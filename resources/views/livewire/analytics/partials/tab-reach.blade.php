<div class="mt-4 grid grid-cols-2 gap-4">
    <div class="sc-card p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-[14px]">Distinct Beneficiaries Served</h3>
            <span class="badge badge-blue">by barangay</span>
        </div>
        <div class="relative h-72"><canvas x-data x-init="if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy(); new Chart($refs.c, {{ json_encode(['type' => $reachChart['type'], 'data' => $reachChart['data'], 'options' => $reachChart['options']]) }})" x-ref="c"></canvas></div>
    </div>
    <div class="sc-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Municipality / Barangay</th><th class="!text-right">Served</th></tr></thead>
                <tbody>
                    @forelse ($reach as $row)
                        <tr wire:key="ax-reach-{{ $row->barangay }}">
                            <td class="font-semibold text-charcoal">{{ $row->barangay }}</td>
                            <td class="!text-right font-bold">{{ number_format($row->served) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-gray-400 py-8">No attendance data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 text-[11.5px] text-gray-400 border-t border-gray-50">
            Reach = distinct beneficiaries with ≥1 present/late attendance across non-cancelled activities (8.6).
        </div>
    </div>
</div>