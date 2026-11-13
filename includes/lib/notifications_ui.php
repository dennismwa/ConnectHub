<?php
/**
 * Notification display helpers: icon and colour per type, safe links, listing and grouping.
 * Creating notifications is notify() in includes/notify.php.
 */

declare(strict_types=1);

/** Icon and colour tone for a notification type. Unknown types fall back to a plain bell. */
function notification_type_meta(string $type): array
{
    static $map = [
        'like' => ['heart', 'rose', 'Like'], 'superlike' => ['star', 'amber', 'Super like'], 'match' => ['sparkles', 'primary', 'Match'],
        'message' => ['message-circle', 'info', 'Message'], 'visit' => ['eye', 'violet', 'Profile visit'], 'gift' => ['gift', 'rose', 'Gift'],
        'booking' => ['calendar-check', 'info', 'Booking'], 'booking_update' => ['calendar-clock', 'info', 'Booking update'],
        'payment' => ['wallet', 'success', 'Payment'], 'verification' => ['badge-check', 'info', 'Verification'],
        'system' => ['bell', 'neutral', 'Update'], 'host_status' => ['concierge-bell', 'violet', 'Host account'],
        'review' => ['star', 'amber', 'Review'], 'elite' => ['gem', 'amber', 'Elite'], 'moderation' => ['shield-alert', 'warn', 'Safety'],
    ];
    return $map[$type] ?? ['bell', 'neutral', 'Update'];
}

/** A stored link, but only when it points inside this site. */
function notification_safe_url(?string $u): string
{
    $u = trim((string)$u);
    if ($u === '' || $u[0] !== '/' || strpos($u, '//') === 0 || strpos($u, '\\') !== false || preg_match('/[\x00-\x1F]/', $u)) {
        return '';
    }
    return $u;
}

/** Types whose sender is already public to the recipient, so their photo may be shown. */
function notification_shows_sender(string $type): bool
{
    return in_array($type, ['message', 'match', 'gift'], true);
}

function notifications_count(int $userId, bool $unreadOnly = false): int
{
    return (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u' . ($unreadOnly ? ' AND is_read = 0' : ''), ['u' => $userId], 0);
}

/** Notifications for a member, newest first, with the sender's photo for the types that allow it. */
function notifications_list(int $userId, bool $unreadOnly = false, int $limit = 20, int $offset = 0, int $afterId = 0): array
{
    $where = 'n.user_id = :u' . ($unreadOnly ? ' AND n.is_read = 0' : '') . ($afterId > 0 ? ' AND n.id > :after' : '');
    $params = ['u' => $userId] + ($afterId > 0 ? ['after' => $afterId] : []);
    $rows = rows(
        'SELECT n.*, ' . SQL_PHOTO_COLS . ' FROM notifications n LEFT JOIN users su ON su.id = n.sender_id ' . sql_photo_join('su.id', 'ph') . "
         WHERE $where ORDER BY n.id DESC LIMIT " . max(1, min(100, $limit)) . ' OFFSET ' . max(0, $offset),
        $params
    );
    foreach ($rows as &$r) {
        $r['photo'] = notification_shows_sender((string)$r['type']) && $r['sender_id'] ? photo_from_row($r) : null;
        $r['safe_url'] = notification_safe_url($r['action_url'] ?? '');
        $r['meta'] = notification_type_meta((string)$r['type']);
    }
    return $rows;
}

/** "Today", "Yesterday" or "Earlier" for grouping. */
function notification_group(string $createdAt): string
{
    $ts = strtotime($createdAt) ?: 0;
    $today = strtotime('today');
    return $ts >= $today ? 'Today' : ($ts >= $today - 86400 ? 'Yesterday' : 'Earlier');
}
