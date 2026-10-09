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
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    private const PER_PAGE = 10;

    #[Url]
    public string $status = '';

    public string $search = '';

    public string $sort = 'priority';

    public string $direction = 'asc';

    public ?int $detailId = null;

    /** The proposal targeted by the approve/reject workflow modal. */
    public ?int $actionId = null;

    public bool $showApprove = false;

    public bool $showReject = false;

    public string $adminRemarks = '';

    public string $rejectionReason = '';

    public $specialOrderFile;

    public function setStatus(string $status): void
    {
        abort_unless(in_array($status, ['', ActivityProposal::STATUS_PENDING, ActivityProposal::STATUS_APPROVED, ActivityProposal::STATUS_REJECTED], true), 422);

        $this->status = $status;
        $this->resetPage();
    }

    public function updatingStatus(string $status): void
    {
        abort_unless(in_array($status, ['', ActivityProposal::STATUS_PENDING, ActivityProposal::STATUS_APPROVED, ActivityProposal::STATUS_REJECTED], true), 422);

        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        abort_unless(in_array($column, ['priority', 'title', 'faculty', 'project', 'dates', 'budget', 'submitted', 'status'], true), 422);

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = in_array($column, ['dates', 'budget', 'submitted'], true) ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'livewire.partials.pagination';
    }

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
        $this->detailId = null;
        $this->actionId = $id;
        $this->showReject = false;
        $this->showApprove = true;
        $this->resetErrorBag();
    }

    public function openReject(int $id): void
    {
        $this->rejectionReason = '';
        $this->detailId = null;
        $this->actionId = $id;
        $this->showApprove = false;
        $this->showReject = true;
        $this->resetErrorBag();
    }

    public function viewDetail(int $id): void
    {
        $this->actionId = null;
        $this->showApprove = false;
        $this->showReject = false;
        $this->detailId = $id;
    }

    public function closeModals(): void
    {
        $this->reset(['detailId', 'actionId', 'showApprove', 'showReject', 'rejectionReason', 'adminRemarks']);
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $baseQuery = ActivityProposal::query()
            ->select('activity_proposals.*')
            ->when($user->isFaculty(), function ($q) use ($user) {
                $q->where('faculty_id', Faculty::where('user_id', $user->id)->value('id'));
            });

        $overview = (clone $baseQuery)->with('program')->get();
        $counts = $overview->groupBy('status')->map->count();

        $query = (clone $baseQuery)->with(['faculty.user', 'program', 'community', 'createdActivity', 'documents']);

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('faculty.user', fn ($faculty) => $faculty->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('program', fn ($program) => $program->where('code', 'like', "%{$search}%"))
                    ->orWhereHas('community', fn ($community) => $community->where('name', 'like', "%{$search}%"));
            });
        }

        if ($this->sort === 'priority') {
            $query->orderByRaw("CASE activity_proposals.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
                ->orderByDesc('activity_proposals.submitted_at');
        } elseif ($this->sort === 'faculty') {
            $query->leftJoin('faculties', 'faculties.id', '=', 'activity_proposals.faculty_id')
                ->leftJoin('users', 'users.id', '=', 'faculties.user_id')
                ->orderBy('users.name', $this->direction)
                ->orderByDesc('activity_proposals.submitted_at');
        } elseif ($this->sort === 'project') {
            $query->leftJoin('extension_projects', 'extension_projects.id', '=', 'activity_proposals.extension_project_id')
                ->orderBy('extension_projects.code', $this->direction)
                ->orderByDesc('activity_proposals.submitted_at');
        } else {
            $sortColumn = [
                'title' => 'title',
                'dates' => 'proposed_start_date',
                'budget' => 'budget_estimate',
                'submitted' => 'submitted_at',
                'status' => 'status',
            ][$this->sort];
            $query->orderBy('activity_proposals.'.$sortColumn, $this->direction);
        }

        $detail = $this->detailId
            ? ActivityProposal::with(['faculty.user', 'program', 'community', 'documents', 'createdActivity', 'approver', 'rejecter'])->find($this->detailId)
            : null;

        return view('livewire.proposals.index', [
            'proposals' => $query->paginate(self::PER_PAGE),
            'detail' => $detail,
            'isAdmin' => $isAdmin,
            'counts' => $counts,
            'outOfRange' => $overview->filter(fn (ActivityProposal $proposal) => $proposal->violatesProgramRange())->count(),
        ]);
    }
}
