<?php
/**
 * Online payment gateways: Paystack, PayPal and cards (Stripe, or Paystack cards).
 *
 * All three work the same way and never touch card numbers: we create a hosted checkout, send the member there, and confirm the
 * result in two independent ways (the member coming back to /pay/return/{gateway}, and the gateway's signed webhook). Both go through
 * the same "apply" function, which checks the amount and completes the order once.
 *
 * Every call fails softly: a timeout or error becomes a plain sentence for the member and a line in storage/logs/payments.log.
 * Tests replace the network with $GLOBALS['__payments_http'] (see payments_http()).
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

function pay_log(string $message, array $ctx = []): void
{
    app_log('payments', $message, $ctx);
}

function pg_site_currency(): string
{
    return strtoupper(trim((string)setting('currency'))) ?: 'KES';
}

/** Currencies the gateways count without cents. */
function pg_zero_decimal(string $currency): bool
{
    return in_array(strtoupper($currency), ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'], true);
}

/** Smallest currency unit: 1250.50 KES -> 125050. */
function pg_minor_units(float $amount, string $currency): int
{
    return (int)round($amount * (pg_zero_decimal($currency) ? 1 : 100));
}

function pg_user_email(int $uid): string
{
    return (string)val('SELECT email FROM users WHERE id = :id', ['id' => $uid], '');
}

function pg_find_payment(string $column, string $value): ?array
{
    if (!in_array($column, ['gateway_ref', 'reference', 'id'], true) || $value === '') {
        return null;
    }
    return row('SELECT * FROM payments WHERE ' . $column . ' = :v ORDER BY id DESC LIMIT 1', ['v' => $value]) ?: null;
}

/** Remember an event id so a webhook sent twice does the work once. Returns false when it was seen before. */
function pg_event_once(string $gateway, string $eventId, string $type = '', ?int $paymentId = null, string $outcome = ''): bool
{
    if ($eventId === '') {
        return true;
    }
    $n = q('INSERT IGNORE INTO payment_events (gateway, event_id, event_type, payment_id, outcome) VALUES (:g, :e, :t, :p, :o)',
        ['g' => $gateway, 'e' => mb_substr($eventId, 0, 120), 't' => mb_substr($type, 0, 80), 'p' => $paymentId, 'o' => mb_substr($outcome, 0, 160)])->rowCount();
    return $n > 0;
}

/**
 * Finish a payment that a gateway says is paid. The payment may already have been cancelled or timed out on our side;
 * a success that arrives late still means the money moved, so it is honoured once (fulfilment runs a single time).
 * Returns completed | duplicate.
 */
function pg_complete(array $payment, string $gateway, string $txnId, string $method = ''): string
{
    $fresh = row('SELECT * FROM payments WHERE id = :id', ['id' => $payment['id']]) ?: $payment;
    if ($fresh['status'] === 'completed' || !empty($fresh['fulfilled_at'])) {
        return 'duplicate';
    }
    $set = ['gateway' => $gateway, 'gateway_txn' => mb_substr($txnId, 0, 120), 'mpesa_result_desc' => 'Paid'];
    if ($method !== '') {
        $set['payment_method'] = $method;
    }
    update('payments', $set, ['id' => (int)$fresh['id']]);
    payment_complete((int)$fresh['id']);
    return 'completed';
}

/** The money arrived but is not the right amount or currency: keep the order closed and tell the admin, never deliver. */
function pg_mismatch(array $payment, string $gateway, string $detail): string
{
    payment_fail((int)$payment['id'], 'failed', 'Amount mismatch: ' . $detail . '. Check with support.');
    pay_log('amount mismatch', ['gateway' => $gateway, 'payment' => $payment['id'], 'detail' => $detail]);
    admin_alert('payment', 'A payment needs a look', ucfirst($gateway) . ' reported a payment that does not match order ' . $payment['reference'] . ': ' . $detail . '.', '/admin/payments/' . (int)$payment['id']);
    return 'mismatch';
}

/** Where the gateway sends the member back. */
function pg_return_url(string $gateway): string
{
    return abs_url('/pay/return/' . $gateway);
}

/** Remember that the member asked for automatic renewal on this order (read when it is paid). */
function pg_note_autorenew(array $payment, bool $on): void
{
    if ($payment['payment_type'] === 'subscription' && autorenew_enabled()) {
        payment_meta_merge((int)$payment['id'], ['auto_renew' => $on ? 1 : 0]);
    }
}

// ---------------------------------------------------------------------------
// Saved cards (a token from the gateway, never a card number)
// ---------------------------------------------------------------------------

function pay_saved_list(int $uid): array
{
    return rows('SELECT * FROM pay_saved_methods WHERE user_id = :u AND is_active = 1 ORDER BY id DESC', ['u' => $uid]);
}

function pay_saved_get(int $uid, int $id): ?array
{
    return row('SELECT * FROM pay_saved_methods WHERE id = :id AND user_id = :u AND is_active = 1', ['id' => $id, 'u' => $uid]) ?: null;
}

/** Store (or refresh) a reusable card token. Returns its id. */
function pay_saved_add(int $uid, string $gateway, string $token, string $fingerprint, array $info = []): int
{
    $fingerprint = mb_substr($fingerprint !== '' ? $fingerprint : $token, 0, 160);
    $data = [
        'token' => mb_substr($token, 0, 160), 'customer' => isset($info['customer']) ? mb_substr((string)$info['customer'], 0, 160) : null,
        'brand' => isset($info['brand']) ? mb_substr(ucfirst(strtolower((string)$info['brand'])), 0, 30) : null,
        'last4' => isset($info['last4']) ? preg_replace('/\D/', '', (string)$info['last4']) : null,
        'exp_month' => isset($info['exp_month']) ? (int)$info['exp_month'] : null, 'exp_year' => isset($info['exp_year']) ? (int)$info['exp_year'] : null, 'is_active' => 1,
    ];
    $ex = row('SELECT id FROM pay_saved_methods WHERE user_id = :u AND gateway = :g AND fingerprint = :f', ['u' => $uid, 'g' => $gateway, 'f' => $fingerprint]);
    if ($ex) {
        // a repeat with thinner details must not wipe what we already know about the card
        update('pay_saved_methods', array_filter($data, fn($v) => $v !== null), ['id' => (int)$ex['id']]);
        return (int)$ex['id'];
    }
    return insert('pay_saved_methods', $data + ['user_id' => $uid, 'gateway' => $gateway, 'fingerprint' => $fingerprint]);
}

function pay_saved_remove(int $uid, int $id): bool
{
    $n = q('UPDATE pay_saved_methods SET is_active = 0 WHERE id = :id AND user_id = :u', ['id' => $id, 'u' => $uid])->rowCount();
    if ($n) {
        // a plan that renewed with this card falls back to a reminder instead of failing
        q("UPDATE user_subscriptions SET renew_method = 'link', renew_saved_id = NULL WHERE user_id = :u AND renew_saved_id = :id", ['u' => $uid, 'id' => $id]);
    }
    return $n > 0;
}

function pay_saved_label(array $s): string
{
    $brand = trim((string)($s['brand'] ?? '')) ?: 'Card';
    return $brand . ' ending ' . ($s['last4'] ?: '••••');
}

/** Save the card a payment was made with, when the member asked for automatic renewal. Records its id on the payment. */
function pg_remember_card(array $payment, string $gateway, string $token, string $fingerprint, array $info): void
{
    $meta = payment_meta($payment);
    if (empty($meta['auto_renew']) || $token === '' || !autorenew_enabled()) {
        return;
    }
    $id = pay_saved_add((int)$payment['user_id'], $gateway, $token, $fingerprint, $info);
    payment_meta_merge((int)$payment['id'], ['renew_saved_id' => $id]);
}

// ---------------------------------------------------------------------------
// Paystack
// ---------------------------------------------------------------------------

function paystack_ready(): bool
{
    return trim((string)setting('paystack_secret_key')) !== '';
}

/** @return array ['ok'=>bool, 'status'=>int, 'data'=>array, 'message'=>string, 'error'=>string] */
function paystack_call(string $method, string $path, ?array $json = null): array
{
    $r = payments_http($method, 'https://api.paystack.co' . $path, [
        'Authorization: Bearer ' . trim((string)setting('paystack_secret_key')), 'Content-Type: application/json', 'Accept: application/json',
    ], $json !== null ? (string)json_encode($json) : null, 25);
    $j = json_decode($r['body'], true);
    $ok = $r['status'] >= 200 && $r['status'] < 300 && is_array($j) && !empty($j['status']);
    if (!$ok) {
        pay_log('paystack call failed', ['path' => $path, 'status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 300)]);
    }
    return ['ok' => $ok, 'status' => $r['status'], 'data' => is_array($j) && is_array($j['data'] ?? null) ? $j['data'] : [], 'message' => is_array($j) ? (string)($j['message'] ?? '') : '', 'error' => $r['error']];
}

/** Start a Paystack checkout. $channels limits it (for example ['card']). @return array ['ok'=>bool, 'url'=>string, 'error'=>string] */
function paystack_start(array $payment, string $storedMethod = 'paystack', array $channels = []): array
{
    if (!paystack_ready()) {
        return ['ok' => false, 'url' => '', 'error' => 'Paystack is not available right now. Please choose another way to pay.'];
    }
    if ($payment['status'] !== 'pending') {
        return ['ok' => false, 'url' => '', 'error' => 'This payment is already closed. Start a new one.'];
    }
    $email = pg_user_email((int)$payment['user_id']);
    if (!valid_email($email)) {
        return ['ok' => false, 'url' => '', 'error' => 'Add a valid email address to your account first.'];
    }
    if (!rate_limit('gwstart:' . $payment['id'], 8, 600)) {
        return ['ok' => false, 'url' => '', 'error' => 'Too many attempts. Wait a few minutes before trying again.'];
    }
    $cur = pg_site_currency();
    $ref = $payment['reference'] . '-' . strtoupper(substr(random_token(4), 0, 6));
    $body = [
        'email' => $email, 'amount' => pg_minor_units((float)$payment['amount'], $cur), 'currency' => $cur, 'reference' => $ref,
        'callback_url' => pg_return_url('paystack'),
        'metadata' => ['payment_id' => (int)$payment['id'], 'order' => $payment['reference'], 'cancel_action' => abs_url('/pay/' . (int)$payment['id'])],
    ];
    if (!$channels) {
        $pick = (string)setting('paystack_channels');
        $channels = $pick === 'card' ? ['card'] : ($pick === 'mobile_money' ? ['mobile_money'] : ($pick === 'bank' ? ['bank', 'bank_transfer'] : []));
    }
    if ($channels) {
        $body['channels'] = array_values($channels);
    }
    $r = paystack_call('POST', '/transaction/initialize', $body);
    $url = (string)($r['data']['authorization_url'] ?? '');
    if (!$r['ok'] || $url === '') {
        return ['ok' => false, 'url' => '', 'error' => $r['status'] === 0 ? 'We could not reach Paystack. Please try again in a moment.' : 'Paystack could not start this payment. Please try again or choose another way to pay.'];
    }
    update('payments', ['gateway' => 'paystack', 'gateway_ref' => $ref, 'payment_method' => $storedMethod], ['id' => (int)$payment['id']]);
    return ['ok' => true, 'url' => $url, 'error' => ''];
}

/** The order reference inside a Paystack reference ("CH261016ABC123-X7Y8Z9" -> "CH261016ABC123"). */
function paystack_order_ref(string $ref): string
{
    return strtoupper(explode('-', $ref, 2)[0]);
}

/**
 * Apply a Paystack transaction (from verify, the webhook or a saved-card charge) to its payment. Safe to repeat.
 * Returns completed | duplicate | mismatch | failed | pending.
 */
function paystack_apply(array $payment, array $tx): string
{
    if ($payment['status'] === 'completed') {
        return 'duplicate';
    }
    $status = (string)($tx['status'] ?? '');
    if ($status !== 'success') {
        if ($status === 'failed' && $payment['status'] === 'pending') {
            payment_fail((int)$payment['id'], 'failed', mb_substr((string)($tx['gateway_response'] ?? 'Declined'), 0, 200));
            return 'failed';
        }
        return 'pending';
    }
    if (paystack_order_ref((string)($tx['reference'] ?? '')) !== strtoupper((string)$payment['reference'])) {
        return pg_mismatch($payment, 'paystack', 'reference ' . ($tx['reference'] ?? '?') . ' is for another order');
    }
    $cur = pg_site_currency();
    $expected = pg_minor_units((float)$payment['amount'], $cur);
    if ((int)($tx['amount'] ?? 0) < $expected || strtoupper((string)($tx['currency'] ?? $cur)) !== $cur) {
        return pg_mismatch($payment, 'paystack', 'received ' . ($tx['amount'] ?? 0) . ' ' . ($tx['currency'] ?? '') . ' (minor units), expected ' . $expected . ' ' . $cur);
    }
    $auth = is_array($tx['authorization'] ?? null) ? $tx['authorization'] : [];
    $channel = (string)($tx['channel'] ?? '');
    $isCard = $channel === 'card' || ($auth['channel'] ?? '') === 'card';
    if ($isCard && !empty($auth['reusable']) && !empty($auth['authorization_code'])) {
        pg_remember_card($payment, 'paystack', (string)$auth['authorization_code'], (string)($auth['signature'] ?? $auth['authorization_code']), [
            'customer' => (string)($tx['customer']['email'] ?? pg_user_email((int)$payment['user_id'])), 'brand' => $auth['brand'] ?? $auth['card_type'] ?? '',
            'last4' => $auth['last4'] ?? '', 'exp_month' => $auth['exp_month'] ?? null, 'exp_year' => $auth['exp_year'] ?? null,
        ]);
    }
    $keep = in_array($payment['payment_method'], ['card', 'paystack'], true) ? (string)$payment['payment_method'] : 'paystack';
    return pg_complete($payment, 'paystack', (string)($tx['id'] ?? $tx['reference'] ?? ''), $keep);
}

/** Ask Paystack about the payment's latest attempt. @return array ['ok'=>bool, 'outcome'=>string, 'error'=>string] */
function paystack_verify(array $payment): array
{
    $ref = (string)($payment['gateway_ref'] ?? '');
    if ($ref === '' || !paystack_ready()) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'No Paystack payment to check.'];
    }
    $r = paystack_call('GET', '/transaction/verify/' . rawurlencode($ref));
    if (!$r['ok']) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'We could not check with Paystack just now.'];
    }
    return ['ok' => true, 'outcome' => paystack_apply($payment, $r['data'] + ['reference' => $ref]), 'error' => ''];
}

