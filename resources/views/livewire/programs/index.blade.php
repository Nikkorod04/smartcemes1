<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Extension programs · codes EXT-{{ now()->format('Y') }}-XXX are assigned automatically</p>
    <div class="reveal-item flex flex-wrap items-center gap-3">
        <label class="relative flex-1 min-w-[230px] max-w-xs">
            <x-sc.icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input type="text" wire:model.live.debounce.300ms="search" class="input !pl-9 bg-white" placeholder="Search programs by title or code…">
        </label>

        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('status', '')" @class(['chip', 'on' => $status === ''])>All</button>
            @foreach ($statuses as $s)
                <button wire:click="$set('status', '{{ $s }}')" @class(['chip', 'on' => $status === $s])>{{ ucfirst($s) }}</button>
            @endforeach
        </div>

        <div class="ml-auto flex items-center gap-3">
            <div class="flex bg-gray-100 rounded-xl p-1">
                <button type="button" wire:click="$set('view', 'grid')" title="Grid view"
                        @class(['w-9 h-8 rounded-lg flex items-center justify-center transition',
                                'bg-white shadow-sm text-lnu-800' => $view === 'grid',
                                'text-gray-400 hover:text-gray-600' => $view !== 'grid'])>
                    <x-sc.icon name="grid" class="w-[18px] h-[18px]" />
                </button>
                <button type="button" wire:click="$set('view', 'list')" title="List view"
                        @class(['w-9 h-8 rounded-lg flex items-center justify-center transition',
                                'bg-white shadow-sm text-lnu-800' => $view === 'list',
                                'text-gray-400 hover:text-gray-600' => $view !== 'list'])>
                    <x-sc.icon name="list" class="w-[18px] h-[18px]" />
                </button>
            </div>
            <button wire:click="create" class="btn btn-primary"><x-sc.icon name="folder" class="w-4 h-4" />New Program</button>
        </div>
    </div>
</section>

<section class="mt-4">
    <p class="mt-3 text-[11.5px] text-gray-400 font-medium">
        Showing {{ $rows->count() }} programs{{ $search !== '' ? ' for “'.$search.'”' : '' }}{{ $status !== '' ? ' · '.ucfirst($status) : '' }}
    </p>

    @if ($rows->isEmpty())
        <div class="reveal-item sc-card px-5 py-12 text-center">
            <p class="text-[13.5px] font-semibold text-gray-500">No programs match your filters</p>
            <p class="text-[12px] text-gray-400 mt-1">Try a different keyword or reset the status chips.</p>
        </div>
    @elseif ($view === 'grid')
        @php($grads = ['from-lnu-800 to-lnu-500', 'from-gold-400 to-gold-600', 'from-emerald-500 to-teal-600', 'from-blue-700 to-indigo-500', 'from-rose-500 to-orange-400', 'from-violet-600 to-fuchsia-500'])
        <div class="mt-3 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($rows as $row)
                @php($p = $row->model)
                @php($community = $p->communities->first())
                @php($bar = $row->over ? 'bg-red-500' : (($row->utilization_pct ?? 0) >= 90 ? 'bg-emerald-500' : (($row->utilization_pct ?? 0) == 0 ? 'bg-gray-300' : 'bg-lnu-600')))
                <article class="reveal-item sc-card sc-card-hover overflow-hidden flex flex-col" wire:key="prog-grid-{{ $p->id }}">
                    <div class="relative h-20 bg-gradient-to-r {{ $grads[$loop->index % count($grads)] }} overflow-hidden shrink-0">
                        <span class="absolute right-16 -top-9 w-24 h-24 rounded-full bg-white/10"></span>
                        <span class="absolute -right-3 -bottom-7 text-[62px] font-black text-white/15 leading-none select-none">{{ strtoupper(\Illuminate\Support\Str::before($p->title, ':')) }}</span>
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10.5px] font-bold px-2 py-0.5 rounded-md bg-gray-100 text-gray-500 tracking-wide">{{ $p->code }}</span>
                            <span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span>
                        </div>
                        <h3 class="mt-2.5 font-bold text-[14px] leading-snug line-clamp-2 min-h-[40px]">{{ $p->title }}</h3>
                        <div class="mt-2 flex items-center gap-2 min-w-0">
                            <span class="avatar w-6 h-6 text-[8.5px]">{{ \App\View\Components\Initials::for($row->lead_name) }}</span>
                            <span class="text-[11.5px] text-gray-600 font-medium truncate">{{ $row->lead_name ?? 'No lead yet' }}</span>
                            @if ($row->over)<span class="ml-auto badge badge-red !text-[9.5px] !px-2 !py-0.5">Over-allocated</span>@endif
                        </div>
                        <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-gray-400">
                            <x-sc.icon name="pin" class="w-3.5 h-3.5" /> {{ $community?->name ?? '—' }}@if($community?->municipality) · {{ $community->municipality }}@endif
                        </p>
                        <div class="pt-3 border-t border-gray-50 mt-auto">
                            <div class="flex items-center justify-between text-[11px] mb-1.5">
                                <span class="text-gray-400 font-medium">Budget · ₱{{ number_format($row->utilized) }} of ₱{{ number_format((float) $p->allocated_budget) }}</span>
                                <span class="font-bold text-charcoal">{{ $row->utilization_pct === null ? '—' : round($row->utilization_pct).'%' }}</span>
                            </div>
                            <div class="progress"><span style="width:{{ min(round($row->utilization_pct ?? 0), 100) }}%" class="{{ $bar }}"></span></div>
                            <div class="flex items-center justify-between mt-2.5">
                                <p class="text-[10.5px] text-gray-400 font-medium flex items-center gap-1.5">
                                    <x-sc.icon name="people" class="w-3 h-3" /> {{ $row->reached }}/{{ $p->target_beneficiaries ?? '—' }} reached
                                </p>
                                <a href="{{ route('programs.show', $p) }}" class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">Details</a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-3 sc-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr>
                        <th>Code</th><th>Program</th><th>Lead</th><th>Community</th>
                        <th>Budget Utilization</th><th>Status</th><th></th>
                    </th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php($p = $row->model)
                            <tr wire:key="prog-{{ $p->id }}">
                                <td><span class="badge badge-gold font-bold tracking-wide">{{ $p->code }}</span></td>
                                <td>
                                    <a href="{{ route('programs.show', $p) }}" class="font-semibold hover:text-lnu-700 transition">{{ $p->title }}</a>
                                    <p class="text-[11px] text-gray-400">{{ $p->activities_count }} activities · {{ $p->planned_start_date->format('M j, Y') }} – {{ $p->planned_end_date->format('M j, Y') }}</p>
                                </td>
                                <td class="text-gray-500">{{ $row->lead_name ?? '—' }}</td>
                                <td class="text-gray-500">{{ $p->communities->first()->name ?? '—' }}</td>
                                <td>
                                    <div class="progress !w-28"><span style="width:{{ min(round($row->utilization_pct ?? 0), 100) }}%" class="{{ $row->over ? 'bg-red-500' : 'bg-lnu-800' }}"></span></div>
                                    <p class="text-[10.5px] font-semibold mt-1 {{ $row->over ? 'text-red-600' : 'text-gray-500' }}">
                                        ₱{{ number_format($row->utilized) }} / ₱{{ number_format((float) $p->allocated_budget) }} · {{ $row->utilization_pct === null ? '—' : round($row->utilization_pct).'%' }}
                                    </p>
                                </td>
                                <td><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span>
                                    @if ($row->over)<span class="badge badge-red">⚠ over</span>@endif
                                </td>
                                <td class="!text-right row-actions">
                                    <a href="{{ route('programs.show', $p) }}" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Open hub</a>
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

