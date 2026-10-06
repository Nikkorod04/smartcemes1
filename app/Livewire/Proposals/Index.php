<?php

namespace App\Livewire\Proposals;

use App\Models\Activity;
use App\Models\ActivityProposal;
use App\Models\Faculty;
use App\Notifications\SmartCemesNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;

    #[Url]
    public string $status = '';

    public ?int $detailId = null;

    public bool $showApprove = false;

    public bool $showReject = false;

    public string $adminRemarks = '';

    public string $rejectionReason = '';

    public $specialOrderFile;

    public function updatedShowApprove(): void
    {
        if (! $this->showApprove) {
            $this->specialOrderFile = null;
        }
    }

    /** 5.12: Admin approves with remarks; may attach a Special Order (PDF). */
    public function approve(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Separation of duties — only the Admin approves proposals.');

        $proposal = ActivityProposal::with('program')->findOrFail($id);

        if ($proposal->status !== ActivityProposal::STATUS_PENDING) {
            return;
        }

        // 5.12 / 8.8: proposed dates validated against the target program's
        // range BEFORE approval — approval is blocked when outside.
        if ($proposal->violatesProgramRange()) {
            $this->dispatch('sc-toast',
                message: 'Approval blocked: proposed dates ('.$proposal->proposed_start_date->format('M j, Y').' – '.$proposal->proposed_end_date->format('M j, Y').') fall outside the program range (8.8).',
                type: 'error');

            return;
        }

        $path = null;
        if ($this->specialOrderFile) {
            $this->validate([
                'specialOrderFile' => 'file|mimes:pdf|max:'.config('smartcemes.uploads.max_kb'),
            ]);
            $path = $this->specialOrderFile->store('special-orders/'.now()->format('Y/m'), 'public');
        }

        $proposal->update([
            'status' => ActivityProposal::STATUS_APPROVED,
            'admin_approved_by' => auth()->id(),
            'admin_approved_at' => now(),
            'admin_remarks' => $this->adminRemarks ?: null,
            'special_order_path' => $path,
        ]);

        // ON APPROVAL: auto-create the linked Activity in draft status (5.12),
        // copying title and dates, and stamp created_activity_id.
        $activity = Activity::create([
            'extension_project_id' => $proposal->extension_project_id,
            'activity_proposal_id' => $proposal->id,
            'title' => $proposal->title,
            'description' => $proposal->description,
            'planned_start_date' => $proposal->proposed_start_date,
            'planned_end_date' => $proposal->proposed_end_date,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'draft',
        ]);

        $proposal->update(['created_activity_id' => $activity->id]);

        activity()->performedOn($proposal)->event('approved')
            ->log("Proposal '{$proposal->title}' approved — activity auto-created");

        $proposal->faculty->user?->notify(new SmartCemesNotification(
            'Proposal approved',
            "'{$proposal->title}' was approved by the Director — a draft activity was created in the program hub.".($path ? ' Special Order attached.' : ' No Special Order attached (informational).'),
            'check', 'green'
        ));

        $this->closeModals();
        $this->dispatch('sc-toast', message: 'Proposal approved — activity auto-created (draft)', type: 'success');
    }

    /** 5.12: rejection requires a reason. */
    public function reject(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Separation of duties — only the Admin rejects proposals.');

        $this->validate([
            'rejectionReason' => 'required|string|min:10|max:2000',
        ]);

        $proposal = ActivityProposal::findOrFail($id);

        if ($proposal->status !== ActivityProposal::STATUS_PENDING) {
            return;
        }

        $proposal->update([
            'status' => ActivityProposal::STATUS_REJECTED,
            'rejection_reason' => $this->rejectionReason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        activity()->performedOn($proposal)->event('rejected')
            ->log("Proposal '{$proposal->title}' rejected: ".$this->rejectionReason);

        $proposal->faculty->user?->notify(new SmartCemesNotification(
            'Proposal rejected',
            "'{$proposal->title}' was rejected — see remarks from the Director.",
            'doc', 'red'
        ));

        $this->closeModals();
        $this->dispatch('sc-toast', message: 'Proposal rejected with remarks', type: 'warn');
    }

    public function openApprove(int $id): void
    {
        $this->adminRemarks = '';
        $this->specialOrderFile = null;
        $this->detailId = $id;
        $this->showApprove = true;
        $this->resetErrorBag();
    }

    public function openReject(int $id): void
    {
        $this->rejectionReason = '';
        $this->detailId = $id;
        $this->showReject = true;
        $this->resetErrorBag();
    }

    public function viewDetail(int $id): void
    {
        $this->detailId = $id;
    }

    public function closeModals(): void
    {
        $this->reset(['detailId', 'showApprove', 'showReject', 'rejectionReason', 'adminRemarks']);
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $proposals = ActivityProposal::query()
            ->with(['faculty.user', 'program', 'community', 'createdActivity'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($user->isFaculty(), function ($q) use ($user) {
                $q->where('faculty_id', Faculty::where('user_id', $user->id)->value('id'));
            })
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('submitted_at')
            ->get();

        $detail = $this->detailId
            ? ActivityProposal::with(['faculty.user', 'program', 'community', 'documents', 'createdActivity', 'approver', 'rejecter'])->find($this->detailId)
            : null;

        return view('livewire.proposals.index', [
            'proposals' => $proposals,
            'detail' => $detail,
            'isAdmin' => $isAdmin,
        ]);
    }
}
