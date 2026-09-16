<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\ExtensionProgram;
use App\Models\ProgramNarrative;
use App\Models\ProgramObjective;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RedesignUiTest extends TestCase
{
    use RefreshDatabase;

    protected function program(array $overrides = []): ExtensionProgram
    {
        return ExtensionProgram::create(array_merge([
            'code' => 'EXT-2026-070',
            'title' => 'Redesign Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 1000,
            'target_beneficiaries' => 100,
        ], $overrides));
    }

    protected function beneficiary(int $i, string $barangay): Beneficiary
    {
        return Beneficiary::create([
            'first_name' => 'Ben'.$i,
            'last_name' => 'Test',
            'barangay' => $barangay,
            'municipality' => 'Tacloban City',
            'beneficiary_category' => 'Farmer',
            'gender' => 'Female',
        ]);
    }

    protected function activity(ExtensionProgram $program, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'extension_program_id' => $program->id,
            'title' => 'First Aid Training',
            'planned_start_date' => '2026-03-10',
            'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
        ], $overrides));
    }

    public function test_admin_dashboard_renders_kpi_markers_and_top10_reach(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        ProgramObjective::create([
            'extension_program_id' => $program->id,
            'objective' => 'Keep budget on target',
            'kpi_metric' => 'budget_utilization',
            'target_value' => 90,
        ]);
        BudgetUtilization::create([
            'extension_program_id' => $program->id,
            'item_name' => 'Materials',
            'amount' => 500,
            'date_used' => '2026-03-01',
        ]);

        $overAllocated = $this->program(['code' => 'EXT-2026-071', 'allocated_budget' => 100]);
        BudgetUtilization::create([
            'extension_program_id' => $overAllocated->id,
            'item_name' => 'Overrun',
            'amount' => 150,
            'date_used' => '2026-03-02',
        ]);

        $activity = $this->activity($program);
        foreach (range(1, 12) as $i) {
            $beneficiary = $this->beneficiary($i, 'Barangay '.$i);
            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => $beneficiary->id,
                'attendance_date' => '2026-03-10',
                'status' => 'present',
            ]);
        }

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('Objectives Achieved')
            ->assertSee('doughnut', false)
            ->assertSee('Others (2 barangays)')
            ->assertSee('left:90%')
            ->assertSee('#ef4444', false)
            ->assertSee('mini-seg', false);
    }

    public function test_dashboard_renders_narrative_summary_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        ProgramNarrative::create([
            'extension_program_id' => $program->id,
            'generated_by' => $admin->id,
            'status' => 'completed',
            'summary' => 'Households trained on disaster preparedness with strong uptake.',
            'health_label' => 'on-track',
            'generated_at' => now(),
            'metadata' => ['model' => 'gemini-3.6-flash'],
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('Households trained on disaster preparedness')
            ->assertSee('gemini-3.6-flash');
    }

    public function test_hub_scorecard_renders_live_metrics_with_objective_markers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        foreach ([['attendance_consistency', 80], ['community_reach', 50]] as [$metric, $target]) {
            ProgramObjective::create([
                'extension_program_id' => $program->id,
                'objective' => 'Objective '.$metric,
                'kpi_metric' => $metric,
                'target_value' => $target,
            ]);
        }

        $activity = $this->activity($program, [
            'pre_assessment_score' => 40,
            'post_assessment_score' => 55,
            'satisfaction_rating' => 4.5,
        ]);
        $beneficiary = $this->beneficiary(1, 'San Jose');
        Attendance::create([
            'activity_id' => $activity->id,
            'beneficiary_id' => $beneficiary->id,
            'attendance_date' => '2026-03-10',
            'status' => 'present',
        ]);

        Livewire::actingAs($admin)->test(Hub::class, ['program' => $program])
            ->assertSee('KPI Scorecard vs Targets')
            ->assertSee('Attendance Consistency')
            ->assertSee('left:80%')
            ->assertSee('4.5/5')
            ->assertSee('Attendees')
            ->assertSee("indexAxis: 'y'", false)
            ->assertSee('+15.0 pts')
            ->assertSee('Cost per Beneficiary');
    }

    public function test_hub_scorecard_hides_markers_without_objective_targets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)->test(Hub::class, ['program' => $program])
            ->assertSee('KPI Scorecard vs Targets')
            ->assertDontSee('target ≥')
            ->assertDontSee('class="marker"', false)
            ->assertSee('No completed activities with attendance yet.');
    }
}
