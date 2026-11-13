<?php
/**
 * Hosts module: shared logic for the directory, profiles, bookings, payouts and reviews.
 *
 * Pages stay thin and call these functions. Anything that decides money, time or permissions lives here
 * so it can be tested from the command line (see tools/test_hosts.php).
 *
 * Sections:
 *   1. Text safety and rules          6. Booking creation and payment hook
 *   2. Reference data                 7. Booking state machine, refunds, housekeeping
 *   3. Profiles and extension rows    8. Earnings, balance and payouts
 *   4. Weekly availability            9. Reviews
 *   5. Free slots and pricing        10. Application, approval, private ID files
 *                                    11. Directory                12. Page helpers (gates, reports, calendar)
 */

declare(strict_types=1);

const HOSTS_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
const HOSTS_DAY_LABELS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
const HOSTS_ID_TYPES = ['national_id' => 'National ID card', 'passport' => 'Passport', 'driving_licence' => 'Driving licence'];
const HOSTS_BOOKING_STATUS = [
    'awaiting_payment' => 'Awaiting payment', 'pending' => 'Waiting for host', 'confirmed' => 'Confirmed', 'completed' => 'Completed',
    'cancelled' => 'Cancelled', 'declined' => 'Declined', 'no_show' => 'No-show',
];
/** Statuses that hold a time slot. */
const HOSTS_SLOT_STATUSES = ['pending', 'confirmed', 'completed', 'no_show'];

// ===========================================================================
// 1. Text safety and rules
// ===========================================================================

/** Lower-cased text plus a version with common character swaps undone (s3x, $ex), for word checks. */
function hosts_text_variants(string $text): array
{
    $t = mb_strtolower($text);
    $t = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $t) ?? $t;
    $swap = strtr($t, ['@' => 'a', '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '$' => 's', '!' => 'i']);
    // "s e x" and "s.e.x": runs of three or more single letters joined by spaces or dots are read as one word.
    $join = fn(string $x) => preg_replace_callback('/(?<![\p{L}\p{N}])(?:\p{L}[\s.\-_*]+){2,}\p{L}(?![\p{L}\p{N}])/u', fn($m) => preg_replace('/[\s.\-_*]+/u', '', $m[0]), $x) ?? $x;
    return array_values(array_unique([$t, $swap, $join($t), $join($swap)]));
}

function hosts_term_list(string $settingKey): array
{
    static $cache = [];
    if (!isset($cache[$settingKey])) {
        $cache[$settingKey] = array_values(array_filter(array_map(fn($w) => mb_strtolower(trim($w)), explode(',', (string)setting($settingKey))), fn($w) => $w !== ''));
    }
    return $cache[$settingKey];
}

function hosts_term_match(string $text, array $terms): ?string
{
    foreach (hosts_text_variants($text) as $variant) {
        foreach ($terms as $w) {
            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($w, '/') . '(?![\p{L}\p{N}])/iu', $variant)) {
                return $w;
            }
        }
    }
    return null;
}

/**
 * Check host- or client-written text against the general banned words (Safety settings) and the Hosts list
 * (host_banned_terms). Returns the matched word, or null when the text is fine.
 */
function hosts_text_violation(string $text): ?string
{
    if (trim($text) === '') {
        return null;
    }
    $w = contains_banned_word($text);
    if ($w !== null) {
        return $w;
    }
    $core = array_values(array_filter(array_map('mb_strtolower', setting_csv('banned_words')), fn($x) => $x !== ''));
    return hosts_term_match($text, array_values(array_unique(array_merge($core, hosts_term_list('host_banned_terms')))));
}

/**
 * Public Host text (profile, packages, reviews, replies) must not carry contact details or ways to take a booking off the platform.
 * Returns what was found (email, link, phone number, handle, app) or null.
 */
function hosts_contact_violation(string $text): ?string
{
    if (trim($text) === '') {
        return null;
    }
    $t = mb_strtolower($text);
    $t = preg_replace('/\s*[\(\[\{]\s*(at|@)\s*[\)\]\}]\s*/u', '@', $t);
    $t = preg_replace('/\s*[\(\[\{]\s*dot\s*[\)\]\}]\s*/u', '.', $t);
    if (preg_match('/[^\s@]+@[^\s@]+\.[a-z]{2,}/u', $t)) {
        return 'email address';
    }
    if (preg_match('~(https?://|www\.)\S+~u', $t) || preg_match('/\b[a-z0-9][a-z0-9-]{1,}\.(com|net|org|co\.ke|ke|io|me|app|link|xyz|info|biz|site|online|chat)\b/u', $t)) {
        return 'link';
    }
    if (preg_match('/(?:\+?\d[\s().\-]*){9,}/u', $t)) {
        return 'phone number';
    }
    if (preg_match('/(?<![\w.])@[a-z0-9_.]{3,}/u', $t)) {
        return 'social handle';
    }
    if (preg_match('/\b(whats?\s?app|watsapp|telegram|instagram|insta|snap\s?chat|tiktok|facebook|wechat|viber|dm me|inbox me|text me on|call me on|pay me directly|pay cash directly)\b/u', $t)) {
        return 'contact app';
    }
    return null;
}

/** A stored phone number (2547...) as people type it: "0712 345 678". Other formats come back unchanged. */
function hosts_phone_local(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone) ?? '';
    $prefix = preg_replace('/\D/', '', (string)setting('phone_prefix')) ?: '254';
    if ($d !== '' && strpos($d, $prefix) === 0 && strlen($d) === strlen($prefix) + 9) {
        $n = substr($d, strlen($prefix));
        return '0' . substr($n, 0, 3) . ' ' . substr($n, 3, 3) . ' ' . substr($n, 6, 3);
    }
    if (strlen($d) === 10 && $d[0] === '0') { // already typed the local way: 0712345678
        return substr($d, 0, 4) . ' ' . substr($d, 4, 3) . ' ' . substr($d, 7, 3);
    }
    return $phone;
}

/** The standard refusal message for text that carries contact details. */
function hosts_contact_message(string $field = 'This text'): string
{
    if (!hosts_booking_enabled()) {
        return $field . ' includes contact details. Please remove them from the text and add them under "Contact details" in your profile instead, where the site shows them to the right people.';
    }
    return $field . ' includes contact details or a way to arrange things outside ConnectHub. Please remove it. Clients and Hosts can message each other here once a booking is confirmed.';
}

/** A meeting place must be public. Returns the matched private-place word or null. */
function hosts_private_place_violation(string $text): ?string
{
    return hosts_term_match($text, hosts_term_list('host_private_place_terms'));
}

/** The standard refusal message for text that breaks the rules. */
function hosts_violation_message(string $field = 'This text'): string
{
    return $field . ' includes wording that is not allowed. Hosts offer friendly company only, and sexual services are not permitted.';
}

/** Rules text as a list of ['type' => 'p'|'li', 'text' => string] for rendering. */
function hosts_rules_lines(): array
{
    $out = [];
    $key = (function_exists('hosts_contact_way') && hosts_contact_way()) ? 'host_rules_text_contact' : 'host_rules_text';
    $text = trim((string)setting($key));
    if ($text === '') {
        $text = (string)setting('host_rules_text');
    }
    foreach (preg_split('/\R/', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if ($line[0] === '-' || $line[0] === '*') {
            $out[] = ['type' => 'li', 'text' => trim(ltrim($line, '-* '))];
        } else {
            $out[] = ['type' => 'p', 'text' => $line];
        }
    }
    return $out;
}

/** Cancellation policy as plain sentences, built from the settings. */
function hosts_cancellation_lines(): array
{
    $full = (int)setting('host_cancel_full_hours');
    $part = (int)setting('host_cancel_partial_hours');
    $pct = (int)setting('host_cancel_partial_pct');
    $lines = [];
    $lines[] = 'Cancel before the Host accepts and you get your whole deposit back.';
    $lines[] = 'Cancel at least ' . $full . ' hours before the start and you get your whole deposit back.';
    if ($pct > 0 && $part < $full) {
        $lines[] = 'Cancel between ' . $part . ' and ' . $full . ' hours before the start and ' . $pct . '% of the deposit is refunded.';
        $lines[] = 'Cancel less than ' . $part . ' hours before the start and the deposit is kept.';
    } else {
        $lines[] = 'Cancel later than that and the deposit is kept.';
    }
    $lines[] = 'If the Host declines, cancels or does not answer in time, you always get the full deposit back.';
    return $lines;
}

function hosts_enabled(): bool
{
    return (bool)setting('hosts_enabled');
}

function hosts_booking_enabled(): bool
{
    // The contact way of running the module has no bookings at all, so every booking page, button and
    // endpoint that already asks this question switches itself off. See includes/lib/host_mode.php.
    if (function_exists('hosts_module_mode') && hosts_module_mode() === 'contact') {
        return false;
    }
    return (bool)setting('hosts_enabled') && (bool)setting('booking_enabled');
}

/** Has this member (as Host or as client) any booking on record? Keeps their booking pages open after bookings are switched off. */
function hosts_user_has_bookings(int $uid): bool
{
    static $memo = [];
    if (!isset($memo[$uid])) {
        try {
            $memo[$uid] = (int)val('SELECT COUNT(*) FROM host_bookings WHERE host_id = :u OR client_id = :u', ['u' => $uid], 0) > 0;
        } catch (Throwable $e) {
            $memo[$uid] = false;
        }
    }
    return $memo[$uid];
}

/** Has this Host any payout on record? Keeps Earnings and Payouts in their menu after bookings are switched off. */
function hosts_user_has_payouts(int $uid): bool
{
    static $memo = [];
    if (!isset($memo[$uid])) {
        try {
            $memo[$uid] = (int)val('SELECT COUNT(*) FROM host_payouts WHERE host_id = :u', ['u' => $uid], 0) > 0;
        } catch (Throwable $e) {
            $memo[$uid] = false;
        }
    }
    return $memo[$uid];
}

/**
 * Stop a booking page when the admin has switched bookings off. People who already have bookings can still open the pages that
 * show them ($keepIfAny), so nothing paid is ever hidden.
 */
function hosts_bookings_guard(bool $keepIfAny = true): void
{
    if (hosts_booking_enabled()) {
        return;
    }
    $u = current_user();
    if ($keepIfAny && $u && hosts_user_has_bookings((int)$u['id'])) {
        return;
    }
    flash('info', 'Bookings are not available on this site. You can get in touch with Hosts from their profile.');
    redirect($u && $u['role'] === 'host' ? '/host/dashboard' : '/hosts');
}

// ---------------------------------------------------------------------------
// Contact details (who may see a Host's phone, WhatsApp, email and Instagram)
// ---------------------------------------------------------------------------

/** The rule in force. "after_booking" needs bookings, so it becomes "members" while bookings are off. */
function hosts_contact_mode(): string
{
    $m = (string)setting('hosts_contact_visibility');
    if (!in_array($m, ['after_booking', 'members', 'premium', 'everyone', 'rules', 'hidden'], true)) {
        $m = 'after_booking';
    }
    if ($m === 'after_booking' && !hosts_booking_enabled()) {
        $m = 'members';
    }
    return $m;
}

/** One sentence for Hosts: who can see the contact details they add. */
function hosts_contact_policy_text(): string
{
    return [
        'after_booking' => 'The site shows them only to clients whose booking with you is confirmed.',
        'members' => 'The site shows them to signed-in members.',
        'premium' => 'The site shows them to Premium and VIP members.',
        'everyone' => 'The site shows them to everyone who opens your profile, including visitors.',
        'rules' => 'The site shows them according to its privacy rules for contact details.',
        'hidden' => 'The site is not showing contact details at the moment, so people reach you by message.',
    ][hosts_contact_mode()] ?? '';
}

/**
 * May this viewer see the Host's contact details? Returns ['allowed' => bool, 'reason' => ''|'login'|'upgrade'|'booking'|'hidden'|'rules'].
 * 'rules' means the admin chose the Privacy contact table: member_contacts() decides field by field.
 */
function hosts_contact_access(?array $viewer, int $hostId): array
{
    $mode = hosts_contact_mode();
    if ($viewer && ((int)$viewer['id'] === $hostId || $viewer['role'] === 'admin')) {
        return ['allowed' => true, 'reason' => ''];
    }
    if ($mode === 'hidden') {
        return ['allowed' => false, 'reason' => 'hidden'];
    }
    if ($mode === 'everyone') {
        return ['allowed' => true, 'reason' => ''];
    }
    if (!$viewer) {
        return ['allowed' => false, 'reason' => 'login'];
    }
    if ($mode === 'rules') {
        return ['allowed' => false, 'reason' => 'rules'];
    }
    if ($mode === 'members') {
        return ['allowed' => true, 'reason' => ''];
    }
    if ($mode === 'premium') {
        return tier_rank((string)$viewer['tier']) >= 1 ? ['allowed' => true, 'reason' => ''] : ['allowed' => false, 'reason' => 'upgrade'];
    }
    $booked = (int)val("SELECT COUNT(*) FROM host_bookings WHERE host_id = :h AND client_id = :c AND status IN ('confirmed','completed')", ['h' => $hostId, 'c' => $viewer['id']], 0) > 0;
    return $booked ? ['allowed' => true, 'reason' => ''] : ['allowed' => false, 'reason' => 'booking'];
}

/**
 * What the contact card on a Host's profile should show for the current viewer.
 * @return array ['state' => 'visible'|'login'|'upgrade'|'booking'|'hidden'|'none', 'items' => [field => ['label','value']], 'note' => string]
 */
function hosts_contacts_for(int $hostId, ?array $viewer = null): array
{
    $viewer = $viewer ?? current_user();
    $note = trim((string)setting('hosts_contact_note'));
    $p = row('SELECT show_contact, contact_phone, contact_whatsapp, contact_email, contact_instagram FROM user_profiles WHERE user_id = :u', ['u' => $hostId]);
    $own = $viewer && (int)$viewer['id'] === $hostId;
    $mine = [];
    foreach (CONTACT_FIELDS as $f => $label) {
        $v = trim((string)($p['contact_' . $f] ?? ''));
        if ($v !== '' && setting('hosts_contact_' . $f)) {
            $mine[$f] = ['label' => $label, 'value' => $v];
        }
    }
    if (!$own && (!$p || !(int)$p['show_contact'])) {
        return ['state' => $mine ? 'hidden' : 'none', 'items' => [], 'note' => ''];
    }
    $acc = hosts_contact_access($viewer, $hostId);
    if ($acc['reason'] === 'rules') {
        $items = [];
        foreach (member_contacts($hostId) as $f => $c) {
            if (!empty($c['visible']) && setting('hosts_contact_' . $f)) {
                $items[$f] = ['label' => $c['label'], 'value' => (string)$c['value']];
            }
        }
        return ['state' => $items ? 'visible' : ($mine ? 'hidden' : 'none'), 'items' => $items, 'note' => $items ? $note : ''];
    }
    if (!$mine) {
        return ['state' => 'none', 'items' => [], 'note' => ''];
    }
    if (!$acc['allowed']) {
        return ['state' => $acc['reason'], 'items' => [], 'note' => ''];
    }
    return ['state' => 'visible', 'items' => $mine, 'note' => $note];
}

// ===========================================================================
// 2. Reference data
// ===========================================================================

/** Active categories with a computed url slug: [id => row]. */
function hosts_categories(): array
{
    static $c = null;
    if ($c === null) {
        $c = [];
        foreach (rows('SELECT id, name, icon, sort_order, seo_title, seo_description, seo_keywords, seo_intro FROM host_categories WHERE is_active = 1 ORDER BY sort_order, name') as $r) {
            $r['id'] = (int)$r['id'];
            $r['slug'] = slugify((string)$r['name']);
            $c[$r['id']] = $r;
        }
    }
    return $c;
}

function hosts_category_by_slug(string $slug): ?array
{
    foreach (hosts_categories() as $c) {
        if ($c['slug'] === $slug) {
            return $c;
        }
    }
    return null;
}

/** Active service tags the admin manages: [id => row]. */
function hosts_services_catalog(): array
{
    static $c = null;
    if ($c === null) {
        $c = [];
        foreach (rows('SELECT id, name, description, group_name, icon, color FROM host_services WHERE is_active = 1 ORDER BY sort_order, name') as $r) {
            $c[(int)$r['id']] = $r;
        }
    }
    return $c;
}

/** "1,2,3" or array to a clean list of positive ints. */
function hosts_ids($csv): array
{
    $list = is_array($csv) ? $csv : explode(',', (string)$csv);
    return array_values(array_unique(array_filter(array_map('intval', $list), fn($x) => $x > 0)));
}

/** Names of locations by id: [id => ['id','name','type','parent_id','county_name']] in the order given. */
function hosts_locations_by_ids(array $ids): array
{
    $ids = hosts_ids($ids);
    if (!$ids) {
        return [];
    }
    $out = [];
    foreach (rows('SELECT l.id, l.name, l.type, l.parent_id, c.name AS county_name FROM locations l LEFT JOIN locations c ON c.id = l.parent_id WHERE l.id IN (:ids)', ['ids' => $ids]) as $r) {
        $out[(int)$r['id']] = $r;
    }
    $sorted = [];
    foreach ($ids as $id) {
        if (isset($out[$id])) {
            $sorted[$id] = $out[$id];
        }
    }
    return $sorted;
}

// ===========================================================================
// 3. Profiles and extension rows
// ===========================================================================

const HOSTS_EXT_DEFAULTS = [
    'instant_booking' => 0, 'bookings_paused' => 0, 'allow_custom_hours' => 1, 'max_hours' => null, 'payout_phone' => null, 'payout_name' => null,
    'id_doc_type' => null, 'id_doc_path' => null, 'selfie_path' => null, 'id_submitted_at' => null, 'rules_agreed_at' => null, 'rules_agreed_ip' => null,
    'notify_email_bookings' => 1, 'notify_email_reminders' => 1, 'notify_email_reviews' => 1, 'deactivate_requested_at' => null,
    'show_prices' => 1, 'listing_until' => null, 'listing_period' => null, 'is_vip' => 0, 'pin_order' => 0, 'promo_text' => null,
];

/** The host's extension row (defaults when none exists yet). */
function hosts_ext(int $userId): array
{
    $r = row('SELECT * FROM host_profile_ext WHERE user_id = :u', ['u' => $userId]);
    return ($r ?: []) + HOSTS_EXT_DEFAULTS;
}

/** Update extension fields (creates the row if needed). Only known columns are written. */
function hosts_ext_save(int $userId, array $data): void
{
    $data = array_intersect_key($data, HOSTS_EXT_DEFAULTS);
    q('INSERT IGNORE INTO host_profile_ext (user_id) VALUES (:u)', ['u' => $userId]);
    if ($data) {
        update('host_profile_ext', $data, ['user_id' => $userId]);
    }
}

/** Services grouped for the picker: [group name => [id => row]], groups in catalogue order. */
function hosts_services_grouped(): array
{
    $out = [];
    foreach (hosts_services_catalog() as $id => $r) {
        $out[(string)($r['group_name'] ?: 'More')][$id] = $r;
    }
    return $out;
}

/** May this Host's prices be shown, given the admin switch and the Host's own choice (ext.show_prices)? */
function hosts_prices_allowed($showPricesFlag): bool
{
    return !setting('host_rates_hideable') || (int)($showPricesFlag ?? 1) === 1;
}

/** Are Hosts asked to pay to be listed? */
function hosts_listing_required(): bool
{
    return (bool)setting('host_listing_fee_enabled');
}

/** SQL fragment (starts with AND) that keeps unpaid Hosts out of public lists. Empty when no fee is charged. $alias is the host_profiles alias. */
function hosts_listing_sql(string $alias = 'hp'): string
{
    if (!hosts_listing_required()) {
        return '';
    }
    return " AND EXISTS (SELECT 1 FROM host_profile_ext lx WHERE lx.user_id = $alias.user_id AND lx.listing_until IS NOT NULL AND lx.listing_until > NOW())";
}

/** Is this Host's listing paid up (or free of charge)? $ext is a host_profile_ext row or merged profile. */
function hosts_listing_active(array $ext): bool
{
    return !hosts_listing_required() || (!empty($ext['listing_until']) && strtotime((string)$ext['listing_until']) > time());
}

/** Listing options an admin has priced: [period => ['label','days','price']]. A price of 0 switches that period off. */
function hosts_listing_plans(): array
{
    $defs = ['weekly' => ['Weekly', 7, 'host_listing_price_weekly'], 'monthly' => ['Monthly', 30, 'host_listing_price_monthly'], 'yearly' => ['Yearly', 365, 'host_listing_price_yearly']];
    $out = [];
    foreach ($defs as $k => [$label, $days, $key]) {
        $price = (float)setting($key);
        if ($price > 0) {
            $out[$k] = ['label' => $label, 'days' => $days, 'price' => $price];
        }
    }
    return $out;
}

/** Add days to a Host's listing (from today, or from the current end if it is still running). Returns the new end as 'Y-m-d H:i:s'. */
function hosts_listing_extend(int $hostId, int $days, string $period = 'custom'): string
{
    hosts_ext_save($hostId, []);
    $cur = val('SELECT listing_until FROM host_profile_ext WHERE user_id = :u', ['u' => $hostId]);
    $from = $cur && strtotime((string)$cur) > time() ? strtotime((string)$cur) : time();
    $until = date('Y-m-d H:i:s', $from + max(1, $days) * 86400);
    update('host_profile_ext', ['listing_until' => $until, 'listing_period' => $period], ['user_id' => $hostId]);
    cache_clear();
    return $until;
}

/** Host profile row merged with the extension row, or null when the user has no host profile. */
function hosts_profile(int $userId): ?array
{
    $hp = row('SELECT hp.*, u.status AS user_status, u.featured_until, u.last_active_at, u.created_at AS joined_at
               FROM host_profiles hp JOIN users u ON u.id = hp.user_id WHERE hp.user_id = :u AND u.role = \'host\'', ['u' => $userId]);
    if (!$hp) {
        return null;
    }
    $hp['user_id'] = (int)$hp['user_id'];
    return $hp + hosts_ext($userId);
}

/** Can the public see this host (approved, active account, not paused out of the directory)? */
function hosts_is_public(array $hp): bool
{
    return ($hp['approval_status'] ?? '') === 'approved' && ($hp['user_status'] ?? '') === 'active';
}

/** Package rows for a host. */
function hosts_offerings(int $hostId, bool $activeOnly = true): array
{
    return rows('SELECT * FROM host_offerings WHERE host_id = :h' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort_order, id', ['h' => $hostId]);
}

function hosts_offering(int $id, int $hostId): ?array
{
    return row('SELECT * FROM host_offerings WHERE id = :id AND host_id = :h', ['id' => $id, 'h' => $hostId]);
}

/** Lowest hourly-equivalent price a host offers: min of the hourly rate and each package. Null when nothing is priced. */
function hosts_from_rate(array $hp, ?array $offerings = null): ?float
{
    $best = null;
    if (!empty($hp['hourly_rate']) && (float)$hp['hourly_rate'] > 0) {
        $best = (float)$hp['hourly_rate'];
    }
    foreach ($offerings ?? hosts_offerings((int)$hp['user_id']) as $o) {
        if ((float)$o['duration_hours'] > 0) {
            $r = (float)$o['price'] / (float)$o['duration_hours'];
            $best = $best === null ? $r : min($best, $r);
        }
    }
    return $best === null ? null : round($best);
}

/**
 * Validate and save a host's packages. $rows: list of ['id'?, 'service_id'?, 'name', 'description', 'duration_hours', 'price'].
 * Rows with an empty name are skipped. Returns ['ok' => bool, 'errors' => [], 'saved' => int].
 */
function hosts_offerings_save(int $hostId, array $rows, bool $replaceAll = true): array
{
    $errors = [];
    $clean = [];
    $min = (float)setting('host_min_rate');
    $maxHours = (float)setting('host_booking_max_hours');
    $catalog = hosts_services_catalog();
    foreach (array_slice($rows, 0, 12) as $i => $r) {
        $name = mb_substr(trim((string)($r['name'] ?? '')), 0, 80);
        if ($name === '') {
            continue;
        }
        $n = $i + 1;
        $desc = mb_substr(trim((string)($r['description'] ?? '')), 0, 255);
        $hours = round((float)($r['duration_hours'] ?? 0) * 2) / 2;
        $price = (float)round((float)($r['price'] ?? 0));
        if (hosts_text_violation($name . ' ' . $desc) !== null) {
            $errors["offer_$n"] = 'Package ' . $n . ': ' . hosts_violation_message('This text');
            continue;
        }
        if (hosts_contact_violation($name . ' ' . $desc) !== null) {
            $errors["offer_$n"] = 'Package ' . $n . ': ' . hosts_contact_message('This text');
            continue;
        }
        if ($hours < 0.5 || $hours > $maxHours) {
            $errors["offer_$n"] = 'Package ' . $n . ': choose a length between 0.5 and ' . rtrim(rtrim(number_format($maxHours, 1), '0'), '.') . ' hours.';
            continue;
        }
        if ($price <= 0) {
            $errors["offer_$n"] = 'Package ' . $n . ': enter a price.';
            continue;
        }
        if ($min > 0 && $price / $hours < $min) {
            $errors["offer_$n"] = 'Package ' . $n . ': the price works out below the lowest hourly rate allowed (' . money($min) . ' an hour).';
            continue;
        }
        $sid = (int)($r['service_id'] ?? 0);
        $clean[] = ['id' => (int)($r['id'] ?? 0), 'service_id' => isset($catalog[$sid]) ? $sid : null, 'name' => $name, 'description' => $desc !== '' ? $desc : null, 'duration_hours' => $hours, 'price' => $price];
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'saved' => 0];
    }
    db_transaction(function () use ($hostId, $clean, $replaceAll) {
        $keep = [];
        foreach ($clean as $i => $r) {
            $data = ['service_id' => $r['service_id'], 'name' => $r['name'], 'description' => $r['description'], 'duration_hours' => $r['duration_hours'], 'price' => $r['price'], 'sort_order' => $i, 'is_active' => 1];
            if ($r['id'] > 0 && hosts_offering($r['id'], $hostId)) {
                update('host_offerings', $data, ['id' => $r['id'], 'host_id' => $hostId]);
                $keep[] = $r['id'];
            } else {
                $keep[] = insert('host_offerings', $data + ['host_id' => $hostId]);
            }
        }
        if ($replaceAll) {
            // Packages with past bookings are hidden instead of deleted so history keeps its label.
            $old = col('SELECT id FROM host_offerings WHERE host_id = :h' . ($keep ? ' AND id NOT IN (:keep)' : ''), ['h' => $hostId] + ($keep ? ['keep' => $keep] : []));
            foreach ($old as $oid) {
                $used = (int)val('SELECT COUNT(*) FROM host_booking_ext WHERE offering_id = :o', ['o' => $oid]);
                if ($used > 0) {
                    update('host_offerings', ['is_active' => 0], ['id' => $oid]);
                } else {
                    delete_rows('host_offerings', ['id' => $oid]);
                }
            }
        }
    });
    return ['ok' => true, 'errors' => [], 'saved' => count($clean)];
}

// ===========================================================================
// 4. Weekly availability
// ===========================================================================

/** "HH:MM" (or "24:00") to minutes from midnight, or null when invalid. */
function hosts_time_to_min($t): ?int
{
    if (!is_string($t) || !preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($t), $m)) {
        return null;
    }
    $h = (int)$m[1];
    $mi = (int)$m[2];
    if ($mi > 59 || $h > 24 || ($h === 24 && $mi > 0)) {
        return null;
    }
    return $h * 60 + $mi;
}

