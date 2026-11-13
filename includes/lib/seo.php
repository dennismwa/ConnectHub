<?php
/**
 * SEO engine: page titles and descriptions the admin can override, structured data, redirects and the 404 log, the https/www rule,
 * IndexNow, article helpers (table of contents, FAQ, reading time, SERP checks) and the SEO health checklist.
 * Everything here is safe to call before the database is ready (it falls back to doing nothing).
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Pages the admin can give their own search title and description
// ---------------------------------------------------------------------------

/** page key => [label, path, default title, default description] */
function seo_page_keys(): array
{
    $name = site_name();
    return [
        'home' => ['Home page', '/', '', (string)setting('site_description')],
        'browse' => ['Dating and discovery', '/browse', 'Dating in Kenya | Meet Singles Near You', 'Browse dating profiles in Kenya, find people with shared interests and start conversations on ' . $name . '. Choose your preferences and connect at your own pace.'],
        'pricing' => ['Plans and prices', '/pricing', 'Plans and prices', 'Compare the free, Premium and VIP plans. Pay with M-Pesa or card. Cancel any time.'],
        'faq' => ['Help and FAQs', '/faq', 'Help and frequently asked questions', 'Answers about accounts, safety, payments, verification and how ' . $name . ' works.'],
        'blog' => ['Blog', '/blog', 'Dating and friendship advice', 'Practical advice on dating, safety, first messages and making friends in Kenya.'],
        'contact' => ['Contact us', '/contact', 'Contact us', 'Questions, feedback or a problem with your account? Send us a message and we will reply.'],
        'international' => ['International connections', '/international', 'Meet people abroad', 'Friendships, language exchange and romance with verified people in other countries.'],
        'hosts' => (function_exists('host_seo_directory_defaults') ? (function () {
            $d = host_seo_directory_defaults();
            return [host_term(true) . ' directory', '/hosts', $d['title'], $d['description']];
        })() : ['Hosts directory', '/hosts', '', 'Meet verified hosts for dinners, events and city tours.']),
        'premium' => ['Premium', '/premium', 'Go Premium', 'Unlimited likes, see who likes you and message without limits.'],
        'elite' => [function_exists('elite_label') ? elite_label() . ' page' : 'Elite page', '/elite', trim((string)setting('elite_title')) ?: 'Elite Connections | Private Member Introductions', trim((string)setting('elite_intro')) ?: 'Learn about the private Elite Connections experience, membership access and profile visibility controls on ' . $name . '.'],
        'elite_access' => [function_exists('elite_label') ? elite_label() . ' access page' : 'Elite access page', '/elite/access', trim((string)setting('elite_access_title')), trim((string)setting('elite_access_intro'))],
        'advertise' => ['Advertise page', '/advertise', 'Advertise with us', 'Put your business in front of people nearby on ' . $name . '. Choose a package, upload a picture and pay when you are happy.'],
        'login' => ['Sign in', '/login', 'Sign in', 'Sign in to your account.'],
        'register' => ['Join', '/register', 'Join the free dating site', 'Create a free profile in two minutes and start meeting singles and friends near you on ' . $name . '.'],
    ];
}

/** The page key for a request path, or null when the page has no editable entry. */
function seo_key_for_path(string $path): ?string
{
    foreach (seo_page_keys() as $key => $def) {
        if ($def[1] === $path) {
            return $key;
        }
    }
    return null;
}

/** Admin overrides, keyed by page key. Read once per request; pass true after a save to read them again. */
function seo_overrides(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) {
        return $cache;
    }
    try {
        $cache = [];
        foreach (rows('SELECT * FROM seo_pages') as $r) {
            $cache[$r['page_key']] = $r;
        }
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

/**
 * Save some of a page's search text, leaving its other columns as they are.
 * $f may hold title, description, keywords, heading, intro, og_image, noindex (a missing key is not touched, null or '' clears it).
 */
function seo_page_write(string $key, array $f): void
{
    $cols = ['title', 'description', 'keywords', 'heading', 'intro', 'og_image', 'noindex'];
    $set = [];
    foreach ($cols as $c) {
        if (array_key_exists($c, $f)) {
            $v = $f[$c];
            $set[$c] = $c === 'noindex' ? ((int)$v ? 1 : 0) : ((is_string($v) && trim($v) === '') ? null : $v);
        }
    }
    if (!$set) {
        return;
    }
    $names = array_keys($set);
    $params = ['k' => $key];
    foreach ($names as $c) {
        $params['v_' . $c] = $set[$c];
    }
    q('INSERT INTO seo_pages (page_key, ' . implode(', ', $names) . ') VALUES (:k, ' . implode(', ', array_map(fn($c) => ':v_' . $c, $names)) . ')
       ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(fn($c) => $c . ' = VALUES(' . $c . ')', $names)), $params);
    seo_overrides(true);
}

