<?php
/**
 * Payments for members: checkout helpers, M-Pesa Daraja (STK push, query, callback), manual
 * M-Pesa / bank confirmation and test mode. Fulfilment itself is payment_complete() in plans.php.
 *
 * Every network call fails softly: a timeout or error becomes a plain sentence for the member
 * and a line in storage/logs/mpesa.log, never an exception on the page.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Which ways to pay are on
// ---------------------------------------------------------------------------

/** M-Pesa credentials from settings. */
function mpesa_config(): array
{
    $env = (string)setting('mpesa_env') === 'production' ? 'production' : 'sandbox';
    return [
        'env' => $env,
        'base' => $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke',
        'shortcode' => trim((string)setting('mpesa_shortcode')),
        'type' => (string)setting('mpesa_type') === 'CustomerBuyGoodsOnline' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
        'key' => trim((string)setting('mpesa_consumer_key')),
        'secret' => trim((string)setting('mpesa_consumer_secret')),
        'passkey' => trim((string)setting('mpesa_passkey')),
    ];
}

function mpesa_ready(): bool
{
    $c = mpesa_config();
    return $c['shortcode'] !== '' && $c['key'] !== '' && $c['secret'] !== '' && $c['passkey'] !== '';
}

/**
 * Payment methods and whether each can be used right now, in the order members see them.
 * Keys: mpesa, card, paystack, paypal, manual, bank, test. Each: label, icon (fallback), enabled (admin switch), ready (usable), stored (value kept on the payment).
 * Logos come from pay_logos() so members see the real M-Pesa, PayPal, Visa... marks.
 */
function payments_methods(): array
{
    $all = [
        'mpesa' => ['label' => 'M-Pesa', 'icon' => 'smartphone', 'enabled' => (bool)setting('mpesa_enabled'), 'ready' => mpesa_ready(), 'stored' => 'mpesa'],
        'card' => ['label' => 'Credit or debit card', 'icon' => 'credit-card', 'enabled' => (bool)setting('card_payments_enabled'), 'ready' => card_ready(), 'stored' => 'card'],
        'paystack' => ['label' => 'Paystack', 'icon' => 'credit-card', 'enabled' => (bool)setting('paystack_enabled'), 'ready' => paystack_ready(), 'stored' => 'paystack'],
        'paypal' => ['label' => 'PayPal', 'icon' => 'wallet', 'enabled' => (bool)setting('paypal_enabled'), 'ready' => paypal_ready(), 'stored' => 'paypal'],
        'manual' => ['label' => 'Pay to our Paybill or till', 'icon' => 'receipt', 'enabled' => (bool)setting('manual_mpesa_enabled'), 'ready' => trim((string)setting('manual_mpesa_number')) !== '' || trim((string)setting('manual_mpesa_details')) !== '', 'stored' => 'manual'],
        'bank' => ['label' => 'Bank transfer', 'icon' => 'landmark', 'enabled' => (bool)setting('bank_enabled'), 'ready' => trim((string)setting('bank_account_number')) !== '', 'stored' => 'bank_transfer'],
        'test' => ['label' => 'Test payment', 'icon' => 'zap', 'enabled' => (bool)setting('payments_test_mode'), 'ready' => true, 'stored' => 'test'],
    ];
    $out = [];
    foreach (payments_method_order() as $k) {
        if (isset($all[$k])) {
            $out[$k] = $all[$k];
        }
    }
    return $out + $all;
}

/** The order the admin chose, with any method it does not mention added at the end. */
function payments_method_order(): array
{
    $known = ['mpesa', 'card', 'paystack', 'paypal', 'manual', 'bank', 'test'];
    $set = array_values(array_filter(array_map('trim', explode(',', (string)setting('payments_method_order'))), fn($k) => in_array($k, $known, true)));
    return array_values(array_unique(array_merge($set, $known)));
}

/** The method new payments are created with; the member can switch on the payment page. */
function payments_default_method(): string
{
    foreach (payments_methods() as $key => $m) {
        if ($key !== 'test' && $m['enabled'] && $m['ready']) {
            return $m['stored'];
        }
    }
    return 'mpesa';
}

// ---------------------------------------------------------------------------
// Creating payments (amounts always come from the database or settings)
// ---------------------------------------------------------------------------

function payment_meta(array $p): array
{
    $d = json_decode((string)($p['metadata'] ?? ''), true);
    return is_array($d) ? $d : [];
}

function payment_meta_merge(int $paymentId, array $add): void
{
    $cur = row('SELECT metadata FROM payments WHERE id = :id', ['id' => $paymentId]);
    $meta = $add + (json_decode((string)($cur['metadata'] ?? ''), true) ?: []);
    q('UPDATE payments SET metadata = :m WHERE id = :id', ['m' => json_encode($meta, JSON_UNESCAPED_UNICODE), 'id' => $paymentId]);
}

/** A pending payment from the last half hour that matches, so a double click does not create two. */
function payment_find_reusable(int $userId, string $type, float $amount, array $match): ?array
{
    $cands = rows(
        "SELECT * FROM payments WHERE user_id = :u AND payment_type = :t AND status = 'pending' AND amount = :a
         AND bank_reference IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY id DESC LIMIT 5",
        ['u' => $userId, 't' => $type, 'a' => $amount]
    );
    foreach ($cands as $c) {
        $meta = payment_meta($c);
        $same = true;
        foreach ($match as $k => $v) {
            if (!array_key_exists($k, $meta) || (string)$meta[$k] !== (string)$v) {
                $same = false;
                break;
            }
        }
        if ($same) {
            return $c;
        }
    }
    return null;
}

/** Start a plan purchase. @return array ['ok'=>bool, 'error'=>string, 'payment'=>array] */
function checkout_subscription(string $planSlug, string $period): array
{
    $u = current_user();
    if (!$u) {
        return ['ok' => false, 'error' => 'Sign in to choose a plan.'];
    }
    return checkout_subscription_for($u, $planSlug, $period);
}

