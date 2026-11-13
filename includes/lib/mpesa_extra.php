<?php
/**
 * M-Pesa beyond the STK prompt:
 *
 *  C2B  a customer pays to your Paybill or till themselves (account number = the order reference). Safaricom calls our
 *       Validation address (optional) and Confirmation address; we match the payment to the order and turn it on.
 *  B2C  we pay money out to a phone (Host and gift-wallet payouts) from the admin screens, or automatically up to a limit.
 *  Test helpers for the admin setup page (token check, a KES 1 test prompt, a sandbox C2B simulation, a B2C test payout).
 *
 * Safaricom refuses callback addresses that contain words such as "mpesa", so every address here is neutral: /api/pay/...
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Addresses (each has its own secret so a leaked one only opens one door)
// ---------------------------------------------------------------------------

function mpesa_secret(string $key): string
{
    $s = (string)setting($key);
    if ($s === '') {
        $s = random_token(16);
        setting_set($key, $s);
    }
    return $s;
}

function mpesa_stk_callback_url(): string
{
    return abs_url('/api/pay/stk/' . mpesa_secret('mpesa_callback_secret'));
}

/** @return array [validation, confirmation] */
function mpesa_c2b_urls(): array
{
    $s = mpesa_secret('mpesa_c2b_secret');
    return [abs_url('/api/pay/c2b/' . $s . '/validate'), abs_url('/api/pay/c2b/' . $s . '/confirm')];
}

/** @return array [result, timeout] */
function mpesa_b2c_urls(): array
{
    $s = mpesa_secret('mpesa_b2c_secret');
    return [abs_url('/api/pay/b2c/' . $s . '/result'), abs_url('/api/pay/b2c/' . $s . '/timeout')];
}

