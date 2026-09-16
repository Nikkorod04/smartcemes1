<?php

namespace App\Livewire\Programs;

use App\Models\Community;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Services\KpiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $view = 'grid';

    public bool $showForm = false;

    public array $form = [
        'title' => '',
        'description' => '',
        'planned_start_date' => '',
        'planned_end_date' => '',
        'target_beneficiaries' => '',
        'allocated_budget' => '',
        'program_lead_id' => '',
        'community_ids' => [],
        'beneficiary_categories' => [],
        'status' => 'draft',
    ];

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function toggleFormArray(string $key, string|int $value): void
    {
        if (! in_array($key, ['community_ids', 'beneficiary_categories'], true)) {
            return;
        }

        $values = is_array($this->form[$key] ?? null) ? $this->form[$key] : [];

        $this->form[$key] = in_array($value, $values, true)
            ? array_values(array_diff($values, [$value]))
            : [...$values, $value];
    }

    public function save(): void
    {
        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.description' => 'nullable|string|max:4000',
            'form.planned_start_date' => 'required|date',
            'form.planned_end_date' => 'required|date|after_or_equal:form.planned_start_date',
            'form.target_beneficiaries' => 'nullable|integer|min:1',
            'form.allocated_budget' => 'nullable|numeric|min:0',
            'form.program_lead_id' => 'nullable|exists:faculties,id',
            'form.community_ids' => 'array',
            'form.community_ids.*' => 'exists:communities,id',
            'form.beneficiary_categories' => 'array',
            'form.beneficiary_categories.*' => 'string',
            'form.status' => 'required|in:draft,ongoing,completed,cancelled',
        ]);

        $program = ExtensionProgram::create([
            'code' => ExtensionProgram::nextCode(),
            'title' => $this->form['title'],
            'description' => $this->form['description'] ?: null,
            'planned_start_date' => $this->form['planned_start_date'],
            'planned_end_date' => $this->form['planned_end_date'],
            'target_beneficiaries' => $this->form['target_beneficiaries'] ?: null,
            'beneficiary_categories' => $this->form['beneficiary_categories'],
            'allocated_budget' => $this->form['allocated_budget'] ?: 0,
            'program_lead_id' => $this->form['program_lead_id'] ?: null,
            'status' => $this->form['status'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        if ($this->form['community_ids']) {
            $program->communities()->sync($this->form['community_ids']);
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('sc-toast', message: 'Program created — code '.$program->code, type: 'success');
    }

    public function render()
    {
        $programs = ExtensionProgram::query()
            ->with(['programLead.user', 'communities'])
            ->withCount('activities')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('planned_start_date')
            ->get();

        $kpi = app(KpiService::class);

        $rows = $programs->map(fn ($p) => (object) [
            'model' => $p,
            'utilized' => $p->utilizedBudget(),
            'utilization_pct' => $kpi->budgetUtilization($p),
            'over' => $p->isOverAllocated(),
            'lead_name' => $p->programLead?->user?->name,
            'reached' => $kpi->distinctServed($p),
        ]);

        return view('livewire.programs.index', [
            'rows' => $rows,
            'statuses' => config('smartcemes.statuses.program'),
            'faculties' => Faculty::with('user')->orderBy('id')->get(),
            'communities' => Community::orderBy('name')->get(),
            'categories' => config('smartcemes.beneficiary_categories'),
        ]);
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->form = [
            'title' => '',
            'description' => '',
            'planned_start_date' => '',
            'planned_end_date' => '',
            'target_beneficiaries' => '',
            'allocated_budget' => '',
            'program_lead_id' => '',
            'community_ids' => [],
            'beneficiary_categories' => [],
            'status' => 'draft',
        ];
        $this->resetErrorBag();
        $this->dispatch('ms-sync-community_ids', ids: []);
        $this->dispatch('ms-sync-beneficiary_categories', ids: []);
    }
}
