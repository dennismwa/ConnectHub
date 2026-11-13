<?php
/**
 * Admin content + money helpers (module D2). Everything here is prefixed ac_ so it cannot clash with the
 * other admin helpers. Page scripts live in app/admin/content/*.php and app/admin/money/*.php.
 *
 * Public helpers other modules may call (guard with function_exists):
 *   hero_slides_active()   Hero slides that are enabled and inside their schedule window, in display order.
 *   ac_flush('landing')    Clear the cache keys used by a group of public pages.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Guards and responses
// ---------------------------------------------------------------------------

/** Admin guard used at the top of every page script: view rights to read, edit rights to post. */
function ac_guard(string $module): array
{
    $u = require_admin($module, 'view');
    if (is_post()) {
        $u = require_admin($module, 'edit');
    }
    return $u;
}

/** POST fields, or the JSON body for fetch() calls that send JSON. */
function ac_body(): array
{
    if ($_POST) {
        return $_POST;
    }
    return json_body();
}

/** Finish a successful save: flash + redirect, or JSON for fetch() callers. */
function ac_ok(string $message, string $to = '', array $extra = []): void
{
    flash('success', $message);
    $to = $to !== '' ? $to : (string)preg_replace('#^' . preg_quote(base_path(), '#') . '#', '', current_url());
    if (wants_json()) {
        json_ok(['message' => $message, 'redirect' => url($to)] + $extra);
    }
    redirect($to);
}

/**
 * Finish a failed save. $errors is [field => message] (or one message string).
 * Form posts are sent back with the old input and inline errors; fetch() callers get JSON.
 */
function ac_fail($errors, ?string $to = null): void
{
    if (is_string($errors)) {
        $errors = ['_' => $errors];
    }
    $first = (string)(reset($errors) ?: 'Please check the form and try again.');
    if (wants_json()) {
        json_fail($first, 422, ['errors' => $errors]);
    }
    set_old($_POST);
    errors_set($errors);
    flash('error', count($errors) > 1 ? 'Please fix the highlighted fields.' : $first);
    redirect($to ?? (string)preg_replace('#^' . preg_quote(base_path(), '#') . '#', '', current_url()));
}

/** Inline error text for a field (works with dotted paths such as f.items.0.title). */
function ac_err(string $key): string
{
    $m = field_error($key);
    return $m !== '' ? '<p class="error-text" data-error-for="' . e($key) . '">' . e($m) . '</p>' : '';
}

function ac_has_err(string $key): string
{
    return field_error($key) !== '' ? ' has-error' : '';
}

// ---------------------------------------------------------------------------
// Cache groups
// ---------------------------------------------------------------------------

/** Clear the cache keys behind the public pages affected by a change. */
function ac_flush(string ...$groups): void
{
    static $map = [
        // The lead's landing page caches under landing_* (sections, slides, stories, locations, faqs, testimonials, blog).
        'landing' => ['landing_', 'hero', 'site_stats'],
        'nav' => ['nav_', 'footer_pages'],
        'pages' => ['footer_pages', 'page_', 'pages', 'sitemap'],
        'blog' => ['landing_', 'blog', 'sitemap'],
        'faq' => ['landing_', 'faq'],
        'testimonials' => ['landing_', 'auth_stories', 'testimonial'],
        'icebreakers' => ['icebreak', 'ice_', 'bio_'],
        'interests' => ['landing_', 'interest'],
        'locations' => ['landing_', 'loc_', 'hero', 'site_stats'],
        'plans' => ['plan', 'pricing'],
        'gifts' => ['gift'],
        'ads' => ['ad_', 'ads'],
    ];
    foreach ($groups as $g) {
        foreach ($map[$g] ?? [$g] as $prefix) {
            cache_clear($prefix);
        }
    }
    if (in_array('nav', $groups, true)) {
        foreach (['header', 'footer_explore', 'footer_company', 'public_tabbar', 'app_sidebar', 'app_tabbar', 'host_sidebar', 'host_tabbar'] as $m) {
            cache_forget('nav_' . $m);
        }
    }
    if (in_array('locations', $groups, true)) {
        cache_forget('loc_counties');
        cache_forget('loc_towns');
    }
}

// ---------------------------------------------------------------------------
// Small value helpers
// ---------------------------------------------------------------------------

/** Internal path, http(s), mailto: or tel: link. Empty is accepted when $allowEmpty. */
function ac_valid_link(string $v, bool $allowEmpty = true): bool
{
    $v = trim($v);
    if ($v === '') {
        return $allowEmpty;
    }
    if (preg_match('#^\s*(javascript|data|vbscript):#i', $v)) {
        return false;
    }
    if ($v[0] === '/' && ($v[1] ?? '') !== '/') {
        return !preg_match('#[\s<>"]#', $v);
    }
    return (bool)preg_match('#^(https?://[^\s<>"]+|mailto:[^\s<>"]+|tel:[+0-9 ()-]+)$#i', $v);
}

function ac_valid_url(string $v, bool $allowEmpty = true): bool
{
    $v = trim($v);
    return $v === '' ? $allowEmpty : (bool)preg_match('#^https?://[^\s<>"]+\.[^\s<>"]+$#i', $v);
}

/** Value for <input type="datetime-local">. */
function ac_dt_input(?string $dt): string
{
    $ts = $dt ? strtotime($dt) : false;
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

/** Parse a datetime-local / date string. Returns 'Y-m-d H:i:s', null when empty, false when invalid. */
function ac_dt_parse(string $s)
{
    $s = trim($s);
    if ($s === '') {
        return null;
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/', $s, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        return false;
    }
    return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[1], $m[2], $m[3], (int)($m[4] ?? 0), (int)($m[5] ?? 0));
}

/** Decimal from user input ("1,250.50" or "1250"). Returns null when it is not a number. */
function ac_decimal($v): ?float
{
    $v = str_replace([',', ' '], '', trim((string)$v));
    return $v !== '' && is_numeric($v) ? round((float)$v, 2) : null;
}

/** Map coordinate from user input. Keeps six decimals (about 10 cm) so saving a place never moves it. Null when not a number. */
function ac_coord($v): ?float
{
    $v = str_replace([',', ' '], '', trim((string)$v));
    return $v !== '' && is_numeric($v) ? round((float)$v, 6) : null;
}

/** Date filter value Y-m-d or ''. */
function ac_date_param(string $v): string
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && ac_dt_parse($v) !== false ? $v : '';
}

function ac_user_url(int $id): string
{
    return url('/admin/users/' . $id);
}

/** "Name" with the email underneath, for tables. Expects columns name/email (or pass keys). */
function ac_person(?string $name, ?string $email, ?int $id = null): string
{
    $name = trim((string)$name) !== '' ? trim((string)$name) : (string)$email;
    $html = '<div class="cm-person"><span class="cm-person-name">' . e($name) . '</span>';
    if ($email && $email !== $name) {
        $html .= '<span class="cm-person-sub">' . e($email) . '</span>';
    }
    $html .= '</div>';
    return $id ? '<a class="cm-person-link" href="' . e(ac_user_url($id)) . '">' . $html . '</a>' : $html;
}

function ac_badge(string $label, string $variant = ''): string
{
    return '<span class="badge' . ($variant !== '' ? ' badge-' . e($variant) : '') . '">' . e($label) . '</span>';
}

/** Coloured badge for any status word used in this module. */
function ac_status_badge(string $status, ?string $label = null): string
{
    static $variants = [
        'success' => ['active', 'completed', 'published', 'approved', 'paid', 'replied', 'confirmed', 'delivered', 'live', 'sent', 'visible'],
        'warning' => ['pending', 'new', 'draft', 'requested', 'awaiting_payment', 'scheduled', 'processing', 'sending', 'expiring'],
        'danger' => ['failed', 'rejected', 'cancelled', 'spam', 'declined', 'no_show', 'banned', 'suspended'],
        'info' => ['refunded', 'read', 'custom'],
        '' => ['expired', 'archived', 'paused', 'unsubscribed', 'hidden', 'ended'],
    ];
    $variant = '';
    foreach ($variants as $v => $list) {
        if (in_array($status, $list, true)) {
            $variant = $v;
            break;
        }
    }
    $label = $label ?? ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge' . ($variant !== '' ? ' badge-' . $variant : '') . '"><span class="badge-dot"></span>' . e($label) . '</span>';
}

