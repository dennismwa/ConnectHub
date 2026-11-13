<?php
/**
 * Admin panel core helpers (builder D1).
 *
 * Contract function: admin_pending_counts(). Everything else is prefixed admin_core_ so it can be
 * reused by the other admin pages without name clashes.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Contract: pending work counters for the sidebar badges and the dashboard queue
// ---------------------------------------------------------------------------

/**
 * Counts of items waiting for an admin. Cached for 30 seconds.
 * Keys: hosts_pending, host_reviews_pending, verifications_pending, photos_pending, reports_open, payments_pending, payouts_pending, contact_unread
 */
function admin_pending_counts(): array
{
    $keys = ['hosts_pending', 'host_reviews_pending', 'verifications_pending', 'photos_pending', 'reports_open', 'payments_pending', 'payouts_pending', 'wallet_pending', 'intl_pending', 'elite_pending', 'contact_unread'];
    if (!is_file(config_file())) {
        return array_fill_keys($keys, 0);
    }
    try {
        return cache_remember('admin_pending', 30, function () {
            $count = function (string $sql): int {
                try {
                    return (int)val($sql, [], 0);
                } catch (Throwable $e) {
                    admin_core_note_failure($sql, $e);
                    return 0;
                }
            };
            return [
                'hosts_pending' => $count("SELECT COUNT(*) FROM host_profiles hp JOIN users u ON u.id = hp.user_id WHERE hp.approval_status = 'pending' AND u.status <> 'banned'"),
                'host_reviews_pending' => $count("SELECT COUNT(*) FROM host_reviews WHERE status = 'pending'"),
                'verifications_pending' => $count("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'"),
                'photos_pending' => $count("SELECT COUNT(*) FROM user_photos WHERE moderation_status = 'pending'"),
                'reports_open' => $count("SELECT COUNT(*) FROM reports WHERE status IN ('pending','reviewing')"),
                'payments_pending' => $count("SELECT COUNT(*) FROM payments WHERE status = 'pending' AND payment_method <> 'mpesa'"),
                'payouts_pending' => $count("SELECT COUNT(*) FROM host_payouts WHERE status = 'requested'"),
                'intl_pending' => $count("SELECT COUNT(*) FROM intl_profiles WHERE status = 'pending'"),
                'elite_pending' => function_exists('mig_table_ready') && mig_table_ready('elite_profiles') ? $count("SELECT COUNT(*) FROM elite_profiles WHERE status = 'pending'") : 0,
                'wallet_pending' => $count("SELECT COUNT(*) FROM wallet_payouts WHERE status = 'requested'"),
                'contact_unread' => $count("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'"),
            ];
        });
    } catch (Throwable $e) {
        return array_fill_keys($keys, 0);
    }
}

/** Drop the cached counters (call after approving or rejecting something). */
function admin_core_counts_forget(): void
{
    cache_forget('admin_pending');
    cache_forget('admin_hosts_stats');
}

// ---------------------------------------------------------------------------
// Modules and permissions
// ---------------------------------------------------------------------------

function admin_core_levels(): array
{
    return ['none' => 'No access', 'view' => 'View', 'edit' => 'Edit', 'full' => 'Full'];
}

function admin_core_level_rank(string $level): int
{
    return ['none' => 0, 'view' => 1, 'edit' => 2, 'full' => 3][$level] ?? 0;
}

/**
 * Every permission module known to the panel: [module => [label, group, description]].
 * Mirrors templates/partials/admin_nav.php and also picks up any module used by an admin route.
 */
function admin_core_modules(): array
{
    $m = [
        'dashboard' => ['Dashboard', 'Overview', 'The home screen with KPIs, queues and system health.'],
        'analytics' => ['Analytics', 'Overview', 'Growth, engagement, revenue and funnel reports and CSV exports.'],
        'users' => ['Members', 'People', 'Member list and profiles. Edit suspends, verifies and grants plans. Full can also delete accounts and export data.'],
        'hosts' => ['Hosts', 'People', 'Host applications and profiles. Edit approves, rejects and suspends.'],
        'verifications' => ['Verifications', 'People', 'Selfie and ID checks for the verified badge.'],
        'moderation' => ['Photo review', 'People', 'Approve or reject uploaded photos.'],
        'reports' => ['Reports', 'People', 'Member reports and the actions taken on them.'],
        'payments' => ['Payments', 'Money', 'Payments and manual payment approvals.'],
        'subscriptions' => ['Subscriptions', 'Money', 'Member subscriptions.'],
        'plans' => ['Plans', 'Money', 'Plan pricing and features.'],
        'elite' => ['Elite', 'People', 'Elite profiles, requests, unlocks and the Elite setup.'],
        'bookings' => ['Bookings', 'Money', 'Host bookings.'],
        'payouts' => ['Payouts', 'Money', 'Host payout requests.'],
        'ads' => ['Adverts', 'Money', 'Advertisers and campaigns.'],
        'gifts' => ['Gifts', 'Money', 'Gift catalogue and sent gifts.'],
        'landing' => ['Hero and landing', 'Site', 'Landing page sections and hero slides.'],
        'nav' => ['Navigation', 'Site', 'Header, footer and app menus.'],
        'content' => ['Pages, blog, FAQs', 'Site', 'Pages, blog posts, FAQs, testimonials and conversation starters.'],
        'media' => ['Media library', 'Site', 'Pictures and videos used across the site. Edit can rename, upload and delete unused files.'],
        'interests' => ['Interests', 'Site', 'Interest catalogue.'],
        'locations' => ['Locations', 'Site', 'Counties and towns.'],
        'messages' => ['Contact messages', 'Inbox', 'Messages from the contact form.'],
        'newsletter' => ['Newsletter', 'Inbox', 'Subscribers and broadcasts.'],
        'settings' => ['Settings', 'System', 'Every site setting. Some tabs (payments, email, safety) need Full.'],
        'roles' => ['Admins and roles', 'System', 'Admin accounts and the permission matrix. Needs Full to change.'],
        'logs' => ['Activity log', 'System', 'Audit trail. Edit also opens the server log files.'],
        'tools' => ['Tools', 'System', 'Cache, backups, demo data and maintenance. Full runs the heavy actions.'],
    ];
    // Modules used by routes registered elsewhere (other builders) show up in the matrix automatically.
    foreach (route_table() as $r) {
        $mod = $r['opts']['perm'][0] ?? '';
        if ($mod !== '' && !isset($m[$mod]) && $mod !== '*') {
            $m[$mod] = [ucfirst(str_replace('_', ' ', $mod)), 'Other', 'Added by a module.'];
        }
    }
    return $m;
}

/** Permission map of an admin role id. NULL means no role: full access. */
function admin_core_role_perms(?int $roleId): array
{
    if (!$roleId) {
        return ['*' => 'full'];
    }
    $json = (string)val('SELECT permissions FROM admin_roles WHERE id = :id', ['id' => $roleId], '{}');
    $d = json_decode($json, true);
    return is_array($d) ? $d : [];
}

function admin_core_perm_level(array $perms, string $module): string
{
    $l = $perms[$module] ?? ($perms['*'] ?? 'none');
    return isset(admin_core_levels()[$l]) ? $l : 'none';
}

/** Admin ids that can manage roles (so the panel can never lock everyone out). */
function admin_core_full_admin_ids(array $excludeIds = [], array $roleOverride = []): array
{
    $out = [];
    foreach (rows("SELECT id, admin_role_id FROM users WHERE role = 'admin' AND status = 'active'") as $a) {
        if (in_array((int)$a['id'], $excludeIds, true)) {
            continue;
        }
        $rid = $a['admin_role_id'] !== null ? (int)$a['admin_role_id'] : null;
        $perms = ($rid !== null && isset($roleOverride[$rid])) ? $roleOverride[$rid] : admin_core_role_perms($rid);
        if (admin_core_perm_level($perms, 'roles') === 'full') {
            $out[] = (int)$a['id'];
        }
    }
    return $out;
}

