<div class="mt-4 space-y-4">
    <div class="grid grid-cols-4 gap-4">
        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Programs</p><span class="w-8 h-8 rounded-lg bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="folder" class="w-4 h-4" /></span></div>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $programRows->count() }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">{{ $programRows->where('model.status', 'ongoing')->count() }} ongoing · {{ $programRows->where('model.status', 'completed')->count() }} completed</p>
        </div>
        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Community Reach</p><span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="people" class="w-4 h-4" /></span></div>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ number_format($programRows->sum('reach')) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">distinct beneficiaries served</p>
        </div>
        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Budget Utilized</p><span class="w-8 h-8 rounded-lg bg-gold-50 text-gold-700 flex items-center justify-center"><x-sc.icon name="wallet" class="w-4 h-4" /></span></div>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">₱{{ number_format($programRows->sum(fn ($r) => $r->model->utilizedBudget())) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">{{ $programRows->filter(fn ($r) => $r->over)->count() }} over-allocated (D7)</p>
        </div>
        @php($avgKnowledgeGain = $programRows->filter(fn ($r) => $r->knowledgeGain !== null)->avg('knowledgeGain'))
        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between"><p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Avg Knowledge Gain</p><span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="chart" class="w-4 h-4" /></span></div>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $avgKnowledgeGain === null ? '—' : ($avgKnowledgeGain >= 0 ? '+' : '').number_format($avgKnowledgeGain, 1) }} pts</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">mean post − pre across programs</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="sc-card p-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-[14px]">Objective Status Breakdown</h3>
                <span class="text-[11.5px] text-gray-400 font-medium">all programs</span>
            </div>
            <div class="relative h-56"><canvas x-data x-init="if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy(); new Chart($refs.c, {{ json_encode(['type' => $objectiveChart['type'], 'data' => $objectiveChart['data'], 'options' => $objectiveChart['options']]) }})" x-ref="c"></canvas></div>
        </div>
        <div class="sc-card p-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-[14px]">Budget Utilization</h3>
                <span class="text-[11.5px] text-gray-400 font-medium">planned vs utilized</span>
            </div>
            <div class="relative h-56"><canvas x-data x-init="if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy(); new Chart($refs.c, {{ json_encode(['type' => $budgetChart['type'], 'data' => $budgetChart['data'], 'options' => $budgetChart['options']]) }})" x-ref="c"></canvas></div>
        </div>
    </div>
</div>