/** Styled checkbox with a screen-reader label (row selectors in tables and lists). $attrs is trusted markup. */
function ac_check(string $attrs, string $label): string
{
    return '<label class="check cm-sel"><input type="checkbox" ' . $attrs . '><span class="sr-only">' . e($label) . '</span></label>';
}

/** Names of the icons in the sprite (so pickers can only offer icons that render). */
function ac_icon_names(): array
{
    $file = ROOT . '/assets/icons/sprite.svg';
    return cache_remember('ac_icon_names_' . (int)@filemtime($file), 3600, function () use ($file) {
        $svg = (string)@file_get_contents($file);
        preg_match_all('/<symbol id="i-([a-z0-9-]+)"/', $svg, $m);
        $names = array_values(array_unique($m[1] ?? []));
        sort($names);
        return $names;
    });
}

function ac_icon_valid(string $name): bool
{
    return $name !== '' && in_array($name, ac_icon_names(), true);
}

/** Slugs that belong to core routes and cannot be used by CMS pages. */
function ac_reserved_slugs(): array
{
    return ['admin', 'api', 'browse', 'pricing', 'faq', 'contact', 'blog', 'newsletter', 'offline', 'login', 'register', 'logout',
        'forgot-password', 'reset-password', 'verify-email', 'account', 'onboarding', 'discover', 'search', 'likes', 'matches', 'visitors',
        'favorites', 'profile', 'u', 'settings', 'verification', 'messages', 'notifications', 'premium', 'pay', 'billing', 'boost', 'gifts',
        'hosts', 'host', 'bookings', 'ad', 'icons', 'assets', 'uploads', 'install', 'manifest.webmanifest', 'sw.js', 'robots.txt', 'sitemap.xml'];
}

/** Plain-text opening of rich content, with a space where block tags end. */
function ac_excerpt(string $html, int $len = 180): string
{
    $t = preg_replace('#</(p|h[1-6]|li|blockquote|div|tr)>|<br\s*/?>#i', ' ', $html) ?? $html;
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return str_limit(trim(preg_replace('/\s+/u', ' ', $t) ?? ''), $len);
}

/** Honest "x ago / in x" label for a datetime. */
function ac_when(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    if (!$ts) {
        return '';
    }
    if ($ts > time()) {
        $d = $ts - time();
        return $d < 3600 ? 'in ' . max(1, (int)round($d / 60)) . ' min' : ($d < 86400 ? 'in ' . (int)round($d / 3600) . ' h' : 'in ' . (int)ceil($d / 86400) . ' days');
    }
    return time_ago($dt);
}

// ---------------------------------------------------------------------------
// Ordering
// ---------------------------------------------------------------------------

/**
 * Save a drag-and-drop order. The ids take over the sort values they already held, so rows that are
 * not part of the list keep their place relative to it (for example FAQs in other categories).
 */
function ac_reorder(string $table, array $ids, string $col = 'sort_order', array $scope = []): int
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (count($ids) < 2 || count($ids) > 500) {
        return 0;
    }
    $sql = 'SELECT id, ' . db_ident($col) . ' AS v FROM ' . db_ident($table) . ' WHERE id IN (:ids)';
    $params = ['ids' => $ids];
    foreach ($scope as $k => $v) {
        $sql .= ' AND ' . db_ident($k) . ' = :s_' . $k;
        $params['s_' . $k] = $v;
    }
    $cur = pairs($sql, $params);
    $ids = array_values(array_filter($ids, fn($i) => isset($cur[$i])));
    if (count($ids) < 2) {
        return 0;
    }
    $slots = array_map('intval', array_values($cur));
    sort($slots);
    if (count(array_unique($slots)) < count($ids)) {
        $slots = range($slots[0], $slots[0] + count($ids) - 1);
    }
    $changed = 0;
    db_transaction(function () use ($table, $col, $ids, $slots, $cur, &$changed) {
        foreach ($ids as $i => $id) {
            if ((int)$cur[$id] !== $slots[$i]) {
                q('UPDATE ' . db_ident($table) . ' SET ' . db_ident($col) . ' = :v WHERE id = :id', ['v' => $slots[$i], 'id' => $id]);
                $changed++;
            }
        }
    });
    return $changed;
}

/** Next sort value for a table (optionally within a scope). */
function ac_next_sort(string $table, array $scope = [], string $col = 'sort_order'): int
{
    $sql = 'SELECT COALESCE(MAX(' . db_ident($col) . '), 0) + 1 FROM ' . db_ident($table);
    $params = [];
    if ($scope) {
        $w = [];
        foreach ($scope as $k => $v) {
            $w[] = db_ident($k) . ' = :' . $k;
            $params[$k] = $v;
        }
        $sql .= ' WHERE ' . implode(' AND ', $w);
    }
    return (int)val($sql, $params, 1);
}

/** Flip one whitelisted 0/1 column. Returns the new value or null when the row does not exist. */
function ac_toggle(string $table, int $id, string $col, array $allowed, ?bool $to = null): ?int
{
    if (!in_array($col, $allowed, true)) {
        throw new HttpError('That option cannot be changed here.', 400);
    }
    $cur = val('SELECT ' . db_ident($col) . ' FROM ' . db_ident($table) . ' WHERE id = :id', ['id' => $id]);
    if ($cur === null) {
        return null;
    }
    $new = $to === null ? ((int)$cur ? 0 : 1) : ($to ? 1 : 0);
    q('UPDATE ' . db_ident($table) . ' SET ' . db_ident($col) . ' = :v WHERE id = :id', ['v' => $new, 'id' => $id]);
    return $new;
}

// ---------------------------------------------------------------------------
// Files
// ---------------------------------------------------------------------------

/**
 * Handle one image field of a form. Returns ['path'=>new-or-kept path|null, 'error'=>string, 'changed'=>bool, 'old'=>path to delete after the row is saved].
 * A "remove_<field>" checkbox clears the image. Call ac_image_commit() once the database row is written.
 */
function ac_image_save(string $field, string $sub, ?string $current, array $o = []): array
{
    $o += ['maxSide' => 1920, 'keepAlpha' => false, 'allowSvg' => false, 'maxMb' => 6];
    $res = ['path' => $current, 'error' => '', 'changed' => false, 'old' => null, 'picked' => false, 'fresh' => false];
    $file = $_FILES[$field] ?? null;
    if ($file && !is_array($file['name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $r = save_image($file, $sub, (int)$o['maxSide'], (bool)$o['keepAlpha'], (bool)$o['allowSvg'], (int)$o['maxMb']);
        if (!$r['ok']) {
            $res['error'] = $r['error'];
            return $res;
        }
        // every admin upload is kept in the media library so it can be picked again elsewhere
        media_register($r['path'], ['title' => media_title_from_name((string)$file['name'])]);
        return ['path' => $r['path'], 'error' => '', 'changed' => true, 'old' => $current, 'picked' => false, 'fresh' => true];
    }
    // "Choose from media": the picker puts the chosen path in pick_<field>
    $pick = trim((string)post('pick_' . $field, ''));
    if ($pick !== '' && $pick !== (string)$current) {
        $ok = media_pick_valid($pick, 'image');
        if ($ok === null) {
            $res['error'] = 'That picture is no longer in the media library. Choose another one.';
            return $res;
        }
        return ['path' => $ok, 'error' => '', 'changed' => true, 'old' => $current, 'picked' => true, 'fresh' => false];
    }
    if (post_bool('remove_' . $field) && $current) {
        return ['path' => null, 'error' => '', 'changed' => true, 'old' => $current, 'picked' => false, 'fresh' => false];
    }
    return $res;
}

/**
 * Tidy up after the row is saved. A replaced or removed file is deleted unless it is kept in the media library
 * (upload_delete() checks). Pass $failed when the save did not happen: a file stored just now is removed again,
 * a picked library file is left alone.
 */
function ac_image_commit(array $res, bool $failed = false): void
{
    if ($failed) {
        if ($res['changed'] && $res['path'] && !empty($res['fresh'])) {
            media_forget($res['path']);
            upload_delete($res['path']);
        }
        return;
    }
    if ($res['changed'] && $res['old']) {
        upload_delete($res['old']);
    }
}

// ---------------------------------------------------------------------------
// CSV
// ---------------------------------------------------------------------------

function ac_csv_cell($v): string
{
    $s = (string)($v ?? '');
    if ($s !== '' && strpbrk($s[0], "=+-@\t\r") !== false && !is_numeric($s)) {
        return "'" . $s;
    }
    return $s;
}

/** Stream a CSV download and stop. $rows is any iterable of row arrays. */
function ac_csv_download(string $filename, array $header, iterable $rows): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '', $filename) . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header, ',', '"', '\\');
    foreach ($rows as $r) {
        fputcsv($out, array_map('ac_csv_cell', $r), ',', '"', '\\');
    }
    fclose($out);
    exit;
}

