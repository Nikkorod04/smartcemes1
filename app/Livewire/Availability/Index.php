<?php

namespace App\Livewire\Availability;

use App\Models\Activity;
use App\Models\AvailabilityRequest;
use App\Models\Faculty;
use App\Notifications\SmartCemesNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $sort = 'date';

    public string $direction = 'asc';

    /** Admin-initiated request form (5.8). */
    public bool $showCreate = false;

    public array $form = [
        'faculty_id' => '',
        'activity_id' => '',
        'date' => '',
        'start_time' => '08:00',
        'end_time' => '12:00',
        'remarks' => '',
    ];

    /** Faculty response state. */
    public ?int $respondingId = null;

    public bool $showDecline = false;

    public string $declineReason = '';

    public function setStatus(string $status): void
    {
        abort_unless(in_array($status, ['all', 'pending', 'accepted', 'declined'], true), 422);

        $this->status = $status;
        $this->resetPagination();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ['date', 'status', 'requested'], true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }

        $this->resetPagination();
    }

    public function updatedSearch(): void
    {
        $this->resetPagination();
    }

    protected function resetPagination(): void
    {
        $this->resetPage('awaitingPage');
        $this->resetPage('respondedPage');
        $this->resetPage('myRequestsPage');
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only the Admin creates availability requests (5.8).');

        $this->form = [
            'faculty_id' => '',
            'activity_id' => '',
            'date' => '',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'remarks' => '',
        ];
        $this->showCreate = true;
        $this->resetErrorBag();
    }

    /** Defaults date/time from the linked activity (6.8). */
    public function updatedFormActivityId($activityId): void
    {
        if ($activityId && ($activity = Activity::find($activityId))) {
            $this->form['date'] = $activity->planned_start_date->format('Y-m-d');
            $this->form['start_time'] = $activity->start_time->format('H:i');
            $this->form['end_time'] = $activity->end_time->format('H:i');
        }
    }

    public function saveCreate(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only the Admin creates availability requests (5.8).');

        $this->validate([
            'form.faculty_id' => 'required|exists:faculties,id',
            'form.activity_id' => 'required|exists:activities,id',
            'form.date' => 'required|date',
            'form.start_time' => 'required',
            'form.end_time' => 'required|after:form.start_time',
            'form.remarks' => 'nullable|string|max:1000',
        ]);

        $activity = Activity::findOrFail($this->form['activity_id']);
        $faculty = Faculty::with('user')->findOrFail($this->form['faculty_id']);

        $request = AvailabilityRequest::create([
            'activity_id' => $activity->id,
            'faculty_id' => $faculty->id,
            'date' => $this->form['date'],
            'start_time' => $this->form['start_time'],
            'end_time' => $this->form['end_time'],
            'status' => AvailabilityRequest::STATUS_PENDING,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'remarks' => $this->form['remarks'] ?: null,
        ]);

        activity()->performedOn($activity)->event('availability_requested')
            ->log("Availability requested for {$faculty->user->name} on {$activity->title}");

        $faculty->user?->notify(new SmartCemesNotification(
            'Availability requested',
            "{$activity->title} · ".$this->form['date'].' '.$this->form['start_time'].'–'.$this->form['end_time'].' — please accept or decline.',
            'clock', 'yellow'
        ));

        $this->showCreate = false;
        $this->dispatch('sc-toast', message: 'Availability request sent to faculty', type: 'success');
    }

    /** Faculty accept: hard-blocked on schedule overlap (8.8). */
    public function accept(int $id): void
    {
        $request = AvailabilityRequest::with('activity')->findOrFail($id);
        $this->authorizeResponse($request);

        if ($request->overlapsFacultySchedule()) {
            $this->dispatch('sc-toast',
                message: 'Accept refused: this schedule overlaps an accepted request or an assigned activity (8.8).',
                type: 'error');

            return;
        }

        $request->update([
            'status' => AvailabilityRequest::STATUS_ACCEPTED,
            'responded_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        activity()->performedOn($request)->event('availability_accepted')
            ->log('Availability accepted by '.auth()->user()->name);

        $request->requester->notify(new SmartCemesNotification(
            'Availability accepted',
            auth()->user()->name." accepted the request for '{$request->activity->title}'.",
            'check', 'green'
        ));

        $this->dispatch('sc-toast', message: 'Availability accepted', type: 'success');
    }

    public function openDecline(int $id): void
    {
        $this->respondingId = $id;
        $this->declineReason = '';
        $this->showDecline = true;
        $this->resetErrorBag();
    }

    /** Faculty decline: reason is REQUIRED (5.8). */
    public function confirmDecline(): void
    {
        $this->validate([
            'declineReason' => 'required|string|min:5|max:1000',
        ]);

        $request = AvailabilityRequest::with('activity')->findOrFail($this->respondingId);
        $this->authorizeResponse($request);

        $request->update([
            'status' => AvailabilityRequest::STATUS_DECLINED,
            'decline_reason' => $this->declineReason,
            'responded_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        activity()->performedOn($request)->event('availability_declined')
            ->log('Availability declined: '.$this->declineReason);

        $request->requester->notify(new SmartCemesNotification(
            'Availability declined',
            auth()->user()->name." declined '{$request->activity->title}' — reason: {$this->declineReason}",
            'clock', 'yellow'
        ));

        $this->reset(['respondingId', 'declineReason']);
        $this->showDecline = false;
        $this->dispatch('sc-toast', message: 'Availability declined — reason recorded', type: 'warn');
    }

    protected function authorizeResponse(AvailabilityRequest $request): void
    {
        $faculty = Faculty::where('user_id', auth()->id())->first();

        abort_unless(
            auth()->user()->isFaculty() && $faculty && $request->faculty_id === $faculty->id,
            403,
            'Only the requested faculty member can respond.'
        );

        abort_unless($request->status === AvailabilityRequest::STATUS_PENDING, 422, 'This request was already answered.');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $faculty = Faculty::where('user_id', $user->id)->first();

        $countsQuery = AvailabilityRequest::query();
        if (! $isAdmin && $faculty) {
            $countsQuery->where('faculty_id', $faculty->id);
        }
        $counts = $countsQuery
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $applyFilters = function ($query) use ($isAdmin, $faculty) {
            if (! $isAdmin && $faculty) {
                $query->where('faculty_id', $faculty->id);
            }

            if ($this->status !== 'all') {
                $query->where('status', $this->status);
            }

            if ($this->search !== '') {
                $search = trim($this->search);
                $query->where(function ($query) use ($search) {
                    $query->whereHas('activity', function ($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%")
                            ->orWhereHas('program', function ($query) use ($search) {
                                $query->where('code', 'like', "%{$search}%")
                                    ->orWhere('title', 'like', "%{$search}%");
                            });
                    })->orWhereHas('faculty.user', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhere('decline_reason', 'like', "%{$search}%");
                });
            }

            return $query;
        };

        $order = function ($query) {
            $column = match ($this->sort) {
                'status' => 'status',
                'requested' => 'requested_at',
                default => 'date',
            };

            return $query->orderBy($column, $this->direction);
        };

        $awaitingQuery = $applyFilters(AvailabilityRequest::with(['activity.program', 'faculty.user'])
            ->where('status', AvailabilityRequest::STATUS_PENDING));
        $respondedQuery = $applyFilters(AvailabilityRequest::with(['activity.program', 'faculty.user'])
            ->whereIn('status', [AvailabilityRequest::STATUS_ACCEPTED, AvailabilityRequest::STATUS_DECLINED]));

        $awaiting = $order($awaitingQuery)->paginate(8, ['*'], 'awaitingPage');
        $responded = $order($respondedQuery)->paginate(8, ['*'], 'respondedPage');

        $myRequests = $faculty
            ? $order($applyFilters(AvailabilityRequest::with(['activity.program', 'requester'])))
                ->paginate(10, ['*'], 'myRequestsPage')
            : collect();

        return view('livewire.availability.index', [
            'isAdmin' => $isAdmin,
            'facultyOptions' => $isAdmin ? Faculty::with('user')->orderBy('id')->get() : collect(),
            'activityOptions' => $isAdmin ? Activity::with('program')->orderBy('planned_start_date')->get() : collect(),
            'awaiting' => $isAdmin ? $awaiting : collect(),
            'responded' => $isAdmin ? $responded : collect(),
            'myRequests' => $myRequests,
            'counts' => $counts,
        ]);
    }
}