/** Same as checkout_subscription() for a given member (also used by automatic renewal, where nobody is signed in). */
function checkout_subscription_for(array $u, string $planSlug, string $period): array
{
    $plan = row('SELECT * FROM subscription_plans WHERE slug = :s AND is_active = 1 AND tier > 0', ['s' => $planSlug]);
    if (!$plan) {
        return ['ok' => false, 'error' => 'That plan is not available.'];
    }
    $period = plan_period_clean($period);
    if ($period !== 'monthly' && !isset(plan_periods_for($plan)[$period])) {
        return ['ok' => false, 'error' => plan_period_defs()[$period]['label'] . ' billing is not offered for this plan.'];
    }
    $amount = plan_period_price($plan, $period);
    if ($amount <= 0) {
        return ['ok' => false, 'error' => 'This plan has no price yet. Please contact support.'];
    }
    $cur = user_subscription((int)$u['id']);
    if ($cur && (int)$cur['plan_tier'] > (int)$plan['tier']) {
        return ['ok' => false, 'error' => 'You are on ' . $cur['plan_name'] . ' until ' . fmt_date((string)$cur['expires_at']) . '. You can switch to ' . $plan['name'] . ' after it ends.'];
    }
    $match = ['plan_id' => (int)$plan['id'], 'period' => $period];
    $existing = payment_find_reusable((int)$u['id'], 'subscription', $amount, $match);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $label = plan_period_defs()[$period]['text'];
    $p = payment_create((int)$u['id'], 'subscription', payments_default_method(), $amount, $plan['name'] . ' plan, ' . $label, $match + ['plan_slug' => $plan['slug'], 'plan_name' => $plan['name']]);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}

/** Start a boost purchase for one of the packs the admin set up. */
function checkout_boost(int $packId = 0): array
{
    $u = current_user();
    if (!$u) {
        return ['ok' => false, 'error' => 'Please sign in first.'];
    }
    [$can, $why] = boost_can_start((int)$u['id']);
    if (!$can) {
        return ['ok' => false, 'error' => $why];
    }
    $packs = boost_packs('boost');
    $pack = $packId > 0 ? boost_pack($packId, 'boost') : ($packs[0] ?? null);
    if (!$pack || !(int)$pack['is_active'] || (float)$pack['price'] <= 0) {
        return ['ok' => false, 'error' => 'That boost is not available right now.'];
    }
    $price = (float)$pack['price'];
    $minutes = (int)$pack['minutes'];
    $match = ['pack_id' => (int)$pack['id'], 'minutes' => $minutes];
    $existing = payment_find_reusable((int)$u['id'], 'boost', $price, $match);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $p = payment_create((int)$u['id'], 'boost', payments_default_method(), $price, 'Profile boost, ' . boost_length_label($minutes), $match + ['pack_name' => $pack['name'], 'return_url' => '/boost']);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}

/** Start a featured-profile purchase for one of the featured packs. */
function checkout_featured(int $packId = 0): array
{
    $u = current_user();
    if (!$u || !featured_enabled()) {
        return ['ok' => false, 'error' => 'Featured profiles are not available right now.'];
    }
    $packs = boost_packs('featured');
    $pack = $packId > 0 ? boost_pack($packId, 'featured') : ($packs[0] ?? null);
    if (!$pack || !(int)$pack['is_active'] || (float)$pack['price'] <= 0) {
        return ['ok' => false, 'error' => 'That featured option is not available right now.'];
    }
    $price = (float)$pack['price'];
    $days = max(1, (int)ceil((int)$pack['minutes'] / 1440));
    $match = ['pack_id' => (int)$pack['id'], 'days' => $days];
    $existing = payment_find_reusable((int)$u['id'], 'featured', $price, $match);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $p = payment_create((int)$u['id'], 'featured', payments_default_method(), $price, 'Featured profile, ' . plural($days, 'day'), $match + ['pack_name' => $pack['name'], 'return_url' => '/boost']);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}

/** Start a Host listing purchase for one of the periods the admin priced (weekly, monthly, yearly). */
function checkout_host_listing(string $period): array
{
    $u = current_user();
    $plans = function_exists('hosts_listing_plans') ? hosts_listing_plans() : [];
    if (!$u || ($u['role'] ?? '') !== 'host' || !hosts_listing_required() || !isset($plans[$period])) {
        return ['ok' => false, 'error' => 'That listing option is not available right now.'];
    }
    $pl = $plans[$period];
    $match = ['period' => $period, 'days' => $pl['days']];
    $existing = payment_find_reusable((int)$u['id'], 'host_listing', (float)$pl['price'], $match);
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $p = payment_create((int)$u['id'], 'host_listing', payments_default_method(), (float)$pl['price'], 'Host listing, ' . strtolower($pl['label']), $match + ['return_url' => '/host/listing']);
    return ['ok' => true, 'error' => '', 'payment' => $p];
}

/** Start the same purchase again after a failed or cancelled attempt. */
function payment_clone_for_retry(array $old): ?array
{
    if (!in_array($old['status'], ['failed', 'cancelled'], true)) {
        return null;
    }
    $meta = payment_meta($old);
    unset($meta['stk_at'], $meta['review'], $meta['submitted_at']);
    return payment_create((int)$old['user_id'], (string)$old['payment_type'], payments_default_method(), (float)$old['amount'], (string)$old['description'], $meta);
}

// ---------------------------------------------------------------------------
// What a payment is for, and where to go afterwards
// ---------------------------------------------------------------------------

/** UI state of a payment: paid, failed, cancelled, refunded, review (manual proof sent), stk (phone prompt sent), open. */
function payment_ui_state(array $p): string
{
    switch ($p['status']) {
        case 'completed':
            return 'paid';
        case 'failed':
            return 'failed';
        case 'cancelled':
            return 'cancelled';
        case 'refunded':
            return 'refunded';
    }
    if (!empty($p['bank_reference']) && in_array($p['payment_method'], ['manual', 'bank_transfer'], true)) {
        return 'review';
    }
    if (!empty($p['mpesa_checkout_id']) && $p['payment_method'] === 'mpesa') {
        return 'stk';
    }
    return 'open';
}

/** Where to send the member after a successful payment. Internal paths only. */
function payment_return_url(array $p): string
{
    $meta = payment_meta($p);
    $id = (int)$p['id'];
    $defaults = [
        'subscription' => '/premium/success?payment=' . $id, 'boost' => '/boost', 'featured' => '/boost', 'gift' => '/gifts?tab=sent',
        'booking' => !empty($meta['booking_id']) ? '/bookings/' . (int)$meta['booking_id'] : '/bookings', 'ad' => '/advertise/mine', 'host_listing' => '/host/listing', 'dm_pack' => '/messages/credits', 'intl' => '/international',
    ];
    $fallback = $defaults[$p['payment_type']] ?? '/billing';
    $custom = isset($meta['return_url']) ? safe_next((string)$meta['return_url'], '') : '';
    if ($p['payment_type'] === 'subscription') {
        return $fallback; // the success page explains what was unlocked
    }
    return $custom !== '' ? $custom : $fallback;
}

