@extends('layouts.report', ['reportTitle' => 'Annual Extension Performance Report', 'subtitle' => 'AY '.$year.' · Sections I–VII'])

@section('content')
{{-- I. Program Portfolio --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[15px] tracking-tight text-lnu-800 uppercase mb-3">I · Program Portfolio ({{ $programs->count() }} programs)</h2>
    <table class="sc-table">
        <thead><tr><th>Code</th><th>Program</th><th>Lead</th><th>Community</th><th>Period</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($programs as $p)
                <tr>
                    <td class="font-bold">{{ $p->code }}</td>
                    <td>{{ $p->title }}</td>
                    <td>{{ $p->programLead?->user?->name ?? '—' }}</td>
                    <td>{{ $p->communities->pluck('name')->implode(', ') }}</td>
                    <td>{{ $p->planned_start_date->format('M j, Y') }} – {{ $p->planned_end_date->format('M j, Y') }}</td>
                    <td>{{ ucfirst($p->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-gray-400 py-6">No programs for this year.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- II. Beneficiaries served --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-2">II · Community Reach (8.6)</h2>
    <p class="text-[13px]">Total distinct beneficiaries served this reporting period: <b>{{ number_format($totalServed) }}</b></p>
    <p class="text-[12px] text-gray-500">Reach = distinct beneficiaries with at least one present/late attendance across non-cancelled activities.</p>
</section>

{{-- III. Faculty participation --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">III · Faculty Participation by Program</h2>
    <table class="sc-table">
        <thead><tr><th>Faculty</th><th class="!text-right">Programs Led</th><th class="!text-right">Approved Rendered Hours</th></tr></thead>
        <tbody>
            @foreach ($facultyParticipation as $f)
                <tr><td>{{ $f['name'] }}</td><td class="!text-right">{{ $f['programs'] }}</td><td class="!text-right">{{ number_format($f['hours'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</section>

{{-- IV. Budget utilization by project --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">IV · Budget Utilization by Project (8.6)</h2>
    <table class="sc-table">
        <thead><tr><th>Program</th><th class="!text-right">Allocated</th><th class="!text-right">Utilized</th><th class="!text-right">Utilization %</th></tr></thead>
        <tbody>
            @foreach ($budgetUtilization as $b)
                <tr>
                    <td>{{ $b['code'] }} · {{ $b['title'] }}</td>
                    <td class="!text-right">₱{{ number_format($b['allocated']) }}</td>
                    <td class="!text-right">₱{{ number_format($b['utilized']) }}</td>
                    <td class="!text-right {{ $b['pct'] !== null && $b['allocated'] > 0 && $b['utilized'] > $b['allocated'] ? 'text-red-600 font-bold' : '' }}">{{ $b['pct'] === null ? '—' : round($b['pct']).'%' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>

{{-- V. Assessment outcomes --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">V · Assessment Outcomes &amp; Approved Recommendations</h2>
    <table class="sc-table">
        <thead><tr><th>Community</th><th>Period</th><th class="!text-right">Responses</th><th class="!text-right">Training Availability</th><th>Avg Satisfaction</th></tr></thead>
        <tbody>
            @forelse (\App\Models\AssessmentSummary::with('community')->orderBy('year')->orderBy('quarter')->get() as $s)
                <tr>
                    <td>{{ $s->community->name }}</td>
                    <td>Q{{ $s->quarter }} {{ $s->year }}</td>
                    <td class="!text-right">{{ $s->total_responses }}</td>
                    <td class="!text-right">{{ $s->training_availability_percentage !== null ? round((float) $s->training_availability_percentage).'%' : '—' }}</td>
                    <td>{{ $s->avg_service_satisfaction !== null ? number_format((float) $s->avg_service_satisfaction, 2).' / 5' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-gray-400 py-4">No validated summaries yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- VI. Executive narratives (placeholder until AI phase) --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">VI · Program Executive Narratives</h2>
    <p class="text-[12.5px] text-gray-500 italic">Placeholder until the AI phase — the latest approved ProgramNarrative per program with generated_at provenance will appear here.</p>
</section>

{{-- VII. Preparedness statement --}}
<section class="mb-2">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-2">VII · Compliance Statement</h2>
    <p class="text-[12.5px] text-gray-600 leading-relaxed">All figures in this report derive exclusively from the SmartCEMES KPI dictionary (blueprint 8.6). Over-allocated budget entries are flagged rather than hidden (D7). Assessment summaries reflect Secretary-validated submissions only.</p>
</section>
@endsection