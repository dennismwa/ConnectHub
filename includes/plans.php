<?php
/**
 * Plans, tiers, feature limits, daily usage, boosts and payment fulfilment.
 *
 * Tiers: free (0), premium (1), vip (2). A plan's features JSON holds numeric
 * limits (-1 = unlimited) and booleans, for example:
 *   {"daily_swipes":25,"daily_superlikes":0,"daily_messages":10,"rewind":false,
 *    "see_who_liked":false,"advanced_filters":false,"incognito":false,
 *    "read_receipts":false,"message_before_match":false,"max_photos":6,
 *    "boost_credits_monthly":0,"profile_visitors":false,"no_ads":false}
 */

declare(strict_types=1);

const TIER_NAMES = [0 => 'free', 1 => 'premium', 2 => 'vip'];
const TIER_RANKS = ['guest' => -1, 'free' => 0, 'premium' => 1, 'vip' => 2];

function tier_rank(string $tier): int
{
    return TIER_RANKS[$tier] ?? 0;
}

function tier_label(string $tier): string
{
    return ['guest' => 'Visitor', 'free' => 'Free', 'premium' => 'Premium', 'vip' => 'VIP'][$tier] ?? ucfirst($tier);
}

/** Active subscription row (with plan columns) or null. Cached for the request. */
function user_subscription(int $userId): ?array
{
    if (array_key_exists($userId, $GLOBALS['__sub_cache'] ?? [])) {
        return $GLOBALS['__sub_cache'][$userId];
    }
    $r = row(
        'SELECT s.*, p.slug AS plan_slug, p.name AS plan_name, p.tier AS plan_tier, p.features AS plan_features, p.badge_color, p.badge_icon
         FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
         WHERE s.user_id = :u AND s.status = \'active\' AND s.expires_at > NOW()
         ORDER BY p.tier DESC, s.expires_at DESC LIMIT 1',
        ['u' => $userId]
    );
    return $GLOBALS['__sub_cache'][$userId] = $r;
}

/** Forget cached plan data for a user (call after a subscription changes). */
function plans_forget(int $userId): void
{
    unset($GLOBALS['__sub_cache'][$userId], $GLOBALS['__tier_cache'][$userId], $GLOBALS['__feat_cache'][$userId]);
}

function user_tier(int $userId): string
{
    if (isset($GLOBALS['__tier_cache'][$userId])) {
        return $GLOBALS['__tier_cache'][$userId];
    }
    $s = user_subscription($userId);
    $tier = $s ? (TIER_NAMES[(int)$s['plan_tier']] ?? 'free') : 'free';
    return $GLOBALS['__tier_cache'][$userId] = $tier;
}

/** Tier of whoever is looking: guest, free, premium or vip. */
function viewer_tier(): string
{
    $u = current_user();
    return $u ? $u['tier'] : 'guest';
}

function is_premium(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user !== null && tier_rank($user['tier']) >= 1;
}

function is_vip(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user !== null && tier_rank($user['tier']) >= 2;
}

function plan_free(): array
{
    static $p = null;
    if ($p === null) {
        $p = row('SELECT * FROM subscription_plans WHERE tier = 0 ORDER BY sort_order, id LIMIT 1') ?: ['features' => '{}', 'name' => 'Free', 'slug' => 'free', 'tier' => 0];
    }
    return $p;
}

function plans_active(): array
{
    return rows('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order, tier, id');
}

function plan_features_decoded(array $plan): array
{
    $d = json_decode((string)($plan['features'] ?? '{}'), true);
    return is_array($d) ? $d : [];
}

/** All features for a user's current plan. */
function user_features(?int $userId = null): array
{
    $userId = $userId ?? auth_id();
    if (!$userId) {
        return plan_features_decoded(plan_free());
    }
    if (isset($GLOBALS['__feat_cache'][$userId])) {
        return $GLOBALS['__feat_cache'][$userId];
    }
    $u = current_user();
    if ($u && $u['id'] === $userId && $u['role'] === 'admin') {
        $top = row('SELECT features FROM subscription_plans ORDER BY tier DESC, id DESC LIMIT 1');
        return $GLOBALS['__feat_cache'][$userId] = plan_features_decoded($top ?: ['features' => '{}']);
    }
    $s = user_subscription($userId);
    $f = $s ? (json_decode((string)$s['plan_features'], true) ?: []) : plan_features_decoded(plan_free());
    // A live launch-access grant temporarily overrides plan limits. The underlying subscription is untouched.
    if (function_exists('launch_access_features_for_user')) {
        $grant = launch_access_features_for_user($userId);
        if ($grant) { $f = array_replace($f, $grant); }
    }
    return $GLOBALS['__feat_cache'][$userId] = $f;
}