/** Plain-language summary of an order for the payment page and receipts. Returns [title, icon, lines[ [label, value] ]]. */
function payment_summary(array $p): array
{
    $meta = payment_meta($p);
    $type = (string)$p['payment_type'];
    $lines = [];
    switch ($type) {
        case 'subscription':
            $period = plan_period_defs()[plan_period_clean((string)($meta['period'] ?? 'monthly'))]['text'];
            $plan = row('SELECT name, tier FROM subscription_plans WHERE id = :id', ['id' => (int)($meta['plan_id'] ?? 0)]);
            $name = $plan['name'] ?? (string)($meta['plan_name'] ?? 'Plan');
            $lines = [['Plan', $name], ['Period', $period], ['Renewal', !empty($meta['auto_renew']) ? 'Renews automatically. Cancel any time on Billing.' : (autorenew_enabled() ? 'One payment. Tick "Renew automatically" to keep it going.' : 'One payment. It does not renew by itself.')]];
            if (($p['status'] ?? '') === 'pending' && !empty($p['user_id']) && !empty($meta['plan_id'])) {
                $sw = subscription_switch_credit((int)$p['user_id'], (int)$meta['plan_id'], plan_period_clean((string)($meta['period'] ?? 'monthly')));
                if ($sw['days'] > 0) {
                    $lines[] = ['Your ' . $sw['from'] . ' plan', 'Has ' . plural($sw['left'], 'day') . ' left. Their value is added here as ' . plural($sw['days'], 'extra day') . ' of ' . $name . '.'];
                }
            }
            return [$name . ' plan', ($plan['tier'] ?? 1) >= 2 ? 'crown' : 'sparkles', $lines];
        case 'boost':
            $lines = [['Boost', boost_length_label((int)($meta['minutes'] ?? boost_default_minutes()))], ['Effect', 'Your profile is shown first while it runs.']];
            return ['Profile boost', 'rocket', $lines];
        case 'featured':
            $lines = [['Featured for', plural((int)($meta['days'] ?? 7), 'day')], ['Effect', 'Your profile appears in Daily picks and the top of Discover.']];
            return ['Featured profile', 'sparkles', $lines];
        case 'gift':
            $g = gift_type((int)($meta['gift_type_id'] ?? 0));
            $lines = [['Gift', trim(($g['emoji'] ?? '') . ' ' . ($g['name'] ?? ($meta['gift_name'] ?? 'Gift')))], ['For', (string)($meta['to_name'] ?? 'Member')]];
            if (!empty($meta['message'])) {
                $lines[] = ['Your note', (string)$meta['message']];
            }
            return ['Gift for ' . ($meta['to_name'] ?? 'a member'), 'gift', $lines];
        case 'host_listing':
            $days = (int)($meta['days'] ?? 30);
            $lines = [['Listing', plural($days, 'day')], ['Effect', 'Your profile shows in the Hosts directory while the listing runs.'], ['Renewal', 'One payment. It does not renew by itself.']];
            return ['Host listing', 'concierge-bell', $lines];
        case 'dm_pack':
            $lines = [['Credits', plural((int)($meta['credits'] ?? 0), 'direct message credit')], ['Use', 'One credit opens a conversation with someone you have not matched with yet.']];
            return [(string)($meta['pack_name'] ?? 'Message credits'), 'send', $lines];
        case 'intl':
            $lines = [['For', ($meta['kind'] ?? '') === 'join' ? 'Joining the international page' : 'Browsing the international page'], ['Lasts', !empty($meta['days']) ? plural((int)$meta['days'], 'day') : 'As long as your account is open']];
            return [($meta['kind'] ?? '') === 'join' ? 'International listing' : 'International access', 'globe', $lines];
        case 'ad':
            return function_exists('promo_payment_summary') ? promo_payment_summary($p, $meta) : ['Advert', 'megaphone', [['For', (string)$p['description']]]];
        case 'elite':
            return function_exists('elite_payment_summary') ? elite_payment_summary($p, $meta) : ['Elite', 'gem', [['For', (string)$p['description']]]];
        case 'booking':
            $lines = [['Booking', (string)($p['description'] ?: 'Host booking deposit')]];
            return ['Booking deposit', 'calendar-check', $lines];
    }
    return [(string)($p['description'] ?: 'Payment'), 'receipt', [['For', (string)($p['description'] ?: ucfirst($type))]]];
}

function payment_type_label(string $type): string
{
    return ['subscription' => 'Plan', 'boost' => 'Boost', 'featured' => 'Featured profile', 'gift' => 'Gift', 'booking' => 'Booking deposit', 'ad' => 'Advert', 'host_listing' => 'Host listing', 'dm_pack' => 'Message credits', 'intl' => 'International', 'elite' => (function_exists('elite_label') ? elite_label() : 'Elite')][$type] ?? ucfirst($type);
}

function payment_method_label(string $m): string
{
    return ['mpesa' => 'M-Pesa', 'manual' => 'M-Pesa (Paybill or till)', 'bank_transfer' => 'Bank transfer', 'test' => 'Test payment', 'card' => 'Card', 'paystack' => 'Paystack', 'paypal' => 'PayPal'][$m] ?? ucfirst($m);
}

/** [label, badge class] for a payment row. */
function payment_status_badge(array $p): array
{
    switch (payment_ui_state($p)) {
        case 'paid':
            return ['Paid', 'badge-success'];
        case 'review':
            return ['In review', 'badge-info'];
        case 'stk':
            return ['Waiting for M-Pesa', 'badge-warning'];
        case 'failed':
            return ['Failed', 'badge-danger'];
        case 'cancelled':
            return ['Cancelled', ''];
        case 'refunded':
            return ['Refunded', ''];
    }
    return ['Not paid yet', 'badge-warning'];
}

// ---------------------------------------------------------------------------
// HTTP (cURL, falling back to streams). Tests can replace it with $GLOBALS['__payments_http'].
// ---------------------------------------------------------------------------

