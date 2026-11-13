<?php
/**
 * Media library.
 *
 * Every image or video an admin uploads for the site (logos, hero slides, town pictures, covers, ...) is recorded in
 * media_files so it can be picked again for another slot, replaced, or deleted from one place (Admin > Media).
 * Built-in illustrations ship with the site in assets/images/art and are addressed as "@art/file.webp"; they are
 * listed beside the uploads but are never deleted.
 *
 * Rule that keeps things safe: a file that is in the library is never removed by a slot that stops using it
 * (upload_delete() checks media_is_registered()). Only media_delete() removes it, and only when nothing uses it.
 */

declare(strict_types=1);

const MEDIA_FOLDERS = ['site', 'hero', 'library', 'ads', 'blog', 'testimonials', 'pages', 'content', 'towns', 'landing', 'locations'];

// ---------------------------------------------------------------------------
// Built-in illustrations
// ---------------------------------------------------------------------------

function media_art_dir(): string
{
    return ROOT . '/assets/images/art';
}

/** Group label for a built-in file, from its name. */
function media_art_group(string $file): string
{
    $n = strtolower($file);
    foreach ([
        'hero-' => 'Hero', 'town-' => 'Towns', 'step-' => 'How it works', 'blog-' => 'Blog covers',
        'empty-' => 'Empty states', 'error-' => 'Empty states', 'offline' => 'Empty states', 'blob-' => 'Backgrounds',
        'auth-' => 'Sign-in', 'face-' => 'Faces',
    ] as $prefix => $label) {
        if (strpos($n, $prefix) === 0) {
            return $label;
        }
    }
    return 'Sections';
}

/** All built-in illustrations: [ ['path'=>'@art/x.webp','file','title','alt','w','h','group'], ... ] */
function media_art_items(): array
{
    static $items = null;
    if ($items !== null) {
        return $items;
    }
    $items = [];
    $dir = media_art_dir();
    if (!is_dir($dir)) {
        return $items;
    }
    $meta = [];
    $mf = $dir . '/manifest.json';
    if (is_file($mf)) {
        foreach ((array)json_decode((string)file_get_contents($mf), true) as $m) {
            if (is_array($m) && !empty($m['file'])) {
                $meta[(string)$m['file']] = $m;
            }
        }
    }
    $files = glob($dir . '/*.{webp,png,jpg,jpeg,svg}', GLOB_BRACE) ?: [];
    sort($files);
    foreach ($files as $f) {
        $file = basename($f);
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $file)) {
            continue;
        }
        $m = $meta[$file] ?? [];
        $title = ucfirst(trim(str_replace(['-', '_'], ' ', pathinfo($file, PATHINFO_FILENAME))));
        $items[] = [
            'path' => '@art/' . $file, 'file' => $file, 'title' => $title, 'alt' => (string)($m['alt'] ?? $title),
            'w' => isset($m['width']) ? (int)$m['width'] : null, 'h' => isset($m['height']) ? (int)$m['height'] : null,
            'group' => media_art_group($file), 'size' => (int)@filesize($f),
        ];
    }
    return $items;
}

function media_art_valid(string $path): bool
{
    if (strncmp($path, '@art/', 5) !== 0) {
        return false;
    }
    $file = substr($path, 5);
    return (bool)preg_match('/^[A-Za-z0-9_.-]+$/', $file) && is_file(media_art_dir() . '/' . $file);
}

/** Alt text for a stored path when the slot has none of its own (built-in art ships with descriptions). */
function media_alt(?string $path, string $fallback = ''): string
{
    if ($path && strncmp($path, '@art/', 5) === 0) {
        foreach (media_art_items() as $it) {
            if ($it['path'] === $path) {
                return $it['alt'];
            }
        }
    }
    return $fallback;
}

// ---------------------------------------------------------------------------
// Registering and looking up uploads
// ---------------------------------------------------------------------------

/** Is the path a file this library may track (a site upload, not a member photo or private document)? */
function media_path_ok(string $rel): bool
{
    $rel = ltrim($rel, '/');
    if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, '/_thumbs/') !== false || !preg_match('#^[a-z]+/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_.-]+\.(?:jpg|jpeg|png|webp|gif|svg|mp4|webm)$#i', $rel)) {
        return false;
    }
    return in_array(explode('/', $rel)[0], MEDIA_FOLDERS, true);
}

