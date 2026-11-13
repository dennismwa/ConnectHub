<?php
/**
 * Messaging core: conversations, sending, contact masking, read state, inbox queries, retention.
 *
 * Pages and JSON endpoints live in app/social/. Who may message whom is decided by
 * messaging_access() in includes/privacy.php; nothing here re-implements that.
 */

declare(strict_types=1);

const MSG_MASK_TOKEN = '••••••••';
const MSG_PAGE_SIZE = 40;

// ---------------------------------------------------------------------------
// Conversations
// ---------------------------------------------------------------------------

/** Find or create the single conversation between two members and return its id. */
function conversation_get_or_create(int $a, int $b, ?int $initiatedBy = null): int
{
    [$x, $y] = pair_ids($a, $b);
    if ($x <= 0 || $x === $y) {
        throw new InvalidArgumentException('A conversation needs two different members.');
    }
    $id = val('SELECT id FROM conversations WHERE user1_id = :x AND user2_id = :y', ['x' => $x, 'y' => $y]);
    if ($id) {
        return (int)$id;
    }
    $by = $initiatedBy !== null && in_array($initiatedBy, [$x, $y], true) ? $initiatedBy : $x;
    q('INSERT IGNORE INTO conversations (user1_id, user2_id, initiated_by) VALUES (:x, :y, :by)', ['x' => $x, 'y' => $y, 'by' => $by]);
    return (int)val('SELECT id FROM conversations WHERE user1_id = :x AND user2_id = :y', ['x' => $x, 'y' => $y]);
}

/** A conversation as seen by one participant, or null when they are not part of it. */
function conversation_load(int $convId, int $userId): ?array
{
    $c = row('SELECT * FROM conversations WHERE id = :id AND (user1_id = :u OR user2_id = :u)', ['id' => $convId, 'u' => $userId]);
    if (!$c) {
        return null;
    }
    $side = (int)$c['user1_id'] === $userId ? 1 : 2;
    $c['me'] = $userId;
    $c['other_id'] = (int)($side === 1 ? $c['user2_id'] : $c['user1_id']);
    $c['my_side'] = $side;
    $c['other_side'] = $side === 1 ? 2 : 1;
    $c['my_unread'] = (int)$c["user{$side}_unread"];
    $c['my_archived'] = (int)$c["user{$side}_archived"] === 1;
    $c['my_muted'] = (int)$c["user{$side}_muted"] === 1;
    $c['pinned'] = (int)val('SELECT COUNT(*) FROM conversation_pins WHERE conversation_id = :c AND user_id = :u', ['c' => $convId, 'u' => $userId]) > 0;
    return $c;
}

/** Total unread messages for a member (the header badge). */
function messaging_unread_total(int $userId): int
{
    return (int)val(
        'SELECT COALESCE(SUM(IF(user1_id = :u, user1_unread, user2_unread)), 0) FROM conversations WHERE (user1_id = :u OR user2_id = :u)',
        ['u' => $userId],
        0
    );
}

/** The other person in a conversation, ready for display. Null when the account no longer exists. */
function messaging_peer(int $otherId): ?array
{
    $r = row(
        'SELECT u.id, u.role, u.status, u.last_active_at, p.display_name, p.show_online, p.show_last_active, p.is_verified, hp.display_name AS host_name, ' . SQL_PHOTO_COLS . '
         FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id LEFT JOIN host_profiles hp ON hp.user_id = u.id ' . sql_photo_join('u.id', 'ph') . '
         WHERE u.id = :id',
        ['id' => $otherId]
    );
    if (!$r) {
        return null;
    }
    $name = trim((string)($r['role'] === 'host' && !empty($r['host_name']) ? $r['host_name'] : $r['display_name'])) ?: 'Member';
    $online = !empty($r['last_active_at']) && (int)($r['show_online'] ?? 1) === 1
        && strtotime((string)$r['last_active_at']) > time() - max(1, (int)setting('online_window_minutes')) * 60;
    $seen = '';
    if (!$online && !empty($r['last_active_at']) && (int)($r['show_last_active'] ?? 1) === 1) {
        $seen = 'Active ' . time_ago((string)$r['last_active_at']);
    }
    return [
        'id' => (int)$r['id'], 'role' => (string)$r['role'], 'name' => $name, 'first_name' => explode(' ', $name)[0],
        'verified' => (int)($r['is_verified'] ?? 0) === 1, 'online' => $online, 'seen' => $seen,
        'available' => $r['status'] === 'active', 'photo' => photo_from_row($r),
        'profile_url' => url(($r['role'] === 'host' ? '/hosts/' : '/u/') . (int)$r['id']),
    ];
}

/** Pin, archive or mute a conversation for one participant. */
function conversation_set_flag(array $conv, string $flag, bool $on): void
{
    $side = (int)$conv['my_side'];
    $id = (int)$conv['id'];
    if ($flag === 'pinned') {
        if ($on) {
            q('INSERT IGNORE INTO conversation_pins (conversation_id, user_id) VALUES (:c, :u)', ['c' => $id, 'u' => $conv['me']]);
        } else {
            q('DELETE FROM conversation_pins WHERE conversation_id = :c AND user_id = :u', ['c' => $id, 'u' => $conv['me']]);
        }
        return;
    }
    $col = ['archived' => "user{$side}_archived", 'muted' => "user{$side}_muted"][$flag] ?? null;
    if ($col) {
        q("UPDATE conversations SET $col = :v WHERE id = :id", ['v' => $on ? 1 : 0, 'id' => $id]);
    }
}

