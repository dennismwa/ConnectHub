<?php
/**
 * Adverts. Admin manages them under /admin/ads; pages call ad_slot('discover') where a slot belongs.
 * Free members see ads when ads_enabled is on; plans with no_ads hide them.
 */

declare(strict_types=1);


/** Record an ad event without storing an IP address or raw session identifier. */
function ad_track_event(int $adId, string $event, string $placement = ''): void
{
    if ($adId <= 0 || !in_array($event, ['impression', 'click', 'story_view'], true)) { return; }
    try {
        $u = current_user();
        $identity = $u ? 'user:' . (int)$u['id'] : 'session:' . session_id();
        if ($identity === 'session:') { return; }
        $visitor = hash('sha256', $identity);
        q('INSERT INTO advertisement_events (advertisement_id, event_type, placement, visitor_key) VALUES (:a, :e, :p, :v)', [
            'a' => $adId, 'e' => $event, 'p' => $placement !== '' ? mb_substr($placement, 0, 40) : null, 'v' => $visitor,
        ]);
    } catch (Throwable $e) {
        // Analytics must never prevent an advert from rendering or redirecting.
        app_log('ads', 'Could not record event for advert ' . $adId . ': ' . $e->getMessage());
    }
}

/** Pick an active advert for a placement. Returns null when ads are off or none are eligible. */
function ad_pick(string $placement, string $type = ''): ?array
{
    if (!setting('ads_enabled') || can('no_ads')) {
        return null;
    }
    $sql = "SELECT * FROM advertisements WHERE status = 'active' AND starts_at <= NOW() AND expires_at > NOW()
            AND (placement = :p OR placement = 'global' OR FIND_IN_SET(:p2, COALESCE(placements, '')) > 0)
            AND (max_views IS NULL OR views < max_views) AND (max_clicks IS NULL OR clicks < max_clicks)";
    $params = ['p' => $placement, 'p2' => $placement];
    if ($type !== '') {
        $sql .= ' AND ad_type = :t';
        $params['t'] = $type;
    }
    $ads = rows($sql . ' ORDER BY RAND() LIMIT 1', $params);
    return $ads[0] ?? null;
}

/** Echo-ready HTML for an ad slot (empty string when there is nothing to show). Records a view. */
function ad_slot(string $placement, string $type = 'card', string $mode = 'row'): string
{
    try {
        $ad = ad_pick($placement, $type);
        if (!$ad) {
            return '';
        }
        q('UPDATE advertisements SET views = views + 1 WHERE id = :id', ['id' => $ad['id']]);
        ad_track_event((int)$ad['id'], 'impression', $placement);
        return partial('spot_card', ['ad' => $ad, 'mode' => $mode]);
    } catch (Throwable $e) {
        return '';
    }
}
