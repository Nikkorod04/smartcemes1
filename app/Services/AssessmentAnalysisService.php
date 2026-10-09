<?php

namespace App\Services;

use App\Jobs\GenerateAssessmentAnalysis;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\NeedsAssessment;

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

    /**
     * The submission row an analysis is filed against.
     *
     * ⚠️ `needs_assessments` holds **one row per respondent** (D11), so a summary
     * routinely has many rows contributed by several people. This returns the
     * LOWEST-ID row — an arbitrary but stable choice, kept because the FK is
     * non-nullable and historical. The analysis's real parent is the SUMMARY; do
     * not render this as "submitted by" (see docs/AI-ANALYSIS-REDESIGN-PLAN.md §8).
     *
     * Centralised here so the queue's per-row Generate and Regenerate cannot pick
     * different rows.
     */
    public function batchIdFor(AssessmentSummary $summary): int
    {
        $id = NeedsAssessment::query()
            ->where('community_id', $summary->community_id)
            ->where('quarter', $summary->quarter)
            ->where('year', $summary->year)
            ->orderBy('id')
            ->value('id');

        abort_if($id === null, 422, 'No submission batch found for this summary.');

        return (int) $id;
    }

    /**
     * Regenerate: produce a NEW analysis for the same summary.
     *
     * The previous generation is left INTACT and simply becomes `superseded` in
     * the queue (lineage is derived, never stored — AssessmentAnalysis::isCurrent).
     * That is what makes a regeneration auditable rather than destructive.
     */
    public function regenerate(AssessmentAnalysis $analysis): AssessmentAnalysis
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        return $this->generateFor($analysis->assessmentSummary, $this->batchIdFor($analysis->assessmentSummary));
    }

    /** D4: approval gate — approved content is what institutional outputs use. */
    /**
     * Retry a FAILED generation IN PLACE.
     *
     * Reuses the same analysis row, so provenance and lineage are unchanged, and
     * the same summary aggregates (D3) — it writes no partial draft. Shared by the
     * queue and the review surface so the two cannot drift.
     */
    public function retry(AssessmentAnalysis $analysis): AssessmentAnalysis
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');
        abort_unless($analysis->status === AssessmentAnalysis::STATUS_FAILED, 422, 'Only a failed generation can be retried.');

        $analysis->update([
            'status' => AssessmentAnalysis::STATUS_PENDING,
            'error_message' => null,
        ]);

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

        return $analysis->refresh();
    }

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
        //
        // ⚠️ WRITE-ONLY. Nothing reads these four columns — `assessment_analyses` is
        // the single source of truth for AI output (docs/AI-ANALYSIS-REDESIGN-PLAN.md
        // §1.3, Decision 5). They survive because `Phase5AiTest` asserts them and
        // retiring them is its own decision. **Do not start reading them**, or a
        // second source of truth appears and the two can drift.
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