// ---------------------------------------------------------------------------
// Contact masking
// ---------------------------------------------------------------------------

/**
 * Hide phone numbers, e-mail addresses and chat/social handles in a piece of text.
 * Pure function. Returns [maskedText, changed].
 */
function messaging_mask_text(string $text): array
{
    if ($text === '') {
        return ['', false];
    }
    $orig = $text;
    $mask = MSG_MASK_TOKEN;
    $apps = 'whats?\s?app|wh?a?ts?ap+|insta(?:gram)?|snap(?:chat)?|telegram|tiktok|facebook|signal|imo|wechat';
    $digitWords = 'zero|oh|one|two|three|four|five|six|seven|eight|nine|sifuri|moja|mbili|tatu|nne|tano|sita|saba|nane|tisa';

    // E-mail addresses, including "name (at) site (dot) com".
    $text = preg_replace('/[\p{L}\p{N}._%+\-]+\s*(?:@|\(at\)|\[at\]|\{at\})\s*[\p{L}\p{N}\-]+(?:\s*(?:\.|\(dot\)|\[dot\]|\{dot\})\s*[\p{L}\p{N}\-]+)+/iu', $mask, $text) ?? $text;
    // Links to chat and social apps.
    $text = preg_replace('#(?:https?://)?(?:www\.)?(?:wa\.me|api\.whatsapp\.com|chat\.whatsapp\.com|whatsapp\.com|t\.me|telegram\.me|instagram\.com|instagr\.am|facebook\.com|fb\.me|fb\.com|snapchat\.com|tiktok\.com|twitter\.com|x\.com|linkedin\.com)/\S*#iu', $mask, $text) ?? $text;
    // "whatsapp: 07..." / "insta @name" / "ig: name" (explicit separator), and "snapchat name_12" (handle with a digit).
    $text = preg_replace('/\b(?:' . $apps . '|ig)\b[^\p{L}\p{N}\n@]{0,12}[:=@]\s*@?[\p{L}\p{N}_.]{3,}/iu', $mask, $text) ?? $text;
    $text = preg_replace('/\b(?:' . $apps . ')\b\s*(?:id|handle|username|number|no\.?|nambari)?\s*(?:is|ni)?\s*@?[A-Za-z_.]*\d[\p{L}\p{N}_.]*/iu', $mask, $text) ?? $text;
    // Bare @handles that are left once e-mails are gone.
    $text = preg_replace('/(?<![\p{L}\p{N}_@.])@[A-Za-z0-9_.]{3,30}/u', $mask, $text) ?? $text;
    // Phone numbers: a run of digits with separators holding nine or more digits.
    $text = preg_replace_callback('/(?<![\p{L}\p{N}])(?:\+|00)?\(?\d[\d\s().\-]{6,}\d(?![\p{L}\p{N}])/u', function ($m) use ($mask) {
        return strlen((string)preg_replace('/\D/', '', $m[0])) >= 9 ? $mask : $m[0];
    }, $text) ?? $text;
    // Numbers spelled out in words ("zero seven one two ...").
    $text = preg_replace('/(?<![\p{L}])(?:(?:' . $digitWords . ')[\s,.\-]*){8,}(?![\p{L}])/iu', $mask, $text) ?? $text;
    // Collapse neighbouring tokens left by overlapping patterns.
    $text = preg_replace('/(?:' . preg_quote($mask, '/') . '[\s,]*){2,}/u', $mask . ' ', $text) ?? $text;
    $text = rtrim($text) === rtrim($orig) ? $orig : rtrim($text);
    return [$text, $text !== $orig];
}

