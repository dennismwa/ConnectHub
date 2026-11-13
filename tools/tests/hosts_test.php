<?php
/**
 * Hosts marketplace tests: slot calculation (time zones, midnight crossing, blocked dates, overlapping bookings, buffers),
 * price quotes, refund tiers, text rules, and a full booking life cycle on disposable accounts.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/hosts_test.php
 *
 * Needs a throw-away database (see tools/testdb.sh, with --demo for locations). Part 1 is pure and touches nothing. Part 2 creates
 * one Host and two clients, books, pays (payment_complete, no network), accepts, completes, reviews, requests a payout, cancels,
 * and removes everything again. Settings it changes are put back. Exit code 0 when every check passes, 1 otherwise.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) {
    exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n");
}

require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  ok    $name\n";
    } else {
        $fail++;
        echo "  FAIL  $name" . ($detail !== '' ? "  ($detail)" : '') . "\n";
    }
}
function section(string $t): void
{
    echo "\n$t\n";
}
function same($a, $b): bool
{
    return $a === $b;
}
/** @return list<string> start times of a slot list */
function starts(array $slots): array
{
    return array_column($slots, 'start');
}

$touched = [];
function set_for_test(string $key, string $value): void
{
    global $touched;
    if (!array_key_exists($key, $touched)) {
        $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $key]);
        $touched[$key] = $r ? (string)$r['setting_value'] : null;
    }
    setting_set($key, $value);
}
function restore_settings(): void
{
    global $touched;
    foreach ($touched as $k => $v) {
        if ($v === null) {
            setting_reset($k);
        } else {
            setting_set($k, $v);
        }
    }
    hosts_mode_reset();
}

// =============================================================================================
// PART 1: pure functions
// =============================================================================================
section('Times and weekly schedules');
check('time parses', same(hosts_time_to_min('09:30'), 570));
check('time with seconds parses', same(hosts_time_to_min('09:30:00'), 570));
check('24:00 is end of day', same(hosts_time_to_min('24:00'), 1440));
check('bad time is null', same(hosts_time_to_min('25:61'), null) && same(hosts_time_to_min('abc'), null) && same(hosts_time_to_min(''), null));

$w = hosts_weekly_normalize(['mon' => [['12:00', '18:00'], ['09:00', '13:00']], 'tue' => [['20:00', '02:00']], 'wed' => [['10:00', '10:00'], ['x', 'y'], ['09:00']], 'fri' => [['08:00', '09:00'], ['10:00', '11:00'], ['12:00', '13:00'], ['14:00', '15:00'], ['16:00', '17:00']]]);
check('overlapping windows merge', same($w['mon'], [['09:00', '18:00']]));
check('midnight window is kept as start and end', same($w['tue'], [['20:00', '02:00']]));
check('empty, malformed and zero-length windows are dropped', same($w['wed'], []));
check('at most four windows a day', count($w['fri']) === 4);
check('every day is present', count($w) === 7 && isset($w['sun']));
check('non-array input gives a closed week', array_sum(array_map('count', hosts_weekly_normalize('junk'))) === 0);

// 2026-10-19 is a Monday. "now" is Sunday 2026-10-18 08:00 in Nairobi unless a test says otherwise.
$tzNbo = 'Africa/Nairobi';
$nowNbo = (new DateTimeImmutable('2026-10-18 08:00:00', new DateTimeZone($tzNbo)))->getTimestamp();
$base = ['tz' => $tzNbo, 'now' => $nowNbo, 'min_notice_hours' => 0, 'max_advance_days' => 60, 'step' => 30, 'buffer' => 0];
$week = hosts_weekly_normalize(['mon' => [['10:00', '18:00']], 'tue' => [['10:00', '18:00']], 'fri' => [['20:00', '02:00']]]);

section('Slots: basic day');
$s = hosts_slots_compute($week, '2026-10-19', 1, [], [], $base);
check('Monday 10:00-18:00, 1 hour, 30 minute step: 15 starts', count($s) === 15, (string)count($s));
check('first start 10:00, last start 17:00', same(starts($s)[0], '10:00') && same(end($s)['start'], '17:00'));
check('a start has its end', same($s[0]['end'], '11:00') && same($s[0]['next_day'], false));
$s2 = hosts_slots_compute($week, '2026-10-19', 3.5, [], [], $base);
check('3.5 hours: last start 14:30', same(end($s2)['start'], '14:30'));
check('too long for the window gives nothing', hosts_slots_compute($week, '2026-10-19', 9, [], [], $base) === []);
check('closed day gives nothing', hosts_slots_compute($week, '2026-10-21', 1, [], [], $base) === []);
check('15 minute step doubles the starts', count(hosts_slots_compute($week, '2026-10-19', 1, [], [], ['step' => 15] + $base)) === 29);
check('bad date gives nothing', hosts_slots_compute($week, '2026-13-45', 1, [], [], $base) === [] && hosts_slots_compute($week, 'tomorrow', 1, [], [], $base) === []);
check('zero hours gives nothing', hosts_slots_compute($week, '2026-10-19', 0, [], [], $base) === []);

section('Slots: notice, past dates and how far ahead');
$monNoon = (new DateTimeImmutable('2026-10-19 12:00:00', new DateTimeZone($tzNbo)))->getTimestamp();
$s = hosts_slots_compute($week, '2026-10-19', 1, [], [], ['now' => $monNoon, 'min_notice_hours' => 2] + $base);
check('2 hours notice at noon: first start 14:00', same(starts($s)[0] ?? null, '14:00'), (string)(starts($s)[0] ?? 'none'));
$s = hosts_slots_compute($week, '2026-10-19', 1, [], [], ['now' => $monNoon, 'min_notice_hours' => 0] + $base);
check('no notice: a slot at exactly "now" is allowed, earlier ones are not', same(starts($s)[0] ?? null, '12:00'));
check('yesterday gives nothing', hosts_slots_compute($week, '2026-10-17', 1, [], [], $base) === []);
check('beyond the booking window gives nothing', hosts_slots_compute($week, '2026-10-19', 1, [], [], ['max_advance_days' => 0] + $base) === [] && count(hosts_slots_compute($week, '2026-10-19', 1, [], [], ['max_advance_days' => 1] + $base)) === 15);

