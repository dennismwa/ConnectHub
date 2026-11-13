<?php
/**
 * Real 24-hour image/video stories for approved Escort/Host profiles.
 * Uses the existing uploads pipeline, moderation model and cron runner.
 */
declare(strict_types=1);

function agent_story_upload_enabled(): bool
{
    return (bool)setting('agent_media_stories_enabled') && (bool)setting('hosts_enabled');
}

function agent_story_owner_rows(int $userId): array
{
    return rows('SELECT id, media_type, media_path, thumbnail_path, caption, status, view_count, created_at, expires_at, moderation_note
        FROM agent_stories WHERE user_id = :u ORDER BY created_at DESC, id DESC LIMIT 30', ['u' => $userId]);
}

/**
 * Store the uploaded image at its original dimensions and encoding. A separate
 * lightweight thumbnail is generated for previews; the viewer always receives
 * the original upload so it is not downscaled or recompressed.
 */
function agent_story_store_image(array $file, int $userId): array
{
    $max = max(1, min(20, (int)setting('agent_story_max_image_mb')));
    $problem = image_upload_problem($file, $max, 320);
    if ($problem !== '') {
        return ['ok' => false, 'error' => $problem, 'path' => '', 'thumb' => ''];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']) ?: '';
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? '';
    if ($ext === '') {
        return ['ok' => false, 'error' => 'Use a valid JPG, PNG or WebP image.', 'path' => '', 'thumb' => ''];
    }
    $base = 'stories/' . date('Y') . '/' . date('m') . '/' . $userId . '_' . random_token(12);
    $original = $base . '.' . $ext;
    $thumbPath = $base . '_t.jpg';
    try {
        upload_ensure_dir(dirname($base));
        $saved = PHP_SAPI === 'cli'
            ? @copy((string)$file['tmp_name'], upload_abs($original))
            : @move_uploaded_file((string)$file['tmp_name'], upload_abs($original));
        if (!$saved) {
            throw new RuntimeException('Could not preserve the original upload');
        }
        @chmod(upload_abs($original), 0664);

        // Thumbnail only: the original file above remains untouched.
        $img = image_load((string)upload_abs($original));
        $thumb = image_fit($img, 420, true);
        image_save_jpeg($thumb, upload_abs($thumbPath), 82);
        if (is_resource($img) || $img instanceof GdImage) { imagedestroy($img); }
        if (is_resource($thumb) || $thumb instanceof GdImage) { imagedestroy($thumb); }
        return ['ok' => true, 'error' => '', 'path' => $original, 'thumb' => $thumbPath];
    } catch (Throwable $e) {
        app_log('stories', 'Image processing failed for member ' . $userId . ': ' . $e->getMessage());
        upload_delete($original);
        upload_delete($thumbPath);
        return ['ok' => false, 'error' => 'The image could not be processed. Please try another file.', 'path' => '', 'thumb' => ''];
    }
}

function agent_story_create(int $userId, array $file, string $caption): array
{
    if (!agent_story_upload_enabled()) {
        return ['ok' => false, 'error' => 'Stories are not available right now.'];
    }
    $host = row("SELECT approval_status FROM host_profiles WHERE user_id = :u", ['u' => $userId]);
    if (!$host || $host['approval_status'] !== 'approved') {
        return ['ok' => false, 'error' => 'Your profile must be approved before you can publish a story.'];
    }
    $caption = trim(preg_replace('/\s+/u', ' ', $caption) ?? '');
    if (mb_strlen($caption) > 500) {
        return ['ok' => false, 'error' => 'Keep the caption to 500 characters or fewer.'];
    }
    if (function_exists('hosts_text_violation') && $caption !== '' && hosts_text_violation($caption) !== null) {
        return ['ok' => false, 'error' => 'Please revise the caption to follow the profile and community rules.'];
    }
    if (function_exists('hosts_contact_violation') && $caption !== '' && hosts_contact_violation($caption) !== null) {
        return ['ok' => false, 'error' => 'Do not put phone numbers, email addresses or external contact details in the caption.'];
    }
    $limit = max(1, min(15, (int)setting('agent_story_max_active')));
    $count = (int)val("SELECT COUNT(*) FROM agent_stories WHERE user_id = :u AND status IN ('pending','active') AND expires_at > NOW()", ['u' => $userId], 0);
    if ($count >= $limit) {
        return ['ok' => false, 'error' => 'You have reached your active story limit. Wait for an older story to expire.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_file((string)$file['tmp_name'])) {
        return ['ok' => false, 'error' => upload_error_text((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))];
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file((string)$file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']) ?: '';
    if (strpos($mime, 'image/') === 0) {
        $stored = agent_story_store_image($file, $userId);
        $type = 'image';
    } elseif (strpos($mime, 'video/') === 0) {
        $max = max(1, min(100, (int)setting('agent_story_max_video_mb')));
        $stored = save_video($file, 'stories/' . date('Y') . '/' . date('m'), $max);
        $stored['thumb'] = '';
        $type = 'video';
    } else {
        return ['ok' => false, 'error' => 'Choose a JPG, PNG or WebP image, or an MP4/WebM video.'];
    }
    if (empty($stored['ok'])) {
        return ['ok' => false, 'error' => (string)($stored['error'] ?? 'The upload failed.')];
    }
    $status = 'active'; // Stories publish immediately; moderation is handled through reporting/removal, not a pre-publication queue.
    $expiresAt = date('Y-m-d H:i:s', time() + 86400);
    try {
        $id = insert('agent_stories', [
            'user_id' => $userId, 'media_type' => $type, 'media_path' => $stored['path'],
            'thumbnail_path' => $stored['thumb'] ?: null, 'caption' => $caption ?: null,
            'status' => $status, 'published_at' => $status === 'active' ? date('Y-m-d H:i:s') : null, 'expires_at' => $expiresAt,
        ]);
        app_log('stories', 'Story ' . $id . ' submitted by member ' . $userId . ' with status ' . $status);
        return ['ok' => true, 'id' => $id, 'status' => $status, 'error' => ''];
    } catch (Throwable $e) {
        upload_delete($stored['path']);
        if (!empty($stored['thumb'])) { upload_delete($stored['thumb']); }
        app_log('stories', 'Could not save story for member ' . $userId . ': ' . $e->getMessage());
        return ['ok' => false, 'error' => 'The story could not be saved. Please try again.'];
    }
}

/** Active stories keyed by owner ID. Expired records are never returned, even if cron has not run yet. */
function agent_stories_for_users(array $userIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($v) => $v > 0)));
    if (!$ids || !agent_story_upload_enabled()) { return []; }
    $rows = rows("SELECT id, user_id, media_type, media_path, thumbnail_path, caption, view_count, created_at, expires_at
        FROM agent_stories WHERE user_id IN (:ids) AND status = 'active' AND expires_at > NOW()
        ORDER BY created_at ASC, id ASC", ['ids' => $ids]);
    $out = [];
    foreach ($rows as $r) {
        $uid = (int)$r['user_id'];
        $src = upload_url((string)$r['media_path']);
        $out[$uid][] = [
            'id' => (int)$r['id'], 'type' => (string)$r['media_type'], 'src' => $src,
            'thumb' => !empty($r['thumbnail_path']) ? upload_url((string)$r['thumbnail_path']) : '',
            'caption' => (string)($r['caption'] ?? ''), 'created_at' => (string)$r['created_at'],
        ];
    }
    return $out;
}

function agent_story_record_view(int $storyId): bool
{
    if ($storyId <= 0) { return false; }
    $story = row("SELECT id, user_id FROM agent_stories WHERE id = :id AND status = 'active' AND expires_at > NOW()", ['id' => $storyId]);
    if (!$story) { return false; }
    $viewer = current_user();
    $viewerId = $viewer ? (int)$viewer['id'] : null;
    if ($viewerId && $viewerId === (int)$story['user_id']) { return false; }
    $sid = session_id();
    if ($sid === '') { $sid = random_token(24); }
    $key = hash('sha256', ($viewerId ? 'user:' . $viewerId : 'session:' . $sid));
    $n = q('INSERT IGNORE INTO agent_story_views (story_id, viewer_key, viewer_user_id) VALUES (:s, :k, :u)', ['s' => $storyId, 'k' => $key, 'u' => $viewerId])->rowCount();
    if ($n > 0) {
        q('UPDATE agent_stories SET view_count = view_count + 1 WHERE id = :id', ['id' => $storyId]);
    }
    return $n > 0;
}

/** Add one published story to daily aggregate reporting exactly once, without retaining its content or viewer rows. */
function agent_story_record_daily_stats(array $story): void
{
    if ((int)($story['stats_recorded'] ?? 0) === 1 || !in_array((string)($story['status'] ?? ''), ['active', 'hidden'], true)) { return; }
    $published = (string)($story['published_at'] ?: $story['created_at']);
    $day = date('Y-m-d', strtotime($published));
    $image = ($story['media_type'] ?? '') === 'image' ? 1 : 0;
    $video = ($story['media_type'] ?? '') === 'video' ? 1 : 0;
    $pdo = db();
    $ownTransaction = !$pdo->inTransaction();
    if ($ownTransaction) { $pdo->beginTransaction(); }
    try {
        $claimed = q('UPDATE agent_stories SET stats_recorded = 1 WHERE id = :id AND stats_recorded = 0', ['id' => (int)$story['id']])->rowCount();
        if ($claimed > 0) {
            q('INSERT INTO agent_story_daily_stats (stat_date, stories_published, image_stories, video_stories, views) VALUES (:d, 1, :i, :v, :views)
                ON DUPLICATE KEY UPDATE stories_published = stories_published + 1, image_stories = image_stories + VALUES(image_stories), video_stories = video_stories + VALUES(video_stories), views = views + VALUES(views)', [
                'd' => $day, 'i' => $image, 'v' => $video, 'views' => (int)($story['view_count'] ?? 0),
            ]);
        }
        if ($ownTransaction) { $pdo->commit(); }
    } catch (Throwable $e) {
        if ($ownTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

function agent_story_delete(int $storyId, ?int $ownerId = null): bool
{
    $sql = 'SELECT id, media_path, thumbnail_path, media_type, status, published_at, created_at, view_count, stats_recorded FROM agent_stories WHERE id = :id' . ($ownerId ? ' AND user_id = :u' : '') . ' LIMIT 1';
    $params = ['id' => $storyId];
    if ($ownerId) { $params['u'] = $ownerId; }
    $r = row($sql, $params);
    if (!$r) { return false; }
    agent_story_record_daily_stats($r);
    delete_rows('agent_story_views', ['story_id' => $storyId]);
    delete_rows('agent_stories', ['id' => $storyId]);
    upload_delete((string)$r['media_path']);
    if (!empty($r['thumbnail_path'])) { upload_delete((string)$r['thumbnail_path']); }
    return true;
}

function agent_story_moderate(int $storyId, string $action, int $adminId, string $note = ''): bool
{
    $r = row('SELECT id, user_id, media_type, media_path, thumbnail_path, status, published_at, created_at, expires_at, view_count, stats_recorded FROM agent_stories WHERE id = :id', ['id' => $storyId]);
    if (!$r) { return false; }
    $note = mb_substr(trim($note), 0, 500);
    if ($action === 'delete') { return agent_story_delete($storyId); }
    $status = ['approve' => 'active', 'reject' => 'rejected', 'hide' => 'hidden'][$action] ?? '';
    if ($status === '') { return false; }
    if ($status === 'rejected') { agent_story_record_daily_stats($r); }
    if ($status === 'active') {
        q('UPDATE agent_stories SET status = :s, moderation_note = :n, reviewed_by = :a, reviewed_at = NOW(), published_at = NOW(), stats_recorded = 0, expires_at = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = :id', ['s' => $status, 'n' => $note ?: null, 'a' => $adminId, 'id' => $storyId]);
    } else {
        q('UPDATE agent_stories SET status = :s, moderation_note = :n, reviewed_by = :a, reviewed_at = NOW() WHERE id = :id', ['s' => $status, 'n' => $note ?: null, 'a' => $adminId, 'id' => $storyId]);
    }
    if ($status === 'rejected') {
        // Keep rejected media only long enough for a member to see the moderation outcome; cleanup will remove it after expiry.
        return true;
    }
    return true;
}

/** Remove expired story rows and their files. Safe to repeat. */
function agent_story_cleanup_expired(int $limit = 200): array
{
    $list = rows('SELECT id, media_path, thumbnail_path FROM agent_stories WHERE expires_at <= NOW() ORDER BY expires_at LIMIT ' . max(1, min(1000, $limit)));
    $removed = 0;
    foreach ($list as $r) {
        try {
            if (agent_story_delete((int)$r['id'])) { $removed++; }
        } catch (Throwable $e) {
            app_log('stories', 'Expired story cleanup failed for ' . (int)$r['id'] . ': ' . $e->getMessage());
        }
    }
    return ['removed' => $removed, 'remaining' => (int)val('SELECT COUNT(*) FROM agent_stories WHERE expires_at <= NOW()', [], 0)];
}

/** Public-safe campaign summary for registration and pricing pages. */
function launch_access_campaign_active(string $role = 'user'): ?array
{
    if (!(bool)setting('launch_access_enabled')) { return null; }
    $campaign = row("SELECT id, name, description, duration_days, eligible_roles FROM launch_access_campaigns
        WHERE is_enabled = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY id DESC LIMIT 1");
    if (!$campaign) { return null; }
    $roles = array_filter(array_map('trim', explode(',', (string)$campaign['eligible_roles'])));
    return in_array($role, $roles, true) ? $campaign : null;
}

/** Apply the active launch campaign to a newly registered user. */
function launch_access_grant_for_user(int $userId): bool
{
    if ($userId <= 0 || !(bool)setting('launch_access_enabled')) { return false; }
    $u = row('SELECT id, role, created_at FROM users WHERE id = :u', ['u' => $userId]);
    if (!$u) { return false; }
    $campaign = row("SELECT * FROM launch_access_campaigns WHERE is_enabled = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY id DESC LIMIT 1");
    if (!$campaign) { return false; }
    $roles = array_filter(array_map('trim', explode(',', (string)$campaign['eligible_roles'])));
    if (!in_array((string)$u['role'], $roles, true)) { return false; }
    $features = json_decode((string)$campaign['features_json'], true);
    if (!is_array($features)) { $features = []; }
    $days = max(1, min(365, (int)$campaign['duration_days']));
    $start = date('Y-m-d H:i:s');
    $expires = date('Y-m-d H:i:s', time() + $days * 86400);
    q('INSERT IGNORE INTO launch_access_grants (campaign_id, user_id, starts_at, expires_at, status, features_json) VALUES (:c, :u, :s, :e, \'active\', :f)', [
        'c' => (int)$campaign['id'], 'u' => $userId, 's' => $start, 'e' => $expires, 'f' => json_encode($features, JSON_UNESCAPED_SLASHES),
    ]);
    return (int)val('SELECT COUNT(*) FROM launch_access_grants WHERE campaign_id = :c AND user_id = :u AND status = \'active\' AND expires_at > NOW()', ['c' => (int)$campaign['id'], 'u' => $userId], 0) > 0;
}

/** Active launch entitlements override plan limits for the duration of the grant. */
function launch_access_features_for_user(int $userId): array
{
    $r = row("SELECT features_json FROM launch_access_grants WHERE user_id = :u AND status = 'active' AND starts_at <= NOW() AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1", ['u' => $userId]);
    if (!$r) { return []; }
    $f = json_decode((string)$r['features_json'], true);
    return is_array($f) ? $f : [];
}


/** Grant the active launch campaign to existing accounts in small batches when the admin enables that option. */
function launch_access_grant_existing_users(int $limit = 100): int
{
    $campaign = row("SELECT id, eligible_roles FROM launch_access_campaigns WHERE is_enabled = 1 AND include_existing = 1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY id DESC LIMIT 1");
    if (!$campaign || !(bool)setting('launch_access_enabled')) { return 0; }
    $roles = array_values(array_filter(array_map('trim', explode(',', (string)$campaign['eligible_roles']))));
    if (!$roles) { return 0; }
    $roleSql = implode(',', array_map(fn($i) => ':r' . $i, array_keys($roles)));
    $params = [];
    foreach ($roles as $i => $role) { $params['r' . $i] = $role; }
    $params['c'] = (int)$campaign['id'];
    $ids = col("SELECT u.id FROM users u WHERE u.status = 'active' AND u.role IN ($roleSql)
        AND NOT EXISTS (SELECT 1 FROM launch_access_grants g WHERE g.campaign_id = :c AND g.user_id = u.id)
        ORDER BY u.id LIMIT " . max(1, min(500, $limit)), $params);
    $n = 0;
    foreach ($ids as $id) { if (launch_access_grant_for_user((int)$id)) { $n++; } }
    return $n;
}

/** Expire launch grants and send one reminder per eligible grant. */
function launch_access_housekeeping(): array
{
    $expired = q("UPDATE launch_access_grants SET status = 'expired' WHERE status = 'active' AND expires_at <= NOW()")->rowCount();
    $sent = 0;
    $rows = rows("SELECT g.id, g.user_id, g.expires_at, c.reminder_days, p.display_name
        FROM launch_access_grants g JOIN launch_access_campaigns c ON c.id = g.campaign_id
        JOIN user_profiles p ON p.user_id = g.user_id
        WHERE g.status = 'active' AND g.reminder_sent_at IS NULL
          AND g.expires_at <= DATE_ADD(NOW(), INTERVAL c.reminder_days DAY) AND g.expires_at > NOW()
        ORDER BY g.id LIMIT 100");
    foreach ($rows as $r) {
        try {
            notify((int)$r['user_id'], 'payment', 'Your launch access is ending soon', 'Your complimentary access ends on ' . fmt_datetime((string)$r['expires_at']) . '. Your account will move to the Free plan. You will not be charged automatically.', '/pricing');
            q('UPDATE launch_access_grants SET reminder_sent_at = NOW() WHERE id = :id AND reminder_sent_at IS NULL', ['id' => (int)$r['id']]);
            $sent++;
        } catch (Throwable $e) {
            app_log('launch_access', 'Reminder failed for grant ' . (int)$r['id'] . ': ' . $e->getMessage());
        }
    }
    return ['expired' => $expired, 'reminders_sent' => $sent];
}
