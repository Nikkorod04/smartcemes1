<?php

namespace App\Policies;

use App\Models\InteragencyAgency;
use App\Models\User;

/**
 * Interagency catalogue policy (revision §5 R6 / §4.6).
 *
 * The catalogue is the closed vocabulary the AI is allowed to cite, so
 * editing it is an administrative act: anyone signed in may read it, only
 * admins may change it. Mirrors CollegePolicy deliberately — same verbs,
 * same admin gate — so the two catalogue screens behave identically.
 */
class InteragencyAgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, InteragencyAgency $agency): bool
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

    public function update(User $user, InteragencyAgency $agency): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, InteragencyAgency $agency): bool
    {
        return $user->isAdmin();
    }
}
