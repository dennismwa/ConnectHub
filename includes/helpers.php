<?php
/**
 * General helpers: escaping, URLs, requests, responses, formatting, caching, logging.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Config & environment
// ---------------------------------------------------------------------------

/** Path of the config file. CH_CONFIG (environment) overrides it, which is handy for tests. */
function config_file(): string
{
    $env = getenv('CH_CONFIG');
    return is_string($env) && $env !== '' ? $env : ROOT . '/config/config.php';
}

function config(?string $key = null, $default = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $file = config_file();
        $cfg = is_file($file) ? (array)require $file : [];
    }
    if ($key === null) {
        return $cfg;
    }
    return $cfg[$key] ?? $default;
}

function is_debug(): bool
{
    return (bool)config('debug', false);
}

function is_https(): bool
{
    static $https = null;
    if ($https !== null) {
        return $https;
    }
    $s = $_SERVER;
    $yes = fn($v) => $v !== null && $v !== '' && strtolower((string)$v) !== 'off' && (string)$v !== '0';
    if ($yes($s['HTTPS'] ?? null) || (int)($s['SERVER_PORT'] ?? 0) === 443 || strtolower((string)($s['REQUEST_SCHEME'] ?? '')) === 'https') {
        return $https = true;
    }
    // Behind a proxy or CDN (Cloudflare, cPanel, a load balancer) the connection to PHP can be plain http while the visitor uses https.
    // The scheme is only used to build links for the same visitor who sent the header, so it is read without the "trust proxy" switch.
    $fwd = strtolower(trim(explode(',', (string)($s['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    if ($fwd === 'https' || $fwd === 'wss' || strtolower((string)($s['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on' || strtolower((string)($s['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on' || $yes($s['HTTP_X_ARR_SSL'] ?? null)) {
        return $https = true;
    }
    if (!empty($s['HTTP_CF_VISITOR']) && strpos((string)$s['HTTP_CF_VISITOR'], '"scheme":"https"') !== false) {
        return $https = true;
    }
    if (preg_match('/proto=https/i', (string)($s['HTTP_FORWARDED'] ?? ''))) {
        return $https = true;
    }
    return $https = false;
}

/** Reads the proxy-trust option without needing the database (installer-safe). */
function setting_trust_proxy(): bool
{
    static $v = null;
    if ($v === null) {
        $v = false;
        if (function_exists('setting') && is_file(config_file())) {
            try {
                $v = (bool)setting('trust_proxy_headers');
            } catch (Throwable $e) {
                $v = false;
            }
        }
    }
    return $v;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (setting_trust_proxy()) {
        $cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
            return $cf;
        }
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $first = trim(explode(',', $xff)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
    }
    return $ip;
}

function user_agent(): string
{
    return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

// ---------------------------------------------------------------------------
// Escaping
// ---------------------------------------------------------------------------

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape and convert newlines to <br>. */
function nl2br_e($value): string
{
    return nl2br(e($value), false);
}

/** Echo-safe JSON for use inside <script type="application/json"> or data attributes. */
function json_attr($data): string
{
    return e(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP));
}

function json_script($data): string
{
    return str_replace('</', '<\/', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP));
}

// ---------------------------------------------------------------------------
// URLs
// ---------------------------------------------------------------------------

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $cfg = config('base_path');
    if (is_string($cfg)) {
        return $base = rtrim($cfg, '/');
    }
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(dirname($script), '/');
    // Scripts inside sub-directories (install/, cron/) still resolve to the site root.
    foreach (['/install', '/cron', '/tools'] as $sub) {
        if (substr($dir, -strlen($sub)) === $sub) {
            $dir = substr($dir, 0, -strlen($sub));
        }
    }
    return $base = ($dir === '/' || $dir === '.' ? '' : $dir);
}

function site_origin(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
        $host = 'localhost';
    }
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

function site_url(): string
{
    $configured = function_exists('setting') ? trim((string)setting('site_url')) : '';
    if ($configured === '') {
        return site_origin() . base_path();
    }
    $configured = rtrim($configured, '/');
    $parts = parse_url($configured);
    $host = strtolower((string)($parts['host'] ?? ''));
    $path = (string)($parts['path'] ?? '');
    $strip = fn(string $h) => preg_replace('/^www\./', '', preg_replace('/:\d+$/', '', strtolower($h)));
    $reqHost = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (!CH_CLI && $reqHost !== '' && $host !== '' && $strip($host) === $strip($reqHost)) {
        // The stored address and the visitor's address are the same site: follow the address bar, so a link never points at an
        // http:// or :port address the visitor did not use (that is what shows up as "refused to connect").
        return site_origin() . $path;
    }
    // From the scheduler (emails) or another host: keep the stored address, but never use plain http once the site has been seen on https.
    if (strpos($configured, 'http://') === 0 && function_exists('setting') && (string)setting('_https_seen') === '1' && $host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP)) {
        return 'https://' . substr($configured, 7);
    }
    return $configured;
}

/** Remember that the site was reached over https, so emails sent by the scheduler use https links. */
function site_note_scheme(): void
{
    if (CH_CLI || !is_https() || (string)setting('_https_seen') === '1') {
        return;
    }
    $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return;
    }
    try {
        setting_set('_https_seen', '1');
    } catch (Throwable $e) {
    }
}