/** Charge a saved card without the member being present (automatic renewal). @return array ['ok'=>bool, 'outcome'=>string, 'error'=>string] */
function paystack_charge_saved(array $payment, array $saved): array
{
    $cur = pg_site_currency();
    $ref = $payment['reference'] . '-' . strtoupper(substr(random_token(4), 0, 6));
    $email = (string)($saved['customer'] ?: pg_user_email((int)$payment['user_id']));
    update('payments', ['gateway' => 'paystack', 'gateway_ref' => $ref, 'payment_method' => 'card'], ['id' => (int)$payment['id']]);
    $r = paystack_call('POST', '/transaction/charge_authorization', [
        'authorization_code' => $saved['token'], 'email' => $email, 'amount' => pg_minor_units((float)$payment['amount'], $cur), 'currency' => $cur, 'reference' => $ref,
        'metadata' => ['payment_id' => (int)$payment['id'], 'order' => $payment['reference'], 'renewal' => true],
    ]);
    if (!$r['ok']) {
        return ['ok' => false, 'outcome' => 'failed', 'error' => $r['message'] !== '' ? $r['message'] : 'The charge did not go through.'];
    }
    $fresh = row('SELECT * FROM payments WHERE id = :id', ['id' => $payment['id']]) ?: $payment;
    $outcome = paystack_apply($fresh, $r['data'] + ['reference' => $ref]);
    return ['ok' => $outcome === 'completed', 'outcome' => $outcome, 'error' => $outcome === 'completed' ? '' : (string)($r['data']['gateway_response'] ?? 'The charge did not go through.')];
}

