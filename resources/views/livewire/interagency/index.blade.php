<div>
    {{-- ===================== FILTERS ===================== --}}
    <section class="pt-6">
        <div class="sc-card rh-filter-panel p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-4 h-4" /></span>
                    <div class="min-w-0">
                        <p class="rh-filter-label">Catalogue guardrail</p>
                        <p class="text-[11.5px] text-gray-500 font-medium mt-1 max-w-xl leading-relaxed">The AI uses these rows as its closed vocabulary. Retired agencies remain visible for history but are no longer available for new referrals.</p>
                        <div class="flex flex-wrap items-center gap-2 mt-2"><span class="tier-badge tier-1">CESO intervention</span><span class="tier-badge tier-2">Interagency referral</span></div>
                    </div>
                </div>
                <button wire:click="create" class="btn btn-primary !shrink-0 !py-2"><x-sc.icon name="plus" class="w-4 h-4" />Add agency</button>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-end gap-2 min-w-0 justify-end">
                    <div class="relative flex-1 min-w-[240px]">
                        <span class="rh-filter-label block mb-2">Search catalogue</span>
                        <div class="sc-search">
                            <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                            <input wire:model.live.debounce.300ms="search" aria-label="Search agency catalogue" class="input !w-full !min-w-0" placeholder="Agency, scope, category, or contact…">
                            @if ($search !== '')
                                <button type="button" wire:click="$set('search', '')" aria-label="Clear catalogue search" class="sc-search__clear">&times;</button>
                            @endif
                        </div>
                    </div>
                    <label class="min-w-[150px]"><span class="rh-filter-label block mb-2">Category</span><select wire:model.live="categoryFilter" aria-label="Filter by intervention category" class="input !w-full"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></label>
                    <label class="min-w-[130px]"><span class="rh-filter-label block mb-2">Status</span><select wire:model.live="statusFilter" aria-label="Filter by agency status" class="input !w-full"><option value="">Any status</option><option value="active">Active only</option><option value="retired">Retired only</option></select></label>
                    @if ($search !== '' || $categoryFilter !== '' || $statusFilter !== '')<button type="button" wire:click="clearFilters" class="btn btn-ghost !shrink-0 !py-2 !text-[12px]">Clear</button>@endif
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-semibold text-gray-400"><span><b class="text-charcoal">{{ $activeCount }}</b> active</span><span><b class="text-charcoal">{{ $totalCount - $activeCount }}</b> retired</span><span><b class="text-charcoal">{{ $categoryCount }}</b> categories</span></div>
                <p class="text-[11px] text-gray-400 font-medium">{{ $agencies->count() }} of {{ $totalCount }} {{ \Illuminate\Support\Str::plural('agency', $totalCount) }}</p>
            </div>
        </div>
    </section>

    {{-- ===================== TABLE ===================== --}}
    <section class="mt-3">
        <div class="reveal-item sc-card p-0 overflow-hidden">
            <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-[15px] tracking-tight">Agency catalogue</h3>
                    <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Manage the agencies the AI is allowed to cite</p>
                </div>
                <span class="badge badge-green"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ $activeCount }} active agencies</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Agency</th>
                            <th>Intervention category</th>
                            <th class="min-w-[240px]">Scope owned by the agency</th>
                            <th>Contact</th>
                            <th class="!text-right">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agencies as $agency)
                            <tr wire:key="agency-{{ $agency->id }}" class="rh-row {{ $agency->active ? '' : 'opacity-60' }}">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-9 h-9 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0 text-[10.5px] font-extrabold font-mono">
                                            {{ \Illuminate\Support\Str::limit($agency->agency_code, 5, '') }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-charcoal leading-snug">{{ $agency->agency_name }}</p>
                                            <p class="text-[11px] text-gray-400">
                                                {{ $agency->agency_code }}
                                                @if ($agency->mandate) · {{ $agency->mandate }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-gray">{{ $agency->need_category }}</span>
                                    @if ($agency->mandate)<p class="text-[10.5px] text-gray-400 mt-1.5 leading-snug">{{ \Illuminate\Support\Str::limit($agency->mandate, 70) }}</p>@endif
                                </td>
                                <td class="text-gray-500 text-[12px] leading-snug">
                                    <span class="block max-w-[320px]">{{ $agency->sample_service ?: '—' }}</span>
                                </td>
                                <td class="text-gray-500 text-[12px] leading-snug">{{ $agency->contact_info ?: '—' }}</td>
                                <td class="!text-right">
                                    <span class="badge {{ $agency->active ? 'badge-green' : 'badge-gray' }}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ $agency->active ? 'Active' : 'Retired' }}
                                    </span>
                                </td>
                                <td class="!text-right row-actions whitespace-nowrap">
                                    <button wire:click="edit({{ $agency->id }})"
                                            wire:loading.attr="disabled" class="rh-action btn btn-outline !px-2 !py-1 !text-[11px]">Edit</button>
                                    <button wire:click="toggleActive({{ $agency->id }})"
                                            wire:confirm="{{ $agency->active
                                                ? 'Retire '.$agency->agency_code.'? The AI will stop citing it, but past referrals stay readable.'
                                                : 'Restore '.$agency->agency_code.' to the AI catalogue?' }}"
                                            wire:loading.attr="disabled" class="rh-action btn {{ $agency->active ? 'btn-danger-soft' : 'btn-success-soft' }} !px-2 !py-1 !text-[11px]">
                                        {{ $agency->active ? 'Retire' : 'Restore' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="!py-12 text-center">
                                    <span class="mx-auto w-11 h-11 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center"><x-sc.icon name="shield" class="w-5 h-5" /></span>
                                    <p class="text-[13px] font-semibold text-gray-500 mt-3">No agencies match the current filters</p>
                                    <p class="text-[11.5px] text-gray-400 mt-1">Clear the search or add a new agency to the catalogue.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
        SmartCEMES · Community Extension Services Office · Leyte Normal University
    </footer>

    {{-- ============ Add / edit modal ============ --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="agency-form-title">
            <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="closeForm"></div>
            <form wire:submit="save" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                                <x-sc.icon name="shield" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Agency catalogue</p>
                                <h3 id="agency-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">{{ $editingId ? 'Edit agency' : 'Add agency' }}</h3>
                                <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">{{ $editingId ? 'Keep this catalogue entry accurate so referrals remain grounded and easy to verify.' : 'Add a trusted referral partner to the vocabulary available to the AI.' }}</p>
                            </div>
                        </div>
                        <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" wire:click="closeForm" aria-label="Close agency form">
                            <x-sc.icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                        <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                        <p><span class="font-extrabold">Guardrail note.</span> Only active agencies are sent to the AI. The scope description is used to match needs and should describe what this agency actually delivers.</p>
                    </div>

                    <div class="space-y-6">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Identity</h4>
                                    <p class="text-[11px] font-medium text-gray-400">The stable code and official name used in referrals.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-[150px_1fr]">
                                <div>
                                    <label class="label" for="agency-code">Code <span class="text-red-500">*</span></label>
                                    <input id="agency-code" wire:model="form.agency_code" class="input font-mono uppercase" placeholder="DOH" autocomplete="off">
                                    @error('form.agency_code') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="agency-name">Agency name <span class="text-red-500">*</span></label>
                                    <input id="agency-name" wire:model="form.agency_name" class="input" placeholder="Department of Health" autocomplete="organization">
                                    @error('form.agency_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Referral scope</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Tell the system when this agency is the right hand-off.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="label" for="agency-category">Intervention category <span class="text-red-500">*</span></label>
                                        <input id="agency-category" wire:model="form.need_category" class="input" list="needCategories" placeholder="e.g. Health / medical">
                                        <datalist id="needCategories">
                                            @foreach ($categories as $category)
                                                <option value="{{ $category }}"></option>
                                            @endforeach
                                        </datalist>
                                        @error('form.need_category') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="label" for="agency-mandate">Mandate</label>
                                        <input id="agency-mandate" wire:model="form.mandate" class="input" placeholder="National health services">
                                        @error('form.mandate') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <div>
                                    <label class="label" for="agency-scope">Scope owned by the agency</label>
                                    <textarea id="agency-scope" wire:model="form.sample_service" rows="4" class="input resize-y" placeholder="Medical and dental missions, immunization, health education…"></textarea>
                                    <p class="mt-1.5 text-[10.5px] font-medium leading-relaxed text-gray-400">This description is sent to the AI so it can explain why the agency is an appropriate referral.</p>
                                    @error('form.sample_service') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">3</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Directory details</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Optional information for staff who follow up on the referral.</p>
                                </div>
                            </div>
                            <div>
                                <label class="label" for="agency-contact">Contact information</label>
                                <input id="agency-contact" wire:model="form.contact_info" class="input" placeholder="Regional Office VIII, Palo, Leyte" autocomplete="off">
                                @error('form.contact_info') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-[12px] font-extrabold text-charcoal">Catalogue availability</h4>
                                    <p class="mt-1 max-w-md text-[11px] font-medium leading-relaxed text-gray-500">Active agencies may be cited for new interagency referrals. Retired agencies stay visible for history only.</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-white px-2 py-1 text-[10px] font-bold text-gray-400 ring-1 ring-gray-200">Access</span>
                            </div>
                            <div class="mt-3">
                                <label class="label" for="agency-status">Status <span class="text-red-500">*</span></label>
                                <x-sc.select id="agency-status" model="form.active" :value="$form['active'] ? 1 : 0" :options="[1 => 'Active — citable by the AI', 0 => 'Retired — kept for history only']" placeholder="— select catalogue status —" :search="false" :required="true" :invalid="$errors->has('form.active')" />
                                @error('form.active') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </section>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-ghost" wire:click="closeForm">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-[122px]" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Add agency' }}</span>
                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
