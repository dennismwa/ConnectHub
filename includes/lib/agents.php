<?php
/**
 * Agent showcase: which Hosts ("agents") the landing page and other showcases display, in what order, and how each card reads.
 *
 * An agent can sit in several tiers at once. The admin chooses which tiers a showcase block shows (Admin > Hero and landing >
 * Page sections > an "agents" block) and whether other approved agents may fill the block when too few qualify, so a block is
 * never empty just because nobody has paid yet.
 *
 *   vip       set by an admin on the Host page, or a VIP subscription
 *   featured  featured by an admin, or a paid "featured" pack
 *   premium   a Premium subscription
 *   paid      a paid directory listing that is running
 *   boosted   an active boost
 *
 * Cache keys start with "landing_agents_" so cache_clear('landing_') also clears them.
 */

declare(strict_types=1);

/** Tiers, strongest first: key => [label, weight, icon]. The weight decides the order when a block shows several. */
function agent_tiers(): array
{
    return [
        'vip' => ['VIP', 1000, 'crown'],
        'featured' => ['Featured', 800, 'sparkles'],
        'premium' => ['Prime', 600, 'gem'],
        'paid' => ['Listed', 500, 'badge-check'],
        'boosted' => ['Boosted', 400, 'rocket'],
    ];
}

/** Ways a showcase block can order agents of the same tier. */
function agent_sorts(): array
{
    return [
        'rotate' => 'Fair rotation (changes every hour, so every agent gets seen)',
        'rating' => 'Best rated first',
        'newest' => 'Newest first',
        'active' => 'Most recently active first',
        'nearest' => 'Nearest to the visitor (falls back to rotation when the place is unknown)',
    ];
}

/** Are agents allowed on public pages at all (Hosts on, and the directory not closed to this visitor)? An 18+ prompt still counts as open. */
function agents_public_ok(): bool
{
    if (!function_exists('hosts_enabled') || !hosts_enabled()) {
        return false;
    }
    [$ok, $why] = hosts_directory_gate();
    return $ok || $why === 'age';
}

/**
 * Tier facts for approved, active agents, optionally limited to one category.
 * @return array<int, array> id => [vip, featured, premium, paid, boosted (bool), pin (int), rating, reviews, approved (ts), active (ts), verified (bool)]
 */
function agents_candidates(int $categoryId = 0, int $limit = 600, int $onlyId = 0): array
{
    $params = [];
    $where = ["hp.approval_status = 'approved'", "u.status = 'active'"];
    if ($categoryId > 0) {
        $where[] = 'hp.category_id = :cat';
        $params['cat'] = $categoryId;
    }
    if ($onlyId > 0) {
        $where[] = 'hp.user_id = :only';
        $params['only'] = $onlyId;
    }
    $listing = hosts_listing_required() ? hosts_listing_sql('hp') : '';
    $sql = "SELECT hp.user_id AS id, hp.is_featured, hp.rating_avg, hp.review_count, hp.id_verified, hp.photo_verified, hp.approved_at,
                   u.last_active_at, u.created_at AS joined_at, (u.featured_until IS NOT NULL AND u.featured_until > NOW()) AS paid_featured,
                   COALESCE(x.is_vip, 0) AS admin_vip, COALESCE(x.pin_order, 0) AS pin_order,
                   (x.listing_until IS NOT NULL AND x.listing_until > NOW()) AS paid_listing,
                   (SELECT MAX(pl.tier) FROM user_subscriptions s JOIN subscription_plans pl ON pl.id = s.plan_id
                     WHERE s.user_id = u.id AND s.status = 'active' AND s.expires_at > NOW()) AS sub_tier,
                   EXISTS (SELECT 1 FROM boosts b WHERE b.user_id = u.id AND b.expires_at > NOW()) AS is_boosted
            FROM host_profiles hp JOIN users u ON u.id = hp.user_id LEFT JOIN host_profile_ext x ON x.user_id = hp.user_id
            WHERE " . implode(' AND ', $where) . $listing . ' ORDER BY hp.user_id LIMIT ' . max(1, min(2000, $limit));
    $out = [];
    foreach (rows($sql, $params) as $r) {
        $sub = $r['sub_tier'] === null ? 0 : (int)$r['sub_tier'];
        $out[(int)$r['id']] = [
            'vip' => (int)$r['admin_vip'] === 1 || $sub >= 2,
            'featured' => (int)$r['is_featured'] === 1 || (int)$r['paid_featured'] === 1,
            'premium' => $sub === 1,
            'paid' => (int)$r['paid_listing'] === 1,
            'boosted' => (int)$r['is_boosted'] === 1,
            'pin' => (int)$r['pin_order'],
            'rating' => (float)$r['rating_avg'], 'reviews' => (int)$r['review_count'],
            'approved' => (int)strtotime((string)($r['joined_at'] ?: $r['approved_at'] ?: '2000-01-01')), 'active' => (int)strtotime((string)($r['last_active_at'] ?: '2000-01-01')),
            'verified' => (int)$r['id_verified'] === 1 || (int)$r['photo_verified'] === 1,
        ];
    }
    return $out;
}

