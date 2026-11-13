<?php
/**
 * Paid direct messages: a member can open a conversation with someone they have not matched with by spending a message credit.
 * Credits are bought in packs the admin prices, or come with a plan. The ledger is the only record of the balance.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS dm_packs (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name        VARCHAR(60) NOT NULL,
        credits     INT UNSIGNED NOT NULL,
        price       DECIMAL(10,2) NOT NULL,
        badge       VARCHAR(30) NULL,
        is_active   TINYINT(1) NOT NULL DEFAULT 1,
        sort_order  INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS dm_ledger (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     INT UNSIGNED NOT NULL,
        delta       INT NOT NULL,
        kind        VARCHAR(20) NOT NULL,
        ref_id      INT UNSIGNED NULL,
        note        VARCHAR(160) NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_dm_ledger_user (user_id, id),
        CONSTRAINT fk_dm_ledger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS dm_unlocks (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     INT UNSIGNED NOT NULL,
        target_id   INT UNSIGNED NOT NULL,
        conversation_id INT UNSIGNED NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_dm_unlock (user_id, target_id),
        KEY idx_dm_unlock_day (user_id, created_at),
        CONSTRAINT fk_dm_unlock_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_dm_unlock_target FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    mig_add_column('user_profiles', 'allow_paid_dm', 'TINYINT(1) NOT NULL DEFAULT 1');

    if ((int)val('SELECT COUNT(*) FROM dm_packs', [], 0) === 0) {
        foreach ([['Starter', 3, 150, null, 1], ['Popular', 10, 400, 'Popular', 2], ['Best value', 25, 900, 'Best value', 3]] as [$n, $c, $p, $b, $o]) {
            insert('dm_packs', ['name' => $n, 'credits' => $c, 'price' => $p, 'badge' => $b, 'sort_order' => $o, 'is_active' => 1]);
        }
    }
    foreach (['dm_paid_enabled' => '1', 'dm_messages_per_unlock' => '3', 'dm_daily_unlocks' => '10', 'dm_welcome_credits' => '0', 'dm_to_hosts' => '0'] as $k => $v) {
        mig_setting_default($k, $v);
    }
};