/** The signed webhook. Returns [httpStatus, body]. */
function paystack_handle_webhook(string $raw, string $signature): array
{
    $key = trim((string)setting('paystack_secret_key'));
    if ($key === '' || $signature === '' || !hash_equals(hash_hmac('sha512', $raw, $key), strtolower($signature))) {
        pay_log('paystack webhook rejected: bad signature');
        return [401, 'invalid signature'];
    }
    $j = json_decode($raw, true);
    $event = is_array($j) ? (string)($j['event'] ?? '') : '';
    $tx = is_array($j['data'] ?? null) ? $j['data'] : [];
    if ($event !== 'charge.success' || empty($tx['reference'])) {
        return [200, 'ignored'];
    }
    $payment = pg_find_payment('reference', paystack_order_ref((string)$tx['reference']));
    if (!$payment) {
        pay_log('paystack webhook for unknown order', ['reference' => $tx['reference']]);
        return [200, 'unknown order'];
    }
    $first = pg_event_once('paystack', (string)($tx['id'] ?? $tx['reference']) . ':' . $event, $event, (int)$payment['id']);
    $outcome = paystack_apply($payment, $tx);
    pay_log('paystack webhook', ['payment' => $payment['id'], 'outcome' => $outcome, 'first' => $first]);
    return [200, 'ok'];
}

