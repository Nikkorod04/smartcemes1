<?php

namespace App\Livewire\RenderedHours;

use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Models\User;
use App\Notifications\SmartCemesNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class My extends Component
{
    /** Faculty adjusts hours DOWN only, with a note (8.9). */
    public ?int $editingId = null;

    public string $adjustedHours = '';

    public string $adjustNote = '';

    public function startAdjust(int $id): void
    {
        $entry = $this->myEntry($id);
        abort_if($entry->isLocked(), 422, 'Approved entries are locked and immutable.');

        $this->editingId = $id;
        $this->adjustedHours = (string) $entry->hours;
        $this->adjustNote = '';
        $this->resetErrorBag();
    }

    public function saveAdjust(): void
    {
        $entry = $this->myEntry($this->editingId);
        abort_if($entry->isLocked(), 422, 'Approved entries are locked and immutable.');

        $this->validate([
            'adjustedHours' => 'required|numeric|min:0',
            'adjustNote' => 'required|string|min:5|max:1000',
        ]);

        $max = (float) $entry->hours;
        $new = (float) $this->adjustedHours;

        // 8.9: adjust DOWN only (partial participation), never above the
        // auto-computed duration, and always with a note.
        if ($new > $max + 0.001) {
            $this->addError('adjustedHours', "Hours may only be adjusted DOWN (max {$max}).");

            return;
        }

        $entry->update([
            'hours' => $new,
            'source' => RenderedHours::SOURCE_MANUAL,
            'remarks' => $entry->remarks.' | Adjusted from '.number_format($max, 2).' to '.number_format($new, 2).' hrs: '.$this->adjustNote,
        ]);

        activity()->performedOn($entry)->event('adjusted')
            ->log("Rendered hours adjusted {$max} → {$new} hrs: {$this->adjustNote}");

        $this->reset(['editingId', 'adjustedHours', 'adjustNote']);
        $this->dispatch('sc-toast', message: 'Hours adjusted down', type: 'success');
    }

    /** Faculty submits the draft -> pending (8.9). */
    public function submit(int $id): void
    {
        $entry = $this->myEntry($id);
        abort_if($entry->status === RenderedHours::STATUS_APPROVED, 422, 'Approved entries are locked.');

        $entry->update([
            'status' => RenderedHours::STATUS_PENDING,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        activity()->performedOn($entry)->event('submitted')
            ->log("Rendered hours submitted: {$entry->hours} hrs for '{$entry->activity->title}'");

        User::where('role', User::ROLE_ADMIN)->each(fn ($admin) => $admin->notify(
            new SmartCemesNotification(
                'Rendered hours submitted',
                auth()->user()->name." — {$entry->hours} hrs ('{$entry->activity->title}') awaiting approval.",
                'clock', 'yellow'
            )
        ));

        $this->dispatch('sc-toast', message: 'Rendered hours submitted for approval', type: 'success');
    }

    protected function myEntry(int $id): RenderedHours
    {
        $faculty = Faculty::where('user_id', auth()->id())->firstOrFail();

        return RenderedHours::with('activity')->where('faculty_id', $faculty->id)->findOrFail($id);
    }

    public function render()
    {
        $faculty = Faculty::where('user_id', auth()->id())->first();

        $entries = $faculty
            ? $faculty->renderedHours()->with(['activity.program'])->orderByDesc('date')->get()
            : collect();

        return view('livewire.rendered-hours.my', [
            'entries' => $entries,
            'approvedTotal' => $entries->where('status', 'approved')->sum('hours'),
        ]);
    }
}