/** Apply the admin's override for the page being shown. Called from the <head>. */
function seo_apply_overrides(): void
{
    $key = seo_key_for_path(request_path());
    if ($key === null) {
        return;
    }
    $o = seo_overrides()[$key] ?? null;
    if (!$o) {
        return;
    }
    $meta = [];
    if (trim((string)$o['title']) !== '') {
        $meta['title'] = trim((string)$o['title']);
    }
    if (trim((string)$o['description']) !== '') {
        $meta['description'] = trim((string)$o['description']);
    }
    if (trim((string)$o['og_image']) !== '') {
        $meta['og_image'] = abs_url(upload_url((string)$o['og_image']));
    }
    if (trim((string)($o['keywords'] ?? '')) !== '') {
        $meta['keywords'] = trim((string)$o['keywords']);
    }
    if ((int)$o['noindex'] === 1) {
        $meta['robots'] = 'noindex,follow';
    }
    if ($meta) {
        page($meta);
    }
}

/**
 * Merge keyword lists ("a, b" strings or arrays) into one line: the first list's words come first, repeats (ignoring case) are
 * dropped, and the line stops at 15 phrases or 300 characters. Search engines read the first words most.
 */
function seo_keywords_merge(...$lists): string
{
    $out = [];
    $seen = [];
    $len = 0;
    foreach ($lists as $list) {
        $parts = is_array($list) ? $list : preg_split('/[\r\n,;]+/u', (string)$list);
        foreach ($parts ?: [] as $p) {
            $p = trim(preg_replace('/\s+/u', ' ', (string)$p) ?? '');
            $k = mb_strtolower($p);
            if ($p === '' || isset($seen[$k]) || count($out) >= 15 || $len + mb_strlen($p) + 2 > 300) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $p;
            $len += mb_strlen($p) + 2;
        }
    }
    return implode(', ', $out);
}

/** Build the <title> from the title pattern. A page can pass title_full to skip the pattern. */
function seo_format_title(string $pageTitle): string
{
    $name = site_name();
    $full = trim((string)page_get('title_full'));
    if ($full !== '') {
        return $full;
    }
    if ($pageTitle === '') {
        $tag = trim((string)setting('site_tagline'));
        return $tag !== '' ? $name . ' · ' . $tag : $name;
    }
    $pattern = trim((string)setting('seo_title_template'));
    if ($pattern === '' || strpos($pattern, '%page%') === false) {
        $pattern = '%page% · %site%';
    }
    if (mb_stripos($pageTitle, $name) !== false) {
        return $pageTitle; // the name is already in the title, do not say it twice
    }
    return str_replace(['%page%', '%site%'], [$pageTitle, $name], $pattern);
}