/** Minutes to "HH:MM". 1440 gives "24:00". */
function hosts_min_to_time(int $m): string
{
    if ($m === 1440) {
        return '24:00';
    }
    $m = (($m % 1440) + 1440) % 1440;
    return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
}

/** 24h minutes to a friendly 12h label: "6:30 pm". */
function hosts_time_label(int $m): string
{
    if ($m === 1440) {
        return '12:00 am';
    }
    $m = (($m % 1440) + 1440) % 1440;
    $h = intdiv($m, 60);
    $suffix = $h >= 12 ? 'pm' : 'am';
    $h12 = $h % 12 === 0 ? 12 : $h % 12;
    return $h12 . ':' . sprintf('%02d', $m % 60) . ' ' . $suffix;
}

/**
 * Clean a weekly availability array (the JSON format in the contract): keys mon..sun, each a list of ["HH:MM","HH:MM"].
 * A window whose end is not later than its start runs past midnight (["20:00","02:00"]). Overlapping windows are merged,
 * at most 4 per day are kept. Unknown or malformed entries are dropped.
 */
function hosts_weekly_normalize($input): array
{
    $out = array_fill_keys(HOSTS_DAYS, []);
    if (!is_array($input)) {
        return $out;
    }
    foreach (HOSTS_DAYS as $day) {
        $spans = [];
        foreach ((array)($input[$day] ?? []) as $w) {
            if (!is_array($w) || count($w) < 2) {
                continue;
            }
            $s = hosts_time_to_min($w[0] ?? null);
            $e = hosts_time_to_min($w[1] ?? null);
            if ($s === null || $e === null || $s >= 1440 || $s === $e) {
                continue;
            }
            if ($e <= $s) {
                $e += 1440;
            }
            $spans[] = [$s, $e];
        }
        usort($spans, fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($spans as [$s, $e]) {
            if ($merged && $s <= $merged[count($merged) - 1][1]) {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $e);
            } else {
                $merged[] = [$s, $e];
            }
        }
        foreach (array_slice($merged, 0, 4) as [$s, $e]) {
            $out[$day][] = [hosts_min_to_time($s), $e === 1440 ? '24:00' : hosts_min_to_time($e % 1440)];
        }
    }
    return $out;
}

/** The weekly schedule stored on a host profile, normalised (all days present). */
function hosts_weekly(array $hp): array
{
    $raw = $hp['weekly_availability'] ?? '';
    $decoded = is_array($raw) ? $raw : json_decode((string)$raw, true);
    return hosts_weekly_normalize($decoded);
}

/** Starting schedule for a new host: every day 10:00 to 20:00. */
function hosts_weekly_default(): array
{
    return array_fill_keys(HOSTS_DAYS, [['10:00', '20:00']]);
}

// ===========================================================================
// 5. Free slots and pricing
// ===========================================================================

/** Whole days from date $a to date $b (b - a), independent of time zones. */
function hosts_day_diff(string $a, string $b): int
{
    $pa = explode('-', substr($a, 0, 10));
    $pb = explode('-', substr($b, 0, 10));
    if (count($pa) !== 3 || count($pb) !== 3) {
        return 0;
    }
    return (int)round((gmmktime(0, 0, 0, (int)$pb[1], (int)$pb[2], (int)$pb[0]) - gmmktime(0, 0, 0, (int)$pa[1], (int)$pa[2], (int)$pa[0])) / 86400);
}

function hosts_valid_date(string $d): bool
{
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4));
}

/** Rules for slot computation from the settings. */
function hosts_slot_options(): array
{
    return [
        'tz' => date_default_timezone_get(),
        'min_notice_hours' => (float)setting('host_min_notice_hours'),
        'max_advance_days' => (int)setting('host_max_advance_days'),
        'step' => max(5, (int)setting('host_slot_step_minutes')),
        'buffer' => max(0, (int)setting('host_booking_buffer_minutes')),
    ];
}

/**
 * Pure slot calculator (no database): which start times on $date can fit a booking of $hours?
 *
 * @param array  $weekly   normalised weekly schedule (hosts_weekly_normalize)
 * @param string $date     Y-m-d, the day the booking starts
 * @param float  $hours    booking length
 * @param array  $blocked  list of blocked Y-m-d dates
 * @param array  $bookings list of ['date' => Y-m-d, 'start' => 'HH:MM[:SS]', 'hours' => float] that hold a slot
 * @param array  $o        now (timestamp), tz, min_notice_hours, max_advance_days, step (minutes), buffer (minutes)
 * @return array list of ['start' => 'HH:MM', 'end' => 'HH:MM', 'next_day' => bool, 'label' => '6:00 pm', 'ts' => int]
 */
function hosts_slots_compute(array $weekly, string $date, float $hours, array $blocked, array $bookings, array $o = []): array
{
    $o += hosts_slot_options();
    $now = (int)($o['now'] ?? time());
    if (!hosts_valid_date($date) || $hours <= 0) {
        return [];
    }
    try {
        $tz = new DateTimeZone((string)$o['tz']);
        $d0 = new DateTimeImmutable($date . ' 00:00:00', $tz);
        $today = (new DateTimeImmutable('@' . $now))->setTimezone($tz)->format('Y-m-d');
    } catch (Throwable $e) {
        return [];
    }
    $ahead = hosts_day_diff($today, $date);
    if ($ahead < 0 || $ahead > (int)$o['max_advance_days']) {
        return [];
    }
    $blockedSet = array_flip(array_map(fn($b) => substr((string)$b, 0, 10), $blocked));

    // Availability windows (minutes from midnight of $date) from yesterday, today and tomorrow.
    $spans = [];
    $blockedRanges = [];
    foreach ([-1, 0, 1] as $k) {
        $dk = $d0->modify(($k >= 0 ? '+' : '') . $k . ' day');
        $dkStr = $dk->format('Y-m-d');
        if (isset($blockedSet[$dkStr])) {
            $blockedRanges[] = [$k * 1440, ($k + 1) * 1440];
            continue;
        }
        $key = HOSTS_DAYS[(int)$dk->format('N') - 1];
        foreach ((array)($weekly[$key] ?? []) as $w) {
            $s = hosts_time_to_min($w[0] ?? null);
            $e = hosts_time_to_min($w[1] ?? null);
            if ($s === null || $e === null || $s >= 1440 || $s === $e) {
                continue;
            }
            if ($e <= $s) {
                $e += 1440;
            }
            $spans[] = [$s + $k * 1440, $e + $k * 1440];
        }
    }
    foreach ($blockedRanges as [$bs, $be]) {
        $next = [];
        foreach ($spans as [$s, $e]) {
            if ($e <= $bs || $s >= $be) {
                $next[] = [$s, $e];
                continue;
            }
            if ($s < $bs) {
                $next[] = [$s, $bs];
            }
            if ($e > $be) {
                $next[] = [$be, $e];
            }
        }
        $spans = $next;
    }
    usort($spans, fn($a, $b) => $a[0] <=> $b[0]);
    $merged = [];
    foreach ($spans as [$s, $e]) {
        if ($merged && $s <= $merged[count($merged) - 1][1]) {
            $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $e);
        } else {
            $merged[] = [$s, $e];
        }
    }
    if (!$merged) {
        return [];
    }

    // Time already taken, widened by the buffer on both sides.
    $buffer = (int)$o['buffer'];
    $taken = [];
    foreach ($bookings as $b) {
        $k = hosts_day_diff($date, (string)($b['date'] ?? ''));
        if ($k < -2 || $k > 1) {
            continue;
        }
        $sm = hosts_time_to_min((string)($b['start'] ?? ''));
        if ($sm === null) {
            continue;
        }
        $start = $k * 1440 + $sm;
        $taken[] = [$start - $buffer, $start + (int)round(((float)($b['hours'] ?? 0)) * 60) + $buffer];
    }

    $dur = (int)round($hours * 60);
    $step = max(5, (int)$o['step']);
    $earliest = $now + (int)round(((float)$o['min_notice_hours']) * 3600);
    $slots = [];
    for ($t = 0; $t < 1440; $t += $step) {
        $fits = false;
        foreach ($merged as [$s, $e]) {
            if ($t >= $s && $t + $dur <= $e) {
                $fits = true;
                break;
            }
        }
        if (!$fits) {
            continue;
        }
        $clash = false;
        foreach ($taken as [$ts, $te]) {
            if ($t < $te && $t + $dur > $ts) {
                $clash = true;
                break;
            }
        }
        if ($clash) {
            continue;
        }
        $slotTs = $d0->setTime(intdiv($t, 60), $t % 60)->getTimestamp();
        if ($slotTs < $earliest) {
            continue;
        }
        $end = $t + $dur;
        $slots[] = ['start' => hosts_min_to_time($t), 'end' => hosts_min_to_time($end % 1440), 'next_day' => $end > 1440, 'label' => hosts_time_label($t), 'end_label' => hosts_time_label($end % 1440), 'ts' => $slotTs];
    }
    return $slots;
}

/** Bookings that hold a slot for a host between two dates (inclusive). Unpaid bookings count while their payment hold lasts. */
function hosts_slot_bookings(int $hostId, string $from, string $to, $exclude = 0): array
{
    $hold = max(1, (int)setting('host_payment_hold_minutes'));
    $skip = array_map('intval', (array)$exclude) ?: [0];
    $r = rows(
        "SELECT id, booking_date AS `date`, TIME_FORMAT(start_time, '%H:%i') AS `start`, duration_hours AS hours FROM host_bookings
         WHERE host_id = :h AND booking_date BETWEEN :f AND :t AND id NOT IN (:x)
           AND (status IN ('pending','confirmed','completed','no_show') OR (status = 'awaiting_payment' AND created_at > DATE_SUB(NOW(), INTERVAL $hold MINUTE)))",
        ['h' => $hostId, 'f' => $from, 't' => $to, 'x' => $skip]
    );
    foreach ($r as &$b) {
        $b['hours'] = (float)$b['hours'];
    }
    return $r;
}

function hosts_blocked_dates(int $hostId, string $from, string $to): array
{
    return col('SELECT blocked_on FROM host_blocked_dates WHERE host_id = :h AND blocked_on BETWEEN :f AND :t', ['h' => $hostId, 'f' => $from, 't' => $to]);
}

/** Free start times for one host on one date. $excludeBookingId (an id or a list of ids) is ignored, for re-checks. */
function hosts_free_slots(int $hostId, string $date, float $hours, $excludeBookingId = 0, array $override = []): array
{
    $hp = $override ?: hosts_profile($hostId);
    if (!$hp || !hosts_valid_date($date)) {
        return [];
    }
    $from = date('Y-m-d', strtotime($date . ' -2 day'));
    $to = date('Y-m-d', strtotime($date . ' +1 day'));
    return hosts_slots_compute(
        hosts_weekly($hp), $date, $hours, hosts_blocked_dates($hostId, $from, $to), hosts_slot_bookings($hostId, $from, $to, $excludeBookingId), hosts_slot_options()
    );
}

/**
 * How many start times each day offers, for $days days starting at $from: [Y-m-d => count].
 * One database round trip per table, not one per day.
 */
function hosts_available_days(int $hostId, float $hours, ?string $from = null, int $days = 30, array $hp = []): array
{
    $hp = $hp ?: hosts_profile($hostId);
    $out = [];
    if (!$hp) {
        return $out;
    }
    $from = $from && hosts_valid_date($from) ? $from : date('Y-m-d');
    $days = max(1, min(120, $days));
    $to = date('Y-m-d', strtotime($from . ' +' . ($days + 1) . ' day'));
    $weekly = hosts_weekly($hp);
    $blocked = hosts_blocked_dates($hostId, date('Y-m-d', strtotime($from . ' -2 day')), $to);
    $bookings = hosts_slot_bookings($hostId, date('Y-m-d', strtotime($from . ' -2 day')), $to);
    $opts = hosts_slot_options();
    for ($i = 0; $i < $days; $i++) {
        $d = date('Y-m-d', strtotime($from . ' +' . $i . ' day'));
        $out[$d] = count(hosts_slots_compute($weekly, $d, $hours, $blocked, $bookings, $opts));
    }
    return $out;
}

/** Which of these hosts have at least one free slot on $date? Used by the directory's date filter. Returns host ids. */
function hosts_ids_available_on(array $hostMinHours, string $date): array
{
    $ids = array_map('intval', array_keys($hostMinHours));
    if (!$ids || !hosts_valid_date($date)) {
        return [];
    }
    $from = date('Y-m-d', strtotime($date . ' -2 day'));
    $to = date('Y-m-d', strtotime($date . ' +1 day'));
    $weekly = pairs('SELECT user_id, weekly_availability FROM host_profiles WHERE user_id IN (:ids)', ['ids' => $ids]);
    $blocked = [];
    foreach (rows('SELECT host_id, blocked_on FROM host_blocked_dates WHERE host_id IN (:ids) AND blocked_on BETWEEN :f AND :t', ['ids' => $ids, 'f' => $from, 't' => $to]) as $r) {
        $blocked[(int)$r['host_id']][] = $r['blocked_on'];
    }
    $hold = max(1, (int)setting('host_payment_hold_minutes'));
    $bookings = [];
    foreach (rows(
        "SELECT host_id, booking_date AS `date`, TIME_FORMAT(start_time, '%H:%i') AS `start`, duration_hours AS hours FROM host_bookings
         WHERE host_id IN (:ids) AND booking_date BETWEEN :f AND :t
           AND (status IN ('pending','confirmed','completed','no_show') OR (status = 'awaiting_payment' AND created_at > DATE_SUB(NOW(), INTERVAL $hold MINUTE)))",
        ['ids' => $ids, 'f' => $from, 't' => $to]
    ) as $r) {
        $bookings[(int)$r['host_id']][] = ['date' => $r['date'], 'start' => $r['start'], 'hours' => (float)$r['hours']];
    }
    $opts = hosts_slot_options();
    $ok = [];
    foreach ($ids as $id) {
        $w = hosts_weekly(['weekly_availability' => $weekly[$id] ?? '']);
        $hrs = max(0.5, (float)$hostMinHours[$id]);
        if (hosts_slots_compute($w, $date, $hrs, $blocked[$id] ?? [], $bookings[$id] ?? [], $opts)) {
            $ok[] = $id;
        }
    }
    return $ok;
}

