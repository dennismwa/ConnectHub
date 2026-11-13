<?php
/**
 * Automatic plan renewal.
 *
 * A member opts in (at checkout, or later on the Billing page) and picks how the renewal is paid:
 *   card   a saved card is charged by Paystack or Stripe, with nobody present;
 *   mpesa  an M-Pesa prompt is sent to their phone and they approve it with their PIN (M-Pesa cannot be charged without the PIN);
 *   link   they get a reminder with a pay link (PayPal, bank and manual payments cannot be charged automatically).
 * The renewal is charged one day before the plan ends, so nothing is ever switched off in between. A failed charge is retried a few
 * times, then automatic renewal is switched off and the member is told. cron/run.php calls autorenew_run() every five minutes.
 */

declare(strict_types=1);

function autorenew_enabled(): bool
{
    return (bool)setting('autorenew_enabled');
}

/** The active, not-cancelled subscription of a member, with its plan, or null. */
function autorenew_subscription(int $uid): ?array
{
    return row("SELECT s.*, p.slug AS plan_slug, p.name AS plan_name, p.tier AS plan_tier FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
                WHERE s.user_id = :u AND s.status = 'active' AND s.expires_at > NOW() ORDER BY p.tier DESC, s.expires_at DESC LIMIT 1", ['u' => $uid]) ?: null;
}

function autorenew_period(array $sub): string
{
    return in_array((string)$sub['billing_period'], ['weekly', 'monthly', 'yearly'], true) ? (string)$sub['billing_period'] : 'monthly';
}

/** What the next renewal costs today, or 0 when the plan is no longer sold at that period. */
function autorenew_price(array $sub): float
{
    $plan = row('SELECT * FROM subscription_plans WHERE id = :id AND is_active = 1', ['id' => $sub['plan_id']]);
    if (!$plan) {
        return 0.0;
    }
    $period = autorenew_period($sub);
    if ($period !== 'monthly' && !isset(plan_periods_for($plan)[$period])) {
        return 0.0;
    }
    return (float)plan_period_price($plan, $period);
}

/** Turn automatic renewal on for the member's current plan. $method: card | mpesa | link. @return array ['ok'=>bool, 'error'=>string] */
function autorenew_turn_on(int $uid, string $method, ?int $savedId = null, string $phone = ''): array
{
    if (!autorenew_enabled()) {
        return ['ok' => false, 'error' => 'Automatic renewal is not available right now.'];
    }
    $sub = autorenew_subscription($uid);
    if (!$sub) {
        return ['ok' => false, 'error' => 'You need a paid plan first.'];
    }
    if (autorenew_price($sub) <= 0) {
        return ['ok' => false, 'error' => 'This plan can no longer be renewed automatically. Choose a plan on the plans page.'];
    }
    $data = ['auto_renew' => 1, 'renew_method' => $method, 'renew_saved_id' => null, 'renew_phone' => null, 'renew_tries' => 0, 'renew_next_at' => null, 'cancelled_at' => null];
    if ($method === 'card') {
        $saved = $savedId ? pay_saved_get($uid, $savedId) : null;
        if (!$saved) {
            return ['ok' => false, 'error' => 'Choose a saved card. You can add one by paying for your plan with a card and ticking automatic renewal.'];
        }
        $data['renew_saved_id'] = (int)$saved['id'];
    } elseif ($method === 'mpesa') {
        $p = mpesa_phone($phone);
        if ($p === '' || !payments_methods()['mpesa']['ready'] || !payments_methods()['mpesa']['enabled']) {
            return ['ok' => false, 'error' => $p === '' ? 'Enter a Safaricom number such as 0712 345 678.' : 'M-Pesa prompts are not available right now.'];
        }
        $data['renew_phone'] = $p;
    } elseif ($method !== 'link') {
        return ['ok' => false, 'error' => 'Choose how to renew.'];
    }
    update('user_subscriptions', $data, ['id' => (int)$sub['id']]);
    plans_forget($uid);
    return ['ok' => true, 'error' => ''];
}

function autorenew_turn_off(int $uid): void
{
    q("UPDATE user_subscriptions SET auto_renew = 0, renew_tries = 0, renew_next_at = NULL WHERE user_id = :u AND status = 'active'", ['u' => $uid]);
    plans_forget($uid);
}

/** Plain-language state for the Billing page. */
function autorenew_summary(array $sub): array
{
    $on = !empty($sub['auto_renew']) && empty($sub['cancelled_at']) && autorenew_enabled();
    $how = '';
    if ($on) {
        if ($sub['renew_method'] === 'card') {
            $s = !empty($sub['renew_saved_id']) ? pay_saved_get((int)$sub['user_id'], (int)$sub['renew_saved_id']) : null;
            $how = $s ? 'your ' . pay_saved_label($s) : 'your saved card';
        } elseif ($sub['renew_method'] === 'mpesa') {
            $how = 'an M-Pesa request to ' . hosts_phone_local((string)$sub['renew_phone']);
        } else {
            $how = 'a reminder with a pay link';
        }
    }
    return ['on' => $on, 'how' => $how, 'price' => autorenew_price($sub), 'date' => (string)$sub['expires_at'], 'tries' => (int)$sub['renew_tries']];
}

/**
 * Called when a plan payment completes (from payment_complete). Records the renewal choice made at checkout, or, for a payment
 * made by the renewal job itself, resets the retry counter.
 */
function autorenew_after_payment(array $payment, array $meta, int $uid): void
{
    if (!autorenew_enabled()) {
        return;
    }
    $renewal = !empty($meta['renewal']);
    if (empty($meta['auto_renew']) && !$renewal) {
        // paid by hand while automatic renewal is already on: the failed tries no longer count
        q('UPDATE user_subscriptions SET renew_tries = 0, renew_next_at = NULL WHERE user_id = :u AND auto_renew = 1', ['u' => $uid]);
        return;
    }
    $sub = autorenew_subscription($uid);
    if (!$sub) {
        return;
    }
    if ($renewal) {
        q('UPDATE user_subscriptions SET renew_tries = 0, renew_next_at = NULL WHERE id = :id', ['id' => $sub['id']]);
        return;
    }
    $data = ['auto_renew' => 1, 'renew_tries' => 0, 'renew_next_at' => null, 'renew_saved_id' => null, 'renew_phone' => null];
    if (!empty($meta['renew_saved_id']) && pay_saved_get($uid, (int)$meta['renew_saved_id'])) {
        $data['renew_method'] = 'card';
        $data['renew_saved_id'] = (int)$meta['renew_saved_id'];
    } elseif (($payment['payment_method'] ?? '') === 'mpesa' && !empty($payment['mpesa_phone'])) {
        $data['renew_method'] = 'mpesa';
        $data['renew_phone'] = (string)$payment['mpesa_phone'];
    } else {
        $data['renew_method'] = 'link';
    }
    update('user_subscriptions', $data, ['id' => (int)$sub['id']]);
    plans_forget($uid);
    notify($uid, 'payment', 'Automatic renewal is on', 'Your ' . $sub['plan_name'] . ' plan renews itself. You can switch this off any time on the Billing page.', '/billing');
}

/** In-app message plus an email: a charge is something a member should not miss. */
function autorenew_tell(int $uid, string $title, string $body, string $path = '/billing'): void
{
    notify($uid, 'payment', $title, $body, $path);
    $to = pg_user_email($uid);
    if (valid_email($to)) {
        $name = (string)val('SELECT display_name FROM user_profiles WHERE user_id = :u', ['u' => $uid], '');
        send_template_mail($to, $title, 'notification', ['name' => $name, 'title' => $title, 'body' => $body, 'url' => abs_url($path)]);
    }
}

/** Try to renew one subscription. Returns charged | waiting | reminded | retry | gave_up | skipped. */
function autorenew_charge(array $sub): string
{
    $uid = (int)$sub['user_id'];
    $user = row('SELECT * FROM users WHERE id = :id', ['id' => $uid]);
    if (!$user || $user['status'] !== 'active') {
        return 'skipped';
    }
    // an M-Pesa prompt or pay link that is still open: give the member time
    $open = (int)val("SELECT COUNT(*) FROM payments WHERE user_id = :u AND payment_type = 'subscription' AND status = 'pending' AND metadata LIKE '%\"renewal\":1%' AND created_at > DATE_SUB(NOW(), INTERVAL 3 HOUR)", ['u' => $uid], 0);
    if ($open > 0) {
        return 'waiting';
    }
    $max = max(1, (int)setting('autorenew_max_tries'));
    if ((int)$sub['renew_tries'] >= $max) {
        autorenew_turn_off($uid);
        autorenew_tell($uid, 'Automatic renewal is off', 'We could not renew your ' . $sub['plan_name'] . ' plan after ' . plural($max, 'try', 'tries') . ', so automatic renewal is switched off. Your plan ends on ' . fmt_date((string)$sub['expires_at']) . '. You can renew it yourself any time.', '/premium');
        return 'gave_up';
    }
    $made = checkout_subscription_for($user, (string)$sub['plan_slug'], autorenew_period($sub));
    if (!$made['ok']) {
        autorenew_turn_off($uid);
        autorenew_tell($uid, 'Automatic renewal is off', 'Your ' . $sub['plan_name'] . ' plan cannot be renewed automatically any more (' . rtrim($made['error'], '.') . '). Please choose a plan to continue.', '/premium');
        return 'gave_up';
    }
    $pay = $made['payment'];
    payment_meta_merge((int)$pay['id'], ['renewal' => 1]);
    $retry = date('Y-m-d H:i:s', time() + max(1, (int)setting('autorenew_retry_days')) * 86400);
    $count = fn() => q('UPDATE user_subscriptions SET renew_tries = renew_tries + 1, renew_next_at = :n WHERE id = :id', ['n' => $retry, 'id' => $sub['id']]);
    $payUrl = '/pay/' . (int)$pay['id'];

    if ($sub['renew_method'] === 'card') {
        $saved = !empty($sub['renew_saved_id']) ? pay_saved_get($uid, (int)$sub['renew_saved_id']) : null;
        if (!$saved) {
            $count();
            autorenew_tell($uid, 'Your plan needs a new card', 'The card we use to renew your ' . $sub['plan_name'] . ' plan is no longer saved. Pay here to keep your plan.', $payUrl);
            return 'retry';
        }
        $r = $saved['gateway'] === 'stripe' ? stripe_charge_saved($pay, $saved) : paystack_charge_saved($pay, $saved);
        if ($r['ok']) {
            return 'charged';
        }
        payment_fail((int)$pay['id'], 'failed', mb_substr($r['error'], 0, 200));
        $count();
        $pay2 = checkout_subscription_for($user, (string)$sub['plan_slug'], autorenew_period($sub));
        $link = $pay2['ok'] ? '/pay/' . (int)$pay2['payment']['id'] : '/premium';
        autorenew_tell($uid, 'We could not renew your plan', 'Your ' . pay_saved_label($saved) . ' was declined. We will try again in ' . plural(max(1, (int)setting('autorenew_retry_days')), 'day') . '. To keep your ' . $sub['plan_name'] . ' plan without waiting, you can pay now.', $link);
        return 'retry';
    }
    if ($sub['renew_method'] === 'mpesa' && payments_methods()['mpesa']['ready'] && payments_methods()['mpesa']['enabled']) {
        $r = payments_mpesa_initiate($pay, (string)$sub['renew_phone']);
        $count();
        if ($r['ok']) {
            autorenew_tell($uid, 'Approve your plan renewal', 'We sent an M-Pesa request for ' . money((float)$pay['amount']) . ' to ' . hosts_phone_local((string)$sub['renew_phone']) . ' to renew your ' . $sub['plan_name'] . ' plan. Enter your PIN to approve it.', $payUrl);
            return 'charged';
        }
        autorenew_tell($uid, 'Renew your plan', 'We could not send the M-Pesa request to renew your ' . $sub['plan_name'] . ' plan. You can pay here instead.', $payUrl);
        return 'retry';
    }
    // reminder with a pay link
    $count();
    autorenew_tell($uid, 'Time to renew your plan', 'Your ' . $sub['plan_name'] . ' plan ends on ' . fmt_date((string)$sub['expires_at']) . '. Pay ' . money((float)$pay['amount']) . ' here to keep it.', $payUrl);
    return 'reminded';
}

/** Cron: send reminders, then charge renewals that are due. Returns counts. */
function autorenew_run(): array
{
    $out = ['reminded' => 0, 'charged' => 0, 'retry' => 0, 'gave_up' => 0, 'waiting' => 0];
    if (!autorenew_enabled()) {
        return $out + ['skipped' => 1];
    }
    $days = max(0, (int)setting('autorenew_remind_days'));
    if ($days > 0) {
        $subs = rows("SELECT s.*, p.slug AS plan_slug, p.name AS plan_name FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
            WHERE s.status = 'active' AND s.auto_renew = 1 AND s.cancelled_at IS NULL AND s.renew_method IN ('card','mpesa')
              AND s.expires_at > DATE_ADD(NOW(), INTERVAL 1 DAY) AND s.expires_at <= DATE_ADD(NOW(), INTERVAL $days DAY)
              AND (s.renew_notified_for IS NULL OR s.renew_notified_for <> s.expires_at) ORDER BY s.id LIMIT 100");
        foreach ($subs as $s) {
            $sum = autorenew_summary($s);
            $price = $sum['price'];
            if ($price <= 0) {
                continue;
            }
            q('UPDATE user_subscriptions SET renew_notified_for = expires_at WHERE id = :id', ['id' => $s['id']]);
            autorenew_tell((int)$s['user_id'], 'Your plan renews soon', 'On ' . fmt_date((string)$s['expires_at']) . ' your ' . $s['plan_name'] . ' plan renews for ' . money($price) . ' using ' . $sum['how'] . '. You can switch this off on the Billing page.', '/billing');
            $out['reminded']++;
        }
    }
    $due = rows("SELECT s.*, p.slug AS plan_slug, p.name AS plan_name FROM user_subscriptions s JOIN subscription_plans p ON p.id = s.plan_id
        WHERE s.status = 'active' AND s.auto_renew = 1 AND s.cancelled_at IS NULL AND s.expires_at <= DATE_ADD(NOW(), INTERVAL 1 DAY)
          AND (s.renew_next_at IS NULL OR s.renew_next_at <= NOW()) ORDER BY s.expires_at LIMIT 50");
    foreach ($due as $s) {
        try {
            $r = autorenew_charge($s);
        } catch (Throwable $e) {
            app_log('error', 'autorenew failed for subscription ' . $s['id'] . ': ' . $e->getMessage());
            continue;
        }
        $key = $r === 'reminded' ? 'reminded' : ($r === 'skipped' ? 'waiting' : $r);
        $out[$key] = ($out[$key] ?? 0) + 1;
    }
    return $out;
}
