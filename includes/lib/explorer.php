<?php
/**
 * Explorer module: option lists, profile completion, filters, deck/feed cards, relation state
 * and the small helpers every Explorer page shares. Actions that change data (swipe, block,
 * report, visits, verification, account) live in explorer_actions.php.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Option lists
// ---------------------------------------------------------------------------

/** What a member is looking for. Keys are stored in user_profiles.intent. */
function explorer_intents(): array
{
    return [
        'dating' => ['label' => 'Dating', 'icon' => 'heart', 'hint' => 'Meet someone and see where it goes'],
        'relationship' => ['label' => 'A relationship', 'icon' => 'heart-handshake', 'hint' => 'Looking for something lasting'],
        'friendship' => ['label' => 'Friendship', 'icon' => 'users', 'hint' => 'New friends who live nearby'],
        'casual' => ['label' => 'Fun and casual', 'icon' => 'party-popper', 'hint' => 'Keep it light and easy'],
        'networking' => ['label' => 'Networking', 'icon' => 'briefcase', 'hint' => 'Career and business contacts'],
        'not_sure' => ['label' => 'Not sure yet', 'icon' => 'compass', 'hint' => 'Open to whatever feels right'],
    ];
}

function explorer_intent_label(?string $key): string
{
    $i = explorer_intents();
    return $key !== null && isset($i[$key]) ? $i[$key]['label'] : '';
}

/** Value => label lists for select fields. */
function explorer_options(string $key): array
{
    static $o = null;
    if ($o === null) {
        $o = [
            'gender' => ['male' => 'Man', 'female' => 'Woman', 'non-binary' => 'Non-binary', 'other' => 'Other'],
            'show' => ['female' => 'Women', 'male' => 'Men', 'everyone' => 'Everyone'],
            'body_type' => ['slim' => 'Slim', 'athletic' => 'Athletic', 'average' => 'Average', 'curvy' => 'Curvy', 'plus-size' => 'Plus size'],
            'smoking' => ['never' => 'Never', 'occasionally' => 'Occasionally', 'regularly' => 'Regularly'],
            'drinking' => ['never' => 'Never', 'socially' => 'Socially', 'regularly' => 'Regularly'],
            'relationship_status' => ['single' => 'Single', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'separated' => 'Separated', 'complicated' => 'It is complicated'],
            'children' => ['none' => 'No children', 'have_at_home' => 'Children at home', 'have_away' => 'Children not at home'],
            'want_children' => ['yes' => 'Want children', 'no' => 'Do not want children', 'maybe' => 'Open to children'],
            'education' => array_combine(
                ['High school', 'Diploma or certificate', 'Bachelor’s degree', 'Master’s degree', 'Doctorate', 'Trade or vocational'],
                ['High school', 'Diploma or certificate', 'Bachelor’s degree', 'Master’s degree', 'Doctorate', 'Trade or vocational']
            ),
            'active' => ['1' => 'Today', '3' => 'Last 3 days', '7' => 'This week', '30' => 'This month'],
            'sort' => ['relevance' => 'Best match', 'distance' => 'Closest', 'active' => 'Recently active', 'newest' => 'Newest'],
        ];
    }
    return $o[$key] ?? [];
}

/** Which onboarding steps exist, in order. Hidden steps (admin setting) are left out of the wizard and the editor. */
function explorer_steps(): array
{
    $all = [
        'basics' => ['label' => 'The basics', 'icon' => 'user'],
        'location' => ['label' => 'Where you are', 'icon' => 'map-pin'],
        'photos' => ['label' => 'Photos', 'icon' => 'camera'],
        'about' => ['label' => 'About you', 'icon' => 'pen-line'],
        'interests' => ['label' => 'Interests', 'icon' => 'sparkles'],
        'looking' => ['label' => 'What you want', 'icon' => 'heart'],
    ];
    $hidden = (array)setting('onboarding_disabled');
    $out = [];
    foreach ($all as $k => $v) {
        if (!in_array($k, $hidden, true)) {
            $out[$k] = $v;
        }
    }
    return $out;
}

function explorer_step_enabled(string $step): bool
{
    return !in_array($step, (array)setting('onboarding_disabled'), true);
}

function explorer_step_required(string $step): bool
{
    return in_array($step, (array)setting('onboarding_required'), true) || $step === 'basics';
}

/** Counties with their towns, for the pickers. Cached briefly. */
function explorer_location_tree(): array
{
    return cache_remember('xp_loc_tree', 300, function () {
        $counties = array_map(fn($c) => ['id' => (int)$c['id'], 'name' => $c['name']], locations_counties());
        $towns = [];
        foreach (locations_towns() as $t) {
            $towns[(int)$t['parent_id']][] = ['id' => (int)$t['id'], 'name' => $t['name']];
        }
        return ['counties' => $counties, 'towns' => $towns];
    });
}

/** URL-safe class name of an interest category: "Food & drink" -> "food-drink". Drives the tile colour in CSS. */
function interest_cat_slug(string $category): string
{
    $s = trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower($category)), '-');
    return $s !== '' ? $s : 'general';
}

/**
 * The picture beside an interest: its icon in a coloured tile (colour comes from the category), else its emoji, else a
 * neutral sparkle. $i needs icon, emoji and cat (or category). Decorative, so hidden from screen readers.
 */
function interest_icon_html(array $i, string $class = ''): string
{
    $icon = (string)($i['icon'] ?? '');
    $cat = interest_cat_slug((string)($i['cat'] ?? $i['category'] ?? ''));
    $cls = 'int-ic' . ($class !== '' ? ' ' . $class : '');
    if ($icon !== '' && preg_match('/^[a-z0-9-]+$/', $icon)) {
        return '<span class="' . e($cls) . '" data-cat="' . e($cat) . '" aria-hidden="true">' . icon($icon, 'int-svg') . '</span>';
    }
    $emoji = (string)($i['emoji'] ?? '');
    if ($emoji !== '') {
        return '<span class="' . e($cls) . ' is-emoji" data-cat="' . e($cat) . '" aria-hidden="true">' . e($emoji) . '</span>';
    }
    return '<span class="' . e($cls) . '" data-cat="' . e($cat) . '" aria-hidden="true">' . icon('sparkles', 'int-svg') . '</span>';
}

/** Active interests grouped by category: [category => [[id,name,emoji,icon], ...]] */
function explorer_interests_catalog(): array
{
    return cache_remember('xp_interests', 120, function () {
        $out = [];
        foreach (rows('SELECT id, name, emoji, icon, category FROM interests WHERE is_active = 1 ORDER BY category, sort_order, name') as $r) {
            $out[$r['category']][] = ['id' => (int)$r['id'], 'name' => $r['name'], 'emoji' => (string)$r['emoji'], 'icon' => (string)$r['icon'], 'cat' => (string)$r['category']];
        }
        return $out;
    });
}

function explorer_interest_ids(int $userId): array
{
    return array_map('intval', col('SELECT interest_id FROM user_interests WHERE user_id = :u', ['u' => $userId]));
}