/** Parse pasted CSV/TSV text into rows (handles quotes, commas, tabs and semicolons). */
function ac_parse_pasted(string $text, int $max = 2000): array
{
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($text)) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $delim = strpos($line, "\t") !== false ? "\t" : (substr_count($line, ';') > substr_count($line, ',') ? ';' : ',');
        $rows[] = array_map('trim', str_getcsv($line, $delim, '"', '\\'));
        if (count($rows) >= $max) {
            break;
        }
    }
    return $rows;
}

// ---------------------------------------------------------------------------
// Public export: hero slides
// ---------------------------------------------------------------------------

/** Hero slides that are enabled and inside their schedule window, in display order. */
function hero_slides_active(): array
{
    try {
        return rows('SELECT * FROM hero_slides WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at > NOW()) ORDER BY sort_order, id');
    } catch (Throwable $e) {
        return rows('SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order, id');
    }
}

// ---------------------------------------------------------------------------
// Landing sections: what each block can edit, and how it is validated
// ---------------------------------------------------------------------------

/**
 * Field definitions for each landing_sections.content JSON. Types: text, textarea, select, int, link, lines, items.
 * The JSON written back has the same keys and shapes as database/seed/05_content.sql.
 */
/** The schema for one section key. Every key that starts with "agents" (agents_top, agents_new ...) uses the agents schema. */
function ac_section_schema_for(string $key): ?array
{
    $all = ac_section_schema();
    if (isset($all[$key])) {
        return $all[$key];
    }
    return strpos($key, 'agents') === 0 ? $all['agents'] : null;
}

