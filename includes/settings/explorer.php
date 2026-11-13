<?php
/**
 * Extra settings owned by the Explorer module (matching engine, visitors, verification wording).
 * Everything else the Explorer pages need already lives in the onboarding, discovery, privacy and safety tabs.
 */
return [
    'discovery' => [
        'Matching engine' => [
            'match_title' => ['type' => 'text', 'default' => 'It is a match!', 'label' => 'Match celebration title', 'help' => 'The headline shown when two members like each other.', 'max' => 40],
            'matching_stale_hours' => ['type' => 'number', 'default' => 12, 'label' => 'Refresh match scores after (hours)', 'help' => 'Scores for a member are recalculated the next time they open Discover once they are older than this.', 'min' => 1, 'max' => 168, 'step' => 1],
            'matching_candidates' => ['type' => 'number', 'default' => 300, 'label' => 'Profiles scored per member', 'help' => 'How many nearby candidates are scored each time. Higher is more thorough but slower on big sites.', 'min' => 50, 'max' => 2000, 'step' => 50],
            'match_liked_you_points' => ['type' => 'number', 'default' => 8, 'label' => 'Extra points for people who already like them', 'help' => 'Shows people who liked a member a little sooner. A Super Like counts for double.', 'min' => 0, 'max' => 30, 'step' => 1],
            'match_nope_penalty' => ['type' => 'number', 'default' => 25, 'label' => 'Points taken off people they passed on', 'help' => 'Only matters when passed profiles are allowed to come back.', 'min' => 0, 'max' => 60, 'step' => 1],
            'daily_picks_featured' => ['type' => 'bool', 'default' => 1, 'label' => 'Put Featured profiles in Daily picks', 'help' => 'Featured profiles can fill part of each member’s daily picks.'],
        ],
    ],
    'privacy' => [
        'Profile visitors' => [
            'visit_notifications' => ['type' => 'bool', 'default' => 1, 'label' => 'Tell members when someone views their profile', 'help' => 'Sends one in-app notification per viewer per day. Members who cannot see visitors are told “Someone viewed your profile”.'],
            'visitors_free_teasers' => ['type' => 'number', 'default' => 6, 'label' => 'Blurred teasers on Visitors and Likes for Free members', 'help' => 'Free members see this many blurred photos plus the total count on the Visitors and Liked you pages. Names and sharp photos stay hidden.', 'min' => 0, 'max' => 24, 'step' => 1],
        ],
    ],
    'onboarding' => [
        'Interests' => [
            'interests_max' => ['type' => 'number', 'default' => 15, 'label' => 'Maximum interests', 'help' => 'The most interests a member can pick.', 'min' => 5, 'max' => 40, 'step' => 1],
        ],
    ],
    'safety' => [
        'Profile verification' => [
            'verification_instruction' => ['type' => 'textarea', 'default' => "Take a clear selfie in good light, facing the camera.\nHold up two fingers beside your face so we know it is you right now.\nNo sunglasses, hats or filters.", 'label' => 'Selfie instructions', 'help' => 'One line per step, shown on the verification page.', 'max' => 600],
            'verification_assign' => ['type' => 'select', 'default' => 'choose', 'options' => ['choose' => 'Members choose a method', 'random' => 'We give each member one at random'],
                'label' => 'When several methods are on', 'help' => 'Random makes it harder to prepare a fake picture in advance. Manage the methods under Verification methods.'],
            'verification_reapply_days' => ['type' => 'number', 'default' => 0, 'label' => 'Wait before trying again after a rejection (days)', 'help' => '0 lets members resubmit straight away.', 'min' => 0, 'max' => 90, 'step' => 1],
        ],
    ],
];
