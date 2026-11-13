<?php
/**
 * Gift wallet. A member claims a gift into their wallet (a share of the gift price), then asks for a payout to a phone
 * number they choose. Money moves by hand: an admin sends it and records the M-Pesa code.
 *
 * Balance = sum of wallet_transactions. Claiming and requesting a payout lock the member's row first, so two taps at once
 * can never credit a gift twice or pay out more than the balance.
 */

declare(strict_types=1);

function wallet_enabled(): bool
{
    return (bool)setting('wallet_enabled') && (bool)setting('gifts_enabled');
}

/** Share of a gift's price that goes to the person who receives it. */
function wallet_share(float $price): float
{
    $pct = max(0.0, min(100.0, (float)setting('gift_receiver_percent')));
    return round($price * $pct / 100, 2);
}

function wallet_balance(int $userId): float
{
    return round((float)val('SELECT COALESCE(SUM(amount), 0) FROM wallet_transactions WHERE user_id = :u', ['u' => $userId], 0), 2);
}

/** A gift can be claimed once it is delivered, unclaimed and (when the admin wants to check first) verified. */
function wallet_gift_state(array $g): string
{
    if (!empty($g['claimed_at'])) {
        return 'claimed';
    }
    if (($g['status'] ?? '') !== 'delivered') {
        return 'pending';
    }
    if ((string)setting('gift_claim_mode') === 'verified' && empty($g['verified_at'])) {
        return 'checking';
    }
    return 'claimable';
}

/** Gifts waiting to be claimed (and ones being checked), with what they would add to the wallet. */
function wallet_unclaimed(int $userId): array
{
    $list = rows(
        "SELECT g.id, g.claimed_at, g.verified_at, g.status, g.created_at, t.name AS gift_name, t.emoji, t.price, p.amount AS price_paid
         FROM sent_gifts g JOIN gift_types t ON t.id = g.gift_type_id
         LEFT JOIN payments p ON p.id = g.payment_id
         WHERE g.receiver_id = :u AND g.status = 'delivered' AND g.claimed_at IS NULL ORDER BY g.id DESC",
        ['u' => $userId]
    );
    foreach ($list as &$g) {
        $g['state'] = wallet_gift_state($g);
        $g['value'] = wallet_share((float)($g['price_paid'] ?? $g['price']));
    }
    unset($g);
    return $list;
}

