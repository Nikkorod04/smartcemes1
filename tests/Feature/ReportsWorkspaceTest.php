<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The 2026-11-20 Reports workspace: four reports, a shared scope, an A4 print
 * layout, and the annual report restored as the flagship.
 */
class ReportsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function project(string $code = 'RPT-2026-001', array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => $code,
            'title' => 'Report Workspace Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 100000,
            'annual_target_hours' => 100,
        ], $overrides));
    }

    public function test_the_index_offers_the_four_reports_and_the_annual_flagship(): void
    {
        $this->project();

        $this->actingAs($this->admin())
            ->get('/reports')
            ->assertOk()
            ->assertSee('Annual Extension Performance')
            ->assertSee('Community Partner Impact')
            ->assertSee('Faculty Rendered Hours')
            ->assertSee('Project Performance')
            ->assertSee('Flagship')
            ->assertSee('Print / Save as PDF');
    }

    public function test_the_annual_report_survives_a_summary_whose_community_was_removed(): void
    {
        $admin = $this->admin();
        $this->project();

        $community = Community::factory()->create(['name' => 'Brgy. Removed']);

        NeedsAssessment::create([
            'community_id' => $community->id,
            'quarter' => 2,
            'year' => 2026,
            'uploaded_by' => $admin->id,
            'review_status' => 'validated',
            'respondent_first_name' => 'Test',
            'respondent_last_name' => 'Respondent',
        ]);

        $community->delete();

        $this->actingAs($admin)
            ->get('/reports/annual?year=2026')
            ->assertOk()
            ->assertDontSee('Brgy. Removed');
    }

    public function test_the_annual_report_filters_by_college(): void
    {
        $collegeA = College::create(['code' => 'CA', 'name' => 'College A', 'short_name' => 'CA', 'status' => 'active']);
        $collegeB = College::create(['code' => 'CB', 'name' => 'College B', 'short_name' => 'CB', 'status' => 'active']);

        $this->project('A-2026-001', ['college_id' => $collegeA->id]);
        $this->project('B-2026-001', ['college_id' => $collegeB->id]);

        $this->actingAs($this->admin())
            ->get('/reports/annual?year=2026&college='.$collegeA->id)
            ->assertOk()
            ->assertSee('A-2026-001')
            ->assertDontSee('B-2026-001');
    }

    public function test_community_impact_filters_to_one_community(): void
    {
        $alpha = Community::factory()->create(['name' => 'Brgy. Alpha']);
        $beta = Community::factory()->create(['name' => 'Brgy. Beta']);

        $project = $this->project();
        $project->communities()->attach([$alpha->id, $beta->id]);

        $this->actingAs($this->admin())
            ->get('/reports/community-impact?year=2026&community='.$alpha->id)
            ->assertOk()
            ->assertSee('Brgy. Alpha')
            ->assertDontSee('Brgy. Beta');
    }

    public function test_community_impact_omits_communities_with_no_activity(): void
    {
        $empty = Community::factory()->create(['name' => 'Brgy. Empty']);
        $active = Community::factory()->create(['name' => 'Brgy. Active']);

        $project = $this->project();
        $project->communities()->attach($active->id);

        $this->actingAs($this->admin())
            ->get('/reports/community-impact?year=2026')
            ->assertOk()
            ->assertSee('Brgy. Active')
            ->assertDontSee('Brgy. Empty');
    }

    public function test_the_preview_mode_hides_the_toolbar_and_prints_a4_landscape(): void
    {
        $this->project();

        $response = $this->actingAs($this->admin())
            ->get('/reports/annual?year=2026&preview=1')
            ->assertOk();

        $body = $response->getContent();

        $this->assertStringNotContainsString('Back to reports', $body);
        $this->assertStringNotContainsString('Print / Save as PDF', $body);
        $this->assertStringContainsString('A4 landscape', $body);
        $this->assertSame(3, substr_count($body, 'Name and Signature'));
    }

    public function test_the_annual_report_is_trimmed_and_has_a_summary_band(): void
    {
        $this->project();

        $response = $this->actingAs($this->admin())
            ->get('/reports/annual?year=2026')
            ->assertOk();

        $body = $response->getContent();

        foreach (['Projects', 'Trainees reached', 'Training hours', 'Budget utilization'] as $figure) {
            $this->assertStringContainsString($figure, $body);
        }

        // The placeholder and developer notes are gone.
        $this->assertStringNotContainsString('appears here', $body);
        $this->assertStringNotContainsString('Nothing on this report is an estimate', $body);
    }
}
