<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use App\Services\BeneficiaryTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

/**
 * The v2 beneficiary import (2026-11-20).
 *
 * v2 replaces free-text Barangay/Municipality with one Community / Partner
 * School cell. Rows update an existing person in the same project, create new
 * people, and skip errors or people owned by another project.
 */
class BeneficiaryImportTest extends TestCase
{
    use RefreshDatabase;

    protected ?Community $community = null;

    protected ?Community $secondCommunity = null;

    /** Build a real XLSX with the official v2 header row + data rows. */
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

    protected function programFor(User $admin): ExtensionProject
    {
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-001',
            'title' => 'Import Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);

        $this->community = Community::factory()->create(['name' => 'Brgy. San Jose']);
        $this->secondCommunity = Community::factory()->create(['name' => 'Brgy. Sagkahan']);

        $program->communities()->attach([$this->community->id, $this->secondCommunity->id]);

        return $program;
    }

    /* ==================== template download ==================== */

    public function test_template_downloads_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/beneficiaries/template');

        $response->assertOk();
        $this->assertStringContainsString('beneficiary-import-template-v2', $response->headers->get('content-disposition'));
    }

    public function test_template_downloads_for_secretary(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);

        $this->actingAs($secretary)
            ->get('/beneficiaries/template')
            ->assertOk();
    }

    public function test_template_blocked_for_faculty(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)->get('/beneficiaries/template')->assertForbidden();
    }

    /* ==================== import flow ==================== */

    public function test_import_creates_beneficiaries_owned_by_the_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx([
            ['Juan', 'Reyes', 'Dela Cruz', '42', 'Male', '09171234567', 'Brgy. San Jose', 'Farmer'],
            ['Maria', '', 'Santos', '35', 'Female', '', 'Brgy. Sagkahan', 'Housewife'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
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
        $this->assertSame($program->id, $juan->extension_project_id);
        $this->assertSame($this->community->id, $juan->community_id);

        // Blank contact stays blank; the old demo number is gone.
        $maria = Beneficiary::where('first_name', 'Maria')->first();
        $this->assertNull($maria->phone);
    }

    public function test_import_updates_an_existing_person_and_skips_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $pedro = Beneficiary::create([
            'first_name' => 'Pedro', 'last_name' => 'Cruz',
            'barangay' => $this->community->name,
            'community_id' => $this->community->id,
            'extension_project_id' => $program->id,
            'beneficiary_category' => 'Farmer', 'phone' => '09170000000', 'age' => 49,
        ]);

        $file = $this->makeXlsx([
            // same person in this project → update, blank phone keeps the saved one
            ['Pedro', '', 'Cruz', '50', 'Male', '', 'Brgy. San Jose', 'Farmer'],
            // missing last name → required error → skipped
            ['Ana', '', '', '28', 'Female', '', 'Brgy. Sagkahan', 'Vendor'],
            // valid → creates
            ['Liza', '', 'Bautista', '31', 'Female', '', 'Brgy. Sagkahan', 'Vendor'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            ->assertSet('importRows.0.status', 'update')
            ->assertSet('importRows.1.status', 'error')
            ->assertSet('importRows.2.status', 'new')
            ->call('confirmImport');

        $this->assertSame(2, Beneficiary::count());
        $this->assertSame('09170000000', $pedro->fresh()->phone, 'A blank import cell must not erase the saved number.');
        $this->assertSame(50, $pedro->fresh()->age);
        $this->assertTrue($program->beneficiaries()->where('first_name', 'Liza')->exists());
    }

    public function test_import_skips_a_person_owned_by_another_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $other = ExtensionProject::create([
            'code' => 'EXT-2026-002',
            'title' => 'Other Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);
        $other->communities()->attach($this->community->id);

        Beneficiary::create([
            'first_name' => 'Pedro', 'last_name' => 'Cruz',
            'barangay' => $this->community->name,
            'community_id' => $this->community->id,
            'extension_project_id' => $other->id,
            'beneficiary_category' => 'Farmer',
        ]);

        $file = $this->makeXlsx([
            ['Pedro', '', 'Cruz', '50', 'Male', '', 'Brgy. San Jose', 'Farmer'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importRows.0.status', 'other_project')
            ->call('confirmImport');

        $this->assertSame(1, Beneficiary::count(), 'The other project’s record must not be duplicated or moved.');
        $this->assertSame($other->id, Beneficiary::sole()->extension_project_id);
    }

    public function test_import_maps_unknown_category_to_other(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx([
            ['Nena', '', 'Villar', '44', 'Female', '', 'Brgy. San Jose', 'Teacher'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', true)
            // Row is importable — category auto-maps to Other (D9).
            ->assertSet('importRows.0.data.beneficiary_category', 'Other')
            ->assertSet('importRows.0.category_other', 'Teacher')
            ->assertSet('importRows.0.data.phone', null);
    }

    public function test_import_flags_a_community_not_linked_to_the_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx([
            ['Nena', '', 'Villar', '44', 'Female', '', 'Brgy. Not Linked', 'Farmer'],
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importRows.0.status', 'error')
            ->assertSet('importRows.0.errors.community', 'This community/partner school is not linked to the project. Add the link first.');
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
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertHasErrors('importFile');

        $this->assertSame(0, Beneficiary::count());
    }

    public function test_import_rejects_the_old_v1_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        $file = $this->makeXlsx(
            [['Juan', '', 'Dela Cruz', '42', 'Male', '0917', 'San Jose', 'Tacloban City', 'Farmer']],
            ['First Name', 'Middle Name', 'Last Name', 'Age', 'Sex', 'Contact Number', 'Barangay', 'Municipality / City', 'Beneficiary Category'],
        );

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->set('importFile', $file)
            ->call('parseImport')
            ->assertSet('importPreview', false)
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
            ->test(Hub::class, ['project' => $program])
            ->call('openImport')
            ->assertForbidden();
    }

    /* ==================== contact number ==================== */

    public function test_register_form_saves_contact_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Carlo')
            ->set('registerForm.last_name', 'Magbanua')
            ->set('registerForm.community_id', $this->community->id)
            ->set('registerForm.age', '35')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->set('registerForm.phone', '09181112222')
            ->call('saveRegister')
            ->assertSet('showRegister', false);

        $this->assertSame('09181112222', Beneficiary::first()->phone);
    }

    public function test_register_form_leaves_a_blank_contact_blank(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Ada')
            ->set('registerForm.last_name', 'Lorente')
            ->set('registerForm.community_id', $this->community->id)
            ->set('registerForm.age', '35')
            ->set('registerForm.beneficiary_category', 'Vendor')
            ->set('registerForm.phone', '')
            ->call('saveRegister');

        $this->assertNull(Beneficiary::first()->phone);
    }

    public function test_register_form_rejects_letters_in_the_contact_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programFor($admin);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Ada')
            ->set('registerForm.last_name', 'Lorente')
            ->set('registerForm.community_id', $this->community->id)
            ->set('registerForm.age', '35')
            ->set('registerForm.beneficiary_category', 'Vendor')
            ->set('registerForm.phone', 'call me')
            ->call('saveRegister')
            ->assertHasErrors('registerForm.phone');

        $this->assertSame(0, Beneficiary::count());
    }

    public function test_seeded_beneficiaries_do_not_use_the_demo_placeholder(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Beneficiary::count());
        $this->assertSame(0, Beneficiary::where('phone', '09123456789')->count(), 'The demo placeholder must not come back.');
        $this->assertSame(0, Beneficiary::whereNull('extension_project_id')->count(), 'Every seeded beneficiary must belong to one project.');
        $this->assertSame(0, Beneficiary::whereNull('community_id')->count(), 'Every seeded beneficiary must belong to one community.');
    }
}
