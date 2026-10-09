<?php

namespace App\Livewire;

use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Services\AssessmentAnalysisService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The AI analysis QUEUE — the index of the split introduced 2026-10-07
 * (`docs/AI-ANALYSIS-REDESIGN-PLAN.md`).
 *
 * It replaces a single page that did three jobs at once (pick a summary and
 * generate / review one analysis / browse history) and could only ever show ONE
 * reviewable analysis: its hero was `$drafts->first()`, where `$drafts` was
 * `completed` + `draft`. So an APPROVED analysis had no reading surface at all.
 *
 * This surface answers one question — *what analyses exist and what state is each
 * in* — and hands the reading to `AiAnalysisReview`.
 *
 * Two things worth knowing:
 *  - An analysis is keyed to a **community + period** (`assessment_summaries` is
 *    unique on community_id/quarter/year), never to a faculty member. Several
 *    contributors roll up into one summary and one analysis.
 *  - **`awaiting_analysis` is a derived state, not a row.** It is every validated
 *    summary with no analysis yet — the pipeline's commonest real state (26 of 30
 *    in the live data), which the old dropdown buried as one option among thirty.
 */
#[Layout('layouts.app')]
class AiAnalysis extends Component
{
    use WithPagination;

    /**
     * How many COMMUNITY GROUPS a page holds — not how many rows.
     *
     * The queue is grouped, so paginating rows would let a community's
     * generations straddle a page break: you would see "gen 2 of 3" on one page
     * and "gen 3 of 3" on the next with nothing tying them together. Bounding the
     * page by groups keeps lineage intact, which is the point of the grouping.
     */
    private const GROUPS_PER_PAGE = 8;

    /**
     * The active filter chip. `''` is "All"; an unknown value resets to it in
     * `mount()`, so a hand-edited `?state=` cannot render an empty page.
     */
    #[Url(as: 'state', except: '')]
    public string $state = '';

    /**
     * Free-text search over the COMMUNITY name.
     *
     * The queue groups by community, so this is how you find one barangay among
     * 21. `q` is the house URL alias (`Faculty\Directory`, `Communities\Index`),
     * which keeps the query string consistent across the app.
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?string $generationResult = null;

    public string $generationResultMessage = '';

    public ?int $generationResultAnalysisId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        if (! array_key_exists($this->state, $this->stateFilters())) {
            $this->state = '';
        }
    }

    /**
     * The filter chips. Counts are DERIVED from the rows on every render — there
     * is no stored counter to drift.
     *
     * ⚠️ `pending` IS a chip, added 2026-10-07 (revisions.md §34.1a). This page
     * originally omitted it, reasoning that generation is synchronous (v4.4) so a
     * pending row resolves inside the request that made it. That holds only while
     * the request COMPLETES: if the process is killed mid-generation — a timeout
     * against the client's 120s wall-clock budget, a fatal, an aborted request —
     * `queueState()` maps the row to `pending` and nothing will ever move it. The
     * narratives page had two such rows in the dev database. Without a chip those
     * rows are unreachable, and the row itself offers no way in — so a state the
     * page RENDERS that no chip can REACH is a filter that lies by omission.
     *
     * @return array<string, string>
     */
    public function stateFilters(): array
    {
        return [
            '' => 'All',
            'awaiting_review' => 'Awaiting review',
            'approved' => 'Approved',
            'failed' => 'Failed',
            'pending' => 'Generating',
            'discarded' => 'Discarded',
            'awaiting_analysis' => 'Awaiting analysis',
        ];
    }

    public function filterBy(string $state): void
    {
        $this->state = array_key_exists($state, $this->stateFilters()) ? $state : '';

        // A filter change must not leave you on a page that no longer exists.
        // (House pattern — Faculty\Directory resets the page in every setter.)
        $this->resetPage();
    }

