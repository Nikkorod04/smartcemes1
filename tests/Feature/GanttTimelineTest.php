<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\BudgetUtilization;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GanttTimelineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * D-G1: the Activities tab can flip between the sc-table grid and a
     * read-only Gantt timeline. The timeline shares the grid's dataset and is
     * framed by the PROJECT's planned window — so an activity that runs past
     * the project end visually overflows the frame instead of stretching it.
     */
    protected function program(array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => 'EXT-2026-090',
            'title' => 'Gantt Timeline Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 100000,
            'target_beneficiaries' => 100,
        ], $overrides));
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
            'status' => 'ongoing',
        ], $overrides));
    }

    public function test_the_grid_is_the_default_activities_view(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        $this->activity($program);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->assertSeeHtml('<table class="sc-table">')
            ->assertDontSee('Day-proportional');
    }

    public function test_the_gantt_view_frames_the_project_window_and_lists_activities(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        // Future dates so the bar renders as planned/ongoing — a past date
        // would (correctly) paint it delayed instead.
        $this->activity($program, ['planned_start_date' => '2026-12-01', 'planned_end_date' => '2026-12-05']);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'gantt')
            ->assertSee('First Aid Training')
            ->assertSee('Jan 2026')
            ->assertSee('Dec 2026')
            ->assertSee('· Ongoing ·');
    }

    public function test_a_completed_activity_uses_the_completed_bar_color(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        $this->activity($program, ['status' => 'completed']);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'gantt')
            // The bar tooltip carries the state, so this can't be satisfied
            // by the (always-rendered) legend alone.
            ->assertSee('· Completed ·')
            ->assertSee('bg-emerald-500');
    }

    public function test_an_overdue_activity_is_painted_delayed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        // The suite's clock (2026) means a 2026-01 activity with an end date
        // before "today" and no completion is delayed — the state no other
        // hub surface shows.
        $this->activity($program, ['planned_start_date' => '2026-01-10', 'planned_end_date' => '2026-01-12', 'status' => 'ongoing']);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'gantt')
            ->assertSee('· Delayed ·')
            ->assertSee('bg-red-500');
    }

    public function test_an_activity_running_past_the_project_end_overflows_the_frame(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        // Overflows BOTH frame edges: start before the project window, end
        // after it — so the bar must clamp to left 0 / width 100% and show
        // the chevron markers.
        $this->activity($program, ['planned_start_date' => '2025-11-01', 'planned_end_date' => '2027-02-15']);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'gantt')
            ->assertSee('width: 100%')
            ->assertSee('bg-charcoal p-0.5 text-white');
    }

    public function test_the_week_scale_relabels_the_header_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();
        $this->activity($program);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'gantt')
            ->call('setGanttScale', 'week')
            ->assertSee('Mar 2')
            ->assertDontSee('Dec 2026');
    }

    public function test_unknown_view_and_scale_values_are_ignored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'kanban')
            ->call('setGanttScale', 'quarter')
            ->assertSeeHtml('<table class="sc-table">');
    }

    public function test_faculty_see_read_only_bars_without_edit_targets(): void
    {
        $lead = Faculty::factory()->create();
        $program = $this->program(['program_lead_id' => $lead->id]);
        $this->activity($program);

        Livewire::actingAs($lead->user)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'activities')
            ->call('setActivitiesView', 'gantt')
            ->assertSee('First Aid Training')
            ->assertDontSeeHtml('openActivityForm(');
    }
}
