{{-- D-G1: read-only Gantt timeline of the project's activities.
     Framed by the project's own planned window so an activity that runs past
     the project end visually overflows the frame (chevron marker). Bars are
     status-colored, "today" gets a marker line, and managers open the
     existing activity form by clicking a bar. The sc-table grid in
     hub-activities.blade.php remains the data-dense default view. --}}
@php
    use Illuminate\Support\Carbon;

    $windowStart = $program->planned_start_date->copy()->startOfDay();
    $windowEnd = $program->planned_end_date->copy()->startOfDay();
    $windowDays = max(1, (int) $windowStart->diff($windowEnd)->days + 1);
    $today = now()->startOfDay();
    $showToday = $today->greaterThanOrEqualTo($windowStart) && $today->lessThanOrEqualTo($windowEnd);

    // Signed day offset from the window start — version-safe across Carbon 2/3
    // (diffInDays changed sign semantics between them).
    $dayIndex = function (Carbon $date) use ($windowStart) {
        return $date->greaterThanOrEqualTo($windowStart)
            ? $windowStart->diff($date)->days
            : -$windowStart->diff($date)->days;
    };

    // Bar edges as a percentage of the window, clamped to the frame. The right
    // edge uses "end + 1 day" so the planned end date is covered inclusively.
    $pctStart = function (Carbon $date) use ($windowDays, $dayIndex) {
        return max(0.0, min(100.0, $dayIndex($date) / $windowDays * 100));
    };
    $pctEnd = function (Carbon $date) use ($windowDays, $dayIndex) {
        return max(0.0, min(100.0, ($dayIndex($date) + 1) / $windowDays * 100));
    };

    // Header columns carry their TRUE day span, so even-length month/week
    // labels stay pixel-aligned with the proportional bars above them.
    $columns = [];
    if ($ganttScale === 'week') {
        $cursor = $windowStart->copy()->startOfWeek();
        while ($cursor->lessThan($windowEnd)) {
            $colStart = $cursor->greaterThan($windowStart) ? $cursor->copy() : $windowStart->copy();
            $cursor->addWeek();
            $colEnd = $cursor->lessThanOrEqualTo($windowEnd) ? $cursor->copy()->subDay() : $windowEnd->copy();
            $columns[] = ['label' => $colStart->format('M j'), 'days' => max(1, (int) $colStart->diff($colEnd)->days + 1)];
        }
    } else {
        $cursor = $windowStart->copy()->startOfMonth();
        while ($cursor->lessThan($windowEnd)) {
            $colStart = $cursor->greaterThan($windowStart) ? $cursor->copy() : $windowStart->copy();
            $cursor->addMonth();
            $colEnd = $cursor->lessThanOrEqualTo($windowEnd) ? $cursor->copy()->subDay() : $windowEnd->copy();
            $columns[] = ['label' => $colStart->format('M Y'), 'days' => max(1, (int) $colStart->diff($colEnd)->days + 1)];
        }
    }

    // Bar color by status; a planned activity that blew past its end date is
    // DELAYED, the one state the hub has no other surface for.
    $barClass = function ($activity) use ($today) {
        if ($activity->status === 'completed') return ['bg-emerald-500', 'Completed'];
        if ($activity->status === 'cancelled') return ['bg-gray-300', 'Cancelled'];
        if ($activity->status !== 'completed' && $activity->planned_end_date->startOfDay()->lessThan($today)) return ['bg-red-500', 'Delayed'];
        if ($activity->status === 'ongoing') return ['bg-lnu-600', 'Ongoing'];
        return ['bg-blue-300', ucfirst($activity->status)];
    };
@endphp

