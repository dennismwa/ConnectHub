<?php
/**
 * Elite: a private circle for established, successful people ("Elite members") and the members who want to meet them.
 *
 * Elite member  joins with an Elite profile (approved by an admin, optionally paid), sets who may reach them (criteria),
 *               receives requests, and accepts or declines each one. They can hide from ordinary dating (Discover, Browse).
 * Other members search Elite profiles. Before unlocking they see a teaser (first name, age, place, headline, a short
 *               excerpt, a blurred photo). The admin chooses how a full profile opens: paid unlock (single, pack of N, or a
 *               time pass), free, or only after the Elite member accepts. Contact always goes through a request: when the
 *               Elite member accepts, a chat opens and, if they chose to, their phone number shows.
 * Auto filter   each request gets a match score against the Elite member's criteria. Requests that miss a must-have, or
 *               score below their minimum, go to a "Filtered" folder without a notification.
 *
 * Tables: elite_profiles, elite_access, elite_unlocks, elite_requests (migration 0023). Payments use payment_type 'elite'
 * with metadata kind = join | single | pack | pass.
 */

// ---------------------------------------------------------------------------
// Basics, labels and option lists
// ---------------------------------------------------------------------------

function elite_enabled(): bool
{
    return (bool)setting('elite_enabled') && mig_table_ready('elite_profiles');
}

/** True once the migration has created the table (protects pages during an upgrade). */
function mig_table_ready(string $table): bool
{
    static $memo = [];
    if (!isset($memo[$table])) {
        try {
            $memo[$table] = (bool)val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t', ['t' => $table], 0);
        } catch (Throwable $e) {
            $memo[$table] = false;
        }
    }
    return $memo[$table];
}

function elite_label(): string
{
    $l = trim((string)setting('elite_label'));
    return $l !== '' ? $l : 'Elite';
}

function elite_offer_options(): array
{
    return ['mentorship' => 'Mentorship', 'travel' => 'Travel', 'dining' => 'Fine dining', 'events' => 'Events and gala nights',
        'networking' => 'Business networking', 'longterm' => 'A long-term relationship', 'marriage' => 'Marriage'];
}

function elite_seeking_options(): array
{
    return ['longterm' => 'A long-term partner', 'marriage' => 'Marriage', 'events' => 'A date for events', 'travel' => 'A travel partner',
        'friendship' => 'Friendship', 'mentee' => 'Someone to mentor'];
}

function elite_industry_options(): array
{
    return ['business' => 'Business owner', 'finance' => 'Finance and banking', 'tech' => 'Technology', 'health' => 'Medicine and health',
        'law' => 'Law', 'property' => 'Real estate', 'energy' => 'Energy and engineering', 'creative' => 'Media and creative',
        'diaspora' => 'Diaspora professional', 'public' => 'Public service', 'other' => 'Other'];
}

function elite_lifestyle_options(): array
{
    return ['comfortable' => 'Comfortable', 'affluent' => 'Affluent', 'wealthy' => 'Wealthy'];
}

function elite_csv(string $s, array $allowed): array
{
    return array_values(array_intersect(array_filter(array_map('trim', explode(',', $s))), array_keys($allowed)));
}

function elite_view_mode(): string
{
    $m = (string)setting('elite_view_mode');
    return in_array($m, ['pay', 'free', 'accept'], true) ? $m : 'pay';
}

/** Plan holders the admin lets through for free: $key is elite_join_free_for or elite_access_free_for. */
function elite_plan_exempt(int $userId, string $key): bool
{
    $who = (string)setting($key);
    if ($who === 'none' || $who === '') {
        return false;
    }
    return tier_rank(user_tier($userId)) >= ($who === 'vip' ? 2 : 1);
}

// ---------------------------------------------------------------------------
// Elite profiles
// ---------------------------------------------------------------------------

function elite_profile(int $userId): ?array
{
    if (!mig_table_ready('elite_profiles')) {
        return null;
    }
    $p = row('SELECT * FROM elite_profiles WHERE user_id = :u', ['u' => $userId]);
    if (!$p) {
        return null;
    }
    $p['criteria_arr'] = elite_criteria_normalise(json_decode((string)$p['criteria'], true) ?: []);
    return $p;
}

function elite_join_fee(): float
{
    return max(0.0, (float)setting('elite_join_fee'));
}

function elite_join_paid(array $p): bool
{
    if (elite_join_fee() <= 0 || elite_plan_exempt((int)$p['user_id'], 'elite_join_free_for')) {
        return true;
    }
    return !empty($p['paid_until']) && strtotime((string)$p['paid_until']) > time();
}

/** Listed and reachable: approved by an admin, membership paid (when there is a fee), account active. */
function elite_profile_live(array $p): bool
{
    return $p['status'] === 'approved' && elite_join_paid($p);
}

/** Effective photo privacy, including an admin override. -1 follows the member setting. */
function elite_photos_private(array $profile): bool
{
    if (!(bool)setting('elite_photo_privacy_enabled', true)) return false;
    $override = isset($profile['admin_photo_privacy']) ? (int)$profile['admin_photo_privacy'] : -1;
    if ($override === 0) return false;
    if ($override === 1) return true;
    return (int)($profile['photos_after_accept'] ?? 0) === 1;
}

function elite_is_member(int $userId): bool
{
    $p = elite_profile($userId);
    return $p !== null && elite_profile_live($p);
}

/** Keep the "hidden from ordinary dating" flag on user_profiles in step with the Elite profile. */
function elite_sync_dating_flag(int $userId): void
{
    $p = elite_profile($userId);
    // "Photos only for people I accept" would mean nothing if the same photos were sharp in Discover, so it keeps them out too.
    $hide = $p && elite_enabled() && elite_profile_live($p) && ((int)$p['hide_in_dating'] === 1 || elite_photos_private($p)) ? 1 : 0;
    q('UPDATE user_profiles SET elite_only = :h WHERE user_id = :u', ['h' => $hide, 'u' => $userId]);
}

/** Daily cron (and after the admin changes the setup): memberships that ran out, plans that lapsed, Elite switched off. */
function elite_resync_all(): int
{
    if (!mig_table_ready('elite_profiles')) {
        return 0;
    }
    $n = 0;
    foreach (col('SELECT user_id FROM elite_profiles UNION SELECT user_id FROM user_profiles WHERE elite_only = 1') as $uid) {
        elite_sync_dating_flag((int)$uid);
        $n++;
    }
    return $n;
}

/** Built-in phrases that turn a relationship into a transaction. Elite is for genuine relationships. */
function elite_text_violation(string $text): ?string
{
    if (trim($text) === '') {
        return null;
    }
    if (function_exists('hosts_text_violation') && ($w = hosts_text_violation($text)) !== null) {
        return $w;
    }
    $t = ' ' . mb_strtolower(preg_replace('/\s+/', ' ', $text)) . ' ';
    foreach (['ppm', 'pay per meet', 'allowance', 'per night', 'per hour', 'per session', 'pay for sex', 'sponsor me', 'monthly support', 'send fare', 'send money'] as $bad) {
        if (preg_match('/(^|[^a-z])' . preg_quote($bad, '/') . '([^a-z]|$)/u', $t)) {
            return $bad;
        }
    }
    return null;
}

function elite_contact_violation(string $text): ?string
{
    return function_exists('hosts_contact_violation') ? hosts_contact_violation($text) : null;
}

