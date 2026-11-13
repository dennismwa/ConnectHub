<?php
/**
 * Manual M-Pesa ("Pay to our Paybill or till"): everything the checkout needs to show it like the M-PESA menu itself.
 * Structured settings (manual_mpesa_type / _number / _account / _name) come first; sites that only filled in the older
 * free-text "Where to pay" are read from that text ("Paybill 123456, account CONNECTHUB", "Till 543210", "0712 345 678").
 */

/** Pay Bill, Buy Goods, Send Money or Pochi, with the number, account, name and the menu steps for one payment ($p may be null). */
function mpesa_manual_info(?array $p): array
{
    $type = (string)setting('manual_mpesa_type');
    $type = in_array($type, ['paybill', 'till', 'phone', 'pochi'], true) ? $type : 'paybill';
    $number = preg_replace('/\s+/', '', (string)setting('manual_mpesa_number')) ?? '';
    $account = trim((string)setting('manual_mpesa_account'));
    $legacy = trim((string)setting('manual_mpesa_details'));
    if ($number === '' && $legacy !== '') {
        if (preg_match('/(?:pay\s*bill|paybill|business(?:\s*no\.?|\s*number)?)\D{0,12}(\d{5,7})/i', $legacy, $m)) {
            $type = 'paybill';
            $number = $m[1];
        } elseif (preg_match('/(?:till|buy\s*goods)\D{0,12}(\d{5,8})/i', $legacy, $m)) {
            $type = 'till';
            $number = $m[1];
        } elseif (preg_match('/(?:\+?254|0)\s*([17]\d{2})\s*(\d{3})\s*(\d{3})/', $legacy, $m)) {
            $type = stripos($legacy, 'pochi') !== false ? 'pochi' : 'phone';
            $number = '0' . $m[1] . $m[2] . $m[3];
        }
        if ($account === '' && preg_match('/acc(?:oun)?t\.?\s*(?:no\.?|number)?\s*[:\-]?\s*([A-Za-z0-9]{2,20})/i', $legacy, $m)) {
            $account = strtoupper($m[1]);
        }
    }
    $c2b = function_exists('mpesa_c2b_ready') && mpesa_c2b_ready();
    $ref = $p ? (string)$p['reference'] : '';
    // With automatic confirmation the order reference must be the account number; otherwise it is the default.
    $accountIsRef = $type === 'paybill' && ($c2b || $account === '');
    if ($accountIsRef) {
        $account = $ref;
    }
    $amount = $p ? (string)(int)ceil((float)$p['amount']) : '';
    $name = trim((string)setting('manual_mpesa_name')) ?: site_name();
    $kind = ['paybill' => 'Pay Bill', 'till' => 'Buy Goods and Services', 'phone' => 'Send Money', 'pochi' => 'Pochi la Biashara'][$type];
    $numLabel = ['paybill' => 'Business number', 'till' => 'Till number', 'phone' => 'Phone number', 'pochi' => 'Phone number'][$type];
    $display = in_array($type, ['phone', 'pochi'], true) && strlen($number) === 10 ? substr($number, 0, 4) . ' ' . substr($number, 4, 3) . ' ' . substr($number, 7) : $number;

    // The menu, step by step: [text, value to show, copy value, copy label]
    $steps = [['Open M-PESA on your phone', '', '', '']];
    if ($type === 'phone') {
        $steps[] = ['Choose Send Money', '', '', ''];
        $steps[] = ['Enter the phone number', $display, $number, 'Phone number copied'];
    } else {
        $steps[] = ['Choose Lipa na M-PESA, then ' . $kind, '', '', ''];
        if ($type === 'pochi') {
            $steps[] = ['Enter the phone number', $display, $number, 'Phone number copied'];
        } else {
            $steps[] = ['Enter the ' . strtolower($numLabel), $display, $number, $numLabel . ' copied'];
        }
        if ($type === 'paybill' && $account !== '') {
            $steps[] = ['Enter the account number', $account, $account, 'Account number copied'];
        }
    }
    if ($amount !== '') {
        $steps[] = ['Enter the amount', money((float)$amount), $amount, 'Amount copied'];
    }
    $steps[] = ['Check the name says ' . $name . ', enter your PIN and send', '', '', ''];

    return [
        'type' => $type, 'kind_label' => $kind, 'number_label' => $numLabel, 'number' => $number, 'number_display' => $display,
        'account' => $account, 'account_is_ref' => $accountIsRef, 'name' => $name, 'amount' => $amount, 'c2b' => $c2b,
        'steps' => $steps, 'legacy' => $number === '' ? $legacy : '', 'ussd' => (bool)setting('manual_mpesa_ussd'),
    ];
}
