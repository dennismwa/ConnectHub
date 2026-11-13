<?php
/**
 * "What brings you here?" - the visitor's choice of experience (Dating, Fun, Elite, Friends).
 *
 * The choice is stored on the device in a first-party cookie (ch_mode, one year) and, for signed-in members, on their profile
 * (user_profiles.explore_mode) so it follows them to a new phone. It is never tied to an IP address: on mobile data many
 * strangers share one address, so a choice made by one person would be shown to others.
 *
 * What a choice changes on the home page (all editable under Admin > Visitor choices):
 *   - the hero heading, the line under it and the main button for visitors
 *   - which landing blocks come first and which are hidden
 */

const CHOICE_COOKIE = 'ch_mode';
const CHOICE_KEYS = ['dating', 'fun', 'elite', 'friends'];
const CHOICE_ACCENTS = ['rose', 'amber', 'ink', 'teal', 'violet', 'sky'];

/** Built-in cards. The admin's saved values (setting choice_cards, JSON keyed by mode) are laid over these. */
function choice_defaults(): array
{
    return [
        'dating' => [
            'enabled' => true, 'in_picker' => true, 'in_switch' => true, 'order' => 1, 'label' => 'Dating', 'tagline' => 'Find someone special',
            'desc' => 'Real people near you who want something real. Free to join.',
            'points' => ['Verified profiles', 'Shown by distance', 'Chat when you match'],
            'accent' => 'rose', 'icon' => 'heart', 'image' => '', 'go' => '',
            'hero_h' => 'Meet someone worth meeting', 'hero_s' => 'Real people near you who want something real. Join free to see who is here.',
            'cta_text' => 'Start dating', 'cta_url' => '/register', 'lead' => ['profiles'], 'hide' => [],
        ],
        // Keep the internal key 'fun' for existing member choices and cookies, but present the actual product experience as Escorts.
        'fun' => [
            'enabled' => true, 'in_picker' => true, 'in_switch' => true, 'order' => 2, 'label' => 'Escorts', 'tagline' => 'Verified profiles and direct contact',
            'desc' => 'Browse approved adult profiles for non-sexual companionship, public events and conversation.',
            'points' => ['Approved adult profiles', 'Call or WhatsApp', 'Near you'],
            'accent' => 'amber', 'icon' => 'user-check', 'image' => '', 'go' => '',
            'hero_h' => 'Find verified Escorts near you', 'hero_s' => 'Browse approved profiles for permitted companionship, public events and conversation.',
            'cta_text' => 'View Escorts', 'cta_url' => '/hosts', 'lead' => ['agents_vip', 'agents_prime', 'agents_top', 'agents_new'], 'hide' => [],
        ],
        'elite' => [
            'enabled' => true, 'in_picker' => true, 'in_switch' => true, 'order' => 3, 'label' => '', 'tagline' => 'Meet established people',
            'desc' => 'Accomplished, successful people who know what they want. Private and by request.',
            'points' => ['Verified members', 'Private by request', 'Serious intentions'],
            'accent' => 'ink', 'icon' => 'gem', 'image' => '', 'go' => '',
            'hero_h' => 'Meet established, successful people', 'hero_s' => 'A private circle with verified profiles. Every member decides who reaches them.',
            'cta_text' => 'Explore', 'cta_url' => '/elite', 'lead' => ['elite'], 'hide' => [],
        ],
        'friends' => [
            'enabled' => false, 'in_picker' => true, 'in_switch' => true, 'order' => 4, 'label' => 'Friends', 'tagline' => 'Make new friends',
            'desc' => 'People nearby to hang out with, try new places and share what you love.',
            'points' => ['Shared interests', 'Group plans', 'Near you'],
            'accent' => 'teal', 'icon' => 'users', 'image' => '', 'go' => '',
            'hero_h' => 'Your next good friend is nearby', 'hero_s' => 'Meet people who like what you like, a short trip from home.',
            'cta_text' => 'Find friends', 'cta_url' => '/register', 'lead' => ['profiles'], 'hide' => [],
        ],
    ];
}

