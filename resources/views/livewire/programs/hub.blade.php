<div>
<section class="pt-6">
    @php
        /* The admin's back-link used to point at the cross-college /projects
           list, labelled "Extension Programs" — which was both a detour and a
           misnomer. Since 2026-09-27 (§23) the structure is browsed through the
           hub, so it returns to THIS project's college AND program: the exact
           view the Director drilled in from. `array_filter` drops nulls, so a
           project with no college or program still lands on the hub's view 1. */
        $backUrl = match (true) {
            auth()->user()->isFaculty() => route('projects.my'),
            auth()->user()->isSecretary() => route('beneficiaries.index'),
            default => route('colleges.index', array_filter([
                'college' => $this->program->college?->code,
                'program' => $this->program->program_id,
            ])),
        };

        $backLabel = match (true) {
            auth()->user()->isSecretary() => 'Manage Beneficiaries',
            auth()->user()->isFaculty() => 'My Projects',
            default => $this->program->college?->code ?? 'All colleges',
        };
    @endphp
    <a href="{{ $backUrl }}" class="text-[12px] font-bold text-gray-400 hover:text-lnu-700 transition">← {{ $backLabel }}</a>
</section>

@if (! $canManage && ! $canManageBeneficiaries)
    <section class="mt-2">
        <div class="sc-card p-3.5 !border-lnu-100 !bg-lnu-50/60 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-lnu-50 text-lnu-700 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-4 h-4" /></span>
            <p class="text-[12.5px] text-lnu-800 font-semibold">Read-only view — you can browse the project's overview, activities, beneficiaries, and budget. Editing is done by the Director's office.</p>
        </div>
    </section>
@elseif (! $canManage)
    <section class="mt-2">
        <div class="sc-card p-3.5 !border-gold-200 !bg-gold-50/60 flex items-center gap-3">
            <span class="w-8 h-8 rounded-lg bg-gold-100 text-gold-700 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-4 h-4" /></span>
            <p class="text-[12.5px] text-gold-800 font-semibold">You can enroll, register, and unenroll beneficiaries, import them from XLSX, and record activity attendance. Project editing, activities, and budget remain with the Director's office.</p>
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
                            @if ($over)<span class="badge badge-red"><x-sc.icon name="alert" class="w-3 h-3" /> Budget over-allocated</span>@endif
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

{{-- D7 OVER-ALLOCATION BANNER — measured against the project's ALLOCATION.
     Budget has no annual target (owner decision 2026-09-26). --}}
@if ($over)
    <section class="mt-4 reveal-item">
        <div class="sc-card p-4 !border-amber-200 !bg-amber-50/70 flex items-start gap-3">
            <span class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0"><x-sc.icon name="wallet" class="w-4 h-4" /></span>
            <div><p class="text-[13px] font-bold text-amber-800">This project exceeded its ₱{{ number_format($budgetVsAllocation['allocated']) }} allocation by ₱{{ number_format($utilized - $budgetVsAllocation['allocated']) }} ({{ $budgetVsAllocation['pct'] === null ? '—' : $budgetVsAllocation['pct'].'%' }})</p>
                <p class="text-[12px] text-amber-700 mt-0.5">Entries are still saved; a persistent warning badge shows on the project and overruns are written to the activity log (D7). Review is advisory, not blocking.</p></div>
        </div>
    </section>
@endif

{{-- STAT TILES — R4 (D-R7): Trainors · Trainees · Training Hrs · Budget · Activities --}}
@php
    $completedCount = $performance['completed_count'];
    $linked = $performance['trainees'];