section('Slots: time zones');
// 2026-10-19 22:30 UTC is 2026-10-20 01:30 in Nairobi. Monday 19th is already over there, but still "today" in UTC.
$late = (new DateTimeImmutable('2026-10-19 22:30:00', new DateTimeZone('UTC')))->getTimestamp();
check('same instant, Nairobi: Monday is over', hosts_slots_compute($week, '2026-10-19', 1, [], [], ['tz' => $tzNbo, 'now' => $late] + $base) === []);
check('same instant, UTC: Monday is today, but its hours have passed', hosts_slots_compute($week, '2026-10-19', 1, [], [], ['tz' => 'UTC', 'now' => $late] + $base) === []);
$earlyUtc = (new DateTimeImmutable('2026-10-19 05:30:00', new DateTimeZone('UTC')))->getTimestamp(); // 08:30 in Nairobi
$sN = hosts_slots_compute($week, '2026-10-19', 1, [], [], ['tz' => $tzNbo, 'now' => $earlyUtc] + $base);
$sU = hosts_slots_compute($week, '2026-10-19', 1, [], [], ['tz' => 'UTC', 'now' => $earlyUtc] + $base);
check('same instant, both zones list Monday\'s 15 starts (hours are in the Host\'s own zone)', count($sN) === 15 && count($sU) === 15, count($sN) . '/' . count($sU));
check('slot timestamps follow the zone', same($sN[0]['ts'], (new DateTimeImmutable('2026-10-19 10:00', new DateTimeZone($tzNbo)))->getTimestamp()));
check('and differ between zones for the same clock time', $sN[0]['ts'] !== ($sU[0]['ts'] ?? 0));
$ny = hosts_slots_compute($week, '2026-10-19', 1, [], [], ['tz' => 'America/New_York', 'now' => $nowNbo] + $base);
check('New York clock time maps to the right instant', same($ny[0]['ts'], (new DateTimeImmutable('2026-10-19 10:00', new DateTimeZone('America/New_York')))->getTimestamp()));
check('bad time zone gives nothing instead of an error', hosts_slots_compute($week, '2026-10-19', 1, [], [], ['tz' => 'Mars/Olympus'] + $base) === []);

section('Slots: windows that cross midnight');
$s = hosts_slots_compute($week, '2026-10-23', 2, [], [], $base); // Friday 20:00 to 02:00
check('Friday 20:00-02:00, 2 hours: starts 20:00 to 23:30', same(starts($s)[0] ?? null, '20:00') && same(end($s)['start'], '23:30') && count($s) === 8, implode(',', starts($s)));
$late = array_values(array_filter($s, fn($x) => $x['start'] === '23:00'))[0] ?? null;
check('a 23:00 start ends at 01:00 the next day', $late && same($late['end'], '01:00') && same($late['next_day'], true));
$early = array_values(array_filter($s, fn($x) => $x['start'] === '20:00'))[0] ?? null;
check('a 20:00 start ends at 22:00 the same day', $early && same($early['end'], '22:00') && same($early['next_day'], false));
$s = hosts_slots_compute($week, '2026-10-24', 1, [], [], $base); // Saturday: closed, but Friday spills into 00:00-02:00
check('Saturday has early-morning starts from Friday\'s window', same(starts($s), ['00:00', '00:30', '01:00']), implode(',', starts($s)));
check('Sunday has none', hosts_slots_compute($week, '2026-10-25', 1, [], [], $base) === []);
check('a booking cannot run past the end of the window', !in_array('01:30', starts(hosts_slots_compute($week, '2026-10-24', 1, [], [], $base)), true));

section('Slots: blocked dates');
check('blocked day gives nothing', hosts_slots_compute($week, '2026-10-19', 1, ['2026-10-19'], [], $base) === []);
check('other days are not affected', count(hosts_slots_compute($week, '2026-10-20', 1, ['2026-10-19'], [], $base)) === 15);
check('blocking Friday also removes the after-midnight part on Saturday', hosts_slots_compute($week, '2026-10-24', 1, ['2026-10-23'], [], $base) === []);
$s = hosts_slots_compute($week, '2026-10-23', 2, ['2026-10-24'], [], $base);
check('blocking Saturday stops a Friday booking at midnight', same(end($s)['start'], '22:00') && count($s) === 5, implode(',', starts($s)));
check('blocked list accepts datetime strings', hosts_slots_compute($week, '2026-10-19', 1, ['2026-10-19 00:00:00'], [], $base) === []);

section('Slots: bookings and buffers');
$bk = [['date' => '2026-10-19', 'start' => '12:00', 'hours' => 2.0]];
$s = hosts_slots_compute($week, '2026-10-19', 1, [], $bk, $base);
check('no buffer: 12:00-14:00 is taken, 11:00 and 14:00 are free', in_array('11:00', starts($s), true) && in_array('14:00', starts($s), true) && !in_array('11:30', starts($s), true) && !in_array('13:30', starts($s), true));
check('no buffer: 10 of 15 starts left', count($s) === 10, (string)count($s));
$s = hosts_slots_compute($week, '2026-10-19', 1, [], $bk, ['buffer' => 30] + $base);
check('30 minute buffer: 11:00 and 14:00 are blocked, 10:30 and 14:30 are free', !in_array('11:00', starts($s), true) && !in_array('14:00', starts($s), true) && in_array('10:30', starts($s), true) && in_array('14:30', starts($s), true), implode(',', starts($s)));
$s = hosts_slots_compute($week, '2026-10-19', 2, [], $bk, ['buffer' => 30] + $base);
check('a 2 hour booking needs room on both sides', !in_array('10:00', starts($s), true) && in_array('14:30', starts($s), true) && !in_array('10:30', starts($s), true), implode(',', starts($s)));
check('another day is not affected by the booking', count(hosts_slots_compute($week, '2026-10-20', 1, [], $bk, $base)) === 15);
check('"HH:MM:SS" start times from the database work', same(starts(hosts_slots_compute($week, '2026-10-19', 1, [], [['date' => '2026-10-19', 'start' => '12:00:00', 'hours' => 2.0]], $base)), starts(hosts_slots_compute($week, '2026-10-19', 1, [], $bk, $base))));
$fri = [['date' => '2026-10-23', 'start' => '23:00', 'hours' => 3.0]]; // Friday 23:00 to 02:00 Saturday
$s = hosts_slots_compute($week, '2026-10-24', 1, [], $fri, $base);
check('a Friday late booking blocks Saturday\'s early hours', $s === [], implode(',', starts($s)));
$fri = [['date' => '2026-10-23', 'start' => '20:00', 'hours' => 2.0]];
$s = hosts_slots_compute($week, '2026-10-24', 1, [], $fri, $base);
check('a Friday booking that ends before midnight leaves Saturday free', count($s) === 3);
$s = hosts_slots_compute($week, '2026-10-23', 1, [], [['date' => '2026-10-24', 'start' => '00:30', 'hours' => 1.0]], $base);
check('a Saturday 00:30 booking blocks the end of Friday\'s window', !in_array('00:00', starts($s), true) && !in_array('00:30', starts($s), true) && in_array('23:00', starts($s), true), implode(',', starts($s)));
check('two bookings back to back leave no gap', starts(hosts_slots_compute($week, '2026-10-19', 1, [], [['date' => '2026-10-19', 'start' => '10:00', 'hours' => 4.0], ['date' => '2026-10-19', 'start' => '14:00', 'hours' => 4.0]], $base)) === []);
check('a booking on the whole day leaves nothing', hosts_slots_compute($week, '2026-10-19', 0.5, [], [['date' => '2026-10-19', 'start' => '10:00', 'hours' => 8.0]], $base) === []);