    /**
     * Typing must also reset the page: narrowing the search can leave the current
     * page beyond the last one, which renders an empty list that looks like a
     * failed search. (House pattern — `Faculty\Directory::updatedSearch`.)
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
     * Generate for one summary — the per-row action.
     *
     * The queue is now the only entry point: the old toolbar select is gone, so a
     * summary can be analysed from the row that says it is awaiting one.
     */
    public function generate(int $summaryId): void
    {
        $cancelKey = $this->generationCancelKey();
        Cache::put($cancelKey, false, now()->addMinutes(10));
        $this->resetGenerationResult();

        try {
            $summary = AssessmentSummary::findOrFail($summaryId);
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
        Cache::put($this->generationCancelKey(), true, now()->addMinutes(10));
    }

    public function closeGenerationResult(): void
    {
        $this->resetGenerationResult();
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

    /**
     * Retry a failed row WITHOUT opening it.
     *
     * Nine of the eleven live analyses are `failed`, so making the Director open
     * each one to retry would be the wrong default. Delegates to the service, so
     * this and the review surface cannot drift.
     */
    public function retry(int $id): void
    {
        $analysis = AssessmentAnalysis::findOrFail($id);

        $failed = app(AssessmentAnalysisService::class)->retry($analysis)->status === AssessmentAnalysis::STATUS_FAILED;
        $analysis->refresh();

        $this->dispatch(
            'sc-toast',
            message: $failed ? $analysis->failureSummary() : 'Retry complete — the draft is ready to review',
            type: $failed ? 'error' : 'info'
        );
    }

    public function render()
    {
        $analyses = AssessmentAnalysis::query()
            ->with(['assessmentSummary.community', 'approver'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $analysedSummaryIds = $analyses->pluck('assessment_summary_id')->filter()->unique()->all();

        $awaiting = AssessmentSummary::query()
            ->with('community')
            ->whereNotIn('id', $analysedSummaryIds)
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get();

        $rows = collect();

        foreach ($analyses as $analysis) {
            $summary = $analysis->assessmentSummary;

            $rows->push([
                'kind' => 'analysis',
                'community' => $summary?->community?->name ?? '— unknown community —',
                // Carried so the group header can link to that community's history page.
                'communityId' => $summary?->community?->id,
                'analysis' => $analysis,
                'summary' => $summary,
                'state' => $analysis->queueState(),
                'period' => $summary ? 'Q'.$summary->quarter.' '.$summary->year : '—',
                'sort' => sprintf('%04d%02d', $summary?->year ?? 0, $summary?->quarter ?? 0),
                'at' => $analysis->created_at,
            ]);
        }

        foreach ($awaiting as $summary) {
            $rows->push([
                'kind' => 'awaiting',
                'community' => $summary->community?->name ?? '—',
                'communityId' => $summary->community?->id,
                'analysis' => null,
                'summary' => $summary,
                'state' => 'awaiting_analysis',
                'period' => 'Q'.$summary->quarter.' '.$summary->year,
                'sort' => sprintf('%04d%02d', $summary->year, $summary->quarter),
                'at' => $summary->updated_at ?? $summary->created_at,
            ]);
        }

        // Search narrows the whole queue FIRST, so the chips describe what you are
        // actually looking at rather than the unfiltered set. Matching is on the
        // COMMUNITY name only — the queue groups by community, so that is the axis
        // a Director searches on. No LIKE escaping here: this is a Collection
        // filter, not SQL (contrast Faculty\Directory, which escapes % and _).
        $needle = mb_strtolower(trim($this->search));
        $matched = $needle === ''
            ? $rows
            : $rows->filter(fn (array $r) => str_contains(mb_strtolower($r['community']), $needle))->values();

        // Counts are taken BEFORE the state filter, so the chips never collapse to
        // the active filter's own count.
        $counts = $matched->countBy('state')->all();
        $counts[''] = $matched->count();

        $visible = $this->state === '' ? $matched : $matched->where('state', $this->state);

        $groups = $this->groupByCommunity($visible);

        // Paginate the GROUP KEYS, then hand the page's slice to the paginator, so
        // a community's rows always travel together. `Collection::paginate()` does
        // not exist in this Laravel version, hence the manual paginator.
        $page = Paginator::resolveCurrentPage('page');
        $perPage = self::GROUPS_PER_PAGE;

        $paginator = new LengthAwarePaginator(
            $groups->slice(($page - 1) * $perPage, $perPage)->all(),
            $groups->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        return view('livewire.ai-analysis', [
            'groups' => $paginator,
            'counts' => $counts,
            'filters' => $this->stateFilters(),
            // The pager's "of N" counts COMMUNITIES, not rows — so say so.
            'communityCount' => $groups->count(),
            'rowCount' => $visible->count(),
        ]);
    }

    /**
     * Community first, then the newest period — so a community's previous
     * quarters and previous generations read together.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    private function groupByCommunity(Collection $rows): Collection
    {
        return $rows
            ->sortByDesc(fn (array $r) => $r['sort'].'|'.str_pad((string) ($r['at'] ? $r['at']->timestamp : 0), 12, '0', STR_PAD_LEFT))
            ->groupBy('community')
            ->sortKeys();
    }
}
