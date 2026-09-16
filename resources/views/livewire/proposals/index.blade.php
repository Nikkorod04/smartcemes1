<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">
        {{ $isAdmin ? 'Proposal review queue — approvals create draft activities; proposed dates outside the program range block approval (8.8)' : 'Your submitted activity proposals' }}
    </p>
    <div class="reveal-item flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('status', '')" @class(['chip', 'on' => $status === ''])>All</button>
            <button wire:click="$set('status', 'pending')" @class(['chip', 'on' => $status === 'pending'])>Pending</button>
            <button wire:click="$set('status', 'approved')" @class(['chip', 'on' => $status === 'approved'])>Approved</button>
            <button wire:click="$set('status', 'rejected')" @class(['chip', 'on' => $status === 'rejected'])>Rejected</button>
        </div>
        @if (auth()->user()->isFaculty())
            <div class="ml-auto">
                <a href="{{ route('proposals.create') }}" class="btn btn-primary"><x-sc.icon name="doc" class="w-4 h-4" />Submit Proposal</a>
            </div>
        @endif
    </div>
</section>

<section class="mt-4">
    <div class="sc-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr>
                    <th>Proposal</th><th>Proposer</th><th>Program · Community</th>
                    <th>Proposed Dates</th><th>Budget</th><th>Submitted</th><th>Special Order</th><th>Status</th><th></th>
                </th></tr></thead>
                <tbody>
                    @forelse ($proposals as $p)
                        <tr wire:key="prop-{{ $p->id }}">
                            <td><p class="font-semibold text-charcoal">{{ $p->title }}</p>
                                @if ($p->documents->count())<p class="text-[11px] text-gray-400">{{ $p->documents->count() }} attachment{{ $p->documents->count() === 1 ? '' : 's' }}</p>@endif</td>
                            <td class="text-gray-500">{{ $p->faculty?->user?->name }}</td>
                            <td class="text-gray-500">{{ $p->program?->code }} · {{ $p->community?->name }}</td>
                            <td class="whitespace-nowrap">{{ $p->proposed_start_date->format('M j, Y') }} – {{ $p->proposed_end_date->format('M j, Y') }}
                                @if ($p->violatesProgramRange())<span class="conflict-marker ml-1">⚠ out of range</span>@endif
                            </td>
                            <td class="whitespace-nowrap">{{ $p->budget_estimate !== null ? '₱'.number_format((float) $p->budget_estimate) : '—' }}</td>
                            <td class="text-gray-500">{{ $p->submitted_at?->format('M j, Y') }}</td>
                            <td>
                                @if ($p->special_order_path)
                                    <a href="{{ Storage::disk('public')->url($p->special_order_path) }}" download class="badge badge-gold hover:opacity-80 transition" title="Download Special Order">⬇ Attached</a>
                                @else
                                    <span class="text-[11.5px] text-gray-300">—</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span></td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                <button wire:click="viewDetail({{ $p->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Details</button>
                                @if ($isAdmin && $p->status === 'pending')
                                    <button wire:click="openApprove({{ $p->id }})" class="btn btn-success-soft !px-2 !py-1 !text-[11px]">Approve</button>
                                    <button wire:click="openReject({{ $p->id }})" class="btn btn-danger-soft !px-2 !py-1 !text-[11px]">Reject</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($proposals->isEmpty())
                        <tr><td colspan="9" class="text-center text-gray-400 py-8">No proposals{{ $isAdmin ? ' awaiting review.' : ' yet — submit your first proposal.' }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Detail modal --}}
