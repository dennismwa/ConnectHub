<?php
/**
 * International connections: members opt in to be found by people in other countries. A listing can be free or paid, and
 * viewing/contacting listed members can be free or paid. The admin chooses in Admin > International.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS intl_profiles (
        user_id         INT UNSIGNED NOT NULL,
        status          VARCHAR(12) NOT NULL DEFAULT 'pending',
        country         CHAR(2) NOT NULL,
        city            VARCHAR(80) NULL,
        languages       VARCHAR(200) NOT NULL DEFAULT '',
        purposes        VARCHAR(120) NOT NULL DEFAULT '',
        want_countries  VARCHAR(500) NOT NULL DEFAULT '',
        headline        VARCHAR(120) NULL,
        about           TEXT NULL,
        contact_type    VARCHAR(12) NULL,
        contact_dial    VARCHAR(6) NULL,
        contact_number  VARCHAR(20) NULL,
        contact_share   VARCHAR(8) NOT NULL DEFAULT 'chat',
        listed_until    DATETIME NULL,
        admin_note      VARCHAR(255) NULL,
        approved_at     DATETIME NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        KEY idx_intl_status (status, country),
        CONSTRAINT fk_intl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS intl_access (
        user_id     INT UNSIGNED NOT NULL,
        expires_at  DATETIME NULL,
        source      VARCHAR(12) NOT NULL DEFAULT 'purchase',
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_intl_access_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    foreach (['intl_enabled' => '1', 'intl_mode' => 'free', 'intl_join_fee' => '500', 'intl_join_days' => '30', 'intl_access_fee' => '500', 'intl_access_days' => '30',
        'intl_premium_free' => 'premium', 'intl_approval' => '1', 'intl_require_verified' => '0', 'intl_preview' => '1', 'intl_msg_included' => '1',
        'intl_contact_types' => '["whatsapp","mobile","telegram"]'] as $k => $v) {
        mig_setting_default($k, $v);
    }

    // A link in the member sidebar and the footer. The admin can move or hide it under Navigation.
    foreach ([['app_sidebar', 'International', 'globe', 'user', 8], ['footer_explore', 'International', 'globe', 'any', 5]] as [$menu, $label, $icon, $aud, $sort]) {
        if ((int)val('SELECT COUNT(*) FROM nav_items WHERE menu = :m AND url = :u', ['m' => $menu, 'u' => '/international'], 0) === 0) {
            insert('nav_items', ['menu' => $menu, 'label' => $label, 'url' => '/international', 'icon' => $icon, 'audience' => $aud, 'sort_order' => $sort, 'is_active' => 1]);
        }
    }
};
