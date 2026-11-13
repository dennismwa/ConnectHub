-- ============================================================================
-- Module B (messaging, notifications, money): idempotent additions only.
-- ============================================================================

-- Conversations a member has pinned to the top of their inbox.
CREATE TABLE IF NOT EXISTS conversation_pins (
    conversation_id INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (conversation_id, user_id),
    KEY idx_conversation_pins_user (user_id),
    CONSTRAINT fk_conv_pins_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_pins_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
