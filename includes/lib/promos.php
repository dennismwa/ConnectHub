<?php
/**
 * Promotions: businesses buy a package on /advertise, pay at the end, and their picture runs for the package's
 * length of time. The rows live in the same `advertisements` table the admin already manages.
 *
 *   draft      status 'unpaid'   saved when the form is sent; nothing shows, nothing is owed yet
 *   paid       status 'pending'  waits for an admin (Settings > Advertising > "Check each paid advert") ...
 *              status 'active'   ... or goes straight on. starts_at / expires_at are set when it goes on.
 *   over       expires_at passes  the advert is simply no longer picked; no job has to run for that to be true
 *
 * CSS classes and files here use "spot" and never the word that blockers look for.
 */

declare(strict_types=1);

const PROMO_TOKEN_LEN = 24;

function promo_enabled(): bool
{
    try {
        return (bool)setting('ads_enabled') && (bool)setting('promo_enabled');
    } catch (Throwable $e) {
        return false;
    }
}

function promo_review_on(): bool
{
    return (bool)setting('promo_review');
}

/** Where a package can show: key => label for the advertiser. */
function promo_where_options(): array
{
    return [
        'landing' => 'Home page',
        'discover' => 'Discover feed',
        'search' => 'Search results',
        'browse' => 'Dating browse pages',
        'elite' => 'Elite Connections',
        'hosts' => (function_exists('host_term') ? host_term(true) : 'Hosts') . ' directory',
        'messages' => 'Messages and billing',
    ];
}

/** Keep only places that exist, in the usual order. */
function promo_clean_places($raw): array
{
    $list = is_array($raw) ? $raw : explode(',', (string)$raw);
    $known = array_keys(promo_where_options());
    $out = [];
    foreach ($known as $k) {
        if (in_array($k, array_map('trim', $list), true)) {
            $out[] = $k;
        }
    }
    return $out;
}

function promo_places_label(array $places): string
{
    $o = promo_where_options();
    $names = [];
    foreach ($places as $p) {
        if (isset($o[$p])) {
            $names[] = $o[$p];
        }
    }
    return implode(', ', $names);
}

