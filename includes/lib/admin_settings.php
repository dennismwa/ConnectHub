<?php
/**
 * Admin settings UI helpers: dependencies between fields, permission levels, uploads,
 * server side normalisation and the generic field renderer driven by settings_schema().
 */

declare(strict_types=1);

/**
 * Which fields only make sense when another field has a given value.
 * Returns [settingKey => [[controllingKey, 'on'|'off'|'in'|'not_in', [values]], ...]] (all conditions must hold).
 * A `depends` entry in the schema itself (same format) wins over this map.
 */
function admin_core_settings_depends(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $on = fn(string $k) => [$k, 'on', []];
    $in = fn(string $k, string ...$v) => [$k, 'in', $v];
    $notIn = fn(string $k, string ...$v) => [$k, 'not_in', $v];
    $map = [
        'maintenance_message' => [$on('maintenance_mode')],
        'hero_art_1' => [$in('hero_layout', 'collage')],
        'hero_art_2' => [$in('hero_layout', 'collage')],
        'hero_art_3' => [$in('hero_layout', 'collage')],
        'hero_chip_1' => [$in('hero_layout', 'collage')],
        'hero_chip_2' => [$in('hero_layout', 'collage')],
        'hero_image' => [$in('hero_bg_type', 'image', 'parallax')],
        'hero_video' => [$in('hero_bg_type', 'video')],
        'hero_slider_speed' => [$in('hero_bg_type', 'slider')],
        'hero_slider_transition' => [$in('hero_bg_type', 'slider')],
        'hero_slider_indicators' => [$in('hero_bg_type', 'slider')],
        'hero_pause_hover' => [$in('hero_bg_type', 'slider')],
        'hero_overlay_color' => [$notIn('hero_overlay_type', 'none')],
        'hero_overlay_start' => [$notIn('hero_overlay_type', 'none')],
        'hero_overlay_end' => [$in('hero_overlay_type', 'gradient')],
        'hero_cta_member_text' => [$in('hero_cta_member_mode', 'discover')],
        'hero_location_mode' => [$on('hero_show_location')],
        'hero_use_counties' => [$on('hero_show_location')],
        'hero_use_towns' => [$on('hero_show_location')],
        'hero_pill_count' => [$on('hero_show_location'), $in('hero_location_mode', 'pills', 'both')],
        'hero_location_prompt' => [$on('hero_show_location'), $in('hero_location_mode', 'search', 'both', 'dropdown')],
        'auth_stats_mode' => [$on('auth_show_stats')],
        'verification_type' => [$on('verification_enabled')],
        'daily_picks_count' => [$on('daily_picks_enabled')],
        'guest_browse_limit' => [$on('guest_browse_enabled')],
        'msg_max_image_mb' => [$notIn('msg_images', 'off')],
        'msg_unlock_after' => [$on('msg_mask_contacts')],
        'msg_icebreaker_count' => [$on('msg_icebreakers')],
        'guest_bio_chars' => [$in('guest_profile_view', 'limited')],
        'host_video_max_seconds' => [$on('host_allow_video')],
        'host_video_max_mb' => [$on('host_allow_video')],
        'booking_deposit_pct' => [$on('booking_enabled')],
        'host_min_notice_hours' => [$on('booking_enabled')],
        'host_max_advance_days' => [$on('booking_enabled')],
        'manual_mpesa_details' => [$on('manual_mpesa_enabled')],
        'ads_discover_every' => [$on('ads_enabled')],
        'ads_banner_landing' => [$on('ads_enabled')],
        'ads_popup_enabled' => [$on('ads_enabled')],
        'ads_label' => [$on('ads_enabled')],
        'promo_enabled' => [$on('ads_enabled')],
        'promo_review' => [$on('ads_enabled'), $on('promo_enabled')],
        'promo_max_shown' => [$on('ads_enabled')],
        'promo_rules' => [$on('ads_enabled'), $on('promo_enabled')],
        'cookie_banner_text' => [$on('cookie_banner_enabled')],
        'msg_edit_window' => [$on('msg_edit_own')],
        'pwa_install_prompt' => [$on('pwa_enabled')],
        'pwa_prompt_after_visits' => [$on('pwa_enabled'), $on('pwa_install_prompt')],
        'pwa_short_name' => [$on('pwa_enabled')],
        'pwa_offline_message' => [$on('pwa_enabled')],
        'pwa_name' => [$on('pwa_enabled')], 'pwa_description' => [$on('pwa_enabled')], 'pwa_icon' => [$on('pwa_enabled')], 'pwa_theme_color' => [$on('pwa_enabled')],
        'pwa_background_color' => [$on('pwa_enabled')], 'pwa_display' => [$on('pwa_enabled')], 'pwa_orientation' => [$on('pwa_enabled')], 'pwa_start_path' => [$on('pwa_enabled')],
        'pwa_shortcuts' => [$on('pwa_enabled')], 'pwa_cache_assets' => [$on('pwa_enabled')], 'pwa_cache_version' => [$on('pwa_enabled'), $on('pwa_cache_assets')],
    ];
    // everything that only matters when Hosts work as a booking service
    foreach (['booking_deposit_pct', 'host_commission_pct', 'host_min_notice_hours', 'host_max_advance_days', 'host_instant_booking_enabled', 'host_accept_hours', 'host_booking_buffer_minutes', 'host_slot_step_minutes',
        'host_booking_max_hours', 'host_payment_hold_minutes', 'host_max_unpaid_bookings', 'host_cancel_full_hours', 'host_cancel_partial_hours', 'host_cancel_partial_pct', 'host_auto_complete_hours', 'host_reminder_hours',
        'host_payout_hold_hours', 'host_contact_after_booking'] as $k) {
        $map[$k] = [$on('booking_enabled')];
    }
    foreach (['auth_stat1', 'auth_stat2', 'auth_stat3'] as $p) {
        $map[$p . '_label'] = [$on('auth_show_stats')];
        $map[$p . '_value'] = [$on('auth_show_stats'), $in('auth_stats_mode', 'manual')];
    }
    foreach (['mpesa_env', 'mpesa_shortcode', 'mpesa_type', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_passkey', 'mpesa_callback_secret'] as $k) {
        $map[$k] = [$on('mpesa_enabled')];
    }
    foreach (['bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch'] as $k) {
        $map[$k] = [$on('bank_enabled')];
    }
    foreach (['smtp_preset', 'smtp_user', 'smtp_pass'] as $k) {
        $map[$k] = [$in('mail_driver', 'smtp')];
    }
    foreach (['smtp_host', 'smtp_port', 'smtp_secure'] as $k) {
        $map[$k] = [$in('mail_driver', 'smtp'), $in('smtp_preset', 'custom')];
    }
    foreach (['intl_mode', 'intl_join_fee', 'intl_join_days', 'intl_access_fee', 'intl_access_days', 'intl_premium_free', 'intl_approval', 'intl_require_verified', 'intl_preview', 'intl_msg_included', 'intl_contact_types'] as $k) {
        $map[$k] = [$on('intl_enabled')];
    }
    foreach (['intl_join_fee', 'intl_join_days'] as $k) {
        $map[$k][] = $in('intl_mode', 'join', 'both');
    }
    foreach (['intl_access_fee', 'intl_access_days', 'intl_msg_included', 'intl_preview'] as $k) {
        $map[$k][] = $in('intl_mode', 'access', 'both');
    }
    $map['intl_premium_free'][] = $notIn('intl_mode', 'free');
    foreach (['dm_messages_per_unlock', 'dm_daily_unlocks', 'dm_welcome_credits', 'dm_to_hosts'] as $k) {
        $map[$k] = [$on('dm_paid_enabled')];
    }
    foreach (['gift_receiver_percent', 'gift_claim_mode', 'wallet_payouts_enabled'] as $k) {
        $map[$k] = [$on('wallet_enabled')];
    }
    foreach (['wallet_min_payout', 'wallet_max_open_payouts', 'wallet_max_numbers'] as $k) {
        $map[$k] = [$on('wallet_enabled')];
    }
    $map['mail_ses_region'] = [$in('mail_driver', 'smtp'), $in('smtp_preset', 'ses')];
    $map['mail_api_key'] = [$in('mail_driver', 'sendgrid_api', 'mailgun_api', 'brevo_api', 'postmark_api', 'resend_api')];
    $map['mail_mailgun_domain'] = [$in('mail_driver', 'mailgun_api')];
    $map['mail_mailgun_region'] = [$in('mail_driver', 'mailgun_api')];
    $map['mail_fallback_server'] = [$notIn('mail_driver', 'mail', 'log')];
    return $map;
}

/** Level an admin needs to change a tab (viewing needs only "view"). */
function admin_core_settings_tab_level(string $tab): string
{
    return in_array($tab, ['payments', 'email', 'safety'], true) ? 'full' : 'edit';
}

function admin_core_settings_key_level(string $key): string
{
    $fields = settings_schema()['fields'];
    $tab = $fields[$key]['tab'] ?? '';
    if ($key === 'custom_css') {
        return 'full'; // arbitrary CSS on every page
    }
    return admin_core_settings_tab_level($tab);
}

function admin_core_settings_can_edit_tab(string $tab): bool
{
    return admin_can('settings', admin_core_settings_tab_level($tab));
}

/** Upload rules for an image or video setting. */
function admin_core_setting_file_rules(string $key, array $def): array
{
    if (($def['accept'] ?? '') === 'video') {
        return ['kind' => 'video', 'sub' => 'hero', 'svg' => false, 'alpha' => false, 'max_side' => 0, 'mb' => 40, 'min_side' => 0, 'label' => 'MP4 or WebM video up to 40 MB'];
    }
    if (strpos($key, 'logo_') === 0) {
        return ['kind' => 'image', 'sub' => 'site', 'svg' => true, 'alpha' => true, 'max_side' => 1600, 'mb' => 4, 'min_side' => 0, 'label' => 'SVG, PNG, JPG or WebP up to 4 MB. Transparent PNG or SVG works best.'];
    }
    if ($key === 'favicon') {
        return ['kind' => 'image', 'sub' => 'site', 'svg' => false, 'alpha' => true, 'max_side' => 512, 'mb' => 2, 'min_side' => 192, 'label' => 'Square PNG, JPG or WebP, at least 192 × 192 px'];
    }
    if ($key === 'og_image') {
        return ['kind' => 'image', 'sub' => 'site', 'svg' => false, 'alpha' => false, 'max_side' => 1600, 'mb' => 6, 'min_side' => 0, 'label' => 'JPG, PNG or WebP, 1200 × 630 px works best'];
    }
    return ['kind' => 'image', 'sub' => 'hero', 'svg' => false, 'alpha' => false, 'max_side' => 2400, 'mb' => 8, 'min_side' => 0, 'label' => 'JPG, PNG or WebP up to 8 MB'];
}

/** Is a stored upload still referenced by a setting or hero slide? */
function admin_core_upload_in_use(string $rel, ?string $exceptKey = null): bool
{
    foreach (settings_overrides() as $k => $v) {
        if ($k !== $exceptKey && $v === $rel) {
            return true;
        }
    }
    try {
        if ((int)val('SELECT COUNT(*) FROM hero_slides WHERE image_path = :p OR mobile_path = :p', ['p' => $rel], 0) > 0) {
            return true;
        }
    } catch (Throwable $e) {
    }
    return false;
}

/** Delete a replaced site file when nothing else uses it. Files kept in the media library are never deleted here. */
function admin_core_setting_file_cleanup(?string $rel, string $exceptKey): void
{
    if (!$rel || !preg_match('#^(site|hero|library)/[A-Za-z0-9_.-]+$#', $rel) || media_is_registered($rel)) {
        return;
    }
    if (!admin_core_upload_in_use($rel, $exceptKey)) {
        upload_delete($rel);
    }
}

/**
 * Store an uploaded file for an image/video setting and save it. Returns ['ok','error','path','url'].
 */
function admin_core_setting_upload(string $key, array $file, int $adminId): array
{
    $fields = settings_schema()['fields'];
    $def = $fields[$key] ?? null;
    if (!$def || $def['type'] !== 'image') {
        return ['ok' => false, 'error' => 'That setting does not take a file.', 'path' => '', 'url' => ''];
    }
    $rules = admin_core_setting_file_rules($key, $def);
    if ($rules['kind'] === 'video') {
        $res = save_video($file, $rules['sub'], $rules['mb']);
        if ($res['ok']) {
            media_register($res['path'], ['title' => media_title_from_name((string)($file['name'] ?? '')), 'uploaded_by' => $adminId]);
        }
    } else {
        if ($rules['min_side'] > 0 && ($file['error'] ?? 4) === UPLOAD_ERR_OK) {
            $info = @getimagesize((string)$file['tmp_name']);
            if ($info && min($info[0], $info[1]) < $rules['min_side']) {
                return ['ok' => false, 'error' => 'The image is too small. Use one that is at least ' . $rules['min_side'] . ' pixels on its shortest side.', 'path' => '', 'url' => ''];
            }
        }
        $res = save_image($file, $rules['sub'], $rules['max_side'], $rules['alpha'], $rules['svg'], $rules['mb']);
        if ($res['ok']) {
            media_register($res['path'], ['title' => media_title_from_name((string)($file['name'] ?? '')), 'uploaded_by' => $adminId]);
        }
    }
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'], 'path' => '', 'url' => ''];
    }
    $old = (string)setting($key);
    setting_set($key, $res['path'], $adminId);
    cache_clear('landing');
    admin_core_setting_file_cleanup($old, $key);
    admin_log('settings.upload', 'setting', null, $key . ': ' . ($old !== '' ? $old : '(none)') . ' -> ' . $res['path']);
    return ['ok' => true, 'error' => '', 'path' => $res['path'], 'url' => upload_url($res['path'])];
}

