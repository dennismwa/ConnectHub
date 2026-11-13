<?php
/**
 * ConnectHub platform upgrades: media stories, launch access, advertising analytics and central experience settings.
 * Safe to run more than once. Existing user, payment, ad and profile records are not rewritten.
 */
return function (): void {
    // The supplied database has booking_enabled=0 while host_module_mode still says booking.
    // When booking is disabled, contact is the only consistent operating mode.
    if (mig_has_table('site_settings')) {
        q("UPDATE site_settings SET setting_value = 'contact', updated_by = NULL WHERE setting_key = 'host_module_mode' AND setting_value = 'booking' AND EXISTS (SELECT 1 FROM (SELECT setting_value FROM site_settings WHERE setting_key = 'booking_enabled' LIMIT 1) b WHERE b.setting_value = '0')");
        q("UPDATE site_settings SET setting_value = '0' WHERE setting_key = 'booking_enabled' AND EXISTS (SELECT 1 FROM (SELECT setting_value FROM site_settings WHERE setting_key = 'host_module_mode' LIMIT 1) m WHERE m.setting_value = 'contact')");
    }

    mig_exec("CREATE TABLE IF NOT EXISTS agent_stories (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        media_type ENUM('image','video') NOT NULL,
        media_path VARCHAR(255) NOT NULL,
        thumbnail_path VARCHAR(255) DEFAULT NULL,
        caption VARCHAR(500) DEFAULT NULL,
        status ENUM('pending','active','rejected','hidden') NOT NULL DEFAULT 'pending',
        moderation_note VARCHAR(500) DEFAULT NULL,
        reviewed_by INT UNSIGNED DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        published_at DATETIME DEFAULT NULL,
        view_count INT UNSIGNED NOT NULL DEFAULT 0,
        stats_recorded TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_agent_stories_public (status, expires_at, created_at),
        KEY idx_agent_stories_owner (user_id, status, expires_at),
        KEY idx_agent_stories_expiry (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    mig_exec("CREATE TABLE IF NOT EXISTS agent_story_daily_stats (
        stat_date DATE NOT NULL,
        stories_published INT UNSIGNED NOT NULL DEFAULT 0,
        image_stories INT UNSIGNED NOT NULL DEFAULT 0,
        video_stories INT UNSIGNED NOT NULL DEFAULT 0,
        views BIGINT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (stat_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    if (mig_has_table('agent_stories') && !mig_has_column('agent_stories', 'published_at')) {
        mig_exec("ALTER TABLE agent_stories ADD COLUMN published_at DATETIME DEFAULT NULL AFTER reviewed_at");
    }
    if (mig_has_table('agent_stories') && !mig_has_column('agent_stories', 'stats_recorded')) {
        mig_exec("ALTER TABLE agent_stories ADD COLUMN stats_recorded TINYINT(1) NOT NULL DEFAULT 0 AFTER view_count");
    }

    mig_exec("CREATE TABLE IF NOT EXISTS agent_story_views (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        story_id BIGINT UNSIGNED NOT NULL,
        viewer_key CHAR(64) NOT NULL,
        viewer_user_id INT UNSIGNED DEFAULT NULL,
        viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_agent_story_viewer (story_id, viewer_key),
        KEY idx_agent_story_views_date (viewed_at),
        KEY idx_agent_story_views_user (viewer_user_id, viewed_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    if (mig_has_table('advertisements')) {
        $placementType = (string)val("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'advertisements' AND column_name = 'placement'", [], '');
        if ($placementType !== '' && stripos($placementType, "'elite'") === false) {
            db()->exec("ALTER TABLE advertisements MODIFY placement ENUM('discover','search','landing','hosts','messages','browse','elite','global') NOT NULL DEFAULT 'discover'");
        }
    }

    mig_exec("CREATE TABLE IF NOT EXISTS advertisement_events (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        advertisement_id INT UNSIGNED NOT NULL,
        event_type ENUM('impression','click','story_view') NOT NULL,
        placement VARCHAR(40) DEFAULT NULL,
        visitor_key CHAR(64) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_ad_events_ad_date (advertisement_id, created_at),
        KEY idx_ad_events_type_date (event_type, created_at),
        KEY idx_ad_events_visitor (advertisement_id, event_type, visitor_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    mig_exec("CREATE TABLE IF NOT EXISTS launch_access_campaigns (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL DEFAULT 'Launch Access',
        description VARCHAR(500) DEFAULT NULL,
        is_enabled TINYINT(1) NOT NULL DEFAULT 0,
        starts_at DATETIME DEFAULT NULL,
        ends_at DATETIME DEFAULT NULL,
        duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 60,
        include_existing TINYINT(1) NOT NULL DEFAULT 0,
        eligible_roles VARCHAR(80) NOT NULL DEFAULT 'user',
        features_json MEDIUMTEXT NOT NULL,
        reminder_days TINYINT UNSIGNED NOT NULL DEFAULT 3,
        created_by INT UNSIGNED DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_launch_campaign_active (is_enabled, starts_at, ends_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    mig_exec("CREATE TABLE IF NOT EXISTS launch_access_grants (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        starts_at DATETIME NOT NULL,
        expires_at DATETIME NOT NULL,
        status ENUM('active','expired','revoked') NOT NULL DEFAULT 'active',
        features_json MEDIUMTEXT NOT NULL,
        reminder_sent_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_launch_campaign_user (campaign_id, user_id),
        KEY idx_launch_grant_user (user_id, status, expires_at),
        KEY idx_launch_grant_expiry (status, expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $defaults = [
        'agent_media_stories_enabled' => '1',
        'agent_story_max_image_mb' => '8',
        'agent_story_max_video_mb' => '20',
        'agent_story_max_active' => '5',
        'agent_story_require_approval' => '1',
        'daily_picks_featured' => '1',
        'launch_access_enabled' => '0',
    ];
    foreach ($defaults as $k => $v) {
        mig_setting_default($k, $v);
    }

    $features = json_encode([
        'daily_swipes' => -1,
        'daily_messages' => -1,
        'see_who_liked' => true,
        'advanced_filters' => true,
        'read_receipts' => true,
        'profile_visitors' => true,
        'no_ads' => true,
        'max_photos' => 12,
    ], JSON_UNESCAPED_SLASHES);
    q('INSERT IGNORE INTO launch_access_campaigns (id, name, description, is_enabled, duration_days, include_existing, eligible_roles, features_json, reminder_days) VALUES (1, :n, :d, 0, 60, 0, :r, :f, 3)', [
        'n' => 'ConnectHub Launch Access',
        'd' => 'A limited launch promotion for new members. No payment method is required and no automatic charge is made when access ends.',
        'r' => 'user', 'f' => $features,
    ]);

    // Public copy in the original showcase seed used the generic Host label. Match the current Escort terminology and contact-only operating model.
    if (mig_has_table('landing_sections')) {
        q("UPDATE landing_sections SET title = 'Escorts', content = REPLACE(content, 'Need company for an evening or an event?', 'Browse approved Escorts') WHERE section_key = 'hosts'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'Concierges are approved, ID-verified people offering friendly, professional company: dinners, city tours, travel and events. Book and pay a deposit securely.', 'Escorts are approved, ID-verified adults offering non-sexual companionship, public events and conversation. Nothing is booked or paid for on this platform.') WHERE section_key = 'hosts'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'Meet the Hosts', 'View Escorts') WHERE section_key = 'hosts'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'Become a Host', 'Become an Escort') WHERE section_key = 'hosts'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'Deposit held securely until the booking is confirmed', 'Contact details are shown according to each profile settings') WHERE section_key = 'hosts'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'Clear rates and services up front', 'Clear profile details and contact options') WHERE section_key = 'hosts'");
        q("UPDATE landing_sections SET title = 'Featured Escorts', content = REPLACE(content, 'Top Hosts near you', 'Featured Escorts near you') WHERE section_key = 'agents_top'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'VIP, featured and verified Hosts. Meet them, see what they offer and get in touch.', 'Featured and verified Escorts. View their profiles and available contact options.') WHERE section_key = 'agents_top'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'See all Hosts', 'See all Escorts') WHERE section_key = 'agents_top'");
        q("UPDATE landing_sections SET title = 'Recently joined Escorts', content = REPLACE(content, 'New Hosts', 'Recently joined Escorts') WHERE section_key = 'agents_new'");
        q("UPDATE landing_sections SET content = REPLACE(content, 'Browse Hosts', 'Browse Escorts') WHERE section_key = 'agents_new'");
    }

    // Keep the old showcase strip and the new media stories on the same pages by default.
    q("UPDATE site_settings SET setting_value = 'landing,hosts,discover' WHERE setting_key = 'agent_story_pages' AND setting_value = 'landing,hosts'");

    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
