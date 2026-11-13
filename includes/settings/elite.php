<?php
/**
 * Elite settings. Kept in the Discovery tab's data but edited on their own page, Admin > Elite > Setup ('ui' => false).
 * Prices are in the site currency. A price of 0 switches that option off.
 */
$who = ['none' => 'Nobody, everyone pays', 'premium' => 'Premium and VIP members', 'vip' => 'VIP members only'];
$f = function (array $def): array {
    return $def + ['ui' => false];
};
return [
    'discovery' => [
        'Elite' => [
            'elite_enabled' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Elite is on']),
            'elite_label' => $f(['type' => 'text', 'default' => 'Elite', 'max' => 24, 'label' => 'What to call it', 'help' => 'Shown in menus, buttons and page titles.']),
            'elite_title' => $f(['type' => 'text', 'default' => 'Meet established, successful people', 'max' => 80, 'label' => 'Page heading']),
            'elite_intro' => $f(['type' => 'textarea', 'default' => 'A private circle for accomplished people and the people who want to meet them. Verified profiles, clear intentions, and every Elite member decides who reaches them.', 'max' => 400, 'label' => 'Page introduction']),
            // joining (the Elite member's side)
            'elite_join_fee' => $f(['type' => 'number', 'default' => 0, 'min' => 0, 'max' => 1000000, 'step' => 50, 'label' => 'Price to join Elite', 'help' => '0 makes joining free.']),
            'elite_join_days' => $f(['type' => 'number', 'default' => 30, 'min' => 0, 'max' => 3650, 'step' => 1, 'label' => 'Membership lasts (days)', 'help' => '0 means one payment, membership never ends.']),
            'elite_join_free_for' => $f(['type' => 'select', 'default' => 'none', 'options' => $who, 'label' => 'Joins free for']),
            'elite_approval' => $f(['type' => 'bool', 'default' => 1, 'label' => 'New Elite profiles wait for my approval']),
            'elite_require_verified' => $f(['type' => 'bool', 'default' => 0, 'label' => 'Only members with the verified badge can join']),
            'elite_require_photo' => $f(['type' => 'bool', 'default' => 1, 'label' => 'A clear, approved photo is needed to join']),
            // seeing a full profile (the other member's side)
            'elite_view_mode' => $f(['type' => 'select', 'default' => 'pay', 'options' => [
                'pay' => 'They unlock it: pay per profile, buy a pack, or a time pass',
                'free' => 'Free for every signed-in member',
                'accept' => 'Only after the Elite member accepts their request',
            ], 'label' => 'How members see a full Elite profile']),
            'elite_single_price' => $f(['type' => 'number', 'default' => 200, 'min' => 0, 'max' => 1000000, 'step' => 10, 'label' => 'Unlock one profile', 'help' => '0 hides this option.']),
            'elite_pack_size' => $f(['type' => 'number', 'default' => 5, 'min' => 2, 'max' => 100, 'step' => 1, 'label' => 'Profiles in a pack']),
            'elite_pack_price' => $f(['type' => 'number', 'default' => 800, 'min' => 0, 'max' => 1000000, 'step' => 10, 'label' => 'Price of a pack', 'help' => '0 hides this option.']),
            'elite_pack_days' => $f(['type' => 'number', 'default' => 30, 'min' => 1, 'max' => 3650, 'step' => 1, 'label' => 'Unused pack credits expire after (days)']),
            'elite_pass_price' => $f(['type' => 'number', 'default' => 2500, 'min' => 0, 'max' => 1000000, 'step' => 50, 'label' => 'Price of a time pass', 'help' => 'See every Elite profile while the pass runs. 0 hides this option.']),
            'elite_pass_days' => $f(['type' => 'number', 'default' => 30, 'min' => 1, 'max' => 3650, 'step' => 1, 'label' => 'A pass lasts (days)']),
            'elite_unlock_days' => $f(['type' => 'number', 'default' => 0, 'min' => 0, 'max' => 3650, 'step' => 1, 'label' => 'An unlocked profile stays open for (days)', 'help' => '0 keeps it open for good.']),
            'elite_access_free_for' => $f(['type' => 'select', 'default' => 'none', 'options' => $who, 'label' => 'Unlocks are free for']),
            'elite_preview_photo' => $f(['type' => 'select', 'default' => 'blur', 'options' => ['blur' => 'A blurred photo', 'hide' => 'No photo, just initials'], 'label' => 'Locked profiles show']),
            'elite_preview_chars' => $f(['type' => 'number', 'default' => 140, 'min' => 0, 'max' => 600, 'step' => 10, 'label' => 'Characters of the "about" text shown before unlocking']),
            'elite_guest_preview' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Visitors who are not signed in can see the locked cards']),
            // connecting
            'elite_request_needs_unlock' => $f(['type' => 'bool', 'default' => 0, 'label' => 'A request needs the profile unlocked first']),
            'elite_request_daily' => $f(['type' => 'number', 'default' => 5, 'min' => 1, 'max' => 100, 'step' => 1, 'label' => 'Requests a member can send per day']),
            'elite_request_expire_days' => $f(['type' => 'number', 'default' => 30, 'min' => 1, 'max' => 365, 'step' => 1, 'label' => 'Unanswered requests expire after (days)']),
            'elite_filter_enabled' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Elite members can filter requests by their criteria']),
            'elite_share_phone' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Elite members may share their phone number after accepting']),
            // page text, web address and look (Admin > Elite > Pages and look)
            'elite_slug' => $f(['type' => 'text', 'default' => 'elite', 'max' => 30, 'label' => 'Web address', 'help' => 'The word after your site name, for example /elite or /circle.']),
            'elite_slug_old' => $f(['type' => 'text', 'default' => '', 'max' => 30, 'label' => 'Previous web address (it sends people to the new one)']),
            'elite_access_title' => $f(['type' => 'text', 'default' => 'Open the profiles you like', 'max' => 80, 'label' => 'Access page heading']),
            'elite_access_intro' => $f(['type' => 'textarea', 'default' => 'Before you unlock a profile you see a first name, age, place, headline and a blurred photo. Unlocking shows every photo and the whole profile. Contact always goes through a request the member accepts.', 'max' => 400, 'label' => 'Access page introduction']),
            'elite_theme' => $f(['type' => 'select', 'default' => 'gold', 'options' => ['gold' => 'Champagne gold', 'rose' => 'Rose', 'emerald' => 'Emerald', 'sapphire' => 'Sapphire', 'amethyst' => 'Amethyst'], 'label' => 'Accent colour']),
            'elite_hero_image' => $f(['type' => 'text', 'default' => '', 'max' => 255, 'label' => 'Page banner picture']),
            'elite_hero_cards' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Show a few member cards in the page banner']),
            'elite_hero_stats' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Show member numbers under the banner']),
            'elite_how_show' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Show "How it works"']),
            'elite_faq_show' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Show the questions and answers']),
            'elite_faq' => $f(['type' => 'textarea', 'max' => 4000, 'label' => 'Questions and answers', 'default' => "How does it work?\nYou create a free account, browse the cards, and send a short request to someone you would like to meet. Every member decides who reaches them. If they accept, a chat opens for you both.\n\nWhat does it cost?\nLooking at the cards is free. Prices for opening a full profile, if there are any, are on the Access page before you pay anything.\n\nHow do you keep fake profiles out?\nA person on our team reviews every profile before it goes live, and members can earn a Verified badge.\n\nIs my phone number shown?\nNo. Contact always starts with a request. A member may choose to share a number after they accept.\n\nCan anyone ask me for money?\nNo. This is for genuine relationships. Never send money to someone you met online, and report anyone who asks."]),
            'elite_saves_enabled' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Members can save profiles to a shortlist']),
        ],
    ],
];