function ac_section_schema(): array
{
    $title = ['name' => 'title', 'type' => 'text', 'label' => 'Heading', 'max' => 100, 'required' => true];
    $sub = ['name' => 'subtitle', 'type' => 'textarea', 'label' => 'Sub-heading', 'max' => 300, 'rows' => 2];
    $cta = fn(string $p = '', string $label = 'Button') => [
        ['name' => $p . 'cta_text', 'type' => 'text', 'label' => $label . ' text', 'max' => 40],
        ['name' => $p . 'cta_url', 'type' => 'link', 'label' => $label . ' link', 'max' => 255],
    ];
    $sec = fn() => [
        ['name' => 'secondary_text', 'type' => 'text', 'label' => 'Second button text', 'max' => 40],
        ['name' => 'secondary_url', 'type' => 'link', 'label' => 'Second button link', 'max' => 255],
    ];
    $art = landing_art_defaults();
    $image = fn(string $label, string $default, string $ratio = '16-9', string $help = '') => ['name' => 'image', 'type' => 'image', 'label' => $label, 'default' => $default, 'ratio' => $ratio, 'help' => $help];
    $count = fn(int $min, int $max, string $label) => ['name' => 'count', 'type' => 'int', 'label' => $label, 'min' => $min, 'max' => $max, 'default' => $min];
    return [
        'locations' => [
            'about' => 'A grid of counties and towns. Each one links to the browse page for that place.',
            'fields' => [$title, $sub,
                ['name' => 'show', 'type' => 'select', 'label' => 'What to list', 'options' => ['mixed' => 'Counties and towns', 'counties' => 'Counties only', 'towns' => 'Towns only'], 'default' => 'mixed'],
                ['name' => 'source', 'type' => 'select', 'label' => 'Which places', 'options' => ['picked' => 'Places ticked "Show on landing page" (Locations)', 'popular' => 'Places marked popular', 'busiest' => 'The places with the most members'], 'default' => 'picked',
                    'help' => 'Choose the places one by one on the Locations page, each with its own picture.'],
                ['name' => 'only_members', 'type' => 'bool', 'label' => 'Only places that already have members', 'help' => 'Hides empty places so the page never shows a town with nobody in it.'],
                ['name' => 'style', 'type' => 'select', 'label' => 'Display style', 'options' => ['cards' => 'Picture cards (default): a photo of each place', 'compact' => 'Small cards: no picture, no counters, neat and compact', 'chips' => 'Buttons with the name only, wrapping to fit', 'chips_row' => 'Buttons in one swipeable row on phones'], 'default' => 'cards',
                    'help' => 'Small cards and buttons use no pictures, so the page loads faster and suits sites with many places. Small cards never show counters.'],
                ['name' => 'show_counts', 'type' => 'bool', 'label' => 'Show member counters', 'help' => 'Applies to picture cards and buttons: cards show "12 members", buttons a small number badge. Small cards never show counters.', 'default' => true],
                $count(4, 24, 'How many places')],
        ],
        'profiles' => [
            'about' => 'A grid of members. Show one group, or several as tabs: newest, VIP and featured, suggested, or regular members.',
            'fields' => array_merge([$title, $sub,
                ['name' => 'count', 'type' => 'int', 'label' => 'How many members in each group', 'min' => 2, 'max' => 36, 'default' => 12, 'help' => 'Between 2 and 36. A multiple of the columns looks tidiest. More people on show makes the page feel alive.'],
                ['name' => 'columns', 'type' => 'select', 'label' => 'Per row on computers', 'options' => ['3' => '3 per row (larger cards)', '4' => '4 per row', '5' => '5 per row (smaller cards)'], 'default' => '4'],
                ['name' => 'cols_tablet', 'type' => 'int', 'label' => 'Per row on tablets', 'min' => 2, 'max' => 4, 'default' => 3],
                ['name' => 'cols_mobile', 'type' => 'int', 'label' => 'Per row on phones', 'min' => 1, 'max' => 3, 'default' => 2],
                ['name' => 'fill_vip', 'type' => 'bool', 'label' => 'Fill the VIP and featured group with other members', 'help' => 'On: when fewer VIP, boosted or featured members exist than the number above (or none yet), other active members follow so the group is never empty. Off: only VIP and featured members are shown.', 'default' => true],
                ['name' => 'group_newest', 'type' => 'bool', 'label' => 'Show: Newest members', 'help' => 'The most recent members to join.', 'default' => true],
                ['name' => 'group_vip', 'type' => 'bool', 'label' => 'Show: VIP and featured members', 'help' => 'Members on VIP, with an active boost, or featured by you on the member page.'],
                ['name' => 'group_suggested', 'type' => 'bool', 'label' => 'Show: Suggested members', 'help' => 'Well-filled or verified profiles that were active lately. Signed-in visitors see ones that fit them best.'],
                ['name' => 'group_regular', 'type' => 'bool', 'label' => 'Show: Regular members', 'help' => 'Everyone who is not VIP or featured, most recently active first.'],
                ['name' => 'first_group', 'type' => 'select', 'label' => 'Group shown first', 'options' => ['newest' => 'Newest', 'vip' => 'VIP and featured', 'suggested' => 'Suggested', 'regular' => 'Regular'], 'default' => 'newest', 'help' => 'With two or more groups switched on, they appear as tabs and this one opens first.'],
                ['name' => 'label_newest', 'type' => 'text', 'label' => 'Tab name: newest', 'max' => 24, 'default' => 'Newest'],
                ['name' => 'label_vip', 'type' => 'text', 'label' => 'Tab name: VIP and featured', 'max' => 24, 'default' => 'VIP and featured'],
                ['name' => 'label_suggested', 'type' => 'text', 'label' => 'Tab name: suggested', 'max' => 24, 'default' => 'Suggested'],
                ['name' => 'label_regular', 'type' => 'text', 'label' => 'Tab name: regular', 'max' => 24, 'default' => 'Members'],
            ], $cta('', 'Button')),
        ],
        'how_it_works' => [
            'about' => 'Numbered steps that explain how the site works.',
            'fields' => [$title, $sub, ['name' => 'items', 'type' => 'items', 'label' => 'Steps', 'item' => 'step', 'min' => 2, 'max' => 6, 'fields' => [
                ['name' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                ['name' => 'image', 'type' => 'image', 'label' => 'Picture', 'ratio' => '4-3'],
                ['name' => 'title', 'type' => 'text', 'label' => 'Step title', 'max' => 60, 'required' => true],
                ['name' => 'text', 'type' => 'textarea', 'label' => 'Description', 'max' => 240, 'rows' => 2, 'required' => true],
            ], 'defaults' => $art['steps']]],
        ],
        'features' => [
            'about' => 'A grid of reasons to join. Each card has an icon, a short title and one or two sentences.',
            'fields' => [$title, $sub, $image('Large picture', $art['features'], '3-4', 'Shown beside the first cards. Remove it to show the cards only.'), ['name' => 'items', 'type' => 'items', 'label' => 'Feature cards', 'item' => 'feature', 'min' => 3, 'max' => 9, 'fields' => [
                ['name' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                ['name' => 'title', 'type' => 'text', 'label' => 'Title', 'max' => 60, 'required' => true],
                ['name' => 'text', 'type' => 'textarea', 'label' => 'Description', 'max' => 240, 'rows' => 2, 'required' => true],
            ]]],
        ],
        'safety' => [
            'about' => 'Short safety habits with an icon each, and a link to the full safety page.',
            'fields' => array_merge([$title, $sub, $image('Picture', $art['safety'], '4-3'), ['name' => 'items', 'type' => 'items', 'label' => 'Safety tips', 'item' => 'tip', 'min' => 2, 'max' => 8, 'fields' => [
                ['name' => 'icon', 'type' => 'icon', 'label' => 'Icon'],
                ['name' => 'text', 'type' => 'textarea', 'label' => 'Tip', 'max' => 200, 'rows' => 2, 'required' => true],
            ]]], $cta('', 'Link')),
        ],
        'agents' => [
            'about' => 'A showcase of Hosts (agents): photo, services, rating and contact buttons. Choose which kinds of agent appear, how many, how many per row, and what happens when too few qualify. Add as many agent blocks as you like and place each one anywhere on the page.',
            'fields' => array_merge([$title, $sub,
                ['name' => 'tier_vip', 'type' => 'bool', 'label' => 'Show VIP agents', 'help' => 'Agents you mark VIP on their Host page, or on a VIP plan.', 'default' => true],
                ['name' => 'tier_featured', 'type' => 'bool', 'label' => 'Show featured agents', 'help' => 'Featured by you, or with a paid featured pack.', 'default' => true],
                ['name' => 'tier_premium', 'type' => 'bool', 'label' => 'Show premium agents', 'help' => 'Agents on a Premium plan.', 'default' => true],
                ['name' => 'tier_paid', 'type' => 'bool', 'label' => 'Show agents with a paid listing', 'help' => 'Agents whose paid directory listing is running.', 'default' => true],
                ['name' => 'tier_boosted', 'type' => 'bool', 'label' => 'Show boosted agents', 'help' => 'Agents with an active boost.'],
                ['name' => 'fill_others', 'type' => 'bool', 'label' => 'Fill with other agents when too few qualify', 'help' => 'On: if fewer agents match the kinds ticked above than the number below, other approved agents follow so the block is never empty. Off: only the kinds ticked above are shown. With none ticked, every approved agent qualifies.', 'default' => true],
                ['name' => 'sort', 'type' => 'select', 'label' => 'Order within the same kind', 'options' => agent_sorts(), 'default' => 'rotate', 'help' => 'Agents you pin on their Host page always come first.'],
                ['name' => 'category', 'type' => 'select', 'label' => 'Only this category', 'options' => ['0' => 'All categories'] + array_map(fn($r) => (string)$r['name'], hosts_categories()), 'default' => '0'],
                ['name' => 'count', 'type' => 'int', 'label' => 'How many agents', 'min' => 1, 'max' => 36, 'default' => 12],
                ['name' => 'layout', 'type' => 'select', 'label' => 'Layout', 'options' => ['grid' => 'Grid: every agent visible, rows wrap', 'rail' => 'Swipe row on every screen', 'rail_mobile' => 'Grid on desktop, swipe row on phones'], 'default' => 'rail_mobile'],
                ['name' => 'cols_desktop', 'type' => 'int', 'label' => 'Per row on computers', 'min' => 2, 'max' => 6, 'default' => 4],
                ['name' => 'cols_tablet', 'type' => 'int', 'label' => 'Per row on tablets', 'min' => 2, 'max' => 4, 'default' => 3],
                ['name' => 'cols_mobile', 'type' => 'int', 'label' => 'Per row on phones', 'min' => 1, 'max' => 3, 'default' => 2, 'help' => 'In a swipe row this is how many are fully visible at once.'],
                ['name' => 'card_style', 'type' => 'select', 'label' => 'Card style', 'options' => ['overlay' => 'Photo with the name over it (like a story card)', 'stack' => 'Photo on top, name underneath'], 'default' => 'overlay'],
                ['name' => 'show_tier', 'type' => 'bool', 'label' => 'Show the VIP / Featured badge', 'default' => true],
                ['name' => 'show_bio', 'type' => 'bool', 'label' => 'Show a short bio', 'help' => 'Uses the default bio template (Settings > Hosts > Agent showcase) with the agent\'s own tagline.', 'default' => true],
                ['name' => 'show_services', 'type' => 'bool', 'label' => 'Show services', 'default' => true],
                ['name' => 'max_services', 'type' => 'int', 'label' => 'Most services on a card', 'min' => 1, 'max' => 6, 'default' => 3],
                ['name' => 'show_rating', 'type' => 'bool', 'label' => 'Show the rating', 'default' => true],
                ['name' => 'show_price', 'type' => 'bool', 'label' => 'Show the starting price', 'help' => 'Follows the "Show rates publicly" setting and each agent\'s own choice.', 'default' => true],
                ['name' => 'show_contact', 'type' => 'bool', 'label' => 'Show Call and WhatsApp buttons', 'help' => 'Only when the visitor may see contact details (Admin > Agent visibility).', 'default' => true],
            ], $cta('', 'Link')),
        ],
        'hosts' => [
            'about' => 'Promotes the Hosts directory. Only shown when Hosts are switched on.',
            'fields' => array_merge([$title, ['name' => 'subtitle', 'type' => 'textarea', 'label' => 'Sub-heading', 'max' => 400, 'rows' => 3], $image('Picture', $art['hosts'], '16-9'),
                ['name' => 'picture_style', 'type' => 'select', 'label' => 'Picture style', 'options' => ['auto' => 'Automatic: a picture with no background sits on a styled card, other pictures fill the card', 'cutout' => 'Picture with no background (PNG): shown whole on a styled card', 'photo' => 'Photo: fills the whole card'], 'default' => 'auto', 'help' => 'A transparent PNG of a person looks best on the styled card; a normal photo looks best filling the card.']], $cta('', 'Main button'),
                [['name' => 'secondary_text', 'type' => 'text', 'label' => 'Second button text', 'max' => 40], ['name' => 'secondary_url', 'type' => 'link', 'label' => 'Second button link', 'max' => 255],
                 ['name' => 'bullets', 'type' => 'lines', 'label' => 'Reassurance points', 'help' => 'One per line. Up to 6.', 'max' => 6, 'maxlen' => 120]]),
        ],
        'international' => [
            'about' => 'Promotes the International connections page. Only shown when it is switched on.',
            'fields' => array_merge([$title, ['name' => 'subtitle', 'type' => 'textarea', 'label' => 'Sub-heading', 'max' => 300, 'rows' => 2], $image('Background picture', $art['international'], 'wide', 'Fills the whole section behind the text. Remove it to use the plain dark design.')], $cta('', 'Button')),
        ],
        'premium' => [
            'about' => 'A pointer to the plans page. The plans themselves are managed under Plans.',
            'fields' => array_merge([$title, $sub, $image('Banner picture', $art['premium'], 'wide', 'Shown above the plans. Remove it to show the plans only.')], $cta('', 'Button')),
        ],
        'elite' => [
            'about' => 'A few approved Elite members on a dark band, with a button to the Elite page. Hidden while Elite is off or nobody is approved yet. Locked cards show blurred photos.',
            'fields' => array_merge([$title, $sub, $count(3, 12, 'How many members')], $cta('', 'Button')),
        ],
        'promos' => [
            'about' => 'Adverts that are running right now, as small cards: one gets a roomy card, two sit side by side, three or more make a row. The whole section disappears when no advert is running. Businesses buy these on the Advertise page; packages and approval are under Adverts.',
            'fields' => array_merge([$title, $sub, $count(1, 12, 'Most adverts to show')], $cta('', 'Link')),
        ],
        'testimonials' => [
            'about' => 'Shows the stories marked "Show on landing page" under Testimonials.',
            'fields' => [$title, $sub],
        ],
        'blog' => [
            'about' => 'Latest published blog posts.',
            'fields' => [$title, $sub, $count(1, 6, 'How many posts'),
                ['name' => 'mobile_slider', 'type' => 'bool', 'label' => 'Swipeable slider on phones', 'help' => 'On phones the articles sit in a row you swipe sideways, with dots underneath. Off stacks them one under the other.', 'default' => true]],
        ],
        'faq' => [
            'about' => 'The first active FAQs.',
            'fields' => [$title, $sub, $count(1, 10, 'How many questions')],
        ],
        'cta' => [
            'about' => 'The closing call to action above the footer.',
            'fields' => array_merge([$title, $sub, $image('Background picture', $art['cta'], 'wide')], $cta('', 'Main button'), $sec()),
        ],
    ];
}

/** Image results of the section form being saved, so the files can be tidied once the row is written (or the save failed). */
function ac_section_images(?array $res = null, ?bool $failed = null): void
{
    static $pending = [];
    if ($res !== null) {
        $pending[] = $res;
    }
    if ($failed !== null) {
        foreach ($pending as $r) {
            ac_image_commit($r, $failed);
        }
        $pending = [];
    }
}

/** Read the posted section form ($src is $_POST['f']) into content JSON data. Returns [content array, errors]. */
function ac_section_collect(array $fields, array $src, string $prefix = 'f'): array
{
    $out = [];
    $errors = [];
    foreach ($fields as $f) {
        $name = $f['name'];
        $path = $prefix . '.' . $name;
        $raw = $src[$name] ?? null;
        $label = $f['label'] ?? $name;
        switch ($f['type']) {
            case 'text':
            case 'textarea':
            case 'link':
                $v = is_string($raw) ? trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw) ?? '') : '';
                $max = (int)($f['max'] ?? 255);
                if (mb_strlen($v) > $max) {
                    $errors[$path] = $label . ' can be up to ' . $max . ' characters.';
                }
                if (!empty($f['required']) && $v === '') {
                    $errors[$path] = $label . ' is required.';
                }
                if ($f['type'] === 'link' && !ac_valid_link($v)) {
                    $errors[$path] = $label . ' must start with / or https://.';
                }
                $out[$name] = $v;
                break;
            case 'select':
                $v = is_string($raw) ? $raw : '';
                if (!isset($f['options'][$v])) {
                    $v = (string)($f['default'] ?? array_key_first($f['options']));
                }
                $out[$name] = $v;
                break;
            case 'int':
                $n = is_numeric($raw) ? (int)$raw : (int)($f['default'] ?? $f['min']);
                if ($n < $f['min'] || $n > $f['max']) {
                    $errors[$path] = $label . ' must be between ' . $f['min'] . ' and ' . $f['max'] . '.';
                }
                $out[$name] = max($f['min'], min($f['max'], $n));
                break;
            case 'icon':
                $v = is_string($raw) ? trim($raw) : '';
                if ($v !== '' && !ac_icon_valid($v)) {
                    $errors[$path] = 'Pick an icon from the list.';
                    $v = '';
                }
                $out[$name] = $v;
                break;
            case 'bool':
                $out[$name] = is_string($raw) ? in_array($raw, ['1', 'true', 'on', 'yes'], true) : !empty($raw);
                break;
            case 'image':
                $cur = is_string($raw) ? trim($raw) : '';
                if ($cur !== '' && !media_art_valid($cur) && !(media_path_ok($cur) && is_file(upload_abs($cur)))) {
                    $cur = '';
                }
                $res = ac_image_save(str_replace('.', '_', $path), 'landing', $cur !== '' ? $cur : null, ['maxSide' => 2000, 'maxMb' => 8]);
                ac_section_images($res);
                if ($res['error'] !== '') {
                    $errors[$path] = $res['error'];
                    $out[$name] = $cur;
                } else {
                    $out[$name] = (string)($res['path'] ?? '');
                }
                break;
            case 'lines':
                $lines = [];
                foreach (preg_split('/\r\n|\r|\n/', is_string($raw) ? $raw : '') ?: [] as $l) {
                    $l = trim($l);
                    if ($l !== '') {
                        if (mb_strlen($l) > (int)$f['maxlen']) {
                            $errors[$path] = 'Each line can be up to ' . $f['maxlen'] . ' characters.';
                        }
                        $lines[] = mb_substr($l, 0, (int)$f['maxlen']);
                    }
                }
                if (count($lines) > (int)$f['max']) {
                    $errors[$path] = 'Use at most ' . $f['max'] . ' lines.';
                    $lines = array_slice($lines, 0, (int)$f['max']);
                }
                $out[$name] = $lines;
                break;
            case 'items':
                $items = [];
                $list = is_array($raw) ? $raw : [];
                foreach ($list as $i => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    [$item, $e2] = ac_section_collect($f['fields'], $row, $path . '.' . $i);
                    $allEmpty = true;
                    foreach ($item as $k => $v) {
                        if ($k !== 'icon' && $v !== '') {
                            $allEmpty = false;
                        }
                    }
                    if ($allEmpty) {
                        continue; // a blank row is dropped, not an error
                    }
                    $errors += $e2;
                    $items[] = $item;
                }
                if (count($items) < (int)$f['min']) {
                    $errors[$path] = 'Add at least ' . $f['min'] . ' ' . $f['item'] . 's.';
                }
                if (count($items) > (int)$f['max']) {
                    $errors[$path] = 'Use at most ' . $f['max'] . ' ' . $f['item'] . 's.';
                    $items = array_slice($items, 0, (int)$f['max']);
                }
                $out[$name] = $items;
                break;
        }
    }
    return [$out, $errors];
}

