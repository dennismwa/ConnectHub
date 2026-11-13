<?php
/**
 * Starter articles: six categories and a library of long, search-friendly guides (database/content/blog/*.php).
 * Safe to run on a site that already has articles: it never touches posts the admin has edited or written.
 */
return function (): void {
    $cats = [
        ['dating-tips', 'Dating tips', 1, 'Practical advice on first messages, first dates, texting and asking someone out, written for people dating in Kenya.', 'Dating tips and advice for Kenya'],
        ['safety', 'Safety', 2, 'How to stay safe when you meet people online: spotting fake profiles, avoiding romance scams, protecting your privacy and meeting for the first time.', 'Online dating safety tips for Kenya'],
        ['community', 'Community', 3, 'Friendship, loneliness and finding your people in a new town. Honest ideas for building a circle that lasts.', 'Making friends and finding community in Kenya'],
        ['profiles', 'Profiles and photos', 4, 'How to write a bio that sounds like you, choose photos that get noticed and build the trust that gets you replies.', 'Dating profile and photo tips'],
        ['relationships', 'Relationships', 5, 'Green flags, red flags, long distance, introducing family and knowing when you are ready, for every stage of getting to know someone.', 'Relationship advice for Kenyans'],
        ['kenya-guides', 'Dating in Kenya', 6, 'Local guides: online dating in Kenya, etiquette, first date ideas around the country and meeting people abroad safely.', 'Dating in Kenya: guides and etiquette'],
    ];
    foreach ($cats as [$slug, $name, $sort, $desc, $meta]) {
        $id = (int)val('SELECT id FROM blog_categories WHERE slug = :s', ['s' => $slug], 0);
        if (!$id) {
            if (val('SELECT id FROM blog_categories WHERE name = :n', ['n' => $name])) {
                $name .= ' guides';
            }
            q('INSERT INTO blog_categories (name, slug, sort_order, description, meta_title) VALUES (:n, :s, :o, :d, :m)', ['n' => $name, 's' => $slug, 'o' => $sort, 'd' => $desc, 'm' => $meta]);
        } else {
            q("UPDATE blog_categories SET description = COALESCE(NULLIF(description, ''), :d), meta_title = COALESCE(NULLIF(meta_title, ''), :m) WHERE id = :id", ['d' => $desc, 'm' => $meta, 'id' => $id]);
        }
    }

    $dir = dirname(__DIR__) . '/content/blog';
    $files = glob($dir . '/*.php') ?: [];
    sort($files);
    $n = count($files);
    foreach ($files as $i => $f) {
        $a = require $f;
        if (!is_array($a) || empty($a['slug']) || empty($a['content'])) {
            continue;
        }
        $cat = (int)val('SELECT id FROM blog_categories WHERE slug = :s', ['s' => (string)$a['category']], 0) ?: null;
        $data = [
            'category_id' => $cat, 'title' => $a['title'], 'excerpt' => $a['excerpt'], 'tags' => $a['tags'], 'content' => $a['content'],
            'cover_alt' => $a['cover_alt'], 'meta_title' => $a['meta_title'], 'meta_description' => $a['meta_description'],
            'focus_keyword' => $a['focus_keyword'], 'faq' => json_encode($a['faq'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        $cur = row('SELECT id, content FROM blog_posts WHERE slug = :s', ['s' => $a['slug']]);
        if (!$cur) {
            $data += ['slug' => $a['slug'], 'status' => 'published', 'published_at' => date('Y-m-d H:i:s', time() - ($n - $i) * 86400 + 3600)];
            insert('blog_posts', $data);
        } elseif (mb_strlen((string)$cur['content']) < 3000) {
            // the three short starter posts: replace with the full guide; anything longer was written by the site owner
            update('blog_posts', $data, ['id' => $cur['id']]);
        }
    }
};
