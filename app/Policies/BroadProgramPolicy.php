<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

/**
 * Policy for the BROAD Program level (revision §4.2 / Phase R1).
 *
 * Named `BroadProgramPolicy` because `ProgramPolicy` already governs the
 * legacy entity (the soon-to-be "project"). Phase R2 renames that side and
 * the two policies can then be reconciled.
 *
 * Broad programs are structural: everyone authenticated may read them (they
 * label projects and roll up performance), but only the Director (admin) may
 * create or modify one.
 */
class BroadProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Program $program): bool
    {
        return true;
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }
}
