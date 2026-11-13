<?php
/**
 * Paid direct messages: access states, spending a credit on the first message, the messages-per-credit and daily limits,
 * opt-out, plan credits, welcome credits, and buying a pack.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/dm_test.php
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
$ids = [];
register_shutdown_function(function () use (&$touched, &$ids) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    foreach ($ids as $uid) {
        q('DELETE FROM payments WHERE user_id = :u', ['u' => $uid]);
        q('DELETE FROM conversations WHERE user1_id = :u OR user2_id = :u', ['u' => $uid]);
        q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
    }
});
function as_user(int $id): void
{
    $_SESSION = ['uid' => $id];
    current_user(true);
}

$stamp = substr(md5((string)microtime(true)), 0, 8);
$county = (int)val("SELECT id FROM locations WHERE type = 'county' AND is_active = 1 ORDER BY id LIMIT 1", [], 0) ?: null;
$mk = function (string $tag) use ($stamp, $county, &$ids): int {
    $id = create_account(['email' => "$tag-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => ucfirst($tag), 'date_of_birth' => '1990-05-05', 'gender' => 'male', 'county_id' => $county]);
    $ids[] = $id;
    return $id;
};
set_for_test('messaging_enabled', '1');
set_for_test('msg_access', 'matched');
set_for_test('dm_paid_enabled', '1');
set_for_test('dm_messages_per_unlock', '2');
set_for_test('dm_daily_unlocks', '2');
set_for_test('dm_welcome_credits', '0');
set_for_test('dm_to_hosts', '0');

$a = $mk('sender');
$b = $mk('target');
$c = $mk('target2');
$d = $mk('target3');
as_user($a);

$acc = messaging_access($b);
check('not matched and no credits: denied with code paid', !$acc['allowed'] && $acc['code'] === 'paid', json_encode($acc));
check('denied result points at the credits page', str_contains((string)$acc['cta'], '/messages/credits'));

// Buy a pack
$pack = row('SELECT * FROM dm_packs WHERE is_active = 1 ORDER BY id LIMIT 1');
$r = checkout_dm_pack((int)$pack['id'], $b);
check('checkout creates a pending dm_pack payment', $r['ok'] && $r['payment']['payment_type'] === 'dm_pack' && (float)$r['payment']['amount'] === (float)$pack['price']);
$r2 = checkout_dm_pack((int)$pack['id'], $b);
check('repeating checkout reuses the pending payment', (int)$r2['payment']['id'] === (int)$r['payment']['id']);
$bad = checkout_dm_pack(999999, $b);
check('unknown pack cannot be bought', !$bad['ok']);
q('UPDATE dm_packs SET is_active = 0 WHERE id = :i', ['i' => $pack['id']]);
$hidden = checkout_dm_pack((int)$pack['id'], $b);
check('hidden pack cannot be bought', !$hidden['ok']);
q('UPDATE dm_packs SET is_active = 1 WHERE id = :i', ['i' => $pack['id']]);

payment_complete((int)$r['payment']['id'], ['mpesa_receipt' => 'DM' . strtoupper($stamp)]);
check('completing the payment adds exactly the pack credits', dm_balance($a) === (int)$pack['credits'], (string)dm_balance($a));
payment_complete((int)$r['payment']['id'], ['mpesa_receipt' => 'DM' . strtoupper($stamp)]);
check('completing it twice does not add credits twice', dm_balance($a) === (int)$pack['credits']);

$start = dm_balance($a);
$acc = messaging_access($b);
check('with credits: allowed as a new paid chat', $acc['allowed'] && ($acc['dm'] ?? '') === 'new');
$cid = conversation_get_or_create($a, $b, $a);
check('opening the chat does not spend a credit', dm_balance($a) === $start);
$s1 = message_send($cid, 'Hello there');
check('first message sends', $s1['ok'], json_encode($s1));
check('first message spends exactly one credit', dm_balance($a) === $start - 1);
$acc = messaging_access($b);
check('afterwards the chat is unlocked with messages left', $acc['allowed'] && ($acc['dm'] ?? '') === 'unlocked' && $acc['dm_left'] === 1, json_encode($acc));
$s2 = message_send($cid, 'Second one');
check('second message sends without another credit', $s2['ok'] && dm_balance($a) === $start - 1);
$acc = messaging_access($b);
check('after the allowed messages, denied until they reply (dm_limit)', !$acc['allowed'] && $acc['code'] === 'dm_limit', json_encode($acc));
$s3 = message_send($cid, 'Third one');
check('a third message is refused', !$s3['ok']);

// They reply, the chat opens both ways.
as_user($b);
$accB = messaging_access($a);
check('the person who got the message can reply', $accB['allowed']);
$rb = message_send($cid, 'Hi back');
check('their reply sends', $rb['ok'], json_encode($rb));
as_user($a);
$acc = messaging_access($b);
check('after they reply the sender can write freely', $acc['allowed'] && ($acc['dm_left'] ?? 0) === -1, json_encode($acc));

// Daily limit
$s = dm_balance($a);
dm_add_credits($a, 10, 'admin', null, 'test');
$cid2 = conversation_get_or_create($a, $c, $a);
check('second new chat today is allowed', messaging_access($c)['allowed']);
message_send($cid2, 'Hi C');
$acc = messaging_access($d);
check('daily limit of new chats stops a third new chat', !$acc['allowed'] && $acc['code'] === 'dm_daily', json_encode($acc));
set_for_test('dm_daily_unlocks', '0');
check('daily limit 0 means no limit', messaging_access($d)['allowed']);

// Opt out
q('UPDATE user_profiles SET allow_paid_dm = 0 WHERE user_id = :u', ['u' => $d]);
$acc = messaging_access($d);
check('a member who opted out cannot be paid-messaged', !$acc['allowed'] && $acc['code'] === 'match', json_encode($acc));
q('UPDATE user_profiles SET allow_paid_dm = 1 WHERE user_id = :u', ['u' => $d]);

// Switched off
set_for_test('dm_paid_enabled', '0');
$acc = messaging_access($d);
check('switched off: falls back to the normal match rule', !$acc['allowed'] && $acc['code'] === 'match');
set_for_test('dm_paid_enabled', '1');

// No credits left race
$e = $mk('broke');
as_user($e);
$cidE = conversation_get_or_create($e, $d, $e);
check('no credits: access denied', !messaging_access($d)['allowed']);
$sE = message_send($cidE, 'Please');
check('sending without credits fails with code paid', !$sE['ok'] && $sE['code'] === 'paid');
check('and nothing was stored', (int)val('SELECT COUNT(*) FROM messages WHERE conversation_id = :c', ['c' => $cidE], 0) === 0);

// Matched members never need credits (a fresh pair, because match results are cached per request)
$f = $mk('matched');
q('INSERT INTO matches (user1_id, user2_id, is_active) VALUES (:a, :b, 1)', ['a' => min($f, $d), 'b' => max($f, $d)]);
as_user($f);
$acc = messaging_access($d);
check('matched members message freely, no credit involved', $acc['allowed'] && empty($acc['dm']), json_encode($acc));
q('DELETE FROM matches WHERE user1_id = :a AND user2_id = :b', ['a' => min($f, $d), 'b' => max($f, $d)]);

// Welcome credits
set_for_test('dm_welcome_credits', '2');
$w = $mk('welcome');
check('welcome credits are given at sign up', dm_balance($w) === 2, (string)dm_balance($w));
set_for_test('dm_welcome_credits', '0');
$w2 = $mk('nowelcome');
check('none given when set to 0', dm_balance($w2) === 0);

// Ledger never negative
check('balances never go below zero', (int)val('SELECT COUNT(*) FROM (SELECT user_id, SUM(delta) b FROM dm_ledger GROUP BY user_id HAVING b < 0) t', [], 0) === 0);

// Plan credits via plan feature
check('plan feature dm_credits_monthly is known', function_exists('dm_add_credits') && str_contains(file_get_contents(dirname(__DIR__, 2) . '/includes/plans.php'), 'dm_credits_monthly'));

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
