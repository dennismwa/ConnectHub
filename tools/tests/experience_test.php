<?php
/**
 * "Show only what they chose": which modules each experience includes, how Discover is laid out for it,
 * and which home page blocks it keeps.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/experience_test.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line.\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0; $fail = 0;
function check(string $n, bool $ok, string $d = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  ok    ' : '  FAIL  ') . $n . (!$ok && $d !== '' ? "  ($d)" : '') . "\n"; }
$touched = [];
function set_for_test(string $k, string $v): void { global $touched; if (!array_key_exists($k, $touched)) { $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $k]); $touched[$k] = $r ? (string)$r['setting_value'] : null; } setting_set($k, $v); }

try {
    set_for_test('hosts_enabled', '1');
    set_for_test('host_discover_enabled', '1');
    set_for_test('discover_mode_filters', '1');
    set_for_test('daily_picks_enabled', '1');
    set_for_test('choice_exclusive', '1');
    $eliteOn = function_exists('elite_enabled') && elite_enabled();

    echo "\nWhat each experience includes\n";
    check('dating is the people side only', experience_modules('dating') === ['dating']);
    check('friends uses the people side', experience_modules('friends') === ['dating']);
    check('fun is the Host side only', experience_modules('fun') === ['concierge']);
    check('elite is its own side', experience_modules('elite') === ['elite']);
    check('nothing chosen, or "everything", includes everything', experience_modules('') === null && experience_modules('all') === null);
    check('an unknown name falls back to everything', experience_modules('nonsense') === null);
    check('allows follows the map', experience_allows('dating', 'dating') && !experience_allows('concierge', 'dating') && !experience_allows('elite', 'dating')
        && experience_allows('concierge', 'fun') && !experience_allows('dating', 'fun') && experience_allows('elite', 'elite') && !experience_allows('concierge', 'elite'));

    echo "\nHome page blocks\n";
    check('a Dating visitor loses every Host and Elite block', !experience_allows_section('agents_top', 'dating') && !experience_allows_section('agents_new', 'dating') && !experience_allows_section('hosts', 'dating') && !experience_allows_section('elite', 'dating'));
    check('and keeps the people blocks', experience_allows_section('profiles', 'dating') && experience_allows_section('locations', 'dating'));
    check('a Fun visitor loses the people blocks and Elite', !experience_allows_section('profiles', 'fun') && !experience_allows_section('locations', 'fun') && !experience_allows_section('elite', 'fun') && !experience_allows_section('international', 'fun'));
    check('and keeps the Host blocks', experience_allows_section('agents_top', 'fun') && experience_allows_section('hosts', 'fun'));
    check('an Elite visitor sees only the Elite block of the three', experience_allows_section('elite', 'elite') && !experience_allows_section('profiles', 'elite') && !experience_allows_section('agents_top', 'elite'));
    check('blocks that belong to no side show for everyone', experience_allows_section('faq', 'fun') && experience_allows_section('safety', 'elite') && experience_allows_section('how_it_works', 'dating') && experience_allows_section('premium', 'fun'));
    check('everything shows when nothing is chosen', experience_allows_section('agents_top', '') && experience_allows_section('elite', 'all'));

    echo "\nDiscover for each experience\n";
    $d = discover_layout('dating');
    check('Dating: people and picks, no Hosts, no Elite, no Host stories', $d['people'] && $d['picks'] && $d['agents'] === 'off' && $d['elite'] === 'off' && !$d['stories'] && !$d['focus'] && $d['toolbar'] && $d['nudges']);
    $d = discover_layout('friends');
    check('Friends is the same people side', $d['people'] && $d['agents'] === 'off' && $d['elite'] === 'off' && !$d['focus']);
    $d = discover_layout('fun');
    check('Fun: the Host grid is the page and nothing else is', !$d['people'] && !$d['picks'] && $d['agents'] === 'top' && $d['elite'] === 'off' && $d['focus'] && $d['stories'] && !$d['toolbar'] && !$d['nudges']);
    set_for_test('host_discover_enabled', '0');
    check('Fun still shows the Hosts when the Discover row is switched off', discover_layout('fun')['agents'] === 'top');
    set_for_test('host_discover_enabled', '1');
    set_for_test('hosts_enabled', '0');
    check('but not when the Host module itself is off', discover_layout('fun')['agents'] === 'off');
    set_for_test('hosts_enabled', '1');
    if ($eliteOn) {
        $d = discover_layout('elite');
        check('Elite: its own grid is the page', $d['elite'] === 'top' && $d['agents'] === 'off' && !$d['people'] && !$d['picks'] && $d['focus'] && !$d['stories']);
    }

    echo "\nWith the switch off\n";
    set_for_test('choice_exclusive', '0');
    check('no experience is exclusive any more', experience_modules('dating') === null && experience_modules('fun') === null && experience_allows_section('agents_top', 'dating'));
    $d = discover_layout('dating');
    check('Dating still has the Host row, below the people', $d['agents'] === 'bottom' && $d['people'] && !$d['focus'] && $d['stories'] && $d['toolbar']);
    $d = discover_layout('fun');
    check('Fun leads with the Hosts but the people stay', $d['agents'] === 'top' && $d['people'] && !$d['focus']);
    set_for_test('discover_mode_filters', '0');
    check('and with the order switch off as well, it is the admin\'s own order', discover_layout('fun')['people'] && discover_layout('fun')['agents'] === (string)setting('host_discover_position'));
    set_for_test('discover_mode_filters', '1');
    set_for_test('choice_exclusive', '1');

    echo "\nHow many\n";
    $n = discover_counts();
    check('the full-page grids hold plenty by default', $n['focus'] >= 6 && $n['focus'] <= 60);
    set_for_test('discover_focus_count', '40');
    check('and the admin can raise it', discover_counts()['focus'] === 40);
    set_for_test('discover_focus_count', '500');
    check('inside a sane limit', discover_counts()['focus'] === 60);

    echo "\nThe home page for a Fun visitor\n";
    $_COOKIE[CHOICE_COOKIE] = 'fun'; // the choice is read once per request, so the whole page below is one visitor
    $keys = array_column(choice_landing_sections(landing_sections()), 'section_key');
    check('Host blocks stay', in_array('agents_top', $keys, true));
    check('people and Elite blocks go', !in_array('profiles', $keys, true) && !in_array('locations', $keys, true) && !in_array('elite', $keys, true));
    check('the general blocks stay', in_array('faq', $keys, true) && in_array('how_it_works', $keys, true));
} finally {
    foreach ($touched as $k => $v) { $v === null ? setting_reset($k) : setting_set($k, $v); }
    cache_clear();
}
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
