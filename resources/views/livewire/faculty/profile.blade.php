{{--
    One faculty member's full extension contribution (revision §6 / D-R9).

    The eight blocks §6 specifies. "Training contribution" and "Performance trend"
    are live as of R3c — they read the training-hours model
    (`trainors x trainees x days`) through `FacultyContributionService`, which
    delegates the maths to `TrainingHoursService` so this page and the project hub
    cannot disagree.

    Both blocks still keep an explicit "not yet measurable" panel for the case
    where the model is absent, and print NULL-derived dashes rather than a 0 —
    a zero would read as "delivered nothing", which is a claim there is no basis
    for. See the Profile component docblock.

    There is NO prototype page for a faculty profile: the prototype renders faculty
    detail into a drawer (`#facDrawerBody`) and never shows a per-person trend, so
    the trend chart below is a native build with no markup to mirror. Recorded as a
    deliberate divergence in revisions.md §18.
--}}
<div>
    {{-- Admin-only. The Directory trail exists to orient the DIRECTOR, who arrives
         from /faculty or /faculty/directory — and BOTH of those routes are admin-only.
         This view is also served by `/my-profile` (`faculty.me`), so rendering the
         trail for everyone handed a faculty member two breadcrumb links that 403'd. --}}
    @if (auth()->user()->isAdmin())
        <section class="pt-6">
            <nav class="flex items-center gap-1.5 text-[11px] font-semibold text-gray-400 mb-1.5">
                <a href="{{ route('faculty.index') }}" class="hover:text-lnu-800 transition">Faculty Management</a>
                <span class="text-gray-300">/</span>
                <a href="{{ route('faculty.directory') }}" class="hover:text-lnu-800 transition">Faculty Directory</a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-500">{{ $faculty->user?->name }}</span>
            </nav>
        </section>
    @endif

    {{-- ===================== PROFILE & IDENTITY ===================== --}}
    <section class="reveal-item mt-3">
        <div class="sc-card p-5 flex flex-wrap items-start gap-4">
            <span class="avatar w-14 h-14 text-[17px]">{{ $contribution->initials($faculty->user?->name) }}</span>
            <div class="min-w-0 flex-1">
                <h2 class="text-[20px] font-extrabold tracking-tight leading-tight">{{ $faculty->user?->name }}</h2>
                <p class="text-[12.5px] text-gray-400 font-medium mt-0.5">{{ $faculty->position ?? '—' }}</p>
                <div class="mt-2.5 flex flex-wrap gap-1.5 items-center">
                    <x-sc.college-pill :code="$faculty->college?->code" :name="$faculty->college?->name" />
                    <span class="badge badge-{{ $faculty->isActive() ? 'green' : 'yellow' }}">{{ $faculty->status_label }}</span>
                    <span class="badge badge-gray font-mono">{{ $faculty->employee_id }}</span>
                </div>
            </div>

            @can('updateOwnProfile', $faculty)
                <button type="button" wire:click="editProfile" class="btn btn-outline">
                    <x-sc.icon name="edit" class="w-4 h-4" /><span class="ml-1">Edit profile</span>
                </button>
            @endcan
        </div>
    </section>

    {{-- ===================== KEY TILES ===================== --}}
    <section class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @php
            $tiles = [
                ['label' => 'Rendered hours approved', 'value' => $this->fmt($record['rendered_hours'] ?? 0), 'sub' => 'service credit'],
                ['label' => 'Projects involved', 'value' => $record['projects_involved'] ?? 0, 'sub' => ($record['projects_led'] ?? 0).' as lead'],
                ['label' => 'Activities handled', 'value' => $record['activities_handled'] ?? 0, 'sub' => 'assigned'],
                ['label' => 'Proposal approval rate', 'value' => ($record['proposal_approval_rate'] ?? null) === null ? '—' : $record['proposal_approval_rate'].'%', 'sub' => ($record['proposals_submitted'] ?? 0).' submitted'],
            ];
        @endphp
        @foreach ($tiles as $tile)
            <div class="sc-card p-4">
                <p class="text-[26px] font-extrabold tracking-tight leading-none">{{ $tile['value'] }}</p>
                <p class="text-[11.5px] text-gray-400 font-medium mt-1.5">{{ $tile['label'] }}</p>
                <p class="text-[10.5px] text-gray-300 font-semibold mt-0.5">{{ $tile['sub'] }}</p>
            </div>
        @endforeach
    </section>

    <div class="mt-4 grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- ===================== LEFT COLUMN ===================== --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- --- Training contribution (§6) --- --}}
            <div class="sc-card p-5">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h3 class="font-extrabold text-[14px] tracking-tight">Training contribution</h3>
                    @if ($record['training_measurable'] ?? false)
                        <span class="badge badge-gray font-mono" title="trainors × trainees × days">trainors × trainees × days</span>
                    @endif
                </div>
                @if ($record['training_measurable'] ?? false)
                    @php
                        $delivered = $record['training_hours_delivered'];
                        $zeroDelivered = $record['training_sessions'] > 0 && (float) $delivered === 0.0;
                    @endphp
                    <div class="grid grid-cols-4 gap-3">
                        <div>
                            <p class="text-[22px] font-extrabold leading-none">{{ $this->fmt($delivered) }}</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-1">training hours delivered</p>
                        </div>
                        <div>
                            <p class="text-[22px] font-extrabold leading-none">{{ $record['training_sessions'] ?? 0 }}</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-1">sessions</p>
                        </div>
                        <div>
                            <p class="text-[22px] font-extrabold leading-none">{{ $record['trainees_reached'] ?? 0 }}</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-1">trainees reached</p>
                        </div>
                        <div>
                            <p class="text-[22px] font-extrabold leading-none">{{ $this->fmt($record['training_days'] ?? 0) }}</p>
                            <p class="text-[11px] text-gray-400 font-medium mt-1">training days</p>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-400 font-medium mt-3">
                        <b>Trainees reached</b> counts distinct people, so it is not the sum of the
                        per-session trainee counts — someone who attended three sessions was still reached once.
                    </p>

                    @if ($record['training_sessions'] > 0)
                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            <span class="text-[10.5px] text-gray-400 font-semibold">Figures rest on:</span>
                            @forelse ($trendSources as $source)
                                <span class="badge badge-{{ $source['source'] === 'attendance' ? 'green' : ($source['source'] === 'manual' ? 'yellow' : 'gray') }}"
                                      title="{{ $source['source'] === 'attendance'
                                          ? 'Trainee counts come from imported attendance records — the most reliable source.'
                                          : ($source['source'] === 'manual'
                                              ? 'Trainee counts come from the manually entered participants field on the activity.'
                                              : 'No trainee count was ever recorded, so this activity contributes no hours.') }}">
                                    {{ $source['label'] }} · {{ $source['count'] }}
                                </span>
                            @empty
                                <span class="badge badge-gray">Not recorded</span>
                            @endforelse
                        </div>
                        @if ($record['training_sources']['none'] ?? 0)
                            <p class="text-[11px] text-gray-400 font-medium mt-2">
                                {{ $record['training_sources']['none'] }} of these
                                {{ \Illuminate\Support\Str::plural('session', $record['training_sessions']) }}
                                {{ $record['training_sessions'] === 1 ? 'has' : 'have' }} no trainee count recorded,
                                so {{ $record['training_sessions'] === 1 ? 'it contributes' : 'they contribute' }} no
                                hours to the total above.
                            </p>
                        @endif
                    @endif

                    @if ($zeroDelivered)
                        <p class="text-[11px] text-gray-500 font-medium mt-2 leading-relaxed">
                            Delivered hours are 0 despite {{ $record['training_sessions'] }}
                            {{ \Illuminate\Support\Str::plural('session', $record['training_sessions']) }} being
                            recorded — every session is missing a trainee count, a trainor count or a day count.
                            This is a data gap, not a claim that nothing was delivered.
                        </p>
                    @endif
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4">
                        <p class="text-[12px] text-gray-500 font-medium leading-relaxed">
                            <b class="text-charcoal">Not yet measurable.</b>
                            Training hours <i>delivered</i> are computed as
                            (<code class="text-[11px]">trainors × trainees × days</code>) from the activity
                            record. Nothing is shown here because the training-hours model is not available in
                            this environment — so this block says so rather than printing a 0. A zero would read
                            as "delivered nothing", which is a claim there is no basis for.
                        </p>
                        <p class="text-[11px] text-gray-400 font-medium mt-2">
                            Note the distinction: <b>rendered hours</b> (above) are service credit this faculty
                            member claims; <b>training hours</b> are hours they delivered to others.
                        </p>
                    </div>
                @endif
            </div>

            {{-- --- Rendered hours by semester --- --}}
            <div class="sc-card p-5">
                <h3 class="font-extrabold text-[14px] tracking-tight mb-3">Rendered hours</h3>
                @if ($hoursBySemester->isEmpty())
                    <p class="text-[12px] text-gray-400 font-medium">No rendered-hour entries yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($hoursBySemester as $semester => $totals)
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-[12.5px] font-bold w-[180px]">{{ $semester }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="badge badge-green">{{ $this->fmt($totals['approved']) }} hrs approved</span>
                                    @if ($totals['pending'] > 0)
                                        <span class="badge badge-yellow">{{ $this->fmt($totals['pending']) }} hrs pending</span>
                                    @endif
                                    @if ($totals['rejected'] > 0)
                                        <span class="badge badge-red">{{ $this->fmt($totals['rejected']) }} hrs rejected</span>
                                    @endif
                                    <span class="badge badge-gray">{{ $totals['count'] }} entries</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- --- Involvement --- --}}
            <div class="sc-card p-5">
                <h3 class="font-extrabold text-[14px] tracking-tight mb-3">Involvement</h3>
                @if ($projects->isEmpty())
                    <p class="text-[12px] text-gray-400 font-medium">No project assignment yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($projects as $entry)
                            <a href="{{ route('projects.show', ['project' => $entry['project']]) }}"
                               class="flex items-center gap-3 px-3.5 py-3 rounded-xl border border-gray-100 hover:border-lnu-200 hover:bg-lnu-50/40 transition">
                                <x-sc.college-pill :code="$entry['project']->college?->code" :short="true" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[12.5px] font-semibold truncate">{{ $entry['project']->title }}</p>
                                    <p class="text-[10.5px] text-gray-400 font-mono truncate">{{ $entry['project']->code }}</p>
                                </div>
                                <span class="badge {{ $entry['role'] === 'Lead' ? 'badge-gold' : 'badge-gray' }} !text-[9.5px] !px-2 !py-0.5 shrink-0">
                                    {{ $entry['role'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- --- Proposals --- --}}
            <div class="sc-card p-5">
                <h3 class="font-extrabold text-[14px] tracking-tight mb-3">Proposals</h3>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <span class="badge badge-gray">{{ $record['proposals_submitted'] }} submitted</span>
                    <span class="badge badge-green">{{ $record['proposals_approved'] }} approved</span>
                    @php $rejected = ($record['proposals_submitted'] ?? 0) - ($record['proposals_approved'] ?? 0); @endphp
                    @if ($rejected > 0)
                        <span class="badge badge-red">{{ $rejected }} other</span>
                    @endif
                </div>
                @if ($proposals->isEmpty())
                    <p class="text-[12px] text-gray-400 font-medium">No proposals submitted yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($proposals as $proposal)
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl border border-gray-100">
                                <div class="min-w-0 flex-1">
                                    <p class="text-[12.5px] font-semibold truncate">{{ $proposal->title }}</p>
                                    <p class="text-[10.5px] text-gray-400 font-medium">
                                        {{ $proposal->created_at?->format('M j, Y') }}
                                    </p>
                                </div>
                                <span class="badge badge-{{ config('smartcemes.status_colors')[$proposal->status] ?? 'gray' }} !text-[9.5px] shrink-0">
                                    {{ ucfirst($proposal->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ===================== RIGHT COLUMN ===================== --}}
        <div class="space-y-4">

            {{-- --- Expertise --- --}}
            <div class="sc-card p-5">
                <h3 class="font-extrabold text-[14px] tracking-tight mb-3">Expertise</h3>
                @if ($faculty->expertise->isEmpty())
                    <p class="text-[12px] text-gray-400 font-medium">No expertise areas tagged.</p>
                @else
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($faculty->expertise as $area)
                            <span class="badge badge-lnu">{{ $area->area }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- --- Academic --- --}}
            <div class="sc-card p-5">
                <h3 class="font-extrabold text-[14px] tracking-tight mb-3">Academic & contact</h3>
                <dl class="space-y-2 text-[12px]">
                    <div>
                        <dt class="text-gray-400 font-medium">Department</dt>
                        <dd class="font-semibold">{{ $faculty->department ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Specialization</dt>
                        <dd class="font-semibold">{{ $faculty->specialization ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Email</dt>
                        <dd class="font-semibold truncate">{{ $faculty->user?->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Contact number</dt>
                        <dd class="font-semibold">{{ $faculty->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Address</dt>
                        <dd class="font-semibold">{{ $faculty->address ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- --- Performance trend (§6) --- --}}
            <div class="sc-card p-5">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h3 class="font-extrabold text-[14px] tracking-tight">Performance trend</h3>
                    @if ($trendChart !== null)
                        <span class="badge badge-gray">{{ count($trend) }} {{ \Illuminate\Support\Str::plural('period', count($trend)) }}</span>
                    @endif
                </div>

                @if ($trendChart !== null)
                    {{-- Native build: the prototype has no faculty-profile page, so
                         there is no prototype chart markup to mirror. Uses the same
                         Chart.js wiring as the dashboard charts. --}}
                    <div class="relative h-52">
                        <canvas x-data
                                x-init="if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy(); new Chart($refs.c, {{ Illuminate\Support\Js::from($trendChart) }})"
                                x-ref="c"></canvas>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($trend as $bucket)
                            <span class="badge {{ (float) $bucket['hours'] === $trendPeak ? 'badge-lnu' : 'badge-gray' }} !text-[9.5px]"
                                  title="{{ $bucket['sessions'] }} {{ \Illuminate\Support\Str::plural('session', $bucket['sessions']) }}">
                                {{ $this->fmt($bucket['hours']) }} hrs
                                · {{ $bucket['period'] === 'undated' ? 'undated' : \Illuminate\Support\Carbon::parse($bucket['period'].'-01')->format('M Y') }}
                            </span>
                        @endforeach
                    </div>

                    <p class="text-[11px] text-gray-400 font-medium mt-3 leading-relaxed">
                        Training hours delivered per period, from each activity's planned start date — the same
                        date the project timeline orders by. Hours rest on the trainee count recorded against
                        each session, so a period where attendees were never imported will read low.
                    </p>
                @elseif (($record['training_measurable'] ?? false) && ($record['training_sessions'] ?? 0) > 0)
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 p-4">
                        <p class="text-[12px] text-gray-500 font-medium leading-relaxed">
                            <b class="text-charcoal">No trend to draw yet.</b>
                            {{ $record['training_sessions'] }}
                            {{ \Illuminate\Support\Str::plural('session', $record['training_sessions']) }}
                            {{ $record['training_sessions'] === 1 ? 'is' : 'are' }} recorded, but
                            {{ $trendPeak > 0 ? 'only one period carries hours' : 'none of them carries a measurable hour figure' }}.
                            A single point {{ $trendPeak > 0 ? 'is not a trend' : 'would draw a flat line' }}, and a flat line
                            would read as steady delivery when the honest answer is that too little is recorded yet.
                        </p>
                    </div>
                @else
                    <p class="text-[12px] text-gray-500 font-medium leading-relaxed">
                        Trend is computed from training hours delivered, which needs the training-hours model
                        and at least one assigned activity. Showing a trend line built from rendered hours
                        instead would answer a different question.
                    </p>
                @endif
            </div>

            {{-- --- Flags --- --}}
            <div class="sc-card p-5">
                <h3 class="font-extrabold text-[14px] tracking-tight mb-3">Flags</h3>
                @if (empty($flags))
                    <p class="text-[12px] text-gray-400 font-medium">Nothing needs attention.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($flags as $flag)
                            @php
                                $tone = match ($flag['tone']) {
                                    'red' => 'bg-red-50 text-red-700 border-red-100',
                                    'yellow' => 'bg-gold-50 text-gold-700 border-gold-100',
                                    default => 'bg-gray-50 text-gray-600 border-gray-100',
                                };
                            @endphp
                            <p class="text-[11.5px] font-semibold px-3 py-2 rounded-xl border {{ $tone }}">
                                {{ $flag['text'] }}
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== CONTACT SELF-EDIT ===================== --}}
    @if ($showEdit)
        <div x-data @keydown.escape.window="$wire.set('showEdit', false)"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
             role="dialog" aria-modal="true" aria-labelledby="faculty-self-edit-title">
            <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="$set('showEdit', false)"></div>
            <form wire:submit="saveProfile" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                                <x-sc.icon name="users" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Faculty profile</p>
                                <h3 id="faculty-self-edit-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Edit profile</h3>
                                <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Keep your expertise and coordination details current for project matching.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showEdit', false)" aria-label="Close profile form"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"><x-sc.icon name="x" class="h-4 w-4" /></button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                        <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                        <p><span class="font-extrabold">Institutional records.</span> Employee ID, college, position, status, and login credentials remain Director-managed and cannot be changed here.</p>
                    </div>

                    <div class="space-y-6">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Academic and contact details</h4>
                                    <p class="text-[11px] font-medium text-gray-400">These details help staff coordinate with you and match you to extension work.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="profile-specialization">Specialization</label>
                                    <input id="profile-specialization" type="text" maxlength="255" wire:model="editForm.specialization" class="input" aria-invalid="{{ $errors->has('editForm.specialization') ? 'true' : 'false' }}" placeholder="e.g. Reading Education">
                                    @error('editForm.specialization') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="profile-department">Department</label>
                                    <input id="profile-department" type="text" maxlength="255" wire:model="editForm.department" class="input" aria-invalid="{{ $errors->has('editForm.department') ? 'true' : 'false' }}" placeholder="e.g. College of Education">
                                    @error('editForm.department') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="profile-phone">Contact number</label>
                                    <input id="profile-phone" type="tel" maxlength="32" wire:model="editForm.phone" class="input" autocomplete="tel" aria-invalid="{{ $errors->has('editForm.phone') ? 'true' : 'false' }}" placeholder="09XX XXX XXXX">
                                    @error('editForm.phone') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="profile-address">Address</label>
                                    <input id="profile-address" type="text" maxlength="255" wire:model="editForm.address" class="input" autocomplete="street-address" aria-invalid="{{ $errors->has('editForm.address') ? 'true' : 'false' }}" placeholder="City / municipality">
                                    @error('editForm.address') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Expertise areas</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Select all areas that should be considered for project matching.</p>
                                </div>
                            </div>
                            <x-sc.multi-select
                                :options="collect(config('smartcemes.expertise_options'))->map(fn ($area) => ['id' => $area, 'label' => $area])->all()"
                                :selected="$editForm['expertise']"
                                model="editForm.expertise"
                                method="toggleExpertise"
                                key="profile-expertise"
                                id="profile-expertise"
                                :invalid="$errors->has('editForm.expertise')"
                                placeholder="Type to filter, then pick from the list…" />
                            @error('editForm.expertise') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                        </section>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10.5px] font-medium text-gray-400">Changes are recorded in your activity log.</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="$set('showEdit', false)" class="btn btn-ghost">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="saveProfile">
                                <span wire:loading.remove wire:target="saveProfile">Save changes</span>
                                <span wire:loading wire:target="saveProfile" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
