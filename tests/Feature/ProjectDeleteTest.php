<?php

namespace Tests\Feature;

use App\Livewire\Availability\Index as AvailabilityIndex;
use App\Livewire\Colleges\Index as CollegesIndex;
use App\Models\Activity;
use App\Models\BudgetUtilization;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as ActivityLogEntry;
use Tests\TestCase;

/**
 * Archiving a project from the hub (owner request 2026-10-05).
 *
 * The Director had no way to remove a project, so the demo's weaker entries
 * could only be deleted with DB surgery. `/colleges` view 3 now carries an
 * archive action per project card, confirmed with Livewire's `wire:confirm`
 * (the same convention `Communities\Index::confirmDelete()` uses).
 *
 * WHY THESE TESTS ARE SHAPED THE WAY THEY ARE
 * -------------------------------------------
 * Deleting the project row alone is NOT the same as making the project
 * disappear. Every project- and university-level figure iterates
 * `ExtensionProject` and therefore excludes trashed rows automatically — but
 * the Calendar, the Availability activity picker and a faculty's own
 * rendered-hours list query `Activity` GLOBALLY and would keep showing a
 * deleted project's work. That asymmetry is the whole risk here, so it is what
 * the tests pin.
 */
class ProjectDeleteTest extends TestCase
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

    private function secretary(): User
    {
        return User::where('email', 'secretary@lnu.com')->firstOrFail();
    }

    private function faculty(): User
    {
        return User::where('email', 'faculty1@lnu.com')->firstOrFail();
    }

    /** The seeded project with the most activities — the interesting case. */
    private function projectWithActivities(): ExtensionProject
    {
        // `whereHas` (an EXISTS subquery) rather than `having` — SQLite rejects
        // HAVING on a non-aggregate query, and the suite runs on :memory:.
        $project = ExtensionProject::withCount('activities')
            ->whereHas('activities')
            ->orderByDesc('activities_count')
            ->first();

        if ($project === null) {
            $this->markTestSkipped('No seeded project carries activities.');
        }

        return $project;
    }

    /* ==================== the happy path ==================== */

    public function test_the_director_archives_a_project_and_its_activities(): void
    {
        $project = $this->projectWithActivities();
        $activityIds = $project->activities()->pluck('id');

        $this->assertGreaterThan(0, $activityIds->count());

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id)
            ->assertDispatched('sc-toast');

        // The project is soft-deleted, not destroyed — the row survives for audit.
        $this->assertSoftDeleted('extension_projects', ['id' => $project->id]);

        // …and so is every activity beneath it, or the Calendar and the
        // Availability picker would keep rendering a deleted project's work.
        foreach ($activityIds as $activityId) {
            $this->assertSoftDeleted('activities', ['id' => $activityId]);
        }
    }

    public function test_an_archived_project_leaves_every_project_query(): void
    {
        $project = $this->projectWithActivities();

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id);

        $this->assertNull(
            ExtensionProject::find($project->id),
            'A trashed project must not resolve through the default scope.'
        );

        $this->assertNotNull(
            ExtensionProject::withTrashed()->find($project->id),
            'The row must still exist — this is a soft delete.'
        );

        // The hub's own rows are built from `ExtensionProject::query()`, so the
        // card is gone from view 3 as a consequence, not by a separate filter.
        $html = $this->actingAs($this->admin())
            ->get('/colleges?college='.$project->college->code.'&program='.$project->program_id)
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($project->code, $html);
    }

    /**
     * `budget_utilizations.activity_id` is NULLABLE and all three project seeders
     * write NULL for a project-wide cost — so a cascade that only walks
     * `$activity->budgetUtilizations()` silently leaves those rows behind.
     */
    public function test_project_level_budget_entries_are_archived_too(): void
    {
        $project = $this->projectWithActivities();

        $projectWide = BudgetUtilization::create([
            'extension_project_id' => $project->id,
            'activity_id' => null,
            'item_name' => 'Project-wide supplies',
            'date_used' => '2026-05-01',
            'description' => 'Not tied to any single activity',
            'amount' => 2500,
        ]);

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id);

        $this->assertSoftDeleted('budget_utilizations', ['id' => $projectWide->id]);
    }

    public function test_archived_activities_stop_reaching_global_activity_queries(): void
    {
        $project = $this->projectWithActivities();
        $activityIds = $project->activities()->pluck('id');

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id);

        // The invariant the cascade exists for: the GLOBAL `Activity` scope — the
        // one the Calendar, the Availability picker and the faculty
        // rendered-hours list read — no longer sees them.
        $this->assertSame(
            0,
            Activity::whereIn('id', $activityIds)->count(),
            'Global activity queries must not reach a deleted project\'s activities.'
        );

        // They are still recoverable, which is what makes this safe.
        $this->assertSame(
            $activityIds->count(),
            Activity::withTrashed()->whereIn('id', $activityIds)->count()
        );
    }

    public function test_archived_activities_leave_the_availability_picker(): void
    {
        $project = $this->projectWithActivities();
        $activity = $project->activities()->firstOrFail();

        // The COMPONENT, not the full page: `/availability` sits inside the app
        // layout, whose notification bell carries historical notification
        // payloads that still name the activity. That is expected and unrelated
        // — what matters is the picker the Director actually chooses from.
        //
        // Default escaping on purpose: Blade `{{ }}` escapes, so the assertion
        // must compare against the escaped form (`&` → `&amp;`). Passing `false`
        // here searches the raw string and finds nothing either way.
        Livewire::actingAs($this->admin())
            ->test(AvailabilityIndex::class)
            ->assertSee($activity->title);

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id);

        Livewire::actingAs($this->admin())
            ->test(AvailabilityIndex::class)
            ->assertDontSee($activity->title);
    }

    /* ==================== the code is never reissued ==================== */

    public function test_an_archived_code_is_never_reissued(): void
    {
        // `nextCode()` computes its floor over `withTrashed()`, so this only
        // means anything for a project whose code follows the college prefix —
        // a legacy `EXT-…` row could never collide with a new `COE-…` code
        // anyway, and the assertion would pass vacuously.
        $project = ExtensionProject::with('college')->get()
            ->first(fn (ExtensionProject $p) => str_starts_with($p->code, $p->college->code.'-'));

        if ($project === null) {
            $this->markTestSkipped('No seeded project uses a college-prefixed code.');
        }

        $archivedCode = $project->code;
        $collegeCode = $project->college->code;

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id);

        $this->assertNotSame(
            $archivedCode,
            ExtensionProject::nextCode($collegeCode),
            'An archived project\'s code must never be handed to a new project.'
        );
    }

    /* ==================== audit (D8) ==================== */

    public function test_the_archive_is_recorded_in_the_activity_log(): void
    {
        $project = $this->projectWithActivities();

        Livewire::actingAs($this->admin())
            ->test(CollegesIndex::class)
            ->call('archiveProject', $project->id);

        // The model carries `LogsActivity`; the `deleted` event is logged by
        // default with the model's own description.
        $this->assertTrue(
            ActivityLogEntry::where('description', 'Extension project deleted')->exists(),
            'D8: a deletion must leave an audit entry.'
        );
    }

    /* ==================== separation of duties ==================== */

    /**
     * Asserted through the GATE, not through a Livewire call.
     *
     * `CollegesIndex::mount()` already authorizes, so a non-admin Testable never
     * gets a valid snapshot and the follow-up `call()` dies with "Invalid
     * Livewire snapshot structure" instead of a clean 403 — a failure that looks
     * like a broken test rather than a refusal. The route middleware is asserted
     * over HTTP for the same reason (§14.1: Livewire swallows mount-time
     * AuthorizationException).
     */
    public function test_the_secretary_cannot_archive_a_project(): void
    {
        $project = $this->projectWithActivities();

        $this->assertFalse(
            Gate::forUser($this->secretary())->allows('delete', $project),
            'Archiving is Director-only.'
        );

        $this->actingAs($this->secretary())->get('/colleges')->assertForbidden();

        $this->assertNotNull(ExtensionProject::find($project->id), 'A refusal must not archive anything.');
    }

    public function test_faculty_cannot_archive_a_project(): void
    {
        $project = $this->projectWithActivities();

        $this->assertFalse(Gate::forUser($this->faculty())->allows('delete', $project));

        $this->actingAs($this->faculty())->get('/colleges')->assertForbidden();

        $this->assertNotNull(ExtensionProject::find($project->id));
    }

    /* ==================== markup guard ==================== */

    public function test_each_project_card_carries_an_archive_action(): void
    {
        $project = $this->projectWithActivities();

        $html = $this->actingAs($this->admin())
            ->get('/colleges?college='.$project->college->code.'&program='.$project->program_id)
            ->assertOk()
            ->getContent();

        // One archive button per project card, and the confirmation is
        // `wire:confirm` — NOT a hand-rolled modal (§14/§25 record three bugs
        // where a watcher bridge failed to open one).
        $this->assertSame(
            substr_count($html, 'proj-card-stripe'),
            substr_count($html, 'proj-archive-btn'),
            'Every project card must carry exactly one archive action.'
        );

        $this->assertStringContainsString('wire:confirm="Archive ', $html);
    }

    /**
     * The archive button must sit OUTSIDE the card's anchor.
     *
     * A `<button>` nested in an `<a>` is invalid HTML and the click bubbles, so
     * the Director would be navigated to the project instead of archiving it.
     * The card is therefore a DIV whose body is the link.
     */
    public function test_the_archive_action_is_not_nested_inside_the_card_link(): void
    {
        $project = $this->projectWithActivities();

        $html = $this->actingAs($this->admin())
            ->get('/colleges?college='.$project->college->code.'&program='.$project->program_id)
            ->assertOk()
            ->getContent();

        $cardStart = strpos($html, 'proj-card reveal-item');
        $this->assertNotFalse($cardStart, 'View 3 must render the project cards.');

        // A generous window — a single card's markup is a few thousand
        // characters once the Tailwind classes are inlined.
        $card = substr($html, $cardStart, 20000);
        $anchorClose = strpos($card, '</a>');
        $buttonPos = strpos($card, 'proj-archive-btn');

        $this->assertNotFalse($anchorClose);
        $this->assertNotFalse($buttonPos);
        $this->assertGreaterThan(
            $anchorClose,
            $buttonPos,
            'The archive button must follow the closing </a>, not sit inside it.'
        );
    }
}
