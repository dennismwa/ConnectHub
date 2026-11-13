<?php
/** Progressive web app helpers: the manifest built from settings, shortcuts, status checks for the admin PWA page. */

/** Shortcuts parsed from the "Label | /path" lines, at most 4. */
function pwa_shortcuts(): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string)setting('pwa_shortcuts')) ?: [] as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '' || $parts[1][0] !== '/' || str_starts_with($parts[1], '//')) {
            continue;
        }
        $out[] = ['name' => mb_substr($parts[0], 0, 30), 'path' => mb_substr($parts[1], 0, 120)];
        if (count($out) >= 4) {
            break;
        }
    }
    return $out;
}

/** The theme colour: the PWA setting, else the primary colour. */
function pwa_theme_color(): string
{
    foreach (['pwa_theme_color', 'color_primary'] as $k) {
        $c = (string)setting($k);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
            return strtolower($c);
        }
    }
    return '#e8523f';
}

/** Source image for the app icons (relative upload path), or ''. */
function pwa_icon_source(): string
{
    foreach (['pwa_icon', 'favicon', 'logo_icon'] as $k) {
        $v = (string)setting($k);
        if ($v !== '') {
            return $v;
        }
    }
    return '';
}

/** Version tag that changes when the icon source or the cache version changes, so phones fetch new icons. */
function pwa_icon_version(): string
{
    return substr(md5(pwa_icon_source() . '|' . (int)setting('pwa_cache_version')), 0, 6);
}

/** The manifest as an array. */
function pwa_manifest(): array
{
    $site = site_name();
    $name = trim((string)setting('pwa_name'));
    if ($name === '') {
        $name = $site . ((string)setting('site_tagline') !== '' ? ' - ' . setting('site_tagline') : '');
    }
    $short = trim((string)setting('pwa_short_name')) ?: mb_substr($site, 0, 14);
    $desc = trim((string)setting('pwa_description')) ?: (string)setting('site_description');
    $base = base_path();
    $v = pwa_icon_version();
    $icon = fn(string $n) => $base . '/icons/' . $n . '?v=' . $v;
    $display = in_array((string)setting('pwa_display'), ['standalone', 'minimal-ui', 'fullscreen', 'browser'], true) ? (string)setting('pwa_display') : 'standalone';
    $orient = in_array((string)setting('pwa_orientation'), ['any', 'portrait-primary', 'landscape-primary'], true) ? (string)setting('pwa_orientation') : 'portrait-primary';
    $start = (string)setting('pwa_start_path');
    $start = $start !== '' && $start[0] === '/' && !str_starts_with($start, '//') ? $start : '/';
    $bg = (string)setting('pwa_background_color');
    $shortcuts = [];
    foreach (pwa_shortcuts() as $s) {
        $shortcuts[] = ['name' => $s['name'], 'url' => $base . $s['path'], 'icons' => [['src' => $icon('icon-192.png'), 'sizes' => '192x192']]];
    }
    $m = [
        'name' => mb_substr($name, 0, 60),
        'short_name' => $short,
        'description' => $desc,
        'id' => $base . '/',
        'start_url' => $base . $start . (str_contains($start, '?') ? '&' : '?') . 'source=pwa',
        'scope' => $base . '/',
        'display' => $display,
        'orientation' => $orient,
        'background_color' => preg_match('/^#[0-9a-fA-F]{6}$/', $bg) ? $bg : '#FBFAF8',
        'theme_color' => pwa_theme_color(),
        'lang' => 'en',
        'categories' => ['social', 'lifestyle'],
        'icons' => [
            ['src' => $icon('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icon('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icon('icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ];
    if ($shortcuts) {
        $m['shortcuts'] = $shortcuts;
    }
    return $m;
}

/** Checks shown on the admin PWA page: [ [ok(bool|null), label, detail], ... ]. null means "worth a look". */
function pwa_checks(): array
{
    $checks = [];
    $https = is_https();
    $local = in_array(parse_url(abs_url('/'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true);
    $checks[] = [$https || $local, 'Secure connection (HTTPS)', $https ? 'The site is served over HTTPS.' : ($local ? 'Local testing is allowed without HTTPS.' : 'Phones only install apps from HTTPS sites. Install an SSL certificate on the domain.')];
    $checks[] = [(bool)setting('pwa_enabled'), 'Installable app is switched on', setting('pwa_enabled') ? 'The manifest and service worker are served.' : 'Switch it on under Settings, Mobile app.'];
    $src = pwa_icon_source();
    $abs = $src !== '' && !preg_match('#^https?://#i', $src) ? upload_abs($src) : '';
    if ($abs !== '' && is_file($abs)) {
        $info = @getimagesize($abs);
        $big = $info && min($info[0], $info[1]) >= 512;
        $checks[] = [$big ? true : null, 'App icon', $big ? 'Your own icon is used (' . $info[0] . ' x ' . $info[1] . ' px).' : 'Your icon is smaller than 512 x 512 px, so it may look soft on some phones.'];
    } else {
        $checks[] = [true, 'App icon', 'The built-in brand icon is used. Upload your own under Settings, Mobile app.'];
    }
    $checks[] = [function_exists('imagecreatetruecolor'), 'Image tools (GD)', function_exists('imagecreatetruecolor') ? 'Icons are resized for every phone size.' : 'The server has no GD extension, so the built-in icons are used instead of yours.'];
    $checks[] = [setting('site_description') !== '' || trim((string)setting('pwa_description')) !== '', 'Description', 'Shown in install dialogs on some phones.'];
    return $checks;
}
