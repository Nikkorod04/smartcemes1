@extends($pdfLayout ?? 'layouts.report', ['reportTitle' => 'Project Performance Report', 'subtitle' => $program->code.' · '.$program->title])

@section('content')
@php
    $hoursSvc = app(\App\Services\TrainingHoursService::class);
    $perf = $hoursSvc->forProject($program);
    // Owner decision 2026-09-26: budget has NO annual target — the allocation is
    // the only budget figure. Hours keep their own annual target.
    $budgetAllocation = $perf['allocated_budget'];
    $over = $budgetAllocation > 0 && $perf['utilized_budget'] > $budgetAllocation;
@endphp

<section class="mb-6">
    <table class="kv-wrap w-full">
        <tbody>
            <tr class="kv"><td class="k">Project</td><td class="v">{{ $program->title }} ({{ $program->code }})</td></tr>
            <tr class="kv"><td class="k">College</td><td class="v">{{ $program->college?->name ?? '—' }}</td></tr>
            <tr class="kv"><td class="k">Broad program</td><td class="v">{{ $program->program?->title ?? '—' }}</td></tr>
            <tr class="kv"><td class="k">Project lead</td><td class="v">{{ $program->programLead?->user?->name ?? '—' }}</td></tr>
            <tr class="kv"><td class="k">Communities</td><td class="v">{{ $program->communities->pluck('name')->implode(', ') ?: '—' }}</td></tr>
            <tr class="kv"><td class="k">Period</td><td class="v">{{ $program->planned_start_date->format('M j, Y') }} – {{ $program->planned_end_date->format('M j, Y') }}</td></tr>
            <tr class="kv"><td class="k">Status</td><td class="v">{{ ucfirst($program->status) }}</td></tr>
        </tbody>
    </table>
</section>

{{-- R4 / D-R7: the results framework (8.6 KPI dictionary) has been replaced by
     the target model. This report presents trainors, trainees and training hours
     rendered against the project's annual HOURS target, and budget against the
     project's ALLOCATION (budget has no annual target — owner decision 2026-09-26). --}}
<section class="mb-6">
    <h2 class="font-extrabold text-[14px] tracking-tight text-lnu-800 uppercase mb-1">Performance against the annual hours target and the allocation</h2>
    <table class="sc-table">
        <thead>
            <tr>
                <th>Measure</th>
                <th class="!text-right">Actual</th>
                <th class="!text-right">Target / allocation</th>
                <th class="!text-right">Progress</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Training hours rendered</td>
                <td class="!text-right font-bold">{{ number_format($perf['actual_hours']) }}</td>
                <td class="!text-right">{{ $perf['target_hours'] === null ? '—' : number_format($perf['target_hours']) }}</td>
                <td class="!text-right font-bold">{{ $perf['hours_pct'] === null ? '—' : $perf['hours_pct'].'%' }}</td>
                <td class="text-[11.5px]">{{ $perf['trainors'] }} trainors × {{ number_format($perf['trainees']) }} trainees × {{ $hoursSvc->formatDays($perf['training_days']) }} days across {{ $perf['completed_count'] }} completed activities</td>
            </tr>
            <tr>
                <td>Trainors assigned</td>
                <td class="!text-right font-bold">{{ $perf['trainors'] }}</td>
                <td class="!text-right">—</td>
                <td class="!text-right">—</td>
                <td class="text-[11.5px]">Lead plus co-lead faculty, counted once per project</td>
            </tr>
            <tr>
                <td>Trainees reached</td>
                <td class="!text-right font-bold">{{ number_format($perf['trainees']) }}</td>
                <td class="!text-right">{{ $program->target_beneficiaries ? number_format((int) $program->target_beneficiaries) : '—' }}</td>
                <td class="!text-right font-bold">{{ $program->target_beneficiaries ? round($perf['trainees'] / (int) $program->target_beneficiaries * 100, 1).'%' : '—' }}</td>
                <td class="text-[11.5px]">Distinct beneficiaries with present or late attendance</td>
            </tr>
            <tr>
                <td>Budget utilized</td>
                <td class="!text-right font-bold {{ $over ? 'text-red-600' : '' }}">₱{{ number_format($perf['utilized_budget']) }}</td>
                <td class="!text-right">₱{{ number_format($budgetAllocation) }} <span class="text-[10px] text-gray-400 font-medium">allocated</span></td>
                <td class="!text-right font-bold {{ $over ? 'text-red-600' : '' }}">{{ $perf['budget_pct'] === null ? '—' : $perf['budget_pct'].'%' }}</td>
                <td class="text-[11.5px]">{{ $over ? 'Over the allocated budget — advisory warning (D7), not a hard block' : 'Utilized against the allocated budget' }}</td>
            </tr>
            <tr>
                <td>Activities</td>
                <td class="!text-right font-bold">{{ $perf['activity_count'] }}</td>
                <td class="!text-right">—</td>
                <td class="!text-right font-bold">{{ $perf['activity_count'] ? round($perf['completed_count'] / $perf['activity_count'] * 100, 1).'%' : '—' }}</td>
                <td class="text-[11.5px]">{{ $perf['completed_count'] }} completed of {{ $perf['activity_count'] }} total</td>
            </tr>
        </tbody>
    </table>
</section>

<section>
    <h2 class="font-extrabold text-[14px] tracking-tight text-lnu-800 uppercase mb-3">Activity detail — trainors, trainees, days, training hours</h2>
    <table class="sc-table">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Date</th>
                <th class="!text-right">Trainors</th>
                <th class="!text-right">Trainees</th>
                <th class="!text-right">Days</th>
                <th class="!text-right">Training hrs</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($perf['rows'] as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $activityDates[$row['activity_id']] ?? '—' }}</td>
                    <td class="!text-right font-semibold">{{ $row['trainors'] }}</td>
                    <td class="!text-right font-semibold">{{ $row['trainees'] > 0 ? number_format($row['trainees']) : '—' }}</td>
                    <td class="!text-right">{{ $hoursSvc->formatDays($row['days']) }}</td>
                    <td class="!text-right font-bold">{{ number_format($row['hours']) }}</td>
                    <td>{{ ucfirst($row['status']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-gray-400 py-6">No activities recorded for this project.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="text-[10.5px] leading-relaxed text-gray-500 mt-2">Figures roll up from the activities recorded in this project. Trainees use present/late attendance where recorded, otherwise the recorded participant count. Days carry the duration: 1.0 full day, 0.5 half day.</p>
</section>
@endsection
