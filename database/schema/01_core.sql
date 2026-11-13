-- ============================================================================
-- ConnectHub schema, part 1: configuration, CMS, users, profiles, locations
-- Portable: MySQL 5.7+/8.x and MariaDB 10.3+. No spatial/SRID columns, no
-- generated columns with non-deterministic functions (distance is computed with
-- a bounding box + Haversine in SQL, age is computed from date_of_birth).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Settings (every admin-controlled option lives here; defaults come from
-- includes/settings_schema.php, rows only exist once an admin saves a value)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key   VARCHAR(100) NOT NULL,
    setting_value MEDIUMTEXT NULL,
    updated_by    INT UNSIGNED NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Admin roles (permission matrix: module => none|view|edit|full)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(40) NOT NULL,
    name        VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    permissions MEDIUMTEXT NOT NULL,
    is_system   TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Locations: Kenyan counties + major towns (admin can add, edit, hide)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS locations (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type        ENUM('county','town') NOT NULL DEFAULT 'town',
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    parent_id   INT UNSIGNED NULL,
    county_code TINYINT UNSIGNED NULL,
    region      VARCHAR(40) NULL,
    latitude    DECIMAL(9,6) NOT NULL,
    longitude   DECIMAL(9,6) NOT NULL,
    is_popular  TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_locations_slug (slug),
    KEY idx_locations_type (type, is_active),
    KEY idx_locations_parent (parent_id),
    KEY idx_locations_popular (is_popular, sort_order),
    KEY idx_locations_geo (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Users
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email               VARCHAR(190) NOT NULL,
    phone               VARCHAR(25) NULL,
    password_hash       VARCHAR(255) NOT NULL,
    role                ENUM('user','host','admin') NOT NULL DEFAULT 'user',
    admin_role_id       INT UNSIGNED NULL,
    status              ENUM('active','pending','suspended','banned','deactivated') NOT NULL DEFAULT 'active',
    status_reason       VARCHAR(255) NULL,
    email_verified_at   DATETIME NULL,
    last_login_at       DATETIME NULL,
    last_active_at      DATETIME NULL,
    last_ip             VARCHAR(45) NULL,
    login_attempts      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until        DATETIME NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    totp_secret         VARCHAR(64) NULL,
    totp_enabled        TINYINT(1) NOT NULL DEFAULT 0,
    featured_until      DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status (status),
    KEY idx_users_role (role),
    KEY idx_users_active (last_active_at),
    KEY idx_users_created (created_at),
    KEY idx_users_featured (featured_until),
    CONSTRAINT fk_users_admin_role FOREIGN KEY (admin_role_id) REFERENCES admin_roles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generic one-time tokens: password reset, email verification, remember-me
CREATE TABLE IF NOT EXISTS user_tokens (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(20) NOT NULL,
    selector    VARCHAR(32) NOT NULL,
    token_hash  VARCHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_tokens_selector (selector),
    KEY idx_user_tokens_user (user_id, type),
    CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Profiles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_profiles (
    user_id              INT UNSIGNED NOT NULL,
    display_name         VARCHAR(100) NOT NULL DEFAULT '',
    date_of_birth        DATE NULL,
    gender               ENUM('male','female','non-binary','other') NULL,
    interested_in        ENUM('male','female','everyone') NOT NULL DEFAULT 'everyone',
    bio                  TEXT NULL,
    occupation           VARCHAR(150) NULL,
    education            VARCHAR(150) NULL,
    height_cm            SMALLINT UNSIGNED NULL,
    body_type            ENUM('slim','athletic','average','curvy','plus-size') NULL,
    smoking              ENUM('never','occasionally','regularly') NULL,
    drinking             ENUM('never','socially','regularly') NULL,
    relationship_status  ENUM('single','divorced','widowed','separated','complicated') NULL,
    children             ENUM('none','have_at_home','have_away') NULL,
    want_children        ENUM('yes','no','maybe') NULL,
    religion             VARCHAR(50) NULL,
    languages            VARCHAR(255) NULL,
    looking_for          VARCHAR(255) NULL,
    intent               VARCHAR(30) NULL,
    contact_phone        VARCHAR(25) NULL,
    contact_whatsapp     VARCHAR(25) NULL,
    contact_email        VARCHAR(190) NULL,
    contact_instagram    VARCHAR(100) NULL,
    profile_completion   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_verified          TINYINT(1) NOT NULL DEFAULT 0,
    verified_at          DATETIME NULL,
    onboarding_completed TINYINT(1) NOT NULL DEFAULT 0,
    onboarding_step      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    -- privacy
    show_online          TINYINT(1) NOT NULL DEFAULT 1,
    show_last_active     TINYINT(1) NOT NULL DEFAULT 1,
    show_distance        TINYINT(1) NOT NULL DEFAULT 1,
    show_age             TINYINT(1) NOT NULL DEFAULT 1,
    show_contact         TINYINT(1) NOT NULL DEFAULT 0,
    allow_visits_log     TINYINT(1) NOT NULL DEFAULT 1,
    is_paused            TINYINT(1) NOT NULL DEFAULT 0,
    is_incognito         TINYINT(1) NOT NULL DEFAULT 0,
    read_receipts        TINYINT(1) NOT NULL DEFAULT 1,
    theme_pref           VARCHAR(10) NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    KEY idx_profiles_dob (date_of_birth),
    KEY idx_profiles_gender (gender),
    KEY idx_profiles_onboarded (onboarding_completed, is_paused),
    CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_locations (
    user_id      INT UNSIGNED NOT NULL,
    latitude     DECIMAL(9,6) NOT NULL,
    longitude    DECIMAL(9,6) NOT NULL,
    location_id  INT UNSIGNED NULL,
    county_id    INT UNSIGNED NULL,
    town_name    VARCHAR(100) NULL,
    county_name  VARCHAR(100) NULL,
    country      VARCHAR(80) NOT NULL DEFAULT 'Kenya',
    source       ENUM('gps','manual') NOT NULL DEFAULT 'manual',
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    KEY idx_user_locations_geo (latitude, longitude),
    KEY idx_user_locations_town (location_id),
    KEY idx_user_locations_county (county_id),
    CONSTRAINT fk_user_locations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Photos: originals are NOT kept (EXIF/GPS stripped on re-encode). Every photo
-- has a guest-safe blurred variant that is served instead of the real image
-- whenever the admin's blur rules apply to the viewer.
CREATE TABLE IF NOT EXISTS user_photos (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,
    large_path        VARCHAR(255) NOT NULL,
    medium_path       VARCHAR(255) NOT NULL,
    thumb_path        VARCHAR(255) NOT NULL,
    blur_path         VARCHAR(255) NOT NULL,
    blur_data_uri     TEXT NULL,
    width             SMALLINT UNSIGNED NULL,
    height            SMALLINT UNSIGNED NULL,
    file_size         INT UNSIGNED NULL,
    is_primary        TINYINT(1) NOT NULL DEFAULT 0,
    is_vip_only       TINYINT(1) NOT NULL DEFAULT 0,
    sort_order        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    moderation_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    rejection_reason  VARCHAR(255) NULL,
    moderated_by      INT UNSIGNED NULL,
    moderated_at      DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_photos_user (user_id, is_primary, sort_order),
    KEY idx_photos_moderation (moderation_status, created_at),
    CONSTRAINT fk_photos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_videos (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,
    video_path        VARCHAR(255) NOT NULL,
    poster_path       VARCHAR(255) NULL,
    duration_seconds  SMALLINT UNSIGNED NULL,
    file_size         INT UNSIGNED NULL,
    moderation_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_videos_user (user_id),
    KEY idx_videos_moderation (moderation_status),
    CONSTRAINT fk_videos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Interests
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS interests (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL,
    icon        VARCHAR(50) NULL,
    emoji       VARCHAR(16) NULL,
    category    VARCHAR(50) NOT NULL DEFAULT 'General',
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_interests_name (name),
    KEY idx_interests_cat (category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_interests (
    user_id     INT UNSIGNED NOT NULL,
    interest_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, interest_id),
    KEY idx_user_interests_interest (interest_id),
    CONSTRAINT fk_ui_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ui_interest FOREIGN KEY (interest_id) REFERENCES interests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS discovery_preferences (
    user_id              INT UNSIGNED NOT NULL,
    age_min              TINYINT UNSIGNED NOT NULL DEFAULT 18,
    age_max              TINYINT UNSIGNED NOT NULL DEFAULT 45,
    distance_km          SMALLINT UNSIGNED NOT NULL DEFAULT 50,
    show_verified_only   TINYINT(1) NOT NULL DEFAULT 0,
    show_photos_only     TINYINT(1) NOT NULL DEFAULT 1,
    hide_swiped          TINYINT(1) NOT NULL DEFAULT 1,
    preferred_view       VARCHAR(10) NULL,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_discovery_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- CMS: hero slides, landing sections, menus, testimonials, FAQs, pages, blog
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hero_slides (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    image_path     VARCHAR(255) NOT NULL,
    mobile_path    VARCHAR(255) NULL,
    alt_text       VARCHAR(160) NULL,
    overlay_opacity TINYINT UNSIGNED NULL,
    sort_order     INT NOT NULL DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hero_slides_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per landing-page block. `content` holds the block's editable copy as JSON.
CREATE TABLE IF NOT EXISTS landing_sections (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    section_key VARCHAR(40) NOT NULL,
    title       VARCHAR(120) NOT NULL,
    is_enabled  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT NOT NULL DEFAULT 0,
    content     MEDIUMTEXT NULL,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_landing_sections_key (section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Editable menus (header, footer columns, mobile tab bar, side panel)
CREATE TABLE IF NOT EXISTS nav_items (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    menu        VARCHAR(30) NOT NULL,
    label       VARCHAR(60) NOT NULL,
    url         VARCHAR(255) NOT NULL,
    icon        VARCHAR(40) NULL,
    audience    ENUM('any','guest','user','host','premium') NOT NULL DEFAULT 'any',
    badge_key   VARCHAR(30) NULL,
    new_tab     TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_nav_menu (menu, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(80) NOT NULL,
    location     VARCHAR(80) NULL,
    quote        TEXT NOT NULL,
    rating       TINYINT UNSIGNED NOT NULL DEFAULT 5,
    photo_path   VARCHAR(255) NULL,
    show_landing TINYINT(1) NOT NULL DEFAULT 1,
    show_auth    TINYINT(1) NOT NULL DEFAULT 1,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    sort_order   INT NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category    VARCHAR(60) NOT NULL DEFAULT 'General',
    question    VARCHAR(255) NOT NULL,
    answer      TEXT NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_faqs_cat (category, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title            VARCHAR(200) NOT NULL,
    slug             VARCHAR(150) NOT NULL,
    content          MEDIUMTEXT NULL,
    meta_title       VARCHAR(200) NULL,
    meta_description VARCHAR(300) NULL,
    show_in_footer   TINYINT(1) NOT NULL DEFAULT 0,
    is_published     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order       INT NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80) NOT NULL,
    slug        VARCHAR(100) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id      INT UNSIGNED NULL,
    author_id        INT UNSIGNED NULL,
    title            VARCHAR(200) NOT NULL,
    slug             VARCHAR(220) NOT NULL,
    excerpt          VARCHAR(400) NULL,
    content          MEDIUMTEXT NULL,
    cover_path       VARCHAR(255) NULL,
    meta_title       VARCHAR(200) NULL,
    meta_description VARCHAR(300) NULL,
    status           ENUM('draft','published') NOT NULL DEFAULT 'draft',
    views            INT UNSIGNED NOT NULL DEFAULT 0,
    published_at     DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_blog_posts_slug (slug),
    KEY idx_blog_posts_status (status, published_at),
    KEY idx_blog_posts_cat (category_id),
    CONSTRAINT fk_blog_posts_cat FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(190) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    status      ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contact_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suggestion chips shown in onboarding step 3 (tap to add to bio)
CREATE TABLE IF NOT EXISTS bio_suggestions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    text        VARCHAR(120) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ice_breakers (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    text        VARCHAR(200) NOT NULL,
    category    VARCHAR(30) NOT NULL DEFAULT 'casual',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Infrastructure: rate limits, daily usage counters, analytics counters
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    bucket      VARCHAR(64) NOT NULL,
    window_start INT UNSIGNED NOT NULL,
    hits        INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (bucket, window_start),
    KEY idx_rate_limits_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS daily_usage (
    user_id          INT UNSIGNED NOT NULL,
    usage_date       DATE NOT NULL,
    swipes_used      INT UNSIGNED NOT NULL DEFAULT 0,
    superlikes_used  INT UNSIGNED NOT NULL DEFAULT 0,
    messages_sent    INT UNSIGNED NOT NULL DEFAULT 0,
    rewinds_used     INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, usage_date),
    KEY idx_daily_usage_date (usage_date),
    CONSTRAINT fk_daily_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_daily (
    stat_date   DATE NOT NULL,
    metric      VARCHAR(40) NOT NULL,
    value       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (stat_date, metric)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_activity_log (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id    INT UNSIGNED NULL,
    action      VARCHAR(80) NOT NULL,
    entity_type VARCHAR(40) NULL,
    entity_id   INT UNSIGNED NULL,
    details     TEXT NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_log_admin (admin_id, created_at),
    KEY idx_admin_log_entity (entity_type, entity_id),
    KEY idx_admin_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
