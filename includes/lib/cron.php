<?php
/**
 * Scheduled jobs. cron/run.php calls cron_run() every 5 minutes; each task keeps its own
 * "last ran" time so heavier jobs run hourly, daily or weekly without extra cron lines.
 */

declare(strict_types=1);

function cron_due(string $task, int $everySeconds, bool $force = false): bool
{
    $last = (int)cache_get('cron_task_' . $task, 0);
    if (!$force && time() - $last < $everySeconds) {
        return false;
    }
    cache_put('cron_task_' . $task, time(), 86400 * 3650);
    return true;
}

/** Run everything that is due. Returns a log of what happened. */
function cron_run(bool $force = false): array
{
    $log = [];
    $do = function (string $name, callable $fn) use (&$log) {
        $t = microtime(true);
        try {
            $r = $fn();
            $log[$name] = ['ok' => true, 'result' => $r, 'ms' => (int)round((microtime(true) - $t) * 1000)];
        } catch (Throwable $e) {
            $log[$name] = ['ok' => false, 'error' => $e->getMessage(), 'ms' => (int)round((microtime(true) - $t) * 1000)];
            app_log('cron', $name . ' failed: ' . $e->getMessage());
        }
    };

    // every run (5 minutes)
    $do('subscriptions', fn() => plans_housekeeping());
    $do('payments_online', fn() => payments_online_housekeeping());
    $do('autorenew', fn() => autorenew_run());
    $do('mpesa_payouts', fn() => mpesa_b2c_cron());
    if (function_exists('hosts_housekeeping')) { $do('hosts', fn() => hosts_housekeeping()); }
    if (function_exists('matching_recompute_all')) { $do('matching', fn() => matching_recompute_all(300)); }
    if (function_exists('cron_send_campaigns')) { $do('campaigns', fn() => cron_send_campaigns()); }
    if (function_exists('launch_access_housekeeping')) { $do('launch_access', fn() => launch_access_housekeeping()); }
    if (function_exists('launch_access_grant_existing_users')) { $do('launch_existing', fn() => launch_access_grant_existing_users(100)); }
    if (function_exists('agent_story_cleanup_expired')) { $do('story_cleanup', fn() => agent_story_cleanup_expired(200)); }

    // hourly
    if (cron_due('hourly', 3600, $force)) {
        $do('expired_rows', function () {
            return [
                'rate_limits' => q('DELETE FROM rate_limits WHERE window_start < :t', ['t' => time() - 86400])->rowCount(),
                'tokens' => q('DELETE FROM user_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)')->rowCount(),
                'typing' => q('DELETE FROM typing_status WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)')->rowCount(),
                'cache' => cron_prune_cache(),
                'sessions' => cron_prune_sessions(),
            ];
        });
    }

    // daily
    if (cron_due('daily', 86400 - 600, $force)) {
        $do('retention', function () {
            $n = [];
            $n['notifications'] = q('DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)')->rowCount()
                + q('DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)')->rowCount();
            $n['search_history'] = q('DELETE FROM search_history WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)')->rowCount();
            $n['daily_usage'] = q('DELETE FROM daily_usage WHERE usage_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)')->rowCount();
            $n['daily_picks'] = q('DELETE FROM daily_picks WHERE pick_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY)')->rowCount();
            $n['ad_events'] = q('DELETE FROM advertisement_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 400 DAY)')->rowCount();
            $n['story_daily_stats'] = q('DELETE FROM agent_story_daily_stats WHERE stat_date < DATE_SUB(CURDATE(), INTERVAL 400 DAY)')->rowCount();
            $n['activity_log'] = q('DELETE FROM admin_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 400 DAY)')->rowCount();
            $n['mail_files'] = cron_prune_files(ROOT . '/storage/mail', 14);
            $n['logs'] = cron_rotate_logs();
            return $n;
        });
        if (function_exists('messaging_purge_old')) { $do('messages', fn() => messaging_purge_old()); }
        if (function_exists('elite_requests_expire')) { $do('elite_requests', fn() => elite_requests_expire()); }
        if (function_exists('elite_resync_all')) { $do('elite_dating_flags', fn() => elite_resync_all()); }
        $do('analytics', fn() => cron_analytics_snapshot(date('Y-m-d', strtotime('-1 day'))));
    }

    // weekly (first run on or after Monday 08:00)
    if ((int)date('N') === 1 && (int)date('G') >= 8 && cron_due('weekly', 6 * 86400, $force)) {
        if (setting('email_digest')) { $do('digest', fn() => cron_weekly_digest()); }
    }

    admin_core_mark_cron_run();
    @file_put_contents(ROOT . '/storage/cron_last_run', (string)time());
    return $log;
}

/** Store one day of headline numbers in analytics_daily (safe to re-run). Same definitions as the Admin analytics page. */
function cron_analytics_snapshot(string $day): int
{
    return admin_analytics_snapshot($day);
}

/**
 * Keeps a newsletter campaign going when the admin closed the browser tab mid-send.
 * Uses the same claim-then-send logic as the compose page, so a tab and cron never send the same email twice.
 */
