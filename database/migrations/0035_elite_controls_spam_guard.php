<?php
/** Elite privacy administration and direct-message abuse guard. Safe to run repeatedly. */
return function (): void {
    if (mig_has_table('elite_profiles')) {
        mig_add_column('elite_profiles', 'admin_photo_privacy', 'TINYINT NOT NULL DEFAULT -1');
    }
    mig_exec("CREATE TABLE IF NOT EXISTS message_abuse_events (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        conversation_id INT UNSIGNED NULL,
        reason VARCHAR(40) NOT NULL,
        content_hash CHAR(64) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_msg_abuse_user_time (user_id, created_at),
        KEY idx_msg_abuse_reason_time (reason, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
};
