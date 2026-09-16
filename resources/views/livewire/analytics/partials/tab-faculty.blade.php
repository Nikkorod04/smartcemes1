<div class="mt-4 grid grid-cols-2 gap-4">
    <div class="sc-card p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-[14px]">Approved Rendered Hours</h3>
            <span class="badge badge-blue">per faculty · 8.6 KPI</span>
        </div>
        <div class="relative h-72"><canvas x-data x-init="if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy(); new Chart($refs.c, {{ json_encode(['type' => $facultyChart['type'], 'data' => $facultyChart['data'], 'options' => $facultyChart['options']]) }})" x-ref="c"></canvas></div>
    </div>
    <div class="sc-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Faculty</th><th class="!text-right">Programs</th><th class="!text-right">Activities</th><th class="!text-right">Approved Hrs</th><th class="!text-right">Pending</th></tr></thead>
                <tbody>
                    @forelse ($facultyRows as $f)
                        <tr wire:key="ax-fac-{{ $f->name }}">
                            <td><p class="font-semibold text-charcoal">{{ $f->name }}</p><p class="text-[11px] text-gray-400">{{ $f->department }}</p></td>
                            <td class="!text-right">{{ $f->programsLed }}</td>
                            <td class="!text-right">{{ $f->activities }}</td>
                            <td class="!text-right font-bold text-emerald-600">{{ number_format($f->approvedHours, 2) }}</td>
                            <td class="!text-right {{ $f->pendingHours ? 'text-amber-600 font-bold' : 'text-gray-400' }}">{{ number_format($f->pendingHours, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-gray-400 py-8">No faculty records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>