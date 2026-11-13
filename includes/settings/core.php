<?php
/** Extra settings owned by the public-site/core builder. */
return [
    'seo' => [
        'Search visibility' => [
            'seo_noindex_site' => ['type' => 'bool', 'default' => 0, 'label' => 'Ask search engines not to index the site', 'help' => 'Turn on while you are still setting up. Adds noindex to every page and blocks crawlers in robots.txt.'],
        ],
    ],
];
