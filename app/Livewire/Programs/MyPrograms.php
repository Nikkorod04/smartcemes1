<?php

namespace App\Livewire\Programs;

use App\Models\ExtensionProject;
use App\Models\Faculty;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MyPrograms extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'start_date')]
    public string $sort = 'start_date';

    #[Url(as: 'direction', except: 'asc')]
    public string $direction = 'asc';

    #[Url(as: 'view', except: 'grid')]
    public string $viewMode = 'grid';

    public int $perPage = 9;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedDirection(): void
    {
        $this->resetPage();
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sort = 'start_date';
        $this->direction = 'asc';
        $this->resetPage();
    }

    public function render()
    {
        $faculty = Faculty::where('user_id', auth()->id())->first();

        $programs = ExtensionProject::query()->whereKey(0);
        if ($faculty) {
            $ledIds = ExtensionProject::where('program_lead_id', $faculty->id)->pluck('id');
            $assignedIds = $faculty->activities()->pluck('extension_project_id')->unique();

            $programs = ExtensionProject::query()
                ->with(['programLead.user', 'college'])
                ->whereIn('id', $ledIds->merge($assignedIds));

            if ($this->search !== '') {
                $term = '%'.trim($this->search).'%';
                $programs->where(function ($query) use ($term) {
                    $query->where('title', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhereHas('college', fn ($college) => $college->where('name', 'like', $term)->orWhere('code', 'like', $term));
                });
            }
        }

        $sortColumn = [
            'start_date' => 'planned_start_date',
            'title' => 'title',
            'status' => 'status',
            'updated' => 'updated_at',
        ][$this->sort] ?? 'planned_start_date';
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';
        $programs = $programs->orderBy($sortColumn, $direction)->orderBy('id')->paginate($this->perPage);

        return view('livewire.programs.my', [
            'programs' => $programs,
        ]);
    }
}
