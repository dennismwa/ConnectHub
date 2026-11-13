<?php
/**
 * SEO: per-page search titles/descriptions, a redirect manager with a 404 log, extra blog fields (focus keyword, FAQ, social image,
 * canonical, noindex) and a description for each blog category.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS seo_pages (
        page_key     VARCHAR(40) NOT NULL,
        title        VARCHAR(120) NULL,
        description  VARCHAR(300) NULL,
        og_image     VARCHAR(255) NULL,
        noindex      TINYINT(1) NOT NULL DEFAULT 0,
        updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (page_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS seo_redirects (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        from_path   VARCHAR(255) NOT NULL,
        to_url      VARCHAR(500) NOT NULL,
        code        SMALLINT UNSIGNED NOT NULL DEFAULT 301,
        hits        INT UNSIGNED NOT NULL DEFAULT 0,
        is_active   TINYINT(1) NOT NULL DEFAULT 1,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_seo_redirect_from (from_path)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS seo_404 (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        path        VARCHAR(255) NOT NULL,
        hits        INT UNSIGNED NOT NULL DEFAULT 1,
        referrer    VARCHAR(255) NULL,
        first_seen  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_seen   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_seo_404_path (path),
        KEY idx_seo_404_hits (hits)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    mig_add_column('blog_posts', 'focus_keyword', 'VARCHAR(80) NULL AFTER meta_description');
    mig_add_column('blog_posts', 'faq', 'MEDIUMTEXT NULL AFTER focus_keyword');
    mig_add_column('blog_posts', 'og_image', 'VARCHAR(255) NULL AFTER faq');
    mig_add_column('blog_posts', 'canonical_url', 'VARCHAR(300) NULL AFTER og_image');
    mig_add_column('blog_posts', 'noindex', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER canonical_url');
    mig_add_column('blog_posts', 'cover_alt', 'VARCHAR(160) NULL AFTER cover_path');
    mig_add_column('locations', 'seo_intro', 'TEXT NULL');
    mig_add_column('blog_categories', 'description', 'VARCHAR(400) NULL');
    mig_add_column('blog_categories', 'meta_title', 'VARCHAR(160) NULL');

    foreach ([
        'seo_title_template' => '%page% · %site%', 'seo_default_description' => '', 'seo_org_legal_name' => '', 'seo_org_phone' => '', 'seo_org_email' => '',
        'seo_org_city' => 'Nairobi', 'seo_org_region' => 'Nairobi County', 'seo_org_country' => 'KE', 'seo_locale' => 'en_KE',
        'seo_google_verification' => '', 'seo_bing_verification' => '', 'seo_yandex_verification' => '', 'seo_pinterest_verification' => '', 'seo_facebook_verification' => '',
        'seo_gtm_id' => '', 'seo_indexnow_enabled' => '1', 'seo_indexnow_key' => '', 'seo_force_https' => '0', 'seo_www' => 'keep',
        'seo_sitemap_images' => '1', 'seo_sitemap_locations' => '1', 'seo_sitemap_hosts' => '1', 'seo_ai_crawlers' => 'allow',
        'seo_schema_org' => '1', 'seo_schema_breadcrumbs' => '1', 'seo_schema_sitelinks' => '1', 'seo_blog_author' => '',
        'seo_noindex_members' => '1', 'seo_log_404' => '1', 'seo_rss_enabled' => '1',
    ] as $k => $v) {
        mig_setting_default($k, $v);
    }
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
