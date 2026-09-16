<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\ExtensionProgram;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsFixesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{summary: AssessmentSummary, assessment: NeedsAssessment} */
    protected function seedSummary(User $admin): array
    {
        $community = Community::factory()->create(['name' => 'Brgy. San Jose']);

        $assessment = NeedsAssessment::create([
            'community_id' => $community->id, 'quarter' => 2, 'year' => 2026,
            'uploaded_by' => $admin->id, 'review_status' => 'validated',
            'respondent_first_name' => 'Lucia', 'respondent_last_name' => 'Amistoso', 'respondent_sex' => 'Female',
            'has_electricity' => 'Yes', 'available_for_training' => 'Yes',
        ]);

        $summary = AssessmentSummary::where([
            'community_id' => $community->id, 'quarter' => 2, 'year' => 2026,
        ])->firstOrFail();

        return compact('summary', 'assessment');
    }

    public function test_pending_tab_lists_draft_ai_analyses(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ['summary' => $summary, 'assessment' => $assessment] = $this->seedSummary($admin);

        AssessmentAnalysis::create([
            'needs_assessment_id' => $assessment->id,
            'assessment_summary_id' => $summary->id,
            'approval_status' => AssessmentAnalysis::APPROVAL_DRAFT,
            'status' => AssessmentAnalysis::STATUS_COMPLETED,
            'summary' => 'Income insufficiency dominates.',
            'metadata' => ['model' => 'gemini-3.6-flash'],
        ]);

        AssessmentAnalysis::create([
            'needs_assessment_id' => $assessment->id,
            'assessment_summary_id' => $summary->id,
            'approval_status' => AssessmentAnalysis::APPROVAL_APPROVED,
            'status' => AssessmentAnalysis::STATUS_COMPLETED,
            'summary' => 'Approved analysis.',
            'metadata' => ['model' => 'gemini-3.6-flash'],
        ]);

        $response = $this->actingAs($admin)->get('/analytics?tab=pending');

        $response->assertOk()
            ->assertSee('AI analyses awaiting approval')
            ->assertSee('Brgy. San Jose')
            ->assertSee('gemini-3.6-flash')
            ->assertSee(route('ai-analysis.index'))
            ->assertDontSee('Approved analysis');
    }

    public function test_negative_knowledge_gain_renders_without_double_sign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = ExtensionProgram::create([
            'code' => 'EXT-2026-060', 'title' => 'Gain Test',
            'planned_start_date' => '2026-01-01', 'planned_end_date' => '2026-12-31',
            'status' => 'ongoing', 'allocated_budget' => 0,
        ]);
        Activity::create([
            'extension_program_id' => $program->id, 'title' => 'Scored Activity',
            'planned_start_date' => '2026-03-10', 'planned_end_date' => '2026-03-10',
            'start_time' => '08:00:00', 'end_time' => '10:00:00', 'status' => 'completed',
            'pre_assessment_score' => 70, 'post_assessment_score' => 65,
        ]);

        $performance = $this->actingAs($admin)->get('/analytics?tab=performance');
        $performance->assertOk()->assertSee('-5.0 pts')->assertDontSee('+-5.0');

        $overview = $this->actingAs($admin)->get('/analytics?tab=overview');
        $overview->assertOk()->assertSee('-5.0 pts')->assertDontSee('+-5.0');

        Livewire::actingAs($admin)->test(Hub::class, ['program' => $program])
            ->assertSee('-5.0 pts')
            ->assertDontSee('+-5.0');
    }
}