@if ($detail)
    <div class="fixed inset-0 z-50 p-6 overflow-auto no-print" style="display:block">
        <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeModals"></div>
        <div class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h3 class="font-extrabold text-[15.5px] tracking-tight leading-snug">{{ $detail->title }}</h3>
                    <p class="text-[12px] text-gray-400 mt-0.5">{{ $detail->program?->code }} · {{ $detail->community?->name }} · by {{ $detail->faculty?->user?->name }}</p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="badge badge-{{ config('smartcemes.status_colors')[$detail->status] ?? 'gray' }}">{{ ucfirst($detail->status) }}</span>
                    <button wire:click="closeModals" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition">✕</button>
                </div>
            </div>

            <p class="text-[12.5px] text-gray-600 leading-relaxed">{{ $detail->description ?? 'No description provided.' }}</p>

            <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-gray-50/70 border border-gray-100 mt-4">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Proposed dates</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->proposed_start_date->format('M j, Y') }} – {{ $detail->proposed_end_date->format('M j, Y') }}</p>
                    @if ($detail->violatesProgramRange())<p class="text-[11px] text-red-600 font-semibold mt-1">⚠ Outside program range — approval blocked (8.8)</p>@endif
                </div>
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Budget estimate</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->budget_estimate !== null ? '₱'.number_format((float) $detail->budget_estimate) : '—' }}</p></div>
                @if ($detail->createdActivity)
                    <div class="col-span-2"><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Linked activity</p>
                        <a href="{{ route('programs.show', $detail->program) }}" class="text-[12.5px] font-semibold text-lnu-700 mt-1 inline-block">{{ $detail->createdActivity->title }} (draft · auto-created on approval)</a>
                    </div>
                @endif
            </div>

            @if ($detail->documents->count())
                <div class="mt-4">
                    <p class="label">Attachments</p>
                    <ul class="space-y-1.5">
                        @foreach ($detail->documents as $doc)
                            <li>
                                <a href="{{ Storage::disk('public')->url($doc->file_path) }}" download
                                   class="flex items-center gap-2 rounded-lg border border-gray-100 px-3 py-2 text-[12.5px] font-medium text-gray-600 hover:border-lnu-200 hover:bg-blue-50/60 hover:text-lnu-700 transition">
                                    <x-sc.icon name="doc" class="w-4 h-4 text-lnu-600 shrink-0" />
                                    <span class="truncate">{{ $doc->file_name }}</span>
                                    <span class="ml-auto text-[11px] text-gray-400 shrink-0">{{ number_format($doc->file_size / 1024, 0) }} KB · {{ strtoupper($doc->file_type) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($detail->special_order_path)
                <div class="mt-4">
                    <p class="label">Special Order</p>
                    <a href="{{ Storage::disk('public')->url($detail->special_order_path) }}" download
                       class="flex items-center gap-2 rounded-lg border border-gold-200 bg-gold-50/60 px-3 py-2 text-[12.5px] font-semibold text-charcoal hover:border-gold-300 hover:bg-gold-100/70 transition">
                        <span class="badge badge-gold shrink-0">SO</span>
                        <span class="truncate">Special Order (PDF)</span>
                        <span class="ml-auto text-[11px] text-gray-400 shrink-0">⬇ Download</span>
                    </a>
                </div>
            @endif

            <div class="mt-4 space-y-1">
                @if ($detail->admin_remarks)<div class="kv"><span class="k">Admin remarks</span><span class="v">{{ $detail->admin_remarks }}</span></div>@endif
                @if ($detail->rejection_reason)<div class="kv"><span class="k">Rejection reason</span><span class="v !text-red-600">{{ $detail->rejection_reason }}</span></div>@endif
                @if ($detail->admin_approved_at)<div class="kv"><span class="k">Approved</span><span class="v">{{ $detail->approver?->name }} · {{ $detail->admin_approved_at->format('M j, Y g:i A') }}</span></div>@endif
                @if ($detail->rejected_at)<div class="kv"><span class="k">Rejected</span><span class="v">{{ $detail->rejecter?->name }} · {{ $detail->rejected_at->format('M j, Y g:i A') }}</span></div>@endif
            </div>

            @if ($isAdmin && $detail->status === 'pending')
                <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-gray-100">
                    <button wire:click="closeModals" class="btn btn-ghost">Close</button>
                    <button wire:click="openApprove({{ $detail->id }})" class="btn btn-success-soft">Approve</button>
                    <button wire:click="openReject({{ $detail->id }})" class="btn btn-danger-soft">Reject</button>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- Approve modal (Special Order attach) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showApprove', v => open = v)" @keydown.escape.window="$wire.closeModals()"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.closeModals()"></div>
    <form wire:submit="approve({{ $detailId }})" class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop">
        <h3 class="font-extrabold text-[16px] tracking-tight mb-1">Approve Proposal</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">On approval, a draft activity is auto-created in the program hub.</p>

        <div class="space-y-3">
            <div>
                <label class="label">Special Order (PDF, optional)</label>
                <input type="file" wire:model="specialOrderFile" accept=".pdf,application/pdf" class="input !py-2.5 bg-gray-50">
                @error('specialOrderFile') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3">
                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                <p class="text-[12px] text-gray-500 leading-snug">If no Special Order is attached, an informational notice is shown to the faculty — approval is still valid.</p>
            </div>
            <div>
                <label class="label">Remarks (optional)</label>
                <textarea rows="2" class="input" wire:model="adminRemarks" placeholder="Approval remarks…"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="closeModals">Cancel</button>
            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">Approve proposal</button>
        </div>
    </form>
</div>

{{-- Reject modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showReject', v => open = v)" @keydown.escape.window="$wire.closeModals()"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.closeModals()"></div>
    <form wire:submit="reject({{ $detailId }})" class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop">
        <h3 class="font-extrabold text-[16px] tracking-tight mb-1">Reject Proposal</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">A rejection reason is required and is shown to the proposer.</p>
        <div>
            <label class="label">Rejection reason *</label>
            <textarea rows="3" class="input" wire:model="rejectionReason" placeholder="e.g. Budget exceeds FY allocation ceiling; revise costing or split into two phases."></textarea>
            @error('rejectionReason') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="closeModals">Cancel</button>
            <button type="submit" class="btn btn-danger-soft">Reject with remarks</button>
        </div>
    </form>
</div>
</div>

