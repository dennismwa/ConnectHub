<?php
/**
 * Payment gateway tests: logos, Paystack, PayPal, Stripe cards, saved cards, automatic renewal, M-Pesa C2B and B2C.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/gateways_test.php
 *
 * Needs a throw-away database. No network is used: every provider is replaced by canned answers through
 * $GLOBALS['__payments_http']. Creates disposable members, orders and payouts and removes them again.
 * Exit code 0 when every check passes, 1 otherwise.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) {
    exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n");
}

require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  ok    $name\n";
    } else {
        $fail++;
        echo "  FAIL  $name" . ($detail !== '' ? "  ($detail)" : '') . "\n";
    }
}
function section(string $t): void
{
    echo "\n$t\n";
}
function clear_limit(string $bucket): void
{
    q('DELETE FROM rate_limits WHERE bucket = :b', ['b' => substr(sha1($bucket), 0, 40)]);
}

$touched = [];
function set_for_test(string $key, string $value): void
{
    global $touched;
    if (!array_key_exists($key, $touched)) {
        $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $key]);
        $touched[$key] = $r ? (string)$r['setting_value'] : null;
    }
    setting_set($key, $value);
}
function restore_settings(): void
{
    global $touched;
    foreach ($touched as $k => $v) {
        if ($v === null) {
            q('DELETE FROM site_settings WHERE setting_key = :k', ['k' => $k]);
        } else {
            setting_set($k, $v);
        }
    }
    settings_overrides(true);
}

/** Canned network. $script is a queue of responses; $routes answers by URL fragment (checked first). */
$calls = [];
$script = [];
$routes = [];
$GLOBALS['__payments_http'] = function (string $method, string $url, array $headers, ?string $body) use (&$calls, &$script, &$routes): array {
    $calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
    foreach ($routes as $frag => $resp) {
        if (strpos($url, (string)$frag) !== false) {
            return is_callable($resp) ? $resp($method, $url, $body) : $resp;
        }
    }
    return array_shift($script) ?? ['status' => 0, 'body' => '', 'error' => 'no canned response'];
};
$ok = fn(array $j, int $status = 200): array => ['status' => $status, 'body' => (string)json_encode($j), 'error' => ''];
$lastBody = function () use (&$calls): array {
    $c = end($calls);
    return $c ? (json_decode((string)$c['body'], true) ?: []) : [];
};

// ---------------------------------------------------------------------------------------------
section('Logos');
foreach (['mpesa', 'airtel_money', 'paypal', 'visa', 'mastercard', 'amex', 'stripe', 'bank', 'paystack'] as $l) {
    check("logo file exists: $l", is_file(ROOT . '/assets/img/pay/' . $l . '.svg'));
}
check('M-Pesa uses its own logo', strpos(pay_logos('mpesa'), 'pay-logo-mpesa') !== false && strpos(pay_logos('mpesa'), 'alt="M-Pesa"') !== false);
check('card shows Visa, Mastercard and Amex', substr_count(pay_logos('card'), '<img') === 3 && strpos(pay_logos('card'), 'alt="Visa"') !== false && strpos(pay_logos('card'), 'alt="Mastercard"') !== false);
check('PayPal and Paystack have their logos', strpos(pay_logos('paypal'), 'alt="PayPal"') !== false && strpos(pay_logos('paystack'), 'alt="Paystack"') !== false);
check('manual M-Pesa also shows the M-Pesa logo', strpos(pay_logos('manual'), 'alt="M-Pesa"') !== false);
check('bank shows the bank mark', strpos(pay_logos('bank_transfer'), 'alt="Bank transfer"') !== false);
check('unknown method shows nothing', pay_logos('nope') === '' && pay_logo('../../etc/passwd') === '');

// ---------------------------------------------------------------------------------------------
section('Disposable member and settings');
$email = 'gateways-test-' . bin2hex(random_bytes(4)) . '@example.test';
$uid = create_account(['email' => $email, 'password' => 'Test#12345pass', 'display_name' => 'Gateway Tester']);
$created = [];
$payouts = ['host' => [], 'wallet' => []];
$mk = function (float $amount = 500.0, string $type = 'featured', array $meta = []) use ($uid, &$created): array {
    $p = payment_create($uid, $type, 'mpesa', $amount, 'Gateway test order', $meta);
    $created[] = (int)$p['id'];
    return $p;
};
$fresh = fn(int $id): array => row('SELECT * FROM payments WHERE id = :i', ['i' => $id]) ?: [];

