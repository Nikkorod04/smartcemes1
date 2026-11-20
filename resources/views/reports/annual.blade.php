@extends($pdfLayout ?? 'layouts.report', [
    'reportTitle' => 'Annual Extension Performance Report',
    'subtitle' => 'Academic Year '.$year.' · Institutional report',
    'scopeLabel' => $scopeLabel,
])

@section('content')
    {{-- At a glance: the four figures a reader needs from this report. --}}
    <section class="report-section mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4 pdf-tiles">
        <div class="rounded-xl border border-lnu-100 bg-lnu-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-lnu-800">Projects</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-lnu-900">{{ number_format($summary['projects']) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">in the reporting year</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-emerald-800">Trainees reached</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-emerald-900">{{ number_format($summary['trainees']) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">distinct people</p>
        </div>
        <div class="rounded-xl border border-gold-100 bg-gold-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-gold-800">Training hours</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-gold-900">{{ number_format($summary['training_hours'], 1) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">rendered by projects</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Budget utilization</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-charcoal">{{ $summary['budget_pct'] === null ? '—' : round($summary['budget_pct']).'%' }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">of the allocated budget</p>
        </div>
    </section>

    {{-- I. Project portfolio --}}
    <section class="report-section mb-6">
        <h2 class="mb-2 text-[13.5px] font-extrabold uppercase tracking-tight text-lnu-800">I &middot; Project Portfolio</h2>
        <table class="sc-table">
            <thead><tr><th>Code</th><th>Project</th><th>Lead</th><th>Community</th><th>Period</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($programs as $p)
                    <tr>
                        <td class="font-bold">{{ $p->code }}</td>
                        <td>{{ $p->title }}</td>
                        <td>{{ $p->programLead?->user?->name ?? '—' }}</td>
                        <td>{{ $p->communities->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td>{{ $p->planned_start_date?->format('M j, Y') ?? '—' }} – {{ $p->planned_end_date?->format('M j, Y') ?? '—' }}</td>
                        <td>{{ ucfirst($p->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-400">No projects match this scope.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- II. Training delivery --}}
    <section class="report-section mb-6">
        <h2 class="mb-2 text-[13.5px] font-extrabold uppercase tracking-tight text-lnu-800">II &middot; Training Delivery by Project</h2>
        <table class="sc-table">
            <thead><tr>
                <th>Project</th>
                <th class="!text-right">Trainors</th>
                <th class="!text-right">Trainees</th>
                <th class="!text-right">Training hours</th>
                <th class="!text-right">Annual target</th>
                <th class="!text-right">Attainment</th>
            </tr></thead>
            <tbody>
                @forelse ($trainingDelivery as $t)
                    <tr>
                        <td>{{ $t['code'] }} &middot; {{ $t['title'] }}</td>
                        <td class="!text-right">{{ $t['trainors'] }}</td>
                        <td class="!text-right">{{ number_format($t['trainees']) }}</td>
                        <td class="!text-right font-bold">{{ number_format($t['training_hours'], 1) }}</td>
                        <td class="!text-right">{{ $t['target_hours'] === null ? '—' : number_format($t['target_hours']) }}</td>
                        <td class="!text-right">{{ $t['hours_pct'] === null ? 'no target' : round($t['hours_pct']).'%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-400">No projects match this scope.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="mt-1.5 text-[10.5px] leading-relaxed text-gray-500">
            Training hours = trainors &times; trainees &times; training days. Trainees use present/late attendance where recorded,
            otherwise the project&rsquo;s participant count.
        </p>
    </section>

    {{-- III. Faculty participation --}}
    <section class="report-section mb-6">
        <h2 class="mb-2 text-[13.5px] font-extrabold uppercase tracking-tight text-lnu-800">III &middot; Faculty Participation</h2>
        <table class="sc-table">
            <thead><tr><th>Faculty</th><th class="!text-right">Projects Led</th><th class="!text-right">Approved Rendered Hours</th></tr></thead>
            <tbody>
                @forelse ($facultyParticipation as $f)
                    <tr><td>{{ $f['name'] }}</td><td class="!text-right">{{ $f['programs'] }}</td><td class="!text-right">{{ number_format($f['hours'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-400">No faculty participation in this scope.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="mt-1.5 text-[10.5px] text-gray-500">Approved rendered hours only; zero-participation faculty are omitted.</p>
    </section>

    {{-- IV. Budget --}}
    <section class="report-section mb-6">
        <h2 class="mb-2 text-[13.5px] font-extrabold uppercase tracking-tight text-lnu-800">IV &middot; Budget Utilization by Project</h2>
        <table class="sc-table">
            <thead><tr><th>Project</th><th class="!text-right">Allocated</th><th class="!text-right">Utilized</th><th class="!text-right">Utilization</th></tr></thead>
            <tbody>
                @forelse ($budgetUtilization as $b)
                    <tr>
                        <td>{{ $b['code'] }} &middot; {{ $b['title'] }}</td>
                        <td class="!text-right">&#8369;{{ number_format($b['allocation']) }}</td>
                        <td class="!text-right">&#8369;{{ number_format($b['utilized']) }}</td>
                        <td class="!text-right {{ $b['over'] ? 'font-bold text-red-600' : '' }}">
                            {{ $b['pct'] === null ? 'no allocation' : round($b['pct']).'%' }}@if ($b['over']) <span class="text-[9px] font-bold">(over)</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">No projects match this scope.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- V. Assessment outcomes --}}
    <section class="report-section mb-2">
        <h2 class="mb-2 text-[13.5px] font-extrabold uppercase tracking-tight text-lnu-800">V &middot; Assessment Outcomes</h2>
        <table class="sc-table">
            <thead><tr><th>Community</th><th>Period</th><th class="!text-right">Responses</th><th class="!text-right">Available for Training</th><th class="!text-right">Avg. Satisfaction</th></tr></thead>
            <tbody>
                @forelse ($summaries as $s)
                    <tr>
                        <td>{{ $s->community?->name ?? '—' }}</td>
                        <td>Q{{ $s->quarter }} {{ $s->year }}</td>
                        <td class="!text-right">{{ number_format($s->total_responses) }}</td>
                        <td class="!text-right">{{ $s->training_availability_percentage !== null ? round((float) $s->training_availability_percentage).'%' : '—' }}</td>
                        <td class="!text-right">{{ $s->avg_service_satisfaction !== null ? number_format((float) $s->avg_service_satisfaction, 2).' / 5' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-gray-400">No validated assessment summaries for this year and scope.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="mt-1.5 text-[10.5px] text-gray-500">Validated submissions only. Figures are not estimates.</p>
    </section>
@endsection
