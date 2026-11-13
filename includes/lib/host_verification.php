<?php
/**
 * How a Host proves who they are. Two methods, each switched on or off by the admin (Admin > Hosts > Host verification):
 *   id     an ID document (and, if the site asks, a selfie holding it), reviewed on the Host's page
 *   photo  a live photo check, the same one members use (copy a pose), reviewed in Admin > Verifications
 * With both on, the rule says whether one is enough or both are needed. With both off, nothing is asked.
 */

declare(strict_types=1);

function host_verify_id_on(): bool
{
    return (bool)setting('host_verify_id_enabled');
}

function host_verify_photo_on(): bool
{
    return (bool)setting('host_verify_photo_enabled');
}

/** 'id' | 'photo' | 'both' | 'none' */
function host_verify_mode(): string
{
    $id = host_verify_id_on();
    $ph = host_verify_photo_on();
    return $id && $ph ? 'both' : ($id ? 'id' : ($ph ? 'photo' : 'none'));
}

function host_verify_rule(): string
{
    return setting('host_verify_rule') === 'all' ? 'all' : 'any';
}

/** Is a live photo check possible right now (method on and at least one pose or selfie method available)? */
function host_verify_photo_available(): bool
{
    return host_verify_photo_on() && count(verification_usable_methods()) > 0;
}

/**
 * Where a Host stands. Keys: id_on, photo_on, id_done, photo_done, photo_status (none|pending|approved|rejected), ok, missing (labels), summary.
 * "Done" for the photo check means approved or waiting for review, so a Host can submit the application while it is being checked.
 */
function host_verify_state(int $hostId): array
{
    $hp = hosts_profile($hostId) ?: [];
    $ext = hosts_ext($hostId);
    $idOn = host_verify_id_on();
    $phOn = host_verify_photo_on();
    $idDone = !empty($ext['id_doc_path']) && (!setting('host_selfie_required') || !empty($ext['selfie_path']));
    $latest = row('SELECT status FROM verification_requests WHERE user_id = :u ORDER BY id DESC LIMIT 1', ['u' => $hostId]);
    $approved = !empty($hp['photo_verified']);
    $status = $approved ? 'approved' : ($latest ? (string)$latest['status'] : 'none');
    $phDone = $approved || $status === 'pending';
    $missing = [];
    if ($idOn && !$idDone) {
        $missing[] = 'ID document';
    }
    if ($phOn && !$phDone) {
        $missing[] = 'photo check';
    }
    if (!$idOn && !$phOn) {
        $ok = true;
    } elseif ($idOn && $phOn && host_verify_rule() === 'any') {
        $ok = $idDone || $phDone;
        $missing = $ok ? [] : ['ID document or photo check'];
    } else {
        $ok = !$missing;
    }
    return ['id_on' => $idOn, 'photo_on' => $phOn, 'id_done' => $idDone, 'photo_done' => $phDone, 'photo_status' => $status, 'ok' => $ok, 'missing' => $missing, 'rule' => host_verify_rule()];
}

/** Label for the badge of one Host: "ID verified", "Photo verified", "ID and photo verified" or ''. */
function host_verify_badge(array $hp): string
{
    $id = !empty($hp['id_verified']);
    $ph = !empty($hp['photo_verified']);
    return $id && $ph ? 'ID and photo verified' : ($id ? 'ID verified' : ($ph ? 'Photo verified' : ''));
}

/** "Verified" in the SQL sense: either mark counts for the directory filter. */
const HOST_VERIFIED_SQL = '(hp.id_verified = 1 OR hp.photo_verified = 1)';

/** Wording used in page copy such as "ID-verified Hosts". */
function host_verify_word(): string
{
    switch (host_verify_mode()) {
        case 'photo':
            return 'photo-verified';
        case 'none':
            return 'approved';
        default:
            return 'ID-verified';
    }
}
