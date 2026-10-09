<div>
<section class="pt-6">
    <div class="flex items-end justify-between mb-3">
        <p class="text-[13px] text-gray-400 font-medium">Pick a project to manage its beneficiaries and activity attendance (§5.4)</p>
        <p class="text-[12px] text-gray-400 font-medium">Enrollment is project-scoped · duplicates are warned, never merged</p>
    </div>
    <div class="reveal-item grid grid-cols-3 gap-4">
        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center"><x-sc.icon name="folder" /></span>
                <span class="badge badge-blue">all statuses</span>
            </div>
            <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none text-lnu-800">{{ $programs->count() }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Projects</p>
        </div>
        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="people" /></span>
                <span class="badge badge-green">enrolled</span>
            </div>
            <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none">{{ $totalEnrolled }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Beneficiary enrollments</p>
        </div>
        <div class="sc-card p-5 !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/40">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">What you can do</p>
            <p class="text-[12.5px] text-gray-600 mt-2 leading-relaxed">Enroll from the global registry, register new beneficiaries (dedup-checked), import from the official XLSX template, unenroll, and record per-activity attendance. Project structure, targets, and budget remain with the Director's office.</p>
        </div>
    </div>
</section>

<section class="mt-5 reveal-item">
    <div class="sc-card p-0 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <label class="sc-search flex-1 min-w-[220px] max-w-sm">
                <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="input !py-2 bg-gray-50 border-transparent focus:bg-white" placeholder="Search project by title or code…">
            </label>
            <span class="ml-auto text-[11.5px] text-gray-400 font-medium whitespace-nowrap">{{ $programs->count() }} project{{ $programs->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr>
                    <th>Project</th><th>Status</th><th>Communities</th><th>Beneficiaries</th><th>Activities</th><th class="!text-right">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($programs as $p)
                        <tr wire:key="benprog-{{ $p->id }}">
                            <td>
                                <p class="font-bold text-[13px] text-charcoal">{{ $p->title }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $p->code }} · {{ $p->programLead?->user?->name ?? 'No lead assigned' }}</p>
                            </td>
                            <td><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span></td>
                            <td class="text-gray-500 max-w-[220px] truncate">{{ $p->communities->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td><span class="badge badge-green">{{ $p->beneficiaries_count }} enrolled</span></td>
                            <td class="text-gray-500">{{ $p->activities_count }} scheduled</td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                <a href="{{ route('projects.show', [ 'project' => $p, 'tab' => 'beneficiaries']) }}" class="btn btn-primary !px-2.5 !py-1.5 !text-[11px]">Beneficiaries</a>
                                <a href="{{ route('projects.show', [ 'project' => $p, 'tab' => 'activities']) }}" class="btn btn-outline !px-2.5 !py-1.5 !text-[11px]">Attendance</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="!py-12 text-center">
                                @if (trim($search) !== '')
                                    <p class="text-[13px] font-semibold text-gray-400">No projects match your search.</p>
                                    <p class="text-[11.5px] text-gray-300 mt-1">Adjust the search term.</p>
                                @else
                                    <p class="text-[13px] font-semibold text-gray-400">No projects yet.</p>
                                    <p class="text-[11.5px] text-gray-300 mt-1">Projects created by the Director's office appear here.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
