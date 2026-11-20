{{--
    AI Analysis INDEX = the community list (2026-11-20 redesign).

    One row per community, one page of communities. The work happens on the
    community dossier. Rows are ordered attention-first (draft, generating,
    failed, needs analysis, approved, no data), then alphabetically.

    No page heading: the sidebar already names the page (the same call the
    Faculty Management board made).
--}}
@php($stateMeta = [
    'draft' => ['badge-yellow', 'draft awaiting review'],
    'pending' => ['badge-gray', 'generating'],
    'failed' => ['badge-red', 'failed'],
    'needs' => ['badge-blue', 'needs analysis'],
    'approved' => ['badge-green', 'approved'],
    'no_data' => ['badge-gray', 'no validated data'],
])

<div>
    <section class="reveal-item ai-analysis-queue sc-card p-0 overflow-hidden">
        {{-- ===================== CONTROLS ===================== --}}
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="sc-search min-w-[220px] flex-1 max-w-sm">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="input"
                           placeholder="Search a barangay or school&hellip;"
                           aria-label="Search by community">
                    @if ($search !== '')
                        <button type="button" wire:click="clearSearch"
                                class="sc-search__clear"
                                aria-label="Clear search">
                            <x-sc.icon name="x" class="w-4 h-4" />
                        </button>
                    @endif
                </div>

                <span class="ai-queue-summary ml-auto whitespace-nowrap">
                    @if ($state === 'attention')
                        {{ $communityCount }} {{ \Illuminate\Support\Str::plural('community', $communityCount) }}
                        {{ $communityCount === 1 ? 'needs' : 'need' }} attention
                    @else
                        {{ $communityCount }} {{ \Illuminate\Support\Str::plural('community', $communityCount) }} &middot;
                        <span class="font-bold text-amber-700">{{ $attentionCount }}</span>
                        need{{ $attentionCount === 1 ? 's' : '' }} attention
                    @endif
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @foreach ($filters as $key => $label)
                    <button type="button" wire:click="filterBy('{{ $key }}')"
                            @class(['chip', 'on' => $state === $key])>
                        {{ $label }} <span class="ml-1 font-mono font-bold">{{ $counts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ===================== COMMUNITIES ===================== --}}
        <div class="divide-y divide-gray-50">
            @forelse ($communities as $community)
                @php($meta = $stateMeta[$community['state']] ?? ['badge-gray', $community['state']])
                <a href="{{ route('ai-analysis.community', ['community' => $community['id']]) }}"
                   wire:key="ai-community-{{ $community['id'] }}"
                   class="group flex flex-wrap items-center gap-3 px-5 py-4 transition hover:bg-gray-50/70">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-[13.5px] font-extrabold tracking-tight text-charcoal group-hover:text-lnu-800">
                                {{ $community['name'] }}
                            </p>
                            <span class="badge badge-gray !text-[10px]">{{ $community['type'] }}</span>
                        </div>
                        <p class="mt-0.5 text-[11px] font-medium text-gray-400">
                            {{ $community['location'] ?: 'Location not set' }}
                            @if ($community['latest'])
                                &middot; latest {{ $community['latest'] }}
                            @endif
                        </p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                            @if ($community['draft'] > 0)
                                <span class="badge badge-yellow !text-[10px]">{{ $community['draft'] }} draft</span>
                            @endif
                            @if ($community['needs'] > 0)
                                <span class="badge badge-blue !text-[10px]">{{ $community['needs'] }} need{{ $community['needs'] === 1 ? 's' : '' }} analysis</span>
                            @endif
                            @if ($community['failed'] > 0)
                                <span class="badge badge-red !text-[10px]">{{ $community['failed'] }} failed</span>
                            @endif
                            @if ($community['pending'] > 0)
                                <span class="badge badge-gray !text-[10px]">{{ $community['pending'] }} generating</span>
                            @endif
                            @if ($community['approved'] > 0)
                                <span class="badge badge-green !text-[10px]">{{ $community['approved'] }} approved</span>
                            @endif
                            @if ($community['draft'] + $community['needs'] + $community['failed'] + $community['pending'] + $community['approved'] === 0)
                                <span class="text-[10.5px] font-semibold text-gray-300">No validated summaries yet</span>
                            @endif
                        </div>

                        @if ($community['failure'])
                            <p class="mt-1 text-[10.5px] font-semibold leading-relaxed text-red-600">{{ $community['failure'] }}</p>
                        @endif
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-[12.5px] font-extrabold text-charcoal">
                            {{ number_format($community['validated']) }}
                            <span class="text-[10.5px] font-semibold text-gray-400">validated</span>
                        </p>
                        <p class="mt-0.5 text-[10.5px] font-medium text-gray-400">
                            {{ $community['periods'] }} {{ \Illuminate\Support\Str::plural('period', $community['periods']) }}
                        </p>
                    </div>

                    <span class="badge {{ $meta[0] }} shrink-0">{{ $meta[1] }}</span>

                    <x-sc.icon name="chevron-right" class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-lnu-800" />
                </a>
            @empty
                <div class="p-9 text-center">
                    <span class="mx-auto flex h-[52px] w-[52px] items-center justify-center rounded-2xl bg-gold-50 text-gold-500">
                        <x-sc.icon name="sparkles" class="h-6 w-6" />
                    </span>
                    @if ($search !== '')
                        <h2 class="ai-empty-title mt-4">No community matches &ldquo;{{ $search }}&rdquo;</h2>
                        <p class="ai-empty-note mt-1.5 max-w-md mx-auto">
                            No community in the list has a name or location containing that.
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
                    @elseif ($state === 'attention')
                        <h2 class="ai-empty-title mt-4">Nothing needs attention</h2>
                        <p class="ai-empty-note mt-1.5 max-w-md mx-auto">
                            Every community with validated data is approved or already has a draft waiting.
                            Choose <b>All</b> to include communities with no validated data yet.
                        </p>
                        <button type="button" wire:click="filterBy('')" class="btn btn-outline mt-5">Show all communities</button>
                    @elseif ($state !== '')
                        <h2 class="ai-empty-title mt-4">Nothing in this state</h2>
                        <p class="ai-empty-note mt-1.5 max-w-md mx-auto">
                            No community matches <b>{{ $filters[$state] ?? $state }}</b>. Choose <b>All</b> to see the whole list.
                        </p>
                        <button type="button" wire:click="filterBy('')" class="btn btn-outline mt-5">Show all states</button>
                    @else
                        <h2 class="ai-empty-title mt-4">No communities yet</h2>
                        <p class="ai-empty-note mt-1.5 max-w-md mx-auto">
                            Add a barangay or school first; its validated needs assessments will appear here.
                        </p>
                        <a href="{{ route('communities.index') }}" class="btn btn-primary mt-5">Manage communities</a>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- ===================== PAGER ===================== --}}
        @if ($communities->hasPages())
            <div class="px-5 py-3.5 border-t border-gray-100">
                @include('livewire.partials.pagination', ['paginator' => $communities])
            </div>
        @endif
    </section>

    <p class="ai-pipeline-note reveal-item mt-4 max-w-4xl">
        Each row is one community. The list opens on <b>Needs attention</b>; choose <b>All</b> to include
        communities with no validated data yet. Open a community to inspect the validated response data and,
        if you choose, generate an analysis. A generated draft is saved automatically; only an approved
        analysis is citable in reports.
    </p>
</div>
