<?php

namespace App\Jobs;

use App\Models\AssessmentAnalysis;
use App\Models\InteragencyAgency;
use App\Models\User;
use App\Notifications\SmartCemesNotification;
use App\Services\Ai\ConfidenceScore;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\Prompts\PromptV2;
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

        // R6 / D-R10: the catalogue is read fresh per analysis and both injected
        // into the prompt and snapshotted into metadata, so the exact citable set
        // this referral came from can be reconstructed later even after the
        // Director edits or retires an agency.
        $catalogue = InteragencyAgency::promptCatalogue();

        $result = $client->generate(PromptV2::assessmentAnalysis($aggregates, $catalogue));

        $referrals = $this->validatedReferrals($result['data']['interagency_referrals'] ?? []);

        $analysis->update([
            'summary' => $result['data']['summary'] ?? null,
            'problems_identified' => $result['data']['problems_identified'] ?? null,
            'recommendations' => $result['data']['recommendations'] ?? null,
            'interagency_referrals' => $referrals,
            'extracted_fields' => $result['data']['problems_identified'] ?? null,
            // Gemini reports no confidence — derived data-confidence heuristic (guidance only).
            'confidence_score' => ConfidenceScore::forAnalysis($aggregates, $result),
            'status' => AssessmentAnalysis::STATUS_COMPLETED,
            'error_message' => null,
            'metadata' => $result['metadata'] + [
                'confidence_basis' => 'derived: sample size + aggregate completeness + output completeness',
                // R6: the referral audit trail. The snapshot is the agency set the
                // model was allowed to cite; `unverified` counts anything it
                // returned outside that set, so a prompt regression is visible in
                // the stored record instead of only at render time.
                'agency_catalogue_snapshot' => $catalogue,
                'referrals_returned' => count($result['data']['interagency_referrals'] ?? []),
                'referrals_accepted' => count($referrals),
                'referrals_unverified' => count($result['data']['interagency_referrals'] ?? []) - count($referrals),
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

    /**
     * R6 — keep only referrals citing a real catalogue agency.
     *
     * WHY A SERVER-SIDE FILTER RATHER THAN TRUSTING THE PROMPT
     * -------------------------------------------------------
     * The prompt forbids inventing an agency, but a language model can still
     * produce one, and the whole value of the catalogue is that every referral is
     * defensible. Rather than silently dropping a bad row (which hides a prompt
     * regression) or letting it through (which defeats the guardrail), this
     * accepts only codes that resolve, backfills the catalogue's own name so the
     * UI never shows a model-authored agency name, and lets the rejection count
     * land in `metadata` so the reviewer can see it happened.
     *
     * @param  mixed  $referrals  the model's `interagency_referrals` value
     * @return array<int, array{need: string, agency_code: string, agency_name: string, rationale: string}>
     */
    protected function validatedReferrals(mixed $referrals): array
    {
        if (! is_array($referrals)) {
            return [];
        }

        return collect($referrals)
            ->filter(fn ($r) => is_array($r) && filled($r['agency_code'] ?? null))
            ->map(function (array $referral) {
                $agency = InteragencyAgency::resolve($referral['agency_code']);

                if ($agency === null) {
                    return null; // not in the catalogue — see the docblock
                }

                return [
                    'need' => (string) ($referral['need'] ?? '—'),
                    // The catalogue's code casing wins, not the model's.
                    'agency_code' => $agency->agency_code,
                    // Deliberately the CATALOGUE name: the model's `agency_name`
                    // is never trusted for display.
                    'agency_name' => $agency->agency_name,
                    'rationale' => (string) ($referral['rationale'] ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
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