/** Create or update the member's Elite profile. Returns ['ok' => bool, 'error' => string, 'errors' => [], 'status' => string]. */
function elite_save_profile(int $userId, array $in): array
{
    $errors = [];
    $u = row("SELECT u.id, u.role, u.status, p.is_verified, p.display_name FROM users u JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :u", ['u' => $userId]);
    if (!$u || $u['role'] !== 'user' || $u['status'] !== 'active') {
        return ['ok' => false, 'error' => 'Only active member accounts can join ' . elite_label() . '.', 'errors' => [], 'status' => ''];
    }
    if (setting('elite_require_verified') && !(int)$u['is_verified']) {
        return ['ok' => false, 'error' => 'Get the verified badge first (Settings > Verification), then come back to join.', 'errors' => [], 'status' => ''];
    }
    if (setting('elite_require_photo') && !(int)val("SELECT COUNT(*) FROM user_photos WHERE user_id = :u AND moderation_status = 'approved'", ['u' => $userId], 0)) {
        return ['ok' => false, 'error' => 'Add a clear photo of yourself first. It needs to be approved before you can join.', 'errors' => [], 'status' => ''];
    }
    $headline = mb_substr(trim(preg_replace('/\s+/', ' ', (string)($in['headline'] ?? ''))), 0, 90);
    $about = mb_substr(trim(str_replace("\r", '', (string)($in['about'] ?? ''))), 0, 1500);
    if (mb_strlen($headline) < 8) {
        $errors['headline'] = 'Write a short headline, at least 8 characters.';
    }
    if (mb_strlen($about) < 60) {
        $errors['about'] = 'Tell people a little more about you: at least 60 characters.';
    }
    foreach (['headline' => $headline, 'about' => $about] as $k => $txt) {
        if (!isset($errors[$k]) && ($w = elite_text_violation($txt)) !== null) {
            $errors[$k] = elite_label() . ' is for genuine relationships. Remove "' . $w . '": payment for dates or intimacy is not allowed.';
        } elseif (!isset($errors[$k]) && ($c = elite_contact_violation($txt)) !== null) {
            $errors[$k] = 'Leave contact details out (found: ' . $c . '). People reach you through a request, and you choose who.';
        }
    }
    $industry = array_key_exists((string)($in['industry'] ?? ''), elite_industry_options()) ? (string)$in['industry'] : '';
    $lifestyle = array_key_exists((string)($in['lifestyle'] ?? ''), elite_lifestyle_options()) ? (string)$in['lifestyle'] : '';
    if ($industry === '') {
        $errors['industry'] = 'Choose what you do.';
    }
    $offers = array_values(array_intersect(array_map('strval', (array)($in['offers'] ?? [])), array_keys(elite_offer_options())));
    $seeking = array_values(array_intersect(array_map('strval', (array)($in['seeking'] ?? [])), array_keys(elite_seeking_options())));
    if (!$seeking) {
        $errors['seeking'] = 'Choose at least one thing you are looking for.';
    }
    $criteria = elite_criteria_from_input($in);
    if ($errors) {
        return ['ok' => false, 'error' => 'Please check the highlighted fields.', 'errors' => $errors, 'status' => ''];
    }

    $old = elite_profile($userId);
    $data = [
        'headline' => $headline, 'about' => $about, 'industry' => $industry, 'lifestyle' => $lifestyle,
        'offers' => implode(',', $offers), 'seeking' => implode(',', $seeking),
        'criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE),
        'min_score' => max(0, min(100, (int)($in['min_score'] ?? 60))),
        'filter_on' => !empty($in['filter_on']) && setting('elite_filter_enabled') ? 1 : 0,
        'hide_in_dating' => !empty($in['hide_in_dating']) ? 1 : 0,
        'share_phone' => !empty($in['share_phone']) && setting('elite_share_phone') ? 1 : 0,
        'photos_after_accept' => !empty($in['photos_after_accept']) && (bool)setting('elite_photo_privacy_enabled', true) ? 1 : 0,
    ];
    if (!$old) {
        $data['user_id'] = $userId;
        $data['status'] = setting('elite_approval') ? 'pending' : 'approved';
        if ($data['status'] === 'approved') {
            $data['approved_at'] = now();
        }
        insert('elite_profiles', $data);
        $status = $data['status'];
        if ($status === 'pending') {
            admin_alert('elite', elite_label() . ' profile waiting for approval', ($u['display_name'] ?: 'A member') . ' asked to join ' . elite_label() . '. Open Admin > ' . elite_label() . ' to review it.', '/admin/elite?tab=members&status=pending');
        }
    } else {
        $status = (string)$old['status'];
        if (in_array($status, ['rejected', 'withdrawn'], true)) {
            $status = setting('elite_approval') ? 'pending' : 'approved';
            $data['status'] = $status;
            if ($status === 'approved') {
                $data['approved_at'] = now();
            }
        }
        update('elite_profiles', $data, ['user_id' => $userId]);
        if ($status === 'approved' && ($old['headline'] !== $headline || (string)$old['about'] !== $about)) {
            admin_alert('elite', elite_label() . ' profile changed', ($u['display_name'] ?: 'A member') . ' changed their ' . elite_label() . ' profile text.', '/admin/elite?tab=members&q=' . $userId);
        } elseif ($status === 'pending' && $old['status'] !== 'pending') {
            admin_alert('elite', elite_label() . ' profile waiting for approval', ($u['display_name'] ?: 'A member') . ' asked to join ' . elite_label() . ' again.', '/admin/elite?tab=members&status=pending');
        }
    }
    elite_sync_dating_flag($userId);
    elite_stats_forget();
    return ['ok' => true, 'error' => '', 'errors' => [], 'status' => $status];
}

/** The member leaves Elite. Their requests stay for the record; they disappear from search at once. */
function elite_leave(int $userId): void
{
    q("UPDATE elite_profiles SET status = 'withdrawn' WHERE user_id = :u", ['u' => $userId]);
    q("UPDATE elite_requests SET status = 'expired' WHERE to_id = :u AND status = 'pending'", ['u' => $userId]);
    elite_sync_dating_flag($userId);
    elite_stats_forget();
}

// ---------------------------------------------------------------------------
// Criteria and the match score
// ---------------------------------------------------------------------------

/** Each criterion has an importance: off, nice (counts towards the score) or must (has to match). */
function elite_criteria_normalise(array $c): array
{
    $w = fn($v) => in_array($v, ['off', 'nice', 'must'], true) ? $v : 'off';
    $g = (string)($c['gender']['v'] ?? 'any');
    $min = max(18, min(99, (int)($c['age']['min'] ?? 18)));
    $max = max($min, min(99, (int)($c['age']['max'] ?? 99)));
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($c['place']['ids'] ?? [])))));
    return [
        'gender' => ['v' => in_array($g, ['any', 'male', 'female'], true) ? $g : 'any', 'w' => $w($c['gender']['w'] ?? 'off')],
        'age' => ['min' => $min, 'max' => $max, 'w' => $w($c['age']['w'] ?? 'off')],
        'place' => ['ids' => array_slice($ids, 0, 20), 'w' => $w($c['place']['w'] ?? 'off')],
        'verified' => ['w' => $w($c['verified']['w'] ?? 'off')],
        'photo' => ['w' => $w($c['photo']['w'] ?? 'nice')],
    ];
}

function elite_criteria_from_input(array $in): array
{
    return elite_criteria_normalise([
        'gender' => ['v' => $in['c_gender'] ?? 'any', 'w' => $in['w_gender'] ?? 'off'],
        'age' => ['min' => $in['c_age_min'] ?? 18, 'max' => $in['c_age_max'] ?? 99, 'w' => $in['w_age'] ?? 'off'],
        'place' => ['ids' => (array)($in['c_places'] ?? []), 'w' => $in['w_place'] ?? 'off'],
        'verified' => ['w' => $in['w_verified'] ?? 'off'],
        'photo' => ['w' => $in['w_photo'] ?? 'off'],
    ]);
}

