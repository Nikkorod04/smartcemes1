{{--
    The AI analysis QUEUE (2026-10-07 redesign; container + pagination pass).

    The index of the split: this page answers "what analyses exist, and what state
    is each in". Reading one happens on `ai-analysis.show`.

    Deliberate choices:
      - NO page heading and no "Director-only" badge. The sidebar already names the
        page, and the D3 boundary is stated in the hero panel and the legend below
        — the same call the Faculty Management board made (P0i/P0j).
      - ONE container card, with community names as group header ROWS inside it.
        Previously each community was its own card, so 21 communities meant 21
        floating cards.
      - Pagination is by COMMUNITY GROUP, not by row: a community's generations must
        not straddle a page break.
      - Generate / Retry are revealed on hover through the `.hover-reveal` utility
        in `app.css`, which is gated on `@media (hover: hover)` rather than on a
        bare breakpoint. A plain `lg:opacity-0` also hides the button on a TOUCH
        device at >=1024px, where there is no hover to bring it back — an invisible
        but tappable control. `:focus-within` covers keyboard users, and a pointer
        without hover simply keeps the action visible.
--}}
@php($stateMeta = [
    'awaiting_review' => ['badge-yellow', 'awaiting review'],
    'approved' => ['badge-green', 'approved'],
    'discarded' => ['badge-gray', 'discarded'],
    'failed' => ['badge-red', 'failed'],
    'pending' => ['badge-gray', 'generating'],
    'awaiting_analysis' => ['badge-blue', 'awaiting analysis'],
])