section('Price quotes');
$hp = ['hourly_rate' => 2500, 'half_day_rate' => 9000, 'full_day_rate' => 15000, 'travel_available' => 1, 'travel_fee' => 800, 'min_hours' => 1];
$q = hosts_quote($hp, null, 2, false, ['deposit_pct' => 50, 'commission_pct' => 15]);
check('2 hours at the hourly rate', same($q['base'], 5000.0) && same($q['total'], 5000.0));
check('deposit 50%, balance the rest', same($q['deposit'], 2500.0) && same($q['balance'], 2500.0));
check('commission 15% and the host keeps the rest', same($q['commission'], 750.0) && same($q['host_earning'], 4250.0));
$q = hosts_quote($hp, null, 2, true, ['deposit_pct' => 50, 'commission_pct' => 15]);
check('travel fee is added when travel is on', same($q['travel_fee'], 800.0) && same($q['total'], 5800.0));
$q = hosts_quote(['travel_available' => 0, 'travel_fee' => 800] + $hp, null, 2, true, ['deposit_pct' => 50, 'commission_pct' => 15]);
check('travel fee is ignored when the Host does not travel', same($q['travel_fee'], 0.0) && same($q['total'], 5000.0));
$q = hosts_quote($hp, ['duration_hours' => 3, 'price' => 7500], 99, false, ['deposit_pct' => 50, 'commission_pct' => 15]);
check('a package sets both its price and its length', same($q['hours'], 3.0) && same($q['total'], 7500.0));
$q = hosts_quote($hp, ['duration_hours' => 2, 'price' => 5000], 2, false, ['deposit_pct' => 5, 'commission_pct' => 15]);
check('the deposit is never smaller than the commission', same($q['deposit'], 750.0) && $q['deposit'] >= $q['commission'], (string)$q['deposit']);
$q = hosts_quote($hp, ['duration_hours' => 1, 'price' => 1000], 1, false, ['deposit_pct' => 100, 'commission_pct' => 15]);
check('deposit never exceeds the total', same($q['deposit'], 1000.0) && same($q['balance'], 0.0));
$q = hosts_quote($hp, ['duration_hours' => 1, 'price' => 1999], 1, false, ['deposit_pct' => 50, 'commission_pct' => 15]);
check('deposit rounds up to a whole shilling', same($q['deposit'], 1000.0), (string)$q['deposit']);
$q = hosts_quote(['hourly_rate' => 0, 'half_day_rate' => 0, 'full_day_rate' => 0, 'min_hours' => 1], null, 2, false, ['deposit_pct' => 50, 'commission_pct' => 15]);
check('no price gives a zero quote with no deposit', same($q['total'], 0.0) && same($q['deposit'], 0.0));

section('Refund tiers');
$tz = new DateTimeZone(date_default_timezone_get());
$start = new DateTimeImmutable('2026-12-10 18:00:00', $tz);
$bkg = ['status' => 'confirmed', 'deposit_amount' => 2000, 'booking_date' => '2026-12-10', 'start_time' => '18:00:00'];
$at = fn(string $mod) => $start->modify($mod)->getTimestamp();
set_for_test('host_cancel_full_hours', '48');
set_for_test('host_cancel_partial_hours', '24');
set_for_test('host_cancel_partial_pct', '50');
$r = hosts_refund_quote($bkg, 'client', $at('-72 hours'));
check('72 hours before: full refund', same($r['pct'], 100.0) && same($r['amount'], 2000.0));
$r = hosts_refund_quote($bkg, 'client', $at('-48 hours'));
check('exactly 48 hours before: full refund', same($r['pct'], 100.0));
$r = hosts_refund_quote($bkg, 'client', $at('-30 hours'));
check('30 hours before: half', same($r['pct'], 50.0) && same($r['amount'], 1000.0));
$r = hosts_refund_quote($bkg, 'client', $at('-24 hours'));
check('exactly 24 hours before: half', same($r['pct'], 50.0));
$r = hosts_refund_quote($bkg, 'client', $at('-10 hours'));
check('10 hours before: nothing', same($r['pct'], 0.0) && same($r['amount'], 0.0));
$r = hosts_refund_quote($bkg, 'client', $at('+1 hour'));
check('after the start: nothing', same($r['pct'], 0.0));
$r = hosts_refund_quote(['status' => 'pending'] + $bkg, 'client', $at('-2 hours'));
check('a request the Host has not answered is refunded in full', same($r['pct'], 100.0));
$r = hosts_refund_quote($bkg, 'host', $at('-2 hours'));
check('when the Host cancels the client gets everything back', same($r['pct'], 100.0));
$r = hosts_refund_quote(['status' => 'awaiting_payment'] + $bkg, 'client', $at('-72 hours'));
check('an unpaid booking has nothing to refund', same($r['amount'], 0.0));
set_for_test('host_cancel_partial_pct', '0');
$r = hosts_refund_quote($bkg, 'client', $at('-30 hours'));
check('with no partial tier, 30 hours before is nothing', same($r['pct'], 0.0));

