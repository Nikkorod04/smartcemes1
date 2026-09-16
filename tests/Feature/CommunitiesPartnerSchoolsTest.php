<?php

namespace Tests\Feature;

use App\Livewire\Communities\Index as CommunitiesIndex;
use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunitiesPartnerSchoolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_communities_and_partner_schools(): void
    {
        $this->seed();

        $this->assertSame(46, Community::where('type', 'community')->count());
        $this->assertSame(12, Community::where('type', 'school')->count());
        $this->assertSame(58, Community::count());
    }

    public function test_seeder_assigns_valid_school_levels(): void
    {
        $this->seed();

        Community::where('type', 'school')->each(function ($school) {
            $this->assertContains($school->school_level, config('smartcemes.school_levels'), $school->name.' has an invalid school_level');
            $this->assertSame('active', $school->status);
        });
    }

    public function test_el_reposo_and_salvacion_are_tacloban(): void
    {
        $this->seed();

        $this->assertSame('Tacloban City', Community::where('name', 'Brgy. El Reposo')->value('municipality'));
        $this->assertSame('Tacloban City', Community::where('name', 'Brgy. Salvacion')->value('municipality'));
    }

    public function test_type_filter_returns_only_schools(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@lnu.com')->first();

        Livewire::actingAs($admin)
            ->test(CommunitiesIndex::class)
            ->set('type', 'school')
            ->assertSee('Caibaan Elementary School')
            ->assertSee('Eastern Visayas State University')
            ->assertDontSee('Brgy. Suhi');
    }

    public function test_type_filter_returns_only_communities(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@lnu.com')->first();

        Livewire::actingAs($admin)
            ->test(CommunitiesIndex::class)
            ->set('type', 'community')
            ->assertSee('Brgy. Suhi')
            ->assertDontSee('Caibaan Elementary School');
    }

    public function test_school_detail_modal_renders_level_and_principal(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@lnu.com')->first();
        $school = Community::where('name', 'Leyte National High School')->first();

        Livewire::actingAs($admin)
            ->test(CommunitiesIndex::class)
            ->call('viewDetail', $school->id)
            ->assertSee('School Level')
            ->assertSee('Secondary')
            ->assertSee($school->contact_person)
            ->assertDontSee('Needs-Assessment History');
    }

    public function test_admin_can_create_a_partner_school(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@lnu.com')->first();

        Livewire::actingAs($admin)
            ->test(CommunitiesIndex::class)
            ->set('form.name', 'Test Elementary School')
            ->set('form.municipality', 'Tacloban City')
            ->set('form.type', 'school')
            ->set('form.school_level', 'elementary')
            ->call('save');

        $school = Community::where('name', 'Test Elementary School')->first();
        $this->assertNotNull($school);
        $this->assertTrue($school->isSchool());
        $this->assertSame('elementary', $school->school_level);
        $this->assertSame('active', $school->status); // schools are always active
    }

    public function test_school_requires_school_level(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@lnu.com')->first();

        Livewire::actingAs($admin)
            ->test(CommunitiesIndex::class)
            ->set('form.name', 'Test Elementary School')
            ->set('form.municipality', 'Tacloban City')
            ->set('form.type', 'school')
            ->set('form.school_level', '')
            ->call('save')
            ->assertHasErrors(['form.school_level']);
    }

    public function test_community_creation_clears_school_level(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@lnu.com')->first();

        Livewire::actingAs($admin)
            ->test(CommunitiesIndex::class)
            ->set('form.name', 'Brgy. Test')
            ->set('form.municipality', 'Tacloban City')
            ->set('form.type', 'community')
            ->set('form.school_level', 'elementary')
            ->call('save');

        $community = Community::where('name', 'Brgy. Test')->first();
        $this->assertNotNull($community);
        $this->assertFalse($community->isSchool());
        $this->assertNull($community->school_level);
    }
}
