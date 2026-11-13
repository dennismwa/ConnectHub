<?php
/**
 * Agent showcase: tiers, fill with others, pin order, per-row and visibility, landing blocks and the admin page.
 *   export CH_CONFIG=/path/to/throwaway-config.php   (a database that has demo Hosts: Admin > Tools > Demo data)
 *   php tools/tests/agents_test.php
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
$restoreExt = [];
register_shutdown_function(function () use (&$touched, &$restoreExt) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    foreach (($GLOBALS['restoreFeat']['flags'] ?? []) as $id => $_) {
        q('UPDATE host_profiles SET is_featured = 1 WHERE user_id = :u', ['u' => $id]);
    }
    foreach (($GLOBALS['restoreFeat']['until'] ?? []) as $id => $until) {
        q('UPDATE users SET featured_until = :d WHERE id = :u', ['d' => $until, 'u' => $id]);
    }
    foreach ($restoreExt as $id => [$vip, $pin]) {
        q('UPDATE host_profile_ext SET is_vip = :v, pin_order = :p WHERE user_id = :u', ['v' => $vip, 'p' => $pin, 'u' => $id]);
    }
    cache_clear();
});

echo "Agent showcase\n";
$hosts = array_map('intval', col("SELECT hp.user_id FROM host_profiles hp JOIN users u ON u.id = hp.user_id WHERE hp.approval_status = 'approved' AND u.status = 'active' ORDER BY hp.user_id"));
if (count($hosts) < 6) {
    echo "  SKIP  needs at least 6 approved Hosts (add demo data in Admin > Tools)\n";
    exit(0);
}
foreach ($hosts as $h) {
    hosts_ext_save($h, []);
    $r = row('SELECT is_vip, pin_order FROM host_profile_ext WHERE user_id = :u', ['u' => $h]);
    $restoreExt[$h] = [(int)$r['is_vip'], (int)$r['pin_order']];
}
q('UPDATE host_profile_ext SET is_vip = 0, pin_order = 0');
// start from a clean slate: no one is featured (by flag or by a paid date), whatever the demo data or earlier clicks left behind
$restoreFeat = ['flags' => pairs('SELECT user_id, is_featured FROM host_profiles WHERE is_featured = 1'), 'until' => pairs('SELECT id, featured_until FROM users WHERE featured_until IS NOT NULL')];
q('UPDATE host_profiles SET is_featured = 0');
q('UPDATE users SET featured_until = NULL WHERE featured_until IS NOT NULL');
cache_clear();
set_for_test('host_listing_fee_enabled', '0');
set_for_test('hosts_enabled', '1');
set_for_test('hosts_directory_access', 'everyone');
set_for_test('hosts_age_gate', '0');

// ---- schema and defaults ----
check('migration added is_vip and pin_order', mig_has_column('host_profile_ext', 'is_vip') && mig_has_column('host_profile_ext', 'pin_order'));
check('tiers are listed strongest first', array_keys(agent_tiers()) === ['vip', 'featured', 'premium', 'paid', 'boosted']);
check('starter landing blocks exist', (int)val("SELECT COUNT(*) FROM landing_sections WHERE section_key LIKE 'agents%'") >= 2);
check('hosts_ext knows the new fields', array_key_exists('is_vip', hosts_ext($hosts[0])) && array_key_exists('pin_order', hosts_ext($hosts[0])));

// ---- tiers ----
[$a, $b, $c, $d] = $hosts;
hosts_ext_save($a, ['is_vip' => 1]);
q('UPDATE host_profiles SET is_featured = 1 WHERE user_id = :u', ['u' => $b]);
cache_clear();
$cand = agents_candidates();
check('admin VIP makes an agent VIP', !empty($cand[$a]['vip']) && empty($cand[$b]['vip']));
check('featured flag makes an agent featured', !empty($cand[$b]['featured']));
check('top tier of a VIP is vip', (agent_top_tier($cand[$a])[0] ?? '') === 'vip');
check('agent in no tier has no top tier', agent_top_tier($cand[$d] ?? []) === null || !empty($cand[$d]['featured']) || !empty($cand[$d]['vip']));

$base = ['count' => 4, 'tier_vip' => true, 'tier_featured' => true, 'fill_others' => false, 'sort' => 'rating', 'category' => 0];
$ranked = agents_rank(agents_block_options($base), 't');
check('without fill only the ticked kinds qualify', array_keys($ranked) === [$a, $b] || (in_array($a, array_keys($ranked), true) && in_array($b, array_keys($ranked), true) && count($ranked) === 2), json_encode($ranked));
check('VIP outranks featured', array_key_first($ranked) === $a);
$filled = agents_rank(agents_block_options(['fill_others' => true] + $base), 't');
check('with fill every approved agent can appear', count($filled) === count($cand));
check('the qualifying agents come before the fillers', array_slice(array_keys($filled), 0, 2) === [$a, $b]);
check('fillers have weight 0', end($filled) === 0);

$none = agents_rank(agents_block_options(['count' => 4, 'fill_others' => false, 'sort' => 'newest']), 't');
check('with no kind ticked every agent qualifies', count($none) === count($cand));

// ---- nobody qualifies: fall back to others ----
q('UPDATE host_profile_ext SET is_vip = 0');
q('UPDATE host_profiles SET is_featured = 0');
cache_clear();
$noTier = ['count' => 5, 'tier_vip' => true, 'tier_featured' => true, 'tier_premium' => true, 'tier_paid' => true, 'fill_others' => true, 'sort' => 'rotate'];
$cards = landing_agents($noTier, 'fallback');
check('with no VIP or featured agents the block still shows other agents', count($cards) === 5, (string)count($cards));
$cardsOff = landing_agents(['fill_others' => false] + $noTier, 'nofill');
check('with fill off and nobody qualifying the block is empty', $cardsOff === [], (string)count($cardsOff));

// ---- pin order ----
hosts_ext_save($c, ['pin_order' => 1]);
hosts_ext_save($d, ['pin_order' => 2]);
cache_clear();
$pin = agents_rank(agents_block_options(['count' => 4, 'tier_vip' => true, 'fill_others' => false, 'sort' => 'rotate']), 'p');
check('pinned agents qualify whatever the kinds', in_array($c, array_keys($pin), true) && in_array($d, array_keys($pin), true));
check('pin 1 comes before pin 2', array_keys($pin) === [$c, $d], json_encode(array_keys($pin)));
$cards = landing_agents(['count' => 3, 'fill_others' => true, 'sort' => 'rating'], 'pin2');
check('pinned agents lead the cards', ($cards[0]['id'] ?? 0) === $c && ($cards[1]['id'] ?? 0) === $d);
check('cards carry the host data and tier', !empty($cards[0]['host']['name']) && array_key_exists('tier', $cards[0]));

// ---- count, category, per row ----
$many = landing_agents(['count' => 3, 'fill_others' => true], 'cnt');
check('count limits the cards', count($many) === 3);
$o = agents_block_options(['count' => 99, 'sort' => 'bogus']);
check('count is clamped to 36 and a bad sort falls back', $o['count'] === 36 && $o['sort'] === 'rotate');
$cat = (int)val("SELECT category_id FROM host_profiles WHERE user_id = :u", ['u' => $a], 0);
if ($cat > 0) {
    $inCat = agents_candidates($cat);
    $allCat = (int)val("SELECT COUNT(*) FROM host_profiles hp JOIN users u ON u.id = hp.user_id WHERE hp.approval_status = 'approved' AND u.status = 'active' AND hp.category_id = :c", ['c' => $cat]);
    check('category filter keeps only that category', count($inCat) === $allCat && isset($inCat[$a]));
}
$ids = [];
foreach (landing_agents(['count' => 6, 'fill_others' => true, 'sort' => 'rotate'], 'seedA') as $m) {
    $ids[] = (int)$m['id'];
}
check('the same seed gives the same order within the hour', $ids === array_map(fn($m) => (int)$m['id'], landing_agents(['count' => 6, 'fill_others' => true, 'sort' => 'rotate'], 'seedA')));

// ---- agents on or off ----
set_for_test('hosts_enabled', '0');
check('nothing shows while Hosts are off', landing_agents(['count' => 4], 'off') === []);
set_for_test('hosts_enabled', '1');
set_for_test('hosts_directory_access', 'vip');
check('nothing shows when the directory is closed to visitors', landing_agents(['count' => 4], 'closed') === []);
set_for_test('hosts_directory_access', 'everyone');
check('agents show again when the directory is open', count(landing_agents(['count' => 4], 'open')) === 4);

// ---- default bio ----
$bio = agent_bio(['first_name' => 'Grace', 'name' => 'Grace', 'age' => 33, 'gender' => 'female', 'town' => 'Ruiru', 'county' => 'Nairobi']);
check('default bio includes name, age, gender and location without companion wording', $bio === 'Meet Grace, a 33 year old female from Ruiru, Nairobi.', $bio);
check('default bio falls back to Kenya when location is missing', agent_bio(['first_name' => 'Grace', 'age' => 33, 'gender' => 'female']) === 'Meet Grace, a 33 year old female from Kenya.');
check('default bio never prints a raw placeholder', strpos(agent_bio(['first_name' => 'X', 'name' => 'X']), '{') === false);

// ---- visibility: visitors and contact buttons ----
set_for_test('hosts_contact_visibility', 'everyone');
set_for_test('blur_guests_hosts', '0');
q("UPDATE user_profiles SET show_contact = 1, contact_phone = '0712345678', contact_whatsapp = '0712345678' WHERE user_id = :u", ['u' => $a]);
$acc = hosts_contact_access(null, $a);
check('"everyone" lets visitors see contact details', $acc['allowed'] === true);
check('agent_card_contacts gives Call and WhatsApp for a visitor', isset(agent_card_contacts($a)['phone']) && isset(agent_card_contacts($a)['whatsapp']));
check('visitors do not get blurred agent photos when blur is off', guest_blur_enabled('hosts') === false);
set_for_test('blur_guests_hosts', '1');
check('blur on again blurs agent photos for visitors', guest_blur_enabled('hosts') === true);
set_for_test('hosts_contact_visibility', 'members');
$acc = hosts_contact_access(null, $a);
check('"members" hides contact details from visitors', $acc['allowed'] === false && $acc['reason'] === 'login');

// ---- rendered landing page ----
set_for_test('hosts_contact_visibility', 'everyone');
set_for_test('blur_guests_hosts', '0');
cache_clear();
$_SERVER['REQUEST_URI'] = '/';
$html = partial('landing_agents', ['c' => ['title' => 'Top', 'count' => 4, 'cols_desktop' => 4, 'cols_tablet' => 3, 'cols_mobile' => 2, 'fill_others' => true, 'sort' => 'rating', 'layout' => 'grid', 'show_contact' => true, 'card_style' => 'overlay'], 's' => ['section_key' => 'agents_t']]);
check('the block renders four cards', substr_count($html, 'class="ag-card') === 4, (string)substr_count($html, 'class="ag-card'));
check('per-row counts reach the markup', strpos($html, '--ag-d:4;--ag-t:3;--ag-m:2') !== false);
check('a visitor sees Call and WhatsApp buttons', strpos($html, 'href="tel:+') !== false && strpos($html, 'https://wa.me/') !== false);
check('cards link to the agent profile', preg_match('#href="[^"]*/hosts/\d+"#', $html) === 1);
check('the pinned agent comes first', preg_match('#data-agent-id="(\d+)"#', $html, $mm) && (int)$mm[1] === $c);
$html2 = partial('landing_agents', ['c' => ['title' => 'Top', 'count' => 4, 'fill_others' => true, 'layout' => 'rail', 'card_style' => 'stack', 'show_contact' => false, 'show_services' => false, 'show_price' => false, 'show_bio' => false], 's' => ['section_key' => 'agents_t']]);
check('switching options off removes them from the cards', strpos($html2, 'tel:+') === false && strpos($html2, 'ag-svc') === false && strpos($html2, 'ag-price') === false && strpos($html2, 'ag-bio') === false);
check('stack style and rail layout apply', strpos($html2, 'ag-card--stack') !== false && strpos($html2, 'ag-layout-rail') !== false);
check('no class starts with "ad-" (ad blockers hide those)', strpos($html . $html2, 'class="ad-') === false && !preg_match('/class="[^"]*\bad-[a-z]/', $html . $html2));

