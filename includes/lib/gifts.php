<?php
/**
 * Virtual gifts: catalogue, checkout and delivery. payment_complete() calls gift_deliver()
 * once a gift payment has been paid.
 */

declare(strict_types=1);

const GIFT_NOTE_MAX = 200;

/** Active gifts, cheapest first as the admin ordered them. */
function gifts_catalogue(): array
{
    if (!setting('gifts_enabled')) {
        return [];
    }
    return rows('SELECT id, name, icon, emoji, price FROM gift_types WHERE is_active = 1 ORDER BY sort_order, price, id');
}

function gift_type(int $id): ?array
{
    return row('SELECT id, name, icon, emoji, price, is_active FROM gift_types WHERE id = :id', ['id' => $id]);
}

/** Can the signed-in member send a gift to this person? Returns [bool, reason]. */
function gifts_recipient_ok(int $toId): array
{
    $me = auth_id();
    if ($toId <= 0 || $toId === $me) {
        return [false, 'Choose someone else to send a gift to.'];
    }
    $t = row('SELECT id, role, status FROM users WHERE id = :id', ['id' => $toId]);
    if (!$t || $t['status'] !== 'active' || $t['role'] === 'admin') {
        return [false, 'This member is not available.'];
    }
    if (users_blocked($me, $toId)) {
        return [false, 'You cannot send a gift to this member.'];
    }
    return [true, ''];
}

/** One member as a gift recipient (name, first name, photo, profile link), or null when they cannot receive gifts. */
function gift_recipient(int $id): ?array
{
    [$ok] = gifts_recipient_ok($id);
    if (!$ok) {
        return null;
    }
    $r = row(
        "SELECT u.id, u.role, p.display_name, hp.display_name AS host_name, " . SQL_PHOTO_COLS . "
         FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id LEFT JOIN host_profiles hp ON hp.user_id = u.id " . sql_photo_join('u.id', 'ph') . "
         WHERE u.id = :id",
        ['id' => $id]
    );
    if (!$r) {
        return null;
    }
    $name = trim((string)($r['role'] === 'host' && !empty($r['host_name']) ? $r['host_name'] : $r['display_name'])) ?: 'Member';
    return ['id' => (int)$r['id'], 'name' => $name, 'first_name' => explode(' ', $name)[0], 'photo' => photo_from_row($r), 'role' => $r['role'], 'href' => ($r['role'] === 'host' ? '/hosts/' : '/u/') . (int)$r['id']];
}

/** People the member is likely to gift: recent chats first, then matches. */
function gifts_suggested_recipients(int $userId, int $limit = 12): array
{
    $ids = col(
        'SELECT other_id FROM (
            SELECT IF(user1_id = :u, user2_id, user1_id) AS other_id, COALESCE(last_message_at, created_at) AS ts FROM conversations WHERE user1_id = :u OR user2_id = :u
            UNION ALL
            SELECT IF(user1_id = :u, user2_id, user1_id), matched_at FROM matches WHERE is_active = 1 AND (user1_id = :u OR user2_id = :u)
         ) x GROUP BY other_id ORDER BY MAX(ts) DESC LIMIT ' . ($limit * 2),
        ['u' => $userId]
    );
    $ids = array_values(array_diff(array_map('intval', $ids), blocked_user_ids($userId)));
    if (!$ids) {
        return [];
    }
    $out = [];
    $rows = rows(
        "SELECT u.id, u.role, p.display_name, hp.display_name AS host_name, " . SQL_PHOTO_COLS . "
         FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id LEFT JOIN host_profiles hp ON hp.user_id = u.id " . sql_photo_join('u.id', 'ph') . "
         WHERE u.id IN (:ids) AND u.status = 'active' AND u.role <> 'admin'",
        ['ids' => $ids]
    );
    $byId = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['role'] === 'host' && !empty($r['host_name']) ? $r['host_name'] : $r['display_name'])) ?: 'Member';
        $byId[(int)$r['id']] = ['id' => (int)$r['id'], 'name' => $name, 'first_name' => explode(' ', $name)[0], 'photo' => photo_from_row($r)];
    }
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $out[] = $byId[$id];
        }
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/**
 * Create the pending payment for a gift. The price comes from the gift catalogue, never from the browser.
 * @return array ['ok'=>bool, 'error'=>string, 'payment'=>array]
 */
function gift_checkout(int $giftTypeId, int $toId, string $note, string $returnUrl): array
{
    $me = auth_id();
    if (!setting('gifts_enabled')) {
        return ['ok' => false, 'error' => 'Gifts are not available right now.'];
    }
    $g = gift_type($giftTypeId);
    if (!$g || !(int)$g['is_active']) {
        return ['ok' => false, 'error' => 'Choose a gift from the list.'];
    }
    [$ok, $why] = gifts_recipient_ok($toId);
    if (!$ok) {
        return ['ok' => false, 'error' => $why];
    }
    $note = messaging_clean_text($note);
    if (mb_strlen($note) > GIFT_NOTE_MAX) {
        return ['ok' => false, 'error' => 'Keep the note under ' . GIFT_NOTE_MAX . ' characters.'];
    }
    if ($note !== '' && (string)setting('profanity_action') === 'block' && contains_banned_word($note) !== null) {
        return ['ok' => false, 'error' => 'That note contains words that are not allowed here.'];
    }
    if ($note !== '' && (string)setting('profanity_action') === 'mask') {
        $note = mask_banned_words($note);
    }
    $amount = (float)$g['price'];
    if ($amount <= 0) {
        return ['ok' => false, 'error' => 'This gift is not available.'];
    }
    $to = row('SELECT u.id, u.role, p.display_name, hp.display_name AS host_name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id LEFT JOIN host_profiles hp ON hp.user_id = u.id WHERE u.id = :id', ['id' => $toId]);
    $toName = trim((string)($to['role'] === 'host' && !empty($to['host_name']) ? $to['host_name'] : $to['display_name'])) ?: 'Member';
    $meta = ['gift_type_id' => (int)$g['id'], 'to' => $toId, 'message' => $note, 'gift_name' => $g['name'], 'to_name' => explode(' ', $toName)[0], 'return_url' => safe_next($returnUrl, '/gifts?tab=sent')];
    // A double click should not create two orders for the same gift.
    $existing = payment_find_reusable($me, 'gift', $amount, ['gift_type_id' => (int)$g['id'], 'to' => $toId, 'message' => $note]);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $p = payment_create($me, 'gift', payments_default_method(), $amount, 'Gift: ' . $g['name'] . ' for ' . explode(' ', $toName)[0], $meta);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}

