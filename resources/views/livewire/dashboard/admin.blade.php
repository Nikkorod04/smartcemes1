@php
    // R5: rebuilt on the prototype's dash-* primitives (§5 Phase R5). Every
    // number comes from TrainingHoursService / RankingService, so the dashboard
    // cannot disagree with the project hub, the targets page or the reports.
    $budgetBarPct = $kpis['budgetPct'] === null ? 0 : min((int) round($kpis['budgetPct']), 100);
    $hoursBarPct = $kpis['hoursPct'] === null ? 0 : min((int) round($kpis['hoursPct']), 100);
    $hoursTone = $kpis['hoursPct'] === null ? 'bg-gray-300' : ($kpis['hoursPct'] >= 100 ? 'bg-emerald-500' : ($kpis['hoursPct'] >= 60 ? 'bg-lnu-600' : 'bg-gold-500'));
    $featuredNarratives = $narrativeLabels->filter(fn ($n) => filled($n['summary']))->take(2);
    $otherNarratives = $narrativeLabels
        ->reject(fn ($n) => $featuredNarratives->contains('code', $n['code']))
        ->filter(fn ($n) => $n['health'] !== null);
    /* Rank label for the leaderboards. The 🥇🥈🥉 medals were REMOVED 2026-09-25
       (owner request): a trophy glyph is not an institutional mark, it rendered
       inconsistently across platforms, and it made the top three look like prizes
       rather than positions. The rank ORDER is the ranking; the magnitude bar on
       each row now carries the emphasis. */
    $rank = fn (int $i) => '#'.($i + 1);
@endphp

<div>
{{-- ===================== PAGE HEAD ===================== --}}
<section class="pt-6">
    <div class="flex items-end justify-between gap-4 flex-wrap">
        <div>
            <p class="dash-sec-eyebrow">Dashboard</p>
            <h2 class="text-[21px] font-extrabold tracking-tight leading-tight mt-1">Extension Overview</h2>
            <p class="text-[12.5px] text-gray-400 font-medium mt-1.5">
                {{ now()->format('l, F j, Y') }} · {{ $kpis['ayLabel'] }}
            </p>
        </div>
        <p class="dash-note max-w-xs text-right">
            Training hours derive from <span class="font-mono font-semibold text-gray-500">trainors × trainees × days</span> — no hourly factor
        </p>
    </div>
</section>

