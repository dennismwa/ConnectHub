<?php
/**
 * Boost and featured packs: buying a pack gives exactly its length, hidden or unknown packs cannot be bought, the stacking
 * and daily limits work, and plan credits follow the same rules.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/boost_test.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) {
    exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n");
}
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $name . (!$ok && $detail !== '' ? "  ($detail)" : '') . "\n";
}
$touched = [];
function set_for_test(string $key, string $value): void
{
    global $touched;
    if (!array_key_exists($key, $touched)) {
        $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $key]);
        $touched[$key] = $r ? (string)$r['setting_value'] : null;
    }
    setting_set($key, $value);
}
$uid = 0;
$packIds = [];
register_shutdown_function(function () use (&$touched, &$uid, &$packIds) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    if ($packIds) {
        q('DELETE FROM boost_packs WHERE id IN (:i)', ['i' => $packIds]);
    }
    if ($uid) {
        q('DELETE FROM payments WHERE user_id = :u', ['u' => $uid]);
        q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
    }
});

$stamp = substr(md5((string)microtime(true)), 0, 8);
$county = (int)val("SELECT id FROM locations WHERE type = 'county' AND is_active = 1 ORDER BY id LIMIT 1", [], 0) ?: null;
$uid = create_account(['email' => "b-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => 'Boost Tester', 'date_of_birth' => '1990-05-05', 'gender' => 'male', 'county_id' => $county]);
$_SESSION = ['uid' => $uid];
current_user(true);
check('test member is signed in', auth_id() === $uid);

set_for_test('boost_enabled', '1');
set_for_test('featured_enabled', '1');
set_for_test('boost_stack', '1');
set_for_test('boost_daily_limit', '0');
set_for_test('boost_credit_minutes', '45');

$p120 = insert('boost_packs', ['kind' => 'boost', 'name' => 'T 2 hours', 'minutes' => 120, 'price' => 250, 'is_active' => 1, 'sort_order' => 90]);
$pHid = insert('boost_packs', ['kind' => 'boost', 'name' => 'T hidden', 'minutes' => 60, 'price' => 100, 'is_active' => 0, 'sort_order' => 91]);
$pFree = insert('boost_packs', ['kind' => 'boost', 'name' => 'T free', 'minutes' => 60, 'price' => 0, 'is_active' => 1, 'sort_order' => 92]);
$pFeat = insert('boost_packs', ['kind' => 'featured', 'name' => 'T featured 3 days', 'minutes' => 3 * 1440, 'price' => 500, 'is_active' => 1, 'sort_order' => 93]);
$packIds = [$p120, $pHid, $pFree, $pFeat];

echo "\nBuying\n";
$r = checkout_boost($p120);
check('a pack can be bought at its own price', $r['ok'] && (float)$r['payment']['amount'] === 250.0, (string)($r['error'] ?? ''));
check('the order remembers the length', json_decode((string)$r['payment']['metadata'], true)['minutes'] === 120);
payment_complete((int)$r['payment']['id']);
$exp = strtotime((string)user_boost_expiry($uid));
check('the boost lasts as long as the pack', abs($exp - (time() + 7200)) < 15, (string)($exp - time()));
check('a hidden pack cannot be bought', !checkout_boost($pHid)['ok']);
check('a free (zero price) pack cannot be bought', !checkout_boost($pFree)['ok']);
check('an unknown pack cannot be bought', !checkout_boost(999999)['ok']);
check('a featured pack cannot be bought as a boost', !checkout_boost($pFeat)['ok']);

echo "\nStacking and limits\n";
$r2 = checkout_boost($p120);
payment_complete((int)$r2['payment']['id']);
check('a second boost adds time', abs(strtotime((string)user_boost_expiry($uid)) - (time() + 14400)) < 15);
set_for_test('boost_stack', '0');
check('stacking off blocks a purchase while running', !checkout_boost($p120)['ok']);
check('stacking off blocks a plan credit while running', boost_use_credit($uid) === null);
set_for_test('boost_stack', '1');
set_for_test('boost_daily_limit', '2');
check('daily limit blocks the third', !boost_can_start($uid)[0] && strpos(boost_can_start($uid)[1], 'a day') !== false);
set_for_test('boost_daily_limit', '0');
set_for_test('boost_enabled', '0');
check('switched off blocks buying', !checkout_boost($p120)['ok']);
set_for_test('boost_enabled', '1');

echo "\nPlan credits\n";
q('DELETE FROM boosts WHERE user_id = :u', ['u' => $uid]);
insert('boost_credits', ['user_id' => $uid]);
$exp = boost_use_credit($uid);
check('a credit gives the credit length', $exp !== null && abs(strtotime($exp) - (time() + 2700)) < 15);
check('the credit is spent', boost_credits_available($uid) === 0 && boost_use_credit($uid) === null);

echo "\nFeatured\n";
$f = checkout_featured($pFeat);
check('a featured pack can be bought', $f['ok'] && (float)$f['payment']['amount'] === 500.0, (string)($f['error'] ?? ''));
payment_complete((int)$f['payment']['id']);
$until = strtotime((string)val('SELECT featured_until FROM users WHERE id = :u', ['u' => $uid]));
check('featured lasts the pack length', abs($until - (time() + 3 * 86400)) < 15);
check('featured can be switched off', (function () use ($pFeat) {
    set_for_test('featured_enabled', '0');
    $ok = !checkout_featured($pFeat)['ok'];
    set_for_test('featured_enabled', '1');
    return $ok;
})());
check('length labels read well', boost_length_label(30) === '30 minutes' && boost_length_label(120) === '2 hours' && boost_length_label(1440) === '1 day' && boost_length_label(4320) === '3 days' && boost_length_label(90) === '90 minutes');

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