/** The strongest tier of one agent (from agents_candidates()), as [key, label, icon], or null for an agent in no tier. */
function agent_top_tier(array $flags): ?array
{
    foreach (agent_tiers() as $key => [$label, , $icon]) {
        if (!empty($flags[$key])) {
            return [$key, $label, $icon];
        }
    }
    return null;
}

/**
 * Read a showcase block's saved content into the settings the engine uses (clamped, with defaults).
 * Keys: count, tiers (list of keys), fill, sort, category, pinned (bool).
 */
function agents_block_options(array $c): array
{
    $tiers = [];
    foreach (array_keys(agent_tiers()) as $k) {
        if (!empty($c['tier_' . $k])) {
            $tiers[] = $k;
        }
    }
    $sort = (string)($c['sort'] ?? 'rotate');
    return [
        'count' => max(1, min(36, (int)($c['count'] ?? 12))),
        'tiers' => $tiers,
        'fill' => !array_key_exists('fill_others', $c) || !empty($c['fill_others']),
        'sort' => isset(agent_sorts()[$sort]) ? $sort : 'rotate',
        'category' => max(0, (int)($c['category'] ?? 0)),
    ];
}

/**
 * Ranked agent ids for a block: [id => weight]. Strongest first. An agent pinned by an admin always qualifies and comes first.
 * With no tier ticked every agent qualifies equally. When $o['fill'] is on, agents outside the ticked tiers follow the ones inside.
 */
function agents_rank(array $o, string $seed = ''): array
{
    $cand = agents_candidates((int)$o['category']);
    if (!$cand) {
        return [];
    }
    $tiers = agent_tiers();
    $hour = date('YmdH');
    $scored = [];
    foreach ($cand as $id => $f) {
        $w = 0;
        if ($f['pin'] > 0) {
            $w = 10000 + (100 - min(99, $f['pin'])); // pin 1 comes before pin 2
        } elseif (!$o['tiers']) {
            $w = 1;
        } else {
            foreach ($o['tiers'] as $k) {
                if (!empty($f[$k])) {
                    $w = max($w, $tiers[$k][1]);
                }
            }
        }
        if ($w === 0 && !$o['fill']) {
            continue;
        }
        $tie = match ($o['sort']) {
            'rating' => $f['rating'] * 1000 + min(999, $f['reviews']),
            'newest' => (float)$f['approved'],
            'active' => (float)$f['active'],
            default => (float)(crc32($seed . '|' . $hour . '|' . $id) % 1000000), // rotate (and nearest until the place is known)
        };
        $scored[$id] = [$w, $tie];
    }
    uasort($scored, fn($a, $b) => $b[0] <=> $a[0] ?: $b[1] <=> $a[1]);
    return array_map(fn($s) => $s[0], $scored);
}

