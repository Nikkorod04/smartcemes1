<div class="mt-4 grid grid-cols-3 gap-4">
    <div class="sc-card p-6 col-span-2">
        <h3 class="font-bold text-[14px] mb-2 flex items-center gap-2"><x-sc.icon name="folder" class="!w-[18px] !h-[18px] text-lnu-700" /> Program Goal</h3>
        <p class="text-[13.5px] text-gray-600 leading-relaxed whitespace-pre-line">{{ $program->goals ?: ($program->description ?: 'No program goal recorded yet.') }}</p>
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

    {{-- RESULTS FRAMEWORK --}}
    <div class="sc-card p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-[14px]">Results Framework</h3>
            @if ($canManage)
                <button wire:click="openObjManager" class="btn btn-outline !px-2.5 !py-1.5 !text-[11px]">Manage</button>
            @endif
        </div>
        @if ($program->programObjectives->isEmpty())
            <p class="text-[12.5px] text-gray-400 mt-2">No objectives defined yet.{{ $canManage ? ' Use Manage to add measurable objectives with locked KPI metrics, or qualitative ones tracked manually.' : '' }}</p>
        @else
            <div class="space-y-3">
                @foreach ($program->programObjectives as $obj)
                    @php
                        $meta = $objectiveMeta[$obj->id];
                        $status = $meta['status'];
                        $actual = $meta['actual'];
                        $pct = $meta['pct'];
                        $statusMeta = [$meta['badge'], $meta['label']];
                        $barClass = $meta['bar'];
                    @endphp
                    <div class="mt-3 pt-3 border-t border-gray-100 first:border-0 first:pt-0 first:mt-0" wire:key="obj-{{ $obj->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[12.5px] font-semibold leading-snug">{{ $obj->objective }}</p>
                            <span class="badge {{ $statusMeta[0] }} shrink-0">{{ $statusMeta[1] }}</span>
                        </div>
                        <p class="text-[10.5px] text-gray-400 font-medium mt-0.5">
                            {{ $obj->kpi_metric ? (config('smartcemes.kpi_metrics')[$obj->kpi_metric] ?? $obj->kpi_metric) : 'Qualitative · manual' }}{{ $obj->unit ? ' · '.$obj->unit : '' }}{{ $obj->target_date ? ' · due '.$obj->target_date->format('M j, Y') : '' }}
                        </p>
                        <div class="progress mt-2"><span style="width:{{ $pct }}%" class="{{ $barClass }}"></span></div>
                        <p class="text-[11px] text-gray-500 font-semibold mt-1.5">Baseline {{ $obj->baseline_value ?? '—' }} → Target {{ $obj->target_value ?? '—' }} → Actual <span class="text-charcoal">{{ $actual === null ? '—' : number_format($actual, 1) }}</span>@if ($meta['source']) <span class="text-gray-400 font-medium">· {{ ['live' => 'auto (live)', 'stored' => 'stored', 'manual' => 'manual'][$meta['source']] }}</span>@endif</p>
                        @if ($obj->evidence_notes)
                            <p class="text-[11px] text-gray-400 mt-1"><span class="font-semibold text-gray-500">Evidence:</span> {{ $obj->evidence_notes }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

{{-- EXECUTIVE NARRATIVE (Director-only, 5.15 — no approval gate) --}}
    @if ($canManage)
        <div class="sc-card p-6 col-span-3">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-[14px] flex items-center gap-2"><x-sc.icon name="sparkles" class="!w-[18px] !h-[18px] text-gold-500" /> Executive Narrative</h3>
                <div class="flex gap-2">
                    <a href="{{ route('program-narratives.index') }}" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">History →</a>
                    <button wire:click="generateNarrative" class="btn btn-primary !px-2.5 !py-1.5 !text-[11px]">Generate program narrative</button>
                </div>
            </div>
            <div class="mt-3">
                @php($narrative = $this->program->programNarratives()->latest('id')->first())
                @if ($narrative === null)
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4 text-center">
                        <p class="text-[12.5px] text-gray-500 font-semibold">No narrative generated yet for this program.</p>
                        <p class="text-[11.5px] text-gray-400 mt-1">The Director can generate an executive summary from program aggregates (no PII leaves the system).</p>
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
                            <span class="text-[11px] text-gray-400 font-medium">Generated {{ $narrative->generated_at?->format('M j, Y g:i A') }} · {{ $narrative->generator?->name }} · {{ $narrative->metadata['model'] ?? 'gemini' }} · prompt v{{ $narrative->metadata['prompt_version'] ?? '—' }}</span>
                        </div>
                        <p class="text-[13px] text-gray-600 leading-relaxed">{{ $narrative->summary }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
