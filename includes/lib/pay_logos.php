<?php
/**
 * Real payment logos (assets/img/pay/*.svg). Used wherever a payment method is shown, so members see the M-Pesa logo for M-Pesa,
 * the PayPal logo for PayPal and so on, never a generic icon.
 */

declare(strict_types=1);

/** Logo files per payment method key. */
function pay_logo_map(): array
{
    return [
        'mpesa' => ['mpesa'], 'manual' => ['mpesa'], 'bank' => ['bank'], 'bank_transfer' => ['bank'],
        'card' => ['visa', 'mastercard', 'amex'], 'paystack' => ['paystack'], 'paypal' => ['paypal'], 'stripe' => ['stripe'],
        'airtel_money' => ['airtel_money'], 'visa' => ['visa'], 'mastercard' => ['mastercard'], 'amex' => ['amex'],
    ];
}

/** Display name of a logo, used as its alt text. */
function pay_logo_name(string $logo): string
{
    return ['mpesa' => 'M-Pesa', 'airtel_money' => 'Airtel Money', 'paypal' => 'PayPal', 'visa' => 'Visa', 'mastercard' => 'Mastercard', 'amex' => 'American Express',
        'stripe' => 'Stripe', 'bank' => 'Bank transfer', 'paystack' => 'Paystack'][$logo] ?? ucfirst($logo);
}

/** One logo as an <img>. Empty string for an unknown logo. */
function pay_logo(string $logo, string $class = 'pay-logo'): string
{
    $logo = preg_replace('/[^a-z_]/', '', strtolower($logo)) ?? '';
    if ($logo === '' || !is_file(ROOT . '/assets/img/pay/' . $logo . '.svg')) {
        return '';
    }
    return '<img class="' . e($class) . ' pay-logo-' . e($logo) . '" src="' . e(asset('img/pay/' . $logo . '.svg')) . '" alt="' . e(pay_logo_name($logo)) . '" width="38" height="24" loading="lazy" decoding="async">';
}

/** All logos of a payment method (card shows Visa, Mastercard and Amex). */
function pay_logos(string $method, string $class = 'pay-logo'): string
{
    $out = '';
    foreach (pay_logo_map()[$method] ?? [] as $l) {
        $out .= pay_logo($l, $class);
    }
    return $out;
}

/** "We accept" row: the logos of every method that is on and usable, one per brand. */
function pay_accepted_strip(string $class = 'pay-accepted'): string
{
    if (!function_exists('payments_methods')) {
        return '';
    }
    $seen = [];
    $html = '';
    foreach (payments_methods() as $key => $m) {
        if ($key === 'test' || !$m['enabled'] || !$m['ready']) {
            continue;
        }
        foreach (pay_logo_map()[$key] ?? [] as $l) {
            if (!isset($seen[$l])) {
                $seen[$l] = true;
                $html .= '<li>' . pay_logo($l, 'pay-logo') . '</li>';
            }
        }
    }
    return $html === '' ? '' : '<ul class="' . e($class) . '" aria-label="Ways to pay">' . $html . '</ul>';
}
