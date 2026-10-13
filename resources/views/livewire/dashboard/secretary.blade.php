<div class="secretary-dashboard-page">
<section class="pt-6">
    <p class="secretary-dashboard-eyebrow mb-3">Validation workspace · {{ now()->format('l, F j, Y') }}</p>
    <div class="grid grid-cols-4 gap-4">
        <x-sc.color-card tone="gold" class="secretary-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <div class="flex items-center justify-between"><span class="secretary-dashboard-kpi-icon"><x-sc.icon name="clock" class="w-5 h-5" /></span><span class="secretary-dashboard-kpi-chip">needs review</span></div>
            <p class="secretary-dashboard-kpi-value mt-4">{{ $pending->count() }}</p>
            <p class="secretary-dashboard-kpi-label">Pending Validations</p>
        </x-sc.color-card>
        <x-sc.color-card tone="teal" class="secretary-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <div class="flex items-center justify-between"><span class="secretary-dashboard-kpi-icon"><x-sc.icon name="check" class="w-5 h-5" /></span><span class="secretary-dashboard-kpi-chip">cleared</span></div>
            <p class="secretary-dashboard-kpi-value mt-4">{{ $validatedCount }}</p>
            <p class="secretary-dashboard-kpi-label">Validated Submissions</p>
        </x-sc.color-card>
        <x-sc.color-card tone="indigo" class="secretary-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <div class="flex items-center justify-between"><span class="secretary-dashboard-kpi-icon"><x-sc.icon name="doc" class="w-5 h-5" /></span><span class="secretary-dashboard-kpi-chip">re-encode</span></div>
            <p class="secretary-dashboard-kpi-value mt-4">{{ $returnedCount }}</p>
            <p class="secretary-dashboard-kpi-label">Returned for Re-encoding</p>
        </x-sc.color-card>
        <x-sc.color-card tone="blue" class="secretary-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <div class="flex items-center justify-between"><span class="secretary-dashboard-kpi-icon"><x-sc.icon name="doc" class="w-5 h-5" /></span><span class="secretary-dashboard-kpi-chip">all imports</span></div>
            <p class="secretary-dashboard-kpi-value mt-4">{{ $importedCount }}</p>
            <p class="secretary-dashboard-kpi-label">XLSX Imports Archived</p>
        </x-sc.color-card>
    </div>
</section>

<section class="mt-5 grid grid-cols-3 gap-4">
    <div class="reveal-item sc-card p-0 overflow-hidden col-span-2">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="secretary-dashboard-section-title flex items-center gap-2"><x-sc.icon name="doc" class="w-[18px] h-[18px] text-lnu-700" /> Pending Validations</h3>
            <a href="{{ route('assessments.review') }}" class="secretary-dashboard-action">Open review queue →</a>
        </div>
        <table class="sc-table">
            <thead><tr><th>Community</th><th>Period</th><th>Respondent</th><th>Submitted By</th><th>Submitted</th></tr></thead>
            <tbody>
                @forelse ($pending->take(6) as $a)
                    <tr wire:key="dash-rv-{{ $a->id }}">
                        <td class="font-semibold text-charcoal">{{ $a->community->name }}</td>
                        <td><span class="badge badge-blue">Q{{ $a->quarter }} {{ $a->year }}</span></td>
                        <td class="text-gray-500">{{ $a->respondent_first_name }} {{ $a->respondent_last_name }}</td>
                        <td class="text-gray-500">{{ $a->uploader?->name ?? '—' }}</td>
                        <td class="text-gray-500">{{ $a->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-8">Queue is clear — no pending validations.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="space-y-4">
        <div class="reveal-item sc-card p-5">
            <h3 class="secretary-dashboard-section-title mb-3">Recent Summaries</h3>
            @forelse ($recentSummaries as $s)
                <div class="kv" wire:key="dash-sum-{{ $s->id }}">
                    <span class="k">{{ $s->community->name }} · Q{{ $s->quarter }}</span>
                    <span class="v">{{ $s->total_responses }} resp · {{ $s->last_calculated_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-[12px] text-gray-400 italic">No summaries computed yet.</p>
            @endforelse
        </div>
        <div class="border-l-4 border-lnu-800 bg-lnu-50 rounded-r-xl p-3.5">
            <p class="secretary-dashboard-callout-title">Your workflow</p>
            <p class="secretary-dashboard-callout-copy">Validated submissions feed the community needs summaries used in dashboards and AI analyses (Phase 5 — aggregates only).</p>
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