// ---------------------------------------------------------------------------
// PayPal (Orders v2)
// ---------------------------------------------------------------------------

function paypal_config(): array
{
    $live = (string)setting('paypal_env') === 'live';
    return [
        'live' => $live, 'base' => $live ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com',
        'id' => trim((string)setting('paypal_client_id')), 'secret' => trim((string)setting('paypal_client_secret')),
        'currency' => strtoupper(trim((string)setting('paypal_currency'))) ?: 'USD', 'rate' => (float)setting('paypal_rate'),
        'webhook_id' => trim((string)setting('paypal_webhook_id')),
    ];
}

function paypal_ready(): bool
{
    $c = paypal_config();
    return $c['id'] !== '' && $c['secret'] !== '' && $c['rate'] > 0;
}

/** What PayPal charges for an order. PayPal does not accept every currency (not Kenyan shillings), so the price is converted at the rate the admin set. */
function paypal_amount(float $siteAmount): array
{
    $c = paypal_config();
    $cur = $c['currency'];
    if ($cur === pg_site_currency()) {
        $value = $siteAmount;
    } else {
        $value = $c['rate'] > 0 ? $siteAmount / $c['rate'] : 0.0;
    }
    $decimals = in_array($cur, ['JPY', 'HUF', 'TWD'], true) ? 0 : 2;
    return ['currency' => $cur, 'value' => number_format(round($value, $decimals), $decimals, '.', ''), 'rate' => $c['rate']];
}

