<?php

namespace Tests\Feature;

use App\Livewire\Faculty\Directory;
use App\Livewire\Users\Index;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserFacultyStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The account gate (users.is_active, toggled in User Management) and the
     * faculty directory's own status column are two views of the same person,
     * so they must not drift: deactivating a faculty LOGIN reads "Inactive" in
     * the directory, and marking a faculty member "Inactive" in the directory
     * locks their login. "On Leave" is a departmental state — never an account
     * lock — so it does not touch the account in either direction.
     */
    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_deactivating_a_faculty_user_marks_the_directory_entry_inactive(): void
    {
        $faculty = Faculty::factory()->create();
        $user = $faculty->user;

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareStatusChange', $user->id)
            ->call('changeStatus');

        $this->assertFalse($user->refresh()->is_active);
        $this->assertSame(Faculty::STATUS_INACTIVE, $faculty->refresh()->status);
    }

    public function test_reactivating_a_deactivated_faculty_user_restores_the_directory_entry(): void
    {
        $faculty = Faculty::factory()->inactive()->create();
        $faculty->user->update(['is_active' => false]);
        $user = $faculty->user;

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareStatusChange', $user->id)
            ->call('changeStatus');

        $this->assertTrue($user->refresh()->is_active);
        $this->assertSame(Faculty::STATUS_ACTIVE, $faculty->refresh()->status);
    }

    public function test_reactivating_does_not_clobber_an_on_leave_directory_entry(): void
    {
        $faculty = Faculty::factory()->onLeave()->create();
        $faculty->user->update(['is_active' => false]);
        $user = $faculty->user;

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareStatusChange', $user->id)
            ->call('changeStatus');

        $this->assertTrue($user->refresh()->is_active);
        $this->assertSame(Faculty::STATUS_ON_LEAVE, $faculty->refresh()->status);
    }

    public function test_deactivating_a_non_faculty_user_never_touches_a_faculty_row(): void
    {
        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareStatusChange', $secretary->id)
            ->call('changeStatus');

        $this->assertFalse($secretary->refresh()->is_active);
        $this->assertSame(0, Faculty::count());
    }

    public function test_marking_a_faculty_member_inactive_in_the_directory_locks_the_login(): void
    {
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('edit', $faculty->id)
            ->set('form.status', Faculty::STATUS_INACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Faculty::STATUS_INACTIVE, $faculty->refresh()->status);
        $this->assertFalse($faculty->user->refresh()->is_active);
    }

    public function test_marking_a_faculty_member_on_leave_never_locks_the_login(): void
    {
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('edit', $faculty->id)
            ->set('form.status', Faculty::STATUS_ON_LEAVE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Faculty::STATUS_ON_LEAVE, $faculty->refresh()->status);
        $this->assertTrue($faculty->user->refresh()->is_active);
    }

    public function test_returning_an_inactive_faculty_member_to_active_reopens_the_login(): void
    {
        $faculty = Faculty::factory()->inactive()->create();
        $faculty->user->update(['is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('edit', $faculty->id)
            ->set('form.status', Faculty::STATUS_ACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Faculty::STATUS_ACTIVE, $faculty->refresh()->status);
        $this->assertTrue($faculty->user->refresh()->is_active);
    }

    public function test_a_faculty_profile_created_as_inactive_starts_with_a_locked_login(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('create')
            ->set('form.name', 'New Inactive Faculty')
            ->set('form.email', 'new.inactive@lnu.edu.ph')
            ->set('form.status', Faculty::STATUS_INACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'new.inactive@lnu.edu.ph')->firstOrFail();
        $this->assertFalse($user->is_active);
        $this->assertSame(Faculty::STATUS_INACTIVE, $user->faculty->status);
        $this->assertTrue($component->get('showCredentials'), 'the temporary-password modal still opens');
    }
}
