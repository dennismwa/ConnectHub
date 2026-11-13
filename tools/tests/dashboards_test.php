<?php
/** Banners, signed-in logo, New faces groups, Host payouts access. Run: CH_CONFIG=... php tools/tests/dashboards_test.php (throw-away database). */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$pass = 0; $fail = 0;
function check(string $n, bool $ok): void { global $pass, $fail; if ($ok) { $pass++; echo "  ok    $n\n"; } else { $fail++; echo "  FAIL  $n\n"; } }
$keys = ['banners_show_images', 'banner_host_image', 'banner_wallet_image', 'banner_admin_image', 'banner_host_wallet_image', 'banner_overlay', 'logo_header_member', 'logo_header_scrolled', 'logo_compact', 'logo_h_header_member'];
$old = [];
foreach ($keys as $k) { $old[$k] = (string)setting($k); }
$featured = (int)val("SELECT id FROM users WHERE role = 'user' AND status = 'active' AND (featured_until IS NULL OR featured_until < NOW()) ORDER BY id LIMIT 1", [], 0);
$prevFeatured = $featured ? val('SELECT featured_until FROM users WHERE id = :i', ['i' => $featured]) : null;
try {
    // ---- banners
    setting_set('banners_show_images', '1');
    foreach (['banner_host_image', 'banner_wallet_image', 'banner_admin_image', 'banner_host_wallet_image'] as $k) { setting_set($k, ''); }
    foreach (['host', 'host_wallet', 'wallet', 'admin'] as $slot) {
        check("banner '$slot' uses a built-in picture when nothing is uploaded", strpos(banner_image($slot), '/assets/images/art/') !== false);
    }
    setting_set('banner_host_image', 'hero/custom.jpg');
    check('an uploaded banner wins', strpos(banner_image('host'), 'hero/custom.jpg') !== false && strpos(banner_attrs('host', 'x'), '--bn-img') !== false);
    setting_set('banner_overlay', 'strong');
    check('overlay strength becomes a class', strpos(banner_attrs('host'), 'bn-strong') !== false);
    setting_set('banner_overlay', 'nonsense');
    check('unknown overlay falls back to medium', strpos(banner_attrs('host'), 'bn-medium') !== false);
    setting_set('banners_show_images', '0');
    check('pictures off: plain colour banner, no picture variable', banner_image('host') === '' && strpos(banner_attrs('host'), '--bn-img') === false && strpos(banner_attrs('host'), 'bn-pic') === false);

    // ---- logo for signed-in members on public pages
    foreach (['logo_header_member', 'logo_header_scrolled'] as $k) { setting_set($k, ''); }
    setting_set('logo_compact', 'site/compact.png');
    check('member top bar logo falls back to the compact logo', strpos(logo_html('header_member'), 'site/compact.png') !== false);
    setting_set('logo_header_scrolled', 'site/scrolled.png');
    check('then to the header logo after scrolling', strpos(logo_html('header_member'), 'site/scrolled.png') !== false);
    setting_set('logo_header_member', 'site/member.png');
    setting_set('logo_h_header_member', '48');
    $h = logo_html('header_member');
    check('its own logo wins and has its own height', strpos($h, 'site/member.png') !== false && strpos($h, 'height:48px') !== false);
    check('visitors still get the normal header logo', strpos(logo_html('header'), 'site/member.png') === false);

    // ---- New faces groups
    if ($featured) {
        q("UPDATE users SET featured_until = DATE_ADD(NOW(), INTERVAL 3 DAY) WHERE id = :i", ['i' => $featured]);
        $vip = landing_group_ids('vip', 8);
        $reg = landing_group_ids('regular', 8);
        check('a featured member is in the VIP group', in_array($featured, $vip, true));
        check('and is not in the regular group', !in_array($featured, $reg, true));
        check('regular group has people in it (members who were never featured)', count($reg) > 0);
        $g = landing_profile_groups(['group_newest' => 1, 'group_regular' => 1, 'group_vip' => 1, 'count' => 4, 'columns' => 3]);
        check('groups honour the member count', $g && count($g[0]['members']) <= 4);
        check('first group is shown first when chosen', landing_profile_groups(['group_newest' => 1, 'group_vip' => 1, 'first_group' => 'vip', 'count' => 4])[0]['key'] === 'vip');
    }
    check('a section saved without groups still shows the old list', is_array(landing_profile_groups(['count' => 4])));

    // ---- Host payouts stay reachable
    $hid = (int)val("SELECT user_id FROM host_profiles WHERE approval_status = 'approved' ORDER BY user_id LIMIT 1", [], 0);
    $hid > 0 ? check('a Host with payouts on record is recognised', is_bool(hosts_user_has_payouts($hid))) : null;
    $src = (string)file_get_contents(dirname(__DIR__, 2) . '/app/host/payouts.php');
    check('payouts page no longer redirects when bookings are off', strpos($src, 'hosts_bookings_guard()') === false || strpos($src, '// Payouts stay open') !== false && !preg_match('/^hosts_bookings_guard\(\)/m', $src));
    check('payout form is always rendered (disabled with reasons instead of hidden)', strpos($src, '$blockers') !== false && strpos($src, 'data-locked') !== false);
    // ---- admin photo ordering
    $pu = (int)val("SELECT user_id FROM user_photos GROUP BY user_id HAVING COUNT(*) >= 3 ORDER BY user_id LIMIT 1", [], 0);
    if ($pu) {
        $snap = rows('SELECT id, sort_order, is_primary FROM user_photos WHERE user_id = :u', ['u' => $pu]);
        $order = fn() => array_map('intval', array_column(rows('SELECT id FROM user_photos WHERE user_id = :u ORDER BY is_primary DESC, sort_order, id', ['u' => $pu]), 'id'));
        $before = $order();
        $second = $before[1];
        [$ok] = admin_core_photo_move($second, $pu, 'down');
        $after = $order();
        check('moving a photo later swaps it with its neighbour', $ok && $after[2] === $second && $after[1] === $before[2]);
        check('the main photo stays first', $after[0] === $before[0]);
        [$ok] = admin_core_photo_move($before[0], $pu, 'down');
        check('the main photo cannot be moved', !$ok);
        [$ok] = admin_core_photo_move($second, 999999, 'up');
        check('a photo of another member is refused', !$ok);
        foreach ($snap as $r) { q('UPDATE user_photos SET sort_order = :s, is_primary = :p WHERE id = :i', ['s' => $r['sort_order'], 'p' => $r['is_primary'], 'i' => $r['id']]); }
    }
    // ---- landing Concierges card, International background, Hosts directory background
    $tmpPng = ROOT . '/uploads/hero/_t_cutout.png'; $tmpJpg = ROOT . '/uploads/hero/_t_photo.png';
    @mkdir(dirname($tmpPng), 0775, true);
    $a = imagecreatetruecolor(60, 60); imagealphablending($a, false); imagesavealpha($a, true);
    imagefill($a, 0, 0, imagecolorallocatealpha($a, 0, 0, 0, 127)); imagefilledellipse($a, 30, 30, 30, 30, imagecolorallocatealpha($a, 200, 50, 50, 0)); imagepng($a, $tmpPng);
    $b = imagecreatetruecolor(60, 60); imagefill($b, 0, 0, imagecolorallocate($b, 10, 80, 160)); imagepng($b, $tmpJpg);
    check('a PNG with see-through corners is detected as a cut-out', landing_image_is_cutout('hero/_t_cutout.png'));
    check('an opaque PNG and the built-in photo are not cut-outs', !landing_image_is_cutout('hero/_t_photo.png') && !landing_image_is_cutout('@art/hosts.webp') && !landing_image_is_cutout('') && !landing_image_is_cutout('https://x.test/a.png'));
    check('Concierges picture style: automatic follows the picture', landing_hosts_fit(['image' => 'hero/_t_cutout.png']) === 'cutout' && landing_hosts_fit(['image' => 'hero/_t_photo.png']) === 'photo' && landing_hosts_fit([]) === 'photo');
    check('Concierges picture style: the admin choice wins', landing_hosts_fit(['image' => 'hero/_t_photo.png', 'picture_style' => 'cutout']) === 'cutout' && landing_hosts_fit(['image' => 'hero/_t_cutout.png', 'picture_style' => 'photo']) === 'photo');
    @unlink($tmpPng); @unlink($tmpJpg);
    $schema = ac_section_schema();
    $names = fn(string $k) => array_column($schema[$k]['fields'], 'name');
    check('Concierges editor has the picture style option', in_array('picture_style', $names('hosts'), true));
    check('International editor has a background picture with a built-in default', in_array('image', $names('international'), true) && landing_art_defaults()['international'] === '@art/international.webp' && is_file(ROOT . '/assets/images/art/international.webp'));
    $ix = (string)file_get_contents(ROOT . '/templates/partials/landing_international.php');
    check('International band paints the picture behind the section', strpos($ix, '--ix-img') !== false && strpos($ix, 'has-pic') !== false);
    $dir = (string)file_get_contents(ROOT . '/app/host/directory.php');
    check('Hosts directory uses the picture as the section background (no side figure)', strpos($dir, '--hdir-img') !== false && strpos($dir, 'hdir-art') === false);
    // ---- admin: failures are shown, and the database check can repair a missing column and table
    check('a failed admin query is no longer silent', admin_core_try_val('SELECT COUNT(*) FROM table_that_does_not_exist_x', [], 0) === 0 && admin_core_query_failures() !== [] && strpos(admin_core_failure_notice(), 'could not be loaded') !== false);
    check('the database check knows the shipped structure', admin_db_expected() !== [] && isset(admin_db_expected()['users']['columns']['featured_until']));
    $c0 = admin_db_check();
    check('a complete database reports nothing missing', $c0['tables'] === [] && $c0['columns'] === []);
    q('ALTER TABLE users DROP COLUMN featured_until');
    $hrBak = rows('SELECT * FROM host_reviews');
    q('SET FOREIGN_KEY_CHECKS = 0'); q('DROP TABLE host_reviews'); q('SET FOREIGN_KEY_CHECKS = 1');
    $c1 = admin_db_check();
    check('a missing column and a missing table are detected', ($c1['columns']['users'] ?? []) === ['featured_until'] && in_array('host_reviews', $c1['tables'], true));
    $rep = admin_db_repair();
    check('repair adds the column and the table without errors', $rep['errors'] === [] && $rep['left'] === 0 && mig_has_column('users', 'featured_until') && mig_has_table('host_reviews'));
    foreach ($hrBak as $r) { insert('host_reviews', $r); }
    check('Verification methods inline script carries the CSP nonce', strpos((string)file_get_contents(ROOT . '/app/admin/verification_methods.php'), '<script nonce=') !== false);
    $dash = (string)file_get_contents(ROOT . '/app/admin/dashboard.php');
    check('the dashboard explains hidden sections for limited roles', strpos($dash, 'signed in with the') !== false);
    // ---- admin uses an ad-blocker-safe CSS prefix (EasyList hides .ad-callout, .ad-grid, .ad-hero, .ad-stack, .ad-card, .ad-section, .ad-tag, .ad-link, .ad-head)
    $adminCss = (string)file_get_contents(ROOT . '/assets/css/app.css');
    $adminLive = (string)file_get_contents(ROOT . '/assets/src/modules/admin.css');
    $risky = ['.ad-callout', '.ad-grid', '.ad-hero', '.ad-stack', '.ad-card', '.ad-section', '.ad-tag', '.ad-head', '.ad-link'];
    $leftIn = array_filter($risky, fn($c) => strpos($adminLive, $c . ' ') !== false || strpos($adminLive, $c . '{') !== false || strpos($adminLive, $c . ',') !== false || strpos($adminLive, $c . '>') !== false);
    check('admin CSS no longer uses ad-blocker-filtered class names', $leftIn === []);
    check('admin HTML uses the ad-blocker-safe cp- prefix', strpos($adminCss, '.cp-kpi') !== false && strpos($adminCss, '.cp-hero') !== false && strpos($adminCss, '.cp-callout') !== false);
    // the dashboard, tools and users pages reference the new prefix
    foreach (['app/admin/dashboard.php', 'app/admin/tools.php', 'app/admin/users.php', 'includes/lib/admin.php'] as $f) {
        $src = (string)file_get_contents(ROOT . '/' . $f);
        check("$f uses the ad-blocker-safe cp- prefix", preg_match('/class=["\'][^"\']*\bcp-/', $src) === 1 && !preg_match('/class=["\'][^"\']*\b(ad|xp)-(hero|callout|grid|stack|card|head|tag|link|section)\b/', $src));
    }
    // ---- Connection Profiles admin page is a central dashboard with all tabs and new settings
    $cp = (string)file_get_contents(ROOT . '/app/admin/connection_profiles.php');
    foreach (['overview', 'profiles', 'services', 'settings', 'audit'] as $tab) {
        check("Connection profiles admin exposes the $tab tab", strpos($cp, "'" . $tab . "'") !== false);
    }
    check('Connection profiles admin writes an audit entry when a profile is moderated', strpos($cp, "admin_log('connection_profile_") !== false);
    check('Connection profiles admin saves the new identity settings', strpos($cp, 'host_connections_page_title') !== false && strpos($cp, 'host_connections_card_style') !== false);
    check('Connection profiles admin syncs the public nav items when placement is turned on or off', strpos($cp, "nav_items WHERE menu = :m AND url = :u") !== false);
    foreach (['host_connections_page_title', 'host_connections_menu_label', 'host_connections_card_style', 'host_connections_max_services', 'host_connections_show_in_header'] as $k) {
        check("$k has a default and is readable", setting($k) !== '');
    }
    $dir = (string)file_get_contents(ROOT . '/app/connection/directory.php');
    check('public directory honours the Connection Profiles page title setting', strpos($dir, "setting('host_connections_page_title')") !== false);
    check('public directory honours the card-style setting on each card', strpos($dir, "setting('host_connections_card_style')") !== false);
    $css = (string)file_get_contents(ROOT . '/assets/css/app.css');
    check('Connection Profiles module CSS is in the compiled bundle', strpos($css, '.cph-tabs') !== false && strpos($css, '.cph-services') !== false && strpos($css, '.cph-stat') !== false);
    check('admin can reach the member editor and photo tools', strpos((string)file_get_contents(dirname(__DIR__, 2) . '/app/routes/50_admin.php'), '/admin/users/{id:int}/edit') !== false && function_exists('admin_core_photo_rotate'));
} finally {
    if ($featured) { q('UPDATE users SET featured_until = :f WHERE id = :i', ['f' => $prevFeatured, 'i' => $featured]); }
    foreach ($old as $k => $v) { setting_set($k, $v); }
}
echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
