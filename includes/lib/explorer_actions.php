<?php
/**
 * Explorer actions that change data: swipes, matches, favourites, blocks, reports, profile visits,
 * onboarding/profile saving, verification files and account closing. Pages stay thin and call these.
 * Every function expects its caller to have checked authentication; permissions are enforced here.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Targets
// ---------------------------------------------------------------------------

/** Minimal row for a member who can currently be acted on, or null. $roles limits the account type. */
function explorer_target(int $id, array $roles = ['user']): ?array
{
    if ($id <= 0) {
        return null;
    }
    $t = row('SELECT u.id, u.role, u.status, p.display_name, p.is_paused, p.onboarding_completed
              FROM users u JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id', ['id' => $id]);
    if (!$t || $t['status'] !== 'active' || !in_array($t['role'], $roles, true)) {
        return null;
    }
    return $t;
}

function explorer_first_name(?string $name): string
{
    return explode(' ', trim((string)$name))[0] ?: 'Someone';
}

function explorer_active_match(int $a, int $b): ?array
{
    [$x, $y] = pair_ids($a, $b);
    return row('SELECT * FROM matches WHERE user1_id = :x AND user2_id = :y AND is_active = 1', ['x' => $x, 'y' => $y]);
}

// ---------------------------------------------------------------------------
// Swipes
// ---------------------------------------------------------------------------

/**
 * Like, pass or super like. Likes cost one of the day's likes, super likes one of the day's super likes,
 * passes are free. Returns ['ok'=>true, 'match'=>?array, 'remaining'=>array, 'noop'=>bool]
 * or ['ok'=>false, 'error'=>string, 'code'=>string, 'status'=>int, 'upgrade_url'?=>string].
 */
function explorer_swipe(int $me, int $target, string $dir, int $dwellMs = 0): array
{
    $fail = fn(string $msg, int $status = 422, string $code = 'invalid', array $extra = []) => ['ok' => false, 'error' => $msg, 'status' => $status, 'code' => $code] + $extra;
    $up = ['upgrade_url' => url('/premium')];
    if (!in_array($dir, ['like', 'nope', 'superlike'], true)) {
        return $fail('That action is not available.');
    }
    if ($target === $me || $target <= 0) {
        return $fail('You cannot do that to your own profile.');
    }
    $t = explorer_target($target);
    if (!$t || (int)$t['is_paused'] === 1 || !(int)$t['onboarding_completed'] || users_blocked($me, $target)) {
        return $fail('This member is not available any more.', 404, 'unavailable');
    }
    $rank = ['nope' => 0, 'like' => 1, 'superlike' => 2];
    $dwellMs = max(0, min(600000, $dwellMs));

    try {
        $res = db_transaction(function () use ($me, $target, $dir, $dwellMs, $rank, $fail, $up) {
            $ex = row('SELECT id, direction FROM swipes WHERE swiper_id = :a AND swiped_id = :b FOR UPDATE', ['a' => $me, 'b' => $target]);
            $matchedNow = explorer_active_match($me, $target) !== null;
            if ($dir === 'nope') {
                if ($ex && $ex['direction'] === 'nope') {
                    return ['noop' => true];
                }
                if ($matchedNow) {
                    return $fail('You are matched with this member. Unmatch from your Matches page if you want to end it.', 409, 'matched');
                }
                if ($ex) {
                    q('UPDATE swipes SET direction = \'nope\', dwell_ms = :d, created_at = NOW() WHERE id = :id', ['d' => $dwellMs, 'id' => $ex['id']]);
                    $swipeId = (int)$ex['id'];
                } else {
                    $swipeId = insert('swipes', ['swiper_id' => $me, 'swiped_id' => $target, 'direction' => 'nope', 'dwell_ms' => $dwellMs]);
                }
                return ['swipe_id' => $swipeId, 'changed' => true];
            }
            // like / superlike
            if ($ex && $rank[$ex['direction']] >= $rank[$dir]) {
                return ['noop' => true, 'match' => $matchedNow];
            }
            if ($dir === 'superlike') {
                if (!setting('superlike_enabled')) {
                    return $fail('Super likes are switched off right now.', 403, 'disabled');
                }
                if ((int)feature('daily_superlikes', 0, $me) === 0) {
                    return $fail('Super likes are part of Premium and VIP.', 403, 'upgrade', $up);
                }
                if (usage_remaining($me, 'superlikes') === 0) {
                    return $fail('You have used today’s Super likes. They come back at midnight.', 429, 'limit', $up);
                }
            } elseif (!$ex || $ex['direction'] === 'nope') {
                if (usage_remaining($me, 'swipes') === 0) {
                    return $fail('You have used all your likes for today. Upgrade for unlimited likes, or come back tomorrow.', 429, 'limit', $up);
                }
            }
            if ($ex) {
                q('UPDATE swipes SET direction = :dir, dwell_ms = :d, created_at = NOW() WHERE id = :id', ['dir' => $dir, 'd' => $dwellMs, 'id' => $ex['id']]);
                $swipeId = (int)$ex['id'];
            } else {
                $swipeId = insert('swipes', ['swiper_id' => $me, 'swiped_id' => $target, 'direction' => $dir, 'dwell_ms' => $dwellMs]);
            }
            usage_add($me, $dir === 'superlike' ? 'superlikes' : 'swipes');

            // Did they already like us back?
            $theirs = val("SELECT direction FROM swipes WHERE swiper_id = :t AND swiped_id = :me AND direction IN ('like','superlike')", ['t' => $target, 'me' => $me]);
            $match = null;
            if ($theirs) {
                [$x, $y] = pair_ids($me, $target);
                $m = row('SELECT id, is_active FROM matches WHERE user1_id = :x AND user2_id = :y FOR UPDATE', ['x' => $x, 'y' => $y]);
                if (!$m) {
                    $mid = insert('matches', ['user1_id' => $x, 'user2_id' => $y]);
                    $match = $mid;
                } elseif (!(int)$m['is_active']) {
                    q('UPDATE matches SET is_active = 1, matched_at = NOW(), unmatched_by = NULL, unmatched_at = NULL, user1_seen = 0, user2_seen = 0 WHERE id = :id', ['id' => $m['id']]);
                    $match = (int)$m['id'];
                }
            }
            return ['swipe_id' => $swipeId, 'changed' => true, 'new_match' => $match, 'theirs' => $theirs];
        });
    } catch (Throwable $e) {
        app_log('error', 'swipe failed: ' . $e->getMessage());
        return $fail('We could not save that. Please try again.', 500, 'error');
    }
    if (isset($res['ok']) && $res['ok'] === false) {
        return $res;
    }
    $out = ['ok' => true, 'match' => null, 'noop' => !empty($res['noop']), 'direction' => $dir];
    if (!empty($res['changed'])) {
        q('UPDATE daily_picks SET is_acted = 1 WHERE user_id = :u AND picked_user_id = :p AND pick_date = CURDATE()', ['u' => $me, 'p' => $target]);
        if (!empty($res['new_match'])) {
            $out['match'] = explorer_match_payload($me, $target, (int)$res['new_match']);
            explorer_notify_match($me, $target, (int)$res['new_match']);
        } elseif ($dir !== 'nope') {
            explorer_notify_like($me, $target, $dir, (int)$res['swipe_id']);
        }
    }
    $out['remaining'] = explorer_allowance($me);
    return $out;
}

/** What the match celebration shows. */
function explorer_match_payload(int $me, int $other, int $matchId): array
{
    $o = row('SELECT p.display_name, ' . SQL_PHOTO_COLS . ' FROM user_profiles p ' . sql_photo_join('p.user_id', 'ph') . ' WHERE p.user_id = :id', ['id' => $other]);
    $photo = $o ? photo_from_row($o) : null;
    $u = current_user();
    $mine = $u && !empty($u['avatar_medium']) ? upload_url($u['avatar_medium']) : photo_placeholder_url();
    return [
        'id' => $matchId, 'user_id' => $other, 'name' => explorer_first_name($o['display_name'] ?? ''),
        'photo' => photo_url($photo, 'medium', 'profile'), 'my_photo' => $mine, 'my_name' => $u['first_name'] ?? 'You',
        'message_url' => setting('messaging_enabled') ? url('/messages/with/' . $other) : url('/matches'),
        'profile_url' => url('/u/' . $other), 'title' => (string)setting('match_title'),
    ];
}

function explorer_notify_match(int $me, int $other, int $matchId): void
{
    $names = rows('SELECT user_id, display_name FROM user_profiles WHERE user_id IN (:a, :b)', ['a' => $me, 'b' => $other]);
    $n = [];
    foreach ($names as $r) {
        $n[(int)$r['user_id']] = explorer_first_name($r['display_name']);
    }
    $msgUrl = fn(int $withId) => setting('messaging_enabled') ? '/messages/with/' . $withId : '/matches';
    notify($me, 'match', 'You matched with ' . ($n[$other] ?? 'someone'), 'Say hello while the moment is fresh.', $msgUrl($other), $other, ['match' => $matchId]);
    notify($other, 'match', 'You matched with ' . ($n[$me] ?? 'someone'), 'Say hello while the moment is fresh.', $msgUrl($me), $me, ['match' => $matchId]);
}

/** Tell a member they were liked. Free members are not told who (unless it was a Super like). One per sender per day. */
function explorer_notify_like(int $me, int $target, string $dir, int $swipeId): void
{
    if (!rate_limit('likenotif:' . $target . ':' . $me, 1, 86400)) {
        return;
    }
    $name = explorer_first_name((string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $me], ''));
    if ($dir === 'superlike') {
        notify($target, 'superlike', $name . ' super liked you', 'They really want you to notice. Take a look.', '/u/' . $me, $me, ['swipe' => $swipeId]);
        return;
    }
    if (can('see_who_liked', $target)) {
        notify($target, 'like', $name . ' likes you', 'Like them back to start talking.', '/likes', $me, ['swipe' => $swipeId]);
    } else {
        notify($target, 'like', 'Someone likes you', 'Upgrade to see who, or keep swiping and you may find them.', '/likes', null, ['swipe' => $swipeId]);
    }
}

