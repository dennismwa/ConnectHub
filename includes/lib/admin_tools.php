<?php
/**
 * Admin tools: caches, backups, demo data, blurred photo copies, orphan upload scan, server facts and manual cron runs.
 * Nothing here shells out. Every function is safe to call from a request and reports problems as return values.
 */

const ADMIN_TOOLS_PHRASE = 'DEMO';

// ---------------------------------------------------------------------------
// Caches
// ---------------------------------------------------------------------------

/**
 * Empty storage/cache and reset PHP's opcode cache. The "last ran" markers of scheduled jobs are kept, otherwise every
 * daily and weekly job (including the weekly e-mail digest) would run again at the next cron tick.
 * @return int number of cache files removed
 */
function admin_tools_clear_cache(): int
{
    $n = 0;
    foreach (glob(ROOT . '/storage/cache/*.cache') ?: [] as $f) {
        $base = basename($f);
        if (strpos($base, 'cron_task_') === 0 || strpos($base, 'cron_last_run') === 0) {
            continue;
        }
        if (@unlink($f)) {
            $n++;
        }
    }
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    settings_overrides(true);
    return $n;
}

/** Rebuild the generated theme CSS from the current settings. Returns its size in bytes. */
function admin_tools_regenerate_theme(): int
{
    cache_clear('theme');
    cache_clear('nav_');
    settings_overrides(true);
    return strlen(theme_css());
}

// ---------------------------------------------------------------------------
// Demo data
// ---------------------------------------------------------------------------

function admin_tools_demo_domain(): string
{
    return defined('DEMO_DOMAIN') ? (string)DEMO_DOMAIN : 'demo.connecthub.test';
}

/** Counts of demo members. Admin accounts are never counted or removed, even when they use the demo e-mail domain. */
function admin_tools_demo_counts(): array
{
    $like = '%@' . admin_tools_demo_domain();
    $c = rows("SELECT role, COUNT(*) AS n FROM users WHERE email LIKE :d GROUP BY role", ['d' => $like]);
    $by = [];
    foreach ($c as $r) {
        $by[$r['role']] = (int)$r['n'];
    }
    return ['members' => $by['user'] ?? 0, 'hosts' => $by['host'] ?? 0, 'admins' => $by['admin'] ?? 0, 'total' => ($by['user'] ?? 0) + ($by['host'] ?? 0)];
}

/** Delete demo members and their photos, never an admin. Returns how many members were removed. */
function admin_tools_demo_remove(): int
{
    $ids = array_map('intval', col("SELECT id FROM users WHERE email LIKE :d AND role <> 'admin'", ['d' => '%@' . admin_tools_demo_domain()]));
    if (!$ids) {
        return 0;
    }
    foreach (array_chunk($ids, 200) as $chunk) {
        foreach (rows('SELECT large_path, medium_path, thumb_path, blur_path FROM user_photos WHERE user_id IN (:ids)', ['ids' => $chunk]) as $p) {
            foreach ($p as $path) {
                upload_delete($path);
            }
        }
        q('DELETE FROM users WHERE id IN (:ids)', ['ids' => $chunk]);
    }
    cache_clear();
    return count($ids);
}

// ---------------------------------------------------------------------------
// Database facts and backup
// ---------------------------------------------------------------------------

