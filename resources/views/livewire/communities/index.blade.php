<div>
<section class="pt-6">
    <div class="reveal-item sc-card p-3.5">
        <div class="flex flex-nowrap items-center gap-1.5 overflow-x-auto">
            <div class="flex shrink-0 items-center gap-1">
                <span class="mr-1 text-[10px] font-extrabold uppercase tracking-[0.13em] text-gray-400">View</span>
                <button type="button" wire:click="$set('type', '')" @class(['chip', 'on' => $type === ''])>All</button>
                <button type="button" wire:click="$set('type', 'community')" @class(['chip', 'on' => $type === 'community'])>Communities</button>
                <button type="button" wire:click="$set('type', 'school')" @class(['chip', 'on' => $type === 'school'])>Partner schools</button>
            </div>

            <label class="sc-search w-[150px] shrink-0">
                <span class="sc-search__icon"><x-sc.icon name="search" class="h-4 w-4" /></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="input !bg-white" placeholder="Search name, municipality, or contact…">
                @if ($search)
                    <button type="button" wire:click="$set('search', '')" aria-label="Clear search" class="sc-search__clear"><x-sc.icon name="x" class="h-3.5 w-3.5" /></button>
                @endif
            </label>

            <label class="sr-only" for="community-province-filter">Province</label>
            <select id="community-province-filter" wire:model.live="province" class="input !w-auto !bg-white">
                <option value="">All provinces</option>
                @foreach ($provinces as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
            </select>

            <label class="sr-only" for="community-status-filter">Status</label>
            <select id="community-status-filter" wire:model.live="status" class="input !w-auto !bg-white">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="prospecting">Prospecting</option>
                <option value="archived">Archived</option>
            </select>

            <button type="button" wire:click="create" class="btn btn-primary ml-auto shrink-0 whitespace-nowrap !px-2.5 !text-[11px]">
                <x-sc.icon name="pin" class="w-4 h-4" />Add community / school
            </button>
        </div>
    </div>
</section>

<section class="mt-4">
    @if ($communities->isEmpty())
        <div class="reveal-item sc-card px-5 py-12 text-center">
            @if ($status === 'archived')
                <p class="text-[13.5px] font-semibold text-gray-500">No archived records</p>
                <p class="text-[12px] text-gray-400 mt-1">Archived communities and partner schools appear here.</p>
            @else
                <p class="text-[13.5px] font-semibold text-gray-500">No records match your filters</p>
                <p class="text-[12px] text-gray-400 mt-1">Try a different keyword, type, or province.</p>
            @endif
        </div>
    @else
        <div class="reveal-item sc-card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div>
                    <h3 class="text-[14px] font-extrabold tracking-tight">Partner directory</h3>
                    <p class="mt-0.5 text-[11px] font-medium text-gray-400">Select a record to inspect its relationship history and contact details.</p>
                </div>
                <span class="badge badge-gray">{{ $communities->count() }} shown</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr>
                        <th>Name</th><th>Contact Person</th><th>Contact Number</th>
                        <th class="!text-right">Beneficiaries</th><th class="!text-right">Projects</th><th>Status</th><th></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($communities as $c)
                            <tr wire:key="com-{{ $c->id }}" wire:click="viewDetail({{ $c->id }})" class="group cursor-pointer">
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($c->isSchool())
                                            <span class="w-9 h-9 rounded-xl bg-gold-50 text-gold-700 flex items-center justify-center shrink-0">
                                                <x-sc.icon name="doc" class="w-[18px] h-[18px]" />
                                            </span>
                                        @else
                                            <span class="w-9 h-9 rounded-xl {{ $c->status === 'active' ? 'bg-lnu-50 text-lnu-800' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center shrink-0">
                                                <x-sc.icon name="pin" class="w-[18px] h-[18px]" />
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold text-charcoal truncate">{{ $c->name }}</p>
                                            @if ($c->isSchool())
                                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ ['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education'][$c->school_level] ?? 'School' }} · {{ $c->municipality }}</p>
                                            @else
                                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $c->municipality }}, {{ $c->province }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-gray-500">{{ $c->contact_person ?? '—' }}</td>
                                <td class="text-gray-500 whitespace-nowrap">{{ $c->contact_number ?? '—' }}</td>
                                <td class="!text-right"><span class="font-bold text-charcoal">{{ $c->isSchool() ? '—' : number_format($c->beneficiaries_count) }}</span></td>
                                <td class="!text-right"><span class="font-bold text-charcoal">{{ $c->isSchool() ? '—' : $c->extension_projects_count }}</span></td>
                                <td>
                                    @if ($c->trashed())
                                        <span class="badge badge-gray">Archived</span>
                                    @else
                                        <span class="badge {{ $c->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($c->status) }}</span>
                                    @endif
                                </td>
                                <td class="!text-right row-actions whitespace-nowrap">
                                    <button wire:click.stop="viewDetail({{ $c->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Details</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Create / Edit modal --}}
@if ($showForm)
    <div x-data @keydown.escape.window="$wire.set('showForm', false)"
         class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print"
         role="dialog" aria-modal="true" aria-labelledby="community-form-title">
        <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="$set('showForm', false)"></div>
        <form wire:submit="save" class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
            <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                            <x-sc.icon name="pin" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Partner registry</p>
                            <h3 id="community-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">
                                {{ $editingId ? ($form['type'] === 'school' ? 'Edit partner school' : 'Edit community partner') : 'Add community or school' }}
                            </h3>
                            <p class="mt-1 max-w-xl text-[12px] font-medium leading-relaxed text-white/72">
                                {{ $editingId ? 'Keep this partner record accurate so staff can coordinate delivery and follow-up.' : 'Register a trusted community or school partner for extension planning and delivery.' }}
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="$set('showForm', false)" aria-label="Close community form"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50">
                        <x-sc.icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                    <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                    <p><span class="font-extrabold">Registry note.</span> Communities begin as prospecting partners. School records are treated as active partners by default.</p>
                </div>

                <div class="space-y-6">
                    <section>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                            <div>
                                <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Partner identity</h4>
                                <p class="text-[11px] font-medium text-gray-400">Identify the community or school and its partner type.</p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label" for="community-type">Record type <span class="text-red-500">*</span></label>
                                <x-sc.select id="community-type" model="form.type" :value="$form['type']" :options="['community' => 'Community (barangay)', 'school' => 'Partner school']" placeholder="— select record type —" :search="false" :required="true" :invalid="$errors->has('form.type')" />
                            </div>
                            <div>
                                <label class="label" for="community-name">{{ $form['type'] === 'school' ? 'School name' : 'Barangay / community name' }} <span class="text-red-500">*</span></label>
                                <input id="community-name" required class="input" wire:model="form.name" autocomplete="organization" placeholder="{{ $form['type'] === 'school' ? 'e.g. Caibaan Elementary School' : 'e.g. Brgy. San Jose' }}">
                                @error('form.name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            @if ($form['type'] === 'school')
                                <div>
                                    <label class="label" for="community-school-level">School level <span class="text-red-500">*</span></label>
                                    <x-sc.select id="community-school-level" model="form.school_level" :value="$form['school_level']" :options="['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education']" placeholder="— select school level —" :search="false" :required="true" :invalid="$errors->has('form.school_level')" />
                                    @error('form.school_level') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                            <div>
                                <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Location</h4>
                                <p class="text-[11px] font-medium text-gray-400">Set the partner’s municipality, province, and physical address.</p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label" for="community-municipality">Municipality <span class="text-red-500">*</span></label>
                                <input id="community-municipality" required class="input" wire:model="form.municipality" autocomplete="address-level-2" placeholder="e.g. Tacloban City">
                                @error('form.municipality') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="community-province">Province <span class="text-red-500">*</span></label>
                                <x-sc.select id="community-province" model="form.province" :value="$form['province']" :options="array_combine(['Leyte', 'Southern Leyte', 'Biliran', 'Samar', 'Eastern Samar'], ['Leyte', 'Southern Leyte', 'Biliran', 'Samar', 'Eastern Samar'])" placeholder="— select province —" :search="false" :required="true" :invalid="$errors->has('form.province')" />
                                @error('form.province') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="community-address">Address</label>
                                <input id="community-address" class="input" wire:model="form.address" autocomplete="street-address" placeholder="Street / purok / landmark">
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">3</span>
                            <div>
                                <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Contact and status</h4>
                                <p class="text-[11px] font-medium text-gray-400">Give staff a reliable contact and the current relationship stage.</p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label" for="community-contact">{{ $form['type'] === 'school' ? 'Contact person (principal / head)' : 'Contact person' }}</label>
                                <input id="community-contact" class="input" wire:model="form.contact_person" autocomplete="name" placeholder="Kagawad / barangay captain / principal">
                            </div>
                            <div>
                                <label class="label" for="community-phone">Contact number</label>
                                <input id="community-phone" class="input" wire:model="form.contact_number" autocomplete="tel" placeholder="09XX XXX XXXX">
                            </div>
                            <div>
                                <label class="label" for="community-email">Contact email</label>
                                <input id="community-email" type="email" class="input" wire:model="form.email" autocomplete="email" placeholder="partner@example.org">
                                @error('form.email') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            @if ($form['type'] !== 'school')
                                <div>
                                    <label class="label" for="community-status">Status</label>
                                    <x-sc.select id="community-status" model="form.status" :value="$form['status']" :options="['prospecting' => 'Prospecting', 'active' => 'Active']" placeholder="— select status —" :search="false" :invalid="$errors->has('form.status')" />
                                </div>
                            @else
                                <div>
                                    <label class="label" for="community-school-status">Status</label>
                                    <input id="community-school-status" class="input bg-gray-50" value="Active (schools are active partners)" disabled>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">4</span>
                            <div>
                                <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Notes</h4>
                                <p class="text-[11px] font-medium text-gray-400">Add context that will help staff understand the partner relationship.</p>
                            </div>
                        </div>
                        <textarea id="community-description" class="input resize-y" rows="4" wire:model="form.description" placeholder="Community / school profile notes"></textarea>
                        @error('form.description') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </section>
                </div>
            </div>

            <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('showForm', false)" class="btn btn-ghost">Cancel</button>
                        <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Add partner' }}</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endif

{{-- Detail modal --}}
@if ($detail)
    <div x-data @keydown.escape.window="$wire.closeDetail()"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
         role="dialog" aria-modal="true" aria-labelledby="community-detail-title">
        <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="closeDetail"></div>
        <section class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
            <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                            <x-sc.icon name="{{ $detail->isSchool() ? 'doc' : 'pin' }}" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Partner record</p>
                            <h3 id="community-detail-title" class="mt-1 truncate text-[18px] font-extrabold tracking-tight">{{ $detail->name }}</h3>
                            <p class="mt-1 text-[12px] font-medium text-white/72">
                                @if ($detail->isSchool())
                                    {{ ['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education'][$detail->school_level] ?? 'Partner school' }} · {{ $detail->municipality }}, {{ $detail->province }}
                                @else
                                    Community · {{ $detail->municipality }}, {{ $detail->province }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($detail->trashed())
                            <span class="inline-flex items-center rounded-full bg-white/12 px-2.5 py-1 text-[10px] font-bold text-white ring-1 ring-white/15">Archived</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-white/12 px-2.5 py-1 text-[10px] font-bold text-white ring-1 ring-white/15">{{ ucfirst($detail->status) }}</span>
                        @endif
                        <button type="button" wire:click="closeDetail" aria-label="Close partner details"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50">
                            <x-sc.icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <section class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-lnu-700 ring-1 ring-gray-200"><x-sc.icon name="pin" class="h-3.5 w-3.5" /></span>
                        <div><h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Partner details</h4><p class="text-[11px] font-medium text-gray-400">Contact and location information for coordination.</p></div>
                    </div>
                    <div class="grid gap-x-5 gap-y-4 sm:grid-cols-2">
                        @if ($detail->isSchool())
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">School Level</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ ['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education'][$detail->school_level] ?? '—' }}</p></div>
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Contact person</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ $detail->contact_person ?? '—' }}</p></div>
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Contact number</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ $detail->contact_number ?? '—' }}</p></div>
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Contact email</p><p class="mt-1 break-all text-[12.5px] font-semibold text-charcoal">{{ $detail->email ?? '—' }}</p></div>
                            <div class="sm:col-span-2"><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Address</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ $detail->address ?? '—' }}</p></div>
                        @else
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Contact person</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ $detail->contact_person ?? '—' }}</p></div>
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Contact number</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ $detail->contact_number ?? '—' }}</p></div>
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Contact email</p><p class="mt-1 break-all text-[12.5px] font-semibold text-charcoal">{{ $detail->email ?? '—' }}</p></div>
                            <div><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Beneficiaries</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ number_format($detail->beneficiaries_count) }} registered</p></div>
                            <div class="sm:col-span-2"><p class="text-[10px] font-extrabold uppercase tracking-[0.1em] text-gray-400">Address</p><p class="mt-1 text-[12.5px] font-semibold text-charcoal">{{ $detail->address ?? '—' }} · {{ $detail->municipality }}, {{ $detail->province }}</p></div>
                        @endif
                    </div>
                </section>

                @if (! $detail->isSchool())
                    <section class="mt-5">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-lnu-50 text-lnu-800"><x-sc.icon name="folder" class="h-3.5 w-3.5" /></span><h4 class="text-[13px] font-extrabold tracking-tight">Relationship history</h4></div>
                            <span class="badge badge-blue">{{ $detailPrograms->count() }} project link{{ $detailPrograms->count() === 1 ? '' : 's' }}</span>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-white px-4">
                            @if ($detailPrograms->isEmpty())
                                <div class="py-4 text-[12.5px] font-medium italic text-gray-400">No linked projects yet — community is in the prospecting pipeline.</div>
                            @else
                                @foreach ($detailPrograms as $p)
                                    <div class="flex items-center gap-2.5 border-b border-dashed border-gray-100 py-3 last:border-0">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-lnu-800"></span>
                                        <a href="{{ route('projects.show', $p) }}" class="truncate text-[12px] font-semibold text-charcoal transition hover:text-lnu-800">{{ $p->title }}</a>
                                        <span class="ml-auto whitespace-nowrap text-[11px] font-medium text-gray-400">since {{ $p->planned_start_date->format('M j, Y') }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>

                    <section class="mt-5">
                        <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-lnu-50 text-lnu-800"><x-sc.icon name="clipboard" class="h-3.5 w-3.5" /></span><h4 class="text-[13px] font-extrabold tracking-tight">Needs-Assessment History</h4></div>
                        <div class="rounded-xl border border-gray-100 bg-white px-4">
                            @if ($detailSummaries->isEmpty())
                                <p class="py-4 text-[12.5px] font-medium italic text-gray-400">No needs-assessment encoded yet.</p>
                            @else
                                @foreach ($detailSummaries as $s)
                                    <div class="flex flex-wrap items-center gap-2 border-b border-dashed border-gray-100 py-3 last:border-0">
                                        <span class="badge badge-blue">Q{{ $s->quarter }} {{ $s->year }}</span>
                                        <span class="badge badge-gray">{{ $s->total_responses }} responses</span>
                                        @if ($s->avg_service_satisfaction !== null)
                                            <span class="ml-auto text-[11.5px] font-medium text-gray-400">avg satisfaction {{ number_format((float) $s->avg_service_satisfaction, 1) }}/5</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </section>
                @else
                    <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-gold-100 bg-gold-50/70 px-3.5 py-3 text-[11px] font-medium leading-relaxed text-gold-800">
                        <x-sc.icon name="doc" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>School records are extension partners. Projects link them as partners and activity venues rather than through enrollment.</p>
                    </div>
                @endif

                @if ($detail->trashed())
                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[11px] font-medium leading-relaxed text-gray-500">This record is archived. Restore it to make it active in the registry again.</div>
                @endif
            </div>

            <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                <div class="flex justify-end gap-2">
                    @if ($detail->trashed())
                        <button type="button" wire:click="restore({{ $detail->id }})" class="btn btn-primary"><x-sc.icon name="loader" class="w-4 h-4" />Restore</button>
                    @else
                        <button type="button" wire:click="edit({{ $detail->id }})" class="btn btn-outline"><x-sc.icon name="edit" class="w-4 h-4" />Edit</button>
                        <button type="button" wire:click="confirmDelete({{ $detail->id }}); closeDetail" wire:confirm="Archive this community? It can be restored later." class="btn btn-danger-soft"><x-sc.icon name="trash" class="w-4 h-4" />Archive</button>
                    @endif
                </div>
            </footer>
        </section>
    </div>
@endif
</div>

