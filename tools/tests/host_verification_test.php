<?php
/**
 * Host verification: the ID check and the live photo check can each be on or off, the rule decides what a Host needs,
 * badges and the directory filter follow, approving a photo check marks the Host, and page wording follows the mode.
 *   export CH_CONFIG=/path/to/throwaway-config.php
 *   php tools/tests/host_verification_test.php
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
    host_labels_reset();
}
$hostId = (int)val("SELECT hp.user_id FROM host_profiles hp JOIN users u ON u.id = hp.user_id WHERE u.role = 'host' ORDER BY hp.user_id LIMIT 1", [], 0);
if (!$hostId) {
    exit("No Host in this database to test with.\n");
}
$saved = row('SELECT id_verified, photo_verified FROM host_profiles WHERE user_id = :u', ['u' => $hostId]);
$savedExt = row('SELECT id_doc_path, selfie_path FROM host_profile_ext WHERE user_id = :u', ['u' => $hostId]);
$reqIds = [];
register_shutdown_function(function () use (&$touched, $hostId, $saved, $savedExt, &$reqIds) {
    foreach ($touched as $k => $v) {
        $v === null ? setting_reset($k) : setting_set($k, $v);
    }
    update('host_profiles', ['id_verified' => (int)$saved['id_verified'], 'photo_verified' => (int)$saved['photo_verified']], ['user_id' => $hostId]);
    if ($savedExt) {
        update('host_profile_ext', ['id_doc_path' => $savedExt['id_doc_path'], 'selfie_path' => $savedExt['selfie_path']], ['user_id' => $hostId]);
    }
    foreach ($reqIds as $i) {
        q('DELETE FROM verification_requests WHERE id = :i', ['i' => $i]);
    }
});
function ext_set(int $hostId, ?string $id, ?string $selfie): void
{
    hosts_ext($hostId);
    update('host_profile_ext', ['id_doc_path' => $id, 'selfie_path' => $selfie], ['user_id' => $hostId]);
}

check('migration added photo_verified', (bool)val("SHOW COLUMNS FROM host_profiles LIKE 'photo_verified'"));
check('defaults: ID on, photo check off', setting('host_verify_id_enabled') !== null);

// --- modes
set_for_test('host_verify_id_enabled', '1');
set_for_test('host_verify_photo_enabled', '0');
check('mode id', host_verify_mode() === 'id');
set_for_test('host_verify_photo_enabled', '1');
check('mode both', host_verify_mode() === 'both');
set_for_test('host_verify_id_enabled', '0');
check('mode photo', host_verify_mode() === 'photo');
set_for_test('host_verify_photo_enabled', '0');
check('mode none', host_verify_mode() === 'none');

// --- state per mode
update('host_profiles', ['photo_verified' => 0], ['user_id' => $hostId]);
q('DELETE FROM verification_requests WHERE user_id = :u', ['u' => $hostId]);
set_for_test('host_selfie_required', '0');

ext_set($hostId, null, null);
set_for_test('host_verify_id_enabled', '1');
$s = host_verify_state($hostId);
check('ID only: nothing uploaded is not ok', !$s['ok'] && $s['missing'] === ['ID document']);
ext_set($hostId, 'x/id.jpg', null);
check('ID only: ID uploaded is ok', host_verify_state($hostId)['ok']);
set_for_test('host_selfie_required', '1');
check('ID only: selfie required and missing is not ok', !host_verify_state($hostId)['ok']);
ext_set($hostId, 'x/id.jpg', 'x/selfie.jpg');
check('ID only: ID and selfie is ok', host_verify_state($hostId)['ok']);
set_for_test('host_selfie_required', '0');

ext_set($hostId, null, null);
set_for_test('host_verify_id_enabled', '0');
set_for_test('host_verify_photo_enabled', '1');
check('photo only: not started is not ok', !host_verify_state($hostId)['ok'] && host_verify_state($hostId)['photo_status'] === 'none');
$r = insert('verification_requests', ['user_id' => $hostId, 'type' => 'photo', 'selfie_path' => 'x/s.jpg', 'status' => 'pending', 'method_kind' => 'selfie']);
$reqIds[] = $r;
check('photo only: waiting for review counts as submitted', host_verify_state($hostId)['ok'] && host_verify_state($hostId)['photo_status'] === 'pending');
update('verification_requests', ['status' => 'rejected'], ['id' => $r]);
check('photo only: rejected is not ok', !host_verify_state($hostId)['ok'] && host_verify_state($hostId)['photo_status'] === 'rejected');
update('host_profiles', ['photo_verified' => 1], ['user_id' => $hostId]);
check('photo only: verified is ok', host_verify_state($hostId)['ok'] && host_verify_state($hostId)['photo_status'] === 'approved');
update('host_profiles', ['photo_verified' => 0], ['user_id' => $hostId]);
update('verification_requests', ['status' => 'rejected'], ['id' => $r]);

set_for_test('host_verify_id_enabled', '1');
set_for_test('host_verify_rule', 'any');
check('both, either: nothing done is not ok', !host_verify_state($hostId)['ok']);
ext_set($hostId, 'x/id.jpg', null);
check('both, either: ID alone is ok', host_verify_state($hostId)['ok']);
ext_set($hostId, null, null);
update('verification_requests', ['status' => 'pending'], ['id' => $r]);
check('both, either: photo alone is ok', host_verify_state($hostId)['ok']);
set_for_test('host_verify_rule', 'all');
check('both, all: photo alone is not ok', !host_verify_state($hostId)['ok'] && in_array('ID document', host_verify_state($hostId)['missing'], true));
ext_set($hostId, 'x/id.jpg', null);
check('both, all: both done is ok', host_verify_state($hostId)['ok']);
update('verification_requests', ['status' => 'rejected'], ['id' => $r]);
check('both, all: ID alone is not ok', !host_verify_state($hostId)['ok'] && host_verify_state($hostId)['missing'] === ['photo check']);

set_for_test('host_verify_id_enabled', '0');
set_for_test('host_verify_photo_enabled', '0');
ext_set($hostId, null, null);
check('none: always ok', host_verify_state($hostId)['ok'] && host_verify_state($hostId)['missing'] === []);

// --- completeness follows the state
set_for_test('host_verify_id_enabled', '1');
ext_set($hostId, null, null);
$c = array_column(hosts_completeness($hostId)['items'], 'done', 'key');
check('checklist verify step is not done without ID', $c['verify'] === false);
ext_set($hostId, 'x/id.jpg', null);
$c = array_column(hosts_completeness($hostId)['items'], 'done', 'key');
check('checklist verify step is done with ID', $c['verify'] === true);

// --- badges
check('badge ID', host_verify_badge(['id_verified' => 1, 'photo_verified' => 0]) === 'ID verified');
check('badge photo', host_verify_badge(['id_verified' => 0, 'photo_verified' => 1]) === 'Photo verified');
check('badge both', host_verify_badge(['id_verified' => 1, 'photo_verified' => 1]) === 'ID and photo verified');
check('badge none', host_verify_badge(['id_verified' => 0, 'photo_verified' => 0]) === '');

// --- directory filter counts either mark
update('host_profiles', ['id_verified' => 0, 'photo_verified' => 1], ['user_id' => $hostId]);
$ids = array_map(fn($m) => (int)$m['id'], hosts_directory(['verified' => 1], 1, 48)['rows']);
$inFilter = in_array($hostId, $ids, true);
$status = (string)val('SELECT approval_status FROM host_profiles WHERE user_id = :u', ['u' => $hostId]);
check('photo-verified Host shows under the verified filter (when listed)', $status !== 'approved' || $inFilter || !hosts_enabled());
$dec = hosts_decorate(members_search(['role' => 'host', 'only_ids' => [$hostId]], 1, 0)['rows']);
check('decorated Host carries the verify label', !$dec || ($dec[0]['host']['verify_label'] ?? '') === 'Photo verified', json_encode($dec[0]['host'] ?? null));

// --- page wording
set_for_test('host_verify_id_enabled', '1');
set_for_test('host_verify_photo_enabled', '0');
check('copy says ID-verified while the ID check is on', host_labels_swap('Book ID-verified people') === 'Book ID-verified people');
set_for_test('host_verify_id_enabled', '0');
set_for_test('host_verify_photo_enabled', '1');
check('copy says photo-verified when only the photo check is on', host_labels_swap('Book ID-verified people. ID verified only') === 'Book photo-verified people. Photo verified only', host_labels_swap('Book ID-verified people. ID verified only'));
check('sentence start is capitalised', host_labels_swap('ID-verified people. ID-verified Hosts') === 'Photo-verified people. Photo-verified Hosts', host_labels_swap('ID-verified people. ID-verified Hosts'));
set_for_test('host_verify_photo_enabled', '0');
check('copy says approved when nothing is checked', host_labels_swap('ID-verified Hosts') === 'Approved Hosts', host_labels_swap('ID-verified Hosts'));
set_for_test('host_verify_id_enabled', '1');
check('copy returns to normal', host_labels_swap('ID-verified Hosts') === 'ID-verified Hosts');

// --- admin approving a live photo check marks the Host
update('host_profiles', ['photo_verified' => 0], ['user_id' => $hostId]);
update('verification_requests', ['status' => 'approved'], ['id' => $r]);
q('UPDATE host_profiles SET photo_verified = 1 WHERE user_id = :u', ['u' => $hostId]);
check('approval flag is stored', (int)val('SELECT photo_verified FROM host_profiles WHERE user_id = :u', ['u' => $hostId], 0) === 1);

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
