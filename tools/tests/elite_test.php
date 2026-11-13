<?php
/**
 * Elite, the visitor choice picker, the dating sign-up gate and the manual M-Pesa details.
 *   export CH_CONFIG=/path/to/throwaway-config.php   (a database with demo members: Admin > Tools > Demo data)
 *   php tools/tests/elite_test.php
 * Everything it creates is removed again; settings it changes are put back.
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
    cache_clear();
}
function act_as(int $uid): void
{
    $_SESSION['uid'] = $uid;
    if (function_exists('plans_forget')) {
        plans_forget($uid);
    }
    current_user(true);
}

// ---- pick test members: an owner with an approved photo, two seekers ----
$ids = array_map('intval', col("SELECT u.id FROM users u JOIN user_profiles p ON p.user_id = u.id WHERE u.role = 'user' AND u.status = 'active' AND p.onboarding_completed = 1
    AND p.date_of_birth IS NOT NULL AND EXISTS (SELECT 1 FROM user_photos ph WHERE ph.user_id = u.id AND ph.moderation_status = 'approved') ORDER BY u.id DESC LIMIT 3"));
if (count($ids) < 3) {
    exit("Needs at least three demo members with an approved photo.\n");
}
[$owner, $seeker, $other] = $ids;
$snap = [
    'elite_profiles' => rows('SELECT * FROM elite_profiles WHERE user_id IN (' . implode(',', $ids) . ')'),
    'flags' => pairs('SELECT user_id, elite_only FROM user_profiles WHERE user_id IN (' . implode(',', $ids) . ')'),
    'modes' => pairs('SELECT user_id, explore_mode FROM user_profiles WHERE user_id IN (' . implode(',', $ids) . ')'),
    'seo' => rows("SELECT * FROM seo_pages WHERE page_key IN ('elite', 'elite_access')"),
];
register_shutdown_function(function () use (&$touched, $ids, $snap) {
    $in = implode(',', $ids);
    q("DELETE FROM elite_requests WHERE from_id IN ($in) OR to_id IN ($in)");
    q("DELETE FROM elite_unlocks WHERE viewer_id IN ($in) OR owner_id IN ($in)");
    q("DELETE FROM elite_access WHERE user_id IN ($in)");
    q("DELETE FROM payments WHERE payment_type = 'elite' AND user_id IN ($in) AND description LIKE '%[test]%'");
    q("DELETE FROM elite_profiles WHERE user_id IN ($in)");
    q("DELETE FROM elite_saves WHERE user_id IN ($in) OR owner_id IN ($in)");
    q("DELETE FROM blocks WHERE blocker_id IN ($in) AND blocked_id IN ($in)");
    q("DELETE FROM seo_pages WHERE page_key IN ('elite', 'elite_access')");
    foreach ($snap['seo'] as $r) {
        insert('seo_pages', $r);
    }
    foreach ($snap['elite_profiles'] as $r) {
        insert('elite_profiles', $r);
    }
    foreach ($snap['flags'] as $u => $f) {
        q('UPDATE user_profiles SET elite_only = :f, explore_mode = :m WHERE user_id = :u', ['f' => (int)$f, 'm' => $snap['modes'][$u] ?? null, 'u' => (int)$u]);
    }
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    cache_clear();
    global $pass, $fail;
    echo "\n$pass passed, $fail failed\n";
});
$in = implode(',', $ids);
q("DELETE FROM elite_requests WHERE from_id IN ($in) OR to_id IN ($in)");
q("DELETE FROM elite_unlocks WHERE viewer_id IN ($in) OR owner_id IN ($in)");
q("DELETE FROM elite_access WHERE user_id IN ($in)");
q("DELETE FROM elite_profiles WHERE user_id IN ($in)");
foreach (['elite_enabled' => '1', 'elite_approval' => '1', 'elite_require_verified' => '0', 'elite_require_photo' => '1', 'elite_join_fee' => '0', 'elite_view_mode' => 'pay',
    'elite_single_price' => '200', 'elite_pack_price' => '800', 'elite_pack_size' => '5', 'elite_pass_price' => '2500', 'elite_unlock_days' => '0', 'elite_access_free_for' => 'none',
    'elite_filter_enabled' => '1', 'elite_request_daily' => '5', 'elite_request_needs_unlock' => '0', 'elite_share_phone' => '1'] as $k => $v) {
    set_for_test($k, $v);
}

echo "Elite profiles\n";
act_as($owner);
$bad = elite_save_profile($owner, ['headline' => 'Hi', 'about' => 'short', 'industry' => '', 'seeking' => []]);
check('too-short profile is refused with field errors', !$bad['ok'] && isset($bad['errors']['headline'], $bad['errors']['about'], $bad['errors']['industry'], $bad['errors']['seeking']));
$tx = elite_save_profile($owner, ['headline' => 'Looking for a partner, generous allowance', 'about' => str_repeat('I love travel and good food with good people. ', 3), 'industry' => 'business', 'seeking' => ['longterm']]);
check('transactional wording is refused', !$tx['ok'] && strpos($tx['errors']['headline'] ?? '', 'genuine') !== false, json_encode($tx['errors']));
$ct = elite_save_profile($owner, ['headline' => 'Founder who loves road trips', 'about' => 'Call me on 0712345678 anytime, I love travel and good food and long walks on the beach.', 'industry' => 'business', 'seeking' => ['longterm']]);
check('contact details in the text are refused', !$ct['ok'] && isset($ct['errors']['about']));
$good = ['headline' => 'Founder who loves road trips', 'about' => 'I run a logistics business and spend weekends on road trips. Looking for someone kind and curious for something lasting.',
    'industry' => 'business', 'lifestyle' => 'wealthy', 'offers' => ['travel', 'dining', 'nonsense'], 'seeking' => ['longterm', 'marriage'],
    'c_gender' => 'any', 'w_gender' => 'off', 'c_age_min' => 20, 'c_age_max' => 40, 'w_age' => 'must', 'w_verified' => 'nice', 'w_photo' => 'nice',
    'filter_on' => 1, 'min_score' => 60, 'hide_in_dating' => 1, 'share_phone' => 1];
$r = elite_save_profile($owner, $good);
check('valid profile saved, waiting for approval', $r['ok'] && $r['status'] === 'pending', $r['error']);
$p = elite_profile($owner);
check('unknown options are dropped', $p['offers'] === 'travel,dining');
check('criteria stored with importance', $p['criteria_arr']['age']['w'] === 'must' && $p['criteria_arr']['age']['min'] === 20);
check('pending profile is not live and not hidden from dating yet', !elite_profile_live($p) && (int)val('SELECT elite_only FROM user_profiles WHERE user_id = :u', ['u' => $owner]) === 0);
[$ok] = elite_admin_action($owner, 'approve');
$p = elite_profile($owner);
check('approval makes it live and hides it from ordinary dating', $ok && elite_profile_live($p) && (int)val('SELECT elite_only FROM user_profiles WHERE user_id = :u', ['u' => $owner]) === 1);
check('Elite-only member is left out of members_search', !in_array($owner, array_map('intval', array_column(members_search(['role' => 'user'], 500, 0)['rows'], 'id')), true));
set_for_test('elite_join_fee', '500');
check('with a joining price an unpaid profile is not live', !elite_profile_live(elite_profile($owner)));
check('unpaid profile is not in the search', !in_array($owner, array_map('intval', array_column(elite_search(0, [], 100)['rows'], 'user_id')), true));
set_for_test('elite_join_fee', '0');
check('free joining: live again and in the search', elite_profile_live(elite_profile($owner)) && in_array($owner, array_map('intval', array_column(elite_search(0, [], 100)['rows'], 'user_id')), true));

echo "Match score\n";
$facts = elite_seeker_facts($seeker);
$sc = elite_score(elite_profile($owner), $seeker);
$ageOk = $facts['age'] !== null && $facts['age'] >= 20 && $facts['age'] <= 40;
check('the age must-have decides whether a request passes', $sc['passed'] === ($ageOk && $sc['score'] >= 60), json_encode($sc));
check('score is between 0 and 100', $sc['score'] >= 0 && $sc['score'] <= 100);
$noCrit = elite_profile($owner);
$noCrit['criteria_arr'] = elite_criteria_normalise([]);
$noCrit['criteria_arr']['photo']['w'] = 'off';
check('no criteria means a full score', elite_score($noCrit, $seeker)['score'] === 100);
$noCrit['filter_on'] = 0;
$noCrit['criteria_arr']['age'] = ['min' => 90, 'max' => 99, 'w' => 'must'];
check('with the filter off every request passes', elite_score($noCrit, $seeker)['passed'] === true);

echo "Seeing full profiles\n";
act_as($seeker);
check('locked by default in paid mode', !elite_can_view_full($seeker, $owner));
check('locked photos blur in the Elite context', elite_photo_locked($seeker, $owner));
check('products: one profile, a pack and a pass', array_keys(elite_products()) === ['single', 'pack', 'pass']);
$co = checkout_elite('single', $owner);
check('single unlock checkout creates a payment', $co['ok'] && $co['payment']['payment_type'] === 'elite' && (float)$co['payment']['amount'] === 200.0);
q("UPDATE payments SET description = CONCAT(description, ' [test]') WHERE id = :id", ['id' => (int)$co['payment']['id']]);
$co2 = checkout_elite('single', $owner);
check('the same pending payment is reused', $co2['ok'] && (int)$co2['payment']['id'] === (int)$co['payment']['id']);
payment_complete((int)$co['payment']['id']);
check('paying opens that profile', elite_can_view_full($seeker, $owner) && !elite_photo_locked($seeker, $owner));
check('paying again is refused', !checkout_elite('single', $owner)['ok']);
set_for_test('elite_unlock_days', '1');
q('UPDATE elite_unlocks SET expires_at = NOW() - INTERVAL 1 HOUR WHERE viewer_id = :v AND owner_id = :o', ['v' => $seeker, 'o' => $owner]);
check('an expired unlock locks the profile again', !elite_can_view_full($seeker, $owner));
set_for_test('elite_unlock_days', '0');
q('UPDATE elite_unlocks SET expires_at = NULL WHERE viewer_id = :v AND owner_id = :o', ['v' => $seeker, 'o' => $owner]);
act_as($other);
$pk = checkout_elite('pack', 0);
q("UPDATE payments SET description = CONCAT(description, ' [test]') WHERE id = :id", ['id' => (int)$pk['payment']['id']]);
payment_complete((int)$pk['payment']['id']);
check('a pack adds five credits', elite_credits($other) === 5);
$u1 = elite_unlock_with_balance($other, $owner);
check('a credit opens a profile', $u1['ok'] && $u1['used'] === 'credit' && elite_credits($other) === 4 && elite_can_view_full($other, $owner));
$u2 = elite_unlock_with_balance($other, $owner);
check('opening it again costs nothing', $u2['ok'] && $u2['used'] === 'already' && elite_credits($other) === 4);
q('UPDATE elite_access SET credits_until = NOW() - INTERVAL 1 DAY WHERE user_id = :u', ['u' => $other]);
check('expired credits count as zero', elite_credits($other) === 0);
elite_extend_pass($other, 3);
check('a pass opens every profile', elite_pass_until($other) !== null);
q('UPDATE elite_access SET pass_until = NOW() - INTERVAL 1 DAY WHERE user_id = :u', ['u' => $other]);
q("DELETE FROM elite_unlocks WHERE viewer_id = :v", ['v' => $other]);
check('after the pass ends the profile locks again', !elite_can_view_full($other, $owner));
set_for_test('elite_view_mode', 'free');
check('free mode opens profiles for members', elite_can_view_full($other, $owner) && elite_products() === []);
check('free mode never opens profiles for visitors', !elite_can_view_full(0, $owner));
set_for_test('elite_view_mode', 'accept');
check('accept mode keeps profiles closed until accepted', !elite_can_view_full($other, $owner));
set_for_test('elite_view_mode', 'pay');
q('UPDATE elite_profiles SET photos_after_accept = 1 WHERE user_id = :u', ['u' => $owner]);
check('"photos only for people I accept" overrides a paid unlock', !elite_can_view_full($seeker, $owner));
q('UPDATE elite_profiles SET photos_after_accept = 0 WHERE user_id = :u', ['u' => $owner]);

echo "Requests\n";
act_as($seeker);
check('a short intro is refused', !elite_request_send($seeker, $owner, 'Hi there')['ok']);
check('an intro with a phone number is refused', !elite_request_send($seeker, $owner, 'Hello, please call me on 0712 345 678 when you can')['ok']);
check('you cannot request yourself', !elite_request_send($owner, $owner, str_repeat('Hello there, ', 3))['ok']);
$rs = elite_request_send($seeker, $owner, 'Hello, I enjoyed your profile and would love to hear about your road trips.');
check('a good request is sent', $rs['ok'], $rs['error'] ?? '');
check('a second request is refused while the first waits', !elite_request_send($seeker, $owner, 'Hello again, just checking you saw my message earlier.')['ok']);
$req = elite_request_between($seeker, $owner);
check('the request carries a score and the criteria detail', $req && (int)$req['score'] >= 0 && json_decode((string)$req['detail'], true) !== null);
q('UPDATE elite_requests SET passed = 1 WHERE id = :id', ['id' => (int)$req['id']]);
check('it shows in the owner\'s inbox', elite_pending_count($owner) === 1 && count(elite_inbox($owner, 'new')) === 1);
act_as($owner);
$acc = elite_request_respond($owner, (int)$req['id'], 'accept');
check('accepting works and opens a conversation', $acc['ok'] && (int)$acc['conversation'] > 0);
check('the intro is the first message', (string)val('SELECT content FROM messages WHERE conversation_id = :c ORDER BY id LIMIT 1', ['c' => (int)$acc['conversation']], '') === (string)$req['message']);
check('accepted: both can message, the seeker sees everything', elite_message_ok($seeker, $owner) && elite_can_view_full($seeker, $owner));
check('answering twice is refused', !elite_request_respond($owner, (int)$req['id'], 'decline')['ok']);
q('DELETE FROM messages WHERE conversation_id = :c', ['c' => (int)$acc['conversation']]);
q('DELETE FROM conversations WHERE id = :c', ['c' => (int)$acc['conversation']]);
act_as($other);
$r2 = elite_request_send($other, $owner, 'Hello, your profile caught my eye and I would like to say hello properly.');
$req2 = elite_request_between($other, $owner);
act_as($owner);
elite_request_respond($owner, (int)$req2['id'], 'decline');
act_as($other);
check('after a decline the same member cannot ask again', $r2['ok'] && !elite_request_send($other, $owner, 'Hello, trying once more because I really liked your profile.')['ok']);
q("UPDATE elite_requests SET status = 'pending', created_at = NOW() - INTERVAL 400 DAY WHERE id = :id", ['id' => (int)$req2['id']]);
check('old unanswered requests expire', elite_requests_expire() >= 1 && (string)val('SELECT status FROM elite_requests WHERE id = :id', ['id' => (int)$req2['id']]) === 'expired');
set_for_test('elite_request_daily', '1');
q('UPDATE elite_requests SET created_at = NOW() WHERE id = :id', ['id' => (int)$req2['id']]);
check('the daily limit applies', !elite_request_send($other, $owner, 'Hello, a fresh request after the old one expired today.')['ok']);
set_for_test('elite_request_daily', '5');

echo "Leaving and admin\n";
elite_leave($owner);
check('leaving hides the profile and lets them back into dating', !elite_profile_live(elite_profile($owner)) && (int)val('SELECT elite_only FROM user_profiles WHERE user_id = :u', ['u' => $owner]) === 0);
$re = elite_save_profile($owner, $good);
check('coming back sends the profile for approval again', $re['ok'] && $re['status'] === 'pending');
[$okV] = elite_admin_action($owner, 'approve');
elite_admin_action($owner, 'verify');
elite_admin_action($owner, 'feature');
$p = elite_profile($owner);
check('admin verify and feature', $okV && (int)$p['is_verified'] === 1 && (int)$p['is_featured'] === 1);
[$okS] = elite_admin_action($owner, 'suspend', 'test');
check('suspend hides it', $okS && !elite_profile_live(elite_profile($owner)));
[$okG, $msgG] = elite_admin_grant($seeker, 'credits', 3);
check('admin can give credits', $okG && elite_credits($seeker) === 3, $msgG);
check('setup sentence reads well', strpos(elite_setup_sentence(), 'Contact always goes through a request') !== false);
check('a payment summary exists for each kind', elite_payment_summary(['description' => ''], ['kind' => 'pass', 'days' => 30])[0] !== '' && payment_type_label('elite') === elite_label());

echo "Edge cases\n";
elite_admin_action($owner, 'restore');
q("DELETE FROM elite_requests WHERE to_id = :o", ['o' => $owner]);
q('DELETE FROM elite_unlocks WHERE owner_id = :o', ['o' => $owner]);
q('UPDATE elite_profiles SET hide_in_dating = 0, photos_after_accept = 1 WHERE user_id = :u', ['u' => $owner]);
elite_sync_dating_flag($owner);
check('"photos only for people I accept" keeps them out of ordinary dating', (int)val('SELECT elite_only FROM user_profiles WHERE user_id = :u', ['u' => $owner]) === 1);
act_as($other);
check('nobody can pay to unlock such a member', !checkout_elite('single', $owner)['ok']);
$pk2 = checkout_elite('pack', $owner);
check('a pack bought from their page keeps every credit', $pk2['ok'] && strpos((string)$pk2['payment']['metadata'], 'owner_id') === false);
q("UPDATE payments SET description = CONCAT(description, ' [test]') WHERE id = :id", ['id' => (int)$pk2['payment']['id']]);
set_for_test('elite_request_needs_unlock', '1');
check('they can still receive requests when requests need an unlock', elite_request_send($other, $owner, 'Hello, I would really like to get to know you better, if you are open to it.')['ok']);
set_for_test('elite_request_needs_unlock', '0');
q('UPDATE elite_profiles SET hide_in_dating = 1, photos_after_accept = 0 WHERE user_id = :u', ['u' => $owner]);
$rw = elite_request_between($other, $owner);
elite_request_withdraw($other, (int)$rw['id']);
check('after withdrawing, a new request waits a day', !elite_request_send($other, $owner, 'Hello again, sorry, I withdrew by mistake earlier today.')['ok']);
q("UPDATE elite_requests SET status = 'pending', responded_at = NULL WHERE id = :id", ['id' => (int)$rw['id']]);
elite_admin_action($owner, 'suspend', 'test');
check('suspending expires waiting requests', (string)val('SELECT status FROM elite_requests WHERE id = :id', ['id' => (int)$rw['id']]) === 'expired');
q("UPDATE elite_requests SET status = 'pending' WHERE id = :id", ['id' => (int)$rw['id']]);
act_as($owner);
check('a suspended member cannot accept', !elite_request_respond($owner, (int)$rw['id'], 'accept')['ok']);
q("UPDATE elite_requests SET status = 'accepted' WHERE id = :id", ['id' => (int)$rw['id']]);
check('and Elite no longer opens chats for them', !elite_message_ok($other, $owner));
elite_admin_action($owner, 'restore');
check('restored: the accepted chat works again', elite_message_ok($other, $owner));
set_for_test('elite_enabled', '0');
elite_resync_all();
check('Elite switched off: everyone is back in ordinary dating', (int)val('SELECT elite_only FROM user_profiles WHERE user_id = :u', ['u' => $owner]) === 0);
set_for_test('elite_enabled', '1');
elite_resync_all();
check('switched on again: the flag returns', (int)val('SELECT elite_only FROM user_profiles WHERE user_id = :u', ['u' => $owner]) === 1);
q('DELETE FROM elite_access WHERE user_id = :u', ['u' => $other]);
elite_add_credits($other, 5, 30);
q('UPDATE elite_access SET credits = credits - 1 WHERE user_id = :u', ['u' => $other]);
elite_add_credits($other, 5, 30);
check('credits add up without losing a spent one', elite_credits($other) === 9);
q('UPDATE elite_access SET credits_until = NOW() - INTERVAL 1 DAY WHERE user_id = :u', ['u' => $other]);
elite_add_credits($other, 5, 30);
check('expired credits are dropped before new ones are added', elite_credits($other) === 5);
act_as($seeker);
q('INSERT IGNORE INTO blocks (blocker_id, blocked_id) VALUES (:a, :b)', ['a' => $owner, 'b' => $seeker]);
check('a blocked member cannot buy an unlock', !checkout_elite('single', $owner)['ok'] && !elite_unlock_with_balance($seeker, $owner)['ok']);
q('DELETE FROM blocks WHERE blocker_id = :a AND blocked_id = :b', ['a' => $owner, 'b' => $seeker]);

echo "Visitor choices\n";
$_SESSION = [];
current_user(true);
$cards = choice_cards();
check('default cards: Dating, Fun and Elite', array_keys($cards) === ['dating', 'fun', 'elite'], implode(',', array_keys($cards)));
check('unknown choices are refused', !choice_save('nonsense') && choice_save('fun'));
check('the current choice comes from the cookie', choice_current() === 'fun');
$sec = [['section_key' => 'profiles'], ['section_key' => 'agents_top'], ['section_key' => 'elite'], ['section_key' => 'faq']];
set_for_test('choice_exclusive', '0');
$ordered = array_column(choice_landing_sections($sec), 'section_key');
check('Fun brings the agents block to the top', $ordered[0] === 'agents_top' && count($ordered) === 4, implode(',', $ordered));
set_for_test('choice_exclusive', '1');
$ordered = array_column(choice_landing_sections($sec), 'section_key');
check('and with "show only what they chose" it also drops the people and Elite blocks', $ordered === ['agents_top', 'faq'], implode(',', $ordered));
check('the hero copy changes with the choice', choice_hero()['h'] === 'Good company, tonight');
set_for_test('choice_cards', (string)(setting('choice_cards') ?? '')); // remember the real cards before the test saves its own
$saved = choice_cards_save(['dating' => ['enabled' => 1, 'label' => 'Dating', 'hide' => ['faq'], 'lead' => ['profiles']], 'fun' => ['enabled' => 0, 'label' => 'Fun'], 'elite' => ['enabled' => 1, 'label' => ''], 'friends' => ['enabled' => 0, 'label' => 'Friends']]);
check('admin can save the cards', $saved[0] === true, (string)$saved[1]);
check('bad links are refused', choice_cards_save(['dating' => ['enabled' => 1, 'label' => 'Dating', 'go' => 'javascript:alert(1)'], 'fun' => ['label' => 'Fun'], 'elite' => [], 'friends' => ['label' => 'Friends']])[0] === false);
check('all cards off is refused', choice_cards_save(['dating' => ['label' => 'Dating'], 'fun' => ['label' => 'Fun'], 'elite' => [], 'friends' => ['label' => 'Friends']])[0] === false);
set_for_test('choice_cards', '');
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1)';
check('search engines never get the picker', choice_is_bot() && !choice_should_open());
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14) Chrome/124 Mobile';
unset($_COOKIE[CHOICE_COOKIE]);
check('a new visitor on a phone gets the picker', !choice_is_bot() && choice_should_open() === (choice_current() === ''));
$art = choice_art('ink') . choice_card_markup_check();
function choice_card_markup_check(): string
{
    return trim(partial('choice_picker'));
}
check('picker markup uses chz- classes, never ad-', strpos($art, 'class="chz') !== false && !preg_match('/class="[^"]*\bad-[a-z]/', $art));

echo "Dating needs an account\n";
set_for_test('dating_requires_account', '1');
check('visitors are gated', dating_gated() && guest_profile_mode() === 'login');
act_as($seeker);
check('members are not', !dating_gated());
$_SESSION = [];
current_user(true);
set_for_test('dating_requires_account', '0');
check('switching it off restores the old visitor rules', !dating_gated() && guest_profile_mode() === (string)setting('guest_profile_view'));

echo "Manual M-Pesa\n";
foreach (['manual_mpesa_number', 'manual_mpesa_account', 'manual_mpesa_type', 'manual_mpesa_details', 'manual_mpesa_name', 'mpesa_c2b_enabled'] as $k) {
    set_for_test($k, (string)(setting($k) ?? ''));
}
set_for_test('mpesa_c2b_enabled', '0');
set_for_test('manual_mpesa_number', '');
set_for_test('manual_mpesa_account', '');
set_for_test('manual_mpesa_details', 'Paybill 522533, account CONNECT');
$pay = ['reference' => 'CH261106ABC123', 'amount' => '200.00'];
$mi = mpesa_manual_info($pay);
check('old free text is read as a Pay Bill with its account', $mi['type'] === 'paybill' && $mi['number'] === '522533' && $mi['account'] === 'CONNECT');
set_for_test('manual_mpesa_details', 'Till 543210');
$mi = mpesa_manual_info($pay);
check('a till number is recognised', $mi['type'] === 'till' && $mi['number'] === '543210' && count(array_filter($mi['steps'], fn($s) => stripos($s[0], 'account') !== false)) === 0);
set_for_test('manual_mpesa_details', 'Send to 0712 345 678');
check('a phone number is recognised', mpesa_manual_info($pay)['type'] === 'phone' && mpesa_manual_info($pay)['number'] === '0712345678');
set_for_test('manual_mpesa_type', 'paybill');
set_for_test('manual_mpesa_number', '400200');
set_for_test('manual_mpesa_details', '');
$mi = mpesa_manual_info($pay);
check('structured Pay Bill uses the order reference as the account', $mi['number'] === '400200' && $mi['account'] === 'CH261106ABC123' && $mi['account_is_ref']);
check('the amount is rounded up to whole shillings for M-Pesa', $mi['amount'] === '200');
check('the steps end with the PIN', stripos(end($mi['steps'])[0], 'PIN') !== false);
check('manual M-Pesa is ready with only the number filled in', payments_methods()['manual']['ready'] === true);

echo "Elite web address\n";
if (!route_table()) {
    load_routes();
}
$baseP = base_path();
check('the default address is /elite', elite_slug(true) === 'elite' && url('/elite/5') === $baseP . '/elite/5');
set_for_test('elite_slug', 'circle');
elite_slug(true);
check('links follow the new address', url('/elite') === $baseP . '/circle' && url('/elite/me') === $baseP . '/circle/me' && abs_url('/elite/7') === site_url() . '/circle/7');
check('queries are kept', url('/elite', ['q' => 'x']) === $baseP . '/circle?q=x');
check('admin and similar paths are left alone', url('/admin/elite') === $baseP . '/admin/elite' && url('/elitegroup') === $baseP . '/elitegroup' && url('/browse') === $baseP . '/browse');
check('incoming requests map back to the routes', elite_path_in('/circle/12') === '/elite/12' && elite_path_in('/circle') === '/elite' && elite_path_in('/circlet') === '/circlet' && elite_path_in('/browse') === '/browse');
check('a request for the new address reaches the Elite route', route_match('GET', elite_path_in('/circle/12'))[0]['file'] === 'elite/profile.php');
check('used words are refused', elite_slug_error('admin') !== null && elite_slug_error('hosts') !== null && elite_slug_error('browse') !== null && elite_slug_error('messages') !== null);
check('bad shapes are refused', elite_slug_error('a') !== null && elite_slug_error('Has Space') !== null && elite_slug_error('-x') !== null && elite_slug_error(str_repeat('a', 40)) !== null);
check('free words are accepted', elite_slug_error('circle') === null && elite_slug_error('vip-club') === null && elite_slug_error('elite') === null);
check('typed text becomes a safe word', elite_slugify('My Circle!') === 'my-circle');
set_for_test('elite_slug', 'Bad Word!');
check('a broken saved value falls back to elite', elite_slug(true) === 'elite');
set_for_test('elite_slug', 'elite');
elite_slug(true);

echo "Elite page text and search appearance\n";
check('the Elite pages are in the SEO list', isset(seo_page_keys()['elite'], seo_page_keys()['elite_access']) && seo_key_for_path('/elite') === 'elite' && seo_key_for_path('/elite/access') === 'elite_access');
$sv = elite_seo_save('elite', ['title' => '  Meet   people ', 'description' => 'A short description.', 'noindex' => '']);
$srow = row("SELECT * FROM seo_pages WHERE page_key = 'elite'");
check('search title and description are saved tidily', !empty($sv['ok']) && $srow && $srow['title'] === 'Meet people' && $srow['description'] === 'A short description.' && (int)$srow['noindex'] === 0);
$sv = elite_seo_save('elite', ['title' => str_repeat('a', 130), 'description' => str_repeat('b', 310)]);
check('too-long title and description are refused', !empty($sv['errors']['title']) && !empty($sv['errors']['description']));
check('other page keys are refused', !empty(elite_seo_save('home', ['title' => 'x'])['errors']));
$sv = elite_seo_save('elite_access', ['title' => 'Prices', 'noindex' => '1']);
check('noindex is saved', !empty($sv['ok']) && (int)val("SELECT noindex FROM seo_pages WHERE page_key = 'elite_access'", [], 0) === 1);
set_for_test('elite_faq', "Q one?\nAnswer one.\nSecond line.\n\nQ two?\nA two.\n\nNo answer here\n\n\n\nQ three?\nA3");
$fq = elite_faq_items();
check('questions and answers are read from the text', count($fq) === 3 && $fq[0]['q'] === 'Q one?' && strpos($fq[0]['a'], 'Second line.') !== false && $fq[2]['q'] === 'Q three?');
$schema = elite_page_schema('Title', 'Desc', $fq);
check('the page tells Google about the questions', $schema['@graph'][1]['@type'] === 'FAQPage' && count($schema['@graph'][1]['mainEntity']) === 3 && $schema['@graph'][0]['@type'] === 'CollectionPage');
set_for_test('elite_faq', '');
check('no text means no questions', elite_faq_items() === [] && count(elite_page_schema('T', 'D', [])['@graph']) === 1);
$manyQ = implode("\n\n", array_map(fn($i) => "Question $i?\nAnswer $i.", range(1, 20)));
set_for_test('elite_faq', $manyQ);
check('at most twelve are used', count(elite_faq_items()) === 12);
foreach (['free', 'accept', 'pay'] as $m) {
    set_for_test('elite_view_mode', $m);
    $steps = elite_steps();
    check("the steps are worded for the \"$m\" way", count($steps) === 4 && ($m === 'pay' ? $steps[2][1] === 'Open the ones you like' : ($m === 'free' ? $steps[2][1] === 'See the full profile' : $steps[2][1] === 'Introduce yourself')));
}
set_for_test('elite_theme', 'rose');
check('the accent colour is read', elite_theme() === 'rose');
set_for_test('elite_theme', 'bogus');
check('an unknown colour falls back to gold', elite_theme() === 'gold');
set_for_test('elite_hero_image', '');
check('no banner picture by default', elite_hero_image_url() === '');
set_for_test('elite_hero_image', 'seo/banner.jpg');
check('the banner picture becomes a URL', strpos(elite_hero_image_url(), 'seo/banner.jpg') !== false);

echo "Elite search, numbers and shortlist\n";
act_as($owner);
$okp = elite_save_profile($owner, $good);
[$ok] = elite_admin_action($owner, 'approve');
$p = elite_profile($owner);
act_as($seeker);
$ownerName = first_name_of((string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $owner], ''));
$ids500 = fn(array $f) => array_map('intval', array_column(elite_search($seeker, $f, 500)['rows'], 'user_id'));
check('search by first name finds the member', in_array($owner, $ids500(['q' => $ownerName]), true));
check('search by headline finds the member', in_array($owner, $ids500(['q' => 'road trips']), true));
check('search does not read the about text', !in_array($owner, $ids500(['q' => 'logistics']), true));
check('nonsense finds nobody', $ids500(['q' => 'zzzzqqxx']) === []);
check('a percent sign is not a wildcard', $ids500(['q' => '%']) === []);
check('newest and recently active keep the same people', count($ids500(['sort' => 'new'])) === count($ids500([])) && count($ids500(['sort' => 'active'])) === count($ids500([])));
check('"active this week" never adds people', count($ids500(['active' => true])) <= count($ids500([])));
$stats = elite_public_stats();
check('public numbers match the search', $stats['members'] === (int)elite_search(0, [], 1)['total'] && $stats['verified'] <= $stats['members']);
set_for_test('elite_hero_stats', '0');
check('numbers can be switched off', elite_public_stats()['show'] === false);
set_for_test('elite_hero_cards', '0');
check('the banner cards can be switched off', elite_hero_cards($seeker) === []);
set_for_test('elite_hero_cards', '1');
set_for_test('elite_guest_preview', '0');
check('visitors get no banner cards when previews are off', elite_hero_cards(0) === []);
set_for_test('elite_guest_preview', '1');
$hc = elite_hero_cards($seeker);
check('the banner shows nothing or at least two cards', $hc === [] || count($hc) >= 2);

set_for_test('elite_saves_enabled', '1');
check('saving is on', elite_saves_enabled());
q('DELETE FROM elite_saves WHERE user_id = :u', ['u' => $seeker]);
[$ok, $saved] = elite_save_toggle($seeker, $owner);
check('a heart saves the profile', $ok && $saved && elite_saved_ids($seeker) === [$owner]);
[$ok, $saved] = elite_save_toggle($seeker, $owner, true);
check('saving twice keeps one row', $ok && $saved && (int)val('SELECT COUNT(*) FROM elite_saves WHERE user_id = :u', ['u' => $seeker], 0) === 1);
check('the owner only learns how many saved them', elite_saves_of($owner) === 1 && elite_owner_insights($owner)['saves'] === 1);
check('the saved list is the search with saved_by', $ids500(['saved_by' => $seeker]) === [$owner] && elite_saved_count($seeker) === 1);
$crow = elite_search($seeker, ['saved_by' => $seeker], 5)['rows'][0];
check('the card knows it is saved', elite_card($crow, $seeker, elite_saved_ids($seeker))['saved'] === true && elite_card($crow, $seeker, [])['saved'] === false && elite_card($crow, $seeker)['saved'] === false);
check('members cannot save themselves', elite_save_toggle($owner, $owner)[0] === false);
check('profiles that are not live cannot be saved', elite_save_toggle($seeker, $other)[0] === false);
insert('blocks', ['blocker_id' => $owner, 'blocked_id' => $other]);
q('DELETE FROM blocks WHERE blocker_id = :a AND blocked_id = :b', ['a' => $owner, 'b' => $other]);
insert('blocks', ['blocker_id' => $owner, 'blocked_id' => $seeker]);
check('a blocked member drops out of the saved list', $ids500(['saved_by' => $seeker]) === []);
q('DELETE FROM blocks WHERE blocker_id = :a AND blocked_id = :b', ['a' => $owner, 'b' => $seeker]);
check('and comes back when the block ends', $ids500(['saved_by' => $seeker]) === [$owner]);
elite_admin_action($owner, 'suspend', 'test');
check('a suspended profile leaves the saved list', $ids500(['saved_by' => $seeker]) === []);
elite_admin_action($owner, 'restore');
[$ok, $saved] = elite_save_toggle($seeker, $owner);
check('a heart again removes it', $ok && $saved === false && elite_saved_ids($seeker) === []);
elite_save_toggle($seeker, $owner, true);
elite_admin_action($owner, 'remove');
check('removing a profile clears its saves', (int)val('SELECT COUNT(*) FROM elite_saves WHERE owner_id = :o', ['o' => $owner], 0) === 0);
set_for_test('elite_saves_enabled', '0');
check('switched off: nobody can save and the list is empty', !elite_saves_enabled() && elite_save_toggle($seeker, $owner)[0] === false);
