<?php
/**
 * Admin analytics: date ranges, daily series, funnel, revenue breakdowns, geography, demographics and sign-up cohorts.
 *
 * Daily numbers come from analytics_daily when a day has been stored (cron, or Tools > Rebuild analytics) and from live
 * queries for every day that has not (always including today). Every query is wrapped so a module that is not installed
 * yet simply reports zero.
 *
 * Exported for other code:
 *   admin_analytics_snapshot(string $date): int      store one day (Y-m-d) in analytics_daily, safe to re-run, returns metrics written
 *   admin_analytics_backfill(int $days): int         store the last N full days, returns days written
 */

/** Metric name => [label, how a week or month is combined: sum|avg]. Names shared with cron_analytics_snapshot() keep its meaning. */
function admin_analytics_metrics(): array
{
    return [
        'signups' => ['New members', 'sum'],
        'host_signups' => ['New Hosts', 'sum'],
        'dau' => ['Active members', 'avg'],
        'likes' => ['Likes sent', 'sum'],
        'matches' => ['Matches', 'sum'],
        'messages' => ['Messages', 'sum'],
        'reports' => ['Reports', 'sum'],
        'pay_count' => ['Payments', 'sum'],
        'rev_all' => ['Revenue', 'sum'],
        'rev_subscription' => ['Subscriptions', 'sum'],
        'rev_boost' => ['Boosts', 'sum'],
        'rev_gift' => ['Gifts', 'sum'],
        'rev_booking' => ['Bookings', 'sum'],
        'rev_other' => ['Other', 'sum'],
    ];
}

/**
 * Selected range from the query string: ?range=7|30|90|365 or ?from=Y-m-d&to=Y-m-d.
 * @return array from, to, days, preset, prev_from, prev_to, gran (day|week), label
 */
function admin_analytics_range(): array
{
    $today = date('Y-m-d');
    $valid = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false && date('Y-m-d', strtotime($d)) === $d;
    $from = get('from', '');
    $to = get('to', '');
    $preset = 30;
    if ($valid($from) && $valid($to)) {
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $to = min($to, $today);
        $from = min($from, $to);
        if ((strtotime($to) - strtotime($from)) / 86400 > 365) {
            $from = date('Y-m-d', strtotime($to . ' -365 days'));
        }
        $preset = 0;
    } else {
        $r = (int)get('range', 30);
        $preset = in_array($r, [7, 30, 90, 365], true) ? $r : 30;
        $to = $today;
        $from = date('Y-m-d', strtotime($today . ' -' . ($preset - 1) . ' days'));
    }
    $days = (int)round((strtotime($to) - strtotime($from)) / 86400) + 1;
    $prevTo = date('Y-m-d', strtotime($from . ' -1 day'));
    $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' days'));
    $label = $preset ? ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months'][(string)$preset]
        : date('j M Y', strtotime($from)) . ' to ' . date('j M Y', strtotime($to));
    return ['from' => $from, 'to' => $to, 'days' => $days, 'preset' => $preset, 'prev_from' => $prevFrom, 'prev_to' => $prevTo,
        'gran' => $days > 92 ? 'week' : 'day', 'label' => $label];
}

/** Next calendar day (exclusive upper bound for datetime comparisons). */
function admin_analytics_next(string $date): string
{
    return date('Y-m-d', strtotime($date . ' +1 day'));
}

/**
 * Live daily values for one metric between two dates (inclusive).
 * @return array<string,float> date => value (days without activity are missing)
 */
