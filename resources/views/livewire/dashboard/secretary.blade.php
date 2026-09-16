<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Validation workspace · {{ now()->format('l, F j, Y') }}</p>
    <div class="grid grid-cols-4 gap-4">
        <div class="reveal-item sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><span class="w-10 h-10 rounded-xl bg-gold-50 text-gold-700 flex items-center justify-center"><x-sc.icon name="clock" class="w-5 h-5" /></span></div>
            <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none {{ $pending->count() ? 'text-gold-700' : '' }}">{{ $pending->count() }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Pending Validations</p>
        </div>
        <div class="reveal-item sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="check" class="w-5 h-5" /></span></div>
            <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none">{{ $validatedCount }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Validated Submissions</p>
        </div>
        <div class="reveal-item sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><span class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center"><x-sc.icon name="doc" class="w-5 h-5" /></span></div>
            <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none">{{ $returnedCount }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Returned for Re-encoding</p>
        </div>
        <div class="reveal-item sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="doc" class="w-5 h-5" /></span></div>
            <p class="mt-4 text-[26px] font-extrabold tracking-tight leading-none">{{ $importedCount }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">XLSX Imports Archived</p>
        </div>
    </div>
</section>

<section class="mt-5 grid grid-cols-3 gap-4">
    <div class="reveal-item sc-card p-0 overflow-hidden col-span-2">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="doc" class="w-[18px] h-[18px] text-lnu-700" /> Pending Validations</h3>
            <a href="{{ route('assessments.review') }}" class="text-[12.5px] font-bold text-lnu-800 hover:text-lnu-600 transition">Open review queue →</a>
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
            <h3 class="font-bold text-[14px] mb-3">Recent Summaries</h3>
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
            <p class="text-[12px] font-extrabold text-lnu-800">Your workflow</p>
            <p class="text-[11px] text-lnu-700/80 font-medium mt-1">Validated submissions feed the community needs summaries used in dashboards and AI analyses (Phase 5 — aggregates only).</p>
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>