<div class="px-5 py-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-400 font-medium">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Completed</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-lnu-600"></span>Ongoing</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-300"></span>Planned</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Delayed</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>Cancelled</span>
            @if ($showToday)
                <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-px bg-red-400"></span>Today</span>
            @endif
        </div>
        <div class="flex overflow-hidden rounded-lg border border-gray-200 bg-gray-50/60 p-0.5">
            <button type="button" wire:click="setGanttScale('month')" @class(['px-2.5 py-1 text-[11px] font-bold rounded-md transition', $ganttScale === 'month' ? 'bg-white text-charcoal shadow-sm' : 'text-gray-400 hover:text-gray-600'])>Month</button>
            <button type="button" wire:click="setGanttScale('week')" @class(['px-2.5 py-1 text-[11px] font-bold rounded-md transition', $ganttScale === 'week' ? 'bg-white text-charcoal shadow-sm' : 'text-gray-400 hover:text-gray-600'])>Week</button>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        <div class="min-w-[720px]">
            {{-- Header row: label gutter + day-proportional columns. --}}
            <div class="flex items-end border-b border-gray-100 pb-1.5">
                <div class="w-56 shrink-0 pr-3 text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Activity</div>
                <div class="flex min-w-0 flex-1">
                    @foreach ($columns as $column)
                        <div class="shrink-0 border-l border-gray-100 pl-1.5 text-[10.5px] font-bold text-gray-400" style="width: {{ $column['days'] / $windowDays * 100 }}%">{{ $column['label'] }}</div>
                    @endforeach
                </div>
            </div>

            @forelse ($activities as $a)
                @php
                    [$class, $stateLabel] = $barClass($a);
                    $left = $pctStart($a->planned_start_date->copy()->startOfDay());
                    $right = $pctEnd($a->planned_end_date->copy()->startOfDay());
                    $overrunsRight = $a->planned_end_date->startOfDay()->greaterThan($windowEnd);
                    $startsBeforeWindow = $a->planned_start_date->startOfDay()->lessThan($windowStart);
                    $assigned = $a->faculty->map(fn ($f) => $f->user->name)->all();
                @endphp
                <div class="group flex items-center border-b border-gray-50 py-2 transition hover:bg-blue-50/30" wire:key="gantt-{{ $a->id }}">
                    <div class="w-56 shrink-0 pr-3">
                        <p class="truncate text-[12px] font-semibold text-charcoal" title="{{ $a->title }}">{{ $a->title }}</p>
                        <p class="truncate text-[10.5px] text-gray-400">{{ $a->planned_start_date->format('M j') }} – {{ $a->planned_end_date->format('M j, Y') }}@if ($assigned) · {{ implode(', ', $assigned) }}@endif</p>
                    </div>
                    <div class="relative h-7 min-w-0 flex-1">
                        @if ($showToday)
                            <span class="absolute bottom-0 top-0 z-10 w-px bg-red-400/70" style="left: {{ ($dayIndex($today) + 0.5) / $windowDays * 100 }}%"></span>
                        @endif
                        @if ($canManage)
                            <button type="button" wire:click="openActivityForm({{ $a->id }})"
                                    class="absolute inset-y-1 rounded-full {{ $class }} opacity-80 transition hover:opacity-100 hover:shadow-sm"
                                    style="left: {{ $left }}%; width: {{ max(0.75, $right - $left) }}%"
                                    title="{{ $a->title }} · {{ $stateLabel }} · {{ $a->planned_start_date->format('M j, Y') }} – {{ $a->planned_end_date->format('M j, Y') }}{{ $a->start_time ? ' · '.$a->start_time->format('g:i A') : '' }}"
                                    aria-label="Edit {{ $a->title }}">
                                @if ($overrunsRight)
                                    <span class="absolute -right-1 top-1/2 -translate-y-1/2 rounded-full bg-charcoal p-0.5 text-white"><x-sc.icon name="chevron-right" class="h-2.5 w-2.5" /></span>
                                @endif
                                @if ($startsBeforeWindow)
                                    <span class="absolute -left-1 top-1/2 -translate-y-1/2 rounded-full bg-charcoal p-0.5 text-white"><x-sc.icon name="chevron-left" class="h-2.5 w-2.5" /></span>
                                @endif
                            </button>
                        @else
                            <span class="absolute inset-y-1 rounded-full {{ $class }} opacity-80"
                                  style="left: {{ $left }}%; width: {{ max(0.75, $right - $left) }}%"
                                  title="{{ $a->title }} · {{ $stateLabel }} · {{ $a->planned_start_date->format('M j, Y') }} – {{ $a->planned_end_date->format('M j, Y') }}">
                                @if ($overrunsRight)
                                    <span class="absolute -right-1 top-1/2 -translate-y-1/2 rounded-full bg-charcoal p-0.5 text-white"><x-sc.icon name="chevron-right" class="h-2.5 w-2.5" /></span>
                                @endif
                                @if ($startsBeforeWindow)
                                    <span class="absolute -left-1 top-1/2 -translate-y-1/2 rounded-full bg-charcoal p-0.5 text-white"><x-sc.icon name="chevron-left" class="h-2.5 w-2.5" /></span>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-gray-400">
                    <p class="text-[12.5px] font-medium">No activities yet for this project.</p>
                    @if ($canManage)
                        <button type="button" wire:click="openActivityForm(null)" class="mt-2 text-[11.5px] font-bold text-lnu-700 hover:text-lnu-900 transition">Add the first activity</button>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>
