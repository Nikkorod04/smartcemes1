<?php

namespace App\Livewire;

use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Services\AssessmentAnalysisService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * ONE community's analysis dossier (redesigned 2026-11-20).
 *
 * The page answers, in order:
 *   1. what validated response data exists for this community (period by period);
 *   2. what analysis state that period is in;
 *   3. whether the Director wants to generate one.
 *
 * An analysis is keyed to a SUMMARY (community + quarter + year), so a period
 * selector is first-class. The default is the period with a live draft, else the
 * newest period with validated responses.
 *
 * Generation and approval stay exactly where they were: the service creates a
 * persisted draft, and the long review page owns Approve / Discard / Regenerate.
 * This page only decides *whether* to generate.
 */
#[Layout('layouts.app')]
class AiAnalysisCommunity extends Component
{
    public Community $community;

    /** The selected period. Null only when the community has no summaries. */
    public ?int $summaryId = null;

    /**
     * Server-rendered confirmation, keyed by analysis id — a Livewire flag rather
     * than an Alpine modal, so it cannot fail the way a `$wire.$watch` visibility
     * bridge can (handoff A14: it fires only on CHANGE).
     */
    public ?int $deletingId = null;

    public ?string $generationResult = null;

    public string $generationResultMessage = '';

    public ?int $generationResultAnalysisId = null;

