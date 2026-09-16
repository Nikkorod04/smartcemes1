<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Wizard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Blueprint v4.9 §7 conditional rules: children appear only when the parent
 * answer allows it, and flipping a parent to a hiding value clears the
 * children so saved records stay internally consistent.
 */
class AssessmentWizardConditionalTest extends TestCase
{
    use RefreshDatabase;

    protected function wizard()
    {
        return Livewire::actingAs(User::factory()->create(['role' => 'faculty']))
            ->test(Wizard::class);
    }

    /* ============ Rules 1-8: show/hide + clear ============ */

    public function test_continuing_studies_yes_shows_area_of_interest_and_no_clears_it(): void
    {
        $this->wizard()
            ->assertDontSee('Area of Educational Interest')
            ->set('form.interested_in_continuing_studies', 'Yes')
            ->assertSee('Area of Educational Interest')
            ->set('form.areas_of_educational_interest', 'Computer literacy')
            ->set('form.interested_in_continuing_studies', 'No')
            ->assertDontSee('Area of Educational Interest')
            ->assertSet('form.areas_of_educational_interest', '');
    }

    public function test_health_programs_chain_shows_and_clears_benefits_and_programs(): void
    {
        $this->wizard()
            ->assertDontSee('Benefits from Barangay Programs')
            ->set('form.has_barangay_health_programs', 'Yes')
            ->assertSee('Benefits from Barangay Programs')
            ->assertDontSee('Programs Benefited From')
            ->set('form.benefits_from_barangay_programs', 'Yes')
            ->assertSee('Programs Benefited From')
            ->set('form.programs_benefited_from', ['Health education'])
            // Flip the middle answer — the grandchild clears too.
            ->set('form.benefits_from_barangay_programs', 'No')
            ->assertDontSee('Programs Benefited From')
            ->assertSet('form.programs_benefited_from', [])
            // Flip the parent — the whole chain clears.
            ->set('form.has_barangay_health_programs', 'No')
            ->assertDontSee('Benefits from Barangay Programs')
            ->assertSet('form.benefits_from_barangay_programs', '');
    }

    public function test_own_toilet_yes_shows_type_and_no_clears_it(): void
    {
        $this->wizard()
            ->assertDontSee('Toilet Type')
            ->set('form.has_own_toilet', 'Yes')
            ->assertSee('Toilet Type')
            ->set('form.toilet_type', 'Flush toilet')
            ->set('form.has_own_toilet', 'No')
            ->assertDontSee('Toilet Type')
            ->assertSet('form.toilet_type', '');
    }

    public function test_keeps_animals_yes_shows_animals_and_no_clears_it(): void
    {
        $this->wizard()
            ->assertDontSee('Animals Kept')
            ->set('form.keeps_animals', 'Yes')
            ->assertSee('Animals Kept')
            ->call('toggleOption', 'animals_kept', 'Chicken')
            ->set('form.keeps_animals', 'No')
            ->assertDontSee('Animals Kept')
            ->assertSet('form.animals_kept', []);
    }

    public function test_electricity_toggles_between_appliances_and_light_source(): void
    {
        $this->wizard()
            // Nothing answered — both hidden.
            ->assertDontSee('Appliances Owned')
            ->assertDontSee('Light Source Without Power')
            // Yes → appliances only.
            ->set('form.has_electricity', 'Yes')
            ->assertSee('Appliances Owned')
            ->assertDontSee('Light Source Without Power')
            ->call('toggleOption', 'appliances_owned', 'Television')
            // No → lighting only, appliances cleared.
            ->set('form.has_electricity', 'No')
            ->assertDontSee('Appliances Owned')
            ->assertSee('Light Source Without Power')
            ->assertSet('form.appliances_owned', [])
            ->set('form.light_source_without_power', 'Solar lamp')
            // Back to Yes → lighting cleared.
            ->set('form.has_electricity', 'Yes')
            ->assertSee('Appliances Owned')
            ->assertDontSee('Light Source Without Power')
            ->assertSet('form.light_source_without_power', '');
    }

    public function test_member_of_organization_yes_shows_org_fields_and_no_clears_them(): void
    {
        $this->wizard()
            ->assertSee('Member of Organization') // visible while household is not None
            ->assertDontSee('Organization Type')
            ->set('form.member_of_organization', 'Yes')
            ->assertSee('Organization Type')
            ->assertSee('Organization Usual')
            ->assertSee('Position in Organization')
            ->set('form.organization_types', 'Barangay council')
            ->set('form.position_in_organization', 'Member')
            ->set('form.member_of_organization', 'No')
            ->assertDontSee('Organization Type')
            ->assertSet('form.organization_types', '')
            ->assertSet('form.position_in_organization', '');
    }