/** Replace a member's interests (validated against the active catalogue). Returns the number saved. */
function explorer_save_interests(int $userId, array $ids): int
{
    $max = max(1, (int)setting('interests_max'));
    $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, $max);
    $valid = $ids ? array_map('intval', col('SELECT id FROM interests WHERE is_active = 1 AND id IN (:ids)', ['ids' => $ids])) : [];
    db_transaction(function () use ($userId, $valid) {
        q('DELETE FROM user_interests WHERE user_id = :u', ['u' => $userId]);
        foreach ($valid as $iid) {
            q('INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (:u, :i)', ['u' => $userId, 'i' => $iid]);
        }
    });
    profile_recalculate_completion($userId);
    return count($valid);
}

// ---------------------------------------------------------------------------
// Profile completion
// ---------------------------------------------------------------------------

/**
 * The parts of a profile that count towards completion.
 * Each: key, label, weight (admin setting), done (0..1), url, hint. Parts the admin hides are left out.
 */
function profile_completion_parts(int $userId): array
{
    $p = row('SELECT * FROM user_profiles WHERE user_id = :u', ['u' => $userId]);
    if (!$p) {
        return [];
    }
    $photos = rows("SELECT is_primary, moderation_status FROM user_photos WHERE user_id = :u AND moderation_status <> 'rejected'", ['u' => $userId]);
    $hasMain = false;
    foreach ($photos as $ph) {
        if ((int)$ph['is_primary'] === 1) {
            $hasMain = true;
        }
    }
    $extra = max(0, count($photos) - 1);
    $bioLen = mb_strlen(trim((string)$p['bio']));
    $interests = (int)val('SELECT COUNT(*) FROM user_interests WHERE user_id = :u', ['u' => $userId]);
    $needInterests = max(1, (int)setting('interests_min'));
    $hasLocation = (int)val('SELECT COUNT(*) FROM user_locations WHERE user_id = :u', ['u' => $userId]) > 0;
    $looking = (trim((string)$p['intent']) !== '' ? 0.6 : 0) + (trim((string)$p['looking_for']) !== '' ? 0.4 : 0);

    $parts = [
        ['photo', 'Add a main photo', (float)setting('cw_photo'), $hasMain ? 1.0 : 0.0, '/profile/photos', 'A clear photo of your face gets the most attention.'],
        ['photos_extra', 'Add more photos', (float)setting('cw_photos_extra'), min(1.0, $extra / 3), '/profile/photos', 'Three or more extra photos show more of your life.'],
    ];
    if (explorer_step_enabled('about')) {
        $parts[] = ['bio', 'Write a short bio', (float)setting('cw_bio'), $bioLen >= 60 ? 1.0 : ($bioLen >= 15 ? 0.6 : ($bioLen > 0 ? 0.3 : 0.0)), '/profile/edit#about', 'Two or three sentences about you give people something to say hello to.'];
        if (setting('onboarding_show_height')) {
            $parts[] = ['height', 'Add your height', (float)setting('cw_height'), (int)$p['height_cm'] > 0 ? 1.0 : 0.0, '/profile/edit#about', 'Some people filter by height.'];
        }
        if (setting('onboarding_show_education')) {
            $parts[] = ['education', 'Add your education', (float)setting('cw_education'), trim((string)$p['education']) !== '' ? 1.0 : 0.0, '/profile/edit#about', 'Shown on your profile under About.'];
        }
        if (setting('onboarding_show_occupation')) {
            $parts[] = ['work', 'Add what you do for work', (float)setting('cw_work'), trim((string)$p['occupation']) !== '' ? 1.0 : 0.0, '/profile/edit#about', 'A job title is an easy conversation starter.'];
        }
    }
    if (explorer_step_enabled('interests')) {
        $parts[] = ['interests', 'Pick your interests', (float)setting('cw_interests'), min(1.0, $interests / $needInterests), '/profile/edit#interests', 'Shared interests are the strongest sign of a good match.'];
    }
    if (explorer_step_enabled('location')) {
        $parts[] = ['location', 'Set your location', (float)setting('cw_location'), $hasLocation ? 1.0 : 0.0, '/profile/edit#location', 'We use it to show people nearby. We never show your exact spot.'];
    }
    if (explorer_step_enabled('looking')) {
        $parts[] = ['looking', 'Say what you are looking for', (float)setting('cw_looking'), $looking, '/profile/edit#looking', 'Helps us match you with people who want the same thing.'];
    }
    if (setting('verification_enabled')) {
        $parts[] = ['verified', 'Get verified', (float)setting('cw_verified'), (int)$p['is_verified'] === 1 ? 1.0 : 0.0, '/verification', 'The blue badge shows you are a real person.'];
    }
    $out = [];
    foreach ($parts as [$k, $label, $w, $done, $url, $hint]) {
        if ($w > 0) {
            $out[] = ['key' => $k, 'label' => $label, 'weight' => $w, 'done' => $done, 'url' => $url, 'hint' => $hint];
        }
    }
    return $out;
}

/** Documented weights: see profile_completion_parts(). Returns 0-100 and stores it on the profile. */
function profile_recalculate_completion(int $userId): int
{
    $parts = profile_completion_parts($userId);
    $total = 0.0;
    $got = 0.0;
    foreach ($parts as $p) {
        $total += $p['weight'];
        $got += $p['weight'] * $p['done'];
    }
    $pct = $total > 0 ? (int)round($got / $total * 100) : 0;
    $pct = max(0, min(100, $pct));
    q('UPDATE user_profiles SET profile_completion = :c WHERE user_id = :u', ['c' => $pct, 'u' => $userId]);
    return $pct;
}

/** A stored phone number (2547...) the way a member types it (0712 345 678). Anything else is returned as it is. */
function explorer_phone_display(string $phone): string
{
    $prefix = preg_replace('/\D/', '', (string)setting('phone_prefix')) ?: '254';
    $d = preg_replace('/\D/', '', $phone) ?? '';
    if ($d !== '' && $d === trim($phone) && strpos($d, $prefix) === 0 && strlen($d) === strlen($prefix) + 9) {
        $local = '0' . substr($d, strlen($prefix));
        return substr($local, 0, 4) . ' ' . substr($local, 4, 3) . ' ' . substr($local, 7);
    }
    return $phone;
}

/** The next things to do, biggest gain first: [['label','points','url','hint'], ...] */
function profile_completion_steps(int $userId, int $limit = 4): array
{
    $parts = profile_completion_parts($userId);
    $total = array_sum(array_column($parts, 'weight'));
    if ($total <= 0) {
        return [];
    }
    $todo = [];
    foreach ($parts as $p) {
        if ($p['done'] < 1) {
            $todo[] = ['label' => $p['label'], 'points' => (int)round($p['weight'] * (1 - $p['done']) / $total * 100), 'url' => $p['url'], 'hint' => $p['hint'], 'key' => $p['key']];
        }
    }
    usort($todo, fn($a, $b) => $b['points'] <=> $a['points']);
    return array_slice(array_values(array_filter($todo, fn($t) => $t['points'] > 0)), 0, $limit);
}

// ---------------------------------------------------------------------------
// Plan helpers
// ---------------------------------------------------------------------------