/** Did these two members have a booking that unlocks contact details? */
function messaging_pair_has_booking(int $a, int $b): bool
{
    if (!setting('host_contact_after_booking')) {
        return false;
    }
    try {
        return (int)val(
            "SELECT COUNT(*) FROM host_bookings WHERE status IN ('confirmed','completed') AND ((host_id = :a AND client_id = :b) OR (host_id = :b AND client_id = :a))",
            ['a' => $a, 'b' => $b]
        ) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Build the context used to render messages for one viewer in one conversation
 * (counts and permissions are looked up once, not per message).
 */
function message_context(array $conv, int $viewerId): array
{
    $other = (int)$conv['other_id'];
    return [
        'conv_id' => (int)$conv['id'], 'viewer' => $viewerId, 'other' => $other, 'count' => null,
        'mask_on' => (bool)setting('msg_mask_contacts') && (int)setting('msg_unlock_after') > 0,
        'receipts' => messaging_receipts_visible($viewerId, $other),
        'profanity' => (string)setting('profanity_action'),
        'booking' => null, 'peer_name' => null,
    ];
}

/** Should contact details in a message from $senderId be hidden from the viewer? Fills the lazy counters in $ctx. */
function messaging_masking_applies(array &$ctx, int $senderId): bool
{
    if (!$ctx['mask_on']) {
        return false;
    }
    if (user_tier($ctx['viewer']) !== 'free' || user_tier($senderId) !== 'free') {
        return false;
    }
    if ($ctx['booking'] === null) {
        $ctx['booking'] = messaging_pair_has_booking($ctx['viewer'], $ctx['other']);
    }
    if ($ctx['booking']) {
        return false;
    }
    if ($ctx['count'] === null) {
        $ctx['count'] = (int)val("SELECT COUNT(*) FROM messages WHERE conversation_id = :c AND type <> 'system' AND is_deleted = 0", ['c' => $ctx['conv_id']]);
    }
    return $ctx['count'] < (int)setting('msg_unlock_after');
}

/** Apply the viewer's contact and banned-word rules to a piece of text. Returns [text, wasMasked]. */
function messaging_display_text(string $text, array &$ctx, int $senderId): array
{
    $masked = false;
    if ($text !== '') {
        [$m, $changed] = messaging_mask_text($text);
        if ($changed && messaging_masking_applies($ctx, $senderId)) {
            $text = $m;
            $masked = true;
        }
        if ($ctx['profanity'] === 'mask') {
            $text = mask_banned_words($text);
        }
    }
    return [$text, $masked];
}

// ---------------------------------------------------------------------------
// Read receipts
// ---------------------------------------------------------------------------

/** May $viewerId see whether $otherId has read their messages? */
function messaging_receipts_visible(int $viewerId, int $otherId): bool
{
    $mode = (string)setting('msg_read_receipts');
    if ($mode === 'off') {
        return false;
    }
    if ($mode === 'premium' && !can('read_receipts', $viewerId)) {
        return false;
    }
    $opt = val('SELECT read_receipts FROM user_profiles WHERE user_id = :u', ['u' => $otherId]);
    return $opt === null || (int)$opt === 1;
}

// ---------------------------------------------------------------------------
// Formatting messages for the browser
// ---------------------------------------------------------------------------

/** Width and height of a stored chat image, so the page can reserve space before it loads. */
function messaging_image_size(?string $rel): array
{
    if (!$rel) {
        return [0, 0];
    }
    $path = upload_abs($rel);
    $s = is_file($path) ? @getimagesize($path) : false;
    return $s ? [(int)$s[0], (int)$s[1]] : [0, 0];
}

/** Decode the JSON payload of a system message (gift notices, etc.). Plain text returns null. */
function message_system_payload(?string $content): ?array
{
    if ($content === null || $content === '' || $content[0] !== '{') {
        return null;
    }
    $d = json_decode($content, true);
    return is_array($d) && isset($d['kind']) ? $d : null;
}

/** One message as the browser receives it. The original text of a masked message never leaves the server. */
function message_format(array $m, array &$ctx): array
{
    $id = (int)$m['id'];
    $sender = (int)$m['sender_id'];
    $mine = $sender === $ctx['viewer'];
    $deleted = (int)$m['is_deleted'] === 1;
    $out = [
        'id' => $id, 'mine' => $mine, 'type' => (string)$m['type'], 'text' => '', 'media' => null, 'w' => 0, 'h' => 0,
        'ts' => (int)strtotime((string)$m['created_at']), 'deleted' => $deleted, 'masked' => false, 'system' => null, 'edited' => !empty($m['edited_at']),
        'read' => $mine && $ctx['receipts'] && (int)$m['is_read'] === 1,
    ];
    if ($deleted) {
        return $out;
    }
    if ($m['type'] === 'system') {
        $p = message_system_payload($m['content'] ?? null);
        if ($p && ($p['kind'] ?? '') === 'gift') {
            $who = $mine ? 'You sent' : (($ctx['peer_name'] ?? 'They') . ' sent you');
            $note = trim((string)($p['note'] ?? ''));
            $masked = false;
            if ($note !== '') {
                [$note, $masked] = messaging_display_text($note, $ctx, $sender);
            }
            $out['text'] = $who . ' a ' . (string)($p['gift'] ?? 'gift');
            $out['system'] = ['kind' => 'gift', 'emoji' => (string)($p['emoji'] ?? '🎁'), 'note' => $note];
            $out['masked'] = $masked;
        } else {
            $out['text'] = (string)$m['content'];
            $out['system'] = ['kind' => 'info', 'emoji' => '', 'note' => ''];
        }
        return $out;
    }
    [$text, $masked] = messaging_display_text((string)($m['content'] ?? ''), $ctx, $sender);
    $out['text'] = $text;
    $out['masked'] = $masked;
    if ($m['type'] === 'image' && !empty($m['media_path'])) {
        $out['media'] = url('/messages/media/' . $id);
        [$out['w'], $out['h']] = messaging_image_size((string)$m['media_path']);
    }
    return $out;
}

/** Messages of a conversation, oldest first. $beforeId pages backwards; $afterId polls forwards. */
function messages_fetch(int $convId, int $limit = MSG_PAGE_SIZE, int $beforeId = 0, int $afterId = 0): array
{
    $limit = max(1, min(100, $limit));
    if ($afterId > 0) {
        return rows('SELECT * FROM messages WHERE conversation_id = :c AND id > :a ORDER BY id ASC LIMIT ' . $limit, ['c' => $convId, 'a' => $afterId]);
    }
    $sql = 'SELECT * FROM messages WHERE conversation_id = :c' . ($beforeId > 0 ? ' AND id < :b' : '') . ' ORDER BY id DESC LIMIT ' . $limit;
    $params = ['c' => $convId] + ($beforeId > 0 ? ['b' => $beforeId] : []);
    return array_reverse(rows($sql, $params));
}

// ---------------------------------------------------------------------------
// Read state and typing
// ---------------------------------------------------------------------------

/** Mark everything the other person sent as read and clear this member's unread counter. */
function conversation_mark_read(array $conv): int
{
    $n = q(
        'UPDATE messages SET is_read = 1, read_at = NOW() WHERE conversation_id = :c AND sender_id <> :u AND is_read = 0',
        ['c' => $conv['id'], 'u' => $conv['me']]
    )->rowCount();
    $col = 'user' . (int)$conv['my_side'] . '_unread';
    q("UPDATE conversations SET $col = 0 WHERE id = :id AND $col <> 0", ['id' => $conv['id']]);
    return $n;
}

/** Highest id among the viewer's own messages that the other person has read (0 when none or receipts are hidden). */
function messaging_read_upto(array $conv, array $ctx): int
{
    if (!$ctx['receipts']) {
        return 0;
    }
    return (int)val('SELECT COALESCE(MAX(id), 0) FROM messages WHERE conversation_id = :c AND sender_id = :u AND is_read = 1 AND is_deleted = 0', ['c' => $conv['id'], 'u' => $conv['me']], 0);
}

function messaging_set_typing(int $convId, int $userId): void
{
    q('INSERT INTO typing_status (conversation_id, user_id, updated_at) VALUES (:c, :u, NOW()) ON DUPLICATE KEY UPDATE updated_at = NOW()', ['c' => $convId, 'u' => $userId]);
}

function messaging_clear_typing(int $convId, int $userId): void
{
    q('DELETE FROM typing_status WHERE conversation_id = :c AND user_id = :u', ['c' => $convId, 'u' => $userId]);
}

function messaging_other_typing(int $convId, int $otherId): bool
{
    return (int)val('SELECT COUNT(*) FROM typing_status WHERE conversation_id = :c AND user_id = :u AND updated_at > DATE_SUB(NOW(), INTERVAL 6 SECOND)', ['c' => $convId, 'u' => $otherId]) > 0;
}

// ---------------------------------------------------------------------------
// Sending
// ---------------------------------------------------------------------------

/**
 * Chat photos are private: the web server must not hand them out by address. They are read through
 * GET /messages/media/{id}, which checks that the viewer is one of the two people in the conversation.
 * (Apache honours this file; the development router refuses the folder too.)
 */
function messaging_protect_media_dir(): void
{
    try {
        $dir = upload_ensure_dir('messages');
        $file = $dir . '/.htaccess';
        if (!is_file($file)) {
            @file_put_contents($file, "# Private chat photos: served only through /messages/media/{id}.\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\nOptions -Indexes\n");
        }
    } catch (Throwable $e) {
        app_log('error', 'chat media folder could not be protected: ' . $e->getMessage());
    }
}

/** May the current member attach photos? Returns [bool, reason]. */
function messaging_images_allowed(): array
{
    $mode = (string)setting('msg_images');
    if ($mode === 'off') {
        return [false, 'Photo sharing is switched off.'];
    }
    if ($mode === 'premium' && !is_premium()) {
        return [false, 'Sharing photos is a Premium feature.'];
    }
    return [true, ''];
}

/** Trim a typed message, drop control characters and tame blank lines. */
function messaging_clean_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{200B}-\x{200F}\x{202A}-\x{202E}]/u', '', $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", trim($text)) ?? $text;
    return $text;
}

