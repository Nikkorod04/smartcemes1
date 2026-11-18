<?php

namespace Tests\Feature;

use App\Models\AssessmentLink;
use App\Models\Community;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public needs-assessment entry surface (after-defense revision, Item 1, Step 1).
 *
 * The questionnaire itself arrives in Step 2; these tests pin the access rules:
 * an active token opens without a login, and every other state fails politely.
 */
class PublicAssessmentFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The public bundle (resources/js/public.js) is built on deploy, not in
        // the test environment; the asset manifest is irrelevant to access rules.
        $this->withoutVite();
    }

    public function test_an_active_link_opens_without_authentication(): void
    {
        $community = Community::factory()->create([
            'name' => 'Brgy. San Isidro',
            'municipality' => 'Tacloban City',
            'province' => 'Leyte',
        ]);

        $link = AssessmentLink::factory()
            ->forPeriod(4, 2026)
            ->create(['community_id' => $community->id]);

        $this->get(route('assessment.public', ['token' => $link->token]))
            ->assertOk()
            ->assertSee('Brgy. San Isidro')
            ->assertSee('Q4 2026')
            ->assertSee('noindex', false);
    }

    public function test_the_page_does_not_require_a_session(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->assertGuest();

        $this->get(route('assessment.public', ['token' => $link->token]))
            ->assertOk();
    }

    public function test_an_unknown_token_shows_the_inactive_page(): void
    {
        $this->get(route('assessment.public', ['token' => 'not-a-real-token']))
            ->assertStatus(404)
            ->assertSee('could not find this assessment link');
    }

    public function test_an_expired_link_is_refused(): void
    {
        $link = AssessmentLink::factory()->expired()->create();

        $this->get(route('assessment.public', ['token' => $link->token]))
            ->assertStatus(410)
            ->assertSee('has expired');
    }

    public function test_a_revoked_link_is_refused(): void
    {
        $link = AssessmentLink::factory()->revoked()->create();

        $this->get(route('assessment.public', ['token' => $link->token]))
            ->assertStatus(410)
            ->assertSee('has been closed');
    }

    public function test_links_expire_at_the_end_of_the_chosen_day(): void
    {
        $this->assertSame(
            now()->addDays(5)->endOfDay()->toDateTimeString(),
            AssessmentLink::expiryFor(5)->toDateTimeString()
        );
    }

    public function test_expiry_is_clamped_to_the_one_to_five_day_window(): void
    {
        $this->assertSame(
            now()->addDay()->endOfDay()->toDateTimeString(),
            AssessmentLink::expiryFor(0)->toDateTimeString()
        );

        $this->assertSame(
            now()->addDays(AssessmentLink::MAX_DAYS)->endOfDay()->toDateTimeString(),
            AssessmentLink::expiryFor(30)->toDateTimeString()
        );
    }

    public function test_the_console_command_mints_a_link_for_a_community(): void
    {
        $community = Community::factory()->create(['name' => 'Brgy. Command Test']);

        $this->artisan('assessment:link', [
            'community' => 'Command Test',
            '--days' => 3,
        ])->assertSuccessful();

        $link = AssessmentLink::where('community_id', $community->id)->first();

        $this->assertNotNull($link);
        $this->assertSame(32, strlen($link->token));
        $this->assertSame((int) ceil(now()->month / 3), $link->quarter);
        $this->assertTrue($link->isActive());
    }

    public function test_the_console_command_refuses_an_out_of_range_lifetime(): void
    {
        Community::factory()->create(['name' => 'Brgy. Range Test']);

        $this->artisan('assessment:link', [
            'community' => 'Range Test',
            '--days' => 9,
        ])->assertFailed();

        $this->assertSame(0, AssessmentLink::count());
    }
}
