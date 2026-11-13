<?php
/**
 * Data for the public landing page. All cache keys start with "landing_" so the admin
 * pages can clear them with cache_clear('landing_').
 */

declare(strict_types=1);

/** Built-in pictures for the landing page sections. The admin can replace, remove or pick another for each one. */
function landing_art_defaults(): array
{
    return [
        'features' => '@art/trust.webp', 'safety' => '@art/safety.webp', 'hosts' => '@art/hosts.webp', 'international' => '@art/international.webp', 'cta' => '@art/cta.webp', 'premium' => '@art/premium.webp',
        'steps' => ['@art/step-profile.webp', '@art/step-nearby.webp', '@art/step-hello.webp', '@art/step-hello.webp', '@art/step-nearby.webp', '@art/step-profile.webp'],
    ];
}

/**
 * Picture URL for a landing section ('' when the admin removed it). A section that has never been saved with a picture
 * field shows the built-in art; a saved empty value means "no picture".
 * $default is the "@art/..." path used when the key is missing from the section's content.
 */
function landing_image(array $c, string $default, string $field = 'image'): string
{
    $v = array_key_exists($field, $c) ? trim((string)$c[$field]) : $default;
    return $v === '' ? '' : upload_url($v);
}

/**
 * Does this picture have see-through corners (a cut-out with no background)? Looks at the four corners and the middle of
 * the top edge of PNG/WebP/GIF files. $rel is the stored value ("@art/…" or an upload path). Cached per file version.
 */
