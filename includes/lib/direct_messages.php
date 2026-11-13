<?php
/**
 * Paid direct messages. A member who cannot message someone yet (no match, or the site needs Premium to start chats) can
 * spend one message credit to open the conversation. One credit opens one person; it allows a few messages until the other
 * person replies, then the conversation is open both ways. People can switch this off for themselves in Settings.
 */

declare(strict_types=1);

function dm_enabled(): bool
{
    return (bool)setting('dm_paid_enabled') && (bool)setting('messaging_enabled');
}

function dm_packs(bool $activeOnly = true): array
{
    return rows('SELECT * FROM dm_packs' . ($activeOnly ? ' WHERE is_active = 1 AND price > 0' : '') . ' ORDER BY sort_order, price, id');
}

function dm_balance(int $userId): int
{
    return (int)val('SELECT COALESCE(SUM(delta), 0) FROM dm_ledger WHERE user_id = :u', ['u' => $userId], 0);
}

function dm_ledger_add(int $userId, int $delta, string $kind, ?int $refId = null, string $note = ''): void
{
    insert('dm_ledger', ['user_id' => $userId, 'delta' => $delta, 'kind' => $kind, 'ref_id' => $refId, 'note' => $note !== '' ? mb_substr($note, 0, 160) : null]);
}

/** Add credits (a purchase, a plan, a welcome gift, an admin gift). */
function dm_add_credits(int $userId, int $credits, string $kind, ?int $refId = null, string $note = ''): void
{
    if ($credits > 0) {
        dm_ledger_add($userId, $credits, $kind, $refId, $note);
    }
}

function dm_unlock_get(int $me, int $targetId): ?array
{
    return row('SELECT * FROM dm_unlocks WHERE user_id = :u AND target_id = :t', ['u' => $me, 't' => $targetId]);
}

/**
 * Decide what a member may do about a person they cannot message in the normal way.
 * Returns null when paid messages do not apply (switched off, or the person opted out), otherwise a messaging_access() result:
 * allowed with 'dm' => 'new' (a credit is spent with the first message) or 'unlocked', or denied with code paid / dm_limit / dm_daily.
 */
function dm_access(int $me, int $targetId, array $target): ?array
{
    if (!dm_enabled() || !(int)setting('dm_paid_enabled')) {
        return null;
    }
    if ($target['role'] === 'host' && !(int)setting('dm_to_hosts')) {
        return null;
    }
    if ($target['role'] !== 'user' && $target['role'] !== 'host') {
        return null;
    }
    $optIn = (int)val('SELECT allow_paid_dm FROM user_profiles WHERE user_id = :u', ['u' => $targetId], 1);
    if (!$optIn) {
        return null;
    }
    $deny = fn(string $code, string $msg, ?string $cta = null) => ['allowed' => false, 'code' => $code, 'message' => $msg, 'cta' => $cta, 'dm' => $code];
    $unlock = dm_unlock_get($me, $targetId);
    if ($unlock) {
        [$a, $b] = pair_ids($me, $targetId);
        $convId = (int)val('SELECT id FROM conversations WHERE user1_id = :a AND user2_id = :b', ['a' => $a, 'b' => $b], 0);
        $theyReplied = $convId > 0 && (int)val("SELECT COUNT(*) FROM messages WHERE conversation_id = :c AND sender_id = :t AND type <> 'system' AND is_deleted = 0", ['c' => $convId, 't' => $targetId], 0) > 0;
        $sent = $convId > 0 ? (int)val("SELECT COUNT(*) FROM messages WHERE conversation_id = :c AND sender_id = :m AND type <> 'system'", ['c' => $convId, 'm' => $me], 0) : 0;
        $limit = max(1, (int)setting('dm_messages_per_unlock'));
        if ($theyReplied || $sent < $limit) {
            return ['allowed' => true, 'code' => 'ok', 'message' => '', 'cta' => null, 'dm' => 'unlocked', 'dm_left' => $theyReplied ? -1 : $limit - $sent];
        }
        return $deny('dm_limit', 'You have sent ' . plural($limit, 'message') . '. You can write again once they reply.');
    }
    $daily = (int)setting('dm_daily_unlocks');
    if ($daily > 0 && (int)val('SELECT COUNT(*) FROM dm_unlocks WHERE user_id = :u AND created_at >= CURDATE()', ['u' => $me], 0) >= $daily) {
        return $deny('dm_daily', 'You have started ' . plural($daily, 'new conversation') . ' today. Try again tomorrow.');
    }
    $credits = dm_balance($me);
    if ($credits > 0) {
        return ['allowed' => true, 'code' => 'ok', 'message' => '', 'cta' => null, 'dm' => 'new', 'dm_credits' => $credits];
    }
    return $deny('paid', 'Message them directly with a message credit.', url('/messages/credits', ['to' => $targetId]));
}

/**
 * Spend one credit to open a conversation. Call inside the transaction that stores the first message.
 * Returns false when the member has no credit left (another tab may have used it).
 */
function dm_unlock_spend(int $me, int $targetId, int $convId): bool
{
    q('SELECT id FROM users WHERE id = :u FOR UPDATE', ['u' => $me]);
    if (dm_unlock_get($me, $targetId)) {
        return true; // already open, nothing to charge
    }
    if (dm_balance($me) < 1) {
        return false;
    }
    $id = insert('dm_unlocks', ['user_id' => $me, 'target_id' => $targetId, 'conversation_id' => $convId ?: null]);
    dm_ledger_add($me, -1, 'unlock', $id, 'Opened a conversation');
    return true;
}

/** Create the pending payment for a pack. Price and credits come from dm_packs. */
function checkout_dm_pack(int $packId, int $forUserId = 0): array
{
    $u = current_user();
    if (!$u || !dm_enabled()) {
        return ['ok' => false, 'error' => 'Message credits are not available right now.'];
    }
    $pack = row('SELECT * FROM dm_packs WHERE id = :id', ['id' => $packId]);
    if (!$pack || !(int)$pack['is_active'] || (float)$pack['price'] <= 0) {
        return ['ok' => false, 'error' => 'That pack is not available right now.'];
    }
    $price = (float)$pack['price'];
    $match = ['pack_id' => (int)$pack['id'], 'credits' => (int)$pack['credits']];
    $meta = $match + ['pack_name' => $pack['name'], 'return_url' => $forUserId > 0 ? '/messages/with/' . $forUserId : '/messages/credits'];
    $existing = payment_find_reusable((int)$u['id'], 'dm_pack', $price, $match);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $p = payment_create((int)$u['id'], 'dm_pack', payments_default_method(), $price, $pack['name'] . ': ' . plural((int)$pack['credits'], 'message credit'), $meta);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}
