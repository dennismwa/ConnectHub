<?php
/**
 * What the site calls Hosts, and the Hosts strip on Discover. Edited on Admin > Hosts > Names and Discover
 * (these keys are not shown on the general Settings screen).
 */
return [
    'hosts' => [
        'Names and Discover' => [
            'host_term_singular' => ['type' => 'text', 'default' => 'Host', 'max' => 30, 'ui' => false, 'label' => 'Name for one Host'],
            'host_term_plural' => ['type' => 'text', 'default' => 'Hosts', 'max' => 30, 'ui' => false, 'label' => 'Name for many Hosts'],
            'host_url_slug' => ['type' => 'text', 'default' => 'hosts', 'max' => 30, 'ui' => false, 'label' => 'Web address of the directory'],
            'host_area_slug' => ['type' => 'text', 'default' => 'host', 'max' => 30, 'ui' => false, 'label' => 'Web address of the member area'],
            'host_discover_enabled' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'Show Hosts on Discover'],
            'host_discover_title' => ['type' => 'text', 'default' => '', 'max' => 80, 'ui' => false, 'label' => 'Discover section title'],
            'host_discover_subtitle' => ['type' => 'text', 'default' => 'Friendly, verified company for dinners, city tours and events.', 'max' => 160, 'ui' => false, 'label' => 'Discover section subtitle'],
            'host_discover_count' => ['type' => 'number', 'default' => 12, 'min' => 2, 'max' => 40, 'step' => 1, 'ui' => false, 'label' => 'How many to show'],
            'host_discover_order' => ['type' => 'select', 'default' => 'recommended', 'options' => ['recommended' => 'Recommended', 'rating' => 'Best rated', 'newest' => 'Newest', 'nearest' => 'Nearest'], 'ui' => false, 'label' => 'Order'],
            'host_discover_position' => ['type' => 'select', 'default' => 'after_picks', 'options' => ['top' => 'Above everything', 'after_picks' => 'After today\'s picks', 'bottom' => 'Below the people'], 'ui' => false, 'label' => 'Where it sits'],
            'host_discover_swipe' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'Also show it under the swipe deck'],
        ],
    ],
];
