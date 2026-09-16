<?php

namespace App\Services;

use App\Jobs\GenerateAssessmentAnalysis;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;

/**
 * Output type 1 — community assessment insights (5.11): input is the
 * AssessmentSummary aggregates ONLY (D3); output is an AssessmentAnalysis
 * draft requiring Admin approval (D4).
 */
class AssessmentAnalysisService
{
    public function generateFor(AssessmentSummary $summary, int $needsAssessmentId): AssessmentAnalysis
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $needsAssessmentId,
            'assessment_summary_id' => $summary->id,
            'raw_extracted_data' => null,
            'approval_status' => AssessmentAnalysis::APPROVAL_DRAFT,
            'status' => AssessmentAnalysis::STATUS_PENDING,
            'metadata' => [
                'model' => config('smartcemes.ai.model'),
                'prompt_version' => config('smartcemes.ai.prompt_version'),
                'generated_by' => auth()->id(),
            ],
        ]);

        activity()->performedOn($summary)->event('ai_generate_started')
            ->log('Community insights generation started (aggregates only, D3)');

        // Immediate generation (owner decision, v4.4): run in-request so the
        // draft appears right away — no queue worker dependency. On failure,
        // persist the first-class failed state (failed() hook is worker-only).
        try {
            GenerateAssessmentAnalysis::dispatchSync($analysis->id);
        } catch (\Throwable $e) {
            $analysis->refresh();
            if ($analysis->status !== AssessmentAnalysis::STATUS_FAILED) {
                $analysis->update([
                    'status' => AssessmentAnalysis::STATUS_FAILED,
                    'error_message' => 'Analysis unavailable — '.$e->getMessage(),
                ]);
            }
        }

        return $analysis;
    }

    /** D4: approval gate — approved content is what institutional outputs use. */
    public function approve(AssessmentAnalysis $analysis): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Admin-only AI access (D4).');
        abort_unless($analysis->isDraft(), 422, 'Only drafts can be approved.');

        $analysis->update([
            'approval_status' => AssessmentAnalysis::APPROVAL_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // 6.10: approved content stamps the summary (institutional use).
        $summary = $analysis->assessmentSummary;
        $summary->update([
            'ai_analysis' => $analysis->summary,
            'ai_interventions' => $analysis->recommendations,
            'ai_analysis_sections' => $analysis->problems_identified,
            'ai_analysis_generated_at' => now(),
        ]);

        activity()->performedOn($analysis)->event('approved')
            ->log("AI analysis approved for {$summary->community->name} Q{$summary->quarter} {$summary->year}");
    }

    public function discard(AssessmentAnalysis $analysis): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Admin-only AI access (D4).');
        abort_unless($analysis->isDraft(), 422, 'Only drafts can be discarded.');

        $analysis->update([
            'approval_status' => AssessmentAnalysis::APPROVAL_DISCARDED,
        ]);

        activity()->performedOn($analysis)->event('discarded')
            ->log('AI analysis discarded with provenance recorded');
    }
}
