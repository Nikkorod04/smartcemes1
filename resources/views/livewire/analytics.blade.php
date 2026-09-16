<div>
<section class="pt-6">
    <div class="flex gap-1 bg-white rounded-xl border border-gray-100 p-1 w-max">
        @foreach (['overview' => 'Overview', 'performance' => 'Program Performance', 'budget' => 'Budget Utilization', 'reach' => 'Community Reach', 'faculty' => 'Faculty Contribution', 'pending' => 'Pending Actions'] as $key => $label)
            <button wire:click="setTab('{{ $key }}')" @class(['tab', 'on' => $tab === $key])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- OVERVIEW --}}
    @includeWhen($tab === 'overview', 'livewire.analytics.partials.tab-overview')
    @includeWhen($tab === 'performance', 'livewire.analytics.partials.tab-performance')
    @includeWhen($tab === 'budget', 'livewire.analytics.partials.tab-budget')
    @includeWhen($tab === 'reach', 'livewire.analytics.partials.tab-reach')
    @includeWhen($tab === 'faculty', 'livewire.analytics.partials.tab-faculty')
    @includeWhen($tab === 'pending', 'livewire.analytics.partials.tab-pending')
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