/** Undo the member's last swipe when the plan allows it. Returns ['ok'=>true, 'card'=>array|null, 'direction'=>string, 'remaining'=>array] or a failure array. */
function explorer_rewind(int $me): array
{
    $fail = fn(string $msg, int $status, string $code, array $extra = []) => ['ok' => false, 'error' => $msg, 'status' => $status, 'code' => $code] + $extra;
    $up = ['upgrade_url' => url('/premium')];
    if (!setting('rewind_enabled')) {
        return $fail('Undo is switched off right now.', 403, 'disabled');
    }
    if (!can('rewind', $me) || (int)feature('daily_rewinds', 0, $me) === 0) {
        return $fail('Undoing a swipe is part of Premium and VIP.', 403, 'upgrade', $up);
    }
    if (usage_remaining($me, 'rewinds') === 0) {
        return $fail('You have used today’s undos. They come back at midnight.', 429, 'limit', $up);
    }
    $last = row('SELECT * FROM swipes WHERE swiper_id = :me ORDER BY id DESC LIMIT 1', ['me' => $me]);
    if (!$last) {
        return $fail('There is nothing to undo yet.', 404, 'nothing');
    }
    $target = (int)$last['swiped_id'];
    if (explorer_active_match($me, $target)) {
        return $fail('That swipe made a match, so it cannot be undone. You can unmatch from your Matches page.', 409, 'matched');
    }
    $today = date('Y-m-d', strtotime((string)$last['created_at'])) === date('Y-m-d');
    db_transaction(function () use ($me, $last, $today, $target) {
        delete_rows('swipes', ['id' => $last['id']]);
        if ($today && $last['direction'] === 'like') {
            q('UPDATE daily_usage SET swipes_used = GREATEST(0, swipes_used - 1) WHERE user_id = :u AND usage_date = CURDATE()', ['u' => $me]);
        } elseif ($today && $last['direction'] === 'superlike') {
            q('UPDATE daily_usage SET superlikes_used = GREATEST(0, superlikes_used - 1) WHERE user_id = :u AND usage_date = CURDATE()', ['u' => $me]);
        }
        usage_add($me, 'rewinds');
        q("DELETE FROM notifications WHERE user_id = :t AND type IN ('like','superlike') AND is_read = 0 AND data LIKE :d", ['t' => $target, 'd' => '%"swipe":' . (int)$last['id'] . '}%']);
        q('UPDATE daily_picks SET is_acted = 0 WHERE user_id = :u AND picked_user_id = :p AND pick_date = CURDATE()', ['u' => $me, 'p' => $target]);
    });
    $card = null;
    $m = members_search(['only_ids' => [$target], 'hide_swiped' => false, 'has_photo' => false], 1, 0)['rows'];
    if ($m) {
        $card = explorer_cards($m, $me)[0] ?? null;
    }
    return ['ok' => true, 'card' => $card, 'direction' => $last['direction'], 'remaining' => explorer_allowance($me)];
}

