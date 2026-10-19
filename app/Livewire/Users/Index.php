<?php

namespace App\Livewire\Users;

use App\Models\Faculty;
use App\Models\User;
use App\Services\EmployeeIdService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'role', except: '')]
    public string $roleFilter = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public bool $showForm = false;

    public bool $showStatusConfirm = false;

    public bool $showResetConfirm = false;

    public bool $showCredentials = false;

    public ?int $editingId = null;

    public ?int $statusTargetId = null;

    public ?int $resettingId = null;

    public string $statusTargetName = '';

    public string $resettingName = '';

    public string $statusTargetAction = 'deactivate';

    public ?string $generatedPassword = null;

    public string $generatedAccountName = '';

    public string $generatedAccountEmail = '';

    public string $credentialAction = 'created';

    /** @var array<string, mixed> */
    public array $form = [
        'name' => '',
        'email' => '',
        'role' => User::ROLE_SECRETARY,
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', User::class);

        $this->editingId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'role' => User::ROLE_SECRETARY,
        ];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        Gate::authorize('update', $user);

        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $editing = $this->editingId ? User::findOrFail($this->editingId) : null;

        Gate::authorize($editing ? 'update' : 'create', $editing ?? User::class);

        $this->validate($this->rules($editing));

        if ($editing) {
            $editing->update([
                'name' => $this->form['name'],
                'email' => $this->form['email'],
            ]);

            activity()->performedOn($editing)->event('user_updated')->log('Updated account details for '.$editing->email);
            $this->closeForm();
            $this->dispatch('sc-toast', message: 'User account updated', type: 'success');

            return;
        }

        $temporaryPassword = Str::password(16);
        $user = null;

        DB::transaction(function () use ($temporaryPassword, &$user) {
            $user = User::create([
                'name' => $this->form['name'],
                'email' => $this->form['email'],
                'password' => Hash::make($temporaryPassword),
                'role' => $this->form['role'],
                'must_change_password' => true,
                'is_active' => true,
            ]);

            if ($user->isFaculty()) {
                Faculty::create([
                    'user_id' => $user->id,
                    'employee_id' => app(EmployeeIdService::class)->next(),
                    'status' => Faculty::STATUS_ACTIVE,
                ]);
            }
        });

        activity()->performedOn($user)->event('user_created')->log('Created '.$user->role.' account for '.$user->email);

        $this->generatedPassword = $temporaryPassword;
        $this->generatedAccountName = $user->name;
        $this->generatedAccountEmail = $user->email;
        $this->credentialAction = 'created';
        $this->closeForm();
        $this->showCredentials = true;
        $this->dispatch('sc-toast', message: 'User account created — temporary credentials are ready to share', type: 'success');
    }

    public function prepareStatusChange(int $id): void
    {
        $user = User::findOrFail($id);

        Gate::authorize('changeStatus', $user);

        $this->statusTargetId = $user->id;
        $this->statusTargetName = $user->name;
        $this->statusTargetAction = $user->is_active ? 'deactivate' : 'activate';
        $this->showStatusConfirm = true;
    }

    public function cancelStatusChange(): void
    {
        $this->showStatusConfirm = false;
        $this->statusTargetId = null;
        $this->statusTargetName = '';
        $this->statusTargetAction = 'deactivate';
    }

    public function changeStatus(): void
    {
        $user = User::findOrFail($this->statusTargetId);

        Gate::authorize('changeStatus', $user);

        if ($user->is_active && $user->isAdmin() && User::query()->where('role', User::ROLE_ADMIN)->where('is_active', true)->count() <= 1) {
            $this->cancelStatusChange();
            $this->dispatch('sc-toast', message: 'The last active administrator cannot be deactivated', type: 'error');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $event = $user->is_active ? 'user_activated' : 'user_deactivated';
        activity()->performedOn($user)->event($event)->log(ucfirst($event).' for '.$user->email);

        $message = $user->is_active ? 'User account activated' : 'User account deactivated';
        $this->cancelStatusChange();
        $this->dispatch('sc-toast', message: $message, type: $user->is_active ? 'success' : 'warning');
    }

    public function prepareReset(int $id): void
    {
        $user = User::findOrFail($id);

        Gate::authorize('resetPassword', $user);

        $this->resettingId = $user->id;
        $this->resettingName = $user->name;
        $this->showResetConfirm = true;
    }

    public function cancelReset(): void
    {
        $this->showResetConfirm = false;
        $this->resettingId = null;
        $this->resettingName = '';
    }

    public function resetUserPassword(): void
    {
        $user = User::findOrFail($this->resettingId);

        Gate::authorize('resetPassword', $user);

        $temporaryPassword = Str::password(16);

        DB::transaction(function () use ($user, $temporaryPassword) {
            $user->update([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
            ]);

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        });

        activity()->performedOn($user)->event('user_password_reset')->log('Reset password for '.$user->email);

        $this->generatedPassword = $temporaryPassword;
        $this->generatedAccountName = $user->name;
        $this->generatedAccountEmail = $user->email;
        $this->credentialAction = 'reset';
        $this->cancelReset();
        $this->showCredentials = true;
        $this->dispatch('sc-toast', message: 'Temporary password generated — share it securely', type: 'success');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'role' => User::ROLE_SECRETARY,
        ];
        $this->resetValidation();
    }

    public function closeCredentials(): void
    {
        $this->showCredentials = false;
        $this->generatedPassword = null;
        $this->generatedAccountName = '';
        $this->generatedAccountEmail = '';
        $this->credentialAction = 'created';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(?User $editing): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($editing?->id),
            ],
            'form.role' => ['required', Rule::in($editing ? User::ROLES : [User::ROLE_SECRETARY, User::ROLE_FACULTY])],
        ];
    }

    public function render()
    {
        $query = User::query()->with('faculty')->when($this->search, function ($query) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        })->when($this->roleFilter, fn ($query) => $query->where('role', $this->roleFilter))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false));

        $query->orderBy('name')->orderBy('id');

        /** @var LengthAwarePaginator $users */
        $users = $query->paginate(10);

        return view('livewire.users.index', [
            'users' => $users,
            'summary' => [
                'total' => User::query()->count(),
                'faculty' => User::query()->where('role', User::ROLE_FACULTY)->count(),
                'secretaries' => User::query()->where('role', User::ROLE_SECRETARY)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
        ]);
    }
}
