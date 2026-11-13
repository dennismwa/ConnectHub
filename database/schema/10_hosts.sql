-- ============================================================================
-- ConnectHub schema, part 10: Hosts module additions.
-- Idempotent: only CREATE TABLE IF NOT EXISTS (works on MySQL 5.7+/8.x and MariaDB 10.3+).
-- The base host_* tables in 03_money.sql are left untouched; everything extra lives here.
--
--   host_profile_ext    one row per host: instant booking, pause switch, payout details,
--                       private ID/selfie file names (inside storage/private/host_id), rule agreement
--   host_offerings      a host's own packages: name, duration (hours), price (KES)
--   host_booking_ext    per booking: package snapshot, accept deadline, timestamps, refund and settlement
--   host_booking_events timeline shown on booking pages (created, paid, accepted, cancelled, ...)
-- ============================================================================

CREATE TABLE IF NOT EXISTS host_profile_ext (
    user_id                 INT UNSIGNED NOT NULL,
    instant_booking         TINYINT(1) NOT NULL DEFAULT 0,
    bookings_paused         TINYINT(1) NOT NULL DEFAULT 0,
    allow_custom_hours      TINYINT(1) NOT NULL DEFAULT 1,
    max_hours               DECIMAL(4,1) NULL,
    payout_phone            VARCHAR(25) NULL,
    payout_name             VARCHAR(100) NULL,
    id_doc_type             VARCHAR(20) NULL,
    id_doc_path             VARCHAR(255) NULL,
    selfie_path             VARCHAR(255) NULL,
    id_submitted_at         DATETIME NULL,
    rules_agreed_at         DATETIME NULL,
    rules_agreed_ip         VARCHAR(45) NULL,
    notify_email_bookings   TINYINT(1) NOT NULL DEFAULT 1,
    notify_email_reminders  TINYINT(1) NOT NULL DEFAULT 1,
    notify_email_reviews    TINYINT(1) NOT NULL DEFAULT 1,
    deactivate_requested_at DATETIME NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_host_ext_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_offerings (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    host_id        INT UNSIGNED NOT NULL,
    service_id     INT UNSIGNED NULL,
    name           VARCHAR(80) NOT NULL,
    description    VARCHAR(255) NULL,
    duration_hours DECIMAL(4,1) NOT NULL,
    price          DECIMAL(10,2) NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    sort_order     INT NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_host_offerings_host (host_id, is_active, sort_order),
    CONSTRAINT fk_host_offerings_host FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_booking_ext (
    booking_id          INT UNSIGNED NOT NULL,
    offering_id         INT UNSIGNED NULL,
    offering_name       VARCHAR(80) NULL,
    area_id             INT UNSIGNED NULL,
    travel_fee          DECIMAL(10,2) NOT NULL DEFAULT 0,
    base_amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    accept_by           DATETIME NULL,
    paid_at             DATETIME NULL,
    confirmed_at        DATETIME NULL,
    completed_at        DATETIME NULL,
    closed_at           DATETIME NULL,
    reminder_sent_at    DATETIME NULL,
    client_confirmed_at DATETIME NULL,
    refund_pct          DECIMAL(5,2) NULL,
    refund_amount       DECIMAL(10,2) NOT NULL DEFAULT 0,
    refund_status       VARCHAR(12) NOT NULL DEFAULT 'none',
    settled_amount      DECIMAL(10,2) NULL,
    settled_at          DATETIME NULL,
    PRIMARY KEY (booking_id),
    KEY idx_host_booking_ext_accept (accept_by),
    KEY idx_host_booking_ext_settled (settled_at),
    KEY idx_host_booking_ext_refund (refund_status),
    CONSTRAINT fk_host_booking_ext_booking FOREIGN KEY (booking_id) REFERENCES host_bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS host_booking_events (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id  INT UNSIGNED NOT NULL,
    actor_id    INT UNSIGNED NULL,
    actor_role  VARCHAR(10) NULL,
    event       VARCHAR(30) NOT NULL,
    note        VARCHAR(255) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_host_booking_events (booking_id, id),
    CONSTRAINT fk_host_booking_events_booking FOREIGN KEY (booking_id) REFERENCES host_bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
