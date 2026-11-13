<?php
/**
 * Discover's side panels, quick filters and card details.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/discover_rail_test.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Run this from the command line.\n"); }
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) { exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n"); }
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0; $fail = 0;
function check(string $n, bool $ok, string $d = ''): void { global $pass, $fail; $ok ? $pass++ : $fail++; echo ($ok ? '  ok    ' : '  FAIL  ') . $n . (!$ok && $d !== '' ? "  ($d)" : '') . "\n"; }

/** A member to test with: the first real one, or a throw-away one when the database has none (a fresh install). Returns [id, created]. */
function test_member(): array
{
    $uid = (int)val("SELECT u.id FROM users u JOIN user_profiles p ON p.user_id = u.id WHERE u.role = 'user' AND u.status = 'active' ORDER BY u.id LIMIT 1");
    if ($uid > 0) {
        return [$uid, false];
    }
    q("INSERT INTO users (email, password_hash, role, status, email_verified_at) VALUES ('zz-test-member@example.invalid', 'x', 'user', 'active', NOW())");
    $uid = (int)val("SELECT id FROM users WHERE email = 'zz-test-member@example.invalid'");
    q('INSERT INTO user_profiles (user_id, display_name, onboarding_completed) VALUES (:u, \'Test Member\', 1)', ['u' => $uid]);
    return [$uid, true];
}
function test_member_remove(int $uid): void
{
    foreach (['discovery_preferences', 'user_profiles'] as $t) {
        q("DELETE FROM `$t` WHERE user_id = :u", ['u' => $uid]);
    }
    q('DELETE FROM users WHERE id = :u', ['u' => $uid]);
}

[$uid, $madeMember] = test_member();
check('there is a member to test with', $uid > 0);
$_SESSION['uid'] = $uid;
current_user(true);
check('and they are signed in for the test', auth_id() === $uid);

echo "\nQuick distances\n";
$d = explorer_quick_distances();
$max = max(5, (int)setting('radius_max'));
check('they end at the site limit', end($d) === $max);
check('none is above the limit', max($d) <= $max);
check('they rise and never repeat', $d === array_values(array_unique($d)) && $d === (function ($x) { sort($x); return $x; })($d));

echo "\nNew members\n";
check('someone who joined today is new', explorer_is_new(date('Y-m-d H:i:s')));
check('someone who joined a month ago is not', !explorer_is_new(date('Y-m-d H:i:s', time() - 30 * 86400)));
check('no date means not new', !explorer_is_new(null) && !explorer_is_new(''));

echo "\nCard details for the sheet\n";
$f = explorer_filters_from([], $uid);
$res = explorer_feed($f['search'], 6, [], 'score', $f['recycle']);
$cards = explorer_cards($res['rows'], $uid);
if (!$cards) { echo "  skip  no other members yet, so there are no cards to look at\n"; }
if ($cards) {
    $c = $cards[0];
    foreach (['bio_full', 'looking_for', 'new', 'seen', 'intent', 'occupation', 'town', 'distance', 'compat', 'interests', 'photos', 'href'] as $k) {
        check("a card carries '$k'", array_key_exists($k, $c));
    }
    check('the full bio is not shorter than the card bio', mb_strlen((string)$c['bio_full']) >= mb_strlen((string)$c['bio']));
    check('a card holds at most eight interests', count($c['interests']) <= 8);
    check('"new" is a yes or no', is_bool($c['new']));
    $json = json_encode($cards);
    check('cards still encode as JSON for the deck', is_string($json) && json_decode($json, true) !== null);
}

echo "\nSide panels\n";
$r = explorer_rail($uid, $f['search']);
check('the rail has likes, matches and online', isset($r['likes'], $r['matches'], $r['online']));
$liked = explorer_connection_ids('liked_you', $uid, 0);
check('the likes count is the real count', $r['likes']['total'] === (int)$liked['total']);
check('at most four faces are shown for likes', count($r['likes']['items']) <= 4);
if (!$r['likes']['can_see']) {
    check('a free member sees blurred teasers, never faces', array_reduce($r['likes']['items'], fn($ok, $i) => $ok && $i['blur'] === true && $i['name'] === '', true));
}
check('at most six matches are shown', count($r['matches']['items']) <= 6);
check('the unseen matches never exceed the matches', $r['matches']['unseen'] <= $r['matches']['total']);
check('at most eight online people are shown', count($r['online']['items']) <= 8);
foreach ($r['online']['items'] as $i) {
    check('an online person has a link, a name and a picture', $i['href'] !== '' && $i['name'] !== '' && $i['img'] !== '');
    break;
}
check('nobody in the rail is the member themselves', !array_filter(array_merge($r['matches']['items'], $r['online']['items'], $r['likes']['items']), fn($i) => str_ends_with($i['href'], '/u/' . $uid)));

echo "\nSettings\n";
$fields = settings_schema()['fields'];
check('side panels can be switched off', isset($fields['discover_side_panels']) && (int)$fields['discover_side_panels']['default'] === 1);
check('quick filters can be switched off', isset($fields['discover_quick_filters']) && (int)$fields['discover_quick_filters']['default'] === 1);
check('swipe is the default view in the schema', ($fields['discover_default_view']['default'] ?? '') === 'swipe');

if ($madeMember) { test_member_remove($uid); }
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
