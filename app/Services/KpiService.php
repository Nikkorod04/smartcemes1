<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ExtensionProject;
use App\Models\ProgramObjective;

/**
 * KPI dictionary implementation — blueprint 8.6 is the single source of
 * truth. All dashboards, reports, objective actuals and at-risk lists
 * derive EXCLUSIVELY from these formulas.
 */
class KpiService
{
    /** 8.6: distinct beneficiaries with >= 1 attendance / linked * 100. */
    public function participationRate(ExtensionProject $program): ?float
    {
        $linked = $program->beneficiaries()->count();
        if ($linked === 0) {
            return null;
        }

        return $this->distinctServed($program) / $linked * 100;
    }

    /** 8.6: completed / (total - cancelled) * 100. */
    public function activityCompletionRate(ExtensionProject $program): ?float
    {
        $eligible = $program->activities()->whereNotIn('status', ['cancelled'])->count();
        if ($eligible === 0) {
            return null;
        }

        $completed = $program->activities()->where('status', 'completed')->count();

        return $completed / $eligible * 100;
    }

    /** 8.6: mean over participating beneficiaries of (attended / eligible) * 100. */
    public function attendanceConsistency(ExtensionProject $program): ?float
    {
        $eligible = $program->activities()->whereNotIn('status', ['cancelled'])->pluck('id');
        if ($eligible->isEmpty()) {
            return null;
        }

        $perBeneficiary = Attendance::query()
            ->whereIn('activity_id', $eligible)
            ->whereIn('status', ['present', 'late'])
            ->selectRaw('beneficiary_id, COUNT(DISTINCT activity_id) as attended')
            ->groupBy('beneficiary_id')
            ->get();

        if ($perBeneficiary->isEmpty()) {
            return null;
        }

        $eligibleCount = $eligible->count();

        return $perBeneficiary->avg(fn ($row) => $row->attended / $eligibleCount * 100);
    }

    /** 8.6: SUM(amount) / allocated_budget * 100. */
    public function budgetUtilization(ExtensionProject $program): ?float
    {
        if ((float) $program->allocated_budget === 0.0) {
            return null;
        }

        return $program->utilizedBudget() / (float) $program->allocated_budget * 100;
    }

    /** 8.6: MEAN(post - pre) across activities that have both scores. */
    public function knowledgeGain(ExtensionProject $program): ?float
    {
        $gains = $program->activities()
            ->whereNotNull('pre_assessment_score')
            ->whereNotNull('post_assessment_score')
            ->get(['pre_assessment_score', 'post_assessment_score'])
            ->map(fn ($a) => (float) $a->post_assessment_score - (float) $a->pre_assessment_score);

        if ($gains->isEmpty()) {
            return null;
        }

        return $gains->avg();
    }

    /** 8.6: SUM(amount) / distinct beneficiaries served. */
    public function costPerBeneficiary(ExtensionProject $program): ?float
    {
        $served = $this->distinctServed($program);
        if ($served === 0) {
            return null;
        }

        return $program->utilizedBudget() / $served;
    }

    /** 8.6: distinct beneficiaries served (present/late attendance). */
    public function communityReach(ExtensionProject $program): int
    {
        return $this->distinctServed($program);
    }

    public function distinctServed(ExtensionProject $program): int
    {
        $activityIds = $program->activities()->whereNotIn('status', ['cancelled'])->pluck('id');

        return Attendance::query()
            ->whereIn('activity_id', $activityIds)
            ->whereIn('status', ['present', 'late'])
            ->distinct('beneficiary_id')
            ->count('beneficiary_id');
    }

    /** System-wide community reach (distinct served across all programs). */
    public function communityReachAll(): int
    {
        return Attendance::query()
            ->whereIn('status', ['present', 'late'])
            ->whereHas('activity', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->distinct('beneficiary_id')
            ->count('beneficiary_id');
    }

    /**
     * Effective actual for an objective (8.6 derivation rules):
     * numeric objectives live-compute; if the formula yields no data yet,
     * any stored/manual value is used as fallback. Stored actual_value is
     * authoritative only for qualitative objectives.
     */
    public function effectiveActual(ProgramObjective $objective): ?float
    {
        if ($objective->isQualitative()) {
            return $objective->actual_value !== null ? (float) $objective->actual_value : null;
        }

        $live = $this->liveKpi($objective);

        return $live ?? ($objective->actual_value !== null ? (float) $objective->actual_value : null);
    }

    /**
     * Live 8.6 formula value for a numeric objective over the program's
     * data — null when the formula has no data yet.
     */
    public function liveKpi(ProgramObjective $objective): ?float
    {
        if ($objective->isQualitative() || $objective->kpi_metric === null || $objective->program === null) {
            return null;
        }

        return match ($objective->kpi_metric) {
            'participation_rate' => $this->participationRate($objective->program),
            'activity_completion_rate' => $this->activityCompletionRate($objective->program),
            'attendance_consistency' => $this->attendanceConsistency($objective->program),
            'budget_utilization' => $this->budgetUtilization($objective->program),
            'knowledge_gain' => $this->knowledgeGain($objective->program),
            'cost_per_beneficiary' => $this->costPerBeneficiary($objective->program),
            'community_reach' => $this->communityReach($objective->program) ?: null,
            default => null,
        };
    }

    /**
     * How the effective actual was obtained (objective manager source
     * tag): manual = qualitative stored value, live = 8.6 formula,
     * stored = manual fallback for a numeric objective, null = no data.
     */
    public function actualSource(ProgramObjective $objective): ?string
    {
        if ($objective->isQualitative()) {
            return $objective->actual_value !== null ? 'manual' : null;
        }

        if ($this->liveKpi($objective) !== null) {
            return 'live';
        }

        return $objective->actual_value !== null ? 'stored' : null;
    }

    /**
     * Objectives needing attention: not_met (any), or on_track with the target
     * date within N days. Status is ALWAYS derived live from the effective
     * actual (8.6) — never the stale stored column.
     *
     * RETAINED BUT UNREAD (R-Q2): both former callers are gone — the dashboard
     * Action Center was removed by the P0m prototype pass, and the Analytics
     * pending tab was removed 2026-09-27. Only ObjectiveStatusTest calls this.
     * Do not wire up a new caller; the 8.6 objective surface is retired (D-R7).
     */
    public function objectivesAtRisk(int $withinDays = 14)
    {
        return ProgramObjective::query()
            ->with('program')
            ->whereNotNull('target_date')
            ->get()
            ->filter(function (ProgramObjective $o) use ($withinDays) {
                $status = $this->statusFor($o);

                if ($status === 'not_met') {
                    return true;
                }

                return $status === 'on_track'
                    && $o->target_date->gte(now()->startOfDay())
                    && $o->target_date->lte(now()->copy()->addDays($withinDays)->endOfDay());
            })
            ->values();
    }

    /**
     * Objective status derivation (8.6) — ALWAYS derived from the effective
     * actual, never a stale column.
     */
    public function statusFor(ProgramObjective $objective): string
    {
        $target = $objective->target_value;
        if ($target === null) {
            return 'not_started';
        }

        $actual = $this->effectiveActual($objective);
        if ($actual === null) {
            return 'not_started';
        }

        if ($actual >= $target) {
            return 'achieved';
        }

        if ($objective->target_date !== null && $objective->target_date->isPast()) {
            return 'not_met';
        }

        return 'on_track';
    }
}