function media_table_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            $ok = function_exists('mig_has_table') && mig_has_table('media_files');
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

/** Cache of paths looked up in this request: path => bool. */
function media_registered_cache(?string $path = null, ?bool $set = null): ?bool
{
    static $c = [];
    if ($path === null) {
        $c = [];
        return null;
    }
    if ($set !== null) {
        $c[$path] = $set;
    }
    return $c[$path] ?? null;
}

function media_is_registered(string $rel): bool
{
    $rel = ltrim($rel, '/');
    if (strncmp($rel, '@art/', 5) === 0) {
        return true;
    }
    if (!media_table_ready()) {
        return false;
    }
    $hit = media_registered_cache($rel);
    if ($hit !== null) {
        return $hit;
    }
    try {
        $has = (int)val('SELECT COUNT(*) FROM media_files WHERE path = :p', ['p' => $rel], 0) > 0;
    } catch (Throwable $e) {
        $has = false;
    }
    media_registered_cache($rel, $has);
    return $has;
}

/** "my-photo_final.JPG" -> "My photo final" */
function media_title_from_name(string $name): string
{
    $t = pathinfo($name, PATHINFO_FILENAME);
    $t = trim((string)preg_replace('/[\s_\-]+/', ' ', $t));
    return $t === '' ? '' : mb_substr(mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1), 0, 100);
}

