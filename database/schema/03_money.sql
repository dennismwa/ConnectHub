-- ============================================================================
-- ConnectHub schema, part 3: plans, payments, boosts, gifts, ads, hosts
-- ============================================================================

CREATE TABLE IF NOT EXISTS subscription_plans (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(40) NOT NULL,
    name          VARCHAR(60) NOT NULL,
    tagline       VARCHAR(160) NULL,
    tier          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly  DECIMAL(10,2) NOT NULL DEFAULT 0,
    features      MEDIUMTEXT NOT NULL,
    badge_color   VARCHAR(9) NULL,
    badge_icon    VARCHAR(40) NULL,
    is_featured   TINYINT(1) NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plans_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_subscriptions (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED NOT NULL,
    plan_id        INT UNSIGNED NOT NULL,
    billing_period ENUM('monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
    status         ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    starts_at      DATETIME NOT NULL,
    expires_at     DATETIME NOT NULL,
    cancelled_at   DATETIME NULL,
    granted_by     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_subs_user (user_id, status, expires_at),
    KEY idx_subs_expires (status, expires_at),
    CONSTRAINT fk_subs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_subs_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payment_type: subscription | boost | gift | featured | booking | ad
-- payment_method: mpesa | bank_transfer | manual
CREATE TABLE IF NOT EXISTS payments (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED NOT NULL,
    payment_type        VARCHAR(20) NOT NULL,
    payment_method      VARCHAR(20) NOT NULL,
    amount              DECIMAL(10,2) NOT NULL,
    currency            VARCHAR(5) NOT NULL DEFAULT 'KES',
    status              ENUM('pending','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    reference           VARCHAR(40) NOT NULL,
    description         VARCHAR(255) NULL,
    metadata            TEXT NULL,
    mpesa_phone         VARCHAR(20) NULL,
    mpesa_checkout_id   VARCHAR(100) NULL,
    mpesa_merchant_id   VARCHAR(100) NULL,
    mpesa_receipt       VARCHAR(40) NULL,
    mpesa_result_desc   VARCHAR(255) NULL,
    bank_reference      VARCHAR(80) NULL,
    bank_paid_on        DATE NULL,
    admin_notes         VARCHAR(255) NULL,
    processed_by        INT UNSIGNED NULL,
    processed_at        DATETIME NULL,
    fulfilled_at        DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_payments_reference (reference),
    KEY idx_payments_user (user_id, created_at),
    KEY idx_payments_status (status, payment_method),
    KEY idx_payments_mpesa (mpesa_checkout_id),
    KEY idx_payments_created (created_at),
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boosts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    payment_id  INT UNSIGNED NULL,
    source      ENUM('plan','purchase','admin') NOT NULL DEFAULT 'plan',
    started_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NOT NULL,
    views_gained INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_boosts_user (user_id, expires_at),
    KEY idx_boosts_active (expires_at),
    CONSTRAINT fk_boosts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchased boosts that have not been activated yet
CREATE TABLE IF NOT EXISTS boost_credits (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    payment_id  INT UNSIGNED NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_boost_credits_user (user_id, used_at),
    CONSTRAINT fk_boost_credits_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gift_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL,
    icon        VARCHAR(40) NOT NULL DEFAULT 'gift',
    emoji       VARCHAR(16) NULL,
    price       DECIMAL(10,2) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sent_gifts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id    INT UNSIGNED NOT NULL,
    receiver_id  INT UNSIGNED NOT NULL,
    gift_type_id INT UNSIGNED NOT NULL,
    payment_id   INT UNSIGNED NULL,
    message      VARCHAR(200) NULL,
    is_seen      TINYINT(1) NOT NULL DEFAULT 0,
    status       ENUM('pending','delivered') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sent_gifts_receiver (receiver_id, status, is_seen),
    KEY idx_sent_gifts_sender (sender_id),
    CONSTRAINT fk_gifts_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_gifts_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_gifts_type FOREIGN KEY (gift_type_id) REFERENCES gift_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS advertisements (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    advertiser_name VARCHAR(100) NOT NULL,
    advertiser_email VARCHAR(190) NULL,
    advertiser_phone VARCHAR(25) NULL,
    title           VARCHAR(160) NOT NULL,
    description     VARCHAR(300) NULL,
    image_path      VARCHAR(255) NULL,
    link_url        VARCHAR(500) NOT NULL,
    cta_text        VARCHAR(40) NOT NULL DEFAULT 'Learn more',
    ad_type         ENUM('banner','card','popup') NOT NULL DEFAULT 'card',
    placement       ENUM('discover','search','landing','hosts','messages','global') NOT NULL DEFAULT 'discover',
    starts_at       DATETIME NOT NULL,
    expires_at      DATETIME NOT NULL,
    max_views       INT UNSIGNED NULL,
    max_clicks      INT UNSIGNED NULL,
    views           INT UNSIGNED NOT NULL DEFAULT 0,
    clicks          INT UNSIGNED NOT NULL DEFAULT 0,
    amount_paid     DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('pending','active','paused','rejected') NOT NULL DEFAULT 'pending',
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ads_serve (status, placement, starts_at, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Hosts (companions): categories, services, profiles, bookings, reviews, payouts
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS host_categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(60) NOT NULL,
    icon        VARCHAR(40) NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_host_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_services (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80) NOT NULL,
    description VARCHAR(200) NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_host_services_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_profiles (
    user_id            INT UNSIGNED NOT NULL,
    category_id        INT UNSIGNED NULL,
    display_name       VARCHAR(100) NOT NULL,
    tagline            VARCHAR(120) NULL,
    about              TEXT NULL,
    hourly_rate        DECIMAL(10,2) NULL,
    half_day_rate      DECIMAL(10,2) NULL,
    full_day_rate      DECIMAL(10,2) NULL,
    min_hours          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    travel_available   TINYINT(1) NOT NULL DEFAULT 0,
    travel_fee         DECIMAL(10,2) NULL,
    service_ids        VARCHAR(255) NULL,
    service_area_ids   VARCHAR(255) NULL,
    weekly_availability TEXT NULL,
    banner_path        VARCHAR(255) NULL,
    approval_status    ENUM('draft','pending','approved','rejected','suspended') NOT NULL DEFAULT 'draft',
    approval_note      VARCHAR(255) NULL,
    approved_by        INT UNSIGNED NULL,
    approved_at        DATETIME NULL,
    is_featured        TINYINT(1) NOT NULL DEFAULT 0,
    id_verified        TINYINT(1) NOT NULL DEFAULT 0,
    total_bookings     INT UNSIGNED NOT NULL DEFAULT 0,
    total_earned       DECIMAL(12,2) NOT NULL DEFAULT 0,
    rating_avg         DECIMAL(3,2) NOT NULL DEFAULT 0,
    review_count       INT UNSIGNED NOT NULL DEFAULT 0,
    profile_views      INT UNSIGNED NOT NULL DEFAULT 0,
    submitted_at       DATETIME NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    KEY idx_host_profiles_status (approval_status, is_featured),
    KEY idx_host_profiles_rating (rating_avg),
    CONSTRAINT fk_host_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_host_profiles_cat FOREIGN KEY (category_id) REFERENCES host_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_blocked_dates (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    host_id     INT UNSIGNED NOT NULL,
    blocked_on  DATE NOT NULL,
    reason      VARCHAR(120) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_host_blocked (host_id, blocked_on),
    CONSTRAINT fk_host_blocked_host FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_bookings (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    host_id          INT UNSIGNED NOT NULL,
    client_id        INT UNSIGNED NOT NULL,
    booking_date     DATE NOT NULL,
    start_time       TIME NOT NULL,
    duration_hours   DECIMAL(4,1) NOT NULL,
    service_ids      VARCHAR(255) NULL,
    location_note    VARCHAR(200) NULL,
    client_note      VARCHAR(500) NULL,
    host_note        VARCHAR(500) NULL,
    total_amount     DECIMAL(10,2) NOT NULL,
    deposit_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    commission_pct   DECIMAL(5,2) NOT NULL DEFAULT 0,
    commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    host_earning     DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_id       INT UNSIGNED NULL,
    status           ENUM('awaiting_payment','pending','confirmed','completed','cancelled','declined','no_show') NOT NULL DEFAULT 'awaiting_payment',
    cancelled_by     ENUM('client','host','admin') NULL,
    cancel_reason    VARCHAR(255) NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_host (host_id, booking_date, status),
    KEY idx_bookings_client (client_id, status),
    KEY idx_bookings_status (status, booking_date),
    CONSTRAINT fk_bookings_host FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_reviews (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    host_id       INT UNSIGNED NOT NULL,
    reviewer_id   INT UNSIGNED NOT NULL,
    booking_id    INT UNSIGNED NOT NULL,
    rating        TINYINT UNSIGNED NOT NULL,
    review_text   VARCHAR(1000) NULL,
    is_anonymous  TINYINT(1) NOT NULL DEFAULT 1,
    status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    host_reply    VARCHAR(500) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_host_reviews_booking (booking_id),
    KEY idx_host_reviews_host (host_id, status),
    CONSTRAINT fk_host_reviews_host FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_host_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_host_reviews_booking FOREIGN KEY (booking_id) REFERENCES host_bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_payouts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    host_id      INT UNSIGNED NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    method       VARCHAR(20) NOT NULL DEFAULT 'mpesa',
    destination  VARCHAR(80) NOT NULL,
    status       ENUM('requested','paid','rejected') NOT NULL DEFAULT 'requested',
    admin_note   VARCHAR(255) NULL,
    processed_by INT UNSIGNED NULL,
    processed_at DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_host_payouts_host (host_id, status),
    CONSTRAINT fk_host_payouts_host FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
