<div class="faculty-projects-page">
<section class="pt-6">
    <div class="faculty-projects-toolbar sc-card p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="faculty-projects-search flex-1 min-w-[280px] max-w-[520px]">
                <label class="faculty-projects-filter-label block mb-2" for="faculty-project-search">Search projects</label>
                <div class="sc-search">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input id="faculty-project-search" type="search" wire:model.live.debounce.300ms="search" aria-label="Search projects" class="input !w-full !min-w-0" placeholder="Title, code, college, or status…">
                    @if ($search !== '')
                        <button type="button" wire:click="$set('search', '')" aria-label="Clear project search" class="sc-search__clear"><x-sc.icon name="x" class="h-3.5 w-3.5" /></button>
                    @endif
                </div>
            </div>
            <div class="faculty-projects-sort min-w-[190px]">
                <label class="faculty-projects-filter-label block mb-2" for="faculty-project-sort">Sort projects</label>
                <select id="faculty-project-sort" wire:model.live="sort" class="input !w-full">
                    <option value="start_date">Start date</option>
                    <option value="updated">Recently updated</option>
                    <option value="title">Project title</option>
                    <option value="status">Status</option>
                </select>
            </div>
            <button type="button" wire:click="toggleDirection" class="btn btn-outline faculty-projects-direction" title="Toggle sort direction">
                <x-sc.icon name="chevron" class="w-4 h-4 {{ $direction === 'desc' ? 'rotate-180' : '' }}" />
                {{ $direction === 'asc' ? 'Ascending' : 'Descending' }}
            </button>
            <div class="faculty-view-switch" role="group" aria-label="Project display mode">
                <button type="button" wire:click="$set('viewMode', 'grid')" @class(['faculty-view-button', 'is-active' => $viewMode === 'grid']) aria-label="Grid view" title="Grid view"><x-sc.icon name="grid" class="w-4 h-4" /></button>
                <button type="button" wire:click="$set('viewMode', 'list')" @class(['faculty-view-button', 'is-active' => $viewMode === 'list']) aria-label="List view" title="List view"><x-sc.icon name="list" class="w-4 h-4" /></button>
            </div>
            @if ($search !== '' || $sort !== 'start_date' || $direction !== 'asc')
                <button type="button" wire:click="clearFilters" class="btn btn-ghost faculty-projects-clear">Clear</button>
            @endif
        </div>
    </div>
</section>

<section class="mt-4">
    @if ($programs->isEmpty())
        <div class="reveal-item sc-card px-5 py-12 text-center">
            <span class="mx-auto w-11 h-11 rounded-2xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="folder" class="w-5 h-5" /></span>
            <p class="text-[13.5px] font-semibold text-gray-500 mt-3">{{ $search !== '' ? 'No matching projects' : 'No projects assigned to you yet' }}</p>
            <p class="text-[12px] text-gray-400 mt-1">{{ $search !== '' ? 'Try a different title, code, college, or status.' : "Projects appear here once the Director's office assigns you as lead or to an activity." }}</p>
        </div>
    @elseif ($viewMode === 'list')
        <div class="faculty-project-list sc-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead><tr><th>Project</th><th>College</th><th>Dates</th><th>Role</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($programs as $p)
                            <tr wire:key="myprog-list-{{ $p->id }}" class="faculty-project-list-row">
                                <td>
                                    <a href="{{ route('projects.show', $p) }}" class="faculty-project-list-title">{{ $p->title }}</a>
                                    <p class="faculty-project-list-code">{{ $p->code }}</p>
                                </td>
                                <td><span class="faculty-project-list-meta">{{ $p->college?->code ?? $p->college?->name ?? '—' }}</span></td>
                                <td class="whitespace-nowrap"><span class="faculty-project-list-meta">{{ $p->planned_start_date?->format('M j, Y') ?? '—' }} – {{ $p->planned_end_date?->format('M j, Y') ?? '—' }}</span></td>
                                <td><span class="badge {{ $p->programLead?->user?->name === auth()->user()->name ? 'badge-gold' : 'badge-gray' }}">{{ $p->programLead?->user?->name === auth()->user()->name ? 'Lead' : 'Assigned' }}</span></td>
                                <td><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span></td>
                                <td class="!text-right"><a href="{{ route('projects.show', $p) }}" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Open →</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100">
                @include('livewire.partials.pagination', ['paginator' => $programs])
            </div>
        </div>
    @else
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($programs as $p)
                <a href="{{ route('projects.show', $p) }}" class="faculty-project-card reveal-item sc-card sc-card-hover flex flex-col overflow-hidden" wire:key="myprog-{{ $p->id }}">
                    <span class="faculty-project-accent"></span>
                    <div class="p-5 flex flex-col flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-1.5 mb-2">
                        <span class="badge badge-gold font-bold tracking-wide">{{ $p->code }}</span>
                        <span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span>
                    </div>
                    <p class="faculty-project-title">{{ $p->title }}</p>
                    <p class="faculty-project-date">{{ $p->planned_start_date->format('M j, Y') }} – {{ $p->planned_end_date->format('M j, Y') }}</p>
                    <div class="faculty-project-meta mt-4 flex items-center gap-2">
                        <x-sc.icon name="people" class="w-4 h-4 shrink-0" />
                        <span>{{ $p->programLead?->user?->name === auth()->user()->name ? 'You are the program lead' : 'Assigned faculty' }}</span>
                    </div>
                    <div class="faculty-project-footer mt-auto pt-4">Open project <span aria-hidden="true">→</span></div>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="faculty-project-pagination sc-card mt-4 px-5 py-4">
            @include('livewire.partials.pagination', ['paginator' => $programs])
        </div>
    @endif
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>