/**
 * Price for a custom length: the hourly rate, with the half-day (4 h) and full-day (8 h) rates applied when they are cheaper.
 * Returns 0 when the host has no hourly rate.
 */
function hosts_price_for_hours(array $hp, float $hours): float
{
    $hourly = (float)($hp['hourly_rate'] ?? 0);
    if ($hourly <= 0 || $hours <= 0) {
        return 0.0;
    }
    $best = $hourly * $hours;
    $half = (float)($hp['half_day_rate'] ?? 0);
    $full = (float)($hp['full_day_rate'] ?? 0);
    if ($half > 0 && $hours >= 4) {
        $best = min($best, $half + $hourly * ($hours - 4));
    }
    if ($full > 0 && $hours >= 8) {
        $best = min($best, $full + $hourly * ($hours - 8));
    }
    return (float)round($best);
}

/**
 * Price breakdown for a booking, from the host's rates and the platform settings (never from the client).
 *
 * @param array      $hp       host profile (hosts_profile)
 * @param array|null $offering a package row, or null for custom hours
 * @param float      $hours    length (ignored when a package is given: the package length is used)
 * @param bool       $travel   add the host's travel fee
 * @param array      $o        optional overrides for tests: deposit_pct, commission_pct
 * @return array hours, base, travel_fee, total, deposit_pct, deposit, balance, commission_pct, commission, host_earning
 */
function hosts_quote(array $hp, ?array $offering, float $hours, bool $travel = false, array $o = []): array
{
    if ($offering) {
        $hours = (float)$offering['duration_hours'];
        $base = (float)$offering['price'];
    } else {
        $base = hosts_price_for_hours($hp, $hours);
    }
    $fee = ($travel && !empty($hp['travel_available'])) ? (float)($hp['travel_fee'] ?? 0) : 0.0;
    $total = (float)round($base + $fee);
    $depositPct = (float)($o['deposit_pct'] ?? setting('booking_deposit_pct'));
    $commPct = (float)($o['commission_pct'] ?? setting('host_commission_pct'));
    $commission = round($total * $commPct / 100, 2);
    // The deposit is never smaller than the commission, so the platform has what it is owed before the meeting.
    $deposit = min($total, max((float)ceil($total * $depositPct / 100), (float)ceil($commission)));
    if ($total <= 0) {
        $deposit = 0.0;
    }
    return [
        'hours' => $hours, 'base' => $base, 'travel_fee' => $fee, 'total' => $total,
        'deposit_pct' => $depositPct, 'deposit' => $deposit, 'balance' => max(0.0, $total - $deposit),
        'commission_pct' => $commPct, 'commission' => $commission, 'host_earning' => round($total - $commission, 2),
    ];
}

/** "2 hours", "1.5 hours", "30 minutes". */
function hosts_hours_label(float $hours): string
{
    if ($hours < 1) {
        return (int)round($hours * 60) . ' minutes';
    }
    $s = rtrim(rtrim(number_format($hours, 1), '0'), '.');
    return $s . ($hours == 1.0 ? ' hour' : ' hours');
}

/** Start timestamp of a booking row. */
function hosts_booking_start_ts(array $b): int
{
    return (int)strtotime($b['booking_date'] . ' ' . $b['start_time']);
}

function hosts_booking_end_ts(array $b): int
{
    return hosts_booking_start_ts($b) + (int)round((float)$b['duration_hours'] * 3600);
}

// ===========================================================================
// 6. Booking creation and the payment hook
// ===========================================================================

const HOSTS_BOOKING_SELECT = 'b.*, e.offering_id, e.offering_name, e.area_id, e.travel_fee, e.base_amount, e.accept_by, e.paid_at, e.confirmed_at, e.completed_at,
    e.closed_at, e.reminder_sent_at, e.client_confirmed_at, e.refund_pct, e.refund_amount, e.refund_status, e.settled_amount, e.settled_at';

/** A booking with its extension columns, or null. */
function hosts_booking_get(int $id): ?array
{
    return row('SELECT ' . HOSTS_BOOKING_SELECT . ' FROM host_bookings b LEFT JOIN host_booking_ext e ON e.booking_id = b.id WHERE b.id = :id', ['id' => $id]);
}

/** Add a line to a booking's timeline. $actor: ['id' => int, 'role' => string] or null for the system. */
function hosts_event(int $bookingId, string $event, ?array $actor = null, string $note = ''): void
{
    insert('host_booking_events', [
        'booking_id' => $bookingId, 'actor_id' => $actor['id'] ?? null, 'actor_role' => $actor['role'] ?? 'system',
        'event' => $event, 'note' => $note !== '' ? mb_substr($note, 0, 255) : null,
    ]);
}

