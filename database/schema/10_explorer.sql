-- ============================================================================
-- ConnectHub schema, part 10: Explorer module additions (idempotent; safe to re-run)
-- The app works without these columns (includes/lib/explorer.php checks for them),
-- they only give admins richer data on reports.
-- ============================================================================

-- Longer report reasons (admins can edit the list under Settings > Safety)
ALTER TABLE reports MODIFY reason VARCHAR(80) NOT NULL;

-- What was reported: a member, a message, a host, a booking or a photo
SET @ch_sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE reports ADD COLUMN target_type VARCHAR(20) NOT NULL DEFAULT ''user'' AFTER reported_id', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'target_type');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;

SET @ch_sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE reports ADD COLUMN target_id INT UNSIGNED NULL AFTER target_type', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'target_id');
PREPARE ch_stmt FROM @ch_sql;
EXECUTE ch_stmt;
DEALLOCATE PREPARE ch_stmt;