/**
 * Called by payment_complete() inside its transaction once a gift payment is paid. Records the gift,
 * tells the recipient and the sender, and drops a line into their conversation when one exists.
 * It never throws: money has already been taken, so a problem here is logged for an admin instead.
 */
function gift_deliver(array $payment, array $meta): void
{
    try {
        $sender = (int)$payment['user_id'];
        $to = (int)($meta['to'] ?? 0);
        $g = gift_type((int)($meta['gift_type_id'] ?? 0));
        if ($to <= 0 || !$g) {
            app_log('payments', 'gift_deliver: missing recipient or gift', ['payment' => $payment['id'] ?? null, 'meta' => $meta]);
            return;
        }
        if ((int)val('SELECT COUNT(*) FROM sent_gifts WHERE payment_id = :p', ['p' => $payment['id']]) > 0) {
            return;
        }
        $note = mb_substr(trim((string)($meta['message'] ?? '')), 0, GIFT_NOTE_MAX);
        $giftId = insert('sent_gifts', [
            'sender_id' => $sender, 'receiver_id' => $to, 'gift_type_id' => (int)$g['id'], 'payment_id' => (int)$payment['id'],
            'message' => $note !== '' ? $note : null, 'status' => 'delivered',
        ]);
        $s = row('SELECT p.display_name FROM user_profiles p WHERE p.user_id = :u', ['u' => $sender]);
        $sName = explode(' ', trim((string)($s['display_name'] ?? '')) ?: 'Someone')[0];
        $emoji = (string)($g['emoji'] ?: '🎁');

        [$a, $b] = pair_ids($sender, $to);
        $convId = (int)val('SELECT id FROM conversations WHERE user1_id = :a AND user2_id = :b', ['a' => $a, 'b' => $b]);
        if ($convId > 0) {
            message_post_system($convId, $sender, ['kind' => 'gift', 'gift' => $g['name'], 'emoji' => $emoji, 'note' => $note, 'gift_id' => $giftId]);
        }
        [$notePreview] = messaging_mask_text($note);
        notify($to, 'gift', $sName . ' sent you a ' . $g['name'] . ' ' . $emoji, $notePreview, $convId > 0 ? '/messages/' . $convId : '/gifts?tab=received', $sender, ['gift_id' => $giftId]);
        $toName = explode(' ', trim((string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $to])) ?: 'them')[0];
        notify($sender, 'payment', 'Your gift is on its way', 'Your ' . $g['name'] . ' ' . $emoji . ' was delivered to ' . $toName . '.', '/gifts?tab=sent');
        $value = money((float)$payment['amount']);
        $ref = trim((string)(($payment['mpesa_receipt'] ?? '') ?: ($payment['bank_reference'] ?? '') ?: ($payment['reference'] ?? '')));
        admin_alert('gift', 'Gift sent: ' . $g['name'] . ' (' . $value . ')', $sName . ' sent a ' . $g['name'] . ' to ' . $toName . '. Paid ' . $value . ' by ' . ($payment['payment_method'] ?? 'unknown') . ($ref !== '' ? ', reference ' . $ref : '') . '. Open Wallet and gifts to check it.', '/admin/wallet?tab=gifts');
    } catch (Throwable $e) {
        app_log('error', 'gift_deliver failed: ' . $e->getMessage(), ['payment' => $payment['id'] ?? null]);
    }
}

/** Gifts a member has sent or received, newest first. $dir is 'sent' or 'received'. */
function gifts_history(int $userId, string $dir, int $limit = 30, int $offset = 0): array
{
    $mine = $dir === 'received' ? 'g.receiver_id' : 'g.sender_id';
    $otherCol = $dir === 'received' ? 'g.sender_id' : 'g.receiver_id';
    return rows(
        "SELECT g.id, g.message, g.status, g.created_at, t.name AS gift_name, t.emoji, t.price, o.id AS other_id, o.role AS other_role, p.display_name, hp.display_name AS host_name
         FROM sent_gifts g JOIN gift_types t ON t.id = g.gift_type_id JOIN users o ON o.id = $otherCol
         LEFT JOIN user_profiles p ON p.user_id = o.id LEFT JOIN host_profiles hp ON hp.user_id = o.id
         WHERE $mine = :u AND g.status = 'delivered' ORDER BY g.id DESC LIMIT " . max(1, min(100, $limit)) . ' OFFSET ' . max(0, $offset),
        ['u' => $userId]
    );
}

function gifts_count(int $userId, string $dir): int
{
    $col = $dir === 'received' ? 'receiver_id' : 'sender_id';
    return (int)val("SELECT COUNT(*) FROM sent_gifts WHERE $col = :u AND status = 'delivered'", ['u' => $userId]);
}
