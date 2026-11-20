{{--
    ONE community's analysis dossier (2026-11-20 redesign).

    The period selector is first-class because an analysis is keyed to a SUMMARY
    (community + quarter + year). The page shows the validated response data that
    the model would receive (D3) BEFORE the Director chooses to generate, then
    hands the long output to the review page for Approve / Discard.

    Response-data panel is shared with the review page
    (`livewire.partials.ai-community-data`), so there is one definition.
--}}
@php($stateMeta = [
    'draft' => ['badge-yellow', 'draft awaiting review'],
    'pending' => ['badge-gray', 'generating'],
    'failed' => ['badge-red', 'failed'],
    'needs' => ['badge-blue', 'needs analysis'],
    'approved' => ['badge-green', 'approved'],
    'no_data' => ['badge-gray', 'no validated data'],
])

<div class="ai-community-dossier">
    {{-- ===================== HEADER ===================== --}}
    <section class="pt-6 reveal-item">
        <a href="{{ route('ai-analysis.index') }}"
           class="inline-flex items-center gap-1 text-[11.5px] font-bold text-lnu-700 transition hover:text-lnu-900">
            &larr; All communities
        </a>

        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-[20px] font-extrabold tracking-tight text-charcoal">{{ $community->name }}</h1>
                    <span class="badge badge-gray">{{ $community->isSchool() ? 'School' : 'Barangay' }}</span>
                </div>
                <p class="mt-1 text-[12px] font-medium text-gray-500">
                    {{ trim(implode(', ', array_filter([$community->municipality, $community->province]))) ?: 'Location not set' }}
                    &middot; {{ $periods->count() }} {{ \Illuminate\Support\Str::plural('period', $periods->count()) }}
                    &middot; {{ $totalCount }} {{ \Illuminate\Support\Str::plural('generation', $totalCount) }} on record
                </p>
            </div>

            @if ($failedCount > 0)
                <button wire:click="clearFailed" wire:loading.attr="disabled" wire:target="clearFailed"
                        class="btn btn-danger-soft shrink-0">
                    <span wire:loading.remove wire:target="clearFailed" class="inline-flex items-center gap-1.5">
                        <x-sc.icon name="trash" class="w-4 h-4" />Clear {{ $failedCount }} failed
                    </span>
                    <span wire:loading wire:target="clearFailed" class="inline-flex items-center gap-1.5">
                        <x-sc.icon name="loader" class="w-4 h-4 animate-spin" />Clearing&hellip;
                    </span>
                </button>
            @endif
        </div>
    </section>

    @if ($periods->isEmpty())
        <section class="mt-5 reveal-item">
            <div class="sc-card p-9 text-center">
                <span class="mx-auto flex h-[52px] w-[52px] items-center justify-center rounded-2xl bg-gold-50 text-gold-500">
                    <x-sc.icon name="sparkles" class="h-6 w-6" />
                </span>
                <h2 class="mt-4 text-[16px] font-extrabold tracking-tight">No validated summaries yet</h2>
                <p class="mx-auto mt-1.5 max-w-md text-[12.5px] leading-relaxed text-gray-500">
                    {{ $community->name }} has no validated needs-assessment summary, so there is nothing to analyse.
                    Validate submissions first, then come back.
                </p>
                <a href="{{ route('communities.assessments', ['community' => $community]) }}" class="btn btn-outline mt-5">
                    Review assessments
                </a>
            </div>
        </section>
    @else
        {{-- ===================== PERIOD SELECTOR ===================== --}}
        <section class="mt-4 reveal-item">
            <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Assessment periods">
                @foreach ($periods as $period)
                    @php($periodState = $periodStates[$period->id] ?? 'no_data')
                    @php($periodMeta = $stateMeta[$periodState] ?? ['badge-gray', $periodState])
                    <button type="button" role="tab"
                            wire:click="selectPeriod({{ $period->id }})"
                            wire:key="period-{{ $period->id }}"
                            aria-selected="{{ $selected?->id === $period->id ? 'true' : 'false' }}"
                            @class([
                                'flex items-center gap-2 rounded-xl border px-3.5 py-2 text-left transition',
                                'border-lnu-800 bg-lnu-800 text-white shadow-sm' => $selected?->id === $period->id,
                                'border-gray-200 bg-white text-charcoal hover:border-lnu-300' => $selected?->id !== $period->id,
                            ])>
                        <span class="text-[12.5px] font-extrabold">Q{{ $period->quarter }} {{ $period->year }}</span>
                        <span @class([
                            'badge !text-[9.5px]',
                            $periodMeta[0],
                            'ring-1 ring-white/30' => $selected?->id === $period->id,
                        ])>{{ $periodMeta[1] }}</span>
                        <span @class([
                            'text-[10.5px] font-semibold',
                            'text-white/70' => $selected?->id === $period->id,
                            'text-gray-400' => $selected?->id !== $period->id,
                        ])>{{ $period->total_responses }} resp.</span>
                    </button>
                @endforeach
            </div>
        </section>

        @if ($selected)
            <div class="mt-4 grid items-start gap-5 xl:grid-cols-12">
                {{-- ===================== RESPONSE DATA ===================== --}}
                <section class="min-w-0 xl:col-span-8">
                    <div class="sc-card p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Selected period</p>
                                <h2 class="mt-0.5 text-[16px] font-extrabold tracking-tight">
                                    Q{{ $selected->quarter }} {{ $selected->year }}
                                </h2>
                            </div>
                            @php($selectedMeta = $stateMeta[$selectedState] ?? ['badge-gray', $selectedState])
                            <span class="badge {{ $selectedMeta[0] }}">{{ $selectedMeta[1] }}</span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <dt class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Validated</dt>
                                <dd class="mt-0.5 text-[16px] font-extrabold text-charcoal">{{ number_format($selected->total_responses) }}</dd>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <dt class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Sources</dt>
                                <dd class="mt-0.5 flex flex-wrap gap-1">
                                    @forelse ($sourceCounts as $source => $count)
                                        <span class="badge badge-gray !text-[9.5px]">{{ \App\Models\NeedsAssessment::sourceLabel($source) }} {{ $count }}</span>
                                    @empty
                                        <span class="text-[12px] font-semibold text-gray-400">&mdash;</span>
                                    @endforelse
                                </dd>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <dt class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Generations</dt>
                                <dd class="mt-0.5 text-[16px] font-extrabold text-charcoal">{{ $analyses->count() }}</dd>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5">
                                <dt class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Excluded</dt>
                                <dd class="mt-0.5 text-[16px] font-extrabold {{ $unvalidatedCount > 0 ? 'text-amber-600' : 'text-charcoal' }}">{{ $unvalidatedCount }}</dd>
                            </div>
                        </dl>

                        @if ($unvalidatedCount > 0)
                            <p class="mt-3 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-[11px] leading-relaxed text-amber-900">
                                {{ $unvalidatedCount }} {{ \Illuminate\Support\Str::plural('submission', $unvalidatedCount) }}
                                for this period {{ $unvalidatedCount === 1 ? 'is' : 'are' }} not validated and therefore excluded from the figures.
                                <a href="{{ route('communities.assessments', ['community' => $community]) }}" class="font-bold underline">Review them</a>.
                            </p>
                        @endif

                        @if ((int) $selected->total_responses <= 0)
                            <p class="mt-4 rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-[12px] font-medium text-gray-400">
                                No validated responses for this period yet. Nothing can be generated until at least one submission is validated.
                            </p>
                        @else
                            <div class="mt-4">
                                @include('livewire.partials.ai-community-data', [
                                    'aggRaw' => $aggregates,
                                    'open' => true,
                                    'keySuffix' => 'dossier-'.$selected->id,
                                    'communityName' => $community->name,
                                    'quarter' => $selected->quarter,
                                    'year' => $selected->year,
                                ])
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ===================== ACTION ===================== --}}
                <aside class="space-y-4 xl:col-span-4">
                    <div class="sc-card p-5">
                        <h2 class="text-[15px] font-extrabold tracking-tight">Analysis</h2>

                        @if ($currentDraft)
                            <p class="mt-2 text-[12px] leading-relaxed text-gray-500">
                                A generated draft is waiting for your review. Drafts are saved automatically.
                            </p>
                            <a href="{{ route('ai-analysis.show', ['analysis' => $currentDraft]) }}" class="btn btn-primary mt-3 w-full">
                                <x-sc.icon name="eye" class="h-4 w-4" /> Review draft
                            </a>
                            <p class="mt-2 text-[10.5px] leading-relaxed text-gray-400">
                                Approve it there to make it citable, discard it, or regenerate a fresh draft.
                            </p>
                        @elseif ($selectedState === 'approved' && $approvedAnalysis)
                            <p class="mt-2 text-[12px] leading-relaxed text-gray-500">
                                An approved analysis is citable in reports. Regenerating from the review page creates a new draft and keeps this one intact.
                            </p>
                            <a href="{{ route('ai-analysis.show', ['analysis' => $approvedAnalysis]) }}" class="btn btn-primary mt-3 w-full">
                                <x-sc.icon name="shield" class="h-4 w-4" /> View approved analysis
                            </a>
                        @elseif ($selectedState === 'failed' && $latestFailed)
                            <p class="mt-2 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-[11.5px] font-semibold leading-relaxed text-red-700">
                                {{ $latestFailed->failureSummary() }}
                            </p>
                            <button wire:click="retry({{ $latestFailed->id }})"
                                    wire:target="retry({{ $latestFailed->id }})" wire:loading.attr="disabled"
                                    class="btn btn-primary mt-3 w-full">
                                <span wire:loading.remove wire:target="retry({{ $latestFailed->id }})" class="inline-flex items-center gap-1.5">
                                    <x-sc.icon name="loader" class="h-4 w-4" /> Retry failed attempt
                                </span>
                                <span wire:loading wire:target="retry({{ $latestFailed->id }})" class="inline-flex items-center gap-1.5">
                                    <x-sc.icon name="loader" class="h-4 w-4 animate-spin" /> Retrying&hellip;
                                </span>
                            </button>
                            <button wire:click="generate({{ $selected->id }})"
                                    wire:target="generate({{ $selected->id }})" wire:loading.attr="disabled"
                                    class="btn btn-outline mt-2 w-full">
                                Start a new generation
                            </button>
                        @elseif ((int) $selected->total_responses <= 0)
                            <p class="mt-2 text-[12px] leading-relaxed text-gray-500">
                                Generate becomes available once this period has validated responses.
                            </p>
                        @else
                            <p class="mt-2 text-[12px] leading-relaxed text-gray-500">
                                The validated responses are ready. Generate a draft to review; nothing is cited until you approve it.
                            </p>
                            <button wire:click="generate({{ $selected->id }})"
                                    wire:target="generate({{ $selected->id }})" wire:loading.attr="disabled"
                                    class="btn btn-primary mt-3 w-full">
                                <span wire:loading.remove wire:target="generate({{ $selected->id }})" class="inline-flex items-center gap-1.5">
                                    <x-sc.icon name="sparkles" class="h-4 w-4" /> Generate analysis
                                </span>
                                <span wire:loading wire:target="generate({{ $selected->id }})" class="inline-flex items-center gap-1.5">
                                    <x-sc.icon name="loader" class="h-4 w-4 animate-spin" /> Generating&hellip;
                                </span>
                            </button>
                        @endif

                        <p class="mt-3 border-t border-gray-100 pt-3 text-[10.5px] leading-relaxed text-gray-400">
                            Only aggregate counts and percentages go to the model. No individual response leaves the server.
                        </p>
                    </div>

                    {{-- ===================== GENERATIONS ===================== --}}
                    <div class="sc-card p-0 overflow-hidden">
                        <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
                            <h2 class="text-[13px] font-extrabold tracking-tight">Generations</h2>
                            <span class="text-[10.5px] font-semibold text-gray-400">{{ $analyses->count() }}</span>
                        </div>

                        <div class="divide-y divide-gray-50">
                            @forelse ($analyses as $a)
                                @php($meta = $stateMeta[$a->queueState()] ?? ['badge-gray', $a->queueState()])
                                <div class="px-4 py-3" wire:key="dossier-gen-{{ $a->id }}">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge {{ $meta[0] }} !text-[10px]">{{ $meta[1] }}</span>
                                        @if ($a->generationCount() > 1)
                                            <span class="text-[10.5px] font-semibold text-gray-400">gen {{ $a->generationIndex() }} of {{ $a->generationCount() }}</span>
                                            @if ($a->isCurrent())
                                                <span class="badge badge-green !text-[9.5px]">current</span>
                                            @endif
                                        @endif
                                        <span class="ml-auto text-[10px] font-medium text-gray-400">{{ $a->created_at->format('M j, g:i A') }}</span>
                                    </div>

                                    @if ($a->queueState() === 'failed')
                                        <p class="mt-1 text-[10.5px] leading-relaxed text-red-600" title="{{ $a->error_message }}">{{ $a->failureSummary() }}</p>
                                    @elseif ($a->approver)
                                        <p class="mt-1 text-[10.5px] text-gray-400">Approved by {{ $a->approver->name }}</p>
                                    @endif

                                    <div class="mt-2 flex items-center gap-2">
                                        @if ($a->queueState() === 'failed')
                                            <button wire:click="retry({{ $a->id }})"
                                                    wire:target="retry({{ $a->id }})" wire:loading.attr="disabled"
                                                    class="text-[11px] font-bold text-lnu-800 underline">Retry</button>
                                        @else
                                            <a href="{{ route('ai-analysis.show', ['analysis' => $a]) }}"
                                               class="text-[11px] font-bold text-lnu-800 underline">
                                                {{ $a->queueState() === 'awaiting_review' ? 'Review' : 'View' }}
                                            </a>
                                        @endif

                                        @if ($a->isDeletable())
                                            <button wire:click="confirmDelete({{ $a->id }})"
                                                    class="ml-auto text-[11px] font-bold text-red-600 underline">Delete</button>
                                        @elseif ($a->approval_status === \App\Models\AssessmentAnalysis::APPROVAL_APPROVED)
                                            <span class="ml-auto text-[10px] text-gray-400">citable &mdash; locked</span>
                                        @else
                                            <span class="ml-auto text-[10px] text-gray-400">live draft &mdash; locked</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="px-4 py-6 text-center text-[11.5px] font-medium text-gray-400">
                                    No generations for this period yet.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </aside>
            </div>
        @endif

        {{-- ===================== GENERATION LOADING =====================
             Generation is synchronous today, so the cancel action records a shared
             cancellation request and immediately closes this UI. The server then
             prevents the in-flight result from being treated as a successful draft. --}}
        <div wire:loading.flex wire:target="generate"
             x-data="{ cancelConfirm: false, canceled: false }"
             x-show="!canceled" x-cloak
             class="fixed inset-0 z-[70] items-center justify-center p-3 sm:p-6 no-print"
             role="dialog" aria-modal="true" aria-labelledby="ai-community-generation-loading-title">
            <div class="fixed inset-0 sc-modal-backdrop"></div>
            <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <header class="shrink-0 border-b border-white/10 bg-gradient-to-br from-lnu-900 via-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                            <x-sc.icon name="sparkles" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Assessment intelligence</p>
                            <h3 id="ai-community-generation-loading-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Generating AI Analysis</h3>
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
            <div class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="ai-community-generation-result-title">
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
                                <h3 id="ai-community-generation-result-title" class="mt-1 text-[18px] font-extrabold tracking-tight {{ $resultIsSuccess ? 'text-emerald-950' : ($resultIsCanceled ? 'text-amber-950' : 'text-red-950') }}">
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

        {{-- ===================== DELETE CONFIRMATION =====================
             SERVER-rendered @if, not an Alpine visibility gate — a click-driven Livewire
             flag cannot fail the way a $wire.$watch bridge can (handoff A14). --}}
        @if ($deletingId !== null)
            <div class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="delete-generation-title">
                <div class="fixed inset-0 sc-modal-backdrop" wire:click="cancelDelete"></div>
                <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                    <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="trash" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">AI analysis history</p><h3 id="delete-generation-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Delete this generation?</h3><p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">Review the permanent deletion before continuing.</p></div></div><button type="button" wire:click="cancelDelete" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close delete generation dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"><div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-[11px] leading-relaxed text-red-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-red-700" /><p>The record for <b>{{ $community->name }}</b> will be permanently removed. The deletion is written to the audit log, but the analysis itself cannot be recovered.</p></div></div>
                    <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" wire:click="cancelDelete" class="btn btn-ghost">Keep it</button><button type="button" wire:click="delete({{ $deletingId }})" wire:loading.attr="disabled" wire:target="delete" class="btn btn-danger-soft min-w-[140px]"><span wire:loading.remove wire:target="delete">Delete generation</span><span wire:loading wire:target="delete" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Deleting&hellip;</span></button></div></footer>
                </section>
            </div>
        @endif
    @endif
</div>
