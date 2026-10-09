<div>
@php($totalRequests = $isAdmin ? $awaiting->total() + $responded->total() : $myRequests->total())
<section class="pt-6">
    <div class="sc-card rh-filter-panel p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="rh-filter-label">Request status</p>
                <div class="chip-group mt-2">
                    @foreach ([
                        'all' => 'All requests',
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                    ] as $value => $label)
                        <button type="button" wire:click="setStatus('{{ $value }}')" @class(['chip', 'on' => $status === $value])>
                            {{ $label }}
                            @if ($value !== 'all')<span class="{{ $status === $value ? 'text-white/80' : 'text-gray-400' }}">{{ $counts[$value] ?? 0 }}</span>@endif
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-wrap items-end gap-2 min-w-[min(100%,420px)] justify-end">
                <div class="relative flex-1 min-w-[240px]">
                    <span class="rh-filter-label block mb-2">Search requests</span>
                    <div class="sc-search">
                        <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                        <input wire:model.live.debounce.300ms="search" aria-label="Search availability requests" class="input !w-full !min-w-0" placeholder="Activity, faculty, project, or note…">
                        @if ($search !== '')
                            <button type="button" wire:click="$set('search', '')" aria-label="Clear request search" class="sc-search__clear">&times;</button>
                        @endif
                    </div>
                </div>
                @if ($isAdmin)
                    <button wire:click="openCreate" class="btn btn-primary !shrink-0"><x-sc.icon name="clock" class="w-4 h-4" />New request</button>
                @endif
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11.5px] text-gray-400 font-medium"><span wire:loading wire:target="search,status,sortBy" class="rh-spinner mr-1.5"></span>{{ $totalRequests }} matching request{{ $totalRequests === 1 ? '' : 's' }} · page {{ $isAdmin ? ($awaiting->currentPage() > 1 ? $awaiting->currentPage() : $responded->currentPage()) : $myRequests->currentPage() }}</p>
            <p class="text-[11px] text-gray-400">Click a column heading to sort the queue.</p>
        </div>
    </div>
</section>

