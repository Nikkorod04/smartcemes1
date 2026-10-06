<?php

namespace App\Livewire\Colleges;

use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Services\ProjectArchiveService;
use App\Services\RankingService;
use App\Services\TrainingHoursService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The extension hub (revision §4.1 / Phase R1; hub shape from P0d/P0e, §11.6/§11.7).
 *
 * Colleges sit at the top of the revised hierarchy:
 *   College → Program → Project → Activity
 *
 * This is the ONE admin entry point for the whole structure. The sidebar no
 * longer carries separate Colleges / Extension Programs / Extension Projects
 * items (prototype PATTERNS v4.3, `_check.cjs:196-210`); the admin drills down
 * from here instead.
 *
 * THREE VIEWS, ONE COMPONENT
 * --------------------------
 * View 1 shows ONLY the college cards — no roll-up strip, no program or
 * project tables (owner decision, §11.7). Clicking a card swaps in view 2: that
 * college's hero, its derived KPIs, the BROAD PROGRAMS it delivers, and its
 * faculty. Clicking a program swaps in view 3: that program's projects within
 * the college, which link on to the project hub (and its activities).
 *
 * The Programs level was ADDED 2026-09-27 (owner request, §23) so the drill-down
 * reads College → Program → Projects → Activities with no cross-college detour.
 * Before it, view 2 listed the college's projects directly.
 *
 * PROGRAMS ARE DERIVED, NEVER ASSIGNED
 * ------------------------------------
 * `programs` has NO `college_id` by design (§3: a CESO thrust spans colleges),
 * so "the programs under CAS" is the set of distinct `program_id` values among
 * CAS's projects. There is nothing to query or assign — grouping is the only
 * correct implementation, and a college that delivers one thrust shows one card.
 *
 * `#[Url]` on BOTH `college` and `program` gives the deep link
 * (`?college=CME&program=5`) and browser history for free — the prototype
 * hand-rolls pushState/popstate for the same behaviour.
 *
 * NO PER-COLLEGE TRAINING-HOURS TARGET
 * ------------------------------------
 * Targets exist at UNIVERSITY and PROJECT level only (§2.2B / D-R5). A college
 * has none, so every figure here is a DERIVED roll-up of the college's projects
 * (count, hours delivered, reach, budget utilized) and no attainment percentage
 * is ever rendered — the prototype asserts the same rule (`_check.cjs:254-267`).
 *
 * READ-ONLY SINCE 2026-09-25 (owner request)
 * ------------------------------------------
 * The college set is FIXED — CAS, COE, CME and the Graduate School — so the
 * create / edit / delete surface was REMOVED. `CollegeSeeder` is the set's only
 * owner: a name, description or coordinator is corrected there, not here.
 *
 * `manage` still guards this page — it is the Director's entry point to the
 * whole hierarchy — but it is no longer a write ability. See CollegePolicy.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    /**
     * The hub's second view. An empty string means view 1 (the cards).
     * `?college=CAS` deep-links straight into that college.
     */
    #[Url(as: 'college', except: '')]
    public string $college = '';

    /**
     * View 3's selection: a broad program ID, scoped to the selected college.
     * Empty means view 2 (that college's programs).
     *
     * The ID rather than the code, to match the existing drill-down convention
     * (`broad.blade.php` links to `/projects?program={id}`).
     */
    #[Url(as: 'program', except: '')]
    public string $program = '';

    /** View-3 project toolbar. Client-side — the program's projects are already loaded. */
    public string $projectSearch = '';

    public string $projectStatus = 'All';

    public function mount(): void
    {
        Gate::authorize('manage', College::class);

        $this->college = strtoupper(trim($this->college));

        // An unknown ?college= falls back to view 1 rather than erroring — a
        // stale bookmark should degrade, not 404.
        if ($this->college !== '' && ! College::where('code', $this->college)->exists()) {
            $this->college = '';
        }

        // A ?program= only means anything INSIDE a college, and only when that
        // college actually delivers it. Without this a hand-edited or stale link
        // would render an empty view 3 with no back link to explain itself.
        if ($this->program !== '' && ! $this->programBelongsToCollege()) {
            $this->program = '';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Hub navigation */
    /* ------------------------------------------------------------------ */

    public function selectCollege(string $code): void
    {
        $this->college = strtoupper($code);
        $this->program = '';
        $this->resetProjectFilters();
    }

    public function clearCollege(): void
    {
        $this->college = '';
        $this->program = '';
        $this->resetProjectFilters();
    }

    /** Drill into one broad program of the selected college (view 3). */
    public function selectProgram(int|string $id): void
    {
        $this->program = (string) $id;
        $this->resetProjectFilters();
    }

    /** Back up to the college's program list (view 2). */
    public function clearProgram(): void
    {
        $this->program = '';
        $this->resetProjectFilters();
    }

    /**
     * Whether `$this->program` is one of the SELECTED COLLEGE's derived programs.
     *
     * Asked of the projects rather than the program, because a program is not
     * owned by a college — the same thrust can be delivered by several.
     */
    private function programBelongsToCollege(): bool
    {
        if ($this->college === '' || $this->program === '') {
            return false;
        }

        /*
         * `withTrashed()` on purpose, matching `programRows()`.
         *
         * Without it, a bookmark or a refresh on a thrust whose only project has
         * been archived would silently bounce back to view 2 — the same
         * reachability trap the list has, one layer down. A thrust that still
         * holds archived projects is legitimately part of the college's
         * structure; it is the LIVE list that hides them.
         */
        return ExtensionProject::query()
            ->withTrashed()
            ->where('program_id', $this->program)
            ->whereHas('college', fn ($q) => $q->where('code', $this->college))
            ->exists();
    }

    /* ------------------------------------------------------------------ */
    /* Create / edit forms — MOVED here from /programs and /projects (§25) */
    /* ------------------------------------------------------------------ */

    /*
     | WHY THE FORMS LIVE IN THE HUB
     | -----------------------------
     | §23 took /programs and /projects out of the browsing flow, which left
     | their create forms with NO inbound link — the pages still worked, but
     | nothing pointed at them, so "add a program" became impossible and "new
     | project" had to navigate to a page the owner had asked to remove.
     |
     | Rather than re-link those pages, the two forms MOVED here: each action now
     | happens where the Director is already standing, and there is still exactly
     | ONE implementation of each — the old components no longer carry a second.
     |
     | BOTH MODALS RENDER SERVER-SIDE (`@if`), NOT VIA AN ALPINE `$wire.$watch`
     | VISIBILITY BRIDGE. The watcher only fires on CHANGE, so a modal whose flag
     | is already true when the component mounts never opens. That is precisely
     | how the old `?new=1` deep link produced a page with no form on it; §14
     | records the same failure class twice before.
     */

    /** Broad-program (CESO thrust) create/edit — §3 keeps the six editable. */
    public bool $showProgramForm = false;

    public ?int $editingProgramId = null;

    public array $programForm = [
        'title' => '',
        'pillar' => 'social',
        'ceso_thrust' => '',
        'description' => '',
        'goals' => '',
        'annual_target_hours' => '',
        'annual_target_budget' => '',
        'status' => 'active',
    ];

    /** Project create. The college and program arrive pre-filled from the view. */
    public bool $showProjectForm = false;

    public array $projectForm = [
        'college_id' => '',
        'program_id' => '',
        'title' => '',
        'description' => '',
        'planned_start_date' => '',
        'planned_end_date' => '',
        'target_beneficiaries' => '',
        'allocated_budget' => '',
        'annual_target_hours' => '',
        'program_lead_id' => '',
        'community_ids' => [],
        'beneficiary_categories' => [],
        'status' => 'draft',
    ];

    public function openProgramCreate(): void
    {
        Gate::authorize('manage', Program::class);

        $this->resetProgramForm();
        $this->showProgramForm = true;
    }

    public function openProgramEdit(int $id): void
    {
        Gate::authorize('manage', Program::class);

        $program = Program::findOrFail($id);

        $this->editingProgramId = $program->id;
        $this->programForm = [
            'title' => $program->title,
            'pillar' => $program->pillar,
            'ceso_thrust' => $program->ceso_thrust,
            'description' => $program->description ?? '',
            'goals' => $program->goals ?? '',
            'annual_target_hours' => $program->annual_target_hours ?? '',
            'annual_target_budget' => $program->annual_target_budget ?? '',
            'status' => $program->status,
        ];
        $this->showProgramForm = true;
    }

    public function closeProgramForm(): void
    {
        $this->showProgramForm = false;
        $this->resetProgramForm();
    }

    public function saveProgram(): void
    {
        Gate::authorize('manage', Program::class);

        $this->validate([
            'programForm.title' => 'required|string|max:255',
            'programForm.pillar' => 'required|in:social,economic,environmental',
            'programForm.ceso_thrust' => 'required|string|max:255',
            'programForm.description' => 'nullable|string|max:4000',
            'programForm.goals' => 'nullable|string|max:4000',
            'programForm.annual_target_hours' => 'nullable|numeric|min:0',
            'programForm.annual_target_budget' => 'nullable|numeric|min:0',
            'programForm.status' => 'required|in:active,inactive',
        ]);

        $payload = [
            'title' => $this->programForm['title'],
            'pillar' => $this->programForm['pillar'],
            'ceso_thrust' => $this->programForm['ceso_thrust'],
            'description' => $this->programForm['description'] ?: null,
            'goals' => $this->programForm['goals'] ?: null,
            'annual_target_hours' => $this->programForm['annual_target_hours'] === '' ? null : $this->programForm['annual_target_hours'],
            'annual_target_budget' => $this->programForm['annual_target_budget'] === '' ? null : $this->programForm['annual_target_budget'],
            'status' => $this->programForm['status'],
            'updated_by' => auth()->id(),
        ];

        if ($this->editingProgramId) {
            $program = Program::findOrFail($this->editingProgramId);
            $program->update($payload);
            $this->dispatch('sc-toast', message: "Program {$program->code} updated", type: 'success');
        } else {
            // The code is generated, never typed: R-Q4.
            $payload['code'] = Program::nextCode();
            $payload['created_by'] = auth()->id();
            $program = Program::create($payload);
            $this->dispatch('sc-toast', message: "Program created — code {$program->code}", type: 'success');
        }

        $this->closeProgramForm();
    }

    private function resetProgramForm(): void
    {
        $this->editingProgramId = null;
        $this->programForm = [
            'title' => '',
            'pillar' => 'social',
            'ceso_thrust' => '',
            'description' => '',
            'goals' => '',
            'annual_target_hours' => '',
            'annual_target_budget' => '',
            'status' => 'active',
        ];
        $this->resetErrorBag();
    }

    public function openProjectCreate(): void
    {
        Gate::authorize('manage', College::class);

        $this->resetProjectForm();

        // Pre-filled from what the Director already chose, so the form never asks
        // for the college and program they just drilled through.
        $this->projectForm['college_id'] = (string) ($this->selectedCollegeId() ?? '');
        $this->projectForm['program_id'] = $this->program;

        $this->showProjectForm = true;
    }

    public function closeProjectForm(): void
    {
        $this->showProjectForm = false;
        $this->resetProjectForm();
    }

    /**
     * Toggle one entry in a multi-select of the project form.
     *
     * The `(key, value)` signature is the `x-sc.multi-select` contract — its JS
     * calls `$wire.call(method, key, id)`. Never `$toggle`: Livewire 3.8's client
     * implementation ignores the value argument for arrays and coerces the
     * property to a bool (§14).
     */
    public function toggleProjectFormArray(string $key, string|int $value): void
    {
        if (! in_array($key, ['community_ids', 'beneficiary_categories'], true)) {
            return;
        }

        $values = is_array($this->projectForm[$key] ?? null) ? $this->projectForm[$key] : [];

        $this->projectForm[$key] = in_array($value, $values, true)
            ? array_values(array_diff($values, [$value]))
            : [...$values, $value];
    }

    public function saveProject(): void
    {
        Gate::authorize('manage', College::class);

        $this->validate([
            'projectForm.college_id' => 'required|exists:colleges,id',
            'projectForm.program_id' => 'required|exists:programs,id',
            'projectForm.title' => 'required|string|max:255',
            'projectForm.description' => 'nullable|string|max:4000',
            'projectForm.planned_start_date' => 'required|date',
            'projectForm.planned_end_date' => 'required|date|after_or_equal:projectForm.planned_start_date',
            'projectForm.target_beneficiaries' => 'nullable|integer|min:1',
            'projectForm.allocated_budget' => 'nullable|numeric|min:0',
            'projectForm.annual_target_hours' => 'nullable|numeric|min:0',
            'projectForm.program_lead_id' => 'nullable|exists:faculties,id',
            'projectForm.community_ids' => 'array',
            'projectForm.community_ids.*' => 'exists:communities,id',
            'projectForm.beneficiary_categories' => 'array',
            'projectForm.beneficiary_categories.*' => 'string',
            'projectForm.status' => 'required|in:draft,ongoing,completed,cancelled',
        ]);

        /* R-Q4: the code prefix comes from the selected college, so the code is
           generated AFTER the college is known. */
        $college = College::findOrFail($this->projectForm['college_id']);

        $project = ExtensionProject::create([
            'code' => ExtensionProject::nextCode($college->code),
            'college_id' => $college->id,
            'program_id' => $this->projectForm['program_id'] ?: null,
            'title' => $this->projectForm['title'],
            'description' => $this->projectForm['description'] ?: null,
            'planned_start_date' => $this->projectForm['planned_start_date'],
            'planned_end_date' => $this->projectForm['planned_end_date'],
            'target_beneficiaries' => $this->projectForm['target_beneficiaries'] ?: null,
            'beneficiary_categories' => $this->projectForm['beneficiary_categories'],
            'allocated_budget' => $this->projectForm['allocated_budget'] ?: 0,
            'annual_target_hours' => $this->projectForm['annual_target_hours'] ?: null,
            'program_lead_id' => $this->projectForm['program_lead_id'] ?: null,
            'status' => $this->projectForm['status'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($this->projectForm['community_ids']) {
            $project->communities()->sync($this->projectForm['community_ids']);
        }

        $this->closeProjectForm();
        $this->dispatch('sc-toast', message: 'Project created — code '.$project->code, type: 'success');
    }

    private function resetProjectForm(): void
    {
        $this->projectForm = [
            'college_id' => '',
            'program_id' => '',
            'title' => '',
            'description' => '',
            'planned_start_date' => '',
            'planned_end_date' => '',
            'target_beneficiaries' => '',
            'allocated_budget' => '',
            'annual_target_hours' => '',
            'program_lead_id' => '',
            'community_ids' => [],
            'beneficiary_categories' => [],
            'status' => 'draft',
        ];
        $this->resetErrorBag();
        $this->dispatch('ms-sync-community_ids', ids: []);
        $this->dispatch('ms-sync-beneficiary_categories', ids: []);
    }

    /* ------------------------------------------------------------------ */
    /* Project archive (owner request 2026-10-05) */
    /* ------------------------------------------------------------------ */

    /**
     * Archive one project (and everything that renders through it).
     *
     * The CASCADE lives in `ProjectArchiveService`, not here — a seeder or a
     * console command cannot reach a component method, and copying it would be
     * the two-implementations-of-one-ruleset problem §25 had to undo for the
     * create forms. The service also owns the mirror `restore()`.
     *
     * The confirmation is Livewire's own `wire:confirm` on the button — the same
     * convention `Communities\Index::confirmDelete()` uses — rather than a
     * hand-rolled modal. That is deliberate: a custom modal would need a
     * visibility flag, and §14/§25 record three separate bugs where a watcher
     * bridge failed to open one. `wire:confirm` has no visibility state to get
     * wrong.
     */
    public function archiveProject(int $id): void
    {
        $project = ExtensionProject::findOrFail($id);

        Gate::authorize('delete', $project);

        $activityCount = app(ProjectArchiveService::class)->archive($project);

        // The archived row is no longer in the live list, so a Director sitting
        // on the "Archived" chip should see it appear; on any other chip the
        // card simply leaves.
        $this->dispatch(
            'sc-toast',
            message: 'Project '.$project->code.' archived'
                .($activityCount > 0
                    ? ' with '.$activityCount.' '.($activityCount === 1 ? 'activity' : 'activities')
                    : '')
                .' — find it under the Archived filter to restore.',
            type: 'warn'
        );
    }

    /**
     * Restore a project the Director archived.
     *
     * Without this the archive is a one-way door: `Communities\Index` has had a
     * `restore()` since v4.5, and a project is a far larger thing to lose to a
     * mis-click. The restore mirrors the archive exactly (see the service).
     */
    public function restoreProject(int $id): void
    {
        $project = ExtensionProject::onlyTrashed()->findOrFail($id);

        Gate::authorize('restore', $project);

        $activityCount = app(ProjectArchiveService::class)->restore($project);

        // The row is live again, so the Archived list no longer holds it —
        // return the Director to the live list where it now appears.
        $this->projectStatus = 'All';

        $this->dispatch(
            'sc-toast',
            message: 'Project '.$project->code.' restored'
                .($activityCount > 0
                    ? ' with '.$activityCount.' '.($activityCount === 1 ? 'activity' : 'activities')
                    : ''),
            type: 'success'
        );
    }

    /** The selected college's id, or NULL when view 1 is showing. */
    private function selectedCollegeId(): ?int
    {
        return $this->college === '' ? null : College::where('code', $this->college)->value('id');
    }

    /* ------------------------------------------------------------------ */
    /* Render */
    /* ------------------------------------------------------------------ */

    public function render()
    {
        $cards = $this->collegeCards();

        $selected = null;
        $programRows = collect();
        $selectedProgram = null;
        $projectRows = collect();
        $statusCounts = [];
        $facultyRows = collect();

        if ($this->college !== '') {
            $selected = $cards->first(fn (array $c) => $c['code'] === $this->college);

            if ($selected) {
                $programRows = $this->programRows($selected['model']);

                // View 3. `$programRows` is the authority on which programs this
                // college has, so the selection is resolved FROM it rather than
                // re-queried — `mount()` has already blanked anything stale.
                $selectedProgram = $this->program === ''
                    ? null
                    : $programRows->firstWhere('id', (int) $this->program);

                if ($selectedProgram) {
                    $all = $this->projectRows($selected['model'], (int) $this->program);
                    $archived = $this->projectRows($selected['model'], (int) $this->program, archived: true);

                    $statusCounts = [
                        'All' => $all->count(),
                        'Ongoing' => $all->where('status', 'ongoing')->count(),
                        'Completed' => $all->where('status', 'completed')->count(),
                        'Draft' => $all->where('status', 'draft')->count(),
                        'Archived' => $archived->count(),
                    ];

                    // "Archived" swaps the SOURCE rather than filtering `$all`:
                    // trashed rows are not in `$all` at all, so a status filter
                    // over it could never find them.
                    $projectRows = $this->projectStatus === 'Archived'
                        ? $this->filteredProjectRows($archived, filterStatus: false)
                        : $this->filteredProjectRows($all);
                }

                // Faculty belongs to the COLLEGE, not to a program, so it is
                // shown in both view 2 and view 3 rather than only the first.
                //
                // `RankingService::faculty()` wraps FacultyContributionService and
                // adds the derived initials/engagement keys the mini-cards print,
                // so the hub and the engagement board cannot disagree.
                $facultyRows = app(RankingService::class)
                    ->faculty(null, $selected['model']->id)
                    ->values();
            }
        }

        // The form option lists are loaded ONLY while a modal is open — the
        // community registry alone is 58 rows, and the hub renders far more
        // often than either form opens.
        $formOpen = $this->showProgramForm || $this->showProjectForm;

        return view('livewire.colleges.index', [
            'cards' => $cards,
            'selected' => $selected,
            'programRows' => $programRows,
            'selectedProgram' => $selectedProgram,
            'projectRows' => $projectRows,
            'statusCounts' => $statusCounts,
            'facultyRows' => $facultyRows,
            'formColleges' => $formOpen ? College::ordered()->get() : collect(),
            'formPrograms' => $formOpen ? Program::active()->orderBy('title')->get() : collect(),
            'formFaculties' => $formOpen ? Faculty::with('user')->orderBy('id')->get() : collect(),
            'formCommunities' => $formOpen ? Community::orderBy('name')->get() : collect(),
            'formCategories' => config('smartcemes.beneficiary_categories'),
            'formPillars' => config('smartcemes.pillars'),
            'formProgramStatuses' => ['active', 'inactive'],
            'formProjectStatuses' => config('smartcemes.statuses.program'),
        ]);
    }

    /**
     * The broad programs this college delivers, DERIVED from its projects.
     *
     * `programs` has no `college_id` (§3), so there is nothing to query: the set
     * IS the distinct `program_id` values among the college's projects, and each
     * figure is rolled up from the same `TrainingHoursService` the project hub
     * and the targets page read — so this level can never disagree with them.
     *
     * A college that delivers a single thrust shows a single card. That is the
     * data being honest, not a rendering bug.
     *
     * @return Collection<int, object>
     */
    private function programRows(College $college): Collection
    {
        $hours = app(TrainingHoursService::class);

        /*
         * Group over LIVE **and ARCHIVED** projects, deliberately.
         *
         * `$college->projects` (the eager-loaded relation) excludes trashed rows,
         * so a thrust whose only project has been archived would drop off this
         * list — and with it the ONLY route to that project, leaving it
         * unreachable and un-restorable. That is not hypothetical: archiving a
         * college's single project for a thrust is exactly what the archive is
         * for.
         *
         * Only the LIVE members contribute to the figures, so a thrust that has
         * been fully archived reads 0 projects / 0 hours (honest) while still
         * being clickable.
         */
        $projects = $college->projects()->withTrashed()->with('program')->get();

        return $projects
            ->groupBy(fn (ExtensionProject $p) => (string) ($p->program_id ?? 0))
            ->map(function (Collection $group) use ($hours) {
                $live = $group->reject(fn (ExtensionProject $p) => $p->trashed());
                $program = $group->first()->program;

                $rollups = $live->map(fn (ExtensionProject $p) => $hours->forProject($p));

                return (object) [
                    'id' => $program?->id,
                    'code' => $program?->code,
                    'title' => $program?->title ?? 'Unfiled projects',
                    'pillar' => $program?->pillar,
                    'projects' => $live->count(),
                    'archived' => $group->count() - $live->count(),
                    'training_hours' => round((float) $rollups->sum(fn ($r) => $r['actual_hours']), 1),
                    'trainors' => (int) $rollups->sum(fn ($r) => $r['trainors']),
                    'trainees' => (int) $rollups->sum(fn ($r) => $r['trainees']),
                    'activities' => (int) $rollups->sum(fn ($r) => $r['activity_count']),
                    'utilized' => round((float) $rollups->sum(fn ($r) => $r['utilized_budget']), 2),
                    'allocated' => round((float) $rollups->sum(fn ($r) => $r['allocated_budget']), 2),
                ];
            })
            ->sortByDesc('projects')
            ->values();
    }

    /**
     * The college cards, each carrying only DERIVED figures. `RankingService::colleges()`
     * already computes the tested roll-ups (projects, hours, reach, budget utilized,
     * faculty) and deliberately leaves `hours_pct` NULL — there is no college target.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collegeCards(): Collection
    {
        $rollups = app(RankingService::class)->colleges()->keyBy('code');

        return College::query()
            ->with(['extensionCoordinator.user', 'projects.program'])
            ->ordered()
            ->get()
            ->map(function (College $college) use ($rollups) {
                $row = $rollups->get($college->code, []);

                // The broad programs this college actually delivers, DERIVED from
                // its projects — `programs` has no college_id by design (§3).
                $programTitles = $college->projects
                    ->map(fn (ExtensionProject $p) => $p->program?->title)
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'model' => $college,
                    'code' => $college->code,
                    'color' => RankingService::collegeColor($college->code) ?? '#003599',
                    // The official seal (config/smartcemes.php `college_logos`).
                    // NULL when a college has no entry — the views then fall back
                    // to the code crest. All four colleges are sealed as of
                    // 2026-09-27, so that branch is a safety net, not a state any
                    // seeded college is in. `$selected` is derived from this
                    // payload, so the hero picks the same value up for free.
                    'logo' => config('smartcemes.college_logos')[$college->code] ?? null,
                    'projects' => (int) ($row['projects'] ?? 0),
                    'programs' => $programTitles->count(),
                    'faculty' => (int) ($row['faculty'] ?? 0),
                    'trainees' => (int) ($row['trainees'] ?? 0),
                    'trainors' => (int) ($row['trainors'] ?? 0),
                    'activities' => (int) ($row['activities'] ?? 0),
                    'budget_utilized' => (float) ($row['budget_utilized'] ?? 0),
                    'program_titles' => $programTitles->take(3)->all(),
                ];
            });
    }

    /**
     * One row per project of a college, every figure from TrainingHoursService
     * (the single implementation of `trainors × trainees × days`).
     *
     * `$programId` narrows it to view 3's selected program. NULL means every
     * project of the college — which is no longer a rendered state (view 2 shows
     * programs, not projects) but stays supported so the roll-up helpers can
     * reuse this method.
     *
     * The project's own annual target IS shown — project-level targets are
     * legitimate under D-R5; only the college/program levels have none.
     *
     * @return Collection<int, object>
     */
    private function projectRows(College $college, ?int $programId = null, bool $archived = false): Collection
    {
        $hours = app(TrainingHoursService::class);
        $ranking = app(RankingService::class);

        return ExtensionProject::query()
            // Trashed rows are excluded by the soft-delete scope; the archived
            // list is the INVERSE of the live one, not a superset.
            ->when($archived, fn ($q) => $q->onlyTrashed())
            ->where('college_id', $college->id)
            ->when($programId !== null, fn ($q) => $q->where('program_id', $programId))
            ->with(['programLead.user', 'communities'])
            ->orderBy('planned_start_date')
            ->get()
            ->map(function (ExtensionProject $project) use ($hours, $ranking) {
                $rollup = $hours->forProject($project);

                return (object) [
                    'id' => $project->id,
                    'code' => $project->code,
                    'acr' => $ranking->shortTitle($project->title),
                    'title' => $project->title,
                    'status' => $project->status,
                    // Read off the model rather than the argument, so the flag
                    // can never disagree with the query that produced the row.
                    'archived' => $project->trashed(),
                    'community' => $project->communities->first()?->name,
                    'lead' => $project->programLead?->user?->name,
                    // NOTE: for an archived row every figure below is 0/NULL,
                    // because the roll-up walks live activities. The view must
                    // NOT print them — an archived project has not lost its
                    // data, so a 0 would be a false claim, not an honest zero.
                    'training_hours' => $rollup['actual_hours'],
                    'hours_target' => $rollup['target_hours'],
                    'hours_pct' => $rollup['hours_pct'],
                    'trainors' => $rollup['trainors'],
                    'trainees' => $rollup['trainees'],
                    'activities' => $rollup['activity_count'],
                    'utilized' => $rollup['utilized_budget'],
                    'budget_allocated' => $rollup['allocated_budget'],
                    'over' => $project->isOverAllocated(),
                ];
            });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function filteredProjectRows(Collection $rows, bool $filterStatus = true): Collection
    {
        if ($filterStatus && $this->projectStatus !== '' && $this->projectStatus !== 'All') {
            $status = strtolower($this->projectStatus);
            $rows = $rows->filter(fn ($row) => $row->status === $status);
        }

        if (trim($this->projectSearch) !== '') {
            $needle = mb_strtolower(trim($this->projectSearch));

            $rows = $rows->filter(fn ($row) => str_contains(
                mb_strtolower($row->code.' '.$row->acr.' '.$row->title.' '.($row->community ?? '').' '.($row->lead ?? '')),
                $needle
            ));
        }

        return $rows->values();
    }

    private function resetProjectFilters(): void
    {
        $this->projectSearch = '';
        $this->projectStatus = 'All';
    }
}
