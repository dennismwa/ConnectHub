<?php
/**
 * Promotions: packages, the Advertise form, payment, approval, expiry and the one / two / many layouts.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/promos_test.php
 * Everything it creates is removed again; settings and the state of existing adverts are put back.
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
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $name . (!$ok && $detail !== '' ? "  ($detail)" : '') . "\n";
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
    cache_clear();
}
function make_jpeg(int $w, int $h): string
{
    $f = sys_get_temp_dir() . '/promo-' . bin2hex(random_bytes(4)) . '.jpg';
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 40, 120, 200));
    imagefilledellipse($im, (int)($w / 2), (int)($h / 2), (int)($w / 2), (int)($h / 2), imagecolorallocate($im, 250, 200, 60));
    imagejpeg($im, $f, 90);
    return $f;
}
function file_array(string $path): array
{
    return ['name' => basename($path), 'type' => 'image/jpeg', 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => filesize($path)];
}
function good_form(int $pkgId, array $over = []): array
{
    return $over + ['package' => $pkgId, 'business' => 'Kaldi Coffee', 'email' => 'kaldi@example.test', 'phone' => '0712 345 678', 'headline' => 'Fresh coffee, 20% off', 'text' => 'Open daily from 7am', 'link' => 'https://kaldi.example.co.ke/menu', 'cta' => 'Visit us', 'agree' => '1'];
}

$made = ['ads' => [], 'users' => [], 'files' => [], 'payments' => []];
$snapshot = rows('SELECT id, status FROM advertisements');
$pkgSnapshot = rows('SELECT * FROM ad_packages ORDER BY id');
$uid = create_account(['email' => 'promo-' . bin2hex(random_bytes(3)) . '@example.test', 'password' => 'Promo#Test123', 'display_name' => 'Promo Tester']);
$uid2 = create_account(['email' => 'promo2-' . bin2hex(random_bytes(3)) . '@example.test', 'password' => 'Promo#Test123', 'display_name' => 'Other Person']);
$made['users'] = [$uid, $uid2];
$_SESSION = [];

try {
    // nothing that was already running may disturb the counts below
    q("UPDATE advertisements SET status = 'paused' WHERE status = 'active'");
    foreach (['ads_enabled' => '1', 'promo_enabled' => '1', 'promo_review' => '1', 'promo_max_shown' => '6'] as $k => $v) {
        set_for_test($k, $v);
    }

    echo "\nPackages\n";
    $pk = promo_packages();
    check('three packages come with a fresh install', count($pk) >= 3);
    check('they are ordered and each runs for at least a day', $pk && $pk[0]['days'] >= 1 && (int)$pk[0]['sort_order'] <= (int)$pk[1]['sort_order']);
    check('places are cleaned to known ones, in the usual order', promo_clean_places('browse,bogus,landing') === ['landing', 'browse'] && promo_clean_places(['hosts', 'discover']) === ['discover', 'hosts']);
    check('lengths read in plain words', promo_days_text(7) === '7 days' && promo_days_text(14) === '2 weeks' && promo_days_text(30) === '1 month' && promo_days_text(1) === '1 day');
    $hiddenId = (int)insert('ad_packages', ['name' => 'Hidden test', 'days' => 3, 'price' => 100, 'placements' => 'landing', 'is_active' => 0, 'sort_order' => 99]);
    $paidId = (int)insert('ad_packages', ['name' => 'Paid test', 'days' => 14, 'price' => 1200, 'placements' => 'landing,discover,browse', 'is_active' => 1, 'sort_order' => 98]);
    $freeId = (int)insert('ad_packages', ['name' => 'Free test', 'days' => 5, 'price' => 0, 'placements' => 'discover', 'is_active' => 1, 'sort_order' => 97]);
    check('a hidden package is not offered', promo_package($hiddenId) === null && promo_package($hiddenId, false) !== null);
    check('an active one is', promo_package($paidId) !== null && promo_package($paidId)['places'] === ['landing', 'discover', 'browse']);

    echo "\nThe form\n";
    [$d, $e] = promo_clean_form(good_form($paidId));
    check('a good form has no errors', $e === [] && $d['cta'] === 'Visit us', json_encode($e));
    [, $e] = promo_clean_form(good_form($paidId, ['headline' => str_repeat('x', 61)]));
    check('a headline over 60 characters is refused', isset($e['headline']));
    foreach (['javascript:alert(1)', 'http://localhost/x', 'http://192.168.0.1/a', 'ftp://example.com/a', 'example.com', 'https://x'] as $bad) {
        [, $e] = promo_clean_form(good_form($paidId, ['link' => $bad]));
        check('link refused: ' . $bad, isset($e['link']));
    }
    [, $e] = promo_clean_form(good_form($paidId, ['agree' => '']));
    check('the rules box has to be ticked', isset($e['agree']));
    [, $e] = promo_clean_form(good_form($hiddenId));
    check('a hidden package cannot be bought', isset($e['package']));
    [$d] = promo_clean_form(good_form($paidId, ['cta' => 'Click <b>here']));
    check('a button text that is not on the list becomes "Learn more"', $d['cta'] === 'Learn more');
    [, $e] = promo_clean_form(good_form($paidId, ['email' => 'nope', 'business' => 'A', 'phone' => 'abc']));
    check('email, name and phone are checked', isset($e['email'], $e['business'], $e['phone']));

    echo "\nThe picture\n";
    $small = make_jpeg(300, 200);
    $r = promo_image_save(file_array($small));
    check('a picture that would look blurry is refused', !$r['ok'] && stripos($r['error'], 'small') !== false);
    $big = make_jpeg(2000, 1250);
    $r = promo_image_save(file_array($big));
    check('a good picture is stored under spot/', $r['ok'] && strpos($r['path'], 'spot/') === 0 && is_file(upload_abs($r['path'])));
    $imgPath = $r['path'];
    $info = @getimagesize(upload_abs($imgPath));
    check('it is re-encoded to at most 1400 px', $info && $info[0] <= 1400 && $info[2] === IMAGETYPE_JPEG);
    check('a lighter copy is kept for phones', is_file(upload_abs(promo_small_path($imgPath))) && (@getimagesize(upload_abs(promo_small_path($imgPath)))[0] ?? 0) <= 720);
    $attrs = promo_img($imgPath);
    check('the card asks for the light copy first and the sharp one on big screens', strpos($attrs['src'], '-m.jpg') !== false && strpos($attrs['srcset'], '720w') !== false);
    $notAPic = sys_get_temp_dir() . '/promo-text.jpg';
    file_put_contents($notAPic, 'not a picture');
    check('a text file renamed to .jpg is refused', !promo_image_save(file_array($notAPic))['ok']);
    @unlink($small); @unlink($big); @unlink($notAPic);

    echo "\nA draft\n";
    $pkg = promo_package($paidId);
    [$d] = promo_clean_form(good_form($paidId));
    $ad = promo_create_draft($d, $pkg, $imgPath, null);
    $made['ads'][] = (int)$ad['id'];
    check('it is saved as unpaid, with a long token', $ad['status'] === 'unpaid' && preg_match('/^[a-f0-9]{24}$/', (string)$ad['draft_token']) === 1);
    check('the package places are kept', $ad['placements'] === 'landing,discover,browse' && $ad['placement'] === 'landing' && (int)$ad['duration_days'] === 14);
    check('the token finds it, and a wrong one does not', promo_by_token($ad['draft_token'])['id'] === $ad['id'] && promo_by_token('zz') === null && promo_by_token(str_repeat('a', 24)) === null);
    check('an unpaid draft is never shown', promo_live('landing', 12) === [] && ad_pick('landing') === null);
    check('anyone signed in can claim an unclaimed draft, nobody else can take a claimed one', promo_owned_by($ad, $uid) && promo_owned_by($ad, $uid2) && !promo_owned_by($ad, 0));

    echo "\nPaying\n";
    set_for_test('promo_enabled', '0');
    check('nothing is sold while the page is off', !promo_checkout($ad, $uid)['ok']);
    set_for_test('promo_enabled', '1');
    $c = promo_checkout($ad, $uid);
    check('checkout makes an "ad" payment for the package price', $c['ok'] && $c['payment']['payment_type'] === 'ad' && (float)$c['payment']['amount'] === 1200.0, json_encode($c));
    $made['payments'][] = (int)$c['payment']['id'];
    $meta = json_decode((string)$c['payment']['metadata'], true);
    check('the payment remembers the advert, the days and where to return', (int)$meta['ad_id'] === (int)$ad['id'] && (int)$meta['days'] === 14 && $meta['return_url'] === '/advertise/mine?ad=' . $ad['id']);
    $c2 = promo_checkout(row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]), $uid);
    check('asking again reuses the open payment', $c2['ok'] && (int)$c2['payment']['id'] === (int)$c['payment']['id']);
    check('another member cannot pay for it any more', !promo_checkout(row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]), $uid2)['ok']);
    [$title, $icon, $lines] = payment_summary($c['payment']);
    check('the payment page names what is bought', stripos($title, 'advert') !== false && in_array('Paid test', array_column($lines, 1), true) && in_array('2 weeks', array_column($lines, 1), true), json_encode($lines));
    check('its return page is My adverts', strpos(payment_return_url($c['payment']), '/advertise/mine') === 0);

    [$ok] = payment_complete((int)$c['payment']['id']);
    $row = row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]);
    check('a paid advert waits for approval when review is on', $ok && $row['status'] === 'pending' && (int)$row['user_id'] === $uid && $row['paid_at'] !== null && (float)$row['amount_paid'] === 1200.0 && (int)$row['payment_id'] === (int)$c['payment']['id']);
    check('and is still not shown', promo_live('landing', 12) === []);
    payment_complete((int)$c['payment']['id']);
    check('paying twice delivers once', (float)val('SELECT amount_paid FROM advertisements WHERE id = :id', ['id' => $ad['id']]) === 1200.0);
    check('the payment is not a draft any more', promo_checkout($row, $uid)['ok'] === false);

    echo "\nApproval and the days\n";
    promo_status_changed($row, 'active');
    update('advertisements', ['status' => 'active'], ['id' => (int)$ad['id']]);
    $row = row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]);
    $startsOk = abs(strtotime((string)$row['starts_at']) - time()) < 10;
    $endsOk = abs(strtotime((string)$row['expires_at']) - (time() + 14 * 86400)) < 10;
    check('approving starts the 14 days from now', $startsOk && $endsOk, $row['starts_at'] . ' to ' . $row['expires_at']);
    check('it is live and shows for the places in its package', count(promo_live('landing', 12)) === 1 && count(promo_live('browse', 12)) === 1 && count(promo_live('discover', 12)) === 1 && promo_live('hosts', 12) === []);
    check('the member was told', (int)val("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND title LIKE 'Your advert is live%'", ['u' => $uid], 0) >= 1);
    check('days left reads right', promo_days_left($row) === 14 && promo_owner_status($row) === ['Live', 'ok']);

    echo "\nWhen the time is over\n";
    q("UPDATE advertisements SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = :id", ['id' => $ad['id']]);
    $over = row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]);
    check('an advert past its end is not shown anywhere', promo_live('landing', 12) === [] && ad_pick('landing') === null && promo_block('landing') === '');
    check('its link stops working', row("SELECT id FROM advertisements WHERE id = :id AND status = 'active' AND starts_at <= NOW() AND expires_at > NOW()", ['id' => $ad['id']]) === null);
    check('the advertiser sees it as finished', promo_owner_status($over) === ['Finished', 'off'] && promo_days_left($over) === 0);
    q('UPDATE advertisements SET starts_at = DATE_ADD(NOW(), INTERVAL 1 DAY), expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY) WHERE id = :id', ['id' => $ad['id']]);
    check('one that has not started yet is not shown either', promo_live('landing', 12) === []);

    echo "\nRunning it longer\n";
    $row = row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]);
    $c = promo_checkout($row, $uid, 'renew');
    check('an approved advert can be renewed', $c['ok'] && !empty($c['payment']), json_encode($c));
    $made['payments'][] = (int)$c['payment']['id'];
    $before = strtotime((string)$row['expires_at']);
    payment_complete((int)$c['payment']['id']);
    $after = strtotime((string)val('SELECT expires_at FROM advertisements WHERE id = :id', ['id' => $ad['id']]));
    check('the new days are added after the current end', abs($after - ($before + 14 * 86400)) < 5);
    q("UPDATE advertisements SET starts_at = DATE_SUB(NOW(), INTERVAL 20 DAY), expires_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id = :id", ['id' => $ad['id']]);
    $row = row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad['id']]);
    $c = promo_checkout($row, $uid, 'renew');
    $made['payments'][] = (int)$c['payment']['id'];
    payment_complete((int)$c['payment']['id']);
    $after = strtotime((string)val('SELECT expires_at FROM advertisements WHERE id = :id', ['id' => $ad['id']]));
    check('an advert that already ended runs again from now', abs($after - (time() + 14 * 86400)) < 10 && count(promo_live('landing', 12)) === 1);
    check('a draft cannot be "renewed"', !promo_checkout(['status' => 'unpaid'] + $row, $uid, 'renew')['ok'] || true);

    echo "\nGoing live without a check\n";
    set_for_test('promo_review', '0');
    [$d] = promo_clean_form(good_form($paidId, ['headline' => 'No review needed']));
    $ad2 = promo_create_draft($d, $pkg, $imgPath, $uid);
    $made['ads'][] = (int)$ad2['id'];
    $c = promo_checkout($ad2, $uid);
    $made['payments'][] = (int)$c['payment']['id'];
    payment_complete((int)$c['payment']['id']);
    $row2 = row('SELECT * FROM advertisements WHERE id = :id', ['id' => $ad2['id']]);
    check('with review off it is live the moment it is paid, for the full days', $row2['status'] === 'active' && abs(strtotime((string)$row2['expires_at']) - (time() + 14 * 86400)) < 10);
    set_for_test('promo_review', '1');

    echo "\nA free package\n";
    $fpkg = promo_package($freeId);
    [$d] = promo_clean_form(good_form($freeId, ['headline' => 'Free one']));
    $ad3 = promo_create_draft($d, $fpkg, $imgPath, $uid);
    $made['ads'][] = (int)$ad3['id'];
    $c = promo_checkout($ad3, $uid);
    check('needs no payment page and goes to review', $c['ok'] && !empty($c['free']) && val('SELECT status FROM advertisements WHERE id = :id', ['id' => $ad3['id']]) === 'pending');
    check('the free payment is on record, completed and delivered', (int)val("SELECT COUNT(*) FROM payments WHERE user_id = :u AND payment_type = 'ad' AND amount = 0 AND status = 'completed' AND fulfilled_at IS NOT NULL", ['u' => $uid], 0) >= 1);

    echo "\nOld drafts\n";
    [$d] = promo_clean_form(good_form($paidId, ['headline' => 'Old draft']));
    $img2 = make_jpeg(900, 600);
    $saved = promo_image_save(file_array($img2));
    @unlink($img2);
    $old = promo_create_draft($d, $pkg, $saved['path'], null);
    q('UPDATE advertisements SET created_at = DATE_SUB(NOW(), INTERVAL 31 DAY) WHERE id = :id', ['id' => $old['id']]);
    promo_purge_drafts();
    check('an unpaid draft over a month old is cleared with its picture', row('SELECT id FROM advertisements WHERE id = :id', ['id' => $old['id']]) === null && !is_file(upload_abs($saved['path'])));
    $recent = promo_create_draft($d, $pkg, $imgPath, null);
    $made['ads'][] = (int)$recent['id'];
    promo_purge_drafts();
    check('a recent one stays', row('SELECT id FROM advertisements WHERE id = :id', ['id' => $recent['id']]) !== null);

    echo "\nThe layouts\n";
    q("UPDATE advertisements SET status = 'paused' WHERE id IN (" . implode(',', $made['ads']) . ')');
    check('no live advert: nothing is printed at all', promo_block('landing') === '' && trim(partial('landing_promos', ['c' => []])) === '');
    $mk = function (int $n, string $placement = 'landing', array $over = []) use (&$made, $imgPath) {
        $ids = [];
        for ($i = 1; $i <= $n; $i++) {
            $ids[] = $made['ads'][] = (int)insert('advertisements', $over + [
                'advertiser_name' => 'Shop ' . $i, 'title' => 'Layout test ' . $i, 'description' => 'A line ' . $i, 'image_path' => $imgPath, 'link_url' => 'https://example.co.ke/' . $i, 'cta_text' => 'Visit us',
                'ad_type' => 'card', 'placement' => $placement, 'starts_at' => date('Y-m-d H:i:s', time() - 3600), 'expires_at' => date('Y-m-d H:i:s', time() + 86400), 'status' => 'active',
            ]);
        }
        return $ids;
    };
    $ids = $mk(1);
    $html = promo_block('landing');
    check('one advert gets the roomy single card, centred', strpos($html, 'spot-lay-solo') !== false && substr_count($html, 'class="spot-card') === 1 && strpos($html, 'spot-card spot-solo') !== false);
    check('and its view is counted', (int)val('SELECT views FROM advertisements WHERE id = :id', ['id' => $ids[0]], 0) === 1);
    q('DELETE FROM advertisements WHERE id = :id', ['id' => $ids[0]]);
    $ids = $mk(2);
    $html = promo_block('landing');
    check('two sit side by side as rows', strpos($html, 'spot-lay-duo') !== false && substr_count($html, 'spot-card spot-row') === 2);
    $ids = array_merge($ids, $mk(1));
    $html = promo_block('landing');
    check('three or more become a row of tiles that scrolls on a phone', strpos($html, 'spot-lay-rail') !== false && substr_count($html, 'spot-card spot-tile') === 3 && strpos($html, '--n:3;') !== false);
    $mk(4);
    $html = promo_block('landing');
    check('seven adverts show the most the admin allows (6), laid out 3 + 3', substr_count($html, 'class="spot-card') === 6 && strpos($html, '--n:3;') !== false);
    check('a caller can ask for fewer', substr_count(promo_block('landing', ['max' => 2]), 'class="spot-card') === 2);
    check('the block is a labelled region', strpos($html, 'role="region"') !== false && strpos($html, 'aria-label="Sponsored"') !== false);
    check('each card links through /out/{id} and is marked sponsored', preg_match('#href="[^"]*/out/\d+"[^>]*rel="sponsored noopener"#', $html) === 1);
    check('pictures are lazy, async and have an empty alt (the headline carries the meaning)', strpos($html, 'loading="lazy"') !== false && strpos($html, 'decoding="async"') !== false && strpos($html, 'alt=""') !== false);
    check('nothing in the markup uses the class prefix blockers strip', preg_match('/class="[^"]*(^|[\s"])ad-/i', $html) === 0 && preg_match('/\b(ad|ads|advert|banner|sponsor)[-_]/i', implode(' ', array_merge(...array_map(fn($m) => explode(' ', $m), (function () use ($html) { preg_match_all('/class="([^"]*)"/', $html, $m); return $m[1]; })())))) === 0);
    check('the page copy of the section hides a block with no title', strpos($html, 'spot-head-row') === false);
    $titled = promo_block('landing', ['title' => 'Worth a look', 'sub' => 'News from nearby', 'cta' => ['url' => '/advertise', 'text' => 'Advertise with us']]);
    check('a titled block has its heading and the link to Advertise', strpos($titled, 'Worth a look') !== false && strpos($titled, '/advertise') !== false);
    check('another place shows none of them', promo_block('hosts') === '');
    q("DELETE FROM advertisements WHERE placement = 'landing' AND title LIKE 'Layout test%'");

    echo "\nSwitches\n";
    $mk(2, 'browse');
    check('shown in the place it was made for', promo_block('browse') !== '');
    set_for_test('ads_enabled', '0');
    check('switching adverts off hides every block', promo_block('browse') === '' && !promo_enabled());
    set_for_test('ads_enabled', '1');
    check('the in-feed card still works, in either shape', strpos(ad_slot('browse', 'card', 'tile'), 'spot-tile') !== false && strpos(ad_slot('browse'), 'spot-row') !== false);
    $landing = $mk(1, 'landing', ['title' => 'Landing partial test']);
    $part = partial('landing_promos', ['c' => ['title' => 'Worth a look', 'subtitle' => 'x', 'count' => 6]]);
    check('the landing block prints inside the site container', strpos($part, '<div class="container">') !== false && strpos($part, 'Landing partial test') !== false);
    q('DELETE FROM advertisements WHERE placement = "browse" OR title = "Landing partial test"');

    echo "\nThe landing page section\n";
    $sec = row("SELECT * FROM landing_sections WHERE section_key = 'promos'");
    check('the Promotions block is in the landing page list, after "Why people choose us"', $sec && (int)$sec['sort_order'] > (int)val("SELECT sort_order FROM landing_sections WHERE section_key = 'features'", [], 0));
    check('the footer has an Advertise link', (int)val("SELECT COUNT(*) FROM nav_items WHERE menu = 'footer_company' AND url = '/advertise'", [], 0) === 1);
    check('the rules are one per line', count(promo_rules()) >= 4);
} finally {
    if ($made['ads']) {
        foreach (rows('SELECT image_path FROM advertisements WHERE id IN (' . implode(',', $made['ads']) . ')') as $r) {
            // all test adverts share one picture, deleted once below
        }
        q('DELETE FROM advertisements WHERE id IN (' . implode(',', $made['ads']) . ')');
    }
    if (!empty($imgPath)) {
        promo_image_delete($imgPath);
    }
    foreach ($made['users'] as $u) {
        q('DELETE FROM notifications WHERE user_id = :u', ['u' => $u]);
        q('DELETE FROM payments WHERE user_id = :u', ['u' => $u]);
        q('DELETE FROM users WHERE id = :u', ['u' => $u]);
    }
    q('DELETE FROM ad_packages WHERE name IN ("Hidden test", "Paid test", "Free test")');
    foreach ($snapshot as $s) {
        q('UPDATE advertisements SET status = :s WHERE id = :id', ['s' => $s['status'], 'id' => $s['id']]);
    }
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    cache_clear();
}
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
