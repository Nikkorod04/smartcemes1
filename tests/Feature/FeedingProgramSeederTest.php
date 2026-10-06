<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ExtensionProject;
use App\Models\RenderedHours;
use App\Services\KpiService;
use App\Services\ProjectArchiveService;
use Database\Seeders\FeedingProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedingProgramSeederTest extends TestCase
{
    use RefreshDatabase;

    private function program(): ExtensionProject
    {
        /*
         * `withTrashed()` + an explicit RESTORE.
         *
         * The safe-zone seeder (2026-10-05) archives BUSOG as part of replacing
         * the demo's project set with five simpler ones. Its rows all survive,
         * but its ACTIVITIES are archived with it — so a trashed BUSOG reports 0
         * activities, 0 attendance and 0 reach, and this test would be measuring
         * the archive rather than the BUSOG seeder it exists to cover.
         *
         * Restoring it puts BUSOG back in exactly the state `FeedingProgramSeeder`
         * produced, which is what these assertions are about. It also proves the
         * archive round-trips a full cohort.
         */
        $program = ExtensionProject::withTrashed()
            ->where('title', FeedingProgramSeeder::PROGRAM_TITLE)
            ->firstOrFail();

        if ($program->trashed()) {
            app(ProjectArchiveService::class)->restore($program);
            $program->refresh();
        }

        return $program;
    }

    public function test_feeding_program_seeds_structure_and_enrollment(): void
    {
        $this->seed();

        $program = $this->program();

        /* R-Q4: BUSOG is created AFTER the R2 rename, so it uses the
           college-prefixed scheme (CAS) rather than the legacy EXT- codes that
           the six migrated projects keep. */
        $this->assertMatchesRegularExpression('/^CAS-2026-\d{3}$/', $program->code);
        $this->assertStringContainsString('BUSOG', $program->title);
        $this->assertSame(30, $program->target_beneficiaries);
        $this->assertSame('ongoing', $program->status);

        /* R2: the seeder attaches the hierarchy (§3.1 mapping). */
        $this->assertNotNull($program->college_id, 'BUSOG is linked to a college');
        $this->assertSame('CAS', $program->college?->code);
        $this->assertNotNull($program->program_id, 'BUSOG is linked to a broad program');
        $this->assertSame('Information, Communication & Education', $program->program?->title);

        $this->assertCount(3, $program->programObjectives);
        $this->assertEqualsCanonicalizing(
            ['community_reach', 'attendance_consistency', 'budget_utilization'],
            $program->programObjectives->pluck('kpi_metric')->all()
        );

        $this->assertCount(3, $program->activities);
        $this->assertSame(2, $program->activities->where('status', 'completed')->count());
        $this->assertSame(1, $program->activities->where('status', 'ongoing')->count());

        $this->assertSame(30, $program->beneficiaries()->count());
        $this->assertSame(30, $program->beneficiaries()->whereNotNull('phone')->count());
        $this->assertSame(30, $program->beneficiaries()->where('barangay', 'Nula-tula')->count());

        $communityNames = $program->communities->pluck('name');
        $this->assertTrue($communityNames->contains('Brgy. Nula-tula'));
        $this->assertTrue($communityNames->contains('Nula-Tula Elementary School'));

        $this->assertSame(2, RenderedHours::whereIn('activity_id', $program->activities->pluck('id'))->count());
    }

    public function test_feeding_program_kpis_are_presentation_ready(): void
    {
        $this->seed();

        $kpi = app(KpiService::class);
        $program = $this->program();

        $this->assertSame(30, $kpi->communityReach($program));
        $this->assertSame(100.0, $kpi->participationRate($program));
        $this->assertGreaterThanOrEqual(85.0, $kpi->attendanceConsistency($program));
        $this->assertEqualsWithDelta(91.76, $kpi->budgetUtilization($program), 0.01);
        $this->assertNull($kpi->knowledgeGain($program));
        $this->assertEqualsWithDelta(66.67, $kpi->activityCompletionRate($program), 0.01);
        $this->assertEqualsWithDelta(2600.0, $kpi->costPerBeneficiary($program), 0.01);

        $program->activities->each(function (Activity $activity): void {
            $this->assertNull($activity->pre_assessment_score);
            $this->assertNull($activity->post_assessment_score);
        });

        $objectives = $program->programObjectives->keyBy('kpi_metric');
        $this->assertSame('achieved', $kpi->statusFor($objectives['community_reach']));
        $this->assertSame('achieved', $kpi->statusFor($objectives['attendance_consistency']));
        $this->assertSame('achieved', $kpi->statusFor($objectives['budget_utilization']));
    }
}
