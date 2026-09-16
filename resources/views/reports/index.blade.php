<x-app-layout>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Print-optimized institutional reports · figures derive exclusively from the 8.6 KPI dictionary · letterhead + signatory blocks included</p>
</section>

<section class="mt-2 grid grid-cols-2 gap-4">
    <div class="reveal-item sc-card p-5 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-[14px]">Annual Extension Performance Report</h3>
            <p class="text-[12px] text-gray-400 mt-1">Sections I–VII: programs per year, distinct beneficiaries served, faculty participation, budget utilization by project.</p>
        </div>
        <a href="{{ route('reports.annual') }}" class="btn btn-primary mt-3"><x-sc.icon name="doc" class="w-4 h-4" />Open &amp; print</a>
    </div>
    <div class="reveal-item sc-card p-5 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-[14px]">Program Results Framework</h3>
            <p class="text-[12px] text-gray-400 mt-1">Per program: objective statement, baseline → target → actual, status, evidence notes.</p>
        </div>
        <div class="mt-3 space-y-1.5 max-h-40 overflow-y-auto pr-1">
            @forelse ($programs as $p)
                <a href="{{ route('reports.results-framework', $p) }}" class="block text-[12px] font-semibold text-lnu-700 hover:text-lnu-500 truncate">{{ $p->code }} · {{ $p->title }}</a>
            @empty
                <p class="text-[12px] text-gray-400 italic">No programs.</p>
            @endforelse
        </div>
    </div>
    <div class="reveal-item sc-card p-5 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-[14px]">Faculty Rendered Hours</h3>
            <p class="text-[12px] text-gray-400 mt-1">Per semester, by activity and program (approved entries only).</p>
        </div>
        <a href="{{ route('reports.rendered-hours') }}" class="btn btn-primary mt-3"><x-sc.icon name="clock" class="w-4 h-4" />Open &amp; print</a>
    </div>
    <div class="reveal-item sc-card p-5 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-[14px]">Community Partner Impact Summary</h3>
            <p class="text-[12px] text-gray-400 mt-1">Programs per community, distinct served, latest assessment outcomes and approved recommendations.</p>
        </div>
        <a href="{{ route('reports.community-impact') }}" class="btn btn-primary mt-3"><x-sc.icon name="people" class="w-4 h-4" />Open &amp; print</a>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</x-app-layout>