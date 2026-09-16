<div>
@php
    $objectiveStatuses = $kpis['objectiveStatuses'];
    $servedPct = $kpis['beneficiaryTarget'] > 0 ? min((int) round($kpis['served'] / $kpis['beneficiaryTarget'] * 100), 100) : null;
    $servedMarker = $kpis['beneficiaryTarget'] > 0 ? min((int) round($kpis['beneficiaryTarget'] / max($kpis['served'], $kpis['beneficiaryTarget'], 1) * 100), 100) : null;
    $budgetBarPct = $kpis['budgetPct'] === null ? 0 : min((int) round($kpis['budgetPct']), 100);
    $featuredNarratives = $narrativeLabels->filter(fn ($n) => filled($n['summary']))->take(2);
    $otherNarratives = $narrativeLabels
        ->reject(fn ($n) => $featuredNarratives->contains('code', $n['code']))
        ->filter(fn ($n) => $n['health'] !== null);
@endphp

<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium">Welcome, {{ auth()->user()->name }} · {{ now()->format('l, F j, Y') }}</p>
    <p class="text-[11.5px] text-gray-400 font-medium mt-1">All figures derive from the 8.6 KPI dictionary · aggregated from validated records</p>
</section>

{{-- ===================== KPI ROW ===================== --}}
<section class="mt-4 grid grid-cols-5 gap-4">
    {{-- Programs --}}
    <div class="reveal-item sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between">
            <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="folder" class="w-5 h-5" /></span>
            <span class="badge badge-gray">{{ $kpis['ayLabel'] }}</span>
        </div>
        <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none">{{ $kpis['programs'] }}</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Extension Programs</p>
        @if ($statusChart['data']->sum() > 0)
            <div class="mini-seg mt-3">
                @foreach ($statusChart['labels'] as $i => $label)
                    <span style="width:{{ round($statusChart['data'][$i] / max($statusChart['data']->sum(), 1) * 100, 1) }}%; background:{{ $statusChart['colors'][$i] }}"></span>
                @endforeach
            </div>
            <p class="text-[11px] text-gray-400 font-medium mt-2">{{ $statusChart['labels']->map(fn ($l, $i) => $statusChart['data'][$i].' '.strtolower($l))->implode(' · ') }}</p>
        @else
            <p class="text-[11px] text-gray-400 font-medium mt-2">no programs yet</p>
        @endif
    </div>

    {{-- Beneficiaries vs combined program target --}}
    <div class="reveal-item sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="people" class="w-5 h-5" /></span>
            @if ($kpis['beneficiaryTarget'] > 0)
                <span class="badge badge-green">target {{ number_format($kpis['beneficiaryTarget']) }}</span>
            @endif
        </div>
        <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none text-emerald-600">{{ number_format($kpis['served']) }}</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Distinct Beneficiaries Served</p>
        @if ($servedPct !== null)
            <div class="relative mt-3">
                <div class="progress"><span style="width:{{ $servedPct }}%" class="bg-emerald-500"></span></div>
                <span class="marker" style="left:{{ $servedMarker }}%"></span>
            </div>
            <p class="text-[11px] text-gray-400 font-medium mt-2">{{ $servedPct }}% of the combined program target · dashed mark = target (8.6)</p>
        @else
            <p class="text-[11px] text-gray-400 font-medium mt-2">distinct present/late beneficiaries (8.6)</p>
        @endif
    </div>

    {{-- Budget vs objective target --}}
    <div class="reveal-item sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between">
            <span class="w-10 h-10 rounded-xl bg-gold-50 text-gold-700 flex items-center justify-center"><x-sc.icon name="wallet" class="w-5 h-5" /></span>
            @if ($kpis['budgetPct'] !== null)
                <span class="badge badge-yellow">{{ (int) round($kpis['budgetPct']) }}% utilized</span>
            @endif
        </div>
        <p class="mt-4 text-[24px] font-extrabold tracking-tight leading-none">₱{{ number_format($kpis['budgetUsed']) }}</p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Budget Utilized of ₱{{ number_format($kpis['allocated']) }}</p>
        <div class="relative mt-3">
            <div class="progress"><span style="width:{{ $budgetBarPct }}%" class="{{ $kpis['overCount'] > 0 ? 'bg-red-500' : 'bg-lnu-800' }}"></span></div>
            @if ($kpis['budgetTarget'] !== null)
                <span class="marker" style="left:{{ min((int) round($kpis['budgetTarget']), 100) }}%"></span>
            @endif
        </div>
        <p class="text-[11px] text-gray-400 font-medium mt-2">
            @if ($kpis['overCount'] > 0)
                <span class="font-bold text-red-600">{{ $kpis['overCount'] }} program{{ $kpis['overCount'] === 1 ? '' : 's' }} over-allocated (D7)</span>
            @endif
            {{ $kpis['budgetTarget'] !== null ? '· dashed mark = objective target' : '· utilized of allocated (8.6)' }}
        </p>
    </div>

    {{-- Objectives achieved --}}
    <div class="reveal-item sc-card sc-card-hover p-5">
        <div class="flex items-center justify-between">
            <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="chart" class="w-5 h-5" /></span>
            <span class="badge badge-blue">{{ $kpis['objectiveTotal'] }} total</span>
        </div>
        <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none">{{ $objectiveStatuses['achieved'] }}<span class="text-[15px] text-gray-400 font-bold"> / {{ $kpis['objectiveTotal'] }}</span></p>
        <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Objectives Achieved</p>
        @if ($kpis['objectiveTotal'] > 0)
            <div class="mini-seg mt-3">
                @foreach (['achieved' => '#10b981', 'on_track' => '#003599', 'not_met' => '#ef4444', 'not_started' => '#cbd5e1'] as $status => $color)
                    @if ($objectiveStatuses[$status] > 0)
                        <span style="width:{{ round($objectiveStatuses[$status] / $kpis['objectiveTotal'] * 100, 1) }}%; background:{{ $color }}"></span>
                    @endif
                @endforeach
            </div>
            <p class="text-[11px] text-gray-400 font-medium mt-2">
                <span class="legend-dot bg-emerald-500"></span> {{ $objectiveStatuses['achieved'] }} achieved ·
                <span class="legend-dot bg-lnu-800"></span> {{ $objectiveStatuses['on_track'] }} on track ·
                <span class="legend-dot bg-red-500"></span> {{ $objectiveStatuses['not_met'] }} not met ·
                <span class="legend-dot bg-gray-300"></span> {{ $objectiveStatuses['not_started'] }} not started
            </p>
        @else
            <p class="text-[11px] text-gray-400 font-medium mt-2">no objectives defined yet</p>
        @endif
    </div>

    {{-- Pending approvals --}}
    <div class="reveal-item sc-card p-5 !border-gold-200 !bg-gradient-to-br !from-white !to-gold-50">
        <div class="flex items-center justify-between">
            <span class="w-10 h-10 rounded-xl bg-gold-100 text-gold-700 flex items-center justify-center"><x-sc.icon name="clock" class="w-5 h-5" /></span>
            <span class="badge badge-red">action needed</span>
        </div>
        <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none text-gold-700">{{ $kpis['pendingApprovals'] }}</p>
        <p class="text-[12.5px] text-gray-600 font-medium mt-1.5">Pending Approvals</p>
        <p class="text-[11px] text-gray-500 font-medium mt-2">{{ $kpis['pendingProposals'] }} proposals · {{ $kpis['pendingAvailability'] }} availability · {{ $kpis['pendingHours'] }} rendered hours</p>
        @if ($actionCenter['aiAnalyses'] > 0)
            <a href="{{ route('ai-analysis.index') }}" class="text-[11px] font-bold text-gold-800 hover:text-gold-600 transition inline-block mt-1.5">{{ $actionCenter['aiAnalyses'] }} AI analysis{{ $actionCenter['aiAnalyses'] === 1 ? '' : 'es' }} awaiting approval →</a>
        @endif
    </div>
