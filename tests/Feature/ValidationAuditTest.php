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
            ->set('projectForm.status', 'ongoing')
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
            ->set('projectForm.status', 'ongoing')
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
            ->set('activityForm.status', 'ongoing')
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

    public function test_beneficiary_registration_requires_age_and_municipality(): void
    {
        $admin = $this->admin();
        $project = $this->project();

        $component = Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openRegister')
            ->set('registerForm.first_name', 'Audit')
            ->set('registerForm.last_name', 'Beneficiary')
            ->set('registerForm.barangay', 'San Jose')
            ->set('registerForm.municipality', '')
            ->set('registerForm.beneficiary_category', 'Farmer')
            ->set('registerForm.age', '')
            ->call('saveRegister')
            ->assertHasErrors(['registerForm.age', 'registerForm.municipality']);

        $this->assertDatabaseMissing('beneficiaries', [
            'first_name' => 'Audit',
            'last_name' => 'Beneficiary',
        ]);

        $component
            ->set('registerForm.age', '38')
            ->set('registerForm.municipality', 'Tacloban City')
            ->call('saveRegister')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('beneficiaries', [
            'first_name' => 'Audit',
            'last_name' => 'Beneficiary',
        ]);
    }

    public function test_beneficiary_edit_requires_age_and_municipality(): void
    {
        $this->admin();
        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);
        $project = $this->project();

        $beneficiary = Beneficiary::create([
            'first_name' => 'Audit',
            'last_name' => 'Edit',
            'age' => 40,
            'gender' => 'Female',
            'phone' => '09170000000',
            'barangay' => 'San Jose',
            'municipality' => 'Tacloban City',
            'beneficiary_category' => 'Housewife',
        ]);
        $project->beneficiaries()->attach($beneficiary->id);

        Livewire::actingAs($secretary)
            ->test(Hub::class, ['project' => $project])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.age', '')
            ->set('beneficiaryEditForm.municipality', '')
            ->call('saveBeneficiaryEdit')
            ->assertHasErrors([
                'beneficiaryEditForm.age',
                'beneficiaryEditForm.municipality',
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
