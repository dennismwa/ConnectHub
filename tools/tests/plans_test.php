<?php
/**
 * Plans and billing tests: weekly, monthly and yearly buying, carrying over days when a member switches plan,
 * boost credits per period, and a late M-Pesa success for an order that was timed out.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/plans_test.php
 *
 * Needs a throw-away database (see tools/testdb.sh). It creates one disposable member and removes it again.
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

$prem = row("SELECT * FROM subscription_plans WHERE slug = 'premium'");
$vip = row("SELECT * FROM subscription_plans WHERE slug = 'vip'");
if (!$prem || !$vip) {
    exit("The premium and vip plans are needed.\n");
}
$email = 'plans.test.' . bin2hex(random_bytes(3)) . '@example.test';
$uid = create_account(['email' => $email, 'password' => 'Test#12345pass', 'display_name' => 'Plans Tester']);
$days = fn(string $exp): float => (strtotime($exp) - time()) / 86400;

try {
    section('Prices and periods');
    check('premium costs 200 a month', (float)$prem['price_monthly'] === 200.0, (string)$prem['price_monthly']);
    check('vip costs 500 a month', (float)$vip['price_monthly'] === 500.0, (string)$vip['price_monthly']);
    check('every period has a price', (float)$prem['price_weekly'] > 0 && (float)$prem['price_yearly'] > 0 && (float)$vip['price_weekly'] > 0 && (float)$vip['price_yearly'] > 0);
    check('period lengths are 7, 30 and 365 days', plan_period_days('weekly') === 7 && plan_period_days('monthly') === 30 && plan_period_days('yearly') === 365);

    section('Buying and renewing');
    $e1 = subscription_activate($uid, (int)$prem['id'], 'weekly');
    check('a weekly premium plan runs for 7 days', abs($days($e1) - 7) < 0.01, (string)$days($e1));
    $e2 = subscription_activate($uid, (int)$prem['id'], 'monthly');
    check('renewing the same plan adds time to the end', abs($days($e2) - 37) < 0.01, (string)$days($e2));
    check('the billing period shows the latest one bought', (string)val("SELECT billing_period FROM user_subscriptions WHERE user_id = :u AND status = 'active'", ['u' => $uid]) === 'monthly');
    check('a different plan is not touched by the renewal', (int)val("SELECT COUNT(*) FROM user_subscriptions WHERE user_id = :u AND status = 'active'", ['u' => $uid]) === 1);

    section('Boost credits follow the time bought');
    $cr = fn() => (int)val('SELECT COUNT(*) FROM boost_credits WHERE user_id = :u', ['u' => $uid]);
    $f = plan_features_decoded($prem);
    $perMonth = (int)($f['boost_credits_monthly'] ?? 0);
    q('DELETE FROM boost_credits WHERE user_id = :u', ['u' => $uid]);
    subscription_activate($uid, (int)$prem['id'], 'yearly');
    check('a year earns twelve months of credits', $cr() === $perMonth * 12, $cr() . ' vs ' . $perMonth * 12);
    q('DELETE FROM boost_credits WHERE user_id = :u', ['u' => $uid]);

    section('Switching plan keeps the value of the days left');
    q("UPDATE user_subscriptions SET status = 'cancelled' WHERE user_id = :u", ['u' => $uid]);
    subscription_activate($uid, (int)$prem['id'], 'monthly', null, 300); // 300 days of premium left (bought as monthly)
    $credit = subscription_switch_credit($uid, (int)$vip['id'], 'monthly');
    $expected = (int)floor(300 * ((float)$prem['price_monthly'] / 30) / ((float)$vip['price_monthly'] / 30));
    check('300 premium days convert at the price ratio', $credit['days'] === $expected || $credit['days'] === $expected - 1, $credit['days'] . ' vs ' . $expected);
    check('the old plan is named and its days counted', $credit['from'] === 'Premium' && $credit['left'] >= 299, json_encode($credit));
    check('the same plan carries nothing over', subscription_switch_credit($uid, (int)$prem['id'], 'monthly')['days'] === 0);
    $e3 = subscription_activate($uid, (int)$vip['id'], 'monthly');
    check('vip runs for 30 days plus the carried days', abs($days($e3) - (30 + $credit['days'])) < 1.01, (string)$days($e3));
    check('the premium plan is closed', (int)val("SELECT COUNT(*) FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id WHERE s.user_id = :u AND s.status = 'active' AND p.slug = 'premium'", ['u' => $uid]) === 0);
    check('an administrator grant carries nothing over', (function () use ($uid, $prem, $days) {
        $before = $days(subscription_activate($uid, (int)$prem['id'], 'custom', 1, 10));
        return abs($before - 10) < 0.01;
    })());
    check('a free or unpriced period carries nothing over', subscription_switch_credit($uid, (int)$vip['id'], 'custom')['days'] === 0);

    section('A late M-Pesa success for a timed-out order');
    $pay = payment_create($uid, 'featured', 'mpesa', 100.0, 'Late success test');
    q("UPDATE payments SET status = 'cancelled', mpesa_result_desc = 'Timed out' WHERE id = :id", ['id' => $pay['id']]);
    $late = row('SELECT * FROM payments WHERE id = :id', ['id' => $pay['id']]);
    $res = ['code' => 0, 'desc' => 'ok', 'amount' => 100.0, 'receipt' => 'LATE' . strtoupper(bin2hex(random_bytes(3))), 'merchant_id' => '', 'phone' => '', 'date' => ''];
    check('the late success is honoured', mpesa_apply_result($late, $res) === 'completed');
    check('the order is now completed and delivered', (string)val('SELECT status FROM payments WHERE id = :id', ['id' => $pay['id']]) === 'completed' && val('SELECT fulfilled_at FROM payments WHERE id = :id', ['id' => $pay['id']]) !== null);
    $again = row('SELECT * FROM payments WHERE id = :id', ['id' => $pay['id']]);
    check('a repeat of the same callback does nothing', mpesa_apply_result($again, $res) === 'duplicate');
    $pay2 = payment_create($uid, 'featured', 'mpesa', 100.0, 'Late failure test');
    q("UPDATE payments SET status = 'cancelled' WHERE id = :id", ['id' => $pay2['id']]);
    $c2 = row('SELECT * FROM payments WHERE id = :id', ['id' => $pay2['id']]);
    check('a late failure changes nothing', mpesa_apply_result($c2, ['code' => 1, 'desc' => 'no', 'amount' => null, 'receipt' => '', 'merchant_id' => '', 'phone' => '', 'date' => '']) === 'duplicate');
    $pay3 = payment_create($uid, 'featured', 'mpesa', 100.0, 'Late wrong amount');
    q("UPDATE payments SET status = 'cancelled' WHERE id = :id", ['id' => $pay3['id']]);
    $c3 = row('SELECT * FROM payments WHERE id = :id', ['id' => $pay3['id']]);
    check('a late success for too little money is refused', mpesa_apply_result($c3, ['code' => 0, 'desc' => 'ok', 'amount' => 10.0, 'receipt' => 'SHORT' . strtoupper(bin2hex(random_bytes(3))), 'merchant_id' => '', 'phone' => '', 'date' => '']) === 'mismatch' && (string)val('SELECT status FROM payments WHERE id = :id', ['id' => $pay3['id']]) !== 'completed');
} finally {
    q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
}

echo "\n" . ($fail === 0 ? "$pass passed, 0 failed\n" : "$pass passed, $fail FAILED\n");
exit($fail === 0 ? 0 : 1);
