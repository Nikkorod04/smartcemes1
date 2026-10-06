<?php

namespace App\Livewire\Programs;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Program;
use App\Services\TrainingHoursService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Broad Program management (revision §4.2 / Phase R1; fidelity pass in R7).
 *
 * The six CESO thrusts (§3) that sit between college and project:
 *   College → Program → Project → Activity
 *
 * NO PROGRAM-LEVEL TARGET
 * -----------------------
 * Targets exist at UNIVERSITY and PROJECT level only (§2.2B / D-R5). The
 * prototype's `programs.html` still renders per-program training-hours targets
 * and attainment, which CONTRADICTS D-R5 — Laravel is correct and the prototype
 * is stale (recorded in `revisions.md` §19.2, category A). What D-R5 does NOT
 * forbid is a DERIVED roll-up of the child projects (their count, the hours they
 * delivered, the reach they produced, the budget they consumed), and dropping
 * those too was an over-correction. This page therefore restores the roll-ups
 * and the toolbar, and keeps the targets out.
 *
 * Admin-only, enforced by BroadProgramPolicy.
 */
#[Layout('layouts.app')]
class BroadPrograms extends Component
{
    /* R5-style `#[Url]` filters — a filtered list is shareable and survives a
       refresh, the same contract the project list already honours. */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'All')]
    public string $pillar = 'All';

    #[Url(except: 'All')]
    public string $college = 'All';

    #[Url(except: 'hours')]
    public string $sort = 'hours';

    public string $view = 'grid';

    public function mount(): void
    {
        Gate::authorize('manage', Program::class);

        if (! in_array($this->pillar, ['All', 'Social', 'Economic', 'Environmental'], true)) {
            $this->pillar = 'All';
        }

        if (! in_array($this->sort, ['hours', 'projects', 'budget', 'title'], true)) {
            $this->sort = 'hours';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Toolbar */
    /* ------------------------------------------------------------------ */

    public function setPillar(string $pillar): void
    {
        $this->pillar = $pillar;
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'list' ? 'list' : 'grid';
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'pillar', 'college', 'sort');
        $this->pillar = 'All';
        $this->college = 'All';
        $this->sort = 'hours';
    }

    /* ------------------------------------------------------------------ */
    /* CRUD MOVED to the hub — this page is now a READ-ONLY list (§25) */
    /* ------------------------------------------------------------------ */

    /*
     | create() / edit() / save() / closeForm() lived here until 2026-09-28.
     | §23 removed every link to this page, which left those methods with no way
     | to be reached — and rather than re-link a page the owner had asked to take
     | out of the flow, the form MOVED to `Colleges\Index` (`openProgramCreate`,
     | `openProgramEdit`, `saveProgram`). Keeping a second copy here would have
     | meant two implementations of the same validation rules.
     |
     | This page keeps its roll-ups, filters and sort; it no longer writes.
     */

    /* ------------------------------------------------------------------ */
    /* Render */
    /* ------------------------------------------------------------------ */

    public function render()
    {
        $all = $this->rollups();
        $rows = $this->applyFilters($all);

        return view('livewire.programs.broad', [
            'rows' => $rows,
            'totalPrograms' => $all->count(),
            'totalProjects' => (int) $all->sum('project_count'),
            'totalHours' => (float) $all->sum('training_hours'),
            'totalUtilized' => (float) $all->sum('utilized'),
            'totalTrainees' => (int) $all->sum('trainees'),
            'pillars' => config('smartcemes.pillars'),
            'colleges' => College::ordered()->get(),
            'statuses' => ['active', 'inactive'],
        ]);
    }

    /**
     * One row per broad program, every figure a DERIVED roll-up of its projects.
     *
     * Training hours come from `TrainingHoursService::forProject()` — the single
     * implementation of `trainors × trainees × days` — so a program's total can
     * never disagree with its projects' totals or with the project hub.
     *
     * Deliberately absent: any target or attainment percentage. A program has no
     * target (§2.2B / D-R5).
     *
     * @return Collection<int, object>
     */
    private function rollups(): Collection
    {
        $hours = app(TrainingHoursService::class);

        return Program::query()
            ->with(['projects.college'])
            ->orderBy('code')
            ->get()
            ->map(function (Program $program) use ($hours) {
                $rollups = $program->projects->map(fn (ExtensionProject $p) => $hours->forProject($p));

                return (object) [
                    'model' => $program,
                    'id' => $program->id,
                    'code' => $program->code,
                    'title' => $program->title,
                    'blurb' => $program->description,
                    'thrust' => $program->ceso_thrust,
                    'pillar' => $program->pillar,
                    'status' => $program->status,

                    // Derived roll-ups — allowed under D-R5.
                    'project_count' => $program->projects->count(),
                    'activity_count' => (int) $rollups->sum('activity_count'),
                    'training_hours' => (float) $rollups->sum('actual_hours'),
                    'trainees' => (int) $rollups->sum('trainees'),
                    'utilized' => (float) $rollups->sum('utilized_budget'),

                    // A program spans colleges, so "which college" is DERIVED from
                    // its projects. The prototype's single `lead college` field is
                    // not portable — `programs` has no college_id by design (§3).
                    'colleges' => $program->projects
                        ->map(fn (ExtensionProject $p) => $p->college?->code)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function applyFilters(Collection $rows): Collection
    {
        if ($this->pillar !== '' && $this->pillar !== 'All') {
            $pillar = strtolower($this->pillar);
            $rows = $rows->filter(fn ($r) => $r->pillar === $pillar);
        }

        if ($this->college !== '' && $this->college !== 'All') {
            $code = strtoupper($this->college);
            $rows = $rows->filter(fn ($r) => in_array($code, $r->colleges, true));
        }

        if (trim($this->search) !== '') {
            $needle = mb_strtolower(trim($this->search));

            $rows = $rows->filter(fn ($r) => str_contains(
                mb_strtolower(implode(' ', array_filter([
                    $r->code,
                    $r->title,
                    $r->blurb,
                    $r->thrust,
                    implode(' ', $r->colleges),
                ]))),
                $needle
            ));
        }

        $rows = match ($this->sort) {
            'projects' => $rows->sortByDesc('project_count'),
            'budget' => $rows->sortByDesc('utilized'),
            'title' => $rows->sortBy('title'),
            default => $rows->sortByDesc('training_hours'),
        };

        return $rows->values();
    }
}
