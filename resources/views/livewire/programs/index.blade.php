<div>
<section class="pt-6">
    {{-- R7: the old subtitle called these "extension programs" and cited the
         retired EXT- codes. Projects are the NARROW level; new codes are
         college-prefixed (R-Q4). The back link matters because the collapsed
         admin nav (PATTERNS v4.3) no longer lists /programs in the sidebar. --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <p class="text-[13px] text-gray-400 font-medium">
            Extension Projects · codes are college-prefixed (e.g. CAS-{{ now()->format('Y') }}-001) and assigned automatically
        </p>
        <a href="{{ route('programs.index') }}" class="btn btn-outline !px-3 !py-2 text-[12px]">← Programs</a>
    </div>
    <div class="reveal-item flex flex-wrap items-center gap-3 mt-3">
        <label class="sc-search flex-1 min-w-[230px] max-w-xs">
            <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
            <input type="text" wire:model.live.debounce.300ms="search" class="input bg-white" placeholder="Search projects by title or code…">
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
        </div>
    </div>

    {{-- R5 §5 step 2 — college / broad program / academic year / sort. Options
         are read from real rows, so a new college or year appears automatically. --}}
    <div class="reveal-item mt-3 flex flex-wrap items-center gap-2">
        <select wire:model.live="college" class="input !py-2 !text-[12.5px] w-auto">
            <option value="">All colleges</option>
            @foreach ($colleges as $c)
                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="program" class="input !py-2 !text-[12.5px] w-auto">
            <option value="">All broad programs</option>
            @foreach ($programs as $p)
                <option value="{{ $p->id }}">{{ $p->title }}</option>
            @endforeach
        </select>

        <select wire:model.live="year" class="input !py-2 !text-[12.5px] w-auto">
            <option value="">All academic years</option>
            @foreach ($years as $y)
                <option value="{{ $y }}">AY {{ $y }}–{{ $y + 1 }}</option>
            @endforeach
        </select>

        <select wire:model.live="sort" class="input !py-2 !text-[12.5px] w-auto">
            <option value="">By start date</option>
            <option value="hours">Most training hours first</option>
        </select>

        @if ($hasFilters)
            <button wire:click="clearFilters" class="chip"><x-sc.icon name="x" class="w-3.5 h-3.5" />Clear filters</button>
        @endif
    </div>
</section>

<section class="mt-4">
    <p class="mt-3 text-[11.5px] text-gray-400 font-medium">
        Showing {{ $rows->count() }} project{{ $rows->count() === 1 ? '' : 's' }}{{ $search !== '' ? ' for “'.$search.'”' : '' }}{{ $status !== '' ? ' · '.ucfirst($status) : '' }}@if ($sort === 'hours') · ranked by training hours rendered @endif
    </p>

    @if ($rows->isEmpty())
        <div class="reveal-item sc-card px-5 py-12 text-center">
            <p class="text-[13.5px] font-semibold text-gray-500">No projects match your filters</p>
            <p class="text-[12px] text-gray-400 mt-1">Try a different keyword or reset the status chips.</p>
        </div>
    @elseif ($view === 'grid')
        @php($grads = ['from-lnu-800 to-lnu-500', 'from-gold-400 to-gold-600', 'from-emerald-500 to-teal-600', 'from-blue-700 to-indigo-500', 'from-rose-500 to-orange-400', 'from-violet-600 to-fuchsia-500'])
        <div class="mt-3 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($rows as $row)
                @php($p = $row->model)
                @php($community = $p->communities->first())
                @php($bar = $row->over ? 'bg-red-500' : (($row->budget_pct ?? 0) >= 90 ? 'bg-emerald-500' : (($row->budget_pct ?? 0) == 0 ? 'bg-gray-300' : 'bg-lnu-600')))
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
                                <span class="text-gray-400 font-medium">Budget · ₱{{ number_format($row->utilized) }} of ₱{{ number_format($row->allocated_budget) }}</span>
                                <span class="font-bold text-charcoal">{{ $row->budget_pct === null ? '—' : round($row->budget_pct).'%' }}</span>
                            </div>
                            <div class="progress"><span style="width:{{ min(round($row->budget_pct ?? 0), 100) }}%" class="{{ $bar }}"></span></div>
                            <div class="flex items-center justify-between text-[11px] mt-2.5">
                                <span class="text-gray-400 font-medium">Training hours · {{ number_format($row->training_hours, 1) }}@if ($row->hours_target !== null) of {{ number_format($row->hours_target) }}@else (no target)@endif</span>
                                <span class="font-bold text-charcoal">{{ $row->hours_pct === null ? '—' : round($row->hours_pct).'%' }}</span>
                            </div>                            <div class="progress mt-1.5"><span style="width:{{ min(round($row->hours_pct ?? 0), 100) }}%" class="bg-lnu-800"></span></div>
                            <div class="flex items-center justify-between mt-2.5">
                                <p class="text-[10.5px] text-gray-400 font-medium flex items-center gap-1.5">
                                    <x-sc.icon name="people" class="w-3 h-3" /> {{ $row->reached }} trainee{{ $row->reached === 1 ? '' : 's' }} reached
                                </p>
                                <a href="{{ route('projects.show', $p) }}" class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">Details</a>
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
                        <th>Code</th><th>Project</th><th>Lead</th><th>Community</th>
                        <th>Budget vs allocation</th><th>Training hours</th><th>Status</th><th></th>
                    </tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php($p = $row->model)
                            <tr wire:key="prog-{{ $p->id }}">
                                <td><span class="badge badge-gold font-bold tracking-wide">{{ $p->code }}</span></td>
                                <td>
                                    <a href="{{ route('projects.show', $p) }}" class="font-semibold hover:text-lnu-700 transition">{{ $p->title }}</a>
                                    <p class="text-[11px] text-gray-400">{{ $p->activities_count }} activities · {{ $p->planned_start_date->format('M j, Y') }} – {{ $p->planned_end_date->format('M j, Y') }}</p>
                                </td>
                                <td class="text-gray-500">{{ $row->lead_name ?? '—' }}</td>
                                <td class="text-gray-500">{{ $p->communities->first()->name ?? '—' }}</td>
                                <td>
                                    <div class="progress !w-28"><span style="width:{{ min(round($row->budget_pct ?? 0), 100) }}%" class="{{ $row->over ? 'bg-red-500' : 'bg-lnu-800' }}"></span></div>
                                    <p class="text-[10.5px] font-semibold mt-1 {{ $row->over ? 'text-red-600' : 'text-gray-500' }}">
                                        ₱{{ number_format($row->utilized) }} / ₱{{ number_format($row->allocated_budget) }} · {{ $row->budget_pct === null ? '—' : round($row->budget_pct).'%' }}
                                    </p>
                                </td>
                                <td>
                                    <div class="progress !w-28"><span style="width:{{ min(round($row->hours_pct ?? 0), 100) }}%" class="bg-lnu-800"></span></div>
                                    <p class="text-[10.5px] font-semibold mt-1 text-gray-500">
                                        {{ number_format($row->training_hours, 1) }} hrs
                                        @if ($row->hours_target !== null)
                                            / {{ number_format($row->hours_target) }} · {{ round($row->hours_pct) }}%
                                        @else
                                            · no target
                                        @endif
                                    </p>
                                </td>
                                <td><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span>
                                    @if ($row->over)<span class="badge badge-red"><x-sc.icon name="alert" class="w-3 h-3" /> over</span>@endif
                                </td>
                                <td class="!text-right row-actions">
                                    <a href="{{ route('projects.show', $p) }}" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Open hub</a>
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

</div>

