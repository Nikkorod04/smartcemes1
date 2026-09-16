<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Beneficiary;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\User;
use App\Services\BeneficiaryTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

class BeneficiaryImportTest extends TestCase
{
    use RefreshDatabase;

    /** Build a real XLSX with the official header row + data rows. */
    protected function makeXlsx(array $rows, ?array $headers = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers ?? BeneficiaryTemplate::headers(), null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'sc_ben_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('beneficiaries.xlsx', $contents);
    }

    protected function programFor(User $admin): ExtensionProgram
    {
        return ExtensionProgram::create([
            'code' => 'EXT-2026-001',
            'title' => 'Import Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);
    }

    /* ==================== template download ==================== */

    public function test_template_downloads_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/beneficiaries/template');

        $response->assertOk();
        $this->assertStringContainsString('beneficiary-import-template', $response->headers->get('content-disposition'));
    }

    public function test_template_blocked_for_faculty(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)->get('/beneficiaries/template')->assertForbidden();
    }

    /* ==================== import flow ==================== */

    public function test_import_creates_beneficiaries_and_enrolls_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx([
            ['Juan', 'Reyes', 'Dela Cruz', '42', 'Male', '09171234567', 'San Jose', 'Tacloban City', 'Farmer'],
            ['Maria', '', 'Santos', '35', 'Female', '', 'Sagkahan', 'Tacloban City', 'Housewife'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openImport')
            ->assertSet('showImport', true)
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            ->assertCount('importRows', 2)
            ->call('confirmImport')
            ->assertSet('showImport', false);

        $this->assertSame(2, Beneficiary::count());
        $this->assertSame(2, $program->beneficiaries()->count());

        $juan = Beneficiary::where('first_name', 'Juan')->first();
        $this->assertSame('09171234567', $juan->phone);
        $this->assertSame('Dela Cruz', $juan->last_name);
        $this->assertSame(42, $juan->age);

        // Blank contact number falls back to the demo default.
        $maria = Beneficiary::where('first_name', 'Maria')->first();
        $this->assertSame('09123456789', $maria->phone);
    }

    public function test_import_skips_duplicates_and_rows_with_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        Beneficiary::create([
            'first_name' => 'Pedro', 'last_name' => 'Cruz', 'barangay' => 'San Jose',
            'beneficiary_category' => 'Farmer', 'phone' => '09123456789',
        ]);

        $file = $this->makeXlsx([
            // duplicate of the registry row above (5.4: first + last + barangay)
            ['Pedro', '', 'Cruz', '50', 'Male', '', 'San Jose', 'Tacloban City', 'Farmer'],
            // missing last name → required error → skipped
            ['Ana', '', '', '28', 'Female', '', 'Sagkahan', 'Tacloban City', 'Vendor'],
            // valid
            ['Liza', '', 'Bautista', '31', 'Female', '', 'Sagkahan', 'Tacloban City', 'Vendor'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            ->call('confirmImport');

        $this->assertSame(2, Beneficiary::count());
        $this->assertSame(1, $program->beneficiaries()->count());
        $this->assertTrue($program->beneficiaries()->where('first_name', 'Liza')->exists());
    }

    public function test_import_maps_unknown_category_to_other(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx([
            ['Nena', '', 'Villar', '44', 'Female', '', 'San Jose', 'Tacloban City', 'Teacher'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            // Row is importable — category auto-maps to Other (D9).
            ->assertSet('importRows.0.data.beneficiary_category', 'Other')
            ->assertSet('importRows.0.category_other', 'Teacher')
            ->assertSet('importRows.0.data.phone', '09123456789');
    }

    public function test_import_rejects_files_without_template_headers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx(
            [['Juan', 'Dela Cruz']],
            ['Given Name', 'Surname']
        );

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertHasErrors('importFile');

        $this->assertSame(0, Beneficiary::count());
    }

    public function test_faculty_lead_cannot_import(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0009']);
        $program = $this->programFor($user);
        $program->update(['program_lead_id' => $faculty->id]);

        Livewire::actingAs($user)
            ->test(Hub::class, ['program' => $program])
            ->call('openImport')
            ->assertForbidden();
    }

    /* ==================== contact number ==================== */

    public function test_register_form_saves_contact_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Carlo')
            ->set('registerForm.last_name', 'Magbanua')
            ->set('registerForm.barangay', 'San Jose')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->set('registerForm.phone', '09181112222')
            ->call('saveRegister')
            ->assertSet('showRegister', false);

        $b = Beneficiary::first();
        $this->assertSame('09181112222', $b->phone);
    }

    public function test_register_form_defaults_contact_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Ada')
            ->set('registerForm.last_name', 'Lorente')
            ->set('registerForm.barangay', 'San Jose')
            ->set('registerForm.beneficiary_category', 'Vendor')
            ->set('registerForm.phone', '')
            ->call('saveRegister');

        $this->assertSame('09123456789', Beneficiary::first()->phone);
    }

    public function test_seeded_beneficiaries_have_contact_number(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Beneficiary::count());
        $this->assertSame(0, Beneficiary::whereNull('phone')->orWhere('phone', '')->count());
        $this->assertSame(
            Beneficiary::count(),
            Beneficiary::where('phone', '09123456789')->count()
        );
    }
}