/** @return array ['ok'=>bool, 'token'=>string, 'error'=>string] */
function paypal_token(): array
{
    $c = paypal_config();
    $key = 'paypal_token_' . substr(sha1($c['base'] . $c['id']), 0, 16);
    $cached = cache_get($key);
    if (is_string($cached) && $cached !== '') {
        return ['ok' => true, 'token' => $cached, 'error' => ''];
    }
    $r = payments_http('POST', $c['base'] . '/v1/oauth2/token', ['Authorization: Basic ' . base64_encode($c['id'] . ':' . $c['secret']), 'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'], 'grant_type=client_credentials', 20);
    $j = json_decode($r['body'], true);
    if ($r['status'] === 200 && is_array($j) && !empty($j['access_token'])) {
        cache_put($key, (string)$j['access_token'], max(60, (int)($j['expires_in'] ?? 3000) - 120));
        return ['ok' => true, 'token' => (string)$j['access_token'], 'error' => ''];
    }
    pay_log('paypal token failed', ['status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 300)]);
    return ['ok' => false, 'token' => '', 'error' => $r['status'] === 0 ? 'We could not reach PayPal. Please try again in a moment.' : 'PayPal is not available right now. Please choose another way to pay.'];
}

/** @return array ['ok'=>bool, 'status'=>int, 'data'=>array, 'error'=>string] */
function paypal_call(string $method, string $path, ?string $jsonBody = null, array $headers = []): array
{
    $t = paypal_token();
    if (!$t['ok']) {
        return ['ok' => false, 'status' => 0, 'data' => [], 'error' => $t['error']];
    }
    $r = payments_http($method, paypal_config()['base'] . $path, array_merge(['Authorization: Bearer ' . $t['token'], 'Content-Type: application/json', 'Accept: application/json'], $headers), $jsonBody, 25);
    $j = json_decode($r['body'], true);
    $ok = $r['status'] >= 200 && $r['status'] < 300 && is_array($j);
    if (!$ok) {
        pay_log('paypal call failed', ['path' => $path, 'status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 400)]);
    }
    return ['ok' => $ok, 'status' => $r['status'], 'data' => is_array($j) ? $j : [], 'error' => $r['error']];
}

/** Create a PayPal order and return where to send the member. @return array ['ok'=>bool, 'url'=>string, 'error'=>string] */
function paypal_start(array $payment): array
{
    if (!paypal_ready()) {
        return ['ok' => false, 'url' => '', 'error' => 'PayPal is not available right now. Please choose another way to pay.'];
    }
    if ($payment['status'] !== 'pending') {
        return ['ok' => false, 'url' => '', 'error' => 'This payment is already closed. Start a new one.'];
    }
    if (!rate_limit('gwstart:' . $payment['id'], 8, 600)) {
        return ['ok' => false, 'url' => '', 'error' => 'Too many attempts. Wait a few minutes before trying again.'];
    }
    $amt = paypal_amount((float)$payment['amount']);
    if ((float)$amt['value'] <= 0) {
        return ['ok' => false, 'url' => '', 'error' => 'PayPal cannot take this amount. Please choose another way to pay.'];
    }
    $order = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => (string)$payment['reference'], 'custom_id' => (string)$payment['reference'], 'invoice_id' => (string)$payment['reference'] . '-' . strtoupper(substr(random_token(3), 0, 4)),
            'description' => mb_substr((string)($payment['description'] ?: 'Order ' . $payment['reference']), 0, 120),
            'amount' => ['currency_code' => $amt['currency'], 'value' => $amt['value']],
        ]],
        'payment_source' => ['paypal' => ['experience_context' => [
            'brand_name' => mb_substr(site_name(), 0, 120), 'user_action' => 'PAY_NOW', 'shipping_preference' => 'NO_SHIPPING', 'landing_page' => 'LOGIN',
            'return_url' => pg_return_url('paypal'), 'cancel_url' => pg_return_url('paypal') . '?cancel=1&pid=' . (int)$payment['id'],
        ]]],
    ];
    $r = paypal_call('POST', '/v2/checkout/orders', (string)json_encode($order), ['PayPal-Request-Id: ' . $payment['reference'] . '-' . random_token(4)]);
    $id = (string)($r['data']['id'] ?? '');
    $url = '';
    foreach ((array)($r['data']['links'] ?? []) as $l) {
        if (is_array($l) && in_array($l['rel'] ?? '', ['payer-action', 'approve'], true) && !empty($l['href'])) {
            $url = (string)$l['href'];
            break;
        }
    }
    if (!$r['ok'] || $id === '' || $url === '') {
        return ['ok' => false, 'url' => '', 'error' => $r['status'] === 0 ? 'We could not reach PayPal. Please try again in a moment.' : 'PayPal could not start this payment. Please try again or choose another way to pay.'];
    }
    update('payments', ['gateway' => 'paypal', 'gateway_ref' => $id, 'payment_method' => 'paypal'], ['id' => (int)$payment['id']]);
    payment_meta_merge((int)$payment['id'], ['pp_currency' => $amt['currency'], 'pp_amount' => $amt['value'], 'pp_rate' => $amt['rate']]);
    return ['ok' => true, 'url' => $url, 'error' => ''];
}

/**
 * Apply a PayPal capture to its payment. $capture is one entry of purchase_units[0].payments.captures.
 * Returns completed | duplicate | mismatch | failed | pending.
 */
function paypal_apply_capture(array $payment, array $capture): string
{
    if ($payment['status'] === 'completed') {
        return 'duplicate';
    }
    $state = strtoupper((string)($capture['status'] ?? ''));
    if ($state === 'PENDING') {
        return 'pending';
    }
    if ($state !== 'COMPLETED') {
        if ($payment['status'] === 'pending') {
            payment_fail((int)$payment['id'], 'failed', 'PayPal did not complete the payment (' . strtolower($state ?: 'unknown') . ').');
        }
        return 'failed';
    }
    $meta = payment_meta($payment);
    $amt = isset($meta['pp_amount']) ? ['currency' => (string)$meta['pp_currency'], 'value' => (string)$meta['pp_amount']] : paypal_amount((float)$payment['amount']);
    $got = (float)($capture['amount']['value'] ?? 0);
    $cur = strtoupper((string)($capture['amount']['currency_code'] ?? ''));
    if ($cur !== strtoupper($amt['currency']) || $got + 0.009 < (float)$amt['value']) {
        return pg_mismatch($payment, 'paypal', 'received ' . $got . ' ' . $cur . ', expected ' . $amt['value'] . ' ' . $amt['currency']);
    }
    return pg_complete($payment, 'paypal', (string)($capture['id'] ?? ''), 'paypal');
}

/** The member came back from PayPal: capture the approved order. @return array ['ok'=>bool, 'outcome'=>string, 'error'=>string] */
function paypal_capture(array $payment): array
{
    $order = (string)($payment['gateway_ref'] ?? '');
    if ($order === '' || !paypal_ready()) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'No PayPal payment to finish.'];
    }
    if ($payment['status'] === 'completed') {
        return ['ok' => true, 'outcome' => 'duplicate', 'error' => ''];
    }
    $r = paypal_call('POST', '/v2/checkout/orders/' . rawurlencode($order) . '/capture', '{}', ['PayPal-Request-Id: cap-' . $payment['reference'] . '-' . substr(sha1($order), 0, 10)]);
    $data = $r['data'];
    if (!$r['ok'] && (($data['details'][0]['issue'] ?? '') === 'ORDER_ALREADY_CAPTURED')) {
        $g = paypal_call('GET', '/v2/checkout/orders/' . rawurlencode($order));
        $data = $g['data'];
        $r['ok'] = $g['ok'];
    }
    if (!$r['ok']) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'PayPal could not finish this payment. If you were charged, it will be confirmed shortly.'];
    }
    $cap = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
    if (!is_array($cap)) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'PayPal has not completed this payment yet.'];
    }
    return ['ok' => true, 'outcome' => paypal_apply_capture($payment, $cap), 'error' => ''];
}

