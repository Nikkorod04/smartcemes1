<div wire:poll.10s class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
    <span class="flex items-center gap-1.5 text-[11.5px] font-semibold text-gray-500">
        <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
        </span>
        Live
    </span>
    <span class="text-[11.5px] font-bold text-charcoal">{{ $count }} response{{ $count === 1 ? '' : 's' }}</span>
    @if ($average !== null)
        <span class="text-[11.5px] font-medium text-gray-500">· {{ $average }} average</span>
    @endif
    @if ($lastAt)
        <span class="ml-auto text-[10.5px] font-medium text-gray-400">Last {{ $lastAt->diffForHumans() }}</span>
    @endif
</div>
