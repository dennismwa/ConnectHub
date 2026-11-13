<?php
/**
 * Elite, the public side the admin shapes: web address, page text, search appearance, colour, "how it works", questions and answers,
 * member numbers, and the saved list (shortlist). The members, requests, unlocks and payments live in elite.php.
 *
 *   elite_slug()          the word after the site name ("elite" by default; the admin can change it, for example to "circle")
 *   elite_path_out/in()   /elite/5 -> /circle/5 for links, and back for incoming requests (the same idea as the Hosts address)
 *   elite_url_early()     the old address sends people (and search engines) to the new one
 *   elite_seo_save()      title, description, share picture and noindex of an Elite page (stored in seo_pages, like every other page)
 *   elite_saved_ids()     a member's shortlist
 */

const ELITE_SLUG_DEFAULT = 'elite';
const ELITE_PUBLIC_MIN = 3;

// ---------------------------------------------------------------------------
// Web address
// ---------------------------------------------------------------------------

function elite_slug(bool $fresh = false): string
{
    static $s = null;
    if ($s !== null && !$fresh) {
        return $s;
    }
    try {
        $v = strtolower(trim((string)setting('elite_slug')));
    } catch (Throwable $e) {
        $v = '';
    }
    return $s = preg_match('/^[a-z0-9](?:[a-z0-9-]{0,28}[a-z0-9])?$/', $v) ? $v : ELITE_SLUG_DEFAULT;
}

/** /elite/5 -> /circle/5, when the admin changed the address. */
function elite_path_out(string $path): string
{
    $s = elite_slug();
    if ($s === ELITE_SLUG_DEFAULT || $path === '' || $path[0] !== '/') {
        return $path;
    }
    if (preg_match('#^/elite(?=$|[/?\#])#', $path)) {
        return '/' . $s . substr($path, 6);
    }
    return $path;
}

/** The reverse, for incoming requests, so the routes need no change. */
function elite_path_in(string $path): string
{
    $s = elite_slug();
    if ($s === ELITE_SLUG_DEFAULT) {
        return $path;
    }
    if (preg_match('#^/' . preg_quote($s, '#') . '(?=$|/)#i', $path)) {
        return '/elite' . substr($path, strlen($s) + 1);
    }
    return $path;
}

/** The default address and the previous one send people to the current one. Runs before routing. */
function elite_url_early(): void
{
    if (CH_CLI || !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        return;
    }
    $s = elite_slug();
    $old = '';
    try {
        $old = strtolower(trim((string)setting('elite_slug_old')));
    } catch (Throwable $e) {
    }
    if ($s === ELITE_SLUG_DEFAULT && $old === '') {
        return;
    }
    $raw = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = base_path();
    if ($base !== '' && strpos($raw, $base) === 0) {
        $raw = substr($raw, strlen($base));
    }
    $raw = '/' . trim($raw, '/');
    $to = null;
    if ($s !== ELITE_SLUG_DEFAULT && preg_match('#^/elite(?=$|/)#', $raw)) {
        $to = '/' . $s . substr($raw, 6);
    } elseif ($old !== '' && $old !== $s && preg_match('#^/' . preg_quote($old, '#') . '(?=$|/)#i', $raw)) {
        $to = '/' . $s . substr($raw, strlen($old) + 1);
    }
    if ($to !== null) {
        $q = (string)($_SERVER['QUERY_STRING'] ?? '');
        header('Location: ' . $base . $to . ($q !== '' ? '?' . $q : ''), true, 301);
        exit;
    }
}

/** Turns what the admin typed into a web address word. */
function elite_slugify(string $s): string
{
    return host_slugify($s);
}

