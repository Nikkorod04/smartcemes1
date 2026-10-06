<?php

namespace App\Livewire\Proposals;

use App\Models\ActivityProposal;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\SmartCemesNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public array $form = [
        'title' => '',
        'description' => '',
        'extension_project_id' => '',
        'community_id' => '',
        'proposed_start_date' => '',
        'proposed_end_date' => '',
        'budget_estimate' => '',
    ];

    public array $attachments = [];

    public function save(): void
    {
        abort_unless(auth()->user()->isFaculty(), 403, 'Only faculty submit activity proposals.');

        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.description' => 'nullable|string|max:4000',
            'form.extension_project_id' => 'required|exists:extension_projects,id',
            'form.community_id' => 'required|exists:communities,id',
            'form.proposed_start_date' => 'required|date',
            'form.proposed_end_date' => 'required|date|after_or_equal:form.proposed_start_date',
            'form.budget_estimate' => 'nullable|numeric|min:0',
            'attachments' => 'array|max:5',
            'attachments.*' => 'file|mimes:'.implode(',', config('smartcemes.uploads.mimes')).'|max:'.config('smartcemes.uploads.max_kb'),
        ]);

        $faculty = Faculty::where('user_id', auth()->id())->firstOrFail();

        $proposal = ActivityProposal::create([
            'faculty_id' => $faculty->id,
            'extension_project_id' => $this->form['extension_project_id'],
            'community_id' => $this->form['community_id'],
            'title' => $this->form['title'],
            'description' => $this->form['description'] ?: null,
            'proposed_start_date' => $this->form['proposed_start_date'],
            'proposed_end_date' => $this->form['proposed_end_date'],
            'budget_estimate' => $this->form['budget_estimate'] !== '' ? $this->form['budget_estimate'] : null,
            'status' => ActivityProposal::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        foreach ($this->attachments as $file) {
            $path = $file->store('proposal-documents/'.now()->format('Y/m'), 'public');
            $proposal->documents()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientOriginalExtension(),
                'uploaded_at' => now(),
            ]);
        }

        activity()->performedOn($proposal)->event('submitted')
            ->log("Proposal '{$proposal->title}' submitted for approval");

        User::where('role', User::ROLE_ADMIN)->each(fn ($admin) => $admin->notify(
            new SmartCemesNotification(
                'New proposal submitted',
                "'{$proposal->title}' by ".auth()->user()->name.' — awaiting Director review.',
                'doc', 'blue'
            )
        ));

        session()->flash('sc-proposal-submitted', $proposal->id);

        $this->dispatch('sc-toast', message: 'Proposal submitted — pending Director review', type: 'success');

        $this->reset(['form', 'attachments']);
        $this->form = [
            'title' => '', 'description' => '', 'extension_project_id' => '',
            'community_id' => '', 'proposed_start_date' => '', 'proposed_end_date' => '', 'budget_estimate' => '',
        ];
    }

    public function render()
    {
        $faculty = Faculty::where('user_id', auth()->id())->first();

        return view('livewire.proposals.create', [
            'programs' => ExtensionProject::orderBy('title')->get(),
            'communities' => Community::orderBy('name')->get(),
            'myProposals' => $faculty
                ? $faculty->activityProposals()->with(['program', 'community'])->orderByDesc('submitted_at')->take(5)->get()
                : collect(),
            'mimes' => config('smartcemes.uploads.mimes'),
        ]);
    }
}
