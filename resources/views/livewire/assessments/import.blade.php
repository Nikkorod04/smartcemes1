<div>
@php
    $communityOptions = $communities->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.$c->municipality])->all();
    $quarterOptions = [1 => 'Q1 · Jan–Mar', 2 => 'Q2 · Apr–Jun', 3 => 'Q3 · Jul–Sep', 4 => 'Q4 · Oct–Dec'];
    $years = range(now()->year - 5, now()->year + 5);
    $yearOptions = array_combine($years, $years);
    $recordCommunity = $communities->find($communityId)?->name;
@endphp

<section class="pt-6">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
        <div>
            <a href="{{ route('assessments.create') }}" class="inline-flex items-center gap-1.5 text-[12px] font-bold text-lnu-700 hover:text-lnu-900 transition mb-2 -ml-1">
                <x-sc.icon name="chevron" class="w-3.5 h-3.5 rotate-90" /> Back to encoding form
            </a>
            <h1 class="text-[22px] font-extrabold tracking-tight">Import from Official Template</h1>
            <p class="text-[13px] text-gray-400 font-medium mt-0.5">One file = one respondent · parsed values are previewed for confirmation before anything is saved</p>
        </div>
        <a href="{{ route('assessments.template') }}" class="btn btn-outline !py-2 !px-3 text-[12px] shrink-0"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a>
    </div>
</section>

<section class="mt-2 reveal-item max-w-3xl">
    {{-- Step indicator (server-side $step, never bare Alpine — §14) --}}
    <div class="sc-card px-6 py-4 flex items-center">
        <div class="flex flex-col items-center gap-1.5 shrink-0 w-[110px]">
            <span @class(['step-dot', 'on' => $step === 'upload', 'done' => $step === 'preview'])>1</span>
            <span @class(['text-[10px] font-bold tracking-wide text-center leading-tight', 'text-lnu-800' => $step === 'upload', 'text-gold-700' => $step === 'preview'])>Upload</span>
        </div>
        <div @class(['step-line', 'done' => $step === 'preview'])></div>
        <div class="flex flex-col items-center gap-1.5 shrink-0 w-[110px]">
            <span @class(['step-dot', 'on' => $step === 'preview'])>2</span>
            <span @class(['text-[10px] font-bold tracking-wide text-center leading-tight', 'text-lnu-800' => $step === 'preview', 'text-gray-400' => $step === 'upload'])>Review &amp; Confirm</span>
        </div>
    </div>

    <div class="sc-card p-6 mt-4">
        <div class="flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 mb-5">
            <x-sc.icon name="clipboard" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
            <p class="text-[12px] text-gray-500 leading-snug">Use the official template — a vertical form where you fill the shaded answer cells (column B) next to each field label, grouped into Sections I–IX with fill-up guides in column C. Values that don't match the standard lists are auto-mapped to <b>Other</b> with the raw text preserved. Imports never reject the whole file — a per-field error list is shown on screen only.</p>
        </div>

        {{-- Server-side step toggle: Livewire properties are NOT in Alpine
             scope — bare x-show="step === ..." evaluates undefined and hides
             BOTH steps (see §14). --}}
        @if ($step === 'upload')
        <div class="space-y-5">
            <div>
                <p class="font-extrabold text-[13px] mb-3">Record context</p>
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
                <p class="text-[11px] text-gray-400 mt-2">If the template's context block (Community / Quarter / Year) is filled in, it is picked up automatically and overrides these fields.</p>
            </div>

            <div>
                <label class="label">Official template (.xlsx) *</label>
                <label class="group block cursor-pointer">
                    <input type="file" wire:model="file"
                           accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="hidden">
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
                <button wire:click="parse" wire:loading.attr="disabled" class="btn btn-primary">
                    <span wire:loading.remove wire:target="parse">Parse file →</span>
                    <span wire:loading wire:target="parse" class="inline-flex items-center gap-1.5"><x-sc.icon name="loader" class="w-4 h-4 animate-spin" /> Reading file…</span>
                </button>
            </div>
        </div>
        @endif

        @if ($step === 'preview')
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
                    <p class="text-[11px] text-red-500/80 mt-2 italic">Import never rejects the whole file — unmapped fields are dropped and listed here. Fix the template and re-upload, or confirm with the listed gaps.</p>
                </div>
            @endif

            <div class="rounded-xl border border-gray-100 p-3 max-h-72 overflow-y-auto">
                <p class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400 mb-2">Parsed values</p>
                <div class="grid grid-cols-2 gap-x-6">
                    @forelse ($record as $field => $value)
                        <div class="kv">
                            <span class="k">{{ ucwords(str_replace('_', ' ', $field)) }}</span>
                            <span class="v">{{ is_array($value) ? implode(', ', array_filter($value, 'is_string')) : $value }}</span>
                        </div>
                    @empty
                        <p class="text-[12px] text-gray-400 italic col-span-2">No mappable values were found in the data row.</p>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <button wire:click="$set('step', 'upload')" class="btn btn-ghost">← Back to upload</button>
                <button wire:click="confirm" wire:loading.attr="disabled" class="btn btn-primary">Confirm & create record</button>
            </div>
        </div>
        @endif
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
