<?php
/**
 * Image and file pipeline (GD). Every upload is re-encoded, so EXIF data and any
 * hidden payloads are stripped and only real images are stored.
 *
 * Photos: random file names under uploads/photos/YYYY/MM/. Four copies are made:
 * large (1600px), medium (800px), thumb (320px square) and blur (48px, heavily
 * blurred; the original detail cannot be recovered from it) plus a 16px data URI.
 */

declare(strict_types=1);

const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
const MAX_PIXELS = 40000000;

function upload_abs(string $rel): string
{
    // "@art/name.webp" is a built-in illustration shipped with the site (assets/images/art), not an upload.
    if (strncmp($rel, '@art/', 5) === 0) {
        return ROOT . '/assets/images/art/' . basename(substr($rel, 5));
    }
    return ROOT . '/uploads/' . ltrim($rel, '/');
}

function upload_ensure_dir(string $sub): string
{
    $dir = ROOT . '/uploads/' . trim($sub, '/');
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create upload folder.');
    }
    return $dir;
}

function upload_error_text(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'That file is too large. The server allows up to ' . ini_get('upload_max_filesize') . '.';
        case UPLOAD_ERR_PARTIAL:
            return 'The upload was interrupted. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'Choose a file to upload.';
        default:
            return 'The upload failed. Please try again.';
    }
}

/** Normalise a $_FILES entry that may hold multiple files into a list of single-file arrays. */
function files_list(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f) {
        return [];
    }
    if (!is_array($f['name'])) {
        return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/** Returns an error message, or '' when the file is an acceptable image. */
function image_upload_problem(array $file, int $maxMb = 8, int $minSide = 0, bool $allowSvg = false): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return upload_error_text((int)($file['error'] ?? UPLOAD_ERR_NO_FILE));
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
        return 'Invalid upload.';
    }
    if ($file['size'] > $maxMb * 1048576) {
        return 'That image is larger than ' . $maxMb . ' MB.';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if ($allowSvg && ($mime === 'image/svg+xml' || ($mime === 'text/xml' || $mime === 'text/plain') && stripos((string)file_get_contents($file['tmp_name'], false, null, 0, 1024), '<svg') !== false)) {
        return svg_sanitize((string)file_get_contents($file['tmp_name'])) === '' ? 'That SVG file could not be used.' : '';
    }
    if (!isset(IMAGE_MIMES[$mime])) {
        return 'Use a JPG, PNG or WebP image' . ($allowSvg ? ' (or SVG)' : '') . '.';
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) {
        return 'That file is not a valid image.';
    }
    if ($info[0] * $info[1] > MAX_PIXELS) {
        return 'That image has too many pixels. Resize it and try again.';
    }
    if ($minSide > 0 && min($info[0], $info[1]) < $minSide) {
        return 'The image is too small. Use one that is at least ' . $minSide . ' pixels on its shortest side.';
    }
    return '';
}

/** Load an image with EXIF rotation applied. */
function image_load(string $path)
{
    @ini_set('memory_limit', '384M');
    $info = @getimagesize($path);
    if (!$info) {
        throw new RuntimeException('Unreadable image');
    }
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $img = imagecreatefromjpeg($path);
            break;
        case IMAGETYPE_PNG:
            $img = imagecreatefrompng($path);
            break;
        case IMAGETYPE_WEBP:
            $img = imagecreatefromwebp($path);
            break;
        case IMAGETYPE_GIF:
            $img = imagecreatefromgif($path);
            break;
        default:
            throw new RuntimeException('Unsupported image type');
    }
    if (!$img) {
        throw new RuntimeException('Could not read image');
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $o = (int)($exif['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle) {
            $rot = imagerotate($img, $angle, 0);
            if ($rot) {
                $img = $rot;
            }
        }
    }
    imagepalettetotruecolor($img);
    return $img;
}

