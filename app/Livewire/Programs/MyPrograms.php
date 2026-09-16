<?php

namespace App\Livewire\Programs;

use App\Models\ExtensionProgram;
use App\Models\Faculty;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyPrograms extends Component
{
    public function render()
    {
        $faculty = Faculty::where('user_id', auth()->id())->first();

        $programs = collect();
        if ($faculty) {
            $ledIds = ExtensionProgram::where('program_lead_id', $faculty->id)->pluck('id');
            $assignedIds = $faculty->activities()->pluck('extension_program_id')->unique();

            $programs = ExtensionProgram::query()
                ->with(['programLead.user'])
                ->whereIn('id', $ledIds->merge($assignedIds))
                ->orderBy('planned_start_date')
                ->get();
        }

        return view('livewire.programs.my', [
            'programs' => $programs,
        ]);
    }
}