@endphp
<section class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-4 reveal-item">
    {{-- Trainors --}}
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Trainors</p><span class="w-8 h-8 rounded-lg bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="users" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $performance['trainors'] }}</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1">assigned faculty · lead + co-leads</p>
    </div>

    {{-- Trainees --}}
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Trainees</p><span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center"><x-sc.icon name="people" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ number_format($linked) }}</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1 mb-2.5">distinct trainees reached</p>
        <div class="progress"><span style="width:{{ min((int) round($program->target_beneficiaries ? $linked / (int) $program->target_beneficiaries * 100 : 0), 100) }}%" class="bg-blue-500"></span></div>
        <p class="text-[11px] text-gray-500 font-semibold mt-2">{{ $program->target_beneficiaries ? $linked.' of '.number_format((int) $program->target_beneficiaries).' target reached' : 'no beneficiary target set' }}</p>
    </div>

    {{-- Training hours --}}
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Training Hrs</p><span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="clock" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ number_format($performance['actual_hours']) }}<span class="text-[14px] text-gray-400 font-bold"> / {{ $performance['target_hours'] === null ? '—' : number_format($performance['target_hours']) }}</span></p>
        <p class="text-[11px] text-gray-400 font-medium mt-1 mb-2.5">rendered of annual target</p>
        <div class="progress"><span style="width:{{ min((int) round($performance['hours_pct'] ?? 0), 100) }}%" class="{{ $performance['hours_pct'] === null ? 'bg-gray-300' : ($performance['hours_pct'] >= 100 ? 'bg-emerald-500' : ($performance['hours_pct'] >= 60 ? 'bg-lnu-600' : 'bg-gold-500')) }}"></span></div>
        <p class="text-[11px] text-gray-500 font-semibold mt-2">{{ $performance['hours_pct'] === null ? 'no annual hours target set' : $performance['hours_pct'].'% of annual target' }}</p>
    </div>

    {{-- Activities --}}
    <div class="sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Activities</p><span class="w-8 h-8 rounded-lg bg-violet-50 text-violet-700 flex items-center justify-center"><x-sc.icon name="clipboard" class="w-4 h-4" /></span></div>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none flex items-center gap-2">{{ $performance['activity_count'] }}@php $upcomingCount = $activities->whereIn('status', ['draft', 'ongoing'])->count(); @endphp@if ($upcomingCount)<span class="badge badge-blue !text-[10px]">{{ $upcomingCount }} in flight</span>@endif</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1 mb-2.5">in this project</p>
        <div class="progress"><span style="width:{{ $performance['activity_count'] ? round($completedCount / $performance['activity_count'] * 100) : 0 }}%" class="bg-gold-500"></span></div>
        <p class="text-[11px] text-gray-500 font-semibold mt-2">{{ $completedCount }} completed · {{ $hoursDaysTotal = (new \App\Services\TrainingHoursService)->formatDays($performance['training_days']) }} training days</p>
    </div>
</section>

