<div class="mt-4 space-y-4">
    <div class="sc-card p-5">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-bold text-[14px]">Allocated vs Utilized per Program</h3>
            <span class="badge badge-red">{{ $programRows->filter(fn ($r) => $r->over)->count() }} over-allocated (D7)</span>
        </div>
        <div class="relative h-60"><canvas x-data x-init="if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy(); new Chart($refs.c, {{ json_encode(['type' => $budgetChart['type'], 'data' => $budgetChart['data'], 'options' => $budgetChart['options']]) }})" x-ref="c"></canvas></div>
    </div>
    <div class="sc-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Program</th><th class="!text-right">Allocated</th><th class="!text-right">Utilized</th><th class="!text-right">Utilization</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($programRows as $r)
                        <tr wire:key="ax-budget-{{ $r->model->id }}">
                            <td><p class="font-semibold text-charcoal">{{ $r->model->title }}</p><p class="text-[11px] text-gray-400">{{ $r->model->code }}</p></td>
                            <td class="!text-right">₱{{ number_format((float) $r->model->allocated_budget) }}</td>
                            <td class="!text-right">₱{{ number_format($r->model->utilizedBudget()) }}</td>
                            <td class="!text-right font-bold {{ $r->over ? 'text-red-600' : '' }}">{{ $r->budgetUtilization === null ? '—' : round($r->budgetUtilization).'%' }} @if ($r->over)<span class="badge badge-red">⚠ over</span>@endif</td>
                            <td><span class="badge badge-{{ config('smartcemes.status_colors')[$r->model->status] ?? 'gray' }}">{{ ucfirst($r->model->status) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>