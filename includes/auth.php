<?php
/**
 * Authentication: sessions, login, remember-me, registration, tokens, 2FA, roles.
 */

declare(strict_types=1);

const REMEMBER_COOKIE = 'chrm';

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $lifetime = max(1, (int)setting('session_hours')) * 3600;
    $dir = ROOT . '/storage/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        session_save_path($dir);
    }
    session_name('chsid');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)max($lifetime, 86400));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/' ?: '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    session_follow_rotation();
    // Sliding expiry independent of the cookie lifetime.
    $last = (int)($_SESSION['_last'] ?? 0);
    if ($last && time() - $last > $lifetime && !empty($_SESSION['uid'])) {
        unset($_SESSION['uid'], $_SESSION['pending_2fa'], $_SESSION['auth_at']);
    }
    $_SESSION['_last'] = time();
}

/**
 * Move the session data to a new session id (after sign-in or a password change) so an old id cannot be reused.
 *
 * session_regenerate_id(true) deletes the old session at once. A request that was already on its way with the old cookie
 * (a background refresh of the badges, a double click) then starts an empty session and its Set-Cookie replaces the new
 * cookie, which signs the person out. So the old id is kept for a few seconds as a bare pointer to the new one, holding no data.
 */
function session_rotate(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $new = session_create_id();
    if (!is_string($new) || $new === '') {
        session_regenerate_id(true);
        return;
    }
    $data = $_SESSION;
    $_SESSION = ['_rotated_to' => $new, '_rotated_at' => time()];
    session_write_close();
    ini_set('session.use_strict_mode', '0');
    session_id($new);
    session_start(); // strict mode stays off for the rest of this request (ini changes are per request, and PHP refuses them while a session is open)
    $_SESSION = $data;
}

/** A request that arrives with a session id that was rotated moments ago continues in the new session (see session_rotate). */
function session_follow_rotation(): void
{
    for ($hop = 0; $hop < 3 && isset($_SESSION['_rotated_to']); $hop++) {
        $to = (string)$_SESSION['_rotated_to'];
        $fresh = time() - (int)($_SESSION['_rotated_at'] ?? 0) <= 20;
        if (!$fresh || !preg_match('/^[A-Za-z0-9,-]{16,256}$/', $to)) {
            $_SESSION = []; // an expired pointer: whoever holds this id is signed out
            return;
        }
        session_write_close();
        ini_set('session.use_strict_mode', '0');
        session_id($to);
        session_start();
    }
    if (isset($_SESSION['_rotated_to'])) {
        $_SESSION = [];
    }
}

// ---------------------------------------------------------------------------
// Current user
// ---------------------------------------------------------------------------