/** Credit one gift to the wallet. Returns [ok, message, amount]. */
function wallet_claim_gift(int $userId, int $giftId): array
{
    if (!wallet_enabled()) {
        return [false, 'The wallet is switched off for now.', 0.0];
    }
    return db_transaction(function () use ($userId, $giftId) {
        q('SELECT id FROM users WHERE id = :u FOR UPDATE', ['u' => $userId]);
        $g = row("SELECT g.*, t.name AS gift_name, p.amount AS price_paid, t.price AS list_price
                  FROM sent_gifts g JOIN gift_types t ON t.id = g.gift_type_id LEFT JOIN payments p ON p.id = g.payment_id
                  WHERE g.id = :id AND g.receiver_id = :u FOR UPDATE", ['id' => $giftId, 'u' => $userId]);
        if (!$g) {
            return [false, 'That gift is not yours to claim.', 0.0];
        }
        $state = wallet_gift_state($g);
        if ($state === 'claimed') {
            return [false, 'You already claimed this gift.', 0.0];
        }
        if ($state === 'checking') {
            return [false, 'We are still checking this gift. It will be ready to claim shortly.', 0.0];
        }
        if ($state !== 'claimable') {
            return [false, 'This gift is not ready yet.', 0.0];
        }
        $amount = wallet_share((float)($g['price_paid'] ?? $g['list_price']));
        q('UPDATE sent_gifts SET claimed_at = NOW(), wallet_amount = :a WHERE id = :id AND claimed_at IS NULL', ['a' => $amount, 'id' => $giftId]);
        insert('wallet_transactions', ['user_id' => $userId, 'kind' => 'gift', 'amount' => $amount, 'ref_type' => 'gift', 'ref_id' => $giftId, 'note' => 'Claimed ' . $g['gift_name']]);
        return [true, $amount > 0 ? money($amount) . ' added to your wallet.' : 'Gift claimed.', $amount];
    });
}

/** Claim everything that is ready. Returns [count, total]. */
function wallet_claim_all(int $userId): array
{
    $n = 0;
    $total = 0.0;
    foreach (wallet_unclaimed($userId) as $g) {
        if ($g['state'] !== 'claimable') {
            continue;
        }
        [$ok, , $amt] = wallet_claim_gift($userId, (int)$g['id']);
        if ($ok) {
            $n++;
            $total += $amt;
        }
    }
    return [$n, round($total, 2)];
}

// ---- Numbers the member can be paid on -------------------------------------------

function wallet_accounts(int $userId): array
{
    return rows('SELECT id, label, phone, account_name FROM wallet_accounts WHERE user_id = :u ORDER BY id', ['u' => $userId]);
}

/** Save a payout number. Returns [ok, message, id]. */
function wallet_account_save(int $userId, string $phone, string $name, string $label = ''): array
{
    $norm = phone_normalize($phone);
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
    if ($norm === '') {
        return [false, 'Enter a valid M-Pesa phone number, for example 0712 345 678.', 0];
    }
    if (mb_strlen($name) < 3 || mb_strlen($name) > 80 || !preg_match('/^[\p{L}][\p{L} .\'-]+$/u', $name)) {
        return [false, 'Enter the name registered on that M-Pesa number.', 0];
    }
    $existing = (int)val('SELECT id FROM wallet_accounts WHERE user_id = :u AND phone = :p', ['u' => $userId, 'p' => $norm], 0);
    if ($existing) {
        update('wallet_accounts', ['account_name' => $name, 'label' => $label !== '' ? mb_substr($label, 0, 40) : null], ['id' => $existing]);
        return [true, 'Number updated.', $existing];
    }
    $max = max(1, (int)setting('wallet_max_numbers'));
    if ((int)val('SELECT COUNT(*) FROM wallet_accounts WHERE user_id = :u', ['u' => $userId], 0) >= $max) {
        return [false, 'You can save up to ' . $max . ' numbers. Remove one first.', 0];
    }
    $id = insert('wallet_accounts', ['user_id' => $userId, 'phone' => $norm, 'account_name' => $name, 'label' => $label !== '' ? mb_substr($label, 0, 40) : null]);
    return [true, 'Number saved.', $id];
}

function wallet_account_delete(int $userId, int $id): bool
{
    return delete_rows('wallet_accounts', ['id' => $id, 'user_id' => $userId]) > 0;
}

// ---- Payouts -----------------------------------------------------------------------

function wallet_payout_min(): float
{
    return max(1.0, (float)setting('wallet_min_payout'));
}

/** Ask for money from the wallet. The amount is held at once so it cannot be asked for twice. Returns [ok, message, id]. */
function wallet_request_payout(int $userId, float $amount, int $accountId): array
{
    if (!wallet_enabled() || !(int)setting('wallet_payouts_enabled')) {
        return [false, 'Payout requests are paused. Your balance is safe and you can ask later.', 0];
    }
    $amount = round($amount, 2);
    $min = wallet_payout_min();
    if ($amount < $min) {
        return [false, 'The smallest payout is ' . money($min) . '.', 0];
    }
    $acc = row('SELECT * FROM wallet_accounts WHERE id = :id AND user_id = :u', ['id' => $accountId, 'u' => $userId]);
    if (!$acc) {
        return [false, 'Choose the number you want to be paid on.', 0];
    }
    $res = db_transaction(function () use ($userId, $amount, $acc) {
        q('SELECT id FROM users WHERE id = :u FOR UPDATE', ['u' => $userId]);
        $open = (int)val("SELECT COUNT(*) FROM wallet_payouts WHERE user_id = :u AND status = 'requested'", ['u' => $userId], 0);
        $maxOpen = max(1, (int)setting('wallet_max_open_payouts'));
        if ($open >= $maxOpen) {
            return [false, 'You already have ' . $open . ' payout' . ($open === 1 ? '' : 's') . ' waiting. Wait until ' . ($open === 1 ? 'it is' : 'they are') . ' paid.', 0];
        }
        $bal = wallet_balance($userId);
        if ($amount > $bal + 0.004) {
            return [false, 'You only have ' . money($bal) . ' in your wallet.', 0];
        }
        $id = insert('wallet_payouts', ['user_id' => $userId, 'amount' => $amount, 'phone' => $acc['phone'], 'account_name' => $acc['account_name']]);
        insert('wallet_transactions', ['user_id' => $userId, 'kind' => 'payout', 'amount' => -$amount, 'ref_type' => 'payout', 'ref_id' => $id, 'note' => 'Payout to ' . mask_phone((string)$acc['phone'])]);
        return [true, 'Payout requested. We will send ' . money($amount) . ' to ' . hosts_phone_local((string)$acc['phone']) . '.', $id];
    });
    if ($res[0]) {
        $who = (string)val('SELECT COALESCE(NULLIF(p.display_name, \'\'), u.email) FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :u', ['u' => $userId], 'A member');
        admin_alert('payout', 'Payout requested: ' . money($amount), $who . ' asked to be paid ' . money($amount) . ' from their gift wallet, to ' . hosts_phone_local((string)$acc['phone']) . ' (' . $acc['account_name'] . ').', '/admin/wallet');
    }
    return $res;
}

/** Admin: record that a payout was sent. Returns [ok, message]. */
function wallet_payout_mark_paid(int $id, string $reference, string $note, int $adminId): array
{
    $po = row('SELECT * FROM wallet_payouts WHERE id = :id', ['id' => $id]);
    if (!$po) {
        return [false, 'That payout request no longer exists.'];
    }
    if ($po['status'] !== 'requested') {
        return [false, 'This request was already ' . $po['status'] . '.'];
    }
    $done = q("UPDATE wallet_payouts SET status = 'paid', payment_reference = :r, admin_note = :n, processed_by = :a, processed_at = NOW() WHERE id = :id AND status = 'requested'",
        ['r' => $reference, 'n' => $note !== '' ? $note : null, 'a' => $adminId ?: null, 'id' => $id])->rowCount();
    if (!$done) {
        return [false, 'This request changed while you were working. Reload and check.'];
    }
    notify((int)$po['user_id'], 'payment', 'Your payout was sent', money($po['amount']) . ' was sent to ' . hosts_phone_local((string)$po['phone']) . '. M-Pesa code ' . $reference . '.', '/wallet');
    return [true, 'Marked as paid. The member was told.'];
}

/** Admin: turn a request down; the held money goes back to the wallet. Returns [ok, message]. */
function wallet_payout_reject(int $id, string $reason, int $adminId): array
{
    return db_transaction(function () use ($id, $reason, $adminId) {
        $po = row('SELECT * FROM wallet_payouts WHERE id = :id FOR UPDATE', ['id' => $id]);
        if (!$po) {
            return [false, 'That payout request no longer exists.'];
        }
        if ($po['status'] !== 'requested') {
            return [false, 'This request was already ' . $po['status'] . '.'];
        }
        q("UPDATE wallet_payouts SET status = 'rejected', admin_note = :n, processed_by = :a, processed_at = NOW() WHERE id = :id", ['n' => $reason, 'a' => $adminId ?: null, 'id' => $id]);
        insert('wallet_transactions', ['user_id' => (int)$po['user_id'], 'kind' => 'refund', 'amount' => (float)$po['amount'], 'ref_type' => 'payout', 'ref_id' => $id, 'note' => 'Payout declined, money returned']);
        notify((int)$po['user_id'], 'payment', 'Your payout request was declined', rtrim($reason, ' .') . '. ' . money($po['amount']) . ' is back in your wallet.', '/wallet');
        return [true, 'Request rejected. The money is back in the wallet and the member was told why.'];
    });
}

/** Admin: add or remove money by hand (a correction). */
function wallet_adjust(int $userId, float $amount, string $note, int $adminId): array
{
    $amount = round($amount, 2);
    if ($amount == 0.0) {
        return [false, 'Enter an amount other than zero.'];
    }
    return db_transaction(function () use ($userId, $amount, $note) {
        q('SELECT id FROM users WHERE id = :u FOR UPDATE', ['u' => $userId]);
        if ($amount < 0 && wallet_balance($userId) + $amount < -0.004) {
            return [false, 'That would take the wallet below zero.'];
        }
        insert('wallet_transactions', ['user_id' => $userId, 'kind' => 'adjust', 'amount' => $amount, 'note' => mb_substr($note, 0, 200) ?: 'Correction by admin']);
        return [true, 'Wallet updated.'];
    });
}

/** Admin: mark a gift as checked (the money really arrived and the gift is genuine). */
function wallet_gift_verify(int $giftId, string $note, int $adminId): array
{
    $g = row('SELECT g.*, p.status AS pay_status FROM sent_gifts g LEFT JOIN payments p ON p.id = g.payment_id WHERE g.id = :id', ['id' => $giftId]);
    if (!$g) {
        return [false, 'That gift no longer exists.'];
    }
    if ($g['status'] !== 'delivered') {
        return [false, 'This gift has not been paid for yet.'];
    }
    if (!empty($g['verified_at'])) {
        return [false, 'This gift was already checked.'];
    }
    q('UPDATE sent_gifts SET verified_at = NOW(), verified_by = :a, verify_note = :n WHERE id = :id AND verified_at IS NULL', ['a' => $adminId ?: null, 'n' => $note !== '' ? mb_substr($note, 0, 255) : null, 'id' => $giftId]);
    if ((string)setting('gift_claim_mode') === 'verified') {
        $name = (string)val('SELECT name FROM gift_types WHERE id = :id', ['id' => $g['gift_type_id']], 'gift');
        notify((int)$g['receiver_id'], 'gift', 'Your ' . $name . ' is ready to claim', 'Open your wallet and add it to your balance.', '/wallet');
    }
    return [true, 'Gift checked.'];
}

function wallet_history(int $userId, int $limit = 40): array
{
    return rows('SELECT id, kind, amount, note, created_at FROM wallet_transactions WHERE user_id = :u ORDER BY id DESC LIMIT ' . max(1, min(200, $limit)), ['u' => $userId]);
}
