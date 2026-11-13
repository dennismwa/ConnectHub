<?php
/**
 * What the Discover page shows, and in what order, for the experience a member chose (Dating, Fun, Elite, Friends).
 *
 * The choice itself lives in includes/lib/choices.php: a cookie on the device and, for a signed-in member, a column
 * on their profile. Discover reads it and decides which sections appear and where:
 *
 *   dating / friends   the people feed leads, the Hosts row sits at the bottom
 *   fun                the Hosts row leads, the people feed follows
 *   elite              the Elite row leads
 *   all / not chosen   the admin's own order (Settings > Discovery, and Hosts > Names and Discover)
 *
 * With "Show only what they chose" on (Admin > Visitor choices, setting choice_exclusive) an experience is exclusive:
 *
 *   dating / friends   the people side only: Today's picks and the people feed. No Hosts, no Elite, no Host stories.
 *   fun                the Host side only: stories and a full page of Hosts. No people feed, no Elite.
 *   elite              the Elite side only: a full page of Elite members.
 *
 * The same module map trims the home page, so a visitor who chose Dating never meets a Host block there either.
 * With that setting off, nothing is filtered out and the order rules above apply (discover_mode_filters).
 */

declare(strict_types=1);

/** Where a section can sit on the page. */
const DISCOVER_SLOTS = ['top', 'after_picks', 'bottom', 'off'];

/** Modes Discover knows how to arrange. Anything else falls back to the admin's order. */
const DISCOVER_MODES = ['dating', 'fun', 'elite', 'friends'];

/**
 * Which module each home page block belongs to. A block that is not listed is neutral (How it works, Safety,
 * Questions...) and shows for every experience.
 */
const EXPERIENCE_SECTION_MODULES = [
    'profiles' => 'dating', 'locations' => 'dating', 'international' => 'dating',
    'agents_vip' => 'concierge', 'agents_prime' => 'concierge', 'agents_top' => 'concierge', 'agents_new' => 'concierge', 'hosts' => 'concierge',
    'elite' => 'elite',
];

/**
 * The modules an experience includes, or null when it includes everything: nothing chosen, "show me everything",
 * or the admin has switched exclusivity off.
 */
function experience_modules(?string $mode = null): ?array
{
    $mode = $mode ?? (function_exists('choice_effective') ? choice_effective() : '');
    if ($mode === '' || $mode === 'all' || !setting('choice_exclusive')) {
        return null;
    }
    return ['dating' => ['dating'], 'friends' => ['dating'], 'fun' => ['concierge'], 'elite' => ['elite']][$mode] ?? null;
}

/** May a block of this module show for the experience? */
function experience_allows(string $module, ?string $mode = null): bool
{
    $m = experience_modules($mode);
    return $m === null || in_array($module, $m, true);
}

/** Should a home page block show for the experience? Blocks that belong to no module always do. */
function experience_allows_section(string $sectionKey, ?string $mode = null): bool
{
    $module = EXPERIENCE_SECTION_MODULES[$sectionKey] ?? '';
    return $module === '' || experience_allows($module, $mode);
}

/** Should the chip that switches experience appear next to Filters? */
function discover_switch_on(): bool
{
    return (bool)setting('choice_enabled') && (bool)setting('discover_mode_switch') && count(discover_switch_cards()) >= 2;
}

/**
 * The experiences a member can switch between on Discover: the cards the admin enabled for the switcher,
 * each already checked against the module behind it.
 */
function discover_switch_cards(): array
{
    if (!function_exists('choice_cards')) {
        return [];
    }
    $out = [];
    foreach (choice_cards('switch') as $key => $c) {
        $out[$key] = $c;
    }
    return $out;
}

/** The member's current experience, as far as Discover is concerned ('' means they never chose). */
function discover_mode(): string
{
    $m = function_exists('choice_effective') ? choice_effective() : '';
    return in_array($m, DISCOVER_MODES, true) || $m === 'all' ? $m : '';
}

/**
 * Where each section sits for this mode.
 *
 * @return array{picks:bool, agents:string, elite:string, people:bool, lead:string, focus:bool, stories:bool, toolbar:bool, nudges:bool}
 *   agents / elite are one of DISCOVER_SLOTS. "lead" names the section the page is built around, for the heading.
 *   focus: the page is one module's own grid (Fun, Elite) rather than the people feed with rows around it.
 *   stories / toolbar / nudges: the Host stories, the view and filter controls, and the profile reminders.
 */