/** One feature value: bool for flags, int for limits (-1 = unlimited). */
function feature(string $key, $default = false, ?int $userId = null)
{
    $f = user_features($userId);
    return $f[$key] ?? $default;
}

function can(string $key, ?int $userId = null): bool
{
    $v = feature($key, false, $userId);
    return is_numeric($v) ? ((int)$v !== 0) : (bool)$v;
}

// ---------------------------------------------------------------------------
// Daily usage (swipes, superlikes, messages, rewinds)
// ---------------------------------------------------------------------------

const USAGE_FIELDS = ['swipes' => 'swipes_used', 'superlikes' => 'superlikes_used', 'messages' => 'messages_sent', 'rewinds' => 'rewinds_used'];

function usage_today(int $userId): array
{
    $r = row('SELECT * FROM daily_usage WHERE user_id = :u AND usage_date = CURDATE()', ['u' => $userId]);
    return $r ?: ['swipes_used' => 0, 'superlikes_used' => 0, 'messages_sent' => 0, 'rewinds_used' => 0];
}

/** Remaining allowance today. Returns -1 for unlimited. */
function usage_remaining(int $userId, string $what): int
{
    $limitKey = ['swipes' => 'daily_swipes', 'superlikes' => 'daily_superlikes', 'messages' => 'daily_messages', 'rewinds' => 'daily_rewinds'][$what] ?? null;
    if ($limitKey === null) {
        return 0;
    }
    $limit = (int)feature($limitKey, 0, $userId);
    if ($limit < 0) {
        return -1;
    }
    $used = (int)(usage_today($userId)[USAGE_FIELDS[$what]] ?? 0);
    return max(0, $limit - $used);
}

