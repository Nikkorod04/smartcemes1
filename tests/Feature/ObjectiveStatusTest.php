<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\ExtensionProject;
use App\Models\ProgramObjective;
use App\Models\User;
use App\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 8.6 conformance: objective status/progress ALWAYS derives live from the
 * effective actual — never the stale stored status column — on every
 * consumer (dashboard, narratives, scheduler, hub manager).
 *
 * The Analytics page was a consumer until it was removed 2026-09-27; its one
 * derivation test went with it. `objectivesAtRisk()` now has no production
 * caller at all (see KpiService) — only this file.
 */
class ObjectiveStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Base project row; `$overrides` WIN over every default below.
     *
     * `array_merge`, not `+`: the PHP union operator keeps the LEFT operand's
     * value for any shared key, so `$defaults + $overrides` silently ignored
     * every override — a test that set `planned_start_date` got the default
     * `2026-01-01` instead. That made the scheduler's own date-window maths
     * (which reads the stored dates) disagree with the test's intent.
     */
    protected function makeProgram(array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => 'EXT-2026-030',
            'title' => 'Objective Status Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2027-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 0,
        ], $overrides));
    }

    /** Program with 1 completed activity + 1 present beneficiary → live community_reach = 1. */
    protected function programWithLiveData(): ExtensionProject
    {
        $program = $this->makeProgram();
        $activity = Activity::create([
            'extension_project_id' => $program->id, 'title' => 'Live Act',
            'planned_start_date' => '2026-09-01', 'planned_end_date' => '2026-09-01',
            'start_time' => '08:00:00', 'end_time' => '11:00:00', 'status' => 'completed',
        ]);
        $beneficiary = Beneficiary::factory()->create();
        Attendance::create([
            'activity_id' => $activity->id, 'beneficiary_id' => $beneficiary->id,
            'attendance_date' => '2026-09-01', 'status' => 'present',
        ]);

        return $program;
    }

    /* ==================== derivation (KpiService) ==================== */

    public function test_numeric_objective_status_derives_live_not_from_stored_column(): void
    {
        $program = $this->programWithLiveData();
        // Stored status deliberately stale ('not_started'); live data makes it achieved.
        $objective = ProgramObjective::create([
            'extension_project_id' => $program->id,
            'objective' => 'Reach 1 household',
            'kpi_metric' => 'community_reach',
            'baseline_value' => 0,
            'target_value' => 1,
            'unit' => 'households',
            'target_date' => '2026-12-31',
            'status' => 'not_started',
        ]);

        $kpi = app(KpiService::class);

        $this->assertSame('live', $kpi->actualSource($objective));
        $this->assertSame(1.0, $kpi->effectiveActual($objective));
        $this->assertSame('achieved', $kpi->statusFor($objective));
        $this->assertSame('not_started', $objective->refresh()->status, 'the stored column is never mutated');
    }

    public function test_qualitative_objective_derives_from_manual_actual(): void
    {
        $program = $this->makeProgram();

        $achieved = ProgramObjective::create([
            'extension_project_id' => $program->id,
            'objective' => 'Form the association', 'kpi_metric' => null,
            'target_value' => 1, 'actual_value' => 1,
            'target_date' => now()->addDays(30)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        $notMet = ProgramObjective::create([
            'extension_project_id' => $program->id,
            'objective' => 'Form the cooperative', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->subDay()->format('Y-m-d'), 'status' => 'not_started',
        ]);

        $kpi = app(KpiService::class);

        $this->assertSame('manual', $kpi->actualSource($achieved));
        $this->assertSame('achieved', $kpi->statusFor($achieved));
        $this->assertSame('not_met', $kpi->statusFor($notMet));
    }

    public function test_objectives_at_risk_returns_derived_not_met_and_imminent_on_track(): void
    {
        $program = $this->makeProgram();

        ProgramObjective::create([ // past due, below target → not_met (included)
            'extension_project_id' => $program->id,
            'objective' => 'Overdue one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->subDays(3)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // on track, due in 7 days → included
            'extension_project_id' => $program->id,
            'objective' => 'Imminent one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(7)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // on track, due in 60 days → excluded
            'extension_project_id' => $program->id,
            'objective' => 'Distant one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(60)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // no actual yet → not_started → excluded
            'extension_project_id' => $program->id,
            'objective' => 'Untouched one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => null,
            'target_date' => now()->subDays(3)->format('Y-m-d'), 'status' => 'not_started',
        ]);

        $atRisk = app(KpiService::class)->objectivesAtRisk();

        $this->assertSame(['Overdue one', 'Imminent one'], $atRisk->pluck('objective')->all());
    }

    /* ==================== dashboard / narratives ==================== */

    public function test_dashboard_dropped_the_action_center_and_its_objective_guardrail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();
        ProgramObjective::create([ // stale 'not_started', derived not_met
            'extension_project_id' => $program->id,
            'objective' => 'Overdue objective', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->subDay()->format('Y-m-d'), 'status' => 'not_started',
        ]);

        // R4 / D-R7 removed the objective panel; R5 removed the whole Action
        // Center with the prototype pass (P0m). What survives is the KPI row's
        // budget tile, which is where the annual-budget guardrail now lives.
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Budget Utilized of')
            ->assertDontSee('Objectives at risk')
            ->assertDontSee('Overdue objective')
            ->assertDontSee('Action Center');
    }

    public function test_narratives_surface_training_hours_not_the_objectives_met_chip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram(['annual_target_hours' => 100]);
        ProgramObjective::create([ // stale 'not_started', derived achieved (manual ≥ target)
            'extension_project_id' => $program->id,
            'objective' => 'Form the association', 'kpi_metric' => null,
            'target_value' => 1, 'actual_value' => 1,
            'target_date' => now()->addDays(30)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // not achieved
            'extension_project_id' => $program->id,
            'objective' => 'Form the cooperative', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(30)->format('Y-m-d'), 'status' => 'not_started',
        ]);

        // R5 / D-R7: the objectives-met chip was an 8.6 surface. The page shows
        // training-hours attainment against the annual target instead.
        //
        // Re-pointed 2026-10-07 (§34): the page was redesigned — the attainment
        // now sits in the card's always-visible metric strip rather than in a
        // standalone badge, so the exact old string ("Training hours: 0.0 / 100
        // (0%)") no longer exists. The ASSERTION'S INTENT is unchanged and both
        // halves are still pinned: the retired chip is absent, and the hours
        // attainment against the 100-hour target is present.
        $this->actingAs($admin)->get('/program-narratives')
            ->assertOk()
            ->assertDontSee('Objectives met')
            ->assertSee('training hrs')
            ->assertSee('0.0')
            ->assertSee('0% of 100-hr target');
    }

    /* ==================== deadline scheduler (5.14) ==================== */

    public function test_scheduler_notifies_a_training_hours_shortfall(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // R5 / D-R7: the scheduler used to alert on OBJECTIVE target dates whose
        // 8.6 status derived as `on_track`. It now alerts on a training-hours
        // shortfall — the actionable signal under the R4 target model.
        // A project already past its midpoint with only a sliver rendered.
        $program = $this->makeProgram([
            'annual_target_hours' => 100,
            'planned_start_date' => now()->subDays(80)->format('Y-m-d'),
            'planned_end_date' => now()->addDays(20)->format('Y-m-d'),
            'status' => 'ongoing',
        ]);
        Activity::create([
            'extension_project_id' => $program->id, 'title' => 'Shortfall Act',
            'planned_start_date' => now()->subDays(10)->format('Y-m-d'),
            'planned_end_date' => now()->subDays(10)->format('Y-m-d'),
            'start_time' => '08:00:00', 'end_time' => '11:00:00', 'status' => 'completed',
            'no_of_days' => 1.0, 'participants' => 2, 'trainors_snapshot' => 1,
        ]);

        $this->artisan('smartcemes:notify-deadlines')
            ->expectsOutput('Deadline notifications sent: 1.')
            ->assertSuccessful();

        $this->assertSame(1, $admin->unreadNotifications->count());
        $this->assertStringContainsString(
            'behind schedule',
            $admin->unreadNotifications->first()->data['title'] ?? ''
        );
    }

    public function test_scheduler_stays_silent_without_a_target_or_a_shortfall(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // No annual target at all: an unset target is an absence of data, not a
        // shortfall — the UI says "no target set" and the scheduler says nothing.
        $program = $this->makeProgram([
            'planned_start_date' => now()->subDays(80)->format('Y-m-d'),
            'planned_end_date' => now()->addDays(20)->format('Y-m-d'),
            'status' => 'ongoing',
        ]);
        Activity::create([
            'extension_project_id' => $program->id, 'title' => 'No Target Act',
            'planned_start_date' => now()->subDays(10)->format('Y-m-d'),
            'planned_end_date' => now()->subDays(10)->format('Y-m-d'),
            'start_time' => '08:00:00', 'end_time' => '11:00:00', 'status' => 'completed',
            'no_of_days' => 1.0, 'participants' => 2, 'trainors_snapshot' => 1,
        ]);

        $this->artisan('smartcemes:notify-deadlines')
            ->expectsOutput('Deadline notifications sent: 0.')
            ->assertSuccessful();

        $this->assertSame(0, $admin->unreadNotifications->count());
    }

    /* ==================== R4 / D-R7: the 8.6 surface is GONE ==================== */
    /*
     * Phase R4 removed the 8.6 KPI dictionary from every project-level surface.
     * The four tests below used to assert that the OBJECTIVE UI rendered those
     * values; R4's exit criteria require the opposite. They are inverted rather
     * than deleted, so a regression that quietly restores the tiles fails here.
     *
     * The CRUD methods on Hub and the ProgramObjective model are RETAINED
     * UNREAD (R-Q2) — the model/derivation tests at the top of this file still
     * cover them, which is why this class keeps importing ProgramObjective.
     */

    public function test_hub_no_longer_renders_the_objective_manager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData();
        ProgramObjective::create([
            'extension_project_id' => $program->id,
            'objective' => 'Reach 1 household', 'kpi_metric' => 'community_reach',
            'baseline_value' => 0, 'target_value' => 1, 'unit' => 'households',
            'target_date' => '2026-12-31', 'status' => 'not_started',
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->assertSee('Targets & Allocation')
            ->assertDontSee('Results Framework')
            ->assertDontSee('Objective Manager')
            ->assertDontSee('Reach 1 household');
    }

    public function test_hub_overview_renders_the_target_model_not_the_kpi_dictionary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData();

        // The R4 replacement is target-based, so state the guardrails on-screen.
        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->assertSee('Training hours rendered')
            ->assertSee('Budget utilized')
            ->assertSee('Trainors assigned')
            ->assertSee('Trainees reached')
            // D-R5's guardrail is stated on the card: broad programs carry no
            // target, so the Director never expects one to appear.
            ->assertSee('Broad programs carry')
            ->assertSee('D-R5');
    }

    public function test_hub_no_longer_offers_the_objective_crud_affordances(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();

        // The methods survive for compatibility, but nothing in the view calls
        // them — the "Manage" button and the add-objective modal are gone.
        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->assertDontSee('Add objective')
            ->assertDontSee('Manage')
            ->assertDontSee('KPI metric');
    }

    public function test_objective_crud_methods_remain_callable_for_compatibility(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();

        // R-Q2: retained unread. The state flags still exist and still flip, so
        // R5/R6 can revive the surface without re-writing this logic.
        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openObjManager')
            ->assertSet('showObjList', true);
    }

    public function test_switching_to_a_kpi_metric_clears_the_manual_actual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();

        // The retained form state still behaves correctly even though no view
        // renders it — this is the contract R5/R6 would inherit.
        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('newObjective')
            ->set('objForm.actual_value', '3')
            ->set('objForm.kpi_metric', 'participation_rate')
            ->assertSet('objForm.actual_value', '');
    }

    public function test_saving_a_numeric_objective_never_stores_a_manual_actual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('newObjective')
            ->set('objForm.objective', 'Reach more households')
            ->set('objForm.kpi_metric', 'community_reach')
            ->set('objForm.unit', 'households')
            ->set('objForm.target_value', '5')
            ->set('objForm.actual_value', '9') // must be discarded for numeric objectives
            ->call('saveObjective')
            ->assertSet('showObjForm', false);

        $objective = ProgramObjective::query()->where('extension_project_id', $program->id)->first();
        $this->assertNotNull($objective);
        $this->assertSame('community_reach', $objective->kpi_metric);
        $this->assertNull($objective->actual_value);
    }

    public function test_objective_create_and_delete_write_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('newObjective')
            ->set('objForm.objective', 'Logged objective')
            ->set('objForm.kpi_metric', 'community_reach')
            ->set('objForm.target_value', '5')
            ->call('saveObjective');

        $this->assertSame(1, DB::table('activity_log')
            ->where('event', 'objective_create')
            ->where('subject_type', ExtensionProject::class)
            ->where('subject_id', $program->id)
            ->count());

        $objective = ProgramObjective::query()->where('extension_project_id', $program->id)->first();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('deleteObjective', $objective->id);

        $this->assertSame(1, DB::table('activity_log')
            ->where('event', 'objective_delete')
            ->where('subject_type', ExtensionProject::class)
            ->where('subject_id', $program->id)
            ->count());
    }
}
