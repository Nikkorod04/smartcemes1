<?php

namespace Tests\Feature;

use App\Models\AssessmentLink;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\NeedsAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * D7 (after-defense revision): summaries count validated rows only.
 *
 * The review queue is the point where a submission becomes data. Before this,
 * every row counted the moment it was created — so a public submission moved the
 * community's published figures before anybody had read it, and a returned row
 * kept propping them up afterwards.
 */
class AssessmentSummaryValidatedOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function assessment(Community $community, string $status): NeedsAssessment
    {
        return NeedsAssessment::create([
            'community_id' => $community->id,
            'quarter' => 4,
            'year' => 2026,
            'review_status' => $status,
            'respondent_first_name' => 'Maria',
            'respondent_last_name' => 'Santos',
            'respondent_sex' => 'Female',
            'has_electricity' => 'Yes',
        ]);
    }

    protected function summaryFor(Community $community): AssessmentSummary
    {
        return AssessmentSummary::query()
            ->where('community_id', $community->id)
            ->where('quarter', 4)
            ->where('year', 2026)
            ->firstOrFail();
    }

    public function test_a_pending_assessment_does_not_move_the_summary(): void
    {
        $community = Community::factory()->create();

        $this->assessment($community, 'pending');

        $this->assertSame(0, $this->summaryFor($community)->total_responses);
    }

    public function test_a_returned_assessment_does_not_count(): void
    {
        $community = Community::factory()->create();

        $this->assessment($community, 'returned');

        $this->assertSame(0, $this->summaryFor($community)->total_responses);
    }

    public function test_validating_an_assessment_adds_it_to_the_summary(): void
    {
        $community = Community::factory()->create();
        $assessment = $this->assessment($community, 'pending');

        $assessment->update(['review_status' => 'validated']);

        $summary = $this->summaryFor($community);

        $this->assertSame(1, $summary->total_responses);
        $this->assertSame(['Female' => 1], $summary->gender_distribution);
        $this->assertSame(100.0, (float) $summary->electricity_access_percentage);
    }

    public function test_returning_a_validated_assessment_removes_it_again(): void
    {
        $community = Community::factory()->create();
        $assessment = $this->assessment($community, 'validated');

        $this->assertSame(1, $this->summaryFor($community)->total_responses);

        $assessment->update(['review_status' => 'returned']);

        $this->assertSame(0, $this->summaryFor($community)->total_responses);
    }

    public function test_a_public_submission_stays_out_of_the_summary_until_it_is_validated(): void
    {
        $link = AssessmentLink::factory()->create();

        $this->postJson(route('assessment.public.submit', ['token' => $link->token]), [
            'client_uuid' => '1b2c3d4e-5f60-4718-9abc-def012345678',
            'consent' => true,
            'form' => [
                'respondent_first_name' => 'Maria',
                'respondent_last_name' => 'Santos',
                'respondent_sex' => 'Female',
            ],
        ])->assertCreated();

        $this->assertSame(0, $this->summaryFor($link->community)->total_responses);

        NeedsAssessment::sole()->update(['review_status' => 'validated']);

        $this->assertSame(1, $this->summaryFor($link->community)->total_responses);
    }

    public function test_the_console_command_recomputes_stale_summaries(): void
    {
        $community = Community::factory()->create();

        $this->assessment($community, 'validated');
        $this->assessment($community, 'pending');

        // Simulate a summary written under the old, count-everything rule.
        $summary = $this->summaryFor($community);
        $summary->forceFill(['total_responses' => 2])->save();

        $this->artisan('assessment:summaries')->assertSuccessful();

        $this->assertSame(1, $this->summaryFor($community)->total_responses);
    }
}
