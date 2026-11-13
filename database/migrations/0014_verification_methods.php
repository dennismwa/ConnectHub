<?php
/**
 * Verification methods the admin can switch on and off: the usual selfie, and "copy this pose" methods with a reference photo
 * the admin uploads. A request remembers which method it used (and a copy of the reference picture) so the reviewer can compare.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS verification_methods (
        id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
        kind           VARCHAR(10) NOT NULL DEFAULT 'pose',
        name           VARCHAR(60) NOT NULL,
        instruction    VARCHAR(400) NULL,
        reference_path VARCHAR(255) NULL,
        is_active      TINYINT(1) NOT NULL DEFAULT 1,
        sort_order     INT NOT NULL DEFAULT 0,
        created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_vmethod_active (is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    mig_add_column('verification_requests', 'method_id', 'INT UNSIGNED NULL');
    mig_add_column('verification_requests', 'method_kind', "VARCHAR(10) NOT NULL DEFAULT 'selfie'");
    mig_add_column('verification_requests', 'reference_path', 'VARCHAR(255) NULL');

    if ((int)val("SELECT COUNT(*) FROM verification_methods WHERE kind = 'selfie'", [], 0) === 0) {
        insert('verification_methods', ['kind' => 'selfie', 'name' => 'Selfie', 'instruction' => null, 'reference_path' => null, 'is_active' => 1, 'sort_order' => 1]);
    }
    mig_setting_default('verification_assign', 'choose');
};
