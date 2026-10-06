<?php

namespace Tests\Feature;

use App\Livewire\Colleges\Index as CollegesIndex;
use App\Livewire\Programs\BroadPrograms;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\User;
use App\Services\TrainingHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The extension hub (R7 fidelity pass; Programs level added §23).
 *
 * `/colleges` is the ONE admin entry point for the whole hierarchy:
 *   College → Program → Project → Activity
 *
 * It is a THREE-view page. View 1 shows only the college cards; clicking one (or
 * `?college=CAS`) swaps in view 2 — that college's hero, KPIs and the PROGRAMS
 * it delivers. Clicking a program (or `?college=CAS&program=N`) swaps in view 3:
 * that program's projects, which link on to the project hub.
 *
 * The prototype asserts the OLD two-view shape in `_check.cjs:224-307` and
 * `_hubtest.cjs`; it was deliberately NOT mirrored (§23.4), so those harnesses
 * still pass against the prototype's own markup. These tests cover the Laravel
 * side, which is now ahead of it.
 */
class ExtensionHubTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seeded once per test — seeding twice collides on users.email.
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@lnu.com')->firstOrFail();
    }

    /**
     * View 1 must be the college cards and NOTHING else — no roll-up strip, no
     * program/project tables, and no drill-down rail (removed in P0d).
     */
    public function test_the_hub_opens_on_the_college_cards_only(): void
    {
        $html = $this->actingAs($this->admin())->get('/colleges')->assertOk()->getContent();

        $this->assertStringContainsString('college-card', $html, 'View 1 must render the college cards.');
        $this->assertStringContainsString('Open programs', $html, 'Each card must offer the drill-down CTA.');

        // The cross-college entry points are GONE (§23): the structure is browsed
        // College → Program → Projects, so view 1 offers no flat program list.
        $this->assertStringNotContainsString('View all programs', $html);

        // The rail was deliberately removed everywhere (revisions.md §11.6).
        $this->assertStringNotContainsString('hier-node', $html);
        $this->assertStringNotContainsString('hier-step', $html);

        // No per-college training-hours figure: a college has no such target
        // (§2.2B / D-R5). The only "Training hours" label lives in a project card.
        $this->assertStringNotContainsString('Training hours', $html);
        $this->assertStringNotContainsString('% of target', $html);
    }

    /**
     * Each college renders its official seal, resolved from
     * `config('smartcemes.college_logos')`, on the card AND in the view-2 hero.
     *
     * The `Logo` placeholder chip that stood in for the seal is gone; the code
     * crest survives only as the fallback for a college with no seal on file.
     */
    public function test_each_college_renders_its_official_seal(): void
    {
        $html = $this->actingAs($this->admin())->get('/colleges')->assertOk()->getContent();

        foreach (['CAS' => 'cas.png', 'COE' => 'coe.png', 'CME' => 'cme.png', 'GRAD' => 'grad.png'] as $code => $file) {
            $logo = config("smartcemes.college_logos.{$code}");

            $this->assertSame('img/colleges/'.$file, $logo, "College {$code} must map to its seal.");
            $this->assertFileExists(public_path($logo), "The {$code} seal must be on disk.");
            $this->assertStringContainsString('src="'.asset($logo).'"', $html, "{$code}'s seal must render on its card.");
        }

        // The placeholder marker is gone outright.
        $this->assertStringNotContainsString('college-logo-ph', $html);

        // All FOUR colleges are sealed as of 2026-09-27 — the Graduate School's
        // was the last one outstanding — so no view-1 card falls back to the code
        // crest any more. This assertion used to expect exactly 1 (GRAD); it is
        // now 0, which is what proves the seal branch took over completely.
        $this->assertSame(
            0,
            substr_count($html, 'college-crest'),
            'Every seeded college has a seal, so no card should fall back to the code crest.'
        );

        // View 2 swaps the hero's code badge for the same seal.
        $hero = $this->actingAs($this->admin())->get('/colleges?college=CAS')->assertOk()->getContent();

        $this->assertStringContainsString('src="'.asset('img/colleges/cas.png').'"', $hero);
        $this->assertStringContainsString('alt="College of Arts and Sciences seal"', $hero);
    }

    /**
     * The code crest is still the fallback for a college with no seal on file.
     *
     * No seeded college exercises this branch any more, so it is pinned by
     * dropping an entry from the config map rather than left to be discovered if
     * a fifth college is ever added. (The college set is fixed at four and has no
     * CRUD, so that is a seeder-only scenario — which is exactly why it needs a
     * test rather than a user to notice it.)
     */
    public function test_an_unsealed_college_falls_back_to_the_code_crest(): void
    {
        config(['smartcemes.college_logos' => [
            'CAS' => 'img/colleges/cas.png',
            'COE' => 'img/colleges/coe.png',
            'CME' => 'img/colleges/cme.png',
            // GRAD deliberately absent.
        ]]);

        $html = $this->actingAs($this->admin())->get('/colleges')->assertOk()->getContent();

        $this->assertStringContainsString(
            'college-crest',
            $html,
            'An unsealed college must fall back to its code crest, not a broken image.'
        );
        $this->assertStringNotContainsString('src="'.asset('img/colleges/grad.png').'"', $html);
    }

    /**
     * Every college card must be a real drill-down control, not a dead card.
     */
    public function test_every_college_card_offers_a_drill_down(): void
    {
        $this->actingAs($this->admin());

        $cards = College::ordered()->pluck('code');

        $this->assertNotEmpty($cards);

        foreach ($cards as $code) {
            Livewire::actingAs($this->admin())
                ->test(CollegesIndex::class)
                ->call('selectCollege', $code)
                ->assertSet('college', $code);
        }
    }

    /**
     * `?college=CAS` deep-links straight into view 2 — which since §23 lists the
     * college's PROGRAMS, not its projects.
     */
    public function test_a_college_deep_link_opens_view_two(): void
    {
        $html = $this->actingAs($this->admin())->get('/colleges?college=CAS')->assertOk()->getContent();

        $this->assertStringContainsString('All colleges', $html, 'View 2 must offer the back control.');
        $this->assertStringContainsString('CAS extension programs', $html);
        $this->assertStringContainsString('Faculty &amp; expertise', $html);

        // The projects grid moved to view 3, so view 2 must not render it.
        $this->assertStringNotContainsString('proj-card', $html);

        // The hero's "Programs" button used to link OUT to the cross-college
        // page; the programs are now the next level of this page instead.
        $this->assertStringNotContainsString('View all programs', $html);
    }

    /**
     * A stale/unknown bookmark degrades to view 1 rather than 404ing.
     */
    public function test_an_unknown_college_deep_link_falls_back_to_view_one(): void
    {
        $html = $this->actingAs($this->admin())->get('/colleges?college=ZZZ')->assertOk()->getContent();

        $this->assertStringContainsString('college-card', $html);
        $this->assertStringNotContainsString('All colleges', $html);
    }

    public function test_selecting_and_clearing_a_college_switches_the_view(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->assertSet('college', '')
            ->call('selectCollege', 'CME')
            ->assertSet('college', 'CME')
            ->call('clearCollege')
            ->assertSet('college', '');
    }

    /* ---------------------------------------------------------------------
     | The Programs level (§23)
     |
     | `programs` has no college_id (§3), so a college's programs are DERIVED
     | from its projects — there is no assignment to read. These pin that
     | derivation, the third view, and the fallbacks: a hand-edited URL must
     | degrade to view 2 rather than render an empty view 3 with no way back.
     ---------------------------------------------------------------------- */

    public function test_the_college_view_lists_only_the_programs_it_delivers(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();

        $delivered = ExtensionProject::where('college_id', $cas->id)
            ->with('program')
            ->get()
            ->pluck('program.title')
            ->filter()
            ->unique()
            ->values();

        $this->assertGreaterThan(0, $delivered->count(), 'The seed must give CAS at least one program.');

        // Negative control: a thrust delivered only by some OTHER college.
        $foreign = ExtensionProject::where('college_id', '!=', $cas->id)
            ->with('program')
            ->get()
            ->pluck('program.title')
            ->filter()
            ->unique()
            ->diff($delivered)
            ->first();

        $html = $this->actingAs($this->admin())->get('/colleges?college=CAS')->assertOk()->getContent();

        foreach ($delivered as $title) {
            $this->assertStringContainsString(e($title), $html, "CAS delivers '{$title}', so it must be listed.");
        }

        if ($foreign !== null) {
            $this->assertStringNotContainsString(
                e($foreign),
                $html,
                'A thrust CAS does not deliver must not be listed — programs are derived, never assigned.'
            );
        }
    }

    public function test_view_three_shows_only_the_selected_programs_projects(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();

        // Plain queries rather than collection gymnastics: `except()` on an
        // Eloquent collection keys by the MODEL's primary key, so calling it on
        // a groupBy() result (whose values are collections) throws.
        $programId = ExtensionProject::where('college_id', $cas->id)->value('program_id');

        $mine = ExtensionProject::where('college_id', $cas->id)
            ->where('program_id', $programId)->pluck('code');

        $theirs = ExtensionProject::where('college_id', $cas->id)
            ->where('program_id', '!=', $programId)->pluck('code');

        $this->assertNotEmpty($theirs, 'CAS must deliver 2+ programs, or the negative half proves nothing.');

        $html = Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('selectCollege', 'CAS')
            ->call('selectProgram', $programId)
            ->assertSet('program', (string) $programId)
            ->html();

        foreach ($mine as $code) {
            $this->assertStringContainsString($code, $html, "This program's projects must render.");
        }

        foreach ($theirs as $code) {
            $this->assertStringNotContainsString($code, $html, "Another program's project must not render here.");
        }
    }

    public function test_a_program_deep_link_opens_view_three(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();
        $project = ExtensionProject::where('college_id', $cas->id)->firstOrFail();

        $html = $this->actingAs($this->admin())
            ->get('/colleges?college=CAS&program='.$project->program_id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('proj-card', $html, 'View 3 renders the project cards.');
        $this->assertStringContainsString($project->code, $html);
    }

    public function test_a_program_belonging_to_another_college_falls_back_to_view_two(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();
        $casPrograms = ExtensionProject::where('college_id', $cas->id)->pluck('program_id')->unique();

        $foreign = ExtensionProject::where('college_id', '!=', $cas->id)
            ->whereNotIn('program_id', $casPrograms)
            ->value('program_id');

        if ($foreign === null) {
            $this->markTestSkipped('No thrust is delivered exclusively by another college in this seed.');
        }

        $html = $this->actingAs($this->admin())
            ->get('/colleges?college=CAS&program='.$foreign)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('CAS extension programs', $html, 'It must degrade to view 2.');
        $this->assertStringNotContainsString('proj-card', $html);
    }

    public function test_clearing_a_program_returns_to_the_college_view(): void
    {
        $project = ExtensionProject::firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('selectCollege', $project->college->code)
            ->call('selectProgram', $project->program_id)
            ->assertSet('program', (string) $project->program_id)
            ->call('clearProgram')
            ->assertSet('program', '')
            ->assertSet('college', $project->college->code);
    }

    public function test_selecting_a_college_clears_any_selected_program(): void
    {
        // Otherwise drilling from one college into another would carry the first
        // college's program selection across, and the URL would claim a program
        // that view 2 does not show.
        $project = ExtensionProject::firstOrFail();
        $other = College::where('code', '!=', $project->college->code)->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('selectCollege', $project->college->code)
            ->call('selectProgram', $project->program_id)
            ->call('selectCollege', $other->code)
            ->assertSet('program', '');
    }

    public function test_view_three_offers_the_new_project_and_edit_program_actions(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();
        $project = ExtensionProject::where('college_id', $cas->id)->firstOrFail();

        $html = $this->actingAs($this->admin())
            ->get('/colleges?college=CAS&program='.$project->program_id)
            ->assertOk()
            ->getContent();

        // Both actions are MODAL triggers inside the hub (§25). "New project" used
        // to be a link to /projects carrying `?new=1` — a page the owner had asked
        // to remove, and whose Alpine visibility bridge never fired, so it landed
        // on a page with no form on it.
        $this->assertStringContainsString('wire:click="openProjectCreate"', $html);
        $this->assertStringContainsString('wire:click="openProgramEdit('.$project->program_id.')"', $html);

        $this->assertStringNotContainsString(
            'new=1',
            $html,
            'The ?new=1 deep link is gone — creation no longer navigates anywhere (§25).'
        );
    }

    public function test_opening_the_new_project_modal_prefills_the_college_and_program(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();
        $project = ExtensionProject::where('college_id', $cas->id)->firstOrFail();

        // The modal opens from a CLICK, so the Alpine watcher fires on the
        // false→true transition — which is why the deep-link variant broke.
        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('selectCollege', 'CAS')
            ->call('selectProgram', $project->program_id)
            ->call('openProjectCreate')
            ->assertSet('showProjectForm', true)
            ->assertSet('projectForm.college_id', (string) $cas->id)
            ->assertSet('projectForm.program_id', (string) $project->program_id);
    }

    /**
     * PATTERNS §7: only a PROJECT card may carry a "Training hours" label.
     *
     * No college and no program has an hours TARGET (§2.2B / D-R5), so an hours
     * figure under that label anywhere else reads as one that does not exist.
     * The program-level KPI tile said exactly that until the 2026-09-28 redesign
     * (§24); it now reads "Hours rendered", and this pins it.
     */
    public function test_only_project_cards_label_training_hours(): void
    {
        $cas = College::where('code', 'CAS')->firstOrFail();
        $programId = ExtensionProject::where('college_id', $cas->id)->value('program_id');

        // View 2 — programs, not projects: no project card, so no such label.
        $view2 = $this->actingAs($this->admin())->get('/colleges?college=CAS')->assertOk()->getContent();

        $this->assertSame(0, substr_count($view2, 'Training hours'), 'View 2 must not label training hours.');
        $this->assertStringContainsString(
            'hrs rendered',
            $view2,
            'A program\'s hours must read as RENDERED, never as a target.'
        );

        // View 3 — one label per project card, and nowhere else.
        $view3 = $this->actingAs($this->admin())
            ->get('/colleges?college=CAS&program='.$programId)
            ->assertOk()
            ->getContent();

        // `proj-card-stripe` is exactly one per project card, and unlike
        // `proj-card` it does not also match `proj-card-stripe`'s sibling classes.
        $cards = substr_count($view3, 'proj-card-stripe');

        $this->assertGreaterThan(0, $cards, 'The fixture must give this program at least one project.');
        $this->assertSame(
            $cards,
            substr_count($view3, 'Training hours'),
            'Only project cards may carry the label — exactly one per card.'
        );
        $this->assertStringContainsString('Hours rendered', $view3);
    }

    /**
     * The card roll-ups are DERIVED from the college's projects — they must not
     * be invented, and they must match the projects actually under the college.
     */
    public function test_college_card_rollups_are_derived_from_its_projects(): void
    {
        $this->actingAs($this->admin());

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->assertViewHas('cards', function ($cards) {
                foreach ($cards as $card) {
                    $expected = $card['model']->projects()->count();

                    if ($card['projects'] !== $expected) {
                        return false;
                    }
                }

                return $cards->sum('projects') === ExtensionProject::count();
            });
    }

    /**
     * The view-2 project toolbar filters on status and search text.
     */
    public function test_the_project_toolbar_filters_the_college_projects(): void
    {
        $this->actingAs($this->admin());

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('selectCollege', 'CAS')
            ->set('projectStatus', 'Draft')
            ->assertViewHas('projectRows', fn ($rows) => $rows->every(fn ($r) => $r->status === 'draft'));
    }

    /* ---------------------------------------------------------------------
     | /programs — the broad level
     | ------------------------------------------------------------------ */

    /**
     * The prototype's toolbar and drill-down CTA were missing entirely (drift,
     * `revisions.md` §19.2 category B). Pin them so they cannot vanish again.
     */
    public function test_the_programs_page_carries_the_toolbar_and_drill_down(): void
    {
        $html = $this->actingAs($this->admin())->get('/programs')->assertOk()->getContent();

        foreach ([
            'Search program, thrust, or college',
            'All pillars',
            'All colleges',
            'Sort: training hours rendered',
            'Grid view',
            'List view',
            'View projects →',
            'View all projects →',
            '← Colleges',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "The /programs toolbar is missing: {$needle}");
        }
    }

    /**
     * The roll-ups are DERIVED from the child projects — a program's hours must
     * equal the sum of its projects' hours, and its college membership is read
     * off those projects (the `programs` table has no college_id by design).
     */
    public function test_program_rollups_are_derived_from_their_projects(): void
    {
        $this->actingAs($this->admin());

        Livewire::actingAs($this->admin())
            ->test(BroadPrograms::class)
            ->assertViewHas('rows', function ($rows) {
                $hours = app(TrainingHoursService::class);

                foreach ($rows as $row) {
                    $expectedHours = (float) $row->model->projects
                        ->sum(fn ($p) => $hours->forProject($p)['actual_hours']);

                    if (abs($row->training_hours - $expectedHours) > 0.001) {
                        return false;
                    }

                    if ($row->project_count !== $row->model->projects->count()) {
                        return false;
                    }
                }

                return true;
            });
    }

    /**
     * D-R5: a program carries NO target and NO attainment. The prototype renders
     * both; Laravel must not (`revisions.md` §19.2, category A).
     */
    public function test_a_program_exposes_no_target_or_attainment(): void
    {
        $this->actingAs($this->admin());

        Livewire::actingAs($this->admin())
            ->test(BroadPrograms::class)
            ->assertViewHas('rows', function ($rows) {
                foreach ($rows as $row) {
                    foreach (['hours_target', 'hours_pct', 'target_hours', 'budget_allocated', 'budget_pct', 'attainment'] as $forbidden) {
                        if (property_exists($row, $forbidden) || isset($row->{$forbidden})) {
                            return false;
                        }
                    }
                }

                return true;
            });
    }

    public function test_the_program_toolbar_filters_and_sorts(): void
    {
        $this->actingAs($this->admin());

        $component = Livewire::actingAs($this->admin())->test(BroadPrograms::class);

        $total = $component->viewData('totalPrograms');

        // Pillar filter narrows the list.
        $component->call('setPillar', 'Social')
            ->assertViewHas('rows', fn ($rows) => $rows->every(fn ($r) => $r->pillar === 'social'))
            ->assertViewHas('rows', fn ($rows) => $rows->count() <= $total);

        // Search narrows to rows matching any searched field (code, title,
        // blurb, thrust or derived college membership).
        $component->call('setPillar', 'All')
            ->set('search', 'literacy')
            ->assertViewHas('rows', fn ($rows) => $rows->every(fn ($r) => str_contains(
                mb_strtolower(implode(' ', array_filter([
                    $r->code, $r->title, $r->blurb, $r->thrust, implode(' ', $r->colleges),
                ]))),
                'literacy'
            )));

        // A–Z sort really orders by title.
        $component->set('search', '')->set('sort', 'title')
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('title')->values()->all()
                === $rows->pluck('title')->sort()->values()->all());

        // Clearing restores everything.
        $component->call('clearFilters')
            ->assertSet('pillar', 'All')
            ->assertSet('college', 'All')
            ->assertSet('sort', 'hours')
            ->assertSet('search', '');
    }

    public function test_the_grid_list_toggle_switches_views(): void
    {
        $this->actingAs($this->admin());

        $component = Livewire::actingAs($this->admin())
            ->test(BroadPrograms::class)
            ->assertSet('view', 'grid');

        $component->call('setView', 'list')->assertSet('view', 'list');
        $component->call('setView', 'grid')->assertSet('view', 'grid');
    }
}
