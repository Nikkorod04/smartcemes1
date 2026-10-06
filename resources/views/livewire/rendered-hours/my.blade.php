<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">My rendered hours · adjust down for partial participation (with a note) · approved entries are locked</p>
</section>

<section class="mt-2 grid grid-cols-4 gap-4">
    <div class="sc-card p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Approved total</p>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none text-emerald-600">{{ number_format((float) $approvedTotal, 2) }} hrs</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1">count toward your faculty contribution</p>
    </div>
    <div class="sc-card p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Pending</p>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $entries->where('status', 'pending')->count() }}</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1">awaiting Director approval</p>
    </div>
    <div class="sc-card p-5">
        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Rejected</p>
        <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none text-red-600">{{ $entries->where('status', 'rejected')->count() }}</p>
        <p class="text-[11px] text-gray-400 font-medium mt-1">see remarks</p>
    </div>
    <div class="sc-card p-5 !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/40">
        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Auto-draft rule</p>
        <p class="text-[12.5px] text-gray-600 mt-2 leading-relaxed">Drafts are auto-created when an activity is marked Completed (hours = activity duration). Overnight schedules skip the auto-draft — record those manually.</p>
    </div>
</section>

<section class="mt-4">
    <div class="sc-card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">My Rendered Hours</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Activity</th><th>Date</th><th class="!text-right">Auto Hours</th><th class="!text-right">Current</th><th>Source</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($entries as $e)
                        <tr wire:key="myrh-{{ $e->id }}">
                            <td><p class="font-semibold text-charcoal">{{ $e->activity->title }}</p>
                                <p class="text-[11px] text-gray-400">{{ $e->activity->program?->code }}</p></td>
                            <td class="text-gray-500">{{ $e->date->format('M j, Y') }}</td>
                            <td class="!text-right font-bold">{{ number_format((float) $e->hours, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ config('smartcemes.status_colors')[$e->status] ?? 'gray' }}">{{ ucfirst($e->status) }}</span>
                                @if ($e->isLocked())
                                    <span class="badge badge-gold">locked</span>
                                @endif
                            </td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                @if ($e->status === 'pending')
                                    <button wire:click="startAdjust({{ $e->id }})" class="btn btn-outline !px-2 !py-1 !text-[11px]">Adjust ↓</button>
                                    <button wire:click="submit({{ $e->id }})" wire:confirm="Submit these hours for Director approval?" class="btn btn-primary !px-2 !py-1 !text-[11px]">Submit</button>
                                @else
                                    <span class="text-[11px] text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @if ($e->remarks && $e->status !== 'approved')
                            <tr wire:key="myrh-note-{{ $e->id }}"><td colspan="7" class="!py-2 text-[11.5px] text-gray-400 italic">{{ $e->remarks }}</td></tr>
                        @endif
                    @endforeach
                    @if ($entries->isEmpty())
                        <tr><td colspan="7" class="text-center text-gray-400 py-8">No rendered-hours entries for you yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Adjust modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('editingId', v => open = v !== null)" @keydown.escape.window="$wire.set('editingId', null)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('editingId', null)"></div>
    <form wire:submit="saveAdjust" class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop">
        <h3 class="font-extrabold text-[15px] tracking-tight mb-1">Adjust Hours Down</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">Partial participation only — hours may be adjusted DOWN, never above the activity duration. A note is required.</p>
        <div class="space-y-3">
            <div>
                <label class="label">Adjusted hours *</label>
                <input type="number" step="0.25" min="0" class="input" wire:model="adjustedHours">
                @error('adjustedHours') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Adjustment note *</label>
                <textarea rows="2" class="input" wire:model="adjustNote" placeholder="e.g. Co-led with Prof. Maglasang — partial session."></textarea>
                @error('adjustNote') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('editingId', null)">Cancel</button>
            <button type="submit" class="btn btn-primary">Save adjustment</button>
        </div>
    </form>
</div>
</div>

