<?php
/**
 * The passcode a member may put on their own account: four digits asked for when they open the app again.
 *
 * It is a second lock in front of an account that is already signed in, not a way of signing in. The code is
 * hashed the same way as a password and is never stored or logged in the clear. A member sets, changes or
 * removes it themselves under Settings > Privacy; nobody else can, and an admin cannot read it.
 *
 * Forgetting it never locks anyone out: the lock screen also takes the account password.
 *
 *   passcode_is_set()    does this member have one
 *   passcode_required()  must this request be unlocked before anything else happens
 *   passcode_unlock()    mark this session as unlocked
 */

declare(strict_types=1);

const PASSCODE_LENGTH = 4;
const PASSCODE_MAX_TRIES = 5;

/** The admin can switch the whole feature off for the site. */
function passcode_feature_on(): bool
{
    try {
        return (bool)setting('passcode_enabled');
    } catch (Throwable $e) {
        return false;
    }
}

/** Minutes of quiet before the app asks for the passcode again. 0 means only when it is opened fresh. */
function passcode_idle_minutes(): int
{
    return max(0, min(1440, (int)setting('passcode_idle_minutes')));
}

function passcode_is_set(int $uid): bool
{
    if ($uid <= 0) {
        return false;
    }
    try {
        return trim((string)val('SELECT passcode_hash FROM user_profiles WHERE user_id = :u', ['u' => $uid], '')) !== '';
    } catch (Throwable $e) {
        return false;
    }
}

/** Four digits, and not something anyone would guess first. */
function passcode_problem(string $code): ?string
{
    if (!preg_match('/^\d{' . PASSCODE_LENGTH . '}$/', $code)) {
        return 'Your passcode is ' . PASSCODE_LENGTH . ' numbers.';
    }
    if (preg_match('/^(\d)\1{' . (PASSCODE_LENGTH - 1) . '}$/', $code)) {
        return 'Pick something harder to guess than the same number four times.';
    }
    if (in_array($code, ['1234', '4321', '0123', '3210', '1111', '2580'], true)) {
        return 'That one is guessed too often. Pick another.';
    }
    return null;
}

/** Save a new passcode. Returns an error, or null when it is saved. */
function passcode_set(int $uid, string $code): ?string
{
    $problem = passcode_problem($code);
    if ($problem !== null) {
        return $problem;
    }
    update('user_profiles', ['passcode_hash' => password_hash($code, PASSWORD_DEFAULT), 'passcode_set_at' => now()], ['user_id' => $uid]);
    passcode_unlock();
    return null;
}

function passcode_clear(int $uid): void
{
    update('user_profiles', ['passcode_hash' => null, 'passcode_set_at' => null], ['user_id' => $uid]);
    unset($_SESSION['_pc_at'], $_SESSION['_pc_tries']);
}

function passcode_verify(int $uid, string $code): bool
{
    $hash = (string)val('SELECT passcode_hash FROM user_profiles WHERE user_id = :u', ['u' => $uid], '');
    return $hash !== '' && $code !== '' && password_verify($code, $hash);
}

/** How many wrong tries are left before the member is signed out. */
function passcode_tries_left(): int
{
    return max(0, PASSCODE_MAX_TRIES - (int)($_SESSION['_pc_tries'] ?? 0));
}

function passcode_wrong_try(): int
{
    $_SESSION['_pc_tries'] = (int)($_SESSION['_pc_tries'] ?? 0) + 1;
    return passcode_tries_left();
}

function passcode_unlock(): void
{
    $_SESSION['_pc_at'] = time();
    unset($_SESSION['_pc_tries']);
}

function passcode_lock_now(): void
{
    unset($_SESSION['_pc_at']);
}

/** Has this session been unlocked recently enough? */
function passcode_unlocked(): bool
{
    $at = (int)($_SESSION['_pc_at'] ?? 0);
    if ($at <= 0) {
        return false;
    }
    $idle = passcode_idle_minutes();
    return $idle === 0 || time() - $at <= $idle * 60;
}

/** Pages that stay open while the app is locked: the lock itself, signing out and the few things it needs. */
const PASSCODE_OPEN_PATHS = ['/lock', '/logout', '/api/passcode', '/manifest.webmanifest', '/offline', '/sitemap.xml', '/robots.txt'];

/** Must this request stop at the lock screen? */
function passcode_required(?array $viewer = null, ?string $path = null): bool
{
    if (!passcode_feature_on()) {
        return false;
    }
    $viewer = $viewer ?? current_user();
    if (!$viewer || !in_array($viewer['role'], ['user', 'host'], true)) {
        return false;
    }
    if (passcode_unlocked() || !passcode_is_set((int)$viewer['id'])) {
        return false;
    }
    $path = $path ?? request_path();
    foreach (PASSCODE_OPEN_PATHS as $open) {
        if ($path === $open || strpos($path, $open . '/') === 0) {
            return false;
        }
    }
    return true;
}

/**
 * Runs for every request once the member is known: a locked app goes to the lock screen, and anything
 * fetched in the background is told plainly instead of being handed a page of HTML.
 */
function passcode_guard(): void
{
    if (CH_CLI || !passcode_required()) {
        return;
    }
    $path = request_path();
    if (strpos($path, '/api/') === 0 || is_ajax()) {
        json_fail('Your app is locked. Enter your passcode to carry on.', 423, ['locked' => true, 'redirect' => url('/lock')]);
    }
    $next = in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true) ? $path : '';
    redirect('/lock' . ($next !== '' && $next !== '/lock' ? '?next=' . rawurlencode($next) : ''));
}

// ---------------------------------------------------------------------------
// The member's own settings page
// ---------------------------------------------------------------------------

/**
 * Handle the passcode form on a settings page. The account password is asked for every time, so a
 * borrowed phone that is already signed in cannot be used to lock the owner out of their own account.
 *
 * @return array{ok:bool, message:string, errors:array<string,string>}
 */
function passcode_handle_post(int $uid, array $in): array
{
    $action = (string)($in['action'] ?? '');
    $fail = fn(string $field, string $msg) => ['ok' => false, 'message' => $msg, 'errors' => [$field => $msg]];

    $hash = (string)val('SELECT password_hash FROM users WHERE id = :u', ['u' => $uid], '');
    $password = (string)($in['password'] ?? '');
    if ($hash === '' || $password === '' || !password_verify($password, $hash)) {
        return $fail('passcode_password', 'Your account password is not right.');
    }

    if ($action === 'passcode_remove') {
        passcode_clear($uid);
        return ['ok' => true, 'message' => 'Passcode removed. The app will not ask for it any more.', 'errors' => []];
    }

    $code = preg_replace('/\D/', '', (string)($in['passcode'] ?? '')) ?? '';
    $again = preg_replace('/\D/', '', (string)($in['passcode_confirm'] ?? '')) ?? '';
    if ($code !== $again) {
        return $fail('passcode_confirm', 'The two passcodes are not the same.');
    }
    $err = passcode_set($uid, $code);
    if ($err !== null) {
        return $fail('passcode', $err);
    }
    return ['ok' => true, 'message' => passcode_is_set($uid) ? 'Passcode saved. The app will ask for it when you open it again.' : 'Passcode saved.', 'errors' => []];
}