/** Remove a like the member sent (not allowed once matched). */
function explorer_take_back_like(int $me, int $target): array
{
    if (explorer_active_match($me, $target)) {
        return ['ok' => false, 'error' => 'You are matched, so this is not a like any more. Use Unmatch if you want to end it.', 'status' => 409];
    }
    $n = delete_rows('swipes', ['swiper_id' => $me, 'swiped_id' => $target]);
    return ['ok' => $n > 0, 'error' => $n > 0 ? '' : 'You have not liked this member.', 'status' => $n > 0 ? 200 : 404];
}

// ---------------------------------------------------------------------------
// Matches, favourites, blocks, reports
// ---------------------------------------------------------------------------

function explorer_unmatch(int $me, int $other): array
{
    [$x, $y] = pair_ids($me, $other);
    $n = q('UPDATE matches SET is_active = 0, unmatched_by = :me, unmatched_at = NOW() WHERE user1_id = :x AND user2_id = :y AND is_active = 1', ['me' => $me, 'x' => $x, 'y' => $y])->rowCount();
    return $n > 0 ? ['ok' => true] : ['ok' => false, 'error' => 'You are not matched with this member.', 'status' => 404];
}

/** Add or remove a favourite. $on null toggles. Returns ['ok'=>true,'favorited'=>bool,'count'=>int]. */
function explorer_favorite(int $me, int $target, ?bool $on = null): array
{
    if ($target === $me || !explorer_target($target, ['user', 'host']) || users_blocked($me, $target)) {
        return ['ok' => false, 'error' => 'This member is not available.', 'status' => 404];
    }
    $has = (int)val('SELECT COUNT(*) FROM favorites WHERE user_id = :u AND target_id = :t', ['u' => $me, 't' => $target]) > 0;
    $want = $on ?? !$has;
    if ($want && !$has) {
        if ((int)val('SELECT COUNT(*) FROM favorites WHERE user_id = :u', ['u' => $me]) >= 500) {
            return ['ok' => false, 'error' => 'You can save up to 500 favourites. Remove a few first.', 'status' => 422];
        }
        q('INSERT IGNORE INTO favorites (user_id, target_id) VALUES (:u, :t)', ['u' => $me, 't' => $target]);
    } elseif (!$want && $has) {
        delete_rows('favorites', ['user_id' => $me, 'target_id' => $target]);
    }
    return ['ok' => true, 'favorited' => $want, 'count' => (int)val('SELECT COUNT(*) FROM favorites WHERE user_id = :u', ['u' => $me])];
}

function explorer_block(int $me, int $target): array
{
    $t = $target > 0 && $target !== $me ? row('SELECT id, role, status FROM users WHERE id = :id', ['id' => $target]) : null;
    if (!$t || $t['role'] === 'admin') {
        return ['ok' => false, 'error' => 'This member could not be found.', 'status' => 404];
    }
    [$x, $y] = pair_ids($me, $target);
    db_transaction(function () use ($me, $target, $x, $y) {
        q('INSERT IGNORE INTO blocks (blocker_id, blocked_id) VALUES (:a, :b)', ['a' => $me, 'b' => $target]);
        q('UPDATE matches SET is_active = 0, unmatched_by = :me, unmatched_at = NOW() WHERE user1_id = :x AND user2_id = :y AND is_active = 1', ['me' => $me, 'x' => $x, 'y' => $y]);
        q('DELETE FROM swipes WHERE (swiper_id = :a AND swiped_id = :b) OR (swiper_id = :b AND swiped_id = :a)', ['a' => $me, 'b' => $target]);
        q('DELETE FROM favorites WHERE (user_id = :a AND target_id = :b) OR (user_id = :b AND target_id = :a)', ['a' => $me, 'b' => $target]);
        q('DELETE FROM daily_picks WHERE (user_id = :a AND picked_user_id = :b) OR (user_id = :b AND picked_user_id = :a)', ['a' => $me, 'b' => $target]);
    });
    return ['ok' => true];
}

function explorer_unblock(int $me, int $target): array
{
    $n = delete_rows('blocks', ['blocker_id' => $me, 'blocked_id' => $target]);
    return $n > 0 ? ['ok' => true] : ['ok' => false, 'error' => 'That member is not on your blocked list.', 'status' => 404];
}

/** Report reasons the admin configured. */
function explorer_report_reasons(): array
{
    $r = setting_lines('report_reasons');
    return $r ?: ['Fake profile', 'Inappropriate photos', 'Harassment or abuse', 'Spam or scam', 'Under 18', 'Something else'];
}

/**
 * File a report. Types: user, message, host, booking, photo. Returns ['ok'=>true,'id'=>int,'message'=>string] or a failure array.
 * target_id is the member for user/host, otherwise the message, booking or photo id.
 */
