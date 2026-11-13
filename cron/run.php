<?php
// Scheduled jobs runner. Add ONE cron entry that runs every 5 minutes:
//
//     every 5 minutes:  /usr/bin/php /path/to/site/cron/run.php   (cron expression: star-slash-5 then four stars)
//
// Hosts that only offer URL cron jobs can call  https://your-site/cron/run.php?key=SECRET
// where SECRET is the "cron_key" value in config/config.php (create one with a long random string).
// Add "--force" on the command line to run the hourly, daily and weekly jobs right now.

declare(strict_types=1);

define('CH_CRON', true);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (!CH_CLI) {
    $key = (string)(config('cron_key') ?? '');
    if ($key === '' || !hash_equals($key, (string)($_GET['key'] ?? ''))) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

@set_time_limit(240);
$lock = fopen(ROOT . '/storage/cron.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Another run is still in progress.\n");
}

$force = CH_CLI && in_array('--force', $argv ?? [], true);
$log = cron_run($force);
foreach ($log as $name => $r) {
    echo str_pad($name, 14) . ($r['ok'] ? 'ok    ' : 'FAILED') . ' ' . $r['ms'] . " ms" . (!$r['ok'] ? ' ' . $r['error'] : '') . "\n";
}
flock($lock, LOCK_UN);
