<?php
/**
 * Member listing: one query builder used by the landing page, Browse, Discover, Search,
 * Likes, Visitors and Favourites so that visibility rules (status, paused profiles,
 * blocks, privacy flags, photo moderation) are applied identically everywhere.
 */

declare(strict_types=1);

const MEMBER_CARD_COLS = 'u.id, u.role, u.featured_until, u.last_active_at, u.created_at AS joined_at,
    p.display_name, p.date_of_birth, p.gender, p.interested_in, p.bio, p.occupation, p.looking_for, p.intent, p.is_verified,
    p.show_online, p.show_last_active, p.show_distance, p.show_age, p.profile_completion,
    ul.county_name, ul.town_name, ul.latitude AS lat, ul.longitude AS lng,
    EXISTS(SELECT 1 FROM boosts b WHERE b.user_id = u.id AND b.expires_at > NOW()) AS is_boosted';

/** Viewer's coordinates [lat, lng] or null. */
function viewer_coords(): ?array
{
    static $c = false;
    if ($c === false) {
        $c = null;
        $uid = auth_id();
        if ($uid) {
            $l = user_location($uid);
            if ($l && $l['latitude'] !== null) {
                $c = [(float)$l['latitude'], (float)$l['longitude']];
            }
        } elseif (($g = guest_location()) && $g['latitude'] !== null) {
            $c = [(float)$g['latitude'], (float)$g['longitude']];
        }
    }
    return $c;
}

/**
 * Find members. Filters (all optional):
 *  gender            male|female|non-binary|other|array   (who to show)
 *  age_min, age_max  ints
 *  distance_km       int (needs the viewer's location)
 *  county_id, location_id   limit to a county / town (location_id may be a county or a town id)
 *  county_slug       limit to a county or town by slug
 *  verified          true = verified only
 *  online            true = active in the last online_window_minutes
 *  active_days       int  active within N days
 *  has_photo         true (default) = must have an approved primary photo
 *  has_bio           true
 *  height_min, height_max, education (LIKE), body_type
 *  intent            string
 *  interest_ids      array of interest ids (must share at least one)
 *  exclude_ids       array of user ids to leave out
 *  only_ids          array of user ids to restrict to
 *  role              user (default) | host | any
 *  q                 name search
 *  hide_swiped       true = skip people the viewer already liked/passed
 *  order             smart (default) | newest | active | distance | random | featured | score
 *  seed              string seed for stable random ordering
 *
 * @return array ['rows' => normalised member rows, 'total' => int]
 */
