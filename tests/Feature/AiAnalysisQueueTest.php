<?php

namespace Tests\Feature;

use App\Livewire\AiAnalysis;
use App\Livewire\AiAnalysisReview;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The AI analysis QUEUE / REVIEW split (2026-10-07, docs/AI-ANALYSIS-REDESIGN-PLAN.md).
 *
 * Why this file exists: `RouteSurfaceTest::test_the_unlinked_surfaces_render_without_a_server_error`
 * **skips model-parameter routes**, so `/ai-analysis/{analysis}` is not covered
 * by that walk. Without this file the whole split — the point of which is that an
 * APPROVED or DISCARDED analysis finally has a reading surface — would be
 * unguarded.
 */
class AiAnalysisQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * One validated submission for Brgy. San Jose · Q2 2026.
     *
     * `raw_extracted_data` is set EXPLICITLY so the Community response data block
     * renders — that block is the D3 evidence surface and the redesign moved it
     * wholesale, so it is asserted rather than assumed.
     *
     * @return array{admin: User, summary: AssessmentSummary, assessment: NeedsAssessment}
     */
    protected function fixture(): array
    {
        $admin = $this->admin();
        $community = Community::factory()->create(['name' => 'Brgy. San Jose']);

        $assessment = NeedsAssessment::create([
            'community_id' => $community->id,
            'quarter' => 2,
            'year' => 2026,
            'uploaded_by' => $admin->id,
            'review_status' => 'validated',
            'respondent_first_name' => 'Lucia',
            'respondent_last_name' => 'Amistoso',
            'respondent_sex' => 'Female',
            'has_electricity' => 'Yes',
            'available_for_training' => 'Yes',
        ]);

        $summary = AssessmentSummary::where([
            'community_id' => $community->id,
            'quarter' => 2,
            'year' => 2026,
        ])->firstOrFail();

        $summary->update(['total_responses' => 13]);

        return compact('admin', 'summary', 'assessment');
    }

    /**
     * The aggregate snapshot sent to the model (D3). It lives on the ANALYSIS —
     * `assessment_analyses.raw_extracted_data` — not on the summary.
     *
     * @return array<string, mixed>
     */
    protected function aggSnapshot(): array
    {
        return [
            'total_responses' => 13,
            'community' => 'Brgy. San Jose',
            'quarter' => 2,
            'year' => 2026,
            'gender_distribution' => ['Female' => 9, 'Male' => 4],
            'health_problems' => ['Hypertension' => 6, 'Diabetes' => 3],
            'electricity_access_percentage' => 92.3,
        ];
    }

    /** @param array<string, mixed> $overrides */
    protected function analysis(array $data, array $overrides = []): AssessmentAnalysis
    {
        return AssessmentAnalysis::create(array_merge([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'summary' => 'Health access and livelihood dominate this quarter.',
            'raw_extracted_data' => $this->aggSnapshot(),
            'problems_identified' => [['need' => 'No potable water', 'evidence' => '6 of 13 households']],
            'recommendations' => [
                ['rank' => 1, 'title' => 'Household water safety session', 'detail' => 'Two sessions.', 'priority' => 'High', 'ceso_program' => 'Environmental Conservation'],
            ],
            'interagency_referrals' => [],
            'approval_status' => AssessmentAnalysis::APPROVAL_DRAFT,
            'status' => AssessmentAnalysis::STATUS_COMPLETED,
            'metadata' => ['model' => 'test-model', 'prompt_version' => 'v2'],
        ], $overrides));
    }

    /* =====================================================================
     | The queue
     |=================================================================== */

    public function test_the_queue_lists_an_analysis_under_its_community(): void
    {
        $data = $this->fixture();
        $this->analysis($data);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('Brgy. San Jose')
            ->assertSee('Q2 2026')
            ->assertSee('awaiting review')
            ->assertSee('Review →');
    }

    /**
     * `awaiting_analysis` is DERIVED — a validated summary with no analysis is a
     * pipeline state, not a row. The old page buried all 30 summaries in one
     * dropdown; the queue shows the unanalysed ones as work to do.
     */
    public function test_the_queue_shows_a_summary_awaiting_analysis(): void
    {
        $data = $this->fixture();

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('awaiting analysis')
            ->assertSee('13 validated responses')
            ->assertSee('Generate');
    }

    public function test_the_state_filter_narrows_the_queue(): void
    {
        $data = $this->fixture();
        $this->analysis($data, ['status' => AssessmentAnalysis::STATUS_FAILED, 'error_message' => 'HTTP 503 overloaded']);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis?state=failed')
            ->assertOk()
            ->assertSee('failed')
            ->assertSee('HTTP 503');

        // A state with nothing in it says so rather than rendering a blank page.
        $this->actingAs($data['admin'])
            ->get('/ai-analysis?state=approved')
            ->assertOk()
            ->assertSee('Nothing in this state');
    }

    /** An unknown ?state= must reset, not render an empty queue. */
    public function test_an_unknown_state_filter_resets_to_all(): void
    {
        $data = $this->fixture();
        $this->analysis($data);

        Livewire::actingAs($data['admin'])
            ->test(AiAnalysis::class, ['state' => 'nonsense'])
            ->assertSet('state', '');
    }

    /** The failure reason is readable text; the raw body stays in the title. */
    public function test_a_failed_row_shows_a_readable_reason(): void
    {
        $data = $this->fixture();
        $this->analysis($data, [
            'status' => AssessmentAnalysis::STATUS_FAILED,
            'error_message' => '{"error":{"code":503,"message":"This model is currently experiencing high demand."}}',
        ]);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('HTTP 503');
    }

    /**
     * A `pending` row is reachable in PRACTICE, and this page was a dead end for
     * one until 2026-10-07 (revisions.md §34.1a).
     *
     * Generation is synchronous, so a row still pending from an earlier request was
     * interrupted — a timeout against the client's 120s wall-clock budget, a fatal,
     * an aborted request — and nothing will ever move it. It had no chip, so it could
     * not be found; the row had no link in; and `retry()` refuses anything but
     * `failed` (422). The escape hatch is a FRESH generation, which leaves the stuck
     * attempt as history instead of mutating it.
     */
    public function test_a_stuck_pending_row_is_findable_and_recoverable(): void
    {
        $data = $this->fixture();
        $this->analysis($data, ['status' => AssessmentAnalysis::STATUS_PENDING]);
        $this->analysis($data); // a completed draft, so the filter has something to exclude

        Livewire::actingAs($data['admin'])
            ->test(AiAnalysis::class)
            ->assertViewHas('counts', fn ($c) => ($c['pending'] ?? 0) === 1 && ($c['awaiting_review'] ?? 0) === 1)
            ->assertViewHas('rowCount', fn ($n) => $n === 2)
            ->call('filterBy', 'pending')
            ->assertViewHas('rowCount', fn ($n) => $n === 1)
            ->assertSee('Start a new generation');
    }

    /* =====================================================================
     | Lineage — derived, never stored
     |=================================================================== */

    public function test_lineage_is_derived_from_the_rows(): void
    {
        $data = $this->fixture();
        $first = $this->analysis($data);
        $second = $this->analysis($data);

        $this->assertSame(2, $second->generationCount());
        $this->assertSame(1, $first->generationIndex());
        $this->assertSame(2, $second->generationIndex());

        // The newest non-discarded generation is the authoritative one.
        $this->assertTrue($second->isCurrent());
        $this->assertFalse($first->isCurrent());
        $this->assertTrue($first->isSuperseded());

        $this->actingAs($data['admin'])
            ->get('/ai-analysis/'.$second->id)
            ->assertOk()
            ->assertSee('gen 2 of 2')
            ->assertSee('current');
    }

    /** A discarded generation must not be treated as the authoritative one. */
    public function test_a_discarded_generation_is_not_current(): void
    {
        $data = $this->fixture();
        $kept = $this->analysis($data);
        $this->analysis($data, ['approval_status' => AssessmentAnalysis::APPROVAL_DISCARDED]);

        $this->assertTrue($kept->isCurrent(), 'A discarded later generation must not supersede a live one.');
    }

    /* =====================================================================
     | The review surface — every state, which is the point of the split
     |=================================================================== */

    public function test_the_review_surface_renders_for_every_state(): void
    {
        $data = $this->fixture();

        $cases = [
            'awaiting_review' => ['approval_status' => AssessmentAnalysis::APPROVAL_DRAFT, 'status' => AssessmentAnalysis::STATUS_COMPLETED],
            'approved' => ['approval_status' => AssessmentAnalysis::APPROVAL_APPROVED, 'status' => AssessmentAnalysis::STATUS_COMPLETED],
            'discarded' => ['approval_status' => AssessmentAnalysis::APPROVAL_DISCARDED, 'status' => AssessmentAnalysis::STATUS_COMPLETED],
            'failed' => ['approval_status' => AssessmentAnalysis::APPROVAL_DRAFT, 'status' => AssessmentAnalysis::STATUS_FAILED, 'error_message' => 'HTTP 503'],
        ];

        foreach ($cases as $state => $overrides) {
            $analysis = $this->analysis($data, $overrides);

            $response = $this->actingAs($data['admin'])->get('/ai-analysis/'.$analysis->id)->assertOk();

            $this->assertSame($state, $analysis->queueState(), "queueState() for {$state}");

            // The approval gate is offered for a DRAFT only.
            if ($state === 'awaiting_review') {
                $response->assertSee('APPROVE ANALYSIS');
            } else {
                $response->assertDontSee('APPROVE ANALYSIS');
            }
        }
    }

    /**
     * ⚠️ The D3 evidence surface. The redesign moved this block wholesale out of
     * the old page; the owner asked explicitly that it not be broken, so it is
     * asserted down to a breakdown row.
     */
    public function test_the_review_surface_keeps_the_community_response_data_breakdown(): void
    {
        $data = $this->fixture();
        $analysis = $this->analysis($data);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis/'.$analysis->id)
            ->assertOk()
            ->assertSee('Community response data')
            ->assertSee('13 respondents')
            ->assertSee('Respondent profile')
            ->assertSee('Sex')
            ->assertSee('Hypertension')
            ->assertSee('92%')
            ->assertSee('no individual response left the server');
    }

    /** The provenance footer cites the SUMMARY, never "submitted by". */
    public function test_the_provenance_cites_the_summary_not_a_respondent_row(): void
    {
        $data = $this->fixture();
        $analysis = $this->analysis($data);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis/'.$analysis->id)
            ->assertOk()
            ->assertSee('validated responses')
            ->assertDontSee('submitted by');
    }

    /* =====================================================================
     | Regenerate
     |=================================================================== */

    public function test_regenerate_adds_a_generation_and_leaves_the_previous_intact(): void
    {
        $data = $this->fixture();
        $original = $this->analysis($data);

        $this->assertSame(1, AssessmentAnalysis::where('assessment_summary_id', $data['summary']->id)->count());

        Livewire::actingAs($data['admin'])
            ->test(AiAnalysisReview::class, ['analysis' => $original])
            ->call('regenerate');

        // A NEW row, and the original is retained rather than overwritten — which
        // is what makes a regeneration auditable rather than destructive.
        $this->assertSame(2, AssessmentAnalysis::where('assessment_summary_id', $data['summary']->id)->count());
        $this->assertNotNull(AssessmentAnalysis::find($original->id), 'The previous generation must survive.');

        $newest = AssessmentAnalysis::where('assessment_summary_id', $data['summary']->id)->orderByDesc('id')->first();

        $this->assertNotSame($original->id, $newest->id, 'Regenerate must create a NEW generation.');
        $this->assertSame(2, $newest->generationIndex());
        $this->assertSame(2, $original->fresh()->generationCount());
    }

    /**
     * A FAILED newer attempt must NOT demote a usable draft.
     *
     * Caught by looking at the real page: the generation history was marking a
     * `failed` generation as `current`, which would point the Director at the one
     * analysis that has no content.
     */
    public function test_a_failed_newer_generation_does_not_demote_a_completed_draft(): void
    {
        $data = $this->fixture();
        $usable = $this->analysis($data);
        $this->analysis($data, ['status' => AssessmentAnalysis::STATUS_FAILED, 'error_message' => 'HTTP 503']);

        $this->assertTrue($usable->isCurrent(), 'A failed attempt must not supersede a usable draft.');
        $this->assertFalse($usable->isSuperseded());
    }

    /* =====================================================================
     | Authorization — the review route is a NEW surface
     |=================================================================== */

    public function test_only_an_admin_can_open_the_review_surface(): void
    {
        $data = $this->fixture();
        $analysis = $this->analysis($data);

        $secretary = User::factory()->create(['role' => 'secretary']);
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($secretary)->get('/ai-analysis/'.$analysis->id)->assertForbidden();
        $this->actingAs($faculty)->get('/ai-analysis/'.$analysis->id)->assertForbidden();
    }

    /* =====================================================================
     | Presentation — no heading, one container, group-level pagination
     |=================================================================== */

    /**
     * The page heading and the "Director-only" badge were removed by owner
     * request: the sidebar already names the page (the same call the Faculty
     * Management board made, P0i/P0j), and the D3 boundary is stated in the hero
     * panel and the legend.
     */
    public function test_the_queue_has_no_page_heading_or_director_badge(): void
    {
        $data = $this->fixture();

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertDontSee('Director-only')
            ->assertDontSee('Every community needs-analysis')
            // …but the controls and the legend survive.
            ->assertSee('Awaiting analysis')
            ->assertSee('Pipeline states are first-class UI states');
    }

    /**
     * Pagination is by COMMUNITY GROUP, so a community's generations can never
     * straddle a page break. 10 communities at 8 per page ⇒ 8 then 2.
     */
    public function test_the_queue_paginates_by_community_group(): void
    {
        $admin = $this->admin();

        foreach (range(1, 10) as $i) {
            $community = Community::factory()->create(['name' => 'Brgy. Test '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);

            NeedsAssessment::create([
                'community_id' => $community->id,
                'quarter' => 2,
                'year' => 2026,
                'uploaded_by' => $admin->id,
                'review_status' => 'validated',
                'respondent_first_name' => 'Test',
                'respondent_last_name' => 'Respondent',
            ]);
        }

        // Page 1 — the first 8 groups, and a pager that knows the total.
        $this->actingAs($admin)
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('Brgy. Test 01')
            ->assertSee('Brgy. Test 08')
            ->assertDontSee('Brgy. Test 09')
            ->assertSee('of 10')
            ->assertSee('Next');

        // Page 2 — the remaining 2.
        $this->actingAs($admin)
            ->get('/ai-analysis?page=2')
            ->assertOk()
            ->assertSee('Brgy. Test 09')
            ->assertSee('Brgy. Test 10')
            ->assertDontSee('Brgy. Test 01');
    }

    /** Changing the filter must not strand you on a page that no longer exists. */
    public function test_changing_the_filter_resets_pagination(): void
    {
        $data = $this->fixture();

        Livewire::actingAs($data['admin'])
            ->test(AiAnalysis::class)
            ->call('setPage', 2)
            ->call('filterBy', 'failed')
            ->assertSet('paginators.page', 1);
    }

    /* =====================================================================
     | Search — find one barangay among 21
     |=================================================================== */

    /** A second community with a validated summary and no analysis yet. */
    protected function secondCommunity(string $name): AssessmentSummary
    {
        $community = Community::factory()->create(['name' => $name]);

        NeedsAssessment::create([
            'community_id' => $community->id,
            'quarter' => 2,
            'year' => 2026,
            'uploaded_by' => $this->admin()->id,
            'review_status' => 'validated',
            'respondent_first_name' => 'Test',
            'respondent_last_name' => 'Respondent',
        ]);

        return AssessmentSummary::where([
            'community_id' => $community->id,
            'quarter' => 2,
            'year' => 2026,
        ])->firstOrFail();
    }

    public function test_the_search_narrows_the_queue_to_one_community(): void
    {
        $data = $this->fixture();          // Brgy. San Jose, with an analysis
        $this->analysis($data);
        $this->secondCommunity('Brgy. Sagkahan');

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('Brgy. San Jose')
            ->assertSee('Brgy. Sagkahan');

        $this->actingAs($data['admin'])
            ->get('/ai-analysis?q=Sagkahan')
            ->assertOk()
            ->assertSee('Brgy. Sagkahan')
            ->assertDontSee('Brgy. San Jose');
    }

    /** Typing a lowercase fragment must still match — nobody types the casing. */
    public function test_the_search_is_partial_and_case_insensitive(): void
    {
        $data = $this->fixture();
        $this->analysis($data);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis?q=san jo')
            ->assertOk()
            ->assertSee('Brgy. San Jose');
    }

    /** A search that matches nothing must say so, and offer the way back. */
    public function test_a_search_with_no_match_explains_itself(): void
    {
        $data = $this->fixture();
        $this->analysis($data);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis?q=Atlantis')
            ->assertOk()
            ->assertSee('No community matches')
            ->assertSee('Clear search');
    }

    /**
     * The chips count the SEARCHED set, so they describe what is on screen.
     * (If they counted the unfiltered queue, a search would show "Awaiting
     * analysis 26" above a single row.)
     */
    public function test_the_chip_counts_follow_the_search(): void
    {
        $data = $this->fixture();
        $this->analysis($data);                 // Brgy. San Jose → 1 analysis
        $this->secondCommunity('Brgy. Sagkahan'); // → 1 awaiting-analysis row

        // Unsearched: 2 rows total.
        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('2 rows');

        // Searched to Sagkahan: 1 row, and the state chip follows it.
        $this->actingAs($data['admin'])
            ->get('/ai-analysis?q=Sagkahan')
            ->assertOk()
            ->assertSee('1 row')
            ->assertSee('Awaiting analysis');
    }

    /** Typing must reset the page, or a narrowed search can render empty. */
    public function test_typing_a_search_resets_pagination(): void
    {
        $data = $this->fixture();

        Livewire::actingAs($data['admin'])
            ->test(AiAnalysis::class)
            ->call('setPage', 2)
            ->set('search', 'San')
            ->assertSet('paginators.page', 1);
    }

    /** Clearing the search must empty it and reset the page. */
    public function test_clearing_the_search_restores_the_whole_queue(): void
    {
        $data = $this->fixture();
        $this->analysis($data);

        Livewire::actingAs($data['admin'])
            ->test(AiAnalysis::class, ['search' => 'Atlantis'])
            ->assertSee('No community matches')
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertSee('Brgy. San Jose');
    }

    /**
     * A lineage badge must not fire its own query — on ANY of the three surfaces.
     *
     * Six generations on ONE summary is the worst case: each row reads four lineage
     * values (`generationCount`, `generationIndex`, `isCurrent`, `isSuperseded`) and
     * every one of them asks `siblings()`. Un-memoised this measured **36 queries on
     * the queue, 24 of them lineage**; it is now **13 / 12 / 13** across the queue,
     * the community page and the review.
     *
     * The bound is deliberately loose — it exists to catch a regression that
     * re-introduces a per-badge query, not to pin an exact count that framework
     * drift could break.
     */
    public function test_the_lineage_badges_do_not_fire_a_query_each(): void
    {
        $data = $this->fixture();

        foreach (range(1, 6) as $i) {
            $this->analysis($data);
        }

        // ONE listener, with the counter reset per surface — registering a listener
        // per iteration would stack them and double-count.
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $surfaces = [
            'the queue' => '/ai-analysis',
            'the community page' => '/ai-analysis/community/'.$data['summary']->community_id,
            'the review' => '/ai-analysis/'.$data['summary']->id,
        ];

        foreach ($surfaces as $label => $uri) {
            $queries = 0;

            $this->actingAs($data['admin'])->get($uri)->assertOk();

            $this->assertLessThan(
                25,
                $queries,
                "{$label} fired {$queries} queries for 6 generations — a lineage badge is querying again."
            );
        }
    }
}
