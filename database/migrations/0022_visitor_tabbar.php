<?php
/**
 * Visitor tab bar: the floating app-style bar at the bottom of public pages on a phone (Home, Hosts, Browse, Pricing, Join).
 * Adds the `public_tabbar` menu (editable under Admin > Navigation) and the `public_dock` switch. Safe to run twice: the items are
 * added only when the menu is empty and the setting is inserted only when the admin has never saved it.
 */
return function (): void {
    mig_setting_default('public_dock', '1');
    if (mig_has_table('nav_items') && (int)val("SELECT COUNT(*) FROM nav_items WHERE menu = 'public_tabbar'", [], 0) === 0) {
        foreach ([
            ['Home', '/', 'home', 'any'],
            ['Hosts', '/hosts', 'concierge-bell', 'any'],
            ['Browse', '/browse', 'compass', 'any'],
            ['Pricing', '/pricing', 'sparkles', 'any'],
            ['Join', '/register', 'user-plus', 'guest'],
        ] as $i => [$label, $url, $icon, $aud]) {
            insert('nav_items', ['menu' => 'public_tabbar', 'label' => $label, 'url' => $url, 'icon' => $icon, 'audience' => $aud, 'badge_key' => null, 'sort_order' => $i + 1, 'is_active' => 1]);
        }
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
