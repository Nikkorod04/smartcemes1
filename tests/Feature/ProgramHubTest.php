<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Livewire\Programs\Index as ProgramsIndex;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\User;
use App\Services\EmployeeIdService;
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

        $this->actingAs($admin)->get('/programs')->assertOk();
    }

    public function test_hub_renders_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-001',
            'title' => 'Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);

        $this->actingAs($admin)->get('/programs/'.$program->id)->assertOk();
    }

    public function test_unassigned_faculty_cannot_view_hub(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-001',
            'title' => 'Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 0,
        ]);

        $this->actingAs($faculty)->get('/programs/'.$program->id)->assertForbidden();
    }

    public function test_assigned_faculty_can_view_hub(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create([
            'user_id' => $user->id,
            'employee_id' => 'LNU-2026-0009',
        ]);
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-002',
            'title' => 'Test Program 2',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 0,
            'program_lead_id' => $faculty->id,
        ]);

        $this->actingAs($user)->get('/programs/'.$program->id)->assertOk();
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
        $program = ExtensionProgram::create([
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
            ->test(Hub::class, ['program' => $data['program']])
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

    public function test_program_edit_blocks_range_that_excludes_activities(): void
    {
        $data = $this->editBase();
        Activity::create([
            'extension_program_id' => $data['program']->id,
            'title' => 'June Activity',
            'planned_start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-01',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'draft',
        ]);

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['program' => $data['program']])
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
            ->test(Hub::class, ['program' => $data['program']])
            ->call('openProgramEdit')
            ->set('editForm.status', 'ongoing')
            ->call('saveProgramEdit');

        $this->assertSame(
            1,
            DB::table('activity_log')
                ->where('event', 'status_transition')
                ->where('subject_type', ExtensionProgram::class)
                ->where('subject_id', $data['program']->id)
                ->count()
        );
    }

    public function test_faculty_lead_cannot_edit_program(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0009']);
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-002',
            'title' => 'Faculty-led Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 0,
            'program_lead_id' => $faculty->id,
        ]);

        Livewire::actingAs($user)
            ->test(Hub::class, ['program' => $program])
            ->call('openProgramEdit')
            ->assertForbidden();
    }

    /* ==================== PROGRAM CREATION (New Program modal) ==================== */

    public function test_next_code_generates_ext_year_sequence_and_increments(): void
    {
        // 5.1 race-safe sequence pattern: EXT-{year}-{seq}, one reservation per call.
        $first = ExtensionProgram::nextCode(2026);
        $second = ExtensionProgram::nextCode(2026);
        $otherYear = ExtensionProgram::nextCode(2027);

        $this->assertSame('EXT-2026-001', $first);
        $this->assertSame('EXT-2026-002', $second);
        $this->assertSame('EXT-2027-001', $otherYear);

        $this->assertSame(2, (int) DB::table('sequences')->where('key', 'extension_program_2026')->value('last_value'));
    }

    public function test_admin_creates_program_via_form_with_auto_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $community = Community::create([
            'name' => 'Brgy. Sagkahan', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(ProgramsIndex::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('form.title', 'SIKAD-DIGITAL: Digital Literacy for Parents & OSY')
            ->set('form.description', 'Basic computer, e-gov, and online-safety training.')
            ->set('form.planned_start_date', '2026-09-01')
            ->set('form.planned_end_date', '2026-12-31')
            ->set('form.target_beneficiaries', '20')
            ->set('form.allocated_budget', '20000')
            ->set('form.status', 'ongoing')
            ->call('toggleFormArray', 'community_ids', $community->id)
            ->call('toggleFormArray', 'beneficiary_categories', 'Parent')
            ->call('save')
            ->assertSet('showForm', false);

        $program = ExtensionProgram::query()->where('title', 'like', 'SIKAD-DIGITAL%')->first();
        $this->assertNotNull($program, 'program was persisted by the form save');
        $this->assertMatchesRegularExpression('/^EXT-2026-\d{3}$/', $program->code);
        $this->assertSame(20000.0, (float) $program->allocated_budget);
        $this->assertSame([$community->id], $program->communities()->pluck('communities.id')->all());
        $this->assertSame(['Parent'], $program->beneficiary_categories);
        $this->assertSame($admin->id, $program->created_by);
    }

    public function test_next_code_skips_codes_already_used_by_seeded_programs(): void
    {
        // Owner-reported scenario: seeder hardcodes EXT-2026-001..006 without
        // reserving sequence slots (and failed inserts leave the row further
        // behind) — nextCode must self-heal past every stored code.
        foreach (range(1, 6) as $i) {
            ExtensionProgram::create([
                'code' => sprintf('EXT-2026-%03d', $i),
                'title' => "Seeded Program {$i}",
                'planned_start_date' => '2026-01-01',
                'planned_end_date' => '2026-12-31',
                'status' => 'ongoing',
                'allocated_budget' => 0,
            ]);
        }
        DB::table('sequences')->insert(['key' => 'extension_program_2026', 'last_value' => 1]);

        $this->assertSame('EXT-2026-007', ExtensionProgram::nextCode(2026));
        $this->assertSame('EXT-2026-008', ExtensionProgram::nextCode(2026));
    }

    public function test_next_code_never_reuses_a_soft_deleted_program_code(): void
    {
        // The unique index applies to soft-deleted rows too.
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-001',
            'title' => 'Deleted Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'completed',
            'allocated_budget' => 0,
        ]);
        $program->delete();

        $this->assertSame('EXT-2026-002', ExtensionProgram::nextCode(2026));
    }

    public function test_next_code_honors_sequence_ahead_of_existing_codes(): void
    {
        // Gaps are acceptable: a healthy sequence row past the data still wins.
        ExtensionProgram::create([
            'code' => 'EXT-2026-001',
            'title' => 'Only Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 0,
        ]);
        DB::table('sequences')->insert(['key' => 'extension_program_2026', 'last_value' => 9]);

        $this->assertSame('EXT-2026-010', ExtensionProgram::nextCode(2026));
    }

    public function test_employee_id_service_skips_existing_ids(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0004']);

        $this->assertSame('LNU-2026-0005', app(EmployeeIdService::class)->next(2026));
    }
}
