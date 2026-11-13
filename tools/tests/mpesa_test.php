<?php
/**
 * M-Pesa (Daraja) fixture tests: request building, callback parsing and the callback handler.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/mpesa_test.php
 *
 * Needs a throw-away database (see tools/testdb.sh). It creates one disposable member, pays for
 * "featured" orders on its behalf, and removes everything again. No network access is used:
 * Daraja is replaced by canned responses through $GLOBALS['__payments_http'].
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
/** Forget a rate-limit bucket so the next call is counted from zero. */
function clear_limit(string $bucket): void
{
    q('DELETE FROM rate_limits WHERE bucket = :b', ['b' => substr(sha1($bucket), 0, 40)]);
}
function same($a, $b): bool
{
    return $a === $b;
}

// ---------------------------------------------------------------------------------------------
// Settings used by the tests are put back afterwards
// ---------------------------------------------------------------------------------------------
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
            setting_reset($k);
        } else {
            setting_set($k, $v);
        }
    }
}

$cfg = ['env' => 'sandbox', 'base' => 'https://sandbox.safaricom.co.ke', 'shortcode' => '174379', 'type' => 'CustomerPayBillOnline', 'key' => 'KEY123', 'secret' => 'SECRET456', 'passkey' => 'PASSKEY789'];

// ---------------------------------------------------------------------------------------------
section('Amounts, phone numbers, passwords');
check('whole amount stays', mpesa_amount(799.00) === 799);
check('part shilling rounds up', mpesa_amount(799.40) === 800);
check('tiny amount is at least 1', mpesa_amount(0.2) === 1);
check('float noise does not add a shilling', mpesa_amount(150.000001) === 150);
check('07 number', mpesa_phone('0712 345 678') === '254712345678');
check('01 number', mpesa_phone('0112345678') === '254112345678');
check('+254 number', mpesa_phone('+254 712 345 678') === '254712345678');
check('254 number', mpesa_phone('254712345678') === '254712345678');
check('landline style is refused', mpesa_phone('0412345678') === '');
check('short number is refused', mpesa_phone('12') === '');
check('letters are refused', mpesa_phone('abc') === '');
check('timestamp is Nairobi time', mpesa_timestamp(0) === '19700101030000', mpesa_timestamp(0));
check('timestamp has 14 digits', (bool)preg_match('/^\d{14}$/', mpesa_timestamp()));
check('password is base64(shortcode+passkey+timestamp)', mpesa_password('174379', 'PASSKEY789', '20260101120000') === base64_encode('174379PASSKEY78920260101120000'));

// ---------------------------------------------------------------------------------------------
section('STK push and query request bodies');
$payment = ['id' => 77, 'amount' => '799.00', 'reference' => 'CH261015ABCDEF'];
$stk = mpesa_build_stk_request($payment, '254712345678', $cfg, 'https://example.test/api/mpesa/callback/s3cr3t', '20261015120000');
check('stk: shortcode', same($stk['BusinessShortCode'], '174379'));
check('stk: password', same($stk['Password'], base64_encode('174379PASSKEY78920261015120000')));
check('stk: timestamp', same($stk['Timestamp'], '20261015120000'));
check('stk: transaction type from config', same($stk['TransactionType'], 'CustomerPayBillOnline'));
check('stk: amount is a whole number', same($stk['Amount'], 799));
check('stk: party A and phone', same($stk['PartyA'], '254712345678') && same($stk['PhoneNumber'], '254712345678'));
check('stk: party B is the shortcode', same($stk['PartyB'], '174379'));
check('stk: callback url', same($stk['CallBackURL'], 'https://example.test/api/mpesa/callback/s3cr3t'));
check('stk: account reference is the last 12 characters of the reference', same($stk['AccountReference'], substr('CH261015ABCDEF', -12)) && strlen($stk['AccountReference']) <= 12, $stk['AccountReference']);
check('stk: description at most 13 characters, plain', strlen($stk['TransactionDesc']) <= 13 && (bool)preg_match('/^[A-Za-z0-9 ]+$/', $stk['TransactionDesc']), $stk['TransactionDesc']);
check('stk: body encodes to JSON', json_encode($stk) !== false);
$stkBuy = mpesa_build_stk_request($payment, '254712345678', ['type' => 'CustomerBuyGoodsOnline'] + $cfg, 'https://x.test/cb', '20261015120000');
check('stk: buy goods type is passed through', same($stkBuy['TransactionType'], 'CustomerBuyGoodsOnline'));
$q = mpesa_build_query_request('ws_CO_15102026120000123456789', $cfg, '20261015120100');
check('query: fields', $q === ['BusinessShortCode' => '174379', 'Password' => base64_encode('174379PASSKEY78920261015120100'), 'Timestamp' => '20261015120100', 'CheckoutRequestID' => 'ws_CO_15102026120000123456789']);

