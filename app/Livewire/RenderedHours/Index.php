<?php

namespace App\Livewire\RenderedHours;

use App\Models\RenderedHours;
use App\Notifications\SmartCemesNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showReject = false;

    public ?int $rejectingId = null;

    public string $rejectRemarks = '';

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
        return view('livewire.rendered-hours.index', [
            'queue' => RenderedHours::with(['faculty.user', 'activity.program'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
                ->orderBy('date')
                ->get(),
        ]);
    }
}