{{-- TRAINING HOURS vs ANNUAL TARGET  +  BUDGET vs ALLOCATION (R4 / §2.2, D-R7) --}}
<section class="mt-4 grid grid-cols-3 gap-4 reveal-item">
    <div class="sc-card p-6 col-span-2">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h3 class="font-bold text-[14px] flex items-center gap-2"><x-sc.icon name="clock" class="w-[18px] h-[18px] text-lnu-700" /> Training Hours vs Annual Target</h3>
            <span class="badge {{ $performance['hours_pct'] === null ? 'badge-gray' : ($performance['hours_pct'] >= 100 ? 'badge-green' : ($performance['hours_pct'] >= 60 ? 'badge-blue' : 'badge-yellow')) }}">{{ $performance['hours_pct'] === null ? 'No target set' : $performance['hours_pct'].'% of target' }}</span>
        </div>
        <p class="text-[11.5px] text-gray-400 font-medium mb-4">
            Formula: <span class="font-semibold text-gray-500">trainors × trainees × days</span> (days carry the duration — 1.0 for a full day, 0.5 for a half day). Summed across every activity.
        </p>

        <div class="space-y-4">
            @foreach ($hoursRows as $row)
                <div wire:key="hrs-{{ \Illuminate\Support\Str::slug($row['label']) }}">
                    <div class="flex items-center justify-between text-[12.5px]">
                        <span class="font-semibold">{{ $row['label'] }}</span>
                        <span class="font-bold">{{ $row['display'] }}@if ($row['sub']) <span class="text-gray-400 font-semibold">· {{ $row['sub'] }}</span>@endif</span>
                    </div>
                    <div class="mt-1.5">
                        <div class="progress !h-2"><span style="width:{{ $row['bar'] }}%" class="{{ $row['tone'] }}"></span></div>
                    </div>
                    <p class="text-[10.5px] text-gray-400 mt-1">{{ $row['caption'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-3 gap-3">
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Hours remaining</p>
                @if ($performance['target_hours'] === null)
                    <p class="text-[15px] font-extrabold mt-1 text-gray-300">No target set</p>
                    <p class="text-[10.5px] text-gray-400">the Director sets it per project — Edit → Target training hours</p>
                @else
                    <p class="text-[15px] font-extrabold mt-1">{{ $performance['target_hours'] - $performance['actual_hours'] > 0 ? number_format($performance['target_hours'] - $performance['actual_hours']).' hrs' : 'Target met' }}</p>
                    <p class="text-[10.5px] text-gray-400">to reach the {{ number_format($performance['target_hours']) }} hr annual target</p>
                @endif
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Avg hours / activity</p>
                <p class="text-[15px] font-extrabold mt-1">{{ $performance['avg_hours_per_completed'] === null ? '—' : number_format($performance['avg_hours_per_completed'], 1) }}</p>
                <p class="text-[10.5px] text-gray-400">across completed activities</p>
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Training days</p>
                <p class="text-[15px] font-extrabold mt-1">{{ (new \App\Services\TrainingHoursService)->formatDays($performance['training_days']) }}</p>
                <p class="text-[10.5px] text-gray-400">0.5-day sessions count as half a day, not 4 hrs</p>
            </div>
        </div>
    </div>

    <div class="sc-card p-6">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-bold text-[14px]">Budget vs Allocated Budget</h3>
            <span class="badge {{ $over ? 'badge-red' : ($budgetVsAllocation['pct'] === null ? 'badge-gray' : ($budgetVsAllocation['pct'] >= 100 ? 'badge-green' : 'badge-blue')) }}">{{ $budgetVsAllocation['pct'] === null ? 'No allocation set' : $budgetVsAllocation['pct'].'% of allocation' }}</span>
        </div>
        <p class="text-[11.5px] text-gray-400 font-medium mb-3">Utilized against this project's allocated budget</p>

        {{-- Owner decision 2026-09-28: doughnut, THREE slices (Allocated / Utilized / Remaining).
             KNOWN CAVEAT, accepted deliberately. Allocated = Utilized + Remaining, so these three
             slices do NOT partition one whole — the ring sums to 2x the allocation, and Chart.js's
             own share-of-ring percentage would therefore UNDERSTATE utilisation (it would read 50%
             at full spend). The centre label and the legend therefore carry the true share of the
             ALLOCATION, so the figure cannot be misread off the geometry.
             Do NOT "fix" this to two slices without asking the owner — it was chosen with the
             caveat on the table.
             `remaining` is clamped at 0 on purpose: HANDA is over-allocated by design (the D7 demo)
             and a doughnut cannot draw a negative slice. Over-allocation is carried by the red badge
             above and the D7 banner, not by the ring. --}}
        <div class="relative h-60">
            <canvas wire:key="budget-chart-{{ md5((string) $utilized.'-'.$budgetVsAllocation['allocated']) }}" wire:ignore x-data x-init="
                if (Chart.getChart($refs.c)) { Chart.getChart($refs.c).destroy(); }
                const allocated = {{ (float) $budgetVsAllocation['allocated'] }};
                const utilized  = {{ (float) $utilized }};
                const over      = {{ $over ? 'true' : 'false' }};
                const remaining = Math.max(0, allocated - utilized);
                const peso      = v => '₱' + Number(v).toLocaleString('en-PH');
                const pctOf     = v => allocated > 0 ? Math.round(v / allocated * 1000) / 10 : 0;
                new Chart($refs.c, {
                    type: 'doughnut',
                    data: {
                        labels: ['Allocated ' + peso(allocated), 'Utilized ' + peso(utilized), 'Remaining ' + peso(remaining)],
                        datasets: [{
                            data: [allocated, utilized, remaining],
                            backgroundColor: ['#003599', over ? '#ef4444' : '#F6B800', '#e5e7eb'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 10, font: { size: 11 } } },
                            tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ' — ' + pctOf(ctx.parsed) + '% of allocation' } }
                        }
                    },
                    plugins: [{
                        id: 'hubBudgetCentre',
                        afterDraw(chart) {
                            const a = chart.chartArea;
                            if (! a) { return; }
                            const cx = (a.left + a.right) / 2;
                            const cy = (a.top + a.bottom) / 2;
                            const c2 = chart.ctx;
                            c2.save();
                            c2.textAlign = 'center';
                            c2.textBaseline = 'middle';
                            c2.fillStyle = over ? '#dc2626' : '#003599';
                            c2.font = '700 20px Figtree, ui-sans-serif, system-ui';
                            c2.fillText(allocated > 0 ? pctOf(utilized) + '%' : '—', cx, cy - 7);
                            c2.fillStyle = '#9ca3af';
                            c2.font = '600 10px Figtree, ui-sans-serif, system-ui';
                            c2.fillText(over ? 'over allocation' : 'of allocation', cx, cy + 11);
                            c2.restore();
                        }
                    }]
                })
            " x-ref="c"></canvas>
        </div>

    </div>
</section>

{{-- ATTENDANCE PER ACTIVITY --}}
<section class="mt-4 reveal-item">
    <div class="sc-card p-6">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-bold text-[14px]">Attendance per Activity</h3>
            <span class="badge badge-blue">trainee counts</span>
        </div>
        <p class="text-[11.5px] text-gray-400 font-medium mb-3">Completed activities · attendees recorded</p>
        @if ($attendanceChartRows->isEmpty())
            <div class="h-56 flex items-center justify-center rounded-xl border border-dashed border-gray-200">
                <p class="text-[12px] text-gray-400 italic px-4 text-center">No completed activities with attendance yet.</p>
            </div>
        @else
            <div class="relative h-56"><canvas wire:key="att-chart-{{ md5($attendanceChartRows->toJson()) }}" wire:ignore x-data x-init="
                if (Chart.getChart($refs.c)) { Chart.getChart($refs.c).destroy(); }
                new Chart($refs.c, {
                    type: 'bar',
                    data: {
                        labels: @js($attendanceChartRows->pluck('label')),
                        datasets: [{
                            label: 'Trainees',
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
                            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} trainees recorded` } }
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

