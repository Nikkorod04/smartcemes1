<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Item 3 / D8: correcting a beneficiary profile (after-defense revision).
 *
 * The asymmetry is deliberate: Admin creates, enrolls and imports; only the
 * Secretary edits. These tests pin both the edit and the boundary.
 */
class BeneficiaryEditTest extends TestCase
{
    use RefreshDatabase;

    protected ?ExtensionProject $currentProgram = null;

    protected ?Community $currentCommunity = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function secretary(): User
    {
        return User::factory()->create(['role' => User::ROLE_SECRETARY]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function program(): ExtensionProject
    {
        $community = Community::factory()->create(['name' => 'Brgy. Edit']);

        $program = ExtensionProject::create([
            'code' => 'EXT-2026-777',
            'title' => 'Beneficiary Edit Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);

        $program->communities()->attach($community->id);

        $this->currentProgram = $program;
        $this->currentCommunity = $community;

        return $program;
    }

    protected function beneficiary(array $overrides = []): Beneficiary
    {
        return Beneficiary::create(array_merge([
            'first_name' => 'Maria',
            'middle_name' => null,
            'last_name' => 'Santos',
            'age' => 41,
            'gender' => 'Female',
            'phone' => '0917 000 0000',
            'barangay' => 'San Jose',
            'municipality' => 'Tacloban City',
            'community_id' => $this->currentCommunity?->id,
            'extension_project_id' => $this->currentProgram?->id,
            'beneficiary_category' => 'Housewife',
        ], $overrides));
    }

    public function test_the_secretary_edits_an_enrolled_beneficiary(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->assertSet('showBeneficiaryEdit', true)
            ->assertSet('beneficiaryEditForm.first_name', 'Maria')
            ->assertSet('beneficiaryEditForm.community_id', $this->currentCommunity->id)
            ->set('beneficiaryEditForm.last_name', 'Santos-Reyes')
            ->set('beneficiaryEditForm.age', '35')
            ->set('beneficiaryEditForm.phone', '0918 111 2222')
            ->call('saveBeneficiaryEdit')
            ->assertSet('showBeneficiaryEdit', false)
            ->assertHasNoErrors();

        $beneficiary->refresh();

        // One registry row: every surface that reads it sees the correction.
        $this->assertSame('Santos-Reyes', $beneficiary->last_name);
        $this->assertSame(35, $beneficiary->age);
        $this->assertSame('0918 111 2222', $beneficiary->phone);

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->set('tab', 'beneficiaries')
            ->assertSee('Santos-Reyes');
    }

    public function test_the_edit_is_audited_on_the_profile_and_the_project(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);
        $secretary = $this->secretary();

        Livewire::actingAs($secretary)
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.middle_name', 'Cruz')
            ->call('saveBeneficiaryEdit')
            ->assertHasNoErrors();

        $projectEntry = Activity::query()
            ->where('event', 'beneficiary_updated')
            ->where('subject_type', ExtensionProject::class)
            ->where('subject_id', $program->id)
            ->sole();

        $this->assertSame($secretary->id, $projectEntry->causer_id);
        $this->assertSame($beneficiary->id, $projectEntry->properties['beneficiary_id']);

        $this->assertTrue(
            Activity::query()
                ->where('event', 'updated')
                ->where('subject_type', Beneficiary::class)
                ->where('subject_id', $beneficiary->id)
                ->exists(),
            'The model-level log should record the changed fields.'
        );
    }

    public function test_admin_manages_beneficiaries_but_cannot_edit(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);
        $admin = $this->admin();

        $this->assertTrue($admin->can('manageBeneficiaries', $program));
        $this->assertFalse($admin->can('editBeneficiary', $program));

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $program])
            ->set('tab', 'beneficiaries')
            ->assertDontSee('openBeneficiaryEdit');
    }

    public function test_a_project_lead_faculty_cannot_edit(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);

        $facultyUser = User::factory()->create(['role' => User::ROLE_FACULTY]);
        $faculty = Faculty::factory()->create(['user_id' => $facultyUser->id]);
        $program->update(['program_lead_id' => $faculty->id]);

        $program->refresh();

        $this->assertTrue($facultyUser->can('view', $program), 'The lead can open the hub.');
        $this->assertFalse($facultyUser->can('editBeneficiary', $program));