/** @return array ['status'=>int, 'body'=>string, 'error'=>string] */
function payments_http(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 20): array
{
    $hook = $GLOBALS['__payments_http'] ?? null;
    if (is_callable($hook)) {
        return $hook($method, $url, $headers, $body);
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => $timeout, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = $raw === false ? curl_error($ch) : '';
        curl_close($ch);
        return ['status' => $status, 'body' => $raw === false ? '' : (string)$raw, 'error' => $err];
    }
    $ctx = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $body ?? '', 'timeout' => $timeout, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $status = (int)$m[1];
        }
    }
    return ['status' => $status, 'body' => $raw === false ? '' : (string)$raw, 'error' => $raw === false ? 'Request failed' : ''];
}

// ---------------------------------------------------------------------------
// M-Pesa Daraja
// ---------------------------------------------------------------------------

function mpesa_log(string $message, array $ctx = []): void
{
    app_log('mpesa', $message, $ctx);
}

/** OAuth token for Daraja. Cached until shortly before it expires. @return array ['ok'=>bool, 'token'=>string, 'error'=>string] */
function mpesa_access_token(): array
{
    $c = mpesa_config();
    $cacheKey = 'mpesa_token_' . substr(sha1($c['env'] . $c['key']), 0, 16);
    $cached = cache_get($cacheKey);
    if (is_string($cached) && $cached !== '') {
        return ['ok' => true, 'token' => $cached, 'error' => ''];
    }
    $r = payments_http('GET', $c['base'] . '/oauth/v1/generate?grant_type=client_credentials', ['Authorization: Basic ' . base64_encode($c['key'] . ':' . $c['secret']), 'Accept: application/json'], null, 15);
    $j = json_decode($r['body'], true);
    if ($r['status'] === 200 && is_array($j) && !empty($j['access_token'])) {
        cache_put($cacheKey, (string)$j['access_token'], max(60, (int)($j['expires_in'] ?? 3599) - 120));
        return ['ok' => true, 'token' => (string)$j['access_token'], 'error' => ''];
    }
    mpesa_log('token request failed', ['status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 300)]);
    return ['ok' => false, 'token' => '', 'error' => $r['status'] === 0 ? 'We could not reach M-Pesa. Please try again in a moment or choose another way to pay.' : 'M-Pesa checkout is not available right now. Please try again shortly or choose another way to pay.'];
}

function mpesa_timestamp(?int $time = null): string
{
    return (new DateTime('@' . ($time ?? time())))->setTimezone(new DateTimeZone('Africa/Nairobi'))->format('YmdHis');
}

function mpesa_password(string $shortcode, string $passkey, string $timestamp): string
{
    return base64_encode($shortcode . $passkey . $timestamp);
}

/** M-Pesa only takes whole shillings, so a part-shilling amount is rounded up. */
function mpesa_amount(float $amount): int
{
    return max(1, (int)ceil(round($amount, 2) - 0.0001));
}

/** Accept 07xx, 01xx, +254... and return 2547XXXXXXXX / 2541XXXXXXXX, or '' when it is not a Safaricom number. */
function mpesa_phone(string $raw): string
{
    $p = phone_normalize($raw);
    return preg_match('/^254[17]\d{8}$/', $p) ? $p : '';
}

/** The JSON body of an STK push request (kept separate so it can be tested without a network). */
function mpesa_build_stk_request(array $payment, string $phone, array $cfg, string $callbackUrl, ?string $timestamp = null): array
{
    $ts = $timestamp ?? mpesa_timestamp();
    return [
        'BusinessShortCode' => $cfg['shortcode'],
        'Password' => mpesa_password($cfg['shortcode'], $cfg['passkey'], $ts),
        'Timestamp' => $ts,
        'TransactionType' => $cfg['type'],
        'Amount' => mpesa_amount((float)$payment['amount']),
        'PartyA' => $phone,
        'PartyB' => $cfg['shortcode'],
        'PhoneNumber' => $phone,
        'CallBackURL' => $callbackUrl,
        'AccountReference' => substr((string)$payment['reference'], -12),
        'TransactionDesc' => mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', site_name()) ?: 'Payment', 0, 13),
    ];
}

/** The body of an STK query request. */
function mpesa_build_query_request(string $checkoutId, array $cfg, ?string $timestamp = null): array
{
    $ts = $timestamp ?? mpesa_timestamp();
    return ['BusinessShortCode' => $cfg['shortcode'], 'Password' => mpesa_password($cfg['shortcode'], $cfg['passkey'], $ts), 'Timestamp' => $ts, 'CheckoutRequestID' => $checkoutId];
}

/** The address Safaricom posts results to. Creates the secret part the first time. */
function mpesa_callback_url(): string
{
    return mpesa_stk_callback_url();
}

/** Plain-language text for an M-Pesa result code. */
function mpesa_result_message(int $code, string $desc = ''): string
{
    $map = [
        0 => 'Payment received.',
        1 => 'Your M-Pesa balance is too low for this payment. Top up and try again.',
        1001 => 'Another M-Pesa payment is waiting on your phone. Finish or cancel it, then try again.',
        1019 => 'The M-Pesa request expired. Please try again.',
        1025 => 'M-Pesa could not send the prompt to your phone. Please try again.',
        1032 => 'You cancelled the payment on your phone.',
        1037 => 'We did not hear back from your phone. Make sure it is on and has network, then try again.',
        2001 => 'The M-Pesa PIN you entered was wrong. Please try again.',
        9999 => 'M-Pesa could not process the request. Please try again.',
    ];
    if (isset($map[$code])) {
        return $map[$code];
    }
    $desc = trim($desc);
    return $desc !== '' ? mb_substr($desc, 0, 160) : 'The payment did not go through. Please try again.';
}

/** Map a Daraja error response to a sentence for the member. */
function mpesa_error_message(?array $j, int $status): string
{
    $code = (string)($j['errorCode'] ?? '');
    $msg = (string)($j['errorMessage'] ?? ($j['ResponseDescription'] ?? ''));
    if (stripos($msg, 'PhoneNumber') !== false || $code === '400.002.02') {
        return 'That phone number is not valid for M-Pesa. Check it and try again.';
    }
    if ($code === '500.001.1001' || stripos($msg, 'lock subscriber') !== false) {
        return 'Another M-Pesa payment is already waiting on that phone. Finish or cancel it, then try again.';
    }
    if ($status === 0) {
        return 'We could not reach M-Pesa. Please try again in a moment or choose another way to pay.';
    }
    return 'M-Pesa could not start the payment. Please try again shortly or choose another way to pay.';
}

/**
 * Ask Safaricom to send the payment prompt to the member's phone.
 * @return array ['ok'=>bool, 'error'=>string, 'checkout_id'=>string, 'merchant_id'=>string, 'message'=>string]
 */
function mpesa_stk_push(array $payment, string $phone): array
{
    $fail = fn(string $e) => ['ok' => false, 'error' => $e, 'checkout_id' => '', 'merchant_id' => '', 'message' => ''];
    if (!mpesa_ready()) {
        return $fail('M-Pesa checkout is not set up yet. Please choose another way to pay.');
    }
    $cfg = mpesa_config();
    $tok = mpesa_access_token();
    if (!$tok['ok']) {
        return $fail($tok['error']);
    }
    $body = mpesa_build_stk_request($payment, $phone, $cfg, mpesa_callback_url());
    $r = payments_http('POST', $cfg['base'] . '/mpesa/stkpush/v1/processrequest', ['Authorization: Bearer ' . $tok['token'], 'Content-Type: application/json'], (string)json_encode($body), 25);
    $j = json_decode($r['body'], true);
    if ($r['status'] === 200 && is_array($j) && (string)($j['ResponseCode'] ?? '') === '0' && !empty($j['CheckoutRequestID'])) {
        mpesa_log('stk push sent', ['payment' => $payment['id'], 'checkout' => $j['CheckoutRequestID'], 'amount' => $body['Amount']]);
        return ['ok' => true, 'error' => '', 'checkout_id' => (string)$j['CheckoutRequestID'], 'merchant_id' => (string)($j['MerchantRequestID'] ?? ''), 'message' => (string)($j['CustomerMessage'] ?? '')];
    }
    if ($r['status'] === 401 || (string)($j['errorCode'] ?? '') === '404.001.03') {
        cache_clear('mpesa_token_'); // the cached token was rejected
    }
    mpesa_log('stk push failed', ['payment' => $payment['id'], 'status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 400)]);
    return $fail(mpesa_error_message(is_array($j) ? $j : null, $r['status']));
}

/**
 * Ask Safaricom what happened to an STK push. state: paid | failed | pending | unknown.
 * @return array ['state'=>string, 'code'=>int, 'message'=>string]
 */
function mpesa_stk_query(array $payment): array
{
    $unknown = ['state' => 'unknown', 'code' => -1, 'message' => ''];
    if (!mpesa_ready() || empty($payment['mpesa_checkout_id'])) {
        return $unknown;
    }
    $tok = mpesa_access_token();
    if (!$tok['ok']) {
        return $unknown;
    }
    $cfg = mpesa_config();
    $r = payments_http('POST', $cfg['base'] . '/mpesa/stkpushquery/v1/query', ['Authorization: Bearer ' . $tok['token'], 'Content-Type: application/json'], (string)json_encode(mpesa_build_query_request((string)$payment['mpesa_checkout_id'], $cfg)), 15);
    return mpesa_parse_query_response($r['status'], $r['body']);
}

/** Read an STK query response. A 500 "transaction is being processed" answer means still pending. */
function mpesa_parse_query_response(int $status, string $body): array
{
    $j = json_decode($body, true);
    if (!is_array($j)) {
        return ['state' => 'unknown', 'code' => -1, 'message' => ''];
    }
    if (isset($j['ResultCode'])) {
        $code = (int)$j['ResultCode'];
        return ['state' => $code === 0 ? 'paid' : 'failed', 'code' => $code, 'message' => mpesa_result_message($code, (string)($j['ResultDesc'] ?? ''))];
    }
    if ((string)($j['errorCode'] ?? '') === '500.001.1001' || stripos((string)($j['errorMessage'] ?? ''), 'being processed') !== false) {
        return ['state' => 'pending', 'code' => -1, 'message' => ''];
    }
    return ['state' => 'unknown', 'code' => -1, 'message' => ''];
}

/**
 * Normalise the JSON Safaricom posts to the callback address. Returns null when it is not an STK callback.
 * @return array|null keys: checkout_id, merchant_id, code, desc, amount, receipt, phone, date
 */
function mpesa_parse_callback(array $payload): ?array
{
    $cb = $payload['Body']['stkCallback'] ?? null;
    if (!is_array($cb) || empty($cb['CheckoutRequestID']) || !is_scalar($cb['CheckoutRequestID'])) {
        return null;
    }
    $items = [];
    foreach ((array)($cb['CallbackMetadata']['Item'] ?? []) as $it) {
        if (is_array($it) && isset($it['Name'])) {
            $items[(string)$it['Name']] = $it['Value'] ?? null;
        }
    }
    return [
        'checkout_id' => (string)$cb['CheckoutRequestID'], 'merchant_id' => (string)($cb['MerchantRequestID'] ?? ''),
        'code' => isset($cb['ResultCode']) && is_numeric($cb['ResultCode']) ? (int)$cb['ResultCode'] : -1, 'desc' => (string)($cb['ResultDesc'] ?? ''),
        'amount' => isset($items['Amount']) && is_numeric($items['Amount']) ? (float)$items['Amount'] : null,
        'receipt' => isset($items['MpesaReceiptNumber']) ? preg_replace('/[^A-Za-z0-9]/', '', (string)$items['MpesaReceiptNumber']) : '',
        'phone' => isset($items['PhoneNumber']) ? preg_replace('/\D/', '', (string)$items['PhoneNumber']) : '',
        'date' => isset($items['TransactionDate']) ? (string)$items['TransactionDate'] : '',
    ];
}

/**
 * Apply an M-Pesa result (from the callback or from an STK query) to a payment. Safe to repeat.
 * Returns: completed | failed | cancelled | mismatch | duplicate | ignored.
 */
function mpesa_apply_result(array $payment, array $res): string
{
    $id = (int)$payment['id'];
    // A success that arrives after the order was timed out or cancelled on our side still means the money moved, so it is honoured once.
    $late = $res['code'] === 0 && in_array((string)$payment['status'], ['cancelled', 'failed'], true) && empty($payment['fulfilled_at']);
    if ($payment['status'] !== 'pending' && !$late) {
        return 'duplicate';
    }
    if ($res['code'] === 0) {
        $expected = mpesa_amount((float)$payment['amount']);
        if ($res['amount'] !== null && $res['amount'] + 0.009 < $expected) {
            update('payments', ['mpesa_receipt' => $res['receipt'] ?: null], ['id' => $id]);
            payment_fail($id, 'failed', 'Amount mismatch: M-Pesa received ' . $res['amount'] . ', expected ' . $expected . '. Check with support.');
            mpesa_log('amount mismatch', ['payment' => $id, 'received' => $res['amount'], 'expected' => $expected, 'receipt' => $res['receipt']]);
            return 'mismatch';
        }
        if ($res['receipt'] !== '' && (int)val('SELECT COUNT(*) FROM payments WHERE mpesa_receipt = :r AND id <> :id', ['r' => $res['receipt'], 'id' => $id]) > 0) {
            payment_fail($id, 'failed', 'That M-Pesa receipt was already used for another payment.');
            mpesa_log('duplicate receipt', ['payment' => $id, 'receipt' => $res['receipt']]);
            return 'mismatch';
        }
        $extra = ['mpesa_result_desc' => mb_substr($res['desc'] !== '' ? $res['desc'] : 'Paid', 0, 255)];
        if ($res['receipt'] !== '') {
            $extra['mpesa_receipt'] = $res['receipt'];
        }
        if (!empty($res['merchant_id'])) {
            $extra['mpesa_merchant_id'] = $res['merchant_id'];
        }
        if (!empty($res['phone']) && strlen($res['phone']) <= 20) {
            update('payments', ['mpesa_phone' => $res['phone']], ['id' => $id]);
        }
        payment_complete($id, $extra);
        return 'completed';
    }
    $status = $res['code'] === 1032 ? 'cancelled' : 'failed';
    payment_fail($id, $status, mpesa_result_message($res['code'], $res['desc']));
    return $status;
}

/** Is $ip inside one of the allowed addresses or CIDR ranges? */
function mpesa_ip_allowed(string $ip, array $allowed): bool
{
    foreach ($allowed as $rule) {
        if (strpos($rule, '/') === false) {
            if ($rule === $ip) {
                return true;
            }
            continue;
        }
        [$net, $bits] = explode('/', $rule, 2);
        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton($net);
        $bits = (int)$bits;
        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin) || $bits < 0 || $bits > strlen($ipBin) * 8) {
            continue;
        }
        $bytes = intdiv($bits, 8);
        $rem = $bits % 8;
        if (substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            continue;
        }
        if ($rem === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rem)) & 0xFF;
        if ((ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask)) {
            return true;
        }
    }
    return false;
}

/**
 * Handle a request to the M-Pesa callback address. Returns [httpStatus, responseBody].
 * $urlSecret is the secret part of the address ('' for the plain address).
 */
function mpesa_handle_callback(array $payload, string $urlSecret, string $ip): array
{
    $ack = fn(int $status, string $desc, int $code = 0) => [$status, ['ResultCode' => $code, 'ResultDesc' => $desc]];
    try {
        $secret = (string)setting('mpesa_callback_secret');
        $ips = setting_lines('mpesa_callback_ips');
        if ($ips && !mpesa_ip_allowed($ip, $ips)) {
            mpesa_log('callback rejected: address not allowed', ['ip' => $ip]);
            return $ack(403, 'Forbidden', 1);
        }
        $secretOk = $secret !== '' && $urlSecret !== '' && hash_equals($secret, $urlSecret);
        if (!$secretOk && !($secret === '' && $ips)) {
            // With no secret configured the address can only be trusted when an IP allow-list is in force.
            mpesa_log('callback rejected: wrong or missing secret', ['ip' => $ip]);
            return $ack(403, 'Forbidden', 1);
        }
        $res = mpesa_parse_callback($payload);
        if (!$res) {
            mpesa_log('callback malformed', ['ip' => $ip]);
            return $ack(400, 'Malformed request', 1);
        }
        $pay = row('SELECT * FROM payments WHERE mpesa_checkout_id = :c LIMIT 1', ['c' => $res['checkout_id']]);
        if (!$pay) {
            mpesa_log('callback for unknown checkout', ['checkout' => $res['checkout_id'], 'code' => $res['code']]);
            return $ack(200, 'Accepted');
        }
        if ($res['merchant_id'] !== '' && !empty($pay['mpesa_merchant_id']) && $pay['mpesa_merchant_id'] !== $res['merchant_id']) {
            mpesa_log('callback merchant id mismatch', ['payment' => $pay['id'], 'got' => $res['merchant_id']]);
            return $ack(200, 'Accepted');
        }
        $outcome = mpesa_apply_result($pay, $res);
        mpesa_log('callback processed', ['payment' => $pay['id'], 'code' => $res['code'], 'outcome' => $outcome, 'receipt' => $res['receipt']]);
        return $ack(200, 'Accepted');
    } catch (Throwable $e) {
        app_log('error', 'mpesa callback failed: ' . $e->getMessage());
        return $ack(500, 'Server error', 1);
    }
}

/**
 * Start an STK push for a pending payment.
 * @return array ['ok'=>bool, 'error'=>string, 'message'=>string, 'wait'=>int]
 */
function payments_mpesa_initiate(array $payment, string $rawPhone): array
{
    $wait = max(45, (int)setting('mpesa_stk_wait_seconds'));
    if ($payment['status'] !== 'pending') {
        return ['ok' => false, 'error' => 'This payment is already closed. Start a new one.', 'message' => '', 'wait' => $wait];
    }
    $phone = mpesa_phone($rawPhone);
    if ($phone === '') {
        return ['ok' => false, 'error' => 'Enter a Safaricom number such as 0712 345 678.', 'message' => '', 'wait' => $wait];
    }
    if (!rate_limit('stk:' . $payment['id'], 5, 600)) {
        return ['ok' => false, 'error' => 'Too many attempts. Wait a few minutes before trying again.', 'message' => '', 'wait' => $wait];
    }
    $r = mpesa_stk_push($payment, $phone);
    if (!$r['ok']) {
        return ['ok' => false, 'error' => $r['error'], 'message' => '', 'wait' => $wait];
    }
    update('payments', ['payment_method' => 'mpesa', 'mpesa_phone' => $phone, 'mpesa_checkout_id' => $r['checkout_id'], 'mpesa_merchant_id' => $r['merchant_id'], 'mpesa_result_desc' => null], ['id' => (int)$payment['id']]);
    payment_meta_merge((int)$payment['id'], ['stk_at' => time()]);
    return ['ok' => true, 'error' => '', 'message' => $r['message'] !== '' ? $r['message'] : 'Check your phone and enter your M-Pesa PIN.', 'wait' => $wait];
}

/**
 * Re-check a waiting STK push with Safaricom (at most every 8 seconds) in case the callback has not arrived.
 * Returns the fresh payment row.
 */
function payments_mpesa_reconcile(array $payment): array
{
    if ($payment['status'] !== 'pending' || $payment['payment_method'] !== 'mpesa' || empty($payment['mpesa_checkout_id']) || !mpesa_ready()) {
        return $payment;
    }
    $age = time() - (int)(payment_meta($payment)['stk_at'] ?? strtotime((string)$payment['updated_at']));
    if ($age < 10 || $age > 1800 || !rate_limit('stkq:' . $payment['id'], 1, 8)) {
        return $payment;
    }
    $q = mpesa_stk_query($payment);
    if ($q['state'] === 'paid' || $q['state'] === 'failed') {
        mpesa_apply_result($payment, ['code' => $q['code'], 'desc' => $q['message'], 'amount' => null, 'receipt' => '', 'phone' => '', 'merchant_id' => '']);
        mpesa_log('reconciled by query', ['payment' => $payment['id'], 'state' => $q['state'], 'code' => $q['code']]);
        return row('SELECT * FROM payments WHERE id = :id', ['id' => $payment['id']]) ?: $payment;
    }
    return $payment;
}

// ---------------------------------------------------------------------------
// Manual M-Pesa / bank confirmation and test mode
// ---------------------------------------------------------------------------

/**
 * A member says they paid by hand. The payment stays pending until an admin approves or rejects it
 * (payment_complete() / payment_fail()). @return array ['ok'=>bool, 'error'=>string]
 */
function payments_submit_manual(array $payment, string $kind, string $code, string $phone, string $paidOn): array
{
    $m = payments_methods();
    if ($payment['status'] !== 'pending') {
        return ['ok' => false, 'error' => 'This payment is already closed.'];
    }
    if (!empty($payment['bank_reference'])) {
        return ['ok' => false, 'error' => 'You already sent confirmation details. We are checking them.'];
    }
    $meta = ['submitted_at' => time(), 'review' => true];
    $set = [];
    if ($kind === 'bank') {
        if (!$m['bank']['enabled'] || !$m['bank']['ready']) {
            return ['ok' => false, 'error' => 'Bank transfer is not available.'];
        }
        $ref = trim($code);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._\/-]{3,39}$/', $ref)) {
            return ['ok' => false, 'error' => 'Enter the reference from your bank receipt (4 to 40 letters or numbers).'];
        }
        $ts = strtotime($paidOn . ' 00:00:00');
        if (!$ts || $ts > time() + 86400 || $ts < time() - 45 * 86400) {
            return ['ok' => false, 'error' => 'Enter the date you paid (within the last 45 days).'];
        }
        $set = ['payment_method' => 'bank_transfer', 'bank_reference' => $ref, 'bank_paid_on' => date('Y-m-d', $ts)];
        $code = $ref;
    } else {
        if (!$m['manual']['enabled'] || !$m['manual']['ready']) {
            return ['ok' => false, 'error' => 'Manual M-Pesa is not available.'];
        }
        $code = strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');
        if (!preg_match('/^[A-Z0-9]{8,12}$/', $code)) {
            return ['ok' => false, 'error' => 'Enter the confirmation code from your M-Pesa message, for example QGH7XK2LMN.'];
        }
        $p = mpesa_phone($phone);
        if ($p === '') {
            return ['ok' => false, 'error' => 'Enter the phone number you paid from, such as 0712 345 678.'];
        }
        $set = ['payment_method' => 'manual', 'bank_reference' => $code, 'mpesa_phone' => $p];
    }
    if ((int)val('SELECT COUNT(*) FROM payments WHERE id <> :id AND (bank_reference = :c OR mpesa_receipt = :c)', ['id' => $payment['id'], 'c' => $code]) > 0) {
        return ['ok' => false, 'error' => 'That code has already been used for another payment. Check it and try again.'];
    }
    if ($kind !== 'bank' && function_exists('mpesa_c2b_try_code') && mpesa_c2b_try_code($payment, $code)) {
        return ['ok' => true, 'error' => '']; // the money was already received on the Paybill: delivered at once
    }
    update('payments', $set, ['id' => (int)$payment['id']]);
    payment_meta_merge((int)$payment['id'], $meta);
    foreach (col("SELECT id FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id LIMIT 10") as $adminId) {
        notify((int)$adminId, 'payment', 'Payment waiting for review', money($payment['amount']) . ' from a member (' . $payment['reference'] . ').', '/admin/payments');
    }
    return ['ok' => true, 'error' => ''];
}