/** Error text when this word cannot be the Elite address, or null when it can. */
function elite_slug_error(string $slug): ?string
{
    if ($slug === ELITE_SLUG_DEFAULT) {
        return null;
    }
    if (!preg_match('/^[a-z0-9][a-z0-9-]{0,28}[a-z0-9]$/', $slug)) {
        return 'Use 2 to 30 lowercase letters, numbers or hyphens.';
    }
    $used = '"' . $slug . '" is already used by the site. Pick another word.';
    $hl = host_labels();
    if (in_array($slug, [$hl['slug_p'], $hl['slug_s'], 'hosts', 'host'], true) || in_array($slug, HOST_RESERVED_SLUGS, true)) {
        return $used;
    }
    foreach (route_table() as $r) {
        $first = explode('/', trim((string)$r['pattern'], '/'))[0] ?? '';
        if ($first !== '' && $first[0] !== '{' && strtolower($first) === $slug) {
            return $used;
        }
    }
    try {
        if (val('SELECT id FROM pages WHERE slug = :s LIMIT 1', ['s' => $slug])) {
            return 'A page called "' . $slug . '" already exists.';
        }
        if (val('SELECT id FROM seo_redirects WHERE from_path = :p LIMIT 1', ['p' => '/' . $slug])) {
            return 'A redirect already uses /' . $slug . '. Remove it first under SEO.';
        }
    } catch (Throwable $e) {
    }
    return null;
}

// ---------------------------------------------------------------------------
// Look and page text
// ---------------------------------------------------------------------------

function elite_themes(): array
{
    return ['gold' => 'Champagne gold', 'rose' => 'Rose', 'emerald' => 'Emerald', 'sapphire' => 'Sapphire', 'amethyst' => 'Amethyst'];
}

function elite_theme(): string
{
    $t = (string)setting('elite_theme');
    return isset(elite_themes()[$t]) ? $t : 'gold';
}

/** The page banner picture as a URL, or '' for none. */
function elite_hero_image_url(): string
{
    $p = trim((string)setting('elite_hero_image'));
    return $p !== '' ? upload_url($p) : '';
}

/** Questions and answers the admin typed: a question line, the answer under it, a blank line between items. */
function elite_faq_items(): array
{
    $raw = trim(str_replace("\r", '', (string)setting('elite_faq')));
    $items = [];
    foreach (preg_split('/\n\s*\n/', $raw) ?: [] as $block) {
        $lines = explode("\n", trim($block), 2);
        $q = trim($lines[0] ?? '');
        $a = trim($lines[1] ?? '');
        if ($q !== '' && $a !== '') {
            $items[] = ['q' => mb_substr($q, 0, 160), 'a' => mb_substr($a, 0, 900)];
        }
        if (count($items) >= 12) {
            break;
        }
    }
    return $items;
}

/** The four steps on the page, worded for how full profiles open on this site. */
function elite_steps(): array
{
    $label = elite_label();
    $mode = elite_view_mode();
    $third = $mode === 'free'
        ? ['eye', 'See the full profile', 'Signed-in members see every photo and the whole profile.']
        : ($mode === 'accept'
            ? ['pencil', 'Introduce yourself', 'Write a short, honest request. Members read every one.']
            : ['key-round', 'Open the ones you like', 'Unlock one profile, a pack, or a pass. You see it before you pay for more.']);
    $fourth = $mode === 'accept'
        ? ['message-circle', 'Chat when they accept', 'The full profile and a private chat open once they say yes.']
        : ['message-circle', 'Connect by request', 'Send a request. If they accept, a private chat opens for you both.'];
    return [
        ['user-plus', 'Create your profile', 'Sign up free. ' . $label . ' members are reviewed by our team before they appear.'],
        ['compass', 'Browse the circle', 'Filter by place, age and lifestyle. Cards show a first name, age, place and a blurred photo.'],
        $third,
        $fourth,
    ];
}

// ---------------------------------------------------------------------------
// Public numbers, cards for the banner
// ---------------------------------------------------------------------------

/** Where an Elite profile counts as live (approved, and paid when joining costs money). Same rule as the search. */
function elite_live_conditions(): array
{
    $where = ["e.status = 'approved'", "u.status = 'active'", "u.role = 'user'"];
    if (elite_join_fee() > 0) {
        $who = (string)setting('elite_join_free_for');
        $exempt = $who === 'none' || $who === '' ? '' : " OR EXISTS (SELECT 1 FROM user_subscriptions s JOIN subscription_plans sp ON sp.id = s.plan_id WHERE s.user_id = e.user_id AND s.status = 'active' AND s.expires_at > NOW() AND sp.tier >= " . ($who === 'vip' ? 2 : 1) . ')';
        $where[] = "(e.paid_until > NOW()$exempt)";
    }
    return $where;
}

