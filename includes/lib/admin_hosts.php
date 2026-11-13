<?php
/**
 * Admin > Hosts: the filters, the list query and the numbers shared by the page and the CSV export.
 */

declare(strict_types=1);

const ADMIN_HOSTS_FROM = 'FROM host_profiles hp JOIN users u ON u.id = hp.user_id LEFT JOIN host_categories c ON c.id = hp.category_id LEFT JOIN host_profile_ext x ON x.user_id = hp.user_id LEFT JOIN user_locations l ON l.user_id = hp.user_id';

function admin_hosts_statuses(): array
{
    return ['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'draft' => 'Drafts'];
}

/** [label, ORDER BY] by key. */
function admin_hosts_sorts(): array
{
    return [
        'waiting' => ['Longest waiting first', 'hp.submitted_at IS NULL, hp.submitted_at ASC, hp.user_id ASC'],
        'newest' => ['Newest first', 'hp.user_id DESC'],
        'rating' => ['Best rated', 'hp.rating_avg DESC, hp.review_count DESC'],
        'bookings' => ['Most bookings', 'hp.total_bookings DESC, hp.user_id ASC'],
        'name' => ['Name A to Z', 'hp.display_name ASC'],
    ];
}

/**
 * Read the filters from a query string.
 * @param array<string,int> $counts approval_status => number of hosts
 * @return array{status:string,q:string,cat:int,id:string,sort:string,view:string,where:string,params:array}
 */
function admin_hosts_filters(array $src, array $counts): array
{
    $statuses = admin_hosts_statuses();
    $sorts = admin_hosts_sorts();
    $status = trim((string)($src['status'] ?? ''));
    if ($status === '' && !isset($src['status'])) {
        $status = !empty($counts['pending']) ? 'pending' : '';
    }
    $status = isset($statuses[$status]) ? $status : '';
    $q = trim((string)($src['q'] ?? ''));
    $cat = (int)($src['cat'] ?? 0);
    $idf = in_array((string)($src['id'] ?? ''), ['yes', 'no', 'files'], true) ? (string)$src['id'] : '';
    $sortIn = (string)($src['sort'] ?? '');
    $sort = isset($sorts[$sortIn]) ? $sortIn : ($status === 'pending' ? 'waiting' : 'newest');
    $view = ($src['view'] ?? '') === 'cards' ? 'cards' : 'table';

    $where = ['1 = 1'];
    $params = [];
    if ($status !== '') {
        $where[] = 'hp.approval_status = :st';
        $params['st'] = $status;
    }
    if ($q !== '') {
        $where[] = '(hp.display_name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q OR hp.tagline LIKE :q' . (ctype_digit($q) ? ' OR hp.user_id = :qid' : '') . ')';
        $params['q'] = admin_core_like($q);
        if (ctype_digit($q)) {
            $params['qid'] = (int)$q;
        }
    }
    if ($cat > 0) {
        $where[] = 'hp.category_id = :cat';
        $params['cat'] = $cat;
    }
    if ($idf === 'yes') {
        $where[] = '(hp.id_verified = 1 OR hp.photo_verified = 1)';
    } elseif ($idf === 'no') {
        $where[] = '(hp.id_verified = 0 AND hp.photo_verified = 0)';
    } elseif ($idf === 'files') {
        $where[] = "COALESCE(x.id_doc_path, '') <> ''";
    }
    return ['status' => $status, 'q' => $q, 'cat' => $cat, 'id' => $idf, 'sort' => $sort, 'view' => $view, 'where' => implode(' AND ', $where), 'params' => $params];
}

/** The numbers above the list. Cached for a minute; the page forgets them on any action through admin_core_counts_forget(). */
function admin_hosts_stats(): array
{
    return cache_remember('admin_hosts_stats', 60, function () {
        $r = admin_core_try_rows("SELECT COUNT(*) AS total,
                COALESCE(SUM(hp.approval_status = 'approved'), 0) AS approved,
                COALESCE(SUM(hp.approval_status = 'pending'), 0) AS pending,
                COALESCE(SUM(hp.approval_status = 'suspended'), 0) AS suspended,
                COALESCE(SUM(hp.approval_status = 'approved' AND hp.is_featured = 1), 0) AS featured,
                COALESCE(SUM(hp.approval_status = 'approved' AND (hp.id_verified = 1 OR hp.photo_verified = 1)), 0) AS verified,
                COALESCE(SUM(hp.created_at >= (NOW() - INTERVAL 30 DAY)), 0) AS joined_30,
                COALESCE(SUM(hp.review_count), 0) AS reviews,
                AVG(CASE WHEN hp.review_count > 0 THEN hp.rating_avg END) AS rating
            FROM host_profiles hp");
        $s = $r[0] ?? [];
        $out = [];
        foreach (['total', 'approved', 'pending', 'suspended', 'featured', 'verified', 'joined_30', 'reviews'] as $k) {
            $out[$k] = (int)($s[$k] ?? 0);
        }
        $out['rating'] = isset($s['rating']) && $s['rating'] !== null ? (float)$s['rating'] : null;
        $out['vip'] = (int)admin_core_try_val("SELECT COUNT(*) FROM host_profile_ext x JOIN host_profiles hp ON hp.user_id = x.user_id WHERE x.is_vip = 1 AND hp.approval_status = 'approved'", [], 0);
        $out['oldest'] = (string)admin_core_try_val("SELECT MIN(submitted_at) FROM host_profiles WHERE approval_status = 'pending' AND submitted_at IS NOT NULL", [], '');
        $out['bookings_30'] = (int)admin_core_try_val("SELECT COUNT(*) FROM host_bookings WHERE status IN ('confirmed','completed') AND created_at >= (NOW() - INTERVAL 30 DAY)", [], 0);
        $out['commission_30'] = (float)admin_core_try_val("SELECT COALESCE(SUM(commission_amount), 0) FROM host_bookings WHERE status = 'completed' AND created_at >= (NOW() - INTERVAL 30 DAY)", [], 0);
        return $out;
    });
}

/** The first approved host: used by the "See a profile" link. */
function admin_hosts_sample_id(): int
{
    return (int)admin_core_try_val("SELECT user_id FROM host_profiles WHERE approval_status = 'approved' ORDER BY is_featured DESC, rating_avg DESC, user_id ASC LIMIT 1", [], 0);
}

/** Star rating drawn as five small stars (filled to the nearest half). */
function admin_core_stars(?float $rating, int $count = 0): string
{
    if ($rating === null || $count <= 0) {
        return '<span class="xstars is-none">No reviews yet</span>';
    }
    $pct = max(0, min(100, round($rating / 5 * 100)));
    return '<span class="xstars" role="img" aria-label="' . e(number_format($rating, 1)) . ' out of 5 from ' . $count . ' ' . ($count === 1 ? 'review' : 'reviews') . '"><span class="xstars-track" aria-hidden="true"><span class="xstars-fill" style="width:' . $pct . '%"></span></span><b>' . e(number_format($rating, 1)) . '</b><i>(' . $count . ')</i></span>';
}

/**
 * One KPI tile, shared by the dashboard, Hosts and Elite.
 * $o: href, feat (bool), tone (money|people|live|alert|ok|gold), delta (admin_core_delta result), note, spark (number list), prefix, valueClass.
 */
function admin_core_kpi(string $label, string $ico, string $value, array $o = []): string
{
    $href = $o['href'] ?? null;
    $tag = $href ? 'a' : 'div';
    $h = '<' . $tag . ' class="cp-kpi' . (!empty($o['feat']) ? ' is-feat' : '') . (!empty($o['tone']) ? ' tone-' . e((string)$o['tone']) : '') . '"' . ($href ? ' href="' . e(url((string)$href)) . '"' : '') . '>';
    $h .= '<span class="cp-kpi-label"><span class="cp-kpi-ico">' . icon($ico, 'w-4 h-4') . '</span>' . e($label) . '</span>';
    $h .= '<span class="cp-kpi-value">' . ($o['prefix'] ?? '') . e($value) . '</span>';
    $h .= '<span class="cp-kpi-foot"><span class="min-w-0">';
    if (isset($o['delta'])) {
        $d = $o['delta'];
        $h .= '<span class="cp-delta is-' . e($d['dir']) . '" title="Compared with the period before">' . ($d['dir'] === 'up' ? icon('trending-up', 'w-3 h-3') : ($d['dir'] === 'down' ? icon('trending-down', 'w-3 h-3') : '')) . e($d['label']) . '</span> ';
    }
    $h .= '<span class="cp-kpi-note">' . e((string)($o['note'] ?? '')) . '</span></span>';
    if (!empty($o['spark'])) {
        $h .= admin_core_spark($o['spark'], 'currentColor', !empty($o['feat']) ? 110 : 58, !empty($o['feat']) ? 36 : 26);
    }
    return $h . '</span></' . $tag . '>';
}
