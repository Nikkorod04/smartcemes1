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
                        <label class="sc-search">
                            <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                            <input wire:model.live.debounce.300ms="projectSearch"
                                   class="input !w-60 !py-2" placeholder="Search project, lead, community…">
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

                <div class="college-hero college-overview-hero mt-4">
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
                                <p class="hub-eyebrow !text-lnu-700 mb-2">College overview</p>
                                <h2 class="college-hero-name">{{ $selected['model']->name }}</h2>
                                <p class="college-hero-sub">{{ $selected['model']->short_name }}</p>
                            </div>

                            {{-- The "Programs" button that used to sit here linked OUT
                                 to the cross-college /programs page. Removed 2026-09-27
                                 (§23): the programs are the next level of THIS page. --}}
                            <a href="{{ route('faculty.index', ['college' => $selected['code']]) }}"
                               class="btn btn-outline !px-3.5 !py-2 text-[12px] shrink-0">
                                <x-sc.icon name="users" class="w-4 h-4" /><span class="ml-1.5">View faculty</span>
                            </a>
                        </div>

                        @if ($selected['model']->description)
                            <p class="college-hero-desc">{{ $selected['model']->description }}</p>
                        @endif
                        <div class="college-hero-coordinator">
                            <span class="college-hero-coordinator-icon"><x-sc.icon name="users" class="w-4 h-4" /></span>
                            <span><span class="block text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Extension coordinator</span><span class="block text-[12.5px] font-bold text-charcoal mt-0.5">{{ $selected['model']->extensionCoordinator?->user?->name ?? 'Unassigned' }}</span></span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Derived KPIs — project-level quantities rolled up. A college has
                 no target of its own (§2.2B / D-R5), so no attainment appears
                 here, and there is deliberately NO hours tile (PATTERNS §7). --}}
            <section class="mt-4 grid grid-cols-2 xl:grid-cols-4 gap-3 reveal-item" aria-label="College totals">
                @foreach ([
                    ['clipboard', 'lnu', $selected['projects'], 'Projects', $selected['programs'].' extension programs'],
                    ['users', 'gold', number_format($selected['trainors']), 'Trainors', $selected['faculty'].' faculty members'],
                    ['people', 'emerald', number_format($selected['trainees']), 'Trainees', $selected['activities'].' activities delivered'],
                    ['wallet', 'slate', '₱'.number_format($selected['budget_utilized']), 'Budget utilized', 'Across this college'],
                ] as [$icon, $tone, $value, $caption, $sub])
                    <div class="hub-kpi college-overview-kpi college-overview-kpi--{{ $tone }}">
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
                        <h3 class="hub-sec-title !mt-0">{{ $selected['code'] }} extension programs</h3>
                    </div>
                    <div class="hub-sec-meta">
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
                    <div class="mt-4 grid xl:grid-cols-2 gap-4">
                        @foreach ($programRows as $program)
                            <button type="button"
                                    @if ($program->id) wire:click="selectProgram({{ $program->id }})" @endif
                                    class="prog-card college-program-card reveal-item"
                                    aria-label="Open {{ $program->title }}">
                                <span class="college-program-accent" style="background:{{ $selected['color'] }}"></span>
                                <span class="college-program-body">
                                    <span class="college-program-topline">
                                        <span class="font-mono">{{ $program->code ?: 'EXTENSION PROGRAM' }}</span>
                                        @if ($program->pillar)
                                            <span class="college-program-pillar">{{ ucfirst($program->pillar) }} pillar</span>
                                        @endif
                                    </span>
                                    <span class="college-program-title">{{ $program->title }}</span>
                                    <span class="college-program-primary">
                                        <span><strong>{{ $program->projects }}</strong> {{ \Illuminate\Support\Str::plural('project', $program->projects) }}</span>
                                        <span class="college-program-primary-divider" aria-hidden="true"></span>
                                        <span><strong>{{ $program->activities }}</strong> {{ \Illuminate\Support\Str::plural('activity', $program->activities) }}</span>
                                    </span>
                                    <span class="college-program-secondary">{{ $program->trainors }} trainors <span aria-hidden="true">·</span> {{ number_format($program->trainees) }} trainees <span aria-hidden="true">·</span> {{ number_format($program->training_hours, 1) }} hours rendered</span>
                                </span>
                                <span class="college-program-foot">
                                    <span>@if ($program->archived > 0){{ $program->archived }} archived {{ \Illuminate\Support\Str::plural('project', $program->archived) }}@else View project details @endif</span>
                                    <span class="college-program-cta">Open projects <x-sc.icon name="chevron-right" class="w-4 h-4" /></span>
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
        <section class="mt-9 college-faculty-section">
            <div class="hub-sec-head">
                <div>
                    <h3 class="hub-sec-title !mt-0">Faculty assigned to {{ $selected['code'] }}</h3>
                </div>
                <span class="college-faculty-count">{{ $facultyRows->count() }} {{ \Illuminate\Support\Str::plural('member', $facultyRows->count()) }}</span>
            </div>

            @if ($facultyRows->isNotEmpty())
                <div class="mt-4 grid lg:grid-cols-2 gap-3">
                    @foreach ($facultyRows as $f)
                        <a href="{{ route('faculty.show', $f['id']) }}"
                           class="college-faculty-card reveal-item" aria-label="Open profile of {{ $f['name'] }}">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="avatar w-10 h-10 text-[11px] shrink-0">{{ $f['initials'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[13px] font-extrabold text-charcoal truncate">{{ $f['name'] }}</p>
                                    <p class="text-[11px] text-gray-500 font-medium truncate">{{ $f['position'] }}</p>
                                </div>
                                <x-sc.icon name="chevron-right" class="w-4 h-4 text-gray-400 college-faculty-arrow" />
                            </div>
                            <div class="college-faculty-meta"><strong>{{ number_format((float) $f['rendered_hours'], 1) }}</strong> hours rendered <span aria-hidden="true">·</span> <strong>{{ $f['projects'] }}</strong> {{ \Illuminate\Support\Str::plural('project', $f['projects']) }}</div>
                            @if (! empty($f['expertise']))
                                <div class="flex flex-wrap gap-1.5 mt-2.5">
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
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
             role="dialog" aria-modal="true" aria-labelledby="program-form-title">
            <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="closeProgramForm"></div>
            <form wire:submit="saveProgram" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop" aria-labelledby="program-form-title">
                <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                                <x-sc.icon name="shield" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Extension program</p>
                                <h3 id="program-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">
                                    {{ $editingProgramId ? 'Edit Extension Program' : 'New Extension Program' }}
                                </h3>
                                <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">
                                    @if ($editingProgramId)
                                        Update the program record while keeping its generated code unchanged.
                                    @else
                                        Define a broad CESO thrust that projects and activities can sit under.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeProgramForm" aria-label="Close program form"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"><x-sc.icon name="x" class="h-4 w-4" /></button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex flex-wrap items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                        <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                        <p class="min-w-0 flex-1"><span class="font-extrabold">Program structure.</span> Programs are the broad thematic level based on CESO thrusts. Projects and activities are managed inside a program.</p>
                        <span class="badge badge-blue shrink-0 !bg-white !text-lnu-800">Code generated on save</span>
                    </div>

                    <div class="space-y-6">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Program identity</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Name the program and place it within the CESO framework.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="label" for="program-title">Program title <span class="text-red-500">*</span></label>
                                    <input id="program-title" required maxlength="255" class="input" wire:model="programForm.title" aria-invalid="{{ $errors->has('programForm.title') ? 'true' : 'false' }}" aria-describedby="program-title-hint program-title-error" placeholder="Literacy, Numeracy &amp; Language">
                                    <p id="program-title-hint" class="field-hint">Use a clear, reusable name for the broad extension thrust.</p>
                                    @error('programForm.title') <p id="program-title-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="label" for="program-pillar">Pillar <span class="text-red-500">*</span></label>
                                        <x-sc.select id="program-pillar" model="programForm.pillar" :value="$programForm['pillar']" :options="$formPillars" placeholder="— select a pillar —" :search="false" :required="true" :invalid="$errors->has('programForm.pillar')" />
                                        @error('programForm.pillar') <p id="program-pillar-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="label" for="program-status">Status <span class="text-red-500">*</span></label>
                                        <x-sc.select id="program-status" model="programForm.status" :value="$programForm['status']" :options="$formProgramStatuses" placeholder="— select a status —" :search="false" :required="true" :invalid="$errors->has('programForm.status')" />
                                        @error('programForm.status') <p id="program-status-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Thrust and intent</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Capture what this program implements and the change it aims to create.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="label" for="program-thrust">CESO thrust implemented <span class="text-red-500">*</span></label>
                                    <input id="program-thrust" required maxlength="255" class="input" wire:model="programForm.ceso_thrust" aria-invalid="{{ $errors->has('programForm.ceso_thrust') ? 'true' : 'false' }}" aria-describedby="program-thrust-hint program-thrust-error" placeholder="Literacy, Numeracy &amp; Language Enhancement">
                                    <p id="program-thrust-hint" class="field-hint">Describe the CESO thrust this program implements.</p>
                                    @error('programForm.ceso_thrust') <p id="program-thrust-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div x-data="{ length: {{ strlen($programForm['description'] ?? '') }} }">
                                    <label class="label" for="program-description">Description</label>
                                    <textarea id="program-description" rows="4" maxlength="4000" class="input resize-y" wire:model="programForm.description" x-on:input="length = $event.target.value.length" aria-invalid="{{ $errors->has('programForm.description') ? 'true' : 'false' }}" aria-describedby="program-description-count program-description-error" placeholder="What this program covers across the community"></textarea>
                                    <p id="program-description-count" class="sc-character-count"><span x-text="length"></span>/4,000 characters</p>
                                    @error('programForm.description') <p id="program-description-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div x-data="{ length: {{ strlen($programForm['goals'] ?? '') }} }">
                                    <label class="label" for="program-goals">Goals</label>
                                    <textarea id="program-goals" rows="4" maxlength="4000" class="input resize-y" wire:model="programForm.goals" x-on:input="length = $event.target.value.length" aria-invalid="{{ $errors->has('programForm.goals') ? 'true' : 'false' }}" aria-describedby="program-goals-count program-goals-error" placeholder="High-level goals for this thrust…"></textarea>
                                    <p id="program-goals-count" class="sc-character-count"><span x-text="length"></span>/4,000 characters</p>
                                    @error('programForm.goals') <p id="program-goals-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-lnu-700 ring-1 ring-gray-200"><x-sc.icon name="plus" class="h-4 w-4" /></span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold text-charcoal">Planning figures <span class="font-semibold text-gray-400">(optional)</span></h4>
                                    <p class="mt-1 text-[11px] font-medium leading-relaxed text-gray-500">Informational only — performance is measured per project, not against these planning values.</p>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="program-hours">Annual hours <span class="font-medium text-gray-400">(planning)</span></label>
                                    <input id="program-hours" type="number" inputmode="decimal" step="0.01" min="0" class="input bg-white" wire:model="programForm.annual_target_hours" aria-invalid="{{ $errors->has('programForm.annual_target_hours') ? 'true' : 'false' }}" placeholder="0.00">
                                    @error('programForm.annual_target_hours') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="program-budget">Annual budget <span class="font-medium text-gray-400">(planning)</span></label>
                                    <input id="program-budget" type="number" inputmode="decimal" step="0.01" min="0" class="input bg-white" wire:model="programForm.annual_target_budget" aria-invalid="{{ $errors->has('programForm.annual_target_budget') ? 'true' : 'false' }}" placeholder="0.00">
                                    @error('programForm.annual_target_budget') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="closeProgramForm" class="btn btn-ghost">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="saveProgram">
                                <span wire:loading.remove wire:target="saveProgram">{{ $editingProgramId ? 'Save changes' : 'Create program' }}</span>
                                <span wire:loading wire:target="saveProgram" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif

    {{-- ============ PROJECT create (§25) ============
         College and program arrive PRE-FILLED from the view the Director was
         standing on, so the form never asks for the two things they just chose. --}}
    @if ($showProjectForm)
        <div x-data @keydown.escape.window="$wire.closeProjectForm()"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
             role="dialog" aria-modal="true" aria-labelledby="project-form-title">
            <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="closeProjectForm"></div>
            <form wire:submit="saveProject" class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop" aria-labelledby="project-form-title">
                <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                                <x-sc.icon name="shield" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project workspace</p>
                                <h3 id="project-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">New Extension Project</h3>
                                <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Set up the project context, delivery window, ownership, and community reach before adding activities.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeProjectForm" aria-label="Close project form"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"><x-sc.icon name="x" class="h-4 w-4" /></button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex flex-wrap items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                        <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                        <p class="min-w-0 flex-1"><span class="font-extrabold">Draft-first setup.</span> The project code is generated automatically from the selected college. You can add activities after the project is created.</p>
                        <span class="badge badge-blue shrink-0 !bg-white !text-lnu-800">Code generated automatically</span>
                    </div>

                    <div class="space-y-6">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Project identity</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Connect this project to its college and extension program.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="project-college">College <span class="text-red-500">*</span></label>
                                    <x-sc.select id="project-college" model="projectForm.college_id" :value="$projectForm['college_id']" :options="$formColleges->mapWithKeys(fn ($c) => [$c->id => $c->code.' · '.$c->name])->all()" placeholder="— select a college —" :search="false" :required="true" :invalid="$errors->has('projectForm.college_id')" />
                                    @error('projectForm.college_id') <p id="project-college-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="project-program">Under program <span class="text-red-500">*</span></label>
                                    <x-sc.select id="project-program" model="projectForm.program_id" :value="$projectForm['program_id']" :options="$formPrograms->mapWithKeys(fn ($p) => [$p->id => $p->title])->all()" placeholder="— select a program —" :search="true" :required="true" :invalid="$errors->has('projectForm.program_id')" />
                                    <p class="field-hint">Choose the university-wide CESO program this project supports.</p>
                                    @error('projectForm.program_id') <p id="project-program-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label" for="project-title">Project title <span class="text-red-500">*</span></label>
                                    <input id="project-title" required maxlength="255" class="input" wire:model="projectForm.title" aria-invalid="{{ $errors->has('projectForm.title') ? 'true' : 'false' }}" aria-describedby="project-title-hint project-title-error" placeholder="e.g. LITRAWIYA: Barangay Reading Proficiency Program">
                                    <p id="project-title-hint" class="field-hint">Use a specific name that distinguishes this project from other work under the program.</p>
                                    @error('projectForm.title') <p id="project-title-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2" x-data="{ length: {{ strlen($projectForm['description'] ?? '') }} }">
                                    <label class="label" for="project-description">Description</label>
                                    <textarea id="project-description" rows="4" maxlength="4000" class="input resize-y" wire:model="projectForm.description" x-on:input="length = $event.target.value.length" aria-invalid="{{ $errors->has('projectForm.description') ? 'true' : 'false' }}" aria-describedby="project-description-count project-description-error" placeholder="Short narrative description"></textarea>
                                    <p id="project-description-count" class="sc-character-count"><span x-text="length"></span>/4,000 characters</p>
                                    @error('projectForm.description') <p id="project-description-error" class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Delivery plan</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Set the project window and the planning figures used by the team.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="project-start">Planned start <span class="text-red-500">*</span></label>
                                    <input id="project-start" required type="date" class="input" wire:model.live="projectForm.planned_start_date" aria-invalid="{{ $errors->has('projectForm.planned_start_date') ? 'true' : 'false' }}">
                                    <p class="field-hint">Choose the first day of project delivery.</p>
                                    @error('projectForm.planned_start_date') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="project-end">Planned end <span class="text-red-500">*</span></label>
                                    <input id="project-end" required type="date" class="input" wire:model.live="projectForm.planned_end_date" min="{{ $projectForm['planned_start_date'] ?: '' }}" aria-invalid="{{ $errors->has('projectForm.planned_end_date') ? 'true' : 'false' }}">
                                    <p class="field-hint">Must be the same day as or after the planned start.</p>
                                    @error('projectForm.planned_end_date') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="project-beneficiaries">Target beneficiaries</label>
                                    <input id="project-beneficiaries" type="number" inputmode="numeric" min="1" step="1" class="input" wire:model="projectForm.target_beneficiaries" aria-invalid="{{ $errors->has('projectForm.target_beneficiaries') ? 'true' : 'false' }}" placeholder="e.g. 250">
                                    @error('projectForm.target_beneficiaries') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="project-budget">Allocated budget <span class="font-medium text-gray-400">(₱)</span></label>
                                    <input id="project-budget" type="number" inputmode="decimal" min="0" step="0.01" class="input" wire:model="projectForm.allocated_budget" aria-invalid="{{ $errors->has('projectForm.allocated_budget') ? 'true' : 'false' }}" placeholder="e.g. 48000">
                                    @error('projectForm.allocated_budget') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="project-hours">Target training hours <span class="font-medium text-gray-400">(annual)</span></label>
                                    <input id="project-hours" type="number" inputmode="decimal" min="0" step="0.01" class="input" wire:model="projectForm.annual_target_hours" aria-invalid="{{ $errors->has('projectForm.annual_target_hours') ? 'true' : 'false' }}" placeholder="e.g. 120">
                                    @error('projectForm.annual_target_hours') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="project-status">Status</label>
                                    <x-sc.select id="project-status" model="projectForm.status" :value="$projectForm['status']" :options="$formProjectStatuses" placeholder="— select a status —" :search="false" :required="true" :invalid="$errors->has('projectForm.status')" />
                                    @error('projectForm.status') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">3</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">People and communities</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Add the lead, locations, and beneficiary groups connected to the project.</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="label" for="project-lead">Project lead</label>
                                    <x-sc.select id="project-lead" model="projectForm.program_lead_id" :value="$projectForm['program_lead_id']" :options="$formFaculties->mapWithKeys(fn ($f) => [$f->id => $f->user->name.' · '.$f->department])->all()" placeholder="— not assigned yet —" :search="true" :clearable="true" :invalid="$errors->has('projectForm.program_lead_id')" />
                                    @error('projectForm.program_lead_id') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Linked communities</label>
                                    <x-sc.multi-select
                                        :options="$formCommunities->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.' · '.$c->municipality.($c->isSchool() ? ' · School' : '')])->all()"
                                        :selected="$projectForm['community_ids'] ?? []"
                                        model="projectForm.community_ids"
                                        method="toggleProjectFormArray"
                                        key="community_ids"
                                        id="project-communities"
                                        :invalid="$errors->has('projectForm.community_ids')"
                                        placeholder="— select communities —" />
                                    @error('projectForm.community_ids') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Beneficiary categories</label>
                                    <x-sc.multi-select
                                        :options="collect($formCategories)->map(fn ($cat) => ['id' => $cat, 'label' => $cat])->all()"
                                        :selected="$projectForm['beneficiary_categories'] ?? []"
                                        model="projectForm.beneficiary_categories"
                                        method="toggleProjectFormArray"
                                        key="beneficiary_categories"
                                        id="project-categories"
                                        :invalid="$errors->has('projectForm.beneficiary_categories')"
                                        placeholder="— select categories —" />
                                    @error('projectForm.beneficiary_categories') <p class="sc-field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="closeProjectForm" class="btn btn-ghost">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="saveProject">
                                <span wire:loading.remove wire:target="saveProject">Create Project</span>
                                <span wire:loading wire:target="saveProject" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