/** Small preview for the library grid: bigger pictures get a 480px copy so a page of thumbnails stays light. */
function media_make_thumb(string $rel): ?string
{
    if (!function_exists('imagecreatetruecolor') || !is_file(upload_abs($rel))) {
        return null;
    }
    $info = @getimagesize(upload_abs($rel));
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true) || max($info[0], $info[1]) <= 520) {
        return null;
    }
    try {
        $img = image_load(upload_abs($rel));
        $alpha = $info[2] !== IMAGETYPE_JPEG && image_has_alpha($img);
        upload_ensure_dir('library/_thumbs');
        $name = 'library/_thumbs/' . substr(md5($rel), 0, 16) . ($alpha ? '.png' : '.jpg');
        if ($alpha) {
            $w = imagesx($img);
            $h = imagesy($img);
            $scale = 480 / max($w, $h);
            $nw = max(1, (int)round($w * $scale));
            $nh = max(1, (int)round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagepng($dst, upload_abs($name), 7);
        } else {
            image_save_jpeg(image_fit($img, 480), upload_abs($name), 76);
        }
        @chmod(upload_abs($name), 0664);
        return $name;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Put an existing upload into the library (no-op when it is already there). Returns the media id, or 0 when the
 * file cannot be tracked. $o: title, uploaded_by.
 */
function media_register(string $rel, array $o = []): int
{
    $rel = ltrim($rel, '/');
    if (!media_table_ready() || !media_path_ok($rel) || !is_file(upload_abs($rel))) {
        return 0;
    }
    $id = (int)val('SELECT id FROM media_files WHERE path = :p', ['p' => $rel], 0);
    if ($id > 0) {
        return $id;
    }
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $kind = in_array($ext, ['mp4', 'webm'], true) ? 'video' : 'image';
    $w = $h = null;
    if ($kind === 'image' && $ext !== 'svg') {
        $info = @getimagesize(upload_abs($rel));
        if ($info) {
            $w = (int)$info[0];
            $h = (int)$info[1];
        }
    }
    $thumb = $kind === 'image' && $ext !== 'svg' ? media_make_thumb($rel) : null;
    try {
        $id = insert('media_files', [
            'path' => $rel, 'thumb_path' => $thumb, 'title' => mb_substr((string)($o['title'] ?? ''), 0, 120), 'kind' => $kind,
            'width' => $w, 'height' => $h, 'file_size' => (int)filesize(upload_abs($rel)),
            'uploaded_by' => !empty($o['uploaded_by']) ? (int)$o['uploaded_by'] : (function_exists('auth_id') ? (auth_id() ?: null) : null),
        ]);
    } catch (Throwable $e) { // two requests registering the same file at once
        $id = (int)val('SELECT id FROM media_files WHERE path = :p', ['p' => $rel], 0);
    }
    media_registered_cache($rel, true);
    return (int)$id;
}

/** Drop the library record (and its small preview) without touching the original file. */
function media_forget(string $rel): void
{
    if (!media_table_ready()) {
        return;
    }
    $thumb = (string)val('SELECT thumb_path FROM media_files WHERE path = :p', ['p' => $rel], '');
    q('DELETE FROM media_files WHERE path = :p', ['p' => $rel]);
    if ($thumb !== '') {
        @unlink(upload_abs($thumb));
    }
    media_registered_cache($rel, false);
}

/** Add every picture already stored under the site upload folders (used once when the library is created). */
function media_backfill(): int
{
    $n = 0;
    foreach (MEDIA_FOLDERS as $folder) {
        $dir = ROOT . '/uploads/' . $folder;
        if (!is_dir($dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile() || strpos($f->getPathname(), '/_thumbs/') !== false) {
                continue;
            }
            $rel = ltrim(substr($f->getPathname(), strlen(ROOT . '/uploads/')), '/');
            if (media_register($rel, ['title' => '', 'uploaded_by' => 0]) > 0) {
                $n++;
            }
        }
    }
    return $n;
}

// ---------------------------------------------------------------------------
// Validating a choice from the library
// ---------------------------------------------------------------------------

/** A path chosen in the picker is accepted only when it is built-in art or a registered upload of the right kind. */
function media_pick_valid(string $path, string $kind = 'image'): ?string
{
    $path = trim($path);
    if ($path === '') {
        return null;
    }
    if (strncmp($path, '@art/', 5) === 0) {
        return $kind === 'image' && media_art_valid($path) ? $path : null;
    }
    $path = ltrim($path, '/');
    if (!media_path_ok($path) || !media_table_ready()) {
        return null;
    }
    $row = row('SELECT kind FROM media_files WHERE path = :p', ['p' => $path]);
    if (!$row || $row['kind'] !== $kind || !is_file(upload_abs($path))) {
        return null;
    }
    return $path;
}

// ---------------------------------------------------------------------------
// Where is a file used?
// ---------------------------------------------------------------------------

/**
 * path => [ ['label'=>..., 'url'=>...], ... ] for every place on the site that points at an uploaded file.
 * Other modules can add their own places through the "media_usage" list in media_usage_sources().
 */
function media_usage_map(bool $reload = false): array
{
    static $map = null;
    if ($map !== null && !$reload) {
        return $map;
    }
    $map = [];
    $add = function ($path, string $label, string $url) use (&$map): void {
        $path = is_string($path) ? ltrim(trim($path), '/') : '';
        if ($path !== '') {
            $map[$path][] = ['label' => $label, 'url' => $url];
        }
    };
    $short = fn($s, int $n = 46) => mb_strlen((string)$s) > $n ? mb_substr((string)$s, 0, $n - 1) . '…' : (string)$s;

    $fields = settings_schema()['fields'];
    foreach (settings_overrides() as $k => $v) {
        if (isset($fields[$k]) && $fields[$k]['type'] === 'image' && is_string($v) && $v !== '') {
            $add($v, 'Setting: ' . $fields[$k]['label'], url('/admin/settings?tab=' . $fields[$k]['tab']) . '#set-' . $k);
        }
    }
    $sources = [
        ['hero_slides', ['image_path', 'mobile_path', 'video_path'], 'Hero slide', 'headline', '/admin/landing'],
        ['advertisements', ['image_path'], 'Advert', 'title', '/admin/ads/{id}'],
        ['blog_posts', ['cover_path'], 'Blog cover', 'title', '/admin/blog/{id}'],
        ['testimonials', ['photo_path'], 'Testimonial', 'name', '/admin/testimonials'],
        ['pages', ['og_image'], 'Page share image', 'title', '/admin/pages/{id}'],
        ['locations', ['image_path'], 'Town card', 'name', '/admin/locations'],
    ];
    foreach ($sources as [$table, $cols, $label, $titleCol, $link]) {
        try {
            $have = array_values(array_filter($cols, fn($c) => mig_has_column($table, $c)));
            if (!$have) {
                continue;
            }
            foreach (rows('SELECT id, ' . $titleCol . ' AS t, ' . implode(', ', $have) . ' FROM ' . $table) as $r) {
                foreach ($have as $c) {
                    $add($r[$c], $label . ': ' . $short($r['t']), url(str_replace('{id}', (string)$r['id'], $link)));
                }
            }
        } catch (Throwable $e) {
        }
    }
    // pictures chosen inside landing sections (step pictures, banners, town cards ...), at any depth of the saved content
    try {
        $walk = function ($node, string $label, string $url) use (&$walk, $add): void {
            if (is_array($node)) {
                foreach ($node as $v) {
                    $walk($v, $label, $url);
                }
            } elseif (is_string($node) && $node !== '' && strlen($node) < 200 && media_path_ok($node)) {
                $add($node, $label, $url);
            }
        };
        foreach (rows('SELECT id, section_key, title, content FROM landing_sections') as $r) {
            $c = json_decode((string)$r['content'], true);
            if (is_array($c)) {
                $walk($c, 'Landing section: ' . $short($r['title'] !== '' ? $r['title'] : $r['section_key']), url('/admin/landing/sections/' . $r['section_key']));
            }
        }
    } catch (Throwable $e) {
    }
    // pictures placed inside blog posts and pages
    foreach ([['blog_posts', 'Blog post', '/admin/blog/{id}', 'title'], ['pages', 'Page', '/admin/pages/{id}', 'title']] as [$table, $label, $link, $titleCol]) {
        try {
            foreach (rows('SELECT id, ' . $titleCol . ' AS t, content FROM ' . $table . " WHERE content LIKE '%/uploads/%' LIMIT 800") as $r) {
                if (preg_match_all('#/uploads/((?:content|library|blog|pages|site|landing)/[A-Za-z0-9_./-]+)#', (string)$r['content'], $m)) {
                    foreach (array_unique($m[1]) as $p) {
                        $add($p, $label . ' text: ' . $short($r['t']), url(str_replace('{id}', (string)$r['id'], $link)));
                    }
                }
            }
        } catch (Throwable $e) {
        }
    }
    return $map;
}

// ---------------------------------------------------------------------------
// Listing, uploading, deleting
// ---------------------------------------------------------------------------

function media_item_public(array $r, array $usage = []): array
{
    $thumb = (string)($r['thumb_path'] ?? '');
    return [
        'id' => (int)$r['id'], 'path' => (string)$r['path'], 'url' => upload_url((string)$r['path']),
        'thumb' => upload_url($thumb !== '' ? $thumb : (string)$r['path']),
        'title' => (string)$r['title'] !== '' ? (string)$r['title'] : 'Untitled', 'kind' => (string)$r['kind'],
        'w' => $r['width'] !== null ? (int)$r['width'] : null, 'h' => $r['height'] !== null ? (int)$r['height'] : null,
        'size' => (int)$r['file_size'], 'created' => (string)$r['created_at'], 'source' => 'library',
        'uses' => count($usage), 'usage' => $usage,
    ];
}

function media_art_public(array $a): array
{
    return [
        'id' => 0, 'path' => $a['path'], 'url' => upload_url($a['path']), 'thumb' => upload_url($a['path']), 'title' => $a['title'],
        'kind' => 'image', 'w' => $a['w'], 'h' => $a['h'], 'size' => $a['size'], 'created' => '', 'source' => 'art',
        'group' => $a['group'], 'alt' => $a['alt'], 'uses' => 0, 'usage' => [],
    ];
}

/**
 * Page of library items. $f: src (library|art), kind (image|video|''), q, unused (bool), group (art), page, per.
 * @return array ['items'=>[], 'total'=>int, 'page'=>int, 'pages'=>int, 'groups'=>[]]
 */
function media_list(array $f): array
{
    $per = max(6, min(96, (int)($f['per'] ?? 36)));
    $page = max(1, (int)($f['page'] ?? 1));
    $q = trim((string)($f['q'] ?? ''));
    $src = ($f['src'] ?? 'library') === 'art' ? 'art' : 'library';

    if ($src === 'art') {
        $groups = [];
        $all = [];
        foreach (media_art_items() as $a) {
            $groups[$a['group']] = ($groups[$a['group']] ?? 0) + 1;
            if (!empty($f['group']) && $a['group'] !== $f['group']) {
                continue;
            }
            if ($q !== '' && stripos($a['title'] . ' ' . $a['alt'] . ' ' . $a['group'], $q) === false) {
                continue;
            }
            $all[] = $a;
        }
        $total = count($all);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($page, $pages);
        return ['items' => array_map('media_art_public', array_slice($all, ($page - 1) * $per, $per)), 'total' => $total, 'page' => $page, 'pages' => $pages, 'groups' => $groups];
    }

    if (!media_table_ready()) {
        return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'groups' => []];
    }
    $where = ['1=1'];
    $args = [];
    if (in_array($f['kind'] ?? '', ['image', 'video'], true)) {
        $where[] = 'kind = :k';
        $args['k'] = $f['kind'];
    }
    if ($q !== '') {
        $where[] = '(title LIKE :q OR path LIKE :q)';
        $args['q'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    }
    // working out where each file is used scans many tables, so the picker (which does not show it) skips it
    $usage = !empty($f['unused']) || !empty($f['usage']) ? media_usage_map() : [];
    if (!empty($f['unused'])) {
        $all = rows('SELECT * FROM media_files WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC', $args);
        $all = array_values(array_filter($all, fn($r) => empty($usage[$r['path']])));
        $total = count($all);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($page, $pages);
        $slice = array_slice($all, ($page - 1) * $per, $per);
    } else {
        $total = (int)val('SELECT COUNT(*) FROM media_files WHERE ' . implode(' AND ', $where), $args, 0);
        $pages = max(1, (int)ceil($total / $per));
        $page = min($page, $pages);
        $slice = rows('SELECT * FROM media_files WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $args);
    }
    $items = array_map(fn($r) => media_item_public($r, $usage[$r['path']] ?? []), $slice);
    return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'groups' => []];
}

/**
 * Store a new upload in the library. Pictures are re-encoded (EXIF stripped); PNGs keep transparency only when they
 * have some; SVG logos are sanitised; MP4/WebM videos are accepted for the hero.
 * @return array ['ok'=>bool,'error'=>string,'item'=>array|null]
 */
function media_upload(array $file, int $adminId = 0): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => upload_error_text((int)($file['error'] ?? UPLOAD_ERR_NO_FILE)), 'item' => null];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']) ?: '';
    if (in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
        $res = save_video($file, 'library', 40);
    } else {
        $res = save_image($file, 'library', 2400, true, true, 10, true);
    }
    if (!$res['ok']) {
        return ['ok' => false, 'error' => $res['error'], 'item' => null];
    }
    $id = media_register($res['path'], ['title' => media_title_from_name((string)($file['name'] ?? '')), 'uploaded_by' => $adminId]);
    if ($id <= 0) {
        upload_delete($res['path']);
        return ['ok' => false, 'error' => 'The file was uploaded but could not be added to the library.', 'item' => null];
    }
    $r = row('SELECT * FROM media_files WHERE id = :id', ['id' => $id]);
    return ['ok' => true, 'error' => '', 'item' => media_item_public($r)];
}