function explorer_report(int $me, string $type, int $targetId, string $reason, string $details): array
{
    $fail = fn(string $m, int $s = 422) => ['ok' => false, 'error' => $m, 'status' => $s];
    if (!in_array($type, ['user', 'message', 'host', 'booking', 'photo'], true) || $targetId <= 0) {
        return $fail('We could not tell what you are reporting.');
    }
    if (!in_array($reason, explorer_report_reasons(), true)) {
        return $fail('Choose a reason from the list.');
    }
    $details = trim(mb_substr($details, 0, 1000));
    $reported = 0;
    switch ($type) {
        case 'user':
        case 'host':
            $reported = $targetId;
            break;
        case 'message':
            $m = row('SELECT m.sender_id, c.user1_id, c.user2_id FROM messages m JOIN conversations c ON c.id = m.conversation_id WHERE m.id = :id', ['id' => $targetId]);
            if (!$m || ($m['user1_id'] != $me && $m['user2_id'] != $me)) {
                return $fail('That message could not be found.', 404);
            }
            $reported = (int)$m['sender_id'];
            break;
        case 'photo':
            $reported = (int)val('SELECT user_id FROM user_photos WHERE id = :id', ['id' => $targetId], 0);
            break;
        case 'booking':
            try {
                $b = row('SELECT host_id, client_id FROM host_bookings WHERE id = :id', ['id' => $targetId]);
            } catch (Throwable $e) {
                $b = null;
            }
            if (!$b || ($b['host_id'] != $me && $b['client_id'] != $me)) {
                return $fail('That booking could not be found.', 404);
            }
            $reported = (int)($b['host_id'] == $me ? $b['client_id'] : $b['host_id']);
            break;
    }
    $who = $reported > 0 ? row('SELECT id, role, status FROM users WHERE id = :id', ['id' => $reported]) : null;
    if (!$who || $who['role'] === 'admin') {
        return $fail('That member could not be found.', 404);
    }
    if ($reported === $me) {
        return $fail('You cannot report yourself.');
    }
    if ($type === 'host' && $who['role'] !== 'host') {
        return $fail('That host could not be found.', 404);
    }
    $ext = explorer_reports_extended();
    $dupSql = "SELECT COUNT(*) FROM reports WHERE reporter_id = :me AND reported_id = :r AND status IN ('pending','reviewing') AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $dupParams = ['me' => $me, 'r' => $reported];
    if ($ext) {
        $dupSql .= ' AND target_type = :tt AND (target_id <=> :ti)';
        $dupParams += ['tt' => $type, 'ti' => $type === 'user' || $type === 'host' ? null : $targetId];
    }
    if ((int)val($dupSql, $dupParams) > 0) {
        return $fail('You have already reported this. Our team is reviewing it.', 409);
    }
    $desc = ($type !== 'user' ? '[' . ucfirst($type) . ' #' . $targetId . '] ' : '') . $details;
    $row = ['reporter_id' => $me, 'reported_id' => $reported, 'reason' => mb_substr($reason, 0, $ext ? 80 : 30), 'description' => $desc !== '' ? $desc : null];
    if ($ext) {
        $row += ['target_type' => $type, 'target_id' => in_array($type, ['user', 'host'], true) ? null : $targetId];
    }
    $id = insert('reports', $row);

    // Several different people reporting the same member puts the account under review.
    $limit = (int)setting('reports_auto_suspend');
    if ($limit > 0 && $who['status'] === 'active') {
        $n = (int)val("SELECT COUNT(DISTINCT reporter_id) FROM reports WHERE reported_id = :r AND status IN ('pending','reviewing') AND created_at > DATE_SUB(NOW(), INTERVAL 60 DAY)", ['r' => $reported]);
        if ($n >= $limit) {
            q("UPDATE users SET status = 'suspended', status_reason = 'Held for review after several reports' WHERE id = :id AND status = 'active'", ['id' => $reported]);
            app_log('safety', 'auto-suspended user ' . $reported . ' after ' . $n . ' reports');
        }
    }
    foreach (col("SELECT id FROM users WHERE role = 'admin' AND status = 'active' LIMIT 20") as $adminId) {
        notify((int)$adminId, 'system', 'New report: ' . $reason, 'A member reported a ' . $type . '. Open the reports queue to review it.', '/admin/reports', $me, ['report' => $id]);
    }
    return ['ok' => true, 'id' => $id, 'message' => 'Thank you. Our team will review your report and act on it if it breaks the rules.'];
}

// ---------------------------------------------------------------------------
// Profile visits
// ---------------------------------------------------------------------------

/** Record that $viewer looked at $viewed (once a day per pair) and tell the owner. Returns true when a new visit was logged. */
function explorer_log_visit(array $viewer, int $viewedId): bool
{
    $v = (int)$viewer['id'];
    if ($v === $viewedId || $viewer['role'] !== 'user' || explorer_is_incognito($viewer)) {
        return false;
    }
    if ((int)val('SELECT allow_visits_log FROM user_profiles WHERE user_id = :u', ['u' => $v], 1) !== 1) {
        return false;
    }
    $ex = row('SELECT id, last_viewed_at FROM profile_views WHERE viewer_id = :v AND viewed_id = :p', ['v' => $v, 'p' => $viewedId]);
    if (!$ex) {
        q('INSERT IGNORE INTO profile_views (viewer_id, viewed_id) VALUES (:v, :p)', ['v' => $v, 'p' => $viewedId]);
    } elseif (strtotime((string)$ex['last_viewed_at']) > time() - 86400) {
        return false;
    } else {
        q('UPDATE profile_views SET view_count = view_count + 1, last_viewed_at = NOW() WHERE id = :id', ['id' => $ex['id']]);
    }
    if (setting('visit_notifications')) {
        $name = explorer_first_name($viewer['name'] ?? '');
        if (can('profile_visitors', $viewedId)) {
            notify($viewedId, 'visit', $name . ' viewed your profile', 'See who else has been looking.', '/visitors', $v);
        } else {
            notify($viewedId, 'visit', 'Someone viewed your profile', 'Upgrade to see who has been looking.', '/visitors', null);
        }
    }
    return true;
}

// ---------------------------------------------------------------------------
// Profile saving (shared by onboarding and the editor)
// ---------------------------------------------------------------------------

/** Text from a member: trimmed, controls stripped, banned words handled per the admin's setting. Adds to $errors when blocked. */
function explorer_clean_text(string $text, int $max, string $field, array &$errors): string
{
    $text = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? '');
    $text = mb_substr($text, 0, $max);
    if ($text === '') {
        return '';
    }
    $mode = (string)setting('profanity_action');
    if ($mode !== 'off' && contains_banned_word($text) !== null) {
        if ($mode === 'block') {
            $errors[$field] = 'Please remove words that are not allowed on ConnectHub and try again.';
        } else {
            $text = mask_banned_words($text);
        }
    }
    return $text;
}

function explorer_validate_basics(array $in, bool $needDob = true): array
{
    $errors = [];
    $name = trim((string)($in['display_name'] ?? ''));
    $name = preg_replace('/\s+/', ' ', $name) ?? '';
    if (!preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\'’-]{1,49}$/u', $name)) {
        $errors['display_name'] = 'Enter the name you go by, 2 to 50 letters.';
    } elseif (contains_banned_word($name) !== null) {
        $errors['display_name'] = 'Please choose a different name.';
    }
    $out = ['display_name' => $name];
    if ($needDob) {
        $dob = '';
        if (!empty($in['dob_year']) || !empty($in['dob_month']) || !empty($in['dob_day'])) {
            $y = (int)($in['dob_year'] ?? 0);
            $mo = (int)($in['dob_month'] ?? 0);
            $d = (int)($in['dob_day'] ?? 0);
            if ($y > 1900 && checkdate($mo, $d, $y)) {
                $dob = sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
        } elseif (!empty($in['date_of_birth']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$in['date_of_birth'])) {
            [$y, $mo, $d] = array_map('intval', explode('-', (string)$in['date_of_birth']));
            $dob = checkdate($mo, $d, $y) ? (string)$in['date_of_birth'] : '';
        }
        $age = $dob !== '' ? age_from_dob($dob) : null;
        $min = max(18, (int)setting('min_age'));
        if ($dob === '' || $age === null) {
            $errors['dob'] = 'Choose your full date of birth.';
        } elseif ($age < $min) {
            $errors['dob'] = 'You must be at least ' . $min . ' to use ' . site_name() . '.';
        } elseif ($age > 110) {
            $errors['dob'] = 'Check your year of birth.';
        }
        $out['date_of_birth'] = $dob;
    }
    $gender = (string)($in['gender'] ?? '');
    if (!isset(explorer_options('gender')[$gender])) {
        $errors['gender'] = 'Choose the option that fits you best.';
    }
    $out['gender'] = $gender;
    $show = (string)($in['interested_in'] ?? '');
    if (!in_array($show, ['male', 'female', 'everyone'], true)) {
        $errors['interested_in'] = 'Choose who you would like to meet.';
    }
    $out['interested_in'] = $show;
    return [$errors, $out];
}

