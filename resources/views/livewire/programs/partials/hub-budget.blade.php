<div class="mt-4 space-y-4">
    @php
        // Owner decision 2026-09-26: budget has NO annual target — the project's
        // ALLOCATION is the only budget figure, so it is the denominator. Same
        // denominator as the Overview tab, so the two can never disagree.
        $alloc = $budgetVsAllocation['allocated'];
        $pct = $budgetVsAllocation['pct'] ?? 0;
        $served = max((int) $performance['trainees'], 1);
    @endphp
    <section class="grid grid-cols-4 gap-4">
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Allocated budget</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">₱{{ number_format($alloc) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1 mb-3">the project's allocation — budget carries no annual target</p>
        </div>
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Utilized</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none {{ $over ? 'text-red-600' : '' }}">₱{{ number_format($utilized) }}</p>
            <div class="progress mt-3"><span style="width:{{ min($pct, 130) }}%" class="{{ $over ? 'bg-red-500' : 'bg-lnu-800' }}"></span></div>
            <p class="text-[11px] text-gray-500 font-semibold mt-2">{{ $pct }}% @if ($over)<span class="badge badge-red !text-[10px]"><x-sc.icon name="alert" class="w-3 h-3" /> over</span>@endif</p>
        </div>
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Remaining</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none {{ $remaining < 0 ? 'text-red-600' : '' }}">₱{{ number_format($remaining) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">unspent against the allocation</p>
        </div>
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Cost per Trainee</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">₱{{ number_format($utilized / $served, 2) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">utilized ÷ distinct trainees served</p>
        </div>
    </section>
    <div class="sc-card p-0 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-[14px]">Utilization Entries</h3>
                <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Each entry attaches to the program (required) and optionally to an activity</p>
            </div>
            @if ($canManage)
                <button wire:click="openBudgetForm" class="btn btn-primary"><x-sc.icon name="wallet" class="w-4 h-4" /> Record utilization</button>
            @endif
        </div>
        <table class="sc-table">
            <thead><tr><th>Item</th><th>Charged to</th><th class="!text-right">Amount</th><th>Date used</th><th>Receipt</th></tr></thead>
            <tbody>
                @forelse ($entries as $e)
                    <tr wire:key="bud-{{ $e->id }}">
                        <td><p class="font-semibold text-charcoal">{{ $e->item_name }}</p><p class="text-[11px] text-gray-400">{{ $e->description ?? '' }}</p></td>
                        <td class="text-gray-500">{{ $e->activity?->title ?? 'Program-level' }}</td>
                        <td class="!text-right font-bold whitespace-nowrap">₱{{ number_format((float) $e->amount, 2) }}</td>
                        <td class="text-gray-500">{{ $e->date_used->format('M j, Y') }}</td>
                        <td class="!text-right row-actions">
                            <span class="text-[11.5px] text-gray-400">{{ $e->receipt_reference }}</span>
                            @if ($canManage)
                                <button wire:click="deleteBudgetEntry({{ $e->id }})" wire:confirm="Delete this budget entry?" class="ml-2 btn btn-danger-soft !px-2 !py-1 !text-[11px]"><x-sc.icon name="trash" class="w-3.5 h-3.5" /></button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if ($entries->isEmpty())
                    <tr><td colspan="5" class="text-center text-gray-400 py-8">No utilization entries yet.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