/** Check a pending PayPal order without capturing (used by the reconcile job). */
function paypal_check_order(array $payment): array
{
    $order = (string)($payment['gateway_ref'] ?? '');
    if ($order === '' || !paypal_ready()) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => ''];
    }
    $g = paypal_call('GET', '/v2/checkout/orders/' . rawurlencode($order));
    if (!$g['ok']) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => ''];
    }
    $cap = $g['data']['purchase_units'][0]['payments']['captures'][0] ?? null;
    if (is_array($cap)) {
        return ['ok' => true, 'outcome' => paypal_apply_capture($payment, $cap), 'error' => ''];
    }
    return ['ok' => true, 'outcome' => 'pending', 'error' => ''];
}

/** PayPal webhook: the signature is checked by PayPal itself. Returns [httpStatus, body]. */
function paypal_handle_webhook(string $raw, array $headers): array
{
    $c = paypal_config();
    $h = array_change_key_case($headers, CASE_LOWER);
    if ($c['webhook_id'] === '' || !paypal_ready()) {
        return [503, 'webhook not set up'];
    }
    foreach (['paypal-transmission-id', 'paypal-transmission-time', 'paypal-transmission-sig', 'paypal-cert-url', 'paypal-auth-algo'] as $need) {
        if (empty($h[$need])) {
            return [400, 'missing header'];
        }
    }
    $verify = '{"auth_algo":' . json_encode((string)$h['paypal-auth-algo']) . ',"cert_url":' . json_encode((string)$h['paypal-cert-url']) . ',"transmission_id":' . json_encode((string)$h['paypal-transmission-id'])
        . ',"transmission_sig":' . json_encode((string)$h['paypal-transmission-sig']) . ',"transmission_time":' . json_encode((string)$h['paypal-transmission-time'])
        . ',"webhook_id":' . json_encode($c['webhook_id']) . ',"webhook_event":' . $raw . '}';
    $r = paypal_call('POST', '/v1/notifications/verify-webhook-signature', $verify);
    if (!$r['ok'] || ($r['data']['verification_status'] ?? '') !== 'SUCCESS') {
        pay_log('paypal webhook rejected: signature not verified');
        return [401, 'invalid signature'];
    }
    $j = json_decode($raw, true);
    $type = is_array($j) ? (string)($j['event_type'] ?? '') : '';
    $res = is_array($j['resource'] ?? null) ? $j['resource'] : [];
    if ($type !== 'PAYMENT.CAPTURE.COMPLETED') {
        return [200, 'ignored'];
    }
    $ref = (string)($res['custom_id'] ?? '');
    $payment = $ref !== '' ? pg_find_payment('reference', $ref) : null;
    if (!$payment) {
        pay_log('paypal webhook for unknown order', ['custom_id' => $ref]);
        return [200, 'unknown order'];
    }
    pg_event_once('paypal', (string)($j['id'] ?? $res['id'] ?? ''), $type, (int)$payment['id']);
    $outcome = paypal_apply_capture($payment, $res);
    pay_log('paypal webhook', ['payment' => $payment['id'], 'outcome' => $outcome]);
    return [200, 'ok'];
}

// ---------------------------------------------------------------------------
// Cards with Stripe (Checkout)
// ---------------------------------------------------------------------------

function stripe_ready(): bool
{
    return trim((string)setting('stripe_secret_key')) !== '';
}

/** Is the card option usable, through whichever gateway the admin chose? */
function card_ready(): bool
{
    return (string)setting('card_gateway') === 'paystack' ? paystack_ready() : stripe_ready();
}

