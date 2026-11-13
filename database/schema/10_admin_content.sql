-- ============================================================================
-- Admin content + money module (D2). Idempotent: safe to import more than once,
-- on MySQL 5.7+/8.x and MariaDB 10.3+. Column additions are guarded with an
-- information_schema check, so no "ADD COLUMN IF NOT EXISTS" syntax is needed.
-- ============================================================================

-- Hero slides: per-slide copy, optional video, focal point and a schedule window.
SET @ch_sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE hero_slides
        ADD COLUMN headline VARCHAR(160) NULL AFTER alt_text,
        ADD COLUMN subheadline VARCHAR(300) NULL AFTER headline,
        ADD COLUMN cta_text VARCHAR(40) NULL AFTER subheadline,
        ADD COLUMN cta_url VARCHAR(255) NULL AFTER cta_text,
        ADD COLUMN video_path VARCHAR(255) NULL AFTER mobile_path,
        ADD COLUMN focal_x TINYINT UNSIGNED NOT NULL DEFAULT 50,
        ADD COLUMN focal_y TINYINT UNSIGNED NOT NULL DEFAULT 50,
        ADD COLUMN starts_at DATETIME NULL,
        ADD COLUMN ends_at DATETIME NULL',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hero_slides' AND COLUMN_NAME = 'headline');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

-- Blog: tags. A "scheduled" post is a published post whose published_at is still in the future,
-- so the public queries (status = 'published' AND published_at <= NOW()) need no cron job.
SET @ch_sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE blog_posts ADD COLUMN tags VARCHAR(255) NULL AFTER excerpt',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog_posts' AND COLUMN_NAME = 'tags');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

-- CMS pages: share image.
SET @ch_sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE pages ADD COLUMN og_image VARCHAR(255) NULL AFTER meta_description',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pages' AND COLUMN_NAME = 'og_image');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

-- Contact inbox: spam status and the saved reply.
ALTER TABLE contact_messages MODIFY status ENUM('new','read','replied','archived','spam') NOT NULL DEFAULT 'new';

SET @ch_sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE contact_messages
        ADD COLUMN reply_text TEXT NULL,
        ADD COLUMN replied_at DATETIME NULL,
        ADD COLUMN replied_by INT UNSIGNED NULL',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_messages' AND COLUMN_NAME = 'reply_text');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

-- Newsletter: unsubscribe token, where the address came from, when they left.
SET @ch_sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE newsletter_subscribers
        ADD COLUMN token VARCHAR(40) NULL,
        ADD COLUMN source VARCHAR(30) NULL,
        ADD COLUMN unsubscribed_at DATETIME NULL,
        ADD UNIQUE KEY uq_newsletter_token (token)',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'newsletter_subscribers' AND COLUMN_NAME = 'token');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

-- Payouts: M-Pesa confirmation code entered by the admin who paid it.
SET @ch_sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE host_payouts ADD COLUMN payment_reference VARCHAR(60) NULL AFTER admin_note',
    'DO 0') FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'host_payouts' AND COLUMN_NAME = 'payment_reference');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

-- Adverts: the public browse pages (/browse) ask for their own slot. Re-running this is harmless.
ALTER TABLE advertisements MODIFY placement ENUM('discover','search','landing','hosts','messages','browse','global') NOT NULL DEFAULT 'discover';

-- Newsletter campaigns (sent in batches from /admin/newsletter, no queue worker needed).
CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject          VARCHAR(200) NOT NULL,
    body_html        MEDIUMTEXT NOT NULL,
    status           ENUM('draft','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
    total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
    sent_count       INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count     INT UNSIGNED NOT NULL DEFAULT 0,
    last_subscriber_id INT UNSIGNED NOT NULL DEFAULT 0,
    created_by       INT UNSIGNED NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at       DATETIME NULL,
    finished_at      DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_newsletter_campaigns_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
