<?php
/**
 * Checks the article files in database/content/blog: required fields, length, headings, FAQs, links and banned words.
 *   php tools/blog_check.php            (all files)
 *   php tools/blog_check.php slug ...   (some files)
 */
declare(strict_types=1);

$dir = dirname(__DIR__) . '/database/content/blog';
$files = $argv[1] ?? null ? array_map(fn($s) => $dir . '/' . basename($s, '.php') . '.php', array_slice($argv, 1)) : glob($dir . '/*.php');
$cats = ['dating-tips', 'safety', 'community', 'profiles', 'relationships', 'kenya-guides'];
$slugs = array_map(fn($f) => basename($f, '.php'), glob($dir . '/*.php'));
$master = ['five-first-message-openers', 'how-to-start-a-conversation-online', 'first-date-ideas-in-nairobi', 'how-to-ask-someone-out-over-text', 'first-date-conversation-topics', 'signs-someone-likes-you-online', 'texting-rules-early-dating', 'how-to-handle-rejection-gracefully',
    'meet-someone-safely', 'how-to-spot-fake-profiles-and-catfishing', 'romance-scams-in-kenya-warning-signs', 'should-you-send-money-to-someone-you-met-online', 'protect-your-privacy-on-dating-apps', 'how-to-block-and-report-someone-online', 'how-to-verify-someone-is-real-before-meeting',
    'how-to-write-a-dating-profile-bio', 'best-dating-profile-photos', 'dating-profile-mistakes-to-avoid', 'why-profile-verification-matters',
    'green-flags-and-red-flags-in-early-dating', 'how-to-know-if-you-are-ready-to-date-again', 'define-the-relationship-talk', 'long-distance-relationship-tips', 'introducing-your-partner-to-family-in-kenya',
    'making-friends-in-a-new-town', 'how-to-make-friends-as-an-adult', 'feeling-lonely-in-the-city',
    'online-dating-in-kenya-complete-guide', 'dating-etiquette-in-kenya', 'meeting-people-abroad-safely', 'first-date-ideas-outside-nairobi'];
