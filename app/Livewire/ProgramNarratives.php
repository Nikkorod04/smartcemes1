<?php

namespace App\Livewire;

use App\Models\ExtensionProject;
use App\Models\ProgramNarrative;
use App\Services\ProgramNarrativeService;
use App\Services\TrainingHoursService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Project Narratives — output type 2 of the AI pipeline (5.15): Admin-only,
 * NO approval gate, history kept per project.
 *
 * WHAT CHANGED (2026-10-07) AND WHY
 * ---------------------------------
 * The page was a reading surface pretending to be an index. Its own heading
 * promised "status at a glance" while offering no search, no filter and no
 * pagination, and rendering every project's full body. It is now the sibling of
 * the AI-analysis QUEUE (`AiAnalysis`) — same controls, same URL aliases, same
 * resetPage discipline, same shared pager partial.
 *
 * It deliberately does NOT copy the queue's card shape. The queue is a triage
 * surface (compact rows); this is the READING surface, so a card keeps its
 * summary / risks / next-actions body and the new controls wrap around it.
 *
 * TWO DIFFERENCES FROM THE QUEUE THAT MATTER
 * ------------------------------------------
 *  - The unit here is the PROJECT, not a community group, so this paginates
 *    projects directly. The queue's `GROUPS_PER_PAGE` group-slicing has no
 *    analogue here and must not be imitated.
 *  - `TrainingHoursService::forProject()` runs ~4 queries per project
 *    (activities + withCount, the distinct `activity_faculty` join,
 *    `traineesReached()`, budget) and the old `render()` called it for EVERY
 *    project. The pipeline is therefore now: search -> count -> filter -> sort
 *    -> PAGINATE -> enrich only the visible page. The rollup cost drops from
 *    N x 4 to pageSize x 4, which makes pagination a query win and not just a
 *    UI nicety.
 *
 *    The corollary is a constraint, not a preference: filter and sort may read
 *    only CHEAP fields (the narrative state, the title). Sorting on hours
 *    attainment would force a rollup for every project and undo the win.
 */
#[Layout('layouts.app')]
class ProgramNarratives extends Component
{
    use WithPagination;

    /** How many PROJECTS a page holds. */
    private const PER_PAGE = 8;

    /**
     * Triage order for the default sort — the same order the chips read in, so
     * the list and the controls tell one story. Whatever needs the Director's
     * attention floats up; healthy projects sink.
     */
    private const STATE_ORDER = [
        'not_generated' => 0,
        'pending' => 1,
        'failed' => 2,
        'needs_attention' => 3,
        'at_risk' => 4,
        'on_track' => 5,
    ];

    /**
     * The active filter chip. `''` is "All"; an unknown value resets to it in
     * `mount()`, so a hand-edited `?state=` cannot render an empty page.
     */
    #[Url(as: 'state', except: '')]
    public string $state = '';

