<?php

namespace Tests\Feature;

use App\Livewire\Colleges\Index as CollegesIndex;
use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\User;
use App\Services\EmployeeIdService;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_communities_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/communities')->assertOk();
    }

    public function test_faculty_cannot_view_communities_page(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)->get('/communities')->assertForbidden();
    }

    public function test_admin_can_view_programs_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        /* Phase R1 moved the legacy list from /programs to /projects; /programs
           is now the broad Program level. Use the named route so R2's rename
           does not break this again. */
        $this->actingAs($admin)->get(route('projects.index'))->assertOk();
    }

    public function test_hub_renders_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-001',
            'title' => 'Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);

        $this->actingAs($admin)->get(route('projects.show', $program))->assertOk();
    }

    public function test_unassigned_faculty_cannot_view_hub(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-001',
            'title' => 'Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 0,
        ]);

        $this->actingAs($faculty)->get(route('projects.show', $program))->assertForbidden();
    }

    public function test_assigned_faculty_can_view_hub(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create([
            'user_id' => $user->id,
            'employee_id' => 'LNU-2026-0009',
        ]);
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-002',
            'title' => 'Test Program 2',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 0,
            'program_lead_id' => $faculty->id,
        ]);

        $this->actingAs($user)->get(route('projects.show', $program))->assertOk();
    }

    public function test_wizard_page_renders_for_faculty(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)->get('/assessments/create')->assertOk();
    }

    public function test_wizard_page_renders_for_secretary(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);

        $this->actingAs($secretary)->get('/assessments/create')->assertOk();
    }

    public function test_import_page_renders_for_secretary(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);

        $this->actingAs($secretary)->get('/assessments/import')->assertOk();
    }

    public function test_template_downloads(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);

        $response = $this->actingAs($secretary)->get('/assessments/template');

        $response->assertOk();
        $this->assertStringContainsString('assessment-template', $response->headers->get('content-disposition'));
    }

    public function test_dedup_matches_first_last_and_barangay(): void
    {
        Beneficiary::create([
            'first_name' => 'Lucia', 'last_name' => 'Amistoso', 'barangay' => 'San Jose',
            'beneficiary_category' => 'Housewife', 'gender' => 'Female', 'age' => 41,
        ]);

        $dupes = Beneficiary::duplicatesFor('Lucia', 'Amistoso', 'San Jose');

        $this->assertCount(1, $dupes);
        $this->assertSame('Lucia', $dupes->first()->first_name);
    }

    public function test_dedup_ignores_different_barangay(): void
    {
        Beneficiary::create([
            'first_name' => 'Lucia', 'last_name' => 'Amistoso', 'barangay' => 'San Jose',
            'beneficiary_category' => 'Housewife', 'gender' => 'Female', 'age' => 41,
        ]);

        $this->assertCount(0, Beneficiary::duplicatesFor('Lucia', 'Amistoso', 'Sagkahan'));
    }

    /* ==================== PROGRAM EDIT (hub) ==================== */

    protected function editBase(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $community = Community::create([
            'name' => 'Brgy. Test', 'municipality' => 'Tacloban City', 'province' => 'Leyte',
            'status' => 'active',
        ]);
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-001',
            'title' => 'Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);

        return compact('admin', 'community', 'program');
    }

    public function test_admin_can_edit_program_fields(): void
    {
        $data = $this->editBase();

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openProgramEdit')
            ->assertSet('showProgramEdit', true)
            ->set('editForm.title', 'Renamed Program')
            ->set('editForm.allocated_budget', '25000')
            ->set('editForm.status', 'ongoing')
            ->call('toggleEditArray', 'community_ids', $data['community']->id)
            ->call('saveProgramEdit')
            ->assertSet('showProgramEdit', false);

        $data['program']->refresh();
        $this->assertSame('Renamed Program', $data['program']->title);
        $this->assertSame('ongoing', $data['program']->status);
        $this->assertEquals(25000, (float) $data['program']->allocated_budget);
        $this->assertSame([$data['community']->id], $data['program']->communities()->pluck('communities.id')->all());
    }

    /**
     * The hub's Edit modal must be able to SET the annual hours target — and clear it.
     *
     * It could not until 2026-09-28. The column, the New-project modal and the
     * hub's own rendering of `actual / target` all existed, but the Edit modal had
     * no input for it — so a project created before the column did (six of the
     * eight seeded ones) could never acquire a target from the UI.
     *
     * Clearing must persist NULL, never 0: NULL is what drives the "no target set"
     * state. A 0 would render as a 0%-of-target bar that reads like a broken
     * figure rather than an unset one.
     */
    public function test_edit_modal_sets_and_clears_the_annual_hours_target(): void
    {
        $data = $this->editBase();
        $this->assertNull($data['program']->annual_target_hours, 'starts unset');

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openProgramEdit')
            ->assertSet('showProgramEdit', true)
            ->assertSet('editForm.annual_target_hours', '')
            ->set('editForm.annual_target_hours', '175')
            ->call('saveProgramEdit')
            ->assertSet('showProgramEdit', false)
            ->assertHasNoErrors();

        $this->assertSame(175.0, (float) $data['program']->fresh()->annual_target_hours);

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']->fresh()])
            ->call('openProgramEdit')
            ->set('editForm.annual_target_hours', '')
            ->call('saveProgramEdit')
            ->assertHasNoErrors();

        $this->assertNull($data['program']->fresh()->annual_target_hours, 'cleared means NULL, not 0');
    }

    public function test_edit_modal_rejects_a_negative_hours_target(): void
    {
        $data = $this->editBase();

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openProgramEdit')
            ->set('editForm.annual_target_hours', '-5')
            ->call('saveProgramEdit')
            ->assertHasErrors(['editForm.annual_target_hours']);
    }

    public function test_program_edit_blocks_range_that_excludes_activities(): void
    {
        $data = $this->editBase();
        Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'June Activity',
            'planned_start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-01',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'draft',
        ]);

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openProgramEdit')
            ->set('editForm.planned_end_date', '2026-05-01')
            ->call('saveProgramEdit')
            ->assertHasErrors('editForm.planned_end_date');

        $this->assertSame('2026-12-31', $data['program']->refresh()->planned_end_date->format('Y-m-d'));
    }

    public function test_program_edit_status_change_writes_activity_log(): void
    {
        $data = $this->editBase();

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openProgramEdit')
            ->set('editForm.status', 'ongoing')
            ->call('saveProgramEdit');

        $this->assertSame(
            1,
            DB::table('activity_log')
                ->where('event', 'status_transition')
                ->where('subject_type', ExtensionProject::class)
                ->where('subject_id', $data['program']->id)
                ->count()
        );
    }

    public function test_faculty_lead_cannot_edit_program(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0009']);
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-002',
            'title' => 'Faculty-led Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 0,
            'program_lead_id' => $faculty->id,
        ]);

        Livewire::actingAs($user)
            ->test(Hub::class, ['project' => $program])
            ->call('openProgramEdit')
            ->assertForbidden();
    }

    /* ==================== PROJECT CREATION (New Project modal) ==================== */

    /**
     * R-Q4: NEW projects get COLLEGE-PREFIXED codes. Each college numbers
     * independently, because the prefix is part of the identity.
     */
    public function test_next_code_generates_college_prefixed_sequence_and_increments(): void
    {
        $first = ExtensionProject::nextCode('CAS', 2026);
        $second = ExtensionProject::nextCode('CAS', 2026);
        $otherCollege = ExtensionProject::nextCode('COE', 2026);
        $otherYear = ExtensionProject::nextCode('CAS', 2027);

        $this->assertSame('CAS-2026-001', $first);
        $this->assertSame('CAS-2026-002', $second);
        $this->assertSame('COE-2026-001', $otherCollege, 'each college numbers independently');
        $this->assertSame('CAS-2027-001', $otherYear, 'the sequence is year-scoped');

        $this->assertSame(2, (int) DB::table('sequences')->where('key', 'project_CAS_2026')->value('last_value'));
        $this->assertSame(1, (int) DB::table('sequences')->where('key', 'project_COE_2026')->value('last_value'));
    }

    public function test_next_code_is_case_insensitive_on_the_college_code(): void
    {
        $this->assertSame('CAS-2026-001', ExtensionProject::nextCode('cas', 2026));
        $this->assertSame('CAS-2026-002', ExtensionProject::nextCode('CAS', 2026));
    }

    public function test_admin_creates_project_via_form_with_auto_code(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);

        $admin = User::where('email', 'admin@lnu.com')->first();
        $community = Community::create([
            'name' => 'Brgy. Sagkahan', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        $college = College::where('code', 'CAS')->first();
        $broadProgram = Program::where('title', 'Information, Communication & Education')->first();

        Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->assertSet('showProjectForm', true)
            ->set('projectForm.college_id', (string) $college->id)
            ->set('projectForm.program_id', (string) $broadProgram->id)
            ->set('projectForm.title', 'SIKAD-DIGITAL: Digital Literacy for Parents & OSY')
            ->set('projectForm.description', 'Basic computer, e-gov, and online-safety training.')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.target_beneficiaries', '20')
            ->set('projectForm.allocated_budget', '20000')
            ->set('projectForm.annual_target_hours', '80')
            ->set('projectForm.status', 'ongoing')
            ->call('toggleProjectFormArray', 'community_ids', $community->id)
            ->call('toggleProjectFormArray', 'beneficiary_categories', 'Parent')
            ->call('saveProject')
            ->assertSet('showProjectForm', false);

        $project = ExtensionProject::query()->where('title', 'like', 'SIKAD-DIGITAL%')->first();
        $this->assertNotNull($project, 'project was persisted by the form save');

        /* R-Q4: the code carries the selected college's prefix. */
        $this->assertMatchesRegularExpression('/^CAS-2026-\d{3}$/', $project->code);
        $this->assertSame($college->id, $project->college_id);
        $this->assertSame($broadProgram->id, $project->program_id);
        $this->assertSame(20000.0, (float) $project->allocated_budget);
        $this->assertSame(80.0, (float) $project->annual_target_hours);
        // Budget has no annual target (owner decision 2026-09-26) — the project
        // form no longer collects one, so the legacy column stays NULL.
        $this->assertNull($project->annual_target_budget);
        $this->assertSame([$community->id], $project->communities()->pluck('communities.id')->all());
        $this->assertSame(['Parent'], $project->beneficiary_categories);
        $this->assertSame($admin->id, $project->created_by);
    }

    public function test_project_form_preserves_multiple_communities_and_beneficiary_categories(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);

        $admin = User::where('email', 'admin@lnu.com')->first();
        $communities = collect([
            ['name' => 'Brgy. San Jose', 'municipality' => 'Tacloban City'],
            ['name' => 'Caibaan Elementary School', 'municipality' => 'Tacloban City'],
        ])->map(fn ($community) => Community::create([
            ...$community,
            'province' => 'Leyte',
            'status' => 'active',
        ]));

        $college = College::where('code', 'CAS')->first();
        $broadProgram = Program::where('title', 'Information, Communication & Education')->first();

        Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.college_id', (string) $college->id)
            ->set('projectForm.program_id', (string) $broadProgram->id)
            ->set('projectForm.title', 'Multiple Link Regression Project')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.status', 'draft')
            ->set('projectForm.community_ids', $communities->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->set('projectForm.beneficiary_categories', ['Parent', 'Student', 'Out-of-School Youth'])
            ->assertSet('projectForm.community_ids', $communities->pluck('id')->map(fn ($id) => (string) $id)->all())
            ->assertSet('projectForm.beneficiary_categories', ['Parent', 'Student', 'Out-of-School Youth'])
            ->call('saveProject');

        $project = ExtensionProject::query()->where('title', 'Multiple Link Regression Project')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            $communities->pluck('id')->all(),
            $project->communities()->pluck('communities.id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Parent', 'Student', 'Out-of-School Youth'],
            $project->beneficiary_categories,
        );
    }

    public function test_project_form_requires_a_college_and_a_program(): void
    {
        $this->seed(UserSeeder::class);
        $admin = User::where('email', 'admin@lnu.com')->first();

        Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.title', 'No parent link')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.status', 'ongoing')
            ->call('saveProject')
            ->assertHasErrors(['projectForm.college_id', 'projectForm.program_id']);

        $this->assertNull(ExtensionProject::where('title', 'No parent link')->first());
    }

    /**
     * §3.1 drift scenario: rows hardcode codes without reserving sequence slots,
     * so nextCode must self-heal past every stored code FOR THAT COLLEGE.
     */
    public function test_next_code_skips_codes_already_used_by_seeded_projects(): void
    {
        foreach (range(1, 6) as $i) {
            ExtensionProject::create([
                'code' => sprintf('CAS-2026-%03d', $i),
                'title' => "Seeded Project {$i}",
                'planned_start_date' => '2026-01-01',
                'planned_end_date' => '2026-12-31',
                'status' => 'ongoing',
                'allocated_budget' => 0,
            ]);
        }
        DB::table('sequences')->insert(['key' => 'project_CAS_2026', 'last_value' => 1]);

        $this->assertSame('CAS-2026-007', ExtensionProject::nextCode('CAS', 2026));
        $this->assertSame('CAS-2026-008', ExtensionProject::nextCode('CAS', 2026));
    }

    public function test_next_code_never_reuses_a_soft_deleted_project_code(): void
    {
        $project = ExtensionProject::create([
            'code' => 'CAS-2026-001',
            'title' => 'Deleted Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'completed',
            'allocated_budget' => 0,
        ]);
        $project->delete();

        $this->assertSame('CAS-2026-002', ExtensionProject::nextCode('CAS', 2026));
    }

    public function test_next_code_honors_sequence_ahead_of_existing_codes(): void
    {
        // Gaps are acceptable: a healthy sequence row past the data still wins.
        ExtensionProject::create([
            'code' => 'CAS-2026-001',
            'title' => 'Only Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 0,
        ]);
        DB::table('sequences')->insert(['key' => 'project_CAS_2026', 'last_value' => 9]);

        $this->assertSame('CAS-2026-010', ExtensionProject::nextCode('CAS', 2026));
    }

    /**
     * DUPLICATE PROTECTION PER COLLEGE (§9.2 risk item). A code issued for one
     * college must never be issued for another, and one college's counters must
     * not be advanced by activity in a different college.
     */
    public function test_next_code_never_collides_across_colleges(): void
    {
        $cas1 = ExtensionProject::nextCode('CAS', 2026);
        $coe1 = ExtensionProject::nextCode('COE', 2026);
        $cme1 = ExtensionProject::nextCode('CME', 2026);

        $this->assertSame('CAS-2026-001', $cas1);
        $this->assertSame('COE-2026-001', $coe1);
        $this->assertSame('CME-2026-001', $cme1);
        $this->assertSame(3, count(array_unique([$cas1, $coe1, $cme1])), 'no two colleges share a code');

        /* Persist them and prove the database agrees. */
        foreach ([$cas1, $coe1, $cme1] as $code) {
            ExtensionProject::create([
                'code' => $code,
                'title' => "Project {$code}",
                'planned_start_date' => '2026-01-01',
                'planned_end_date' => '2026-12-31',
                'status' => 'ongoing',
                'allocated_budget' => 0,
            ]);
        }
        /* All three are now persisted, so the self-healing floor sees each
           college's existing maximum. Each must still advance its OWN counter. */
        $this->assertSame('CAS-2026-002', ExtensionProject::nextCode('CAS', 2026), 'CAS advanced independently');
        $this->assertSame('COE-2026-002', ExtensionProject::nextCode('COE', 2026), 'COE advanced independently');
        $this->assertSame('CME-2026-002', ExtensionProject::nextCode('CME', 2026), 'CME advanced independently');
    }

    public function test_employee_id_service_skips_existing_ids(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0004']);

        $this->assertSame('LNU-2026-0005', app(EmployeeIdService::class)->next(2026));
    }
}