</section>

{{-- ===================== CHARTS ROW 1 ===================== --}}
<section class="mt-5 grid grid-cols-3 gap-4">
    <div class="reveal-item sc-card p-5">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-bold text-[14px]">Program Portfolio</h3>
            <span class="text-[11.5px] text-gray-400 font-medium">by status</span>
        </div>
        <div class="relative h-56"><canvas x-data x-init="
            if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();
            new Chart($refs.c, {
                type: 'doughnut',
                data: {
                    labels: @js($statusChart['labels']),
                    datasets: [{ data: @js($statusChart['data']), backgroundColor: @js($statusChart['colors']), borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
                },
                options: {
                    maintainAspectRatio: false, cutout: '66%',
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} program${ctx.parsed === 1 ? '' : 's'}` } }
                    }
                },
                plugins: [{
                    id: 'centerText',
                    afterDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const x = (chartArea.left + chartArea.right) / 2;
                        const y = (chartArea.top + chartArea.bottom) / 2;
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '#1f2937';
                        ctx.font = '800 26px Figtree, sans-serif';
                        ctx.fillText(@js($kpis['programs']), x, y + 2);
                        ctx.fillStyle = '#9ca3af';
                        ctx.font = '600 10px Figtree, sans-serif';
                        ctx.fillText('PROGRAMS', x, y + 18);
                        ctx.restore();
                    }
                }]
            })
        " x-ref="c"></canvas></div>
    </div>

    <div class="reveal-item sc-card p-5 col-span-2">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-bold text-[14px]">Budget Utilization</h3>
            <div class="flex items-center gap-4 text-[11.5px] text-gray-400 font-medium">
                <span class="flex items-center gap-1.5"><span class="legend-dot" style="background:#dbe6fe"></span>Allocated</span>
                <span class="flex items-center gap-1.5"><span class="legend-dot bg-gold-500"></span>Utilized</span>
                <span class="flex items-center gap-1.5"><span class="legend-dot bg-red-500"></span>Over-allocated (D7)</span>
            </div>
        </div>
        <p class="text-[11.5px] text-gray-400 font-medium mb-2">Hover a bar for the utilization rate against allocation</p>
        <div class="relative h-56"><canvas x-data x-init="
            if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();
            new Chart($refs.c, {
                type: 'bar',
                data: {
                    labels: @js($budgetChart['labels']),
                    datasets: [
                        { label: 'Allocated', data: @js($budgetChart['allocated']), backgroundColor: '#dbe6fe', borderRadius: 6, maxBarThickness: 18 },
                        { label: 'Utilized', data: @js($budgetChart['utilized']), backgroundColor: @js($budgetChart['utilizedColors']), borderRadius: 6, maxBarThickness: 18 }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: v => '₱' + Number(v).toLocaleString('en-PH') }, grid: { drawTicks: false } },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label(ctx) {
                                    if (ctx.dataset.label === 'Utilized') {
                                        const planned = ctx.chart.data.datasets[0].data[ctx.dataIndex];
                                        const pct = planned ? Math.round(ctx.parsed.y / planned * 100) : 0;
                                        return ` Utilized: ₱${Number(ctx.parsed.y).toLocaleString('en-PH')} · ${pct}% of allocation${ctx.parsed.y > planned ? ' — over (D7)' : ''}`;
                                    }
                                    return ` Allocated: ₱${Number(ctx.parsed.y).toLocaleString('en-PH')}`;
                                }
                            }
                        }
                    }
                }
            })
        " x-ref="c"></canvas></div>
    </div>
</section>

{{-- ===================== CHARTS ROW 2 ===================== --}}
<section class="mt-4 grid grid-cols-3 gap-4">
    <div class="reveal-item sc-card p-5 col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-[14px]">Community Reach</h3>
            <span class="badge badge-blue">top 10 · distinct beneficiaries served</span>
        </div>
        <div class="relative h-64"><canvas x-data x-init="
            if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();
            new Chart($refs.c, {
                type: 'bar',
                data: {
                    labels: @js($reachChart['labels']),
                    datasets: [{ label: 'Served', data: @js($reachChart['data']), backgroundColor: @js($reachChart['colors']), borderRadius: 7, maxBarThickness: 20 }]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, grid: { drawTicks: false } },
                        y: { grid: { display: false }, ticks: { font: { size: 11.5, weight: '600' } } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} distinct beneficiaries served` } }
                    }
                }
            })
        " x-ref="c"></canvas></div>
        <p class="text-[11px] text-gray-400 font-medium mt-2">Remaining barangays are grouped into “Others”. Grouped by municipality · barangay (8.6).</p>
    </div>

    <div class="reveal-item sc-card p-5">
        <h3 class="font-bold text-[14px] mb-3">Recent Activity</h3>
        <ol class="relative border-l border-gray-100 ml-2 space-y-4">
            @forelse ($recentActivity as $log)
                <li class="ml-4" wire:key="dash-log-{{ $log->id }}">
                    <span class="absolute -left-[7px] w-3.5 h-3.5 rounded-full {{ match ($log->event) { 'deleted' => 'bg-red-500 ring-4 ring-red-50', 'status_transition' => 'bg-gold-500 ring-4 ring-gold-50', default => 'bg-lnu-800 ring-4 ring-lnu-50' } }}"></span>
                    <p class="text-[13px] font-semibold leading-snug">{{ $log->description }}</p>
                    <p class="text-[11.5px] text-gray-400">{{ $log->causer?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</p>
                </li>
            @empty
                <li class="ml-4 text-[12.5px] text-gray-400 italic">No activity logged yet.</li>
            @endforelse
        </ol>
    </div>
</section>

{{-- ===================== ACTION CENTER (12.1) ===================== --}}
<section class="mt-4">
    <div class="reveal-item sc-card p-6">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="clipboard" class="w-[18px] h-[18px] text-lnu-700" /> Action Center</h3>
            <span class="badge badge-red">{{ $kpis['pendingApprovals'] }} items</span>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Proposals awaiting approval</p>
                <p class="mt-2 text-[15px] font-extrabold {{ $actionCenter['proposals']->count() ? 'text-red-600' : 'text-gray-300' }} leading-none">{{ $actionCenter['proposals']->count() }}</p>
                @forelse ($actionCenter['proposals']->take(2) as $p)
                    <a href="{{ route('proposals.index') }}" class="block text-[11.5px] font-semibold text-charcoal mt-2 truncate hover:text-lnu-700">{{ \Illuminate\Support\Str::limit($p->title, 30) }}</a>
                @endforeach
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Availability awaiting response</p>
                <p class="mt-2 text-[15px] font-extrabold {{ $actionCenter['availability']->count() ? 'text-amber-600' : 'text-gray-300' }} leading-none">{{ $actionCenter['availability']->count() }}</p>
                @foreach ($actionCenter['availability']->take(2) as $a)
                    <a href="{{ route('availability.index') }}" class="block text-[11.5px] font-semibold text-charcoal mt-2 truncate hover:text-lnu-700">{{ $a->faculty->user->name }} · {{ $a->date->format('M j') }}</a>
                @endforeach
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Rendered hours awaiting approval</p>
                <p class="mt-2 text-[15px] font-extrabold {{ $actionCenter['renderedHours']->count() ? 'text-lnu-700' : 'text-gray-300' }} leading-none">{{ $actionCenter['renderedHours']->count() }}</p>
                @foreach ($actionCenter['renderedHours']->take(2) as $r)
                    <a href="{{ route('rendered-hours.index') }}" class="block text-[11.5px] font-semibold text-charcoal mt-2 truncate hover:text-lnu-700">{{ $r->faculty->user->name }} · {{ number_format((float) $r->hours, 1) }} hrs</a>
                @endforeach
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-3">
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Objectives at risk</p>
                <p class="mt-2 text-[15px] font-extrabold {{ $actionCenter['objectivesAtRisk']->count() ? 'text-red-600' : 'text-gray-300' }} leading-none">{{ $actionCenter['objectivesAtRisk']->count() }}</p>
                @foreach ($actionCenter['objectivesAtRisk']->take(2) as $o)
                    <a href="{{ route('programs.show', $o->program) }}" class="block text-[11.5px] font-semibold text-charcoal mt-2 truncate hover:text-lnu-700">{{ $o->program?->code }} · {{ \Illuminate\Support\Str::limit($o->objective, 26) }}</a>
                    <p class="text-[10.5px] text-gray-400 mt-0.5">{{ $o->target_date ? 'Target date '.$o->target_date->format('M j') : 'No target date' }}</p>
                @endforeach
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Programs nearing deadline (≤14d)</p>
                <p class="mt-2 text-[15px] font-extrabold {{ $actionCenter['programsEnding']->count() ? 'text-amber-600' : 'text-gray-300' }} leading-none">{{ $actionCenter['programsEnding']->count() }}</p>
                @foreach ($actionCenter['programsEnding']->take(2) as $p)
                    <a href="{{ route('programs.show', $p) }}" class="block text-[11.5px] font-semibold text-charcoal mt-2 truncate hover:text-lnu-700">{{ $p->code }} · ends {{ $p->planned_end_date->format('M j') }}</a>
                    <p class="text-[10.5px] text-gray-400 mt-0.5">{{ (int) now()->diffInDays($p->planned_end_date) }} day{{ (int) now()->diffInDays($p->planned_end_date) === 1 ? '' : 's' }} remaining</p>
                @endforeach
            </div>
            <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">AI analyses awaiting approval</p>
                <p class="mt-2 text-[15px] font-extrabold {{ $actionCenter['aiAnalyses'] ? 'text-gold-700' : 'text-gray-300' }} leading-none">{{ $actionCenter['aiAnalyses'] }}</p>
                <a href="{{ route('ai-analysis.index') }}" class="text-[11px] font-bold text-lnu-700 mt-2 inline-block">Open workspace →</a>
            </div>
        </div>
    </div>
</section>

{{-- ===================== AI DECISION SUPPORT — Director-only (12.1) ===================== --}}
<section class="mt-4">
    <div class="reveal-item flex flex-wrap items-center justify-between gap-2 mb-3">
        <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">
            <x-sc.icon name="sparkles" class="w-5 h-5 text-gold-500" /> AI Decision Support
            <span class="badge badge-blue">Director-only · aggregated inputs only</span>
        </h3>
        <a href="{{ route('ai-analysis.index') }}" class="text-[12.5px] font-bold text-lnu-800 hover:text-lnu-600 transition">Open AI workspace →</a>
    </div>
    <div class="reveal-item grid grid-cols-3 gap-4">
        <div class="sc-card p-5 !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/50">
            <div class="flex items-center justify-between mb-2">
                <p class="font-bold text-[13.5px]">Community Insights Queue</p>
                <span class="badge badge-gold">{{ $aiDrafts->count() }} draft{{ $aiDrafts->count() === 1 ? '' : 's' }}</span>
            </div>
            <p class="text-[11.5px] text-gray-400 font-medium mb-2.5">Assessment analyses awaiting Admin approval{{ $aiPending ? " · {$aiPending} generating…" : '' }}</p>
            @forelse ($aiDrafts as $a)
                <div class="p-3 rounded-xl border border-gray-100 bg-white mb-2" wire:key="dash-ai-{{ $a->id }}">
                    <p class="text-[12.5px] font-bold truncate">{{ $a->assessmentSummary->community->name }} · Q{{ $a->assessmentSummary->quarter }} {{ $a->assessmentSummary->year }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $a->assessmentSummary->total_responses }} responses · {{ $a->metadata['model'] ?? 'gemini' }} · aggregated only</p>
                    <div class="flex gap-2 mt-2.5">
                        <a href="{{ route('ai-analysis.index') }}" class="btn btn-primary !px-2.5 !py-1.5 text-[11px]">Review &amp; approve</a>
                    </div>
                </div>
            @empty
                <div class="p-3 rounded-xl border border-dashed border-gray-200 text-center">
                    <p class="text-[12px] text-gray-400 italic">No drafts — generate an analysis from a validated community summary.</p>
                </div>
            @endforelse
            <p class="text-[10.5px] text-gray-400 mt-2.5">Failed generations appear here as “Analysis unavailable” with a retry — never a silent error.</p>
        </div>

        <div class="sc-card p-5 col-span-2">
            <div class="flex items-center justify-between mb-2">
                <p class="font-bold text-[13.5px]">Program Narratives — latest health label per program</p>
                <a href="{{ route('program-narratives.index') }}" class="text-[12px] font-bold text-lnu-800 hover:text-lnu-600 transition">View all narratives →</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @forelse ($featuredNarratives as $n)
                    <div class="sc-card p-4 ai-panel relative overflow-hidden" wire:key="dash-pn-{{ $n['code'] }}">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[12.5px] font-bold flex items-center gap-1.5 text-white"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" /> {{ $n['code'] }}</p>
                            @if ($n['status'] === 'failed')
                                <span class="badge badge-red">unavailable</span>
                            @elseif ($n['status'] === 'pending')
                                <span class="badge badge-yellow">generating</span>
                            @elseif ($n['health'])
                                <span class="narrative-health {{ $n['health'] }}">{{ match ($n['health']) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}</span>
                            @endif
                        </div>
                        <p class="text-[11.5px] text-white/85 leading-snug">{{ \Illuminate\Support\Str::limit($n['summary'], 140) }}</p>
                        <p class="text-[10px] text-white/60 mt-1.5">{{ $n['generated_at']?->format('M j') }} · {{ $n['model'] ?? 'gemini' }}</p>
                    </div>
                @empty
                    <p class="text-[12px] text-gray-400 italic">No narratives yet — generate one from a program hub.</p>
                @endforelse
            </div>
            @if ($otherNarratives->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    @foreach ($otherNarratives as $n)
                        <span class="badge {{ $n['health'] === 'at-risk' ? 'badge-red' : ($n['health'] === 'needs-attention' ? 'badge-yellow' : 'badge-green') }}">{{ $n['code'] }} · {{ str_replace('-', ' ', $n['health']) }}</span>
                    @endforeach
                    <a href="{{ route('program-narratives.index') }}" class="text-[11px] font-bold text-gray-400 hover:text-lnu-700 transition ml-auto">+ history per program →</a>
                </div>
            @endif
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
