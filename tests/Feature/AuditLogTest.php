<?php

namespace Tests\Feature;

use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Tests\TestCase;

/**
 * Audit Logs (owner request 2026-09-25).
 *
 * The page that replaced the dashboard's four-row "Recent Activity" panel. Two
 * properties matter and are pinned here: it is ADMIN-ONLY, and it is READ-ONLY —
 * an audit trail a Director can amend is not an audit trail.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@lnu.com')->firstOrFail();
    }

    public function test_the_audit_log_page_is_admin_only(): void
    {
        $this->seed();

        /* A guest is REDIRECTED to login — the `auth` middleware runs first on
           this route group.

           Asserted BEFORE any `actingAs()`: that call sets the user on the guard
           for the REST OF THE TEST, so a later "guest" request is still
           authenticated and would return 403 (from EnsureRole) instead of the
           redirect. Asserting it last makes the test pass for the wrong reason. */
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));

        $this->actingAs($this->admin())->get(route('audit-logs.index'))->assertOk();

        /* Authorization surfaces at the HTTP layer — the codebase convention
           (see ProgramHubTest). Livewire::test() would bypass the middleware. */
        foreach (['secretary@lnu.com', 'faculty1@lnu.com'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())
                ->get(route('audit-logs.index'))
                ->assertForbidden();
        }
    }

    public function test_it_lists_entries_newest_first(): void
    {
        $this->seed();

        ActivityLog::query()->delete();

        /* Capture the real "now" BEFORE travelling — `travelTo(now())` after a
           backward jump resolves to the jumped-to time, which would give both
           entries the same timestamp and make the order assertion meaningless. */
        $base = now()->copy();

        $this->travelTo($base->copy()->subMinutes(5));
        activity()->log('Older entry');

        $this->travelTo($base);
        activity()->log('Newer entry');

        $this->travelBack();

        $this->actingAs($this->admin())
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Newer entry')
            ->assertSee('Older entry')
            ->assertSeeInOrder(['Newer entry', 'Older entry']);
    }

    public function test_it_paginates_at_twenty_five_per_page(): void
    {
        $this->seed();

        ActivityLog::query()->delete();

        foreach (range(1, 30) as $i) {
            activity()->log('Entry '.$i);
        }

        Livewire::actingAs($this->admin())
            ->test(AuditLogsIndex::class)
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 25)
            ->assertViewHas('total', 30)
            /* Livewire 3 tracks the page in `$paginators['page']`, not a public
               `page` property. */
            ->call('setPage', 2)
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 5);
    }

    public function test_the_component_exposes_no_write_action(): void
    {
        $this->seed();

        $component = Livewire::actingAs($this->admin())->test(AuditLogsIndex::class)->instance();

        /* The page must never be able to alter the trail it reports. If someone
           later adds an edit or delete affordance, this fails rather than
           shipping a mutable audit log. */
        foreach (['save', 'edit', 'create', 'delete', 'destroy', 'update', 'closeForm'] as $action) {
            $this->assertFalse(
                method_exists($component, $action),
                "AuditLogs\\Index must not expose {$action}() — the log is read-only."
            );
        }
    }

    public function test_the_dashboard_drops_the_panel_and_the_nav_points_at_the_page(): void
    {
        $this->seed();

        $html = $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'Recent Activity',
            $html,
            'The dashboard panel was moved to its own page; it must not linger.'
        );

        /* ...and the nav entry that replaced it resolves. */
        $this->assertStringContainsString('Audit Logs', $html);
        $this->assertStringContainsString(route('audit-logs.index'), $html);
    }
}
