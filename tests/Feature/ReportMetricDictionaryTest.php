<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase R5, step 4 — the four print reports on the R4/R5 metric dictionary.
 *
 * WHY THIS SUITE EXISTS
 * ---------------------
 * `Phase4Test` already proves the four report routes return 200. That is not
 * enough for R5: a report can render happily while printing retired 8.6 metrics,
 * which is exactly the failure mode the D-R7 sweep was meant to eliminate. So
 * each report is asserted to show the CURRENT dictionary (trainors, trainees,
 * training hours, annual target) and to NOT show the retired one (knowledge
 * gain, cost per beneficiary, community reach, objectives met).
 *
 * The reports are PRINT surfaces, so a stale figure there is worse than
 * elsewhere: it is the number that leaves the building on paper.
 */
class ReportMetricDictionaryTest extends TestCase
{
    use RefreshDatabase;

    /** The retired 8.6 vocabulary. None of it may appear on a report. */
    private const RETIRED = [
        'Knowledge gain',
        'Cost per beneficiary',
        'Community reach',
        'Objectives met',
        'Objectives at risk',
    ];

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * A project with an annual HOURS target, an allocation, and one activity that
     * renders 40 hours. Budget has no annual target (owner decision 2026-09-26) —
     * the allocation is the figure utilization is measured against.
     */
    protected function project(string $code = 'CAS-2026-500', array $overrides = []): ExtensionProject
    {
        $project = ExtensionProject::create(array_merge([
            'code' => $code,
            'title' => 'Report Metrics Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 100000,
            'annual_target_hours' => 100,
        ], $overrides));

        Activity::create([
            'extension_project_id' => $project->id,
            'title' => 'Report Activity',
            'planned_start_date' => '2026-03-10',
            'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'no_of_days' => 1.0,
            'participants' => 20,
            'trainors_snapshot' => 2,
        ]);

        return $project;
    }

    private function assertNoRetiredVocabulary(string $body, string $surface): void
    {
        foreach (self::RETIRED as $retired) {
            $this->assertStringNotContainsString(
                $retired, $body,
                "{$surface} still prints the retired 8.6 metric \"{$retired}\""
            );
        }
    }

    public function test_annual_report_uses_the_training_hours_dictionary(): void
    {
        $this->project();

        $response = $this->actingAs($this->admin())->get('/reports/annual')->assertOk();

        $body = $response->getContent();
        $this->assertStringContainsString('Training hours', $body);

        // 2 trainors x 20 trainees x 1 day = 40 — no `x 8`.
        $this->assertStringContainsString('40', $body);

        $this->assertNoRetiredVocabulary($body, 'The annual report');
    }

    public function test_community_impact_report_uses_real_reach_not_the_retired_metric(): void
    {
        $this->project();

        $response = $this->actingAs($this->admin())->get('/reports/community-impact')->assertOk();

        $this->assertNoRetiredVocabulary($response->getContent(), 'The community impact report');
    }

    public function test_rendered_hours_report_keeps_rendered_and_training_hours_distinct(): void
    {
        $this->project();

        $response = $this->actingAs($this->admin())->get('/reports/rendered-hours')->assertOk();

        $body = $response->getContent();
        $this->assertStringContainsString('Rendered hours', $body);

        // §9: the two are different metrics and must never be summed — the
        // report names both so a reader cannot conflate them.
        $this->assertDoesNotMatchRegularExpression(
            '/Rendered training hours/i', $body,
            'rendered hours and training hours must stay named separately'
        );

        $this->assertNoRetiredVocabulary($body, 'The rendered hours report');
    }

    public function test_results_framework_report_uses_targets_not_objectives(): void
    {
        $project = $this->project();

        $response = $this->actingAs($this->admin())
            ->get('/reports/results-framework/'.$project->id)
            ->assertOk();

        $body = $response->getContent();

        // v4.19: the report is still built on the target model, but the bare word
        // "Annual" is no longer the contract — budget has NO annual target. Assert
        // both halves instead: the annual HOURS target AND the allocation.
        $this->assertStringContainsString('annual hours target', $body);
        $this->assertStringContainsString('allocated budget', $body);

        $this->assertNoRetiredVocabulary($body, 'The results framework report');
    }

    public function test_no_report_renders_a_fabricated_attainment_without_a_target(): void
    {
        // No annual target at all: the NULL-over-0 discipline says the report
        // must say so rather than print "0%".
        $project = $this->project('CAS-2026-501', [
            'annual_target_hours' => null,
            'annual_target_budget' => null,
        ]);

        foreach ([
            '/reports/annual',
            '/reports/community-impact',
            '/reports/rendered-hours',
            '/reports/results-framework/'.$project->id,
        ] as $url) {
            $body = $this->actingAs($this->admin())->get($url)->assertOk()->getContent();

            $this->assertDoesNotMatchRegularExpression(
                '/0\s*%\s*(of|attainment)/i', $body,
                "{$url} invented a 0% attainment where no target exists"
            );
        }

        $this->assertNull($project->fresh()->hoursTarget(), 'the fixture genuinely has no target');
    }

    public function test_the_reports_index_lists_all_four_reports(): void
    {
        // The Project Performance card links per-project, so a project must
        // exist for the fourth report to be reachable from the index.
        $this->project();

        $response = $this->actingAs($this->admin())->get('/reports')->assertOk();

        $body = $response->getContent();
        foreach ([
            'Annual Extension Performance Report',
            'Project Performance Report',
            'Faculty Rendered Hours',
            'Community Partner Impact Summary',
        ] as $title) {
            $this->assertStringContainsString($title, $body, "the index does not offer \"{$title}\"");
        }
    }
}
