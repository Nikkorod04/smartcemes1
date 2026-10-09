<?php

namespace App\Livewire;

use App\Models\AssessmentAnalysis;
use App\Models\Community;
use App\Services\AssessmentAnalysisService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Every AI analysis this office has produced for ONE community or school.
 *
 * Why it exists: the queue groups by community but **paginates by group**, so a
 * community's rows can sit on any page — and there is no way to LINK to one
 * community. A Director preparing for a barangay visit wants "everything about
 * Brgy. San Jose" in one place, across periods.
 *
 * The page is keyed to the **community**, not to a period: a community has one
 * summary per quarter/year (`assessment_summaries` is unique on
 * community_id + quarter + year), so this lists PERIODS, each with its own
 * generation lineage. That is also where the delete action lives, because
 * "clear the failed attempts" is a per-community housekeeping task.
 */
#[Layout('layouts.app')]
class AiAnalysisCommunity extends Component
{
    public Community $community;

    /**
     * Server-rendered confirmation, keyed by analysis id — a Livewire flag rather
     * than an Alpine modal, so it cannot fail the way a `$wire.$watch` visibility
     * bridge can (handoff §14: it fires only on CHANGE).
     */
    public ?int $deletingId = null;

    public ?string $generationResult = null;

    public string $generationResultMessage = '';

    public ?int $generationResultAnalysisId = null;

    public function mount(Community $community): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        $this->community = $community;
    }

    public function confirmDelete(int $id): void
    {
        // Resolve it NOW so an out-of-scope id fails here rather than at delete time.
        $this->analysisInScope($id);

        $this->deletingId = $id;
    }

    /**
     * Generate for one of this community's periods.
     *
     * Same service path as the queue, so the two entry points cannot diverge.
     */
    public function generate(int $summaryId): void
    {
        $cancelKey = $this->generationCancelKey();
        Cache::put($cancelKey, false, now()->addMinutes(10));
        $this->resetGenerationResult();

        try {
            // Scoped: the id comes from the browser and must be one of THIS community's.
            $summary = $this->community->assessmentSummaries()->findOrFail($summaryId);
            $service = app(AssessmentAnalysisService::class);

            $analysis = $service->generateFor($summary->fresh(), $service->batchIdFor($summary));
            $analysis->refresh();

            if (Cache::pull($cancelKey) === true) {
                $analysis->update([
                    'status' => AssessmentAnalysis::STATUS_FAILED,
                    'error_message' => 'Generation canceled by the Director.',
                ]);
                $this->setGenerationResult('canceled', 'The AI generation was canceled. No analysis was published.');

                return;
            }

            if ($analysis->status === AssessmentAnalysis::STATUS_COMPLETED) {
                $this->generationResultAnalysisId = $analysis->id;
                $this->setGenerationResult('success', 'The AI analysis was generated from validated aggregate responses and is ready for review.');

                return;
            }

            $this->setGenerationResult('error', $analysis->failureSummary());
        } catch (\Throwable $e) {
            Cache::forget($cancelKey);
            $this->setGenerationResult('error', 'Analysis unavailable — '.$e->getMessage());
        }
    }

    /** Request cancellation for the current synchronous generation. */
    public function cancelGeneration(): void
    {
        Cache::put($this->generationCancelKey(), true, now()->addMinutes(10));
    }

    public function closeGenerationResult(): void
    {
        $this->resetGenerationResult();
    }

    private function generationCancelKey(): string
    {
        return 'ai-analysis:generation-cancel:'.auth()->id();
    }

    private function resetGenerationResult(): void
    {
        $this->generationResult = null;
        $this->generationResultMessage = '';
        $this->generationResultAnalysisId = null;
    }

    private function setGenerationResult(string $type, string $message): void
    {
        $this->generationResult = $type;
        $this->generationResultMessage = $message;
    }

    /** Retry a failed generation in place — shared with the queue and the review. */
    public function retry(int $id): void
    {
        $analysis = $this->analysisInScope($id);

        $failed = app(AssessmentAnalysisService::class)->retry($analysis)->status === AssessmentAnalysis::STATUS_FAILED;
        $analysis->refresh();

        $this->dispatch(
            'sc-toast',
            message: $failed ? $analysis->failureSummary() : 'Retry complete — the draft is ready to review',
            type: $failed ? 'error' : 'info'
        );
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(int $id): void
    {
        $analysis = $this->analysisInScope($id);

        abort_unless(
            $analysis->isDeletable(),
            422,
            'An approved analysis, or the current draft, cannot be deleted.'
        );

        activity()->performedOn($analysis)->event('deleted')
            ->log("AI analysis deleted for {$this->community->name}");

        $analysis->delete();

        $this->deletingId = null;

        $this->dispatch('sc-toast', message: 'Generation deleted', type: 'warn');
    }

    /**
     * Clear every FAILED attempt for this community.
     *
     * The common case by far — 9 of the 12 live generations are failed noise. Kept
     * separate from the per-row delete so the usual cleanup is one click.
     */
    public function clearFailed(): void
    {
        $removed = 0;

        foreach ($this->analyses() as $analysis) {
            if ($analysis->status !== AssessmentAnalysis::STATUS_FAILED || ! $analysis->isDeletable()) {
                continue;
            }

            activity()->performedOn($analysis)->event('deleted')
                ->log("AI analysis deleted (failed attempt) for {$this->community->name}");

            $analysis->delete();
            $removed++;
        }

        $this->dispatch(
            'sc-toast',
            message: $removed === 0
                ? 'No failed attempts to clear'
                : $removed.' failed '.($removed === 1 ? 'attempt' : 'attempts').' cleared',
            type: $removed === 0 ? 'info' : 'warn'
        );
    }

    public function render()
    {
        $analyses = $this->analyses();

        return view('livewire.ai-analysis-community', [
            'periods' => $this->community->assessmentSummaries()
                ->orderByDesc('year')
                ->orderByDesc('quarter')
                ->get(),
            'bySummary' => $analyses->groupBy('assessment_summary_id'),
            'totalCount' => $analyses->count(),
            'failedCount' => $analyses->where('status', AssessmentAnalysis::STATUS_FAILED)->count(),
        ]);
    }

    /**
     * Every analysis for THIS community.
     *
     * Reached through the community's summaries rather than through
     * `needs_assessment_id`, which is vestigial (the lowest-id respondent row —
     * see docs/AI-ANALYSIS-REDESIGN-PLAN.md §8).
     *
     * @return Collection<int, AssessmentAnalysis>
     */
    private function analyses(): Collection
    {
        return AssessmentAnalysis::query()
            ->with('approver')
            ->whereIn('assessment_summary_id', $this->summaryIds())
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Resolve an id that must belong to THIS community.
     *
     * The id arrives from the browser, so it is scoped here — otherwise a crafted
     * request could delete another community's analysis.
     */
    private function analysisInScope(int $id): AssessmentAnalysis
    {
        return AssessmentAnalysis::query()
            ->whereIn('assessment_summary_id', $this->summaryIds())
            ->findOrFail($id);
    }

    /** @return Collection<int, int> */
    private function summaryIds(): Collection
    {
        return $this->community->assessmentSummaries()->pluck('id');
    }
}
