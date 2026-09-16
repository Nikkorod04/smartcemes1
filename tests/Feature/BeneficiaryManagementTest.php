<?php

namespace Tests\Feature;

use App\Livewire\Beneficiaries\Index as BeneficiariesIndex;
use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\ExtensionProgram;
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

        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-010',
            'title' => 'Beneficiary Management Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
            'program_lead_id' => $faculty->id,
        ]);

        return compact('admin', 'secretary', 'facultyUser', 'faculty', 'program');
    }

    protected function makeXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(BeneficiaryTemplate::headers(), null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'sc_ben_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('beneficiaries.xlsx', $contents);
    }

    protected function makeAttendanceXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(ActivityAttendanceTemplate::HEADERS, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'sc_att_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('attendance.xlsx', $contents);
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

    /* ==================== secretary hub powers ==================== */

    public function test_secretary_can_enroll_register_and_unenroll(): void
    {
        $data = $this->seedBase();
        $existing = Beneficiary::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
        ]);

        $test = Livewire::actingAs($data['secretary'])->test(Hub::class, ['program' => $data['program']]);

        // Enroll existing from the global registry.
        $test->call('openEnroll')->assertSet('showEnroll', true)
            ->call('enrollExisting', $existing->id);
        $this->assertSame(1, $data['program']->beneficiaries()->count());

        // Register new (dedup check, then confirmed save).
        $test->call('openRegister')->assertSet('showRegister', true)
            ->set('registerForm.first_name', 'Maria')
            ->set('registerForm.last_name', 'Santos')
            ->set('registerForm.barangay', 'Sagkahan')
            ->set('registerForm.beneficiary_category', 'Housewife')
            ->call('saveRegister');
        $this->assertSame(2, $data['program']->beneficiaries()->count());

        // Unenroll the registered beneficiary.
        $maria = Beneficiary::where('first_name', 'Maria')->first();
        $test->call('unenroll', $maria->id);
        $this->assertSame(1, $data['program']->beneficiaries()->count());
    }

    public function test_secretary_can_import_beneficiaries_from_xlsx(): void
    {
        $data = $this->seedBase();

        $file = $this->makeXlsx([
            ['Pedro', '', 'Ramos', '50', 'Male', '09171234567', 'San Jose', 'Tacloban City', 'Farmer'],
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['program' => $data['program']])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            ->call('confirmImport');

        $this->assertSame(1, Beneficiary::count());
        $this->assertSame(1, $data['program']->beneficiaries()->count());
    }

    public function test_secretary_can_import_activity_attendance(): void
    {
        $data = $this->seedBase();
        $activity = Activity::create([
            'extension_program_id' => $data['program']->id,
            'title' => 'Training Session',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'ongoing',
        ]);
        $beneficiary = Beneficiary::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
        ]);
        $data['program']->beneficiaries()->syncWithoutDetaching([$beneficiary->id]);

        // v4.13: attendance is import-only via the official XLSX template.
        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['program' => $data['program']])
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
                ->test(Hub::class, ['program' => $data['program']])
                ->call($action)
                ->assertForbidden();
        }
    }

    public function test_faculty_cannot_manage_beneficiaries(): void
    {
        $data = $this->seedBase();
        $beneficiary = Beneficiary::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
        ]);

        // Faculty passes view() as program lead, but must be refused writes.
        // One Testable per call — an aborted action invalidates the instance.
        $cases = [
            ['openEnroll', []],
            ['openRegister', []],
            ['checkDedup', []],
            ['unenroll', [$beneficiary->id]],
            ['openImport', []],
            ['parseAttendanceImport', []],
            ['confirmAttendanceImport', []],
            ['parseEvaluationImport', []],
            ['confirmEvaluationImport', []],
        ];

        foreach ($cases as [$action, $args]) {
            Livewire::actingAs($data['facultyUser'])
                ->test(Hub::class, ['program' => $data['program']])
                ->call($action, ...$args)
                ->assertForbidden();
        }
    }

    /* ==================== register dedup (5.4) ==================== */

    public function test_register_new_warns_on_duplicate_and_requires_confirmation(): void
    {
        $data = $this->seedBase();
        Beneficiary::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
        ]);

        $test = Livewire::actingAs($data['secretary'])->test(Hub::class, ['program' => $data['program']]);

        // Submitting a duplicate holds for confirmation — nothing is created yet.
        $test->call('openRegister')
            ->set('registerForm.first_name', 'Juan')
            ->set('registerForm.last_name', 'Dela Cruz')
            ->set('registerForm.barangay', 'San Jose')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertSet('showRegister', true)
            ->assertSee('Possible duplicate found')
            ->assertSee('Juan Dela Cruz');
        $this->assertSame(1, Beneficiary::count());

        // Confirming ("this is a different person") creates the record and enrolls it.
        $test->call('registerConfirmedSave')
            ->assertSet('showRegister', false);
        $this->assertSame(2, Beneficiary::count());
        $this->assertSame(1, $data['program']->beneficiaries()->count());
    }

    public function test_register_new_unique_name_saves_directly(): void
    {
        $data = $this->seedBase();

        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['program' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Maria')
            ->set('registerForm.last_name', 'Santos')
            ->set('registerForm.barangay', 'Sagkahan')
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
        Beneficiary::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
        ]);

        // Padded input still matches the registry entry…
        Livewire::actingAs($data['secretary'])
            ->test(Hub::class, ['program' => $data['program']])
            ->call('openRegister')
            ->set('registerForm.first_name', '  Juan  ')
            ->set('registerForm.last_name', ' Dela Cruz ')
            ->set('registerForm.barangay', ' San Jose ')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertSee('Possible duplicate found')
            ->call('registerConfirmedSave');
        $this->assertSame(2, Beneficiary::count());

        // …and the created record stores trimmed values.
        $created = Beneficiary::orderByDesc('id')->first();
        $this->assertSame('Juan', $created->first_name);
        $this->assertSame('Dela Cruz', $created->last_name);
        $this->assertSame('San Jose', $created->barangay);
    }

    public function test_editing_form_after_duplicate_warning_rechecks_before_confirmed_save(): void
    {
        $data = $this->seedBase();
        foreach ([['Juan', 'Dela Cruz'], ['Pedro', 'Ramos']] as [$first, $last]) {
            Beneficiary::create([
                'first_name' => $first, 'last_name' => $last,
                'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
            ]);
        }

        // First submit warns on Juan Dela Cruz…
        $test = Livewire::actingAs($data['secretary'])->test(Hub::class, ['program' => $data['program']]);
        $test->call('openRegister')
            ->set('registerForm.first_name', 'Juan')
            ->set('registerForm.last_name', 'Dela Cruz')
            ->set('registerForm.barangay', 'San Jose')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->call('saveRegister')
            ->assertSee('Possible duplicate found');

        // …then the user swaps in a DIFFERENT duplicate while the warning is
        // visible — "save anyway" must not bypass the new name's check.
        $test->set('registerForm.first_name', 'Pedro')
            ->set('registerForm.last_name', 'Ramos')
            ->call('registerConfirmedSave')
            ->assertSet('showRegister', true)
            ->assertSee('Possible duplicate found');

        $this->assertSame(2, Beneficiary::count());
    }

    /* ==================== /beneficiaries page ==================== */

    public function test_beneficiaries_page_is_secretary_only(): void
    {
        $data = $this->seedBase();

        // Guest check first — actingAs persists across requests in a test.
        $this->get('/beneficiaries')->assertRedirect('/login');

        $this->actingAs($data['secretary'])->get('/beneficiaries')->assertOk();
        $this->actingAs($data['admin'])->get('/beneficiaries')->assertForbidden();
        $this->actingAs($data['facultyUser'])->get('/beneficiaries')->assertForbidden();
    }

    public function test_beneficiaries_page_lists_programs_with_deep_links(): void
    {
        $data = $this->seedBase();
        $beneficiary = Beneficiary::create([
            'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
            'gender' => 'Male', 'barangay' => 'San Jose', 'beneficiary_category' => 'Farmer',
        ]);
        $data['program']->beneficiaries()->syncWithoutDetaching([$beneficiary->id]);

        $this->actingAs($data['secretary'])
            ->get('/beneficiaries')
            ->assertOk()
            ->assertSee('Beneficiary Management Program')
            ->assertSee('1 enrolled')
            ->assertSee('tab=beneficiaries')
            ->assertSee('tab=activities')
            ->assertSee('Manage Beneficiaries'); // sidebar nav item

        // Search filter narrows the list.
        Livewire::actingAs($data['secretary'])
            ->test(BeneficiariesIndex::class)
            ->set('search', 'Nonexistent Program')
            ->assertDontSee('Beneficiary Management Program');
    }

    public function test_hub_tab_deep_links_render_the_right_panel(): void
    {
        $data = $this->seedBase();

        $this->actingAs($data['secretary'])
            ->get(route('programs.show', ['program' => $data['program'], 'tab' => 'beneficiaries']))
            ->assertOk()
            ->assertSee('Enrolled Beneficiaries')
            ->assertSee('Enroll existing');

        $this->actingAs($data['secretary'])
            ->get(route('programs.show', ['program' => $data['program'], 'tab' => 'activities']))
            ->assertOk()
            ->assertSee('Program Activities');
    }
}
