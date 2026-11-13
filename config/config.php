<?php
/**
 * ConnectHub configuration. The installer writes config/config.php for you.
 * To set up by hand, copy this file to config.php and fill in your details.
 */
return [
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'zurihubc_hubconnect',
        'user'    => 'zurihubc_hubconnect',
        'pass'    => 'IwIHAV+~bHl8~PB6',
        'charset' => 'utf8mb4',
    ],
    // Random secret used to sign tokens. The installer generates one.
    'app_key'   => 'change-me-to-a-long-random-string',
    // Show detailed errors on screen. Keep false on a live site.
    'debug'     => false,
    // Sub-folder the site is installed in, e.g. '/connecthub'. null = detect.
    'base_path' => null,
    'installed' => true,
];