/** Does a callback come from where it should? The secret in the address, and the optional IP list. */
function mpesa_callback_auth(string $settingKey, string $urlSecret, string $ip): bool
{
    $secret = (string)setting($settingKey);
    if ($secret === '' || $urlSecret === '' || !hash_equals($secret, $urlSecret)) {
        mpesa_log('callback rejected: wrong or missing secret', ['key' => $settingKey, 'ip' => $ip]);
        return false;
    }
    $ips = setting_lines('mpesa_callback_ips');
    if ($ips && !mpesa_ip_allowed($ip, $ips)) {
        mpesa_log('callback rejected: address not allowed', ['key' => $settingKey, 'ip' => $ip]);
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------------
// Connection tests for the admin setup page
// ---------------------------------------------------------------------------

/** Ask Safaricom for a fresh token with the saved key and secret. @return array ['ok'=>bool, 'message'=>string] */
function mpesa_test_connection(): array
{
    $c = mpesa_config();
    if ($c['key'] === '' || $c['secret'] === '') {
        return ['ok' => false, 'message' => 'Enter the consumer key and consumer secret first.'];
    }
    cache_clear('mpesa_token_');
    $t = mpesa_access_token();
    if ($t['ok']) {
        return ['ok' => true, 'message' => 'Connected to Safaricom (' . ($c['env'] === 'production' ? 'live' : 'sandbox') . '). Your consumer key and secret work.'];
    }
    return ['ok' => false, 'message' => $t['error'] . ' Check the key and secret and that the environment (sandbox or live) matches the app.'];
}

/** Send a KES 1 M-Pesa prompt to a phone to prove STK push works end to end. @return array ['ok'=>bool, 'message'=>string, 'payment_id'=>int] */
function mpesa_test_stk(int $adminId, string $phone): array
{
    if (!mpesa_ready()) {
        return ['ok' => false, 'message' => 'Fill in the short code, consumer key, consumer secret and passkey first.', 'payment_id' => 0];
    }
    $p = mpesa_phone($phone);
    if ($p === '') {
        return ['ok' => false, 'message' => 'Enter a Safaricom number such as 0712 345 678.', 'payment_id' => 0];
    }
    $pay = payment_create($adminId, 'test', 'mpesa', 1.0, 'M-Pesa connection test');
    $r = payments_mpesa_initiate($pay, $p);
    if (!$r['ok']) {
        payment_fail((int)$pay['id'], 'failed', $r['error']);
        return ['ok' => false, 'message' => $r['error'], 'payment_id' => (int)$pay['id']];
    }
    return ['ok' => true, 'message' => 'A KES 1 request was sent to ' . hosts_phone_local($p) . '. Approve it on the phone, then reload this page to see it marked as paid.', 'payment_id' => (int)$pay['id']];
}

// ---------------------------------------------------------------------------
// C2B: customers pay to the Paybill / till
// ---------------------------------------------------------------------------

function mpesa_c2b_shortcode(): string
{
    $s = trim((string)setting('mpesa_c2b_shortcode'));
    return $s !== '' ? $s : trim((string)setting('mpesa_shortcode'));
}

function mpesa_c2b_ready(): bool
{
    $c = mpesa_config();
    return (bool)setting('mpesa_c2b_enabled') && mpesa_c2b_shortcode() !== '' && $c['key'] !== '' && $c['secret'] !== '';
}

/** Tell Safaricom where to send C2B notifications. @return array ['ok'=>bool, 'message'=>string] */
function mpesa_c2b_register(): array
{
    $c = mpesa_config();
    if (mpesa_c2b_shortcode() === '' || $c['key'] === '' || $c['secret'] === '') {
        return ['ok' => false, 'message' => 'Enter the short code, consumer key and consumer secret first.'];
    }
    $tok = mpesa_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'message' => $tok['error']];
    }
    [$validate, $confirm] = mpesa_c2b_urls();
    $ver = (string)setting('mpesa_c2b_api') === 'v1' ? 'v1' : 'v2';
    $body = ['ShortCode' => mpesa_c2b_shortcode(), 'ResponseType' => (string)setting('mpesa_c2b_response') === 'Cancelled' ? 'Cancelled' : 'Completed', 'ConfirmationURL' => $confirm, 'ValidationURL' => $validate];
    $r = payments_http('POST', $c['base'] . '/mpesa/c2b/' . $ver . '/registerurl', ['Authorization: Bearer ' . $tok['token'], 'Content-Type: application/json'], (string)json_encode($body), 25);
    $j = json_decode($r['body'], true);
    $okCode = is_array($j) && ((string)($j['ResponseCode'] ?? $j['ResponseDescription'] ?? '') === '0' || stripos((string)($j['ResponseDescription'] ?? ''), 'success') !== false);
    if ($r['status'] === 200 && $okCode) {
        setting_set('mpesa_c2b_registered_at', date('Y-m-d H:i:s'));
        mpesa_log('c2b urls registered', ['shortcode' => $body['ShortCode'], 'version' => $ver]);
        return ['ok' => true, 'message' => 'Registered. Safaricom will now tell this site about every payment made to ' . $body['ShortCode'] . '.'];
    }
    mpesa_log('c2b register failed', ['status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 400)]);
    $raw = strtolower($r['body']);
    if (strpos($raw, 'duplicate') !== false) {
        return ['ok' => false, 'message' => 'Safaricom says these addresses are already registered. On a live short code you can register only once; delete the old ones in the Daraja portal (URL management) and try again.'];
    }
    if (strpos($raw, 'invalid') !== false && (strpos($raw, 'url') !== false || strpos($raw, 'confirmation') !== false)) {
        return ['ok' => false, 'message' => 'Safaricom did not accept the addresses. They must be public https addresses. A site that is only on localhost or has no valid SSL certificate cannot be registered.'];
    }
    return ['ok' => false, 'message' => mpesa_error_message(is_array($j) ? $j : null, $r['status'])];
}

/** Sandbox only: ask Safaricom to simulate a customer paying to the short code. @return array ['ok'=>bool, 'message'=>string] */
function mpesa_c2b_simulate(float $amount, string $reference): array
{
    $c = mpesa_config();
    if ($c['env'] !== 'sandbox') {
        return ['ok' => false, 'message' => 'Simulation only works in the sandbox. On a live short code, pay from a real phone.'];
    }
    $tok = mpesa_access_token();
    if (!$tok['ok']) {
        return ['ok' => false, 'message' => $tok['error']];
    }
    $body = ['ShortCode' => mpesa_c2b_shortcode(), 'CommandID' => 'CustomerPayBillOnline', 'Amount' => mpesa_amount($amount), 'Msisdn' => '254708374149', 'BillRefNumber' => mb_substr($reference, 0, 20)];
    $r = payments_http('POST', $c['base'] . '/mpesa/c2b/v1/simulate', ['Authorization: Bearer ' . $tok['token'], 'Content-Type: application/json'], (string)json_encode($body), 25);
    $j = json_decode($r['body'], true);
    if ($r['status'] === 200 && is_array($j) && (string)($j['ResponseCode'] ?? $j['ResponseDescription'] ?? '') !== '' && stripos((string)($j['ResponseDescription'] ?? ''), 'accept') !== false) {
        return ['ok' => true, 'message' => 'Safaricom accepted the simulated payment. It appears in the list below within a few seconds.'];
    }
    return ['ok' => false, 'message' => mpesa_error_message(is_array($j) ? $j : null, $r['status'])];
}

/** An account number typed by a customer ("ch 261016-abc123") as an order reference ("CH261016ABC123"). */
function mpesa_c2b_ref(string $raw): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw) ?? '');
}

