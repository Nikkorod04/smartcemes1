<?php

namespace App\Livewire\Beneficiaries;

use App\Models\ExtensionProgram;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $search = '';

    public function render()
    {
        $term = trim($this->search);

        $programs = ExtensionProgram::query()
            ->with(['programLead.user', 'communities'])
            ->withCount(['beneficiaries', 'activities'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('code', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.beneficiaries.index', [
            'programs' => $programs,
            'totalEnrolled' => (clone $programs)->sum('beneficiaries_count'),
        ]);
    }
}
