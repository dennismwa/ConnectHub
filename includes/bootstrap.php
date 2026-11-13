<?php
/**
 * Application bootstrap: environment, error handling, includes, session.
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('CH_VERSION', '1.0.0');
define('CH_CLI', PHP_SAPI === 'cli');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Africa/Nairobi');

require_once ROOT . '/includes/helpers.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/settings.php';
require_once ROOT . '/includes/security.php';
require_once ROOT . '/includes/plans.php';
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/geo.php';
require_once ROOT . '/includes/privacy.php';
require_once ROOT . '/includes/notify.php';
require_once ROOT . '/includes/mail.php';
require_once ROOT . '/includes/upload.php';
require_once ROOT . '/includes/view.php';
require_once ROOT . '/includes/router.php';
require_once ROOT . '/includes/migrate.php';

// Optional module helpers (includes/lib/*.php), loaded in name order.
foreach (glob(ROOT . '/includes/lib/*.php') ?: [] as $lib) {
    require_once $lib;
}

// Errors: never show raw PHP errors to visitors.
error_reporting(E_ALL);
ini_set('display_errors', is_debug() ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/storage/logs/php.log');
set_error_handler(function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    app_log('php', "[$no] $str @ " . str_replace(ROOT, '', $file) . ":$line");
    return !is_debug();
});
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_log('error', 'FATAL ' . $e['message'] . ' @ ' . str_replace(ROOT, '', $e['file']) . ':' . $e['line']);
        if (!CH_CLI && !headers_sent()) {
            http_response_code(500);
            if (!is_debug()) {
                echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title><body style="font-family:system-ui;padding:48px;text-align:center"><h1>Something went wrong</h1><p>Please try again in a few minutes.</p></body>';
            }
        }
    }
});

// Not installed yet: send visitors to the installer.
if (!is_file(config_file())) {
    if (!CH_CLI && strpos(request_path(), '/install') !== 0 && !defined('CH_INSTALLER')) {
        redirect('/install/');
    }
    return;
}

// Time zone from settings (falls back to Nairobi).
function apply_timezone(): void
{
    $tz = (string)setting('timezone');
    if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true) && $tz !== date_default_timezone_get()) {
        date_default_timezone_set($tz);
        try {
            db()->exec("SET time_zone = '" . (new DateTime('now'))->format('P') . "'");
        } catch (Throwable $e) {
        }
    }
}

/**
 * Saved pages, menus and counters from the previous release can have a different shape from what the new code expects, which
 * showed up as empty or half-drawn admin sections right after an update. When the code changes, start with a clean cache.
 */
function cache_code_guard(): void
{
    $stamp = md5(CH_VERSION . '|' . CH_SCHEMA_VERSION . '|' . (int)@filemtime(ROOT . '/includes/settings_schema.php') . '|' . (int)@filemtime(ROOT . '/app/admin/dashboard.php') . '|' . (int)@filemtime(ROOT . '/includes/lib/landing.php'));
    $f = ROOT . '/storage/cache/code_stamp';
    if (is_file($f) && trim((string)@file_get_contents($f)) === $stamp) {
        return;
    }
    foreach (glob(ROOT . '/storage/cache/*.cache') ?: [] as $c) {
        $b = basename($c);
        if (strpos($b, 'cron_task_') === 0 || strpos($b, 'cron_last_run') === 0) {
            continue;
        }
        @unlink($c);
    }
    if (is_dir(dirname($f))) {
        @file_put_contents($f, $stamp);
    }
    if (function_exists('settings_overrides')) {
        settings_overrides(true);
    }
}

try {
    db();
    apply_timezone();
    migrate_if_needed();
    cache_code_guard();
} catch (Throwable $e) {
    app_log('error', 'Database connection failed: ' . $e->getMessage());
    if (!CH_CLI) {
        http_response_code(503);
        echo '<!doctype html><meta charset="utf-8"><title>We will be right back</title><body style="font-family:system-ui;padding:48px;text-align:center"><h1>We will be right back</h1><p>The site cannot reach its database right now.' . (is_debug() ? '<br><small>' . e($e->getMessage()) . '</small>' : '') . '</p></body>';
        exit;
    }
    throw $e;
}

if (!CH_CLI) {
    // Optional: send plain-http visitors to https (Settings > General > Connection & security).
    if (setting('force_https') && !is_https() && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) && !empty($_SERVER['HTTP_HOST'])) {
        $h = strtolower(preg_replace('/:\d+$/', '', (string)$_SERVER['HTTP_HOST']));
        if ($h !== 'localhost' && !filter_var($h, FILTER_VALIDATE_IP) && preg_match('/^[a-z0-9.\-]+$/', $h)) {
            header('Location: https://' . $h . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }
    }
    ob_start();
    session_boot();
    site_note_scheme();
}