/** Detect repeated/bulk direct-message spam without penalising ordinary conversation. */
function messaging_spam_check(int $userId, int $conversationId, string $text): ?string
{
    $normal = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));
    if (mb_strlen($normal) < 12) return null;
    $hash = hash('sha256', $normal);
    $recent = (int)val("SELECT COUNT(*) FROM messages WHERE sender_id = :u AND content IS NOT NULL AND created_at >= NOW() - INTERVAL 10 MINUTE AND SHA2(LOWER(TRIM(content)), 256) = :h", ['u' => $userId, 'h' => $hash], 0);
    if ($recent >= 2) {
        if (mig_table_ready('message_abuse_events')) q('INSERT INTO message_abuse_events (user_id, conversation_id, reason, content_hash) VALUES (:u, :c, :r, :h)', ['u' => $userId, 'c' => $conversationId, 'r' => 'duplicate_message', 'h' => $hash]);
        return 'This looks like a repeated message. Please send a relevant, original message instead.';
    }
    $burst = (int)val("SELECT COUNT(*) FROM messages WHERE sender_id = :u AND created_at >= NOW() - INTERVAL 60 SECOND", ['u' => $userId], 0);
    if ($burst >= 18) {
        if (mig_table_ready('message_abuse_events')) q('INSERT INTO message_abuse_events (user_id, conversation_id, reason, content_hash) VALUES (:u, :c, :r, :h)', ['u' => $userId, 'c' => $conversationId, 'r' => 'message_burst', 'h' => null]);
        return 'You are sending messages too quickly. Please pause before sending more.';
    }
    return null;
}

/**
 * Send a message as the signed-in member. Handles access, limits, banned words, images,
 * unread counters and the recipient notification.
 *
 * @return array ['ok'=>bool, 'error'=>string, 'code'=>string, 'status'=>int, 'message'=>array, 'remaining'=>int, 'notice'=>string]
 */