function seo_robots_value(string $requested): string
{
    if (setting('seo_noindex_site')) {
        return 'noindex,nofollow';
    }
    $layout = (string)page_get('layout', 'public');
    if (setting('seo_noindex_members') && in_array($layout, ['app', 'admin'], true)) {
        return 'noindex,nofollow';
    }
    $requested = $requested !== '' ? $requested : 'index,follow';
    if (stripos($requested, 'noindex') === false) {
        return $requested . ',max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    }
    return $requested;
}

// ---------------------------------------------------------------------------
// Structured data
// ---------------------------------------------------------------------------

function seo_social_urls(): array
{
    return array_values(array_filter(array_map(fn($k) => trim((string)setting($k)), ['social_facebook', 'social_instagram', 'social_twitter', 'social_tiktok', 'social_youtube'])));
}

function seo_logo_url(): string
{
    foreach (['logo_full', 'logo_compact', 'logo_icon'] as $k) {
        $v = trim((string)setting($k));
        if ($v !== '') {
            return preg_match('#^https?://#', $v) ? $v : abs_url(upload_url($v));
        }
    }
    return abs_url('/icons/icon-512.png');
}

/** The Organization and WebSite graph used on the home page. */
function seo_site_graph(): array
{
    $graph = [];
    if (setting('seo_schema_org')) {
        $org = ['@type' => 'Organization', '@id' => abs_url('/#org'), 'name' => site_name(), 'url' => abs_url('/'),
            'logo' => ['@type' => 'ImageObject', 'url' => seo_logo_url()]];
        if (trim((string)setting('seo_org_legal_name')) !== '') {
            $org['legalName'] = trim((string)setting('seo_org_legal_name'));
        }
        if ($same = seo_social_urls()) {
            $org['sameAs'] = $same;
        }
        $phone = trim((string)(setting('seo_org_phone') ?: setting('site_phone')));
        $email = trim((string)(setting('seo_org_email') ?: setting('support_email')));
        if ($phone !== '' || $email !== '') {
            $org['contactPoint'] = [['@type' => 'ContactPoint', 'contactType' => 'customer support', 'areaServed' => (string)setting('seo_org_country') ?: 'KE', 'availableLanguage' => ['English', 'Swahili']]
                + ($phone !== '' ? ['telephone' => $phone] : []) + ($email !== '' ? ['email' => $email] : [])];
        }
        $org['address'] = ['@type' => 'PostalAddress', 'addressLocality' => (string)setting('seo_org_city'), 'addressRegion' => (string)setting('seo_org_region'), 'addressCountry' => (string)setting('seo_org_country') ?: 'KE'];
        $graph[] = $org;
    }
    $site = ['@type' => 'WebSite', '@id' => abs_url('/#site'), 'name' => site_name(), 'url' => abs_url('/'), 'inLanguage' => str_replace('_', '-', (string)setting('seo_locale') ?: 'en_KE')];
    if (setting('seo_schema_org')) {
        $site['publisher'] = ['@id' => abs_url('/#org')];
    }
    if (setting('seo_schema_sitelinks')) {
        $site['potentialAction'] = ['@type' => 'SearchAction', 'target' => ['@type' => 'EntryPoint', 'urlTemplate' => abs_url('/browse') . '?q={search_term_string}'], 'query-input' => 'required name=search_term_string'];
    }
    $graph[] = $site;
    return $graph;
}

/**
 * Apply the admin's chosen name for Hosts to the words inside JSON-LD.
 *
 * The page-wide re-labeller skips <script> on purpose, so structured data would otherwise keep saying
 * "Hosts" after a rename. Addresses, identifiers and type names are left exactly as they are.
 */
const SEO_SCHEMA_KEEP = ['@context', '@type', '@id', 'url', 'item', 'logo', 'image', 'sameAs', 'target', 'urlTemplate', 'telephone', 'email', 'inLanguage', 'query-input', 'addressCountry'];

function seo_schema_relabel($node)
{
    if (!function_exists('host_labels_swap')) {
        return $node;
    }
    if (is_string($node)) {
        return preg_match('#^(https?:)?//#', $node) ? $node : host_labels_swap($node);
    }
    if (!is_array($node)) {
        return $node;
    }
    foreach ($node as $k => $v) {
        $node[$k] = in_array((string)$k, SEO_SCHEMA_KEEP, true) ? $v : seo_schema_relabel($v);
    }
    return $node;
}

/** [[label, path], ...] => BreadcrumbList. The last item is the current page. */
function seo_breadcrumb_schema(array $items): ?array
{
    if (!setting('seo_schema_breadcrumbs') || count($items) < 2) {
        return null;
    }
    $list = [];
    foreach (array_values($items) as $i => [$label, $path]) {
        $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label, 'item' => preg_match('#^https?://#', (string)$path) ? $path : abs_url((string)$path)];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

/** All JSON-LD for the page: its own schema plus breadcrumbs, as one @graph. */
function seo_schema_for_page(): ?array
{
    $nodes = [];
    $own = page_get('schema');
    if (is_array($own)) {
        if (isset($own['@graph'])) {
            $nodes = array_merge($nodes, $own['@graph']);
        } else {
            unset($own['@context']);
            $nodes[] = $own;
        }
    }
    $crumbs = page_get('breadcrumbs');
    if (is_array($crumbs) && ($bc = seo_breadcrumb_schema($crumbs))) {
        $nodes[] = $bc;
    }
    if (!$nodes) {
        return null;
    }
    // JSON-LD sits inside a <script>, which the page-wide re-labeller skips on purpose, so the
    // chosen name for Hosts is applied to it here instead.
    return seo_schema_relabel(['@context' => 'https://schema.org', '@graph' => $nodes]);
}

// ---------------------------------------------------------------------------
// Redirects, 404 log, https/www
// ---------------------------------------------------------------------------

function seo_redirect_map(): array
{
    try {
        return cache_remember('seo_redirect_map', 300, function () {
            $map = [];
            foreach (rows('SELECT id, from_path, to_url, code FROM seo_redirects WHERE is_active = 1 ORDER BY id LIMIT 3000') as $r) {
                $map[strtolower($r['from_path'])] = [(int)$r['id'], $r['to_url'], (int)$r['code']];
            }
            return $map;
        });
    } catch (Throwable $e) {
        return [];
    }
}

function seo_normalize_path(string $path): string
{
    $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: $path, '/');
    return strtolower($path);
}

/** Runs at the start of every request: the https/www rule, the IndexNow key file and the redirect list. */
function seo_early(): void
{
    if (CH_CLI || !isset($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
        return;
    }
    $path = request_path();

    // IndexNow key file
    if (preg_match('#^/([A-Za-z0-9]{16,64})\.txt$#', $path, $m)) {
        $key = trim((string)setting('seo_indexnow_key'));
        if ($key !== '' && hash_equals($key, $m[1])) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $key;
            exit;
        }
    }

    // One address for the whole site: https and www or not
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $hostOnly = preg_replace('/:\d+$/', '', $host);
    $isLocal = $hostOnly === '' || $hostOnly === 'localhost' || filter_var($hostOnly, FILTER_VALIDATE_IP) || substr($hostOnly, -5) === '.test' || substr($hostOnly, -6) === '.local';
    if (!$isLocal && strpos($path, '/admin') !== 0 && $path !== '/api/sw' && strpos($path, '/__dev') !== 0) {
        $wantHttps = (bool)setting('seo_force_https') && !is_https();
        $www = (string)setting('seo_www');
        $newHost = $host;
        if ($www === 'add' && strpos($host, 'www.') !== 0 && substr_count($hostOnly, '.') === 1) {
            $newHost = 'www.' . $host;
        } elseif ($www === 'remove' && strpos($host, 'www.') === 0) {
            $newHost = substr($host, 4);
        }
        if ($wantHttps || $newHost !== $host) {
            $scheme = ($wantHttps || is_https()) ? 'https' : 'http';
            header('Location: ' . $scheme . '://' . $newHost . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }
    }

    // Redirect manager
    $map = seo_redirect_map();
    if ($map) {
        $hit = $map[seo_normalize_path($path)] ?? null;
        if ($hit) {
            try {
                q('UPDATE seo_redirects SET hits = hits + 1 WHERE id = :i', ['i' => $hit[0]]);
            } catch (Throwable $e) {
            }
            $to = $hit[1];
            if (!preg_match('#^https?://#i', $to)) {
                $to = abs_url($to);
            }
            $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
            header('Location: ' . $to . ($qs !== '' && strpos($to, '?') === false ? '?' . $qs : ''), true, in_array($hit[2], [301, 302, 307, 308], true) ? $hit[2] : 301);
            exit;
        }
    }
}

function seo_log_404(string $path): void
{
    if (CH_CLI || !setting('seo_log_404')) {
        return;
    }
    if (preg_match('#\.(css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|txt|xml|json)$#i', $path) && !preg_match('#\.(xml|txt)$#', $path)) {
        return;
    }
    if (strlen($path) > 250 || preg_match('#(wp-|\.php|xmlrpc|\.env|phpmyadmin|\.git)#i', $path)) {
        return; // scanners, not real visitors
    }
    $ref = mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 250);
    try {
        q('INSERT INTO seo_404 (path, referrer) VALUES (:p, :r) ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = NOW(), referrer = COALESCE(NULLIF(:r2, \'\'), referrer)', ['p' => $path, 'r' => $ref !== '' ? $ref : null, 'r2' => $ref]);
        if (random_int(1, 200) === 1) {
            q('DELETE FROM seo_404 WHERE last_seen < (NOW() - INTERVAL 120 DAY)');
        }
    } catch (Throwable $e) {
    }
}

function seo_redirect_flush(): void
{
    cache_forget('seo_redirect_map');
}

// ---------------------------------------------------------------------------
// IndexNow
// ---------------------------------------------------------------------------

function seo_indexnow_key(): string
{
    $key = trim((string)setting('seo_indexnow_key'));
    if ($key === '' && setting('seo_indexnow_enabled')) {
        $key = bin2hex(random_bytes(16));
        try {
            setting_set('seo_indexnow_key', $key);
        } catch (Throwable $e) {
        }
    }
    return $key;
}

/** Tell search engines about new or changed addresses. Never blocks the page and never fails loudly. */
function seo_indexnow_submit(array $urls): void
{
    if (CH_CLI || !setting('seo_indexnow_enabled') || setting('seo_noindex_site') || !function_exists('curl_init')) {
        return;
    }
    $host = (string)parse_url(abs_url('/'), PHP_URL_HOST);
    if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return;
    }
    $key = seo_indexnow_key();
    if ($key === '') {
        return;
    }
    $urls = array_values(array_unique(array_filter($urls)));
    if (!$urls) {
        return;
    }
    $payload = json_encode(['host' => $host, 'key' => $key, 'keyLocation' => abs_url('/' . $key . '.txt'), 'urlList' => array_slice($urls, 0, 100)]);
    register_shutdown_function(function () use ($payload) {
        $finish = 'fastcgi_finish_request';
        if (function_exists($finish)) {
            @$finish();
        }
        $ch = curl_init('https://api.indexnow.org/indexnow');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 2]);
        @curl_exec($ch);
        curl_close($ch);
    });
}

