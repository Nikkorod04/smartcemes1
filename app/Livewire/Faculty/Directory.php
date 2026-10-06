<?php

namespace App\Livewire\Faculty;

use App\Models\College;
use App\Models\Faculty as FacultyModel;
use App\Models\User;
use App\Services\EmployeeIdService;
use App\Services\FacultyContributionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Faculty Directory — the roster-management page (revision §5 R3 step 3).
 *
 * Mirrors docs/prototype/pages/faculty-directory.html:
 *   search + college / expertise / status filters + sort, the roster table,
 *   and the New / Edit Faculty Profile modal (with the college select and the
 *   expertise multi-select driven by config('smartcemes.expertise_options')).
 *
 * Admin-only. The prototype's `?role=faculty` "My Faculty Profile" variant is
 * served by the separate Profile component instead of a mode flag, so the
 * read-only and self-edit paths cannot leak admin controls.
 */
#[Layout('layouts.app')]
class Directory extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'college', except: '')]
    public string $collegeFilter = '';

    #[Url(as: 'expertise', except: '')]
    public string $expertiseFilter = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'sort', except: 'name')]
    public string $sort = 'name';

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [
        'name' => '',
        'email' => '',
        'employee_id' => '',
        'college_id' => '',
        'department' => '',
        'specialization' => '',
        'position' => '',
        'status' => FacultyModel::STATUS_ACTIVE,
        'phone' => '',
        'address' => '',
        'expertise' => [],
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', FacultyModel::class);
    }

    /**
     * Reset pagination whenever a filter changes — otherwise page 3 of a
     * narrowed list shows an empty table.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCollegeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedExpertiseFilter(): void
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
        $this->collegeFilter = '';
        $this->expertiseFilter = '';
        $this->statusFilter = '';
        $this->sort = 'name';
        $this->resetPage();
    }

    /* ---------------------------------------------------------------------
     | Create / edit
     | ------------------------------------------------------------------ */

    public function create(): void
    {
        Gate::authorize('create', FacultyModel::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $faculty = FacultyModel::with(['user', 'expertise'])->findOrFail($id);

        Gate::authorize('update', $faculty);

        $this->editingId = $faculty->id;
        $this->form = [
            'name' => $faculty->user?->name ?? '',
            'email' => $faculty->user?->email ?? '',
            'employee_id' => $faculty->employee_id,
            'college_id' => (string) ($faculty->college_id ?? ''),
            'department' => $faculty->department ?? '',
            'specialization' => $faculty->specialization ?? '',
            'position' => $faculty->position ?? '',
            'status' => $faculty->status ?? FacultyModel::STATUS_ACTIVE,
            'phone' => $faculty->phone ?? '',
            'address' => $faculty->address ?? '',
            'expertise' => $faculty->expertise->pluck('area')->all(),
        ];
        $this->showForm = true;
    }

    public function save(FacultyContributionService $contribution): void
    {
        $editing = $this->editingId
            ? FacultyModel::with('user')->findOrFail($this->editingId)
            : null;

        Gate::authorize($editing ? 'update' : 'create', $editing ?? FacultyModel::class);

        $this->validate($this->rules($editing), [], $this->attributes());

        DB::transaction(function () use ($editing) {
            if ($editing) {
                $editing->user->update([
                    'name' => $this->form['name'],
                    'email' => $this->form['email'],
                ]);

                $editing->update([
                    'employee_id' => $this->form['employee_id'],
                    'college_id' => $this->form['college_id'] ?: null,
                    'department' => $this->form['department'] ?: null,
                    'specialization' => $this->form['specialization'] ?: null,
                    'position' => $this->form['position'] ?: null,
                    'status' => $this->form['status'],
                    'phone' => $this->form['phone'] ?: null,
                    'address' => $this->form['address'] ?: null,
                ]);

                $faculty = $editing;
            } else {
                // Account provisioning is Admin-only (§5.1). The password is a
                // deliberate demo default, matching the seeded accounts, so a
                // freshly created profile can actually be logged into during
                // the defense walkthrough.
                $user = User::create([
                    'name' => $this->form['name'],
                    'email' => $this->form['email'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_FACULTY,
                ]);

                $faculty = FacultyModel::create([
                    'user_id' => $user->id,
                    'employee_id' => $this->form['employee_id'] ?: app(EmployeeIdService::class)->next(),
                    'college_id' => $this->form['college_id'] ?: null,
                    'department' => $this->form['department'] ?: null,
                    'specialization' => $this->form['specialization'] ?: null,
                    'position' => $this->form['position'] ?: null,
                    'status' => $this->form['status'],
                    'phone' => $this->form['phone'] ?: null,
                    'address' => $this->form['address'] ?: null,
                ]);
            }

            $faculty->syncExpertise($this->form['expertise'], FacultyModel::expertiseCategoryMap());
        });

        $this->dispatch('sc-toast', message: $editing ? 'Faculty profile updated' : 'Faculty profile created', type: 'success');

        $this->closeForm();
    }

    /**
     * Soft-delete (deactivate) a faculty member.
     *
     * Soft, not forced: the person's historical contribution (rendered hours,
     * proposals, activities) must stay intact for reporting, so the row is kept
     * and simply hidden from the roster.
     */
    public function deactivate(int $id): void
    {
        $faculty = FacultyModel::findOrFail($id);

        Gate::authorize('delete', $faculty);

        $faculty->delete();

        $this->dispatch('sc-toast', message: 'Faculty profile deactivated', type: 'warning');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetForm();
    }

    /**
     * Toggle one expertise area in the form's multi-select.
     *
     * The shared `x-sc.multi-select` component calls back into the component
     * rather than binding the array directly — see its `toggle()` JS. The
     * (key, id) signature is the component's contract, so the key is ignored
     * here; there is only one multi-select on this page.
     */
    public function toggleExpertise(string $key, string $area): void
    {
        $current = $this->form['expertise'] ?? [];

        $this->form['expertise'] = in_array($area, $current, true)
            ? array_values(array_diff($current, [$area]))
            : array_values(array_merge($current, [$area]));
    }

    /* ---------------------------------------------------------------------
     | Internals
     | ------------------------------------------------------------------ */

    private function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'email' => '',
            'employee_id' => '',
            'college_id' => '',
            'department' => '',
            'specialization' => '',
            'position' => '',
            'status' => FacultyModel::STATUS_ACTIVE,
            'phone' => '',
            'address' => '',
            'expertise' => [],
        ];
        $this->resetErrorBag();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?FacultyModel $editing): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($editing?->user_id),
            ],
            'form.employee_id' => [
                'nullable', 'string', 'max:32',
                Rule::unique('faculties', 'employee_id')->ignore($editing?->id),
            ],
            'form.college_id' => ['nullable', 'exists:colleges,id'],
            'form.department' => ['nullable', 'string', 'max:255'],
            'form.specialization' => ['nullable', 'string', 'max:255'],
            'form.position' => ['nullable', Rule::in(
                array_column(config('smartcemes.position_ladder', []), 'label')
            )],
            'form.status' => ['required', Rule::in(FacultyModel::STATUSES)],
            'form.phone' => ['nullable', 'string', 'max:32'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.expertise' => ['array'],
            'form.expertise.*' => ['string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'form.name' => 'name',
            'form.email' => 'email address',
            'form.employee_id' => 'employee ID',
            'form.college_id' => 'college',
            'form.position' => 'position',
            'form.status' => 'status',
        ];
    }

    public function render(FacultyContributionService $contribution)
    {
        $faculty = FacultyModel::query()
            ->with(['user', 'college', 'expertise'])
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $this->search).'%';

                $query->where(function ($q) use ($term) {
                    $q->where('employee_id', 'like', $term)
                        ->orWhere('position', 'like', $term)
                        ->orWhere('department', 'like', $term)
                        ->orWhere('specialization', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term));
                });
            })
            ->forCollegeCode($this->collegeFilter)
            ->when($this->expertiseFilter !== '', fn ($query) => $query->whereHas(
                'expertise',
                fn ($q) => $q->where('area', $this->expertiseFilter)
            ))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->get()
            ->filter(fn (FacultyModel $f) => $f->user !== null)
            ->values();

        // Sorting happens after the contribution metrics are attached, because
        // "sort by hours" is not a database column.
        $rows = $contribution->forFaculty($faculty);

        $rows = match ($this->sort) {
            'hours' => $rows->sortByDesc('rendered_hours'),
            'projects' => $rows->sortByDesc('projects_involved'),
            'leads' => $rows->sortByDesc('projects_led'),
            'college' => $rows->sortBy(fn ($r) => $r['college'].' '.$r['name']),
            'rank' => $rows->sortBy(fn ($r) => $contribution->positionRank($r['position']) ?? 999),
            default => $rows->sortBy('name'),
        };

        return view('livewire.faculty.directory', [
            'rows' => $rows->values(),
            'colleges' => College::ordered()->get(),
            'expertiseOptions' => config('smartcemes.expertise_options', []),
            'positionLadder' => config('smartcemes.position_ladder', []),
            'contribution' => $contribution,
            'summary' => [
                'total' => $rows->count(),
                'active' => $rows->where('status', FacultyModel::STATUS_ACTIVE)->count(),
                'on_leave' => $rows->where('status', FacultyModel::STATUS_ON_LEAVE)->count(),
                'hours' => round($rows->sum('rendered_hours'), 1),
            ],
        ]);
    }
}
