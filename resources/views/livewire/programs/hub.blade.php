<div>
<section class="pt-6">
    @php
        $backUrl = match (true) {
            auth()->user()->isFaculty() => route('programs.my'),
            auth()->user()->isSecretary() => route('beneficiaries.index'),
            default => route('programs.index'),
        };
    @endphp
    <a href="{{ $backUrl }}" class="text-[12px] font-bold text-gray-400 hover:text-lnu-700 transition">← {{ auth()->user()->isSecretary() ? 'Manage Beneficiaries' : 'Extension Programs' }}</a>
</section>

@if (! $canManage && ! $canManageBeneficiaries)
    <section class="mt-2">
        <div class="sc-card p-3.5 !border-lnu-100 !bg-lnu-50/60 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-lnu-50 text-lnu-700 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-4 h-4" /></span>
            <p class="text-[12.5px] text-lnu-800 font-semibold">Read-only view — you can browse the program's overview, activities, beneficiaries, and budget. Editing is done by the Director's office.</p>
        </div>
    </section>
@elseif (! $canManage)
    <section class="mt-2">
        <div class="sc-card p-3.5 !border-gold-200 !bg-gold-50/60 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-gold-100 text-gold-700 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-4 h-4" /></span>
            <p class="text-[12.5px] text-gold-800 font-semibold">You can enroll, register, and unenroll beneficiaries, import them from XLSX, and record activity attendance. Program editing, objectives, activities, and budget remain with the Director's office.</p>
        </div>
    </section>
@endif

{{-- HERO CARD --}}
<section class="mt-2 reveal-item">
    <div class="sc-card overflow-hidden">
        <div class="relative h-24 bg-gradient-to-r from-lnu-800 to-lnu-600"
             style="background-image:radial-gradient(circle at 88% -30%, rgba(246,184,0,.4), transparent 46%), radial-gradient(rgba(255,255,255,.13) 1px, transparent 1.4px); background-size:auto, 16px 16px;"></div>
        <div class="px-6 pb-5 -mt-8 relative">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex items-end gap-4 min-w-0">
                    <span class="avatar w-16 h-16 text-lg !ring-4 !ring-white shadow-pop">{{ \App\View\Components\Initials::for($this->program->programLead?->user?->name ?? 'SC') }}</span>
                    <div class="min-w-0 pb-0.5">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                            <span class="badge badge-gold font-bold tracking-wide">{{ $program->code }}</span>
                            <span class="badge badge-{{ config('smartcemes.status_colors')[$program->status] ?? 'gray' }}">{{ ucfirst($program->status) }}</span>
                            @if ($over)<span class="badge badge-red">⚠ Budget over-allocated</span>@endif
                        </div>
                        <h1 class="font-extrabold text-[19px] tracking-tight leading-tight">{{ $program->title }}</h1>
                    </div>
                </div>
                <div class="flex gap-2 pb-1">
                    @if ($canManage)
                        <button wire:click="openProgramEdit" class="btn btn-outline !py-2"><x-sc.icon name="edit" class="w-4 h-4" />Edit</button>
                        <button wire:click="openActivityForm(null)" class="btn btn-primary !py-2"><x-sc.icon name="calendar" class="w-4 h-4" />Add Activity</button>
                    @endif
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-gray-100 flex flex-wrap gap-x-7 gap-y-2.5 text-[12.5px] text-gray-600">
                <span class="flex items-center gap-2"><x-sc.icon name="people" class="w-4 h-4 text-lnu-600" /><span class="font-semibold">{{ $program->programLead?->user?->name ?? 'No lead assigned' }}</span><span class="text-gray-400">· Lead</span></span>
                <span class="flex items-center gap-2"><x-sc.icon name="pin" class="w-4 h-4 text-red-400" />{{ $program->communities->pluck('name')->implode(', ') ?: 'No community linked' }}</span>
                <span class="flex items-center gap-2"><x-sc.icon name="calendar" class="w-4 h-4 text-emerald-500" />{{ $program->planned_start_date->format('M j, Y') }} – {{ $program->planned_end_date->format('M j, Y') }}</span>
                <span class="flex items-center gap-2"><x-sc.icon name="clipboard" class="w-4 h-4 text-gold-600" />{{ $program->activities->count() }} activities scheduled</span>
            </div>
        </div>
    </div>
</section>

{{-- D7 OVER-ALLOCATION BANNER --}}
@if ($over)
    <section class="mt-4 reveal-item">
        <div class="sc-card p-4 !border-amber-200 !bg-amber-50/70 flex items-start gap-3">
            <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><x-sc.icon name="wallet" class="w-4 h-4" /></span>
            <div><p class="text-[13px] font-bold text-amber-800">This program exceeded its ₱{{ number_format((float) $program->allocated_budget) }} allocation by ₱{{ number_format($utilized - (float) $program->allocated_budget) }} ({{ $kpi->budgetUtilization($program) === null ? '—' : round($kpi->budgetUtilization($program)).'%' }})</p>
                <p class="text-[12px] text-amber-700 mt-0.5">Entries are still saved; a persistent warning badge shows on the program and overruns are written to the activity log (D7). Review is advisory, not blocking.</p></div>
        </div>
    </section>
