<x-app-layout>
<div
    x-data="reportsWorkspace({
        year: {{ (int) $defaultYear }},
        projects: @js($projects),
        collegeNames: @js($collegeNames),
        programNames: @js($programNames),
        communityNames: @js($communityNames),
    })"
    x-init="init()"
    class="pt-6"
>
    <div class="grid items-start gap-5 xl:grid-cols-12">
        {{-- ===================== LEFT: CATALOG + SCOPE ===================== --}}
        <aside class="space-y-4 xl:col-span-3">
            <section class="sc-card p-5">
                <h1 class="text-[20px] font-extrabold tracking-tight text-charcoal">Reports</h1>
                <p class="mt-1 text-[11.5px] leading-relaxed text-gray-500">
                    Choose a report, set the scope, then print or save it as a PDF.
                </p>

                <div class="mt-4 space-y-2">
                    @foreach ([
                        ['key' => 'annual', 'label' => 'Annual Extension Performance', 'desc' => 'The annual institutional report: projects, reach, training delivery, faculty and budget.'],
                        ['key' => 'community-impact', 'label' => 'Community Partner Impact', 'desc' => 'Projects, people reached and validated assessment outcomes per community.'],
                        ['key' => 'rendered-hours', 'label' => 'Faculty Rendered Hours', 'desc' => 'Approved service-credit entries by faculty, activity and project.'],
                        ['key' => 'project-performance', 'label' => 'Project Performance', 'desc' => 'One project against its annual hours target and allocated budget.'],
                    ] as $r)
                        <button type="button"
                                x-on:click="selectReport('{{ $r['key'] }}')"
                                x-bind:class="report === '{{ $r['key'] }}'
                                    ? 'border-lnu-800 bg-lnu-800 text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-charcoal hover:border-lnu-300 hover:bg-lnu-50/40'"
                                class="w-full rounded-xl border px-3.5 py-3 text-left transition">
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-[12.5px] font-extrabold leading-snug">{{ $r['label'] }}</span>
                            </span>
                            <span class="mt-1 block text-[10.5px] leading-snug"
                                  x-bind:class="report === '{{ $r['key'] }}' ? 'text-white/70' : 'text-gray-400'">{{ $r['desc'] }}</span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="sc-card p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-[13px] font-extrabold tracking-tight">Scope</h2>
                    <button type="button" x-on:click="reset()" class="text-[10.5px] font-bold text-lnu-700 underline">Reset</button>
                </div>

                <div class="mt-3 space-y-3">
                    <label class="block" x-bind:class="report === 'project-performance' ? 'opacity-50' : ''">
                        <span class="mb-1 block text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Year</span>
                        <select x-model.number="year" class="input !py-2 text-[12px]">
                            @foreach ($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                        <span class="mt-1 block text-[10px] text-gray-400">Used by the annual, community and rendered-hours reports.</span>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10.5px] font-bold uppercase tracking-wide text-gray-400">College</span>
                        <select x-model="college" class="input !py-2 text-[12px]">
                            <option value="">All colleges</option>
                            @foreach ($colleges as $c)
                                <option value="{{ $c->id }}">{{ $c->short_name ?: $c->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Program</span>
                        <select x-model="program" class="input !py-2 text-[12px]">
                            <option value="">All programs</option>
                            @foreach ($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->title }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Community</span>
                        <select x-model="community" class="input !py-2 text-[12px]">
                            <option value="">All communities</option>
                            @foreach ($communities as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}@if ($c->municipality) — {{ $c->municipality }}@endif</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block" x-bind:class="report === 'project-performance' ? '' : 'opacity-60'">
                        <span class="mb-1 block text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Project</span>
                        <select x-model="projectId" class="input !py-2 text-[12px]">
                            <template x-for="p in filteredProjects" x-bind:key="p.id">
                                <option x-bind:value="p.id" x-text="p.code + ' — ' + p.title"></option>
                            </template>
                        </select>
                        <span class="mt-1 block text-[10px] text-gray-400">Used by Project Performance only.</span>
                        <span class="mt-1 block text-[10.5px] text-amber-700" style="display:none"
                              x-bind:style="filteredProjects.length ? 'display:none' : ''">
                            No project matches this scope.
                        </span>
                    </label>
                </div>
            </section>
        </aside>

        {{-- ===================== RIGHT: A4 PAPER ===================== --}}
        <section class="min-w-0 xl:col-span-9">
            <div class="sc-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-[13px] font-extrabold tracking-tight" x-text="selectedReport.label"></p>
                        <p class="mt-0.5 truncate text-[10.5px] font-medium text-gray-400" x-text="scopeLabel"></p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a x-bind:href="fullUrl" target="_blank" rel="noopener"
                           class="btn btn-outline !px-3 !py-2 !text-[11.5px]">
                            <x-sc.icon name="eye" class="w-3.5 h-3.5" />Open report
                        </a>
                        <a x-bind:href="downloadUrl"
                           class="btn btn-primary !px-3 !py-2 !text-[11.5px]">
                            <x-sc.icon name="doc" class="w-3.5 h-3.5" />Download PDF
                        </a>
                    </div>
                </div>

                <div class="bg-[#e9edf4] p-3 sm:p-4" x-bind:style="previewUrl ? '' : 'display:none'">
                    <iframe x-bind:src="previewUrl"
                            class="h-[min(80vh,900px)] w-full rounded-lg border border-gray-200 bg-white"
                            title="A4 PDF preview"></iframe>

                    <p class="mt-2 text-center text-[10.5px] font-medium text-gray-400">
                        A4 landscape PDF &middot; page numbers &middot; use <b>Download PDF</b> for the file
                    </p>
                </div>
            </div>

            <div class="sc-card mt-4 p-9 text-center" style="display:none"
                 x-bind:style="previewUrl ? 'display:none' : ''">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gold-50 text-gold-600">
                    <x-sc.icon name="doc" class="h-6 w-6" />
                </span>
                <p class="mt-3 text-[13px] font-extrabold">Choose a project</p>
                <p class="mx-auto mt-1 max-w-sm text-[11.5px] leading-relaxed text-gray-400">
                    Project Performance needs one project. Set the scope on the left and pick a project to see its A4 report.
                </p>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