function landing_image_is_cutout(string $rel): bool
{
    $rel = trim($rel);
    if ($rel === '' || preg_match('#^https?://#i', $rel) || !function_exists('imagecreatefromstring')) {
        return false;
    }
    $file = upload_abs($rel);
    if (!is_file($file) || !preg_match('/\.(png|webp|gif)$/i', $file)) {
        return false;
    }
    $key = 'cutout_' . md5($file . '|' . (int)@filemtime($file) . '|' . (int)@filesize($file));
    return (bool)cache_remember($key, 3600, function () use ($file) {
        $raw = @file_get_contents($file, false, null, 0, 6 * 1024 * 1024);
        $im = $raw === false ? false : @imagecreatefromstring($raw);
        if (!$im) {
            return false;
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $hits = 0;
        $pts = [[2, 2], [$w - 3, 2], [2, $h - 3], [$w - 3, $h - 3], [(int)($w / 2), 2], [2, (int)($h / 2)], [$w - 3, (int)($h / 2)]];
        foreach ($pts as [$x, $y]) {
            $alpha = (imagecolorat($im, max(0, min($w - 1, $x)), max(0, min($h - 1, $y))) >> 24) & 0x7F; // 0 opaque … 127 clear
            if ($alpha >= 100) {
                $hits++;
            }
        }
        imagedestroy($im);
        return $hits >= 4;
    });
}

/** "cutout" or "photo": how the Concierges picture is fitted into its card. */
function landing_hosts_fit(array $c): string
{
    $mode = (string)($c['picture_style'] ?? 'auto');
    if ($mode === 'cutout' || $mode === 'photo') {
        return $mode;
    }
    $raw = array_key_exists('image', $c) ? (string)$c['image'] : landing_art_defaults()['hosts'];
    return landing_image_is_cutout($raw) ? 'cutout' : 'photo';
}

/**
 * What the hero looks like right now. The "collage" layout needs a clean page, so it is only used when no background
 * picture, slider or video is active; with one active it falls back to the simple layout over that background.
 */
function landing_hero_state(): array
{
    $bgType = (string)setting('hero_bg_type');
    $slides = $bgType === 'slider' ? landing_slides() : [];
    $single = in_array($bgType, ['image', 'parallax'], true) ? (string)setting('hero_image') : '';
    $video = $bgType === 'video' ? (string)setting('hero_video') : '';
    $hasMedia = $slides || $single !== '' || $video !== '';
    $layout = (string)setting('hero_layout');
    $layout = in_array($layout, ['collage', 'simple', 'split'], true) ? $layout : 'collage';
    if ($layout === 'collage' && $hasMedia) {
        $layout = 'simple';
    }
    $collage = $layout === 'collage';
    return [
        'bgType' => $bgType, 'slides' => $slides, 'single' => $single, 'video' => $video, 'hasMedia' => $hasMedia, 'layout' => $layout, 'collage' => $collage,
        // light text on a dark background; the collage sits on the page colour so it follows the theme instead
        'light' => !$collage && setting('hero_text_color') === 'light',
    ];
}

/** Cover picture for a blog post: its own cover, else built-in art that suits its category. Always returns a URL. */
function blog_cover_url(array $post): string
{
    $own = trim((string)($post['cover_path'] ?? ''));
    if ($own !== '') {
        return upload_url($own);
    }
    $hay = strtolower((string)($post['category'] ?? '') . ' ' . (string)($post['slug'] ?? '') . ' ' . (string)($post['title'] ?? ''));
    $pick = 'blog-generic';
    foreach (['safe' => 'blog-safety', 'scam' => 'blog-safety', 'privacy' => 'blog-safety', 'date' => 'blog-dating', 'love' => 'blog-dating', 'romance' => 'blog-dating', 'first' => 'blog-dating',
        'friend' => 'blog-community', 'communit' => 'blog-community', 'event' => 'blog-community', 'tip' => 'blog-tips', 'advice' => 'blog-tips', 'how' => 'blog-tips', 'guide' => 'blog-tips'] as $needle => $art) {
        if (strpos($hay, $needle) !== false) {
            $pick = $art;
            break;
        }
    }
    return upload_url('@art/' . $pick . '.webp');
}

/** Enabled sections in admin order, content decoded. */
function landing_sections(): array
{
    $rows = cache_remember('landing_sections', 120, fn() => rows('SELECT section_key, title, content FROM landing_sections WHERE is_enabled = 1 ORDER BY sort_order, id'));
    foreach ($rows as &$r) {
        $d = json_decode((string)$r['content'], true);
        $r['c'] = is_array($d) ? $d : [];
    }
    return $rows;
}

function landing_slides(): array
{
    // hero_slides_active() (includes/lib/admin_content.php) also applies each slide's start and end dates.
    return cache_remember('landing_slides', 120, fn() => function_exists('hero_slides_active')
        ? hero_slides_active()
        : rows('SELECT id, image_path, mobile_path, alt_text, overlay_opacity FROM hero_slides WHERE is_active = 1 ORDER BY sort_order, id'));
}

/** Members shown on the landing page, honouring the admin's "which profiles" choice. */
function landing_profiles(int $limit = 0): array
{
    $limit = $limit ?: (int)setting('landing_profiles_count');
    $order = (string)setting('landing_profiles_source');
    $order = in_array($order, ['newest', 'random', 'active', 'featured'], true) ? $order : 'newest';
    $filters = ['order' => $order, 'role' => 'user'];
    if (is_logged_in()) {
        $filters['order'] = 'smart';
    }
    $key = 'landing_profiles_' . $order . '_' . $limit . (is_logged_in() ? '_u' . auth_id() : (guest_location() ? '_g' . guest_location()['id'] : ''));
    if ($order === 'random') {
        return members_search($filters + ['seed' => (int)date('YmdH')], $limit, 0)['rows'];
    }
    return cache_remember($key, 120, fn() => members_search($filters, $limit, 0)['rows']);
}

/** The groups the "New faces" section can show, with the default wording for each tab. */
function landing_profile_groups_all(): array
{
    return [
        'newest' => ['Newest', 'The most recent members to join.'],
        'vip' => ['VIP and featured', 'Members on VIP, with an active boost, or featured by you.'],
        'suggested' => ['Suggested', 'Verified or well-filled profiles that were active lately.'],
        'regular' => ['Members', 'Everyone else, most recently active first.'],
    ];
}

/** Ids of the members in a group, for members_search(only_ids). Empty means nobody yet. */
function landing_group_ids(string $group, int $limit): array
{
    $base = "u.role = 'user' AND u.status = 'active'";
    $vip = "(COALESCE(u.featured_until > NOW(), 0) OR EXISTS (SELECT 1 FROM boosts b WHERE b.user_id = u.id AND b.expires_at > NOW())
        OR EXISTS (SELECT 1 FROM user_subscriptions s JOIN subscription_plans pl ON pl.id = s.plan_id WHERE s.user_id = u.id AND s.status = 'active' AND s.expires_at > NOW() AND pl.tier >= 2))";
    if ($group === 'vip') {
        return array_map('intval', array_column(rows("SELECT u.id FROM users u WHERE $base AND $vip ORDER BY u.last_active_at DESC LIMIT " . max(1, $limit * 3)), 'id'));
    }
    if ($group === 'regular') {
        return array_map('intval', array_column(rows("SELECT u.id FROM users u WHERE $base AND NOT $vip ORDER BY u.last_active_at DESC LIMIT " . max(1, $limit * 4)), 'id'));
    }
    return [];
}