/** Delete a library file, but only when nothing on the site uses it. @return array ['ok'=>bool,'error'=>string] */
function media_delete(int $id): array
{
    $r = row('SELECT * FROM media_files WHERE id = :id', ['id' => $id]);
    if (!$r) {
        return ['ok' => false, 'error' => 'That file is already gone.'];
    }
    $use = media_usage_map(true)[$r['path']] ?? [];
    if ($use) {
        $names = implode(', ', array_slice(array_column($use, 'label'), 0, 3));
        return ['ok' => false, 'error' => 'It is still used by ' . $names . (count($use) > 3 ? ' and ' . (count($use) - 3) . ' more' : '') . '. Replace it there first.'];
    }
    media_forget((string)$r['path']);
    $f = upload_abs((string)$r['path']);
    if (is_file($f)) {
        @unlink($f);
    }
    return ['ok' => true, 'error' => ''];
}

/** Delete every library file nothing uses. @return int number removed */
function media_delete_unused(): int
{
    if (!media_table_ready()) {
        return 0;
    }
    $usage = media_usage_map(true);
    $n = 0;
    foreach (rows('SELECT id, path FROM media_files') as $r) {
        if (empty($usage[$r['path']]) && media_delete((int)$r['id'])['ok']) {
            $n++;
        }
    }
    return $n;
}

function media_size_label(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }
    return max(1, (int)round($bytes / 1024)) . ' KB';
}

/** Can this admin see (view) or add to (edit) the media library? Anyone who can edit a page with pictures can. */
function media_admin_can(string $level = 'view'): bool
{
    foreach (['media', 'content', 'landing', 'settings', 'ads', 'locations', 'nav'] as $m) {
        if (admin_can($m, $level)) {
            return true;
        }
    }
    return false;
}