function cron_send_campaigns(int $batchSize = 40): array
{
    $out = ['sent' => 0, 'failed' => 0, 'finished' => 0];
    $camps = rows("SELECT * FROM newsletter_campaigns WHERE status = 'sending' AND started_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE) ORDER BY id LIMIT 2");
    foreach ($camps as $c) {
        $cid = (int)$c['id'];
        $batch = rows('SELECT id, email FROM newsletter_subscribers WHERE is_active = 1 AND id > :last ORDER BY id LIMIT ' . (int)$batchSize, ['last' => (int)$c['last_subscriber_id']]);
        if ($batch) {
            $newLast = (int)end($batch)['id'];
            $claimed = q("UPDATE newsletter_campaigns SET last_subscriber_id = :n WHERE id = :id AND last_subscriber_id = :o AND status = 'sending'", ['n' => $newLast, 'id' => $cid, 'o' => (int)$c['last_subscriber_id']])->rowCount();
            if ($claimed) {
                $sent = 0;
                $failed = 0;
                foreach ($batch as $sub) {
                    try {
                        $ok = ac_campaign_send($c, $sub);
                    } catch (Throwable $e) {
                        app_log('mail', 'campaign ' . $cid . ' send error: ' . $e->getMessage());
                        $ok = false;
                    }
                    $ok ? $sent++ : $failed++;
                }
                q('UPDATE newsletter_campaigns SET sent_count = sent_count + :s, failed_count = failed_count + :f WHERE id = :id', ['s' => $sent, 'f' => $failed, 'id' => $cid]);
                $out['sent'] += $sent;
                $out['failed'] += $failed;
            }
        }
        $last = (int)val('SELECT last_subscriber_id FROM newsletter_campaigns WHERE id = :id', ['id' => $cid], 0);
        if ((int)val('SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1 AND id > :last', ['last' => $last], 0) === 0) {
            $n = q("UPDATE newsletter_campaigns SET status = 'sent', finished_at = NOW() WHERE id = :id AND status = 'sending'", ['id' => $cid])->rowCount();
            $out['finished'] += $n;
        }
    }
    return $out;
}

/** Weekly summary: only members who opted in to news emails and have something to catch up on. */
function cron_weekly_digest(int $limit = 400): array
{
    $users = rows("SELECT u.id, u.email, p.display_name,
            (SELECT COUNT(*) FROM notifications n WHERE n.user_id = u.id AND n.is_read = 0) AS unread,
            (SELECT COUNT(*) FROM matches m WHERE (m.user1_id = u.id OR m.user2_id = u.id) AND m.is_active = 1 AND m.matched_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new_matches,
            COALESCE((SELECT SUM(IF(c.user1_id = u.id, c.user1_unread, c.user2_unread)) FROM conversations c WHERE c.user1_id = u.id OR c.user2_id = u.id), 0) AS unread_messages
        FROM users u JOIN user_profiles p ON p.user_id = u.id
        LEFT JOIN notification_preferences np ON np.user_id = u.id
        WHERE u.status = 'active' AND u.role = 'user' AND u.email_verified_at IS NOT NULL AND COALESCE(np.email_news, 1) = 1
          AND u.last_active_at < DATE_SUB(NOW(), INTERVAL 2 DAY) AND u.last_active_at > DATE_SUB(NOW(), INTERVAL 120 DAY)
        ORDER BY u.id LIMIT " . (int)$limit);
    $sent = 0;
    foreach ($users as $u) {
        if ((int)$u['unread'] + (int)$u['new_matches'] + (int)$u['unread_messages'] === 0) {
            continue;
        }
        $parts = [];
        if ($u['new_matches'] > 0) { $parts[] = $u['new_matches'] . ($u['new_matches'] == 1 ? ' new match' : ' new matches'); }
        if ($u['unread_messages'] > 0) { $parts[] = $u['unread_messages'] . ($u['unread_messages'] == 1 ? ' unread message' : ' unread messages'); }
        if ($u['unread'] > 0) { $parts[] = $u['unread'] . ($u['unread'] == 1 ? ' notification' : ' notifications'); }
        $ok = send_template_mail((string)$u['email'], 'Your week on ' . site_name(), 'notification', [
            'name' => (string)$u['display_name'], 'title' => 'What you missed this week',
            'body' => 'You have ' . implode(', ', $parts) . ' waiting for you.', 'url' => abs_url('/discover'),
        ]);
        $sent += $ok ? 1 : 0;
    }
    return ['checked' => count($users), 'sent' => $sent];
}

/** Remove session files nobody has touched for longer than a session can live. PHP's own cleanup is often switched off and never looks in storage/sessions. */
function cron_prune_sessions(): int
{
    $keepDays = max(1, (int)ceil(max(max(1, (int)setting('session_hours')) * 3600, 86400) / 86400)) + 1;
    $n = 0;
    foreach (glob(ROOT . '/storage/sessions/sess_*') ?: [] as $f) {
        if (is_file($f) && filemtime($f) < time() - $keepDays * 86400 && @unlink($f)) {
            $n++;
        }
    }
    return $n;
}

function cron_prune_cache(): int
{
    return cron_prune_files(ROOT . '/storage/cache', 2);
}

/** Delete files older than $days in a folder (not recursive). */
function cron_prune_files(string $dir, int $days): int
{
    $n = 0;
    foreach (glob(rtrim($dir, '/') . '/*') ?: [] as $f) {
        if (is_file($f) && basename($f) !== '.gitkeep' && basename($f) !== '.htaccess' && filemtime($f) < time() - $days * 86400) {
            if (@unlink($f)) { $n++; }
        }
    }
    return $n;
}

/** Keep log files from growing without end: truncate anything over 5 MB to its last 1 MB. */
function cron_rotate_logs(): int
{
    $n = 0;
    foreach (glob(ROOT . '/storage/logs/*.log') ?: [] as $f) {
        if (filesize($f) > 5 * 1048576) {
            $tail = (string)@file_get_contents($f, false, null, filesize($f) - 1048576);
            @file_put_contents($f, $tail, LOCK_EX);
            $n++;
        }
    }
    return $n;
}
