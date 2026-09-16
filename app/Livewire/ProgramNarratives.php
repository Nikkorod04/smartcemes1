<?php

namespace App\Livewire;

use App\Models\ExtensionProgram;
use App\Services\KpiService;
use App\Services\ProgramNarrativeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProgramNarratives extends Component
{
    public ?int $programId = null;

    public ?int $detailId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Admin-only AI access (D4).');
    }

    /** 5.15: manual trigger, one new row per generation (history kept). */
    public function generate(int $programId): void
    {
        $program = ExtensionProgram::findOrFail($programId);
        app(ProgramNarrativeService::class)->generateFor($program);

        $this->dispatch('sc-toast', message: 'Narrative generated — Director-only, aggregates only', type: 'success');
    }

    public function toggleDetail(int $id): void
    {
        $this->detailId = $this->detailId === $id ? null : $id;
    }

    public function render()
    {
        $kpi = app(KpiService::class);

        $programs = ExtensionProgram::with([
            'programNarratives' => fn ($q) => $q->latest(),
            'programLead.user',
            'communities',
            'programObjectives',
        ])
            ->get()
            ->map(fn ($p) => (object) [
                'model' => $p,
                'latest' => $p->programNarratives->first(),
                'objectivesTotal' => $p->programObjectives->count(),
                // 8.6: status derives live from the effective actual — never the stored column.
                'objectivesAchieved' => $p->programObjectives
                    ->filter(fn ($o) => $kpi->statusFor($o) === 'achieved')->count(),
            ]);

        return view('livewire.program-narratives', [
            'programs' => $programs,
        ]);
    }
}