/** Facts about a member that the criteria are checked against. */
function elite_seeker_facts(int $userId): array
{
    $r = row("SELECT p.gender, p.date_of_birth, p.is_verified, l.county_id, l.location_id,
                     (SELECT COUNT(*) FROM user_photos ph WHERE ph.user_id = p.user_id AND ph.moderation_status = 'approved') AS photos
              FROM user_profiles p LEFT JOIN user_locations l ON l.user_id = p.user_id WHERE p.user_id = :u", ['u' => $userId]) ?: [];
    return [
        'gender' => (string)($r['gender'] ?? ''), 'age' => age_from_dob($r['date_of_birth'] ?? null),
        'county_id' => (int)($r['county_id'] ?? 0), 'location_id' => (int)($r['location_id'] ?? 0),
        'verified' => !empty($r['is_verified']), 'photo' => (int)($r['photos'] ?? 0) > 0,
    ];
}

/**
 * Score a member against an Elite member's criteria.
 * Returns ['score' => 0-100, 'passed' => bool, 'items' => [[label, met, importance], ...]].
 * Must-haves weigh 3, nice-to-haves 1. A request passes when every must-have is met and the score reaches the minimum
 * (only when the Elite member switched the filter on).
 */
function elite_score(array $owner, int $seekerId): array
{
    $c = $owner['criteria_arr'] ?? elite_criteria_normalise(json_decode((string)($owner['criteria'] ?? ''), true) ?: []);
    $f = elite_seeker_facts($seekerId);
    $items = [];
    if ($c['gender']['w'] !== 'off' && $c['gender']['v'] !== 'any') {
        $items[] = [$c['gender']['v'] === 'male' ? 'A man' : 'A woman', $f['gender'] === $c['gender']['v'], $c['gender']['w']];
    }
    if ($c['age']['w'] !== 'off') {
        $items[] = ['Aged ' . $c['age']['min'] . ' to ' . $c['age']['max'], $f['age'] !== null && $f['age'] >= $c['age']['min'] && $f['age'] <= $c['age']['max'], $c['age']['w']];
    }
    if ($c['place']['w'] !== 'off' && $c['place']['ids']) {
        $names = array_column(rows('SELECT id, name FROM locations WHERE id IN (' . implode(',', array_map('intval', $c['place']['ids'])) . ')'), 'name');
        $label = 'Lives in ' . (count($names) > 2 ? implode(', ', array_slice($names, 0, 2)) . ' or nearby' : implode(' or ', $names));
        $items[] = [$label, in_array($f['county_id'], $c['place']['ids'], true) || in_array($f['location_id'], $c['place']['ids'], true), $c['place']['w']];
    }
    if ($c['verified']['w'] !== 'off') {
        $items[] = ['Has the verified badge', $f['verified'], $c['verified']['w']];
    }
    if ($c['photo']['w'] !== 'off') {
        $items[] = ['Has a profile photo', $f['photo'], $c['photo']['w']];
    }
    $total = 0;
    $got = 0;
    $mustOk = true;
    foreach ($items as [$label, $met, $imp]) {
        $wt = $imp === 'must' ? 3 : 1;
        $total += $wt;
        $got += $met ? $wt : 0;
        if ($imp === 'must' && !$met) {
            $mustOk = false;
        }
    }
    $score = $total > 0 ? (int)round($got * 100 / $total) : 100;
    $filter = (int)($owner['filter_on'] ?? 0) === 1 && setting('elite_filter_enabled');
    $passed = !$filter || ($mustOk && $score >= (int)($owner['min_score'] ?? 0));
    return ['score' => $score, 'passed' => $passed, 'items' => $items, 'must_ok' => $mustOk];
}

// ---------------------------------------------------------------------------
// Seeing full profiles: unlocks, packs and passes
// ---------------------------------------------------------------------------

function elite_access_row(int $userId): array
{
    $r = mig_table_ready('elite_access') ? row('SELECT * FROM elite_access WHERE user_id = :u', ['u' => $userId]) : null;
    return $r ?: ['user_id' => $userId, 'credits' => 0, 'credits_until' => null, 'pass_until' => null];
}

function elite_pass_until(int $userId): ?string
{
    $r = elite_access_row($userId);
    return !empty($r['pass_until']) && strtotime((string)$r['pass_until']) > time() ? (string)$r['pass_until'] : null;
}

function elite_credits(int $userId): int
{
    $r = elite_access_row($userId);
    if ((int)$r['credits'] <= 0 || (!empty($r['credits_until']) && strtotime((string)$r['credits_until']) <= time())) {
        return 0;
    }
    return (int)$r['credits'];
}

/** An unlock still in force (passes are checked separately, so their rows do not outlive the pass). */
function elite_has_unlock(int $viewerId, int $ownerId): bool
{
    if (!mig_table_ready('elite_unlocks')) {
        return false;
    }
    return (bool)val("SELECT COUNT(*) FROM elite_unlocks WHERE viewer_id = :v AND owner_id = :o AND source <> 'pass' AND (expires_at IS NULL OR expires_at > NOW())", ['v' => $viewerId, 'o' => $ownerId], 0);
}

/** Has either side accepted a request between these two members? */
function elite_accepted(int $a, int $b): bool
{
    if (!mig_table_ready('elite_requests') || $a <= 0 || $b <= 0) {
        return false;
    }
    return (bool)val("SELECT COUNT(*) FROM elite_requests WHERE status = 'accepted' AND ((from_id = :a AND to_id = :b) OR (from_id = :b2 AND to_id = :a2))", ['a' => $a, 'b' => $b, 'a2' => $a, 'b2' => $b], 0);
}

/** May this viewer see the full profile (all photos sharp, the whole text) of this Elite member? Contact is separate. */
function elite_can_view_full(int $viewerId, int $ownerId, ?array $owner = null): bool
{
    if ($viewerId <= 0) {
        return false;
    }
    if ($viewerId === $ownerId || viewer_is_staff(['users', 'elite'])) {
        return true;
    }
    $owner = $owner ?? elite_profile($ownerId);
    if ($owner && elite_photos_private($owner)) {
        return elite_accepted($viewerId, $ownerId);
    }
    if (elite_accepted($viewerId, $ownerId)) {
        return true;
    }
    switch (elite_view_mode()) {
        case 'free':
            return true;
        case 'accept':
            return false;
    }
    return elite_plan_exempt($viewerId, 'elite_access_free_for') || elite_pass_until($viewerId) !== null || elite_has_unlock($viewerId, $ownerId);
}

/** Photo gate hook (privacy.php): a photo shown in the Elite context stays blurred until the viewer may see the full profile. */
function elite_photo_locked(int $viewerId, int $ownerId): bool
{
    return !elite_can_view_full($viewerId, $ownerId);
}

/** Record an unlock. Passes write a row only for the statistics. */
function elite_grant(int $viewerId, int $ownerId, string $source, ?int $paymentId = null): void
{
    $days = (int)setting('elite_unlock_days');
    $exp = $days > 0 && !in_array($source, ['accepted', 'admin'], true) ? date('Y-m-d H:i:s', time() + $days * 86400) : null;
    q('INSERT INTO elite_unlocks (viewer_id, owner_id, source, payment_id, expires_at) VALUES (:v, :o, :s, :p, :e)
       ON DUPLICATE KEY UPDATE source = IF(source = \'pass\' OR VALUES(source) <> \'pass\', VALUES(source), source), payment_id = COALESCE(VALUES(payment_id), payment_id), expires_at = VALUES(expires_at), created_at = NOW()',
        ['v' => $viewerId, 'o' => $ownerId, 's' => $source, 'p' => $paymentId, 'e' => $exp]);
}

function elite_add_credits(int $userId, int $credits, int $days): void
{
    $until = date('Y-m-d H:i:s', time() + max(1, $days) * 86400);
    // one statement, so a credit spent at the same moment is never written over; expired credits are dropped first
    q('INSERT INTO elite_access (user_id, credits, credits_until) VALUES (:u, :c, :t)
       ON DUPLICATE KEY UPDATE credits = IF(credits_until IS NOT NULL AND credits_until <= NOW(), 0, credits) + VALUES(credits), credits_until = VALUES(credits_until)',
        ['u' => $userId, 'c' => $credits, 't' => $until]);
}

function elite_extend_pass(int $userId, int $days): string
{
    $cur = elite_pass_until($userId);
    $from = $cur ? strtotime($cur) : time();
    $until = date('Y-m-d H:i:s', $from + max(1, $days) * 86400);
    q('INSERT INTO elite_access (user_id, pass_until) VALUES (:u, :p) ON DUPLICATE KEY UPDATE pass_until = :p2', ['u' => $userId, 'p' => $until, 'p2' => $until]);
    return $until;
}

/**
 * Try to open a profile with what the member already has (pass, then a pack credit).
 * Returns ['ok' => bool, 'used' => 'pass'|'credit'|'already'|'free'|'', 'error' => string].
 */
function elite_unlock_with_balance(int $viewerId, int $ownerId): array
{
    $owner = elite_profile($ownerId);
    if (!$owner || !elite_profile_live($owner) || $viewerId === $ownerId || users_blocked($viewerId, $ownerId)) {
        return ['ok' => false, 'used' => '', 'error' => 'That profile is not available.'];
    }
    if (elite_photos_private($owner) && !elite_accepted($viewerId, $ownerId)) {
        return ['ok' => false, 'used' => '', 'error' => 'This member shows their full profile only to people they accept. Send a request instead.'];
    }
    if (elite_can_view_full($viewerId, $ownerId, $owner)) {
        return ['ok' => true, 'used' => 'already', 'error' => ''];
    }
    if (elite_view_mode() !== 'pay') {
        return ['ok' => false, 'used' => '', 'error' => 'This profile opens when the member accepts your request.'];
    }
    if (elite_credits($viewerId) > 0) {
        $done = db_transaction(function () use ($viewerId, $ownerId) {
            $n = (int)val('SELECT credits FROM elite_access WHERE user_id = :u FOR UPDATE', ['u' => $viewerId], 0);
            if ($n <= 0) {
                return false;
            }
            q('UPDATE elite_access SET credits = credits - 1 WHERE user_id = :u', ['u' => $viewerId]);
            elite_grant($viewerId, $ownerId, 'pack');
            return true;
        });
        if ($done) {
            return ['ok' => true, 'used' => 'credit', 'error' => ''];
        }
    }
    return ['ok' => false, 'used' => '', 'error' => ''];
}

/** What a member can buy right now: ['single' => [...], 'pack' => [...], 'pass' => [...]] (only the ones switched on). */
function elite_products(): array
{
    if (elite_view_mode() !== 'pay') {
        return [];
    }
    $out = [];
    $single = (float)setting('elite_single_price');
    $pack = (float)setting('elite_pack_price');
    $size = max(2, (int)setting('elite_pack_size'));
    $pass = (float)setting('elite_pass_price');
    $passDays = max(1, (int)setting('elite_pass_days'));
    $unlockDays = (int)setting('elite_unlock_days');
    $keep = $unlockDays > 0 ? 'Open for ' . plural($unlockDays, 'day') : 'Yours to keep';
    if ($single > 0) {
        $out['single'] = ['kind' => 'single', 'title' => 'This profile', 'price' => $single, 'note' => $keep, 'sub' => 'See every photo and the whole profile.', 'icon' => 'unlock'];
    }
    if ($pack > 0) {
        $each = $pack / $size;
        $save = $single > 0 && $each < $single ? (int)round((1 - $each / $single) * 100) : 0;
        $out['pack'] = ['kind' => 'pack', 'title' => $size . ' profiles', 'price' => $pack, 'note' => 'Use them within ' . plural(max(1, (int)setting('elite_pack_days')), 'day'),
            'sub' => 'Choose the ' . $size . ' people you want to see in full.', 'icon' => 'layers', 'save' => $save, 'size' => $size];
    }
    if ($pass > 0) {
        $out['pass'] = ['kind' => 'pass', 'title' => plural($passDays, 'day') . ' pass', 'price' => $pass, 'note' => 'Every profile, for ' . plural($passDays, 'day'),
            'sub' => 'See every ' . elite_label() . ' profile in full while it runs.', 'icon' => 'key-round'];
    }
    return $out;
}

/** Start a payment. $kind: join | single | pack | pass. $ownerId: the profile a single unlock (or the first pack credit) is for. */
function checkout_elite(string $kind, int $ownerId = 0): array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'user' || !elite_enabled()) {
        return ['ok' => false, 'error' => elite_label() . ' is not available right now.'];
    }
    $uid = (int)$u['id'];
    $meta = ['kind' => $kind];
    if ($kind === 'join') {
        $p = elite_profile($uid);
        if (!$p) {
            return ['ok' => false, 'error' => 'Fill in your ' . elite_label() . ' profile first.'];
        }
        if (elite_join_fee() <= 0 || elite_plan_exempt($uid, 'elite_join_free_for')) {
            return ['ok' => false, 'error' => 'Joining is free, there is nothing to pay.'];
        }
        $fee = elite_join_fee();
        $days = (int)setting('elite_join_days');
        $meta['days'] = $days;
        $label = elite_label() . ' membership, ' . ($days > 0 ? plural($days, 'day') : 'one time');
    } else {
        $products = elite_products();
        if (!isset($products[$kind])) {
            return ['ok' => false, 'error' => 'That option is not available.'];
        }
        if (elite_plan_exempt($uid, 'elite_access_free_for')) {
            return ['ok' => false, 'error' => 'Your plan already opens every profile.'];
        }
        $fee = (float)$products[$kind]['price'];
        if ($kind === 'single') {
            $owner = elite_profile($ownerId);
            if (!$owner || !elite_profile_live($owner) || $ownerId === $uid || users_blocked($uid, $ownerId)) {
                return ['ok' => false, 'error' => 'That profile is not available.'];
            }
            if (elite_photos_private($owner)) {
                return ['ok' => false, 'error' => 'This member shows their full profile only to people they accept, so there is nothing to unlock. Send a request instead.'];
            }
            if (elite_can_view_full($uid, $ownerId, $owner)) {
                return ['ok' => false, 'error' => 'You can already see this profile.'];
            }
            $meta['owner_id'] = $ownerId;
            $label = elite_label() . ' profile unlock';
        } elseif ($kind === 'pack') {
            $meta['credits'] = (int)$products['pack']['size'];
            $meta['days'] = max(1, (int)setting('elite_pack_days'));
            // the first credit goes to the profile they came from, but only when an unlock would really open it
            $target = $ownerId > 0 && $ownerId !== $uid ? elite_profile($ownerId) : null;
            if ($target && elite_profile_live($target) && !elite_photos_private($target) && !users_blocked($uid, $ownerId) && !elite_can_view_full($uid, $ownerId, $target)) {
                $meta['owner_id'] = $ownerId;
            }
            $label = elite_label() . ' pack, ' . plural($meta['credits'], 'profile');
        } else {
            $meta['days'] = max(1, (int)setting('elite_pass_days'));
            $label = elite_label() . ' pass, ' . plural($meta['days'], 'day');
        }
    }
    $meta['return_url'] = $ownerId > 0 ? '/elite/' . $ownerId : ($kind === 'join' ? '/elite/me' : '/elite');
    $existing = payment_find_reusable($uid, 'elite', $fee, array_diff_key($meta, ['return_url' => 1]));
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    return ['ok' => true, 'error' => '', 'payment' => payment_create($uid, 'elite', payments_default_method(), $fee, $label, $meta)];
}

