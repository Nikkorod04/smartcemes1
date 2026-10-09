<div class="mt-4">
    <div class="sc-card p-0 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <h3 class="font-bold text-[14px]">Project Activities</h3>
                <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Planned dates must fall within <span class="font-semibold">{{ $program->planned_start_date->format('M j, Y') }} – {{ $program->planned_end_date->format('M j, Y') }}</span></p>
            </div>
            @if ($canManage)
                <button wire:click="openActivityForm(null)" class="btn btn-primary"><x-sc.icon name="calendar" class="w-4 h-4" /> Add Activity</button>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Activity</th><th>Date</th><th>Time</th><th>Venue</th><th class="!text-right">Trainors</th><th class="!text-right">Trainees</th><th class="!text-right">Days</th><th class="!text-right">Training Hrs</th><th>Status</th><th class="!text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse ($activities as $a)
                        @php
                            $assigned = $a->faculty->map(fn ($f) => $f->user->name)->all();
                            $hasConflict = false;
                            // R4: every figure below comes from TrainingHoursService,
                            // computed once per render in Hub::render().
                            $aTrainors = $trainorsByActivity[$a->id] ?? 0;
                            $aTrainees = $traineesByActivity[$a->id] ?? 0;
                            $aSource = $traineeSourceByActivity[$a->id] ?? 'none';
                            $aHours = $trainingHoursByActivity[$a->id] ?? 0;
                        @endphp
                        <tr wire:key="act-{{ $a->id }}">
                            <td>
                                <p class="font-semibold text-charcoal">{{ $a->title }}</p>
                                <p class="text-[11px] text-gray-400">{{ $assigned ? implode(', ', $assigned) : 'No faculty assigned' }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-1">
                                    {{-- R-Q1: the trainee figure always shows WHERE it came from. --}}
                                    <span class="badge {{ $aSource === 'attendance' ? 'badge-blue' : ($aSource === 'manual' ? 'badge-gold' : 'badge-gray') }} !text-[10px]">
                                        trainee source · {{ $traineeSourceLabels[$aSource] ?? $aSource }}
                                    </span>
                                    @if (($attendancesByActivity[$a->id] ?? 0) > 0)
                                        <span class="badge badge-gray !text-[10px]">{{ $attendancesByActivity[$a->id] }} attendance</span>
                                    @else
                                        <span class="badge badge-gray !text-[10px]">No attendance yet</span>
                                    @endif
                                    @if ($a->pre_assessment_score !== null || $a->post_assessment_score !== null)
                                        <span class="badge badge-blue !text-[10px]">Pre {{ $a->pre_assessment_score === null ? '—' : number_format((float) $a->pre_assessment_score, 1) }} → Post {{ $a->post_assessment_score === null ? '—' : number_format((float) $a->post_assessment_score, 1) }}</span>
                                    @endif
                                    @if ($a->satisfaction_rating !== null)
                                        <span class="badge badge-gold !text-[10px]">{{ number_format((float) $a->satisfaction_rating, 1) }}/5 satisfaction</span>
                                    @endif
                                </p>
                            </td>
                            <td>{{ $a->planned_start_date->format('M j, Y') }}{{ $a->planned_end_date->format('M j, Y') !== $a->planned_start_date->format('M j, Y') ? ' – '.$a->planned_end_date->format('M j, Y') : '' }}</td>
                            <td class="whitespace-nowrap">{{ $a->start_time->format('g:i A') }} – {{ $a->end_time->format('g:i A') }}</td>
                            <td class="text-gray-500">{{ $a->venue ?? '—' }}</td>
                            <td class="!text-right font-semibold">{{ $aTrainors }}</td>
                            <td class="!text-right font-semibold">{{ $aTrainees > 0 ? $aTrainees : '—' }}</td>
                            <td class="!text-right text-gray-500">{{ (new \App\Services\TrainingHoursService)->formatDays($a->no_of_days ?? 1.0) }}</td>
                            <td class="!text-right font-bold text-lnu-700">{{ number_format($aHours) }}</td>
                            <td><span class="badge badge-{{ config('smartcemes.status_colors')[$a->status] ?? 'gray' }}">{{ ucfirst($a->status) }}</span></td>
                            <td class="!text-right row-actions whitespace-nowrap">
                                <button wire:click="openRecords({{ $a->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Records</button>
                                @if ($canManage)
                                    <button wire:click="openActivityForm({{ $a->id }})" class="btn btn-outline !px-2 !py-1 !text-[11px]">Edit</button>
                                    @if (in_array($a->status, ['draft', 'ongoing']))
                                        <button wire:click="completeActivity({{ $a->id }})" wire:confirm="Mark activity as completed? This writes an activity log entry." class="btn btn-success-soft !px-2 !py-1 !text-[11px]">Complete</button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($activities->isEmpty())
                        <tr><td colspan="10" class="text-center text-gray-400 py-8">No activities yet for this project.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>