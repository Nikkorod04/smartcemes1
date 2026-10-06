<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Services\FacultyContributionService;
use App\Services\RankingService;
use App\Services\TrainingHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase R5 — the rankings behind "most active project / faculty / college".
 *
 * WHY THIS SUITE EXISTS
 * ---------------------
 * The R5 exit criteria are unambiguous: *every ranking and filter is driven by
 * real computed data, no invented numbers*. Three failure modes would violate
 * that, and each has a test below:
 *
 *  1. A ranking that sorts on a number the row does not print. The sort key is
 *     asserted to be reconstructible from the printed keys — `engagement` for a
 *     project is exactly `training_hours + trainees`.
 *  2. A ranking that invents a denominator. Colleges and faculty carry **no
 *     target** (§2.2B: targets exist at University and Project level only), so
 *     no attainment percentage may appear on their rows — asserted as NULL.
 *  3. A filter whose option list is hardcoded. `filterOptions()` is asserted to
 *     be sourced from real rows, including the years.
 *
 * The faculty weight (10) is asserted by a scenario where it is the ONLY thing
 * that can produce the expected order, so a silent change to the weight fails.
 */
class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Counter so throwaway project codes stay deterministic across calls. */
    private int $renderedHoursSeq = 0;

    /* ================================================================== */
    /* helpers */
    /* ================================================================== */

    protected function service(): RankingService
    {
        return app(RankingService::class);
    }

    protected function college(string $code): College
    {
        return College::factory()->create(['code' => $code]);
    }

    protected function project(array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => 'CAS-2026-900',
            'title' => 'Ranking Test Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 100000,
        ], $overrides));
    }

    /**
     * An activity that delivers exactly `$trainors x $trainees x $days` hours
     * via the MANUAL participant path (R-Q1's `manual` source), which keeps the
     * fixture independent of the attendance tables.
     */
    protected function activity(ExtensionProject $project, int $trainors, int $trainees, float $days = 1.0): Activity
    {
        return Activity::create([
            'extension_project_id' => $project->id,
            'title' => 'Ranking Activity',
            'planned_start_date' => '2026-03-10',
            'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
            'no_of_days' => $days,
            'participants' => $trainees,
            'trainors_snapshot' => $trainors,
        ]);
    }

    /**
     * Give `$count` distinct present beneficiaries to an activity.
     *
     * R-Q1 makes attendance the FIRST trainee source, so adding attendees
     * changes the hours as well as the reach of that activity — the tests below
     * rely on that, and say so where it matters.
     */
    protected function attendance(Activity $activity, int $count): void
    {
        foreach (Beneficiary::factory()->count($count)->create() as $beneficiary) {
            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => $beneficiary->id,
                'attendance_date' => '2026-03-10',
                'status' => 'present',
            ]);
        }
    }

    /**
     * Approved rendered hours for a faculty member.
     *
     * `rendered_hours.activity_id` is NOT NULL and unique per faculty, so each
     * row needs its own activity — hence the throwaway project per call. The
     * project code is counter-based, not random, so a failure is reproducible.
     */
    protected function renderedHours(Faculty $faculty, float $hours, string $status = RenderedHours::STATUS_APPROVED): RenderedHours
    {
        $this->renderedHoursSeq = ($this->renderedHoursSeq ?? 0) + 1;

        $project = $this->project([
            'code' => 'CAS-2026-8'.str_pad((string) $this->renderedHoursSeq, 2, '0', STR_PAD_LEFT),
        ]);
        $activity = $this->activity($project, 1, 1);

        return RenderedHours::create([
            'faculty_id' => $faculty->id,
            'activity_id' => $activity->id,
            'date' => '2026-03-01',
            'hours' => $hours,
            'status' => $status,
        ]);
    }

    /* ================================================================== */
    /* 1. projects — the engagement sort */
    /* ================================================================== */

    public function test_projects_rank_by_training_hours_then_reach(): void
    {
        // 200 hrs, no attendees → engagement 200.
        $heavy = $this->project(['code' => 'CAS-2026-001', 'title' => 'BUSOG: Heavy']);
        $this->activity($heavy, trainors: 10, trainees: 20, days: 1.0);

        // 50 hrs + 60 distinct attendees → engagement 110. Fewer hours, more
        // reach — an hours-only ranking would put it second, the real rule does
        // not move it above 200, so make the winner unambiguous:
        $broad = $this->project(['code' => 'CAS-2026-002', 'title' => 'GULAY: Broad']);
        $broadActivity = $this->activity($broad, trainors: 1, trainees: 60, days: 1.0);
        $this->attendance($broadActivity, 60);

        // 150 hrs + 100 attendees → engagement 250: the genuine winner, and it
        // is neither the most-hours nor the most-reached project taken alone.
        $balanced = $this->project(['code' => 'CAS-2026-003', 'title' => 'HALAMAN: Balanced']);
        $balancedActivity = $this->activity($balanced, trainors: 2, trainees: 100, days: 1.0);
        $this->attendance($balancedActivity, 100);

        $rows = $this->service()->projects();

        $this->assertSame(
            ['HALAMAN: Balanced', 'BUSOG: Heavy', 'GULAY: Broad'],
            $rows->pluck('title')->all()
        );

        // `GULAY` is last: 1 trainor x 60 trainees x 1 day = 60 hrs, and the 60
        // attendees are the SAME 60 people, so reach adds 60 → 120. Both figures
        // are lower than BUSOG's 200 hours, so it cannot climb.
        $this->assertSame(120.0, $rows->firstWhere('title', 'GULAY: Broad')['engagement']);

        // The sort key must be reconstructible from the printed keys.
        foreach ($rows as $row) {
            $this->assertSame(
                $row['training_hours'] + $row['trainees'],
                $row['engagement'],
                'engagement must equal training_hours + trainees for '.$row['title']
            );
        }
    }

    public function test_project_rows_carry_the_metric_dictionary_not_the_retired_one(): void
    {
        $project = $this->project(['annual_target_hours' => 200, 'allocated_budget' => 50000]);
        $this->activity($project, trainors: 2, trainees: 25, days: 1.0);

        $row = $this->service()->projects()->first();

        // The R4 dictionary.
        foreach ([
            'training_hours', 'target_hours', 'hours_pct', 'trainees', 'trainors',
            'activities', 'completed', 'budget_allocated', 'budget_utilized', 'budget_pct',
            'over_budget', 'engagement', 'short_title', 'college_color',
        ] as $key) {
            $this->assertArrayHasKey($key, $row, "missing key: {$key}");
        }

        // D-R7: none of the retired 8.6 KPI names may reappear.
        foreach (['knowledge_gain', 'cost_per_beneficiary', 'community_reach', 'objectives'] as $gone) {
            $this->assertArrayNotHasKey($gone, $row, "retired key resurfaced: {$gone}");
        }

        $this->assertSame(50.0, $row['training_hours']);
        $this->assertSame(200.0, $row['target_hours']);
        $this->assertSame(25.0, $row['hours_pct']);
    }

    public function test_a_project_without_a_target_reports_null_attainment_not_zero(): void
    {
        $project = $this->project();
        $this->activity($project, trainors: 3, trainees: 10, days: 1.0);

        $row = $this->service()->projects()->first();

        $this->assertNull($row['target_hours'], 'no target must be null, never 0');
        $this->assertNull(
            $row['hours_pct'],
            'NULL over 0: an absent denominator must not become "0%"'
        );
        $this->assertSame(30.0, $row['training_hours'], 'the actual still computes');
    }

    public function test_projects_can_be_filtered_by_college_and_year(): void
    {
        $cas = $this->college('CAS');
        $coe = $this->college('COE');

        $casProject = $this->project([
            'code' => 'CAS-2026-001', 'college_id' => $cas->id, 'planned_start_date' => '2026-02-01',
        ]);
        $coeProject = $this->project([
            'code' => 'COE-2025-001', 'college_id' => $coe->id, 'planned_start_date' => '2025-02-01',
        ]);
        $this->activity($casProject, 1, 10);
        $this->activity($coeProject, 1, 10);

        $byCollege = $this->service()->projects(null, $cas->id);
        $this->assertSame([$casProject->id], $byCollege->pluck('id')->all());

        $byYear = $this->service()->projects(null, null, 2025);
        $this->assertSame([$coeProject->id], $byYear->pluck('id')->all());
    }

    public function test_the_limit_truncates_without_reordering(): void
    {
        foreach (range(1, 4) as $i) {
            $project = $this->project(['code' => 'CAS-2026-00'.$i, 'title' => 'Project '.$i]);
            $this->activity($project, trainors: 1, trainees: $i * 10);
        }

        $all = $this->service()->projects();
        $top = $this->service()->projects(2);

        $this->assertCount(4, $all);
        $this->assertCount(2, $top);
        $this->assertSame($all->take(2)->pluck('id')->all(), $top->pluck('id')->all());
    }

    /* ================================================================== */
    /* 2. faculty — contribution, never attainment */
    /* ================================================================== */

    public function test_faculty_rank_by_rendered_hours_plus_weighted_involvement(): void
    {
        // Ten hours, one project → 10 + 1x10 = 20.
        $specialist = Faculty::factory()->create(['position' => 'Instructor I']);
        $this->renderedHours($specialist, 10);

        // Zero hours but three projects → 0 + 3x10 = 30: wins purely on
        // involvement, which is the only reason the weight exists.
        $generalist = Faculty::factory()->create(['position' => 'Instructor II']);
        foreach (range(1, 3) as $i) {
            $project = $this->project(['code' => 'CAS-2026-10'.$i, 'title' => 'Led '.$i]);
            $activity = $this->activity($project, 1, 1);
            $activity->faculty()->sync([$generalist->id]);
        }

        $rows = $this->service()->faculty();

        $this->assertSame($generalist->id, $rows->first()['id'], '3 projects must outrank 10 flat hours');

        foreach ($rows as $row) {
            $this->assertSame(
                $row['rendered_hours'] + $row['projects'] * RankingService::INVOLVEMENT_WEIGHT,
                $row['engagement'],
                'faculty engagement must be rendered_hours + projects * weight'
            );
        }
    }

    public function test_a_faculty_row_carries_no_attainment_percentage(): void
    {
        $faculty = Faculty::factory()->create();
        $this->renderedHours($faculty, 40);

        $row = $this->service()->faculty()->first();

        // D-R9: there is no per-professor target, so any percentage would be
        // fabricated. Assert its ABSENCE rather than that it is null.
        foreach (['pct', 'attainment', 'hours_pct', 'target_hours', 'hours_target'] as $forbidden) {
            $this->assertArrayNotHasKey(
                $forbidden, $row,
                "a faculty row must not carry {$forbidden} — faculty rank by contribution"
            );
        }

        $this->assertSame(40.0, $row['rendered_hours']);
        $this->assertSame(40.0, $row['engagement'], 'one project is not involved here → weight 0');
    }

    public function test_faculty_rows_agree_with_the_contribution_service(): void
    {
        $faculty = Faculty::factory()->create();
        $this->renderedHours($faculty, 7, RenderedHours::STATUS_APPROVED);
        $this->renderedHours($faculty, 3, RenderedHours::STATUS_PENDING);

        $ranked = $this->service()->faculty()->first();
        $direct = app(FacultyContributionService::class)->forFaculty(Faculty::all())->first();

        // The leaderboard and the Faculty Management board must not disagree.
        $this->assertSame($direct['rendered_hours'], $ranked['rendered_hours']);
        $this->assertSame($direct['pending_hours'], $ranked['pending_hours']);
        $this->assertSame($direct['projects_involved'], $ranked['projects']);
    }

    public function test_faculty_can_be_filtered_by_college(): void
    {
        $cas = $this->college('CAS');
        $coe = $this->college('COE');

        $inCas = Faculty::factory()->create(['college_id' => $cas->id]);
        $inCoe = Faculty::factory()->create(['college_id' => $coe->id]);

        $rows = $this->service()->faculty(null, $cas->id);

        $this->assertSame([$inCas->id], $rows->pluck('id')->all());
    }

    /* ================================================================== */
    /* 3. colleges — contribution ranking, no target */
    /* ================================================================== */

    public function test_colleges_rank_by_training_hours_and_carry_no_attainment(): void
    {
        $cas = $this->college('CAS');
        $coe = $this->college('COE');

        $casProject = $this->project(['code' => 'CAS-2026-001', 'college_id' => $cas->id]);
        $coeProject = $this->project(['code' => 'COE-2026-001', 'college_id' => $coe->id]);
        $this->activity($casProject, trainors: 1, trainees: 90);
        $this->activity($coeProject, trainors: 1, trainees: 10);

        $rows = $this->service()->colleges();

        $this->assertSame('CAS', $rows->first()['code'], 'CAS delivered 90 hrs vs COE 10');
        $this->assertSame(90.0, $rows->first()['training_hours']);

        foreach ($rows as $row) {
            // §2.2B: a college has no target of its own.
            $this->assertNull($row['hours_pct'], "college {$row['code']} must not show attainment");
        }
    }

    public function test_a_college_with_no_projects_still_appears_with_zeroes(): void
    {
        $this->college('CME');

        $row = $this->service()->colleges()->firstWhere('code', 'CME');

        $this->assertNotNull($row, 'every college is listed, even at zero activity');
        $this->assertSame(0, $row['projects']);
        $this->assertSame(0.0, $row['training_hours']);
        $this->assertNull($row['hours_pct']);
    }

    /* ================================================================== */
    /* 4. presentation helpers */
    /* ================================================================== */

    public function test_short_title_takes_the_segment_before_a_colon(): void
    {
        $service = $this->service();

        $this->assertSame('BUSOG', $service->shortTitle('BUSOG: Nutrition & Feeding'));
        $this->assertSame('No Colon Here', $service->shortTitle('No Colon Here'));
        $this->assertSame('—', $service->shortTitle(null));
        $this->assertSame('—', $service->shortTitle('   '));
    }

    public function test_college_colour_resolves_by_code_case_insensitively(): void
    {
        $this->assertSame('#003599', RankingService::collegeColor('CAS'));
        $this->assertSame('#F6B800', RankingService::collegeColor('coe'));
        $this->assertSame('#10b981', RankingService::collegeColor('CME'));
        $this->assertNull(RankingService::collegeColor('XXX'));
        $this->assertNull(RankingService::collegeColor(null));
    }

    public function test_project_rows_carry_the_college_accent_colour(): void
    {
        $cas = $this->college('CAS');
        $project = $this->project(['college_id' => $cas->id]);
        $this->activity($project, 1, 5);

        $row = $this->service()->projects()->first();

        $this->assertSame('CAS', $row['college']);
        $this->assertSame('#003599', $row['college_color']);
    }

    /* ================================================================== */
    /* 5. filter option sources are real data */
    /* ================================================================== */

    public function test_filter_options_are_sourced_from_real_rows(): void
    {
        $cas = $this->college('CAS');
        $coe = $this->college('COE');
        $this->project(['code' => 'CAS-2026-001', 'college_id' => $cas->id, 'planned_start_date' => '2026-01-01']);
        $this->project(['code' => 'COE-2025-001', 'college_id' => $coe->id, 'planned_start_date' => '2025-01-01']);

        $options = $this->service()->filterOptions();

        $this->assertEqualsCanonicalizing(
            ['CAS', 'COE'],
            $options['colleges']->pluck('code')->all()
        );

        // Years come from the projects, newest first — not a hardcoded list.
        $this->assertSame([2026, 2025], $options['years']->all());

        $this->assertNotEmpty($options['statuses'], 'statuses come from config, not a literal here');
    }

    public function test_filter_options_compute_years_in_php_for_sqlite_portability(): void
    {
        // `YEAR()` is MySQL-only and the suite runs on in-memory SQLite, so a
        // `DISTINCT YEAR(x)` implementation would break the whole suite. This
        // asserts the portable path is the one in use.
        $this->project(['code' => 'CAS-2019-001', 'planned_start_date' => '2019-06-15']);
        $this->project(['code' => 'CAS-2021-001', 'planned_start_date' => '2021-06-15']);

        $years = $this->service()->filterOptions()['years'];

        $this->assertSame([2021, 2019], $years->all());
        foreach ($years as $year) {
            $this->assertIsInt($year, 'years must be ints, not the raw date strings');
        }
    }

    /* ================================================================== */
    /* 6. the exit criteria, stated directly */
    /* ================================================================== */

    public function test_every_ranked_figure_traces_back_to_the_training_hours_service(): void
    {
        $project = $this->project(['annual_target_hours' => 80]);
        $this->activity($project, trainors: 4, trainees: 5, days: 2.0);

        $rollup = app(TrainingHoursService::class)->forProject($project->fresh());
        $row = $this->service()->projects()->first();

        $this->assertSame($rollup['actual_hours'], $row['training_hours']);
        $this->assertSame($rollup['trainees'], $row['trainees']);
        $this->assertSame($rollup['trainors'], $row['trainors']);
        $this->assertSame($rollup['activity_count'], $row['activities']);
        $this->assertSame($rollup['hours_pct'], $row['hours_pct']);

        // 4 trainors x 5 trainees x 2 days = 40 — no `x 8`.
        $this->assertSame(40.0, $row['training_hours']);
        $this->assertSame(50.0, $row['hours_pct']);
    }

    public function test_an_empty_database_yields_empty_rankings_not_an_error(): void
    {
        $service = $this->service();

        $this->assertCount(0, $service->projects());
        $this->assertCount(0, $service->faculty());
        $this->assertCount(0, $service->colleges());
    }

    public function test_the_faculty_weight_is_ten(): void
    {
        // Pinned literally: the weight is a Director-visible behaviour ported
        // from the prototype, so a change must be deliberate and fail here.
        $this->assertSame(10, RankingService::INVOLVEMENT_WEIGHT);
    }
}
