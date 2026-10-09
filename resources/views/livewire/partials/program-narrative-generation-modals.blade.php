{{--
    Shared generation flow for the Project Narratives page and Project Hub.
    The action target is passed by the parent because the two entry points use
    different Livewire method names (`generate` and `generateNarrative`).
--}}
<div wire:loading.flex wire:target="{{ $actionTarget }}"
     x-data="{
         cancelConfirm: false,
         canceled: false,
         generationProject: @js($generationProject ?? []),
     }"
     x-on:project-narrative-generate.window="generationProject = $event.detail; canceled = false; cancelConfirm = false"
     x-show="!canceled" x-cloak
     class="fixed inset-0 z-[70] items-center justify-center p-3 sm:p-6 no-print"
     role="dialog" aria-modal="true" aria-labelledby="project-narrative-generation-title">
    <div class="fixed inset-0 sc-modal-backdrop"></div>
    <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-white/10 bg-gradient-to-br from-lnu-900 via-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                    <x-sc.icon name="sparkles" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project narrative generation</p>
                    <h3 id="project-narrative-generation-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Generating extension project narrative</h3>
                    <p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">Building a clear status narrative from this project’s validated aggregate data.</p>
                </div>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Extension project</p>
                    <p class="mt-1 text-[12.5px] font-extrabold leading-snug text-charcoal" x-text="generationProject.title || 'Extension project'"></p>
                    <p class="mt-0.5 font-mono text-[10.5px] text-gray-400" x-text="generationProject.code || 'Project code unavailable'"></p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">College / unit</p>
                    <p class="mt-1 text-[12.5px] font-semibold leading-snug text-charcoal" x-text="generationProject.college || 'College not linked'"></p>
                    <p class="mt-0.5 text-[10.5px] text-gray-400">Lead: <span x-text="generationProject.lead || 'Not assigned'"></span></p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-3 sm:col-span-2">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Communities / partner schools</p>
                    <p class="mt-1 text-[12px] font-semibold leading-relaxed text-charcoal" x-text="(generationProject.communities || []).join(', ') || 'No community or partner school linked'"></p>
                </div>
            </div>

            <div x-show="!cancelConfirm" x-cloak>
                <div class="mt-4 flex items-center gap-3 rounded-xl border border-lnu-100 bg-lnu-50/70 px-4 py-3.5 text-[12px] text-lnu-900">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-lnu-700 shadow-sm ring-1 ring-lnu-100">
                        <x-sc.icon name="loader" class="h-4 w-4 animate-spin" />
                    </span>
                    <p class="leading-relaxed">Summarizing training hours, trainees, activities, budget and annual targets. Aggregates only — no PII is sent to the AI provider.</p>
                </div>
                <p class="mt-4 text-[11.5px] leading-relaxed text-gray-500">You can cancel safely. The current attempt will be recorded as canceled and will not become the project’s readable narrative.</p>
            </div>

            <div x-show="cancelConfirm" x-cloak class="mt-4">
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-[12px] text-amber-950">
                    <x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
                    <div>
                        <p class="font-extrabold">Cancel this generation?</p>
                        <p class="mt-1 leading-relaxed">The current attempt will be marked canceled and its narrative will not be published.</p>
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

@if ($generationResult)
    @php($resultIsSuccess = $generationResult === 'success')
    @php($resultIsCanceled = $generationResult === 'canceled')
    <div class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="project-narrative-generation-result-title">
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
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] {{ $resultIsSuccess ? 'text-emerald-700/70' : ($resultIsCanceled ? 'text-amber-700/70' : 'text-red-700/70') }}">Project narrative generation</p>
                        <h3 id="project-narrative-generation-result-title" class="mt-1 text-[18px] font-extrabold tracking-tight {{ $resultIsSuccess ? 'text-emerald-950' : ($resultIsCanceled ? 'text-amber-950' : 'text-red-950') }}">
                            {{ $resultIsSuccess ? 'Project narrative generated' : ($resultIsCanceled ? 'Generation canceled' : 'Narrative unavailable') }}
                        </h3>
                        @if (! empty($generationProject['title']))
                            <p class="mt-1 text-[11.5px] font-medium {{ $resultIsSuccess ? 'text-emerald-800/75' : ($resultIsCanceled ? 'text-amber-800/75' : 'text-red-800/75') }}">{{ $generationProject['title'] }} · {{ $generationProject['code'] ?? '—' }}</p>
                        @endif
                    </div>
                </div>
            </header>
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <p class="text-[12.5px] leading-relaxed text-gray-600">{{ $generationResultMessage }}</p>
            </div>
            <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="closeGenerationResult" class="btn btn-ghost">Close</button>
                    @if ($resultIsSuccess && in_array($actionTarget, ['generate', 'generateNarrative'], true))
                        <button type="button" wire:click="viewGeneratedNarrative" class="btn btn-primary">View Narrative</button>
                    @endif
                </div>
            </footer>
        </section>
    </div>
@endif
