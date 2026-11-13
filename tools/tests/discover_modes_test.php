<?php
/**
 * What Discover shows for the experience a member chose, and where each choice card is allowed to appear.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/discover_modes_test.php
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
    set_for_test('choice_enabled', '1');
    set_for_test('hosts_enabled', '1');
    set_for_test('host_discover_enabled', '1');
    set_for_test('host_discover_position', 'after_picks');
    set_for_test('daily_picks_enabled', '1');
    set_for_test('discover_mode_switch', '1');
    set_for_test('discover_mode_filters', '1');
    set_for_test('discover_elite_enabled', '1');
    set_for_test('elite_enabled', '1');
    set_for_test('choice_exclusive', '0'); // these checks are the arrangement rules; tools/tests/experience_test.php covers the exclusive ones

    echo "\nWhere each section sits\n";
    $admin = discover_layout('all');
    check('without a choice the admin\'s own order is used', $admin['agents'] === 'after_picks' && $admin['lead'] === 'people');
    check('so is it for someone who never chose', discover_layout('')['agents'] === 'after_picks');
    $dating = discover_layout('dating');
    check('Dating puts people first and Hosts at the bottom', $dating['agents'] === 'bottom' && $dating['people'] && $dating['lead'] === 'people');
    $fun = discover_layout('fun');
    check('Fun leads with the Hosts row', $fun['agents'] === 'top' && $fun['lead'] === 'agents');
    check('and keeps the people below it', $fun['people']);
    check('Fun puts the Elite row away', $fun['elite'] === 'off');
    $elite = discover_layout('elite');
    check('Elite leads with the Elite row', $elite['elite'] === 'top' && $elite['lead'] === 'elite' && $elite['agents'] === 'bottom');
    check('Friends is arranged like Dating', discover_layout('friends')['agents'] === 'bottom');

    set_for_test('discover_mode_filters', '0');
    check('switching the arrangement off brings the admin\'s order back for every choice', discover_layout('fun')['agents'] === 'after_picks' && discover_layout('dating')['agents'] === 'after_picks');
    set_for_test('discover_mode_filters', '1');

    set_for_test('host_discover_enabled', '0');
    check('no Hosts row while the admin switched it off, whatever the choice', discover_layout('fun')['agents'] === 'off' && discover_layout('dating')['agents'] === 'off');
    set_for_test('host_discover_enabled', '1');
    set_for_test('discover_elite_enabled', '0');
    check('no Elite row while that is off', discover_layout('elite')['elite'] === 'off' && discover_elite_rows(0) === []);
    set_for_test('discover_elite_enabled', '1');
    set_for_test('daily_picks_enabled', '0');
    check('no picks while daily picks are off', discover_layout('dating')['picks'] === false);
    set_for_test('daily_picks_enabled', '1');

    echo "\nHow many each section shows\n";
    set_for_test('daily_picks_count', '12');
    set_for_test('host_discover_count', '30');
    set_for_test('discover_elite_count', '14');
    set_for_test('discover_batch_size', '40');
    $n = discover_counts();
    check('the admin\'s numbers are used', $n['picks'] === 12 && $n['agents'] === 30 && $n['elite'] === 14 && $n['people'] === 40);
    set_for_test('host_discover_count', '999');
    set_for_test('discover_batch_size', '1');
    $n = discover_counts();
    check('a silly number is pulled back to the allowed range', $n['agents'] === 40 && $n['people'] === 5);

    echo "\nThe heading over the people\n";
    check('each choice gets its own heading', discover_people_title('dating') === 'People to meet' && discover_people_title('friends') === 'People to get to know' && discover_people_title('') === 'People near you');

    echo "\nWhere a choice card appears\n";
    $saved = (string)setting('choice_cards');
    $base = [];
    foreach (choice_defaults() as $k => $d) {
        $base[$k] = ['enabled' => 1, 'in_picker' => 1, 'in_switch' => 1, 'order' => $d['order'], 'label' => $d['label'] ?: ucfirst($k), 'tagline' => $d['tagline'], 'desc' => $d['desc'],
            'points' => implode("\n", $d['points']), 'accent' => $d['accent'], 'icon' => $d['icon'], 'image' => '', 'go' => '', 'hero_h' => $d['hero_h'], 'hero_s' => $d['hero_s'],
            'cta_text' => $d['cta_text'], 'cta_url' => $d['cta_url'], 'lead' => [], 'hide' => []];
    }
    $base['friends']['enabled'] = 0;
    [$ok] = choice_cards_save($base);
    check('the cards save', $ok);
    check('every live card is in both places to start with', array_keys(choice_cards('picker')) === array_keys(choice_cards('switch')));

    // Elite out of the opening question, still in the menu: exactly what the admin asked for
    $base['elite']['in_picker'] = 0;
    [$ok] = choice_cards_save($base);
    check('the change saves', $ok);
    check('Elite leaves the question visitors are asked', !isset(choice_cards('picker')['elite']));
    check('but stays in the switch and the menu', isset(choice_cards('switch')['elite']));
    check('and is still a choice someone may already have', isset(choice_cards('any')['elite']));

    $base['fun']['in_switch'] = 0;
    [$ok] = choice_cards_save($base);
    check('a card can be in the question only', isset(choice_cards('picker')['fun']) && !isset(choice_cards('switch')['fun']));

    $base['elite']['enabled'] = 0;
    [$ok] = choice_cards_save($base);
    check('switching a card off removes it from everywhere', !isset(choice_cards('any')['elite']) && !isset(choice_cards('switch')['elite']));

    $allOff = $base;
    foreach ($allOff as $k => $v) { $allOff[$k]['enabled'] = 0; }
    [$ok, $err] = choice_cards_save($allOff);
    check('at least one card must stay on', !$ok && $err !== '');

    setting_set('choice_cards', $saved);
} finally {
    foreach ($touched as $k => $v) { $v === null ? setting_reset($k) : setting_set($k, $v); }
}
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
