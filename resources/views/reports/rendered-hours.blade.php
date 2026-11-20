@extends('layouts.report', [
    'reportTitle' => 'Faculty Rendered Hours Report',
    'subtitle' => 'Approved entries only · by faculty, activity and project',
    'scopeLabel' => $scopeLabel,
])

@section('content')
    <section class="report-section mb-6 grid grid-cols-3 gap-3">
        <div class="rounded-xl border border-lnu-100 bg-lnu-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-lnu-800">Faculty</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-lnu-900">{{ number_format($entries->count()) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">with approved entries</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Approved entries</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-charcoal">{{ number_format($entryCount) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">in this scope</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
            <p class="text-[9.5px] font-extrabold uppercase tracking-[0.12em] text-emerald-800">Total approved hours</p>
            <p class="mt-1 text-[22px] font-extrabold leading-none text-emerald-900">{{ number_format($grandTotal, 2) }}</p>
            <p class="mt-1 text-[10px] font-medium text-gray-500">service credit</p>
        </div>
    </section>

    @forelse ($entries as $facultyName => $rows)
        <section class="report-section mb-5">
            <div class="mb-2 flex items-baseline justify-between gap-3">
                <h2 class="text-[13.5px] font-extrabold tracking-tight text-lnu-800">{{ $facultyName }}</h2>
                <p class="text-[11px] font-semibold text-gray-500">{{ number_format($rows->sum('hours'), 2) }} total approved hours</p>
            </div>
            <table class="sc-table">
                <thead><tr><th>Date</th><th>Project</th><th>Activity</th><th class="!text-right">Hours</th><th>Approved</th></tr></thead>
                <tbody>
                    @foreach ($rows->sortBy('date') as $e)
                        <tr>
                            <td>{{ $e->date->format('M j, Y') }}</td>
                            <td>{{ $e->activity?->program?->code ?? '—' }}</td>
                            <td>{{ $e->activity?->title ?? '—' }}</td>
                            <td class="!text-right font-bold">{{ number_format((float) $e->hours, 2) }}</td>
                            <td>{{ $e->approved_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <section class="report-section">
            <p class="py-8 text-center text-[12.5px] text-gray-400">No approved rendered-hours entries in this scope.</p>
        </section>
    @endforelse

    <p class="mt-2 text-[10.5px] leading-relaxed text-gray-500">
        Rendered hours are the service credit a faculty member claims for their own record. They are separate from
        the training hours a project delivers to participants. Approved entries are locked and audit-logged.
    </p>
@endsection
