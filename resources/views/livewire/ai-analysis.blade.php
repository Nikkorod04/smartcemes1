<div>
@php($drafts = $analyses->where('approval_status', 'draft')->where('status', 'completed')->values())
@php($hero = $drafts->firstWhere('id', $detailId) ?? $drafts->first())

{{-- ===================== SUMMARY PICKER ===================== --}}
<section class="pt-6 reveal-item">
    <div class="sc-card p-5 flex flex-wrap items-center gap-4">
        <span class="w-11 h-11 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="clipboard" class="w-5 h-5" /></span>
        <div class="min-w-0 flex-1">
            <label class="label !mb-1.5">Community summary to analyze</label>
            <select class="input !w-auto min-w-[320px] max-w-full" wire:model.live="summaryId">
                <option value="">— select validated summary —</option>
                @foreach ($summaries as $s)
                    <option value="{{ $s->id }}">{{ $s->community->name }} · Q{{ $s->quarter }} {{ $s->year }} ({{ $s->total_responses }} responses)</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-4 ml-auto shrink-0">
            <p class="text-[11px] text-gray-400 leading-snug max-w-[220px] hidden xl:block">Only validated AssessmentSummaries can be analyzed — aggregates only, never respondent PII (D3).</p>
            <button wire:click="generate" wire:target="generate" wire:loading.attr="disabled" @class(['btn btn-primary whitespace-nowrap', 'opacity-50 pointer-events-none' => ! $summaryId])>
                <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="sparkles" class="w-4 h-4" />Generate analysis</span>
                <span wire:loading wire:target="generate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4 animate-spin" />Generating…</span>
            </button>
        </div>
    </div>
    <p wire:loading wire:target="generate" class="mt-3 flex items-center gap-2 text-[12px] font-semibold text-lnu-700">
        <span class="w-2 h-2 rounded-full bg-gold-500 pulse-dot"></span>
        Generating analysis — calling the live Gemini API with aggregate data only; this usually takes a few seconds…
    </p>
</section>