function usage_add(int $userId, string $what, int $n = 1): void
{
    $col = USAGE_FIELDS[$what] ?? null;
    if (!$col) {
        return;
    }
    q("INSERT INTO daily_usage (user_id, usage_date, $col) VALUES (:u, CURDATE(), :n)
       ON DUPLICATE KEY UPDATE $col = $col + VALUES($col)", ['u' => $userId, 'n' => $n]);
}

// ---------------------------------------------------------------------------
// Boost & featured
// ---------------------------------------------------------------------------

function user_is_boosted(int $userId): bool
{
    return (int)val('SELECT COUNT(*) FROM boosts WHERE user_id = :u AND expires_at > NOW()', ['u' => $userId]) > 0;
}

function user_boost_expiry(int $userId): ?string
{
    return val('SELECT MAX(expires_at) FROM boosts WHERE user_id = :u AND expires_at > NOW()', ['u' => $userId]);
}

function boost_credits_available(int $userId): int
{
    return (int)val('SELECT COUNT(*) FROM boost_credits WHERE user_id = :u AND used_at IS NULL', ['u' => $userId]);
}

/** Start (or extend) a boost for the configured number of minutes. */
function boost_start(int $userId, string $source = 'purchase', ?int $paymentId = null, ?int $minutes = null): string
{
    $minutes = $minutes ?? boost_default_minutes();
    $current = user_boost_expiry($userId);
    $from = $current ? strtotime($current) : time();
    $expires = date('Y-m-d H:i:s', $from + $minutes * 60);
    insert('boosts', ['user_id' => $userId, 'payment_id' => $paymentId, 'source' => $source, 'expires_at' => $expires]);
    return $expires;
}

/** Spend one stored credit to boost now. */
function boost_use_credit(int $userId): ?string
{
    if (!boost_can_start($userId)[0]) {
        return null;
    }
    return db_transaction(function () use ($userId) {
        $id = val('SELECT id FROM boost_credits WHERE user_id = :u AND used_at IS NULL ORDER BY id LIMIT 1 FOR UPDATE', ['u' => $userId]);
        if (!$id) {
            return null;
        }
        q('UPDATE boost_credits SET used_at = NOW() WHERE id = :id', ['id' => $id]);
        return boost_start($userId, 'plan');
    });
}

function is_featured_user(array $row): bool
{
    return !empty($row['featured_until']) && strtotime((string)$row['featured_until']) > time();
}

// ---------------------------------------------------------------------------
// Subscriptions
// ---------------------------------------------------------------------------

/**
 * What happens to the days left on a different paid plan when a member buys $planId for $period. Their value is turned
 * into extra days of the new plan (days left x the old plan's price per day / the new plan's price per day), so
 * switching never throws away time that was paid for. Renewing the same plan needs no conversion (the days are simply added).
 * Prices are today's plan prices; the old plan's price per day uses the period it was bought for.
 * @return array ['days' => extra days of the new plan, 'left' => whole days left on the old plan, 'from' => old plan name]
 */
function subscription_switch_credit(int $userId, int $planId, string $period): array
{
    $none = ['days' => 0, 'left' => 0, 'from' => ''];
    if (!in_array($period, ['weekly', 'monthly', 'yearly'], true)) {
        return $none;
    }
    $new = row('SELECT * FROM subscription_plans WHERE id = :id', ['id' => $planId]);
    $newPrice = $new ? plan_period_price($new, $period) : 0.0;
    if ($newPrice <= 0) {
        return $none;
    }
    $newDaily = $newPrice / plan_period_days($period);
    $subs = rows("SELECT s.expires_at, s.billing_period, p.name, p.price_weekly, p.price_monthly, p.price_yearly
                  FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
                  WHERE s.user_id = :u AND s.status = 'active' AND s.expires_at > NOW() AND s.plan_id <> :p ORDER BY s.expires_at DESC", ['u' => $userId, 'p' => $planId]);
    $value = 0.0;
    $left = 0;
    $from = '';
    foreach ($subs as $s) {
        $bp = in_array((string)$s['billing_period'], ['weekly', 'monthly', 'yearly'], true) ? (string)$s['billing_period'] : 'monthly';
        $oldPrice = plan_period_price($s, $bp);
        $days = max(0.0, (strtotime((string)$s['expires_at']) - time()) / 86400);
        if ($oldPrice <= 0 || $days <= 0) {
            continue;
        }
        $value += $days * $oldPrice / plan_period_days($bp);
        $left = max($left, (int)ceil($days));
        $from = $from !== '' ? $from : (string)$s['name'];
    }
    return ['days' => (int)floor($value / $newDaily), 'left' => $left, 'from' => $from];
}

/** Activate (or extend) a subscription. Returns the expiry datetime. */
function subscription_activate(int $userId, int $planId, string $period = 'monthly', ?int $grantedBy = null, ?int $days = null): string
{
    $plan = row('SELECT * FROM subscription_plans WHERE id = :id', ['id' => $planId]);
    if (!$plan) {
        throw new RuntimeException('Unknown plan');
    }
    $paid = $days === null && $grantedBy === null;
    $days = $days ?? plan_period_days($period);
    $bought = $days; // credits follow the time that was paid for, not days carried over from another plan
    if ($paid) {
        // a paid switch from another plan keeps the value of the days left on it
        $days += subscription_switch_credit($userId, $planId, $period)['days'];
    }
    $existing = row('SELECT * FROM user_subscriptions WHERE user_id = :u AND plan_id = :p AND status = \'active\' AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1', ['u' => $userId, 'p' => $planId]);
    // Switching plans replaces the old subscription; renewing extends it.
    q('UPDATE user_subscriptions SET status = \'cancelled\', cancelled_at = NOW() WHERE user_id = :u AND status = \'active\' AND plan_id <> :p', ['u' => $userId, 'p' => $planId]);
    $start = $existing ? strtotime((string)$existing['expires_at']) : time();
    $expires = date('Y-m-d H:i:s', $start + $days * 86400);
    if ($existing) {
        // the latest period bought is the one shown on the billing page
        q('UPDATE user_subscriptions SET expires_at = :e, cancelled_at = NULL' . (in_array($period, ['weekly', 'monthly', 'yearly'], true) ? ', billing_period = :bp' : '') . ' WHERE id = :id',
            ['e' => $expires, 'id' => $existing['id']] + (in_array($period, ['weekly', 'monthly', 'yearly'], true) ? ['bp' => $period] : []));
    } else {
        insert('user_subscriptions', [
            'user_id' => $userId, 'plan_id' => $planId, 'billing_period' => in_array($period, ['weekly', 'monthly', 'yearly'], true) ? $period : 'custom',
            'status' => 'active', 'starts_at' => now(), 'expires_at' => $expires, 'granted_by' => $grantedBy,
        ]);
    }
    $features = plan_features_decoded($plan);
    // the plan lists credits per month: a week earns a quarter of that, a year twelve times
    $credits = (int)round((int)($features['boost_credits_monthly'] ?? 0) * ($bought / 30));
    for ($i = 0; $i < $credits; $i++) {
        insert('boost_credits', ['user_id' => $userId]);
    }
    $dmCredits = (int)round((int)($features['dm_credits_monthly'] ?? 0) * ($bought / 30));
    if ($dmCredits > 0 && function_exists('dm_add_credits')) {
        dm_add_credits($userId, $dmCredits, 'plan', null, $plan['name'] . ' plan');
    }
    plans_forget($userId);
    return $expires;
}

function subscription_cancel(int $userId): void
{
    q('UPDATE user_subscriptions SET status = \'cancelled\', cancelled_at = NOW() WHERE user_id = :u AND status = \'active\'', ['u' => $userId]);
    plans_forget($userId);
}

// ---------------------------------------------------------------------------
// Payments
// ---------------------------------------------------------------------------

/** Create a pending payment and return its row. */
function payment_create(int $userId, string $type, string $method, float $amount, string $description, array $meta = []): array
{
    $ref = 'CH' . date('ymd') . strtoupper(substr(random_token(4), 0, 6));
    $id = insert('payments', [
        'user_id' => $userId, 'payment_type' => $type, 'payment_method' => $method, 'amount' => $amount,
        'currency' => (string)setting('currency'), 'status' => 'pending', 'reference' => $ref,
        'description' => mb_substr($description, 0, 255), 'metadata' => $meta ? json_encode($meta) : null,
    ]);
    return row('SELECT * FROM payments WHERE id = :id', ['id' => $id]);
}

/**
 * Mark a payment completed and deliver what was bought. Safe to call more than
 * once: fulfilment runs a single time. Returns [bool delivered, string note].
 */
function payment_complete(int $paymentId, array $extra = [], ?int $adminId = null): array
{
    return db_transaction(function () use ($paymentId, $extra, $adminId) {
        $p = row('SELECT * FROM payments WHERE id = :id FOR UPDATE', ['id' => $paymentId]);
        if (!$p) {
            return [false, 'Payment not found'];
        }
        if ($p['fulfilled_at']) {
            return [true, 'Already delivered'];
        }
        $set = ['status' => 'completed', 'processed_at' => now()];
        if ($adminId) {
            $set['processed_by'] = $adminId;
        }
        foreach (['mpesa_receipt', 'mpesa_result_desc', 'mpesa_merchant_id', 'admin_notes', 'bank_reference', 'bank_paid_on'] as $k) {
            if (isset($extra[$k])) {
                $set[$k] = $extra[$k];
            }
        }
        update('payments', $set, ['id' => $paymentId]);
        $meta = json_decode((string)$p['metadata'], true) ?: [];
        $uid = (int)$p['user_id'];
        $note = '';
        switch ($p['payment_type']) {
            case 'subscription':
                $exp = subscription_activate($uid, (int)($meta['plan_id'] ?? 0), (string)($meta['period'] ?? 'monthly'));
                if (function_exists('autorenew_after_payment')) {
                    autorenew_after_payment($p, $meta, $uid);
                }
                $note = 'Subscription active until ' . fmt_date($exp);
                notify($uid, 'payment', 'Your plan is active', 'Thank you. Your plan runs until ' . fmt_date($exp) . '.', '/billing');
                break;
            case 'boost':
                $exp = boost_start($uid, 'purchase', $paymentId, max(5, (int)($meta['minutes'] ?? 0)) ?: null);
                $note = 'Boost running until ' . fmt_datetime($exp);
                notify($uid, 'payment', 'Your boost is live', 'Your profile is shown first until ' . fmt_date($exp, 'g:i a') . '.', '/boost');
                break;
            case 'featured':
                $cur = val('SELECT featured_until FROM users WHERE id = :u', ['u' => $uid]);
                $from = $cur && strtotime((string)$cur) > time() ? strtotime((string)$cur) : time();
                $until = date('Y-m-d H:i:s', $from + max(1, (int)($meta['days'] ?? 7)) * 86400);
                q('UPDATE users SET featured_until = :f WHERE id = :u', ['f' => $until, 'u' => $uid]);
                $note = 'Featured until ' . fmt_date($until);
                notify($uid, 'payment', 'You are featured', 'Your profile is featured until ' . fmt_date($until) . '.', '/billing');
                break;
            case 'gift':
                if (function_exists('gift_deliver')) {
                    gift_deliver(array_merge($p, $extra), $meta);
                }
                $note = 'Gift sent';
                break;
            case 'dm_pack':
                $credits = max(0, (int)($meta['credits'] ?? 0));
                dm_add_credits($uid, $credits, 'purchase', $paymentId, (string)($meta['pack_name'] ?? 'Message credits'));
                $note = plural($credits, 'message credit') . ' added';
                notify($uid, 'payment', 'Message credits added', 'You can now message ' . plural($credits, 'new person', 'new people') . '.', $meta['return_url'] ?? '/messages/credits');
                break;
            case 'intl':
                $days = (int)($meta['days'] ?? 0);
                if (($meta['kind'] ?? '') === 'join') {
                    $until = intl_extend_listing($uid, $days);
                    $note = 'International listing ' . ($until ? 'until ' . fmt_date($until) : 'active');
                    notify($uid, 'payment', 'You are listed internationally', $until ? 'Your listing runs until ' . fmt_date($until) . '.' : 'Your listing has no end date.', '/international');
                } else {
                    $until = intl_grant_access($uid, $days, 'purchase');
                    $note = 'International access ' . ($until ? 'until ' . fmt_date($until) : 'for good');
                    notify($uid, 'payment', 'International access is on', $until ? 'You can meet people abroad until ' . fmt_date($until) . '.' : 'You can meet people abroad, with no end date.', '/international');
                }
                break;
            case 'host_listing':
                $until = hosts_listing_extend($uid, max(1, (int)($meta['days'] ?? 30)), (string)($meta['period'] ?? 'custom'));
                $note = 'Host listing active until ' . fmt_date($until);
                notify($uid, 'payment', 'You are listed', 'Your Host profile is listed until ' . fmt_date($until) . '.', '/host/listing');
                break;
            case 'elite':
                $note = function_exists('elite_fulfil') ? elite_fulfil($p, $meta) : 'Elite';
                break;
            case 'ad':
                $note = function_exists('promo_fulfil') ? promo_fulfil($p, $meta) : 'Advert';
                break;
            case 'booking':
                if (function_exists('booking_payment_completed')) {
                    booking_payment_completed($p, $meta);
                }
                $note = 'Booking deposit received';
                break;
        }
        update('payments', ['fulfilled_at' => now()], ['id' => $paymentId]);
        if ($p['payment_type'] !== 'gift') { // gifts send their own, more useful alert
            admin_alert('payment', 'Payment received: ' . money((float)$p['amount']), ucfirst(str_replace('_', ' ', (string)$p['payment_type'])) . ' payment of ' . money((float)$p['amount']) . ' by ' . $p['payment_method'] . '. Reference ' . $p['reference'] . '.', '/admin/payments/' . $paymentId);
        }
        return [true, $note];
    });
}

function payment_fail(int $paymentId, string $status = 'failed', string $reason = ''): void
{
    q('UPDATE payments SET status = :s, mpesa_result_desc = COALESCE(NULLIF(:r, \'\'), mpesa_result_desc), processed_at = NOW()
       WHERE id = :id AND status = \'pending\'', ['s' => $status, 'r' => mb_substr($reason, 0, 255), 'id' => $paymentId]);
}

/** Expire subscriptions and clean stale pending payments (called by cron). */
function plans_housekeeping(): array
{
    $expired = q('UPDATE user_subscriptions SET status = \'expired\' WHERE status = \'active\' AND expires_at <= NOW()')->rowCount();
    $stale = q('UPDATE payments SET status = \'cancelled\', mpesa_result_desc = \'Timed out\' WHERE status = \'pending\' AND payment_method = \'mpesa\' AND updated_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)')->rowCount();
    return ['expired_subscriptions' => $expired, 'stale_payments' => $stale];
}