/** The modes a choice can be: the four cards plus "all" (skipped, show everything). */
function choice_valid(string $mode): bool
{
    return $mode === 'all' || in_array($mode, CHOICE_KEYS, true);
}

/** Saved admin values for every card (also the disabled ones), in admin order. */
function choice_cards_all(bool $fresh = false): array
{
    static $memo = null;
    if ($memo !== null && !$fresh) {
        return $memo;
    }
    $saved = json_decode((string)setting('choice_cards'), true);
    $saved = is_array($saved) ? $saved : [];
    $out = [];
    foreach (choice_defaults() as $key => $def) {
        $s = is_array($saved[$key] ?? null) ? $saved[$key] : [];
        $c = array_replace($def, array_intersect_key($s, $def));
        $c['key'] = $key;
        $c['enabled'] = (bool)$c['enabled'];
        $c['in_picker'] = (bool)$c['in_picker'];
        $c['in_switch'] = (bool)$c['in_switch'];
        $c['order'] = (int)$c['order'];
        $c['points'] = array_values(array_filter(array_map(fn($p) => trim((string)$p), (array)$c['points']), fn($p) => $p !== ''));
        $c['lead'] = array_values(array_filter((array)$c['lead'], 'is_string'));
        $c['hide'] = array_values(array_filter((array)$c['hide'], 'is_string'));
        $c['accent'] = in_array($c['accent'], CHOICE_ACCENTS, true) ? $c['accent'] : $def['accent'];
        if ($key === 'elite' && trim((string)$c['label']) === '') {
            $c['label'] = function_exists('elite_label') ? elite_label() : 'Elite';
        }
        $out[$key] = $c;
    }
    uasort($out, fn($a, $b) => $a['order'] <=> $b['order']);
    return $memo = $out;
}

/**
 * Cards that can be chosen right now: enabled by the admin and backed by a module that is switched on.
 *
 * $where narrows it to one place: 'picker' for the opening question, 'switch' for the menu and the chip on
 * Discover. So a card such as Elite can be left out of the question people are asked first and still be one
 * tap away in the menu. 'any' is every card that is on somewhere.
 */
function choice_cards(string $where = 'any'): array
{
    $out = [];
    foreach (choice_cards_all() as $key => $c) {
        if (!$c['enabled']) {
            continue;
        }
        if ($where === 'picker' && !$c['in_picker']) {
            continue;
        }
        if ($where === 'switch' && !$c['in_switch']) {
            continue;
        }
        if ($key === 'elite' && !(function_exists('elite_enabled') && elite_enabled())) {
            continue;
        }
        if ($key === 'fun' && !setting('hosts_enabled')) {
            continue;
        }
        $out[$key] = $c;
    }
    return $out;
}

/** Forget the cards held for this request (after the admin saves). */
function choice_cards_reset(): void
{
    choice_cards_all(true);
    cache_forget('landing_sections');
}

/** The current visitor's choice: '' (never chose), 'all' (skipped) or a card key that is still available. */
function choice_current(): string
{
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }
    // The mobile all-experiences dock can switch a member directly into a mode.
    // The query value is allow-listed by choice_save() and persisted like picker choices.
    $requested = strtolower(trim((string)($_GET['experience'] ?? '')));
    if ($requested !== '' && ($requested === 'all' || isset(choice_cards()[$requested]))) {
        choice_save($requested);
    }
    $mode = '';
    $u = current_user();
    if ($u && $u['role'] === 'user') {
        $mode = (string)val('SELECT explore_mode FROM user_profiles WHERE user_id = :u', ['u' => (int)$u['id']], '');
    }
    if ($mode === '') {
        $mode = (string)($_COOKIE[CHOICE_COOKIE] ?? '');
    }
    $mode = strtolower(trim($mode));
    if ($mode !== 'all' && !isset(choice_cards()[$mode])) {
        $mode = '';
    }
    return $memo = $mode;
}

