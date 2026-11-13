<?php
/**
 * Admin > Money > Payment methods: what each gateway needs, how its form is drawn and how it is saved and tested.
 * One definition list per gateway page, so the page, the save and the tests all read the same facts.
 */

declare(strict_types=1);

/** The pages: slug => [title, one-line help, logo key]. */
function pgs_gateways(): array
{
    return [
        'mpesa' => ['M-Pesa', 'Prompts on the customer\'s phone (STK push), payments made to your Paybill or till (C2B) and payouts to phones (B2C).', 'mpesa'],
        'card' => ['Credit and debit cards', 'Visa, Mastercard and American Express through Stripe or Paystack.', 'card'],
        'paystack' => ['Paystack', 'Cards, M-Pesa and bank payments on Paystack\'s secure page.', 'paystack'],
        'paypal' => ['PayPal', 'Customers pay with PayPal, their bank or a card.', 'paypal'],
        'bank' => ['Bank transfer', 'Customers transfer to your account and send you the reference. You approve each one.', 'bank'],
    ];
}

/** The switch that turns a method on, by method key (as used by payments_methods()). */
function pgs_enable_key(string $method): string
{
    return ['mpesa' => 'mpesa_enabled', 'card' => 'card_payments_enabled', 'paystack' => 'paystack_enabled', 'paypal' => 'paypal_enabled', 'manual' => 'manual_mpesa_enabled', 'bank' => 'bank_enabled', 'test' => 'payments_test_mode'][$method] ?? '';
}

/**
 * Fields of one gateway page (and tab). Each: key, type (bool|text|secret|select|number|textarea), label, help, plus options/min/max/step/placeholder.
 * @return array<int, array>
 */
