<?php

namespace App\Policies;

use App\Models\Faculty;
use App\Models\User;

/**
 * Policy for the Faculty Management module (revision §5 R3 step 6 / D-R9).
 *
 * RULES (from the plan):
 *  - Admin may manage every faculty profile (create, edit, deactivate).
 *  - A faculty member may VIEW their own profile, and edit their own ACADEMIC
 *    and CONTACT details (specialization, department, contact number, address)
 *    plus their EXPERTISE areas. Employee ID, college, position, status and the
 *    login account (name, email) stay Director-only — those are institutional
 *    records the faculty member does not own. WIDENED 2026-09-27; it was
 *    contact-only before.
 *  - Secretary has NO faculty-management access. This is a deliberate
 *    separation of duties: the Secretary validates assessments and manages
 *    beneficiaries, but does not touch the faculty roster.
 *
 * The performance data (training hours, rendered hours, proposals) is
 * Director-facing (D-R9) and is therefore gated by `viewAny`/`view`, not shown
 * to a faculty member viewing their own record beyond their own contribution.
 */
class FacultyPolicy
{
    /**
     * The Director's board and directory — Admin only.
     *
     * Faculty do NOT get the roster: seeing colleagues' hours and rankings is a
     * Director view, and §6 frames the whole module as "what the Director sees".
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * View one faculty profile.
     *
     * Admin sees anyone; a faculty member sees themselves; nobody else.
     */
    public function view(User $user, Faculty $faculty): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->isOwnProfile($user, $faculty);
    }

    /**
     * Provision a new faculty account/profile — Admin only (§5.1: account
     * provisioning is Admin-only).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Full edit (employee ID, college, position, expertise) — Admin only.
     */
    public function update(User $user, Faculty $faculty): bool
    {
        return $user->isAdmin();
    }

    /**
     * A faculty member may edit a WIDENED set of their OWN profile fields.
     *
     * OWNER DECISION 2026-09-27 — this REPLACES the narrower
     * `updateOwnContactDetails()`, which permitted contact details only. A
     * faculty member now also maintains the fields that describe their own
     * academic work and their expertise:
     *
     *   SELF-EDITABLE  contact number · address · specialization · department ·
     *                  expertise areas
     *   DIRECTOR-ONLY  employee ID · college · position · status, plus the
     *                  `users` row (full name, email)
     *
     * The split is deliberate. The self-editable set is what the faculty member
     * knows better than anyone — and what the Faculty Directory's expertise
     * filter reads — while the Director-only set is what the institution
     * decides (appointment, rank, employment status) or issues (employee ID).
     *
     * Like its predecessor, this ability only decides whether the self-edit form
     * is AVAILABLE. The writable set is enforced in the component, server-side —
     * never by hiding inputs. See `Faculty\Profile::saveProfile()`.
     */
    public function updateOwnProfile(User $user, Faculty $faculty): bool
    {
        return $this->isOwnProfile($user, $faculty);
    }

    /**
     * Deactivate / soft-delete — Admin only.
     */
    public function delete(User $user, Faculty $faculty): bool
    {
        return $user->isAdmin();
    }

    /**
     * Reset a faculty member's login password — Admin only (account
     * provisioning is Admin-only, §5.1).
     */
    public function resetPassword(User $user, Faculty $faculty): bool
    {
        return $user->isAdmin();
    }

    private function isOwnProfile(User $user, Faculty $faculty): bool
    {
        return $user->isFaculty() && $faculty->user_id === $user->id;
    }
}
