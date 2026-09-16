<div class="mt-4 sc-card p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="sc-table">
            <thead><tr>
                <th>Program</th>
                <th class="!text-right">Participation %</th>
                <th class="!text-right">Completion %</th>
                <th class="!text-right">Consistency %</th>
                <th class="!text-right">Knowledge Gain</th>
                <th class="!text-right">Cost/Beneficiary</th>
                <th class="!text-right">Reach</th>
            </th></tr></thead>
            <tbody>
                @foreach ($programRows as $r)
                    <tr wire:key="ax-perf-{{ $r->model->id }}">
                        <td>
                            <a href="{{ route('programs.show', $r->model) }}" class="font-semibold hover:text-lnu-700">{{ $r->model->title }}</a>
                            <p class="text-[11px] text-gray-400">{{ $r->model->code }} · lead {{ $r->model->programLead?->user?->name ?? '—' }}</p>
                        </td>
                        <td class="!text-right">{{ $r->participation === null ? '—' : round($r->participation).'%'.($r->model->target_beneficiaries ? ' · target ≥'.round($r->reach / $r->model->target_beneficiaries * 100).'%' : '') }}</td>
                        <td class="!text-right">{{ $r->completion === null ? '—' : round($r->completion).'%' }}</td>
                        <td class="!text-right">{{ $r->consistency === null ? '—' : round($r->consistency).'% (target 80)' }}</td>
                        <td class="!text-right">{{ $r->knowledgeGain === null ? '—' : ($r->knowledgeGain >= 0 ? '+' : '').number_format($r->knowledgeGain, 1).' pts' }}</td>
                        <td class="!text-right">{{ $r->costPerBeneficiary === null ? '—' : '₱'.number_format($r->costPerBeneficiary, 2) }}</td>
                        <td class="!text-right font-bold">{{ $r->reach }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>