<div>
@php($completed = $programs->filter(fn ($row) => $row->latest && $row->latest->status === 'completed'))
@php($generatedCount = $completed->count())
@php($onTrackCount = $completed->filter(fn ($row) => $row->latest->health_label === 'on-track')->count())
@php($attentionCount = $completed->filter(fn ($row) => in_array($row->latest->health_label, ['at-risk', 'needs-attention']))->count())

{{-- ===================== AI HERO ===================== --}}
<section class="pt-6 reveal-item">
    <div class="ai-panel p-6 flex items-start justify-between gap-6">
        <div class="min-w-0">
            <span class="ai-chip"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" /> Executive Program Narratives</span>
            <h1 class="mt-3 text-white font-extrabold text-xl tracking-tight leading-snug">Program Status at a Glance</h1>
            <p class="mt-2 text-[12.5px] text-blue-100/75 leading-relaxed max-w-2xl">AI-generated executive summaries of each extension project — training hours, trainees, activities, budget against target, risks, and recommended next actions. Director-only; full provenance is recorded for audit.</p>
        </div>
        <div class="text-right shrink-0 space-y-2">
            <span class="badge badge-blue !bg-white/10 !text-blue-100 !border-white/20">Aggregated data only · no PII</span>
            <div class="flex items-center justify-end flex-wrap gap-1.5">
                <span class="badge !bg-white/10 !text-blue-100 !border-white/20">{{ $generatedCount }}/{{ $programs->count() }} generated</span>
                <span class="badge !bg-white/10 !text-blue-100 !border-white/20">{{ $onTrackCount }} on track</span>
                @if ($attentionCount)
                    <span class="badge !bg-gold-500/15 !text-gold-200 !border-gold-300/30">{{ $attentionCount }} need attention</span>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- ===================== LATEST NARRATIVES ===================== --}}