        Livewire::actingAs($facultyUser)
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->assertForbidden();
    }

    public function test_renaming_into_another_profile_warns_and_needs_confirmation(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);

        // Someone else with the name this edit is about to take.
        $this->beneficiary([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'barangay' => 'San Jose',
        ]);

        $component = Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.first_name', 'Ana')
            ->set('beneficiaryEditForm.last_name', 'Reyes')
            ->call('saveBeneficiaryEdit')
            ->assertSet('showBeneficiaryEdit', true)
            ->assertSee('Possible duplicate found');

        $this->assertSame('Maria', $beneficiary->fresh()->first_name, 'Nothing is saved before confirmation.');

        $component
            ->call('editConfirmedSave')
            ->assertSet('showBeneficiaryEdit', false)
            ->assertHasNoErrors();

        $this->assertSame('Ana', $beneficiary->fresh()->first_name);
        $this->assertSame('Reyes', $beneficiary->fresh()->last_name);
    }

    public function test_a_phone_correction_does_not_warn_about_the_same_person(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);

        // A same-name profile elsewhere in the registry: the edit must exclude
        // the person being edited, and an unchanged name needs no dedup pass.
        $this->beneficiary();

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.phone', '0918 111 2222')
            ->call('saveBeneficiaryEdit')
            ->assertSet('showBeneficiaryEdit', false)
            ->assertHasNoErrors();

        $this->assertSame('0918 111 2222', $beneficiary->fresh()->phone);
    }

    public function test_required_fields_are_enforced(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.first_name', '')
            ->set('beneficiaryEditForm.community_id', '')
            ->set('beneficiaryEditForm.beneficiary_category', '')
            ->call('saveBeneficiaryEdit')
            ->assertHasErrors([
                'beneficiaryEditForm.first_name',
                'beneficiaryEditForm.community_id',
                'beneficiaryEditForm.beneficiary_category',
            ]);

        $this->assertSame('Maria', $beneficiary->fresh()->first_name);
    }

    public function test_the_project_is_fixed_once_assigned_and_tampering_is_ignored(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();

        $activity = \App\Models\Activity::create([
            'extension_project_id' => $program->id,
            'title' => 'Old project session',
            'planned_start_date' => '2026-03-01',
            'planned_end_date' => '2026-03-01',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'status' => 'completed',
        ]);
        $activity->attendances()->create([
            'beneficiary_id' => $beneficiary->id,
            'attendance_date' => '2026-03-01',
            'status' => 'present',
        ]);

        $otherCommunity = Community::factory()->create(['name' => 'Brgy. Destination']);
        $otherProject = ExtensionProject::create([
            'code' => 'EXT-2026-888',
            'title' => 'Destination Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $otherProject->communities()->attach($otherCommunity->id);

        // Even a tampered project value is ignored: the stored project wins.
        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.extension_project_id', $otherProject->id)
            ->call('saveBeneficiaryEdit')
            ->assertHasNoErrors()
            ->assertSet('showBeneficiaryEdit', false);

        $beneficiary->refresh();
        $this->assertSame($program->id, $beneficiary->extension_project_id, 'The project must not change.');
        $this->assertSame($this->currentCommunity->id, $beneficiary->community_id);
        $this->assertSame(1, $beneficiary->attendances()->count(), 'Past attendance stays with the old activity.');
        $this->assertSame(1, $activity->attendances()->count());
    }

    public function test_the_edit_rejects_a_community_from_another_project(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();

        $otherCommunity = Community::factory()->create(['name' => 'Brgy. Elsewhere']);

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->set('beneficiaryEditForm.community_id', $otherCommunity->id)
            ->call('saveBeneficiaryEdit')
            ->assertHasErrors('beneficiaryEditForm.community_id');

        $this->assertSame($this->currentCommunity->id, $beneficiary->fresh()->community_id);
    }

    public function test_a_registry_only_beneficiary_cannot_be_edited_from_a_project_roster(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary(['extension_project_id' => null]); // not enrolled

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->call('openBeneficiaryEdit', $beneficiary->id)
            ->assertNotFound();
    }

    public function test_the_roster_shows_edit_to_the_secretary_only(): void
    {
        $program = $this->program();
        $beneficiary = $this->beneficiary();
        $program->beneficiaries()->attach($beneficiary->id);

        Livewire::actingAs($this->secretary())
            ->test(Hub::class, ['project' => $program])
            ->set('tab', 'beneficiaries')
            ->assertSee('openBeneficiaryEdit');

        Livewire::actingAs($this->admin())
            ->test(Hub::class, ['project' => $program])
            ->set('tab', 'beneficiaries')
            ->assertDontSee('openBeneficiaryEdit');
    }
}