/** @return array ['ok'=>bool, 'status'=>int, 'data'=>array, 'error'=>string] */
function stripe_call(string $method, string $path, array $form = []): array
{
    $body = $form ? http_build_query($form, '', '&', PHP_QUERY_RFC3986) : null;
    $url = 'https://api.stripe.com' . $path . ($method === 'GET' && $body ? '?' . $body : '');
    $r = payments_http($method, $url, ['Authorization: Bearer ' . trim((string)setting('stripe_secret_key')), 'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'], $method === 'GET' ? null : $body, 25);
    $j = json_decode($r['body'], true);
    $ok = $r['status'] >= 200 && $r['status'] < 300 && is_array($j);
    if (!$ok) {
        pay_log('stripe call failed', ['path' => $path, 'status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 400)]);
    }
    return ['ok' => $ok, 'status' => $r['status'], 'data' => is_array($j) ? $j : [], 'error' => $r['error']];
}

/** Start a Stripe Checkout session for a card payment. @return array ['ok'=>bool, 'url'=>string, 'error'=>string] */
function stripe_start(array $payment): array
{
    if (!stripe_ready()) {
        return ['ok' => false, 'url' => '', 'error' => 'Card payments are not available right now. Please choose another way to pay.'];
    }
    if ($payment['status'] !== 'pending') {
        return ['ok' => false, 'url' => '', 'error' => 'This payment is already closed. Start a new one.'];
    }
    if (!rate_limit('gwstart:' . $payment['id'], 8, 600)) {
        return ['ok' => false, 'url' => '', 'error' => 'Too many attempts. Wait a few minutes before trying again.'];
    }
    $cur = strtolower(pg_site_currency());
    $meta = payment_meta($payment);
    $form = [
        'mode' => 'payment', 'client_reference_id' => (string)$payment['reference'], 'customer_email' => pg_user_email((int)$payment['user_id']),
        'success_url' => pg_return_url('stripe') . '?session_id={CHECKOUT_SESSION_ID}', 'cancel_url' => abs_url('/pay/' . (int)$payment['id']),
        'payment_method_types' => ['card'],
        'line_items' => [['quantity' => 1, 'price_data' => ['currency' => $cur, 'unit_amount' => pg_minor_units((float)$payment['amount'], $cur),
            'product_data' => ['name' => mb_substr((string)($payment['description'] ?: 'Order ' . $payment['reference']), 0, 120)]]]],
        'metadata' => ['payment_id' => (string)(int)$payment['id'], 'order' => (string)$payment['reference']],
        'payment_intent_data' => ['metadata' => ['payment_id' => (string)(int)$payment['id'], 'order' => (string)$payment['reference']]],
    ];
    if (!valid_email((string)$form['customer_email'])) {
        unset($form['customer_email']);
    }
    if (!empty($meta['auto_renew']) && autorenew_enabled()) {
        $form['customer_creation'] = 'always';
        $form['payment_intent_data']['setup_future_usage'] = 'off_session';
    }
    $r = stripe_call('POST', '/v1/checkout/sessions', $form);
    $id = (string)($r['data']['id'] ?? '');
    $url = (string)($r['data']['url'] ?? '');
    if (!$r['ok'] || $id === '' || $url === '') {
        return ['ok' => false, 'url' => '', 'error' => $r['status'] === 0 ? 'We could not reach our card processor. Please try again in a moment.' : 'We could not start the card payment. Please try again or choose another way to pay.'];
    }
    update('payments', ['gateway' => 'stripe', 'gateway_ref' => $id, 'payment_method' => 'card'], ['id' => (int)$payment['id']]);
    return ['ok' => true, 'url' => $url, 'error' => ''];
}

/** Fetch a Checkout session with the card details expanded. */
function stripe_fetch_session(string $id): array
{
    return stripe_call('GET', '/v1/checkout/sessions/' . rawurlencode($id), ['expand' => ['payment_intent.payment_method']]);
}

/** Apply a Checkout session to its payment. Returns completed | duplicate | mismatch | pending | failed. */
function stripe_apply_session(array $payment, array $session): string
{
    if ($payment['status'] === 'completed') {
        return 'duplicate';
    }
    if (($session['payment_status'] ?? '') !== 'paid') {
        return ($session['status'] ?? '') === 'expired' ? 'failed' : 'pending';
    }
    if (strtoupper((string)($session['client_reference_id'] ?? '')) !== strtoupper((string)$payment['reference'])) {
        return pg_mismatch($payment, 'stripe', 'session is for order ' . ($session['client_reference_id'] ?? '?'));
    }
    $cur = pg_site_currency();
    $expected = pg_minor_units((float)$payment['amount'], $cur);
    if ((int)($session['amount_total'] ?? 0) < $expected || strtoupper((string)($session['currency'] ?? $cur)) !== $cur) {
        return pg_mismatch($payment, 'stripe', 'received ' . ($session['amount_total'] ?? 0) . ' ' . ($session['currency'] ?? '') . ' (minor units), expected ' . $expected . ' ' . $cur);
    }
    $pi = is_array($session['payment_intent'] ?? null) ? $session['payment_intent'] : [];
    $pm = is_array($pi['payment_method'] ?? null) ? $pi['payment_method'] : [];
    if ($pm && !empty($pm['id']) && is_array($pm['card'] ?? null) && !empty($session['customer'])) {
        pg_remember_card($payment, 'stripe', (string)$pm['id'], (string)($pm['card']['fingerprint'] ?? $pm['id']), [
            'customer' => (string)$session['customer'], 'brand' => $pm['card']['brand'] ?? '', 'last4' => $pm['card']['last4'] ?? '',
            'exp_month' => $pm['card']['exp_month'] ?? null, 'exp_year' => $pm['card']['exp_year'] ?? null,
        ]);
    }
    $txn = (string)($pi['id'] ?? (is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : ($session['id'] ?? '')));
    return pg_complete($payment, 'stripe', $txn, 'card');
}

/** The member came back from Stripe, or the job is checking a waiting payment. @return array ['ok'=>bool, 'outcome'=>string, 'error'=>string] */
function stripe_verify(array $payment): array
{
    $id = (string)($payment['gateway_ref'] ?? '');
    if ($id === '' || !stripe_ready()) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'No card payment to check.'];
    }
    $r = stripe_fetch_session($id);
    if (!$r['ok']) {
        return ['ok' => false, 'outcome' => 'pending', 'error' => 'We could not check the card payment just now.'];
    }
    return ['ok' => true, 'outcome' => stripe_apply_session($payment, $r['data']), 'error' => ''];
}

/** Charge a saved card off-session (automatic renewal). @return array ['ok'=>bool, 'outcome'=>string, 'error'=>string] */
function stripe_charge_saved(array $payment, array $saved): array
{
    $cur = strtolower(pg_site_currency());
    update('payments', ['gateway' => 'stripe', 'payment_method' => 'card'], ['id' => (int)$payment['id']]);
    $r = stripe_call('POST', '/v1/payment_intents', [
        'amount' => pg_minor_units((float)$payment['amount'], $cur), 'currency' => $cur, 'customer' => $saved['customer'], 'payment_method' => $saved['token'],
        'off_session' => 'true', 'confirm' => 'true', 'description' => mb_substr((string)$payment['description'], 0, 120),
        'metadata' => ['payment_id' => (string)(int)$payment['id'], 'order' => (string)$payment['reference'], 'renewal' => '1'],
    ]);
    $pi = $r['data'];
    if (!$r['ok'] || ($pi['status'] ?? '') !== 'succeeded') {
        $msg = (string)($pi['error']['message'] ?? $pi['last_payment_error']['message'] ?? 'The card was declined.');
        return ['ok' => false, 'outcome' => 'failed', 'error' => $msg];
    }
    $expected = pg_minor_units((float)$payment['amount'], $cur);
    if ((int)($pi['amount_received'] ?? $pi['amount'] ?? 0) < $expected) {
        return ['ok' => false, 'outcome' => pg_mismatch($payment, 'stripe', 'received ' . ($pi['amount_received'] ?? 0) . ', expected ' . $expected), 'error' => 'The amount charged did not match.'];
    }
    update('payments', ['gateway_ref' => (string)($pi['id'] ?? '')], ['id' => (int)$payment['id']]);
    return ['ok' => true, 'outcome' => pg_complete($payment, 'stripe', (string)($pi['id'] ?? ''), 'card'), 'error' => ''];
}