<section class="mt-6">
    <div class="reveal-item flex items-center justify-between mb-3">
        <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2">Latest Narratives
            <span class="badge badge-gray">{{ $generatedCount }} of {{ $programs->count() }} programs generated</span>
        </h3>
        <span class="text-[12px] text-gray-400">One row per program — including programs with no narrative yet (first-class "unavailable" state).</span>
    </div>

    <div class="grid gap-5">
        @forelse ($programs as $row)
            @php($p = $row->model)
            @php($versions = $p->programNarratives->sortByDesc('created_at')->values())
            <div class="reveal-item sc-card p-5" wire:key="pn-{{ $p->id }}">
                <div class="flex items-start justify-between gap-3 pb-3.5 border-b border-gray-50">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-[14px] leading-snug">{{ $p->title }}</p>
                            <span class="badge badge-gray !text-[10px] font-mono">{{ $p->code }}</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1.5">Lead: {{ $p->programLead?->user?->name ?? '—' }} · {{ $p->communities->pluck('name')->implode(', ') ?: 'No community linked' }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($row->latest && $row->latest->status === 'completed')
                            <span class="narrative-health {{ $row->latest->health_label }}">{{ match ($row->latest->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}</span>
                        @elseif ($row->latest && $row->latest->status === 'failed')
                            <span class="narrative-health needs-attention">Narrative unavailable</span>
                        @endif
                        <button wire:click="generate({{ $p->id }})" wire:loading.attr="disabled" wire:target="generate" class="btn btn-primary !px-3 !py-1.5 !text-[11.5px]">
                            <x-sc.icon name="sparkles" class="w-3.5 h-3.5" />Generate
                        </button>
                    </div>
                </div>

                @if ($row->latest === null)
                    <div class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50/60 p-4">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl bg-lnu-50 text-lnu-700 flex items-center justify-center shrink-0"><x-sc.icon name="clock" class="w-[18px] h-[18px]" /></span>
                            <div class="min-w-0">
                                <p class="text-[12.5px] font-bold text-gray-600">Narrative unavailable</p>
                                <p class="text-[12px] text-gray-500 mt-0.5 leading-relaxed">No narrative has been generated for this program yet. Generation is Director-only and uses aggregate program data (no PII).</p>
                                <div class="flex items-center gap-2 mt-2.5">
                                    {{-- R5 / D-R7: the objectives-met badge was an 8.6 surface.
                                         Replaced by training-hours attainment against the
                                         project's annual target. --}}
                                    @if ($row->hours_pct !== null)
                                        <span @class(['badge !text-[10.5px]', $row->hours_pct >= 100 ? 'badge-green' : 'badge-blue'])>Training hours: {{ number_format($row->training_hours, 1) }} / {{ number_format($row->target_hours) }} ({{ round($row->hours_pct) }}%)</span>
                                    @else
                                        <span class="badge badge-gray !text-[10.5px]">{{ number_format($row->training_hours, 1) }} hrs · no target set</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($row->latest->status === 'failed')
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50/70 p-4">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl bg-red-100 text-red-500 flex items-center justify-center shrink-0"><x-sc.icon name="clock" class="w-[18px] h-[18px]" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[12.5px] font-bold text-gray-700">Narrative unavailable</p>
                                <p class="text-[12px] text-red-600 mt-0.5 leading-relaxed">{{ $row->latest->error_message }}</p>
                                <button wire:click="generate({{ $p->id }})" wire:loading.attr="disabled" wire:target="generate" class="btn btn-danger-soft !px-3 !py-1.5 !text-[11.5px] mt-3">⟳ Regenerate</button>
                            </div>
                        </div>
                    </div>
                @elseif ($row->latest->status === 'pending')
                    <div class="mt-4 rounded-xl border border-gold-200 bg-gold-50/60 p-4">
                        <p class="text-[12.5px] font-semibold text-gold-800 flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-gold-500 pulse-dot"></span>Generating…</p>
                        <div class="mt-3 space-y-2 max-w-xl">
                            <div class="skeleton h-3 rounded-md" style="width:96%"></div>
                            <div class="skeleton h-3 rounded-md" style="width:88%"></div>
                            <div class="skeleton h-3 rounded-md" style="width:62%"></div>
                        </div>
                        <p class="text-[11px] text-gold-700/70 mt-3">{{ $row->latest->metadata['model'] ?? 'gemini' }} · prompt v{{ $row->latest->metadata['prompt_version'] ?? '—' }}</p>
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400"><x-sc.icon name="doc" class="w-3.5 h-3.5" /> Executive Summary</p>
                                @if ($row->hours_pct !== null)
                                    <span @class(['badge !text-[10.5px] shrink-0', $row->hours_pct >= 100 ? 'badge-green' : 'badge-blue'])>Training hours: {{ number_format($row->training_hours, 1) }} / {{ number_format($row->target_hours) }} ({{ round($row->hours_pct) }}%)</span>
                                @else
                                    <span class="badge badge-gray !text-[10.5px] shrink-0">{{ number_format($row->training_hours, 1) }} hrs · no target set</span>
                                @endif
                            </div>
                            <p class="text-[12.5px] text-gray-600 leading-relaxed mt-2.5">{{ $row->latest->summary }}</p>
                        </div>
                        <div class="space-y-3">
                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                                <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Top Risks</p>
                                <ul class="space-y-1.5 text-[12.5px] text-gray-600">
                                    @forelse ($row->latest->risks ?? [] as $risk)
                                        <li class="flex items-start gap-1.5"><span class="text-red-500 mt-1 text-[8px]">●</span><span class="leading-snug">{{ is_array($risk) ? ($risk['risk'] ?? $risk['text'] ?? '') : $risk }}</span></li>
                                    @empty
                                        <li class="text-gray-400 italic">No material risks identified.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                                <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2"><span class="w-1.5 h-1.5 rounded-sm bg-lnu-400"></span> Recommended Next Actions</p>
                                <ul class="space-y-2 text-[12.5px] text-gray-600">
                                    @forelse ($row->latest->recommendations ?? [] as $r)
                                        @php($prioTone = (($r['priority'] ?? '') === 'High') ? 'badge-red' : ((($r['priority'] ?? '') === 'Medium') ? 'badge-gold' : 'badge-gray'))
                                        <li class="flex items-start gap-1.5">
                                            <span class="text-lnu-700 mt-0.5 text-[10px]">▸</span>
                                            <span class="leading-snug min-w-0">
                                                <span class="flex items-start gap-1.5 flex-wrap">
                                                    @if (! empty($r['priority']))
                                                        <span class="badge {{ $prioTone }} !text-[10px] shrink-0">{{ $r['priority'] }}</span>
                                                    @endif
                                                    <span class="font-semibold">{{ $r['action'] ?? '' }}</span>
                                                </span>
                                                @if (! empty($r['rationale']))<span class="block text-[11px] text-gray-400 mt-0.5 leading-snug">{{ $r['rationale'] }}</span>@endif
                                            </span>
                                        </li>
                                    @empty
                                        <li class="text-gray-400 italic">None pending.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3.5 border-t border-gray-50 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-[11px] text-gray-400">Generated {{ $row->latest->generated_at?->format('M j, Y · g:i A') }} · {{ $row->latest->generator?->name ?? 'Director, CESO' }}</p>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="badge badge-gray !text-[10px] font-mono">{{ $row->latest->metadata['model'] ?? 'gemini' }}</span>
                            <span class="badge badge-gray !text-[10px]">prompt v{{ $row->latest->metadata['prompt_version'] ?? '—' }}</span>
                            <span class="badge badge-gray !text-[10px]">confidence {{ $row->latest->confidence_score !== null ? number_format((float) $row->latest->confidence_score, 2) : '—' }}</span>
                        </div>
                    </div>
                @endif

                @if ($versions->count())
                    <details class="sc-acc mt-4" wire:key="pn-acc-{{ $p->id }}">
                        <summary>Version history <span class="acc-num">{{ $versions->count() }}</span><svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg></summary>
                        <div class="px-4 pb-3.5 pt-1 border-t border-gray-50">
                            <ol class="relative border-l border-gray-100 ml-2 mt-3.5 space-y-3.5">
                                @foreach ($versions as $v)
                                    @php($failed = $v->status === 'failed')
                                    <li class="ml-4" wire:key="pnv-{{ $v->id }}">
                                        <span @class([$failed ? 'bg-red-400 ring-4 ring-red-50' : 'bg-lnu ring-4 ring-lnu-50' => true, 'absolute -left-[7px] top-1 w-3 h-3 rounded-full'])></span>
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[12px] font-semibold leading-snug">
                                                    @if ($failed)
                                                        Generation attempt — failed
                                                    @else
                                                        {{ $versions->count() - $loop->index > 0 ? 'Narrative v'.($versions->count() - $loop->index) : 'Narrative' }}
                                                        @if ($v->health_label) · {{ match ($v->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}@endif
                                                    @endif
                                                </p>
                                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $failed ? ($v->error_message ?? 'Narrative unavailable') : 'Generated '.$v->created_at->format('M j, Y · g:i A').' · '.($v->generator?->name ?? 'Director, CESO').' · '.($v->metadata['model'] ?? 'gemini') }}</p>
                                            </div>
                                            <span @class([$failed ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-gray-50 text-gray-500 border border-gray-100' => true, 'shrink-0 text-[10.5px] font-bold px-2 py-1 rounded-lg'])>{{ $v->created_at->format('M j, Y') }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="text-[11px] text-gray-400 mt-3 ml-1">Every generation creates a new row — the Director can browse past versions per program. Provenance (model, prompt version, generated_at/by) is recorded.</p>
                        </div>
                    </details>
                @endif
            </div>
        @empty
            <div class="sc-card p-9 text-center">
                <p class="text-[12.5px] text-gray-500">No programs yet.</p>
            </div>
        @endforelse
    </div>
</section>

<footer class="mt-10 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- GENERATING OVERLAY — centered while the synchronous generation runs --}}
<div wire:loading.flex wire:target="generate"
     class="fixed inset-0 z-[70] items-center justify-center bg-charcoal/50 backdrop-blur-[2px] no-print">
    <div class="sc-card sc-modal px-10 py-8 text-center shadow-pop max-w-sm mx-4">
        <span class="relative w-16 h-16 mx-auto rounded-2xl bg-lnu-50 text-lnu-700 flex items-center justify-center">
            <x-sc.icon name="sparkles" class="w-7 h-7" />
            <span class="absolute -bottom-1.5 -right-1.5 w-7 h-7 rounded-xl bg-gold-500 text-white flex items-center justify-center ring-2 ring-white shadow-sm">
                <x-sc.icon name="loader" class="w-4 h-4 animate-spin" />
            </span>
        </span>
        <p class="mt-4 font-extrabold text-[14px] tracking-tight">Generating executive narrative<span class="pulse-dot">…</span></p>
        <p class="text-[12px] text-gray-400 mt-1 leading-relaxed">Summarizing training hours, trainees, activities, budget and annual targets. Aggregates only — no PII leaves the system.</p>
        <div class="mt-4 flex justify-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-lnu-700 animate-bounce"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-lnu-500 animate-bounce" style="animation-delay:150ms"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gold-500 animate-bounce" style="animation-delay:300ms"></span>
        </div>
    </div>
</div>
</div>
