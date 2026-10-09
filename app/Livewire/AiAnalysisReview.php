<?php

namespace App\Livewire;

use App\Models\AssessmentAnalysis;
use App\Models\NeedsAssessment;
use App\Services\AssessmentAnalysisService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The REVIEW surface for ONE community needs analysis.
 *
 * Split out of the old single-page AiAnalysis component on 2026-10-07 — see
 * `docs/AI-ANALYSIS-REDESIGN-PLAN.md`. Why it exists: the old page fed its
 * review surface from `$drafts->first()`, so it could only ever render a
 * `completed` + `draft` analysis. An APPROVED analysis therefore had no reading
 * surface at all, and neither did a discarded one — even though "citable in
 * reports" is the whole point of the approval gate.
 *
 * This route renders ANY state. Only a draft offers the gate.
 */
#[Layout('layouts.app')]
class AiAnalysisReview extends Component
{
    public AssessmentAnalysis $analysis;

    /**
     * Server-rendered confirmation. Deliberately a Livewire flag rather than an
     * Alpine modal: a click-driven `@if` cannot fail the way a `$wire.$watch`
     * visibility bridge can (handoff §14 — it fires only on CHANGE).
     */
    public bool $confirmingDiscard = false;

    public function mount(AssessmentAnalysis $analysis): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        $this->analysis = $analysis;
    }

    /** D4: the approval gate. */
    public function approve(): void
    {
        app(AssessmentAnalysisService::class)->approve($this->analysis);
        $this->analysis->refresh();

        $this->dispatch('sc-toast', message: 'Analysis approved — institutional use unlocked', type: 'success');
    }

    public function confirmDiscard(): void
    {
        $this->confirmingDiscard = true;
    }

    public function cancelDiscard(): void
    {
        $this->confirmingDiscard = false;
    }

    public function discard(): void
    {
        app(AssessmentAnalysisService::class)->discard($this->analysis);
        $this->analysis->refresh();
        $this->confirmingDiscard = false;

        $this->dispatch('sc-toast', message: 'Draft discarded — logged with provenance', type: 'warn');
    }

    /**
     * Regenerate: a NEW analysis for the same summary. The generation on screen
     * is left INTACT and becomes `superseded` in the queue, then we move to the
     * new one — so a regeneration is auditable rather than destructive.
     */
    public function regenerate(): void
    {
        $fresh = app(AssessmentAnalysisService::class)->regenerate($this->analysis);

        $this->dispatch('sc-toast', message: 'Regenerated — a new draft is open; the previous generation is retained', type: 'info');

        $this->redirectRoute('ai-analysis.show', ['analysis' => $fresh->id], navigate: true);
    }

    /** Retry is only meaningful for a FAILED generation (the service enforces it). */
    public function retry(): void
    {
        $failed = app(AssessmentAnalysisService::class)->retry($this->analysis)->status === AssessmentAnalysis::STATUS_FAILED;

        $this->analysis->refresh();

        $this->dispatch(
            'sc-toast',
            message: $failed ? $this->analysis->failureSummary() : 'Retry complete',
            type: $failed ? 'error' : 'info'
        );
    }

    public function render()
    {
        return view('livewire.ai-analysis-review', [
            // Every generation for this summary, oldest first — the lineage block.
            'siblings' => $this->analysis->siblings(),
            // The analysis's real source is the SUMMARY, and a summary is routinely
            // fed by several uploaders (13 respondent rows / 2 uploaders in the live
            // data), so "submitted by" would be wrong — a contributor COUNT is honest.
            'contributors' => $this->contributorCount(),
        ]);
    }

    private function contributorCount(): int
    {
        $summary = $this->analysis->assessmentSummary;

        if ($summary === null) {
            return 0;
        }

        return (int) NeedsAssessment::query()
            ->where('community_id', $summary->community_id)
            ->where('quarter', $summary->quarter)
            ->where('year', $summary->year)
            ->distinct()
            ->count('uploaded_by');
    }
}
