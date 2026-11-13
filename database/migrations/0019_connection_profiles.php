<?php
/** Optional host connection profiles, service abbreviations and admin moderation. */
return function (): void {
    q("CREATE TABLE IF NOT EXISTS host_connection_profiles (
        user_id INT UNSIGNED NOT NULL,
        enabled TINYINT(1) NOT NULL DEFAULT 0,
        approval_status VARCHAR(12) NOT NULL DEFAULT 'draft',
        title VARCHAR(90) NOT NULL DEFAULT '',
        about_text VARCHAR(800) NOT NULL DEFAULT '',
        city VARCHAR(80) NOT NULL DEFAULT '',
        area VARCHAR(120) NOT NULL DEFAULT '',
        contact_phone VARCHAR(30) NULL,
        contact_whatsapp VARCHAR(30) NULL,
        contact_method VARCHAR(20) NOT NULL DEFAULT 'platform',
        service_codes VARCHAR(500) NOT NULL DEFAULT '',
        photo_ids VARCHAR(500) NOT NULL DEFAULT '',
        admin_note VARCHAR(255) NULL,
        submitted_at DATETIME NULL,
        approved_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        KEY idx_connection_public (enabled, approval_status, city),
        CONSTRAINT fk_connection_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    mig_setting_default('host_connections_enabled', '0');
    mig_setting_default('host_connections_require_approval', '1');
    mig_setting_default('host_connections_show_contact', '1');
    // Seed a safe, non-sexual catalogue of short codes. Admins can edit this catalogue in the module page.
    q("CREATE TABLE IF NOT EXISTS host_connection_services (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        code VARCHAR(12) NOT NULL,
        label VARCHAR(70) NOT NULL,
        description VARCHAR(180) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id), UNIQUE KEY uq_connection_service_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $items = [
        ['SOC','Social meet-up','Meet for a conversation in a public place'],
        ['DIN','Dinner company','Company for a meal or public event'],
        ['EVT','Event companion','Company at public events and celebrations'],
        ['TRV','Travel companion','Company for planned trips and city visits'],
        ['COF','Coffee chat','A casual coffee and conversation'],
        ['VID','Video chat','A friendly online conversation'],
        ['LAN','Language practice','Practice a language together'],
        ['WALK','Walk partner','A walk in a public, well-lit place'],
    ];
    foreach ($items as $i => $item) {
        if (!val('SELECT id FROM host_connection_services WHERE code = :c', ['c' => $item[0]])) {
            insert('host_connection_services', ['code'=>$item[0], 'label'=>$item[1], 'description'=>$item[2], 'is_active'=>1, 'sort_order'=>$i+1]);
        }
    }
    if (!val("SELECT id FROM nav_items WHERE menu = 'host_sidebar' AND url = '/host/connections'")) {
        insert('nav_items', ['menu'=>'host_sidebar','label'=>'Connection profile','url'=>'/host/connections','icon'=>'heart-handshake','audience'=>'host','badge_key'=>null,'sort_order'=>9]);
    }
    if (function_exists('cache_clear')) cache_clear();
};
