<?php
/**
 * Two ways to run the Hosts module, chosen by the admin with the switch at the top of Admin > Hosts (the same choice is on
 * Names and Discover). The contact way is what ships; the booking way is one click away.
 *
 *   booking   the original way: a client picks a time, pays a deposit and the Host confirms
 *   contact   the direct way (default): nothing is booked. Visitors reach the Host on WhatsApp, by phone or with a
 *             message here, whichever that Host has filled in. Services are named rather than sold, and a
 *             price only appears next to one when the Host chose to add it.
 *
 * Switching is not destructive. Rates, packages, availability and past bookings stay in the database, so the
 * admin can go back to booking and everything is as it was. hosts_booking_enabled() already guards every
 * booking page, button and endpoint, so the contact way simply makes that answer false.
 *
 *   hosts_contact_way()     true while the contact way is on
 *   hosts_service_cards()   the Host's services ready to draw as small colour cards
 *   hosts_contact_actions() the WhatsApp / Call / Message buttons a viewer may use
 */

declare(strict_types=1);

const HOST_MODE_DEFAULT = 'contact';

/** The colours a service card can take. Keys are stored, so keep them stable. */
const HOST_SERVICE_COLORS = ['rose', 'amber', 'emerald', 'sky', 'violet', 'teal', 'orange', 'indigo'];

/** 'booking' or 'contact'. */
function hosts_module_mode(bool $fresh = false): string
{
    static $memo = null;
    if ($memo !== null && !$fresh) {
        return $memo;
    }
    try {
        $m = strtolower(trim((string)setting('host_module_mode')));
    } catch (Throwable $e) {
        $m = '';
    }
    return $memo = in_array($m, ['booking', 'contact'], true) ? $m : HOST_MODE_DEFAULT;
}

function hosts_mode_reset(): void
{
    hosts_module_mode(true);
}

/** True while people reach a Host directly instead of booking a time. */
function hosts_contact_way(): bool
{
    return (bool)setting('hosts_enabled') && hosts_module_mode() === 'contact';
}

/**
 * May a listing show prices? A listing is any card or bubble that points at a Host: the directory, the Discover rows,
 * the home page, the stories. Only the booking way does. In the contact way a charge appears once, on the agent's own
 * profile, and only when the agent chose to add one.
 */
function hosts_listing_prices(): bool
{
    return !hosts_contact_way();
}

/** The directory sort choices. The two price sorts go with the prices. */
function hosts_sorts(): array
{
    $all = HOSTS_SORTS;
    if (!hosts_listing_prices()) {
        unset($all['price_asc'], $all['price_desc']);
    }
    return $all;
}

/** Heading above the contact buttons. */
function hosts_contact_headline(): string
{
    $h = trim((string)setting('host_contact_headline'));
    return $h !== '' ? $h : 'Get in touch';
}

// ---------------------------------------------------------------------------
// Services as small cards
// ---------------------------------------------------------------------------

/** A stable colour for a service: the admin's choice, else one picked from its id so a list always looks varied. */
function host_service_color(array $service): string
{
    $set = trim((string)($service['color'] ?? ''));
    if (in_array($set, HOST_SERVICE_COLORS, true)) {
        return $set;
    }
    $id = (int)($service['id'] ?? 0);
    return HOST_SERVICE_COLORS[$id % count(HOST_SERVICE_COLORS)];
}

/** The prices a Host saved against their services: [serviceId => price]. Stored as JSON on the profile. */
function hosts_service_prices($raw): array
{
    if (is_array($raw)) {
        $map = $raw;
    } else {
        $map = json_decode((string)$raw, true);
        $map = is_array($map) ? $map : [];
    }
    $out = [];
    foreach ($map as $sid => $price) {
        $sid = (int)$sid;
        $price = (int)round((float)$price);
        if ($sid > 0 && $price > 0) {
            $out[$sid] = $price;
        }
    }
    return $out;
}

/** True while Hosts are allowed to put a price on a service. */
function hosts_service_prices_allowed(): bool
{
    return hosts_contact_way() && (bool)setting('host_service_prices');
}

/**
 * The Host's chosen services, ready to draw: name, short line, icon, colour and a price when they set one.
 * @return array<int, array{id:int,name:string,description:string,icon:string,color:string,group:string,price:?int,price_label:string}>
 */
function hosts_service_cards(array $hp, bool $withPrices = true): array
{
    $catalog = hosts_services_catalog();
    $prices = $withPrices && hosts_service_prices_allowed() ? hosts_service_prices($hp['service_prices'] ?? '') : [];
    $out = [];
    foreach (hosts_ids($hp['service_ids'] ?? '') as $sid) {
        if (!isset($catalog[$sid])) {
            continue;
        }
        $s = $catalog[$sid];
        $price = $prices[$sid] ?? null;
        $out[] = [
            'id' => $sid,
            'name' => (string)$s['name'],
            'description' => (string)($s['description'] ?? ''),
            'icon' => (string)($s['icon'] ?: 'sparkles'),
            'color' => host_service_color($s + ['id' => $sid]),
            'group' => (string)($s['group_name'] ?? ''),
            'price' => $price,
            'price_label' => $price !== null ? money($price) : '',
        ];
    }
    return $out;
}

/** Validate the prices a Host typed, against the services they actually chose. Returns [map, errors]. */
function hosts_service_prices_save(array $in, array $serviceIds): array
{
    $allowed = array_flip(hosts_ids($serviceIds));
    $max = 10000000;
    $out = [];
    $errors = [];
    foreach ((array)$in as $sid => $raw) {
        $sid = (int)$sid;
        $raw = trim((string)$raw);
        if ($sid <= 0 || !isset($allowed[$sid]) || $raw === '') {
            continue;
        }
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $raw)) {
            $errors['service_price_' . $sid] = 'Use numbers only, for example 2500.';
            continue;
        }
        $price = (int)round((float)$raw);
        if ($price <= 0) {
            continue;
        }
        if ($price > $max) {
            $errors['service_price_' . $sid] = 'That price is too high.';
            continue;
        }
        $out[$sid] = $price;
    }
    ksort($out);
    return [$out, $errors];
}