@endif

{{-- STAT TILES --}}
@php
    $completedCount = $program->activities->where('status', 'completed')->count();
    $linked = $program->beneficiaries->count();
@endphp
<section class="mt-4 grid grid-cols-4 gap-4 reveal-item">
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Budget</p><span class="w-8 h-8 rounded-lg bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="wallet" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">₱{{ number_format((float) $program->allocated_budget) }}</p>
        <div class="progress mt-3"><span style="width:{{ min(round($kpi->budgetUtilization($program) ?? 0), 100) }}%" class="{{ $over ? 'bg-red-500' : 'bg-lnu-800' }}"></span></div>
        <p class="text-[11px] text-gray-500 font-semibold mt-2">₱{{ number_format($utilized) }} utilized · {{ $kpi->budgetUtilization($program) === null ? '—' : round($kpi->budgetUtilization($program)).'%' }}</p>
    </div>
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Beneficiaries</p><span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="people" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $served }}<span class="text-[14px] text-gray-400 font-bold"> / {{ $program->target_beneficiaries ?? '—' }}</span></p>
        <p class="text-[11px] text-gray-400 font-medium mt-1 mb-3">reached of target · {{ $linked }} enrolled in hub</p>
        <div class="progress"><span style="width:{{ $program->target_beneficiaries ? min(round($served / $program->target_beneficiaries * 100), 100) : 0 }}%" class="bg-emerald-500"></span></div>
        <p class="text-[11px] text-gray-500 font-semibold mt-2">distinct beneficiaries served</p>
    </div>
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Activities</p><span class="w-8 h-8 rounded-lg bg-gold-50 text-gold-700 flex items-center justify-center"><x-sc.icon name="clipboard" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $program->activities->count() }}</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1 mb-3">this program</p>
        <div class="progress"><span style="width:{{ $program->activities->count() ? round($completedCount / $program->activities->count() * 100) : 0 }}%" class="bg-gold-500"></span></div>
        <p class="text-[11px] text-gray-500 font-semibold mt-2">{{ $completedCount }} completed · {{ round($kpi->activityCompletionRate($program) ?? 0) }}% completion rate</p>
    </div>
    <div class="sc-card sc-card-hover p-5 {{ $knowledgeGain === null ? '' : ($knowledgeGain >= 0 ? '!border-emerald-100 !bg-gradient-to-br !from-white !to-emerald-50/60' : '!border-red-100 !bg-gradient-to-br !from-white !to-red-50/60') }}">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Knowledge Gain</p><span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="chart" class="w-4 h-4" /></span></div>
        @if ($knowledgeGain !== null)
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none {{ $knowledgeGain >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ ($knowledgeGain >= 0 ? '+' : '').number_format($knowledgeGain, 1) }} pts</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1 mb-3">average score {{ $knowledgeGain >= 0 ? 'uplift' : 'decline' }}@if ($avgPre !== null && $avgPost !== null) · {{ number_format($avgPre, 1) }} → {{ number_format($avgPost, 1) }}@endif</p>
        @else
            <p class="mt-3 text-[15px] font-bold text-gray-400 leading-snug">No scored activities yet</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1 mb-3">pre/post scores pending</p>
        @endif
    </div>
</section>

