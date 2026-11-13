<?php
/**
 * International connections. Members opt in with a short profile (country, languages, what they are looking for, and
 * how people can reach them). The admin decides whether listing is free or paid and whether viewing and contacting listed
 * members is free or paid. Prices and lengths always come from settings on the server.
 *
 * Modes (intl_mode): free = nobody pays, join = listing is paid, access = viewing and messaging is paid, both = both are paid.
 * Members on a plan at or above intl_premium_free never pay either fee.
 */

declare(strict_types=1);

function intl_enabled(): bool
{
    return (bool)setting('intl_enabled');
}

function intl_mode(): string
{
    $m = (string)setting('intl_mode');
    return in_array($m, ['free', 'join', 'access', 'both'], true) ? $m : 'free';
}

function intl_join_required(): bool
{
    return in_array(intl_mode(), ['join', 'both'], true) && (float)setting('intl_join_fee') > 0;
}

function intl_access_required(): bool
{
    return in_array(intl_mode(), ['access', 'both'], true) && (float)setting('intl_access_fee') > 0;
}

/** True when the member's plan includes International at no charge. */
function intl_exempt(int $userId): bool
{
    $need = ['premium' => 1, 'vip' => 2][(string)setting('intl_premium_free')] ?? 99;
    return tier_rank(user_tier($userId)) >= $need;
}

function intl_profile(int $userId): ?array
{
    return row('SELECT * FROM intl_profiles WHERE user_id = :u', ['u' => $userId]);
}

/** When paid access ends: null means no access, 0 means it never ends. */
function intl_access_until(int $userId): ?string
{
    $r = row('SELECT expires_at FROM intl_access WHERE user_id = :u', ['u' => $userId]);
    if (!$r) {
        return null;
    }
    if ($r['expires_at'] === null) {
        return '0';
    }
    return strtotime((string)$r['expires_at']) > time() ? (string)$r['expires_at'] : null;
}

/** May this member browse and contact listed members? */
function intl_has_access(int $userId): bool
{
    if (!intl_access_required()) {
        return true;
    }
    return intl_exempt($userId) || intl_access_until($userId) !== null;
}

/** Is this profile row visible to others right now (approved, not paused, and paid when listing is paid)? */
function intl_profile_live(array $p): bool
{
    if ($p['status'] !== 'active') {
        return false;
    }
    if (!intl_join_required()) {
        return true;
    }
    if ($p['listed_until'] !== null && strtotime((string)$p['listed_until']) > time()) {
        return true;
    }
    return intl_exempt((int)$p['user_id']);
}

function intl_is_listed(int $userId): bool
{
    $p = intl_profile($userId);
    return $p !== null && intl_profile_live($p);
}

/** Add days to paid access (0 days = for good). Extends from the current end when still running. */
function intl_grant_access(int $userId, int $days, string $source = 'purchase'): ?string
{
    $cur = row('SELECT expires_at FROM intl_access WHERE user_id = :u', ['u' => $userId]);
    if ($days <= 0) {
        $until = null;
    } else {
        $base = $cur && $cur['expires_at'] !== null && strtotime((string)$cur['expires_at']) > time() ? strtotime((string)$cur['expires_at']) : time();
        if ($cur && $cur['expires_at'] === null) {
            return null; // already for good
        }
        $until = date('Y-m-d H:i:s', $base + $days * 86400);
    }
    q('INSERT INTO intl_access (user_id, expires_at, source) VALUES (:u, :e, :s) ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at), source = VALUES(source)', ['u' => $userId, 'e' => $until, 's' => $source]);
    return $until;
}

/** Paid listing time. Returns the new end, or null for a listing that never ends. */
function intl_extend_listing(int $userId, int $days): ?string
{
    $p = intl_profile($userId);
    if (!$p) {
        return null;
    }
    if ($days <= 0) {
        q('UPDATE intl_profiles SET listed_until = :e WHERE user_id = :u', ['e' => '9999-12-31 00:00:00', 'u' => $userId]);
        return null;
    }
    $base = $p['listed_until'] !== null && strtotime((string)$p['listed_until']) > time() ? strtotime((string)$p['listed_until']) : time();
    $until = date('Y-m-d H:i:s', min($base + $days * 86400, strtotime('9999-12-30')));
    q('UPDATE intl_profiles SET listed_until = :e WHERE user_id = :u', ['e' => $until, 'u' => $userId]);
    return $until;
}

