<?php

namespace App\Livewire\RenderedHours;

use App\Models\RenderedHours;
use App\Notifications\SmartCemesNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 10;

    public string $status = 'all';

    public string $source = 'all';

    public string $search = '';

    public string $sort = 'priority';

    public string $direction = 'asc';

    public bool $showReject = false;

    public ?int $rejectingId = null;

    public string $rejectRemarks = '';

    public function setStatus(string $status): void
    {
        abort_unless(in_array($status, ['all', RenderedHours::STATUS_PENDING, RenderedHours::STATUS_APPROVED, RenderedHours::STATUS_REJECTED], true), 422);

        $this->status = $status;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSource(string $source): void
    {
        abort_unless(in_array($source, ['all', RenderedHours::SOURCE_AUTO, RenderedHours::SOURCE_MANUAL], true), 422);

        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        abort_unless(in_array($column, ['priority', 'faculty', 'activity', 'date', 'hours', 'source', 'status'], true), 422);

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = in_array($column, ['date', 'hours'], true) ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    /** The shared design-system paginator, not Livewire's Tailwind default. */
    public function paginationView(): string
    {
        return 'livewire.partials.pagination';
    }

    /** 8.9: Admin approves or rejects with remarks. */
    public function approve(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Separation of duties — only the Admin approves rendered hours.');

        $entry = RenderedHours::with(['faculty.user', 'activity'])->findOrFail($id);
        abort_unless($entry->status === RenderedHours::STATUS_PENDING, 422, 'Already reviewed.');
        abort_if($entry->isLocked(), 422, 'Approved entries are locked.');

        $entry->update([
            'status' => RenderedHours::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        activity()->performedOn($entry)->event('approved')
            ->log("Rendered hours approved: {$entry->hours} hrs for '{$entry->activity->title}' (locked)");

        $entry->faculty->user?->notify(new SmartCemesNotification(
            'Rendered hours approved',
            "'{$entry->activity->title}' · {$entry->hours} hrs approved by the Director.",
            'check', 'green'
        ));

        $this->dispatch('sc-toast', message: 'Rendered hours approved — entry locked', type: 'success');
    }

    public function openReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectRemarks = '';
        $this->showReject = true;
        $this->resetErrorBag();
    }

    /** 8.9: rejection requires remarks. */
    public function confirmReject(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Separation of duties — only the Admin approves rendered hours.');

        $this->validate([
            'rejectRemarks' => 'required|string|min:5|max:1000',
        ]);

        $entry = RenderedHours::with(['faculty.user', 'activity'])->findOrFail($this->rejectingId);
        abort_unless($entry->status === RenderedHours::STATUS_PENDING, 422, 'Already reviewed.');

        $entry->update([
            'status' => RenderedHours::STATUS_REJECTED,
            'remarks' => $this->rejectRemarks,
        ]);

        activity()->performedOn($entry)->event('rejected')
            ->log('Rendered hours rejected: '.$this->rejectRemarks);

        $entry->faculty->user?->notify(new SmartCemesNotification(
            'Rendered hours rejected',
            "'{$entry->activity->title}' — see remarks from the Director.",
            'doc', 'red'
        ));

        $this->reset(['showReject', 'rejectRemarks', 'rejectingId']);
        $this->dispatch('sc-toast', message: 'Rendered hours rejected with remarks', type: 'warn');
    }

    public function render()
    {
        $query = RenderedHours::query()
            ->select('rendered_hours.*')
            ->with(['faculty.user', 'activity.program']);

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->source !== 'all') {
            $query->where('source', $this->source);
        }

        if (trim($this->search) !== '') {
            $search = trim($this->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('faculty.user', fn ($faculty) => $faculty->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('activity', function ($activity) use ($search) {
                        $activity->where('title', 'like', "%{$search}%")
                            ->orWhereHas('program', fn ($program) => $program->where('code', 'like', "%{$search}%"));
                    });
            });
        }

        $counts = RenderedHours::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        if ($this->sort === 'priority') {
            $query->orderByRaw("CASE rendered_hours.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
                ->orderByDesc('rendered_hours.date');
        } elseif ($this->sort === 'faculty') {
            $query->leftJoin('faculties', 'faculties.id', '=', 'rendered_hours.faculty_id')
                ->leftJoin('users', 'users.id', '=', 'faculties.user_id')
                ->orderBy('users.name', $this->direction)
                ->orderByDesc('rendered_hours.date');
        } elseif ($this->sort === 'activity') {
            $query->leftJoin('activities', 'activities.id', '=', 'rendered_hours.activity_id')
                ->orderBy('activities.title', $this->direction)
                ->orderByDesc('rendered_hours.date');
        } else {
            $query->orderBy('rendered_hours.'.$this->sort, $this->direction);
        }

        return view('livewire.rendered-hours.index', [
            'queue' => $query->paginate(self::PER_PAGE),
            'counts' => $counts,
            'pendingHours' => RenderedHours::where('status', RenderedHours::STATUS_PENDING)->sum('hours'),
            'approvedHours' => RenderedHours::where('status', RenderedHours::STATUS_APPROVED)->sum('hours'),
        ]);
    }
}