/**
 * The agents a showcase block shows, as decorated member rows (hosts_decorate) with ['tier' => [key,label,icon]|null] added.
 * Cached per block for two minutes; the visitor's own distance and blocks are applied after the cache.
 */
function landing_agents(array $c, string $seed = ''): array
{
    if (!agents_public_ok()) {
        return [];
    }
    $o = agents_block_options($c);
    $key = 'landing_agents_' . md5(json_encode([$o, $seed]));
    $ranked = cache_remember($key, 120, fn() => agents_rank($o, $seed));
    if (!$ranked) {
        return [];
    }
    $ids = array_keys($ranked);
    if (!empty($GLOBALS['__landing_agent_dedupe'])) {
        $seenIds = (array)($GLOBALS['__landing_agent_seen_ids'] ?? []);
        $ids = array_values(array_filter($ids, fn($id) => !in_array((int)$id, $seenIds, true)));
    }
    $ids = array_slice($ids, 0, $o['count'] * 3);
    $found = members_search(['role' => 'host', 'only_ids' => $ids, 'order' => 'newest'], count($ids), 0)['rows'];
    $byId = [];
    foreach ($found as $m) {
        $byId[(int)$m['id']] = $m;
    }
    $ordered = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $ordered[] = $byId[$id];
        }
    }
    if ($o['sort'] === 'nearest' && viewer_coords() !== null) {
        usort($ordered, fn($a, $b) => ($ranked[$b['id']] ?? 0) <=> ($ranked[$a['id']] ?? 0)
            ?: ($a['distance_km'] ?? 1e9) <=> ($b['distance_km'] ?? 1e9));
    }
    // The Fun landing page tracks IDs as its tier sections render, so one profile appears only once.
    $ordered = array_slice($ordered, 0, $o['count']);
    $flags = agents_candidates($o['category']);
    $rows = hosts_decorate($ordered);
    foreach ($rows as &$m) {
        $m['tier'] = isset($flags[(int)$m['id']]) ? agent_top_tier($flags[(int)$m['id']]) : null;
        $m['pin'] = (int)($flags[(int)$m['id']]['pin'] ?? 0);
    }
    unset($m);
    $rows = array_values(array_filter($rows, fn($m) => !empty($m['host'])));
    if (!empty($GLOBALS['__landing_agent_dedupe'])) {
        foreach ($rows as $shown) {
            $GLOBALS['__landing_agent_seen_ids'][] = (int)$shown['id'];
        }
        $GLOBALS['__landing_agent_seen_ids'] = array_values(array_unique((array)$GLOBALS['__landing_agent_seen_ids']));
    }
    return $rows;
}

/** Where the round "Become an agent" button is shown: menu, strip, both or off. */
function agent_join_place(): string
{
    $p = strtolower(trim((string)setting('agent_join_place')));
    return in_array($p, ['menu', 'strip', 'both', 'off'], true) ? $p : 'menu';
}

/**
 * The round "Become an agent" button for the menus, or null when it does not belong there.
 * Hidden from people who are already agents or admins, and while Host accounts are switched off.
 */
function agent_join_cta(?array $viewer = null): ?array
{
    if (!in_array(agent_join_place(), ['menu', 'both'], true) || !setting('hosts_enabled')) {
        return null;
    }
    $viewer = $viewer ?? current_user();
    if ($viewer && in_array($viewer['role'], ['host', 'admin'], true)) {
        return null;
    }
    return [
        'href' => url('/register', ['type' => 'host']),
        'label' => 'Become ' . host_article(host_term()) . ' ' . host_term(),
        'sub' => 'Set up a free profile and get found',
    ];
}

/**
 * Story strip settings, clamped. Keys: on, pages (list), count, tiers (list), fill, join, ads, viewer, seconds, drift, promo.
 * Set in Admin > Agent showcase > Stories.
 */
