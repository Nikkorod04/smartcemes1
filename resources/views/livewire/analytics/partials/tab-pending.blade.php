<div class="mt-4 space-y-4">
    @foreach ([
        'proposals' => ['title' => 'Proposals awaiting approval', 'route' => 'proposals.index', 'label' => 'Proposer'],
        'availability' => ['title' => 'Availability awaiting response', 'route' => 'availability.index', 'label' => 'Faculty'],
        'renderedHours' => ['title' => 'Rendered hours awaiting approval', 'route' => 'rendered-hours.index', 'label' => 'Faculty'],
        'programsEnding' => ['title' => 'Programs nearing deadline (≤14 days)', 'route' => 'programs.index', 'label' => 'Ends'],
        'aiAnalyses' => ['title' => 'AI analyses awaiting approval', 'route' => 'ai-analysis.index', 'label' => 'Model'],
        'objectivesAtRisk' => ['title' => 'Objectives at risk', 'route' => 'programs.index', 'label' => 'Target date'],
    ] as $key => $meta)
        <div class="sc-card p-0 overflow-hidden" wire:key="ax-pend-{{ $key }}">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-[13.5px]">{{ $meta['title'] }}</h3>
                <span class="badge badge-gray">{{ $pendingActions[$key]->count() }}</span>
            </div>
            <div class="px-5 py-3 space-y-2">
                @forelse ($pendingActions[$key] as $item)
                    <div class="flex items-center gap-3 text-[12.5px]" wire:key="ax-pend-item-{{ $key }}-{{ $loop->index }}">
                        @if ($key === 'proposals')
                            <span class="font-semibold text-charcoal truncate">{{ $item->title }}</span>
                            <span class="text-gray-400 ml-auto whitespace-nowrap">{{ $item->faculty->user->name }} · {{ $item->submitted_at?->diffForHumans() }}</span>
                            <a href="{{ route($meta['route']) }}" class="btn btn-ghost !px-2 !py-0.5 !text-[11px]">Review</a>
                        @elseif ($key === 'availability')
                            <span class="font-semibold text-charcoal truncate">{{ $item->activity?->title }}</span>
                            <span class="text-gray-400 ml-auto whitespace-nowrap">{{ $item->faculty->user->name }} · {{ $item->date->format('M j') }}</span>
                            <a href="{{ route($meta['route']) }}" class="btn btn-ghost !px-2 !py-0.5 !text-[11px]">View</a>
                        @elseif ($key === 'renderedHours')
                            <span class="font-semibold text-charcoal truncate">{{ $item->activity->title }}</span>
                            <span class="text-gray-400 ml-auto whitespace-nowrap">{{ $item->faculty->user->name }} · {{ number_format((float) $item->hours, 2) }} hrs</span>
                            <a href="{{ route($meta['route']) }}" class="btn btn-ghost !px-2 !py-0.5 !text-[11px]">Review</a>
                        @elseif ($key === 'programsEnding')
                            <span class="font-semibold text-charcoal truncate">{{ $item->code }} · {{ $item->title }}</span>
                            <span class="text-gray-400 ml-auto whitespace-nowrap">ends {{ $item->planned_end_date->format('M j, Y') }}</span>
                            <a href="{{ route($meta['route']) }}" class="btn btn-ghost !px-2 !py-0.5 !text-[11px]">Open</a>
                        @elseif ($key === 'aiAnalyses')
                            <span class="font-semibold text-charcoal truncate">{{ $item->assessmentSummary?->community?->name ?? 'Community summary' }} · Q{{ $item->assessmentSummary?->quarter }} {{ $item->assessmentSummary?->year }}</span>
                            <span class="text-gray-400 ml-auto whitespace-nowrap">{{ $item->metadata['model'] ?? 'gemini' }} · {{ $item->created_at->diffForHumans() }}</span>
                            <a href="{{ route($meta['route']) }}" class="btn btn-ghost !px-2 !py-0.5 !text-[11px]">Review</a>
                        @elseif ($key === 'objectivesAtRisk')
                            <span class="font-semibold text-charcoal truncate">{{ \Illuminate\Support\Str::limit($item->objective, 46) }}</span>
                            <span class="text-gray-400 ml-auto whitespace-nowrap">{{ $item->program?->code }} · due {{ $item->target_date?->format('M j, Y') }}</span>
                            <a href="{{ route('programs.show', $item->program) }}" class="btn btn-ghost !px-2 !py-0.5 !text-[11px]">Open</a>
                        @endif
                    </div>
                @empty
                    <p class="text-[12px] text-gray-400 italic py-1">Nothing here — all clear.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>