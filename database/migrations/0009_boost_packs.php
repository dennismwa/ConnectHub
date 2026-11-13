<?php
/**
 * Boost and featured packs: the admin sets any number of lengths and prices instead of one price.
 * The old single price and length become the first packs so nothing changes for members until the admin edits them.
 */
return function (): void {
    mig_exec("CREATE TABLE IF NOT EXISTS boost_packs (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        kind        ENUM('boost','featured') NOT NULL DEFAULT 'boost',
        name        VARCHAR(60) NOT NULL,
        minutes     INT UNSIGNED NOT NULL,
        price       DECIMAL(10,2) NOT NULL,
        badge       VARCHAR(30) NULL,
        is_active   TINYINT(1) NOT NULL DEFAULT 1,
        sort_order  INT NOT NULL DEFAULT 0,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_boost_packs (kind, is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    if ((int)val('SELECT COUNT(*) FROM boost_packs', [], 0) === 0) {
        $get = fn(string $k, float $d) => (float)(val('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $k]) ?? $d);
        $bp = max(0.0, $get('boost_price', 50));
        $bm = max(5, (int)$get('boost_minutes', 30));
        $fp = max(0.0, $get('featured_price', 299));
        $fd = max(1, (int)$get('featured_days', 7));
        $rows = [
            ['boost', 'Quick boost', $bm, $bp, null, 1],
            ['boost', 'Evening boost', $bm * 4, round($bp * 3), 'Popular', 2],
            ['boost', 'All day', 1440, round($bp * 10), 'Best value', 3],
            ['featured', 'Featured week', $fd * 1440, $fp, null, 1],
            ['featured', 'Featured month', 30 * 1440, round($fp * 3), 'Best value', 2],
        ];
        foreach ($rows as [$kind, $name, $min, $price, $badge, $order]) {
            insert('boost_packs', ['kind' => $kind, 'name' => $name, 'minutes' => $min, 'price' => $price, 'badge' => $badge, 'sort_order' => $order, 'is_active' => $price > 0 ? 1 : 0]);
        }
        // the old boost length is also the length of one plan credit
        mig_setting_default('boost_credit_minutes', (string)$bm);
    }
    foreach (['boost_enabled' => '1', 'featured_enabled' => '1', 'boost_daily_limit' => '0', 'boost_stack' => '1'] as $k => $v) {
        mig_setting_default($k, $v);
    }
};