/** Photos a member may keep: their own limit if an admin set one, else what their plan allows, never above the site ceiling. */
function explorer_photo_limit(?int $userId = null): int
{
    $userId = $userId ?? auth_id();
    $cap = max(1, (int)setting('photo_cap_all'));
    $own = $userId ? val('SELECT photo_limit_override FROM user_profiles WHERE user_id = :u', ['u' => $userId]) : null;
    if ($own !== null && (int)$own > 0) {
        return min($cap, (int)$own);
    }
    $n = (int)feature('max_photos', 6, $userId);
    return min($cap, $n < 0 ? $cap : max(1, $n));
}

function explorer_can_vip_photos(): bool
{
    return can('vip_photos') || (current_user()['tier'] ?? 'free') === 'vip';
}

/** Is this filter reserved for paying members (admin list) and is the viewer one of the free members? */
function explorer_filter_locked(string $key): bool
{
    return in_array($key, (array)setting('premium_filters'), true) && !can('advanced_filters');
}

/** Blurred, nameless stand-in for a photo the viewer is not allowed to see sharp. The sharp image is never sent. */
function explorer_locked_photo_tag(?array $photo, string $class = '', string $alt = ''): string
{
    $url = $photo && !empty($photo['blur_path']) ? upload_url($photo['blur_path']) : photo_placeholder_url();
    return '<img src="' . e($url) . '" alt="' . e($alt) . '" class="' . e(trim($class . ' ch-blurred ch-blur-' . (string)setting('blur_level'))) . '" loading="lazy" decoding="async">';
}

// ---------------------------------------------------------------------------
// Discovery preferences and filters
// ---------------------------------------------------------------------------

function explorer_prefs(int $userId): array
{
    $d = row('SELECT * FROM discovery_preferences WHERE user_id = :u', ['u' => $userId]);
    if (!$d) {
        q('INSERT IGNORE INTO discovery_preferences (user_id, age_min, age_max, distance_km) VALUES (:u, 18, 45, :d)', ['u' => $userId, 'd' => (int)setting('radius_default')]);
        $d = row('SELECT * FROM discovery_preferences WHERE user_id = :u', ['u' => $userId]) ?: [];
    }
    return $d + ['age_min' => 18, 'age_max' => 45, 'distance_km' => (int)setting('radius_default'), 'show_verified_only' => 0, 'show_photos_only' => 1, 'hide_swiped' => 1, 'preferred_view' => null];
}

/** Name of the cookie that remembers a chosen view on this device (a new name, so views saved by older versions are not applied). */
const EXPLORER_VIEW_COOKIE = 'chx_v';

/**
 * 'swipe' | 'grid' | 'list' for this member.
 *
 * Swipe is the site's default (setting discover_default_view). A view only replaces it once the member has chosen one
 * themselves: a saved preference counts only when view_set_at is filled in, and the device cookie is the fallback.
 */
function explorer_view_mode(?int $userId = null): string
{
    $valid = ['swipe', 'grid', 'list'];
    $default = (string)setting('discover_default_view');
    if (!in_array($default, $valid, true)) {
        $default = 'swipe';
    }
    if (!setting('discover_allow_view_switch')) {
        return $default;
    }
    $want = (string)($_GET['view'] ?? '');
    if (in_array($want, $valid, true)) {
        return $want;
    }
    $userId = $userId ?? auth_id();
    $pref = $userId ? (string)val('SELECT preferred_view FROM discovery_preferences WHERE user_id = :u AND view_set_at IS NOT NULL', ['u' => $userId], '') : '';
    if (in_array($pref, $valid, true)) {
        return $pref;
    }
    $c = (string)($_COOKIE[EXPLORER_VIEW_COOKIE] ?? '');
    return in_array($c, $valid, true) ? $c : $default;
}

/** Remember the view a member chose, on their profile and on this device. */
function explorer_view_remember(int $userId, string $view): void
{
    if (!in_array($view, ['swipe', 'grid', 'list'], true)) {
        return;
    }
    explorer_prefs($userId);
    update('discovery_preferences', ['preferred_view' => $view, 'view_set_at' => date('Y-m-d H:i:s')], ['user_id' => $userId]);
    explorer_cookie(EXPLORER_VIEW_COOKIE, $view);
}

/**
 * Turn request parameters into members_search filters.
 * Returns ['search' => filters, 'values' => form values, 'chips' => [[key,label]], 'count' => active extra filters, 'locked' => [keys ignored],
 * 'prefs' => saved preferences, 'recycle' => bring back profiles the member passed on once everyone else has been seen].
 */
