<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\AvailabilityRequest;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Calendar extends Component
{
    public int $year;

    public int $month;

    public ?string $selectedDate = null;

    public string $view = 'month';

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function goToday(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
        $this->selectedDate = now()->format('Y-m-d');
        $this->view = 'month';
    }

    public function prevMonth(): void
    {
        $this->month === 1 ? ($this->month = 12) : $this->month--;
        if ($this->month === 12) {
            $this->year--;
        }
    }

    public function nextMonth(): void
    {
        $this->month === 12 ? ($this->month = 1) : $this->month++;
        if ($this->month === 1) {
            $this->year++;
        }
    }

    public function selectDay(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function render()
    {
        $user = auth()->user();
        $faculty = Faculty::where('user_id', $user->id)->first();

        // Feed 1: activities. Faculty sees only their own, with a "(You)" marker (5.9).
        $activitiesQuery = Activity::query()->with(['program', 'faculty.user'])->whereNotIn('status', ['cancelled']);
        if ($user->isFaculty() && $faculty) {
            $activitiesQuery->whereHas('faculty', fn ($q) => $q->where('faculty_id', $faculty->id));
        }
        $activities = $activitiesQuery->get();

        // Feed 2: accepted availability requests.
        $availabilityQuery = AvailabilityRequest::query()->with(['activity', 'faculty.user'])->where('status', 'accepted');
        if ($user->isFaculty() && $faculty) {
            $availabilityQuery->where('faculty_id', $faculty->id);
        }
        $availability = $availabilityQuery->get();

        // Feed 3: program/objective deadlines.
        $deadlines = collect();
        foreach ($this->allVisiblePrograms($user) as $program) {
            foreach ($program->programObjectives as $objective) {
                if ($objective->target_date) {
                    $deadlines->push(['date' => $objective->target_date->format('Y-m-d'), 'program' => $program, 'objective' => $objective]);
                }
            }
            $deadlines->push(['date' => $program->planned_end_date->format('Y-m-d'), 'program' => $program, 'objective' => null]);
        }

        // 8.8 conflict computation: activities that overlap another activity
        // sharing an assigned faculty member.
        $conflictingIds = $this->conflictingActivityIds();

        $events = collect();
        foreach ($activities as $a) {
            $events->push([
                'type' => 'activity',
                'date' => $a->planned_start_date->format('Y-m-d'),
                'title' => $a->title,
                'mine' => $a->faculty->pluck('user_id')->contains($user->id),
                'conflict' => $conflictingIds->contains($a->id),
                'status' => $a->status,
                'time' => $a->start_time->format('g:i A').' – '.$a->end_time->format('g:i A'),
                'sortTime' => $a->start_time->format('H:i'),
                'model' => $a,
            ]);
        }
        foreach ($availability as $r) {
            $events->push([
                'type' => 'availability',
                'date' => $r->date->format('Y-m-d'),
                'title' => $r->activity?->title ?? 'Availability',
                'mine' => $r->faculty->user_id === $user->id,
                'conflict' => false,
                'status' => 'accepted',
                'time' => Carbon::parse($r->start_time)->format('g:i A').' – '.Carbon::parse($r->end_time)->format('g:i A'),
                'sortTime' => Carbon::parse($r->start_time)->format('H:i'),
                'model' => $r,
            ]);
        }
        foreach ($deadlines as $d) {
            $events->push([
                'type' => 'deadline',
                'date' => $d['date'],
                'title' => $d['objective'] ? Str::limit($d['objective']->objective, 40) : $d['program']->title.' ends',
                'mine' => false,
                'conflict' => false,
                'status' => 'deadline',
                'time' => null,
                'sortTime' => '23:59',
                'model' => $d,
            ]);
        }

        // Month grid geometry (Sun-first grid per prototype; adjacent-month
        // days rendered as real dates so every visible cell is clickable).
        $first = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $cells = collect($first->dayOfWeek > 0 ? range($first->dayOfWeek, 1, -1) : [])
            ->map(fn ($i) => $first->copy()->subDays($i))
            ->merge(collect(range(1, $first->daysInMonth))->map(fn ($d) => $first->copy()->day($d)));
        while ($cells->count() % 7 !== 0) {
            $cells->push($cells->last()->copy()->addDay());
        }

        $next60 = $events->filter(fn ($e) => $e['date'] >= now()->format('Y-m-d') && $e['date'] <= now()->addDays(60)->format('Y-m-d'))
            ->sortBy([['date', 'asc'], ['sortTime', 'asc']])->values();

        $selected = $this->selectedDate;
        $dayEvents = $events->where('date', $this->selectedDate)->sortBy('sortTime');

        return view('livewire.calendar', [
            'cells' => $cells,
            'events' => $events,
            'conflictingIds' => $conflictingIds,
            'dayEvents' => $dayEvents,
            'selectedDate' => $selected,
            'next60' => $next60,
            'isFaculty' => auth()->user()->isFaculty(),
        ]);
    }

    protected function allVisiblePrograms($user)
    {
        if (! $user->isFaculty()) {
            return ExtensionProgram::with('programObjectives')->get();
        }

        $faculty = Faculty::where('user_id', $user->id)->first();
        if (! $faculty) {
            return collect();
        }

        return ExtensionProgram::query()
            ->where(fn ($q) => $q
                ->where('program_lead_id', $faculty->id)
                ->orWhereHas('activities', fn ($w) => $w->whereHas('faculty', fn ($f) => $f->where('faculty_id', $faculty->id))))
            ->with('programObjectives')
            ->get();
    }

    protected function conflictingActivityIds()
    {
        $ids = collect();
        $activities = Activity::with('faculty')->whereNotIn('status', ['cancelled'])->get();

        foreach ($activities as $a) {
            foreach ($a->faculty as $f) {
                $overlap = $f->activities()
                    ->whereKeyNot($a->id)
                    ->whereNotIn('status', ['cancelled'])
                    ->get()
                    ->first(fn ($other) => $a->overlaps($other));
                if ($overlap) {
                    $ids->push($a->id);
                    break;
                }
            }
        }

        return $ids->unique();
    }
}
