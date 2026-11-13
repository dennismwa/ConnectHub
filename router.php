<?php
/**
 * Router for PHP's built-in web server (development and testing only):
 *   php -S 127.0.0.1:8080 router.php
 * On Apache or Nginx the .htaccess / server config does this job.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        if (strpos($path, '/install/') === 0 || $path === '/index.php') {
            return false; // let the server run it
        }
        http_response_code(404);
        exit;
    }
    if (preg_match('#^/uploads/photos/.*_[lmt]\.jpg$#', $path)) {
        http_response_code(404);
        exit;
    }
    if (preg_match('#(^|/)\\.[^/]|\\.(sql|md|log|sh|lock|dist|sample)$|/(composer|package(-lock)?)\\.json$|/tailwind\\.config\\.js$#i', $path)) {
        http_response_code(404); // same list the .htaccess blocks: dotfiles, dumps, docs, logs, tool configs
        exit;
    }
    foreach (['/config/', '/includes/', '/database/', '/storage/', '/app/', '/templates/', '/cron/', '/tools/', '/docs/', '/node_modules/', '/uploads/messages/'] as $deny) {
        if (strpos($path, $deny) === 0) {
            http_response_code(404);
            exit;
        }
    }
    return false; // serve the static file
}
if (preg_match('#^/install(/|$)#', $path)) {
    $_SERVER['SCRIPT_NAME'] = '/install/index.php';
    require __DIR__ . '/install/index.php';
    return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
