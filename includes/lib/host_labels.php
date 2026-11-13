<?php
/**
 * What the site calls its Hosts.
 *
 * The admin picks a singular and plural name (for example "Companion" / "Companions") and the web addresses for the public
 * section (/hosts) and the member area (/host/...). Everything the visitor reads is then re-labelled when the page is built:
 * page text, menus, e-mails and JSON messages. Code, class names, database values and role names never change.
 *
 *   host_term()        "Host"         host_term(true)   "Hosts"     host_term(false, true) "host"
 *   host_labels_swap() plain text     host_labels_swap_html()       HTML (text and a few attributes only)
 *   host_path_out()    /hosts/5 -> /companions/5 (used by url() and abs_url())
 *   host_path_in()     /companions/5 -> /hosts/5 (used by request_path() so the routes need no change)
 */

declare(strict_types=1);

const HOST_LABEL_DEFAULTS = ['s' => 'Host', 'p' => 'Hosts', 'slug_p' => 'hosts', 'slug_s' => 'host'];

/** Words that cannot be used as a web address because the site already uses them. */
const HOST_RESERVED_SLUGS = [
    'admin', 'api', 'app', 'assets', 'uploads', 'blog', 'browse', 'login', 'logout', 'register', 'signup', 'pricing', 'faq', 'premium', 'international',
    'contact', 'about', 'discover', 'matches', 'messages', 'likes', 'bookings', 'booking', 'u', 'settings', 'profile', 'safety-tips', 'terms-of-service',
    'privacy-policy', 'cookie-policy', 'community-guidelines', 'sitemap', 'robots', 'boost', 'gifts', 'wallet', 'pay', 'cron', 'storage', 'database',
    'includes', 'templates', 'tools', 'docs', 'favorites', 'favourites', 'notifications', 'onboarding', 'verify', 'verification', 'reset-password',
    'forgot-password', 'help', 'support', 'search', 'feed', 'sitemap.xml', 'robots.txt', 'manifest', 'offline', 'install', 'account', 'dashboard',
    'visitors', 'search-people', 'plans', 'checkout', 'payments', 'webhooks', 'callbacks', 'mpesa', 'paystack', 'paypal', 'stripe', 'newsletter', 'unsubscribe',
];

function host_labels(bool $fresh = false): array
{
    static $cache = null;
    if ($cache !== null && !$fresh) {
        return $cache;
    }
    $d = HOST_LABEL_DEFAULTS;
    try {
        $s = trim((string)setting('host_term_singular'));
        $p = trim((string)setting('host_term_plural'));
        $sp = trim((string)setting('host_url_slug'));
        $ss = trim((string)setting('host_area_slug'));
    } catch (Throwable $e) {
        $s = $p = $sp = $ss = '';
    }
    $s = $s !== '' ? $s : $d['s'];
    $p = $p !== '' ? $p : host_pluralise($s);
    $sp = $sp !== '' ? $sp : $d['slug_p'];
    $ss = $ss !== '' ? $ss : $d['slug_s'];
    // page copy says "ID-verified" only while the ID check is one of the ways to be verified
    $vw = '';
    try {
        $vm = function_exists('host_verify_mode') ? host_verify_mode() : 'id';
        $vw = $vm === 'photo' ? 'photo-verified' : ($vm === 'none' ? 'approved' : '');
    } catch (Throwable $e) {
    }
    $cache = [
        's' => $s, 'p' => $p, 'ls' => mb_strtolower($s), 'lp' => mb_strtolower($p), 'slug_p' => $sp, 'slug_s' => $ss,
        'rename' => $s !== $d['s'] || $p !== $d['p'],
        'vword' => $vw,
        'text' => $s !== $d['s'] || $p !== $d['p'] || $vw !== '',
        'urls' => $sp !== $d['slug_p'] || $ss !== $d['slug_s'],
    ];
    return $cache;
}

function host_labels_reset(): void
{
    host_labels(true);
}

function host_term(bool $plural = false, bool $lower = false): string
{
    $L = host_labels();
    return $plural ? ($lower ? $L['lp'] : $L['p']) : ($lower ? $L['ls'] : $L['s']);
}

/** Best-effort English plural for a name the admin typed (they can always type the plural themselves). */
function host_pluralise(string $s): string
{
    if (preg_match('/(s|x|z|ch|sh)$/i', $s)) {
        return $s . 'es';
    }
    if (preg_match('/[^aeiou]y$/i', $s)) {
        return substr($s, 0, -1) . 'ies';
    }
    return $s . 's';
}

