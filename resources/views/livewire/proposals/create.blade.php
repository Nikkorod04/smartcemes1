<div>
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Submit an activity proposal under an existing project · routed to the Director for approval</p>
</section>

<section class="mt-2 grid grid-cols-3 gap-4 reveal-item">
    <form wire:submit="save" class="sc-card p-6 col-span-2">
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="label">Proposal title *</label>
                <input required class="input" wire:model="form.title" placeholder="e.g. GULAYAN SA PAARALAN: School Vegetable Gardening Project">
                @error('form.title') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Target project *</label>
                <select required class="input" wire:model="form.extension_project_id">
                    <option value="">— select project —</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}">{{ $p->code }} · {{ $p->title }}</option>
                    @endforeach
                </select>
                @error('form.extension_project_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Target community *</label>
                <select required class="input" wire:model="form.community_id">
                    <option value="">— select community —</option>
                    @foreach ($communities as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} · {{ $c->municipality }}</option>
                    @endforeach
                </select>
                @error('form.community_id') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Proposed start *</label>
                <input required type="date" class="input" wire:model="form.proposed_start_date">
                @error('form.proposed_start_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Proposed end *</label>
                <input required type="date" class="input" wire:model="form.proposed_end_date">
                @error('form.proposed_end_date') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Budget estimate (₱)</label>
                <input type="number" min="0" step="0.01" class="input" wire:model="form.budget_estimate" placeholder="e.g. 36500">
                @error('form.budget_estimate') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="label">Description</label>
                <textarea rows="3" class="input" wire:model="form.description" placeholder="What will this activity deliver?"></textarea>
            </div>
            <div class="col-span-2">
                <label class="label">Attachments (up to 5 · pdf/jpg/png/docx/xlsx · max 10 MB each)</label>
                <input type="file" multiple wire:model="attachments" accept="{{ '.'.implode(',.', $mimes) }}" class="input !py-2.5 bg-gray-50">
                @error('attachments.*') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                @error('attachments') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3">
                <x-sc.icon name="clock" class="w-4 h-4 text-lnu-600 shrink-0 mt-0.5" />
                <p class="text-[12px] text-gray-500 leading-snug">Proposed dates are validated against the target project's range <b>before approval</b> — proposals outside the range cannot be approved (8.8).</p>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">Submit proposal</button>
        </div>
    </form>

    <div class="space-y-4">
        <div class="sc-card p-5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-3">My recent proposals</p>
            @forelse ($myProposals as $p)
                <div class="kv" wire:key="myprop-{{ $p->id }}">
                    <span class="k truncate">{{ \Illuminate\Support\Str::limit($p->title, 34) }}</span>
                    <span class="v"><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span></span>
                </div>
            @empty
                <p class="text-[12px] text-gray-400 italic">No proposals yet.</p>
            @endforelse
        </div>
        <div class="border-l-4 border-gold-500 bg-gold-50 rounded-r-xl p-3.5">
            <p class="text-[12px] font-extrabold text-gold-800">After approval</p>
            <p class="text-[11px] text-gold-700/80 font-medium mt-1">Approved proposals auto-create a draft activity in the project hub. Your proposal then enters the assessment stage — the Secretary validates resulting submissions.</p>
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
