<?php
/**
 * Boost and featured packs, and the rules around them. Prices and lengths always come from boost_packs on the server.
 */

declare(strict_types=1);

function boost_enabled(): bool
{
    return (bool)setting('boost_enabled');
}

function featured_enabled(): bool
{
    return (bool)setting('featured_enabled');
}

/** Minutes one plan credit is worth. */
function boost_default_minutes(): int
{
    return max(5, (int)setting('boost_credit_minutes'));
}

/** Packs for sale, in the order the admin set. $kind is 'boost' or 'featured'. */
function boost_packs(string $kind, bool $activeOnly = true): array
{
    return rows('SELECT * FROM boost_packs WHERE kind = :k' . ($activeOnly ? ' AND is_active = 1 AND price > 0' : '') . ' ORDER BY sort_order, price, id', ['k' => $kind]);
}

function boost_pack(int $id, string $kind): ?array
{
    return row('SELECT * FROM boost_packs WHERE id = :id AND kind = :k', ['id' => $id, 'k' => $kind]);
}

/** "30 minutes", "2 hours", "1 day", "3 days". */
function boost_length_label(int $minutes, string $kind = 'boost'): string
{
    if ($minutes >= 1440 && $minutes % 1440 === 0) {
        return plural((int)($minutes / 1440), 'day');
    }
    if ($minutes >= 60 && $minutes % 60 === 0) {
        return plural((int)($minutes / 60), 'hour');
    }
    return plural($minutes, 'minute');
}

/** Can this member start another boost now? Returns [ok, message]. Applies to credits and purchases alike. */
function boost_can_start(int $userId): array
{
    if (!boost_enabled()) {
        return [false, 'Boosts are not available right now.'];
    }
    if (!(int)setting('boost_stack') && user_is_boosted($userId)) {
        return [false, 'Your boost is still running. You can start another once it ends.'];
    }
    $limit = (int)setting('boost_daily_limit');
    if ($limit > 0) {
        $n = (int)val("SELECT COUNT(*) FROM boosts WHERE user_id = :u AND source IN ('plan','purchase') AND started_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)", ['u' => $userId], 0);
        if ($n >= $limit) {
            return [false, 'You can start up to ' . plural($limit, 'boost') . ' a day. Try again tomorrow.'];
        }
    }
    return [true, ''];
}