function host_article(string $word): string
{
    return preg_match('/^[aeiou]/i', $word) ? 'an' : 'a';
}

/** Turns a name into a safe single web address segment. */
function host_slugify(string $s): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s)) ?? '', '-');
}

/** Error text when this word cannot be a web address, or null when it can. */
function host_slug_error(string $slug, string $other): ?string
{
    if ($slug === '' || !preg_match('/^[a-z0-9](?:[a-z0-9-]{0,28}[a-z0-9])?$/', $slug)) {
        return 'Use 2 to 30 lowercase letters, numbers or hyphens.';
    }
    if ($slug === $other) {
        return 'The section and the member area need different web addresses.';
    }
    if (in_array($slug, HOST_RESERVED_SLUGS, true) && !in_array($slug, ['hosts', 'host'], true)) {
        return '"' . $slug . '" is already used by the site. Pick another word.';
    }
    try {
        if (val('SELECT id FROM pages WHERE slug = :s LIMIT 1', ['s' => $slug])) {
            return 'A page called "' . $slug . '" already exists.';
        }
    } catch (Throwable $e) {
    }
    return null;
}

// ---------------------------------------------------------------------------
// Web addresses
// ---------------------------------------------------------------------------

/** /hosts/5 -> /companions/5 and /host/dashboard -> /companion/dashboard, when the admin changed them. */
function host_path_out(string $path): string
{
    $L = host_labels();
    if (!$L['urls'] || $path === '' || $path[0] !== '/') {
        return $path;
    }
    if ($L['slug_p'] !== 'hosts' && preg_match('#^/hosts(?=$|[/?\#])#', $path)) {
        return '/' . $L['slug_p'] . substr($path, 6);
    }
    if ($L['slug_s'] !== 'host' && preg_match('#^/host(?=$|[/?\#])#', $path)) {
        return '/' . $L['slug_s'] . substr($path, 5);
    }
    return $path;
}

/** The reverse, for incoming requests. */
function host_path_in(string $path): string
{
    $L = host_labels();
    if (!$L['urls']) {
        return $path;
    }
    if ($L['slug_p'] !== 'hosts' && preg_match('#^/' . preg_quote($L['slug_p'], '#') . '(?=$|/)#i', $path)) {
        return '/hosts' . substr($path, strlen($L['slug_p']) + 1);
    }
    if ($L['slug_s'] !== 'host' && preg_match('#^/' . preg_quote($L['slug_s'], '#') . '(?=$|/)#i', $path)) {
        return '/host' . substr($path, strlen($L['slug_s']) + 1);
    }
    return $path;
}

