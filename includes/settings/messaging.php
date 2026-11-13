<?php
/** Extra messaging settings (module B). Merged into the admin catalogue by settings_schema(). */
return [
    'messaging' => [
        'Safety limits' => [
            'msg_rate_per_minute' => ['type' => 'number', 'default' => 30, 'min' => 5, 'max' => 120, 'step' => 1,
                'label' => 'Most messages a member can send per minute', 'help' => 'Stops flooding. Members who hit the limit are asked to wait a moment.'],
        ],
        'Paid direct messages' => [
            'dm_paid_enabled' => ['type' => 'bool', 'default' => 1,
                'label' => 'Members can pay to message someone they have not matched with',
                'help' => 'Members buy message credits. One credit opens one conversation. Manage packs and prices under Paid messages.'],
            'dm_messages_per_unlock' => ['type' => 'number', 'default' => 3, 'min' => 1, 'max' => 50, 'step' => 1,
                'label' => 'Messages one credit allows before they reply', 'help' => 'Once the other person replies, the chat is open both ways.'],
            'dm_daily_unlocks' => ['type' => 'number', 'default' => 10, 'min' => 0, 'max' => 200, 'step' => 1,
                'label' => 'New conversations a member can open per day', 'help' => '0 means no limit.'],
            'dm_welcome_credits' => ['type' => 'number', 'default' => 0, 'min' => 0, 'max' => 50, 'step' => 1,
                'label' => 'Free credits for new members', 'help' => 'Given once when someone signs up. 0 gives none.'],
            'dm_to_hosts' => ['type' => 'bool', 'default' => 0,
                'label' => 'Credits also work for messaging Hosts', 'help' => 'Off keeps the Host messaging rules above in charge.'],
        ],
    ],
];