/** Check the Stripe-Signature header (t=timestamp,v1=hmac). */
function stripe_signature_ok(string $raw, string $header, string $secret, int $tolerance = 300, ?int $now = null): bool
{
    if ($secret === '' || $header === '') {
        return false;
    }
    $t = 0;
    $sigs = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') {
            $t = (int)$v;
        } elseif ($k === 'v1') {
            $sigs[] = $v;
        }
    }
    if ($t <= 0 || abs(($now ?? time()) - $t) > $tolerance) {
        return false;
    }
    $want = hash_hmac('sha256', $t . '.' . $raw, $secret);
    foreach ($sigs as $s) {
        if (hash_equals($want, $s)) {
            return true;
        }
    }
    return false;
}

function stripe_handle_webhook(string $raw, string $sigHeader): array
{
    if (!stripe_signature_ok($raw, $sigHeader, trim((string)setting('stripe_webhook_secret')))) {
        pay_log('stripe webhook rejected: bad signature');
        return [400, 'invalid signature'];
    }
    $j = json_decode($raw, true);
    $type = is_array($j) ? (string)($j['type'] ?? '') : '';
    $obj = is_array($j['data']['object'] ?? null) ? $j['data']['object'] : [];
    if (!in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true) || empty($obj['id'])) {
        return [200, 'ignored'];
    }
    $payment = pg_find_payment('gateway_ref', (string)$obj['id']) ?: (!empty($obj['client_reference_id']) ? pg_find_payment('reference', (string)$obj['client_reference_id']) : null);
    if (!$payment) {
        pay_log('stripe webhook for unknown order', ['session' => $obj['id']]);
        return [200, 'unknown order'];
    }
    pg_event_once('stripe', (string)($j['id'] ?? $obj['id']), $type, (int)$payment['id']);
    // Fetch the session again: it carries the card details and proves the event is real even if the secret ever leaked.
    $r = stripe_fetch_session((string)$obj['id']);
    $session = $r['ok'] ? $r['data'] : $obj;
    $outcome = stripe_apply_session($payment, $session);
    pay_log('stripe webhook', ['payment' => $payment['id'], 'outcome' => $outcome]);
    return [200, 'ok'];
}

// ---------------------------------------------------------------------------
// One entry point for the member's checkout and the cron job
// ---------------------------------------------------------------------------

/** Start a hosted checkout. $method: paystack | paypal | card. @return array ['ok'=>bool, 'url'=>string, 'error'=>string] */
function payments_online_start(array $payment, string $method): array
{
    switch ($method) {
        case 'paystack':
            return paystack_start($payment, 'paystack');
        case 'paypal':
            return paypal_start($payment);
        case 'card':
            return (string)setting('card_gateway') === 'paystack' ? paystack_start($payment, 'card', ['card']) : stripe_start($payment);
    }
    return ['ok' => false, 'url' => '', 'error' => 'Unknown payment method.'];
}

/** Check a waiting hosted payment with its gateway. @return array ['ok'=>bool, 'outcome'=>string, 'error'=>string] */
function payments_online_verify(array $payment): array
{
    switch ((string)($payment['gateway'] ?? '')) {
        case 'paystack':
            return paystack_verify($payment);
        case 'paypal':
            return paypal_check_order($payment);
        case 'stripe':
            return stripe_verify($payment);
    }
    return ['ok' => false, 'outcome' => 'pending', 'error' => ''];
}

/**
 * Cron: re-check payments that were sent to a gateway but never reported back (a closed tab, a missed webhook),
 * and close ones nobody finished after three days. Returns counts.
 */
function payments_online_housekeeping(): array
{
    $out = ['checked' => 0, 'completed' => 0, 'closed' => 0];
    $waiting = rows("SELECT * FROM payments WHERE status = 'pending' AND gateway IN ('paystack','paypal','stripe') AND gateway_ref IS NOT NULL
                     AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND created_at > DATE_SUB(NOW(), INTERVAL 3 DAY) ORDER BY id LIMIT 25");
    foreach ($waiting as $p) {
        $out['checked']++;
        $r = payments_online_verify($p);
        if ($r['outcome'] === 'completed') {
            $out['completed']++;
        }
        q('UPDATE payments SET updated_at = NOW() WHERE id = :id AND status = \'pending\'', ['id' => $p['id']]);
    }
    $out['closed'] = q("UPDATE payments SET status = 'cancelled', mpesa_result_desc = 'Not completed' WHERE status = 'pending' AND gateway IN ('paystack','paypal','stripe') AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)")->rowCount();
    return $out;
}