/** Text about a price: "KSh 500 for 30 days" or "KSh 500, once". */
function intl_price_label(string $kind): string
{
    $fee = (float)setting($kind === 'join' ? 'intl_join_fee' : 'intl_access_fee');
    $days = (int)setting($kind === 'join' ? 'intl_join_days' : 'intl_access_days');
    return money($fee) . ($days > 0 ? ' for ' . plural($days, 'day') : ', one time');
}

/** What the pricing is, for the page, in plain words. */
function intl_pricing_summary(): array
{
    return ['mode' => intl_mode(), 'join' => intl_join_required(), 'access' => intl_access_required()];
}

function intl_csv(string $s): array
{
    return array_values(array_filter(array_map('trim', explode(',', $s)), fn($x) => $x !== ''));
}

/** Phone numbers kept as '+' and digits only. Returns null when it does not look like a real number. */
function intl_normalize_number(string $dial, string $national): ?string
{
    $digits = preg_replace('/\D+/', '', $national) ?? '';
    $digits = ltrim($digits, '0');
    if (str_starts_with($digits, $dial) && strlen($digits) > strlen($dial) + 6) {
        $digits = substr($digits, strlen($dial)); // they typed the country code too
    }
    if (strlen($digits) < 6 || strlen($digits) > 12) {
        return null;
    }
    return $digits;
}

/** Text that hides phone numbers and links, so people keep contact details inside the private contact field. */
function intl_text_has_contact(string $t): bool
{
    return (bool)preg_match('/\d[\d\s().-]{7,}\d|https?:|www\.|@[a-z0-9_.-]+\.[a-z]{2,}|\b[\w.+-]+@[\w-]+\.\w{2,}\b/i', $t);
}

/**
 * Save the signed-in member's international profile. $in is the raw form data.
 * Returns ['ok' => bool, 'errors' => [field => message], 'status' => string].
 */
