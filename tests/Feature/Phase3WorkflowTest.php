<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Review;
use App\Livewire\Availability\Index as AvailabilityIndex;
use App\Livewire\Programs\Hub;
use App\Livewire\Proposals\Index as ProposalsIndex;
use App\Livewire\RenderedHours\My;
use App\Models\Activity;
use App\Models\ActivityProposal;
use App\Models\AssessmentSummary;
use App\Models\AvailabilityRequest;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\NeedsAssessment;
use App\Models\RenderedHours;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Phase3WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBase(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretary = User::factory()->create(['role' => 'secretary']);
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'employee_id' => 'LNU-2026-0009']);

        $community = Community::factory()->create();
        $program = ExtensionProject::create([
            'code' => 'EXT-2026-010',
            'title' => 'Workflow Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
            'program_lead_id' => $faculty->id,
        ]);

        return compact('admin', 'secretary', 'facultyUser', 'faculty', 'community', 'program');
    }

    // ============ PROPOSALS ============

    public function test_faculty_cannot_approve_proposals(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data);

        Livewire::actingAs($data['facultyUser'])
            ->test(ProposalsIndex::class)
            ->call('approve', $proposal->id)
            ->assertForbidden();
    }

    public function test_admin_approval_auto_creates_activity(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data);

        Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('openApprove', $proposal->id)
            ->call('approve', $proposal->id);

        $proposal->refresh();

        $this->assertSame('approved', $proposal->status);
        $this->assertNotNull($proposal->created_activity_id);
        $this->assertNotNull($proposal->admin_approved_by);

        $activity = Activity::find($proposal->created_activity_id);
        $this->assertSame('draft', $activity->status);
        $this->assertSame($proposal->title, $activity->title);
        $this->assertSame($proposal->extension_project_id, $activity->extension_project_id);
    }

    public function test_proposal_action_modal_does_not_render_the_detail_modal_at_the_same_time(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data);

        $component = Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('openApprove', $proposal->id)
            ->assertSet('actionId', $proposal->id)
            ->assertSet('detailId', null)
            ->assertSet('showApprove', true)
            ->assertSet('showReject', false)
            ->assertDontSeeHtml('id="proposal-detail-title"');

        $component->call('closeModals')
            ->call('openReject', $proposal->id)
            ->assertSet('actionId', $proposal->id)
            ->assertSet('detailId', null)
            ->assertSet('showApprove', false)
            ->assertSet('showReject', true)
            ->assertDontSeeHtml('id="proposal-detail-title"');
    }

    public function test_approval_blocked_when_proposed_dates_outside_program_range(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data, [
            'proposed_start_date' => '2025-11-01',
            'proposed_end_date' => '2026-03-01', // starts before program range
        ]);

        Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('openApprove', $proposal->id)
            ->call('approve', $proposal->id);

        $proposal->refresh();
        $this->assertSame('pending', $proposal->status);
        $this->assertNull($proposal->created_activity_id);
    }

    public function test_rejection_requires_reason(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data);

        Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('openReject', $proposal->id)
            ->call('reject', $proposal->id)
            ->assertHasErrors(['rejectionReason']);

        $proposal->refresh();
        $this->assertSame('pending', $proposal->status);
    }

    public function test_rejection_records_reason_and_notifies(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data);

        Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('openReject', $proposal->id)
            ->set('rejectionReason', 'Budget exceeds the FY allocation ceiling; revise costing.')
            ->call('reject', $proposal->id);

        $proposal->refresh();
        $this->assertSame('rejected', $proposal->status);
        $this->assertNotNull($proposal->rejection_reason);
        $this->assertNotNull($data['facultyUser']->notifications()->first());
    }

    public function test_admin_detail_renders_downloadable_attachment_and_special_order_links(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data, [
            'special_order_path' => 'special-orders/demo/peace-corners-special-order.pdf',
        ]);
        $proposal->documents()->create([
            'file_name' => 'solid-start-proposal.pdf',
            'file_path' => 'proposal-documents/demo/solid-start-proposal.pdf',
            'file_size' => 240_000,
            'file_type' => 'pdf',
            'uploaded_at' => now(),
        ]);

        $docUrl = Storage::disk('public')->url('proposal-documents/demo/solid-start-proposal.pdf');
        $soUrl = Storage::disk('public')->url('special-orders/demo/peace-corners-special-order.pdf');

        Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('viewDetail', $proposal->id)
            ->assertSeeHtml('href="'.$docUrl.'" download')
            ->assertSeeHtml('href="'.$soUrl.'" download');
    }

    public function test_proposals_without_special_order_render_no_so_link(): void
    {
        $data = $this->seedBase();
        $proposal = $this->makeProposal($data);

        Livewire::actingAs($data['admin'])
            ->test(ProposalsIndex::class)
            ->call('viewDetail', $proposal->id)
            ->assertDontSeeHtml('/storage/special-orders/');
    }

    // ============ AVAILABILITY ============

    public function test_decline_requires_reason(): void
    {
        $data = $this->seedBase();
        $request = $this->makeAvailability($data);

        Livewire::actingAs($data['facultyUser'])
            ->test(AvailabilityIndex::class)
            ->call('openDecline', $request->id)
            ->call('confirmDecline')
            ->assertHasErrors(['declineReason']);

        $request->refresh();
        $this->assertSame('pending', $request->status);
    }

    public function test_accept_refused_on_schedule_overlap(): void
    {
        $data = $this->seedBase();

        // Existing assigned activity overlapping the requested slot.
        $conflicting = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Existing Assignment',
            'planned_start_date' => '2026-08-22',
            'planned_end_date' => '2026-08-22',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'ongoing',
        ]);
        $conflicting->faculty()->syncWithoutDetaching([$data['faculty']->id]);

        $request = $this->makeAvailability($data, ['date' => '2026-08-22', 'start_time' => '10:00:00', 'end_time' => '12:00:00']);

        Livewire::actingAs($data['facultyUser'])
            ->test(AvailabilityIndex::class)
            ->call('accept', $request->id);

        $request->refresh();
        $this->assertSame('pending', $request->status); // hard-blocked
    }

    public function test_accept_succeeds_without_overlap(): void
    {
        $data = $this->seedBase();
        $request = $this->makeAvailability($data, ['date' => '2026-09-01', 'start_time' => '09:00:00', 'end_time' => '11:00:00']);

        Livewire::actingAs($data['facultyUser'])
            ->test(AvailabilityIndex::class)
            ->call('accept', $request->id);

        $request->refresh();
        $this->assertSame('accepted', $request->status);
        $this->assertNotNull($request->responded_at);
    }

    // ============ RENDERED HOURS ============

    public function test_completion_auto_drafts_hours_and_skips_overnight(): void
    {
        $data = $this->seedBase();

        $normal = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Normal Schedule',
            'planned_start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-01',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'ongoing',
        ]);
        $overnight = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Overnight Guard',
            'planned_start_date' => '2026-06-02',
            'planned_end_date' => '2026-06-02',
            'start_time' => '20:00:00',
            'end_time' => '04:00:00',
            'status' => 'ongoing',
        ]);
        $normal->faculty()->syncWithoutDetaching([$data['faculty']->id]);
        $overnight->faculty()->syncWithoutDetaching([$data['faculty']->id]);

        Livewire::actingAs($data['admin'])
            ->test(Hub::class, ['project' => $data['program']])
            ->call('completeActivity', $normal->id)
            ->call('completeActivity', $overnight->id);

        $this->assertSame(1, RenderedHours::count()); // overnight skipped (8.9 guard)
        $entry = RenderedHours::first();
        $this->assertSame(4.0, (float) $entry->hours);
        $this->assertSame('auto', $entry->source);
        $this->assertSame('pending', $entry->status);
    }

    public function test_faculty_can_only_adjust_hours_down(): void
    {
        $data = $this->seedBase();
        $entry = RenderedHours::create([
            'faculty_id' => $data['faculty']->id,
            'activity_id' => Activity::create([
                'extension_project_id' => $data['program']->id,
                'title' => 'Activity A',
                'planned_start_date' => '2026-07-01',
                'planned_end_date' => '2026-07-01',
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'status' => 'completed',
            ])->id,
            'date' => '2026-07-01',
            'hours' => 4.0,
            'source' => 'auto',
            'status' => 'pending',
        ]);

        Livewire::actingAs($data['facultyUser'])
            ->test(My::class)
            ->call('startAdjust', $entry->id)
            ->set('adjustedHours', '5.0')
            ->set('adjustNote', 'Tried to inflate hours')
            ->call('saveAdjust')
            ->assertHasErrors(['adjustedHours']);

        $entry->refresh();
        $this->assertSame(4.0, (float) $entry->hours);

        Livewire::actingAs($data['facultyUser'])
            ->test(My::class)
            ->call('startAdjust', $entry->id)
            ->set('adjustedHours', '2.5')
            ->set('adjustNote', 'Co-led — partial session.')
            ->call('saveAdjust');

        $entry->refresh();
        $this->assertSame(2.5, (float) $entry->hours);
        $this->assertSame('manual', $entry->source);
    }

    // ============ ASSESSMENT REVIEW ============

    public function test_secretary_validation_recomputes_summary(): void
    {
        $data = $this->seedBase();
        $assessment = NeedsAssessment::create([
            'community_id' => $data['community']->id,
            'quarter' => 2,
            'year' => 2026,
            'uploaded_by' => $data['facultyUser']->id,
            'review_status' => 'pending',
            'respondent_first_name' => 'Test',
            'respondent_last_name' => 'Respondent',
            'respondent_sex' => 'Male',
            'has_electricity' => 'Yes',
            'available_for_training' => 'Yes',
        ]);

        $summaryBefore = AssessmentSummary::where([
            'community_id' => $data['community']->id, 'quarter' => 2, 'year' => 2026,
        ])->first();
        $this->assertNotNull($summaryBefore);

        Livewire::actingAs($data['secretary'])
            ->test(Review::class)
            ->call('openValidate', $assessment->id)
            ->call('confirmValidate');

        $assessment->refresh();
        $this->assertSame('validated', $assessment->review_status);

        $summaryAfter = AssessmentSummary::where([
            'community_id' => $data['community']->id, 'quarter' => 2, 'year' => 2026,
        ])->first();

        // 6.10: recompute fires on review_status change — same batch count,
        // freshly stamped last_calculated_at.
        $this->assertSame($summaryBefore->total_responses, $summaryAfter->total_responses);
        $this->assertTrue($summaryAfter->last_calculated_at >= $summaryBefore->last_calculated_at);
    }

    public function test_return_requires_remarks(): void
    {
        $data = $this->seedBase();
        $assessment = NeedsAssessment::create([
            'community_id' => $data['community']->id,
            'quarter' => 2,
            'year' => 2026,
            'uploaded_by' => $data['facultyUser']->id,
            'review_status' => 'pending',
            'respondent_first_name' => 'Test',
            'respondent_last_name' => 'Respondent',
        ]);

        Livewire::actingAs($data['secretary'])
            ->test(Review::class)
            ->call('openReturn', $assessment->id)
            ->call('confirmReturn')
            ->assertHasErrors(['returnRemarks']);

        $assessment->refresh();
        $this->assertSame('pending', $assessment->review_status);
    }

    // ============ helpers ============

    protected function makeProposal(array $data, array $overrides = []): ActivityProposal
    {
        return ActivityProposal::create([
            ...$overrides,
            'faculty_id' => $data['faculty']->id,
            'extension_project_id' => $data['program']->id,
            'community_id' => $data['community']->id,
            'title' => 'Test Proposal',
            'proposed_start_date' => $overrides['proposed_start_date'] ?? '2026-06-01',
            'proposed_end_date' => $overrides['proposed_end_date'] ?? '2026-06-30',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    protected function makeAvailability(array $data, array $overrides = []): AvailabilityRequest
    {
        $activity = Activity::create([
            'extension_project_id' => $data['program']->id,
            'title' => 'Requested Activity',
            'planned_start_date' => '2026-08-22',
            'planned_end_date' => '2026-08-22',
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
            'status' => 'draft',
        ]);

        return AvailabilityRequest::create([
            ...$overrides,
            'activity_id' => $activity->id,
            'faculty_id' => $data['faculty']->id,
            'date' => $overrides['date'] ?? '2026-08-22',
            'start_time' => $overrides['start_time'] ?? '08:00:00',
            'end_time' => $overrides['end_time'] ?? '11:00:00',
            'status' => 'pending',
            'requested_by' => $data['admin']->id,
            'requested_at' => now(),
        ]);
    }
}
