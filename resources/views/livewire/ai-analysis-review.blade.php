{{--
    The REVIEW surface for ONE analysis (2026-10-07 redesign).

    Moved out of `ai-analysis.blade.php`, which is now the queue. Two structural
    changes only:

      1. The narrative and the two action panels are FULL WIDTH / 2-up instead of
         being squeezed into a 1/3 rail. The panel bodies are collapsed into the
         house `sc-acc` accordion (app.css:290-305 — already used here), with
         High-priority interventions left OPEN so triage is never hidden.
      2. The action bar is state-aware: only a draft can be approved/discarded/
         regenerated; an approved or discarded analysis is read-only.

    ⚠️ The **Community response data** block below is carried over VERBATIM — it
    is the D3 evidence surface (the exact aggregates sent to the model) and must
    not be restructured. `$hero` is kept as the alias so the port stays faithful.
--}}
@php($hero = $analysis)
@php($state = $hero->queueState())
@php($stateLabel = match ($state) {
    'approved' => 'APPROVED — CITABLE IN REPORTS',
    'discarded' => 'DISCARDED',
    'failed' => 'ANALYSIS UNAVAILABLE',
    'pending' => 'GENERATING…',
    default => 'DRAFT — AWAITING REVIEW',
})

<div>
    {{-- ===================== BREADCRUMB =====================
         queue ↔ community ↔ review. Without the community link this page was a
         dead end: from a generation you could not reach the community's other
         periods. --}}
    <section class="pt-6 reveal-item flex items-center gap-3 flex-wrap">
        <a href="{{ route('ai-analysis.index') }}" class="btn btn-outline !px-3 !py-2 text-[12px]">
            <x-sc.icon name="chevron" class="w-3.5 h-3.5 rotate-90" /><span class="ml-1">Analysis queue</span>
        </a>
        <a href="{{ route('ai-analysis.community', ['community' => $hero->assessmentSummary->community]) }}"
           class="btn btn-outline !px-3 !py-2 text-[12px]">
            <x-sc.icon name="pin" class="w-3.5 h-3.5" /><span class="ml-1">{{ $hero->assessmentSummary->community->name }}</span>
        </a>
        @if ($siblings->count() > 1)
            <span class="badge badge-gray">generation {{ $hero->generationIndex() }} of {{ $hero->generationCount() }}</span>
            @if ($hero->isCurrent())
                <span class="badge badge-green">current</span>
            @elseif ($hero->isSuperseded())
                <span class="badge badge-gray">superseded</span>
            @endif
        @endif
    </section>

    {{-- ===================== AI HERO ===================== --}}
    <section class="mt-4 reveal-item" wire:key="ai-hero-{{ $hero->id }}">
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
                    <span class="badge badge-yellow !bg-gold-500/15 !text-gold-200 !border-gold-300/30">
                        @if (in_array($state, ['awaiting_review', 'pending'], true))
                            <span class="w-1.5 h-1.5 rounded-full bg-gold-400 pulse-dot"></span>
                        @endif
                        {{ $stateLabel }}
                    </span>
                </div>
                @if ($hero->approver)
                    <p class="mt-2 text-[11px] text-blue-100/70 font-medium">Approved · {{ $hero->approver->name }}</p>
                @endif
            </div>
        </div>
        <p class="mt-3 text-[11.5px] text-gray-400 leading-relaxed max-w-4xl">Only aggregate counts and percentages derived from validated summaries were sent to the language model. Raw responses never leave the server. Human approval is required before this analysis is cited institutionally.</p>
    </section>

    {{-- ===================== NARRATIVE (full width) ===================== --}}
    <section class="mt-4">
        <div class="reveal-item sc-card p-6" wire:key="ai-narrative-{{ $hero->id }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-[14px]">Analysis Narrative</h3>
                <span class="badge badge-gray">auto-generated draft</span>
            </div>
            <p class="flex items-center gap-2 text-[10.5px] font-extrabold uppercase tracking-[.14em] text-lnu-800"><span class="w-1.5 h-1.5 rounded-sm bg-gold-500"></span> Situation Overview</p>
            <p class="mt-2 leading-relaxed text-[14px] text-charcoal font-medium">{{ $hero->summary ?? '—' }}</p>

            {{-- Community response data (the exact aggregates sent to the model, D3).
                 CARRIED OVER VERBATIM — do not restructure. --}}
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
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Prompt</p>
                        <p class="text-[12.5px] font-semibold mt-0.5">{{ $hero->metadata['prompt_version'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Model</p>
                        <p class="text-[12.5px] font-mono font-semibold mt-0.5 truncate">{{ $hero->metadata['model'] ?? '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5" title="{{ $hero->metadata['confidence_basis'] ?? 'Derived data confidence — guidance only' }}">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Confidence</p>
                        <p class="text-[12.5px] font-semibold mt-0.5">{{ $hero->confidence_score !== null ? number_format((float) $hero->confidence_score, 2) : '—' }}</p>
                    </div>
                    {{-- The analysis's real source is the SUMMARY. `needs_assessment_id`
                         is NOT "submitted by" — it is the lowest-id respondent row
                         (one row per respondent, D11). Cite the summary + the count. --}}
                    <div class="rounded-lg bg-gray-50 border border-gray-100 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Source</p>
                        <p class="text-[12.5px] font-semibold mt-0.5">{{ $hero->assessmentSummary->total_responses }} validated responses{{ $contributors > 0 ? ' · '.$contributors.' contributor'.($contributors === 1 ? '' : 's') : '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== INTERVENTIONS | REFERRALS (2-up) ===================== --}}
    <section class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4 items-start">
        {{-- ===== CESO INTERVENTIONS (revision §7.1 / R6) =====
             The AI's recommendations are split into two hard-separated groups so a
             reader can never mistake an out-of-scope referral for something CESO
             will deliver. This group is green and rail-marked; the interagency
             referrals sit beside it with the amber rail and name the agency
             explicitly. The `tier-*` class names are internal styling only — the
             wording the Director reads is scope language, never "tier". --}}
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
            <ol class="space-y-2">
                @forelse ($hero->cesoInterventions() as $r)
                    @php($priority = $r['priority'] ?? '')
                    <li>
                        {{-- High stays OPEN: collapsing everything would hide the triage signal. --}}
                        <details class="sc-acc" @if ($priority === 'High') open @endif>
                            <summary>
                                <span class="acc-num">{{ $r['rank'] ?? '·' }}</span>
                                <span class="min-w-0 flex-1 truncate">{{ $r['title'] }}</span>
                                @if ($priority === 'High')
                                    <span class="badge badge-red shrink-0">High</span>
                                @elseif ($priority === 'Medium')
                                    <span class="badge badge-gold shrink-0">Medium</span>
                                @elseif ($priority !== '')
                                    <span class="badge badge-gray shrink-0">Low</span>
                                @endif
                                <svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </summary>
                            <div class="px-4 pb-4 pt-1 border-t border-gray-50">
                                <p class="text-[11.5px] text-gray-500 leading-relaxed">{{ $r['detail'] ?? '' }}</p>
                                @if (! empty($r['ceso_program']))
                                    <p class="text-[10.5px] text-gray-400 font-semibold mt-2">
                                        Thrust · {{ $r['ceso_program'] }}
                                    </p>
                                @endif
                            </div>
                        </details>
                    </li>
                @empty
                    <li class="text-[12px] text-gray-400 italic">No CESO-deliverable interventions returned.</li>
                @endforelse
            </ol>
        </div>

        {{-- ===== INTERAGENCY REFERRALS (§7.1 / D-R10) =====
             Rendered as its own group, never mixed into the list beside it. An empty
             result is the NORMAL case and is stated plainly rather than hidden,
             because "the model found nothing out of scope" is information the
             reviewer wants. This is ADVISORY ONLY — the system raises the flag and
             names the agency from the catalogue; it files no form, SO or paperwork.

             The collapsed row leads with the AGENCY (the actionable part) and clamps
             the `need` text to one line: `need` averages 55 characters and reads as a
             sentence, so a title-only collapse would still leave a 2-line heading. --}}
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
                <ul class="space-y-2">
                    @foreach ($hero->resolvedReferrals() as $referral)
                        @php($agency = $referral['agency'])
                        <li>
                            <details class="sc-acc">
                                <summary>
                                    @if ($agency !== null)
                                        <span class="badge badge-green shrink-0 font-mono">{{ $agency->agency_code }}</span>
                                    @else
                                        <span class="tier-badge tier-3 shrink-0">unverified</span>
                                    @endif
                                    <span class="min-w-0 flex-1 truncate">{{ $referral['need'] }}</span>
                                    <svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                                </summary>
                                <div class="px-4 pb-4 pt-1 border-t border-gray-50">
                                    @if ($referral['rationale'] !== '')
                                        <p class="text-[11.5px] text-gray-500 leading-relaxed">{{ $referral['rationale'] }}</p>
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
                                </div>
                            </details>
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
                    No out-of-scope needs were returned for this community — the model found nothing CESO cannot deliver.
                </p>
            @endif

            @if (! empty($hero->metadata['agency_catalogue_snapshot']))
                <div class="mt-4">
                    <details class="sc-acc">
                        <summary class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400 cursor-pointer select-none hover:text-lnu-800 transition">
                            Catalogue snapshot used for this analysis ({{ count($hero->metadata['agency_catalogue_snapshot']) }} agencies)
                            <svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="px-4 pb-4 pt-2 flex flex-wrap gap-1.5">
                            @foreach ($hero->metadata['agency_catalogue_snapshot'] as $snap)
                                <span class="badge badge-gray font-mono">{{ $snap['agency_code'] }}</span>
                            @endforeach
                            <p class="w-full text-[11px] text-gray-400 leading-relaxed mt-2">
                            The exact set of agencies the model was permitted to cite. Retiring an agency later does not alter this
                            record, so the referral above stays reproducible.
                            </p>
                        </div>
                    </details>
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== IDENTIFIED PRIORITY NEEDS ===================== --}}
    <section class="mt-4">
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
    </section>

    {{-- ===================== GENERATION HISTORY =====================
         Lineage is DERIVED (AssessmentAnalysis::isCurrent), never stored — so it
         cannot drift. A summary legitimately has several generations; this is the
         only place that says which one is authoritative. --}}
    @if ($siblings->count() > 1)
        <section class="mt-4">
            <div class="reveal-item sc-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-[14px]">Generation history</h3>
                    <span class="badge badge-gray">{{ $siblings->count() }} generations for this summary</span>
                </div>
                <ul class="space-y-2">
                    @foreach ($siblings as $gen)
                        <li class="flex items-center gap-3 flex-wrap px-3.5 py-2.5 rounded-xl border {{ $gen->id === $hero->id ? 'border-lnu-200 bg-lnu-50/40' : 'border-gray-100' }}">
                            <span class="acc-num shrink-0">{{ $loop->iteration }}</span>
                            <span class="text-[12.5px] font-semibold">gen {{ $loop->iteration }} of {{ $siblings->count() }}</span>
                            @if ($gen->id === $hero->id)<span class="badge badge-blue">viewing</span>@endif
                            @if ($gen->isCurrent())<span class="badge badge-green">current</span>@endif
                            @if ($gen->isSuperseded())<span class="badge badge-gray">superseded</span>@endif
                            @if ($gen->queueState() === 'failed')<span class="badge badge-red">failed</span>@endif
                            <span class="ml-auto text-[11.5px] text-gray-400">{{ $gen->created_at->format('M j, Y g:i A') }}</span>
                            <a href="{{ route('ai-analysis.show', $gen) }}" class="text-[12px] font-bold text-lnu-800 whitespace-nowrap">open →</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===================== ACTION BAR (state-aware, sticky) ===================== --}}
    <section class="sticky bottom-4 mt-4 z-20 reveal-item no-print">
        <div class="sc-card px-5 py-4 flex items-center gap-4 flex-wrap shadow-pop">
            <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="shield" class="w-5 h-5" /></span>
            <div class="min-w-0 flex-1">
                @if ($state === 'awaiting_review')
                    <p class="text-[13px] font-bold leading-snug">As CESO Director, your approval makes this analysis citable in reports.</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Approval is signed with your name and timestamp, then archived to the audit log.</p>
                @elseif ($state === 'approved')
                    <p class="text-[13px] font-bold leading-snug">Approved and citable.</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Signed by {{ $hero->approver?->name ?? 'the Director' }}{{ $hero->approved_at ? ' on '.$hero->approved_at->format('M j, Y g:i A') : '' }}. Regenerating creates a NEW draft and leaves this one intact.</p>
                @elseif ($state === 'discarded')
                    <p class="text-[13px] font-bold leading-snug">Discarded — retained for provenance, not citable.</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">The record stays in the queue. Regenerate to produce a fresh draft from the same summary.</p>
                @elseif ($state === 'failed')
                    <p class="text-[13px] font-bold leading-snug">Analysis unavailable — the generation failed.</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Retry is safe: it reuses the same summary aggregates (D3) and writes no partial draft.</p>
                @else
                    <p class="text-[13px] font-bold leading-snug">Generating…</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">The draft appears here as soon as the model responds.</p>
                @endif
            </div>
            <div class="ml-auto flex items-center gap-2 shrink-0">
                @if ($state === 'awaiting_review')
                    <button wire:click="regenerate" wire:loading.attr="disabled" wire:target="regenerate" class="btn btn-outline">
                        <span wire:loading.remove wire:target="regenerate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4" />Regenerate</span>
                        <span wire:loading wire:target="regenerate" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4 animate-spin" />Regenerating…</span>
                    </button>
                    <button wire:click="confirmDiscard" wire:loading.attr="disabled" class="btn btn-danger-soft">Discard</button>
                    <button wire:click="approve" wire:loading.attr="disabled" class="btn btn-primary !px-4 tracking-wide"><x-sc.icon name="shield" class="w-4 h-4" />APPROVE ANALYSIS</button>
                @elseif ($state === 'failed')
                    <button wire:click="retry" wire:loading.attr="disabled" class="btn btn-primary !px-4">
                        <span wire:loading.remove wire:target="retry" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4" />Retry</span>
                        <span wire:loading wire:target="retry" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4 animate-spin" />Retrying…</span>
                    </button>
                @elseif ($state === 'approved')
                    <span class="badge badge-green">Approved</span>
                    <button wire:click="regenerate" wire:loading.attr="disabled" class="btn btn-outline">Regenerate</button>
                @elseif ($state === 'discarded')
                    <span class="badge badge-gray">Discarded</span>
                    <button wire:click="regenerate" wire:loading.attr="disabled" class="btn btn-outline">Regenerate</button>
                @else
                    <span class="text-[11.5px] text-gray-400">generating…</span>
                @endif
            </div>
        </div>
    </section>

    {{-- Discard confirmation — SERVER-rendered @if, no Alpine visibility gate.
         A click-driven Livewire flag cannot fail the way a $wire.$watch bridge
         can (handoff §14: it fires only on CHANGE, never on an already-true flag). --}}
    @if ($confirmingDiscard)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="discard-analysis-title">
            <div class="fixed inset-0 sc-modal-backdrop" wire:click="cancelDiscard"></div>
            <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="bell" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">AI analysis workflow</p><h3 id="discard-analysis-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Discard this analysis?</h3><p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">Confirm the draft status change before it is recorded.</p></div></div><button type="button" wire:click="cancelDiscard" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close discard analysis dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"><div class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900"><x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" /><p>The draft for <b>{{ $hero->assessmentSummary->community->name }}</b> · Q{{ $hero->assessmentSummary->quarter }} {{ $hero->assessmentSummary->year }} will be marked discarded. It remains retained for provenance and can be regenerated later.</p></div></div>
                <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" wire:click="cancelDiscard" class="btn btn-ghost">Keep draft</button><button type="button" wire:click="discard" wire:loading.attr="disabled" wire:target="discard" class="btn btn-danger-soft min-w-[140px]"><span wire:loading.remove wire:target="discard">Discard analysis</span><span wire:loading wire:target="discard" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Discarding…</span></button></div></footer>
            </section>
        </div>
    @endif
</div>