/** Old default addresses send people (and search engines) to the new ones. Runs before routing. */
function host_label_early(): void
{
    $L = host_labels();
    if (!$L['urls'] || CH_CLI || !in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        return;
    }
    $raw = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $base = base_path();
    if ($base !== '' && strpos($raw, $base) === 0) {
        $raw = substr($raw, strlen($base));
    }
    $raw = '/' . trim($raw, '/');
    $new = host_path_out($raw);
    if ($new !== $raw) {
        $q = (string)($_SERVER['QUERY_STRING'] ?? '');
        header('Location: ' . $base . $new . ($q !== '' ? '?' . $q : ''), true, 301);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Re-labelling text
// ---------------------------------------------------------------------------

/** Words before "host" that mean a computer, not a person ("SMTP host", "web host"). */
const HOST_SWAP_SKIP = '(?<!SMTP )(?<!smtp )(?<!IMAP )(?<!POP3 )(?<!server )(?<!Server )(?<!mail )(?<!Mail )(?<!database )(?<!Database )(?<!DB )(?<!web )(?<!Web )(?<!remote )(?<!Remote )(?<!proxy )(?<!Proxy )(?<!SQL )(?<!MySQL )(?<!shared )(?<!Shared )(?<!the HTTP )(?<!http )';

function host_labels_swap(string $text): string
{
    if ($text === '') {
        return $text;
    }
    $L = host_labels();
    if (!$L['text']) {
        return $text;
    }
    if ($L['vword'] !== '' && stripos($text, 'verified') !== false) {
        $word = fn(string $sep) => $sep === '-' ? $L['vword'] : str_replace('-', ' ', $L['vword']);
        $text = preg_replace_callback('/(^|[.!?]\s+)ID([- ])verified\b/u', fn($m) => $m[1] . ucfirst($word($m[2])), $text) ?? $text;
        $text = preg_replace_callback('/\bID([- ])verified\b/u', fn($m) => $word($m[1]), $text) ?? $text;
    }
    if (!$L['rename'] || stripos($text, 'host') === false) {
        return $text;
    }
    $out = preg_replace_callback('/(?<![\w\-])' . HOST_SWAP_SKIP . '(HOSTS|Hosts|hosts|HOST|Host|host)(?![\w\-])/u', function ($m) use ($L) {
        switch ($m[1]) {
            case 'HOSTS':
                return mb_strtoupper($L['p']);
            case 'HOST':
                return mb_strtoupper($L['s']);
            case 'Hosts':
                return $L['p'];
            case 'hosts':
                return $L['lp'];
            case 'Host':
                return $L['s'];
            default:
                return $L['ls'];
        }
    }, $text);
    if ($out === null) {
        return $text;
    }
    // "a Host" becomes "an Expert" when the new word starts with a vowel, and the other way round
    $one = preg_quote($L['s'], '/');
    $out = preg_replace_callback('/\b(an?|An?)(\s+)(' . $one . '|' . preg_quote($L['ls'], '/') . ')(?![\w\-])/u', function ($m) {
        $a = host_article($m[3]);
        return ($m[1][0] === 'A' ? ucfirst($a) : $a) . $m[2] . $m[3];
    }, $out) ?? $out;
    return $out;
}

/** Attributes whose text people read. */
const HOST_SWAP_ATTRS = ['title', 'aria-label', 'placeholder', 'alt', 'data-ask', 'data-ask-label', 'data-title-new', 'data-title-edit', 'data-confirm', 'data-label', 'data-msg', 'data-toast', 'data-empty'];

function host_labels_swap_html(string $html): string
{
    $L = host_labels();
    if ($html === '' || (!$L['text'] && !$L['urls']) || (stripos($html, 'host') === false && stripos($html, 'verified') === false)) {
        return $html;
    }
    $parts = preg_split('~(<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>|<!--.*?-->|<[^>]+>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) {
        return $html;
    }
    foreach ($parts as $i => $p) {
        if ($p === '') {
            continue;
        }
        if ($p[0] !== '<') {
            $parts[$i] = $L['text'] ? host_labels_swap($p) : $p;
            continue;
        }
        if (preg_match('~^<(script|style)\b~i', $p) || strncmp($p, '<!--', 4) === 0) {
            continue;
        }
        $parts[$i] = host_labels_swap_tag($p, $L);
    }
    return implode('', $parts);
}

function host_labels_swap_tag(string $tag, array $L): string
{
    if (stripos($tag, 'host') === false && stripos($tag, 'verified') === false) {
        return $tag;
    }
    return (string)preg_replace_callback('~\s([a-z][a-z0-9:_\-]*)=(")([^"]*)"~i', function ($m) use ($L) {
        $name = strtolower($m[1]);
        $val = $m[3];
        if ($L['text'] && in_array($name, HOST_SWAP_ATTRS, true)) {
            $val = host_labels_swap($val);
        } elseif ($L['urls'] && in_array($name, ['href', 'action', 'data-href', 'data-url'], true)) {
            $base = base_path();
            if ($base !== '' && strpos($val, $base . '/') === 0) {
                $val = $base . host_path_out(substr($val, strlen($base)));
            } else {
                $val = host_path_out($val);
            }
        }
        return ' ' . $m[1] . '=' . $m[2] . $val . $m[2];
    }, $tag);
}

/** Messages in JSON replies: sentences are re-labelled, single tokens such as role "host" are left alone. */
function host_labels_swap_json($data)
{
    $L = host_labels();
    if (!$L['text']) {
        return $data;
    }
    if (is_string($data)) {
        if (strpos($data, '<') !== false && strpos($data, '>') !== false) {
            return host_labels_swap_html($data);
        }
        return strpos($data, ' ') !== false ? host_labels_swap($data) : $data;
    }
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            if (in_array($k, ['role', 'type', 'kind', 'field', 'status', 'slug', 'url', 'href', 'redirect', 'next'], true)) {
                continue;
            }
            $data[$k] = host_labels_swap_json($v);
        }
    }
    return $data;
}

/** Hides "Host" from the re-labeller on purpose (the admin page that explains the default name). */
function host_literal(string $word): string
{
    return preg_replace('/^(.)/u', "$1\u{200D}", $word) ?? $word;
}
