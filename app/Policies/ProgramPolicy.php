<?php

namespace App\Policies;

use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\User;

class ProgramPolicy
{
    /**
     * Admin manages every program. Faculty view READ-ONLY for programs
     * they lead or are assigned to (5.2).
     */
    public function view(User $user, ExtensionProgram $program): bool
    {
        if ($user->isAdmin() || $user->isSecretary()) {
            return true;
        }

        if (! $user->isFaculty()) {
            return false;
        }

        $faculty = Faculty::where('user_id', $user->id)->first();

        if (! $faculty) {
            return false;
        }

        return $program->program_lead_id === $faculty->id
            || $program->activities()
                ->whereHas('faculty', fn ($q) => $q->where('faculty_id', $faculty->id))
                ->exists();
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Scoped hub management (v4.12): beneficiary enrollment/registry
     * actions and attendance recording. Admin or Secretary — the
     * Director-only powers (program edit, objectives, activities, budget)
     * remain under manage().
     */
    public function manageBeneficiaries(User $user): bool
    {
        return $user->isAdmin() || $user->isSecretary();
    }
}
