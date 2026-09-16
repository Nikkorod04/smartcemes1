<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\ExtensionProgram;
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
 * consumer (dashboard, analytics, narratives, scheduler, hub manager).
 */
class ObjectiveStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProgram(array $overrides = []): ExtensionProgram
    {
        return ExtensionProgram::create([
            'code' => 'EXT-2026-030',
            'title' => 'Objective Status Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2027-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 0,
        ] + $overrides);
    }

    /** Program with 1 completed activity + 1 present beneficiary → live community_reach = 1. */
    protected function programWithLiveData(): ExtensionProgram
    {
        $program = $this->makeProgram();
        $activity = Activity::create([
            'extension_program_id' => $program->id, 'title' => 'Live Act',
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
            'extension_program_id' => $program->id,
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
            'extension_program_id' => $program->id,
            'objective' => 'Form the association', 'kpi_metric' => null,
            'target_value' => 1, 'actual_value' => 1,
            'target_date' => now()->addDays(30)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        $notMet = ProgramObjective::create([
            'extension_program_id' => $program->id,
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
            'extension_program_id' => $program->id,
            'objective' => 'Overdue one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->subDays(3)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // on track, due in 7 days → included
            'extension_program_id' => $program->id,
            'objective' => 'Imminent one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(7)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // on track, due in 60 days → excluded
            'extension_program_id' => $program->id,
            'objective' => 'Distant one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(60)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // no actual yet → not_started → excluded
            'extension_program_id' => $program->id,
            'objective' => 'Untouched one', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => null,
            'target_date' => now()->subDays(3)->format('Y-m-d'), 'status' => 'not_started',
        ]);

        $atRisk = app(KpiService::class)->objectivesAtRisk();

        $this->assertSame(['Overdue one', 'Imminent one'], $atRisk->pluck('objective')->all());
    }

    /* ==================== dashboard / analytics / narratives ==================== */

    public function test_dashboard_action_center_lists_derived_at_risk_objective(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();
        ProgramObjective::create([ // stale 'not_started', derived not_met
            'extension_program_id' => $program->id,
            'objective' => 'Overdue objective', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->subDay()->format('Y-m-d'), 'status' => 'not_started',
        ]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Objectives at risk')
            ->assertSee($program->code.' · Overdue objective')
            ->assertSee('Target date');
    }

    public function test_analytics_objective_chart_uses_derived_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData();
        ProgramObjective::create([ // stale 'not_started', derived achieved
            'extension_program_id' => $program->id,
            'objective' => 'Reach 1 household', 'kpi_metric' => 'community_reach',
            'target_value' => 1, 'target_date' => '2026-12-31', 'status' => 'not_started',
        ]);

        $this->actingAs($admin)->get('/analytics')
            ->assertOk()
            ->assertSee('"label":"Objectives","data":[1,0,0,0]');
    }

    public function test_narratives_objectives_met_chip_uses_derived_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();
        ProgramObjective::create([ // stale 'not_started', derived achieved (manual ≥ target)
            'extension_program_id' => $program->id,
            'objective' => 'Form the association', 'kpi_metric' => null,
            'target_value' => 1, 'actual_value' => 1,
            'target_date' => now()->addDays(30)->format('Y-m-d'), 'status' => 'not_started',
        ]);
        ProgramObjective::create([ // not achieved
            'extension_program_id' => $program->id,
            'objective' => 'Form the cooperative', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(30)->format('Y-m-d'), 'status' => 'not_started',
        ]);

        $this->actingAs($admin)->get('/program-narratives')
            ->assertOk()
            ->assertSee('Objectives met: 1/2');
    }

    /* ==================== deadline scheduler (5.14) ==================== */

    public function test_scheduler_notifies_derived_on_track_objective_approaching_deadline(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram(); // ends 2027 — program-ending path silent
        ProgramObjective::create([ // stale 'not_started', derived on_track, due in 7 days
            'extension_program_id' => $program->id,
            'objective' => 'Imminent objective', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => 2,
            'target_date' => now()->addDays(7)->format('Y-m-d'), 'status' => 'not_started',
        ]);

        $this->artisan('smartcemes:notify-deadlines')
            ->expectsOutput('Deadline notifications sent: 1.')
            ->assertSuccessful();

        $this->assertSame(1, $admin->unreadNotifications->count());
    }

    public function test_scheduler_skips_derived_not_started_objectives(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();
        ProgramObjective::create([ // no actual → derived not_started → no notification
            'extension_program_id' => $program->id,
            'objective' => 'Untouched objective', 'kpi_metric' => null,
            'target_value' => 5, 'actual_value' => null,
            'target_date' => now()->addDays(7)->format('Y-m-d'), 'status' => 'on_track',
        ]);

        $this->artisan('smartcemes:notify-deadlines')
            ->expectsOutput('Deadline notifications sent: 0.')
            ->assertSuccessful();

        $this->assertSame(0, $admin->unreadNotifications->count());
    }

    /* ==================== hub objective manager ==================== */

    public function test_manager_modal_renders_derived_status_progress_and_source(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData();
        ProgramObjective::create([ // stale 'not_started', derived achieved (live)
            'extension_program_id' => $program->id,
            'objective' => 'Reach 1 household', 'kpi_metric' => 'community_reach',
            'baseline_value' => 0, 'target_value' => 1, 'unit' => 'households',
            'target_date' => '2026-12-31', 'status' => 'not_started',
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('openObjManager')
            ->assertSet('showObjList', true)
            ->assertSee('Objective Manager')
            ->assertSee('Reach 1 household')
            ->assertSee('Achieved')
            ->assertSeeHtml('class="progress mt-2"')
            ->assertSee('auto (live)')
            ->assertSee('Baseline 0');
    }

    public function test_manual_actual_field_only_renders_for_qualitative_objectives(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('newObjective')
            ->assertSet('showObjForm', true)
            ->assertSee('Manual actual (qualitative)')
            ->set('objForm.kpi_metric', 'community_reach')
            ->assertDontSee('Manual actual (qualitative)')
            ->assertSee('Currently computed · live');
    }

    public function test_live_computed_preview_shows_the_program_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->programWithLiveData(); // community_reach = 1

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('newObjective')
            ->set('objForm.unit', 'households')
            ->set('objForm.kpi_metric', 'community_reach')
            ->assertSee('1.0 households');
    }

    public function test_switching_to_a_kpi_metric_clears_the_manual_actual(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
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
            ->test(Hub::class, ['program' => $program])
            ->call('newObjective')
            ->set('objForm.objective', 'Reach more households')
            ->set('objForm.kpi_metric', 'community_reach')
            ->set('objForm.unit', 'households')
            ->set('objForm.target_value', '5')
            ->set('objForm.actual_value', '9') // must be discarded for numeric objectives
            ->call('saveObjective')
            ->assertSet('showObjForm', false);

        $objective = ProgramObjective::query()->where('extension_program_id', $program->id)->first();
        $this->assertNotNull($objective);
        $this->assertSame('community_reach', $objective->kpi_metric);
        $this->assertNull($objective->actual_value);
    }

    public function test_objective_create_and_delete_write_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->makeProgram();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('newObjective')
            ->set('objForm.objective', 'Logged objective')
            ->set('objForm.kpi_metric', 'community_reach')
            ->set('objForm.target_value', '5')
            ->call('saveObjective');

        $this->assertSame(1, DB::table('activity_log')
            ->where('event', 'objective_create')
            ->where('subject_type', ExtensionProgram::class)
            ->where('subject_id', $program->id)
            ->count());

        $objective = ProgramObjective::query()->where('extension_program_id', $program->id)->first();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['program' => $program])
            ->call('deleteObjective', $objective->id);

        $this->assertSame(1, DB::table('activity_log')
            ->where('event', 'objective_delete')
            ->where('subject_type', ExtensionProgram::class)
            ->where('subject_id', $program->id)
            ->count());
    }
}
