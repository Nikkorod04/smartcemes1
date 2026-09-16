<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">
        {{ $isAdmin ? 'Admin-initiated availability requests — no approval step: Admin requests, Faculty responds (5.8)' : 'Availability requests addressed to you — accept, or decline with a required reason' }}
    </p>
    @if ($isAdmin)
        <div class="reveal-item flex justify-end mb-1">
            <button wire:click="openCreate" class="btn btn-primary"><x-sc.icon name="clock" class="w-4 h-4" />New Request</button>
        </div>
    @endif
</section>

@if ($isAdmin)
    <section class="mt-2">
        <div class="sc-card p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="clock" class="w-[18px] h-[18px] text-gold-600" /> Awaiting Response</h3>
                <span class="badge badge-yellow">{{ $awaiting->count() }} pending</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr><th>Activity</th><th>Faculty</th><th>Date &amp; Time</th><th>Status</th><th>Notes</th></tr></thead>
                    <tbody>
                        @forelse ($awaiting as $r)
                            <tr wire:key="av-{{ $r->id }}">
                                <td><p class="font-semibold text-charcoal">{{ $r->activity?->title }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $r->activity?->program?->code }}</p></td>
                                <td class="text-gray-500">{{ $r->faculty?->user?->name }}</td>
                                <td class="whitespace-nowrap">{{ $r->date->format('M j, Y') }}<br><span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($r->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($r->end_time)->format('g:i A') }}</span></td>
                                <td><span class="badge badge-yellow">Pending</span></td>
                                <td class="text-gray-500">{{ $r->remarks ?? '—' }}</td>
                            </tr>
                        @endforeach
                        @if ($awaiting->isEmpty())
                            <tr><td colspan="5" class="text-center text-gray-400 py-8">No pending requests — create one with "New Request".</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mt-4">
        <div class="sc-card p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">Responded</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr><th>Activity</th><th>Faculty</th><th>Date &amp; Time</th><th>Status</th><th>Notes / Reason</th></tr></thead>
                    <tbody>
                        @forelse ($responded as $r)
                            <tr wire:key="avr-{{ $r->id }}">
                                <td class="font-semibold text-charcoal">{{ $r->activity?->title }}</td>
                                <td class="text-gray-500">{{ $r->faculty?->user?->name }}</td>
                                <td class="whitespace-nowrap">{{ $r->date->format('M j, Y') }} · {{ \Carbon\Carbon::parse($r->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($r->end_time)->format('g:i A') }}</td>
                                <td><span class="badge {{ $r->status === 'accepted' ? 'badge-green' : 'badge-red' }}">{{ ucfirst($r->status) }}</span></td>
                                <td class="text-gray-500">{{ $r->status === 'declined' ? $r->decline_reason : ($r->remarks ?? '—') }}</td>
                            </tr>
                        @endforeach
                        @if ($responded->isEmpty())
                            <tr><td colspan="5" class="text-center text-gray-400 py-8">No responses yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@else
    <section class="mt-2">
        <div class="sc-card p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">Requests For You</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr><th>Activity</th><th>Date &amp; Time</th><th>Status</th><th>Notes</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($myRequests as $r)
                            <tr wire:key="myav-{{ $r->id }}">
                                <td><p class="font-semibold text-charcoal">{{ $r->activity?->title }}</p>
                                    <p class="text-[11px] text-gray-400">requested by {{ $r->requester?->name }} {{ $r->requested_at?->diffForHumans() }}</p></td>
                                <td class="whitespace-nowrap">{{ $r->date->format('M j, Y') }}<br><span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($r->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($r->end_time)->format('g:i A') }}</span></td>
                                <td><span class="badge badge-{{ config('smartcemes.status_colors')[$r->status === 'accepted' ? 'approved' : ($r->status === 'declined' ? 'rejected' : 'pending')] ?? 'gray' }}">{{ ucfirst($r->status) }}</span></td>
                                <td class="text-gray-500">{{ $r->status === 'declined' ? 'Reason: '.$r->decline_reason : ($r->remarks ?? '—') }}</td>
                                <td class="!text-right row-actions whitespace-nowrap">
                                    @if ($r->status === 'pending')
                                        <button wire:click="accept({{ $r->id }})" class="btn btn-success-soft !px-2 !py-1 !text-[11px]">Accept</button>
                                        <button wire:click="openDecline({{ $r->id }})" class="btn btn-danger-soft !px-2 !py-1 !text-[11px]">Decline</button>
                                    @else
                                        <span class="text-[11px] text-gray-300">responded</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if ($myRequests->isEmpty())
                            <tr><td colspan="5" class="text-center text-gray-400 py-8">No availability requests for you yet.</td></tr>
                        @endif
                    </tbody>
                </table>
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
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showCreate', false)"></div>
    <form wire:submit="saveCreate" class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
        <h3 class="font-extrabold text-[16px] tracking-tight mb-1">New Availability Request</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">Tied to one activity and one faculty member — date/time default from the activity schedule and are editable.</p>

        <div class="space-y-3">
            <div>
                <label class="label">Faculty *</label>
                <select required class="input" wire:model="form.faculty_id">
                    <option value="">— select faculty —</option>
                    @foreach ($facultyOptions as $f)
                        <option value="{{ $f->id }}">{{ $f->user->name }} · {{ $f->department }}</option>
                    @endforeach
                </select>
                @error('form.faculty_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Activity *</label>
                <select required class="input" wire:model="form.activity_id">
                    <option value="">— select activity —</option>
                    @foreach ($activityOptions as $a)
                        <option value="{{ $a->id }}">{{ $a->title }} ({{ $a->program?->code }})</option>
                    @endforeach
                </select>
                @error('form.activity_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="label">Date *</label><input required type="date" class="input" wire:model="form.date">
                    @error('form.date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
                <div><label class="label">Start time *</label><input required type="time" class="input" wire:model="form.start_time"></div>
                <div><label class="label">End time *</label><input required type="time" class="input" wire:model="form.end_time"></div>
            </div>
            <div>
                <label class="label">Message to faculty</label>
                <textarea rows="2" class="input" wire:model="form.remarks" placeholder="Why is their presence needed?"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('showCreate', false)">Cancel</button>
            <button type="submit" class="btn btn-primary">Send request</button>
        </div>
    </form>
</div>

{{-- Faculty: decline modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showDecline', v => open = v)" @keydown.escape.window="$wire.set('showDecline', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showDecline', false)"></div>
    <form wire:submit="confirmDecline" class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop">
        <h3 class="font-extrabold text-[16px] tracking-tight mb-1">Decline Availability Request</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">A decline reason is REQUIRED and is shown to the Director.</p>
        <div>
            <label class="label">Reason for declining *</label>
            <textarea rows="3" class="input" wire:model="declineReason" placeholder="e.g. Class conflict — university accreditation week."></textarea>
            @error('declineReason') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('showDecline', false)">Cancel</button>
            <button type="submit" class="btn btn-danger-soft">Decline with reason</button>
        </div>
    </form>
</div>
</div>

