<?php
/**
 * Settings engine. Values come from site_settings (overrides only) and fall
 * back to the defaults declared in includes/settings_schema.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/settings_schema.php';

function settings_overrides(bool $reload = false): array
{
    static $cache = null;
    if ($reload) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    if (!is_file(config_file())) {
        return $cache;
    }
    try {
        $cache = pairs('SELECT setting_key, setting_value FROM site_settings');
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

function setting_cast(array $def, $raw)
{
    switch ($def['type']) {
        case 'bool':
            return in_array($raw, [1, '1', true, 'true', 'on', 'yes'], true);
        case 'number':
            if ($raw === '' || $raw === null) {
                $raw = $def['default'];
            }
            $n = (float)$raw;
            return (isset($def['step']) && (float)$def['step'] != (int)$def['step']) || fmod($n, 1.0) !== 0.0 ? $n : (int)$n;
        case 'multiselect':
            if (is_array($raw)) {
                return $raw;
            }
            $d = json_decode((string)$raw, true);
            return is_array($d) ? $d : [];
        default:
            return (string)$raw;
    }
}

/** Typed value of a setting. Unknown keys return $default. */
function setting(string $key, $default = null)
{
    $fields = settings_schema()['fields'];
    if (!isset($fields[$key])) {
        $o = settings_overrides();
        return $o[$key] ?? $default;
    }
    $o = settings_overrides();
    $def = $fields[$key];
    if (array_key_exists($key, $o) && $o[$key] !== null) {
        return setting_cast($def, $o[$key]);
    }
    return setting_cast($def, $def['default']);
}

function setting_is_default(string $key): bool
{
    $o = settings_overrides();
    if (!array_key_exists($key, $o) || $o[$key] === null) {
        return true;
    }
    // A saved row that holds exactly the built-in value (the installer and upgrades write rows like that) is still "default".
    $def = settings_schema()['fields'][$key] ?? null;
    if ($def === null || ($def['type'] ?? '') === 'image') {
        return false;
    }
    $norm = fn($v) => is_string($v) ? str_replace("\r\n", "\n", $v) : $v;
    return $norm(setting_cast($def, $o[$key])) === $norm(setting_cast($def, $def['default']));
}

/** All settings as typed values (for the admin form). */
function settings_all(): array
{
    $out = [];
    foreach (settings_schema()['fields'] as $key => $_) {
        $out[$key] = setting($key);
    }
    return $out;
}

/**
 * Validate a raw submitted value against the schema.
 * @return array [bool ok, mixed storedValue|string error]
 */
function setting_validate(string $key, $raw): array
{
    $fields = settings_schema()['fields'];
    if (!isset($fields[$key])) {
        return [false, 'Unknown setting'];
    }
    $def = $fields[$key];
    $label = $def['label'];
    switch ($def['type']) {
        case 'bool':
            return [true, in_array($raw, [1, '1', 'on', 'true', 'yes', true], true) ? '1' : '0'];
        case 'number':
            if ($raw === '' || $raw === null) {
                return [true, (string)$def['default']];
            }
            if (!is_numeric($raw)) {
                return [false, $label . ' must be a number.'];
            }
            $n = $raw + 0;
            if (isset($def['min']) && $n < $def['min']) {
                return [false, $label . ' must be at least ' . $def['min'] . '.'];
            }
            if (isset($def['max']) && $n > $def['max']) {
                return [false, $label . ' must be at most ' . $def['max'] . '.'];
            }
            return [true, (string)$n];
        case 'select':
            $raw = (string)$raw;
            if (!array_key_exists($raw, $def['options'] ?? [])) {
                return [false, $label . ' has an invalid choice.'];
            }
            return [true, $raw];
        case 'multiselect':
            $vals = is_array($raw) ? array_values(array_map('strval', $raw)) : [];
            $vals = array_values(array_intersect($vals, array_map('strval', array_keys($def['options'] ?? []))));
            return [true, json_encode($vals)];
        case 'color':
            $raw = trim((string)$raw);
            if ($raw === '') {
                return [true, ''];
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $raw)) {
                return [false, $label . ' must be a colour like #E8523F.'];
            }
            return [true, strtolower($raw)];
        case 'email':
            $raw = trim((string)$raw);
            if ($raw !== '' && !valid_email($raw)) {
                return [false, $label . ' must be a valid email address.'];
            }
            return [true, $raw];
        case 'url':
            $raw = trim((string)$raw);
            if ($raw !== '' && !preg_match('#^https?://[^\s]+$#i', $raw)) {
                return [false, $label . ' must start with http:// or https://.'];
            }
            return [true, $raw];
        case 'image':
            $raw = trim((string)$raw);
            return [true, preg_match('#^[A-Za-z0-9_./-]*$#', $raw) ? $raw : ''];
        default: // text, textarea, secret
            $raw = is_array($raw) ? '' : trim((string)$raw);
            if (isset($def['max']) && mb_strlen($raw) > (int)$def['max']) {
                return [false, $label . ' is too long (max ' . $def['max'] . ' characters).'];
            }
            return [true, $raw];
    }
}

/**
 * Save a batch of settings. Returns ['saved' => int, 'errors' => [key => message]].
 * Secrets left blank keep their stored value.
 */
function settings_save(array $values, ?int $adminId = null): array
{
    $fields = settings_schema()['fields'];
    $errors = [];
    $saved = 0;
    $changed = [];
    foreach ($values as $key => $raw) {
        if (!isset($fields[$key])) {
            continue;
        }
        if ($fields[$key]['type'] === 'secret' && trim((string)$raw) === '') {
            continue;
        }
        [$ok, $v] = setting_validate($key, $raw);
        if (!$ok) {
            $errors[$key] = $v;
            continue;
        }
        $default = setting_cast($fields[$key], $fields[$key]['default']);
        $stored = array_key_exists($key, settings_overrides()) ? settings_overrides()[$key] : null;
        if ($stored === $v) {
            continue;
        }
        if ($stored === null && setting_cast($fields[$key], $v) === $default) {
            continue; // unchanged from default, keep the table lean
        }
        q('INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (:k, :v, :u)
           ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)',
            ['k' => $key, 'v' => $v, 'u' => $adminId]);
        $saved++;
        $changed[] = $key;
    }
    settings_overrides(true);
    if ($saved) {
        cache_clear('theme');
        cache_clear('landing');
        admin_log('settings.save', 'settings', null, implode(', ', array_slice($changed, 0, 30)));
    }
    return ['saved' => $saved, 'errors' => $errors, 'changed' => $changed];
}

/** Programmatic single-value save (used by logo uploads etc.). */
function setting_set(string $key, string $value, ?int $adminId = null): void
{
    q('INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (:k, :v, :u)
       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)',
        ['k' => $key, 'v' => $value, 'u' => $adminId]);
    settings_overrides(true);
    cache_clear('theme');
}

function setting_reset(string $key): void
{
    delete_rows('site_settings', ['setting_key' => $key]);
    settings_overrides(true);
    cache_clear('theme');
}

/** Lines of a textarea setting as a clean list. */
function setting_lines(string $key): array
{
    $v = (string)setting($key);
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $v) ?: []), fn($x) => $x !== ''));
}

function setting_csv(string $key): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string)setting($key))), fn($x) => $x !== ''));
}

/** Convenience: setting is one of the "on" values for a visibility toggle. */
function setting_on(string $key): bool
{
    return (bool)setting($key);
}

function site_name(): string
{
    return (string)setting('site_name') ?: 'ConnectHub';
}