// ---------------------------------------------------------------------------
// Articles
// ---------------------------------------------------------------------------

function seo_reading_minutes(string $html): int
{
    return max(1, (int)ceil(str_word_count(strip_tags($html)) / 220));
}

/** Give every h2/h3 an id and collect a table of contents. Returns [html, [[level, id, text], ...]]. */
function seo_toc(string $html): array
{
    if (trim($html) === '') {
        return [$html, []];
    }
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $dom->getElementById('__root');
    if (!$root) {
        return [$html, []];
    }
    $toc = [];
    $used = [];
    foreach ($dom->getElementsByTagName('*') as $el) {
        if (!in_array(strtolower($el->tagName), ['h2', 'h3'], true)) {
            continue;
        }
        $text = trim(preg_replace('/\s+/', ' ', $el->textContent) ?? '');
        if ($text === '') {
            continue;
        }
        $id = slugify($text, 60);
        $base = $id;
        for ($i = 2; isset($used[$id]); $i++) {
            $id = $base . '-' . $i;
        }
        $used[$id] = true;
        $el->setAttribute('id', $id);
        $toc[] = [(int)substr($el->tagName, 1), $id, $text];
    }
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $dom->saveHTML($c);
    }
    return [$out, $toc];
}

/** FAQ rows stored as JSON [{q,a}, ...]. */
function seo_faq_items($raw): array
{
    $list = is_array($raw) ? $raw : (json_decode((string)$raw, true) ?: []);
    $out = [];
    foreach ($list as $f) {
        $q = trim((string)($f['q'] ?? ''));
        $a = trim((string)($f['a'] ?? ''));
        if ($q !== '' && $a !== '') {
            $out[] = ['q' => $q, 'a' => $a];
        }
    }
    return $out;
}

