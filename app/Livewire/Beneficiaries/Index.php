<?php

namespace App\Livewire\Beneficiaries;

use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Services\BeneficiaryImporter;
use App\Services\BeneficiaryRules;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * The beneficiary registry (2026-11-20).
 *
 * One project per beneficiary. Admin can add, archive and restore; the
 * Secretary can also edit (D8 stays). Contact details are visible to both roles;
 * the project hub's list is the read-only view for other roles.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $projectId = '';

    public string $communityId = '';

    public string $category = '';

    /** active | archived | all */
    public string $status = 'active';

    public bool $showForm = false;

    public ?int $editingId = null;

    public bool $showImport = false;

    public $importFile = null;

    public array $importRows = [];

    public bool $importPreview = false;

    public $importProjectId = '';

    public bool $showExport = false;

    public $exportProjectId = '';

    public array $form = [
        'first_name' => '', 'middle_name' => '', 'last_name' => '',
        'age' => '', 'gender' => '', 'phone' => '',
        'community_id' => '', 'extension_project_id' => '', 'beneficiary_category' => '',
    ];

    public function mount(): void
    {
        $this->guard();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProjectId(): void
    {
        $this->communityId = '';
        $this->resetPage();
    }

    public function updatedCommunityId(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFormExtensionProjectId(): void
    {
        // Editing an already-assigned beneficiary never changes the assignment.
        $assignedProject = $this->editingId !== null
            ? Beneficiary::find($this->editingId)?->extension_project_id
            : null;

        if ($assignedProject !== null) {
            $this->form['extension_project_id'] = $assignedProject;

            return;
        }

        $projectId = (int) ($this->form['extension_project_id'] ?? 0);
        $first = ExtensionProject::find($projectId)?->communities()->orderBy('name')->first();

        $this->form['community_id'] = $first?->id ?? '';
        $this->resetErrorBag('form.community_id');
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->guard();

        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $beneficiaryId): void
    {
        $this->guardEdit();

        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        $this->editingId = $beneficiary->id;
        $this->form = [
            'first_name' => $beneficiary->first_name ?? '',
            'middle_name' => $beneficiary->middle_name ?? '',
            'last_name' => $beneficiary->last_name ?? '',
            'age' => $beneficiary->age ?? '',
            'gender' => $beneficiary->gender ?? '',
            'phone' => $beneficiary->phone ?? '',
            'community_id' => $beneficiary->community_id ?? '',
            'extension_project_id' => $beneficiary->extension_project_id ?? '',
            'beneficiary_category' => $beneficiary->beneficiary_category ?? '',
        ];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetErrorBag();
    }

    public function openImport(): void
    {
        $this->guard();

        $this->resetErrorBag();
        $this->importFile = null;
        $this->importRows = [];
        $this->importPreview = false;
        $this->importProjectId = $this->projectId !== '' ? $this->projectId : '';
        $this->showImport = true;
    }

    public function updatedImportFile(): void
    {
        $this->resetErrorBag('importFile');
    }

    public function updatedImportProjectId(): void
    {
        $this->resetErrorBag('importProjectId');
    }

    public function parseImport(): void
    {
        $this->guard();

        $this->validate([
            'importProjectId' => ['required', 'integer', 'exists:extension_projects,id'],
            'importFile' => 'required|file|mimes:xlsx|max:'.config('smartcemes.uploads.max_kb'),
        ], [
            'importProjectId.required' => 'Choose the project this import belongs to.',
        ]);

        $project = ExtensionProject::findOrFail((int) $this->importProjectId);
        $result = app(BeneficiaryImporter::class)->parse($this->importFile, $project);

        if ($result['error'] !== null) {
            $this->addError('importFile', $result['error']);

            return;
        }

        $this->importRows = $result['rows'];
        $this->importPreview = true;
    }

    public function confirmImport(): void
    {
        $this->guard();

        if ($this->importRows === []) {
            $this->addError('importFile', 'Nothing parsed to import.');

            return;
        }

        $project = ExtensionProject::findOrFail((int) $this->importProjectId);
        $importer = app(BeneficiaryImporter::class);
        $counts = $importer->confirm($this->importRows, $project);

        $this->dispatch(
            'sc-toast',
            message: $importer->summary($counts),
            type: ($counts['created'] + $counts['updated']) > 0 ? 'success' : 'warn',
        );

        $this->showImport = false;
        $this->importPreview = false;
        $this->importRows = [];
        $this->importFile = null;

        // Show the roster the rows were just written to.
        $this->projectId = (string) $project->id;
        $this->communityId = '';
        $this->category = '';
        $this->search = '';
        $this->status = 'active';
        $this->resetPage();
    }

    public function openExport(): void
    {
        $this->guard();

        $this->resetErrorBag();
        $this->exportProjectId = $this->projectId !== '' ? $this->projectId : '';
        $this->showExport = true;
    }

    public function updatedExportProjectId(): void
    {
        $this->resetErrorBag('exportProjectId');
    }

    public function exportRoster(): void
    {
        $this->guard();

        $this->validate([
            'exportProjectId' => ['required', 'integer', 'exists:extension_projects,id'],
        ], [
            'exportProjectId.required' => 'Choose the project to export.',
        ]);

        $this->showExport = false;
        $this->redirectRoute('beneficiaries.export', [
            'project' => (int) $this->exportProjectId,
            'download' => 1,
        ]);
    }

    public function save(): void
    {
        $this->guard();

        if ($this->editingId !== null) {
            $this->guardEdit();
        }

        $editing = $this->editingId !== null
            ? Beneficiary::findOrFail($this->editingId)
            : null;

        // A beneficiary's project is fixed once assigned: editing resolves the
        // project from the stored record and ignores the submitted value. An
        // unassigned legacy row may still be assigned here.
        $projectIsFixed = $editing?->extension_project_id !== null;

        $this->validate(
            BeneficiaryRules::rules(true, 'form', ! $projectIsFixed),
            BeneficiaryRules::messages('form'),
        );

        $project = $projectIsFixed
            ? $editing->extensionProject
            : ExtensionProject::find($this->form['extension_project_id']);

        if ($projectIsFixed) {
            $this->form['extension_project_id'] = $project?->id;
        }

        $community = Community::find($this->form['community_id']);

        if ($community === null || $project === null || ! $project->communities()->whereKey($community->id)->exists()) {
            $this->addError('form.community_id', 'Choose a community or partner school linked to the person\'s project.');

            return;
        }

        $duplicates = Beneficiary::duplicatesFor(
            $this->form['first_name'],
            $this->form['last_name'],
            $community->id,
            $project->id,
        )->reject(fn (Beneficiary $b) => $b->id === $this->editingId);

        if ($duplicates->isNotEmpty()) {
            $this->addError('form.first_name', 'A beneficiary with the same first name, last name, and community already exists in this project.');

            return;
        }

        $data = [
            'first_name' => $this->form['first_name'],
            'middle_name' => $this->form['middle_name'] ?: null,
            'last_name' => $this->form['last_name'],
            'age' => $this->form['age'] !== '' ? (int) $this->form['age'] : null,
            'gender' => $this->form['gender'] ?: null,
            'phone' => BeneficiaryRules::normaliseContact($this->form['phone']),
            'barangay' => $community->name,
            'municipality' => $community->municipality,
            'province' => $community->province,
            'community_id' => $community->id,
            'extension_project_id' => $project->id,
            'beneficiary_category' => $this->form['beneficiary_category'],
        ];

        if ($this->editingId !== null) {
            $beneficiary = Beneficiary::findOrFail($this->editingId);
            $beneficiary->update($data);

            activity()->performedOn($beneficiary)->event('beneficiary_updated')
                ->log("Beneficiary '{$beneficiary->fullName()}' updated from the registry");
            $message = 'Beneficiary updated';
        } else {
            $beneficiary = Beneficiary::create($data);

            activity()->performedOn($beneficiary)->event('beneficiary_created')
                ->log("Beneficiary '{$beneficiary->fullName()}' added to the registry");
            $message = 'Beneficiary added';
        }

        $this->closeForm();
        $this->dispatch('sc-toast', message: $message, type: 'success');
    }

    public function archive(int $beneficiaryId): void
    {
        $this->guard();

        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $beneficiary->delete();

        activity()->performedOn($beneficiary)->event('beneficiary_archived')
            ->log("Beneficiary '{$beneficiary->fullName()}' archived");

        $this->dispatch('sc-toast', message: 'Beneficiary archived. Attendance history stays.', type: 'warn');
    }

    public function restore(int $beneficiaryId): void
    {
        $this->guard();

        $beneficiary = Beneficiary::withTrashed()->findOrFail($beneficiaryId);
        $beneficiary->restore();

        activity()->performedOn($beneficiary)->event('beneficiary_restored')
            ->log("Beneficiary '{$beneficiary->fullName()}' restored");

        $this->dispatch('sc-toast', message: 'Beneficiary restored', type: 'success');
    }

    public function render()
    {
        $query = Beneficiary::query()
            ->with(['community', 'extensionProject'])
            ->when($this->status === 'archived', fn ($q) => $q->onlyTrashed())
            ->when($this->status === 'all', fn ($q) => $q->withTrashed())
            ->when($this->projectId !== '', fn ($q) => $q->where('extension_project_id', $this->projectId))
            ->when($this->communityId !== '', fn ($q) => $q->where('community_id', $this->communityId))
            ->when($this->category !== '', fn ($q) => $q->where('beneficiary_category', $this->category))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($this->search)).'%';
                $q->where(fn ($inner) => $inner
                    ->where('first_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('barangay', 'like', $term)
                    ->orWhereHas('community', fn ($c) => $c->where('name', 'like', $term)));
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        $editingBeneficiary = $this->editingId !== null ? Beneficiary::find($this->editingId) : null;
        $projectLocked = $editingBeneficiary?->extension_project_id !== null;
        $formProject = $projectLocked
            ? $editingBeneficiary->extensionProject
            : ExtensionProject::find($this->form['extension_project_id'] ?? 0);

        return view('livewire.beneficiaries.index', [
            'beneficiaries' => $query->paginate(15),
            'projects' => ExtensionProject::orderBy('code')->get(['id', 'code', 'title']),
            'communities' => Community::orderBy('name')->get(['id', 'name', 'municipality', 'type']),
            'formCommunities' => $formProject?->communities()->orderBy('name')->get() ?? collect(),
            'projectLocked' => $projectLocked,
            'categories' => config('smartcemes.beneficiary_categories'),
            'activeCount' => Beneficiary::count(),
            'archivedCount' => Beneficiary::onlyTrashed()->count(),
            'projectCount' => ExtensionProject::count(),
        ]);
    }

    private function resetForm(): void
    {
        $firstProject = ExtensionProject::orderBy('code')->first();
        $firstCommunity = $firstProject?->communities()->orderBy('name')->first();

        $this->editingId = null;
        $this->form = [
            'first_name' => '', 'middle_name' => '', 'last_name' => '',
            'age' => '', 'gender' => '', 'phone' => '',
            'community_id' => $firstCommunity?->id ?? '',
            'extension_project_id' => $firstProject?->id ?? '',
            'beneficiary_category' => '',
        ];
        $this->resetErrorBag();
    }

    private function guard(): void
    {
        abort_unless(
            in_array(auth()->user()?->role, [User::ROLE_ADMIN, User::ROLE_SECRETARY], true),
            403,
        );
    }

    private function guardEdit(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_SECRETARY, 403, 'Only the Secretary can edit a beneficiary profile.');
    }
}