/** Live members, verified members and places. Cached for ten minutes. Shown only once there are enough to look real. */
function elite_public_stats(): array
{
    if (!mig_table_ready('elite_profiles')) {
        return ['members' => 0, 'verified' => 0, 'places' => 0, 'show' => false];
    }
    $s = cache_remember('elite_public_stats', 600, function () {
        $from = 'FROM elite_profiles e JOIN users u ON u.id = e.user_id LEFT JOIN user_locations l ON l.user_id = e.user_id WHERE ' . implode(' AND ', elite_live_conditions());
        return [
            'members' => (int)val("SELECT COUNT(*) $from", [], 0),
            'verified' => (int)val("SELECT COUNT(*) $from AND e.is_verified = 1", [], 0),
            'places' => (int)val("SELECT COUNT(DISTINCT l.county_id) $from AND l.county_id IS NOT NULL", [], 0),
        ];
    });
    $s['show'] = (bool)setting('elite_hero_stats') && (int)$s['members'] >= ELITE_PUBLIC_MIN;
    return $s;
}

function elite_stats_forget(): void
{
    cache_forget('elite_public_stats');
    cache_forget('elite_landing');
}

/** Up to three member cards for the banner (blurred, as visitors see them). Empty when the admin turned it off or nobody is live yet. */
function elite_hero_cards(int $viewerId): array
{
    if (!setting('elite_hero_cards') || (!$viewerId && !setting('elite_guest_preview'))) {
        return [];
    }
    $res = elite_search($viewerId, ['sort' => 'best'], 3);
    $cards = array_map(fn($r) => elite_card($r, $viewerId), $res['rows']);
    return count($cards) >= 2 ? $cards : [];
}

// ---------------------------------------------------------------------------
// Saved list
// ---------------------------------------------------------------------------

const ELITE_SAVE_MAX = 200;

function elite_saves_enabled(): bool
{
    return elite_enabled() && (bool)setting('elite_saves_enabled') && mig_table_ready('elite_saves');
}

/** The member's shortlist: owner ids. */
function elite_saved_ids(int $uid): array
{
    if ($uid <= 0 || !mig_table_ready('elite_saves')) {
        return [];
    }
    return array_map('intval', array_column(rows('SELECT owner_id FROM elite_saves WHERE user_id = :u ORDER BY created_at DESC LIMIT ' . ELITE_SAVE_MAX, ['u' => $uid]), 'owner_id'));
}

function elite_saved_count(int $uid): int
{
    if ($uid <= 0 || !mig_table_ready('elite_saves')) {
        return 0;
    }
    $f = ['saved_by' => $uid];
    return (int)elite_search($uid, $f, 1)['total'];
}

/** How many members saved this profile (only the number is ever shown, never who). */
function elite_saves_of(int $ownerId): int
{
    if (!mig_table_ready('elite_saves')) {
        return 0;
    }
    return (int)val('SELECT COUNT(*) FROM elite_saves WHERE owner_id = :o', ['o' => $ownerId], 0);
}

/** Save or un-save. $want true saves, false removes, null flips. Returns [ok, saved now, message]. */
function elite_save_toggle(int $uid, int $ownerId, ?bool $want = null): array
{
    if (!elite_saves_enabled()) {
        return [false, false, 'Saving is switched off.'];
    }
    $has = (bool)val('SELECT 1 FROM elite_saves WHERE user_id = :u AND owner_id = :o', ['u' => $uid, 'o' => $ownerId], 0);
    $save = $want === null ? !$has : $want;
    if (!$save) {
        q('DELETE FROM elite_saves WHERE user_id = :u AND owner_id = :o', ['u' => $uid, 'o' => $ownerId]);
        return [true, false, 'Removed from your saved list.'];
    }
    if ($has) {
        return [true, true, 'Saved.'];
    }
    $p = elite_profile($ownerId);
    if ($uid === $ownerId || !$p || !elite_profile_live($p)) {
        return [false, false, 'That profile is not available.'];
    }
    if ((string)val("SELECT status FROM users WHERE id = :u AND role = 'user'", ['u' => $ownerId], '') !== 'active' || users_blocked($uid, $ownerId)) {
        return [false, false, 'That profile is not available.'];
    }
    if ((int)val('SELECT COUNT(*) FROM elite_saves WHERE user_id = :u', ['u' => $uid], 0) >= ELITE_SAVE_MAX) {
        return [false, false, 'Your saved list is full (' . ELITE_SAVE_MAX . '). Remove someone first.'];
    }
    q('INSERT IGNORE INTO elite_saves (user_id, owner_id) VALUES (:u, :o)', ['u' => $uid, 'o' => $ownerId]);
    return [true, true, 'Saved to your list.'];
}

