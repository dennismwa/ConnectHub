<?php
/**
 * The 4-digit passcode a member can put on their own account.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/passcode_test.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line.\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0; $fail = 0;
function check(string $n, bool $ok, string $d = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  ok    ' : '  FAIL  ') . $n . (!$ok && $d !== '' ? "  ($d)" : '') . "\n"; }
$touched = [];
function set_for_test(string $k, string $v): void { global $touched; if (!array_key_exists($k, $touched)) { $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $k]); $touched[$k] = $r ? (string)$r['setting_value'] : null; } setting_set($k, $v); }

$pw = 'Passcode#Test123';
$uid = create_account(['email' => 'passcode-' . bin2hex(random_bytes(3)) . '@example.test', 'password' => $pw, 'display_name' => 'Pass Tester']);
$_SESSION = [];

try {
    set_for_test('passcode_enabled', '1');
    set_for_test('passcode_idle_minutes', '30');

    echo "\nWhat makes a passcode\n";
    check('four numbers are fine', passcode_problem('2846') === null && passcode_problem('9073') === null);
    check('three or five are not', passcode_problem('284') !== null && passcode_problem('28465') !== null);
    check('letters are not', passcode_problem('28a6') !== null && passcode_problem('') !== null);
    check('the same number four times is refused', passcode_problem('1111') !== null && passcode_problem('7777') !== null);
    check('the ones everybody picks are refused', passcode_problem('1234') !== null && passcode_problem('0000') !== null && passcode_problem('2580') !== null);

    echo "\nSetting, checking and removing\n";
    check('nobody has one to start with', !passcode_is_set($uid));
    check('a weak one is not saved', passcode_set($uid, '1234') !== null && !passcode_is_set($uid));
    check('a good one saves', passcode_set($uid, '2846') === null && passcode_is_set($uid));
    check('it is never stored in the clear', strpos((string)val('SELECT passcode_hash FROM user_profiles WHERE user_id = :u', ['u' => $uid], ''), '2846') === false);
    check('the right code is accepted', passcode_verify($uid, '2846'));
    check('a wrong code is not', !passcode_verify($uid, '2847') && !passcode_verify($uid, '') && !passcode_verify($uid, '28460'));
    check('saving one also unlocks this session', passcode_unlocked());
    check('changing it works', passcode_set($uid, '9073') === null && passcode_verify($uid, '9073') && !passcode_verify($uid, '2846'));
    passcode_clear($uid);
    check('removing it clears everything', !passcode_is_set($uid) && !passcode_verify($uid, '9073') && !passcode_unlocked());

    echo "\nWhen the app asks for it\n";
    passcode_set($uid, '2846');
    $me = ['id' => $uid, 'role' => 'user', 'tier' => 'free'];
    passcode_lock_now();
    check('a locked app asks on an ordinary page', passcode_required($me, '/discover'));
    check('the lock screen itself stays open', !passcode_required($me, '/lock'));
    check('so does signing out and the unlock call', !passcode_required($me, '/logout') && !passcode_required($me, '/api/passcode/unlock'));
    passcode_unlock();
    check('once unlocked it stops asking', !passcode_required($me, '/discover'));
    $_SESSION['_pc_at'] = time() - 31 * 60;
    check('it asks again after the quiet time', passcode_required($me, '/discover'));
    set_for_test('passcode_idle_minutes', '0');
    $_SESSION['_pc_at'] = time() - 86400;
    check('with no quiet time it only asks when the app is opened fresh', !passcode_required($me, '/discover'));
    set_for_test('passcode_idle_minutes', '30');
    passcode_unlock();
    check('a visitor is never asked', !passcode_required(null, '/discover'));
    check('an admin is never asked', !passcode_required(['id' => $uid, 'role' => 'admin', 'tier' => 'vip'], '/admin'));
    set_for_test('passcode_enabled', '0');
    passcode_lock_now();
    check('the admin can switch the whole thing off', !passcode_required($me, '/discover'));
    set_for_test('passcode_enabled', '1');

    echo "\nWrong tries\n";
    unset($_SESSION['_pc_tries']);
    check('five tries to start with', passcode_tries_left() === PASSCODE_MAX_TRIES);
    for ($i = 1; $i <= 4; $i++) { passcode_wrong_try(); }
    check('four wrong leaves one', passcode_tries_left() === 1);
    check('the fifth leaves none', passcode_wrong_try() === 0);
    passcode_unlock();
    check('getting in resets the count', passcode_tries_left() === PASSCODE_MAX_TRIES);

    echo "\nThe settings form\n";
    $r = passcode_handle_post($uid, ['action' => 'passcode_save', 'passcode' => '5162', 'passcode_confirm' => '5162', 'password' => 'not-my-password']);
    check('a wrong account password changes nothing', !$r['ok'] && passcode_verify($uid, '2846'));
    $r = passcode_handle_post($uid, ['action' => 'passcode_save', 'passcode' => '5162', 'passcode_confirm' => '5163', 'password' => $pw]);
    check('two codes that differ are refused', !$r['ok'] && isset($r['errors']['passcode_confirm']));
    $r = passcode_handle_post($uid, ['action' => 'passcode_save', 'passcode' => '1111', 'passcode_confirm' => '1111', 'password' => $pw]);
    check('a weak code is refused with a reason', !$r['ok'] && isset($r['errors']['passcode']));
    $r = passcode_handle_post($uid, ['action' => 'passcode_save', 'passcode' => '5162', 'passcode_confirm' => '5162', 'password' => $pw]);
    check('a good change goes through', $r['ok'] && passcode_verify($uid, '5162'));
    $r = passcode_handle_post($uid, ['action' => 'passcode_remove', 'password' => 'wrong']);
    check('removing needs the account password too', !$r['ok'] && passcode_is_set($uid));
    $r = passcode_handle_post($uid, ['action' => 'passcode_remove', 'password' => $pw]);
    check('and then it is gone', $r['ok'] && !passcode_is_set($uid));
} finally {
    q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
    foreach ($touched as $k => $v) { $v === null ? setting_reset($k) : setting_set($k, $v); }
}
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
