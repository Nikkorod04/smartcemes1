<?php

namespace App\Services\Ai;

use App\Models\ExtensionProgram;
use App\Services\KpiService;
use Illuminate\Support\Str;

/**
 * Builds the AGGREGATE program-level payload for the narrative pipeline
 * (D3): objective statuses, 8.6 KPIs, activity completion, budget warnings,
 * at-risk signals. No PII.
 */
class ProgramAggregates
{
    public function __construct(protected KpiService $kpi) {}

    public function build(ExtensionProgram $program): array
    {
        $objectives = $program->programObjectives->map(fn ($o) => [
            'objective' => Str::limit($o->objective, 120),
            'kpi_metric' => $o->kpi_metric,
            'baseline' => $o->baseline_value !== null ? (float) $o->baseline_value : null,
            'target' => $o->target_value !== null ? (float) $o->target_value : null,
            'actual' => $this->kpi->effectiveActual($o) !== null ? round((float) $this->kpi->effectiveActual($o), 2) : null,
            'status' => $this->kpi->statusFor($o),
            'target_date' => $o->target_date?->format('Y-m-d'),
        ]);

        $activities = $program->activities;
        $completed = $activities->where('status', 'completed')->count();
        $overdue = $activities->filter(fn ($a) => $a->status !== 'completed'
            && $a->planned_end_date->isPast())->count();

        return [
            'program' => [
                'code' => $program->code,
                'status' => $program->status,
                'period' => $program->planned_start_date->format('Y-m-d').' to '.$program->planned_end_date->format('Y-m-d'),
                'communities' => $program->communities->pluck('name')->values(),
            ],
            'objectives' => [
                'total' => $program->programObjectives->count(),
                'status_counts' => collect(['achieved', 'on_track', 'not_met', 'not_started'])
                    ->mapWithKeys(fn ($s) => [$s => $program->programObjectives->where('status', $s)->count()]),
                'list' => $objectives,
            ],
            'kpis' => [
                'participation_rate' => $this->kpi->participationRate($program) !== null ? round($this->kpi->participationRate($program), 1) : null,
                'activity_completion_rate' => $this->kpi->activityCompletionRate($program) !== null ? round($this->kpi->activityCompletionRate($program), 1) : null,
                'attendance_consistency' => $this->kpi->attendanceConsistency($program) !== null ? round($this->kpi->attendanceConsistency($program), 1) : null,
                'budget_utilization' => $this->kpi->budgetUtilization($program) !== null ? round($this->kpi->budgetUtilization($program), 1) : null,
                'knowledge_gain' => $this->kpi->knowledgeGain($program) !== null ? round($this->kpi->knowledgeGain($program), 2) : null,
                'cost_per_beneficiary' => $this->kpi->costPerBeneficiary($program) !== null ? round($this->kpi->costPerBeneficiary($program), 2) : null,
                'community_reach' => $this->kpi->communityReach($program),
            ],
            'activities' => [
                'total' => $activities->count(),
                'completed' => $completed,
                'overdue' => $overdue,
            ],
            'budget' => [
                'allocated' => (float) $program->allocated_budget,
                'utilized' => $program->utilizedBudget(),
                'over_allocated' => $program->isOverAllocated(),
            ],
        ];
    }
}
