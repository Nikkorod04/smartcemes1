<div class="mt-4 grid grid-cols-3 gap-4">
    <div class="sc-card p-6 col-span-2">
        <h3 class="font-bold text-[14px] mb-2 flex items-center gap-2"><x-sc.icon name="folder" class="!w-[18px] !h-[18px] text-lnu-700" /> Project Goal</h3>
        <p class="text-[13.5px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $program->goals ?: ($program->description ?: 'No project goal recorded yet.') }}</p>
        <div class="mt-5 pt-4 border-t border-gray-100">
            <p class="label">Linked Communities & Partners</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($program->communities as $c)
                    <span class="chip on !py-1.5 !text-[12px]">{{ $c->name }} · {{ $c->municipality }}</span>
                @empty
                    <span class="text-[12.5px] text-gray-400 italic">No linked communities.</span>
                @endforelse
                @foreach ($program->partners ?? [] as $partner)
                    <span class="chip !py-1.5 !text-[12px]">{{ $partner }}</span>
                @endforeach
            </div>
        </div>
        <div class="mt-5 pt-4 border-t border-gray-100">
            <p class="label">Beneficiary Categories</p>
            <div class="flex flex-wrap gap-2">
                @forelse ($program->beneficiary_categories ?? [] as $cat)
                    <span class="chip !py-1.5 !text-[12px]">{{ $cat }}</span>
                @empty
                    <span class="text-[12.5px] text-gray-400 italic">None specified.</span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- TARGETS & ALLOCATION (R4, §4.7) — replaces the 8.6 Results Framework
         card. D-R7 removed the KPI dictionary from project level; the Director
         now reads four quantities per project. Training hours are measured
         against the project's annual HOURS target; budget has NO annual target
         (owner decision 2026-09-26), so it is measured against the project's
         allocation. Broad programs carry no target (D-R5) — targets exist at
         University and Project level only, which is stated explicitly below. --}}
    <div class="sc-card p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-[14px] flex items-center gap-2">
                <x-sc.icon name="chart" class="!w-[18px] !h-[18px] text-lnu-700" /> Targets &amp; Allocation
                <span class="badge badge-gray !text-[10px]">target vs actual</span>
            </h3>
            {{-- Admin-only. `targets.index` is admin-only and this card carries no
                 other role guard, so faculty and secretary were shown a link that 403'd. --}}
            @if ($canManage)
                <a href="{{ route('targets.index') }}" class="text-[11.5px] font-bold text-lnu-700 hover:text-lnu-900 transition">University pool →</a>
            @endif
        </div>

        <div class="space-y-3">
            <div>
                <div class="flex items-baseline justify-between gap-2">
                    <p class="text-[12.5px] font-semibold">Training hours rendered</p>
                    <p class="text-[12px] font-bold shrink-0">{{ number_format($performance['actual_hours']) }} <span class="text-gray-400 font-medium">/ {{ $performance['target_hours'] === null ? 'no target' : number_format($performance['target_hours']).' hrs' }}</span></p>
                </div>
                <div class="progress mt-1.5"><span style="width:{{ (int) min($performance['hours_pct'] ?? 0, 100) }}%" class="{{ $attainBg($performance['hours_pct']) }}"></span></div>
                <p class="text-[10.5px] text-gray-400 mt-1">{{ $performance['hours_pct'] === null ? 'No annual target set for this project yet.' : $performance['hours_pct'].'% of the annual target' }} · <span class="font-mono">trainors × trainees × days</span></p>
            </div>

            <div>
                <div class="flex items-baseline justify-between gap-2">
                    <p class="text-[12.5px] font-semibold">Budget utilized</p>
                    <p class="text-[12px] font-bold shrink-0 {{ $over ? 'text-red-600' : '' }}">₱{{ number_format($utilized) }} <span class="text-gray-400 font-medium">/ ₱{{ number_format($budgetVsAllocation['allocated']) }} allocated</span></p>
                </div>
                <div class="progress mt-1.5"><span style="width:{{ (int) min($budgetVsAllocation['pct'] ?? 0, 100) }}%" class="{{ $budgetVsAllocation['over'] ? 'bg-red-500' : 'bg-lnu-600' }}"></span></div>
                <p class="text-[10.5px] text-gray-400 mt-1">
                    {{ $budgetVsAllocation['pct'] === null ? 'No allocation set.' : $budgetVsAllocation['pct'].'% · ₱'.number_format($budgetVsAllocation['remaining']).' remaining' }}
                </p>
            </div>

            <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-3">
                <div>
                    <p class="label">Trainors assigned</p>
                    <p class="text-[15px] font-extrabold tracking-tight">{{ $performance['trainors'] }} <span class="text-[11px] text-gray-400 font-semibold">faculty</span></p>
                </div>
                <div>
                    <p class="label">Trainees reached</p>
                    <p class="text-[15px] font-extrabold tracking-tight">{{ number_format($performance['trainees']) }} <span class="text-[11px] text-gray-400 font-semibold">/ {{ $program->target_beneficiaries ? number_format((int) $program->target_beneficiaries) : '—' }}</span></p>
                </div>
            </div>

            <p class="text-[11px] text-gray-400 leading-relaxed pt-1">
                Broad programs carry <b>no</b> target — targets exist at University and Project level only (D-R5). The 8.6 KPI dictionary is no longer shown at project level (D-R7).
            </p>
        </div>
    </div>

{{-- EXECUTIVE NARRATIVE (Director-only, 5.15 — no approval gate) --}}
    @if ($canManage)
        @php($narrative = $latestNarrative)

        <div class="sc-card p-6 col-span-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-bold text-[14px] flex items-center gap-2">
                    <x-sc.icon name="sparkles" class="!w-[18px] !h-[18px] text-gold-500" /> Project Narrative
                    <span class="badge badge-gray !text-[10px]">Director-only · aggregates only</span>
                </h3>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('program-narratives.index') }}" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">History →</a>
                    @if ($fullNarrative)
                        <button wire:click="openNarrativeModal" wire:loading.attr="disabled" wire:target="generateNarrative" class="btn btn-outline !px-2.5 !py-1.5 !text-[11px]">
                            <x-sc.icon name="doc" class="w-3.5 h-3.5" />View full narrative
                        </button>
                    @endif
                    <button type="button" @click="$dispatch('project-narrative-generate', { title: @js($program->title), code: @js($program->code), college: @js($program->college?->name ?? 'College not linked'), lead: @js($program->programLead?->user?->name ?? 'No project lead assigned'), communities: @js($program->communities->pluck('name')->values()->all()) })" wire:click="generateNarrative" wire:loading.attr="disabled" wire:target="generateNarrative" class="btn btn-primary !px-2.5 !py-1.5 !text-[11px]">
                        <span wire:loading.remove wire:target="generateNarrative" class="inline-flex items-center gap-1.5"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" />Generate narrative</span>
                        <span wire:loading wire:target="generateNarrative" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-3.5 h-3.5 animate-spin" />Generating…</span>
                    </button>
                </div>
            </div>
            <div class="mt-3">
                @if ($narrative === null)
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-center">
                        <p class="text-[12.5px] text-gray-500 font-semibold">No narrative generated yet for this project.</p>
                        <p class="text-[11.5px] text-gray-400 mt-1">The Director can generate a project narrative from aggregate data (no PII leaves the system).</p>
                    </div>
                @elseif ($narrative->status === \App\Models\ProgramNarrative::STATUS_FAILED)
                    <div class="rounded-xl border border-red-200 bg-red-50/70 p-4">
                        <p class="text-[12.5px] font-bold text-red-700">Narrative unavailable</p>
                        <p class="text-[11.5px] text-red-600 mt-1">{{ $narrative->error_message }}</p>
                    </div>
                @elseif ($narrative->status === \App\Models\ProgramNarrative::STATUS_PENDING)
                    <div class="rounded-xl border border-gold-200 bg-gold-50/60 p-4 text-center">
                        <p class="text-[12.5px] font-semibold text-gold-800">Generating<span class="pulse-dot">…</span></p>
                    </div>
                @else
                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="narrative-health {{ $narrative->health_label }}">{{ match ($narrative->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}</span>
                            <span class="text-[11px] text-gray-400 font-medium">Generated {{ $narrative->generated_at?->format('M j, Y g:i A') }} · {{ $narrative->generator?->name }} · {{ $narrative->metadata['model'] ?? 'gemini' }} · prompt {{ $narrative->metadata['prompt_version'] ?? '—' }}</span>
                        </div>
                        <p class="text-[13px] text-gray-600 leading-relaxed">{{ $narrative->summary }}</p>
                        <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-[11px] text-gray-400 font-medium">{{ count($narrative->risks ?? []) }} risk{{ count($narrative->risks ?? []) === 1 ? '' : 's' }} flagged · {{ count($narrative->recommendations ?? []) }} recommended action{{ count($narrative->recommendations ?? []) === 1 ? '' : 's' }}</p>
                            <button wire:click="openNarrativeModal" class="text-[11.5px] font-bold text-lnu-700 hover:text-lnu-900 transition">Read the full narrative →</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    @endif
</div>
