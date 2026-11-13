<?php
/**
 * Defaults for visitors who have not chosen: the site arranges itself for Dating, and Discover opens on the swipe cards.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/default_mode_test.php
 * The choice is read once per request, so each kind of visitor runs in its own process (this file calls itself).
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line.\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }

$phase = '';
foreach ($argv as $a) { if (str_starts_with($a, '--phase=')) { $phase = substr($a, 8); } }
if ($phase === '') {
    $fail = 0; $pass = 0;
    foreach (['none', 'fun', 'all', 'view'] as $p) {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --yes --phase=' . $p . ' 2>&1';
        $out = (string)shell_exec($cmd);
        echo $out;
        if (preg_match('/(\d+) passed, (\d+) failed/', $out, $m)) { $pass += (int)$m[1]; $fail += (int)$m[2]; } else { $fail++; echo "  FAIL  phase $p did not finish\n"; }
    }
    echo "\nALL PHASES: $pass passed, $fail failed\n";
    exit($fail > 0 ? 1 : 0);
}

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$pass = 0; $fail = 0;
function check(string $n, bool $ok, string $d = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  ok    ' : '  FAIL  ') . $n . (!$ok && $d !== '' ? "  ($d)" : '') . "\n"; }

/** A member to test with: the first real one, or a throw-away one when the database has none (a fresh install). Returns [id, created]. */
function test_member(): array
{
    $uid = (int)val("SELECT u.id FROM users u JOIN user_profiles p ON p.user_id = u.id WHERE u.role = 'user' AND u.status = 'active' ORDER BY u.id LIMIT 1");
    if ($uid > 0) {
        return [$uid, false];
    }
    q("INSERT INTO users (email, password_hash, role, status, email_verified_at) VALUES ('zz-test-member@example.invalid', 'x', 'user', 'active', NOW())");
    $uid = (int)val("SELECT id FROM users WHERE email = 'zz-test-member@example.invalid'");
    q('INSERT INTO user_profiles (user_id, display_name, onboarding_completed) VALUES (:u, \'Test Member\', 1)', ['u' => $uid]);
    return [$uid, true];
}
function test_member_remove(int $uid): void
{
    foreach (['discovery_preferences', 'user_profiles'] as $t) {
        q("DELETE FROM `$t` WHERE user_id = :u", ['u' => $uid]);
    }
    q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
}
$touched = [];
function set_for_test(string $k, string $v): void { global $touched; if (!array_key_exists($k, $touched)) { $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $k]); $touched[$k] = $r ? $r['setting_value'] : null; } setting_set($k, $v); cache_clear(); }

