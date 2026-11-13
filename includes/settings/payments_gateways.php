<?php
/**
 * Paystack, PayPal, card, automatic renewal and the M-Pesa C2B / B2C extras. Edited on Admin > Money > Payment methods
 * (so every gateway has its own page with logo, status and test buttons), not on the general Settings screen.
 */
$off = ['ui' => false];
return [
    'payments' => [
        'Payment methods' => [
            'payments_method_order' => ['type' => 'text', 'default' => 'mpesa,card,paystack,paypal,manual,bank,test', 'max' => 120, 'label' => 'Order of payment methods'] + $off,
            'paystack_enabled' => ['type' => 'bool', 'default' => 0, 'label' => 'Accept Paystack'] + $off,
            'paystack_secret_key' => ['type' => 'secret', 'default' => '', 'label' => 'Paystack secret key'] + $off,
            'paystack_channels' => ['type' => 'select', 'default' => 'all', 'options' => ['all' => 'Everything Paystack offers', 'card' => 'Cards only', 'mobile_money' => 'Mobile money (M-Pesa) only', 'bank' => 'Bank only'], 'label' => 'Paystack channels'] + $off,
            'paypal_enabled' => ['type' => 'bool', 'default' => 0, 'label' => 'Accept PayPal'] + $off,
            'paypal_env' => ['type' => 'select', 'default' => 'sandbox', 'options' => ['sandbox' => 'Sandbox (testing)', 'live' => 'Live'], 'label' => 'PayPal environment'] + $off,
            'paypal_client_id' => ['type' => 'text', 'default' => '', 'max' => 200, 'label' => 'PayPal client ID'] + $off,
            'paypal_client_secret' => ['type' => 'secret', 'default' => '', 'label' => 'PayPal client secret'] + $off,
            'paypal_webhook_id' => ['type' => 'text', 'default' => '', 'max' => 60, 'label' => 'PayPal webhook ID'] + $off,
            'paypal_currency' => ['type' => 'text', 'default' => 'USD', 'max' => 5, 'label' => 'Currency PayPal charges in'] + $off,
            'paypal_rate' => ['type' => 'number', 'default' => 129, 'min' => 0.0001, 'max' => 1000000, 'step' => 0.01, 'label' => 'Site currency per 1 PayPal currency'] + $off,
            'card_gateway' => ['type' => 'select', 'default' => 'stripe', 'options' => ['stripe' => 'Stripe', 'paystack' => 'Paystack (cards only)'], 'label' => 'Process cards with'] + $off,
            'stripe_secret_key' => ['type' => 'secret', 'default' => '', 'label' => 'Stripe secret key'] + $off,
            'stripe_webhook_secret' => ['type' => 'secret', 'default' => '', 'label' => 'Stripe webhook signing secret'] + $off,
            'gateway_webhook_secret' => ['type' => 'secret', 'default' => '', 'label' => 'Webhook address secret'] + $off,
        ],
        'Automatic renewal' => [
            'autorenew_enabled' => ['type' => 'bool', 'default' => 0, 'label' => 'Let members renew their plan automatically'] + $off,
            'autorenew_remind_days' => ['type' => 'number', 'default' => 3, 'min' => 0, 'max' => 14, 'label' => 'Remind members this many days before they are charged'] + $off,
            'autorenew_retry_days' => ['type' => 'number', 'default' => 2, 'min' => 1, 'max' => 7, 'label' => 'Days between retries after a failed charge'] + $off,
            'autorenew_max_tries' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 6, 'label' => 'Tries before automatic renewal is switched off'] + $off,
        ],
        'M-Pesa C2B and B2C' => [
            'mpesa_c2b_enabled' => ['type' => 'bool', 'default' => 0, 'label' => 'Receive payments made to my Paybill or till (C2B)'] + $off,
            'mpesa_c2b_shortcode' => ['type' => 'text', 'default' => '', 'max' => 12, 'label' => 'C2B short code'] + $off,
            'mpesa_c2b_response' => ['type' => 'select', 'default' => 'Completed', 'options' => ['Completed' => 'Accept when my server cannot be reached', 'Cancelled' => 'Refuse when my server cannot be reached'], 'label' => 'Default when we do not answer'] + $off,
            'mpesa_c2b_validation' => ['type' => 'bool', 'default' => 0, 'label' => 'Check the account number before accepting'] + $off,
            'mpesa_c2b_api' => ['type' => 'select', 'default' => 'v2', 'options' => ['v2' => 'Version 2 (phone numbers are hidden)', 'v1' => 'Version 1 (phone numbers shown)'], 'label' => 'C2B API version'] + $off,
            'mpesa_c2b_secret' => ['type' => 'secret', 'default' => '', 'label' => 'C2B address secret'] + $off,
            'mpesa_c2b_registered_at' => ['type' => 'text', 'default' => '', 'max' => 30, 'label' => 'C2B addresses registered at'] + $off,
            'mpesa_b2c_enabled' => ['type' => 'bool', 'default' => 0, 'label' => 'Pay members and Hosts out through M-Pesa (B2C)'] + $off,
            'mpesa_b2c_shortcode' => ['type' => 'text', 'default' => '', 'max' => 12, 'label' => 'B2C short code'] + $off,
            'mpesa_b2c_command' => ['type' => 'select', 'default' => 'BusinessPayment', 'options' => ['BusinessPayment' => 'Business payment', 'SalaryPayment' => 'Salary payment', 'PromotionPayment' => 'Promotion payment'], 'label' => 'B2C payment type'] + $off,
            'mpesa_initiator_name' => ['type' => 'text', 'default' => '', 'max' => 60, 'label' => 'Initiator name'] + $off,
            'mpesa_initiator_password' => ['type' => 'secret', 'default' => '', 'label' => 'Initiator password'] + $off,
            'mpesa_security_credential' => ['type' => 'secret', 'default' => '', 'label' => 'Security credential'] + $off,
            'mpesa_cert' => ['type' => 'textarea', 'default' => '', 'max' => 6000, 'label' => 'Safaricom certificate'] + $off,
            'mpesa_b2c_max' => ['type' => 'number', 'default' => 150000, 'min' => 10, 'max' => 250000, 'label' => 'Largest single M-Pesa payout'] + $off,
            'mpesa_b2c_auto' => ['type' => 'bool', 'default' => 0, 'label' => 'Send small payouts automatically'] + $off,
            'mpesa_b2c_auto_limit' => ['type' => 'number', 'default' => 5000, 'min' => 10, 'max' => 250000, 'label' => 'Send automatically up to this amount'] + $off,
            'mpesa_b2c_secret' => ['type' => 'secret', 'default' => '', 'label' => 'B2C address secret'] + $off,
        ],
    ],
];