// ---------------------------------------------------------------------------
// Plans: the features JSON
// ---------------------------------------------------------------------------

/** The feature keys read by includes/plans.php, with plain-language labels. -1 means unlimited. */
function ac_plan_feature_defs(): array
{
    return [
        'Daily allowances' => [
            'daily_swipes' => ['limit', 'Likes and passes per day', 'How many profiles a member can react to each day.'],
            'daily_superlikes' => ['limit', 'Super likes per day', '0 switches super likes off for this plan.'],
            'daily_messages' => ['limit', 'New messages per day', 'Replies in a conversation they already have are not counted when "Free members can always reply" is on.'],
            'daily_rewinds' => ['limit', 'Undo swipes per day', 'Only used when "Undo the last swipe" is on.'],
        ],
        'Member perks' => [
            'rewind' => ['bool', 'Undo the last swipe', 'Take back a pass or a like.'],
            'see_who_liked' => ['bool', 'See who liked them', 'Shows the Likes page with names and photos.'],
            'advanced_filters' => ['bool', 'Advanced search filters', 'Height, education, body type and the other premium filters.'],
            'incognito' => ['bool', 'Browse in incognito', 'Visit profiles without leaving a visit.'],
            'read_receipts' => ['bool', 'Read receipts', 'See when a message has been read.'],
            'message_before_match' => ['bool', 'Message before matching', 'Start a conversation with someone who has not liked them back yet.'],
            'profile_visitors' => ['bool', 'See profile visitors', 'Shows who looked at their profile.'],
            'no_ads' => ['bool', 'No adverts', 'Hides sponsored cards and banners.'],
            'priority_likes' => ['bool', 'Likes shown first', 'Their likes appear at the top of the other person\'s list.'],
        ],
        'Photos and boosts' => [
            'max_photos' => ['count', 'Photos on a profile', 'Between 1 and 30.'],
            'boost_credits_monthly' => ['count', 'Free boosts each month', 'Credited when the plan is activated or renewed.'],
            'dm_credits_monthly' => ['count', 'Free message credits each month', 'Lets them message people they have not matched with. Credited when the plan is activated or renewed.'],
        ],
    ];
}