try {
    set_for_test('hosts_enabled', '1');
    set_for_test('choice_enabled', '1');
    set_for_test('choice_exclusive', '1');
    set_for_test('choice_default', 'dating');
    unset($_COOKIE[CHOICE_COOKIE]);
    if ($phase === 'fun') { $_COOKIE[CHOICE_COOKIE] = 'fun'; }
    if ($phase === 'all') { $_COOKIE[CHOICE_COOKIE] = 'all'; }

    if ($phase === 'none') {
        echo "\nA visitor who has not chosen\n";
        check('the site has a dating card to fall back on', isset(choice_cards()['dating']));
        check('they have not chosen', choice_current() === '');
        check('but the site is arranged for Dating', choice_effective() === 'dating');
        check('the active card is Dating', (choice_active_card()['key'] ?? '') === 'dating');
        check('the hero speaks for Dating', choice_hero()['h'] === choice_cards()['dating']['hero_h'] && choice_hero()['h'] !== '');
        check('Discover is arranged for Dating', discover_mode() === 'dating');
        check('and Dating is the people side only', experience_modules() === ['dating']);
        $keys = array_column(choice_landing_sections(landing_sections()), 'section_key');
        check('the home page leads with the people', in_array('profiles', $keys, true));
        check('and holds back the Host and Elite blocks', !in_array('agents_top', $keys, true) && !in_array('elite', $keys, true));
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 14) Chrome/120 Mobile Safari/537.36';
        check('the picker still opens for them, so they can choose', count(choice_cards('picker')) >= 2 && choice_should_open());
        $_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';
        check('and search engines are shown the same Dating home page, not a different one', choice_effective() === 'dating' && !choice_should_open());

        set_for_test('choice_default', 'all');
        check('with the default set to Everything nothing is arranged', choice_effective() === '' && experience_modules() === null);
        set_for_test('choice_default', 'fun');
        check('the admin can make Fun the default', choice_effective() === 'fun' && experience_modules() === ['concierge']);
        set_for_test('choice_default', 'nonsense');
        check('a bad value falls back to nothing arranged', choice_effective() === '');
        set_for_test('choice_default', 'dating');
        $cards = json_decode((string)setting('choice_cards'), true);
        check('the default is declared in settings', isset(settings_schema()['fields']['choice_default']) && settings_schema()['fields']['choice_default']['default'] === 'dating');
    }
    if ($phase === 'fun') {
        echo "\nA visitor who chose Fun\n";
        check('their choice wins over the default', choice_effective() === 'fun' && choice_current() === 'fun');
        check('and they see the Host side', experience_modules() === ['concierge']);
    }
    if ($phase === 'all') {
        echo "\nA visitor who chose Everything\n";
        check('Everything stays Everything', choice_effective() === 'all' && experience_modules() === null);
        $keys = array_column(choice_landing_sections(landing_sections()), 'section_key');
        check('and the home page keeps every block', in_array('profiles', $keys, true) && (in_array('agents_top', $keys, true) || !hosts_enabled()));
    }
    if ($phase === 'view') {
        echo "\nDiscover opens on the swipe cards\n";
        [$uid, $madeMember] = test_member();
        check('there is a member to test with', $uid > 0);
        $saved = row('SELECT preferred_view, view_set_at FROM discovery_preferences WHERE user_id = :u', ['u' => $uid]);
        explorer_prefs($uid);
        set_for_test('discover_default_view', 'swipe');
        set_for_test('discover_allow_view_switch', '1');
        unset($_COOKIE[EXPLORER_VIEW_COOKIE], $_GET['view']);

        update('discovery_preferences', ['preferred_view' => null, 'view_set_at' => null], ['user_id' => $uid]);
        check('a member who never chose gets swipe', explorer_view_mode($uid) === 'swipe');
        update('discovery_preferences', ['preferred_view' => 'grid', 'view_set_at' => null], ['user_id' => $uid]);
        check('a grid saved before this update is kept but not applied', explorer_view_mode($uid) === 'swipe'
            && (string)val('SELECT preferred_view FROM discovery_preferences WHERE user_id = :u', ['u' => $uid]) === 'grid');
        $_COOKIE['chx_view'] = 'list';
        check('an old device cookie is ignored too', explorer_view_mode($uid) === 'swipe');
        unset($_COOKIE['chx_view']);

        explorer_view_remember($uid, 'grid');
        check('choosing Grid sticks', explorer_view_mode($uid) === 'grid');
        check('and records when they chose', (string)val('SELECT view_set_at FROM discovery_preferences WHERE user_id = :u', ['u' => $uid]) !== '');
        $_GET['view'] = 'list';
        check('a link with a view in it shows that view once', explorer_view_mode($uid) === 'list');
        unset($_GET['view']);
        explorer_view_remember($uid, 'swipe');
        check('and they can go back to Swipe', explorer_view_mode($uid) === 'swipe');
        explorer_view_remember($uid, 'nonsense');
        check('an unknown view is ignored', explorer_view_mode($uid) === 'swipe');

        set_for_test('discover_default_view', 'grid');
        update('discovery_preferences', ['preferred_view' => null, 'view_set_at' => null], ['user_id' => $uid]);
        unset($_COOKIE[EXPLORER_VIEW_COOKIE]);
        check('the admin can still pick another default for everyone', explorer_view_mode($uid) === 'grid');
        set_for_test('discover_allow_view_switch', '0');
        update('discovery_preferences', ['preferred_view' => 'list', 'view_set_at' => date('Y-m-d H:i:s')], ['user_id' => $uid]);
        check('with switching off, the site default rules', explorer_view_mode($uid) === 'grid');

        update('discovery_preferences', ['preferred_view' => $saved['preferred_view'] ?? null, 'view_set_at' => $saved['view_set_at'] ?? null], ['user_id' => $uid]);
        if ($madeMember) { test_member_remove($uid); }
    }
} finally {
    foreach ($touched as $k => $v) { $v === null ? setting_reset($k) : setting_set($k, $v); }
    cache_clear();
}
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
