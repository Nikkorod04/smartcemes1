<?php

namespace App\Livewire;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\UniversityTarget;
use App\Services\TrainingHoursService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * University Targets (Phase R4b, revision §4.7 / §2.2B / R-Q3).
 *
 * The institutional commitment CESO reports against — ONE annual training-hours
 * pool, drawn down by project actuals, plus the annual budget target. This is
 * the page on which the Director sets it.
 *
 * The prototype's §2.2C "this page does not yet reflect the final target model"
 * banner is DELETED here: the model now exists, so the banner would be a lie.
 *
 * What this page is NOT:
 *  - It does not sum project targets to produce the annual target. The annual
 *    target is set directly; project targets are planning figures for their own
 *    project. Summing them would double-count (§2.2B).
 *  - It does not carry a target for broad programs. Targets exist at University
 *    and Project level only (D-R5).
 *  - It does not store actuals. Every actual is computed live by
 *    TrainingHoursService, so this page and the project hubs cannot disagree.
 */
#[Layout('layouts.app')]
class Targets extends Component
{
    /** AY START year (2026 means AY 2026-2027) — R-Q3. */
    #[Url]
    public ?int $year = null;

    /** Set when the Director has no row for the selected year yet. */
    public bool $showForm = false;

    /**
     * The Project-targets table's sort key.
     *
     * Sorted SERVER-side on purpose. The prototype sorts client-side
     * (`targets.html`), and this blade once carried data-* sort keys for an
     * Alpine comparator that was never written — so the dropdown was inert and
     * the table always rendered in code order while the select claimed
     * "hours attainment up". A client-side comparator would also be lost on
     * every Livewire re-render (saving the annual target re-renders this
     * component), reintroducing exactly that mismatch.
     */
    public string $sort = 'hours';

    public array $form = [
        'annual_target_hours' => '',
        'annual_target_budget' => '',
        'notes' => '',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($this->year === null) {
            $this->year = (int) (UniversityTarget::current()?->year ?? now()->year);
        }

        $this->loadForm();
    }

    /** The AY years the picker offers: any stored year, plus the current one. */
    public function getYearOptionsProperty(): array
    {
        $stored = UniversityTarget::query()->orderByDesc('year')->pluck('year')->all();
        $years = array_unique([...$stored, (int) now()->year, (int) $this->year]);

        return collect($years)->sortDesc()->values()->all();
    }

    /**
     * Display label per year, resolved ONCE in PHP and handed to the view as an
     * array. Blade cannot call a public method bare — `$yearLabel(...)` would be
     * an undefined variable — and resolving it in the view would also put a
     * query inside the render loop.
     *
     * @return array<int, string>
     */
    public function getYearLabelsProperty(): array
    {
        $labels = [];

        foreach ($this->yearOptions as $y) {
            $labels[$y] = $this->labelFor((int) $y);
        }

        return $labels;
    }

    public function getTargetProperty(): ?UniversityTarget
    {
        return UniversityTarget::forYear((int) $this->year);
    }

    /** Selected year's label, derived from the pre-resolved map. */
    public function getSelectedLabelProperty(): string
    {
        return $this->yearLabels[(int) $this->year] ?? $this->labelFor((int) $this->year);
    }

    public function updatedYear(): void
    {
        $this->showForm = false;
        $this->loadForm();
    }

    /** Stored label when the Director set one, else the derived AY convention. */
    private function labelFor(int $year): string
    {
        return UniversityTarget::forYear($year)?->display_label ?? sprintf('AY %d-%d', $year, $year + 1);
    }

    private function loadForm(): void
    {
        $t = $this->target;

        $this->form = [
            'annual_target_hours' => $t?->annual_target_hours !== null ? (string) $t->annual_target_hours : '',
            'annual_target_budget' => $t?->annual_target_budget !== null ? (string) $t->annual_target_budget : '',
            'notes' => (string) ($t?->notes ?? ''),
        ];
    }

    public function openForm(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->loadForm();
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->loadForm();
    }