    public function test_available_for_training_no_shows_reason_and_yes_clears_it(): void
    {
        $this->wizard()
            ->assertDontSee('Reason Not Available')
            ->set('form.available_for_training', 'No')
            ->assertSee('Reason Not Available')
            ->assertSee('Work schedule conflict')
            ->set('form.reason_not_available', 'Health reasons')
            ->set('form.available_for_training', 'Yes')
            ->assertDontSee('Reason Not Available')
            ->assertSet('form.reason_not_available', '');
    }

    /* ============ Rule 9: household members in organization = None ============ */

    public function test_household_none_hides_and_derives_member_as_no(): void
    {
        $this->wizard()
            ->set('form.member_of_organization', 'Yes')
            ->set('form.organization_types', 'Youth group')
            ->set('form.household_members_in_organization', 'None')
            ->assertDontSee('Member of Organization')
            ->assertSet('form.member_of_organization', 'No')
            ->assertSet('form.organization_types', '');
    }

    public function test_changing_away_from_none_re_shows_member_cleared_for_re_answer(): void
    {
        $this->wizard()
            ->set('form.household_members_in_organization', 'None')
            ->assertSet('form.member_of_organization', 'No')
            ->set('form.household_members_in_organization', '2 members')
            ->assertSee('Member of Organization')
            ->assertSet('form.member_of_organization', '');
    }

    /* ============ Exclusive options ============ */

    public function test_flexible_replaces_selected_days_and_vice_versa(): void
    {
        $this->wizard()
            ->call('toggleOption', 'preferred_training_days', 'Monday')
            ->call('toggleOption', 'preferred_training_days', 'Wednesday')
            ->assertSet('form.preferred_training_days', ['Monday', 'Wednesday'])
            ->call('toggleOption', 'preferred_training_days', 'Flexible')
            ->assertSet('form.preferred_training_days', ['Flexible'])
            ->call('toggleOption', 'preferred_training_days', 'Saturday')
            ->assertSet('form.preferred_training_days', ['Saturday']);
    }

    public function test_no_supplies_replaces_selected_supplies_and_vice_versa(): void
    {
        $this->wizard()
            ->call('toggleOption', 'barangay_medical_supplies_available', 'First aid kit')
            ->call('toggleOption', 'barangay_medical_supplies_available', 'Paracetamol')
            ->assertSet('form.barangay_medical_supplies_available', ['First aid kit', 'Paracetamol'])
            ->call('toggleOption', 'barangay_medical_supplies_available', 'No supplies')
            ->assertSet('form.barangay_medical_supplies_available', ['No supplies'])
            ->call('toggleOption', 'barangay_medical_supplies_available', 'Vitamins')
            ->assertSet('form.barangay_medical_supplies_available', ['Vitamins']);
    }

    /* ============ Problems capped at 3 ============ */

    public function test_problem_fields_ignore_selections_beyond_three(): void
    {
        $this->wizard()
            ->call('toggleOption', 'family_problems', 'Low income')
            ->call('toggleOption', 'family_problems', 'Insufficient food')
            ->call('toggleOption', 'family_problems', 'Poor housing')
            ->assertSet('form.family_problems', ['Low income', 'Insufficient food', 'Poor housing'])
            // Fourth click is ignored at the cap.
            ->call('toggleOption', 'family_problems', 'Family conflict')
            ->assertSet('form.family_problems', ['Low income', 'Insufficient food', 'Poor housing'])
            // Deselecting one frees a slot again.
            ->call('toggleOption', 'family_problems', 'Insufficient food')
            ->call('toggleOption', 'family_problems', 'Family conflict')
            ->assertSet('form.family_problems', ['Low income', 'Poor housing', 'Family conflict']);
    }

    /* ============ Removed field + letters-only names ============ */

    public function test_interested_in_livelihood_training_is_gone(): void
    {
        $this->wizard()
            ->assertDontSee('Interested in Livelihood Training')
            ->assertDontSeeHtml('interested_in_livelihood_training');
    }

    public function test_name_inputs_have_letters_only_sanitizer(): void
    {
        $this->wizard()
            ->assertSeeHtml("x-on:input=\"\$el.value = \$el.value.replace(/[^a-zA-ZÑñ\\s\\.\\-']/g, '')\"")
            ->assertSee('Divorced')
            ->assertDontSee('Live-in');
    }
}
