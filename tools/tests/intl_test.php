<?php
/**
 * International connections: the four pricing modes, paying for a listing and for access, plan exemption, profile rules
 * (no contact details in text, approval, verified-only), who can message whom, and when a phone number may be shown.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/intl_test.php
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
    $id = create_account(['email' => "$tag-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => ucfirst($tag), 'date_of_birth' => '1990-05-05', 'gender' => 'female', 'county_id' => $county]);
    $ids[] = $id;
    return $id;
};
$good = ['country' => 'GB', 'city' => 'Leeds', 'languages' => ['en', 'sw'], 'purposes' => ['friendship'], 'want_countries' => ['KE'], 'headline' => 'Teacher who loves football and travel',
    'about' => 'I enjoy cooking and long walks.', 'contact_type' => 'whatsapp', 'contact_dial' => '44', 'contact_number' => '07712 345678', 'contact_share' => 'chat'];

set_for_test('intl_enabled', '1');
set_for_test('messaging_enabled', '1');
set_for_test('msg_access', 'matched');
set_for_test('intl_approval', '1');
set_for_test('intl_require_verified', '0');
set_for_test('intl_msg_included', '1');
set_for_test('intl_premium_free', 'premium');
set_for_test('intl_join_fee', '300');
set_for_test('intl_join_days', '30');
set_for_test('intl_access_fee', '500');
set_for_test('intl_access_days', '30');
set_for_test('intl_contact_types', json_encode(['whatsapp', 'mobile']));
set_for_test('dm_paid_enabled', '0');

// ---- Modes
set_for_test('intl_mode', 'free');
check('free mode: no listing fee, no access fee', !intl_join_required() && !intl_access_required());
set_for_test('intl_mode', 'access');
check('access mode: viewers pay, listing is free', !intl_join_required() && intl_access_required());
set_for_test('intl_mode', 'join');
check('join mode: listing paid, viewing free', intl_join_required() && !intl_access_required());
set_for_test('intl_mode', 'both');
check('both mode: both paid', intl_join_required() && intl_access_required());
set_for_test('intl_mode', 'bogus');
check('an unknown mode behaves as free', intl_mode() === 'free');

// ---- Profile rules (free mode first)
set_for_test('intl_mode', 'free');
$a = $mk('lister');
$b = $mk('viewer');
as_user($a);
$r = intl_save_profile($a, $good);
check('a good profile saves', $r['ok'], json_encode($r));
$p = intl_profile($a);
check('with approval on, a new profile is pending', $p['status'] === 'pending');
check('pending profiles are not live', !intl_profile_live($p));
check('phone number is stored as digits without the leading zero', $p['contact_number'] === '7712345678' && $p['contact_dial'] === '44', json_encode([$p['contact_dial'], $p['contact_number']]));

$bad = intl_save_profile($a, ['about' => 'Call me on +44 7712 345678'] + $good);
check('a phone number in the text is refused', !$bad['ok'] && isset($bad['errors']['about']));
$bad = intl_save_profile($a, ['headline' => 'Email me at me@example.com please'] + $good);
check('an email in the text is refused', !$bad['ok'] && isset($bad['errors']['headline']));
$bad = intl_save_profile($a, ['country' => 'ZZ'] + $good);
check('an unknown country is refused', !$bad['ok'] && isset($bad['errors']['country']));
$bad = intl_save_profile($a, ['languages' => []] + $good);
check('no language is refused', !$bad['ok'] && isset($bad['errors']['languages']));
$bad = intl_save_profile($a, ['contact_type' => 'telegram'] + $good);
check('a number type the admin has not allowed is refused', !$bad['ok'] && isset($bad['errors']['contact_type']));
$bad = intl_save_profile($a, ['contact_number' => '12'] + $good);
check('a number that is too short is refused', !$bad['ok'] && isset($bad['errors']['contact_number']));
$ok = intl_save_profile($a, ['contact_type' => '', 'contact_number' => ''] + $good);
check('a number is optional', $ok['ok'] && intl_profile($a)['contact_number'] === null);
intl_save_profile($a, $good);

update('intl_profiles', ['status' => 'active', 'approved_at' => now()], ['user_id' => $a]);
check('approved profile is live when listing is free', intl_profile_live(intl_profile($a)));
intl_save_profile($a, ['about' => 'Different words now'] + $good);
check('changing the words sends a live profile back for approval', intl_profile($a)['status'] === 'pending');
intl_save_profile($a, $good);
update('intl_profiles', ['status' => 'active'], ['user_id' => $a]);
intl_save_profile($a, ['languages' => ['en']] + $good);
check('changing only filters keeps it live', intl_profile($a)['status'] === 'active');
set_for_test('intl_approval', '0');
$c = $mk('autoapprove');
as_user($c);
intl_save_profile($c, $good);
check('with approval off, a profile goes live at once', intl_profile($c)['status'] === 'active');
set_for_test('intl_approval', '1');

set_for_test('intl_require_verified', '1');
$d = $mk('unverified');
as_user($d);
$r = intl_save_profile($d, $good);
check('verified-only: an unverified member cannot list', !$r['ok'] && isset($r['errors']['verified']));
q('UPDATE user_profiles SET is_verified = 1 WHERE user_id = :u', ['u' => $d]);
$r = intl_save_profile($d, $good);
check('verified-only: a verified member can', $r['ok']);
set_for_test('intl_require_verified', '0');

// ---- Paid listing
set_for_test('intl_mode', 'join');
as_user($a);
$pa = intl_profile($a);
check('paid listing: approved but unpaid is not live', !intl_profile_live($pa));
$co = checkout_intl('join');
check('listing checkout uses the admin price', $co['ok'] && (float)$co['payment']['amount'] === 300.0 && $co['payment']['payment_type'] === 'intl');
$co2 = checkout_intl('join');
check('repeating checkout reuses the pending payment', (int)$co2['payment']['id'] === (int)$co['payment']['id']);
check('access cannot be bought in join mode', !checkout_intl('access')['ok']);
payment_complete((int)$co['payment']['id'], ['mpesa_receipt' => 'IX' . strtoupper($stamp)]);
$pa = intl_profile($a);
check('paying lists the member for the paid days', $pa['listed_until'] !== null && abs(strtotime((string)$pa['listed_until']) - (time() + 30 * 86400)) < 120);
check('paid listing is live', intl_profile_live($pa));
$before = $pa['listed_until'];
payment_complete((int)$co['payment']['id'], ['mpesa_receipt' => 'IX' . strtoupper($stamp)]);
check('completing twice does not extend twice', intl_profile($a)['listed_until'] === $before);
$ee = checkout_intl('nonsense');
check('an unknown kind is refused', !$ee['ok']);

// ---- Premium plans are exempt
$prem = $mk('premium');
as_user($prem);
intl_save_profile($prem, $good);
update('intl_profiles', ['status' => 'active'], ['user_id' => $prem]);
check('without a plan, a premium-to-be member is not exempt', !intl_exempt($prem));
$plan = row('SELECT id FROM subscription_plans WHERE tier = 1 AND is_active = 1 LIMIT 1');
if ($plan) {
    q('INSERT INTO user_subscriptions (user_id, plan_id, billing_period, status, starts_at, expires_at) VALUES (:u, :p, \'monthly\', \'active\', NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY))', ['u' => $prem, 'p' => $plan['id']]);
    unset($GLOBALS['__tier_cache'][$prem], $GLOBALS['__sub_cache'][$prem]);
    check('a Premium member is exempt from fees', intl_exempt($prem));
    check('an exempt member is live without paying', intl_profile_live(intl_profile($prem)));
    check('an exempt member cannot buy what is included', !checkout_intl('join')['ok']);
    set_for_test('intl_premium_free', 'none');
    check('with "nobody exempt", Premium pays too', !intl_exempt($prem));
    set_for_test('intl_premium_free', 'premium');
} else {
    check('a Premium plan exists for the exemption test', false, 'no tier 1 plan in this database');
}

// ---- Paid access
set_for_test('intl_mode', 'access');
as_user($b);
check('without paying, a viewer has no access', !intl_has_access($b));
$co = checkout_intl('access');
check('access checkout uses the admin price', $co['ok'] && (float)$co['payment']['amount'] === 500.0);
check('listing cannot be bought in access mode', !checkout_intl('join')['ok']);
payment_complete((int)$co['payment']['id'], ['mpesa_receipt' => 'IA' . strtoupper($stamp)]);
check('paying gives access', intl_has_access($b));
$until = intl_access_until($b);
check('access ends after the paid days', $until !== null && abs(strtotime($until) - (time() + 30 * 86400)) < 120);
q("UPDATE intl_access SET expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE user_id = :u", ['u' => $b]);
check('expired access is not access', !intl_has_access($b));
intl_grant_access($b, 0, 'admin');
check('access with no end date works', intl_has_access($b) && intl_access_until($b) === '0');
intl_grant_access($b, 30, 'admin');
check('giving days to an unlimited member does not shorten it', intl_access_until($b) === '0');
q('DELETE FROM intl_access WHERE user_id = :u', ['u' => $b]);

// ---- Browse and messaging
update('intl_profiles', ['status' => 'active'], ['user_id' => $a]);
[$list] = intl_member_ids($b, []);
check('a live listing shows in browse', in_array($a, $list, true));
[$list] = intl_member_ids($b, ['country' => 'GB']);
check('country filter keeps it', in_array($a, $list, true));
[$list] = intl_member_ids($b, ['country' => 'FR']);
check('country filter drops it', !in_array($a, $list, true));
[$list] = intl_member_ids($b, ['language' => 'en']);
check('language filter keeps a match', in_array($a, $list, true));
[$list] = intl_member_ids($b, ['language' => 'sw']);
check('language filter drops a non-match', !in_array($a, $list, true));
[$list] = intl_member_ids($b, ['purpose' => 'language']);
check('purpose filter works', !in_array($a, $list, true));

set_for_test('intl_mode', 'access');
as_user($b);
$acc = messaging_access($a);
check('no access: messaging a listed member is refused', !$acc['allowed'], json_encode($acc));
intl_grant_access($b, 30, 'purchase');
$acc = messaging_access($a);
check('with access: messaging a listed member is allowed', $acc['allowed'], json_encode($acc));
set_for_test('intl_msg_included', '0');
check('access without messaging included: still refused', !messaging_access($a)['allowed']);
set_for_test('intl_msg_included', '1');
update('intl_profiles', ['status' => 'paused'], ['user_id' => $a]);
check('a paused listing cannot be reached through access', !messaging_access($a)['allowed']);
update('intl_profiles', ['status' => 'active'], ['user_id' => $a]);
$other = $mk('notlisted');
check('access does not open members who are not listed', !messaging_access($other)['allowed']);

// ---- Phone number visibility
check('number hidden before any chat', !intl_contact_visible($b, $a));
$cid = conversation_get_or_create($b, $a, $b);
$s = message_send($cid, 'Hello from Nairobi');
check('the viewer can write', $s['ok'], json_encode($s));
check('number still hidden after only one side wrote', !intl_contact_visible($b, $a));
as_user($a);
$s = message_send($cid, 'Hello back');
check('the listed member can reply', $s['ok'], json_encode($s));
check('number shown once both have written', intl_contact_visible($b, $a));
update('intl_profiles', ['contact_share' => 'never'], ['user_id' => $a]);
check('number stays hidden when the owner keeps it private', !intl_contact_visible($b, $a));
update('intl_profiles', ['contact_share' => 'chat'], ['user_id' => $a]);
check('contact text includes the type and full number', str_contains(intl_contact_text(intl_profile($a)), '+44 7712345678') && str_contains(intl_contact_text(intl_profile($a)), 'WhatsApp'));

// ---- Misc
check('country flag emoji is made from the code', country_flag('KE') !== '' && mb_strlen(country_flag('KE')) === 2);
check('stats count live members', intl_stats()['members'] >= 1);
check('free mode always grants access', (function () {
    setting_set('intl_mode', 'free');
    return intl_has_access(999999);
})());

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
