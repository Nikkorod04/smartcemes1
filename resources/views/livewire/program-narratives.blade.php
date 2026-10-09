{{--
    Project Narratives (2026-10-07 — search / filter / pagination pass).

    The sibling of the AI-analysis QUEUE (`ai-analysis.blade.php`): same control
    bar, same `q`/`state` URL aliases, same empty-state wording. What it does NOT
    borrow is the queue's shape — that page is a triage surface (compact rows),
    this one is the READING surface, so a card keeps its summary / risks /
    next-actions body and the controls wrap around it.

    Deliberate choices:
      - ONE container card, one row per project — not N floating cards.
      - The card body COLLAPSES (Alpine, keyed by `wire:key` so morphing keeps the
        open state). The header and the metric strip always show, so the page is
        scannable at a glance and readable on demand.
      - Only the TITLE AREA toggles. The Generate button sits outside it, so a
        click cannot both fire the action and collapse the card.
      - "Not generated" and "Failed" do NOT collapse: there is nothing to read, and
        the failure reason is the one thing you must see without a click.
      - The hero counts are PORTFOLIO figures (unfiltered) and are labelled as
        such, while the chips carry the view counts. One screen, two numbers, no
        ambiguity about which is which.
--}}
@php($stateMeta = [
    'not_generated' => ['badge-gray', 'not generated'],
    'failed' => ['badge-red', 'failed'],
    'pending' => ['badge-gold', 'generating'],
    'needs_attention' => ['badge-red', 'needs attention'],
    'at_risk' => ['badge-gold', 'at risk'],
    'on_track' => ['badge-green', 'on track'],
])

