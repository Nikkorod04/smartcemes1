<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Wizard;
use App\Models\AssessmentLink;
use App\Models\Community;
use App\Services\AssessmentFormSchema;
use App\Services\AssessmentRules;
use App\Services\AssessmentTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public needs-assessment surface (after-defense revision, Item 1).
 *
 * Step 1 pinned the access rules; Step 2 adds the questionnaire itself. The
 * tests that matter here are the ones that keep the public form and the staff
 * wizard answering to the same instrument.
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

    // -----------------------------------------------------------------------
    // Step 2 — the questionnaire
    // -----------------------------------------------------------------------

    public function test_the_form_renders_every_section_of_the_instrument(): void
    {
        $link = AssessmentLink::factory()->create();

        $response = $this->get(route('assessment.public', ['token' => $link->token]));
        $response->assertOk();

        foreach (config('smartcemes.assessment_sections') as $section) {
            $response->assertSee('Section '.$section['key']);
            $response->assertSee($section['title']);
        }
    }

    public function test_the_form_is_client_side_and_has_no_livewire(): void
    {
        $link = AssessmentLink::factory()->create();

        $response = $this->get(route('assessment.public', ['token' => $link->token]));

        // The whole point of the public form: no per-answer server round trip.
        $response->assertDontSee('wire:', false);
        $response->assertDontSee('livewire', false);

        $response->assertSee('assessmentForm(', false);
        $response->assertSee('Step', false);
        $response->assertSee('of 5', false);
    }

    public function test_the_send_control_is_wired_to_the_form_handler(): void
    {
        $link = AssessmentLink::factory()->create();

        $response = $this->get(route('assessment.public', ['token' => $link->token]));

        // The navigation bar sits outside <form>, so a `type="submit"` button
        // there submits nothing at all and taps silently do nothing. Keep the
        // send control bound to the component's own handler.
        $response->assertSee('x-on:click="submit()"', false);
        $response->assertDontSee('type="submit"', false);

        // Enter inside a text field still goes through the form itself.
        $response->assertSee('x-on:submit.prevent="submit()"', false);
    }

    public function test_the_success_card_offers_another_household(): void
    {
        $link = AssessmentLink::factory()->create();

        $response = $this->get(route('assessment.public', ['token' => $link->token]));

        // One phone is often shared by a household (or passed around a purok).
        // The button must exist and reuse the reset that rotates the draft id.
        $response->assertSee('Answer for another household');
        $response->assertSee('x-on:click="startOver()"', false);
    }

    public function test_the_form_renders_the_tricky_fields(): void
    {
        $link = AssessmentLink::factory()->create();

        $response = $this->get(route('assessment.public', ['token' => $link->token]));

        // Identity block (not part of the section map, like the wizard).
        $response->assertSee('form.respondent_first_name', false);
        $response->assertSee('form.respondent_last_name', false);
        $response->assertSee('form.respondent_age', false);

        // Capped multi, conditionally-visible follow-ups, service rating and
        // the free-text field that has no XLSX cell.
        $response->assertSee('family_problems', false);
        $response->assertSee('show().toilet', false);
        $response->assertSee('form.general_feedback', false);
        $response->assertSee('I agree that these answers may be recorded', false);
    }

    public function test_the_form_schema_covers_every_template_field(): void
    {
        $types = AssessmentFormSchema::types();
        $initial = AssessmentFormSchema::initialState();

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if ($col['type'] === 'context') {
                $this->assertArrayNotHasKey($field, $types, "$field is form context and must not be an answer field");

                continue;
            }

            $this->assertArrayHasKey($field, $types, "$field is missing from the public form");
        }

        $this->assertSame('rating', $types['barangay_service_ratings']);
        $this->assertSame('multi', $types['family_problems']);
        $this->assertSame('yesno', $types['has_electricity']);
        $this->assertArrayHasKey('general_feedback', $types);

        // Empty shapes: multi-selects are arrays, the rating is an object.
        $this->assertSame([], $initial['family_problems']);
        $this->assertSame(['overall' => ''], $initial['barangay_service_ratings']);
        $this->assertSame('', $initial['respondent_first_name']);
    }

    public function test_the_form_schema_knows_which_fields_need_an_other_box(): void
    {
        $others = AssessmentFormSchema::otherFields();

        $this->assertContains('toilet_type', $others);
        $this->assertContains('common_illnesses', $others);

        // No "Other" in these vocabularies, and yes/no never has one.
        $this->assertNotContains('appliances_owned', $others);
        $this->assertNotContains('has_electricity', $others);
        $this->assertNotContains('barangay_service_ratings', $others);
    }

    public function test_the_form_schema_carries_the_multi_select_limits(): void
    {
        $max = AssessmentFormSchema::maxSelect();
        $exclusive = AssessmentFormSchema::exclusiveOptions();

        $this->assertSame(3, $max['family_problems']);
        $this->assertSame(3, $max['security_problems']);
        $this->assertArrayNotHasKey('appliances_owned', $max);

        $this->assertSame('Flexible', $exclusive['preferred_training_days']);
        $this->assertSame('No supplies', $exclusive['barangay_medical_supplies_available']);
    }

    public function test_the_wizard_and_the_public_form_share_one_rule_set(): void
    {
        $wizard = new Wizard;
        $method = new \ReflectionMethod($wizard, 'rules');
        $method->setAccessible(true);
        $rules = $method->invoke($wizard);

        // The extraction must not have dropped or altered a single rule.
        foreach (AssessmentRules::forForm('form') as $key => $rule) {
            $this->assertArrayHasKey($key, $rules, "the wizard no longer validates $key");
            $this->assertSame($rule, $rules[$key], "the wizard disagrees with the shared rules on $key");
        }

        foreach (AssessmentRules::respondent('form') as $key => $rule) {
            $this->assertSame($rule, $rules[$key] ?? null, "the wizard disagrees on $key");
        }
    }

    public function test_the_shared_rules_cover_every_answer_field(): void
    {
        $rules = AssessmentRules::forForm('form');

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (in_array($col['type'], ['context', 'text', 'number'], true)) {
                continue;
            }

            // The rating field is validated by the wizard as a shape, not a
            // vocabulary, so it is present but not enumerated.
            $this->assertArrayHasKey("form.$field", $rules);

            if ($col['type'] === 'multi') {
                $this->assertSame('array'.(isset($col['max']) ? '|max:'.$col['max'] : ''), $rules["form.$field"]);
            }
        }

        $this->assertSame('nullable|in:Yes,No', $rules['form.has_electricity']);
        $this->assertSame('nullable', $rules['form.barangay_service_ratings']);
    }
}
