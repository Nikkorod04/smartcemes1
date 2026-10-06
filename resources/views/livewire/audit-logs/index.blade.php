{{--
    Audit Logs (owner request 2026-09-25).

    A PLAIN reverse-chronological list of the Spatie activity log — the four-row
    "Recent Activity" panel that used to sit on the dashboard, given a page of its
    own and the room to show everything.

    Read-only by design: no row here can be edited or deleted.
--}}
<div>
    <section class="pt-6 reveal-item">
        <div class="flex items-end justify-between gap-3 flex-wrap">
            <div>
                <p class="hub-eyebrow">Intelligence &amp; Reports</p>
                <h3 class="text-[15px] font-extrabold tracking-tight mt-1">Audit Logs</h3>
                <p class="text-[12.5px] text-gray-400 font-medium mt-1 max-w-3xl">
                    Every approval, rejection, deletion, status change and record import the system
                    recorded, newest first.
                    <span class="text-gray-500 font-semibold">{{ number_format($total) }}</span>
                    {{ \Illuminate\Support\Str::plural('entry', $total) }}.
                </p>
            </div>
        </div>

        <div class="mt-4 sc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">When</th>
                            <th>Event</th>
                            <th class="min-w-[280px]">What happened</th>
                            <th>Subject</th>
                            <th>Who</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr wire:key="audit-{{ $row->id }}">
                                <td class="whitespace-nowrap align-top">
                                    <span class="block text-[12px] font-semibold text-charcoal">
                                        {{ $row->created_at?->format('M j, Y') ?? '—' }}
                                    </span>
                                    <span class="block text-[11px] text-gray-400 font-medium">
                                        {{ $row->created_at?->format('g:i A') }}
                                    </span>
                                </td>
                                <td class="align-top">
                                    <span class="badge badge-{{ $this->eventColor($row->event) }}">
                                        {{ $row->event ?? 'logged' }}
                                    </span>
                                </td>
                                <td class="align-top">
                                    <span class="block text-[12.5px] text-charcoal leading-snug">{{ $row->description }}</span>
                                </td>
                                <td class="align-top">
                                    @if ($row->subject_type === null)
                                        <span class="text-[12px] text-gray-300">—</span>
                                    @else
                                        <span class="block text-[12px] text-gray-500 font-medium">
                                            {{ $this->subjectLabel($row->subject_type) }}
                                        </span>
                                        @if ($row->subject === null)
                                            <span class="block text-[11px] text-gray-300">deleted</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="align-top">
                                    <span class="text-[12px] text-gray-500 font-medium">
                                        {{ $row->causer?->name ?? 'System' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10">
                                    <span class="block text-[12.5px] text-gray-400 italic">Nothing has been logged yet.</span>
                                    <span class="block text-[12px] text-gray-400 font-medium mt-1">
                                        Approvals, deletions and status changes appear here as they happen.
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($rows->hasPages())
            <div class="mt-4">
                @include('livewire.partials.pagination', ['paginator' => $rows])
            </div>
        @endif
    </section>
</div>
