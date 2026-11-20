<?php

namespace App\Livewire;

use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The AI analysis INDEX, redesigned 2026-11-20 as a COMMUNITY LIST.
 *
 * The previous queue listed every analysis row grouped by community, which made
 * the Director read the pipeline before choosing a barangay. This surface answers
 * the first question instead: *which communities have data, and which need
 * attention?* One row per community, one page of communities, and the work
 * happens on the community dossier (`ai-analysis.community`).
 *
 * A community's state is derived, never stored:
 *   draft     - a completed draft is waiting for review (highest priority)
 *   pending   - a generation was interrupted and will never resolve alone
 *   failed    - the only attempts failed
 *   needs     - validated data exists but no usable analysis (never analysed,
 *               or every attempt was discarded/failed)
 *   approved  - at least one approved analysis, nothing newer waiting
 *   no_data   - no validated summary yet
 *
 * The list is paginated by COMMUNITY (not by analysis row), so a community can
 * never straddle a page break.
 */
#[Layout('layouts.app')]
class AiAnalysis extends Component
{
    use WithPagination;

    private const PER_PAGE = 12;

    /** Attention first, then alphabetical inside the state. */
    private const STATE_RANK = [
        'draft' => 0,
        'pending' => 1,
        'failed' => 2,
        'needs' => 3,
        'approved' => 4,
        'no_data' => 5,
    ];

    #[Url(as: 'state', except: '')]
    public string $state = '';

    /** Free-text search over the community name, municipality or province. */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        // Older bookmarks used the row-level state names. Map them onto the new
        // community states instead of dumping the Director on an empty page.
        $this->state = match ($this->state) {
            'awaiting_review' => 'draft',
            'awaiting_analysis', 'discarded' => 'needs',
            'draft', 'pending', 'failed', 'approved', 'no_data' => $this->state,
            default => '',
        };
    }

    /** @return array<string, string> */
    public function stateFilters(): array
    {
        return [
            '' => 'All',
            'draft' => 'Draft awaiting review',
            'pending' => 'Generating',
            'failed' => 'Failed',
            'needs' => 'Needs analysis',
            'approved' => 'Approved',
            'no_data' => 'No validated data',
        ];
    }

    public function filterBy(string $state): void
    {
        $this->state = array_key_exists($state, $this->stateFilters()) ? $state : '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $rows = $this->communityRows();

        $needle = mb_strtolower(trim($this->search));
        $matched = $needle === ''
            ? $rows
            : $rows->filter(fn (array $row) => str_contains(mb_strtolower($row['haystack']), $needle))->values();

        // Counts are taken BEFORE the state filter, so the chips describe the
        // searched set rather than collapsing to the active filter.
        $counts = $matched->countBy('state')->all();
        $counts[''] = $matched->count();

        $visible = $this->state === '' ? $matched : $matched->where('state', $this->state)->values();

        $page = Paginator::resolveCurrentPage('page');
        $paginator = new LengthAwarePaginator(
            $visible->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values()->all(),
            $visible->count(),
            self::PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );

        return view('livewire.ai-analysis', [
            'communities' => $paginator,
            'counts' => $counts,
            'filters' => $this->stateFilters(),
            'communityCount' => $visible->count(),
            'attentionCount' => $visible->whereIn('state', ['draft', 'pending', 'failed', 'needs'])->count(),
        ]);
    }

    /**
     * Every community with its derived work state.
     *
     * The dataset is small (tens of communities, not thousands), so the state is
     * computed in memory after one aggregate query. The expensive part — loading
     * analyses — is reduced to counts and a few columns, never the narrative.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function communityRows(): Collection
    {
        $communities = Community::query()
            ->with(['assessmentSummaries' => function ($query) {
                $query->withCount([
                    'analyses as analyses_count',
                    'analyses as draft_count' => fn ($q) => $q
                        ->where('status', AssessmentAnalysis::STATUS_COMPLETED)
                        ->where('approval_status', AssessmentAnalysis::APPROVAL_DRAFT),
                    'analyses as pending_count' => fn ($q) => $q
                        ->where('status', AssessmentAnalysis::STATUS_PENDING),
                    'analyses as failed_count' => fn ($q) => $q
                        ->where('status', AssessmentAnalysis::STATUS_FAILED),
                    'analyses as approved_count' => fn ($q) => $q
                        ->where('approval_status', AssessmentAnalysis::APPROVAL_APPROVED),
                ])->orderByDesc('year')->orderByDesc('quarter');
            }])
            ->orderBy('name')
            ->get();

        // One readable failure per summary, so a failed row says WHY without the
        // Director opening the dossier first.
        $failedBySummary = AssessmentAnalysis::query()
            ->whereIn('assessment_summary_id', $communities->flatMap->assessmentSummaries->pluck('id'))
            ->where('status', AssessmentAnalysis::STATUS_FAILED)
            ->orderByDesc('id')
            ->get(['id', 'assessment_summary_id', 'error_message'])
            ->unique('assessment_summary_id')
            ->keyBy('assessment_summary_id');

        return $communities->map(function (Community $community) use ($failedBySummary) {
            /** @var Collection<int, AssessmentSummary> $summaries */
            $summaries = $community->assessmentSummaries;
            $withData = $summaries->where('total_responses', '>', 0);

            $draft = (int) $summaries->sum('draft_count');
            $pending = (int) $summaries->sum('pending_count');
            $failed = (int) $summaries->sum('failed_count');
            $approved = (int) $summaries->sum('approved_count');

            // "Needs analysis" means a period with validated data but no usable
            // analysis. A failed or discarded-only period still needs one.
            $needs = $withData->filter(function ($summary) {
                $usable = $summary->draft_count > 0 || $summary->approved_count > 0;

                return ! $usable && $summary->pending_count === 0;
            })->count();

            $state = match (true) {
                $draft > 0 => 'draft',
                $pending > 0 => 'pending',
                $failed > 0 => 'failed',
                $needs > 0 => 'needs',
                $approved > 0 => 'approved',
                default => 'no_data',
            };

            $latest = $withData->first();
            $lastActivity = $summaries
                ->map(fn ($s) => $s->last_calculated_at ?? $s->updated_at)
                ->filter()
                ->sortDesc()
                ->first();

            $failure = $summaries
                ->map(fn ($s) => $failedBySummary->get($s->id))
                ->filter()
                ->first();

            return [
                'id' => $community->id,
                'name' => $community->name,
                'type' => $community->isSchool() ? 'School' : 'Barangay',
                'location' => trim(implode(', ', array_filter([$community->municipality, $community->province]))),
                'periods' => $withData->count(),
                'validated' => (int) $withData->sum('total_responses'),
                'latest' => $latest ? 'Q'.$latest->quarter.' '.$latest->year : null,
                'last_activity' => $lastActivity,
                'draft' => $draft,
                'pending' => $pending,
                'failed' => $failed,
                'needs' => $needs,
                'approved' => $approved,
                'state' => $state,
                'failure' => $failure?->failureSummary(),
                'haystack' => implode(' ', array_filter([
                    $community->name,
                    $community->municipality,
                    $community->province,
                ])),
            ];
        })->sortBy(fn (array $row) => sprintf(
            '%d|%s',
            self::STATE_RANK[$row['state']] ?? 99,
            mb_strtolower($row['name'])
        ))->values();
    }
}
