<?php

namespace Tests\Feature;

use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The real PDF output (mPDF) for the four reports, and its download mode.
 */
class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function project(): ExtensionProject
    {
        return ExtensionProject::create([
            'code' => 'PDF-2026-001',
            'title' => 'PDF Report Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
            'annual_target_hours' => 50,
        ]);
    }

    public function test_each_report_streams_a_real_pdf(): void
    {
        $project = $this->project();

        $urls = [
            '/reports/annual?year=2026&pdf=1',
            '/reports/community-impact?year=2026&pdf=1',
            '/reports/rendered-hours?year=2026&pdf=1',
            '/reports/results-framework/'.$project->id.'?pdf=1',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->admin())->get($url);

            $response->assertOk();
            $response->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent(), "{$url} did not return a PDF");
        }
    }

    public function test_a_download_request_is_an_attachment(): void
    {
        $this->project();

        $response = $this->actingAs($this->admin())
            ->get('/reports/annual?year=2026&pdf=1&download=1')
            ->assertOk();

        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('content-disposition'),
        );
        $this->assertStringContainsString(
            'annual-extension-performance-2026.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_only_an_admin_can_download_a_pdf(): void
    {
        $this->project();

        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);
        $faculty = User::factory()->create(['role' => User::ROLE_FACULTY]);

        $this->actingAs($secretary)->get('/reports/annual?pdf=1')->assertForbidden();
        $this->actingAs($faculty)->get('/reports/annual?pdf=1')->assertForbidden();
    }

    public function test_the_workspace_offers_the_pdf_preview_and_download(): void
    {
        $this->project();

        $this->actingAs($this->admin())
            ->get('/reports')
            ->assertOk()
            ->assertSee('Download PDF')
            ->assertSee('A4 landscape PDF');
    }
}
