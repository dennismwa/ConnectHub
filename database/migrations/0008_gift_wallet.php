<?php
/**
 * Gift wallet: a member claims a gift into a wallet balance, then asks for a payout to a number they choose.
 * Admin verifies gifts (optional step) and pays the requests by hand.
 */
return function (): void {
    mig_add_column('sent_gifts', 'claimed_at', 'DATETIME NULL AFTER status');
    mig_add_column('sent_gifts', 'wallet_amount', 'DECIMAL(10,2) NULL AFTER claimed_at');
    mig_add_column('sent_gifts', 'verified_at', 'DATETIME NULL AFTER wallet_amount');
    mig_add_column('sent_gifts', 'verified_by', 'INT UNSIGNED NULL AFTER verified_at');
    mig_add_column('sent_gifts', 'verify_note', 'VARCHAR(255) NULL AFTER verified_by');

    mig_exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     INT UNSIGNED NOT NULL,
        kind        VARCHAR(20) NOT NULL,
        amount      DECIMAL(10,2) NOT NULL,
        ref_type    VARCHAR(20) NULL,
        ref_id      INT UNSIGNED NULL,
        note        VARCHAR(200) NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wallet_tx_user (user_id, id),
        CONSTRAINT fk_wallet_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS wallet_accounts (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     INT UNSIGNED NOT NULL,
        label       VARCHAR(40) NULL,
        phone       VARCHAR(20) NOT NULL,
        account_name VARCHAR(80) NOT NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wallet_acc_user (user_id),
        CONSTRAINT fk_wallet_acc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS wallet_payouts (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     INT UNSIGNED NOT NULL,
        amount      DECIMAL(10,2) NOT NULL,
        phone       VARCHAR(20) NOT NULL,
        account_name VARCHAR(80) NOT NULL,
        status      ENUM('requested','paid','rejected') NOT NULL DEFAULT 'requested',
        payment_reference VARCHAR(30) NULL,
        admin_note  VARCHAR(255) NULL,
        processed_by INT UNSIGNED NULL,
        processed_at DATETIME NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wallet_pay_user (user_id, status),
        KEY idx_wallet_pay_status (status, created_at),
        CONSTRAINT fk_wallet_pay_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    foreach (['wallet_enabled' => '1', 'gift_receiver_percent' => '70', 'gift_claim_mode' => 'auto', 'wallet_min_payout' => '500',
              'wallet_max_open_payouts' => '2', 'wallet_max_numbers' => '4'] as $k => $v) {
        mig_setting_default($k, $v);
    }
};
