{{--
    Faculty Directory (revision §5 R3 step 3).

    Mirrors docs/prototype/pages/faculty-directory.html: breadcrumb back to the
    board, toolbar (search / college tabs / expertise / status / sort / Add
    Faculty), and the roster card grid. The New + Edit profile form is the
    modal at the bottom.
--}}
<div>
    {{-- ===================== header ===================== --}}
    <section class="pt-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <nav class="flex items-center gap-1.5 text-[11px] font-semibold text-gray-400 mb-1.5">
                    <a href="{{ route('faculty.index') }}" class="hover:text-lnu-800 transition">Faculty Management</a>
                    <span class="text-gray-300">/</span>
                    <span class="text-gray-500">Faculty Directory</span>
                </nav>
                <h2 class="text-[20px] font-extrabold tracking-tight leading-tight">Faculty Directory</h2>
                <p class="text-[12.5px] text-gray-400 font-medium mt-0.5">Roster of extension faculty</p>
            </div>
        </div>
    </section>

    {{-- ===================== toolbar ===================== --}}
    <section class="reveal-item mt-4">
        <div class="sc-card p-4 flex flex-wrap items-center gap-3">
            <label class="sc-search flex-1 min-w-[220px] max-w-xs">
                <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="input"
                       placeholder="Search name, ID, position, email…">
            </label>

            <div class="flex flex-wrap gap-1 bg-white rounded-xl border border-gray-100 p-1">
                <button type="button" class="tab {{ $collegeFilter === '' ? 'on' : '' }}"
                        wire:click="$set('collegeFilter', '')">All</button>
                @foreach ($colleges as $college)
                    <button type="button" class="tab {{ $collegeFilter === $college->code ? 'on' : '' }}"
                            wire:click="$set('collegeFilter', '{{ $college->code }}')">{{ $college->code }}</button>
                @endforeach
            </div>

            <select wire:model.live="expertiseFilter" class="input !w-[200px] !py-2 text-[12px]">
                <option value="">All expertise areas</option>
                @foreach ($expertiseOptions as $area)
                    <option value="{{ $area }}">{{ $area }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="input !w-[140px] !py-2 text-[12px]">
                <option value="">All statuses</option>
                @foreach (\App\Models\Faculty::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
                <option value="{{ \App\Models\Faculty::STATUS_INACTIVE }}">Inactive</option>
            </select>

            <select wire:model.live="sort" class="ml-auto input !w-[190px] !py-2 text-[12px]">
                <option value="rank">Sort: rank (seniority)</option>
                <option value="name">Sort: name A–Z</option>
                <option value="hours">Sort: hours rendered</option>
                <option value="college">Sort: college</option>
                <option value="projects">Sort: projects involved</option>
                <option value="leads">Sort: projects led</option>
            </select>

            <button type="button" wire:click="create" class="btn btn-primary">
                <x-sc.icon name="users" class="w-4 h-4" /><span class="ml-1">Add Faculty</span>
            </button>
        </div>

        @if ($search || $collegeFilter || $expertiseFilter || $statusFilter)
            <div class="mt-2 flex items-center gap-2">
                <span class="text-[11px] text-gray-400 font-medium">Filters active</span>
                <button type="button" wire:click="clearFilters" class="text-[11px] font-bold text-lnu-700 hover:underline">
                    Clear all
                </button>
            </div>
        @endif
    </section>

    {{-- ===================== roster ===================== --}}
    <section class="reveal-item mt-4">
        <div class="sc-card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="font-bold text-[14px]">Roster</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-gray">{{ $rows->count() }} shown</span>
                    <span class="badge badge-gray">{{ $summary['hours'] }} hrs rendered</span>
                </div>
            </div>

            @if ($rows->isEmpty())
                <div class="px-5 py-14 text-center">
                    <p class="text-[13.5px] font-semibold text-gray-500">No faculty match your filters</p>
                    <p class="text-[12px] text-gray-400 mt-1">Try a different keyword, college, or expertise area.</p>
                </div>
            @else
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($rows as $row)
                        <a href="{{ route('faculty.show', ['faculty' => $row['id']]) }}"
                           class="reveal-item sc-card sc-card-hover p-4 flex flex-col gap-3"
                           wire:key="fac-{{ $row['id'] }}">
                            <div class="flex items-start gap-3">
                                <span class="avatar w-10 h-10 text-[12px] shrink-0">
                                    {{ $contribution->initials($row['name']) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-extrabold text-[13.5px] leading-snug tracking-tight truncate">
                                        {{ $row['name'] }}
                                    </p>
                                    <p class="text-[11px] text-gray-400 font-medium truncate">{{ $row['position'] ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-1.5 items-center">
                                <x-sc.college-pill :code="$row['college']" :short="true" />
                                <span class="badge badge-{{ $row['status'] === 'active' ? 'green' : 'yellow' }} !text-[9.5px]">
                                    {{ \App\Models\Faculty::STATUS_LABELS[$row['status']] ?? $row['status'] }}
                                </span>
                                <span class="badge badge-gray font-mono !text-[9.5px]">{{ $row['employee_id'] }}</span>
                            </div>

                            @if ($row['expertise'])
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_slice($row['expertise'], 0, 3) as $area)
                                        <span class="badge badge-lnu !text-[9.5px]">{{ $area }}</span>
                                    @endforeach
                                    @if (count($row['expertise']) > 3)
                                        <span class="badge badge-gray !text-[9.5px]">+{{ count($row['expertise']) - 3 }}</span>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-auto pt-3 border-t border-gray-100 grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-[15px] font-extrabold tracking-tight leading-none">
                                        {{ $contribution->formatHoursValue($row['rendered_hours']) }}
                                    </p>
                                    <p class="text-[9.5px] text-gray-400 font-semibold mt-1">hrs rendered</p>
                                </div>
                                <div>
                                    <p class="text-[15px] font-extrabold tracking-tight leading-none">{{ $row['projects_involved'] }}</p>
                                    <p class="text-[9.5px] text-gray-400 font-semibold mt-1">projects</p>
                                </div>
                                <div>
                                    <p class="text-[15px] font-extrabold tracking-tight leading-none">{{ $row['projects_led'] }}</p>
                                    <p class="text-[9.5px] text-gray-400 font-semibold mt-1">leads</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== NEW / EDIT PROFILE MODAL ===================== --}}
    @if ($showForm)
        <div x-data @keydown.escape.window="$wire.closeForm()"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
             role="dialog" aria-modal="true" aria-labelledby="faculty-form-title">
            <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="closeForm"></div>

            <form wire:submit="save"
                  class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                                <x-sc.icon name="users" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Faculty directory</p>
                                <h3 id="faculty-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">
                                    {{ $editingId ? 'Edit faculty profile' : 'Add faculty member' }}
                                </h3>
                                <p class="mt-1 max-w-xl text-[12px] font-medium leading-relaxed text-white/72">
                                    {{ $editingId ? 'Keep the faculty record, assignment, and expertise details up to date.' : 'Create a faculty profile so they can be assigned to extension programs and activities.' }}
                                </p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeForm" aria-label="Close faculty form"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50">
                            <x-sc.icon name="x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                        <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                        <p><span class="font-extrabold">Profile note.</span> The account uses the email address for sign-in. Employee ID is generated automatically when it is left blank.</p>
                    </div>

                    <div class="space-y-6">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Identity</h4>
                                    <p class="text-[11px] font-medium text-gray-400">The name and account details shown across the system.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="faculty-name">Full name <span class="text-red-500">*</span></label>
                                    <input id="faculty-name" type="text" wire:model="form.name" class="input" autocomplete="name" required>
                                    @error('form.name') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="faculty-email">Email address <span class="text-red-500">*</span></label>
                                    <input id="faculty-email" type="email" wire:model="form.email" class="input" autocomplete="email" required>
                                    @error('form.email') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="faculty-employee-id">Employee ID</label>
                                    @if ($editingId)
                                        <input id="faculty-employee-id" type="text" wire:model="form.employee_id" class="input font-mono" autocomplete="off">
                                    @else
                                        <div class="relative">
                                            <input id="faculty-employee-id" type="text" value="Generated automatically" class="input bg-gray-50 pr-20 font-mono text-gray-400" disabled aria-describedby="faculty-employee-help">
                                            <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md bg-white px-2 py-1 text-[9px] font-extrabold uppercase tracking-[0.1em] text-lnu-700 ring-1 ring-gray-200">Auto</span>
                                        </div>
                                    @endif
                                    @if (! $editingId)
                                        <p id="faculty-employee-help" class="mt-1.5 text-[10.5px] font-medium text-gray-400">Assigned by the system when the profile is created.</p>
                                    @endif
                                    @error('form.employee_id') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="faculty-college">College</label>
                                    <x-sc.select id="faculty-college" model="form.college_id" :value="$form['college_id']" :options="$colleges->mapWithKeys(fn ($college) => [$college->id => $college->code.' — '.$college->name])->all()" placeholder="— select college —" :search="false" :invalid="$errors->has('form.college_id')" />
                                    @error('form.college_id') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Appointment</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Place the faculty member within the university and extension roster.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="faculty-position">Position</label>
                                    <x-sc.select id="faculty-position" model="form.position" :value="$form['position']" :options="collect($positionLadder)->mapWithKeys(fn ($rung) => [$rung['label'] => $rung['label']])->all()" placeholder="— select position —" :search="false" :invalid="$errors->has('form.position')" />
                                    @error('form.position') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="faculty-status">Status</label>
                                    <x-sc.select id="faculty-status" model="form.status" :value="$form['status']" :options="\App\Models\Faculty::STATUS_LABELS" placeholder="— select status —" :search="false" :required="true" :invalid="$errors->has('form.status')" />
                                    @error('form.status') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="faculty-department">Department</label>
                                    <input id="faculty-department" type="text" wire:model="form.department" class="input" autocomplete="organization-title">
                                </div>
                                <div>
                                    <label class="label" for="faculty-specialization">Specialization</label>
                                    <input id="faculty-specialization" type="text" wire:model="form.specialization" class="input">
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">3</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Contact and expertise</h4>
                                    <p class="text-[11px] font-medium text-gray-400">Optional details used for coordination and project matching.</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="faculty-phone">Contact number</label>
                                    <input id="faculty-phone" type="text" wire:model="form.phone" class="input" autocomplete="tel">
                                </div>
                                <div>
                                    <label class="label" for="faculty-address">Address</label>
                                    <input id="faculty-address" type="text" wire:model="form.address" class="input" autocomplete="street-address">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="label">
                                    Expertise areas
                                    <span class="font-normal text-gray-400">(select all that apply)</span>
                                </label>
                                <x-sc.multi-select
                                    :options="collect($expertiseOptions)->map(fn ($area) => ['id' => $area, 'label' => $area])->all()"
                                    :selected="$form['expertise']"
                                    model="form.expertise"
                                    method="toggleExpertise"
                                    key="faculty-expertise"
                                    placeholder="Search and select expertise areas…" />
                                @error('form.expertise') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>
                        </section>
                    </div>
                </div>

                <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="closeForm" class="btn btn-ghost">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Create profile' }}</span>
                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