/** @return array<int,array> packages, ordered the way the admin arranged them */
function promo_packages(bool $activeOnly = true): array
{
    try {
        $rows = rows('SELECT * FROM ad_packages' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id');
    } catch (Throwable $e) {
        return [];
    }
    foreach ($rows as &$r) {
        $r['places'] = promo_clean_places((string)$r['placements']);
        $r['days'] = max(1, (int)$r['days']);
        $r['price'] = (float)$r['price'];
    }
    return $rows;
}

function promo_package(int $id, bool $activeOnly = true): ?array
{
    foreach (promo_packages($activeOnly) as $p) {
        if ((int)$p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

/** "7 days", "2 weeks", "1 month": the length in words an advertiser reads at a glance. */
function promo_days_text(int $days): string
{
    if ($days % 30 === 0 && $days >= 30) {
        return plural((int)($days / 30), 'month');
    }
    if ($days % 7 === 0 && $days >= 14) {
        return plural((int)($days / 7), 'week');
    }
    return plural($days, 'day');
}

/** One rule per line, as bullet text. */
function promo_rules(): array
{
    $lines = preg_split('/\R/', (string)setting('promo_rules')) ?: [];
    return array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
}

function promo_cta_options(): array
{
    return ['Learn more', 'Visit us', 'Shop now', 'Book now', 'Order now', 'Get the offer', 'Call us', 'Sign up'];
}

/** The advert is being shown right now. Same rule as ad_pick(), as SQL. */
function promo_live_sql(string $a = 'a'): string
{
    return "($a.status = 'active' AND $a.starts_at <= NOW() AND $a.expires_at > NOW() AND ($a.max_views IS NULL OR $a.views < $a.max_views) AND ($a.max_clicks IS NULL OR $a.clicks < $a.max_clicks))";
}

/** Whole days left, rounding up (a few hours left reads as "1 day"). 0 when it is over. */
function promo_days_left(array $ad): int
{
    $end = strtotime((string)$ad['expires_at']);
    return $end > time() ? (int)ceil(($end - time()) / 86400) : 0;
}

// ---------------------------------------------------------------------------
// The form
// ---------------------------------------------------------------------------

function promo_valid_url(string $v): bool
{
    $v = trim($v);
    if (mb_strlen($v) > 500 || !preg_match('#^https?://[^\s<>"\'\\\\]+\.[^\s<>"\'\\\\]+$#i', $v)) {
        return false;
    }
    $host = strtolower((string)parse_url($v, PHP_URL_HOST));
    // a real name, never "localhost" or a bare address
    return $host !== '' && $host !== 'localhost' && strpos($host, '.') !== false && filter_var($host, FILTER_VALIDATE_IP) === false;
}

/**
 * Check what the advertiser typed. Returns [clean values, errors by field]. The picture is checked separately.
 *
 * @return array{0:array<string,mixed>,1:array<string,string>}
 */
function promo_clean_form(array $in): array
{
    $one = fn(string $k, int $max) => mb_substr(trim(preg_replace('/\s+/', ' ', (string)($in[$k] ?? '')) ?? ''), 0, $max);
    $d = [
        'package' => (int)($in['package'] ?? 0),
        'business' => $one('business', 100),
        'email' => strtolower($one('email', 190)),
        'phone' => $one('phone', 25),
        'headline' => $one('headline', 80),
        'text' => $one('text', 160),
        'link' => trim((string)($in['link'] ?? '')),
        'cta' => $one('cta', 40),
    ];
    $e = [];
    if (!promo_package($d['package'])) {
        $e['package'] = 'Choose a package.';
    }
    if (mb_strlen($d['business']) < 2) {
        $e['business'] = 'Enter your business or brand name.';
    }
    if (!valid_email($d['email'])) {
        $e['email'] = 'Enter an email address we can reach you on.';
    }
    if ($d['phone'] !== '' && !preg_match('/^[0-9+()\-\s]{6,25}$/', $d['phone'])) {
        $e['phone'] = 'Use digits, for example 0712 345 678.';
    }
    if (mb_strlen($d['headline']) < 3) {
        $e['headline'] = 'Write a short headline, 3 to 60 characters.';
    } elseif (mb_strlen($d['headline']) > 60) {
        $e['headline'] = 'Keep the headline to 60 characters so it fits the card.';
    }
    if (mb_strlen($d['text']) > 120) {
        $e['text'] = 'Keep the line under the headline to 120 characters.';
    }
    if ($d['link'] === '') {
        $e['link'] = 'Add the web address people should land on.';
    } elseif (!promo_valid_url($d['link'])) {
        $e['link'] = 'Use a full web address that starts with https://';
    }
    if ($d['cta'] === '' || !in_array($d['cta'], promo_cta_options(), true)) {
        $d['cta'] = 'Learn more';
    }
    if (empty($in['agree'])) {
        $e['agree'] = 'Tick the box to confirm your advert follows the rules.';
    }
    return [$d, $e];
}

// ---------------------------------------------------------------------------
// The picture: re-encoded, sized for a small card at 2x, plus a lighter copy for phones
// ---------------------------------------------------------------------------

function promo_small_path(string $path): string
{
    return (string)preg_replace('/\.(jpe?g|png|webp)$/i', '-m.jpg', $path);
}

/** @return array{ok:bool,error:string,path:string} */
function promo_image_save(array $file): array
{
    $problem = image_upload_problem($file, 6, 0);
    if ($problem !== '') {
        return ['ok' => false, 'error' => $problem, 'path' => ''];
    }
    $info = @getimagesize((string)$file['tmp_name']);
    if (!$info || $info[0] < 480 || $info[1] < 270) {
        return ['ok' => false, 'error' => 'That picture is too small and would look blurry. Use one at least 480 pixels wide. 1200 x 750 works best.', 'path' => ''];
    }
    $r = save_image($file, 'spot', 1400, false, false, 6);
    if (!$r['ok']) {
        return $r;
    }
    try {
        $img = image_load(upload_abs($r['path']));
        if (imagesx($img) > 760) {
            image_save_jpeg(image_fit($img, 720), upload_abs(promo_small_path($r['path'])), 84);
        }
    } catch (Throwable $e) {
        // the full size copy is enough on its own
    }
    return $r;
}

function promo_image_delete(?string $path): void
{
    if (!$path) {
        return;
    }
    upload_delete($path);
    upload_delete(promo_small_path($path));
}

/**
 * Attributes for an <img>: the light copy for phones and the full one for sharp screens.
 * @return array{src:string,srcset:string}
 */
function promo_img(?string $path): array
{
    if (!$path) {
        return ['src' => '', 'srcset' => ''];
    }
    $full = upload_url($path);
    $small = promo_small_path($path);
    if ($small !== $path && is_file(upload_abs($small))) {
        $w = (int)((@getimagesize(upload_abs($path)) ?: [1400])[0]);
        return ['src' => upload_url($small), 'srcset' => upload_url($small) . ' 720w, ' . $full . ' ' . max(721, $w) . 'w'];
    }
    return ['src' => $full, 'srcset' => ''];
}

// ---------------------------------------------------------------------------
// Drafts, ownership and payment
// ---------------------------------------------------------------------------

/** Drafts nobody paid for are cleared after a month, with their pictures. */
function promo_purge_drafts(): void
{
    try {
        foreach (rows("SELECT id, image_path FROM advertisements WHERE status = 'unpaid' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 50") as $d) {
            promo_image_delete($d['image_path']);
            delete_rows('advertisements', ['id' => (int)$d['id']]);
        }
    } catch (Throwable $e) {
        app_log('error', 'promo purge failed: ' . $e->getMessage());
    }
}

/** Save the advert as an unpaid draft. Returns the row. */
function promo_create_draft(array $d, array $pkg, string $imagePath, ?int $uid): array
{
    promo_purge_drafts();
    $places = $pkg['places'] ?: ['discover'];
    $token = bin2hex(random_bytes(PROMO_TOKEN_LEN / 2));
    $id = insert('advertisements', [
        'advertiser_name' => $d['business'], 'advertiser_email' => $d['email'], 'advertiser_phone' => $d['phone'] !== '' ? $d['phone'] : null,
        'title' => $d['headline'], 'description' => $d['text'] !== '' ? $d['text'] : null, 'image_path' => $imagePath, 'link_url' => $d['link'], 'cta_text' => $d['cta'],
        'ad_type' => 'card', 'placement' => $places[0], 'placements' => implode(',', $places),
        'starts_at' => now(), 'expires_at' => date('Y-m-d H:i:s', time() + (int)$pkg['days'] * 86400),
        'status' => 'unpaid', 'user_id' => $uid ?: null, 'package_id' => (int)$pkg['id'], 'duration_days' => (int)$pkg['days'], 'draft_token' => $token,
    ]);
    return row('SELECT * FROM advertisements WHERE id = :id', ['id' => $id]);
}

function promo_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{' . PROMO_TOKEN_LEN . '}$/', $token)) {
        return null;
    }
    return row('SELECT * FROM advertisements WHERE draft_token = :t', ['t' => $token]) ?: null;
}

/** Can this member act on the advert? An unclaimed draft is theirs to claim; one that belongs to someone else is not. */
function promo_owned_by(array $ad, int $uid): bool
{
    return $uid > 0 && ((int)($ad['user_id'] ?? 0) === $uid || ($ad['user_id'] === null && $ad['status'] === 'unpaid'));
}

function promo_mine(int $uid): array
{
    $rows = rows('SELECT a.*, ' . promo_live_sql('a') . ' AS is_live FROM advertisements a WHERE a.user_id = :u ORDER BY a.id DESC LIMIT 100', ['u' => $uid]);
    foreach ($rows as &$r) {
        $r['package'] = !empty($r['package_id']) ? promo_package((int)$r['package_id'], false) : null;
    }
    return $rows;
}

/**
 * Start paying for a draft (kind "new") or for more days on an advert that has run (kind "renew").
 * @return array{ok:bool,error:string,payment?:array,free?:bool}
 */
function promo_checkout(array $ad, int $uid, string $kind = 'new'): array
{
    if (!promo_enabled()) {
        return ['ok' => false, 'error' => 'Adverts are not on sale right now.'];
    }
    if (!promo_owned_by($ad, $uid)) {
        return ['ok' => false, 'error' => 'That advert is not available.'];
    }
    $kind = $kind === 'renew' ? 'renew' : 'new';
    if ($kind === 'new' && $ad['status'] !== 'unpaid') {
        return ['ok' => false, 'error' => 'That advert is already paid for.'];
    }
    if ($kind === 'renew' && !in_array($ad['status'], ['active', 'paused'], true)) {
        return ['ok' => false, 'error' => 'Only an advert that has been approved can be renewed.'];
    }
    $pkg = !empty($ad['package_id']) ? promo_package((int)$ad['package_id']) : null;
    if (!$pkg) {
        return ['ok' => false, 'error' => 'That package is no longer on sale. Send the advert again and choose another.'];
    }
    if ($ad['user_id'] === null) {
        update('advertisements', ['user_id' => $uid], ['id' => (int)$ad['id']]);
    }
    $fee = (float)$pkg['price'];
    $meta = ['ad_id' => (int)$ad['id'], 'kind' => $kind, 'package_id' => (int)$pkg['id'], 'days' => (int)$pkg['days'], 'return_url' => '/advertise/mine?ad=' . (int)$ad['id']];
    if ($fee <= 0) {
        // a free package needs no payment: the same delivery runs straight away
        $pid = insert('payments', [
            'user_id' => $uid, 'payment_type' => 'ad', 'payment_method' => 'test', 'amount' => 0, 'currency' => (string)setting('currency'), 'status' => 'pending',
            'reference' => 'CH' . date('ymd') . strtoupper(substr(random_token(4), 0, 6)), 'description' => 'Free advert package: ' . $pkg['name'], 'metadata' => json_encode($meta),
        ]);
        payment_complete((int)$pid);
        return ['ok' => true, 'error' => '', 'free' => true];
    }
    $existing = payment_find_reusable($uid, 'ad', $fee, array_diff_key($meta, ['return_url' => 1]));
    if ($existing) {
        return ['ok' => true, 'error' => '', 'payment' => $existing];
    }
    $label = 'Advert: ' . $pkg['name'] . ', ' . promo_days_text((int)$pkg['days']);
    return ['ok' => true, 'error' => '', 'payment' => payment_create($uid, 'ad', payments_default_method(), $fee, $label, $meta)];
}

/** Put an advert on for its days, counted from now. */
function promo_activate(int $adId, int $days, string $status = 'active'): void
{
    $days = max(1, $days);
    update('advertisements', [
        'status' => $status, 'starts_at' => now(), 'expires_at' => date('Y-m-d H:i:s', time() + $days * 86400), 'review_note' => null,
    ], ['id' => $adId]);
}

/** Called by payment_complete() inside its transaction. Returns the note kept on the payment. */
function promo_fulfil(array $p, array $meta): string
{
    $uid = (int)$p['user_id'];
    $pid = (int)$p['id'];
    $adId = (int)($meta['ad_id'] ?? 0);
    $days = max(1, (int)($meta['days'] ?? 7));
    $kind = (string)($meta['kind'] ?? 'new');
    $ad = $adId ? row('SELECT * FROM advertisements WHERE id = :id FOR UPDATE', ['id' => $adId]) : null;
    if (!$ad) {
        return 'Advert no longer exists. The payment is kept on record.';
    }
    $paid = round((float)$ad['amount_paid'] + (float)$p['amount'], 2);

    if ($kind === 'renew' && in_array($ad['status'], ['active', 'paused'], true)) {
        $base = strtotime((string)$ad['expires_at']) > time() ? strtotime((string)$ad['expires_at']) : time();
        $end = date('Y-m-d H:i:s', $base + $days * 86400);
        update('advertisements', ['expires_at' => $end, 'amount_paid' => $paid, 'paid_at' => now(), 'payment_id' => $pid], ['id' => $adId]);
        notify($uid, 'payment', 'Your advert runs longer', '"' . str_limit((string)$ad['title'], 40) . '" now runs until ' . fmt_date($end) . '.', '/advertise/mine');
        return 'Advert extended until ' . fmt_date($end);
    }

    update('advertisements', ['payment_id' => $pid, 'paid_at' => now(), 'amount_paid' => $paid, 'duration_days' => $days, 'user_id' => $uid], ['id' => $adId]);
    if (promo_review_on()) {
        promo_activate($adId, $days, 'pending'); // the days are counted again from the moment it is approved
        notify($uid, 'payment', 'Payment received', 'We are checking your advert. It goes live as soon as it is approved, usually within a day.', '/advertise/mine?ad=' . $adId);
        admin_alert('advert', 'An advert is waiting for approval', '"' . (string)$ad['title'] . '" by ' . (string)$ad['advertiser_name'] . ' was paid for and needs a look.', '/admin/ads?tab=review');
        return 'Advert paid, waiting for approval';
    }
    promo_activate($adId, $days, 'active');
    $end = date('Y-m-d H:i:s', time() + $days * 86400);
    notify($uid, 'payment', 'Your advert is live', '"' . str_limit((string)$ad['title'], 40) . '" runs until ' . fmt_date($end) . '.', '/advertise/mine?ad=' . $adId);
    return 'Advert live until ' . fmt_date($end);
}

/** For the payment page and receipts. Returns [title, icon, lines]. */
function promo_payment_summary(array $p, array $meta): array
{
    $ad = !empty($meta['ad_id']) ? row('SELECT title, advertiser_name FROM advertisements WHERE id = :id', ['id' => (int)$meta['ad_id']]) : null;
    $pkg = !empty($meta['package_id']) ? promo_package((int)$meta['package_id'], false) : null;
    $days = (int)($meta['days'] ?? ($pkg['days'] ?? 0));
    $lines = [['Advert', $ad ? (string)$ad['title'] : 'Your advert']];
    if ($pkg) {
        $lines[] = ['Package', (string)$pkg['name']];
    }
    $lines[] = ['Runs for', $days > 0 ? promo_days_text($days) : '-'];
    if ($pkg && $pkg['places']) {
        $lines[] = ['Shows on', promo_places_label($pkg['places'])];
    }
    $lines[] = ['Starts', (($meta['kind'] ?? 'new') === 'renew') ? 'When the current days end.' : (promo_review_on() ? 'When we approve it, usually within a day.' : 'As soon as the payment is confirmed.')];
    return [(($meta['kind'] ?? 'new') === 'renew' ? 'More days for your advert' : 'Advert'), 'megaphone', $lines];
}

/** [label, tone] the advertiser sees for one of their adverts. Tone: ok, wait, bad, off. */
function promo_owner_status(array $ad): array
{
    switch ($ad['status']) {
        case 'unpaid':
            return ['Not paid yet', 'wait'];
        case 'pending':
            return ['Being checked', 'wait'];
        case 'rejected':
            return ['Not approved', 'bad'];
        case 'paused':
            return ['Paused', 'off'];
    }
    if (strtotime((string)$ad['expires_at']) <= time()) {
        return ['Finished', 'off'];
    }
    if (strtotime((string)$ad['starts_at']) > time()) {
        return ['Starts soon', 'wait'];
    }
    return ['Live', 'ok'];
}

// ---------------------------------------------------------------------------
// Showing them
// ---------------------------------------------------------------------------

/**
 * Live adverts for a place. Cards and banners only (stories and pop-ups have their own homes), shuffled so each one
 * gets its turn. Empty when adverts are off or this member's plan hides them.
 */
function promo_live(string $placement, int $limit): array
{
    try {
        if (!setting('ads_enabled') || can('no_ads')) {
            return [];
        }
        $limit = max(1, min(12, $limit));
        return rows("SELECT * FROM advertisements a WHERE " . promo_live_sql('a') . "
            AND a.ad_type IN ('card','banner') AND (a.placement = :p OR a.placement = 'global' OR FIND_IN_SET(:p2, COALESCE(a.placements, '')) > 0)
            ORDER BY RAND() LIMIT " . $limit, ['p' => $placement, 'p2' => $placement]);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * The block for a place: nothing at all when no advert is running, and a layout made for however many there are
 * (one roomy card, two side by side, three or more in a row that scrolls on a phone). Counts one view each.
 */
function promo_block(string $placement, array $o = []): string
{
    $max = max(1, min(12, (int)($o['max'] ?? setting('promo_max_shown'))));
    $ads = promo_live($placement, $max);
    if (!$ads) {
        return '';
    }
    $ids = array_map(fn($a) => (int)$a['id'], $ads);
    try {
        q('UPDATE advertisements SET views = views + 1 WHERE id IN (' . implode(',', $ids) . ')');
        if (function_exists('ad_track_event')) { foreach ($ids as $adId) { ad_track_event((int)$adId, 'impression', $placement); } }
    } catch (Throwable $e) {
        // a missed count never blocks the page
    }
    return partial('spot_block', ['ads' => $ads, 'place' => $placement, 'title' => (string)($o['title'] ?? ''), 'sub' => (string)($o['sub'] ?? ''), 'cta' => $o['cta'] ?? null]);
}

/**
 * Tell the advertiser when their advert goes live or is turned down. $before is the row as it was before the change, $to the new
 * status. Approving a paid advert that was waiting starts its days now (the package says how many), unless $restart is false
 * because an admin chose the dates by hand.
 */
function promo_status_changed(array $before, string $to, bool $restart = true): void
{
    $was = (string)$before['status'];
    $ad = $before;
    if ($to === 'active' && $was === 'pending' && $restart && (int)($before['duration_days'] ?? 0) > 0 && !empty($before['payment_id'])) {
        promo_activate((int)$before['id'], (int)$before['duration_days'], 'active');
        $ad = row('SELECT * FROM advertisements WHERE id = :id', ['id' => (int)$before['id']]) ?: $before;
    }
    $uid = (int)($ad['user_id'] ?? 0);
    if ($uid <= 0 || $was === $to) {
        return;
    }
    try {
        if ($to === 'active' && $was === 'pending') {
            notify($uid, 'payment', 'Your advert is live', '"' . str_limit((string)$ad['title'], 40) . '" is running until ' . fmt_date((string)$ad['expires_at']) . '.', '/advertise/mine?ad=' . (int)$ad['id']);
        } elseif ($to === 'rejected') {
            notify($uid, 'payment', 'We could not approve your advert', trim((string)($ad['review_note'] ?? '')) !== '' ? (string)$ad['review_note'] : 'It did not meet the advert rules. Write to us and we will refund you or help you send a new one.', '/advertise/mine?ad=' . (int)$ad['id']);
        }
    } catch (Throwable $e) {
        app_log('error', 'advert notice failed: ' . $e->getMessage());
    }
}
