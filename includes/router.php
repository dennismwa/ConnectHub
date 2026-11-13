<?php
/**
 * Router + dispatcher.
 *
 * Route files in app/routes/*.php call:
 *   route('GET', '/u/{id:int}', 'explorer/profile_view.php', ['auth' => 'member']);
 *   route('GET|POST', '/login', 'pages/auth.php');
 *
 * Placeholders: {name} (any segment), {name:int}, {name:slug}, {name:token}, {name:*} (rest of path).
 * Options:
 *   auth        false (default) | 'any' | 'member' | 'user' | 'host' | 'admin'
 *   perm        ['module','level'] for admin routes (permission check)
 *   csrf        true (default for POST) | false (webhooks)
 *   onboarding  true (default for member routes) | false (allowed before onboarding is finished)
 *   verify      true (default for member routes) | false (allowed before e-mail is verified)
 *   layout      layout name (default public; app for members; admin for /admin)
 *   rate        [max, windowSeconds] per-IP rate limit for this route
 *   maintenance false to stay reachable during maintenance mode
 * Page scripts run with $params (placeholder values), output HTML and call page([...]).
 */

declare(strict_types=1);

function &route_table(): array
{
    static $routes = [];
    return $routes;
}

function route(string $methods, string $pattern, string $file, array $opts = []): void
{
    $regex = preg_replace_callback('/\{(\w+)(?::(\w+|\*))?\}/', function ($m) {
        $type = $m[2] ?? '';
        switch ($type) {
            case 'int':
                $re = '\d+';
                break;
            case 'slug':
                $re = '[A-Za-z0-9][A-Za-z0-9_-]*';
                break;
            case 'token':
                $re = '[A-Za-z0-9._-]+';
                break;
            case '*':
                $re = '.+';
                break;
            default:
                $re = '[^/]+';
        }
        return '(?P<' . $m[1] . '>' . $re . ')';
    }, $pattern);
    $routes = &route_table();
    $routes[] = [
        'methods' => array_map('trim', explode('|', strtoupper($methods))),
        'pattern' => $pattern, 'regex' => '#^' . $regex . '$#', 'file' => $file, 'opts' => $opts,
    ];
}

function load_routes(): void
{
    $files = glob(ROOT . '/app/routes/*.php') ?: [];
    sort($files);
    foreach ($files as $f) {
        require $f;
    }
}

/** Find the route for a method + path. Returns [route, params] or [null, allowedMethods]. */
function route_match(string $method, string $path): array
{
    $allowed = [];
    foreach (route_table() as $r) {
        if (preg_match($r['regex'], $path, $m)) {
            $m = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $lookup = $method === 'HEAD' ? 'GET' : $method;
            if (in_array($lookup, $r['methods'], true)) {
                return [$r, $m];
            }
            $allowed = array_merge($allowed, $r['methods']);
        }
    }
    return [null, array_unique($allowed)];
}

function maintenance_exempt(string $path): bool
{
    foreach (['/login', '/logout', '/api/mpesa', '/manifest.webmanifest', '/robots.txt', '/sw.js', '/offline'] as $p) {
        if ($path === $p || strpos($path, $p . '/') === 0) {
            return true;
        }
    }
    return false;
}

