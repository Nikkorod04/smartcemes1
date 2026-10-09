<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Welcome, {{ auth()->user()->name }} · your extension work at a glance</p>
    <div class="grid grid-cols-4 gap-4">
        <div class="reveal-item sc-card sc-card-hover p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Approved Hours</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none text-emerald-600">{{ number_format($stats['approvedHours'], 2) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">toward the rendered-hours KPI</p>
        </div>
        <div class="reveal-item sc-card sc-card-hover p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Pending Hours</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none text-gold-700">{{ number_format($stats['pendingHours'], 2) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">awaiting Director approval</p>
        </div>
        <div class="reveal-item sc-card sc-card-hover p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Activities</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $stats['activitiesCompleted'] }}<span class="text-[14px] text-gray-400 font-bold"> / {{ $stats['activitiesAssigned'] }}</span></p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">completed of assigned</p>
        </div>
        <div class="reveal-item sc-card sc-card-hover p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Projects</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $stats['programsLed'] }}<span class="text-[14px] text-gray-400 font-bold"> led</span></p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">assigned as lead or faculty</p>
        </div>
    </div>
</section>

<section class="mt-5 grid grid-cols-3 gap-4">
    <div class="reveal-item sc-card p-0 overflow-hidden col-span-2">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="calendar" class="w-[18px] h-[18px] text-lnu-700" /> My Activities</h3>
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
            <h3 class="font-bold text-[14px] mb-3">Pending Availability <span class="badge badge-yellow">{{ $availability->count() }}</span></h3>
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
            <h3 class="font-bold text-[14px] mb-3">My Proposals</h3>
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
            <h3 class="font-extrabold text-[15px] tracking-tight flex items-center gap-2"><x-sc.icon name="clock" class="w-[18px] h-[18px] text-gold-600" /> My Rendered Hours</h3>
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