function message_send(int $convId, string $text, ?array $imageFile = null): array
{
    $me = auth_id();
    $fail = fn(string $err, int $status = 422, string $code = 'invalid', array $extra = []) => ['ok' => false, 'error' => $err, 'status' => $status, 'code' => $code] + $extra;

    $conv = conversation_load($convId, $me);
    if (!$conv) {
        return $fail('That conversation was not found.', 404, 'not_found');
    }
    $acc = messaging_access($conv['other_id']);
    if (!$acc['allowed']) {
        return $fail($acc['message'], 403, $acc['code'], ['cta' => $acc['cta']]);
    }

    $text = messaging_clean_text($text);
    $hasImage = $imageFile && ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($text === '' && !$hasImage) {
        return $fail('Write a message first.');
    }
    $max = max(100, (int)setting('msg_max_length'));
    if (mb_strlen($text) > $max) {
        return $fail('That message is too long. The limit is ' . $max . ' characters.');
    }
    if ($text !== '' && ($spam = messaging_spam_check($me, $convId, $text)) !== null) {
        return $fail($spam, 429, 'spam');
    }
    if ($text !== '' && (string)setting('profanity_action') === 'block' && contains_banned_word($text) !== null) {
        return $fail('That message contains words that are not allowed here. Please rephrase it.');
    }
    if ($text !== '' && (string)setting('profanity_action') === 'mask') {
        $text = mask_banned_words($text);
    }

    $mediaPath = null;
    if ($hasImage) {
        [$okImg, $why] = messaging_images_allowed();
        if (!$okImg) {
            return $fail($why, 403, 'images');
        }
        messaging_protect_media_dir();
        $res = save_image($imageFile, 'messages', 1600, false, false, max(1, (int)setting('msg_max_image_mb')));
        if (!$res['ok']) {
            return $fail($res['error'] !== '' ? $res['error'] : 'We could not use that photo.');
        }
        $mediaPath = $res['path'];
    }

    $type = $mediaPath !== null ? 'image' : 'text';
    $otherSide = (int)$conv['other_side'];
    $dmNew = ($acc['dm'] ?? '') === 'new';
    $otherId = (int)$conv['other_id'];
    try {
        $msgId = db_transaction(function () use ($convId, $me, $type, $text, $mediaPath, $otherSide, $dmNew, $otherId) {
        if ($dmNew && !dm_unlock_spend($me, $otherId, $convId)) {
            throw new RuntimeException('dm_no_credit');
        }
        $id = insert('messages', [
            'conversation_id' => $convId, 'sender_id' => $me, 'type' => $type,
            'content' => $text !== '' ? $text : null, 'media_path' => $mediaPath,
        ]);
        q(
            "UPDATE conversations SET last_message_id = :m, last_message_at = NOW(), user{$otherSide}_unread = user{$otherSide}_unread + 1, user{$otherSide}_archived = 0 WHERE id = :c",
            ['m' => $id, 'c' => $convId]
        );
        return $id;
        });
    } catch (RuntimeException $e) {
        if ($e->getMessage() === 'dm_no_credit') {
            return $fail('You have no message credits left.', 403, 'paid', ['cta' => url('/messages/credits', ['to' => $otherId])]);
        }
        throw $e;
    }
    usage_add($me, 'messages');
    messaging_clear_typing($convId, $me);

    // Tell the recipient. notify() folds repeated message alerts from one sender into a single unread row.
    $muted = (int)$conv['user' . $otherSide . '_muted'] === 1;
    if (!$muted) {
        $senderName = current_user()['first_name'] ?? 'Someone';
        $preview = $type === 'image' && $text === '' ? 'Sent you a photo' : messaging_notification_preview($text);
        notify((int)$conv['other_id'], 'message', 'New message from ' . $senderName, $preview, '/messages/' . $convId, $me, ['conversation_id' => $convId]);
    }

    $m = row('SELECT * FROM messages WHERE id = :id', ['id' => $msgId]);
    $ctx = message_context($conv, $me);
    $ctx['peer_name'] = null;
    $out = message_format($m, $ctx);
    $notice = $out['masked'] ? 'Phone numbers, e-mails and social handles are hidden until you have exchanged more messages.' : '';
    return ['ok' => true, 'message' => $out, 'conversation_id' => $convId, 'remaining' => usage_remaining($me, 'messages'), 'notice' => $notice];
}

/** Notification and e-mail preview text: contact details are always hidden here. */
function messaging_notification_preview(string $text): string
{
    [$t] = messaging_mask_text($text);
    return str_limit($t, 140);
}

/**
 * Add a system line to a conversation (gift notices, etc.). $payload is stored as JSON.
 * The unread counter goes up for the member who did not trigger it.
 */
