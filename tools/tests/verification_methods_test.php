<?php
/**
 * Verification methods: which methods are usable, random vs choose assignment, and that a request keeps its own
 * copy of the method and reference picture.
 *
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/verification_methods_test.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this from the command line.\n");
}
if (!getenv('CH_CONFIG') && !in_array('--yes', $argv, true)) {
    exit("Refusing to run without CH_CONFIG pointing at a throw-away database (or pass --yes).\n");
}
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $name . (!$ok && $detail !== '' ? "  ($detail)" : '') . "\n";
}
$touched = [];
function set_for_test(string $key, string $value): void
{
    global $touched;
    if (!array_key_exists($key, $touched)) {
        $r = row('SELECT setting_value FROM site_settings WHERE setting_key = :k', ['k' => $key]);
        $touched[$key] = $r ? (string)$r['setting_value'] : null;
    }
    setting_set($key, $value);
}
$mids = [];
$uids = [];
register_shutdown_function(function () use (&$touched, &$mids, &$uids) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    foreach ($uids as $u) {
        q('DELETE FROM users WHERE id = :u', ['u' => $u]);
    }
    foreach ($mids as $m) {
        q('DELETE FROM verification_methods WHERE id = :m', ['m' => $m]);
    }
    q("UPDATE verification_methods SET is_active = 1 WHERE kind = 'selfie'");
});

$selfie = row("SELECT * FROM verification_methods WHERE kind = 'selfie' ORDER BY id LIMIT 1");
check('the selfie method exists after migration', (bool)$selfie);
check('selfie method is usable without a reference', in_array((int)$selfie['id'], array_map(fn($m) => (int)$m['id'], verification_usable_methods()), true));

$pose = insert('verification_methods', ['kind' => 'pose', 'name' => 'Test pose', 'instruction' => "Two fingers by cheek\nNo filters", 'reference_path' => null, 'is_active' => 1, 'sort_order' => 90]);
$mids[] = $pose;
$ids = array_map(fn($m) => (int)$m['id'], verification_usable_methods());
check('a pose method without a reference photo is not usable', !in_array($pose, $ids, true));

update('verification_methods', ['reference_path' => 'verification-poses/test-ref.jpg'], ['id' => $pose]);
$usable = verification_usable_methods();
$ids = array_map(fn($m) => (int)$m['id'], $usable);
check('with a reference photo it becomes usable', in_array($pose, $ids, true));

update('verification_methods', ['is_active' => 0], ['id' => $pose]);
check('switched off pose method is not usable', !in_array($pose, array_map(fn($m) => (int)$m['id'], verification_usable_methods()), true));
update('verification_methods', ['is_active' => 1], ['id' => $pose]);
$usable = verification_usable_methods();

// Assignment
set_for_test('verification_assign', 'choose');
$_SESSION = [];
$m = verification_method_for_member($usable, $pose);
check('choose: the picked method is returned', $m && (int)$m['id'] === $pose);
$m = verification_method_for_member($usable, 999999);
check('choose: an unknown pick falls back to the first method', $m && (int)$m['id'] === (int)$usable[0]['id']);

set_for_test('verification_assign', 'random');
$_SESSION = [];
$first = verification_method_for_member($usable, 0);
$again = verification_method_for_member($usable, 0);
check('random: a method is assigned and stays the same during the visit', $first && $again && $first['id'] === $again['id']);
$seen = [];
for ($i = 0; $i < 60; $i++) {
    $_SESSION = [];
    $x = verification_method_for_member($usable, 0);
    $seen[(int)$x['id']] = true;
}
check('random: different members can get different methods', count($seen) >= 2);

update('verification_methods', ['is_active' => 0], ['id' => (int)$selfie['id']]);
$only = verification_usable_methods();
$_SESSION = [];
$m = verification_method_for_member($only, 0);
check('with only one method on, everyone gets it', count($only) === 1 && $m && (int)$m['id'] === $pose);
update('verification_methods', ['is_active' => 0], ['id' => $pose]);
check('every method off: nothing is usable and none is assigned', verification_usable_methods() === [] && verification_method_for_member([], 0) === null);
update('verification_methods', ['is_active' => 1], ['id' => (int)$selfie['id']]);
update('verification_methods', ['is_active' => 1], ['id' => $pose]);

// A request keeps its own copy
$county = (int)val("SELECT id FROM locations WHERE type = 'county' AND is_active = 1 ORDER BY id LIMIT 1", [], 0) ?: null;
$stamp = substr(md5((string)microtime(true)), 0, 8);
$uid = create_account(['email' => "vm-$stamp@test.invalid", 'password' => 'Test#pass12345', 'role' => 'user', 'display_name' => 'Vee', 'date_of_birth' => '1990-05-05', 'gender' => 'female', 'county_id' => $county]);
$uids[] = $uid;
$rid = insert('verification_requests', ['user_id' => $uid, 'type' => 'photo', 'selfie_path' => 'x/y.jpg', 'status' => 'pending', 'method_id' => $pose, 'method_kind' => 'pose', 'reference_path' => 'verification-poses/test-ref.jpg']);
q('DELETE FROM verification_methods WHERE id = :m', ['m' => $pose]);
$req = row('SELECT method_id, method_kind, reference_path FROM verification_requests WHERE id = :i', ['i' => $rid]);
check('a request keeps its method kind and reference picture after the method is deleted', $req && $req['method_kind'] === 'pose' && $req['reference_path'] === 'verification-poses/test-ref.jpg');
$def = row("SELECT method_kind FROM verification_requests WHERE id = :i", ['i' => insert('verification_requests', ['user_id' => $uid, 'type' => 'photo', 'selfie_path' => 'x/z.jpg', 'status' => 'rejected'])]);
check('older requests default to the selfie kind', $def && $def['method_kind'] === 'selfie');
check('default steps come from the verification_instruction setting', is_array(verification_default_steps()));

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
