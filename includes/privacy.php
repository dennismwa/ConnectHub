<?php
/**
 * Privacy layer. EVERY photo URL, contact detail and "can I message them?"
 * decision in the app must go through the functions in this file so the
 * admin's blur, contact-visibility and messaging rules are enforced in one place.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Relationships
// ---------------------------------------------------------------------------

function pair_ids(int $a, int $b): array
{
    return $a < $b ? [$a, $b] : [$b, $a];
}

function users_matched(int $a, int $b): bool
{
    static $cache = [];
    [$x, $y] = pair_ids($a, $b);
    $k = $x . ':' . $y;
    if (!isset($cache[$k])) {
        $cache[$k] = (int)val('SELECT COUNT(*) FROM matches WHERE user1_id = :x AND user2_id = :y AND is_active = 1', ['x' => $x, 'y' => $y]) > 0;
    }
    return $cache[$k];
}

/** True when either person has blocked the other. */
function users_blocked(int $a, int $b): bool
{
    return (int)val('SELECT COUNT(*) FROM blocks WHERE (blocker_id = :a AND blocked_id = :b) OR (blocker_id = :b AND blocked_id = :a)', ['a' => $a, 'b' => $b]) > 0;
}

/** Everyone who has blocked, or been blocked by, $userId. */
function blocked_user_ids(int $userId): array
{
    static $cache = [];
    if (!isset($cache[$userId])) {
        $cache[$userId] = array_map('intval', col(
            'SELECT blocked_id FROM blocks WHERE blocker_id = :u UNION SELECT blocker_id FROM blocks WHERE blocked_id = :u',
            ['u' => $userId]
        ));
    }
    return $cache[$userId];
}

// ---------------------------------------------------------------------------
// Photos and blur
// ---------------------------------------------------------------------------

/** Column list + join to put a member's primary photo on a query row (prefix photo_). */
const SQL_PHOTO_COLS = "ph.id AS photo_id, ph.user_id AS photo_user_id, ph.large_path AS photo_large_path, ph.medium_path AS photo_medium_path,
    ph.thumb_path AS photo_thumb_path, ph.blur_path AS photo_blur_path, ph.is_primary AS photo_is_primary, ph.is_vip_only AS photo_is_vip_only";

function sql_photo_join(string $userCol = 'u.id', string $alias = 'ph'): string
{
    return "LEFT JOIN user_photos $alias ON $alias.user_id = $userCol AND $alias.is_primary = 1 AND $alias.moderation_status = 'approved'";
}

/** Build a photo array from a joined query row (keys prefixed photo_). Null when there is no photo. */
function photo_from_row(array $row, string $prefix = 'photo_'): ?array
{
    if (empty($row[$prefix . 'id'])) {
        return null;
    }
    return [
        'id' => (int)$row[$prefix . 'id'], 'user_id' => (int)($row[$prefix . 'user_id'] ?? 0),
        'large_path' => $row[$prefix . 'large_path'] ?? '', 'medium_path' => $row[$prefix . 'medium_path'] ?? '',
        'thumb_path' => $row[$prefix . 'thumb_path'] ?? '', 'blur_path' => $row[$prefix . 'blur_path'] ?? '',
        'is_primary' => (int)($row[$prefix . 'is_primary'] ?? 0), 'is_vip_only' => (int)($row[$prefix . 'is_vip_only'] ?? 0),
    ];
}

/**
 * Is the signed-in viewer an admin who may see private member data (sharp VIP photos, hidden contact details)?
 * Respects the granular admin roles: an admin whose role has no People area gets no special view of members.
 */
function viewer_is_staff(array $modules = ['users', 'moderation', 'verifications', 'hosts', 'reports']): bool
{
    $v = current_user();
    if (!$v || $v['role'] !== 'admin') {
        return false;
    }
    foreach ($modules as $m) {
        if (admin_can($m, 'view')) {
            return true;
        }
    }
    return false;
}

/** Signature that proves a /media/photo URL was produced by this site (so ids cannot be enumerated). */
function photo_gate_sig(string $what): string
{
    return substr(hash_hmac('sha256', $what, (string)config('app_key')), 0, 20);
}

/** Sharp photos are never served as plain files: this URL is checked against the viewer on every request. */
function photo_gate_url(int $photoId, string $size, string $context): string
{
    return url('/media/photo', ['p' => $photoId, 'z' => $size, 'c' => $context, 's' => photo_gate_sig("p|$photoId|$size|$context")]);
}

/** Same gate for code that only has a stored path (owner and admin pages): owner or staff only. */
function photo_gate_path_url(string $rel): string
{
    return url('/media/photo', ['f' => $rel, 's' => photo_gate_sig('f|' . $rel)]);
}

