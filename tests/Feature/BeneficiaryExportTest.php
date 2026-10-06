<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Beneficiary;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use App\Services\BeneficiaryTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Beneficiary export + the beneficiary tab's 2026-10-05 changes.
 *
 * Owner request: export the enrolled list as XLSX **using the same template** as
 * the import, hide the "Enroll existing" action, reword the panel, and paginate
 * the table.
 *
 * The export and the blank template are built by ONE method
 * (`BeneficiaryTemplate::build()`), so the two cannot drift — which is what most
 * of these tests are really pinning. The export is deliberately round-trippable:
 * download the list, edit it in Excel, import it back.
 */
class BeneficiaryExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function program(): ExtensionProject
    {
        return ExtensionProject::create([
            'code' => 'EXT-2026-001',
            'title' => 'Beneficiary Export Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);
    }

    private function beneficiary(string $first, string $last, string $barangay = 'San Jose'): Beneficiary
    {
        return Beneficiary::create([
            'first_name' => $first,
            'last_name' => $last,
            'age' => 40,
            'gender' => 'Male',
            'phone' => '09123456789',
            'barangay' => $barangay,
            'municipality' => 'Tacloban City',
            'beneficiary_category' => 'Farmer',
        ]);
    }

    /** Read a downloaded workbook the way the other template tests do. */
    protected function loadStreamedSheet($response)
    {
        ob_start();
        $response->baseResponse->sendContent();
        $content = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'sc_export_').'.xlsx';
        file_put_contents($path, $content);
        $sheet = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $sheet;
    }

    /* ==================== the export ==================== */

    public function test_the_export_carries_the_import_templates_headers(): void
    {
        $admin = $this->admin();
        $program = $this->program();

        $response = $this->actingAs($admin)->get(route('beneficiaries.export', $program));

        $response->assertOk();
        $this->assertStringContainsString('beneficiaries-ext-2026-001.xlsx', $response->headers->get('content-disposition'));

        $sheet = $this->loadStreamedSheet($response);

        // The SAME sheet shape as the import template: headers on row 5.
        // `array_values` because `headers()` is keyed by field name (a quirk
        // `fromArray` ignores), while a sheet range reads back as a plain list.
        $this->assertSame(
            array_values(BeneficiaryTemplate::headers()),
            $sheet->rangeToArray('A5:I5', null, true, false)[0]
        );
    }

    public function test_the_export_lists_the_programs_enrolled_beneficiaries(): void
    {
        $admin = $this->admin();
        $program = $this->program();

        // Deliberately enrolled out of alphabetical order, so the export's own
        // ordering (last name, then first name) is what is asserted.
        $program->beneficiaries()->attach($this->beneficiary('Maria', 'Santos')->id);
        $program->beneficiaries()->attach($this->beneficiary('Juan', 'Dela Cruz')->id);

        $sheet = $this->loadStreamedSheet(
            $this->actingAs($admin)->get(route('beneficiaries.export', $program))
        );

        $this->assertSame(
            ['Dela Cruz', 'Juan'],
            [$sheet->getCell('C6')->getValue(), $sheet->getCell('A6')->getValue()],
            'Rows are ordered by last name, then first name — the same order the hub table uses.'
        );

        $this->assertSame('Santos', $sheet->getCell('C7')->getValue());
        $this->assertNull($sheet->getCell('A8')->getValue(), 'Only the enrolled rows are written.');
    }

    public function test_the_export_does_not_carry_the_blank_templates_example_row(): void
    {
        $admin = $this->admin();
        $program = $this->program();
        $program->beneficiaries()->attach($this->beneficiary('Maria', 'Santos')->id);

        $sheet = $this->loadStreamedSheet(
            $this->actingAs($admin)->get(route('beneficiaries.export', $program))
        );

        // 'Dela Cruz' is the blank template's grey example; it must not leak in.
        $this->assertNotSame('Dela Cruz', $sheet->getCell('C6')->getValue());
    }

    public function test_the_blank_template_still_carries_the_example_row(): void
    {
        // Regression guard for the refactor that extracted build(): pulling the
        // layout into a shared method must not have removed the encoder's
        // example row from the BLANK template.
        $sheet = $this->loadStreamedSheet(
            $this->actingAs($this->admin())->get(route('beneficiaries.template'))
        );

        $this->assertSame(array_values(BeneficiaryTemplate::headers()), $sheet->rangeToArray('A5:I5', null, true, false)[0]);
        $this->assertSame('Dela Cruz', $sheet->getCell('C6')->getValue());
    }

    /* ==================== access ==================== */

    public function test_the_secretary_can_export(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);
        $program = $this->program();

        $this->actingAs($secretary)
            ->get(route('beneficiaries.export', $program))
            ->assertOk();
    }

    public function test_faculty_cannot_export(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $program = $this->program();

        $this->actingAs($faculty)
            ->get(route('beneficiaries.export', $program))
            ->assertForbidden();
    }

    /* ==================== the tab ==================== */

    public function test_the_tab_hides_the_enroll_existing_action(): void
    {
        $program = $this->program();

        $html = $this->actingAs($this->admin())
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        // The BUTTON is what was hidden (owner: "just hide the button"). Asserting
        // the trigger, not the string: the enroll modal itself is still in the
        // DOM — it is an always-rendered Alpine `x-cloak` panel, so its heading
        // ("Enroll existing beneficiary") remains in the markup by design.
        $this->assertStringNotContainsString('wire:click="openEnroll"', $html);

        $this->assertStringContainsString('Register new', $html);
        $this->assertStringContainsString('Import XLSX', $html);
        $this->assertStringContainsString('Export XLSX', $html);
    }

    public function test_the_tab_uses_the_new_manage_beneficiaries_wording(): void
    {
        $program = $this->program();

        $html = $this->actingAs($this->admin())
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Manage Beneficiaries', $html);
        $this->assertStringContainsString('Import or export the list of beneficiaries', $html);

        // The old heading is gone.
        $this->assertStringNotContainsString('Enrollment is program-scoped', $html);
    }

    public function test_the_tab_links_to_the_export_for_managers_only(): void
    {
        $program = $this->program();
        $url = route('beneficiaries.export', $program);

        $adminHtml = $this->actingAs($this->admin())
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($url, $adminHtml);

        // A faculty viewer sees the tab read-only, so the link must not render
        // — otherwise they get a link the route 403s (§29). The faculty has to
        // LEAD the project, or `ProgramPolicy::view()` refuses the hub outright
        // and this would prove nothing about the link.
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'employee_id' => 'LNU-2026-0011']);
        $program->update(['program_lead_id' => $faculty->id]);

        $facultyHtml = $this->actingAs($facultyUser)
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($url, $facultyHtml);
    }

    /* ==================== pagination ==================== */

    public function test_the_enrolled_table_paginates_at_ten(): void
    {
        $admin = $this->admin();
        $program = $this->program();

        for ($i = 1; $i <= 12; $i++) {
            $program->beneficiaries()->attach(
                $this->beneficiary('Person', sprintf('Number%02d', $i))->id
            );
        }

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->assertViewHas('enrolledPaginator', fn ($p) => $p->count() === 10 && $p->total() === 12)
            ->assertViewHas('enrolledCount', 12)
            // Its OWN page name, so it cannot collide with another paginator.
            ->assertSet('paginators.beneficiariesPage', 1);
    }

    public function test_the_enrolled_count_reflects_the_whole_list_not_the_page(): void
    {
        $admin = $this->admin();
        $program = $this->program();

        for ($i = 1; $i <= 12; $i++) {
            $program->beneficiaries()->attach($this->beneficiary('Person', sprintf('Number%02d', $i))->id);
        }

        $html = $this->actingAs($admin)
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        // The tile and the table badge count the LIST, not the visible page.
        $this->assertStringContainsString('12 enrolled', $html);
        $this->assertStringContainsString('of 12', $html);
    }
}