function intl_save_profile(int $userId, array $in): array
{
    $errors = [];
    $countries = countries_all();
    $country = strtoupper(trim((string)($in['country'] ?? '')));
    if (!isset($countries[$country])) {
        $errors['country'] = 'Choose the country you live in.';
    }
    $city = trim(preg_replace('/\s+/', ' ', (string)($in['city'] ?? '')) ?? '');
    if (mb_strlen($city) > 80) {
        $errors['city'] = 'The city can be up to 80 characters.';
    }
    $langs = array_values(array_intersect(array_map('strval', (array)($in['languages'] ?? [])), array_keys(intl_languages())));
    if (!$langs) {
        $errors['languages'] = 'Pick at least one language you speak.';
    }
    $purposes = array_values(array_intersect(array_map('strval', (array)($in['purposes'] ?? [])), array_keys(intl_purposes())));
    if (!$purposes) {
        $errors['purposes'] = 'Pick what you are hoping to find.';
    }
    $want = array_values(array_intersect(array_map('strtoupper', array_map('strval', (array)($in['want_countries'] ?? []))), array_keys($countries)));
    $headline = trim(preg_replace('/\s+/', ' ', (string)($in['headline'] ?? '')) ?? '');
    $about = trim((string)($in['about'] ?? ''));
    if (mb_strlen($headline) < 8 || mb_strlen($headline) > 120) {
        $errors['headline'] = 'Write a short line about yourself, 8 to 120 characters.';
    }
    if (mb_strlen($about) > 800) {
        $errors['about'] = 'The longer text can be up to 800 characters.';
    }
    foreach (['headline' => $headline, 'about' => $about] as $f => $t) {
        if (!isset($errors[$f]) && $t !== '') {
            if (intl_text_has_contact($t)) {
                $errors[$f] = 'Leave phone numbers, emails and links out of this text. Add your number under Contact, where we keep it private.';
            } elseif (function_exists('contains_banned_word') && contains_banned_word($t) !== null) {
                $errors[$f] = 'Some words here are not allowed. Please rephrase.';
            }
        }
    }

    $allowed = array_values(array_intersect((array)setting('intl_contact_types'), array_keys(intl_contact_types_all())));
    $type = (string)($in['contact_type'] ?? '');
    $dial = '';
    $number = '';
    $share = ($in['contact_share'] ?? 'chat') === 'never' ? 'never' : 'chat';
    if ($type !== '') {
        if (!in_array($type, $allowed, true)) {
            $errors['contact_type'] = 'That kind of number is not available.';
        } else {
            $dial = (string)preg_replace('/\D+/', '', (string)($in['contact_dial'] ?? '')) ?: country_dial($country);
            $n = intl_normalize_number($dial, (string)($in['contact_number'] ?? ''));
            if ($n === null) {
                $errors['contact_number'] = 'Enter the number without the country code, for example 712345678.';
            } else {
                $number = $n;
            }
        }
    }

    $existing = intl_profile($userId);
    if (!$existing && intl_require_verified_block($userId)) {
        $errors['verified'] = 'Verify your profile photo first. Only verified members can join International connections.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'status' => $existing['status'] ?? ''];
    }

    $data = ['country' => $country, 'city' => $city !== '' ? $city : null, 'languages' => implode(',', $langs), 'purposes' => implode(',', $purposes),
        'want_countries' => implode(',', $want), 'headline' => $headline, 'about' => $about !== '' ? $about : null,
        'contact_type' => $type !== '' ? $type : null, 'contact_dial' => $type !== '' ? $dial : null, 'contact_number' => $type !== '' ? $number : null, 'contact_share' => $share];
    $needsReview = (bool)setting('intl_approval');
    if (!$existing) {
        $data['user_id'] = $userId;
        $data['status'] = $needsReview ? 'pending' : 'active';
        $data['approved_at'] = $needsReview ? null : now();
        insert('intl_profiles', $data);
        if ($needsReview) {
            admin_alert('intl', 'International profile waiting for approval', 'A member asked to be listed in International connections. Open Admin > International to approve it.', '/admin/international?status=pending');
        }
        $status = $data['status'];
    } else {
        $status = (string)$existing['status'];
        // Changing the words people read sends a live listing back for a look; contact and filters do not.
        $textChanged = trim((string)$existing['headline']) !== $headline || trim((string)$existing['about']) !== $about;
        if ($needsReview && ($status === 'rejected' || ($status === 'active' && $textChanged))) {
            $status = 'pending';
            admin_alert('intl', 'International profile changed', 'A listed member changed their profile text. Open Admin > International to review it.', '/admin/international?status=pending');
        }
        if ($status === 'rejected' && !$needsReview) {
            $status = 'active';
        }
        $data['status'] = $status;
        update('intl_profiles', $data, ['user_id' => $userId]);
    }
    return ['ok' => true, 'errors' => [], 'status' => $status];
}

/** True when only verified members may list and this member is not verified. */
function intl_require_verified_block(int $userId): bool
{
    return (bool)setting('intl_require_verified') && !(int)val('SELECT is_verified FROM user_profiles WHERE user_id = :u', ['u' => $userId], 0);
}

/** Which listed members to show. Filters: country, language, purpose, wants_me (only people open to my country), q. */
function intl_member_ids(int $viewerId, array $f = []): array
{
    $where = ["i.status = 'active'", 'i.user_id <> :me'];
    $params = ['me' => $viewerId];
    if (!empty($f['country']) && isset(countries_all()[strtoupper((string)$f['country'])])) {
        $where[] = 'i.country = :c';
        $params['c'] = strtoupper((string)$f['country']);
    }
    if (!empty($f['language']) && isset(intl_languages()[$f['language']])) {
        $where[] = 'FIND_IN_SET(:l, i.languages) > 0';
        $params['l'] = (string)$f['language'];
    }
    if (!empty($f['purpose']) && isset(intl_purposes()[$f['purpose']])) {
        $where[] = 'FIND_IN_SET(:pp, i.purposes) > 0';
        $params['pp'] = (string)$f['purpose'];
    }
    if (!empty($f['open_to_me'])) {
        $mine = intl_profile($viewerId);
        $myCountry = $mine['country'] ?? '';
        if ($myCountry !== '') {
            $where[] = "(i.want_countries = '' OR FIND_IN_SET(:mc, i.want_countries) > 0)";
            $params['mc'] = $myCountry;
        }
    }
    $rows = rows('SELECT i.* FROM intl_profiles i WHERE ' . implode(' AND ', $where) . ' ORDER BY i.updated_at DESC LIMIT 600', $params);
    $ids = [];
    $byId = [];
    foreach ($rows as $r) {
        if (intl_profile_live($r)) {
            $ids[] = (int)$r['user_id'];
            $byId[(int)$r['user_id']] = $r;
        }
    }
    return [$ids, $byId];
}

