<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Review;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function secretary(): User
    {
        return User::factory()->create(['role' => 'secretary']);
    }

    protected function makeAssessment(array $overrides = []): NeedsAssessment
    {
        return NeedsAssessment::create(array_merge([
            'community_id' => Community::factory()->create()->id,
            'quarter' => 2,
            'year' => 2026,
            'uploaded_by' => User::factory()->create(['role' => 'faculty'])->id,
            'review_status' => 'pending',
            'respondent_first_name' => 'Lucia',
            'respondent_last_name' => 'Amistoso',
        ], $overrides));
    }

    public function test_page_renders_kpi_cards_and_queue(): void
    {
        $this->makeAssessment();

        Livewire::actingAs($this->secretary())
            ->test(Review::class)
            ->assertSee('Pending Review')
            ->assertSee('Validated')
            ->assertSee('Returned')
            ->assertSee('Total Encoded')
            ->assertSee('Amistoso');
    }

    public function test_drawer_renders_single_select_fields_without_error(): void
    {
        // v4.9 regression: education / livelihood / water / house were once
        // JSON arrays but are now single-select STRINGS — the old detail row
        // called implode() on them and the render 500'd with a TypeError.
        $a = $this->makeAssessment([
            'respondent_educational_attainment' => 'College Graduate',
            'livelihood_options' => 'Farming',
            'water_source' => 'Deep well',
            'house_type' => 'Concrete / solid house',
            'has_electricity' => 'Yes',
            'family_problems' => ['Low income', 'Debt'],
            'other_text' => ['water_source' => ['Deepwell nearby']],
        ]);

        Livewire::actingAs($this->secretary())
            ->test(Review::class)
            ->call('openDrawer', $a->id)
            ->assertSet('showDrawer', true)
            ->assertSee('Encoded Response Dossier')
            ->assertSee('College Graduate')
            ->assertSee('Farming')
            ->assertSee('Deep well')
            ->assertSee('Concrete / solid house')
            ->assertSee('Low income')
            ->assertSee('Deepwell nearby')
            ->assertSee('Record completeness')
            ->assertSee('Validate Assessment')
            ->assertSee('Return with Remarks');
    }

    public function test_search_and_quarter_filters(): void
    {
        $alpha = Community::factory()->create(['name' => 'Brgy. Alpha']);
        $beta = Community::factory()->create(['name' => 'Brgy. Beta']);

        $one = $this->makeAssessment(['community_id' => $alpha->id, 'quarter' => 1]);
        $two = $this->makeAssessment(['community_id' => $beta->id, 'quarter' => 2]);

        $test = Livewire::actingAs($this->secretary())->test(Review::class);

        $test->set('search', 'Alpha')
            ->assertSee('Brgy. Alpha')
            ->assertDontSee('Brgy. Beta');

        $test->set('search', '')
            ->set('quarter', '2')
            ->assertSee('Brgy. Beta')
            ->assertDontSee('Brgy. Alpha');

        $test->set('quarter', 'all')
            ->assertSee('Brgy. Alpha')
            ->assertSee('Brgy. Beta');
    }

    public function test_validate_opens_confirmation_modal_and_validates(): void
    {
        $a = $this->makeAssessment();

        $test = Livewire::actingAs($this->secretary())->test(Review::class);

        $test->call('openDrawer', $a->id)
            ->call('openValidate', $a->id)
            ->assertSet('showValidate', true)
            ->assertSee('Validate Submission')
            ->assertSee('On confirm:')
            ->assertSee($a->community->name);

        $test->call('confirmValidate')
            ->assertSet('showValidate', false)
            ->assertSet('showDrawer', false);

        $a->refresh();
        $this->assertSame('validated', $a->review_status);
        $this->assertNotNull($a->reviewed_at);
    }

    public function test_validating_already_reviewed_submission_is_refused(): void
    {
        $a = $this->makeAssessment(['review_status' => 'validated']);

        Livewire::actingAs($this->secretary())
            ->test(Review::class)
            ->call('openValidate', $a->id)
            ->call('confirmValidate')
            ->assertStatus(422);
    }

    public function test_return_from_drawer_requires_remarks_and_closes_drawer(): void
    {
        $a = $this->makeAssessment();

        Livewire::actingAs($this->secretary())
            ->test(Review::class)
            ->call('openDrawer', $a->id)
            ->call('openReturn', $a->id)
            ->call('confirmReturn')
            ->assertHasErrors(['returnRemarks']);

        $a->refresh();
        $this->assertSame('pending', $a->review_status);
    }

    public function test_non_secretary_roles_cannot_access_review(): void
    {
        $this->makeAssessment();

        foreach (['admin', 'faculty'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('assessments.review'))
                ->assertForbidden();
        }
    }

    public function test_queue_is_paginated_at_ten_per_page(): void
    {
        $names = ['Ada', 'Bri', 'Cor', 'Dan', 'Eve', 'Fay', 'Gil', 'Han', 'Ida', 'Jon', 'Kim', 'Lia'];

        // Distinct created_at stamps so the newest-first default order is
        // deterministic (sqlite timestamps share one second otherwise).
        foreach ($names as $i => $name) {
            $assessment = $this->makeAssessment([
                'community_id' => Community::factory()->create(['name' => "Brgy. {$name}"])->id,
            ]);
            $assessment->forceFill(['created_at' => now()->addSeconds($i)])->saveQuietly();
        }

        $test = Livewire::actingAs($this->secretary())->test(Review::class);

        // Page 1: latest ten (default order = pending first, newest first).
        $test->assertSee('Showing 1–10 of 12')
            ->assertSee('Brgy. Lia')
            ->assertDontSee('Brgy. Ada')
            ->assertDontSee('Brgy. Bri');

        // Page 2: the two oldest.
        $test->call('setPage', 2)
            ->assertSee('Showing 11–12 of 12')
            ->assertSee('Brgy. Ada')
            ->assertSee('Brgy. Bri')
            ->assertDontSee('Brgy. Lia');
    }

    public function test_changing_filters_resets_to_first_page(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeAssessment([
                'community_id' => Community::factory()->create(['name' => "Brgy. Filt{$i}"])->id,
            ]);
        }

        Livewire::actingAs($this->secretary())
            ->test(Review::class)
            ->call('setPage', 2)
            ->set('search', 'Filt')
            ->assertSet('paginators.page', 1);
    }

    public function test_period_sort_orders_by_year_then_quarter(): void
    {
        $this->makeAssessment([
            'community_id' => Community::factory()->create(['name' => 'Brgy. Sort2025'])->id,
            'quarter' => 4, 'year' => 2025,
        ]);
        $this->makeAssessment([
            'community_id' => Community::factory()->create(['name' => 'Brgy. Sort2027'])->id,
            'quarter' => 1, 'year' => 2027,
        ]);
        $this->makeAssessment([
            'community_id' => Community::factory()->create(['name' => 'Brgy. Sort2026'])->id,
            'quarter' => 2, 'year' => 2026,
        ]);
        // Same year — quarter breaks the tie.
        $this->makeAssessment([
            'community_id' => Community::factory()->create(['name' => 'Brgy. Sort2026q1'])->id,
            'quarter' => 1, 'year' => 2026,
        ]);

        $test = Livewire::actingAs($this->secretary())->test(Review::class);

        $test->call('toggleSortPeriod')
            ->assertSet('sortPeriod', 'asc')
            ->assertSeeInOrder(['Brgy. Sort2025', 'Brgy. Sort2026q1', 'Brgy. Sort2026', 'Brgy. Sort2027']);

        $test->call('toggleSortPeriod')
            ->assertSet('sortPeriod', 'desc')
            ->assertSeeInOrder(['Brgy. Sort2027', 'Brgy. Sort2026', 'Brgy. Sort2026q1', 'Brgy. Sort2025']);

        $test->call('toggleSortPeriod')
            ->assertSet('sortPeriod', 'default')
            ->assertSee('Brgy. Sort2025');
    }
}
