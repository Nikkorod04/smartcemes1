<x-app-layout>
<div x-data="{ previewOpen: false, previewUrl: '', previewTitle: '' }" @keydown.escape.window="previewOpen = false">
    <section class="pt-6 grid gap-4 md:grid-cols-2">
        <article class="sc-card group relative overflow-hidden p-5 transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-pop">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-lnu-50/80"></div>
            <div class="relative flex h-full flex-col justify-between gap-6">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-lnu-50 text-lnu-800"><x-sc.icon name="doc" class="w-5 h-5" /></span>
                        <span class="badge badge-blue">Annual</span>
                    </div>
                    <h2 class="font-extrabold text-[15px] tracking-tight mt-4">Annual Extension Performance</h2>
                    <p class="text-[12px] text-gray-500 leading-relaxed mt-1.5">Projects, trainees reached, faculty participation, training delivery, and budget utilization for the reporting year.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="previewOpen = true; previewUrl = @js(route('reports.annual')); previewTitle = 'Annual Extension Performance Report'" class="btn btn-outline !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="eye" class="w-3.5 h-3.5" />Preview</button>
                    <a href="{{ route('reports.annual') }}" target="_blank" rel="noopener" class="btn btn-primary !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="link" class="w-3.5 h-3.5" />Open report</a>
                </div>
            </div>
        </article>

        <article class="sc-card group relative overflow-hidden p-5 transition duration-200 hover:-translate-y-0.5 hover:border-gold-200 hover:shadow-pop">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-gold-50/80"></div>
            <div class="relative flex h-full flex-col justify-between gap-6">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gold-50 text-gold-700"><x-sc.icon name="clock" class="w-5 h-5" /></span>
                        <span class="badge badge-gold">Approved only</span>
                    </div>
                    <h2 class="font-extrabold text-[15px] tracking-tight mt-4">Faculty Rendered Hours</h2>
                    <p class="text-[12px] text-gray-500 leading-relaxed mt-1.5">Approved service-credit entries grouped by faculty, activity, project, date, and rendered hours.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="previewOpen = true; previewUrl = @js(route('reports.rendered-hours')); previewTitle = 'Faculty Rendered Hours Report'" class="btn btn-outline !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="eye" class="w-3.5 h-3.5" />Preview</button>
                    <a href="{{ route('reports.rendered-hours') }}" target="_blank" rel="noopener" class="btn btn-primary !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="link" class="w-3.5 h-3.5" />Open report</a>
                </div>
            </div>
        </article>

        <article class="sc-card group relative overflow-hidden p-5 transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-pop">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-emerald-50/80"></div>
            <div class="relative flex h-full flex-col justify-between gap-6">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><x-sc.icon name="people" class="w-5 h-5" /></span>
                        <span class="badge badge-green">Community</span>
                    </div>
                    <h2 class="font-extrabold text-[15px] tracking-tight mt-4">Community Partner Impact</h2>
                    <p class="text-[12px] text-gray-500 leading-relaxed mt-1.5">Projects per community, people served, and the latest validated assessment outcomes and recommendations.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="previewOpen = true; previewUrl = @js(route('reports.community-impact')); previewTitle = 'Community Partner Impact Summary'" class="btn btn-outline !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="eye" class="w-3.5 h-3.5" />Preview</button>
                    <a href="{{ route('reports.community-impact') }}" target="_blank" rel="noopener" class="btn btn-primary !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="link" class="w-3.5 h-3.5" />Open report</a>
                </div>
            </div>
        </article>

        <article class="sc-card group relative overflow-hidden p-5 transition duration-200 hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-pop">
            <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-violet-50/80"></div>
            <div class="relative">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700"><x-sc.icon name="chart" class="w-5 h-5" /></span>
                        <div><h2 class="font-extrabold text-[15px] tracking-tight">Project Performance</h2><p class="text-[11px] text-gray-400 mt-0.5">Select a project to inspect</p></div>
                    </div>
                    <span class="badge badge-gray">{{ $programs->count() }} projects</span>
                </div>
                <div class="relative mt-5 grid gap-2 sm:grid-cols-2">
                    @forelse ($programs as $p)
                        <div class="flex min-w-0 items-center justify-between gap-2 rounded-xl border border-gray-100 bg-gray-50/60 px-3 py-2.5 transition hover:border-lnu-200 hover:bg-lnu-50/50">
                            <div class="min-w-0"><p class="text-[11px] font-bold text-lnu-800">{{ $p->code }}</p><p class="truncate text-[12px] font-semibold text-charcoal" title="{{ $p->title }}">{{ $p->title }}</p></div>
                            <button type="button" @click="previewOpen = true; previewUrl = @js(route('reports.results-framework', $p)); previewTitle = @js('Project Performance · '.$p->code)" class="btn btn-ghost !shrink-0 !px-2 !py-1.5 !text-[11px]">Preview</button>
                        </div>
                    @empty
                        <p class="text-[12px] text-gray-400 italic sm:col-span-2">No projects are available for reporting yet.</p>
                    @endforelse
                </div>
                @if ($programs->isNotEmpty())
                    <p class="relative text-[11px] text-gray-400 mt-3">Each project opens its own performance report with project-level metrics and activity detail.</p>
                @endif
            </div>
        </article>
    </section>

    <footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
        SmartCEMES · Community Extension Services Office · Leyte Normal University
    </footer>

    <div x-cloak x-show="previewOpen" x-transition.opacity.duration.150ms class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="report-preview-title">
        <div class="absolute inset-0 sc-modal-backdrop" @click="previewOpen = false"></div>
        <section class="relative flex h-[min(92vh,920px)] w-full max-w-[1180px] flex-col overflow-hidden rounded-2xl bg-white shadow-pop" @click.stop>
            <header class="sc-modal-header !shrink-0 !border-b-0 !px-4 !py-3 sm:!px-5">
                <div class="min-w-0"><p class="rh-filter-label">Print-ready preview</p><h2 id="report-preview-title" class="truncate sc-modal-title !text-[15px]" x-text="previewTitle"></h2></div>
                <div class="flex shrink-0 items-center gap-2">
                    <a :href="previewUrl" target="_blank" rel="noopener" class="btn btn-primary !px-3 !py-2 !text-[11.5px]"><x-sc.icon name="link" class="w-3.5 h-3.5" />Open full report</a>
                    <button type="button" @click="previewOpen = false" aria-label="Close report preview" class="sc-modal-close"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>
            </header>
            <div class="min-h-0 flex-1 bg-gray-100 p-2 sm:p-4">
                <iframe :src="previewUrl" title="Report preview" class="h-full w-full rounded-xl border border-gray-200 bg-white shadow-sm"></iframe>
            </div>
            <p class="shrink-0 border-t border-gray-100 px-4 py-2 text-center text-[11px] text-gray-400">Use <b>Print report</b> in the full report, then choose <b>Save as PDF</b> in your browser.</p>
        </section>
    </div>
</div>
</x-app-layout>