/** Test mode only: finish a payment without any money moving. */
function payments_test_finish(array $payment, bool $success): array
{
    if (!setting('payments_test_mode')) {
        return ['ok' => false, 'error' => 'Test mode is off.'];
    }
    if ($payment['status'] !== 'pending') {
        return ['ok' => false, 'error' => 'This payment is already closed.'];
    }
    update('payments', ['payment_method' => 'test'], ['id' => (int)$payment['id']]);
    if ($success) {
        payment_complete((int)$payment['id'], ['mpesa_receipt' => 'TEST' . strtoupper(substr(random_token(4), 0, 6)), 'mpesa_result_desc' => 'Test mode payment, no money moved']);
    } else {
        payment_fail((int)$payment['id'], 'failed', 'Test mode: simulated failure.');
    }
    return ['ok' => true, 'error' => ''];
}

/** Member cancels an unpaid payment. */
function payments_cancel(array $payment): bool
{
    if ($payment['status'] !== 'pending' || !empty($payment['bank_reference'])) {
        return false;
    }
    payment_fail((int)$payment['id'], 'cancelled', 'Cancelled by you.');
    return true;
}

// ---------------------------------------------------------------------------
// One entry point for the buttons on the payment page (form posts and the JSON API share it)
// ---------------------------------------------------------------------------

