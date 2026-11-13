<?php
/** Host verification methods. Edited on Admin > Hosts > Host verification (not shown on the general Settings screen). */
return [
    'hosts' => [
        'Host verification' => [
            'host_verify_id_enabled' => ['type' => 'bool', 'default' => 1, 'ui' => false, 'label' => 'ID document check'],
            'host_verify_photo_enabled' => ['type' => 'bool', 'default' => 0, 'ui' => false, 'label' => 'Live photo check'],
            'host_verify_rule' => ['type' => 'select', 'default' => 'any', 'options' => ['any' => 'Either method is enough', 'all' => 'Every method that is on'], 'ui' => false, 'label' => 'When both are on'],
        ],
    ],
];