/**
 * The groups to show in the "New faces" section: [['key', 'label', 'members']...]. Groups are switched on in Admin > Landing page
 * > New faces. Sections saved before groups existed fall back to the older settings (Landing page > Featured profiles).
 */
function landing_profile_groups(array $c): array
{
    $all = landing_profile_groups_all();
    $on = [];
    foreach (array_keys($all) as $k) {
        if (!empty($c['group_' . $k])) {
            $on[] = $k;
        }
    }
    $limit = max(2, min(36, (int)($c['count'] ?? 0) ?: (int)setting('landing_profiles_count')));
    if (!$on) {
        $m = landing_profiles($limit);
        return $m ? [['key' => 'newest', 'label' => '', 'members' => $m]] : [];
    }
    $first = (string)($c['first_group'] ?? '');
    if (in_array($first, $on, true)) {
        $on = array_merge([$first], array_diff($on, [$first]));
    }
    $viewer = is_logged_in() ? '_u' . auth_id() : (guest_location() ? '_g' . guest_location()['id'] : '');
    $out = [];
    foreach ($on as $k) {
        $label = trim((string)($c['label_' . $k] ?? '')) ?: $all[$k][0];
        $fill = !array_key_exists('fill_vip', $c) || !empty($c['fill_vip']);
        $members = cache_remember('landing_pgroup_' . $k . '_' . $limit . ($fill ? 'f' : 'n') . $viewer, 120, function () use ($k, $limit, $fill) {
            $f = ['role' => 'user'];
            if ($k === 'newest') {
                return members_search($f + ['order' => 'newest'], $limit, 0)['rows'];
            }
            if ($k === 'suggested') {
                $f += ['order' => is_logged_in() ? 'score' : 'smart', 'active_days' => 14, 'has_bio' => true];
                $rows = members_search($f, $limit, 0)['rows'];
                if (count($rows) < $limit) { // not enough well-filled profiles: add verified ones
                    $more = members_search(['role' => 'user', 'verified' => true, 'order' => 'active', 'exclude_ids' => array_column($rows, 'id') ?: [0]], $limit - count($rows), 0)['rows'];
                    $rows = array_merge($rows, $more);
                }
                return $rows;
            }
            $ids = landing_group_ids($k, $limit);
            $rows = $ids ? members_search($f + ['only_ids' => $ids, 'order' => $k === 'vip' ? 'featured' : 'active'], $limit, 0)['rows'] : [];
            // too few VIP or featured members (or none yet): follow with other active members so the group is never empty
            if ($k === 'vip' && $fill && count($rows) < $limit) {
                $more = members_search($f + ['order' => 'active', 'exclude_ids' => array_column($rows, 'id') ?: [0]], $limit - count($rows), 0)['rows'];
                $rows = array_merge($rows, $more);
            }
            return $rows;
        });
        if ($members) {
            $out[] = ['key' => $k, 'label' => $label, 'members' => $members];
        }
    }
    return $out;
}

/** Member counts per county / town for the "browse by place" tiles. */
function landing_location_counts(): array
{
    return cache_remember('landing_locations_counts', 600, function () {
        // the same people a visitor sees when they open the place on the Browse page (members only, not incognito, with an approved photo)
        $base = "FROM user_locations ul JOIN users u ON u.id = ul.user_id JOIN user_profiles p ON p.user_id = u.id " . sql_photo_join('u.id', 'ph') . "
                 WHERE u.status = 'active' AND u.role = 'user' AND p.onboarding_completed = 1 AND p.is_paused = 0 AND p.elite_only = 0 AND p.is_incognito = 0 AND ph.id IS NOT NULL";
        return [
            'county' => pairs("SELECT ul.county_id, COUNT(*) $base AND ul.county_id IS NOT NULL GROUP BY ul.county_id"),
            'town' => pairs("SELECT ul.location_id, COUNT(*) $base AND ul.location_id IS NOT NULL GROUP BY ul.location_id"),
        ];
    });
}

/**
 * Built-in picture for a place when the admin has not set one: by name first, then by region.
 * Returns an "@art/..." path (see upload_url()).
 */
