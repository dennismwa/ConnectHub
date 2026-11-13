<?php
/**
 * Fun landing page: separate VIP, Prime, Featured and Recently Joined showcases with unique profiles.
 * Public rating displays are disabled without deleting existing review records.
 */
return function (): void {
    if (mig_has_table('landing_sections')) {
        $min = (int)val('SELECT COALESCE(MIN(sort_order), 1) FROM landing_sections', [], 1);
        $blocks = [
            ['agents_vip', 'VIP Escorts', $min - 4, [
                'title' => 'VIP Escorts', 'subtitle' => 'Priority profiles with VIP access.',
                'tier_vip' => true, 'tier_featured' => false, 'tier_premium' => false, 'tier_paid' => false, 'tier_boosted' => false, 'fill_others' => false,
                'sort' => 'rotate', 'category' => 0, 'count' => 12, 'layout' => 'grid', 'card_style' => 'stack',
                'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2,
                'show_services' => false, 'show_rating' => false, 'show_price' => true, 'show_tier' => true, 'show_contact' => true, 'show_bio' => true,
                'cta_text' => 'See all Escorts', 'cta_url' => '/hosts',
            ]],
            ['agents_prime', 'Prime Escorts', $min - 3, [
                'title' => 'Prime Escorts', 'subtitle' => 'Profiles included with an active Prime subscription.',
                'tier_vip' => false, 'tier_featured' => false, 'tier_premium' => true, 'tier_paid' => false, 'tier_boosted' => false, 'fill_others' => false,
                'sort' => 'rotate', 'category' => 0, 'count' => 12, 'layout' => 'grid', 'card_style' => 'stack',
                'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2,
                'show_services' => false, 'show_rating' => false, 'show_price' => true, 'show_tier' => true, 'show_contact' => true, 'show_bio' => true,
                'cta_text' => 'See all Escorts', 'cta_url' => '/hosts',
            ]],
            ['agents_top', 'Featured Escorts', $min - 2, [
                'title' => 'Featured Escorts near you', 'subtitle' => 'Profiles selected for featured placement.',
                'tier_vip' => false, 'tier_featured' => true, 'tier_premium' => false, 'tier_paid' => false, 'tier_boosted' => false, 'fill_others' => false,
                'sort' => 'rotate', 'category' => 0, 'count' => 12, 'layout' => 'grid', 'card_style' => 'stack',
                'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2,
                'show_services' => false, 'show_rating' => false, 'show_price' => true, 'show_tier' => true, 'show_contact' => true, 'show_bio' => true,
                'cta_text' => 'See all Escorts', 'cta_url' => '/hosts',
            ]],
            ['agents_new', 'Recently Joined Escorts', $min - 1, [
                'title' => 'Recently Joined Escorts', 'subtitle' => 'Newly approved profiles, with no repeats from the sections above.',
                'tier_vip' => false, 'tier_featured' => false, 'tier_premium' => false, 'tier_paid' => false, 'tier_boosted' => false, 'fill_others' => true,
                'sort' => 'newest', 'category' => 0, 'count' => 16, 'layout' => 'grid', 'card_style' => 'stack',
                'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2,
                'show_services' => false, 'show_rating' => false, 'show_price' => true, 'show_tier' => false, 'show_contact' => true, 'show_bio' => true,
                'cta_text' => 'Browse all Escorts', 'cta_url' => '/hosts',
            ]],
        ];
        foreach ($blocks as [$key, $title, $sort, $defaults]) {
            $existing = row('SELECT id, content FROM landing_sections WHERE section_key = :k LIMIT 1', ['k' => $key]);
            if (!$existing) {
                insert('landing_sections', [
                    'section_key' => $key, 'title' => $title, 'is_enabled' => 1, 'sort_order' => $sort,
                    'content' => json_encode($defaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
                continue;
            }
            $saved = json_decode((string)$existing['content'], true);
            $saved = is_array($saved) ? $saved : [];
            // Preserve custom copy and visual preferences while enforcing distinct tier membership, multi-row grids and no ratings.
            foreach (['tier_vip','tier_featured','tier_premium','tier_paid','tier_boosted','fill_others','sort','count','layout','show_rating'] as $field) {
                $saved[$field] = $defaults[$field];
            }
            $saved['title'] = $defaults['title'];
            $saved['subtitle'] = $defaults['subtitle'];
            foreach (['show_services','show_price','show_tier','show_contact','show_bio','cta_text','cta_url','category'] as $field) {
                $saved[$field] = $defaults[$field];
            }
            $saved['cols_desktop'] = $defaults['cols_desktop'];
            $saved['cols_tablet'] = $defaults['cols_tablet'];
            $saved['cols_mobile'] = $defaults['cols_mobile'];
            update('landing_sections', ['title' => $title, 'content' => json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)], ['id' => (int)$existing['id']]);
        }
    }
    // A saved visitor-choice config may override defaults. Add the new tier sections to the Fun experience's lead sequence.
    $saved = json_decode((string)setting('choice_cards'), true);
    if (is_array($saved) && isset($saved['fun']) && is_array($saved['fun'])) {
        $saved['fun']['lead'] = ['agents_vip', 'agents_prime', 'agents_top', 'agents_new'];
        q('UPDATE site_settings SET setting_value = :v WHERE setting_key = :k', [
            'v' => json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'k' => 'choice_cards'
        ]);
    }
    mig_setting_default('agent_default_bio', 'Meet {name}, a {age} year old {gender} from {location}.');
    mig_setting_default('public_ratings_enabled', '0');
    cache_clear();
};
