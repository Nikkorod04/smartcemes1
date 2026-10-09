{{--
    Broad Program list (the six CESO thrusts) — R7 fidelity pass.

    Matches docs/prototype/pages/programs.html, with ONE deliberate correction:
    the prototype renders per-program training-hours targets and attainment,
    which contradicts D-R5 (targets exist at University + Project level only).
    Laravel is right and the prototype is stale (`revisions.md` §19.2, category A).

    What the prototype legitimately offers — the derived roll-ups (project count,
    hours delivered, reach, budget consumed), the toolbar, the list view and the
    drill-down CTA — is restored here.
--}}
<div>
    {{-- ===================== PAGE HEADER ===================== --}}
    <section class="pt-6 reveal-item">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-[20px] font-extrabold tracking-tight leading-tight">Extension Programs</h2>
                <p class="text-[12.5px] text-gray-400 font-medium mt-0.5">
                    Broad thematic programs under the three CESO pillars — each containing one or more extension projects
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('colleges.index') }}" class="btn btn-outline">← Colleges</a>
                <a href="{{ route('projects.index') }}" class="btn btn-outline">View all projects →</a>
                {{-- "New Program" moved to the hub (§25) — creating and editing a
                     thrust happens on /colleges, where the Director already is.
                     This page is a READ-ONLY list now. --}}
            </div>
        </div>
    </section>

    {{-- ===================== SUMMARY TILES =====================
         Counts and derived roll-ups only — no per-program target exists. --}}
    <section class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([
            ['folder', 'bg-lnu-50 text-lnu-800', $totalPrograms, 'Extension Programs', 'Across the 3 CESO pillars'],
            ['clipboard', 'bg-blue-50 text-blue-700', $totalProjects, 'Extension Projects', 'Nested under these programs'],
            ['clock', 'bg-emerald-50 text-emerald-600', number_format($totalHours, 1), 'Training Hours Rendered', number_format($totalTrainees).' beneficiaries reached'],
            ['wallet', 'bg-gold-50 text-gold-700', '₱'.number_format($totalUtilized), 'Budget Utilized', 'Rolled up from projects'],
        ] as [$icon, $tone, $value, $label, $caption])
            <div class="sc-card p-5 reveal-item">
                <span class="w-10 h-10 rounded-xl {{ $tone }} flex items-center justify-center">
                    <x-sc.icon :name="$icon" class="w-5 h-5" />
                </span>
                <p class="mt-4 text-[24px] font-extrabold tracking-tight leading-none">{{ $value }}</p>
                <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">{{ $label }}</p>
                <p class="text-[11px] text-gray-400 font-medium mt-2">{{ $caption }}</p>
            </div>
        @endforeach
    </section>

    {{-- D-R5 notice — stated where a user would look for the missing target. --}}
    <section class="mt-4 reveal-item">
        <div class="sc-card p-4 flex items-start gap-3 !border-lnu-100 !bg-lnu-50/40">
            <span class="w-9 h-9 rounded-xl bg-white text-lnu-800 flex items-center justify-center shrink-0">
                <x-sc.icon name="check" class="w-4 h-4" />
            </span>
            <div class="min-w-0">
                <p class="text-[12.5px] font-bold leading-snug">Programs are a grouping level — they carry no training-hours target.</p>
                <p class="text-[11.5px] text-gray-500 mt-0.5 leading-relaxed">
                    Targets live at two levels only: the annual university pool, and each individual project.
                    Every figure on this page is a derived roll-up of the projects under each program.
                </p>
            </div>
            <span class="badge badge-blue shrink-0 ml-auto">D-R5</span>
        </div>
    </section>

    {{-- ===================== TOOLBAR + FILTERS ===================== --}}
    <section class="mt-5">
        <div class="sc-card p-4 hub-toolbar">
            <label class="sc-search flex-1 min-w-[220px]">
                <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                <input wire:model.live.debounce.300ms="search" class="input"
                       placeholder="Search program, thrust, or college…">
            </label>

            {{-- Pillar filter --}}
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['All', 'Social', 'Economic', 'Environmental'] as $option)
                    <button type="button" wire:click="setPillar('{{ $option }}')"
                            @class(['chip', 'on' => $pillar === $option])>
                        {{ $option === 'All' ? 'All pillars' : $option }}
                    </button>
                @endforeach
            </div>

            {{-- College filter — membership is DERIVED from each program's projects --}}
            <select wire:model.live="college" class="input !w-[170px]">
                <option value="All">All colleges</option>
                @foreach ($colleges as $c)
                    <option value="{{ $c->code }}">{{ $c->code }} — {{ $c->short_name }}</option>
                @endforeach
            </select>

            <div class="ml-auto flex items-center gap-3">
                <select wire:model.live="sort" class="input !w-[210px] text-[12px]">
                    <option value="hours">Sort: training hours rendered</option>
                    <option value="projects">Sort: most projects</option>
                    <option value="budget">Sort: budget utilized</option>
                    <option value="title">Sort: A–Z</option>
                </select>
                <div class="flex bg-gray-100 rounded-xl p-1">
                    <button type="button" wire:click="setView('grid')" title="Grid view"
                            @class(['w-9 h-8 rounded-lg flex items-center justify-center transition',
                                    'bg-white shadow-sm text-lnu-800' => $view === 'grid',
                                    'text-gray-400 hover:text-gray-600' => $view !== 'grid'])>
                        <x-sc.icon name="grid" class="w-4 h-4" />
                    </button>
                    <button type="button" wire:click="setView('list')" title="List view"
                            @class(['w-9 h-8 rounded-lg flex items-center justify-center transition',
                                    'bg-white shadow-sm text-lnu-800' => $view === 'list',
                                    'text-gray-400 hover:text-gray-600' => $view !== 'list'])>
                        <x-sc.icon name="list" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <p class="mt-3 text-[11.5px] text-gray-400 font-medium">
            Showing {{ $rows->count() }} of {{ $totalPrograms }}
            {{ \Illuminate\Support\Str::plural('program', $totalPrograms) }}
            @if ($pillar !== 'All') · {{ $pillar }} @endif
            @if ($college !== 'All') · {{ $college }} @endif
            @if (trim($search) !== '') for “{{ $search }}” @endif
        </p>

        {{-- ===================== GRID VIEW ===================== --}}
        @if ($view === 'grid')
            <div class="mt-3 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                @forelse ($rows as $row)
                    @php($accent = match ($row->pillar) {
                        'social' => '#2547eb',
                        'economic' => '#10b981',
                        'environmental' => '#15803d',
                        default => '#003599',
                    })
                    <article class="sc-card sc-card-hover overflow-hidden flex flex-col" wire:key="program-{{ $row->id }}">
                        <div class="h-1.5 shrink-0" style="background:{{ $accent }}"></div>
                        <div class="p-5 flex flex-col flex-1">

                            <div class="flex items-start justify-between gap-3">
                                <span class="text-[10.5px] font-bold px-2 py-0.5 rounded-md bg-gray-100 text-gray-500 tracking-wide font-mono">{{ $row->code }}</span>
                                <x-sc.pillar-chip :pillar="$row->pillar" />
                            </div>

                            <h3 class="mt-2.5 font-bold text-[14.5px] leading-snug">{{ $row->title }}</h3>
                            <p class="mt-1.5 text-[11.5px] text-gray-500 leading-relaxed line-clamp-2 min-h-[34px]">
                                {{ $row->blurb ?: '—' }}
                            </p>

                            <div class="mt-3 flex items-center gap-2 flex-wrap">
                                @forelse ($row->colleges as $code)
                                    <x-sc.college-pill :code="$code" short />
                                @empty
                                    <x-sc.college-pill />
                                @endforelse
                                <span class="text-[10.5px] text-gray-400 font-medium truncate">{{ $row->thrust }}</span>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-50 mt-auto space-y-3">
                                {{-- Derived roll-ups. No "of target" and no attainment bar: D-R5. --}}
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <p class="text-[16px] font-extrabold tracking-tight leading-none">{{ number_format($row->training_hours, 1) }}</p>
                                        <p class="text-[10px] text-gray-400 font-semibold mt-1">Training hrs</p>
                                        <p class="text-[9.5px] text-gray-300 font-medium">rendered</p>
                                    </div>
                                    <div>
                                        <p class="text-[16px] font-extrabold tracking-tight leading-none">{{ $row->project_count }}</p>
                                        <p class="text-[10px] text-gray-400 font-semibold mt-1">{{ \Illuminate\Support\Str::plural('Project', $row->project_count) }}</p>
                                        <p class="text-[9.5px] text-gray-300 font-medium">{{ number_format($row->trainees) }} reached</p>
                                    </div>
                                    <div>
                                        <p class="text-[16px] font-extrabold tracking-tight leading-none">₱{{ number_format($row->utilized) }}</p>
                                        <p class="text-[10px] text-gray-400 font-semibold mt-1">Budget</p>
                                        <p class="text-[9.5px] text-gray-300 font-medium">utilized</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="badge badge-{{ config('smartcemes.status_colors')[$row->status] ?? 'gray' }}">
                                        {{ ucfirst($row->status) }}
                                    </span>
                                    <a href="{{ route('projects.index', ['program' => $row->id]) }}"
                                       class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">View projects →</a>
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full sc-card p-10 text-center">
                        <span class="mx-auto w-12 h-12 rounded-2xl bg-gray-50 text-gray-300 flex items-center justify-center">
                            <x-sc.icon name="folder" class="w-6 h-6" />
                        </span>
                        <p class="mt-3 font-bold text-[13.5px]">No programs match your filters</p>
                        <p class="text-[12px] text-gray-400 mt-1">Try a different keyword, pillar, or college.</p>
                    </div>
                @endforelse
            </div>

            {{-- ===================== LIST VIEW ===================== --}}
        @else
            <div class="mt-3 sc-card p-0 overflow-hidden">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Pillar</th>
                            <th>College(s)</th>
                            <th class="!text-right">Projects</th>
                            <th class="!text-right">Training Hours</th>
                            <th class="!text-right">Budget</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="cursor-pointer" wire:key="row-{{ $row->id }}"
                                onclick="window.location='{{ route('projects.index', ['program' => $row->id]) }}'">
                                <td class="max-w-[300px]">
                                    <p class="font-semibold text-[13px]">{{ $row->title }}</p>
                                    <p class="text-[10.5px] text-gray-400 font-medium font-mono">{{ $row->code }} · {{ $row->thrust }}</p>
                                </td>
                                <td><x-sc.pillar-chip :pillar="$row->pillar" /></td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($row->colleges as $code)
                                            <x-sc.college-pill :code="$code" short />
                                        @empty
                                            <x-sc.college-pill />
                                        @endforelse
                                    </div>
                                </td>
                                <td class="!text-right font-semibold">{{ $row->project_count }}</td>
                                <td class="!text-right font-semibold">{{ number_format($row->training_hours, 1) }}</td>
                                <td class="!text-right">
                                    <span class="font-semibold">₱{{ number_format($row->utilized) }}</span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('projects.index', ['program' => $row->id]) }}"
                                       onclick="event.stopPropagation()"
                                       class="text-[12px] font-bold text-lnu-800 hover:text-lnu-600 transition">Projects →</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-[12.5px] text-gray-400">
                                    Nothing to show — adjust your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>
