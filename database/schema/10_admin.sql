-- ============================================================================
-- ConnectHub schema, admin core additions (idempotent; safe to re-import)
-- ============================================================================

-- Private notes that admins attach to a member (visible to admins only)
CREATE TABLE IF NOT EXISTS admin_user_notes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    admin_id    INT UNSIGNED NULL,
    note        VARCHAR(2000) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_notes_user (user_id, created_at),
    CONSTRAINT fk_admin_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