/**
 * Cards for the browse page: the usual member data plus the international profile. Returns ['rows' => [...], 'total' => n].
 */
function intl_cards(int $viewerId, array $f, int $limit, int $offset): array
{
    [$ids, $byId] = intl_member_ids($viewerId, $f);
    if (!$ids) {
        return ['rows' => [], 'total' => 0];
    }
    $q = trim((string)($f['q'] ?? ''));
    $order = (string)($f['sort'] ?? 'newest');
    $res = members_search(['only_ids' => $ids, 'order' => in_array($order, ['newest', 'active'], true) ? $order : 'newest', 'q' => $q !== '' ? $q : null, 'has_photo' => true], $limit, $offset);
    $photos = explorer_photos_for(array_map(fn($m) => (int)$m['id'], $res['rows']), 1);
    $out = [];
    foreach ($res['rows'] as $m) {
        $id = (int)$m['id'];
        $i = $byId[$id] ?? null;
        if (!$i) {
            continue;
        }
        $ph = $photos[$id][0] ?? null;
        $out[] = ['id' => $id, 'name' => $m['first_name'], 'age' => $m['age'], 'verified' => $m['verified'], 'online' => (bool)$m['online'],
            'photo' => $ph ? ['url' => photo_url($ph, 'large', 'profile'), 'blurred' => photo_is_blurred($ph, 'profile')] : ['url' => photo_placeholder_url(), 'blurred' => false],
            'country' => $i['country'], 'city' => (string)$i['city'], 'headline' => (string)$i['headline'],
            'languages' => intl_csv((string)$i['languages']), 'purposes' => intl_csv((string)$i['purposes']),
            'contact_type' => (string)$i['contact_type'], 'href' => url('/international/' . $id),
            'is_new' => strtotime((string)($i['approved_at'] ?: $i['created_at'] ?? 'now')) > time() - 7 * 86400,
            'message_href' => url('/messages/with/' . $id)];
    }
    return ['rows' => $out, 'total' => $res['total']];
}

/** Public numbers for the hero: how many members, in how many countries. Counts only live listings. */
function intl_stats(): array
{
    return cache_remember('intl_stats', 60, function () {
        $rows = rows("SELECT i.* FROM intl_profiles i JOIN users u ON u.id = i.user_id WHERE i.status = 'active' AND u.status = 'active'");
        $members = 0;
        $countries = [];
        foreach ($rows as $r) {
            if (intl_profile_live($r)) {
                $members++;
                $countries[$r['country']] = ($countries[$r['country']] ?? 0) + 1;
            }
        }
        arsort($countries);
        return ['members' => $members, 'countries' => $countries];
    });
}

/**
 * Can this member see the other's number? Only when the owner chose to share, and both people have written in the chat.
 */
function intl_contact_visible(int $viewerId, int $ownerId): bool
{
    $p = intl_profile($ownerId);
    if (!$p || !$p['contact_number'] || $p['contact_share'] !== 'chat') {
        return false;
    }
    [$a, $b] = pair_ids($viewerId, $ownerId);
    $cid = (int)val('SELECT id FROM conversations WHERE user1_id = :a AND user2_id = :b', ['a' => $a, 'b' => $b], 0);
    if (!$cid) {
        return false;
    }
    return (int)val("SELECT COUNT(DISTINCT sender_id) FROM messages WHERE conversation_id = :c AND type <> 'system' AND is_deleted = 0", ['c' => $cid], 0) >= 2;
}

function intl_contact_text(array $p): string
{
    $types = intl_contact_types_all();
    return '+' . $p['contact_dial'] . ' ' . $p['contact_number'] . ' (' . ($types[$p['contact_type']][0] ?? 'Phone') . ')';
}

/** Messaging door: a member with International access may message any live listing. Returns true when that applies. */
function intl_message_ok(int $me, int $targetId): bool
{
    if (!intl_enabled() || !(bool)setting('intl_msg_included')) {
        return false;
    }
    $p = intl_profile($targetId);
    return $p !== null && intl_profile_live($p) && intl_has_access($me);
}

