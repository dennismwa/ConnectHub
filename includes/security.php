<?php
/**
 * Security: CSP nonce + headers, CSRF, rate limiting, HTML sanitising, password rules.
 */

declare(strict_types=1);

function csp_nonce(): string
{
    static $n = null;
    if ($n === null) {
        $n = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }
    return $n;
}

function send_security_headers(bool $isAdminArea = false): void
{
    if (headers_sent()) {
        return;
    }
    $n = csp_nonce();
    $ga = '';
    $gaId = function_exists('setting') ? (string)setting('google_analytics_id') . (string)setting('seo_gtm_id') : '';
    if ($gaId !== '') {
        $ga = ' https://www.googletagmanager.com https://www.google-analytics.com';
    }
    // Which sites may show this one in a frame: itself, plus any address the admin lists (Settings > General > Connection & security).
    $ancestors = "'self'";
    if (function_exists('setting')) {
        foreach (setting_lines('frame_allow_sites') as $site) {
            if (preg_match('#^https?://[A-Za-z0-9.\-]+(:\d+)?$#', rtrim($site, '/'))) {
                $ancestors .= ' ' . rtrim($site, '/');
            }
        }
    }
    $csp = "default-src 'self'; "
        . "script-src 'self' 'nonce-$n'$ga; "
        . "style-src 'self' 'nonce-$n'; "
        . "style-src-attr 'unsafe-inline'; "
        . "img-src 'self' data: blob: https:; "
        . "media-src 'self' blob:; "
        . "font-src 'self'; "
        . "connect-src 'self'$ga https://www.google-analytics.com; "
        . "worker-src 'self'; manifest-src 'self'; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors " . $ancestors;
    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    if ($ancestors === "'self'") {
        header('X-Frame-Options: SAMEORIGIN');
    }
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header_remove('X-Powered-By');
    if (is_https() && function_exists('setting') && setting('hsts_enabled')) {
        header('Strict-Transport-Security: max-age=' . (max(1, min(730, (int)setting('hsts_days'))) * 86400));
    }
    if ($isAdminArea) {
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
    }
}

// ---------------------------------------------------------------------------
// CSRF
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sent === '' || empty($_SESSION['_csrf'])) {
        $sent = $sent ?: (json_body()['_csrf'] ?? '');
    }
    return is_string($sent) && $sent !== '' && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $sent);
}

/** Abort the request with 419 when the CSRF token is missing or wrong. */
function csrf_require(): void
{
    if (!csrf_verify()) {
        throw new HttpError('Your session expired. Reload the page and try again.', 419);
    }
}

// ---------------------------------------------------------------------------
// Rate limiting (fixed window, stored in rate_limits)
// ---------------------------------------------------------------------------

/**
 * Count a hit and return true while the caller is still within the limit.
 * Example: if (!rate_limit('login:' . client_ip(), 10, 600)) { ...too many... }
 */
