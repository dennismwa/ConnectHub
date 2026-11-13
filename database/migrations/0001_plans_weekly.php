<?php
/** Weekly billing option and lower plan prices (Premium KSh 200 / VIP KSh 500 a month). */
return function (): void {
    mig_add_column('subscription_plans', 'price_weekly', 'DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER price_monthly');
    db()->exec("ALTER TABLE user_subscriptions MODIFY billing_period ENUM('weekly','monthly','yearly','custom') NOT NULL DEFAULT 'monthly'");

    // Prices still at the first release's defaults move to the new ones. A price the admin has edited is never touched.
    $new = ['premium' => [70, 200, 1920], 'vip' => [170, 500, 4800]];
    $old = ['premium' => [799, 7190], 'vip' => [1999, 17990]];
    foreach ($new as $slug => [$week, $month, $year]) {
        $plan = row('SELECT id, price_weekly, price_monthly, price_yearly FROM subscription_plans WHERE slug = :s', ['s' => $slug]);
        if (!$plan) {
            continue;
        }
        $m = (float)$plan['price_monthly'];
        $y = (float)$plan['price_yearly'];
        if ($m == $old[$slug][0] && $y == $old[$slug][1]) {
            q('UPDATE subscription_plans SET price_weekly = :w, price_monthly = :m, price_yearly = :y WHERE id = :id', ['w' => $week, 'm' => $month, 'y' => $year, 'id' => $plan['id']]);
        } elseif ((float)$plan['price_weekly'] <= 0 && $m > 0) {
            // custom prices: offer a weekly price at about a third of the monthly one, rounded to the nearest 10
            q('UPDATE subscription_plans SET price_weekly = :w WHERE id = :id', ['w' => max(10, round($m * 0.35 / 10) * 10), 'id' => $plan['id']]);
        }
    }
};