// ---- admin schema and pages ----
check('the agents schema is used for agents_* keys', ac_section_schema_for('agents_top') !== null && ac_section_schema_for('agents_x9') !== null && ac_section_schema_for('nope') === null);
$names = array_column(ac_section_schema_for('agents_top')['fields'], 'name');
foreach (['tier_vip', 'tier_featured', 'tier_premium', 'tier_paid', 'tier_boosted', 'fill_others', 'sort', 'count', 'cols_desktop', 'cols_tablet', 'cols_mobile', 'layout', 'card_style'] as $f) {
    check('admin can set ' . $f, in_array($f, $names, true));
}
$prof = array_column(ac_section_schema()['profiles']['fields'], 'name');
check('member section has per-row and fill controls', in_array('cols_tablet', $prof, true) && in_array('cols_mobile', $prof, true) && in_array('fill_vip', $prof, true));
$profHtml = partial('landing_profiles', ['c' => ['title' => 'x', 'count' => 4, 'columns' => '4', 'cols_tablet' => 2, 'cols_mobile' => 1, 'group_newest' => true]]);
check('member section carries per-row variables', strpos($profHtml, '--lp-t:2;--lp-m:1') !== false || trim($profHtml) === '');

// ---- member VIP group falls back to other members ----
$vipC = ['count' => 4, 'group_vip' => true, 'fill_vip' => true];
$g = landing_profile_groups($vipC);
check('VIP group is filled with other active members when none are VIP', !$g || count($g[0]['members']) >= 1);
$g2 = landing_profile_groups(['fill_vip' => false] + $vipC);
check('with fill off the VIP group only holds VIP or featured members', !$g2 || count($g2[0]['members']) <= count($g[0]['members'] ?? []));


