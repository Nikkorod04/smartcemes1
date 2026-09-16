@extends('layouts.report', ['reportTitle' => 'Program Results Framework', 'subtitle' => $program->code.' · '.$program->title])

@section('content')
<section class="mb-6">
    <table class="kv-wrap w-full">
        <tbody>
            <tr class="kv"><td class="k">Program</td><td class="v">{{ $program->title }} ({{ $program->code }})</td></tr>
            <tr class="kv"><td class="k">Program lead</td><td class="v">{{ $program->programLead?->user?->name ?? '—' }}</td></tr>
            <tr class="kv"><td class="k">Communities</td><td class="v">{{ $program->communities->pluck('name')->implode(', ') ?: '—' }}</td></tr>
            <tr class="kv"><td class="k">Period</td><td class="v">{{ $program->planned_start_date->format('M j, Y') }} – {{ $program->planned_end_date->format('M j, Y') }}</td></tr>
            <tr class="kv"><td class="k">Status</td><td class="v">{{ ucfirst($program->status) }}</td></tr>
        </tbody>
    </table>
</section>

<section>
    <h2 class="font-extrabold text-[14px] tracking-tight text-lnu-800 uppercase mb-3">Objectives — baseline → target → actual</h2>
    <table class="sc-table">
        <thead><tr><th>Objective</th><th>KPI</th><th class="!text-right">Baseline</th><th class="!text-right">Target</th><th class="!text-right">Actual</th><th>Status</th><th>Evidence</th></tr></thead>
        <tbody>
            @forelse ($program->programObjectives as $o)
                @php($actual = $kpi->effectiveActual($o))
                <tr>
                    <td>{{ $o->objective }}</td>
                    <td>{{ $o->kpi_metric ? (config('smartcemes.kpi_metrics')[$o->kpi_metric] ?? $o->kpi_metric) : 'Qualitative · manual' }}</td>
                    <td class="!text-right">{{ $o->baseline_value ?? '—' }}</td>
                    <td class="!text-right">{{ $o->target_value ?? '—' }} {{ $o->unit }}</td>
                    <td class="!text-right font-bold">{{ $actual === null ? '—' : number_format($actual, 1) }} {{ $o->unit }}</td>
                    <td>{{ ucfirst($kpi->statusFor($o)) }}</td>
                    <td class="text-[11.5px]">{{ $o->evidence_notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-gray-400 py-6">No objectives defined.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection