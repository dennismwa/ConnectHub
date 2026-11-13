<?php
/** Member list filtering shared by the members page and its CSV export (builder D1). */

declare(strict_types=1);

/** SQL expression for a member's current plan tier (0 free, 1 premium, 2 vip). Needs the users table aliased as u. */
function admin_core_tier_sql(): string
{
    return "COALESCE((SELECT MAX(pl.tier) FROM user_subscriptions s JOIN subscription_plans pl ON pl.id = s.plan_id WHERE s.user_id = u.id AND s.status = 'active' AND s.expires_at > NOW()), 0)";
}

/** Whitelisted sort options: key => [label, ORDER BY]. */
function admin_core_users_sorts(): array
{
    return [
        'newest' => ['Newest first', 'u.id DESC'],
        'oldest' => ['Oldest first', 'u.id ASC'],
        'active' => ['Recently active', 'u.last_active_at IS NULL, u.last_active_at DESC, u.id DESC'],
        'login' => ['Last sign-in', 'u.last_login_at IS NULL, u.last_login_at DESC, u.id DESC'],
        'name' => ['Name A to Z', "p.display_name = '', p.display_name ASC, u.id DESC"],
    ];
}

/**
 * Build the WHERE part for the member list from query-string filters.
 * @return array ['where' => string, 'params' => array, 'clean' => array of the filters that were applied]
 */
function admin_core_users_filters(array $src): array
{
    $where = ['1 = 1'];
    $params = [];
    $clean = [];
    $str = fn(string $k) => trim((string)($src[$k] ?? ''));

    $q = $str('q');
    if ($q !== '') {
        $clean['q'] = $q;
        $like = admin_core_like($q);
        $w = '(u.email LIKE :q1 OR p.display_name LIKE :q1 OR u.phone LIKE :q1 OR l.town_name LIKE :q1';
        $params['q1'] = $like;
        if (ctype_digit($q)) {
            $w .= ' OR u.id = :qid';
            $params['qid'] = (int)$q;
        }
        $where[] = $w . ')';
    }
    $role = $str('role');
    if (in_array($role, ['user', 'host', 'admin'], true)) {
        $clean['role'] = $role;
        $where[] = 'u.role = :role';
        $params['role'] = $role;
    }
    $status = $str('status');
    if (in_array($status, ['active', 'pending', 'suspended', 'banned', 'deactivated'], true)) {
        $clean['status'] = $status;
        $where[] = 'u.status = :status';
        $params['status'] = $status;
    }
    $tier = $str('tier');
    if (in_array($tier, ['free', 'premium', 'vip'], true)) {
        $clean['tier'] = $tier;
        $where[] = admin_core_tier_sql() . ($tier === 'free' ? ' = 0' : ($tier === 'premium' ? ' = 1' : ' >= 2'));
    }
    $verified = $str('verified');
    if ($verified === '1' || $verified === '0') {
        $clean['verified'] = $verified;
        $where[] = $verified === '1' ? 'p.is_verified = 1' : '(p.is_verified = 0 OR p.is_verified IS NULL)';
    }
    $photo = $str('photo');
    if ($photo === '1' || $photo === '0') {
        $clean['photo'] = $photo;
        $has = "EXISTS (SELECT 1 FROM user_photos ph WHERE ph.user_id = u.id AND ph.moderation_status = 'approved')";
        $where[] = $photo === '1' ? $has : 'NOT ' . $has;
    }
    $county = $str('county');
    if ($county !== '') {
        $clean['county'] = $county;
        $where[] = 'l.county_name = :county';
        $params['county'] = $county;
    }
    $country = $str('country');
    if ($country !== '') {
        $clean['country'] = $country;
        $where[] = 'l.country = :country';
        $params['country'] = $country;
    }
    $joined = $str('joined');
    if (ctype_digit($joined) && (int)$joined > 0 && (int)$joined <= 3650) {
        $clean['joined'] = $joined;
        $where[] = 'u.created_at >= (CURDATE() - INTERVAL ' . ((int)$joined - 1) . ' DAY)';
    }
    $from = $str('from');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $clean['from'] = $from;
        $where[] = 'u.created_at >= :from';
        $params['from'] = $from . ' 00:00:00';
    }
    $to = $str('to');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $clean['to'] = $to;
        $where[] = 'u.created_at <= :to';
        $params['to'] = $to . ' 23:59:59';
    }
    $active = $str('active');
    if (in_array($active, ['1', '7', '30', 'dormant', 'never'], true)) {
        $clean['active'] = $active;
        if ($active === 'dormant') {
            $where[] = '(u.last_active_at IS NOT NULL AND u.last_active_at < (NOW() - INTERVAL 30 DAY))';
        } elseif ($active === 'never') {
            $where[] = 'u.last_active_at IS NULL';
        } else {
            $where[] = 'u.last_active_at >= (NOW() - INTERVAL ' . (int)$active . ' DAY)';
        }
    }
    return ['where' => implode(' AND ', $where), 'params' => $params, 'clean' => $clean];
}

const ADMIN_CORE_USERS_FROM = 'FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id LEFT JOIN user_locations l ON l.user_id = u.id';
