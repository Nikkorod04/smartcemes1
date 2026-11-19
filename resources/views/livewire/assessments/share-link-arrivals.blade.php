<div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-2.5"
     wire:poll.15s.visible
     aria-live="polite">
    <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500 pulse-dot" aria-hidden="true"></span>

    <p class="text-[11.5px] font-semibold text-charcoal">
        <span class="text-[15px] font-extrabold tabular-nums">{{ number_format($count) }}</span>
        {{ $count === 1 ? 'answer' : 'answers' }} received
    </p>

    <p class="text-[11px] text-gray-400">
        @if ($lastAt)
            · last {{ $lastAt->diffForHumans() }}
        @else
            · waiting for the first scan
        @endif
    </p>

    @if ($isSecretary && $count > 0)
        <a href="{{ route('assessments.review', ['source' => 'public']) }}"
           class="ml-auto whitespace-nowrap text-[11px] font-bold text-lnu-700 underline decoration-lnu-200 underline-offset-2 hover:text-lnu-900">
            Review in queue →
        </a>
    @endif
</div>