function seo_faq_schema(array $items): ?array
{
    if (!$items) {
        return null;
    }
    return ['@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $items)];
}

function seo_blog_author_name(?string $profileName = null): string
{
    $set = trim((string)setting('seo_blog_author'));
    if ($set !== '') {
        return $set;
    }
    $p = trim((string)$profileName);
    return $p !== '' && $p !== 'Site admin' ? $p : site_name() . ' team';
}

/**
 * Quick quality checks for one article. Returns a list of [status, text] where status is ok, warn or bad, plus a 0 to 100 score.
 */
function seo_post_checks(array $p): array
{
    $title = trim((string)($p['meta_title'] ?: $p['title']));
    $desc = trim((string)($p['meta_description'] ?? ''));
    $html = (string)($p['content'] ?? '');
    $text = trim(strip_tags($html));
    $words = str_word_count($text);
    $kw = mb_strtolower(trim((string)($p['focus_keyword'] ?? '')));
    $checks = [];
    $add = function (string $s, string $t) use (&$checks) {
        $checks[] = [$s, $t];
    };
    $tl = mb_strlen($title);
    $add($tl >= 30 && $tl <= 62 ? 'ok' : ($tl === 0 ? 'bad' : 'warn'), $tl >= 30 && $tl <= 62 ? 'Search title length is good (' . $tl . ').' : 'Search title is ' . $tl . ' characters. Aim for 30 to 60.');
    $dl = mb_strlen($desc);
    $add($dl >= 110 && $dl <= 160 ? 'ok' : ($dl === 0 ? 'bad' : 'warn'), $dl >= 110 && $dl <= 160 ? 'Search description length is good (' . $dl . ').' : ($dl === 0 ? 'Add a search description of 120 to 155 characters.' : 'Search description is ' . $dl . ' characters. Aim for 120 to 155.'));
    $add($words >= 900 ? 'ok' : ($words >= 500 ? 'warn' : 'bad'), $words . ' words. ' . ($words >= 900 ? 'Detailed enough to compete.' : 'Articles of 900+ words usually rank better.'));
    preg_match_all('#<h2[ >]#i', $html, $h2);
    $add(count($h2[0]) >= 3 ? 'ok' : 'warn', count($h2[0]) >= 3 ? count($h2[0]) . ' sections with headings.' : 'Add at least 3 headings (h2) so the article is easy to scan.');
    preg_match_all('#<img\b[^>]*>#i', $html, $imgs);
    $noAlt = 0;
    foreach ($imgs[0] as $img) {
        if (!preg_match('#\balt="[^"]+"#i', $img)) {
            $noAlt++;
        }
    }
    if ($imgs[0]) {
        $add($noAlt === 0 ? 'ok' : 'bad', $noAlt === 0 ? 'Every picture has alt text.' : $noAlt . ' picture(s) without alt text.');
    }
    preg_match_all('#<a\b[^>]*href="(/[^"]*)"#i', $html, $int);
    $add(count($int[0]) >= 2 ? 'ok' : 'warn', count($int[0]) >= 2 ? count($int[0]) . ' links to other pages on your site.' : 'Link to 2 or more related pages or articles on your site.');
    $add(count(seo_faq_items($p['faq'] ?? '')) >= 3 ? 'ok' : 'warn', count(seo_faq_items($p['faq'] ?? '')) >= 3 ? 'FAQ block present (can show as rich results).' : 'Add 3 or more FAQs to be eligible for rich results.');
    $add(!empty($p['cover_path']) ? 'ok' : 'warn', !empty($p['cover_path']) ? 'Has a cover picture.' : 'Add a cover picture for social sharing.');
    if ($kw !== '') {
        $first = mb_strtolower(mb_substr($text, 0, 300));
        $add(mb_strpos(mb_strtolower($title), $kw) !== false ? 'ok' : 'warn', mb_strpos(mb_strtolower($title), $kw) !== false ? 'Focus keyword is in the title.' : 'Put the focus keyword in the search title.');
        $add(mb_strpos(mb_strtolower($desc), $kw) !== false ? 'ok' : 'warn', mb_strpos(mb_strtolower($desc), $kw) !== false ? 'Focus keyword is in the description.' : 'Use the focus keyword in the description.');
        $add(mb_strpos($first, $kw) !== false ? 'ok' : 'warn', mb_strpos($first, $kw) !== false ? 'Focus keyword is in the opening.' : 'Mention the focus keyword in the first paragraph.');
        $hasH = false;
        preg_match_all('#<h[23][^>]*>(.*?)</h[23]>#is', $html, $hs);
        foreach ($hs[1] as $h) {
            if (mb_strpos(mb_strtolower(strip_tags($h)), $kw) !== false) {
                $hasH = true;
            }
        }
        $add($hasH ? 'ok' : 'warn', $hasH ? 'Focus keyword is in a heading.' : 'Use the focus keyword in one heading.');
    } else {
        $add('warn', 'Set a focus keyword (the phrase people search for).');
    }
    $score = 0;
    foreach ($checks as [$s]) {
        $score += $s === 'ok' ? 1 : ($s === 'warn' ? 0.45 : 0);
    }
    return [$checks, (int)round($checks ? $score / count($checks) * 100 : 0)];
}

// ---------------------------------------------------------------------------
// Health checklist for Admin > SEO
// ---------------------------------------------------------------------------

/** [[status ok|warn|bad, title, detail, fixUrl|null], ...] */
function seo_health(): array
{
    $out = [];
    $add = function (string $s, string $t, string $d, ?string $fix = null) use (&$out) {
        $out[] = [$s, $t, $d, $fix];
    };
    $site = site_url();
    $isHttps = strpos($site, 'https://') === 0;
    $add($isHttps ? 'ok' : 'warn', 'Site address uses https', $isHttps ? 'Search engines prefer secure sites.' : 'Your site address is not https. Install a certificate and set the site address to https://.', '/admin/settings/general');
    $add(setting('seo_noindex_site') ? 'bad' : 'ok', 'Search engines allowed', setting('seo_noindex_site') ? 'The whole site is hidden from Google. Turn this off on launch.' : 'Search engines can index the site.', '/admin/settings/seo');
    $add(trim((string)setting('seo_google_verification')) !== '' ? 'ok' : 'warn', 'Google Search Console connected', trim((string)setting('seo_google_verification')) !== '' ? 'Verification tag is on every page.' : 'Add your Search Console code so you can see rankings and submit the sitemap.', '/admin/seo?tab=connect');
    $add(trim((string)setting('seo_bing_verification')) !== '' ? 'ok' : 'warn', 'Bing Webmaster connected', trim((string)setting('seo_bing_verification')) !== '' ? 'Verification tag is on every page.' : 'Bing also powers other search engines. Add its code (free).', '/admin/seo?tab=connect');
    $add(trim((string)(setting('og_image'))) !== '' ? 'ok' : 'warn', 'Social share picture', trim((string)setting('og_image')) !== '' ? 'Set.' : 'Upload a 1200 x 630 picture so links look good on WhatsApp, Facebook and X.', '/admin/settings/seo');
    $add(trim((string)setting('site_description')) !== '' ? 'ok' : 'bad', 'Site description', trim((string)setting('site_description')) !== '' ? 'Set.' : 'Write a short description of the site.', '/admin/settings/general');
    $add(count(seo_social_urls()) >= 2 ? 'ok' : 'warn', 'Social profiles linked', count(seo_social_urls()) . ' linked. Add your pages so Google connects them to your brand.', '/admin/settings/seo');
    $add(trim((string)(setting('seo_org_phone') ?: setting('site_phone'))) !== '' || trim((string)(setting('seo_org_email') ?: setting('support_email'))) !== '' ? 'ok' : 'warn', 'Contact details for search engines', 'Phone or email in the organisation card builds trust.', '/admin/settings/seo');
    $add(setting('seo_indexnow_enabled') ? 'ok' : 'warn', 'Instant indexing (IndexNow)', setting('seo_indexnow_enabled') ? 'New and edited articles are announced.' : 'Turn on to announce new articles to Bing and others.', '/admin/settings/seo');

    try {
        $posts = (int)val("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'", [], 0);
        $add($posts >= 20 ? 'ok' : ($posts >= 8 ? 'warn' : 'bad'), 'Articles published', $posts . ' published. Sites with 20+ useful articles rank for many more searches.', '/admin/blog');
        $noDesc = (int)val("SELECT COUNT(*) FROM blog_posts WHERE status = 'published' AND (meta_description IS NULL OR meta_description = '')", [], 0);
        $add($noDesc === 0 ? 'ok' : 'warn', 'Articles with a search description', $noDesc === 0 ? 'All have one.' : $noDesc . ' article(s) have no search description.', '/admin/seo?tab=articles');
        $short = (int)val("SELECT COUNT(*) FROM blog_posts WHERE status = 'published' AND CHAR_LENGTH(content) < 3500", [], 0);
        $add($short === 0 ? 'ok' : 'warn', 'Article depth', $short === 0 ? 'All articles are detailed.' : $short . ' article(s) look short. Longer, helpful articles rank better.', '/admin/seo?tab=articles');
        $dupes = (int)val("SELECT COUNT(*) FROM (SELECT meta_title FROM blog_posts WHERE status = 'published' AND meta_title <> '' GROUP BY meta_title HAVING COUNT(*) > 1) d", [], 0);
        $add($dupes === 0 ? 'ok' : 'bad', 'Duplicate search titles', $dupes === 0 ? 'None.' : $dupes . ' title(s) used twice. Each page needs its own.', '/admin/seo?tab=articles');
        $broken = (int)val('SELECT COUNT(*) FROM seo_404 WHERE hits >= 3', [], 0);
        $add($broken === 0 ? 'ok' : 'warn', 'Broken links visitors hit', $broken === 0 ? 'None repeated.' : $broken . ' missing address(es) were requested 3+ times. Redirect them.', '/admin/seo?tab=redirects');
    } catch (Throwable $e) {
    }
    $mailOk = trim((string)setting('support_email')) !== '';
    $add($mailOk ? 'ok' : 'warn', 'Support email', $mailOk ? 'Set.' : 'Add a support email so people can reach you.', '/admin/settings/general');
    return $out;
}

/** Share of OK checks, 0 to 100. */
function seo_health_score(array $checks): int
{
    $t = 0;
    foreach ($checks as [$s]) {
        $t += $s === 'ok' ? 1 : ($s === 'warn' ? 0.5 : 0);
    }
    return $checks ? (int)round($t / count($checks) * 100) : 0;
}

// ---------------------------------------------------------------------------
// Location pages: helpful, unique copy for /browse/{county-or-town}
// ---------------------------------------------------------------------------

/** HTML and FAQ list for the text block under the results on Browse. Built from real numbers so every place reads differently. */
function browse_seo_block(?array $loc, ?array $parent, int $total): array
{
    $site = site_name();
    $faq = [];
    $h = '';
    if (!$loc) {
        $counties = rows("SELECT name, slug FROM locations WHERE type = 'county' AND is_active = 1 ORDER BY is_popular DESC, name LIMIT 24");
        $h .= '<div class="prose-ch seo-block"><h2>Meet people across Kenya</h2><p>' . e($site) . ' shows real profiles of people in every county, ordered by how close they are to you. Pick your county or town to see who is nearby, then join free to like, match and chat.</p>';
        if ($counties) {
            $h .= '<h3>Browse by county</h3><ul class="seo-links">';
            foreach ($counties as $c) {
                $h .= '<li><a href="' . e(url('/browse/' . $c['slug'])) . '">People in ' . e($c['name']) . '</a></li>';
            }
            $h .= '</ul>';
        }
        return ['html' => $h . '</div>', 'faq' => []];
    }
    $name = (string)$loc['name'];
    $where = $parent ? $name . ', ' . $parent['name'] : $name;
    $verified = 0;
    $women = 0;
    $men = 0;
    try {
        $col = $loc['type'] === 'county' ? 'ul.county_id = :l' : 'ul.location_id = :l';
        $row = row("SELECT COUNT(*) n, SUM(p.is_verified = 1) v, SUM(p.gender = 'female') w, SUM(p.gender = 'male') m
                    FROM users u JOIN user_profiles p ON p.user_id = u.id JOIN user_locations ul ON ul.user_id = u.id
                    WHERE u.role = 'user' AND u.status = 'active' AND $col", ['l' => $loc['id']]);
        $verified = (int)($row['v'] ?? 0);
        $women = (int)($row['w'] ?? 0);
        $men = (int)($row['m'] ?? 0);
    } catch (Throwable $e) {
    }
    $intro = trim((string)($loc['seo_intro'] ?? ''));
    $h .= '<div class="prose-ch seo-block"><h2>Dating and friendships in ' . e($name) . '</h2>';
    if ($intro !== '') {
        foreach (preg_split('/\R{2,}/', $intro) as $para) {
            $h .= '<p>' . nl2br(e(trim($para))) . '</p>';
        }
    } else {
        $h .= '<p>' . e($site) . ' makes it easy to meet people in ' . e($where) . '. ';
        if ($total > 0) {
            $h .= 'Right now ' . number_format($total) . ' ' . ($total === 1 ? 'member lists' : 'members list') . ' ' . e($name) . ' as home' . ($verified > 0 ? ', and ' . number_format($verified) . ' of them ' . ($verified === 1 ? 'has' : 'have') . ' a verified badge' : '') . '. ';
        }
        $h .= 'Profiles are ordered by distance, so the people you see are the ones who are really nearby. Whether you want a relationship, a new circle of friends or someone to explore ' . e($name) . ' with, you can filter by age, verified members and who is online now.</p>';
        $h .= '<p>Joining is free. Create a profile with a few photos, say what you are looking for and start liking. When two people like each other it is a match and the chat opens. Everyone stays in control: you choose who can see your photos and you can report or block anyone.</p>';
    }
    // places nearby
    $near = [];
    if ($loc['type'] === 'county') {
        $near = rows("SELECT name, slug FROM locations WHERE type = 'town' AND parent_id = :p AND is_active = 1 ORDER BY is_popular DESC, name LIMIT 16", ['p' => $loc['id']]);
        $label = 'Towns in ' . $name;
    } else {
        $near = $parent ? rows("SELECT name, slug FROM locations WHERE type = 'town' AND parent_id = :p AND id <> :i AND is_active = 1 ORDER BY is_popular DESC, name LIMIT 16", ['p' => $parent['id'], 'i' => $loc['id']]) : [];
        $label = 'More places near ' . $name;
    }
    if ($parent) {
        array_unshift($near, ['name' => $parent['name'], 'slug' => $parent['slug']]);
    }
    if ($near) {
        $h .= '<h3>' . e($label) . '</h3><ul class="seo-links">';
        foreach ($near as $n) {
            $h .= '<li><a href="' . e(url('/browse/' . $n['slug'])) . '">People in ' . e($n['name']) . '</a></li>';
        }
        $h .= '</ul>';
    }
    $faq = [
        ['q' => 'How do I meet people in ' . $name . ' on ' . $site . '?', 'a' => 'Create a free profile, add your photos and interests, then browse or like people in ' . $name . '. When two people like each other it becomes a match and you can start chatting.'],
        ['q' => 'Is ' . $site . ' free to use in ' . $name . '?', 'a' => 'Yes. Creating a profile, browsing and matching are free. Premium and VIP plans add extras such as unlimited likes, seeing who liked you and messaging without limits. You can pay with M-Pesa.'],
        ['q' => 'How do I stay safe when meeting someone from ' . $name . '?', 'a' => 'Chat on the app first, look for the verified badge, meet in a busy public place, tell a friend where you are going and never send money to someone you have not met. You can report or block anyone at any time.'],
    ];
    $h .= '<h3>Questions about meeting people in ' . e($name) . '</h3>';
    foreach ($faq as $f) {
        $h .= '<details class="faq-item"><summary>' . e($f['q']) . '</summary><div class="faq-a">' . e($f['a']) . '</div></details>';
    }
    return ['html' => $h . '</div>', 'faq' => $faq];
}

/** "Q: ...\nA: ...\n\nQ: ..." typed by an admin => [[q, a], ...] */
function ac_faq_parse(string $text): array
{
    $out = [];
    foreach (preg_split('/\R{2,}/', trim(str_replace("\r", '', $text))) ?: [] as $block) {
        if (!preg_match('/^\s*Q\s*[:.)-]\s*(.+?)\s*\n\s*A\s*[:.)-]\s*(.+)$/si', trim($block), $m)) {
            continue;
        }
        $q = trim(preg_replace('/\s+/', ' ', $m[1]) ?? '');
        $a = trim(preg_replace('/[ \t]+/', ' ', $m[2]) ?? '');
        if ($q !== '' && $a !== '') {
            $out[] = ['q' => mb_substr($q, 0, 200), 'a' => mb_substr($a, 0, 1500)];
        }
    }
    return $out;
}

function ac_faq_text($raw): string
{
    $out = [];
    foreach (seo_faq_items($raw) as $f) {
        $out[] = 'Q: ' . $f['q'] . "\nA: " . $f['a'];
    }
    return implode("\n\n", $out);
}