/** JSON for the profile column, or null when the Host priced nothing. */
function hosts_service_prices_json(array $map): ?string
{
    return $map ? json_encode($map, JSON_UNESCAPED_SLASHES) : null;
}

// ---------------------------------------------------------------------------
// Ways to get in touch
// ---------------------------------------------------------------------------

/**
 * The buttons a viewer may use to reach this Host, in the order they should appear.
 *
 * Only ways the Host actually filled in are returned, and only while the admin allows that channel and this
 * viewer is allowed to see contact details at all. "message" needs no details from the Host: it is the
 * conversation on this site, so it is offered to any signed-in member whom messaging allows through.
 *
 * @return array{state:string, note:string, items:array<int, array<string,string>>, message:?array<string,string>}
 */
function hosts_contact_actions(int $hostId, ?array $viewer = null): array
{
    $viewer = $viewer ?? current_user();
    $ct = hosts_contacts_for($hostId, $viewer);
    $items = [];
    $vals = ($ct['state'] === 'visible') ? (array)$ct['items'] : [];

    $wa = trim((string)($vals['whatsapp']['value'] ?? ''));
    if ($wa !== '') {
        $digits = preg_replace('/\D/', '', phone_normalize($wa) ?: $wa) ?? '';
        if ($digits !== '') {
            $items[] = ['key' => 'whatsapp', 'label' => 'WhatsApp', 'hero' => 'Chat on WhatsApp', 'icon' => 'message-circle', 'href' => 'https://wa.me/' . $digits,
                'external' => '1', 'hint' => hosts_phone_local($wa)];
        }
    }
    $ph = trim((string)($vals['phone']['value'] ?? ''));
    if ($ph !== '') {
        $items[] = ['key' => 'phone', 'label' => 'Call', 'hero' => 'Call now', 'icon' => 'phone', 'href' => 'tel:' . (phone_normalize($ph) ?: $ph),
            'external' => '', 'hint' => hosts_phone_local($ph)];
    }
    $em = trim((string)($vals['email']['value'] ?? ''));
    if ($em !== '') {
        $items[] = ['key' => 'email', 'label' => 'Email', 'hero' => 'Send an email', 'icon' => 'mail', 'href' => 'mailto:' . $em, 'external' => '', 'hint' => $em];
    }
    $ig = trim((string)($vals['instagram']['value'] ?? ''));
    if ($ig !== '') {
        $handle = ltrim(trim($ig), '@');
        $href = preg_match('#^https?://#i', $handle) ? $handle : 'https://instagram.com/' . rawurlencode($handle);
        $items[] = ['key' => 'instagram', 'label' => 'Instagram', 'hero' => 'See on Instagram', 'icon' => 'instagram', 'href' => $href, 'external' => '1',
            'hint' => preg_match('#^https?://#i', $handle) ? 'Opens Instagram' : '@' . $handle];
    }

    $message = null;
    $own = $viewer && (int)$viewer['id'] === $hostId;
    if (!$own && function_exists('messaging_access')) {
        if (!$viewer) {
            $message = ['key' => 'message', 'label' => 'Message', 'hero' => 'Message on ' . site_name(), 'icon' => 'message-circle', 'href' => url('/register', ['next' => '/hosts/' . $hostId]),
                'external' => '', 'hint' => 'Create a free account to chat here'];
        } else {
            $acc = messaging_access($hostId);
            if (!empty($acc['allowed'])) {
                $message = ['key' => 'message', 'label' => 'Message', 'hero' => 'Message on ' . site_name(), 'icon' => 'message-circle', 'href' => url('/messages/with/' . $hostId),
                    'external' => '', 'hint' => 'Chat here on ' . site_name()];
            } elseif (!empty($acc['cta'])) {
                $message = ['key' => 'message', 'label' => 'Message', 'hero' => 'Message on ' . site_name(), 'icon' => 'message-circle', 'href' => (string)$acc['cta'],
                    'external' => '', 'hint' => (string)($acc['message'] ?? '')];
            }
        }
    }

    return ['state' => (string)$ct['state'], 'note' => (string)$ct['note'], 'items' => $items, 'message' => $message];
}

/** Does this Host have any way for people to reach them? Used to nudge them while their profile is quiet. */
function hosts_has_contact_way(int $hostId): bool
{
    $p = row('SELECT show_contact, contact_phone, contact_whatsapp, contact_email, contact_instagram FROM user_profiles WHERE user_id = :u', ['u' => $hostId]);
    if (!$p) {
        return false;
    }
    foreach (['phone', 'whatsapp', 'email', 'instagram'] as $f) {
        if (trim((string)($p['contact_' . $f] ?? '')) !== '' && setting('hosts_contact_' . $f)) {
            return true;
        }
    }
    return false;
}

/** Plain words for why the contact details are not shown, used on the profile and in tests. */
function hosts_contact_state_text(string $state): string
{
    return [
        'login' => 'Sign in to see how to get in touch.',
        'upgrade' => 'Premium members can see how to get in touch.',
        'booking' => 'Clients with a confirmed booking can see how to get in touch.',
        'hidden' => 'This ' . host_term(false, true) . ' prefers messages here.',
        'none' => 'This ' . host_term(false, true) . ' has not added a phone number yet.',
    ][$state] ?? '';
}