/**
 * What the site arranges itself around for this visitor: their own choice, or (when they have not chosen) the experience the
 * admin set as the default, which is Dating. '' only when there is no default to fall back on (setting choice_default is
 * "Everything", or the default card is switched off). The picker still asks; this is what they see until they answer.
 */
function choice_effective(): string
{
    $m = choice_current();
    if ($m !== '') {
        return $m;
    }
    $d = strtolower(trim((string)setting('choice_default')));
    return $d !== '' && $d !== 'all' && isset(choice_cards()[$d]) ? $d : '';
}

/** Save a choice on this device and, for a signed-in member, on their profile. Returns false for an unknown choice. */
function choice_save(string $mode): bool
{
    $mode = strtolower(trim($mode));
    if ($mode !== 'all' && !isset(choice_cards()[$mode])) {
        return false;
    }
    if (!headers_sent()) {
        setcookie(CHOICE_COOKIE, $mode, ['expires' => time() + 31536000, 'path' => (base_path() !== '' ? base_path() : '') . '/', 'secure' => is_https(), 'httponly' => false, 'samesite' => 'Lax']);
    }
    $_COOKIE[CHOICE_COOKIE] = $mode;
    $u = current_user();
    if ($u && $u['role'] === 'user') {
        q('UPDATE user_profiles SET explore_mode = :m WHERE user_id = :u', ['m' => $mode, 'u' => (int)$u['id']]);
    }
    return true;
}

/** Search engines, link previews and uptime checks never see the picker. */
function choice_is_bot(): bool
{
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    return $ua === '' || (bool)preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegram|lighthouse|inspectiontool|pingdom|uptime|curl|wget|python|httpclient/i', $ua);
}

/** Should the picker open by itself on this page view? */
function choice_should_open(): bool
{
    if (!setting('choice_enabled') || count(choice_cards('picker')) < 2 || choice_is_bot() || isset($_GET['nopicker'])) {
        return false;
    }
    if (isset($_GET['pick'])) {
        return true; // "Preview the picker" from the admin page, or a link that asks people to choose again
    }
    $u = current_user();
    if ($u && ($u['role'] !== 'user' || !setting('choice_members'))) {
        return false;
    }
    return choice_current() === '';
}

/** The card for the current choice, or null for "never chose" and "everything". */
function choice_active_card(): ?array
{
    $m = choice_effective();
    return $m !== '' && $m !== 'all' ? (choice_cards()[$m] ?? null) : null;
}

/** Landing blocks for the current choice: hidden ones removed, the "show first" ones moved to the top in the admin's order. */
function choice_landing_sections(array $sections): array
{
    $card = choice_active_card();
    if (!$card) {
        return $sections;
    }
    $hide = array_flip($card['hide']);
    $lead = array_flip($card['lead']);
    $first = [];
    $rest = [];
    foreach ($sections as $s) {
        $k = (string)$s['section_key'];
        // the card's own hide list, then the rule that each experience shows only its own side (setting choice_exclusive)
        if (isset($hide[$k]) || !experience_allows_section($k)) {
            continue;
        }
        if (isset($lead[$k])) {
            $first[$lead[$k]] = $s;
        } else {
            $rest[] = $s;
        }
    }
    ksort($first);
    return array_merge(array_values($first), $rest);
}

/** Hero overrides for the current choice: ['h' => heading, 's' => line, 'cta' => button text, 'url' => button link] (empty = keep the site's). */
function choice_hero(): array
{
    $card = choice_active_card();
    if (!$card) {
        return ['h' => '', 's' => '', 'cta' => '', 'url' => ''];
    }
    return ['h' => trim((string)$card['hero_h']), 's' => trim((string)$card['hero_s']), 'cta' => trim((string)$card['cta_text']), 'url' => trim((string)$card['cta_url'])];
}