<div>
    {{-- ===================== PROJECT NARRATIVES HERO ===================== --}}
    <section class="pt-6 reveal-item">
        <div class="ai-panel p-6">
            <div class="flex items-start justify-between gap-6">
                <div class="min-w-0">
                <span class="ai-chip"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" /> Project Narratives</span>
                    <h1 class="mt-3 text-white font-extrabold text-xl tracking-tight leading-snug">Project status at a glance</h1>
                    <p class="mt-2 max-w-2xl text-[12.5px] leading-relaxed text-blue-100/75">Review AI-generated status narratives for extension projects — training delivery, reach, budget, risks, and recommended next actions. Director-only; aggregate data and provenance are preserved for audit.</p>
                </div>
                <span class="badge badge-blue shrink-0 !bg-white/10 !text-blue-100 !border-white/20">Aggregated data only · no PII</span>
            </div>

            {{-- Portfolio figures remain unfiltered; the project list below carries
                 the active search and state filter. --}}
            <div class="mt-5 grid grid-cols-2 gap-2 border-t border-white/10 pt-4 md:grid-cols-4">
                <div class="pn-portfolio-stat">
                    <span>Generated</span>
                    <strong>{{ $portfolio['generated'] }}<small>/{{ $portfolio['total'] }}</small></strong>
                    <em>projects with a narrative</em>
                </div>
                <button type="button" wire:click="filterBy('on_track')" aria-pressed="{{ $state === 'on_track' ? 'true' : 'false' }}" @class(['pn-portfolio-stat', 'pn-portfolio-stat--active' => $state === 'on_track', 'text-left transition hover:bg-white/10'])>
                    <span>On track</span>
                    <strong>{{ $portfolio['on_track'] }}</strong>
                    <em>healthy projects</em>
                </button>
                <button type="button" wire:click="filterBy('at_risk')" aria-pressed="{{ $state === 'at_risk' ? 'true' : 'false' }}" @class(['pn-portfolio-stat', 'pn-portfolio-stat--active' => $state === 'at_risk', 'text-left transition hover:bg-white/10'])>
                    <span>At risk</span>
                    <strong>{{ $portfolio['at_risk'] }}</strong>
                    <em>watch closely</em>
                </button>
                <button type="button" wire:click="filterBy('needs_attention')" aria-pressed="{{ $state === 'needs_attention' ? 'true' : 'false' }}" @class(['pn-portfolio-stat', 'pn-portfolio-stat--active' => $state === 'needs_attention', 'text-left transition hover:bg-white/10'])>
                    <span>Needs attention</span>
                    <strong>{{ $portfolio['needs_attention'] }}</strong>
                    <em>needs Director review</em>
                </button>
            </div>
        </div>
    </section>

    {{-- ===================== CONTAINER ===================== --}}
    <section id="pn-project-list" aria-label="Project narratives list" class="reveal-item sc-card p-0 overflow-hidden mt-5">
        {{-- ===================== CONTROLS ===================== --}}
        <div class="border-b border-gray-100 bg-white px-5 py-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="sc-search min-w-[220px] flex-1 max-w-sm">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input id="pn-project-search" type="text" wire:model.live.debounce.300ms="search"
                           class="input"
                           placeholder="Search a project, code, lead or community…"
                           aria-label="Search projects by title, code, lead or community"
                           aria-controls="pn-project-list">
                    @if ($search !== '')
                        <button type="button" wire:click="clearSearch"
                                class="sc-search__clear"
                                aria-label="Clear search">
                            <x-sc.icon name="x" class="w-4 h-4" />
                        </button>
                    @endif
                </div>

                <span class="pn-results-summary ml-auto text-[11px] text-gray-400 font-semibold whitespace-nowrap" aria-live="polite">
                    <span wire:loading wire:target="search,filterBy" class="pn-control-spinner" aria-hidden="true"></span>
                    <span wire:loading.remove wire:target="search,filterBy">{{ $visibleCount }} of {{ $portfolio['total'] }} {{ \Illuminate\Support\Str::plural('project', $portfolio['total']) }}</span>
                    <span wire:loading wire:target="search,filterBy">Updating projects…</span>
                </span>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3" role="group" aria-label="Filter projects by narrative state">
                <span class="mr-1 text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Show</span>
                @foreach ($filters as $key => $label)
                    {{-- One @class directive, never two class attributes (handoff §14). --}}
                    <button type="button" wire:click="filterBy('{{ $key }}')" aria-pressed="{{ $state === $key ? 'true' : 'false' }}" wire:loading.attr="disabled" wire:target="filterBy"
                            @class(['chip', 'on' => $state === $key])>
                        {{ $label }} <span class="ml-1 font-mono font-bold">{{ $counts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ===================== ROWS ===================== --}}
        @forelse ($rows as $row)
            @php($p = $row['model'])
            @php($latest = $row['latest'])
            @php($versions = $p->programNarratives->sortByDesc('created_at')->values())
            @php($isCompleted = $latest !== null && $latest->status === 'completed')
            @php($isOpenable = $isCompleted)

            <div @class([
                    'pn-project-row',
                    'pn-project-row--completed' => $isCompleted,
                    'pn-project-row--failed' => $latest?->status === 'failed',
                    'pn-project-row--pending' => $latest?->status === 'pending',
                    'pn-project-row--empty' => $latest === null,
                ]) wire:key="pn-{{ $p->id }}"
                 @if ($isOpenable) x-data="{ open: false }" @endif>

                {{-- ---------- HEADER (always visible) ---------- --}}
                <div class="pn-project-header flex items-start justify-between gap-4 px-5 py-4">
                    <div class="min-w-0 flex-1">
                        @if ($isOpenable)
                            {{-- The toggle is the TITLE AREA only, so the Generate button
                                 beside it can never double as a collapse control. --}}
                            <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-controls="pn-reading-{{ $p->id }}" :aria-label="open ? 'Collapse narrative reading view' : 'Expand narrative reading view'"
                                    class="group/t flex items-start gap-2 w-full text-left">
                                <span class="shrink-0 mt-0.5 text-gray-300 transition-transform duration-150 group-hover/t:text-lnu-700" :class="open ? 'rotate-90' : ''">
                                    <x-sc.icon name="chevron-right" class="w-4 h-4" />
                                </span>
                                <span class="min-w-0">
                                    @include('livewire.partials.program-narrative-heading', ['p' => $p])
                                </span>
                            </button>
                            {{-- Collapsed preview: enough to recognise the narrative, not enough
                                 to make the page a wall of text. --}}
                            <p x-show="!open" x-cloak class="mt-2 pl-6 text-[12px] text-gray-500 leading-relaxed">
                                {{ \Illuminate\Support\Str::limit((string) $latest->summary, 170) }}
                            </p>
                        @else
                            <div class="pl-0">
                                @include('livewire.partials.program-narrative-heading', ['p' => $p])
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if ($isCompleted)
                            <span class="narrative-health {{ $latest->health_label }}">{{ match ($latest->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}</span>
                        @elseif ($latest && $latest->status === 'failed')
                            <span class="narrative-health needs-attention">Narrative unavailable</span>
                        @endif
                        <button type="button" @click="$dispatch('project-narrative-generate', { title: @js($p->title), code: @js($p->code), college: @js($p->college?->name ?? 'College not linked'), lead: @js($p->programLead?->user?->name ?? 'No project lead assigned'), communities: @js($p->communities->pluck('name')->values()->all()) })" wire:click="generate({{ $p->id }})" wire:loading.attr="disabled" wire:target="generate" class="btn btn-primary !px-3 !py-1.5 !text-[11.5px]">
                            <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" />Generate</span>
                            <span wire:loading wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5 animate-spin" />Generating…</span>
                        </button>
                    </div>
                </div>

                {{-- ---------- METRIC STRIP (always visible — the "at a glance" part) ---------- --}}
                <div class="pn-project-metrics px-5 pb-4">
                    <div class="pn-metric"><span>Trainors</span><strong>{{ $row['trainors'] }}</strong></div>
                    <div class="pn-metric"><span>Trainees</span><strong>{{ number_format($row['trainees']) }}</strong></div>
                    <div class="pn-metric"><span>Training hours</span><strong>{{ number_format($row['training_hours'], 1) }}</strong></div>
                    <div class="pn-metric">
                        <span>Target attainment</span>
                        @if ($row['hours_pct'] !== null)
                            <strong><span @class(['badge !text-[10px]', $row['hours_pct'] >= 100 ? 'badge-green' : 'badge-blue'])>{{ round($row['hours_pct']) }}%</span></strong>
                            <small>of {{ number_format($row['target_hours']) }} hrs</small>
                        @else
                            <strong><span class="badge badge-gray !text-[10px]">No target</span></strong>
                        @endif
                    </div>
                    <div class="pn-metric"><span>Activities</span><strong>{{ $row['activities'] }}</strong></div>
                    <div class="pn-metric"><span>Status</span><strong><span class="badge {{ $stateMeta[$row['state']][0] ?? 'badge-gray' }} !text-[10px]">{{ $stateMeta[$row['state']][1] ?? $row['state'] }}</span></strong></div>
                </div>

                {{-- ---------- NON-COMPLETED STATES (never collapsed) ---------- --}}
                @if ($latest === null)
                    <div class="px-5 pb-4">
                        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50/60 p-4">
                            <div class="flex items-start gap-3">
                                <span class="w-9 h-9 rounded-xl bg-lnu-50 text-lnu-700 flex items-center justify-center shrink-0"><x-sc.icon name="clock" class="w-[18px] h-[18px]" /></span>
                                <div class="min-w-0">
                                    <p class="text-[12.5px] font-bold text-gray-600">No narrative yet</p>
                                    <p class="text-[12px] text-gray-500 mt-0.5 leading-relaxed">No narrative has been generated for this project yet. Generation is Director-only and uses aggregate project data (no PII).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($latest->status === 'failed')
                    <div class="px-5 pb-4">
                        <div class="rounded-xl border border-red-200 bg-red-50/70 p-4">
                            <div class="flex items-start gap-3">
                                <span class="w-9 h-9 rounded-xl bg-red-100 text-red-500 flex items-center justify-center shrink-0"><x-sc.icon name="clock" class="w-[18px] h-[18px]" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[12.5px] font-bold text-gray-700">Narrative unavailable</p>
                                    <p class="text-[12px] text-red-600 mt-0.5 leading-relaxed">{{ $latest->error_message }}</p>
                                    <button type="button" @click="$dispatch('project-narrative-generate', { title: @js($p->title), code: @js($p->code), college: @js($p->college?->name ?? 'College not linked'), lead: @js($p->programLead?->user?->name ?? 'No project lead assigned'), communities: @js($p->communities->pluck('name')->values()->all()) })" wire:click="generate({{ $p->id }})" wire:loading.attr="disabled" wire:target="generate" class="btn btn-danger-soft !px-3 !py-1.5 !text-[11.5px] mt-3">
                                        <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5" />Regenerate</span>
                                        <span wire:loading wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5 animate-spin" />Generating…</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($latest->status === 'pending')
                    <div class="px-5 pb-4">
                        <div class="rounded-xl border border-gold-200 bg-gold-50/60 p-4">
                            <div class="flex items-start gap-3">
                                <span class="w-9 h-9 rounded-xl bg-gold-100 text-gold-700 flex items-center justify-center shrink-0"><x-sc.icon name="clock" class="w-[18px] h-[18px]" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[12.5px] font-semibold text-gold-800 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-gold-500 pulse-dot"></span>Generating…</p>
                                    <div class="mt-3 space-y-2 max-w-xl">
                                        <div class="skeleton h-3 rounded-md" style="width:96%"></div>
                                        <div class="skeleton h-3 rounded-md" style="width:88%"></div>
                                        <div class="skeleton h-3 rounded-md" style="width:62%"></div>
                                    </div>
                                    <p class="text-[11px] text-gold-700/70 mt-3">Started {{ $latest->created_at->diffForHumans() }} · {{ $latest->metadata['model'] ?? 'gemini' }} · prompt {{ $latest->metadata['prompt_version'] ?? '—' }}</p>
                                    {{-- Generation is SYNCHRONOUS, so a row still pending from an
                                         earlier request was interrupted — a timeout against the
                                         client's 120s wall-clock budget, a fatal, an aborted
                                         request — and nothing will ever move it. Without this it
                                         reads "Generating…" forever, which is exactly the silent
                                         error the first-class failure state exists to prevent. --}}
                                    <button type="button" @click="$dispatch('project-narrative-generate', { title: @js($p->title), code: @js($p->code), college: @js($p->college?->name ?? 'College not linked'), lead: @js($p->programLead?->user?->name ?? 'No project lead assigned'), communities: @js($p->communities->pluck('name')->values()->all()) })" wire:click="generate({{ $p->id }})" wire:loading.attr="disabled" wire:target="generate" class="btn btn-outline !px-3 !py-1.5 !text-[11.5px] mt-3">
                                        <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5" />Start a new generation</span>
                                        <span wire:loading wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5 animate-spin" />Generating…</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- ---------- COMPLETED: the collapsible reading body ---------- --}}
                    <div id="pn-reading-{{ $p->id }}" x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" class="pn-reading-body px-5 pb-5">
                        <div class="pn-reading-grid">
                            <article class="pn-content-card pn-content-card--summary">
                                <p class="pn-content-label"><x-sc.icon name="doc" class="h-3.5 w-3.5" /> Summary</p>
                                <p class="pn-summary-text">{{ $latest->summary }}</p>
                            </article>

                            <article class="pn-content-card">
                                <p class="pn-content-label"><span class="h-1.5 w-1.5 rounded-full bg-red-400"></span> Top risks</p>
                                <ul class="pn-content-list pn-content-list--risks">
                                    @forelse ($latest->risks ?? [] as $risk)
                                        <li><span class="pn-list-marker">●</span><span>{{ is_array($risk) ? ($risk['risk'] ?? $risk['text'] ?? '') : $risk }}</span></li>
                                    @empty
                                        <li class="pn-empty-copy">No material risks identified.</li>
                                    @endforelse
                                </ul>
                            </article>

                            <article class="pn-content-card">
                                <p class="pn-content-label"><span class="h-1.5 w-1.5 rounded-sm bg-lnu-400"></span> Recommended Next Actions</p>
                                <ul class="pn-content-list pn-content-list--actions">
                                    @forelse ($latest->recommendations ?? [] as $r)
                                        @php($prioTone = (($r['priority'] ?? '') === 'High') ? 'badge-red' : ((($r['priority'] ?? '') === 'Medium') ? 'badge-gold' : 'badge-gray'))
                                        <li>
                                            <span class="pn-list-marker">▸</span>
                                            <span class="min-w-0">
                                                <span class="flex flex-wrap items-start gap-1.5">
                                                    @if (! empty($r['priority']))<span class="badge {{ $prioTone }} !text-[10px] shrink-0">{{ $r['priority'] }}</span>@endif
                                                    <span class="font-semibold">{{ $r['action'] ?? '' }}</span>
                                                </span>
                                                @if (! empty($r['rationale']))<span class="pn-supporting-copy">{{ $r['rationale'] }}</span>@endif
                                            </span>
                                        </li>
                                    @empty
                                        <li class="pn-empty-copy">No pending actions.</li>
                                    @endforelse
                                </ul>
                            </article>
                        </div>
                        <div class="pn-provenance mt-4 flex flex-wrap items-center justify-between gap-2">
                            <p>Generated {{ $latest->generated_at?->format('M j, Y · g:i A') }} · {{ $latest->generator?->name ?? 'Director, CESO' }}</p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="badge badge-gray !text-[10px] font-mono">{{ $latest->metadata['model'] ?? 'gemini' }}</span>
                                <span class="badge badge-gray !text-[10px]">prompt {{ $latest->metadata['prompt_version'] ?? '—' }}</span>
                                <span class="badge badge-gray !text-[10px]">confidence {{ $latest->confidence_score !== null ? number_format((float) $latest->confidence_score, 2) : '—' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ---------- VERSION HISTORY (any state) ---------- --}}
                @if ($versions->count())
                    <div class="pn-history-wrap px-5 pb-4">
                        <details class="pn-history" wire:key="pn-acc-{{ $p->id }}">
                            <summary class="pn-history-summary"><span class="inline-flex items-center gap-2"><x-sc.icon name="clock" class="h-3.5 w-3.5" /> Version history <span class="acc-num">{{ $versions->count() }}</span></span><span class="pn-history-summary-hint">Review attempts and provenance</span><svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg></summary>
                            <div class="pn-history-content">
                                <ol class="pn-history-list">
                                    @foreach ($versions as $v)
                                        @php($failed = $v->status === 'failed')
                                        @php($pending = $v->status === 'pending')
                                        @php($isCurrent = $latest?->id === $v->id)
                                        <li @class(['pn-history-item', 'pn-history-item--failed' => $failed, 'pn-history-item--pending' => $pending, 'pn-history-item--current' => $isCurrent]) wire:key="pnv-{{ $v->id }}">
                                            <span class="pn-history-marker"></span>
                                            <div class="pn-history-main">
                                                <div class="pn-history-heading">
                                                    <p class="pn-history-title">
                                                        @if ($failed)
                                                            Generation attempt — failed
                                                        @elseif ($pending)
                                                            {{-- A row still pending is an attempt that never FINISHED:
                                                                 generation is synchronous, so it was interrupted. Labeling
                                                                 it "Narrative vN · Generated <date>" would claim a
                                                                 narrative that does not exist — which is what the dev
                                                                 database's two interrupted rows were shown as. --}}
                                                            Generation attempt — never completed
                                                        @else
                                                            Narrative v{{ $versions->count() - $loop->index }}
                                                            @if ($v->health_label) · {{ match ($v->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}@endif
                                                        @endif
                                                        @if ($isCurrent)<span class="badge badge-blue !text-[10px] ml-1.5">Current</span>@endif
                                                    </p>
                                                    <time class="pn-history-date" datetime="{{ $v->created_at->toIso8601String() }}">{{ $v->created_at->format('M j, Y') }}</time>
                                                </div>
                                                <p class="pn-history-detail">
                                                        @if ($failed)
                                                            {{ $v->error_message ?? 'Narrative unavailable' }}
                                                        @elseif ($pending)
                                                            Started {{ $v->created_at->format('M j, Y · g:i A') }} · interrupted before it finished
                                                        @else
                                                            Generated {{ $v->created_at->format('M j, Y · g:i A') }} · {{ $v->generator?->name ?? 'Director, CESO' }} · {{ $v->metadata['model'] ?? 'gemini' }}
                                                        @endif
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                                <p class="pn-history-note">Every generation creates a new row. Failed and interrupted attempts remain visible so the Director can distinguish a completed narrative from an incomplete attempt.</p>
                            </div>
                        </details>
                    </div>
                @endif
            </div>
        @empty
            {{-- Distinguish the three empties: they need different fixes. --}}
            <div class="pn-empty-state p-9 text-center" role="status">
                <span class="pn-empty-icon"><x-sc.icon name="sparkles" class="w-6 h-6" /></span>
                <span class="pn-empty-kicker">Project narratives</span>
                @if ($search !== '')
                    <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">No project matches “{{ $search }}”</h2>
                    <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">
                        No project title, code, lead or community contains that.
                        @if ($state !== '')
                            It is also filtered to <b>{{ $filters[$state] ?? $state }}</b>.
                        @endif
                    </p>
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                        <button type="button" wire:click="clearSearch" class="btn btn-outline">Clear search</button>
                        @if ($state !== '')
                            <button type="button" wire:click="filterBy('')" class="btn btn-ghost">Show all states</button>
                        @endif
                    </div>
                @elseif ($portfolio['total'] === 0)
                    <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">No projects yet</h2>
                    <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">Create an extension project and its narrative will appear here.</p>
                @else
                    <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">Nothing in this state</h2>
                    <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">No project's latest narrative is in this state. Choose <b>All</b> to see every project.</p>
                    <div class="mt-5">
                        <button type="button" wire:click="filterBy('')" class="btn btn-outline">Show all</button>
                    </div>
                @endif
            </div>
        @endforelse

        {{-- ===================== PAGER ===================== --}}
        @if ($paginator->hasPages())
            <div class="px-5 py-3.5 border-t border-gray-100">
                {{-- "of N" counts PROJECTS — one row per project, so this is the project count. --}}
                @include('livewire.partials.pagination', ['paginator' => $paginator])
            </div>
        @endif
    </section>

    <footer class="mt-10 text-center text-[11px] text-gray-300 font-medium no-print">
        SmartCEMES · Community Extension Services Office · Leyte Normal University
    </footer>

    @include('livewire.partials.program-narrative-generation-modals', ['actionTarget' => 'generate'])
    @include('livewire.partials.program-narrative-view-modal')
</div>
