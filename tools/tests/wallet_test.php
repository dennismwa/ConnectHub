<?php
/**
 * Gift wallet and admin alerts: claim, double claim, verification mode, payout hold, reject refund, paid, limits, adjustments.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/wallet_test.php
 *
 * Creates two disposable members, runs real gifts through payment_complete (no network) and removes everything again.
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
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $name . (!$ok && $detail !== '' ? "  ($detail)" : '') . "\n";
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
$ids = [];
register_shutdown_function(function () use (&$ids, &$touched) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    if ($ids) {
        q('DELETE FROM payments WHERE user_id IN (:ids)', ['ids' => $ids]);
        q('DELETE FROM notifications WHERE user_id IN (:ids)', ['ids' => $ids]);
        q('DELETE FROM users WHERE id IN (:ids)', ['ids' => $ids]);
    }
});

$stamp = substr(md5((string)microtime(true)), 0, 8);
$county = (int)val("SELECT id FROM locations WHERE type = 'county' AND is_active = 1 ORDER BY id LIMIT 1", [], 0) ?: null;
$sender = create_account(['email' => "w-s-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => 'Gift Sender', 'date_of_birth' => '1990-05-05', 'gender' => 'male', 'county_id' => $county]);
$recv = create_account(['email' => "w-r-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => 'Gift Receiver', 'date_of_birth' => '1992-05-05', 'gender' => 'female', 'county_id' => $county]);
$ids = [$sender, $recv];

set_for_test('gifts_enabled', '1');
set_for_test('wallet_enabled', '1');
set_for_test('wallet_payouts_enabled', '1');
set_for_test('gift_receiver_percent', '70');
set_for_test('gift_claim_mode', 'auto');
set_for_test('wallet_min_payout', '100');
set_for_test('wallet_max_open_payouts', '2');
set_for_test('admin_alert_gift', '1');
set_for_test('mail_driver', 'log');
set_for_test('admin_alert_email', "owner-$stamp@test.invalid");
$gt = (int)val('SELECT id FROM gift_types WHERE is_active = 1 ORDER BY price LIMIT 1');
if (!$gt) {
    $gt = insert('gift_types', ['name' => 'Test rose', 'emoji' => '🌹', 'price' => 1000, 'is_active' => 1, 'sort_order' => 99]);
}
q('UPDATE gift_types SET price = 1000 WHERE id = :id', ['id' => $gt]);

function send_gift(int $from, int $to, int $gt, float $amount = 1000.0): int
{
    $p = payment_create($from, 'gift', 'mpesa', $amount, 'Gift test', ['gift_type_id' => $gt, 'to' => $to, 'message' => 'hi']);
    payment_complete((int)$p['id'], ['mpesa_receipt' => 'TST' . strtoupper(substr(md5((string)microtime(true) . $from), 0, 8))]);
    return (int)val('SELECT id FROM sent_gifts WHERE payment_id = :p', ['p' => $p['id']]);
}

echo "\nClaiming\n";
$g1 = send_gift($sender, $recv, $gt);
check('gift delivered', $g1 > 0);
check('wallet starts empty', wallet_balance($recv) == 0.0);
check('state is claimable in auto mode', wallet_gift_state(row('SELECT * FROM sent_gifts WHERE id = :id', ['id' => $g1])) === 'claimable');
[$ok, , $amt] = wallet_claim_gift($recv, $g1);
check('claim adds 70% of the price', $ok && $amt == 700.0 && wallet_balance($recv) == 700.0);
[$ok2] = wallet_claim_gift($recv, $g1);
check('a gift cannot be claimed twice', !$ok2 && wallet_balance($recv) == 700.0);
[$ok3] = wallet_claim_gift($sender, $g1);
check('the sender cannot claim it', !$ok3);
check('one wallet row for the gift', (int)val("SELECT COUNT(*) FROM wallet_transactions WHERE user_id = :u AND kind = 'gift'", ['u' => $recv]) === 1);

echo "\nPercentage and price are taken from the payment\n";
set_for_test('gift_receiver_percent', '50');
q('UPDATE gift_types SET price = 5000 WHERE id = :id', ['id' => $gt]);
$g2 = send_gift($sender, $recv, $gt, 1000.0);
q('UPDATE gift_types SET price = 1000 WHERE id = :id', ['id' => $gt]);
[$ok] = wallet_claim_gift($recv, $g2);
check('uses what was paid, not the current price', $ok && wallet_balance($recv) == 1200.0, (string)wallet_balance($recv));
set_for_test('gift_receiver_percent', '70');

echo "\nVerify first mode\n";
set_for_test('gift_claim_mode', 'verified');
$g3 = send_gift($sender, $recv, $gt);
[$ok] = wallet_claim_gift($recv, $g3);
check('cannot claim before the admin checks', !$ok && wallet_balance($recv) == 1200.0);
check('claim_all skips unchecked gifts', wallet_claim_all($recv)[0] === 0);
[$v] = wallet_gift_verify($g3, 'seen in paybill', 1);
check('admin check works', $v);
check('checking twice is refused', !wallet_gift_verify($g3, '', 1)[0]);
[$ok] = wallet_claim_gift($recv, $g3);
check('claim works after the check', $ok && wallet_balance($recv) == 1900.0);
check('receiver is told when checked', (int)val("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title LIKE '%ready to claim%'", ['u' => $recv]) === 1);
set_for_test('gift_claim_mode', 'auto');

echo "\nNumbers\n";
check('bad phone is refused', !wallet_account_save($recv, '123', 'Gift Receiver')[0]);
check('bad name is refused', !wallet_account_save($recv, '0712345678', 'x')[0]);
[$ok, , $acc1] = wallet_account_save($recv, '0712 345 678', 'Gift Receiver', 'Main');
check('number saves normalised', $ok && (string)val('SELECT phone FROM wallet_accounts WHERE id = :id', ['id' => $acc1]) === '254712345678');
[, , $same] = wallet_account_save($recv, '+254712345678', 'Gift R Receiver');
check('same number is updated, not duplicated', $same === $acc1 && (int)val('SELECT COUNT(*) FROM wallet_accounts WHERE user_id = :u', ['u' => $recv]) === 1);
[$ok, , $acc2] = wallet_account_save($recv, '0722 000 111', 'Gift Receiver', 'Work');
check('a second number saves', $ok && $acc2 > 0);
check('numbers belong to their owner', !wallet_account_delete($sender, $acc2) && wallet_account_delete($recv, $acc2));
set_for_test('wallet_max_numbers', '1');
check('number limit is enforced', !wallet_account_save($recv, '0733 000 222', 'Gift Receiver')[0]);

echo "\nPayouts\n";
[$ok, $msg] = wallet_request_payout($recv, 50, $acc1);
check('below the minimum is refused', !$ok, $msg);
[$ok] = wallet_request_payout($recv, 99999, $acc1);
check('more than the balance is refused', !$ok);
[$ok] = wallet_request_payout($recv, 500, 999999);
check('someone else\'s number is refused', !$ok);
[$ok] = wallet_request_payout($sender, 500, $acc1);
check('another member cannot use my number', !$ok);
[$ok, , $po1] = wallet_request_payout($recv, 800, $acc1);
check('payout request works and holds the money', $ok && wallet_balance($recv) == 1100.0);
[$ok, , $po2] = wallet_request_payout($recv, 300, $acc1);
check('second request works', $ok && wallet_balance($recv) == 800.0);
[$ok] = wallet_request_payout($recv, 100, $acc1);
check('open request limit is enforced', !$ok && wallet_balance($recv) == 800.0);
check('the number is copied to the request', (string)val('SELECT phone FROM wallet_payouts WHERE id = :id', ['id' => $po1]) === '254712345678');
[$ok] = wallet_payout_reject($po2, 'Name does not match', 1);
check('reject refunds the held amount', $ok && wallet_balance($recv) == 1100.0);
check('reject twice does nothing', !wallet_payout_reject($po2, 'again', 1)[0] && wallet_balance($recv) == 1100.0);
[$ok] = wallet_payout_mark_paid($po1, 'QGH7XK2LMN', 'sent', 1);
check('mark paid works', $ok && (string)val('SELECT status FROM wallet_payouts WHERE id = :id', ['id' => $po1]) === 'paid');
check('paid does not change the balance again', wallet_balance($recv) == 1100.0);
check('cannot pay or reject a paid request', !wallet_payout_mark_paid($po1, 'ABCDEF123', '', 1)[0] && !wallet_payout_reject($po1, 'x', 1)[0]);
check('receiver was notified', (int)val("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title IN ('Your payout was sent','Your payout request was declined')", ['u' => $recv]) === 2);

echo "\nSwitches and corrections\n";
set_for_test('wallet_payouts_enabled', '0');
check('payouts can be paused', !wallet_request_payout($recv, 200, $acc1)[0]);
set_for_test('wallet_payouts_enabled', '1');
check('cannot take a wallet below zero', !wallet_adjust($recv, -99999, 'test', 1)[0]);
check('admin can add money', wallet_adjust($recv, 50, 'bonus', 1)[0] && wallet_balance($recv) == 1150.0);
set_for_test('wallet_enabled', '0');
check('wallet off blocks claiming', !wallet_claim_gift($recv, send_gift($sender, $recv, $gt))[0]);
set_for_test('wallet_enabled', '1');

echo "\nAdmin alert email (log driver)\n";
$dir = ROOT . '/storage/mail';
$found = false;
foreach (glob($dir . '/*.html') ?: [] as $f) {
    if (strpos((string)file_get_contents($f), "owner-$stamp@test.invalid") !== false) {
        $found = true;
        @unlink($f);
    }
}
check('a gift sends the owner an alert', $found);
set_for_test('admin_alert_gift', '0');
check('alerts can be switched off', admin_alert('gift', 't', 'b') === false);

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
