<?php

namespace App\Livewire;

use App\Models\ExtensionProject;
use App\Services\ProgramNarrativeService;
use App\Services\TrainingHoursService;
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
        $program = ExtensionProject::findOrFail($programId);
        app(ProgramNarrativeService::class)->generateFor($program);

        $this->dispatch('sc-toast', message: 'Narrative generated — Director-only, aggregates only', type: 'success');
    }

    public function toggleDetail(int $id): void
    {
        $this->detailId = $this->detailId === $id ? null : $id;
    }

    public function render()
    {
        $hours = app(TrainingHoursService::class);

        // R5 / D-R7: the "objectives achieved" column was an 8.6 surface. It is
        // replaced by the training-hours attainment against the project's annual
        // target — the same figure the hub and the dashboard show.
        $programs = ExtensionProject::with([
            'programNarratives' => fn ($q) => $q->latest(),
            'programLead.user',
            'communities',
            'college',
        ])
            ->get()
            ->map(function ($p) use ($hours) {
                $rollup = $hours->forProject($p);

                return (object) [
                    'model' => $p,
                    'latest' => $p->programNarratives->first(),
                    'training_hours' => $rollup['actual_hours'],
                    'target_hours' => $rollup['target_hours'],
                    'hours_pct' => $rollup['hours_pct'],
                    'trainees' => $rollup['trainees'],
                    'activities' => $rollup['activity_count'],
                ];
            });

        return view('livewire.program-narratives', [
            'programs' => $programs,
        ]);
    }
}
