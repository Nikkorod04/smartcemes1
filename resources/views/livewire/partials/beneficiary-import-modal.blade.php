{{--
    Shared beneficiary v2 XLSX import modal (2026-11-20).

    Used by the project hub (project fixed) and the standalone registry page
    (project chosen in the modal). The host component owns `$showImport`,
    `$importFile`, `$importRows`, `$importPreview`, `parseImport()` and
    `confirmImport()`.
--}}
<div x-data="{ open: false }" x-init="$wire.$watch('showImport', v => open = v)" @keydown.escape.window="$wire.set('showImport', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="import-beneficiary-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.set('showImport', false)"></div>
    <section class="sc-modal relative z-10 flex w-full max-w-3xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="upload" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Beneficiaries</p>
                        <h3 id="import-beneficiary-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Import beneficiaries</h3>
                        <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Review the official v2 template rows before adding them to the project.</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('showImport', false)" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Close beneficiary import dialog"><x-sc.icon name="x" class="h-4 w-4" /></button>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            @if (! $importPreview)
                <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3.5 mb-5">
                    <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                    <p class="text-[12px] text-gray-500 leading-snug">
                        Use the <b>v2 template</b>. Required: First Name, Last Name, <b>Community / Partner School</b>, Beneficiary Category.
                        Contact Number is optional; blank means “No contact provided.” Existing people in the project are <b>updated</b>;
                        people already in another project are <b>skipped</b>. Old templates are rejected — download the new one.
                    </p>
                </div>

                @if ($showImportProjectPicker ?? false)
                    <div class="mb-4">
                        <label class="label" for="import-project">Target project <span class="text-red-500">*</span></label>
                        <x-sc.select id="import-project" model="importProjectId"
                                     :value="$importProjectId ?? null"
                                     :options="($importProjects ?? collect())->mapWithKeys(fn ($p) => [$p->id => $p->code.' — '.$p->title])->all()"
                                     placeholder="Choose the project for this import"
                                     :search="($importProjects ?? collect())->count() > 12"
                                     :invalid="$errors->has('importProjectId')" />
                        @error('importProjectId') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">Every row’s Community / Partner School must already be linked to this project.</p>
                    </div>
                @endif

                <div>
                    <label class="label">Official v2 template (.xlsx) *</label>
                    <input type="file" wire:model="importFile"
                           accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="input !py-2.5 bg-gray-50">
                    @error('importFile') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    <p class="text-[11px] text-gray-400 mt-1">Max 10 MB · up to 500 rows per file · .xlsx only</p>
                </div>

            @else
                @php
                    $previewCounts = collect($importRows)->countBy('status');
                    $previewTarget = $importTargetLabel ?? null;
                @endphp

                <div class="mb-3 flex flex-wrap items-center gap-2">
                    @if ($previewTarget)
                        <p class="mr-1 text-[11.5px] font-medium text-gray-500">Target: <b class="text-charcoal">{{ $previewTarget }}</b></p>
                    @endif
                    @if (($previewCounts['new'] ?? 0) > 0)
                        <span class="badge badge-green">{{ $previewCounts['new'] }} new</span>
                    @endif
                    @if (($previewCounts['update'] ?? 0) > 0)
                        <span class="badge badge-yellow">{{ $previewCounts['update'] }} update</span>
                    @endif
                    @if (($previewCounts['unassigned'] ?? 0) > 0)
                        <span class="badge badge-blue">{{ $previewCounts['unassigned'] }} unassigned</span>
                    @endif
                    @if (($previewCounts['other_project'] ?? 0) > 0)
                        <span class="badge badge-gray">{{ $previewCounts['other_project'] }} other project</span>
                    @endif
                    @if (($previewCounts['error'] ?? 0) > 0)
                        <span class="badge badge-red">{{ $previewCounts['error'] }} skipped</span>
                    @endif
                </div>

                <div class="flex items-start gap-2.5 rounded-xl border border-gray-100 bg-gray-50 p-3 mb-4">
                    <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                    <p class="text-[12px] text-gray-500 leading-snug">
                        Parsed <b>{{ $importFile?->getClientOriginalName() }}</b> — review the rows below, then confirm.
                        <span class="badge badge-green !text-[10px]">New</span> creates,
                        <span class="badge badge-yellow !text-[10px]">Update</span> corrects an existing person in this project,
                        <span class="badge badge-blue !text-[10px]">Unassigned</span> is claimed into this project,
                        <span class="badge badge-gray !text-[10px]">Other project</span> and
                        <span class="badge badge-red !text-[10px]">Skip</span> are not imported.
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100 max-h-80 overflow-y-auto">
                    <table class="sc-table !text-[12px]">
                        <thead><tr><th>#</th><th>Name</th><th>Community</th><th>Category</th><th>Contact</th><th>Status</th></tr></thead>
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
                                    <td class="text-gray-500">{{ $row['community_name'] ?? '—' }}</td>
                                    <td class="text-gray-500">{{ $row['data']['beneficiary_category'] ?? '—' }}</td>
                                    <td class="text-gray-500">{{ $row['data']['phone'] ?: 'No contact provided' }}</td>
                                    <td>
                                        @if ($row['status'] === 'error')
                                            <span class="badge badge-red">Skip</span>
                                        @elseif ($row['status'] === 'update')
                                            <span class="badge badge-yellow">Update</span>
                                        @elseif ($row['status'] === 'unassigned')
                                            <span class="badge badge-blue">Unassigned → assign</span>
                                        @elseif ($row['status'] === 'other_project')
                                            <span class="badge badge-gray">Other project: {{ $row['other_project'] }}</span>
                                        @else
                                            <span class="badge badge-green">New</span>
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
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('beneficiaries.template') }}" class="btn btn-ghost !px-3 text-[12px]"><x-sc.icon name="download" class="w-4 h-4" /> Download v2 template</a>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-ghost" wire:click="$set('showImport', false)">Cancel</button>
                        <button type="button" wire:click="parseImport" wire:loading.attr="disabled" wire:target="parseImport" class="btn btn-primary min-w-[120px]"><span wire:loading.remove wire:target="parseImport">Parse file →</span><span wire:loading wire:target="parseImport" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Parsing…</span></button>
                    </div>
                </div>
            @else
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('importPreview', false)" class="btn btn-ghost">← Back</button>
                    <button type="button" wire:click="confirmImport" wire:loading.attr="disabled" wire:target="confirmImport" class="btn btn-primary min-w-[142px]"><span wire:loading.remove wire:target="confirmImport">Confirm import</span><span wire:loading wire:target="confirmImport" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Importing…</span></button>
                </div>
            @endif
        </footer>
    </section>
</div>