/** Keys in the order the seeds write them. */
function ac_plan_feature_order(): array
{
    return ['daily_swipes', 'daily_superlikes', 'daily_messages', 'daily_rewinds', 'rewind', 'see_who_liked', 'advanced_filters', 'incognito',
        'read_receipts', 'message_before_match', 'max_photos', 'boost_credits_monthly', 'dm_credits_monthly', 'profile_visitors', 'no_ads', 'priority_likes'];
}

/** Build the features array from a posted form. Returns [features, errors]. */
function ac_plan_features_collect(array $src): array
{
    $defs = [];
    foreach (ac_plan_feature_defs() as $items) {
        $defs += $items;
    }
    $features = [];
    $errors = [];
    foreach (ac_plan_feature_order() as $key) {
        [$type, $label] = $defs[$key];
        if ($type === 'bool') {
            $features[$key] = !empty($src[$key]);
        } elseif ($type === 'limit') {
            if (!empty($src[$key . '_unlimited'])) {
                $features[$key] = -1;
            } else {
                $v = $src[$key] ?? '0';
                if (!is_numeric($v) || (int)$v < 0 || (int)$v > 100000) {
                    $errors['feat.' . $key] = $label . ' must be a whole number from 0 to 100000, or unlimited.';
                    $features[$key] = 0;
                } else {
                    $features[$key] = (int)$v;
                }
            }
        } else {
            $v = $src[$key] ?? '0';
            $min = $key === 'max_photos' ? 1 : 0;
            if (!is_numeric($v) || (int)$v < $min || (int)$v > 30) {
                $errors['feat.' . $key] = $label . ' must be a whole number from ' . $min . ' to 30.';
                $features[$key] = $min;
            } else {
                $features[$key] = (int)$v;
            }
        }
    }
    return [$features, $errors];
}

// ---------------------------------------------------------------------------
// Money helpers
// ---------------------------------------------------------------------------

/**
 * Host balance context for payouts. Uses the Hosts module's ledger (which honours the payout hold period) when it is loaded.
 * Keys: earned (total payable so far), paid, pending (requested payouts), available, held, upcoming.
 */
function ac_host_balance(int $hostId): array
{
    if (function_exists('hosts_balance')) {
        try {
            $b = hosts_balance($hostId);
            return ['earned' => (float)$b['total_payable'], 'paid' => (float)$b['paid_out'], 'pending' => (float)$b['requested'],
                'available' => (float)$b['available'], 'held' => (float)$b['held'], 'upcoming' => (float)$b['upcoming']];
        } catch (Throwable $e) {
            // fall through to the simple sum below
        }
    }
    $earned = (float)val("SELECT COALESCE(SUM(host_earning), 0) FROM host_bookings WHERE host_id = :h AND status = 'completed'", ['h' => $hostId], 0);
    $paid = (float)val("SELECT COALESCE(SUM(amount), 0) FROM host_payouts WHERE host_id = :h AND status = 'paid'", ['h' => $hostId], 0);
    $pending = (float)val("SELECT COALESCE(SUM(amount), 0) FROM host_payouts WHERE host_id = :h AND status = 'requested'", ['h' => $hostId], 0);
    return ['earned' => $earned, 'paid' => $paid, 'pending' => $pending, 'available' => max(0.0, $earned - $paid - $pending), 'held' => 0.0, 'upcoming' => 0.0];
}

/** Plain-language description of what a payment bought, from its metadata. */
function ac_payment_subject(array $p): string
{
    $meta = json_decode((string)($p['metadata'] ?? ''), true) ?: [];
    switch ($p['payment_type']) {
        case 'subscription':
            $plan = !empty($meta['plan_id']) ? val('SELECT name FROM subscription_plans WHERE id = :id', ['id' => (int)$meta['plan_id']]) : null;
            return ($plan ?: 'Plan') . ' subscription' . (!empty($meta['period']) ? ', ' . $meta['period'] : '');
        case 'boost':
            return 'Profile boost';
        case 'featured':
            return 'Featured profile';
        case 'gift':
            $g = !empty($meta['gift_type_id']) ? val('SELECT name FROM gift_types WHERE id = :id', ['id' => (int)$meta['gift_type_id']]) : null;
            return 'Gift' . ($g ? ': ' . $g : '');
        case 'booking':
            return 'Booking deposit' . (!empty($meta['booking_id']) ? ' #' . (int)$meta['booking_id'] : '');
        case 'host_listing':
            return 'Host listing' . (!empty($meta['period']) ? ', ' . $meta['period'] : '');
        case 'dm_pack':
            return 'Message credits' . (!empty($meta['credits']) ? ': ' . (int)$meta['credits'] : '');
        case 'intl':
            return 'International page, ' . (($meta['kind'] ?? '') === 'join' ? 'listing' : 'access');
        case 'elite':
            return 'Elite, ' . (['join' => 'membership', 'single' => 'one profile', 'pack' => 'pack of ' . (int)($meta['credits'] ?? 0), 'pass' => 'pass'][$meta['kind'] ?? ''] ?? 'payment');
        case 'ad':
            return 'Advert';
    }
    return (string)($p['description'] ?? ucfirst((string)$p['payment_type']));
}

function ac_payment_type_label(string $t): string
{
    return ['subscription' => 'Subscription', 'boost' => 'Boost', 'gift' => 'Gift', 'featured' => 'Featured', 'booking' => 'Booking', 'ad' => 'Advert', 'host_listing' => 'Host listing', 'dm_pack' => 'Message credits', 'intl' => 'International', 'elite' => 'Elite'][$t] ?? ucfirst($t);
}

function ac_payment_method_label(string $m): string
{
    return ['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank transfer', 'manual' => 'M-Pesa Paybill or till', 'card' => 'Card', 'paystack' => 'Paystack', 'paypal' => 'PayPal', 'test' => 'Test payment'][$m] ?? ucfirst(str_replace('_', ' ', $m));
}

/** Other payments that carry the same M-Pesa receipt or bank reference (a sign of a reused proof). */
function ac_duplicate_receipts(array $p): array
{
    $out = [];
    $r = trim((string)($p['mpesa_receipt'] ?? ''));
    if ($r !== '') {
        $out = rows('SELECT id, status, amount, created_at FROM payments WHERE id <> :id AND UPPER(mpesa_receipt) = UPPER(:r) ORDER BY id LIMIT 10', ['id' => $p['id'], 'r' => $r]);
    }
    $b = trim((string)($p['bank_reference'] ?? ''));
    if ($b !== '') {
        $out = array_merge($out, rows('SELECT id, status, amount, created_at FROM payments WHERE id <> :id AND UPPER(bank_reference) = UPPER(:r) ORDER BY id LIMIT 10', ['id' => $p['id'], 'r' => $b]));
    }
    return $out;
}

/** Payments waiting for a person to check them: manual M-Pesa and bank transfers that are still pending. */
function ac_review_sql(string $alias = 'p'): string
{
    return "($alias.status = 'pending' AND $alias.payment_method IN ('manual', 'bank_transfer'))";
}

