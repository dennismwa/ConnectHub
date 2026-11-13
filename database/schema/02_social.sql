-- ============================================================================
-- ConnectHub schema, part 2: discovery, matching, messaging, notifications, safety
-- ============================================================================

CREATE TABLE IF NOT EXISTS swipes (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    swiper_id   INT UNSIGNED NOT NULL,
    swiped_id   INT UNSIGNED NOT NULL,
    direction   ENUM('like','nope','superlike') NOT NULL,
    dwell_ms    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_swipes_pair (swiper_id, swiped_id),
    KEY idx_swipes_target (swiped_id, direction, created_at),
    KEY idx_swipes_created (created_at),
    CONSTRAINT fk_swipes_swiper FOREIGN KEY (swiper_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_swipes_swiped FOREIGN KEY (swiped_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- user1_id is always the smaller id of the pair
CREATE TABLE IF NOT EXISTS matches (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user1_id        INT UNSIGNED NOT NULL,
    user2_id        INT UNSIGNED NOT NULL,
    matched_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    unmatched_by    INT UNSIGNED NULL,
    unmatched_at    DATETIME NULL,
    user1_seen      TINYINT(1) NOT NULL DEFAULT 0,
    user2_seen      TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_matches_pair (user1_id, user2_id),
    KEY idx_matches_u1 (user1_id, is_active),
    KEY idx_matches_u2 (user2_id, is_active),
    CONSTRAINT fk_matches_u1 FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_matches_u2 FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS profile_views (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    viewer_id       INT UNSIGNED NOT NULL,
    viewed_id       INT UNSIGNED NOT NULL,
    view_count      INT UNSIGNED NOT NULL DEFAULT 1,
    dwell_ms        INT UNSIGNED NOT NULL DEFAULT 0,
    scroll_depth    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    photos_viewed   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    first_viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_viewed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_profile_views_pair (viewer_id, viewed_id),
    KEY idx_profile_views_viewed (viewed_id, last_viewed_at),
    CONSTRAINT fk_pv_viewer FOREIGN KEY (viewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pv_viewed FOREIGN KEY (viewed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorites (
    user_id     INT UNSIGNED NOT NULL,
    target_id   INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, target_id),
    KEY idx_favorites_target (target_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_target FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_history (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    query_text  VARCHAR(160) NULL,
    filters     TEXT NULL,
    results     INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_search_history_user (user_id, created_at),
    CONSTRAINT fk_search_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS algorithm_scores (
    user_id        INT UNSIGNED NOT NULL,
    target_id      INT UNSIGNED NOT NULL,
    interest_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    location_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    demographic_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    dwell_score    DECIMAL(5,2) NOT NULL DEFAULT 0,
    search_score   DECIMAL(5,2) NOT NULL DEFAULT 0,
    activity_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    quality_score  DECIMAL(5,2) NOT NULL DEFAULT 0,
    total_score    DECIMAL(5,2) NOT NULL DEFAULT 0,
    computed_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, target_id),
    KEY idx_algorithm_scores_rank (user_id, total_score),
    KEY idx_algorithm_scores_computed (computed_at),
    CONSTRAINT fk_algo_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_algo_target FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS daily_picks (
    user_id         INT UNSIGNED NOT NULL,
    picked_user_id  INT UNSIGNED NOT NULL,
    pick_date       DATE NOT NULL,
    score           DECIMAL(5,2) NOT NULL DEFAULT 0,
    is_acted        TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, picked_user_id, pick_date),
    KEY idx_daily_picks_date (user_id, pick_date),
    CONSTRAINT fk_dp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_dp_picked FOREIGN KEY (picked_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Safety
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blocks (
    blocker_id  INT UNSIGNED NOT NULL,
    blocked_id  INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (blocker_id, blocked_id),
    KEY idx_blocks_blocked (blocked_id),
    CONSTRAINT fk_blocks_blocker FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_blocks_blocked FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reporter_id  INT UNSIGNED NOT NULL,
    reported_id  INT UNSIGNED NOT NULL,
    reason       VARCHAR(30) NOT NULL,
    description  TEXT NULL,
    status       ENUM('pending','reviewing','resolved','dismissed') NOT NULL DEFAULT 'pending',
    action_taken ENUM('none','warning','photo_removed','suspended','banned') NULL,
    admin_notes  TEXT NULL,
    resolved_by  INT UNSIGNED NULL,
    resolved_at  DATETIME NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reports_status (status, created_at),
    KEY idx_reports_reported (reported_id),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_reported FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification documents are stored in uploads/private and only ever served to admins
CREATE TABLE IF NOT EXISTS verification_requests (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED NOT NULL,
    type             ENUM('photo','id','both') NOT NULL DEFAULT 'photo',
    selfie_path      VARCHAR(255) NOT NULL,
    id_document_path VARCHAR(255) NULL,
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    rejection_reason VARCHAR(255) NULL,
    reviewed_by      INT UNSIGNED NULL,
    reviewed_at      DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_verification_status (status, created_at),
    KEY idx_verification_user (user_id),
    CONSTRAINT fk_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Messaging (independent of matches so admin can open messaging to anyone,
-- logged-in users, premium members, or matches only). user1_id < user2_id.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user1_id         INT UNSIGNED NOT NULL,
    user2_id         INT UNSIGNED NOT NULL,
    initiated_by     INT UNSIGNED NOT NULL,
    last_message_id  BIGINT UNSIGNED NULL,
    last_message_at  DATETIME NULL,
    user1_unread     INT UNSIGNED NOT NULL DEFAULT 0,
    user2_unread     INT UNSIGNED NOT NULL DEFAULT 0,
    user1_archived   TINYINT(1) NOT NULL DEFAULT 0,
    user2_archived   TINYINT(1) NOT NULL DEFAULT 0,
    user1_muted      TINYINT(1) NOT NULL DEFAULT 0,
    user2_muted      TINYINT(1) NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_conversations_pair (user1_id, user2_id),
    KEY idx_conversations_u1 (user1_id, last_message_at),
    KEY idx_conversations_u2 (user2_id, last_message_at),
    CONSTRAINT fk_conv_u1 FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_u2 FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id  INT UNSIGNED NOT NULL,
    sender_id        INT UNSIGNED NOT NULL,
    type             ENUM('text','image','system') NOT NULL DEFAULT 'text',
    content          TEXT NULL,
    media_path       VARCHAR(255) NULL,
    is_read          TINYINT(1) NOT NULL DEFAULT 0,
    read_at          DATETIME NULL,
    is_deleted       TINYINT(1) NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_conv (conversation_id, id),
    KEY idx_messages_sender (sender_id, created_at),
    KEY idx_messages_created (created_at),
    CONSTRAINT fk_messages_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS typing_status (
    conversation_id INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (conversation_id, user_id),
    CONSTRAINT fk_typing_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_typing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Notifications
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(30) NOT NULL,
    title       VARCHAR(160) NOT NULL,
    body        VARCHAR(400) NULL,
    action_url  VARCHAR(255) NULL,
    sender_id   INT UNSIGNED NULL,
    data        TEXT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    read_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id, is_read, created_at),
    KEY idx_notifications_type (user_id, type),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id          INT UNSIGNED NOT NULL,
    inapp_matches    TINYINT(1) NOT NULL DEFAULT 1,
    inapp_messages   TINYINT(1) NOT NULL DEFAULT 1,
    inapp_likes      TINYINT(1) NOT NULL DEFAULT 1,
    inapp_visits     TINYINT(1) NOT NULL DEFAULT 1,
    inapp_gifts      TINYINT(1) NOT NULL DEFAULT 1,
    email_matches    TINYINT(1) NOT NULL DEFAULT 1,
    email_messages   TINYINT(1) NOT NULL DEFAULT 0,
    email_likes      TINYINT(1) NOT NULL DEFAULT 0,
    email_news       TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_notification_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
