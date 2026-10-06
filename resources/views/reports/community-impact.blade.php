@extends('layouts.report', ['reportTitle' => 'Community Partner Impact Summary', 'subtitle' => 'Programs, reach, and assessment outcomes per community'])

@section('content')
@foreach ($communities as $row)
    <section class="mb-7">
        <h2 class="font-extrabold text-[14px] tracking-tight text-lnu-800 mb-1">{{ $row->community->name }} · {{ $row->community->municipality }}, {{ $row->community->province }}
            <span class="badge {{ $row->community->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($row->community->status) }}</span>
        </h2>
        <p class="text-[12px] text-gray-500 mb-2">Contact: {{ $row->community->contact_person ?? '—' }} · {{ $row->community->contact_number ?? '—' }}</p>

        <table class="sc-table">
            <thead><tr><th>Linked Projects</th><th>Period</th><th class="!text-right">Trainees Reached</th><th class="!text-right">Training Hours</th><th class="!text-right">Budget Utilization</th></tr></thead>
            <tbody>
                @forelse ($row->programs as $p)
                    @php
                        // R4: reach and training hours both come from the one
                        // training-hours service, so this report cannot disagree
                        // with the project hub or the targets page. Budget is
                        // measured against the project's ALLOCATION — budget has
                        // no annual target (owner decision 2026-09-26).
                        $rowPerf = app(\App\Services\TrainingHoursService::class)->forProject($p);
                        $rowBudgetAllocation = $rowPerf['allocated_budget'];
                    @endphp
                    <tr>
                        <td>{{ $p->code }} · {{ $p->title }}</td>
                        <td>{{ $p->planned_start_date->format('M j, Y') }} – {{ $p->planned_end_date->format('M j, Y') }}</td>
                        <td class="!text-right font-bold">{{ number_format($rowPerf['trainees']) }}</td>
                        <td class="!text-right font-bold">{{ number_format($rowPerf['actual_hours']) }}</td>
                        <td class="!text-right">{{ $rowBudgetAllocation > 0 ? round($rowPerf['utilized_budget'] / $rowBudgetAllocation * 100, 1).'%' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-3 italic">No linked projects yet — prospecting pipeline.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($row->summaries->count())
            <p class="text-[12px] font-bold text-charcoal mt-3">Assessment outcomes &amp; approved recommendations</p>
            @foreach ($row->summaries as $s)
                <p class="text-[12px] text-gray-600 ml-3">• Q{{ $s->quarter }} {{ $s->year }} — {{ $s->total_responses }} responses · avg satisfaction {{ $s->avg_service_satisfaction !== null ? number_format((float) $s->avg_service_satisfaction, 2).' / 5' : '—' }} · top training interest: {{ collect($s->livelihood_interests ?? [])->sortDesc()->take(1)->keys()->first() ?? '—' }}</p>
            @endforeach
        @endif
    </section>
@endforeach
@endsection