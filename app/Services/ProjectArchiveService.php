<?php

namespace App\Services;

use App\Models\ExtensionProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Archiving and restoring an extension project (owner request 2026-10-05).
 *
 * WHY THIS IS A SERVICE AND NOT A COMPONENT METHOD
 * ------------------------------------------------
 * The cascade used to live inside `Colleges\Index::archiveProject()`. That made
 * it unreachable from anywhere else — a seeder or a console command could not
 * reuse it and would have had to copy it, which is the exact
 * two-implementations-of-one-ruleset problem §25 had to undo for the create
 * forms. Keeping `archive()` and its mirror `restore()` in one class also means
 * they cannot drift: every row type the archive touches is restored by the
 * method directly beneath it.
 *
 * WHY THE CASCADE EXISTS AT ALL
 * -----------------------------
 * Archiving the project ROW alone is not enough to make it disappear. Every
 * project- and university-level figure iterates `ExtensionProject`, so trashed
 * rows drop out for free — but three surfaces query `Activity` GLOBALLY and
 * would keep showing an archived project's work:
 *
 *   - the Calendar (`Calendar::render()` → `Activity::query()`)
 *   - the Availability activity picker (`Activity::with('program')`)
 *   - a faculty's own rendered-hours list, which eager-loads `activity.program`
 *     and would render a blank row for a trashed activity
 *
 * So the activities, and the rows that render THROUGH them, are archived with
 * it — plus the project's OWN budget entries, whose `activity_id` is nullable
 * (all three project seeders write NULL for a project-wide cost, so a
 * per-activity loop never reaches them).
 *
 * Deliberately NOT cascaded: proposals and needs-assessments. They are separate
 * records with their own workflow and audit trail. (`NeedsAssessment` has no
 * project FK at all, so it is unaffected either way.)
 *
 * EVERYTHING IS A SOFT DELETE — the row survives, the code is never reissued
 * (`ExtensionProject::nextCode()` computes its floor over `withTrashed()`), and
 * `restore()` is a true inverse.
 */
class ProjectArchiveService
{
    /**
     * Archive a project and everything that renders through it.
     *
     * @return int the number of activities archived
     */
    public function archive(ExtensionProject $project): int
    {
        $activityCount = $project->activities()->count();

        DB::transaction(function () use ($project): void {
            foreach ($project->activities()->get() as $activity) {
                $activity->renderedHours()->delete();
                $activity->availabilityRequests()->delete();
                $activity->budgetUtilizations()->delete();
                $activity->attendances()->delete();
                $activity->activityImports()->delete();
                $activity->delete();
            }

            $project->budgetUtilizations()->delete();

            $project->delete();
        });

        return $activityCount;
    }

    /**
     * Restore a project archived by `archive()` — the exact inverse.
     *
     * Walks the SAME row types in the SAME order, so adding a type to the
     * archive without adding it here is immediately visible as a diff.
     *
     * @param  ExtensionProject  $project  must already be soft-deleted
     * @return int the number of activities restored
     */
    public function restore(ExtensionProject $project): int
    {
        $activities = $project->activities()->onlyTrashed()->get();

        DB::transaction(function () use ($project, $activities): void {
            foreach ($activities as $activity) {
                $this->restoreRows($activity->renderedHours()->onlyTrashed()->get());
                $this->restoreRows($activity->availabilityRequests()->onlyTrashed()->get());
                $this->restoreRows($activity->budgetUtilizations()->onlyTrashed()->get());
                $this->restoreRows($activity->attendances()->onlyTrashed()->get());
                $this->restoreRows($activity->activityImports()->onlyTrashed()->get());

                $activity->restore();
            }

            $this->restoreRows($project->budgetUtilizations()->onlyTrashed()->get());

            $project->restore();
        });

        return $activities->count();
    }

    /**
     * Restore each row individually rather than via a mass `update()`.
     *
     * A query-builder `restore()` macro would be fewer statements, but it
     * silently depends on how `onlyTrashed()` and `withTrashed()` compose; a
     * demo-scale cascade is a handful of rows, so the explicit model call is
     * worth the clarity.
     *
     * @param  Collection<int, Model>  $rows
     */
    private function restoreRows($rows): void
    {
        foreach ($rows as $row) {
            $row->restore();
        }
    }
}