function discover_layout(?string $mode = null): array
{
    $mode = $mode ?? discover_mode();
    $adminAgents = (string)setting('host_discover_position');
    if (!in_array($adminAgents, DISCOVER_SLOTS, true)) {
        $adminAgents = 'after_picks';
    }
    if (!setting('host_discover_enabled') || !hosts_enabled()) {
        $adminAgents = 'off';
    }
    $eliteOn = setting('discover_elite_enabled') && function_exists('elite_enabled') && elite_enabled();
    $base = ['picks' => (bool)setting('daily_picks_enabled'), 'agents' => $adminAgents, 'elite' => $eliteOn ? 'bottom' : 'off', 'people' => true, 'lead' => 'people',
        'focus' => false, 'stories' => true, 'toolbar' => true, 'nudges' => true];

    // exclusive: one module gets the page and the others are not on it at all
    $ex = experience_modules($mode);
    if ($ex !== null) {
        $dating = in_array('dating', $ex, true);
        $hosts = in_array('concierge', $ex, true) && hosts_enabled();
        $elite = in_array('elite', $ex, true) && function_exists('elite_enabled') && elite_enabled();
        return [
            'picks' => $dating && $base['picks'], 'agents' => $hosts ? 'top' : 'off', 'elite' => $elite ? 'top' : 'off', 'people' => $dating,
            'lead' => $dating ? 'people' : ($hosts ? 'agents' : 'elite'), 'focus' => !$dating,
            'stories' => in_array('concierge', $ex, true), 'toolbar' => $dating, 'nudges' => $dating,
        ];
    }

    if (!setting('discover_mode_filters') || $mode === '' || $mode === 'all') {
        return $base;
    }
    switch ($mode) {
        case 'fun':
            // they came for company: the Hosts row leads and the Elite row steps aside
            return ['picks' => $base['picks'], 'agents' => $adminAgents === 'off' ? 'off' : 'top', 'elite' => 'off', 'people' => true, 'lead' => 'agents'] + $base;
        case 'elite':
            return ['picks' => $base['picks'], 'agents' => $adminAgents === 'off' ? 'off' : 'bottom', 'elite' => $eliteOn ? 'top' : 'off', 'people' => true, 'lead' => 'elite'] + $base;
        case 'dating':
        case 'friends':
        default:
            // they came to meet people: the feed leads, Hosts move below it
            return ['picks' => $base['picks'], 'agents' => $adminAgents === 'off' ? 'off' : 'bottom', 'elite' => $eliteOn ? 'bottom' : 'off', 'people' => true, 'lead' => 'people'] + $base;
    }
}

/** The intents that go with an experience, used only when the admin asks Discover to narrow the feed. */
function discover_mode_intents(string $mode): array
{
    return [
        'dating' => ['dating', 'relationship'],
        'friends' => ['friendship', 'networking'],
        'fun' => ['casual', 'dating'],
    ][$mode] ?? [];
}

/** Elite members for the row on Discover. Empty while the module is off or too few profiles are live. */
function discover_elite_rows(int $viewerId, ?int $limit = null): array
{
    // asking for a limit means the page is built around these members, so the row's own switch does not apply
    if (($limit === null && !setting('discover_elite_enabled')) || !function_exists('elite_enabled') || !elite_enabled()) {
        return [];
    }
    $n = $limit !== null ? max(2, min(60, $limit)) : max(2, min(24, (int)setting('discover_elite_count')));
    try {
        $res = elite_search($viewerId, [], $n);
    } catch (Throwable $e) {
        return [];
    }
    $saved = function_exists('elite_saved_ids') && $viewerId > 0 ? elite_saved_ids($viewerId) : [];
    $out = [];
    foreach ($res['rows'] as $r) {
        $out[] = elite_card($r, $viewerId, $saved);
    }
    return $out;
}

/** How many of each thing a section shows, kept inside sensible limits. */
function discover_counts(): array
{
    return [
        'picks' => max(1, min(24, (int)setting('daily_picks_count'))),
        'agents' => max(2, min(40, (int)setting('host_discover_count'))),
        'elite' => max(2, min(24, (int)setting('discover_elite_count'))),
        'people' => max(5, min(60, (int)setting('discover_batch_size'))),
        'focus' => max(6, min(60, (int)setting('discover_focus_count'))),
    ];
}

/** The heading over the people feed, worded for the experience the member chose. */
function discover_people_title(string $mode): string
{
    return [
        'dating' => 'People to meet',
        'friends' => 'People to get to know',
        'fun' => 'People near you',
        'elite' => 'People near you',
    ][$mode] ?? 'People near you';
}