// ---------------------------------------------------------------------------------------------
section('Callback parsing (payloads in the shape Safaricom sends)');
$okPayload = json_decode('{"Body":{"stkCallback":{"MerchantRequestID":"29115-34620561-1","CheckoutRequestID":"ws_CO_191220191020363925","ResultCode":0,"ResultDesc":"The service request is processed successfully.","CallbackMetadata":{"Item":[{"Name":"Amount","Value":799.00},{"Name":"MpesaReceiptNumber","Value":"NLJ7RT61SV"},{"Name":"Balance"},{"Name":"TransactionDate","Value":20191219102115},{"Name":"PhoneNumber","Value":254708374149}]}}}}', true);
$p = mpesa_parse_callback($okPayload);
check('success: parsed', is_array($p));
check('success: checkout id', ($p['checkout_id'] ?? '') === 'ws_CO_191220191020363925');
check('success: merchant id', ($p['merchant_id'] ?? '') === '29115-34620561-1');
check('success: code 0', ($p['code'] ?? -9) === 0);
check('success: amount', ($p['amount'] ?? null) === 799.0);
check('success: receipt', ($p['receipt'] ?? '') === 'NLJ7RT61SV');
check('success: phone digits', ($p['phone'] ?? '') === '254708374149');
check('success: an item without a value (Balance) is tolerated', array_key_exists('code', $p));
$cancel = json_decode('{"Body":{"stkCallback":{"MerchantRequestID":"8555-67195-1","CheckoutRequestID":"ws_CO_27072017151044001","ResultCode":1032,"ResultDesc":"Request cancelled by user"}}}', true);
$p = mpesa_parse_callback($cancel);
check('cancelled: parsed with code 1032 and no amount', is_array($p) && $p['code'] === 1032 && $p['amount'] === null && $p['receipt'] === '');
check('string result code is read', (mpesa_parse_callback(['Body' => ['stkCallback' => ['CheckoutRequestID' => 'x', 'ResultCode' => '2001']]])['code'] ?? -9) === 2001);
check('missing result code becomes -1', (mpesa_parse_callback(['Body' => ['stkCallback' => ['CheckoutRequestID' => 'x']]])['code'] ?? -9) === -1);
check('receipt is stripped to letters and digits', (mpesa_parse_callback(['Body' => ['stkCallback' => ['CheckoutRequestID' => 'x', 'ResultCode' => 0, 'CallbackMetadata' => ['Item' => [['Name' => 'MpesaReceiptNumber', 'Value' => "AB12'; DROP--"]]]]]])['receipt'] ?? '') === 'AB12DROP');
check('empty payload is rejected', mpesa_parse_callback([]) === null);
check('no checkout id is rejected', mpesa_parse_callback(['Body' => ['stkCallback' => ['ResultCode' => 0]]]) === null);
check('array checkout id is rejected', mpesa_parse_callback(['Body' => ['stkCallback' => ['CheckoutRequestID' => ['a'], 'ResultCode' => 0]]]) === null);
check('Body as a string is rejected', mpesa_parse_callback(['Body' => 'nope']) === null);