/** Called by payment_complete() inside its transaction. Returns a note for the payment record. */
function elite_fulfil(array $p, array $meta): string
{
    $uid = (int)$p['user_id'];
    $pid = (int)$p['id'];
    $kind = (string)($meta['kind'] ?? '');
    $label = elite_label();
    switch ($kind) {
        case 'join':
            $days = (int)($meta['days'] ?? 0);
            $cur = (string)val('SELECT paid_until FROM elite_profiles WHERE user_id = :u', ['u' => $uid], '');
            $from = $cur !== '' && strtotime($cur) > time() ? strtotime($cur) : time();
            $until = $days > 0 ? date('Y-m-d H:i:s', $from + $days * 86400) : '2099-12-31 23:59:59';
            q('UPDATE elite_profiles SET paid_until = :t WHERE user_id = :u', ['t' => $until, 'u' => $uid]);
            elite_sync_dating_flag($uid);
            notify($uid, 'payment', 'Your ' . $label . ' membership is paid', $days > 0 ? 'It runs until ' . fmt_date($until) . '.' : 'It has no end date.', '/elite/me');
            return $label . ' membership ' . ($days > 0 ? 'until ' . fmt_date($until) : 'for good');
        case 'single':
            $owner = (int)($meta['owner_id'] ?? 0);
            if ($owner > 0) {
                elite_grant($uid, $owner, 'single', $pid);
            }
            notify($uid, 'payment', 'Profile unlocked', 'You can now see the whole profile.', '/elite/' . $owner);
            return $label . ' profile unlocked';
        case 'pack':
            $credits = max(1, (int)($meta['credits'] ?? 5));
            elite_add_credits($uid, $credits, (int)($meta['days'] ?? 30));
            $owner = (int)($meta['owner_id'] ?? 0);
            $tp = $owner > 0 ? elite_profile($owner) : null;
            if (!$tp || elite_photos_private($tp)) {
                $owner = 0; // nothing to open there: keep every credit
            }
            if ($owner > 0 && !elite_has_unlock($uid, $owner)) {
                q('UPDATE elite_access SET credits = GREATEST(credits - 1, 0) WHERE user_id = :u', ['u' => $uid]);
                elite_grant($uid, $owner, 'pack', $pid);
            }
            notify($uid, 'payment', $label . ' pack ready', 'You can open ' . plural($owner > 0 ? $credits - 1 : $credits, 'more profile') . ($owner > 0 ? ', and the one you chose is open.' : '.'), $owner > 0 ? '/elite/' . $owner : '/elite');
            return plural($credits, $label . ' credit') . ' added';
        case 'pass':
            $until = elite_extend_pass($uid, (int)($meta['days'] ?? 30));
            notify($uid, 'payment', 'Your ' . $label . ' pass is on', 'Every profile is open until ' . fmt_date($until) . '.', '/elite');
            return $label . ' pass until ' . fmt_date($until);
    }
    return $label;
}