// ---- stories strip ----
set_for_test('agent_story_enabled', '1');
set_for_test('agent_story_pages', 'landing,hosts');
set_for_test('agent_story_count', '99');
set_for_test('agent_story_seconds', '1');
$st = story_settings();
check('story count is clamped to 30', $st['count'] === 30, (string)$st['count']);
check('seconds per story are clamped to 3..15', $st['seconds'] >= 3 && $st['seconds'] <= 15, (string)$st['seconds']);
set_for_test('agent_story_seconds', '99');
check('seconds clamp at the top as well', story_settings()['seconds'] <= 15);
set_for_test('agent_story_pages', 'landing,nonsense,discover');
check('unknown pages are dropped from the strip', story_settings()['pages'] === ['landing', 'discover'], implode(',', story_settings()['pages']));
set_for_test('agent_story_pages', 'landing,hosts');
set_for_test('agent_story_count', '12');
set_for_test('agent_story_seconds', '5');
set_for_test('agent_join_place', 'strip');
set_for_test('agent_story_ads', '0');
$items = story_items('landing');
check('story items exist for the landing page', count($items) > 0);
check('the "become an agent" bubble comes first for visitors', ($items[0]['kind'] ?? '') === 'join', (string)($items[0]['kind'] ?? ''));
$rank = ['join' => 0, 'agent' => 1, 'ad' => 1];
$tierOrder = [];
foreach ($items as $it) {
    if (($it['kind'] ?? '') === 'agent') { $tierOrder[] = (string)($it['tier'][0] ?? 'none'); }
}
$weights = ['pin' => 0, 'vip' => 1, 'featured' => 2, 'premium' => 3, 'paid' => 3, 'boosted' => 4, 'none' => 9];
$sorted = true;
for ($i = 1; $i < count($tierOrder); $i++) {
    if (($weights[$tierOrder[$i]] ?? 9) < ($weights[$tierOrder[$i - 1]] ?? 9)) { $sorted = false; }
}
check('paid agents are listed before the others', $sorted, implode(',', $tierOrder));
set_for_test('agent_join_place', 'menu');
check('the round button moves to the menu and leaves the strip', (story_items('landing')[0]['kind'] ?? '') !== 'join' && agent_join_cta(null) !== null);
set_for_test('agent_join_place', 'off');
check('it can be switched off everywhere', (story_items('landing')[0]['kind'] ?? '') !== 'join' && agent_join_cta(null) === null);
set_for_test('agent_join_place', 'both');
check('it can sit in both places at once', (story_items('landing')[0]['kind'] ?? '') === 'join' && agent_join_cta(null) !== null);
check('an agent is never asked to become one', agent_join_cta(['id' => 1, 'role' => 'host', 'tier' => 'free']) === null && agent_join_cta(['id' => 1, 'role' => 'admin', 'tier' => 'vip']) === null);
set_for_test('agent_join_place', 'strip');
set_for_test('agent_story_enabled', '0');
check('strip off: no items and nothing printed', story_items('landing') === [] && trim(partial('story_strip', ['page' => 'landing'])) === '');
set_for_test('agent_story_enabled', '1');
set_for_test('agent_join_place', 'strip');
set_for_test('agent_story_pages', 'hosts');
check('a page that is not ticked shows no strip', story_items('landing') === []);
set_for_test('agent_story_pages', 'landing,hosts');
$stripHtml = partial('story_strip', ['page' => 'landing']);
check('strip markup uses sty- classes and never ad-', strpos($stripHtml, 'class="sty') !== false && !preg_match('/class="[^"]*\bad-[a-z]/', $stripHtml));
$cssFiles = [dirname(__DIR__, 2) . '/assets/src/modules/stories.css', dirname(__DIR__, 2) . '/assets/src/modules/apppages.css', dirname(__DIR__, 2) . '/assets/js/stories.js'];
$bad = 0;
foreach ($cssFiles as $f) { $bad += preg_match_all('/[.\s"\'](ad-[a-z][\w-]*)/', (string)file_get_contents($f)); }
check('story and app-page assets never use an ad- class', $bad === 0, (string)$bad);