/** What an Elite member sees about their own page: views, saves, requests. */
function elite_owner_insights(int $uid): array
{
    $views = (int)val('SELECT views FROM elite_profiles WHERE user_id = :u', ['u' => $uid], 0);
    return [
        'views' => $views,
        'saves' => elite_saves_of($uid),
        'requests' => (int)val('SELECT COUNT(*) FROM elite_requests WHERE to_id = :u AND created_at >= NOW() - INTERVAL 30 DAY', ['u' => $uid], 0),
        'accepted' => (int)val("SELECT COUNT(*) FROM elite_requests WHERE to_id = :u AND status = 'accepted'", ['u' => $uid], 0),
    ];
}

// ---------------------------------------------------------------------------
// Search appearance (stored in seo_pages next to every other page's)
// ---------------------------------------------------------------------------

/**
 * Save the search title, description, share picture and noindex of 'elite' or 'elite_access'.
 * Returns ['errors' => [...]] or ['ok' => true]. Call ac_image_commit() with ['img'] after the row is written.
 */
function elite_seo_save(string $key, array $b): array
{
    $keys = ['elite', 'elite_access'];
    if (!in_array($key, $keys, true)) {
        return ['errors' => ['page_key' => 'That page cannot be edited here.']];
    }
    $old = row('SELECT * FROM seo_pages WHERE page_key = :k', ['k' => $key]);
    $title = trim(preg_replace('/\s+/', ' ', (string)($b['title'] ?? '')) ?? '');
    $desc = trim(preg_replace('/\s+/', ' ', (string)($b['description'] ?? '')) ?? '');
    $errors = [];
    if (mb_strlen($title) > 120) {
        $errors['title'] = 'The title can be up to 120 characters. Google shows about 60.';
    }
    if (mb_strlen($desc) > 300) {
        $errors['description'] = 'The description can be up to 300 characters. Google shows about 155.';
    }
    $img = ac_image_save('og', 'seo', $old['og_image'] ?? null, ['maxSide' => 1600]);
    if ($img['error'] !== '') {
        $errors['og'] = $img['error'];
    }
    if ($errors) {
        ac_image_commit($img, true);
        return ['errors' => $errors];
    }
    q('INSERT INTO seo_pages (page_key, title, description, og_image, noindex) VALUES (:k, :t, :d, :i, :n)
       ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), og_image = VALUES(og_image), noindex = VALUES(noindex)',
        ['k' => $key, 't' => $title !== '' ? $title : null, 'd' => $desc !== '' ? $desc : null, 'i' => $img['path'], 'n' => !empty($b['noindex']) ? 1 : 0]);
    ac_image_commit($img);
    return ['ok' => true];
}

/** Structured data for the Elite page: the page itself and its questions. */
function elite_page_schema(string $name, string $description, array $faq): array
{
    $nodes = [['@type' => 'CollectionPage', 'name' => $name, 'description' => $description, 'url' => abs_url('/elite'), 'isPartOf' => ['@type' => 'WebSite', 'name' => site_name(), 'url' => abs_url('/')]]];
    if ($faq && function_exists('seo_faq_schema') && ($f = seo_faq_schema($faq))) {
        unset($f['@context']);
        $nodes[] = $f;
    }
    return ['@context' => 'https://schema.org', '@graph' => $nodes];
}
