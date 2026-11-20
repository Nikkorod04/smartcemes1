<?php

namespace Tests\Feature;

use App\Livewire\Colleges\Index as CollegesIndex;
use App\Livewire\Programs\Hub;
use App\Livewire\Proposals\Create as ProposalCreate;
use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\User;
use App\Services\AssessmentRules;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Item 5 / D9: the required-field decisions recorded in docs/VALIDATION-AUDIT.md.
 *
 * These are boundary tests: the required set, plus the paths that must stay
 * lenient (project edit, activity counts, assessment demographics).
 */
class ValidationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed([UserSeeder::class, CollegeSeeder::class, ProgramSeeder::class]);

        return User::where('email', 'admin@lnu.com')->firstOrFail();
    }

    protected function project(): ExtensionProject
    {
        return ExtensionProject::create([
            'code' => 'VAL-2026-001',
            'title' => 'Validation Audit Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
    }

    public function test_project_creation_requires_the_d9_set(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $admin = $this->admin();
        $college = College::where('code', 'CAS')->firstOrFail();
        $program = Program::where('title', 'Information, Communication & Education')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.college_id', (string) $college->id)
            ->set('projectForm.program_id', (string) $program->id)
            ->set('projectForm.title', 'Missing D9 fields')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.status', 'draft')
            ->call('saveProject')
            ->assertHasErrors([
                'projectForm.description',
                'projectForm.target_beneficiaries',
                'projectForm.allocated_budget',
                'projectForm.program_lead_id',
                'projectForm.community_ids',
            ]);

        $this->assertNull(ExtensionProject::where('title', 'Missing D9 fields')->first());
    }

    public function test_project_creation_accepts_the_d9_set(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $admin = $this->admin();
        $college = College::where('code', 'CAS')->firstOrFail();
        $program = Program::where('title', 'Information, Communication & Education')->firstOrFail();
        $lead = Faculty::factory()->create();
        $community = Community::create([
            'name' => 'Brgy. Validation', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.college_id', (string) $college->id)
            ->set('projectForm.program_id', (string) $program->id)
            ->set('projectForm.title', 'Complete D9 fields')
            ->set('projectForm.description', 'A complete project description for the audit.')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.target_beneficiaries', '25')
            ->set('projectForm.allocated_budget', '10000')
            ->set('projectForm.program_lead_id', (string) $lead->id)
            ->set('projectForm.status', 'draft')
            ->call('toggleProjectFormArray', 'community_ids', $community->id)
            ->call('saveProject')
            ->assertHasNoErrors();

        $project = ExtensionProject::where('title', 'Complete D9 fields')->firstOrFail();

        $this->assertSame(25, (int) $project->target_beneficiaries);
        $this->assertSame(10000.0, (float) $project->allocated_budget);
        $this->assertSame($lead->id, $project->program_lead_id);
        $this->assertSame([$community->id], $project->communities()->pluck('communities.id')->all());
    }

    public function test_project_creation_rejects_a_start_before_today(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $admin = $this->admin();
        $college = College::where('code', 'CAS')->firstOrFail();
        $program = Program::where('title', 'Information, Communication & Education')->firstOrFail();
        $lead = Faculty::factory()->create();
        $community = Community::create([
            'name' => 'Brgy. Past Start', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.college_id', (string) $college->id)
            ->set('projectForm.program_id', (string) $program->id)
            ->set('projectForm.title', 'Backdated project')
            ->set('projectForm.description', 'A description that is long enough to pass validation.')
            ->set('projectForm.planned_start_date', '2025-12-31')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.target_beneficiaries', '25')
            ->set('projectForm.allocated_budget', '10000')
            ->set('projectForm.program_lead_id', (string) $lead->id)
            ->set('projectForm.status', 'ongoing')
            ->call('toggleProjectFormArray', 'community_ids', $community->id)
            ->call('saveProject')
            ->assertHasErrors('projectForm.planned_start_date');

        $this->assertNull(ExtensionProject::where('title', 'Backdated project')->first());
    }

    public function test_future_project_only_allows_draft_before_start(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $admin = $this->admin();
        $college = College::where('code', 'CAS')->firstOrFail();
        $program = Program::where('title', 'Information, Communication & Education')->firstOrFail();
        $lead = Faculty::factory()->create();
        $community = Community::create([
            'name' => 'Brgy. Future Status', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.college_id', (string) $college->id)
            ->set('projectForm.program_id', (string) $program->id)
            ->set('projectForm.title', 'Future status project')
            ->set('projectForm.description', 'A project that has not started yet and must stay a draft.')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.target_beneficiaries', '25')
            ->set('projectForm.allocated_budget', '10000')
            ->set('projectForm.program_lead_id', (string) $lead->id)
            ->call('toggleProjectFormArray', 'community_ids', $community->id);

        foreach (['ongoing', 'completed', 'cancelled'] as $status) {
            $component
                ->set('projectForm.status', $status)
                ->call('saveProject')
                ->assertHasErrors('projectForm.status');
        }

        $this->assertNull(ExtensionProject::where('title', 'Future status project')->first());

        $component
            ->set('projectForm.status', 'draft')
            ->call('saveProject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('extension_projects', ['title' => 'Future status project']);
    }

    public function test_future_activity_only_allows_draft_before_start(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $admin = $this->admin();
        $project = $this->project();

        $component = Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openActivityForm')
            ->set('activityForm.title', 'Future status activity')
            ->set('activityForm.venue', 'Barangay Hall')
            ->set('activityForm.planned_start_date', '2026-03-10')
            ->set('activityForm.planned_end_date', '2026-03-10')
            ->set('activityForm.start_time', '08:00')
            ->set('activityForm.end_time', '12:00')
            ->set('activityForm.no_of_days', '1')
            ->set('activityForm.allocated_budget', '1000');

        foreach (['ongoing', 'completed', 'cancelled'] as $status) {
            $component
                ->set('activityForm.status', $status)
                ->call('saveActivity')
                ->assertHasErrors('activityForm.status');
        }

        $this->assertDatabaseMissing('activities', ['title' => 'Future status activity']);

        $component
            ->set('activityForm.status', 'draft')
            ->call('saveActivity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activities', ['title' => 'Future status activity']);
    }

    public function test_legacy_future_project_can_keep_its_status(): void
    {
        $admin = $this->admin();
        $project = ExtensionProject::create([
            'code' => 'VAL-2026-900',
            'title' => 'Legacy future project',
            'planned_start_date' => now()->addMonth()->toDateString(),
            'planned_end_date' => now()->addMonths(2)->toDateString(),
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openProgramEdit')
            ->set('editForm.title', 'Legacy future project renamed')
            ->call('saveProgramEdit')
            ->assertHasNoErrors();

        $this->assertSame('Legacy future project renamed', $project->fresh()->title);
        $this->assertSame('ongoing', $project->fresh()->status);
    }

    public function test_legacy_future_activity_can_keep_its_status(): void
    {
        $admin = $this->admin();
        $project = ExtensionProject::create([
            'code' => 'VAL-2026-901',
            'title' => 'Legacy future activity project',
            'planned_start_date' => now()->subMonth()->toDateString(),
            'planned_end_date' => now()->addMonths(2)->toDateString(),
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $activity = Activity::create([
            'extension_project_id' => $project->id,
            'title' => 'Legacy future activity',
            'venue' => 'Barangay Hall',
            'planned_start_date' => now()->addMonth()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'no_of_days' => 1,
            'allocated_budget' => 1000,
            'status' => 'ongoing',
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openActivityForm', $activity->id)
            ->set('activityForm.title', 'Legacy future activity renamed')
            ->call('saveActivity')
            ->assertHasNoErrors();

        $this->assertSame('Legacy future activity renamed', $activity->fresh()->title);
        $this->assertSame('ongoing', $activity->fresh()->status);
    }

    public function test_completing_a_future_activity_is_refused(): void
    {
        $admin = $this->admin();
        $project = ExtensionProject::create([
            'code' => 'VAL-2026-902',
            'title' => 'Future completion project',
            'planned_start_date' => now()->subMonth()->toDateString(),
            'planned_end_date' => now()->addMonths(2)->toDateString(),
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $activity = Activity::create([
            'extension_project_id' => $project->id,
            'title' => 'Future completion activity',
            'venue' => 'Barangay Hall',
            'planned_start_date' => now()->addMonth()->toDateString(),
            'planned_end_date' => now()->addMonth()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'no_of_days' => 1,
            'allocated_budget' => 1000,
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('completeActivity', $activity->id);

        $this->assertSame('draft', $activity->fresh()->status);
        $this->assertNull($activity->fresh()->actual_end_date);
    }

    public function test_project_edit_cannot_move_a_start_into_the_past(): void
    {
        $admin = $this->admin();
        $project = $this->project(); // starts 2026-01-01, already in the past

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openProgramEdit')
            ->set('editForm.planned_start_date', '2025-12-31')
            ->call('saveProgramEdit')
            ->assertHasErrors('editForm.planned_start_date');

        $this->assertSame('2026-01-01', $project->fresh()->planned_start_date->format('Y-m-d'));
    }

    public function test_proposal_rejects_a_start_before_today(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $user = User::factory()->create(['role' => User::ROLE_FACULTY]);
        Faculty::factory()->create(['user_id' => $user->id]);
        $project = $this->project();
        $community = Community::create([
            'name' => 'Brgy. Past Proposal', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProposalCreate::class)
            ->set('form.title', 'Backdated proposal')
            ->set('form.description', 'A proposal description long enough to pass the audit.')
            ->set('form.extension_project_id', $project->id)
            ->set('form.community_id', $community->id)
            ->set('form.proposed_start_date', '2025-12-31')
            ->set('form.proposed_end_date', '2026-01-01')
            ->set('form.budget_estimate', '5000')
            ->call('save')
            ->assertHasErrors('form.proposed_start_date');

        $component
            ->set('form.proposed_start_date', '2026-03-01')
            ->set('form.proposed_end_date', '2026-03-02')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_proposals', ['title' => 'Backdated proposal']);
    }

    public function test_activity_past_date_requires_confirmation(): void
    {
        $this->travelTo('2026-11-19 12:00:00');
        $admin = $this->admin();
        $project = $this->project();

        $component = Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openActivityForm')
            ->set('activityForm.title', 'Recorded activity')
            ->set('activityForm.venue', 'Barangay Hall')
            ->set('activityForm.planned_start_date', '2026-11-18')
            ->set('activityForm.planned_end_date', '2026-11-18')
            ->set('activityForm.start_time', '08:00')
            ->set('activityForm.end_time', '12:00')
            ->set('activityForm.no_of_days', '1')
            ->set('activityForm.allocated_budget', '1000')
            ->set('activityForm.status', 'completed')
            ->call('saveActivity')
            ->assertHasErrors('activityPastConfirmed');

        $this->assertDatabaseMissing('activities', ['title' => 'Recorded activity']);

        $component
            ->set('activityPastConfirmed', true)
            ->call('saveActivity')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activities', ['title' => 'Recorded activity']);
    }

    public function test_activity_requires_venue_and_budget_but_allows_blank_counts(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $admin = $this->admin();
        $project = $this->project();

        $component = Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openActivityForm')
            ->set('activityForm.title', 'Audit activity')
            ->set('activityForm.planned_start_date', '2026-03-10')
            ->set('activityForm.planned_end_date', '2026-03-10')
            ->set('activityForm.start_time', '08:00')
            ->set('activityForm.end_time', '12:00')
            ->set('activityForm.no_of_days', '1')
            ->set('activityForm.participants', '')
            ->set('activityForm.trainors_snapshot', '')
            ->set('activityForm.status', 'draft')
            ->call('saveActivity')
            ->assertHasErrors(['activityForm.venue', 'activityForm.allocated_budget']);

        $this->assertDatabaseMissing('activities', ['title' => 'Audit activity']);

        $component
            ->set('activityForm.venue', 'Barangay Hall')
            ->set('activityForm.allocated_budget', '0')
            ->call('saveActivity')
            ->assertHasNoErrors();

        $activity = Activity::where('title', 'Audit activity')->sole();
        $this->assertNull($activity->participants);
        $this->assertNull($activity->trainors_snapshot);
        $this->assertSame('Barangay Hall', $activity->venue);
    }

    public function test_proposal_requires_description_and_budget(): void
    {
        $this->travelTo('2026-01-01 00:00:00');
        $user = User::factory()->create(['role' => User::ROLE_FACULTY]);
        Faculty::factory()->create(['user_id' => $user->id]);
        $project = $this->project();
        $community = Community::create([
            'name' => 'Brgy. Proposal', 'municipality' => 'Tacloban City',
            'province' => 'Leyte', 'status' => 'active',
        ]);

        $component = Livewire::actingAs($user)
            ->test(ProposalCreate::class)
            ->set('form.title', 'Audit proposal')
            ->set('form.extension_project_id', $project->id)
            ->set('form.community_id', $community->id)
            ->set('form.proposed_start_date', '2026-03-01')
            ->set('form.proposed_end_date', '2026-03-02')
            ->call('save')
            ->assertHasErrors(['form.description', 'form.budget_estimate']);

        $component
            ->set('form.description', 'A proposal description long enough to pass the audit.')
            ->set('form.budget_estimate', '12000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('activity_proposals', ['title' => 'Audit proposal']);
    }

    public function test_beneficiary_registration_requires_age_and_community(): void
    {
        $admin = $this->admin();
        $project = $this->project();
        $community = Community::factory()->create(['name' => 'San Jose']);
        $project->communities()->attach($community->id);

        $component = Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Audit')
            ->set('registerForm.last_name', 'Beneficiary')
            ->set('registerForm.community_id', '')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->set('registerForm.age', '')
            ->call('saveRegister')
            ->assertHasErrors(['registerForm.age', 'registerForm.community_id']);

        $this->assertDatabaseMissing('beneficiaries', [
            'first_name' => 'Audit',
            'last_name' => 'Beneficiary',
        ]);

        $component
            ->set('registerForm.age', '38')
            ->set('registerForm.community_id', $community->id)
            ->call('saveRegister')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('beneficiaries', [
            'first_name' => 'Audit',
            'last_name' => 'Beneficiary',
        ]);
    }

    public function test_beneficiary_edit_requires_age_and_community(): void
    {
        $this->admin();
        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);
        $project = $this->project();
        $community = Community::factory()->create(['name' => 'San Jose']);
        $project->communities()->attach($community->id);

        $beneficiary = Beneficiary::create([
            'first_name' => 'Audit',
            'last_name' => 'Edit',
            'age' => 40,
            'gender' => 'Female',
            'phone' => '09170000000',
            'barangay' => 'San Jose',
            'municipality' => 'Tacloban City',
            'community_id' => $community->id,
            'extension_project_id' => $project->id,
            'beneficiary_category' => 'Housewife',
        ]);

        Livewire::actingAs($secretary)
            ->test(Hub::class, ['project' => $project])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.age', '')
            ->set('beneficiaryEditForm.community_id', '')
            ->call('saveBeneficiaryEdit')
            ->assertHasErrors([
                'beneficiaryEditForm.age',
                'beneficiaryEditForm.community_id',
            ]);
    }

    public function test_project_edit_stays_lenient_for_older_projects(): void
    {
        $admin = $this->admin();
        $project = $this->project();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openProgramEdit')
            ->set('editForm.title', 'Renamed without the new fields')
            ->call('saveProgramEdit')
            ->assertHasNoErrors();

        $this->assertSame('Renamed without the new fields', $project->fresh()->title);
    }

    public function test_assessment_demographics_remain_optional(): void
    {
        $rules = AssessmentRules::respondent();

        $this->assertStringContainsString('required', $rules['form.respondent_first_name']);
        $this->assertStringContainsString('required', $rules['form.respondent_last_name']);
        $this->assertStringContainsString('nullable', $rules['form.respondent_age']);
    }
}