function explorer_validate_about(array $in, bool $lifestyle = false): array
{
    $errors = [];
    $out = [];
    $max = max(100, (int)setting('bio_max_length'));
    $bio = explorer_clean_text(str_replace(["\r\n", "\r"], "\n", (string)($in['bio'] ?? '')), $max, 'bio', $errors);
    $bio = preg_replace("/\n{3,}/", "\n\n", $bio) ?? $bio;
    if ($bio !== '' && (preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $bio) || preg_match('/(?:\d[\s\-.()]*){9,}/', $bio))) {
        $errors['bio'] = 'Keep phone numbers and email addresses out of your bio. You can share them under Contact details instead.';
    }
    $out['bio'] = $bio !== '' ? $bio : null;
    if (setting('onboarding_show_occupation')) {
        $out['occupation'] = explorer_clean_text((string)($in['occupation'] ?? ''), 150, 'occupation', $errors) ?: null;
    }
    if (setting('onboarding_show_education')) {
        $edu = explorer_clean_text((string)($in['education'] ?? ''), 150, 'education', $errors);
        $out['education'] = $edu !== '' ? $edu : null;
    }
    if (setting('onboarding_show_height')) {
        $h = trim((string)($in['height_cm'] ?? ''));
        if ($h === '') {
            $out['height_cm'] = null;
        } elseif (!ctype_digit($h) || (int)$h < 120 || (int)$h > 230) {
            $errors['height_cm'] = 'Enter your height in centimetres, between 120 and 230.';
        } else {
            $out['height_cm'] = (int)$h;
        }
    }
    $bt = (string)($in['body_type'] ?? '');
    if ($bt !== '' && !isset(explorer_options('body_type')[$bt])) {
        $errors['body_type'] = 'Choose one of the options.';
    }
    $out['body_type'] = $bt !== '' ? $bt : null;
    if ($lifestyle) {
        foreach (['smoking', 'drinking', 'relationship_status', 'children', 'want_children'] as $f) {
            $v = (string)($in[$f] ?? '');
            if ($v !== '' && !isset(explorer_options($f)[$v])) {
                $errors[$f] = 'Choose one of the options.';
            }
            $out[$f] = $v !== '' ? $v : null;
        }
        $out['religion'] = explorer_clean_text((string)($in['religion'] ?? ''), 50, 'religion', $errors) ?: null;
        $out['languages'] = explorer_clean_text((string)($in['languages'] ?? ''), 255, 'languages', $errors) ?: null;
    }
    return [$errors, $out];
}

function explorer_validate_looking(array $in): array
{
    $errors = [];
    $intent = (string)($in['intent'] ?? '');
    if ($intent !== '' && !isset(explorer_intents()[$intent])) {
        $errors['intent'] = 'Choose one of the options.';
    }
    $out = ['intent' => $intent !== '' ? $intent : null, 'looking_for' => explorer_clean_text((string)($in['looking_for'] ?? ''), 255, 'looking_for', $errors) ?: null];
    return [$errors, $out];
}

/** Age range, distance and the two toggles. Returns [errors, discovery_preferences columns]. */
function explorer_validate_prefs(array $in): array
{
    $errors = [];
    $min = max(18, (int)setting('min_age'));
    $max = max($min + 1, (int)setting('max_age'));
    $a = (int)($in['age_min'] ?? $min);
    $b = (int)($in['age_max'] ?? 45);
    if ($a < $min || $b > $max) {
        $errors['age_min'] = 'Choose an age range between ' . $min . ' and ' . $max . '.';
    } elseif ($a > $b) {
        $errors['age_min'] = 'The youngest age cannot be higher than the oldest.';
    }
    $rmax = max(5, (int)setting('radius_max'));
    $d = (int)($in['distance_km'] ?? setting('radius_default'));
    if ($d < 1 || $d > $rmax) {
        $errors['distance_km'] = 'Choose a distance between 1 and ' . $rmax . ' km.';
    }
    return [$errors, [
        'age_min' => max($min, min($a, $b)), 'age_max' => min($max, max($a, $b)), 'distance_km' => max(1, min($rmax, $d)),
        'show_verified_only' => !empty($in['show_verified_only']) ? 1 : 0, 'show_photos_only' => !empty($in['show_photos_only']) ? 1 : 0,
    ]];
}

/** Phone, WhatsApp, email and Instagram the member chooses to share. Returns [errors, columns]. */
function explorer_validate_contact(array $in): array
{
    $errors = [];
    $out = [];
    foreach (['phone' => 'phone number', 'whatsapp' => 'WhatsApp number'] as $f => $label) {
        $raw = trim((string)($in['contact_' . $f] ?? ''));
        if ($raw === '') {
            $out['contact_' . $f] = null;
        } else {
            $n = phone_normalize($raw);
            if ($n === '') {
                $errors['contact_' . $f] = 'Enter a valid ' . $label . ', for example 0712 345 678.';
            }
            $out['contact_' . $f] = $n ?: null;
        }
    }
    $em = trim((string)($in['contact_email'] ?? ''));
    if ($em !== '' && !valid_email($em)) {
        $errors['contact_email'] = 'Enter a valid email address.';
    }
    $out['contact_email'] = $em !== '' ? strtolower($em) : null;
    $ig = trim((string)($in['contact_instagram'] ?? ''));
    $ig = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $ig) ?? $ig;
    $ig = ltrim(trim($ig, '/ '), '@');
    if ($ig !== '' && !preg_match('/^[A-Za-z0-9._]{1,30}$/', $ig)) {
        $errors['contact_instagram'] = 'Use your Instagram username, for example amina_ke.';
    }
    $out['contact_instagram'] = $ig !== '' ? $ig : null;
    $out['show_contact'] = !empty($in['show_contact']) ? 1 : 0;
    return [$errors, $out];
}

