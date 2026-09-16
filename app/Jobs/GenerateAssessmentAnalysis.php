<?php

namespace App\Jobs;

use App\Models\AssessmentAnalysis;
use App\Models\User;
use App\Notifications\SmartCemesNotification;
use App\Services\Ai\ConfidenceScore;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\Prompts\PromptV1;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAssessmentAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** 8.5: retry with backoff (minutes between attempts). */
    public array $backoff = [60, 180];

    public function __construct(public int $analysisId) {}

    public function handle(GeminiClient $client): void
    {
        $analysis = AssessmentAnalysis::with('assessmentSummary.community')->findOrFail($this->analysisId);
        $summary = $analysis->assessmentSummary;

        // D3: aggregates ONLY.
        $aggregates = [
            'community' => $summary->community->name,
            'quarter' => (int) $summary->quarter,
            'year' => (int) $summary->year,
            'total_responses' => (int) $summary->total_responses,
            'gender_distribution' => $summary->gender_distribution,
            'religion_distribution' => $summary->religion_distribution,
            'education_distribution' => $summary->education_distribution,
            'civil_status_distribution' => $summary->civil_status_distribution,
            'livelihood_interests' => $summary->livelihood_interests,
            'educational_interests' => $summary->educational_interests,
            'health_problems' => $summary->health_problems,
            'family_problems' => $summary->family_problems,
            'employment_problems' => $summary->employment_problems,
            'infrastructure_problems' => $summary->infrastructure_problems,
            'economic_problems' => $summary->economic_problems,
            'security_problems' => $summary->security_problems,
            'water_sources' => $summary->water_sources,
            'house_types' => $summary->house_types,
            'electricity_access_percentage' => $summary->electricity_access_percentage !== null ? round((float) $summary->electricity_access_percentage, 1) : null,
            'organization_membership_percentage' => $summary->organization_membership_percentage !== null ? round((float) $summary->organization_membership_percentage, 1) : null,
            'training_availability_percentage' => $summary->training_availability_percentage !== null ? round((float) $summary->training_availability_percentage, 1) : null,
            'avg_service_satisfaction' => $summary->avg_service_satisfaction !== null ? round((float) $summary->avg_service_satisfaction, 2) : null,
        ];

        $analysis->update(['raw_extracted_data' => $aggregates]);

        $result = $client->generate(PromptV1::assessmentAnalysis($aggregates));

        $analysis->update([
            'summary' => $result['data']['summary'] ?? null,
            'problems_identified' => $result['data']['problems_identified'] ?? null,
            'recommendations' => $result['data']['recommendations'] ?? null,
            'extracted_fields' => $result['data']['problems_identified'] ?? null,
            // Gemini reports no confidence — derived data-confidence heuristic (guidance only).
            'confidence_score' => ConfidenceScore::forAnalysis($aggregates, $result),
            'status' => AssessmentAnalysis::STATUS_COMPLETED,
            'error_message' => null,
            'metadata' => $result['metadata'] + [
                'confidence_basis' => 'derived: sample size + aggregate completeness + output completeness',
            ],
        ]);

        // 5.14: AI analysis ready for review (Admin).
        $admin = User::where('role', 'admin')->first();
        $communityName = $summary->community->name ?? 'Community';
        $admin?->notify(new SmartCemesNotification(
            'AI analysis ready for review',
            "Draft insights for {$communityName} Q{$summary->quarter} {$summary->year} are ready — review and approve.",
            'sparkles', 'blue',
        ));
    }

    /** 8.5: terminal failed state persisted with error_message. */
    public function failed(\Throwable $e): void
    {
        $analysis = AssessmentAnalysis::find($this->analysisId);
        if ($analysis) {
            $analysis->update([
                'status' => AssessmentAnalysis::STATUS_FAILED,
                'error_message' => 'Analysis unavailable — '.$e->getMessage(),
            ]);
        }

        Log::error('Assessment analysis generation failed', ['analysis_id' => $this->analysisId, 'error' => $e->getMessage()]);
    }
}