    /**
     * Upsert the annual target for the selected year.
     *
     * Empty hours is allowed and meaningful: it clears the pool so the page
     * renders "no target set" rather than a 0 that would make every attainment
     * figure divide-by-zero or read as infinite.
     */
    public function save(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->validate([
            'form.annual_target_hours' => 'nullable|numeric|min:0|max:100000',
            'form.annual_target_budget' => 'nullable|numeric|min:0|max:100000000',
            'form.notes' => 'nullable|string|max:1000',
        ], [
            'form.annual_target_hours.max' => 'Annual training hours look implausibly high — check the figure.',
            'form.annual_target_budget.max' => 'Annual budget looks implausibly high — check the figure.',
        ]);

        $hours = $this->form['annual_target_hours'] !== '' ? (float) $this->form['annual_target_hours'] : null;
        $budget = $this->form['annual_target_budget'] !== '' ? (float) $this->form['annual_target_budget'] : null;
        $notes = $this->form['notes'] !== '' ? $this->form['notes'] : null;

        $target = UniversityTarget::forYear((int) $this->year);
        $isNew = $target === null;

        $target ??= new UniversityTarget(['year' => (int) $this->year]);

        $target->fill([
            'annual_target_hours' => $hours,
            'annual_target_budget' => $budget,
            'notes' => $notes,
            'updated_by' => auth()->id(),
        ])->save();

        // 8.1: every target change is attributable — the page itself tells the
        // Director that edits are logged, so they must actually be logged.
        activity()->performedOn($target)->event($isNew ? 'target_create' : 'target_update')
            ->log(($isNew ? 'Annual target set for ' : 'Annual target updated for ').$target->display_label
                .' — '.($hours === null ? 'no hours target' : number_format($hours).' hrs')
                .' · '.($budget === null ? 'no budget target' : '₱'.number_format($budget)));

        $this->showForm = false;
        $this->loadForm();

        $this->dispatch('sc-toast', message: 'Annual target saved for '.$target->display_label, type: 'success');
    }

    public function render()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $hours = app(TrainingHoursService::class);
        $target = $this->target;

        // University actuals roll up from the PROJECT roll-up, so this page can
        // never contradict the project hubs (prototype: `actuals()`).
        $projects = ExtensionProject::with(['college', 'programLead.user'])->orderBy('code')->get();

        $rows = $projects->map(function ($p) use ($hours) {
            $perf = $hours->forProject($p);

            return (object) [
                'model' => $p,
                'hours' => $perf,
                'over' => $p->isOverAllocated(),
            ];
        });

        // Sort keys mirror the prototype's comparator exactly (targets.html):
        // hours ASCENDING (worst attainment first), budget DESCENDING, code A-Z.
        // A project with no hours target counts as 0 — the prototype does the
        // same (`p.hoursPct = target ? ... : 0`), so an untargeted project sorts
        // first under "hours up" rather than landing somewhere unpredictable.
        $rows = match ($this->sort) {
            'budget' => $rows->sortByDesc(fn ($r) => $r->hours['budget_pct'] ?? 0),
            'code' => $rows->sortBy(fn ($r) => (string) $r->model->code),
            default => $rows->sortBy(fn ($r) => $r->hours['hours_pct'] ?? 0),
        };

        $actuals = [
            'training_hours' => (float) $rows->sum(fn ($r) => $r->hours['actual_hours']),
            'budget' => (float) $rows->sum(fn ($r) => $r->hours['utilized_budget']),
            'trainees' => (int) $rows->sum(fn ($r) => $r->hours['trainees']),
            'activities' => (int) $rows->sum(fn ($r) => $r->hours['activity_count']),
            'projects' => $rows->count(),
        ];

        $targetHours = $target?->annual_target_hours !== null ? (float) $target->annual_target_hours : null;
        $targetBudget = $target?->annual_target_budget !== null ? (float) $target->annual_target_budget : null;

        $attainPct = function (?float $actual, ?float $t): ?float {
            if ($t === null || $t <= 0) {
                return null;
            }

            return round($actual / $t * 100, 1);
        };

        return view('livewire.targets', [
            'target' => $target,
            'targetHours' => $targetHours,
            'targetBudget' => $targetBudget,
            'actuals' => $actuals,
            'hoursPct' => $attainPct($actuals['training_hours'], $targetHours),
            'budgetPct' => $attainPct($actuals['budget'], $targetBudget),
            // CONSUMPTION, not a ratio: the pool is drawn down by project
            // actuals. Remaining floors at 0 — an over-drawn pool is reported
            // by the percentage, never by a negative "hours left".
            'drawnDown' => $targetHours !== null ? (float) min($actuals['training_hours'], $targetHours) : null,
            'remaining' => $targetHours !== null ? (float) max($targetHours - $actuals['training_hours'], 0) : null,
            'overDrawn' => $targetHours !== null && $actuals['training_hours'] > $targetHours,
            'rows' => $rows,
            'colleges' => College::orderBy('code')->get(),
            'yearOptions' => $this->yearOptions,
            'yearLabels' => $this->yearLabels,
            'selectedLabel' => $this->selectedLabel,
        ]);
    }
}