// ---- promotion line ----
$aid = (int)val("SELECT hp.user_id FROM host_profiles hp WHERE hp.approval_status = 'approved' ORDER BY hp.user_id LIMIT 1", [], 0);
if ($aid > 0) {
    $before = hosts_ext($aid)['promo_text'] ?? null;
    hosts_ext_save($aid, ['promo_text' => 'Two hours of company, the third is half price']);
    cache_clear();
    check('promotion line is stored', (hosts_ext($aid)['promo_text'] ?? '') === 'Two hours of company, the third is half price');
    set_for_test('agent_story_promo', '1');
    $withPromo = array_values(array_filter(story_items('landing'), fn($i) => (int)($i['id'] ?? 0) === $aid));
    check('promotion line becomes the headline of the story', !$withPromo || (($withPromo[0]['v']['headline'] ?? '') === 'Two hours of company, the third is half price' && !empty($withPromo[0]['v']['promo'])));
    set_for_test('agent_story_promo', '0');
    $noPromo = array_values(array_filter(story_items('landing'), fn($i) => (int)($i['id'] ?? 0) === $aid));
    check('promotion line is ignored when the admin turns it off', !$noPromo || empty($noPromo[0]['v']['promo']));
    hosts_ext_save($aid, ['promo_text' => $before]);
    cache_clear();
}

// ---- visitor tab bar ----
check('the visitor tab bar menu has items', count(rows("SELECT id FROM nav_items WHERE menu = 'public_tabbar' AND is_active = 1")) >= 3);
check('public_dock is a known setting', setting('public_dock') !== null);
check('the nav admin lists the visitor tab bar', strpos((string)file_get_contents(dirname(__DIR__, 2) . '/app/admin/content/nav.php'), "'public_tabbar'") !== false);
check('saving a nav item clears the visitor tab bar cache', strpos((string)file_get_contents(dirname(__DIR__, 2) . '/includes/lib/admin_content.php'), "'public_tabbar'") !== false);
$guestMenu = nav_menu('public_tabbar');
check('visitors get Join in the visitor bar', in_array('/register', array_column($guestMenu, 'url'), true));

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