    /**
     * Free-text search over the project's code, title, lead and communities.
     *
     * `q` is the house URL alias (`AiAnalysis`, `Faculty\Directory`,
     * `Communities\Index`), which keeps the query string consistent app-wide.
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?string $generationResult = null;

    public string $generationResultMessage = '';

    public ?int $generationResultNarrativeId = null;

    public ?int $viewingNarrativeId = null;

    /** @var array<string, mixed> */
    public array $generationProject = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        if (! array_key_exists($this->state, $this->stateFilters())) {
            $this->state = '';
        }
    }

    /**
     * One chip per state a project's LATEST narrative can be in — the actionable
     * axes. Counts are derived on every render; there is no stored counter to
     * drift.
     *
     * ⚠️ `pending` IS a chip here, and the reason is empirical rather than
     * theoretical. The AI-analysis queue omits it, reasoning that generation is
     * synchronous (v4.4) so a pending row resolves inside the request that made
     * it. That holds only while the request COMPLETES: if the PHP process is
     * killed mid-generation — a timeout against the client's 120s wall-clock
     * budget, a fatal, an aborted Livewire request — the row is left `pending`
     * with `generated_at = NULL` and nothing will ever move it. The dev database
     * had two such rows the day this page was built. A state the page RENDERS but
     * no chip can find is a filter that lies by omission, so it gets a chip, and
     * the card gets a Regenerate action.
     *
     * @return array<string, string>
     */
    public function stateFilters(): array
    {
        return [
            '' => 'All',
            'not_generated' => 'Not generated',
            'pending' => 'Generating',
            'failed' => 'Failed',
            'needs_attention' => 'Needs attention',
            'at_risk' => 'At risk',
            'on_track' => 'On track',
        ];
    }

    public function filterBy(string $state): void
    {
        $this->state = array_key_exists($state, $this->stateFilters()) ? $state : '';

        // A filter change must not leave you on a page that no longer exists.
        // (House pattern — `AiAnalysis::filterBy`, `Faculty\Directory`.)
        $this->resetPage();
    }

    /**
     * Typing must also reset the page: narrowing the search can leave the current
     * page beyond the last one, which renders an empty list that looks like a
     * failed search.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * 5.15: manual trigger, one new row per generation (history kept).
     *
     * The service never throws — it catches its own failures and persists the
     * first-class `failed` state — so the toast has to read the RESULT. Toasting
     * unconditionally printed a green "generated" over a red "unavailable" card.
     */
    public function generate(int $projectId): void
    {
        $cancelKey = $this->generationCancelKey();
        Cache::put($cancelKey, false, now()->addMinutes(10));
        $this->resetGenerationResult();

        $project = ExtensionProject::with(['college', 'communities', 'programLead.user'])->findOrFail($projectId);
        $this->generationProject = $this->projectGenerationInfo($project);

        try {
            $narrative = app(ProgramNarrativeService::class)->generateFor($project);
            $narrative->refresh();

            if (Cache::pull($cancelKey) === true) {
                $this->markGenerationCanceled($narrative);
                $this->setGenerationResult('canceled', 'The project narrative generation was canceled. No narrative was published.');

                return;
            }

            if ($narrative->status === ProgramNarrative::STATUS_COMPLETED) {
                $this->generationResultNarrativeId = $narrative->id;
                $this->setGenerationResult('success', 'The extension project narrative was generated from aggregate project data and is ready to read.');

                return;
            }

            $this->setGenerationResult('error', $narrative->error_message ?: 'Narrative unavailable — see the project row for the reason.');
        } catch (\Throwable $e) {
            $wasCanceled = Cache::pull($cancelKey) === true;
            if ($wasCanceled) {
                $this->setGenerationResult('canceled', 'The project narrative generation was canceled. No narrative was published.');

                return;
            }

            Cache::forget($cancelKey);
            $this->setGenerationResult('error', 'Narrative unavailable — '.$e->getMessage());
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

    public function viewGeneratedNarrative(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        if ($this->generationResultNarrativeId === null) {
            return;
        }

        $this->viewingNarrativeId = $this->generationResultNarrativeId;
        $this->resetGenerationResult();
    }

    public function closeNarrativeModal(): void
    {
        $this->viewingNarrativeId = null;
    }

    private function generationCancelKey(): string
    {
        return 'program-narrative:generation-cancel:'.auth()->id();
    }

    /** @return array<string, mixed> */
    private function projectGenerationInfo(ExtensionProject $project): array
    {
        return [
            'title' => $project->title,
            'code' => $project->code,
            'college' => $project->college?->name ?? 'College not linked',
            'lead' => $project->programLead?->user?->name ?? 'No project lead assigned',
            'communities' => $project->communities->pluck('name')->values()->all(),
        ];
    }

    private function markGenerationCanceled(ProgramNarrative $narrative): void
    {
        $narrative->update([
            'status' => ProgramNarrative::STATUS_FAILED,
            'summary' => null,
            'health_label' => null,
            'risks' => null,
            'recommendations' => null,
            'error_message' => 'Narrative generation canceled by the Director.',
            'generated_at' => now(),
        ]);
    }

    private function resetGenerationResult(): void
    {
        $this->generationResult = null;
        $this->generationResultMessage = '';
        $this->generationResultNarrativeId = null;
        $this->generationProject = [];
    }

    private function setGenerationResult(string $type, string $message): void
    {
        $this->generationResult = $type;
        $this->generationResultMessage = $message;
    }

    public function render()
    {
        $projects = ExtensionProject::query()
            ->with([
                'programNarratives' => fn ($q) => $q->latest(),
                'programLead.user',
                'communities',
                'college',
            ])
            ->get();

        // Derive each row's state and search text WITHOUT the rollup — the chips
        // and the search must never depend on `forProject()`, which is the
        // expensive call. Only the page you can actually see pays for that.
        $rows = $projects->map(function (ExtensionProject $p) {
            $latest = $p->programNarratives->first();

            return [
                'model' => $p,
                'latest' => $latest,
                'state' => $this->stateOf($latest),
                // The four things a Director names a project by, lowered once so
                // the per-keystroke filter stays a plain str_contains.
                'haystack' => mb_strtolower(implode(' ', array_filter([
                    $p->title,
                    $p->code,
                    $p->programLead?->user?->name,
                    $p->communities->pluck('name')->implode(' '),
                ]))),
            ];
        });

        // Search narrows the whole set FIRST, so the chips describe what you are
        // actually looking at rather than the unfiltered set. This is a Collection
        // filter, not SQL — the narratives are already loaded, so there is no LIKE
        // escaping to do (contrast `Faculty\Directory`, which escapes % and _).
        $needle = mb_strtolower(trim($this->search));
        $matched = $needle === ''
            ? $rows
            : $rows->filter(fn (array $r) => str_contains($r['haystack'], $needle))->values();

        // Counts are taken BEFORE the state filter, so the chips never collapse to
        // the active filter's own count.
        $counts = $matched->countBy('state')->all();
        $counts[''] = $matched->count();

        $visible = $this->state === '' ? $matched : $matched->where('state', $this->state)->values();

        // Triage order, then title — a stable, predictable list.
        $ordered = $visible
            ->sortBy(fn (array $r) => sprintf(
                '%d|%s',
                self::STATE_ORDER[$r['state']] ?? 99,
                mb_strtolower($r['model']->title)
            ))
            ->values();

        // `Collection::paginate()` does not exist in this Laravel version, hence
        // the manual paginator (house pattern — `AiAnalysis::render`).
        $page = Paginator::resolveCurrentPage('page');
        $perPage = self::PER_PAGE;

        $paginator = new LengthAwarePaginator(
            $ordered->slice(($page - 1) * $perPage, $perPage)->all(),
            $ordered->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        // Enrich ONLY the page you can see. This is where the ~4-queries-per-
        // project rollup is paid, and it must not be paid for hidden rows.
        $hours = app(TrainingHoursService::class);

        $pageRows = collect($paginator->items())
            ->map(function (array $r) use ($hours) {
                $rollup = $hours->forProject($r['model']);

                return $r + [
                    'training_hours' => $rollup['actual_hours'],
                    'target_hours' => $rollup['target_hours'],
                    'hours_pct' => $rollup['hours_pct'],
                    'trainors' => $rollup['trainors'],
                    'trainees' => $rollup['trainees'],
                    'activities' => $rollup['activity_count'],
                ];
            })
            ->values();

        return view('livewire.program-narratives', [
            'rows' => $pageRows,
            'paginator' => $paginator,
            'counts' => $counts,
            'filters' => $this->stateFilters(),
            'visibleCount' => $ordered->count(),
            'viewingNarrative' => $this->viewingNarrativeId
                ? ProgramNarrative::with(['program', 'generator'])->find($this->viewingNarrativeId)
                : null,
            // Portfolio headline figures — deliberately UNFILTERED, so they answer
            // "how is the whole set doing?" while the chips answer "what am I
            // looking at?". The view labels them as portfolio so the two can never
            // be read as the same number.
            'portfolio' => [
                'total' => $rows->count(),
                'generated' => $rows->whereIn('state', ['on_track', 'at_risk', 'needs_attention'])->count(),
                'on_track' => $rows->where('state', 'on_track')->count(),
                'at_risk' => $rows->where('state', 'at_risk')->count(),
                'needs_attention' => $rows->where('state', 'needs_attention')->count(),
            ],
        ]);
    }

    /**
     * The state of a project's LATEST narrative — which chip it belongs under.
     *
     * An unrecognised or absent health label is treated as needing attention:
     * the same default the card badge uses, so the chip and the badge on screen
     * can never disagree.
     */
    private function stateOf(?ProgramNarrative $latest): string
    {
        if ($latest === null) {
            return 'not_generated';
        }

        if ($latest->status === ProgramNarrative::STATUS_FAILED) {
            return 'failed';
        }

        if ($latest->status === ProgramNarrative::STATUS_PENDING) {
            return 'pending';
        }

        return match ($latest->health_label) {
            'on-track' => 'on_track',
            'at-risk' => 'at_risk',
            default => 'needs_attention',
        };
    }
}
