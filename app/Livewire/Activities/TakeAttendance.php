<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tablet-first attendance roster for one activity.
 *
 * The XLSX import is still the bulk/legacy path, but the normal case is a staff
 * member with the enrolled roster in hand: mark everyone present, correct the
 * absences, save. This writes the same Attendance rows the import writes, using
 * the activity date, so training-hours figures and the records counts stay
 * consistent.
 */
#[Layout('layouts.app')]
class TakeAttendance extends Component
{
    public int $activityId;

    public string $search = '';

    /** @var array<int, string> beneficiary id => status (or empty) */
    public array $statuses = [];

    public function mount(Activity $activity): void
    {
        $this->guard();

        $activity->loadMissing('program');

        abort_unless(auth()->user()->can('view', $activity->program), 403);

        $this->activityId = $activity->id;
        $this->loadStatuses();
    }

    public function toggleStatus(int $beneficiaryId, string $status): void
    {
        $this->guard();

        abort_unless(array_key_exists($beneficiaryId, $this->statuses), 404);
        abort_unless(in_array($status, config('smartcemes.statuses.attendance'), true), 422);

        $this->statuses[$beneficiaryId] = $this->statuses[$beneficiaryId] === $status ? '' : $status;
    }

    public function markAllPresent(): void
    {
        $this->guard();

        foreach (array_keys($this->statuses) as $beneficiaryId) {
            $this->statuses[$beneficiaryId] = 'present';
        }
    }

    public function clearAll(): void
    {
        $this->guard();

        foreach (array_keys($this->statuses) as $beneficiaryId) {
            $this->statuses[$beneficiaryId] = '';
        }
    }

    public function save(): void
    {
        $this->guard();

        $this->validate([
            'statuses.*' => 'nullable|in:'.implode(',', config('smartcemes.statuses.attendance')),
        ]);

        $activity = $this->activity();
        $date = $activity->planned_start_date->toDateTimeString();
        $applied = 0;

        DB::transaction(function () use ($activity, $date, &$applied) {
            foreach ($this->statuses as $beneficiaryId => $status) {
                $status = $status !== '' ? $status : null;

                if ($status === null) {
                    $activity->attendances()
                        ->where('beneficiary_id', $beneficiaryId)
                        ->where('attendance_date', $date)
                        ->delete();

                    continue;
                }

                $activity->attendances()->updateOrCreate(
                    ['beneficiary_id' => $beneficiaryId, 'attendance_date' => $date],
                    ['status' => $status]
                );

                $applied++;
            }
        });

        activity()->performedOn($activity)->event('attendance_recorded')
            ->log("Attendance recorded for '{$activity->title}' — {$applied} record".($applied === 1 ? '' : 's'));

        $this->dispatch(
            'sc-toast',
            message: 'Attendance saved — '.$applied.' record'.($applied === 1 ? '' : 's'),
            type: 'success',
        );
    }

    public function render()
    {
        $activity = $this->activity();
        $project = $activity->program;

        $query = $project->beneficiaries()
            ->whereNull('beneficiaries.deleted_at')
            ->orderBy('beneficiaries.last_name')
            ->orderBy('beneficiaries.first_name');

        if (trim($this->search) !== '') {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->search)).'%';
            $query->where(function ($query) use ($term) {
                $query->where('beneficiaries.first_name', 'like', $term)
                    ->orWhere('beneficiaries.middle_name', 'like', $term)
                    ->orWhere('beneficiaries.last_name', 'like', $term)
                    ->orWhere('beneficiaries.barangay', 'like', $term);
            });
        }

        $counts = array_fill_keys(config('smartcemes.statuses.attendance'), 0);
        $counts['unrecorded'] = 0;

        foreach ($this->statuses as $status) {
            if ($status === '' || ! array_key_exists($status, $counts)) {
                $counts['unrecorded']++;
            } else {
                $counts[$status]++;
            }
        }

        return view('livewire.activities.take-attendance', [
            'activity' => $activity,
            'project' => $project,
            'beneficiaries' => $query->get(),
            'counts' => $counts,
            'statusOptions' => config('smartcemes.statuses.attendance'),
            'enrolledTotal' => count($this->statuses),
        ]);
    }

    private function activity(): Activity
    {
        return Activity::with('program')->findOrFail($this->activityId);
    }

    private function loadStatuses(): void
    {
        $activity = $this->activity();
        $date = $activity->planned_start_date->toDateTimeString();

        $existing = $activity->attendances()
            ->where('attendance_date', $date)
            ->pluck('status', 'beneficiary_id')
            ->all();

        $this->statuses = [];

        $activity->program->beneficiaries()
            ->whereNull('beneficiaries.deleted_at')
            ->orderBy('beneficiaries.last_name')
            ->orderBy('beneficiaries.first_name')
            ->select('beneficiaries.id')
            ->each(function (Beneficiary $beneficiary) use ($existing) {
                $this->statuses[$beneficiary->id] = $existing[$beneficiary->id] ?? '';
            });
    }

    private function guard(): void
    {
        abort_unless(
            in_array(auth()->user()?->role, [User::ROLE_ADMIN, User::ROLE_SECRETARY], true),
            403,
        );
    }
}
