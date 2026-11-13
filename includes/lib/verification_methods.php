<?php
/**
 * Verification methods. "selfie" is the usual picture of your face. A "pose" method shows a reference photo the admin uploaded and
 * asks the member to take a new picture in the same pose, which proves the photo is live and not copied from somewhere.
 */

declare(strict_types=1);

function verification_methods(bool $activeOnly = true): array
{
    return rows('SELECT * FROM verification_methods' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id');
}

function verification_method(int $id): ?array
{
    return row('SELECT * FROM verification_methods WHERE id = :i', ['i' => $id]);
}

/** Methods a member can use right now. A pose method with no reference picture cannot be used. */
function verification_usable_methods(): array
{
    return array_values(array_filter(verification_methods(true), fn($m) => $m['kind'] !== 'pose' || ($m['reference_path'] ?? '') !== ''));
}

/** The one this member should use now: the one they picked, or one chosen at random for them (and remembered for this visit). */
function verification_method_for_member(array $usable, int $picked): ?array
{
    if (!$usable) {
        return null;
    }
    if (count($usable) === 1) {
        return $usable[0];
    }
    if ((string)setting('verification_assign') === 'random') {
        $keep = (int)($_SESSION['verif_method'] ?? 0);
        foreach ($usable as $m) {
            if ((int)$m['id'] === $keep) {
                return $m;
            }
        }
        $m = $usable[array_rand($usable)];
        $_SESSION['verif_method'] = (int)$m['id'];
        return $m;
    }
    foreach ($usable as $m) {
        if ((int)$m['id'] === $picked) {
            return $m;
        }
    }
    return $usable[0];
}

function verification_default_steps(): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)setting('verification_instruction')) ?: [])));
}