function guest_blur_enabled(string $context): bool
{
    $map = ['landing' => 'blur_guests_landing', 'browse' => 'blur_guests_browse', 'profile' => 'blur_guests_profile', 'hosts' => 'blur_guests_hosts'];
    return isset($map[$context]) ? (bool)setting($map[$context]) : true;
}

/**
 * Should the viewer see the blurred copy of this photo?
 * Contexts for visitors: landing, browse, profile, hosts. Signed-in members use any context.
 */
function photo_should_blur(?array $photo, string $context = 'profile'): bool
{
    if (!$photo) {
        return false;
    }
    $viewer = current_user();
    if (!$viewer) {
        return guest_blur_enabled($context);
    }
    if (viewer_is_staff() || (int)($photo['user_id'] ?? 0) === (int)$viewer['id']) {
        return false;
    }
    $owner = (int)($photo['user_id'] ?? 0);
    if ($context === 'elite' && (!function_exists('elite_photo_locked') || elite_photo_locked((int)$viewer['id'], $owner))) {
        // Elite photos stay blurred until the viewer may see that member's full profile (unlock, pass, accepted request ...);
        // after that the usual VIP-only and free-gallery rules below still apply.
        return true;
    }
    if (!empty($photo['is_vip_only']) && tier_rank($viewer['tier']) < 2) {
        return !($owner && users_matched((int)$viewer['id'], $owner));
    }
    if (setting('blur_gallery_free') && empty($photo['is_primary']) && $viewer['tier'] === 'free') {
        return !($owner && users_matched((int)$viewer['id'], $owner));
    }
    return false;
}

function photo_placeholder_url(): string
{
    return asset('images/avatar.svg');
}

/** Image URL the current viewer is allowed to see. $size: large|medium|thumb */
function photo_url(?array $photo, string $size = 'medium', string $context = 'profile'): string
{
    if (!$photo) {
        return photo_placeholder_url();
    }
    $size = in_array($size, ['large', 'medium', 'thumb'], true) ? $size : 'medium';
    if (photo_should_blur($photo, $context) && !empty($photo['blur_path'])) {
        return upload_url($photo['blur_path']);
    }
    $path = $photo[$size . '_path'] ?? '';
    if ($path === '') {
        return photo_placeholder_url();
    }
    return !empty($photo['id']) ? photo_gate_url((int)$photo['id'], $size, $context) : upload_url($path);
}

function photo_blur_class(?array $photo, string $context = 'profile'): string
{
    if (!$photo || !photo_should_blur($photo, $context)) {
        return '';
    }
    return 'ch-blurred ch-blur-' . (string)setting('blur_level');
}

/** Complete <img> tag with the right source, blur class and lazy loading. */
function photo_tag(?array $photo, string $size = 'medium', string $context = 'profile', string $alt = '', string $class = '', array $attrs = []): string
{
    $url = photo_url($photo, $size, $context);
    $blur = photo_blur_class($photo, $context);
    $cls = trim($class . ' ' . $blur);
    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . e($k) . '="' . e($v) . '"';
    }
    return '<img src="' . e($url) . '" alt="' . e($alt) . '" class="' . e($cls) . '" loading="lazy" decoding="async"' . $extra . '>';
}

/** True if the photo being shown to this viewer is currently the blurred copy. */
function photo_is_blurred(?array $photo, string $context = 'profile'): bool
{
    return $photo !== null && photo_should_blur($photo, $context);
}

/** Visible-to-viewer gallery for a member. Pending/rejected photos only show to the owner and admins. */
function member_photos(int $userId, string $size = 'large'): array
{
    $viewer = current_user();
    $own = $viewer && ((int)$viewer['id'] === $userId || viewer_is_staff());
    $sql = 'SELECT id, user_id, large_path, medium_path, thumb_path, blur_path, blur_data_uri, width, height, is_primary, is_vip_only, sort_order, moderation_status
            FROM user_photos WHERE user_id = :u' . ($own ? '' : " AND moderation_status = 'approved'") . ' ORDER BY is_primary DESC, sort_order, id';
    return rows($sql, ['u' => $userId]);
}

// ---------------------------------------------------------------------------
// Contact details
// ---------------------------------------------------------------------------

