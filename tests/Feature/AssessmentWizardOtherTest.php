<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Wizard;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentWizardOtherTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_and_profile_fields_use_custom_dropdowns(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        Livewire::actingAs($faculty)
            ->test(Wizard::class)
            // No native selects remain — the sc-select component replaces them.
            ->assertDontSeeHtml('<select')
            // Year offers the past and next five years (current year preselected).
            ->assertSee((string) (now()->year - 5))
            ->assertSee((string) now()->year)
            ->assertSee((string) (now()->year + 5))
            ->assertDontSee((string) (now()->year - 6));
    }

    public function test_other_specify_input_renders_when_other_selected(): void
    {
        // v4.9: multi-select fields that keep "Other" — Animals Kept
        // (conditional on Keeps Animals = Yes).
        $component = Livewire::actingAs(User::factory()->create(['role' => 'faculty']))
            ->test(Wizard::class);

        $component->assertDontSee('form.animals_kept_other', false);

        $component->set('form.keeps_animals', 'Yes')
            ->call('toggleOption', 'animals_kept', 'Other');
        $component->assertSee('form.animals_kept_other', false);

        $component->call('toggleOption', 'animals_kept', 'Other');
        $component->assertDontSee('form.animals_kept_other', false);
    }

    public function test_other_specify_input_renders_for_single_fields(): void
    {
        // v4.9: single-select fields that keep "Other" — Water Source.
        $component = Livewire::actingAs(User::factory()->create(['role' => 'faculty']))
            ->test(Wizard::class);

        $component->assertDontSee('form.water_source_other', false);

        $component->set('form.water_source', 'Other');
        $component->assertSee('form.water_source_other', false);
    }

    public function test_save_merges_other_text_into_other_text_json(): void
    {
        $community = Community::factory()->create();
        $faculty = User::factory()->create(['role' => 'faculty']);

        Livewire::actingAs($faculty)
            ->test(Wizard::class)
            ->set('communityId', $community->id)
            ->set('form.respondent_first_name', 'Juan')
            ->set('form.respondent_last_name', 'Dela Cruz')
            ->set('form.keeps_animals', 'Yes')
            ->call('toggleOption', 'animals_kept', 'Other')
            ->set('form.animals_kept_other', 'Fighting rooster')
            ->set('form.animals_kept', ['Chicken', 'Other'])
            ->set('form.water_source', 'Other')
            ->set('form.water_source_other', 'Open dug well')
            ->call('save')
            ->assertHasNoErrors();

        $record = NeedsAssessment::where('respondent_first_name', 'Juan')->first();

        $this->assertNotNull($record);
        $this->assertSame(['Chicken', 'Other'], $record->animals_kept);
        $this->assertSame('Other', $record->water_source);
        $this->assertSame(
            [
                'animals_kept' => ['Fighting rooster'],
                'water_source' => ['Open dug well'],
            ],
            $record->other_text
        );
    }

    public function test_other_text_is_dropped_when_other_not_selected(): void
    {
        $community = Community::factory()->create();
        $faculty = User::factory()->create(['role' => 'faculty']);

        Livewire::actingAs($faculty)
            ->test(Wizard::class)
            ->set('communityId', $community->id)
            ->set('form.respondent_first_name', 'Maria')
            ->set('form.respondent_last_name', 'Santos')
            ->set('form.animals_kept_other', 'Leftover text')
            ->set('form.animals_kept', ['Chicken'])
            ->call('save')
            ->assertHasNoErrors();

        $record = NeedsAssessment::where('respondent_first_name', 'Maria')->first();

        $this->assertNotNull($record);
        $this->assertNull($record->other_text);
    }
}