function pgs_fields(string $gw, string $tab = ''): array
{
    $b = fn(string $k, string $label, string $help = '') => ['key' => $k, 'type' => 'bool', 'label' => $label, 'help' => $help];
    $t = fn(string $k, string $label, string $help = '', array $o = []) => ['key' => $k, 'type' => 'text', 'label' => $label, 'help' => $help] + $o;
    $s = fn(string $k, string $label, string $help = '') => ['key' => $k, 'type' => 'secret', 'label' => $label, 'help' => $help];
    $sel = fn(string $k, string $label, array $opts, string $help = '') => ['key' => $k, 'type' => 'select', 'label' => $label, 'help' => $help, 'options' => $opts];
    $n = fn(string $k, string $label, string $help, float $min, float $max, float $step = 1) => ['key' => $k, 'type' => 'number', 'label' => $label, 'help' => $help, 'min' => $min, 'max' => $max, 'step' => $step];
    $ta = fn(string $k, string $label, string $help = '', int $rows = 4) => ['key' => $k, 'type' => 'textarea', 'label' => $label, 'help' => $help, 'rows' => $rows];

    switch ($gw) {
        case 'mpesa':
            if ($tab === 'c2b') {
                return [
                    $b('manual_mpesa_enabled', 'Show "Pay to our Paybill or till" at checkout', 'Customers see your Paybill or till and their order reference as the account number.'),
                    $sel('manual_mpesa_type', 'How customers pay you', ['paybill' => 'Pay Bill (business number and account)', 'till' => 'Buy Goods and Services (till number)', 'phone' => 'Send Money (to a phone number)', 'pochi' => 'Pochi la Biashara (phone number)']),
                    $t('manual_mpesa_number', 'Paybill, till or phone number', 'Shown in big digits with a Copy button.', ['placeholder' => '123456', 'max' => 15]),
                    $t('manual_mpesa_account', 'Account number (Pay Bill only)', 'Leave empty to use each order\'s own reference. With automatic confirmation (C2B) the reference is always used.', ['placeholder' => 'Order reference', 'max' => 20]),
                    $t('manual_mpesa_name', 'Name M-Pesa shows', 'Customers check it before they enter their PIN.', ['placeholder' => 'ConnectHub Ltd', 'max' => 60]),
                    $b('manual_mpesa_ussd', 'Show an "Open M-PESA menu" button on Android phones', 'Opens the dialler with *334#. iPhones see a short hint instead.'),
                    $t('manual_mpesa_details', 'Older free-text "Where customers pay" (optional)', 'Only used when the number above is empty, for example: Paybill 123456.', ['placeholder' => 'Paybill 123456', 'max' => 120]),
                    $b('mpesa_c2b_enabled', 'Confirm those payments automatically (C2B)', 'Safaricom tells this site about each payment and the order is turned on at once.'),
                    $t('mpesa_c2b_shortcode', 'Short code for C2B', 'Leave empty to use the short code from the STK tab.', ['placeholder' => 'Same as STK', 'max' => 12]),
                    $sel('mpesa_c2b_api', 'C2B version', ['v2' => 'Version 2 (customer phone numbers are hidden)', 'v1' => 'Version 1 (phone numbers shown)'], 'Version 2 is recommended. Pick the same one Safaricom enabled for your short code.'),
                    $b('mpesa_c2b_validation', 'Check the account number before accepting', 'Safaricom asks us first and refuses payments with a wrong account number. Safaricom must also switch validation on for your short code.'),
                    $sel('mpesa_c2b_response', 'If this site cannot answer in time', ['Completed' => 'Accept the payment', 'Cancelled' => 'Refuse the payment'], 'Only matters when validation is on.'),
                ];
            }
            if ($tab === 'b2c') {
                return [
                    $b('mpesa_b2c_enabled', 'Pay Hosts and members out through M-Pesa (B2C)', 'Adds a "Send via M-Pesa" button to payout requests.'),
                    $t('mpesa_b2c_shortcode', 'Short code that pays out', 'Leave empty to use the short code from the STK tab.', ['placeholder' => 'Same as STK', 'max' => 12]),
                    $sel('mpesa_b2c_command', 'Payment type', ['BusinessPayment' => 'Business payment (most common)', 'SalaryPayment' => 'Salary payment', 'PromotionPayment' => 'Promotion payment']),
                    $t('mpesa_initiator_name', 'Initiator name', 'The API operator username created in the M-Pesa portal.', ['max' => 60]),
                    $s('mpesa_initiator_password', 'Initiator password', 'Used with the certificate below to make the security credential.'),
                    $ta('mpesa_cert', 'Safaricom certificate', 'Paste the whole public certificate from the Daraja portal (sandbox and production have different ones), including the BEGIN and END lines.', 6),
                    $s('mpesa_security_credential', 'Ready-made security credential (optional)', 'Skip the password and certificate by pasting a credential you already made. It wins if both are filled in.'),
                    $n('mpesa_b2c_max', 'Largest single payout (KES)', 'Anything above this must be paid another way.', 10, 250000),
                    $b('mpesa_b2c_auto', 'Send small payouts automatically', 'Requests up to the limit below are paid within a few minutes with no click. Larger ones wait for you.'),
                    $n('mpesa_b2c_auto_limit', 'Automatic up to (KES)', '', 10, 250000),
                ];
            }
            return [
                $b('mpesa_enabled', 'Send M-Pesa prompts at checkout (STK push)', 'The customer gets a pop-up on their phone and enters their PIN.'),
                $sel('mpesa_env', 'Environment', ['sandbox' => 'Sandbox (testing, no real money)', 'production' => 'Production (live)'], 'Use sandbox until a test payment works, then switch to production with your live details.'),
                $t('mpesa_shortcode', 'Paybill or till number', 'Sandbox default is 174379.', ['max' => 12]),
                $sel('mpesa_type', 'Transaction type', ['CustomerPayBillOnline' => 'Paybill', 'CustomerBuyGoodsOnline' => 'Buy Goods (till)']),
                $s('mpesa_consumer_key', 'Consumer key', 'From your app on the Daraja portal.'),
                $s('mpesa_consumer_secret', 'Consumer secret'),
                $s('mpesa_passkey', 'Passkey', 'Lipa na M-Pesa Online passkey for this short code.'),
                $n('mpesa_stk_wait_seconds', 'How long checkout waits for the prompt (seconds)', 'After this the customer is offered a retry. A payment made late is still confirmed.', 45, 240, 5),
                $ta('mpesa_callback_ips', 'Only accept results from these addresses (optional)', 'One IP address or range per line. Leave empty to rely on the secret in the address. A wrong entry leaves payments unconfirmed.', 3),
            ];
        case 'card':
            return [
                $b('card_payments_enabled', 'Accept credit and debit cards'),
                $sel('card_gateway', 'Process cards with', ['stripe' => 'Stripe', 'paystack' => 'Paystack (cards only)'], 'Paystack uses the key on the Paystack page.'),
                $s('stripe_secret_key', 'Stripe secret key', 'Starts with sk_test_ (testing) or sk_live_ (live). Only needed for Stripe.'),
                $s('stripe_webhook_secret', 'Stripe webhook signing secret', 'Starts with whsec_. From the webhook you add in Stripe (see the address below).'),
            ];
        case 'paystack':
            return [
                $b('paystack_enabled', 'Accept Paystack'),
                $s('paystack_secret_key', 'Secret key', 'Starts with sk_test_ (testing) or sk_live_ (live). Paystack > Settings > API keys.'),
                $sel('paystack_channels', 'What customers can use', ['all' => 'Everything Paystack offers', 'card' => 'Cards only', 'mobile_money' => 'Mobile money (M-Pesa) only', 'bank' => 'Bank only']),
            ];
        case 'paypal':
            return [
                $b('paypal_enabled', 'Accept PayPal'),
                $sel('paypal_env', 'Environment', ['sandbox' => 'Sandbox (testing)', 'live' => 'Live']),
                $t('paypal_client_id', 'Client ID', 'From your app in the PayPal developer dashboard.', ['max' => 200]),
                $s('paypal_client_secret', 'Client secret'),
                $t('paypal_webhook_id', 'Webhook ID', 'Create the webhook shown below and paste its ID. It makes sure a payment is never missed.', ['max' => 60]),
                $t('paypal_currency', 'Currency PayPal charges in', 'PayPal does not accept every currency (for example Kenyan shillings), so prices are converted.', ['max' => 5, 'placeholder' => 'USD']),
                $n('paypal_rate', 'How many ' . pg_site_currency() . ' make 1 of that currency', 'For example 129 means KES 129 = USD 1. Update it when the rate moves. Ignored when both currencies are the same.', 0.0001, 1000000, 0.01),
            ];
        case 'bank':
            return [
                $b('bank_enabled', 'Accept bank transfer'),
                $t('bank_name', 'Bank'),
                $t('bank_account_name', 'Account name'),
                $t('bank_account_number', 'Account number'),
                $t('bank_branch', 'Branch'),
                $ta('manual_instructions', 'Instructions shown to customers', 'For example: use your payment reference as the description.', 3),
            ];
    }
    return [];
}

