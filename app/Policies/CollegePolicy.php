<?php

namespace App\Policies;

use App\Models\College;
use App\Models\User;

/**
 * College policy (revision §4.1 / Phase R1; READ-ONLY since 2026-09-25).
 *
 * Colleges are institution-wide structure: everyone authenticated may READ them
 * (they label programs and projects everywhere).
 *
 * There is deliberately NO create / update / delete. The set is FIXED — CAS,
 * COE, CME and the Graduate School — and `CollegeSeeder` re-asserts it on every
 * seed, so names, descriptions and coordinators are corrected in the seeder
 * rather than in the UI. The owner's reason: the colleges are an institutional
 * fact, not user data, and an editable list invites drift.
 *
 * `manage` SURVIVES, but its meaning changed: it is the HUB-ACCESS ability (the
 * /colleges page is the Director's single entry point to the whole hierarchy),
 * not a write permission. `Colleges\Index::mount()` still authorises it.
 */
class CollegePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, College $college): bool
    {
        return true;
    }

    /**
     * Hub access — the /colleges page. Admin-only.
     *
     * NOT a write ability: no action in the component mutates a college.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
