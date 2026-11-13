<?php
/** Media library: one place for every image and video the admin has uploaded, with "choose from media" in every image slot. */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS media_files (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        path VARCHAR(255) NOT NULL,
        thumb_path VARCHAR(255) DEFAULT NULL,
        title VARCHAR(120) NOT NULL DEFAULT '',
        kind ENUM('image','video') NOT NULL DEFAULT 'image',
        width SMALLINT UNSIGNED DEFAULT NULL,
        height SMALLINT UNSIGNED DEFAULT NULL,
        file_size INT UNSIGNED NOT NULL DEFAULT 0,
        uploaded_by INT UNSIGNED DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_media_path (path),
        KEY idx_media_kind (kind, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Admin roles that could edit site content get the same level on the new Media module.
    foreach (rows('SELECT id, permissions FROM admin_roles') as $role) {
        $perms = json_decode((string)$role['permissions'], true);
        if (!is_array($perms) || isset($perms['media']) || !isset($perms['content'])) {
            continue;
        }
        $perms['media'] = $perms['content'];
        q('UPDATE admin_roles SET permissions = :p WHERE id = :id', ['p' => json_encode($perms), 'id' => $role['id']]);
    }

    // Pictures that were uploaded before the library existed.
    media_backfill();
};
