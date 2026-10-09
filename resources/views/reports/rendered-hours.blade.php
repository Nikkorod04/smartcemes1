@extends('layouts.report', ['reportTitle' => 'Faculty Rendered Hours Report', 'subtitle' => 'Approved entries only · by activity and project'])

@section('content')
<section>
    @forelse ($entries as $facultyName => $rows)
        <div class="mb-6">
            <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 mb-2">{{ $facultyName }} — {{ $entries[$facultyName]->sum('hours') }} total approved hours</h2>
            <table class="sc-table">
                <thead><tr><th>Date</th><th>Project</th><th>Activity</th><th class="!text-right">Hours</th><th>Approved</th></tr></thead>
                <tbody>
                    @foreach ($entries[$facultyName]->sortBy('date') as $e)
                        <tr>
                            <td>{{ $e->date->format('M j, Y') }}</td>
                            {{-- The relation is `program` on the Activity model, but
                                 the entity is the R2 PROJECT level — the label says
                                 "Project" so the report matches the hierarchy. --}}
                            <td>{{ $e->activity?->program?->code ?? '—' }}</td>
                            <td>{{ $e->activity?->title ?? '—' }}</td>
                            <td class="!text-right font-bold">{{ number_format((float) $e->hours, 2) }}</td>
                            <td>{{ $e->approved_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="text-[12.5px] text-gray-500 italic">No approved rendered-hours entries.</p>
    @endforelse
</section>

<section class="mt-8">
    <p class="text-[12.5px] text-gray-500 italic">Approved entries are locked and audit-logged. Rendered hours are the service credit a faculty member claims for their own record — they are a distinct metric from the <b>training hours</b> a project delivers to other people (trainors × trainees × days). The two are never summed.</p>
</section>
@endsection
