<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">
        {{ $isFaculty ? 'Your extension schedule — items marked "You" involve you directly' : 'Office-wide schedule of activities, accepted availability, deadlines, and conflict alerts' }}
    </p>
    <div class="reveal-item flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-4">
            <h2 class="font-extrabold text-[17px] tracking-tight">{{ Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h2>
            <div class="flex flex-wrap items-center gap-3 text-[11.5px] font-semibold">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-lnu-600"></span>Activity</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gold-500"></span>Availability</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border-2 border-red-500 bg-white"></span>Deadline</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Conflict</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-gray-100 rounded-xl p-1">
                <button type="button" wire:click="$set('view', 'month')"
                        @class(['w-max px-3 h-8 rounded-lg flex items-center justify-center text-[11.5px] font-bold transition',
                                'bg-white shadow-sm text-lnu-800' => $view === 'month',
                                'text-gray-400 hover:text-gray-600' => $view !== 'month'])>Month</button>
                <button type="button" wire:click="$set('view', 'list')"
                        @class(['w-max px-3 h-8 rounded-lg flex items-center justify-center text-[11.5px] font-bold transition',
                                'bg-white shadow-sm text-lnu-800' => $view === 'list',
                                'text-gray-400 hover:text-gray-600' => $view !== 'list'])>Next 60 days</button>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" wire:click="prevMonth" title="Previous month" class="btn btn-ghost !p-2 rounded-xl"><x-sc.icon name="chevron" class="w-4 h-4 rotate-90" /></button>
                <button type="button" wire:click="goToday" class="btn btn-outline !px-3 !py-1.5 text-[11.5px]">Today</button>
                <button type="button" wire:click="nextMonth" title="Next month" class="btn btn-ghost !p-2 rounded-xl"><x-sc.icon name="chevron" class="w-4 h-4 -rotate-90" /></button>
            </div>
        </div>
    </div>
</section>

@if ($view === 'month')
<section class="mt-4 grid lg:grid-cols-[1fr_20rem] gap-4 items-start">
    {{-- Month grid --}}
    <div class="reveal-item min-w-0">
        <div class="grid grid-cols-7 gap-2 mb-2">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                <p class="text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ $d }}</p>
            @endforeach
        </div>
        <div class="grid grid-cols-7 gap-2">
            @foreach ($cells as $cell)
                @php($dayStr = $cell->format('Y-m-d'))
                @php($inMonth = $cell->month === $month && $cell->year === $year)
                @php($isToday = $dayStr === now()->format('Y-m-d'))
                @php($isSel = $dayStr === $selectedDate)
                @php($cellEvents = $inMonth ? $events->where('date', $dayStr)->values() : collect())
                <button type="button" wire:click="selectDay('{{ $dayStr }}')" wire:key="cal-cell-{{ $dayStr }}"
                        title="{{ $cell->format('l, F j, Y') }} — click to view"
                        @class([
                            'text-left min-h-[96px] rounded-lg border p-2 transition cursor-pointer overflow-hidden',
                            $isToday ? 'ring-2 ring-gold-500 border-gold-200 bg-white hover:bg-lnu-50' : 'border-gray-200',
                            $isSel ? '!bg-lnu-50 !border-lnu-300' : '',
                            $inMonth ? 'bg-white hover:bg-lnu-50' : 'bg-gray-50/60 hover:bg-gray-100',
                        ])>
                    <span @class([
                            'inline-flex w-6 h-6 rounded-full items-center justify-center text-[12px]',
                            $isToday ? 'bg-gold-500 text-charcoal font-bold' : '',
                            ! $isToday && $inMonth ? 'font-semibold text-charcoal' : '',
                            ! $inMonth ? 'text-gray-300' : '',
                        ])>{{ $cell->format('j') }}</span>
                    <span class="block mt-1 space-y-1">
                        @foreach ($cellEvents->take(2) as $e)
                            <span @class([
                                    'block truncate text-[10px] font-semibold px-1.5 py-[3px] rounded-md',
                                    $e['conflict'] ? '!border !border-red-300 !text-red-600' : match ($e['type']) {
                                        'activity' => 'bg-lnu-50 text-lnu-700',
                                        'availability' => 'bg-gold-50 text-gold-800',
                                        default => 'bg-red-50 !border !border-red-200 text-red-600',
                                    },
                                ]) title="{{ $e['title'] }}{{ $e['conflict'] ? ' — CONFLICT' : '' }}">
                                @if ($e['conflict'])<x-sc.icon name="alert" class="w-3 h-3" /> @endif{{ $e['title'] }}
                            </span>
                        @endforeach
                        @if ($cellEvents->count() > 2)
                            <span class="block text-[10px] font-semibold text-gray-400 pl-1">+{{ $cellEvents->count() - 2 }} more</span>
                        @endif
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Day panel --}}
    <aside class="reveal-item w-full lg:w-80 lg:sticky lg:top-24">
        <div class="sc-card p-5">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-gray-100">
                <div>
                    <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">{{ Carbon\Carbon::parse($selectedDate)->format('l') }}</p>
                    <h3 class="font-extrabold text-[15px] tracking-tight">{{ Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}</h3>
                </div>
                <span class="badge {{ $dayEvents->count() ? 'badge-blue' : 'badge-gray' }}">{{ $dayEvents->count() ?: 'No' }} event{{ $dayEvents->count() === 1 ? '' : 's' }}</span>
            </div>
            @forelse ($dayEvents as $e)
                <ul class="mt-3 space-y-2.5" wire:key="cal-day-{{ $e['type'] }}-{{ $loop->index }}">
                    <li class="flex gap-3 p-3 rounded-xl border {{ $e['conflict'] ? 'border-red-200 bg-red-50/40' : 'border-gray-100 hover:border-lnu-200 hover:bg-lnu-50/40 transition' }}">
                        <span class="mt-1 w-2.5 h-2.5 rounded-full shrink-0 {{ $e['conflict'] ? 'bg-red-500' : match ($e['type']) { 'activity' => 'bg-lnu-600', 'availability' => 'bg-gold-500', default => 'bg-red-500' } }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12.5px] font-bold leading-snug">
                                {{ $e['title'] }}
                                @if ($e['mine'])<span class="badge badge-gold !text-[9.5px] !px-2 !py-0.5">(You)</span>@endif
                                @if ($e['conflict'])<span class="conflict-marker">conflict</span>@endif
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                @if ($e['type'] === 'activity')
                                    {{ \Illuminate\Support\Str::before($e['model']->program?->title ?? 'Activity', ':') }} · {{ $e['model']->venue ?? ucfirst($e['status']) }}
                                @elseif ($e['type'] === 'availability')
                                    Accepted availability · Director's Office
                                @else
                                    {{ \Illuminate\Support\Str::before($e['model']['program']->title ?? 'Program', ':') }} · objective target date
                                @endif
                            </p>
                            <div class="mt-1.5 flex items-center gap-2.5 flex-wrap">
                                @if ($e['time'])
                                    <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md"><x-sc.icon name="clock" class="w-3 h-3" /> {{ $e['time'] }}</span>
                                @endif
                                @if ($e['type'] === 'deadline')
                                    <a href="{{ route('projects.show', $e['model']['program']) }}" class="text-[11px] font-bold text-lnu-800 hover:text-lnu-600 transition">Open deadline →</a>
                                @elseif ($e['type'] === 'availability')
                                    <a href="{{ route('availability.index') }}" class="text-[11px] font-bold text-lnu-800 hover:text-lnu-600 transition">Open availability →</a>
                                @else
                                    <a href="{{ route('projects.show', $e['model']->program) }}" class="text-[11px] font-bold text-lnu-800 hover:text-lnu-600 transition">Open activity →</a>
                                @endif
                            </div>
                        </div>
                    </li>
                </ul>
            @empty
                <div class="text-center py-7">
                    <span class="mx-auto w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center"><x-sc.icon name="check" class="w-5 h-5" /></span>
                    <p class="mt-3 font-bold text-[13px]">Nothing scheduled</p>
                    <p class="text-[11.5px] text-gray-400 mt-1">Enjoy the breather — no activities or deadlines on this day.</p>
                </div>
            @endforelse
        </div>
    </aside>
</section>
@else
{{-- 60-day grouped list --}}
<section class="mt-4 space-y-3">
    @forelse ($next60->groupBy('date') as $dateKey => $dayGroup)
        @php($isToday = $dateKey === now()->format('Y-m-d'))
        <div class="reveal-item sc-card p-0 overflow-hidden" wire:key="cal60-group-{{ $dateKey }}">
            <div class="flex items-center gap-2.5 px-4 py-2.5 {{ $isToday ? 'bg-gold-50/70' : 'bg-gray-50/60' }}">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">{{ Carbon\Carbon::parse($dateKey)->format('l') }}</p>
                <p class="text-[13px] font-extrabold tracking-tight">{{ Carbon\Carbon::parse($dateKey)->format('F j, Y') }}</p>
                @if ($isToday)<span class="badge badge-gold !text-[10px]">today</span>@endif
            </div>
            <ul class="px-4 divide-y divide-gray-50">
                @foreach ($dayGroup as $e)
                    <li class="flex items-center gap-3 py-2.5" wire:key="cal60-{{ $e['type'] }}-{{ $dateKey }}-{{ $loop->index }}">
                        <span class="w-2 h-2 rounded-full shrink-0 {{ $e['conflict'] ? 'bg-red-500' : match ($e['type']) { 'activity' => 'bg-lnu-600', 'availability' => 'bg-gold-500', default => 'bg-red-500' } }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12.5px] font-bold truncate">
                                {{ $e['title'] }}
                                @if ($e['mine'])<span class="badge badge-gold !text-[9.5px] !px-2 !py-0.5">(You)</span>@endif
                                @if ($e['conflict'])<span class="conflict-marker">conflict</span>@endif
                            </p>
                            <p class="text-[11px] text-gray-400">
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
                           class="text-[11px] font-bold text-lnu-800 hover:text-lnu-600 shrink-0">Open →</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="sc-card p-10 text-center">
            <p class="font-bold text-[13.5px]">Nothing scheduled in the next 60 days</p>
            <p class="text-[12px] text-gray-400 mt-1">Accept an availability request or check the month grid.</p>
        </div>
    @endforelse
</section>
@endif

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