function message_post_system(int $convId, int $actorId, array $payload): int
{
    $conv = row('SELECT * FROM conversations WHERE id = :id', ['id' => $convId]);
    if (!$conv) {
        return 0;
    }
    $otherSide = (int)$conv['user1_id'] === $actorId ? 2 : 1;
    $id = insert('messages', ['conversation_id' => $convId, 'sender_id' => $actorId, 'type' => 'system', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
    q(
        "UPDATE conversations SET last_message_id = :m, last_message_at = NOW(), user{$otherSide}_unread = user{$otherSide}_unread + 1 WHERE id = :c",
        ['m' => $id, 'c' => $convId]
    );
    return $id;
}

/** Delete one of your own messages (soft delete: the row stays for moderation until retention removes it). */
function message_delete_own(int $messageId): array
{
    if (!setting('msg_delete_own')) {
        return ['ok' => false, 'error' => 'Deleting messages is switched off.', 'status' => 403];
    }
    $me = auth_id();
    $m = row("SELECT m.*, c.user1_id, c.user2_id FROM messages m JOIN conversations c ON c.id = m.conversation_id WHERE m.id = :id AND m.sender_id = :u AND m.type <> 'system'", ['id' => $messageId, 'u' => $me]);
    if (!$m) {
        return ['ok' => false, 'error' => 'You can only delete your own messages.', 'status' => 404];
    }
    if ((int)$m['is_deleted'] === 1) {
        return ['ok' => true, 'id' => $messageId];
    }
    db_transaction(function () use ($m, $me) {
        q('UPDATE messages SET is_deleted = 1 WHERE id = :id', ['id' => $m['id']]);
        if ((int)$m['is_read'] === 0) {
            $otherSide = (int)$m['user1_id'] === $me ? 2 : 1;
            q("UPDATE conversations SET user{$otherSide}_unread = GREATEST(0, user{$otherSide}_unread - 1) WHERE id = :c", ['c' => $m['conversation_id']]);
        }
    });
    return ['ok' => true, 'id' => $messageId];
}

/** Can the member still edit this message (own, plain text, not deleted, inside the time window the admin allows)? */
function message_can_edit(array $m, int $viewerId): bool
{
    if (!setting('msg_edit_own') || (int)$m['sender_id'] !== $viewerId || (string)$m['type'] !== 'text' || (int)$m['is_deleted'] === 1) {
        return false;
    }
    $win = (int)setting('msg_edit_window');
    return $win <= 0 || time() - (int)strtotime((string)$m['created_at']) <= $win * 60;
}

/** Edit one of your own messages. The new text goes through the same length and banned-word rules as a new message. */
function message_edit_own(int $messageId, string $text): array
{
    if (!setting('msg_edit_own')) {
        return ['ok' => false, 'error' => 'Editing messages is switched off.', 'status' => 403];
    }
    $me = auth_id();
    $m = row("SELECT m.*, c.user1_id, c.user2_id FROM messages m JOIN conversations c ON c.id = m.conversation_id WHERE m.id = :id AND m.sender_id = :u AND m.type <> 'system'", ['id' => $messageId, 'u' => $me]);
    if (!$m) {
        return ['ok' => false, 'error' => 'You can only edit your own messages.', 'status' => 404];
    }
    if (!message_can_edit($m, $me)) {
        $win = (int)setting('msg_edit_window');
        return ['ok' => false, 'error' => (string)$m['type'] !== 'text' ? 'Photos cannot be edited.' : ($win > 0 ? 'Messages can only be edited for ' . plural($win, 'minute') . ' after they are sent.' : 'This message can no longer be edited.'), 'status' => 403];
    }
    $text = messaging_clean_text($text);
    if ($text === '') {
        return ['ok' => false, 'error' => 'A message cannot be empty. Delete it instead.', 'status' => 422];
    }
    $max = max(100, (int)setting('msg_max_length'));
    if (mb_strlen($text) > $max) {
        return ['ok' => false, 'error' => 'That message is too long. The limit is ' . $max . ' characters.', 'status' => 422];
    }
    if ((string)setting('profanity_action') === 'block' && contains_banned_word($text) !== null) {
        return ['ok' => false, 'error' => 'That message contains words that are not allowed here. Please rephrase it.', 'status' => 422];
    }
    if ((string)setting('profanity_action') === 'mask') {
        $text = mask_banned_words($text);
    }
    if ($text !== (string)$m['content']) {
        q('UPDATE messages SET content = :t, edited_at = NOW() WHERE id = :id', ['t' => $text, 'id' => $messageId]);
        $m['content'] = $text;
        $m['edited_at'] = date('Y-m-d H:i:s');
    }
    $conv = conversation_load((int)$m['conversation_id'], $me);
    $ctx = message_context($conv, $me);
    $ctx['peer_name'] = null;
    return ['ok' => true, 'message' => message_format($m, $ctx)];
}

// ---------------------------------------------------------------------------
// Inbox
// ---------------------------------------------------------------------------

/** "14:05", "Yesterday", "Tue", "12 Oct" for conversation lists. */
function messaging_time_label(?string $dt): string
{
    $ts = $dt ? strtotime($dt) : false;
    if (!$ts) {
        return '';
    }
    $today = strtotime('today');
    if ($ts >= $today) {
        return date('g:i a', $ts);
    }
    if ($ts >= $today - 86400) {
        return 'Yesterday';
    }
    if ($ts >= $today - 6 * 86400) {
        return date('D', $ts);
    }
    return date(date('Y', $ts) === date('Y') ? 'j M' : 'j M Y', $ts);
}

/**
 * Conversation list for a member. $view is 'active' or 'archived'. A just-opened empty
 * conversation ($includeId) is always included so it appears while you write the first message.
 */
function messaging_inbox(int $userId, string $view = 'active', int $limit = 80, int $includeId = 0): array
{
    $arch = $view === 'archived' ? '1' : '0';
    $sql = 'SELECT c.id, c.last_message_id, c.last_message_at, c.created_at, c.initiated_by, o.id AS other_id, o.role AS other_role, o.last_active_at,
                   p.display_name, p.show_online, p.is_verified, hp.display_name AS host_name,
                   IF(c.user1_id = :u, c.user1_unread, c.user2_unread) AS my_unread,
                   IF(c.user1_id = :u, c.user1_archived, c.user2_archived) AS my_archived,
                   IF(c.user1_id = :u, c.user1_muted, c.user2_muted) AS my_muted,
                   (cp.user_id IS NOT NULL) AS pinned,
                   m.type AS last_type, m.content AS last_content, m.sender_id AS last_sender, m.is_deleted AS last_deleted,
                   mt.is_active AS match_active, ' . SQL_PHOTO_COLS . '
            FROM conversations c
            JOIN users o ON o.id = IF(c.user1_id = :u, c.user2_id, c.user1_id)
            LEFT JOIN user_profiles p ON p.user_id = o.id
            LEFT JOIN host_profiles hp ON hp.user_id = o.id
            LEFT JOIN messages m ON m.id = c.last_message_id
            LEFT JOIN conversation_pins cp ON cp.conversation_id = c.id AND cp.user_id = :u
            LEFT JOIN matches mt ON mt.user1_id = c.user1_id AND mt.user2_id = c.user2_id
            ' . sql_photo_join('o.id', 'ph') . "
            WHERE (c.user1_id = :u OR c.user2_id = :u)
              AND (IF(c.user1_id = :u, c.user1_archived, c.user2_archived) = $arch OR c.id = :inc)
              AND (c.last_message_id IS NOT NULL OR c.id = :inc)
              AND o.status = 'active'
              AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.blocker_id = :u AND b.blocked_id = o.id) OR (b.blocker_id = o.id AND b.blocked_id = :u))
            ORDER BY pinned DESC, COALESCE(c.last_message_at, c.created_at) DESC, c.id DESC
            LIMIT " . max(1, min(200, $limit));
    $out = [];
    foreach (rows($sql, ['u' => $userId, 'inc' => $includeId]) as $r) {
        $name = trim((string)($r['other_role'] === 'host' && !empty($r['host_name']) ? $r['host_name'] : $r['display_name'])) ?: 'Member';
        $online = !empty($r['last_active_at']) && (int)($r['show_online'] ?? 1) === 1
            && strtotime((string)$r['last_active_at']) > time() - max(1, (int)setting('online_window_minutes')) * 60;
        $mineLast = (int)$r['last_sender'] === $userId;
        $preview = '';
        if ($r['last_message_id']) {
            if ((int)$r['last_deleted'] === 1) {
                $preview = 'Message deleted';
            } elseif ($r['last_type'] === 'image') {
                $preview = '📷 Photo';
            } elseif ($r['last_type'] === 'system') {
                $p = message_system_payload($r['last_content']);
                $preview = $p && ($p['kind'] ?? '') === 'gift' ? ($p['emoji'] ?? '🎁') . ' Gift: ' . ($p['gift'] ?? '') : (string)$r['last_content'];
            } else {
                $preview = (string)$r['last_content'];
                [$masked, $changed] = messaging_mask_text($preview);
                if ($changed) {
                    $ctx = message_context(['id' => $r['id'], 'other_id' => $r['other_id']], $userId);
                    if (messaging_masking_applies($ctx, (int)$r['last_sender'])) {
                        $preview = $masked;
                    }
                }
                if ((string)setting('profanity_action') === 'mask') {
                    $preview = mask_banned_words($preview);
                }
                $preview = str_limit($preview, 90);
            }
        } else {
            $preview = 'Say hello';
        }
        $out[] = [
            'id' => (int)$r['id'], 'other_id' => (int)$r['other_id'], 'name' => $name, 'role' => (string)$r['other_role'],
            'photo' => photo_from_row($r), 'online' => $online, 'verified' => (int)($r['is_verified'] ?? 0) === 1,
            'unread' => (int)$r['my_unread'], 'pinned' => (int)$r['pinned'] === 1, 'muted' => (int)$r['my_muted'] === 1,
            'archived' => (int)$r['my_archived'] === 1, 'unmatched' => $r['match_active'] !== null && (int)$r['match_active'] === 0,
            'preview' => $preview, 'mine_last' => $mineLast && $r['last_type'] !== 'system', 'empty' => !$r['last_message_id'],
            'time' => $r['last_message_at'] ? messaging_time_label((string)$r['last_message_at']) : '', 'ts' => (int)strtotime((string)($r['last_message_at'] ?: $r['created_at'])),
            'last_id' => (int)$r['last_message_id'],
        ];
    }
    return $out;
}