function dispatch(): void
{
    $path = request_path();
    $method = request_method();
    $isAdminPath = $path === '/admin' || strpos($path, '/admin/') === 0;
    send_security_headers($isAdminPath);

    try {
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        host_label_early();
        elite_url_early();
        if (function_exists('seo_early')) {
            seo_early();
        }
        load_routes();
        [$route, $params] = route_match($method, $path);

        if (!$route) {
            if ($params) {
                header('Allow: ' . implode(', ', $params));
                throw new HttpError('That action is not allowed here.', 405);
            }
            throw new HttpError('', 404);
        }
        $o = $route['opts'] + [];

        // Maintenance mode
        if (setting('maintenance_mode') && ($o['maintenance'] ?? true) && !maintenance_exempt($path)) {
            $cu = current_user();
            if (!$cu || $cu['role'] !== 'admin') {
                header('Retry-After: 3600');
                http_response_code(503);
                echo render_error_page(503, (string)setting('maintenance_message'));
                return;
            }
        }

        if (isset($o['rate'])) {
            require_rate_limit('route:' . $route['pattern'] . ':' . client_ip(), (int)$o['rate'][0], (int)$o['rate'][1]);
        }

        // Auth guards
        $auth = $o['auth'] ?? false;
        $user = null;
        if ($auth) {
            switch ($auth) {
                case 'user':
                    $user = require_user();
                    break;
                case 'host':
                    $user = require_host();
                    break;
                case 'member':
                    $user = require_member();
                    break;
                case 'admin':
                    $perm = $o['perm'] ?? ['', 'view'];
                    $user = require_admin((string)$perm[0], (string)($perm[1] ?? 'view'));
                    break;
                default:
                    $user = require_login();
            }
        }

        // CSRF on every state-changing request
        if (!in_array($method, ['GET', 'HEAD'], true) && ($o['csrf'] ?? true)) {
            csrf_require();
        }

        // A member who put a passcode on their account unlocks before anything else
        if ($user && function_exists('passcode_guard') && ($o['passcode'] ?? true)) {
            passcode_guard();
        }

        // Onboarding / verification gates for members
        if ($user && in_array($user['role'], ['user', 'host'], true) && in_array($auth, ['member', 'user', 'host', 'any'], true)) {
            if (($o['verify'] ?? true) && setting('email_verification_required') && empty($user['email_verified_at'])) {
                if (wants_json()) {
                    json_fail('Please confirm your email address first.', 403, ['redirect' => url('/verify-email')]);
                }
                redirect('/verify-email');
            }
            if (($o['onboarding'] ?? true) && setting('onboarding_required') && !(int)$user['onboarding_completed']) {
                if (wants_json()) {
                    json_fail('Finish setting up your profile first.', 403, ['redirect' => url($user['role'] === 'host' ? '/host/setup' : '/onboarding')]);
                }
                redirect($user['role'] === 'host' ? '/host/setup' : '/onboarding');
            }
        }

        // Layout default
        $defaultLayout = $isAdminPath ? 'admin' : ($user && in_array($user['role'], ['user', 'host'], true) && in_array($auth, ['member', 'user', 'host'], true) ? 'app' : 'public');
        page(['layout' => $o['layout'] ?? $defaultLayout]);

        $file = ROOT . '/app/' . $route['file'];
        if (!is_file($file)) {
            throw new RuntimeException('Route file missing: ' . $route['file']);
        }
        $content = run_page_script($file, $params);
        if (page_get('layout') === 'blank' && !headers_sent() && !preg_grep('/^content-type:/i', headers_list())) {
            header('Content-Type: text/html; charset=utf-8');
        } elseif (!headers_sent() && !preg_grep('/^content-type:/i', headers_list())) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo render_layout($content);
    } catch (HttpError $e) {
        send_error_response($e->getCode() ?: 500, $e->getMessage());
    } catch (Throwable $e) {
        app_log('error', get_class($e) . ': ' . $e->getMessage() . ' @ ' . str_replace(ROOT, '', $e->getFile()) . ':' . $e->getLine() . ' [' . $method . ' ' . $path . ']');
        if (is_debug()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
            echo '<pre style="white-space:pre-wrap;padding:24px;font:13px/1.5 monospace">' . e(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString()) . '</pre>';
            return;
        }
        send_error_response(500, '');
    }
}

function send_error_response(int $code, string $message): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if ($code < 400 || $code > 599) {
        $code = 500;
    }
    http_response_code($code);
    if ($code === 404 && function_exists('seo_log_404') && !wants_json()) {
        seo_log_404(request_path());
    }
    if (wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $message !== '' ? $message : (['404' => 'Not found', '403' => 'Not allowed', '419' => 'Session expired', '429' => 'Too many requests'][(string)$code] ?? 'Something went wrong')]);
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo render_error_page($code, $message);
}