section('Query response parsing and message mapping');
check('query: success', mpesa_parse_query_response(200, '{"ResponseCode":"0","ResultCode":"0","ResultDesc":"The service request is processed successfully."}')['state'] === 'paid');
check('query: cancelled by user', mpesa_parse_query_response(200, '{"ResultCode":"1032","ResultDesc":"Request cancelled by user"}')['state'] === 'failed');
check('query: wrong pin has friendly text', stripos(mpesa_parse_query_response(200, '{"ResultCode":"2001","ResultDesc":"The initiator information is invalid."}')['message'], 'PIN') !== false);
check('query: still processing', mpesa_parse_query_response(500, '{"requestId":"x","errorCode":"500.001.1001","errorMessage":"The transaction is being processed"}')['state'] === 'pending');
check('query: garbage is unknown', mpesa_parse_query_response(502, '<html>Bad gateway</html>')['state'] === 'unknown');
check('message: 0', mpesa_result_message(0) === 'Payment received.');
check('message: 1032 says cancelled', stripos(mpesa_result_message(1032), 'cancel') !== false);
check('message: 1 says balance', stripos(mpesa_result_message(1), 'balance') !== false);
check('message: unknown code falls back to Safaricom text', mpesa_result_message(4242, 'Something specific') === 'Something specific');
check('message: unknown code without text is generic', stripos(mpesa_result_message(4242), 'did not go through') !== false);
check('error: invalid phone', stripos(mpesa_error_message(['errorCode' => '400.002.02', 'errorMessage' => 'Bad Request - Invalid PhoneNumber'], 400), 'phone number') !== false);
check('error: locked subscriber', stripos(mpesa_error_message(['errorCode' => '500.001.1001', 'errorMessage' => 'Unable to lock subscriber, a transaction is already in process for the current subscriber'], 500), 'waiting on that phone') !== false);
check('error: no network', stripos(mpesa_error_message(null, 0), 'could not reach') !== false);
check('error: anything else', stripos(mpesa_error_message(['errorMessage' => 'boom'], 500), 'could not start') !== false);

// ---------------------------------------------------------------------------------------------
section('Callback source checks');
check('exact ip', mpesa_ip_allowed('196.201.214.200', ['196.201.214.200']));
check('other ip', !mpesa_ip_allowed('196.201.214.201', ['196.201.214.200']));
check('/24 range inside', mpesa_ip_allowed('196.201.214.77', ['196.201.214.0/24']));
check('/24 range outside', !mpesa_ip_allowed('196.201.215.77', ['196.201.214.0/24']));
check('/25 range edge', mpesa_ip_allowed('10.0.0.127', ['10.0.0.0/25']) && !mpesa_ip_allowed('10.0.0.128', ['10.0.0.0/25']));
check('/32 range', mpesa_ip_allowed('1.2.3.4', ['1.2.3.4/32']) && !mpesa_ip_allowed('1.2.3.5', ['1.2.3.4/32']));
check('ipv6 /64', mpesa_ip_allowed('2001:db8:1:2::9', ['2001:db8:1:2::/64']) && !mpesa_ip_allowed('2001:db8:1:3::9', ['2001:db8:1:2::/64']));
check('mixed families never match', !mpesa_ip_allowed('1.2.3.4', ['2001:db8::/32']));
check('bad rule is skipped', !mpesa_ip_allowed('1.2.3.4', ['not-an-ip/99', 'x']));
check('empty list matches nothing', !mpesa_ip_allowed('1.2.3.4', []));

// ---------------------------------------------------------------------------------------------
section('Daraja calls with canned responses');
set_for_test('mpesa_env', 'sandbox');
set_for_test('mpesa_shortcode', '174379');
set_for_test('mpesa_consumer_key', 'KEY123');
set_for_test('mpesa_consumer_secret', 'SECRET456');
set_for_test('mpesa_passkey', 'PASSKEY789');
set_for_test('mpesa_enabled', '1');
set_for_test('mpesa_callback_secret', 'unit-test-secret');
set_for_test('mpesa_callback_ips', '');
cache_clear('mpesa_token_');

$calls = [];
$script = [];
$GLOBALS['__payments_http'] = function (string $method, string $url, array $headers, ?string $body) use (&$calls, &$script): array {
    $calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
    $next = array_shift($script);
    return $next ?? ['status' => 0, 'body' => '', 'error' => 'no canned response left'];
};
$tokenOk = ['status' => 200, 'body' => '{"access_token":"TOKEN-ABC","expires_in":"3599"}', 'error' => ''];