section('Text rules');
check('plain friendly text is fine', hosts_text_violation('Friendly dinner company and city tours') === null);
check('empty text is fine', hosts_text_violation('') === null && hosts_text_violation('   ') === null);
check('sexual services are refused', hosts_text_violation('I offer sex services for cash') !== null);
check('spaced or dotted spelling is still caught', hosts_text_violation('s.e.x services') !== null && hosts_text_violation('s e x services') !== null);
check('look-alike characters are still caught', hosts_text_violation('s3x services') !== null && hosts_text_violation('h@ppy ending') !== null);
check('ordinary initials are not flagged', hosts_text_violation('Based in the U.S.A. and I love A B C songs') === null);
check('private places are refused for meetings', hosts_private_place_violation('My house') !== null && hosts_private_place_violation('your apartment') !== null);
check('public venues are fine', hosts_private_place_violation('Java House Westlands') === null && hosts_private_place_violation('Karura Forest gate') === null);
foreach ([['call or WhatsApp me on 0712 345 678', 'phone number'], ['jane@example.com', 'email address'], ['jane (at) example (dot) com', 'email address'], ['www.mysite.co.ke', 'link'], ['https://wa.me/254712345678', 'link'], ['+254 712 345678', 'phone number'], ['@jane_ke', 'social handle'], ['follow me on instagram', 'contact app'], ['dm me', 'contact app']] as [$t, $what]) {
    check('contact details caught: ' . $t, same(hosts_contact_violation($t), $what), (string)hosts_contact_violation($t));
}
foreach (['I love food, travel and good conversation.', 'KSh 5,000 for 2 hours', 'Dinner at 7:30 pm, 2026-10-12', 'Instant booking available', 'I speak English and Swahili.'] as $t) {
    check('plain text is not flagged: ' . $t, hosts_contact_violation($t) === null);
}

// =============================================================================================
// PART 2: a full booking on disposable accounts
// =============================================================================================
section('Life cycle (disposable accounts)');
$cleanupIds = [];
$cleanup = function () use (&$cleanupIds) {
    if ($cleanupIds) {
        q('DELETE FROM payments WHERE user_id IN (:ids)', ['ids' => $cleanupIds]);
        q('DELETE FROM notifications WHERE user_id IN (:ids)', ['ids' => $cleanupIds]);
        q('DELETE FROM reports WHERE reporter_id IN (:ids) OR reported_id IN (:ids2)', ['ids' => $cleanupIds, 'ids2' => $cleanupIds]);
        q('DELETE FROM users WHERE id IN (:ids)', ['ids' => $cleanupIds]);
    }
};
register_shutdown_function(function () use ($cleanup) {
    try {
        $cleanup();
        restore_settings();
    } catch (Throwable $e) {
        echo "cleanup failed: " . $e->getMessage() . "\n";
    }
});

