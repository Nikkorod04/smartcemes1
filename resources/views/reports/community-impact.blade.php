@extends($pdfLayout ?? 'layouts.report', [
    'reportTitle' => 'Community Partner Impact Summary',
    'subtitle' => 'Projects, reach, and validated assessment outcomes per community',
    'scopeLabel' => $scopeLabel,
])

@section('content')
    {{-- At a glance --}}
    <section class="report-section mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 pdf-tiles">
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-emerald-800">Communities</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-emerald-900">{{ number_format($totals['communities']) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">with activity in this scope</p>
        </div>
        <div class="rounded-xl border border-lnu-100 bg-lnu-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-lnu-800">Projects</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-lnu-900">{{ number_format($totals['projects']) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">linked projects</p>
        </div>
        <div class="rounded-xl border border-gold-100 bg-gold-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-gold-800">Trainees reached</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-gold-900">{{ number_format($totals['served']) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">distinct people</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Training hours</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-charcoal">{{ number_format($totals['training_hours'], 1) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">rendered in this scope</p>
        </div>
    </section>

    @forelse ($communities as $row)
        <section class="report-section mb-6">
            <div class="mb-2 flex flex-wrap items-baseline gap-2">
                <h2 class="text-[14px] font-extrabold tracking-tight text-lnu-800">
                    {{ $row->community->name }}
                </h2>
                <p class="text-[11px] font-medium text-gray-500">
                    {{ trim(implode(', ', array_filter([$row->community->municipality, $row->community->province]))) ?: 'Location not set' }}
                </p>
                <span class="badge {{ $row->community->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($row->community->status) }}</span>
            </div>

            @if ($row->programs->isNotEmpty())
                <table class="sc-table">
                    <thead><tr><th>Linked Projects</th><th>Period</th><th class="!text-right">Trainees Reached</th><th class="!text-right">Training Hours</th><th class="!text-right">Budget Utilization</th></tr></thead>
                    <tbody>
                        @foreach ($row->programs as $p)
                            @php($perf = app(\App\Services\TrainingHoursService::class)->forProject($p))
                            <tr>
                                <td>{{ $p->code }} &middot; {{ $p->title }}</td>
                                <td>{{ $p->planned_start_date?->format('M j, Y') ?? '—' }} – {{ $p->planned_end_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="!text-right font-bold">{{ number_format($perf['trainees']) }}</td>
                                <td class="!text-right font-bold">{{ number_format($perf['actual_hours'], 1) }}</td>
                                <td class="!text-right">{{ $perf['allocated_budget'] > 0 ? round($perf['utilized_budget'] / $perf['allocated_budget'] * 100, 1).'%' : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-[11px] italic text-gray-400">No linked projects in this scope.</p>
            @endif

            @if ($row->summaries->isNotEmpty())
                <p class="mt-2 text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Validated assessment outcomes</p>
                <div class="mt-1 space-y-0.5">
                    @foreach ($row->summaries as $s)
                        <p class="text-[11px] text-gray-600">
                            <span class="font-bold text-charcoal">Q{{ $s->quarter }} {{ $s->year }}</span>
                            &middot; {{ number_format($s->total_responses) }} responses
                            &middot; avg. satisfaction {{ $s->avg_service_satisfaction !== null ? number_format((float) $s->avg_service_satisfaction, 2).' / 5' : '—' }}
                            @php($topInterest = collect($s->livelihood_interests ?? [])->sortDesc()->take(1)->keys()->first())
                            @if ($topInterest)
                                &middot; top training interest: {{ $topInterest }}
                            @endif
                        </p>
                    @endforeach
                </div>
            @endif
        </section>
    @empty
        <section class="report-section">
            <p class="py-8 text-center text-[12.5px] text-gray-400">
                No communities have linked projects or validated assessments in this scope.
            </p>
        </section>
    @endforelse
@endsection
