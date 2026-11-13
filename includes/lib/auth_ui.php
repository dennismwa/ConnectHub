<?php
/**
 * Helpers for the sign-in / register experience (pages + modal).
 */

declare(strict_types=1);

/** Respond to an auth form: JSON for the modal/AJAX, flash + redirect for plain form posts. */
function auth_respond(bool $ok, string $message, array $extra = [], string $to = '/'): void
{
    if (wants_json()) {
        $payload = ['ok' => $ok] + ($ok ? ['message' => $message] : ['error' => $message]) + $extra;
        if ($ok && !isset($payload['redirect'])) {
            $payload['redirect'] = url($to);
        }
        json_out($payload, $ok ? 200 : (($extra['_status'] ?? 422)));
    }
    flash($ok ? 'success' : 'error', $message);
    if (!$ok && !empty($extra['errors'])) {
        errors_set($extra['errors']);
    }
    redirect($to);
}

/** Where a member lands after signing in. */
function auth_landing_url(array $user, ?string $next = null): string
{
    if (!empty($user['must_change_password'])) {
        return '/account/password?force=1' . ($next ? '&next=' . rawurlencode($next) : '');
    }
    $default = home_for_role($user['role']);
    if ($next === null || $next === '' || $next === '/') {
        return $default;
    }
    $safe = safe_next($next, $default);
    // never send a member to an area their role cannot use
    if (strpos($safe, '/admin') === 0 && $user['role'] !== 'admin') {
        return $default;
    }
    if (strpos($safe, '/host/') === 0 && $user['role'] !== 'host') {
        return $default;
    }
    return $safe;
}

function auth_send_verification(int $userId, string $email, string $name): void
{
    $token = token_create($userId, 'verify', 60 * 24);
    send_template_mail($email, 'Confirm your email address', 'verify', ['name' => $name, 'url' => abs_url('/verify-email/' . $token)]);
}

/** Latest approved stories for the sign-in side panel. */
function auth_stories(int $limit = 4): array
{
    if (!setting('auth_show_testimonials')) {
        return [];
    }
    return cache_remember('auth_stories', 300, fn() => rows(
        'SELECT name, location, quote, rating, photo_path FROM testimonials WHERE is_active = 1 AND show_auth = 1 ORDER BY sort_order, id LIMIT ' . $limit
    ));
}

/** Counter values for the bottom of the side panel. Never invents numbers: empty values are hidden. */
function auth_stats(): array
{
    if (!setting('auth_show_stats')) {
        return [];
    }
    $out = [];
    if (setting('auth_stats_mode') === 'manual') {
        for ($i = 1; $i <= 3; $i++) {
            $v = trim((string)setting("auth_stat{$i}_value"));
            if ($v !== '') {
                $out[] = ['value' => $v, 'label' => (string)setting("auth_stat{$i}_label")];
            }
        }
        return $out;
    }
    $s = site_stats();
    if ($s['matches'] > 0) {
        $out[] = ['value' => number_short((int)$s['matches']), 'label' => (string)setting('auth_stat1_label')];
    }
    if ($s['active_week'] > 0) {
        $out[] = ['value' => number_short((int)$s['active_week']), 'label' => (string)setting('auth_stat2_label')];
    }
    $avg = cache_remember('auth_avg_rating', 600, fn() => (float)val('SELECT AVG(rating) FROM testimonials WHERE is_active = 1', [], 0));
    if ($avg > 0) {
        $out[] = ['value' => number_format($avg, 1), 'label' => (string)setting('auth_stat3_label')];
    }
    return $out;
}

/** Latest and earliest allowed birth dates for the register form. */
function auth_dob_bounds(): array
{
    $min = max(18, (int)setting('min_age'));
    return ['max' => date('Y-m-d', strtotime("-{$min} years")), 'min' => date('Y-m-d', strtotime('-100 years'))];
}

/**
 * Validate a registration. Returns [errors, clean]. Never trusts the posted account type:
 * hosts only when the admin has Hosts turned on.
 */