@if ($isAdmin)
    @if ($status === 'all' || $status === 'pending')
        <section class="mt-4">
            <div class="sc-card p-0 overflow-hidden">
                <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="clock" class="w-[18px] h-[18px] text-gold-600" /> Awaiting response</h3>
                        <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Requests waiting for a faculty response</p>
                    </div>
                    <span class="badge badge-yellow">{{ $awaiting->total() }} pending</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sc-table">
                        <thead><tr><th>Activity</th><th>Faculty</th><th><button type="button" wire:click="sortBy('date')" class="rh-sort-button">Date &amp; time <span class="rh-sort-arrow">{{ $sort === 'date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th><th>Status</th><th>Notes</th></tr></thead>
                        <tbody>
                            @forelse ($awaiting as $r)
                                <tr wire:key="av-{{ $r->id }}" class="rh-row rh-row--pending">
                                    <td><p class="font-semibold text-charcoal">{{ $r->activity?->title }}</p><p class="text-[11px] text-gray-400">{{ $r->activity?->program?->code ?? 'Unassigned project' }}</p></td>
                                    <td><div class="flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="people" class="w-3.5 h-3.5" /></span><span class="font-semibold text-charcoal">{{ $r->faculty?->user?->name ?? '—' }}</span></div></td>
                                    <td class="whitespace-nowrap"><span class="inline-flex items-center gap-1.5 text-gray-600"><x-sc.icon name="calendar" class="w-3.5 h-3.5 text-gray-400" />{{ $r->date->format('M j, Y') }}</span><p class="text-[11px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($r->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($r->end_time)->format('g:i A') }}</p></td>
                                    <td><span class="badge badge-yellow"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>Pending</span></td>
                                    <td class="text-gray-500 max-w-[260px] truncate" title="{{ $r->remarks ?? '' }}">{{ $r->remarks ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-12"><span class="mx-auto w-11 h-11 rounded-2xl bg-yellow-50 text-gold-600 flex items-center justify-center"><x-sc.icon name="clock" class="w-5 h-5" /></span><p class="text-[13px] font-semibold text-gray-500 mt-3">No pending requests</p><p class="text-[11.5px] text-gray-400 mt-1">Create a request or try a different search.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-gray-100">
                    @include('livewire.partials.pagination', ['paginator' => $awaiting])
                </div>
            </div>
        </section>
    @endif

    @if ($status === 'all' || $status === 'accepted' || $status === 'declined')
        <section class="mt-4">
            <div class="sc-card p-0 overflow-hidden">
                <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-extrabold text-[15px] tracking-tight">Response history</h3>
                        <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Accepted and declined availability requests</p>
                    </div>
                    <span class="badge badge-blue">{{ $responded->total() }} responded</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="sc-table">
                        <thead><tr><th>Activity</th><th>Faculty</th><th><button type="button" wire:click="sortBy('date')" class="rh-sort-button">Date &amp; time <span class="rh-sort-arrow">{{ $sort === 'date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th><th><button type="button" wire:click="sortBy('status')" class="rh-sort-button">Status <span class="rh-sort-arrow">{{ $sort === 'status' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th><th>Notes / reason</th></tr></thead>
                        <tbody>
                            @forelse ($responded as $r)
                                <tr wire:key="avr-{{ $r->id }}" class="rh-row rh-row--{{ $r->status === 'accepted' ? 'approved' : 'rejected' }}">
                                    <td><p class="font-semibold text-charcoal">{{ $r->activity?->title }}</p><p class="text-[11px] text-gray-400">{{ $r->activity?->program?->code ?? 'Unassigned project' }}</p></td>
                                    <td><div class="flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="people" class="w-3.5 h-3.5" /></span><span class="font-semibold text-charcoal">{{ $r->faculty?->user?->name ?? '—' }}</span></div></td>
                                    <td class="whitespace-nowrap"><span class="inline-flex items-center gap-1.5 text-gray-600"><x-sc.icon name="calendar" class="w-3.5 h-3.5 text-gray-400" />{{ $r->date->format('M j, Y') }}</span><p class="text-[11px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($r->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($r->end_time)->format('g:i A') }}</p></td>
                                    <td><span class="badge {{ $r->status === 'accepted' ? 'badge-green' : 'badge-red' }}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ ucfirst($r->status) }}</span></td>
                                    <td class="text-gray-500 max-w-[260px] truncate" title="{{ $r->status === 'declined' ? ($r->decline_reason ?? '') : ($r->remarks ?? '') }}">{{ $r->status === 'declined' ? ($r->decline_reason ?? '—') : ($r->remarks ?? '—') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-12"><span class="mx-auto w-11 h-11 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center"><x-sc.icon name="check" class="w-5 h-5" /></span><p class="text-[13px] font-semibold text-gray-500 mt-3">No response history</p><p class="text-[11.5px] text-gray-400 mt-1">Accepted and declined requests will appear here.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-gray-100">
                    @include('livewire.partials.pagination', ['paginator' => $responded])
                </div>
            </div>
        </section>
    @endif
@else
    <section class="mt-4">
        <div class="sc-card p-0 overflow-hidden">
            <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-[15px] tracking-tight">Requests for you</h3>
                    <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Availability requests addressed to you</p>
                </div>
                <span class="badge badge-{{ $status === 'pending' ? 'yellow' : 'blue' }}">{{ $status === 'all' ? 'All requests' : ucfirst($status) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr><th>Activity</th><th><button type="button" wire:click="sortBy('date')" class="rh-sort-button">Date &amp; time <span class="rh-sort-arrow">{{ $sort === 'date' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th><th><button type="button" wire:click="sortBy('status')" class="rh-sort-button">Status <span class="rh-sort-arrow">{{ $sort === 'status' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th><th>Notes</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($myRequests as $r)
                            <tr wire:key="myav-{{ $r->id }}" class="rh-row rh-row--{{ $r->status === 'accepted' ? 'approved' : ($r->status === 'declined' ? 'rejected' : 'pending') }}">
                                <td><p class="font-semibold text-charcoal">{{ $r->activity?->title }}</p><div class="flex flex-wrap items-center gap-1.5 mt-1"><span class="text-[11px] text-gray-400">{{ $r->activity?->program?->code ?? 'Unassigned project' }}</span><span class="text-[11px] text-gray-400">· requested by {{ $r->requester?->name ?? 'Director’s Office' }} {{ $r->requested_at?->diffForHumans() }}</span></div></td>
                                <td class="whitespace-nowrap"><span class="inline-flex items-center gap-1.5 text-gray-600"><x-sc.icon name="calendar" class="w-3.5 h-3.5 text-gray-400" />{{ $r->date->format('M j, Y') }}</span><p class="text-[11px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($r->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($r->end_time)->format('g:i A') }}</p></td>
                                <td><span class="badge badge-{{ config('smartcemes.status_colors')[$r->status === 'accepted' ? 'approved' : ($r->status === 'declined' ? 'rejected' : 'pending')] ?? 'gray' }}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ ucfirst($r->status) }}</span></td>
                                <td class="text-gray-500 max-w-[260px] truncate" title="{{ $r->status === 'declined' ? ($r->decline_reason ?? '') : ($r->remarks ?? '') }}">{{ $r->status === 'declined' ? 'Reason: '.($r->decline_reason ?? '—') : ($r->remarks ?? '—') }}</td>
                                <td class="!text-right row-actions whitespace-nowrap">
                                    @if ($r->status === 'pending')
                                        <button wire:click="accept({{ $r->id }})" wire:confirm="Accept this availability request?" wire:loading.attr="disabled" class="rh-action btn btn-success-soft !px-2 !py-1 !text-[11px]">Accept</button>
                                        <button wire:click="openDecline({{ $r->id }})" wire:loading.attr="disabled" class="rh-action btn btn-danger-soft !px-2 !py-1 !text-[11px]">Decline</button>
                                    @else
                                        <span class="text-[11px] text-gray-400 inline-flex items-center gap-1"><x-sc.icon name="check" class="w-3 h-3 text-emerald-500" /> responded</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-12"><span class="mx-auto w-11 h-11 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center"><x-sc.icon name="clock" class="w-5 h-5" /></span><p class="text-[13px] font-semibold text-gray-500 mt-3">No matching requests</p><p class="text-[11.5px] text-gray-400 mt-1">You have no availability requests for this filter.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100">
                @include('livewire.partials.pagination', ['paginator' => $myRequests])
            </div>
        </div>
        <div class="border-l-4 border-lnu-800 bg-lnu-50 rounded-r-xl p-3.5 mt-4">
            <p class="text-[12px] font-extrabold text-lnu-800">Schedule conflict rule</p>
            <p class="text-[11px] text-lnu-700/80 font-medium mt-1">Accepting is refused if the requested slot overlaps an activity you are already assigned to or an availability request you already accepted.</p>
        </div>
    </section>
@endif

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Admin: create request --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showCreate', v => open = v)" @keydown.escape.window="$wire.set('showCreate', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="availability-create-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showCreate', false)"></div>
    <form wire:submit="saveCreate" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="clock" class="h-5 w-5" /></span>
                    <div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Availability workspace</p><h3 id="availability-create-title" class="mt-1 text-[18px] font-extrabold tracking-tight">New availability request</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Request a faculty member’s availability for a scheduled extension activity.</p></div>
                </div>
                <button type="button" wire:click="$set('showCreate', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close new availability request dialog"><x-sc.icon name="x" class="h-4 w-4" /></button>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900"><x-sc.icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" /><p><span class="font-extrabold">Schedule request.</span> Choose the activity and faculty member first; the date and time must match the activity window.</p></div>
            <div class="space-y-3">
            <div>
                <label class="label">Faculty *</label>
                <x-sc.select model="form.faculty_id" :value="$form['faculty_id']" :options="$facultyOptions->mapWithKeys(fn ($f) => [$f->id => $f->user->name.' · '.$f->department])->all()" placeholder="— select faculty —" :search="true" :required="true" :invalid="$errors->has('form.faculty_id')" />
                @error('form.faculty_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Activity *</label>
                <x-sc.select model="form.activity_id" :value="$form['activity_id']" :options="$activityOptions->mapWithKeys(fn ($a) => [$a->id => $a->title.' ('.($a->program?->code ?? '—').')'])->all()" placeholder="— select activity —" :search="true" :required="true" :invalid="$errors->has('form.activity_id')" />
                @error('form.activity_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="label">Date *</label><input required type="date" class="input" wire:model="form.date" aria-invalid="{{ $errors->has('form.date') ? 'true' : 'false' }}">
                    @error('form.date') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror</div>
                <div><label class="label">Start time *</label><input required type="time" class="input" wire:model="form.start_time" aria-invalid="{{ $errors->has('form.start_time') ? 'true' : 'false' }}">@error('form.start_time') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
                <div><label class="label">End time *</label><input required type="time" class="input" wire:model="form.end_time" aria-invalid="{{ $errors->has('form.end_time') ? 'true' : 'false' }}">@error('form.end_time') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
            </div>
            <div>
                <label class="label">Message to faculty</label>
                <textarea rows="2" class="input" wire:model="form.remarks" placeholder="Why is their presence needed?"></textarea>
            </div>
            </div>
        </div>

        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="$set('showCreate', false)">Cancel</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="saveCreate" class="btn btn-primary"><span wire:loading.remove wire:target="saveCreate">Send request</span><span wire:loading wire:target="saveCreate" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Sending…</span></button>
        </div>
    </form>
</div>

{{-- Faculty: decline modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showDecline', v => open = v)" @keydown.escape.window="$wire.set('showDecline', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="availability-decline-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showDecline', false)"></div>
    <form wire:submit="confirmDecline" class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="x" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Availability workspace</p><h3 id="availability-decline-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Decline availability request</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Provide a clear reason so the Director can follow up or reschedule.</p></div></div>
                <button type="button" wire:click="$set('showDecline', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close decline availability request dialog"><x-sc.icon name="x" class="h-4 w-4" /></button>
            </div>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><p><span class="font-extrabold">This action is logged.</span> The decline reason will be shown to the Director and retained in the request history.</p></div>
            <div>
                <label class="label" for="availability-decline-reason">Reason for declining <span class="text-red-500">*</span></label>
                <textarea id="availability-decline-reason" rows="4" maxlength="2000" class="input resize-y" wire:model="declineReason" aria-invalid="{{ $errors->has('declineReason') ? 'true' : 'false' }}" placeholder="e.g. Class conflict — university accreditation week."></textarea>
                <p class="field-hint">Give enough context for the request to be rescheduled.</p>
                @error('declineReason') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="$set('showDecline', false)">Cancel</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="confirmDecline" class="btn btn-danger-soft"><span wire:loading.remove wire:target="confirmDecline">Decline with reason</span><span wire:loading wire:target="confirmDecline" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Declining…</span></button>
        </div>
    </form>
</div>
</div>
