<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Review;
use App\Models\AssessmentLink;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The public submission endpoint (after-defense revision, Item 1, Step 3).
 *
 * The form answers in the browser and sends exactly one request; these tests
 * pin what the office gets when that request arrives.
 */
class PublicAssessmentSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Nothing on the public page is compiled in the test environment.
        $this->withoutVite();
    }

    /**
     * A believable finished questionnaire: identity, a yes/no with its
     * follow-up, a capped multi-select and the service rating.
     */
    protected function payload(array $form = [], array $overrides = []): array
    {
        return array_merge([
            'client_uuid' => '9c3f0f0e-1d2a-4b3c-8d4e-5f60718293a4',
            'consent' => true,
            'captured_at' => now()->subMinutes(5)->toIso8601String(),
            'form' => array_merge([
                'respondent_first_name' => 'Maria',
                'respondent_middle_name' => 'Dizon',
                'respondent_last_name' => 'Santos',
                'respondent_age' => 34,
                'has_electricity' => 'Yes',
                'appliances_owned' => ['Television', 'Electric fan'],
                'family_problems' => ['Low income', 'Medical expenses'],
                'barangay_service_ratings' => ['overall' => 'Good'],
                'general_feedback' => 'More training please.',
            ], $form),
        ], $overrides);
    }

    public function test_an_anonymous_visitor_can_submit_the_questionnaire(): void
    {
        $link = AssessmentLink::factory()->create();

        $response = $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload());

        $response->assertCreated()->assertJson(['status' => 'created']);

        $assessment = NeedsAssessment::sole();

        $this->assertSame($link->community_id, $assessment->community_id);
        $this->assertSame((int) $link->quarter, (int) $assessment->quarter);
        $this->assertSame((int) $link->year, (int) $assessment->year);
        $this->assertSame('Maria', $assessment->respondent_first_name);
        $this->assertSame('Santos', $assessment->respondent_last_name);

        // Provenance: no user, and the queue can tell where it came from.
        $this->assertNull($assessment->uploaded_by);
        $this->assertSame(NeedsAssessment::SOURCE_PUBLIC, $assessment->source);
        $this->assertSame('pending', $assessment->review_status);
        $this->assertSame('9c3f0f0e-1d2a-4b3c-8d4e-5f60718293a4', $assessment->client_uuid);
        $this->assertNotNull($assessment->captured_at);

        // The answers landed in the same shape the wizard produces.
        $this->assertSame('Dizon', $assessment->respondent_middle_name);
        $this->assertSame(['Television', 'Electric fan'], $assessment->appliances_owned);
        $this->assertSame(['Low income', 'Medical expenses'], $assessment->family_problems);
        $this->assertSame(['overall' => 'Good'], $assessment->barangay_service_ratings);
        $this->assertSame('More training please.', $assessment->general_feedback);
    }

    public function test_a_retry_of_the_same_draft_does_not_create_a_duplicate(): void
    {
        $link = AssessmentLink::factory()->create();
        $payload = $this->payload();

        $first = $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $payload);
        $second = $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $payload);

        $first->assertCreated();
        $second->assertOk()->assertJson(['status' => 'already-recorded']);

        $this->assertSame(1, NeedsAssessment::count());
        $this->assertSame($first->json('id'), $second->json('id'));
    }

    public function test_a_dropped_signal_can_be_retried_with_the_same_answers(): void
    {
        $link = AssessmentLink::factory()->create();

        // The first attempt never reached the server (client-side failure), the
        // second carries the same client_uuid.
        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([
            'respondent_first_name' => 'Ana',
        ]))->assertCreated();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([
            'respondent_first_name' => 'Ana',
        ]))->assertOk();

        $this->assertSame(1, NeedsAssessment::count());
        $this->assertSame('Ana', NeedsAssessment::sole()->respondent_first_name);
    }

    public function test_an_expired_link_refuses_the_submission(): void
    {
        $link = AssessmentLink::factory()->create(['expires_at' => now()->subDay()]);

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload())
            ->assertStatus(410)
            ->assertJson(['reason' => 'expired']);

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_a_revoked_link_refuses_the_submission(): void
    {
        $link = AssessmentLink::factory()->create(['revoked_at' => now()]);

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload())
            ->assertStatus(410)
            ->assertJson(['reason' => 'revoked']);

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_an_unknown_token_is_refused(): void
    {
        $this->postJson(route('assessment.public.submit', ['token' => 'nope-nope-nope']), $this->payload())
            ->assertNotFound();

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_consent_is_required(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([], [
            'consent' => false,
        ]))->assertStatus(422)->assertJsonValidationErrors(['consent']);

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_the_respondent_name_is_required(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([
            'respondent_first_name' => '',
            'respondent_last_name' => '',
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'form.respondent_first_name',
            'form.respondent_last_name',
        ]);

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_answers_are_validated_against_the_instrument(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([
            'has_electricity' => 'Maybe',
            'toilet_type' => 'Teleporter',
            'family_problems' => ['Low income', 'Medical expenses', 'Lack of employment', 'Poor housing'],
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'form.has_electricity',
            'form.toilet_type',
            'form.family_problems',
        ]);

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_other_free_text_is_folded_into_other_text(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([
            'toilet_type' => 'Other',
            'toilet_type_other' => 'Shared with neighbours',
        ]))->assertCreated();

        $assessment = NeedsAssessment::sole();

        $this->assertSame(['toilet_type' => ['Shared with neighbours']], $assessment->other_text);
        // The transient key is not a column in its own right.
        $this->assertArrayNotHasKey('toilet_type_other', $assessment->getAttributes());
    }

    public function test_other_free_text_without_the_other_choice_is_discarded(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([
            'toilet_type' => 'Pour flush',
            'toilet_type_other' => 'Typed then changed their mind',
        ]))->assertCreated();

        $this->assertNull(NeedsAssessment::sole()->other_text);
    }

    public function test_a_device_clock_in_the_future_is_not_trusted(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([], [
            'captured_at' => now()->addDays(30)->toIso8601String(),
        ]))->assertCreated();

        $this->assertTrue(NeedsAssessment::sole()->captured_at->lessThanOrEqualTo(now()));
    }

    public function test_the_honeypot_is_answered_politely_but_stores_nothing(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), $this->payload([], [
            'website' => 'https://spam.example',
        ]))->assertCreated();

        $this->assertSame(0, NeedsAssessment::count());
    }

    public function test_the_review_queue_labels_a_public_submission(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);
        $community = Community::factory()->create(['name' => 'Brgy. Public Test']);

        NeedsAssessment::create([
            'community_id' => $community->id,
            'quarter' => 4,
            'year' => 2026,
            'review_status' => 'pending',
            'source' => NeedsAssessment::SOURCE_PUBLIC,
            'respondent_first_name' => 'Maria',
            'respondent_last_name' => 'Santos',
        ]);

        Livewire::actingAs($secretary)
            ->test(Review::class)
            ->assertSee('Public form')
            ->assertSee('Respondent')
            ->assertSee('public link');
    }

    public function test_the_review_queue_can_filter_to_public_submissions(): void
    {
        $secretary = User::factory()->create(['role' => 'secretary']);
        $community = Community::factory()->create();

        NeedsAssessment::create([
            'community_id' => $community->id, 'quarter' => 4, 'year' => 2026,
            'review_status' => 'pending', 'source' => NeedsAssessment::SOURCE_PUBLIC,
            'respondent_first_name' => 'Zara', 'respondent_last_name' => 'Publiconly',
        ]);

        NeedsAssessment::create([
            'community_id' => $community->id, 'quarter' => 4, 'year' => 2026,
            'review_status' => 'pending', 'source' => NeedsAssessment::SOURCE_STAFF,
            'uploaded_by' => $secretary->id,
            'respondent_first_name' => 'Yuri', 'respondent_last_name' => 'Staffonly',
        ]);

        Livewire::actingAs($secretary)
            ->test(Review::class)
            ->set('source', 'public')
            ->assertSee('Publiconly')
            ->assertDontSee('Staffonly');
    }

    public function test_the_import_and_manual_rows_keep_their_own_labels(): void
    {
        $this->assertSame('Public form', NeedsAssessment::sourceLabel(NeedsAssessment::SOURCE_PUBLIC));
        $this->assertSame('XLSX', NeedsAssessment::sourceLabel(NeedsAssessment::SOURCE_IMPORT));
        $this->assertSame('Manual', NeedsAssessment::sourceLabel(NeedsAssessment::SOURCE_STAFF));
        $this->assertSame('Manual', NeedsAssessment::sourceLabel(null));
    }
}
