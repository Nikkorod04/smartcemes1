<div>
    <section class="pt-6">
        <div class="sc-card rh-filter-panel p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="rh-filter-label">Event type</p>
                    <div class="chip-group mt-2">
                        @foreach ([
                            'all' => 'All events',
                            'created' => 'Created',
                            'updated' => 'Updated',
                            'status_transition' => 'Status changes',
                            'deleted' => 'Deleted',
                            'restored' => 'Restored',
                        ] as $value => $label)
                            <button type="button" wire:click="setEvent('{{ $value }}')" @class(['chip', 'on' => $event === $value])>{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-2 min-w-[min(100%,480px)] justify-end">
                    <div class="relative flex-1 min-w-[240px]">
                        <span class="rh-filter-label block mb-2">Search audit trail</span>
                        <div class="sc-search">
                            <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                            <input wire:model.live.debounce.300ms="search" aria-label="Search audit trail" class="input !w-full !min-w-0" placeholder="Event, description, subject, or user…">
                            @if ($search !== '')
                                <button type="button" wire:click="$set('search', '')" aria-label="Clear audit search" class="sc-search__clear">&times;</button>
                            @endif
                        </div>
                    </div>
                    <label class="min-w-[170px]">
                        <span class="rh-filter-label block mb-2">Subject</span>
                        <select wire:model.live="subject" aria-label="Filter audit subject" class="input !w-full">
                            <option value="all">All subjects</option>
                            @foreach ($subjectOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11.5px] text-gray-400 font-medium" aria-live="polite"><span wire:loading wire:target="search,event,subject,sortBy" class="rh-spinner mr-1.5"></span>{{ number_format($total) }} matching {{ \Illuminate\Support\Str::plural('entry', $total) }} · page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</p>
                <p class="text-[11px] text-gray-400">Read-only history · newest activity first</p>
            </div>
        </div>
    </section>

    <section class="mt-4">
        <div class="sc-card p-0 overflow-hidden">
            <div class="rh-queue-head px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="activity" class="w-[18px] h-[18px] text-lnu-700" /> Audit trail</h3>
                    <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Approvals, changes, imports, and record history</p>
                </div>
                <span class="badge badge-blue">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('entry', $total) }}</span>
            </div>
            <div class="overflow-x-auto transition-opacity" wire:loading.class="opacity-60" wire:target="search,event,subject,sortBy,setPage,previousPage,nextPage">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap"><button type="button" wire:click="sortBy('created_at')" wire:loading.attr="disabled" class="rh-sort-button">When <span class="rh-sort-arrow">{{ $sort === 'created_at' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th><button type="button" wire:click="sortBy('event')" wire:loading.attr="disabled" class="rh-sort-button">Event <span class="rh-sort-arrow">{{ $sort === 'event' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th class="min-w-[280px]"><button type="button" wire:click="sortBy('description')" wire:loading.attr="disabled" class="rh-sort-button">What happened <span class="rh-sort-arrow">{{ $sort === 'description' ? ($direction === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                            <th>Subject</th>
                            <th>Who</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr wire:key="audit-{{ $row->id }}" class="rh-row rh-row--{{ $row->event === 'deleted' ? 'rejected' : ($row->event === 'status_transition' ? 'pending' : 'approved') }}">
                                <td class="whitespace-nowrap align-top">
                                    <span class="block text-[12px] font-semibold text-charcoal">{{ $row->created_at?->format('M j, Y') ?? '—' }}</span>
                                    <span class="block text-[11px] text-gray-400 font-medium mt-1">{{ $row->created_at?->format('g:i A') }}</span>
                                </td>
                                <td class="align-top">
                                    <span class="badge badge-{{ $this->eventColor($row->event) }}"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>{{ ucwords(str_replace('_', ' ', $row->event ?? 'logged')) }}</span>
                                </td>
                                <td class="align-top">
                                    <span class="block text-[12.5px] text-charcoal leading-snug max-w-[420px]">{{ $row->description }}</span>
                                </td>
                                <td class="align-top">
                                    @if ($row->subject_type === null)
                                        <span class="text-[12px] text-gray-300">—</span>
                                    @else
                                        <span class="block text-[12px] text-gray-600 font-semibold">{{ $this->subjectLabel($row->subject_type) }}</span>
                                        @if ($row->subject === null)
                                            <span class="block text-[11px] text-red-400 font-medium mt-1">record deleted</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="align-top">
                                    <span class="inline-flex items-center gap-2 text-[12px] text-gray-600 font-semibold whitespace-nowrap"><span class="w-7 h-7 rounded-lg bg-lnu-50 text-lnu-800 flex items-center justify-center shrink-0"><x-sc.icon name="people" class="w-3.5 h-3.5" /></span>{{ $row->causer?->name ?? 'System' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-12">
                                    <span class="mx-auto w-11 h-11 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center"><x-sc.icon name="activity" class="w-5 h-5" /></span>
                                    <p class="text-[13px] font-semibold text-gray-500 mt-3">No matching audit entries</p>
                                    <p class="text-[11.5px] text-gray-400 mt-1">Try a different event, subject, or search term.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100">
                @include('livewire.partials.pagination', ['paginator' => $rows])
            </div>
        </div>
    </section>
</div>
