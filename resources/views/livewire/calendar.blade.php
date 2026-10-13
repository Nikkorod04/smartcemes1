<div class="secretary-calendar-page faculty-calendar-page">
<section class="pt-6">
    <div class="reveal-item calendar-toolbar sc-card p-4">
        <div class="calendar-toolbar-main">
            <div class="calendar-month-heading">
                <span class="calendar-month-icon"><x-sc.icon name="calendar" class="h-5 w-5" /></span>
                <div>
                    <p class="calendar-eyebrow">Schedule</p>
                    <h2 class="calendar-month-title">{{ Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h2>
                </div>
            </div>
            <div class="calendar-legend" aria-label="Calendar legend">
                <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--activity"></span>Activity</span>
                <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--availability"></span>Availability</span>
                <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--deadline"></span>Deadline</span>
                <span class="calendar-legend-item"><span class="calendar-legend-dot calendar-legend-dot--conflict"></span>Conflict</span>
            </div>
        </div>
        <div class="calendar-toolbar-actions">
            <div class="calendar-view-switch" role="group" aria-label="Calendar view">
                <button type="button" wire:click="$set('view', 'month')"
                        @class(['calendar-view-button', 'is-active' => $view === 'month'])>Month</button>
                <button type="button" wire:click="$set('view', 'list')"
                        @class(['calendar-view-button', 'is-active' => $view === 'list'])>Next 60 days</button>
            </div>
            <div class="calendar-nav">
                <button type="button" wire:click="prevMonth" title="Previous month" class="calendar-nav-button"><x-sc.icon name="chevron" class="w-4 h-4 rotate-90" /></button>
                <button type="button" wire:click="goToday" class="calendar-today-button">Today</button>
                <button type="button" wire:click="nextMonth" title="Next month" class="calendar-nav-button"><x-sc.icon name="chevron" class="w-4 h-4 -rotate-90" /></button>
            </div>
        </div>
    </div>
</section>

@if ($view === 'month')
<section class="mt-4 grid lg:grid-cols-[1fr_20rem] gap-4 items-start">
    {{-- Month grid --}}
    <div class="reveal-item calendar-month-board min-w-0">
        <div class="calendar-weekdays">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                <p>{{ $d }}</p>
            @endforeach
        </div>
        <div class="calendar-grid">
            @foreach ($cells as $cell)
                @php($dayStr = $cell->format('Y-m-d'))
                @php($inMonth = $cell->month === $month && $cell->year === $year)
                @php($isToday = $dayStr === now()->format('Y-m-d'))
                @php($isSel = $dayStr === $selectedDate)
                @php($cellEvents = $inMonth ? $events->where('date', $dayStr)->values() : collect())
                <button type="button" wire:click="selectDay('{{ $dayStr }}')" wire:key="cal-cell-{{ $dayStr }}"
                        title="{{ $cell->format('l, F j, Y') }} — click to view"
                        @class([
                            'calendar-cell',
                            'is-today' => $isToday,
                            'is-selected' => $isSel,
                            'is-outside' => ! $inMonth,
                        ])>
                    <span @class([
                            'calendar-day-number',
                            'is-today' => $isToday,
                            'is-outside' => ! $inMonth,
                        ])>{{ $cell->format('j') }}</span>
                    <span class="calendar-cell-events">
                        @foreach ($cellEvents->take(2) as $e)
                            <span @class([
                                    'calendar-event-pill',
                                    'is-conflict' => $e['conflict'],
                                    'is-activity' => ! $e['conflict'] && $e['type'] === 'activity',
                                    'is-availability' => ! $e['conflict'] && $e['type'] === 'availability',
                                    'is-deadline' => ! $e['conflict'] && $e['type'] === 'deadline',
                                ]) title="{{ $e['title'] }}{{ $e['conflict'] ? ' — CONFLICT' : '' }}">
                                @if ($e['conflict'])<x-sc.icon name="alert" class="w-3 h-3 shrink-0" /> @endif<span class="truncate">{{ $e['title'] }}</span>
                            </span>
                        @endforeach
                        @if ($cellEvents->count() > 2)
                            <span class="calendar-more-events">+{{ $cellEvents->count() - 2 }} more</span>
                        @endif
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Day panel --}}
    <aside class="reveal-item calendar-day-panel-wrap w-full lg:w-80 lg:sticky lg:top-24">
        <div class="calendar-day-panel sc-card p-5">
            <div class="calendar-day-panel-head">
                <div>
                    <p class="calendar-eyebrow">Selected day</p>
                    <h3 class="calendar-day-title">{{ Carbon\Carbon::parse($selectedDate)->format('l, M j, Y') }}</h3>
                </div>
                <span class="calendar-event-count {{ $dayEvents->count() ? 'has-events' : '' }}">{{ $dayEvents->count() ?: 'No' }} event{{ $dayEvents->count() === 1 ? '' : 's' }}</span>
            </div>
            @forelse ($dayEvents as $e)
                <ul class="calendar-day-events" wire:key="cal-day-{{ $e['type'] }}-{{ $loop->index }}">
                    <li class="calendar-event-card {{ $e['conflict'] ? 'is-conflict' : '' }}">
                        <span class="calendar-event-dot {{ $e['conflict'] ? 'is-conflict' : match ($e['type']) { 'activity' => 'is-activity', 'availability' => 'is-availability', default => 'is-deadline' } }}"></span>
                        <div class="calendar-event-body">
                            <p class="calendar-event-title">
                                {{ $e['title'] }}
                                @if ($e['mine'])<span class="badge badge-gold !text-[9.5px] !px-2 !py-0.5">(You)</span>@endif
                                @if ($e['conflict'])<span class="conflict-marker">conflict</span>@endif
                            </p>
                            <p class="calendar-event-meta">
                                @if ($e['type'] === 'activity')
                                    {{ \Illuminate\Support\Str::before($e['model']->program?->title ?? 'Activity', ':') }} · {{ $e['model']->venue ?? ucfirst($e['status']) }}
                                @elseif ($e['type'] === 'availability')
                                    Accepted availability · Director's Office
                                @else
                                    {{ \Illuminate\Support\Str::before($e['model']['program']->title ?? 'Program', ':') }} · objective target date
                                @endif
                            </p>
                            <div class="calendar-event-actions">
                                @if ($e['time'])
                                    <span class="calendar-time-chip"><x-sc.icon name="clock" class="w-3 h-3" /> {{ $e['time'] }}</span>
                                @endif
                                @if ($e['type'] === 'deadline')
                                    <a href="{{ route('projects.show', $e['model']['program']) }}" class="calendar-event-link">Open deadline <span aria-hidden="true">→</span></a>
                                @elseif ($e['type'] === 'availability')
                                    <a href="{{ route('availability.index') }}" class="calendar-event-link">Open availability <span aria-hidden="true">→</span></a>
                                @else
                                    <a href="{{ route('projects.show', $e['model']->program) }}" class="calendar-event-link">Open activity <span aria-hidden="true">→</span></a>
                                @endif
                            </div>
                        </div>
                    </li>
                </ul>
            @empty
                <div class="calendar-empty-day">
                    <span class="calendar-empty-icon"><x-sc.icon name="check" class="w-5 h-5" /></span>
                    <p class="calendar-empty-title">Nothing scheduled</p>
                    <p class="calendar-empty-note">Enjoy the breather — no activities or deadlines on this day.</p>
                </div>
            @endforelse
        </div>
    </aside>
