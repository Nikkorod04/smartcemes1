<?php

namespace Tests\Feature;

use App\Jobs\GenerateAssessmentAnalysis;
use App\Jobs\GenerateProgramNarrative;
use App\Livewire\ProgramNarratives;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\NeedsAssessment;
use App\Models\ProgramNarrative;
use App\Models\User;
use App\Services\Ai\AiUnavailableException;
use App\Services\Ai\GeminiClient;
use App\Services\AssessmentAnalysisService;
use App\Services\ProgramNarrativeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase5AiTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSummary(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $community = Community::factory()->create(['name' => 'Brgy. San Jose']);
        $assessment = NeedsAssessment::create([
            'community_id' => $community->id, 'quarter' => 2, 'year' => 2026,
            'uploaded_by' => $admin->id, 'review_status' => 'validated',
            'respondent_first_name' => 'Lucia', 'respondent_last_name' => 'Amistoso', 'respondent_sex' => 'Female',
            'has_electricity' => 'Yes', 'available_for_training' => 'Yes',
        ]);
        $summary = AssessmentSummary::where([
            'community_id' => $community->id, 'quarter' => 2, 'year' => 2026,
        ])->first();

        $program = ExtensionProject::create([
            'code' => 'EXT-2026-050', 'title' => 'AI Test Program',
            'planned_start_date' => '2026-01-01', 'planned_end_date' => '2026-12-31',
            'status' => 'ongoing', 'allocated_budget' => 10000,
        ]);

        return compact('admin', 'secretary', 'community', 'assessment', 'summary', 'program');
    }

    protected function fakeGemini(array $data): void
    {
        config(['smartcemes.ai.key' => 'test-key', 'smartcemes.ai.model' => 'gemini-2.0-flash']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode($data)]]],
                ]],
                'usageMetadata' => ['totalTokenCount' => 900],
            ]),
        ]);
    }

    public function test_non_admin_cannot_generate(): void
    {
        $data = $this->seedSummary();
        $secretary = User::factory()->create(['role' => 'secretary']);

        $this->actingAs($secretary);
        $this->expectException(HttpException::class);

        app(AssessmentAnalysisService::class)->generateFor($data['summary'], $data['assessment']->id);
    }

    public function test_generation_creates_pending_draft_and_dispatches_job(): void
    {
        // v4.4: dispatch is synchronous — fake the Bus so the job does not
        // hit the live API here; the row stays pending for this assertion.
        Bus::fake();
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);

        $analysis = app(AssessmentAnalysisService::class)->generateFor($data['summary'], $data['assessment']->id);

        $this->assertSame('pending', $analysis->status);
        $this->assertSame('draft', $analysis->approval_status);
        $this->assertSame(config('smartcemes.ai.model'), $analysis->metadata['model']);
        Bus::assertDispatched(GenerateAssessmentAnalysis::class);
    }

    public function test_sync_generation_completes_immediately(): void
    {
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);

        $this->fakeGemini([
            'summary' => 'Income insufficiency dominates.',
            'problems_identified' => [['need' => 'Low income', 'evidence' => '61% of responses']],
            'recommendations' => [['rank' => 1, 'title' => 'Food processing cohort', 'detail' => '5 sessions', 'priority' => 'High']],
        ]);

        $analysis = app(AssessmentAnalysisService::class)->generateFor($data['summary'], $data['assessment']->id);

        $analysis->refresh();
        $this->assertSame('completed', $analysis->status);
        $this->assertSame('Income insufficiency dominates.', $analysis->summary);
        $this->assertNull($analysis->error_message);
    }

    public function test_sync_generation_failure_persists_failed_state(): void
    {
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response('quota exceeded', 429)]);

        $analysis = app(AssessmentAnalysisService::class)->generateFor($data['summary'], $data['assessment']->id);

        $analysis->refresh();
        $this->assertSame('failed', $analysis->status);
        $this->assertStringContainsString('Analysis unavailable', $analysis->error_message);
    }

    public function test_job_completes_with_fields_and_provenance(): void
    {
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);

        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'raw_extracted_data' => null,
            'approval_status' => 'draft',
            'status' => 'pending',
        ]);

        $this->fakeGemini([
            'summary' => 'Income insufficiency dominates.',
            'problems_identified' => [['need' => 'Low income', 'evidence' => '61% of responses']],
            'recommendations' => [['rank' => 1, 'title' => 'Food processing cohort', 'detail' => '5 sessions', 'priority' => 'High']],
        ]);

        (new GenerateAssessmentAnalysis($analysis->id))->handle(app(GeminiClient::class));

        $analysis->refresh();
        $this->assertSame('completed', $analysis->status);
        $this->assertSame('Income insufficiency dominates.', $analysis->summary);
        $this->assertSame(config('smartcemes.ai.model'), $analysis->metadata['model']);
        // R6/D-R10: the analysis prompt was rewritten to PromptV2, which carries
        // the interagency catalogue. The version tag moves with it so a stored
        // analysis stays reproducible from metadata alone.
        $this->assertSame('v2', $analysis->metadata['prompt_version']);
        $this->assertSame('gemini', $analysis->metadata['api']);
        $this->assertSame(900, $analysis->metadata['tokens']);

        // Gemini reports no confidence — a derived data-confidence is persisted instead.
        $this->assertNotNull($analysis->confidence_score);
        $this->assertGreaterThanOrEqual(0.10, $analysis->confidence_score);
        $this->assertLessThanOrEqual(0.95, $analysis->confidence_score);
        $this->assertStringContainsString('derived', $analysis->metadata['confidence_basis']);
    }

    public function test_aggregation_before_send_no_pii_leaves_system(): void
    {
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);
        config(['smartcemes.ai.key' => 'test-key']);

        $sent = null;
        Http::fake(function ($request) use (&$sent) {
            $sent = $request->data();

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"summary":"x"}']]]]]]);
        });

        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'approval_status' => 'draft', 'status' => 'pending',
        ]);

        (new GenerateAssessmentAnalysis($analysis->id))->handle(app(GeminiClient::class));

        $json = json_encode($sent);
        $this->assertStringNotContainsString('Lucia', $json);
        $this->assertStringNotContainsString('Amistoso', $json);
        $this->assertStringContainsString('total_responses', $json);
    }

    public function test_job_failure_persists_terminal_failed_state(): void
    {
        $data = $this->seedSummary();
        config(['smartcemes.ai.key' => null]);

        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'raw_extracted_data' => null,
            'approval_status' => 'draft', 'status' => 'pending',
        ]);

        $job = new GenerateAssessmentAnalysis($analysis->id);
        $job->failed(new AiUnavailableException('No API key configured (GEMINI_API_KEY).'));

        $analysis->refresh();
        $this->assertSame('failed', $analysis->status);
        $this->assertStringContainsString('Analysis unavailable', $analysis->error_message);
    }

    public function test_only_admin_can_approve_and_discard(): void
    {
        $data = $this->seedSummary();
        $secretary = User::factory()->create(['role' => 'secretary']);

        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'summary' => 'Draft text',
            'recommendations' => [['rank' => 1, 'title' => 'X', 'detail' => 'Y', 'priority' => 'High']],
            'problems_identified' => [['need' => 'N', 'evidence' => 'E']],
            'approval_status' => 'draft', 'status' => 'completed',
        ]);

        // D4 separation of duties: secretary cannot approve.
        $this->actingAs($secretary);
        try {
            app(AssessmentAnalysisService::class)->approve($analysis);
            $this->fail('Expected HttpException');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $analysis->refresh();
            $this->assertSame('draft', $analysis->approval_status);
        }

        $this->actingAs($data['admin']);
        app(AssessmentAnalysisService::class)->approve($analysis->fresh());

        $analysis->refresh();
        $this->assertSame('approved', $analysis->approval_status);
        $this->assertNotNull($analysis->approved_by);

        // 6.10: approved content stamps the summary.
        $data['summary']->refresh();
        $this->assertSame('Draft text', $data['summary']->ai_analysis);
        $this->assertNotNull($data['summary']->ai_analysis_generated_at);
    }

    public function test_narrative_generation_completes_without_approval_gate(): void
    {
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);

        Bus::fake();
        $narrative = app(ProgramNarrativeService::class)->generateFor($data['program']);
        Bus::assertDispatched(GenerateProgramNarrative::class);

        $this->assertSame('pending', $narrative->status);

        $this->fakeGemini([
            'summary' => 'On pace with 1 of 2 objectives met.',
            'health_label' => 'on-track',
            'risks' => ['Budget overrun risk', 'Attendance dips', 'Pace slower than plan'],
            'recommendations' => [['action' => 'Add cohorts', 'rationale' => 'Close reach gap', 'priority' => 'High']],
        ]);

        (new GenerateProgramNarrative($narrative->id))->handle(app(GeminiClient::class));

        $narrative->refresh();
        $this->assertSame('completed', $narrative->status); // no approval gate
        $this->assertSame('on-track', $narrative->health_label);
        $this->assertCount(3, $narrative->risks);
        $this->assertNotNull($narrative->generated_at);
        $this->assertSame('gemini', $narrative->metadata['api']);
        $this->assertNotNull($narrative->confidence_score);
        $this->assertGreaterThanOrEqual(0.10, $narrative->confidence_score);
        $this->assertLessThanOrEqual(0.95, $narrative->confidence_score);
    }

    public function test_gemini_failure_raises_unavailable_exception(): void
    {
        config(['smartcemes.ai.key' => 'test-key']);
        Http::fake(['*generativelanguage.googleapis.com*' => Http::response('quota exceeded', 429)]);

        $this->expectException(AiUnavailableException::class);
        (new GeminiClient)->generate('test prompt');
    }

    public function test_narrative_unavailable_state_is_first_class(): void
    {
        $data = $this->seedSummary();
        $narrative = ProgramNarrative::create([
            'extension_project_id' => $data['program']->id,
            'generated_by' => $data['admin']->id,
            'status' => 'failed',
            'error_message' => 'Narrative unavailable — quota exceeded.',
            'generated_at' => now(),
        ]);

        Livewire::actingAs($data['admin'])
            ->test(ProgramNarratives::class)
            ->assertSee('Narrative unavailable')
            ->assertSee('quota exceeded');
    }

    public function test_ai_analysis_page_renders_with_rows(): void
    {
        // Regression: eager load must use assessmentSummary (the `summary`
        // TEXT column shadows the belongsTo — handoff gotcha).
        $data = $this->seedSummary();
        $this->actingAs($data['admin']);

        AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'summary' => 'Draft insight text',
            'approval_status' => 'draft',
            'status' => 'completed',
            'metadata' => ['model' => 'test-model', 'api' => 'gemini', 'prompt_version' => 'v1'],
        ]);

        $this->get('/ai-analysis')
            ->assertOk()
            ->assertSee('Draft insight text')
            ->assertSee($data['summary']->community->name);
    }

    public function test_ai_pages_are_admin_only(): void
    {
        $data = $this->seedSummary();
        $secretary = User::factory()->create(['role' => 'secretary']);
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($data['admin'])->get('/ai-analysis')->assertOk();
        $this->actingAs($secretary)->get('/ai-analysis')->assertForbidden();
        $this->actingAs($faculty)->get('/ai-analysis')->assertForbidden();
        $this->actingAs($faculty)->get('/program-narratives')->assertForbidden();
    }
}