function explorer_save_prefs(int $userId, array $cols): void
{
    explorer_prefs($userId);
    update('discovery_preferences', $cols, ['user_id' => $userId]);
}

function explorer_save_profile(int $userId, array $cols): void
{
    if ($cols) {
        update('user_profiles', $cols, ['user_id' => $userId]);
    }
    profile_recalculate_completion($userId);
}

/** Did the member pick a place that is not the one already stored (a GPS fix is kept when nothing changed)? */
function explorer_location_differs(int $userId, int $countyId, int $townId): bool
{
    $want = $townId > 0 ? $townId : $countyId;
    if ($want <= 0) {
        return false;
    }
    $cur = user_location($userId);
    return !$cur || (int)($cur['location_id'] ?? 0) !== $want;
}

/** Save a manually chosen county / town. Returns an error message or ''. */
function explorer_save_manual_location(int $userId, int $countyId, int $townId): string
{
    if ($townId > 0) {
        $t = location_by_id($townId);
        if (!$t || $t['type'] !== 'town' || ($countyId > 0 && (int)$t['parent_id'] !== $countyId)) {
            return 'Choose a town that belongs to the county you picked.';
        }
        set_user_location_from_location($userId, $townId);
        return '';
    }
    if ($countyId > 0) {
        $c = location_by_id($countyId);
        if (!$c || $c['type'] !== 'county') {
            return 'Choose a county from the list.';
        }
        set_user_location_from_location($userId, $countyId);
        return '';
    }
    return 'Choose your county, or use your current location.';
}

// ---------------------------------------------------------------------------
// Onboarding
// ---------------------------------------------------------------------------

/** Steps in the wizard order, including the closing "finish" screen. */
function explorer_wizard_steps(): array
{
    return array_merge(array_keys(explorer_steps()), ['finish']);
}

/** Step number stored in user_profiles.onboarding_step (1-based over the full list of steps). */
function explorer_step_number(string $step): int
{
    $all = ['basics', 'location', 'photos', 'about', 'interests', 'looking', 'finish'];
    $i = array_search($step, $all, true);
    return $i === false ? 1 : $i + 1;
}

/** Why a step is not finished yet, or '' when it is. Used by the wizard and by the final check. */
function explorer_step_problem(int $userId, string $step): string
{
    switch ($step) {
        case 'basics':
            $p = row('SELECT display_name, date_of_birth, gender FROM user_profiles WHERE user_id = :u', ['u' => $userId]);
            if (!$p || trim((string)$p['display_name']) === '' || !$p['gender'] || age_from_dob($p['date_of_birth']) === null) {
                return 'Add your name, date of birth and gender.';
            }
            return '';
        case 'location':
            return (int)val('SELECT COUNT(*) FROM user_locations WHERE user_id = :u', ['u' => $userId]) > 0 ? '' : 'Tell us where you are so we can show people nearby.';
        case 'photos':
            $min = max(1, (int)setting('photos_min'));
            $n = (int)val("SELECT COUNT(*) FROM user_photos WHERE user_id = :u AND moderation_status <> 'rejected'", ['u' => $userId]);
            return $n >= $min ? '' : 'Add at least ' . plural($min, 'photo') . ' to continue.';
        case 'about':
            $bio = trim((string)val('SELECT bio FROM user_profiles WHERE user_id = :u', ['u' => $userId], ''));
            return mb_strlen($bio) >= 10 ? '' : 'Write a line or two about yourself.';
        case 'interests':
            $min = max(1, (int)setting('interests_min'));
            $n = (int)val('SELECT COUNT(*) FROM user_interests WHERE user_id = :u', ['u' => $userId]);
            return $n >= $min ? '' : 'Pick at least ' . $min . ' interests.';
        case 'looking':
            return trim((string)val('SELECT intent FROM user_profiles WHERE user_id = :u', ['u' => $userId], '')) !== '' ? '' : 'Tell us what you are looking for.';
    }
    return '';
}

/**
 * Save one wizard step. Returns ['ok'=>true, 'next'=>stepKey] or ['ok'=>false, 'errors'=>[field=>msg], 'error'=>string].
 */
function explorer_onboarding_step(int $userId, string $step, array $in, bool $skip = false): array
{
    $steps = explorer_wizard_steps();
    if (!in_array($step, $steps, true)) {
        return ['ok' => false, 'error' => 'Unknown step.', 'errors' => []];
    }
    $fail = fn(array $errors, string $msg = '') => ['ok' => false, 'errors' => $errors, 'error' => $msg !== '' ? $msg : (reset($errors) ?: 'Please check the highlighted fields.')];
    $required = $step !== 'finish' && explorer_step_required($step);
    if ($skip) {
        if ($required) {
            return $fail([], 'This step is needed before you can continue.');
        }
        $next = explorer_next_step($step, $userId);
        q('UPDATE user_profiles SET onboarding_step = GREATEST(onboarding_step, :s) WHERE user_id = :u AND onboarding_completed = 0', ['s' => explorer_step_number($next), 'u' => $userId]);
        return ['ok' => true, 'next' => $next];
    }
    switch ($step) {
        case 'basics':
            [$errors, $cols] = explorer_validate_basics($in);
            if ($errors) {
                return $fail($errors);
            }
            explorer_save_profile($userId, $cols);
            break;
        case 'location':
            $county = (int)($in['county'] ?? 0);
            $town = (int)($in['town'] ?? 0);
            if (explorer_location_differs($userId, $county, $town)) {
                $e = explorer_save_manual_location($userId, $county, $town);
                if ($e !== '') {
                    return $fail(['location' => $e]);
                }
            } elseif ($p = explorer_step_problem($userId, 'location')) {
                return $fail(['location' => $p]);
            }
            profile_recalculate_completion($userId);
            break;
        case 'photos':
            if ($p = explorer_step_problem($userId, 'photos')) {
                return $fail(['photos' => $p]);
            }
            break;
        case 'about':
            [$errors, $cols] = explorer_validate_about($in);
            if (!$errors && $required && mb_strlen((string)($cols['bio'] ?? '')) < 10) {
                $errors['bio'] = 'Write a line or two about yourself.';
            }
            if ($errors) {
                return $fail($errors);
            }
            explorer_save_profile($userId, $cols);
            break;
        case 'interests':
            $ids = int_list($in['interests'] ?? []);
            $min = max(1, (int)setting('interests_min'));
            if (count($ids) < $min && $required) {
                return $fail(['interests' => 'Pick at least ' . $min . ' interests.']);
            }
            $maxPick = max($min, (int)setting('interests_max'));
            if (count($ids) > $maxPick) {
                return $fail(['interests' => 'You can pick up to ' . $maxPick . ' interests.']);
            }
            if ($ids) {
                explorer_save_interests($userId, $ids);
            }
            break;
        case 'looking':
            [$e1, $cols] = explorer_validate_looking($in);
            [$e2, $prefs] = explorer_validate_prefs($in);
            $errors = $e1 + $e2;
            if (!$errors && $required && empty($cols['intent'])) {
                $errors['intent'] = 'Tell us what you are looking for.';
            }
            if ($errors) {
                return $fail($errors);
            }
            explorer_save_profile($userId, $cols);
            explorer_save_prefs($userId, $prefs);
            break;
        case 'finish':
            foreach (array_keys(explorer_steps()) as $s) {
                if (explorer_step_required($s) && ($p = explorer_step_problem($userId, $s))) {
                    return ['ok' => false, 'errors' => [], 'error' => $p, 'goto' => $s];
                }
            }
            explorer_onboarding_finish($userId);
            return ['ok' => true, 'next' => 'done', 'redirect' => url('/discover'), 'celebrate' => (string)setting('onboarding_celebration')];
    }
    $next = explorer_next_step($step, $userId);
    q('UPDATE user_profiles SET onboarding_step = GREATEST(onboarding_step, :s) WHERE user_id = :u AND onboarding_completed = 0', ['s' => explorer_step_number($next), 'u' => $userId]);
    return ['ok' => true, 'next' => $next];
}

