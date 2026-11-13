<?php
/**
 * Views: page meta, layouts, partials, theme variables, logos and navigation.
 *
 * A page script (app/.../*.php) outputs its HTML and calls page([...]) to set
 * the title, layout and meta. The router wraps the output in templates/layouts/{layout}.php.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Page meta
// ---------------------------------------------------------------------------

/**
 * Set or read page options.
 * title, description, layout (public|app|auth|admin|blank), body_class, canonical,
 * og_image, robots, active (nav key), header_transparent, schema (JSON-LD array),
 * js (extra script paths under assets/), css (extra stylesheet paths), no_footer, no_nav
 */
function page(array $meta = []): array
{
    if (!isset($GLOBALS['__page'])) {
        $GLOBALS['__page'] = ['layout' => 'public', 'title' => '', 'description' => '', 'body_class' => '', 'js' => [], 'css' => []];
    }
    foreach ($meta as $k => $v) {
        if (($k === 'js' || $k === 'css') && is_array($v) && is_array($GLOBALS['__page'][$k] ?? null)) {
            $GLOBALS['__page'][$k] = array_values(array_unique(array_merge($GLOBALS['__page'][$k], $v)));
        } else {
            $GLOBALS['__page'][$k] = $v;
        }
    }
    return $GLOBALS['__page'];
}

function page_get(string $key, $default = null)
{
    return page()[$key] ?? $default;
}