function location_art_path(array $p): string
{
    $slug = (string)($p['slug'] ?? '');
    $byName = [
        'nairobi' => 'town-nairobi', 'mombasa' => 'town-mombasa', 'nyali' => 'town-mombasa', 'kisumu' => 'town-kisumu', 'nakuru' => 'town-nakuru',
        'nyeri' => 'town-nyeri', 'nanyuki' => 'town-mountain', 'meru' => 'town-mountain', 'embu' => 'town-mountain', 'kirinyaga' => 'town-mountain', 'laikipia' => 'town-mountain',
        'diani-beach' => 'town-diani', 'diani' => 'town-diani', 'malindi' => 'town-diani', 'watamu' => 'town-diani', 'kwale' => 'town-diani',
        'kilifi' => 'town-coast', 'lamu' => 'town-coast', 'tana-river' => 'town-coast', 'taita-taveta' => 'town-savanna', 'voi' => 'town-savanna',
        'naivasha' => 'town-rift', 'nakuru-town' => 'town-nakuru', 'narok' => 'town-savanna', 'kajiado' => 'town-savanna', 'maasai-mara' => 'town-savanna',
        'kisii' => 'town-hills', 'kakamega' => 'town-hills', 'bungoma' => 'town-hills', 'vihiga' => 'town-hills', 'busia' => 'town-lake', 'homa-bay' => 'town-lake', 'migori' => 'town-lake', 'siaya' => 'town-lake',
        'kericho' => 'town-hills', 'bomet' => 'town-hills', 'nandi' => 'town-hills', 'trans-nzoia' => 'town-hills', 'kiambu' => 'town-hills', 'thika' => 'town-hills', 'muranga' => 'town-hills', 'nyandarua' => 'town-hills',
    ];
    if (isset($byName[$slug])) {
        return '@art/' . $byName[$slug] . '.webp';
    }
    $byRegion = ['Nairobi' => 'town-city', 'Coast' => 'town-coast', 'Nyanza' => 'town-lake', 'Western' => 'town-hills', 'Central' => 'town-hills',
        'Rift Valley' => 'town-rift', 'Eastern' => 'town-savanna', 'North Eastern' => 'town-savanna'];
    return '@art/' . ($byRegion[(string)($p['region'] ?? '')] ?? 'town-city') . '.webp';
}

/** Picture URL for a town card: the admin's own image, else the built-in art for that kind of place. */
function location_image_url(array $p): string
{
    $own = trim((string)($p['image_path'] ?? ''));
    return upload_url($own !== '' ? $own : location_art_path($p));
}

/**
 * Tiles for the "locations" section. $c is the section's content:
 *   show = counties|towns|mixed, count, source = picked (places ticked "Show on landing page") | popular | busiest,
 *   only_members = hide places nobody has joined from yet.
 */
function landing_location_tiles(array $c): array
{
    $show = (string)($c['show'] ?? 'mixed');
    $count = max(1, min(48, (int)($c['count'] ?? 12)));
    $source = in_array((string)($c['source'] ?? ''), ['picked', 'popular', 'busiest'], true) ? (string)$c['source'] : 'picked';
    $only = !empty($c['only_members']);
    $key = 'landing_loc_tiles_' . md5(json_encode([$show, $count, $source, $only]));
    try {
        return landing_location_tiles_load($key, $show, $count, $source, $only);
    } catch (Throwable $e) {
        error_log('landing_location_tiles: ' . $e->getMessage());
        return [];
    }
}

function landing_location_tiles_load(string $key, string $show, int $count, string $source, bool $only): array
{
    return cache_remember($key, 120, function () use ($show, $count, $source, $only) {
        $types = $show === 'counties' ? ['county'] : ($show === 'towns' ? ['town'] : ['county', 'town']);
        $where = 'is_active = 1 AND type IN (:t)';
        if ($source === 'picked') {
            $where .= ' AND show_landing = 1';
        } elseif ($source === 'popular') {
            $where .= ' AND is_popular = 1';
        }
        $places = rows('SELECT id, type, name, slug, parent_id, region, image_path FROM locations WHERE ' . $where . ' ORDER BY sort_order, name LIMIT 300', ['t' => $types]);
        $counts = landing_location_counts();
        foreach ($places as &$p) {
            $p['members'] = (int)($counts[$p['type']][$p['id']] ?? 0);
        }
        unset($p);
        if ($only) {
            $places = array_values(array_filter($places, fn($p) => $p['members'] > 0));
        }
        if ($source === 'busiest') {
            usort($places, fn($a, $b) => $b['members'] <=> $a['members'] ?: strcmp($a['name'], $b['name']));
        }
        $places = array_slice($places, 0, $count);
        foreach ($places as &$p) {
            $p['img'] = location_image_url($p);
        }
        return $places;
    });
}

