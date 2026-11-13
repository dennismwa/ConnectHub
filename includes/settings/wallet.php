<?php
/** Gift wallet settings. Merged into the Payments tab by settings_schema(). */
return [
    'payments' => [
        'Gift wallet and payouts' => [
            'wallet_enabled' => ['type' => 'bool', 'default' => 1,
                'label' => 'Members can claim gifts into a wallet',
                'help' => 'Needs gifts to be switched on. Members see Wallet in their account menu.'],
            'gift_receiver_percent' => ['type' => 'number', 'default' => 70, 'min' => 0, 'max' => 100, 'step' => 1,
                'label' => 'Share of a gift that goes to the person who receives it (%)',
                'help' => 'You keep the rest. At 70, a KSh 1,000 gift adds KSh 700 to the receiver\'s wallet.'],
            'gift_claim_mode' => ['type' => 'select', 'default' => 'auto', 'options' => ['auto' => 'Claim at once, no checking', 'verified' => 'Claim only after I check the gift'],
                'label' => 'When can a gift be claimed',
                'help' => 'Checking means opening Wallet and gifts and pressing Check on the gift once you see the payment in your M-Pesa or bank. Members are told when it is ready.'],
            'wallet_payouts_enabled' => ['type' => 'bool', 'default' => 1,
                'label' => 'Members can ask for a payout',
                'help' => 'Turn off to pause payouts, for example while you reconcile. Balances are kept.'],
            'wallet_min_payout' => ['type' => 'number', 'default' => 500, 'min' => 1, 'max' => 1000000, 'step' => 50,
                'label' => 'Smallest payout', 'help' => 'In your site currency.'],
            'wallet_max_open_payouts' => ['type' => 'number', 'default' => 2, 'min' => 1, 'max' => 10, 'step' => 1,
                'label' => 'Payout requests a member can have waiting at once'],
            'wallet_max_numbers' => ['type' => 'number', 'default' => 4, 'min' => 1, 'max' => 10, 'step' => 1,
                'label' => 'Phone numbers a member can save'],
        ],
    ],
];