/** A short fingerprint of the inbox so the browser can skip re-rendering when nothing changed. */
function messaging_inbox_signature(array $items): string
{
    $s = '';
    foreach ($items as $i) {
        $s .= $i['id'] . ':' . $i['last_id'] . ':' . $i['unread'] . ':' . (int)$i['pinned'] . ':' . (int)$i['muted'] . ':' . (int)$i['online'] . ';';
    }
    return substr(md5($s), 0, 16);
}

/** Random conversation starters from the admin's list. */
function messaging_icebreakers(): array
{
    if (!setting('msg_icebreakers')) {
        return [];
    }
    return col('SELECT text FROM ice_breakers WHERE is_active = 1 ORDER BY RAND() LIMIT ' . max(1, min(6, (int)setting('msg_icebreaker_count'))));
}

// ---------------------------------------------------------------------------
// Thread data for the browser
// ---------------------------------------------------------------------------

/** What the browser needs to draw and run one thread. Everything here is already filtered for the viewer. */
function messaging_thread_data(array $conv, array $peer, array $viewer): array
{
    $uid = (int)$viewer['id'];
    $ctx = message_context($conv, $uid);
    $ctx['peer_name'] = $peer['first_name'];
    $raw = messages_fetch((int)$conv['id'], MSG_PAGE_SIZE);
    $messages = [];
    foreach ($raw as $m) {
        $messages[] = message_format($m, $ctx);
    }
    $hasMore = $raw && (int)val('SELECT COUNT(*) FROM messages WHERE conversation_id = :c AND id < :m', ['c' => $conv['id'], 'm' => $raw[0]['id']]) > 0;
    $acc = messaging_access((int)$conv['other_id']);
    [$imgOk, $imgWhy] = messaging_images_allowed();
    $match = row('SELECT is_active FROM matches WHERE user1_id = :a AND user2_id = :b', ['a' => min($uid, (int)$conv['other_id']), 'b' => max($uid, (int)$conv['other_id'])]);
    return [
        'conv' => ['id' => (int)$conv['id'], 'muted' => (bool)$conv['my_muted'], 'archived' => (bool)$conv['my_archived'], 'pinned' => (bool)$conv['pinned'], 'unmatched' => $match !== null && (int)$match['is_active'] === 0],
        'peer' => messaging_peer_payload($peer),
        'me' => ['id' => $uid, 'premium' => tier_rank((string)$viewer['tier']) >= 1],
        'access' => ['allowed' => (bool)$acc['allowed'], 'code' => (string)$acc['code'], 'message' => (string)$acc['message'], 'cta' => $acc['cta'], 'dm' => $acc['dm'] ?? null, 'dm_left' => $acc['dm_left'] ?? null],
        'limits' => ['max' => max(100, (int)setting('msg_max_length')), 'images' => $imgOk, 'images_why' => $imgWhy, 'max_mb' => max(1, (int)setting('msg_max_image_mb')), 'remaining' => usage_remaining($uid, 'messages')],
        'receipts' => $ctx['receipts'], 'typing_enabled' => (bool)setting('msg_typing'), 'poll_ms' => max(1500, (int)setting('msg_poll_ms')),
        'can_delete' => (bool)setting('msg_delete_own'), 'can_edit' => (bool)setting('msg_edit_own'), 'edit_window' => max(0, (int)setting('msg_edit_window')) * 60, 'server_now' => time(), 'gifts' => (bool)setting('gifts_enabled') && $peer['available'],
        'messages' => $messages, 'has_more' => $hasMore, 'last_id' => $raw ? (int)end($raw)['id'] : 0,
        'read_upto' => messaging_read_upto($conv, $ctx), 'typing' => $acc['allowed'] && setting('msg_typing') && messaging_other_typing((int)$conv['id'], (int)$conv['other_id']),
        'icebreakers' => $raw ? [] : messaging_icebreakers(),
        'unlock_after' => (int)setting('msg_unlock_after'),
    ];
}