function explorer_next_step(string $step, int $userId): string
{
    $steps = explorer_wizard_steps();
    $i = array_search($step, $steps, true);
    return $steps[($i === false ? 0 : $i) + 1] ?? 'finish';
}

/** Where a returning member should pick up: the stored step, or the first unfinished required one before it. */
function explorer_resume_step(int $userId, int $storedStep): string
{
    $all = ['basics', 'location', 'photos', 'about', 'interests', 'looking', 'finish'];
    $steps = explorer_wizard_steps();
    $want = $all[max(0, min(count($all) - 1, $storedStep - 1))];
    foreach ($steps as $s) {
        if ($s === 'finish') {
            return 'finish';
        }
        if (explorer_step_number($s) >= explorer_step_number($want)) {
            return $s;
        }
        if (explorer_step_required($s) && explorer_step_problem($userId, $s)) {
            return $s;
        }
    }
    return 'finish';
}

function explorer_onboarding_finish(int $userId): void
{
    q('UPDATE user_profiles SET onboarding_completed = 1, onboarding_step = 7 WHERE user_id = :u', ['u' => $userId]);
    profile_recalculate_completion($userId);
    $u = row('SELECT u.email, u.role, p.display_name FROM users u JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :u', ['u' => $userId]);
    if (!$u) {
        return;
    }
    $name = explorer_first_name($u['display_name']);
    notify($userId, 'system', 'Welcome to ' . site_name() . ', ' . $name, 'Your profile is live. Start with a few swipes, or check who is nearby.', '/discover');
    if (setting('email_welcome')) {
        try {
            send_template_mail($u['email'], 'Welcome to ' . site_name(), 'welcome', ['name' => $name, 'role' => $u['role']]);
        } catch (Throwable $e) {
            app_log('error', 'welcome email failed: ' . $e->getMessage());
        }
    }
    if (function_exists('matching_recompute_user')) {
        try {
            matching_recompute_user($userId);
        } catch (Throwable $e) {
            app_log('error', 'match scoring after onboarding failed: ' . $e->getMessage());
        }
    }
}

// ---------------------------------------------------------------------------
// Photos
// ---------------------------------------------------------------------------