/** @return array list of ['name','rows','bytes'] largest first */
function admin_tools_table_sizes(): array
{
    return array_map(fn($r) => ['name' => (string)$r['name'], 'rows' => (int)$r['n'], 'bytes' => (int)$r['bytes']],
        admin_core_try_rows("SELECT TABLE_NAME AS name, COALESCE(TABLE_ROWS, 0) AS n, COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0) AS bytes
            FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY bytes DESC, name"));
}

/**
 * Stream a complete .sql dump (structure and, optionally, data) to the browser. Pure PDO, no mysqldump.
 * Rows are written in extended INSERT statements and flushed as they are read, so memory stays flat on large tables.
 */
function admin_tools_backup_stream(bool $withData): void
{
    @set_time_limit(0);
    ignore_user_abort(false);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $pdo = db();
    $dbName = (string)val('SELECT DATABASE()');
    $file = 'connecthub-' . preg_replace('/[^A-Za-z0-9_-]/', '', $dbName) . '-' . date('Ymd-His') . ($withData ? '' : '-structure') . '.sql';
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Accel-Buffering: no');
    $out = fn(string $s) => print($s);
    $out("-- ConnectHub database backup\n-- Created " . date('Y-m-d H:i:s') . ' by ' . (string)($_SESSION['uid'] ?? '?') . "\n-- Database: $dbName\n-- Contents: " . ($withData ? 'structure and data' : 'structure only') . "\n"
        . "-- This file contains private member data. Store it somewhere safe.\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n\n");
    $skipData = ['rate_limits', 'typing_status'];
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as [$table]) {
        $table = (string)$table;
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM);
        $out("-- ----------------------------------------\n-- Table `$table`\n-- ----------------------------------------\nDROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n");
        if (!$withData || in_array($table, $skipData, true)) {
            continue;
        }
        $bin = [];
        foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC) as $i => $c) {
            $bin[$i] = (bool)preg_match('/blob|binary/i', (string)$c['Type']);
        }
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $st = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
        $buf = [];
        $size = 0;
        $flush = function () use (&$buf, &$size, $table, $out) {
            if ($buf) {
                $out('INSERT INTO `' . $table . "` VALUES\n" . implode(",\n", $buf) . ";\n");
                $buf = [];
                $size = 0;
            }
        };
        while (($row = $st->fetch(PDO::FETCH_NUM)) !== false) {
            $vals = [];
            foreach ($row as $i => $v) {
                if ($v === null) {
                    $vals[] = 'NULL';
                } elseif (!empty($bin[$i])) {
                    $vals[] = $v === '' ? "''" : '0x' . bin2hex((string)$v);
                } elseif (is_int($v)) {
                    $vals[] = (string)$v;
                } else {
                    $vals[] = $pdo->quote((string)$v);
                }
            }
            $line = '(' . implode(',', $vals) . ')';
            $buf[] = $line;
            $size += strlen($line);
            if (count($buf) >= 200 || $size > 400000) {
                $flush();
                flush();
            }
        }
        $st->closeCursor();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $flush();
        $out("\n");
        flush();
    }
    $out("SET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n-- End of backup\n");
}

// ---------------------------------------------------------------------------
// Blurred photo copies
// ---------------------------------------------------------------------------

/**
 * Rebuild the blurred copy (file and tiny inline preview) of a batch of member photos.
 * $after is the id of the last photo done so far (0 to start). Returns total, processed (including skipped), errors, the new cursor and done.
 */
function admin_tools_blur_batch(int $after, int $batch): array
{
    @set_time_limit(90);
    $batch = max(1, min(50, $batch));
    $total = (int)val('SELECT COUNT(*) FROM user_photos', [], 0);
    $list = rows('SELECT id, large_path, medium_path, thumb_path, blur_path FROM user_photos WHERE id > :a ORDER BY id LIMIT ' . $batch, ['a' => max(0, $after)]);
    $errors = 0;
    $last = $after;
    foreach ($list as $p) {
        $last = (int)$p['id'];
        try {
            $src = '';
            foreach (['large_path', 'medium_path', 'thumb_path'] as $k) {
                if (!empty($p[$k]) && strpos((string)$p[$k], '..') === false && is_file(upload_abs((string)$p[$k]))) {
                    $src = upload_abs((string)$p[$k]);
                    break;
                }
            }
            if ($src === '') {
                throw new RuntimeException('No source image');
            }
            $rel = (string)$p['blur_path'];
            if ($rel === '' || strpos($rel, '..') !== false) {
                $base = (string)($p['large_path'] ?: $p['medium_path'] ?: $p['thumb_path']);
                $rel = preg_replace('/_[lmt]\.jpg$/', '', $base) . '_b.jpg';
            }
            $img = image_load($src);
            $uri = image_make_blur($img, upload_abs($rel));
            q('UPDATE user_photos SET blur_path = :b, blur_data_uri = :u WHERE id = :id', ['b' => $rel, 'u' => $uri, 'id' => (int)$p['id']]);
        } catch (Throwable $e) {
            $errors++;
            app_log('error', 'blur rebuild skipped photo ' . $p['id'] . ': ' . $e->getMessage());
        }
    }
    cache_clear('landing');
    return ['total' => $total, 'processed' => count($list), 'errors' => $errors, 'offset' => $last, 'done' => count($list) < $batch];
}

// ---------------------------------------------------------------------------
// Orphan upload scan (report only)
// ---------------------------------------------------------------------------

/**
 * Compare files in /uploads with every path the database mentions. Nothing is deleted.
 * @return array scanned_at, files, bytes, referenced, orphans[ [path, bytes, mtime] ], orphan_count, orphan_bytes, recent, missing[], missing_count, columns, truncated
 */
