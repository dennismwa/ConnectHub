<?php
/**
 * Concierge search: what Google and WhatsApp show for the Concierge section, and the words on the page itself.
 *
 *   host_seo_directory($category)   title, description, keywords, heading, about text for /hosts and /hosts/category/x
 *   host_seo_profile(...)           title, description and keywords for one profile, from the admin's patterns
 *   host_seo_faq_items()            the questions and answers (the admin's, or a helpful starter set)
 *   host_seo_health()               the checklist and score shown in Admin > Concierge search
 *   host_seo_suggestions()          keyword ideas built from the real categories and places
 *
 * Everything an admin types is stored in seo_pages (page_key 'hosts'), host_categories.seo_* and site_settings host_seo_*.
 * The section is spelled "Host" in code and re-labelled to the admin's chosen name; these helpers write the chosen name
 * directly, so a title never mixes the two.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Words and keyword lists
// ---------------------------------------------------------------------------

/** "a, b\nc; A" => ['a', 'b', 'c']. Trimmed, de-duplicated (ignoring case), at most 40 of up to 60 characters. */
function host_seo_keyword_list(string $raw): array
{
    $out = [];
    $seen = [];
    foreach (preg_split('/[\r\n,;]+/u', $raw) ?: [] as $p) {
        $p = trim(preg_replace('/\s+/u', ' ', (string)$p) ?? '', " \t.-");
        $k = mb_strtolower($p);
        if ($p === '' || mb_strlen($p) > 60 || isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $out[] = $p;
        if (count($out) >= 40) {
            break;
        }
    }
    return $out;
}

/** The same list as one line, ready to store or show: "a, b, c". */
function host_seo_keyword_text(string $raw): string
{
    return implode(', ', host_seo_keyword_list($raw));
}

/** The words every Concierge page carries (set in Admin > Concierge search). */
function host_seo_section_keywords(): array
{
    return host_seo_keyword_list((string)setting('host_seo_keywords'));
}

/** What a pattern can use: token => what it stands for. */
function host_seo_tokens(): array
{
    return [
        '{name}' => 'The person\'s name',
        '{first_name}' => 'Their first name',
        '{category}' => 'Their category, for example Dinner company',
        '{town}' => 'Their town',
        '{county}' => 'Their county',
        '{place}' => 'Their town, or their county when no town is set',
        '{concierge}' => 'What you call them, one person (' . host_term() . ')',
        '{concierges}' => 'What you call them, many people (' . host_term(true) . ')',
        '{site}' => 'Your site name',
        '{tagline}' => 'Their one-line headline',
        '{about}' => 'The start of their "about" text',
    ];
}

/** Fill a pattern and tidy what is left when a part is empty: "Amina,  in " becomes "Amina". */
function host_seo_fill(string $pattern, array $vars): string
{
    $map = [];
    foreach (host_seo_tokens() as $tok => $_) {
        $key = trim($tok, '{}');
        $map[$tok] = trim((string)($vars[$key] ?? ''));
    }
    $s = strtr($pattern, $map);
    $s = preg_replace('/\{[a-z_]+\}/i', '', $s) ?? $s;               // a token that does not exist
    $s = preg_replace('/\s+(in|at|near|from)\s*(?=[,.:;|\-]|$)/iu', '', $s) ?? $s;   // "in" with nothing after it
    $s = preg_replace('/,\s+(in|at|near|from)\b/iu', ' $1', $s) ?? $s;   // "Amina, in Westlands" when the part before it was empty
    $s = preg_replace('/\s+([,.:;])/u', '$1', $s) ?? $s;             // space before a comma
    $s = preg_replace('/([,.:;])(\s*[,.:;])+/u', '$1', $s) ?? $s;    // doubled punctuation
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
    return rtrim(ltrim($s, " \t,.:;|-"), " \t,:;|-");
}

// ---------------------------------------------------------------------------
// The directory (and the age-check page that stands in for it)
// ---------------------------------------------------------------------------

/** The wording used when the admin has typed nothing. */
function host_seo_directory_defaults(?array $category = null): array
{
    $many = host_term(true);
    $site = site_name();
    $country = trim((string)setting('site_country')) ?: 'Kenya';
    $booking = function_exists('hosts_booking_enabled') && hosts_booking_enabled();
    if ($category) {
        $cat = (string)$category['name'];
        return [
            'title' => $cat . ' ' . $many,
            'description' => ($booking ? 'Book a ' : 'Browse an adult ') . mb_strtolower($cat) . ' ' . mb_strtolower(host_term()) . ' in ' . $country . '. ID-verified profiles for permitted companionship, public events and conversation.',
            'heading' => $cat,
            'intro' => '',
        ];
    }
    return [
        'title' => 'Verified ' . $many . ' in ' . $country,
        'description' => $booking
            ? 'Book friendly, ID-verified ' . mb_strtolower($many) . ' in ' . $country . ' for permitted companionship, public events and conversation. Meet in public places and pay the booking deposit on the platform.'
            : 'Browse adult, ID-verified ' . mb_strtolower($many) . ' in ' . $country . ' for non-sexual companionship, public events and conversation. View approved profiles and contact directly; bookings and payments are not processed on this site.',
        'heading' => 'Adult, verified ' . $many . ' for permitted companionship and events',
        'intro' => 'Browse adult, ID-verified ' . mb_strtolower($many) . ' in ' . $country . ' for non-sexual companionship, public events and conversation. Every profile on ' . $site . ' is approved before it goes live. This directory does not process bookings or payments. Review the profile rules, choose a contact option and keep safety in mind.',
    ];
}

/** The admin's saved search text for the Concierge page (seo_pages row 'hosts'), empty strings where nothing is set. */
function host_seo_saved(): array
{
    $o = function_exists('seo_overrides') ? (seo_overrides()['hosts'] ?? []) : [];
    return [
        'title' => trim((string)($o['title'] ?? '')), 'description' => trim((string)($o['description'] ?? '')),
        'keywords' => trim((string)($o['keywords'] ?? '')), 'heading' => trim((string)($o['heading'] ?? '')),
        'intro' => trim((string)($o['intro'] ?? '')), 'og_image' => trim((string)($o['og_image'] ?? '')),
        'noindex' => (int)($o['noindex'] ?? 0),
    ];
}

/**
 * Everything the directory page needs for its head and its text. $category is a row from hosts_categories() or null.
 * Returns title, description, keywords (a "a, b, c" line), heading, intro (paragraphs separated by a blank line) and noindex.
 */
function host_seo_directory(?array $category = null): array
{
    $def = host_seo_directory_defaults($category);
    if ($category) {
        $title = trim((string)($category['seo_title'] ?? '')) ?: $def['title'];
        $desc = trim((string)($category['seo_description'] ?? '')) ?: $def['description'];
        $own = (string)($category['seo_keywords'] ?? '');
        $heading = $def['heading'];
        $intro = trim((string)($category['seo_intro'] ?? ''));
        $noindex = 0;
    } else {
        $s = host_seo_saved();
        $title = $s['title'] ?: $def['title'];
        $desc = $s['description'] ?: $def['description'];
        $own = $s['keywords'];
        $heading = $s['heading'] ?: $def['heading'];
        $intro = $s['intro'] ?: $def['intro'];
        $noindex = $s['noindex'];
    }
    $auto = [host_term(true, true) . ' ' . (trim((string)setting('site_country')) ?: 'Kenya')];
    $words = host_seo_keyword_list($own . ',' . implode(',', host_seo_section_keywords()) . ',' . implode(',', $auto));
    return [
        'title' => $title, 'description' => $desc, 'heading' => $heading, 'intro' => $intro,
        'keywords' => implode(', ', array_slice($words, 0, 15)), 'noindex' => $noindex,
    ];
}

/** True when search engines may index the age-check or sign-in page that stands in for the directory. */
function host_seo_gate_indexable(string $reason): bool
{
    return setting('host_seo_index_gate') && in_array($reason, ['age', 'login'], true)
        && (string)setting('hosts_directory_access') === 'everyone' && !host_seo_saved()['noindex'];
}

/** The paragraphs of an "about" text as an array (blank line between paragraphs). */
function host_seo_paragraphs(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/u', trim($text)) ?: []), fn($p) => $p !== ''));
}

// ---------------------------------------------------------------------------
// Questions and answers
// ---------------------------------------------------------------------------

/** "Question line\nAnswer lines\n\nNext question..." => [['q', 'a'], ...]. At most 12. */
function host_seo_faq_parse(string $raw): array
{
    $items = [];
    foreach (preg_split('/\n\s*\n/', trim(str_replace("\r", '', $raw))) ?: [] as $block) {
        $lines = explode("\n", trim($block), 2);
        $q = trim((string)($lines[0] ?? ''));
        $a = trim((string)($lines[1] ?? ''));
        if ($q !== '' && $a !== '') {
            $items[] = ['q' => mb_substr($q, 0, 160), 'a' => mb_substr(trim(preg_replace('/[ \t]+/', ' ', $a) ?? $a), 0, 900)];
        }
        if (count($items) >= 12) {
            break;
        }
    }
    return $items;
}

/** The starter questions, in the admin's name for Hosts. Used until the admin writes their own. */
function host_seo_faq_default(): array
{
    $one = mb_strtolower(host_term());
    $many = mb_strtolower(host_term(true));
    $site = site_name();
    $A = fn(string $s) => ucfirst($s);
    return [
        ['q' => 'What is ' . host_article($one) . ' ' . $one . ' on ' . $site . '?', 'a' => $A(host_article($one)) . ' ' . $one . ' is an approved, ID-verified adult who offers friendly company: dinners, city tours, event plus-ones, travel and conversation. Open a profile to see what they offer and how to get in touch.'],
        ['q' => 'Is it free to join ' . $site . '?', 'a' => 'Yes. Creating an account on ' . $site . ' is free. If ' . host_article($one) . ' ' . $one . ' charges for their time, they say so on their own profile.'],
        ['q' => 'How are ' . $many . ' checked?', 'a' => 'Every ' . $one . ' is approved before their profile goes live, and ID and photo checks earn a verified badge you can see on the profile.'],
        ['q' => 'Is it only friendly company?', 'a' => 'Yes. ' . $site . ' is for friendly company: dinners, tours, events and conversation. Sexual services are not allowed and lead to removal.'],
        ['q' => 'How do I stay safe when I meet ' . host_article($one) . ' ' . $one . '?', 'a' => 'Meet in a public place, tell a friend where you are going, look for the verified badge and never send money to someone you have not met. You can report any profile from its page.'],
    ];
}

/** The questions to show: the admin's, otherwise the starter set. Empty when the admin switched them off. */
function host_seo_faq_items(): array
{
    if (!setting('host_seo_faq_show')) {
        return [];
    }
    $own = host_seo_faq_parse((string)setting('host_seo_faq'));
    return $own ?: host_seo_faq_default();
}

// ---------------------------------------------------------------------------
// A profile's search text
// ---------------------------------------------------------------------------

/**
 * Title, description and keywords for one profile from the admin's patterns.
 * $v: name, category (name or ''), town, county, tagline, about. Returns ['title', 'description', 'keywords'].
 */
function host_seo_profile(array $v): array
{
    $name = trim((string)($v['name'] ?? ''));
    $town = trim((string)($v['town'] ?? ''));
    $county = trim((string)($v['county'] ?? ''));
    $vars = [
        'name' => $name, 'first_name' => (string)(explode(' ', $name)[0] ?? ''), 'category' => trim((string)($v['category'] ?? '')),
        'town' => $town, 'county' => $county, 'place' => $town !== '' ? $town : $county,
        'concierge' => host_term(), 'concierges' => host_term(true), 'site' => site_name(),
        'tagline' => trim((string)($v['tagline'] ?? '')),
        'about' => str_limit(trim(preg_replace('/\s+/u', ' ', (string)($v['about'] ?? '')) ?? ''), 150),
    ];
    $tp = trim((string)setting('host_seo_profile_title'));
    $title = host_seo_fill($tp !== '' ? $tp : '{name}, {category} in {place}', $vars);
    if ($title === '') {
        $title = $name !== '' ? $name : host_term();
    }
    $dp = trim((string)setting('host_seo_profile_desc'));
    $desc = $dp !== ''
        ? host_seo_fill($dp, $vars)
        : trim(($vars['tagline'] !== '' ? rtrim($vars['tagline'], '. ') . '. ' : '') . preg_replace('/\s+/u', ' ', (string)($v['about'] ?? '')));
    $desc = str_limit($desc, 190);
    $keywords = '';
    if (setting('host_seo_profile_keywords')) {
        $place = $vars['place'];
        $own = [$name];
        if ($vars['category'] !== '') {
            $own[] = mb_strtolower($vars['category']) . ($place !== '' ? ' ' . mb_strtolower(host_term()) . ' ' . $place : '');
        }
        $own[] = mb_strtolower(host_term()) . ($place !== '' ? ' in ' . $place : ' Kenya');
        $keywords = implode(', ', array_slice(host_seo_keyword_list(implode(',', $own) . ',' . implode(',', host_seo_section_keywords())), 0, 12));
    }
    return ['title' => $title, 'description' => $desc, 'keywords' => $keywords];
}

// ---------------------------------------------------------------------------
// Keyword ideas
// ---------------------------------------------------------------------------

/** Ideas for the keywords box, built from the categories and places that really have people. Up to 18. */
function host_seo_suggestions(): array
{
    $one = mb_strtolower(host_term());
    $many = mb_strtolower(host_term(true));
    $ideas = [$many . ' in Kenya', 'verified ' . $many, $many . ' near me', 'friendly ' . $one . ' Nairobi', $one . ' for dinner', 'city tour ' . $one, 'event plus one Nairobi'];
    try {
        foreach (rows("SELECT c.name, COUNT(*) n FROM host_profiles hp JOIN host_categories c ON c.id = hp.category_id JOIN users u ON u.id = hp.user_id
                       WHERE hp.approval_status = 'approved' AND u.status = 'active' GROUP BY c.id ORDER BY n DESC, c.name LIMIT 5") as $r) {
            $ideas[] = mb_strtolower((string)$r['name']) . ' ' . $one;
        }
        foreach (rows("SELECT COALESCE(NULLIF(l.town_name, ''), l.county_name) AS p, COUNT(*) n FROM host_profiles hp JOIN users u ON u.id = hp.user_id JOIN user_locations l ON l.user_id = hp.user_id
                       WHERE hp.approval_status = 'approved' AND u.status = 'active' AND COALESCE(NULLIF(l.town_name, ''), l.county_name) IS NOT NULL GROUP BY p ORDER BY n DESC, p LIMIT 5") as $r) {
            $ideas[] = $many . ' in ' . $r['p'];
        }
    } catch (Throwable $e) {
    }
    $have = array_map('mb_strtolower', host_seo_section_keywords());
    $out = [];
    foreach (host_seo_keyword_list(implode(',', $ideas)) as $k) {
        if (!in_array(mb_strtolower($k), $have, true)) {
            $out[] = $k;
        }
    }
    return array_slice($out, 0, 18);
}

// ---------------------------------------------------------------------------
// The checklist
// ---------------------------------------------------------------------------

/** [[status ok|warn|bad, title, detail, anchor], ...] for Admin > Concierge search. */
function host_seo_health(): array
{
    $out = [];
    $add = function (string $s, string $t, string $d, string $a) use (&$out) {
        $out[] = [$s, $t, $d, $a];
    };
    $d = host_seo_directory(null);
    $s = host_seo_saved();
    $one = mb_strtolower(host_term());
    $many = mb_strtolower(host_term(true));
    $has = fn(string $hay) => mb_stripos($hay, $one) !== false || mb_stripos($hay, $many) !== false;

    $open = function_exists('hosts_enabled') && hosts_enabled();
    $access = (string)setting('hosts_directory_access');
    $hidden = setting('seo_noindex_site') || $s['noindex'];
    if (!$open) {
        $add('bad', 'The section is switched off', 'Nothing here is shown to visitors or search engines until the Concierge section is turned on under Settings.', 'visibility');
    } elseif ($hidden) {
        $add('bad', 'Hidden from search engines', setting('seo_noindex_site') ? 'The whole site is set to noindex. Turn that off at launch.' : 'The Concierge page is set to "Hide this page from search engines".', 'visibility');
    } elseif ($access !== 'everyone') {
        $add('warn', 'Only some members can open the directory', 'Search engines cannot see pages that need a sign-in or a paid plan, so the section cannot rank. Set directory access to everyone to let it be found.', 'visibility');
    } elseif (setting('hosts_age_gate') && !setting('host_seo_index_gate')) {
        $add('warn', 'The 18+ page is hidden from search', 'Visitors confirm their age first, so search engines only see that page. Allow it to be indexed so it can carry your text.', 'visibility');
    } else {
        $add('ok', 'Search engines can find the section', setting('hosts_age_gate') ? 'The 18+ confirmation page carries your title, description and text, so it can rank.' : 'The directory is open and listed in the sitemap.', 'visibility');
    }

    $tl = mb_strlen($d['title']);
    $add($tl >= 20 && $tl <= 60 ? 'ok' : 'warn', 'Search title', $tl . ' characters. ' . ($tl >= 20 && $tl <= 60 ? 'A good length.' : ($tl < 20 ? 'Short. Aim for 30 to 60 and include what people search for.' : 'Long. Google cuts it after about 60.')), 'page');
    $dl = mb_strlen($d['description']);
    $add($dl >= 110 && $dl <= 160 ? 'ok' : 'warn', 'Search description', $dl . ' characters. ' . ($dl >= 110 && $dl <= 160 ? 'A good length.' : ($dl < 110 ? 'Short. Aim for 120 to 155 and say what people get.' : 'Long. Google cuts it after about 155.')), 'page');
    $add($has($d['title']) ? 'ok' : 'warn', 'The title says what they are', $has($d['title']) ? 'It contains "' . host_term(true) . '".' : 'Add the word "' . host_term(true) . '" so searchers see it.', 'page');
    $add($has($d['heading']) ? 'ok' : 'warn', 'The page heading says it too', $has($d['heading']) ? 'The big heading contains the name.' : 'Put "' . host_term(true) . '" in the heading so the page is clearly about them.', 'page');

    $kw = host_seo_keyword_list($s['keywords'] . ',' . implode(',', host_seo_section_keywords()));
    $add(count($kw) >= 6 ? 'ok' : (count($kw) >= 3 ? 'warn' : 'bad'), 'Keywords', count($kw) . ' set. ' . (count($kw) >= 6 ? 'Good coverage.' : 'Add words people type, such as "' . $many . ' in Nairobi". Aim for 6 to 12.'), 'keywords');

    $words = str_word_count(strip_tags((string)($s['intro'] ?: '')));
    $add($words >= 80 ? 'ok' : ($words >= 30 ? 'warn' : 'bad'), 'Text on the page', $s['intro'] === '' ? 'Using the built-in introduction. Write your own: 80 words or more helps a page rank.' : $words . ' words. ' . ($words >= 80 ? 'Good.' : 'Add a little more, aim for 80 or more.'), 'page');

    $faq = count(host_seo_faq_parse((string)setting('host_seo_faq')));
    $faqShown = setting('host_seo_faq_show');
    $add($faqShown && $faq >= 3 ? 'ok' : ($faqShown ? 'warn' : 'warn'), 'Questions and answers', !$faqShown ? 'Switched off. They can earn extra space in Google results.' : ($faq >= 3 ? $faq . ' questions, also marked up for Google.' : 'Showing the starter questions. Write 3 or more of your own about your section.'), 'faq');

    $hasOg = $s['og_image'] !== '' || trim((string)setting('og_image')) !== '';
    $add($hasOg ? 'ok' : 'warn', 'Share picture', $hasOg ? ($s['og_image'] !== '' ? 'This page has its own.' : 'Using the site picture.') : 'Add a 1200 x 630 picture so links look good on WhatsApp and Facebook.', 'page');

    try {
        $cats = (int)val('SELECT COUNT(*) FROM host_categories WHERE is_active = 1', [], 0);
        $done = (int)val("SELECT COUNT(*) FROM host_categories WHERE is_active = 1 AND COALESCE(seo_title, '') <> '' AND COALESCE(seo_description, '') <> ''", [], 0);
        if ($cats > 0) {
            $add($done === $cats ? 'ok' : ($done > 0 ? 'warn' : 'warn'), 'Category pages', $done . ' of ' . $cats . ' have their own title and description.', 'categories');
        }
    } catch (Throwable $e) {
    }

    $tp = (string)setting('host_seo_profile_title');
    $add(strpos($tp, '{place}') !== false || strpos($tp, '{town}') !== false || strpos($tp, '{county}') !== false ? 'ok' : 'warn', 'Profile titles include the place', 'Pattern: ' . ($tp !== '' ? $tp : '{name}, {category} in {place}') . '. A place in the title helps people searching for "' . $many . ' in Nairobi".', 'profiles');
    return $out;
}

/** Share of OK checks, 0 to 100 (a warning counts half). */
function host_seo_score(array $checks): int
{
    $t = 0.0;
    foreach ($checks as [$s]) {
        $t += $s === 'ok' ? 1 : ($s === 'warn' ? 0.5 : 0);
    }
    return $checks ? (int)round($t / count($checks) * 100) : 0;
}

// ---------------------------------------------------------------------------
// Saving (the page does the picture and the writing; this checks the words)
// ---------------------------------------------------------------------------

/** Clean what the admin typed for the Concierge page. Returns [data, errors]. */
function host_seo_clean_page(array $b): array
{
    $one = fn(string $k, int $max) => trim(preg_replace('/\s+/u', ' ', (string)($b[$k] ?? '')) ?? '');
    $d = ['title' => $one('title', 120), 'description' => $one('description', 300), 'heading' => $one('heading', 160), 'keywords' => host_seo_keyword_text((string)($b['keywords'] ?? '')),
        'intro' => trim(preg_replace("/[ \t]+/", ' ', str_replace("\r", '', (string)($b['intro'] ?? ''))) ?? ''), 'noindex' => !empty($b['noindex']) ? 1 : 0];
    $e = [];
    if (mb_strlen($d['title']) > 120) {
        $e['title'] = 'The title can be up to 120 characters. Google shows about 60.';
    }
    if (mb_strlen($d['description']) > 300) {
        $e['description'] = 'The description can be up to 300 characters. Google shows about 155.';
    }
    if (mb_strlen($d['heading']) > 160) {
        $e['heading'] = 'Keep the heading under 160 characters.';
    }
    if (mb_strlen($d['keywords']) > 500) {
        $e['keywords'] = 'That is too many keywords. Keep to about 12 short phrases.';
    }
    if (mb_strlen($d['intro']) > 2500) {
        $e['intro'] = 'Keep the text under 2,500 characters.';
    }
    return [$d, $e];
}

/** Clean one category's search text. Returns [data, errors]. */
function host_seo_clean_category(array $b): array
{
    $one = fn(string $k) => trim(preg_replace('/\s+/u', ' ', (string)($b[$k] ?? '')) ?? '');
    $d = ['seo_title' => $one('title'), 'seo_description' => $one('description'), 'seo_keywords' => host_seo_keyword_text((string)($b['keywords'] ?? '')),
        'seo_intro' => trim(preg_replace("/[ \t]+/", ' ', str_replace("\r", '', (string)($b['intro'] ?? ''))) ?? '')];
    $e = [];
    if (mb_strlen($d['seo_title']) > 120) {
        $e['title'] = 'The title can be up to 120 characters.';
    }
    if (mb_strlen($d['seo_description']) > 300) {
        $e['description'] = 'The description can be up to 300 characters.';
    }
    if (mb_strlen($d['seo_keywords']) > 500) {
        $e['keywords'] = 'That is too many keywords.';
    }
    if (mb_strlen($d['seo_intro']) > 1500) {
        $e['intro'] = 'Keep the text under 1,500 characters.';
    }
    foreach ($d as $k => $v) {
        $d[$k] = $v !== '' ? $v : null;
    }
    return [$d, $e];
}

// ---------------------------------------------------------------------------
// Structured data
// ---------------------------------------------------------------------------

/** JSON-LD for the directory (or one category of it): the page, its words and its questions. Null when switched off. */
function host_seo_directory_schema(array $sd, ?array $category, array $faq, string $path): ?array
{
    if (!setting('host_seo_schema')) {
        return null;
    }
    $page = ['@type' => 'CollectionPage', 'name' => $category ? (string)$category['name'] . ' ' . host_term(true) : host_term(true), 'description' => $sd['description'],
        'url' => abs_url($path), 'inLanguage' => str_replace('_', '-', (string)setting('seo_locale') ?: 'en_KE'),
        'isPartOf' => ['@type' => 'WebSite', 'name' => site_name(), 'url' => abs_url('/')]];
    if ($sd['keywords'] !== '') {
        $page['keywords'] = $sd['keywords'];
    }
    $nodes = [$page];
    if ($faq && function_exists('seo_faq_schema') && ($f = seo_faq_schema($faq))) {
        unset($f['@context']);
        $nodes[] = $f;
    }
    return ['@context' => 'https://schema.org', '@graph' => $nodes];
}
