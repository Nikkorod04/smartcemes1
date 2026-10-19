<?php

namespace App\Policies;

use App\Models\User;

/**
 * Account-management policy. Login accounts are an administrative control
 * surface, separate from faculty profile data and contribution records.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->isAdmin();
    }

    public function resetPassword(User $user, User $managedUser): bool
    {
        return $user->isAdmin();
    }

    public function changeStatus(User $user, User $managedUser): bool
    {
        return $user->isAdmin() && $user->id !== $managedUser->id;
    }
}