/** The order a C2B account number points to, if it can still be paid. */
function mpesa_c2b_find_payment(string $billRef): ?array
{
    $ref = mpesa_c2b_ref($billRef);
    if ($ref === '') {
        return null;
    }
    return row('SELECT * FROM payments WHERE reference = :r LIMIT 1', ['r' => $ref]) ?: null;
}

function mpesa_c2b_ack(int $status, string $desc = 'Accepted', string $code = '0'): array
{
    return [$status, ['ResultCode' => $code, 'ResultDesc' => $desc]];
}

/** Safaricom asks whether to accept a payment. Only used when the short code has validation switched on. */
function mpesa_c2b_validate(array $payload, string $urlSecret, string $ip): array
{
    if (!mpesa_callback_auth('mpesa_c2b_secret', $urlSecret, $ip)) {
        return mpesa_c2b_ack(403, 'Forbidden', 'C2B00016');
    }
    if (!(bool)setting('mpesa_c2b_validation')) {
        return mpesa_c2b_ack(200);
    }
    $p = mpesa_c2b_find_payment((string)($payload['BillRefNumber'] ?? ''));
    if (!$p || !in_array($p['status'], ['pending', 'cancelled', 'failed'], true)) {
        mpesa_log('c2b validation refused', ['ref' => $payload['BillRefNumber'] ?? '', 'found' => $p ? $p['status'] : 'no']);
        return mpesa_c2b_ack(200, 'Rejected', 'C2B00012');
    }
    $amount = isset($payload['TransAmount']) && is_numeric($payload['TransAmount']) ? (float)$payload['TransAmount'] : 0.0;
    if ($amount + 0.009 < (float)$p['amount']) {
        return mpesa_c2b_ack(200, 'Rejected', 'C2B00013');
    }
    return mpesa_c2b_ack(200);
}

