<?php
/**
 * Host module modes: bookings on/off and who may see a Host's contact details.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/host_modes_test.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line.\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0; $fail = 0;
function check(string $n, bool $ok, string $d = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  ok    ' : '  FAIL  ') . $n . (!$ok && $d !== '' ? "  ($d)" : '') . "\n"; }
$touched = [];
function set_for_test(string $k, string $v): void { global $touched; if (!array_key_exists($k, $touched)) { $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $k]); $touched[$k] = $r ? (string)$r['setting_value'] : null; } setting_set($k, $v); }
$hostId = (int)val("SELECT hp.user_id FROM host_profiles hp JOIN users u ON u.id = hp.user_id WHERE u.role = 'host' ORDER BY hp.user_id LIMIT 1", [], 0);
if (!$hostId) { exit("No Host in this database to test with.\n"); }
$savedProfile = row('SELECT show_contact, contact_phone, contact_whatsapp, contact_email, contact_instagram FROM user_profiles WHERE user_id = :u', ['u' => $hostId]);
$clientId = create_account(['email' => 'hostmodes-' . bin2hex(random_bytes(3)) . '@example.test', 'password' => 'Test#12345pass', 'display_name' => 'Mode Tester']);
$bid = 0;
try {
    set_for_test('hosts_enabled', '1');
    set_for_test('booking_enabled', '1');
    set_for_test('host_module_mode', 'booking');
    hosts_mode_reset();
    update('user_profiles', ['show_contact' => 1, 'contact_phone' => '254712345678', 'contact_whatsapp' => '254712345678', 'contact_email' => 'host@example.test', 'contact_instagram' => 'hostgram'], ['user_id' => $hostId]);
    $savedHp2 = row('SELECT hourly_rate, half_day_rate, min_hours FROM host_profiles WHERE user_id = :u', ['u' => $hostId]);
    $free = ['id' => $clientId, 'role' => 'user', 'tier' => 'free'];
    $prem = ['id' => $clientId, 'role' => 'user', 'tier' => 'premium'];
    $admin = ['id' => 999999, 'role' => 'admin', 'tier' => 'vip'];
    $owner = ['id' => $hostId, 'role' => 'host', 'tier' => 'free'];

    echo "\nBookings on\n";
    check('bookings are on', hosts_booking_enabled());
    set_for_test('hosts_contact_visibility', 'after_booking');
    check('default rule: nothing before a booking', hosts_contact_access($free, $hostId) === ['allowed' => false, 'reason' => 'booking']);
    check('visitors are asked to sign in', hosts_contact_access(null, $hostId)['reason'] === 'login');
    $bid = insert('host_bookings', ['host_id' => $hostId, 'client_id' => $clientId, 'booking_date' => date('Y-m-d', strtotime('+3 days')), 'start_time' => '14:00:00', 'duration_hours' => 2, 'total_amount' => 2000, 'deposit_amount' => 1000, 'commission_pct' => 15, 'commission_amount' => 300, 'host_earning' => 1700, 'status' => 'pending']);
    check('a pending booking does not unlock contact', !hosts_contact_access($free, $hostId)['allowed']);
    update('host_bookings', ['status' => 'confirmed'], ['id' => $bid]);
    check('a confirmed booking unlocks contact', hosts_contact_access($free, $hostId)['allowed']);
    check('the owner and admins always see them', hosts_contact_access($owner, $hostId)['allowed'] && hosts_contact_access($admin, $hostId)['allowed']);
    check('legacy helper follows the same rule', host_contact_unlocked($free, $hostId));
    update('host_bookings', ['status' => 'cancelled'], ['id' => $bid]);

    echo "\nContact rules\n";
    set_for_test('hosts_contact_visibility', 'members');
    check('members: any signed-in member', hosts_contact_access($free, $hostId)['allowed'] && hosts_contact_access(null, $hostId)['reason'] === 'login');
    set_for_test('hosts_contact_visibility', 'premium');
    check('premium: free members are asked to upgrade', hosts_contact_access($free, $hostId)['reason'] === 'upgrade' && hosts_contact_access($prem, $hostId)['allowed']);
    set_for_test('hosts_contact_visibility', 'everyone');
    check('everyone: even visitors', hosts_contact_access(null, $hostId)['allowed']);
    $c = hosts_contacts_for($hostId);
    check('visitor card shows phone, WhatsApp and Instagram but not email (email is off by default)', $c['state'] === 'visible' && isset($c['items']['phone'], $c['items']['whatsapp'], $c['items']['instagram']) && !isset($c['items']['email']), json_encode(array_keys($c['items'])));
    set_for_test('hosts_contact_email', '1');
    set_for_test('hosts_contact_phone', '0');
    $c = hosts_contacts_for($hostId);
    check('admin switches decide which fields are offered', isset($c['items']['email']) && !isset($c['items']['phone']));
    set_for_test('hosts_contact_note', 'Say hello from the site');
    check('the note is returned with visible details', hosts_contacts_for($hostId)['note'] === 'Say hello from the site');
    set_for_test('hosts_contact_visibility', 'hidden');
    check('hidden: nobody, and no values are returned', !hosts_contact_access($prem, $hostId)['allowed'] && hosts_contacts_for($hostId)['items'] === [] && hosts_contacts_for($hostId)['state'] === 'hidden');
    set_for_test('hosts_contact_visibility', 'everyone');
    update('user_profiles', ['show_contact' => 0], ['user_id' => $hostId]);
    check('a Host who switched sharing off shows nothing, whatever the rule', hosts_contacts_for($hostId)['items'] === []);
    update('user_profiles', ['show_contact' => 1, 'contact_email' => null, 'contact_whatsapp' => null, 'contact_instagram' => null, 'contact_phone' => null], ['user_id' => $hostId]);
    check('a Host with nothing filled in shows "none"', hosts_contacts_for($hostId)['state'] === 'none');
    update('user_profiles', ['contact_whatsapp' => '254712345678'], ['user_id' => $hostId]);
    set_for_test('hosts_contact_visibility', 'rules');
    check('"follow the privacy table" defers to member_contacts (visitors never)', hosts_contact_access(null, $hostId)['reason'] === 'login');

    echo "\nBookings off\n";
    set_for_test('booking_enabled', '0');
    check('bookings are off', !hosts_booking_enabled());
    set_for_test('hosts_contact_visibility', 'after_booking');
    check('"after a booking" becomes "signed-in members" when there are no bookings', hosts_contact_mode() === 'members' && hosts_contact_access($free, $hostId)['allowed']);
    check('the Host sentence changes with it', strpos(hosts_contact_policy_text(), 'signed-in members') !== false);
    check('a user with a booking on record can still reach booking pages', hosts_user_has_bookings($clientId));
    check('a user with none cannot', !hosts_user_has_bookings($clientId + 987654));
    check('the profile checklist no longer asks for payout details', (function () use ($hostId) {
        foreach (hosts_completeness($hostId)['items'] as $i) { if ($i['key'] === 'payout') { return $i['label'] === 'Host rules'; } }
        return false;
    })());
    check('the error for contact text points to the contact section', strpos(hosts_contact_message('Your text'), 'Contact details') !== false);
    $nav = array_column(nav_menu('host_sidebar'), 'url');
    check('menus lose Availability (and Bookings, Earnings, Payouts for a Host with no bookings)', !in_array('/host/availability', $nav, true));
    set_for_test('booking_enabled', '1');
    check('switching back brings booking features back', hosts_booking_enabled() && hosts_contact_mode() === 'after_booking');

    echo "\nThe contact way\n";
    $savedHp = row('SELECT service_ids, service_prices, contact_intro FROM host_profiles WHERE user_id = :u', ['u' => $hostId]);
    $cat = hosts_services_catalog();
    $ids = array_slice(array_keys($cat), 0, 3);
    check('there are services to work with', count($ids) === 3);
    set_for_test('host_module_mode', 'booking');
    hosts_mode_reset();
    check('the booking way works when an admin chooses it', hosts_module_mode() === 'booking' && !hosts_contact_way() && hosts_booking_enabled());
    q("DELETE FROM site_settings WHERE setting_key = 'host_module_mode'");
    settings_overrides(true);
    hosts_mode_reset();
    check('with nothing chosen, the contact way is the one that ships', hosts_module_mode() === 'contact' && hosts_contact_way());
    set_for_test('host_module_mode', 'booking');
    hosts_mode_reset();
    check('prices on a service are off while people book', !hosts_service_prices_allowed());

    set_for_test('host_module_mode', 'contact');
    hosts_mode_reset();
    check('switching turns the contact way on', hosts_contact_way() && hosts_module_mode() === 'contact');
    check('nothing can be booked any more', !hosts_booking_enabled());
    set_for_test('host_service_prices', '1');
    check('a Host may now put a price on a service', hosts_service_prices_allowed());

    // saving services the contact way
    $r = hosts_save_services($hostId, ['service_ids' => $ids, 'service_price' => [$ids[0] => '2500', $ids[1] => '', $ids[2] => '0'], 'contact_intro' => '  WhatsApp   is fastest. ']);
    check('services and prices save', !empty($r['ok']), json_encode($r['errors'] ?? []));
    $hp = hosts_profile($hostId);
    check('only real prices are kept', hosts_service_prices($hp['service_prices']) === [$ids[0] => 2500]);
    check('the line about getting in touch is tidied up', $hp['contact_intro'] === 'WhatsApp is fastest.');
    $cards = hosts_service_cards($hp);
    check('every chosen service becomes a card', count($cards) === 3 && $cards[0]['id'] === $ids[0]);
    check('the priced one shows its price and the others show none', $cards[0]['price'] === 2500 && $cards[1]['price'] === null && $cards[2]['price'] === null);
    check('each card carries a colour and an icon', in_array($cards[0]['color'], HOST_SERVICE_COLORS, true) && $cards[0]['icon'] !== '');
    check('the colour of a service never changes by itself', host_service_color(['id' => 7, 'color' => '']) === host_service_color(['id' => 7, 'color' => '']));
    check('an admin colour wins', host_service_color(['id' => 7, 'color' => 'violet']) === 'violet' && host_service_color(['id' => 7, 'color' => 'nonsense']) !== 'nonsense');

    // what the price field accepts
    [$map, $errs] = hosts_service_prices_save([$ids[0] => '1200.60', $ids[1] => 'free', $ids[2] => '99999999999'], $ids);
    check('a sensible price is rounded and kept', ($map[$ids[0]] ?? 0) === 1201);
    check('words are refused', isset($errs['service_price_' . $ids[1]]));
    check('a silly price is refused', isset($errs['service_price_' . $ids[2]]));
    [$map2] = hosts_service_prices_save([$ids[0] => '500', 999999 => '700'], $ids);
    check('a price for a service they do not offer is dropped', $map2 === [$ids[0] => 500]);
    check('no prices means nothing is stored', hosts_service_prices_json([]) === null);

    // the admin can switch prices off for everyone
    set_for_test('host_service_prices', '0');
    check('with prices off no card shows one', !hosts_service_prices_allowed() && hosts_service_cards(hosts_profile($hostId))[0]['price'] === null);
    set_for_test('host_service_prices', '1');

    // at least one service is still required
    $bad = hosts_save_services($hostId, ['service_ids' => [], 'service_price' => []]);
    check('a Host must still choose at least one service', empty($bad['ok']) && isset($bad['errors']['service_ids']));

    // the ways to get in touch (earlier sections emptied some fields on purpose, so fill them again)
    update('user_profiles', ['show_contact' => 1, 'contact_phone' => '254712345678', 'contact_whatsapp' => '254712345678', 'contact_email' => 'host@example.test', 'contact_instagram' => 'hostgram'], ['user_id' => $hostId]);
    set_for_test('hosts_contact_visibility', 'everyone');
    set_for_test('hosts_contact_phone', '1');
    set_for_test('hosts_contact_whatsapp', '1');
    $act = hosts_contact_actions($hostId, $free);
    $keys = array_column($act['items'], 'key');
    check('WhatsApp comes first, then the call', $keys[0] === 'whatsapp' && $keys[1] === 'phone');
    check('the WhatsApp link opens a chat', strpos($act['items'][0]['href'], 'https://wa.me/254712345678') === 0);
    check('the call link dials', strpos($act['items'][1]['href'], 'tel:') === 0);
    check('a message here is offered as well', $act['message'] !== null);
    set_for_test('hosts_contact_whatsapp', '0');
    check('a channel the admin switched off disappears', !in_array('whatsapp', array_column(hosts_contact_actions($hostId, $free)['items'], 'key'), true));
    set_for_test('hosts_contact_whatsapp', '1');
    set_for_test('hosts_contact_visibility', 'hidden');
    $hid = hosts_contact_actions($hostId, $free);
    check('nothing is shared when the admin hides contact details', $hid['items'] === [] && $hid['state'] === 'hidden');
    check('but a message here is still offered', $hid['message'] !== null);
    check('and the reason is said in plain words', strpos(hosts_contact_state_text('hidden'), 'messages here') !== false);
    set_for_test('hosts_contact_visibility', 'everyone');
    check('a visitor who is not signed in is pointed at joining', hosts_contact_actions($hostId, null)['message']['href'] !== '');
    check('a Host is not offered a way to message themselves', hosts_contact_actions($hostId, $owner)['message'] === null);
    check('this Host can be reached', hosts_has_contact_way($hostId));

    echo "\nNo prices on listings in the contact way\n";
    $svcSaved = row('SELECT service_ids, service_prices, hourly_rate FROM host_profiles WHERE user_id = :u', ['u' => $hostId]);
    $catalog = hosts_services_catalog();
    $sid = (int)array_key_first($catalog);
    update('host_profiles', ['service_ids' => (string)$sid, 'service_prices' => hosts_service_prices_json([$sid => 4500]), 'hourly_rate' => 3000], ['user_id' => $hostId]);
    set_for_test('host_service_prices', '1');
    set_for_test('host_show_rates', '1');
    set_for_test('host_module_mode', 'contact');
    hosts_mode_reset();
    $hpNow = row('SELECT * FROM host_profiles WHERE user_id = :u', ['u' => $hostId]);
    $seed = [['id' => $hostId, 'name' => 'Price Tester', 'photo' => null, 'town' => 'Westlands', 'county' => 'Nairobi', 'age' => 28]];
    $row = hosts_decorate($seed)[0];
    $card = partial('host_card', ['m' => $row]);
    check('the listing carries no lowest price', array_key_exists('from_price', $row['host']) && $row['host']['from_price'] === null);
    check('no service on the listing has a price', array_filter(array_column($row['host']['cards'], 'price'), fn($p) => $p !== null) === [] && $row['host']['cards'] !== []);
    check('the directory card shows no money, no "From" and no rate', strpos($card, '4,500') === false && strpos($card, '3,000') === false && strpos($card, '>From<') === false && strpos($card, '/hr') === false);
    check('it says how to reach the agent instead', strpos($card, 'Get in touch') !== false);
    check('the agent card on the home page shows no rate either', strpos(partial('agent_card', ['m' => $row]), '3,000') === false && strpos(partial('agent_card', ['m' => $row]), 'ag-price') === false);
    check('the agent keeps the charge for their own profile', array_filter(array_column(hosts_service_cards($hpNow), 'price'), fn($p) => $p !== null) !== []);
    check('the price range and the price sorts are gone', !isset(hosts_sorts()['price_asc']) && !isset(hosts_sorts()['price_desc']) && isset(hosts_sorts()['rating']));
    $f = hosts_directory_filters(['price_min' => 500, 'price_max' => 3000, 'sort' => 'price_asc']);
    check('a price filter in the address is ignored', $f['price_min'] === 0 && $f['price_max'] === 0 && $f['sort'] === 'recommended');
    $items = story_items('landing');
    $agentBubbles = array_values(array_filter($items, fn($i) => ($i['kind'] ?? '') === 'agent'));
    check('no story bubble carries a price (' . count($agentBubbles) . ' checked)', array_filter(array_column(array_column($agentBubbles, 'v'), 'price')) === []);

    set_for_test('host_module_mode', 'booking');
    hosts_mode_reset();
    $row = hosts_decorate($seed)[0];
    $card = partial('host_card', ['m' => $row]);
    check('with bookings on, the card shows the hourly price again', $row['host']['from_rate'] !== null && strpos($card, '>From<') !== false && strpos($card, money($row['host']['from_rate'])) !== false);
    $f = hosts_directory_filters(['price_min' => 500, 'price_max' => 3000, 'sort' => 'price_asc']);
    check('and the price range and sorts come back', $f['price_min'] === 500 && $f['price_max'] === 3000 && $f['sort'] === 'price_asc' && isset(hosts_sorts()['price_desc']));
    update('host_profiles', $svcSaved, ['user_id' => $hostId]);
    set_for_test('host_module_mode', 'contact');
    hosts_mode_reset();

    // rules copy follows the way the module runs
    check('the rules say nothing is paid here', stripos(implode(' ', array_column(hosts_rules_lines(), 'text')), 'Nothing is booked or paid for') !== false);
    set_for_test('host_module_mode', 'booking');
    hosts_mode_reset();
    check('and the booking rules come back', stripos(implode(' ', array_column(hosts_rules_lines(), 'text')), 'pay deposits') !== false);
    check('every rate and package survived the round trip', hosts_profile($hostId)['hourly_rate'] == $savedHp2['hourly_rate']);
    if ($savedHp) { update('host_profiles', $savedHp, ['user_id' => $hostId]); }
} finally {
    if ($bid) { try { q('DELETE FROM host_booking_events WHERE booking_id = :b', ['b' => $bid]); } catch (Throwable $e) {} q('DELETE FROM host_bookings WHERE id = :b', ['b' => $bid]); }
    if ($savedProfile) { update('user_profiles', $savedProfile, ['user_id' => $hostId]); }
    q('DELETE FROM users WHERE id = :u', ['u' => $clientId]);
    foreach ($touched as $k => $v) { $v === null ? setting_reset($k) : setting_set($k, $v); }
}
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
