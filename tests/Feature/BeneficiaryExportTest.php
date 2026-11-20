<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Per-project beneficiary roster export (2026-11-20).
 *
 * The XLSX export is replaced by an A4 portrait PDF that carries the university
 * seal, the project information and each person's contact number. Attendance is
 * deliberately absent; the signature block is blank "Prepared by" / "Noted by".
 */
class BeneficiaryExportTest extends TestCase
{
    use RefreshDatabase;

    protected ?Community $community = null;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function program(): ExtensionProject
    {
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-001',
            'title' => 'Beneficiary Export Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'draft',
            'allocated_budget' => 10000,
        ]);

        $this->community = Community::factory()->create(['name' => 'Brgy. San Jose']);
        $program->communities()->attach($this->community->id);

        return $program;
    }

    private function beneficiary(
        ExtensionProject $program,
        string $first,
        string $last,
        ?string $phone = '09171234567',
        ?string $community = null,
    ): Beneficiary {
        $communityModel = $community === null ? $this->community : Community::factory()->create(['name' => $community]);

        if ($community !== null && ! $program->communities()->whereKey($communityModel->id)->exists()) {
            $program->communities()->attach($communityModel->id);
        }

        return Beneficiary::create([
            'first_name' => $first,
            'last_name' => $last,
            'age' => 40,
            'gender' => 'Male',
            'phone' => $phone,
            'barangay' => $communityModel->name,
            'municipality' => $communityModel->municipality,
            'province' => $communityModel->province,
            'community_id' => $communityModel->id,
            'extension_project_id' => $program->id,
            'beneficiary_category' => 'Farmer',
        ]);
    }

    /* ==================== the export ==================== */

    public function test_the_export_is_a_pdf_named_for_the_project(): void
    {
        $admin = $this->admin();
        $program = $this->program();
        $this->beneficiary($program, 'Maria', 'Santos');

        $response = $this->actingAs($admin)->get(route('beneficiaries.export', $program));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'beneficiaries-ext-2026-001.pdf',
            $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_rendered_roster_lists_people_by_last_name_with_contacts(): void
    {
        $program = $this->program();
        $this->beneficiary($program, 'Maria', 'Santos', '09170000002');
        $this->beneficiary($program, 'Juan', 'Dela Cruz', null);

        $html = view('reports.beneficiaries-pdf', [
            'project' => $program,
            'beneficiaries' => $program->beneficiaries()->with('community')->orderBy('last_name')->orderBy('first_name')->get(),
            'scopeLabel' => '2 active beneficiaries',
            'signatureMode' => 'beneficiary',
        ])->render();

        $this->assertStringContainsString('Beneficiary List', $html);
        $this->assertStringContainsString('EXT-2026-001', $html);
        $this->assertStringContainsString('Beneficiary Export Test Program', $html);
        $this->assertStringContainsString('Leyte Normal University', $html);

        // Order: Dela Cruz before Santos.
        $this->assertLessThan(strpos($html, 'Maria Santos'), strpos($html, 'Juan Dela Cruz'));

        // Contacts are shown; a blank one says so instead of a fake number.
        $this->assertStringContainsString('09170000002', $html);
        $this->assertStringContainsString('No contact provided', $html);
        $this->assertStringNotContainsString('09123456789', $html);

        // Attendance is intentionally not part of this roster.
        $this->assertStringNotContainsString('>Attendance<', $html);

        // Blank signature lines: Prepared by / Noted by.
        $this->assertStringContainsString('Prepared by', $html);
        $this->assertStringContainsString('Noted by', $html);
        $this->assertStringNotContainsString('Reviewed by', $html);
        $this->assertStringNotContainsString('Approved by', $html);
    }

    public function test_archived_beneficiaries_are_excluded_from_the_roster(): void
    {
        $admin = $this->admin();
        $program = $this->program();
        $archived = $this->beneficiary($program, 'Archived', 'Person');
        $active = $this->beneficiary($program, 'Active', 'Person');
        $archived->delete();

        $html = view('reports.beneficiaries-pdf', [
            'project' => $program,
            'beneficiaries' => $program->beneficiaries()->with('community')->orderBy('last_name')->get(),
            'scopeLabel' => '1 active beneficiary',
            'signatureMode' => 'beneficiary',
        ])->render();

        $this->assertStringContainsString('Active Person', $html);
        $this->assertStringNotContainsString('Archived Person', $html);
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

    public function test_the_tab_offers_add_import_and_pdf_export_only(): void
    {
        $program = $this->program();

        $html = $this->actingAs($this->admin())
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('wire:click="openEnroll"', $html);

        $this->assertStringContainsString('Add beneficiary', $html);
        $this->assertStringContainsString('Import beneficiaries', $html);
        $this->assertStringContainsString('Export PDF', $html);
        $this->assertStringContainsString('All beneficiaries', $html);
        $this->assertStringNotContainsString('Export XLSX', $html);
    }

    public function test_the_tab_uses_the_new_manage_beneficiaries_wording(): void
    {
        $program = $this->program();

        $html = $this->actingAs($this->admin())
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Manage Beneficiaries', $html);
        $this->assertStringContainsString('print the project roster as a PDF', $html);
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

        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'employee_id' => 'LNU-2026-0011']);
        $program->update(['program_lead_id' => $faculty->id]);

        $facultyHtml = $this->actingAs($facultyUser)
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($url, $facultyHtml);
    }

    /* ==================== search + pagination ==================== */

    public function test_the_enrolled_table_paginates_at_ten(): void
    {
        $admin = $this->admin();
        $program = $this->program();

        for ($i = 1; $i <= 12; $i++) {
            $this->beneficiary($program, 'Person', sprintf('Number%02d', $i));
        }

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->assertViewHas('enrolledPaginator', fn ($p) => $p->count() === 10 && $p->total() === 12)
            ->assertViewHas('enrolledCount', 12)
            ->assertSet('paginators.beneficiariesPage', 1);
    }

    public function test_the_roster_can_be_searched_and_filtered(): void
    {
        $admin = $this->admin();
        $program = $this->program();
        $this->beneficiary($program, 'Maria', 'Santos');
        $this->beneficiary($program, 'Juan', 'Dela Cruz', null, 'Brgy. Sagkahan');

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->set('beneficiarySearch', 'Santos')
            ->assertViewHas('enrolledPaginator', fn ($p) => $p->total() === 1 && $p->first()->last_name === 'Santos')
            ->set('beneficiarySearch', '')
            ->set('beneficiaryCommunityId', (string) $this->community->id)
            ->assertViewHas('enrolledPaginator', fn ($p) => $p->total() === 1 && $p->first()->last_name === 'Santos')
            ->set('beneficiaryCommunityId', '')
            ->set('beneficiaryCategory', 'Farmer')
            ->assertViewHas('enrolledPaginator', fn ($p) => $p->total() === 2)
            ->call('clearBeneficiaryFilters')
            ->assertSet('beneficiarySearch', '')
            ->assertSet('beneficiaryCommunityId', '')
            ->assertSet('beneficiaryCategory', '');
    }

    public function test_the_enrolled_count_reflects_the_whole_list_not_the_page(): void
    {
        $admin = $this->admin();
        $program = $this->program();

        for ($i = 1; $i <= 12; $i++) {
            $this->beneficiary($program, 'Person', sprintf('Number%02d', $i));
        }

        $html = $this->actingAs($admin)
            ->get('/projects/'.$program->id.'?tab=beneficiaries')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('12 active', $html);
        $this->assertStringContainsString('of 12', $html);
    }
}
