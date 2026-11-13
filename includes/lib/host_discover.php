<?php
/** The Hosts row on the Discover page (settings: Admin > Hosts > Names and Discover). */

declare(strict_types=1);

function host_discover_title(): string
{
    $t = trim((string)setting('host_discover_title'));
    return $t !== '' ? $t : host_term(true) . ' near you';
}

/**
 * Cards for the Discover row, or [] when the row is off, the directory is closed to this viewer, or nobody qualifies.
 * Asking for a limit means a member picked the Host side and this is the whole page, so the row's own switch does not apply.
 */
function host_discover_rows(?int $limit = null): array
{
    if (($limit === null && !setting('host_discover_enabled')) || !hosts_enabled()) {
        return [];
    }
    [$ok] = hosts_directory_gate();
    if (!$ok) {
        return [];
    }
    $n = $limit !== null ? max(2, min(60, $limit)) : max(2, min(40, (int)setting('host_discover_count')));
    $order = (string)setting('host_discover_order');
    if (!isset(hosts_sorts()[$order])) {
        $order = 'recommended';
    }
    if ($order === 'nearest' && viewer_coords() === null) {
        $order = 'recommended';
    }
    try {
        $dir = hosts_directory(['sort' => $order], 1, $n);
    } catch (Throwable $e) {
        return [];
    }
    $me = (int)(current_user()['id'] ?? 0);
    return array_values(array_filter($dir['rows'], fn($m) => (int)$m['id'] !== $me));
}