/** Display names for both sides of a booking: ['host' => ['id','name','email'], 'client' => [...]]. */
function hosts_booking_parties(array $b): array
{
    $one = function (int $id, bool $host) {
        $r = row('SELECT u.id, u.email, p.display_name, hp.display_name AS host_name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
                  LEFT JOIN host_profiles hp ON hp.user_id = u.id WHERE u.id = :id', ['id' => $id]) ?: ['id' => $id, 'email' => '', 'display_name' => '', 'host_name' => ''];
        $name = trim((string)($host ? ($r['host_name'] ?: $r['display_name']) : $r['display_name'])) ?: 'Member';
        return ['id' => (int)$r['id'], 'email' => (string)$r['email'], 'name' => $name, 'first_name' => explode(' ', $name)[0]];
    };
    return ['host' => $one((int)$b['host_id'], true), 'client' => $one((int)$b['client_id'], false)];
}

/** Everything a booking e-mail needs as label => value. */
function hosts_booking_summary(array $b, bool $forHost = false): array
{
    if (!array_key_exists('offering_name', $b) && isset($b['id'])) {
        $b = hosts_booking_get((int)$b['id']) ?: $b;
    }
    $start = hosts_booking_start_ts($b);
    $end = hosts_booking_end_ts($b);
    $rows = [
        'Booking' => '#' . (int)$b['id'],
        'Service' => (string)($b['offering_name'] ?? '') !== '' ? (string)$b['offering_name'] : 'Custom time',
        'Date' => date('l j F Y', $start),
        'Time' => date('g:i a', $start) . ' to ' . date('g:i a', $end) . ' (' . hosts_hours_label((float)$b['duration_hours']) . ')',
        'Meeting place' => (string)($b['location_note'] ?? ''),
        'Total' => money($b['total_amount']),
        'Deposit' => money($b['deposit_amount']),
    ];
    if ($forHost) {
        $rows['Your earning'] = money($b['host_earning']);
    } else {
        $rows['Balance at the meeting'] = money(max(0, (float)$b['total_amount'] - (float)$b['deposit_amount']));
    }
    return array_filter($rows, fn($v) => $v !== '');
}

/**
 * In-app notification plus a booking e-mail. Hosts can switch the e-mail off in their settings.
 * $type: 'booking' for new requests, 'booking_update' for changes, 'review' for reviews.
 */
function hosts_booking_notify(array $b, int $toUserId, string $type, string $title, string $body, string $url, string $mailIntro = '', string $mailLabel = 'View booking', ?int $senderId = null): void
{
    try {
        notify($toUserId, $type, $title, $body, $url, $senderId, ['booking_id' => (int)$b['id']]);
        if ($mailIntro === '') {
            return;
        }
        $isHost = $toUserId === (int)$b['host_id'];
        if ($isHost) {
            $ext = hosts_ext($toUserId);
            $flag = $type === 'review' ? 'notify_email_reviews' : ($title === 'Booking reminder' ? 'notify_email_reminders' : 'notify_email_bookings');
            if (!(int)$ext[$flag]) {
                return;
            }
        }
        $parties = hosts_booking_parties($b);
        $to = $isHost ? $parties['host'] : $parties['client'];
        if ($to['email'] === '') {
            return;
        }
        send_template_mail($to['email'], $title, 'booking', [
            'title' => $title, 'name' => $to['first_name'], 'intro' => $mailIntro, 'details' => hosts_booking_summary($b, $isHost),
            'url' => abs_url($url), 'label' => $mailLabel,
        ]);
    } catch (Throwable $e) {
        app_log('error', 'hosts_booking_notify failed: ' . $e->getMessage());
    }
}

/** Tell every active admin about something that needs a decision (application, refund). */
function hosts_notify_admins(string $title, string $body, string $url, string $type = 'system'): void
{
    foreach (col("SELECT id FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id LIMIT 20") as $aid) {
        notify((int)$aid, $type, $title, $body, $url);
    }
}

/**
 * Create a booking and its deposit payment.
 *
 * $in: offering_id (0 = custom hours), hours, date (Y-m-d), start (HH:MM), venue, area (location id or 'other'), note, accept (terms ticked).
 * Everything is validated here; the slot is re-checked while the host's profile row is locked, so two clients can never take the same time.
 *
 * @return array ['ok' => bool, 'error' => string, 'errors' => [field => message], 'booking_id' => int, 'payment_id' => int]
 */
function hosts_booking_create(array $client, int $hostId, array $in): array
{
    $fail = fn(array $errors, string $general = '') => ['ok' => false, 'error' => $general !== '' ? $general : (string)reset($errors), 'errors' => $errors, 'booking_id' => 0, 'payment_id' => 0];
    $cid = (int)($client['id'] ?? 0);

    if (!hosts_booking_enabled()) {
        return $fail([], 'Bookings are switched off right now.');
    }
    if (($client['role'] ?? '') !== 'user' || ($client['status'] ?? 'active') !== 'active') {
        return $fail([], 'Only member accounts can book a Host.');
    }
    $hp = hosts_profile($hostId);
    if (!$hp || !hosts_is_public($hp) || (int)$hp['bookings_paused'] === 1 || $hostId === $cid || users_blocked($cid, $hostId)) {
        return $fail([], 'This Host is not taking bookings right now.');
    }
    $unpaid = (int)val("SELECT COUNT(*) FROM host_bookings WHERE client_id = :c AND host_id <> :h AND status = 'awaiting_payment' AND created_at > DATE_SUB(NOW(), INTERVAL :m MINUTE)",
        ['c' => $cid, 'h' => $hostId, 'm' => max(1, (int)setting('host_payment_hold_minutes'))]);
    if ($unpaid >= max(1, (int)setting('host_max_unpaid_bookings'))) {
        return $fail([], 'You have unpaid bookings waiting. Pay or cancel one before making another.');
    }

    $errors = [];
    // Service or custom hours
    $offering = null;
    $hours = 0.0;
    $oid = (int)($in['offering_id'] ?? 0);
    if ($oid > 0) {
        $offering = hosts_offering($oid, $hostId);
        if (!$offering || !(int)$offering['is_active']) {
            $errors['offering'] = 'Choose one of the services this Host offers.';
        } else {
            $hours = (float)$offering['duration_hours'];
        }
    } else {
        $maxHours = min((float)setting('host_booking_max_hours'), $hp['max_hours'] !== null ? (float)$hp['max_hours'] : 24.0);
        $minHours = max(0.5, (float)$hp['min_hours']);
        $hours = round((float)($in['hours'] ?? 0) * 2) / 2;
        if (!(int)$hp['allow_custom_hours'] || (float)$hp['hourly_rate'] <= 0) {
            $errors['offering'] = 'Choose one of the services this Host offers.';
        } elseif ($hours < $minHours || $hours > $maxHours) {
            $errors['hours'] = 'Choose a length between ' . hosts_hours_label($minHours) . ' and ' . hosts_hours_label($maxHours) . '.';
        }
    }

    // Date and time
    $date = trim((string)($in['date'] ?? ''));
    $start = trim((string)($in['start'] ?? ''));
    if (!hosts_valid_date($date)) {
        $errors['date'] = 'Choose a date.';
    }
    if (hosts_time_to_min($start) === null || hosts_time_to_min($start) >= 1440) {
        $errors['start'] = 'Choose a start time.';
    } else {
        $start = hosts_min_to_time((int)hosts_time_to_min($start));
    }

    // Meeting place
    $venue = trim((string)($in['venue'] ?? ''));
    if (mb_strlen($venue) < 3 || mb_strlen($venue) > 120) {
        $errors['venue'] = 'Enter the venue name (3 to 120 characters), for example a restaurant, café or event venue.';
    } elseif (hosts_private_place_violation($venue) !== null) {
        $errors['venue'] = 'Choose a public place such as a restaurant, café, hotel lobby or event venue. Private homes and rooms are not allowed.';
    } elseif (hosts_text_violation($venue) !== null) {
        $errors['venue'] = hosts_violation_message('The venue');
    }
    $areaIds = hosts_ids($hp['service_area_ids'] ?? '');
    $areaIn = $in['area'] ?? '';
    $travel = false;
    $areaId = null;
    $areaName = '';
    if ($areaIn === 'other') {
        if (empty($hp['travel_available'])) {
            $errors['area'] = 'This Host only meets in the areas listed on their profile.';
        } else {
            $travel = true;
            $areaName = 'Other area';
        }
    } elseif ((int)$areaIn > 0) {
        $areaId = (int)$areaIn;
        if (!in_array($areaId, $areaIds, true)) {
            $errors['area'] = 'Choose one of the areas this Host works in.';
        } else {
            $areaName = (string)(hosts_locations_by_ids([$areaId])[$areaId]['name'] ?? '');
        }
    } elseif ($areaIds) {
        $errors['area'] = 'Choose where you would like to meet.';
    }
    $note = mb_substr(trim((string)($in['note'] ?? '')), 0, 500);
    if ($note !== '' && hosts_text_violation($note) !== null) {
        $errors['note'] = hosts_violation_message('Your note');
    }
    if (empty($in['accept'])) {
        $errors['terms'] = 'Please confirm that you have read the Host rules and the cancellation policy.';
    }
    if ($errors) {
        return $fail($errors);
    }

    try {
        return db_transaction(function () use ($client, $cid, $hostId, $hp, $offering, $hours, $date, $start, $venue, $areaId, $areaName, $travel, $note, $fail) {
            q('SELECT user_id FROM host_profiles WHERE user_id = :h FOR UPDATE', ['h' => $hostId]); // serialises bookings for this host
            // A client holds one unpaid booking per host: a new attempt replaces the old one (its slot counts as free).
            $olds = array_map('intval', col("SELECT id FROM host_bookings WHERE client_id = :c AND host_id = :h AND status = 'awaiting_payment'", ['c' => $cid, 'h' => $hostId]));
            $free = array_column(hosts_free_slots($hostId, $date, $hours, $olds, $hp), 'start');
            if (!in_array($start, $free, true)) {
                return $fail(['start' => 'That time is no longer available. Please pick another slot.']);
            }
            foreach ($olds as $old) {
                hosts_booking_transition($old, 'cancelled', ['id' => $cid, 'role' => 'user'], ['reason' => 'Replaced by a new request']);
            }
            $quote = hosts_quote($hp, $offering, $hours, $travel);
            if ($quote['total'] <= 0) {
                return $fail([], 'This Host has not set a price for that service.');
            }
            $label = $offering ? (string)$offering['name'] : 'Custom time';
            $id = insert('host_bookings', [
                'host_id' => $hostId, 'client_id' => $cid, 'booking_date' => $date, 'start_time' => $start . ':00', 'duration_hours' => $quote['hours'],
                'service_ids' => $offering && $offering['service_id'] ? (string)$offering['service_id'] : null,
                'location_note' => mb_substr($venue . ($areaName !== '' ? ' (' . $areaName . ')' : ''), 0, 200), 'client_note' => $note !== '' ? $note : null,
                'total_amount' => $quote['total'], 'deposit_amount' => $quote['deposit'], 'commission_pct' => $quote['commission_pct'],
                'commission_amount' => $quote['commission'], 'host_earning' => $quote['host_earning'], 'status' => 'awaiting_payment',
            ]);
            insert('host_booking_ext', [
                'booking_id' => $id, 'offering_id' => $offering['id'] ?? null, 'offering_name' => mb_substr($label, 0, 80), 'area_id' => $areaId,
                'travel_fee' => $quote['travel_fee'], 'base_amount' => $quote['base'],
            ]);
            hosts_event($id, 'created', ['id' => $cid, 'role' => 'user']);
            $payment = payment_create($cid, 'booking', 'mpesa', (float)$quote['deposit'],
                'Booking deposit - ' . $hp['display_name'] . ' on ' . date('j M Y', strtotime($date)),
                ['booking_id' => $id, 'host_id' => $hostId, 'return_url' => '/bookings/' . $id]);
            update('host_bookings', ['payment_id' => (int)$payment['id']], ['id' => $id]);
            return ['ok' => true, 'error' => '', 'errors' => [], 'booking_id' => $id, 'payment_id' => (int)$payment['id']];
        });
    } catch (Throwable $e) {
        app_log('error', 'hosts_booking_create failed: ' . $e->getMessage());
        return $fail([], 'We could not create the booking. Please try again.');
    }
}

/**
 * Called by payment_complete() (includes/plans.php) when a booking deposit is paid.
 * Moves the booking to "pending" (host must accept) or "confirmed" (instant booking), tells both people.
 * Idempotent: a second call for the same payment does nothing.
 */
function booking_payment_completed(array $payment, array $meta): void
{
    $bid = (int)($meta['booking_id'] ?? 0);
    $b = $bid > 0 ? row('SELECT * FROM host_bookings WHERE id = :id FOR UPDATE', ['id' => $bid]) : null;
    if (!$b) {
        app_log('error', 'booking_payment_completed: booking not found', ['payment' => $payment['id'] ?? null, 'meta' => $meta]);
        return;
    }
    if ((int)$b['client_id'] !== (int)$payment['user_id']) {
        app_log('error', 'booking_payment_completed: payment owner does not match booking', ['payment' => $payment['id'] ?? null, 'booking' => $bid]);
        return;
    }
    q('INSERT IGNORE INTO host_booking_ext (booking_id) VALUES (:b)', ['b' => $bid]);
    $ext = row('SELECT * FROM host_booking_ext WHERE booking_id = :b', ['b' => $bid]);
    if ($ext['paid_at'] !== null) {
        return; // already processed
    }
    $paymentId = (int)$payment['id'];
    $client = ['id' => (int)$b['client_id'], 'role' => 'user'];
    $metaHostId = (int)($meta['host_id'] ?? 0);
    $paidAmount = round((float)$payment['amount'], 2);
    $expectedAmount = round((float)$b['deposit_amount'], 2);
    if (($metaHostId > 0 && $metaHostId !== (int)$b['host_id']) || abs($paidAmount - $expectedAmount) > 0.5) {
        // Never confirm a booking against a payment for another Host or the wrong deposit amount.
        // The gateway has already confirmed receipt, so record the actual amount as a refund due
        // and close the booking rather than silently granting the service for a partial payment.
        app_log('error', 'booking_payment_completed: payment metadata or amount does not match booking', [
            'payment' => $paymentId, 'booking' => $bid, 'paid' => $paidAmount,
            'deposit' => $expectedAmount, 'payment_host' => $metaHostId, 'booking_host' => (int)$b['host_id'],
        ]);
        update('host_bookings', ['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancel_reason' => 'Payment amount or Host did not match the booking'], ['id' => $bid]);
        update('host_booking_ext', ['paid_at' => now(), 'closed_at' => now()], ['booking_id' => $bid]);
        hosts_refund_record($b, $paidAmount, 100.0, 'Payment did not match the booking deposit or Host');
        hosts_event($bid, 'paid', $client, 'Payment received but held for refund because the amount or Host did not match');
        hosts_booking_notify($b, (int)$b['client_id'], 'booking_update', 'Payment needs review',
            'We received your payment, but it did not match the booking details. The booking was not confirmed and the payment has been marked for refund.',
            '/bookings/' . $bid, '', 'View booking');
        hosts_notify_admins('Booking payment mismatch: #' . $bid,
            'Payment #' . $paymentId . ' for ' . money($paidAmount) . ' did not match the expected deposit of ' . money($expectedAmount) . '.', '/admin/payments');
        return;
    }

    if ($b['status'] === 'awaiting_payment') {
        $hp = hosts_profile((int)$b['host_id']);
        $instant = $hp && (int)$hp['instant_booking'] === 1 && (bool)setting('host_instant_booking_enabled');
        $startTs = hosts_booking_start_ts($b);
        if ($instant) {
            update('host_bookings', ['status' => 'confirmed', 'payment_id' => $paymentId], ['id' => $bid]);
            update('host_booking_ext', ['paid_at' => now(), 'confirmed_at' => now()], ['booking_id' => $bid]);
        } else {
            $deadline = min(time() + max(1, (int)setting('host_accept_hours')) * 3600, $startTs - 3600);
            $deadline = max($deadline, time() + 1800);
            update('host_bookings', ['status' => 'pending', 'payment_id' => $paymentId], ['id' => $bid]);
            update('host_booking_ext', ['paid_at' => now(), 'accept_by' => date('Y-m-d H:i:s', $deadline)], ['booking_id' => $bid]);
        }
        hosts_event($bid, 'paid', $client, 'Deposit ' . money($payment['amount']) . ' received');
        $full = hosts_booking_get($bid);
        $names = hosts_booking_parties($full);
        if ($instant) {
            hosts_event($bid, 'accepted', null, 'Instant booking');
            hosts_booking_notify($full, (int)$b['host_id'], 'booking', 'New confirmed booking', $names['client']['first_name'] . ' booked ' . date('D j M, g:i a', $startTs) . '.', '/host/bookings/' . $bid,
                $names['client']['first_name'] . ' booked you and the deposit is paid. The booking is confirmed because you have instant booking on.', 'View booking', (int)$b['client_id']);
            hosts_booking_notify($full, (int)$b['client_id'], 'booking_update', 'Booking confirmed', $names['host']['name'] . ' on ' . date('D j M, g:i a', $startTs) . '.', '/bookings/' . $bid,
                'Your deposit is paid and your booking with ' . $names['host']['name'] . ' is confirmed.', 'View booking', (int)$b['host_id']);
        } else {
            hosts_booking_notify($full, (int)$b['host_id'], 'booking', 'New booking request', $names['client']['first_name'] . ' would like to book ' . date('D j M, g:i a', $startTs) . '. Please answer before ' . date('D j M, g:i a', strtotime((string)$full['accept_by'])) . '.', '/host/bookings/' . $bid,
                $names['client']['first_name'] . ' has paid the deposit and is waiting for your answer. Accept or decline the request in your dashboard.', 'Answer the request', (int)$b['client_id']);
            hosts_booking_notify($full, (int)$b['client_id'], 'booking_update', 'Request sent to ' . $names['host']['name'], 'Your deposit is paid. We will tell you as soon as ' . $names['host']['name'] . ' answers.', '/bookings/' . $bid,
                'Your deposit is paid and your request has been sent to ' . $names['host']['name'] . '. You will get a message as soon as they answer. If they decline or do not answer in time, your deposit is refunded.', 'View booking', (int)$b['host_id']);
        }
        return;
    }

    if ($b['status'] === 'cancelled' && (int)$b['payment_id'] === $paymentId) {
        // The deposit arrived after the unpaid hold ran out. Re-open the booking if the time is still free.
        $hp = hosts_profile((int)$b['host_id']);
        $free = $hp ? array_column(hosts_free_slots((int)$b['host_id'], (string)$b['booking_date'], (float)$b['duration_hours'], $bid, $hp), 'start') : [];
        if ($hp && hosts_is_public($hp) && in_array(substr((string)$b['start_time'], 0, 5), $free, true)) {
            update('host_bookings', ['status' => 'pending', 'cancelled_by' => null, 'cancel_reason' => null], ['id' => $bid]);
            update('host_booking_ext', ['paid_at' => now(), 'closed_at' => null, 'refund_status' => 'none', 'refund_amount' => 0, 'accept_by' => date('Y-m-d H:i:s', time() + max(1, (int)setting('host_accept_hours')) * 3600)], ['booking_id' => $bid]);
            hosts_event($bid, 'paid', $client, 'Deposit received after the hold ran out; booking re-opened');
            $full = hosts_booking_get($bid);
            $names = hosts_booking_parties($full);
            hosts_booking_notify($full, (int)$b['host_id'], 'booking', 'New booking request', $names['client']['first_name'] . ' would like to book ' . date('D j M, g:i a', hosts_booking_start_ts($b)) . '.', '/host/bookings/' . $bid,
                $names['client']['first_name'] . ' has paid the deposit and is waiting for your answer.', 'Answer the request', (int)$b['client_id']);
            hosts_booking_notify($full, (int)$b['client_id'], 'booking_update', 'Request sent to ' . $names['host']['name'], 'Your deposit is paid and your request is with the Host.', '/bookings/' . $bid, '', 'View booking');
        } else {
            update('host_booking_ext', ['paid_at' => now()], ['booking_id' => $bid]);
            hosts_event($bid, 'paid', $client, 'Deposit received after the booking was cancelled');
            hosts_refund_record($b, (float)$payment['amount'], 100.0, 'Deposit received after the payment hold ran out and the time is taken');
            hosts_booking_notify($b, (int)$b['client_id'], 'booking_update', 'Your deposit will be refunded', 'We could not keep your time slot because the payment arrived late. Your deposit of ' . money($payment['amount']) . ' will be refunded.', '/bookings/' . $bid,
                'Your payment reached us after the time slot was released and the slot has since been taken. Your deposit of ' . money($payment['amount']) . ' will be refunded by our team.', 'View booking');
        }
        return;
    }
    // Any other state (for example a second payment for a booking already paid): just remember we saw it.
    update('host_booking_ext', ['paid_at' => now()], ['booking_id' => $bid]);
    hosts_event($bid, 'paid', $client, 'Extra payment received; contact support if it was a mistake');
    app_log('error', 'booking_payment_completed: booking ' . $bid . ' was ' . $b['status'] . ' when payment ' . $paymentId . ' arrived');
}

/**
 * Get a payment id the client can use to pay the deposit for an unpaid booking (re-using the open payment when there is one).
 * Extends the hold when the time is still free. @return array ['ok', 'error', 'payment_id']
 */
function hosts_booking_repay(int $bookingId, int $clientId): array
{
    return db_transaction(function () use ($bookingId, $clientId) {
        $b = row('SELECT * FROM host_bookings WHERE id = :id AND client_id = :c FOR UPDATE', ['id' => $bookingId, 'c' => $clientId]);
        if (!$b || $b['status'] !== 'awaiting_payment') {
            return ['ok' => false, 'error' => 'This booking is not waiting for a payment.', 'payment_id' => 0];
        }
        $hold = max(1, (int)setting('host_payment_hold_minutes'));
        if (strtotime((string)$b['created_at']) < time() - $hold * 60) {
            $free = array_column(hosts_free_slots((int)$b['host_id'], (string)$b['booking_date'], (float)$b['duration_hours'], $bookingId), 'start');
            if (!in_array(substr((string)$b['start_time'], 0, 5), $free, true)) {
                hosts_booking_transition($bookingId, 'cancelled', ['id' => 0, 'role' => 'system'], ['reason' => 'Payment not completed in time']);
                return ['ok' => false, 'error' => 'That time slot has been released. Please choose a new time.', 'payment_id' => 0];
            }
            q('UPDATE host_bookings SET created_at = NOW() WHERE id = :id', ['id' => $bookingId]);
        }
        $pay = $b['payment_id'] ? row('SELECT * FROM payments WHERE id = :id AND user_id = :u', ['id' => $b['payment_id'], 'u' => $clientId]) : null;
        if ($pay && $pay['status'] === 'pending') {
            return ['ok' => true, 'error' => '', 'payment_id' => (int)$pay['id']];
        }
        if ($pay && $pay['status'] === 'completed') {
            return ['ok' => false, 'error' => 'This deposit is already paid.', 'payment_id' => 0];
        }
        $host = hosts_profile((int)$b['host_id']);
        $p = payment_create($clientId, 'booking', 'mpesa', (float)$b['deposit_amount'], 'Booking deposit - ' . ($host['display_name'] ?? 'Host') . ' on ' . date('j M Y', strtotime((string)$b['booking_date'])),
            ['booking_id' => $bookingId, 'host_id' => (int)$b['host_id'], 'return_url' => '/bookings/' . $bookingId]);
        update('host_bookings', ['payment_id' => (int)$p['id']], ['id' => $bookingId]);
        return ['ok' => true, 'error' => '', 'payment_id' => (int)$p['id']];
    });
}

// ===========================================================================
// 7. Booking state machine, refunds, housekeeping
// ===========================================================================

function hosts_booking_status_label(string $s): string
{
    return HOSTS_BOOKING_STATUS[$s] ?? ucfirst(str_replace('_', ' ', $s));
}

/** Badge class (from the design system) for a booking status. */
function hosts_booking_badge(string $s): string
{
    return ['awaiting_payment' => 'badge-warning', 'pending' => 'badge-warning', 'confirmed' => 'badge-success', 'completed' => 'badge-info',
        'cancelled' => 'badge-danger', 'declined' => 'badge-danger', 'no_show' => 'badge-danger'][$s] ?? '';
}

/**
 * What the client would get back if the booking were cancelled now.
 * $by: client | host | admin | system. Returns ['pct' => float, 'amount' => float, 'hours' => float, 'label' => string].
 */
function hosts_refund_quote(array $b, string $by = 'client', ?int $now = null): array
{
    $now = $now ?? time();
    $deposit = (float)$b['deposit_amount'];
    $hours = (hosts_booking_start_ts($b) - $now) / 3600;
    $paid = !in_array($b['status'], ['awaiting_payment'], true);
    if (!$paid) {
        return ['pct' => 0.0, 'amount' => 0.0, 'hours' => $hours, 'label' => 'Nothing has been paid yet.'];
    }
    if ($by !== 'client' || $b['status'] === 'pending') {
        $pct = 100.0;
        $label = $by === 'client' ? 'The Host has not accepted yet, so your whole deposit is refunded.' : 'Your whole deposit is refunded.';
    } else {
        $full = (int)setting('host_cancel_full_hours');
        $part = (int)setting('host_cancel_partial_hours');
        $ppct = (float)setting('host_cancel_partial_pct');
        if ($hours >= $full) {
            $pct = 100.0;
            $label = 'More than ' . $full . ' hours before the start: your whole deposit is refunded.';
        } elseif ($hours >= $part && $ppct > 0) {
            $pct = $ppct;
            $label = 'Between ' . $part . ' and ' . $full . ' hours before the start: ' . rtrim(rtrim(number_format($ppct, 1), '0'), '.') . '% of the deposit is refunded.';
        } else {
            $pct = 0.0;
            $label = 'Less than ' . ($ppct > 0 ? $part : $full) . ' hours before the start: the deposit is not refunded.';
        }
    }
    return ['pct' => $pct, 'amount' => round($deposit * $pct / 100, 2), 'hours' => $hours, 'label' => $label];
}

/** Record that money is owed back to the client. The refund itself is paid by an admin (see payments.admin_notes and host_booking_ext.refund_status). */
function hosts_refund_record(array $b, float $amount, float $pct, string $why): void
{
    if ($amount <= 0) {
        update('host_booking_ext', ['refund_pct' => $pct, 'refund_amount' => 0, 'refund_status' => 'none'], ['booking_id' => (int)$b['id']]);
        return;
    }
    update('host_booking_ext', ['refund_pct' => $pct, 'refund_amount' => $amount, 'refund_status' => 'due'], ['booking_id' => (int)$b['id']]);
    if (!empty($b['payment_id'])) {
        q('UPDATE payments SET admin_notes = :n WHERE id = :id', ['n' => mb_substr('REFUND DUE ' . money($amount) . ' - booking #' . (int)$b['id'] . ' - ' . $why, 0, 255), 'id' => (int)$b['payment_id']]);
    }
    hosts_notify_admins('Refund due: booking #' . (int)$b['id'], money($amount) . ' to return to the client. ' . $why, '/admin/payments');
}

/**
 * Settle a finished booking: fix the commission on what the platform actually keeps and record what is owed to the host.
 * $base is the money the commission applies to: the full price for a completed booking, the kept deposit for a no-show or late cancellation.
 */
function hosts_settle(array $b, float $base, bool $completed): void
{
    $id = (int)$b['id'];
    $pct = (float)$b['commission_pct'];
    if ($base <= 0) {
        update('host_bookings', ['commission_amount' => 0, 'host_earning' => 0], ['id' => $id]);
        update('host_booking_ext', ['settled_amount' => 0, 'settled_at' => now()], ['booking_id' => $id]);
        return;
    }
    $commission = round($base * $pct / 100, 2);
    $earning = round($base - $commission, 2);
    $held = $completed ? round((float)$b['deposit_amount'] - $commission, 2) : $earning; // what the platform pays out of money it holds
    update('host_bookings', ['commission_amount' => $commission, 'host_earning' => $earning], ['id' => $id]);
    update('host_booking_ext', ['settled_amount' => $held, 'settled_at' => now()], ['booking_id' => $id]);
    q('UPDATE host_profiles SET total_earned = total_earned + :e, total_bookings = total_bookings + :c WHERE user_id = :h', ['e' => $earning, 'c' => $completed ? 1 : 0, 'h' => (int)$b['host_id']]);
}

/**
 * Move a booking to a new status. The one place status changes happen.
 *
 * @param int    $id     booking id
 * @param string $to     confirmed | declined | cancelled | completed | no_show
 * @param array  $actor  ['id' => int, 'role' => 'user'|'host'|'admin'|'system']
 * @param array  $opts   reason (text, max 255)
 * @return array ['ok' => bool, 'error' => string, 'status' => string, 'message' => string, 'refund' => ['pct','amount','label']|null]
 */
function hosts_booking_transition(int $id, string $to, array $actor, array $opts = []): array
{
    $reason = mb_substr(trim((string)($opts['reason'] ?? '')), 0, 255);
    $no = fn(string $m) => ['ok' => false, 'error' => $m, 'status' => '', 'message' => '', 'refund' => null];
    if ($reason !== '' && hosts_text_violation($reason) !== null) {
        return $no(hosts_violation_message('Your reason'));
    }
    if ($reason !== '' && hosts_contact_violation($reason) !== null) {
        return $no(hosts_contact_message('Your reason'));
    }
    $role = (string)($actor['role'] ?? '');
    $aid = (int)($actor['id'] ?? 0);

    $result = db_transaction(function () use ($id, $to, $actor, $reason, $no, $role, $aid) {
        $b = row('SELECT * FROM host_bookings WHERE id = :id FOR UPDATE', ['id' => $id]);
        if (!$b) {
            return $no('Booking not found.');
        }
        q('INSERT IGNORE INTO host_booking_ext (booking_id) VALUES (:b)', ['b' => $id]);
        $isHost = $role === 'host' && $aid === (int)$b['host_id'];
        $isClient = $role === 'user' && $aid === (int)$b['client_id'];
        $isAdmin = $role === 'admin';
        $isSystem = $role === 'system';
        if (!$isHost && !$isClient && !$isAdmin && !$isSystem) {
            return $no('Booking not found.');
        }
        $from = (string)$b['status'];
        if ($isHost && in_array($to, ['confirmed', 'completed', 'no_show'], true)
            && (string)val('SELECT approval_status FROM host_profiles WHERE user_id = :h', ['h' => (int)$b['host_id']]) !== 'approved') {
            return $no('Your profile is not live, so our team handles this booking. You can still decline or cancel it, or contact support.');
        }
        $startTs = hosts_booking_start_ts($b);
        $endTs = hosts_booking_end_ts($b);
        $now = time();
        $by = $isHost ? 'host' : ($isClient ? 'client' : 'admin');
        $who = $isSystem ? null : ['id' => $aid, 'role' => $role];
        $refund = null;

        switch ($to) {
            case 'confirmed':
                if ($from !== 'pending') {
                    return $no('Only a request that is waiting for an answer can be accepted.');
                }
                if (!$isHost && !$isAdmin) {
                    return $no('Only the Host can accept a request.');
                }
                if ($startTs < $now) {
                    return $no('The start time has already passed.');
                }
                update('host_bookings', ['status' => 'confirmed'], ['id' => $id]);
                update('host_booking_ext', ['confirmed_at' => now()], ['booking_id' => $id]);
                hosts_event($id, 'accepted', $who, $reason);
                break;

            case 'declined':
                if ($from !== 'pending') {
                    return $no('Only a request that is waiting for an answer can be declined.');
                }
                if (!$isHost && !$isAdmin && !$isSystem) {
                    return $no('Only the Host can decline a request.');
                }
                update('host_bookings', ['status' => 'declined', 'cancelled_by' => $isSystem ? 'admin' : $by, 'cancel_reason' => $reason !== '' ? $reason : null], ['id' => $id]);
                update('host_booking_ext', ['closed_at' => now()], ['booking_id' => $id]);
                $refund = ['pct' => 100.0, 'amount' => (float)$b['deposit_amount'], 'label' => 'Your whole deposit is refunded.'];
                hosts_refund_record($b, $refund['amount'], 100.0, $isSystem ? 'Host did not answer in time' : 'Host declined');
                hosts_settle($b, 0, false);
                hosts_event($id, $isSystem ? 'expired' : 'declined', $who, $reason);
                break;

            case 'cancelled':
                if (!in_array($from, ['awaiting_payment', 'pending', 'confirmed'], true)) {
                    return $no('This booking can no longer be cancelled.');
                }
                if ($isHost && $from !== 'confirmed') {
                    return $no('Use Decline to turn down a request.');
                }
                if (($isClient || $isHost) && $from !== 'awaiting_payment' && $startTs < $now) {
                    return $no('This booking has already started. Mark it completed or report an issue instead.');
                }
                $q = hosts_refund_quote($b, $isSystem ? 'system' : $by, $now);
                $refund = ['pct' => $q['pct'], 'amount' => $q['amount'], 'label' => $q['label']];
                update('host_bookings', ['status' => 'cancelled', 'cancelled_by' => $isSystem ? 'admin' : $by, 'cancel_reason' => $reason !== '' ? $reason : null], ['id' => $id]);
                update('host_booking_ext', ['closed_at' => now()], ['booking_id' => $id]);
                if ($from === 'awaiting_payment') {
                    if (!empty($b['payment_id'])) {
                        payment_fail((int)$b['payment_id'], 'cancelled', 'Booking cancelled');
                    }
                    hosts_settle($b, 0, false);
                } else {
                    hosts_refund_record($b, $q['amount'], $q['pct'], 'Cancelled by ' . ($isSystem ? 'the system' : $by) . ($reason !== '' ? ': ' . $reason : ''));
                    $kept = round((float)$b['deposit_amount'] - $q['amount'], 2);
                    hosts_settle($b, $kept, false);
                }
                hosts_event($id, 'cancelled', $who, $reason);
                break;

            case 'completed':
                if ($from !== 'confirmed') {
                    return $no('Only a confirmed booking can be completed.');
                }
                if (!$isAdmin && !$isSystem && $now < $endTs) {
                    return $no('You can mark a booking completed once its end time has passed.');
                }
                update('host_bookings', ['status' => 'completed'], ['id' => $id]);
                update('host_booking_ext', ['completed_at' => now(), 'closed_at' => now(), 'client_confirmed_at' => $isClient ? now() : null], ['booking_id' => $id]);
                hosts_settle($b, (float)$b['total_amount'], true);
                hosts_event($id, 'completed', $who, $isSystem ? 'Completed automatically' : $reason);
                break;

            case 'no_show':
                if ($from !== 'confirmed') {
                    return $no('Only a confirmed booking can be marked as a no-show.');
                }
                if (!$isHost && !$isAdmin) {
                    return $no('Only the Host can report a no-show.');
                }
                if (!$isAdmin && $now < $startTs + 900) {
                    return $no('You can report a no-show 15 minutes after the start time.');
                }
                update('host_bookings', ['status' => 'no_show', 'cancel_reason' => $reason !== '' ? $reason : null], ['id' => $id]);
                update('host_booking_ext', ['closed_at' => now(), 'refund_status' => 'none', 'refund_amount' => 0, 'refund_pct' => 0], ['booking_id' => $id]);
                hosts_settle($b, (float)$b['deposit_amount'], false);
                hosts_event($id, 'no_show', $who, $reason);
                break;

            default:
                return $no('Unknown action.');
        }
        return ['ok' => true, 'error' => '', 'status' => $to, 'message' => '', 'refund' => $refund, '_b' => $b, '_from' => $from, '_by' => $by, '_system' => $isSystem];
    });
    if (empty($result['ok'])) {
        return $result;
    }
    $b = $result['_b'];
    $by = $result['_by'];
    $system = $result['_system'];
    unset($result['_b'], $result['_from'], $result['_by'], $result['_system']);

    // Messages go out after the status change is saved.
    $full = hosts_booking_get($id) ?: $b;
    $parties = hosts_booking_parties($full);
    $when = date('D j M, g:i a', hosts_booking_start_ts($b));
    $refundLine = $result['refund'] && $result['refund']['amount'] > 0 ? ' ' . money($result['refund']['amount']) . ' will be refunded.' : '';
    $cu = (int)$b['client_id'];
    $hu = (int)$b['host_id'];
    switch ($to) {
        case 'confirmed':
            $contactNote = setting('host_contact_after_booking') ? ' Their contact details are now visible on the booking page.' : '';
            hosts_booking_notify($full, $cu, 'booking_update', 'Booking confirmed', $parties['host']['name'] . ' accepted your request for ' . $when . '.', '/bookings/' . $id,
                $parties['host']['name'] . ' accepted your request. Pay the balance of ' . money(max(0, (float)$b['total_amount'] - (float)$b['deposit_amount'])) . ' to them at the meeting.' . $contactNote, 'View booking', $hu);
            $result['message'] = 'Booking confirmed. ' . $parties['client']['first_name'] . ' has been told.';
            break;
        case 'declined':
            hosts_booking_notify($full, $cu, 'booking_update', $system ? 'Request expired' : 'Request declined',
                ($system ? $parties['host']['name'] . ' did not answer in time.' : $parties['host']['name'] . ' could not take this booking.') . $refundLine, '/bookings/' . $id,
                ($system ? $parties['host']['name'] . ' did not answer your request in time.' : $parties['host']['name'] . ' is not able to take this booking.') . ' Your deposit of ' . money($b['deposit_amount']) . ' will be refunded in full. You can look for another Host in the directory.', 'Find another Host');
            if ($system) {
                hosts_booking_notify($full, $hu, 'booking_update', 'Request expired', 'A request from ' . $parties['client']['first_name'] . ' was declined because it was not answered in time.', '/host/bookings/' . $id);
            }
            $result['message'] = 'Request declined.';
            break;
        case 'cancelled':
            if ($by === 'client' && !$system) {
                if ($b['status'] !== 'awaiting_payment') {
                    hosts_booking_notify($full, $hu, 'booking_update', 'Booking cancelled', $parties['client']['first_name'] . ' cancelled the booking for ' . $when . '.', '/host/bookings/' . $id,
                        $parties['client']['first_name'] . ' cancelled the booking.' . ($reason !== '' ? ' Reason: ' . $reason : '') . ' The time is free again in your calendar.', 'View booking');
                }
                $result['message'] = $b['status'] === 'awaiting_payment' ? 'Booking cancelled.' : 'Booking cancelled.' . $refundLine;
            } elseif ($by === 'host') {
                hosts_booking_notify($full, $cu, 'booking_update', 'Booking cancelled by the Host', $parties['host']['name'] . ' cancelled your booking for ' . $when . '.' . $refundLine, '/bookings/' . $id,
                    $parties['host']['name'] . ' had to cancel your booking.' . ($reason !== '' ? ' Reason: ' . $reason : '') . ' Your deposit of ' . money($b['deposit_amount']) . ' will be refunded in full.', 'Find another Host', $hu);
                $result['message'] = 'Booking cancelled. The client will be refunded.';
            } elseif ($b['status'] !== 'awaiting_payment') {
                hosts_booking_notify($full, $cu, 'booking_update', 'Booking cancelled', 'Your booking for ' . $when . ' was cancelled.' . $refundLine, '/bookings/' . $id);
                hosts_booking_notify($full, $hu, 'booking_update', 'Booking cancelled', 'The booking for ' . $when . ' was cancelled.', '/host/bookings/' . $id);
                $result['message'] = 'Booking cancelled.';
            } else {
                $result['message'] = 'Booking cancelled.';
            }
            break;
        case 'completed':
            hosts_booking_notify($full, $cu, 'review', 'How was your booking?', 'Rate your time with ' . $parties['host']['name'] . '. It helps other members choose.', '/bookings/' . $id . '#review',
                'Your booking with ' . $parties['host']['name'] . ' is complete. Tell other members how it went by leaving a short review.', 'Leave a review');
            hosts_booking_notify($full, $hu, 'booking_update', 'Booking completed', 'Your booking with ' . $parties['client']['first_name'] . ' is complete. ' . money($full['host_earning']) . ' added to your earnings.', '/host/bookings/' . $id);
            $result['message'] = 'Booking marked as completed.';
            break;
        case 'no_show':
            hosts_booking_notify($full, $cu, 'booking_update', 'Marked as a no-show', $parties['host']['name'] . ' reported that you did not attend on ' . $when . '. The deposit is not refunded.', '/bookings/' . $id,
                $parties['host']['name'] . ' reported that you did not attend this booking, so the deposit is kept. If this is a mistake, report an issue from the booking page.', 'View booking');
            $result['message'] = 'Marked as a no-show. The deposit is kept.';
            break;
    }
    return $result;
}

/**
 * Cron job: release unpaid slots, decline unanswered requests, complete finished bookings, send reminders.
 * Safe to run every few minutes. @return array counts
 */
function hosts_housekeeping(): array
{
    $out = ['unpaid_cancelled' => 0, 'auto_declined' => 0, 'auto_completed' => 0, 'reminders' => 0];
    $system = ['id' => 0, 'role' => 'system'];
    $hold = max(1, (int)setting('host_payment_hold_minutes'));
    foreach (col("SELECT id FROM host_bookings WHERE status = 'awaiting_payment' AND created_at < DATE_SUB(NOW(), INTERVAL $hold MINUTE) ORDER BY id LIMIT 200") as $id) {
        if (hosts_booking_transition((int)$id, 'cancelled', $system, ['reason' => 'Payment not completed in time'])['ok']) {
            $out['unpaid_cancelled']++;
        }
    }
    $pending = rows("SELECT b.id FROM host_bookings b LEFT JOIN host_booking_ext e ON e.booking_id = b.id
                     WHERE b.status = 'pending' AND ((e.accept_by IS NOT NULL AND e.accept_by < NOW()) OR TIMESTAMP(b.booking_date, b.start_time) < NOW()) ORDER BY b.id LIMIT 200");
    foreach ($pending as $r) {
        if (hosts_booking_transition((int)$r['id'], 'declined', $system, ['reason' => 'The Host did not answer in time'])['ok']) {
            $out['auto_declined']++;
        }
    }
    $auto = max(1, (int)setting('host_auto_complete_hours'));
    $done = rows("SELECT id FROM host_bookings WHERE status = 'confirmed'
                  AND DATE_ADD(TIMESTAMP(booking_date, start_time), INTERVAL ROUND(duration_hours * 60 + :h * 60) MINUTE) < NOW() ORDER BY id LIMIT 200", ['h' => $auto]);
    foreach ($done as $r) {
        if (hosts_booking_transition((int)$r['id'], 'completed', $system)['ok']) {
            $out['auto_completed']++;
        }
    }
    $window = max(1, (int)setting('host_reminder_hours'));
    $remind = rows("SELECT b.id FROM host_bookings b JOIN host_booking_ext e ON e.booking_id = b.id
                    WHERE b.status = 'confirmed' AND e.reminder_sent_at IS NULL AND TIMESTAMP(b.booking_date, b.start_time) > NOW()
                      AND TIMESTAMP(b.booking_date, b.start_time) <= DATE_ADD(NOW(), INTERVAL :h HOUR) ORDER BY b.id LIMIT 200", ['h' => $window]);
    foreach ($remind as $r) {
        $b = hosts_booking_get((int)$r['id']);
        if (!$b) {
            continue;
        }
        update('host_booking_ext', ['reminder_sent_at' => now()], ['booking_id' => (int)$b['id']]);
        $parties = hosts_booking_parties($b);
        $when = date('D j M, g:i a', hosts_booking_start_ts($b));
        hosts_booking_notify($b, (int)$b['client_id'], 'booking_update', 'Booking reminder', 'You are meeting ' . $parties['host']['name'] . ' on ' . $when . '.', '/bookings/' . (int)$b['id'],
            'This is a reminder of your booking with ' . $parties['host']['name'] . '. Please arrive on time and keep to the public place you agreed.', 'View booking');
        hosts_booking_notify($b, (int)$b['host_id'], 'booking_update', 'Booking reminder', 'You are meeting ' . $parties['client']['first_name'] . ' on ' . $when . '.', '/host/bookings/' . (int)$b['id'],
            'This is a reminder of your booking with ' . $parties['client']['first_name'] . '.', 'View booking');
        $out['reminders']++;
    }
    return $out;
}

// ===========================================================================
// 8. Earnings, balance and payouts
// ===========================================================================

/**
 * Money the platform owes a host, booking by booking, with a status for each line:
 * held (inside the payout hold), available, requested (in a payout request) or paid.
 * Payouts are matched to the oldest earnings first.
 *
 * @return array ['rows' => [...], 'balance' => [...]]
 */
function hosts_ledger(int $hostId): array
{
    $rows = rows(
        "SELECT b.id, b.booking_date, b.start_time, b.duration_hours, b.status, b.total_amount, b.deposit_amount, b.commission_pct, b.commission_amount, b.host_earning,
                e.offering_name, e.settled_amount, e.settled_at, p.display_name AS client_name
         FROM host_bookings b JOIN host_booking_ext e ON e.booking_id = b.id LEFT JOIN user_profiles p ON p.user_id = b.client_id
         WHERE b.host_id = :h AND e.settled_at IS NOT NULL AND (b.status IN ('completed','no_show') OR e.settled_amount > 0)
         ORDER BY e.settled_at, b.id", ['h' => $hostId]);
    $sum = fn(string $status) => (float)val("SELECT COALESCE(SUM(amount), 0) FROM host_payouts WHERE host_id = :h AND status = :s", ['h' => $hostId, 's' => $status]);
    $paid = $sum('paid');
    $req = $sum('requested');
    $holdSec = max(0, (int)setting('host_payout_hold_hours')) * 3600;
    $now = time();
    $cum = 0.0;
    $matured = 0.0;
    $total = 0.0;
    foreach ($rows as &$r) {
        $amt = (float)$r['settled_amount'];
        $cum += $amt;
        $total += $amt;
        $isMatured = strtotime((string)$r['settled_at']) + $holdSec <= $now;
        if ($isMatured) {
            $matured += $amt;
        }
        if ($cum <= $paid + 0.005) {
            $r['ledger_status'] = 'paid';
        } elseif ($cum <= $paid + $req + 0.005) {
            $r['ledger_status'] = 'requested';
        } else {
            $r['ledger_status'] = $isMatured ? 'available' : 'held';
        }
        $r['available_at'] = date('Y-m-d H:i:s', strtotime((string)$r['settled_at']) + $holdSec);
    }
    unset($r);
    $upcoming = (float)val("SELECT COALESCE(SUM(deposit_amount - commission_amount), 0) FROM host_bookings WHERE host_id = :h AND status IN ('pending','confirmed')", ['h' => $hostId]);
    // Same set of bookings as the ledger and the monthly figure, so "this month" can never exceed "in total".
    $stats = row("SELECT COALESCE(SUM(b.status = 'completed'), 0) AS n, COALESCE(SUM(IF(b.status = 'completed', b.total_amount, 0)), 0) AS gross,
                         COALESCE(SUM(b.commission_amount), 0) AS commission, COALESCE(SUM(b.host_earning), 0) AS earned
                  FROM host_bookings b JOIN host_booking_ext e ON e.booking_id = b.id
                  WHERE b.host_id = :h AND e.settled_at IS NOT NULL AND (b.status IN ('completed','no_show') OR e.settled_amount > 0)", ['h' => $hostId]);
    $monthStart = date('Y-m-01 00:00:00');
    $monthEarned = (float)val("SELECT COALESCE(SUM(b.host_earning), 0) FROM host_bookings b JOIN host_booking_ext e ON e.booking_id = b.id
                               WHERE b.host_id = :h AND e.settled_at >= :m AND (b.status IN ('completed','no_show') OR e.settled_amount > 0)", ['h' => $hostId, 'm' => $monthStart]);
    $balance = [
        'available' => max(0.0, round($matured - $paid - $req, 2)), 'held' => max(0.0, round($total - $matured, 2)), 'requested' => $req, 'paid_out' => $paid,
        'total_payable' => round($total, 2), 'upcoming' => round($upcoming, 2), 'completed_count' => (int)$stats['n'], 'gross' => (float)$stats['gross'],
        'commission_total' => (float)$stats['commission'], 'earned_total' => (float)$stats['earned'], 'earned_month' => $monthEarned,
    ];
    return ['rows' => array_reverse($rows), 'balance' => $balance];
}

/** Balance summary (see hosts_ledger). Keys: available, held, requested, paid_out, upcoming, earned_total, earned_month, ... */
function hosts_balance(int $hostId): array
{
    return hosts_ledger($hostId)['balance'];
}

/** Earnings per month for the last $months months, oldest first: [['month' => '2026-10', 'label' => 'Oct', 'earning' => float, 'bookings' => int], ...]. */
function hosts_monthly_earnings(int $hostId, int $months = 12): array
{
    $months = max(1, min(36, $months));
    $from = date('Y-m-01 00:00:00', strtotime('first day of this month -' . ($months - 1) . ' month'));
    $got = [];
    foreach (rows("SELECT DATE_FORMAT(e.settled_at, '%Y-%m') AS m, COALESCE(SUM(b.host_earning), 0) AS earned, SUM(b.status = 'completed') AS n
                   FROM host_bookings b JOIN host_booking_ext e ON e.booking_id = b.id
                   WHERE b.host_id = :h AND e.settled_at >= :f AND (b.status IN ('completed','no_show') OR e.settled_amount > 0) GROUP BY m", ['h' => $hostId, 'f' => $from]) as $r) {
        $got[$r['m']] = $r;
    }
    $out = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $ts = strtotime('first day of this month -' . $i . ' month');
        $key = date('Y-m', $ts);
        $out[] = ['month' => $key, 'label' => date('M', $ts), 'earning' => (float)($got[$key]['earned'] ?? 0), 'bookings' => (int)($got[$key]['n'] ?? 0)];
    }
    return $out;
}

/** Ask for a payout. Checks the minimum, the available balance and payout details inside a transaction. @return array ['ok', 'error', 'id'] */
function hosts_payout_request(int $hostId, float $amount): array
{
    $amount = round($amount, 2);
    try {
        return db_transaction(function () use ($hostId, $amount) {
            $hp = row('SELECT approval_status FROM host_profiles WHERE user_id = :h FOR UPDATE', ['h' => $hostId]);
            if (!$hp || $hp['approval_status'] !== 'approved') {
                return ['ok' => false, 'error' => 'Payouts are available once your profile is approved.', 'id' => 0];
            }
            $ext = hosts_ext($hostId);
            $phone = phone_normalize((string)$ext['payout_phone']);
            if ($phone === '' || trim((string)$ext['payout_name']) === '') {
                return ['ok' => false, 'error' => 'Add your M-Pesa number and the name on the account first.', 'id' => 0];
            }
            $min = (float)setting('host_payout_min');
            $bal = hosts_balance($hostId);
            if ($amount <= 0 || $amount < $min) {
                return ['ok' => false, 'error' => 'The smallest payout is ' . money($min) . '.', 'id' => 0];
            }
            if ($amount > $bal['available'] + 0.005) {
                return ['ok' => false, 'error' => 'You can request up to ' . money($bal['available']) . ' right now.', 'id' => 0];
            }
            $id = insert('host_payouts', ['host_id' => $hostId, 'amount' => $amount, 'method' => 'mpesa', 'destination' => mb_substr($phone . ' - ' . trim((string)$ext['payout_name']), 0, 80), 'status' => 'requested']);
            hosts_notify_admins('Payout requested', money($amount) . ' to ' . $phone . '.', '/admin/payouts', 'payment');
            return ['ok' => true, 'error' => '', 'id' => $id];
        });
    } catch (Throwable $e) {
        app_log('error', 'hosts_payout_request failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'We could not save the request. Please try again.', 'id' => 0];
    }
}

// ===========================================================================
// 9. Reviews
// ===========================================================================

/** Recompute rating_avg and review_count on host_profiles from approved reviews. Call after approving or removing a review. */
function hosts_rating_recalc(int $hostId): void
{
    $r = row("SELECT COUNT(*) AS c, COALESCE(AVG(rating), 0) AS a FROM host_reviews WHERE host_id = :h AND status = 'approved'", ['h' => $hostId]);
    update('host_profiles', ['rating_avg' => round((float)$r['a'], 2), 'review_count' => (int)$r['c']], ['user_id' => $hostId]);
}

/** Average, count and 5..1 star distribution of approved reviews: ['avg' => float, 'count' => int, 'dist' => [5 => n, ...]]. */
function hosts_review_summary(int $hostId): array
{
    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $sum = 0;
    $n = 0;
    foreach (rows("SELECT rating, COUNT(*) AS c FROM host_reviews WHERE host_id = :h AND status = 'approved' GROUP BY rating", ['h' => $hostId]) as $r) {
        $k = (int)$r['rating'];
        if (isset($dist[$k])) {
            $dist[$k] = (int)$r['c'];
            $sum += $k * (int)$r['c'];
            $n += (int)$r['c'];
        }
    }
    return ['avg' => $n ? round($sum / $n, 1) : 0.0, 'count' => $n, 'dist' => $dist];
}

/** Reviews of a host with the reviewer's display name (first name only, or "Verified client" when anonymous). */
function hosts_reviews(int $hostId, int $limit = 6, int $offset = 0, bool $approvedOnly = true): array
{
    $r = rows("SELECT r.id, r.rating, r.review_text, r.is_anonymous, r.status, r.host_reply, r.created_at, r.booking_id, p.display_name
               FROM host_reviews r LEFT JOIN user_profiles p ON p.user_id = r.reviewer_id
               WHERE r.host_id = :h" . ($approvedOnly ? " AND r.status = 'approved'" : '') . ' ORDER BY r.created_at DESC, r.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset), ['h' => $hostId]);
    foreach ($r as &$x) {
        $x['reviewer'] = (int)$x['is_anonymous'] === 1 ? 'Verified client' : (explode(' ', trim((string)$x['display_name']))[0] ?: 'Client');
    }
    return $r;
}

/** Can this client review this booking? Returns ['ok' => bool, 'error' => string]. */
function hosts_review_check(array $b, int $clientId): array
{
    if ((int)$b['client_id'] !== $clientId) {
        return ['ok' => false, 'error' => 'Booking not found.'];
    }
    if ($b['status'] !== 'completed') {
        return ['ok' => false, 'error' => 'You can review a booking once it is completed.'];
    }
    if ((int)val('SELECT COUNT(*) FROM host_reviews WHERE booking_id = :b', ['b' => $b['id']]) > 0) {
        return ['ok' => false, 'error' => 'You have already reviewed this booking.'];
    }
    if (!empty($b['completed_at']) && strtotime((string)$b['completed_at']) < time() - 60 * 86400) {
        return ['ok' => false, 'error' => 'The review period for this booking has ended.'];
    }
    return ['ok' => true, 'error' => ''];
}

/** Save a client's review of a completed booking. @return array ['ok', 'error', 'status'] */
function hosts_review_create(int $bookingId, int $clientId, int $rating, string $text, bool $anonymous = true): array
{
    $text = mb_substr(trim($text), 0, 1000);
    if ($rating < 1 || $rating > 5) {
        return ['ok' => false, 'error' => 'Choose a rating from 1 to 5 stars.', 'status' => ''];
    }
    if ($text !== '' && mb_strlen($text) < 10) {
        return ['ok' => false, 'error' => 'Write at least a sentence, or leave the comment empty.', 'status' => ''];
    }
    if ($text !== '' && hosts_text_violation($text) !== null) {
        return ['ok' => false, 'error' => hosts_violation_message('Your review'), 'status' => ''];
    }
    if ($text !== '' && hosts_contact_violation($text) !== null) {
        return ['ok' => false, 'error' => hosts_contact_message('Your review'), 'status' => ''];
    }
    return db_transaction(function () use ($bookingId, $clientId, $rating, $text, $anonymous) {
        $b = row('SELECT b.*, e.completed_at FROM host_bookings b LEFT JOIN host_booking_ext e ON e.booking_id = b.id WHERE b.id = :id FOR UPDATE', ['id' => $bookingId]);
        if (!$b) {
            return ['ok' => false, 'error' => 'Booking not found.', 'status' => ''];
        }
        $chk = hosts_review_check($b, $clientId);
        if (!$chk['ok']) {
            return $chk + ['status' => ''];
        }
        $status = (string)setting('host_review_moderation') === 'auto' ? 'approved' : 'pending';
        insert('host_reviews', ['host_id' => (int)$b['host_id'], 'reviewer_id' => $clientId, 'booking_id' => $bookingId, 'rating' => $rating,
            'review_text' => $text !== '' ? $text : null, 'is_anonymous' => $anonymous ? 1 : 0, 'status' => $status]);
        hosts_event($bookingId, 'review', ['id' => $clientId, 'role' => 'user'], $rating . ' stars');
        if ($status === 'approved') {
            hosts_rating_recalc((int)$b['host_id']);
            hosts_booking_notify($b, (int)$b['host_id'], 'review', 'New review', 'You received a ' . $rating . '-star review.', '/host/reviews', 'A client left you a ' . $rating . '-star review. You can reply to it from your reviews page.', 'Read the review');
        } else {
            admin_core_counts_forget();
            try {
                hosts_notify_admins('Review to check', 'A client left a ' . $rating . '-star review that waits for approval.', '/admin/host-reviews');
            } catch (Throwable $e) {
                app_log('error', 'review admin notice failed: ' . $e->getMessage());
            }
        }
        return ['ok' => true, 'error' => '', 'status' => $status];
    });
}

/**
 * Admin decision on a client's review: 'approve' publishes it, 'reject' hides it (an approved review can also be taken down).
 * Keeps the host's rating and review count in step and tells the host when a review goes live.
 * @return array ['ok', 'error', 'status', 'host_id']
 */
function hosts_review_decide(int $reviewId, string $decision): array
{
    if (!in_array($decision, ['approve', 'reject'], true)) {
        return ['ok' => false, 'error' => 'Unknown action.', 'status' => '', 'host_id' => 0];
    }
    return db_transaction(function () use ($reviewId, $decision) {
        $r = row('SELECT id, host_id, booking_id, rating, status FROM host_reviews WHERE id = :id FOR UPDATE', ['id' => $reviewId]);
        if (!$r) {
            return ['ok' => false, 'error' => 'Review not found.', 'status' => '', 'host_id' => 0];
        }
        $hostId = (int)$r['host_id'];
        $new = $decision === 'approve' ? 'approved' : 'rejected';
        if ($r['status'] === $new) {
            return ['ok' => true, 'error' => '', 'status' => $new, 'host_id' => $hostId, 'unchanged' => true];
        }
        update('host_reviews', ['status' => $new], ['id' => $reviewId]);
        hosts_rating_recalc($hostId);
        if ($new === 'approved' && $r['status'] === 'pending') {
            $b = row('SELECT * FROM host_bookings WHERE id = :id', ['id' => (int)$r['booking_id']]);
            if ($b) {
                hosts_booking_notify($b, $hostId, 'review', 'New review', 'You received a ' . (int)$r['rating'] . '-star review.', '/host/reviews', 'A client left you a ' . (int)$r['rating'] . '-star review. You can reply to it from your reviews page.', 'Read the review');
            }
        }
        return ['ok' => true, 'error' => '', 'status' => $new, 'host_id' => $hostId];
    });
}

/** Host answers a review (once, editable). @return array ['ok', 'error'] */
function hosts_review_reply(int $reviewId, int $hostId, string $reply): array
{
    $reply = mb_substr(trim($reply), 0, 500);
    if (mb_strlen($reply) < 2) {
        return ['ok' => false, 'error' => 'Write a short reply.'];
    }
    if (hosts_text_violation($reply) !== null) {
        return ['ok' => false, 'error' => hosts_violation_message('Your reply')];
    }
    if (hosts_contact_violation($reply) !== null) {
        return ['ok' => false, 'error' => hosts_contact_message('Your reply')];
    }
    $r = row('SELECT id, status FROM host_reviews WHERE id = :id AND host_id = :h', ['id' => $reviewId, 'h' => $hostId]);
    if (!$r) {
        return ['ok' => false, 'error' => 'Review not found.'];
    }
    if ($r['status'] !== 'approved') {
        return ['ok' => false, 'error' => 'You can reply once the review is published.'];
    }
    update('host_reviews', ['host_reply' => $reply], ['id' => $reviewId]);
    return ['ok' => true, 'error' => ''];
}

/** Share of requests the host answered (accepted or declined) and the average time to answer. Null values when there are too few requests. */
function hosts_response_stats(int $hostId): array
{
    $r = row("SELECT SUM(ev.event IN ('accepted','declined')) AS answered, SUM(ev.event = 'expired') AS expired
              FROM host_booking_events ev JOIN host_bookings b ON b.id = ev.booking_id
              WHERE b.host_id = :h AND ev.event IN ('accepted','declined','expired') AND ev.actor_role <> 'admin'", ['h' => $hostId]);
    $answered = (int)($r['answered'] ?? 0);
    $expired = (int)($r['expired'] ?? 0);
    $total = $answered + $expired;
    $rate = $total >= 3 ? (int)round($answered / $total * 100) : null;
    $avg = val("SELECT AVG(TIMESTAMPDIFF(MINUTE, p.created_at, a.created_at)) FROM host_booking_events p JOIN host_booking_events a ON a.booking_id = p.booking_id AND a.event IN ('accepted','declined') AND a.actor_role = 'host'
                JOIN host_bookings b ON b.id = p.booking_id WHERE p.event = 'paid' AND b.host_id = :h", ['h' => $hostId]);
    return ['rate' => $rate, 'avg_minutes' => $avg !== null && $total >= 3 ? (int)round((float)$avg) : null, 'requests' => $total];
}

// ===========================================================================
// 10. Application, approval, private ID files
// ===========================================================================

const HOSTS_SETUP_STEPS = [
    'basics' => 'About you', 'services' => 'Services and rates', 'area' => 'Where you work', 'photos' => 'Photos',
    'verify' => 'Verification', 'payout' => 'Payout and rules', 'review' => 'Review and submit',
];

const HOSTS_LANGUAGES = ['English', 'Kiswahili', 'French', 'Arabic', 'Somali', 'Kikuyu', 'Luo', 'Luhya', 'Kalenjin', 'Kamba', 'Kisii', 'Meru', 'German', 'Italian', 'Spanish', 'Mandarin', 'Hindi', 'Gujarati'];

/** How far a host is from a complete application. @return array ['items' => [...], 'pct' => int, 'complete' => bool, 'photos' => int, 'min_photos' => int] */
function hosts_completeness(int $hostId): array
{
    $hp = hosts_profile($hostId) ?: [];
    $minPhotos = max(1, (int)setting('host_min_photos'));
    $photos = (int)val("SELECT COUNT(*) FROM user_photos WHERE user_id = :u AND moderation_status <> 'rejected'", ['u' => $hostId]);
    $loc = user_location($hostId);
    $minRate = (float)setting('host_min_rate');
    $offers = (int)val('SELECT COUNT(*) FROM host_offerings WHERE host_id = :h AND is_active = 1', ['h' => $hostId]);
    $priced = (!empty($hp['hourly_rate']) && (float)$hp['hourly_rate'] >= $minRate) || $offers > 0;
    $basics = trim((string)($hp['display_name'] ?? '')) !== '' && mb_strlen(trim((string)($hp['tagline'] ?? ''))) >= 8
        && mb_strlen(trim((string)($hp['about'] ?? ''))) >= (int)setting('host_min_about_chars') && !empty($hp['category_id']);
    $vs = host_verify_state($hostId);
    $idOk = $vs['ok'];
    // with bookings off there is no money to pay out, so only the agreement to the Host rules is needed
    $payoutOk = (!hosts_booking_enabled() || (phone_normalize((string)($hp['payout_phone'] ?? '')) !== '' && trim((string)($hp['payout_name'] ?? '')) !== '')) && !empty($hp['rules_agreed_at']);
    $items = [
        ['key' => 'basics', 'label' => 'Name, headline and about you', 'done' => $basics, 'hint' => 'Add a headline and a few sentences about you.'],
        ['key' => 'services', 'label' => 'Services and rates', 'done' => $priced && !empty(hosts_ids($hp['service_ids'] ?? '')), 'hint' => 'Pick your services and set an hourly rate or a package.'],
        ['key' => 'area', 'label' => 'Where you work', 'done' => $loc !== null && !empty($loc['county_id']) && !empty(hosts_ids($hp['service_area_ids'] ?? '')), 'hint' => 'Choose your town and the areas you meet in.'],
        ['key' => 'photos', 'label' => 'At least ' . $minPhotos . ' photos', 'done' => $photos >= $minPhotos, 'hint' => 'Upload clear, recent photos of yourself (' . $photos . ' of ' . $minPhotos . ').'],
        ['key' => 'verify', 'label' => host_verify_mode() === 'photo' ? 'Photo check' : (host_verify_mode() === 'both' ? 'Verification' : (host_verify_mode() === 'none' ? 'Verification' : 'ID verification')), 'done' => $idOk,
            'hint' => host_verify_mode() === 'photo' ? 'Take a live photo so we know it is really you.' : (host_verify_mode() === 'both' ? ($vs['rule'] === 'any' ? 'Upload your ID or take a live photo.' : 'Upload your ID and take a live photo.') : 'Upload your ID' . (setting('host_selfie_required') ? ' and a selfie holding it' : '') . '.')],
        ['key' => 'payout', 'label' => hosts_booking_enabled() ? 'Payout details and rules' : 'Host rules', 'done' => $payoutOk, 'hint' => hosts_booking_enabled() ? 'Add your M-Pesa details and agree to the Host rules.' : 'Read and agree to the Host rules.'],
    ];
    $done = count(array_filter($items, fn($i) => $i['done']));
    return ['items' => $items, 'pct' => (int)round($done / count($items) * 100), 'complete' => $done === count($items), 'photos' => $photos, 'min_photos' => $minPhotos];
}

/**
 * Submit the application for review (or publish straight away when approval is switched off).
 * Marks onboarding as finished. @return array ['ok' => bool, 'error' => string, 'status' => string, 'missing' => [labels]]
 */
function hosts_submit_application(int $hostId): array
{
    $hp = hosts_profile($hostId);
    if (!$hp) {
        return ['ok' => false, 'error' => 'Host profile not found.', 'status' => '', 'missing' => []];
    }
    if (!in_array($hp['approval_status'], ['draft', 'rejected'], true)) {
        return ['ok' => false, 'error' => 'Your application has already been submitted.', 'status' => (string)$hp['approval_status'], 'missing' => []];
    }
    $c = hosts_completeness($hostId);
    if (!$c['complete']) {
        return ['ok' => false, 'error' => 'Finish every step before you submit.', 'status' => '', 'missing' => array_column(array_filter($c['items'], fn($i) => !$i['done']), 'label')];
    }
    $auto = !setting('host_requires_approval');
    $status = $auto ? 'approved' : 'pending';
    $set = ['approval_status' => $status, 'approval_note' => null, 'submitted_at' => now()];
    if ($auto) {
        $set += ['approved_at' => now(), 'approved_by' => null];
    }
    db_transaction(function () use ($hostId, $set, $hp) {
        update('host_profiles', $set, ['user_id' => $hostId]);
        if (empty(json_decode((string)$hp['weekly_availability'], true)) || !array_filter(hosts_weekly($hp))) {
            update('host_profiles', ['weekly_availability' => json_encode(hosts_weekly_default())], ['user_id' => $hostId]);
        }
        update('user_profiles', ['onboarding_completed' => 1, 'onboarding_step' => 7], ['user_id' => $hostId]);
    });
    if ($auto) {
        notify($hostId, 'host_status', 'Your Host profile is live', 'Your profile is visible in the directory. Check your availability so clients can book.', '/host/dashboard');
    } else {
        notify($hostId, 'host_status', 'Application received', 'We will review it within ' . (int)setting('host_approval_sla_hours') . ' hours and let you know.', '/host/dashboard');
        hosts_notify_admins('New Host application', ($hp['display_name'] ?: 'A host') . ' has applied to become a Host.', '/admin/hosts', 'host_status');
    }
    return ['ok' => true, 'error' => '', 'status' => $status, 'missing' => []];
}

/**
 * Change a Host's approval state (for the admin pages). Tells the host by notification and e-mail.
 * $status: approved | rejected | suspended | pending | draft. $idVerified: null = set it when approving and an ID file exists.
 */
function hosts_set_approval(int $hostId, string $status, string $note = '', ?int $adminId = null, ?bool $idVerified = null): bool
{
    if (!in_array($status, ['draft', 'pending', 'approved', 'rejected', 'suspended'], true) || !hosts_profile($hostId)) {
        return false;
    }
    $note = mb_substr(trim($note), 0, 255);
    $set = ['approval_status' => $status, 'approval_note' => $note !== '' ? $note : null];
    if ($status === 'approved') {
        $set += ['approved_at' => now(), 'approved_by' => $adminId];
        $ext = hosts_ext($hostId);
        $set['id_verified'] = ($idVerified ?? (host_verify_id_on() && !empty($ext['id_doc_path']))) ? 1 : 0;
    } elseif ($idVerified !== null) {
        $set['id_verified'] = $idVerified ? 1 : 0;
    }
    update('host_profiles', $set, ['user_id' => $hostId]);
    update('user_profiles', ['onboarding_completed' => 1], ['user_id' => $hostId]);
    if ($status === 'approved' && ((int)($set['id_verified'] ?? 0) === 1 || (int)val('SELECT photo_verified FROM host_profiles WHERE user_id = :u', ['u' => $hostId], 0) === 1)) {
        update('user_profiles', ['is_verified' => 1, 'verified_at' => now()], ['user_id' => $hostId]);
    }
    $titles = ['approved' => 'Your Host profile is approved', 'rejected' => 'Your application needs changes', 'suspended' => 'Your Host profile is suspended', 'pending' => 'Your application is in review', 'draft' => 'Your application was reopened'];
    $bodies = [
        'approved' => 'You are live in the Host directory. Check your availability so clients can book.',
        'rejected' => $note !== '' ? $note : 'Please update your profile and submit it again.',
        'suspended' => $note !== '' ? $note : 'Contact support if you think this is a mistake.',
        'pending' => 'We will let you know when the review is done.', 'draft' => 'Finish your application and submit it again.',
    ];
    notify($hostId, 'host_status', $titles[$status], $bodies[$status], '/host/dashboard');
    $u = row('SELECT u.email, p.display_name FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = :id', ['id' => $hostId]);
    if ($u) {
        send_template_mail($u['email'], $titles[$status], 'host_status', ['title' => $titles[$status], 'name' => explode(' ', trim((string)$u['display_name']))[0] ?: 'there', 'body' => $bodies[$status]]);
    }
    cache_forget('site_stats');
    return true;
}

function hosts_private_dir(): string
{
    return ROOT . '/storage/private';
}

/**
 * Store an ID or selfie image privately: validated, re-encoded as JPEG (EXIF removed) under storage/private/host_id/{userId}/.
 * The folder is never web-served. @return array ['ok' => bool, 'error' => string, 'path' => relative path inside storage/private]
 */
function hosts_store_private_image(array $file, int $userId, string $kind): array
{
    $problem = image_upload_problem($file, 10, 400);
    if ($problem !== '') {
        return ['ok' => false, 'error' => $problem, 'path' => ''];
    }
    try {
        $rel = 'host_id/' . $userId;
        $dir = hosts_private_dir() . '/' . $rel;
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create private folder');
        }
        foreach ([hosts_private_dir() . '/.htaccess' => "Require all denied\nDeny from all\n", hosts_private_dir() . '/index.html' => ''] as $f => $content) {
            if (!is_file($f)) {
                @file_put_contents($f, $content);
            }
        }
        $img = image_load($file['tmp_name']);
        $out = image_fit($img, 2000);
        $name = preg_replace('/[^a-z]/', '', $kind) . '_' . random_token(8) . '.jpg';
        image_save_jpeg($out, $dir . '/' . $name, 85);
        @chmod($dir . '/' . $name, 0640);
        return ['ok' => true, 'error' => '', 'path' => $rel . '/' . $name];
    } catch (Throwable $e) {
        app_log('error', 'private image failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'We could not process that image. Try a different photo.', 'path' => ''];
    }
}

/**
 * Absolute path of a host's private verification file, for admins only.
 * $kind: 'id' (the ID document) or 'selfie'. Returns null for anyone who is not an admin with access to Hosts, or when there is no file.
 * Admin pages stream it with readfile() after sending the right Content-Type, or call hosts_id_file_send().
 */
function hosts_id_file_path(int $hostUserId, string $kind = 'id'): ?string
{
    $u = current_user();
    if (!$u || $u['role'] !== 'admin' || !admin_can('hosts', 'view')) {
        return null;
    }
    $ext = hosts_ext($hostUserId);
    $rel = (string)($kind === 'selfie' ? $ext['selfie_path'] : $ext['id_doc_path']);
    if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, 'host_id/' . $hostUserId . '/') !== 0) {
        return null;
    }
    $abs = hosts_private_dir() . '/' . $rel;
    return is_file($abs) ? $abs : null;
}

/** Send a host's private ID or selfie image to an admin (404 for everyone else). Does not return. */
function hosts_id_file_send(int $hostUserId, string $kind = 'id'): void
{
    $path = hosts_id_file_path($hostUserId, $kind);
    if ($path === null) {
        abort(404);
    }
    admin_log('view_host_id', 'host', $hostUserId, $kind);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, no-store');
    header('X-Robots-Tag: noindex');
    readfile($path);
    exit;
}

// ---------------------------------------------------------------------------
// Save handlers shared by /host/setup, /host/profile, /host/services and /host/settings.
// Each returns ['ok' => bool, 'errors' => [field => message]].
// ---------------------------------------------------------------------------

function hosts_save_basics(int $hostId, array $in): array
{
    $errors = [];
    $name = mb_substr(trim((string)($in['display_name'] ?? '')), 0, 60);
    $tagline = mb_substr(trim((string)($in['tagline'] ?? '')), 0, 120);
    $about = mb_substr(trim((string)($in['about'] ?? '')), 0, 2000);
    $cat = (int)($in['category_id'] ?? 0);
    $langs = array_values(array_intersect(HOSTS_LANGUAGES, array_map('strval', (array)($in['languages'] ?? []))));
    if (mb_strlen($name) < 2) {
        $errors['display_name'] = 'Enter the name clients will see (2 to 60 characters).';
    }
    if (mb_strlen($tagline) < 8) {
        $errors['tagline'] = 'Write a headline of at least 8 characters, for example "Easy conversation and good taste in restaurants".';
    }
    $minAbout = (int)setting('host_min_about_chars');
    if (mb_strlen($about) < $minAbout) {
        $errors['about'] = 'Tell clients a little about yourself (at least ' . $minAbout . ' characters).';
    }
    if (!isset(hosts_categories()[$cat])) {
        $errors['category_id'] = 'Choose the category that fits you best.';
    }
    if (!isset($errors['display_name']) && preg_match('/[<>@\\d]/u', $name)) {
        $errors['display_name'] = 'Use your name or a nickname, without numbers or symbols.';
    }
    // short promotion line shown in the agent's story (only when the form carries the field and the admin allows it)
    $setPromo = array_key_exists('promo_text', $in) && setting('agent_story_promo');
    $promo = $setPromo ? mb_substr(trim((string)preg_replace('/\s+/u', ' ', (string)$in['promo_text'])), 0, 140) : '';
    foreach (['display_name' => $name, 'tagline' => $tagline, 'about' => $about] + ($promo !== '' ? ['promo_text' => $promo] : []) as $f => $text) {
        if (isset($errors[$f])) {
            continue;
        }
        if (hosts_text_violation($text) !== null) {
            $errors[$f] = hosts_violation_message('This field');
        } elseif (hosts_contact_violation($text) !== null) {
            $errors[$f] = hosts_contact_message('This field');
        }
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    db_transaction(function () use ($hostId, $name, $tagline, $about, $cat, $langs) {
        update('host_profiles', ['display_name' => $name, 'tagline' => $tagline, 'about' => $about, 'category_id' => $cat], ['user_id' => $hostId]);
        update('user_profiles', ['display_name' => $name, 'bio' => $about, 'languages' => $langs ? implode(', ', $langs) : null], ['user_id' => $hostId]);
    });
    if ($setPromo) {
        hosts_ext_save($hostId, ['promo_text' => $promo !== '' ? $promo : null]);
        cache_clear();
    }
    return ['ok' => true, 'errors' => []];
}

/**
 * The contact way of saving services: what the Host offers, an optional price beside each one, and the short
 * line that sits above their contact buttons. Rates, packages and travel belong to the booking way and are
 * left exactly as they are, so switching back restores them.
 */
function hosts_save_services_contact(int $hostId, array $in): array
{
    $catalog = hosts_services_catalog();
    $ids = array_values(array_filter(hosts_ids((array)($in['service_ids'] ?? [])), fn($i) => isset($catalog[$i])));
    $errors = [];
    if (!$ids) {
        $errors['service_ids'] = 'Choose at least one service you offer.';
    } elseif (count($ids) > max(1, (int)setting('host_max_services'))) {
        $errors['service_ids'] = 'You can choose up to ' . (int)setting('host_max_services') . ' services.';
    }
    [$prices, $priceErrors] = hosts_service_prices_allowed()
        ? hosts_service_prices_save((array)($in['service_price'] ?? []), $ids)
        : [[], []];
    $errors += $priceErrors;

    $intro = trim(preg_replace('/\s+/u', ' ', (string)($in['contact_intro'] ?? '')) ?? '');
    if (mb_strlen($intro) > 200) {
        $errors['contact_intro'] = 'Keep this under 200 characters.';
    }
    // Naming a channel ("WhatsApp is fastest") is the whole point of this line, so only banned words and
    // real contact details are refused: the number itself belongs in the contact fields.
    if ($intro !== '' && !isset($errors['contact_intro'])) {
        if (hosts_text_violation($intro) !== null) {
            $errors['contact_intro'] = hosts_violation_message('This line');
        } elseif (preg_match('/\d[\d\s.\-()]{6,}/u', $intro) || preg_match('/\S+@\S+\.\S+|https?:\/\/|\bwww\./iu', $intro)) {
            $errors['contact_intro'] = 'Leave your number, email or links out of this line. Add them under Contact details and the site shows them to the right people.';
        }
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    update('host_profiles', [
        'service_ids' => implode(',', $ids),
        'service_prices' => hosts_service_prices_json($prices),
        'contact_intro' => setting('host_contact_intro_enabled') ? ($intro !== '' ? $intro : null) : null,
    ], ['user_id' => $hostId]);
    return ['ok' => true, 'errors' => []];
}

function hosts_save_services(int $hostId, array $in): array
{
    if (function_exists('hosts_contact_way') && hosts_contact_way()) {
        return hosts_save_services_contact($hostId, $in);
    }
    $errors = [];
    $min = (float)setting('host_min_rate');
    $catalog = hosts_services_catalog();
    $ids = array_values(array_filter(hosts_ids((array)($in['service_ids'] ?? [])), fn($i) => isset($catalog[$i])));
    $hourly = (float)round((float)($in['hourly_rate'] ?? 0));
    $half = (float)round((float)($in['half_day_rate'] ?? 0));
    $full = (float)round((float)($in['full_day_rate'] ?? 0));
    $minHours = max(1, min(12, (int)($in['min_hours'] ?? 1)));
    $travel = !empty($in['travel_available']);
    $fee = $travel ? (float)round((float)($in['travel_fee'] ?? 0)) : 0.0;
    $rows = [];
    foreach ((array)($in['offer'] ?? []) as $r) {
        if (is_array($r)) {
            $rows[] = $r;
        }
    }
    if (!$ids) {
        $errors['service_ids'] = 'Choose at least one service you offer.';
    } elseif (count($ids) > max(1, (int)setting('host_max_services'))) {
        $errors['service_ids'] = 'You can choose up to ' . (int)setting('host_max_services') . ' services.';
    }
    if ($hourly > 0 && $hourly < $min) {
        $errors['hourly_rate'] = 'The lowest hourly rate allowed is ' . money($min) . '.';
    }
    if ($half > 0 && $half < $hourly) {
        $errors['half_day_rate'] = 'A half-day rate should not be lower than your hourly rate.';
    }
    if ($full > 0 && $half > 0 && $full < $half) {
        $errors['full_day_rate'] = 'A full-day rate should not be lower than your half-day rate.';
    }
    if ($travel && $fee < 0) {
        $errors['travel_fee'] = 'Enter a travel fee of zero or more.';
    }
    $saved = hosts_offerings_save($hostId, $rows);
    if (!$saved['ok']) {
        $errors += $saved['errors'];
    }
    $hasOffer = $saved['ok'] ? $saved['saved'] > 0 : true;
    if ($hourly <= 0 && !$hasOffer && !isset($errors['hourly_rate'])) {
        $errors['hourly_rate'] = 'Set an hourly rate or add at least one package.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    update('host_profiles', [
        'service_ids' => implode(',', $ids), 'hourly_rate' => $hourly > 0 ? $hourly : null, 'half_day_rate' => $half > 0 ? $half : null, 'full_day_rate' => $full > 0 ? $full : null,
        'min_hours' => $minHours, 'travel_available' => $travel ? 1 : 0, 'travel_fee' => $travel && $fee > 0 ? $fee : null,
    ], ['user_id' => $hostId]);
    $ext = ['allow_custom_hours' => !empty($in['allow_custom_hours']) && $hourly > 0 ? 1 : 0];
    if (setting('host_rates_hideable') && ($in['_f'] ?? '') === '1') {
        $ext['show_prices'] = !empty($in['show_prices']) ? 1 : 0;
    }
    hosts_ext_save($hostId, $ext);
    return ['ok' => true, 'errors' => []];
}

function hosts_save_area(int $hostId, array $in): array
{
    $errors = [];
    $loc = (int)($in['location_id'] ?? 0);
    $town = (int)($in['town_id'] ?? 0);
    $pick = $town > 0 ? $town : $loc;
    $base = $pick > 0 ? row('SELECT id, type, parent_id FROM locations WHERE id = :id AND is_active = 1', ['id' => $pick]) : null;
    if (!$base) {
        $errors['location_id'] = 'Choose the county you are based in.';
    } elseif ($town > 0 && ($base['type'] !== 'town' || ($loc > 0 && (int)$base['parent_id'] !== $loc))) {
        $errors['town_id'] = 'That town is not in the county you chose.';
    }
    $areas = array_slice(hosts_ids((array)($in['areas'] ?? [])), 0, 15);
    $valid = $areas ? array_map('intval', col('SELECT id FROM locations WHERE id IN (:ids) AND is_active = 1', ['ids' => $areas])) : [];
    $areas = array_values(array_intersect($areas, $valid));
    if (!$areas) {
        $errors['areas'] = 'Choose at least one area where you meet clients.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    set_user_location_from_location($hostId, (int)$base['id']);
    update('host_profiles', ['service_area_ids' => implode(',', $areas)], ['user_id' => $hostId]);
    return ['ok' => true, 'errors' => []];
}

function hosts_save_payout(int $hostId, array $in): array
{
    $errors = [];
    $phone = phone_normalize(trim((string)($in['payout_phone'] ?? '')));
    $name = mb_substr(trim((string)($in['payout_name'] ?? '')), 0, 100);
    $needPayout = hosts_booking_enabled() || (trim((string)($in['payout_phone'] ?? '')) !== '' || $name !== '');
    if ($needPayout && $phone === '') {
        $errors['payout_phone'] = 'Enter a valid M-Pesa phone number, for example 0712 345 678.';
    }
    if ($needPayout && mb_strlen($name) < 3) {
        $errors['payout_name'] = 'Enter the name registered on the M-Pesa account.';
    }
    $ext = hosts_ext($hostId);
    $agree = !empty($in['agree_rules']);
    if (isset($in['agree_rules_present']) || !$ext['rules_agreed_at']) {
        if (!$agree && !$ext['rules_agreed_at']) {
            $errors['agree_rules'] = 'You need to agree to the Host rules to continue.';
        }
    }
    if (!empty($in['require_age']) && empty($in['confirm_age'])) {
        $errors['confirm_age'] = 'Please confirm that you are 18 or over.';
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    $data = $needPayout ? ['payout_phone' => $phone, 'payout_name' => $name] : [];
    if ($agree && !$ext['rules_agreed_at']) {
        $data += ['rules_agreed_at' => now(), 'rules_agreed_ip' => client_ip()];
    }
    hosts_ext_save($hostId, $data);
    return ['ok' => true, 'errors' => []];
}

/** Save the ID document and selfie from $_FILES (id_file, selfie_file). Existing files are kept when none is uploaded. */
function hosts_save_verification(int $hostId, array $in): array
{
    $errors = [];
    $ext = hosts_ext($hostId);
    $type = (string)($in['id_doc_type'] ?? '');
    $anyFile = !empty(files_list('id_file'));
    if (!host_verify_id_on()) {
        return ['ok' => true, 'errors' => []]; // only the photo check is used, handled on /verification
    }
    if (!isset(HOSTS_ID_TYPES[$type]) && ($anyFile || !empty($ext['id_doc_path']) || !(host_verify_photo_on() && host_verify_rule() === 'any'))) {
        $errors['id_doc_type'] = 'Choose the type of ID you are uploading.';
    }
    if (host_verify_photo_on() && host_verify_rule() === 'any' && !$anyFile && empty($ext['id_doc_path'])) {
        return ['ok' => true, 'errors' => []]; // the ID was skipped on purpose
    }
    $data = ['id_doc_type' => $type];
    $remove = [];
    $idRule = host_verify_id_on() && !(host_verify_photo_on() && host_verify_rule() === 'any'); // with "either is enough" the ID may be skipped
    foreach (['id_file' => ['id_doc_path', 'id', $idRule], 'selfie_file' => ['selfie_path', 'selfie', $idRule && setting('host_selfie_required')]] as $field => [$col, $kind, $required]) {
        $files = files_list($field);
        if ($files) {
            $res = hosts_store_private_image($files[0], $hostId, $kind);
            if (!$res['ok']) {
                $errors[$field] = $res['error'];
                continue;
            }
            $data[$col] = $res['path'];
            if (!empty($ext[$col])) {
                $remove[] = $ext[$col];
            }
        } elseif ($required && empty($ext[$col])) {
            $errors[$field] = $kind === 'id' ? 'Upload a clear photo of your ID.' : 'Upload a selfie of you holding your ID.';
        }
    }
    if ($errors) {
        foreach ($data as $k => $v) {
            if (in_array($k, ['id_doc_path', 'selfie_path'], true) && $v !== ($ext[$k] ?? null)) {
                @unlink(hosts_private_dir() . '/' . $v);
            }
        }
        return ['ok' => false, 'errors' => $errors];
    }
    $data['id_submitted_at'] = now();
    hosts_ext_save($hostId, $data);
    foreach ($remove as $old) {
        @unlink(hosts_private_dir() . '/' . $old);
    }
    return ['ok' => true, 'errors' => []];
}

/** Upload photos from $_FILES['photos']. @return array ['added' => int, 'errors' => [string]] */
function hosts_photos_upload(int $hostId): array
{
    $max = max(1, (int)setting('host_max_photos'));
    $have = (int)val('SELECT COUNT(*) FROM user_photos WHERE user_id = :u', ['u' => $hostId]);
    $added = 0;
    $errors = [];
    foreach (files_list('photos') as $f) {
        if ($have + $added >= $max) {
            $errors[] = 'You can have up to ' . $max . ' photos. Delete one to add another.';
            break;
        }
        $res = process_profile_photo($f, $hostId);
        if (!$res['ok']) {
            $errors[] = ($f['name'] ?? 'Photo') . ': ' . $res['error'];
            continue;
        }
        photo_add($hostId, $res['data']);
        $added++;
    }
    return ['added' => $added, 'errors' => array_values(array_unique($errors))];
}

/** Profile visibility switches and public contact details (user_profiles). */
function hosts_save_visibility(int $hostId, array $in): array
{
    $errors = [];
    $contact = [];
    foreach (['contact_phone' => 'phone', 'contact_whatsapp' => 'phone', 'contact_email' => 'email', 'contact_instagram' => 'text'] as $f => $kind) {
        $v = trim((string)($in[$f] ?? ''));
        if ($v === '') {
            $contact[$f] = null;
        } elseif ($kind === 'phone') {
            $n = phone_normalize($v);
            if ($n === '') {
                $errors[$f] = 'That phone number does not look right.';
            }
            $contact[$f] = $n !== '' ? $n : null;
        } elseif ($kind === 'email') {
            if (!valid_email($v)) {
                $errors[$f] = 'That email address does not look right.';
            }
            $contact[$f] = mb_substr($v, 0, 190);
        } else {
            $contact[$f] = mb_substr(ltrim($v, '@'), 0, 100);
            if (!preg_match('/^[A-Za-z0-9._]{1,100}$/', (string)$contact[$f])) {
                $errors[$f] = 'Use only letters, numbers, dots and underscores.';
            }
        }
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }
    update('user_profiles', $contact + [
        'show_age' => !empty($in['show_age']) ? 1 : 0, 'show_online' => !empty($in['show_online']) ? 1 : 0, 'show_last_active' => !empty($in['show_last_active']) ? 1 : 0,
        'show_distance' => !empty($in['show_distance']) ? 1 : 0, 'show_contact' => !empty($in['show_contact']) ? 1 : 0, 'is_paused' => !empty($in['hide_profile']) ? 1 : 0,
    ], ['user_id' => $hostId]);
    return ['ok' => true, 'errors' => []];
}

// ===========================================================================
// 11. Directory
// ===========================================================================

const HOSTS_SORTS = [
    'recommended' => 'Recommended', 'rating' => 'Top rated', 'newest' => 'Newest',
    'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'nearest' => 'Nearest to me',
];

/** SQL for a host's lowest hourly-equivalent price: the hourly rate or the best package (alias hp = host_profiles). */
const HOSTS_FROM_RATE_SQL = 'NULLIF(LEAST(COALESCE(hp.hourly_rate, 99999999), COALESCE((SELECT MIN(o.price / o.duration_hours) FROM host_offerings o WHERE o.host_id = hp.user_id AND o.is_active = 1), 99999999)), 99999999)';

/** Clean the directory query string into a filter array (unknown or invalid values are dropped). */
function hosts_directory_filters(array $src): array
{
    $str = fn(string $k, int $max) => mb_substr(trim((string)($src[$k] ?? '')), 0, $max);
    $cats = hosts_categories();
    $cat = (int)($src['category'] ?? 0);
    $sort = (string)($src['sort'] ?? 'recommended');
    $date = $str('date', 10);
    $gender = (string)($src['gender'] ?? '');
    $prices = !function_exists('hosts_listing_prices') || hosts_listing_prices();
    $pmin = $prices ? max(0, min(1000000, (int)($src['price_min'] ?? 0))) : 0;
    $pmax = $prices ? max(0, min(1000000, (int)($src['price_max'] ?? 0))) : 0;
    if ($pmax > 0 && $pmin > $pmax) {
        [$pmin, $pmax] = [$pmax, $pmin];
    }
    return [
        'q' => $str('q', 80), 'category' => isset($cats[$cat]) ? $cat : 0,
        'county' => max(0, (int)($src['county'] ?? 0)), 'town' => max(0, (int)($src['town'] ?? 0)),
        'price_min' => $pmin, 'price_max' => $pmax,
        'date' => hosts_valid_date($date) && $date >= date('Y-m-d') ? $date : '',
        'gender' => in_array($gender, ['male', 'female', 'non-binary'], true) ? $gender : '',
        'verified' => !empty($src['verified']) ? 1 : 0,
        'distance' => in_array((int)($src['distance'] ?? 0), [5, 10, 25, 50, 100], true) ? (int)$src['distance'] : 0,
        'sort' => isset((function_exists('hosts_sorts') ? hosts_sorts() : HOSTS_SORTS)[$sort]) ? $sort : 'recommended',
    ];
}

/** Number of public Hosts per category id (for the category chips). */
function hosts_category_counts(): array
{
    $out = [];
    foreach (rows("SELECT hp.category_id, COUNT(*) AS c FROM host_profiles hp JOIN users u ON u.id = hp.user_id
                   WHERE hp.approval_status = 'approved' AND u.status = 'active' AND hp.category_id IS NOT NULL" . hosts_listing_sql('hp') . " GROUP BY hp.category_id") as $r) {
        $out[(int)$r['category_id']] = (int)$r['c'];
    }
    return $out;
}

/**
 * The Hosts directory. Host-specific filters run in SQL; blocks, privacy, photo and distance rules come from members_search();
 * sorting and paging happen in PHP over at most 800 candidates.
 *
 * @param array $f filters from hosts_directory_filters()
 * @return array ['rows' => decorated members, 'total' => int, 'page' => int, 'pages' => int, 'per_page' => int]
 */
function hosts_directory(array $f, int $page = 1, int $perPage = 12): array
{
    $page = max(1, $page);
    $perPage = max(1, min(48, $perPage));
    $f += ['q' => '', 'category' => 0, 'county' => 0, 'town' => 0, 'price_min' => 0, 'price_max' => 0, 'date' => '', 'gender' => '', 'verified' => 0, 'distance' => 0, 'sort' => 'recommended'];
    $rate = HOSTS_FROM_RATE_SQL;
    $where = ["hp.approval_status = 'approved'", "u.status = 'active'"];
    if (hosts_listing_required()) {
        $where[] = ltrim(hosts_listing_sql('hp'), ' AND');
    }
    $p = [];
    if ($f['category'] > 0) {
        $where[] = 'hp.category_id = :cat';
        $p['cat'] = (int)$f['category'];
    }
    if ($f['verified']) {
        $where[] = HOST_VERIFIED_SQL;
    }
    if ($f['q'] !== '') {
        $where[] = '(hp.display_name LIKE :q OR hp.tagline LIKE :q OR ul.town_name LIKE :q OR ul.county_name LIKE :q
            OR EXISTS (SELECT 1 FROM host_services s WHERE FIND_IN_SET(s.id, hp.service_ids) AND s.name LIKE :q)
            OR EXISTS (SELECT 1 FROM host_offerings o WHERE o.host_id = hp.user_id AND o.is_active = 1 AND o.name LIKE :q))';
        $p['q'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $f['q']) . '%';
    }
    if ($f['price_min'] > 0) {
        $where[] = "$rate >= :pmin";
        $p['pmin'] = (int)$f['price_min'];
    }
    if ($f['price_max'] > 0) {
        $where[] = "$rate <= :pmax";
        $p['pmax'] = (int)$f['price_max'];
    }
    if ($f['town'] > 0) {
        $where[] = '(ul.location_id = :town OR FIND_IN_SET(:town, hp.service_area_ids))';
        $p['town'] = (int)$f['town'];
    } elseif ($f['county'] > 0) {
        $where[] = '(ul.county_id = :county OR FIND_IN_SET(:county, hp.service_area_ids)
            OR EXISTS (SELECT 1 FROM locations l WHERE FIND_IN_SET(l.id, hp.service_area_ids) AND l.parent_id = :county))';
        $p['county'] = (int)$f['county'];
    }
    $cand = rows(
        "SELECT hp.user_id, hp.min_hours, hp.hourly_rate, hp.rating_avg, hp.review_count, hp.total_bookings, hp.is_featured, hp.approved_at,
                COALESCE(x.allow_custom_hours, 1) AS allow_custom, COALESCE(x.bookings_paused, 0) AS paused, $rate AS from_rate,
                (SELECT MIN(o.duration_hours) FROM host_offerings o WHERE o.host_id = hp.user_id AND o.is_active = 1) AS min_offer
         FROM host_profiles hp JOIN users u ON u.id = hp.user_id LEFT JOIN host_profile_ext x ON x.user_id = hp.user_id
         LEFT JOIN user_locations ul ON ul.user_id = hp.user_id
         WHERE " . implode(' AND ', $where) . ' ORDER BY hp.rating_avg DESC, hp.user_id LIMIT 800', $p
    );
    $by = [];
    foreach ($cand as $c) {
        $by[(int)$c['user_id']] = $c;
    }
    if ($f['date'] !== '' && $by) {
        $minHours = [];
        foreach ($by as $id => $c) {
            if ((int)$c['paused'] === 1) {
                continue;
            }
            $opts = [];
            if ((float)$c['hourly_rate'] > 0 && (int)$c['allow_custom'] === 1) {
                $opts[] = max(0.5, (float)$c['min_hours']);
            }
            if ($c['min_offer'] !== null) {
                $opts[] = (float)$c['min_offer'];
            }
            if ($opts) {
                $minHours[$id] = min($opts);
            }
        }
        $ok = hosts_ids_available_on($minHours, (string)$f['date']);
        $by = array_intersect_key($by, array_flip($ok));
    }
    if (!$by) {
        return ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage];
    }
    $mf = ['role' => 'host', 'only_ids' => array_keys($by), 'order' => 'newest'];
    if ($f['gender'] !== '') {
        $mf['gender'] = [$f['gender']];
    }
    if ($f['distance'] > 0 && viewer_coords()) {
        $mf['distance_km'] = (int)$f['distance'];
    }
    $found = members_search($mf, 800, 0)['rows'];
    $hasCoords = viewer_coords() !== null;
    $sort = $f['sort'] === 'nearest' && !$hasCoords ? 'recommended' : $f['sort'];
    $score = function (array $m) use ($by, $sort): float {
        $c = $by[$m['id']];
        $rating = (float)$c['rating_avg'];
        $reviews = (int)$c['review_count'];
        switch ($sort) {
            case 'rating':
                return $rating * 1000 + min($reviews, 999);
            case 'newest':
                return (float)strtotime((string)($c['approved_at'] ?: $m['joined_at']));
            case 'price_asc':
                return $c['from_rate'] === null ? -1e12 : -(float)$c['from_rate'];
            case 'price_desc':
                return $c['from_rate'] === null ? -1e12 : (float)$c['from_rate'];
            case 'nearest':
                return $m['distance_km'] === null ? -1e9 : -(float)$m['distance_km'] * 1000 + $rating;
        }
        $s = ($m['featured'] || (int)$c['is_featured'] ? 100 : 0) + ($m['boosted'] ? 50 : 0) + $rating * 8 * min(1.0, ($reviews + 1) / 10)
            + min(10, (int)$c['total_bookings']) + ($m['online'] ? 5 : 0) + ((int)$c['paused'] ? -30 : 0);
        if ($m['distance_km'] !== null) {
            $s -= min(20.0, (float)$m['distance_km'] / 5);
        }
        if (!empty($m['raw']['last_active_at']) && strtotime((string)$m['raw']['last_active_at']) > time() - 2 * 86400) {
            $s += 4;
        }
        return $s;
    };
    usort($found, function ($a, $b) use ($score) {
        return $score($b) <=> $score($a) ?: $a['id'] <=> $b['id'];
    });
    $total = count($found);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    return ['rows' => hosts_decorate(array_slice($found, ($page - 1) * $perPage, $perPage)), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
}

/** Add a 'host' array (name, tagline, category, from_rate, rating, services ...) to normalised member rows. */
function hosts_decorate(array $members): array
{
    if (!$members) {
        return [];
    }
    $ids = array_map('intval', array_column($members, 'id'));
    $rate = HOSTS_FROM_RATE_SQL;
    $info = [];
    foreach (rows(
        "SELECT hp.user_id, hp.display_name, hp.tagline, hp.category_id, hp.hourly_rate, hp.min_hours, hp.service_ids, hp.service_area_ids, hp.rating_avg, hp.review_count, hp.id_verified, hp.photo_verified,
                hp.is_featured, hp.total_bookings, hp.travel_available, hp.service_prices, COALESCE(x.instant_booking, 0) AS instant_booking, COALESCE(x.bookings_paused, 0) AS bookings_paused, COALESCE(x.show_prices, 1) AS show_prices, $rate AS from_rate
         FROM host_profiles hp LEFT JOIN host_profile_ext x ON x.user_id = hp.user_id WHERE hp.user_id IN (:ids)", ['ids' => $ids]
    ) as $r) {
        $info[(int)$r['user_id']] = $r;
    }
    $cats = hosts_categories();
    $catalog = hosts_services_catalog();
    $contactWay = function_exists('hosts_contact_way') && hosts_contact_way();
    foreach ($members as &$m) {
        $r = $info[(int)$m['id']] ?? null;
        if (!$r) {
            $m['host'] = null;
            continue;
        }
        $services = [];
        foreach (hosts_ids($r['service_ids']) as $sid) {
            if (isset($catalog[$sid])) {
                $services[] = (string)$catalog[$sid]['name'];
            }
        }
        // the contact way shows the services themselves on the card and no price at all: an agent's charges
        // belong on their own profile, not on the cards that lead to it
        $cards = $contactWay ? hosts_service_cards($r, false) : [];
        $cat = $cats[(int)$r['category_id']] ?? null;
        $name = trim((string)$r['display_name']) ?: $m['name'];
        $m['host'] = [
            'name' => $name, 'first_name' => explode(' ', $name)[0], 'tagline' => (string)$r['tagline'], 'category' => $cat['name'] ?? '', 'category_slug' => $cat['slug'] ?? '',
            'from_rate' => $r['from_rate'] !== null ? (float)round((float)$r['from_rate']) : null, 'rating' => (float)$r['rating_avg'], 'reviews' => (int)$r['review_count'],
            'id_verified' => (int)$r['id_verified'] === 1, 'photo_verified' => (int)$r['photo_verified'] === 1, 'verified' => (int)$r['id_verified'] === 1 || (int)$r['photo_verified'] === 1, 'verify_label' => host_verify_badge(['id_verified' => $r['id_verified'], 'photo_verified' => $r['photo_verified']]), 'instant' => (int)$r['instant_booking'] === 1 && (bool)setting('host_instant_booking_enabled'),
            'paused' => (int)$r['bookings_paused'] === 1, 'prices_hidden' => !hosts_prices_allowed($r['show_prices']), 'services' => $services, 'bookings' => (int)$r['total_bookings'], 'travel' => (int)$r['travel_available'] === 1,
            'cards' => $cards, 'from_price' => null,
        ];
    }
    unset($m);
    return $members;
}

/** Featured Hosts (admin-featured or boosted), best first, for the row above the directory results. */
function hosts_featured_list(int $limit): array
{
    if ($limit < 1) {
        return [];
    }
    $ids = array_map('intval', col("SELECT hp.user_id FROM host_profiles hp JOIN users u ON u.id = hp.user_id
        WHERE hp.approval_status = 'approved' AND u.status = 'active' AND (hp.is_featured = 1 OR (u.featured_until IS NOT NULL AND u.featured_until > NOW()))" . hosts_listing_sql('hp') . "
        ORDER BY hp.rating_avg DESC, hp.user_id LIMIT 60"));
    if (!$ids) {
        return [];
    }
    $found = members_search(['role' => 'host', 'only_ids' => $ids, 'order' => 'featured'], $limit, 0)['rows'];
    return hosts_decorate($found);
}

/**
 * Everything the public profile page needs, built without the directory's visibility rules (the page decides who may look).
 * Returns null when the user has no host profile.
 */
function hosts_view(int $id): ?array
{
    $hp = hosts_profile($id);
    if (!$hp) {
        return null;
    }
    $params = ['id' => $id];
    $dist = 'NULL';
    $coords = viewer_coords();
    if ($coords) {
        $dist = sql_distance(':vlat', ':vlng', 'ul.latitude', 'ul.longitude');
        $params += ['vlat' => $coords[0], 'vlng' => $coords[1]];
    }
    $raw = row('SELECT ' . MEMBER_CARD_COLS . ', ' . SQL_PHOTO_COLS . ', ' . $dist . ' AS distance_km
                FROM users u JOIN user_profiles p ON p.user_id = u.id LEFT JOIN user_locations ul ON ul.user_id = u.id ' . sql_photo_join('u.id', 'ph') . ' WHERE u.id = :id', $params);
    if (!$raw) {
        return null;
    }
    $m = member_normalize($raw);
    $cats = hosts_categories();
    $catalog = hosts_services_catalog();
    $services = [];
    foreach (hosts_ids($hp['service_ids']) as $sid) {
        if (isset($catalog[$sid])) {
            $services[$sid] = $catalog[$sid];
        }
    }
    $langs = array_values(array_filter(array_map('trim', explode(',', (string)val('SELECT languages FROM user_profiles WHERE user_id = :u', ['u' => $id])))));
    $offerings = hosts_offerings($id);
    return [
        'm' => $m, 'hp' => $hp, 'name' => trim((string)$hp['display_name']) ?: $m['name'], 'category' => $cats[(int)$hp['category_id']] ?? null,
        'services' => $services, 'areas' => hosts_locations_by_ids(hosts_ids($hp['service_area_ids'])), 'offerings' => $offerings, 'languages' => $langs,
        'from_rate' => hosts_from_rate($hp, $offerings),
    ];
}

// ---------------------------------------------------------------------------
// Demo data
// ---------------------------------------------------------------------------

/**
 * Give a demo Host packages, payout details and a few completed bookings with reviews so the pages look lived in.
 * Called by demo_seed() for every demo Host it creates. Safe to call twice.
 */
function hosts_demo_enrich(int $hostId, int $index = 0): void
{
    $hp = hosts_profile($hostId);
    if (!$hp) {
        return;
    }
    hosts_ext_save($hostId, [
        'instant_booking' => $index % 3 === 0 ? 1 : 0, 'allow_custom_hours' => 1, 'payout_phone' => '2547' . sprintf('%08d', 10000000 + $hostId * 37),
        'payout_name' => $hp['display_name'], 'rules_agreed_at' => now(), 'rules_agreed_ip' => '127.0.0.1',
    ]);
    $hourly = (float)($hp['hourly_rate'] ?: 2000);
    if (!(int)val('SELECT COUNT(*) FROM host_offerings WHERE host_id = :h', ['h' => $hostId])) {
        $svc = array_keys(hosts_services_catalog());
        $packs = [
            ['Dinner company', 'A relaxed evening at a restaurant of your choice.', 2.0, $hourly * 2],
            ['City tour', 'A walk or drive around the places worth seeing.', 4.0, (float)($hp['half_day_rate'] ?: $hourly * 3.5)],
            ['Event plus-one', 'Company for a wedding, launch, party or concert.', 3.0, $hourly * 3],
            ['Conversation session', 'An hour of good talk over coffee.', 1.0, $hourly],
        ];
        foreach ($packs as $i => [$name, $desc, $hrs, $price]) {
            insert('host_offerings', ['host_id' => $hostId, 'service_id' => $svc[$i % max(1, count($svc))] ?? null, 'name' => $name, 'description' => $desc,
                'duration_hours' => $hrs, 'price' => round($price / 50) * 50, 'is_active' => 1, 'sort_order' => $i]);
        }
    }
    if ((int)val('SELECT COUNT(*) FROM host_bookings WHERE host_id = :h', ['h' => $hostId])) {
        return;
    }
    $clients = array_map('intval', col("SELECT id FROM users WHERE role = 'user' AND email LIKE 'demo%' AND status = 'active' ORDER BY id DESC LIMIT 12"));
    if (!$clients) {
        return;
    }
    $texts = [
        [5, 'Easy to talk to and arrived early. We had a lovely dinner and the time flew.'],
        [5, 'Professional, warm and exactly as described. I would book again.'],
        [4, 'Great company and good suggestions for where to go. Slightly late but let me know in advance.'],
        [5, 'Made me feel at ease at an event where I knew nobody. Thank you.'],
        [4, 'A really nice afternoon. Good conversation throughout.'],
    ];
    $hpFull = hosts_profile($hostId);
    $offers = hosts_offerings($hostId);
    for ($k = 0; $k < 3 && $k < count($clients); $k++) {
        $offer = $offers[$k % max(1, count($offers))] ?? null;
        $q = hosts_quote($hpFull, $offer, $offer ? (float)$offer['duration_hours'] : 2.0);
        $date = date('Y-m-d', strtotime('-' . (9 + $k * 8 + $index) . ' days'));
        $bid = insert('host_bookings', [
            'host_id' => $hostId, 'client_id' => $clients[($index + $k) % count($clients)], 'booking_date' => $date, 'start_time' => '18:30:00', 'duration_hours' => $q['hours'],
            'location_note' => 'Hero Restaurant, Westlands', 'total_amount' => $q['total'], 'deposit_amount' => $q['deposit'], 'commission_pct' => $q['commission_pct'],
            'commission_amount' => $q['commission'], 'host_earning' => $q['host_earning'], 'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', strtotime($date . ' -3 days')),
        ]);
        insert('host_booking_ext', ['booking_id' => $bid, 'offering_id' => $offer['id'] ?? null, 'offering_name' => $offer['name'] ?? 'Custom time', 'base_amount' => $q['base'],
            'paid_at' => date('Y-m-d H:i:s', strtotime($date . ' -3 days')), 'confirmed_at' => date('Y-m-d H:i:s', strtotime($date . ' -2 days')),
            'completed_at' => $date . ' 22:00:00', 'closed_at' => $date . ' 22:00:00']);
        hosts_settle(hosts_booking_get($bid), (float)$q['total'], true);
        update('host_booking_ext', ['settled_at' => date('Y-m-d H:i:s', strtotime($date . ' +1 day'))], ['booking_id' => $bid]);
        [$rating, $text] = $texts[($index + $k) % count($texts)];
        insert('host_reviews', ['host_id' => $hostId, 'reviewer_id' => $clients[($index + $k) % count($clients)], 'booking_id' => $bid, 'rating' => $rating,
            'review_text' => $text, 'is_anonymous' => $k % 2, 'status' => 'approved', 'created_at' => $date . ' 23:10:00']);
    }
    hosts_rating_recalc($hostId);
}

// ===========================================================================
// 12. Page helpers: access gates, reports, calendar
// ===========================================================================

const HOSTS_REPORT_REASONS = ['Sexual services', 'Fake profile or photos', 'Harassment or unsafe', 'Payment outside platform', 'Spam or scam', 'Under 18', 'Something else'];

/**
 * Can the current visitor open the Hosts directory? Admins and Hosts always can; everyone else follows Settings
 * (hosts_directory_access) and, for visitors, the 18+ confirmation. Returns [bool allowed, string reason: ok|disabled|login|upgrade|age].
 */
function hosts_directory_gate(): array
{
    if (!hosts_enabled()) {
        return [false, 'disabled'];
    }
    $u = current_user();
    if ($u && in_array($u['role'], ['admin', 'host'], true)) {
        return [true, 'ok'];
    }
    [$ok, $reason] = hosts_directory_allowed();
    if (!$ok) {
        return [false, (string)$reason];
    }
    $mustConfirm = !$u || (setting('hosts_age_gate_scope') === 'everyone' && ($u['role'] ?? '') === 'user');
    if ($mustConfirm && setting('hosts_age_gate') && empty($_SESSION['hosts_age_ok'])) {
        return [false, 'age'];
    }
    return [true, 'ok'];
}

/** Same as the directory gate, but a client who already has a booking with this Host may always open their page. */
function hosts_profile_gate(int $hostId): array
{
    [$ok, $reason] = hosts_directory_gate();
    if ($ok) {
        return [true, 'ok'];
    }
    $u = current_user();
    if ($u && $reason !== 'disabled' && (int)val('SELECT COUNT(*) FROM host_bookings WHERE host_id = :h AND client_id = :c', ['h' => $hostId, 'c' => (int)$u['id']]) > 0) {
        return [true, 'ok'];
    }
    return [false, $reason];
}

/** Who may look at a Host's page: the public (when approved and visible), the Host themself and admins. */
function hosts_can_view(?array $hp): bool
{
    if (!$hp) {
        return false;
    }
    $u = current_user();
    if ($u && (viewer_is_staff(['hosts', 'users']) || (int)$u['id'] === (int)$hp['user_id'])) {
        return true;
    }
    if (!hosts_is_public($hp) || (int)val('SELECT is_paused FROM user_profiles WHERE user_id = :u', ['u' => (int)$hp['user_id']], 0) === 1) {
        return false;
    }
    return !($u && users_blocked((int)$u['id'], (int)$hp['user_id']));
}

/** Report a Host or one of your bookings. Same table and notification as /api/report, with reasons written for Hosts. @return array ['ok', 'error', 'message'] */
function hosts_report(int $reporterId, string $type, int $targetId, string $reason, string $details): array
{
    $fail = fn(string $m) => ['ok' => false, 'error' => $m, 'message' => ''];
    if (!in_array($reason, HOSTS_REPORT_REASONS, true)) {
        return $fail('Choose a reason from the list.');
    }
    $details = trim(mb_substr($details, 0, 1000));
    if ($type === 'booking') {
        $b = row('SELECT host_id, client_id FROM host_bookings WHERE id = :id', ['id' => $targetId]);
        if (!$b || ((int)$b['host_id'] !== $reporterId && (int)$b['client_id'] !== $reporterId)) {
            return $fail('That booking could not be found.');
        }
        $reported = (int)$b['host_id'] === $reporterId ? (int)$b['client_id'] : (int)$b['host_id'];
    } else {
        $type = 'host';
        $reported = $targetId;
    }
    $who = row('SELECT id, role FROM users WHERE id = :id', ['id' => $reported]);
    if (!$who || $who['role'] === 'admin') {
        return $fail('That member could not be found.');
    }
    if ($reported === $reporterId) {
        return $fail('You cannot report yourself.');
    }
    if ($type === 'host' && $who['role'] !== 'host') {
        return $fail('That member could not be found.');
    }
    $ext = function_exists('explorer_reports_extended') && explorer_reports_extended();
    $dup = 'SELECT COUNT(*) FROM reports WHERE reporter_id = :me AND reported_id = :r AND status IN (\'pending\',\'reviewing\') AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)';
    $dupP = ['me' => $reporterId, 'r' => $reported];
    if ($ext) {
        $dup .= ' AND target_type = :tt AND (target_id <=> :ti)';
        $dupP += ['tt' => $type, 'ti' => $type === 'host' ? null : $targetId];
    }
    if ((int)val($dup, $dupP) > 0) {
        return $fail('You have already reported this. Our team is reviewing it.');
    }
    $desc = ($type === 'booking' ? '[Booking #' . $targetId . '] ' : '[Host] ') . $details;
    $data = ['reporter_id' => $reporterId, 'reported_id' => $reported, 'reason' => mb_substr($reason, 0, $ext ? 80 : 30), 'description' => $desc];
    if ($ext) {
        $data += ['target_type' => $type, 'target_id' => $type === 'host' ? null : $targetId];
    }
    $id = insert('reports', $data);
    foreach (col("SELECT id FROM users WHERE role = 'admin' AND status = 'active' ORDER BY id LIMIT 20") as $adminId) {
        notify((int)$adminId, 'system', 'New report: ' . $reason, 'A member reported a ' . $type . '. Open the reports queue to review it.', '/admin/reports', $reporterId, ['report' => $id]);
    }
    return ['ok' => true, 'error' => '', 'message' => 'Thank you. Our team will review your report and act on it if it breaks the rules.'];
}

/**
 * Month-by-month availability for the profile and booking pages.
 * @param array $counts [Y-m-d => free start times] from hosts_available_days()
 * @return array list of months: ['label' => 'October 2026', 'ym' => '2026-10', 'weeks' => [[cell|null x7], ...]]
 *               cell: ['date', 'day', 'count', 'past' => bool, 'today' => bool, 'in_range' => bool]
 */
function hosts_calendar_months(array $counts, string $from, int $days): array
{
    $months = [];
    $start = new DateTimeImmutable($from);
    $end = $start->modify('+' . max(0, $days - 1) . ' day');
    $cursor = $start->modify('first day of this month');
    $today = date('Y-m-d');
    while ($cursor <= $end) {
        $first = $cursor;
        $dim = (int)$first->format('t');
        $lead = (int)$first->format('N') - 1;
        $cells = array_fill(0, $lead, null);
        for ($d = 1; $d <= $dim; $d++) {
            $date = $first->setDate((int)$first->format('Y'), (int)$first->format('n'), $d)->format('Y-m-d');
            $inRange = $date >= $from && $date <= $end->format('Y-m-d');
            $cells[] = ['date' => $date, 'day' => $d, 'count' => (int)($counts[$date] ?? 0), 'past' => $date < $today, 'today' => $date === $today, 'in_range' => $inRange];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }
        $months[] = ['label' => $first->format('F Y'), 'ym' => $first->format('Y-m'), 'weeks' => array_chunk($cells, 7)];
        $cursor = $cursor->modify('first day of next month');
    }
    return $months;
}

/** Shortest length (hours) a client can book from this Host: the smaller of the minimum custom length and the shortest package. */
function hosts_min_booking_hours(array $hp, array $offerings): float
{
    $opts = [];
    if ((float)($hp['hourly_rate'] ?? 0) > 0 && (int)($hp['allow_custom_hours'] ?? 1) === 1) {
        $opts[] = max(0.5, (float)($hp['min_hours'] ?? 1));
    }
    foreach ($offerings as $o) {
        $opts[] = (float)$o['duration_hours'];
    }
    return $opts ? min($opts) : 1.0;
}

/** Stars row. $rating 0 to 5, one decimal. */
function hosts_stars(float $rating, string $class = ''): string
{
    $r = max(0.0, min(5.0, $rating));
    $row = str_repeat(icon('star', 'w-4 h-4'), 5);
    return '<span class="stars ' . e($class) . '" style="--r:' . number_format($r, 2, '.', '') . '" role="img" aria-label="Rated ' . e(number_format($r, 1)) . ' out of 5"><span class="stars-bg" aria-hidden="true">' . $row . '</span><span class="stars-fg" aria-hidden="true">' . $row . '</span></span>';
}

/** Use the signed-in member shell for pages that serve both visitors and members. */
function hosts_use_app_layout(): void
{
    $u = current_user();
    if ($u && in_array($u['role'], ['user', 'host'], true)) {
        page(['layout' => 'app']);
    }
}

/** Load a Host for a JSON endpoint: 404 when the viewer may not see them, 403 when the directory is gated for the viewer. */
function hosts_api_host(int $id): array
{
    $hp = hosts_profile($id);
    if (!$hp || !hosts_can_view($hp)) {
        json_fail('Host not found.', 404);
    }
    if (!hosts_profile_gate($id)[0]) {
        json_fail('Sign in to see availability.', 403);
    }
    return $hp;
}

/** Hours for a request: from a package, or the custom length checked against this Host's limits. Returns [offering|null, hours|0]. */
function hosts_resolve_hours(array $hp, $offeringId, $hours): array
{
    $offering = null;
    $oid = (int)$offeringId;
    if ($oid > 0) {
        $offering = hosts_offering($oid, (int)$hp['user_id']);
        if (!$offering || !(int)$offering['is_active']) {
            return [null, 0.0];
        }
        return [$offering, (float)$offering['duration_hours']];
    }
    if (!(int)$hp['allow_custom_hours'] || (float)$hp['hourly_rate'] <= 0) {
        return [null, 0.0];
    }
    $max = min((float)setting('host_booking_max_hours'), $hp['max_hours'] !== null ? (float)$hp['max_hours'] : 24.0);
    $min = max(0.5, (float)$hp['min_hours']);
    $h = round((float)$hours * 2) / 2;
    return [null, ($h >= $min && $h <= $max) ? $h : 0.0];
}

/** The signed-in Host's profile row (with extension), creating an empty draft when an account has none yet. */
function hosts_my(): array
{
    $u = current_user();
    $uid = (int)($u['id'] ?? 0);
    $hp = $uid ? hosts_profile($uid) : null;
    if ($uid && !$hp && ($u['role'] ?? '') === 'host') {
        q('INSERT IGNORE INTO host_profiles (user_id, display_name, approval_status) VALUES (:u, :n, \'draft\')', ['u' => $uid, 'n' => mb_substr((string)$u['name'], 0, 100) ?: 'Host']);
        $hp = hosts_profile($uid);
    }
    if (!$hp) {
        abort(403, 'This page is for Host accounts.');
    }
    return $hp;
}

/** Step key to open first on /host/setup: the first part of the application that is not finished. */
function hosts_first_open_step(int $hostId): string
{
    foreach (hosts_completeness($hostId)['items'] as $i) {
        if (!$i['done']) {
            return (string)$i['key'];
        }
    }
    return 'review';
}