function admin_tools_orphan_scan(int $minAgeSeconds = 86400): array
{
    @set_time_limit(120);
    $base = realpath(ROOT . '/uploads');
    $result = ['scanned_at' => time(), 'files' => 0, 'bytes' => 0, 'referenced' => 0, 'orphans' => [], 'orphan_count' => 0, 'orphan_bytes' => 0, 'recent' => 0,
        'missing' => [], 'missing_count' => 0, 'columns' => 0, 'truncated' => false];
    if ($base === false) {
        return $result;
    }
    // 1. Every path-like token the database mentions.
    $refs = [];
    $names = [];
    $skipTables = ['messages', 'notifications', 'admin_activity_log', 'rate_limits', 'search_history', 'typing_status', 'user_tokens', 'payments', 'host_booking_events', 'contact_messages', 'newsletter_subscribers', 'blocks', 'reports'];
    $nameLike = '/(path|image|img|photo|logo|video|file|avatar|icon|media|thumb|banner|cover|poster|picture|attachment|selfie|favicon|src)/i';
    $cols = admin_core_try_rows("SELECT TABLE_NAME AS t, COLUMN_NAME AS c, DATA_TYPE AS d, COALESCE(CHARACTER_MAXIMUM_LENGTH, 0) AS len FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN ('varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'json')");
    $re = '#[A-Za-z0-9_\-./%]+\.(?:jpe?g|png|gif|webp|avif|svg|ico|mp4|webm|mov|m4v|pdf)#i';
    foreach ($cols as $col) {
        $t = (string)$col['t'];
        $c = (string)$col['c'];
        $pathy = (bool)preg_match($nameLike, $c);
        $big = in_array($col['d'], ['text', 'mediumtext', 'longtext', 'json'], true) || (int)$col['len'] >= 60;
        if (!$pathy && (!$big || in_array($t, $skipTables, true))) {
            continue;
        }
        $result['columns']++;
        $tq = '`' . str_replace('`', '``', $t) . '`';
        $cq = '`' . str_replace('`', '``', $c) . '`';
        foreach (admin_core_try_rows("SELECT $cq AS v FROM $tq WHERE $cq LIKE '%.%' LIMIT 100000") as $r) {
            if (!preg_match_all($re, (string)$r['v'], $m)) {
                continue;
            }
            foreach ($m[0] as $tok) {
                $p = stripos($tok, 'uploads/');
                if ($p !== false) {
                    $tok = substr($tok, $p + 8);
                }
                $tok = strtolower(ltrim($tok, '/'));
                if ($tok === '') {
                    continue;
                }
                if (strpos($tok, '/') === false) {
                    $names[$tok] = true;
                } else {
                    $refs[$tok] = true;
                }
            }
        }
    }
    // 2. Every file on disk.
    $onDisk = [];
    $count = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $name = $f->getFilename();
        if ($name[0] === '.' || in_array($name, ['index.html', 'index.php'], true)) {
            continue;
        }
        if (++$count > 200000) {
            $result['truncated'] = true;
            break;
        }
        $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($base))), '/');
        $onDisk[$rel] = [$f->getSize(), $f->getMTime()];
    }
    $orphans = [];
    $cut = time() - max(0, $minAgeSeconds);
    foreach ($onDisk as $rel => [$size, $mtime]) {
        $result['files']++;
        $result['bytes'] += $size;
        $low = strtolower($rel);
        if (isset($refs[$low]) || isset($names[strtolower(basename($rel))])) {
            $result['referenced']++;
            continue;
        }
        if ($minAgeSeconds > 0 && $mtime > $cut) {
            $result['recent']++;
            continue;
        }
        $result['orphan_count']++;
        $result['orphan_bytes'] += $size;
        $orphans[] = [$rel, $size, $mtime];
    }
    usort($orphans, fn($a, $b) => $b[1] <=> $a[1]);
    $result['orphans'] = array_slice($orphans, 0, 200);
    // 3. Database mentions of files that are not there (only inside folders that exist under /uploads).
    $top = [];
    foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        $top[strtolower(basename($d))] = true;
    }
    $lowDisk = [];
    foreach ($onDisk as $rel => $_) {
        $lowDisk[strtolower($rel)] = true;
    }
    foreach (array_keys($refs) as $tok) {
        $first = strtok($tok, '/');
        if (isset($top[$first]) && !isset($lowDisk[$tok]) && strpos($tok, '%') === false) {
            $result['missing_count']++;
            if (count($result['missing']) < 50) {
                $result['missing'][] = $tok;
            }
        }
    }
    return $result;
}