/** Start a payment for a listing or for access. $kind is 'join' or 'access'. */
function checkout_intl(string $kind): array
{
    $u = current_user();
    if (!$u || !intl_enabled()) {
        return ['ok' => false, 'error' => 'International connections are not available right now.'];
    }
    $uid = (int)$u['id'];
    if ($kind === 'join') {
        if (!intl_join_required()) {
            return ['ok' => false, 'error' => 'Listing is free, there is nothing to pay.'];
        }
        $p = intl_profile($uid);
        if (!$p) {
            return ['ok' => false, 'error' => 'Fill in your international profile first.'];
        }
        if (intl_exempt($uid)) {
            return ['ok' => false, 'error' => 'Your plan already includes this.'];
        }
        $fee = (float)setting('intl_join_fee');
        $days = (int)setting('intl_join_days');
        $label = 'International listing, ' . ($days > 0 ? plural($days, 'day') : 'one time');
    } elseif ($kind === 'access') {
        if (!intl_access_required()) {
            return ['ok' => false, 'error' => 'Access is free, there is nothing to pay.'];
        }
        if (intl_exempt($uid)) {
            return ['ok' => false, 'error' => 'Your plan already includes this.'];
        }
        $fee = (float)setting('intl_access_fee');
        $days = (int)setting('intl_access_days');
        $label = 'International access, ' . ($days > 0 ? plural($days, 'day') : 'one time');
    } else {
        return ['ok' => false, 'error' => 'That option is not available.'];
    }
    $match = ['kind' => $kind, 'days' => $days];
    $existing = payment_find_reusable($uid, 'intl', $fee, $match);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $p = payment_create($uid, 'intl', payments_default_method(), $fee, $label, $match + ['return_url' => '/international']);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}

/** Decorative arcs and dots for the page hero: fixed city-like points on the globe joined by curved lines. */
function intl_hero_arcs(): array
{
    $dots = [[150, 150], [270, 120], [300, 230], [190, 290], [110, 240], [230, 200]];
    $pairs = [[0, 1], [1, 2], [2, 3], [3, 4], [4, 0], [5, 1], [5, 3], [0, 5]];
    $arcs = [];
    foreach ($pairs as [$a, $b]) {
        [$x1, $y1] = $dots[$a];
        [$x2, $y2] = $dots[$b];
        $mx = ($x1 + $x2) / 2;
        $my = ($y1 + $y2) / 2 - 38;
        $arcs[] = "M$x1 $y1 Q$mx $my $x2 $y2";
    }
    return ['dots' => $dots, 'arcs' => $arcs];
}


/**
 * The member's path through International, as three steps with one clear next action.
 * Each step: key, title, text, state (done|current|todo), and optionally action => [label, href|form kind].
 */
function intl_journey(int $uid): array
{
    $mine = intl_profile($uid);
    $live = $mine && intl_profile_live($mine);
    $exempt = intl_exempt($uid);
    $joinPaid = intl_join_required() && !$exempt;
    $accessPaid = intl_access_required() && !$exempt;
    $hasAccess = intl_has_access($uid);
    $accessUntil = intl_access_until($uid);

    $s1 = ['key' => 'profile', 'title' => 'Your profile', 'icon' => 'user'];
    if ($mine) {
        $s1 += ['state' => 'done', 'text' => 'Created', 'action' => ['Edit', '/international/join', 'link']];
    } else {
        $s1 += ['state' => 'current', 'text' => 'Tell people where you live and what you hope to find. It takes about two minutes.', 'action' => ['Create profile', '/international/join', 'primary']];
    }

    $s2 = ['key' => 'live', 'title' => 'Go live', 'icon' => 'rocket'];
    if (!$mine) {
        $s2 += ['state' => 'todo', 'text' => (bool)setting('intl_approval') ? 'We check new profiles before they appear.' : 'You appear right after you save.'];
    } elseif ($mine['status'] === 'pending') {
        $s2 += ['state' => 'current', 'text' => 'Waiting for approval. We usually look within a day.'];
    } elseif ($mine['status'] === 'rejected') {
        $s2 += ['state' => 'current', 'text' => 'Needs changes' . ($mine['admin_note'] ? ': ' . $mine['admin_note'] : '. Edit your profile and save to send it again.'), 'action' => ['Edit profile', '/international/join', 'primary']];
    } elseif ($mine['status'] === 'paused') {
        $s2 += ['state' => 'current', 'text' => 'Paused. Nobody can see your profile.', 'action' => ['Resume', '/international/join', 'link']];
    } elseif (!$live && $joinPaid) {
        $s2 += ['state' => 'current', 'text' => 'Approved. ' . intl_price_label('join') . ' puts you live.', 'action' => ['Go live', 'join', 'buy']];
    } else {
        $until = $mine['listed_until'] && $joinPaid && (int)date('Y', strtotime((string)$mine['listed_until'])) < 9000 ? ' until ' . fmt_date((string)$mine['listed_until']) : '';
        $s2 += ['state' => 'done', 'text' => 'You are listed' . $until . '.'];
    }

    $s3 = ['key' => 'meet', 'title' => 'Meet people', 'icon' => 'compass'];
    if (!$accessPaid) {
        $s3 += ['state' => $live ? 'done' : 'todo', 'text' => $exempt && intl_access_required() ? 'Included in your plan.' : 'Free to browse and message.'];
    } elseif ($hasAccess) {
        $s3 += ['state' => 'done', 'text' => $accessUntil === '0' ? 'You have access with no end date.' : 'Access until ' . fmt_date((string)$accessUntil) . '.', 'action' => ['Add time', 'access', 'buyline']];
    } else {
        $s3 += ['state' => 'current', 'text' => 'Unlock access to see everyone and send messages. ' . intl_price_label('access') . '.', 'action' => ['Unlock access', 'access', 'buy']];
    }
    $steps = [$s1, $s2, $s3];
    $allDone = true;
    foreach ($steps as $st) {
        $allDone = $allDone && $st['state'] === 'done';
    }
    return ['steps' => $steps, 'done' => $allDone, 'mine' => $mine, 'live' => $live];
}