function explorer_filters_from(array $src, int $userId): array
{
    $prefs = explorer_prefs($userId);
    $me = row('SELECT interested_in, gender FROM user_profiles WHERE user_id = :u', ['u' => $userId]) ?: ['interested_in' => 'everyone', 'gender' => null];
    $minAge = max(18, (int)setting('min_age'));
    $maxAge = max($minAge + 1, (int)setting('max_age'));
    $radiusMax = max(5, (int)setting('radius_max'));
    $str = fn($k, $n = 60) => is_string($src[$k] ?? null) ? mb_substr(trim((string)$src[$k]), 0, $n) : '';
    $num = fn($k) => isset($src[$k]) && is_scalar($src[$k]) && is_numeric($src[$k]) ? (int)$src[$k] : null;
    $flag = fn($k) => in_array($src[$k] ?? '', ['1', 1, 'on', 'true', true], true);

    $v = [
        'age_min' => min($maxAge, max($minAge, $num('age_min') ?? (int)$prefs['age_min'])),
        'age_max' => min($maxAge, max($minAge, $num('age_max') ?? (int)$prefs['age_max'])),
        'distance' => min($radiusMax, max(1, $num('distance') ?? (int)$prefs['distance_km'])),
        'show' => in_array($src['show'] ?? '', ['male', 'female', 'everyone'], true) ? (string)$src['show'] : (string)$me['interested_in'],
        'online' => $flag('online'),
        'verified' => isset($src['verified']) ? $flag('verified') : (bool)$prefs['show_verified_only'],
        'county' => max(0, $num('county') ?? 0),
        'town' => max(0, $num('town') ?? 0),
        'intent' => array_key_exists($str('intent'), explorer_intents()) ? $str('intent') : '',
        'interests' => array_slice(int_list(is_array($src['interests'] ?? null) ? $src['interests'] : []), 0, 10),
        'height_min' => $num('height_min') && $num('height_min') >= 120 && $num('height_min') <= 230 ? $num('height_min') : 0,
        'height_max' => $num('height_max') && $num('height_max') >= 120 && $num('height_max') <= 230 ? $num('height_max') : 0,
        'education' => array_key_exists($str('education', 80), explorer_options('education')) ? $str('education', 80) : '',
        'body_type' => array_key_exists($str('body_type'), explorer_options('body_type')) ? $str('body_type') : '',
        'has_bio' => $flag('has_bio'),
        'active' => array_key_exists($str('active', 3), explorer_options('active')) ? (int)$str('active', 3) : 0,
        'q' => $str('q', 60),
    ];
    if ($v['age_min'] > $v['age_max']) {
        [$v['age_min'], $v['age_max']] = [$v['age_max'], $v['age_min']];
    }
    // Gates: filters reserved for paying members are ignored (and reported) for everyone else.
    $locked = [];
    $gates = ['height' => ['height_min', 'height_max'], 'education' => ['education'], 'body_type' => ['body_type'], 'active_recently' => ['active'], 'has_bio' => ['has_bio']];
    foreach ($gates as $gate => $fields) {
        if (explorer_filter_locked($gate)) {
            foreach ($fields as $f) {
                if (!empty($v[$f])) {
                    $locked[] = $gate;
                }
                $v[$f] = is_bool($v[$f]) ? false : (is_int($v[$f]) ? 0 : '');
            }
        }
    }
    $s = [
        'age_min' => $v['age_min'], 'age_max' => $v['age_max'], 'distance_km' => $v['distance'],
        'hide_swiped' => true, 'has_photo' => (bool)$prefs['show_photos_only'],
    ];
    if (in_array($v['show'], ['male', 'female'], true)) {
        $s['gender'] = [$v['show']];
    }
    foreach (['online' => 'online', 'verified' => 'verified', 'has_bio' => 'has_bio'] as $from => $to) {
        if ($v[$from]) {
            $s[$to] = true;
        }
    }
    if ($v['town']) {
        $s['location_id'] = $v['town'];
    } elseif ($v['county']) {
        $s['county_id'] = $v['county'];
    }
    if ($v['intent'] !== '') {
        $s['intent'] = $v['intent'];
    }
    if ($v['interests']) {
        $s['interest_ids'] = $v['interests'];
    }
    foreach (['height_min', 'height_max', 'education', 'body_type'] as $k) {
        if ($v[$k]) {
            $s[$k] = $v[$k];
        }
    }
    if ($v['active']) {
        $s['active_days'] = $v['active'];
    }
    if ($v['q'] !== '') {
        $s['q'] = $v['q'];
    }

    // Chips + a count of what differs from the member's saved defaults.
    $chips = [];
    $count = 0;
    if ($v['distance'] !== (int)$prefs['distance_km']) {
        $count++;
    }
    if ($v['age_min'] !== (int)$prefs['age_min'] || $v['age_max'] !== (int)$prefs['age_max']) {
        $count++;
    }
    if ($v['show'] !== (string)$me['interested_in']) {
        $count++;
    }
    if ($v['verified'] !== (bool)$prefs['show_verified_only']) {
        $count++;
    }
    $chips[] = ['key' => 'distance', 'label' => 'Within ' . $v['distance'] . ' km'];
    $chips[] = ['key' => 'age', 'label' => 'Ages ' . $v['age_min'] . ' to ' . $v['age_max']];
    if ($v['online']) {
        $chips[] = ['key' => 'online', 'label' => 'Online now'];
        $count++;
    }
    if ($v['verified']) {
        $chips[] = ['key' => 'verified', 'label' => 'Verified only'];
    }
    if ($v['county'] || $v['town']) {
        $loc = location_by_id($v['town'] ?: $v['county']);
        if ($loc) {
            $chips[] = ['key' => 'place', 'label' => $loc['name']];
            $count++;
        }
    }
    if ($v['intent'] !== '') {
        $chips[] = ['key' => 'intent', 'label' => explorer_intent_label($v['intent'])];
        $count++;
    }
    if ($v['interests']) {
        $chips[] = ['key' => 'interests', 'label' => plural(count($v['interests']), 'interest')];
        $count++;
    }
    foreach (['height_min' => 'height_min', 'education' => 'education', 'body_type' => 'body_type', 'has_bio' => 'has_bio', 'active' => 'active'] as $k => $_) {
        if ($v[$k]) {
            $count++;
        }
    }
    if ($v['height_min'] || $v['height_max']) {
        $chips[] = ['key' => 'height', 'label' => 'Height ' . ($v['height_min'] ?: '120') . ' to ' . ($v['height_max'] ?: '230') . ' cm'];
    }
    if ($v['education'] !== '') {
        $chips[] = ['key' => 'education', 'label' => $v['education']];
    }
    if ($v['body_type'] !== '') {
        $chips[] = ['key' => 'body_type', 'label' => explorer_options('body_type')[$v['body_type']] ?? $v['body_type']];
    }
    if ($v['has_bio']) {
        $chips[] = ['key' => 'has_bio', 'label' => 'Has a bio'];
    }
    if ($v['active']) {
        $chips[] = ['key' => 'active', 'label' => 'Active ' . strtolower(explorer_options('active')[(string)$v['active']] ?? '')];
    }
    return ['search' => $s, 'values' => $v, 'chips' => $chips, 'count' => $count, 'locked' => array_values(array_unique($locked)), 'prefs' => $prefs, 'recycle' => !(int)$prefs['hide_swiped']];
}

// ---------------------------------------------------------------------------
// Relations between the viewer and a set of members
// ---------------------------------------------------------------------------

/** [id => ['mine' => like|nope|superlike|null, 'theirs' => like|superlike|null, 'fav' => bool, 'matched' => bool]] */
function explorer_relations(int $me, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $out = [];
    foreach ($ids as $id) {
        $out[$id] = ['mine' => null, 'theirs' => null, 'fav' => false, 'matched' => false];
    }
    if (!$ids) {
        return $out;
    }
    foreach (rows('SELECT swiped_id AS id, direction FROM swipes WHERE swiper_id = :me AND swiped_id IN (:ids)', ['me' => $me, 'ids' => $ids]) as $r) {
        $out[(int)$r['id']]['mine'] = $r['direction'];
    }
    foreach (rows("SELECT swiper_id AS id, direction FROM swipes WHERE swiped_id = :me AND swiper_id IN (:ids) AND direction IN ('like','superlike')", ['me' => $me, 'ids' => $ids]) as $r) {
        $out[(int)$r['id']]['theirs'] = $r['direction'];
    }
    foreach (col('SELECT target_id FROM favorites WHERE user_id = :me AND target_id IN (:ids)', ['me' => $me, 'ids' => $ids]) as $id) {
        $out[(int)$id]['fav'] = true;
    }
    foreach (rows('SELECT IF(user1_id = :me, user2_id, user1_id) AS id FROM matches WHERE is_active = 1 AND ((user1_id = :me AND user2_id IN (:ids)) OR (user2_id = :me AND user1_id IN (:ids)))', ['me' => $me, 'ids' => $ids]) as $r) {
        $out[(int)$r['id']]['matched'] = true;
    }
    return $out;
}