function members_search(array $f = [], int $limit = 24, int $offset = 0): array
{
    $viewer = current_user();
    $params = [];
    $where = ["u.status = 'active'", 'p.onboarding_completed = 1', 'p.is_paused = 0', 'p.elite_only = 0']; // elite_only: Elite members who chose to stay out of ordinary dating
    $role = $f['role'] ?? 'user';
    if ($role !== 'any') {
        $where[] = 'u.role = :role';
        $params['role'] = $role;
    } else {
        $where[] = "u.role IN ('user','host')";
    }
    if ($role === 'host') {
        $where[] = "EXISTS (SELECT 1 FROM host_profiles hp WHERE hp.user_id = u.id AND hp.approval_status = 'approved'" . (function_exists('hosts_listing_sql') ? hosts_listing_sql('hp') : '') . ')';
    }
    if ($viewer) {
        $params['me'] = $viewer['id'];
        $where[] = 'u.id <> :me';
        $where[] = 'NOT EXISTS (SELECT 1 FROM blocks bl WHERE (bl.blocker_id = :me AND bl.blocked_id = u.id) OR (bl.blocker_id = u.id AND bl.blocked_id = :me))';
        if (!empty($f['hide_swiped'])) {
            $where[] = 'NOT EXISTS (SELECT 1 FROM swipes sw WHERE sw.swiper_id = :me AND sw.swiped_id = u.id)';
        }
    }
    // Incognito members stay out of listings unless they have liked the viewer.
    $where[] = $viewer
        ? "(p.is_incognito = 0 OR EXISTS (SELECT 1 FROM swipes il WHERE il.swiper_id = u.id AND il.swiped_id = :me AND il.direction IN ('like','superlike')))"
        : 'p.is_incognito = 0';
    if (!empty($f['gender'])) {
        $g = (array)$f['gender'];
        if (!in_array('everyone', $g, true)) {
            $where[] = 'p.gender IN (:genders)';
            $params['genders'] = array_values($g);
        }
    }
    if (!empty($f['age_min'])) {
        $where[] = 'p.date_of_birth <= DATE_SUB(CURDATE(), INTERVAL :amin YEAR)';
        $params['amin'] = (int)$f['age_min'];
    }
    if (!empty($f['age_max'])) {
        $where[] = 'p.date_of_birth > DATE_SUB(CURDATE(), INTERVAL :amax YEAR)';
        $params['amax'] = (int)$f['age_max'] + 1;
    }
    if (!empty($f['verified'])) {
        $where[] = 'p.is_verified = 1';
    }
    if (!empty($f['online'])) {
        $where[] = 'u.last_active_at > DATE_SUB(NOW(), INTERVAL :ow MINUTE) AND p.show_online = 1';
        $params['ow'] = max(1, (int)setting('online_window_minutes'));
    }
    if (!empty($f['active_days'])) {
        $where[] = 'u.last_active_at > DATE_SUB(NOW(), INTERVAL :ad DAY)';
        $params['ad'] = (int)$f['active_days'];
    }
    if (!isset($f['has_photo']) || $f['has_photo']) {
        $where[] = 'ph.id IS NOT NULL';
    }
    if (!empty($f['has_bio'])) {
        $where[] = "p.bio IS NOT NULL AND CHAR_LENGTH(p.bio) > 20";
    }
    if (!empty($f['height_min'])) {
        $where[] = 'p.height_cm >= :hmin';
        $params['hmin'] = (int)$f['height_min'];
    }
    if (!empty($f['height_max'])) {
        $where[] = 'p.height_cm <= :hmax';
        $params['hmax'] = (int)$f['height_max'];
    }
    if (!empty($f['education'])) {
        $where[] = 'p.education LIKE :edu';
        $params['edu'] = '%' . $f['education'] . '%';
    }
    if (!empty($f['body_type'])) {
        $where[] = 'p.body_type = :bt';
        $params['bt'] = $f['body_type'];
    }
    if (!empty($f['intent'])) {
        $where[] = 'p.intent = :intent';
        $params['intent'] = $f['intent'];
    }
    if (!empty($f['interest_ids'])) {
        $where[] = 'EXISTS (SELECT 1 FROM user_interests ui WHERE ui.user_id = u.id AND ui.interest_id IN (:iids))';
        $params['iids'] = array_map('intval', (array)$f['interest_ids']);
    }
    if (!empty($f['exclude_ids'])) {
        $where[] = 'u.id NOT IN (:xids)';
        $params['xids'] = array_map('intval', (array)$f['exclude_ids']);
    }
    if (isset($f['only_ids'])) {
        $ids = array_map('intval', (array)$f['only_ids']);
        if (!$ids) {
            return ['rows' => [], 'total' => 0];
        }
        $where[] = 'u.id IN (:oids)';
        $params['oids'] = $ids;
    }
    if (!empty($f['q'])) {
        $where[] = '(p.display_name LIKE :q OR ul.town_name LIKE :q OR ul.county_name LIKE :q)';
        $params['q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$f['q']) . '%';
    }
    // Location filters
    if (!empty($f['county_slug'])) {
        $loc = location_by_slug((string)$f['county_slug']);
        if ($loc) {
            $f[$loc['type'] === 'county' ? 'county_id' : 'location_id'] = (int)$loc['id'];
        }
    }
    if (!empty($f['county_id'])) {
        $where[] = 'ul.county_id = :cid';
        $params['cid'] = (int)$f['county_id'];
    }
    if (!empty($f['location_id'])) {
        $where[] = '(ul.location_id = :lid OR ul.county_id = :lid)';
        $params['lid'] = (int)$f['location_id'];
    }
    $coords = viewer_coords();
    $distSql = $coords ? sql_distance(':vlat', ':vlng', 'ul.latitude', 'ul.longitude') : 'NULL';
    $hasDistance = (bool)$coords;
    if ($coords) {
        $params['vlat'] = $coords[0];
        $params['vlng'] = $coords[1];
        if (!empty($f['distance_km'])) {
            $box = geo_bbox($coords[0], $coords[1], (float)$f['distance_km']);
            $where[] = 'ul.latitude BETWEEN :b1 AND :b2 AND ul.longitude BETWEEN :b3 AND :b4';
            $params += ['b1' => $box['min_lat'], 'b2' => $box['max_lat'], 'b3' => $box['min_lng'], 'b4' => $box['max_lng']];
            $where[] = $distSql . ' <= :dkm';
            $params['dkm'] = (float)$f['distance_km'];
        }
    }

    $order = (string)($f['order'] ?? 'smart');
    $seed = (string)($f['seed'] ?? date('Ymd'));
    $boostFirst = 'is_boosted DESC, (u.featured_until IS NOT NULL AND u.featured_until > NOW()) DESC';
    switch ($order) {
        case 'newest':
            $orderBy = 'u.created_at DESC';
            break;
        case 'active':
            $orderBy = 'u.last_active_at DESC';
            break;
        case 'distance':
            $orderBy = $hasDistance ? 'distance_km IS NULL, distance_km ASC' : 'u.last_active_at DESC';
            break;
        case 'random':
            $orderBy = 'MD5(CONCAT(u.id, :seed))';
            $params['seed'] = $seed;
            break;
        case 'featured':
            $orderBy = $boostFirst . ', u.last_active_at DESC';
            break;
        case 'score':
            // Unscored members get a neutral 35; boosted and featured members count for boost_multiplier times their score.
            $orderBy = $viewer ? '(COALESCE(asr.total_score, 35) * IF(is_boosted = 1 OR (u.featured_until IS NOT NULL AND u.featured_until > NOW()), :bm, 1)) DESC, ' . $boostFirst . ', u.last_active_at DESC' : $boostFirst . ', u.last_active_at DESC';
            if ($viewer) {
                $params['bm'] = max(1.0, (float)setting('boost_multiplier'));
            }
            break;
        default: // smart: boosted and featured first, then closest, then most recently active
            $orderBy = $boostFirst . ', ' . ($hasDistance ? 'distance_km IS NULL, distance_km ASC, ' : '') . 'u.last_active_at DESC';
    }
    $scoreJoin = ($order === 'score' && $viewer) ? 'LEFT JOIN algorithm_scores asr ON asr.user_id = :me AND asr.target_id = u.id' : '';

    $from = 'FROM users u JOIN user_profiles p ON p.user_id = u.id
             LEFT JOIN user_locations ul ON ul.user_id = u.id ' . sql_photo_join('u.id', 'ph') . ' ' . $scoreJoin;
    $whereSql = implode(' AND ', $where);
    $total = (int)val('SELECT COUNT(*) ' . $from . ' WHERE ' . $whereSql, array_diff_key($params, ['seed' => 1, 'bm' => 1]));
    $sql = 'SELECT ' . MEMBER_CARD_COLS . ', ' . SQL_PHOTO_COLS . ', ' . $distSql . ' AS distance_km ' . $from . ' WHERE ' . $whereSql
        . ' ORDER BY ' . $orderBy . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
    $rows = array_map('member_normalize', rows($sql, $params));
    return ['rows' => $rows, 'total' => $total];
}

/** Turn a raw query row into display-ready data, honouring the member's privacy flags. */
function member_normalize(array $r): array
{
    $age = age_from_dob($r['date_of_birth'] ?? null);
    $name = trim((string)($r['display_name'] ?? '')) ?: 'Member';
    $online = !empty($r['last_active_at']) && (int)($r['show_online'] ?? 1) === 1
        && strtotime((string)$r['last_active_at']) > time() - max(1, (int)setting('online_window_minutes')) * 60;
    $km = isset($r['distance_km']) && $r['distance_km'] !== null && (int)($r['show_distance'] ?? 1) === 1 ? (float)$r['distance_km'] : null;
    return [
        'id' => (int)$r['id'], 'role' => $r['role'] ?? 'user', 'name' => $name, 'first_name' => explode(' ', $name)[0],
        'age' => (int)($r['show_age'] ?? 1) === 1 ? $age : null, 'gender' => $r['gender'] ?? null,
        'bio' => $r['bio'] ?? '', 'occupation' => $r['occupation'] ?? '', 'intent' => $r['intent'] ?? '', 'looking_for' => $r['looking_for'] ?? '',
        'verified' => (int)($r['is_verified'] ?? 0) === 1, 'online' => $online,
        'last_active' => (int)($r['show_last_active'] ?? 1) === 1 ? ($r['last_active_at'] ?? null) : null,
        'distance_km' => $km, 'distance_label' => $km !== null ? distance_label($km) : '',
        'location' => location_label(['town_name' => $r['town_name'] ?? '', 'county_name' => $r['county_name'] ?? '']),
        'county' => $r['county_name'] ?? '', 'town' => $r['town_name'] ?? '',
        'featured' => !empty($r['featured_until']) && strtotime((string)$r['featured_until']) > time(), 'boosted' => !empty($r['is_boosted']),
        'completion' => (int)($r['profile_completion'] ?? 0), 'joined_at' => $r['joined_at'] ?? null,
        'photo' => photo_from_row($r), 'raw' => $r,
    ];
}

/** Interests for a batch of members: [userId => [ ['id','name','emoji'], … ]] */
function members_interests(array $userIds, int $perMember = 6): array
{
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    if (!$userIds) {
        return [];
    }
    $out = [];
    foreach (rows('SELECT ui.user_id, i.id, i.name, i.emoji, i.icon, i.category FROM user_interests ui JOIN interests i ON i.id = ui.interest_id
                   WHERE ui.user_id IN (:ids) AND i.is_active = 1 ORDER BY i.sort_order', ['ids' => $userIds]) as $r) {
        $out[(int)$r['user_id']][] = $r;
    }
    foreach ($out as $k => $v) {
        $out[$k] = array_slice($v, 0, $perMember);
    }
    return $out;
}

/** Counts used by the landing page and auth pages. Cached for 10 minutes. */
function site_stats(): array
{
    return cache_remember('site_stats', 600, function () {
        return [
            'members' => (int)val("SELECT COUNT(*) FROM users WHERE role = 'user' AND status = 'active'"),
            'active_week' => (int)val("SELECT COUNT(*) FROM users WHERE role IN ('user','host') AND last_active_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'matches' => (int)val('SELECT COUNT(*) FROM matches'),
            'hosts' => (int)val("SELECT COUNT(*) FROM host_profiles WHERE approval_status = 'approved'"),
            'counties' => (int)val("SELECT COUNT(DISTINCT county_id) FROM user_locations WHERE county_id IS NOT NULL"),
        ];
    });
}
