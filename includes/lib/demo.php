<?php
/**
 * Demo data: sample members, hosts and activity so a fresh install can be explored and tested.
 * Every demo account uses the @demo.connecthub.test domain, so demo_remove() can delete all of it.
 * Photos are generated abstract silhouettes, never real people.
 */

declare(strict_types=1);

const DEMO_DOMAIN = 'demo.connecthub.test';
/** Password shared by the demo accounts. It is derived from this site's secret key, so it is different on every installation and never appears in the source. */
function demo_password(): string
{
    return 'Dm#' . substr(hash_hmac('sha256', 'demo-accounts-v1', (string)config('app_key')), 0, 12) . '9x';
}

function demo_names(): array
{
    return [
        'female' => ['Amina', 'Wanjiru', 'Akinyi', 'Njeri', 'Mwende', 'Zawadi', 'Atieno', 'Chebet', 'Faith', 'Grace', 'Halima', 'Linet', 'Mercy', 'Naomi', 'Purity', 'Wambui', 'Brenda', 'Cynthia', 'Diana', 'Esther', 'Fatuma', 'Joy', 'Lucy', 'Winnie'],
        'male' => ['Kamau', 'Otieno', 'Mutua', 'Kiprono', 'Barasa', 'Omondi', 'Njoroge', 'Kariuki', 'Hassan', 'Baraka', 'Brian', 'Kevin', 'Allan', 'Victor', 'Ian', 'Collins', 'Peter', 'Samuel', 'Joseph', 'Felix', 'Dennis', 'Mark', 'Tony', 'Jackson'],
    ];
}

function demo_make_image(int $seed, string $gender): string
{
    mt_srand($seed);
    $w = 900;
    $h = 1200;
    $img = imagecreatetruecolor($w, $h);
    $palettes = [[[232, 82, 63], [244, 166, 58]], [[108, 92, 231], [232, 82, 63]], [[244, 166, 58], [247, 195, 90]], [[42, 34, 48], [232, 82, 63]], [[60, 120, 140], [244, 166, 58]], [[140, 60, 110], [244, 140, 100]], [[30, 100, 90], [200, 220, 120]]];
    [$c1, $c2] = $palettes[$seed % count($palettes)];
    for ($y = 0; $y < $h; $y++) {
        $t = $y / $h;
        $col = imagecolorallocate($img, (int)($c1[0] * (1 - $t) + $c2[0] * $t), (int)($c1[1] * (1 - $t) + $c2[1] * $t), (int)($c1[2] * (1 - $t) + $c2[2] * $t));
        imageline($img, 0, $y, $w, $y, $col);
    }
    imagealphablending($img, true);
    for ($i = 0; $i < 5; $i++) {
        $c = imagecolorallocatealpha($img, 255, 255, 255, mt_rand(108, 122));
        imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), mt_rand(200, 600), mt_rand(200, 600), $c);
    }
    $skin = [[141, 85, 58], [166, 105, 72], [198, 134, 94], [112, 68, 48], [224, 172, 130]][$seed % 5];
    $body = imagecolorallocate($img, max(0, $skin[0] - 70 + ($seed % 3) * 25), max(0, $skin[1] - 50), max(0, $skin[2] - 40));
    $skinC = imagecolorallocate($img, $skin[0], $skin[1], $skin[2]);
    $hair = imagecolorallocate($img, 28 + ($seed % 4) * 6, 22, 20);
    imagefilledellipse($img, (int)($w / 2), (int)($h * 0.98), (int)($w * 0.95), (int)($h * 0.62), $body);
    imagefilledrectangle($img, (int)($w / 2 - 55), (int)($h * 0.58), (int)($w / 2 + 55), (int)($h * 0.72), $skinC);
    if ($gender === 'female') {
        imagefilledellipse($img, (int)($w / 2), (int)($h * 0.44), 400, 470, $hair);
        imagefilledrectangle($img, (int)($w / 2 - 200), (int)($h * 0.44), (int)($w / 2 + 200), (int)($h * 0.66), $hair);
    } else {
        imagefilledellipse($img, (int)($w / 2), (int)($h * 0.405), 330, 360, $hair);
    }
    imagefilledellipse($img, (int)($w / 2), (int)($h * 0.46), 300, 380, $skinC);
    $path = sys_get_temp_dir() . '/chdemo_' . $seed . '_' . random_token(4) . '.jpg';
    imagejpeg($img, $path, 88);
    return $path;
}