/** Cached "does this column exist" check for optional columns added by other modules. */
function admin_core_column_exists(string $table, string $column): bool
{
    static $cache = [];
    if (!preg_match('/^[a-z0-9_]+$/i', $table)) {
        return false;
    }
    if (!isset($cache[$table])) {
        try {
            $cache[$table] = array_map('strtolower', col('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t', ['t' => $table]));
        } catch (Throwable $e) {
            $cache[$table] = [];
        }
    }
    return in_array(strtolower($column), $cache[$table], true);
}

function admin_core_table_exists(string $table): bool
{
    static $cache = null;
    if ($cache === null) {
        try {
            $cache = array_map('strtolower', col('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'));
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return in_array(strtolower($table), $cache, true);
}

// ---------------------------------------------------------------------------
// Small UI helpers
// ---------------------------------------------------------------------------

function admin_core_num($n): string
{
    return number_format((float)$n, 0, '.', ',');
}

/** @return array ['pct' => float|null, 'dir' => 'up'|'down'|'flat', 'label' => string] */
function admin_core_delta(float $cur, float $prev): array
{
    if ($prev <= 0) {
        return ['pct' => null, 'dir' => $cur > 0 ? 'up' : 'flat', 'label' => $cur > 0 ? 'New' : '0%'];
    }
    $pct = ($cur - $prev) / $prev * 100;
    $dir = abs($pct) < 0.5 ? 'flat' : ($pct > 0 ? 'up' : 'down');
    return ['pct' => $pct, 'dir' => $dir, 'label' => ($pct > 0 ? '+' : '') . round($pct) . '%'];
}

function admin_core_badge(string $label, string $tone = '', string $iconName = ''): string
{
    $cls = 'badge' . ($tone !== '' ? ' badge-' . $tone : '');
    return '<span class="' . e($cls) . '">' . ($iconName !== '' ? icon($iconName, 'w-3 h-3') : '') . e($label) . '</span>';
}

/** Coloured badge for any status word used in the panel. */
function admin_core_status_badge(string $status, ?string $label = null): string
{
    $tones = [
        'active' => 'success', 'approved' => 'success', 'completed' => 'success', 'resolved' => 'success', 'paid' => 'success', 'delivered' => 'success', 'published' => 'success', 'confirmed' => 'success', 'replied' => 'success',
        'pending' => 'warning', 'requested' => 'warning', 'reviewing' => 'info', 'draft' => '', 'new' => 'info', 'awaiting_payment' => 'warning', 'read' => '',
        'suspended' => 'warning', 'banned' => 'danger', 'rejected' => 'danger', 'failed' => 'danger', 'cancelled' => '', 'declined' => 'danger', 'refunded' => 'info',
        'dismissed' => '', 'deactivated' => '', 'expired' => '', 'archived' => '', 'no_show' => 'danger',
    ];
    $labels = ['awaiting_payment' => 'Awaiting payment', 'no_show' => 'No show'];
    $tone = $tones[$status] ?? '';
    $text = $label ?? ($labels[$status] ?? ucfirst(str_replace('_', ' ', $status)));
    return '<span class="badge' . ($tone !== '' ? ' badge-' . $tone : '') . '"><span class="badge-dot"></span>' . e($text) . '</span>';
}

function admin_core_role_badge(string $role): string
{
    $map = ['user' => ['Explorer', ''], 'host' => ['Host', 'primary'], 'admin' => ['Admin', 'vip']];
    [$label, $tone] = $map[$role] ?? [ucfirst($role), ''];
    return admin_core_badge($label, $tone);
}

function admin_core_tier_badge(string $tier): string
{
    return $tier === 'free' ? '<span class="badge">Free</span>' : tier_badge($tier);
}

/** Round avatar from a stored thumb path, or initials. $size: xs|sm|md|lg|xl */
function admin_core_avatar(?string $thumb, string $name, string $size = 'sm'): string
{
    $size = in_array($size, ['xs', 'sm', 'md', 'lg', 'xl', '2xl'], true) ? $size : 'sm';
    if ($thumb) {
        return '<span class="avatar avatar-' . $size . '"><img src="' . e(upload_url($thumb)) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="avatar avatar-' . $size . '"><span class="avatar-initials w-full h-full">' . e(initials($name !== '' ? $name : '?')) . '</span></span>';
}

function admin_core_pager(array $p): string
{
    if ($p['pages'] <= 1) {
        return '<p class="text-xs text-ink-3 text-center mt-4">' . admin_core_num($p['total']) . ' ' . ($p['total'] === 1 ? 'result' : 'results') . '</p>';
    }
    $page = $p['page'];
    $pages = $p['pages'];
    $items = [];
    $link = function (int $n, string $text, string $cls = '', string $aria = '') {
        return '<a href="' . e(url_with(['page' => $n > 1 ? $n : null])) . '" class="' . $cls . '"' . ($aria !== '' ? ' aria-label="' . e($aria) . '"' : '') . '>' . $text . '</a>';
    };
    $items[] = $p['has_prev'] ? $link($page - 1, icon('chevron-left', 'w-4 h-4'), '', 'Previous page') : '<span class="is-disabled">' . icon('chevron-left', 'w-4 h-4') . '</span>';
    $window = [1, $pages];
    for ($i = $page - 2; $i <= $page + 2; $i++) {
        if ($i > 1 && $i < $pages) {
            $window[] = $i;
        }
    }
    $window = array_values(array_unique($window));
    sort($window);
    $prev = 0;
    foreach ($window as $n) {
        if ($prev && $n - $prev > 1) {
            $items[] = '<span class="is-disabled">…</span>';
        }
        $items[] = $n === $page ? '<span class="is-current" aria-current="page">' . $n . '</span>' : $link($n, (string)$n);
        $prev = $n;
    }
    $items[] = $p['has_next'] ? $link($page + 1, icon('chevron-right', 'w-4 h-4'), '', 'Next page') : '<span class="is-disabled">' . icon('chevron-right', 'w-4 h-4') . '</span>';
    $from = $p['offset'] + 1;
    $to = min($p['total'], $p['offset'] + $p['per_page']);
    return '<nav class="pager" aria-label="Pagination">' . implode('', $items) . '</nav><p class="text-xs text-ink-3 text-center mt-3">Showing ' . admin_core_num($from) . ' to ' . admin_core_num($to) . ' of ' . admin_core_num($p['total']) . '</p>';
}

function admin_core_empty(string $iconName, string $title, string $text = '', string $actionHtml = ''): string
{
    return '<div class="empty"><div class="empty-icon">' . icon($iconName, 'w-7 h-7') . '</div><h3>' . e($title) . '</h3>'
        . ($text !== '' ? '<p class="max-w-md">' . e($text) . '</p>' : '') . $actionHtml . '</div>';
}

/** Set the page title and load the admin script. $meta takes the same keys as page(). */
function admin_core_page(string $title, array $meta = []): void
{
    $js = array_merge(['js/admin.js'], (array)($meta['js'] ?? []));
    unset($meta['js']);
    page(['title' => $title, 'js' => $js] + $meta);
}

/** SQL expression for the day a completed payment counts as revenue. */
function admin_core_pay_date(string $alias = 'p'): string
{
    return 'COALESCE(' . $alias . '.fulfilled_at, ' . $alias . '.processed_at, ' . $alias . '.created_at)';
}

/** Run a scalar query and return $default when the table or column does not exist yet. */
/**
 * Queries that failed on this page. The "try" helpers below keep a page working when one query breaks, but they used to do it
 * silently, which hid whole sections. Failures are now logged and listed at the top of the page (see admin_core_failure_notice()).
 */
function admin_core_query_failures(?string $add = null): array
{
    static $list = [];
    if ($add !== null && count($list) < 12) {
        $list[md5($add)] = $add;
    }
    return array_values($list);
}

function admin_core_note_failure(string $sql, Throwable $e): void
{
    $msg = preg_replace('/\s+/', ' ', $e->getMessage());
    $from = trim(preg_replace('/\s+/', ' ', substr($sql, 0, 90)));
    app_log('error', 'Admin query failed: ' . $msg . ' | ' . $from . ' | ' . request_path());
    admin_core_query_failures(mb_substr($msg, 0, 200) . '  (' . $from . ')');
}

function admin_core_try_val(string $sql, array $params = [], $default = 0)
{
    try {
        return val($sql, $params, $default);
    } catch (Throwable $e) {
        admin_core_note_failure($sql, $e);
        return $default;
    }
}

function admin_core_try_rows(string $sql, array $params = []): array
{
    try {
        return rows($sql, $params);
    } catch (Throwable $e) {
        admin_core_note_failure($sql, $e);
        return [];
    }
}

/** A short box for admins when something on the page could not be loaded, with the reason and what to do. */
function admin_core_failure_notice(): string
{
    $f = admin_core_query_failures();
    if (!$f) {
        return '';
    }
    $h = '<section class="cp-callout is-warn mb-5" role="alert"><span class="shrink-0">' . icon('alert-triangle', 'w-5 h-5') . '</span><div class="min-w-0"><p class="font-semibold">Some information on this page could not be loaded</p>'
        . '<p class="text-sm mt-0.5">The rest of the page works. This usually means the database is missing a recent update. Open <a class="cp-link font-medium" href="' . e(url('/admin/tools')) . '">Tools</a> and run <b>Check and repair the database</b>, then reload.</p><ul class="text-xs mt-2 space-y-1 break-words">';
    foreach ($f as $line) {
        $h .= '<li>' . e($line) . '</li>';
    }
    return $h . '</ul></div></section>';
}

/** Current path with its query string, as a site-relative URL (for "come back here" links). */
function admin_core_here(): string
{
    $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
    return request_path() . ($qs !== '' ? '?' . $qs : '');
}

/** Redirect target from a posted "back" field: only paths inside /admin are accepted. */
function admin_core_back(string $fallback): string
{
    $b = safe_next((string)post('back', ''), $fallback);
    return strpos($b, '/admin') === 0 ? $b : $fallback;
}

/** In-page header under the top bar: optional breadcrumb, a sentence about the page and action buttons. */
function admin_core_head(string $sub = '', string $actionsHtml = '', array $crumbs = []): string
{
    $out = '<div class="cp-head">';
    $out .= '<div class="min-w-0">';
    if ($crumbs) {
        $out .= '<nav class="cp-crumbs" aria-label="Breadcrumb">';
        foreach ($crumbs as $i => [$label, $href]) {
            $out .= ($i ? '<span class="sep">/</span>' : '') . ($href ? '<a href="' . e(url($href)) . '">' . e($label) . '</a>' : '<span>' . e($label) . '</span>');
        }
        $out .= '</nav>';
    }
    if ($sub !== '') {
        $out .= '<p class="cp-head-sub">' . e($sub) . '</p>';
    }
    $out .= '</div>';
    if ($actionsHtml !== '') {
        $out .= '<div class="cp-head-actions">' . $actionsHtml . '</div>';
    }
    return $out . '</div>';
}

/** Clean up a free-text search term used in LIKE patterns. */
function admin_core_like(string $term): string
{
    return '%' . addcslashes(trim($term), '%_\\') . '%';
}

/** CSV cell safe against spreadsheet formula injection. */
function admin_core_csv_cell($v): string
{
    if ($v === null) {
        return '';
    }
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    $s = (string)$v;
    if ($s !== '' && !is_numeric($s) && strpos("=+-@\t\r", $s[0]) !== false) {
        return "'" . $s;
    }
    return $s;
}

/** Stream a CSV download and stop. $rows is any iterable of arrays. */
function admin_core_csv(string $filename, array $headers, iterable $rows): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) ?: 'export.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, array_map('admin_core_csv_cell', array_values($r)));
    }
    fclose($out);
    exit;
}

