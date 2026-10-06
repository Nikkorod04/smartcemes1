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
            <label class="relative flex-1 min-w-[220px] max-w-xs">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 inline-flex">
                    <x-sc.icon name="search" class="w-4 h-4" />
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" class="input !pl-9"
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
        <div class="fixed inset-0 z-50 p-6 overflow-auto no-print">
            <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" wire:click="closeForm"></div>

            <div class="relative max-w-2xl mx-auto mt-10 sc-card p-0 shadow-pop overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-extrabold text-[16px] tracking-tight leading-tight">
                        {{ $editingId ? 'Edit Faculty Profile' : 'New Faculty Profile' }}
                    </h3>
                    <button type="button" wire:click="closeForm"
                            class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 transition"><x-sc.icon name="x" class="w-4 h-4" /></button>
                </div>

                <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Full name <span class="req">*</span></label>
                            <input type="text" wire:model="form.name" class="input" required>
                            @error('form.name') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Email address <span class="req">*</span></label>
                            <input type="email" wire:model="form.email" class="input" required>
                            @error('form.email') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Employee ID</label>
                            <input type="text" wire:model="form.employee_id" class="input font-mono"
                                   placeholder="Auto-generated if left blank">
                            @error('form.employee_id') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">College <span class="req">*</span></label>
                            <select wire:model="form.college_id" class="input">
                                <option value="">— Select —</option>
                                @foreach ($colleges as $college)
                                    <option value="{{ $college->id }}">{{ $college->code }} — {{ $college->name }}</option>
                                @endforeach
                            </select>
                            @error('form.college_id') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Position</label>
                            <select wire:model="form.position" class="input">
                                <option value="">— Select —</option>
                                @foreach ($positionLadder as $rung)
                                    <option value="{{ $rung['label'] }}">{{ $rung['label'] }}</option>
                                @endforeach
                            </select>
                            @error('form.position') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select wire:model="form.status" class="input">
                                @foreach (\App\Models\Faculty::STATUS_LABELS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('form.status') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Specialization</label>
                            <input type="text" wire:model="form.specialization" class="input">
                        </div>
                        <div>
                            <label class="label">Department</label>
                            <input type="text" wire:model="form.department" class="input">
                        </div>
                        <div>
                            <label class="label">Contact number</label>
                            <input type="text" wire:model="form.phone" class="input">
                        </div>
                        <div>
                            <label class="label">Address</label>
                            <input type="text" wire:model="form.address" class="input">
                        </div>
                    </div>

                    <div>
                        <label class="label">
                            Expertise areas
                            <span class="text-gray-400 font-normal">(select all that apply — used to match faculty to projects)</span>
                        </label>
                        <x-sc.multi-select
                            :options="collect($expertiseOptions)->map(fn ($area) => ['id' => $area, 'label' => $area])->all()"
                            :selected="$form['expertise']"
                            method="toggleExpertise"
                            key="faculty-expertise"
                            placeholder="Type to filter, then pick from the list…" />
                        @error('form.expertise') <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="btn btn-outline">Cancel</button>
                    <button type="button" wire:click="save" class="btn btn-primary">
                        {{ $editingId ? 'Save changes' : 'Create profile' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