/** Create demo members. Returns counts. Safe to run more than once (adds more). */
function demo_seed(int $explorers = 40, int $hosts = 8): array
{
    @set_time_limit(300);
    $names = demo_names();
    $towns = rows("SELECT id, name, latitude, longitude, parent_id FROM locations WHERE type = 'town' AND slug IN ('westlands','kilimani','karen','nyali','thika','eldoret','naivasha','nanyuki','kitengela','ruaka','kisumu-city','nakuru-town','athi-river','diani-beach','malindi','nyeri-town','machakos-town','ongata-rongai','nairobi-cbd','mombasa-island','kiambu-town') ORDER BY id");
    if (!$towns) {
        throw new RuntimeException('Locations are missing.');
    }
    $interests = col('SELECT id FROM interests WHERE is_active = 1');
    $bios = ['Weekend hiker, weekday coffee addict.', 'Looking for good conversation and someone who laughs easily.', 'I will always say yes to nyama choma.', 'New in town and keen to meet people who know the best spots.', 'Big on family, faith and Sunday lunches.', 'Part dreamer, part planner. Mostly hungry.', 'Here for real connections, not endless chatting.', 'Ask me about my last trip. I will talk for an hour.', 'Gym in the morning, series at night, football on weekends.', 'Quiet on first meeting, loud once we are friends.', 'Chasing sunsets and good playlists.', 'Software by day, amateur chef by night.'];
    $jobs = ['Software developer', 'Nurse', 'Teacher', 'Accountant', 'Graphic designer', 'Entrepreneur', 'Marketing manager', 'Engineer', 'Lawyer', 'Photographer', 'Banker', 'Doctor', 'Content creator', 'Architect'];
    $intents = ['relationship', 'dating', 'friendship', 'casual', 'not_sure'];
    $created = 0;
    $startIdx = (int)val("SELECT COUNT(*) FROM users WHERE email LIKE '%@" . DEMO_DOMAIN . "'");
    $planPremium = (int)val("SELECT id FROM subscription_plans WHERE slug = 'premium'");
    $hash = hash_password(demo_password());

    $makeUser = function (int $i, string $role, string $gender) use ($names, $towns, $interests, $bios, $jobs, $intents, $hash) {
        $first = $names[$gender][$i % count($names[$gender])];
        $town = $towns[$i % count($towns)];
        $email = 'demo' . $i . '.' . strtolower($first) . '@' . DEMO_DOMAIN;
        if ((int)val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
            return 0;
        }
        $uid = insert('users', ['email' => $email, 'password_hash' => $hash, 'role' => $role, 'status' => 'active', 'email_verified_at' => now(), 'last_active_at' => date('Y-m-d H:i:s', time() - mt_rand(0, 86400 * 6)), 'created_at' => date('Y-m-d H:i:s', time() - mt_rand(86400, 86400 * 60))]);
        $dob = date('Y-m-d', strtotime('-' . mt_rand(21, 44) . ' years -' . mt_rand(0, 360) . ' days'));
        insert('user_profiles', [
            'user_id' => $uid, 'display_name' => $first, 'date_of_birth' => $dob, 'gender' => $gender,
            'interested_in' => $gender === 'female' ? 'male' : 'female', 'bio' => $bios[$i % count($bios)], 'occupation' => $jobs[$i % count($jobs)],
            'height_cm' => mt_rand(155, 190), 'intent' => $intents[$i % count($intents)], 'looking_for' => 'Someone genuine',
            'is_verified' => $i % 3 === 0 ? 1 : 0, 'onboarding_completed' => 1, 'onboarding_step' => 7, 'profile_completion' => mt_rand(70, 100),
            'show_contact' => $i % 4 === 0 ? 1 : 0, 'contact_whatsapp' => '07' . mt_rand(10000000, 29999999), 'contact_instagram' => strtolower($first) . '_ke',
        ]);
        $lat = (float)$town['latitude'] + (mt_rand(-80, 80) / 10000);
        $lng = (float)$town['longitude'] + (mt_rand(-80, 80) / 10000);
        $cty = location_by_id((int)$town['parent_id']);
        q('INSERT INTO user_locations (user_id, latitude, longitude, location_id, county_id, town_name, county_name, country, source) VALUES (?,?,?,?,?,?,?,?,?)',
            [$uid, $lat, $lng, $town['id'], $cty['id'] ?? null, $town['name'], $cty['name'] ?? null, 'Kenya', 'manual']);
        q('INSERT IGNORE INTO discovery_preferences (user_id, age_min, age_max, distance_km) VALUES (?,?,?,?)', [$uid, 21, 45, 100]);
        q('INSERT IGNORE INTO notification_preferences (user_id) VALUES (?)', [$uid]);
        $mine = (array)array_rand(array_flip($interests), min(count($interests), mt_rand(4, 8)));
        foreach ($mine as $iid) {
            q('INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (?,?)', [$uid, $iid]);
        }
        for ($p = 0; $p < mt_rand(2, 4); $p++) {
            $src = demo_make_image($uid * 10 + $p, $gender);
            $res = process_profile_photo(['error' => UPLOAD_ERR_OK, 'tmp_name' => $src, 'size' => filesize($src), 'name' => 'demo.jpg'], $uid, 8);
            @unlink($src);
            if ($res['ok']) {
                $res['data'] += ['user_id' => $uid, 'is_primary' => $p === 0 ? 1 : 0, 'sort_order' => $p, 'moderation_status' => 'approved'];
                insert('user_photos', $res['data']);
            }
        }
        return $uid;
    };

    $ids = [];
    for ($i = 0; $i < $explorers; $i++) {
        $idx = $startIdx + $i + 1;
        $uid = $makeUser($idx, 'user', $idx % 2 ? 'female' : 'male');
        if ($uid) {
            $ids[] = $uid;
            $created++;
        }
    }
    // Hosts
    $cats = col('SELECT id FROM host_categories ORDER BY sort_order');
    $svcs = col('SELECT id FROM host_services ORDER BY sort_order');
    $areaIds = col("SELECT id FROM locations WHERE type = 'county' AND is_popular = 1 ORDER BY sort_order LIMIT 5");
    $hostIds = [];
    for ($h = 0; $h < $hosts; $h++) {
        $idx = 500 + $startIdx + $h + 1;
        $gender = $h % 2 ? 'male' : 'female';
        $uid = $makeUser($idx, 'host', $gender);
        if (!$uid) {
            continue;
        }
        $first = $names[$gender][$idx % count($names[$gender])];
        $mySvcs = (array)array_rand(array_flip($svcs), min(count($svcs), mt_rand(3, 6)));
        $week = [];
        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $d) {
            $week[$d] = in_array($d, ['sun'], true) && $h % 2 ? [] : [['10:00', '22:00']];
        }
        insert('host_profiles', [
            'user_id' => $uid, 'category_id' => $cats[$h % count($cats)], 'display_name' => $first,
            'tagline' => ['Dinner company with great stories', 'Your local guide to the city', 'Event plus-one who actually enjoys the party', 'Easy conversation and good taste in restaurants'][$h % 4],
            'about' => "Friendly, well travelled and easy to talk to. I enjoy good food, live music and meeting new people. I keep to the agreed plan and always arrive on time.",
            'hourly_rate' => 2000 + ($h % 4) * 500, 'half_day_rate' => 7000 + ($h % 4) * 1000, 'full_day_rate' => 12000 + ($h % 4) * 2000,
            'min_hours' => 2, 'travel_available' => $h % 2, 'travel_fee' => 1500, 'service_ids' => implode(',', $mySvcs),
            'service_area_ids' => implode(',', array_slice($areaIds, 0, 2 + $h % 3)), 'weekly_availability' => json_encode($week),
            'approval_status' => 'approved', 'approved_at' => now(), 'id_verified' => 1, 'rating_avg' => 4.2 + ($h % 8) / 10, 'review_count' => 3 + $h * 2, 'total_bookings' => 5 + $h * 3,
        ]);
        $hostIds[] = $uid;
        if (function_exists('hosts_demo_enrich')) {
            hosts_demo_enrich($uid, $h); // packages, payout details and a few reviewed bookings (Hosts module)
        }
    }
    // Activity: likes, a few matches, favourites, visits, messages
    $all = array_merge($ids, col("SELECT id FROM users WHERE email LIKE 'demo%@" . DEMO_DOMAIN . "' AND role = 'user' ORDER BY id DESC LIMIT 60"));
    $all = array_values(array_unique(array_map('intval', $all)));
    $swipes = 0;
    $matches = 0;
    foreach ($all as $a) {
        foreach (array_slice($all, 0, 12) as $b) {
            if ($a === $b || mt_rand(0, 3) > 0) {
                continue;
            }
            q('INSERT IGNORE INTO swipes (swiper_id, swiped_id, direction, dwell_ms) VALUES (?,?,?,?)', [$a, $b, mt_rand(0, 5) ? 'like' : 'nope', mt_rand(800, 9000)]);
            $swipes++;
            if (mt_rand(0, 6) === 0) {
                q('INSERT IGNORE INTO profile_views (viewer_id, viewed_id) VALUES (?,?)', [$a, $b]);
            }
        }
    }
    for ($i = 0; $i + 1 < min(10, count($all)); $i += 2) {
        [$x, $y] = pair_ids($all[$i], $all[$i + 1]);
        q('INSERT IGNORE INTO swipes (swiper_id, swiped_id, direction) VALUES (?,?,\'like\'),(?,?,\'like\')', [$x, $y, $y, $x]);
        $mid = q('INSERT IGNORE INTO matches (user1_id, user2_id) VALUES (?,?)', [$x, $y])->rowCount();
        $matches += $mid ? 1 : 0;
    }
    return ['explorers' => $created, 'hosts' => count($hostIds), 'swipes' => $swipes, 'matches' => $matches];
}

