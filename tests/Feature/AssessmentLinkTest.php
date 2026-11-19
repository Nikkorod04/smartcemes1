<?php

namespace Tests\Feature;

use App\Livewire\Assessments\ShareLinkArrivals;
use App\Livewire\Assessments\ShareLinks;
use App\Models\AssessmentLink;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The staff-facing share screen (after-defense revision, Item 1, Steps 4a/4b).
 *
 * The link is the credential: if it is minted, extended or revoked wrongly,
 * either strangers can submit or the field team's printed sheet stops working
 * mid-visit. These tests pin both directions.
 */
class AssessmentLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function faculty(): User
    {
        return User::factory()->create(['role' => User::ROLE_FACULTY]);
    }

    protected function secretary(): User
    {
        return User::factory()->create(['role' => User::ROLE_SECRETARY]);
    }

    public function test_faculty_and_secretary_can_open_the_share_screen(): void
    {
        $this->actingAs($this->faculty())->get(route('assessments.share'))->assertOk();
        $this->actingAs($this->secretary())->get(route('assessments.share'))->assertOk();
    }

    public function test_other_roles_cannot_open_the_share_screen(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('assessments.share'))->assertForbidden();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('assessments.share'))->assertRedirect(route('login'));
    }

    public function test_generating_a_link_records_the_community_period_and_creator(): void
    {
        $faculty = $this->faculty();
        $community = Community::factory()->create();

        Livewire::actingAs($faculty)
            ->test(ShareLinks::class)
            ->set('communityId', (string) $community->id)
            ->set('quarter', '4')
            ->set('year', '2026')
            ->set('days', 3)
            ->call('generate')
            ->assertHasNoErrors();

        $link = AssessmentLink::sole();

        $this->assertSame($community->id, $link->community_id);
        $this->assertSame(4, $link->quarter);
        $this->assertSame(2026, $link->year);
        $this->assertSame($faculty->id, $link->created_by);
        $this->assertSame(32, strlen($link->token));

        // "3 days" ends at 11:59 PM three calendar days out, not 72 hours later.
        $this->assertTrue($link->expires_at->isSameDay(now()->addDays(3)));
        $this->assertSame('23:59:59', $link->expires_at->format('H:i:s'));

        // The generated card is selected so the QR is on screen immediately.
        Livewire::actingAs($faculty)
            ->test(ShareLinks::class)
            ->assertSet('selectedId', $link->id);
    }

    public function test_validity_is_limited_to_one_to_five_days(): void
    {
        $community = Community::factory()->create();

        foreach ([0, 6, 30] as $days) {
            Livewire::actingAs($this->faculty())
                ->test(ShareLinks::class)
                ->set('communityId', (string) $community->id)
                ->set('quarter', '4')
                ->set('year', '2026')
                ->set('days', $days)
                ->call('generate')
                ->assertHasErrors('days');
        }

        $this->assertSame(0, AssessmentLink::count());
    }

    public function test_a_community_must_be_chosen(): void
    {
        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->set('communityId', '')
            ->call('generate')
            ->assertHasErrors('communityId');

        $this->assertSame(0, AssessmentLink::count());
    }

    public function test_the_screen_lists_links_created_by_anyone(): void
    {
        $community = Community::factory()->create(['name' => 'Brgy. Shared List']);
        $other = $this->faculty();
        $other->update(['name' => 'Carlo Sumile']);

        AssessmentLink::factory()->create([
            'community_id' => $community->id,
            'created_by' => $other->id,
        ]);

        // A different faculty member still sees the link — the field team shares
        // printed sheets, so hiding a colleague's link would strand it.
        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->assertSee('Brgy. Shared List')
            ->assertSee('Carlo Sumile');
    }

    public function test_the_card_shows_the_public_url_for_the_qr(): void
    {
        $link = AssessmentLink::factory()->create();

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->assertSee(route('assessment.public', ['token' => $link->token]), false);
    }

    public function test_an_expired_link_can_be_reopened_without_changing_its_token(): void
    {
        $link = AssessmentLink::factory()->create([
            'expires_at' => now()->subDay(),
            'token' => 'keep-this-token',
        ]);

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->call('openExtend', $link->id)
            ->assertSet('showExtend', true)
            ->set('extendDays', 2)
            ->call('extend')
            ->assertSet('showExtend', false)
            ->assertHasNoErrors();

        $link->refresh();

        $this->assertSame('keep-this-token', $link->token);
        $this->assertTrue($link->isActive());
        $this->assertTrue($link->expires_at->isSameDay(now()->addDays(2)));

        // The reopened link works again.
        $this->get(route('assessment.public', ['token' => $link->token]))->assertOk();
    }

    public function test_a_revoked_link_cannot_be_reopened(): void
    {
        $link = AssessmentLink::factory()->revoked()->create();
        $originalExpiry = $link->expires_at;

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->call('openExtend', $link->id)
            ->assertSet('showExtend', false);

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->set('extendingId', $link->id)
            ->set('extendDays', 3)
            ->call('extend')
            ->assertSet('showExtend', false);

        $link->refresh();

        $this->assertTrue($link->isRevoked());
        $this->assertEquals($originalExpiry, $link->expires_at);
    }

    public function test_revoking_closes_the_public_form_immediately(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->get(route('assessment.public', ['token' => $link->token]))->assertOk();

        Livewire::actingAs($this->secretary())
            ->test(ShareLinks::class)
            ->call('openRevoke', $link->id)
            ->assertSet('showRevoke', true)
            ->call('revoke')
            ->assertSet('showRevoke', false);

        $this->get(route('assessment.public', ['token' => $link->token]))->assertStatus(410);
        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), [])->assertStatus(410);

        $this->assertTrue($link->fresh()->isRevoked());
    }

    public function test_a_public_submission_records_the_link_it_came_through(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), [
            'client_uuid' => 'f1e2d3c4-b5a6-4978-8abc-def012345678',
            'consent' => true,
            'form' => [
                'respondent_first_name' => 'Maria',
                'respondent_last_name' => 'Santos',
            ],
        ])->assertCreated();

        $this->assertSame($link->id, NeedsAssessment::sole()->assessment_link_id);
    }

    public function test_revoking_a_link_keeps_the_answers_it_collected(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), [
            'client_uuid' => 'a1b2c3d4-e5f6-4a7b-8c9d-0123456789ab',
            'consent' => true,
            'form' => [
                'respondent_first_name' => 'Ana',
                'respondent_last_name' => 'Reyes',
            ],
        ])->assertCreated();

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->call('openRevoke', $link->id)
            ->call('revoke');

        $this->assertSame(1, NeedsAssessment::count());
        $this->assertSame('Ana', NeedsAssessment::sole()->respondent_first_name);
        $this->assertSame($link->id, NeedsAssessment::sole()->assessment_link_id);
    }

    public function test_a_link_without_an_expiry_is_not_open(): void
    {
        // expires_at is nullable so MySQL cannot manage it. A row that somehow
        // lost its expiry must not read as "open forever".
        $link = AssessmentLink::factory()->create(['expires_at' => null]);

        $this->assertFalse($link->isActive());
        $this->get(route('assessment.public', ['token' => $link->token]))->assertStatus(410);
        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), [])->assertStatus(410);
    }

    public function test_the_expiry_column_has_no_database_managed_default(): void
    {
        // MySQL gives the first NOT NULL timestamp column
        // `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` unless
        // explicit_defaults_for_timestamp is on. That silently rewrote
        // expires_at on every update — revoking a link made it look expired,
        // and extending one lost the new date on the next edit. SQLite does not
        // do this, which is why the failure only showed up on the MySQL build.
        $column = collect(Schema::getColumns('assessment_links'))->firstWhere('name', 'expires_at');

        $this->assertTrue($column['nullable'], 'expires_at must stay nullable or MySQL will auto-update it.');
        $this->assertNull($column['default']);
    }

    public function test_revoking_does_not_move_the_expiry_date(): void
    {
        $link = AssessmentLink::factory()->create([
            'expires_at' => now()->addDays(3)->endOfDay(),
        ]);
        $expiry = $link->expires_at->copy();

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->call('openRevoke', $link->id)
            ->call('revoke');

        $link->refresh();

        $this->assertTrue($link->isRevoked());
        $this->assertTrue($expiry->equalTo($link->expires_at));
    }

    public function test_a_revoked_link_shows_no_reopen_action(): void
    {
        $link = AssessmentLink::factory()->revoked()->create();

        $this->get(route('assessment.public', ['token' => $link->token]))->assertStatus(410);

        Livewire::actingAs($this->faculty())
            ->test(ShareLinks::class)
            ->assertSee('Revoked')
            ->assertDontSee('Reopen');
    }

    public function test_every_link_action_is_logged_without_the_token(): void
    {
        $community = Community::factory()->create();
        $faculty = $this->faculty();

        Livewire::actingAs($faculty)
            ->test(ShareLinks::class)
            ->set('communityId', (string) $community->id)
            ->set('quarter', '4')
            ->set('year', '2026')
            ->set('days', 2)
            ->call('generate');

        $link = AssessmentLink::sole();

        Livewire::actingAs($faculty)
            ->test(ShareLinks::class)
            ->call('openExtend', $link->id)
            ->set('extendDays', 4)
            ->call('extend');

        Livewire::actingAs($faculty)
            ->test(ShareLinks::class)
            ->call('openRevoke', $link->id)
            ->call('revoke');

        // Scoped to this feature: model-level logging also writes `created`
        // rows for the community and the link themselves.
        $this->assertSame(
            ['assessment_link_created', 'assessment_link_extended', 'assessment_link_revoked'],
            Activity::query()->where('event', 'like', 'assessment_link_%')->orderBy('id')->pluck('event')->all(),
        );

        $created = Activity::where('event', 'assessment_link_created')->sole();

        $this->assertSame($faculty->id, $created->causer_id);
        $this->assertSame($link->id, $created->subject_id);
        $this->assertSame($community->name, $created->properties['community']);

        // The audit trail is readable by more people than the field team; the
        // token is the credential and must not be written into it.
        $this->assertStringNotContainsString($link->token, json_encode($created->properties));
    }

    // -----------------------------------------------------------------------
    // Step 4b — live arrivals, printable sheet, entry points
    // -----------------------------------------------------------------------

    protected function answer(AssessmentLink $link, $capturedAt = null): NeedsAssessment
    {
        return NeedsAssessment::create([
            'assessment_link_id' => $link->id,
            'community_id' => $link->community_id,
            'quarter' => $link->quarter,
            'year' => $link->year,
            'source' => NeedsAssessment::SOURCE_PUBLIC,
            'captured_at' => $capturedAt ?? now(),
            'respondent_first_name' => 'Maria',
            'respondent_last_name' => 'Santos',
        ]);
    }

    public function test_the_arrivals_strip_counts_only_this_links_answers(): void
    {
        $link = AssessmentLink::factory()->create();
        $other = AssessmentLink::factory()->create();

        $this->answer($link, now()->subHours(2));
        $this->answer($link, now()->subHours(2));
        $this->answer($other);

        Livewire::actingAs($this->faculty())
            ->test(ShareLinkArrivals::class, ['linkId' => $link->id])
            ->assertSeeHtml('tabular-nums">2</span>')
            ->assertSee('answers received')
            ->assertSee('last 2 hours ago')
            ->assertDontSee('Review in queue');
    }

    public function test_only_the_secretary_gets_the_review_link_in_the_arrivals_strip(): void
    {
        $link = AssessmentLink::factory()->create();
        $this->answer($link);

        Livewire::actingAs($this->secretary())
            ->test(ShareLinkArrivals::class, ['linkId' => $link->id])
            ->assertSee('Review in queue')
            ->assertSee(route('assessments.review', ['source' => 'public']), false);

        Livewire::actingAs($this->faculty())
            ->test(ShareLinkArrivals::class, ['linkId' => $link->id])
            ->assertDontSee('Review in queue');
    }

    public function test_arrivals_are_not_readable_by_other_roles(): void
    {
        $link = AssessmentLink::factory()->create();

        Livewire::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->test(ShareLinkArrivals::class, ['linkId' => $link->id])
            ->assertForbidden();
    }

    public function test_the_printable_sheet_carries_the_qr_url_and_community(): void
    {
        $link = AssessmentLink::factory()->create();
        $link->community->update(['name' => 'Brgy. Printed']);

        $this->actingAs($this->faculty())
            ->get(route('assessments.share.sheet', $link))
            ->assertOk()
            ->assertSee('Brgy. Printed')
            ->assertSee(route('assessment.public', ['token' => $link->token]), false)
            ->assertSee('data-qr', false)
            ->assertSee('Print this sheet')
            ->assertSee($link->expires_at->format('F j, Y'))
            ->assertDontSee('wire:', false);
    }

    public function test_only_faculty_and_secretary_can_open_a_printable_sheet(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->actingAs($this->secretary())
            ->get(route('assessments.share.sheet', $link))
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('assessments.share.sheet', $link))
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_the_login_page_for_a_printable_sheet(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->get(route('assessments.share.sheet', $link))
            ->assertRedirect(route('login'));
    }

    public function test_a_revoked_links_sheet_says_the_qr_no_longer_works(): void
    {
        $link = AssessmentLink::factory()->revoked()->create();

        $this->actingAs($this->faculty())
            ->get(route('assessments.share.sheet', $link))
            ->assertOk()
            ->assertSee('This link was revoked');
    }

    public function test_an_unknown_sheet_token_is_not_found(): void
    {
        $this->actingAs($this->faculty())
            ->get('/assessments/share/does-not-exist/sheet')
            ->assertNotFound();
    }

    public function test_the_wizard_and_review_pages_link_to_the_share_screen(): void
    {
        $this->actingAs($this->faculty())
            ->get(route('assessments.create'))
            ->assertOk()
            ->assertSee(route('assessments.share'), false);

        $this->actingAs($this->secretary())
            ->get(route('assessments.review'))
            ->assertOk()
            ->assertSee(route('assessments.share'), false);
    }
}