// ---------------------------------------------------------------------------
// Scheduled jobs and e-mail
// ---------------------------------------------------------------------------

/**
 * Run the scheduled jobs now, using the same lock as cron/run.php so two runs never overlap.
 * @return array ['ran' => bool, 'message' => string, 'log' => array]
 */
function admin_tools_run_cron(bool $force): array
{
    @set_time_limit(240);
    $lock = @fopen(ROOT . '/storage/cron.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return ['ran' => false, 'message' => 'The scheduled jobs are already running. Try again in a minute.', 'log' => []];
    }
    try {
        $log = cron_run($force);
    } catch (Throwable $e) {
        flock($lock, LOCK_UN);
        fclose($lock);
        return ['ran' => false, 'message' => 'The run stopped with an error: ' . $e->getMessage(), 'log' => []];
    }
    flock($lock, LOCK_UN);
    fclose($lock);
    return ['ran' => true, 'message' => '', 'log' => $log];
}

/** Send a test message through the configured mail driver. @return array ['ok' => bool, 'driver' => string, 'message' => string] */
function admin_tools_send_test_mail(string $to): array
{
    $driver = (string)setting('mail_driver');
    $site = site_name();
    $html = '<p>This is a test message from <strong>' . e($site) . '</strong>.</p><p>If you can read it, e-mail sending is working.</p><p style="color:#888;font-size:12px">Sent ' . e(date('j M Y, g:i a')) . ' from the admin Tools page.</p>';
    $ok = send_mail($to, 'Test message from ' . $site, $html);
    $labels = ['mail' => 'PHP mail()', 'smtp' => 'SMTP', 'log' => 'log only'];
    if ($driver === 'log') {
        return ['ok' => $ok, 'driver' => 'log', 'message' => 'Mail is set to log only, so nothing was delivered. The message was saved in storage/mail. Change the mail method under Settings, Email to send real e-mail.'];
    }
    return ['ok' => $ok, 'driver' => $driver, 'message' => $ok ? 'Sent through ' . ($labels[$driver] ?? $driver) . '. Check the inbox of ' . $to . ' (and spam).' : 'The server did not accept the message (' . ($labels[$driver] ?? $driver) . '). See Activity log, Server logs, mail.'];
}

// ---------------------------------------------------------------------------
// Server facts
// ---------------------------------------------------------------------------

/** A short, safe subset of PHP and server settings. @return array section => list of [label, value, status ok|warn|bad|''] */
function admin_tools_php_info(): array
{
    $ini = fn(string $k) => (string)ini_get($k);
    $on = fn($v) => in_array(strtolower((string)$v), ['1', 'on', 'true', 'yes'], true);
    $need = ['gd', 'mbstring', 'fileinfo', 'pdo_mysql', 'exif', 'curl', 'openssl', 'intl', 'zip', 'json'];
    $ext = [];
    foreach ($need as $x) {
        $ext[] = [$x, extension_loaded($x) ? 'Loaded' : 'Missing', extension_loaded($x) ? 'ok' : (in_array($x, ['gd', 'mbstring', 'fileinfo', 'pdo_mysql'], true) ? 'bad' : 'warn')];
    }
    $free = @disk_free_space(ROOT);
    $total = @disk_total_space(ROOT);
    $gd = function_exists('gd_info') ? gd_info() : [];
    return [
        'PHP' => [
            ['Version', PHP_VERSION, version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'bad'],
            ['Interface', PHP_SAPI, ''],
            ['Memory limit', $ini('memory_limit'), ''],
            ['Max execution time', $ini('max_execution_time') === '0' ? 'No limit' : $ini('max_execution_time') . ' s', ''],
            ['Upload limit', $ini('upload_max_filesize'), ''],
            ['Post size limit', $ini('post_max_size'), ''],
            ['Time zone', date_default_timezone_get(), ''],
            ['OPcache', function_exists('opcache_get_status') && $on($ini('opcache.enable')) ? 'On' : 'Off', function_exists('opcache_get_status') && $on($ini('opcache.enable')) ? 'ok' : 'warn'],
            ['Show errors on screen', $on($ini('display_errors')) ? 'On' : 'Off', $on($ini('display_errors')) ? 'warn' : 'ok'],
        ],
        'Server' => [
            ['Software', (string)($_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI), ''],
            ['Database', (string)val('SELECT VERSION()', [], '?'), ''],
            ['Database size', file_size_h((int)array_sum(array_column(admin_tools_table_sizes(), 'bytes'))), ''],
            ['Disk free', $free !== false ? file_size_h((int)$free) . ($total ? ' of ' . file_size_h((int)$total) : '') : 'Unknown', $free !== false && $free < 200 * 1048576 ? 'bad' : ''],
            ['Image formats', $gd ? implode(', ', array_filter([!empty($gd['JPEG Support']) ? 'JPEG' : '', !empty($gd['PNG Support']) ? 'PNG' : '', !empty($gd['WebP Support']) ? 'WebP' : '', !empty($gd['GIF Read Support']) ? 'GIF' : ''])) : 'GD missing', $gd ? '' : 'bad'],
            ['Secure connection', is_https() ? 'Yes' : 'No', is_https() ? 'ok' : (is_debug() ? 'warn' : 'bad')],
        ],
        'Extensions' => $ext,
    ];
}