function admin_analytics_live(string $metric, string $from, string $to): array
{
    $f = $from;
    $t = admin_analytics_next($to);
    $pd = admin_core_pay_date('p');
    $by = function (string $sql, array $p) {
        $out = [];
        foreach (admin_core_try_rows($sql, $p) as $r) {
            $out[(string)$r['d']] = (float)$r['n'];
        }
        return $out;
    };
    switch ($metric) {
        case 'signups':
            return $by("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM users WHERE role <> 'admin' AND created_at >= :f AND created_at < :t GROUP BY DATE(created_at)", ['f' => $f, 't' => $t]);
        case 'host_signups':
            return $by("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM users WHERE role = 'host' AND created_at >= :f AND created_at < :t GROUP BY DATE(created_at)", ['f' => $f, 't' => $t]);
        case 'dau':
            return $by("SELECT d, COUNT(DISTINCT uid) AS n FROM (
                    SELECT DATE(created_at) AS d, swiper_id AS uid FROM swipes WHERE created_at >= :f1 AND created_at < :t1
                    UNION ALL SELECT DATE(created_at), sender_id FROM messages WHERE type <> 'system' AND created_at >= :f2 AND created_at < :t2
                    UNION ALL SELECT DATE(last_active_at), id FROM users WHERE role <> 'admin' AND last_active_at >= :f3 AND last_active_at < :t3
                ) x GROUP BY d", ['f1' => $f, 't1' => $t, 'f2' => $f, 't2' => $t, 'f3' => $f, 't3' => $t]);
        case 'likes':
            return $by("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM swipes WHERE direction <> 'nope' AND created_at >= :f AND created_at < :t GROUP BY DATE(created_at)", ['f' => $f, 't' => $t]);
        case 'matches':
            return $by('SELECT DATE(matched_at) AS d, COUNT(*) AS n FROM matches WHERE matched_at >= :f AND matched_at < :t GROUP BY DATE(matched_at)', ['f' => $f, 't' => $t]);
        case 'messages':
            return $by("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM messages WHERE type <> 'system' AND created_at >= :f AND created_at < :t GROUP BY DATE(created_at)", ['f' => $f, 't' => $t]);
        case 'reports':
            return $by('SELECT DATE(created_at) AS d, COUNT(*) AS n FROM reports WHERE created_at >= :f AND created_at < :t GROUP BY DATE(created_at)', ['f' => $f, 't' => $t]);
        case 'pay_count':
            return $by("SELECT DATE($pd) AS d, COUNT(*) AS n FROM payments p WHERE p.status = 'completed' AND $pd >= :f AND $pd < :t GROUP BY DATE($pd)", ['f' => $f, 't' => $t]);
        case 'rev_all':
            return $by("SELECT DATE($pd) AS d, SUM(p.amount) AS n FROM payments p WHERE p.status = 'completed' AND $pd >= :f AND $pd < :t GROUP BY DATE($pd)", ['f' => $f, 't' => $t]);
        case 'rev_subscription':
        case 'rev_boost':
        case 'rev_gift':
        case 'rev_booking':
            $type = substr($metric, 4);
            return $by("SELECT DATE($pd) AS d, SUM(p.amount) AS n FROM payments p WHERE p.status = 'completed' AND p.payment_type = '$type' AND $pd >= :f AND $pd < :t GROUP BY DATE($pd)", ['f' => $f, 't' => $t]);
        case 'rev_other':
            return $by("SELECT DATE($pd) AS d, SUM(p.amount) AS n FROM payments p WHERE p.status = 'completed' AND p.payment_type NOT IN ('subscription','boost','gift','booking') AND $pd >= :f AND $pd < :t GROUP BY DATE($pd)", ['f' => $f, 't' => $t]);
    }
    return [];
}

/**
 * Values for a metric on each of $dates (Y-m-d list): stored totals where they exist, live numbers for the rest.
 * @return float[] in the order of $dates
 */
function admin_analytics_series(string $metric, array $dates): array
{
    if (!$dates) {
        return [];
    }
    $first = $dates[0];
    $last = end($dates);
    $today = date('Y-m-d');
    $stored = [];
    if (admin_core_table_exists('analytics_daily')) {
        foreach (admin_core_try_rows('SELECT stat_date, value FROM analytics_daily WHERE metric = :m AND stat_date >= :f AND stat_date <= :t', ['m' => $metric, 'f' => $first, 't' => $last]) as $r) {
            $stored[(string)$r['stat_date']] = (float)$r['value'];
        }
    }
    $missing = array_values(array_filter($dates, fn($d) => !isset($stored[$d]) || $d >= $today));
    $live = [];
    if ($missing) {
        $live = admin_analytics_live($metric, min($missing), max($missing));
    }
    $out = [];
    foreach ($dates as $d) {
        $out[] = in_array($d, $missing, true) ? ($live[$d] ?? 0.0) : $stored[$d];
    }
    return $out;
}