$script = [$tokenOk];
$t = mpesa_access_token();
check('token: ok', $t['ok'] && $t['token'] === 'TOKEN-ABC');
check('token: requested with basic auth on the sandbox host', $calls[0]['method'] === 'GET' && strpos($calls[0]['url'], 'https://sandbox.safaricom.co.ke/oauth/v1/generate') === 0 && in_array('Authorization: Basic ' . base64_encode('KEY123:SECRET456'), $calls[0]['headers'], true));
$n = count($calls);
$t2 = mpesa_access_token();
check('token: second call uses the cache', $t2['ok'] && count($calls) === $n);
cache_clear('mpesa_token_');
$script = [['status' => 401, 'body' => '{"errorMessage":"Bad credentials"}', 'error' => '']];
$t3 = mpesa_access_token();
check('token: bad credentials fail softly', !$t3['ok'] && $t3['error'] !== '' && stripos($t3['error'], 'not available') !== false);
$script = [['status' => 0, 'body' => '', 'error' => 'Could not resolve host']];
$t4 = mpesa_access_token();
check('token: no network fails softly with a plain sentence', !$t4['ok'] && stripos($t4['error'], 'could not reach') !== false);

// Disposable member and orders
$email = 'mpesa-test-' . bin2hex(random_bytes(4)) . '@example.test';
$uid = create_account(['email' => $email, 'password' => 'Test#12345pass', 'display_name' => 'Mpesa Tester']);
$created = [];
$mk = function (float $amount = 500.0) use ($uid, &$created): array {
    $p = payment_create($uid, 'featured', 'mpesa', $amount, 'Featured spot (test)');
    $created[] = (int)$p['id'];
    return $p;
};
$fresh = fn(int $id): array => row('SELECT * FROM payments WHERE id = :i', ['i' => $id]) ?: [];
$featured = fn(): ?string => val('SELECT featured_until FROM users WHERE id = :u', ['u' => $uid]) ?: null;