function register_validate(array $in): array
{
    $errors = [];
    $type = (($in['account_type'] ?? 'user') === 'host' && setting('hosts_enabled')) ? 'host' : 'user';
    $name = trim(preg_replace('/\s+/u', ' ', (string)($in['display_name'] ?? '')));
    $email = strtolower(trim((string)($in['email'] ?? '')));
    $pass = (string)($in['password'] ?? '');
    $dob = trim((string)($in['date_of_birth'] ?? ''));
    $gender = (string)($in['gender'] ?? '');
    $into = (string)($in['interested_in'] ?? '');
    $phoneRaw = trim((string)($in['phone'] ?? ''));
    $countyId = (int)($in['county_id'] ?? 0);

    if (mb_strlen($name) < 2 || mb_strlen($name) > 40) {
        $errors['display_name'] = 'Enter your first name (2 to 40 characters).';
    } elseif (!preg_match('/^[\p{L}][\p{L} \'.-]*$/u', $name)) {
        $errors['display_name'] = 'Use letters only in your name.';
    } elseif ($word = contains_banned_word($name)) {
        $errors['display_name'] = 'That name is not allowed. Please use your real first name.';
    }
    if (!valid_email($email) || strlen($email) > 190) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($p = password_problem($pass)) {
        $errors['password'] = $p;
    }

    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d || $d->format('Y-m-d') !== $dob) {
        $errors['date_of_birth'] = 'Enter your date of birth.';
    } else {
        $age = age_from_dob($dob);
        if ($age === null) {
            $errors['date_of_birth'] = 'Check your date of birth.';
        } elseif ($age < max(18, (int)setting('min_age'))) {
            $errors['date_of_birth'] = 'You must be ' . max(18, (int)setting('min_age')) . ' or older to join.';
        } elseif ($age > 100) {
            $errors['date_of_birth'] = 'Check your date of birth.';
        }
    }

    $phone = '';
    if ($phoneRaw !== '') {
        $phone = phone_normalize($phoneRaw);
        if ($phone === '') {
            $errors['phone'] = 'Enter a valid phone number, for example 0712 345 678.';
        }
    }
    if ($type === 'host') {
        if ($phone === '' && !isset($errors['phone'])) {
            $errors['phone'] = 'Hosts need a phone number so clients and our team can reach you.';
        }
        if (!$countyId || !location_by_id($countyId)) {
            $errors['county_id'] = 'Choose the county where you are based.';
        }
        $gender = in_array($gender, ['male', 'female', 'non-binary', 'other'], true) ? $gender : null;
        $into = 'everyone';
    } else {
        if (!in_array($gender, ['male', 'female', 'non-binary'], true)) {
            $errors['gender'] = 'Choose how you describe yourself.';
        }
        if (!in_array($into, ['male', 'female', 'everyone'], true)) {
            $errors['interested_in'] = 'Choose who you would like to meet.';
        }
        if (setting('require_phone') && $phone === '' && !isset($errors['phone'])) {
            $errors['phone'] = 'Enter your phone number.';
        }
        if ($countyId && !location_by_id($countyId)) {
            $countyId = 0;
        }
    }
    if (setting('require_age_confirm') && empty($in['age_confirm'])) {
        $errors['age_confirm'] = 'Please confirm you are 18 or older.';
    }
    if (empty($in['terms'])) {
        $errors['terms'] = 'Please accept the terms and privacy policy to continue.';
    }
    if (!$errors && val('SELECT COUNT(*) FROM users WHERE email = :e', ['e' => $email])) {
        $errors['email'] = 'An account with this email already exists. Sign in instead.';
    }

    return [$errors, [
        'role' => $type, 'email' => $email, 'password' => $pass, 'display_name' => $name, 'date_of_birth' => $dob,
        'gender' => $gender ?: null, 'interested_in' => $into ?: 'everyone', 'phone' => $phone !== '' ? $phone : null,
        'county_id' => $countyId ?: null,
    ]];
}

/**
 * Render a single-card account page (forgot password, reset, verify e-mail, 2FA...) inside the sign-in page frame.
 * $icon is a sprite icon name shown above the title.
 */
function auth_solo_page(string $title, string $subtitle, string $bodyHtml, string $icon = 'key-round', array $meta = []): void
{
    page($meta + ['title' => $title, 'layout' => 'auth', 'robots' => 'noindex,nofollow', 'js' => ['js/auth.js'], 'body_class' => 'auth-body']);
    $inner = '<span class="auth-solo-icon">' . icon($icon, 'w-6 h-6') . '</span>'
        . '<header class="auth-head"><h1 class="auth-title">' . e($title) . '</h1>'
        . ($subtitle !== '' ? '<p class="auth-sub">' . e($subtitle) . '</p>' : '') . '</header>' . $bodyHtml;
    echo partial('auth_page', ['side' => 'login', 'next' => '', 'type' => 'user', 'inner' => $inner]);
}

/** Alert box markup used by the single-card pages. */
function auth_alert_html(): string
{
    $out = '';
    foreach (flashes() as $f) {
        $kind = $f['type'] === 'success' ? 'success' : ($f['type'] === 'info' ? 'info' : 'error');
        $out .= '<div class="auth-alert" data-kind="' . $kind . '" role="alert">' . e($f['message']) . '</div>';
    }
    return $out;
}