function url(string $path = '', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    $path = elite_path_out(host_path_out($path));
    $u = base_path() . ($path === '/' ? '/' : $path);
    if ($query) {
        $u .= (strpos($u, '?') === false ? '?' : '&') . http_build_query($query);
    }
    return $u;
}

function abs_url(string $path = '', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    $path = elite_path_out(host_path_out($path));
    $u = site_url() . ($path === '/' ? '' : $path);
    if ($query) {
        $u .= (strpos($u, '?') === false ? '?' : '&') . http_build_query($query);
    }
    return $u;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = ROOT . '/assets/' . $path;
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return base_path() . '/assets/' . $path . $v;
}

/** URL of a file under /uploads (relative path as stored in the database). */
function upload_url(?string $rel): string
{
    if ($rel === null || $rel === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $rel)) {
        return $rel;
    }
    // Built-in illustrations that ship with the site.
    if (strncmp($rel, '@art/', 5) === 0) {
        return base_path() . '/assets/images/art/' . rawurlencode(basename(substr($rel, 5)));
    }
    // Sharp member photos (_l, _m, _t) are not public files: they go through the permission gate.
    if (preg_match('#^photos/\d{4}/\d{2}/\d+_[0-9a-f]+_[lmt]\.jpg$#', ltrim($rel, '/'))) {
        return photo_gate_path_url(ltrim($rel, '/'));
    }
    return base_path() . '/uploads/' . ltrim($rel, '/');
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function request_path(): string
{
    static $path = null;
    if ($path !== null) {
        return $path;
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $uri = rawurldecode($uri);
    $base = base_path();
    if ($base !== '' && strpos($uri, $base) === 0) {
        $uri = substr($uri, strlen($base));
    }
    $uri = '/' . trim($uri, '/');
    return $path = elite_path_in(host_path_in($uri));
}

function current_url(): string
{
    return url(request_path(), $_GET);
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function wants_json(): bool
{
    if (strpos(request_path(), '/api/') === 0) {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return is_ajax() || (strpos($accept, 'application/json') !== false && strpos($accept, 'text/html') === false);
}

function is_active_path(string $path, bool $prefix = true): bool
{
    $cur = request_path();
    $path = '/' . trim($path, '/');
    if ($path === '/') {
        return $cur === '/';
    }
    return $prefix ? ($cur === $path || strpos($cur, $path . '/') === 0) : $cur === $path;
}

// ---------------------------------------------------------------------------
// Input
// ---------------------------------------------------------------------------

function input(string $key, $default = null)
{
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    return $default;
}

function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function post_str(string $key, int $max = 0, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    if (is_array($v)) {
        return $default;
    }
    $v = trim((string)$v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v) ?? $v;
    return $max > 0 ? mb_substr($v, 0, $max) : $v;
}

function get_str(string $key, int $max = 0, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    if (is_array($v)) {
        return $default;
    }
    $v = trim((string)$v);
    return $max > 0 ? mb_substr($v, 0, $max) : $v;
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? null;
    return is_scalar($v) && is_numeric($v) ? (int)$v : $default;
}

function get_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? null;
    return is_scalar($v) && is_numeric($v) ? (int)$v : $default;
}

function post_bool(string $key): bool
{
    $v = $_POST[$key] ?? '';
    return in_array($v, [1, '1', 'on', 'true', 'yes'], true);
}

/** Array of ints from a POST/GET list field. */
function int_list($value): array
{
    if (!is_array($value)) {
        return [];
    }
    return array_values(array_unique(array_map('intval', array_filter($value, 'is_numeric'))));
}

/** JSON request body as an array (empty when the body is not JSON). */
function json_body(): array
{
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input');
        $d = $raw !== '' && $raw !== false ? json_decode($raw, true) : null;
        $body = is_array($d) ? $d : [];
    }
    return $body;
}

// ---------------------------------------------------------------------------
// Responses
// ---------------------------------------------------------------------------

function redirect(string $to, int $code = 302): void
{
    if (!preg_match('#^(https?:)?//#i', $to) && ($to === '' || $to[0] === '/') && strpos($to, base_path() . '/') !== 0 && $to !== base_path()) {
        $to = url($to);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $to, true, $code);
    exit;
}

/** Redirect to the previous page, falling back to $fallback. Same-site only. */
function back(string $fallback = '/'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    $ours = parse_url(site_origin(), PHP_URL_HOST);
    if ($ref !== '' && $host !== null && $host !== false && strcasecmp((string)$host, (string)$ours) === 0) {
        // keep only the path and query: the address the visitor is on decides the scheme and host
        $path = (string)parse_url($ref, PHP_URL_PATH);
        $query = (string)parse_url($ref, PHP_URL_QUERY);
        if ($path !== '' && $path[0] === '/' && strpos($path, '//') !== 0) {
            redirect($path . ($query !== '' ? '?' . $query : ''));
        }
    }
    redirect($fallback);
}

/** Only allow redirects to paths on this site (used for ?next= parameters). */
function safe_next(?string $next, string $fallback = '/'): string
{
    $next = (string)$next;
    // Browsers drop tabs and line breaks inside a URL, so "/<tab>/evil.com" would become "//evil.com": refuse any control character,
    // in the raw value or after one round of percent-decoding, and anything that could read as a host after the first slash.
    $decoded = rawurldecode($next);
    foreach ([$next, $decoded] as $v) {
        if ($v === '' || $v[0] !== '/' || preg_match('/[\x00-\x20\x7f\\\\]/', $v) || strpos($v, '//') === 0) {
            return $fallback;
        }
    }
    return $next;
}

function json_out($data, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $data = host_labels_swap_json($data);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

function json_ok(array $data = []): void
{
    json_out(['ok' => true] + $data);
}

function json_fail(string $message, int $status = 400, array $extra = []): void
{
    json_out(['ok' => false, 'error' => $message] + $extra, $status);
}

class HttpError extends RuntimeException
{
}

/** Stop with an error page (or JSON error for API calls). */
function abort(int $code = 404, string $message = ''): void
{
    throw new HttpError($message, $code);
}

// ---------------------------------------------------------------------------
// Flash messages & old input
// ---------------------------------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function set_old(array $data): void
{
    unset($data['password'], $data['password_confirm'], $data['_csrf']);
    $_SESSION['_old'] = $data;
}

function old(string $key, $default = '')
{
    static $old = null;
    if ($old === null) {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
    }
    $v = $old[$key] ?? $default;
    return is_array($v) ? $v : (string)$v;
}

function errors_set(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function errors_get(): array
{
    static $e = null;
    if ($e === null) {
        $e = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
    }
    return $e;
}

function field_error(string $key): string
{
    $e = errors_get();
    return isset($e[$key]) ? (string)$e[$key] : '';
}

// ---------------------------------------------------------------------------
// Strings
// ---------------------------------------------------------------------------

function slugify(string $text, int $max = 80): string
{
    $text = function_exists('iconv') ? (@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text) : $text;
    $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text) ?? '');
    $text = trim($text, '-');
    return $text === '' ? 'item' : substr($text, 0, $max);
}

/** Make a slug unique in $table.$column by appending -2, -3… */
function unique_slug(string $table, string $column, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $i = 1;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM ' . db_ident($table) . ' WHERE ' . db_ident($column) . ' = ?';
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        if ((int)val($sql, $params) === 0) {
            return $slug;
        }
        $i++;
        $slug = $base . '-' . $i;
    }
}

