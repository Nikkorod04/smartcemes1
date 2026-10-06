<?php

namespace App\Livewire\Programs;

use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Services\RankingService;
use App\Services\TrainingHoursService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    // R5 §5 step 2 filters. `#[Url]` so a filtered list is shareable and
    // survives a refresh — the Director can send a colleague a link.
    #[Url]
    public string $college = '';

    #[Url]
    public string $program = '';

    #[Url]
    public string $year = '';

    #[Url]
    public string $sort = '';

    public string $view = 'grid';

    /*
     | CREATE MOVED to the hub — this page is now a READ-ONLY list (§25).
     |
     | The `?new=1` deep link that used to live here opened the create form on
     | arrival, but the modal's Alpine `$wire.$watch` visibility bridge only fires
     | on CHANGE — and the flag was already true at mount, so the watcher never
     | ran and the page rendered with NO form on it. That is the failure §14
     | documents twice before.
     |
     | Creation now happens in `Colleges\Index::openProjectCreate()`, where the
     | college and program are pre-filled from the view the Director is on. The
     | modal there renders server-side (`@if`), so the bug cannot recur.
     */

    public function render()
    {
        $rankings = app(RankingService::class);

        $programs = ExtensionProject::query()
            ->with(['college', 'programLead.user', 'communities'])
            ->withCount('activities')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            // R5 §5 step 2: college / broad-program / academic-year filters.
            ->when($this->college !== '', fn ($q) => $q->where('college_id', $this->college))
            ->when($this->program !== '', fn ($q) => $q->where('program_id', $this->program))
            ->when($this->year !== '', fn ($q) => $q->whereYear('planned_start_date', (int) $this->year))
            ->orderBy('planned_start_date')
            ->get();

        // R4: every figure comes from TrainingHoursService, so this list agrees
        // with the project hub and the targets page. `KpiService` is no longer
        // read here (R-Q2 soft-deprecation).
        $hours = app(TrainingHoursService::class);

        $rows = $programs->map(function ($p) use ($hours) {
            $rollup = $hours->forProject($p);

            return (object) [
                'model' => $p,
                'utilized' => $p->utilizedBudget(),
                'allocated_budget' => $p->budgetAllocated(),
                'budget_pct' => $rollup['budget_pct'],
                'over' => $p->isOverAllocated(),
                'lead_name' => $p->programLead?->user?->name,
                'reached' => $rollup['trainees'],
                'training_hours' => $rollup['actual_hours'],
                'hours_target' => $rollup['target_hours'],
                'hours_pct' => $rollup['hours_pct'],
            ];
        });

        // Sort by the ranking rule when the toolbar asks for it, so "most active"
        // here means exactly what it means on the dashboard.
        if ($this->sort === 'hours') {
            $rows = $rows->sortByDesc('training_hours')->values();
        }

        return view('livewire.programs.index', [
            'rows' => $rows,
            'statuses' => config('smartcemes.statuses.program'),
            'faculties' => Faculty::with('user')->orderBy('id')->get(),
            'communities' => Community::orderBy('name')->get(),
            'categories' => config('smartcemes.beneficiary_categories'),
            'colleges' => College::ordered()->get(),
            'programs' => Program::active()->orderBy('title')->get(),
            // R5 filter options, read from real rows rather than hardcoded.
            'years' => $rankings->filterOptions()['years'],
            'hasFilters' => $this->college !== '' || $this->program !== '' || $this->year !== '' || $this->status !== '' || $this->search !== '',
        ]);
    }

    /** R5 §5 step 2 — clear every filter at once. */
    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'college', 'program', 'year', 'sort');
        $this->resetPage();
    }
}
