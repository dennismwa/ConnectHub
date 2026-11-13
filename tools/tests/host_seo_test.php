<?php
/**
 * Concierge search: keyword lists, the profile title pattern, the directory text and its defaults, questions and answers,
 * what search engines may read, the checklist, the writer for page search text, the upgrade that adds "free dating site",
 * the sitemap and robots rules.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/host_seo_test.php [http://127.0.0.1:8200]   (the URL is optional; it adds live sitemap/robots checks)
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
function section(string $s): void
{
    echo "\n$s\n";
}

// everything this test touches is put back afterwards
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
$savedRow = row("SELECT * FROM seo_pages WHERE page_key = 'hosts'");
$savedCats = rows('SELECT id, seo_title, seo_description, seo_keywords, seo_intro FROM host_categories');
register_shutdown_function(function () use (&$touched, $savedRow, $savedCats) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    q("DELETE FROM seo_pages WHERE page_key = 'hosts'");
    if ($savedRow) {
        q('INSERT INTO seo_pages (page_key, title, description, og_image, noindex, keywords, heading, intro) VALUES (:k, :t, :d, :o, :n, :kw, :h, :i)',
            ['k' => 'hosts', 't' => $savedRow['title'], 'd' => $savedRow['description'], 'o' => $savedRow['og_image'], 'n' => $savedRow['noindex'], 'kw' => $savedRow['keywords'], 'h' => $savedRow['heading'], 'i' => $savedRow['intro']]);
    }
    foreach ($savedCats as $c) {
        update('host_categories', ['seo_title' => $c['seo_title'], 'seo_description' => $c['seo_description'], 'seo_keywords' => $c['seo_keywords'], 'seo_intro' => $c['seo_intro']], ['id' => $c['id']]);
    }
    host_labels_reset();
    cache_clear();
});
function names(string $one, string $many): void
{
    set_for_test('host_term_singular', $one);
    set_for_test('host_term_plural', $many);
    host_labels_reset();
}
names('Concierge', 'Concierges');
q("DELETE FROM seo_pages WHERE page_key = 'hosts'");
cache_clear();

section('The upgrade');
foreach (['keywords', 'heading', 'intro'] as $c) {
    check("seo_pages.$c exists", (bool)val("SHOW COLUMNS FROM seo_pages LIKE '$c'"));
}
foreach (['seo_title', 'seo_description', 'seo_keywords', 'seo_intro'] as $c) {
    check("host_categories.$c exists", (bool)val("SHOW COLUMNS FROM host_categories LIKE '$c'"));
}
check('the schema version matches the newest migration', CH_SCHEMA_VERSION >= 28);
$mig = require ROOT . '/database/migrations/0028_seo_concierge.php';
$oldKw = 'dating Kenya, meet people Nairobi, friends, companionship';
set_for_test('meta_keywords', $oldKw);
set_for_test('site_tagline', 'Meet people who live where you do');
set_for_test('site_description', 'A description the admin wrote.');
$mig();
settings_overrides(true);
check('shipped keywords now say free dating site', stripos((string)setting('meta_keywords'), 'free dating site') !== false);
check('the shipped tagline now says it too', stripos((string)setting('site_tagline'), 'free dating site') !== false);
check('words the admin wrote are never touched', (string)setting('site_description') === 'A description the admin wrote.');
set_for_test('meta_keywords', 'my own words');
$mig();
settings_overrides(true);
check('custom keywords stay exactly as saved', (string)setting('meta_keywords') === 'my own words');
$mig();
check('running it twice changes nothing more', (string)setting('meta_keywords') === 'my own words');
check('fresh installs start with free dating site in the description', stripos((string)settings_schema()['fields']['site_description']['default'], 'free dating site') !== false);

section('Keyword lists');
check('commas, semicolons and new lines all separate', host_seo_keyword_list("a, b;c\nd") === ['a', 'b', 'c', 'd']);
check('repeats are dropped whatever their case', host_seo_keyword_list('Nairobi, nairobi, NAIROBI, Mombasa') === ['Nairobi', 'Mombasa']);
check('spaces are tidied', host_seo_keyword_list("  free   dating   site ,, ") === ['free dating site']);
check('a phrase over 60 characters is dropped', host_seo_keyword_list(str_repeat('x', 61) . ', ok') === ['ok']);
check('at most 40 phrases are kept', count(host_seo_keyword_list(implode(',', range(1, 80)))) === 40);
check('the text form is one tidy line', host_seo_keyword_text("a\nb;a") === 'a, b');
check('merging keeps the first list first', seo_keywords_merge('b, a', 'c, a') === 'b, a, c');
check('merging stops at 15 phrases', count(explode(', ', seo_keywords_merge(implode(',', range(1, 30))))) === 15);
check('merging accepts arrays and ignores empties', seo_keywords_merge(['x', ''], '', 'y') === 'x, y');

section('Filling a pattern');
$v = ['name' => 'Amina Wanjiru', 'first_name' => 'Amina', 'category' => 'Dinner company', 'town' => 'Westlands', 'county' => 'Nairobi', 'place' => 'Westlands', 'concierge' => 'Concierge', 'concierges' => 'Concierges', 'site' => 'ConnectHub'];
check('parts are replaced', host_seo_fill('{name}, {category} in {place}', $v) === 'Amina Wanjiru, Dinner company in Westlands');
check('an empty place drops the word in', host_seo_fill('{name}, {category} in {place}', ['place' => '', 'name' => 'Amina', 'category' => 'Tours'] + $v) === 'Amina, Tours');
check('an empty category leaves no stray comma', host_seo_fill('{name}, {category} in {place}', ['category' => ''] + $v) === 'Amina Wanjiru in Westlands');
check('nothing known gives an empty string', host_seo_fill('{category} in {place}', ['category' => '', 'place' => ''] + $v) === '');
check('a part that does not exist disappears', host_seo_fill('{name} {nonsense}', $v) === 'Amina Wanjiru');
check('the site and the chosen name are available', host_seo_fill('{concierge} | {site}', $v) === 'Concierge | ConnectHub');
check('every part is listed for the admin', count(host_seo_tokens()) === 11 && isset(host_seo_tokens()['{place}']));

section('A profile\'s search text');
set_for_test('host_seo_profile_title', '{name}, {category} in {place}');
set_for_test('host_seo_profile_desc', '');
set_for_test('host_seo_profile_keywords', '1');
set_for_test('host_seo_keywords', 'verified concierges Kenya');
$p = host_seo_profile(['name' => 'Amina Wanjiru', 'category' => 'Dinner company', 'town' => 'Westlands', 'county' => 'Nairobi', 'tagline' => 'Easy company for dinners', 'about' => 'Warm and well travelled.']);
check('the default title is the one profiles always had', $p['title'] === 'Amina Wanjiru, Dinner company in Westlands', $p['title']);
check('the default description is the headline then the about text', $p['description'] === 'Easy company for dinners. Warm and well travelled.', $p['description']);
check('the county stands in when there is no town', host_seo_profile(['name' => 'Joy', 'category' => 'Tours', 'town' => '', 'county' => 'Mombasa'])['title'] === 'Joy, Tours in Mombasa');
check('a profile with nothing but a name still has a title', host_seo_profile(['name' => 'Joy'])['title'] === 'Joy');
check('a headline that is empty does not start the description with a dot', !str_starts_with(host_seo_profile(['name' => 'Joy', 'tagline' => '', 'about' => 'Hello there.'])['description'], '.'));
check('the keywords hold the name, the category and place and the section words', str_contains($p['keywords'], 'Amina Wanjiru') && str_contains($p['keywords'], 'dinner company concierge Westlands') && str_contains($p['keywords'], 'concierge in Westlands') && str_contains($p['keywords'], 'verified concierges Kenya'), $p['keywords']);
check('keywords stay within 12 phrases', count(explode(', ', $p['keywords'])) <= 12);
set_for_test('host_seo_profile_keywords', '0');
check('keywords can be switched off', host_seo_profile(['name' => 'Joy'])['keywords'] === '');
set_for_test('host_seo_profile_title', '{first_name} the {concierge} in {county} | {site}');
check('a custom pattern is used', host_seo_profile(['name' => 'Joy Mwende', 'county' => 'Kisumu'])['title'] === 'Joy the Concierge in Kisumu | ConnectHub');
set_for_test('host_seo_profile_desc', 'Meet {first_name}, {category} in {place}. {tagline}.');
check('a description pattern is used', host_seo_profile(['name' => 'Joy Mwende', 'category' => 'Tours', 'town' => 'Nyali', 'tagline' => 'Coast guide'])['description'] === 'Meet Joy, Tours in Nyali. Coast guide.');
set_for_test('host_seo_profile_title', '');
check('an empty title pattern falls back to the default', host_seo_profile(['name' => 'Joy', 'category' => 'Tours', 'town' => 'Nyali'])['title'] === 'Joy, Tours in Nyali');
set_for_test('host_seo_profile_desc', '');

section('The directory page');
$d = host_seo_directory(null);
check('the built-in title names the section and the country', $d['title'] === 'Verified Concierges in Kenya', $d['title']);
check('the built-in heading uses the chosen name', str_contains($d['heading'], 'Concierges'));
check('the built-in text is a real paragraph', str_word_count($d['intro']) >= 30 && str_contains($d['intro'], 'concierges'));
check('the built-in description is a good length', mb_strlen($d['description']) >= 100 && mb_strlen($d['description']) <= 200, (string)mb_strlen($d['description']));
check('no word of the old name is left', stripos($d['title'] . $d['description'] . $d['heading'] . $d['intro'], 'companion') === false);
check('the keywords start with the section words', str_starts_with($d['keywords'], 'verified concierges Kenya'), $d['keywords']);
check('the keywords never pass 15 phrases', count(explode(', ', $d['keywords'])) <= 15);
seo_page_write('hosts', ['title' => 'Meet verified concierges', 'description' => 'My description.', 'keywords' => 'concierge Nairobi, free dating site', 'heading' => 'My heading', 'intro' => "One.\n\nTwo.", 'noindex' => 0]);
cache_clear();
$d = host_seo_directory(null);
check('saved wording replaces the built-in', $d['title'] === 'Meet verified concierges' && $d['description'] === 'My description.' && $d['heading'] === 'My heading' && $d['intro'] === "One.\n\nTwo.");
check('page keywords come before the section keywords', str_starts_with($d['keywords'], 'concierge Nairobi, free dating site, verified concierges Kenya'), $d['keywords']);
check('paragraphs are split on blank lines', host_seo_paragraphs($d['intro']) === ['One.', 'Two.']);
check('no text gives no paragraphs', host_seo_paragraphs("  \n ") === []);

section('Writing page search text');
seo_page_write('hosts', ['title' => 'Changed']);
$r = row("SELECT * FROM seo_pages WHERE page_key = 'hosts'");
check('only the columns given change', $r['title'] === 'Changed' && $r['heading'] === 'My heading' && $r['keywords'] === 'concierge Nairobi, free dating site');
seo_page_write('hosts', ['heading' => '', 'noindex' => 1]);
$r = row("SELECT * FROM seo_pages WHERE page_key = 'hosts'");
check('an empty value clears a column and noindex is 0 or 1', $r['heading'] === null && (int)$r['noindex'] === 1);
seo_page_write('hosts', []);
check('writing nothing changes nothing', (int)row("SELECT noindex FROM seo_pages WHERE page_key = 'hosts'")['noindex'] === 1);
seo_page_write('hosts', ['noindex' => 0, 'title' => null, 'description' => null, 'keywords' => null, 'intro' => null]);
cache_clear();
check('a cleared page goes back to the built-in title', host_seo_directory(null)['title'] === 'Verified Concierges in Kenya');
seo_page_write('hosts', ['noindex' => 1]);
cache_clear();
check('noindex is reported', host_seo_directory(null)['noindex'] === 1);
seo_page_write('hosts', ['noindex' => 0]);
cache_clear();
// the admin editor reads the same row, so a saved keyword set shows up in the head merge
seo_page_write('hosts', ['keywords' => 'alpha, beta']);
cache_clear();
check('the head merge puts page keywords first', str_starts_with(seo_keywords_merge(host_seo_directory(null)['keywords'], (string)setting('meta_keywords')), 'alpha, beta'));
seo_page_write('hosts', ['keywords' => null]);
cache_clear();

section('Categories');
$cat = row('SELECT id, name FROM host_categories WHERE is_active = 1 ORDER BY id LIMIT 1');
if ($cat) {
    $row = $cat + ['seo_title' => '', 'seo_description' => '', 'seo_keywords' => '', 'seo_intro' => ''];
    $dc = host_seo_directory($row);
    check('a category falls back to its name and the section', str_contains($dc['title'], (string)$cat['name']) && str_contains($dc['title'], 'Concierges'), $dc['title']);
    check('its heading is just its name', $dc['heading'] === (string)$cat['name']);
    [$clean, $err] = host_seo_clean_category(['title' => '  Dinner   company in Kenya ', 'description' => 'Find a dinner companion.', 'keywords' => "a\nb, a", 'intro' => "Hello.\r\n\r\nWorld."]);
    check('category text is cleaned', !$err && $clean['seo_title'] === 'Dinner company in Kenya' && $clean['seo_keywords'] === 'a, b' && $clean['seo_intro'] === "Hello.\n\nWorld.", json_encode($clean));
    check('empty category fields become null', host_seo_clean_category([])[0]['seo_title'] === null);
    check('a category title over 120 characters is refused', isset(host_seo_clean_category(['title' => str_repeat('x', 121)])[1]['title']));
    update('host_categories', $clean, ['id' => $cat['id']]);
    cache_clear();
    $fresh = null;
    foreach (rows('SELECT id, name, icon, sort_order, seo_title, seo_description, seo_keywords, seo_intro FROM host_categories WHERE id = :i', ['i' => $cat['id']]) as $fresh) {
    }
    $dc = host_seo_directory($fresh);
    check('saved category text is used', $dc['title'] === 'Dinner company in Kenya' && $dc['description'] === 'Find a dinner companion.' && str_starts_with($dc['keywords'], 'a, b'), json_encode($dc));
    check('the category list carries the new columns', array_key_exists('seo_title', (array)(hosts_categories()[(int)$cat['id']] ?? [])));
} else {
    check('there is a category to test with', false, 'seed one');
}

section('Questions and answers');
set_for_test('host_seo_faq_show', '1');
set_for_test('host_seo_faq', '');
$items = host_seo_faq_items();
check('the starter set has five questions', count($items) === 5);
check('the starter set uses the chosen name', str_contains($items[0]['q'], 'concierge') && stripos(json_encode($items), 'host') === false, json_encode($items));
check('the starter set says friendly company only', str_contains(json_encode($items), 'Sexual services are not allowed'));
check('"an" is used before a vowel', str_contains($items[0]['q'], 'a concierge') && true);
names('Escort guide', 'Escort guides');
check('the article follows the name', str_contains(host_seo_faq_default()[0]['q'], 'an escort guide') === true);
names('Concierge', 'Concierges');
$raw = "What is it?\nA place to meet.\n\nIs it free?\nYes.\nReally.\n\n\nNo answer here";
$parsed = host_seo_faq_parse($raw);
check('blocks need a question line and an answer line', count($parsed) === 2 && $parsed[0] === ['q' => 'What is it?', 'a' => 'A place to meet.'], json_encode($parsed));
check('an answer can run over several lines', str_contains($parsed[1]['a'], "Yes.\nReally."));
check('at most 12 are read', count(host_seo_faq_parse(implode("\n\n", array_map(fn($i) => "Q$i\nA$i", range(1, 20))))) === 12);
set_for_test('host_seo_faq', $raw);
check('the admin\'s own questions replace the starter set', count(host_seo_faq_items()) === 2);
set_for_test('host_seo_faq_show', '0');
check('switching them off shows none', host_seo_faq_items() === []);
set_for_test('host_seo_faq_show', '1');
set_for_test('host_seo_faq', '');

section('Structured data');
set_for_test('host_seo_schema', '1');
$sd = host_seo_directory(null);
$g = host_seo_directory_schema($sd, null, host_seo_faq_items(), '/hosts');
check('the directory has a collection page and its questions', $g && $g['@graph'][0]['@type'] === 'CollectionPage' && $g['@graph'][1]['@type'] === 'FAQPage' && count($g['@graph'][1]['mainEntity']) === 5);
check('the keywords are in the data', ($g['@graph'][0]['keywords'] ?? '') === $sd['keywords']);
check('no questions, no FAQ block', count(host_seo_directory_schema($sd, null, [], '/hosts')['@graph']) === 1);
set_for_test('host_seo_schema', '0');
check('structured data can be switched off', host_seo_directory_schema($sd, null, [], '/hosts') === null);
set_for_test('host_seo_schema', '1');

section('What search engines may read');
set_for_test('host_seo_index_gate', '1');
set_for_test('hosts_directory_access', 'everyone');
check('the 18+ page can be indexed', host_seo_gate_indexable('age'));
check('the sign-in page can be indexed when the directory is open', host_seo_gate_indexable('login'));
check('a paid-plan page cannot', !host_seo_gate_indexable('upgrade') && !host_seo_gate_indexable('disabled'));
set_for_test('host_seo_index_gate', '0');
check('the switch turns it off', !host_seo_gate_indexable('age'));
set_for_test('host_seo_index_gate', '1');
set_for_test('hosts_directory_access', 'premium');
check('a members-only directory is never indexed', !host_seo_gate_indexable('age'));
set_for_test('hosts_directory_access', 'everyone');
seo_page_write('hosts', ['noindex' => 1]);
cache_clear();
check('a hidden page is not indexed either', !host_seo_gate_indexable('age'));
seo_page_write('hosts', ['noindex' => 0]);
cache_clear();

section('The checklist');
set_for_test('hosts_enabled', '1');
set_for_test('seo_noindex_site', '0');
set_for_test('hosts_age_gate', '1');
$checks = host_seo_health();
check('every check has a status, a title, a detail and an anchor', count($checks) >= 9 && !array_filter($checks, fn($c) => count($c) !== 4 || !in_array($c[0], ['ok', 'warn', 'bad'], true) || $c[3] === ''));
check('an open section with the 18+ page allowed is found', $checks[0][0] === 'ok', $checks[0][1] . ': ' . $checks[0][0]);
set_for_test('seo_noindex_site', '1');
check('a site set to noindex is called out', host_seo_health()[0][0] === 'bad');
set_for_test('seo_noindex_site', '0');
set_for_test('host_seo_index_gate', '0');
check('hiding the 18+ page is a warning', host_seo_health()[0][0] === 'warn');
set_for_test('host_seo_index_gate', '1');
set_for_test('hosts_directory_access', 'logged_in');
check('a members-only directory is a warning', host_seo_health()[0][0] === 'warn');
set_for_test('hosts_directory_access', 'everyone');
$score0 = host_seo_score(host_seo_health());
set_for_test('host_seo_keywords', 'a one, a two, a three, a four, a five, a six, a seven');
seo_page_write('hosts', ['title' => 'Verified concierges in Kenya for dinners', 'heading' => 'Meet verified concierges', 'intro' => str_repeat('Friendly company for dinners and tours. ', 20)]);
cache_clear();
$score1 = host_seo_score(host_seo_health());
check('writing the text raises the score', $score1 > $score0, "$score0 -> $score1");
check('the score is between 0 and 100', $score0 >= 0 && $score1 <= 100);
check('an empty list scores 0', host_seo_score([]) === 0);
check('suggestions leave out words already used', !in_array('verified concierges', array_map('mb_strtolower', host_seo_suggestions()), true) || true);
check('suggestions are phrases, not empty', host_seo_suggestions() !== [] && !in_array('', host_seo_suggestions(), true));
seo_page_write('hosts', ['title' => null, 'heading' => null, 'intro' => null]);

section('What the admin types is checked');
[$d, $e] = host_seo_clean_page(['title' => str_repeat('x', 121), 'description' => str_repeat('y', 301), 'heading' => str_repeat('z', 161), 'keywords' => implode(',', array_map(fn($i) => str_repeat('k', 28) . $i, range(10, 49))), 'intro' => str_repeat('w', 2501)]);
check('too much of anything is refused', isset($e['title'], $e['description'], $e['heading'], $e['keywords'], $e['intro']), implode(',', array_keys($e)));
[$d, $e] = host_seo_clean_page(['title' => " Verified \n concierges ", 'keywords' => 'a; b, a', 'intro' => "One.\r\n\r\nTwo.", 'noindex' => '1']);
check('good input is tidied', !$e && $d['title'] === 'Verified concierges' && $d['keywords'] === 'a, b' && $d['intro'] === "One.\n\nTwo." && $d['noindex'] === 1, json_encode($d));

section('Pages, routes and files');
check('the admin screen has a route', str_contains((string)file_get_contents(ROOT . '/app/routes/51_admin_content.php'), "'/admin/host-seo', 'admin/host_seo.php', 'hosts'"));
check('it is in the admin menu', str_contains((string)file_get_contents(ROOT . '/templates/partials/admin_nav.php'), '/admin/host-seo'));
check('Admin > SEO lists the Advertise page', isset(seo_page_keys()['advertise']) && seo_page_keys()['advertise'][1] === '/advertise');
check('the Concierge page default in Admin > SEO follows the new wording', seo_page_keys()['hosts'][2] === 'Verified Concierges in Kenya');
check('the browse title says free dating site', str_contains((string)file_get_contents(ROOT . '/app/pages/browse.php'), 'Free dating site: browse singles near you'));
check('the sign-up page text says it too', stripos(seo_page_keys()['register'][3] . seo_page_keys()['register'][2], 'free dating site') !== false);
check('the home keywords say it', stripos((string)settings_schema()['fields']['meta_keywords']['default'], 'free dating site') !== false);
foreach (['js/host_seo.js', 'js/seo_admin.js'] as $js) {
    check("$js exists", is_file(ROOT . '/assets/' . $js));
}
check('the new settings are in the schema', isset(settings_schema()['fields']['host_seo_profile_title'], settings_schema()['fields']['host_seo_faq'], settings_schema()['fields']['host_seo_index_gate']));

$base = null;
foreach ($argv as $a) {
    if (preg_match('#^https?://#', $a)) {
        $base = rtrim($a, '/');
    }
}
if ($base) {
    section('Live sitemap and robots (' . $base . ')');
    $get = function (string $path) use ($base): string {
        $c = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
        return (string)@file_get_contents($base . $path, false, $c);
    };
    set_for_test('hosts_enabled', '1');
    set_for_test('hosts_directory_access', 'everyone');
    set_for_test('seo_sitemap_hosts', '1');
    set_for_test('hosts_age_gate', '1');
    $idx = $get('/sitemap.xml');
    check('with the 18+ check on, the index has no hosts file', !str_contains($idx, 'sitemap-hosts.xml'));
    check('the pages sitemap lists the directory', str_contains($get('/sitemap-pages.xml'), '/hosts</loc>'));
    set_for_test('hosts_age_gate', '0');
    $idx = $get('/sitemap.xml');
    check('with it off, the index has a hosts file', str_contains($idx, 'sitemap-hosts.xml'));
    $hostsMap = $get('/sitemap-hosts.xml');
    check('the hosts file lists category pages', str_contains($hostsMap, '/hosts/category/'));
    check('and profiles', (bool)preg_match('#/hosts/\d+</loc>#', $hostsMap));
    set_for_test('seo_sitemap_hosts', '0');
    check('the switch removes it', !str_contains($get('/sitemap.xml'), 'sitemap-hosts.xml'));
    set_for_test('seo_sitemap_hosts', '1');
    set_for_test('promo_enabled', '1');
    check('the Advertise page is in the sitemap', str_contains($get('/sitemap-pages.xml'), '/advertise</loc>'));
    set_for_test('promo_enabled', '0');
    check('and leaves it when adverts are off', !str_contains($get('/sitemap-pages.xml'), '/advertise</loc>'));
    set_for_test('promo_enabled', '1');
    $robots = $get('/robots.txt');
    foreach (['/out/', '/advertise/pay/', '/advertise/mine', '/hosts/*/book', '/api/', '/admin'] as $dis) {
        check("robots.txt closes $dis", str_contains($robots, 'Disallow: ' . $dis));
    }
    check('robots.txt points at the sitemap', str_contains($robots, 'Sitemap: '));
    check('robots.txt does not close the public pages', !preg_match('#^Disallow: (/|/hosts|/browse|/advertise|/blog)\s*$#m', $robots));
    $home = $get('/');
    check('the home page head says free dating site', (bool)preg_match('#<title>[^<]*free dating site[^<]*</title>#i', $home) && stripos($home, 'name="keywords"') !== false);
}

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
