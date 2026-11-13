<?php
/** Logo placements and fallbacks. Run: CH_CONFIG=... php tools/tests/logos_test.php (throw-away database). */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$pass = 0; $fail = 0;
function check(string $n, bool $ok): void { global $pass, $fail; if ($ok) { $pass++; echo "  ok    $n\n"; } else { $fail++; echo "  FAIL  $n\n"; } }
$keys = ['logo_full', 'logo_compact', 'logo_icon', 'logo_dark', 'logo_white', 'logo_mobile', 'logo_mobile_dark', 'logo_app_header', 'logo_app_header_dark', 'logo_sidepanel', 'logo_sidepanel_dark', 'logo_sidepanel_host', 'logo_sidepanel_host_dark', 'logo_menu', 'logo_menu_dark', 'logo_h_app_header', 'logo_w_menu'];
$old = [];
foreach ($keys as $k) { $old[$k] = (string)setting($k); setting_set($k, ''); }
try {
    check('no logo uploaded: the default mark shows in every new placement', strpos(logo_html('app_header'), 'ch-logo-default') !== false && strpos(logo_html('menu'), 'ch-logo-default') !== false && strpos(logo_html('sidepanel_host'), 'ch-logo-default') !== false);
    setting_set('logo_compact', 'site/compact.png');
    check('everything falls back to the compact logo', strpos(logo_html('menu'), 'site/compact.png') !== false && strpos(logo_html('sidepanel'), 'site/compact.png') !== false && strpos(logo_html('app_header'), 'site/compact.png') !== false);
    setting_set('logo_sidepanel', 'site/side.png');
    check('side panel logo is used for members', strpos(logo_html('sidepanel'), 'site/side.png') !== false);
    check('Hosts use the members side panel logo until they get their own', strpos(logo_html('sidepanel_host'), 'site/side.png') !== false);
    check('the phone menu also falls back to the side panel logo', strpos(logo_html('menu'), 'site/side.png') !== false);
    setting_set('logo_sidepanel_host', 'site/host.png');
    check('a Host side panel logo wins for Hosts only', strpos(logo_html('sidepanel_host'), 'site/host.png') !== false && strpos(logo_html('sidepanel'), 'site/host.png') === false);
    setting_set('logo_menu', 'site/menu.png');
    setting_set('logo_menu_dark', 'site/menu-dark.png');
    $m = logo_html('menu');
    check('phone menu logo has its own light and dark files', strpos($m, 'site/menu.png') !== false && strpos($m, 'site/menu-dark.png') !== false && strpos($m, 'ch-logo-darkmode') !== false);
    setting_set('logo_app_header', 'site/app.png');
    check('signed-in top bar logo wins over the mobile logo', strpos(logo_html('app_header'), 'site/app.png') !== false);
    setting_set('logo_h_app_header', '40');
    setting_set('logo_w_menu', '200');
    check('sizes come from the new size settings', strpos(logo_html('app_header'), 'height:40px') !== false && strpos(logo_html('menu'), 'width:200px') !== false);
} finally {
    foreach ($old as $k => $v) { setting_set($k, $v); }
}
echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