function str_limit(string $s, int $max = 100, string $end = '…'): string
{
    $s = trim(preg_replace('/\s+/', ' ', $s) ?? '');
    return mb_strlen($s) > $max ? rtrim(mb_substr($s, 0, $max - 1)) . $end : $s;
}

function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : '?';
}

function plural(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

function number_short(int $n): string
{
    if ($n >= 1000000) {
        return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
    }
    if ($n >= 1000) {
        return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k';
    }
    return (string)$n;
}

function valid_email(string $email): bool
{
    return strlen($email) <= 190 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Normalise a phone number to digits with country code (e.g. 0712345678 → 254712345678). Returns '' if invalid. */
function phone_normalize(string $phone): string
{
    $prefix = preg_replace('/\D/', '', (string)setting('phone_prefix')) ?: '254';
    $d = preg_replace('/\D/', '', $phone) ?? '';
    if ($d === '') {
        return '';
    }
    if (strpos($d, '00') === 0) {
        $d = substr($d, 2);
    } elseif ($d[0] === '0') {
        $d = $prefix . substr($d, 1);
    } elseif (strpos($d, $prefix) !== 0 && strlen($d) <= 9) {
        $d = $prefix . $d;
    }
    return strlen($d) >= 10 && strlen($d) <= 15 ? $d : '';
}

function mask_phone(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone) ?? '';
    return strlen($d) > 6 ? substr($d, 0, 4) . str_repeat('•', max(3, strlen($d) - 7)) . substr($d, -3) : '••••••';
}

function mask_email(string $email): string
{
    [$u, $d] = array_pad(explode('@', $email, 2), 2, '');
    return ($u !== '' ? mb_substr($u, 0, 1) : '') . '•••@' . $d;
}

// ---------------------------------------------------------------------------
// Dates, numbers, money
// ---------------------------------------------------------------------------

function now(): string
{
    return date('Y-m-d H:i:s');
}

function age_from_dob(?string $dob): ?int
{
    if (!$dob || $dob === '0000-00-00') {
        return null;
    }
    try {
        $diff = (new DateTime($dob))->diff(new DateTime('today'));
        return $diff->invert ? null : $diff->y; // a date in the future is not a birth date
    } catch (Throwable $e) {
        return null;
    }
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if (!$ts) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 45) {
        return 'just now';
    }
    if ($diff < 3600) {
        $m = max(1, (int)round($diff / 60));
        return $m . ' min ago';
    }
    if ($diff < 86400) {
        $h = (int)round($diff / 3600);
        return $h . ($h === 1 ? ' hour ago' : ' hours ago');
    }
    if ($diff < 86400 * 7) {
        $d = (int)round($diff / 86400);
        return $d . ($d === 1 ? ' day ago' : ' days ago');
    }
    return date(date('Y', $ts) === date('Y') ? 'j M' : 'j M Y', $ts);
}

