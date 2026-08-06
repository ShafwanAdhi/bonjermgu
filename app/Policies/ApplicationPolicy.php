<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Application;
use App\Models\User;

/**
 * The second half of AD-09.
 *
 * The global scope answers "which rows exist for this user". This answers
 * "what may they do with one". The split matters most for Referral: it passes
 * the scope for the applications it brought in, and is refused every update.
 * Seeing is allowed, changing is not (docs/actors.md section 2).
 *
 * Admin is refused everywhere. It never even reaches here for a normal query,
 * because the scope already returns an empty set.
 */
class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== Role::Admin;
    }

    /**
     * Ownership is already guaranteed by the scope — a row this user cannot
     * see never reaches the policy. Re-checking it here is deliberate belt and
     * braces for any caller that reached the model another way.
     */
    public function view(User $user, Application $application): bool
    {
        return match ($user->role) {
            Role::AccountOfficer => $application->account_officer_id === $user->accountOfficer?->id,
            Role::Referral => $application->referral_id === $user->referral?->id,
            Role::Admin => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === Role::AccountOfficer;
    }

    /** Only the owning AO. Referral is read-only, Admin has no access. */
    public function update(User $user, Application $application): bool
    {
        return $user->role === Role::AccountOfficer
            && $application->account_officer_id === $user->accountOfficer?->id;
    }

    /** Document and tracking status ride on the same permission as update. */
    public function updateDocuments(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }

    public function updateTracking(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }

    public function delete(User $user, Application $application): bool
    {
        return false;
    }
}
