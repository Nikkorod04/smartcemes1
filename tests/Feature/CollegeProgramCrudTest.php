<?php

namespace Tests\Feature;

use App\Livewire\Colleges\Index as CollegesIndex;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\FacultyCollegeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase R1 — CRUD + access control for the structural levels
 * (revision §4.1 / §4.2 / §5 Phase R1).
 *
 * Broad Programs are Admin-only to manage; every authenticated role may read
 * them. COLLEGES are different: their set is FIXED — CAS, COE, CME and the
 * Graduate School — and their CRUD was REMOVED on 2026-09-25 (owner request),
 * so `CollegeSeeder` is the set's only owner. See the fixed-set guard below.
 */
class CollegeProgramCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function faculty(): User
    {
        return User::factory()->create(['role' => User::ROLE_FACULTY]);
    }

    private function secretary(): User
    {
        return User::factory()->create(['role' => User::ROLE_SECRETARY]);
    }

    /* ---------------------------------------------------------------------
     | Routes + access
     | ------------------------------------------------------------------ */

    public function test_index_routes_are_admin_only(): void
    {
        $this->actingAs($this->admin())->get('/colleges')->assertOk();
        $this->actingAs($this->admin())->get('/programs')->assertOk();
        $this->actingAs($this->admin())->get('/projects')->assertOk();

        $this->actingAs($this->faculty())->get('/colleges')->assertForbidden();
        $this->actingAs($this->faculty())->get('/programs')->assertForbidden();

        $this->actingAs($this->secretary())->get('/colleges')->assertForbidden();
    }

    public function test_programs_index_is_the_broad_level_and_projects_index_is_the_legacy_list(): void
    {
        /* `/programs` must render the NEW structural level, and `/projects` the
           list that used to own that path. */
        $this->actingAs($this->admin())
            ->get('/programs')
            ->assertSee('Extension Programs')
            ->assertSee('they carry no training-hours target');

        $this->actingAs($this->admin())
            ->get('/projects')
            ->assertOk();
    }

    public function test_legacy_extension_projects_path_redirects_to_projects(): void
    {
        $this->actingAs($this->admin())
            ->get('/extension-programs')
            ->assertRedirect('/projects');
    }

    public function test_project_hub_is_still_reachable_at_the_new_path(): void
    {
        $this->seed();

        $project = ExtensionProject::firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('projects.show', $project))
            ->assertOk();
    }

    /* ---------------------------------------------------------------------
     | College set — FIXED, read-only (owner request 2026-09-25)
     | ------------------------------------------------------------------ */

    /**
     * The college set is fixed and seeded, so its CRUD was removed.
     *
     * Pins BOTH halves: the four official codes exist, AND the component no
     * longer exposes the actions that could add a fifth. Without the second
     * half a re-added `create()` would silently reopen the set — which is
     * exactly the drift the owner asked to close.
     */
    public function test_the_college_set_is_fixed_and_exposes_no_crud(): void
    {
        $this->seed(CollegeSeeder::class);

        $this->assertSame(
            ['CAS', 'CME', 'COE', 'GRAD'],
            College::ordered()->pluck('code')->all(),
            'The college set is fixed: CAS, COE, CME and the Graduate School.'
        );

        $component = Livewire::actingAs($this->admin())->test(CollegesIndex::class)->instance();

        foreach (['create', 'edit', 'save', 'closeForm'] as $action) {
            $this->assertFalse(
                method_exists($component, $action),
                "Colleges\\Index must not expose {$action}() — the college set is fixed."
            );
        }

        /* The seeder is the set's only owner, so re-running it must not drift. */
        $this->seed(CollegeSeeder::class);

        $this->assertSame(4, College::count());
    }

    /**
     * With no CRUD, a coordinator is a seeder concern — every college carries
     * theirs, so the hub can always name someone.
     */
    public function test_seeded_colleges_carry_their_coordinators(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        // Links the roster to colleges. Without it the GRAD coordinator is found
        // (CollegeSeeder matches on email) but carries a NULL college_id, so the
        // "belongs to GRAD" assertion below would be vacuous.
        $this->seed(FacultyCollegeSeeder::class);

        foreach (['CAS', 'COE', 'CME', 'GRAD'] as $code) {
            $this->assertNotNull(
                College::where('code', $code)->firstOrFail()->extension_coordinator_id,
                "{$code} must have a seeded coordinator."
            );
        }

        /* The Graduate School's coordinator must be one of ITS OWN faculty rather
           than borrowed from another college — that is the point of seeding the
           graduate starter set at all. */
        $grad = College::where('code', 'GRAD')->firstOrFail();

        $this->assertSame(
            'GRAD',
            $grad->extensionCoordinator->college->code,
            'The Graduate School coordinator must belong to the Graduate School.'
        );
    }

    public function test_college_component_is_forbidden_to_non_admin(): void
    {
        /* Authorization surfaces at the HTTP layer — the codebase convention
           (see ProgramHubTest). Livewire::test() would bypass the middleware
           that enforces it. */
        $this->actingAs($this->faculty())->get('/colleges')->assertForbidden();
        $this->actingAs($this->secretary())->get('/colleges')->assertForbidden();
    }

    /* ---------------------------------------------------------------------
     | Broad Program CRUD — MOVED to the hub (§25)
     |
     | These used to drive `BroadPrograms`, which carried the only copy of the
     | form. §23 removed every link to that page, leaving the form unreachable,
     | so it MOVED to `Colleges\Index`. The assertions are unchanged — only the
     | component and the method/property names are.
     ---------------------------------------------------------------------- */

    public function test_admin_can_create_a_program_and_a_code_is_generated(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('openProgramCreate')
            ->set('programForm.title', 'Literacy, Numeracy & Language')
            ->set('programForm.pillar', 'social')
            ->set('programForm.ceso_thrust', 'Literacy, Numeracy & Language Enhancement')
            ->set('programForm.status', 'active')
            ->call('saveProgram')
            ->assertHasNoErrors();

        $program = Program::where('title', 'Literacy, Numeracy & Language')->firstOrFail();

        $this->assertStringStartsWith('PROG-'.now()->format('Y').'-', $program->code);
        $this->assertSame('social', $program->pillar);
        $this->assertNull($program->annual_target_hours, 'programs carry no target');
    }

    public function test_program_requires_a_valid_pillar_and_a_cited_thrust(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('openProgramCreate')
            ->set('programForm.title', 'X')
            ->set('programForm.pillar', 'nonsense')
            ->set('programForm.ceso_thrust', '')
            ->call('saveProgram')
            ->assertHasErrors(['programForm.pillar' => 'in', 'programForm.ceso_thrust' => 'required']);
    }

    public function test_admin_can_edit_a_program_without_changing_its_code(): void
    {
        $program = Program::factory()->create(['code' => 'PROG-2026-001', 'title' => 'Old']);

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('openProgramEdit', $program->id)
            ->set('programForm.title', 'New Title')
            ->call('saveProgram')
            ->assertHasNoErrors();

        $program->refresh();
        $this->assertSame('New Title', $program->title);
        $this->assertSame('PROG-2026-001', $program->code, 'code must be immutable on edit');
    }

    public function test_program_component_is_forbidden_to_non_admin(): void
    {
        $this->actingAs($this->faculty())->get('/programs')->assertForbidden();
        $this->actingAs($this->secretary())->get('/programs')->assertForbidden();
    }

    /* ---------------------------------------------------------------------
     | Policies
     | ------------------------------------------------------------------ */

    public function test_college_policy_matrix(): void
    {
        $college = College::factory()->create();

        $this->assertTrue($this->admin()->can('manage', College::class));
        $this->assertFalse($this->faculty()->can('manage', College::class));
        $this->assertFalse($this->secretary()->can('manage', College::class));

        /* Everyone authenticated may read. */
        $this->assertTrue($this->faculty()->can('view', $college));
        $this->assertTrue($this->secretary()->can('view', $college));
    }

    public function test_broad_program_policy_matrix(): void
    {
        $program = Program::factory()->create();

        $this->assertTrue($this->admin()->can('manage', Program::class));
        $this->assertFalse($this->faculty()->can('manage', Program::class));
        $this->assertFalse($this->secretary()->can('manage', Program::class));

        $this->assertTrue($this->faculty()->can('view', $program));
    }

    /**
     * Defence in depth: the route middleware is the primary gate, but each
     * component ALSO authorizes in mount(). Livewire v3 does not rethrow the
     * AuthorizationException — it renders an error response — so the correct
     * signal to assert is that the admin action is unreachable and the Gate
     * denies, not that an exception escapes.
     */
    public function test_component_mount_guard_denies_non_admin_even_without_middleware(): void
    {
        $this->actingAs($this->faculty());

        $this->assertFalse(Gate::allows('manage', College::class));
        $this->assertFalse(Gate::allows('manage', Program::class));

        $html = Livewire::test(CollegesIndex::class)->html();

        /* The create control must never reach a non-admin's HTML. */
        $this->assertStringNotContainsString('New College', $html);
    }

    /* ---------------------------------------------------------------------
     | Nav — the COLLAPSED hierarchy (prototype PATTERNS v4.3, §11.4)
     |
     | The admin sidebar exposes exactly ONE extension-structure entry. The
     | prototype asserts this in `_check.cjs:196-210`; these pin the same rule
     | on the Laravel side, because nothing did before — R1 added three separate
     | entries without reconciling the P0b decision and it went unnoticed.
     | ------------------------------------------------------------------ */

    public function test_the_admin_nav_collapses_the_hierarchy_into_one_hub_entry(): void
    {
        $items = collect(config('smartcemes.nav.admin'))->pluck('items')->flatten(1);

        $hub = $items->firstWhere('key', 'manage-programs');

        $this->assertNotNull($hub, 'The admin nav must carry the "Manage Extension Programs" hub entry.');
        $this->assertSame('Manage Extension Programs', $hub['label']);
        $this->assertSame('colleges.index', $hub['route']);

        // Every level of the chain stays reachable from that ONE entry.
        foreach (['colleges.index', 'programs.index', 'projects.index', 'projects.show'] as $sub) {
            $this->assertContains($sub, $hub['subs'], "The hub must declare the sub route {$sub}.");
        }

        // The three levels must NOT be separate sidebar items.
        $labels = $items->pluck('label');

        foreach (['Colleges', 'Extension Programs', 'Extension Projects'] as $duplicate) {
            $this->assertFalse(
                $labels->contains($duplicate),
                "The admin sidebar still has a separate '{$duplicate}' entry — the hierarchy must be collapsed."
            );
        }
    }

    public function test_the_admin_sidebar_renders_the_hub_entry_and_not_the_levels(): void
    {
        $sidebar = $this->sidebarHtml($this->actingAs($this->admin())->get('/colleges')->assertOk()->getContent());

        $this->assertStringContainsString('Manage Extension Programs', $sidebar);
        $this->assertStringContainsString('nav-chevron', $sidebar, 'The hub entry must signal its drill-down.');

        foreach (['Colleges', 'Extension Programs', 'Extension Projects'] as $duplicate) {
            $this->assertStringNotContainsString(
                ">{$duplicate}</span>",
                $sidebar,
                "The sidebar still renders a '{$duplicate}' nav item."
            );
        }
    }

    /**
     * The single hub entry must stay lit for the WHOLE chain — that is what
     * `subs[]` is for. Without it, /programs and /projects would leave the
     * sidebar with nothing highlighted, since neither is a nav item any more.
     */
    public function test_the_hub_entry_stays_active_anywhere_in_the_chain(): void
    {
        $admin = $this->admin();

        foreach (['/colleges', '/programs', '/projects'] as $path) {
            $html = $this->actingAs($admin)->get($path)->assertOk()->getContent();

            $this->assertMatchesRegularExpression(
                '/<a href="[^"]+"\s+class="sc-nav-link active"[^>]*>(?:(?!<\/a>).)*Manage Extension Programs/s',
                $html,
                "The hub entry must be highlighted on {$path}."
            );
        }

        // …and must NOT be lit on an unrelated page.
        $dashboard = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<a href="[^"]+"\s+class="sc-nav-link active"[^>]*>(?:(?!<\/a>).)*Manage Extension Programs/s',
            $dashboard
        );
    }

    /**
     * A sub-page of the collapsed hub still reports the HUB's label in the
     * topbar, so a project hub reads "Manage Extension Programs" rather than
     * a blank title.
     */
    public function test_a_chain_page_reports_the_hub_label_in_the_topbar(): void
    {
        foreach (['/programs', '/projects'] as $path) {
            $html = $this->actingAs($this->admin())->get($path)->assertOk()->getContent();

            $this->assertMatchesRegularExpression(
                '/<h1[^>]*>Manage Extension Programs<\/h1>/',
                $html,
                "The topbar must read the hub label on {$path}."
            );
        }
    }

    /** The rendered <aside> sidebar, so nav assertions cannot match page body text. */
    private function sidebarHtml(string $html): string
    {
        preg_match('/<aside class="fixed inset-y-0 left-0[^"]*">(.*?)<\/aside>/s', $html, $matches);

        return $matches[1] ?? '';
    }
}