/**
 * Everything an admin needs to set up the cron job: the ready-to-paste commands for this server, and what each job does.
 * @return array ['php' => string, 'root' => string, 'url' => string, 'key_set' => bool, 'suggest_key' => string, 'commands' => list<array>, 'jobs' => list<array>]
 */
function admin_tools_cron_info(): array
{
    $php = PHP_BINARY ?: '';
    if ($php === '' || !preg_match('~/php(\d[\d.]*)?$~', $php)) {
        $php = '/usr/bin/php'; // the web server's own binary (php-fpm, apache) cannot run a script from cron
    }
    $root = rtrim((string)ROOT, '/');
    $url = abs_url('/cron/run.php');
    $key = (string)(config('cron_key') ?? '');
    $keyShown = $key !== '' ? $key : 'YOUR_SECRET_KEY';
    $commands = [
        ['id' => 'cli', 'title' => 'Command line (best)', 'when' => 'VPS, cPanel “Cron Jobs”, Plesk and most hosts', 'recommended' => true,
         'schedule' => '*/5 * * * *', 'command' => $php . ' ' . $root . '/cron/run.php >/dev/null 2>&1',
         'note' => 'Runs every 5 minutes. On cPanel the PHP path is often /usr/local/bin/php or /opt/cpanel/ea-php82/root/usr/bin/php, check with “which php”.'],
        ['id' => 'curl', 'title' => 'Web address with curl', 'when' => 'Hosts that only allow URL cron jobs', 'recommended' => false,
         'schedule' => '*/5 * * * *', 'command' => 'curl -fsS "' . $url . '?key=' . $keyShown . '" >/dev/null 2>&1',
         'note' => $key !== '' ? 'Uses the cron_key from config/config.php.' : 'Needs a secret key first. Add the line below to config/config.php, then use the key in this command.'],
        ['id' => 'wget', 'title' => 'Web address with wget', 'when' => 'Servers without curl', 'recommended' => false,
         'schedule' => '*/5 * * * *', 'command' => 'wget -q -O /dev/null "' . $url . '?key=' . $keyShown . '"',
         'note' => 'Same as above. Many panels also accept the plain address as a “URL cron” or “webcron” entry.'],
        ['id' => 'force', 'title' => 'Run everything once, right now', 'when' => 'Testing from a terminal', 'recommended' => false,
         'schedule' => '', 'command' => $php . ' ' . $root . '/cron/run.php --force',
         'note' => 'Also runs the hourly, daily and weekly jobs. The weekly digest sends email on Mondays.'],
    ];
    $jobs = [
        ['every' => 'Every 5 minutes', 'name' => 'Subscriptions', 'what' => 'Expires plans that ended and boosts that ran out.'],
        ['every' => 'Every 5 minutes', 'name' => 'Hosts', 'what' => 'Releases unpaid slots, auto-declines unanswered requests, sends reminders and completes finished bookings.'],
        ['every' => 'Every 5 minutes', 'name' => 'Matching', 'what' => 'Refreshes compatibility scores for new and changed profiles.'],
        ['every' => 'Every 5 minutes', 'name' => 'Campaigns', 'what' => 'Keeps newsletter sends going if the browser tab was closed.'],
        ['every' => 'Hourly', 'name' => 'Housekeeping', 'what' => 'Clears old rate limits, tokens, typing flags, cache and session files.'],
        ['every' => 'Daily', 'name' => 'Retention and analytics', 'what' => 'Removes old notifications and logs, applies message retention, saves yesterday’s analytics.'],
        ['every' => 'Mondays after 08:00', 'name' => 'Weekly digest', 'what' => 'Sends the weekly summary email to members who opted in (if turned on in Settings).'],
    ];
    return ['php' => $php, 'root' => $root, 'url' => $url, 'key_set' => $key !== '', 'suggest_key' => random_token(20), 'commands' => $commands, 'jobs' => $jobs];
}