{{-- ===================== KPI ROW (R4/R5 dictionary) ===================== --}}
<section class="dash-section">
    <div class="reveal-item grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

        {{-- Extension Projects --}}
        <div class="dash-panel dash-kpi">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon bg-lnu-50 text-lnu-800"><x-sc.icon name="folder" class="w-5 h-5" /></span>
                <span class="badge badge-gray">{{ $kpis['programCount'] }} programs</span>
            </div>
            <p class="dash-kpi-value" style="color:#003599">{{ $kpis['programs'] }}</p>
            <p class="dash-kpi-label">Extension Projects</p>
            <div class="dash-kpi-foot">
                @if ($statusChart['data']->sum() > 0)
                    <div class="mini-seg">
                        @foreach ($statusChart['labels'] as $i => $label)
                            <span style="width:{{ round($statusChart['data'][$i] / max($statusChart['data']->sum(), 1) * 100, 1) }}%; background:{{ $statusChart['colors'][$i] }}"></span>
                        @endforeach
                    </div>
                    <p class="dash-note mt-2.5">
                        {{ $statusChart['labels']->map(fn ($l, $i) => $statusChart['data'][$i].' '.strtolower($l))->implode(' · ') }}
                        · {{ $kpis['collegeCount'] }} colleges · {{ $kpis['programCount'] }} broad programs
                    </p>
                @else
                    <p class="dash-note">no projects yet</p>
                @endif
            </div>
        </div>

        {{-- Training hours vs the ANNUAL POOL (R4 / §2.2B — consumption, not a ratio) --}}
        <div class="dash-panel dash-kpi">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon bg-lnu-50 text-lnu-800"><x-sc.icon name="chart" class="w-5 h-5" /></span>
                <span class="badge {{ $kpis['hoursPct'] === null ? 'badge-gray' : ($kpis['hoursPct'] >= 100 ? 'badge-green' : 'badge-blue') }}">
                    {{ $kpis['hoursPct'] === null ? 'no target' : 'target '.number_format($kpis['annualTargetHours']).' hrs' }}
                </span>
            </div>
            <p class="dash-kpi-value" style="color:#003599">{{ number_format($kpis['trainingHours']) }}</p>
            <p class="dash-kpi-label">Training Hours Rendered</p>
            <div class="dash-kpi-foot">
                @if ($kpis['hasTarget'])
                    <div class="relative">
                        <div class="progress"><span style="width:{{ $hoursBarPct }}%" class="{{ $hoursTone }}"></span></div>
                        <span class="marker" style="left:100%"></span>
                    </div>
                    <p class="dash-note mt-2.5">
                        {{ (int) round($kpis['hoursPct']) }}% of the annual SmartCEMES target ·
                        {{ number_format($kpis['hoursRemaining']) }} hrs still to be rendered, drawn down by each project's actual hours.
                    </p>
                @else
                    <p class="dash-note">
                        No annual target set for {{ $kpis['ayLabel'] }} — the Director sets it on
                        <a href="{{ route('targets.index') }}" class="dash-head-link">University Targets</a>.
                        {{ number_format($kpis['trainingHours']) }} hrs have been rendered across {{ $kpis['programs'] }} projects.
                    </p>
                @endif
            </div>
        </div>

        {{-- Budget vs allocation --}}
        <div class="dash-panel dash-kpi">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon bg-gold-50 text-gold-700"><x-sc.icon name="wallet" class="w-5 h-5" /></span>
                @if ($kpis['budgetPct'] !== null)
                    <span class="badge badge-yellow">{{ (int) round($kpis['budgetPct']) }}% utilized</span>
                @endif
            </div>
            <p class="dash-kpi-value is-money">₱{{ number_format($kpis['budgetUsed']) }}</p>
            <p class="dash-kpi-label">Budget Utilized of ₱{{ number_format($kpis['allocated']) }}</p>
            <div class="dash-kpi-foot">
                <div class="relative">
                    <div class="progress"><span style="width:{{ $budgetBarPct }}%" class="{{ $kpis['overCount'] > 0 ? 'bg-red-500' : 'bg-lnu-800' }}"></span></div>
                    <span class="marker" style="left:100%"></span>
                </div>
                <p class="dash-note mt-2.5">
                    @if ($kpis['overCount'] > 0)
                        <span class="font-bold text-red-600">{{ $kpis['overCount'] }} project{{ $kpis['overCount'] === 1 ? '' : 's' }} over its allocated budget</span> ·
                    @endif
                    {{ (int) round($kpis['budgetPct'] ?? 0) }}% of the allocated budget, summed from each project's own
                </p>
            </div>
        </div>

        {{-- Pending approvals --}}
        <div class="dash-panel dash-kpi !border-gold-200 !bg-gradient-to-br !from-white !to-gold-50">
            <div class="dash-kpi-top">
                <span class="dash-kpi-icon bg-gold-100 text-gold-700"><x-sc.icon name="clock" class="w-5 h-5" /></span>
                <span class="badge badge-red">action needed</span>
            </div>
            <p class="dash-kpi-value" style="color:#b8860b">{{ $kpis['pendingApprovals'] }}</p>
            <p class="dash-kpi-label">Pending Approvals</p>
            <div class="dash-kpi-foot">
                <p class="dash-note">{{ $kpis['pendingProposals'] }} proposals · {{ $kpis['pendingAvailability'] }} availability · {{ $kpis['pendingHours'] }} rendered hours</p>
                @if ($kpis['pendingAnalyses'] > 0)
                    <a href="{{ route('ai-analysis.index') }}" class="dash-head-link inline-block mt-2">{{ $kpis['pendingAnalyses'] }} AI analysis{{ $kpis['pendingAnalyses'] === 1 ? '' : 'es' }} awaiting approval →</a>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ===================== TRENDS ===================== --}}