/** Use a picture or video from the media library for an image/video setting. Returns ['ok','error','path','url']. */
function admin_core_setting_pick(string $key, string $path, int $adminId): array
{
    $fields = settings_schema()['fields'];
    $def = $fields[$key] ?? null;
    if (!$def || $def['type'] !== 'image') {
        return ['ok' => false, 'error' => 'That setting does not take a file.', 'path' => '', 'url' => ''];
    }
    $rules = admin_core_setting_file_rules($key, $def);
    $ok = media_pick_valid($path, $rules['kind']);
    if ($ok === null) {
        return ['ok' => false, 'error' => $rules['kind'] === 'video' ? 'Choose a video from the library.' : 'That picture is not in the media library any more.', 'path' => '', 'url' => ''];
    }
    // a picture from the library must still suit this place (the same rules as a fresh upload)
    if ($rules['kind'] === 'image' && strncmp($ok, '@art/', 5) !== 0) {
        $isSvg = strtolower(pathinfo($ok, PATHINFO_EXTENSION)) === 'svg';
        if ($isSvg && !$rules['svg']) {
            return ['ok' => false, 'error' => 'This place does not take an SVG. Choose a PNG, JPG or WebP picture.', 'path' => '', 'url' => ''];
        }
        if ($rules['min_side'] > 0 && !$isSvg) {
            $info = @getimagesize(upload_abs($ok));
            if (!$info || min((int)$info[0], (int)$info[1]) < $rules['min_side']) {
                return ['ok' => false, 'error' => 'That picture is too small here. It needs to be at least ' . $rules['min_side'] . ' × ' . $rules['min_side'] . ' px.', 'path' => '', 'url' => ''];
            }
        }
    }
    $old = (string)setting($key);
    if ($old !== $ok) {
        setting_set($key, $ok, $adminId);
        cache_clear('landing');
        admin_core_setting_file_cleanup($old, $key);
        admin_log('settings.upload', 'setting', null, $key . ': ' . ($old !== '' ? $old : '(none)') . ' -> ' . $ok . ' (media library)');
    }
    return ['ok' => true, 'error' => '', 'path' => $ok, 'url' => upload_url($ok)];
}