function story_settings(): array
{
    $tiers = array_values(array_intersect(array_keys(agent_tiers()), array_map('trim', explode(',', (string)setting('agent_story_tiers')))));
    return [
        'on' => (bool)setting('agent_story_enabled'),
        'pages' => array_values(array_intersect(['landing', 'hosts', 'discover'], array_map('trim', explode(',', (string)setting('agent_story_pages'))))),
        'count' => max(1, min(30, (int)setting('agent_story_count'))),
        'tiers' => $tiers,
        'fill' => (bool)setting('agent_story_fill'),
        'join' => in_array(agent_join_place(), ['strip', 'both'], true),
        'ads' => (bool)setting('agent_story_ads'),
        'viewer' => (bool)setting('agent_story_viewer'),
        'seconds' => max(3, min(15, (int)setting('agent_story_seconds'))),
        'drift' => (bool)setting('agent_story_drift'),
        'promo' => (bool)setting('agent_story_promo'),
    ];
}

/** Agents for the round story strip: the ticked tiers first (VIP, then featured ...), then, when "fill" is on, everyone else. */
function landing_story_agents(?int $limit = null): array
{
    $st = story_settings();
    if (!$st['on'] || !agents_public_ok()) {
        return [];
    }
    $c = ['count' => $limit ?? $st['count'], 'fill_others' => $st['fill'], 'sort' => 'rotate'];
    foreach (array_keys(agent_tiers()) as $k) {
        $c['tier_' . $k] = in_array($k, $st['tiers'], true);
    }
    return landing_agents($c, 'story');
}