<section class="dash-section">
    <div class="dash-sec-head">
        <div>
            <p class="dash-sec-eyebrow">Trends</p>
            <h3 class="dash-sec-title">Hours &amp; budget against target</h3>
        </div>
        <span class="dash-sec-note">Targets exist at university and project level only</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="reveal-item dash-panel">
            <div class="dash-head">
                <h4 class="dash-head-title">Training Hours vs Target</h4>
                <span class="dash-note">by project</span>
            </div>
            <div class="dash-chart h-56"><canvas x-data x-init="
                if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();
                new Chart($refs.c, {
                    type: 'bar',
                    data: {
                        labels: @js($hoursChart['labels']),
                        datasets: [
                            { label: 'Annual target', data: @js($hoursChart['target']), backgroundColor: '#dbe6fe', borderRadius: 5, maxBarThickness: 16 },
                            { label: 'Rendered', data: @js($hoursChart['rendered']), backgroundColor: '#003599', borderRadius: 5, maxBarThickness: 16 }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: v => Number(v).toLocaleString('en-PH') + ' hrs' }, grid: { drawTicks: false } },
                            x: { grid: { display: false } }
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10.5 } } },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${Number(ctx.parsed.y).toLocaleString('en-PH')} hrs` } }
                        }
                    }
                })
            " x-ref="c"></canvas></div>
            <p class="dash-note mt-3">Rendered hours against each project's annual target — project level only, as no per-program target exists. Projects with no target show a rendered bar only.</p>
        </div>

        <div class="reveal-item dash-panel lg:col-span-2">
            <div class="dash-head">
                <h4 class="dash-head-title">Budget Utilized vs Allocated Budget</h4>
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="dash-note inline-flex items-center gap-1.5"><span class="legend-dot" style="background:#dbe6fe"></span>Allocated budget</span>
                    <span class="dash-note inline-flex items-center gap-1.5"><span class="legend-dot bg-gold-500"></span>Utilized</span>
                    <span class="dash-note inline-flex items-center gap-1.5"><span class="legend-dot bg-red-500"></span>Over allocation</span>
                </div>
            </div>
            {{-- Compact bullet rows (owner request 2026-09-25): the full project
                 TITLE, a utilization bar per row with a tick at 100%, and the
                 amounts inline. Replaces a grouped bar chart whose x-axis could
                 only carry project codes — a vertical axis has no room for a
                 title, and a code tells the Director nothing. --}}
            <div class="space-y-2.5">
                @forelse ($budgetRows as $row)
                    <div class="flex items-center gap-3" wire:key="dash-budget-{{ $loop->index }}">
                        <span class="min-w-0 flex-1 text-[12px] font-semibold text-charcoal truncate"
                              title="{{ $row['title'] }}">{{ $row['title'] }}</span>
                        <span class="bullet-track w-[150px] shrink-0">
                            <span class="bullet-fill {{ $row['over'] ? 'is-over' : '' }}"
                                  style="width:{{ min((float) ($row['pct'] ?? 0), 100) }}%"></span>
                            <span class="bullet-tick"></span>
                        </span>
                        <span class="w-[118px] shrink-0 text-right text-[11px] text-gray-400 font-medium tabular-nums">
                            ₱{{ number_format($row['actual']) }} / ₱{{ number_format($row['allocated']) }}
                        </span>
                    </div>
                @empty
                    <p class="dash-note italic">No projects yet.</p>
                @endforelse
            </div>

            <p class="dash-note mt-3">
                The bar is utilization against each project's allocated budget; the tick marks 100%.
                Budget has no annual target — the allocation is the figure every project is measured against.
            </p>
        </div>

    </div>
</section>

{{-- ===================== PERFORMANCE LEADERS (R5 §5 step 1) ===================== --}}
<section class="dash-section">
    <div class="dash-sec-head">
        <div>
            <p class="dash-sec-eyebrow">Performance</p>
            <h3 class="dash-sec-title">Performance Leaders</h3>
        </div>
        <span class="dash-sec-note">Ranked by training hours rendered and activities delivered</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Most performing projects --}}
        <div class="reveal-item dash-panel lg:col-span-2">
            <div class="dash-head">
                <p class="dash-head-title">Most Performing Projects</p>
                {{-- Was "All projects →", pointing at the cross-college /projects
                     list. Removed 2026-09-27 (§23): the structure is browsed
                     College → Program → Projects, so this points at the hub's
                     entry point instead of a flat list of everything. --}}
                <a href="{{ route('colleges.index') }}" class="dash-head-link">Manage programs →</a>
            </div>
            <div class="space-y-2.5">
                @forelse ($topProjects as $i => $p)
                    <a href="{{ route('projects.show', $p['id']) }}"
                       class="dash-leader" wire:key="dash-proj-{{ $p['id'] }}">
                        <span class="dash-leader-rank">{{ $rank($i) }}</span>
                        <span class="dash-leader-stripe" style="background:{{ $p['college_color'] ?? '#cbd5e1' }}"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[12.5px] font-bold truncate">{{ $p['short_title'] }}</span>
                            <span class="block text-[10.5px] text-gray-400 font-medium truncate">
                                {{ $p['college'] ?? '—' }} · {{ $p['activities'] }} activit{{ $p['activities'] === 1 ? 'y' : 'ies' }} · {{ $p['trainors'] }} trainor{{ $p['trainors'] === 1 ? '' : 's' }}
                            </span>
                            {{-- Magnitude bar (owner request 2026-09-25): rank alone
                                 cannot show that the leader is 2.5× the runner-up.
                                 The tick is the annual target, on the SAME scale. --}}
                            <span class="leader-bar">
                                <span class="leader-bar-fill" style="width:{{ $p['bar_pct'] }}%"></span>
                                @if ($p['target_pct'] !== null)
                                    <span class="leader-bar-tick" style="left:{{ $p['target_pct'] }}%"></span>
                                @endif
                            </span>
                        </span>
                        <span class="text-right shrink-0">
                            <span class="block text-[13px] font-extrabold text-lnu-800">{{ number_format($p['training_hours'], 1) }}</span>
                            <span class="block text-[10.5px] text-gray-400 font-medium">
                                @if ($p['hours_pct'] === null)
                                    no target
                                @else
                                    {{ (int) round($p['hours_pct']) }}% of target
                                @endif
                            </span>
                        </span>
                    </a>
                @empty
                    <p class="dash-note italic">No projects yet.</p>
                @endforelse
            </div>
            <p class="dash-note mt-3">
                Ranked by training hours rendered then trainees reached. A project with no annual target shows
                <span class="font-bold">no target</span> rather than a percentage against zero.
            </p>
        </div>

        {{-- Most performing faculty --}}
        <div class="reveal-item dash-panel">
            <div class="dash-head">
                <p class="dash-head-title">Most Performing Faculty</p>
                <a href="{{ route('faculty.index') }}" class="dash-head-link">All →</a>
            </div>
            <div class="space-y-2.5">
                @forelse ($topFaculty as $i => $f)
                    <a href="{{ route('faculty.index') }}"
                       class="dash-leader" wire:key="dash-fac-{{ $f['id'] }}">
                        <span class="dash-leader-rank">{{ $rank($i) }}</span>
                        <span class="avatar w-8 h-8 text-[10.5px] shrink-0">{{ $f['initials'] }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[12px] font-bold truncate">{{ $f['name'] }}</span>
                            <span class="block text-[10.5px] text-gray-400 font-medium truncate">
                                {{ $f['college'] ?? '—' }} · {{ $f['projects'] }} project{{ $f['projects'] === 1 ? '' : 's' }}
                            </span>
                            {{-- Bar only, NO target marker: faculty carry no target
                                 (D-R19 — contribution, never attainment), so a
                                 marker here would be a fabricated percentage. --}}
                            <span class="leader-bar">
                                <span class="leader-bar-fill" style="width:{{ $f['bar_pct'] }}%"></span>
                            </span>
                        </span>
                        <span class="text-right shrink-0">
                            <span class="block text-[12.5px] font-extrabold text-lnu-800">{{ number_format($f['rendered_hours'], 1) }}</span>
                            <span class="block text-[10.5px] text-gray-400 font-medium">hrs</span>
                        </span>
                    </a>
                @empty
                    <p class="dash-note italic">No faculty yet.</p>
                @endforelse
            </div>
            <p class="dash-note mt-3">Rendered hours are approved entries only. There is no per-professor target, so no attainment is shown.</p>
        </div>
    </div>
</section>

{{-- ===================== AI DECISION SUPPORT — Director-only (12.1) ===================== --}}
<section class="dash-section">
    <div class="dash-sec-head">
        <div>
            <p class="dash-sec-eyebrow">Intelligence</p>
            <h3 class="dash-sec-title">
                AI Decision Support
                <span class="badge badge-blue">Director-only · aggregated inputs only</span>
            </h3>
        </div>
        <a href="{{ route('ai-analysis.index') }}" class="dash-head-link">Open AI workspace →</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="reveal-item dash-panel !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/50">
            <div class="dash-head">
                <p class="dash-head-title">Community Insights Queue</p>
                <span class="badge badge-gold">{{ $aiDrafts->count() }} draft{{ $aiDrafts->count() === 1 ? '' : 's' }}</span>
            </div>
            <p class="dash-note mb-3">Assessment analyses awaiting Admin approval{{ $aiPending ? " · {$aiPending} generating…" : '' }}</p>
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
            <p class="dash-note mt-3">Failed generations appear here as “Analysis unavailable” with a retry — never a silent error.</p>
        </div>

        <div class="reveal-item dash-panel lg:col-span-2">
            <div class="dash-head">
                <p class="dash-head-title">Project Narratives — latest health label per project</p>
                <a href="{{ route('program-narratives.index') }}" class="dash-head-link">View all narratives →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                    <p class="text-[12px] text-gray-400 italic">No narratives yet — generate one from a project hub.</p>
                @endforelse
            </div>
            @if ($otherNarratives->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    @foreach ($otherNarratives as $n)
                        <span class="badge {{ $n['health'] === 'at-risk' ? 'badge-red' : ($n['health'] === 'needs-attention' ? 'badge-yellow' : 'badge-green') }}">{{ $n['code'] }} · {{ str_replace('-', ' ', $n['health']) }}</span>
                    @endforeach
                    <a href="{{ route('program-narratives.index') }}" class="dash-head-link ml-auto">+ history per project →</a>
                </div>
            @endif
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