/** Date list from $from to $to inclusive (Y-m-d). */
function admin_core_dates(string $from, string $to): array
{
    $out = [];
    $d = new DateTimeImmutable($from);
    $end = new DateTimeImmutable($to);
    for ($i = 0; $d <= $end && $i < 800; $i++, $d = $d->modify('+1 day')) {
        $out[] = $d->format('Y-m-d');
    }
    return $out;
}

/** Mini sparkline (inline SVG, no hover). */
function admin_core_spark(array $values, string $color = 'rgb(var(--primary-500))', int $w = 96, int $h = 30): string
{
    $values = array_values(array_map('floatval', $values));
    $n = count($values);
    if ($n < 2) {
        return '';
    }
    $max = max($values);
    $min = min($values);
    $range = $max - $min;
    $pts = [];
    foreach ($values as $i => $v) {
        $x = $i / ($n - 1) * ($w - 4) + 2;
        $y = $range > 0 ? ($h - 5) - ($v - $min) / $range * ($h - 10) + 2.5 : $h / 2;
        $pts[] = round($x, 1) . ',' . round($y, 1);
    }
    $last = explode(',', end($pts));
    $area = 'M2,' . ($h - 1) . ' L' . implode(' L', $pts) . ' L' . ($w - 2) . ',' . ($h - 1) . ' Z';
    return '<svg class="cp-spark" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" aria-hidden="true" style="color:' . e($color) . '">'
        . '<path d="' . $area . '" fill="currentColor" opacity=".1"/><polyline points="' . implode(' ', $pts) . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>'
        . '<circle cx="' . $last[0] . '" cy="' . $last[1] . '" r="3" fill="currentColor" stroke="rgb(var(--card))" stroke-width="2"/></svg>';
}

/**
 * Chart container drawn in the browser by assets/js/admin.js (plain SVG) with a table view for accessibility.
 * $cfg: type (line|bar|stack), labels[], series[ [name, values[], color?] ], format (int|money|pct), height, title, labelFormat (date|text)
 */
function admin_core_chart(array $cfg): string
{
    $cfg += ['type' => 'line', 'labels' => [], 'series' => [], 'format' => 'int', 'height' => 220, 'title' => 'Chart', 'labelFormat' => 'date'];
    $id = 'chart-' . substr(md5(json_encode($cfg) . random_token(2)), 0, 8);
    $table = '<table class="table"><thead><tr><th></th>';
    foreach ($cfg['series'] as $s) {
        $table .= '<th class="num">' . e($s['name']) . '</th>';
    }
    $table .= '</tr></thead><tbody>';
    foreach ($cfg['labels'] as $i => $l) {
        $table .= '<tr><td>' . e($l) . '</td>';
        foreach ($cfg['series'] as $s) {
            $v = $s['values'][$i] ?? 0;
            $table .= '<td class="num">' . e($cfg['format'] === 'money' ? money($v) : ($cfg['format'] === 'pct' ? round((float)$v, 1) . '%' : admin_core_num($v))) . '</td>';
        }
        $table .= '</tr>';
    }
    $table .= '</tbody></table>';
    $legend = '';
    if (count($cfg['series']) > 1) {
        $legend = '<ul class="cp-legend">';
        foreach ($cfg['series'] as $i => $s) {
            $legend .= '<li><i style="background:' . e($s['color'] ?? 'var(--viz-' . ($i + 1) . ')') . '"></i>' . e($s['name']) . '</li>';
        }
        $legend .= '</ul>';
    }
    $data = ['type' => $cfg['type'], 'labels' => array_values($cfg['labels']), 'format' => $cfg['format'], 'labelFormat' => $cfg['labelFormat'], 'height' => (int)$cfg['height'],
        'symbol' => (string)setting('currency_symbol'),
        'series' => array_map(fn($s) => ['name' => $s['name'], 'values' => array_map('floatval', array_values($s['values'])), 'color' => $s['color'] ?? null], $cfg['series'])];
    return '<figure class="cp-chart" id="' . e($id) . '" data-chart="' . json_attr($data) . '">'
        . $legend
        . '<div class="cp-chart-canvas" style="height:' . (int)$cfg['height'] . 'px" role="img" aria-label="' . e($cfg['title']) . '"><div class="skeleton w-full h-full"></div></div>'
        . '<details class="cp-chart-table"><summary>View as table</summary><div class="table-wrap">' . $table . '</div></details></figure>';
}

