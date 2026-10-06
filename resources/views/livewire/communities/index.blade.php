<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">
        {{ $communityCount }} partner communit{{ $communityCount === 1 ? 'y' : 'ies' }} · {{ $schoolCount }} partner school{{ $schoolCount === 1 ? '' : 's' }} across {{ $provinces->count() }} province{{ $provinces->count() === 1 ? '' : 's' }}
    </p>
    <div class="reveal-item flex flex-wrap items-center gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('type', '')" @class(['chip', 'on' => $type === ''])>All</button>
            <button wire:click="$set('type', 'community')" @class(['chip', 'on' => $type === 'community'])>Communities</button>
            <button wire:click="$set('type', 'school')" @class(['chip', 'on' => $type === 'school'])>Partner Schools</button>
        </div>

        <label class="relative flex-1 min-w-[200px] max-w-xs">
            <x-sc.icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input type="text" wire:model.live.debounce.300ms="search" class="input !pl-9 bg-white" placeholder="Search name or municipality…">
        </label>

        <select wire:model.live="province" class="input !w-auto bg-white">
            <option value="">All Provinces</option>
            @foreach ($provinces as $p)
                <option value="{{ $p }}">{{ $p }}</option>
            @endforeach
        </select>

        <select wire:model.live="status" class="input !w-auto bg-white">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="prospecting">Prospecting</option>
            <option value="archived">Archived</option>
        </select>

        <div class="ml-auto">
            <button wire:click="create" class="btn btn-primary">
                <x-sc.icon name="pin" class="w-4 h-4" />Add Community / School
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
        <div class="reveal-item sc-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr>
                        <th>Name</th><th>Contact Person</th><th>Contact Number</th>
                        <th class="!text-right">Beneficiaries</th><th class="!text-right">Programs</th><th>Status</th><th></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($communities as $c)
                            <tr wire:key="com-{{ $c->id }}" wire:click="viewDetail({{ $c->id }})" class="cursor-pointer">
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
                                    <button wire:click="viewDetail({{ $c->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Details</button>
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
<div x-data="{ open: false }"
     x-init="$wire.$watch('showForm', v => open = v)"
     @keydown.escape.window="$wire.set('showForm', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms
     class="fixed inset-0 z-[60] p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showForm', false)"></div>
    <form wire:submit="save" class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h3 class="font-extrabold text-[16px] tracking-tight">{{ $editingId ? ($form['type'] === 'school' ? 'Edit Partner School' : 'Edit Community Partner') : 'New Community / Partner School' }}</h3>
                <p class="text-[12px] text-gray-400 mt-0.5">New communities enter the prospecting pipeline for needs assessment.</p>
            </div>
            <button type="button" wire:click="$set('showForm', false)" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="label">Record Type</label>
                <select class="input" wire:model="form.type">
                    <option value="community">Community (barangay)</option>
                    <option value="school">Partner School</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="label">Barangay / School Name</label>
                <input required class="input" wire:model="form.name" placeholder="{{ $form['type'] === 'school' ? 'e.g. Caibaan Elementary School' : 'Brgy. Sample' }}">
                @error('form.name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($form['type'] === 'school')
                <div>
                    <label class="label">School Level *</label>
                    <select class="input" wire:model="form.school_level">
                        <option value="">Select level</option>
                        @foreach (['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('form.school_level') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
            <div @class(['col-span-2' => $form['type'] !== 'school'])>
                <label class="label">Municipality</label>
                <input required class="input" wire:model="form.municipality" placeholder="e.g. Tacloban City">
                @error('form.municipality') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div @class(['col-span-2' => $form['type'] !== 'school'])>
                <label class="label">Province</label>
                <select class="input" wire:model="form.province">
                    @foreach (['Leyte', 'Southern Leyte', 'Biliran', 'Samar', 'Eastern Samar'] as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ $form['type'] === 'school' ? 'Contact Person (Principal / Head)' : 'Contact Person' }}</label>
                <input class="input" wire:model="form.contact_person" placeholder="Kagawad / Barangay Captain / Principal">
            </div>
            <div>
                <label class="label">Contact Number</label>
                <input class="input" wire:model="form.contact_number" placeholder="09XX XXX XXXX">
            </div>
            <div>
                <label class="label">Contact Email</label>
                <input type="email" class="input" wire:model="form.email" placeholder="barangay@lgu.gov.ph">
                @error('form.email') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($form['type'] !== 'school')
                <div>
                    <label class="label">Status</label>
                    <select class="input" wire:model="form.status">
                        <option value="prospecting">Prospecting</option>
                        <option value="active">Active</option>
                    </select>
                </div>
            @else
                <div>
                    <label class="label">Status</label>
                    <input class="input bg-gray-50" value="Active (schools are active partners)" disabled>
                </div>
            @endif
            <div class="col-span-2">
                <label class="label">Address</label>
                <input class="input" wire:model="form.address" placeholder="Street / purok / landmark">
            </div>
            <div class="col-span-2">
                <label class="label">Description</label>
                <textarea class="input" rows="2" wire:model="form.description" placeholder="Community / school profile notes"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <button type="button" wire:click="$set('showForm', false)" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

{{-- Detail modal --}}
@if ($detail)
    <div class="fixed inset-0 z-50 p-6 overflow-auto no-print" style="display:block">
        <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeDetail"></div>
        <div class="sc-modal relative max-w-lg mx-auto mt-20 sc-card p-6 shadow-pop">
            <div class="flex items-start justify-between gap-3 mb-5">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-11 h-11 rounded-xl {{ $detail->isSchool() ? 'bg-gold-50 text-gold-700' : 'bg-lnu-50 text-lnu-800' }} flex items-center justify-center shrink-0">
                        <x-sc.icon name="{{ $detail->isSchool() ? 'doc' : 'pin' }}" class="w-5 h-5" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-extrabold text-[16px] tracking-tight truncate">{{ $detail->name }}</h3>
                        <p class="text-[12px] text-gray-400 mt-0.5">
                            @if ($detail->isSchool())
                                {{ ['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education'][$detail->school_level] ?? 'Partner School' }} · {{ $detail->municipality }}, {{ $detail->province }}
                            @else
                                {{ $detail->municipality }}, {{ $detail->province }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    @if ($detail->trashed())
                        <span class="badge badge-gray">Archived</span>
                    @else
                        <span class="badge {{ $detail->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($detail->status) }}</span>
                    @endif
                    <button wire:click="closeDetail" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-gray-50/70 border border-gray-100 mb-5">
                @if ($detail->isSchool())
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">School Level</p><p class="text-[12.5px] font-semibold mt-1">{{ ['elementary' => 'Elementary', 'secondary' => 'Secondary', 'higher_ed' => 'Higher Education'][$detail->school_level] ?? '—' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Person</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->contact_person ?? '—' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Number</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->contact_number ?? '—' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Email</p><p class="text-[12.5px] font-semibold mt-1 break-all">{{ $detail->email ?? '—' }}</p></div>
                    <div class="col-span-2"><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Address</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->address ?? '—' }}</p></div>
                @else
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Person</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->contact_person ?? '—' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Number</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->contact_number ?? '—' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Contact Email</p><p class="text-[12.5px] font-semibold mt-1 break-all">{{ $detail->email ?? '—' }}</p></div>
                    <div><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Beneficiaries</p><p class="text-[12.5px] font-semibold mt-1">{{ number_format($detail->beneficiaries_count) }} registered</p></div>
                    <div class="col-span-2"><p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Address</p><p class="text-[12.5px] font-semibold mt-1">{{ $detail->address ?? '—' }} · {{ $detail->municipality }}, {{ $detail->province }}</p></div>
                @endif
            </div>

            @if (! $detail->isSchool())
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="font-bold text-[13.5px] flex items-center gap-2"><x-sc.icon name="folder" class="w-4 h-4 text-lnu-800" />Relationship History</p>
                        <span class="badge badge-blue">{{ $detailPrograms->count() }} program link{{ $detailPrograms->count() === 1 ? '' : 's' }}</span>
                    </div>
                    @if ($detailPrograms->isEmpty())
                        <div class="flex items-center gap-2.5 p-3 rounded-xl border border-dashed border-gray-200 text-[12.5px] text-gray-400 italic">
                            No linked programs yet — community is in the prospecting pipeline.
                        </div>
                    @else
                        @foreach ($detailPrograms as $p)
                            <div class="flex items-center gap-2.5 py-2 border-b border-dashed border-gray-100 last:border-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-lnu-800 shrink-0"></span>
                                <a href="{{ route('projects.show', $p) }}" class="text-[12px] font-semibold truncate hover:text-lnu-800 transition">{{ $p->title }}</a>
                                <span class="text-[11px] text-gray-400 ml-auto whitespace-nowrap">since {{ $p->planned_start_date->format('M j, Y') }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="mb-1">
                    <p class="font-bold text-[13.5px] flex items-center gap-2 mb-2.5"><x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-800" />Needs-Assessment History</p>
                    @if ($detailSummaries->isEmpty())
                        <p class="text-[12.5px] text-gray-400 italic py-1">No needs-assessment encoded yet.</p>
                    @else
                        @foreach ($detailSummaries as $s)
                            <div class="flex items-center gap-2 py-1.5">
                                <span class="badge badge-blue">Q{{ $s->quarter }} {{ $s->year }}</span>
                                <span class="badge badge-gray">{{ $s->total_responses }} responses</span>
                                @if ($s->avg_service_satisfaction !== null)
                                    <span class="text-[11.5px] text-gray-400 ml-auto">avg satisfaction {{ number_format((float) $s->avg_service_satisfaction, 1) }}/5</span>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            @else
                <p class="text-[11.5px] text-gray-400 leading-relaxed mb-1">School records are extension partners — programs link them as partners and activity venues rather than through enrollment.</p>
            @endif

            @if ($detail->trashed())
                <p class="text-[11.5px] text-gray-400 mb-1">This record is archived. Restore it to make it active in the registry again.</p>
            @endif

            <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-gray-100">
                <button wire:click="closeDetail" class="btn btn-ghost">Close</button>
                @if ($detail->trashed())
                    <button wire:click="restore({{ $detail->id }})" class="btn btn-primary"><x-sc.icon name="loader" class="w-4 h-4" />Restore</button>
                @else
                    <button wire:click="edit({{ $detail->id }})" class="btn btn-outline"><x-sc.icon name="edit" class="w-4 h-4" />Edit</button>
                    <button wire:click="confirmDelete({{ $detail->id }}); closeDetail" wire:confirm="Archive this community? It can be restored later." class="btn btn-danger-soft"><x-sc.icon name="trash" class="w-4 h-4" />Archive</button>
                @endif
            </div>
        </div>
    </div>
@endif
</div>

