<?php
/**
 * Concierge search settings. Every page's search text can now carry keywords, a heading and a short introduction, each
 * Concierge category can have its own search text, and the site-wide words now say "free dating site".
 *
 * Nothing saved is changed except one thing: the site description, tagline and keywords are rewritten to mention a free
 * dating site ONLY when they still hold the exact words the site shipped with. Anything an admin typed is left alone.
 * Safe to run twice.
 */
return function (): void {
    if (mig_has_table('seo_pages')) {
        mig_add_column('seo_pages', 'keywords', 'VARCHAR(500) NULL');
        mig_add_column('seo_pages', 'heading', 'VARCHAR(160) NULL');
        mig_add_column('seo_pages', 'intro', 'TEXT NULL');
    }
    if (mig_has_table('host_categories')) {
        mig_add_column('host_categories', 'seo_title', 'VARCHAR(120) NULL');
        mig_add_column('host_categories', 'seo_description', 'VARCHAR(300) NULL');
        mig_add_column('host_categories', 'seo_keywords', 'VARCHAR(500) NULL');
        mig_add_column('host_categories', 'seo_intro', 'TEXT NULL');
    }

    foreach ([
        'host_seo_profile_title' => '{name}, {category} in {place}',   // pattern for each profile's search title
        'host_seo_profile_desc' => '',                                 // empty: the profile's own headline and about text
        'host_seo_keywords' => '',                                     // words added to every Concierge page
        'host_seo_faq' => '',                                          // "question line, answer lines" blocks
        'host_seo_faq_show' => '1',                                    // show them on the directory and mark them up for search
        'host_seo_index_gate' => '1',                                  // let search engines index the 18+ confirmation page with its text
        'host_seo_schema' => '1',                                      // structured data on the directory and on profiles
        'host_seo_profile_keywords' => '1',                            // keywords on a profile: category, place, the words above
    ] as $k => $v) {
        mig_setting_default($k, $v);
    }

    // Wording the site shipped with before this version => the new wording. Only an exact match is replaced.
    $swap = [
        'meta_keywords' => ['dating Kenya, meet people Nairobi, friends, companionship', 'free dating site Kenya, dating Kenya, meet people Nairobi, singles near me, friends, companionship'],
        'site_description' => ['ConnectHub helps you meet real people in your town, for dating, friendship and company. Verified profiles, private by default.', 'ConnectHub is a free dating site where you meet real people in your town, for dating, friendship and company. Verified profiles, private by default.'],
        'site_tagline' => ['Meet people who live where you do', 'Free dating site in Kenya: meet people near you'],
    ];
    foreach ($swap as $key => [$old, $new]) {
        q('UPDATE site_settings SET setting_value = :n WHERE setting_key = :k AND setting_value = :o', ['n' => $new, 'k' => $key, 'o' => $old]);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