try {
    // STK push through the hook
    cache_clear('mpesa_token_');
    $p1 = $mk(500.0);
    $calls = [];
    $script = [$tokenOk, ['status' => 200, 'body' => '{"MerchantRequestID":"29115-34620561-1","CheckoutRequestID":"ws_CO_UNIT_0001","ResponseCode":"0","ResponseDescription":"Success. Request accepted for processing","CustomerMessage":"Success. Request accepted for processing"}', 'error' => '']];
    $r = payments_mpesa_initiate($p1, '0712 345 678');
    check('initiate: ok', $r['ok'] === true, json_encode($r));
    $stkCall = $calls[1] ?? null;
    check('initiate: STK request goes to processrequest with a bearer token', $stkCall && $stkCall['method'] === 'POST' && str_ends_with($stkCall['url'], '/mpesa/stkpush/v1/processrequest') && in_array('Authorization: Bearer TOKEN-ABC', $stkCall['headers'], true), json_encode($stkCall));
    $sent = json_decode((string)($stkCall['body'] ?? ''), true) ?: [];
    check('initiate: amount and phone in the request', ($sent['Amount'] ?? 0) === 500 && ($sent['PhoneNumber'] ?? '') === '254712345678' && ($sent['PartyA'] ?? '') === '254712345678');
    check('initiate: callback address carries the secret', ($sent['CallBackURL'] ?? '') !== '' && substr((string)$sent['CallBackURL'], -strlen('/api/pay/stk/unit-test-secret')) === '/api/pay/stk/unit-test-secret', (string)($sent['CallBackURL'] ?? ''));
    $row1 = $fresh((int)$p1['id']);
    check('initiate: checkout id stored and method is mpesa', $row1['mpesa_checkout_id'] === 'ws_CO_UNIT_0001' && $row1['payment_method'] === 'mpesa' && $row1['mpesa_phone'] === '254712345678' && $row1['mpesa_merchant_id'] === '29115-34620561-1');

    // Bad phone and push failures leave the payment untouched
    $p2 = $mk(500.0);
    $calls = [];
    $r = payments_mpesa_initiate($p2, '12345');
    check('initiate: bad phone is refused before any network call', !$r['ok'] && count($calls) === 0 && stripos($r['error'], 'Safaricom number') !== false);
    clear_limit('stk:' . $p2['id']);
    cache_clear('mpesa_token_');
    $script = [$tokenOk, ['status' => 400, 'body' => '{"requestId":"1","errorCode":"400.002.02","errorMessage":"Bad Request - Invalid PhoneNumber"}', 'error' => '']];
    $r = payments_mpesa_initiate($p2, '0700000001');
    check('initiate: Daraja rejects the number, friendly error, nothing stored', !$r['ok'] && stripos($r['error'], 'phone number') !== false && empty($fresh((int)$p2['id'])['mpesa_checkout_id']));
    cache_clear('mpesa_token_');
    $script = [$tokenOk, ['status' => 500, 'body' => '{"errorCode":"500.001.1001","errorMessage":"Unable to lock subscriber, a transaction is already in process for the current subscriber"}', 'error' => '']];
    $r = payments_mpesa_initiate($p2, '0700000002');
    check('initiate: busy phone, friendly error', !$r['ok'] && stripos($r['error'], 'waiting on that phone') !== false);
    cache_clear('mpesa_token_');
    $script = [$tokenOk, ['status' => 0, 'body' => '', 'error' => 'Operation timed out']];
    $r = payments_mpesa_initiate($p2, '0700000003');
    check('initiate: timeout, friendly error, no exception', !$r['ok'] && stripos($r['error'], 'could not reach') !== false);
    cache_clear('mpesa_token_');
    $script = [$tokenOk, ['status' => 200, 'body' => '{"ResponseCode":"1","ResponseDescription":"Rejected"}', 'error' => '']];
    $r = payments_mpesa_initiate($p2, '0700000004');
    check('initiate: non-zero ResponseCode is a failure', !$r['ok']);
    set_for_test('mpesa_passkey', '');
    $r = payments_mpesa_initiate($p2, '0700000005');
    check('initiate: not set up gives a plain sentence', !$r['ok'] && stripos($r['error'], 'not set up') !== false);
    set_for_test('mpesa_passkey', 'PASSKEY789');

    // Callback handler
    section('Callback handler');
    $mkCb = fn(string $checkout, int $code, ?float $amount, string $receipt = '', string $merchant = '29115-34620561-1', string $desc = ''): array => ['Body' => ['stkCallback' => array_filter([
        'MerchantRequestID' => $merchant, 'CheckoutRequestID' => $checkout, 'ResultCode' => $code, 'ResultDesc' => $desc !== '' ? $desc : ($code === 0 ? 'The service request is processed successfully.' : 'failed'),
        'CallbackMetadata' => $code === 0 ? ['Item' => [['Name' => 'Amount', 'Value' => $amount], ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt], ['Name' => 'TransactionDate', 'Value' => 20261015120000], ['Name' => 'PhoneNumber', 'Value' => 254712345678]]] : null,
    ], fn($v) => $v !== null)]];

    [$st, $body] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 0, 500.0, 'UNITRCPT01'), 'wrong-secret', '196.201.214.200');
    check('wrong secret: 403 and payment untouched', $st === 403 && $fresh((int)$p1['id'])['status'] === 'pending' && $body['ResultCode'] === 1);
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 0, 500.0, 'UNITRCPT01'), '', '196.201.214.200');
    check('missing secret: 403', $st === 403);

    set_for_test('mpesa_callback_ips', "196.201.214.0/24\n196.201.213.114");
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 0, 500.0, 'UNITRCPT01'), 'unit-test-secret', '203.0.113.9');
    check('address not on the allow-list: 403', $st === 403 && $fresh((int)$p1['id'])['status'] === 'pending');
    set_for_test('mpesa_callback_ips', '');

    [$st, $body] = mpesa_handle_callback(['hello' => 'world'], 'unit-test-secret', '196.201.214.200');
    check('malformed body: 400', $st === 400 && $body['ResultCode'] === 1);
    [$st, $body] = mpesa_handle_callback($mkCb('ws_CO_DOES_NOT_EXIST', 0, 500.0, 'UNITRCPT99'), 'unit-test-secret', '196.201.214.200');
    check('unknown checkout id: acknowledged, nothing changes', $st === 200 && $body['ResultCode'] === 0);

    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 0, 500.0, 'UNITRCPT01', 'OTHER-MERCHANT-ID'), 'unit-test-secret', '196.201.214.200');
    check('merchant id mismatch is ignored', $st === 200 && $fresh((int)$p1['id'])['status'] === 'pending');

    $before = $featured();
    [$st, $body] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 0, 500.0, 'UNITRCPT01'), 'unit-test-secret', '196.201.214.200');
    $row1 = $fresh((int)$p1['id']);
    check('success: acknowledged with ResultCode 0', $st === 200 && $body['ResultCode'] === 0);
    check('success: payment completed with receipt', $row1['status'] === 'completed' && $row1['mpesa_receipt'] === 'UNITRCPT01', json_encode([$row1['status'], $row1['mpesa_receipt']]));
    check('success: order fulfilled exactly once', !empty($row1['fulfilled_at']) && $featured() !== null && $featured() !== $before);
    $after = $featured();
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 0, 500.0, 'UNITRCPT01'), 'unit-test-secret', '196.201.214.200');
    check('duplicate success: acknowledged, nothing delivered twice', $st === 200 && $featured() === $after);
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_0001', 1032, null), 'unit-test-secret', '196.201.214.200');
    check('late failure after success does not undo it', $st === 200 && $fresh((int)$p1['id'])['status'] === 'completed');

    // Failure codes
    foreach ([[1032, 'cancelled', 'cancel'], [1037, 'failed', 'hear back'], [2001, 'failed', 'PIN'], [1, 'failed', 'balance'], [1019, 'failed', 'expired'], [9999, 'failed', 'could not process']] as [$code, $want, $needle]) {
        $pf = $mk(500.0);
        $cid = 'ws_CO_UNIT_F' . $code;
        update('payments', ['mpesa_checkout_id' => $cid, 'payment_method' => 'mpesa'], ['id' => (int)$pf['id']]);
        [$st] = mpesa_handle_callback($mkCb($cid, $code, null), 'unit-test-secret', '196.201.214.200');
        $rf = $fresh((int)$pf['id']);
        check("failure $code: $want with a plain message", $st === 200 && $rf['status'] === $want && stripos((string)$rf['mpesa_result_desc'], $needle) !== false && empty($rf['fulfilled_at']), json_encode([$rf['status'], $rf['mpesa_result_desc']]));
    }

    // Amount mismatch
    $pm = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_MIS', 'payment_method' => 'mpesa'], ['id' => (int)$pm['id']]);
    $fBefore = $featured();
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_MIS', 0, 50.0, 'UNITRCPT02'), 'unit-test-secret', '196.201.214.200');
    $rm = $fresh((int)$pm['id']);
    check('amount mismatch: not delivered, flagged for support', $st === 200 && $rm['status'] === 'failed' && stripos((string)$rm['mpesa_result_desc'], 'Amount mismatch') !== false && empty($rm['fulfilled_at']) && $featured() === $fBefore, json_encode([$rm['status'], $rm['mpesa_result_desc']]));

    // Same receipt on a different order
    $pd = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_DUP', 'payment_method' => 'mpesa'], ['id' => (int)$pd['id']]);
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_DUP', 0, 500.0, 'UNITRCPT01'), 'unit-test-secret', '196.201.214.200');
    $rd = $fresh((int)$pd['id']);
    check('receipt already used by another payment: refused', $st === 200 && $rd['status'] === 'failed' && empty($rd['fulfilled_at']), json_encode([$rd['status'], $rd['mpesa_result_desc']]));

    // Overpayment is accepted
    $po = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_OVER', 'payment_method' => 'mpesa'], ['id' => (int)$po['id']]);
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_OVER', 0, 600.0, 'UNITRCPT03'), 'unit-test-secret', '196.201.214.200');
    check('amount above the price is accepted', $st === 200 && $fresh((int)$po['id'])['status'] === 'completed');

    // No secret configured: only the IP list can vouch for the sender
    set_for_test('mpesa_callback_secret', '');
    $pn = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_NOSEC', 'payment_method' => 'mpesa'], ['id' => (int)$pn['id']]);
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_NOSEC', 0, 500.0, 'UNITRCPT04'), '', '196.201.214.200');
    check('no secret and no allow-list: nothing is trusted', $st === 403 && $fresh((int)$pn['id'])['status'] === 'pending');
    set_for_test('mpesa_callback_ips', '196.201.214.0/24');
    [$st] = mpesa_handle_callback($mkCb('ws_CO_UNIT_NOSEC', 0, 500.0, 'UNITRCPT04'), '', '196.201.214.200');
    check('no secret but allowed address: accepted', $st === 200 && $fresh((int)$pn['id'])['status'] === 'completed');
    set_for_test('mpesa_callback_ips', '');
    set_for_test('mpesa_callback_secret', 'unit-test-secret');

    // Reconciliation by STK query when the callback never arrives
    section('Query reconciliation');
    $pr = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_Q1', 'payment_method' => 'mpesa'], ['id' => (int)$pr['id']]);
    payment_meta_merge((int)$pr['id'], ['stk_at' => time() - 40]);
    clear_limit('stkq:' . $pr['id']);
    $calls = [];
    cache_clear('mpesa_token_');
    $script = [$tokenOk, ['status' => 500, 'body' => '{"requestId":"x","errorCode":"500.001.1001","errorMessage":"The transaction is being processed"}', 'error' => '']];
    $out = payments_mpesa_reconcile($fresh((int)$pr['id']));
    check('query: still processing keeps the payment open', $out['status'] === 'pending');
    check('query: request body names the checkout id', (json_decode((string)($calls[1]['body'] ?? ''), true)['CheckoutRequestID'] ?? '') === 'ws_CO_UNIT_Q1' && str_ends_with((string)($calls[1]['url'] ?? ''), '/mpesa/stkpushquery/v1/query'));
    clear_limit('stkq:' . $pr['id']);
    $script = [['status' => 200, 'body' => '{"ResponseCode":"0","ResultCode":"0","ResultDesc":"The service request is processed successfully."}', 'error' => '']];
    $out = payments_mpesa_reconcile($fresh((int)$pr['id']));
    check('query: paid completes the order', $out['status'] === 'completed' && !empty($out['fulfilled_at']));
    $pq = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_Q2', 'payment_method' => 'mpesa'], ['id' => (int)$pq['id']]);
    payment_meta_merge((int)$pq['id'], ['stk_at' => time() - 40]);
    clear_limit('stkq:' . $pq['id']);
    $script = [['status' => 200, 'body' => '{"ResultCode":"1032","ResultDesc":"Request cancelled by user"}', 'error' => '']];
    $out = payments_mpesa_reconcile($fresh((int)$pq['id']));
    check('query: cancelled on the phone closes the order', in_array($out['status'], ['cancelled', 'failed'], true) && empty($out['fulfilled_at']));
    $pt = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_Q3', 'payment_method' => 'mpesa'], ['id' => (int)$pt['id']]);
    payment_meta_merge((int)$pt['id'], ['stk_at' => time() - 40]);
    clear_limit('stkq:' . $pt['id']);
    $script = [['status' => 0, 'body' => '', 'error' => 'timeout']];
    $out = payments_mpesa_reconcile($fresh((int)$pt['id']));
    check('query: network trouble leaves the order open', $out['status'] === 'pending');
    $young = $mk(500.0);
    update('payments', ['mpesa_checkout_id' => 'ws_CO_UNIT_Q4', 'payment_method' => 'mpesa'], ['id' => (int)$young['id']]);
    payment_meta_merge((int)$young['id'], ['stk_at' => time() - 2]);
    $calls = [];
    payments_mpesa_reconcile($fresh((int)$young['id']));
    check('query: not asked in the first seconds', count($calls) === 0);

    // Callback url helper
    check('callback url ends with the secret', substr(mpesa_callback_url(), -strlen('/api/pay/stk/unit-test-secret')) === '/api/pay/stk/unit-test-secret');
} finally {
    unset($GLOBALS['__payments_http']);
    if ($created) {
        q('DELETE FROM payments WHERE id IN (' . implode(',', array_map('intval', $created)) . ')');
    }
    foreach ($created as $cid) {
        clear_limit('stk:' . $cid);
        clear_limit('stkq:' . $cid);
    }
    q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
    restore_settings();
    cache_clear('mpesa_token_');
}

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