function rate_limit(string $bucket, int $max, int $windowSeconds): bool
{
    $key = substr(sha1($bucket), 0, 40);
    $window = (int)(floor(time() / $windowSeconds) * $windowSeconds);
    try {
        q('INSERT INTO rate_limits (bucket, window_start, hits) VALUES (:b, :w, 1)
           ON DUPLICATE KEY UPDATE hits = hits + 1', ['b' => $key, 'w' => $window]);
        $hits = (int)val('SELECT hits FROM rate_limits WHERE bucket = :b AND window_start = :w', ['b' => $key, 'w' => $window], 0);
        if (mt_rand(1, 200) === 1) {
            q('DELETE FROM rate_limits WHERE window_start < :t', ['t' => time() - 86400]);
        }
        return $hits <= $max;
    } catch (Throwable $e) {
        return true; // never block real users because of a limiter fault
    }
}

/** Check without counting. */
function rate_limited(string $bucket, int $max, int $windowSeconds): bool
{
    $key = substr(sha1($bucket), 0, 40);
    $window = (int)(floor(time() / $windowSeconds) * $windowSeconds);
    try {
        return (int)val('SELECT hits FROM rate_limits WHERE bucket = :b AND window_start = :w', ['b' => $key, 'w' => $window], 0) >= $max;
    } catch (Throwable $e) {
        return false;
    }
}

function require_rate_limit(string $bucket, int $max, int $windowSeconds, string $message = 'Too many attempts. Please wait a few minutes and try again.'): void
{
    if (!rate_limit($bucket, $max, $windowSeconds)) {
        throw new HttpError($message, 429);
    }
}

// ---------------------------------------------------------------------------
// Honeypot + timing for public forms
// ---------------------------------------------------------------------------

function honeypot_field(): string
{
    $_SESSION['_hp_t'] = time();
    return '<div class="hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden"><label>Leave this empty<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>';
}

function honeypot_passed(): bool
{
    if (!empty($_POST['website_url'])) {
        return false;
    }
    $t = (int)($_SESSION['_hp_t'] ?? 0);
    return !($t && time() - $t < 2);
}

// ---------------------------------------------------------------------------
// Passwords
// ---------------------------------------------------------------------------

function password_strength(string $p): int
{
    $score = 0;
    if (strlen($p) >= 8) {
        $score++;
    }
    if (strlen($p) >= 12) {
        $score++;
    }
    if (preg_match('/[a-z]/', $p) && preg_match('/[A-Z]/', $p)) {
        $score++;
    }
    if (preg_match('/\d/', $p) && preg_match('/[^A-Za-z0-9]/', $p)) {
        $score++;
    }
    return min(4, $score);
}

/** Strength bar under a password field. Hidden unless the admin turned it on (or $force, for administrator accounts). */
function password_meter_html(string $id, bool $force = false): string
{
    if (!$force && !(function_exists('setting') && setting('password_show_meter'))) {
        return '';
    }
    return '<div class="pw-meter" id="' . e($id) . '" data-s="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>';
}

/** Shortest password a member may use (Settings > Sign in & register). Administrators always need 8. */
function password_min_length(bool $strict = false): int
{
    $n = function_exists('setting') ? (int)setting('password_min_length') : 6;
    $n = max(4, min(32, $n ?: 6));
    return $strict ? max(8, $n) : $n;
}

/** Returns an error message or '' when the password is acceptable. $strict is used for administrator accounts. */
function password_problem(string $p, bool $strict = false): string
{
    $minLen = password_min_length($strict);
    if (mb_strlen($p) < $minLen) {
        return 'Use at least ' . $minLen . ' characters.';
    }
    if (strlen($p) > 200) {
        return 'That password is too long.';
    }
    $common = ['password', '12345678', '123456789', 'qwertyui', 'password1', 'iloveyou', '11111111', 'abcd1234', '123456', '1234567', '0000000', '000000', 'qwerty', 'qwerty123', 'abc123'];
    if (($strict || setting('password_block_common')) && in_array(strtolower($p), $common, true)) {
        return 'That password is too easy to guess.';
    }
    $rule = (string)setting('password_min_strength');
    $min = ['none' => 0, 'fair' => 1, 'good' => 2, 'strong' => 3][$rule] ?? 0;
    if ($strict) {
        $min = max($min, 1);
    }
    if ($min >= 1 && password_strength($p) < $min) {
        return $min >= 3 ? 'Make it stronger: use 12 or more characters with upper and lower case letters.' : 'Make it a little stronger: mix upper and lower case letters, or add a number and a symbol.';
    }
    return '';
}

function hash_password(string $p): string
{
    return password_hash($p, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ---------------------------------------------------------------------------
// HTML sanitising for admin-authored rich content
// ---------------------------------------------------------------------------

function sanitize_html(string $html): string
{
    if (trim($html) === '') {
        return '';
    }
    $allowed = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [],
        'a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'hr' => [], 'span' => [], 'div' => [], 'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => [], 'td' => [], 'code' => [], 'pre' => [], 'figure' => [], 'figcaption' => [],
    ];
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $dom->getElementById('__root');
    if (!$root) {
        return e(strip_tags($html));
    }
    $clean = function (DOMNode $node) use (&$clean, $allowed) {
        $children = [];
        foreach ($node->childNodes as $c) {
            $children[] = $c;
        }
        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!isset($allowed[$tag])) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'math', 'link', 'meta', 'base'], true)) {
                        $node->removeChild($child);
                        continue;
                    }
                    // unwrap unknown tags but keep their (cleaned) children
                    $clean($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                $remove = [];
                foreach ($child->attributes as $attr) {
                    $name = strtolower($attr->name);
                    if (!in_array($name, $allowed[$tag], true)) {
                        $remove[] = $attr->name;
                        continue;
                    }
                    if (in_array($name, ['href', 'src'], true)) {
                        $v = trim($attr->value);
                        $ok = preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $v) && !preg_match('#^\s*(javascript|data|vbscript):#i', $v);
                        if (!$ok) {
                            $remove[] = $attr->name;
                        }
                    }
                }
                foreach ($remove as $r) {
                    $child->removeAttribute($r);
                }
                if ($tag === 'a') {
                    if ($child->getAttribute('target') === '_blank') {
                        $child->setAttribute('rel', 'noopener noreferrer');
                    } else {
                        $child->removeAttribute('target');
                    }
                }
                $clean($child);
            } elseif ($child instanceof DOMComment) {
                $node->removeChild($child);
            }
        }
    };
    $clean($root);
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $dom->saveHTML($c);
    }
    return $out;
}

/** Plain-text excerpt from rich content. */
function html_excerpt(string $html, int $len = 160): string
{
    return str_limit(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'), $len);
}

// ---------------------------------------------------------------------------
// Banned words
// ---------------------------------------------------------------------------

function contains_banned_word(string $text): ?string
{
    $words = setting_csv('banned_words');
    if (!$words) {
        return null;
    }
    foreach ($words as $w) {
        if ($w !== '' && preg_match('/(?<![\p{L}\p{N}])' . preg_quote($w, '/') . '(?![\p{L}\p{N}])/iu', $text)) {
            return $w;
        }
    }
    return null;
}

function mask_banned_words(string $text): string
{
    foreach (setting_csv('banned_words') as $w) {
        if ($w === '') {
            continue;
        }
        $text = preg_replace_callback('/(?<![\p{L}\p{N}])' . preg_quote($w, '/') . '(?![\p{L}\p{N}])/iu',
            fn($m) => str_repeat('•', mb_strlen($m[0])), $text) ?? $text;
    }
    return $text;
}
