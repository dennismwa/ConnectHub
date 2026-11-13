<?php
/**
 * Agent showcase: admin-set VIP and pin order per Host, starter landing blocks that show Hosts, and the "everyone can see agents" defaults.
 * Safe to run twice and never overwrites anything an admin already saved: columns are added only when missing, blocks and settings
 * only when their key does not exist yet.
 */
return function (): void {
    // ---- per-Host showcase controls (set by admins on the Host page) ----
    if (mig_has_table('host_profile_ext')) {
        mig_add_column('host_profile_ext', 'is_vip', 'TINYINT(1) NOT NULL DEFAULT 0');
        mig_add_column('host_profile_ext', 'pin_order', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
    }

    // ---- starter landing blocks (the admin can edit, reorder, hide or delete them) ----
    if (mig_has_table('landing_sections')) {
        $min = (int)val('SELECT COALESCE(MIN(sort_order), 1) FROM landing_sections', [], 1);
        $afterProfiles = (int)val("SELECT sort_order FROM landing_sections WHERE section_key = 'profiles'", [], 0);
        $blocks = [
            ['agents_top', 'Top agents', $min - 1, [
                'title' => 'Top Hosts near you', 'subtitle' => 'VIP, featured and verified Hosts. Meet them, see what they offer and get in touch.',
                'tier_vip' => true, 'tier_featured' => true, 'tier_premium' => true, 'tier_paid' => true, 'tier_boosted' => false, 'fill_others' => true,
                'sort' => 'rotate', 'category' => 0, 'count' => 8, 'layout' => 'rail_mobile', 'card_style' => 'overlay',
                'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2,
                'show_services' => true, 'max_services' => 3, 'show_rating' => true, 'show_price' => true, 'show_tier' => true, 'show_contact' => true, 'show_bio' => true,
                'cta_text' => 'See all Hosts', 'cta_url' => '/hosts',
            ]],
            ['agents_new', 'New agents', $afterProfiles > 0 ? $afterProfiles : $min + 1, [
                'title' => 'New Hosts', 'subtitle' => 'Recently approved and ready to meet.',
                'tier_vip' => false, 'tier_featured' => false, 'tier_premium' => false, 'tier_paid' => false, 'tier_boosted' => false, 'fill_others' => true,
                'sort' => 'newest', 'category' => 0, 'count' => 4, 'layout' => 'rail_mobile', 'card_style' => 'stack',
                'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2,
                'show_services' => true, 'max_services' => 3, 'show_rating' => true, 'show_price' => true, 'show_tier' => true, 'show_contact' => true, 'show_bio' => false,
                'cta_text' => 'Browse Hosts', 'cta_url' => '/hosts',
            ]],
        ];
        foreach ($blocks as [$key, $label, $sort, $content]) {
            if (!val('SELECT id FROM landing_sections WHERE section_key = :k', ['k' => $key])) {
                insert('landing_sections', ['section_key' => $key, 'title' => $label, 'is_enabled' => 1, 'sort_order' => $sort, 'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        }
    }

    // ---- defaults: visitors may see Hosts, their photos, services, rates and contact details (admin can tighten this in Admin > Agent visibility) ----
    foreach ([
        'hosts_directory_access' => 'everyone', 'blur_guests_hosts' => '0', 'hosts_contact_visibility' => 'everyone', 'host_show_rates' => '1',
        'agent_default_bio' => 'Meet {name} from {location}. {about}',
        'agent_story_enabled' => '1',
    ] as $k => $v) {
        mig_setting_default($k, $v);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
