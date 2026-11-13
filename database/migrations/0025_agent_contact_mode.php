<?php
/**
 * The contact way of running the Hosts module: people reach a Host on WhatsApp, by phone or through a message
 * here instead of booking a time. Services are then named rather than sold, with a price only when the Host adds one.
 *
 * Adds: a price the Host may put on each of their services, a colour for each service in the catalogue, the
 * settings that switch between the booking way and the contact way, how Discover arranges its sections, where the
 * round "Become a Host" button sits, and the 4-digit passcode a member may put on their own account.
 * Nothing is removed, so the admin can switch back.
 * Safe to run twice: each column and setting is only added when it is missing.
 */
return function (): void {
    mig_add_column('host_profiles', 'service_prices', "VARCHAR(1500) NULL AFTER service_ids");
    mig_add_column('host_profiles', 'contact_intro', "VARCHAR(220) NULL AFTER about");
    mig_add_column('host_services', 'color', "VARCHAR(16) NOT NULL DEFAULT '' AFTER icon");

    mig_setting_default('host_module_mode', 'booking');
    mig_setting_default('host_service_prices', '1');
    mig_setting_default('host_contact_intro_enabled', '1');
    mig_setting_default('host_contact_headline', '');
    mig_setting_default('host_service_card_style', 'tint');

    // Discover: the chip that switches experience, and the sections it arranges
    mig_setting_default('discover_mode_switch', '1');
    mig_setting_default('discover_mode_filters', '1');
    mig_setting_default('discover_elite_enabled', '1');
    mig_setting_default('discover_elite_count', '8');

    // The round "Become a Host" button moves into the menu. A site that had switched the first story
    // bubble off keeps it off; every other site gets the button in its menu.
    $joinWasOff = (string)val("SELECT setting_value FROM site_settings WHERE setting_key = 'agent_story_join'", [], '1') === '0';
    mig_setting_default('agent_join_place', $joinWasOff ? 'off' : 'menu');

    // A 4-digit passcode a member may put on their own account, kept the same way as a password.
    mig_add_column('user_profiles', 'passcode_hash', "VARCHAR(255) NULL");
    mig_add_column('user_profiles', 'passcode_set_at', "DATETIME NULL");
    mig_setting_default('passcode_enabled', '1');
    mig_setting_default('passcode_idle_minutes', '30');

    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
