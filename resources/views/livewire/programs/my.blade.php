<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Programs you lead or are assigned to as faculty</p>
</section>

<section class="mt-2">
    @if ($programs->isEmpty())
        <div class="reveal-item sc-card px-5 py-12 text-center">
            <p class="text-[13.5px] font-semibold text-gray-500">No programs assigned to you yet</p>
            <p class="text-[12px] text-gray-400 mt-1">Programs appear here once the Director's office assigns you as lead or to an activity.</p>
        </div>
    @else
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($programs as $p)
                <a href="{{ route('programs.show', $p) }}" class="reveal-item sc-card sc-card-hover p-5 flex flex-col" wire:key="myprog-{{ $p->id }}">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                        <span class="badge badge-gold font-bold tracking-wide">{{ $p->code }}</span>
                        <span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span>
                    </div>
                    <p class="font-bold text-[14px] leading-snug">{{ $p->title }}</p>
                    <p class="text-[11.5px] text-gray-400 mt-0.5">{{ $p->planned_start_date->format('M j, Y') }} – {{ $p->planned_end_date->format('M j, Y') }}</p>
                    <div class="mt-3 flex items-center gap-2 text-[11.5px] text-gray-500">
                        <x-sc.icon name="people" class="w-4 h-4 shrink-0" />
                        <span>{{ $p->programLead?->user?->name === auth()->user()->name ? 'You are the program lead' : 'Assigned faculty' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>