/** Horizontal bar rows (server drawn, no JS). $rows: [[label, value, hint?]] */
function admin_core_hbars(array $rows, string $format = 'int', ?float $max = null): string
{
    if (!$rows) {
        return '<p class="text-sm text-ink-3 py-4">No data for this period.</p>';
    }
    $max = $max ?? max(array_map(fn($r) => (float)$r[1], $rows));
    $max = $max > 0 ? $max : 1;
    $out = '<ul class="cp-hbars">';
    foreach ($rows as $r) {
        $v = (float)$r[1];
        $pct = max(0.5, $v / $max * 100);
        $val = $format === 'money' ? money($v) : ($format === 'pct' ? round($v, 1) . '%' : admin_core_num($v));
        $out .= '<li><span class="cp-hbar-label" title="' . e((string)$r[0]) . '">' . e((string)$r[0]) . '</span><span class="cp-hbar-track"><span style="width:' . round($pct, 1) . '%"></span></span><span class="cp-hbar-val">' . e($val) . (isset($r[2]) && $r[2] !== '' ? '<small>' . e((string)$r[2]) . '</small>' : '') . '</span></li>';
    }
    return $out . '</ul>';
}

// ---------------------------------------------------------------------------
// Private files (verification selfies, host ID documents) and logs
// ---------------------------------------------------------------------------

function admin_core_private_base(): string
{
    $b = ROOT . '/storage/private';
    if (!is_dir($b)) {
        @mkdir($b, 0775, true);
    }
    return (string)realpath($b);
}

/**
 * Resolve a stored path to a real file that lives inside storage/private, or null.
 * Accepts "verification/x.jpg", "private/verification/x.jpg", "storage/private/verification/x.jpg" or an absolute path.
 */
function admin_core_private_resolve(?string $stored): ?string
{
    $stored = trim((string)$stored);
    if ($stored === '' || strpos($stored, "\0") !== false) {
        return null;
    }
    $base = admin_core_private_base();
    if ($base === '') {
        return null;
    }
    $candidates = [];
    if ($stored[0] === '/' && strpos($stored, $base) === 0) {
        $candidates[] = $stored;
    } else {
        $rel = ltrim($stored, '/');
        $rel = preg_replace('#^(storage/private/|private/)#', '', $rel);
        $candidates[] = $base . '/' . $rel;
    }
    foreach ($candidates as $c) {
        $real = realpath($c);
        if ($real !== false && strpos($real, $base . DIRECTORY_SEPARATOR) === 0 && is_file($real)) {
            return $real;
        }
    }
    return null;
}

/** Stream a file from storage/private to an authorised admin and stop. */
function admin_core_stream_private(?string $stored): void
{
    $file = admin_core_private_resolve($stored);
    if ($file === null) {
        abort(404, 'That file is not available.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'], true)) {
        abort(404, 'That file type cannot be shown.');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($file));
    header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file)) . '"');
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");
    readfile($file);
    exit;
}

/**
 * Private files of a host: ['id' => stored path|null, 'selfie' => stored path|null].
 * Uses hosts_id_file_path() from the Hosts module when it exists, then falls back to storage/private/host_id/.
 */
function admin_core_host_files(int $hostId): array
{
    $out = ['id' => null, 'selfie' => null];
    if (function_exists('hosts_id_file_path')) {
        foreach (['id', 'selfie'] as $kind) {
            try {
                $r = hosts_id_file_path($hostId, $kind);
                if (is_string($r) && $r !== '') {
                    $out[$kind] = $r;
                }
            } catch (Throwable $e) {
                app_log('error', 'hosts_id_file_path failed: ' . $e->getMessage());
            }
        }
    }
    $dir = admin_core_private_base() . '/host_id';
    if (is_dir($dir)) {
        foreach (['id' => ['{u}/id_*.*', '{u}_id.*', '{u}-id.*'], 'selfie' => ['{u}/selfie_*.*', '{u}_selfie.*', '{u}-selfie.*']] as $kind => $patterns) {
            if ($out[$kind] !== null) {
                continue;
            }
            foreach ($patterns as $pat) {
                $hit = glob($dir . '/' . str_replace('{u}', (string)$hostId, $pat)) ?: [];
                if ($hit) {
                    usort($hit, fn($a, $b) => filemtime($b) <=> filemtime($a));
                    $out[$kind] = $hit[0];
                    break;
                }
            }
        }
    }
    return $out;
}

function admin_core_log_files(): array
{
    $out = [];
    foreach (glob(ROOT . '/storage/logs/*.log') ?: [] as $f) {
        $out[basename($f)] = ['name' => basename($f), 'size' => (int)filesize($f), 'mtime' => (int)filemtime($f)];
    }
    ksort($out);
    return $out;
}

/** Path of a log file by name, or null when the name is not a plain *.log file in storage/logs. */
function admin_core_log_path(string $name): ?string
{
    if (!preg_match('/^[A-Za-z0-9_.-]+\.log$/', $name) || strpos($name, '..') !== false) {
        return null;
    }
    $file = ROOT . '/storage/logs/' . $name;
    $real = realpath($file);
    $base = realpath(ROOT . '/storage/logs');
    if ($real === false || $base === false || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)) {
        return null;
    }
    return $real;
}

/** Last lines of a log file, optionally filtered (case-insensitive substring). Reads at most ~600 KB from the end. */
function admin_core_log_tail(string $path, int $lines = 200, string $filter = ''): array
{
    $size = (int)filesize($path);
    if ($size <= 0) {
        return [];
    }
    $read = min($size, 600000);
    $fh = fopen($path, 'rb');
    if (!$fh) {
        return [];
    }
    if ($read < $size) {
        fseek($fh, $size - $read);
    }
    $chunk = (string)fread($fh, $read);
    fclose($fh);
    $all = preg_split('/\R/', $chunk) ?: [];
    if ($read < $size) {
        array_shift($all); // first line is probably cut
    }
    $all = array_values(array_filter($all, fn($l) => $l !== ''));
    if ($filter !== '') {
        $all = array_values(array_filter($all, fn($l) => stripos($l, $filter) !== false));
    }
    return array_slice($all, -max(10, min(1000, $lines)));
}

// ---------------------------------------------------------------------------
// Members: shared actions
// ---------------------------------------------------------------------------