/** Scale to fit inside $max x $max (or crop to a square when $square). Never enlarges. */
function image_fit($img, int $max, bool $square = false)
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($square) {
        $side = min($w, $h);
        $sx = (int)(($w - $side) / 2);
        $sy = (int)(($h - $side) / 2);
        $dst = imagecreatetruecolor($max, $max);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $img, 0, 0, $sx, $sy, $max, $max, $side, $side);
        return $dst;
    }
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, true);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}

function image_save_jpeg($img, string $path, int $quality): void
{
    imageinterlace($img, true);
    if (!imagejpeg($img, $path, max(40, min(95, $quality)))) {
        throw new RuntimeException('Could not save image');
    }
    @chmod($path, 0664);
}

/** Heavily blurred, tiny copy used for visitors. Detail cannot be recovered from it. */
function image_make_blur($img, string $path): string
{
    $small = image_fit($img, 48);
    for ($i = 0; $i < 6; $i++) {
        imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
    }
    imagefilter($small, IMG_FILTER_SELECTIVE_BLUR);
    image_save_jpeg($small, $path, 55);
    // 16px data URI for instant placeholders
    $tiny = image_fit($img, 16);
    for ($i = 0; $i < 3; $i++) {
        imagefilter($tiny, IMG_FILTER_GAUSSIAN_BLUR);
    }
    ob_start();
    imagejpeg($tiny, null, 50);
    $data = (string)ob_get_clean();
    return 'data:image/jpeg;base64,' . base64_encode($data);
}

/**
 * Process an uploaded profile photo.
 * @return array ['ok'=>bool,'error'=>string,'data'=>array of user_photos columns]
 */
