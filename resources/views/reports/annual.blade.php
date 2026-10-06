@extends('layouts.report', ['reportTitle' => 'Annual Extension Performance Report', 'subtitle' => 'AY '.$year.' · Sections I–VIII'])

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

{{-- II. Training delivery — the R4/R5 headline metric --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">II · Training Delivery by Project</h2>
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
                    <td>{{ $t['code'] }} · {{ $t['title'] }}</td>
                    <td class="!text-right">{{ $t['trainors'] }}</td>
                    <td class="!text-right">{{ number_format($t['trainees']) }}</td>
                    <td class="!text-right font-bold">{{ number_format($t['training_hours'], 1) }}</td>
                    <td class="!text-right">{{ $t['target_hours'] === null ? '—' : number_format($t['target_hours']) }}</td>
                    {{-- NULL over 0: no target is "no target set", never "0%". --}}
                    <td class="!text-right">{{ $t['hours_pct'] === null ? 'no target set' : round($t['hours_pct']).'%' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-gray-400 py-6">No projects for this year.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="text-[12px] text-gray-500 mt-2">
        Training hours = <b>trainors × trainees × days</b>, with no hourly factor (days carries the duration; a half day is 0.5).
        Trainees are distinct beneficiaries with present/late attendance where those records exist, otherwise the recorded participant count.
    </p>
</section>

{{-- III. Beneficiaries served --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-2">III · Trainees Reached</h2>
    <p class="text-[13px]">Total distinct trainees reached this reporting period: <b>{{ number_format($totalServed) }}</b></p>
    <p class="text-[12px] text-gray-500">A distinct-person count: a beneficiary who attended three sessions was reached once. Draws on the same resolution order as every other reach figure in the system.</p>
</section>

{{-- IV. Faculty participation --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">IV · Faculty Participation by Program</h2>
    <table class="sc-table">
        <thead><tr><th>Faculty</th><th class="!text-right">Programs Led</th><th class="!text-right">Approved Rendered Hours</th></tr></thead>
        <tbody>
            @foreach ($facultyParticipation as $f)
                <tr><td>{{ $f['name'] }}</td><td class="!text-right">{{ $f['programs'] }}</td><td class="!text-right">{{ number_format($f['hours'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</section>

{{-- V. Budget utilization by project --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">V · Budget Utilization by Project</h2>
    <table class="sc-table">
        <thead><tr><th>Project</th><th class="!text-right">Allocated budget</th><th class="!text-right">Utilized</th><th class="!text-right">Utilization</th></tr></thead>
        <tbody>
            @forelse ($budgetUtilization as $b)
                <tr>
                    <td>{{ $b['code'] }} · {{ $b['title'] }}</td>
                    <td class="!text-right">₱{{ number_format($b['allocation']) }}</td>
                    <td class="!text-right">₱{{ number_format($b['utilized']) }}</td>
                    <td class="!text-right {{ $b['over'] ? 'text-red-600 font-bold' : '' }}">{{ $b['pct'] === null ? 'no allocation set' : round($b['pct']).'%' }}@if ($b['over']) <span class="badge badge-red !text-[10px]"><x-sc.icon name="alert" class="w-3 h-3" /> over</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-gray-400 py-4">No projects for this year.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{-- VI. Assessment outcomes --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">VI · Assessment Outcomes &amp; Approved Recommendations</h2>
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

{{-- VII. Executive narratives --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-3">VII · Program Executive Narratives</h2>
    <p class="text-[12.5px] text-gray-500 italic">The latest approved ProgramNarrative per project, with generated_at provenance, appears here.</p>
</section>

{{-- VIII. Preparedness statement --}}
<section class="mb-2">
    <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 uppercase mb-2">VIII · Compliance Statement</h2>
    <p class="text-[12.5px] text-gray-600 leading-relaxed">All figures in this report derive from the training-hours and target model: trainors, trainees and training days per activity, measured against each project's annual HOURS target and the University annual pool. Budget has no annual target — it is measured against each project's allocation. A blank attainment means no target has been set — it is never rendered as 0%. Over-allocated budget entries are flagged rather than hidden (D7). Assessment summaries reflect Secretary-validated submissions only.</p>
</section>
@endsection