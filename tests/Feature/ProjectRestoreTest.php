<?php

namespace Tests\Feature;

use App\Livewire\Colleges\Index as CollegesIndex;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\ExtensionProject;
use App\Models\User;
use App\Services\ProjectArchiveService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The other half of the archive (owner request 2026-10-05).
 *
 * The archive shipped first as a one-way door: `Communities\Index` has had a
 * `restore()` since v4.5, and a project is a far larger thing to lose to a
 * mis-click. These tests pin the two halves that make it a real archive:
 *
 *   1. VISIBILITY — an archived project is findable under the "Archived" chip
 *      instead of being invisible the moment it is archived.
 *   2. UNDO — `restoreProject()` reverses the exact cascade `archive()` applied.
 *
 * The cascade itself now lives in `ProjectArchiveService`, so these tests also
 * prove the archive/restore pair stayed symmetrical through that extraction.
 */
class ProjectRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@lnu.com')->firstOrFail();
    }

    private function projectWithActivities(): ExtensionProject
    {
        $project = ExtensionProject::withCount('activities')
            ->whereHas('activities')
            ->orderByDesc('activities_count')
            ->first();

        if ($project === null) {
            $this->markTestSkipped('No seeded project carries activities.');
        }

        return $project;
    }

    /** Open the hub on view 3 for a project's college + thrust. */
    private function hubFor(ExtensionProject $project)
    {
        return Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('selectCollege', $project->college->code)
            ->call('selectProgram', $project->program_id);
    }

    private function archive(ExtensionProject $project): void
    {
        app(ProjectArchiveService::class)->archive($project);
    }

    /* ==================== visibility ==================== */

    public function test_an_archived_project_is_findable_under_the_archived_chip(): void
    {
        $project = $this->projectWithActivities();

        $this->archive($project);

        // Under "All" it is gone…
        $this->hubFor($project)
            ->set('projectStatus', 'All')
            ->assertDontSee($project->code);

        // …and under "Archived" it is listed, which is what makes it recoverable.
        $this->hubFor($project)
            ->set('projectStatus', 'Archived')
            ->assertSee($project->code)
            ->assertSee('Archived');
    }

    public function test_the_archived_chip_counts_the_archived_projects(): void
    {
        $project = $this->projectWithActivities();

        // `viewData($key)`, not `assertViewHas` — Livewire's Testable does not
        // implement the latter, and it resolves the key to the WRONG value.
        $this->assertSame(0, $this->hubFor($project)->viewData('statusCounts')['Archived']);

        $this->archive($project);

        $this->assertSame(1, $this->hubFor($project)->viewData('statusCounts')['Archived']);
    }

    /**
     * The reachability guard.
     *
     * `programRows()` groups the college's projects, and the live relation
     * excludes trashed rows — so a thrust whose ONLY project was archived used to
     * disappear from the list, taking the last route to that project with it. The
     * project became unreachable AND un-restorable, which defeats the whole
     * point. It must stay listed (reading 0 projects) so the Director can open
     * it and restore.
     */
    public function test_a_thrust_whose_only_project_is_archived_stays_reachable(): void
    {
        $project = $this->projectWithActivities();

        // Make it the sole project of its thrust, so the case is unambiguous.
        ExtensionProject::where('program_id', $project->program_id)
            ->where('id', '!=', $project->id)
            ->get()
            ->each(fn (ExtensionProject $other) => app(ProjectArchiveService::class)->archive($other));

        $this->archive($project);

        $ids = $this->hubFor($project)->viewData('programRows')->pluck('id')->all();

        $this->assertContains(
            $project->program_id,
            $ids,
            'A thrust with only archived projects must remain on the college list.'
        );
    }

    public function test_an_archived_card_offers_restore_and_prints_no_false_zeroes(): void
    {
        $project = $this->projectWithActivities();
        $this->archive($project);

        $html = $this->hubFor($project)->set('projectStatus', 'Archived')->html();

        $this->assertStringContainsString('restoreProject('.$project->id.')', $html);
        $this->assertStringContainsString('Restore', $html);

        // The card must not link to the hub — `projects.show` binds through the
        // soft-delete scope and would 404 on an archived project.
        $this->assertStringNotContainsString(
            route('projects.show', $project->id),
            $html,
            'An archived card must not carry a hub link.'
        );

        // And it must not print the roll-up's 0s as if they were real figures.
        $this->assertStringContainsString('retained and return when it is restored', $html);
    }

    /**
     * The same reachability trap, one layer down: `mount()` validates the
     * `?program=` deep link against the LIVE projects, so a bookmark or refresh
     * on a fully-archived thrust used to bounce silently back to view 2.
     */
    public function test_a_deep_link_to_a_thrust_with_only_archived_projects_still_opens_view_three(): void
    {
        $project = $this->projectWithActivities();

        ExtensionProject::where('program_id', $project->program_id)
            ->where('id', '!=', $project->id)
            ->get()
            ->each(fn (ExtensionProject $other) => app(ProjectArchiveService::class)->archive($other));

        $this->archive($project);

        $testable = Livewire::withQueryParams([
            'college' => $project->college->code,
            'program' => (string) $project->program_id,
        ])->actingAs($this->admin())->test(CollegesIndex::class);

        $this->assertSame(
            (string) $project->program_id,
            $testable->get('program'),
            'The deep link must not be blanked by mount().'
        );

        $this->assertNotNull(
            $testable->viewData('selectedProgram'),
            'View 3 must resolve for a thrust whose only projects are archived.'
        );
    }

    /* ==================== restore ==================== */

    public function test_the_director_restores_a_project_and_its_activities(): void
    {
        $project = $this->projectWithActivities();
        $activityIds = $project->activities()->pluck('id');

        $this->archive($project);

        $this->assertSoftDeleted('extension_projects', ['id' => $project->id]);

        $this->hubFor($project)
            ->set('projectStatus', 'Archived')
            ->call('restoreProject', $project->id)
            ->assertDispatched('sc-toast')
            // The row is live again, so the Archived list no longer holds it —
            // the action returns the Director to the list where it now appears.
            ->assertSet('projectStatus', 'All');

        $this->assertNotSoftDeleted('extension_projects', ['id' => $project->id]);

        foreach ($activityIds as $activityId) {
            $this->assertNotSoftDeleted('activities', ['id' => $activityId]);
        }
    }

    public function test_restore_reverses_the_whole_cascade(): void
    {
        $project = $this->projectWithActivities();
        $activity = $project->activities()->firstOrFail();

        // The three child types the archive touches, plus the project-level
        // budget entry whose `activity_id` is NULL.
        $attendance = Attendance::create([
            'activity_id' => $activity->id,
            'beneficiary_id' => $project->beneficiaries()->first()?->id
                ?? Beneficiary::create([
                    'first_name' => 'Restore', 'last_name' => 'Case',
                    'gender' => 'Male', 'barangay' => 'San Jose',
                    'beneficiary_category' => 'Farmer',
                ])->id,
            'attendance_date' => $activity->planned_start_date,
            'status' => 'present',
        ]);

        $projectWide = BudgetUtilization::create([
            'extension_project_id' => $project->id,
            'activity_id' => null,
            'item_name' => 'Project-wide supplies',
            'date_used' => '2026-05-01',
            'amount' => 1000,
        ]);

        $this->archive($project);

        $this->assertSoftDeleted('attendances', ['id' => $attendance->id]);
        $this->assertSoftDeleted('budget_utilizations', ['id' => $projectWide->id]);

        $this->hubFor($project)->call('restoreProject', $project->id);

        $this->assertNotSoftDeleted('attendances', ['id' => $attendance->id]);
        $this->assertNotSoftDeleted('budget_utilizations', ['id' => $projectWide->id]);
    }

    public function test_a_restored_project_returns_to_the_live_list_and_the_targets_page(): void
    {
        $project = $this->projectWithActivities();

        $this->archive($project);
        $this->assertNull(ExtensionProject::find($project->id));

        $this->hubFor($project)->call('restoreProject', $project->id);

        $this->assertNotNull(ExtensionProject::find($project->id));

        // Visible again on the hub's live list…
        $this->hubFor($project)
            ->set('projectStatus', 'All')
            ->assertSee($project->code);

        // …and back on the university targets page, which iterates projects.
        $this->actingAs($this->admin())
            ->get('/targets')
            ->assertOk()
            ->assertSee($project->code);
    }

    /* ==================== access ==================== */

    public function test_the_secretary_cannot_restore_a_project(): void
    {
        $project = $this->projectWithActivities();
        $this->archive($project);

        $secretary = User::where('email', 'secretary@lnu.com')->firstOrFail();

        // Through the GATE, not a Livewire call: `mount()` already authorizes, so
        // a non-admin Testable never gets a valid snapshot (§14.1).
        $this->assertFalse(Gate::forUser($secretary)->allows('restore', $project));

        $this->actingAs($secretary)->get('/colleges')->assertForbidden();

        $this->assertSoftDeleted('extension_projects', ['id' => $project->id]);
    }

    public function test_a_live_project_cannot_be_restored(): void
    {
        $project = $this->projectWithActivities();

        // `restoreProject()` resolves through `onlyTrashed()`, so a live project
        // is simply not found — "restore" can never be a second way to touch it.
        // The model binding throws rather than rendering a 404 response, so the
        // exception is the assertion.
        $this->expectException(ModelNotFoundException::class);

        $this->hubFor($project)->call('restoreProject', $project->id);
    }
}
