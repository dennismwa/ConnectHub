<?php
/**
 * Compatibility scoring. For each member we score nearby candidates on seven components
 * (shared interests, distance, age and preference fit, what they linger on, what they search for,
 * recent activity, profile quality), weighted by the admin's w_* settings, and store the result in
 * algorithm_scores. Discover ranks by it. Scores are refreshed lazily when a member opens Discover
 * and by cron through matching_recompute_all(). Safe to call from the command line.
 */

declare(strict_types=1);

/** The seven weights as fractions that add up to 1. */
function matching_weights(): array
{
    $w = [
        'interest' => max(0.0, (float)setting('w_interests')),
        'location' => max(0.0, (float)setting('w_location')),
        'demographic' => max(0.0, (float)setting('w_demographics')),
        'dwell' => max(0.0, (float)setting('w_dwell')),
        'search' => max(0.0, (float)setting('w_search')),
        'activity' => max(0.0, (float)setting('w_activity')),
        'quality' => max(0.0, (float)setting('w_quality')),
    ];
    $sum = array_sum($w);
    if ($sum <= 0) {
        return array_map(fn() => 1 / 7, $w);
    }
    return array_map(fn($v) => $v / $sum, $w);
}

/** Everything the scorer needs about a set of members, keyed by user id. */
function matching_load(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $out = [];
    foreach (rows("SELECT u.id, u.last_active_at, u.created_at, p.gender, p.interested_in, p.date_of_birth, p.intent, p.is_verified, p.profile_completion,
                          p.education, p.body_type, p.height_cm, ul.latitude AS lat, ul.longitude AS lng, ul.county_id, ul.location_id,
                          dp.age_min, dp.age_max, dp.distance_km,
                          (SELECT COUNT(*) FROM user_photos ph WHERE ph.user_id = u.id AND ph.moderation_status = 'approved') AS photo_count
                   FROM users u JOIN user_profiles p ON p.user_id = u.id
                   LEFT JOIN user_locations ul ON ul.user_id = u.id
                   LEFT JOIN discovery_preferences dp ON dp.user_id = u.id
                   WHERE u.id IN (:ids)", ['ids' => $ids]) as $r) {
        $r['id'] = (int)$r['id'];
        foreach (['county_id', 'location_id'] as $k) {
            $r[$k] = $r[$k] !== null ? (int)$r[$k] : null;
        }
        $r['age'] = age_from_dob($r['date_of_birth']);
        $r['interests'] = [];
        $out[$r['id']] = $r;
    }
    if ($out) {
        foreach (rows('SELECT user_id, interest_id FROM user_interests WHERE user_id IN (:ids)', ['ids' => array_keys($out)]) as $r) {
            $out[(int)$r['user_id']]['interests'][(int)$r['interest_id']] = true;
        }
    }
    return $out;
}

/**
 * What the member's own behaviour says about their taste: the interests and intents of profiles they
 * liked, favourited or looked at for a while, plus their recent searches, plus who liked or passed on whom.
 */
function matching_context(int $userId): ?array
{
    $me = matching_load([$userId])[$userId] ?? null;
    if (!$me) {
        return null;
    }
    $liked = [];
    $nope = [];
    $likedMe = [];
    $lingered = [];
    foreach (rows('SELECT swiped_id, direction, dwell_ms FROM swipes WHERE swiper_id = :u ORDER BY id DESC LIMIT 400', ['u' => $userId]) as $s) {
        $id = (int)$s['swiped_id'];
        if ($s['direction'] === 'nope') {
            $nope[$id] = true;
        } else {
            $liked[$id] = true;
        }
        if ($s['direction'] !== 'nope' || (int)$s['dwell_ms'] >= 6000) {
            if (count($lingered) < 60) {
                $lingered[$id] = $s['direction'] === 'superlike' ? 2 : ((int)$s['dwell_ms'] >= 6000 ? 1.5 : 1);
            }
        }
    }
    foreach (col('SELECT target_id FROM favorites WHERE user_id = :u ORDER BY created_at DESC LIMIT 30', ['u' => $userId]) as $id) {
        $lingered[(int)$id] = max($lingered[(int)$id] ?? 0, 2);
    }
    foreach (rows("SELECT swiper_id, direction FROM swipes WHERE swiped_id = :u AND direction IN ('like','superlike') ORDER BY id DESC LIMIT 500", ['u' => $userId]) as $s) {
        $likedMe[(int)$s['swiper_id']] = $s['direction'];
    }

    // Taste profile from the profiles they lingered on.
    $taste = ['interests' => [], 'intents' => [], 'n' => 0];
    if ($lingered) {
        $hist = matching_load(array_keys($lingered));
        $total = 0.0;
        foreach ($hist as $id => $h) {
            $wgt = (float)$lingered[$id];
            $total += $wgt;
            foreach (array_keys($h['interests']) as $iid) {
                $taste['interests'][$iid] = ($taste['interests'][$iid] ?? 0) + $wgt;
            }
            if ($h['intent'] !== null && $h['intent'] !== '') {
                $taste['intents'][$h['intent']] = ($taste['intents'][$h['intent']] ?? 0) + $wgt;
            }
        }
        if ($total > 0) {
            foreach ($taste['interests'] as $k => $v) {
                $taste['interests'][$k] = $v / $total;
            }
            foreach ($taste['intents'] as $k => $v) {
                $taste['intents'][$k] = $v / $total;
            }
            $taste['n'] = count($hist);
        }
    }

    // Recent searches (see /search): the attributes they keep asking for.
    $searches = [];
    foreach (rows("SELECT filters FROM search_history WHERE user_id = :u AND filters IS NOT NULL AND filters <> '' ORDER BY id DESC LIMIT 15", ['u' => $userId]) as $s) {
        $f = json_decode((string)$s['filters'], true);
        if (is_array($f) && $f) {
            $searches[] = $f;
        }
    }
    return ['me' => $me, 'liked' => $liked, 'nope' => $nope, 'liked_me' => $likedMe, 'taste' => $taste, 'searches' => $searches, 'weights' => matching_weights()];
}

function matching_clamp(float $v): float
{
    return max(0.0, min(100.0, $v));
}

/**
 * Score one candidate for the member in $ctx (from matching_context()). $t is a row from matching_load().
 * Returns the seven components (0-100), the total (with bonuses) and flags.
 */
function matching_pair(array $ctx, array $t): array
{
    $me = $ctx['me'];
    $w = $ctx['weights'];

    // 1. Shared interests
    $mine = $me['interests'];
    $theirs = $t['interests'];
    if (!$mine || !$theirs) {
        $interest = 30.0;
    } else {
        $shared = count(array_intersect_key($mine, $theirs));
        $interest = matching_clamp(100 * $shared / max(3, min(count($mine), count($theirs), 6)));
    }

    // 2. Distance
    $radius = max(10.0, (float)($me['distance_km'] ?: setting('radius_default')));
    if ($me['lat'] !== null && $t['lat'] !== null) {
        $km = haversine_km((float)$me['lat'], (float)$me['lng'], (float)$t['lat'], (float)$t['lng']);
        $location = $km <= 3 ? 100.0 : 100 * exp(-$km / ($radius * 0.6));
        if ($me['location_id'] && $me['location_id'] === $t['location_id']) {
            $location = max($location, 92.0);
        }
    } elseif ($me['county_id'] && $me['county_id'] === $t['county_id']) {
        $location = 70.0;
    } else {
        $location = 35.0;
    }

    // 3. Age and preference fit, both ways, plus what they are looking for
    $demo = 0.0;
    $mineWants = in_array($me['interested_in'], ['everyone', $t['gender']], true);
    $theyWant = in_array($t['interested_in'], ['everyone', $me['gender']], true);
    $demo += ($mineWants ? 20 : 0) + ($theyWant ? 15 : 0);
    $ageFit = function (?int $age, $lo, $hi): float {
        if ($age === null || $lo === null || $hi === null) {
            return 0.6;
        }
        if ($age >= (int)$lo && $age <= (int)$hi) {
            return 1.0;
        }
        $off = $age < (int)$lo ? (int)$lo - $age : $age - (int)$hi;
        return max(0.0, 1 - $off / 8);
    };
    $demo += 25 * $ageFit($t['age'], $me['age_min'], $me['age_max']);
    $demo += 15 * $ageFit($me['age'], $t['age_min'], $t['age_max']);
    if (($me['intent'] ?? '') !== '' && ($t['intent'] ?? '') !== '') {
        $demo += $me['intent'] === $t['intent'] ? 25 : 8;
    } else {
        $demo += 10;
    }
    $demo = matching_clamp($demo);

    // 4. Taste learned from profiles they liked or lingered on
    $taste = $ctx['taste'];
    if ($taste['n'] < 3) {
        $dwell = 50.0;
    } else {
        $sum = 0.0;
        foreach (array_keys($theirs) as $iid) {
            $sum += $taste['interests'][$iid] ?? 0;
        }
        $part = $theirs ? min(1.0, $sum / max(1, min(5, count($theirs))) * 2.2) : 0.0;
        $intent = ($t['intent'] ?? '') !== '' ? min(1.0, ($taste['intents'][$t['intent']] ?? 0) * 1.5) : 0.4;
        $dwell = matching_clamp(100 * (0.7 * $part + 0.3 * $intent));
    }

    // 5. What they keep searching for
    $search = 50.0;
    if ($ctx['searches']) {
        $acc = 0.0;
        $wsum = 0.0;
        foreach ($ctx['searches'] as $i => $f) {
            $considered = 0;
            $hit = 0;
            if (!empty($f['show']) && in_array($f['show'], ['male', 'female'], true)) {
                $considered++;
                $hit += $t['gender'] === $f['show'] ? 1 : 0;
            }
            if (!empty($f['age_min']) && !empty($f['age_max']) && $t['age'] !== null) {
                $considered++;
                $hit += ($t['age'] >= (int)$f['age_min'] && $t['age'] <= (int)$f['age_max']) ? 1 : 0;
            }
            if (!empty($f['town']) || !empty($f['county'])) {
                $considered++;
                $hit += (!empty($f['town']) && (int)$f['town'] === $t['location_id']) || (!empty($f['county']) && (int)$f['county'] === $t['county_id']) ? 1 : 0;
            }
            if (!empty($f['intent'])) {
                $considered++;
                $hit += $t['intent'] === $f['intent'] ? 1 : 0;
            }
            if (!empty($f['interests']) && is_array($f['interests'])) {
                $considered++;
                $hit += array_intersect_key($theirs, array_flip(array_map('intval', $f['interests']))) ? 1 : 0;
            }
            if (!empty($f['education'])) {
                $considered++;
                $hit += $t['education'] === $f['education'] ? 1 : 0;
            }
            if (!empty($f['body_type'])) {
                $considered++;
                $hit += $t['body_type'] === $f['body_type'] ? 1 : 0;
            }
            if ($considered > 0) {
                $recency = 1 / (1 + $i * 0.35);
                $acc += $recency * 100 * $hit / $considered;
                $wsum += $recency;
            }
        }
        $search = $wsum > 0 ? matching_clamp($acc / $wsum) : 50.0;
    }

    // 6. Recent activity
    $activity = 5.0;
    if (!empty($t['last_active_at'])) {
        $mins = (time() - strtotime((string)$t['last_active_at'])) / 60;
        if ($mins <= max(1, (int)setting('online_window_minutes'))) {
            $activity = 100.0;
        } elseif ($mins <= 1440) {
            $activity = 85.0;
        } elseif ($mins <= 4320) {
            $activity = 65.0;
        } elseif ($mins <= 10080) {
            $activity = 45.0;
        } elseif ($mins <= 43200) {
            $activity = 20.0;
        }
    }

    // 7. Profile quality
    $quality = matching_clamp((int)$t['profile_completion'] * 0.65 + ((int)$t['is_verified'] === 1 ? 15 : 0) + min(3, (int)$t['photo_count']) / 3 * 20);

    $comp = ['interest' => $interest, 'location' => $location, 'demographic' => $demo, 'dwell' => $dwell, 'search' => $search, 'activity' => $activity, 'quality' => $quality];
    $total = 0.0;
    foreach ($comp as $k => $v) {
        $total += $v * $w[$k];
    }

    // Nudges that are not about fit
    $hours = (int)setting('new_member_boost_hours');
    if ($hours > 0 && !empty($t['created_at'])) {
        $ageH = (time() - strtotime((string)$t['created_at'])) / 3600;
        if ($ageH < $hours) {
            $total += (float)setting('new_member_boost_points') * (1 - $ageH / $hours);
        }
    }
    $id = (int)$t['id'];
    if (isset($ctx['liked_me'][$id])) {
        $total += (float)setting('match_liked_you_points') * ($ctx['liked_me'][$id] === 'superlike' ? 2 : 1);
    }
    if (isset($ctx['nope'][$id])) {
        $total -= (float)setting('match_nope_penalty');
    }
    $comp['total'] = matching_clamp($total);
    return $comp;
}

/** Fit score for display: the five components that describe how well two people suit each other. */
function matching_compat(array $c, array $w): float
{
    $keys = ['interest', 'location', 'demographic', 'dwell', 'search'];
    $sum = 0.0;
    $wsum = 0.0;
    foreach ($keys as $k) {
        $sum += (float)$c[$k] * $w[$k];
        $wsum += $w[$k];
    }
    return $wsum > 0 ? matching_clamp($sum / $wsum) : 50.0;
}

/** Write scored rows for one member in chunks. $scored: [targetId => components]. */
function matching_store(int $userId, array $scored, string $at): void
{
    foreach (array_chunk($scored, 100, true) as $chunk) {
        $marks = [];
        $args = [];
        foreach ($chunk as $tid => $c) {
            $marks[] = '(?,?,?,?,?,?,?,?,?,?,?)';
            array_push($args, $userId, (int)$tid, round($c['interest'], 2), round($c['location'], 2), round($c['demographic'], 2), round($c['dwell'], 2),
                round($c['search'], 2), round($c['activity'], 2), round($c['quality'], 2), round($c['total'], 2), $at);
        }
        db()->prepare('INSERT INTO algorithm_scores (user_id, target_id, interest_score, location_score, demographic_score, dwell_score, search_score, activity_score, quality_score, total_score, computed_at)
                       VALUES ' . implode(',', $marks) . '
                       ON DUPLICATE KEY UPDATE interest_score = VALUES(interest_score), location_score = VALUES(location_score), demographic_score = VALUES(demographic_score),
                         dwell_score = VALUES(dwell_score), search_score = VALUES(search_score), activity_score = VALUES(activity_score), quality_score = VALUES(quality_score),
                         total_score = VALUES(total_score), computed_at = VALUES(computed_at)')->execute($args);
    }
}

/** Score the member's best candidates again and replace what was stored. */
function matching_recompute_user(int $userId): void
{
    // Only ordinary members are scored: Hosts and admins never appear in Discover.
    if ($userId <= 0 || val("SELECT role FROM users WHERE id = :u AND status = 'active'", ['u' => $userId]) !== 'user') {
        return;
    }
    $ctx = matching_context($userId);
    if (!$ctx) {
        return;
    }
    $me = $ctx['me'];
    $limit = max(50, min(2000, (int)setting('matching_candidates')));
    $params = ['me' => $userId, 'g' => (string)$me['interested_in']];
    $dist = 'NULL';
    if ($me['lat'] !== null) {
        $dist = sql_distance(':vlat', ':vlng', 'ul.latitude', 'ul.longitude');
        $params['vlat'] = (float)$me['lat'];
        $params['vlng'] = (float)$me['lng'];
    }
    $ids = col("SELECT u.id FROM users u JOIN user_profiles p ON p.user_id = u.id LEFT JOIN user_locations ul ON ul.user_id = u.id
                WHERE u.status = 'active' AND u.role = 'user' AND p.onboarding_completed = 1 AND p.is_paused = 0 AND p.elite_only = 0 AND u.id <> :me
                  AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.blocker_id = :me AND b.blocked_id = u.id) OR (b.blocker_id = u.id AND b.blocked_id = :me))
                ORDER BY (:g = 'everyone' OR p.gender = :g) DESC, $dist IS NULL, $dist ASC, u.last_active_at DESC
                LIMIT $limit", $params);
    $ids = array_map('intval', $ids);
    $at = now();
    $scored = [];
    foreach (array_chunk($ids, 200) as $chunk) {
        foreach (matching_load($chunk) as $id => $t) {
            $scored[$id] = matching_pair($ctx, $t);
        }
    }
    db_transaction(function () use ($userId, $scored, $at) {
        if ($scored) {
            matching_store($userId, $scored, $at);
        }
        q('DELETE FROM algorithm_scores WHERE user_id = :u AND computed_at < :at', ['u' => $userId, 'at' => $at]);
    });
    cache_forget('matching_last_' . $userId);
}

/** Refresh every member's scores, oldest first. Returns how many members were refreshed. */
function matching_recompute_all(int $limit = 500): int
{
    $limit = max(1, $limit);
    $stale = max(1, (int)setting('matching_stale_hours'));
    $ids = col("SELECT u.id FROM users u JOIN user_profiles p ON p.user_id = u.id
                LEFT JOIN (SELECT user_id, MAX(computed_at) AS last_run FROM algorithm_scores GROUP BY user_id) s ON s.user_id = u.id
                WHERE u.status = 'active' AND u.role = 'user' AND p.onboarding_completed = 1 AND p.is_paused = 0
                  AND (s.last_run IS NULL OR s.last_run < DATE_SUB(NOW(), INTERVAL $stale HOUR))
                ORDER BY s.last_run IS NULL DESC, s.last_run ASC, u.last_active_at DESC
                LIMIT $limit");
    $n = 0;
    foreach ($ids as $id) {
        try {
            matching_recompute_user((int)$id);
            $n++;
        } catch (Throwable $e) {
            app_log('error', 'matching_recompute_user(' . $id . ') failed: ' . $e->getMessage());
        }
    }
    return $n;
}

/**
 * Make sure the member has recent scores: none yet, older than the admin's refresh time,
 * or new members have joined since the last run (checked at most every ten minutes).
 */
function matching_ensure_fresh(int $userId): void
{
    static $done = [];
    if (isset($done[$userId]) || $userId <= 0) {
        return;
    }
    $done[$userId] = true;
    $last = val('SELECT MAX(computed_at) FROM algorithm_scores WHERE user_id = :u', ['u' => $userId]);
    $need = false;
    if (!$last) {
        $need = true;
    } else {
        $age = time() - strtotime((string)$last);
        if ($age > max(1, (int)setting('matching_stale_hours')) * 3600) {
            $need = true;
        } elseif ($age > 600 && cache_get('matching_last_' . $userId) === null) {
            cache_put('matching_last_' . $userId, 1, 600);
            $need = (int)val("SELECT COUNT(*) FROM users u JOIN user_profiles p ON p.user_id = u.id
                              WHERE u.status = 'active' AND u.role = 'user' AND p.onboarding_completed = 1 AND u.id <> :u AND u.created_at > :t", ['u' => $userId, 't' => $last]) > 0;
        }
    }
    if ($need) {
        try {
            matching_recompute_user($userId);
        } catch (Throwable $e) {
            app_log('error', 'matching_recompute_user(' . $userId . ') failed: ' . $e->getMessage());
        }
    }
}

/**
 * Scores for specific candidates: [targetId => ['total','compat','interest',...]]. Pairs with no stored
 * score (new members, people outside the stored set) are scored on the spot and kept.
 */
function matching_scores(int $userId, array $targetIds): array
{
    $targetIds = array_values(array_unique(array_filter(array_map('intval', $targetIds), fn($i) => $i > 0 && $i !== $userId)));
    if (!$targetIds) {
        return [];
    }
    $w = matching_weights();
    $out = [];
    $fmt = function (array $r) use ($w) {
        $c = [
            'interest' => (float)$r['interest_score'], 'location' => (float)$r['location_score'], 'demographic' => (float)$r['demographic_score'],
            'dwell' => (float)$r['dwell_score'], 'search' => (float)$r['search_score'], 'activity' => (float)$r['activity_score'], 'quality' => (float)$r['quality_score'],
        ];
        return $c + ['total' => (float)$r['total_score'], 'compat' => matching_compat($c, $w)];
    };
    foreach (rows('SELECT * FROM algorithm_scores WHERE user_id = :u AND target_id IN (:ids)', ['u' => $userId, 'ids' => $targetIds]) as $r) {
        $out[(int)$r['target_id']] = $fmt($r);
    }
    $missing = array_slice(array_values(array_diff($targetIds, array_keys($out))), 0, 80);
    if ($missing) {
        $ctx = matching_context($userId);
        if ($ctx) {
            $now = now();
            $scored = [];
            foreach (matching_load($missing) as $id => $t) {
                $scored[$id] = matching_pair($ctx, $t);
                $out[$id] = $scored[$id] + ['compat' => matching_compat($scored[$id], $w)];
            }
            try {
                if ($scored) {
                    matching_store($userId, $scored, $now);
                }
            } catch (Throwable $e) {
                app_log('error', 'matching_store failed: ' . $e->getMessage());
            }
        }
    }
    return $out;
}
