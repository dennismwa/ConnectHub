<?php
/** International path, overview and setup sentence. Run: CH_CONFIG=... php tools/tests/intl_ui_test.php (throw-away database). */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$pass = 0; $fail = 0;
function check(string $n, bool $ok): void { global $pass, $fail; if ($ok) { $pass++; echo "  ok    $n\n"; } else { $fail++; echo "  FAIL  $n\n"; } }
$keys = ['intl_enabled', 'intl_mode', 'intl_join_fee', 'intl_join_days', 'intl_access_fee', 'intl_access_days', 'intl_premium_free', 'intl_msg_included', 'intl_approval'];
$old = [];
foreach ($keys as $k) { $old[$k] = (string)setting($k); }
$uid = (int)val("SELECT u.id FROM users u WHERE u.role = 'user' AND u.status = 'active' AND NOT EXISTS (SELECT 1 FROM intl_profiles i WHERE i.user_id = u.id) AND NOT EXISTS (SELECT 1 FROM intl_access a WHERE a.user_id = u.id) ORDER BY u.id LIMIT 1", [], 0);
try {
    setting_set('intl_enabled', '1'); setting_set('intl_premium_free', 'none'); setting_set('intl_approval', '1');
    setting_set('intl_mode', 'free');
    check('free mode sentence', strpos(intl_setup_sentence(), 'Listing is free') === 0 && strpos(intl_setup_sentence(), 'browsing and messaging are free') !== false);
    setting_set('intl_mode', 'both'); setting_set('intl_join_fee', '300'); setting_set('intl_access_fee', '700'); setting_set('intl_join_days', '30'); setting_set('intl_access_days', '0');
    $s = intl_setup_sentence();
    check('both mode sentence names both prices', strpos($s, '300') !== false && strpos($s, '700') !== false && strpos($s, 'one time') !== false);
    setting_set('intl_premium_free', 'vip');
    check('sentence mentions included plans', strpos(intl_setup_sentence(), 'VIP members pay nothing') !== false);
    setting_set('intl_enabled', '0');
    check('sentence when off', strpos(intl_setup_sentence(), 'switched off') !== false);
    setting_set('intl_enabled', '1');

    check('test member found', $uid > 0);
    $j = intl_journey($uid);
    check('no profile: step 1 current with a create button', $j['steps'][0]['state'] === 'current' && $j['steps'][0]['action'][0] === 'Create profile');
    check('no profile: later steps wait', $j['steps'][1]['state'] === 'todo' && !$j['done']);
    check('paid access not bought: step 3 offers unlock', $j['steps'][2]['state'] === 'current' && $j['steps'][2]['action'][2] === 'buy');

    $now = date('Y-m-d H:i:s');
    q("INSERT INTO intl_profiles (user_id, status, country, languages, purposes, headline) VALUES (:u, 'pending', 'KE', 'en', 'friendship', 'Test line for the path')", ['u' => $uid]);
    $j = intl_journey($uid);
    check('pending profile: step 2 says waiting, no button', $j['steps'][0]['state'] === 'done' && $j['steps'][1]['state'] === 'current' && empty($j['steps'][1]['action']));
    q("UPDATE intl_profiles SET status = 'rejected', admin_note = 'Add a clear photo' WHERE user_id = :u", ['u' => $uid]);
    $j = intl_journey($uid);
    check('rejected profile: shows the note and an edit button', strpos($j['steps'][1]['text'], 'Add a clear photo') !== false && $j['steps'][1]['action'][0] === 'Edit profile');
    q("UPDATE intl_profiles SET status = 'active', admin_note = NULL, approved_at = NOW() WHERE user_id = :u", ['u' => $uid]);
    $j = intl_journey($uid);
    check('approved but listing unpaid: step 2 offers go live', $j['steps'][1]['state'] === 'current' && $j['steps'][1]['action'][2] === 'buy');
    q("UPDATE intl_profiles SET listed_until = '9999-12-31 00:00:00' WHERE user_id = :u", ['u' => $uid]);
    intl_grant_access($uid, 0, 'admin');
    $j = intl_journey($uid);
    check('listed with access: everything done', $j['done'] === true);
    setting_set('intl_mode', 'free');
    q("UPDATE intl_profiles SET listed_until = NULL WHERE user_id = :u", ['u' => $uid]);
    $j = intl_journey($uid);
    check('free mode: live and done without paying', $j['done'] === true);

    $ov = intl_admin_overview();
    check('overview returns counts, queue and money', isset($ov['counts'], $ov['queue'], $ov['month'], $ov['recent'], $ov['stats']) && is_float($ov['month']));
    $cards = intl_cards($uid + 100000, ['sort' => 'active'], 5, 0);
    check('cards accept the active sort and add message links', is_array($cards['rows']) && ($cards['rows'] === [] || strpos($cards['rows'][0]['message_href'], '/messages/with/') !== false));
    $cards = intl_cards($uid + 100000, ['sort' => 'nonsense'], 5, 0);
    check('unknown sort falls back safely', is_array($cards['rows']));
} finally {
    q('DELETE FROM intl_profiles WHERE user_id = :u', ['u' => $uid]);
    q('DELETE FROM intl_access WHERE user_id = :u', ['u' => $uid]);
    foreach ($old as $k => $v) { setting_set($k, $v); }
    cache_clear();
}
echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
