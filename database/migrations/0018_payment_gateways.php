<?php
/**
 * More ways to pay (Paystack, PayPal, card), automatic plan renewal, and the M-Pesa C2B (customer pays to your Paybill / till)
 * and B2C (you pay a member or Host out) tables. Everything is additive and safe to run twice.
 */
return function (): void {
    // ---- payments: which gateway took it, its reference and its transaction id -----------------------------------
    mig_add_column('payments', 'gateway', 'VARCHAR(20) NULL AFTER payment_method');
    mig_add_column('payments', 'gateway_ref', 'VARCHAR(120) NULL AFTER gateway');
    mig_add_column('payments', 'gateway_txn', 'VARCHAR(120) NULL AFTER gateway_ref');
    mig_add_index('payments', 'idx_payments_gateway_ref', 'gateway_ref');

    // ---- automatic renewal of a plan -----------------------------------------------------------------------------
    mig_add_column('user_subscriptions', 'auto_renew', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER cancelled_at');
    mig_add_column('user_subscriptions', 'renew_method', 'VARCHAR(20) NULL AFTER auto_renew');
    mig_add_column('user_subscriptions', 'renew_saved_id', 'INT UNSIGNED NULL AFTER renew_method');
    mig_add_column('user_subscriptions', 'renew_phone', 'VARCHAR(20) NULL AFTER renew_saved_id');
    mig_add_column('user_subscriptions', 'renew_tries', 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER renew_phone');
    mig_add_column('user_subscriptions', 'renew_next_at', 'DATETIME NULL AFTER renew_tries');
    mig_add_column('user_subscriptions', 'renew_notified_for', 'DATETIME NULL AFTER renew_next_at');
    mig_add_index('user_subscriptions', 'idx_subs_renew', 'status, auto_renew, expires_at');

    mig_exec("CREATE TABLE IF NOT EXISTS pay_saved_methods (
        id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id      INT UNSIGNED NOT NULL,
        gateway      VARCHAR(20) NOT NULL,
        token        VARCHAR(160) NOT NULL,
        customer     VARCHAR(160) NULL,
        fingerprint  VARCHAR(160) NOT NULL,
        brand        VARCHAR(30) NULL,
        last4        VARCHAR(4) NULL,
        exp_month    TINYINT UNSIGNED NULL,
        exp_year     SMALLINT UNSIGNED NULL,
        is_active    TINYINT(1) NOT NULL DEFAULT 1,
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_saved_method (user_id, gateway, fingerprint),
        KEY idx_saved_user (user_id, is_active),
        CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS payment_events (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        gateway     VARCHAR(20) NOT NULL,
        event_id    VARCHAR(120) NOT NULL,
        event_type  VARCHAR(80) NULL,
        payment_id  INT UNSIGNED NULL,
        outcome     VARCHAR(160) NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_payment_event (gateway, event_id),
        KEY idx_payment_events_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS mpesa_c2b_transactions (
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
        trans_id      VARCHAR(30) NOT NULL,
        trans_type    VARCHAR(30) NULL,
        trans_time    VARCHAR(20) NULL,
        amount        DECIMAL(12,2) NOT NULL,
        shortcode     VARCHAR(20) NULL,
        bill_ref      VARCHAR(60) NULL,
        msisdn        VARCHAR(80) NULL,
        payer_name    VARCHAR(120) NULL,
        org_balance   VARCHAR(30) NULL,
        status        ENUM('matched','unmatched','underpaid','duplicate','manual') NOT NULL DEFAULT 'unmatched',
        payment_id    INT UNSIGNED NULL,
        note          VARCHAR(255) NULL,
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_c2b_trans (trans_id),
        KEY idx_c2b_status (status, created_at),
        KEY idx_c2b_ref (bill_ref)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS mpesa_b2c_requests (
        id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
        kind            VARCHAR(20) NOT NULL,
        ref_id          INT UNSIGNED NOT NULL DEFAULT 0,
        phone           VARCHAR(20) NOT NULL,
        amount          DECIMAL(12,2) NOT NULL,
        remarks         VARCHAR(100) NULL,
        originator_id   VARCHAR(60) NOT NULL,
        conversation_id VARCHAR(80) NULL,
        status          ENUM('queued','sent','completed','failed','timeout') NOT NULL DEFAULT 'queued',
        receipt         VARCHAR(30) NULL,
        result_code     VARCHAR(12) NULL,
        result_desc     VARCHAR(255) NULL,
        receiver_name   VARCHAR(120) NULL,
        requested_by    INT UNSIGNED NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_b2c_originator (originator_id),
        KEY idx_b2c_ref (kind, ref_id, status),
        KEY idx_b2c_conv (conversation_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // ---- defaults --------------------------------------------------------------------------------------------------
    foreach ([
        'paystack_enabled' => '0', 'paypal_enabled' => '0', 'paypal_env' => 'sandbox', 'paypal_currency' => 'USD', 'paypal_rate' => '129',
        'card_gateway' => 'stripe', 'autorenew_enabled' => '0', 'autorenew_remind_days' => '3', 'autorenew_retry_days' => '2', 'autorenew_max_tries' => '3',
        'mpesa_c2b_enabled' => '0', 'mpesa_c2b_response' => 'Completed', 'mpesa_c2b_validation' => '0', 'mpesa_c2b_api' => 'v2',
        'mpesa_b2c_enabled' => '0', 'mpesa_b2c_command' => 'BusinessPayment', 'mpesa_b2c_max' => '150000', 'mpesa_b2c_auto' => '0', 'mpesa_b2c_auto_limit' => '5000',
    ] as $k => $v) {
        mig_setting_default($k, $v);
    }
};
