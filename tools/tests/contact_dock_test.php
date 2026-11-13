<?php
/** The contact buttons on profiles, the brand logos and the four added service options. Run: CH_CONFIG=... php tools/tests/contact_dock_test.php (throw-away database). */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$pass = 0; $fail = 0;
function check(string $n, bool $ok): void { global $pass, $fail; if ($ok) { $pass++; echo "  ok    $n\n"; } else { $fail++; echo "  FAIL  $n\n"; } }

// logos
$wa = brand_icon('whatsapp'); $ig = brand_icon('instagram'); $ph = brand_icon('phone');
check('WhatsApp logo is an inline svg with its own drawing', strpos($wa, '<svg') === 0 && strpos($wa, '<path') !== false && strpos($wa, '<use') === false);
check('Instagram logo is an inline svg', strpos($ig, '<svg') === 0 && strpos($ig, '<rect') !== false);
check('logos are hidden from screen readers (the button carries the name)', strpos($wa, 'aria-hidden="true"') !== false && strpos($ig, 'aria-hidden="true"') !== false);
check('other names fall back to the icon sprite', strpos($ph, '<use') !== false);

// phone formatting
check('local number is spaced 0712 345 678', hosts_phone_local('0712345678') === '0712 345 678');
check('international Kenyan number is shown the local way', hosts_phone_local('254712345678') === '0712 345 678');
check('an unknown shape is left as typed', hosts_phone_local('+44 20 7946 0958') === '+44 20 7946 0958');

// the dock
$items = [
    ['key' => 'whatsapp', 'label' => 'WhatsApp', 'hero' => 'Chat on WhatsApp', 'icon' => 'message-circle', 'href' => 'https://wa.me/254712345678', 'external' => '1', 'hint' => '0712 345 678'],
    ['key' => 'phone', 'label' => 'Call', 'hero' => 'Call now', 'icon' => 'phone', 'href' => 'tel:+254712345678', 'external' => '', 'hint' => '0712 345 678'],
    ['key' => 'instagram', 'label' => 'Instagram', 'hero' => 'See on Instagram', 'icon' => 'instagram', 'href' => 'https://instagram.com/x', 'external' => '1', 'hint' => '@x'],
];
$msg = ['key' => 'message', 'label' => 'Message', 'hero' => 'Message on Site', 'icon' => 'message-circle', 'href' => '/messages/with/5', 'external' => '', 'hint' => 'Chat here'];
$html = partial('host_contact_dock', ['items' => $items, 'message' => $msg, 'first' => 'Amina']);
check('first channel becomes the big button', substr_count($html, 'class="hgt-hero hgt-') === 1 && strpos($html, 'hgt-hero hgt-whatsapp') !== false);
check('the rest sit in a grid of tiles', substr_count($html, 'class="hgt-tile hgt-') === 3 && strpos($html, 'data-n="3"') !== false);
check('WhatsApp carries its logo, call keeps the phone icon', strpos($html, 'fill="currentColor" d="M17.47') !== false && strpos($html, '#i-phone') !== false);
check('outside links open safely in a new tab', preg_match('#href="https://wa.me/254712345678" target="_blank" rel="noopener"#', $html) === 1);
check('call link is not opened in a new tab', strpos($html, 'href="tel:+254712345678" target') === false);
$one = partial('host_contact_dock', ['items' => [], 'message' => $msg, 'first' => 'A']);
check('with only the site chat, that is the big button and there is no grid', strpos($one, 'hgt-hero hgt-message') !== false && strpos($one, 'hgt-grid') === false);
check('with nothing to show, nothing is drawn', trim(partial('host_contact_dock', ['items' => [], 'message' => null, 'first' => 'A'])) === '');
$evil = partial('host_contact_dock', ['items' => [['key' => 'whatsapp', 'label' => '<b>x</b>', 'icon' => 'x', 'href' => 'https://wa.me/1', 'external' => '1', 'hint' => '"><script>alert(1)</script>']], 'message' => null, 'first' => 'A']);
check('text is escaped', strpos($evil, '<script>alert') === false && strpos($evil, '&lt;script&gt;') !== false);

// service options
$names = ['Dinner Dates', 'Travel Companion', 'Massage', 'Meet at my place'];
foreach ($names as $n) {
    check("service '$n' exists and is active", (int)val('SELECT is_active FROM host_services WHERE name = :n', ['n' => $n]) === 1);
}
$all = array_column(rows('SELECT name FROM host_services'), 'name');
$banned = '/\b(oral|anal|bdsm|lesbian|threesome|incall|outcall|escort|sex)/i';
check('no explicit sexual service is offered in the catalogue', !array_filter($all, fn($x) => preg_match($banned, $x)));
$desc = implode(' ', array_column(rows('SELECT description FROM host_services'), 'description'));
check('no service description is sexual', !preg_match($banned, $desc));
$count = (int)val('SELECT COUNT(*) FROM host_services');
$mig = require dirname(__DIR__, 2) . '/database/migrations/0029_service_options.php';
$mig(); $mig();
check('running the migration again adds nothing', (int)val('SELECT COUNT(*) FROM host_services') === $count);
$id = (int)val("SELECT id FROM host_services WHERE name = 'Massage'");
q("UPDATE host_services SET description = 'Edited by the admin', is_active = 0 WHERE id = :i", ['i' => $id]);
$mig();
check('a renamed or hidden service is not put back or overwritten', (string)val('SELECT description FROM host_services WHERE id = :i', ['i' => $id]) === 'Edited by the admin' && (int)val('SELECT is_active FROM host_services WHERE id = :i', ['i' => $id]) === 0);
q("UPDATE host_services SET description = 'A relaxing massage from a trained therapist. Therapeutic only.', is_active = 1 WHERE id = :i", ['i' => $id]);

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
