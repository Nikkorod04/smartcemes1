<div>
<section class="pt-6">
    <div class="sc-card rh-filter-panel p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="rh-filter-label">Proposal status</p>
                <div class="chip-group mt-2">
                    @foreach ([
                        '' => 'All proposals',
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ] as $value => $label)
                        <button type="button" wire:click="setStatus('{{ $value }}')" @class(['chip', 'on' => $status === $value])>
                            {{ $label }}
                            @if ($value !== '')<span class="{{ $status === $value ? 'text-white/80' : 'text-gray-400' }}">{{ $counts[$value] ?? 0 }}</span>@endif
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="relative flex-1 min-w-[260px] max-w-[420px]">
                <span class="rh-filter-label block mb-2">Search proposals</span>
                <div class="sc-search">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input wire:model.live.debounce.300ms="search" aria-label="Search proposals" class="input !w-full !min-w-0" placeholder="Title, faculty, project, or community…">
                    @if ($search !== '')
                        <button type="button" wire:click="$set('search', '')" aria-label="Clear proposal search" class="sc-search__clear">&times;</button>
                    @endif
                </div>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11.5px] text-gray-400 font-medium"><span wire:loading wire:target="search,status,sortBy" class="rh-spinner mr-1.5"></span>{{ $proposals->total() }} result{{ $proposals->total() === 1 ? '' : 's' }} · page {{ $proposals->currentPage() }} of {{ $proposals->lastPage() }}</p>
            <p class="text-[11px] text-gray-400">Click a column heading to sort the queue.</p>
        </div>
    </div>
</section>

<section class="mt-4">
    <div class="sc-card p-0 overflow-hidden">
        <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-[15px] tracking-tight">Proposal queue</h3>
                <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">{{ $proposals->total() }} matching proposal{{ $proposals->total() === 1 ? '' : 's' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-{{ $status === 'pending' ? 'yellow' : 'blue' }}">{{ $status === '' ? 'All proposals' : ucfirst($status) }}</span>
                @if ($sort !== 'priority')<span class="rh-sort-summary">Sorted by {{ ucfirst($sort) }} {{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr>
                    @foreach ([['title', 'Proposal'], ['faculty', 'Proposer'], ['project', 'Project'], ['dates', 'Dates'], ['budget', 'Budget']] as [$column, $label])
                        <th class="{{ $column === 'budget' ? '!text-right' : '' }}">
                            <button type="button" wire:click="sortBy('{{ $column }}')" wire:loading.attr="disabled" class="rh-sort-button {{ $sort === $column ? 'is-active' : '' }}">
                                <span>{{ $label }}</span><span class="rh-sort-arrow">{{ $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
                            </button>
                        </th>
                    @endforeach
                    <th>
                        <button type="button" wire:click="sortBy('status')" wire:loading.attr="disabled" class="rh-sort-button {{ $sort === 'status' ? 'is-active' : '' }}">
                            <span>Status</span><span class="rh-sort-arrow">{{ $sort === 'status' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span>
                        </button>
                    </th>
                    <th></th>
                </tr></thead>
                <tbody>
                    @forelse ($proposals as $p)
                        <tr wire:key="prop-{{ $p->id }}" class="rh-row rh-row--{{ $p->status }}">
                            <td><p class="font-semibold text-charcoal">{{ $p->title }}</p>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                    <span class="text-[11px] text-gray-400">{{ $p->program?->code ?? 'Unassigned project' }}</span>
                                    @if ($p->documents->count())<span class="badge badge-gray !text-[10px]">{{ $p->documents->count() }} attachment{{ $p->documents->count() === 1 ? '' : 's' }}</span>@endif
                                    @if ($p->special_order_path)<span class="badge badge-gold !text-[10px]">SO attached</span>@endif
                                </div>
                            </td>
                            <td><div class="flex items-center gap-2.5"><span class="w-7 h-7 rounded-lg bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="people" class="w-3.5 h-3.5" /></span><span class="font-semibold text-charcoal">{{ $p->faculty?->user?->name ?? '—' }}</span></div></td>
                            <td><p class="font-semibold text-charcoal">{{ $p->program?->code ?? '—' }}</p><p class="text-[11px] text-gray-400">{{ $p->community?->name ?? 'No community' }}</p></td>
                            <td class="whitespace-nowrap"><span class="inline-flex items-center gap-1.5 text-gray-600"><x-sc.icon name="calendar" class="w-3.5 h-3.5 text-gray-400" />{{ $p->proposed_start_date->format('M j, Y') }} – {{ $p->proposed_end_date->format('M j, Y') }}</span><p class="text-[11px] text-gray-400 mt-1">Submitted {{ $p->submitted_at?->format('M j, Y') ?? '—' }}</p>
                                @if ($p->violatesProgramRange())<span class="conflict-marker ml-1"><x-sc.icon name="alert" class="w-3 h-3" /> out of range</span>@endif
                            </td>
                            <td class="!text-right whitespace-nowrap font-semibold tabular-nums">{{ $p->budget_estimate !== null ? '₱'.number_format((float) $p->budget_estimate) : '—' }}</td>
                            <td><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ ucfirst($p->status) }}</span></td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                <button wire:click="viewDetail({{ $p->id }})" wire:loading.attr="disabled" class="rh-action btn btn-ghost !px-2 !py-1 !text-[11px]">Details</button>
                                @if ($isAdmin && $p->status === 'pending')
                                    <button wire:click="openApprove({{ $p->id }})" wire:loading.attr="disabled" class="rh-action btn btn-success-soft !px-2 !py-1 !text-[11px]">Approve</button>
                                    <button wire:click="openReject({{ $p->id }})" wire:loading.attr="disabled" class="rh-action btn btn-danger-soft !px-2 !py-1 !text-[11px]">Reject</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($proposals->isEmpty())
                        <tr><td colspan="7" class="text-center py-12"><span class="mx-auto w-11 h-11 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center"><x-sc.icon name="doc" class="w-5 h-5" /></span><p class="text-[13px] font-semibold text-gray-500 mt-3">No matching proposals</p><p class="text-[11.5px] text-gray-400 mt-1">Try a different status or search term.</p></td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            @include('livewire.partials.pagination', ['paginator' => $proposals])
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Detail modal --}}
@if ($detail && ! $showApprove && ! $showReject)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="proposal-detail-title">
        <div class="fixed inset-0 sc-modal-backdrop" wire:click="closeModals"></div>
        <section class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
            <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="doc" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Proposal workspace</p><h3 id="proposal-detail-title" class="mt-1 text-[18px] font-extrabold leading-snug tracking-tight">{{ $detail->title }}</h3><p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">{{ $detail->program?->code }} · {{ $detail->community?->name }} · by {{ $detail->faculty?->user?->name }}</p></div></div><div class="flex items-center gap-2 shrink-0"><span class="badge badge-{{ config('smartcemes.status_colors')[$detail->status] ?? 'gray' }}">{{ ucfirst($detail->status) }}</span><button type="button" wire:click="closeModals" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close proposal details"><x-sc.icon name="x" class="w-4 h-4" /></button></div></div></header>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">

            <p class="text-[12.5px] text-gray-600 leading-relaxed">{{ $detail->description ?? 'No description provided.' }}</p>

            <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-gray-50/70 border border-gray-100 mt-4">
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Proposed dates</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->proposed_start_date->format('M j, Y') }} – {{ $detail->proposed_end_date->format('M j, Y') }}</p>
                    @if ($detail->violatesProgramRange())<p class="text-[11px] text-red-600 font-semibold mt-1"><x-sc.icon name="alert" class="w-3 h-3" /> Outside project range — approval blocked (8.8)</p>@endif
                </div>
                <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Budget estimate</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->budget_estimate !== null ? '₱'.number_format((float) $detail->budget_estimate) : '—' }}</p></div>
                @if ($detail->createdActivity)
                    <div class="col-span-2"><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Linked activity</p>
                        <a href="{{ route('projects.show', $detail->program) }}" class="text-[12.5px] font-semibold text-lnu-700 mt-1 inline-block">{{ $detail->createdActivity->title }} (draft · auto-created on approval)</a>
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

            </div>
            <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" wire:click="closeModals" class="btn btn-ghost">Close</button>@if ($isAdmin && $detail->status === 'pending')<button type="button" wire:click="openApprove({{ $detail->id }})" class="btn btn-success-soft">Approve</button><button type="button" wire:click="openReject({{ $detail->id }})" class="btn btn-danger-soft">Reject</button>@endif</div></footer>
        </section>
    </div>
