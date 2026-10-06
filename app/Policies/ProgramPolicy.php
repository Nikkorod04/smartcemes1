<?php

namespace App\Policies;

use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;

class ProgramPolicy
{
    /**
     * Admin manages every program. Faculty view READ-ONLY for programs
     * they lead or are assigned to (5.2).
     */
    public function view(User $user, ExtensionProject $program): bool
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
     * Archive a project (soft delete) — Director only.
     *
     * Deliberately its OWN ability rather than reusing `manage()`, so the
     * destructive path is separately assertable: `manage()` also gates the
     * create/edit forms, and a test that only proves "the Director can edit"
     * would say nothing about whether a Secretary can archive a whole project.
     *
     * This is a SOFT delete (the model uses SoftDeletes), so the row survives
     * for audit and `ExtensionProject::nextCode()` will never reissue its code.
     * The hub archives the project's activities in the same transaction —
     * without that, the activities stay visible in the Calendar and the
     * Availability activity picker, because those query `Activity` globally
     * rather than through the project.
     */
    public function delete(User $user, ExtensionProject $program): bool
    {
        return $user->isAdmin();
    }

    /**
     * Undo an archive — Director only.
     *
     * Its own ability rather than reusing `delete()`, for the same reason
     * `delete()` is separate from `manage()`: "may archive" and "may bring back"
     * are different powers, and a policy that answered both with one method
     * could not express a future where only one of them is granted.
     *
     * The project must already be soft-deleted; the hub resolves it through
     * `onlyTrashed()` so a live project can never be "restored".
     */
    public function restore(User $user, ExtensionProject $program): bool
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