/** Create the named QA accounts (explorer, premium, host) with a known password. Returns [email => password]. */
function demo_test_accounts(): array
{
    $out = [];
    $hash = hash_password(demo_password());
    $defs = [
        ['explorer@' . DEMO_DOMAIN, 'user', 'Tester', 'female', 'free'],
        ['premium@' . DEMO_DOMAIN, 'user', 'Premium Tester', 'male', 'premium'],
    ];
    $town = row("SELECT * FROM locations WHERE slug = 'westlands'");
    foreach ($defs as [$email, $role, $name, $gender, $tier]) {
        if ((int)val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
            $out[$email] = demo_password();
            continue;
        }
        $uid = insert('users', ['email' => $email, 'password_hash' => $hash, 'role' => $role, 'status' => 'active', 'email_verified_at' => now(), 'last_active_at' => now()]);
        insert('user_profiles', ['user_id' => $uid, 'display_name' => $name, 'date_of_birth' => date('Y-m-d', strtotime('-28 years')), 'gender' => $gender,
            'interested_in' => $gender === 'female' ? 'male' : 'female', 'bio' => 'Demo account for exploring the site.', 'onboarding_completed' => 1, 'onboarding_step' => 7, 'profile_completion' => 80]);
        $cty = location_by_id((int)$town['parent_id']);
        q('INSERT INTO user_locations (user_id, latitude, longitude, location_id, county_id, town_name, county_name, country, source) VALUES (?,?,?,?,?,?,?,?,?)',
            [$uid, $town['latitude'], $town['longitude'], $town['id'], $cty['id'] ?? null, $town['name'], $cty['name'] ?? null, 'Kenya', 'manual']);
        q('INSERT IGNORE INTO discovery_preferences (user_id, age_min, age_max, distance_km) VALUES (?,?,?,?)', [$uid, 21, 45, 200]);
        q('INSERT IGNORE INTO notification_preferences (user_id) VALUES (?)', [$uid]);
        $src = demo_make_image($uid * 7, $gender);
        $res = process_profile_photo(['error' => UPLOAD_ERR_OK, 'tmp_name' => $src, 'size' => filesize($src), 'name' => 'demo.jpg'], $uid, 8);
        @unlink($src);
        if ($res['ok']) {
            $res['data'] += ['user_id' => $uid, 'is_primary' => 1, 'sort_order' => 0, 'moderation_status' => 'approved'];
            insert('user_photos', $res['data']);
        }
        if ($tier === 'premium') {
            subscription_activate($uid, (int)val("SELECT id FROM subscription_plans WHERE slug='premium'"), 'monthly', null, 365);
        }
        $out[$email] = demo_password();
    }
    return $out;
}

/** Delete every demo account, with its photos. Returns the number of members removed. */
function demo_remove(): int
{
    // never remove administrators (the QA admin lives on the demo domain too)
    $ids = col("SELECT id FROM users WHERE email LIKE '%@" . DEMO_DOMAIN . "' AND role <> 'admin'");
    if (!$ids) {
        return 0;
    }
    foreach (rows('SELECT large_path, medium_path, thumb_path, blur_path FROM user_photos WHERE user_id IN (:ids)', ['ids' => $ids]) as $p) {
        foreach ($p as $path) {
            upload_delete($path);
        }
    }
    q('DELETE FROM users WHERE id IN (:ids)', ['ids' => $ids]);
    cache_clear();
    return count($ids);
}
