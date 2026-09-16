<?php

namespace App\Livewire;

use App\Jobs\GenerateAssessmentAnalysis;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\NeedsAssessment;
use App\Services\AssessmentAnalysisService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AiAnalysis extends Component
{
    public ?int $summaryId = null;

    public ?int $detailId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Admin-only AI access (D4).');
    }

    public function selectSummary(int $id): void
    {
        $this->summaryId = $id;
    }

    /** 5.11: generate → AssessmentAnalysis draft, generated immediately. */
    public function generate(): void
    {
        $summary = AssessmentSummary::findOrFail($this->summaryId);

        $batch = NeedsAssessment::where('community_id', $summary->community_id)
            ->where('quarter', $summary->quarter)
            ->where('year', $summary->year)
            ->orderBy('id')
            ->first();

        if (! $batch) {
            $this->dispatch('sc-toast', message: 'No submission batch found for this summary.', type: 'error');

            return;
        }

        $analysis = app(AssessmentAnalysisService::class)->generateFor($summary->fresh(), $batch->id);
        $analysis->refresh();

        if ($analysis->status === AssessmentAnalysis::STATUS_COMPLETED) {
            $this->dispatch('sc-toast', message: 'Analysis generated — aggregated inputs only (D3)', type: 'success');
        } else {
            $this->dispatch('sc-toast', message: $analysis->error_message ?? 'Analysis unavailable — generation failed. Retry is available in the history table.', type: 'error');
        }
    }

    public function approve(int $id): void
    {
        app(AssessmentAnalysisService::class)->approve(AssessmentAnalysis::findOrFail($id));
        $this->dispatch('sc-toast', message: 'Analysis approved — institutional use unlocked', type: 'success');
    }

    public function discard(int $id): void
    {
        app(AssessmentAnalysisService::class)->discard(AssessmentAnalysis::findOrFail($id));
        $this->dispatch('sc-toast', message: 'Draft discarded — logged with provenance', type: 'warn');
    }

    public function retry(int $id): void
    {
        $analysis = AssessmentAnalysis::findOrFail($id);
        abort_unless($analysis->status === AssessmentAnalysis::STATUS_FAILED, 422);

        $analysis->update(['status' => AssessmentAnalysis::STATUS_PENDING, 'error_message' => null]);

        try {
            (new GenerateAssessmentAnalysis($analysis->id))->dispatchSync();
            $this->dispatch('sc-toast', message: 'Retry complete — see the draft state below', type: 'info');
        } catch (\Throwable $e) {
            $analysis->refresh();
            if ($analysis->status !== AssessmentAnalysis::STATUS_FAILED) {
                $analysis->update([
                    'status' => AssessmentAnalysis::STATUS_FAILED,
                    'error_message' => 'Analysis unavailable — '.$e->getMessage(),
                ]);
            }
            $this->dispatch('sc-toast', message: 'Retry failed — analysis unavailable', type: 'error');
        }
    }

    public function viewDetail(int $id): void
    {
        $this->detailId = $this->detailId === $id ? null : $id;
    }

    public function render()
    {
        return view('livewire.ai-analysis', [
            'summaries' => AssessmentSummary::with('community')->orderByDesc('year')->orderBy('quarter')->get(),
            'analyses' => AssessmentAnalysis::with(['assessmentSummary.community', 'approver'])
                ->orderByDesc('created_at')->get(),
        ]);
    }
}
