{{--
    ONE community's AI analysis history (2026-10-07).

    The queue groups by community but PAGINATES BY GROUP, so a community's rows can
    sit on any page and there is no way to link to one community. This page is that
    link, and it is where housekeeping lives: deleting a past generation, or clearing
    the failed attempts in one go.

    Periods come first because that is the natural axis: one summary per
    community + quarter + year, each with its own generation lineage.
--}}
@php($stateMeta = [
    'awaiting_review' => ['badge-yellow', 'awaiting review'],
    'approved' => ['badge-green', 'approved'],
    'discarded' => ['badge-gray', 'discarded'],
    'failed' => ['badge-red', 'failed'],
    'pending' => ['badge-gray', 'generating'],
])

<div>
    {{-- ===================== HEADER ===================== --}}
    <section class="pt-6 reveal-item">
        <a href="{{ route('ai-analysis.index') }}" class="btn btn-outline !px-3 !py-2 text-[12px]">
            <x-sc.icon name="chevron" class="w-3.5 h-3.5 rotate-90" /><span class="ml-1">Analysis queue</span>
        </a>

        <div class="mt-4 flex items-start justify-between gap-4 flex-wrap">
            <div class="min-w-0">
                <h1 class="font-extrabold text-[18px] tracking-tight flex items-center gap-2">
                    <x-sc.icon name="pin" class="w-5 h-5 text-lnu-700" /> {{ $community->name }}
                </h1>
                <p class="mt-1 text-[12.5px] text-gray-500 leading-relaxed">
                    {{ $community->isSchool() ? 'School' : 'Barangay' }} ·
                    {{ $periods->count() }} validated {{ \Illuminate\Support\Str::plural('period', $periods->count()) }} ·
                    {{ $totalCount }} {{ \Illuminate\Support\Str::plural('generation', $totalCount) }} on record
                </p>
            </div>

            @if ($failedCount > 0)
                {{-- The usual cleanup: 9 of the 12 live generations are failed noise. --}}
                <button wire:click="clearFailed" wire:loading.attr="disabled" wire:target="clearFailed"
                        class="btn btn-danger-soft shrink-0">
                    <span wire:loading.remove wire:target="clearFailed" class="inline-flex items-center gap-1.5">
                        <x-sc.icon name="trash" class="w-4 h-4" />Clear {{ $failedCount }} failed
                    </span>
                    <span wire:loading wire:target="clearFailed" class="inline-flex items-center gap-1.5">
                        <x-sc.icon name="loader" class="w-4 h-4 animate-spin" />Clearing…
                    </span>
                </button>
            @endif
        </div>
    </section>

    {{-- ===================== PERIODS ===================== --}}
    @forelse ($periods as $summary)
        @php($gens = $bySummary->get($summary->id, collect()))
        <section class="mt-5 reveal-item">
            <div class="flex items-center justify-between gap-3 mb-2.5">
                <h2 class="text-[13px] font-extrabold tracking-tight">Q{{ $summary->quarter }} {{ $summary->year }}</h2>
                <span class="text-[11px] text-gray-400 font-semibold">
                    {{ $summary->total_responses }} validated responses ·
                    {{ $gens->count() }} {{ \Illuminate\Support\Str::plural('generation', $gens->count()) }}
                </span>
            </div>

            <div class="sc-card p-0 overflow-hidden divide-y divide-gray-50">
                @forelse ($gens as $a)
                    @php($meta = $stateMeta[$a->queueState()] ?? ['badge-gray', $a->queueState()])
                    <div class="flex items-center gap-3 flex-wrap px-5 py-3.5 transition hover:bg-gray-50/60"
                         wire:key="ai-comm-{{ $a->id }}">
                        <span class="badge {{ $meta[0] }}">
                            @if (in_array($a->queueState(), ['awaiting_review', 'pending'], true))
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 pulse-dot"></span>
                            @endif
                            {{ $meta[1] }}
                        </span>

                        @if ($a->generationCount() > 1)
                            <span class="text-[11.5px] text-gray-400 font-semibold">gen {{ $a->generationIndex() }} of {{ $a->generationCount() }}</span>
                            @if ($a->isCurrent())
                                <span class="badge badge-green">current</span>
                            @elseif ($a->isSuperseded())
                                <span class="badge badge-gray">superseded</span>
                            @endif
                        @endif

                        @if ($a->queueState() === 'failed')
                            <span class="text-[11.5px] text-red-600 font-semibold" title="{{ $a->error_message }}">{{ $a->failureSummary() }}</span>
                        @elseif ($a->approver)
                            <span class="text-[11.5px] text-gray-400">Approved by {{ $a->approver->name }}</span>
                        @endif

                        <span class="ml-auto flex items-center gap-3 shrink-0">
                            <span class="text-[11.5px] text-gray-400 whitespace-nowrap hidden md:inline">{{ $a->created_at->format('M j, Y g:i A') }}</span>
                            <span class="font-mono text-[11px] text-gray-400 hidden xl:inline">{{ $a->metadata['model'] ?? '—' }}</span>

                            @if ($a->queueState() === 'failed')
                                <button wire:click="retry({{ $a->id }})" wire:target="retry({{ $a->id }})" wire:loading.attr="disabled"
                                        class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">⟳ Retry</button>
                            @else
                                <a href="{{ route('ai-analysis.show', ['analysis' => $a]) }}" class="text-[12px] font-bold text-lnu-800 whitespace-nowrap">
                                    {{ $a->queueState() === 'awaiting_review' ? 'Review →' : 'View →' }}
                                </a>
                            @endif

                            {{-- Deleting is scoped: an approved analysis is citable, and the
                                 current draft is the queue's only actionable row. Say WHY
                                 rather than showing a disabled button with no reason. --}}
                            @if ($a->isDeletable())
                                <button wire:click="confirmDelete({{ $a->id }})"
                                        class="btn btn-danger-soft !px-2.5 !py-1.5 text-[11px]">Delete</button>
                            @elseif ($a->approval_status === \App\Models\AssessmentAnalysis::APPROVAL_APPROVED)
                                <span class="text-[11px] text-gray-400 whitespace-nowrap" title="An approved analysis is citable in reports and cannot be deleted.">citable — locked</span>
                            @else
                                <span class="text-[11px] text-gray-400 whitespace-nowrap" title="This is the live draft. Discard it first, then delete.">live draft — locked</span>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="flex items-center gap-3 flex-wrap px-5 py-3.5">
                        <span class="badge badge-blue">awaiting analysis</span>
                        <span class="text-[11.5px] text-gray-400">No analysis has been generated for this period yet.</span>
                        <span class="ml-auto shrink-0">
                            <button wire:click="generate({{ $summary->id }})"
                                    wire:target="generate({{ $summary->id }})" wire:loading.attr="disabled"
                                    class="btn btn-primary !px-3 !py-1.5 text-[11.5px]">
                                <span wire:loading.remove wire:target="generate({{ $summary->id }})" class="inline-flex items-center gap-1.5"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" />Generate</span>
                                <span wire:loading wire:target="generate({{ $summary->id }})" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5 animate-spin" />Generating…</span>
                            </button>
                        </span>
                    </div>
                @endforelse
            </div>
        </section>
    @empty
        <section class="mt-6 reveal-item">
            <div class="sc-card p-9 text-center">
                <span class="w-[52px] h-[52px] rounded-2xl bg-gold-50 text-gold-500 mx-auto flex items-center justify-center"><x-sc.icon name="sparkles" class="w-6 h-6" /></span>
                <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">No validated summaries yet</h2>
                <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">
                    {{ $community->name }} has no validated needs-assessment summary, so there is nothing to analyse.
                </p>
            </div>
        </section>
    @endforelse

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
         flag cannot fail the way a $wire.$watch bridge can (handoff §14). --}}
    @if ($deletingId !== null)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="delete-generation-title">
            <div class="fixed inset-0 sc-modal-backdrop" wire:click="cancelDelete"></div>
            <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="trash" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">AI analysis history</p><h3 id="delete-generation-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Delete this generation?</h3><p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">Review the permanent deletion before continuing.</p></div></div><button type="button" wire:click="cancelDelete" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close delete generation dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"><div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-[11px] leading-relaxed text-red-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-red-700" /><p>The record for <b>{{ $community->name }}</b> will be permanently removed. The deletion is written to the audit log, but the analysis itself cannot be recovered.</p></div></div>
                <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" wire:click="cancelDelete" class="btn btn-ghost">Keep it</button><button type="button" wire:click="delete({{ $deletingId }})" wire:loading.attr="disabled" wire:target="delete" class="btn btn-danger-soft min-w-[140px]"><span wire:loading.remove wire:target="delete">Delete generation</span><span wire:loading wire:target="delete" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Deleting…</span></button></div></footer>
            </section>
        </div>
    @endif
</div>
