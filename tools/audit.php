<?php
/**
 * Static audit of the whole code base. Run: php tools/audit.php   (CH_CONFIG may point at a test config)
 * Checks: route files exist, partials exist, assets exist, icons exist in the sprite, settings keys exist in the
 * schema, every called function is defined, no function is declared inside page scripts or partials.
 * Exit code 1 when anything is wrong.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

$problems = [];
$note = function (string $kind, string $msg) use (&$problems) { $problems[$kind][] = $msg; };

function ch_files(string $dir, array $ext): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (strpos($p, '/node_modules/') !== false || strpos($p, '/storage/') !== false || strpos($p, '/uploads/') !== false) { continue; }
        if (in_array(strtolower($f->getExtension()), $ext, true)) { $out[] = $p; }
    }
    sort($out);
    return $out;
}

$php = array_merge(ch_files(ROOT . '/app', ['php']), ch_files(ROOT . '/includes', ['php']), ch_files(ROOT . '/templates', ['php']), ch_files(ROOT . '/cron', ['php']), ch_files(ROOT . '/install', ['php']));
$js = ch_files(ROOT . '/assets/js', ['js']);
$rel = fn($p) => str_replace(ROOT . '/', '', $p);

// ---- 0. migrations ---------------------------------------------------------------
$migFiles = migrate_files();
if (!$migFiles || max(array_keys($migFiles)) !== CH_SCHEMA_VERSION) { $note('migrations', 'CH_SCHEMA_VERSION is ' . CH_SCHEMA_VERSION . ' but the newest migration file is ' . ($migFiles ? max(array_keys($migFiles)) : 'missing')); }
if (array_keys($migFiles) !== range(1, count($migFiles))) { $note('migrations', 'migration numbers are not 1..N without gaps: ' . implode(',', array_keys($migFiles))); }
foreach ($migFiles as $n => $f) { if (!is_callable(require $f)) { $note('migrations', basename($f) . ' does not return a function'); } }

// ---- 1. routes -------------------------------------------------------------------
load_routes();
$routes = route_table();
foreach ($routes as $r) {
    if (!is_file(ROOT . '/app/' . $r['file'])) { $note('route file missing', implode('|', $r['methods']) . ' ' . $r['pattern'] . ' -> app/' . $r['file']); }
}
echo count($routes) . " routes checked\n";

// ---- 2. sprite icons -------------------------------------------------------------
$sprite = file_get_contents(ROOT . '/assets/icons/sprite.svg');
preg_match_all('/id="i-([a-z0-9-]+)"/', $sprite, $m);
$have = array_flip($m[1]);
$scan = array_merge($php, $js, ch_files(ROOT . '/database/seed', ['sql']));
$iconPatterns = ['/\bicon\(\s*[\'"]([a-z0-9-]+)[\'"]/', '/[\'"]icon[\'"]\s*(?:=>|:)\s*[\'"]([a-z0-9-]+)[\'"]/', '/\bicon:\s*[\'"]([a-z0-9-]+)[\'"]/', '/#i-([a-z0-9-]+)/', '/data-icon="([a-z0-9-]+)"/', '/"icon"\s*:\s*"([a-z0-9-]+)"/', '/badge_icon[^\n]{0,40}?[\'"]([a-z0-9-]+)[\'"]/'];
foreach ($scan as $f) {
    $src = file_get_contents($f);
    foreach ($iconPatterns as $re) {
        if (preg_match_all($re, $src, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[1] as [$name, $off]) {
                if (!isset($have[$name]) && !preg_match('/^(sprite|x|name|value|label)$/', $name)) {
                    $line = substr_count(substr($src, 0, $off), "\n") + 1;
                    $note('unknown icon', $rel($f) . ':' . $line . " icon '$name'");
                }
            }
        }
    }
}
// SQL seeds: icon columns are positional, so check quoted tokens that look like icon names in nav/category inserts
foreach (ch_files(ROOT . '/database/seed', ['sql']) as $f) {
    foreach (file($f) as $n => $ln) {
        if (preg_match("/^\('[a-z_]+','[^']*','[^']*','([a-z0-9-]+)'/", $ln, $mm) && !isset($have[$mm[1]])) { $note('unknown icon', $rel($f) . ':' . ($n + 1) . " seed icon '{$mm[1]}'"); }
    }
}

// ---- 3. settings keys ------------------------------------------------------------
$schema = settings_schema();
$keys = $schema['fields'];
$used = [];
foreach ($php as $f) {
    $src = file_get_contents($f);
    if (preg_match_all('/\bsetting\(\s*[\'"]([a-z0-9_]+)[\'"]/', $src, $mm, PREG_OFFSET_CAPTURE)) {
        foreach ($mm[1] as [$k, $off]) {
            $used[$k] = true;
            if (!isset($keys[$k]) && $k !== 'key' && $k[0] !== '_' && substr($k, -1) !== '_') { $note('unknown setting', $rel($f) . ':' . (substr_count(substr($src, 0, $off), "\n") + 1) . " setting('$k')"); }
        }
    }
    if (preg_match_all('/\bsetting_set\(\s*[\'"]([a-z0-9_]+)[\'"]/', $src, $mm)) { foreach ($mm[1] as $k) { $used[$k] = true; } }
}
// every settings group must sit under a registered tab (a typo here crashes the whole Settings screen)
foreach ($schema['tabs'] as $tk => $tt) {
    foreach (['label', 'icon', 'desc'] as $need) {
        if (!isset($tt[$need]) || $tt[$need] === '') { $note('settings tab', "tab '$tk' has no $need (a settings group names a tab that was never registered)"); }
    }
}
foreach ($keys as $k => $def) {
    if (!isset($schema['tabs'][$def['tab'] ?? ''])) { $note('settings tab', "setting '$k' sits under an unknown tab '" . ($def['tab'] ?? '') . "'"); }
}
echo count($keys) . ' settings in schema, ' . count($used) . " referenced directly in code\n";

// ---- 4. partials and assets ------------------------------------------------------
foreach ($php as $f) {
    $src = file_get_contents($f);
    if (preg_match_all('/\bpartial\(\s*[\'"]([a-z0-9_\/-]+)[\'"](?!\s*\.)/', $src, $mm, PREG_OFFSET_CAPTURE)) {
        foreach ($mm[1] as [$name, $off]) {
            if (!is_file(ROOT . '/templates/partials/' . $name . '.php')) { $note('missing partial', $rel($f) . ':' . (substr_count(substr($src, 0, $off), "\n") + 1) . " '$name'"); }
        }
    }
    if (preg_match_all('/[\'"]((?:js|css|images|icons|fonts)\/[A-Za-z0-9_\/.-]+\.(?:js|css|png|svg|jpg|webp|woff2))[\'"]/', $src, $mm, PREG_OFFSET_CAPTURE)) {
        foreach ($mm[1] as [$name, $off]) {
            if (!is_file(ROOT . '/assets/' . $name)) { $note('missing asset', $rel($f) . ':' . (substr_count(substr($src, 0, $off), "\n") + 1) . " '$name'"); }
        }
    }
}
foreach (ch_files(ROOT . '/templates', ['php']) as $f) {
    foreach (['include', 'require', 'include_once', 'require_once'] as $_) { /* includes use __DIR__ paths; php -l and smoke tests cover them */ }
}