</section>
@else
{{-- 60-day grouped list --}}
<section class="mt-4 calendar-list-view space-y-3">
    @forelse ($next60->groupBy('date') as $dateKey => $dayGroup)
        @php($isToday = $dateKey === now()->format('Y-m-d'))
        <div class="reveal-item calendar-list-group sc-card p-0 overflow-hidden" wire:key="cal60-group-{{ $dateKey }}">
            <div class="calendar-list-date {{ $isToday ? 'is-today' : '' }}">
                <div>
                    <p class="calendar-eyebrow">{{ Carbon\Carbon::parse($dateKey)->format('l') }}</p>
                    <p class="calendar-list-date-title">{{ Carbon\Carbon::parse($dateKey)->format('F j, Y') }}</p>
                </div>
                @if ($isToday)<span class="calendar-event-count has-events">today</span>@endif
            </div>
            <ul class="calendar-list-events px-4">
                @foreach ($dayGroup as $e)
                    <li class="calendar-list-event {{ $e['conflict'] ? 'is-conflict' : '' }}" wire:key="cal60-{{ $e['type'] }}-{{ $dateKey }}-{{ $loop->index }}">
                        <span class="calendar-event-dot {{ $e['conflict'] ? 'is-conflict' : match ($e['type']) { 'activity' => 'is-activity', 'availability' => 'is-availability', default => 'is-deadline' } }}"></span>
                        <div class="calendar-event-body">
                            <p class="calendar-event-title truncate">
                                {{ $e['title'] }}
                                @if ($e['mine'])<span class="badge badge-gold !text-[9.5px] !px-2 !py-0.5">(You)</span>@endif
                                @if ($e['conflict'])<span class="conflict-marker">conflict</span>@endif
                            </p>
                            <p class="calendar-event-meta">
                                @if ($e['type'] === 'activity')
                                    {{ \Illuminate\Support\Str::before($e['model']->program?->title ?? 'Activity', ':') }} · {{ $e['model']->venue ?? ucfirst($e['status']) }} · {{ $e['time'] }}
                                @elseif ($e['type'] === 'availability')
                                    Accepted availability · Director's Office · {{ $e['time'] }}
                                @else
                                    {{ \Illuminate\Support\Str::before($e['model']['program']->title ?? 'Program', ':') }} · objective target date
                                @endif
                            </p>
                        </div>
                        <a href="{{ $e['type'] === 'availability' ? route('availability.index') : route('projects.show', $e['model']['program'] ?? $e['model']->program) }}"
                           class="calendar-event-link shrink-0">Open <span aria-hidden="true">→</span></a>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="calendar-empty-list sc-card p-10 text-center">
            <span class="calendar-empty-icon"><x-sc.icon name="calendar" class="w-5 h-5" /></span>
            <p class="calendar-empty-title">Nothing scheduled in the next 60 days</p>
            <p class="calendar-empty-note">Accept an availability request or check the month grid.</p>
        </div>
    @endforelse
</section>
@endif

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
