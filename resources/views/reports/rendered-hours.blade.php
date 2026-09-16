@extends('layouts.report', ['reportTitle' => 'Faculty Rendered Hours Report', 'subtitle' => 'Approved entries only · by activity and program (8.9)'])

@section('content')
<section>
    @forelse ($entries as $facultyName => $rows)
        <div class="mb-6">
            <h2 class="font-extrabold text-[13.5px] tracking-tight text-lnu-800 mb-2">{{ $facultyName }} — {{ $entries[$facultyName]->sum('hours') }} total approved hours</h2>
            <table class="sc-table">
                <thead><tr><th>Date</th><th>Program</th><th>Activity</th><th class="!text-right">Hours</th><th>Approved</th></tr></thead>
                <tbody>
                    @foreach ($entries[$facultyName]->sortBy('date') as $e)
                        <tr>
                            <td>{{ $e->date->format('M j, Y') }}</td>
                            <td>{{ $e->activity?->program?->code }} · {{ $e->activity->title }}</td>
                            <td class="!text-right font-bold">{{ number_format((float) $e->hours, 2) }}</td>
                            <td>{{ $e->approved_at?->format('M j, Y') }}</td>
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
    <p class="text-[12.5px] text-gray-500 italic">Approved entries are locked and audit-logged. Rendered Hours KPI = SUM(approved hours) per faculty per semester (8.6).</p>
</section>
@endsection