function elite_payment_summary(array $p, array $meta): array
{
    $kind = (string)($meta['kind'] ?? '');
    $label = elite_label();
    switch ($kind) {
        case 'join':
            $days = (int)($meta['days'] ?? 0);
            return [$label . ' membership', 'gem', [['Membership', $days > 0 ? plural($days, 'day') : 'One payment, no end date'], ['Effect', 'Your profile shows in ' . $label . ' and people can send you requests.']]];
        case 'single':
            $name = (string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => (int)($meta['owner_id'] ?? 0)], 'A member');
            return ['Profile unlock', 'unlock', [['Profile', first_name_of($name)], ['Effect', 'Every photo and the whole profile.']]];
        case 'pack':
            return [$label . ' pack', 'layers', [['Profiles', plural((int)($meta['credits'] ?? 5), 'profile')], ['Use within', plural((int)($meta['days'] ?? 30), 'day')]]];
        case 'pass':
            return [$label . ' pass', 'key-round', [['Lasts', plural((int)($meta['days'] ?? 30), 'day')], ['Effect', 'Every ' . $label . ' profile in full.']]];
    }
    return [$label, 'gem', [['For', (string)($p['description'] ?: $label)]]];
}

function first_name_of(string $name): string
{
    $name = trim($name);
    return $name === '' ? 'Member' : (preg_split('/\s+/', $name)[0] ?? $name);
}

// ---------------------------------------------------------------------------
// Requests
// ---------------------------------------------------------------------------

function elite_request_between(int $from, int $to): ?array
{
    return mig_table_ready('elite_requests') ? row('SELECT * FROM elite_requests WHERE from_id = :f AND to_id = :t', ['f' => $from, 't' => $to]) : null;
}

/** Send "I'd like to connect". Returns ['ok' => bool, 'error' => string, 'status' => 'sent']. */
function elite_request_send(int $from, int $to, string $message): array
{
    $label = elite_label();
    $me = row("SELECT u.id, u.role, u.status FROM users u WHERE u.id = :u", ['u' => $from]);
    if (!$me || $me['role'] !== 'user' || $me['status'] !== 'active') {
        return ['ok' => false, 'error' => 'Only member accounts can send requests.'];
    }
    if ($from === $to) {
        return ['ok' => false, 'error' => 'That is your own profile.'];
    }
    $owner = elite_profile($to);
    if (!$owner || !elite_profile_live($owner) || (string)val('SELECT status FROM users WHERE id = :u', ['u' => $to], '') !== 'active') {
        return ['ok' => false, 'error' => 'That profile is not available.'];
    }
    if (users_blocked($from, $to)) {
        return ['ok' => false, 'error' => 'That profile is not available.'];
    }
    $message = trim(preg_replace("/[ \t]+/", ' ', str_replace("\r", '', $message)));
    if (mb_strlen($message) < 20) {
        return ['ok' => false, 'error' => 'Write a short introduction, at least 20 characters. It is the first thing they read.'];
    }
    $message = mb_substr($message, 0, 500);
    if (($w = elite_text_violation($message)) !== null) {
        return ['ok' => false, 'error' => $label . ' is for genuine relationships. Remove "' . $w . '": payment for dates or intimacy is not allowed.'];
    }
    if (($c = elite_contact_violation($message)) !== null) {
        return ['ok' => false, 'error' => 'Leave contact details out of your request (found: ' . $c . '). If they accept, a chat opens for you both.'];
    }
    if (setting('elite_request_needs_unlock') && elite_view_mode() === 'pay' && !elite_photos_private($owner) && !elite_can_view_full($from, $to, $owner)) {
        return ['ok' => false, 'error' => 'Unlock this profile first, then send your request.', 'need' => 'unlock'];
    }
    $old = elite_request_between($from, $to);
    if ($old && in_array($old['status'], ['pending', 'accepted'], true)) {
        return ['ok' => false, 'error' => $old['status'] === 'accepted' ? 'You are already connected. Open your messages.' : 'Your request is waiting for an answer.'];
    }
    if ($old && $old['status'] === 'declined') {
        return ['ok' => false, 'error' => 'This member did not accept your earlier request.'];
    }
    if ($old && $old['status'] === 'withdrawn' && !empty($old['responded_at']) && strtotime((string)$old['responded_at']) > time() - 86400) {
        return ['ok' => false, 'error' => 'You withdrew a request to this member today. You can send a new one tomorrow.'];
    }
    $today = (int)val("SELECT COUNT(*) FROM elite_requests WHERE from_id = :f AND created_at >= CURDATE()", ['f' => $from], 0);
    if ($today >= max(1, (int)setting('elite_request_daily'))) {
        return ['ok' => false, 'error' => 'You have sent today\'s ' . plural((int)setting('elite_request_daily'), 'request') . '. Try again tomorrow.'];
    }
    $sc = elite_score($owner, $from);
    $detail = json_encode(array_map(fn($i) => [$i[0], $i[1] ? 1 : 0, $i[2]], $sc['items']), JSON_UNESCAPED_UNICODE);
    $data = ['message' => $message, 'status' => 'pending', 'score' => $sc['score'], 'passed' => $sc['passed'] ? 1 : 0, 'detail' => mb_substr((string)$detail, 0, 600),
        'seen_at' => null, 'responded_at' => null, 'created_at' => now()];
    if ($old) {
        update('elite_requests', $data, ['id' => (int)$old['id']]);
    } else {
        insert('elite_requests', $data + ['from_id' => $from, 'to_id' => $to]);
    }
    if ($sc['passed']) {
        $name = first_name_of((string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $from], 'Someone'));
        notify($to, 'elite', 'New ' . $label . ' request', $name . ' would like to connect (' . $sc['score'] . '% match).', '/elite/requests', $from);
    }
    return ['ok' => true, 'error' => '', 'status' => 'sent'];
}