{{-- KPI SCORECARD VS TARGETS + ATTENDANCE & SATISFACTION --}}
<section class="mt-4 grid grid-cols-3 gap-4 reveal-item">
    <div class="sc-card p-6 col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h3 class="font-bold text-[14px] flex items-center gap-2"><x-sc.icon name="chart" class="w-[18px] h-[18px] text-lnu-700" /> KPI Scorecard vs Targets</h3>
            <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-400 font-medium">
                <span class="flex items-center gap-1.5"><span class="legend-dot bg-emerald-500"></span>meets target</span>
                <span class="flex items-center gap-1.5"><span class="legend-dot bg-red-500"></span>below / over</span>
                <span class="flex items-center gap-1.5"><span class="legend-dot bg-lnu-800"></span>no target</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-0 border-t-2 border-dashed border-gold-500"></span>target</span>
            </div>
        </div>
        <p class="text-[11.5px] text-gray-400 font-medium mb-4">Actuals auto-computed live from program data (8.6) · targets from the program's results framework</p>

        <div class="space-y-4">
            @foreach ($scorecard as $row)
                <div wire:key="sc-{{ \Illuminate\Support\Str::slug($row['label']) }}">
                    <div class="flex items-center justify-between text-[12.5px]">
                        <span class="font-semibold">{{ $row['label'] }}</span>
                        <span class="font-bold">{{ $row['display'] }}@if ($row['pct_text']) <span class="text-gray-400 font-semibold">· {{ $row['pct_text'] }}</span>@endif</span>
                    </div>
                    <div class="relative mt-1.5">
                        <div class="progress !h-2"><span style="width:{{ $row['bar'] }}%" class="{{ $row['color'] }}"></span></div>
                        @if ($row['marker'] !== null)
                            <span class="marker" style="left:{{ $row['marker'] }}%"></span>
                        @endif
                    </div>
                    <p class="text-[10.5px] {{ $row['caption_class'] }} mt-1">{{ $row['caption'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-3 gap-3">
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Cost per Beneficiary</p>
                <p class="text-[15px] font-extrabold mt-1">{{ $costPerBeneficiary === null ? '—' : '₱'.number_format($costPerBeneficiary, 2) }}</p>
                <p class="text-[10.5px] text-gray-400">{{ $costPerBeneficiary === null ? 'no beneficiaries served yet' : '₱'.number_format($utilized).' ÷ '.$served.' served' }}</p>
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Knowledge Gain</p>
                <p class="text-[15px] font-extrabold mt-1 {{ $knowledgeGain === null ? 'text-gray-300' : ($knowledgeGain >= 0 ? 'text-emerald-600' : 'text-red-600') }}">{{ $knowledgeGain === null ? 'No scores' : ($knowledgeGain >= 0 ? '+' : '').number_format($knowledgeGain, 1).' pts' }}</p>
                <p class="text-[10.5px] text-gray-400">{{ $scoredActivities === 0 ? 'pre/post scores pending' : $scoredActivities.' scored activit'.($scoredActivities === 1 ? 'y' : 'ies') }}</p>
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Avg Satisfaction</p>
                <p class="text-[15px] font-extrabold mt-1">{{ $avgSatisfaction === null ? '—' : number_format($avgSatisfaction, 2).' / 5' }}</p>
                <p class="text-[10.5px] text-gray-400">{{ $satisfactionRated === 0 ? 'no evaluation ratings yet' : $satisfactionRated.' evaluated activit'.($satisfactionRated === 1 ? 'y' : 'ies') }}</p>
            </div>
        </div>
    </div>

    <div class="sc-card p-6">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-bold text-[14px]">Attendance &amp; Satisfaction</h3>
            <span class="badge badge-blue">per activity</span>
        </div>
        <p class="text-[11.5px] text-gray-400 font-medium mb-3">Completed activities · attendees recorded</p>
        @if ($attendanceChartRows->isEmpty())
            <div class="h-56 flex items-center justify-center rounded-xl border border-dashed border-gray-200">
                <p class="text-[12px] text-gray-400 italic px-4 text-center">No completed activities with attendance yet.</p>
            </div>
        @else
            <div class="relative h-56"><canvas wire:key="att-chart-{{ md5($attendanceChartRows->toJson()) }}" wire:ignore x-data x-init="
                if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();
                new Chart($refs.c, {
                    type: 'bar',
                    data: {
                        labels: @js($attendanceChartRows->pluck('label')),
                        datasets: [{
                            label: 'Attendees',
                            data: @js($attendanceChartRows->pluck('attendees')),
                            backgroundColor: @js($attendanceChartRows->map(fn ($r, $i) => $i % 2 === 0 ? '#003599' : '#2547eb')),
                            borderRadius: 7, maxBarThickness: 24
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        maintainAspectRatio: false,
                        scales: {
                            x: { beginAtZero: true, grid: { drawTicks: false } },
                            y: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} attendees recorded` } }
                        }
                    }
                })
            " x-ref="c"></canvas></div>
        @endif
        <div class="mt-3 space-y-1.5">
            @forelse ($satisfactionRows as $r)
                <p class="text-[11px] text-gray-500 flex items-center justify-between">
                    <span class="truncate">{{ $r['label'] }}</span>
                    <span class="badge {{ $r['rating'] >= 4 ? 'badge-green' : ($r['rating'] >= 3 ? 'badge-yellow' : 'badge-red') }}">{{ number_format($r['rating'], 1) }}/5</span>
                </p>
            @empty
                <p class="text-[11px] text-gray-400 italic">No satisfaction ratings imported yet.</p>
            @endforelse
        </div>
    </div>
</section>

{{-- TABS --}}
<section class="reveal-item mt-5">
    <div class="flex gap-1 bg-white rounded-xl border border-gray-100 p-1 w-max">
        @foreach (['overview' => 'Overview', 'activities' => 'Activities', 'beneficiaries' => 'Beneficiaries', 'budget' => 'Budget'] as $key => $label)
            <button wire:click="setTab('{{ $key }}')" @class(['tab', 'on' => $tab === $key])>{{ $label }}</button>
        @endforeach
    </div>

    @includeWhen($tab === 'overview', 'livewire.programs.partials.hub-overview')
    @includeWhen($tab === 'activities', 'livewire.programs.partials.hub-activities')
    @includeWhen($tab === 'beneficiaries', 'livewire.programs.partials.hub-beneficiaries')
    @includeWhen($tab === 'budget', 'livewire.programs.partials.hub-budget')
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">SmartCEMES · Community Extension Services Office · Leyte Normal University</footer>

@include('livewire.programs.partials.hub-modals')
</div>

