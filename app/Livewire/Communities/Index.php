<?php

namespace App\Livewire\Communities;

use App\Models\Community;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $province = '';

    public string $status = '';

    public string $type = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'municipality' => '',
        'province' => 'Leyte',
        'type' => Community::TYPE_COMMUNITY,
        'school_level' => '',
        'contact_person' => '',
        'contact_number' => '',
        'email' => '',
        'address' => '',
        'description' => '',
        'status' => 'prospecting',
    ];

    public ?int $detailId = null;

    protected function rules(): array
    {
        $rules = [
            'form.name' => 'required|string|max:255',
            'form.municipality' => 'required|string|max:255',
            'form.province' => 'required|string|max:255',
            'form.type' => 'required|in:'.implode(',', config('smartcemes.community_types')),
            'form.school_level' => 'nullable|in:'.implode(',', config('smartcemes.school_levels')),
            'form.contact_person' => 'nullable|string|max:255',
            'form.contact_number' => 'nullable|string|max:50',
            'form.email' => 'nullable|email|max:255',
            'form.address' => 'nullable|string|max:500',
            'form.description' => 'nullable|string|max:2000',
            'form.status' => 'required|in:active,prospecting',
        ];

        // School records must declare a level; communities must not carry one.
        if (($this->form['type'] ?? '') === Community::TYPE_SCHOOL) {
            $rules['form.school_level'] = 'required|in:'.implode(',', config('smartcemes.school_levels'));
        }

        return $rules;
    }

    public function updatedShowForm($value): void
    {
        if (! $value) {
            $this->resetForm();
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $community = Community::findOrFail($id);
        $this->editingId = $id;
        $this->form = [
            'name' => $community->name,
            'municipality' => $community->municipality,
            'province' => $community->province,
            'type' => $community->type ?? Community::TYPE_COMMUNITY,
            'school_level' => (string) ($community->school_level ?? ''),
            'contact_person' => (string) $community->contact_person,
            'contact_number' => (string) $community->contact_number,
            'email' => (string) $community->email,
            'address' => (string) $community->address,
            'description' => (string) $community->description,
            'status' => $community->status,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        // Communities never carry a school level.
        $attributes = $this->form;
        if ($attributes['type'] !== Community::TYPE_SCHOOL) {
            $attributes['school_level'] = null;
        } else {
            $attributes['status'] = 'active';
        }

        if ($this->editingId) {
            $community = Community::findOrFail($this->editingId);
            $community->update($attributes);
            $this->dispatch('sc-toast', message: ($community->isSchool() ? 'Partner school updated' : 'Community updated'), type: 'success');
        } else {
            Community::create($attributes);
            $this->dispatch('sc-toast', message: $attributes['type'] === Community::TYPE_SCHOOL
                ? 'Partner school added — schools are active partners by default'
                : 'Community profile created — status set to '.$attributes['status'], type: 'success');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        Community::findOrFail($id)->delete();
        $this->dispatch('sc-toast', message: 'Community archived', type: 'warn');
    }

    public function restore(int $id): void
    {
        $community = Community::withTrashed()->findOrFail($id);
        $community->restore();
        $this->dispatch('sc-toast', message: $community->isSchool() ? 'Partner school restored' : 'Community restored', type: 'success');
    }

    public function viewDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
    }

    public function render()
    {
        $communities = Community::query()
            ->when($this->status === 'archived', fn ($q) => $q->onlyTrashed())
            ->withCount([
                'beneficiaries' => fn ($q) => $q->whereNull('beneficiaries.deleted_at'),
                'extensionPrograms',
            ])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('municipality', 'like', "%{$this->search}%")
                ->orWhere('contact_person', 'like', "%{$this->search}%")))
            ->when($this->province !== '', fn ($q) => $q->where('province', $this->province))
            ->when($this->status !== '' && $this->status !== 'archived', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->orderBy('name')
            ->get();

        $provinces = Community::query()
            ->when($this->status === 'archived', fn ($q) => $q->onlyTrashed())
            ->select('province')->distinct()->orderBy('province')->pluck('province');

        $detail = $this->detailId ? Community::withTrashed()->findOrFail($this->detailId) : null;

        $typeCounts = Community::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('livewire.communities.index', [
            'communities' => $communities,
            'provinces' => $provinces,
            'detail' => $detail,
            'detailPrograms' => $detail?->extensionPrograms()->get() ?? collect(),
            'detailSummaries' => $detail?->assessmentSummaries()->orderBy('year')->orderBy('quarter')->get() ?? collect(),
            'communityCount' => $typeCounts['community'] ?? 0,
            'schoolCount' => $typeCounts['school'] ?? 0,
        ]);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'municipality' => '',
            'province' => 'Leyte',
            'type' => Community::TYPE_COMMUNITY,
            'school_level' => '',
            'contact_person' => '',
            'contact_number' => '',
            'email' => '',
            'address' => '',
            'description' => '',
            'status' => 'prospecting',
        ];
        $this->resetErrorBag();
    }
}