/** Look up a member by "#id", plain id, or e-mail. */
function ac_find_member(string $q): ?array
{
    $q = trim($q);
    if ($q === '') {
        return null;
    }
    if (preg_match('/^#?(\d+)$/', $q, $m)) {
        return row("SELECT u.id, u.email, u.role, u.status, COALESCE(NULLIF(p.display_name, ''), u.email) AS name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id", ['id' => (int)$m[1]]);
    }
    return row("SELECT u.id, u.email, u.role, u.status, COALESCE(NULLIF(p.display_name, ''), u.email) AS name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.email = :e", ['e' => strtolower($q)]);
}

/** SQL fragment: member display name from users u + user_profiles p aliases. */
function ac_name_sql(string $u = 'u', string $p = 'p'): string
{
    return "COALESCE(NULLIF($p.display_name, ''), $u.email)";
}

// ---------------------------------------------------------------------------
// Newsletter
// ---------------------------------------------------------------------------

/** Make sure a subscriber has an unsubscribe token and return it. */
function ac_subscriber_token(int $id): string
{
    $t = (string)val('SELECT token FROM newsletter_subscribers WHERE id = :id', ['id' => $id], '');
    if ($t !== '') {
        return $t;
    }
    $t = bin2hex(random_bytes(16));
    q('UPDATE newsletter_subscribers SET token = :t WHERE id = :id AND (token IS NULL OR token = \'\')', ['t' => $t, 'id' => $id]);
    return (string)val('SELECT token FROM newsletter_subscribers WHERE id = :id', ['id' => $id], $t);
}

/** Send one campaign e-mail to one subscriber. */
function ac_campaign_send(array $campaign, array $sub): bool
{
    $token = ac_subscriber_token((int)$sub['id']);
    return send_template_mail((string)$sub['email'], (string)$campaign['subject'], 'newsletter_campaign', [
        'body_html' => (string)$campaign['body_html'],
        'unsubscribe_url' => abs_url('/newsletter/unsubscribe/' . $token),
    ]);
}

// ---------------------------------------------------------------------------
// Payments list filters (shared by the list, the review queue and the CSV export)
// ---------------------------------------------------------------------------

/** Filter options for payments: [type => label], [method => label], [status => label]. */
function ac_payment_filter_options(): array
{
    return [
        'types' => ['subscription' => 'Subscription', 'booking' => 'Booking deposit', 'gift' => 'Gift', 'boost' => 'Boost', 'featured' => 'Featured profile', 'host_listing' => 'Host listing', 'dm_pack' => 'Message credits', 'intl' => 'International', 'elite' => 'Elite', 'ad' => 'Advert'],
        'methods' => ['mpesa' => 'M-Pesa prompt', 'manual' => 'M-Pesa Paybill or till', 'card' => 'Card', 'paystack' => 'Paystack', 'paypal' => 'PayPal', 'bank_transfer' => 'Bank transfer', 'test' => 'Test payment'],
        'statuses' => ['pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'],
    ];
}

/**
 * WHERE clause for "payments p JOIN users u LEFT JOIN user_profiles up" from the query string (q, status, type, method, from, to).
 * $review limits it to the manual approval queue. Returns [sql, params].
 */
function ac_payments_where(bool $review = false): array
{
    $where = ['1=1'];
    $params = [];
    if ($review) {
        $where[] = ac_review_sql('p');
    }
    $opts = ac_payment_filter_options();
    $status = get_str('status', 12);
    $type = get_str('type', 20);
    $method = get_str('method', 20);
    if ($status !== '' && isset($opts['statuses'][$status]) && !$review) {
        $where[] = 'p.status = :status';
        $params['status'] = $status;
    }
    if ($type !== '' && isset($opts['types'][$type])) {
        $where[] = 'p.payment_type = :type';
        $params['type'] = $type;
    }
    if ($method !== '' && isset($opts['methods'][$method])) {
        $where[] = 'p.payment_method = :method';
        $params['method'] = $method;
    }
    $q = get_str('q', 100);
    if ($q !== '') {
        $where[] = '(p.reference LIKE :q OR p.mpesa_receipt LIKE :q OR p.bank_reference LIKE :q OR p.mpesa_phone LIKE :q OR p.description LIKE :q OR u.email LIKE :q OR up.display_name LIKE :q' . (ctype_digit($q) ? ' OR p.id = :qid OR p.user_id = :qid' : '') . ')';
        $params['q'] = '%' . addcslashes($q, '%_\\') . '%';
        if (ctype_digit($q)) {
            $params['qid'] = (int)$q;
        }
    }
    $from = ac_date_param(get_str('from', 10));
    $to = ac_date_param(get_str('to', 10));
    if ($from !== '') {
        $where[] = 'p.created_at >= :dfrom';
        $params['dfrom'] = $from . ' 00:00:00';
    }
    if ($to !== '') {
        $where[] = 'p.created_at <= :dto';
        $params['dto'] = $to . ' 23:59:59';
    }
    return [implode(' AND ', $where), $params];
}

/** Add an entry to the payment's metadata "refunds" list and return the new metadata array. */
function ac_payment_note_refund(int $paymentId, array $entry): array
{
    $meta = json_decode((string)val('SELECT metadata FROM payments WHERE id = :id', ['id' => $paymentId], ''), true);
    $meta = is_array($meta) ? $meta : [];
    $meta['refunds'] = array_merge((array)($meta['refunds'] ?? []), [$entry]);
    q('UPDATE payments SET metadata = :m WHERE id = :id', ['m' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'id' => $paymentId]);
    return $meta;
}

/**
 * Decide a payment by hand. $act: approve | mark_paid | reject | cancel | refund. $in: note, reason, receipt, amount, reference, cancel_sub.
 * Returns [bool ok, string message]. Uses payment_complete(), payment_fail() and subscription_cancel() so fulfilment stays in one place.
 */