function process_profile_photo(array $file, int $userId, int $maxMb = 0): array
{
    $maxMb = $maxMb > 0 ? $maxMb : max(1, (int)setting('photo_max_mb'));
    $problem = image_upload_problem($file, $maxMb, 300);
    if ($problem !== '') {
        return ['ok' => false, 'error' => $problem, 'data' => []];
    }
    try {
        $sub = 'photos/' . date('Y') . '/' . date('m');
        upload_ensure_dir($sub);
        $base = $sub . '/' . $userId . '_' . random_token(10);
        $img = image_load($file['tmp_name']);
        $q = max(60, min(92, (int)setting('photo_quality')));
        $large = image_fit($img, 1600);
        image_save_jpeg($large, upload_abs($base . '_l.jpg'), $q);
        $medium = image_fit($img, 800);
        image_save_jpeg($medium, upload_abs($base . '_m.jpg'), $q);
        $thumb = image_fit($img, 320, true);
        image_save_jpeg($thumb, upload_abs($base . '_t.jpg'), max(60, $q - 8));
        $dataUri = image_make_blur($img, upload_abs($base . '_b.jpg'));
        $w = imagesx($large);
        $h = imagesy($large);
        return ['ok' => true, 'error' => '', 'data' => [
            'large_path' => $base . '_l.jpg', 'medium_path' => $base . '_m.jpg', 'thumb_path' => $base . '_t.jpg',
            'blur_path' => $base . '_b.jpg', 'blur_data_uri' => $dataUri, 'width' => $w, 'height' => $h,
            'file_size' => (int)filesize(upload_abs($base . '_l.jpg')),
        ]];
    } catch (Throwable $e) {
        app_log('error', 'photo processing failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'We could not process that image. Try a different photo.', 'data' => []];
    }
}

/** Add a processed photo to a member's gallery. Returns the photo id. */
function photo_add(int $userId, array $data, bool $makePrimaryIfFirst = true): int
{
    $count = (int)val('SELECT COUNT(*) FROM user_photos WHERE user_id = :u', ['u' => $userId]);
    $status = setting('photo_moderation') === 'manual' ? 'pending' : 'approved';
    $data += [
        'user_id' => $userId, 'is_primary' => ($makePrimaryIfFirst && $count === 0) ? 1 : 0,
        'sort_order' => $count, 'moderation_status' => $status,
    ];
    $id = insert('user_photos', $data);
    photo_refresh_completion($userId);
    return $id;
}

function photo_delete(int $photoId, int $userId): bool
{
    $p = row('SELECT * FROM user_photos WHERE id = :id AND user_id = :u', ['id' => $photoId, 'u' => $userId]);
    if (!$p) {
        return false;
    }
    delete_rows('user_photos', ['id' => $photoId]);
    foreach (['large_path', 'medium_path', 'thumb_path', 'blur_path'] as $k) {
        if (!empty($p[$k])) {
            @unlink(upload_abs($p[$k]));
        }
    }
    if ((int)$p['is_primary'] === 1) { // promote the next photo
        $next = val("SELECT id FROM user_photos WHERE user_id = :u ORDER BY (moderation_status = 'approved') DESC, sort_order, id LIMIT 1", ['u' => $userId]);
        if ($next) {
            q('UPDATE user_photos SET is_primary = 1 WHERE id = :id', ['id' => $next]);
        }
    }
    photo_refresh_completion($userId);
    return true;
}

function photo_set_primary(int $photoId, int $userId): bool
{
    $p = row('SELECT id FROM user_photos WHERE id = :id AND user_id = :u', ['id' => $photoId, 'u' => $userId]);
    if (!$p) {
        return false;
    }
    q('UPDATE user_photos SET is_primary = IF(id = :id, 1, 0) WHERE user_id = :u', ['id' => $photoId, 'u' => $userId]);
    return true;
}

/** Recompute profile completion if the profile module has defined it. */
function photo_refresh_completion(int $userId): void
{
    if (function_exists('profile_recalculate_completion')) {
        profile_recalculate_completion($userId);
    }
}

// ---------------------------------------------------------------------------
// Site images (logos, hero, OG, ads, blog, banners)
// ---------------------------------------------------------------------------

/**
 * Save an admin- or member-supplied image under uploads/$sub, re-encoded.
 * PNG/WebP transparency is kept when $keepAlpha. SVG allowed when $allowSvg (sanitised).
 * @return array ['ok'=>bool,'error'=>string,'path'=>relative path]
 */
function save_image(array $file, string $sub, int $maxSide = 1920, bool $keepAlpha = false, bool $allowSvg = false, int $maxMb = 6, bool $smartAlpha = false): array
{
    $problem = image_upload_problem($file, $maxMb, 0, $allowSvg);
    if ($problem !== '') {
        return ['ok' => false, 'error' => $problem, 'path' => ''];
    }
    try {
        upload_ensure_dir($sub);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        $name = trim($sub, '/') . '/' . random_token(8);
        if (!isset(IMAGE_MIMES[$mime])) { // SVG
            $svg = svg_sanitize((string)file_get_contents($file['tmp_name']));
            file_put_contents(upload_abs($name . '.svg'), $svg);
            return ['ok' => true, 'error' => '', 'path' => $name . '.svg'];
        }
        $img = image_load($file['tmp_name']);
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $maxSide / max($w, $h));
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        // $smartAlpha: keep a PNG only when the picture really has transparent areas, otherwise a smaller JPEG is stored.
        if ($keepAlpha && in_array($mime, ['image/png', 'image/webp', 'image/gif'], true) && (!$smartAlpha || image_has_alpha($img))) {
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagepng($dst, upload_abs($name . '.png'), 7);
            @chmod(upload_abs($name . '.png'), 0664);
            return ['ok' => true, 'error' => '', 'path' => $name . '.png'];
        }
        $dst = image_fit($img, $maxSide);
        image_save_jpeg($dst, upload_abs($name . '.jpg'), 86);
        return ['ok' => true, 'error' => '', 'path' => $name . '.jpg'];
    } catch (Throwable $e) {
        app_log('error', 'save_image failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'We could not process that image.', 'path' => ''];
    }
}

/** Does the picture have transparent pixels? Samples a grid (borders included), which is enough for logos and cut-outs. */
function image_has_alpha($img): bool
{
    $w = imagesx($img);
    $h = imagesy($img);
    $n = 48;
    for ($yi = 0; $yi <= $n; $yi++) {
        $y = (int)min($h - 1, round($yi * ($h - 1) / $n));
        for ($xi = 0; $xi <= $n; $xi++) {
            $x = (int)min($w - 1, round($xi * ($w - 1) / $n));
            if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 8) {
                return true;
            }
        }
    }
    return false;
}