/** Safaricom tells us a payment was made. Records it, and turns the order on when it matches. Always answers Accepted. */
function mpesa_c2b_confirm(array $payload, string $urlSecret, string $ip): array
{
    if (!mpesa_callback_auth('mpesa_c2b_secret', $urlSecret, $ip)) {
        return mpesa_c2b_ack(403, 'Forbidden', 'C2B00016');
    }
    try {
        $transId = preg_replace('/[^A-Za-z0-9]/', '', (string)($payload['TransID'] ?? '')) ?? '';
        if ($transId === '' || !isset($payload['TransAmount']) || !is_numeric($payload['TransAmount'])) {
            mpesa_log('c2b confirmation malformed', ['ip' => $ip]);
            return mpesa_c2b_ack(400, 'Malformed request', 'C2B00016');
        }
        $name = trim(implode(' ', array_filter([(string)($payload['FirstName'] ?? ''), (string)($payload['MiddleName'] ?? ''), (string)($payload['LastName'] ?? '')])));
        $msisdn = (string)($payload['MSISDN'] ?? '');
        $data = [
            'trans_id' => mb_substr($transId, 0, 30), 'trans_type' => mb_substr((string)($payload['TransactionType'] ?? ''), 0, 30), 'trans_time' => mb_substr((string)($payload['TransTime'] ?? ''), 0, 20),
            'amount' => (float)$payload['TransAmount'], 'shortcode' => mb_substr((string)($payload['BusinessShortCode'] ?? ''), 0, 20), 'bill_ref' => mb_substr(trim((string)($payload['BillRefNumber'] ?? '')), 0, 60),
            'msisdn' => mb_substr($msisdn, 0, 80), 'payer_name' => mb_substr($name, 0, 120), 'org_balance' => mb_substr((string)($payload['OrgAccountBalance'] ?? ''), 0, 30), 'status' => 'unmatched',
        ];
        $n = q('INSERT IGNORE INTO mpesa_c2b_transactions (trans_id, trans_type, trans_time, amount, shortcode, bill_ref, msisdn, payer_name, org_balance, status)
                VALUES (:trans_id, :trans_type, :trans_time, :amount, :shortcode, :bill_ref, :msisdn, :payer_name, :org_balance, :status)', $data)->rowCount();
        if (!$n) {
            return mpesa_c2b_ack(200); // Safaricom sent it twice
        }
        $txId = (int)val('SELECT id FROM mpesa_c2b_transactions WHERE trans_id = :t', ['t' => $data['trans_id']], 0);
        $payment = mpesa_c2b_find_payment($data['bill_ref']);
        $outcome = mpesa_c2b_apply($txId, $payment);
        mpesa_log('c2b confirmation', ['trans' => $transId, 'ref' => $data['bill_ref'], 'amount' => $data['amount'], 'outcome' => $outcome]);
        if ($outcome !== 'matched') {
            admin_alert('payment', 'M-Pesa payment needs matching', money($data['amount']) . ' arrived from ' . ($name !== '' ? $name : 'a customer') . ' with account number "' . $data['bill_ref'] . '" (' . $outcome . '). Match it on the M-Pesa setup page.', '/admin/payment-methods/mpesa?tab=c2b');
        }
        return mpesa_c2b_ack(200);
    } catch (Throwable $e) {
        app_log('error', 'mpesa c2b confirmation failed: ' . $e->getMessage());
        return mpesa_c2b_ack(500, 'Server error', 'C2B00016');
    }
}

/**
 * Match a recorded C2B transaction to an order and turn it on if the amount is enough.
 * Returns matched | unmatched | underpaid | duplicate.
 */
function mpesa_c2b_apply(int $txId, ?array $payment, string $via = 'matched'): string
{
    $tx = row('SELECT * FROM mpesa_c2b_transactions WHERE id = :id', ['id' => $txId]);
    if (!$tx) {
        return 'unmatched';
    }
    $set = fn(string $status, ?int $pid, string $note) => update('mpesa_c2b_transactions', ['status' => $status, 'payment_id' => $pid, 'note' => mb_substr($note, 0, 250)], ['id' => $txId]);
    if (!$payment) {
        $set('unmatched', null, 'No order has this account number.');
        return 'unmatched';
    }
    $pid = (int)$payment['id'];
    if ($payment['status'] === 'completed' || !empty($payment['fulfilled_at'])) {
        $set('duplicate', $pid, 'Order ' . $payment['reference'] . ' was already paid.');
        return 'duplicate';
    }
    if ((float)$tx['amount'] + 0.009 < (float)$payment['amount']) {
        $set('underpaid', $pid, 'Paid ' . money((float)$tx['amount']) . ', order ' . $payment['reference'] . ' is ' . money((float)$payment['amount']) . '.');
        return 'underpaid';
    }
    $upd = ['payment_method' => 'mpesa', 'gateway' => null];
    if (preg_match('/^254[17]\d{8}$/', (string)$tx['msisdn'])) {
        $upd['mpesa_phone'] = (string)$tx['msisdn'];
    }
    update('payments', $upd, ['id' => $pid]);
    payment_complete($pid, ['mpesa_receipt' => (string)$tx['trans_id'], 'mpesa_result_desc' => 'Paid to our Paybill or till']);
    $set($via === 'manual' ? 'manual' : 'matched', $pid, 'Order ' . $payment['reference']);
    return 'matched';
}

/** Admin: match a transaction that arrived with a wrong account number to an order. @return array ['ok'=>bool, 'error'=>string, 'message'=>string] */
function mpesa_c2b_match(int $txId, string $reference): array
{
    $tx = row('SELECT * FROM mpesa_c2b_transactions WHERE id = :id', ['id' => $txId]);
    if (!$tx) {
        return ['ok' => false, 'error' => 'That payment no longer exists.', 'message' => ''];
    }
    if (in_array($tx['status'], ['matched', 'manual'], true)) {
        return ['ok' => false, 'error' => 'This payment is already matched.', 'message' => ''];
    }
    $p = mpesa_c2b_find_payment($reference);
    if (!$p) {
        return ['ok' => false, 'error' => 'No order has the reference ' . mpesa_c2b_ref($reference) . '.', 'message' => ''];
    }
    $out = mpesa_c2b_apply($txId, $p, 'manual');
    if ($out === 'matched') {
        return ['ok' => true, 'error' => '', 'message' => 'Matched. Order ' . $p['reference'] . ' is now paid and delivered.'];
    }
    return ['ok' => false, 'error' => $out === 'underpaid' ? 'The amount is lower than the order. Ask the customer to pay the rest, or approve the order by hand on the Payments page.' : ($out === 'duplicate' ? 'That order was already paid.' : 'Could not match it.'), 'message' => ''];
}

/** Used when a member types the M-Pesa code of a payment already received on the Paybill. Returns true when it completed the order. */
function mpesa_c2b_try_code(array $payment, string $code): bool
{
    if (!(bool)setting('mpesa_c2b_enabled')) {
        return false;
    }
    $tx = row("SELECT * FROM mpesa_c2b_transactions WHERE trans_id = :t AND status IN ('unmatched','underpaid')", ['t' => $code]);
    if (!$tx || (float)$tx['amount'] + 0.009 < (float)$payment['amount']) {
        return false;
    }
    return mpesa_c2b_apply((int)$tx['id'], $payment, 'manual') === 'matched';
}

// ---------------------------------------------------------------------------
// B2C: paying out to a phone
// ---------------------------------------------------------------------------

/** The encrypted initiator password Safaricom wants. A ready-made credential wins; otherwise it is made from the password and certificate. @return array [credential, error] */
function mpesa_security_credential(): array
{
    $ready = trim((string)setting('mpesa_security_credential'));
    if ($ready !== '') {
        return [$ready, ''];
    }
    $pw = (string)setting('mpesa_initiator_password');
    $cert = trim((string)setting('mpesa_cert'));
    if ($pw === '' || $cert === '') {
        return ['', 'Add the initiator password and the Safaricom certificate, or paste a ready-made security credential.'];
    }
    $key = @openssl_pkey_get_public($cert);
    if (!$key) {
        return ['', 'The certificate could not be read. Paste the whole certificate, including the BEGIN and END lines.'];
    }
    $enc = '';
    if (!@openssl_public_encrypt($pw, $enc, $key, OPENSSL_PKCS1_PADDING)) {
        return ['', 'The initiator password could not be encrypted with that certificate.'];
    }
    return [base64_encode($enc), ''];
}

function mpesa_b2c_shortcode(): string
{
    $s = trim((string)setting('mpesa_b2c_shortcode'));
    return $s !== '' ? $s : trim((string)setting('mpesa_shortcode'));
}

function mpesa_b2c_ready(): bool
{
    $c = mpesa_config();
    return (bool)setting('mpesa_b2c_enabled') && mpesa_b2c_shortcode() !== '' && $c['key'] !== '' && $c['secret'] !== '' && trim((string)setting('mpesa_initiator_name')) !== '' && mpesa_security_credential()[0] !== '';
}

/** The latest B2C request for a payout, or null. */
function mpesa_b2c_for(string $kind, int $refId): ?array
{
    return row('SELECT * FROM mpesa_b2c_requests WHERE kind = :k AND ref_id = :r ORDER BY id DESC LIMIT 1', ['k' => $kind, 'r' => $refId]) ?: null;
}

/** Send a Host payout or a gift-wallet payout through M-Pesa. @return array ['ok'=>bool, 'error'=>string, 'message'=>string] */
function mpesa_b2c_send_payout(string $kind, int $id, ?int $adminId): array
{
    if (!in_array($kind, ['host_payout', 'wallet_payout'], true)) {
        return ['ok' => false, 'error' => 'Unknown payout type.', 'message' => ''];
    }
    if (!mpesa_b2c_ready()) {
        return ['ok' => false, 'error' => 'M-Pesa payouts are not set up yet. Finish the Payouts (B2C) tab on the M-Pesa setup page.', 'message' => ''];
    }
    // lock the payout row so two clicks cannot create two requests
    $made = db_transaction(function () use ($kind, $id, $adminId) {
        if ($kind === 'host_payout') {
            $po = row('SELECT id, amount, destination AS phone_text, status FROM host_payouts WHERE id = :id FOR UPDATE', ['id' => $id]);
            $phone = $po ? mpesa_phone(preg_replace('/\s*-.*$/', '', (string)$po['phone_text']) ?? '') : '';
        } else {
            $po = row('SELECT id, amount, phone AS phone_text, status FROM wallet_payouts WHERE id = :id FOR UPDATE', ['id' => $id]);
            $phone = $po ? mpesa_phone((string)$po['phone_text']) : '';
        }
        if (!$po) {
            return ['error' => 'That payout request no longer exists.'];
        }
        if ($po['status'] !== 'requested') {
            return ['error' => 'This request was already ' . $po['status'] . '.'];
        }
        $prev = mpesa_b2c_for($kind, $id);
        if ($prev && in_array($prev['status'], ['queued', 'sent', 'timeout', 'completed'], true)) {
            return ['error' => $prev['status'] === 'completed' ? 'M-Pesa already sent this payout.' : 'An M-Pesa payment for this payout is already in progress. Check its status first.'];
        }
        if ($phone === '') {
            return ['error' => 'The phone number on this request is not a Safaricom number M-Pesa can pay. Pay it another way and mark it as paid.'];
        }
        $amount = (float)$po['amount'];
        $max = (float)setting('mpesa_b2c_max');
        if ($amount < 10 || ($max > 0 && $amount > $max)) {
            return ['error' => 'M-Pesa payouts can be KES 10 up to ' . money($max) . '. Pay this one another way and mark it as paid.'];
        }
        $reqId = insert('mpesa_b2c_requests', [
            'kind' => $kind, 'ref_id' => $id, 'phone' => $phone, 'amount' => $amount, 'remarks' => $kind === 'host_payout' ? 'Host payout' : 'Wallet payout',
            'originator_id' => 'CHB2C' . date('ymdHis') . strtoupper(substr(random_token(4), 0, 6)), 'status' => 'queued', 'requested_by' => $adminId,
        ]);
        return ['req' => $reqId];
    });
    if (isset($made['error'])) {
        return ['ok' => false, 'error' => $made['error'], 'message' => ''];
    }
    return mpesa_b2c_dispatch((int)$made['req']);
}

/** Send a small test payout (admin setup page). @return array ['ok'=>bool, 'error'=>string, 'message'=>string] */
function mpesa_b2c_send_test(string $phone, float $amount, ?int $adminId): array
{
    if (!mpesa_b2c_ready()) {
        return ['ok' => false, 'error' => 'Finish the payout settings and switch payouts on first.', 'message' => ''];
    }
    $p = mpesa_phone($phone);
    if ($p === '') {
        return ['ok' => false, 'error' => 'Enter a Safaricom number such as 0712 345 678.', 'message' => ''];
    }
    if ($amount < 10 || $amount > 1000) {
        return ['ok' => false, 'error' => 'A test payment can be KES 10 to KES 1,000.', 'message' => ''];
    }
    $reqId = insert('mpesa_b2c_requests', [
        'kind' => 'test', 'ref_id' => 0, 'phone' => $p, 'amount' => mpesa_amount($amount), 'remarks' => 'Payout test',
        'originator_id' => 'CHB2C' . date('ymdHis') . strtoupper(substr(random_token(4), 0, 6)), 'status' => 'queued', 'requested_by' => $adminId,
    ]);
    return mpesa_b2c_dispatch($reqId);
}

/** Call Safaricom for a queued request. @return array ['ok'=>bool, 'error'=>string, 'message'=>string] */
function mpesa_b2c_dispatch(int $reqId): array
{
    $req = row('SELECT * FROM mpesa_b2c_requests WHERE id = :id', ['id' => $reqId]);
    if (!$req || $req['status'] !== 'queued') {
        return ['ok' => false, 'error' => 'This payment was already sent.', 'message' => ''];
    }
    $c = mpesa_config();
    [$cred, $credErr] = mpesa_security_credential();
    $tok = mpesa_access_token();
    if ($credErr !== '' || !$tok['ok']) {
        update('mpesa_b2c_requests', ['status' => 'failed', 'result_desc' => mb_substr($credErr !== '' ? $credErr : $tok['error'], 0, 250)], ['id' => $reqId]);
        return ['ok' => false, 'error' => $credErr !== '' ? $credErr : $tok['error'], 'message' => ''];
    }
    [$result, $timeout] = mpesa_b2c_urls();
    $body = [
        'OriginatorConversationID' => $req['originator_id'], 'InitiatorName' => trim((string)setting('mpesa_initiator_name')), 'SecurityCredential' => $cred,
        'CommandID' => in_array((string)setting('mpesa_b2c_command'), ['BusinessPayment', 'SalaryPayment', 'PromotionPayment'], true) ? (string)setting('mpesa_b2c_command') : 'BusinessPayment',
        'Amount' => mpesa_amount((float)$req['amount']), 'PartyA' => mpesa_b2c_shortcode(), 'PartyB' => $req['phone'], 'Remarks' => mb_substr((string)($req['remarks'] ?: 'Payout'), 0, 100),
        'QueueTimeOutURL' => $timeout, 'ResultURL' => $result, 'Occasion' => mb_substr(site_name(), 0, 100),
    ];
    $r = payments_http('POST', $c['base'] . '/mpesa/b2c/v3/paymentrequest', ['Authorization: Bearer ' . $tok['token'], 'Content-Type: application/json'], (string)json_encode($body), 30);
    $j = json_decode($r['body'], true);
    if ($r['status'] === 200 && is_array($j) && (string)($j['ResponseCode'] ?? '') === '0') {
        update('mpesa_b2c_requests', ['status' => 'sent', 'conversation_id' => mb_substr((string)($j['ConversationID'] ?? ''), 0, 80), 'result_desc' => mb_substr((string)($j['ResponseDescription'] ?? 'Accepted for processing'), 0, 250)], ['id' => $reqId]);
        mpesa_log('b2c sent', ['request' => $reqId, 'amount' => $body['Amount'], 'conversation' => $j['ConversationID'] ?? '']);
        return ['ok' => true, 'error' => '', 'message' => 'Sent to M-Pesa. The payout is marked as paid when Safaricom confirms it, usually within a minute.'];
    }
    mpesa_log('b2c request failed', ['request' => $reqId, 'status' => $r['status'], 'error' => $r['error'], 'body' => mb_substr($r['body'], 0, 400)]);
    if ($r['status'] === 401) {
        cache_clear('mpesa_token_');
    }
    if ($r['status'] === 0) {
        // no answer at all: the money may or may not be on its way, so it must not be sent again until someone checks
        update('mpesa_b2c_requests', ['status' => 'timeout', 'result_desc' => 'No answer from M-Pesa. Check the M-Pesa portal before sending again.'], ['id' => $reqId]);
        return ['ok' => false, 'error' => 'M-Pesa did not answer, so we cannot tell if the money was sent. Check the M-Pesa portal, then use "Mark as not sent" if nothing left your account.', 'message' => ''];
    }
    $msg = mpesa_error_message(is_array($j) ? $j : null, $r['status']);
    update('mpesa_b2c_requests', ['status' => 'failed', 'result_desc' => mb_substr($msg, 0, 250)], ['id' => $reqId]);
    return ['ok' => false, 'error' => $msg, 'message' => ''];
}

/**
 * Parse the Result Safaricom posts after a B2C request.
 * @return array|null keys: originator, conversation, code, desc, receipt, amount, receiver
 */
function mpesa_b2c_parse_result(array $payload): ?array
{
    $r = $payload['Result'] ?? null;
    if (!is_array($r) || (empty($r['OriginatorConversationID']) && empty($r['ConversationID']))) {
        return null;
    }
    $params = [];
    foreach ((array)($r['ResultParameters']['ResultParameter'] ?? []) as $it) {
        if (is_array($it) && isset($it['Key'])) {
            $params[(string)$it['Key']] = $it['Value'] ?? null;
        }
    }
    return [
        'originator' => (string)($r['OriginatorConversationID'] ?? ''), 'conversation' => (string)($r['ConversationID'] ?? ''),
        'code' => isset($r['ResultCode']) && is_numeric($r['ResultCode']) ? (int)$r['ResultCode'] : -1, 'desc' => (string)($r['ResultDesc'] ?? ''),
        'receipt' => preg_replace('/[^A-Za-z0-9]/', '', (string)($params['TransactionReceipt'] ?? $r['TransactionID'] ?? '')) ?? '',
        'amount' => isset($params['TransactionAmount']) && is_numeric($params['TransactionAmount']) ? (float)$params['TransactionAmount'] : null,
        'receiver' => mb_substr((string)($params['ReceiverPartyPublicName'] ?? ''), 0, 120),
    ];
}

/** The money left: mark the payout paid and tell the member. */
function mpesa_b2c_settle(array $req, string $receipt): void
{
    $by = (int)($req['requested_by'] ?? 0);
    if ($req['kind'] === 'wallet_payout') {
        wallet_payout_mark_paid((int)$req['ref_id'], $receipt, 'Sent through M-Pesa', $by);
        return;
    }
    if ($req['kind'] === 'host_payout') {
        $po = row('SELECT * FROM host_payouts WHERE id = :id', ['id' => $req['ref_id']]);
        if (!$po) {
            return;
        }
        $done = q("UPDATE host_payouts SET status = 'paid', payment_reference = :r, admin_note = 'Sent through M-Pesa', processed_by = :a, processed_at = NOW() WHERE id = :id AND status = 'requested'", ['r' => $receipt, 'a' => $by ?: null, 'id' => $po['id']])->rowCount();
        if ($done) {
            notify((int)$po['host_id'], 'payment', 'Your payout was sent', money((float)$po['amount']) . ' was sent to ' . hosts_phone_local((string)$req['phone']) . '. M-Pesa code ' . $receipt . '.', '/host/payouts');
        }
    }
}

/** Safaricom reports the result of a B2C payment. Always answers Accepted. */
function mpesa_b2c_handle_result(array $payload, string $urlSecret, string $ip): array
{
    if (!mpesa_callback_auth('mpesa_b2c_secret', $urlSecret, $ip)) {
        return mpesa_c2b_ack(403, 'Forbidden', '1');
    }
    try {
        $res = mpesa_b2c_parse_result($payload);
        if (!$res) {
            return mpesa_c2b_ack(400, 'Malformed request', '1');
        }
        $req = ($res['originator'] !== '' ? row('SELECT * FROM mpesa_b2c_requests WHERE originator_id = :o', ['o' => $res['originator']]) : null)
            ?: ($res['conversation'] !== '' ? row('SELECT * FROM mpesa_b2c_requests WHERE conversation_id = :c', ['c' => $res['conversation']]) : null);
        if (!$req) {
            mpesa_log('b2c result for unknown request', ['originator' => $res['originator']]);
            return mpesa_c2b_ack(200);
        }
        if ($req['status'] === 'completed') {
            return mpesa_c2b_ack(200);
        }
        if ($res['code'] === 0) {
            update('mpesa_b2c_requests', ['status' => 'completed', 'receipt' => mb_substr($res['receipt'], 0, 30), 'result_code' => '0', 'result_desc' => mb_substr($res['desc'], 0, 250), 'receiver_name' => $res['receiver']], ['id' => (int)$req['id']]);
            mpesa_b2c_settle($req, $res['receipt'] !== '' ? strtoupper($res['receipt']) : 'B2C' . $req['id']);
            mpesa_log('b2c completed', ['request' => $req['id'], 'receipt' => $res['receipt']]);
        } else {
            update('mpesa_b2c_requests', ['status' => 'failed', 'result_code' => (string)$res['code'], 'result_desc' => mb_substr($res['desc'], 0, 250)], ['id' => (int)$req['id']]);
            mpesa_log('b2c failed', ['request' => $req['id'], 'code' => $res['code'], 'desc' => $res['desc']]);
            if ($req['kind'] !== 'test') {
                admin_alert('payout', 'An M-Pesa payout failed', money((float)$req['amount']) . ' to ' . hosts_phone_local((string)$req['phone']) . ' did not go through: ' . $res['desc'] . '. The payout is still waiting; fix the cause and send it again.', '/admin/' . ($req['kind'] === 'wallet_payout' ? 'wallet' : 'payouts'));
            }
        }
        return mpesa_c2b_ack(200);
    } catch (Throwable $e) {
        app_log('error', 'mpesa b2c result failed: ' . $e->getMessage());
        return mpesa_c2b_ack(500, 'Server error', '1');
    }
}

/** Safaricom could not deliver the request in time. The outcome is unknown, so it stays blocked until an admin checks. */
function mpesa_b2c_handle_timeout(array $payload, string $urlSecret, string $ip): array
{
    if (!mpesa_callback_auth('mpesa_b2c_secret', $urlSecret, $ip)) {
        return mpesa_c2b_ack(403, 'Forbidden', '1');
    }
    $res = mpesa_b2c_parse_result($payload);
    $req = $res && $res['originator'] !== '' ? row('SELECT * FROM mpesa_b2c_requests WHERE originator_id = :o', ['o' => $res['originator']]) : null;
    if ($req && in_array($req['status'], ['queued', 'sent'], true)) {
        update('mpesa_b2c_requests', ['status' => 'timeout', 'result_desc' => 'M-Pesa timed out. Check the M-Pesa portal before sending again.'], ['id' => (int)$req['id']]);
        mpesa_log('b2c timeout', ['request' => $req['id']]);
        if ($req['kind'] !== 'test') {
            admin_alert('payout', 'An M-Pesa payout timed out', money((float)$req['amount']) . ' to ' . hosts_phone_local((string)$req['phone']) . ' timed out. Check the M-Pesa portal to see whether it was sent.', '/admin/' . ($req['kind'] === 'wallet_payout' ? 'wallet' : 'payouts'));
        }
    }
    return mpesa_c2b_ack(200);
}

/** Admin: after checking the M-Pesa portal, free a stuck request so the payout can be sent again. */
function mpesa_b2c_release(int $reqId): array
{
    $req = row('SELECT * FROM mpesa_b2c_requests WHERE id = :id', ['id' => $reqId]);
    if (!$req || !in_array($req['status'], ['queued', 'sent', 'timeout'], true)) {
        return ['ok' => false, 'error' => 'There is nothing to release.'];
    }
    if ($req['status'] === 'sent' && strtotime((string)$req['updated_at']) > time() - 900) {
        return ['ok' => false, 'error' => 'Safaricom is still processing this one. Give it 15 minutes.'];
    }
    update('mpesa_b2c_requests', ['status' => 'failed', 'result_desc' => 'Released by an admin after checking the M-Pesa portal.'], ['id' => $reqId]);
    return ['ok' => true, 'error' => ''];
}

/** Cron: send small payout requests automatically when the admin switched that on. */
function mpesa_b2c_cron(): array
{
    $out = ['sent' => 0, 'skipped' => 0];
    if (!(bool)setting('mpesa_b2c_auto') || !mpesa_b2c_ready()) {
        return $out + ['off' => 1];
    }
    $limit = (float)setting('mpesa_b2c_auto_limit');
    foreach (['host_payout' => 'host_payouts', 'wallet_payout' => 'wallet_payouts'] as $kind => $table) {
        $list = rows("SELECT id FROM $table WHERE status = 'requested' AND amount <= :l AND created_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                      AND NOT EXISTS (SELECT 1 FROM mpesa_b2c_requests b WHERE b.kind = :k AND b.ref_id = $table.id) ORDER BY id LIMIT 15", ['l' => $limit, 'k' => $kind]);
        foreach ($list as $po) {
            $r = mpesa_b2c_send_payout($kind, (int)$po['id'], null);
            $out[$r['ok'] ? 'sent' : 'skipped']++;
        }
    }
    return $out;
}
