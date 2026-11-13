<?php
/**
 * Elite shortlist: a member can save profiles to come back to (elite_saves). Only the number of saves is ever shown to an
 * Elite member, never who saved them. New page text, colour and address settings need no migration: they have defaults.
 * Safe to run twice: the table is created only when missing.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS elite_saves (
        user_id INT UNSIGNED NOT NULL,
        owner_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, owner_id),
        KEY idx_elite_saves_owner (owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