/** Signed-in user (users + profile + avatar + tier) or null. Cached per request. */
function current_user(bool $reload = false): ?array
{
    static $cached = false;
    static $user = null;
    if ($reload) {
        $cached = false;
    }
    if ($cached) {
        return $user;
    }
    $cached = true;
    $user = null;

    if (empty($_SESSION['uid'])) {
        remember_login_from_cookie();
    }
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid <= 0) {
        return null;
    }
    if (!empty($_COOKIE[REMEMBER_COOKIE]) && time() - (int)($_SESSION['_rm'] ?? 0) > 86400) {
        $_SESSION['_rm'] = time();
        remember_refresh();
    }
    $u = row(
        'SELECT u.id, u.email, u.phone, u.role, u.status, u.status_reason, u.admin_role_id, u.email_verified_at,
                u.must_change_password, u.totp_enabled, u.featured_until, u.created_at, u.last_login_at,
                p.display_name, p.date_of_birth, p.gender, p.interested_in, p.profile_completion, p.is_verified,
                p.onboarding_completed, p.onboarding_step, p.theme_pref, p.is_paused, p.is_incognito,
                (SELECT ph.thumb_path FROM user_photos ph WHERE ph.user_id = u.id AND ph.is_primary = 1 AND ph.moderation_status <> \'rejected\' LIMIT 1) AS avatar_thumb,
                (SELECT ph.medium_path FROM user_photos ph WHERE ph.user_id = u.id AND ph.is_primary = 1 AND ph.moderation_status <> \'rejected\' LIMIT 1) AS avatar_medium
         FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
         WHERE u.id = :id',
        ['id' => $uid]
    );
    if (!$u || in_array($u['status'], ['banned', 'suspended'], true)) {
        logout_user(false);
        return null;
    }
    $u['id'] = (int)$u['id'];
    $u['age'] = age_from_dob($u['date_of_birth']);
    $u['name'] = $u['display_name'] !== null && $u['display_name'] !== '' ? $u['display_name'] : explode('@', $u['email'])[0];
    $u['first_name'] = explode(' ', trim($u['name']))[0];
    $u['tier'] = $u['role'] === 'admin' ? 'vip' : user_tier($u['id']);
    $u['is_admin'] = $u['role'] === 'admin';
    $u['is_host'] = $u['role'] === 'host';

    // Record activity at most once a minute.
    if (time() - (int)($_SESSION['_active_at'] ?? 0) > 60) {
        $_SESSION['_active_at'] = time();
        try {
            q('UPDATE users SET last_active_at = NOW() WHERE id = :id', ['id' => $u['id']]);
        } catch (Throwable $e) {
        }
    }
    return $user = $u;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function auth_id(): int
{
    $u = current_user();
    return $u ? (int)$u['id'] : 0;
}

function is_admin_user(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function home_for_role(string $role): string
{
    return $role === 'admin' ? '/admin' : ($role === 'host' ? '/host/dashboard' : '/discover');
}

// ---------------------------------------------------------------------------
// Login / logout
// ---------------------------------------------------------------------------

function admin_ip_allowed(): bool
{
    $list = setting_lines('admin_ip_whitelist');
    if (!$list) {
        return true;
    }
    return in_array(client_ip(), $list, true);
}

/**
 * Check credentials. Does NOT start the session unless $finish is true.
 * @return array ['ok'=>bool, 'error'=>string, 'user_id'=>int, 'needs_2fa'=>bool]
 */
function attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    $fail = fn(string $m) => ['ok' => false, 'error' => $m, 'user_id' => 0, 'needs_2fa' => false];

    if (!rate_limit('login-ip:' . client_ip(), 30, 900)) {
        return $fail('Too many sign-in attempts. Please wait 15 minutes and try again.');
    }
    if ($email === '' || $password === '') {
        return $fail('Enter your email and password.');
    }
    if (rate_limited('login-email:' . $email, 10, 900)) {
        return $fail('Too many attempts for this account. Please wait 15 minutes or reset your password.');
    }

    $u = row('SELECT * FROM users WHERE email = :e', ['e' => $email]);
    $dummy = '$2y$12$abcdefghijklmnopqrstuuA1G0b0Q3q2o3g1xk1g4cE0yq6s3m1pK';
    $hash = $u['password_hash'] ?? $dummy;
    $valid = password_verify($password, $hash);

    // While an account is locked, no guess succeeds and none is counted (a wrong guess must not clear the lock).
    $lockedUntil = $u && $u['locked_until'] ? (int)strtotime((string)$u['locked_until']) : 0;
    if ($u && $lockedUntil > time()) {
        $mins = max(1, (int)ceil(($lockedUntil - time()) / 60));
        return $fail('This account is locked for ' . $mins . ' more ' . ($mins === 1 ? 'minute' : 'minutes') . '. You can also reset your password.');
    }
    if (!$u || !$valid) {
        rate_limit('login-email:' . $email, 10, 900);
        if ($u) {
            $attempts = (int)$u['login_attempts'] + 1;
            $max = max(3, (int)setting('max_login_attempts'));
            $lock = $attempts >= $max ? date('Y-m-d H:i:s', time() + max(1, (int)setting('lockout_minutes')) * 60) : null;
            q('UPDATE users SET login_attempts = :a, locked_until = :l WHERE id = :id', ['a' => $lock ? 0 : $attempts, 'l' => $lock, 'id' => $u['id']]);
        }
        return $fail('That email and password do not match.');
    }
    if ($u['status'] === 'banned') {
        return $fail('This account has been closed. Contact support if you think this is a mistake.');
    }
    if ($u['status'] === 'suspended') {
        return $fail('This account is suspended' . ($u['status_reason'] ? ': ' . $u['status_reason'] : '') . '. Contact support to appeal.');
    }
    if ($u['role'] === 'admin' && !admin_ip_allowed()) {
        return $fail('Admin sign-in is not allowed from this address.');
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
        q('UPDATE users SET password_hash = :h WHERE id = :id', ['h' => hash_password($password), 'id' => $u['id']]);
    }
    $needs2fa = (int)$u['totp_enabled'] === 1 && $u['totp_secret'];
    if (!$needs2fa && $u['role'] === 'admin' && setting('admin_require_2fa')) {
        // Admins must enrol: the login flow lets them in but forces the setup page.
        $_SESSION['must_setup_2fa'] = true;
    }
    return ['ok' => true, 'error' => '', 'user_id' => (int)$u['id'], 'needs_2fa' => $needs2fa];
}

function login_user(int $userId, bool $remember = false): void
{
    session_rotate();
    $_SESSION['uid'] = $userId;
    $_SESSION['auth_at'] = time();
    $_SESSION['_csrf'] = bin2hex(random_bytes(24));
    unset($_SESSION['pending_2fa']);
    $u = row('SELECT status FROM users WHERE id = :id', ['id' => $userId]);
    q('UPDATE users SET last_login_at = NOW(), last_active_at = NOW(), last_ip = :ip, login_attempts = 0, locked_until = NULL,
        status = IF(status = \'deactivated\', \'active\', status) WHERE id = :id', ['ip' => client_ip(), 'id' => $userId]);
    if ($u && $u['status'] === 'deactivated') {
        q('UPDATE user_profiles SET is_paused = 0 WHERE user_id = :id', ['id' => $userId]);
    }
    if ($remember) {
        remember_issue($userId);
    }
    current_user(true);
}

function logout_user(bool $clearCookie = true): void
{
    if ($clearCookie) {
        remember_forget_current();
    }
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    current_user(true);
}

// Remember me -----------------------------------------------------------------

function remember_issue(int $userId): void
{
    $days = max(1, (int)setting('remember_me_days'));
    $selector = random_token(8);
    $validator = random_token(32);
    q('DELETE FROM user_tokens WHERE user_id = :u AND type = \'remember\' AND expires_at < NOW()', ['u' => $userId]);
    insert('user_tokens', [
        'user_id' => $userId, 'type' => 'remember', 'selector' => $selector,
        'token_hash' => hash('sha256', $validator), 'expires_at' => date('Y-m-d H:i:s', time() + $days * 86400),
    ]);
    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires' => time() + $days * 86400, 'path' => base_path() . '/' ?: '/', 'secure' => is_https(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function remember_login_from_cookie(): void
{
    $c = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!is_string($c) || strpos($c, ':') === false) {
        return;
    }
    [$selector, $validator] = explode(':', $c, 2);
    if (!preg_match('/^[a-f0-9]{16}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
        return;
    }
    $t = row('SELECT * FROM user_tokens WHERE selector = :s AND type = \'remember\' AND expires_at > NOW()', ['s' => $selector]);
    if (!$t || !hash_equals($t['token_hash'], hash('sha256', $validator))) {
        if ($t) { // wrong validator for a real selector: treat as theft
            delete_rows('user_tokens', ['user_id' => $t['user_id'], 'type' => 'remember']);
        }
        setcookie(REMEMBER_COOKIE, '', time() - 3600, base_path() . '/' ?: '/');
        return;
    }
    $u = row('SELECT id, status, role FROM users WHERE id = :id', ['id' => $t['user_id']]);
    if (!$u || in_array($u['status'], ['banned', 'suspended'], true)) {
        return;
    }
    if ($u['role'] === 'admin') {
        return; // admins always sign in explicitly
    }
    delete_rows('user_tokens', ['id' => $t['id']]);
    login_user((int)$u['id'], true);
}

/**
 * Keep an active member signed in: while they keep using the site, the "stay signed in" token is pushed
 * further out instead of quietly running out one day. Checked at most once a day per session, and only
 * once the token is past half its life, so it costs almost nothing.
 */
function remember_refresh(): void
{
    $c = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (!is_string($c) || strpos($c, ':') === false || headers_sent()) {
        return;
    }
    [$selector] = explode(':', $c, 2);
    if (!preg_match('/^[a-f0-9]{16}$/', $selector)) {
        return;
    }
    $days = max(1, (int)setting('remember_me_days'));
    $t = row('SELECT id, expires_at FROM user_tokens WHERE selector = :s AND type = \'remember\' AND expires_at > NOW()', ['s' => $selector]);
    if (!$t || strtotime((string)$t['expires_at']) > time() + (int)($days * 86400 / 2)) {
        return;
    }
    $until = time() + $days * 86400;
    update('user_tokens', ['expires_at' => date('Y-m-d H:i:s', $until)], ['id' => (int)$t['id']]);
    setcookie(REMEMBER_COOKIE, $c, [
        'expires' => $until, 'path' => base_path() . '/' ?: '/', 'secure' => is_https(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function remember_forget_current(): void
{
    $c = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if (is_string($c) && strpos($c, ':') !== false) {
        delete_rows('user_tokens', ['selector' => explode(':', $c, 2)[0]]);
    }
    setcookie(REMEMBER_COOKIE, '', time() - 3600, base_path() . '/' ?: '/');
}

// ---------------------------------------------------------------------------
// One-time tokens (password reset, e-mail verification)
// ---------------------------------------------------------------------------

/** Returns "selector.validator" to put in an emailed link. */
function token_create(int $userId, string $type, int $ttlMinutes = 60): string
{
    delete_rows('user_tokens', ['user_id' => $userId, 'type' => $type]);
    $selector = random_token(8);
    $validator = random_token(24);
    insert('user_tokens', [
        'user_id' => $userId, 'type' => $type, 'selector' => $selector,
        'token_hash' => hash('sha256', $validator), 'expires_at' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
    ]);
    return $selector . '.' . $validator;
}

/** Look at a token without using it. Returns the user id or null. */
function token_peek(string $type, string $token): ?int
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{16}$/', $parts[0]) || !preg_match('/^[a-f0-9]{48}$/', $parts[1])) {
        return null;
    }
    $t = row('SELECT * FROM user_tokens WHERE selector = :s AND type = :t AND used_at IS NULL AND expires_at > NOW()', ['s' => $parts[0], 't' => $type]);
    if (!$t || !hash_equals($t['token_hash'], hash('sha256', $parts[1]))) {
        return null;
    }
    return (int)$t['user_id'];
}

/** Use a token (single use). Returns the user id or null. */
function token_consume(string $type, string $token): ?int
{
    $uid = token_peek($type, $token);
    if ($uid === null) {
        return null;
    }
    q('UPDATE user_tokens SET used_at = NOW() WHERE selector = :s', ['s' => explode('.', $token, 2)[0]]);
    return $uid;
}

// ---------------------------------------------------------------------------
// Route guards
// ---------------------------------------------------------------------------

/** Require a signed-in user (any role). Redirects to login, or 401 for API calls. */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (wants_json()) {
            json_fail('Please sign in to continue.', 401, ['login' => url('/login')]);
        }
        $_SESSION['_next'] = request_method() === 'GET' ? current_url() : null;
        flash('info', 'Sign in to continue.');
        redirect('/login?next=' . rawurlencode(request_path()));
    }
    if ($u['must_change_password'] && request_path() !== '/account/password' && strpos(request_path(), '/api/') !== 0 && request_path() !== '/logout') {
        flash('warning', 'Please choose a new password to continue.');
        redirect('/account/password');
    }
    return $u;
}

/** Require one role; users of another role are sent to their own home. */
function require_role(string $role): array
{
    $u = require_login();
    if ($u['role'] !== $role) {
        if (wants_json()) {
            json_fail('You do not have access to this.', 403);
        }
        redirect(home_for_role($u['role']));
    }
    return $u;
}

function require_user(): array
{
    return require_role('user');
}

function require_host(): array
{
    return require_role('host');
}

/** Explorer or host (anyone who is not an admin). */
function require_member(): array
{
    $u = require_login();
    if ($u['role'] === 'admin') {
        if (wants_json()) {
            json_fail('Not available for admin accounts.', 403);
        }
        redirect('/admin');
    }
    return $u;
}

/**
 * Admin guard with optional module permission.
 * Levels: none < view < edit < full.
 */
function require_admin(string $module = '', string $level = 'view'): array
{
    $u = current_user();
    if (!$u) {
        if (wants_json()) {
            json_fail('Please sign in.', 401);
        }
        redirect('/login?next=' . rawurlencode(request_path()));
    }
    if ($u['role'] !== 'admin' || !admin_ip_allowed()) {
        throw new HttpError('Not found', 404);
    }
    if ($u['must_change_password'] && !in_array(request_path(), ['/admin/account', '/account/password', '/logout'], true)) {
        if (wants_json()) {
            json_fail('Choose a new password before you continue.', 403, ['redirect' => url('/admin/account')]);
        }
        flash('warning', 'Choose a new password before you continue.');
        redirect('/admin/account');
    }
    if ((!empty($_SESSION['must_setup_2fa']) || setting('admin_require_2fa')) && !$u['totp_enabled'] && request_path() !== '/account/2fa' && request_path() !== '/logout') {
        flash('warning', 'Two-step sign-in is required for admins. Set it up to continue.');
        redirect('/account/2fa');
    }
    if ($module !== '' && !admin_can($module, $level)) {
        throw new HttpError('You do not have permission to ' . ($level === 'view' ? 'view' : 'change') . ' this area.', 403);
    }
    return $u;
}

function admin_permissions(): array
{
    static $perms = null;
    if ($perms !== null) {
        return $perms;
    }
    $u = current_user();
    $perms = [];
    if (!$u || $u['role'] !== 'admin') {
        return $perms;
    }
    if (empty($u['admin_role_id'])) {
        return $perms = ['*' => 'full'];
    }
    $json = (string)val('SELECT permissions FROM admin_roles WHERE id = :id', ['id' => $u['admin_role_id']], '{}');
    $d = json_decode($json, true);
    return $perms = is_array($d) ? $d : [];
}

function admin_can(string $module, string $level = 'view'): bool
{
    $rank = ['none' => 0, 'view' => 1, 'edit' => 2, 'full' => 3];
    $p = admin_permissions();
    $have = $p[$module] ?? ($p['*'] ?? 'none');
    return ($rank[$have] ?? 0) >= ($rank[$level] ?? 1);
}

// ---------------------------------------------------------------------------
// Registration
// ---------------------------------------------------------------------------

/**
 * Create an account with its profile rows. Input is trusted (validate first).
 * Keys: email, password, role (user|host), display_name, date_of_birth, gender,
 *       interested_in, phone, county_id, town_name
 * Returns the new user id.
 */
function create_account(array $d): int
{
    return db_transaction(function () use ($d) {
        $role = ($d['role'] ?? 'user') === 'host' ? 'host' : 'user';
        $needsVerify = (bool)setting('email_verification_required');
        $uid = insert('users', [
            'email' => strtolower(trim($d['email'])),
            'phone' => $d['phone'] ?? null,
            'password_hash' => hash_password($d['password']),
            'role' => $role,
            'status' => 'active',
            'email_verified_at' => $needsVerify ? null : now(),
            'last_ip' => client_ip(),
        ]);
        insert('user_profiles', [
            'user_id' => $uid,
            'display_name' => mb_substr(trim($d['display_name'] ?? ''), 0, 100),
            'date_of_birth' => $d['date_of_birth'] ?? null,
            'gender' => $d['gender'] ?? null,
            'interested_in' => $d['interested_in'] ?? 'everyone',
            'show_contact' => 0,
        ]);
        q('INSERT IGNORE INTO discovery_preferences (user_id, age_min, age_max, distance_km) VALUES (:u, 18, 45, :d)',
            ['u' => $uid, 'd' => (int)setting('radius_default')]);
        q('INSERT IGNORE INTO notification_preferences (user_id) VALUES (:u)', ['u' => $uid]);
        $welcome = $role === 'user' && function_exists('dm_add_credits') ? (int)setting('dm_welcome_credits') : 0;
        if ($welcome > 0 && dm_enabled()) {
            dm_add_credits($uid, $welcome, 'welcome', null, 'Welcome gift');
        }
        if (!empty($d['county_id'])) {
            set_user_location_from_location($uid, (int)$d['county_id'], $d['town_name'] ?? null);
        }
        if ($role === 'host') {
            insert('host_profiles', [
                'user_id' => $uid,
                'display_name' => mb_substr(trim($d['display_name'] ?? ''), 0, 100),
                'approval_status' => 'draft',
            ]);
        }
        return $uid;
    });
}

/** Store a user's location from a county/town row (no GPS). */
function set_user_location_from_location(int $userId, int $locationId, ?string $townName = null): void
{
    $loc = row('SELECT * FROM locations WHERE id = :id AND is_active = 1', ['id' => $locationId]);
    if (!$loc) {
        return;
    }
    $county = $loc['type'] === 'county' ? $loc : row('SELECT * FROM locations WHERE id = :id', ['id' => $loc['parent_id']]);
    q('INSERT INTO user_locations (user_id, latitude, longitude, location_id, county_id, town_name, county_name, country, source)
       VALUES (:u, :la, :lo, :lid, :cid, :tn, :cn, :co, \'manual\')
       ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), location_id = VALUES(location_id),
         county_id = VALUES(county_id), town_name = VALUES(town_name), county_name = VALUES(county_name), source = \'manual\'',
        [
            'u' => $userId, 'la' => $loc['latitude'], 'lo' => $loc['longitude'], 'lid' => $loc['id'],
            'cid' => $county['id'] ?? null,
            'tn' => $loc['type'] === 'town' ? $loc['name'] : ($townName ?: null),
            'cn' => $county['name'] ?? $loc['name'], 'co' => (string)setting('site_country'),
        ]);
}

// ---------------------------------------------------------------------------
// TOTP (RFC 6238)
// ---------------------------------------------------------------------------

function base32_encode(string $bin): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($bin) as $c) {
        $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        $out .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
    }
    return $out;
}

function base32_decode(string $s): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s) ?? '');
    $bits = '';
    foreach (str_split($s) as $c) {
        $bits .= str_pad(decbin((int)strpos($alphabet, $c)), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr(bindec($byte));
        }
    }
    return $out;
}

function totp_generate_secret(): string
{
    return base32_encode(random_bytes(20));
}

function totp_code(string $secret, ?int $timeSlice = null): string
{
    $timeSlice = $timeSlice ?? (int)floor(time() / 30);
    $key = base32_decode($secret);
    $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $timeSlice), $key, true);
    $offset = ord($hash[19]) & 0x0F;
    $value = ((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16)
        | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF);
    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function totp_verify(string $secret, string $code, int $window = 1): bool
{
    $code = preg_replace('/\D/', '', $code) ?? '';
    if (strlen($code) !== 6) {
        return false;
    }
    $slice = (int)floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($secret, $slice + $i), $code)) {
            return true;
        }
    }
    return false;
}

function totp_uri(string $secret, string $email): string
{
    $issuer = rawurlencode(site_name());
    return 'otpauth://totp/' . $issuer . ':' . rawurlencode($email) . '?secret=' . $secret . '&issuer=' . $issuer . '&digits=6&period=30';
}
