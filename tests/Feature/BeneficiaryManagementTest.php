<?php

namespace Tests\Feature;

use App\Livewire\Beneficiaries\Index as BeneficiariesIndex;
use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use App\Services\ActivityAttendanceTemplate;
use App\Services\BeneficiaryTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

class BeneficiaryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBase(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'employee_id' => 'LNU-2026-0009']);

        $community = Community::factory()->create([
            'name' => 'San Jose',
            'municipality' => 'Tacloban City',
            'province' => 'Leyte',
            'type' => Community::TYPE_COMMUNITY,
            'status' => 'active',
        ]);

        $program = ExtensionProject::create([
            'code' => 'EXT-2026-010',
            'title' => 'Beneficiary Management Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
            'program_lead_id' => $faculty->id,
        ]);
        $program->communities()->attach($community->id);

        return compact('admin', 'secretary', 'facultyUser', 'faculty', 'program', 'community');
    }

    protected function beneficiary(ExtensionProject $project, Community $community, array $overrides = []): Beneficiary
    {
        return Beneficiary::create(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'age' => 40,
            'gender' => 'Male',
            'barangay' => $community->name,
            'municipality' => $community->municipality,
            'province' => $community->province,
            'community_id' => $community->id,
            'extension_project_id' => $project->id,
            'beneficiary_category' => 'Farmer',
        ], $overrides));
    }

    protected function makeXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(BeneficiaryTemplate::headers(), null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        return $this->uploadedSpreadsheet($spreadsheet, 'beneficiaries.xlsx');
    }

    protected function makeOldXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'First Name', 'Middle Name', 'Last Name', 'Age', 'Sex', 'Contact Number',
            'Barangay', 'Municipality / City', 'Beneficiary Category',
        ], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        return $this->uploadedSpreadsheet($spreadsheet, 'old-beneficiaries.xlsx');
    }

    protected function uploadedSpreadsheet(Spreadsheet $spreadsheet, string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sc_ben_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    protected function makeAttendanceXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(ActivityAttendanceTemplate::HEADERS, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        return $this->uploadedSpreadsheet($spreadsheet, 'attendance.xlsx');
    }

    /* ==================== policy ==================== */

    public function test_policy_scopes_beneficiary_management(): void
    {
        $data = $this->seedBase();

        $this->assertTrue($data['admin']->can('manageBeneficiaries', $data['program']));
        $this->assertTrue($data['admin']->can('manage', $data['program']));

        $this->assertTrue($data['secretary']->can('manageBeneficiaries', $data['program']));
        $this->assertFalse($data['secretary']->can('manage', $data['program']));

        $this->assertFalse($data['facultyUser']->can('manageBeneficiaries', $data['program']));
        $this->assertFalse($data['facultyUser']->can('manage', $data['program']));
    }

    /* ==================== add / edit / archive ==================== */

    public function test_secretary_can_add_edit_and_archive(): void
    {
        $data = $this->seedBase();

        $test = Livewire::actingAs($data['secretary'])->test(Hub::class, ['project' => $data['program']]);

        $test->call('openRegister')
            ->set('registerForm.first_name', 'Maria')
            ->set('registerForm.last_name', 'Santos')
            ->set('registerForm.age', '35')
            ->set('registerForm.gender', 'Female')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Housewife')
            ->call('saveRegister')
            ->assertSet('showRegister', false);

        $maria = Beneficiary::where('first_name', 'Maria')->firstOrFail();
        $this->assertSame($data['program']->id, $maria->extension_project_id);
        $this->assertSame($data['community']->id, $maria->community_id);
        $this->assertNull($maria->phone, 'Blank contact must stay blank.');
        $this->assertSame(1, $data['program']->beneficiaries()->count());

        // Secretary can correct a profile.
        $test->call('openBeneficiaryEdit', $maria->id)
            ->set('beneficiaryEditForm.phone', '0917 123 4567')
            ->call('saveBeneficiaryEdit');

        $this->assertSame('0917 123 4567', $maria->fresh()->phone);

        // Archive keeps history and removes it from the active roster.
        $activity = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Session',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'completed',
        ]);
        $activity->attendances()->create([
            'beneficiary_id' => $maria->id,
            'attendance_date' => '2026-03-01',
            'status' => 'present',
        ]);

        $test->call('archiveBeneficiary', $maria->id);
        $this->assertSoftDeleted($maria);
        $this->assertSame(0, $data['program']->beneficiaries()->count());
        $this->assertSame(1, $maria->fresh()->attendances()->count(), 'Attendance history must survive an archive.');
    }

    public function test_admin_can_add_but_cannot_edit_a_beneficiary(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Ana')
            ->set('registerForm.last_name', 'Reyes')
            ->set('registerForm.age', '28')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Vendor')
            ->call('saveRegister');

        $ana = Beneficiary::where('first_name', 'Ana')->firstOrFail();

        // D8 stays: Admin creates, the Secretary edits.
        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openBeneficiaryEdit', $ana->id)
            ->assertForbidden();
    }

    public function test_names_and_contact_are_validated(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Juan2')
            ->set('registerForm.last_name', 'Dela Cruz')
            ->set('registerForm.age', '35')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertHasErrors('registerForm.first_name');

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Juan')
            ->set('registerForm.last_name', 'Dela Cruz')
            ->set('registerForm.age', '35')
            ->set('registerForm.phone', 'call me maybe')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertHasErrors('registerForm.phone');

        $this->assertSame(0, Beneficiary::count());
    }

    /* ==================== import ==================== */

    public function test_secretary_can_import_beneficiaries_from_the_v2_template(): void
    {
        $data = $this->seedBase();

        $file = $this->makeXlsx([
            ['Pedro', '', 'Ramos', '50', 'Male', '09171234567', 'San Jose', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            ->call('confirmImport');

        $beneficiary = Beneficiary::sole();
        $this->assertSame($data['program']->id, $beneficiary->extension_project_id);
        $this->assertSame($data['community']->id, $beneficiary->community_id);
        $this->assertSame(1, $data['program']->beneficiaries()->count());
    }

    public function test_import_rejects_the_old_template(): void
    {
        $data = $this->seedBase();

        $file = $this->makeOldXlsx([
            ['Pedro', '', 'Ramos', '50', 'Male', '09171234567', 'San Jose', 'Tacloban City', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', false)
            ->assertHasErrors('importFile')
            ->assertSee('v2 beneficiary template');

        $this->assertSame(0, Beneficiary::count());
    }

    public function test_import_updates_an_existing_person_without_clearing_blank_fields(): void
    {
        $data = $this->seedBase();
        $existing = $this->beneficiary($data['program'], $data['community'], [
            'first_name' => 'Pedro',
            'last_name' => 'Ramos',
            'age' => 50,
            'phone' => '09170000000',
        ]);

        $file = $this->makeXlsx([
            ['Pedro', '', 'Ramos', '51', 'Male', '', 'San Jose', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importRows.0.status', 'update')
            ->call('confirmImport');

        $existing->refresh();
        $this->assertSame(51, $existing->age);
        $this->assertSame('09170000000', $existing->phone, 'A blank import cell must not erase the saved number.');
        $this->assertSame(1, Beneficiary::count());
    }

    public function test_import_skips_a_person_in_another_project(): void
    {
        $data = $this->seedBase();

        $otherProject = ExtensionProject::create([
            'code' => 'EXT-2026-011',
            'title' => 'Other Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $otherProject->communities()->attach($data['community']->id);
        $this->beneficiary($otherProject, $data['community'], [
            'first_name' => 'Pedro',
            'last_name' => 'Ramos',
        ]);

        $file = $this->makeXlsx([
            ['Pedro', '', 'Ramos', '50', 'Male', '', 'San Jose', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importRows.0.status', 'other_project')
            ->call('confirmImport');

        $this->assertSame(1, Beneficiary::count(), 'The other project’s record must not be duplicated or moved.');
    }

    /* ==================== registry import ==================== */

    public function test_registry_page_imports_for_the_chosen_project(): void
    {
        $data = $this->seedBase();

        $file = $this->makeXlsx([
            ['Pedro', '', 'Ramos', '50', 'Male', '09171234567', 'San Jose', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openImport')
            ->assertSet('showImport', true)
            ->set('importProjectId', (string) $data['program']->id)
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            ->assertCount('importRows', 1)
            ->assertSee('Target:')
            ->assertSee('EXT-2026-010')
            ->assertSee('1 new')
            ->call('confirmImport')
            ->assertSet('showImport', false);

        $beneficiary = Beneficiary::sole();
        $this->assertSame($data['program']->id, $beneficiary->extension_project_id);
        $this->assertSame($data['community']->id, $beneficiary->community_id);
        $this->assertSame('09171234567', $beneficiary->phone);
    }

    public function test_registry_import_requires_a_target_project(): void
    {
        $data = $this->seedBase();

        $file = $this->makeXlsx([
            ['Pedro', '', 'Ramos', '50', 'Male', '', 'San Jose', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openImport')
            ->set('importProjectId', '')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertHasErrors('importProjectId')
            ->assertSet('importPreview', false);

        $this->assertSame(0, Beneficiary::count());
    }

    /* ==================== attendance import still works ==================== */

    public function test_secretary_can_import_activity_attendance(): void
    {
        $data = $this->seedBase();
        $activity = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Training Session',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'ongoing',
        ]);
        $beneficiary = $this->beneficiary($data['program'], $data['community']);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRecords', $activity->id)
            ->set('attendanceImportFile', $this->makeAttendanceXlsx([
                [$beneficiary->id, $beneficiary->last_name, $beneficiary->first_name, $beneficiary->barangay, 'Present'],
            ]))
            ->call('parseAttendanceImport')
            ->assertSet('attendanceStep', 'preview')
            ->call('confirmAttendanceImport')
            ->assertSet('recordsActivityId', null);

        $attendance = $activity->attendances()->where('beneficiary_id', $beneficiary->id)->first();
        $this->assertSame('present', $attendance->status);
        $this->assertSame('2026-03-01', $attendance->attendance_date->format('Y-m-d'));
    }

    /* ==================== separation of duties ==================== */

    public function test_secretary_cannot_use_admin_only_hub_actions(): void
    {
        $data = $this->seedBase();

        foreach (['openProgramEdit', 'newObjective', 'openActivityForm', 'openBudgetForm'] as $action) {
            Livewire::actingAs($data['secretary'])
                ->test(Hub::class, ['project' => $data['program']])
                ->call($action)
                ->assertForbidden();
        }
    }

    public function test_faculty_cannot_manage_beneficiaries(): void
    {
        $data = $this->seedBase();
        $beneficiary = $this->beneficiary($data['program'], $data['community']);

        $cases = [
            ['openRegister', []],
            ['checkDedup', []],
            ['archiveBeneficiary', [$beneficiary->id]],
            ['openImport', []],
            ['parseAttendanceImport', []],
            ['confirmAttendanceImport', []],
            ['parseEvaluationImport', []],
            ['confirmEvaluationImport', []],
        ];

        foreach ($cases as [$action, $args]) {
            Livewire::actingAs($data['facultyUser'])
                ->test(Hub::class, ['project' => $data['program']])
                ->call($action, ...$args)
                ->assertForbidden();
        }
    }

    /* ==================== register dedup (5.4) ==================== */

    public function test_register_new_warns_on_duplicate_and_requires_confirmation(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community']);

        $test = Livewire::actingAs($data['secretary'])->test(Hub::class, ['project' => $data['program']]);

        // Submitting a duplicate holds for confirmation — nothing is created yet.
        $test->call('openRegister')
            ->set('registerForm.first_name', 'Juan')
            ->set('registerForm.last_name', 'Dela Cruz')
            ->set('registerForm.age', '35')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertSet('showRegister', true)
            ->assertSee('Possible duplicate found')
            ->assertSee('Juan Dela Cruz');
        $this->assertSame(1, Beneficiary::count());

        $test->call('registerConfirmedSave')->assertSet('showRegister', false);
        $this->assertSame(2, Beneficiary::count());
        $this->assertSame(2, $data['program']->beneficiaries()->count());
    }

    public function test_register_new_unique_name_saves_directly(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Maria')
            ->set('registerForm.last_name', 'Santos')
            ->set('registerForm.age', '35')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Housewife')
            ->call('saveRegister')
            ->assertSet('showRegister', false)
            ->assertDontSee('Possible duplicate found');

        $this->assertSame(1, Beneficiary::count());
        $this->assertSame(1, $data['program']->beneficiaries()->count());
    }

    public function test_register_new_trims_input_and_dedup_matches_despite_padding(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community']);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', '  Juan  ')
            ->set('registerForm.last_name', ' Dela Cruz ')
            ->set('registerForm.age', '35')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertSee('Possible duplicate found')
            ->call('registerConfirmedSave');

        $this->assertSame(2, Beneficiary::count());
        $created = Beneficiary::orderByDesc('id')->first();
        $this->assertSame('Juan', $created->first_name);
        $this->assertSame('Dela Cruz', $created->last_name);
        $this->assertSame('San Jose', $created->barangay);
    }

    public function test_editing_form_after_duplicate_warning_rechecks_before_confirmed_save(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community'], ['first_name' => 'Juan']);
        $this->beneficiary($data['program'], $data['community'], ['first_name' => 'Pedro', 'last_name' => 'Ramos']);

        $test = Livewire::actingAs($data['secretary'])->test(Hub::class, ['project' => $data['program']]);
        $test->call('openRegister')
            ->set('registerForm.first_name', 'Juan')
            ->set('registerForm.last_name', 'Dela Cruz')
            ->set('registerForm.age', '35')
            ->set('registerForm.community_id', $data['community']->id)
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertSee('Possible duplicate found');

        $test->set('registerForm.first_name', 'Pedro')
            ->set('registerForm.last_name', 'Ramos')
            ->call('registerConfirmedSave')
            ->assertSet('showRegister', true)
            ->assertSee('Possible duplicate found');

        $this->assertSame(2, Beneficiary::count());
    }

    /* ==================== /beneficiaries page ==================== */

    public function test_beneficiaries_page_is_admin_and_secretary_only(): void
    {
        $data = $this->seedBase();

        $this->get('/beneficiaries')->assertRedirect('/login');

        $this->actingAs($data['secretary'])->get('/beneficiaries')->assertOk();
        $this->actingAs($data['admin'])->get('/beneficiaries')->assertOk();
        $this->actingAs($data['facultyUser'])->get('/beneficiaries')->assertForbidden();
    }

    public function test_beneficiaries_page_lists_and_filters_records(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community']);

        $this->actingAs($data['secretary'])
            ->get('/beneficiaries')
            ->assertOk()
            ->assertSee('Juan Dela Cruz')
            ->assertSee('San Jose')
            ->assertSee('EXT-2026-010')
            ->assertSee('Add beneficiary')
            ->assertSee('Import beneficiaries');

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->set('search', 'Nonexistent Person')
            ->assertDontSee('Juan Dela Cruz');
    }

    public function test_hub_tab_renders_the_project_roster(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community'], ['phone' => null]);

        $this->actingAs($data['secretary'])
            ->get(route('projects.show', ['project' => $data['program'], 'tab' => 'beneficiaries']))
            ->assertOk()
            ->assertSee('Beneficiaries in this project')
            ->assertSee('Add beneficiary')
            ->assertSee('Export PDF')
            ->assertSee('No contact provided')
            ->assertDontSee('Enroll existing');
    }

    /* ==================== registry page actions ==================== */

    public function test_registry_page_creates_a_project_owned_beneficiary(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openCreate')
            ->set('form.first_name', 'Nico')
            ->set('form.last_name', 'Alegre')
            ->set('form.age', '30')
            ->set('form.gender', 'Male')
            ->set('form.community_id', $data['community']->id)
            ->set('form.extension_project_id', $data['program']->id)
            ->set('form.beneficiary_category', 'Farmer')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $beneficiary = Beneficiary::sole();
        $this->assertSame($data['program']->id, $beneficiary->extension_project_id);
        $this->assertSame($data['community']->id, $beneficiary->community_id);
        $this->assertNull($beneficiary->phone);
    }

    public function test_registry_page_rejects_a_community_outside_the_project(): void
    {
        $data = $this->seedBase();
        $outside = Community::factory()->create(['name' => 'Not Linked']);

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openCreate')
            ->set('form.first_name', 'Nico')
            ->set('form.last_name', 'Alegre')
            ->set('form.age', '30')
            ->set('form.extension_project_id', $data['program']->id)
            ->set('form.community_id', $outside->id)
            ->set('form.beneficiary_category', 'Farmer')
            ->call('save')
            ->assertHasErrors('form.community_id');

        $this->assertSame(0, Beneficiary::count());
    }

    public function test_registry_page_archives_and_restores_with_history(): void
    {
        $data = $this->seedBase();
        $beneficiary = $this->beneficiary($data['program'], $data['community']);

        $activity = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Session',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'completed',
        ]);
        $activity->attendances()->create([
            'beneficiary_id' => $beneficiary->id,
            'attendance_date' => '2026-03-01',
            'status' => 'present',
        ]);

        Livewire::actingAs($data['admin'])
            ->test(BeneficiariesIndex::class)
            ->call('archive', $beneficiary->id);

        $this->assertSoftDeleted($beneficiary);
        $this->assertSame(1, $beneficiary->fresh()->attendances()->count());

        Livewire::actingAs($data['admin'])
            ->test(BeneficiariesIndex::class)
            ->set('status', 'archived')
            ->call('restore', $beneficiary->id);

        $this->assertFalse($beneficiary->fresh()->trashed());
        $this->assertSame(1, $beneficiary->fresh()->attendances()->count());
    }

    public function test_registry_form_resets_the_community_when_the_project_changes(): void
    {
        $data = $this->seedBase();

        $otherCommunity = Community::factory()->create(['name' => 'Brgy. Other']);
        $otherProject = ExtensionProject::create([
            'code' => 'EXT-2026-012',
            'title' => 'Other Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $otherProject->communities()->attach($otherCommunity->id);

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openCreate')
            ->set('form.extension_project_id', $otherProject->id)
            ->assertSet('form.community_id', $otherCommunity->id);
    }

    public function test_registry_edit_keeps_the_assigned_project_even_if_tampered(): void
    {
        $data = $this->seedBase();
        $beneficiary = $this->beneficiary($data['program'], $data['community']);

        $otherProject = ExtensionProject::create([
            'code' => 'EXT-2026-013',
            'title' => 'Other Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $otherProject->communities()->attach(Community::factory()->create(['name' => 'Brgy. Elsewhere'])->id);

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openEdit', $beneficiary->id)
            ->set('form.extension_project_id', $otherProject->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($data['program']->id, $beneficiary->fresh()->extension_project_id);
        $this->assertSame($data['community']->id, $beneficiary->fresh()->community_id);
    }

    public function test_registry_edit_rejects_a_community_from_another_project(): void
    {
        $data = $this->seedBase();
        $beneficiary = $this->beneficiary($data['program'], $data['community']);
        $outside = Community::factory()->create(['name' => 'Brgy. Elsewhere']);

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openEdit', $beneficiary->id)
            ->set('form.community_id', $outside->id)
            ->call('save')
            ->assertHasErrors('form.community_id');

        $this->assertSame($data['community']->id, $beneficiary->fresh()->community_id);
    }

    public function test_registry_page_offers_template_and_export_actions(): void
    {
        $data = $this->seedBase();

        $this->actingAs($data['secretary'])
            ->get('/beneficiaries')
            ->assertOk()
            ->assertSee('Download template')
            ->assertSee('Export PDF');
    }

    public function test_registry_export_uses_the_chosen_project(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openExport')
            ->assertSet('showExport', true)
            ->set('exportProjectId', (string) $data['program']->id)
            ->call('exportRoster')
            ->assertRedirect(route('beneficiaries.export', ['project' => $data['program'], 'download' => 1]));
    }

    public function test_registry_export_requires_a_project(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openExport')
            ->set('exportProjectId', '')
            ->call('exportRoster')
            ->assertHasErrors('exportProjectId');
    }

    public function test_registry_trims_names_before_saving_and_deduping(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community'], [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
        ]);

        // The padded duplicate still matches the stored person.
        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openCreate')
            ->set('form.first_name', '  Maria  ')
            ->set('form.last_name', ' Santos ')
            ->set('form.age', '35')
            ->set('form.community_id', $data['community']->id)
            ->set('form.extension_project_id', $data['program']->id)
            ->set('form.beneficiary_category', 'Parent')
            ->call('save')
            ->assertHasErrors('form.first_name');

        $this->assertSame(1, Beneficiary::count());

        // A unique padded name is stored without the padding.
        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openCreate')
            ->set('form.first_name', '  Ana  ')
            ->set('form.last_name', ' Reyes ')
            ->set('form.age', '30')
            ->set('form.community_id', $data['community']->id)
            ->set('form.extension_project_id', $data['program']->id)
            ->set('form.beneficiary_category', 'Vendor')
            ->call('save')
            ->assertHasNoErrors();

        $ana = Beneficiary::where('first_name', 'Ana')->sole();
        $this->assertSame('Ana', $ana->first_name);
        $this->assertSame('Reyes', $ana->last_name);
    }

    public function test_registry_rejects_age_zero(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openCreate')
            ->set('form.first_name', 'Zero')
            ->set('form.last_name', 'Age')
            ->set('form.age', '0')
            ->set('form.community_id', $data['community']->id)
            ->set('form.extension_project_id', $data['program']->id)
            ->set('form.beneficiary_category', 'Parent')
            ->call('save')
            ->assertHasErrors('form.age');

        $this->assertSame(0, Beneficiary::count());
    }

    public function test_registry_project_code_links_to_the_hub(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community']);

        $this->actingAs($data['secretary'])
            ->get('/beneficiaries')
            ->assertOk()
            ->assertSee(route('projects.show', $data['program']), false);
    }

    public function test_registry_filters_unassigned_records_and_warns_about_them(): void
    {
        $data = $this->seedBase();
        $this->beneficiary($data['program'], $data['community'], ['first_name' => 'Assigned']);
        $unassigned = $this->beneficiary($data['program'], $data['community'], [
            'first_name' => 'Unassigned',
            'extension_project_id' => null,
        ]);

        $this->actingAs($data['secretary'])
            ->get('/beneficiaries')
            ->assertOk()
            ->assertSee('no project')
            ->assertSee('Review unassigned');

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->set('projectId', 'unassigned')
            ->assertViewHas('beneficiaries', fn ($page) => $page->total() === 1 && $page->first()->id === $unassigned->id)
            ->call('openImport')
            ->assertSet('importProjectId', '');
    }

    public function test_registry_page_keeps_editing_secretary_only(): void
    {
        $data = $this->seedBase();
        $beneficiary = $this->beneficiary($data['program'], $data['community']);

        Livewire::actingAs($data['admin'])
            ->test(BeneficiariesIndex::class)
            ->call('openEdit', $beneficiary->id)
            ->assertForbidden();

        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->call('openEdit', $beneficiary->id)
            ->set('form.phone', '0917 555 1234')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('0917 555 1234', $beneficiary->fresh()->phone);
    }
}
