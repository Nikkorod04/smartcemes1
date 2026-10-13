<div class="faculty-dashboard-page">
<section class="pt-6">
    <p class="faculty-page-hint mb-3">Welcome, {{ auth()->user()->name }} · your extension work at a glance</p>
    <div class="grid grid-cols-4 gap-4">
        <x-sc.color-card tone="teal" class="faculty-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <span class="faculty-kpi-icon"><x-sc.icon name="clock" class="w-4 h-4" /></span>
            <p class="faculty-kpi-label">Approved hours</p>
            <p class="faculty-kpi-value mt-3">{{ number_format($stats['approvedHours'], 2) }}</p>
            <p class="faculty-kpi-sub">toward the rendered-hours KPI</p>
        </x-sc.color-card>
        <x-sc.color-card tone="gold" class="faculty-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <span class="faculty-kpi-icon"><x-sc.icon name="clock" class="w-4 h-4" /></span>
            <p class="faculty-kpi-label">Pending hours</p>
            <p class="faculty-kpi-value mt-3">{{ number_format($stats['pendingHours'], 2) }}</p>
            <p class="faculty-kpi-sub">awaiting Director approval</p>
        </x-sc.color-card>
        <x-sc.color-card tone="blue" class="faculty-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <span class="faculty-kpi-icon"><x-sc.icon name="calendar" class="w-4 h-4" /></span>
            <p class="faculty-kpi-label">Activities</p>
            <p class="faculty-kpi-value mt-3">{{ $stats['activitiesCompleted'] }}<span class="faculty-kpi-secondary"> / {{ $stats['activitiesAssigned'] }}</span></p>
            <p class="faculty-kpi-sub">completed of assigned</p>
        </x-sc.color-card>
        <x-sc.color-card tone="indigo" class="faculty-dashboard-kpi reveal-item flex h-full flex-col p-5">
            <span class="faculty-kpi-icon"><x-sc.icon name="people" class="w-4 h-4" /></span>
            <p class="faculty-kpi-label">Projects</p>
            <p class="faculty-kpi-value mt-3">{{ $stats['programsLed'] }}<span class="faculty-kpi-secondary"> led</span></p>
            <p class="faculty-kpi-sub">assigned as lead or faculty</p>
        </x-sc.color-card>
    </div>
</section>

<section class="mt-5 grid grid-cols-3 gap-4">
    <div class="reveal-item sc-card p-0 overflow-hidden col-span-2">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="faculty-section-title flex items-center gap-2"><x-sc.icon name="calendar" class="w-[18px] h-[18px] text-lnu-700" /> My Activities</h3>
            <a href="{{ route('projects.my') }}" class="text-[12.5px] font-bold text-lnu-800 hover:text-lnu-600 transition">My Projects →</a>
        </div>
        <table class="sc-table">
            <thead><tr><th>Activity</th><th>Project</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($activities as $a)
                    <tr wire:key="dash-fact-{{ $a->id }}">
                        <td class="font-semibold text-charcoal">{{ $a->title }}</td>
                        <td class="text-gray-500">{{ $a->program?->code }}</td>
                        <td class="text-gray-500">{{ $a->planned_start_date->format('M j, Y') }}</td>
                        <td><span class="badge badge-{{ config('smartcemes.status_colors')[$a->status] ?? 'gray' }}">{{ ucfirst($a->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-400 py-8">No activities assigned yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="space-y-4">
        <div class="reveal-item sc-card p-5">
            <h3 class="faculty-section-title mb-3">Pending Availability <span class="badge badge-yellow">{{ $availability->count() }}</span></h3>
            @forelse ($availability->take(4) as $a)
                <div class="kv" wire:key="dash-fav-{{ $a->id }}">
                    <span class="k truncate">{{ \Illuminate\Support\Str::limit($a->activity?->title ?? '—', 24) }}</span>
                    <span class="v">{{ $a->date->format('M j') }} · <a href="{{ route('availability.index') }}" class="text-lnu-700 font-bold">respond →</a></span>
                </div>
            @empty
                <p class="text-[12px] text-gray-400 italic">No availability requests to answer.</p>
            @endforelse
        </div>
        <div class="reveal-item sc-card p-5">
            <h3 class="faculty-section-title mb-3">My Proposals</h3>
            @forelse ($myProposals as $p)
                <div class="kv" wire:key="dash-fpr-{{ $p->id }}">
                    <span class="k truncate">{{ \Illuminate\Support\Str::limit($p->title, 26) }}</span>
                    <span class="v"><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span></span>
                </div>
            @empty
                <p class="text-[12px] text-gray-400 italic">No proposals yet.</p>
            @endforelse
            <a href="{{ route('proposals.index') }}" class="btn btn-ghost mt-3 w-full !justify-center text-[12.5px]">My Proposals →</a>
        </div>
    </div>
</section>

<section class="mt-4">
    <div class="reveal-item sc-card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="faculty-section-title flex items-center gap-2"><x-sc.icon name="clock" class="w-[18px] h-[18px] text-gold-600" /> My Rendered Hours</h3>
            <a href="{{ route('rendered-hours.my') }}" class="text-[12.5px] font-bold text-lnu-800 hover:text-lnu-600 transition">Manage →</a>
        </div>
        <table class="sc-table">
            <thead><tr><th>Activity</th><th>Date</th><th class="!text-right">Hours</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($hours->take(5) as $e)
                    <tr wire:key="dash-frh-{{ $e->id }}">
                        <td class="font-semibold text-charcoal">{{ $e->activity->title }}</td>
                        <td class="text-gray-500">{{ $e->date->format('M j, Y') }}</td>
                        <td class="!text-right font-bold">{{ number_format((float) $e->hours, 2) }}</td>
                        <td><span class="badge badge-{{ config('smartcemes.status_colors')[$e->status] ?? 'gray' }}">{{ ucfirst($e->status) }}</span>@if ($e->isLocked()) <span class="badge badge-gold">locked</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-400 py-8">No rendered-hours entries yet — drafts appear when activities complete.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
