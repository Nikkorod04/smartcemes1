{{-- R4 / D-R7: the Objective Manager and the objective form were REMOVED from
     this surface. The 8.6 KPI dictionary is no longer shown at project level —
     the Director tracks trainors, trainees, training hours and budget against
     annual targets instead, on the Overview tab and the University Targets page.

     ProgramObjective, KpiService, the `programObjectives` relation and the
     `smartcemes.kpi_metrics` config all REMAIN in the codebase (R-Q2: retained
     unread), so historical rows stay inspectable and the migration stays
     reversible. Only the UI surface is gone. --}}

{{-- Activity create/edit --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showActivityForm', v => open = v)" @keydown.escape.window="$wire.set('showActivityForm', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="activity-form-title">
    <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" @click="$wire.set('showActivityForm', false)"></div>
    <form wire:submit="saveActivity" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop" aria-labelledby="activity-form-title">
        <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                        <x-sc.icon name="calendar" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Activity workspace</p>
                        <h3 id="activity-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">{{ $editingActivityId ? 'Edit activity' : 'Add activity' }}</h3>
                        <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Plan the session, record its training-hour inputs, and assign the faculty who will deliver it.</p>
                    </div>
                </div>
                <button type="button" aria-label="Close activity form" @click="$wire.set('showActivityForm', false)"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"><x-sc.icon name="x" class="h-4 w-4" /></button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                <x-sc.icon name="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                <p><span class="font-extrabold">Project schedule.</span> Dates must stay within {{ $program->planned_start_date->format('M j, Y') }} – {{ $program->planned_end_date->format('M j, Y') }}. Faculty assignment is blocked when schedules overlap.</p>
            </div>

            @if ($facultyConflict !== '')
                <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-[11px] leading-relaxed text-red-800">
                    <x-sc.icon name="x" class="mt-0.5 h-4 w-4 shrink-0" />
                    <p><span class="font-extrabold">Schedule conflict.</span> {{ $facultyConflict }}</p>
                </div>
            @endif

            <div class="space-y-6">
                <section>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                        <div>
                            <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Activity details</h4>
                            <p class="text-[11px] font-medium text-gray-400">Name the session and set when and where it will happen.</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="label" for="activity-project">Project</label>
                            <input id="activity-project" class="input !bg-gray-50" value="{{ $program->code }} · {{ $program->title }}" disabled>
                        </div>
                        <div>
                            <label class="label" for="activity-title">Title <span class="text-red-500">*</span></label>
                            <input id="activity-title" class="input" wire:model="activityForm.title" placeholder="Activity title">
                            @error('activityForm.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="activity-venue">Venue</label>
                            <input id="activity-venue" class="input" wire:model="activityForm.venue" placeholder="Venue / site">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label" for="activity-start-date">Planned start <span class="text-red-500">*</span></label>
                                <input id="activity-start-date" type="date" class="input" wire:model="activityForm.planned_start_date" min="{{ $program->planned_start_date->format('Y-m-d') }}" max="{{ $program->planned_end_date->format('Y-m-d') }}">
                                @error('activityForm.planned_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label" for="activity-end-date">Planned end <span class="text-red-500">*</span></label>
                                <input id="activity-end-date" type="date" class="input" wire:model="activityForm.planned_end_date" min="{{ $program->planned_start_date->format('Y-m-d') }}" max="{{ $program->planned_end_date->format('Y-m-d') }}">
                                @error('activityForm.planned_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label" for="activity-start-time">Start time</label>
                                <input id="activity-start-time" type="time" class="input" wire:model="activityForm.start_time">
                            </div>
                            <div>
                                <label class="label" for="activity-end-time">End time</label>
                                <input id="activity-end-time" type="time" class="input" wire:model="activityForm.end_time">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-lnu-100 bg-lnu-50/50 p-4">
                    <div class="mb-3 flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-lnu-700 ring-1 ring-lnu-100"><x-sc.icon name="plus" class="h-4 w-4" /></span>
                        <div>
                            <h4 class="text-[12px] font-extrabold text-lnu-900">Training hours inputs</h4>
                            <p class="mt-1 text-[11px] font-medium leading-relaxed text-lnu-800/70">These values drive the training-hours calculation shown in the activity records.</p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="label" for="activity-days">Days <span class="text-red-500">*</span></label>
                            <input id="activity-days" type="number" min="0.5" max="60" step="0.5" class="input bg-white" wire:model="activityForm.no_of_days" placeholder="1">
                            @error('activityForm.no_of_days') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="activity-trainees">Trainees <span class="font-medium text-gray-400">(manual)</span></label>
                            <input id="activity-trainees" type="number" min="0" max="10000" step="1" class="input bg-white" wire:model="activityForm.participants" placeholder="fallback">
                            @error('activityForm.participants') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="activity-trainors">Trainors <span class="font-medium text-gray-400">(override)</span></label>
                            <input id="activity-trainors" type="number" min="0" max="200" step="1" class="input bg-white" wire:model="activityForm.trainors_snapshot" placeholder="auto">
                            @error('activityForm.trainors_snapshot') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="mt-2.5 text-[10.5px] leading-relaxed text-lnu-700/80"><b>Days</b> — 1.0 for a full day, <b>0.5</b> for a half day (0.5 increments only). Training hours are not multiplied by 8. <b>Trainees</b> is used only when no imported attendance exists; <b>trainors</b> defaults to assigned faculty.</p>
                </section>

                <section>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">3</span>
                        <div>
                            <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Budget and status</h4>
                            <p class="text-[11px] font-medium text-gray-400">Record the activity-level budget and its current workflow state.</p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="activity-budget">Allocated budget <span class="font-medium text-gray-400">(₱)</span></label>
                            <input id="activity-budget" type="number" min="0" step="0.01" class="input" wire:model="activityForm.allocated_budget" placeholder="0.00">
                        </div>
                        <div>
                            <label class="label" for="activity-status">Status</label>
                            <x-sc.select id="activity-status" model="activityForm.status" :value="$activityForm['status']" :options="collect(config('smartcemes.statuses.activity'))->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" placeholder="— select status —" :search="false" :invalid="$errors->has('activityForm.status')" />
                            @error('activityForm.status') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">4</span>
                        <div>
                            <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Faculty assignment</h4>
                            <p class="text-[11px] font-medium text-gray-400">Select the faculty members responsible for delivery.</p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3.5">
                        <div class="chip-group">
                            @forelse ($facultyOptions as $f)
                                <label wire:click="toggleActivityFaculty({{ $f->id }})"
                                      @class(['chip', 'on' => in_array($f->id, is_array($activityForm['faculty_ids'] ?? null) ? $activityForm['faculty_ids'] : [], true)])>{{ $f->user->name }}</label>
                            @empty
                                <p class="text-[11px] font-medium text-gray-400">No faculty members are available for assignment.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" @click="$wire.set('showActivityForm', false)">Cancel</button>
                    <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="saveActivity">
                        <span wire:loading.remove wire:target="saveActivity">Save activity</span>
                        <span wire:loading wire:target="saveActivity" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Enroll existing --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showEnroll', v => open = v)" @keydown.escape.window="$wire.set('showEnroll', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="enroll-beneficiary-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showEnroll', false)"></div>
    <section class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="users" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project beneficiaries</p><h3 id="enroll-beneficiary-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Enroll existing beneficiary</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Find a profile in the global registry and link it to this project.</p></div></div><button type="button" wire:click="$set('showEnroll', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close enroll beneficiary dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-[11px] leading-relaxed text-blue-900"><x-sc.icon name="users" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-600" /><p>Search the global registry, including beneficiaries not yet linked to any project.</p></div>
            <label class="label" for="enroll-beneficiary-search">Search registry</label>
            <input id="enroll-beneficiary-search" type="text" class="input mb-4" wire:model.live.debounce.300ms="enrollSearch" placeholder="Search by name or barangay…">
            <div class="space-y-1.5 pr-1">
            @forelse ($registry as $b)
                <div class="flex items-center gap-3 p-2.5 rounded-xl border border-gray-100 hover:bg-gray-50 transition">
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold">{{ $b->fullName() }}</p>
                        <p class="text-[11px] text-gray-400">{{ $b->barangay }} · {{ $b->beneficiary_category }}</p>
                    </div>
                    @if ($program->beneficiaries->contains($b->id))
                        <span class="badge badge-gray shrink-0">Already enrolled</span>
                    @else
                        <button wire:click="enrollExisting({{ $b->id }})" class="btn btn-outline !px-2 !py-1 !text-[11px] shrink-0">Enroll</button>
                    @endif
                </div>
            @empty
                <p class="text-[12.5px] text-gray-400 italic py-2">No registry matches for this search.</p>
            @endforelse
            </div>
        </div>
        <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end"><button type="button" class="btn btn-ghost" wire:click="$set('showEnroll', false)">Close</button></div></footer>
    </section>
</div>

{{-- Register new (with dedup confirm) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showRegister', v => open = v)" @keydown.escape.window="$wire.set('showRegister', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="register-beneficiary-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showRegister', false)"></div>
    <form wire:submit="saveRegister" class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="people" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project beneficiaries</p><h3 id="register-beneficiary-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Register new beneficiary</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Create a registry profile and enroll it into this project in one step.</p></div></div><button type="button" wire:click="$set('showRegister', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close register beneficiary dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-[11px] leading-relaxed text-blue-900"><x-sc.icon name="users" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-600" /><p>Complete the profile fields below. Required fields are validated before the profile is enrolled.</p></div>
        <div class="grid grid-cols-3 gap-3">
            <div><label class="label" for="register-first-name">First name <span class="text-red-500">*</span></label><input id="register-first-name" maxlength="100" class="input" wire:model="registerForm.first_name" aria-invalid="{{ $errors->has('registerForm.first_name') ? 'true' : 'false' }}">@error('registerForm.first_name') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
            <div><label class="label" for="register-middle-name">Middle name</label><input id="register-middle-name" maxlength="100" class="input" wire:model="registerForm.middle_name"></div>
            <div><label class="label" for="register-last-name">Last name <span class="text-red-500">*</span></label><input id="register-last-name" maxlength="100" class="input" wire:model="registerForm.last_name" aria-invalid="{{ $errors->has('registerForm.last_name') ? 'true' : 'false' }}">@error('registerForm.last_name') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-4">
            <div><label class="label" for="register-age">Age</label><input id="register-age" type="number" min="0" max="120" class="input" wire:model="registerForm.age"></div>
            <div><label class="label" for="register-gender">Sex</label><x-sc.select id="register-gender" model="registerForm.gender" :value="$registerForm['gender'] ?? null" :options="['Female' => 'Female', 'Male' => 'Male', 'Prefer not to say' => 'Prefer not to say']" placeholder="Select sex" /></div>
            <div><label class="label" for="register-category">Category <span class="text-red-500">*</span></label><x-sc.select id="register-category" model="registerForm.beneficiary_category" :value="$registerForm['beneficiary_category'] ?? null" :options="collect(config('smartcemes.beneficiary_categories'))->mapWithKeys(fn ($cat) => [$cat => $cat])->all()" placeholder="Select category" :invalid="$errors->has('registerForm.beneficiary_category')" />@error('registerForm.beneficiary_category') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-4">
            <div><label class="label" for="register-phone">Contact number</label><input id="register-phone" maxlength="30" class="input" wire:model="registerForm.phone" placeholder="0912…"></div>
            <div><label class="label" for="register-barangay">Barangay <span class="text-red-500">*</span></label><input id="register-barangay" maxlength="150" class="input" wire:model="registerForm.barangay" aria-invalid="{{ $errors->has('registerForm.barangay') ? 'true' : 'false' }}">@error('registerForm.barangay') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
            <div><label class="label" for="register-municipality">Municipality</label><input id="register-municipality" maxlength="150" class="input" wire:model="registerForm.municipality"></div>
        </div>

        @if ($dedupWarning !== '' && ! $registerConfirmed)
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-[12.5px] font-bold text-red-700">Possible duplicate found</p>
                <p class="text-[12px] text-red-600 mt-1">{{ $dedupWarning }}</p>
                <p class="text-[11.5px] text-red-500 mt-2">Saving is not blocked — but you must confirm this is a different person. Records are never silently merged.</p>
                <div class="flex gap-2 mt-3">
                    <button type="button" wire:click="$set('dedupWarning', '')" class="btn btn-outline !py-1.5 !text-[11.5px]">Go back and edit</button>
                    <button type="button" wire:click="registerConfirmedSave" class="btn btn-danger-soft !py-1.5 !text-[11.5px]">This is a different person — save anyway</button>
                </div>
            </div>
        @endif

        </div>
        <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between"><p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p><div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" wire:click="$set('showRegister', false)">Cancel</button><button type="submit" class="btn btn-primary min-w-[142px]" wire:loading.attr="disabled" wire:target="saveRegister"><span wire:loading.remove wire:target="saveRegister">Register &amp; enroll</span><span wire:loading wire:target="saveRegister" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span></button></div></div></footer>
    </form>
</div>

{{-- Beneficiary XLSX import (upload → preview → confirm) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showImport', v => open = v)" @keydown.escape.window="$wire.set('showImport', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="import-beneficiary-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showImport', false)"></div>
    <section class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="upload" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project beneficiaries</p><h3 id="import-beneficiary-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Import beneficiaries from XLSX</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Review the official template rows before enrolling them into this project.</p></div></div><button type="button" wire:click="$set('showImport', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close beneficiary import dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
        @if (! $importPreview)
            <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3.5 mb-5">
                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                <p class="text-[12px] text-gray-500 leading-snug">Use the official template with fixed column headers. Required: First Name, Last Name, Barangay, Beneficiary Category. Blank contact numbers default to <b>09123456789</b>; unknown categories auto-map to <b>Other</b>. Rows with errors or duplicates (first + last name + barangay) are skipped — never a whole-file reject.</p>
            </div>

            <div>
                <label class="label">Official template (.xlsx) *</label>
                <input type="file" wire:model="importFile"
                       accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                       class="input !py-2.5 bg-gray-50">
                @error('importFile') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                <p class="text-[11px] text-gray-400 mt-1">Max 10 MB · up to 500 rows per file · .xlsx only</p>
            </div>

        @else
            <div class="flex items-start gap-2.5 rounded-xl border border-gray-100 bg-gray-50 p-3 mb-4">
                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                <p class="text-[12px] text-gray-500 leading-snug">Parsed <b>{{ $importFile?->getClientOriginalName() }}</b> — review the rows below, then confirm. Rows marked <span class="badge badge-red !text-[10px]">Skip</span> (errors) or <span class="badge badge-yellow !text-[10px]">Duplicate</span> will not be imported.</p>
            </div>

            <div class="rounded-xl border border-gray-100 max-h-80 overflow-y-auto">
                <table class="sc-table !text-[12px]">
                    <thead><tr><th>#</th><th>Name</th><th>Barangay</th><th>Category</th><th>Contact</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($importRows as $i => $row)
                            <tr wire:key="imp-{{ $i }}">
                                <td class="text-gray-400">{{ $i + 1 }}</td>
                                <td>
                                    <span class="font-semibold">{{ $row['data']['first_name'] }} {{ $row['data']['last_name'] }}</span>
                                    @if ($row['category_other']) <span class="text-[10px] text-gold-700">· "{{ $row['category_other'] }}" kept as Other</span> @endif
                                    @foreach ($row['errors'] as $field => $err)
                                        <p class="text-[10.5px] text-red-600 mt-0.5">{{ ucfirst(str_replace('_', ' ', $field)) }}: {{ $err }}</p>
                                    @endforeach
                                </td>
                                <td class="text-gray-500">{{ $row['data']['barangay'] ?? '—' }}</td>
                                <td class="text-gray-500">{{ $row['data']['beneficiary_category'] ?? '—' }}</td>
                                <td class="text-gray-500">{{ $row['data']['phone'] ?? '—' }}</td>
                                <td>
                                    @if ($row['errors'] !== [])
                                        <span class="badge badge-red">Skip</span>
                                    @elseif ($row['duplicate'])
                                        <span class="badge badge-yellow">Duplicate</span>
                                    @else
                                        <span class="badge badge-green">Import</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
        </div>
        <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
            @if (! $importPreview)
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between"><a href="{{ route('beneficiaries.template') }}" class="btn btn-ghost !px-3 text-[12px]"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a><div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" wire:click="$set('showImport', false)">Cancel</button><button type="button" wire:click="parseImport" wire:loading.attr="disabled" wire:target="parseImport" class="btn btn-primary min-w-[120px]"><span wire:loading.remove wire:target="parseImport">Parse file →</span><span wire:loading wire:target="parseImport" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Parsing…</span></button></div></div>
            @else
                <div class="flex justify-end gap-2"><button type="button" wire:click="$set('importPreview', false)" class="btn btn-ghost">← Back</button><button type="button" wire:click="confirmImport" wire:loading.attr="disabled" wire:target="confirmImport" class="btn btn-primary min-w-[142px]"><span wire:loading.remove wire:target="confirmImport">Confirm &amp; import</span><span wire:loading wire:target="confirmImport" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Importing…</span></button></div>
            @endif
        </footer>
    </section>
</div>

{{-- Budget entry --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showBudgetForm', v => open = v)" @keydown.escape.window="$wire.set('showBudgetForm', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="budget-entry-title">
    <div class="fixed inset-0 sc-modal-backdrop" wire:click="$set('showBudgetForm', false)"></div>
    <form wire:submit="saveBudgetEntry" class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="wallet" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project finances</p><h3 id="budget-entry-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Record utilization</h3><p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Log a project expense and optionally attribute it to an activity.</p></div></div><button type="button" wire:click="$set('showBudgetForm', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close record utilization dialog"><x-sc.icon name="x" class="h-4 w-4" /></button></div></header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
        <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-[11px] leading-relaxed text-blue-900"><x-sc.icon name="wallet" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-600" /><p>The expense is charged against this project. Selecting an activity only controls how the utilization is attributed.</p></div>
        <div class="space-y-4">
            <div><label class="label" for="budget-item-name">Item name <span class="text-red-500">*</span></label><input id="budget-item-name" maxlength="255" class="input" wire:model="budgetForm.item_name" placeholder="e.g. Training materials" aria-invalid="{{ $errors->has('budgetForm.item_name') ? 'true' : 'false' }}">@error('budgetForm.item_name') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
            <div class="grid grid-cols-2 gap-3"><div><label class="label" for="budget-amount">Amount (₱) <span class="text-red-500">*</span></label><input id="budget-amount" type="number" min="0" step="0.01" class="input" wire:model="budgetForm.amount" aria-invalid="{{ $errors->has('budgetForm.amount') ? 'true' : 'false' }}">@error('budgetForm.amount') <p class="sc-field-error">{{ $message }}</p> @enderror</div><div><label class="label" for="budget-date-used">Date used <span class="text-red-500">*</span></label><input id="budget-date-used" type="date" class="input" wire:model="budgetForm.date_used" aria-invalid="{{ $errors->has('budgetForm.date_used') ? 'true' : 'false' }}">@error('budgetForm.date_used') <p class="sc-field-error">{{ $message }}</p> @enderror</div></div>
            <div><label class="label" for="budget-activity">Charge against activity</label><x-sc.select id="budget-activity" model="budgetForm.activity_id" :value="$budgetForm['activity_id'] ?? null" :options="$program->activities->mapWithKeys(fn ($a) => [$a->id => $a->title])->all()" placeholder="Project-level (no specific activity)" :search="$program->activities->count() > 12" :clearable="true" /></div>
            <p class="text-[11.5px] text-gray-400">Exceeding the allocation still saves — a persistent warning badge appears and the entry is written to the activity log (D7).</p>
        </div>
        </div>
        <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" wire:click="$set('showBudgetForm', false)">Cancel</button><button type="submit" class="btn btn-primary min-w-[122px]" wire:loading.attr="disabled" wire:target="saveBudgetEntry"><span wire:loading.remove wire:target="saveBudgetEntry">Save entry</span><span wire:loading wire:target="saveBudgetEntry" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span></button></div></footer>
    </form>
</div>

{{-- Project edit modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showProgramEdit', v => open = v)" @keydown.escape.window="$wire.set('showProgramEdit', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="edit-project-form-title">
    <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="$set('showProgramEdit', false)"></div>
    <form wire:submit="saveProgramEdit" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop" aria-labelledby="edit-project-form-title">
        <div class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                        <x-sc.icon name="edit" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Project workspace</p>
                        <h3 id="edit-project-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Edit project details</h3>
                        <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">{{ $program->code }} · update the project plan, ownership, reach, and workflow status.</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('showProgramEdit', false)" aria-label="Close edit project form"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"><x-sc.icon name="x" class="h-4 w-4" /></button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-[11px] leading-relaxed text-amber-900">
                <x-sc.icon name="edit" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
                <p><span class="font-extrabold">Schedule safeguard.</span> Existing activities must remain inside the revised project range. Status changes are written to the activity log.</p>
            </div>

            <div class="space-y-6">
                <section>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                        <div>
                            <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Project identity</h4>
                            <p class="text-[11px] font-medium text-gray-400">Keep the project name and description current for staff and partners.</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="label" for="edit-project-title">Project title <span class="text-red-500">*</span></label>
                            <input id="edit-project-title" required class="input" wire:model="editForm.title">
                            @error('editForm.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="edit-project-description">Description</label>
                            <textarea id="edit-project-description" rows="4" class="input resize-y" wire:model="editForm.description" placeholder="Short narrative description"></textarea>
                            @error('editForm.description') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">2</span>
                        <div>
                            <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Schedule and resources</h4>
                            <p class="text-[11px] font-medium text-gray-400">Adjust the delivery window and the planning values for this project.</p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="edit-project-start">Planned start <span class="text-red-500">*</span></label>
                            <input id="edit-project-start" required type="date" class="input" wire:model="editForm.planned_start_date">
                            @error('editForm.planned_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="edit-project-end">Planned end <span class="text-red-500">*</span></label>
                            <input id="edit-project-end" required type="date" class="input" wire:model="editForm.planned_end_date">
                            @error('editForm.planned_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="edit-project-beneficiaries">Target beneficiaries</label>
                            <input id="edit-project-beneficiaries" type="number" min="1" class="input" wire:model="editForm.target_beneficiaries">
                            @error('editForm.target_beneficiaries') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="edit-project-budget">Allocated budget <span class="font-medium text-gray-400">(₱)</span></label>
                            <input id="edit-project-budget" type="number" min="0" step="0.01" class="input" wire:model="editForm.allocated_budget">
                            @error('editForm.allocated_budget') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="edit-project-hours">Target training hours <span class="font-medium text-gray-400">(annual)</span></label>
                            <input id="edit-project-hours" type="number" min="0" step="0.01" class="input" wire:model="editForm.annual_target_hours" placeholder="e.g. 120">
                            @error('editForm.annual_target_hours') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="edit-project-status">Status</label>
                            <x-sc.select id="edit-project-status" model="editForm.status" :value="$editForm['status']" :options="collect(config('smartcemes.statuses.program'))->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" placeholder="— select status —" :search="false" :required="true" :invalid="$errors->has('editForm.status')" />
                            @error('editForm.status') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">3</span>
                        <div>
                            <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Ownership and reach</h4>
                            <p class="text-[11px] font-medium text-gray-400">Maintain the lead, target communities, and beneficiary groups.</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="label" for="edit-project-lead">Project lead</label>
                            <x-sc.select id="edit-project-lead" model="editForm.program_lead_id" :value="$editForm['program_lead_id']" :options="$facultyOptions->mapWithKeys(fn ($f) => [$f->id => $f->user->name.' · '.$f->department])->all()" placeholder="— not assigned yet —" :search="true" :clearable="true" :invalid="$errors->has('editForm.program_lead_id')" />
                            @error('editForm.program_lead_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Linked communities</label>
                            <x-sc.multi-select
                                :options="$allCommunities->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.' · '.$c->municipality.($c->isSchool() ? ' · School' : '')])->all()"
                                :selected="$editForm['community_ids'] ?? []"
                                model="editForm.community_ids"
                                method="toggleEditArray"
                                key="community_ids"
                                placeholder="— select communities —" />
                            @error('editForm.community_ids') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Beneficiary categories</label>
                            <x-sc.multi-select
                                :options="collect($beneficiaryCategories)->map(fn ($cat) => ['id' => $cat, 'label' => $cat])->all()"
                                :selected="$editForm['beneficiary_categories'] ?? []"
                                model="editForm.beneficiary_categories"
                                method="toggleEditArray"
                                key="beneficiary_categories"
                                placeholder="— select categories —" />
                            @error('editForm.beneficiary_categories') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showProgramEdit', false)" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="saveProgramEdit">
                        <span wire:loading.remove wire:target="saveProgramEdit">Save changes</span>
                        <span wire:loading wire:target="saveProgramEdit" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Activity records: attendance + evaluation XLSX imports (v4.13, 5.5/5.6) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('recordsActivityId', v => open = v)" @keydown.escape.window="$wire.closeRecords()"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="activity-records-title">
    <div class="fixed inset-0 sc-modal-backdrop" wire:click="closeRecords"></div>
    @if ($recordsActivityId)
        @php($recordsActivity = $activities->firstWhere('id', $recordsActivityId))
        @if ($recordsActivity)
            <section class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
                <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6"><div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="clipboard" class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Activity records</p><h3 id="activity-records-title" class="mt-1 text-[18px] font-extrabold leading-snug tracking-tight">{{ $recordsActivity->title }}</h3><p class="mt-1 max-w-2xl text-[12px] font-medium leading-relaxed text-white/72">{{ $recordsActivity->planned_start_date->format('M j, Y') }} · {{ $recordsActivity->venue ?? 'No venue recorded' }} · manage attendance and evaluation imports.</p></div></div><button type="button" wire:click="closeRecords" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close activity records dialog"><x-sc.icon name="x" class="w-4 h-4" /></button></div></header>
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">

                <div class="flex gap-1 bg-gray-50 rounded-xl border border-gray-100 p-1 w-max mb-4">
                    <button type="button" wire:click="setRecordsTab('attendance')" @class(['tab', 'on' => $recordsTab === 'attendance'])>Attendance</button>
                    <button type="button" wire:click="setRecordsTab('evaluation')" @class(['tab', 'on' => $recordsTab === 'evaluation'])>Evaluation</button>
                </div>

                {{-- ==================== ATTENDANCE ==================== --}}
                @if ($recordsTab === 'attendance')
                    @if ($canManageBeneficiaries)
                        @if ($attendanceStep === 'upload')
                            @php($attendanceStatusLabels = implode(' · ', array_map(fn ($s) => ucfirst($s), config('smartcemes.statuses.attendance'))))
                            <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-4">
                                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                                <p class="text-[12px] text-gray-500 leading-snug">Download the official template — the enrolled roster is pre-filled. Fill the <b>Status</b> column ({{ $attendanceStatusLabels }}) for the <b>{{ $recordsActivity->planned_start_date->format('M j, Y') }}</b> session; a blank cell leaves that beneficiary unrecorded. Rows are matched by Beneficiary ID + name — unknown, duplicate or invalid rows are skipped and listed on screen, never a whole-file reject.</p>
                            </div>

                            <div>
                                <label class="label">Official attendance template (.xlsx) *</label>
                                <input type="file" wire:model="attendanceImportFile"
                                       accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                       class="input !py-2.5 bg-gray-50">
                                @error('attendanceImportFile') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                <p class="text-[11px] text-gray-400 mt-1">Max 10 MB · up to 500 rows per file · .xlsx only</p>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <a href="{{ route('activities.attendance-template', $recordsActivity) }}" class="btn btn-ghost !px-3 text-[12px]"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a>
                                <button wire:click="parseAttendanceImport" wire:loading.attr="disabled" wire:target="parseAttendanceImport" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="parseAttendanceImport">Parse file →</span>
                                    <span wire:loading wire:target="parseAttendanceImport">Reading file…</span>
                                </button>
                            </div>
                        @elseif ($attendanceStep === 'preview')
                            @php($attendanceBadge = ['present' => 'badge-green', 'absent' => 'badge-red', 'excused' => 'badge-yellow', 'late' => 'badge-gold'])
                            <div class="flex items-start gap-2.5 rounded-xl border border-gray-100 bg-gray-50 p-3 mb-4">
                                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                                <p class="text-[12px] text-gray-500 leading-snug">
                                    Parsed <b>{{ $attendanceFileName }}</b> for <b>{{ $recordsActivity->planned_start_date->format('M j, Y') }}</b> — review the rows, then confirm.
                                    <span class="badge badge-gray !text-[10px]">{{ $attendanceSummary['processed'] ?? 0 }} rows</span>
                                    <span class="badge badge-green !text-[10px]">{{ $attendanceSummary['applied'] ?? 0 }} to apply</span>
                                    <span class="badge badge-yellow !text-[10px]">{{ $attendanceSummary['skipped'] ?? 0 }} blank</span>
                                    <span class="badge badge-red !text-[10px]">{{ $attendanceSummary['invalid'] ?? 0 }} errors</span>
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-100 max-h-80 overflow-y-auto">
                                <table class="sc-table !text-[12px]">
                                    <thead><tr><th>#</th><th>Beneficiary</th><th>Beneficiary ID</th><th>Status</th><th>State</th></tr></thead>
                                    <tbody>
                                        @foreach ($attendanceRows as $row)
                                            <tr wire:key="att-{{ $row['row'] }}">
                                                <td class="text-gray-400">{{ $row['row'] }}</td>
                                                <td>
                                                    <span class="font-semibold">{{ $row['name'] }}</span>
                                                    @foreach ($row['errors'] as $err)
                                                        <p class="text-[10.5px] text-red-600 mt-0.5">{{ $err }}</p>
                                                    @endforeach
                                                </td>
                                                <td class="text-gray-500">{{ $row['beneficiary_id'] ?: '—' }}</td>
                                                <td>
                                                    @if ($row['status'] !== null)
                                                        <span class="badge {{ $attendanceBadge[$row['status']] ?? 'badge-gray' }}">{{ ucfirst($row['status']) }}</span>
                                                    @else
                                                        <span class="text-gray-300">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($row['state'] === 'error')
                                                        <span class="badge badge-red">Skip</span>
                                                    @elseif ($row['state'] === 'skipped')
                                                        <span class="badge badge-gray">Blank</span>
                                                    @else
                                                        <span class="badge badge-green">Apply</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <button type="button" wire:click="$set('attendanceStep', 'upload')" class="btn btn-ghost">← Back</button>
                                <button wire:click="confirmAttendanceImport" wire:loading.attr="disabled" wire:target="confirmAttendanceImport" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="confirmAttendanceImport">Confirm & import attendance</span>
                                    <span wire:loading wire:target="confirmAttendanceImport">Saving…</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-4">
                            <x-sc.icon name="eye" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                            <p class="text-[12px] text-gray-500 leading-snug">Read-only — attendance is recorded by the Director's office or the Secretary by importing the official template. <b>{{ $attendancesByActivity[$recordsActivity->id] ?? 0 }}</b> attendance record{{ ($attendancesByActivity[$recordsActivity->id] ?? 0) === 1 ? '' : 's' }} on file for this activity.</p>
                        </div>
                    @endif

                    @php($attendanceImport = $recordsImports->firstWhere('type', 'attendance'))
                    @if ($attendanceImport)
                        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11.5px] text-gray-500">
                            <span class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Last import</span>
                            <span class="font-semibold text-charcoal">{{ $attendanceImport->original_name }}</span>
                            <span>{{ $attendanceImport->imported_at?->format('M j, Y g:i A') }}</span>
                            <span>{{ $attendanceImport->rows_applied }} applied · {{ $attendanceImport->rows_skipped }} skipped</span>
                            @if ($attendanceImport->importer) <span>by {{ $attendanceImport->importer->name }}</span> @endif
                        </div>
                    @endif
                @else
                    {{-- ==================== EVALUATION ==================== --}}
                    @php($evalCurrentCards = [
                        ['Pre-test mean', $evaluationCurrent['pre'] ?? null, ''],
                        ['Post-test mean', $evaluationCurrent['post'] ?? null, ''],
                        ['Satisfaction mean', $evaluationCurrent['satisfaction'] ?? null, '/5'],
                    ])

                    @if ($canManageBeneficiaries)
                        @if ($evaluationStep === 'upload')
                            <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-4">
                                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                                <p class="text-[12px] text-gray-500 leading-snug">Download the official template — the enrolled roster is pre-filled. Enter each beneficiary's <b>Pre-Test (0–100)</b>, <b>Post-Test (0–100)</b> and <b>Satisfaction (1–5)</b>; blank cells are ignored. On confirm the values are averaged into this activity's aggregate scores (D13 — no per-beneficiary scores are stored).</p>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mb-4">
                                @foreach ($evalCurrentCards as [$label, $value, $suffix])
                                    <div class="rounded-xl border border-gray-100 bg-gray-50/60 px-3.5 py-2.5">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $label }}</p>
                                        <p class="text-[15px] font-extrabold tracking-tight mt-0.5 leading-none {{ $value === null ? 'text-gray-300' : 'text-charcoal' }}">
                                            {{ $value === null ? 'No scores yet' : number_format($value, 2).$suffix }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <label class="label">Official evaluation template (.xlsx) *</label>
                                <input type="file" wire:model="evaluationImportFile"
                                       accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                       class="input !py-2.5 bg-gray-50">
                                @error('evaluationImportFile') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                                <p class="text-[11px] text-gray-400 mt-1">Max 10 MB · up to 500 rows per file · .xlsx only</p>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <a href="{{ route('activities.evaluation-template', $recordsActivity) }}" class="btn btn-ghost !px-3 text-[12px]"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a>
                                <button wire:click="parseEvaluationImport" wire:loading.attr="disabled" wire:target="parseEvaluationImport" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="parseEvaluationImport">Parse file →</span>
                                    <span wire:loading wire:target="parseEvaluationImport">Reading file…</span>
                                </button>
                            </div>
                        @elseif ($evaluationStep === 'preview')
                            @php($means = $evaluationSummary['means'] ?? ['pre' => null, 'post' => null, 'satisfaction' => null, 'counts' => []])
                            <div class="flex items-start gap-2.5 rounded-xl border border-gray-100 bg-gray-50 p-3 mb-4">
                                <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                                <p class="text-[12px] text-gray-500 leading-snug">
                                    Parsed <b>{{ $evaluationFileName }}</b> — review the rows, then confirm. Per-metric means merge into the activity aggregates; a metric without values keeps its current aggregate.
                                    <span class="badge badge-gray !text-[10px]">{{ $evaluationSummary['processed'] ?? 0 }} rows</span>
                                    <span class="badge badge-green !text-[10px]">{{ $evaluationSummary['applied'] ?? 0 }} scored</span>
                                    <span class="badge badge-yellow !text-[10px]">{{ $evaluationSummary['skipped'] ?? 0 }} blank</span>
                                    <span class="badge badge-red !text-[10px]">{{ $evaluationSummary['invalid'] ?? 0 }} errors</span>
                                </p>
                            </div>

                            <div class="rounded-xl border border-gray-100 max-h-72 overflow-y-auto">
                                <table class="sc-table !text-[12px]">
                                    <thead><tr><th>#</th><th>Beneficiary</th><th>ID</th><th class="!text-right">Pre</th><th class="!text-right">Post</th><th class="!text-right">Satisfaction</th><th>State</th></tr></thead>
                                    <tbody>
                                        @foreach ($evaluationRows as $row)
                                            <tr wire:key="eval-{{ $row['row'] }}">
                                                <td class="text-gray-400">{{ $row['row'] }}</td>
                                                <td>
                                                    <span class="font-semibold">{{ $row['name'] }}</span>
                                                    @foreach ($row['errors'] as $err)
                                                        <p class="text-[10.5px] text-red-600 mt-0.5">{{ $err }}</p>
                                                    @endforeach
                                                </td>
                                                <td class="text-gray-500">{{ $row['beneficiary_id'] ?: '—' }}</td>
                                                <td class="!text-right text-gray-600">{{ $row['values']['pre'] === null ? '—' : number_format($row['values']['pre'], 2) }}</td>
                                                <td class="!text-right text-gray-600">{{ $row['values']['post'] === null ? '—' : number_format($row['values']['post'], 2) }}</td>
                                                <td class="!text-right text-gray-600">{{ $row['values']['satisfaction'] === null ? '—' : number_format($row['values']['satisfaction'], 2) }}</td>
                                                <td>
                                                    @if ($row['state'] === 'error')
                                                        <span class="badge badge-red">Skip</span>
                                                    @elseif ($row['state'] === 'skipped')
                                                        <span class="badge badge-gray">Blank</span>
                                                    @else
                                                        <span class="badge badge-green">Scored</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="grid grid-cols-3 gap-3 pt-4">
                                @foreach ([['Pre-test mean', 'pre', ''], ['Post-test mean', 'post', ''], ['Satisfaction mean', 'satisfaction', '/5']] as [$label, $key, $suffix])
                                    @php($before = $evaluationCurrent[$key] ?? null)
                                    @php($after = $means[$key] ?? null)
                                    @php($count = $means['counts'][$key] ?? 0)
                                    <div class="rounded-xl border px-3.5 py-2.5 {{ $after === null ? 'border-gray-100 bg-gray-50/60' : 'border-lnu-100 bg-lnu-50/60' }}">
                                        <p class="text-[10px] font-bold uppercase tracking-wider {{ $after === null ? 'text-gray-400' : 'text-lnu-700' }}">{{ $label }}</p>
                                        <p class="text-[15px] font-extrabold tracking-tight mt-0.5 leading-none">
                                            <span class="text-gray-400">{{ $before === null ? '—' : number_format($before, 2) }}</span>
                                            <span class="text-gray-300">→</span>
                                            @if ($after === null)
                                                <span class="text-gray-400">unchanged</span>
                                            @else
                                                <span class="text-lnu-800">{{ number_format($after, 2).$suffix }}</span>
                                            @endif
                                        </p>
                                        <p class="text-[10.5px] text-gray-400 mt-1">{{ $count }} scored row{{ $count === 1 ? '' : 's' }} in the file{{ $after === null ? ' — the current aggregate is kept' : '' }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <button type="button" wire:click="$set('evaluationStep', 'upload')" class="btn btn-ghost">← Back</button>
                                <button wire:click="confirmEvaluationImport" wire:loading.attr="disabled" wire:target="confirmEvaluationImport" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="confirmEvaluationImport">Confirm & apply means</span>
                                    <span wire:loading wire:target="confirmEvaluationImport">Saving…</span>
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-4">
                            <x-sc.icon name="eye" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                            <p class="text-[12px] text-gray-500 leading-snug">Read-only — evaluation scores are recorded by the Director's office or the Secretary by importing the official template. The aggregates below feed knowledge gain, objectives and reports (D13).</p>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            @foreach ($evalCurrentCards as [$label, $value, $suffix])
                                <div class="rounded-xl border border-gray-100 bg-gray-50/60 px-3.5 py-2.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $label }}</p>
                                    <p class="text-[15px] font-extrabold tracking-tight mt-0.5 leading-none {{ $value === null ? 'text-gray-300' : 'text-charcoal' }}">
                                        {{ $value === null ? 'No scores yet' : number_format($value, 2).$suffix }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @php($evaluationImport = $recordsImports->firstWhere('type', 'evaluation'))
                    @if ($evaluationImport)
                        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11.5px] text-gray-500">
                            <span class="text-[10.5px] font-bold uppercase tracking-wider text-gray-400">Last import</span>
                            <span class="font-semibold text-charcoal">{{ $evaluationImport->original_name }}</span>
                            <span>{{ $evaluationImport->imported_at?->format('M j, Y g:i A') }}</span>
                            <span>{{ $evaluationImport->rows_applied }} scored · {{ $evaluationImport->rows_skipped }} blank</span>
                            @if ($evaluationImport->importer) <span>by {{ $evaluationImport->importer->name }}</span> @endif
                        </div>
                    @endif
                @endif
                </div>
                <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end"><button type="button" wire:click="closeRecords" class="btn btn-ghost">Close</button></div></footer>
            </section>
        @endif
    @endif
</div>

{{-- ==================== PROJECT NARRATIVE GENERATION (5.15) ==================== --}}
{{-- Kept at the Livewire root (NOT inside the .reveal-item tabs section):
     its animation leaves a persistent transform, which would make these
     position:fixed overlays resolve against the section instead of the viewport. --}}
@include('livewire.partials.program-narrative-generation-modals', ['actionTarget' => 'generateNarrative'])

{{-- FULL NARRATIVE MODAL — summary, risks, next actions, provenance, aggregates reviewed --}}
@if ($showNarrativeModal && $fullNarrative)
    @php($raw = $fullNarrative->raw_extracted_data ?? [])
    <div x-data @keydown.escape.window="$wire.set('showNarrativeModal', false)"
         class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="project-narrative-title">
        <div class="fixed inset-0 sc-modal-backdrop" wire:click="closeNarrativeModal"></div>
        <section class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">

            {{-- header --}}
            <header class="relative shrink-0 px-5 py-5 bg-lnu-800 text-white sm:px-6"
                 style="background-image:radial-gradient(circle at 90% -40%, rgba(246,184,0,.35), transparent 46%), radial-gradient(rgba(255,255,255,.12) 1px, transparent 1.4px); background-size:auto, 16px 16px;">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <x-sc.icon name="sparkles" class="w-5 h-5 text-gold-300" />
                        </span>
                        <div class="min-w-0">
                            <span class="ai-chip !bg-white/10 !text-blue-100 !border-white/20"><x-sc.icon name="sparkles" class="w-3 h-3" />Project Narrative</span>
                            <h3 id="project-narrative-title" class="text-white font-extrabold text-[18px] tracking-tight leading-snug mt-2">{{ $program->title }}</h3>
                            <p class="text-[11.5px] text-blue-100/75 mt-1 font-medium truncate">{{ $program->code }} · {{ $program->communities->pluck('name')->implode(', ') ?: 'No community linked' }} · Lead: {{ $program->programLead?->user?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="narrative-health {{ $fullNarrative->health_label }}">{{ match ($fullNarrative->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}</span>
                        <button wire:click="closeNarrativeModal" class="p-2 rounded-lg text-blue-100/70 hover:bg-white/10 hover:text-white transition" title="Close"><x-sc.icon name="x" class="w-4 h-4" /></button>
                    </div>
                </div>
            </header>

            {{-- meta strip --}}
            <div class="shrink-0 px-5 py-3 border-b border-gray-100 bg-gray-50/70 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] text-gray-500 font-medium sm:px-6">
                <span>Generated <span class="font-bold text-charcoal">{{ $fullNarrative->generated_at?->format('M j, Y · g:i A') }}</span></span>
                <span>by <span class="font-bold text-charcoal">{{ $fullNarrative->generator?->name ?? 'Director, CESO' }}</span></span>
                <span class="sm:ml-auto flex flex-wrap items-center gap-1.5">
                    <span class="badge badge-gray !text-[10px] font-mono">{{ $fullNarrative->metadata['model'] ?? 'gemini' }}</span>
                    <span class="badge badge-gray !text-[10px]">prompt {{ $fullNarrative->metadata['prompt_version'] ?? '—' }}</span>
                    <span class="badge badge-blue !text-[10px]" title="{{ $fullNarrative->metadata['confidence_basis'] ?? 'Derived data-confidence heuristic — guidance only' }}">confidence {{ $fullNarrative->confidence_score !== null ? number_format((float) $fullNarrative->confidence_score, 2) : '—' }}</span>
                </span>
            </div>

            {{-- body --}}
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 space-y-5 sm:px-6">
                {{-- snapshot tiles --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {{-- These read the LIVE rollup ($performance), while the narrative text and
                         the "Data the AI reviewed" accordion below are the FROZEN
                         `raw_extracted_data`. The label is not decoration: on an audit surface,
                         unlabelled live figures sitting beside a frozen snapshot drift apart
                         silently as soon as the project changes. --}}
                    <div class="col-span-2 sm:col-span-4 flex flex-wrap items-baseline gap-x-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Current figures</p>
                        <p class="text-[10.5px] text-gray-400">— live now; the narrative was generated from the snapshot under “Data the AI reviewed”</p>
                    </div>
                    <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Trainors</p>
                        <p class="text-[16px] font-extrabold mt-1">{{ $performance['trainors'] }}</p>
                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">faculty assigned</p>
                    </div>
                    <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Activities</p>
                        <p class="text-[16px] font-extrabold mt-1">{{ $program->activities->where('status', 'completed')->count() }}<span class="text-[12px] text-gray-400 font-bold"> / {{ $program->activities->count() }}</span></p>
                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">completed</p>
                    </div>
                    <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Budget utilized</p>
                        <p class="text-[16px] font-extrabold mt-1 {{ $over ? 'text-red-600' : '' }}">{{ $budgetVsAllocation['pct'] === null ? '—' : $budgetVsAllocation['pct'].'%' }}</p>
                        <p class="text-[10px] {{ $over ? 'text-red-500 font-semibold' : 'text-gray-400 font-medium' }} mt-0.5">{{ $over ? 'over-allocated (D7)' : 'of ₱'.number_format($budgetVsAllocation['allocated']) }}</p>
                    </div>
                    <div class="p-3 rounded-xl border border-gray-100 bg-gray-50/60">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Training hours</p>
                        <p class="text-[16px] font-extrabold mt-1">{{ number_format($performance['actual_hours']) }}<span class="text-[12px] text-gray-400 font-bold"> / {{ $performance['target_hours'] === null ? '—' : number_format($performance['target_hours']) }}</span></p>
                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $performance['training_days'] === 0.0 ? 'no days recorded' : app(\App\Services\TrainingHoursService::class)->formatDays($performance['training_days']).' days · no × 8' }}</p>
                    </div>
                </div>

                {{-- executive summary --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                    <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2"><x-sc.icon name="doc" class="w-3.5 h-3.5" /> Summary</p>
                    <p class="text-[13px] text-gray-600 leading-relaxed">{{ $fullNarrative->summary ?: 'No summary recorded.' }}</p>
                </div>

                {{-- risks + recommended next actions --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Top Risks</p>
                        <ul class="space-y-1.5 text-[12.5px] text-gray-600">
                            @forelse ($fullNarrative->risks ?? [] as $risk)
                                <li class="flex items-start gap-1.5"><span class="text-red-500 mt-1 text-[8px]">●</span><span class="leading-snug">{{ is_array($risk) ? ($risk['risk'] ?? $risk['text'] ?? '') : $risk }}</span></li>
                            @empty
                                <li class="text-gray-400 italic">No material risks identified.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4">
                        <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2"><span class="w-1.5 h-1.5 rounded-sm bg-lnu-400"></span> Recommended Next Actions</p>
                        <ul class="space-y-2 text-[12.5px] text-gray-600">
                            @forelse ($fullNarrative->recommendations ?? [] as $r)
                                @php($prioTone = (($r['priority'] ?? '') === 'High') ? 'badge-red' : ((($r['priority'] ?? '') === 'Medium') ? 'badge-gold' : 'badge-gray'))
                                <li class="flex items-start gap-1.5">
                                    <span class="text-lnu-700 mt-0.5 text-[10px]">▸</span>
                                    <span class="leading-snug min-w-0">
                                        <span class="flex items-start gap-1.5 flex-wrap">
                                            @if (! empty($r['priority']))
                                                <span class="badge {{ $prioTone }} !text-[10px] shrink-0">{{ $r['priority'] }}</span>
                                            @endif
                                            <span class="font-semibold">{{ $r['action'] ?? (is_string($r) ? $r : '') }}</span>
                                        </span>
                                        @if (! empty($r['rationale']))<span class="block text-[11px] text-gray-400 mt-0.5 leading-snug">{{ $r['rationale'] }}</span>@endif
                                    </span>
                                </li>
                            @empty
                                <li class="text-gray-400 italic">None pending.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                {{-- D3 audit view: the aggregate snapshot the model received --}}
                @if (! empty($raw))
                    <details class="sc-acc">
                        <summary>Data the AI reviewed <span class="acc-num">{{ $raw['activities']['total'] ?? 0 }} activities · {{ number_format((float) ($raw['training']['training_hours'] ?? 0)) }} training hrs · aggregates only</span><svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg></summary>
                        <div class="px-4 pb-4 pt-2 border-t border-gray-50">
                            <p class="text-[11px] text-gray-400 mb-3 mt-1.5">Aggregate snapshot sent to the model (D3) — counts, percentages, and distributions only; no respondent PII.</p>

                            {{-- R4 / D-R7: the 8.6 KPI grid that used to sit here is gone.
                                 The model still receives the raw aggregate payload (contract
                                 unchanged — no AI behaviour change in R4), but the surface no
                                 longer replays the retired dictionary back to the Director. --}}

                            <div class="mt-1 grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-gray-600">
                                <div class="p-2.5 rounded-lg border border-gray-100 bg-white">
                                    <span class="font-bold text-gray-500">Activities:</span>
                                    {{ $raw['activities']['total'] ?? 0 }} total · {{ $raw['activities']['completed'] ?? 0 }} completed · {{ $raw['activities']['overdue'] ?? 0 }} overdue
                                </div>
                                <div class="p-2.5 rounded-lg border border-gray-100 bg-white">
                                    <span class="font-bold text-gray-500">Budget:</span>
                                    ₱{{ number_format((float) ($raw['budget']['allocated'] ?? 0)) }} allocated · ₱{{ number_format((float) ($raw['budget']['utilized'] ?? 0)) }} utilized{{ ($raw['budget']['over_allocated'] ?? false) ? ' · over-allocated' : '' }}
                                </div>
                                <div class="p-2.5 rounded-lg border border-gray-100 bg-white">
                                    <span class="font-bold text-gray-500">Period:</span> {{ $raw['project']['period'] ?? '—' }}
                                </div>
                            </div>

                            {{-- R4/R5 REPLACEMENT (2026-10-07). This block used to list the
                                 project's OBJECTIVES from `$raw['objectives']['list']` — an 8.6
                                 surface retired by D-R7, and a key `ProgramAggregates` stopped
                                 emitting in R5. So it was dead markup AND the accordion's
                                 summary line above read a permanent "0 objectives".

                                 It now shows the `training` block — the metric dictionary the
                                 narrative is actually built from, and the figure the Director
                                 needs to cross-check the narrative's claims against
                                 (docs/guides/10-ai-analysis-narratives.md B3). --}}
                            @php($training = $raw['training'] ?? [])
                            @if (! empty($training))
                                <div class="mt-3 rounded-lg border border-gray-100 bg-white overflow-hidden">
                                    <p class="px-3 pt-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Training reviewed</p>
                                    <div class="px-3 mt-1.5 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] text-gray-600">
                                        <div><span class="font-bold text-gray-500">Trainors:</span> {{ $training['trainors'] ?? 0 }}</div>
                                        <div><span class="font-bold text-gray-500">Trainees:</span> {{ number_format((float) ($training['trainees'] ?? 0)) }}</div>
                                        <div><span class="font-bold text-gray-500">Training hours:</span> {{ number_format((float) ($training['training_hours'] ?? 0)) }}</div>
                                        <div><span class="font-bold text-gray-500">Training days:</span> {{ number_format((float) ($training['training_days'] ?? 0), 1) }}</div>
                                    </div>
                                    <p class="px-3 py-2.5 mt-1.5 border-t border-gray-50 text-[10.5px] text-gray-400 leading-relaxed">
                                        {{ $training['formula'] ?? 'trainors × trainees × days' }}
                                        @if (($training['target_hours'] ?? null) !== null)
                                            · {{ $training['hours_attainment_pct'] === null ? '—' : $training['hours_attainment_pct'].'%' }} of the {{ number_format((float) $training['target_hours']) }}-hr annual target
                                        @else
                                            · no annual hours target set
                                        @endif
                                        @if (! empty($training['trainee_sources']))
                                            · trainee figures rest on {{ collect($training['trainee_sources'])->map(fn ($n, $src) => $n.' '.\Illuminate\Support\Str::plural('activity', $n).' from '.$src)->implode(', ') }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </details>
                @endif
            </div>

            {{-- footer --}}
            <footer class="shrink-0 px-5 py-4 border-t border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-3 sm:px-6">
                <p class="text-[10.5px] text-gray-400 leading-snug max-w-lg">Provenance recorded for audit — model, prompt version, and generating officer. Admin-internal decision support; guidance only, not an approval record.</p>
                <button wire:click="closeNarrativeModal" class="btn btn-outline !px-4 !py-2">Close</button>
            </footer>
        </section>
    </div>
@endif

