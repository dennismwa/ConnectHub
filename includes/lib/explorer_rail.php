<?php
/**
 * The side panels on Discover: who likes you, your newest matches and who is online now, plus the quick filter
 * choices above the cards. Small, cheap queries only; every list is capped.
 */

declare(strict_types=1);

/** Quick distance choices for the pills on Discover, never above the site's own limit. */
function explorer_quick_distances(): array
{
    $max = max(5, (int)setting('radius_max'));
    $out = [];
    foreach ([10, 25, 50, 100, 250] as $d) {
        if ($d < $max) {
            $out[] = $d;
        }
    }
    $out[] = $max;
    return array_values(array_unique($out));
}

/** True when a member joined in the last N days (shown as a "New" tag). */
function explorer_is_new(?string $joinedAt, int $days = 7): bool
{
    $ts = $joinedAt ? strtotime($joinedAt) : false;
    return $ts !== false && $ts > time() - $days * 86400;
}

/**
 * Data for the side panels.
 *
 * @return array{likes: array, matches: array, online: array}
 *   likes   total, can_see (Premium shows faces, Free members see blurred teasers), items [href, name, img, blur]
 *   matches total, unseen, items [href, name, img, new]
 *   online  total shown, items [href, name, img, age]
 */
function explorer_rail(int $uid, array $search = []): array
{
    $out = ['likes' => ['total' => 0, 'can_see' => false, 'items' => []], 'matches' => ['total' => 0, 'unseen' => 0, 'items' => []], 'online' => ['items' => []]];

    // People who liked you and you have not answered yet
    $canSee = can('see_who_liked');
    $liked = explorer_connection_ids('liked_you', $uid, 4);
    $out['likes']['total'] = (int)$liked['total'];
    $out['likes']['can_see'] = (bool)$canSee;
    if ($liked['ids']) {
        if ($canSee) {
            foreach (explorer_members_by_ids($liked['ids']) as $m) {
                $out['likes']['items'][] = ['href' => url('/u/' . (int)$m['id']), 'name' => $m['first_name'], 'img' => photo_url($m['photo'], 'thumb', 'profile'), 'blur' => photo_is_blurred($m['photo'], 'profile')];
            }
        } else {
            foreach (explorer_teasers($liked['ids'], 4) as $t) {
                $out['likes']['items'][] = ['href' => url('/likes'), 'name' => '', 'img' => $t['blur'], 'blur' => true];
            }
        }
    }

    // Newest matches, with a dot for the ones not opened yet
    $base = "FROM matches m JOIN users u ON u.id = IF(m.user1_id = :me, m.user2_id, m.user1_id) JOIN user_profiles p ON p.user_id = u.id
             WHERE m.is_active = 1 AND (m.user1_id = :me OR m.user2_id = :me) AND u.status = 'active'
               AND NOT EXISTS (SELECT 1 FROM blocks bl WHERE (bl.blocker_id = :me AND bl.blocked_id = u.id) OR (bl.blocker_id = u.id AND bl.blocked_id = :me))";
    $out['matches']['total'] = (int)val('SELECT COUNT(*) ' . $base, ['me' => $uid], 0);
    if ($out['matches']['total'] > 0) {
        $out['matches']['unseen'] = (int)val('SELECT COUNT(*) ' . $base . ' AND IF(m.user1_id = :me, m.user1_seen, m.user2_seen) = 0', ['me' => $uid], 0);
        $list = rows('SELECT u.id AS other, IF(m.user1_id = :me, m.user1_seen, m.user2_seen) AS seen ' . $base . ' ORDER BY m.matched_at DESC LIMIT 6', ['me' => $uid]);
        $seen = [];
        foreach ($list as $r) {
            $seen[(int)$r['other']] = (int)$r['seen'];
        }
        foreach (explorer_members_by_ids(array_keys($seen)) as $m) {
            $out['matches']['items'][] = ['href' => url('/u/' . (int)$m['id']), 'name' => $m['first_name'], 'img' => photo_url($m['photo'], 'thumb', 'profile'), 'blur' => photo_is_blurred($m['photo'], 'profile'), 'new' => empty($seen[(int)$m['id']])];
        }
    }

    // People online right now who fit the member's filters and who they have not answered yet
    if (setting('discover_show_online')) {
        $res = members_search(['online' => true, 'order' => 'active'] + $search, 8, 0);
        foreach ($res['rows'] as $m) {
            if (!$m['online']) {
                continue;
            }
            $out['online']['items'][] = ['href' => url('/u/' . (int)$m['id']), 'name' => $m['first_name'], 'img' => photo_url($m['photo'], 'thumb', 'profile'), 'blur' => photo_is_blurred($m['photo'], 'profile'), 'age' => $m['age']];
        }
    }
    return $out;
}