function fmt_date(?string $d, string $format = 'j M Y'): string
{
    $ts = $d ? strtotime($d) : false;
    return $ts ? date($format, $ts) : '';
}

function fmt_datetime(?string $d): string
{
    return fmt_date($d, 'j M Y, g:i a');
}

function money($amount, bool $symbol = true): string
{
    $n = (float)$amount;
    $dec = abs($n - round($n)) < 0.005 ? 0 : 2;
    $s = number_format($n, $dec);
    return $symbol ? trim((string)setting('currency_symbol') . ' ' . $s) : $s;
}

function file_size_h(int $bytes): string
{
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float)$bytes;
    while ($n >= 1024 && $i < 3) {
        $n /= 1024;
        $i++;
    }
    return round($n, $i ? 1 : 0) . ' ' . $u[$i];
}

function ini_bytes(string $v): int
{
    $v = trim($v);
    $n = (int)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        // no break
        case 'm': $n *= 1024;
        // no break
        case 'k': $n *= 1024;
    }
    return $n;
}

function paginate(int $total, int $perPage, int $page): array
{
    $perPage = max(1, $perPage);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return [
        'total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => $pages,
        'offset' => ($page - 1) * $perPage, 'has_prev' => $page > 1, 'has_next' => $page < $pages,
    ];
}