/** Admin: validate and store the cards posted by the Visitor choices editor. Returns [ok, error]. */
function choice_cards_save(array $in): array
{
    $defaults = choice_defaults();
    $sectionKeys = array_column(rows('SELECT section_key FROM landing_sections'), 'section_key');
    $out = [];
    foreach ($defaults as $key => $def) {
        $c = is_array($in[$key] ?? null) ? $in[$key] : [];
        $label = mb_substr(trim((string)($c['label'] ?? '')), 0, 24);
        if ($label === '' && $key !== 'elite') {
            return [false, 'Give every card a name.'];
        }
        $pick = fn($v) => array_values(array_intersect(array_map('strval', (array)$v), $sectionKeys));
        $go = trim((string)($c['go'] ?? ''));
        if ($go !== '' && !preg_match('#^/[A-Za-z0-9/_\-?=&.%]*$#', $go)) {
            return [false, 'The page to open after choosing must be a link on this site that starts with /.'];
        }
        $ctaUrl = trim((string)($c['cta_url'] ?? ''));
        // the "#" inside the class is escaped: it is also the delimiter, and an unescaped one ended the pattern early
        if ($ctaUrl !== '' && !preg_match('#^(/[A-Za-z0-9/_\-?=&.%\#]*|https?://\S+)$#', $ctaUrl)) {
            return [false, 'The button link on the "' . ($label ?: $key) . '" card is not a valid link.'];
        }
        $out[$key] = [
            'enabled' => !empty($c['enabled']),
            'in_picker' => !empty($c['in_picker']),
            'in_switch' => !empty($c['in_switch']),
            'order' => max(1, min(9, (int)($c['order'] ?? $def['order']))),
            'label' => $label,
            'tagline' => mb_substr(trim((string)($c['tagline'] ?? '')), 0, 48),
            'desc' => mb_substr(trim((string)($c['desc'] ?? '')), 0, 160),
            'points' => array_slice(array_values(array_filter(array_map(fn($p) => mb_substr(trim((string)$p), 0, 28), preg_split('/\R|,/', (string)($c['points'] ?? '')) ?: []), fn($p) => $p !== '')), 0, 3),
            'accent' => in_array($c['accent'] ?? '', CHOICE_ACCENTS, true) ? $c['accent'] : $def['accent'],
            'icon' => preg_match('/^[a-z0-9-]{2,30}$/', (string)($c['icon'] ?? '')) ? (string)$c['icon'] : $def['icon'],
            'image' => mb_substr(trim((string)($c['image'] ?? '')), 0, 255),
            'go' => $go,
            'hero_h' => mb_substr(trim((string)($c['hero_h'] ?? '')), 0, 90),
            'hero_s' => mb_substr(trim((string)($c['hero_s'] ?? '')), 0, 220),
            'cta_text' => mb_substr(trim((string)($c['cta_text'] ?? '')), 0, 32),
            'cta_url' => $ctaUrl,
            'lead' => array_slice($pick($c['lead'] ?? []), 0, 3),
            'hide' => $pick($c['hide'] ?? []),
        ];
    }
    if (!array_filter($out, fn($c) => $c['enabled'])) {
        return [false, 'Leave at least one card switched on, or switch the whole picker off.'];
    }
    setting_set('choice_cards', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    choice_cards_reset();
    return [true, ''];
}

/** How many members chose each option (for the admin page). */
function choice_stats(): array
{
    $out = array_fill_keys(array_merge(CHOICE_KEYS, ['all']), 0);
    foreach (rows("SELECT explore_mode m, COUNT(*) n FROM user_profiles WHERE explore_mode IS NOT NULL AND explore_mode <> '' GROUP BY explore_mode") as $r) {
        if (isset($out[$r['m']])) {
            $out[$r['m']] = (int)$r['n'];
        }
    }
    return $out;
}

/** Built-in picture for a card, drawn as inline SVG so it is sharp on every screen and costs no download. */
function choice_art(string $accent): string
{
    $svg = [
        // two lives overlapping, a heart where they meet
        'rose' => '<circle cx="128" cy="92" r="58" fill="#fff" fill-opacity=".14" stroke="#fff" stroke-opacity=".55" stroke-width="2"/><circle cx="192" cy="92" r="58" fill="#fff" fill-opacity=".1" stroke="#fff" stroke-opacity=".55" stroke-width="2"/>'
            . '<path d="M160 116c-15-11-25-20-25-31 0-8 6-14 13-14 5 0 9 3 12 7 3-4 7-7 12-7 7 0 13 6 13 14 0 11-10 20-25 31z" fill="#fff"/>'
            . '<path d="M262 40l3 8 8 3-8 3-3 8-3-8-8-3 8-3zM58 132l2 6 6 2-6 2-2 6-2-6-6-2 6-2z" fill="#fff" fill-opacity=".8"/>',
        // confetti and a spotlight
        'amber' => '<circle cx="232" cy="118" r="62" fill="#fff" fill-opacity=".12"/><circle cx="232" cy="118" r="38" fill="#fff" fill-opacity=".12"/>'
            . '<rect x="70" y="44" width="22" height="9" rx="4.5" fill="#fff" transform="rotate(-24 81 48)"/><rect x="128" y="30" width="16" height="8" rx="4" fill="#fff" fill-opacity=".75" transform="rotate(32 136 34)"/>'
            . '<rect x="98" y="104" width="20" height="8" rx="4" fill="#fff" fill-opacity=".85" transform="rotate(18 108 108)"/><circle cx="168" cy="64" r="6" fill="#fff"/><circle cx="56" cy="96" r="4" fill="#fff" fill-opacity=".7"/>'
            . '<path d="M150 136c8-10 16 10 24 0s16 10 24 0" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-opacity=".85"/>'
            . '<path d="M232 84l6 17 17 6-17 6-6 17-6-17-17-6 17-6z" fill="#fff"/>',
        // a cut stone in gold lines
        'ink' => '<defs><linearGradient id="chzGold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f6e3b0"/><stop offset=".55" stop-color="#d4a85a"/><stop offset="1" stop-color="#9c7432"/></linearGradient></defs>'
            . '<circle cx="160" cy="90" r="78" fill="none" stroke="url(#chzGold)" stroke-opacity=".35" stroke-width="1.5"/><circle cx="160" cy="90" r="104" fill="none" stroke="url(#chzGold)" stroke-opacity=".18" stroke-width="1.5"/>'
            . '<path d="M124 64h72l22 26-58 52-58-52z" fill="url(#chzGold)" fill-opacity=".12" stroke="url(#chzGold)" stroke-width="2.5" stroke-linejoin="round"/>'
            . '<path d="M102 90h116M124 64l14 26 22 52 22-52 14-26M138 90l22-26 22 26" fill="none" stroke="url(#chzGold)" stroke-width="1.6" stroke-linejoin="round" stroke-opacity=".9"/>'
            . '<path d="M254 38l2 6 6 2-6 2-2 6-2-6-6-2 6-2zM70 134l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="#f6e3b0"/>',
        // friends in a circle
        'teal' => '<circle cx="118" cy="96" r="30" fill="#fff" fill-opacity=".16" stroke="#fff" stroke-opacity=".6" stroke-width="2"/><circle cx="202" cy="96" r="30" fill="#fff" fill-opacity=".16" stroke="#fff" stroke-opacity=".6" stroke-width="2"/><circle cx="160" cy="56" r="26" fill="#fff" fill-opacity=".22" stroke="#fff" stroke-opacity=".7" stroke-width="2"/>'
            . '<path d="M140 78l-6 6M180 78l6 6M148 108h24" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-dasharray="2 7"/>',
        'violet' => '<circle cx="160" cy="90" r="64" fill="#fff" fill-opacity=".14"/><path d="M160 46l12 32 34 2-27 21 9 33-28-19-28 19 9-33-27-21 34-2z" fill="#fff" fill-opacity=".9"/>',
        'sky' => '<path d="M40 130c40-60 80-60 120 0s80 60 120 0" fill="none" stroke="#fff" stroke-opacity=".6" stroke-width="3"/><circle cx="160" cy="70" r="26" fill="#fff" fill-opacity=".85"/>',
    ][$accent] ?? '';
    return '<svg class="chz-art-svg" viewBox="0 0 320 180" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">' . $svg . '</svg>';
}
