<?php
/** International connections settings. Merged into the Discovery tab by settings_schema(); the friendly editor is Admin > International. */
$types = [];
foreach (intl_contact_types_all() as $k => $v) {
    $types[$k] = $v[0];
}
return [
    'discovery' => [
        'International connections' => [
            'intl_enabled' => ['type' => 'bool', 'default' => 1, 'label' => 'International connections are on',
                'help' => 'A page where members meet people in other countries. Switch off to hide it everywhere.'],
            'intl_title' => ['type' => 'text', 'default' => 'Meet the world', 'max' => 60, 'label' => 'Page heading'],
            'intl_intro' => ['type' => 'textarea', 'default' => 'Friendships, language exchange and romance with people in other countries. Everyone here chose to be found.', 'max' => 220, 'rows' => 3, 'label' => 'Short introduction', 'help' => 'Shown under the heading.'],
            'intl_mode' => ['type' => 'select', 'default' => 'free', 'options' => ['free' => 'Free for everyone', 'join' => 'Members pay to be listed', 'access' => 'Members pay to browse and message', 'both' => 'Both pay'],
                'label' => 'Who pays', 'help' => 'Most sites do best letting people list for free and charging to browse and message. Admin > International explains the choices.'],
            'intl_join_fee' => ['type' => 'number', 'default' => 500, 'min' => 1, 'max' => 1000000, 'step' => 50, 'label' => 'Price to be listed'],
            'intl_join_days' => ['type' => 'number', 'default' => 30, 'min' => 0, 'max' => 3650, 'step' => 1, 'label' => 'A listing lasts (days)', 'help' => '0 means paid once, listed for good.'],
            'intl_access_fee' => ['type' => 'number', 'default' => 500, 'min' => 1, 'max' => 1000000, 'step' => 50, 'label' => 'Price to browse and message'],
            'intl_access_days' => ['type' => 'number', 'default' => 30, 'min' => 0, 'max' => 3650, 'step' => 1, 'label' => 'Access lasts (days)', 'help' => '0 means paid once, access for good.'],
            'intl_premium_free' => ['type' => 'select', 'default' => 'premium', 'options' => ['none' => 'Nobody, everyone pays', 'premium' => 'Premium and VIP', 'vip' => 'VIP only'],
                'label' => 'Plans that never pay', 'help' => 'Members on these plans get listing and access included.'],
            'intl_approval' => ['type' => 'bool', 'default' => 1, 'label' => 'New listings wait for my approval'],
            'intl_require_verified' => ['type' => 'bool', 'default' => 0, 'label' => 'Only verified members can be listed', 'help' => 'Members with the verified badge only. Strongly recommended for a paid listing.'],
            'intl_preview' => ['type' => 'bool', 'default' => 1, 'label' => 'Show blurred previews to people who have not paid'],
            'intl_msg_included' => ['type' => 'bool', 'default' => 1, 'label' => 'Access includes messaging listed members', 'help' => 'Off means access is for browsing only and people use message credits to write.'],
            'intl_contact_types' => ['type' => 'multiselect', 'default' => ['whatsapp', 'mobile', 'telegram'], 'options' => $types, 'label' => 'Phone number types members can add',
                'help' => 'Members pick one of these and enter their number. It stays private until they have chatted with someone.'],
        ],
    ],
];
