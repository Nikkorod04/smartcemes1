<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\ProgramNarrative;
use App\Models\ProgramObjective;
use App\Models\User;
use App\Services\ProgramNarrativeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RedesignUiTest extends TestCase
{
    use RefreshDatabase;

    protected function program(array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
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

    protected function activity(ExtensionProject $program, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'extension_project_id' => $program->id,
            'title' => 'First Aid Training',
            'planned_start_date' => '2026-03-10',
            'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
        ], $overrides));
    }

    /**
     * A role that cannot reach a route must not be shown a link to it.
     *
     * `hub-overview`'s "University pool ->" pointed at `targets.index`, which is
     * admin-only, while the Targets & Allocation card carried NO role guard — so
     * faculty and secretary both saw a link that 403'd. Found by sweeping every
     * `route()` call in the views against each route's role middleware; nothing in
     * the suite could see it, because `RouteSurfaceTest` walks routes by role but
     * never inspects the links a page renders.
     */
    public function test_the_hub_hides_the_university_pool_link_from_non_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $lead = Faculty::factory()->create();
        $program = $this->program(['program_lead_id' => $lead->id]);

        $poolLink = route('targets.index');

        // The Director keeps it — that page is theirs.
        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->assertSee($poolLink);

        // Neither of the other two may be offered a link they cannot follow.
        Livewire::actingAs($secretary)->test(Hub::class, ['project' => $program])
            ->assertDontSee($poolLink);

        Livewire::actingAs($lead->user)->test(Hub::class, ['project' => $program])
            ->assertDontSee($poolLink);
    }

    public function test_admin_dashboard_renders_the_r5_target_kpis_and_performance_leaders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        // R4 / D-R7: the dashboard's KPI row now reports training hours against
        // the annual pool instead of the objective-status doughnut. The
        // ProgramObjective below is deliberately created to prove the dashboard
        // ignores it (retained unread, R-Q2).
        ProgramObjective::create([
            'extension_project_id' => $program->id,
            'objective' => 'Keep budget on target',
            'kpi_metric' => 'budget_utilization',
            'target_value' => 90,
        ]);
        BudgetUtilization::create([
            'extension_project_id' => $program->id,
            'item_name' => 'Materials',
            'amount' => 500,
            'date_used' => '2026-03-01',
        ]);

        $overAllocated = $this->program(['code' => 'EXT-2026-071', 'allocated_budget' => 100]);
        BudgetUtilization::create([
            'extension_project_id' => $overAllocated->id,
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

        // R5: the dashboard was rebuilt on the prototype's dash-* primitives.
        // The R4 KPI row and Performance Leaders survive; the 8.6 surfaces and
        // the R5-removed Community Reach chart do not.
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('Training Hours Rendered')
            ->assertSee('Budget Utilized of')
            ->assertSee('Pending Approvals')
            // R5 charts: exactly the two the prototype keeps (hours + budget).
            ->assertSee('Training Hours vs Target')
            ->assertSee('Budget Utilized vs Allocated Budget')
            /* The over-allocation colour moved OUT of the inline Chart.js config and
               into the stylesheet (owner request 2026-09-25) — the bullet row now
               marks the state with a class, so assert the STATE, not the hex.
               HANDA is seeded over its allocated budget, so it must appear. */
            ->assertSee('bullet-fill is-over', false)
            /* Stage 3 (owner request 2026-09-25): every leaderboard row now carries
               a magnitude bar, and the 🥇🥈🥉 medals are gone — the rank is `#N`. */
            ->assertSee('leader-bar-fill', false)
            ->assertDontSee('🥇')
            ->assertSee('#1')
            ->assertSee('mini-seg', false)
            // R5 §5 step 1: the two rankings the Director acts on.
            ->assertSee('Performance Leaders')
            ->assertSee('Most Performing Projects')
            ->assertSee('Most Performing Faculty')
            // R5: the Community Reach chart and its Others grouping are GONE.
            ->assertDontSee('Community Reach')
            ->assertDontSee('Others (2 barangays)')
            ->assertDontSee('Trainees Reached')
            // The Action Center was removed by the P0m prototype pass.
            ->assertDontSee('Action Center')
            ->assertDontSee('Objectives Achieved')
            ->assertDontSee('<h3>KPI Scorecard vs Targets</h3>', false);
    }

    public function test_dashboard_renders_narrative_summary_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        ProgramNarrative::create([
            'extension_project_id' => $program->id,
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

    public function test_hub_performance_renders_training_hours_and_budget_against_targets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        // R4 / D-R7: the 8.6 KPI tiles are gone. This is the replacement — the
        // quantities the Director tracks. Hours carry an annual target; budget
        // does NOT (owner decision 2026-09-26) — its allocation is the basis.
        $program->update(['annual_target_hours' => 100]);

        foreach ([['attendance_consistency', 80], ['community_reach', 50]] as [$metric, $target]) {
            ProgramObjective::create([
                'extension_project_id' => $program->id,
                'objective' => 'Objective '.$metric,
                'kpi_metric' => $metric,
                'target_value' => $target,
            ]);
        }

        $activity = $this->activity($program, [
            'pre_assessment_score' => 40,
            'post_assessment_score' => 55,
            'satisfaction_rating' => 4.5,
            'no_of_days' => 1.0,
            'participants' => 10,
        ]);
        $beneficiary = $this->beneficiary(1, 'San Jose');
        Attendance::create([
            'activity_id' => $activity->id,
            'beneficiary_id' => $beneficiary->id,
            'attendance_date' => '2026-03-10',
            'status' => 'present',
        ]);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->assertSee('Training Hours vs Annual Target')
            ->assertSee('Budget vs Allocated Budget')
            ->assertSee('Training hours rendered')
            ->assertSee('Trainors assigned')
            ->assertSee('Trainees / beneficiaries reached')
            ->assertSee('Activities completed')
            ->assertSee('Attendance per Activity')
            // R4 / D-R3: the formula is stated on the surface, not the 8.6 vocabulary.
            ->assertSee('no × 8')
            ->assertSee('completed activit')
            ->assertDontSee('KPI Scorecard vs Targets')
            ->assertDontSee('Cost per Beneficiary')
            // 2026-09-28: the Overview keeps exactly ONE budget surface — the chart.
            // The Budget stat tile and the 4-row key/value list under the chart were
            // both removed as duplicates of it. The chart is now a doughnut (owner
            // decision; it carries a documented caveat — see hub.blade.php).
            ->assertDontSee('utilized of allocated budget')
            ->assertDontSee('Utilized to date')
            ->assertSee("type: 'doughnut'", false)
            ->assertDontSee('Knowledge Gain');
    }

    public function test_hub_performance_states_no_target_rather_than_printing_zero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program(); // no annual targets set

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->assertSee('Training Hours vs Annual Target')
            ->assertSee('No annual target set')
            ->assertDontSee('KPI Scorecard vs Targets');
    }

    protected function completedNarrative(ExtensionProject $program, User $admin): ProgramNarrative
    {
        return ProgramNarrative::create([
            'extension_project_id' => $program->id,
            'generated_by' => $admin->id,
            'status' => 'completed',
            'summary' => 'BUSOG is progressing well across both feeding cycles.',
            'health_label' => 'on-track',
            'risks' => ['Rice supply volatility may affect cycle 3.'],
            'recommendations' => [
                ['priority' => 'High', 'action' => 'Lock in a second rice supplier', 'rationale' => 'Single-supplier dependency flagged.'],
            ],
            // THE REAL PAYLOAD SHAPE. `ProgramAggregates::build()` emits exactly these
            // four keys (`project`, `training`, `budget`, `activities`).
            //
            // This fixture used to fabricate the PRE-R5 shape — `program`, `objectives`,
            // `kpis` — and the modal test asserted an objective ('Reach 30 pupils')
            // rendered. That is why the view's reads of those dead keys went unnoticed
            // for so long: the fixture SUPPLIED them, so the suite stayed green while
            // production showed "0 objectives" and a blank Period, and the test would
            // have FAILED if the view were corrected to match reality.
            'raw_extracted_data' => [
                'project' => [
                    'code' => $program->code,
                    'title' => $program->title,
                    'status' => 'ongoing',
                    'college' => null,
                    'period' => '2026-01-01 to 2026-12-31',
                    'communities' => [],
                ],
                'training' => [
                    'training_hours' => 30.0,
                    'completed_hours' => 30.0,
                    'training_days' => 1.0,
                    'trainors' => 3,
                    'trainees' => 30,
                    'avg_hours_per_completed' => 30.0,
                    'target_hours' => 60.0,
                    'hours_attainment_pct' => 50,
                    'formula' => 'trainors x trainees x days (no x 8)',
                    'trainee_sources' => ['attendance' => 1],
                ],
                'budget' => ['allocated' => 85000, 'utilized' => 78000, 'utilization_pct' => 92, 'over_allocated' => false],
                'activities' => ['total' => 3, 'completed' => 2, 'overdue' => 0],
            ],
            'confidence_score' => 0.82,
            'metadata' => ['model' => 'gemini-3.6-flash', 'prompt_version' => 'v1', 'confidence_basis' => 'derived'],
            'generated_at' => now(),
        ]);
    }

    public function test_hub_full_narrative_modal_shows_risks_actions_and_aggregates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        $this->completedNarrative($program, $admin);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->assertSee('View full narrative')
            ->assertSee('Generate narrative')
            ->assertSee('Read the full narrative')
            ->assertSeeHtml('wire:loading.flex')
            ->assertSee('Generating extension project narrative')
            ->assertDontSee('Data the AI reviewed')
            ->call('openNarrativeModal')
            ->assertSet('showNarrativeModal', true)
            ->assertSee('Data the AI reviewed')
            ->assertSee('Rice supply volatility may affect cycle 3.')
            ->assertSee('Lock in a second rice supplier')
            ->assertSee('confidence 0.82')
            ->assertSee('₱85,000 allocated')
            // The D3 accordion now reads the keys the payload ACTUALLY carries: the
            // period lives under `project` (not `program`), and the retired objectives
            // list is replaced by the `training` block the narrative is built from.
            ->assertSee('2026-01-01 to 2026-12-31')
            ->assertSee('Training reviewed')
            ->assertSee('50% of the 60-hr annual target')
            ->assertSee('trainee figures rest on 1 activity from attendance')
            ->assertSee('Current figures')
            ->assertDontSee('Objectives reviewed')
            ->assertDontSee('0 objectives')
            ->call('closeNarrativeModal')
            ->assertSet('showNarrativeModal', false);
    }

    public function test_hub_narrative_generation_exposes_a_success_result(): void
    {
        config(['smartcemes.ai.key' => 'test-key']);
        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'summary' => 'On pace.',
                        'health_label' => 'on-track',
                        'risks' => [],
                        'recommendations' => [],
                    ])]]],
                ]],
                'usageMetadata' => ['totalTokenCount' => 500],
            ]),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('generateNarrative')
            ->assertSet('generationResult', 'success')
            ->assertSee('Project narrative generated')
            ->assertSee('View Narrative')
            ->assertSee('View full narrative');
    }

    public function test_hub_canceled_generation_is_recorded_and_not_published(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        $narrative = ProgramNarrative::create([
            'extension_project_id' => $program->id,
            'generated_by' => $admin->id,
            'status' => ProgramNarrative::STATUS_PENDING,
            'metadata' => [],
        ]);

        $service = \Mockery::mock(ProgramNarrativeService::class);
        $service->shouldReceive('generateFor')->once()->andReturnUsing(function () use ($narrative, $admin) {
            Cache::put('program-narrative:generation-cancel:'.$admin->id, true, now()->addMinutes(10));

            return $narrative;
        });
        $this->app->instance(ProgramNarrativeService::class, $service);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('generateNarrative')
            ->assertSet('generationResult', 'canceled')
            ->assertSee('Generation canceled');

        $narrative->refresh();
        $this->assertSame(ProgramNarrative::STATUS_FAILED, $narrative->status);
        $this->assertSame('Narrative generation canceled by the Director.', $narrative->error_message);
        $this->assertNull($narrative->summary);
    }

    public function test_hub_hides_full_narrative_button_without_completed_narrative(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        ProgramNarrative::create([
            'extension_project_id' => $program->id,
            'generated_by' => $admin->id,
            'status' => 'failed',
            'error_message' => 'Narrative unavailable — API quota exceeded.',
            'generated_at' => now(),
            'metadata' => ['model' => 'gemini-3.6-flash'],
        ]);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->assertSee('Narrative unavailable')
            ->assertDontSee('View full narrative')
            ->assertDontSee('Read the full narrative');
    }

    public function test_hub_narrative_modal_is_admin_only(): void
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0099']);
        $program = $this->program(['program_lead_id' => $faculty->id]);

        Livewire::actingAs($user)->test(Hub::class, ['project' => $program])
            ->call('openNarrativeModal')
            ->assertForbidden();
    }
}
