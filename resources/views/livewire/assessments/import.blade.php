<div class="secretary-assessment-import-page">
@php
    $communityOptions = $communities->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.$c->municipality])->all();
    $quarterOptions = [1 => 'Q1 · Jan–Mar', 2 => 'Q2 · Apr–Jun', 3 => 'Q3 · Jul–Sep', 4 => 'Q4 · Oct–Dec'];
    $years = range(now()->year - 5, now()->year + 5);
    $yearOptions = array_combine($years, $years);
    $recordCommunity = $communities->find($communityId)?->name;
    $selectedBatchIndexes = array_map('intval', $selectedBatchFiles);
    $batchReadyCount = collect($batchResults)->whereIn('status', ['ready', 'warning'])->count();
    $batchWarningCount = collect($batchResults)->where('status', 'warning')->count();
    $batchFailedCount = collect($batchResults)->where('status', 'failed')->count();
@endphp

<section class="pt-6">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
        <div>
            <a href="{{ route('assessments.create') }}" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-lnu-700 hover:text-lnu-900 transition mb-2 -ml-1">
                <x-sc.icon name="chevron" class="w-3.5 h-3.5 rotate-90" /> Back to encoding form
            </a>
            <h1 class="secretary-form-title">{{ $batchMode ? 'Batch import assessments' : 'Import from Official Template' }}</h1>
            <p class="secretary-form-subtitle">{{ $batchMode ? 'Upload multiple one-respondent templates, review each result, then import the selected records.' : 'One file = one respondent · parsed values are previewed for confirmation before anything is saved' }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if ($batchMode)
                <button type="button" wire:click="enableSingleMode" class="btn btn-ghost !py-2 !px-3 text-[12px] shrink-0">Single-file import</button>
            @else
                <button type="button" wire:click="enableBatchMode" class="btn btn-outline !py-2 !px-3 text-[12px] shrink-0"><x-sc.icon name="upload" class="w-4 h-4" /> Import multiple files</button>
            @endif
            <a href="{{ route('assessments.template') }}" class="btn btn-outline !py-2 !px-3 text-[12px] shrink-0"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a>
        </div>
    </div>
</section>

<section class="mt-2 reveal-item {{ $batchMode ? 'max-w-5xl' : 'max-w-3xl' }}">
    <div class="sc-card px-6 py-4 flex items-center">
        <div class="flex flex-col items-center gap-1.5 shrink-0 w-[125px]">
            <span @class(['step-dot', 'on' => $step === 'upload', 'done' => $step === 'preview'])>1</span>
            <span @class(['text-[10px] font-bold tracking-wide text-center leading-tight', 'text-lnu-800' => $step === 'upload', 'text-gold-700' => $step === 'preview'])>{{ $batchMode ? 'Upload files' : 'Upload' }}</span>
        </div>
        <div @class(['step-line', 'done' => $step === 'preview'])></div>
        <div class="flex flex-col items-center gap-1.5 shrink-0 w-[125px]">
            <span @class(['step-dot', 'on' => $step === 'preview'])>2</span>
            <span @class(['text-[10px] font-bold tracking-wide text-center leading-tight', 'text-lnu-800' => $step === 'preview', 'text-gray-400' => $step === 'upload'])>Review &amp; Confirm</span>
        </div>
    </div>

    <div class="sc-card p-6 mt-4">
        <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-5">
            <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
            <p class="text-[12px] text-gray-500 leading-snug">Use the official vertical template. Values that do not match standard lists may be mapped to <b>Other</b> with the original text preserved. Field warnings do not block an import, but unreadable or unsupported files are excluded from the batch.</p>
        </div>

        @if ($step === 'upload')
            <div class="space-y-5">
                <div>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <p class="font-extrabold text-[13px]">Record context</p>
                        @if ($batchMode)
                            <span class="badge badge-blue">Up to {{ \App\Livewire\Assessments\Import::MAX_BATCH_FILES }} files · 50 MB total</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="label">Community *</label>
                            <x-sc.select model="communityId" :value="$communityId" :options="$communityOptions" placeholder="— select community —" />
                            @error('communityId') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Quarter</label>
                            <x-sc.select model="quarter" :value="$quarter" :options="$quarterOptions" />
                        </div>
                        <div>
                            <label class="label">Year *</label>
                            <x-sc.select model="year" :value="$year" :options="$yearOptions" placeholder="— year —" />
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">A file's Community, Quarter, and Year context overrides these fallback values when filled in the template.</p>
                </div>

                @if ($batchMode)
                    <div>
                        <label class="label">Official templates (.xlsx) *</label>
                        <label class="group block cursor-pointer">
                            <input type="file" wire:model="files" multiple accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden">
                            @php
                                $dropClasses = $errors->has('files')
                                    ? 'border-red-300 bg-red-50/40'
                                    : 'border-gray-200 bg-gray-50/50 group-hover:border-lnu-300 group-hover:bg-lnu-50/30';
                            @endphp
                            <div @class(['border-2 border-dashed rounded-xl px-6 py-8 text-center transition', $dropClasses])>
                                <span class="mx-auto w-12 h-12 rounded-2xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="upload" class="w-6 h-6" /></span>
                                <p class="mt-3 font-bold text-[13.5px]">Click to choose multiple .xlsx files</p>
                                <p class="text-[11.5px] text-gray-400 mt-1">One respondent per file · 10 MB maximum per file</p>
                            </div>
                        </label>
                        @error('files') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        @if ($files)
                            <div class="mt-3 rounded-xl border border-gray-100 bg-gray-50/70 divide-y divide-gray-100">
                                @foreach ($files as $index => $uploadedFile)
                                    <div class="flex items-center justify-between gap-3 px-3 py-2.5 text-[12px]">
                                        <div class="flex min-w-0 items-center gap-2"><x-sc.icon name="doc" class="w-4 h-4 shrink-0 text-lnu-600" /><span class="truncate font-semibold text-charcoal">{{ $uploadedFile?->getClientOriginalName() }}</span></div>
                                        <span class="shrink-0 text-[11px] text-gray-400">{{ number_format(($uploadedFile?->getSize() ?? 0) / 1048576, 2) }} MB</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <p class="text-[11px] text-gray-400">Files with unreadable templates will be listed as failed and will not be saved.</p>
                        <button wire:click="parse" wire:loading.attr="disabled" wire:target="parse" class="btn btn-primary">
                            <span wire:loading.remove wire:target="parse">Review batch →</span>
                            <span wire:loading wire:target="parse" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4 animate-spin" /> Reading files…</span>
                        </button>
                    </div>
                @else
                    <div>
                        <label class="label">Official template (.xlsx) *</label>
                        <label class="group block cursor-pointer">
                            <input type="file" wire:model="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden">
                            @php
                                $dropClasses = $errors->has('file')
                                    ? 'border-red-300 bg-red-50/40'
                                    : 'border-gray-200 bg-gray-50/50 group-hover:border-lnu-300 group-hover:bg-lnu-50/30';
                            @endphp
                            <div @class(['border-2 border-dashed rounded-xl px-6 py-8 text-center transition', $dropClasses])>
                                @if ($file)
                                    <span class="mx-auto w-12 h-12 rounded-2xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="doc" class="w-6 h-6" /></span>
                                    <p class="mt-3 font-bold text-[13.5px] text-charcoal truncate max-w-md mx-auto">{{ $file?->getClientOriginalName() }}</p>
                                    <p class="text-[11.5px] text-gray-400 mt-0.5">Click to replace the file</p>
                                @else
                                    <span class="mx-auto w-12 h-12 rounded-2xl bg-lnu-50 text-lnu-700 flex items-center justify-center"><x-sc.icon name="upload" class="w-6 h-6" /></span>
                                    <p class="mt-3 font-bold text-[13.5px]">Click to browse for your .xlsx file</p>
                                    <p class="text-[11.5px] text-gray-400 mt-1">Max 10 MB · official template only · .xlsx</p>
                                @endif
                            </div>
                        </label>
                        @error('file') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <p class="text-[11px] text-gray-400">No template yet? <a href="{{ route('assessments.template') }}" class="font-bold text-lnu-700 hover:text-lnu-900">Download it here</a> — don't rename the field labels.</p>
                        <button wire:click="parse" wire:loading.attr="disabled" wire:target="parse" class="btn btn-primary">
                            <span wire:loading.remove wire:target="parse">Parse file →</span>
                            <span wire:loading wire:target="parse" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4 animate-spin" /> Reading file…</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        @if ($step === 'preview' && ! $batchMode)
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-100 bg-gray-50 p-3">
                    <x-sc.icon name="doc" class="w-4 h-4 text-lnu-600 shrink-0" />
                    <p class="text-[12px] text-gray-500 leading-snug">Imported from <b>{{ $file?->getClientOriginalName() }}</b> — will be recorded as <b>pending</b> under:</p>
                    <span class="badge badge-blue">{{ $recordCommunity ?? '—' }}</span>
                    <span class="badge badge-blue">Q{{ $quarter }} {{ $year }}</span>
                </div>

                @if ($otherMapped)
                    <div class="rounded-xl border border-gold-200 bg-gold-50/40 p-3">
                        <p class="text-[10.5px] font-bold uppercase tracking-wide text-gold-800 mb-2">Auto-mapped to "Other" (D9)</p>
                        @foreach ($otherMapped as $field => $texts)
                            @foreach ($texts as $t)
                                <div class="kv"><span class="k">{{ ucwords(str_replace('_', ' ', $field)) }}</span><span class="v !text-gold-700">“{{ $t }}” <span class="text-[10px]">(kept as Other)</span></span></div>
                            @endforeach
                        @endforeach
                        <p class="text-[11px] text-gray-400 mt-2 italic">Raw text is preserved in other_text for audit.</p>
                    </div>
                @endif

                @if ($fieldErrors)
                    <div class="rounded-xl border border-red-200 bg-red-50/70 p-3">
                        <p class="text-[10.5px] font-bold uppercase tracking-wide text-red-500 mb-2">Fields needing attention <span class="badge badge-red !text-[10px] ml-1">{{ count($fieldErrors) }} — on-screen only</span></p>
                        @foreach ($fieldErrors as $field => $err)
                            <div class="kv"><span class="k">{{ ucwords(str_replace('_', ' ', $field)) }}</span><span class="v !text-red-600">{{ $err }}</span></div>
                        @endforeach
                        <p class="text-[11px] text-red-500/80 mt-2 italic">Import never rejects the whole file — unmapped fields are dropped and listed here.</p>
                    </div>
                @endif

                <div class="rounded-xl border border-gray-100 p-3 max-h-72 overflow-y-auto">
                    <p class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2">Parsed values</p>
                    <div class="grid grid-cols-2 gap-x-6">
                        @forelse ($record as $field => $value)
                            <div class="kv"><span class="k">{{ ucwords(str_replace('_', ' ', $field)) }}</span><span class="v">{{ is_array($value) ? implode(', ', array_filter($value, 'is_string')) : $value }}</span></div>
                        @empty
                            <p class="text-[12px] text-gray-400 italic col-span-2">No mappable values were found in the data row.</p>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <button wire:click="$set('step', 'upload')" class="btn btn-ghost">← Back to upload</button>
                    <button wire:click="confirm" wire:loading.attr="disabled" wire:target="confirm" class="btn btn-primary">Confirm & create record</button>
                </div>
            </div>
        @endif

        @if ($step === 'preview' && $batchMode)
            <div class="space-y-4">
                <div class="grid grid-cols-4 gap-3">
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Files</p><p class="mt-1 text-xl font-extrabold text-charcoal">{{ count($batchResults) }}</p></div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">Ready</p><p class="mt-1 text-xl font-extrabold text-emerald-800">{{ $batchReadyCount }}</p></div>
                    <div class="rounded-xl border border-gold-200 bg-gold-50/60 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-gold-700">Warnings</p><p class="mt-1 text-xl font-extrabold text-gold-800">{{ $batchWarningCount }}</p></div>
                    <div class="rounded-xl border border-red-200 bg-red-50/60 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-red-600">Failed</p><p class="mt-1 text-xl font-extrabold text-red-700">{{ $batchFailedCount }}</p></div>
                </div>

                @error('batch') <p class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-[12px] text-red-700">{{ $message }}</p> @enderror

                <div class="overflow-hidden rounded-xl border border-gray-100">
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-3">
                        <div><p class="text-[12px] font-extrabold text-charcoal">Review files before importing</p><p class="text-[11px] text-gray-400">Warnings may be imported; failed files must be corrected and uploaded again.</p></div>
                        <div class="flex items-center gap-2"><span class="badge badge-blue">{{ count($selectedBatchIndexes) }} selected</span>@if ($batchFailedCount > 0)<button type="button" wire:click="downloadBatchErrors" class="btn btn-ghost !px-2.5 !py-1.5 text-[11px]">Download errors</button>@endif</div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach ($batchResults as $item)
                            @php
                                $itemRecord = $item['record'] ?? [];
                                $respondent = trim(collect([$itemRecord['respondent_first_name'] ?? '', $itemRecord['respondent_middle_name'] ?? '', $itemRecord['respondent_last_name'] ?? ''])->filter()->implode(' '));
                                $itemCommunity = $item['community_name'] ?? ($communities->find($item['community_id'] ?? 0)?->name ?? '—');
                                $isSelectable = in_array($item['status'], ['ready', 'warning'], true);
                            @endphp
                            <details class="group" wire:key="batch-file-{{ $item['index'] }}">
                                <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 hover:bg-gray-50">
                                    @if ($isSelectable)
                                        <input type="checkbox" value="{{ $item['index'] }}" wire:model="selectedBatchFiles" @checked(in_array((int) $item['index'], $selectedBatchIndexes, true)) class="h-4 w-4 rounded border-gray-300 text-lnu-700 focus:ring-lnu-500" onclick="event.stopPropagation()" aria-label="Select {{ $item['filename'] }}">
                                    @else
                                        <span class="h-4 w-4 shrink-0 rounded border border-gray-200 bg-gray-100"></span>
                                    @endif
                                    <x-sc.icon name="doc" class="h-4 w-4 shrink-0 text-lnu-600" />
                                    <span class="min-w-0 flex-1 truncate text-[12px] font-semibold text-charcoal">{{ $item['filename'] }}</span>
                                    @if ($item['status'] === 'ready')
                                        <span class="badge badge-green">Ready</span>
                                    @elseif ($item['status'] === 'warning')
                                        <span class="badge badge-yellow">Warning</span>
                                    @else
                                        <span class="badge badge-red">Failed</span>
                                    @endif
                                    <span class="text-gray-400 transition group-open:rotate-180">⌄</span>
                                </summary>
                                <div class="border-t border-gray-100 bg-gray-50/50 px-11 py-3">
                                    <div class="grid grid-cols-3 gap-3 text-[11px]">
                                        <div><span class="block text-gray-400">Respondent</span><span class="font-semibold text-charcoal">{{ $respondent !== '' ? $respondent : '—' }}</span></div>
                                        <div><span class="block text-gray-400">Context</span><span class="font-semibold text-charcoal">{{ $itemCommunity }} · Q{{ $item['quarter'] }} {{ $item['year'] ?: '—' }}</span></div>
                                        <div><span class="block text-gray-400">Parsed fields</span><span class="font-semibold text-charcoal">{{ count($itemRecord) }}</span></div>
                                    </div>
                                    @if ($item['other_mapped'])
                                        <p class="mt-3 text-[11px] text-gold-700"><b>{{ count($item['other_mapped'], COUNT_RECURSIVE) - count($item['other_mapped']) }}</b> value(s) will be mapped to Other.</p>
                                    @endif
                                    @if ($item['field_errors'])
                                        <div class="mt-3 rounded-lg border border-gold-200 bg-gold-50/60 p-2.5 text-[11px] text-gold-800"><b>Field warnings:</b> {{ implode(' · ', $item['field_errors']) }}</div>
                                    @endif
                                    @if ($item['file_errors'])
                                        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-2.5 text-[11px] text-red-700"><b>File errors:</b> {{ implode(' · ', $item['file_errors']) }}</div>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <button wire:click="backToBatchUpload" class="btn btn-ghost">← Back to upload</button>
                    <button wire:click="openBatchConfirm" class="btn btn-primary">Import {{ count($selectedBatchIndexes) }} selected</button>
                </div>
            </div>
        @endif
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Batch confirmation modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showBatchConfirm', v => open = v)" @keydown.escape.window="$wire.cancelBatchConfirm()" x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="batch-confirm-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.cancelBatchConfirm()"></div>
    <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="upload" class="h-5 w-5" /></span><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Batch import</p><h3 id="batch-confirm-title" class="mt-1 text-[18px] font-extrabold tracking-tight">Confirm assessment import</h3><p class="mt-1 text-[12px] font-medium leading-relaxed text-white/72">Selected records will be created as pending and sent to the Secretary review queue.</p></div></div><button type="button" wire:click="cancelBatchConfirm" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white" aria-label="Close batch confirmation"><x-sc.icon name="x" class="h-4 w-4" /></button></div>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"><div class="rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-[12px] leading-relaxed text-blue-900"><b>{{ count($selectedBatchIndexes) }} record(s)</b> will be imported. Their source XLSX files will be archived for audit.</div><div class="mt-4 rounded-xl border border-gray-100 bg-gray-50/70 p-4"><p class="text-[12px] font-bold text-charcoal mb-1.5">On confirm:</p><ul class="space-y-1.5 text-[12px] text-gray-500 leading-relaxed"><li class="flex items-start gap-2"><span class="text-emerald-500"><x-sc.icon name="check" class="w-4 h-4" /></span>Each file is saved independently</li><li class="flex items-start gap-2"><span class="text-emerald-500"><x-sc.icon name="check" class="w-4 h-4" /></span>Every created record starts as pending</li><li class="flex items-start gap-2"><span class="text-emerald-500"><x-sc.icon name="check" class="w-4 h-4" /></span>Failures are reported without hiding successful imports</li></ul></div></div>
        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" wire:click="cancelBatchConfirm">Cancel</button><button type="button" class="btn btn-primary min-w-[160px]" wire:click="confirmBatch" wire:loading.attr="disabled" wire:target="confirmBatch"><span wire:loading.remove wire:target="confirmBatch">Confirm &amp; import</span><span wire:loading wire:target="confirmBatch" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Importing…</span></button></div></div>
    </section>
</div>

{{-- Batch result modal --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showBatchResult', v => open = v)" @keydown.escape.window="$wire.closeBatchResult()" x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6 no-print" role="dialog" aria-modal="true" aria-labelledby="batch-result-title">
    <div class="fixed inset-0 sc-modal-backdrop" @click="$wire.closeBatchResult()"></div>
    <section class="sc-modal relative z-10 flex w-full max-w-xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop">
        @php($resultIsSuccess = $batchResultType === 'success')
        @php($resultIsPartial = $batchResultType === 'partial')
        <header class="shrink-0 border-b border-gray-100 {{ $resultIsSuccess ? 'bg-gradient-to-br from-emerald-700 to-emerald-600' : ($resultIsPartial ? 'bg-gradient-to-br from-gold-700 to-gold-600' : 'bg-gradient-to-br from-red-700 to-red-600') }} px-5 py-5 text-white sm:px-6">
            <div class="flex items-start justify-between gap-4"><div class="flex min-w-0 items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15"><x-sc.icon name="{{ $resultIsSuccess ? 'check' : 'alert' }}" class="h-5 w-5" /></span><div><p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">Batch import result</p><h3 id="batch-result-title" class="mt-1 text-[18px] font-extrabold tracking-tight">{{ $resultIsSuccess ? 'Batch import completed' : ($resultIsPartial ? 'Completed with issues' : 'Batch import failed') }}</h3><p class="mt-1 text-[12px] font-medium leading-relaxed text-white/80">{{ $resultIsSuccess ? 'All selected assessment records were created successfully.' : ($resultIsPartial ? 'Some records were imported, while other files need attention.' : 'No selected records were created.') }}</p></div></div><button type="button" wire:click="closeBatchResult" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white" aria-label="Close batch result"><x-sc.icon name="x" class="h-4 w-4" /></button></div>
        </header>
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6"><div class="grid grid-cols-3 gap-3"><div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Imported</p><p class="mt-1 text-xl font-extrabold text-emerald-700">{{ $batchResultSummary['imported'] ?? 0 }}</p></div><div class="rounded-xl border border-red-100 bg-red-50/60 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-red-500">Failed</p><p class="mt-1 text-xl font-extrabold text-red-700">{{ $batchResultSummary['failed'] ?? 0 }}</p></div><div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3"><p class="text-[10px] font-bold uppercase tracking-wide text-gray-400">Skipped</p><p class="mt-1 text-xl font-extrabold text-gray-600">{{ $batchResultSummary['skipped'] ?? 0 }}</p></div></div><p class="mt-4 text-[12px] leading-relaxed text-gray-500">Imported records are now <b>pending</b> and will appear in the Secretary assessment review queue.</p></div>
        <div class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6"><div class="flex flex-wrap justify-end gap-2"><button type="button" class="btn btn-ghost" wire:click="resetBatch">Import another batch</button>@if (($batchResultSummary['failed'] ?? 0) > 0)<button type="button" class="btn btn-outline" wire:click="downloadBatchErrors">Download error report</button>@endif @if (auth()->user()?->isSecretary())<a href="{{ route('assessments.review') }}" class="btn btn-primary">Open review queue</a>@else<button type="button" class="btn btn-primary" wire:click="closeBatchResult">Done</button>@endif</div></div>
    </section>
</div>
</div>