/** Remove an uploaded file from a setting (back to its default of nothing). */
function admin_core_setting_file_remove(string $key, int $adminId): bool
{
    $fields = settings_schema()['fields'];
    if (($fields[$key]['type'] ?? '') !== 'image') {
        return false;
    }
    $old = (string)setting($key);
    if ((string)($fields[$key]['default'] ?? '') !== '') {
        // a setting that ships with a built-in picture: "Remove" means no picture, "Reset to default" brings the built-in one back
        setting_set($key, '', $adminId);
    } else {
        setting_reset($key);
    }
    cache_clear('landing');
    admin_core_setting_file_cleanup($old, $key);
    admin_log('settings.upload', 'setting', null, $key . ': ' . ($old !== '' ? $old : '(none)') . ' -> (removed)');
    return true;
}

/**
 * Clean and extra-validate a posted value before settings_save() sees it.
 * @return array [mixed value, string|null error]
 */
function admin_core_settings_normalize(string $key, array $def, $raw): array
{
    if (is_string($raw)) {
        $raw = str_replace("\r\n", "\n", $raw);
    }
    $label = (string)$def['label'];
    switch ($key) {
        case 'site_url':
            return [rtrim(trim((string)$raw), '/'), null];
        case 'phone_prefix':
        case 'site_whatsapp':
            $d = preg_replace('/\D/', '', (string)$raw) ?? '';
            return [$d, null];
        case 'timezone':
            $tz = trim((string)$raw);
            if (!in_array($tz, timezone_identifiers_list(), true)) {
                return [$tz, $label . ' must be a time zone name such as Africa/Nairobi.'];
            }
            return [$tz, null];
        case 'currency':
            $c = strtoupper(trim((string)$raw));
            return [$c, preg_match('/^[A-Z]{3,5}$/', $c) ? null : $label . ' must be letters only, for example KES.'];
        case 'hero_cta_primary_url':
        case 'hero_cta_secondary_url':
            $u = trim((string)$raw);
            if ($u === '' && $key === 'hero_cta_secondary_url') {
                return ['', null];
            }
            return [$u, preg_match('#^(/|https?://|\#)[^\s]*$#i', $u) ? null : $label . ' must start with / or https://.'];
        case 'terms_slug':
        case 'privacy_slug':
            $s = trim((string)$raw, " /\t");
            return [$s, preg_match('/^[a-z0-9][a-z0-9-]*$/', $s) ? null : $label . ' can use lowercase letters, numbers and hyphens only.'];
        case 'admin_ip_whitelist':
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$raw) ?: []), fn($l) => $l !== ''));
            foreach ($lines as $l) {
                if (!filter_var($l, FILTER_VALIDATE_IP)) {
                    return [implode("\n", $lines), '"' . $l . '" is not a valid IP address. Put one address on each line.'];
                }
            }
            if ($lines && !in_array(client_ip(), $lines, true)) {
                return [implode("\n", $lines), 'Your current address (' . client_ip() . ') is not in this list. Add it first or you will lock yourself out of the admin panel.'];
            }
            return [implode("\n", $lines), null];
    }
    if ($def['type'] === 'color') {
        $c = trim((string)$raw);
        return [$c === '' ? (string)$def['default'] : $c, null];
    }
    if ($def['type'] === 'text' || $def['type'] === 'textarea') {
        return [preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$raw) ?? '', null];
    }
    return [$raw, null];
}

