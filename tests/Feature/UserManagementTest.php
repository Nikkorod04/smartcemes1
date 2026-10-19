<?php

namespace Tests\Feature;

use App\Livewire\Users\Index;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_manage_users_page(): void
    {
        $this->actingAs($this->admin())->get(route('users.index'))
            ->assertOk()
            ->assertSee('Manage Users')
            ->assertSee('Account directory');
    }

    public function test_non_admins_cannot_open_the_manage_users_page(): void
    {
        $this->actingAs(User::factory()->faculty()->create())->get(route('users.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SECRETARY]))->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_secretary_account_with_temporary_credentials(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('create')
            ->set('form.name', 'New Secretary')
            ->set('form.email', 'new.secretary@lnu.edu.ph')
            ->set('form.role', User::ROLE_SECRETARY)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showCredentials', true);

        $user = User::where('email', 'new.secretary@lnu.edu.ph')->firstOrFail();

        $this->assertSame(User::ROLE_SECRETARY, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($component->get('generatedPassword'), $user->password));
    }

    public function test_admin_can_create_a_faculty_account_with_an_auto_created_profile(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('create')
            ->set('form.name', 'New Faculty')
            ->set('form.email', 'new.faculty@lnu.edu.ph')
            ->set('form.role', User::ROLE_FACULTY)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'new.faculty@lnu.edu.ph')->firstOrFail();

        $this->assertNotNull($user->faculty);
        $this->assertMatchesRegularExpression('/^LNU-\d{4}-\d{4}$/', $user->faculty->employee_id);
        $this->assertSame(Faculty::STATUS_ACTIVE, $user->faculty->status);
    }

    public function test_user_creation_requires_a_unique_email(): void
    {
        $existing = User::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('create')
            ->set('form.name', 'Duplicate')
            ->set('form.email', $existing->email)
            ->call('save')
            ->assertHasErrors(['form.email' => 'unique']);
    }

    public function test_admin_can_reset_a_user_password(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SECRETARY]);

        $component = Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareReset', $user->id)
            ->assertSet('showResetConfirm', true)
            ->call('resetUserPassword')
            ->assertSet('showCredentials', true);

        $this->assertTrue($user->refresh()->must_change_password);
        $this->assertTrue(Hash::check($component->get('generatedPassword'), $user->password));
    }

    public function test_admin_can_deactivate_and_reactivate_another_account(): void
    {
        $managed = User::factory()->create(['role' => User::ROLE_SECRETARY]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareStatusChange', $managed->id)
            ->call('changeStatus')
            ->assertSet('showStatusConfirm', false);

        $this->assertFalse($managed->refresh()->is_active);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->call('prepareStatusChange', $managed->id)
            ->call('changeStatus');

        $this->assertTrue($managed->refresh()->is_active);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('prepareStatusChange', $admin->id)
            ->assertForbidden();

        $this->assertTrue($admin->refresh()->is_active);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }
}