function landing_testimonials(int $limit = 6): array
{
    return cache_remember('landing_testimonials', 300, fn() => rows('SELECT name, location, quote, rating, photo_path FROM testimonials WHERE is_active = 1 AND show_landing = 1 ORDER BY sort_order, id LIMIT ' . $limit));
}

function landing_faqs(int $limit = 5): array
{
    return cache_remember('landing_faqs_' . $limit, 300, fn() => rows('SELECT question, answer FROM faqs WHERE is_active = 1 ORDER BY sort_order, id LIMIT ' . $limit));
}

function landing_blog(int $limit = 3): array
{
    return cache_remember('landing_blog_' . $limit, 300, fn() => rows(
        "SELECT b.title, b.slug, b.excerpt, b.cover_path, b.published_at, c.name AS category FROM blog_posts b
         LEFT JOIN blog_categories c ON c.id = b.category_id
         WHERE b.status = 'published' AND b.published_at <= NOW() ORDER BY b.published_at DESC LIMIT " . $limit));
}

/** CSS custom properties for the hero overlay, from the admin's Hero & header settings. */
function hero_overlay_style(): string
{
    $type = (string)setting('hero_overlay_type');
    $rgb = implode(' ', hex_to_rgb((string)setting('hero_overlay_color') ?: '#0F0E0D'));
    $start = max(0, min(100, (int)setting('hero_overlay_start'))) / 100;
    $end = max(0, min(100, (int)setting('hero_overlay_end'))) / 100;
    if ($type === 'none') {
        $start = $end = 0;
    } elseif ($type === 'solid') {
        $end = $start;
    }
    return '--hero-ov:' . $rgb . ';--hero-ov-bottom:' . $start . ';--hero-ov-top:' . $end . ';';
}

/** Highlights of a plan for the pricing teasers (plain language). */
function plan_highlights(array $plan, int $max = 5): array
{
    $f = plan_features_decoded($plan);
    $out = [];
    $n = fn($k) => (int)($f[$k] ?? 0);
    $out[] = $n('daily_swipes') === -1 ? 'Unlimited likes' : $n('daily_swipes') . ' likes a day';
    $out[] = $n('daily_messages') === -1 ? 'Unlimited messages' : $n('daily_messages') . ' messages a day';
    if ($n('daily_superlikes') !== 0) { $out[] = $n('daily_superlikes') === -1 ? 'Unlimited super likes' : $n('daily_superlikes') . ' super likes a day'; }
    if (!empty($f['see_who_liked'])) { $out[] = 'See who likes you'; }
    if (!empty($f['advanced_filters'])) { $out[] = 'Advanced filters'; }
    if (!empty($f['profile_visitors'])) { $out[] = 'See who viewed you'; }
    if (!empty($f['message_before_match'])) { $out[] = 'Message before you match'; }
    if (!empty($f['incognito'])) { $out[] = 'Browse in incognito'; }
    if (!empty($f['read_receipts'])) { $out[] = 'Read receipts'; }
    if ($n('dm_credits_monthly') > 0) { $out[] = $n('dm_credits_monthly') . ' message credits a month'; }
    if ($n('boost_credits_monthly') > 0) { $out[] = $n('boost_credits_monthly') . ' boost' . ($n('boost_credits_monthly') > 1 ? 's' : '') . ' a month'; }
    if (!empty($f['no_ads'])) { $out[] = 'No adverts'; }
    $out[] = ($n('max_photos') ?: 6) . ' photos';
    return array_slice($out, 0, $max);
}

/** Section heading block used by every landing section. $c is the section's decoded content. */
function lp_head(array $c, string $actions = ''): string
{
    $t = trim((string)($c['title'] ?? ''));
    $s = trim((string)($c['subtitle'] ?? ''));
    if ($t === '' && $s === '') {
        return '';
    }
    $h = '<div class="lp-head">' . ($t !== '' ? '<h2 class="lp-title">' . e($t) . '</h2>' : '') . ($s !== '' ? '<p class="lp-sub">' . e($s) . '</p>' : '') . '</div>';
    return $actions === '' ? $h : '<div class="lp-head-row">' . $h . $actions . '</div>';
}

/** Link button from admin-entered text + url; opens the sign-up modal for /register links. */
function lp_button(string $text, string $url, string $class = 'btn btn-primary btn-lg'): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $auth = preg_match('#^/(register|login)(\?|$)#', $url, $m) ? ' data-auth-open="' . $m[1] . '"' : '';
    return '<a href="' . e(nav_url($url)) . '" class="' . e($class) . '"' . $auth . '>' . e($text) . '</a>';
}
