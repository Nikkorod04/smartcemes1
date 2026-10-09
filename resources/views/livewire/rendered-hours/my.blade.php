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
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="rendered-hours-adjust-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('editingId', null)"></div>
    <form wire:submit="saveAdjust" class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="clock" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Rendered-hours workflow</p>
                        <h3 id="rendered-hours-adjust-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Adjust hours down</h3>
                        <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Record a partial participation adjustment with a clear audit note.</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('editingId', null)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close adjust hours dialog"><x-sc.icon name="x" class="h-4 w-4" /></button>
            </div>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><p><span class="font-extrabold">Hours can only move downward.</span> The adjusted value cannot exceed the activity duration, and a note is required for the audit trail.</p></div>
            <div class="space-y-4">
                <div>
                    <label class="label" for="rendered-hours-adjusted-hours">Adjusted hours <span class="text-red-500">*</span></label>
                    <input id="rendered-hours-adjusted-hours" type="number" step="0.25" min="0" class="input" wire:model="adjustedHours" aria-invalid="{{ $errors->has('adjustedHours') ? 'true' : 'false' }}">
                    @error('adjustedHours') <p class="sc-field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="rendered-hours-adjust-note">Adjustment note <span class="text-red-500">*</span></label>
                    <textarea id="rendered-hours-adjust-note" rows="3" maxlength="2000" class="input resize-none" wire:model="adjustNote" placeholder="e.g. Co-led with Prof. Maglasang — partial session." aria-invalid="{{ $errors->has('adjustNote') ? 'true' : 'false' }}"></textarea>
                    <p class="mt-1.5 text-[11px] text-gray-400">Explain why the recorded hours differ from the activity duration. Maximum 2,000 characters.</p>
                    @error('adjustNote') <p class="sc-field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-ghost" wire:click="$set('editingId', null)">Cancel</button>
                <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="saveAdjust">
                    <span wire:loading.remove wire:target="saveAdjust">Save adjustment</span>
                    <span wire:loading wire:target="saveAdjust" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                </button>
            </div>
        </div>
    </form>
</div>
</div>