/**
 * Combine daily values into weeks (Monday start) when the range is long.
 * @return array [labels[], values[]]
 */
function admin_analytics_bucket(array $dates, array $values, string $gran, string $how = 'sum'): array
{
    if ($gran !== 'week') {
        return [$dates, array_map('floatval', $values)];
    }
    $sumBy = [];
    $cnt = [];
    foreach ($dates as $i => $d) {
        $wk = date('Y-m-d', strtotime($d . ' -' . ((int)date('N', strtotime($d)) - 1) . ' days'));
        $sumBy[$wk] = ($sumBy[$wk] ?? 0) + (float)($values[$i] ?? 0);
        $cnt[$wk] = ($cnt[$wk] ?? 0) + 1;
    }
    $labels = [];
    $vals = [];
    $multiYear = substr($dates[0], 0, 4) !== substr(end($dates), 0, 4);
    foreach ($sumBy as $wk => $v) {
        $labels[] = date($multiYear ? 'j M y' : 'j M', strtotime($wk));
        $vals[] = $how === 'avg' ? round($v / max(1, $cnt[$wk]), 1) : $v;
    }
    return [$labels, $vals];
}

/** Store one day of totals in analytics_daily. Returns the number of metrics written (0 for a bad or future date). */
function admin_analytics_snapshot(string $date): int
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false || $date > date('Y-m-d') || !admin_core_table_exists('analytics_daily')) {
        return 0;
    }
    $n = 0;
    foreach (array_keys(admin_analytics_metrics()) as $metric) {
        $v = admin_analytics_live($metric, $date, $date)[$date] ?? 0;
        q('INSERT INTO analytics_daily (stat_date, metric, value) VALUES (:d, :m, :v) ON DUPLICATE KEY UPDATE value = VALUES(value)',
            ['d' => $date, 'm' => $metric, 'v' => max(0, (int)round($v))]);
        $n++;
    }
    return $n;
}

/** Store the last $days complete days (yesterday backwards), one query per metric. Returns how many days were written. */
function admin_analytics_backfill(int $days): int
{
    @set_time_limit(300);
    $days = max(1, min(400, $days));
    if (!admin_core_table_exists('analytics_daily')) {
        return 0;
    }
    $from = date('Y-m-d', strtotime("-$days days"));
    $to = date('Y-m-d', strtotime('-1 day'));
    $dates = admin_core_dates($from, $to);
    foreach (array_keys(admin_analytics_metrics()) as $metric) {
        $live = admin_analytics_live($metric, $from, $to);
        foreach (array_chunk($dates, 100) as $chunk) {
            $sql = [];
            $p = [];
            foreach ($chunk as $i => $d) {
                $sql[] = "(:d$i, :m$i, :v$i)";
                $p["d$i"] = $d;
                $p["m$i"] = $metric;
                $p["v$i"] = max(0, (int)round($live[$d] ?? 0));
            }
            q('INSERT INTO analytics_daily (stat_date, metric, value) VALUES ' . implode(',', $sql) . ' ON DUPLICATE KEY UPDATE value = VALUES(value)', $p);
        }
    }
    cache_clear('admin_an_');
    return count($dates);
}

/**
 * Funnel for members who registered in the range. A step only counts members who also completed every step before it.
 * @return array list of [key, label, count, pct_of_registered, pct_of_previous]
 */
