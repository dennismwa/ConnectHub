<?php
/**
 * In-app notifications (+ optional e-mail) and header badge counts.
 */

declare(strict_types=1);

const NOTIFY_PREF_MAP = [
    'match' => 'matches', 'message' => 'messages', 'like' => 'likes', 'superlike' => 'likes',
    'visit' => 'visits', 'gift' => 'gifts',
];

/**
 * Create a notification for a user.
 * Types: match, message, like, superlike, visit, gift, payment, booking, system, verification, moderation
 */
function notify(int $userId, string $type, string $title, string $body = '', string $url = '', ?int $senderId = null, array $data = []): void
{
    try {
        $prefKey = NOTIFY_PREF_MAP[$type] ?? null;
        $prefs = row('SELECT * FROM notification_preferences WHERE user_id = :u', ['u' => $userId]);
        if ($prefKey && $prefs && isset($prefs['inapp_' . $prefKey]) && !(int)$prefs['inapp_' . $prefKey]) {
            return;
        }
        // Collapse repeated "message" notifications from the same sender into one unread row.
        if ($type === 'message' && $senderId) {
            $existing = val('SELECT id FROM notifications WHERE user_id = :u AND type = \'message\' AND sender_id = :s AND is_read = 0 ORDER BY id DESC LIMIT 1', ['u' => $userId, 's' => $senderId]);
            if ($existing) {
                q('UPDATE notifications SET body = :b, created_at = NOW() WHERE id = :id', ['b' => mb_substr($body, 0, 400), 'id' => $existing]);
                return;
            }
        }
        insert('notifications', [
            'user_id' => $userId, 'type' => mb_substr($type, 0, 30), 'title' => mb_substr($title, 0, 160),
            'body' => $body !== '' ? mb_substr($body, 0, 400) : null, 'action_url' => $url !== '' ? mb_substr($url, 0, 255) : null,
            'sender_id' => $senderId, 'data' => $data ? json_encode($data) : null,
        ]);
        notify_email_maybe($userId, $type, $title, $body, $url, $prefs);
    } catch (Throwable $e) {
        app_log('error', 'notify failed: ' . $e->getMessage());
    }
}

function notify_email_maybe(int $userId, string $type, string $title, string $body, string $url, ?array $prefs): void
{
    $settingKey = ['match' => 'email_on_match', 'message' => 'email_on_message', 'like' => 'email_on_like', 'superlike' => 'email_on_like', 'gift' => 'email_on_gift'][$type] ?? null;
    if (!$settingKey || !setting($settingKey)) {
        return;
    }
    $prefMap = ['match' => 'email_matches', 'message' => 'email_messages', 'like' => 'email_likes', 'superlike' => 'email_likes', 'gift' => 'email_news'];
    if ($prefs && isset($prefMap[$type]) && !(int)($prefs[$prefMap[$type]] ?? 0)) {
        return;
    }
    $throttle = max(0, (int)setting('email_throttle_minutes'));
    if ($throttle > 0 && !rate_limit('notifmail:' . $userId . ':' . $type, 1, $throttle * 60)) {
        return;
    }
    $u = row('SELECT u.email, p.display_name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id AND u.status = \'active\'', ['id' => $userId]);
    if (!$u) {
        return;
    }
    send_template_mail($u['email'], $title, 'notification', [
        'name' => $u['display_name'] ?: 'there', 'title' => $title, 'body' => $body, 'url' => $url !== '' ? abs_url($url) : abs_url('/notifications'),
    ]);
}

/** Counts shown on the nav bars. Cached for the request. */
function badge_counts(): array
{
    static $c = null;
    if ($c !== null) {
        return $c;
    }
    $uid = auth_id();
    $c = ['notifications' => 0, 'messages' => 0, 'matches' => 0, 'likes' => 0];
    if (!$uid) {
        return $c;
    }
    try {
        $c['notifications'] = (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0', ['u' => $uid], 0);
        $c['messages'] = (int)val('SELECT COALESCE(SUM(IF(user1_id = :u, user1_unread, user2_unread)), 0) FROM conversations WHERE (user1_id = :u OR user2_id = :u)', ['u' => $uid], 0);
        $c['matches'] = (int)val('SELECT COUNT(*) FROM matches WHERE is_active = 1 AND ((user1_id = :u AND user1_seen = 0) OR (user2_id = :u AND user2_seen = 0))', ['u' => $uid], 0);
        $c['likes'] = (int)val('SELECT COUNT(*) FROM swipes s WHERE s.swiped_id = :u AND s.direction IN (\'like\',\'superlike\')
            AND NOT EXISTS (SELECT 1 FROM swipes s2 WHERE s2.swiper_id = :u AND s2.swiped_id = s.swiper_id)
            AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.blocker_id = :u AND b.blocked_id = s.swiper_id) OR (b.blocker_id = s.swiper_id AND b.blocked_id = :u))', ['u' => $uid], 0);
    } catch (Throwable $e) {
    }
    return $c;
}

function notifications_mark_read(int $userId, ?int $id = null): void
{
    if ($id) {
        q('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :u AND id = :id AND is_read = 0', ['u' => $userId, 'id' => $id]);
    } else {
        q('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :u AND is_read = 0', ['u' => $userId]);
    }
}