/** Settings fields for automatic renewal (shown on the overview page). */
function pgs_autorenew_fields(): array
{
    return [
        ['key' => 'autorenew_enabled', 'type' => 'bool', 'label' => 'Let members renew their plan automatically', 'help' => 'Adds a "Renew automatically" tick at checkout and controls on the Billing page. Cards are charged by Paystack or Stripe; M-Pesa members get a prompt to approve; others get a reminder.'],
        ['key' => 'autorenew_remind_days', 'type' => 'number', 'label' => 'Remind members this many days before the charge', 'help' => '0 sends no reminder.', 'min' => 0, 'max' => 14, 'step' => 1],
        ['key' => 'autorenew_retry_days', 'type' => 'number', 'label' => 'Days between retries after a failed charge', 'help' => '', 'min' => 1, 'max' => 7, 'step' => 1],
        ['key' => 'autorenew_max_tries', 'type' => 'number', 'label' => 'Tries before automatic renewal switches off', 'help' => 'The member is told each time.', 'min' => 1, 'max' => 6, 'step' => 1],
    ];
}

/** Draw one field. Secrets are never printed back. */
function pgs_render_field(array $f, bool $canEdit): string
{
    $key = (string)$f['key'];
    $id = 'f-' . $key;
    $val = setting($key);
    $dis = $canEdit ? '' : ' disabled';
    $err = (errors_get()[$key] ?? "");
    $help = ($f['help'] ?? '') !== '' ? '<p class="help" id="' . e($id) . '-h">' . e((string)$f['help']) . '</p>' : '';
    $desc = $help !== '' ? ' aria-describedby="' . e($id) . '-h"' : '';
    $errHtml = $err ? '<p class="field-error" role="alert">' . e((string)$err) . '</p>' : '';
    $old = fn($default) => old($key, $default);
    switch ($f['type']) {
        case 'bool':
            return '<div class="field pgs-switch"><label class="cm-switch-line"><span><span class="t">' . e((string)$f['label']) . '</span>' . ($help !== '' ? '<span class="h block">' . e((string)$f['help']) . '</span>' : '') . '</span>'
                . '<span class="switch"><input type="hidden" name="' . e($key) . '" value="0"><input type="checkbox" name="' . e($key) . '" value="1" id="' . e($id) . '"' . ((int)$old((int)$val) ? ' checked' : '') . $dis . '><span></span></span></label></div>';
        case 'select':
            $o = '';
            foreach ($f['options'] as $v => $l) {
                $o .= '<option value="' . e((string)$v) . '"' . ((string)$old((string)$val) === (string)$v ? ' selected' : '') . '>' . e((string)$l) . '</option>';
            }
            return '<div class="field"><label class="label" for="' . e($id) . '">' . e((string)$f['label']) . '</label><select class="select" id="' . e($id) . '" name="' . e($key) . '"' . $desc . $dis . '>' . $o . '</select>' . $help . $errHtml . '</div>';
        case 'number':
            return '<div class="field"><label class="label" for="' . e($id) . '">' . e((string)$f['label']) . '</label><input class="input" type="number" id="' . e($id) . '" name="' . e($key) . '" value="' . e((string)$old((string)$val)) . '" min="' . e((string)$f['min']) . '" max="' . e((string)$f['max']) . '" step="' . e((string)($f['step'] ?? 1)) . '"' . $desc . $dis . '>' . $help . $errHtml . '</div>';
        case 'secret':
            $set = (string)$val !== '';
            return '<div class="field"><label class="label" for="' . e($id) . '">' . e((string)$f['label']) . ($set ? ' <span class="badge badge-success pgs-saved">Saved</span>' : '') . '</label><input class="input" type="password" id="' . e($id) . '" name="' . e($key) . '" value="" autocomplete="new-password" spellcheck="false" placeholder="' . ($set ? 'Saved. Type to replace it.' : 'Not set') . '"' . $desc . $dis . '>' . $help . $errHtml . '</div>';
        case 'textarea':
            return '<div class="field"><label class="label" for="' . e($id) . '">' . e((string)$f['label']) . '</label><textarea class="textarea cm-mono" id="' . e($id) . '" name="' . e($key) . '" rows="' . (int)($f['rows'] ?? 4) . '" spellcheck="false"' . $desc . $dis . '>' . e((string)$old((string)$val)) . '</textarea>' . $help . $errHtml . '</div>';
    }
    return '<div class="field"><label class="label" for="' . e($id) . '">' . e((string)$f['label']) . '</label><input class="input" type="text" id="' . e($id) . '" name="' . e($key) . '" value="' . e((string)$old((string)$val)) . '"' . (isset($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '') . (isset($f['placeholder']) ? ' placeholder="' . e((string)$f['placeholder']) . '"' : '') . $desc . $dis . '>' . $help . $errHtml . '</div>';
}

/**
 * Validate and save the fields of one gateway page.
 * @return array ['errors'=>[key=>message], 'saved'=>int]
 */
function pgs_save(array $fields, array $in, int $adminId): array
{
    $errors = [];
    $clean = [];
    foreach ($fields as $f) {
        $k = (string)$f['key'];
        if (!array_key_exists($k, $in)) {
            continue;
        }
        $raw = $in[$k];
        if (!is_scalar($raw)) {
            continue;
        }
        $v = trim((string)$raw);
        switch ($f['type']) {
            case 'bool':
                $v = $v === '1' ? '1' : '0';
                break;
            case 'secret':
                if ($v === '') {
                    continue 2; // blank keeps what is saved
                }
                if (mb_strlen($v) > 600) {
                    $errors[$k] = 'That is too long.';
                }
                break;
            case 'select':
                if (!array_key_exists($v, $f['options'])) {
                    $errors[$k] = 'Choose one of the options.';
                }
                break;
            case 'number':
                if (!is_numeric($v) || (float)$v < $f['min'] || (float)$v > $f['max']) {
                    $errors[$k] = 'Enter a number from ' . $f['min'] . ' to ' . $f['max'] . '.';
                }
                break;
            case 'textarea':
                $v = trim(str_replace("\r\n", "\n", (string)$raw));
                if (mb_strlen($v) > 6000) {
                    $errors[$k] = 'That is too long.';
                }
                if ($k === 'mpesa_cert' && $v !== '' && !@openssl_pkey_get_public($v)) {
                    $errors[$k] = 'This is not a readable certificate. Paste the whole thing, including the BEGIN and END lines.';
                }
                break;
            default:
                if (isset($f['max']) && mb_strlen($v) > (int)$f['max']) {
                    $errors[$k] = 'Use at most ' . (int)$f['max'] . ' characters.';
                }
                if (in_array($k, ['mpesa_shortcode', 'mpesa_c2b_shortcode', 'mpesa_b2c_shortcode'], true) && $v !== '' && !preg_match('/^\d{3,10}$/', $v)) {
                    $errors[$k] = 'A short code is 3 to 10 digits.';
                }
                if ($k === 'paypal_currency') {
                    $v = strtoupper($v);
                    if (!preg_match('/^[A-Z]{3}$/', $v)) {
                        $errors[$k] = 'Use a three-letter code such as USD.';
                    }
                }
                if ($k === 'mpesa_initiator_name' && $v !== '' && !preg_match('/^[A-Za-z0-9._ -]{2,60}$/', $v)) {
                    $errors[$k] = 'Use letters, numbers and . _ - only.';
                }
        }
        $clean[$k] = $v;
    }
    if ($errors) {
        return ['errors' => $errors, 'saved' => 0];
    }
    $n = 0;
    foreach ($clean as $k => $v) {
        if ((string)setting($k) !== $v) {
            setting_set($k, $v, $adminId ?: null);
            $n++;
        }
    }
    return ['errors' => [], 'saved' => $n];
}

/** One-line status of a method for the overview: [label, badge class, detail]. */
function pgs_status(string $method): array
{
    $m = payments_methods()[$method] ?? null;
    if (!$m) {
        return ['Unknown', '', ''];
    }
    if ($method === 'test') {
        return $m['enabled'] ? ['Test mode on', 'badge-warning', 'Nobody is charged. Turn it off before going live.'] : ['Off', '', ''];
    }
    if ($m['enabled'] && $m['ready']) {
        return ['On', 'badge-success', ''];
    }
    if ($m['enabled']) {
        return ['Needs setup', 'badge-warning', 'Switched on, but not shown to customers until the details are filled in.'];
    }
    return $m['ready'] ? ['Off', '', 'Ready to switch on.'] : ['Off', '', ''];
}

/** Check the saved keys of a hosted gateway. @return array ['ok'=>bool, 'message'=>string] */
function pgs_test_gateway(string $gw): array
{
    switch ($gw) {
        case 'paystack':
            if (!paystack_ready()) {
                return ['ok' => false, 'message' => 'Enter the secret key first.'];
            }
            $r = paystack_call('GET', '/balance');
            return $r['ok'] ? ['ok' => true, 'message' => 'Connected to Paystack. Your secret key works.'] : ['ok' => false, 'message' => $r['status'] === 0 ? 'We could not reach Paystack.' : 'Paystack refused the key. Check that you copied the secret key (not the public one) and that it is for the right mode.'];
        case 'paypal':
            if (!paypal_ready()) {
                return ['ok' => false, 'message' => 'Enter the client ID, client secret and the exchange rate first.'];
            }
            cache_clear('paypal_token_');
            $t = paypal_token();
            return $t['ok'] ? ['ok' => true, 'message' => 'Connected to PayPal (' . (paypal_config()['live'] ? 'live' : 'sandbox') . '). Your client ID and secret work.' . (paypal_config()['webhook_id'] === '' ? ' Add the webhook ID too so no payment is missed.' : '')] : ['ok' => false, 'message' => $t['error'] . ' Check the client ID and secret and that the environment matches.'];
        case 'card':
            if ((string)setting('card_gateway') === 'paystack') {
                return paystack_ready() ? pgs_test_gateway('paystack') : ['ok' => false, 'message' => 'Cards go through Paystack. Add the Paystack secret key on the Paystack page.'];
            }
            if (!stripe_ready()) {
                return ['ok' => false, 'message' => 'Enter the Stripe secret key first.'];
            }
            $r = stripe_call('GET', '/v1/balance');
            return $r['ok'] ? ['ok' => true, 'message' => 'Connected to Stripe. Your secret key works.' . (trim((string)setting('stripe_webhook_secret')) === '' ? ' Add the webhook signing secret too so no payment is missed.' : '')] : ['ok' => false, 'message' => $r['status'] === 0 ? 'We could not reach Stripe.' : 'Stripe refused the key. Check that you copied the secret key (sk_...).'];
    }
    return ['ok' => false, 'message' => 'Nothing to test here.'];
}

/** Webhook addresses to paste into each gateway's dashboard. */
function pgs_webhook_url(string $gw): string
{
    return abs_url('/api/webhooks/' . $gw);
}
