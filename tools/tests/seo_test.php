<?php
/**
 * SEO: titles, robots rules, FAQ parsing, table of contents, article score, redirects, 404 log, sitemap, robots.txt, feed.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/seo_test.php [http://127.0.0.1:8200]   (the URL is optional; it adds live sitemap/robots/feed checks)
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
}
$cleanup = [];
register_shutdown_function(function () use (&$touched, &$cleanup) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    foreach ($cleanup as [$t, $id]) {
        q("DELETE FROM $t WHERE id = :i", ['i' => $id]);
    }
});

// --- tables from migration 15
foreach (['seo_pages', 'seo_redirects', 'seo_404'] as $t) {
    check("table $t exists", (bool)val("SHOW TABLES LIKE '$t'"));
}
foreach (['focus_keyword', 'faq', 'og_image', 'canonical_url', 'noindex', 'cover_alt'] as $c) {
    check("blog_posts.$c exists", (bool)val("SHOW COLUMNS FROM blog_posts LIKE '$c'"));
}

// --- titles
$name = site_name();
set_for_test('seo_title_template', '%page% | %site%');
page(['title' => 'Safety tips']);
check('title uses the template', seo_format_title('Safety tips') === 'Safety tips | ' . $name, seo_format_title('Safety tips'));
check('site name is not repeated', seo_format_title('About ' . $name) === 'About ' . $name);
set_for_test('seo_title_template', 'broken template');
check('a template without %page% falls back', str_contains(seo_format_title('Pricing'), 'Pricing'));

// --- robots
set_for_test('seo_noindex_site', '0');
set_for_test('seo_noindex_members', '1');
page(['layout' => 'public']);
check('public pages are indexable with rich snippets', str_contains(seo_robots_value(''), 'max-image-preview:large') && !str_contains(seo_robots_value(''), 'noindex'));
check('an explicit noindex stays noindex', str_contains(seo_robots_value('noindex,follow'), 'noindex'));
page(['layout' => 'app']);
check('member pages are noindex', str_starts_with(seo_robots_value(''), 'noindex'));
page(['layout' => 'admin']);
check('admin pages are noindex', str_starts_with(seo_robots_value(''), 'noindex'));
page(['layout' => 'public']);
set_for_test('seo_noindex_site', '1');
check('site-wide noindex wins', seo_robots_value('') === 'noindex,nofollow');
set_for_test('seo_noindex_site', '0');

// --- FAQ round trip
$faq = ac_faq_parse("Q: Is it free?\nA: Yes, joining is free.\n\nQ: Is it safe?\nA: We verify profiles.\nMeet in public.\n\nnot a pair");
check('FAQ text parses into pairs', count($faq) === 2 && $faq[0]['q'] === 'Is it free?');
check('FAQ answers keep extra lines', str_contains($faq[1]['a'], 'Meet in public'));
check('FAQ text round-trips', count(ac_faq_parse(ac_faq_text(json_encode($faq)))) === 2);
$schema = seo_faq_schema(seo_faq_items(json_encode($faq)));
check('FAQ schema is a FAQPage', ($schema['@type'] ?? '') === 'FAQPage' && count($schema['mainEntity']) === 2);
check('no FAQ, no schema', seo_faq_schema([]) === null);

// --- table of contents
[$html, $toc] = seo_toc('<h2>First part</h2><p>a</p><h3>Sub part</h3><h2>First part</h2><p>b</p>');
check('toc finds 3 headings', count($toc) === 3);
check('toc ids are unique', count(array_unique(array_column($toc, 1))) === 3);
check('headings get ids in the html', str_contains($html, 'id="first-part"') && str_contains($html, 'id="first-part-2"'));
check('toc of nothing is empty', seo_toc('')[1] === []);
check('reading time is at least 1 minute', seo_reading_minutes('<p>short</p>') === 1);
check('reading time grows with length', seo_reading_minutes('<p>' . str_repeat('word ', 1200) . '</p>') >= 5);

// --- article score
$thin = seo_post_checks(['title' => 'Hi', 'meta_title' => '', 'meta_description' => '', 'content' => '<p>tiny</p>', 'faq' => '', 'focus_keyword' => '', 'cover_path' => '', 'tags' => '']);
$strong = seo_post_checks([
    'title' => 'How to stay safe on a first date in Nairobi', 'meta_title' => 'How to stay safe on a first date in Nairobi', 'meta_description' => str_repeat('Stay safe on a first date in Nairobi with these simple steps that work. ', 2),
    'content' => '<p>Stay safe on a first date in Nairobi.</p>' . str_repeat('<h2>Stay safe on a first date</h2><p>' . str_repeat('word ', 200) . '<a href="/blog">more</a> <a href="/safety">safety</a></p>', 6),
    'faq' => json_encode([['q' => 'a?', 'a' => 'b'], ['q' => 'c?', 'a' => 'd'], ['q' => 'e?', 'a' => 'f']]), 'focus_keyword' => 'stay safe on a first date', 'cover_path' => 'x.jpg', 'cover_alt' => 'Two friends', 'tags' => 'safety',
]);
check('a strong article scores higher than a thin one', $strong[1] > $thin[1] + 30, $strong[1] . ' vs ' . $thin[1]);

// --- redirects and 404 log
$path = '/old-seo-test-' . bin2hex(random_bytes(3));
$rid = insert('seo_redirects', ['from_path' => $path, 'to_url' => '/blog', 'code' => 301, 'is_active' => 1]);
$cleanup[] = ['seo_redirects', $rid];
seo_redirect_flush();
check('redirect is found in the map', isset(seo_redirect_map()[$path]));
check('paths are normalised', seo_normalize_path('/Foo/Bar/?x=1') === '/foo/bar');
check('404 logging is skipped on the command line', (function () { seo_log_404('/missing-seo-test'); return !val('SELECT id FROM seo_404 WHERE path = :p', ['p' => '/missing-seo-test']); })());

// --- IndexNow
set_for_test('seo_indexnow_enabled', '1');
set_for_test('seo_indexnow_key', '');
$k = seo_indexnow_key();
check('IndexNow key is created once', preg_match('/^[a-f0-9]{32}$/', $k) === 1 && seo_indexnow_key() === $k);

// --- health
$h = seo_health();
check('health returns checks', is_array($h) && count($h) >= 8);
$sc = seo_health_score($h);
check('health score is 0 to 100', $sc >= 0 && $sc <= 100);

// --- live endpoints
$base = null;
foreach ($argv as $a) {
    if (preg_match('#^https?://#', $a)) {
        $base = rtrim($a, '/');
    }
}
if ($base) {
    $get = function (string $p) use ($base): array {
        $ch = curl_init($base . $p);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false]);
        $r = (string)curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $hs = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        return [$code, substr($r, 0, $hs), substr($r, $hs)];
    };
    [$c, , $b] = $get('/sitemap.xml');
    check('sitemap index is served', $c === 200 && str_contains($b, '<sitemapindex'));
    preg_match_all('#<loc>([^<]+)</loc>#', $b, $m);
    $allOk = true;
    foreach ($m[1] as $loc) {
        $p = parse_url(html_entity_decode($loc), PHP_URL_PATH);
        [$cc, , $bb] = $get($p);
        $allOk = $allOk && $cc === 200 && str_contains($bb, '<urlset');
    }
    check('every sitemap part is valid', $allOk && count($m[1]) >= 2);
    [$c, , $b] = $get('/robots.txt');
    check('robots.txt has the sitemap line and blocks admin', $c === 200 && str_contains($b, 'Sitemap:') && str_contains($b, 'Disallow: /admin'));
    [$c, $hd, $b] = $get('/blog/feed.xml');
    check('RSS feed is valid', $c === 200 && str_contains($b, '<rss') && stripos($hd, 'xml') !== false);
    [$c, , $b] = $get('/');
    check('home page has a title, description, canonical and JSON-LD', $c === 200 && str_contains($b, '<title>') && str_contains($b, 'name="description"') && str_contains($b, 'rel="canonical"') && str_contains($b, 'application/ld+json'));
    check('home page has exactly one h1', substr_count($b, '<h1') === 1, (string)substr_count($b, '<h1'));
    [$c] = $get($path);
    check('the redirect fires', $c === 301);
    [$c, , $b] = $get('/blog');
    check('blog index is served with breadcrumbs', $c === 200 && str_contains($b, 'BreadcrumbList'));
    [$c] = $get('/blog/category/does-not-exist-zz');
    check('unknown category is a 404', $c === 404);
    [$c, $hd] = $get('/blog?c=safety');
    check('old category links redirect', $c === 301 || $c === 404);
    $slug = (string)val("SELECT slug FROM blog_posts WHERE status = 'published' AND published_at <= NOW() ORDER BY id LIMIT 1");
    if ($slug !== '') {
        [$c, , $b] = $get('/blog/' . $slug);
        check('article has BlogPosting schema, one h1 and a canonical', $c === 200 && str_contains($b, 'BlogPosting') && substr_count($b, '<h1') === 1 && str_contains($b, 'rel="canonical"'));
    }
    [$c] = $get('/admin/seo');
    check('admin SEO page needs a login', $c === 302 || $c === 303 || $c === 401 || $c === 403);
}

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