/** The Elite member accepts or declines. Accepting opens the full profile both ways and starts a chat with the intro as the first message. */
function elite_request_respond(int $ownerId, int $requestId, string $action): array
{
    $r = row('SELECT * FROM elite_requests WHERE id = :id AND to_id = :o', ['id' => $requestId, 'o' => $ownerId]);
    if (!$r) {
        return ['ok' => false, 'error' => 'That request is no longer here.'];
    }
    if ($r['status'] !== 'pending') {
        return ['ok' => false, 'error' => 'You already answered this request.'];
    }
    $mineP = elite_profile($ownerId);
    if ($action === 'accept' && (!$mineP || !elite_profile_live($mineP))) {
        return ['ok' => false, 'error' => 'Your ' . elite_label() . ' profile is not live, so you cannot accept requests right now.'];
    }
    $seeker = (int)$r['from_id'];
    if ($action === 'decline') {
        update('elite_requests', ['status' => 'declined', 'responded_at' => now(), 'seen_at' => $r['seen_at'] ?: now()], ['id' => $requestId]);
        return ['ok' => true, 'error' => '', 'message' => 'Request declined. They will not be told why.'];
    }
    if ($action !== 'accept') {
        return ['ok' => false, 'error' => 'Unknown action.'];
    }
    if (users_blocked($ownerId, $seeker)) {
        return ['ok' => false, 'error' => 'You blocked this member.'];
    }
    $convId = db_transaction(function () use ($r, $requestId, $ownerId, $seeker) {
        update('elite_requests', ['status' => 'accepted', 'responded_at' => now(), 'seen_at' => $r['seen_at'] ?: now()], ['id' => $requestId]);
        elite_grant($seeker, $ownerId, 'accepted');
        $convId = conversation_get_or_create($ownerId, $seeker, $seeker);
        if ($convId && !(int)val('SELECT COUNT(*) FROM messages WHERE conversation_id = :c', ['c' => $convId], 0)) {
            $mid = insert('messages', ['conversation_id' => $convId, 'sender_id' => $seeker, 'type' => 'text', 'content' => (string)$r['message'], 'created_at' => (string)$r['created_at']]);
            [$a] = pair_ids($ownerId, $seeker);
            $seekerSide = $a === $seeker ? 1 : 2;
            q("UPDATE conversations SET last_message_id = :m, last_message_at = NOW(), user{$seekerSide}_unread = 0 WHERE id = :c", ['m' => $mid, 'c' => $convId]);
        }
        return $convId;
    });
    $name = first_name_of((string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $ownerId], 'They'));
    notify($seeker, 'elite', $name . ' accepted your request', 'You are connected. Say hello in your messages.', $convId ? '/messages/' . $convId : '/messages', $ownerId);
    return ['ok' => true, 'error' => '', 'message' => 'Accepted. A chat is open for you both.', 'conversation' => (int)$convId];
}

function elite_request_withdraw(int $from, int $requestId): array
{
    $n = q("UPDATE elite_requests SET status = 'withdrawn', responded_at = NOW() WHERE id = :id AND from_id = :f AND status = 'pending'", ['id' => $requestId, 'f' => $from])->rowCount();
    return $n ? ['ok' => true, 'error' => '', 'message' => 'Request withdrawn.'] : ['ok' => false, 'error' => 'That request cannot be withdrawn.'];
}

/** Daily cron: unanswered requests expire. */
function elite_requests_expire(): int
{
    if (!mig_table_ready('elite_requests')) {
        return 0;
    }
    $days = max(1, (int)setting('elite_request_expire_days'));
    return q("UPDATE elite_requests SET status = 'expired' WHERE status = 'pending' AND created_at < (NOW() - INTERVAL $days DAY)")->rowCount();
}

/** Requests waiting in the Elite member's inbox (passed the filter, not answered). */
function elite_pending_count(int $ownerId): int
{
    if (!mig_table_ready('elite_requests')) {
        return 0;
    }
    return (int)val("SELECT COUNT(*) FROM elite_requests WHERE to_id = :o AND status = 'pending' AND passed = 1", ['o' => $ownerId], 0);
}

/**
 * Requests for the Elite member's inbox. $tab: new | filtered | accepted | declined. Rows carry the sender's card fields.
 */
