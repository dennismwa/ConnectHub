<?php
/**
 * Promotions: advertisers buy a package on the public Advertise page, pay at the end, and the picture runs for the
 * package's length of time. Adds the packages table, the columns a paid advert needs, an "unpaid" draft status, the
 * Promotions block for the landing page and the settings. Nothing saved is changed or removed.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS ad_packages (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(60) NOT NULL,
        tagline     VARCHAR(140) NULL,
        days        INT UNSIGNED NOT NULL DEFAULT 7,
        price       DECIMAL(10,2) NOT NULL DEFAULT 0,
        placements  VARCHAR(120) NOT NULL DEFAULT 'landing,discover',
        is_popular  TINYINT(1) NOT NULL DEFAULT 0,
        is_active   TINYINT(1) NOT NULL DEFAULT 1,
        sort_order  INT NOT NULL DEFAULT 0,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_ad_packages_show (is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (mig_has_table('advertisements')) {
        $type = (string)val("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'advertisements' AND column_name = 'status'", [], '');
        if ($type !== '' && stripos($type, "'unpaid'") === false) {
            db()->exec("ALTER TABLE `advertisements` MODIFY `status` ENUM('unpaid','pending','active','paused','rejected') NOT NULL DEFAULT 'pending'");
        }
        mig_add_column('advertisements', 'user_id', 'INT UNSIGNED NULL DEFAULT NULL');
        mig_add_column('advertisements', 'package_id', 'INT UNSIGNED NULL DEFAULT NULL');
        mig_add_column('advertisements', 'duration_days', 'INT UNSIGNED NULL DEFAULT NULL');
        mig_add_column('advertisements', 'payment_id', 'INT UNSIGNED NULL DEFAULT NULL');
        mig_add_column('advertisements', 'paid_at', 'DATETIME NULL DEFAULT NULL');
        mig_add_column('advertisements', 'draft_token', 'VARCHAR(32) NULL DEFAULT NULL');
        mig_add_column('advertisements', 'placements', 'VARCHAR(120) NULL DEFAULT NULL');
        mig_add_column('advertisements', 'review_note', 'VARCHAR(255) NULL DEFAULT NULL');
        mig_add_index('advertisements', 'idx_ads_owner', 'user_id, status');
        mig_add_index('advertisements', 'idx_ads_draft', 'draft_token');
    }

    // Three packages to start with, only when the admin has none. They can be renamed, repriced or removed under Adverts > Packages.
    if ((int)val('SELECT COUNT(*) FROM ad_packages', [], 0) === 0) {
        $seed = [
            ['Starter', 'A week in the feed, to try it out.', 7, 500, 'discover,browse', 0],
            ['Growth', 'Two weeks on the home page and in the feed.', 14, 1200, 'landing,discover,browse', 1],
            ['Spotlight', 'A month everywhere people look.', 30, 2500, 'landing,discover,search,browse,hosts', 0],
        ];
        foreach ($seed as $i => [$name, $tag, $days, $price, $where, $pop]) {
            insert('ad_packages', ['name' => $name, 'tagline' => $tag, 'days' => $days, 'price' => $price, 'placements' => $where, 'is_popular' => $pop, 'is_active' => 1, 'sort_order' => $i + 1]);
        }
    }

    // The Promotions block on the landing page. It prints nothing while no advert is running, so an empty site shows no gap.
    if (mig_has_table('landing_sections') && (int)val("SELECT COUNT(*) FROM landing_sections WHERE section_key = 'promos'", [], 0) === 0) {
        $after = (int)val("SELECT sort_order FROM landing_sections WHERE section_key = 'features'", [], 0);
        $pos = $after > 0 ? $after + 1 : ((int)val('SELECT COALESCE(MAX(sort_order), 0) FROM landing_sections', [], 0) + 1);
        q('UPDATE landing_sections SET sort_order = sort_order + 1 WHERE sort_order >= :p', ['p' => $pos]);
        insert('landing_sections', [
            'section_key' => 'promos', 'title' => 'Promotions', 'is_enabled' => 1, 'sort_order' => $pos,
            'content' => json_encode(['title' => 'Worth a look', 'subtitle' => 'Offers and news from businesses near you.', 'count' => 6, 'cta_text' => 'Advertise with us', 'cta_url' => '/advertise'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    // A footer link to the Advertise page, added once. The admin can rename or remove it under Navigation.
    if (mig_has_table('nav_items') && !val("SELECT id FROM nav_items WHERE menu = 'footer_company' AND url = '/advertise'", [], 0)) {
        $sort = (int)val("SELECT COALESCE(MAX(sort_order), 0) FROM nav_items WHERE menu = 'footer_company'", [], 0) + 1;
        insert('nav_items', ['menu' => 'footer_company', 'label' => 'Advertise', 'url' => '/advertise', 'icon' => 'megaphone', 'audience' => 'any', 'badge_key' => null, 'sort_order' => $sort, 'is_active' => 1]);
    }

    foreach ([
        'promo_enabled' => '1',       // the public Advertise page and its footer link
        'promo_review' => '1',        // a paid advert waits for an admin to approve it before it shows
        'admin_alert_advert' => '1',  // email when a paid advert waits for approval
        'promo_max_shown' => '6',     // adverts in one block at most
        'promo_rules' => "No adult services, escorts or sexual content.\nNothing illegal, misleading or unsafe.\nThe picture must be yours to use, and sharp enough to look good small.\nThe link must go to a real website, shop or social page.\nWe may decline or remove an advert that breaks these rules, and refund it if it never ran.",
    ] as $k => $v) {
        mig_setting_default($k, $v);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
