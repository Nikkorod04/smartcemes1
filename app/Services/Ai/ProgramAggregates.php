<?php

namespace App\Services\Ai;

use App\Models\ExtensionProject;
use App\Services\TrainingHoursService;

/**
 * Builds the AGGREGATE project-level payload for the narrative pipeline.
 * No PII.
 *
 * REWRITTEN IN R5 (§5 Phase R5 step 4 / D-R7).
 * -------------------------------------------
 * This class used to ship the 8.6 KPI dictionary — `knowledge_gain`,
 * `cost_per_beneficiary`, `community_reach`, per-objective statuses — into the
 * Gemini prompt. R4 removed those tiles from the UI but this payload was
 * missed, which meant the AI was still being asked to narrate **retired
 * metrics that no screen displayed**. That is worse than a cosmetic gap: the
 * narrative could cite a number the Director has no way to see or verify.
 *
 * The payload now carries the R4/R5 metric dictionary:
 *   training hours (trainors x trainees x days, NO x 8), trainors, trainees,
 *   activities, budget against the annual target, and the training-day total.
 *
 * `ProgramAggregates` deliberately does NOT depend on `KpiService` any more.
 * `KpiService` and `ProgramObjective` remain in the codebase, retained unread
 * (R-Q2) — they are simply no longer a source for anything the AI sees.
 */
class ProgramAggregates
{
    public function __construct(protected TrainingHoursService $hours) {}

    public function build(ExtensionProject $program): array
    {
        $program->loadMissing(['activities.faculty', 'communities', 'college']);

        $rollup = $this->hours->forProject($program);

        $activities = $program->activities;
        $completed = $activities->where('status', 'completed')->count();
        $overdue = $activities->filter(fn ($a) => $a->status !== 'completed'
            && $a->planned_end_date->isPast())->count();

        return [
            'project' => [
                'code' => $program->code,
                'title' => $program->title,
                'status' => $program->status,
                'college' => $program->college?->code,
                'period' => $program->planned_start_date->format('Y-m-d').' to '.$program->planned_end_date->format('Y-m-d'),
                'communities' => $program->communities->pluck('name')->values(),
            ],

            // The quantities the Director actually tracks. Training hours are
            // measured against the project's annual HOURS target (NULL means
            // "no target set" — never 0); budget has NO target, so it is
            // measured against the project's allocation.
            'training' => [
                'training_hours' => $rollup['actual_hours'],
                'completed_hours' => $rollup['completed_hours'],
                'training_days' => $rollup['training_days'],
                'trainors' => $rollup['trainors'],
                'trainees' => $rollup['trainees'],
                'avg_hours_per_completed' => $rollup['avg_hours_per_completed'],
                'target_hours' => $rollup['target_hours'],
                'hours_attainment_pct' => $rollup['hours_pct'],
                'formula' => 'trainors x trainees x days (no x 8)',
                // Where the trainee figure rests, so the narrative can be honest
                // about it: imported attendance vs manual entry.
                'trainee_sources' => $rollup['sources'],
            ],

            'budget' => [
                'allocated' => $rollup['allocated_budget'],
                'utilized' => $rollup['utilized_budget'],
                'utilization_pct' => $rollup['budget_pct'],
                'over_allocated' => $program->isOverAllocated(),
            ],

            'activities' => [
                'total' => $activities->count(),
                'completed' => $completed,
                'overdue' => $overdue,
            ],
        ];
    }
}
