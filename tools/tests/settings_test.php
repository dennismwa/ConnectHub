<?php
/** Settings schema integrity and the "changed from the default" marker. Run: CH_CONFIG=... php tools/tests/settings_test.php (throw-away database). */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$pass = 0; $fail = 0;
function check(string $n, bool $ok): void { global $pass, $fail; if ($ok) { $pass++; echo "  ok    $n\n"; } else { $fail++; echo "  FAIL  $n\n"; } }

$schema = settings_schema();
$bad = [];
foreach ($schema['tabs'] as $k => $t) {
    foreach (['label', 'icon', 'desc'] as $need) { if (empty($t[$need])) { $bad[] = "$k:$need"; } }
}
check('every settings tab has a label, an icon and a description' . ($bad ? ' (' . implode(', ', $bad) . ')' : ''), !$bad);
$orphans = array_filter($schema['fields'], fn($d) => !isset($schema['tabs'][$d['tab'] ?? '']));
check('every setting sits under a registered tab', !$orphans);
$ids = [];
foreach ($schema['tabs'] as $t) { foreach ($t['sections'] as $s) { foreach ($s['keys'] as $key) { $ids[$key] = ($ids[$key] ?? 0) + 1; } } }
check('no setting is listed in two sections', !array_filter($ids, fn($n) => $n > 1));

$key = 'promo_max_shown';
$def = $schema['fields'][$key];
$old = q('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $key])->fetchColumn();
try {
    setting_reset($key);
    check('no saved row counts as default', setting_is_default($key));
    setting_set($key, (string)$def['default']);
    check('a saved row that holds the built-in value still counts as default', setting_is_default($key));
    setting_set($key, (string)((int)$def['default'] + 1));
    check('a different saved value counts as changed', !setting_is_default($key));
    $rules = 'promo_rules';
    $dr = (string)$schema['fields'][$rules]['default'];
    setting_set($rules, str_replace("\n", "\r\n", $dr));
    check('line-ending differences alone do not count as a change', setting_is_default($rules));
    setting_set($rules, $dr . ' Extra rule.');
    check('edited text counts as changed', !setting_is_default($rules));
    setting_reset($rules);
} finally {
    if ($old === false) { setting_reset($key); } else { setting_set($key, (string)$old); }
}
echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
