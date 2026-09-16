{{--
    Design-system pagination for Livewire paginators (single root, §14).
    Uses wire:click (setPage/previousPage/nextPage) so page changes round-trip
    through Livewire instead of full reloads. Expects $paginator.
--}}
<div>
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        // Sliding window: first, current-1..current+1, last; gaps become ellipses.
        $pages = collect([1, $current - 1, $current, $current + 1, $last])
            ->filter(fn ($p) => $p >= 1 && $p <= $last)
            ->unique()
            ->sort()
            ->values();

        $items = [];
        $prev = null;
        foreach ($pages as $p) {
            if ($prev !== null && $p - $prev > 1) {
                $items[] = null;
            }
            $items[] = $p;
            $prev = $p;
        }
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-[11.5px] text-gray-400 font-medium">
            Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </p>

        @if ($paginator->hasPages())
            <nav class="flex items-center gap-1.5" role="navigation" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px] opacity-40 cursor-default">Prev</span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">Prev</button>
                @endif

                @foreach ($items as $item)
                    @if ($item === null)
                        <span class="px-1 text-gray-300 text-[12px] select-none">…</span>
                    @elseif ((int) $item === $current)
                        <span class="btn btn-primary !px-2.5 !py-1.5 !text-[11px] cursor-default">{{ $item }}</span>
                    @else
                        <button type="button" wire:click="setPage({{ (int) $item }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">{{ $item }}</button>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">Next</button>
                @else
                    <span class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px] opacity-40 cursor-default">Next</span>
                @endif
            </nav>
        @endif
    </div>
</div>
