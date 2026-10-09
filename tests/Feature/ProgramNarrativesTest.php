<?php

namespace Tests\Feature;

use App\Livewire\ProgramNarratives;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\ProgramNarrative;
use App\Models\User;
use App\Services\ProgramNarrativeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Project Narratives page (2026-10-07 — search / filter / pagination pass).
 *
 * Two of these tests exist because the page had no coverage for the behaviour
 * they pin, and both were real defects rather than hypotheticals:
 *
 *  - `test_a_failed_generation_toasts_an_error_not_a_success` — the page used to
 *    dispatch a success toast unconditionally, because the service swallows its
 *    own failures and never throws. The row was honest (red card) and the toast
 *    was not.
 *  - `test_the_training_hours_rollup_runs_only_for_the_visible_page` — the old
 *    render() called `TrainingHoursService::forProject()` for EVERY project, so
 *    the page was an N+1 by design. Pagination is only a win if the enrichment
 *    follows the page, so that ordering is asserted rather than assumed.
 */
class ProgramNarrativesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function project(string $title, string $code, array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => $code,
            'title' => $title,
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ], $overrides));
    }

    private function narrative(
        ExtensionProject $project,
        User $admin,
        string $status,
        ?string $health = null,
        ?string $error = null
    ): ProgramNarrative {
        return ProgramNarrative::create([
            'extension_project_id' => $project->id,
            'generated_by' => $admin->id,
            'status' => $status,
            'health_label' => $health,
            'summary' => $status === 'completed' ? 'A short executive summary.' : null,
            'error_message' => $error,
            // A `pending` row has no generated_at — that is precisely what makes a
            // stuck one (an interrupted request) distinguishable from a live one.
            'generated_at' => $status === 'pending' ? null : now(),
        ]);
    }

    public function test_non_admin_cannot_view_the_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'secretary']))
            ->get('/program-narratives')
            ->assertForbidden();
    }

    public function test_the_page_renders_for_an_admin(): void
    {
        $admin = $this->admin();
        $this->project('KULTURA', 'CAS-2026-001');

        $this->actingAs($admin)->get('/program-narratives')->assertOk()->assertSee('KULTURA');
    }

    /**
     * Search covers the four things a Director names a project by. Each axis is
     * asserted separately so a future narrowing of the haystack fails loudly.
     */
    public function test_search_matches_title_code_lead_and_community(): void
    {
        $admin = $this->admin();

        $leadUser = User::factory()->faculty()->create(['name' => 'Marites Guinto']);
        $lead = Faculty::factory()->create(['user_id' => $leadUser->id]);

        $community = Community::factory()->create(['name' => 'Brgy. Salvacion']);

        $this->project('Coastal Livelihood', 'CAS-2026-010', ['program_lead_id' => $lead->id])
            ->communities()->attach($community->id);

        foreach (['coastal' => 'title', 'cas-2026-010' => 'code', 'marites' => 'lead', 'salvacion' => 'community'] as $needle => $axis) {
            Livewire::actingAs($admin)
                ->test(ProgramNarratives::class)
                ->set('search', $needle)
                ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
                ->assertViewHas('rows', fn ($rows) => $rows->first()['model']->title === 'Coastal Livelihood');

            $this->assertTrue(true, "search matched on {$axis}");
        }
    }

    public function test_search_is_case_insensitive_and_partial(): void
    {
        $admin = $this->admin();
        $this->project('Kultura at Sining', 'CAS-2026-001');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->set('search', 'KULTUR')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
    }

    /**
     * The chips must describe what is ON SCREEN, so the search has to narrow
     * before the counts are taken — the house rule from the AI-analysis queue.
     */
    public function test_search_narrows_before_the_chip_counts(): void
    {
        $admin = $this->admin();
        $this->project('Kultura', 'CAS-2026-001');
        $this->project('Numeracy', 'CAS-2026-002');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->assertViewHas('counts', fn ($c) => $c[''] === 2)
            ->set('search', 'kultura')
            ->assertViewHas('counts', fn ($c) => $c[''] === 1 && ($c['not_generated'] ?? 0) === 1);
    }

    public function test_the_chips_count_and_filter_by_narrative_state(): void
    {
        $admin = $this->admin();

        $none = $this->project('Never Generated', 'CAS-2026-001');
        $failed = $this->project('Broke', 'CAS-2026-002');
        $ok = $this->project('Healthy', 'CAS-2026-003');
        $risk = $this->project('Slipping', 'CAS-2026-004');
        $attention = $this->project('Badly Behind', 'CAS-2026-005');

        $this->narrative($failed, $admin, 'failed', null, 'Narrative unavailable — quota exceeded.');
        $this->narrative($ok, $admin, 'completed', 'on-track');
        $this->narrative($risk, $admin, 'completed', 'at-risk');
        $this->narrative($attention, $admin, 'completed', 'needs-attention');

        $component = Livewire::actingAs($admin)->test(ProgramNarratives::class);

        $component->assertViewHas('counts', fn ($c) => $c[''] === 5
            && $c['not_generated'] === 1
            && $c['failed'] === 1
            && $c['on_track'] === 1
            && $c['at_risk'] === 1
            && $c['needs_attention'] === 1);

        // Each chip narrows to exactly its own project.
        foreach (['not_generated' => 'Never Generated', 'failed' => 'Broke', 'on_track' => 'Healthy', 'at_risk' => 'Slipping', 'needs_attention' => 'Badly Behind'] as $state => $title) {
            Livewire::actingAs($admin)
                ->test(ProgramNarratives::class)
                ->call('filterBy', $state)
                ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['model']->title === $title);
        }

        // A completed narrative with a missing/unrecognised health label is
        // treated as needing attention — the same default the card badge uses.
        $unlabelled = $this->project('Unlabelled', 'CAS-2026-006');
        $this->narrative($unlabelled, $admin, 'completed', null);

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('filterBy', 'needs_attention')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 2);
    }

    /**
     * A `pending` row is reachable in PRACTICE, not just in theory.
     *
     * Generation is synchronous, so a row still pending from an earlier request
     * was interrupted (a timeout against the client's 120s wall-clock budget, a
     * fatal, an aborted request) and nothing will ever move it. The dev database
     * held two such rows the day this page was built. So the page must be able to
     * FIND one (a chip — a state the page renders that no chip can reach is a
     * filter that lies by omission) and ESCAPE it (a new generation), or it reads
     * "Generating…" forever — the silent error the first-class failure state
     * exists to prevent.
     */
    public function test_a_stuck_pending_row_is_findable_and_recoverable(): void
    {
        $admin = $this->admin();
        $stuck = $this->project('Kultura', 'CAS-2026-001');
        $this->project('Numeracy', 'CAS-2026-002');

        $this->narrative($stuck, $admin, 'pending');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->assertViewHas('counts', fn ($c) => ($c['pending'] ?? 0) === 1)
            ->call('filterBy', 'pending')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1
                && $rows->first()['model']->title === 'Kultura')
            ->assertSee('Generating…')
            ->assertSee('Start a new generation');
    }

    /** Attention-first, so the actionable rows are never buried. */
    public function test_rows_are_ordered_attention_first(): void
    {
        $admin = $this->admin();

        $ok = $this->project('A Healthy One', 'CAS-2026-001');
        $failed = $this->project('Z Broken One', 'CAS-2026-002');

        $this->narrative($ok, $admin, 'completed', 'on-track');
        $this->narrative($failed, $admin, 'failed', null, 'nope');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->assertViewHas('rows', fn ($rows) => $rows->first()['state'] === 'failed'
                && $rows->last()['state'] === 'on_track');
    }

    public function test_an_unknown_state_query_parameter_falls_back_to_all(): void
    {
        $admin = $this->admin();
        $this->project('Kultura', 'CAS-2026-001');

        $this->actingAs($admin)
            ->get('/program-narratives?state=not-a-real-state')
            ->assertOk()
            ->assertSee('Kultura');
    }

    public function test_pagination_bounds_the_project_list(): void
    {
        $admin = $this->admin();

        foreach (range(1, 10) as $i) {
            $this->project("Project {$i}", sprintf('CAS-2026-%03d', $i));
        }

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->assertViewHas('paginator', fn ($p) => $p->total() === 10 && $p->count() === 8)
            ->assertViewHas('paginator', fn ($p) => $p->lastPage() === 2);

        // Livewire 3 keeps the page in `paginators.page` (not a `$page` property);
        // `Paginator::resolveCurrentPage()` is wired to it by SupportPagination, so
        // the manual paginator in render() follows `setPage()` correctly.
        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('setPage', 2)
            ->assertViewHas('paginator', fn ($p) => $p->count() === 2 && $p->currentPage() === 2);
    }

    /**
     * Narrowing the list must not leave you stranded on a page that no longer
     * exists — that renders an empty list which looks like a failed search.
     */
    public function test_narrowing_resets_to_page_one(): void
    {
        $admin = $this->admin();

        foreach (range(1, 10) as $i) {
            $this->project("Project {$i}", sprintf('CAS-2026-%03d', $i));
        }

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('search', 'Project 1')
            ->assertSet('paginators.page', 1);

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->call('filterBy', 'not_generated')
            ->assertSet('paginators.page', 1);
    }

    public function test_clear_search_resets_the_search_and_the_page(): void
    {
        $admin = $this->admin();
        $this->project('Kultura', 'CAS-2026-001');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->set('search', 'nothing matches this')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 0)
            ->call('clearSearch')
            ->assertSet('search', '')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
    }

    /**
     * Three different empties, because they need three different fixes: there is
     * nothing to show, your SEARCH found nothing, or your FILTER is too narrow.
     */
    public function test_the_three_empty_states_are_distinguished(): void
    {
        $admin = $this->admin();

        // (1) Nothing exists at all.
        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->assertSee('No projects yet');

        $this->project('Kultura', 'CAS-2026-001');

        // (2) The search found nothing.
        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->set('search', 'zzzz-no-such-project')
            ->assertSee('No project matches')
            ->assertSee('Clear search');

        // (3) The filter is too narrow (every project is not_generated here).
        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('filterBy', 'on_track')
            ->assertSee('Nothing in this state')
            ->assertSee('Show all');
    }

    /** A failed generation exposes the dedicated error result state. */
    public function test_a_failed_generation_exposes_an_error_result(): void
    {
        config(['smartcemes.ai.key' => 'test-key']);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response('quota exceeded', 429)]);

        $admin = $this->admin();
        $project = $this->project('Kultura', 'CAS-2026-001');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('generate', $project->id)
            ->assertSet('generationResult', 'error')
            ->assertSee('Narrative unavailable');

        $this->assertSame('failed', ProgramNarrative::where('extension_project_id', $project->id)->latest('id')->first()->status);
    }

    public function test_a_successful_generation_exposes_a_success_result(): void
    {
        config(['smartcemes.ai.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'summary' => 'On pace.',
                        'health_label' => 'on-track',
                        'risks' => ['A risk'],
                        'recommendations' => [['action' => 'Keep going', 'rationale' => 'It works', 'priority' => 'Low']],
                    ])]]],
                ]],
                'usageMetadata' => ['totalTokenCount' => 500],
            ]),
        ]);

        $admin = $this->admin();
        $project = $this->project('Kultura', 'CAS-2026-001');

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('generate', $project->id)
            ->assertSet('generationResult', 'success')
            ->assertSee('Project narrative generated')
            ->assertSee('View Narrative')
            ->call('viewGeneratedNarrative')
            ->assertSet('generationResult', null)
            ->assertSet('viewingNarrativeId', fn ($id) => is_int($id) && $id > 0)
            ->assertSee('On pace.')
            ->assertSee('Recommended next actions');

        $this->assertSame('completed', ProgramNarrative::where('extension_project_id', $project->id)->latest('id')->first()->status);
    }

    public function test_canceled_generation_is_recorded_and_not_published(): void
    {
        $admin = $this->admin();
        $project = $this->project('Kultura', 'CAS-2026-001');
        $narrative = ProgramNarrative::create([
            'extension_project_id' => $project->id,
            'generated_by' => $admin->id,
            'status' => ProgramNarrative::STATUS_PENDING,
            'metadata' => [],
        ]);

        $service = \Mockery::mock(ProgramNarrativeService::class);
        $service->shouldReceive('generateFor')->once()->andReturnUsing(function () use ($narrative, $admin) {
            Cache::put('program-narrative:generation-cancel:'.$admin->id, true, now()->addMinutes(10));

            return $narrative;
        });
        $this->app->instance(ProgramNarrativeService::class, $service);

        Livewire::actingAs($admin)
            ->test(ProgramNarratives::class)
            ->call('generate', $project->id)
            ->assertSet('generationResult', 'canceled')
            ->assertSee('Generation canceled');

        $narrative->refresh();
        $this->assertSame(ProgramNarrative::STATUS_FAILED, $narrative->status);
        $this->assertSame('Narrative generation canceled by the Director.', $narrative->error_message);
        $this->assertNull($narrative->summary);
    }

    /**
     * The collapsible branch is hidden client-side (`x-show`), so it is still
     * server-rendered — which means its content must be asserted directly or a
     * Blade error inside it would only ever surface in a browser.
     */
    public function test_a_completed_narrative_renders_its_body_and_provenance(): void
    {
        $admin = $this->admin();
        $project = $this->project('Kultura', 'CAS-2026-001');

        ProgramNarrative::create([
            'extension_project_id' => $project->id,
            'generated_by' => $admin->id,
            'status' => 'completed',
            'health_label' => 'at-risk',
            'summary' => 'Hours are behind the annual target.',
            'risks' => ['Attendance is slipping', 'Budget is tight'],
            'recommendations' => [['action' => 'Add a Saturday cohort', 'rationale' => 'Close the hours gap', 'priority' => 'High']],
            'confidence_score' => 0.72,
            'metadata' => ['model' => 'gemini-3.6-flash', 'prompt_version' => 'v2'],
            'generated_at' => now(),
        ]);

        $this->actingAs($admin)->get('/program-narratives')
            ->assertOk()
            ->assertSee('Hours are behind the annual target.')
            ->assertSee('Attendance is slipping')
            ->assertSee('Recommended Next Actions')
            ->assertSee('Add a Saturday cohort')
            ->assertSee('Close the hours gap')
            ->assertSee('At risk')
            // `metadata.prompt_version` is the canonical label INCLUDING its "v"
            // (config/smartcemes.php stores 'v2'; R6GuardrailTest pins that). Five
            // render sites used to prepend another "v", so every AI surface read
            // "prompt vv2". No test asserted the rendered string, so it survived.
            ->assertSee('prompt v2')
            ->assertDontSee('vv2')
            ->assertSee('0.72');
    }

    /**
     * A version row that never FINISHED must not be presented as a narrative.
     *
     * The timeline branched on `failed` alone, so a `pending` version fell through to
     * the completed branch and printed **"Narrative vN · Generated <date>"** — claiming
     * a narrative that was never produced. This is not hypothetical: project KULTURA
     * carried two interrupted `pending` versions from the 2026-10-06 quota failures,
     * and they were being shown as narratives.
     */
    public function test_a_version_that_never_completed_is_not_shown_as_a_narrative(): void
    {
        $admin = $this->admin();
        $project = $this->project('Kultura', 'CAS-2026-001');

        $this->narrative($project, $admin, 'pending');
        $this->narrative($project, $admin, 'completed', 'on-track');

        $this->actingAs($admin)->get('/program-narratives')
            ->assertOk()
            ->assertSee('Generation attempt — never completed')
            ->assertSee('interrupted before it finished')
            // The completed sibling still renders as a numbered narrative. The NUMBER
            // is deliberately not pinned: it is `count - index` over a DESC sort of
            // timestamps that tie here, so pinning it would test the pre-existing
            // numbering scheme rather than this fix.
            ->assertSee('Narrative v')
            ->assertDontSee('Generation attempt — failed');
    }

    /**
     * The cost must scale with the PAGE, not with the set.
     *
     * 12 projects at 8 per page: if the rollup ran for every project regardless
     * of the page, both requests would fire the same number of queries and
     * paginating before enriching would buy nothing.
     *
     * WHY THERE IS NO SECOND, ABSOLUTE-BOUND TEST HERE. The obvious companion —
     * "8 projects must cost fewer than N queries" — was written, measured at 38,
     * and dropped. It cannot do the job it looks like it does: a NEW per-row
     * query would hit the visible page in BOTH requests equally, so no
     * set-scaling comparison can see it, and an absolute bound loose enough to
     * survive framework drift (38 → ~56) would still miss the +8 that one
     * per-row query adds. A brittle guard that cannot catch its own target is
     * worse than an honest relative one, so this is the only query test.
     */
    public function test_the_training_hours_rollup_runs_only_for_the_visible_page(): void
    {
        $admin = $this->admin();

        foreach (range(1, 12) as $i) {
            $this->project("Project {$i}", sprintf('CAS-2026-%03d', $i));
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($admin)->get('/program-narratives?page=1')->assertOk();
        $firstPage = $queries;

        $queries = 0;
        $this->actingAs($admin)->get('/program-narratives?page=2')->assertOk();
        $secondPage = $queries;

        $this->assertGreaterThan(
            $secondPage,
            $firstPage,
            "Page 1 (8 projects) fired {$firstPage} queries and page 2 (4 projects) fired {$secondPage} — the rollup is running for hidden rows."
        );
    }
}