/** Admin overview numbers: queue, money, countries, a short health checklist. */
function intl_admin_overview(): array
{
    $counts = [];
    foreach (rows('SELECT status, COUNT(*) AS n FROM intl_profiles GROUP BY status') as $r) {
        $counts[$r['status']] = (int)$r['n'];
    }
    $month = date('Y-m-01 00:00:00');
    $money = fn(string $from) => (float)val("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_type = 'intl' AND status = 'completed' AND COALESCE(processed_at, created_at) >= :m", ['m' => $from], 0);
    $listing = (float)val("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_type = 'intl' AND status = 'completed' AND COALESCE(processed_at, created_at) >= :m AND metadata LIKE '%\"kind\":\"join\"%'", ['m' => $month], 0);
    $recent = rows("SELECT p.id, p.amount, p.description, p.status, COALESCE(p.processed_at, p.created_at) AS at, u.email FROM payments p JOIN users u ON u.id = p.user_id WHERE p.payment_type = 'intl' AND p.status = 'completed' ORDER BY p.id DESC LIMIT 6");
    $queue = rows("SELECT i.user_id, i.country, i.headline, i.updated_at, " . ac_name_sql('u', 'up') . " AS member_name FROM intl_profiles i JOIN users u ON u.id = i.user_id LEFT JOIN user_profiles up ON up.user_id = u.id WHERE i.status = 'pending' ORDER BY i.updated_at ASC LIMIT 5");
    $week = (int)val("SELECT COUNT(*) FROM intl_profiles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [], 0);
    $accessNow = (int)val('SELECT COUNT(*) FROM intl_access WHERE expires_at IS NULL OR expires_at > NOW()', [], 0);
    return ['counts' => $counts, 'month' => $money($month), 'listing_month' => $listing, 'total' => $money('1970-01-01 00:00:00'), 'recent' => $recent, 'queue' => $queue, 'week' => $week, 'access' => $accessNow, 'stats' => intl_stats()];
}

/** One-line summary of what members will see with the chosen setup. */
function intl_setup_sentence(): string
{
    $mode = intl_mode();
    if (!intl_enabled()) {
        return 'International connections are switched off.';
    }
    $parts = [];
    $parts[] = intl_join_required() ? 'Members pay ' . intl_price_label('join') . ' to be listed' : 'Listing is free';
    $parts[] = intl_access_required() ? 'people pay ' . intl_price_label('access') . ' to browse' . ((bool)setting('intl_msg_included') ? ' and message' : '') : 'browsing and messaging are free';
    $free = (string)setting('intl_premium_free');
    if ($mode !== 'free' && $free !== 'none') {
        $parts[] = ($free === 'vip' ? 'VIP' : 'Premium and VIP') . ' members pay nothing';
    }
    return ucfirst(implode(', ', $parts)) . '.';
}