@endif

{{-- Approve modal (Special Order attach) --}}
<div wire:key="proposal-approve-modal" x-data="{ open: false }" x-init="$wire.$watch('showApprove', v => open = v)" @keydown.escape.window="$wire.closeModals()"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="proposal-approve-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.closeModals()"></div>
    <form wire:submit="approve({{ $actionId }})" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="check" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Proposal workflow</p><h3 id="proposal-approve-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Approve proposal</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Confirm the proposal and optionally attach its Special Order before the draft activity is created.</p></div></div><button type="button" wire:click="closeModals" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close approve proposal dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-[11px] leading-relaxed text-emerald-900"><x-sc.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" /><p><span class="font-extrabold">Approval creates a draft activity.</span> The proposal’s dates, community, and project context will be carried into the project hub.</p></div>
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
        </div>

        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="closeModals">Cancel</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="approve" class="btn btn-primary"><span wire:loading.remove wire:target="approve">Approve proposal</span><span wire:loading wire:target="approve" class="rh-spinner"></span></button>
        </div>
    </form>
</div>

{{-- Reject modal --}}
<div wire:key="proposal-reject-modal" x-data="{ open: false }" x-init="$wire.$watch('showReject', v => open = v)" @keydown.escape.window="$wire.closeModals()"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="proposal-reject-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.closeModals()"></div>
    <form wire:submit="reject({{ $actionId }})" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="x" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Proposal workflow</p><h3 id="proposal-reject-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Reject proposal</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Give the proposer a clear reason that can be acted on in the next revision.</p></div></div><button type="button" wire:click="closeModals" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close reject proposal dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-[11px] leading-relaxed text-red-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-red-700" /><p><span class="font-extrabold">This decision is recorded.</span> The rejection reason will be visible to the proposer and included in the proposal history.</p></div>
            <div>
                <label class="label" for="proposal-rejection-reason">Rejection reason <span class="text-red-500">*</span></label>
                <textarea id="proposal-rejection-reason" rows="5" maxlength="2000" class="input resize-y" wire:model="rejectionReason" aria-invalid="{{ $errors->has('rejectionReason') ? 'true' : 'false' }}" placeholder="e.g. Budget exceeds FY allocation ceiling; revise costing or split into two phases."></textarea>
                <p class="field-hint">Be specific about the change required before resubmission.</p>
                @error('rejectionReason') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="closeModals">Cancel</button>
            <button type="submit" wire:loading.attr="disabled" wire:target="reject" class="btn btn-danger-soft"><span wire:loading.remove wire:target="reject">Reject with remarks</span><span wire:loading wire:target="reject" class="rh-spinner"></span></button>
        </div>
    </form>
</div>
</div>

