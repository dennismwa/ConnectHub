<?php
/**
 * Database upgrades.
 *
 * database/schema + database/seed describe the first release. Everything added after that lives in
 * database/migrations/NNNN_name.php (each file returns a function). The migrations run once, in order, the first time the
 * site is opened after new files are uploaded, so an existing site upgrades itself without re-importing anything.
 * CH_SCHEMA_VERSION must equal the number of the newest migration file (tools/audit.php checks it).
 */

declare(strict_types=1);

const CH_SCHEMA_VERSION = 33;

function mig_has_table(string $table): bool
{
    return (bool)val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t', ['t' => $table], 0);
}

function mig_has_column(string $table, string $column): bool
{
    return (bool)val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c', ['t' => $table, 'c' => $column], 0);
}

function mig_has_index(string $table, string $index): bool
{
    return (bool)val('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :t AND index_name = :i', ['t' => $table, 'i' => $index], 0);
}

/** ALTER TABLE ... ADD COLUMN, skipped when the column already exists. $definition is e.g. "TINYINT(1) NOT NULL DEFAULT 0 AFTER name". */
function mig_add_column(string $table, string $column, string $definition): void
{
    if (!mig_has_column($table, $column)) {
        db()->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }
}

function mig_add_index(string $table, string $index, string $columns): void
{
    if (!mig_has_index($table, $index)) {
        db()->exec('ALTER TABLE `' . $table . '` ADD INDEX `' . $index . '` (' . $columns . ')');
    }
}

/** Run a statement or several (separated by semicolons at line ends). */
function mig_exec(string $sql): void
{
    foreach (sql_split($sql) as $stmt) {
        db()->exec($stmt);
    }
}

/** Insert a setting only when the admin has never saved it, so an upgrade never overwrites a choice. */
function mig_setting_default(string $key, string $value): void
{
    q('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (:k, :v)', ['k' => $key, 'v' => $value]);
}

function migrate_files(): array
{
    $files = glob(ROOT . '/database/migrations/[0-9][0-9][0-9][0-9]_*.php') ?: [];
    sort($files);
    $out = [];
    foreach ($files as $f) {
        $out[(int)substr(basename($f), 0, 4)] = $f;
    }
    return $out;
}

/** Apply every migration that has not run yet. Returns the numbers applied, or null when another request holds the upgrade lock. */
function migrate_run(): ?array
{
    db()->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (version)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $done = array_map('intval', col('SELECT version FROM schema_migrations'));
    $applied = [];
    $got = (int)val("SELECT GET_LOCK('ch_migrate', 30)", [], 0);
    if (!$got) {
        return null; // someone else is still upgrading; this request tries again next time instead of running the steps twice
    }
    try {
        // another request may have finished the work while this one waited for the lock
        $done = array_map('intval', col('SELECT version FROM schema_migrations'));
        foreach (migrate_files() as $n => $file) {
            if (in_array($n, $done, true)) {
                continue;
            }
            $fn = require $file;
            if (is_callable($fn)) {
                $fn();
            }
            q('INSERT IGNORE INTO schema_migrations (version, name) VALUES (:v, :n)', ['v' => $n, 'n' => basename($file, '.php')]);
            $applied[] = $n;
        }
    } finally {
        q("SELECT RELEASE_LOCK('ch_migrate')");
    }
    if ($applied) {
        settings_overrides(true);
        if (function_exists('cache_clear')) {
            cache_clear();
        }
    }
    return $applied;
}

/** Cheap check on every request: a marker file says this code version has already been applied to this database. */
function migrate_if_needed(): void
{
    // The marker is per database: importing a different database next to the same files must still run the upgrades.
    $flag = ROOT . '/storage/cache/schema_' . CH_SCHEMA_VERSION . '_' . substr(md5((string)val('SELECT DATABASE()', [], '') . '|' . (string)(((array)config('db', []))['host'] ?? '')), 0, 8) . '.ok';
    if (is_file($flag)) {
        if (time() - (int)@filemtime($flag) < 600) {
            return;
        }
        // look again now and then: a database restored from an older backup needs the upgrades applied again
        try {
            if ((int)val('SELECT COUNT(*) FROM schema_migrations', [], 0) >= count(migrate_files())) {
                @touch($flag);
                return;
            }
        } catch (Throwable $e) {
            // no migrations table: fall through and upgrade
        }
        @unlink($flag);
    }
    try {
        if (migrate_run() !== null) {
            @file_put_contents($flag, (string)time());
        }
    } catch (Throwable $e) {
        app_log('error', 'Database upgrade failed: ' . $e->getMessage());
    }
}
