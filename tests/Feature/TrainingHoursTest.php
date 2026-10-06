<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Livewire\Targets;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\UniversityTarget;
use App\Models\User;
use App\Services\TrainingHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase R4 — the training-hours model and the target model.
 *
 * The three things this suite exists to protect:
 *
 *  1. THE FORMULA IS `trainors x trainees x days` — there is NO `x 8`.
 *     The prototype's worked example (3 x 141 x 1 = 423) is asserted verbatim,
 *     and a separate test asserts that multiplying by 8 would give a DIFFERENT
 *     number, so a regression that reintroduces it fails loudly.
 *  2. THE TRAINEE RESOLUTION ORDER (R-Q1) is attendance -> manual -> 0, and the
 *     source is always visible so the Director knows what a figure rests on.
 *  3. TARGETS ARE A CONSUMPTION MODEL, NOT A RATIO (§2.2B / D-R5): the annual
 *     university pool is drawn down by project ACTUALS, and project targets are
 *     never summed to produce it. Broad programs carry no target.
 */
class TrainingHoursTest extends TestCase
{
    use RefreshDatabase;

    /* ================================================================== */
    /* helpers */
    /* ================================================================== */

    protected function program(array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => 'CAS-2026-900',
            'title' => 'Training Hours Test Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 100000,
        ], $overrides));
    }

    protected function activity(ExtensionProject $program, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'extension_project_id' => $program->id,
            'title' => 'Training Activity',
            'planned_start_date' => '2026-03-10',
            'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
        ], $overrides));
    }

    protected function service(): TrainingHoursService
    {
        return app(TrainingHoursService::class);
    }

    /* ================================================================== */
    /* 1. the formula — no x 8 */
    /* ================================================================== */

    public function test_compute_multiplies_trainors_trainees_and_days(): void
    {
        // The amendment, stated as arithmetic: 2 trainors x 112 trainees x 0.5
        // day = 112 hrs. Under the retired `x 8` rule this was 896.
        $this->assertSame(112.0, $this->service()->compute(2, 112, 0.5));
        $this->assertSame(423.0, $this->service()->compute(3, 141, 1.0));
        $this->assertSame(63.0, $this->service()->compute(1, 126, 0.5));
    }

    public function test_the_formula_is_not_multiplied_by_eight(): void
    {
        $service = $this->service();

        foreach ([[2, 112, 0.5], [3, 141, 1.0], [1, 126, 0.5]] as [$trainors, $trainees, $days]) {
            $expected = $trainors * $trainees * $days;
            $retiredRule = $expected * 8;

            $this->assertSame(
                (float) $expected,
                $service->compute($trainors, $trainees, $days),
                "compute({$trainors}, {$trainees}, {$days}) must equal trainors x trainees x days."
            );
            $this->assertNotSame(
                (float) $retiredRule,
                $service->compute($trainors, $trainees, $days),
                'A x 8 factor has been reintroduced — this is a regression, not a decision (D-R3/D-R4).'
            );
        }
    }

    public function test_a_half_day_is_first_class(): void
    {
        // decimal(4,1), not an integer: 0.5 must round-trip, not become 0 or 1.
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 0.5, 'participants' => 4]);

        $this->assertSame(0.5, $activity->fresh()->no_of_days);
        $this->assertSame('0.5', $this->service()->formatDays(0.5));
        $this->assertSame('1', $this->service()->formatDays(1.0));
        $this->assertSame('2.5', $this->service()->formatDays(2.5));
    }

    public function test_formula_string_matches_the_prototype_wording(): void
    {
        $service = $this->service();

        $this->assertSame('1 trainor x 1 trainee x 1 day = 1 hrs', $service->explain(1, 1, 1.0, 1));
        $this->assertSame('3 trainors x 141 trainees x 1 day = 423 hrs', $service->explain(3, 141, 1.0, 423));
        // A half day is singular: "0.5 day", not "0.5 days".
        $this->assertSame('2 trainors x 112 trainees x 0.5 day = 112 hrs', $service->explain(2, 112, 0.5, 112));
        // …but a day-and-a-half is not.
        $this->assertSame('2 trainors x 10 trainees x 2.5 days = 50 hrs', $service->explain(2, 10, 2.5, 50));
    }

    /* ================================================================== */
    /* 2. trainee resolution order (R-Q1) */
    /* ================================================================== */

    public function test_imported_attendance_wins_over_the_manual_field(): void
    {
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 1.0, 'participants' => 999]);

        // Two present/late attendances coexist with a manual 999.
        foreach (range(1, 2) as $i) {
            $beneficiary = Beneficiary::factory()->create();
            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => $beneficiary->id,
                'attendance_date' => '2026-03-10',
                'status' => 'present',
            ]);
        }

        $breakdown = $this->service()->forActivity($activity->fresh());

        $this->assertSame(2, $breakdown['trainees'], 'Imported attendance must win over the manual fallback.');
        $this->assertSame(TrainingHoursService::SOURCE_ATTENDANCE, $breakdown['trainees_source']);
    }

    public function test_manual_participants_are_used_when_no_attendance_exists(): void
    {
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 1.0, 'participants' => 25]);

        $breakdown = $this->service()->forActivity($activity->fresh());

        $this->assertSame(25, $breakdown['trainees']);
        $this->assertSame(TrainingHoursService::SOURCE_MANUAL, $breakdown['trainees_source']);
    }

    public function test_trainees_fall_back_to_zero_and_say_so(): void
    {
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 1.0, 'participants' => null]);

        $breakdown = $this->service()->forActivity($activity->fresh());

        // Zero rather than NULL: the count is genuinely 0, but the SOURCE says
        // "none" so the UI can distinguish "nobody attended" from "not recorded".
        $this->assertSame(0, $breakdown['trainees']);
        $this->assertSame(TrainingHoursService::SOURCE_NONE, $breakdown['trainees_source']);
    }

    public function test_only_present_and_late_attendance_counts_as_trainees(): void
    {
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 1.0]);

        foreach (['present', 'late', 'absent', 'excused'] as $status) {
            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => Beneficiary::factory()->create()->id,
                'attendance_date' => '2026-03-10',
                'status' => $status,
            ]);
        }

        // absent + excused are recorded but do not count as reached.
        $this->assertSame(2, $this->service()->forActivity($activity->fresh())['trainees']);
    }

    /* ================================================================== */
    /* 3. trainors resolution */
    /* ================================================================== */

    public function test_trainors_default_to_the_assigned_faculty_count(): void
    {
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 1.0, 'participants' => 10]);

        $activity->faculty()->sync([Faculty::factory()->create()->id, Faculty::factory()->create()->id]);

        $this->assertSame(2, $this->service()->forActivity($activity->fresh())['trainors']);
    }

    public function test_the_snapshot_overrides_the_live_faculty_count(): void
    {
        $program = $this->program();
        // One faculty assigned, but the snapshot records the historical 3.
        $activity = $this->activity($program, [
            'no_of_days' => 1.0, 'participants' => 10, 'trainors_snapshot' => 3,
        ]);
        $activity->faculty()->sync([Faculty::factory()->create()->id]);

        $this->assertSame(3, $this->service()->forActivity($activity->fresh())['trainors']);
    }

    /* ================================================================== */
    /* 4. days default + NULL semantics */
    /* ================================================================== */

    public function test_null_days_default_to_a_full_day(): void
    {
        $program = $this->program();
        // A pre-R4 activity: no_of_days is NULL. The service reads it as 1.0 so
        // a migrated row still renders a meaningful (if conservative) figure
        // instead of a blank or a divide-by-zero.
        $activity = $this->activity($program, ['no_of_days' => null, 'participants' => 10]);
        $activity->faculty()->sync([Faculty::factory()->create()->id]);

        $breakdown = $this->service()->forActivity($activity->fresh());

        $this->assertSame(1.0, $breakdown['days']);
        // trainors 1 x trainees 10 x days 1 = 10 hrs
        $this->assertSame(10.0, $breakdown['hours']);
        $this->assertTrue($breakdown['measurable'], 'A defaulted full day still yields a measurable figure.');
    }

    public function test_measurable_is_false_when_a_factor_is_missing(): void
    {
        $program = $this->program();
        // No faculty assigned and no manual participants: two factors are 0, so
        // the product is 0. `measurable` is what tells the UI to say "not
        // measurable" instead of presenting a real-looking 0 hrs.
        $activity = $this->activity($program, ['no_of_days' => 1.0, 'participants' => null]);

        $this->assertFalse($this->service()->forActivity($activity->fresh())['measurable']);
    }

    public function test_measurable_flips_once_the_days_column_exists(): void
    {
        // §14.7: `activities.no_of_days` is the trigger. R4 added it.
        $this->assertTrue($this->service()->isMeasurable());
        $this->assertTrue(Schema::hasColumn('activities', 'no_of_days'));
    }

    /* ================================================================== */
    /* 5. project roll-up */
    /* ================================================================== */

    public function test_project_rollup_sums_activity_hours_and_counts_distinct_entities(): void
    {
        $program = $this->program(['annual_target_hours' => 1000]);

        // Two completed activities: 2x3x1 = 6 hrs and 1x3x0.5 = 1.5 hrs.
        $lead = Faculty::factory()->create();
        $coLead = Faculty::factory()->create();

        $a1 = $this->activity($program, ['title' => 'A1', 'no_of_days' => 1.0, 'participants' => 3]);
        $a1->faculty()->sync([$lead->id, $coLead->id]);

        $a2 = $this->activity($program, ['title' => 'A2', 'no_of_days' => 0.5, 'participants' => 3]);
        $a2->faculty()->sync([$lead->id]);

        // One ongoing activity — counted in activity_count but not completed_count.
        $this->activity($program, ['title' => 'A3', 'status' => 'ongoing', 'no_of_days' => 1.0, 'participants' => 3]);

        $perf = $this->service()->forProject($program->fresh());

        $this->assertSame(7.5, $perf['actual_hours'], '6 + 1.5 = 7.5 hrs');
        $this->assertSame(3, $perf['activity_count']);
        $this->assertSame(2, $perf['completed_count']);
        // 2 distinct project trainors (lead on both, co-lead on one).
        $this->assertSame(2, $perf['trainors']);
        // training_days mirrors actual_hours: it sums EVERY activity's recorded
        // days, delivered or not, because the caption reads "N days across M
        // completed activities" — the two numbers are deliberately different
        // quantities and both are shown. 1.0 + 0.5 + 1.0 = 2.5.
        $this->assertSame(2.5, $perf['training_days']);
        // percentage() rounds to one decimal, so 7.5 / 1000 = 0.75% -> 0.8.
        $this->assertSame(0.8, $perf['hours_pct'], '7.5 / 1000 = 0.75%, rounded to 0.8');
    }

    public function test_project_hours_pct_is_null_without_a_target(): void
    {
        $program = $this->program(); // no annual target
        $this->activity($program, ['no_of_days' => 1.0, 'participants' => 5]);

        $perf = $this->service()->forProject($program->fresh());

        // NULL, not 0 and not infinite: there is no denominator, so attainment
        // is unknown — the UI renders "No target set".
        $this->assertNull($perf['target_hours']);
        $this->assertNull($perf['hours_pct']);
    }

    public function test_trainees_are_counted_distinctly_not_summed_across_activities(): void
    {
        $program = $this->program();
        $a1 = $this->activity($program, ['title' => 'A1', 'no_of_days' => 1.0]);
        $a2 = $this->activity($program, ['title' => 'A2', 'no_of_days' => 1.0]);

        // The same beneficiary attends BOTH activities — reached once, not twice.
        $beneficiary = Beneficiary::factory()->create();
        foreach ([$a1, $a2] as $activity) {
            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => $beneficiary->id,
                'attendance_date' => '2026-03-10',
                'status' => 'present',
            ]);
        }

        $this->assertSame(1, $this->service()->forProject($program->fresh())['trainees']);
    }

    /* ================================================================== */
    /* 6. the university pool — consumption, not a ratio */
    /* ================================================================== */

    public function test_university_target_is_consumed_by_project_actuals(): void
    {
        // Two projects rendering 6 and 1.5 hrs against a 100-hour pool.
        $p1 = $this->program(['code' => 'CAS-2026-901', 'annual_target_hours' => 999]);
        $p2 = $this->program(['code' => 'CAS-2026-902', 'annual_target_hours' => 999]);

        $this->activity($p1, ['no_of_days' => 1.0, 'participants' => 6, 'trainors_snapshot' => 1]);
        $this->activity($p2, ['no_of_days' => 0.5, 'participants' => 3, 'trainors_snapshot' => 1]);

        $rollup = $this->service()->forYear(2026, 100.0, 50000.0);

        // 6 + 1.5 = 7.5 consumed, 92.5 remaining.
        $this->assertSame(7.5, $rollup['training_hours']);
        $this->assertSame(100.0, $rollup['target_hours']);
        $this->assertSame(92.5, $rollup['remaining']);
        $this->assertSame(7.5, $rollup['drawn_down']);
        $this->assertSame(7.5, $rollup['hours_pct']);

        // The project-level 999 targets are NOT summed to make the annual pool —
        // 999 + 999 = 1998 would be the double-counting bug D-R5 forbids.
        $this->assertSame(100.0, $rollup['target_hours']);
        $this->assertNotSame(1998.0, $rollup['target_hours']);
    }

    public function test_an_over_drawn_pool_reports_over_draw_without_a_negative_remainder(): void
    {
        $program = $this->program();
        $this->activity($program, ['no_of_days' => 1.0, 'participants' => 200, 'trainors_snapshot' => 1]);

        $rollup = $this->service()->forYear(2026, 100.0, null);

        $this->assertSame(200.0, $rollup['training_hours']);
        // Remaining floors at 0 — the truth is carried by the percentage.
        $this->assertSame(0.0, $rollup['remaining']);
        $this->assertSame(100.0, $rollup['drawn_down'], 'Drawdown cannot exceed the pool.');
        $this->assertSame(200.0, $rollup['hours_pct']);
    }

    /* ================================================================== */
    /* 7. UniversityTarget model */
    /* ================================================================== */

    public function test_for_year_does_not_auto_create(): void
    {
        // A missing target must render "no target set", never a silent 0 row —
        // a 0 denominator is what makes attainment meaningless.
        $this->assertNull(UniversityTarget::forYear(2026));
        $this->assertSame(0, UniversityTarget::count());
    }

    public function test_display_label_derives_the_ay_convention(): void
    {
        $target = UniversityTarget::create(['year' => 2026, 'annual_target_hours' => 2500]);

        $this->assertSame('AY 2026-2027', $target->display_label);

        // An explicit label wins, so the Director controls the convention.
        $target->update(['label' => 'AY 2026–2027 (Board-approved)']);
        $this->assertSame('AY 2026–2027 (Board-approved)', $target->fresh()->display_label);
    }

    public function test_current_returns_the_latest_year(): void
    {
        UniversityTarget::create(['year' => 2025, 'annual_target_hours' => 2100]);
        UniversityTarget::create(['year' => 2026, 'annual_target_hours' => 2500]);

        $this->assertSame(2026, UniversityTarget::current()->year);
    }

    /* ================================================================== */
    /* 8. the Targets page (R4b) */
    /* ================================================================== */

    public function test_the_targets_page_is_admin_only(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)->get('/targets')->assertForbidden();
    }

    public function test_the_targets_page_no_longer_shows_the_model_pending_banner(): void
    {
        // §2.2C's amber "this page does not yet reflect the final target model"
        // banner is deleted: the model now exists, so the banner would be false.
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/targets')
            ->assertOk()
            ->assertSee('University Targets')
            ->assertDontSee('does not yet reflect', escape: false)
            ->assertDontSee('Pending');
    }

    public function test_the_director_can_set_the_annual_target(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(Targets::class)
            ->set('year', 2026)
            ->set('form.annual_target_hours', '2500')
            ->set('form.annual_target_budget', '668000')
            ->call('save')
            ->assertHasNoErrors();

        $target = UniversityTarget::forYear(2026);
        $this->assertNotNull($target);
        $this->assertSame('2500.00', $target->annual_target_hours);
        $this->assertSame('668000.00', $target->annual_target_budget);
        $this->assertSame($admin->id, $target->updated_by);
    }

    public function test_setting_a_target_writes_an_activity_log_entry(): void
    {
        // The page promises the Director that edits are logged, so they must be.
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)->test(Targets::class)
            ->set('year', 2026)
            ->set('form.annual_target_hours', '2500')
            ->call('save');

        $this->assertDatabaseHas('activity_log', [
            'event' => 'target_create',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_editing_a_target_updates_rather_than_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        UniversityTarget::create(['year' => 2026, 'annual_target_hours' => 2000]);

        Livewire::actingAs($admin)->test(Targets::class)
            ->set('year', 2026)
            ->set('form.annual_target_hours', '2500')
            ->call('save');

        $this->assertSame(1, UniversityTarget::where('year', 2026)->count());
        $this->assertSame('2500.00', UniversityTarget::forYear(2026)->annual_target_hours);
    }

    public function test_a_blank_hours_field_clears_the_pool_rather_than_zeroing_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        UniversityTarget::create(['year' => 2026, 'annual_target_hours' => 2500]);

        Livewire::actingAs($admin)->test(Targets::class)
            ->set('year', 2026)
            ->set('form.annual_target_hours', '')
            ->call('save')
            ->assertHasNoErrors();

        // NULL, not 0 — the page then renders "no target set".
        $this->assertNull(UniversityTarget::forYear(2026)->annual_target_hours);
    }

    /**
     * The page used to carry a D-R5 guardrail card ("Targets are set at two levels only
     * — University and Project...") and a "Training Hours Formula" card, and this test
     * used to REQUIRE them.
     *
     * Both were removed on 2026-09-28 at the owner's request, so the test now pins their
     * ABSENCE — otherwise a future pass could "restore" them from the old copy, or from
     * `docs/prototype/pages/targets.html`, which lost them in the same change.
     *
     * The pool/drawdown explanation is deliberately NOT gone: it still lives on the
     * "Training hours rendered" KPI tile, which is what actually teaches the model. D-R5's
     * guardrail is also still stated on the PROJECT HUB (`hub-overview`), so the app has
     * not lost the disclosure — only this page's copy of it.
     */
    public function test_the_targets_page_dropped_the_guardrail_and_formula_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        UniversityTarget::create(['year' => 2026, 'annual_target_hours' => 2500]);

        $this->actingAs($admin)->get('/targets?year=2026')
            ->assertOk()
            ->assertDontSee('Targets are set at two levels only')
            ->assertDontSee('Training Hours Formula')
            ->assertDontSee('Progress Across the Year')
            ->assertDontSee('multiplied by 8')
            // ...but the consumption model is still explained, on the KPI tile.
            ->assertSee('of the annual pool still available')
            ->assertSee('drawn down by');
    }

    /**
     * The Project-targets table must actually honour its sort dropdown.
     *
     * It did not until 2026-09-28: the blade rendered `$tableRows->sortBy('code')` while
     * the select offered "hours attainment up" / "budget utilization down" / "code", so
     * the dropdown was inert and the table was always in code order. The rows even carried
     * data-* sort keys for an Alpine comparator that was never written. Sorting now lives
     * in the component (`Targets::$sort`), and this pins the ORDER, not the markup.
     */
    public function test_the_project_targets_table_honours_its_sort_dropdown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Code order and hours-attainment order are deliberately OPPOSITE, so a table
        // stuck in code order cannot satisfy the "hours up" assertion by accident.
        $met = $this->program(['code' => 'CAS-2026-001', 'title' => 'METTARGET', 'annual_target_hours' => 10]);
        $this->program(['code' => 'CAS-2026-002', 'title' => 'BEHINDTARGET', 'annual_target_hours' => 100]);

        // 1 trainor x 10 trainees x 1 day = 10 hrs, i.e. 100 % of its target; the other is 0 %.
        $this->activity($met, ['no_of_days' => 1.0, 'participants' => 10, 'trainors_snapshot' => 1]);

        $order = function (string $sort) use ($admin): array {
            $html = Livewire::actingAs($admin)->test(Targets::class)->set('sort', $sort)->html();

            return ['met' => strpos($html, 'METTARGET'), 'behind' => strpos($html, 'BEHINDTARGET')];
        };

        // hours up — worst attainment first, so the 0 % project PRECEDES the 100 % one.
        // (assertLessThan($a, $b) asserts $b < $a: the 0 % row must sit at the lower offset.)
        $hours = $order('hours');
        $this->assertNotFalse($hours['met'], 'the met-target row must render');
        $this->assertNotFalse($hours['behind'], 'the behind-target row must render');
        $this->assertLessThan($hours['met'], $hours['behind'], 'hours up must list the 0 % project first');

        // code — alphabetical, so CAS-2026-001 leads again.
        $code = $order('code');
        $this->assertLessThan($code['behind'], $code['met'], 'code must sort alphabetically');
    }

    public function test_the_targets_page_lists_projects_with_their_attainment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $college = College::factory()->create(['code' => 'CAS']);
        $program = $this->program([
            'college_id' => $college->id,
            'annual_target_hours' => 10,
            'allocated_budget' => 1000,
        ]);
        $this->activity($program, ['no_of_days' => 1.0, 'participants' => 5, 'trainors_snapshot' => 1]);

        // Hours carry an annual target; budget has none (owner decision
        // 2026-09-26) — it is measured against the allocation, so the column
        // reads "Budget utilization", never "Budget attainment".
        $this->actingAs($admin)->get('/targets')
            ->assertOk()
            ->assertSee($program->code)
            ->assertSee('Hours attainment')
            ->assertSee('Budget utilization');
    }

    /* ================================================================== */
    /* 9. the hub's activity form */
    /* ================================================================== */

    public function test_the_activity_form_accepts_days_and_participants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openActivityForm')
            ->set('activityForm.title', 'Half-day training')
            ->set('activityForm.planned_start_date', '2026-03-10')
            ->set('activityForm.planned_end_date', '2026-03-10')
            ->set('activityForm.no_of_days', '0.5')
            ->set('activityForm.participants', '141')
            ->set('activityForm.trainors_snapshot', '3')
            ->call('saveActivity')
            ->assertHasNoErrors();

        $activity = $program->activities()->where('title', 'Half-day training')->first();
        $this->assertSame(0.5, $activity->no_of_days);
        $this->assertSame(141, $activity->participants);
        $this->assertSame(3, $activity->trainors_snapshot);

        // 3 x 141 x 0.5 = 211.5 hrs — days is the duration carrier.
        $this->assertSame(211.5, $this->service()->forProject($program->fresh())['actual_hours']);
    }

    public function test_days_must_be_in_half_day_increments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        // 0.75 is not a real duration — you cannot deliver three quarters of a
        // day and have it mean anything the formula can use.
        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openActivityForm')
            ->set('activityForm.title', 'Bad duration')
            ->set('activityForm.planned_start_date', '2026-03-10')
            ->set('activityForm.planned_end_date', '2026-03-10')
            ->set('activityForm.no_of_days', '0.75')
            ->call('saveActivity')
            ->assertHasErrors('activityForm.no_of_days');
    }

    public function test_days_below_a_half_day_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openActivityForm')
            ->set('activityForm.title', 'Too short')
            ->set('activityForm.planned_start_date', '2026-03-10')
            ->set('activityForm.planned_end_date', '2026-03-10')
            ->set('activityForm.no_of_days', '0.25')
            ->call('saveActivity')
            ->assertHasErrors('activityForm.no_of_days');
    }

    public function test_clearing_participants_writes_null_not_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openActivityForm')
            ->set('activityForm.title', 'No manual count')
            ->set('activityForm.planned_start_date', '2026-03-10')
            ->set('activityForm.planned_end_date', '2026-03-10')
            ->set('activityForm.no_of_days', '1')
            ->set('activityForm.participants', '')
            ->call('saveActivity')
            ->assertHasNoErrors();

        // "Not recorded", not "nobody attended" — the source tag distinguishes
        // them, so the underlying column must stay NULL.
        $this->assertNull($program->activities()->where('title', 'No manual count')->first()->participants);
    }

    /* ================================================================== */
    /* 10. the activity table shows what the figures rest on */
    /* ================================================================== */

    public function test_the_activities_table_shows_the_trainee_source(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        $activity = $this->activity($program, ['no_of_days' => 1.0, 'participants' => 12]);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->assertSee('Training Hrs')
            ->assertSee('Days')
            // The manual-source badge tells the Director this figure is typed,
            // not imported.
            ->assertSee('manual');
    }

    /* ================================================================== */
    /* 11. the allocation IS the budget basis (no annual budget target) */
    /* ================================================================== */

    public function test_the_allocation_is_the_budget_basis_even_when_the_legacy_target_column_is_set(): void
    {
        // Owner decision 2026-09-26: a project has NO annual budget target — the
        // allocation is its only budget figure, so it is the denominator. A
        // legacy `annual_target_budget` value is RETAINED BUT UNREAD (the same
        // treatment D-R7 gives the 8.6 KPIs) and must NOT override the
        // allocation. This test is what stops the old fallback creeping back.
        $program = $this->program(['allocated_budget' => 50000, 'annual_target_budget' => null]);
        $this->assertSame(50000.0, $program->budgetAllocated());

        $program->update(['annual_target_budget' => 80000]);

        $this->assertSame(
            50000.0,
            $program->fresh()->budgetAllocated(),
            'The legacy annual_target_budget column must never become the budget basis again.'
        );
    }

    public function test_is_over_allocated_uses_the_allocation_not_the_legacy_target(): void
    {
        // A stored 5,000 legacy target must not mask a 1,500 spend against a
        // 1,000 allocation — otherwise "over" would silently stop firing.
        $program = $this->program(['allocated_budget' => 1000, 'annual_target_budget' => 5000]);

        BudgetUtilization::create([
            'extension_project_id' => $program->id,
            'item_name' => 'Over allocation',
            'amount' => 1500,
            'date_used' => '2026-03-01',
        ]);

        $this->assertTrue($program->fresh()->isOverAllocated());
    }
}
