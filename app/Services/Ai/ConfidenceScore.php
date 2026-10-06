<?php

namespace App\Services\Ai;

/**
 * Derives the confidence_score persisted on AssessmentAnalysis and
 * ProgramNarrative. Gemini reports no confidence indicator, so this is a
 * deterministic DATA confidence heuristic (blueprint §6.11/§6.16: displayed
 * as guidance only, never treated as ground truth):
 *
 *  - sample/coverage: how much validated data backed the generation
 *  - output completeness: whether the model returned every expected section
 *
 * Result is clamped to [0.10, 0.95] — a derived score never claims certainty.
 */
class ConfidenceScore
{
    public const MIN = 0.10;

    public const MAX = 0.95;

    /** AssessmentAnalysis: n respondents + aggregate completeness + returned sections. */
    public static function forAnalysis(array $aggregates, array $result): float
    {
        $n = max(0, (int) ($aggregates['total_responses'] ?? 0));
        $sample = min(1, $n / 25);

        $distributionKeys = [
            'gender_distribution', 'religion_distribution', 'education_distribution',
            'civil_status_distribution', 'livelihood_interests', 'educational_interests',
            'health_problems', 'family_problems', 'employment_problems',
            'infrastructure_problems', 'economic_problems', 'security_problems',
            'water_sources', 'house_types',
        ];
        $present = collect($distributionKeys)
            ->filter(fn ($key) => ! empty($aggregates[$key]))->count();
        $completeness = count($distributionKeys) > 0 ? $present / count($distributionKeys) : 0;

        $sections = collect(['summary', 'problems_identified', 'recommendations'])
            ->filter(fn ($key) => ! empty($result['data'][$key] ?? null))->count();
        $output = $sections / 3;

        return self::scale(0.5 * $sample + 0.3 * $completeness + 0.2 * $output);
    }

    /** ProgramNarrative: metric coverage + project richness + returned sections. */
    public static function forNarrative(array $aggregates, array $result): float
    {
        // R5: coverage is measured over the R4/R5 metric dictionary the narrative
        // is now built from — was the 8.6 KPI list, which the payload no longer
        // carries (and which the UI no longer displays).
        $metricPaths = [
            'training.trainors', 'training.trainees', 'training.training_hours',
            'training.training_days', 'budget.utilized', 'activities.total',
        ];
        $present = collect($metricPaths)
            ->filter(fn ($path) => data_get($aggregates, $path) !== null)->count();
        $coverage = count($metricPaths) > 0 ? $present / count($metricPaths) : 0;

        // Richness: a project with activities and at least one measured input is
        // narratable. Objectives no longer contribute — they are retained unread.
        $richness = (($aggregates['activities']['total'] ?? 0) > 0 ? 0.5 : 0)
            + (($aggregates['training']['trainors'] ?? 0) > 0 ? 0.5 : 0);

        $sections = collect(['summary', 'health_label', 'risks', 'recommendations'])
            ->filter(fn ($key) => ! empty($result['data'][$key] ?? null))->count();
        $output = $sections / 4;

        return self::scale(0.5 * $coverage + 0.25 * $richness + 0.25 * $output);
    }

    protected static function scale(float $raw): float
    {
        return round(max(self::MIN, min(self::MAX, $raw)), 2);
    }
}