/** Approved photos for several members at once: [userId => [photo, ...]] (at most $per each). */
function explorer_photos_for(array $userIds, int $per = 5): array
{
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    if (!$userIds) {
        return [];
    }
    $out = [];
    foreach (rows("SELECT id, user_id, large_path, medium_path, thumb_path, blur_path, is_primary, is_vip_only FROM user_photos
                   WHERE user_id IN (:ids) AND moderation_status = 'approved' ORDER BY is_primary DESC, sort_order, id", ['ids' => $userIds]) as $r) {
        if (count($out[(int)$r['user_id']] ?? []) < $per) {
            $out[(int)$r['user_id']][] = $r;
        }
    }
    return $out;
}

/**
 * Cards for the swipe deck: normalised members plus photos, interests, compatibility and relation.
 * Everything a card shows is already filtered through the privacy helpers.
 */
function explorer_cards(array $members, int $me): array
{
    if (!$members) {
        return [];
    }
    $ids = array_map(fn($m) => (int)$m['id'], $members);
    $photos = explorer_photos_for($ids, 5);
    $interests = members_interests($ids, 8);
    $mine = array_flip(explorer_interest_ids($me));
    $scores = function_exists('matching_scores') ? matching_scores($me, $ids) : [];
    $rel = explorer_relations($me, $ids);
    $showCompat = (bool)setting('discover_show_compat');
    $seeLikes = can('see_who_liked');
    $intents = explorer_intents();
    $out = [];
    foreach ($members as $m) {
        $id = (int)$m['id'];
        $pics = [];
        foreach ($photos[$id] ?? [] as $ph) {
            $pics[] = ['url' => photo_url($ph, 'large', 'profile'), 'blurred' => photo_is_blurred($ph, 'profile')];
        }
        if (!$pics) {
            $pics[] = ['url' => photo_placeholder_url(), 'blurred' => false];
        }
        $chips = [];
        $shared = 0;
        foreach ($interests[$id] ?? [] as $i) {
            $isShared = isset($mine[(int)$i['id']]);
            $shared += $isShared ? 1 : 0;
            $chips[] = ['name' => $i['name'], 'emoji' => (string)$i['emoji'], 'icon' => (string)($i['icon'] ?? ''), 'cat' => (string)($i['category'] ?? ''), 'shared' => $isShared];
        }
        usort($chips, fn($a, $b) => (int)$b['shared'] <=> (int)$a['shared']);
        $out[] = [
            'id' => $id, 'name' => $m['first_name'], 'age' => $m['age'], 'verified' => $m['verified'], 'online' => (bool)$m['online'] && (bool)setting('discover_show_online'),
            'distance' => setting('discover_show_distance') ? preg_replace('/ away$/', '', (string)$m['distance_label']) : '', 'town' => $m['location'],
            'bio' => str_limit((string)$m['bio'], 150), 'bio_full' => str_limit((string)$m['bio'], 480), 'looking_for' => str_limit((string)($m['looking_for'] ?? ''), 160),
            'intent' => $intents[$m['intent']]['label'] ?? '', 'occupation' => (string)$m['occupation'],
            'new' => explorer_is_new($m['joined_at'] ?? null), 'seen' => !$m['online'] && !empty($m['last_active']) ? time_ago((string)$m['last_active']) : '',
            'photos' => $pics, 'interests' => setting('discover_show_interests') ? array_slice($chips, 0, 8) : [], 'shared' => $shared,
            'compat' => $showCompat && isset($scores[$id]) ? (int)round($scores[$id]['compat']) : null,
            'featured' => $m['featured'], 'boosted' => $m['boosted'],
            'likes_you' => $seeLikes && !empty($rel[$id]['theirs']), 'superliked_you' => ($rel[$id]['theirs'] ?? '') === 'superlike',
            'fav' => $rel[$id]['fav'], 'liked' => in_array($rel[$id]['mine'], ['like', 'superlike'], true),
            'href' => url('/u/' . $id),
        ];
    }
    return $out;
}

/**
 * One batch of members for Discover (deck, grid, list). Personalised through the match scores.
 * $exclude are ids the client already holds. Returns ['rows' => normalised, 'total' => int].
 */
function explorer_feed(array $filters, int $limit, array $exclude = [], string $order = 'score', bool $recycle = false): array
{
    $me = auth_id();
    if ($order === 'score' && function_exists('matching_ensure_fresh')) {
        matching_ensure_fresh($me);
    }
    $exclude = array_slice(array_values(array_unique(array_map('intval', $exclude))), 0, 200);
    $min = (int)setting('min_match_score');
    $f = $filters + ['order' => $order];
    if ($exclude) {
        $f['exclude_ids'] = $exclude;
    }
    $res = members_search($f, $min > 0 ? $limit * 2 : $limit, 0);
    if ($min > 0 && $res['rows']) {
        $scores = matching_scores($me, array_map(fn($m) => (int)$m['id'], $res['rows']));
        $res['rows'] = array_slice(array_values(array_filter($res['rows'], function ($m) use ($scores, $min) {
            return $m['boosted'] || $m['featured'] || !isset($scores[(int)$m['id']]) || $scores[(int)$m['id']]['compat'] >= $min;
        })), 0, $limit);
    }
    // Everyone new has been seen: members who chose to see passes again get them back, oldest pass first.
    if ($recycle && count($res['rows']) < $limit) {
        $passed = array_map('intval', col("SELECT swiped_id FROM swipes WHERE swiper_id = :me AND direction = 'nope' AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY created_at ASC LIMIT 200", ['me' => $me]));
        $passed = array_values(array_diff($passed, $exclude, array_map(fn($m) => (int)$m['id'], $res['rows'])));
        if ($passed) {
            $again = members_search(['only_ids' => $passed, 'hide_swiped' => false, 'order' => 'newest'] + array_diff_key($filters, ['hide_swiped' => 1, 'exclude_ids' => 1]), $limit - count($res['rows']), 0);
            $res['rows'] = array_merge($res['rows'], $again['rows']);
            $res['total'] += $again['total'];
        }
    }
    return $res;
}

/** Remaining daily allowances for the viewer (-1 = unlimited) and the plan flags the UI needs. */
function explorer_allowance(int $userId): array
{
    $superOn = (bool)setting('superlike_enabled');
    return [
        'likes' => usage_remaining($userId, 'swipes'),
        'likes_limit' => (int)feature('daily_swipes', 0, $userId),
        'superlikes' => $superOn ? usage_remaining($userId, 'superlikes') : 0,
        'superlikes_in_plan' => $superOn && (int)feature('daily_superlikes', 0, $userId) !== 0,
        'superlike_enabled' => $superOn,
        'rewinds' => (setting('rewind_enabled') && can('rewind', $userId)) ? usage_remaining($userId, 'rewinds') : 0,
        'rewind_in_plan' => (bool)setting('rewind_enabled') && can('rewind', $userId) && (int)feature('daily_rewinds', 0, $userId) !== 0,
        'rewind_enabled' => (bool)setting('rewind_enabled'),
        'premium' => tier_rank((string)(current_user()['tier'] ?? 'free')) >= 1,
    ];
}

// ---------------------------------------------------------------------------
// Daily picks
// ---------------------------------------------------------------------------

/** Today's picks as normalised members (generated on first call each day). [] when the feature is off. */
function explorer_daily_picks(int $userId): array
{
    if (!setting('daily_picks_enabled')) {
        return [];
    }
    $count = max(1, (int)setting('daily_picks_count'));
    $have = (int)val('SELECT COUNT(*) FROM daily_picks WHERE user_id = :u AND pick_date = CURDATE()', ['u' => $userId]);
    if ($have === 0) {
        try {
            matching_ensure_fresh($userId);
            $f = explorer_filters_from([], $userId)['search'];
            $f['order'] = 'score';
            $pool = members_search($f + [], $count * 4, 0)['rows'];
            if (!$pool) { // nothing within the member's filters: widen to anyone suitable
                $pool = members_search(['hide_swiped' => true, 'order' => 'score'], $count * 4, 0)['rows'];
            }
            $scores = matching_scores($userId, array_map(fn($m) => (int)$m['id'], $pool));
            $featured = [];
            $normal = [];
            foreach ($pool as $m) {
                if (setting('daily_picks_featured') && ($m['featured'] || $m['boosted'])) {
                    $featured[] = $m;
                } else {
                    $normal[] = $m;
                }
            }
            $take = array_merge(array_slice($featured, 0, (int)ceil($count / 3)), $normal);
            foreach (array_slice($take, 0, $count) as $m) {
                q('INSERT IGNORE INTO daily_picks (user_id, picked_user_id, pick_date, score) VALUES (:u, :p, CURDATE(), :s)',
                    ['u' => $userId, 'p' => $m['id'], 's' => $scores[(int)$m['id']]['total'] ?? 0]);
            }
        } catch (Throwable $e) {
            app_log('error', 'daily picks failed: ' . $e->getMessage());
            return [];
        }
    }
    $picks = rows('SELECT picked_user_id AS id, score, is_acted FROM daily_picks WHERE user_id = :u AND pick_date = CURDATE() ORDER BY score DESC, picked_user_id', ['u' => $userId]);
    if (!$picks) {
        return [];
    }
    $ids = array_map(fn($p) => (int)$p['id'], $picks);
    $res = members_search(['only_ids' => $ids, 'hide_swiped' => false], count($ids), 0)['rows'];
    $byId = [];
    foreach ($res as $m) {
        $byId[(int)$m['id']] = $m;
    }
    $out = [];
    foreach ($picks as $p) {
        if (isset($byId[(int)$p['id']])) {
            $m = $byId[(int)$p['id']];
            $m['acted'] = (int)$p['is_acted'] === 1;
            $out[] = $m;
        }
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Profile data
// ---------------------------------------------------------------------------

/** One member as a profile page needs them: normalised plus the extra detail columns in ['raw']. Null if not found. */
function explorer_profile_row(int $id): ?array
{
    $coords = viewer_coords();
    $dist = $coords ? sql_distance(':vlat', ':vlng', 'ul.latitude', 'ul.longitude') : 'NULL';
    $params = ['id' => $id];
    if ($coords) {
        $params['vlat'] = $coords[0];
        $params['vlng'] = $coords[1];
    }
    $r = row('SELECT ' . MEMBER_CARD_COLS . ', ' . SQL_PHOTO_COLS . ', ' . $dist . ' AS distance_km,
                     u.status, p.education, p.height_cm, p.body_type, p.smoking, p.drinking, p.relationship_status, p.children, p.want_children,
                     p.religion, p.languages, p.is_paused, p.is_incognito, p.onboarding_completed, p.verified_at, p.allow_visits_log
              FROM users u JOIN user_profiles p ON p.user_id = u.id LEFT JOIN user_locations ul ON ul.user_id = u.id ' . sql_photo_join('u.id', 'ph') . '
              WHERE u.id = :id', $params);
    return $r ? member_normalize($r) : null;
}

/** "5 shared interests" etc: interests of a member with the viewer's shared ones flagged. */
function explorer_interests_of(int $userId, array $viewerInterestIds): array
{
    $mine = array_flip($viewerInterestIds);
    $out = [];
    foreach (rows('SELECT i.id, i.name, i.emoji, i.icon, i.category FROM user_interests ui JOIN interests i ON i.id = ui.interest_id WHERE ui.user_id = :u AND i.is_active = 1 ORDER BY i.category, i.sort_order', ['u' => $userId]) as $r) {
        $out[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'emoji' => (string)$r['emoji'], 'icon' => (string)$r['icon'], 'cat' => (string)$r['category'], 'shared' => isset($mine[(int)$r['id']])];
    }
    usort($out, fn($a, $b) => (int)$b['shared'] <=> (int)$a['shared']);
    return $out;
}

/** Human readable detail rows for a profile: [[icon,label,value], ...] */
function explorer_profile_details(array $m): array
{
    $r = $m['raw'];
    $rows = [];
    // "wide" rows hold text that can run long, so they take the whole width instead of half a line
    $add = function (string $icon, string $label, ?string $value, bool $wide = false) use (&$rows) {
        if ($value !== null && trim($value) !== '') {
            $rows[] = ['icon' => $icon, 'label' => $label, 'value' => trim($value), 'wide' => $wide];
        }
    };
    $add('briefcase', 'Work', (string)($r['occupation'] ?? ''), true);
    $add('graduation-cap', 'Education', (string)($r['education'] ?? ''), true);
    $add('ruler', 'Height', !empty($r['height_cm']) ? (int)$r['height_cm'] . ' cm' : '');
    $add('user', 'Build', explorer_options('body_type')[$r['body_type'] ?? ''] ?? '');
    $add('heart', 'Status', explorer_options('relationship_status')[$r['relationship_status'] ?? ''] ?? '');
    $add('users', 'Children', explorer_options('children')[$r['children'] ?? ''] ?? '');
    $add('baby', 'Wants children', explorer_options('want_children')[$r['want_children'] ?? ''] ?? '');
    $add('flame', 'Smoking', explorer_options('smoking')[$r['smoking'] ?? ''] ?? '');
    $add('wine', 'Drinking', explorer_options('drinking')[$r['drinking'] ?? ''] ?? '');
    $add('landmark', 'Faith', (string)($r['religion'] ?? ''));
    $add('languages', 'Languages', (string)($r['languages'] ?? ''), true);
    return $rows;
}

/** Contact link for a visible value. */
function explorer_contact_href(string $field, string $value): string
{
    switch ($field) {
        case 'phone':
            return 'tel:+' . preg_replace('/\D/', '', $value);
        case 'whatsapp':
            return 'https://wa.me/' . preg_replace('/\D/', '', $value);
        case 'email':
            return 'mailto:' . $value;
        case 'instagram':
            return 'https://instagram.com/' . rawurlencode(ltrim($value, '@'));
    }
    return '';
}

/** Who can see each contact field, as plain sentences for the member's own settings. */
function explorer_contact_rules(): array
{
    $out = [];
    foreach (CONTACT_FIELDS as $field => $label) {
        $who = [];
        foreach (['free' => 'Free members', 'premium' => 'Premium members', 'vip' => 'VIP members', 'matched' => 'people you have matched with'] as $k => $name) {
            if (setting("contact_{$field}_{$k}") === 'visible') {
                $who[] = $name;
            }
        }
        $out[$field] = ['label' => $label, 'who' => $who];
    }
    return $out;
}

/** Notification switches members can set: column => [group, title, hint, default]. */
function explorer_notification_keys(): array
{
    return [
        'inapp_matches' => ['group' => 'inapp', 'title' => 'New matches', 'hint' => 'When you and someone else like each other.', 'default' => 1],
        'inapp_messages' => ['group' => 'inapp', 'title' => 'Messages', 'hint' => 'When someone writes to you.', 'default' => 1],
        'inapp_likes' => ['group' => 'inapp', 'title' => 'Likes and Super Likes', 'hint' => 'When someone likes your profile.', 'default' => 1],
        'inapp_visits' => ['group' => 'inapp', 'title' => 'Profile views', 'hint' => 'When someone looks at your profile.', 'default' => 1],
        'inapp_gifts' => ['group' => 'inapp', 'title' => 'Gifts', 'hint' => 'When someone sends you a gift.', 'default' => 1],
        'email_matches' => ['group' => 'email', 'title' => 'New matches', 'hint' => 'An email when you get a match.', 'default' => 1],
        'email_messages' => ['group' => 'email', 'title' => 'Messages', 'hint' => 'An email when you have unread messages.', 'default' => 0],
        'email_likes' => ['group' => 'email', 'title' => 'Likes', 'hint' => 'An email when someone likes you.', 'default' => 0],
        'email_news' => ['group' => 'email', 'title' => 'News and offers', 'hint' => 'Tips, new features and special offers from ConnectHub.', 'default' => 1],
    ];
}

/** Make a plan-gated setting honest: incognito only stays on while the plan allows it. */
function explorer_heal_privacy(int $userId): void
{
    if (!can('incognito', $userId) && (int)val('SELECT is_incognito FROM user_profiles WHERE user_id = :u', ['u' => $userId], 0) === 1) {
        q('UPDATE user_profiles SET is_incognito = 0 WHERE user_id = :u', ['u' => $userId]);
    }
}

/** Is this member browsing in incognito right now (setting on AND plan allows it)? */
function explorer_is_incognito(array $user): bool
{
    return !empty($user['is_incognito']) && can('incognito', (int)$user['id']);
}

/** Does the reports table have the Explorer columns (database/schema/10_explorer.sql)? */
function explorer_reports_extended(): bool
{
    static $r = null;
    if ($r === null) {
        try {
            $r = (int)val("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'target_type'") > 0;
        } catch (Throwable $e) {
            $r = false;
        }
    }
    return $r;
}

/** Set a cookie that lives for a year. */
function explorer_cookie(string $name, string $value): void
{
    if (headers_sent()) {
        return;
    }
    setcookie($name, $value, ['expires' => time() + 31536000, 'path' => base_path() . '/' ?: '/', 'secure' => is_https(), 'httponly' => false, 'samesite' => 'Lax']);
    $_COOKIE[$name] = $value;
}

// ---------------------------------------------------------------------------
// Request helpers for the JSON endpoints
// ---------------------------------------------------------------------------

/** Request fields from a JSON body or a normal form post. */
function explorer_body(): array
{
    $b = json_body();
    return $b ? $b + $_POST : $_POST;
}

function explorer_int(array $b, string $key): int
{
    return isset($b[$key]) && is_scalar($b[$key]) && is_numeric($b[$key]) ? (int)$b[$key] : 0;
}

function explorer_str(array $b, string $key, int $max = 255): string
{
    $v = $b[$key] ?? '';
    if (!is_scalar($v)) {
        return '';
    }
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', trim((string)$v)) ?? '';
    return $max > 0 ? mb_substr($v, 0, $max) : $v;
}

/** Send a failure array from explorer_* actions as JSON. */
function explorer_fail(array $r): void
{
    $extra = array_diff_key($r, ['ok' => 1, 'error' => 1, 'status' => 1]);
    json_fail((string)($r['error'] ?? 'Something went wrong.'), (int)($r['status'] ?? 400), $extra);
}

// ---------------------------------------------------------------------------
// Connection lists (likes, visitors, favourites) and rendering
// ---------------------------------------------------------------------------

/**
 * Member ids for a connection list, newest first, already limited to members who can be shown.
 * $kind: liked_you (pending likes to answer) | super_you | you_liked | visitors | favorites
 * Returns ['ids' => [...], 'total' => int, 'at' => [id => datetime], 'meta' => [id => ['direction'=>..,'views'=>..]]]
 */
function explorer_connection_ids(string $kind, int $me, int $limit, int $offset = 0): array
{
    $ok = "u.status = 'active' AND p.onboarding_completed = 1 AND p.is_paused = 0 AND p.elite_only = 0 AND u.id <> :me
           AND NOT EXISTS (SELECT 1 FROM blocks bl WHERE (bl.blocker_id = :me AND bl.blocked_id = u.id) OR (bl.blocker_id = u.id AND bl.blocked_id = :me))";
    switch ($kind) {
        case 'liked_you':
            $from = "FROM swipes s JOIN users u ON u.id = s.swiper_id JOIN user_profiles p ON p.user_id = u.id
                     WHERE s.swiped_id = :me AND s.direction IN ('like','superlike') AND u.role = 'user' AND $ok
                       AND NOT EXISTS (SELECT 1 FROM swipes r WHERE r.swiper_id = :me AND r.swiped_id = s.swiper_id)";
            $sel = 'u.id AS id, s.created_at AS at, s.direction AS direction, 0 AS views';
            $order = 's.created_at DESC';
            break;
        case 'super_you':
            $from = "FROM swipes s JOIN users u ON u.id = s.swiper_id JOIN user_profiles p ON p.user_id = u.id
                     WHERE s.swiped_id = :me AND s.direction = 'superlike' AND u.role = 'user' AND $ok";
            $sel = 'u.id AS id, s.created_at AS at, s.direction AS direction, 0 AS views';
            $order = 's.created_at DESC';
            break;
        case 'you_liked':
            $from = "FROM swipes s JOIN users u ON u.id = s.swiped_id JOIN user_profiles p ON p.user_id = u.id
                     WHERE s.swiper_id = :me AND s.direction IN ('like','superlike') AND u.role = 'user' AND $ok";
            $sel = 'u.id AS id, s.created_at AS at, s.direction AS direction, 0 AS views';
            $order = 's.created_at DESC';
            break;
        case 'visitors':
            $from = "FROM profile_views pv JOIN users u ON u.id = pv.viewer_id JOIN user_profiles p ON p.user_id = u.id
                     WHERE pv.viewed_id = :me AND u.role = 'user' AND p.is_incognito = 0 AND $ok";
            $sel = "u.id AS id, pv.last_viewed_at AS at, '' AS direction, pv.view_count AS views";
            $order = 'pv.last_viewed_at DESC';
            break;
        case 'favorites':
            $from = "FROM favorites f JOIN users u ON u.id = f.target_id JOIN user_profiles p ON p.user_id = u.id
                     WHERE f.user_id = :me AND u.role IN ('user','host') AND $ok";
            $sel = "u.id AS id, f.created_at AS at, '' AS direction, 0 AS views";
            $order = 'f.created_at DESC';
            break;
        default:
            return ['ids' => [], 'total' => 0, 'at' => [], 'meta' => []];
    }
    $total = (int)val('SELECT COUNT(*) ' . $from, ['me' => $me]);
    $ids = [];
    $at = [];
    $meta = [];
    if ($total > 0 && $limit > 0) {
        foreach (rows('SELECT ' . $sel . ' ' . $from . ' ORDER BY ' . $order . ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), ['me' => $me]) as $r) {
            $id = (int)$r['id'];
            $ids[] = $id;
            $at[$id] = $r['at'];
            $meta[$id] = ['direction' => $r['direction'], 'views' => (int)$r['views']];
        }
    }
    return ['ids' => $ids, 'total' => $total, 'at' => $at, 'meta' => $meta];
}

/** Normalised members for a list of ids, in that order (anyone hidden by the usual rules is left out). */
function explorer_members_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $by = [];
    foreach (members_search(['only_ids' => $ids, 'hide_swiped' => false, 'has_photo' => false, 'role' => 'any'], count($ids), 0)['rows'] as $m) {
        $by[(int)$m['id']] = $m;
    }
    $out = [];
    foreach ($ids as $id) {
        if (isset($by[$id])) {
            $out[] = $by[$id];
        }
    }
    return $out;
}

/** Blurred, nameless teasers for members the viewer may not see yet: [['blur' => url]]. Nothing that identifies anyone is returned. */
function explorer_teasers(array $ids, int $n): array
{
    $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, max(0, $n));
    if (!$ids) {
        return [];
    }
    $by = [];
    foreach (rows("SELECT user_id, blur_path FROM user_photos WHERE user_id IN (:ids) AND is_primary = 1 AND moderation_status = 'approved'", ['ids' => $ids]) as $r) {
        $by[(int)$r['user_id']] = $r['blur_path'];
    }
    $out = [];
    foreach ($ids as $id) {
        $out[] = ['blur' => !empty($by[$id]) ? upload_url($by[$id]) : photo_placeholder_url()];
    }
    return $out;
}

/**
 * HTML for a batch of members in grid or list form. $opts: actions (list of like|fav|pass|unlike), actions_for [id => list],
 * notes [id => text], start (index for ad spacing), ads (bool).
 */
function explorer_render_items(array $members, string $view, array $opts = []): string
{
    $me = auth_id();
    $cards = explorer_cards($members, $me);
    $actions = $opts['actions'] ?? ['fav', 'like'];
    $notes = $opts['notes'] ?? [];
    $every = !empty($opts['ads']) ? (int)setting('ads_discover_every') : 0;
    $start = (int)($opts['start'] ?? 0);
    $html = '';
    foreach ($cards as $i => $c) {
        $html .= partial($view === 'list' ? 'explorer_row' : 'explorer_card', ['c' => $c, 'actions' => $opts['actions_for'][$c['id']] ?? $actions, 'note' => $notes[$c['id']] ?? '']);
        if ($every > 0 && (($start + $i + 1) % $every) === 0 && function_exists('ad_slot')) {
            $ad = ad_slot('discover', 'card', $view === 'list' ? 'row' : 'tile');
            if ($ad !== '') {
                $html .= '<div class="xspot ' . ($view === 'list' ? 'xspot-row' : 'xspot-card') . '">' . $ad . '</div>';
            }
        }
    }
    return $html;
}

// ---------------------------------------------------------------------------
// Profile page context
// ---------------------------------------------------------------------------

/**
 * Everything the profile page needs for member $m (from explorer_profile_row()), as the current viewer may see it.
 * $preview = the owner looking at their own profile the way others see it (approved photos only, no actions).
 */
function explorer_profile_context(array $m, bool $preview = false): array
{
    $id = (int)$m['id'];
    $viewer = current_user();
    $vid = $viewer ? (int)$viewer['id'] : 0;
    $guest = !$viewer;
    $mode = $guest ? guest_profile_mode() : 'full';
    $limited = $guest && $mode === 'limited';
    $level = (string)setting('blur_level');

    // Photos: the privacy helpers choose sharp or blurred; a limited visitor only ever receives the main photo.
    $photos = [];
    $all = member_photos($id, 'large');
    if ($preview) {
        $all = array_values(array_filter($all, fn($p) => $p['moderation_status'] === 'approved'));
    }
    $total = count($all);
    // Visitors who must sign in first, or only get a preview, receive just the main (blurred) photo.
    $oneOnly = $guest && in_array($mode, ['limited', 'login'], true);
    if ($oneOnly) {
        $all = array_slice($all, 0, 1);
    }
    foreach ($all as $p) {
        $photos[] = [
            'url' => photo_url($p, 'large', 'profile'), 'thumb' => photo_url($p, 'thumb', 'profile'),
            'blurred' => photo_is_blurred($p, 'profile'), 'vip' => (int)$p['is_vip_only'] === 1,
        ];
    }
    if (!$photos) {
        $photos[] = ['url' => photo_placeholder_url(), 'thumb' => photo_placeholder_url(), 'blurred' => false, 'vip' => false];
    }

    $mineIds = $vid ? explorer_interest_ids($vid) : [];
    $interests = explorer_interests_of($id, $mineIds);
    if ($limited) {
        $interests = array_slice($interests, 0, 6);
    }
    $bio = trim((string)$m['bio']);
    $bioCut = false;
    if ($limited && mb_strlen($bio) > (int)setting('guest_bio_chars')) {
        $bio = rtrim(mb_substr($bio, 0, max(0, (int)setting('guest_bio_chars')))) . '…';
        $bioCut = true;
    }

    $ctx = [
        'photos' => $photos, 'hidden_photos' => $oneOnly ? max(0, $total - 1) : 0, 'blur_level' => $level, 'interests' => $interests,
        'shared' => count(array_filter($interests, fn($i) => $i['shared'])), 'bio' => $bio, 'bio_cut' => $bioCut,
        'details' => ($limited || ($guest && $mode === 'login')) ? [] : explorer_profile_details($m),
        'guest' => $guest, 'mode' => $mode, 'limited' => $limited, 'preview' => $preview, 'viewer_id' => $vid, 'viewer_role' => $viewer ? (string)$viewer['role'] : '',
        'contacts' => [], 'compat' => null, 'rel' => ['mine' => null, 'theirs' => null, 'fav' => false, 'matched' => false],
        'message' => null, 'gift' => false, 'likes_you' => false,
    ];
    if ($preview) {
        $ctx['contact_rules'] = explorer_contact_rules();
        $ctx['own_contacts'] = row('SELECT contact_phone, contact_whatsapp, contact_email, contact_instagram, show_contact FROM user_profiles WHERE user_id = :u', ['u' => $id])
            ?: ['contact_phone' => '', 'contact_whatsapp' => '', 'contact_email' => '', 'contact_instagram' => '', 'show_contact' => 0];
        return $ctx;
    }
    $ctx['contacts'] = member_contacts($id);
    if ($vid) {
        $ctx['rel'] = explorer_relations($vid, [$id])[$id];
        if (setting('discover_show_compat')) {
            $sc = matching_scores($vid, [$id]);
            $ctx['compat'] = isset($sc[$id]) ? (int)round($sc[$id]['compat']) : null;
        }
        $ctx['message'] = messaging_access($id);
        $ctx['gift'] = (bool)setting('gifts_enabled');
        $ctx['likes_you'] = !empty($ctx['rel']['theirs']) && (can('see_who_liked') || $ctx['rel']['matched']);
    }
    return $ctx;
}