{{-- New Program modal --}}
<div x-data="{ open: false }"
     x-init="$wire.$watch('showForm', v => open = v)"
     @keydown.escape.window="$wire.set('showForm', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms
     class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showForm', false)"></div>
    <form wire:submit="save" class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h3 class="font-extrabold text-[16px] tracking-tight">New Extension Program</h3>
                <p class="text-[12px] text-gray-400 mt-0.5">New programs start in Draft status — the code is auto-generated.</p>
            </div>
            <button type="button" wire:click="$set('showForm', false)" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition">✕</button>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="label">Program title *</label>
                <input required class="input" wire:model="form.title" placeholder="e.g. LITRAWIYA: Barangay Reading Proficiency Program">
                @error('form.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Description</label>
                <textarea rows="2" class="input" wire:model="form.description" placeholder="Short narrative description"></textarea>
            </div>
            <div>
                <label class="label">Planned start *</label>
                <input required type="date" class="input" wire:model="form.planned_start_date">
                @error('form.planned_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Planned end *</label>
                <input required type="date" class="input" wire:model="form.planned_end_date">
                @error('form.planned_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Target beneficiaries</label>
                <input type="number" min="1" class="input" wire:model="form.target_beneficiaries" placeholder="e.g. 250">
            </div>
            <div>
                <label class="label">Allocated budget (₱)</label>
                <input type="number" min="0" step="0.01" class="input" wire:model="form.allocated_budget" placeholder="e.g. 48000">
            </div>
            <div>
                <label class="label">Program lead</label>
                <select class="input" wire:model="form.program_lead_id">
                    <option value="">— not assigned yet —</option>
                    @foreach ($faculties as $f)
                        <option value="{{ $f->id }}">{{ $f->user->name }} · {{ $f->department }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select class="input" wire:model="form.status">
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <label class="label">Linked communities</label>
                <x-sc.multi-select
                    :options="$communities->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.' · '.$c->municipality.($c->isSchool() ? ' · School' : '')])->all()"
                    :selected="$form['community_ids'] ?? []"
                    method="toggleFormArray"
                    key="community_ids"
                    placeholder="— select communities —" />
            </div>
            <div class="col-span-2">
                <label class="label">Beneficiary categories</label>
                <x-sc.multi-select
                    :options="collect($categories)->map(fn ($cat) => ['id' => $cat, 'label' => $cat])->all()"
                    :selected="$form['beneficiary_categories'] ?? []"
                    method="toggleFormArray"
                    key="beneficiary_categories"
                    placeholder="— select categories —" />
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <button type="button" wire:click="$set('showForm', false)" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Program</button>
        </div>
    </form>
</div>
</div>