/** Short printable form of a setting value for the activity log. */
function admin_core_setting_log_value(string $key, $v): string
{
    $def = settings_schema()['fields'][$key] ?? ['type' => 'text'];
    if ($def['type'] === 'secret') {
        return '(secret)';
    }
    if ($def['type'] === 'bool') {
        return $v ? 'on' : 'off';
    }
    if (is_array($v)) {
        $v = implode(',', $v);
    }
    $s = trim(preg_replace('/\s+/', ' ', (string)$v) ?? '');
    return $s === '' ? '(empty)' : str_limit($s, 70);
}

/**
 * Save one or more sections of a tab from posted form data.
 * @param int[] $sectionIdxs indexes into settings_schema()['tabs'][$tab]['sections']
 * @return array ['saved'=>int,'errors'=>[key=>msg],'keys'=>string[],'custom'=>[key=>bool],'secrets'=>[key=>bool]]
 */
function admin_core_settings_apply(string $tab, array $sectionIdxs, array $post, int $adminId): array
{
    $schema = settings_schema();
    $fields = $schema['fields'];
    $sections = $schema['tabs'][$tab]['sections'] ?? [];
    $keys = [];
    foreach ($sectionIdxs as $i) {
        foreach ($sections[(int)$i]['keys'] ?? [] as $k) {
            $keys[] = $k;
        }
    }
    $v = is_array($post['v'] ?? null) ? $post['v'] : [];
    $clear = is_array($post['clear'] ?? null) ? $post['clear'] : [];
    $errors = [];
    $values = [];
    $old = [];
    $cleared = [];
    foreach ($keys as $k) {
        $def = $fields[$k];
        $old[$k] = setting($k);
        if ($def['type'] === 'image') {
            continue; // files are handled by their own upload requests
        }
        if (!admin_can('settings', admin_core_settings_key_level($k))) {
            continue;
        }
        if ($def['type'] === 'secret') {
            if (!empty($clear[$k])) {
                $cleared[] = $k;
                continue;
            }
            if (trim((string)($v[$k] ?? '')) === '') {
                continue;
            }
        } elseif (!array_key_exists($k, $v)) {
            if ($def['type'] === 'multiselect') {
                $values[$k] = [];
            }
            continue;
        }
        [$val, $err] = admin_core_settings_normalize($k, $def, $v[$k]);
        if ($err !== null) {
            $errors[$k] = $err;
            continue;
        }
        if ($def['type'] === 'color' && strcasecmp((string)$val, (string)setting($k)) === 0) {
            continue; // same colour, only the letter case differs: keep the stored value
        }
        $values[$k] = $val;
    }
    $res = settings_save($values, $adminId);
    $errors += $res['errors'];
    foreach ($cleared as $k) {
        setting_reset($k);
        $res['changed'][] = $k;
        $res['saved']++;
    }
    foreach ($res['changed'] as $i => $k) {
        if ($i >= 25) {
            admin_log('settings.change', 'setting', null, '… and ' . (count($res['changed']) - 25) . ' more');
            break;
        }
        $new = in_array($k, $cleared, true) ? '' : setting($k);
        admin_log('settings.change', 'setting', null, $k . ': ' . admin_core_setting_log_value($k, $old[$k] ?? '') . ' -> ' . (in_array($k, $cleared, true) ? '(removed)' : admin_core_setting_log_value($k, $new)));
    }
    if ($res['saved']) {
        cache_clear('theme');
        cache_clear('landing');
        cache_clear('icons');
    }
    $custom = [];
    $secrets = [];
    foreach ($keys as $k) {
        $custom[$k] = !setting_is_default($k);
        if ($fields[$k]['type'] === 'secret') {
            $secrets[$k] = (string)setting($k) !== '';
        }
    }
    return ['saved' => $res['saved'], 'errors' => $errors, 'keys' => $keys, 'custom' => $custom, 'secrets' => $secrets];
}