// ---------------------------------------------------------------------------
// Database check: compares the live database with database/structure.json (written at release time) and can add what is missing.
// ---------------------------------------------------------------------------

function admin_db_expected(): array
{
    $f = ROOT . '/database/structure.json';
    $d = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    return is_array($d) && isset($d['tables']) ? $d['tables'] : [];
}

/**
 * What is missing from this database. Returns ['tables' => [name], 'columns' => [table => [column]], 'migrations' => [done, total], 'upgrade_error' => string].
 */
function admin_db_check(): array
{
    $want = admin_db_expected();
    $have = [];
    foreach (rows('SELECT table_name AS t, column_name AS c FROM information_schema.columns WHERE table_schema = DATABASE()') as $r) {
        $have[strtolower($r['t'])][strtolower($r['c'])] = true;
    }
    $missingTables = [];
    $missingCols = [];
    foreach ($want as $t => $info) {
        if (!isset($have[strtolower($t)])) {
            $missingTables[] = $t;
            continue;
        }
        foreach (array_keys($info['columns']) as $c) {
            if (!isset($have[strtolower($t)][strtolower($c)])) {
                $missingCols[$t][] = $c;
            }
        }
    }
    $done = 0;
    try {
        $done = (int)val('SELECT COUNT(*) FROM schema_migrations', [], 0);
    } catch (Throwable $e) {
    }
    $err = '';
    $log = ROOT . '/storage/logs/error.log';
    if (is_file($log) && filesize($log) < 5 * 1024 * 1024) {
        foreach (array_reverse(file($log, FILE_IGNORE_NEW_LINES) ?: []) as $line) {
            if (strpos($line, 'Database upgrade failed') !== false) {
                $err = trim($line);
                break;
            }
        }
    }
    return ['tables' => $missingTables, 'columns' => $missingCols, 'migrations' => [$done, count(migrate_files())], 'upgrade_error' => $err, 'known' => (bool)$want];
}

function admin_db_problem_count(array $c): int
{
    return count($c['tables']) + array_sum(array_map('count', $c['columns'])) + ($c['migrations'][0] < $c['migrations'][1] ? 1 : 0);
}

/** Apply pending upgrades, then create missing tables and add missing columns. Returns report lines and any errors. */
function admin_db_repair(): array
{
    $lines = [];
    $errors = [];
    foreach (glob(ROOT . '/storage/cache/schema_*.ok') ?: [] as $f) {
        @unlink($f);
    }
    try {
        $applied = migrate_run();
        $lines[] = $applied === null ? 'Another upgrade is running; try again in a minute.' : ($applied ? 'Applied upgrade steps: ' . implode(', ', $applied) . '.' : 'No upgrade steps were waiting.');
    } catch (Throwable $e) {
        $errors[] = 'Upgrade step failed: ' . $e->getMessage();
        app_log('error', 'Database repair, upgrade failed: ' . $e->getMessage());
    }
    $want = admin_db_expected();
    $c = admin_db_check();
    db()->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        foreach ($c['tables'] as $t) {
            try {
                db()->exec(preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $want[$t]['create']));
                $lines[] = "Created table $t.";
            } catch (Throwable $e) {
                $errors[] = "Table $t: " . $e->getMessage();
            }
        }
        foreach ($c['columns'] as $t => $cols) {
            foreach ($cols as $col) {
                $def = $want[$t]['columns'][$col]['def'];
                try {
                    db()->exec('ALTER TABLE `' . str_replace('`', '', $t) . '` ADD COLUMN `' . str_replace('`', '', $col) . '` ' . $def);
                    $lines[] = "Added column $t.$col.";
                } catch (Throwable $e) {
                    $errors[] = "Column $t.$col: " . $e->getMessage();
                }
            }
        }
    } finally {
        db()->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    admin_tools_clear_cache();
    return ['lines' => $lines, 'errors' => $errors, 'left' => admin_db_problem_count(admin_db_check())];
}
