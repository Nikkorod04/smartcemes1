<div>
<section class="pt-6">
    <div class="sc-card rh-filter-panel p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="rh-filter-label">Review state</p>
                <div class="chip-group mt-2">
                @foreach ([
                    'all' => 'All entries',
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ] as $value => $label)
                    <button type="button" wire:click="setStatus('{{ $value }}')" @class(['chip', 'on' => $status === $value])>
                        {{ $label }}
                        @if ($value !== 'all')<span class="{{ $status === $value ? 'text-white/80' : 'text-gray-400' }}">{{ $counts[$value] ?? 0 }}</span>@endif
                    </button>
                @endforeach
                </div>
            </div>
            <div class="flex flex-wrap items-end gap-2 min-w-[min(100%,560px)] justify-end">
                <div class="relative flex-1 min-w-[240px]">
                    <span class="rh-filter-label block mb-2">Search records</span>
                    <div class="sc-search">
                        <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                        <input wire:model.live.debounce.300ms="search" aria-label="Search faculty, activity, or project" class="input !w-full !min-w-0" placeholder="Faculty, activity, or project…">
                        @if ($search !== '')
                            <button type="button" wire:click="$set('search', '')" aria-label="Clear search" class="sc-search__clear">&times;</button>
                        @endif
                    </div>
                </div>
                <label class="min-w-[150px]">
                    <span class="rh-filter-label block mb-2">Source</span>
                    <select wire:model.live="source" aria-label="Filter by source" class="input !w-full">
                        <option value="all">All sources</option>
                        <option value="auto">Auto-drafted</option>
                        <option value="manual">Manual</option>
                    </select>
                </label>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11.5px] text-gray-400 font-medium"><span wire:loading wire:target="search,status,source,sortBy" class="rh-spinner mr-1.5"></span>{{ $queue->total() }} result{{ $queue->total() === 1 ? '' : 's' }} · page {{ $queue->currentPage() }} of {{ $queue->lastPage() }}</p>
            <p class="text-[11px] text-gray-400">Click a column heading to sort the queue.</p>
        </div>
    </div>
</section>

<section class="mt-4">
    <div class="sc-card p-0 overflow-hidden">
        <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">Review queue</h3>
                <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">{{ $queue->total() }} matching entr{{ $queue->total() === 1 ? 'y' : 'ies' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-{{ $status === 'pending' ? 'yellow' : 'blue' }}">{{ $status === 'all' ? 'All entries' : ucfirst($status) }}</span>
                @if ($sort !== 'priority')<span class="rh-sort-summary">Sorted by {{ ucfirst($sort) }} {{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr>
                    @foreach ([['faculty', 'Faculty'], ['activity', 'Activity'], ['date', 'Date'], ['hours', 'Hours'], ['source', 'Source'], ['status', 'Status']] as [$column, $label])
                        <th class="{{ $column === 'hours' ? '!text-right' : '' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" wire:loading.attr="disabled" class="rh-sort-button {{ $sort === $column ? 'is-active' : '' }}">
                                <span>{{ $label }}</span><span class="rh-sort-arrow">{{ $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
                            </button>
                        </th>
                    @endforeach
                    <th></th>
                </tr></thead>
                <tbody>
                    @forelse ($queue as $e)
                        <tr wire:key="rh-{{ $e->id }}" class="rh-row rh-row--{{ $e->status }}">
                            <td><div class="flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="people" class="w-3.5 h-3.5" /></span><span class="font-semibold text-charcoal">{{ $e->faculty->user->name }}</span></div></td>
                            <td><p class="font-semibold text-charcoal">{{ $e->activity->title }}</p><p class="text-[11px] text-gray-400">{{ $e->activity->program?->code }}</p></td>
                            <td class="text-gray-500 whitespace-nowrap"><span class="inline-flex items-center gap-1.5"><x-sc.icon name="calendar" class="w-3.5 h-3.5 text-gray-400" />{{ $e->date->format('M j, Y') }}</span></td>
                            <td class="!text-right font-bold tabular-nums">{{ number_format((float) $e->hours, 2) }} hrs</td>
                            <td><span class="badge badge-{{ $e->source === 'auto' ? 'blue' : 'gold' }}">{{ ucfirst($e->source) }}</span></td>
                            <td><span class="badge badge-{{ config('smartcemes.status_colors')[$e->status] ?? 'gray' }}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ ucfirst($e->status) }}</span></td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                @if ($e->status === 'pending')
                                    <button wire:click="approve({{ $e->id }})" wire:confirm="Approve these rendered hours? The entry becomes locked and immutable." wire:loading.attr="disabled" wire:target="approve({{ $e->id }})" class="rh-action rh-action--approve btn btn-success-soft !px-2 !py-1 !text-[11px]"><span wire:loading.remove wire:target="approve({{ $e->id }})">Approve</span><span wire:loading wire:target="approve({{ $e->id }})" class="rh-spinner"></span></button>
                                    <button wire:click="openReject({{ $e->id }})" wire:loading.attr="disabled" wire:target="openReject({{ $e->id }})" class="rh-action rh-action--reject btn btn-danger-soft !px-2 !py-1 !text-[11px]"><span wire:loading.remove wire:target="openReject({{ $e->id }})">Reject</span><span wire:loading wire:target="openReject({{ $e->id }})" class="rh-spinner"></span></button>
                                @elseif ($e->status === 'approved')
                                    <span class="text-[11px] text-gray-400 inline-flex items-center gap-1"><x-sc.icon name="check" class="w-3 h-3 text-emerald-500" /> locked</span>
                                @else
                                    <span class="text-[11px] text-gray-400" title="{{ $e->remarks }}">see remarks</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-12"><span class="mx-auto w-11 h-11 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center"><x-sc.icon name="clock" class="w-5 h-5" /></span><p class="text-[13px] font-semibold text-gray-500 mt-3">No matching rendered-hours entries</p><p class="text-[11.5px] text-gray-400 mt-1">Try a different status, source, or search term.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            @include('livewire.partials.pagination', ['paginator' => $queue])
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Reject modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showReject', v => open = v)" @keydown.escape.window="$wire.set('showReject', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="rendered-hours-reject-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showReject', false)"></div>
    <form wire:submit="confirmReject" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="x" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Rendered-hours review</p><h3 id="rendered-hours-reject-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Reject rendered hours</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Return the entry with remarks so the faculty member can correct and resubmit it.</p></div></div><button type="button" wire:click="$set('showReject', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close reject rendered hours dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-[11px] leading-relaxed text-red-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-red-700" /><p><span class="font-extrabold">Remarks are required.</span> The faculty member will see this reason when the entry is returned.</p></div>
            <div>
                <label class="label" for="rendered-hours-reject-remarks">Remarks <span class="text-red-500">*</span></label>
                <textarea id="rendered-hours-reject-remarks" rows="5" maxlength="2000" class="input resize-y" wire:model="rejectRemarks" aria-invalid="{{ $errors->has('rejectRemarks') ? 'true' : 'false' }}" placeholder="e.g. Session log shows partial participation — resubmit with corrected hours."></textarea>
                @error('rejectRemarks') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="$set('showReject', false)">Cancel</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="confirmReject" class="btn btn-danger-soft"><span wire:loading.remove wire:target="confirmReject">Reject with remarks</span><span wire:loading wire:target="confirmReject" class="rh-spinner"></span></button>
        </div>
    </form>
</div>
</div>
