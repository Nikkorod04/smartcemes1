<?php

namespace Tests\Feature;

use App\Livewire\Assessments\Review;
use App\Livewire\Communities\Index as CommunitiesIndex;
use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Item 2: the per-community assessment directory (after-defense revision).
 *
 * The same Review queue, locked to one barangay. Admin may read it; only the
 * Secretary may validate or return, and a tampered id must not reach another
 * community's rows.
 */
class CommunityAssessmentDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function secretary(): User
    {
        return User::factory()->create(['role' => User::ROLE_SECRETARY]);
    }

    protected function assessment(Community $community, array $overrides = []): NeedsAssessment
    {
        return NeedsAssessment::create(array_merge([
            'community_id' => $community->id,
            'quarter' => 4,
            'year' => 2026,
            'review_status' => 'pending',
            'respondent_first_name' => 'Maria',
            'respondent_last_name' => 'Santos',
            'respondent_sex' => 'Female',
        ], $overrides));
    }

    public function test_secretary_and_admin_can_open_a_community_directory(): void
    {
        $community = Community::factory()->create(['name' => 'Brgy. Directory']);

        $this->actingAs($this->secretary())
            ->get(route('communities.assessments', $community))
            ->assertOk()
            ->assertSee('Brgy. Directory')
            ->assertSee('Back to the review queue');

        $this->actingAs($this->admin())
            ->get(route('communities.assessments', $community))
            ->assertOk()
            ->assertSee('Brgy. Directory')
            ->assertSee('Back to communities');
    }

    public function test_faculty_cannot_open_a_community_directory(): void
    {
        $community = Community::factory()->create();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_FACULTY]))
            ->get(route('communities.assessments', $community))
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_login_for_a_community_directory(): void
    {
        $community = Community::factory()->create();

        $this->get(route('communities.assessments', $community))
            ->assertRedirect(route('login'));
    }

    public function test_an_unknown_community_is_not_found(): void
    {
        $this->actingAs($this->secretary())
            ->get('/communities/999999/assessments')
            ->assertNotFound();
    }

    public function test_the_directory_lists_only_that_communitys_rows(): void
    {
        $abucay = Community::factory()->create(['name' => 'Brgy. Abucay']);
        $sanJose = Community::factory()->create(['name' => 'Brgy. San Jose']);

        $this->assessment($abucay, ['respondent_first_name' => 'Lucia', 'respondent_last_name' => 'Amistoso']);
        $this->assessment($sanJose, ['respondent_first_name' => 'Pedro', 'respondent_last_name' => 'Penduko']);

        $this->actingAs($this->secretary())
            ->get(route('communities.assessments', $abucay))
            ->assertOk()
            ->assertSee('Amistoso')
            ->assertDontSee('Penduko');
    }

    public function test_the_kpis_count_only_that_community(): void
    {
        $abucay = Community::factory()->create();
        $sanJose = Community::factory()->create();

        $this->assessment($abucay);
        $this->assessment($sanJose);
        $this->assessment($sanJose);

        // One pending row in Abucay, two in San Jose: the Abucay page must say 1.
        $this->actingAs($this->secretary())
            ->get(route('communities.assessments', $abucay))
            ->assertOk()
            ->assertSeeHtml('secretary-review-kpi-value mt-4">1</p>')
            ->assertSee('1 submission');
    }

    public function test_admin_reads_the_dossier_but_gets_no_validation_controls(): void
    {
        $community = Community::factory()->create();
        $assessment = $this->assessment($community);

        Livewire::actingAs($this->admin())
            ->test(Review::class, ['community' => $community])
            ->assertSet('readOnly', true)
            ->assertDontSee('openValidate')
            ->call('openDrawer', $assessment->id)
            ->assertSee('Encoded response dossier')
            ->assertSee('Read-only view')
            ->assertDontSee('Validate Assessment')
            ->assertDontSee('Return with Remarks');
    }

    public function test_admin_cannot_validate_or_return_even_if_the_action_is_invoked(): void
    {
        $community = Community::factory()->create();
        $assessment = $this->assessment($community);

        Livewire::actingAs($this->admin())
            ->test(Review::class, ['community' => $community])
            ->call('openValidate', $assessment->id)
            ->assertForbidden();

        Livewire::actingAs($this->admin())
            ->test(Review::class, ['community' => $community])
            ->call('confirmValidate')
            ->assertForbidden();

        $this->assertSame('pending', $assessment->fresh()->review_status);
    }

    public function test_the_secretary_can_validate_from_the_directory(): void
    {
        $community = Community::factory()->create();
        $assessment = $this->assessment($community);

        Livewire::actingAs($this->secretary())
            ->test(Review::class, ['community' => $community])
            ->assertSet('readOnly', false)
            ->call('openValidate', $assessment->id)
            ->call('confirmValidate')
            ->assertHasNoErrors();

        $this->assertSame('validated', $assessment->fresh()->review_status);
    }

    public function test_a_tampered_id_cannot_reach_another_community(): void
    {
        $abucay = Community::factory()->create();
        $sanJose = Community::factory()->create();
        $other = $this->assessment($sanJose);

        Livewire::actingAs($this->secretary())
            ->test(Review::class, ['community' => $abucay])
            ->call('openDrawer', $other->id)
            ->assertNotFound();

        Livewire::actingAs($this->secretary())
            ->test(Review::class, ['community' => $abucay])
            ->call('openValidate', $other->id)
            ->assertNotFound();

        $this->assertSame('pending', $other->fresh()->review_status);
    }

    public function test_year_and_status_filters_narrow_the_directory(): void
    {
        $community = Community::factory()->create();

        $this->assessment($community, [
            'year' => 2025,
            'review_status' => 'validated',
            'respondent_first_name' => 'Older',
            'respondent_last_name' => 'Villanueva',
        ]);
        $this->assessment($community, [
            'year' => 2026,
            'review_status' => 'pending',
            'respondent_first_name' => 'Newer',
            'respondent_last_name' => 'Zamora',
        ]);

        Livewire::actingAs($this->secretary())
            ->test(Review::class, ['community' => $community])
            ->assertSee('Villanueva')
            ->assertSee('Zamora')
            ->set('year', '2026')
            ->assertSee('Zamora')
            ->assertDontSee('Villanueva')
            ->set('year', 'all')
            ->set('status', 'validated')
            ->assertSee('Villanueva')
            ->assertDontSee('Zamora');
    }

    public function test_the_community_drawer_links_to_the_directory(): void
    {
        $community = Community::factory()->create();
        $this->assessment($community);
        $this->assessment($community);

        Livewire::actingAs($this->admin())
            ->test(CommunitiesIndex::class)
            ->call('viewDetail', $community->id)
            ->assertSee('View all 2')
            ->assertSee(route('communities.assessments', $community), false);
    }

    public function test_the_review_drawer_links_to_the_directory_for_the_secretary(): void
    {
        $community = Community::factory()->create();
        $assessment = $this->assessment($community);

        // The queue is the secretary's home; the Admin-only communities page
        // cannot be their only way into the directory.
        Livewire::actingAs($this->secretary())
            ->test(Review::class)
            ->call('openDrawer', $assessment->id)
            ->assertSee('See every assessment for')
            ->assertSee(route('communities.assessments', $community), false);
    }
}