function elite_inbox(int $ownerId, string $tab, int $limit = 60): array
{
    $where = ['new' => "r.status = 'pending' AND r.passed = 1", 'filtered' => "r.status = 'pending' AND r.passed = 0",
        'accepted' => "r.status = 'accepted'", 'declined' => "r.status IN ('declined', 'expired')"][$tab] ?? "r.status = 'pending' AND r.passed = 1";
    return rows("SELECT r.*, p.display_name, p.date_of_birth, p.is_verified, l.county_name, l.town_name, u.last_active_at
                 FROM elite_requests r JOIN users u ON u.id = r.from_id AND u.status = 'active' JOIN user_profiles p ON p.user_id = r.from_id
                 LEFT JOIN user_locations l ON l.user_id = r.from_id
                 WHERE r.to_id = :o AND $where ORDER BY r.score DESC, r.created_at DESC LIMIT " . (int)$limit, ['o' => $ownerId]);
}

function elite_sent(int $from, int $limit = 60): array
{
    return rows("SELECT r.*, p.display_name, e.headline FROM elite_requests r JOIN user_profiles p ON p.user_id = r.to_id
                 LEFT JOIN elite_profiles e ON e.user_id = r.to_id WHERE r.from_id = :f ORDER BY r.created_at DESC LIMIT " . (int)$limit, ['f' => $from]);
}

function elite_inbox_counts(int $ownerId): array
{
    $out = ['new' => 0, 'filtered' => 0, 'accepted' => 0, 'declined' => 0];
    foreach (rows("SELECT CASE WHEN status = 'pending' AND passed = 1 THEN 'new' WHEN status = 'pending' THEN 'filtered' WHEN status = 'accepted' THEN 'accepted' ELSE 'declined' END k, COUNT(*) n
                   FROM elite_requests WHERE to_id = :o AND status <> 'withdrawn' GROUP BY k", ['o' => $ownerId]) as $r) {
        $out[$r['k']] = (int)$r['n'];
    }
    return $out;
}

/** Messaging hook (privacy.php): an accepted Elite request lets the two members message each other. */
function elite_message_ok(int $me, int $targetId): bool
{
    if (!elite_enabled() || !elite_accepted($me, $targetId)) {
        return false;
    }
    // a suspended Elite member cannot use Elite to keep chats open
    return !(int)val("SELECT COUNT(*) FROM elite_profiles WHERE user_id IN (:a, :b) AND status = 'suspended'", ['a' => $me, 'b' => $targetId], 0);
}

/** Phone or WhatsApp of an Elite member, for someone they accepted, when they chose to share it. */
function elite_shared_phone(int $viewerId, array $owner): array
{
    if (!setting('elite_share_phone') || (int)$owner['share_phone'] !== 1 || !elite_accepted($viewerId, (int)$owner['user_id'])) {
        return [];
    }
    $r = row('SELECT contact_phone, contact_whatsapp FROM user_profiles WHERE user_id = :u', ['u' => (int)$owner['user_id']]) ?: [];
    $phone = trim((string)($r['contact_phone'] ?? '')) ?: (string)val('SELECT phone FROM users WHERE id = :u', ['u' => (int)$owner['user_id']], '');
    $wa = trim((string)($r['contact_whatsapp'] ?? '')) ?: $phone;
    $out = [];
    if ($phone !== '') {
        $out['phone'] = $phone;
    }
    if ($wa !== '') {
        $out['whatsapp'] = $wa;
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Search and cards
// ---------------------------------------------------------------------------

/** Elite profiles for the search page. $f: gender, age_min, age_max, county, lifestyle, industry, verified. */
function elite_search(int $viewerId, array $f, int $limit, int $offset = 0): array
{
    if (!mig_table_ready('elite_profiles')) {
        return ['rows' => [], 'total' => 0];
    }
    $where = elite_live_conditions();
    $args = [];
    if ($viewerId > 0) {
        $where[] = 'e.user_id <> :me';
        $where[] = 'NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.blocker_id = :me1 AND b.blocked_id = e.user_id) OR (b.blocker_id = e.user_id AND b.blocked_id = :me2))';
        $args += ['me' => $viewerId, 'me1' => $viewerId, 'me2' => $viewerId];
    }
    if (in_array($f['gender'] ?? '', ['male', 'female'], true)) {
        $where[] = 'p.gender = :g';
        $args['g'] = $f['gender'];
    }
    $amin = (int)($f['age_min'] ?? 0);
    $amax = (int)($f['age_max'] ?? 0);
    if ($amin > 18) {
        $where[] = 'p.date_of_birth <= DATE_SUB(CURDATE(), INTERVAL :amin YEAR)';
        $args['amin'] = $amin;
    }
    if ($amax > 0 && $amax < 99) {
        $where[] = 'p.date_of_birth > DATE_SUB(CURDATE(), INTERVAL :amax YEAR)';
        $args['amax'] = $amax + 1;
    }
    if ((int)($f['county'] ?? 0) > 0) {
        $where[] = 'l.county_id = :cty';
        $args['cty'] = (int)$f['county'];
    }
    if (array_key_exists((string)($f['lifestyle'] ?? ''), elite_lifestyle_options())) {
        $where[] = 'e.lifestyle = :ls';
        $args['ls'] = $f['lifestyle'];
    }
    if (array_key_exists((string)($f['industry'] ?? ''), elite_industry_options())) {
        $where[] = 'e.industry = :ind';
        $args['ind'] = $f['industry'];
    }
    if (!empty($f['verified'])) {
        $where[] = 'e.is_verified = 1';
    }
    if (!empty($f['active'])) {
        $where[] = 'u.last_active_at > NOW() - INTERVAL 7 DAY';
    }
    $term = trim(preg_replace('/\s+/', ' ', (string)($f['q'] ?? '')) ?? '');
    if ($term !== '') {
        // only what a locked card already shows: first name, headline, place (never the "about" text)
        $like = '%' . addcslashes(mb_substr($term, 0, 60), '%_\\') . '%';
        $where[] = "(SUBSTRING_INDEX(p.display_name, ' ', 1) LIKE :qa OR e.headline LIKE :qb OR l.town_name LIKE :qc OR l.county_name LIKE :qd)";
        $args += ['qa' => $like, 'qb' => $like, 'qc' => $like, 'qd' => $like];
    }
    if ((int)($f['saved_by'] ?? 0) > 0) {
        $where[] = 'EXISTS (SELECT 1 FROM elite_saves sv WHERE sv.user_id = :sv AND sv.owner_id = e.user_id)';
        $args['sv'] = (int)$f['saved_by'];
    }
    $order = [
        'new' => 'e.approved_at DESC, e.user_id DESC',
        'active' => 'u.last_active_at DESC, e.user_id DESC',
    ][(string)($f['sort'] ?? '')] ?? 'e.is_featured DESC, e.is_verified DESC, (u.last_active_at > NOW() - INTERVAL 3 DAY) DESC, e.approved_at DESC';
    $sqlWhere = implode(' AND ', $where);
    $from = "FROM elite_profiles e JOIN users u ON u.id = e.user_id JOIN user_profiles p ON p.user_id = e.user_id LEFT JOIN user_locations l ON l.user_id = e.user_id";
    $total = (int)val("SELECT COUNT(*) $from WHERE $sqlWhere", $args, 0);
    $rows = rows("SELECT e.*, p.display_name, p.date_of_birth, p.gender, p.is_verified AS member_verified, l.county_name, l.town_name, u.last_active_at,
                         (SELECT ph.id FROM user_photos ph WHERE ph.user_id = e.user_id AND ph.moderation_status = 'approved' ORDER BY ph.is_primary DESC, ph.sort_order, ph.id LIMIT 1) AS photo_id
                  $from WHERE $sqlWhere ORDER BY $order
                  LIMIT " . (int)$limit . ' OFFSET ' . (int)$offset, $args);
    return ['rows' => $rows, 'total' => $total];
}

function elite_photo_row(?int $photoId): ?array
{
    if (!$photoId) {
        return null;
    }
    return row('SELECT id, user_id, large_path, medium_path, thumb_path, blur_path, blur_data_uri, width, height, is_primary, is_vip_only, moderation_status FROM user_photos WHERE id = :id', ['id' => $photoId]);
}

/** Everything an Elite card needs, as the viewer may see it. */
function elite_card(array $r, int $viewerId, ?array $savedIds = null): array
{
    $owner = (int)$r['user_id'];
    $full = elite_can_view_full($viewerId, $owner, $r);
    $photo = elite_photo_row(isset($r['photo_id']) ? (int)$r['photo_id'] : null);
    $hide = !$full && (string)setting('elite_preview_photo') === 'hide';
    $chars = max(0, (int)setting('elite_preview_chars'));
    $about = preg_replace('/\s+/', ' ', (string)$r['about']);
    $age = age_from_dob($r['date_of_birth'] ?? null);
    return [
        'id' => $owner, 'name' => first_name_of((string)$r['display_name']), 'age' => $age,
        'place' => trim((string)($r['town_name'] ?: $r['county_name'] ?? '')),
        'headline' => (string)$r['headline'], 'about' => $full ? $about : ($chars > 0 ? str_limit($about, $chars) : ''),
        'lifestyle' => elite_lifestyle_options()[$r['lifestyle']] ?? '', 'industry' => elite_industry_options()[$r['industry']] ?? '',
        'verified' => (int)$r['is_verified'] === 1, 'featured' => (int)$r['is_featured'] === 1, 'locked' => !$full,
        'photo' => $hide || !$photo ? '' : photo_url($photo, 'medium', 'elite'), 'blurred' => !$hide && $photo && !$full,
        'initials' => mb_strtoupper(mb_substr(first_name_of((string)$r['display_name']), 0, 1)),
        'active' => !empty($r['last_active_at']) && strtotime((string)$r['last_active_at']) > time() - 86400 * 3,
        'saved' => $savedIds !== null && in_array($owner, $savedIds, true),
    ];
}

/** A few Elite cards for the landing block. */
function elite_landing_cards(int $limit): array
{
    $viewer = current_user();
    $vid = $viewer ? (int)$viewer['id'] : 0;
    $res = elite_search($vid, [], max(1, min(12, $limit)));
    return array_map(fn($r) => elite_card($r, $vid), $res['rows']);
}

/** Where the member stands, for the banner on the Elite pages. */
function elite_viewer_status(int $uid): array
{
    $mode = elite_view_mode();
    return [
        'mode' => $mode, 'exempt' => $uid > 0 && elite_plan_exempt($uid, 'elite_access_free_for'),
        'pass_until' => $uid > 0 ? elite_pass_until($uid) : null, 'credits' => $uid > 0 ? elite_credits($uid) : 0,
        'credits_until' => $uid > 0 ? (elite_access_row($uid)['credits_until'] ?? null) : null,
        'member' => $uid > 0 ? elite_profile($uid) : null,
    ];
}

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------

function elite_admin_stats(): array
{
    if (!mig_table_ready('elite_profiles')) {
        return ['live' => 0, 'pending' => 0, 'requests_7' => 0, 'accepted_7' => 0, 'revenue_30' => 0.0, 'unlocks_30' => 0, 'passes' => 0];
    }
    return [
        'live' => (int)val("SELECT COUNT(*) FROM elite_profiles WHERE status = 'approved'", [], 0),
        'pending' => (int)val("SELECT COUNT(*) FROM elite_profiles WHERE status = 'pending'", [], 0),
        'requests_7' => (int)val('SELECT COUNT(*) FROM elite_requests WHERE created_at >= NOW() - INTERVAL 7 DAY', [], 0),
        'accepted_7' => (int)val("SELECT COUNT(*) FROM elite_requests WHERE status = 'accepted' AND responded_at >= NOW() - INTERVAL 7 DAY", [], 0),
        'filtered_7' => (int)val('SELECT COUNT(*) FROM elite_requests WHERE passed = 0 AND created_at >= NOW() - INTERVAL 7 DAY', [], 0),
        'revenue_30' => (float)val("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_type = 'elite' AND status = 'completed' AND created_at >= NOW() - INTERVAL 30 DAY", [], 0),
        'unlocks_30' => (int)val("SELECT COUNT(*) FROM elite_unlocks WHERE source IN ('single', 'pack') AND created_at >= NOW() - INTERVAL 30 DAY", [], 0),
        'passes' => (int)val('SELECT COUNT(*) FROM elite_access WHERE pass_until > NOW()', [], 0),
    ];
}

/** Admin moderation of one Elite profile. $action: approve | reject | suspend | restore | verify | unverify | feature | unfeature | remove */
function elite_admin_action(int $userId, string $action, string $note = ''): array
{
    $p = elite_profile($userId);
    if (!$p) {
        return [false, 'That ' . elite_label() . ' profile is not here.'];
    }
    $name = (string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $userId], 'Member');
    $label = elite_label();
    switch ($action) {
        case 'approve':
        case 'restore':
            update('elite_profiles', ['status' => 'approved', 'approved_at' => $p['approved_at'] ?: now(), 'admin_note' => $note ?: null], ['user_id' => $userId]);
            if ($action === 'approve') {
                $pay = !elite_join_paid(['user_id' => $userId] + $p);
                notify($userId, 'elite', 'Welcome to ' . $label, $pay ? 'You are approved. Pay the membership to appear in ' . $label . '.' : 'Your profile is live. People can now send you requests.', '/elite/me');
            }
            $msg = $action === 'approve' ? 'Approved.' : 'Restored.';
            break;
        case 'reject':
            update('elite_profiles', ['status' => 'rejected', 'admin_note' => $note ?: null], ['user_id' => $userId]);
            notify($userId, 'elite', 'Your ' . $label . ' profile needs changes', $note !== '' ? $note : 'Open your ' . $label . ' profile to see what to change.', '/elite/me');
            $msg = 'Rejected. The member was told.';
            break;
        case 'suspend':
            update('elite_profiles', ['status' => 'suspended', 'admin_note' => $note ?: null], ['user_id' => $userId]);
            q("UPDATE elite_requests SET status = 'expired' WHERE to_id = :u AND status = 'pending'", ['u' => $userId]);
            $msg = 'Suspended. The profile is hidden.';
            break;
        case 'verify':
        case 'unverify':
            update('elite_profiles', ['is_verified' => $action === 'verify' ? 1 : 0], ['user_id' => $userId]);
            if ($action === 'verify') {
                notify($userId, 'elite', 'You are a Verified ' . $label . ' member', 'The badge now shows on your profile.', '/elite/me');
            }
            $msg = $action === 'verify' ? 'Verified badge added.' : 'Verified badge removed.';
            break;
        case 'feature':
        case 'unfeature':
            update('elite_profiles', ['is_featured' => $action === 'feature' ? 1 : 0], ['user_id' => $userId]);
            $msg = $action === 'feature' ? 'Featured: shown first.' : 'No longer featured.';
            break;
        case 'photo_privacy_on':
        case 'photo_privacy_off':
        case 'photo_privacy_reset':
            if (!mig_has_column('elite_profiles', 'admin_photo_privacy')) return [false, 'Run migration 0035 before changing photo privacy.'];
            $override = $action === 'photo_privacy_on' ? 1 : ($action === 'photo_privacy_off' ? 0 : -1);
            q('UPDATE elite_profiles SET admin_photo_privacy = :v WHERE user_id = :u', ['v' => $override, 'u' => $userId]);
            $msg = $override === 1 ? 'Admin enforced photos-after-accept privacy.' : ($override === 0 ? 'Admin disabled photos-after-accept privacy for this member.' : 'Photo privacy now follows the member setting.');
            break;
        case 'remove':
            q('DELETE FROM elite_profiles WHERE user_id = :u', ['u' => $userId]);
            if (mig_table_ready('elite_saves')) {
                q('DELETE FROM elite_saves WHERE owner_id = :u', ['u' => $userId]);
            }
            q("UPDATE elite_requests SET status = 'expired' WHERE to_id = :u AND status = 'pending'", ['u' => $userId]);
            $msg = 'Removed from ' . $label . '.';
            break;
        default:
            return [false, 'Unknown action.'];
    }
    elite_sync_dating_flag($userId);
    elite_stats_forget();
    admin_log('elite.' . $action, 'user', $userId, $name . ($note !== '' ? ' - ' . $note : ''));
    return [true, $msg];
}

function elite_admin_grant(int $userId, string $kind, int $amount): array
{
    if ($kind === 'credits') {
        elite_add_credits($userId, max(1, min(500, $amount)), max(1, (int)setting('elite_pack_days')));
        admin_log('elite.grant_credits', 'user', $userId, (string)$amount);
        return [true, plural($amount, 'credit') . ' added.'];
    }
    if ($kind === 'pass') {
        $until = elite_extend_pass($userId, max(1, min(3650, $amount)));
        admin_log('elite.grant_pass', 'user', $userId, (string)$amount);
        return [true, 'Pass runs until ' . fmt_date($until) . '.'];
    }
    return [false, 'Unknown option.'];
}

/** One plain sentence describing the current setup (admin overview). */
function elite_setup_sentence(): string
{
    $label = elite_label();
    $join = elite_join_fee() > 0 ? 'Joining costs ' . money(elite_join_fee()) . ((int)setting('elite_join_days') > 0 ? ' every ' . plural((int)setting('elite_join_days'), 'day') : ' once') : 'Joining is free';
    $join .= setting('elite_approval') ? ' and you approve every profile.' : ', profiles go live at once.';
    $mode = elite_view_mode();
    if ($mode === 'free') {
        $view = 'Signed-in members see full profiles for free.';
    } elseif ($mode === 'accept') {
        $view = 'A full profile opens only when the ' . $label . ' member accepts a request.';
    } else {
        $bits = [];
        foreach (elite_products() as $p) {
            $bits[] = $p['title'] . ' ' . money($p['price']);
        }
        $view = $bits ? 'Members unlock full profiles: ' . implode(', ', $bits) . '.' : 'Unlocking is set to paid, but every price is 0, so nobody can unlock. Set at least one price.';
    }
    return $join . ' ' . $view . ' Contact always goes through a request the ' . $label . ' member accepts.';
}