function admin_analytics_funnel(string $from, string $to): array
{
    $steps = [['registered', 'Registered'], ['onboarded', 'Finished onboarding'], ['liked', 'Sent a first like'], ['matched', 'Got a first match'], ['messaged', 'Sent a first message'], ['premium', 'Bought a paid plan']];
    $r = admin_core_try_rows("SELECT COUNT(*) AS reg,
            COALESCE(SUM(onb), 0) AS onb,
            COALESCE(SUM(onb AND lk), 0) AS lk,
            COALESCE(SUM(onb AND lk AND mt), 0) AS mt,
            COALESCE(SUM(onb AND lk AND mt AND ms), 0) AS ms,
            COALESCE(SUM(onb AND lk AND mt AND ms AND pr), 0) AS pr
        FROM (
            SELECT u.id, COALESCE(p.onboarding_completed, 0) AS onb,
                EXISTS(SELECT 1 FROM swipes s WHERE s.swiper_id = u.id AND s.direction <> 'nope') AS lk,
                EXISTS(SELECT 1 FROM matches m WHERE m.user1_id = u.id OR m.user2_id = u.id) AS mt,
                EXISTS(SELECT 1 FROM messages g WHERE g.sender_id = u.id AND g.type <> 'system') AS ms,
                EXISTS(SELECT 1 FROM user_subscriptions us JOIN subscription_plans sp ON sp.id = us.plan_id WHERE us.user_id = u.id AND sp.tier >= 1) AS pr
            FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
            WHERE u.role <> 'admin' AND u.created_at >= :f AND u.created_at < :t
        ) x", ['f' => $from, 't' => admin_analytics_next($to)])[0] ?? [];
    $counts = [(int)($r['reg'] ?? 0), (int)($r['onb'] ?? 0), (int)($r['lk'] ?? 0), (int)($r['mt'] ?? 0), (int)($r['ms'] ?? 0), (int)($r['pr'] ?? 0)];
    $out = [];
    foreach ($steps as $i => [$key, $label]) {
        $out[] = [$key, $label, $counts[$i], $counts[0] > 0 ? $counts[$i] / $counts[0] * 100 : 0.0, $i === 0 ? 100.0 : ($counts[$i - 1] > 0 ? $counts[$i] / $counts[$i - 1] * 100 : 0.0)];
    }
    return $out;
}

/**
 * Money for a range: totals, by type, by plan, by method, and the boost, gift and booking numbers.
 * @return array
 */
function admin_analytics_revenue(string $from, string $to): array
{
    $pd = admin_core_pay_date('p');
    $p = ['f' => $from, 't' => admin_analytics_next($to)];
    $w = "p.status = 'completed' AND $pd >= :f AND $pd < :t";
    $types = admin_core_try_rows("SELECT p.payment_type AS k, COUNT(*) AS n, COALESCE(SUM(p.amount), 0) AS amt FROM payments p WHERE $w GROUP BY p.payment_type ORDER BY amt DESC", $p);
    $methods = admin_core_try_rows("SELECT p.payment_method AS k, COUNT(*) AS n, COALESCE(SUM(p.amount), 0) AS amt FROM payments p WHERE $w GROUP BY p.payment_method ORDER BY amt DESC", $p);
    $plans = [];
    foreach (admin_core_try_rows("SELECT p.amount, p.metadata FROM payments p WHERE $w AND p.payment_type = 'subscription' LIMIT 20000", $p) as $r) {
        $meta = json_decode((string)$r['metadata'], true);
        $name = is_array($meta) && !empty($meta['plan_name']) ? (string)$meta['plan_name'] : (is_array($meta) && !empty($meta['plan_slug']) ? ucfirst((string)$meta['plan_slug']) : 'Plan not recorded');
        $plans[$name] = ($plans[$name] ?? ['n' => 0, 'amt' => 0.0]);
        $plans[$name]['n']++;
        $plans[$name]['amt'] += (float)$r['amount'];
    }
    uasort($plans, fn($a, $b) => $b['amt'] <=> $a['amt']);
    $total = array_sum(array_map(fn($r) => (float)$r['amt'], $types));
    $count = array_sum(array_map(fn($r) => (int)$r['n'], $types));
    $payers = (int)admin_core_try_val("SELECT COUNT(DISTINCT p.user_id) FROM payments p WHERE $w", $p, 0);
    $refunded = (float)admin_core_try_val("SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.status = 'refunded' AND $pd >= :f AND $pd < :t", $p, 0);
    $typeAmt = [];
    $typeN = [];
    foreach ($types as $r) {
        $typeAmt[$r['k']] = (float)$r['amt'];
        $typeN[$r['k']] = (int)$r['n'];
    }
    $bookings = admin_core_try_rows("SELECT COUNT(*) AS n,
            COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','declined') THEN total_amount ELSE 0 END), 0) AS value,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN commission_amount ELSE 0 END), 0) AS commission,
            COALESCE(SUM(status = 'completed'), 0) AS completed
        FROM host_bookings WHERE created_at >= :f AND created_at < :t", $p)[0] ?? ['n' => 0, 'value' => 0, 'commission' => 0, 'completed' => 0];
    return [
        'total' => $total, 'count' => $count, 'payers' => $payers, 'refunded' => $refunded, 'types' => $types, 'methods' => $methods, 'plans' => $plans,
        'type_amt' => $typeAmt, 'type_n' => $typeN,
        'boosts' => (int)admin_core_try_val('SELECT COUNT(*) FROM boosts WHERE started_at >= :f AND started_at < :t', $p, 0),
        'gifts' => (int)admin_core_try_val('SELECT COUNT(*) FROM sent_gifts WHERE created_at >= :f AND created_at < :t', $p, 0),
        'bookings' => $bookings,
    ];
}

/** Where active members are: top counties and towns, with how many of them joined in the range. */
function admin_analytics_geo(string $from, string $to, int $limit = 8): array
{
    $p = ['f' => $from, 't' => admin_analytics_next($to)];
    $counties = admin_core_try_rows("SELECT l.county_name AS name, COUNT(*) AS total, SUM(u.created_at >= :f AND u.created_at < :t) AS fresh
        FROM users u JOIN user_locations l ON l.user_id = u.id
        WHERE u.role <> 'admin' AND u.status = 'active' AND COALESCE(l.county_name, '') <> ''
        GROUP BY l.county_name ORDER BY total DESC, name LIMIT " . (int)$limit, $p);
    $towns = admin_core_try_rows("SELECT l.town_name AS name, MIN(l.county_name) AS county, COUNT(*) AS total, SUM(u.created_at >= :f AND u.created_at < :t) AS fresh
        FROM users u JOIN user_locations l ON l.user_id = u.id
        WHERE u.role <> 'admin' AND u.status = 'active' AND COALESCE(l.town_name, '') <> ''
        GROUP BY l.town_name ORDER BY total DESC, name LIMIT " . (int)$limit, $p);
    $noLoc = (int)admin_core_try_val("SELECT COUNT(*) FROM users u LEFT JOIN user_locations l ON l.user_id = u.id WHERE u.role <> 'admin' AND u.status = 'active' AND l.user_id IS NULL", [], 0);
    return ['counties' => $counties, 'towns' => $towns, 'no_location' => $noLoc];
}

/** Gender and age of active members. */
function admin_analytics_people(): array
{
    $gender = admin_core_try_rows("SELECT COALESCE(p.gender, 'unknown') AS k, COUNT(*) AS n FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
        WHERE u.role <> 'admin' AND u.status = 'active' GROUP BY COALESCE(p.gender, 'unknown') ORDER BY n DESC");
    $age = admin_core_try_rows("SELECT CASE
            WHEN a < 18 THEN 'Under 18' WHEN a < 25 THEN '18-24' WHEN a < 30 THEN '25-29' WHEN a < 35 THEN '30-34'
            WHEN a < 40 THEN '35-39' WHEN a < 50 THEN '40-49' ELSE '50+' END AS k, COUNT(*) AS n
        FROM (SELECT TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) AS a FROM users u JOIN user_profiles p ON p.user_id = u.id
              WHERE u.role <> 'admin' AND u.status = 'active' AND p.date_of_birth IS NOT NULL) x GROUP BY k");
    $by = [];
    foreach ($age as $r) {
        $by[$r['k']] = (int)$r['n'];
    }
    $order = ['Under 18', '18-24', '25-29', '30-34', '35-39', '40-49', '50+'];
    $ages = [];
    foreach ($order as $o) {
        if ($o === 'Under 18' && empty($by[$o])) {
            continue;
        }
        $ages[$o] = $by[$o] ?? 0;
    }
    $labels = ['male' => 'Men', 'female' => 'Women', 'non-binary' => 'Non-binary', 'other' => 'Other', 'unknown' => 'Not given'];
    $g = [];
    foreach ($gender as $r) {
        $g[] = [$labels[$r['k']] ?? ucfirst((string)$r['k']), (int)$r['n']];
    }
    return ['gender' => $g, 'age' => $ages];
}

/**
 * Weekly sign-up cohorts (last $weeks weeks, Monday start): did they come back, are they still around, did they match and message.
 * @return array list of [week_start, size, back, a7, a30, matched, messaged]
 */
function admin_analytics_cohorts(int $weeks = 8): array
{
    $start = date('Y-m-d', strtotime('monday this week -' . (($weeks - 1) * 7) . ' days'));
    $rows = admin_core_try_rows("SELECT DATE_SUB(DATE(u.created_at), INTERVAL WEEKDAY(u.created_at) DAY) AS wk, COUNT(*) AS n,
            COALESCE(SUM(u.last_active_at >= (u.created_at + INTERVAL 1 DAY)), 0) AS back,
            COALESCE(SUM(u.last_active_at >= (NOW() - INTERVAL 7 DAY)), 0) AS a7,
            COALESCE(SUM(u.last_active_at >= (NOW() - INTERVAL 30 DAY)), 0) AS a30,
            COALESCE(SUM(EXISTS(SELECT 1 FROM matches m WHERE m.user1_id = u.id OR m.user2_id = u.id)), 0) AS mat,
            COALESCE(SUM(EXISTS(SELECT 1 FROM messages g WHERE g.sender_id = u.id AND g.type <> 'system')), 0) AS msg
        FROM users u WHERE u.role <> 'admin' AND u.created_at >= :f GROUP BY wk ORDER BY wk", ['f' => $start]);
    $by = [];
    foreach ($rows as $r) {
        $by[$r['wk']] = $r;
    }
    $out = [];
    for ($i = 0; $i < $weeks; $i++) {
        $wk = date('Y-m-d', strtotime($start . ' +' . ($i * 7) . ' days'));
        $r = $by[$wk] ?? null;
        $out[] = [$wk, (int)($r['n'] ?? 0), (int)($r['back'] ?? 0), (int)($r['a7'] ?? 0), (int)($r['a30'] ?? 0), (int)($r['mat'] ?? 0), (int)($r['msg'] ?? 0)];
    }
    return $out;
}

/** Everything the analytics page and its exports need for one range, cached for a minute. */
function admin_analytics_bundle(array $rg): array
{
    $key = 'admin_an_' . md5($rg['from'] . $rg['to']);
    return cache_remember($key, 60, function () use ($rg) {
        $all = admin_core_dates($rg['prev_from'], $rg['to']);
        $cur = array_slice($all, -$rg['days']);
        $prev = array_slice($all, 0, count($all) - $rg['days']);
        $series = [];
        foreach (array_keys(admin_analytics_metrics()) as $m) {
            $v = admin_analytics_series($m, $all);
            $series[$m] = ['cur' => array_slice($v, -$rg['days']), 'prev' => array_slice($v, 0, count($all) - $rg['days'])];
        }
        $revPrev = admin_analytics_revenue($rg['prev_from'], $rg['prev_to']);
        return [
            'dates' => $cur, 'prev_dates' => $prev, 'series' => $series,
            'funnel' => admin_analytics_funnel($rg['from'], $rg['to']),
            'revenue' => admin_analytics_revenue($rg['from'], $rg['to']),
            'revenue_prev_payers' => $revPrev['payers'],
            'geo' => admin_analytics_geo($rg['from'], $rg['to']),
            'people' => admin_analytics_people(),
            'cohorts' => admin_analytics_cohorts(8),
            'stored_days' => (int)admin_core_try_val("SELECT COUNT(DISTINCT stat_date) FROM analytics_daily WHERE stat_date >= :f AND stat_date <= :t", ['f' => $rg['from'], 't' => $rg['to']], 0),
        ];
    });
}
