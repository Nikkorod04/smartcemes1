<?php

namespace App\Services;

use App\Models\AssessmentSummary;
use App\Models\NeedsAssessment;
use Illuminate\Support\Collection;

/**
 * Recomputes AssessmentSummary aggregates per blueprint 6.10: invoked
 * whenever a NeedsAssessment for the same community/quarter/year is created
 * or its review_status changes; last_calculated_at is stamped every recompute.
 */
class AssessmentSummaryService
{
    /** Rating scale words → points for avg_service_satisfaction (Section IX). */
    public const RATING_SCALE = [
        'Very poor' => 1,
        'Poor' => 2,
        'Fair' => 3,
        'Good' => 4,
        'Very good' => 5,
    ];

    /**
     * R7: this class used to take `KpiService` in its constructor and never use
     * it — a leftover from the 8.6 KPI dictionary that D-R7 removed. Dropping the
     * parameter is safe: the only caller resolves this class from the container
     * with no explicit arguments (`app(AssessmentSummaryService::class)`).
     *
     * `KpiService` itself remains in the codebase, retained unread (R-Q2).
     */
    public function recomputeFor(int $communityId, int $quarter, int $year): AssessmentSummary
    {
        $batch = NeedsAssessment::query()
            ->where('community_id', $communityId)
            ->where('quarter', $quarter)
            ->where('year', $year)
            ->get();

        $summary = AssessmentSummary::query()->firstOrCreate([
            'community_id' => $communityId,
            'quarter' => $quarter,
            'year' => $year,
        ]);

        $summary->total_responses = $batch->count();
        $summary->gender_distribution = $this->countBy($batch, 'respondent_sex');
        $summary->religion_distribution = $this->countBy($batch, 'respondent_religion');
        $summary->civil_status_distribution = $this->countBy($batch, 'respondent_civil_status');
        $summary->education_distribution = $this->countByArray($batch, 'respondent_educational_attainment');
        $summary->livelihood_interests = $this->countByArray($batch, 'desired_training');
        $summary->educational_interests = $this->countByArray($batch, 'areas_of_educational_interest');
        $summary->health_problems = $this->countByArray($batch, 'health_problems');
        $summary->family_problems = $this->countByArray($batch, 'family_problems');
        $summary->employment_problems = $this->countByArray($batch, 'employment_problems');
        $summary->infrastructure_problems = $this->countByArray($batch, 'infrastructure_problems');
        $summary->economic_problems = $this->countByArray($batch, 'economic_problems');
        $summary->security_problems = $this->countByArray($batch, 'security_problems');
        $summary->water_sources = $this->countByArray($batch, 'water_source');
        $summary->house_types = $this->countByArray($batch, 'house_type');

        $summary->electricity_access_percentage = $this->percentYes($batch, 'has_electricity');
        $summary->organization_membership_percentage = $this->percentYes($batch, 'member_of_organization');
        $summary->training_availability_percentage = $this->percentYes($batch, 'available_for_training');
        $summary->avg_service_satisfaction = $this->avgSatisfaction($batch);

        // Baseline = satisfaction of the community's earliest summary.
        $earliest = AssessmentSummary::query()
            ->where('community_id', $communityId)
            ->orderBy('year')->orderBy('quarter')
            ->whereNotNull('avg_service_satisfaction')
            ->first();
        $summary->baseline_satisfaction_score = $earliest?->avg_service_satisfaction
            ?? $summary->avg_service_satisfaction;

        $summary->last_calculated_at = now();
        $summary->save();

        return $summary;
    }

    protected function countBy(Collection $batch, string $field): array
    {
        return $batch
            ->groupBy(fn ($row) => $row->{$field} ?? 'Unspecified')
            ->map->count()
            ->toArray();
    }

    protected function countByArray(Collection $batch, string $field): array
    {
        // v4.9: most of these fields are single-select strings now; the
        // problems fields remain JSON arrays. Handle both shapes.
        $counts = [];
        foreach ($batch as $row) {
            $value = $row->{$field} ?? null;

            if (is_array($value)) {
                $values = $value;
            } elseif (is_string($value) && $value !== '') {
                $values = [$value];
            } else {
                continue;
            }

            foreach ($values as $item) {
                if (is_string($item) && $item !== '') {
                    $counts[$item] = ($counts[$item] ?? 0) + 1;
                }
            }
        }
        arsort($counts);

        return $counts;
    }

    protected function percentYes(Collection $batch, string $field): ?float
    {
        if ($batch->isEmpty()) {
            return null;
        }
        $yes = $batch->where($field, 'Yes')->count();

        return $yes / $batch->count() * 100;
    }

    protected function avgSatisfaction(Collection $batch): ?float
    {
        $values = [];
        foreach ($batch as $row) {
            foreach ($row->barangay_service_ratings ?? [] as $rating) {
                $score = self::RATING_SCALE[$rating] ?? null;
                if ($score !== null) {
                    $values[] = $score;
                }
            }
        }

        return $values === [] ? null : array_sum($values) / count($values);
    }
}
