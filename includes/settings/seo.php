<?php
/** SEO settings. Merged into the SEO & social tab by settings_schema(). Most of these are managed on Admin > SEO. */
return [
    'seo' => [
        'Search results' => [
            'seo_title_template' => ['type' => 'text', 'default' => '%page% · %site%', 'max' => 120,
                'label' => 'Page title pattern', 'help' => '%page% is the page name and %site% is your site name. Search engines show about 60 characters, so keep page names short.'],
            'seo_default_description' => ['type' => 'textarea', 'default' => '', 'max' => 300,
                'label' => 'Default search description', 'help' => 'Used on pages that have none. Aim for 120 to 155 characters that say what people get.'],
            'seo_locale' => ['type' => 'select', 'default' => 'en_KE', 'options' => ['en_KE' => 'English (Kenya)', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'en_UG' => 'English (Uganda)', 'en_TZ' => 'English (Tanzania)', 'sw_KE' => 'Kiswahili (Kenya)'],
                'label' => 'Language and country', 'help' => 'Tells search engines who the site is for.'],
            'seo_blog_author' => ['type' => 'text', 'default' => '', 'max' => 80,
                'label' => 'Author name on articles', 'help' => 'Shown on every article and in search data. Leave empty to use the site name.'],
        ],
        'Business details (shown to search engines)' => [
            'seo_org_legal_name' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Registered business name', 'help' => 'Optional. Builds trust and fills the organisation card.'],
            'seo_org_phone' => ['type' => 'text', 'default' => '', 'max' => 30, 'label' => 'Business phone', 'help' => 'With country code, for example +254700000000.'],
            'seo_org_email' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Business email', 'help' => ''],
            'seo_org_city' => ['type' => 'text', 'default' => 'Nairobi', 'max' => 60, 'label' => 'City', 'help' => ''],
            'seo_org_region' => ['type' => 'text', 'default' => 'Nairobi County', 'max' => 60, 'label' => 'County or region', 'help' => ''],
            'seo_org_country' => ['type' => 'text', 'default' => 'KE', 'max' => 2, 'label' => 'Country code', 'help' => 'Two letters, for example KE.'],
        ],
        'Verify your site' => [
            'seo_google_verification' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Google Search Console code', 'help' => 'In Search Console choose HTML tag and paste only the content value, the long code.'],
            'seo_bing_verification' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Bing Webmaster code', 'help' => 'The content value of the msvalidate.01 tag.'],
            'seo_yandex_verification' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Yandex code', 'help' => 'Optional.'],
            'seo_pinterest_verification' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Pinterest code', 'help' => 'Optional.'],
            'seo_facebook_verification' => ['type' => 'text', 'default' => '', 'max' => 120, 'label' => 'Facebook domain code', 'help' => 'Optional.'],
            'seo_gtm_id' => ['type' => 'text', 'default' => '', 'max' => 20, 'label' => 'Google Tag Manager ID', 'help' => 'For example GTM-XXXXXXX. Loaded only after visitors accept cookies.'],
        ],
        'Crawling and indexing' => [
            'seo_force_https' => ['type' => 'bool', 'default' => 0, 'label' => 'Send everyone to the https:// address', 'help' => 'Turn on once your certificate works. Search engines then see only one version of each page.'],
            'seo_www' => ['type' => 'select', 'default' => 'keep', 'options' => ['keep' => 'Leave as it is', 'add' => 'Always use www.', 'remove' => 'Never use www.'],
                'label' => 'www or no www', 'help' => 'Pick one so Google does not treat them as two sites. Your certificate must cover both.'],
            'seo_noindex_members' => ['type' => 'bool', 'default' => 1, 'label' => 'Keep member areas out of search', 'help' => 'Recommended. Keeps private pages such as messages and settings out of Google.'],
            'seo_ai_crawlers' => ['type' => 'select', 'default' => 'allow', 'options' => ['allow' => 'Allow AI search and assistants', 'block' => 'Block AI training crawlers'],
                'label' => 'AI crawlers', 'help' => 'Blocking adds the main AI training crawlers to robots.txt. Google and Bing search are never blocked.'],
            'seo_indexnow_enabled' => ['type' => 'bool', 'default' => 1, 'label' => 'Tell Bing and others when content changes (IndexNow)', 'help' => 'New and edited articles are sent to search engines straight away.'],
            'seo_indexnow_key' => ['type' => 'text', 'default' => '', 'label' => 'IndexNow key', 'help' => 'Created for you the first time it is needed. Leave it as it is.'],
            'seo_log_404' => ['type' => 'bool', 'default' => 1, 'label' => 'Keep a list of broken links people hit', 'help' => 'See them under SEO > Redirects and fix them with one click.'],
        ],
        'Sitemap and feeds' => [
            'seo_sitemap_images' => ['type' => 'bool', 'default' => 1, 'label' => 'Include pictures in the sitemap', 'help' => 'Helps article pictures show in Google Images.'],
            'seo_sitemap_locations' => ['type' => 'bool', 'default' => 1, 'label' => 'Include county and town pages', 'help' => 'Pages such as /browse/nairobi can rank for local searches.'],
            'seo_sitemap_hosts' => ['type' => 'bool', 'default' => 1, 'label' => 'Include the directory page', 'help' => 'Only when the directory is open to everyone.'],
            'seo_rss_enabled' => ['type' => 'bool', 'default' => 1, 'label' => 'Blog feed (RSS)', 'help' => 'Publishes /blog/feed.xml.'],
        ],
        'Structured data' => [
            'seo_schema_org' => ['type' => 'bool', 'default' => 1, 'label' => 'Organisation card', 'help' => 'Your name, logo and social profiles for Google.'],
            'seo_schema_sitelinks' => ['type' => 'bool', 'default' => 1, 'label' => 'Site search box', 'help' => 'Lets Google offer a search box under your result.'],
            'seo_schema_breadcrumbs' => ['type' => 'bool', 'default' => 1, 'label' => 'Breadcrumbs', 'help' => 'Shows a tidy path instead of a long address in results.'],
        ],
    ],
];