function ac_payment_decide(int $id, string $act, array $in, int $adminId): array
{
    $p = row('SELECT * FROM payments WHERE id = :id', ['id' => $id]);
    if (!$p) {
        return [false, 'That payment no longer exists.'];
    }
    $uid = (int)$p['user_id'];
    $note = trim(preg_replace('/\s+/', ' ', (string)($in['note'] ?? '')) ?? '');
    $reason = trim(preg_replace('/\s+/', ' ', (string)($in['reason'] ?? '')) ?? '');
    $who = $adminId > 0 ? (string)val('SELECT ' . ac_name_sql() . ' FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id', ['id' => $adminId], 'admin') : 'admin';

    if ($act === 'approve' || $act === 'mark_paid') {
        if ($p['status'] !== 'pending') {
            return [false, 'This payment is already ' . $p['status'] . '.'];
        }
        if (mb_strlen($note) > 160) {
            return [false, 'The note can be up to 160 characters.'];
        }
        $extra = ['admin_notes' => mb_substr(($act === 'approve' ? 'Approved' : 'Marked as paid') . ' by ' . $who . ($note !== '' ? ': ' . $note : ''), 0, 255)];
        $code = strtoupper(preg_replace('/\s+/', '', (string)($in['receipt'] ?? '')) ?? '');
        if ($act === 'mark_paid' && $code !== '') {
            if (!preg_match('/^[A-Z0-9]{6,20}$/', $code)) {
                return [false, 'An M-Pesa code is 6 to 20 letters and numbers, for example QGH7XK2LMN.'];
            }
            $dup = (int)val('SELECT id FROM payments WHERE id <> :id AND (UPPER(mpesa_receipt) = :c OR UPPER(bank_reference) = :c) LIMIT 1', ['id' => $id, 'c' => $code], 0);
            if ($dup) {
                return [false, 'That code is already on payment #' . $dup . '. Check it before using it again.'];
            }
            $extra['mpesa_receipt'] = $code;
        }
        if ($act === 'approve' && $p['payment_method'] === 'manual' && trim((string)$p['bank_reference']) !== '' && trim((string)$p['mpesa_receipt']) === '') {
            $extra['mpesa_receipt'] = strtoupper((string)$p['bank_reference']);
        }
        if ($act === 'mark_paid' && $note === '' && $code === '') {
            return [false, 'Add the M-Pesa code or a short note saying how you confirmed the money arrived.'];
        }
        [$ok, $result] = payment_complete($id, $extra, $adminId ?: null);
        return [(bool)$ok, $ok ? ($act === 'approve' ? 'Payment approved. ' : 'Payment marked as paid. ') . $result . '.' : 'Could not complete the payment: ' . $result];
    }

    if ($act === 'reject' || $act === 'cancel') {
        if ($p['status'] !== 'pending') {
            return [false, 'This payment is already ' . $p['status'] . '.'];
        }
        if (mb_strlen($reason) < 3) {
            return [false, $act === 'reject' ? 'Tell the member why it was not accepted.' : 'Add a short reason.'];
        }
        if (mb_strlen($reason) > 180) {
            return [false, 'The reason can be up to 180 characters.'];
        }
        payment_fail($id, $act === 'reject' ? 'failed' : 'cancelled', ($act === 'reject' ? 'Rejected: ' : 'Cancelled: ') . $reason);
        q('UPDATE payments SET admin_notes = :n, processed_by = :a WHERE id = :id', ['n' => mb_substr(($act === 'reject' ? 'Rejected' : 'Cancelled') . ' by ' . $who . ': ' . $reason, 0, 255), 'a' => $adminId ?: null, 'id' => $id]);
        if ($act === 'reject') {
            notify($uid, 'payment', 'We could not confirm your payment', 'Reason: ' . rtrim($reason, " .") . '. You can try again from Billing, or contact us if you think this is a mistake.', '/billing');
        }
        return [true, $act === 'reject' ? 'Payment rejected and the member was told why.' : 'Payment cancelled.'];
    }

    if ($act === 'refund') {
        if ($p['status'] !== 'completed') {
            return [false, $p['status'] === 'refunded' ? 'This payment has already been refunded.' : 'Only a completed payment can be refunded.'];
        }
        $meta = json_decode((string)$p['metadata'], true) ?: [];
        $already = 0.0;
        foreach ((array)($meta['refunds'] ?? []) as $r) {
            $already += (float)($r['amount'] ?? 0);
        }
        $left = round((float)$p['amount'] - $already, 2);
        $amount = ac_decimal($in['amount'] ?? '');
        if ($amount === null || $amount <= 0) {
            return [false, 'Enter the amount you sent back.'];
        }
        if ($amount > $left + 0.004) {
            return [false, 'You can refund up to ' . money($left) . ' on this payment.'];
        }
        if (mb_strlen($reason) < 3) {
            return [false, 'Add a short reason for the refund.'];
        }
        if (mb_strlen($reason) > 160) {
            return [false, 'The reason can be up to 160 characters.'];
        }
        $ref = strtoupper(preg_replace('/\s+/', '', (string)($in['reference'] ?? '')) ?? '');
        if ($ref !== '' && !preg_match('/^[A-Z0-9]{6,20}$/', $ref)) {
            return [false, 'The M-Pesa reference of the refund is 6 to 20 letters and numbers, or leave it blank.'];
        }
        $full = $amount + 0.004 >= $left;
        $changed = db_transaction(function () use ($id, $full, $amount, $reason, $ref, $adminId, $who, $already) {
            $upd = q('UPDATE payments SET status = :s, admin_notes = :n WHERE id = :id AND status = \'completed\'', [
                's' => $full ? 'refunded' : 'completed',
                'n' => mb_substr(($full ? 'Refunded ' : 'Part refund ') . money($amount) . ' by ' . $who . ': ' . $reason, 0, 255), 'id' => $id,
            ])->rowCount();
            if (!$upd && !$full) {
                $upd = 1; // status unchanged for a part refund, so MySQL may report 0 changed rows
            }
            if ($upd) {
                ac_payment_note_refund($id, ['amount' => $amount, 'reason' => $reason, 'reference' => $ref, 'by' => $adminId, 'at' => date('Y-m-d H:i:s')]);
            }
            return $upd;
        });
        if (!$changed) {
            return [false, 'This payment changed while you were working. Open it again and check.'];
        }
        $extraNote = '';
        if (!empty($in['cancel_sub']) && $p['payment_type'] === 'subscription') {
            subscription_cancel($uid);
            $extraNote = ' The member\'s subscription was cancelled.';
        }
        if ($p['payment_type'] === 'booking' && !empty($meta['booking_id'])) {
            q("UPDATE host_booking_ext SET refund_status = 'processed' WHERE booking_id = :b AND refund_status = 'due'", ['b' => (int)$meta['booking_id']]);
        }
        notify($uid, 'payment', 'Refund sent', money($amount) . ' was refunded for ' . ($p['description'] ?: 'your payment') . '.', '/billing');
        return [true, 'Refund of ' . money($amount) . ' recorded.' . $extraNote];
    }

    return [false, 'That action is not available.'];
}

// ---------------------------------------------------------------------------
// Adverts
// ---------------------------------------------------------------------------

/** SQL condition: the advert is being shown right now (the same rules as ad_pick() on the public side). */
function ac_ad_live_sql(string $a = 'a'): string
{
    return "($a.status = 'active' AND $a.starts_at <= NOW() AND $a.expires_at > NOW() AND ($a.max_views IS NULL OR $a.views < $a.max_views) AND ($a.max_clicks IS NULL OR $a.clicks < $a.max_clicks))";
}

/** Where an advert can be placed: value => label. */
function ac_ad_placements(): array
{
    return [
        'discover' => 'Dating discovery', 'search' => 'Search results', 'landing' => 'Landing page', 'hosts' => (function_exists('host_term') ? host_term(true) : 'Hosts') . ' directory',
        'messages' => 'Messages and billing', 'browse' => 'Dating browse pages', 'elite' => 'Elite Connections', 'global' => 'Everywhere',
    ];
}

// ---------------------------------------------------------------------------
// Bookings
// ---------------------------------------------------------------------------

/** FROM clause shared by the bookings list, export and detail page. Aliases: b, e, cu, cp, hu, hup, hp. */
function ac_bookings_from(): string
{
    return 'host_bookings b LEFT JOIN host_booking_ext e ON e.booking_id = b.id
        JOIN users cu ON cu.id = b.client_id LEFT JOIN user_profiles cp ON cp.user_id = cu.id
        JOIN users hu ON hu.id = b.host_id LEFT JOIN user_profiles hup ON hup.user_id = hu.id LEFT JOIN host_profiles hp ON hp.user_id = hu.id';
}

/** Name columns for the bookings FROM clause. */
function ac_bookings_names(): string
{
    return "COALESCE(NULLIF(cp.display_name, ''), cu.email) AS client_name, cu.email AS client_email,
        COALESCE(NULLIF(hp.display_name, ''), NULLIF(hup.display_name, ''), hu.email) AS host_name, hu.email AS host_email";
}

/** WHERE clause and parameters for the bookings list and export, from the query string. */
function ac_bookings_where(): array
{
    $where = ['1=1'];
    $params = [];
    $q = get_str('q', 100);
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(cu.email LIKE :q OR cp.display_name LIKE :q OR hu.email LIKE :q OR hp.display_name LIKE :q OR hup.display_name LIKE :q OR b.location_note LIKE :q' . (ctype_digit(ltrim($q, '#')) ? ' OR b.id = :qid' : '') . ')';
        $params['q'] = $like;
        if (ctype_digit(ltrim($q, '#'))) {
            $params['qid'] = (int)ltrim($q, '#');
        }
    }
    $status = get_str('status', 20);
    if (isset(HOSTS_BOOKING_STATUS[$status])) {
        $where[] = 'b.status = :st';
        $params['st'] = $status;
    }
    $refund = get_str('refund', 10);
    if (in_array($refund, ['due', 'processed'], true)) {
        $where[] = 'e.refund_status = :rf';
        $params['rf'] = $refund;
    }
    $from = ac_date_param(get_str('from', 10));
    $to = ac_date_param(get_str('to', 10));
    if ($from !== '') {
        $where[] = 'b.booking_date >= :dfrom';
        $params['dfrom'] = $from;
    }
    if ($to !== '') {
        $where[] = 'b.booking_date <= :dto';
        $params['dto'] = $to;
    }
    return [implode(' AND ', $where), $params];
}