try {
    foreach (['host_module_mode' => 'booking', 'hosts_enabled' => '1', 'booking_enabled' => '1', 'host_instant_booking_enabled' => '1', 'host_payout_hold_hours' => '0', 'host_payout_min' => '1000', 'booking_deposit_pct' => '50', 'host_commission_pct' => '15',
              'host_min_notice_hours' => '1', 'host_max_advance_days' => '90', 'host_slot_step_minutes' => '30', 'host_booking_buffer_minutes' => '30', 'host_cancel_partial_pct' => '50', 'host_auto_complete_hours' => '24'] as $k => $v) {
        set_for_test($k, $v);
    }
    hosts_mode_reset(); // the booking life cycle below needs the booking way; contact is the site default
    $stamp = (string)random_int(100000, 999999);
    $county = (int)val("SELECT id FROM locations WHERE type = 'county' AND is_active = 1 ORDER BY id LIMIT 1");
    check('a county exists to meet in', $county > 0);

    $hostId = create_account(['email' => "t-host-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'host', 'display_name' => 'Test Host', 'date_of_birth' => '1993-05-05', 'gender' => 'female', 'county_id' => $county]);
    $c1 = create_account(['email' => "t-c1-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => 'Client One', 'date_of_birth' => '1990-05-05', 'gender' => 'male', 'county_id' => $county]);
    $c2 = create_account(['email' => "t-c2-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => 'Client Two', 'date_of_birth' => '1991-05-05', 'gender' => 'male', 'county_id' => $county]);
    $cleanupIds = [$hostId, $c1, $c2];
    $client1 = ['id' => $c1, 'role' => 'user', 'status' => 'active'];
    $client2 = ['id' => $c2, 'role' => 'user', 'status' => 'active'];
    $hostActor = ['id' => $hostId, 'role' => 'host'];

    // Application: refused while incomplete, accepted when complete
    $r = hosts_submit_application($hostId);
    check('an incomplete application is refused and lists what is missing', !$r['ok'] && count($r['missing']) > 0, json_encode($r['missing']));
    $cat = (int)array_key_first(hosts_categories());
    $services = hosts_services_catalog();
    $sid = (int)array_key_first($services);
    $b = hosts_save_basics($hostId, ['display_name' => 'Test Host', 'tagline' => 'Warm company for dinners', 'category_id' => $cat, 'about' => str_repeat('I enjoy good food, travel and easy conversation. ', 4), 'languages' => ['English']]);
    check('basics save', $b['ok'], json_encode($b['errors']));
    $b = hosts_save_basics($hostId, ['display_name' => 'Test Host', 'tagline' => 'Call me 0712345678', 'category_id' => $cat, 'about' => str_repeat('I enjoy good food. ', 8)]);
    check('basics refuse a phone number in the headline', !$b['ok'] && isset($b['errors']['tagline']));
    $b = hosts_save_basics($hostId, ['display_name' => 'Test Host', 'tagline' => 'Warm company for dinners', 'category_id' => $cat, 'about' => 'Offering sex services and escort for money. ' . str_repeat('More text here. ', 6)]);
    check('basics refuse sexual services', !$b['ok'] && isset($b['errors']['about']));
    $b = hosts_save_services($hostId, ['service_ids' => [$sid], 'hourly_rate' => 100, 'min_hours' => 1]);
    check('services refuse a rate below the minimum', !$b['ok'] && isset($b['errors']['hourly_rate']));
    $b = hosts_save_services($hostId, ['service_ids' => [$sid], 'hourly_rate' => 2500, 'half_day_rate' => 9000, 'full_day_rate' => 15000, 'min_hours' => 1, 'allow_custom_hours' => 1,
        'offer' => [['name' => 'Dinner company', 'duration_hours' => 2, 'price' => 5000], ['name' => 'Conversation', 'duration_hours' => 1, 'price' => 2500]]]);
    check('services save with two packages', $b['ok'], json_encode($b['errors']));
    $b = hosts_save_services($hostId, ['service_ids' => [$sid], 'hourly_rate' => 2500, 'offer' => [['name' => 'Sex massage', 'duration_hours' => 2, 'price' => 5000]]]);
    check('a package with banned wording is refused', !$b['ok']);
    $b = hosts_save_area($hostId, ['location_id' => $county, 'areas' => [$county]]);
    check('area saves', $b['ok'], json_encode($b['errors']));
    $week = hosts_weekly_normalize(array_fill_keys(HOSTS_DAYS, [['10:00', '22:00']]));
    update('host_profiles', ['weekly_availability' => json_encode($week)], ['user_id' => $hostId]);
    $r = hosts_submit_application($hostId);
    check('still missing photos, ID and payout details', !$r['ok'] && count($r['missing']) > 0, json_encode($r['missing']));
    check('a pending-less host is not public', !hosts_is_public(hosts_profile($hostId)));
    // approve directly (photos and ID are an upload matter, covered by the HTTP tests)
    hosts_ext_save($hostId, ['payout_phone' => '254712345678', 'payout_name' => 'Test Host', 'rules_agreed_at' => now(), 'instant_booking' => 0]);
    check('approval works', hosts_set_approval($hostId, 'approved', 'test', null, true));
    check('an approved Host is public', hosts_is_public(hosts_profile($hostId)));
    $offers = hosts_offerings($hostId);
    $dinner = null;
    foreach ($offers as $o) {
        if ($o['name'] === 'Dinner company') {
            $dinner = $o;
        }
    }
    check('packages were stored', $dinner !== null && count($offers) === 2);

    // Find a day the Host is free on
    $day = date('Y-m-d', strtotime('+10 days'));
    $slots = hosts_free_slots($hostId, $day, 2);
    check('the Host has free slots in ten days', count($slots) > 0);
    $input = fn(array $o = []) => $o + ['offering_id' => (int)$dinner['id'], 'date' => $day, 'start' => '14:00', 'venue' => 'Java House Westlands', 'area' => (string)$county, 'note' => '', 'accept' => 1];

    // Validation
    $r = hosts_booking_create($client1, $hostId, $input(['accept' => 0]));
    check('terms must be accepted', !$r['ok'] && isset($r['errors']['terms']));
    $r = hosts_booking_create($client1, $hostId, $input(['venue' => 'My house']));
    check('a private place is refused', !$r['ok'] && isset($r['errors']['venue']));
    $r = hosts_booking_create($client1, $hostId, $input(['note' => 'We can have sex later']));
    check('banned wording in the note is refused', !$r['ok'] && isset($r['errors']['note']));
    $r = hosts_booking_create($client1, $hostId, $input(['date' => 'x']));
    check('a bad date is refused', !$r['ok'] && isset($r['errors']['date']));
    $r = hosts_booking_create($client1, $hostId, $input(['start' => '03:00']));
    check('a time outside the Host\'s hours is refused', !$r['ok'] && isset($r['errors']['start']));
    $r = hosts_booking_create($client1, $hostId, $input(['offering_id' => 999999]));
    check('an unknown service is refused', !$r['ok'] && isset($r['errors']['offering']));
    $r = hosts_booking_create($client1, $hostId, $input(['offering_id' => 0, 'hours' => 40]));
    check('custom hours beyond the maximum are refused', !$r['ok'] && isset($r['errors']['hours']));
    $r = hosts_booking_create($client1, $hostId, $input(['area' => '99999999']));
    check('an area the Host does not work in is refused', !$r['ok'] && isset($r['errors']['area']));
    $r = hosts_booking_create(['id' => $hostId, 'role' => 'host', 'status' => 'active'], $hostId, $input());
    check('a Host cannot book themself or other Hosts', !$r['ok']);
    $r = hosts_booking_create($client1, $hostId, $input(['date' => date('Y-m-d', strtotime('-1 day'))]));
    check('the past is refused', !$r['ok']);

    // Book and pay (not instant: the Host must accept)
    $r = hosts_booking_create($client1, $hostId, $input());
    check('booking is created', $r['ok'] && $r['booking_id'] > 0 && $r['payment_id'] > 0, json_encode($r['errors']));
    $b1 = (int)$r['booking_id'];
    $p1 = (int)$r['payment_id'];
    $bk = row('SELECT * FROM host_bookings WHERE id = :id', ['id' => $b1]);
    check('awaiting payment, price and deposit set', $bk['status'] === 'awaiting_payment' && (float)$bk['total_amount'] === 5000.0 && (float)$bk['deposit_amount'] === 2500.0 && (float)$bk['commission_amount'] === 750.0);
    check('the payment is for the deposit', (float)val('SELECT amount FROM payments WHERE id = :id', ['id' => $p1]) === 2500.0);
    check('the slot is held while unpaid', !in_array('14:00', starts(hosts_free_slots($hostId, $day, 2)), true));
    $r2 = hosts_booking_create($client2, $hostId, $input());
    check('a second client cannot take the held slot', !$r2['ok'] && isset($r2['errors']['start']));
    check('the slot is free again for the same client re-booking', in_array('14:00', starts(hosts_free_slots($hostId, $day, 2, [$b1])), true));
    $r2 = hosts_booking_create($client2, $hostId, $input(['start' => '15:00']));
    check('overlap inside the buffer is refused', !$r2['ok']);
    $r2 = hosts_booking_create($client2, $hostId, $input(['start' => '16:30']));
    check('a slot after the buffer is allowed', $r2['ok'], json_encode($r2['errors']));
    $b2 = (int)($r2['booking_id'] ?? 0);

    [$delivered, $note] = payment_complete($p1);
    check('payment_complete delivers', $delivered === true, (string)$note);
    [$delivered2, $note2] = payment_complete($p1);
    check('payment_complete twice does nothing more', $delivered2 === true && same(1, (int)val("SELECT COUNT(*) FROM host_booking_events WHERE booking_id = :b AND event = 'paid'", ['b' => $b1])));
    booking_payment_completed(row('SELECT * FROM payments WHERE id = :id', ['id' => $p1]), ['booking_id' => $b1]);
    check('booking_payment_completed is idempotent', same(1, (int)val("SELECT COUNT(*) FROM host_booking_events WHERE booking_id = :b AND event = 'paid'", ['b' => $b1])));
    $bk = row('SELECT * FROM host_bookings WHERE id = :id', ['id' => $b1]);
    check('paid booking waits for the Host', $bk['status'] === 'pending');
    check('accept-by deadline is set', val('SELECT accept_by FROM host_booking_ext WHERE booking_id = :b', ['b' => $b1]) !== null);

    // Permissions on the transition
    $r = hosts_booking_transition($b1, 'confirmed', ['id' => $c1, 'role' => 'user']);
    check('the client cannot accept', !$r['ok']);
    $r = hosts_booking_transition($b1, 'confirmed', ['id' => $c2, 'role' => 'user']);
    check('another user cannot touch the booking', !$r['ok']);
    $r = hosts_booking_transition($b1, 'completed', $hostActor);
    check('cannot complete a request that is not confirmed', !$r['ok']);
    $r = hosts_booking_transition($b1, 'declined', $hostActor, ['reason' => 'WhatsApp me on 0712345678']);
    check('a decline reason with contact details is refused', !$r['ok']);
    $r = hosts_booking_transition($b1, 'confirmed', $hostActor);
    check('the Host accepts', $r['ok'] && $r['status'] === 'confirmed', $r['error']);
    $r = hosts_booking_transition($b1, 'confirmed', $hostActor);
    check('accepting twice is refused', !$r['ok']);
    $r = hosts_booking_transition($b1, 'completed', $hostActor);
    check('cannot complete before the end time', !$r['ok']);
    $r = hosts_booking_transition($b1, 'no_show', $hostActor);
    check('cannot report a no-show before the start', !$r['ok']);

    // Complete: move the booking into the past
    update('host_bookings', ['booking_date' => date('Y-m-d', strtotime('-3 days'))], ['id' => $b1]);
    $r = hosts_review_create($b1, $c1, 5, 'Lovely evening and great company.');
    check('cannot review before completion', !$r['ok']);
    $r = hosts_booking_transition($b1, 'completed', $hostActor);
    check('the Host completes it', $r['ok'] && $r['status'] === 'completed', $r['error']);
    $r = hosts_booking_transition($b1, 'cancelled', ['id' => $c1, 'role' => 'user'], ['reason' => 'late']);
    check('a completed booking cannot be cancelled', !$r['ok']);
    $bk = row('SELECT * FROM host_bookings WHERE id = :id', ['id' => $b1]);
    $ex = row('SELECT * FROM host_booking_ext WHERE booking_id = :id', ['id' => $b1]);
    check('commission is 15% of the full price', (float)$bk['commission_amount'] === 750.0 && (float)$bk['host_earning'] === 4250.0);
    check('the platform owes the host the deposit minus commission', (float)$ex['settled_amount'] === 1750.0, (string)$ex['settled_amount']);
    $bal = hosts_balance($hostId);
    check('balance shows it as available (no hold)', (float)$bal['available'] === 1750.0 && (float)$bal['earned_total'] === 4250.0 && (int)$bal['completed_count'] === 1, json_encode($bal));
    check('earned this month never exceeds earned in total', (float)$bal['earned_month'] <= (float)$bal['earned_total'] + 0.005 && (float)$bal['earned_month'] === 4250.0, json_encode($bal));
    check('total earned and bookings are counted once', (float)val('SELECT total_earned FROM host_profiles WHERE user_id = :h', ['h' => $hostId]) === 4250.0 && (int)val('SELECT total_bookings FROM host_profiles WHERE user_id = :h', ['h' => $hostId]) === 1);

    // Reviews
    $r = hosts_review_create($b1, $c2, 5, 'I was not there at all, honestly.');
    check('only the booking\'s client can review', !$r['ok']);
    $r = hosts_review_create($b1, $c1, 0, 'x');
    check('a rating of 0 is refused', !$r['ok']);
    $r = hosts_review_create($b1, $c1, 5, 'Call her on 0712 345 678 for a good time');
    check('a review with a phone number is refused', !$r['ok']);
    $r = hosts_review_create($b1, $c1, 5, 'Lovely evening and great company, would book again.', false);
    check('a good review is saved for moderation', $r['ok'], $r['error']);
    $rid = (int)val('SELECT id FROM host_reviews WHERE booking_id = :b', ['b' => $b1]);
    $r = hosts_review_create($b1, $c1, 4, 'A second review of the same booking is refused.');
    check('only one review per booking', !$r['ok']);
    check('it is not public until approved', hosts_review_summary($hostId)['count'] === 0);
    $r = hosts_review_reply($rid, $hostId, 'Thank you!');
    check('the Host cannot reply to an unpublished review', !$r['ok']);
    check('a pending review shows in the admin queue count', (int)val("SELECT COUNT(*) FROM host_reviews WHERE status = 'pending'") >= 1 && (admin_core_counts_forget() === null) && admin_pending_counts()['host_reviews_pending'] >= 1);
    check('an unknown admin action is refused', !hosts_review_decide($rid, 'delete')['ok']);
    check('a missing review is refused', !hosts_review_decide(99999999, 'approve')['ok']);
    $r = hosts_review_decide($rid, 'reject');
    check('the admin can decline a review', $r['ok'] && $r['status'] === 'rejected' && hosts_review_summary($hostId)['count'] === 0);
    $r = hosts_review_decide($rid, 'approve');
    check('the admin can publish it after all', $r['ok'] && $r['status'] === 'approved' && hosts_review_summary($hostId)['count'] === 1);
    $r = hosts_review_decide($rid, 'approve');
    check('publishing twice changes nothing', $r['ok'] && !empty($r['unchanged']));
    $r = hosts_review_decide($rid, 'reject');
    check('taking a published review down updates the rating', $r['ok'] && (int)val('SELECT review_count FROM host_profiles WHERE user_id = :h', ['h' => $hostId]) === 0 && (float)val('SELECT rating_avg FROM host_profiles WHERE user_id = :h', ['h' => $hostId]) === 0.0);
    hosts_review_decide($rid, 'approve');
    check('approved review counts', hosts_review_summary($hostId)['count'] === 1 && (float)val('SELECT rating_avg FROM host_profiles WHERE user_id = :h', ['h' => $hostId]) === 5.0);
    $r = hosts_review_reply($rid, $c1 + 0, 'hijack');
    check('someone else cannot reply to the review', !$r['ok']);
    $r = hosts_review_reply($rid, $hostId, 'Email me at host@example.com');
    check('a reply with contact details is refused', !$r['ok']);
    $r = hosts_review_reply($rid, $hostId, 'Thank you, it was a pleasure.');
    check('the Host replies', $r['ok'], $r['error']);

    // Payouts
    $r = hosts_payout_request($hostId, 500);
    check('below the minimum payout is refused', !$r['ok']);
    $r = hosts_payout_request($hostId, 5000);
    check('more than the balance is refused', !$r['ok']);
    $r = hosts_payout_request($hostId, 1750);
    check('payout is requested', $r['ok'] && $r['id'] > 0, $r['error']);
    $bal = hosts_balance($hostId);
    check('the balance moves from available to requested', (float)$bal['available'] === 0.0 && (float)$bal['requested'] === 1750.0, json_encode($bal));
    $r = hosts_payout_request($hostId, 1000);
    check('cannot request money that is already requested', !$r['ok']);
    update('host_payouts', ['status' => 'paid'], ['host_id' => $hostId]);
    $bal = hosts_balance($hostId);
    check('a paid payout moves to paid out', (float)$bal['paid_out'] === 1750.0 && (float)$bal['requested'] === 0.0 && (float)$bal['available'] === 0.0);

    // Cancellations and refunds (second booking, 30 days away)
    $far = date('Y-m-d', strtotime('+30 days'));
    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '12:00']));
    check('a far-off booking is created', $r['ok'], json_encode($r['errors']));
    $b3 = (int)$r['booking_id'];
    check('cancelling an unpaid booking needs no refund', hosts_booking_transition($b3, 'cancelled', ['id' => $c1, 'role' => 'user'])['ok'] && (float)val('SELECT refund_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b3]) === 0.0);
    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '12:00']));
    $b3 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    $r = hosts_booking_transition($b3, 'cancelled', ['id' => $c1, 'role' => 'user'], ['reason' => 'Plans changed']);
    check('client cancels a request the Host has not answered: full refund due', $r['ok'] && (float)val('SELECT refund_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b3]) === 2500.0 && same('due', (string)val('SELECT refund_status FROM host_booking_ext WHERE booking_id = :b', ['b' => $b3])));

    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '12:00']));
    $b4 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    hosts_booking_transition($b4, 'confirmed', $hostActor);
    $r = hosts_booking_transition($b4, 'declined', $hostActor, ['reason' => 'Sorry']);
    check('a confirmed booking cannot be declined', !$r['ok']);
    $r = hosts_booking_transition($b4, 'cancelled', $hostActor, ['reason' => 'Family emergency']);
    check('the Host cancels a confirmed booking: client gets everything back', $r['ok'] && (float)val('SELECT refund_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b4]) === 2500.0);

    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '12:00']));
    $b5 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    hosts_booking_transition($b5, 'confirmed', $hostActor);
    update('host_bookings', ['booking_date' => date('Y-m-d', strtotime('+30 hours')), 'start_time' => date('H:i:s', strtotime('+30 hours'))], ['id' => $b5]);
    $r = hosts_booking_transition($b5, 'cancelled', ['id' => $c1, 'role' => 'user'], ['reason' => 'Cannot make it']);
    check('client cancels 30 hours before: half of the deposit comes back', $r['ok'] && (float)val('SELECT refund_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b5]) === 1250.0, json_encode($r));

    // No-show
    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '12:00']));
    $b6 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    hosts_booking_transition($b6, 'confirmed', $hostActor);
    update('host_bookings', ['booking_date' => date('Y-m-d', strtotime('-2 days'))], ['id' => $b6]);
    $r = hosts_booking_transition($b6, 'no_show', ['id' => $c1, 'role' => 'user']);
    check('the client cannot report a no-show', !$r['ok']);
    $r = hosts_booking_transition($b6, 'no_show', $hostActor, ['reason' => 'Did not arrive']);
    check('the Host reports a no-show: deposit is kept', $r['ok'] && (float)val('SELECT refund_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b6]) === 0.0);
    check('the Host is credited deposit minus commission on the deposit', (float)val('SELECT settled_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b6]) === 2125.0, (string)val('SELECT settled_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b6]));

    // Housekeeping: unpaid expiry, unanswered requests, auto-complete
    $r = hosts_booking_create($client2, $hostId, $input(['date' => $far, 'start' => '16:00']));
    $b8 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    q("UPDATE host_booking_ext SET accept_by = DATE_SUB(NOW(), INTERVAL 1 HOUR) WHERE booking_id = :b", ['b' => $b8]);
    $r = hosts_booking_create($client1, $hostId, $input(['date' => date('Y-m-d', strtotime('+11 days')), 'start' => '12:00']));
    $b9 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    hosts_booking_transition($b9, 'confirmed', $hostActor);
    update('host_bookings', ['booking_date' => date('Y-m-d', strtotime('-5 days'))], ['id' => $b9]);
    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '12:00'])); // unpaid, left to go stale
    $b7 = (int)$r['booking_id'];
    q("UPDATE host_bookings SET created_at = DATE_SUB(NOW(), INTERVAL 3 HOUR) WHERE id = :b", ['b' => $b7]);
    $h = hosts_housekeeping();
    check('housekeeping reports what it did', $h['unpaid_cancelled'] >= 1 && $h['auto_declined'] >= 1 && $h['auto_completed'] >= 1, json_encode($h));
    check('the stale unpaid booking is cancelled', same('cancelled', (string)val('SELECT status FROM host_bookings WHERE id = :b', ['b' => $b7])));
    check('the unanswered request is declined and refunded', same('declined', (string)val('SELECT status FROM host_bookings WHERE id = :b', ['b' => $b8])) && (float)val('SELECT refund_amount FROM host_booking_ext WHERE booking_id = :b', ['b' => $b8]) === 2500.0);
    check('the old confirmed booking is completed automatically', same('completed', (string)val('SELECT status FROM host_bookings WHERE id = :b', ['b' => $b9])));
    $h2 = hosts_housekeeping();
    check('housekeeping is safe to run again', $h2['unpaid_cancelled'] === 0 && $h2['auto_declined'] === 0 && $h2['auto_completed'] === 0, json_encode($h2));

    // Reports
    $r = hosts_report($c1, 'host', $hostId, 'Fake profile or photos', 'The photos do not match');
    check('a client can report a Host', $r['ok'], $r['error']);
    $r = hosts_report($c1, 'host', $hostId, 'Fake profile or photos', 'again');
    check('a duplicate report is refused', !$r['ok']);
    $r = hosts_report($c1, 'host', $hostId, 'Because I said so', '');
    check('only listed reasons are accepted', !$r['ok']);
    $r = hosts_report($c2, 'booking', $b1, 'Spam or scam', '');
    check('someone else cannot report a booking that is not theirs', !$r['ok']);
    $r = hosts_report($hostId, 'booking', $b1, 'Payment outside platform', 'asked for cash');
    check('the Host can report their own booking', $r['ok'], $r['error']);

    // Suspended Hosts cannot accept or complete, can still decline or cancel
    $r = hosts_booking_create($client2, $hostId, $input(['date' => $far, 'start' => '19:00']));
    $b10 = (int)$r['booking_id'];
    payment_complete((int)$r['payment_id']);
    hosts_set_approval($hostId, 'suspended', 'test');
    $r = hosts_booking_transition($b10, 'confirmed', $hostActor);
    check('a suspended Host cannot accept', !$r['ok']);
    $r = hosts_booking_create($client1, $hostId, $input(['date' => $far, 'start' => '20:30']));
    check('a suspended Host cannot be booked', !$r['ok']);
    $r = hosts_booking_transition($b10, 'declined', $hostActor, ['reason' => 'Unavailable']);
    check('a suspended Host can still decline', $r['ok'], $r['error']);
    check('a suspended Host is not in the directory', !in_array($hostId, array_map(fn($x) => (int)$x['user_id'], hosts_directory(['q' => 'Test Host'])['rows']), true));
} catch (Throwable $e) {
    check('no exception in the life cycle', false, $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
}


// ---------------------------------------------------------------------------------------------------------------------
// Part 3: services catalogue, price hiding, listing fees, PWA manifest
// ---------------------------------------------------------------------------------------------------------------------
$saved3 = [];
foreach (['host_rates_hideable', 'host_listing_fee_enabled', 'host_listing_price_weekly', 'host_listing_price_monthly', 'host_listing_price_yearly', 'host_max_services', 'pwa_name', 'pwa_shortcuts', 'pwa_start_path', 'pwa_display'] as $k) {
    $saved3[$k] = (string)setting($k);
}
$h3 = 0;
try {
    $groups = hosts_services_grouped();
    check('services come grouped for the picker', count($groups) >= 3 && count(hosts_services_catalog()) >= 20);
    $banned = 0;
    foreach (hosts_services_catalog() as $svc) {
        if (hosts_text_violation($svc['name'] . ' ' . $svc['description']) !== null) {
            $banned++;
        }
    }
    check('no catalogue service trips the banned-terms list', $banned === 0, (string)$banned);

    setting_set('host_rates_hideable', '0');
    check('prices always show when hiding is not allowed', hosts_prices_allowed(0) === true);
    setting_set('host_rates_hideable', '1');
    check('a Host can hide prices when the admin allows it', hosts_prices_allowed(0) === false && hosts_prices_allowed(1) === true);

    setting_set('host_listing_fee_enabled', '0');
    check('no listing SQL when no fee is charged', hosts_listing_sql('hp') === '' && hosts_listing_active([]) === true);
    setting_set('host_listing_fee_enabled', '1');
    setting_set('host_listing_price_weekly', '200');
    setting_set('host_listing_price_monthly', '500');
    setting_set('host_listing_price_yearly', '0');
    $plans = hosts_listing_plans();
    check('only priced listing periods are offered', array_keys($plans) === ['weekly', 'monthly'] && $plans['monthly']['days'] === 30);
    check('an unpaid Host is not listed', hosts_listing_active(['listing_until' => null]) === false && hosts_listing_sql('hp') !== '');

    $h3 = create_account(['email' => 'listing.test.' . bin2hex(random_bytes(3)) . '@example.test', 'password' => 'Listing#Test2026', 'role' => 'host', 'display_name' => 'Listing Test']);
    $until = hosts_listing_extend($h3, 7, 'weekly');
    check('paying extends the listing', strtotime($until) > time() + 6 * 86400 && hosts_listing_active(hosts_ext($h3)));
    $until2 = hosts_listing_extend($h3, 30, 'monthly');
    check('a second payment adds to the end of the first', strtotime($until2) - strtotime($until) >= 29 * 86400);
    q('UPDATE host_profile_ext SET listing_until = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE user_id = :u', ['u' => $h3]);
    check('an expired listing is not active', !hosts_listing_active(hosts_ext($h3)));

    // Payment type delivers listing days
    $pid = (int)payment_create($h3, 'host_listing', 'test', 500.0, 'Host listing, monthly', ['period' => 'monthly', 'days' => 30])['id'];
    [$ok, $note] = payment_complete($pid);
    check('a host_listing payment lists the Host', $ok && hosts_listing_active(hosts_ext($h3)), $note);
    check('the payment summary names the listing', payment_summary(row('SELECT * FROM payments WHERE id = :i', ['i' => $pid]))[0] === 'Host listing');

    // PWA manifest from settings
    setting_set('pwa_name', 'ConnectHub Test App');
    setting_set('pwa_display', 'fullscreen');
    setting_set('pwa_start_path', '/discover');
    setting_set('pwa_shortcuts', "One | /one\nBad | https://evil.test\nTwo | /two\nThree | //evil.test\nFour | /four\nFive | /five\nSix | /six");
    $m = pwa_manifest();
    check('manifest uses the admin name, window and start page', $m['name'] === 'ConnectHub Test App' && $m['display'] === 'fullscreen' && str_contains($m['start_url'], '/discover'));
    check('manifest shortcuts keep only safe site paths, at most 4', count($m['shortcuts']) === 4 && !str_contains(json_encode($m['shortcuts']), 'evil'));
    check('manifest has the three icons', count($m['icons']) === 3);
    setting_set('pwa_start_path', 'https://evil.test');
    check('an outside start page falls back to the home page', !str_contains(pwa_manifest()['start_url'], 'evil'));
} catch (Throwable $e) {
    check('no exception in part 3', false, $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
} finally {
    if ($h3) {
        q('DELETE FROM payments WHERE user_id = :u', ['u' => $h3]);
        q('DELETE FROM users WHERE id = :u', ['u' => $h3]);
    }
    foreach ($saved3 as $k => $v) {
        setting_set($k, $v);
    }
}

echo "\n" . ($fail === 0 ? "All $pass checks passed.\n" : "$fail of " . ($pass + $fail) . " checks FAILED.\n");
exit($fail === 0 ? 0 : 1);
