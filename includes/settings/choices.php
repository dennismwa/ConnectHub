<?php
/** "What brings you here?" picker on the landing page. Edited on Admin > Visitor choices ('ui' => false). */
$f = fn(array $def): array => $def + ['ui' => false];
return [
    'landing' => [
        'Visitor choices' => [
            'choice_enabled' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Ask new visitors what they are looking for']),
            'choice_title' => $f(['type' => 'text', 'default' => 'What brings you here?', 'max' => 60, 'label' => 'Heading']),
            'choice_subtitle' => $f(['type' => 'text', 'default' => 'Choose one. We remember it on this device, and you can change it any time.', 'max' => 160, 'label' => 'Line under the heading']),
            'choice_skip_text' => $f(['type' => 'text', 'default' => 'Show me everything', 'max' => 40, 'label' => 'Skip link']),
            'choice_delay' => $f(['type' => 'number', 'default' => 1, 'min' => 0, 'max' => 20, 'step' => 1, 'label' => 'Open after (seconds)']),
            'choice_members' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Also ask signed-in members who have not chosen yet']),
            'choice_default' => $f(['type' => 'select', 'default' => 'dating', 'options' => ['dating' => 'Dating', 'fun' => 'Fun', 'elite' => 'Elite', 'friends' => 'Friends', 'all' => 'Everything'], 'label' => 'Shown to visitors who have not chosen yet']),
            'choice_exclusive' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Show only what they chose']),
            'choice_switcher' => $f(['type' => 'bool', 'default' => 1, 'label' => 'Show a "You are seeing" switch on the home page']),
            'choice_cards' => $f(['type' => 'textarea', 'default' => '', 'max' => 20000, 'label' => 'Cards (saved by the editor)']),
        ],
    ],
];