/**
 * Put settings back to their defaults. Returns [key => typed default].
 */
function admin_core_settings_reset(array $keys, int $adminId): array
{
    $fields = settings_schema()['fields'];
    $out = [];
    $done = [];
    foreach ($keys as $k) {
        if (!isset($fields[$k]) || !admin_can('settings', admin_core_settings_key_level($k))) {
            continue;
        }
        $def = $fields[$k];
        if ($def['type'] === 'image') {
            if (!setting_is_default($k)) {
                // drop the saved choice (an upload, or "removed"), so the built-in picture comes back if the setting has one
                $old = (string)setting($k);
                setting_reset($k);
                cache_clear('landing');
                admin_core_setting_file_cleanup($old, $k);
                admin_log('settings.upload', 'setting', null, $k . ': ' . ($old !== '' ? $old : '(removed)') . ' -> (default)');
            }
        } else {
            if (!setting_is_default($k)) {
                setting_reset($k);
            }
        }
        $out[$k] = setting_cast($def, $def['default']);
        $done[] = $k;
    }
    if ($done) {
        cache_clear('theme');
        cache_clear('landing');
        admin_log('settings.reset', 'setting', null, implode(', ', array_slice($done, 0, 25)));
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

function admin_core_setting_input_value(array $def, $value): string
{
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_array($value)) {
        return (string)json_encode($value);
    }
    return (string)$value;
}

/** Controls that sit side by side as a segmented choice instead of a dropdown. */
function admin_core_setting_is_segmented(array $def): bool
{
    $opts = $def['options'] ?? [];
    if (count($opts) < 2 || count($opts) > 4) {
        return false;
    }
    $len = 0;
    foreach ($opts as $label) {
        $len += mb_strlen((string)$label);
    }
    return $len <= 34;
}

/**
 * Render one setting row. $ctx: readonly (bool), custom (bool), secret_set (bool), depends (array)
 */
function admin_core_setting_field(string $key, array $def, $value, array $ctx = []): string
{
    $ro = !empty($ctx['readonly']);
    $type = (string)$def['type'];
    $label = (string)$def['label'];
    $help = (string)($def['help'] ?? '');
    $id = 'f-' . $key;
    $name = 'v[' . $key . ']';
    $dis = $ro ? ' disabled' : '';
    $default = setting_cast($def, $def['default']);
    $depends = $def['depends'] ?? ($ctx['depends'] ?? []);
    $c = '';

    switch ($type) {
        case 'bool':
            $c = '<div class="flex items-center gap-3"><label class="switch"><input type="hidden" name="' . e($name) . '" value="0"' . $dis . '><input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"' . checked((bool)$value) . $dis . ' data-state-label="#' . e($id) . '-state"><span></span></label>'
                . '<span class="text-sm text-ink-2" id="' . e($id) . '-state">' . ($value ? 'On' : 'Off') . '</span></div>';
            break;
        case 'select':
            if (admin_core_setting_is_segmented($def)) {
                $c = '<div class="set-seg" role="radiogroup" aria-label="' . e($label) . '">';
                foreach ($def['options'] as $ov => $ol) {
                    $c .= '<label class="set-seg-opt"><input type="radio" name="' . e($name) . '" value="' . e((string)$ov) . '"' . checked((string)$value === (string)$ov) . $dis . '><span>' . e((string)$ol) . '</span></label>';
                }
                $c .= '</div>';
            } else {
                $c = '<select class="select" id="' . e($id) . '" name="' . e($name) . '"' . $dis . '>';
                foreach ($def['options'] as $ov => $ol) {
                    $c .= '<option value="' . e((string)$ov) . '"' . selected((string)$value, (string)$ov) . '>' . e((string)$ol) . '</option>';
                }
                $c .= '</select>';
            }
            break;
        case 'multiselect':
            $c = '<div class="flex flex-wrap gap-2" role="group" aria-label="' . e($label) . '"><input type="hidden" name="' . e($name) . '[]" value=""' . $dis . '>';
            $vals = array_map('strval', is_array($value) ? $value : []);
            foreach ($def['options'] as $ov => $ol) {
                $c .= '<label><input type="checkbox" class="chip-input" name="' . e($name) . '[]" value="' . e((string)$ov) . '"' . checked(in_array((string)$ov, $vals, true)) . $dis . '><span class="chip">' . e((string)$ol) . '</span></label>';
            }
            $c .= '</div>';
            break;
        case 'number':
            $min = $def['min'] ?? null;
            $max = $def['max'] ?? null;
            $step = $def['step'] ?? 1;
            $attrs = ($min !== null ? ' min="' . e((string)$min) . '"' : '') . ($max !== null ? ' max="' . e((string)$max) . '"' : '') . ' step="' . e((string)$step) . '"';
            $input = '<input class="input set-num-input" type="number" inputmode="decimal" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$value) . '"' . $attrs . $dis . '>';
            if ($min !== null && $max !== null && ($max - $min) <= 500 && !$ro) {
                $c = '<div class="set-num"><input type="range" class="range" min="' . e((string)$min) . '" max="' . e((string)$max) . '" step="' . e((string)$step) . '" value="' . e((string)$value) . '" data-range-for="' . e($id) . '" aria-label="' . e($label) . ' slider" tabindex="-1">' . $input . '</div>';
            } else {
                $c = '<div class="set-num">' . $input . '</div>';
            }
            break;
        case 'color':
            $hex = preg_match('/^#[0-9a-f]{6}$/i', (string)$value) ? (string)$value : (string)$def['default'];
            $c = '<div class="set-color"><input type="color" class="set-color-pick" value="' . e(preg_match('/^#[0-9a-f]{6}$/i', $hex) ? strtolower($hex) : '#000000') . '" aria-label="Pick a colour for ' . e($label) . '"' . $dis . '>'
                . '<input type="text" class="input set-color-hex" id="' . e($id) . '" name="' . e($name) . '" value="' . e($hex) . '" maxlength="7" spellcheck="false" autocomplete="off" pattern="#[0-9A-Fa-f]{6}"' . $dis . '>';
            if (in_array($key, ['color_primary', 'color_secondary', 'color_tertiary'], true)) {
                $c .= '<div class="set-ramp" data-ramp-for="' . e($key) . '" aria-hidden="true"></div>';
            }
            $c .= '</div>';
            break;
        case 'image':
            $rules = admin_core_setting_file_rules($key, $def);
            $cur = (string)$value;
            $isVideo = $rules['kind'] === 'video';
            $accept = $isVideo ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp' . ($rules['svg'] ? ',image/svg+xml' : '');
            $bg = in_array($key, ['logo_white', 'logo_dark', 'logo_header_scrolled_dark', 'logo_footer_dark', 'logo_mobile_dark', 'logo_mobile_white', 'logo_header_member_dark', 'logo_app_header_dark', 'logo_sidepanel_dark', 'logo_sidepanel_host_dark', 'logo_menu_dark', 'logo_auth', 'logo_auth_dark'], true) ? ' is-dark' : '';
            $preview = $cur !== ''
                ? ($isVideo ? '<video src="' . e(upload_url($cur)) . '" muted loop playsinline preload="metadata"></video>' : '<img src="' . e(upload_url($cur)) . '" alt="">')
                : '<span class="set-image-empty">' . icon($isVideo ? 'video' : 'image', 'w-6 h-6') . '</span>';
            $c = '<div class="set-image" data-key="' . e($key) . '" data-kind="' . e($rules['kind']) . '" data-max-mb="' . (int)$rules['mb'] . '" data-path="' . e($cur) . '" data-url="' . e($cur !== '' ? upload_url($cur) : '') . '">'
                . '<div class="set-image-preview' . $bg . '" data-img-preview>' . $preview . '</div>'
                . '<div class="set-image-body"><p class="set-image-name" data-img-name>' . ($cur !== '' ? e(basename($cur)) : 'Nothing uploaded') . '</p>'
                . '<p class="help !mt-0">' . e($rules['label']) . '</p>'
                . ($ro ? '' : '<div class="flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-outline btn-sm" data-img-upload>' . icon('upload', 'w-4 h-4') . '<span data-img-upload-label>' . ($cur !== '' ? 'Replace' : 'Upload') . '</span></button>'
                    . '<button type="button" class="btn btn-outline btn-sm btn-media" data-img-pick>' . icon('images', 'w-4 h-4') . ' Choose from media</button>'
                    . '<button type="button" class="btn btn-ghost btn-sm" data-img-remove' . ($cur === '' ? ' hidden' : '') . '>' . icon('trash-2', 'w-4 h-4') . ' Remove</button></div>'
                    . '<input type="file" class="sr-only" accept="' . e($accept) . '" data-img-input tabindex="-1" aria-label="Choose file for ' . e($label) . '">')
                . '</div></div>';
            break;
        case 'secret':
            $set = !empty($ctx['secret_set']);
            $c = '<div class="set-secret"><div class="flex items-center gap-2"><input class="input" type="password" id="' . e($id) . '" name="' . e($name) . '" value="" autocomplete="new-password" placeholder="' . ($set ? 'Saved. Type to replace it' : 'Not set') . '"' . $dis . '>'
                . '<span class="badge ' . ($set ? 'badge-success' : '') . '" data-secret-state>' . ($set ? 'Set' : 'Not set') . '</span></div>'
                . ($set && !$ro ? '<label class="check text-xs mt-2"><input type="checkbox" name="clear[' . e($key) . ']" value="1"><span>Remove the saved value</span></label>' : '') . '</div>';
            break;
        case 'textarea':
            $max = (int)($def['max'] ?? 0);
            $c = '<textarea class="textarea' . ($key === 'custom_css' ? ' font-mono text-sm' : '') . '" id="' . e($id) . '" name="' . e($name) . '" rows="' . (int)($def['rows'] ?? 4) . '"' . ($max ? ' maxlength="' . $max . '" data-counter="#' . e($id) . '-n"' : '') . $dis . '>' . e((string)$value) . '</textarea>'
                . ($max ? '<p class="help text-right" id="' . e($id) . '-n">' . mb_strlen((string)$value) . ' / ' . $max . '</p>' : '');
            break;
        default: // text, email, url
            $max = (int)($def['max'] ?? 0);
            $htype = $type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text');
            $c = '<input class="input" type="' . $htype . '" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$value) . '"' . ($max ? ' maxlength="' . $max . '"' : '') . ($htype === 'text' ? ' autocomplete="off"' : '') . $dis . '>';
            if ($max >= 40 && !in_array($key, ['hero_cta_primary_url', 'hero_cta_secondary_url'], true)) {
                $c .= '<p class="help text-right" id="' . e($id) . '-n" data-count-for="' . e($id) . '" data-max="' . $max . '">' . mb_strlen((string)$value) . ' / ' . $max . '</p>';
            }
    }

    $defaultText = '';
    if ($type === 'bool') {
        $defaultText = $default ? 'On' : 'Off';
    } elseif ($type === 'select') {
        $defaultText = (string)($def['options'][$default] ?? $default);
    } elseif ($type === 'multiselect') {
        $defaultText = implode(', ', array_map(fn($x) => (string)($def['options'][$x] ?? $x), (array)$default)) ?: 'None';
    } elseif (in_array($type, ['image', 'secret'], true)) {
        $defaultText = $type === 'image' ? ((string)$default !== '' ? 'The built-in picture' : 'Nothing uploaded') : 'Not set';
    } else {
        $defaultText = (string)$default === '' ? 'Empty' : str_limit((string)$default, 40);
    }

    $keywords = mb_strtolower($label . ' ' . $help . ' ' . $key . ' ' . (string)($def['section'] ?? ''));
    $out = '<div class="setting-row set-row" id="set-' . e($key) . '" data-key="' . e($key) . '" data-type="' . e($type) . '" data-custom="' . (!empty($ctx['custom']) ? '1' : '0') . '"'
        . ' data-keywords="' . e($keywords) . '" data-default="' . json_attr(is_array($default) ? $default : (string)(is_bool($default) ? (int)$default : $default)) . '"'
        . ($depends ? ' data-depends="' . json_attr($depends) . '"' : '') . '>';
    $out .= '<div class="set-meta"><label class="setting-label" ' . (in_array($type, ['bool', 'text', 'textarea', 'number', 'color', 'select', 'secret', 'email', 'url'], true) && !($type === 'select' && admin_core_setting_is_segmented($def)) ? 'for="' . e($id) . '"' : '') . '>'
        . e($label) . '<span class="set-dot" role="img" title="Changed from the default" aria-label="Changed from the default"></span></label>'
        . ($help !== '' ? '<p class="setting-help">' . e($help) . '</p>' : '')
        . '<code class="set-key">' . e($key) . '</code></div>';
    $out .= '<div class="set-control">' . $c
        . '<div class="set-foot"><span class="error-text set-error" data-error role="alert"></span>'
        . ($ro ? '' : '<button type="button" class="set-reset" data-reset="' . e($key) . '" data-default-text="' . e($defaultText) . '" title="Back to ' . e($defaultText) . '">' . icon('rotate-ccw', 'w-3.5 h-3.5') . ' Reset to default<span class="sr-only">: ' . e($defaultText) . '</span></button>')
        . '</div></div></div>';
    return $out;
}

/** One settings section as a form. $idxs are the schema section indexes it saves. */
function admin_core_settings_section(string $tab, array $idxs, array $titleOverride = [], bool $readonly = false): string
{
    $schema = settings_schema();
    $sections = $schema['tabs'][$tab]['sections'];
    $fields = $schema['fields'];
    $depMap = admin_core_settings_depends();
    $first = $sections[$idxs[0]];
    $title = $titleOverride['title'] ?? $first['title'];
    $note = $titleOverride['note'] ?? $first['note'];
    $anchor = 'sec-' . $tab . '-' . $idxs[0];
    $sumPrefix = '';
    if ($first['title'] === 'Ranking weights') {
        $sumPrefix = 'w_';
    } elseif ($first['title'] === 'Profile completeness weights') {
        $sumPrefix = 'cw_';
    }
    $rows = '';
    $allKeys = [];
    $inner = $titleOverride['inner'] ?? null;
    if ($inner === null) {
        foreach ($idxs as $i) {
            foreach ($sections[$i]['keys'] as $key) {
                $def = $fields[$key];
                $allKeys[] = $key;
                $needLevel = admin_core_settings_key_level($key);
                $rows .= admin_core_setting_field($key, $def, setting($key), [
                    'readonly' => $readonly || !admin_can('settings', $needLevel),
                    'custom' => !setting_is_default($key),
                    'secret_set' => $def['type'] === 'secret' ? (string)setting($key) !== '' : false,
                    'depends' => $depMap[$key] ?? [],
                ]);
            }
        }
    } else {
        $rows = $inner;
        foreach ($idxs as $i) {
            foreach ($sections[$i]['keys'] as $key) {
                $allKeys[] = $key;
            }
        }
    }
    $customCount = 0;
    foreach ($allKeys as $k) {
        if (!setting_is_default($k)) {
            $customCount++;
        }
    }
    $out = '<form class="admin-card set-section" id="' . e($anchor) . '" method="post" action="' . e(url('/admin/settings/' . $tab)) . '" data-settings-form data-tab="' . e($tab) . '"' . ($sumPrefix !== '' ? ' data-sum-prefix="' . e($sumPrefix) . '" data-sum-target="100"' : '') . ' novalidate>';
    $out .= csrf_field();
    foreach ($idxs as $i) {
        $out .= '<input type="hidden" name="sec[]" value="' . (int)$i . '">';
    }
    $out .= '<div class="admin-card-head set-section-head"><div class="min-w-0"><h3 class="admin-card-title">' . e($title) . '</h3>'
        . ($note !== '' ? '<p class="text-xs text-ink-3 mt-1">' . e($note) . '</p>' : '')
        . ($sumPrefix !== '' ? '<p class="text-xs mt-1 set-sum" data-sum-out></p>' : '') . '</div>'
        . '<div class="flex items-center gap-2 shrink-0"><span class="set-dirty" data-dirty hidden>Unsaved changes</span>'
        . ($readonly ? '<span class="badge">' . icon('lock', 'w-3 h-3') . ' View only</span>' : '<button type="button" class="btn btn-ghost btn-xs" data-reset-section title="Reset every setting in this section">' . icon('rotate-ccw', 'w-3.5 h-3.5') . '<span class="hidden sm:inline"> Reset section</span></button>'
            . '<button type="submit" class="btn btn-primary btn-sm" data-save>' . icon('save', 'w-4 h-4') . ' Save</button>')
        . '</div></div>';
    $out .= '<div class="set-rows px-5 md:px-6" data-section-keys="' . e(json_encode($allKeys)) . '">' . $rows . '</div>';
    $out .= '</form>';
    return $out;
}

/** The contact visibility matrix: phone/WhatsApp/email/Instagram by member type. */
function admin_core_settings_matrix_rows(bool $readonly): string
{
    $tiers = ['free' => 'Free', 'premium' => 'Premium', 'vip' => 'VIP', 'matched' => 'Matched'];
    $fields = settings_schema()['fields'];
    $ro = $readonly || !admin_can('settings', 'edit');
    $out = '<div class="set-matrix-wrap"><table class="set-matrix"><thead><tr><th scope="col">Contact detail</th>';
    foreach ($tiers as $t => $tl) {
        $out .= '<th scope="col">' . e($tl) . '</th>';
    }
    $out .= '</tr></thead><tbody>';
    foreach (CONTACT_FIELDS as $f => $fl) {
        $out .= '<tr><th scope="row">' . e($fl) . '</th>';
        foreach ($tiers as $t => $tl) {
            $k = 'contact_' . $f . '_' . $t;
            if (!isset($fields[$k])) {
                $out .= '<td></td>';
                continue;
            }
            $vis = (string)setting($k) === 'visible';
            $out .= '<td class="set-row" data-key="' . e($k) . '" data-type="matrix" data-custom="' . (setting_is_default($k) ? '0' : '1') . '" data-default="' . json_attr((string)$fields[$k]['default']) . '" data-keywords="' . e(mb_strtolower($fl . ' ' . $tl . ' contact visibility ' . $k)) . '">'
                . '<label class="set-matrix-cell"><input type="hidden" name="v[' . e($k) . ']" value="hidden"' . ($ro ? ' disabled' : '') . '><input type="checkbox" name="v[' . e($k) . ']" value="visible"' . checked($vis) . ($ro ? ' disabled' : '') . ' data-matrix-cell>'
                . '<span class="set-matrix-box" aria-hidden="true">' . icon('eye', 'w-4 h-4') . '</span><span class="sr-only">' . e($fl . ' visible to ' . $tl . ' members') . '</span></label><span class="error-text set-error" data-error role="alert"></span></td>';
        }
        $out .= '</tr>';
    }
    $out .= '</tbody></table></div><p class="help mt-3 px-1">Ticked means the detail is shown to that group. "Matched" applies to anyone the member has matched with, whatever their plan. Members can also hide their details on their own profile.</p>';
    return $out;
}
