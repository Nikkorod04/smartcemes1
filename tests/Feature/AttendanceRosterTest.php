<?php

namespace Tests\Feature;

use App\Livewire\Activities\TakeAttendance;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\ExtensionProject;
use App\Models\User;
use App\Services\TrainingHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 1 of the attendance/evaluation streamlining: the tablet roster.
 *
 * The roster writes the same Attendance rows as the XLSX import, using the
 * activity date, so the trainee counts and training hours stay consistent.
 */
class AttendanceRosterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function secretary(): User
    {
        return User::factory()->create(['role' => User::ROLE_SECRETARY]);
    }

    protected function project(string $code = 'ATT-2026-001'): ExtensionProject
    {
        return ExtensionProject::create([
            'code' => $code,
            'title' => 'Attendance Roster Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
    }

    protected function beneficiary(array $overrides = []): Beneficiary
    {
        return Beneficiary::create(array_merge([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'age' => 40,
            'gender' => 'Female',
            'phone' => '09170000000',
            'barangay' => 'San Jose',
            'municipality' => 'Tacloban City',
            'beneficiary_category' => 'Housewife',
        ], $overrides));
    }

    protected function activity(ExtensionProject $project, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'extension_project_id' => $project->id,
            'title' => 'Community Training',
            'venue' => 'Barangay Hall',
            'planned_start_date' => '2026-03-10',
            'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'no_of_days' => 1,
            'allocated_budget' => 1000,
            'status' => 'ongoing',
        ], $overrides));
    }

    public function test_admin_and_secretary_can_open_the_roster(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->actingAs($this->secretary())
            ->get(route('activities.attendance', $activity))
            ->assertOk()
            ->assertSee('Take attendance')
            ->assertSee('Community Training');

        $this->actingAs($this->admin())
            ->get(route('activities.attendance', $activity))
            ->assertOk();
    }

    public function test_faculty_cannot_open_the_roster(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_FACULTY]))
            ->get(route('activities.attendance', $activity))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_and_an_unknown_activity_is_not_found(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->get(route('activities.attendance', $activity))
            ->assertRedirect(route('login'));

        $this->actingAs($this->secretary())
            ->get('/activities/999999/attendance')
            ->assertNotFound();
    }

    public function test_the_roster_lists_only_enrolled_beneficiaries(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $pedro = $this->beneficiary(['first_name' => 'Pedro', 'last_name' => 'Penduko']);
        $other = $this->beneficiary(['first_name' => 'Other', 'last_name' => 'Project']);
        $project->beneficiaries()->attach([$maria->id, $pedro->id]);

        $otherProject = $this->project('ATT-2026-002');
        $otherProject->beneficiaries()->attach($other->id);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->assertSee('Maria Santos')
            ->assertSee('Pedro Penduko')
            ->assertDontSee('Other Project');
    }

    public function test_existing_attendance_is_preloaded(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $project->beneficiaries()->attach($maria->id);

        Attendance::create([
            'activity_id' => $activity->id,
            'beneficiary_id' => $maria->id,
            'attendance_date' => $activity->planned_start_date,
            'status' => 'late',
        ]);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->assertSet('statuses.'.$maria->id, 'late');
    }

    public function test_saving_writes_statuses_with_the_activity_date(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $pedro = $this->beneficiary(['first_name' => 'Pedro', 'last_name' => 'Penduko']);
        $project->beneficiaries()->attach([$maria->id, $pedro->id]);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->call('toggleStatus', $maria->id, 'present')
            ->call('toggleStatus', $pedro->id, 'absent')
            ->call('save')
            ->assertHasNoErrors();

        $mariaRow = Attendance::where('beneficiary_id', $maria->id)->sole();
        $pedroRow = Attendance::where('beneficiary_id', $pedro->id)->sole();

        $this->assertSame('present', $mariaRow->status);
        $this->assertSame('absent', $pedroRow->status);
        $this->assertSame(
            $activity->planned_start_date->toDateString(),
            $mariaRow->attendance_date->toDateString(),
        );
    }

    public function test_reopening_shows_saved_statuses_and_updates_without_duplicates(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $project->beneficiaries()->attach($maria->id);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->call('toggleStatus', $maria->id, 'present')
            ->call('save');

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->assertSet('statuses.'.$maria->id, 'present')
            ->call('toggleStatus', $maria->id, 'absent')
            ->call('save');

        $this->assertSame(1, Attendance::where('beneficiary_id', $maria->id)->count());
        $this->assertSame('absent', Attendance::where('beneficiary_id', $maria->id)->sole()->status);
    }

    public function test_tapping_the_active_status_clears_the_row_on_save(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $project->beneficiaries()->attach($maria->id);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->call('toggleStatus', $maria->id, 'present')
            ->call('save');

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->assertSet('statuses.'.$maria->id, 'present')
            ->call('toggleStatus', $maria->id, 'present')
            ->assertSet('statuses.'.$maria->id, '')
            ->call('save');

        $this->assertSame(0, Attendance::where('beneficiary_id', $maria->id)->count());
    }

    public function test_mark_all_present_and_clear_all(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $pedro = $this->beneficiary(['first_name' => 'Pedro', 'last_name' => 'Penduko']);
        $project->beneficiaries()->attach([$maria->id, $pedro->id]);

        $component = Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->call('markAllPresent')
            ->assertSet('statuses.'.$maria->id, 'present')
            ->assertSet('statuses.'.$pedro->id, 'present')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Attendance::count());

        $component->call('clearAll')
            ->assertSet('statuses.'.$maria->id, '')
            ->call('save');

        $this->assertSame(0, Attendance::count());
    }

    public function test_attendance_feeds_the_trainee_count(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $pedro = $this->beneficiary(['first_name' => 'Pedro', 'last_name' => 'Penduko']);
        $project->beneficiaries()->attach([$maria->id, $pedro->id]);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->call('markAllPresent')
            ->call('save');

        $breakdown = app(TrainingHoursService::class)->forActivity($activity->fresh());

        $this->assertSame(2, $breakdown['trainees']);
        $this->assertSame('attendance', $breakdown['trainees_source']);
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $project->beneficiaries()->attach($maria->id);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->set('statuses.'.$maria->id, 'maybe')
            ->call('save')
            ->assertHasErrors('statuses.'.$maria->id);

        $this->assertSame(0, Attendance::count());
    }

    public function test_search_filters_the_roster_without_losing_statuses(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $maria = $this->beneficiary();
        $pedro = $this->beneficiary(['first_name' => 'Pedro', 'last_name' => 'Penduko']);
        $project->beneficiaries()->attach([$maria->id, $pedro->id]);

        Livewire::actingAs($this->secretary())
            ->test(TakeAttendance::class, ['activity' => $activity])
            ->call('toggleStatus', $maria->id, 'present')
            ->set('search', 'Pedro')
            ->assertSee('Pedro Penduko')
            ->assertDontSee('Maria Santos')
            ->assertSet('statuses.'.$maria->id, 'present')
            ->call('save');

        $this->assertSame('present', Attendance::where('beneficiary_id', $maria->id)->sole()->status);
    }
}
