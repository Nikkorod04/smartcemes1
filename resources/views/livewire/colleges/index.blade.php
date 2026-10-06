{{--
    The extension hub (three views, one component).

    VIEW 1 — the college cards only (owner decision §11.7: no roll-up strip,
             no program/project tables, no page header — the sidebar already
             names the entry).
    VIEW 2 — the selected college: hero, derived KPIs, the BROAD PROGRAMS it
             delivers, and its faculty.
    VIEW 3 — one of those programs: its projects (with a search + status
             toolbar) and the same faculty block, which is shared by both views
             rather than duplicated.

    The Programs level was ADDED 2026-09-27 (owner request, §23), so the
    drill-down reads College → Program → Projects → Activities. Programs are
    DERIVED from the college's projects — `programs` has no college_id (§3) —
    never assigned.

    Every figure is a DERIVED roll-up of the college's projects. A college has
    no target of its own (§2.2B / D-R5), so no attainment percentage appears
    anywhere on this page.
--}}
<div>
    @if ($selected)
        @if ($selectedProgram)
            {{-- ============================================================
                 VIEW 3 — ONE PROGRAM OF THE SELECTED COLLEGE

                 Reached from view 2. Lists only that program's projects, which
                 link on to the project hub (and its activities).

                 Restyled 2026-09-28 (§24) so it matches view 2 rather than
                 looking like the previous design — same breadcrumb, same
                 icon-chip KPI tiles, same section header.
                 ============================================================ --}}
            <section class="pt-6 reveal-item">
                {{-- Three-level breadcrumb: the Director can step back one level
                     or all the way out, and can see where they are. --}}
                <nav class="hub-crumb" aria-label="Breadcrumb">
                    <button type="button" class="hub-crumb-link" wire:click="clearCollege">
                        <x-sc.icon name="grid" class="w-3.5 h-3.5" /> All colleges
                    </button>
                    <x-sc.icon name="chevron-right" class="hub-crumb-sep w-3.5 h-3.5" />
                    <button type="button" class="hub-crumb-link" wire:click="clearProgram">
                        {{ $selected['code'] }}
                    </button>
                    <x-sc.icon name="chevron-right" class="hub-crumb-sep w-3.5 h-3.5" />
                    {{-- 58 chars clears the longest seeded thrust title
                         ("Environmental Conservation & Disaster Preparedness",
                         49) without an ellipsis, and still truncates a runaway one. --}}
                    <span class="hub-crumb-here" aria-current="page">{{ \Illuminate\Support\Str::limit($selectedProgram->title, 58) }}</span>
                </nav>

                <div class="college-hero mt-4">
                    <div class="h-1.5" style="background:{{ $selected['color'] }}"></div>
                    <div class="college-hero-body">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2.5">
                                    <span class="hub-kpi-chip hub-kpi-chip--lnu !mb-0">
                                        <x-sc.icon name="folder" class="w-4 h-4" />
                                    </span>
                                    <p class="hub-eyebrow">Program</p>
                                </div>
                                <h2 class="college-hero-name mt-2.5">{{ $selectedProgram->title }}</h2>
                                <p class="college-hero-sub">
                                    {{ $selected['model']->name }}
                                    @if ($selectedProgram->code)
                                        <span class="text-gray-300 mx-1.5">·</span>
                                        <span class="font-mono">{{ $selectedProgram->code }}</span>
                                    @endif
                                    @if ($selectedProgram->pillar)
                                        <span class="text-gray-300 mx-1.5">·</span>{{ ucfirst($selectedProgram->pillar) }} pillar
                                    @endif
                                </p>
                            </div>

                            {{-- Both actions are MODALS now (§25). "New project" used to
                                 be a link to /projects carrying `?new=1` — a page the
                                 owner had asked to remove, and whose Alpine visibility
                                 bridge never fired, so it showed no form at all. --}}
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" wire:click="openProgramEdit({{ $selectedProgram->id }})"
                                        class="btn btn-outline !px-3.5 !py-2 text-[12px]">
                                    <x-sc.icon name="edit" class="w-4 h-4" /><span class="ml-1.5">Edit program</span>
                                </button>
                                <button type="button" wire:click="openProjectCreate"
                                        class="btn btn-primary !px-3.5 !py-2 text-[12px]">
                                    <x-sc.icon name="plus" class="w-4 h-4" /><span class="ml-1.5">New project</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Rolled up from this program's projects. "Hours rendered", NOT
                 "Training hours": no program has an hours TARGET, so the label
                 must not read like one — the only `Training hours` label on the
                 page belongs to a project card, where the target is real
                 (PATTERNS §7). --}}
            <section class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-4 reveal-item">
                @foreach ([
                    ['clipboard', 'lnu', $selectedProgram->projects, 'Projects', 'in this program'],
                    ['users', 'gold', number_format($selectedProgram->trainors), 'Trainors', number_format($selectedProgram->trainees).' trainees'],
                    ['clock', 'emerald', number_format($selectedProgram->training_hours, 1), 'Hours rendered', 'rolled up from projects'],
                    ['wallet', 'slate', '₱'.number_format($selectedProgram->utilized), 'Budget utilized', 'of ₱'.number_format($selectedProgram->allocated)],
                ] as [$icon, $tone, $value, $caption, $sub])
                    <div class="hub-kpi">
                        <span class="hub-kpi-chip hub-kpi-chip--{{ $tone }}">
                            <x-sc.icon name="{{ $icon }}" class="w-4 h-4" />
                        </span>
                        <p class="hub-kpi-val">{{ $value }}</p>
                        <p class="hub-kpi-cap">{{ $caption }}</p>
                        <p class="hub-kpi-sub">{{ $sub }}</p>
                    </div>
                @endforeach
            </section>

            {{-- This program's projects --}}
            <section class="mt-9">
                <div class="hub-sec-head">
                    <div>
                        <p class="hub-eyebrow">Projects</p>
                        <h3 class="hub-sec-title">{{ $selectedProgram->title }} under {{ $selected['code'] }}</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 inline-flex">
                                <x-sc.icon name="search" class="w-4 h-4" />
                            </span>
                            <input wire:model.live.debounce.300ms="projectSearch"
                                   class="input !pl-9 !w-60 !py-2" placeholder="Search project, lead, community…">
                        </label>
                        <div class="flex items-center gap-1.5">
                            @foreach (['All', 'Ongoing', 'Completed', 'Draft', 'Archived'] as $chip)
                                <button type="button"
                                        wire:click="$set('projectStatus', '{{ $chip }}')"
                                        @class(['chip', 'on' => $projectStatus === $chip])>
                                    {{ $chip }}@if (($statusCounts[$chip] ?? 0) > 0)
                                        <span class="opacity-60">{{ $statusCounts[$chip] }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($projectRows->isNotEmpty())
                    <div class="mt-4 grid md:grid-cols-2 xl:grid-cols-3 gap-5">
                        @foreach ($projectRows as $row)
                            {{-- The CARD is the div and the BODY is the link, so the footer
                                 can carry the archive button. A <button> inside an <a> is
                                 invalid HTML and the click bubbles, so the archive action
                                 would navigate to the project instead of archiving it. --}}
                            <div class="proj-card reveal-item">
                                @if ($row->archived)
                                    {{-- No hub link on an archived card: `projects.show`
                                         binds through the soft-delete scope, so it would
                                         404. The body is a plain block instead. --}}
                                    <div class="block flex-1">
                                @else
                                    <a href="{{ route('projects.show', $row->id) }}" class="block flex-1"
                                       aria-label="Open {{ $row->acr }}">
                                @endif
                                <span class="proj-card-stripe" style="background:{{ $selected['color'] }}"></span>
                                <span class="block p-5 text-left">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="block font-extrabold text-[14.5px] tracking-tight leading-snug">{{ $row->acr }}</span>
                                            <span class="block text-[11px] text-gray-400 font-mono mt-0.5">{{ $row->code }}</span>
                                        </span>
                                        @if ($row->archived)
                                            <span class="badge badge-gray shrink-0">Archived</span>
                                        @else
                                            <span class="badge badge-{{ config('smartcemes.status_colors')[$row->status] ?? 'gray' }} shrink-0">
                                                {{ ucfirst($row->status) }}
                                            </span>
                                        @endif
                                    </span>

                                    @if ($row->acr !== $row->title)
                                        <span class="block text-[11.5px] text-gray-500 leading-snug mt-2.5 min-h-[32px]">{{ $row->title }}</span>
                                    @else
                                        <span class="block mt-2.5 min-h-[32px]"></span>
                                    @endif

                                    <span class="flex items-center gap-1.5 mt-3 text-[11px] text-gray-400 font-medium">
                                        <x-sc.icon name="pin" class="w-3.5 h-3.5" />
                                        {{ $row->community ?? '—' }}
                                    </span>
                                    <span class="flex items-center gap-1.5 mt-1.5 text-[11px] text-gray-400 font-medium">
                                        <x-sc.icon name="people" class="w-3.5 h-3.5" />
                                        {{ $row->lead ?? '—' }}
                                    </span>

                                    @if ($row->archived)
                                        {{-- NO figures here on purpose. The roll-up walks LIVE
                                             activities, so an archived project would print
                                             0 hours / 0 reach / 0 activities. Its data is
                                             retained, so a 0 would be a false claim rather
                                             than an honest zero (the NULL-over-0 rule). --}}
                                        <span class="block mt-4 proj-stat">
                                            <span class="block text-[11px] text-gray-400 font-medium leading-relaxed">
                                                Archived — its hours, reach and budget are retained and return when it is restored.
                                            </span>
                                        </span>
                                    @else
                                        <span class="block mt-4 proj-stat">
                                            <span class="flex items-center justify-between text-[11px] mb-1.5">
                                                <span class="text-gray-400 font-semibold">Training hours</span>
                                                <span class="font-bold {{ ($row->hours_pct ?? 0) >= 100 ? 'text-emerald-600' : 'text-charcoal' }}">
                                                    {{ number_format($row->training_hours, 1) }}@if ($row->hours_target !== null) / {{ number_format($row->hours_target) }}@endif
                                                </span>
                                            </span>
                                            @if ($row->hours_target !== null)
                                                <span class="progress block">
                                                    <span style="width:{{ min($row->hours_pct ?? 0, 100) }}%"
                                                          class="{{ ($row->hours_pct ?? 0) >= 100 ? 'bg-emerald-500' : (($row->hours_pct ?? 0) >= 50 ? 'bg-lnu-600' : 'bg-gold-500') }}"></span>
                                                </span>
                                            @else
                                                <span class="block text-[10.5px] text-gray-300 font-medium">no annual target set</span>
                                            @endif
                                        </span>

                                        <span class="grid grid-cols-3 gap-2 mt-3">
                                            <span class="proj-stat block">
                                                <span class="s-val block">{{ $row->trainors }}</span>
                                                <span class="s-cap block">Trainors</span>
                                            </span>
                                            <span class="proj-stat block">
                                                <span class="s-val block">{{ number_format($row->trainees) }}</span>
                                                <span class="s-cap block">Trainees</span>
                                            </span>
                                            <span class="proj-stat block">
                                                <span class="s-val block">{{ $row->activities }}</span>
                                                <span class="s-cap block">Activities</span>
                                            </span>
                                        </span>
                                    @endif
                                </span>
                                @if ($row->archived)
                                    </div>
                                @else
                                    </a>
                                @endif
                                <span class="block px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-[11px] text-gray-400 font-medium">
                                        @if ($row->archived)
                                            Data retained
                                        @else
                                            ₱{{ number_format($row->utilized) }}@if ($row->budget_allocated) of ₱{{ number_format($row->budget_allocated) }}@endif
                                        @endif
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        @if ($row->archived)
                                            {{-- The only way back — without this the archive is a
                                                 one-way door. Restores the exact inverse of what
                                                 the archive cascaded. --}}
                                            <button type="button"
                                                    wire:click="restoreProject({{ $row->id }})"
                                                    wire:confirm="Restore {{ $row->acr }}? Its activities, attendance, rendered hours and budget entries all come back."
                                                    class="btn btn-outline !px-2.5 !py-1 !text-[11px]">Restore</button>
                                        @else
                                            <a href="{{ route('projects.show', $row->id) }}" class="college-card-cta">View <x-sc.icon name="chevron-right" class="w-3.5 h-3.5" /></a>
                                            <button type="button"
                                                    wire:click="archiveProject({{ $row->id }})"
                                                    wire:confirm="Archive {{ $row->acr }}?@if ($row->activities > 0) This also archives its {{ $row->activities }} {{ $row->activities === 1 ? 'activity' : 'activities' }} and their attendance, rendered-hours, availability and budget records.@endif The record is kept, and you can bring it back from the Archived filter."
                                                    class="proj-archive-btn" title="Archive project"
                                                    aria-label="Archive {{ $row->acr }}">
                                                <x-sc.icon name="trash" class="w-3.5 h-3.5" />
                                            </button>
                                        @endif
                                    </span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 hub-empty">
                        <span class="hub-empty-badge"><x-sc.icon name="folder" class="w-6 h-6" /></span>
                        <p class="text-[13px] font-bold text-gray-600">No projects match this filter</p>
                        <p class="text-[12px] text-gray-400 font-medium mt-1">Try a different status or clear the search.</p>
                    </div>
                @endif
            </section>
        @else
            {{-- ============================================================
                 VIEW 2 — ONE COLLEGE: breadcrumb, hero, derived KPIs, and the
                 programs it delivers. Its faculty renders BELOW, outside this
                 branch, so views 2 and 3 share one copy.

                 Redesigned 2026-09-28 (§24): the hero leads with the college's
                 own brand band and seal, the KPI tiles carry tinted icon chips,
                 and the program grid is 2-up so a college delivering one or two
                 thrusts fills its row instead of leaving a hole.
                 ============================================================ --}}
            <section class="pt-6 reveal-item">
                {{-- Breadcrumb. PATTERNS §7 bans a step RAIL, not orientation —
                     one line naming the current level is what tells the Director
                     where in the hierarchy they are. --}}
                <nav class="hub-crumb" aria-label="Breadcrumb">
                    <button type="button" class="hub-crumb-link" wire:click="clearCollege">
                        <x-sc.icon name="grid" class="w-3.5 h-3.5" /> All colleges
                    </button>
                    <x-sc.icon name="chevron-right" class="hub-crumb-sep w-3.5 h-3.5" />
                    <span class="hub-crumb-here" aria-current="page">{{ $selected['model']->name }}</span>
                </nav>

                <div class="college-hero mt-4">
                    <div class="college-hero-media" style="background:{{ $selected['color'] }}">
                        @if ($selected['logo'])
                            <img src="{{ asset($selected['logo']) }}" alt="{{ $selected['model']->name }} seal"
                                 class="college-hero-seal" width="256" height="256">
                        @else
                            <span class="college-crest !w-20 !h-20 !text-[26px]">{{ $selected['code'] }}</span>
                        @endif
                        <span class="college-hero-mark" aria-hidden="true">{{ $selected['code'] }}</span>
                    </div>

                    <div class="college-hero-body">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="min-w-0 flex-1">
                                <h2 class="college-hero-name">{{ $selected['model']->name }}</h2>
                                <p class="college-hero-sub">
                                    {{ $selected['model']->short_name }}
                                    <span class="text-gray-300 mx-1.5">·</span>
                                    Extension Coordinator ·
                                    <span class="font-bold text-charcoal">{{ $selected['model']->extensionCoordinator?->user?->name ?? 'unassigned' }}</span>
                                </p>
                            </div>

                            {{-- The "Programs" button that used to sit here linked OUT
                                 to the cross-college /programs page. Removed 2026-09-27
                                 (§23): the programs are the next level of THIS page. --}}
                            <a href="{{ route('faculty.index', ['college' => $selected['code']]) }}"
                               class="btn btn-outline !px-3.5 !py-2 text-[12px] shrink-0">
                                <x-sc.icon name="users" class="w-4 h-4" /><span class="ml-1.5">Faculty</span>
                            </a>
                        </div>

                        @if ($selected['model']->description)
                            <p class="college-hero-desc">{{ $selected['model']->description }}</p>
                        @endif

                        {{-- Identity facts at a glance. This strip deliberately
                             echoes the KPI row below — the strip is the one-line
                             summary, the row is the same figures with their
                             sub-captions. Same numbers, so they cannot disagree. --}}
                        <div class="college-hero-chips">
                            <span class="hero-chip">
                                <x-sc.icon name="folder" class="w-3.5 h-3.5" />
                                {{ $selected['programs'] }} {{ \Illuminate\Support\Str::plural('program', $selected['programs']) }}
                            </span>
                            <span class="hero-chip">
                                <x-sc.icon name="clipboard" class="w-3.5 h-3.5" />
                                {{ $selected['projects'] }} {{ \Illuminate\Support\Str::plural('project', $selected['projects']) }}
                            </span>
                            <span class="hero-chip">
                                <x-sc.icon name="users" class="w-3.5 h-3.5" />
                                {{ $selected['faculty'] }} faculty
                            </span>
                            <span class="hero-chip">
                                <x-sc.icon name="people" class="w-3.5 h-3.5" />
                                {{ number_format($selected['trainees']) }} trainees
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Derived KPIs — project-level quantities rolled up. A college has
                 no target of its own (§2.2B / D-R5), so no attainment appears
                 here, and there is deliberately NO hours tile (PATTERNS §7). --}}
            <section class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-4 reveal-item">
                @foreach ([
                    ['clipboard', 'lnu', $selected['projects'], 'Extension projects', $selected['programs'].' broad programs'],
                    ['users', 'gold', number_format($selected['trainors']), 'Trainors', $selected['faculty'].' faculty assigned'],
                    ['people', 'emerald', number_format($selected['trainees']), 'Trainees', $selected['activities'].' activities'],
                    ['wallet', 'slate', '₱'.number_format($selected['budget_utilized']), 'Budget utilized', 'rolled up from projects'],
                ] as [$icon, $tone, $value, $caption, $sub])
                    <div class="hub-kpi">
                        <span class="hub-kpi-chip hub-kpi-chip--{{ $tone }}">
                            <x-sc.icon name="{{ $icon }}" class="w-4 h-4" />
                        </span>
                        <p class="hub-kpi-val">{{ $value }}</p>
                        <p class="hub-kpi-cap">{{ $caption }}</p>
                        <p class="hub-kpi-sub">{{ $sub }}</p>
                    </div>
                @endforeach
            </section>

            {{-- The programs this college delivers. DERIVED from its projects:
                 `programs` has no college_id by design (§3), so there is no
                 assignment to list — grouping is the only correct reading. --}}
            <section class="mt-9">
                <div class="hub-sec-head">
                    <div>
                        <p class="hub-eyebrow">Programs</p>
                        <h3 class="hub-sec-title">{{ $selected['code'] }} extension programs</h3>
                    </div>
                    <div class="hub-sec-meta">
                        <span class="badge badge-blue">{{ $programRows->count() }} {{ \Illuminate\Support\Str::plural('program', $programRows->count()) }}</span>
                        {{-- §25: program create/edit lives HERE now. §23 removed every
                             link to /programs, which left its create form with no
                             inbound path — "add a program" became impossible. --}}
                        <button type="button" wire:click="openProgramCreate"
                                class="btn btn-primary !px-3.5 !py-2 text-[12px]">
                            <x-sc.icon name="plus" class="w-4 h-4" /><span class="ml-1.5">New program</span>
                        </button>
                    </div>
                </div>

                @if ($programRows->isNotEmpty())
                    {{-- 2-up, not 3-up: a college delivers one or two thrusts, and a
                         third column would sit empty beside them. --}}
                    <div class="mt-4 grid md:grid-cols-2 gap-5">
                        @foreach ($programRows as $program)
                            <button type="button"
                                    @if ($program->id) wire:click="selectProgram({{ $program->id }})" @endif
                                    class="prog-card reveal-item"
                                    aria-label="Open {{ $program->title }}">
                                <span class="block h-1.5" style="background:{{ $selected['color'] }}"></span>
                                <span class="block p-5 flex-1">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="block font-extrabold text-[17px] tracking-tight leading-snug">{{ $program->title }}</span>
                                            @if ($program->code)
                                                <span class="block text-[11px] text-gray-400 font-mono mt-1">{{ $program->code }}</span>
                                            @endif
                                            @if ($program->archived > 0)
                                                <span class="block text-[10.5px] text-gray-400 font-medium mt-1">{{ $program->archived }} archived — open to restore</span>
                                            @endif
                                        </span>
                                        @if ($program->pillar)
                                            <span class="badge badge-gray shrink-0">{{ ucfirst($program->pillar) }}</span>
                                        @endif
                                    </span>

                                    <span class="grid grid-cols-4 gap-2 mt-4">
                                        <span class="proj-stat block">
                                            <span class="s-val block">{{ $program->projects }}</span>
                                            <span class="s-cap block">{{ \Illuminate\Support\Str::plural('Project', $program->projects) }}</span>
                                        </span>
                                        <span class="proj-stat block">
                                            <span class="s-val block">{{ $program->trainors }}</span>
                                            <span class="s-cap block">Trainors</span>
                                        </span>
                                        <span class="proj-stat block">
                                            <span class="s-val block">{{ number_format($program->trainees) }}</span>
                                            <span class="s-cap block">Trainees</span>
                                        </span>
                                        <span class="proj-stat block">
                                            <span class="s-val block">{{ $program->activities }}</span>
                                            <span class="s-cap block">Activities</span>
                                        </span>
                                    </span>
                                </span>
                                <span class="block px-5 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                                    {{-- "hrs rendered", not "Training hours": no college
                                         or program has an hours TARGET, so the label must
                                         not read like one (PATTERNS §7). --}}
                                    <span class="text-[11.5px] text-gray-400 font-semibold">
                                        {{ number_format($program->training_hours, 1) }} hrs rendered
                                    </span>
                                    <span class="college-card-cta">
                                        Open projects <x-sc.icon name="chevron" class="w-3.5 h-3.5" />
                                    </span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 hub-empty">
                        <span class="hub-empty-badge"><x-sc.icon name="folder" class="w-6 h-6" /></span>
                        <p class="text-[13px] font-bold text-gray-600">This college delivers no programs yet</p>
                        <p class="text-[12px] text-gray-400 font-medium mt-1">
                            Programs are derived from the college's projects, so this fills in as projects are added.
                        </p>
                    </div>
                @endif
            </section>
        @endif

        {{-- Faculty assigned to this college --}}
        <section class="mt-8">
            <p class="hub-eyebrow">Faculty &amp; expertise</p>
            <h3 class="text-[15px] font-extrabold tracking-tight mt-1">
                Faculty assigned to {{ $selected['code'] }} ({{ $facultyRows->count() }})
            </h3>

            @if ($facultyRows->isNotEmpty())
                <div class="mt-4 grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
                    @foreach ($facultyRows as $f)
                        <a href="{{ route('faculty.show', $f['id']) }}"
                           class="sc-card p-4 reveal-item fac-mini" aria-label="Open profile of {{ $f['name'] }}">
                            <div class="flex items-center gap-3">
                                <span class="avatar w-9 h-9 text-[11px]">{{ $f['initials'] }}</span>
                                <div class="min-w-0">
                                    <p class="text-[12.5px] font-bold truncate">{{ $f['name'] }}</p>
                                    <p class="text-[10.5px] text-gray-400 font-medium truncate">{{ $f['position'] }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-3.5">
                                <div class="fac-mini-stat">
                                    <p class="fac-mini-val">{{ number_format((float) $f['rendered_hours'], 1) }}</p>
                                    <p class="fac-mini-lbl">Hours rendered</p>
                                </div>
                                <div class="fac-mini-stat">
                                    <p class="fac-mini-val">{{ $f['projects'] }}</p>
                                    <p class="fac-mini-lbl">{{ \Illuminate\Support\Str::plural('Project', $f['projects']) }}</p>
                                </div>
                            </div>
                            @if (! empty($f['expertise']))
                                <div class="flex flex-wrap gap-1.5 mt-3">
                                    @foreach (array_slice($f['expertise'], 0, 2) as $tag)
                                        <span class="expertise-tag">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mt-4 hub-empty">
                    <p class="text-[12.5px] text-gray-400 font-medium">No faculty mapped to this college yet.</p>
                </div>
            @endif
        </section>
    @else
        {{-- ============================================================
             VIEW 1 — THE THREE COLLEGE CARDS, NOTHING ELSE
             ============================================================ --}}
        <section class="pt-6 reveal-item">
            <div class="flex items-end justify-between gap-3 flex-wrap">
                <div>
                    <p class="hub-eyebrow">Colleges</p>
                    <h3 class="text-[15px] font-extrabold tracking-tight mt-1">Four colleges deliver CESO extension work</h3>
                </div>
                <div class="flex items-center gap-3">
                    {{-- The "View all programs →" link that used to sit here pointed
                         at the cross-college /programs page. Removed 2026-09-27: the
                         drill-down is College → Program → Projects → Activities, so
                         there is no cross-college entry point to offer. --}}
                    <p class="text-[11.5px] text-gray-400 font-medium hidden sm:block">Click a college to open its programs</p>
                </div>
            </div>

            {{-- 2x2: four colleges divide evenly, so xl:grid-cols-3 would strand a lone card. --}}
            <div class="mt-4 grid md:grid-cols-2 gap-5">
                @foreach ($cards as $card)
                    <button type="button" class="college-card reveal-item"
                            wire:click="selectCollege('{{ $card['code'] }}')"
                            aria-label="Open {{ $card['model']->name }}">

                        {{-- logo / media area — the official college seal on a
                             brand-tinted band. Falls back to the code crest when
                             no seal is on file (config `college_logos`); all four
                             colleges are sealed as of 2026-09-27, so the crest is
                             a safety net rather than a rendered state. --}}
                        <span class="college-media w-full" style="background:{{ $card['color'] }}">
                            @if ($card['logo'])
                                <img src="{{ asset($card['logo']) }}" alt="{{ $card['model']->name }} seal"
                                     class="college-logo" width="256" height="256">
                            @else
                                <span class="college-crest">{{ $card['code'] }}</span>
                            @endif
                        </span>

                        <span class="block px-6 pt-5 pb-6 flex-1 text-left">
                            <span class="block font-extrabold text-[17px] leading-snug tracking-tight">{{ $card['model']->name }}</span>
                            <span class="block text-[11.5px] text-gray-400 font-medium mt-1.5">
                                Extension Coordinator · {{ $card['model']->extensionCoordinator?->user?->name ?? '— unassigned —' }}
                            </span>

                            <span class="block text-[12.5px] text-gray-500 leading-relaxed mt-3.5 min-h-[54px]">
                                {{ $card['model']->description ?: $card['model']->short_name }}
                            </span>

                            {{-- Derived roll-ups only. A college has no training-hours target (§2.2B / D-R5). --}}
                            <span class="block mt-4 college-metrics">
                                <span>
                                    <span class="m-val block">{{ $card['projects'] }}</span>
                                    <span class="m-cap block">Projects</span>
                                </span>
                                <span>
                                    <span class="m-val block">{{ $card['programs'] }}</span>
                                    <span class="m-cap block">Programs</span>
                                </span>
                                <span>
                                    <span class="m-val block">{{ $card['faculty'] }}</span>
                                    <span class="m-cap block">Faculty</span>
                                </span>
                            </span>

                            <span class="flex flex-wrap gap-1.5 mt-4 min-h-[24px]">
                                @foreach ($card['program_titles'] as $title)
                                    <span class="badge badge-gray">{{ \Illuminate\Support\Str::limit($title, 30) }}</span>
                                @endforeach
                            </span>
                        </span>

                        <span class="college-foot w-full">
                            <span class="text-[11px] text-gray-400 font-medium">
                                {{ number_format($card['trainees']) }} reached
                                <span class="text-gray-300 mx-1.5">·</span>
                                ₱{{ number_format($card['budget_utilized']) }} utilized
                            </span>
                            <span class="college-card-cta">
                                Open programs <x-sc.icon name="chevron" class="w-3.5 h-3.5" />
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>

            @if ($cards->isEmpty())
                <div class="reveal-item sc-card px-5 py-12 text-center mt-4">
                    <p class="text-[13.5px] font-semibold text-gray-500">No colleges yet</p>
                    <p class="text-[12px] text-gray-400 mt-1">
                        The set is fixed and seeded — run
                        <span class="font-mono text-[11.5px]">php artisan db:seed --class=CollegeSeeder</span> to restore it.
                    </p>
                </div>
            @endif
        </section>
    @endif

    {{-- COLLEGE CRUD was REMOVED (2026-09-25, owner request): the college set is
         FIXED — CAS, COE, CME and the Graduate School — and `CollegeSeeder` is
         its only owner. The modals below are for the levels UNDER a college. --}}

    {{-- ============ BROAD PROGRAM create / edit (§25) ============
         Rendered with a server-side @if, NOT an Alpine `$wire.$watch` bridge.
         The watcher only fires on CHANGE, so a modal whose flag is already true
         when the component mounts never opens — the bug that made the old
         `?new=1` deep link land on a page with no form on it (§14). --}}
    @if ($showProgramForm)
        <div x-data @keydown.escape.window="$wire.closeProgramForm()"
             class="fixed inset-0 z-50 p-6 overflow-auto no-print">
            <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeProgramForm"></div>
            <form wire:submit="saveProgram" class="sc-modal relative max-w-2xl mx-auto mt-16 sc-card p-6 shadow-pop">
                <div class="flex items-start justify-between mb-1">
                    <div>
                        <h3 class="font-extrabold text-[16px] tracking-tight">
                            {{ $editingProgramId ? 'Edit Extension Program' : 'New Extension Program' }}
                        </h3>
                        <p class="text-[12px] text-gray-400 mt-0.5">
                            @if ($editingProgramId)
                                The code never changes — {{ $editingProgramId ? \App\Models\Program::find($editingProgramId)?->code : '' }}
                            @else
                                The code is generated automatically (PROG-{{ now()->format('Y') }}-nnn)
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="closeProgramForm"
                            class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>
                <p class="text-[12px] text-gray-400 font-medium mb-4">
                    Programs are the <span class="font-semibold text-charcoal">broad</span> thematic level —
                    the verbatim CESO thrusts. Projects sit inside a program.
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="label">Program title *</label>
                        <input required class="input" wire:model="programForm.title" placeholder="Literacy, Numeracy &amp; Language">
                        @error('programForm.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Pillar *</label>
                            <select class="input" wire:model="programForm.pillar">
                                @foreach ($formPillars as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('programForm.pillar') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Status *</label>
                            <select class="input" wire:model="programForm.status">
                                @foreach ($formProgramStatuses as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            @error('programForm.status') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">CESO thrust implemented *</label>
                        <input required class="input" wire:model="programForm.ceso_thrust" placeholder="Literacy, Numeracy &amp; Language Enhancement">
                        @error('programForm.ceso_thrust') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Description</label>
                        <textarea rows="2" class="input" wire:model="programForm.description"
                                  placeholder="What this program covers across the community"></textarea>
                        @error('programForm.description') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">Goals</label>
                        <textarea rows="2" class="input" wire:model="programForm.goals"
                                  placeholder="High-level goals for this thrust…"></textarea>
                        @error('programForm.goals') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Planning figures (optional)</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">
                            Informational only — not a target. Performance is measured per project (D-R5).
                        </p>
                        <div class="grid grid-cols-2 gap-3 mt-2.5">
                            <div>
                                <label class="label">Annual hours (planning)</label>
                                <input type="number" step="0.01" min="0" class="input" wire:model="programForm.annual_target_hours">
                                @error('programForm.annual_target_hours') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label">Annual budget (planning)</label>
                                <input type="number" step="0.01" min="0" class="input" wire:model="programForm.annual_target_budget">
                                @error('programForm.annual_target_budget') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" wire:click="closeProgramForm" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        {{ $editingProgramId ? 'Save changes' : 'Create program' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ============ PROJECT create (§25) ============
         College and program arrive PRE-FILLED from the view the Director was
         standing on, so the form never asks for the two things they just chose. --}}
    @if ($showProjectForm)
        <div x-data @keydown.escape.window="$wire.closeProjectForm()"
             class="fixed inset-0 z-50 p-6 overflow-auto no-print">
            <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeProjectForm"></div>
            <form wire:submit="saveProject" class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
                <div class="flex items-start justify-between mb-5">
                    <div>
                        <h3 class="font-extrabold text-[16px] tracking-tight">New Extension Project</h3>
                        <p class="text-[12px] text-gray-400 mt-0.5">Projects start in Draft — the code is auto-generated from the college.</p>
                    </div>
                    <button type="button" wire:click="closeProjectForm"
                            class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">College *</label>
                        <select required class="input" wire:model.live="projectForm.college_id">
                            <option value="">— select a college —</option>
                            @foreach ($formColleges as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} · {{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('projectForm.college_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Under program *</label>
                        <select required class="input" wire:model="projectForm.program_id">
                            <option value="">— select a program —</option>
                            @foreach ($formPrograms as $p)
                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                            @endforeach
                        </select>
                        @error('projectForm.program_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="label">Project title *</label>
                        <input required class="input" wire:model="projectForm.title" placeholder="e.g. LITRAWIYA: Barangay Reading Proficiency Program">
                        @error('projectForm.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="label">Description</label>
                        <textarea rows="2" class="input" wire:model="projectForm.description" placeholder="Short narrative description"></textarea>
                    </div>
                    <div>
                        <label class="label">Planned start *</label>
                        <input required type="date" class="input" wire:model="projectForm.planned_start_date">
                        @error('projectForm.planned_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Planned end *</label>
                        <input required type="date" class="input" wire:model="projectForm.planned_end_date">
                        @error('projectForm.planned_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Target beneficiaries</label>
                        <input type="number" min="1" class="input" wire:model="projectForm.target_beneficiaries" placeholder="e.g. 250">
                    </div>
                    <div>
                        <label class="label">Allocated budget (₱)</label>
                        <input type="number" min="0" step="0.01" class="input" wire:model="projectForm.allocated_budget" placeholder="e.g. 48000">
                    </div>
                    <div>
                        <label class="label">Target training hours (annual)</label>
                        <input type="number" min="0" step="0.01" class="input" wire:model="projectForm.annual_target_hours" placeholder="e.g. 120">
                    </div>
                    <div>
                        <label class="label">Project lead</label>
                        <select class="input" wire:model="projectForm.program_lead_id">
                            <option value="">— not assigned yet —</option>
                            @foreach ($formFaculties as $f)
                                <option value="{{ $f->id }}">{{ $f->user->name }} · {{ $f->department }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select class="input" wire:model="projectForm.status">
                            @foreach ($formProjectStatuses as $s)
                                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="label">Linked communities</label>
                        <x-sc.multi-select
                            :options="$formCommunities->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.' · '.$c->municipality.($c->isSchool() ? ' · School' : '')])->all()"
                            :selected="$projectForm['community_ids'] ?? []"
                            method="toggleProjectFormArray"
                            key="community_ids"
                            placeholder="— select communities —" />
                    </div>
                    <div class="col-span-2">
                        <label class="label">Beneficiary categories</label>
                        <x-sc.multi-select
                            :options="collect($formCategories)->map(fn ($cat) => ['id' => $cat, 'label' => $cat])->all()"
                            :selected="$projectForm['beneficiary_categories'] ?? []"
                            method="toggleProjectFormArray"
                            key="beneficiary_categories"
                            placeholder="— select categories —" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" wire:click="closeProjectForm" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </form>
        </div>
    @endif
</div>
