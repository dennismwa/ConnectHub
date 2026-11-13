<?php
/** Extra payment settings (module B). Merged into the admin catalogue by settings_schema(). */
return [
    'payments' => [
        'M-Pesa callback and timing' => [
            'mpesa_callback_ips' => ['type' => 'textarea', 'default' => '', 'max' => 800,
                'label' => 'Only accept M-Pesa results from these addresses',
                'help' => 'Optional, one IP address or range (for example 196.201.214.0/24) per line. Leave empty to rely on the secret in the callback address. A wrong entry makes payments stay unconfirmed.'],
            'mpesa_stk_wait_seconds' => ['type' => 'number', 'default' => 90, 'min' => 45, 'max' => 240, 'step' => 5,
                'label' => 'How long checkout waits for the phone prompt (seconds)',
                'help' => 'After this time the member is offered a retry. If they did pay, the payment is still confirmed when M-Pesa reports it.'],
        ],
        'Launch access' => [
            'launch_access_enabled' => ['type' => 'bool', 'default' => 0, 'ui' => false, 'label' => 'Enable complimentary launch access', 'help' => 'Managed under Admin > Launch access.'],
        ],
        'Card payments' => [
            'card_payments_enabled' => ['type' => 'bool', 'default' => 0, 'ui' => false,
                'label' => 'Accept credit and debit cards',
                'help' => 'Set up under Admin > Money > Payment methods > Card.'],
        ],
    ],
];