/** A member's own gallery for the manager UI (all statuses). */
function explorer_own_photos(int $userId): array
{
    return rows('SELECT id, user_id, large_path, medium_path, thumb_path, blur_path, is_primary, is_vip_only, sort_order, moderation_status, rejection_reason, created_at
                 FROM user_photos WHERE user_id = :u ORDER BY is_primary DESC, sort_order, id', ['u' => $userId]);
}

function explorer_photo_status_text(array $p): array
{
    switch ($p['moderation_status']) {
        case 'pending':
            return ['Waiting for review', 'Only you can see this photo until it is approved.', 'warning'];
        case 'rejected':
            return ['Not accepted', trim((string)$p['rejection_reason']) !== '' ? (string)$p['rejection_reason'] : 'It breaks our photo rules. Delete it and upload a different one.', 'danger'];
    }
    return ['Live', 'Everyone who can see your profile can see this photo.', 'success'];
}

/** One photo as the manager UI needs it. */
function explorer_photo_payload(array $p): array
{
    [$label, $text, $tone] = explorer_photo_status_text($p);
    return [
        'id' => (int)$p['id'], 'url' => upload_url($p['medium_path']), 'thumb' => upload_url($p['thumb_path']), 'large' => upload_url($p['large_path']),
        'primary' => (int)$p['is_primary'] === 1, 'vip' => (int)$p['is_vip_only'] === 1, 'status' => $p['moderation_status'],
        'status_label' => $label, 'status_text' => $text, 'tone' => $tone,
    ];
}

/** Renumber sort_order to the given order (own photos only) and make the first approved photo the main one. */
function explorer_reorder_photos(int $userId, array $ids): bool
{
    $own = array_map('intval', col('SELECT id FROM user_photos WHERE user_id = :u', ['u' => $userId]));
    $ids = array_values(array_filter(array_map('intval', $ids), fn($i) => in_array($i, $own, true)));
    $ids = array_values(array_unique(array_merge($ids, array_diff($own, $ids))));
    if (!$ids) {
        return false;
    }
    db_transaction(function () use ($userId, $ids) {
        foreach ($ids as $pos => $pid) {
            q('UPDATE user_photos SET sort_order = :o WHERE id = :id AND user_id = :u', ['o' => $pos, 'id' => $pid, 'u' => $userId]);
        }
        $first = val("SELECT id FROM user_photos WHERE user_id = :u AND moderation_status <> 'rejected' ORDER BY (moderation_status = 'approved') DESC, sort_order, id LIMIT 1", ['u' => $userId]);
        if ($first) {
            q('UPDATE user_photos SET is_primary = IF(id = :id, 1, 0) WHERE user_id = :u', ['id' => $first, 'u' => $userId]);
        }
    });
    return true;
}

// ---------------------------------------------------------------------------
// Verification
// ---------------------------------------------------------------------------

/**
 * Selfies and ID documents live under storage/private/verification/{userId}/{selfie|id}_{random}.jpg
 * (never under the web root). verification_requests.selfie_path / id_document_path hold the part after storage/private/.
 */
function verification_private_root(): string
{
    $dir = ROOT . '/storage/private';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    return $dir;
}

/**
 * Absolute path of a stored verification file for an authorised caller (the admin module serves it after its own permission check).
 * $kind is 'selfie' or 'id'. Returns null when there is no such file.
 */
function verification_file_path(int $requestId, string $kind = 'selfie'): ?string
{
    $r = row('SELECT selfie_path, id_document_path FROM verification_requests WHERE id = :id', ['id' => $requestId]);
    if (!$r) {
        return null;
    }
    $rel = $kind === 'id' ? (string)$r['id_document_path'] : (string)$r['selfie_path'];
    if ($rel === '' || strpos($rel, '..') !== false) {
        return null;
    }
    $root = realpath(verification_private_root());
    $abs = realpath($root . '/' . ltrim($rel, '/'));
    if ($root === false || $abs === false || strpos($abs, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($abs)) {
        return null;
    }
    return $abs;
}

/** Re-encode an uploaded picture into private storage. Returns ['ok','error','path'(relative to storage/private)]. */
function verification_store_upload(array $file, int $userId, string $kind): array
{
    $problem = image_upload_problem($file, max(2, (int)setting('photo_max_mb')), 300);
    if ($problem !== '') {
        return ['ok' => false, 'error' => $problem, 'path' => ''];
    }
    try {
        $sub = 'verification/' . $userId;
        $dir = verification_private_root() . '/' . $sub;
        if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create folder');
        }
        $rel = $sub . '/' . ($kind === 'id' ? 'id' : 'selfie') . '_' . random_token(8) . '.jpg';
        $img = image_load($file['tmp_name']);
        image_save_jpeg(image_fit($img, 1800), verification_private_root() . '/' . $rel, 85);
        return ['ok' => true, 'error' => '', 'path' => $rel];
    } catch (Throwable $e) {
        app_log('error', 'verification upload failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'We could not process that picture. Try another one.', 'path' => ''];
    }
}

function verification_latest(int $userId): ?array
{
    return row('SELECT * FROM verification_requests WHERE user_id = :u ORDER BY id DESC LIMIT 1', ['u' => $userId]);
}

// ---------------------------------------------------------------------------
// Account
// ---------------------------------------------------------------------------

/** Hide the account until the member signs in again (login reactivates it). */
function explorer_deactivate_account(int $userId): void
{
    q("UPDATE users SET status = 'deactivated', status_reason = 'Deactivated by the member' WHERE id = :u AND status = 'active'", ['u' => $userId]);
    q('UPDATE user_profiles SET is_paused = 1 WHERE user_id = :u', ['u' => $userId]);
}

/**
 * Close the account for good: personal data and pictures are removed, the sign-in email is released,
 * payment records stay for accounting. The account cannot be used again.
 */
function explorer_delete_account(int $userId): void
{
    $files = [];
    foreach (rows('SELECT large_path, medium_path, thumb_path, blur_path FROM user_photos WHERE user_id = :u', ['u' => $userId]) as $p) {
        foreach ($p as $path) {
            $files[] = $path;
        }
    }
    $private = [];
    foreach (rows('SELECT selfie_path, id_document_path FROM verification_requests WHERE user_id = :u', ['u' => $userId]) as $v) {
        foreach ($v as $path) {
            if ($path) {
                $private[] = $path;
            }
        }
    }
    db_transaction(function () use ($userId) {
        subscription_cancel($userId);
        foreach (['user_photos' => 'user_id', 'user_videos' => 'user_id', 'user_interests' => 'user_id', 'user_locations' => 'user_id', 'favorites' => 'user_id',
                  'search_history' => 'user_id', 'algorithm_scores' => 'user_id', 'daily_picks' => 'user_id', 'verification_requests' => 'user_id',
                  'notifications' => 'user_id', 'user_tokens' => 'user_id', 'discovery_preferences' => 'user_id'] as $table => $colName) {
            q('DELETE FROM ' . db_ident($table) . ' WHERE ' . db_ident($colName) . ' = :u', ['u' => $userId]);
        }
        q('DELETE FROM swipes WHERE swiper_id = :u OR swiped_id = :u', ['u' => $userId]);
        q('DELETE FROM favorites WHERE target_id = :u', ['u' => $userId]);
        q('DELETE FROM profile_views WHERE viewer_id = :u OR viewed_id = :u', ['u' => $userId]);
        q('DELETE FROM algorithm_scores WHERE target_id = :u', ['u' => $userId]);
        q('DELETE FROM daily_picks WHERE picked_user_id = :u', ['u' => $userId]);
        q('UPDATE matches SET is_active = 0, unmatched_by = :u, unmatched_at = NOW() WHERE (user1_id = :u OR user2_id = :u) AND is_active = 1', ['u' => $userId]);
        q("UPDATE user_profiles SET display_name = 'Deleted member', date_of_birth = NULL, gender = NULL, bio = NULL, occupation = NULL, education = NULL,
              height_cm = NULL, languages = NULL, looking_for = NULL, intent = NULL, contact_phone = NULL, contact_whatsapp = NULL, contact_email = NULL,
              contact_instagram = NULL, is_paused = 1, onboarding_completed = 0, profile_completion = 0, is_verified = 0 WHERE user_id = :u", ['u' => $userId]);
        q("UPDATE users SET email = :e, phone = NULL, password_hash = :h, status = 'deactivated', status_reason = 'Account closed by the member',
              totp_enabled = 0, totp_secret = NULL, last_ip = NULL, featured_until = NULL WHERE id = :u",
            ['e' => 'deleted+' . $userId . '.' . random_token(4) . '@deleted.invalid', 'h' => hash_password(random_token(16)), 'u' => $userId]);
    });
    foreach ($files as $f) {
        upload_delete($f);
    }
    $root = verification_private_root();
    foreach ($private as $rel) {
        if (strpos($rel, '..') === false) {
            @unlink($root . '/' . $rel);
        }
    }
}