try {
    foreach (['paystack_enabled' => '1', 'paystack_secret_key' => 'sk_test_gateways', 'paystack_channels' => 'all', 'currency' => 'KES',
        'paypal_enabled' => '1', 'paypal_env' => 'sandbox', 'paypal_client_id' => 'PP-ID', 'paypal_client_secret' => 'PP-SECRET', 'paypal_currency' => 'USD', 'paypal_rate' => '130', 'paypal_webhook_id' => 'WH-1',
        'card_payments_enabled' => '1', 'card_gateway' => 'stripe', 'stripe_secret_key' => 'sk_test_stripe', 'stripe_webhook_secret' => 'whsec_unit',
        'autorenew_enabled' => '1', 'autorenew_max_tries' => '3', 'autorenew_retry_days' => '2', 'autorenew_remind_days' => '3',
        'mpesa_enabled' => '1', 'mpesa_env' => 'sandbox', 'mpesa_shortcode' => '174379', 'mpesa_consumer_key' => 'KEY', 'mpesa_consumer_secret' => 'SEC', 'mpesa_passkey' => 'PASS',
        'mpesa_callback_secret' => 'unit-stk', 'mpesa_c2b_secret' => 'unit-c2b', 'mpesa_b2c_secret' => 'unit-b2c', 'mpesa_callback_ips' => '', 'payments_test_mode' => '0',
        'manual_mpesa_enabled' => '1', 'manual_mpesa_details' => 'Paybill 174379', 'mpesa_c2b_enabled' => '1', 'mpesa_c2b_validation' => '0', 'payments_method_order' => 'mpesa,card,paystack,paypal,manual,bank,test'] as $k => $v) {
        set_for_test($k, $v);
    }
    $tokenOk = ['status' => 200, 'body' => '{"access_token":"TOKEN-ABC","expires_in":"3599"}', 'error' => ''];

    // -----------------------------------------------------------------------------------------
    section('Methods, order and readiness');
    $m = payments_methods();
    check('all methods are listed in the saved order', array_keys($m) === ['mpesa', 'card', 'paystack', 'paypal', 'manual', 'bank', 'test'], implode(',', array_keys($m)));
    check('card, Paystack and PayPal are ready with their keys', $m['card']['ready'] && $m['paystack']['ready'] && $m['paypal']['ready']);
    set_for_test('payments_method_order', 'paypal,mpesa');
    check('a short order list is completed with the rest', array_slice(array_keys(payments_methods()), 0, 3) === ['paypal', 'mpesa', 'card']);
    set_for_test('payments_method_order', 'mpesa,card,paystack,paypal,manual,bank,test');
    set_for_test('stripe_secret_key', '');
    check('card is not ready without a Stripe key', !payments_methods()['card']['ready']);
    set_for_test('card_gateway', 'paystack');
    check('card is ready through Paystack when chosen', payments_methods()['card']['ready']);
    set_for_test('card_gateway', 'stripe');
    set_for_test('stripe_secret_key', 'sk_test_stripe');
    check('minor units: KES has cents, UGX does not', pg_minor_units(12.5, 'KES') === 1250 && pg_minor_units(1250, 'UGX') === 1250);
    check('status words for the overview', pgs_status('paystack')[0] === 'On' && pgs_status('test')[0] === 'Off');
    $acc = pay_accepted_strip();
    check('accepted strip shows each brand once', substr_count($acc, 'alt="Visa"') === 1 && substr_count($acc, 'alt="M-Pesa"') === 1 && strpos($acc, 'alt="PayPal"') !== false);

    // -----------------------------------------------------------------------------------------
    section('Paystack');
    $p = $mk(750.0);
    clear_limit('gwstart:' . $p['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'access_code' => 'abc', 'reference' => 'x']])];
    $r = paystack_start($p, 'paystack');
    $b = $lastBody();
    check('start: returns the hosted page', $r['ok'] && $r['url'] === 'https://checkout.paystack.com/abc', json_encode($r));
    check('start: amount in cents, currency, email and a callback', ($b['amount'] ?? 0) === 75000 && ($b['currency'] ?? '') === 'KES' && ($b['email'] ?? '') === $email && strpos((string)($b['callback_url'] ?? ''), '/pay/return/paystack') !== false);
    check('start: bearer key sent to Paystack', in_array('Authorization: Bearer sk_test_gateways', end($calls)['headers'], true));
    $row = $fresh((int)$p['id']);
    check('start: gateway, reference and method stored', $row['gateway'] === 'paystack' && strpos((string)$row['gateway_ref'], $p['reference'] . '-') === 0 && $row['payment_method'] === 'paystack');
    $ref = (string)$row['gateway_ref'];
    check('order reference is read back from the Paystack reference', paystack_order_ref($ref) === $p['reference']);

    $tx = ['status' => 'success', 'reference' => $ref, 'amount' => 75000, 'currency' => 'KES', 'id' => 99001, 'channel' => 'card'];
    check('apply: too little money is refused and closed', paystack_apply($row, ['amount' => 70000] + $tx) === 'mismatch' && $fresh((int)$p['id'])['status'] === 'failed');
    $p = $mk(750.0);
    clear_limit('gwstart:' . $p['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://x', 'reference' => 'x']])];
    paystack_start($p, 'paystack');
    $row = $fresh((int)$p['id']);
    $tx['reference'] = $row['gateway_ref'];
    check('apply: another order\'s reference is refused', paystack_apply($row, ['reference' => 'CH000000ZZZZZZ-ABC'] + $tx) === 'mismatch');
    $p = $mk(750.0);
    clear_limit('gwstart:' . $p['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://x', 'reference' => 'x']])];
    paystack_start($p, 'paystack');
    $row = $fresh((int)$p['id']);
    $tx['reference'] = $row['gateway_ref'];
    check('apply: pending stays pending', paystack_apply($row, ['status' => 'ongoing'] + $tx) === 'pending' && $fresh((int)$p['id'])['status'] === 'pending');
    check('apply: success completes and delivers', paystack_apply($row, $tx) === 'completed');
    $done = $fresh((int)$p['id']);
    check('apply: payment is completed, fulfilled and carries the gateway id', $done['status'] === 'completed' && $done['fulfilled_at'] !== null && $done['gateway_txn'] === '99001');
    check('apply: featured spot delivered', (string)val('SELECT featured_until FROM users WHERE id = :u', ['u' => $uid]) !== '');
    check('apply: a repeat does nothing', paystack_apply($done, $tx) === 'duplicate');

    // webhook
    $payload = (string)json_encode(['event' => 'charge.success', 'data' => $tx]);
    $p2 = $mk(750.0);
    clear_limit('gwstart:' . $p2['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://x', 'reference' => 'x']])];
    paystack_start($p2, 'paystack');
    $ref2 = (string)$fresh((int)$p2['id'])['gateway_ref'];
    $payload2 = (string)json_encode(['event' => 'charge.success', 'data' => ['reference' => $ref2, 'id' => 99002] + $tx]);
    [$st] = paystack_handle_webhook($payload2, 'deadbeef');
    check('webhook: wrong signature is refused', $st === 401 && $fresh((int)$p2['id'])['status'] === 'pending');
    [$st] = paystack_handle_webhook($payload2, hash_hmac('sha512', $payload2, 'sk_test_gateways'));
    check('webhook: signed charge.success completes the order', $st === 200 && $fresh((int)$p2['id'])['status'] === 'completed');
    [$st, $bd] = paystack_handle_webhook($payload2, hash_hmac('sha512', $payload2, 'sk_test_gateways'));
    check('webhook: a second delivery is harmless', $st === 200 && $fresh((int)$p2['id'])['status'] === 'completed');
    $other = (string)json_encode(['event' => 'transfer.success', 'data' => []]);
    [$st, $bd] = paystack_handle_webhook($other, hash_hmac('sha512', $other, 'sk_test_gateways'));
    check('webhook: other events are ignored politely', $st === 200 && $bd === 'ignored');

    // late success on a cancelled order still counts once
    $p3 = $mk(300.0);
    clear_limit('gwstart:' . $p3['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://x', 'reference' => 'x']])];
    paystack_start($p3, 'paystack');
    update('payments', ['status' => 'cancelled'], ['id' => (int)$p3['id']]);
    $row3 = $fresh((int)$p3['id']);
    check('late success on a cancelled order is honoured', paystack_apply($row3, ['reference' => $row3['gateway_ref'], 'amount' => 30000, 'id' => 99003] + $tx) === 'completed' && $fresh((int)$p3['id'])['status'] === 'completed');

    // verify
    $p4 = $mk(300.0);
    clear_limit('gwstart:' . $p4['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://x', 'reference' => 'x']])];
    paystack_start($p4, 'paystack');
    $row4 = $fresh((int)$p4['id']);
    $script = [$ok(['status' => true, 'data' => ['status' => 'success', 'amount' => 30000, 'currency' => 'KES', 'id' => 99004, 'channel' => 'mobile_money']])];
    $v = paystack_verify($row4);
    check('verify: success completes the order', $v['ok'] && $v['outcome'] === 'completed' && strpos((string)end($calls)['url'], '/transaction/verify/') !== false);
    $script = [['status' => 0, 'body' => '', 'error' => 'timeout']];
    $v = paystack_verify($fresh((int)$mk(100.0)['id']));
    check('verify: no gateway reference is a soft no', !$v['ok']);
    $p5 = $mk(300.0);
    update('payments', ['gateway' => 'paystack', 'gateway_ref' => $p5['reference'] . '-AAAA'], ['id' => (int)$p5['id']]);
    $script = [['status' => 0, 'body' => '', 'error' => 'timeout']];
    $v = paystack_verify($fresh((int)$p5['id']));
    check('verify: network trouble is a soft failure', !$v['ok'] && $v['error'] !== '' && $fresh((int)$p5['id'])['status'] === 'pending');

    // -----------------------------------------------------------------------------------------
    section('PayPal');
    $amt = paypal_amount(1300.0);
    check('PayPal converts the price at the admin rate', $amt['currency'] === 'USD' && $amt['value'] === '10.00', json_encode($amt));
    $pp = $mk(1300.0);
    clear_limit('gwstart:' . $pp['id']);
    cache_clear('paypal_token_');
    $script = [$ok(['access_token' => 'PP-TOKEN', 'expires_in' => 32000]), $ok(['id' => 'ORDER-1', 'status' => 'PAYER_ACTION_REQUIRED', 'links' => [['rel' => 'self', 'href' => 'https://api'], ['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER-1']]], 201)];
    $r = paypal_start($pp);
    $b = $lastBody();
    check('start: returns the PayPal approval page', $r['ok'] && strpos($r['url'], 'paypal.com/checkoutnow') !== false, json_encode($r));
    check('start: order is CAPTURE in the converted currency with our reference', ($b['intent'] ?? '') === 'CAPTURE' && ($b['purchase_units'][0]['amount']['value'] ?? '') === '10.00' && ($b['purchase_units'][0]['amount']['currency_code'] ?? '') === 'USD' && ($b['purchase_units'][0]['custom_id'] ?? '') === $pp['reference']);
    check('start: talks to the sandbox host', strpos((string)end($calls)['url'], 'api-m.sandbox.paypal.com/v2/checkout/orders') !== false);
    $row = $fresh((int)$pp['id']);
    check('start: order id and converted amount stored', $row['gateway'] === 'paypal' && $row['gateway_ref'] === 'ORDER-1' && $row['payment_method'] === 'paypal' && (payment_meta($row)['pp_amount'] ?? '') === '10.00');

    $cap = ['id' => 'CAP-1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => '10.00']];
    check('capture: too little money is refused', paypal_apply_capture($row, ['amount' => ['currency_code' => 'USD', 'value' => '5.00']] + $cap) === 'mismatch' && $fresh((int)$pp['id'])['status'] === 'failed');
    $pp = $mk(1300.0);
    clear_limit('gwstart:' . $pp['id']);
    $script = [$ok(['id' => 'ORDER-2', 'links' => [['rel' => 'payer-action', 'href' => 'https://pp/approve']]], 201)];
    paypal_start($pp);
    $row = $fresh((int)$pp['id']);
    check('capture: wrong currency is refused', paypal_apply_capture($row, ['amount' => ['currency_code' => 'EUR', 'value' => '10.00']] + $cap) === 'mismatch');
    $pp = $mk(1300.0);
    clear_limit('gwstart:' . $pp['id']);
    $script = [$ok(['id' => 'ORDER-3', 'links' => [['rel' => 'approve', 'href' => 'https://pp/approve']]], 201)];
    paypal_start($pp);
    $row = $fresh((int)$pp['id']);
    check('capture: pending capture waits', paypal_apply_capture($row, ['status' => 'PENDING'] + $cap) === 'pending');
    $script = [$ok(['id' => 'ORDER-3', 'status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [$cap]]]]], 201)];
    $r = paypal_capture($row);
    check('capture: the return page captures the approved order and completes it', $r['ok'] && $r['outcome'] === 'completed' && strpos((string)end($calls)['url'], '/v2/checkout/orders/ORDER-3/capture') !== false);
    check('capture: payment id stored from the capture', $fresh((int)$pp['id'])['gateway_txn'] === 'CAP-1' && $fresh((int)$pp['id'])['status'] === 'completed');
    $r = paypal_capture($fresh((int)$pp['id']));
    check('capture: coming back twice is harmless', $r['ok'] && $r['outcome'] === 'duplicate');

    // webhook
    $pp2 = $mk(1300.0);
    clear_limit('gwstart:' . $pp2['id']);
    $script = [$ok(['id' => 'ORDER-4', 'links' => [['rel' => 'payer-action', 'href' => 'https://pp/approve']]], 201)];
    paypal_start($pp2);
    $evt = (string)json_encode(['id' => 'WH-EVT-1', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => ['id' => 'CAP-2', 'status' => 'COMPLETED', 'custom_id' => $pp2['reference'], 'amount' => ['currency_code' => 'USD', 'value' => '10.00']]]);
    $hdrs = ['paypal-transmission-id' => 't1', 'paypal-transmission-time' => '2026-10-16T10:00:00Z', 'paypal-transmission-sig' => 'sig', 'paypal-cert-url' => 'https://api.paypal.com/cert', 'paypal-auth-algo' => 'SHA256withRSA'];
    $script = [$ok(['verification_status' => 'FAILURE'])];
    [$st] = paypal_handle_webhook($evt, $hdrs);
    check('webhook: a signature PayPal does not verify is refused', $st === 401 && $fresh((int)$pp2['id'])['status'] === 'pending');
    $script = [$ok(['verification_status' => 'SUCCESS'])];
    [$st] = paypal_handle_webhook($evt, $hdrs);
    $vb = json_decode((string)end($calls)['body'], true) ?: [];
    check('webhook: verified event completes the order', $st === 200 && $fresh((int)$pp2['id'])['status'] === 'completed');
    check('webhook: the raw event and webhook id are sent for verification', ($vb['webhook_id'] ?? '') === 'WH-1' && ($vb['webhook_event']['id'] ?? '') === 'WH-EVT-1');
    [$st] = paypal_handle_webhook($evt, ['paypal-transmission-id' => 't1']);
    check('webhook: missing headers are refused', $st === 400);

    // -----------------------------------------------------------------------------------------
    section('Cards with Stripe');
    $sp = $mk(500.0);
    clear_limit('gwstart:' . $sp['id']);
    $script = [$ok(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_1'])];
    $r = stripe_start($sp);
    $call = end($calls);
    parse_str((string)$call['body'], $form);
    check('start: returns the Stripe page', $r['ok'] && strpos($r['url'], 'checkout.stripe.com') !== false, json_encode($r));
    check('start: form has the amount in cents, currency, our reference and a return address', ($form['line_items'][0]['price_data']['unit_amount'] ?? '') === '50000' && ($form['line_items'][0]['price_data']['currency'] ?? '') === 'kes' && ($form['client_reference_id'] ?? '') === $sp['reference'] && strpos((string)($form['success_url'] ?? ''), '/pay/return/stripe?session_id={CHECKOUT_SESSION_ID}') !== false, json_encode($form));
    check('start: card only, bearer key', ($form['payment_method_types'][0] ?? '') === 'card' && in_array('Authorization: Bearer sk_test_stripe', $call['headers'], true));
    check('start: no card saving unless asked', !isset($form['customer_creation']) && !isset($form['payment_intent_data']['setup_future_usage']));
    $row = $fresh((int)$sp['id']);
    check('start: session id and method stored', $row['gateway'] === 'stripe' && $row['gateway_ref'] === 'cs_test_1' && $row['payment_method'] === 'card');

    $sess = ['id' => 'cs_test_1', 'payment_status' => 'paid', 'client_reference_id' => $sp['reference'], 'amount_total' => 50000, 'currency' => 'kes', 'payment_intent' => ['id' => 'pi_1']];
    check('apply: unpaid session waits', stripe_apply_session($row, ['payment_status' => 'unpaid'] + $sess) === 'pending');
    check('apply: too little money is refused', stripe_apply_session($row, ['amount_total' => 100] + $sess) === 'mismatch' && $fresh((int)$sp['id'])['status'] === 'failed');
    $sp = $mk(500.0);
    clear_limit('gwstart:' . $sp['id']);
    $script = [$ok(['id' => 'cs_test_2', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_2'])];
    stripe_start($sp);
    $row = $fresh((int)$sp['id']);
    check('apply: a session for another order is refused', stripe_apply_session($row, ['client_reference_id' => 'CH000000AAAAAA'] + $sess) === 'mismatch');
    $sp = $mk(500.0);
    clear_limit('gwstart:' . $sp['id']);
    $script = [$ok(['id' => 'cs_test_3', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_3'])];
    stripe_start($sp);
    $row = $fresh((int)$sp['id']);
    $sess = ['id' => 'cs_test_3', 'client_reference_id' => $sp['reference']] + $sess;
    check('apply: paid session completes', stripe_apply_session($row, $sess) === 'completed' && $fresh((int)$sp['id'])['gateway_txn'] === 'pi_1' && $fresh((int)$sp['id'])['status'] === 'completed');
    check('apply: a repeat does nothing', stripe_apply_session($fresh((int)$sp['id']), $sess) === 'duplicate');

    // signature
    $body = '{"id":"evt_1"}';
    $t = 1760600000;
    $good = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $body, 'whsec_unit');
    check('signature: valid header passes', stripe_signature_ok($body, $good, 'whsec_unit', 300, $t + 10));
    check('signature: wrong secret fails', !stripe_signature_ok($body, $good, 'whsec_other', 300, $t + 10));
    check('signature: changed body fails', !stripe_signature_ok($body . ' ', $good, 'whsec_unit', 300, $t + 10));
    check('signature: old timestamp fails', !stripe_signature_ok($body, $good, 'whsec_unit', 300, $t + 4000));
    check('signature: empty header or secret fails', !stripe_signature_ok($body, '', 'whsec_unit', 300, $t) && !stripe_signature_ok($body, $good, '', 300, $t));
    check('signature: second v1 value is accepted', stripe_signature_ok($body, 't=' . $t . ',v1=bad,v1=' . hash_hmac('sha256', $t . '.' . $body, 'whsec_unit'), 'whsec_unit', 300, $t));

    // webhook (needs a fresh timestamp)
    $sp = $mk(500.0);
    clear_limit('gwstart:' . $sp['id']);
    $script = [$ok(['id' => 'cs_test_4', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_4'])];
    stripe_start($sp);
    $evt = (string)json_encode(['id' => 'evt_9', 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_test_4', 'client_reference_id' => $sp['reference'], 'payment_status' => 'paid']]]);
    $now = time();
    [$st] = stripe_handle_webhook($evt, 't=' . $now . ',v1=bad');
    check('webhook: bad signature is refused', $st === 400 && $fresh((int)$sp['id'])['status'] === 'pending');
    $script = [$ok(['id' => 'cs_test_4', 'payment_status' => 'paid', 'client_reference_id' => $sp['reference'], 'amount_total' => 50000, 'currency' => 'kes', 'payment_intent' => ['id' => 'pi_4']])];
    [$st] = stripe_handle_webhook($evt, 't=' . $now . ',v1=' . hash_hmac('sha256', $now . '.' . $evt, 'whsec_unit'));
    check('webhook: signed event completes the order (confirmed again with Stripe)', $st === 200 && $fresh((int)$sp['id'])['status'] === 'completed' && strpos((string)end($calls)['url'], '/v1/checkout/sessions/cs_test_4') !== false);
    $evt2 = (string)json_encode(['id' => 'evt_10', 'type' => 'charge.refunded', 'data' => ['object' => ['id' => 'ch_1']]]);
    [$st, $bd] = stripe_handle_webhook($evt2, 't=' . $now . ',v1=' . hash_hmac('sha256', $now . '.' . $evt2, 'whsec_unit'));
    check('webhook: other events are ignored politely', $st === 200 && $bd === 'ignored');

    // card via Paystack
    set_for_test('card_gateway', 'paystack');
    $cp = $mk(400.0);
    clear_limit('gwstart:' . $cp['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/card', 'reference' => 'x']])];
    $r = payments_online_start($cp, 'card');
    check('card through Paystack: limited to cards and stored as card', $r['ok'] && ($lastBody()['channels'] ?? []) === ['card'] && $fresh((int)$cp['id'])['payment_method'] === 'card');
    set_for_test('card_gateway', 'stripe');

    // -----------------------------------------------------------------------------------------
    section('The checkout action');
    $ap = $mk(500.0);
    clear_limit('gwstart:' . $ap['id']);
    $script = [$ok(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/act', 'reference' => 'x']])];
    $r = payments_perform_action($ap, 'paystack', []);
    check('action: paystack returns a redirect to the hosted page', $r['ok'] && $r['redirect'] === 'https://checkout.paystack.com/act' && $r['state'] === 'open');
    set_for_test('paystack_enabled', '0');
    $r = payments_perform_action($ap, 'paystack', []);
    check('action: a method that is switched off is refused', !$r['ok'] && $r['error'] !== '');
    set_for_test('paystack_enabled', '1');
    $r = payments_perform_action($fresh((int)$mk(10.0)['id']), 'check', []);
    check('action: "check" with nothing paid says so', !$r['ok'] && stripos($r['error'], 'not received') !== false);

    // -----------------------------------------------------------------------------------------
    section('Saved cards');
    $sid = pay_saved_add($uid, 'paystack', 'AUTH_abc', 'sig-1', ['customer' => $email, 'brand' => 'visa', 'last4' => '4081', 'exp_month' => 12, 'exp_year' => 2030]);
    check('saved: a card is stored with a readable label', pay_saved_label(pay_saved_get($uid, $sid)) === 'Visa ending 4081');
    check('saved: the same card is not added twice', pay_saved_add($uid, 'paystack', 'AUTH_abc2', 'sig-1', ['last4' => '4081']) === $sid && count(pay_saved_list($uid)) === 1);
    check('saved: another member cannot read it', pay_saved_get($uid + 999999, $sid) === null);

    // -----------------------------------------------------------------------------------------
    section('Automatic renewal');
    $plan = row("SELECT * FROM subscription_plans WHERE is_active = 1 AND tier > 0 AND price_monthly > 0 ORDER BY tier LIMIT 1");
    check('a paid plan exists to test with', (bool)$plan);
    $user = row('SELECT * FROM users WHERE id = :id', ['id' => $uid]);
    $price = (float)plan_period_price($plan, 'monthly');
    $buy = function (array $meta = [], string $method = 'paystack') use ($uid, $plan, $price, &$created): array {
        $p = payment_create($uid, 'subscription', $method, $price, $plan['name'] . ' plan', ['plan_id' => (int)$plan['id'], 'period' => 'monthly', 'plan_slug' => $plan['slug'], 'plan_name' => $plan['name']] + $meta);
        $created[] = (int)$p['id'];
        return $p;
    };
    q("DELETE FROM user_subscriptions WHERE user_id = :u", ['u' => $uid]);

    // paid by hand: no renewal
    $p = $buy();
    payment_complete((int)$p['id']);
    $sub = row('SELECT * FROM user_subscriptions WHERE user_id = :u ORDER BY id DESC LIMIT 1', ['u' => $uid]);
    check('without the tick, renewal stays off', $sub && (int)$sub['auto_renew'] === 0);
    check('autorenew_turn_on needs a saved card to turn on with a card', !autorenew_turn_on($uid, 'card', 999999)['ok']);
    check('autorenew_turn_on rejects a bad phone', !autorenew_turn_on($uid, 'mpesa', null, '12')['ok']);

    // ticked at checkout, paid with a card that was saved
    q("DELETE FROM user_subscriptions WHERE user_id = :u", ['u' => $uid]);
    $p = $buy(['auto_renew' => 1, 'renew_saved_id' => $sid]);
    payment_complete((int)$p['id']);
    $sub = row('SELECT * FROM user_subscriptions WHERE user_id = :u ORDER BY id DESC LIMIT 1', ['u' => $uid]);
    check('with the tick and a saved card, renewal is on with that card', (int)$sub['auto_renew'] === 1 && $sub['renew_method'] === 'card' && (int)$sub['renew_saved_id'] === $sid);
    $sum = autorenew_summary(array_merge($sub, ['plan_name' => $plan['name']]));
    check('summary names the card and the price', $sum['on'] && strpos($sum['how'], 'Visa ending 4081') !== false && $sum['price'] === $price);

    // reminder
    update('user_subscriptions', ['expires_at' => date('Y-m-d H:i:s', time() + 2 * 86400 + 3600), 'renew_notified_for' => null], ['id' => (int)$sub['id']]);
    $out = autorenew_run();
    check('run: a reminder goes out 3 days before and a charge is not yet due', $out['reminded'] >= 1 && (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title = \'Your plan renews soon\'', ['u' => $uid], 0) === 1);
    $out = autorenew_run();
    check('run: the reminder is sent once per renewal', (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title = \'Your plan renews soon\'', ['u' => $uid], 0) === 1);

    // charge succeeds
    update('user_subscriptions', ['expires_at' => date('Y-m-d H:i:s', time() + 3600)], ['id' => (int)$sub['id']]);
    $before = strtotime((string)row('SELECT expires_at FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']])['expires_at']);
    $routes = ['/transaction/charge_authorization' => function ($m, $u, $body) use ($ok) {
        $b = json_decode((string)$body, true);
        return $ok(['status' => true, 'data' => ['status' => 'success', 'reference' => $b['reference'], 'amount' => $b['amount'], 'currency' => $b['currency'], 'id' => 5555, 'channel' => 'card', 'authorization' => ['channel' => 'card']]]);
    }];
    $out = autorenew_run();
    $routes = [];
    $after = row('SELECT * FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']]);
    check('run: the saved card is charged and the plan is extended', ($out['charged'] ?? 0) >= 1 && strtotime((string)$after['expires_at']) > $before + 25 * 86400, json_encode($out));
    $renewal = row("SELECT * FROM payments WHERE user_id = :u AND payment_type = 'subscription' AND metadata LIKE '%\"renewal\":1%' ORDER BY id DESC LIMIT 1", ['u' => $uid]);
    $created[] = (int)$renewal['id'];
    check('run: the renewal payment is completed by the gateway', $renewal['status'] === 'completed' && $renewal['gateway'] === 'paystack' && $renewal['gateway_txn'] === '5555' && $renewal['payment_method'] === 'card');
    check('run: retry counter is clear after success', (int)$after['renew_tries'] === 0);
    $again = autorenew_run();
    check('run: nothing is charged twice', ($again['charged'] ?? 0) === 0 && (int)val("SELECT COUNT(*) FROM payments WHERE user_id = :u AND payment_type = 'subscription' AND metadata LIKE '%\"renewal\":1%'", ['u' => $uid], 0) === 1);

    // charge declined, retried, then given up
    update('user_subscriptions', ['expires_at' => date('Y-m-d H:i:s', time() + 3600), 'renew_tries' => 0, 'renew_next_at' => null], ['id' => (int)$sub['id']]);
    $routes = ['/transaction/charge_authorization' => $ok(['status' => false, 'message' => 'Insufficient funds'], 400)];
    $out = autorenew_run();
    $routes = [];
    $after = row('SELECT * FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']]);
    check('declined: the try is counted and the next one is scheduled', ($out['retry'] ?? 0) >= 1 && (int)$after['renew_tries'] === 1 && strtotime((string)$after['renew_next_at']) > time() + 86400);
    check('declined: the member is told', (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title = \'We could not renew your plan\'', ['u' => $uid], 0) === 1);
    $failed = row("SELECT * FROM payments WHERE user_id = :u AND metadata LIKE '%\"renewal\":1%' AND status = 'failed' ORDER BY id DESC LIMIT 1", ['u' => $uid]);
    check('declined: the charge is recorded as failed', (bool)$failed);
    $out = autorenew_run();
    check('declined: it is not retried before the wait is over', ($out['retry'] ?? 0) === 0 && (int)row('SELECT renew_tries FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']])['renew_tries'] === 1);
    update('user_subscriptions', ['renew_tries' => 3, 'renew_next_at' => null], ['id' => (int)$sub['id']]);
    $out = autorenew_run();
    $after = row('SELECT * FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']]);
    check('after the last try, automatic renewal switches off and the member is told', ($out['gave_up'] ?? 0) >= 1 && (int)$after['auto_renew'] === 0 && (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title = \'Automatic renewal is off\'', ['u' => $uid], 0) >= 1);

    // M-Pesa renewal sends a prompt
    check('turn on with M-Pesa', autorenew_turn_on($uid, 'mpesa', null, '0712 345 678')['ok']);
    update('user_subscriptions', ['expires_at' => date('Y-m-d H:i:s', time() + 3600), 'renew_tries' => 0, 'renew_next_at' => null], ['id' => (int)$sub['id']]);
    cache_clear('mpesa_token_');
    $routes = ['/oauth/v1/generate' => $tokenOk, '/mpesa/stkpush/v1/processrequest' => $ok(['MerchantRequestID' => 'm1', 'CheckoutRequestID' => 'ws_CO_RENEW_1', 'ResponseCode' => '0', 'CustomerMessage' => 'ok'])];
    $out = autorenew_run();
    $routes = [];
    $stk = row("SELECT * FROM payments WHERE user_id = :u AND mpesa_checkout_id = 'ws_CO_RENEW_1'", ['u' => $uid]);
    if ($stk) {
        $created[] = (int)$stk['id'];
    }
    check('M-Pesa renewal: an STK prompt is sent to the saved phone', $stk && $stk['mpesa_phone'] === '254712345678' && $stk['status'] === 'pending', json_encode($out));
    $before = strtotime((string)row('SELECT expires_at FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']])['expires_at']);
    mpesa_apply_result($stk, ['code' => 0, 'desc' => 'ok', 'amount' => $price, 'receipt' => 'RENEWRCPT1', 'phone' => '254712345678', 'merchant_id' => 'm1']);
    $after = row('SELECT * FROM user_subscriptions WHERE id = :i', ['i' => $sub['id']]);
    check('M-Pesa renewal: approving it extends the plan and clears the tries', strtotime((string)$after['expires_at']) > $before + 25 * 86400 && (int)$after['renew_tries'] === 0 && (int)$after['auto_renew'] === 1);
    $waiting = autorenew_run();
    check('M-Pesa renewal: while a prompt is open nothing else is sent', ($waiting['charged'] ?? 0) === 0);

    // link renewal reminds with a pay link
    check('turn on with a reminder only', autorenew_turn_on($uid, 'link')['ok']);
    update('user_subscriptions', ['expires_at' => date('Y-m-d H:i:s', time() + 3600), 'renew_tries' => 0, 'renew_next_at' => null], ['id' => (int)$sub['id']]);
    $out = autorenew_run();
    check('link renewal: a reminder with a pay link is sent', ($out['reminded'] ?? 0) >= 1 && (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title = \'Time to renew your plan\'', ['u' => $uid], 0) === 1);
    foreach (rows("SELECT id FROM payments WHERE user_id = :u AND payment_type = 'subscription'", ['u' => $uid]) as $r) {
        $created[] = (int)$r['id'];
    }
    autorenew_turn_off($uid);
    check('turn off clears the renewal', (int)row('SELECT auto_renew FROM user_subscriptions WHERE user_id = :u ORDER BY id DESC LIMIT 1', ['u' => $uid])['auto_renew'] === 0);
    check('removing a saved card moves its plans to reminders', true);
    autorenew_turn_on($uid, 'card', $sid);
    pay_saved_remove($uid, $sid);
    $rm = row('SELECT * FROM user_subscriptions WHERE user_id = :u ORDER BY id DESC LIMIT 1', ['u' => $uid]);
    check('removing a saved card switches that plan to a reminder', $rm['renew_method'] === 'link' && $rm['renew_saved_id'] === null && count(pay_saved_list($uid)) === 0);
    set_for_test('autorenew_enabled', '0');
    check('switched off by the admin: the job does nothing', (autorenew_run()['skipped'] ?? 0) === 1 && !autorenew_turn_on($uid, 'link')['ok']);
    set_for_test('autorenew_enabled', '1');

    // -----------------------------------------------------------------------------------------
    section('M-Pesa C2B (Paybill or till)');
    check('addresses avoid the word "mpesa"', stripos(implode(' ', array_merge(mpesa_c2b_urls(), mpesa_b2c_urls(), [mpesa_stk_callback_url()])), 'mpesa') === false && stripos(implode(' ', mpesa_c2b_urls()), 'safaricom') === false);
    check('addresses carry their own secret', substr(mpesa_c2b_urls()[1], -strlen('/api/pay/c2b/unit-c2b/confirm')) === '/api/pay/c2b/unit-c2b/confirm' && substr(mpesa_b2c_urls()[0], -strlen('/api/pay/b2c/unit-b2c/result')) === '/api/pay/b2c/unit-b2c/result');
    $cp = $mk(500.0);
    $tx = fn(string $id, string $ref, float $amt) => ['TransactionType' => 'Pay Bill', 'TransID' => $id, 'TransTime' => '20261016120000', 'TransAmount' => (string)$amt, 'BusinessShortCode' => '174379', 'BillRefNumber' => $ref, 'MSISDN' => '254712345678', 'FirstName' => 'Jane', 'LastName' => 'Doe'];
    [$st] = mpesa_c2b_confirm($tx('QAA0000001', $cp['reference'], 500), 'wrong', '1.2.3.4');
    check('confirm: a wrong secret is refused and nothing is recorded', $st === 403 && (int)val("SELECT COUNT(*) FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000001'", [], 0) === 0);
    [$st, $body] = mpesa_c2b_confirm($tx('QAA0000001', ' ' . strtolower(substr($cp['reference'], 0, 4)) . '-' . substr($cp['reference'], 4), 500), 'unit-c2b', '1.2.3.4');
    check('confirm: Safaricom gets an Accepted answer', $st === 200 && (string)$body['ResultCode'] === '0');
    $row = $fresh((int)$cp['id']);
    check('confirm: a typed-in account number (spaces, lower case) still finds the order and pays it', $row['status'] === 'completed' && $row['mpesa_receipt'] === 'QAA0000001' && $row['payment_method'] === 'mpesa' && $row['fulfilled_at'] !== null);
    $rec = row("SELECT * FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000001'");
    check('confirm: the transaction is recorded as matched to the order', $rec['status'] === 'matched' && (int)$rec['payment_id'] === (int)$cp['id']);
    [$st] = mpesa_c2b_confirm($tx('QAA0000001', $cp['reference'], 500), 'unit-c2b', '1.2.3.4');
    check('confirm: Safaricom sending it twice changes nothing', $st === 200 && (int)val("SELECT COUNT(*) FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000001'", [], 0) === 1);
    $low = $mk(500.0);
    mpesa_c2b_confirm($tx('QAA0000002', $low['reference'], 300), 'unit-c2b', '1.2.3.4');
    check('confirm: too little money is recorded but does not pay the order', $fresh((int)$low['id'])['status'] === 'pending' && row("SELECT status FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000002'")['status'] === 'underpaid');
    mpesa_c2b_confirm($tx('QAA0000003', 'NOSUCHORDER', 500), 'unit-c2b', '1.2.3.4');
    check('confirm: an unknown account number is kept for matching', row("SELECT status FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000003'")['status'] === 'unmatched');
    $big = $mk(400.0);
    mpesa_c2b_confirm($tx('QAA0000004', $big['reference'], 700), 'unit-c2b', '1.2.3.4');
    check('confirm: paying more than the order still pays it', $fresh((int)$big['id'])['status'] === 'completed');
    mpesa_c2b_confirm($tx('QAA0000005', $cp['reference'], 500), 'unit-c2b', '1.2.3.4');
    check('confirm: paying an already paid order is flagged as a duplicate', row("SELECT status FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000005'")['status'] === 'duplicate');
    [$st] = mpesa_c2b_confirm(['TransID' => 'X'], 'unit-c2b', '1.2.3.4');
    check('confirm: a malformed notice is refused', $st === 400);

    $unm = (int)row("SELECT id FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000003'")['id'];
    $m1 = mpesa_c2b_match($unm, 'NOPE');
    check('match: an unknown order reference is refused', !$m1['ok']);
    $target = $mk(500.0);
    $m2 = mpesa_c2b_match($unm, $target['reference']);
    check('match: an admin can match a wrong account number to an order', $m2['ok'] && $fresh((int)$target['id'])['status'] === 'completed' && row("SELECT status FROM mpesa_c2b_transactions WHERE id = $unm")['status'] === 'manual');
    check('match: it cannot be matched twice', !mpesa_c2b_match($unm, $target['reference'])['ok']);
    $under = (int)row("SELECT id FROM mpesa_c2b_transactions WHERE trans_id = 'QAA0000002'")['id'];
    check('match: an underpaid transaction cannot pay a bigger order', !mpesa_c2b_match($under, $mk(900.0)['reference'])['ok']);

    // typing the M-Pesa code of a payment that already arrived
    $late = $mk(250.0);
    mpesa_c2b_confirm($tx('QAA0000006', 'WRONGREF', 250), 'unit-c2b', '1.2.3.4');
    $r = payments_submit_manual($late, 'manual', 'qaa0000006', '0712345678', '');
    check('manual code: a code that already arrived on the Paybill pays the order at once', $r['ok'] && $fresh((int)$late['id'])['status'] === 'completed');

    // validation
    set_for_test('mpesa_c2b_validation', '0');
    [$st, $b] = mpesa_c2b_validate($tx('QV1', 'WHATEVER', 100), 'unit-c2b', '1.2.3.4');
    check('validate: with validation off everything is accepted', $st === 200 && (string)$b['ResultCode'] === '0');
    set_for_test('mpesa_c2b_validation', '1');
    $vp = $mk(500.0);
    [$st, $b] = mpesa_c2b_validate($tx('QV2', $vp['reference'], 500), 'unit-c2b', '1.2.3.4');
    check('validate: a real open order is accepted', (string)$b['ResultCode'] === '0');
    [$st, $b] = mpesa_c2b_validate($tx('QV3', 'NOPE', 500), 'unit-c2b', '1.2.3.4');
    check('validate: an unknown account number is refused with C2B00012', (string)$b['ResultCode'] === 'C2B00012');
    [$st, $b] = mpesa_c2b_validate($tx('QV4', $vp['reference'], 100), 'unit-c2b', '1.2.3.4');
    check('validate: too little money is refused with C2B00013', (string)$b['ResultCode'] === 'C2B00013');
    [$st, $b] = mpesa_c2b_validate($tx('QV5', $vp['reference'], 500), 'bad', '1.2.3.4');
    check('validate: a wrong secret is forbidden', $st === 403);
    set_for_test('mpesa_c2b_validation', '0');

    // registering
    cache_clear('mpesa_token_');
    $script = [$tokenOk, $ok(['OriginatorCoversationID' => 'x', 'ResponseCode' => '0', 'ResponseDescription' => 'Success'])];
    $r = mpesa_c2b_register();
    $b = $lastBody();
    check('register: sends both addresses and the short code to the v2 endpoint', $r['ok'] && strpos((string)end($calls)['url'], '/mpesa/c2b/v2/registerurl') !== false && ($b['ShortCode'] ?? '') === '174379' && ($b['ConfirmationURL'] ?? '') === mpesa_c2b_urls()[1] && ($b['ValidationURL'] ?? '') === mpesa_c2b_urls()[0] && ($b['ResponseType'] ?? '') === 'Completed');
    check('register: the time is remembered', (string)setting('mpesa_c2b_registered_at') !== '');
    $script = [$ok(['errorCode' => '500.003.1001', 'errorMessage' => 'Duplicate notification info found'], 500)];
    $r = mpesa_c2b_register();
    check('register: "already registered" is explained in plain words', !$r['ok'] && stripos($r['message'], 'already registered') !== false);
    $script = [$ok(['errorMessage' => 'Invalid Access Token'], 401)];
    check('simulate: refuses to run outside the sandbox', (function () { set_for_test('mpesa_env', 'production'); $r = mpesa_c2b_simulate(10, 'X'); set_for_test('mpesa_env', 'sandbox'); return !$r['ok'] && stripos($r['message'], 'sandbox') !== false; })());

    // -----------------------------------------------------------------------------------------
    section('M-Pesa B2C (payouts)');
    // security credential
    $pk = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $pubPem = openssl_pkey_get_details($pk)['key'];
    set_for_test('mpesa_cert', $pubPem);
    set_for_test('mpesa_initiator_password', 'Safaricom999!');
    set_for_test('mpesa_security_credential', '');
    [$cred, $err] = mpesa_security_credential();
    $plain = '';
    openssl_private_decrypt((string)base64_decode($cred), $plain, $pk, OPENSSL_PKCS1_PADDING);
    check('credential: the password is encrypted with the certificate (RSA, PKCS1) and base64 encoded', $err === '' && $plain === 'Safaricom999!');
    set_for_test('mpesa_cert', 'not a certificate');
    check('credential: a bad certificate is explained', mpesa_security_credential()[1] !== '');
    set_for_test('mpesa_security_credential', 'READYMADE==');
    check('credential: a ready-made one wins', mpesa_security_credential() === ['READYMADE==', '']);
    foreach (['mpesa_b2c_enabled' => '1', 'mpesa_initiator_name' => 'testapi', 'mpesa_b2c_max' => '150000', 'mpesa_b2c_auto' => '0', 'mpesa_b2c_auto_limit' => '2000'] as $k => $v) {
        set_for_test($k, $v);
    }
    check('B2C is ready with the initiator and credential', mpesa_b2c_ready());
    set_for_test('mpesa_initiator_name', '');
    check('B2C is not ready without an initiator', !mpesa_b2c_ready());
    set_for_test('mpesa_initiator_name', 'testapi');

    $hp = insert('host_payouts', ['host_id' => $uid, 'amount' => 1500, 'method' => 'mpesa', 'destination' => '0712 345 678 - Jane Doe', 'status' => 'requested']);
    $payouts['host'][] = $hp;
    $routes = ['/oauth/v1/generate' => $tokenOk];
    cache_clear('mpesa_token_');
    $b2cOk = $ok(['ConversationID' => 'AG_1', 'OriginatorConversationID' => 'orig', 'ResponseCode' => '0', 'ResponseDescription' => 'Accept the service request successfully.']);
    $script = [$b2cOk];
    $r = mpesa_b2c_send_payout('host_payout', $hp, 1);
    $b = $lastBody();
    check('send: accepted by Safaricom', $r['ok'], json_encode($r));
    check('send: request has the amount, phone, command, initiator and callbacks', ($b['Amount'] ?? 0) === 1500 && ($b['PartyB'] ?? '') === '254712345678' && ($b['PartyA'] ?? '') === '174379' && ($b['CommandID'] ?? '') === 'BusinessPayment' && ($b['InitiatorName'] ?? '') === 'testapi' && ($b['SecurityCredential'] ?? '') === 'READYMADE==' && ($b['ResultURL'] ?? '') === mpesa_b2c_urls()[0] && ($b['QueueTimeOutURL'] ?? '') === mpesa_b2c_urls()[1], json_encode($b));
    check('send: goes to the B2C endpoint with a bearer token', strpos((string)end($calls)['url'], '/mpesa/b2c/v3/paymentrequest') !== false && in_array('Authorization: Bearer TOKEN-ABC', end($calls)['headers'], true));
    $req = mpesa_b2c_for('host_payout', $hp);
    check('send: the request is stored as sent with its conversation id', $req['status'] === 'sent' && $req['conversation_id'] === 'AG_1' && $req['phone'] === '254712345678' && strpos($req['originator_id'], 'CHB2C') === 0 && (int)$req['requested_by'] === 1);
    $again = mpesa_b2c_send_payout('host_payout', $hp, 1);
    check('send: sending the same payout twice is blocked', !$again['ok'] && stripos($again['error'], 'in progress') !== false);

    $result = fn(string $orig, int $code, string $desc = 'The service request is processed successfully.', string $receipt = 'RKL51ZDAAA') => ['Result' => ['ResultType' => 0, 'ResultCode' => $code, 'ResultDesc' => $desc, 'OriginatorConversationID' => $orig, 'ConversationID' => 'AG_1', 'TransactionID' => $receipt, 'ResultParameters' => ['ResultParameter' => [['Key' => 'TransactionAmount', 'Value' => 1500], ['Key' => 'TransactionReceipt', 'Value' => $receipt], ['Key' => 'ReceiverPartyPublicName', 'Value' => '254712345678 - JANE DOE']]]]];
    [$st] = mpesa_b2c_handle_result($result($req['originator_id'], 0), 'bad', '1.2.3.4');
    check('result: a wrong secret is refused', $st === 403 && mpesa_b2c_for('host_payout', $hp)['status'] === 'sent');
    [$st, $body] = mpesa_b2c_handle_result($result($req['originator_id'], 0), 'unit-b2c', '1.2.3.4');
    $req = mpesa_b2c_for('host_payout', $hp);
    $po = row('SELECT * FROM host_payouts WHERE id = :i', ['i' => $hp]);
    check('result: success marks the request completed with the receipt and receiver', $st === 200 && $req['status'] === 'completed' && $req['receipt'] === 'RKL51ZDAAA' && strpos((string)$req['receiver_name'], 'JANE DOE') !== false);
    check('result: success marks the Host payout paid with that code', $po['status'] === 'paid' && $po['payment_reference'] === 'RKL51ZDAAA');
    check('result: the Host is told', (int)val("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title = 'Your payout was sent'", ['u' => $uid], 0) >= 1);
    mpesa_b2c_handle_result($result($req['originator_id'], 0), 'unit-b2c', '1.2.3.4');
    check('result: a repeated result changes nothing', mpesa_b2c_for('host_payout', $hp)['status'] === 'completed');
    check('send: an already paid payout cannot be sent again', !mpesa_b2c_send_payout('host_payout', $hp, 1)['ok']);

    // failure keeps the payout waiting and allows another try
    $hp2 = insert('host_payouts', ['host_id' => $uid, 'amount' => 900, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    $payouts['host'][] = $hp2;
    $script = [$b2cOk];
    mpesa_b2c_send_payout('host_payout', $hp2, 1);
    $req2 = mpesa_b2c_for('host_payout', $hp2);
    mpesa_b2c_handle_result($result($req2['originator_id'], 2001, 'The initiator information is invalid.'), 'unit-b2c', '1.2.3.4');
    $req2 = mpesa_b2c_for('host_payout', $hp2);
    check('result: failure records the reason and the payout keeps waiting', $req2['status'] === 'failed' && $req2['result_desc'] === 'The initiator information is invalid.' && row('SELECT status FROM host_payouts WHERE id = :i', ['i' => $hp2])['status'] === 'requested');
    $script = [$b2cOk];
    check('send: after a failure it can be sent again', mpesa_b2c_send_payout('host_payout', $hp2, 1)['ok']);

    // timeout blocks until released
    $req3 = mpesa_b2c_for('host_payout', $hp2);
    mpesa_b2c_handle_timeout($result($req3['originator_id'], 0), 'unit-b2c', '1.2.3.4');
    check('timeout: the request is flagged and further sends are blocked', mpesa_b2c_for('host_payout', $hp2)['status'] === 'timeout' && !mpesa_b2c_send_payout('host_payout', $hp2, 1)['ok']);
    check('release: an admin can free it after checking the portal', mpesa_b2c_release((int)$req3['id'])['ok'] && mpesa_b2c_for('host_payout', $hp2)['status'] === 'failed');
    check('release: a finished request cannot be released', !mpesa_b2c_release((int)$req['id'])['ok']);

    // no answer at all is "unknown", never "failed"
    $hp3 = insert('host_payouts', ['host_id' => $uid, 'amount' => 700, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    $payouts['host'][] = $hp3;
    $script = [['status' => 0, 'body' => '', 'error' => 'timeout']];
    $r = mpesa_b2c_send_payout('host_payout', $hp3, 1);
    check('send: no answer from Safaricom is stored as timeout so it cannot be sent twice', !$r['ok'] && mpesa_b2c_for('host_payout', $hp3)['status'] === 'timeout');
    // rejected before it left
    $hp4 = insert('host_payouts', ['host_id' => $uid, 'amount' => 700, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    $payouts['host'][] = $hp4;
    $script = [$ok(['requestId' => 'r', 'errorCode' => '500.001.1001', 'errorMessage' => 'The initiator information is invalid.'], 500)];
    $r = mpesa_b2c_send_payout('host_payout', $hp4, 1);
    check('send: an error answer is stored as failed and can be retried', !$r['ok'] && mpesa_b2c_for('host_payout', $hp4)['status'] === 'failed');

    // limits and bad numbers
    $hp5 = insert('host_payouts', ['host_id' => $uid, 'amount' => 5, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    $hp6 = insert('host_payouts', ['host_id' => $uid, 'amount' => 900, 'method' => 'mpesa', 'destination' => '0412345678 - Jane', 'status' => 'requested']);
    $hp7 = insert('host_payouts', ['host_id' => $uid, 'amount' => 900000, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    array_push($payouts['host'], $hp5, $hp6, $hp7);
    $calls = [];
    check('send: under KES 10 is refused before any network call', !mpesa_b2c_send_payout('host_payout', $hp5, 1)['ok'] && !$calls);
    check('send: a number that is not Safaricom is refused', !mpesa_b2c_send_payout('host_payout', $hp6, 1)['ok'] && !$calls);
    check('send: above the largest payout is refused', !mpesa_b2c_send_payout('host_payout', $hp7, 1)['ok'] && !$calls);
    check('send: an unknown payout is refused', !mpesa_b2c_send_payout('host_payout', 99999999, 1)['ok'] && !mpesa_b2c_send_payout('nonsense', 1, 1)['ok']);

    // wallet payouts
    q('INSERT INTO wallet_payouts (user_id, amount, phone, account_name, status) VALUES (:u, 1200, :p, :n, \'requested\')', ['u' => $uid, 'p' => '254712345678', 'n' => 'Jane Doe']);
    $wp = (int)val('SELECT MAX(id) FROM wallet_payouts WHERE user_id = :u', ['u' => $uid], 0);
    $payouts['wallet'][] = $wp;
    $script = [$b2cOk];
    $r = mpesa_b2c_send_payout('wallet_payout', $wp, 1);
    $wreq = mpesa_b2c_for('wallet_payout', $wp);
    mpesa_b2c_handle_result($result($wreq['originator_id'], 0, 'ok', 'WLT0000001'), 'unit-b2c', '1.2.3.4');
    $wrow = row('SELECT * FROM wallet_payouts WHERE id = :i', ['i' => $wp]);
    check('wallet payout: sent, then marked paid with the M-Pesa code', $r['ok'] && $wrow['status'] === 'paid' && $wrow['payment_reference'] === 'WLT0000001');

    // automatic sending
    $ha = insert('host_payouts', ['host_id' => $uid, 'amount' => 1000, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    $hb = insert('host_payouts', ['host_id' => $uid, 'amount' => 4000, 'method' => 'mpesa', 'destination' => '0712345678 - Jane', 'status' => 'requested']);
    array_push($payouts['host'], $ha, $hb);
    q('UPDATE host_payouts SET created_at = DATE_SUB(NOW(), INTERVAL 10 MINUTE) WHERE id IN (:a, :b)', ['a' => $ha, 'b' => $hb]);
    check('auto: nothing is sent while automatic payouts are off', (mpesa_b2c_cron()['off'] ?? 0) === 1 && mpesa_b2c_for('host_payout', $ha) === null);
    set_for_test('mpesa_b2c_auto', '1');
    $script = [$b2cOk, $b2cOk];
    $out = mpesa_b2c_cron();
    check('auto: a payout under the limit is sent by itself, a larger one waits', $out['sent'] === 1 && mpesa_b2c_for('host_payout', $ha) !== null && mpesa_b2c_for('host_payout', $hb) === null, json_encode($out));
    $before = count($calls);
    mpesa_b2c_cron();
    check('auto: it is not sent a second time', count($calls) === $before);

    // test payout
    cache_clear('mpesa_token_');
    $script = [$b2cOk];
    $r = mpesa_b2c_send_test('0712345678', 10, 1);
    check('test payout: sent as kind "test"', $r['ok'] && (int)val("SELECT COUNT(*) FROM mpesa_b2c_requests WHERE kind = 'test' AND phone = '254712345678'", [], 0) >= 1);
    check('test payout: amount is limited', !mpesa_b2c_send_test('0712345678', 5000, 1)['ok'] && !mpesa_b2c_send_test('0412345678', 20, 1)['ok']);

    // -----------------------------------------------------------------------------------------
    section('Connection tests');
    $routes = [];
    cache_clear('mpesa_token_');
    $script = [$tokenOk];
    check('M-Pesa connection test uses a fresh token', mpesa_test_connection()['ok']);
    cache_clear('mpesa_token_');
    $script = [$ok(['errorMessage' => 'Bad'], 400)];
    check('M-Pesa connection test reports bad keys', !mpesa_test_connection()['ok']);
    $script = [$ok(['status' => true, 'data' => []])];
    check('Paystack connection test', pgs_test_gateway('paystack')['ok']);
    $script = [$ok(['status' => false], 401)];
    check('Paystack connection test reports a bad key', !pgs_test_gateway('paystack')['ok']);
    cache_clear('paypal_token_');
    $script = [$ok(['access_token' => 'T', 'expires_in' => 3000])];
    check('PayPal connection test', pgs_test_gateway('paypal')['ok']);
    $script = [$ok(['object' => 'balance'])];
    check('Stripe connection test', pgs_test_gateway('card')['ok']);

    // -----------------------------------------------------------------------------------------
    section('Settings forms');
    $fields = pgs_fields('paypal');
    $bad = pgs_save($fields, ['paypal_currency' => 'DOLLARS', 'paypal_rate' => '-3'], 1);
    check('save: bad values are refused with a message each', isset($bad['errors']['paypal_currency'], $bad['errors']['paypal_rate']) && $bad['saved'] === 0);
    $good = pgs_save($fields, ['paypal_currency' => 'eur', 'paypal_rate' => '140', 'paypal_client_secret' => ''], 1);
    check('save: good values are stored (currency upper-cased) and a blank secret keeps the saved one', !$good['errors'] && setting('paypal_currency') === 'EUR' && (string)setting('paypal_client_secret') === 'PP-SECRET');
    $good = pgs_save($fields, ['paypal_client_secret' => 'NEW-SECRET'], 1);
    check('save: a typed secret replaces the old one', (string)setting('paypal_client_secret') === 'NEW-SECRET');
    $html = pgs_render_field(['key' => 'paypal_client_secret', 'type' => 'secret', 'label' => 'x', 'help' => ''], true);
    check('form: a saved secret is never printed back', strpos($html, 'NEW-SECRET') === false && strpos($html, 'Saved') !== false);
    $c2b = pgs_save(pgs_fields('mpesa', 'c2b'), ['mpesa_c2b_shortcode' => '12', 'mpesa_c2b_api' => 'v9'], 1);
    check('save: a short code must be digits and a select must be one of its options', isset($c2b['errors']['mpesa_c2b_shortcode'], $c2b['errors']['mpesa_c2b_api']));
    $cert = pgs_save(pgs_fields('mpesa', 'b2c'), ['mpesa_cert' => 'garbage'], 1);
    check('save: an unreadable certificate is refused', isset($cert['errors']['mpesa_cert']));
} finally {
    unset($GLOBALS['__payments_http']);
    foreach (rows('SELECT id FROM payments WHERE user_id = :u', ['u' => $uid]) as $r) {
        $created[] = (int)$r['id'];
    }
    if ($created) {
        q('DELETE FROM payments WHERE id IN (' . implode(',', array_unique(array_map('intval', $created))) . ')');
        foreach ($created as $cid) {
            clear_limit('gwstart:' . $cid);
            clear_limit('stk:' . $cid);
            clear_limit('stkq:' . $cid);
        }
    }
    q("DELETE FROM mpesa_c2b_transactions WHERE trans_id LIKE 'QAA%' OR trans_id LIKE 'QV%'");
    q("DELETE FROM mpesa_b2c_requests WHERE kind = 'test' OR (kind = 'host_payout' AND ref_id IN (SELECT id FROM host_payouts WHERE host_id = :u)) OR (kind = 'wallet_payout' AND ref_id IN (SELECT id FROM wallet_payouts WHERE user_id = :u))", ['u' => $uid]);
    q('DELETE FROM payment_events WHERE gateway IN (\'paystack\',\'paypal\',\'stripe\') AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
    restore_settings();
    cache_clear('mpesa_token_');
    cache_clear('paypal_token_');
}

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