const CONTACT_FIELDS = ['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'instagram' => 'Instagram'];

/** Is this contact field visible to the viewer according to the admin matrix? */
function contact_field_allowed(string $field, string $viewerTier, bool $matched): bool
{
    if (!isset(CONTACT_FIELDS[$field])) {
        return false;
    }
    if (in_array($viewerTier, ['free', 'premium', 'vip'], true) && setting("contact_{$field}_{$viewerTier}") === 'visible') {
        return true;
    }
    return $matched && setting("contact_{$field}_matched") === 'visible';
}

/**
 * Contact details of a member as the current viewer may see them.
 * Returns [field => ['label'=>, 'value'=>string|null, 'visible'=>bool, 'reason'=>'' | 'login' | 'upgrade' | 'match' | 'hidden']].
 * Visitors never receive a value.
 */
function member_contacts(int $ownerId): array
{
    $viewer = current_user();
    $p = row('SELECT show_contact, contact_phone, contact_whatsapp, contact_email, contact_instagram FROM user_profiles WHERE user_id = :u', ['u' => $ownerId]);
    $out = [];
    foreach (CONTACT_FIELDS as $field => $label) {
        $value = trim((string)($p['contact_' . $field] ?? ''));
        $item = ['label' => $label, 'value' => null, 'visible' => false, 'reason' => ''];
        if ($value === '' || !$p) {
            continue; // nothing to show for this field
        }
        $owner = $viewer && (int)$viewer['id'] === $ownerId;
        $admin = $viewer && viewer_is_staff(['users', 'hosts']);
        if ($owner || $admin) {
            $item['value'] = $value;
            $item['visible'] = true;
        } elseif (!$viewer) {
            $item['reason'] = 'login';
        } elseif (!(int)$p['show_contact']) {
            $item['reason'] = 'hidden';
        } else {
            $matched = users_matched((int)$viewer['id'], $ownerId);
            if (host_contact_unlocked($viewer, $ownerId) || contact_field_allowed($field, $viewer['tier'], $matched)) {
                $item['value'] = $value;
                $item['visible'] = true;
            } else {
                // Which step would unlock it?
                $reason = 'hidden';
                foreach (['premium', 'vip'] as $t) {
                    if (setting("contact_{$field}_{$t}") === 'visible') {
                        $reason = 'upgrade';
                        break;
                    }
                }
                if ($reason === 'hidden' && setting("contact_{$field}_matched") === 'visible') {
                    $reason = 'match';
                }
                $item['reason'] = $reason;
            }
        }
        $out[$field] = $item;
    }
    return $out;
}

/** Hosts' contact details follow the Host contact rule (Settings > Hosts > Contact details). */
function host_contact_unlocked(array $viewer, int $ownerId): bool
{
    if (!function_exists('hosts_contact_access')) {
        return false;
    }
    try {
        return hosts_contact_access($viewer, $ownerId)['allowed'] === true;
    } catch (Throwable $e) {
        return false; // host_bookings table not present
    }
}

// ---------------------------------------------------------------------------
// Messaging access
// ---------------------------------------------------------------------------

/**
 * Can the current viewer message $targetId?
 * Returns ['allowed'=>bool, 'code'=>string, 'message'=>string, 'cta'=>url|null]
 * Codes: ok, disabled, login, self, unavailable, blocked, upgrade, match, limit.
 */
function messaging_access(int $targetId): array
{
    $deny = fn(string $code, string $msg, ?string $cta = null) => ['allowed' => false, 'code' => $code, 'message' => $msg, 'cta' => $cta];
    $ok = ['allowed' => true, 'code' => 'ok', 'message' => '', 'cta' => null];

    if (!setting('messaging_enabled')) {
        return $deny('disabled', 'Messaging is switched off right now.');
    }
    $viewer = current_user();
    if (!$viewer) {
        return $deny('login', 'Sign in to send a message.', url('/login'));
    }
    $me = (int)$viewer['id'];
    if ($me === $targetId) {
        return $deny('self', 'This is your own profile.');
    }
    if ($viewer['role'] === 'admin') {
        return $ok;
    }
    $t = row("SELECT u.id, u.role, u.status, p.onboarding_completed FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id", ['id' => $targetId]);
    if (!$t || $t['status'] !== 'active' || $t['role'] === 'admin') {
        return $deny('unavailable', 'This member is not available.');
    }
    if (users_blocked($me, $targetId)) {
        return $deny('blocked', 'You cannot message this member.');
    }
    $rank = tier_rank($viewer['tier']);

    // A Host and a client with a live booking can always coordinate (plan and match rules do not apply).
    if ($viewer['role'] === 'host' || $t['role'] === 'host') {
        $hostId = $viewer['role'] === 'host' ? $me : $targetId;
        $clientId = $viewer['role'] === 'host' ? $targetId : $me;
        if ((int)val("SELECT COUNT(*) FROM host_bookings WHERE host_id = :h AND client_id = :c AND status IN ('pending','confirmed','completed')", ['h' => $hostId, 'c' => $clientId], 0) > 0) {
            return $ok;
        }
    }

    // Replying to someone who wrote first is always allowed when the admin permits it.
    if (setting('msg_free_can_reply')) {
        [$a, $b] = pair_ids($me, $targetId);
        $conv = row('SELECT id, initiated_by FROM conversations WHERE user1_id = :a AND user2_id = :b', ['a' => $a, 'b' => $b]);
        if ($conv && (int)$conv['initiated_by'] !== $me) {
            return messaging_limit_check($me, $ok, $deny);
        }
    }

    if ($t['role'] === 'host') {
        $mode = (string)setting('msg_hosts_access');
        $hostBlock = null;
        if ($mode === 'premium' && $rank < 1) {
            $hostBlock = ['upgrade', 'Messaging Hosts is a Premium feature.', url('/premium')];
        } elseif ($mode === 'vip' && $rank < 2) {
            $hostBlock = ['upgrade', 'Messaging Hosts is for VIP members.', url('/premium')];
        }
        if ($hostBlock !== null) {
            // With "credits also work for Hosts" switched on, a message credit opens the door here too.
            $paid = function_exists('dm_access') && $viewer['role'] !== 'host' ? dm_access($me, $targetId, $t) : null;
            return $paid ?? $deny(...$hostBlock);
        }
        return messaging_limit_check($me, $ok, $deny);
    }

    $mode = (string)setting('msg_access');
    $earlyPass = can('message_before_match', $me);
    $blocked = null;
    if ($mode === 'premium' && $rank < 1 && !$earlyPass) {
        $blocked = ['upgrade', 'Starting conversations is a Premium feature.', url('/premium')];
    } elseif ($mode === 'matched' && !users_matched($me, $targetId) && !$earlyPass) {
        $blocked = ['match', 'You can message each other once you match.', null];
    }
    if ($blocked !== null) {
        // International access opens the door to listed international members.
        if (function_exists('intl_message_ok') && $t['role'] === 'user' && intl_message_ok($me, $targetId)) {
            return messaging_limit_check($me, $ok, $deny);
        }
        // An accepted Elite request opens a chat between the two members.
        if (function_exists('elite_message_ok') && $t['role'] === 'user' && elite_message_ok($me, $targetId)) {
            return messaging_limit_check($me, $ok, $deny);
        }
        // A message credit opens the door when the site allows paid direct messages.
        $paid = function_exists('dm_access') ? dm_access($me, $targetId, $t) : null;
        return $paid ?? $deny(...$blocked);
    }
    return messaging_limit_check($me, $ok, $deny);
}

function messaging_limit_check(int $me, array $ok, callable $deny): array
{
    if (usage_remaining($me, 'messages') === 0) {
        return $deny('limit', 'You have used today\'s messages. Upgrade for more, or come back tomorrow.', url('/premium'));
    }
    return $ok;
}

// ---------------------------------------------------------------------------
// Visitor browsing
// ---------------------------------------------------------------------------

/** How a visitor (not signed in) sees profile pages: full, limited, login. */
function guest_profile_mode(): string
{
    return dating_gated() ? 'login' : (string)setting('guest_profile_view');
}

/** Dating members are only for people with an account (Settings > Privacy > Dating members): true for a visitor while that is on. */
function dating_gated(): bool
{
    return !is_logged_in() && (bool)setting('dating_requires_account');
}

/** How many people a visitor would see after joining (for the sign-up prompts). Cached for five minutes. */
function dating_member_count(): int
{
    return (int)cache_remember('dating_member_count', 300, fn() => (int)val("SELECT COUNT(*) FROM users u JOIN user_profiles p ON p.user_id = u.id
        WHERE u.status = 'active' AND u.role = 'user' AND p.onboarding_completed = 1 AND p.is_paused = 0 AND p.elite_only = 0", [], 0));
}

/** Can the viewer open the Hosts directory? Returns [bool, reasonCode]. */
function hosts_directory_allowed(): array
{
    if (!setting('hosts_enabled')) {
        return [false, 'disabled'];
    }
    $mode = (string)setting('hosts_directory_access');
    $tier = viewer_tier();
    switch ($mode) {
        case 'everyone':
            return [true, 'ok'];
        case 'logged_in':
            return [$tier !== 'guest', $tier === 'guest' ? 'login' : 'ok'];
        case 'premium':
            return [tier_rank($tier) >= 1, $tier === 'guest' ? 'login' : (tier_rank($tier) >= 1 ? 'ok' : 'upgrade')];
        default: // vip
            return [tier_rank($tier) >= 2, $tier === 'guest' ? 'login' : (tier_rank($tier) >= 2 ? 'ok' : 'upgrade')];
    }
}
