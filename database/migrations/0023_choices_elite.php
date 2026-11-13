<?php
/**
 * 1. "What brings you here?" picker: the visitor's choice (Dating, Fun, Elite, Friends) is kept on their device in a cookie and,
 *    once they sign in, on their profile (user_profiles.explore_mode) so it follows them to a new phone.
 * 2. Elite: a private circle for established, successful people and the members who want to meet them.
 *    elite_profiles  one row per Elite member (approved by an admin), with who may reach them (criteria) and privacy choices
 *    elite_access    a member's unlock credits (packs) and time pass
 *    elite_unlocks   which Elite profiles a member may see in full, and until when
 *    elite_requests  "I'd like to connect" requests, their match score and whether they passed the Elite member's filter
 * Safe to run twice: tables are created only when missing, columns only when missing, the landing block and menu links only once.
 */
return function (): void {
    if (mig_has_table('user_profiles')) {
        mig_add_column('user_profiles', 'explore_mode', 'VARCHAR(12) NULL DEFAULT NULL');
        mig_add_column('user_profiles', 'elite_only', 'TINYINT(1) NOT NULL DEFAULT 0');
    }

    mig_exec("CREATE TABLE IF NOT EXISTS elite_profiles (
        user_id INT UNSIGNED NOT NULL,
        status VARCHAR(12) NOT NULL DEFAULT 'pending',
        headline VARCHAR(120) NOT NULL DEFAULT '',
        about TEXT NULL,
        industry VARCHAR(40) NOT NULL DEFAULT '',
        lifestyle VARCHAR(20) NOT NULL DEFAULT '',
        offers VARCHAR(255) NOT NULL DEFAULT '',
        seeking VARCHAR(255) NOT NULL DEFAULT '',
        criteria TEXT NULL,
        min_score TINYINT UNSIGNED NOT NULL DEFAULT 60,
        filter_on TINYINT(1) NOT NULL DEFAULT 1,
        hide_in_dating TINYINT(1) NOT NULL DEFAULT 1,
        share_phone TINYINT(1) NOT NULL DEFAULT 0,
        photos_after_accept TINYINT(1) NOT NULL DEFAULT 0,
        is_verified TINYINT(1) NOT NULL DEFAULT 0,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        paid_until DATETIME NULL DEFAULT NULL,
        views INT UNSIGNED NOT NULL DEFAULT 0,
        admin_note VARCHAR(255) NULL DEFAULT NULL,
        approved_at DATETIME NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        KEY idx_elite_status (status, is_featured)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mig_exec("CREATE TABLE IF NOT EXISTS elite_access (
        user_id INT UNSIGNED NOT NULL,
        credits SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        credits_until DATETIME NULL DEFAULT NULL,
        pass_until DATETIME NULL DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mig_exec("CREATE TABLE IF NOT EXISTS elite_unlocks (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        viewer_id INT UNSIGNED NOT NULL,
        owner_id INT UNSIGNED NOT NULL,
        source VARCHAR(10) NOT NULL DEFAULT 'single',
        payment_id INT UNSIGNED NULL DEFAULT NULL,
        expires_at DATETIME NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_elite_unlock (viewer_id, owner_id),
        KEY idx_elite_unlock_owner (owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mig_exec("CREATE TABLE IF NOT EXISTS elite_requests (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        from_id INT UNSIGNED NOT NULL,
        to_id INT UNSIGNED NOT NULL,
        message VARCHAR(600) NOT NULL DEFAULT '',
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        score TINYINT UNSIGNED NOT NULL DEFAULT 0,
        passed TINYINT(1) NOT NULL DEFAULT 1,
        detail VARCHAR(600) NULL DEFAULT NULL,
        seen_at DATETIME NULL DEFAULT NULL,
        responded_at DATETIME NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_elite_request (from_id, to_id),
        KEY idx_elite_req_to (to_id, status, passed),
        KEY idx_elite_req_from (from_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ---- landing block that shows a few Elite members (the admin can edit, move, hide or delete it) ----
    if (mig_has_table('landing_sections') && !val("SELECT id FROM landing_sections WHERE section_key = 'elite'")) {
        $after = (int)val("SELECT sort_order FROM landing_sections WHERE section_key IN ('agents_new', 'profiles') ORDER BY sort_order DESC LIMIT 1", [], 0);
        insert('landing_sections', ['section_key' => 'elite', 'title' => 'Elite', 'is_enabled' => 1, 'sort_order' => $after, 'content' => json_encode([
            'title' => 'The Elite circle', 'subtitle' => 'Established, successful people who know what they want. Verified profiles, and they choose who reaches them.',
            'count' => 6, 'cta_text' => 'Explore Elite', 'cta_url' => '/elite',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    // ---- menu links (only added once; the admin can rename, move or remove them under Navigation) ----
    if (mig_has_table('nav_items')) {
        foreach ([
            ['header', 'Elite', '/elite', 'gem', 'any', 3],
            ['app_sidebar', 'Elite', '/elite', 'gem', 'user', 8],
            ['footer_explore', 'Elite', '/elite', 'gem', 'any', 3],
        ] as [$menu, $label, $url, $icon, $aud, $sort]) {
            if (!val('SELECT id FROM nav_items WHERE menu = :m AND url = :u', ['m' => $menu, 'u' => $url])) {
                insert('nav_items', ['menu' => $menu, 'label' => $label, 'url' => $url, 'icon' => $icon, 'audience' => $aud, 'badge_key' => null, 'sort_order' => $sort, 'is_active' => 1]);
            }
        }
    }

    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
