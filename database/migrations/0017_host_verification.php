<?php
/**
 * Host verification methods: the ID document check and a second, different method (a live photo check, the same one members use).
 * Each can be switched on or off by the admin. Adds host_profiles.photo_verified and the three settings.
 */
return function (): void {
    mig_add_column('host_profiles', 'photo_verified', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER id_verified');
    // keep the old behaviour: the ID check stays on unless the site had turned the ID requirement off
    $idOn = row("SELECT setting_value FROM site_settings WHERE setting_key = 'host_id_required'");
    mig_setting_default('host_verify_id_enabled', $idOn && (string)$idOn['setting_value'] === '0' ? '0' : '1');
    mig_setting_default('host_verify_photo_enabled', '0');
    mig_setting_default('host_verify_rule', 'any');
    // hosts who already passed a photo check keep their badge
    q("UPDATE host_profiles hp SET photo_verified = 1 WHERE EXISTS (SELECT 1 FROM verification_requests v WHERE v.user_id = hp.user_id AND v.status = 'approved')");
};
