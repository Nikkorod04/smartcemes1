<?php

namespace Tests\Feature;

use App\Livewire\AiAnalysisCommunity;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The per-community analysis history page and its delete action (2026-10-07).
 *
 * Why this file exists: `/ai-analysis/community/{community}` is a NEW
 * model-parameter route, and `RouteSurfaceTest`'s unlinked-surfaces walk SKIPS
 * those. The delete rules also need guarding — a hard delete is permanent, so the
 * scope (never an approved analysis, never the live draft) has to be pinned.
 */
class AiAnalysisCommunityTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * A community with one validated summary.
     *
     * @return array{community: Community, summary: AssessmentSummary, assessment: NeedsAssessment}
     */
    protected function communityWithSummary(string $name, int $quarter = 2, int $year = 2026): array
    {
        $community = Community::factory()->create(['name' => $name]);

        $assessment = NeedsAssessment::create([
            'community_id' => $community->id,
            'quarter' => $quarter,
            'year' => $year,
            'uploaded_by' => $this->admin()->id,
            'review_status' => 'validated',
            'respondent_first_name' => 'Test',
            'respondent_last_name' => 'Respondent',
        ]);

        $summary = AssessmentSummary::where([
            'community_id' => $community->id,
            'quarter' => $quarter,
            'year' => $year,
        ])->firstOrFail();

        $summary->update(['total_responses' => 13]);

        return compact('community', 'summary', 'assessment');
    }

    /** @param array<string, mixed> $overrides */
    protected function analysis(array $data, array $overrides = []): AssessmentAnalysis
    {
        return AssessmentAnalysis::create(array_merge([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'summary' => 'A generated narrative.',
            'problems_identified' => [['need' => 'Water access', 'evidence' => '6 of 13']],
            'recommendations' => [['rank' => 1, 'title' => 'Water safety session', 'detail' => 'Two sessions.', 'priority' => 'High', 'ceso_program' => 'Environmental Conservation']],
            'interagency_referrals' => [],
            'approval_status' => AssessmentAnalysis::APPROVAL_DRAFT,
            'status' => AssessmentAnalysis::STATUS_COMPLETED,
            'metadata' => ['model' => 'test-model', 'prompt_version' => 'v2'],
        ], $overrides));
    }

    /* =====================================================================
     | The queue links to it
     |=================================================================== */

    public function test_the_queue_links_each_community_to_its_history_page(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $this->analysis($data);

        $this->actingAs($this->admin())
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('/ai-analysis/community/'.$data['community']->id, false);
    }

    /**
     * …and the review page links back to its community, so the navigation forms a
     * loop (queue ↔ community ↔ review) instead of dead-ending at the queue.
     */
    public function test_the_review_page_links_to_its_community_history(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $analysis = $this->analysis($data);

        $this->actingAs($this->admin())
            ->get('/ai-analysis/'.$analysis->id)
            ->assertOk()
            ->assertSee('/ai-analysis/community/'.$data['community']->id, false);
    }

    /* =====================================================================
     | The page itself
     |=================================================================== */

    public function test_the_community_page_lists_its_own_periods_and_generations_only(): void
    {
        $mine = $this->communityWithSummary('Brgy. San Jose');
        $this->analysis($mine);
        $this->analysis($mine);   // a second generation, so the lineage marker renders

        $theirs = $this->communityWithSummary('Brgy. Sagkahan');
        $this->analysis($theirs);

        // NOTE: this page is a HISTORY list — it lists generations, it does not print
        // their narrative. Reading one happens on `ai-analysis.show`.
        $this->actingAs($this->admin())
            ->get('/ai-analysis/community/'.$mine['community']->id)
            ->assertOk()
            ->assertSee('Brgy. San Jose')
            ->assertSee('gen 2 of 2')
            ->assertSee('Q2 2026')
            ->assertDontSee('Brgy. Sagkahan');
    }

    /** A period with no analysis is shown as work to do, with a Generate action. */
    public function test_the_community_page_shows_periods_awaiting_an_analysis(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');

        $this->actingAs($this->admin())
            ->get('/ai-analysis/community/'.$data['community']->id)
            ->assertOk()
            ->assertSee('awaiting analysis')
            ->assertSee('Generate');
    }

    public function test_only_an_admin_can_open_a_community_page(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');

        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)
            ->get('/ai-analysis/community/'.$data['community']->id)
            ->assertForbidden();
    }

    /* =====================================================================
     | Delete — scoped, because a hard delete is permanent
     |=================================================================== */

    public function test_a_failed_generation_can_be_deleted_and_the_deletion_is_logged(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $failed = $this->analysis($data, ['status' => AssessmentAnalysis::STATUS_FAILED, 'summary' => null, 'error_message' => 'HTTP 503']);

        Livewire::actingAs($this->admin())
            ->test(AiAnalysisCommunity::class, ['community' => $data['community']])
            ->call('delete', $failed->id);

        $this->assertNull(AssessmentAnalysis::find($failed->id), 'The failed generation should be gone.');

        // The model auto-logs the delete (LogsActivity), and the component adds a
        // SEMANTIC event naming the community — assert the latter specifically.
        // (Two rows per action is the existing house behaviour: approve()/discard()
        // do exactly the same.)
        $this->assertSame(
            1,
            Activity::where('event', 'deleted')->where('description', 'like', '%AI analysis deleted%')->count(),
            'The deletion must be logged with a semantic description.'
        );
    }

    /**
     * ⚠️ An APPROVED analysis is citable in reports and its content is mirrored onto
     * the summary. Deleting it would orphan a citation.
     */
    public function test_an_approved_generation_cannot_be_deleted(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $approved = $this->analysis($data, ['approval_status' => AssessmentAnalysis::APPROVAL_APPROVED]);

        $this->assertFalse($approved->isDeletable());

        try {
            Livewire::actingAs($this->admin())
                ->test(AiAnalysisCommunity::class, ['community' => $data['community']])
                ->call('delete', $approved->id);
            $this->fail('Deleting an approved analysis should have been refused.');
        } catch (\Throwable $e) {
            $this->assertNotNull(AssessmentAnalysis::find($approved->id), 'The approved analysis must survive.');
        }
    }

    /** The live draft is the queue's only actionable row — Discard is the way out. */
    public function test_the_live_draft_cannot_be_deleted(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $draft = $this->analysis($data);

        $this->assertFalse($draft->isDeletable(), 'The current draft must not be deletable.');
        $this->assertTrue($draft->isCurrent());
    }

    /** A superseded completed draft is a past attempt, so it IS deletable. */
    public function test_a_superseded_generation_is_deletable(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $old = $this->analysis($data);
        $this->analysis($data);   // a newer draft makes $old superseded

        $this->assertTrue($old->isSuperseded());
        $this->assertTrue($old->isDeletable());
    }

    public function test_clearing_failed_attempts_removes_only_the_failed_ones(): void
    {
        $data = $this->communityWithSummary('Brgy. San Jose');
        $draft = $this->analysis($data);
        $failedA = $this->analysis($data, ['status' => AssessmentAnalysis::STATUS_FAILED, 'summary' => null]);
        $failedB = $this->analysis($data, ['status' => AssessmentAnalysis::STATUS_FAILED, 'summary' => null]);

        Livewire::actingAs($this->admin())
            ->test(AiAnalysisCommunity::class, ['community' => $data['community']])
            ->call('clearFailed');

        $this->assertNull(AssessmentAnalysis::find($failedA->id));
        $this->assertNull(AssessmentAnalysis::find($failedB->id));
        $this->assertNotNull(AssessmentAnalysis::find($draft->id), 'The live draft must survive a failed-clear.');
    }

    /**
     * The id arrives from the browser, so it must be scoped to THIS community —
     * otherwise a crafted call could delete another community's analysis.
     */
    public function test_an_analysis_from_another_community_cannot_be_deleted(): void
    {
        $mine = $this->communityWithSummary('Brgy. San Jose');
        $theirs = $this->communityWithSummary('Brgy. Sagkahan');
        $foreign = $this->analysis($theirs, ['status' => AssessmentAnalysis::STATUS_FAILED, 'summary' => null]);

        $this->expectException(ModelNotFoundException::class);

        try {
            Livewire::actingAs($this->admin())
                ->test(AiAnalysisCommunity::class, ['community' => $mine['community']])
                ->call('delete', $foreign->id);
        } finally {
            $this->assertNotNull(AssessmentAnalysis::find($foreign->id), 'Another community\'s analysis must survive.');
        }
    }
}
