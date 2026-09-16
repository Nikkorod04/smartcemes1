<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Rendered-hours approval queue · approved entries are locked and audit-logged (8.9)</p>
</section>

<section class="mt-2">
    <div class="sc-card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">Awaiting Approval</h3>
            <span class="badge badge-yellow">{{ $queue->where('status', 'pending')->count() }} pending</span>
        </div>
        <table class="sc-table">
            <thead><tr><th>Faculty</th><th>Activity</th><th>Date</th><th class="!text-right">Hours</th><th>Source</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($queue as $e)
                    <tr wire:key="rh-{{ $e->id }}">
                        <td class="font-semibold text-charcoal">{{ $e->faculty->user->name }}</td>
                        <td><p class="font-semibold text-charcoal">{{ $e->activity->title }}</p>
                            <p class="text-[11px] text-gray-400">{{ $e->activity->program?->code }}</p></td>
                        <td class="text-gray-500">{{ $e->date->format('M j, Y') }}</td>
                        <td class="!text-right font-bold">{{ number_format((float) $e->hours, 2) }} hrs</td>
                        <td><span class="badge badge-{{ $e->source === 'auto' ? 'blue' : 'gold' }}">{{ ucfirst($e->source) }}</span></td>
                        <td><span class="badge badge-{{ config('smartcemes.status_colors')[$e->status] ?? 'gray' }}">{{ ucfirst($e->status) }}</span></td>
                        <td class="!text-right row-actions whitespace-nowrap">
                            @if ($e->status === 'pending')
                                <button wire:click="approve({{ $e->id }})" wire:confirm="Approve these rendered hours? The entry becomes locked and immutable." class="btn btn-success-soft !px-2 !py-1 !text-[11px]">Approve</button>
                                <button wire:click="openReject({{ $e->id }})" class="btn btn-danger-soft !px-2 !py-1 !text-[11px]">Reject</button>
                            @elseif ($e->status === 'approved')
                                <span class="text-[11px] text-gray-300">locked</span>
                            @else
                                <span class="text-[11px] text-gray-400" title="{{ $e->remarks }}">see remarks</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if ($queue->isEmpty())
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No rendered-hours entries yet — they auto-draft when an activity is marked completed.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Reject modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showReject', v => open = v)" @keydown.escape.window="$wire.set('showReject', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showReject', false)"></div>
    <form wire:submit="confirmReject" class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop">
        <h3 class="font-extrabold text-[15px] tracking-tight mb-1">Reject Rendered Hours</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">Rejection requires remarks and is sent to the faculty member.</p>
        <div>
            <label class="label">Remarks *</label>
            <textarea rows="3" class="input" wire:model="rejectRemarks" placeholder="e.g. Session log shows partial participation — resubmit with corrected hours."></textarea>
            @error('rejectRemarks') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('showReject', false)">Cancel</button>
            <button type="submit" class="btn btn-danger-soft">Reject with remarks</button>
        </div>
    </form>
</div>
</div>

