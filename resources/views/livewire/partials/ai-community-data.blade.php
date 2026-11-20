{{--
    The exact aggregate snapshot the AI receives (D3): respondent profile,
    household/utilities, priority problems, interests, and key indicators.

    Shared by:
      - the community dossier, EXPANDED, so the Director can inspect the data
        before choosing to generate;
      - the analysis review page, COLLAPSED, where it is the D3 evidence surface.

    Variables: $aggRaw (array), $open (bool), $keySuffix (string).
--}}
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

<details class="sc-acc" @if ($open ?? false) open @endif wire:key="ai-agg-{{ $keySuffix }}">
    <summary>
        <x-sc.icon name="chart" class="w-4 h-4 text-lnu-700" />
        Community response data
        <span class="text-[11px] font-semibold text-gray-400">{{ $aggTotal }} respondent{{ $aggTotal === 1 ? '' : 's' }} &middot; {{ $aggShown }} datasets</span>
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
                            <p class="text-[11.5px] font-bold text-charcoal mb-1.5">{{ $aggLabel }} <span class="font-semibold text-gray-400">&middot; multi-select</span></p>
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

        <p class="mt-4 pt-3 border-t border-gray-50 text-[11px] text-gray-400 leading-relaxed">
            Counts and percentages over the {{ $aggTotal }} validated responses for
            {{ $aggRaw['community'] ?? $communityName ?? 'this community' }}
            &middot; Q{{ $aggRaw['quarter'] ?? $quarter ?? '?' }} {{ $aggRaw['year'] ?? $year ?? '' }}.
            These are the exact aggregate statistics transmitted to the model (D3) &mdash; no individual response left the server.
            Multi-select fields may sum above 100%.
        </p>
    </div>
</details>
