<?php
/**
 * Renaming "Host": text swap, HTML swap (text and chosen attributes only), JSON swap, web addresses in and out, e-mail and Discover row.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/host_labels_test.php
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
    host_labels_reset();
}
register_shutdown_function(function () use (&$touched) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
});

// --- defaults change nothing
check('default name is Host', host_term() === 'Host' && host_term(true) === 'Hosts');
check('default text is untouched', host_labels_swap('Become a Host. Hosts near you') === 'Become a Host. Hosts near you');
check('default urls are untouched', host_path_out('/hosts/5') === '/hosts/5' && host_path_in('/hosts/5') === '/hosts/5');

// --- rename
set_for_test('host_term_singular', 'Companion');
set_for_test('host_term_plural', 'Companions');
check('plural and singular swap', host_labels_swap('Hosts near you. Become a Host today.') === 'Companions near you. Become a Companion today.');
check('lower case is kept', host_labels_swap('ask your host or other hosts') === 'ask your companion or other companions');
check('upper case is kept', host_labels_swap('HOSTS WANTED') === 'COMPANIONS WANTED');
check('possessive works', host_labels_swap("The Host's profile") === "The Companion's profile");
check('words that only contain host are left alone', host_labels_swap('ghost hostname localhost hosting ghosted') === 'ghost hostname localhost hosting ghosted');
check('co-host style words are left alone', host_labels_swap('a co-host and host-led walks') === 'a co-host and host-led walks');
check('computer hosts are left alone', host_labels_swap('SMTP host and database host, web host') === 'SMTP host and database host, web host');
check('identifiers with underscores are left alone', host_labels_swap('host_booking and hosts_enabled') === 'host_booking and hosts_enabled');
check('literal marker protects a word', host_labels_swap(host_literal('Host') . ' stays') === host_literal('Host') . ' stays');

set_for_test('host_term_singular', 'Expert');
set_for_test('host_term_plural', 'Experts');
check('"a" becomes "an" before a vowel', host_labels_swap('Become a Host. A host is verified.') === 'Become an Expert. An expert is verified.', host_labels_swap('Become a Host. A host is verified.'));
set_for_test('host_term_singular', 'Companion');
set_for_test('host_term_plural', 'Companions');
check('"an" becomes "a" before a consonant', host_labels_swap('an Host') === 'a Companion', host_labels_swap('an Host'));

$html = '<a href="/hosts/5" class="host-card" data-role="host" title="Open Host profile">Meet our Hosts</a><input type="radio" name="role" value="host"><script>var h="Hosts";</script><!-- Host --><textarea>Hosts rule</textarea>';
$out = host_labels_swap_html($html);
check('html text and title are swapped', str_contains($out, '>Meet our Companions</a>') && str_contains($out, 'title="Open Companion profile"'));
check('html classes, data values and radio values are not', str_contains($out, 'class="host-card"') && str_contains($out, 'data-role="host"') && str_contains($out, 'value="host"'));
check('scripts and comments are not touched', str_contains($out, 'var h="Hosts"') && str_contains($out, '<!-- Host -->'));

$j = host_labels_swap_json(['ok' => true, 'message' => 'Host approved.', 'role' => 'host', 'html' => '<b>Hosts</b>', 'label' => 'Host', 'n' => 3, 'type' => 'host']);
check('json messages are swapped', $j['message'] === 'Companion approved.' && $j['html'] === '<b>Companions</b>');
check('json roles and single tokens are not', $j['role'] === 'host' && $j['type'] === 'host' && $j['label'] === 'Host' && $j['n'] === 3);

// --- web addresses
set_for_test('host_url_slug', 'companions');
set_for_test('host_area_slug', 'companion');
check('url out: directory', host_path_out('/hosts') === '/companions' && host_path_out('/hosts/12') === '/companions/12' && host_path_out('/hosts/12/book?x=1') === '/companions/12/book?x=1');
check('url out: member area', host_path_out('/host/dashboard') === '/companion/dashboard');
check('url out: unrelated paths are untouched', host_path_out('/admin/hosts') === '/admin/hosts' && host_path_out('/api/hosts/5/days') === '/api/hosts/5/days' && host_path_out('/hostel') === '/hostel' && host_path_out('/') === '/');
check('url in: directory', host_path_in('/companions') === '/hosts' && host_path_in('/companions/12') === '/hosts/12' && host_path_in('/companions/category/x') === '/hosts/category/x');
check('url in: member area', host_path_in('/companion/earnings') === '/host/earnings');
check('url in: lookalikes are untouched', host_path_in('/companionship') === '/companionship' && host_path_in('/blog') === '/blog');
check('url() and abs_url() use the new address', url('/hosts/9') === base_path() . '/companions/9' && str_ends_with(abs_url('/host/setup'), '/companion/setup'));
$a = host_labels_swap_html('<a href="' . base_path() . '/hosts/3">x</a><form action="/host/settings"></form>');
check('hard-coded links are rewritten too', str_contains($a, '/companions/3') && str_contains($a, 'action="/companion/settings"'));

// --- slug rules
check('reserved words are refused', host_slug_error('admin', 'x') !== null && host_slug_error('blog', 'x') !== null);
check('same word twice is refused', host_slug_error('guides', 'guides') !== null);
check('a good word passes', host_slug_error('guides', 'guide') === null);
check('bad characters are refused', host_slug_error('Bad Slug!', 'x') !== null && host_slug_error('', 'x') !== null);
check('slugify', host_slugify('Local guides!') === 'local-guides');
check('pluralise', host_pluralise('Guide') === 'Guides' && host_pluralise('Buddy') === 'Buddies' && host_pluralise('Coach') === 'Coaches');

// --- e-mail
$sent = null;
set_for_test('mail_driver', 'log');
$ok = function_exists('send_mail');
check('send_mail exists', $ok);

// --- Discover row
set_for_test('host_discover_enabled', '1');
set_for_test('host_discover_title', '');
check('discover title defaults to the plural', host_discover_title() === 'Companions near you');
set_for_test('host_discover_title', 'Meet a local');
check('discover title can be set', host_discover_title() === 'Meet a local');
set_for_test('host_discover_enabled', '0');
check('discover row is empty when off', host_discover_rows() === []);

// --- the chosen name reaches search results
set_for_test('host_term_singular', 'Concierge');
set_for_test('host_term_plural', 'Concierges');
host_labels_reset();
$keys = seo_page_keys();
check('the directory entry in Admin > SEO uses the new name', strpos($keys['hosts'][0], 'Concierges') !== false);
check('its default search description uses the new name', strpos($keys['hosts'][3], 'concierges') !== false && stripos($keys['hosts'][3], 'companion') === false);
$graph = seo_schema_relabel(['@context' => 'https://schema.org', '@graph' => [
    ['@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'name' => 'Hosts', 'item' => 'https://example.com/hosts']]],
    ['@type' => 'WebPage', 'name' => 'Hosts', 'description' => 'Book a Host near you.', 'url' => 'https://example.com/hosts', 'sameAs' => ['https://x.com/hosts']],
]]);
check('words inside search data are renamed', $graph['@graph'][1]['name'] === 'Concierges' && $graph['@graph'][1]['description'] === 'Book a Concierge near you.');
check('a one-word name is renamed too', $graph['@graph'][0]['itemListElement'][0]['name'] === 'Concierges');
check('web addresses inside search data are left alone', $graph['@graph'][1]['url'] === 'https://example.com/hosts' && $graph['@graph'][0]['itemListElement'][0]['item'] === 'https://example.com/hosts' && $graph['@graph'][1]['sameAs'][0] === 'https://x.com/hosts');
check('the kind of thing is left alone', $graph['@graph'][1]['@type'] === 'WebPage' && $graph['@context'] === 'https://schema.org');
set_for_test('host_term_singular', 'Host');
set_for_test('host_term_plural', 'Hosts');
host_labels_reset();
check('nothing changes while the default name is in use', seo_schema_relabel(['name' => 'Hosts near you'])['name'] === 'Hosts near you');

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