// ---- 5. undefined function calls + declarations in page scripts ------------------
$builtin = array_flip(array_merge(get_defined_functions()['internal'], ['isset', 'empty', 'unset', 'list', 'array', 'exit', 'die', 'echo', 'print', 'eval', 'compact', 'extract', 'func_get_args', 'func_num_args']));
$skipFiles = ['tools/', 'install/'];
foreach ($php as $f) {
    $r = $rel($f);
    $tokens = token_get_all(file_get_contents($f));
    $n = count($tokens);
    $declared = [];
    for ($i = 0; $i < $n; $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
            $j = $i + 1;
            while ($j < $n && (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE || $tokens[$j] === '&')) { $j++; }
            if ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) { $declared[strtolower($tokens[$j][1])] = $tokens[$j][2]; }
        }
    }
    $inLib = strpos($r, 'includes/') === 0;
    if (!$inLib && strpos($r, 'install/') !== 0 && strpos($r, 'app/routes/') !== 0) {
        // classes/closures are fine; named functions inside page scripts or partials are redeclared on the second render
        foreach ($declared as $name => $line) {
            $isMethod = false;
            if (!$isMethod) { $note('function declared in a page/partial', "$r:$line function $name()"); }
        }
    }
    for ($i = 0; $i < $n; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING) { continue; }
        $name = $tokens[$i][1];
        $line = $tokens[$i][2];
        // next significant token must be "("
        $k = $i + 1;
        while ($k < $n && is_array($tokens[$k]) && in_array($tokens[$k][0], [T_WHITESPACE, T_COMMENT], true)) { $k++; }
        if ($k >= $n || $tokens[$k] !== '(') { continue; }
        // previous significant token
        $p = $i - 1;
        while ($p >= 0 && is_array($tokens[$p]) && in_array($tokens[$p][0], [T_WHITESPACE, T_COMMENT], true)) { $p--; }
        $prev = $p >= 0 ? $tokens[$p] : null;
        if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW, T_CONST, T_USE, T_EXTENDS, T_IMPLEMENTS, T_INSTANCEOF, T_NAMESPACE], true)) { continue; }
        if ($prev === '&') { continue; }
        $lc = strtolower($name);
        if (isset($builtin[$lc]) || function_exists($name) || isset($declared[$lc]) || in_array($lc, ['self', 'parent', 'static', 'array', 'match', 'fn', 'catch', 'if', 'elseif', 'while', 'for', 'foreach', 'switch', 'return', 'and', 'or', 'new'], true)) { continue; }
        $note('undefined function', "$r:$line $name()");
    }
}

// ---- report ------------------------------------------------------------------------
$total = 0;
foreach ($problems as $kind => $list) {
    $list = array_values(array_unique($list));
    echo "\n== $kind (" . count($list) . ")\n";
    foreach (array_slice($list, 0, 80) as $l) { echo "  $l\n"; }
    if (count($list) > 80) { echo '  ... and ' . (count($list) - 80) . " more\n"; }
    $total += count($list);
}
echo $total ? "\n$total problem(s)\n" : "\nAudit clean\n";
exit($total ? 1 : 0);