<div>
    <section class="reveal-item sc-card p-0 overflow-hidden">
        {{-- ===================== CONTROLS ===================== --}}
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
            {{-- Search — the page groups by community, so this is how you find one
                 barangay among 21. Bound with the house debounce, and `q` is the
                 house URL alias so the query string matches the rest of the app. --}}
            <div class="flex flex-wrap items-center gap-3">
                <div class="sc-search min-w-[220px] flex-1 max-w-sm">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="input"
                           placeholder="Search a barangay…"
                           aria-label="Search by community">
                    @if ($search !== '')
                        <button type="button" wire:click="clearSearch"
                                class="sc-search__clear"
                                aria-label="Clear search">
                            <x-sc.icon name="x" class="w-4 h-4" />
                        </button>
                    @endif
                </div>

                {{-- The pager's "of N" counts COMMUNITIES, so label the totals. --}}
                <span class="ml-auto text-[11px] text-gray-400 font-semibold whitespace-nowrap">
                    {{ $communityCount }} {{ \Illuminate\Support\Str::plural('community', $communityCount) }} ·
                    {{ $rowCount }} {{ \Illuminate\Support\Str::plural('row', $rowCount) }}
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @foreach ($filters as $key => $label)
                    {{-- One @class directive, never two class attributes (handoff §14). --}}
                    <button type="button" wire:click="filterBy('{{ $key }}')"
                            @class(['chip', 'on' => $state === $key])>
                        {{ $label }} <span class="ml-1 font-mono font-bold">{{ $counts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ===================== GROUPS ===================== --}}
        @forelse ($groups as $community => $rows)
            @php($communityId = $rows->first()['communityId'] ?? null)
            <div class="px-5 py-2.5 bg-gray-50/70 border-b border-gray-100 flex items-center justify-between gap-3">
                {{-- The community is the deep link the queue cannot otherwise offer:
                     with group-level pagination its rows can sit on any page. --}}
                @if ($communityId !== null)
                    <a href="{{ route('ai-analysis.community', ['community' => $communityId]) }}"
                       class="group/link flex items-center gap-1.5 min-w-0 text-[12.5px] font-extrabold tracking-tight hover:text-lnu-800 transition">
                        <span class="truncate">{{ $community }}</span>
                        <x-sc.icon name="chevron-right" class="w-3.5 h-3.5 shrink-0 text-gray-300 group-hover/link:text-lnu-800 transition" />
                    </a>
                @else
                    <h2 class="text-[12.5px] font-extrabold tracking-tight truncate">{{ $community }}</h2>
                @endif
                <span class="text-[11px] text-gray-400 font-semibold shrink-0">
                    {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('row', $rows->count()) }}
                </span>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach ($rows as $row)
                    @php($meta = $stateMeta[$row['state']] ?? ['badge-gray', $row['state']])

                    @if ($row['kind'] === 'awaiting')
                        {{-- A validated summary with no analysis yet. --}}
                        <div class="group flex items-center gap-3 flex-wrap px-5 py-3.5 transition hover:bg-gray-50/60"
                             wire:key="ai-await-{{ $row['summary']->id }}">
                            <span class="badge {{ $meta[0] }}">{{ $meta[1] }}</span>
                            <span class="text-[12.5px] font-semibold">{{ $row['period'] }}</span>
                            <span class="text-[11.5px] text-gray-400">{{ $row['summary']->total_responses }} validated responses</span>
                            <span class="hover-reveal ml-auto flex items-center gap-3 shrink-0">
                                <button wire:click="generate({{ $row['summary']->id }})"
                                        wire:target="generate({{ $row['summary']->id }})" wire:loading.attr="disabled"
                                        class="btn btn-primary !px-3 !py-1.5 text-[11.5px]">
                                    <span wire:loading.remove wire:target="generate({{ $row['summary']->id }})" class="inline-flex items-center gap-1.5"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" />Generate</span>
                                    <span wire:loading wire:target="generate({{ $row['summary']->id }})" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5 animate-spin" />Generating…</span>
                                </button>
                            </span>
                        </div>
                    @else
                        @php($a = $row['analysis'])
                        <div class="group flex items-center gap-3 flex-wrap px-5 py-3.5 transition hover:bg-gray-50/60"
                             wire:key="ai-row-{{ $a->id }}">
                            <span class="badge {{ $meta[0] }}">
                                @if ($row['state'] === 'awaiting_review' || $row['state'] === 'pending')
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 pulse-dot"></span>
                                @endif
                                {{ $meta[1] }}
                            </span>
                            <span class="text-[12.5px] font-semibold">{{ $row['period'] }}</span>

                            @if ($a->generationCount() > 1)
                                <span class="text-[11.5px] text-gray-400 font-semibold">gen {{ $a->generationIndex() }} of {{ $a->generationCount() }}</span>
                                @if ($a->isCurrent())
                                    <span class="badge badge-green">current</span>
                                @elseif ($a->isSuperseded())
                                    <span class="badge badge-gray">superseded</span>
                                @endif
                            @endif

                            @if ($row['state'] === 'failed')
                                {{-- The provider's raw body is kept in the title; this is the readable form. --}}
                                <span class="text-[11.5px] text-red-600 font-semibold" title="{{ $a->error_message }}">{{ $a->failureSummary() }}</span>
                            @elseif ($row['state'] === 'approved' && $a->approver)
                                <span class="text-[11.5px] text-gray-400">Approved by {{ $a->approver->name }}</span>
                            @endif

                            <span class="ml-auto flex items-center gap-3 shrink-0">
                                <span class="text-[11.5px] text-gray-400 whitespace-nowrap hidden md:inline">{{ $a->created_at->format('M j, Y g:i A') }}</span>
                                <span class="font-mono text-[11px] text-gray-400 hidden xl:inline">{{ $a->metadata['model'] ?? '—' }}</span>

                                {{-- Opening an analysis is the PRIMARY action, so it stays visible.
                                     Only the secondary action is revealed on hover. --}}
                                @if ($row['state'] === 'failed')
                                    <span class="hover-reveal">
                                        <button wire:click="retry({{ $a->id }})" wire:target="retry({{ $a->id }})" wire:loading.attr="disabled"
                                                class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">⟳ Retry</button>
                                    </span>
                                @elseif ($row['state'] === 'pending')
                                    {{-- Generation is SYNCHRONOUS, so a row still pending was
                                         interrupted and will never resolve on its own — and
                                         `retry()` deliberately refuses anything but `failed`
                                         (422, "only a failed generation can be retried"), and
                                         this row has no link in. Start a FRESH generation rather
                                         than relax that guard: the stuck attempt stays as history,
                                         exactly as the narratives page does (revisions.md §34.1a). --}}
                                    @if ($row['summary'])
                                        <span class="hover-reveal">
                                            <button wire:click="generate({{ $row['summary']->id }})"
                                                    wire:target="generate({{ $row['summary']->id }})" wire:loading.attr="disabled"
                                                    class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">Start a new generation</button>
                                        </span>
                                    @else
                                        <span class="text-[11px] text-gray-400">generating…</span>
                                    @endif
                                @else
                                    <a href="{{ route('ai-analysis.show', ['analysis' => $a]) }}" class="text-[12px] font-bold text-lnu-800 whitespace-nowrap">
                                        {{ $row['state'] === 'awaiting_review' ? 'Review →' : 'View →' }}
                                    </a>
                                @endif
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        @empty
            <div class="p-9 text-center">
                <span class="w-[52px] h-[52px] rounded-2xl bg-gold-50 text-gold-500 mx-auto flex items-center justify-center"><x-sc.icon name="sparkles" class="w-6 h-6" /></span>
                @if ($search !== '')
                    {{-- Distinguish "your search found nothing" from "this state is
                         empty" — they need different fixes. --}}
                    <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">No community matches “{{ $search }}”</h2>
                    <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">
                        No community in the queue has a name containing that.
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
                @else
                    <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">Nothing in this state</h2>
                    <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">
                        No analysis matches this filter. Choose <b>All</b> to see the whole queue.
                    </p>
                @endif
            </div>
        @endforelse

        {{-- ===================== PAGER ===================== --}}
        @if ($groups->hasPages())
            <div class="px-5 py-3.5 border-t border-gray-100">
                @include('livewire.partials.pagination', ['paginator' => $groups])
            </div>
        @endif
    </section>

    {{-- ===================== PIPELINE LEGEND ===================== --}}
    <p class="reveal-item mt-4 text-[11.5px] text-gray-400 leading-relaxed max-w-4xl">
        Pipeline states are first-class UI states: <span class="badge badge-gray !text-[10px]">pending</span> generating ·
        <span class="badge badge-yellow !text-[10px]">awaiting review</span> draft ·
        <span class="badge badge-green !text-[10px]">approved</span> citable ·
        <span class="badge badge-red !text-[10px]">failed</span> "analysis unavailable" — never a silent error.
        <b>Awaiting analysis</b> is derived: a validated summary with no analysis yet. Manual retry is available to the Director.
    </p>

    {{-- ===================== GENERATION LOADING =====================
         Generation is synchronous today, so the cancel action records a shared
         cancellation request and immediately closes this UI. The server then
         prevents the in-flight result from being treated as a successful draft. --}}
    <div wire:loading.flex wire:target="generate"
         x-data="{ cancelConfirm: false, canceled: false }"
         x-show="!canceled" x-cloak
         class="fixed inset-0 z-[70] items-center justify-center p-3 sm:p-6 no-print"
         role="dialog" aria-modal="true" aria-labelledby="ai-generation-loading-title">
        <div class="fixed inset-0 sc-modal-backdrop"></div>
        <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
            <header class="shrink-0 border-b border-white/10 bg-gradient-to-br from-lnu-900 via-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                        <x-sc.icon name="sparkles" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Assessment intelligence</p>
                        <h3 id="ai-generation-loading-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Generating AI Analysis</h3>
                        <p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">The validated community responses are being converted into a reviewable draft.</p>
                    </div>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-6 sm:px-6">
                <div x-show="!cancelConfirm" x-cloak>
                    <div class="flex items-center gap-3 rounded-xl border border-lnu-100 bg-lnu-50/70 px-4 py-3.5 text-[12px] text-lnu-900">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-lnu-700 shadow-sm ring-1 ring-lnu-100">
                            <x-sc.icon name="loader" class="h-4 w-4 animate-spin" />
                        </span>
                        <p class="leading-relaxed">Generating a structured analysis. This may take a moment while the AI provider responds.</p>
                    </div>
                    <p class="mt-4 text-[11.5px] leading-relaxed text-gray-500">You can cancel safely. The current attempt will be recorded as canceled and will not become a reviewable analysis.</p>
                </div>

                <div x-show="cancelConfirm" x-cloak>
                    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-[12px] text-amber-950">
                        <x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
                        <div>
                            <p class="font-extrabold">Cancel this generation?</p>
                            <p class="mt-1 leading-relaxed">The current attempt will be marked canceled and its result will not be published.</p>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                <div x-show="!cancelConfirm" x-cloak class="flex justify-end">
                    <button type="button" @click="cancelConfirm = true" class="btn btn-outline min-w-[148px]">Cancel generation</button>
                </div>
                <div x-show="cancelConfirm" x-cloak class="flex flex-wrap justify-end gap-2">
                    <button type="button" @click="cancelConfirm = false" class="btn btn-ghost">Keep generating</button>
                    <button type="button" @click="canceled = true; $wire.cancelGeneration()" class="btn btn-danger-soft min-w-[132px]">Yes, cancel</button>
                </div>
            </footer>
        </section>
    </div>

    {{-- ===================== GENERATION RESULT ===================== --}}
    @if ($generationResult)
        @php($resultIsSuccess = $generationResult === 'success')
        @php($resultIsCanceled = $generationResult === 'canceled')
        <div class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="ai-generation-result-title">
            <div class="fixed inset-0 sc-modal-backdrop" wire:click="closeGenerationResult"></div>
            <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <header class="shrink-0 border-b border-gray-100 px-5 py-5 sm:px-6 {{ $resultIsSuccess ? 'bg-emerald-50' : ($resultIsCanceled ? 'bg-amber-50' : 'bg-red-50') }}">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $resultIsSuccess ? 'bg-emerald-100 text-emerald-700' : ($resultIsCanceled ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                            @if ($resultIsSuccess)
                                <x-sc.icon name="check" class="h-5 w-5" />
                            @elseif ($resultIsCanceled)
                                <x-sc.icon name="x" class="h-5 w-5" />
                            @else
                                <x-sc.icon name="alert" class="h-5 w-5" />
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] {{ $resultIsSuccess ? 'text-emerald-700/70' : ($resultIsCanceled ? 'text-amber-700/70' : 'text-red-700/70') }}">Assessment intelligence</p>
                            <h3 id="ai-generation-result-title" class="mt-1 text-[18px] font-extrabold tracking-tight {{ $resultIsSuccess ? 'text-emerald-950' : ($resultIsCanceled ? 'text-amber-950' : 'text-red-950') }}">
                                {{ $resultIsSuccess ? 'Analysis generated' : ($resultIsCanceled ? 'Generation canceled' : 'Analysis unavailable') }}
                            </h3>
                        </div>
                    </div>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <p class="text-[12.5px] leading-relaxed text-gray-600">{{ $generationResultMessage }}</p>
                </div>
                <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" wire:click="closeGenerationResult" class="btn btn-ghost">Close</button>
                        @if ($resultIsSuccess && $generationResultAnalysisId)
                            <a href="{{ route('ai-analysis.show', ['analysis' => $generationResultAnalysisId]) }}" class="btn btn-primary">Review analysis</a>
                        @endif
                    </div>
                </footer>
            </section>
        </div>
    @endif
</div>
