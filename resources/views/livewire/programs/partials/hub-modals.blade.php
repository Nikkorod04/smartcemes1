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
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showActivityForm', false)"></div>
    <form wire:submit="saveActivity" class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
        <h3 class="font-bold text-[15px] mb-1">{{ $editingActivityId ? 'Edit activity' : 'Add activity' }}</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">Program is locked to this hub. Dates are constrained to the program range; faculty assignment is hard-blocked on schedule conflicts.</p>
        <div class="space-y-3">
            <div><label class="label">Program</label><input class="input !bg-gray-50" value="{{ $program->code }} · {{ $program->title }}" disabled></div>
            <div><label class="label">Title *</label><input class="input" wire:model="activityForm.title" placeholder="Activity title">
                @error('activityForm.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            <div><label class="label">Venue</label><input class="input" wire:model="activityForm.venue" placeholder="Venue / site"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Planned start *</label><input type="date" class="input" wire:model="activityForm.planned_start_date" min="{{ $program->planned_start_date->format('Y-m-d') }}" max="{{ $program->planned_end_date->format('Y-m-d') }}">
                    @error('activityForm.planned_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
                <div><label class="label">Planned end *</label><input type="date" class="input" wire:model="activityForm.planned_end_date" min="{{ $program->planned_start_date->format('Y-m-d') }}" max="{{ $program->planned_end_date->format('Y-m-d') }}">
                    @error('activityForm.planned_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Start time</label><input type="time" class="input" wire:model="activityForm.start_time"></div>
                <div><label class="label">End time</label><input type="time" class="input" wire:model="activityForm.end_time"></div>
            </div>

            {{-- R4 training-hours inputs (§4.4, D-R3/D-R4, R-Q1). Days is the
                 duration carrier — there is no `× 8`; a half day is 0.5. --}}
            <div class="rounded-xl border border-lnu-100 bg-lnu-50/50 p-3.5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-lnu-800 mb-2.5">Training hours inputs</p>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="label">Days *</label>
                        <input type="number" min="0.5" max="60" step="0.5" class="input" wire:model="activityForm.no_of_days" placeholder="1">
                        @error('activityForm.no_of_days') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div><label class="label">Trainees (manual)</label>
                        <input type="number" min="0" max="10000" step="1" class="input" wire:model="activityForm.participants" placeholder="fallback">
                        @error('activityForm.participants') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div><label class="label">Trainors override</label>
                        <input type="number" min="0" max="200" step="1" class="input" wire:model="activityForm.trainors_snapshot" placeholder="auto">
                        @error('activityForm.trainors_snapshot') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-[10.5px] text-lnu-700/80 mt-2 leading-relaxed">
                    <b>Days</b> — 1.0 for a full day, <b>0.5</b> for a half day (0.5 increments only). The days field already carries the duration, so training hours are
                    <b>not</b> multiplied by 8. <b>Trainees</b> is used only when no imported attendance exists. <b>Trainors</b> defaults to the faculty you assign below.
                    <span class="font-mono font-bold">trainors × trainees × days</span>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Allocated budget (₱)</label><input type="number" min="0" step="0.01" class="input" wire:model="activityForm.allocated_budget"></div>
                <div><label class="label">Status</label>
                    <select class="input" wire:model="activityForm.status">
                        @foreach (config('smartcemes.statuses.activity') as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div><label class="label">Assign faculty</label>
                <div class="chip-group">
                    @foreach ($facultyOptions as $f)
                        <label wire:click="toggleActivityFaculty({{ $f->id }})"
                              @class(['chip', 'on' => in_array($f->id, is_array($activityForm['faculty_ids'] ?? null) ? $activityForm['faculty_ids'] : [], true)])>{{ $f->user->name }}</label>
                    @endforeach
                </div>
                @if ($facultyConflict !== '')
                    <p class="text-[12px] text-red-600 font-semibold mt-2">{{ $facultyConflict }}</p>
                @endif
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('showActivityForm', false)">Cancel</button>
            <button type="submit" class="btn btn-primary">Save activity</button>
        </div>
    </form>
</div>

{{-- Enroll existing --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showEnroll', v => open = v)" @keydown.escape.window="$wire.set('showEnroll', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showEnroll', false)"></div>
    <div class="sc-modal relative max-w-lg mx-auto mt-20 sc-card p-6 shadow-pop">
        <h3 class="font-bold text-[15px] mb-1">Enroll existing beneficiary</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-3">Search the global registry — including beneficiaries not yet linked to any program.</p>
        <input type="text" class="input mb-3" wire:model.live.debounce.300ms="enrollSearch" placeholder="Search registry by name or barangay…">
        <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
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
</div>

{{-- Register new (with dedup confirm) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showRegister', v => open = v)" @keydown.escape.window="$wire.set('showRegister', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showRegister', false)"></div>
    <form wire:submit="saveRegister" class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
        <h3 class="font-bold text-[15px] mb-1">Register new beneficiary</h3>
        <p class="text-[12px] text-gray-400 font-medium mb-4">Creates the profile and auto-enrolls it into this program.</p>
        <div class="grid grid-cols-3 gap-3">
            <div><label class="label">First name *</label><input class="input" wire:model="registerForm.first_name">
                @error('registerForm.first_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            <div><label class="label">Middle name</label><input class="input" wire:model="registerForm.middle_name"></div>
            <div><label class="label">Last name *</label><input class="input" wire:model="registerForm.last_name">
                @error('registerForm.last_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-3">
            <div><label class="label">Age</label><input type="number" class="input" wire:model="registerForm.age"></div>
            <div><label class="label">Sex</label>
                <select class="input" wire:model="registerForm.gender">
                    @foreach (['Female', 'Male', 'Prefer not to say'] as $g)
                        <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">Category *</label>
                <select class="input" wire:model="registerForm.beneficiary_category">
                    <option value="">Select category</option>
                    @foreach (config('smartcemes.beneficiary_categories') as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
                @error('registerForm.beneficiary_category') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-3">
            <div><label class="label">Contact number</label><input class="input" wire:model="registerForm.phone" placeholder="0912…"></div>
            <div><label class="label">Barangay *</label><input class="input" wire:model="registerForm.barangay">
                @error('registerForm.barangay') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            <div><label class="label">Municipality</label><input class="input" wire:model="registerForm.municipality"></div>
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

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('showRegister', false)">Cancel</button>
            <button type="submit" class="btn btn-primary">Register & enroll</button>
        </div>
    </form>
</div>

{{-- Beneficiary XLSX import (upload → preview → confirm) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showImport', v => open = v)" @keydown.escape.window="$wire.set('showImport', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showImport', false)"></div>
    <div class="sc-modal relative max-w-3xl mx-auto mt-16 sc-card p-6 shadow-pop">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-[15px]">Import beneficiaries from XLSX</h3>
                <p class="text-[12px] text-gray-400 font-medium mt-0.5">Official template · one row = one beneficiary · imported rows are auto-enrolled into this program.</p>
            </div>
            <button type="button" wire:click="$set('showImport', false)" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
        </div>

        @if (! $importPreview)
            <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-4">
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

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('beneficiaries.template') }}" class="btn btn-ghost !px-3 text-[12px]"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a>
                <button wire:click="parseImport" wire:loading.attr="disabled" class="btn btn-primary">Parse file →</button>
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

            <div class="flex items-center justify-between pt-4">
                <button wire:click="$set('importPreview', false)" class="btn btn-ghost">← Back</button>
                <button wire:click="confirmImport" wire:loading.attr="disabled" class="btn btn-primary">Confirm & import</button>
            </div>
        @endif
    </div>
</div>

{{-- Budget entry --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showBudgetForm', v => open = v)" @keydown.escape.window="$wire.set('showBudgetForm', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="$set('showBudgetForm', false)"></div>
    <form wire:submit="saveBudgetEntry" class="sc-modal relative max-w-lg mx-auto mt-20 sc-card p-6 shadow-pop">
        <div class="flex items-start justify-between mb-1">
            <div>
                <h3 class="font-bold text-[15px]">Record utilization</h3>
                <p class="text-[12px] text-gray-400 font-medium mt-0.5">Charged against this program (locked) — optionally attributed to an activity.</p>
            </div>
            <button type="button" wire:click="$set('showBudgetForm', false)" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
        </div>
        <div class="space-y-3 mt-3">
            <div><label class="label">Item name *</label><input class="input" wire:model="budgetForm.item_name" placeholder="e.g. Training materials">
                @error('budgetForm.item_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">Amount (₱) *</label><input type="number" step="0.01" class="input" wire:model="budgetForm.amount">
                    @error('budgetForm.amount') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror</div>
                <div><label class="label">Date used *</label><input type="date" class="input" wire:model="budgetForm.date_used"></div>
            </div>
            <div><label class="label">Charge against activity</label>
                <select class="input" wire:model="budgetForm.activity_id">
                    <option value="">Program-level (no specific activity)</option>
                    @foreach ($program->activities as $a)
                        <option value="{{ $a->id }}">{{ $a->title }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-[11.5px] text-gray-400">Exceeding the allocation still saves — a persistent warning badge appears and the entry is written to the activity log (D7).</p>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="btn btn-ghost" wire:click="$set('showBudgetForm', false)">Cancel</button>
            <button type="submit" class="btn btn-primary">Save entry</button>
        </div>
    </form>
</div>

{{-- Program edit modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showProgramEdit', v => open = v)" @keydown.escape.window="$wire.set('showProgramEdit', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="$set('showProgramEdit', false)"></div>
    <form wire:submit="saveProgramEdit" class="sc-modal relative max-w-xl mx-auto mt-16 sc-card p-6 shadow-pop">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h3 class="font-extrabold text-[16px] tracking-tight">Edit program details</h3>
                <p class="text-[12px] text-gray-400 mt-0.5">{{ $program->code }} · status transitions write activity log entries (8.1)</p>
            </div>
            <button type="button" wire:click="$set('showProgramEdit', false)" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="label">Project title *</label>
                <input required class="input" wire:model="editForm.title">
                @error('editForm.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Description</label>
                <textarea rows="2" class="input" wire:model="editForm.description" placeholder="Short narrative description"></textarea>
                @error('editForm.description') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Planned start *</label>
                <input required type="date" class="input" wire:model="editForm.planned_start_date">
                @error('editForm.planned_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Planned end *</label>
                <input required type="date" class="input" wire:model="editForm.planned_end_date">
                @error('editForm.planned_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Target beneficiaries</label>
                <input type="number" min="1" class="input" wire:model="editForm.target_beneficiaries">
                @error('editForm.target_beneficiaries') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Allocated budget (₱)</label>
                <input type="number" min="0" step="0.01" class="input" wire:model="editForm.allocated_budget">
                @error('editForm.allocated_budget') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Target training hours (annual)</label>
                <input type="number" min="0" step="0.01" class="input" wire:model="editForm.annual_target_hours" placeholder="e.g. 120">
                @error('editForm.annual_target_hours') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Project lead</label>
                <select class="input" wire:model="editForm.program_lead_id">
                    <option value="">— not assigned yet —</option>
                    @foreach ($facultyOptions as $f)
                        <option value="{{ $f->id }}">{{ $f->user->name }} · {{ $f->department }}</option>
                    @endforeach
                </select>
                @error('editForm.program_lead_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Status</label>
                <select class="input" wire:model="editForm.status">
                    @foreach (config('smartcemes.statuses.program') as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                @error('editForm.status') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Linked communities</label>
                <x-sc.multi-select
                    :options="$allCommunities->map(fn ($c) => ['id' => $c->id, 'label' => $c->name.' · '.$c->municipality.($c->isSchool() ? ' · School' : '')])->all()"
                    :selected="$editForm['community_ids'] ?? []"
                    method="toggleEditArray"
                    key="community_ids"
                    placeholder="— select communities —" />
                @error('editForm.community_ids') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Beneficiary categories</label>
                <x-sc.multi-select
                    :options="collect($beneficiaryCategories)->map(fn ($cat) => ['id' => $cat, 'label' => $cat])->all()"
                    :selected="$editForm['beneficiary_categories'] ?? []"
                    method="toggleEditArray"
                    key="beneficiary_categories"
                    placeholder="— select categories —" />
                @error('editForm.beneficiary_categories') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <p class="text-[11.5px] text-gray-400 mt-3">Activities must stay within the program date range — shrinking the range is blocked while activities sit outside it (8.8).</p>

        <div class="flex justify-end gap-2 mt-5">
            <button type="button" wire:click="$set('showProgramEdit', false)" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
</div>

{{-- Activity records: attendance + evaluation XLSX imports (v4.13, 5.5/5.6) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('recordsActivityId', v => open = v)" @keydown.escape.window="$wire.closeRecords()"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeRecords"></div>
    @if ($recordsActivityId)
        @php($recordsActivity = $activities->firstWhere('id', $recordsActivityId))
        @if ($recordsActivity)
            <div class="sc-modal relative max-w-3xl mx-auto mt-16 sc-card p-6 shadow-pop">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="font-bold text-[15px] leading-snug">Records · {{ $recordsActivity->title }}</h3>
                        <p class="text-[12px] text-gray-400 font-medium mt-0.5">
                            {{ $recordsActivity->planned_start_date->format('M j, Y') }} · {{ $recordsActivity->venue ?? 'No venue recorded' }} · attendance and evaluation are recorded by importing the official XLSX templates.
                        </p>
                    </div>
                    <button type="button" wire:click="closeRecords" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-charcoal transition shrink-0"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>

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
        @endif
    @endif
</div>

{{-- ==================== EXECUTIVE NARRATIVE (5.15) ==================== --}}
{{-- Kept at the Livewire root (NOT inside the .reveal-item tabs section):
     its animation leaves a persistent transform, which would make these
     position:fixed overlays resolve against the section instead of the viewport. --}}

{{-- GENERATING OVERLAY — centered while the synchronous generation runs --}}
<div wire:loading.flex wire:target="generateNarrative"
     class="fixed inset-0 z-[70] items-center justify-center bg-charcoal/50 backdrop-blur-[2px] no-print">
    <div class="sc-card sc-modal px-10 py-8 text-center shadow-pop max-w-sm mx-4">
        <span class="relative w-16 h-16 mx-auto rounded-2xl bg-lnu-50 text-lnu-700 flex items-center justify-center">
            <x-sc.icon name="sparkles" class="w-7 h-7" />
            <span class="absolute -bottom-1.5 -right-1.5 w-7 h-7 rounded-xl bg-gold-500 text-white flex items-center justify-center ring-2 ring-white shadow-sm">
                <x-sc.icon name="loader" class="w-4 h-4 animate-spin" />
            </span>
        </span>
        <p class="mt-4 font-extrabold text-[14px] tracking-tight">Generating executive narrative<span class="pulse-dot">…</span></p>
        <p class="text-[12px] text-gray-400 mt-1 leading-relaxed">Summarizing training hours, budget, activities and targets. Aggregates only — no PII leaves the system.</p>
        <div class="mt-4 flex justify-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-lnu-700 animate-bounce"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-lnu-500 animate-bounce" style="animation-delay:150ms"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gold-500 animate-bounce" style="animation-delay:300ms"></span>
        </div>
    </div>
</div>

{{-- FULL NARRATIVE MODAL — summary, risks, next actions, provenance, aggregates reviewed --}}
@if ($showNarrativeModal && $fullNarrative)
    @php($raw = $fullNarrative->raw_extracted_data ?? [])
    <div x-data @keydown.escape.window="$wire.set('showNarrativeModal', false)"
         class="fixed inset-0 z-[60] p-4 sm:p-6 overflow-auto no-print">
        <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[2px]" wire:click="closeNarrativeModal"></div>
        <div class="sc-modal relative max-w-3xl mx-auto my-6 sc-card p-0 shadow-pop overflow-hidden">

            {{-- header --}}
            <div class="relative px-6 py-5 bg-lnu-800 text-white"
                 style="background-image:radial-gradient(circle at 90% -40%, rgba(246,184,0,.35), transparent 46%), radial-gradient(rgba(255,255,255,.12) 1px, transparent 1.4px); background-size:auto, 16px 16px;">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                            <x-sc.icon name="sparkles" class="w-5 h-5 text-gold-300" />
                        </span>
                        <div class="min-w-0">
                            <span class="ai-chip !bg-white/10 !text-blue-100 !border-white/20"><x-sc.icon name="sparkles" class="w-3 h-3" />Executive Program Narrative</span>
                            <h3 class="text-white font-extrabold text-[16px] tracking-tight leading-snug mt-2">{{ $program->title }}</h3>
                            <p class="text-[11.5px] text-blue-100/75 mt-1 font-medium truncate">{{ $program->code }} · {{ $program->communities->pluck('name')->implode(', ') ?: 'No community linked' }} · Lead: {{ $program->programLead?->user?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="narrative-health {{ $fullNarrative->health_label }}">{{ match ($fullNarrative->health_label) { 'on-track' => 'On track', 'at-risk' => 'At risk', default => 'Needs attention' } }}</span>
                        <button wire:click="closeNarrativeModal" class="p-2 rounded-lg text-blue-100/70 hover:bg-white/10 hover:text-white transition" title="Close"><x-sc.icon name="x" class="w-4 h-4" /></button>
                    </div>
                </div>
            </div>

            {{-- meta strip --}}
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/70 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[11px] text-gray-500 font-medium">
                <span>Generated <span class="font-bold text-charcoal">{{ $fullNarrative->generated_at?->format('M j, Y · g:i A') }}</span></span>
                <span>by <span class="font-bold text-charcoal">{{ $fullNarrative->generator?->name ?? 'Director, CESO' }}</span></span>
                <span class="sm:ml-auto flex flex-wrap items-center gap-1.5">
                    <span class="badge badge-gray !text-[10px] font-mono">{{ $fullNarrative->metadata['model'] ?? 'gemini' }}</span>
                    <span class="badge badge-gray !text-[10px]">prompt v{{ $fullNarrative->metadata['prompt_version'] ?? '—' }}</span>
                    <span class="badge badge-blue !text-[10px]" title="{{ $fullNarrative->metadata['confidence_basis'] ?? 'Derived data-confidence heuristic — guidance only' }}">confidence {{ $fullNarrative->confidence_score !== null ? number_format((float) $fullNarrative->confidence_score, 2) : '—' }}</span>
                </span>
            </div>

            {{-- body --}}
            <div class="px-6 py-5 max-h-[62vh] overflow-y-auto space-y-5">
                {{-- snapshot tiles --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
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
                    <p class="flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2"><x-sc.icon name="doc" class="w-3.5 h-3.5" /> Executive Summary</p>
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
                        <summary>Data the AI reviewed <span class="acc-num">{{ $raw['activities']['total'] ?? 0 }} activities · {{ count($raw['objectives']['list'] ?? []) }} objectives<span class="text-gray-300"> ·</span> aggregates only</span><svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg></summary>
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
                                    <span class="font-bold text-gray-500">Period:</span> {{ $raw['program']['period'] ?? '—' }}
                                </div>
                            </div>

                            @if (! empty($raw['objectives']['list']))
                                <div class="mt-3 rounded-lg border border-gray-100 bg-white overflow-hidden">
                                    <p class="px-3 pt-3 text-[10px] font-bold uppercase tracking-wider text-gray-400">Objectives reviewed</p>
                                    <div class="px-3 pb-2 mt-1.5">
                                        @foreach ($raw['objectives']['list'] as $o)
                                            @php($tone = match ($o['status'] ?? '') { 'achieved' => 'badge-green', 'on_track' => 'badge-blue', 'not_met' => 'badge-red', default => 'badge-gray' })
                                            <div class="flex items-start justify-between gap-3 py-1.5 border-b border-dashed border-gray-100 last:border-0">
                                                <span class="text-[11.5px] text-gray-600 leading-snug">{{ $o['objective'] ?? '—' }}</span>
                                                <span class="badge {{ $tone }} !text-[10px] shrink-0">{{ ucwords(str_replace('_', ' ', $o['status'] ?? 'not_started')) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </details>
                @endif
            </div>

            {{-- footer --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-3">
                <p class="text-[10.5px] text-gray-400 leading-snug max-w-lg">Provenance recorded for audit — model, prompt version, and generating officer. Admin-internal decision support; guidance only, not an approval record.</p>
                <button wire:click="closeNarrativeModal" class="btn btn-outline !px-4 !py-2">Close</button>
            </div>
        </div>
    </div>
@endif

