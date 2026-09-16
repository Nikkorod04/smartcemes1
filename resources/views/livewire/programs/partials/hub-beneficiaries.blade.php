<div class="mt-4 space-y-4">
    <section class="grid grid-cols-4 gap-4">
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Enrolled</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none">{{ $program->beneficiaries->count() }}<span class="text-[14px] text-gray-400 font-bold"> / {{ $program->target_beneficiaries ?? '—' }}</span></p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">enrolled of program target</p>
        </div>
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Reached</p>
            <p class="mt-3 text-[22px] font-extrabold tracking-tight leading-none text-lnu-800">{{ $kpi->communityReach($program) }}</p>
            <p class="text-[11px] text-gray-400 font-medium mt-1">distinct beneficiaries served</p>
        </div>
        <div class="sc-card p-5 col-span-2 !border-lnu-100 !bg-gradient-to-br !from-white !to-lnu-50/40">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Enrollment is program-scoped</p>
            <p class="text-[12.5px] text-gray-600 mt-2 leading-relaxed">Enroll from the global registry, register new beneficiaries directly into this program, or unenroll. A beneficiary may belong to multiple programs; duplicates are warned before save (never silently merged).</p>
            @if ($canManageBeneficiaries)
                <div class="flex gap-2 mt-3">
                    <button wire:click="openEnroll" class="btn btn-outline !py-1.5 !text-[11.5px]">Enroll existing</button>
                    <button wire:click="openRegister" class="btn btn-primary !py-1.5 !text-[11.5px]"><x-sc.icon name="people" class="w-4 h-4" /> Register new</button>
                    <button wire:click="openImport" class="btn btn-outline !py-1.5 !text-[11.5px]"><x-sc.icon name="upload" class="w-4 h-4" /> Import XLSX</button>
                </div>
            @endif
        </div>
    </section>
    <div class="sc-card p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-[14px]">Enrolled Beneficiaries</h3>
            <span class="badge badge-gray">{{ $program->beneficiaries->count() }} enrolled</span>
        </div>
        <table class="sc-table">
            <thead><tr><th>Beneficiary</th><th>Barangay</th><th>Category</th><th>Age / Sex</th><th>Contact</th><th class="!text-right">Enrollment</th></tr></thead>
            <tbody>
                @forelse ($program->beneficiaries->sortBy('last_name') as $b)
                    <tr wire:key="ben-{{ $b->id }}">
                        <td><div class="flex items-center gap-2.5"><span class="avatar w-7 h-7 text-[10px]">{{ \App\View\Components\Initials::for($b->fullName()) }}</span><span class="font-semibold">{{ $b->fullName() }}</span></div></td>
                        <td class="text-gray-500">{{ $b->barangay }}</td>
                        <td><span class="badge badge-gray">{{ $b->beneficiary_category }}</span></td>
                        <td class="text-gray-500">{{ $b->age ?? '—' }} · {{ $b->gender ?? '—' }}</td>
                        <td class="text-gray-500">{{ $b->phone ?? '—' }}</td>
                        <td class="!text-right row-actions">
                            @if ($canManageBeneficiaries)
                                <button wire:click="unenroll({{ $b->id }})" wire:confirm="Unenroll this beneficiary from the program?" class="btn btn-danger-soft !px-2 !py-1 !text-[11px]">Unenroll</button>
                            @else
                                <span class="text-[11.5px] text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if ($program->beneficiaries->isEmpty())
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">No beneficiaries enrolled yet{{ $canManageBeneficiaries ? ' — enroll existing, register new, or import from XLSX.' : '.' }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>