function page_title(): string
{
    if (function_exists('seo_format_title')) {
        return seo_format_title(trim((string)page_get('title')));
    }
    $t = trim((string)page_get('title'));
    $name = site_name();
    if ($t === '') {
        $tag = trim((string)setting('site_tagline'));
        return $tag !== '' ? $name . ' · ' . $tag : $name;
    }
    return $t . ' · ' . $name;
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

/** Render templates/partials/{name}.php and return the HTML. */
function partial(string $name, array $vars = []): string
{
    $file = ROOT . '/templates/partials/' . str_replace(['..', '\\'], '', $name) . '.php';
    if (!is_file($file)) {
        return is_debug() ? '<!-- missing partial ' . e($name) . ' -->' : '';
    }
    return (function () use ($file, $vars) {
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    })();
}

/** Run a page script and return what it printed. */
function run_page_script(string $file, array $params): string
{
    return (function () use ($file, $params) {
        ob_start();
        try {
            include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    })();
}

function render_layout(string $content): string
{
    $layout = preg_replace('/[^a-z0-9_-]/i', '', (string)page_get('layout', 'public'));
    $file = ROOT . '/templates/layouts/' . $layout . '.php';
    if ($layout === 'blank' || !is_file($file)) {
        return $content;
    }
    return host_labels_swap_html((function () use ($file, $content) {
        ob_start();
        include $file;
        return (string)ob_get_clean();
    })());
}

/** Render a full error page (404, 403, 500…) inside the public layout. */
function render_error_page(int $code, string $message = ''): string
{
    $titles = [
        400 => 'Something is wrong with that request', 403 => 'You do not have access', 404 => 'We cannot find that page',
        419 => 'Your session expired', 429 => 'Slow down a little', 500 => 'Something went wrong on our side', 503 => 'Back in a moment',
    ];
    $defaults = [
        403 => 'You are signed in, but this area is not available to your account.',
        404 => 'The page may have moved, or the link may be mistyped.',
        419 => 'Reload the page and try again.',
        429 => 'You have made a lot of requests. Wait a minute, then try again.',
        500 => 'We have logged the problem. Please try again in a few minutes.',
        503 => 'We are making improvements.',
    ];
    $GLOBALS['__page'] = ['layout' => 'public', 'title' => (string)($titles[$code] ?? 'Error'), 'description' => '', 'body_class' => 'page-error', 'js' => [], 'css' => [], 'robots' => 'noindex', 'no_hero' => true];
    $title = $titles[$code] ?? 'Error';
    $msg = $message !== '' ? $message : ($defaults[$code] ?? '');
    $inner = partial('error', ['code' => $code, 'title' => $title, 'message' => $msg]);
    return render_layout($inner);
}

// ---------------------------------------------------------------------------
// Theme: CSS variables from the admin's colour, font and effect settings
// ---------------------------------------------------------------------------

function hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return [232, 82, 63];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function rgb_to_hsl(int $r, int $g, int $b): array
{
    $r /= 255;
    $g /= 255;
    $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    if ($max === $min) {
        return [0.0, 0.0, $l];
    }
    $d = $max - $min;
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    switch ($max) {
        case $r:
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
            break;
        case $g:
            $h = ($b - $r) / $d + 2;
            break;
        default:
            $h = ($r - $g) / $d + 4;
    }
    return [$h / 6, $s, $l];
}

function hsl_to_rgb(float $h, float $s, float $l): array
{
    if ($s == 0) {
        $v = (int)round($l * 255);
        return [$v, $v, $v];
    }
    $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
    $p = 2 * $l - $q;
    $f = function ($t) use ($p, $q) {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }
        return $p;
    };
    return [(int)round($f($h + 1 / 3) * 255), (int)round($f($h) * 255), (int)round($f($h - 1 / 3) * 255)];
}

/** 50…900 ramp around a brand colour (500 = the colour itself). Returns [shade => 'r g b']. */
function color_ramp(string $hex): array
{
    [$r, $g, $b] = hex_to_rgb($hex);
    [$h, $s, $l] = rgb_to_hsl($r, $g, $b);
    $targets = [50 => 0.96, 100 => 0.92, 200 => 0.84, 300 => 0.74, 400 => 0.63, 500 => null, 600 => null, 700 => null, 800 => null, 900 => null];
    $out = [];
    foreach ($targets as $shade => $light) {
        if ($shade === 500) {
            $out[$shade] = "$r $g $b";
            continue;
        }
        if ($light === null) {
            // darker steps: scale lightness down from the base
            $factor = [600 => 0.88, 700 => 0.74, 800 => 0.6, 900 => 0.46][$shade];
            [$rr, $gg, $bb] = hsl_to_rgb($h, min(1, $s * 1.02), max(0.08, $l * $factor));
        } else {
            [$rr, $gg, $bb] = hsl_to_rgb($h, min(1, $s * ($shade <= 100 ? 0.9 : 1)), max($l, $light));
        }
        $out[$shade] = "$rr $gg $bb";
    }
    return $out;
}

const THEME_GRADIENTS = [
    'warm_dusk' => ['#E8523F', '#F06A52', '#F4A63A'], 'sunrise' => ['#FF8A5B', '#FFB347', '#FFD27D'],
    'ember' => ['#C2332A', '#E8523F', '#F08A4B'], 'orchid' => ['#8E4DFF', '#C45BE0', '#F0668F'],
    'twilight' => ['#4C3BCF', '#6C5CE7', '#E8523F'], 'honey' => ['#F4A63A', '#F7C35A', '#FBE3A1'],
    'plum' => ['#5B2A5C', '#8E3A7A', '#E8523F'], 'mono' => ['#2A2230', '#B93F30', '#E8523F'],
];

const THEME_MESHES = [
    'sunset' => ['#F4A63A', '#E8523F', '#6C5CE7', '#F7C35A'], 'dawn' => ['#FFD6A5', '#FFADAD', '#CDB4DB', '#FDFFB6'],
    'ember' => ['#E8523F', '#F4A63A', '#8C2F39', '#F08A4B'], 'dusk' => ['#6C5CE7', '#E8523F', '#2A2230', '#F4A63A'],
    'haze' => ['#F6D6C8', '#E8C9D6', '#D8CFF0', '#FBE7C6'], 'orchid' => ['#8E4DFF', '#F0668F', '#F4A63A', '#C45BE0'],
    'amber' => ['#F4A63A', '#F7C35A', '#E8523F', '#FBE3A1'], 'midnight' => ['#2B2150', '#6C5CE7', '#E8523F', '#14102B'],
];

const THEME_FONTS = [
    'plus-jakarta-sans' => "'Plus Jakarta Sans'", 'outfit' => "'Outfit'", 'sora' => "'Sora'", 'manrope' => "'Manrope'",
    'inter' => "'Inter'", 'dm-sans' => "'DM Sans'",
];

/** CSS custom properties generated from the admin's settings. Cached until settings change. */
function theme_css(): string
{
    return cache_remember('theme_css', 3600, function () {
        $vars = [];
        foreach (['primary' => 'color_primary', 'secondary' => 'color_secondary', 'tertiary' => 'color_tertiary'] as $name => $key) {
            foreach (color_ramp((string)setting($key)) as $shade => $rgb) {
                $vars["--$name-$shade"] = $rgb;
            }
        }
        $g = THEME_GRADIENTS[(string)setting('hero_gradient')] ?? THEME_GRADIENTS['warm_dusk'];
        foreach ($g as $i => $hex) {
            $vars['--grad-' . ($i + 1)] = implode(' ', hex_to_rgb($hex));
        }
        $m = THEME_MESHES[(string)setting('mesh_style')] ?? THEME_MESHES['sunset'];
        foreach ($m as $i => $hex) {
            $vars['--mesh-' . ($i + 1)] = implode(' ', hex_to_rgb($hex));
        }
        $vars['--font-heading'] = (THEME_FONTS[(string)setting('font_heading')] ?? "'Plus Jakarta Sans'") . ", ui-sans-serif, system-ui, sans-serif";
        $vars['--font-body'] = (THEME_FONTS[(string)setting('font_body')] ?? "'Inter'") . ", ui-sans-serif, system-ui, sans-serif";
        $vars['--base-size'] = (int)setting('base_font_size') . 'px';
        $vars['--heading-tracking'] = ['normal' => '0em', 'tight' => '-0.02em', 'ultra' => '-0.035em'][(string)setting('heading_tracking')] ?? '-0.02em';
        $vars['--radius-scale'] = ['sharp' => '0.6', 'default' => '1', 'round' => '1.4'][(string)setting('radius_scale')] ?? '1';
        $vars['--noise-opacity'] = (string)round(((int)setting('noise_intensity')) / 100, 3);
        $vars['--blur-extra'] = ['light' => '0px', 'medium' => '6px', 'heavy' => '14px'][(string)setting('blur_level')] ?? '6px';
        $vars['--logo-pad'] = (int)setting('logo_padding') . 'px';
        $css = ':root{';
        foreach ($vars as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }
        return $css . '}';
    });
}

/** Attributes for <html>: theme, motion level, etc. */
function html_attrs(): string
{
    $theme = (string)setting('default_theme');
    $attrs = ['lang' => 'en', 'data-motion' => (string)setting('animations_level'), 'data-hover' => (string)setting('hover_effects'),
        'data-reveal' => (string)setting('page_reveals'), 'data-float' => (string)setting('floating_elements'),
        'data-dark' => (string)setting('dark_intensity'), 'data-skeleton' => (string)setting('skeleton_style'),
        'data-flip' => (string)setting('auth_flip_style'), 'data-default-theme' => $theme,
        'data-allow-toggle' => setting('allow_theme_toggle') ? '1' : '0', 'data-respect-motion' => setting('respect_reduced_motion') ? '1' : '0'];
    $u = current_user();
    if ($u && in_array($u['theme_pref'] ?? '', ['light', 'dark', 'system'], true)) {
        $attrs['data-user-theme'] = $u['theme_pref'];
    }
    $out = '';
    foreach ($attrs as $k => $v) {
        $out .= ' ' . $k . '="' . e($v) . '"';
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Logo
// ---------------------------------------------------------------------------

/**
 * Logo for a placement: header_top (floating over the hero photo) | header (solid, after scrolling) | header_mobile_top |
 * header_mobile | auth | sidepanel | footer | admin.
 * $variant: auto | white. "white" is the light-on-dark version used over photos; "auto" outputs the light-mode and
 * dark-mode logos together and lets CSS pick, so dark mode needs no extra request. Each placement has its own chain of
 * logo settings (see $sizes), so every logo slot the admin fills in is used where it is meant to be.
 */
function logo_html(string $placement = 'header', string $variant = 'auto', string $class = ''): string
{
    // placement => [dimension, size setting, light chain, dark chain, "light on dark" chain]
    // A chain is the list of logo settings tried in order; the first one that has a file is used.
    $base = ['logo_compact', 'logo_full', 'logo_icon'];
    $sizes = [
        'header_top' => ['h', 'logo_h_header', $base, $base, ['logo_white', 'logo_header_scrolled']],
        'header' => ['h', 'logo_h_header_scrolled', array_merge(['logo_header_scrolled'], $base), array_merge(['logo_header_scrolled_dark', 'logo_dark', 'logo_white'], $base), ['logo_white']],
        // the top bar of public pages (profiles, hosts, articles) for a signed-in member on a large screen
        'header_member' => ['h', 'logo_h_header_member', array_merge(['logo_header_member', 'logo_header_scrolled'], $base), array_merge(['logo_header_member_dark', 'logo_header_scrolled_dark', 'logo_dark', 'logo_white'], $base), ['logo_white']],
        'header_mobile_top' => ['h', 'logo_h_header_mobile', ['logo_mobile', 'logo_icon', 'logo_compact', 'logo_full'], ['logo_mobile', 'logo_icon', 'logo_compact', 'logo_full'], ['logo_mobile_white', 'logo_white', 'logo_icon']],
        'header_mobile' => ['h', 'logo_h_header_mobile', ['logo_mobile', 'logo_icon', 'logo_compact', 'logo_full'], ['logo_mobile_dark', 'logo_mobile', 'logo_icon', 'logo_dark', 'logo_white', 'logo_compact', 'logo_full'], ['logo_mobile_white', 'logo_white', 'logo_icon']],
        'auth' => ['w', 'logo_w_auth', ['logo_auth', 'logo_full', 'logo_compact', 'logo_icon'], ['logo_auth_dark', 'logo_auth', 'logo_dark', 'logo_white', 'logo_full', 'logo_compact', 'logo_icon'], ['logo_auth_white', 'logo_auth', 'logo_white']],
        'sidepanel' => ['w', 'logo_w_sidepanel', array_merge(['logo_sidepanel'], $base), array_merge(['logo_sidepanel_dark', 'logo_dark', 'logo_white'], $base), ['logo_white']],
        // the side panel of Hosts can have its own logo; it falls back to the one members see
        'sidepanel_host' => ['w', 'logo_w_sidepanel', array_merge(['logo_sidepanel_host', 'logo_sidepanel'], $base), array_merge(['logo_sidepanel_host_dark', 'logo_sidepanel_dark', 'logo_dark', 'logo_white'], $base), ['logo_white']],
        // the top bar of the signed-in app on phones
        'app_header' => ['h', 'logo_h_app_header', ['logo_app_header', 'logo_mobile', 'logo_icon', 'logo_compact', 'logo_full'], ['logo_app_header_dark', 'logo_mobile_dark', 'logo_app_header', 'logo_mobile', 'logo_icon', 'logo_dark', 'logo_white', 'logo_compact', 'logo_full'], ['logo_white', 'logo_mobile_white']],
        // the menu that slides in on phones on the public pages and the landing page
        'menu' => ['w', 'logo_w_menu', array_merge(['logo_menu', 'logo_sidepanel'], $base), array_merge(['logo_menu_dark', 'logo_sidepanel_dark', 'logo_dark', 'logo_white'], $base), ['logo_white']],
        'footer' => ['w', 'logo_w_footer', array_merge(['logo_footer'], $base), array_merge(['logo_footer_dark', 'logo_dark', 'logo_white'], $base), ['logo_white']],
        'admin' => ['h', 'logo_h_admin', $base, array_merge(['logo_white'], $base), ['logo_white']],
    ];
    // older templates asked for "header" with the white variant for the floating state
    if ($placement === 'header' && $variant === 'white') {
        $placement = 'header_top';
    } elseif ($placement === 'header_mobile' && $variant === 'white') {
        $placement = 'header_mobile_top';
    }
    [$dim, $key, $lightChain, $darkChain, $whiteChain] = $sizes[$placement] ?? $sizes['header'];
    $pick = function (array $chain): string {
        foreach ($chain as $k) {
            $v = (string)setting($k);
            if ($v !== '') {
                return $v;
            }
        }
        return '';
    };
    $size = max(12, (int)setting($key));
    $normal = $pick($lightChain);
    $white = $pick($whiteChain) ?: $normal;
    $dark = $pick($darkChain) ?: $white;
    $name = site_name();
    $style = ($dim === 'h' ? 'height:' . $size . 'px;width:auto;' : 'width:' . $size . 'px;height:auto;') . 'max-width:100%;';
    $bg = (string)setting('logo_bg');
    $wrapClass = 'ch-logo ch-logo-' . str_replace('_', '-', $placement) . ($bg !== 'none' ? ' ch-logo-bg-' . $bg : '') . ($class !== '' ? ' ' . $class : '');
    $href = e(url('/'));

    if ($normal === '' && $white === '') {
        $mark = '<span class="ch-logo-mark" style="' . ($dim === 'h' ? 'height:' . $size . 'px;width:' . $size . 'px;' : 'height:' . (int)($size / 4.2) . 'px;width:' . (int)($size / 4.2) . 'px;') . '" aria-hidden="true">' . icon('heart-handshake', 'w-full h-full p-[22%]') . '</span>';
        $word = !setting('logo_show_wordmark') ? '' : '<span class="ch-logo-word">' . e($name) . '</span>';
        return '<a href="' . $href . '" class="' . e($wrapClass) . ' ch-logo-default' . ($variant === 'white' ? ' on-dark' : '') . '" aria-label="' . e($name) . ' home">' . $mark . $word . '</a>';
    }
    if ($variant === 'white') {
        $img = '<img src="' . e(upload_url($white)) . '" alt="' . e($name) . '" style="' . $style . '" class="ch-logo-img" decoding="async">';
    } elseif ($dark === $normal) {
        $img = '<img src="' . e(upload_url($normal)) . '" alt="' . e($name) . '" style="' . $style . '" class="ch-logo-img" decoding="async">';
    } else {
        $img = '<img src="' . e(upload_url($normal)) . '" alt="' . e($name) . '" style="' . $style . '" class="ch-logo-img ch-logo-light" decoding="async">'
            . '<img src="' . e(upload_url($dark)) . '" alt="" aria-hidden="true" style="' . $style . '" class="ch-logo-img ch-logo-darkmode" decoding="async">';
    }
    return '<a href="' . $href . '" class="' . e($wrapClass) . '" aria-label="' . e($name) . ' home">' . $img . '</a>';
}

/**
 * Dashboard banners. $slot is host | host_wallet | wallet | admin. Returns the picture URL, or '' when pictures are switched off.
 * The admin can upload one for each slot under Settings > Logo & branding; an empty slot uses the built-in picture.
 */
function banner_image(string $slot): string
{
    if (!setting('banners_show_images')) {
        return '';
    }
    $defaults = ['host' => '@art/hosts.webp', 'host_wallet' => '@art/cta.webp', 'wallet' => '@art/premium.webp', 'admin' => '@art/cta.webp'];
    $v = (string)setting('banner_' . $slot . '_image');
    return upload_url($v !== '' ? $v : ($defaults[$slot] ?? ''));
}

/** Attributes for a banner element: class (with picture and overlay modifiers) and the picture as a CSS variable. */
function banner_attrs(string $slot, string $class = ''): string
{
    $img = banner_image($slot);
    $ov = (string)setting('banner_overlay');
    $ov = in_array($ov, ['light', 'medium', 'strong'], true) ? $ov : 'medium';
    return 'class="' . e(trim($class . ' bn' . ($img !== '' ? ' bn-pic' : '') . ' bn-' . $ov)) . '"'
        . ($img !== '' ? ' style="--bn-img:url(\'' . e($img) . '\')"' : '');
}

// ---------------------------------------------------------------------------
// Navigation (admin-managed through nav_items)
// ---------------------------------------------------------------------------

/** Active items of a menu that the current viewer should see. */
function nav_menu(string $menu): array
{
    $all = cache_remember('nav_' . $menu, 120, fn() => rows('SELECT * FROM nav_items WHERE menu = :m AND is_active = 1 ORDER BY sort_order, id', ['m' => $menu]));
    $u = current_user();
    $out = [];
    foreach ($all as $item) {
        $a = $item['audience'];
        if ($a === 'guest' && $u) {
            continue;
        }
        if ($a === 'user' && (!$u || $u['role'] !== 'user')) {
            continue;
        }
        if ($a === 'host' && (!$u || $u['role'] !== 'host')) {
            continue;
        }
        if ($a === 'premium' && (!$u || tier_rank($u['tier']) < 1)) {
            continue;
        }
        if (!setting('hosts_enabled') && preg_match('#^/hosts?(/|$)#', $item['url'])) {
            continue;
        }
        // Bookings switched off: the booking pages leave the menus, except for people who already have bookings to follow
        if (function_exists('hosts_booking_enabled') && !hosts_booking_enabled() && preg_match('#^/(host/(availability|bookings|earnings|payouts)|bookings)(/|$)#', $item['url'])
            && (!$u || strpos($item['url'], '/host/availability') === 0 || !(hosts_user_has_bookings((int)$u['id']) || (preg_match('#^/host/(earnings|payouts)#', $item['url']) && hosts_user_has_payouts((int)$u['id']))))) {
            continue;
        }
        if (!setting('intl_enabled') && strpos($item['url'], '/international') === 0) {
            continue;
        }
        if (!setting('messaging_enabled') && strpos($item['url'], '/messages') === 0) {
            continue;
        }
        $out[] = $item;
    }
    return $out;
}

/** Build a member's contextual second dock item from their selected experience.
 * The first item is always Home; unrelated experience links are removed so an old
 * admin-configured Hosts/Elite/Discover link cannot leak into another mode.
 */
function choice_contextual_dock(array $tabs, ?string $mode = null): array
{
    // Use the explicit saved choice, not the site's fallback/default, so a visitor
    // who has not answered the experience picker still gets the full discovery dock.
    $mode = $mode ?? (function_exists('choice_current') ? choice_current() : '');
    $targets = [
        'dating'  => ['label' => 'Dating',  'url' => '/discover?experience=dating', 'icon' => 'heart'],
        'friends' => ['label' => 'Friends', 'url' => '/discover?experience=friends', 'icon' => 'users'],
        'fun'     => ['label' => 'Escorts', 'url' => '/hosts?experience=fun',    'icon' => 'concierge-bell'],
        'elite'   => ['label' => function_exists('elite_label') ? elite_label() : 'Elite', 'url' => '/elite?experience=elite', 'icon' => 'gem'],
    ];
    $home = ['label' => 'Home', 'url' => '/', 'icon' => 'home', 'badge_key' => null, 'new_tab' => 0];
    $all = [
        ['label' => 'Escorts', 'url' => '/hosts?experience=fun', 'icon' => 'concierge-bell'],
        ['label' => 'Dating', 'url' => '/discover?experience=dating', 'icon' => 'heart'],
        ['label' => function_exists('elite_label') ? elite_label() : 'Elite', 'url' => '/elite?experience=elite', 'icon' => 'gem'],
        ['label' => 'Friends', 'url' => '/discover?experience=friends', 'icon' => 'users'],
    ];
    foreach ($all as &$item) { $item += ['badge_key' => null, 'new_tab' => 0]; }
    unset($item);

    // Remove stale admin-configured experience links and Pricing from the mobile dock.
    $rest = [];
    foreach ($tabs as $tab) {
        $path = strtolower((string)(parse_url((string)($tab['url'] ?? ''), PHP_URL_PATH) ?: ''));
        $label = strtolower(trim((string)($tab['label'] ?? '')));
        if (in_array($path, ['/','/home'], true) || $label === 'home') { continue; }
        if ($path === '/pricing' || $path === '/premium' || in_array($label, ['pricing','plans','premium'], true)) { continue; }
        if (preg_match('#^/(discover|hosts?)(/|$)#', $path) || preg_match('#^/elite(/|$)#', $path)
            || in_array($label, ['dating','friends','escorts','hosts','elite'], true)) { continue; }
        $rest[] = $tab;
    }

    if ($mode === 'all') {
        // Six slots: Home, all four experiences, then Join.
        return array_merge([$home], $all, [[
            'label' => 'Join', 'url' => '/register', 'icon' => 'user-plus', 'badge_key' => null, 'new_tab' => 0,
        ]]);
    }
    if (isset($targets[$mode])) {
        // A chosen experience gets priority in slot two; retain useful member tools after it.
        return array_slice(array_merge([$home, $targets[$mode] + ['badge_key' => null, 'new_tab' => 0]], $rest), 0, 5);
    }

    // No explicit choice yet: make the four experiences discoverable immediately.
    return array_merge([$home], $all);
}

function nav_url(string $url): string
{
    return preg_match('#^(https?:)?//|^mailto:|^tel:#i', $url) ? $url : url($url);
}

function nav_badge(?string $key): int
{
    if (!$key) {
        return 0;
    }
    return (int)(badge_counts()[$key] ?? 0);
}

function footer_pages(): array
{
    return cache_remember('footer_pages', 300, fn() => rows('SELECT title, slug FROM pages WHERE is_published = 1 AND show_in_footer = 1 ORDER BY sort_order, title'));
}

// ---------------------------------------------------------------------------
// Small UI helpers
// ---------------------------------------------------------------------------

function selected($a, $b): string
{
    return (string)$a === (string)$b ? ' selected' : '';
}

function checked($cond): string
{
    return $cond ? ' checked' : '';
}

function active_class(string $path, string $on = 'is-active', bool $prefix = true): string
{
    return is_active_path($path, $prefix) ? $on : '';
}

function tier_badge(string $tier): string
{
    if ($tier === 'free' || $tier === 'guest') {
        return '';
    }
    return '<span class="badge badge-' . e($tier) . '">' . icon($tier === 'vip' ? 'crown' : 'sparkles', 'w-3 h-3') . e(tier_label($tier)) . '</span>';
}

function verified_badge(bool $verified): string
{
    return $verified ? '<span class="verified-badge" title="Verified member">' . icon('badge-check', 'w-4 h-4', 'Verified') . '</span>' : '';
}

function flash_html(): string
{
    $out = '';
    foreach (flashes() as $f) {
        $out .= '<div class="flash flash-' . e($f['type']) . '" role="status">' . e($f['message']) . '</div>';
    }
    return $out;
}

/** JSON config for assets/js/app.js: URLs, CSRF token, flags. */
function js_config(): array
{
    $u = current_user();
    return [
        'base' => base_path(), 'csrf' => csrf_token(), 'siteName' => site_name(),
        'user' => $u ? ['id' => $u['id'], 'role' => $u['role'], 'tier' => $u['tier'], 'name' => $u['first_name']] : null,
        'poll' => (int)setting('msg_poll_ms'), 'confetti' => (bool)setting('confetti_on_match'),
        'pwa' => (bool)setting('pwa_enabled'), 'installPrompt' => (bool)setting('pwa_install_prompt'),
        'installAfter' => (int)setting('pwa_prompt_after_visits'), 'allowThemeToggle' => (bool)setting('allow_theme_toggle'),
        'cookieBanner' => (bool)setting('cookie_banner_enabled'), 'cookieText' => (string)setting('cookie_banner_text'),
        'ga' => (string)setting('google_analytics_id'), 'gtm' => (string)setting('seo_gtm_id'), 'hostTerm' => [host_term(), host_term(true)], 'termsUrl' => url('/' . (string)setting('terms_slug')), 'privacyUrl' => url('/' . (string)setting('privacy_slug')),
    ];
}