    public function mount(Community $community): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        $this->community = $community;
        $this->summaryId = $this->defaultSummaryId();
    }

    public function selectPeriod(int $id): void
    {
        $this->guard();

        abort_unless(
            $this->community->assessmentSummaries()->whereKey($id)->exists(),
            404,
        );

        $this->summaryId = $id;
    }

    public function generate(int $summaryId): void
    {
        $this->guard();

        $summary = $this->community->assessmentSummaries()->findOrFail($summaryId);

        if ((int) $summary->total_responses <= 0) {
            $this->setGenerationResult('error', 'This period has no validated responses to analyse.');

            return;
        }

        // A live draft is the one thing the Director should review first. Guard
        // the service path even though the UI hides Generate in that state.
        $hasDraft = AssessmentAnalysis::query()
            ->where('assessment_summary_id', $summary->id)
            ->where('status', AssessmentAnalysis::STATUS_COMPLETED)
            ->where('approval_status', AssessmentAnalysis::APPROVAL_DRAFT)
            ->exists();

        if ($hasDraft) {
            $this->setGenerationResult(
                'error',
                'A draft already exists for this period. Review it before regenerating.',
            );

            return;
        }

        $cancelKey = $this->generationCancelKey();
        Cache::put($cancelKey, false, now()->addMinutes(10));
        $this->resetGenerationResult();

        try {
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
        $this->guard();

        Cache::put($this->generationCancelKey(), true, now()->addMinutes(10));
    }

    public function closeGenerationResult(): void
    {
        $this->resetGenerationResult();
    }

    public function confirmDelete(int $id): void
    {
        $this->guard();

        $this->analysisInScope($id);

        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(int $id): void
    {
        $this->guard();

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

    /** Retry a failed generation in place — shared with the review surface. */
    public function retry(int $id): void
    {
        $this->guard();

        $analysis = $this->analysisInScope($id);

        $failed = app(AssessmentAnalysisService::class)->retry($analysis)->status === AssessmentAnalysis::STATUS_FAILED;
        $analysis->refresh();

        $this->dispatch(
            'sc-toast',
            message: $failed ? $analysis->failureSummary() : 'Retry complete — the draft is ready to review',
            type: $failed ? 'error' : 'info'
        );
    }

    /**
     * Clear every FAILED attempt for this community.
     *
     * The common case by far — failed calls are noise. Kept separate from the
     * per-row delete so the usual cleanup is one click.
     */
    public function clearFailed(): void
    {
        $this->guard();

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
        $periods = $this->community->assessmentSummaries()
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get();

        if ($this->summaryId !== null && ! $periods->contains('id', $this->summaryId)) {
            $this->summaryId = $this->defaultSummaryId($periods);
        }

        $selected = $this->summaryId !== null
            ? $periods->firstWhere('id', $this->summaryId)
            : null;

        $analyses = $selected
            ? AssessmentAnalysis::query()
                ->where('assessment_summary_id', $selected->id)
                ->with('approver')
                ->orderByDesc('id')
                ->get()
            : collect();

        $allAnalyses = $this->analyses();

        return view('livewire.ai-analysis-community', [
            'periods' => $periods,
            'selected' => $selected,
            'selectedState' => $selected ? $this->stateForSummary($selected, $allAnalyses) : 'no_data',
            'periodStates' => $periods->mapWithKeys(
                fn (AssessmentSummary $summary) => [$summary->id => $this->stateForSummary($summary, $allAnalyses)]
            )->all(),
            'analyses' => $analyses,
            'currentDraft' => $analyses->first(
                fn (AssessmentAnalysis $a) => $a->approval_status === AssessmentAnalysis::APPROVAL_DRAFT
                    && $a->status === AssessmentAnalysis::STATUS_COMPLETED
                    && $a->isCurrent()
            ),
            'latestFailed' => $analyses->first(
                fn (AssessmentAnalysis $a) => $a->status === AssessmentAnalysis::STATUS_FAILED
            ),
            'approvedAnalysis' => $analyses->first(
                fn (AssessmentAnalysis $a) => $a->approval_status === AssessmentAnalysis::APPROVAL_APPROVED
            ),
            'aggregates' => $selected ? $this->aggregatesFor($selected) : [],
            'sourceCounts' => $selected ? $this->sourceCounts($selected) : collect(),
            'unvalidatedCount' => $selected ? $this->unvalidatedCount($selected) : 0,
            'totalCount' => $allAnalyses->count(),
            'failedCount' => $allAnalyses->where('status', AssessmentAnalysis::STATUS_FAILED)->count(),
        ]);
    }

    /**
     * Default period: a live draft first (that is the pending decision), then the
     * newest period with validated responses, then the newest period.
     *
     * @param  Collection<int, AssessmentSummary>|null  $periods
     */
    private function defaultSummaryId(?Collection $periods = null): ?int
    {
        $periods ??= $this->community->assessmentSummaries()
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get();

        if ($periods->isEmpty()) {
            return null;
        }

        $draftSummaryId = AssessmentAnalysis::query()
            ->whereIn('assessment_summary_id', $periods->pluck('id'))
            ->where('status', AssessmentAnalysis::STATUS_COMPLETED)
            ->where('approval_status', AssessmentAnalysis::APPROVAL_DRAFT)
            ->orderByDesc('id')
            ->value('assessment_summary_id');

        if ($draftSummaryId !== null) {
            return (int) $draftSummaryId;
        }

        $withData = $periods->first(fn (AssessmentSummary $s) => (int) $s->total_responses > 0);

        return (int) ($withData ?? $periods->first())->id;
    }

    /** @param Collection<int, AssessmentAnalysis> $allAnalyses */
    private function stateForSummary(AssessmentSummary $summary, Collection $allAnalyses): string
    {
        $rows = $allAnalyses->where('assessment_summary_id', $summary->id);

        return match (true) {
            $rows->contains(fn (AssessmentAnalysis $a) => $a->status === AssessmentAnalysis::STATUS_COMPLETED
                && $a->approval_status === AssessmentAnalysis::APPROVAL_DRAFT) => 'draft',
            $rows->contains(fn (AssessmentAnalysis $a) => $a->status === AssessmentAnalysis::STATUS_PENDING) => 'pending',
            $rows->contains(fn (AssessmentAnalysis $a) => $a->status === AssessmentAnalysis::STATUS_FAILED) => 'failed',
            (int) $summary->total_responses > 0 => 'needs',
            $rows->contains(fn (AssessmentAnalysis $a) => $a->approval_status === AssessmentAnalysis::APPROVAL_APPROVED) => 'approved',
            default => 'no_data',
        };
    }

    /** The exact aggregate shape the shared response-data panel renders. */
    private function aggregatesFor(AssessmentSummary $summary): array
    {
        return [
            'total_responses' => (int) $summary->total_responses,
            'community' => $this->community->name,
            'quarter' => $summary->quarter,
            'year' => $summary->year,
            'gender_distribution' => $summary->gender_distribution,
            'religion_distribution' => $summary->religion_distribution,
            'civil_status_distribution' => $summary->civil_status_distribution,
            'education_distribution' => $summary->education_distribution,
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
            'electricity_access_percentage' => $summary->electricity_access_percentage,
            'organization_membership_percentage' => $summary->organization_membership_percentage,
            'training_availability_percentage' => $summary->training_availability_percentage,
            'avg_service_satisfaction' => $summary->avg_service_satisfaction,
        ];
    }

    /** @return Collection<string, int> */
    private function sourceCounts(AssessmentSummary $summary): Collection
    {
        return NeedsAssessment::query()
            ->where('community_id', $this->community->id)
            ->where('quarter', $summary->quarter)
            ->where('year', $summary->year)
            ->where('review_status', 'validated')
            ->selectRaw('source, count(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source');
    }

    private function unvalidatedCount(AssessmentSummary $summary): int
    {
        return NeedsAssessment::query()
            ->where('community_id', $this->community->id)
            ->where('quarter', $summary->quarter)
            ->where('year', $summary->year)
            ->where('review_status', '!=', 'validated')
            ->count();
    }

    /** Every analysis for THIS community. */
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

    private function guard(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');
    }
}