@if ($hero)
    {{-- ===================== AI HERO ===================== --}}
    <section class="mt-6 reveal-item" wire:key="ai-hero-{{ $hero->id }}">
        <div class="ai-panel p-6 flex items-start justify-between gap-6">
            <div class="min-w-0">
                <span class="ai-chip"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" /> AI Insight Engine</span>
                <h1 class="mt-3 text-white font-extrabold text-xl tracking-tight leading-snug">{{ $hero->assessmentSummary->community->name }} · Q{{ $hero->assessmentSummary->quarter }} {{ $hero->assessmentSummary->year }} Needs-Assessment Analysis</h1>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="ai-chip"><x-sc.icon name="sparkles" class="w-3.5 h-3.5" /> model {{ $hero->metadata['model'] ?? 'gemini' }}</span>
                    <span class="ai-chip"><x-sc.icon name="shield" class="w-3.5 h-3.5" /> aggregated inputs only — no PII (D3)</span>
                    <span class="ai-chip"><x-sc.icon name="people" class="w-3.5 h-3.5" /> n = {{ $hero->assessmentSummary->total_responses }} respondents</span>
                    <span class="ai-chip" title="{{ $hero->metadata['confidence_basis'] ?? 'Derived from sample size and data completeness — guidance only.' }}"><x-sc.icon name="check" class="w-3.5 h-3.5" /> confidence {{ $hero->confidence_score !== null ? number_format((float) $hero->confidence_score, 2) : '—' }}</span>
                </div>
            </div>
            <div class="text-right shrink-0">
                <p class="text-[11px] text-blue-100/70 font-medium">Generated · {{ $hero->created_at->format('M j, Y g:i A') }}</p>
                <div class="mt-2.5">
                    <span class="badge badge-yellow !bg-gold-500/15 !text-gold-200 !border-gold-300/30"><span class="w-1.5 h-1.5 rounded-full bg-gold-400 pulse-dot"></span>DRAFT — AWAITING REVIEW</span>
                </div>
            </div>
        </div>
        <p class="mt-3 text-[11.5px] text-gray-400 leading-relaxed max-w-4xl">Only aggregate counts and percentages derived from validated summaries were sent to the language model. Raw responses never leave the server. Human approval is required before this analysis is cited institutionally.</p>
    </section>

    {{-- ===================== NARRATIVE + INTERVENTIONS ===================== --}}
    <section class="mt-4 grid grid-cols-3 gap-4 items-start">
        <div class="reveal-item sc-card p-6 col-span-2" wire:key="ai-narrative-{{ $hero->id }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-[14px]">Analysis Narrative</h3>
                <span class="badge badge-gray">auto-generated draft</span>
            </div>
            <p class="flex items-center gap-2 text-[10.5px] font-extrabold uppercase tracking-[.14em] text-lnu-800"><span class="w-1.5 h-1.5 rounded-sm bg-gold-500"></span> Situation Overview</p>
            <p class="mt-2 leading-relaxed text-[14px] text-charcoal font-medium">{{ $hero->summary ?? '—' }}</p>

            {{-- Community response data (the exact aggregates sent to the model, D3) --}}
            @php($aggRaw = $hero->raw_extracted_data ?? [])
            @if (! empty($aggRaw))
                @php($aggTotal = max(1, (int) ($aggRaw['total_responses'] ?? 0)))
                @php($aggGroups = [
                    'Respondent profile' => [
                        'gender_distribution' => 'Sex',
                        'civil_status_distribution' => 'Civil status',
                        'religion_distribution' => 'Religion',
                        'education_distribution' => 'Educational attainment',
                    ],
                    'Household & utilities' => [
                        'water_sources' => 'Water sources',
                        'house_types' => 'House types',
                    ],
                    'Priority problems reported' => [
                        'health_problems' => 'Health',
                        'family_problems' => 'Family',
                        'employment_problems' => 'Employment',
                        'infrastructure_problems' => 'Infrastructure',
                        'economic_problems' => 'Economic',
                        'security_problems' => 'Security',
                    ],
                    'Interests & training' => [
                        'livelihood_interests' => 'Desired livelihood training',
                        'educational_interests' => 'Areas of educational interest',
                    ],
                ])
                @php($aggShown = collect($aggGroups)->map(fn ($f) => array_keys($f))->flatten()->filter(fn ($f) => ! empty($aggRaw[$f]))->count())
                @php($aggPct = collect([
                    'Has electricity' => $aggRaw['electricity_access_percentage'] ?? null,
                    'Available for training' => $aggRaw['training_availability_percentage'] ?? null,
                ])->filter(fn ($v) => $v !== null))
                <div class="mt-5">
                    <details class="sc-acc" wire:key="ai-agg-{{ $hero->id }}">
                        <summary>
                            <x-sc.icon name="chart" class="w-4 h-4 text-lnu-700" />
                            Community response data
                            <span class="text-[11px] font-semibold text-gray-400">{{ $aggTotal }} respondent{{ $aggTotal === 1 ? '' : 's' }} · {{ $aggShown }} datasets</span>
                            <svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="px-4 pb-4 pt-3 border-t border-gray-50">
                            @if ($aggPct->isNotEmpty() || ($aggRaw['avg_service_satisfaction'] ?? null) !== null)
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Key indicators</p>
                                <div class="grid grid-cols-2 lg:grid-cols-3 gap-2.5 mt-2.5">
                                    @foreach ($aggPct as $aggLabel => $aggValue)
                                        <div class="rounded-xl bg-lnu-50/60 border border-lnu-100 px-3.5 py-2.5">
                                            <p class="text-[10.5px] font-bold uppercase tracking-wide text-lnu-800 truncate">{{ $aggLabel }}</p>
                                            <p class="text-[16px] font-extrabold text-lnu-900 mt-0.5">{{ round((float) $aggValue) }}%</p>
                                            <p class="text-[10.5px] text-gray-500 font-medium">{{ (int) round((float) $aggValue / 100 * $aggTotal) }} of {{ $aggTotal }} respondents</p>
                                        </div>
                                    @endforeach
                                    @if (($aggRaw['avg_service_satisfaction'] ?? null) !== null)
                                        <div class="rounded-xl bg-gold-50 border border-gold-100 px-3.5 py-2.5">
                                            <p class="text-[10.5px] font-bold uppercase tracking-wide text-gold-800 truncate">Avg service satisfaction</p>
                                            <p class="text-[16px] font-extrabold text-gold-900 mt-0.5">{{ number_format((float) $aggRaw['avg_service_satisfaction'], 2) }}<span class="text-[11px] font-bold text-gold-700"> / 5</span></p>
                                            <p class="text-[10.5px] text-gray-500 font-medium">barangay service ratings</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @foreach ($aggGroups as $aggHeading => $aggFields)
                                @php($aggFields = collect($aggFields)->filter(fn ($label, $field) => ! empty($aggRaw[$field])))
                                @if ($aggFields->isNotEmpty())
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mt-5">{{ $aggHeading }}</p>
                                    <div class="mt-2 grid lg:grid-cols-2 gap-x-6 gap-y-4">
                                        @foreach ($aggFields as $aggField => $aggLabel)
                                            @php($aggItems = collect($aggRaw[$aggField] ?? [])->sortDesc())
                                            @php($aggMax = max(1, (int) $aggItems->max()))
                                            <div>
                                                <p class="text-[11.5px] font-bold text-charcoal mb-1.5">{{ $aggLabel }} <span class="font-semibold text-gray-400">· multi-select</span></p>
                                                <div class="space-y-1.5">
                                                    @foreach ($aggItems as $aggKey => $aggCount)
                                                        <div>
                                                            <div class="flex items-center justify-between gap-3">
                                                                <p class="text-[12px] text-gray-600 font-medium truncate">{{ $aggKey }}</p>
                                                                <p class="text-[11.5px] font-bold text-charcoal shrink-0">{{ $aggCount }} <span class="text-gray-400 font-semibold">({{ (int) round($aggCount / $aggTotal * 100) }}%)</span></p>
                                                            </div>
                                                            <div class="h-1 rounded-full bg-gray-100 overflow-hidden mt-1"><div class="h-full rounded-full bg-lnu-300" style="width: {{ (int) round($aggCount / $aggMax * 100) }}%"></div></div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                            <p class="mt-4 pt-3 border-t border-gray-50 text-[11px] text-gray-400 leading-relaxed">Counts and percentages over the {{ $aggTotal }} validated responses for {{ $aggRaw['community'] ?? $hero->assessmentSummary->community->name }} · Q{{ $aggRaw['quarter'] ?? $hero->assessmentSummary->quarter }} {{ $aggRaw['year'] ?? $hero->assessmentSummary->year }}. These are the exact aggregate statistics transmitted to the model (D3) — no individual response left the server. Multi-select fields may sum above 100%.</p>
                        </div>
                    </details>
                </div>
            @endif

            <div class="mt-6 pt-5 border-t border-gray-100">
                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-2.5">Provenance</p>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Prompt</p>
                        <p class="text-[12.5px] font-semibold mt-0.5">v{{ $hero->metadata['prompt_version'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Model</p>
                        <p class="text-[12.5px] font-mono font-semibold mt-0.5 truncate">{{ $hero->metadata['model'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5" title="{{ $hero->metadata['confidence_basis'] ?? 'Derived data confidence — guidance only' }}">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Confidence</p>
                        <p class="text-[12.5px] font-semibold mt-0.5">{{ $hero->confidence_score !== null ? number_format((float) $hero->confidence_score, 2) : '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            {{-- ===== CESO INTERVENTIONS (revision §7.1 / R6) =====
                 The AI's recommendations are split into two hard-separated groups so a
                 reader can never mistake an out-of-scope referral for something CESO
                 will deliver. This group is green and rail-marked; the interagency
                 referrals sit below with the amber rail and name the agency explicitly.
                 The `tier-*` class names are internal styling only — the wording the
                 Director reads is scope language, never "tier". --}}
            <div class="reveal-item sc-card p-6 tier-card tier-1" wire:key="ai-interventions-{{ $hero->id }}">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-[14px]">CESO interventions</h3>
                            <span class="tier-badge tier-1">CESO intervention</span>
                        </div>
                        <p class="text-[11px] text-gray-400 font-medium mt-1.5 leading-relaxed">
                            Deliverable as CESO extension activities — signed off against the six CESO thrusts.
                        </p>
                    </div>
                    <span class="badge badge-green shrink-0">{{ count($hero->cesoInterventions()) }} actions</span>
                </div>
                <ol class="space-y-3">
                    @forelse ($hero->cesoInterventions() as $r)
                        @php($rankNum = (int) ($r['rank'] ?? 9))
                        @php($rankTone = $rankNum <= 2 ? 'bg-lnu text-white' : ($rankNum <= 4 ? 'bg-gold-100 text-gold-800' : 'bg-gray-100 text-gray-500'))
                        <li class="flex gap-3 p-3.5 rounded-xl border border-gray-100 hover:border-lnu-200 hover:bg-lnu-50/40 hover:shadow-card transition">
                            <span @class(['w-8 h-8 rounded-full flex items-center justify-center text-[12.5px] font-extrabold shrink-0', $rankTone => true])>{{ $r['rank'] ?? '·' }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[13px] font-bold leading-snug">{{ $r['title'] }}</p>
                                    @if (($r['priority'] ?? '') === 'High')
                                        <span class="badge badge-red shrink-0">High</span>
                                    @elseif (($r['priority'] ?? '') === 'Medium')
                                        <span class="badge badge-gold shrink-0">Medium</span>
                                    @elseif (($r['priority'] ?? '') !== '')
                                        <span class="badge badge-gray shrink-0">Low</span>
                                    @endif
                                </div>
                                <p class="text-[11.5px] text-gray-500 mt-1 leading-relaxed">{{ $r['detail'] ?? '' }}</p>
                                @if (! empty($r['ceso_program']))
                                    <p class="text-[10.5px] text-gray-400 font-semibold mt-1.5">
                                        Thrust · {{ $r['ceso_program'] }}
                                    </p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-[12px] text-gray-400 italic">No CESO-deliverable interventions returned.</li>
                    @endforelse
                </ol>
            </div>

            {{-- ===== INTERAGENCY REFERRALS (§7.1 / D-R10) =====
                 Rendered as its own group, never mixed into the list above. An empty
                 result is the NORMAL case and is stated plainly rather than hidden,
                 because "the model found nothing out of scope" is information the
                 reviewer wants. This is ADVISORY ONLY — the system raises the flag and
                 names the agency from the catalogue; it files no form, SO or paperwork. --}}
            <div class="reveal-item sc-card p-6 tier-card tier-2" wire:key="ai-referrals-{{ $hero->id }}">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-[14px]">Interagency referrals</h3>
                            <span class="tier-badge tier-2">Requires interagency referral</span>
                        </div>
                        <p class="text-[11px] text-gray-400 font-medium mt-1.5 leading-relaxed">
                            Real community needs CESO does not deliver. Flagged for referral to the named agency — neither dropped nor
                            mislabelled as a CESO activity.
                        </p>
                    </div>
                    <span class="badge {{ $hero->hasReferrals() ? 'badge-yellow' : 'badge-gray' }} shrink-0">
                        {{ count($hero->interagency_referrals ?? []) }} {{ \Illuminate\Support\Str::plural('referral', count($hero->interagency_referrals ?? [])) }}
                    </span>
                </div>

                @if ($hero->hasReferrals())
                    <ul class="space-y-3">
                        @foreach ($hero->resolvedReferrals() as $referral)
                            <li class="p-3.5 rounded-xl border border-gray-100 bg-white">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-[13px] font-bold leading-snug">{{ $referral['need'] }}</p>
                                    @php($agency = $referral['agency'])
                                    @if ($agency === null)
                                        <span class="tier-badge tier-3 shrink-0">unverified</span>
                                    @endif
                                </div>
                                @if ($referral['rationale'] !== '')
                                    <p class="text-[11.5px] text-gray-500 mt-1 leading-relaxed">{{ $referral['rationale'] }}</p>
                                @endif

                                <div class="mt-2.5">
                                    @if ($agency !== null)
                                        <span class="interagency-note inline-flex items-start gap-2 text-[11.5px]">
                                            <x-sc.icon name="shield" class="w-4 h-4 text-[#b45309] shrink-0 mt-[1px]" />
                                            <span>
                                                Refer to <b>{{ $agency->label }}</b>
                                                <span class="block text-[10.5px] text-gray-500 mt-0.5">
                                                    Intervention category: {{ $agency->need_category }}
                                                </span>
                                            </span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-start gap-2 text-[11.5px] rounded-xl px-3.5 py-2.5 bg-red-50 border border-red-200 text-red-700">
                                            <x-sc.icon name="bell" class="w-4 h-4 shrink-0 mt-[1px]" />
                                            <span>
                                                <b>Agency not in the catalogue.</b> The model cited
                                                <span class="font-mono">{{ $referral['as_cited']['agency_code'] ?: '(no code)' }}</span>
                                                ({{ $referral['as_cited']['agency_name'] ?: 'no name' }}). Rejected rather than shown as a
                                                valid referral — add the agency to the catalogue if it is a genuine partner.
                                            </span>
                                        </span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($hero->unverifiedReferralCount() > 0)
                        <p class="text-[11px] text-red-600 font-semibold mt-3 pt-3 border-t border-gray-50">
                            {{ $hero->unverifiedReferralCount() }} citation(s) could not be verified against the catalogue and were rejected server-side.
                        </p>
                    @endif
                @else
                    <p class="text-[12px] text-gray-400 italic">
                        No needs fell outside CESO's mandate for this assessment — every identified need was addressed above.
                    </p>
                @endif

                @if (! empty($hero->metadata['agency_catalogue_snapshot']))
                    <details class="mt-4 pt-3 border-t border-gray-50">
                        <summary class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400 cursor-pointer select-none hover:text-lnu-800 transition">
                            Catalogue snapshot used for this analysis ({{ count($hero->metadata['agency_catalogue_snapshot']) }} agencies)
                        </summary>
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach ($hero->metadata['agency_catalogue_snapshot'] as $snap)
                                <span class="badge badge-gray font-mono">{{ $snap['agency_code'] }}</span>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-gray-400 mt-2 leading-relaxed">
                            The exact set of agencies the model was permitted to cite. Retiring an agency later does not alter this
                            record, so the referral above stays reproducible.
                        </p>
                    </details>
                @endif
            </div>

            <div class="reveal-item sc-card p-6" wire:key="ai-needs-{{ $hero->id }}">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-[14px]">Identified Priority Needs</h3>
                    <span class="badge badge-blue">from aggregates</span>
                </div>
                <ol class="space-y-3">
                    @forelse ($hero->problems_identified ?? [] as $i => $p)
                        <li class="flex items-start gap-3">
                            <span @class([$i === 0 ? 'bg-lnu text-white' : 'bg-gray-100 text-gray-500' => true, 'w-6 h-6 rounded-lg flex items-center justify-center text-[11px] font-extrabold shrink-0 mt-0.5'])>{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[12.5px] font-semibold leading-snug">{{ $p['need'] }}</p>
                                @if (! empty($p['evidence']))
                                    <p class="text-[11.5px] text-gray-400 mt-1.5 leading-relaxed bg-gray-50 border border-gray-100 rounded-lg px-2.5 py-1.5">{{ $p['evidence'] }}</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-[12px] text-gray-400 italic">No needs identified.</li>
                    @endforelse
                </ol>
                <p class="text-[11px] text-gray-400 mt-4 pt-3 border-t border-gray-50 leading-relaxed">Derived from the AssessmentSummary aggregates for {{ $hero->assessmentSummary->community->name }} · Q{{ $hero->assessmentSummary->quarter }} {{ $hero->assessmentSummary->year }} — the exact statistics transmitted to the model (D3).</p>
            </div>
        </div>
    </section>

    {{-- ===================== ACTION BAR ===================== --}}
    <section class="sticky bottom-4 mt-4 z-20 reveal-item no-print">
        <div class="sc-card px-5 py-4 flex items-center gap-4 shadow-pop">
            <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-5 h-5" /></span>
            <div class="min-w-0">
                <p class="text-[13px] font-bold leading-snug">As CESO Director, your approval makes this analysis citable in reports.</p>
                <p class="text-[11px] text-gray-400 mt-0.5">Approval is signed with your name and timestamp, then archived to the audit log.</p>
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                <button wire:click="discard({{ $hero->id }})" wire:loading.attr="disabled" class="btn btn-danger-soft">Discard</button>
                <button wire:click="approve({{ $hero->id }})" wire:loading.attr="disabled" class="btn btn-primary !px-4 tracking-wide"><x-sc.icon name="shield" class="w-4 h-4" />APPROVE ANALYSIS</button>
            </div>
        </div>
    </section>
@else
    {{-- ===================== EMPTY STATE ===================== --}}
    <section class="mt-6 reveal-item">
        <div class="sc-card p-9 text-center">
            <span class="w-[52px] h-[52px] rounded-2xl bg-gold-50 text-gold-500 mx-auto flex items-center justify-center"><x-sc.icon name="sparkles" class="w-6 h-6" /></span>
            <h2 class="mt-4 font-extrabold text-[16px] tracking-tight">No analysis in review</h2>
            <p class="mt-1.5 text-[12.5px] text-gray-500 max-w-md mx-auto leading-relaxed">Generate a community needs analysis from a validated summary — it arrives here as a draft for your review and approval before it can be cited institutionally.</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-[11.5px] font-semibold text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-5 h-5 rounded-md bg-lnu-50 text-lnu-700 flex items-center justify-center text-[10.5px] font-extrabold">1</span> Select a validated summary</span>
                <x-sc.icon name="chevron" class="w-3.5 h-3.5 text-gray-300 -rotate-90" />
                <span class="flex items-center gap-1.5"><span class="w-5 h-5 rounded-md bg-lnu-50 text-lnu-700 flex items-center justify-center text-[10.5px] font-extrabold">2</span> Generate analysis</span>
                <x-sc.icon name="chevron" class="w-3.5 h-3.5 text-gray-300 -rotate-90" />
                <span class="flex items-center gap-1.5"><span class="w-5 h-5 rounded-md bg-lnu-50 text-lnu-700 flex items-center justify-center text-[10.5px] font-extrabold">3</span> Review &amp; approve</span>
            </div>
        </div>
    </section>
@endif

{{-- ===================== DRAFTS QUEUE ===================== --}}
<section class="mt-7">
    <div class="reveal-item flex items-center justify-between mb-3">
        <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="sparkles" class="w-[18px] h-[18px] text-gold-500" /> Drafts awaiting approval</h3>
        <span class="badge badge-gold">{{ $drafts->count() }} drafts</span>
    </div>
    <div class="grid grid-cols-2 gap-4">
        @forelse ($drafts as $a)
            @php($isSelected = $hero && $hero->id === $a->id)
            <button type="button" wire:click="viewDetail({{ $a->id }})" wire:key="ai-draft-{{ $a->id }}"
                    @class([$isSelected ? '!border-lnu-400 ring-2 ring-lnu-100 bg-lnu-50/30' : 'hover:!border-lnu-200 sc-card-hover' => true, 'sc-card p-4 text-left transition group'])>
                <div class="flex items-center justify-between gap-2">
                    <p class="font-bold text-[13px] truncate">{{ $a->assessmentSummary->community->name }} · Q{{ $a->assessmentSummary->quarter }} {{ $a->assessmentSummary->year }}</p>
                    <span @class([$isSelected ? 'badge-blue' : 'badge-gold' => true, 'badge shrink-0'])>{{ $isSelected ? 'viewing' : 'draft' }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-[11px] text-gray-400 font-medium">
                    <span class="inline-flex items-center gap-1"><x-sc.icon name="people" class="w-3.5 h-3.5" />{{ $a->assessmentSummary->total_responses }} responses</span>
                    <span class="font-mono">{{ $a->metadata['model'] ?? 'gemini' }}</span>
                    <span class="inline-flex items-center gap-1"><x-sc.icon name="clock" class="w-3.5 h-3.5" />{{ $a->created_at->format('M j, g:i A') }}</span>
                </div>
                <p class="text-[12px] text-gray-600 leading-relaxed line-clamp-2 mt-2.5">{{ $a->summary ?? '—' }}</p>
                <p class="text-[11px] font-bold mt-3 {{ $isSelected ? 'text-gray-400' : 'text-lnu-800' }} group-hover:underline">{{ $isSelected ? 'Currently viewing — shown above' : 'Open in review workspace →' }}</p>
            </button>
        @empty
            <div class="sc-card p-7 !border-dashed col-span-2 text-center">
                <p class="text-[12.5px] text-gray-500 font-semibold">No drafts awaiting approval.</p>
                <p class="text-[11.5px] text-gray-400 mt-1">Select a community summary above and generate an analysis.</p>
            </div>
        @endforelse
    </div>
</section>

{{-- ===================== ANALYSIS HISTORY & PIPELINE STATES ===================== --}}
<section class="mt-7">
    <div class="reveal-item flex items-center justify-between mb-3">
        <h3 class="font-extrabold text-[15px] tracking-tight">Analysis History &amp; Pipeline States</h3>
        <span class="badge badge-gray">provenance retained per analysis</span>
    </div>
    <div class="reveal-item sc-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Community · Period</th><th>Generated</th><th>Model</th><th>Status</th><th>Approval</th><th>Approving Officer</th><th></th></tr></thead>
                <tbody>
                    @forelse ($analyses as $a)
                        <tr wire:key="ai-hist-{{ $a->id }}" @class(['bg-lnu-50/40' => $hero && $hero->id === $a->id])>
                            <td class="font-semibold text-charcoal whitespace-nowrap">{{ $a->assessmentSummary->community->name }} · Q{{ $a->assessmentSummary->quarter }} {{ $a->assessmentSummary->year }}
                                @if ($hero && $hero->id === $a->id)<span class="badge badge-blue !text-[10px] ml-1">viewing</span>@endif
                            </td>
                            <td class="text-gray-500 text-[12px] whitespace-nowrap">{{ $a->created_at->format('M j, Y · g:i A') }}</td>
                            <td class="text-gray-500 font-mono text-[11.5px] whitespace-nowrap">{{ $a->metadata['model'] ?? '—' }}</td>
                            <td>
                                @if ($a->status === 'completed')
                                    <span class="badge badge-green">completed</span>
                                @elseif ($a->status === 'failed')
                                    <span class="badge badge-red">failed</span>
                                @else
                                    <span class="badge badge-yellow"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 pulse-dot"></span>pending</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $a->approval_status === 'approved' ? 'badge-green' : ($a->approval_status === 'discarded' ? 'badge-gray' : 'badge-gold') }}">{{ ucfirst($a->approval_status) }}</span></td>
                            <td class="text-gray-600 text-[12px] font-semibold whitespace-nowrap">{{ $a->approver?->name ?? '—' }}</td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                @if ($a->status === 'failed')
                                    <span class="text-[11px] text-red-600 font-bold mr-1.5" title="{{ $a->error_message }}"><x-sc.icon name="alert" class="w-3 h-3" /> unavailable</span>
                                    <button wire:click="retry({{ $a->id }})" wire:target="retry({{ $a->id }})" wire:loading.attr="disabled" class="btn btn-outline !px-2.5 !py-1.5 text-[11px]">⟳ Retry</button>
                                @elseif ($a->status === 'pending')
                                    <span class="text-[11px] text-gray-400">generating…</span>
                                @elseif ($a->approval_status === 'approved')
                                    <span class="text-[11px] text-gray-400">citable in reports</span>
                                @else
                                    <span class="text-[11px] text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-gray-400 py-9">No analyses generated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="reveal-item mt-3 text-[11.5px] text-gray-400 leading-relaxed max-w-4xl">Pipeline states are first-class UI states: <span class="badge badge-gray !text-[10px]">pending</span> generating · <span class="badge badge-gold !text-[10px]">draft</span> awaiting review · <span class="badge badge-green !text-[10px]">approved</span> citable · <span class="badge badge-red !text-[10px]">failed</span> "analysis unavailable" — never a silent error. Manual retry is available to the Director.</p>
</section>

<footer class="mt-10 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
