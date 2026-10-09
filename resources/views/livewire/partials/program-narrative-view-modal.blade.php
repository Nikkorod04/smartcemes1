@if ($viewingNarrative)
    @php($narrative = $viewingNarrative)
    @php($project = $narrative->program)
    @php($healthText = match ($narrative->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' })

    <div x-data @keydown.escape.window="$wire.closeNarrativeModal()"
         class="fixed inset-0 z-[90] flex items-center justify-center p-3 sm:p-6 no-print"
         role="dialog" aria-modal="true" aria-labelledby="project-narrative-view-title">
        <div class="fixed inset-0 sc-modal-backdrop" wire:click="closeNarrativeModal"></div>
        <section class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
            <header class="relative shrink-0 bg-lnu-800 px-5 py-5 text-white sm:px-6"
                    style="background-image:radial-gradient(circle at 90% -40%, rgba(246,184,0,.35), transparent 46%), radial-gradient(rgba(255,255,255,.12) 1px, transparent 1.4px); background-size:auto, 16px 16px;">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10">
                            <x-sc.icon name="sparkles" class="h-5 w-5 text-gold-300" />
                        </span>
                        <div class="min-w-0">
                            <span class="ai-chip !bg-white/10 !text-blue-100 !border-white/20"><x-sc.icon name="sparkles" class="h-3 w-3" />Project Narrative</span>
                            <h3 id="project-narrative-view-title" class="mt-2 text-[18px] font-extrabold leading-snug tracking-tight">{{ $project?->title ?? 'Extension project narrative' }}</h3>
                            <p class="mt-1 truncate text-[11.5px] font-medium text-blue-100/75">{{ $project?->code ?? '—' }} · {{ $project?->communities?->pluck('name')->implode(', ') ?: 'No community linked' }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeNarrativeModal" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-blue-100/70 transition hover:bg-white/10 hover:text-white" aria-label="Close project narrative">
                        <x-sc.icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50/60 p-4 sm:p-5">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="narrative-health {{ $narrative->health_label ?? 'needs-attention' }}">{{ $healthText }}</span>
                    <span class="text-[11px] text-gray-400">Generated {{ $narrative->generated_at?->format('M j, Y · g:i A') }} · {{ $narrative->generator?->name ?? 'Director, CESO' }}</span>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <section class="rounded-xl border border-gray-100 bg-white p-4">
                        <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400"><x-sc.icon name="doc" class="h-3.5 w-3.5" />Summary</p>
                        <p class="mt-2.5 text-[12.5px] leading-relaxed text-gray-600">{{ $narrative->summary ?: 'No summary was generated.' }}</p>
                    </section>

                    <section class="rounded-xl border border-gray-100 bg-white p-4">
                        <p class="mb-2 flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400"><span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>Top risks</p>
                        <ul class="space-y-1.5 text-[12.5px] text-gray-600">
                            @forelse ($narrative->risks ?? [] as $risk)
                                <li class="flex items-start gap-1.5"><span class="mt-1 text-[8px] text-red-500">●</span><span class="leading-snug">{{ is_array($risk) ? ($risk['risk'] ?? $risk['text'] ?? '') : $risk }}</span></li>
                            @empty
                                <li class="italic text-gray-400">No material risks identified.</li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="rounded-xl border border-gray-100 bg-white p-4 lg:col-span-2">
                        <p class="mb-2 flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400"><span class="h-1.5 w-1.5 rounded-sm bg-lnu-400"></span>Recommended next actions</p>
                        <ul class="grid gap-2 text-[12.5px] text-gray-600 sm:grid-cols-2">
                            @forelse ($narrative->recommendations ?? [] as $recommendation)
                                @php($priority = $recommendation['priority'] ?? '')
                                @php($priorityTone = $priority === 'High' ? 'badge-red' : ($priority === 'Medium' ? 'badge-gold' : 'badge-gray'))
                                <li class="flex items-start gap-1.5">
                                    <span class="mt-0.5 text-[10px] text-lnu-700">▸</span>
                                    <span class="min-w-0 leading-snug">
                                        <span class="flex flex-wrap items-start gap-1.5">
                                            @if ($priority !== '')<span class="badge {{ $priorityTone }} !text-[10px]">{{ $priority }}</span>@endif
                                            <span class="font-semibold">{{ $recommendation['action'] ?? '' }}</span>
                                        </span>
                                        @if (! empty($recommendation['rationale']))<span class="mt-0.5 block text-[11px] leading-snug text-gray-400">{{ $recommendation['rationale'] }}</span>@endif
                                    </span>
                                </li>
                            @empty
                                <li class="italic text-gray-400">No pending actions identified.</li>
                            @endforelse
                        </ul>
                    </section>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-3.5">
                    <p class="text-[11px] text-gray-400">Aggregate project data only · no PII sent to the AI provider</p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="badge badge-gray !text-[10px] font-mono">{{ $narrative->metadata['model'] ?? 'gemini' }}</span>
                        <span class="badge badge-gray !text-[10px]">prompt {{ $narrative->metadata['prompt_version'] ?? '—' }}</span>
                        <span class="badge badge-gray !text-[10px]">confidence {{ $narrative->confidence_score !== null ? number_format((float) $narrative->confidence_score, 2) : '—' }}</span>
                    </div>
                </div>
            </div>

            <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                <div class="flex justify-end">
                    <button type="button" wire:click="closeNarrativeModal" class="btn btn-ghost">Close</button>
                </div>
            </footer>
        </section>
    </div>
@endif
