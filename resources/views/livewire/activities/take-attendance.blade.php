<div class="attendance-roster-page">
    <section class="pt-6">
        <a href="{{ route('projects.show', $project) }}?tab=activities"
           class="inline-flex items-center gap-1 text-[11.5px] font-bold text-lnu-700 transition hover:text-lnu-900">
            ← Back to {{ $project->title }}
        </a>

        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">
                    {{ $project->code }} · Attendance
                </p>
                <h1 class="secretary-form-title">Take attendance</h1>
                <p class="secretary-form-subtitle">
                    {{ $activity->title }} ·
                    {{ $activity->planned_start_date->format('M j, Y') }} ·
                    {{ $activity->venue ?: 'Venue not set' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach (['present', 'late', 'absent', 'excused'] as $status)
                    <span class="badge {{ $status === 'present' ? 'badge-green' : ($status === 'late' ? 'badge-gold' : ($status === 'absent' ? 'badge-red' : 'badge-gray')) }}">
                        {{ $counts[$status] ?? 0 }} {{ ucfirst($status) }}
                    </span>
                @endforeach
                <span class="badge badge-gray">{{ $counts['unrecorded'] ?? 0 }} unrecorded</span>
            </div>
        </div>
    </section>

    <section class="mt-4">
        <div class="sc-card p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-2">
                <label class="sc-search min-w-[220px] flex-1 sm:max-w-sm">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="input !py-2 bg-gray-50 border-transparent focus:bg-white"
                           placeholder="Search name or barangay…">
                </label>

                <button type="button" wire:click="markAllPresent" class="btn btn-primary !py-2 !px-3 text-[12px]">
                    <x-sc.icon name="check" class="h-4 w-4" /> Mark all present
                </button>
                <button type="button" wire:click="clearAll" class="btn btn-outline !py-2 !px-3 text-[12px]">
                    Clear all
                </button>

                <span class="ml-auto text-[11.5px] font-medium text-gray-400">
                    {{ $enrolledTotal }} enrolled
                </span>
            </div>

            <p class="mt-2 text-[11px] leading-relaxed text-gray-400">
                Tap a status to set it. Tap the active status again to clear it. Present and late count toward the
                activity’s trainees and training hours.
            </p>
        </div>
    </section>

    <section class="mt-4">
        <div class="sc-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table min-w-[720px]">
                    <thead>
                        <tr>
                            <th>Beneficiary</th>
                            <th>Barangay</th>
                            <th>Category</th>
                            <th class="!text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($beneficiaries as $beneficiary)
                            @php($current = $statuses[$beneficiary->id] ?? '')
                            <tr wire:key="attendance-{{ $beneficiary->id }}">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="avatar w-7 h-7 text-[10px]">{{ \App\View\Components\Initials::for($beneficiary->fullName()) }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-[12.5px]">{{ $beneficiary->fullName() }}</p>
                                            <p class="mt-0.5 text-[10.5px] text-gray-400">
                                                {{ $beneficiary->age ?? '—' }} · {{ $beneficiary->gender ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-[12px] text-gray-500">{{ $beneficiary->community?->name ?? ($beneficiary->barangay ?: '—') }}</td>
                                <td><span class="badge badge-gray">{{ $beneficiary->beneficiary_category }}</span></td>
                                <td class="!text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-1.5">
                                        @foreach (['present', 'late', 'absent', 'excused'] as $status)
                                            @php($isActive = $current === $status)
                                            <button type="button"
                                                    wire:click="toggleStatus({{ $beneficiary->id }}, '{{ $status }}')"
                                                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                                    @class([
                                                        'rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition',
                                                        'border-emerald-600 bg-emerald-600 text-white shadow-sm' => $isActive && $status === 'present',
                                                        'border-amber-500 bg-amber-500 text-white shadow-sm' => $isActive && $status === 'late',
                                                        'border-red-600 bg-red-600 text-white shadow-sm' => $isActive && $status === 'absent',
                                                        'border-slate-500 bg-slate-500 text-white shadow-sm' => $isActive && $status === 'excused',
                                                        'border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50' => ! $isActive && $status === 'present',
                                                        'border-amber-200 bg-white text-amber-700 hover:bg-amber-50' => ! $isActive && $status === 'late',
                                                        'border-red-200 bg-white text-red-700 hover:bg-red-50' => ! $isActive && $status === 'absent',
                                                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => ! $isActive && $status === 'excused',
                                                    ])>
                                                {{ ucfirst($status) }}
                                            </button>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="!py-12 text-center">
                                    @if ($enrolledTotal === 0)
                                        <p class="text-[13px] font-semibold text-gray-400">No beneficiaries are listed for this project yet.</p>
                                        <a href="{{ route('projects.show', $project) }}?tab=beneficiaries"
                                           class="mt-2 inline-block text-[11.5px] font-bold text-lnu-700 underline">
                                            Add beneficiaries first
                                        </a>
                                    @else
                                        <p class="text-[13px] font-semibold text-gray-400">No beneficiary matches that search.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="sticky bottom-3 z-10 mt-4">
        <div class="sc-card flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <p class="text-[11.5px] font-medium text-gray-500">
                <span class="font-bold text-charcoal">{{ $counts['present'] ?? 0 }}</span> present ·
                <span class="font-bold text-charcoal">{{ $counts['late'] ?? 0 }}</span> late ·
                <span class="font-bold text-charcoal">{{ $counts['absent'] ?? 0 }}</span> absent ·
                <span class="font-bold text-charcoal">{{ $counts['excused'] ?? 0 }}</span> excused
            </p>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="btn btn-primary min-w-[160px]">
                <span wire:loading.remove wire:target="save">Save attendance</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
            </button>
        </div>
    </div>
</div>
