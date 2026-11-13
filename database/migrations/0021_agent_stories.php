<?php
/**
 * Agent stories: the round profile strip at the top of the landing page and the Hosts directory.
 * Adds a "story" advert format (sponsored bubbles run through Admin > Adverts, with dates, limits and counts), a short promotion line
 * each Host can write, and the story settings. Safe to run twice: the column and enum are changed only when needed and settings are
 * inserted only when their key does not exist yet, so nothing an admin saved is overwritten.
 */
return function (): void {
    if (mig_has_table('advertisements')) {
        $type = (string)val("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'advertisements' AND column_name = 'ad_type'", [], '');
        if ($type !== '' && stripos($type, "'story'") === false) {
            db()->exec("ALTER TABLE `advertisements` MODIFY `ad_type` ENUM('banner','card','popup','story') NOT NULL DEFAULT 'card'");
        }
    }
    if (mig_has_table('host_profile_ext')) {
        mig_add_column('host_profile_ext', 'promo_text', 'VARCHAR(140) NULL DEFAULT NULL');
    }
    foreach ([
        'agent_story_enabled' => '1',          // the strip itself
        'agent_story_pages' => 'landing,hosts', // where it shows
        'agent_story_count' => '12',           // agent bubbles at most
        'agent_story_tiers' => 'vip,featured,premium,paid,boosted',
        'agent_story_fill' => '1',             // other agents fill the strip when too few are paid
        'agent_story_join' => '1',             // first bubble: "Become an agent"
        'agent_story_ads' => '1',              // include sponsored story adverts
        'agent_story_viewer' => '1',           // tap opens the full-screen viewer (off: tap opens the profile)
        'agent_story_seconds' => '5',          // seconds per story in the viewer
        'agent_story_drift' => '1',            // the strip slowly scrolls by itself
        'agent_story_promo' => '1',            // Hosts may add a promotion line that shows in their story
    ] as $k => $v) {
        mig_setting_default($k, $v);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
