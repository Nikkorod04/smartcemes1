<?php

namespace Tests\Feature;

use App\Livewire\Calendar;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\User;
use App\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase4Test extends TestCase
{
    use RefreshDatabase;

    protected function facultyAccount(): array
    {
        $user = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $user->id, 'employee_id' => 'LNU-2026-0099']);

        return [$user, $faculty];
    }

    public function test_admin_dashboard_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_secretary_dashboard_renders(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);

        $this->actingAs($secretary)->get('/dashboard')->assertOk();
    }

    public function test_faculty_dashboard_renders(): void
    {
        [$user] = $this->facultyAccount();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_analytics_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);

        $this->actingAs($admin)->get('/analytics')->assertOk();
        $this->actingAs($secretary)->get('/analytics')->assertForbidden();
    }

    public function test_analytics_all_tabs_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['overview', 'performance', 'budget', 'reach', 'faculty', 'pending'] as $tab) {
            $this->actingAs($admin)->get('/analytics?tab='.$tab)->assertOk();
        }
    }

    public function test_calendar_renders_for_all_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        [$facultyUser] = $this->facultyAccount();

        $this->actingAs($admin)->get('/calendar')->assertOk();
        $this->actingAs($secretary)->get('/calendar')->assertOk();
        $this->actingAs($facultyUser)->get('/calendar')->assertOk();
    }

    public function test_calendar_excludes_other_faculty_activities(): void
    {
        [$facultyUser, $faculty] = $this->facultyAccount();
        $other = User::factory()->create(['role' => 'faculty']);
        $otherFaculty = Faculty::create(['user_id' => $other->id, 'employee_id' => 'LNU-2026-0100']);

        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-030', 'title' => 'Calendar Test',
            'planned_start_date' => '2026-01-01', 'planned_end_date' => '2026-12-31',
            'status' => 'ongoing', 'allocated_budget' => 0,
        ]);

        $mineA = Activity::create([
            'extension_program_id' => $program->id, 'title' => 'My Activity',
            'planned_start_date' => '2026-09-01', 'planned_end_date' => '2026-09-01',
            'start_time' => '08:00:00', 'end_time' => '10:00:00', 'status' => 'ongoing',
        ]);
        $mineB = Activity::create([
            'extension_program_id' => $program->id, 'title' => 'Second Assignment',
            'planned_start_date' => '2026-09-03', 'planned_end_date' => '2026-09-03',
            'start_time' => '08:00:00', 'end_time' => '11:00:00', 'status' => 'ongoing',
        ]);
        $theirs = Activity::create([
            'extension_program_id' => $program->id, 'title' => 'Other Activity',
            'planned_start_date' => '2026-09-02', 'planned_end_date' => '2026-09-02',
            'start_time' => '08:00:00', 'end_time' => '11:00:00', 'status' => 'ongoing',
        ]);
        $mineA->faculty()->syncWithoutDetaching([$faculty->id]);
        $mineB->faculty()->syncWithoutDetaching([$faculty->id]);
        $theirs->faculty()->syncWithoutDetaching([$otherFaculty->id]);

        $component = Livewire::actingAs($facultyUser)
            ->test(Calendar::class);

        $events = collect($component->viewData('events'));
        $titles = $events->where('type', 'activity')->pluck('title');

        $this->assertTrue($titles->contains('My Activity'));
        $this->assertTrue($titles->contains('Second Assignment'));
        $this->assertFalse($titles->contains('Other Activity'));
    }

    public function test_reports_are_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$facultyUser] = $this->facultyAccount();

        $this->actingAs($admin)->get('/reports')->assertOk();
        $this->actingAs($facultyUser)->get('/reports')->assertForbidden();
    }

    public function test_annual_report_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/reports/annual')->assertOk();
    }

    public function test_results_framework_report_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-031', 'title' => 'Results Framework Test',
            'planned_start_date' => '2026-01-01', 'planned_end_date' => '2026-12-31',
            'status' => 'ongoing', 'allocated_budget' => 0,
        ]);

        $this->actingAs($admin)->get('/reports/results-framework/'.$program->id)->assertOk();
    }

    public function test_rendered_hours_report_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/reports/rendered-hours')->assertOk();
    }

    public function test_community_impact_report_renders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/reports/community-impact')->assertOk();
    }

    public function test_kpi_reach_derives_from_attendance_only(): void
    {
        // 8.6: reach counts DISTINCT beneficiaries with present/late attendance.
        $admin = User::factory()->create(['role' => 'admin']);
        [$facultyUser, $faculty] = $this->facultyAccount();
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-040', 'title' => 'KPI Test',
            'planned_start_date' => '2026-01-01', 'planned_end_date' => '2026-12-31',
            'status' => 'ongoing', 'allocated_budget' => 0,
        ]);
        $activity = Activity::create([
            'extension_program_id' => $program->id, 'title' => 'Act',
            'planned_start_date' => '2026-09-01', 'planned_end_date' => '2026-09-01',
            'start_time' => '08:00:00', 'end_time' => '11:00:00', 'status' => 'completed',
        ]);

        $b1 = Beneficiary::factory()->create();
        $b2 = Beneficiary::factory()->create();

        Attendance::create(['activity_id' => $activity->id, 'beneficiary_id' => $b1->id, 'attendance_date' => '2026-09-01', 'status' => 'present']);
        Attendance::create(['activity_id' => $activity->id, 'beneficiary_id' => $b1->id, 'attendance_date' => '2026-09-01', 'status' => 'late']);
        Attendance::create(['activity_id' => $activity->id, 'beneficiary_id' => $b2->id, 'attendance_date' => '2026-09-01', 'status' => 'absent']);

        $kpi = app(KpiService::class);
        $this->assertSame(1, $kpi->communityReach($program)); // b2 absent → excluded; b1 counted once
    }
}