$slugs = array_unique(array_merge($slugs, $master));
$okPaths = ['/', '/register', '/login', '/browse', '/pricing', '/faq', '/premium', '/international', '/hosts', '/blog', '/safety-tips', '/about', '/community-guidelines', '/contact'];
$banned = ['guaranteed', 'sex ', 'sexual', 'hookup', 'hook-up', 'nudes', 'one-night', 'escort', 'sugar daddy', 'sugar mummy', 'sponsor', 'lorem'];
$bad = 0;
foreach ($files as $f) {
    $name = basename($f, '.php');
    $errs = [];
    if (!is_file($f)) { echo "MISSING $name\n"; $bad++; continue; }
    $a = require $f;
    if (!is_array($a)) { echo "BAD $name: does not return an array\n"; $bad++; continue; }
    foreach (['slug', 'title', 'category', 'excerpt', 'meta_title', 'meta_description', 'focus_keyword', 'tags', 'cover_alt', 'faq', 'content'] as $k) {
        if (!isset($a[$k]) || (is_string($a[$k]) && trim($a[$k]) === '') || (is_array($a[$k]) && !$a[$k])) { $errs[] = "missing $k"; }
    }
    if ($errs) { echo "FAIL $name: " . implode('; ', $errs) . "\n"; $bad++; continue; }
    if ($a['slug'] !== $name) { $errs[] = "slug '{$a['slug']}' must equal file name"; }
    if (!in_array($a['category'], $cats, true)) { $errs[] = 'category must be one of ' . implode(',', $cats); }
    $tl = mb_strlen($a['title']); if ($tl < 25 || $tl > 75) { $errs[] = "title is $tl chars (25-75)"; }
    $ml = mb_strlen($a['meta_title']); if ($ml < 30 || $ml > 60) { $errs[] = "meta_title is $ml chars (30-60)"; }
    $dl = mb_strlen($a['meta_description']); if ($dl < 120 || $dl > 158) { $errs[] = "meta_description is $dl chars (120-158)"; }
    $el = mb_strlen($a['excerpt']); if ($el < 60 || $el > 220) { $errs[] = "excerpt is $el chars (60-220)"; }
    $kw = mb_strtolower($a['focus_keyword']);
    foreach (['title' => $a['title'], 'meta_title' => $a['meta_title'], 'meta_description' => $a['meta_description']] as $k => $v) {
        if (mb_stripos($v, $kw) === false) { $errs[] = "focus keyword '$kw' not in $k"; }
    }
    $html = $a['content'];
    $text = trim(html_entity_decode(strip_tags($html)));
    $words = str_word_count($text);
    if ($words < 1100) { $errs[] = "only $words words (min 1100)"; }
    if ($words > 2400) { $errs[] = "$words words is too long (max 2400)"; }
    $first = mb_strtolower(strip_tags(substr($html, 0, strpos($html, '</p>') ?: 400)));
    if (mb_strpos($first, $kw) === false) { $errs[] = 'focus keyword missing from the first paragraph'; }
    preg_match_all('#<h2[^>]*>(.*?)</h2>#si', $html, $h2);
    if (count($h2[1]) < 5 || count($h2[1]) > 10) { $errs[] = count($h2[1]) . ' h2 headings (5-10)'; }
    $inH = false; foreach ($h2[1] as $h) { if (mb_stripos(strip_tags($h), $kw) !== false) { $inH = true; } }
    if (!$inH) { $errs[] = 'focus keyword (or exact phrase) not in any h2'; }
    foreach ($h2[1] as $h) { if (preg_match('/^\s*\d+[.)]/', strip_tags($h))) { $errs[] = "heading starts with a number: " . strip_tags($h); } }
    if (preg_match('#<h1|<script|<style|<img|<iframe#i', $html)) { $errs[] = 'h1/script/style/img/iframe not allowed in content'; }
    if (!preg_match('#<(ul|ol)>#i', $html)) { $errs[] = 'needs at least one list'; }
    preg_match_all('#href="([^"]+)"#i', $html, $hr);
    $internal = 0;
    foreach ($hr[1] as $href) {
        if (preg_match('#^/blog/([a-z0-9-]+)$#', $href, $m)) { $internal++; if (!in_array($m[1], $slugs, true) && !in_array($m[1], [], true)) { $errs[] = "link to unknown article $href"; } }
        elseif (in_array($href, $okPaths, true)) { $internal++; }
        elseif (preg_match('#^https?://#', $href)) { $errs[] = "external link $href (not allowed)"; }
        else { $errs[] = "unknown link $href"; }
    }
    if ($internal < 3) { $errs[] = "only $internal internal links (min 3)"; }
    if (!is_array($a['faq']) || count($a['faq']) < 4 || count($a['faq']) > 7) { $errs[] = 'faq needs 4-7 items'; }
    else { foreach ($a['faq'] as $q) { if (empty($q['q']) || empty($q['a']) || mb_strlen($q['a']) < 80 || mb_strlen($q['a']) > 700 || substr(trim($q['q']), -1) !== '?') { $errs[] = 'faq item needs a question ending in ? and an answer of 80-700 chars: ' . ($q['q'] ?? ''); } } }
    $tags = array_filter(array_map('trim', explode(',', $a['tags']))); if (count($tags) < 3 || count($tags) > 8) { $errs[] = 'tags: 3-8 comma separated'; }
    $low = mb_strtolower($text . ' ' . $a['title']);
    foreach ($banned as $w) { if (mb_strpos($low, $w) !== false) { $errs[] = "banned word/phrase '$w'"; } }
    if (preg_match('/\b\d{2,3}\s?%/', $text)) { $errs[] = 'percentage statistic found: do not invent statistics'; }
    if (preg_match('/\b(study|survey|research)\b[^.]{0,60}\b(shows?|found|finds|says)\b/i', $text)) { $errs[] = 'claims a study/survey: do not cite unverifiable research'; }
    if (preg_match('/\bhosts?\b/i', $text)) { $errs[] = 'do not use the word "host(s)" (the site renames it)'; }
    if (preg_match('/[—–]/u', $text . $a['title'] . $a['meta_description'])) { $errs[] = 'avoid em/en dashes, use commas or full stops'; }
    if ($errs) { $bad++; echo "FAIL $name ($words words):\n  - " . implode("\n  - ", array_unique($errs)) . "\n"; }
    else { echo "ok   $name ($words words, " . count($h2[1]) . ' sections, ' . count($a['faq']) . " faqs)\n"; }
}
exit($bad ? 1 : 0);