function svg_sanitize(string $svg): string
{
    if ($svg === '' || stripos($svg, '<svg') === false || strlen($svg) > 500000) {
        return '';
    }
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $ok = stripos($svg, '<!ENTITY') === false && stripos($svg, '<!DOCTYPE') === false && $dom->loadXML($svg, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$ok || !$dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
        return '';
    }
    $xp = new DOMXPath($dom);
    foreach (['script', 'foreignObject', 'iframe', 'object', 'embed', 'audio', 'video', 'animate', 'set', 'use', 'image', 'a'] as $tag) {
        foreach (iterator_to_array($xp->query('//*[local-name()="' . $tag . '"]')) as $n) {
            $n->parentNode->removeChild($n);
        }
    }
    foreach (iterator_to_array($xp->query('//@*')) as $attr) {
        $n = strtolower($attr->nodeName);
        $v = strtolower(trim($attr->nodeValue));
        if (strpos($n, 'on') === 0 || (in_array($n, ['href', 'xlink:href'], true) && ($v[0] ?? '') !== '#') || strpos($v, 'javascript:') !== false || strpos($v, 'url(http') !== false || strpos($v, '@import') !== false) {
            $attr->ownerElement->removeAttributeNode($attr);
        }
    }
    foreach (iterator_to_array($xp->query('//*[local-name()="style"]')) as $style) {
        if (preg_match('/@import|url\s*\(|expression|javascript:/i', $style->textContent)) {
            $style->parentNode->removeChild($style);
        }
    }
    return (string)$dom->saveXML($dom->documentElement);
}

/**
 * Delete a stored upload (relative path). Ignores missing files, paths outside /uploads, built-in art, and any file that
 * is kept in the admin media library: those are only ever deleted from the Media page (media_delete()).
 */
function upload_delete(?string $rel): void
{
    if (!$rel || strpos($rel, '..') !== false || strncmp($rel, '@art/', 5) === 0) {
        return;
    }
    if (function_exists('media_is_registered') && media_is_registered($rel)) {
        return;
    }
    $f = upload_abs($rel);
    if (is_file($f)) {
        @unlink($f);
    }
}

/** Short video upload (host intro). Checks type and size only. */
function save_video(array $file, string $sub, int $maxMb = 20): array
{
    if (($file['error'] ?? 4) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => upload_error_text((int)($file['error'] ?? 4)), 'path' => ''];
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        return ['ok' => false, 'error' => 'Invalid upload.', 'path' => ''];
    }
    if ((int)($file['size'] ?? 0) <= 0 || $file['size'] > $maxMb * 1048576) {
        return ['ok' => false, 'error' => 'That video is larger than ' . $maxMb . ' MB.', 'path' => ''];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mp4'][$mime] ?? '';
    if ($ext === '') {
        return ['ok' => false, 'error' => 'Use an MP4 or WebM video.', 'path' => ''];
    }
    upload_ensure_dir($sub);
    $rel = trim($sub, '/') . '/' . random_token(8) . '.' . $ext;
    if (!(PHP_SAPI === 'cli' ? copy($file['tmp_name'], upload_abs($rel)) : move_uploaded_file($file['tmp_name'], upload_abs($rel)))) {
        return ['ok' => false, 'error' => 'Could not save the video.', 'path' => ''];
    }
    return ['ok' => true, 'error' => '', 'path' => $rel];
}
