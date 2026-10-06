<div>
    {{-- ===================== HEADER ===================== --}}
    <section class="pt-6 reveal-item">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-[20px] font-extrabold tracking-tight leading-tight">Interagency Catalogue</h2>
                <p class="text-[12.5px] text-gray-400 font-medium mt-0.5">
                    The government agencies CESO refers out-of-scope needs to, and the intervention categories each one owns
                </p>
            </div>
            <button wire:click="create" class="btn btn-primary">
                <x-sc.icon name="plus" class="w-4 h-4" />Add agency
            </button>
        </div>
    </section>

    {{-- ===================== WHY THIS EXISTS ===================== --}}
    <section class="mt-4 reveal-item">
        <div class="sc-card p-5 !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/40">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0">
                    <x-sc.icon name="shield" class="w-5 h-5" />
                </span>
                <div class="min-w-0">
                    <h3 class="font-extrabold text-[14px] tracking-tight">How the AI uses this catalogue</h3>
                    <p class="text-[11.5px] text-gray-500 mt-0.5 leading-relaxed max-w-4xl">
                        When a community need falls outside CESO's delivery remit, the AI still surfaces it — but labelled as an
                        <b>interagency intervention</b> and pointed at the responsible agency from this list. Needs that cannot be
                        attributed to any agency are suppressed entirely rather than presented as a CESO recommendation.
                        The AI may cite <b>only</b> the agencies below: a referral naming anything else is rejected.
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="tier-badge tier-1">CESO intervention</span>
                        <span class="tier-badge tier-2">Requires interagency referral</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== SUMMARY TILES ===================== --}}
    <section class="mt-5 grid lg:grid-cols-4 md:grid-cols-2 gap-4">
        @php
            $tiles = [
                [
                    'label' => 'Agencies in the catalogue',
                    'value' => $activeCount,
                    'icon' => 'shield',
                    'tone' => 'bg-lnu-50 text-lnu-800',
                    'note' => $totalCount > $activeCount
                        ? ($totalCount - $activeCount).' retired — no longer citable'
                        : 'All citable by the AI',
                ],
                [
                    'label' => 'Intervention categories',
                    'value' => $categoryCount,
                    'icon' => 'folder',
                    'tone' => 'bg-gold-50 text-gold-700',
                    'note' => 'Distinct referral types',
                ],
                [
                    'label' => 'Referrals raised by the AI',
                    'value' => $referralCount,
                    'icon' => 'sparkles',
                    'tone' => 'bg-lnu-50 text-lnu-800',
                    'note' => $referralCount ? 'From validated summaries' : 'None flagged yet',
                ],
                [
                    'label' => 'Unverified citations',
                    'value' => $unverifiedCount,
                    'icon' => 'bell',
                    'tone' => $unverifiedCount ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600',
                    'note' => $unverifiedCount
                        ? 'Rejected — agency not in catalogue'
                        : 'Every referral resolved',
                ],
            ];
        @endphp

        @foreach ($tiles as $tile)
            <div class="sc-card sc-card-hover p-5 reveal-item">
                <span class="w-10 h-10 rounded-xl {{ $tile['tone'] }} flex items-center justify-center">
                    <x-sc.icon :name="$tile['icon']" class="w-5 h-5" />
                </span>
                <p class="mt-3 text-[24px] font-extrabold tracking-tight leading-none">{{ $tile['value'] }}</p>
                <p class="text-[12px] text-gray-500 font-medium mt-1.5">{{ $tile['label'] }}</p>
                @if ($tile['note'])
                    <p class="text-[10.5px] text-gray-400 font-medium mt-0.5">{{ $tile['note'] }}</p>
                @endif
            </div>
        @endforeach
    </section>

    {{-- ===================== FILTERS ===================== --}}
    <section class="mt-5 flex flex-wrap items-center gap-2">
        <div class="relative">
            <input wire:model.live.debounce.300ms="search" class="input !py-2 !text-[12.5px] w-64 pl-9"
                   placeholder="Search agency, scope or contact…">
            <x-sc.icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        </div>

        <select wire:model.live="categoryFilter" class="input !py-2 !text-[12.5px] w-auto">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}">{{ $category }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="input !py-2 !text-[12.5px] w-auto">
            <option value="">Any status</option>
            <option value="active">Active only</option>
            <option value="retired">Retired only</option>
        </select>

        @if ($search !== '' || $categoryFilter !== '' || $statusFilter !== '')
            <button wire:click="clearFilters" class="btn btn-ghost !py-2 !text-[12px]">Clear</button>
        @endif

        <span class="ml-auto text-[12px] text-gray-400 font-medium">
            {{ $agencies->count() }} of {{ $totalCount }} {{ \Illuminate\Support\Str::plural('agency', $totalCount) }}
        </span>
    </section>

    {{-- ===================== TABLE ===================== --}}
    <section class="mt-3">
        <div class="reveal-item sc-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Agency</th>
                            <th>Intervention category</th>
                            <th class="min-w-[240px]">Scope owned by the agency</th>
                            <th>Contact</th>
                            <th>Order</th>
                            <th class="!text-right">Status</th>
                            <th></th>                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agencies as $agency)
                            <tr wire:key="agency-{{ $agency->id }}" class="{{ $agency->active ? '' : 'opacity-60' }}">
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
                                    @if ($agency->sample_services)
                                        <p class="text-[10.5px] text-gray-400 mt-1.5 leading-snug">
                                            {{ \Illuminate\Support\Str::limit($agency->sample_service, 90) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="text-gray-500 text-[12px] leading-snug">
                                    {{ $agency->sample_service ?: '—' }}
                                </td>
                                <td class="text-gray-500 text-[12px]">
                                    {{ $agency->contact_info ?: '—' }}
                                </td>
                                <td class="text-gray-400 text-[12px] font-mono">{{ $agency->sort_order }}</td>
                                <td class="!text-right">
                                    <span class="badge {{ $agency->active ? 'badge-green' : 'badge-gray' }}">
                                        {{ $agency->active ? 'Active' : 'Retired' }}
                                    </span>
                                </td>
                                <td class="!text-right row-actions whitespace-nowrap">
                                    <button wire:click="edit({{ $agency->id }})"
                                            class="btn btn-outline !px-2 !py-1 !text-[11px]">Edit</button>
                                    <button wire:click="toggleActive({{ $agency->id }})"
                                            wire:confirm="{{ $agency->active
                                                ? 'Retire '.$agency->agency_code.'? The AI will stop citing it, but past referrals stay readable.'
                                                : 'Restore '.$agency->agency_code.' to the AI catalogue?' }}"
                                            class="btn {{ $agency->active ? 'btn-danger-soft' : 'btn-success-soft' }} !px-2 !py-1 !text-[11px]">
                                        {{ $agency->active ? 'Retire' : 'Restore' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="!py-10 text-center">
                                    <p class="text-[13px] font-bold text-gray-500">No agencies match the current filters.</p>
                                    <p class="text-[11.5px] text-gray-400 mt-1">Clear the search or add a new agency to the catalogue.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ===================== AI REFERRALS IN FLIGHT ===================== --}}
    <section class="mt-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-[14px] font-extrabold tracking-tight">Referrals raised by the AI</h3>
                <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">
                    Needs the model flagged as requiring an interagency referral
                </p>
            </div>
            <a href="{{ route('ai-analysis.index') }}" class="text-[12.5px] font-bold text-lnu-800 hover:text-lnu-600 transition">
                Open AI workspace →
            </a>
        </div>

        @if ($referrals->isEmpty())
            <div class="sc-card p-6 text-center lg:col-span-2 mt-3">
                <p class="text-[13px] font-semibold text-gray-500">No interagency referrals have been raised yet.</p>
                <p class="text-[11.5px] text-gray-400 mt-1">
                    Referrals appear here when the AI flags a need outside CESO's delivery remit.
                </p>
            </div>
        @else
            <div class="mt-3 grid lg:grid-cols-2 gap-3">
                @foreach ($referrals as $i => $referral)
                    <div class="sc-card p-4 tier-card tier-2 reveal-item" wire:key="referral-{{ $referral['analysis_id'] }}-{{ $i }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                    <span class="tier-badge tier-2">Requires interagency referral</span>
                                    @if ($referral['community'])
                                        <span class="text-[11px] text-gray-400 font-medium">{{ $referral['community'] }}</span>
                                    @endif
                                </div>
                                <p class="text-[13px] font-bold leading-snug">{{ $referral['need'] }}</p>
                                @if ($referral['rationale'])
                                    <p class="text-[11.5px] text-gray-500 mt-1 leading-relaxed">{{ $referral['rationale'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-gray-100">
                            @if ($referral['agency'])
                                <span class="interagency-note inline-flex items-start gap-2 text-[11.5px]">
                                    <x-sc.icon name="shield" class="w-4 h-4 text-[#b45309] shrink-0 mt-[1px]" />
                                    <span>
                                        Refer to <b>{{ $referral['agency']->label }}</b>
                                        <span class="block text-[10.5px] text-gray-500 mt-0.5">
                                            Outside CESO's training mandate — logged as an interagency intervention, not a CESO activity.
                                        </span>
                                    </span>
                                </span>
                            @else
                                <span class="inline-flex items-start gap-2 text-[11.5px] rounded-xl px-3.5 py-3 bg-red-50 border border-red-200 text-red-700">
                                    <x-sc.icon name="bell" class="w-4 h-4 shrink-0 mt-[1px]" />
                                    <span>
                                        <b>Unverified citation</b> — the model cited
                                        <span class="font-mono">{{ $referral['as_cited']['agency_code'] ?: '(no code)' }}</span>,
                                        which is not in the catalogue. Rejected and recorded for audit.
                                    </span>
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
        SmartCEMES · Community Extension Services Office · Leyte Normal University
    </footer>

    {{-- ============ Add / edit modal ============ --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 p-6 overflow-auto no-print">
            <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeForm"></div>
            <div class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-[15px]">{{ $editingId ? 'Edit agency' : 'Add agency' }}</h3>
                    <button class="btn btn-ghost !px-2 !py-1 !text-[11px]" wire:click="closeForm"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>
                <p class="text-[12px] text-gray-400 font-medium mb-4">
                    Agencies added here become valid targets for the AI's interagency referrals. Only active rows are sent to the model.
                </p>

                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="label">Code *</label>
                            <input wire:model="form.agency_code" class="input font-mono uppercase" placeholder="DOH">
                            @error('form.agency_code') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label class="label">Agency name *</label>
                            <input wire:model="form.agency_name" class="input" placeholder="Department of Health">
                            @error('form.agency_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">Intervention category *</label>
                        <input wire:model="form.need_category" class="input" list="needCategories"
                               placeholder="e.g. Health / medical">
                        <datalist id="needCategories">
                            @foreach ($categories as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                        </datalist>
                        @error('form.need_category') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Scope owned by the agency</label>
                        <textarea wire:model="form.sample_service" rows="2" class="input"
                                  placeholder="Medical and dental missions, immunization…"></textarea>
                        <p class="text-[10.5px] text-gray-400 mt-1">
                            This text is sent to the AI — it is how the model knows what the agency actually delivers.
                        </p>
                        @error('form.sample_service') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Mandate</label>
                            <input wire:model="form.mandate" class="input" placeholder="National health services">
                            @error('form.mandate') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Contact</label>
                            <input wire:model="form.contact_info" class="input" placeholder="Regional Office VIII, Palo, Leyte">
                            @error('form.contact_info') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Catalogue order</label>
                            <input type="number" min="0" max="9999" wire:model="form.sort_order" class="input font-mono">
                            @error('form.sort_order') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Status *</label>
                            <select wire:model="form.active" class="input">
                                <option value="1">Active — citable by the AI</option>
                                <option value="0">Retired — kept for history only</option>
                            </select>
                            @error('form.active') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button class="btn btn-ghost" wire:click="closeForm">Cancel</button>
                    <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                        {{ $editingId ? 'Save changes' : 'Add agency' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
