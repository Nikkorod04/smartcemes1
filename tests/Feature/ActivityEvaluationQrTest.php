<?php

namespace Tests\Feature;

use App\Livewire\Activities\EvaluationLinks;
use App\Models\Activity;
use App\Models\ActivityEvaluation;
use App\Models\ActivityEvaluationLink;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 2 of the attendance/evaluation streamlining: the anonymous per-activity
 * evaluation QR.
 *
 * The public surface is token-scoped and unauthenticated; the staff surface is
 * Admin/Secretary. Public responses must never touch the activity's imported
 * per-beneficiary aggregates.
 */
class ActivityEvaluationQrTest extends TestCase
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

    protected function faculty(): User
    {
        return User::factory()->create(['role' => User::ROLE_FACULTY]);
    }

    protected function project(string $code = 'EVA-2026-001'): ExtensionProject
    {
        return ExtensionProject::create([
            'code' => $code,
            'title' => 'Evaluation Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
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

    protected function link(Activity $activity, array $overrides = []): ActivityEvaluationLink
    {
        return ActivityEvaluationLink::create(array_merge([
            'activity_id' => $activity->id,
            'token' => ActivityEvaluationLink::newToken(),
            'expires_at' => now()->addDay()->endOfDay(),
            'created_by' => null,
        ], $overrides));
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'satisfaction' => 4,
            'comments' => '  Very useful session.  ',
            'client_uuid' => (string) Str::uuid(),
            'captured_at' => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_public_form_renders_for_an_active_link(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);

        $this->get(route('activity.evaluation.public', ['token' => $link->token]))
            ->assertOk()
            ->assertSee('Activity Evaluation')
            ->assertSee('Community Training')
            ->assertSee('How satisfied were you with this activity?')
            ->assertSee('Send evaluation')
            ->assertSee($project->code);
    }

    public function test_unknown_revoked_and_expired_links_are_refused(): void
    {
        $this->get('/e/does-not-exist')
            ->assertNotFound()
            ->assertSee('no longer active');

        $project = $this->project();
        $activity = $this->activity($project);

        $revoked = $this->link($activity, ['revoked_at' => now()]);
        $this->get(route('activity.evaluation.public', ['token' => $revoked->token]))
            ->assertStatus(410)
            ->assertSee('closed');

        $expired = $this->link($activity, ['expires_at' => now()->subMinute()]);
        $this->get(route('activity.evaluation.public', ['token' => $expired->token]))
            ->assertStatus(410)
            ->assertSee('expired');
    }

    public function test_a_response_is_stored_anonymously(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);
        $payload = $this->payload();

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'created');

        $row = ActivityEvaluation::sole();

        $this->assertSame($activity->id, $row->activity_id);
        $this->assertSame($link->id, $row->activity_evaluation_link_id);
        $this->assertSame(4, $row->satisfaction);
        $this->assertSame('Very useful session.', $row->comments);
        $this->assertSame($payload['client_uuid'], $row->client_uuid);

        // Public feedback is separate from the XLSX-imported aggregates.
        $this->assertNull($activity->fresh()->satisfaction_rating);
        $this->assertNull($activity->fresh()->post_assessment_score);
    }

    public function test_the_same_client_uuid_is_not_stored_twice(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);
        $payload = $this->payload();

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $payload)
            ->assertCreated();

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $payload)
            ->assertOk()
            ->assertJsonPath('status', 'already-recorded');

        $this->assertSame(1, ActivityEvaluation::count());
    }

    public function test_the_honeypot_returns_created_without_a_row(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $this->payload([
            'website' => 'https://spam.example',
        ]))->assertCreated();

        $this->assertSame(0, ActivityEvaluation::count());
    }

    public function test_validation_rejects_missing_or_out_of_range_answers(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), [
            'comments' => 'No score',
        ])->assertStatus(422)->assertJsonValidationErrors('satisfaction');

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $this->payload([
            'satisfaction' => 6,
        ]))->assertStatus(422)->assertJsonValidationErrors('satisfaction');

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $this->payload([
            'comments' => str_repeat('a', 1001),
        ]))->assertStatus(422)->assertJsonValidationErrors('comments');

        $this->assertSame(0, ActivityEvaluation::count());
    }

    public function test_an_expired_link_cannot_accept_responses(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity, ['expires_at' => now()->subMinute()]);

        $this->postJson(route('activity.evaluation.submit', ['token' => $link->token]), $this->payload())
            ->assertStatus(410);

        $this->assertSame(0, ActivityEvaluation::count());
    }

    public function test_staff_can_open_the_screen_and_generate_a_link(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->actingAs($this->secretary())
            ->get(route('activities.evaluation', $activity))
            ->assertOk()
            ->assertSee('Evaluation QR')
            ->assertSee('Community Training');

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->set('days', 2)
            ->call('generate')
            ->assertHasNoErrors();

        $link = ActivityEvaluationLink::sole();

        $this->assertSame($activity->id, $link->activity_id);
        $this->assertSame(32, strlen($link->token));
        $this->assertSame(now()->addDays(2)->endOfDay()->toDateTimeString(), $link->expires_at->toDateTimeString());
    }

    public function test_a_link_generated_before_the_activity_stays_open_through_the_session(): void
    {
        $project = $this->project();
        $sessionDate = now()->addDays(10)->startOfDay();
        $activity = $this->activity($project, [
            'planned_start_date' => $sessionDate->toDateString(),
            'planned_end_date' => $sessionDate->toDateString(),
        ]);

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->set('days', 1)
            ->call('generate')
            ->assertHasNoErrors();

        $link = ActivityEvaluationLink::sole();

        $this->assertSame(
            $sessionDate->copy()->addDay()->endOfDay()->toDateTimeString(),
            $link->expires_at->toDateTimeString(),
        );
        $this->assertTrue($link->isActive());
    }

    public function test_a_link_cannot_be_opened_for_more_than_seven_days(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->set('days', 8)
            ->call('generate')
            ->assertHasErrors('days');

        $this->assertSame(0, ActivityEvaluationLink::count());
    }

    public function test_faculty_cannot_open_the_screen(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->actingAs($this->faculty())
            ->get(route('activities.evaluation', $activity))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_and_an_unknown_activity_is_not_found(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->get(route('activities.evaluation', $activity))
            ->assertRedirect(route('login'));

        $this->actingAs($this->secretary())
            ->get('/activities/999999/evaluation')
            ->assertNotFound();
    }

    public function test_admin_can_open_the_screen(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);

        $this->actingAs($this->admin())
            ->get(route('activities.evaluation', $activity))
            ->assertOk();
    }

    public function test_revoking_closes_the_public_form(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->call('openRevoke', $link->id)
            ->call('revoke')
            ->assertSet('showRevoke', false);

        $this->assertNotNull($link->fresh()->revoked_at);

        $this->get(route('activity.evaluation.public', ['token' => $link->token]))
            ->assertStatus(410)
            ->assertSee('closed');
    }

    public function test_extending_keeps_the_same_token_and_moves_the_expiry(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity, ['expires_at' => now()->addHour()]);
        $token = $link->token;

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->call('openExtend', $link->id)
            ->set('extendDays', 3)
            ->call('extend')
            ->assertHasNoErrors();

        $link->refresh();

        $this->assertSame($token, $link->token);
        $this->assertTrue($link->expires_at->greaterThan(now()->addDays(2)));
    }

    public function test_a_revoked_link_cannot_be_reopened(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity, ['revoked_at' => now()]);

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->call('openExtend', $link->id)
            ->assertSet('showExtend', false);
    }

    public function test_extending_is_limited_to_seven_days(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);
        $original = $link->expires_at->toDateTimeString();

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->call('openExtend', $link->id)
            ->set('extendDays', 8)
            ->call('extend')
            ->assertHasErrors('extendDays');

        $this->assertSame($original, $link->fresh()->expires_at->toDateTimeString());
    }

    public function test_the_screen_shows_the_public_aggregate(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);

        foreach ([2, 4, 5] as $score) {
            ActivityEvaluation::create([
                'activity_id' => $activity->id,
                'activity_evaluation_link_id' => $link->id,
                'client_uuid' => (string) Str::uuid(),
                'satisfaction' => $score,
                'captured_at' => now(),
            ]);
        }

        Livewire::actingAs($this->secretary())
            ->test(EvaluationLinks::class, ['activity' => $activity])
            ->assertSee('3.67')
            ->assertSee('3 responses');
    }

    public function test_the_printable_card_renders_for_a_link(): void
    {
        $project = $this->project();
        $activity = $this->activity($project);
        $link = $this->link($activity);

        $this->actingAs($this->secretary())
            ->get(route('activities.evaluation.sheet', [$activity, $link]))
            ->assertOk()
            ->assertSee('Scan to evaluate')
            ->assertSee(route('activity.evaluation.public', ['token' => $link->token]));

        $this->actingAs($this->faculty())
            ->get(route('activities.evaluation.sheet', [$activity, $link]))
            ->assertForbidden();
    }
}
