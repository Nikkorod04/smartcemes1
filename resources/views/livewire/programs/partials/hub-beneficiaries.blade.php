<div class="mt-4 space-y-4">
    <section class="grid grid-cols-4 gap-4">
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Beneficiaries</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $enrolledCount }}<span class="text-[14px] text-gray-400 font-bold"> / {{ $program->target_beneficiaries ?? '—' }}</span></p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">active in this project</p>
        </div>
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Reached</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none text-lnu-800">{{ number_format($performance['trainees']) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">distinct beneficiaries served</p>
        </div>
        <div class="sc-card p-5 col-span-2 !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/40">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Manage Beneficiaries</p>
            <p class="text-[12.5px] text-gray-600 mt-2 leading-relaxed">
                Add one person directly, import the official XLSX template, or print the project roster as a PDF.
                Archiving hides a person from this roster; attendance history stays.
            </p>
            @if ($canManageBeneficiaries)
                <div class="flex flex-wrap gap-2 mt-3">
                    <button wire:click="openRegister" class="btn btn-primary !py-1.5 !text-[11.5px]"><x-sc.icon name="people" class="w-4 h-4" /> Add beneficiary</button>
                    <button wire:click="openImport" class="btn btn-outline !py-1.5 !text-[11.5px]"><x-sc.icon name="upload" class="w-4 h-4" /> Import beneficiaries</button>
                    <a href="{{ route('beneficiaries.export', $program) }}" class="btn btn-outline !py-1.5 !text-[11.5px]"><x-sc.icon name="download" class="w-4 h-4" /> Export PDF</a>
                    <a href="{{ route('beneficiaries.index') }}" class="btn btn-ghost !py-1.5 !text-[11.5px]">All beneficiaries</a>
                </div>
            @endif
        </div>
    </section>

    <div class="sc-card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-bold text-[14px]">Beneficiaries in this project</h3>
                <span class="badge badge-gray">{{ $enrolledCount }} active</span>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <div class="sc-search min-w-[220px] flex-1 sm:max-w-xs">
                    <span class="sc-search__icon"><x-sc.icon name="search" class="w-4 h-4" /></span>
                    <input type="text" wire:model.live.debounce.300ms="beneficiarySearch"
                           class="input !py-2 bg-gray-50 border-transparent focus:bg-white"
                           placeholder="Search name or community…" aria-label="Search beneficiaries">
                </div>
                <select wire:model.live="beneficiaryCommunityId" class="input !w-auto !py-2 text-[12px]" aria-label="Filter by community">
                    <option value="">All communities</option>
                    @foreach ($projectCommunities as $community)
                        <option value="{{ $community->id }}">{{ $community->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="beneficiaryCategory" class="input !w-auto !py-2 text-[12px]" aria-label="Filter by category">
                    <option value="">All categories</option>
                    @foreach ($beneficiaryCategories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
                @if ($beneficiarySearch !== '' || $beneficiaryCommunityId !== '' || $beneficiaryCategory !== '')
                    <button type="button" wire:click="clearBeneficiaryFilters" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">Clear</button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr><th>Beneficiary</th><th>Community</th><th>Category</th><th>Age / Sex</th><th>Contact</th><th class="!text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse ($enrolledPaginator as $b)
                        <tr wire:key="ben-{{ $b->id }}">
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="avatar w-7 h-7 text-[10px]">{{ \App\View\Components\Initials::for($b->fullName()) }}</span>
                                    <span class="font-semibold">{{ $b->fullName() }}</span>
                                </div>
                            </td>
                            <td class="text-gray-500">{{ $b->community?->name ?? $b->barangay ?? '—' }}</td>
                            <td><span class="badge badge-gray">{{ $b->beneficiary_category }}</span></td>
                            <td class="text-gray-500">{{ $b->age ?? '—' }} · {{ $b->gender ?? '—' }}</td>
                            <td class="text-gray-500">{{ $b->phone ?: 'No contact provided' }}</td>
                            <td class="!text-right whitespace-nowrap">
                                @if ($canEditBeneficiaries)
                                    <button wire:click="openBeneficiaryEdit({{ $b->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">
                                        <x-sc.icon name="edit" class="w-3.5 h-3.5" /> Edit
                                    </button>
                                @endif
                                @if ($canManageBeneficiaries)
                                    <button wire:click="archiveBeneficiary({{ $b->id }})"
                                            wire:confirm="Archive {{ $b->fullName() }}? They disappear from this roster, but attendance history stays."
                                            class="btn btn-danger-soft !px-2 !py-1 !text-[11px]">Archive</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($enrolledCount === 0)
                        <tr><td colspan="6" class="text-center text-gray-400 py-8">
                            @if ($canManageBeneficiaries)
                                No beneficiaries match. Add one, import the template, or clear the filters.
                            @else
                                No beneficiaries are listed for this project.
                            @endif
                        </td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($enrolledCount > 0)
            <div class="px-5 py-3 border-t border-gray-100">
                @include('livewire.partials.pagination', ['paginator' => $enrolledPaginator])
            </div>
        @endif
    </div>
</div>
