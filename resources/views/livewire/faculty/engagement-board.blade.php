{{--
    Faculty Engagement board (revision §5 R3 / D-R9).

    Mirrors docs/prototype/pages/faculty-management.html:
      - NO page heading, KPI cards or roster table (P0i/P0j/P0k removed them;
        the roster is the separate Faculty Directory page reached from the
        header action).
      - One metric switch drives the chart, the leaderboard AND the insights,
        so they can never disagree.
      - ?college= narrows all three together.

    Chart.js is re-inited on every Livewire render, guarded against the
    "Canvas is already in use" failure documented in the handoff gotchas.
--}}
<div>
    {{-- Deep-link banner: the board is narrowed to one college --}}
    @if ($collegeScope)
        @php $scopedCollege = $colleges->firstWhere('code', strtoupper($collegeScope)); @endphp
        <div class="mt-6 sc-card p-3.5 flex items-center gap-3 flex-wrap">
            <x-sc.college-pill :code="strtoupper($collegeScope)" :name="$scopedCollege?->name" />
            <span class="text-[11.5px] text-gray-400 font-medium">Board narrowed to this college</span>
            <button type="button" wire:click="clearCollegeScope"
                    class="ml-auto btn btn-outline !px-2.5 !py-1.5 text-[11px]">
                Show all colleges
            </button>
        </div>
    @endif

    <section class="mt-6 reveal-item">
        <div class="sc-card p-0 overflow-hidden">

            {{-- ===================== header ===================== --}}
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-gold-50 text-gold-700 flex items-center justify-center">
                        <x-sc.icon name="chart" class="w-4 h-4" />
                    </span>
                    <div>
                        <h2 id="heading" class="font-extrabold text-[15px] tracking-tight leading-tight">
                            Faculty Engagement
                        </h2>
                        <p class="text-[11px] text-gray-400 font-medium">
                            How the extension load is actually distributed across the roster
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <div class="eng-switch" id="engSwitch">
                        {{-- Loop vars are deliberately NOT named $key/$metric: the
                             component has public Livewire properties `$metric`
                             (a string) which Livewire injects into this scope, so
                             a same-named local would be shadowed by the property. --}}
                        @foreach ($definitions as $metricId => $metricDef)
                            <button type="button"
                                    wire:click="setMetric('{{ $metricId }}')"
                                    class="{{ $activeMetric['key'] === $metricDef['key'] ? 'on' : '' }}">
                                {{ $metricDef['short'] }}
                            </button>
                        @endforeach
                    </div>
                    <span class="badge badge-gold">AY {{ now()->format('Y') }}–{{ now()->addYear()->format('Y') }}</span>
                    <a href="{{ route('faculty.directory') }}" class="btn btn-primary !px-3 !py-2 text-[12px]">
                        <x-sc.icon name="users" class="w-4 h-4" /><span class="ml-1">Faculty Directory</span>
                    </a>
                </div>
            </div>

            <div class="p-5 grid grid-cols-1 lg:grid-cols-5 gap-5">

                {{-- ===================== LEFT: chart ===================== --}}
                <div class="lg:col-span-3">
                    <div class="flex items-baseline justify-between mb-3">
                        <div>
                            <p class="font-extrabold text-[14px] tracking-tight">{{ $activeMetric['label'] }}</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5">{{ $activeMetric['sub'] }}</p>
                        </div>
                        <span class="text-[10.5px] text-gray-400 font-semibold whitespace-nowrap">
                            {{ $ranked->count() }} faculty
                        </span>
                    </div>

                    <div class="eng-chart-wrap">
                        <canvas id="engChart" wire:ignore></canvas>
                    </div>

                    {{-- college split --}}
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2.5">
                            <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400">Load by college</p>
                            <div class="eng-split-legend">
                                @foreach ($split as $slice)
                                    <span>
                                        <span class="eng-dot" style="background:{{ $this->collegeColor($slice['code']) }}"></span>
                                        {{ $slice['code'] }} {{ $slice['share'] }}%
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="eng-split">
                            @foreach ($split as $slice)
                                <span style="width:{{ $slice['share'] }}%; background:{{ $this->collegeColor($slice['code']) }}"></span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ===================== RIGHT: leaderboard ===================== --}}
                <div class="lg:col-span-2">
                    <div class="flex items-baseline justify-between mb-3">
                        <p class="font-extrabold text-[14px] tracking-tight">Leaderboard</p>
                        <span class="text-[10.5px] text-gray-400 font-semibold">select to open profile</span>
                    </div>

                    <div id="engRows" class="space-y-2 max-h-[400px] overflow-y-auto pr-0.5">
                        @forelse ($ranked as $index => $row)
                            @php
                                $value = $row[$activeMetric['key']] ?? 0;
                                $max = max($ranked->max(fn ($r) => $r[$activeMetric['key']] ?? 0), 1);
                                $rankClass = $index === 0 ? 'top1' : ($index === 1 ? 'top2' : ($index === 2 ? 'top3' : ''));
                                $isOnLeave = ($row['status'] ?? 'active') === 'on_leave';
                            @endphp
                            <button type="button" class="eng-row" wire:key="row-{{ $row['id'] }}"
                                    wire:click="openFaculty({{ $row['id'] }})">
                                <span class="eng-row-fill"
                                      style="width:{{ max($value / $max * 100, 3) }}%; background:{{ $this->collegeColor($row['college']) }}"></span>
                                <span class="eng-row-inner">
                                    <span class="eng-rank {{ $rankClass }}">{{ $index + 1 }}</span>
                                    <span class="avatar w-8 h-8 text-[10px] shrink-0">{{ $contribution->initials($row['name']) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[12.5px] font-bold truncate">{{ $row['name'] }}</span>
                                        <span class="eng-sub truncate block">
                                            {{ $row['college'] ?? '—' }} ·
                                            {{ $isOnLeave ? 'On leave' : ($row['projects_involved'] ?? 0).' project'.(($row['projects_involved'] ?? 0) === 1 ? '' : 's') }}
                                        </span>
                                    </span>
                                    <span class="text-right shrink-0">
                                        <span class="eng-val">{{ $this->headlineValue($row, $activeMetric) }}</span>
                                        <span class="eng-sub block">{{ $this->headlineCaption($row, $activeMetric) }}</span>
                                    </span>
                                </span>
                            </button>
                        @empty
                            <p class="text-[12px] text-gray-400 font-medium py-6 text-center">
                                No faculty in scope.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100" id="engInsights">
                        @if ($insights)
                            <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400 mb-2.5">
                                What this says
                            </p>
                            <div class="space-y-2">
                                @foreach ($insights as $note)
                                    <div class="eng-note">
                                        <x-sc.icon :name="$note['icon']" class="w-3.5 h-3.5 mt-px {{ $this->toneClass($note['tone']) }}" />
                                        <span>{!! $note['text'] !!}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== FACULTY DRAWER ===================== --}}
    @if ($selected)
        <div class="fixed inset-0 z-[60] bg-charcoal/45 backdrop-blur-[2px] no-print"
             wire:click="closeFaculty"></div>
        <aside class="sc-drawer fixed inset-y-0 right-0 z-[70] w-[460px] bg-white shadow-pop overflow-hidden no-print">
            <div class="min-h-full flex flex-col">
                <div class="p-5 border-b border-gray-100 flex items-start gap-3.5">
                    <span class="avatar w-12 h-12 text-[15px]">{{ $contribution->initials($selected->user?->name) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-extrabold text-[15px] tracking-tight truncate">{{ $selected->user?->name }}</p>
                        <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">{{ $selected->position }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5 items-center">
                            <x-sc.college-pill :code="$selected->college?->code" :name="$selected->college?->name" />
                            <span class="badge badge-{{ $selected->isActive() ? 'green' : 'yellow' }}">
                                {{ $selected->status_label }}
                            </span>
                            <span class="badge badge-gray font-mono">{{ $selected->employee_id }}</span>
                        </div>
                    </div>
                    <button type="button" wire:click="closeFaculty"
                            class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition shrink-0">
                        <x-sc.icon name="x" class="w-4 h-4" />
                    </button>
                </div>

                @php $record = $ranked->firstWhere('id', $selected->id) ?? $rows->firstWhere('id', $selected->id); @endphp

                <div class="flex-1 overflow-y-auto p-5 space-y-5">
                    <div>
                        <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400 mb-2.5">
                            Contribution · AY {{ now()->format('Y') }}–{{ now()->addYear()->format('Y') }}
                        </p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="sc-card p-4">
                                <p class="text-[26px] font-extrabold tracking-tight leading-none">
                                    {{ $this->fmtHours($record['rendered_hours'] ?? 0) }}
                                </p>
                                <p class="text-[11.5px] text-gray-400 font-medium mt-1.5">rendered hours approved</p>
                            </div>
                            <div class="sc-card p-4">
                                <p class="text-[26px] font-extrabold tracking-tight leading-none">
                                    {{ $record['projects_involved'] ?? 0 }}
                                </p>
                                <p class="text-[11.5px] text-gray-400 font-medium mt-1.5">
                                    project{{ ($record['projects_involved'] ?? 0) === 1 ? '' : 's' }} involved
                                </p>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="badge badge-gold">{{ $record['projects_led'] ?? 0 }} lead</span>
                            <span class="badge badge-gray">{{ $record['activities_handled'] ?? 0 }} activities</span>
                            @if (($record['pending_hours'] ?? 0) > 0)
                                <span class="badge badge-yellow">{{ $this->fmtHours($record['pending_hours']) }} hrs pending</span>
                            @endif
                        </div>
                    </div>

                    {{-- The measurable branch is live (R3c); this only shows if the
                         training-hours model is unavailable — say so rather than print a
                         fabricated 0. --}}
                    @unless ($record['training_measurable'] ?? false)
                        <div class="sc-card p-3.5 border border-dashed border-gray-200 bg-gray-50/60">
                            <p class="text-[11.5px] text-gray-500 font-medium leading-relaxed">
                                <b class="text-charcoal">Training hours delivered</b> is not measurable in this
                                environment — the training-hours model is unavailable. Deliberately not shown as 0:
                                a zero would be a claim, and there is no basis for it.
                            </p>
                        </div>
                    @endunless

                    @if ($selected->expertise->isNotEmpty())
                        <div>
                            <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400 mb-2.5">Expertise</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($selected->expertise as $area)
                                    <span class="badge badge-lnu">{{ $area->area }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($selectedProjects->isNotEmpty())
                        <div>
                            <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400 mb-2.5">Projects</p>
                            <div class="space-y-2">
                                @foreach ($selectedProjects as $entry)
                                    <a href="{{ route('projects.show', ['project' => $entry['project']]) }}"
                                       class="flex items-center gap-3 px-3.5 py-3 rounded-xl border border-gray-100 hover:border-lnu-200 hover:bg-lnu-50/40 transition">
                                        <x-sc.college-pill :code="$entry['project']->college?->code" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[12.5px] font-semibold truncate">{{ $entry['project']->title }}</p>
                                            <p class="text-[10.5px] text-gray-400 font-mono truncate">{{ $entry['project']->code }}</p>
                                        </div>
                                        <span class="badge {{ $entry['role'] === 'Lead' ? 'badge-gold' : 'badge-gray' }} !text-[9.5px] !px-2 !py-0.5 shrink-0">
                                            {{ $entry['role'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($selected->specialization || $selected->department)
                        <div>
                            <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400 mb-2.5">Academic</p>
                            <div class="space-y-1.5 text-[12px]">
                                @if ($selected->specialization)
                                    <p><span class="text-gray-400 font-medium">Specialization</span> · {{ $selected->specialization }}</p>
                                @endif
                                @if ($selected->department)
                                    <p><span class="text-gray-400 font-medium">Department</span> · {{ $selected->department }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    @endif

    {{-- ===================== chart bootstrap ===================== --}}
    @script
    <script>
        (function () {
            const palette = { CAS: '#003599', COE: '#F6B800', CME: '#10b981' };
            const list = @js($chartRows);
            const metricLabel = @js($activeMetric['label']);

            const canvas = document.getElementById('engChart');
            if (! canvas) return;

            // Guard the re-init — Livewire morphs can reuse the canvas and a
            // second Chart() on the same element throws "Canvas is already in
            // use", which aborts Alpine init for the whole subtree.
            if (window.Chart && Chart.getChart(canvas)) {
                Chart.getChart(canvas).destroy();
            }

            const shortName = (n) => {
                const parts = String(n || '').replace(/^(Prof\.|Dr\.)\s+/, '').split(' ').filter(Boolean);
                return parts.length < 2 ? (parts[0] || '—') : `${parts[parts.length - 1]} ${parts[0][0]}.`;
            };

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: list.map((r) => shortName(r.name)),
                    datasets: [{
                        label: metricLabel,
                        data: list.map((r) => r.value),
                        backgroundColor: list.map((r) => palette[r.college] || '#93b4fd'),
                        borderRadius: 5,
                        maxBarThickness: 16,
                        // Dim a faculty member on leave so the active load reads cleanly.
                        borderColor: list.map((r) => r.status === 'on_leave' ? '#cbd5e1' : 'transparent'),
                        borderWidth: list.map((r) => r.status === 'on_leave' ? 1.5 : 0),
                    }],
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    layout: { padding: { right: 8 } },
                    animation: { duration: 500 },
                    scales: {
                        x: { beginAtZero: true, grid: { drawTicks: false, color: '#f1f3f7' }, ticks: { font: { size: 10.5 } } },
                        y: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => list[items[0].dataIndex].name,
                                label: (ctx) => {
                                    const r = list[ctx.dataIndex];
                                    return [
                                        ` ${metricLabel}: ${ctx.parsed.x}`,
                                        ` ${r.hours} hrs rendered · ${r.projects} project${r.projects === 1 ? '' : 's'}`,
                                        ` ${r.college || '—'} · ${r.position || '—'} · ${r.statusLabel}`,
                                    ];
                                },
                            },
                        },
                    },
                    onClick: (e, els) => {
                        if (els.length) $wire.openFaculty(list[els[0].index].id);
                    },
                    onHover: (e, els) => {
                        e.native.target.style.cursor = els.length ? 'pointer' : 'default';
                    },
                },
            });
        })();
    </script>
    @endscript
</div>