/** Active sponsored story adverts for a page (Admin > Adverts, format "Story"). Honours the site-wide adverts switch and ad-free plans. */
function story_adverts(string $page, int $limit = 6): array
{
    $u = current_user();
    $admin = $u && $u['role'] === 'admin'; // admins always see them, so the admin preview matches what visitors get
    if (!setting('ads_enabled') || (can('no_ads') && !$admin)) {
        return [];
    }
    $slot = ['landing' => 'landing', 'hosts' => 'hosts', 'discover' => 'discover'][$page] ?? 'landing';
    return rows("SELECT id, advertiser_name, title, description, image_path, link_url, cta_text FROM advertisements
                 WHERE ad_type = 'story' AND status = 'active' AND starts_at <= NOW() AND expires_at > NOW() AND (placement = :p OR placement = 'global')
                   AND (max_views IS NULL OR views < max_views) AND (max_clicks IS NULL OR clicks < max_clicks)
                 ORDER BY amount_paid DESC, id DESC LIMIT " . max(1, min(12, $limit)), ['p' => $slot]);
}

/**
 * Everything the story strip and viewer need, in display order: a "Become an agent" bubble, VIP/featured agents, sponsored promotions,
 * then the other agents. Each item: kind (join|agent|ad), key (for "seen" memory), label, img, blur, tier, href, and 'v' (what the viewer shows).
 */
function story_items(string $page = 'landing'): array
{
    $st = story_settings();
    if (!$st['on'] || !in_array($page, $st['pages'], true)) {
        return [];
    }
    $items = [];
    $viewer = current_user();
    if ($st['join'] && !($viewer && in_array($viewer['role'], ['host', 'admin'], true))) {
        $items[] = ['kind' => 'join', 'key' => 'join', 'label' => 'Be an agent', 'img' => '', 'blur' => false, 'tier' => null, 'href' => url('/register', ['type' => 'host']),
            'v' => ['k' => 'join', 'name' => 'Become an agent', 'headline' => 'Get seen by people looking for company', 'bio' => 'Create a free profile, choose your services and set your own rates. VIP and featured agents are shown first on the home page.', 'cta' => 'Start your profile', 'href' => url('/register', ['type' => 'host'])]];
    }
    $agents = landing_story_agents();
    $promo = [];
    if ($st['promo'] && $agents) {
        foreach (rows('SELECT user_id, promo_text FROM host_profile_ext WHERE user_id IN (:ids) AND promo_text IS NOT NULL AND promo_text <> \'\'', ['ids' => array_map('intval', array_column($agents, 'id'))]) as $r) {
            $promo[(int)$r['user_id']] = (string)$r['promo_text'];
        }
    }
    $digits = fn(string $v) => phone_normalize($v) ?: (preg_replace('/\D/', '', $v) ?? '');
    $showRate = hosts_listing_prices() && (setting('host_show_rates') || is_logged_in());
    $agentItems = [];
    foreach ($agents as $m) {
        $h = $m['host'];
        $id = (int)$m['id'];
        $first = (string)($h['first_name'] ?: $m['name']);
        $place = trim((string)($m['town'] ?: $m['county']));
        $ct = agent_card_contacts($id);
        $price = ($showRate && empty($h['prices_hidden']) && $h['from_rate'] !== null) ? money($h['from_rate']) . '/hr' : '';
        $blur = photo_is_blurred($m['photo'] ?? null, 'hosts');
        // an agent the admin pinned but who has no paid kind still gets a highlighted ring
        $tier = $m['tier'] ?? ((int)($m['pin'] ?? 0) > 0 ? ['pin', 'Top pick', 'pin'] : null);
        $agentItems[] = ['kind' => 'agent', 'key' => 'a' . $id, 'owner_id' => $id, 'label' => $first, 'photo' => $m['photo'] ?? null, 'img' => '', 'blur' => $blur, 'tier' => $tier, 'href' => url('/hosts/' . $id),
            'paid' => (int)($m['pin'] ?? 0) > 0 || (!empty($m['tier']) && in_array($m['tier'][0], ['vip', 'featured'], true)),
            'v' => [
                'k' => 'agent', 'owner_id' => $id, 'name' => $first . ($m['age'] ? ', ' . (int)$m['age'] : ''), 'place' => $place, 'tier' => $tier[1] ?? '', 'tier_k' => $tier[0] ?? '',
                'img' => photo_url($m['photo'] ?? null, 'large', 'hosts'), 'blur' => $blur, 'verified' => !empty($h['verified']),
                'headline' => $promo[$id] ?? ($h['tagline'] !== '' ? $h['tagline'] : ($h['category'] ?? '')), 'promo' => isset($promo[$id]),
                'bio' => agent_bio($m, isset($promo[$id]) ? null : ''), 'services' => array_slice((array)$h['services'], 0, 3),
                'price' => $price, 'rating' => $h['reviews'] > 0 ? number_format((float)$h['rating'], 1) . ' (' . (int)$h['reviews'] . ')' : '',
                'href' => url('/hosts/' . $id), 'cta' => 'View profile',
                'tel' => !empty($ct['phone']) ? 'tel:+' . $digits((string)$ct['phone']['value']) : '',
                'wa' => !empty($ct['whatsapp']) ? 'https://wa.me/' . $digits((string)$ct['whatsapp']['value']) : '',
            ]];
    }
    $mediaStories = function_exists('agent_stories_for_users') ? agent_stories_for_users(array_column($agentItems, 'owner_id')) : [];
    foreach ($agentItems as &$a) {
        $profilePhoto = photo_url($a['photo'], 'medium', 'hosts');
        $ownerId = (int)($a['owner_id'] ?? 0);
        $slides = $mediaStories[$ownerId] ?? [];
        if ($slides) {
            $a['v']['slides'] = $slides;
            // Use the newest actual story's thumbnail for the bubble; fall back to the profile photo for video.
            $a['img'] = !empty($slides[0]['thumb']) ? $slides[0]['thumb'] : $profilePhoto;
        } else {
            $a['img'] = $profilePhoto;
        }
        unset($a['photo']);
    }
    unset($a);
    $adItems = [];
    if ($st['ads']) {
        foreach (story_adverts($page) as $ad) {
            $img = $ad['image_path'] ? upload_url((string)$ad['image_path']) : '';
            $adItems[] = ['kind' => 'ad', 'key' => 'd' . (int)$ad['id'], 'label' => mb_substr((string)$ad['advertiser_name'], 0, 16), 'img' => $img, 'blur' => false,
                'tier' => ['ad', (string)(setting('ads_label') ?: 'Sponsored'), 'megaphone'], 'href' => url('/ad/' . (int)$ad['id']), 'ad_id' => (int)$ad['id'],
                'v' => ['k' => 'ad', 'id' => (int)$ad['id'], 'name' => (string)$ad['advertiser_name'], 'tier' => (string)(setting('ads_label') ?: 'Sponsored'), 'tier_k' => 'ad', 'img' => $img,
                    'headline' => (string)$ad['title'], 'bio' => (string)$ad['description'], 'cta' => (string)$ad['cta_text'], 'href' => url('/ad/' . (int)$ad['id'])]];
        }
    }
    // paid agents first, then the sponsored promotions, then everyone else
    $paid = array_values(array_filter($agentItems, fn($a) => $a['paid']));
    $rest = array_values(array_filter($agentItems, fn($a) => !$a['paid']));
    return array_merge($items, $paid, $adItems, $rest);
}

/** The strongest tier of one agent as [key, label, icon], or null (not approved, or in no tier). */
function agent_tier_of(int $hostId): ?array
{
    $f = agents_candidates(0, 1, $hostId)[$hostId] ?? null;
    return $f ? agent_top_tier($f) : null;
}

/**
 * Agents to suggest under a profile: the same category first, then anyone else, never the agent being viewed.
 * @return array decorated member rows (see landing_agents)
 */
function agents_similar(int $hostId, int $categoryId, int $limit = 8): array
{
    $out = [];
    $seen = [$hostId => true];
    foreach ($categoryId > 0 ? [$categoryId, 0] : [0] as $cat) {
        foreach (landing_agents(['count' => $limit + 1, 'fill_others' => true, 'sort' => 'rotate', 'category' => $cat], 'similar' . $hostId) as $m) {
            if (!isset($seen[(int)$m['id']]) && count($out) < $limit) {
                $seen[(int)$m['id']] = true;
                $out[] = $m;
            }
        }
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/** "Meet Amina from Westlands. Her own text." from the admin's template. $m is a decorated member row or any array with name/town/about. */
function agent_bio(array $m, ?string $about = null): string
{
    $name = trim((string)($m['host']['first_name'] ?? $m['first_name'] ?? $m['name'] ?? ''));
    $age = (int)($m['age'] ?? 0);
    $genderKey = strtolower(trim((string)($m['gender'] ?? '')));
    $gender = ['female' => 'female', 'male' => 'male', 'non-binary' => 'non-binary'][$genderKey] ?? '';
    $place = trim((string)($m['town'] ?? ''));
    $county = trim((string)($m['county'] ?? ''));
    $location = trim($place . ($place !== '' && $county !== '' && stripos($place, $county) === false ? ', ' : '') . ($place !== '' && stripos($place, $county) !== false ? '' : $county));
    if ($location === '') { $location = 'Kenya'; }
    $parts = ['Meet ' . ($name !== '' ? $name : 'this profile')];
    if ($age > 0 && in_array($gender, ['female', 'male'], true)) { $parts[] = ', a ' . $age . ' year old ' . $gender; }
    elseif ($age > 0) { $parts[] = ', ' . $age . ' years old'; }
    elseif (in_array($gender, ['female', 'male'], true)) { $parts[] = ', a ' . $gender; }
    elseif ($gender === 'non-binary') { $parts[] = ', a non-binary person'; }
    return implode('', $parts) . ' from ' . $location . '.';
}

/** Contact buttons a viewer may see for one agent: [] when none. Items are [field => ['label','value']]. */
function agent_card_contacts(int $hostId): array
{
    static $memo = [];
    if (!isset($memo[$hostId])) {
        $ct = hosts_contacts_for($hostId);
        $memo[$hostId] = $ct['state'] === 'visible' ? (array)$ct['items'] : [];
    }
    return $memo[$hostId];
}