/** Current page URL with modified query parameters (for pagination and filters). */
function url_with(array $changes): string
{
    $q = array_merge($_GET, $changes);
    foreach ($q as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        }
    }
    return url(request_path(), $q);
}

function haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $r = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $r * 2 * asin(min(1, sqrt($a)));
}

// ---------------------------------------------------------------------------
// File cache (storage/cache)
// ---------------------------------------------------------------------------

function cache_path(string $key): string
{
    return ROOT . '/storage/cache/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) . '.cache';
}

function cache_get(string $key, $default = null)
{
    $f = cache_path($key);
    if (!is_file($f)) {
        return $default;
    }
    $raw = @file_get_contents($f);
    $d = $raw === false ? null : @unserialize($raw, ['allowed_classes' => false]);
    if (!is_array($d) || ($d['exp'] ?? 0) < time()) {
        @unlink($f);
        return $default;
    }
    return $d['val'];
}

function cache_put(string $key, $value, int $ttl = 300): void
{
    $dir = ROOT . '/storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents(cache_path($key), serialize(['exp' => time() + $ttl, 'val' => $value]), LOCK_EX);
}

function cache_remember(string $key, int $ttl, callable $fn)
{
    $v = cache_get($key, "\0miss");
    if ($v === "\0miss") {
        $v = $fn();
        cache_put($key, $v, $ttl);
    }
    return $v;
}

function cache_forget(string $key): void
{
    @unlink(cache_path($key));
}

function cache_clear(string $prefix = ''): int
{
    $n = 0;
    foreach (glob(ROOT . '/storage/cache/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $prefix) . '*.cache') ?: [] as $f) {
        if (@unlink($f)) {
            $n++;
        }
    }
    return $n;
}

// ---------------------------------------------------------------------------
// Logging
// ---------------------------------------------------------------------------

function app_log(string $channel, string $message, array $context = []): void
{
    $dir = ROOT . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . ($context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : '') . "\n";
    @file_put_contents($dir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $channel) . '.log', $line, FILE_APPEND | LOCK_EX);
}

/** Record an admin action in admin_activity_log. */
function admin_log(string $action, string $entity = '', ?int $entityId = null, string $details = ''): void
{
    try {
        $u = $_SESSION['uid'] ?? null;
        insert('admin_activity_log', [
            'admin_id' => $u,
            'action' => mb_substr($action, 0, 80),
            'entity_type' => mb_substr($entity, 0, 40) ?: null,
            'entity_id' => $entityId,
            'details' => $details !== '' ? mb_substr($details, 0, 1000) : null,
            'ip_address' => client_ip(),
        ]);
    } catch (Throwable $e) {
        app_log('error', 'admin_log failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Icons
// ---------------------------------------------------------------------------

/**
 * The picture for an empty page ("No matches yet", "No messages"...). $kind picks the illustration (likes, matches, messages,
 * notifications, bookings, search, photos, generic); $fallbackIcon is shown instead when the admin turned illustrations off
 * in Settings > Appearance.
 */
function empty_art(string $kind = 'generic', string $fallbackIcon = 'sparkles'): string
{
    $kind = preg_replace('/[^a-z]/', '', $kind) ?: 'generic';
    if (!is_file(ROOT . '/assets/images/art/empty-' . $kind . '.svg')) {
        $kind = 'generic';
    }
    if (!setting('empty_art')) {
        return '<span class="empty-icon">' . icon($fallbackIcon, 'w-8 h-8') . '</span>';
    }
    return '<img class="empty-art" src="' . e(asset('images/art/empty-' . $kind . '.svg')) . '" alt="" width="240" height="180" loading="lazy" decoding="async">';
}

/** Inline SVG icon from the generated sprite (assets/icons/sprite.svg). */
function icon(string $name, string $class = 'w-5 h-5', string $label = ''): string
{
    $name = preg_replace('/[^a-z0-9-]/', '', strtolower($name));
    $aria = $label !== '' ? ' role="img" aria-label="' . e($label) . '"' : ' aria-hidden="true"';
    return '<svg class="icon ' . e($class) . '"' . $aria . '><use href="' . e(asset('icons/sprite.svg')) . '#i-' . $name . '"></use></svg>';
}
