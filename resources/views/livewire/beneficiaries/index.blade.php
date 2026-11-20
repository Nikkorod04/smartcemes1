<div class="beneficiaries-registry-page">
    <section class="pt-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Community Extension Services Office</p>
                <h1 class="secretary-form-title">Beneficiaries</h1>
                <p class="secretary-form-subtitle">
                    One person, one project. Add, correct, archive, and restore beneficiary records; archived records keep their attendance history.
                </p>
            </div>
            @if ($status !== 'archived')
                <button type="button" wire:click="openCreate" class="btn btn-primary">
                    <x-sc.icon name="plus" class="h-4 w-4" /> Add beneficiary
                </button>
            @endif
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-3">
            <div class="sc-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Active</p>
                <p class="mt-2 text-[24px] font-extrabold leading-none">{{ number_format($activeCount) }}</p>
                <p class="mt-1 text-[11px] text-gray-400">beneficiaries in projects</p>
            </div>
            <div class="sc-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Archived</p>
                <p class="mt-2 text-[24px] font-extrabold leading-none text-gray-500">{{ number_format($archivedCount) }}</p>
                <p class="mt-1 text-[11px] text-gray-400">hidden from active rosters</p>
            </div>
            <div class="sc-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Projects</p>
                <p class="mt-2 text-[24px] font-extrabold leading-none">{{ number_format($projectCount) }}</p>
                <p class="mt-1 text-[11px] text-gray-400">beneficiaries must belong to one</p>
            </div>
        </div>
    </section>

    <section class="mt-5 sc-card p-0 overflow-hidden">
        <div class="space-y-3 border-b border-gray-100 px-5 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <div class="sc-search min-w-[220px] flex-1 sm:max-w-xs">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="input !py-2 bg-gray-50 border-transparent focus:bg-white"
                           placeholder="Search name or community…" aria-label="Search beneficiaries">
                </div>
                <select wire:model.live="projectId" class="input !w-auto !py-2 text-[12px]" aria-label="Filter by project">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->code }} — {{ $project->title }}</option>
                    @endforeach
                </select>
                <select wire:model.live="communityId" class="input !w-auto !py-2 text-[12px]" aria-label="Filter by community">
                    <option value="">All communities</option>
                    @foreach ($communities as $community)
                        <option value="{{ $community->id }}">{{ $community->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="category" class="input !w-auto !py-2 text-[12px]" aria-label="Filter by category">
                    <option value="">All categories</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                @foreach (['active' => 'Active', 'archived' => 'Archived', 'all' => 'All'] as $key => $label)
                    <button type="button" wire:click="$set('status', '{{ $key }}')"
                            @class(['chip', 'on' => $status === $key])>{{ $label }}</button>
                @endforeach
                <span class="ml-auto text-[11px] font-medium text-gray-400">
                    {{ $beneficiaries->total() }} {{ \Illuminate\Support\Str::plural('record', $beneficiaries->total()) }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="sc-table min-w-[980px]">
                <thead>
                    <tr>
                        <th>Beneficiary</th>
                        <th>Project</th>
                        <th>Community / Partner School</th>
                        <th>Category</th>
                        <th>Age / Sex</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="!text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($beneficiaries as $beneficiary)
                        <tr wire:key="registry-{{ $beneficiary->id }}">
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="avatar w-7 h-7 text-[10px]">{{ \App\View\Components\Initials::for($beneficiary->fullName()) }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-[12.5px]">{{ $beneficiary->fullName() }}</p>
                                        <p class="text-[10.5px] text-gray-400">{{ $beneficiary->created_at->format('M j, Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-[12px] text-gray-500">{{ $beneficiary->extensionProject?->code ?? '—' }}</td>
                            <td class="text-[12px] text-gray-500">{{ $beneficiary->community?->name ?? $beneficiary->barangay ?? '—' }}</td>
                            <td><span class="badge badge-gray !text-[10px]">{{ $beneficiary->beneficiary_category }}</span></td>
                            <td class="text-[12px] text-gray-500">{{ $beneficiary->age ?? '—' }} · {{ $beneficiary->gender ?? '—' }}</td>
                            <td class="text-[12px] text-gray-500">{{ $beneficiary->phone ?: 'No contact provided' }}</td>
                            <td>
                                @if ($beneficiary->trashed())
                                    <span class="badge badge-gray">Archived</span>
                                @else
                                    <span class="badge badge-green">Active</span>
                                @endif
                            </td>
                            <td class="!text-right whitespace-nowrap">
                                @if ($beneficiary->trashed())
                                    <button type="button" wire:click="restore({{ $beneficiary->id }})" class="btn btn-outline !px-2 !py-1 !text-[11px]">Restore</button>
                                @else
                                    @if (auth()->user()->isSecretary())
                                        <button type="button" wire:click="openEdit({{ $beneficiary->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Edit</button>
                                    @endif
                                    <button type="button" wire:click="archive({{ $beneficiary->id }})"
                                            wire:confirm="Archive {{ $beneficiary->fullName() }}? They disappear from active lists, but attendance history stays."
                                            class="btn btn-danger-soft !px-2 !py-1 !text-[11px]">Archive</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="!py-12 text-center">
                                @if ($search !== '' || $projectId !== '' || $communityId !== '' || $category !== '')
                                    <p class="text-[13px] font-semibold text-gray-400">No beneficiary matches these filters.</p>
                                    <p class="mt-1 text-[11.5px] text-gray-300">Clear a filter or search a different name.</p>
                                @elseif ($status === 'archived')
                                    <p class="text-[13px] font-semibold text-gray-400">No archived beneficiaries.</p>
                                @else
                                    <p class="text-[13px] font-semibold text-gray-400">No beneficiaries yet.</p>
                                    <p class="mt-1 text-[11.5px] text-gray-300">Add one, or import the v2 XLSX template from a project hub.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($beneficiaries->hasPages())
            <div class="border-t border-gray-100 px-5 py-3">
                @include('livewire.partials.pagination', ['paginator' => $beneficiaries])
            </div>
        @endif
    </section>

    {{-- Add / edit ---------------------------------------------------------- --}}
    <div x-data="{ open: false }" x-init="$wire.$watch('showForm', v => open = v)" @keydown.escape.window="$wire.set('showForm', false)"
         x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
         role="dialog" aria-modal="true" aria-labelledby="beneficiary-form-title">
        <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showForm', false)"></div>
        <form wire:submit="save" class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
            <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="people" class="h-5 w-5" /></span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Beneficiary registry</p>
                            <h3 id="beneficiary-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">{{ $editingId ? 'Edit beneficiary' : 'Add beneficiary' }}</h3>
                            <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">One project and one community/partner school are required. Blank contact becomes “No contact provided.”</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeForm" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white" aria-label="Close beneficiary form"><x-sc.icon name="x" class="h-4 w-4" /></button>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="label" for="ben-first">First name <span class="text-red-500">*</span></label><input id="ben-first" maxlength="255" class="input" wire:model="form.first_name">@error('form.first_name') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
                    <div><label class="label" for="ben-middle">Middle name</label><input id="ben-middle" maxlength="255" class="input" wire:model="form.middle_name">@error('form.middle_name') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
                    <div><label class="label" for="ben-last">Last name <span class="text-red-500">*</span></label><input id="ben-last" maxlength="255" class="input" wire:model="form.last_name">@error('form.last_name') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div><label class="label" for="ben-age">Age <span class="text-red-500">*</span></label><input id="ben-age" type="number" min="0" max="120" class="input" wire:model="form.age">@error('form.age') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
                    <div><label class="label" for="ben-gender">Sex</label><x-sc.select id="ben-gender" model="form.gender" :value="$form['gender'] ?? null" :options="['Female' => 'Female', 'Male' => 'Male', 'Prefer not to say' => 'Prefer not to say']" placeholder="Select sex" /></div>
                    <div><label class="label" for="ben-category">Category <span class="text-red-500">*</span></label><x-sc.select id="ben-category" model="form.beneficiary_category" :value="$form['beneficiary_category'] ?? null" :options="collect($categories)->mapWithKeys(fn ($c) => [$c => $c])->all()" placeholder="Select category" /></div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div><label class="label" for="ben-project">Project <span class="text-red-500">*</span></label><x-sc.select id="ben-project" model="form.extension_project_id" :value="$form['extension_project_id'] ?? null" :options="$projects->mapWithKeys(fn ($p) => [$p->id => $p->code.' — '.$p->title])->all()" placeholder="Choose project" :search="$projects->count() > 12" /></div>
                    <div><label class="label" for="ben-community">Community / Partner School <span class="text-red-500">*</span></label><x-sc.select id="ben-community" model="form.community_id" :value="$form['community_id'] ?? null" :options="$formCommunities->mapWithKeys(fn ($c) => [$c->id => $c->name.($c->municipality ? ' — '.$c->municipality : '')])->all()" placeholder="Choose community or school" :search="$formCommunities->count() > 12" /></div>
                </div>
                <p class="mt-2 text-[11px] leading-relaxed text-gray-400">The community must be linked to the chosen project. Moving a beneficiary keeps past attendance under the old activities.</p>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div><label class="label" for="ben-phone">Contact number</label><input id="ben-phone" maxlength="20" inputmode="tel" class="input" wire:model="form.phone" placeholder="0917 123 4567">@error('form.phone') <p class="sc-field-error">{{ $message }}</p> @enderror</div>
                </div>

                @error('form.extension_project_id') <p class="mt-2 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                @error('form.community_id') <p class="mt-2 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[10.5px] font-medium text-gray-400"><span class="text-red-500">*</span> Required fields</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="closeForm" class="btn btn-ghost">Cancel</button>
                        <button type="submit" class="btn btn-primary min-w-[142px]" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save changes' : 'Add beneficiary' }}</span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                        </button>
                    </div>
                </div>
            </footer>
        </form>
    </div>
</div>
