<?php
/**
 * Locations (counties + towns), distance maths and nearest-town lookup.
 */

declare(strict_types=1);

function locations_counties(): array
{
    return cache_remember('loc_counties', 600, fn() => rows(
        "SELECT id, name, slug, region, latitude, longitude, is_popular FROM locations
         WHERE type = 'county' AND is_active = 1 ORDER BY name"
    ));
}

function locations_towns(?int $countyId = null): array
{
    if ($countyId) {
        return rows("SELECT id, name, slug, parent_id, latitude, longitude, is_popular FROM locations
                     WHERE type = 'town' AND is_active = 1 AND parent_id = :c ORDER BY is_popular DESC, name", ['c' => $countyId]);
    }
    return cache_remember('loc_towns', 600, fn() => rows(
        "SELECT id, name, slug, parent_id, latitude, longitude, is_popular FROM locations
         WHERE type = 'town' AND is_active = 1 ORDER BY is_popular DESC, name"
    ));
}

/** Popular locations for the hero buttons, honouring the admin's counties/towns choice. */
function locations_for_hero(?int $limit = null): array
{
    $limit = $limit ?? (int)setting('hero_pill_count');
    $types = [];
    if (setting('hero_use_counties')) {
        $types[] = 'county';
    }
    if (setting('hero_use_towns')) {
        $types[] = 'town';
    }
    if (!$types) {
        return [];
    }
    return rows(
        'SELECT id, type, name, slug FROM locations WHERE is_active = 1 AND type IN (:t) AND is_popular = 1
         ORDER BY sort_order, name LIMIT ' . max(1, $limit),
        ['t' => $types]
    );
}

function location_by_slug(string $slug): ?array
{
    return row('SELECT * FROM locations WHERE slug = :s AND is_active = 1', ['s' => $slug]);
}

function location_by_id(int $id): ?array
{
    return row('SELECT * FROM locations WHERE id = :id', ['id' => $id]);
}

/** Search counties and towns by name (for the hero search box and pickers). */
function locations_search(string $term, int $limit = 8): array
{
    $term = trim($term);
    if ($term === '') {
        return [];
    }
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
    return rows(
        "SELECT l.id, l.type, l.name, l.slug, c.name AS county_name FROM locations l
         LEFT JOIN locations c ON c.id = l.parent_id
         WHERE l.is_active = 1 AND l.name LIKE :l
         ORDER BY (l.name LIKE :p) DESC, l.type = 'county' DESC, l.is_popular DESC, l.name LIMIT " . max(1, $limit),
        ['l' => $like, 'p' => str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%']
    );
}

/** Closest known town or county to a coordinate (offline reverse geocoding). */
function nearest_location(float $lat, float $lng): ?array
{
    $rows = rows(
        "SELECT id, type, name, parent_id, latitude, longitude,
                (6371 * ACOS(LEAST(1, COS(RADIANS(:la)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(:lo)) + SIN(RADIANS(:la)) * SIN(RADIANS(latitude))))) AS km
         FROM locations WHERE is_active = 1 AND type = 'town'
         ORDER BY km LIMIT 1",
        ['la' => $lat, 'lo' => $lng]
    );
    if (!$rows) {
        return null;
    }
    $town = $rows[0];
    $county = $town['parent_id'] ? location_by_id((int)$town['parent_id']) : null;
    if ((float)$town['km'] > 60) { // too far from any town: fall back to nearest county
        $c = row(
            "SELECT id, type, name, latitude, longitude,
                    (6371 * ACOS(LEAST(1, COS(RADIANS(:la)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(:lo)) + SIN(RADIANS(:la)) * SIN(RADIANS(latitude))))) AS km
             FROM locations WHERE is_active = 1 AND type = 'county' ORDER BY km LIMIT 1",
            ['la' => $lat, 'lo' => $lng]
        );
        if ($c) {
            return ['location_id' => (int)$c['id'], 'town_name' => null, 'county_id' => (int)$c['id'], 'county_name' => $c['name'], 'km' => (float)$c['km']];
        }
    }
    return [
        'location_id' => (int)$town['id'], 'town_name' => $town['name'],
        'county_id' => $county ? (int)$county['id'] : null, 'county_name' => $county['name'] ?? null, 'km' => (float)$town['km'],
    ];
}

/** Save the member's position from the browser (GPS). Coordinates are rounded to ~1 km. */
function save_user_gps(int $userId, float $lat, float $lng): ?array
{
    if ($lat < -5.5 || $lat > 5.5 || $lng < 33 || $lng > 42.5) {
        // Outside East Africa: still store, but without a town match we keep the raw point.
    }
    $lat = round($lat, 2);
    $lng = round($lng, 2);
    $near = nearest_location($lat, $lng);
    q('INSERT INTO user_locations (user_id, latitude, longitude, location_id, county_id, town_name, county_name, country, source)
       VALUES (:u, :la, :lo, :lid, :cid, :tn, :cn, :co, \'gps\')
       ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), location_id = VALUES(location_id),
         county_id = VALUES(county_id), town_name = VALUES(town_name), county_name = VALUES(county_name), source = \'gps\'',
        ['u' => $userId, 'la' => $lat, 'lo' => $lng, 'lid' => $near['location_id'] ?? null, 'cid' => $near['county_id'] ?? null,
         'tn' => $near['town_name'] ?? null, 'cn' => $near['county_name'] ?? null, 'co' => (string)setting('site_country')]);
    return $near;
}

function user_location(int $userId): ?array
{
    return row('SELECT * FROM user_locations WHERE user_id = :u', ['u' => $userId]);
}

/** "Westlands, Nairobi" / "Nairobi" / "" */
function location_label(?array $loc): string
{
    if (!$loc) {
        return '';
    }
    $town = trim((string)($loc['town_name'] ?? ''));
    $county = trim((string)($loc['county_name'] ?? ''));
    if ($town !== '' && $county !== '' && strcasecmp($town, $county) !== 0) {
        return $town . ', ' . $county;
    }
    return $town !== '' ? $town : $county;
}

/** SQL for the distance in km between two lat/lng column expressions (Haversine). */
function sql_distance(string $lat1, string $lng1, string $lat2, string $lng2): string
{
    return "(6371 * ACOS(LEAST(1, COS(RADIANS($lat1)) * COS(RADIANS($lat2)) * COS(RADIANS($lng2) - RADIANS($lng1)) + SIN(RADIANS($lat1)) * SIN(RADIANS($lat2)))))";
}

/** Bounding box around a point, to let MySQL use the lat/lng index before Haversine. */
function geo_bbox(float $lat, float $lng, float $km): array
{
    $dLat = $km / 111.0;
    $dLng = $km / max(0.01, 111.0 * cos(deg2rad($lat)));
    return ['min_lat' => $lat - $dLat, 'max_lat' => $lat + $dLat, 'min_lng' => $lng - $dLng, 'max_lng' => $lng + $dLng];
}

/** Friendly distance text, rounded by the admin's setting. */
function distance_label(?float $km): string
{
    if ($km === null) {
        return '';
    }
    $step = max(1, (int)setting('distance_rounding_km'));
    if ($km < $step) {
        return 'Less than ' . $step . ' km away';
    }
    $rounded = (int)(round($km / $step) * $step);
    return $rounded . ' km away';
}


/** The place a visitor picked (hero buttons, search box or GPS), remembered in the ch_loc cookie as a location slug. */
function guest_location(): ?array
{
    static $loc = false;
    if ($loc === false) {
        $loc = null;
        $slug = isset($_COOKIE['ch_loc']) ? (string)$_COOKIE['ch_loc'] : '';
        if ($slug !== '' && preg_match('/^[a-z0-9-]{2,120}$/', $slug)) {
            $loc = location_by_slug($slug);
        }
    }
    return $loc;
}

function guest_location_remember(?array $loc): void
{
    if ($loc && !headers_sent()) {
        setcookie('ch_loc', (string)$loc['slug'], ['expires' => time() + 86400 * 90, 'path' => (base_path() !== '' ? base_path() : '') . '/', 'samesite' => 'Lax', 'secure' => is_https()]);
        $_COOKIE['ch_loc'] = (string)$loc['slug'];
    }
}