/** The peer fields the browser needs (the photo goes through the privacy-aware URL helper). */
function messaging_peer_payload(array $peer): array
{
    return [
        'id' => $peer['id'], 'name' => $peer['name'], 'first_name' => $peer['first_name'], 'role' => $peer['role'], 'verified' => $peer['verified'],
        'online' => $peer['online'], 'seen' => $peer['seen'], 'available' => $peer['available'], 'profile_url' => $peer['profile_url'],
        'avatar' => photo_url($peer['photo'], 'thumb', 'profile'),
    ];
}

// ---------------------------------------------------------------------------
// Retention
// ---------------------------------------------------------------------------

/** Delete messages older than msg_retention_days (and their photos). Called from the daily cron job. Returns the number removed. */
function messaging_purge_old(): int
{
    $days = (int)setting('msg_retention_days');
    $removed = 0;
    if ($days > 0) {
        $cutoff = date('Y-m-d H:i:s', time() - $days * 86400);
        for ($i = 0; $i < 50; $i++) {
            foreach (col('SELECT media_path FROM messages WHERE created_at < :c AND media_path IS NOT NULL LIMIT 500', ['c' => $cutoff]) as $path) {
                upload_delete((string)$path);
            }
            $n = q('DELETE FROM messages WHERE created_at < :c LIMIT 1000', ['c' => $cutoff])->rowCount();
            $removed += $n;
            if ($n < 1000) {
                break;
            }
        }
        if ($removed > 0) {
            q('UPDATE conversations c SET c.last_message_id = (SELECT MAX(m.id) FROM messages m WHERE m.conversation_id = c.id),
                   c.last_message_at = (SELECT MAX(m.created_at) FROM messages m WHERE m.conversation_id = c.id)
               WHERE c.last_message_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM messages m2 WHERE m2.id = c.last_message_id)');
            q('UPDATE conversations SET user1_unread = 0, user2_unread = 0 WHERE last_message_id IS NULL');
        }
    }
    q('DELETE FROM typing_status WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    q('DELETE FROM conversations WHERE last_message_id IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    return $removed;
}