/** Seconds left of the phone-prompt wait for an STK push that was sent (0 when none is waiting). */
function payments_stk_seconds_left(array $payment): int
{
    if ($payment['status'] !== 'pending' || empty($payment['mpesa_checkout_id']) || $payment['payment_method'] !== 'mpesa') {
        return 0;
    }
    $sent = (int)(payment_meta($payment)['stk_at'] ?? strtotime((string)$payment['updated_at']));
    return max(0, $sent + max(45, (int)setting('mpesa_stk_wait_seconds')) - time());
}

/**
 * Run one action on the member's own payment: mpesa | manual | bank | test | cancel | retry.
 * @return array ['ok'=>bool, 'error'=>string, 'message'=>string, 'wait'=>int, 'redirect'=>string, 'payment_id'=>int, 'state'=>string]
 */
function payments_perform_action(array $payment, string $action, array $in): array
{
    $out = ['ok' => false, 'error' => '', 'message' => '', 'wait' => 0, 'redirect' => '', 'payment_id' => (int)$payment['id'], 'state' => payment_ui_state($payment)];
    $str = fn(string $k, int $max = 120): string => isset($in[$k]) && is_scalar($in[$k]) ? mb_substr(trim((string)$in[$k]), 0, $max) : '';
    $finish = function (array $o) use ($payment): array {
        $fresh = row('SELECT * FROM payments WHERE id = :id', ['id' => $payment['id']]) ?: $payment;
        $o['state'] = payment_ui_state($fresh);
        if ($o['state'] === 'paid') {
            $o['redirect'] = payment_return_url($fresh);
        }
        return $o;
    };
    switch ($action) {
        case 'mpesa':
            if (!payments_methods()['mpesa']['enabled']) {
                $out['error'] = 'M-Pesa prompts are not available right now. Choose another way to pay.';
                return $out;
            }
            $r = payments_mpesa_initiate($payment, $str('phone', 30));
            $out['ok'] = $r['ok'];
            $out['error'] = $r['error'];
            $out['message'] = $r['message'];
            $out['wait'] = $r['wait'];
            return $finish($out);
        case 'manual':
        case 'bank':
            $r = payments_submit_manual($payment, $action === 'bank' ? 'bank' : 'manual', $str('code', 60), $str('phone', 30), $str('paid_on', 12));
            $out['ok'] = $r['ok'];
            $out['error'] = $r['error'];
            $out['message'] = $r['ok'] ? 'Thanks. We are checking your payment and will confirm it shortly.' : '';
            $o = $finish($out);
            if ($o['state'] === 'paid') {
                $o['message'] = 'Payment received. Thank you.';
            }
            return $o;
        case 'paystack':
        case 'paypal':
        case 'card':
            if (!payments_methods()[$action]['enabled'] || !payments_methods()[$action]['ready']) {
                $out['error'] = 'This way of paying is not available right now. Choose another.';
                return $out;
            }
            if ($payment['status'] !== 'pending') {
                $out['error'] = 'This payment is already closed. Start a new one.';
                return $out;
            }
            pg_note_autorenew($payment, !empty($in['auto_renew']));
            $r = payments_online_start(row('SELECT * FROM payments WHERE id = :id', ['id' => $payment['id']]) ?: $payment, $action);
            $out['ok'] = $r['ok'];
            $out['error'] = $r['error'];
            $out['redirect'] = $r['url'];
            $out['message'] = $r['ok'] ? 'Taking you to a secure payment page…' : '';
            $out['state'] = 'open';
            return $out;
        case 'check':
            // the member says they paid to the Paybill: look for it, and ask the gateway about any hosted payment
            $fresh = row('SELECT * FROM payments WHERE id = :id', ['id' => $payment['id']]) ?: $payment;
            if (!empty($fresh['gateway_ref']) && $fresh['status'] === 'pending') {
                payments_online_verify($fresh);
            }
            $out['ok'] = true;
            $out['message'] = 'Checked.';
            $o = $finish($out);
            if ($o['state'] !== 'paid') {
                $o['ok'] = false;
                $o['error'] = 'We have not received this payment yet. It can take a minute. We update this page as soon as it arrives.';
            }
            return $o;
        case 'test':
            $r = payments_test_finish($payment, ($in['result'] ?? 'success') !== 'fail');
            $out['ok'] = $r['ok'];
            $out['error'] = $r['error'];
            $out['message'] = $r['ok'] ? (($in['result'] ?? 'success') !== 'fail' ? 'Test payment completed.' : 'Test payment marked as failed.') : '';
            return $finish($out);
        case 'cancel':
            if (!payments_cancel($payment)) {
                $out['error'] = $payment['status'] === 'pending' ? 'You already sent payment details for this order, so it cannot be cancelled here. Contact support if something is wrong.' : 'This payment is already closed.';
                return $out;
            }
            $out['ok'] = true;
            $out['message'] = 'Payment cancelled.';
            return $finish($out);
        case 'retry':
            if (!in_array($payment['status'], ['failed', 'cancelled'], true)) {
                $out['error'] = 'This payment cannot be repeated.';
                return $out;
            }
            // Plans and boosts are priced again from today's settings; other types repeat the stored order.
            $meta = payment_meta($payment);
            $again = null;
            if ($payment['payment_type'] === 'subscription' && !empty($meta['plan_slug'])) {
                $again = checkout_subscription((string)$meta['plan_slug'], (string)($meta['period'] ?? 'monthly'));
            } elseif ($payment['payment_type'] === 'boost') {
                $again = checkout_boost((int)($meta['pack_id'] ?? 0));
            } elseif ($payment['payment_type'] === 'featured') {
                $again = checkout_featured((int)($meta['pack_id'] ?? 0));
            } elseif ($payment['payment_type'] === 'host_listing') {
                $again = checkout_host_listing((string)($meta['period'] ?? 'monthly'));
            }
            if ($again !== null && !$again['ok']) {
                $out['error'] = $again['error'];
                return $out;
            }
            $new = $again['payment'] ?? payment_clone_for_retry($payment);
            if (!$new) {
                $out['error'] = 'This payment cannot be repeated.';
                return $out;
            }
            $out['ok'] = true;
            $out['payment_id'] = (int)$new['id'];
            $out['redirect'] = '/pay/' . (int)$new['id'];
            $out['state'] = 'open';
            return $out;
    }
    $out['error'] = 'Unknown action.';
    return $out;
}