/** User row with profile, location and primary photo for admin pages. */
function admin_core_user(int $id): ?array
{
    return row('SELECT u.*, p.display_name, p.date_of_birth, p.gender, p.interested_in, p.bio, p.occupation, p.education, p.height_cm, p.body_type, p.relationship_status,
            p.intent, p.looking_for, p.languages, p.religion, p.profile_completion, p.is_verified, p.verified_at, p.onboarding_completed, p.onboarding_step, p.is_paused, p.is_incognito,
            p.contact_phone, p.contact_whatsapp, p.contact_email, p.contact_instagram, p.show_contact,
            l.county_name, l.town_name, l.latitude, l.longitude, l.source AS location_source, l.updated_at AS location_updated,
            (SELECT ph.thumb_path FROM user_photos ph WHERE ph.user_id = u.id AND ph.is_primary = 1 LIMIT 1) AS avatar_thumb
        FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id LEFT JOIN user_locations l ON l.user_id = u.id WHERE u.id = :id', ['id' => $id]);
}

function admin_core_user_name(array $u): string
{
    $n = trim((string)($u['display_name'] ?? ''));
    return $n !== '' ? $n : explode('@', (string)($u['email'] ?? 'member'))[0];
}

/** Send a short account notice by e-mail (members who cannot sign in never see in-app notifications). */
function admin_core_mail_user(int $userId, string $subject, string $title, string $body): void
{
    try {
        $u = row('SELECT u.email, p.display_name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id', ['id' => $userId]);
        if ($u) {
            send_template_mail($u['email'], $subject, 'notification', ['name' => $u['display_name'] ?: 'there', 'title' => $title, 'body' => $body, 'url' => abs_url('/')]);
        }
    } catch (Throwable $e) {
        app_log('mail', 'admin notice failed: ' . $e->getMessage());
    }
}

/**
 * Guard for actions on an account. Returns an error message or '' when the actor may touch the target.
 * Admin accounts need roles:full, and nobody may act on themselves or on the last full admin.
 */
function admin_core_target_problem(array $target, int $actorId, string $what = 'change'): string
{
    if ((int)$target['id'] === $actorId) {
        return 'You cannot ' . $what . ' your own account here.';
    }
    if ($target['role'] === 'admin') {
        if (!admin_can('roles', 'full')) {
            return 'Only admins with full access to Admins and roles can ' . $what . ' another admin.';
        }
        if (in_array((int)$target['id'], admin_core_full_admin_ids(), true) && !admin_core_full_admin_ids([(int)$target['id']])) {
            return 'This is the last admin who can manage roles, so it cannot be ' . $what . 'd.';
        }
    }
    return '';
}

/**
 * Change an account status. Returns [bool ok, string message].
 * $status: active | suspended | banned
 */
function admin_core_user_set_status(int $userId, string $status, string $reason, int $actorId): array
{
    if (!in_array($status, ['active', 'suspended', 'banned'], true)) {
        return [false, 'Unknown status.'];
    }
    $u = row('SELECT id, email, role, status FROM users WHERE id = :id', ['id' => $userId]);
    if (!$u) {
        return [false, 'Member not found.'];
    }
    $problem = $status === 'active' ? '' : admin_core_target_problem($u, $actorId, $status === 'banned' ? 'ban' : 'suspend');
    if ($problem !== '') {
        return [false, $problem];
    }
    $reason = trim(mb_substr($reason, 0, 250));
    if ($status !== 'active' && $reason === '') {
        return [false, 'Add a reason. The member will see it.'];
    }
    q('UPDATE users SET status = :s, status_reason = :r WHERE id = :id', ['s' => $status, 'r' => $status === 'active' ? null : $reason, 'id' => $userId]);
    if ($status !== 'active') {
        delete_rows('user_tokens', ['user_id' => $userId, 'type' => 'remember']);
    }
    if ($status === 'active' && $u['status'] === 'deactivated') {
        q('UPDATE user_profiles SET is_paused = 0 WHERE user_id = :id', ['id' => $userId]);
    }
    admin_log('user.' . ($status === 'active' ? 'restore' : ($status === 'banned' ? 'ban' : 'suspend')), 'user', $userId, $reason !== '' ? $reason : 'Status set to ' . $status);
    if ($status === 'active') {
        notify($userId, 'system', 'Your account is active again', 'You can use ' . site_name() . ' as usual.', '/');
        admin_core_mail_user($userId, 'Your account is active again', 'Your account is active again', 'Your ' . site_name() . ' account has been restored. You can sign in again.');
    } else {
        $title = $status === 'banned' ? 'Your account has been closed' : 'Your account is suspended';
        $body = 'Reason: ' . $reason . '. Reply to this message or contact support if you think this is a mistake.';
        notify($userId, 'system', $title, $body, '/contact');
        admin_core_mail_user($userId, $title, $title, $body);
    }
    return [true, $status === 'active' ? 'Account restored.' : ($status === 'banned' ? 'Account banned.' : 'Account suspended.')];
}

/** What a hard delete would remove, plus warnings. */
function admin_core_user_cascade(int $userId): array
{
    $c = fn(string $sql) => (int)val($sql, ['u' => $userId], 0);
    $paid = row("SELECT COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total FROM payments WHERE user_id = :u AND status = 'completed'", ['u' => $userId]) ?: ['n' => 0, 'total' => 0];
    return [
        'photos' => $c('SELECT COUNT(*) FROM user_photos WHERE user_id = :u'),
        'messages' => $c('SELECT COUNT(*) FROM messages WHERE sender_id = :u'),
        'conversations' => $c('SELECT COUNT(*) FROM conversations WHERE user1_id = :u OR user2_id = :u'),
        'matches' => $c('SELECT COUNT(*) FROM matches WHERE user1_id = :u OR user2_id = :u'),
        'swipes' => $c('SELECT COUNT(*) FROM swipes WHERE swiper_id = :u OR swiped_id = :u'),
        'payments' => $c('SELECT COUNT(*) FROM payments WHERE user_id = :u'),
        'payments_completed' => (int)$paid['n'],
        'payments_total' => (float)$paid['total'],
        'subscriptions' => $c('SELECT COUNT(*) FROM user_subscriptions WHERE user_id = :u'),
        'bookings' => admin_core_table_exists('host_bookings') ? $c('SELECT COUNT(*) FROM host_bookings WHERE host_id = :u OR client_id = :u') : 0,
        'reports' => $c('SELECT COUNT(*) FROM reports WHERE reporter_id = :u OR reported_id = :u'),
        'notifications' => $c('SELECT COUNT(*) FROM notifications WHERE user_id = :u'),
    ];
}

/** Remove every stored file that belongs to a member (photos, private documents). */
function admin_core_user_files_delete(int $userId): void
{
    foreach (rows('SELECT large_path, medium_path, thumb_path, blur_path FROM user_photos WHERE user_id = :u', ['u' => $userId]) as $p) {
        foreach ($p as $path) {
            upload_delete((string)$path);
        }
    }
    foreach (rows('SELECT selfie_path, id_document_path FROM verification_requests WHERE user_id = :u', ['u' => $userId]) as $v) {
        foreach ($v as $path) {
            $f = admin_core_private_resolve((string)$path);
            if ($f) {
                @unlink($f);
            }
        }
    }
    foreach (['id', 'selfie'] as $kind) {
        $f = admin_core_private_resolve(admin_core_host_files($userId)[$kind] ?? null);
        if ($f) {
            @unlink($f);
        }
    }
}

/**
 * Delete an account. $mode: anonymise (keeps payment records, removes personal data) | hard (removes the row and everything linked to it).
 * Returns [bool ok, string message].
 */
function admin_core_user_delete(int $userId, string $mode, int $actorId): array
{
    $u = row('SELECT id, email, role, status FROM users WHERE id = :id', ['id' => $userId]);
    if (!$u) {
        return [false, 'Member not found.'];
    }
    $problem = admin_core_target_problem($u, $actorId, 'delete');
    if ($problem !== '') {
        return [false, $problem];
    }
    if ($u['role'] === 'admin') {
        return [false, 'Remove the admin role first, then delete the account.'];
    }
    $cascade = admin_core_user_cascade($userId);
    if ($mode === 'hard') {
        if ($cascade['payments_completed'] > 0) {
            return [false, 'This member has completed payments. Anonymise the account instead so the financial records stay.'];
        }
        admin_core_user_files_delete($userId);
        db_transaction(function () use ($userId) {
            q('DELETE FROM reports WHERE reporter_id = :u OR reported_id = :u', ['u' => $userId]);
            q('DELETE FROM users WHERE id = :id', ['id' => $userId]);
        });
        admin_log('user.delete', 'user', $userId, 'Permanently deleted ' . $u['email']);
    } else {
        admin_core_user_files_delete($userId);
        db_transaction(function () use ($userId) {
            q('DELETE FROM user_photos WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM user_videos WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM user_interests WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM user_locations WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM user_tokens WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM notifications WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM favorites WHERE user_id = :u OR target_id = :u', ['u' => $userId]);
            q('DELETE FROM search_history WHERE user_id = :u', ['u' => $userId]);
            q('DELETE FROM verification_requests WHERE user_id = :u', ['u' => $userId]);
            q("UPDATE messages SET content = NULL, media_path = NULL, is_deleted = 1 WHERE sender_id = :u", ['u' => $userId]);
            q("UPDATE user_profiles SET display_name = 'Deleted member', date_of_birth = NULL, bio = NULL, occupation = NULL, education = NULL, looking_for = NULL, languages = NULL,
                contact_phone = NULL, contact_whatsapp = NULL, contact_email = NULL, contact_instagram = NULL, is_paused = 1, is_verified = 0, show_contact = 0 WHERE user_id = :u", ['u' => $userId]);
            if (admin_core_table_exists('host_profiles')) {
                q("UPDATE host_profiles SET display_name = 'Deleted member', tagline = NULL, about = NULL, banner_path = NULL, approval_status = 'suspended' WHERE user_id = :u", ['u' => $userId]);
            }
            q("UPDATE users SET email = :e, phone = NULL, password_hash = :h, status = 'banned', status_reason = 'Account deleted', totp_secret = NULL, totp_enabled = 0, admin_role_id = NULL, featured_until = NULL WHERE id = :u",
                ['e' => 'deleted-' . $userId . '@deleted.invalid', 'h' => password_hash(random_token(24), PASSWORD_BCRYPT), 'u' => $userId]);
            q("UPDATE user_subscriptions SET status = 'cancelled', cancelled_at = NOW() WHERE user_id = :u AND status = 'active'", ['u' => $userId]);
        });
        admin_log('user.anonymise', 'user', $userId, 'Anonymised ' . $u['email']);
    }
    plans_forget($userId);
    admin_core_counts_forget();
    return [true, $mode === 'hard' ? 'Account deleted permanently.' : 'Account anonymised and closed.'];
}

/** Full data export for one member, as an array ready for JSON. */
function admin_core_user_export(int $userId): array
{
    $u = row('SELECT id, email, phone, role, status, status_reason, email_verified_at, last_login_at, last_active_at, last_ip, featured_until, created_at FROM users WHERE id = :id', ['id' => $userId]);
    $by = fn(string $sql, int $limit = 2000) => rows($sql . ' LIMIT ' . $limit, ['u' => $userId]);
    $data = [
        'exported_at' => now(), 'site' => site_name(), 'account' => $u,
        'profile' => row('SELECT * FROM user_profiles WHERE user_id = :u', ['u' => $userId]),
        'location' => row('SELECT * FROM user_locations WHERE user_id = :u', ['u' => $userId]),
        'interests' => col('SELECT i.name FROM user_interests ui JOIN interests i ON i.id = ui.interest_id WHERE ui.user_id = :u', ['u' => $userId]),
        'discovery_preferences' => row('SELECT * FROM discovery_preferences WHERE user_id = :u', ['u' => $userId]),
        'photos' => $by('SELECT id, large_path, is_primary, is_vip_only, moderation_status, rejection_reason, created_at FROM user_photos WHERE user_id = :u'),
        'subscriptions' => $by('SELECT s.*, p.name AS plan FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id WHERE s.user_id = :u ORDER BY s.id DESC'),
        'payments' => $by('SELECT id, payment_type, payment_method, amount, currency, status, reference, description, created_at FROM payments WHERE user_id = :u ORDER BY id DESC'),
        'boosts' => $by('SELECT * FROM boosts WHERE user_id = :u ORDER BY id DESC'),
        'swipes_made' => $by('SELECT swiped_id, direction, created_at FROM swipes WHERE swiper_id = :u ORDER BY id DESC', 5000),
        'matches' => $by('SELECT * FROM matches WHERE user1_id = :u OR user2_id = :u ORDER BY id DESC'),
        'messages_sent' => $by('SELECT conversation_id, type, content, created_at FROM messages WHERE sender_id = :u ORDER BY id DESC', 5000),
        'notifications' => $by('SELECT type, title, body, created_at FROM notifications WHERE user_id = :u ORDER BY id DESC', 500),
        'blocks_made' => $by('SELECT blocked_id, created_at FROM blocks WHERE blocker_id = :u'),
        'reports_made' => $by('SELECT reported_id, reason, description, status, created_at FROM reports WHERE reporter_id = :u'),
        'reports_received' => $by('SELECT reason, status, action_taken, created_at FROM reports WHERE reported_id = :u'),
        'verification_requests' => $by('SELECT id, type, status, rejection_reason, created_at FROM verification_requests WHERE user_id = :u'),
    ];
    if (admin_core_table_exists('host_profiles')) {
        $data['host_profile'] = row('SELECT * FROM host_profiles WHERE user_id = :u', ['u' => $userId]);
        $data['host_bookings'] = admin_core_table_exists('host_bookings') ? $by('SELECT * FROM host_bookings WHERE host_id = :u OR client_id = :u ORDER BY id DESC') : [];
    }
    return $data;
}

// ---------------------------------------------------------------------------
// Photo moderation (shared by the review queue, member pages and reports)
// ---------------------------------------------------------------------------

/**
 * Apply a moderation decision to one photo. $action: approve | reject | vip | unvip | delete
 * Returns [bool ok, string message].
 */
function admin_core_photo_decide(int $photoId, string $action, string $reason, int $adminId): array
{
    $p = row('SELECT * FROM user_photos WHERE id = :id', ['id' => $photoId]);
    if (!$p) {
        return [false, 'Photo not found.'];
    }
    $uid = (int)$p['user_id'];
    switch ($action) {
        case 'approve':
            q("UPDATE user_photos SET moderation_status = 'approved', rejection_reason = NULL, moderated_by = :a, moderated_at = NOW() WHERE id = :id", ['a' => $adminId, 'id' => $photoId]);
            if (!(int)val('SELECT COUNT(*) FROM user_photos WHERE user_id = :u AND is_primary = 1 AND moderation_status = \'approved\'', ['u' => $uid], 0)) {
                q('UPDATE user_photos SET is_primary = IF(id = :id, 1, 0) WHERE user_id = :u', ['id' => $photoId, 'u' => $uid]);
            }
            photo_refresh_completion($uid);
            admin_log('photo.approve', 'photo', $photoId, 'Member #' . $uid);
            return [true, 'Photo approved.'];
        case 'reject':
            $reason = trim(mb_substr($reason, 0, 250));
            if ($reason === '') {
                return [false, 'Choose or write a reason. The member will see it.'];
            }
            q("UPDATE user_photos SET moderation_status = 'rejected', rejection_reason = :r, is_primary = 0, moderated_by = :a, moderated_at = NOW() WHERE id = :id", ['r' => $reason, 'a' => $adminId, 'id' => $photoId]);
            if ((int)$p['is_primary'] === 1) {
                $next = val("SELECT id FROM user_photos WHERE user_id = :u AND moderation_status = 'approved' ORDER BY sort_order, id LIMIT 1", ['u' => $uid]);
                if ($next) {
                    q('UPDATE user_photos SET is_primary = 1 WHERE id = :id', ['id' => $next]);
                }
            }
            photo_refresh_completion($uid);
            notify($uid, 'system', 'A photo was not approved', 'Reason: ' . $reason . '. You can upload a different photo.', '/profile/photos');
            admin_log('photo.reject', 'photo', $photoId, 'Member #' . $uid . ': ' . $reason);
            return [true, 'Photo rejected and the member was told why.'];
        case 'vip':
        case 'unvip':
            q('UPDATE user_photos SET is_vip_only = :v WHERE id = :id', ['v' => $action === 'vip' ? 1 : 0, 'id' => $photoId]);
            admin_log('photo.' . $action, 'photo', $photoId, 'Member #' . $uid);
            return [true, $action === 'vip' ? 'Photo is now VIP only.' : 'Photo is visible to everyone again.'];
        case 'delete':
            photo_delete($photoId, $uid);
            admin_log('photo.delete', 'photo', $photoId, 'Member #' . $uid);
            return [true, 'Photo deleted.'];
    }
    return [false, 'Unknown action.'];
}

/** Reasons offered when rejecting a photo or verification. */
function admin_core_reject_reasons(string $kind = 'photo'): array
{
    if ($kind === 'verification') {
        return ['The selfie did not match the profile photos', 'The selfie was blurry or too dark', 'The pose in the selfie was not followed', 'The ID document was unreadable', 'The details on the ID did not match the profile'];
    }
    if ($kind === 'host') {
        return ['The ID document is unclear or unreadable', 'The name or details on the ID do not match the profile', 'The selfie holding the ID is missing or unclear', 'The profile photos are not suitable for the directory', 'The headline or about text breaks the Host rules', 'Services or rates are not clear enough'];
    }
    if ($kind === 'host_suspend') {
        return ['Complaints from clients about missed or unsafe bookings', 'Asking clients to pay outside the platform', 'Breaking the Host rules', 'Waiting for an updated ID document', 'Under review after a report'];
    }
    if ($kind === 'warning') {
        return ['Please keep conversations respectful. Further reports may lead to a suspension.', 'Please do not ask other members for money.', 'Please use photos that show you, and nothing else.', 'Please follow the community guidelines. This is a formal warning.'];
    }
    return ['Face is not clearly visible', 'Contains nudity or sexual content', 'Contains a logo, text or contact details', 'Not a photo of you', 'Low quality or blurry', 'Violates our community guidelines'];
}

// ---------------------------------------------------------------------------
// System health (dashboard and tools)
// ---------------------------------------------------------------------------

/** Last time the cron runner reported in (Unix time) or 0. */
function admin_core_cron_last_run(): int
{
    $stored = cache_get('cron_last_run', 0);
    $t = (int)$stored;
    foreach ([ROOT . '/storage/cron_last_run', ROOT . '/storage/logs/cron.log'] as $f) {
        if (is_file($f)) {
            $t = max($t, (int)filemtime($f));
        }
    }
    return $t;
}

function admin_core_mark_cron_run(): void
{
    cache_put('cron_last_run', time(), 86400 * 3650);
}

/** @return array list of ['label','status' => ok|warn|bad,'value','hint'] */
function admin_core_health(): array
{
    $out = [];
    $add = function (string $label, string $status, string $value, string $hint = '') use (&$out) {
        $out[] = ['label' => $label, 'status' => $status, 'value' => $value, 'hint' => $hint];
    };
    $add('PHP version', version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'bad', PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=') ? '' : 'PHP 8.1 or newer is required.');
    try {
        $t = microtime(true);
        $ver = (string)val('SELECT VERSION()', [], '?');
        $ms = round((microtime(true) - $t) * 1000, 1);
        $add('Database', 'ok', explode('-0ubuntu', $ver)[0] . ' (' . $ms . ' ms)');
    } catch (Throwable $e) {
        $add('Database', 'bad', 'Not reachable', $e->getMessage());
    }
    if (function_exists('admin_db_check')) {
        try {
            $dbc = cache_remember('admin_db_check', 300, fn() => admin_db_check());
            $n = admin_db_problem_count($dbc);
            $add('Database structure', $n ? 'bad' : 'ok', $n ? $n . ' to fix' : 'Complete', $n ? 'Part of a recent update did not finish, so some admin sections can look empty. Open Tools and run "Check and repair the database".' : '');
        } catch (Throwable $e) {
        }
    }
    $dirs = ['storage/cache', 'storage/logs', 'storage/sessions', 'storage/mail', 'storage/private', 'uploads', 'uploads/photos', 'uploads/site'];
    $bad = [];
    foreach ($dirs as $d) {
        $p = ROOT . '/' . $d;
        if (!is_dir($p)) {
            @mkdir($p, 0775, true);
        }
        if (!is_dir($p) || !is_writable($p)) {
            $bad[] = $d;
        }
    }
    $add('Writable folders', $bad ? 'bad' : 'ok', $bad ? count($bad) . ' not writable' : 'All writable', $bad ? 'Fix permissions on: ' . implode(', ', $bad) : '');
    $cron = admin_core_cron_last_run();
    $add('Cron job', $cron && time() - $cron < 900 ? 'ok' : ($cron && time() - $cron < 86400 ? 'warn' : 'warn'), $cron ? time_ago(date('Y-m-d H:i:s', $cron)) : 'Never reported', $cron ? 'Expected every 5 minutes.' : 'Add a cron job that runs cron/run.php every 5 minutes.');
    $driver = (string)setting('mail_driver');
    $add('Email', $driver === 'log' ? 'warn' : ($driver === 'smtp' && (string)setting('smtp_host') === '' ? 'bad' : 'ok'), ['mail' => 'PHP mail()', 'smtp' => 'SMTP', 'log' => 'Log only (nothing is sent)'][$driver] ?? $driver,
        $driver === 'log' ? 'Members will not receive emails. Change this under Settings, Email.' : ($driver === 'smtp' && (string)setting('smtp_host') === '' ? 'SMTP is selected but no host is set.' : ''));
    $add('HTTPS', is_https() ? 'ok' : (is_debug() ? 'warn' : 'bad'), is_https() ? 'Secure connection' : 'Not secure', is_https() ? '' : 'Serve the site over HTTPS before launch.');
    // The stored site address should match the address the admin is using now, otherwise links in emails and shares can point somewhere else.
    $stored = trim((string)setting('site_url'));
    if ($stored !== '') {
        $sp = parse_url($stored);
        $same = strcasecmp(preg_replace('/^www\./i', '', (string)($sp['host'] ?? '')), preg_replace('/^www\./i', '', preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')))) === 0;
        $schemeOk = !is_https() || ($sp['scheme'] ?? '') === 'https';
        $add('Site address', $same && $schemeOk ? 'ok' : 'warn', $stored,
            !$same ? 'You are using a different address than the one saved in Settings > General > Site address. Links in emails use the saved one.' : (!$schemeOk ? 'The saved address starts with http:// but the site is open over https. Edit it in Settings > General > Site address (or clear it to detect automatically).' : ''));
    }
    $add('Debug mode', is_debug() ? 'warn' : 'ok', is_debug() ? 'On' : 'Off', is_debug() ? 'Debug mode shows technical details. Turn it off in config/config.php for a live site.' : '');
    $up = ini_bytes((string)ini_get('upload_max_filesize'));
    $post = ini_bytes((string)ini_get('post_max_size'));
    $need = (int)setting('photo_max_mb') * 1048576;
    $add('Upload limits', $up >= $need && $post >= $need ? 'ok' : 'warn', 'Files ' . ini_get('upload_max_filesize') . ', requests ' . ini_get('post_max_size'),
        $up >= $need && $post >= $need ? '' : 'The server limit is below the photo size allowed in Settings (' . (int)setting('photo_max_mb') . ' MB).');
    $missing = array_values(array_filter(['gd', 'mbstring', 'fileinfo', 'pdo_mysql', 'exif'], fn($x) => !extension_loaded($x)));
    $add('PHP extensions', $missing ? 'bad' : 'ok', $missing ? 'Missing: ' . implode(', ', $missing) : 'GD, mbstring, fileinfo, exif, PDO', '');
    $u = current_user();
    if ($u && (int)$u['must_change_password'] === 1) {
        $add('Admin password', 'bad', 'Default password still in use', 'Change it now under Account.');
    }
    $others = (int)val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND must_change_password = 1 AND id <> :me", ['me' => auth_id()], 0);
    if ($others > 0) {
        $add('Other admins', 'warn', $others . ' still on a temporary password', 'They will be asked to change it at next sign-in.');
    }
    if (setting('maintenance_mode')) {
        $add('Maintenance mode', 'warn', 'On', 'Visitors see the maintenance page.');
    }
    $free = @disk_free_space(ROOT);
    if ($free !== false) {
        $add('Disk space', $free < 200 * 1048576 ? 'bad' : ($free < 1024 * 1048576 ? 'warn' : 'ok'), file_size_h((int)$free) . ' free', '');
    }
    // Payments: a method that is switched on but cannot take money is the most expensive thing to miss.
    if (function_exists('payments_methods')) {
        $notReady = [];
        $noHook = [];
        foreach (payments_methods() as $key => $m) {
            if (!empty($m['enabled']) && empty($m['ready']) && !in_array($key, ['test', 'bank', 'manual'], true)) {
                $notReady[] = $m['label'];
            }
        }
        if (setting('paypal_enabled') && (string)setting('paypal_webhook_id') === '') {
            $noHook[] = 'PayPal';
        }
        if (setting('card_payments_enabled') && setting('card_gateway') === 'stripe' && (string)setting('stripe_webhook_secret') === '') {
            $noHook[] = 'Stripe';
        }
        $stuck = (int)val("SELECT COUNT(*) FROM payments WHERE status = 'pending' AND gateway IS NOT NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY) AND created_at > DATE_SUB(NOW(), INTERVAL 3 DAY)", [], 0);
        if ($notReady) {
            $add('Payments', 'bad', 'Not ready: ' . implode(', ', $notReady), 'These are switched on but missing keys. Finish them under Payment methods or switch them off.');
        } elseif ($noHook && !is_debug()) {
            $add('Payments', 'warn', 'Webhook not set: ' . implode(', ', $noHook), 'PayPal and Stripe confirm payments by webhook as well as on return. Add the webhook details shown on each setup page so no payment is missed.');
        } else {
            $add('Payments', $stuck > 0 ? 'warn' : 'ok', $stuck > 0 ? $stuck . ' waiting over a day' : 'Ready', $stuck > 0 ? 'Open Payments and check the pending online payments.' : '');
        }
    }
    // Search engines: the things that quietly keep a site out of results.
    if (!is_debug()) {
        $seoIssues = [];
        if (setting('seo_noindex_site')) {
            $seoIssues[] = 'site is set to hide from search engines';
        }
        if (trim((string)setting('site_url')) === '') {
            $seoIssues[] = 'site address is not saved';
        }
        if (trim((string)setting('seo_default_description')) === '') {
            $seoIssues[] = 'no default description';
        }
        $add('Search engines', $seoIssues ? 'warn' : 'ok', $seoIssues ? ucfirst(implode(', ', $seoIssues)) : 'Open to indexing', $seoIssues ? 'Review SEO in the admin menu.' : '');
    }
    $errLog = ROOT . '/storage/logs/error.log';
    if (is_file($errLog)) {
        $recent = 0;
        foreach (admin_core_log_tail($errLog, 400) as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m) && strtotime($m[1]) > time() - 86400) {
                $recent++;
            }
        }
        $add('Errors, last 24 hours', $recent === 0 ? 'ok' : ($recent > 20 ? 'bad' : 'warn'), $recent === 0 ? 'None logged' : $recent . ' logged', $recent ? 'Open Activity log, Server logs.' : '');
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Member photos, managed by an admin
// ---------------------------------------------------------------------------

/** Rotate a stored photo (all sizes and the blurred copy) by 90 degrees. $dir is 'cw' or 'ccw'. Returns [ok, message]. */
function admin_core_photo_rotate(int $photoId, int $userId, string $dir): array
{
    $p = row('SELECT * FROM user_photos WHERE id = :id AND user_id = :u', ['id' => $photoId, 'u' => $userId]);
    if (!$p) {
        return [false, 'That photo does not belong to this member.'];
    }
    if (!function_exists('imagerotate')) {
        return [false, 'Rotating needs the GD image library, which is not available on this server.'];
    }
    $deg = $dir === 'ccw' ? 90 : -90;
    try {
        $q = max(60, min(92, (int)setting('photo_quality')));
        foreach (['large_path', 'medium_path', 'thumb_path'] as $k) {
            if (empty($p[$k]) || !is_file(upload_abs($p[$k]))) {
                continue;
            }
            $img = image_load(upload_abs($p[$k]));
            $rot = imagerotate($img, $deg, imagecolorallocate($img, 255, 255, 255));
            if (!$rot) {
                throw new RuntimeException('rotate failed');
            }
            image_save_jpeg($rot, upload_abs($p[$k]), $k === 'thumb_path' ? max(60, $q - 8) : $q);
        }
        $cols = [];
        if (!empty($p['large_path']) && is_file(upload_abs($p['large_path']))) {
            $big = image_load(upload_abs($p['large_path']));
            $cols['width'] = imagesx($big);
            $cols['height'] = imagesy($big);
            if (!empty($p['blur_path'])) {
                $cols['blur_data_uri'] = image_make_blur($big, upload_abs($p['blur_path']));
            }
            $cols['file_size'] = (int)filesize(upload_abs($p['large_path']));
        }
        if ($cols) {
            update('user_photos', $cols, ['id' => $photoId]);
        }
    } catch (Throwable $e) {
        app_log('error', 'admin photo rotate failed: ' . $e->getMessage());
        return [false, 'The photo could not be rotated.'];
    }
    admin_log('photo.rotate', 'photo', $photoId, 'Member #' . $userId);
    return [true, 'Photo rotated.'];
}

/** Move a photo one place earlier ('up') or later ('down') among the member's other photos. The main photo always comes first. */
function admin_core_photo_move(int $photoId, int $userId, string $dir): array
{
    $ids = array_map('intval', array_column(rows('SELECT id FROM user_photos WHERE user_id = :u AND is_primary = 0 ORDER BY sort_order, id', ['u' => $userId]), 'id'));
    $i = array_search($photoId, $ids, true);
    if ($i === false) {
        return [false, 'Only photos after the main photo can be moved.'];
    }
    $j = $dir === 'up' ? $i - 1 : $i + 1;
    if (!isset($ids[$j])) {
        return [true, 'It is already there.'];
    }
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    $prim = (int)val('SELECT id FROM user_photos WHERE user_id = :u AND is_primary = 1 LIMIT 1', ['u' => $userId], 0);
    $n = 0;
    if ($prim) {
        q('UPDATE user_photos SET sort_order = 0 WHERE id = :id', ['id' => $prim]);
        $n = 1;
    }
    foreach ($ids as $id) {
        q('UPDATE user_photos SET sort_order = :s WHERE id = :id', ['s' => $n++, 'id' => $id]);
    }
    return [true, 'Order saved.'];
}
