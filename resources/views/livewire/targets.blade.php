{{--
    University Targets (Phase R4b) — mirrors docs/prototype/pages/targets.html.

    Debts paid here vs the prototype:
      * The §2.2C "model pending" amber banner is DELETED. The target model now
        exists (§4.7), so the banner would be untrue.
      * The prototype's fourth tile ("Activities delivered" vs a
        `targetActivities` that has no backing column) is rendered WITHOUT a
        target bar — R4 defines an annual training-hours pool and an annual
        budget target, and nothing else. Inventing a target here would be the
        exact thing R5/R6 must stop doing.
      * "Edit targets" opens a real form and every save writes an activity-log
        entry, because the page tells the Director edits are logged.
--}}
<div>
    {{-- ===================== HEADER ===================== --}}
    <section class="pt-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-[20px] font-extrabold tracking-tight leading-tight">University Targets</h2>
                <p class="text-[12.5px] text-gray-400 font-medium mt-0.5">
                    The institutional commitments CESO reports against — training hours and budget for
                    <span class="font-semibold text-gray-500">{{ $target?->display_label ?? $selectedLabel }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="year" class="input !py-2 !text-[12.5px] w-auto">
                    @foreach ($yearOptions as $y)
                        <option value="{{ $y }}">{{ $yearLabels[(int) $y] ?? 'AY '.$y.'-'.($y + 1) }}</option>
                    @endforeach
                </select>
                @if (! $showForm)
                    <button wire:click="openForm" class="btn btn-primary">{{ $target ? 'Edit targets' : 'Set targets' }}</button>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== EDIT FORM ===================== --}}
    @if ($showForm)
        <section class="mt-4">
            <form wire:submit="save" class="sc-card p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-bold text-[14px]">{{ $target ? 'Edit' : 'Set' }} annual target · {{ $selectedLabel }}</h3>
                        <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Every edit is logged with your name and timestamp (8.1). Leave a field blank to clear that target.</p>
                    </div>
                    <span class="badge badge-gray shrink-0">Director-only</span>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Annual training hours *</label>
                        <input type="number" min="0" step="0.5" class="input" wire:model="form.annual_target_hours" placeholder="e.g. 2500">
                        @error('form.annual_target_hours') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">The annual <b>pool</b>. Project actuals are subtracted from it — project targets are never summed to make this number.</p>
                    </div>
                    <div>
                        <label class="label">Annual budget target (₱) *</label>
                        <input type="number" min="0" step="0.01" class="input" wire:model="form.annual_target_budget" placeholder="e.g. 668000">
                        @error('form.annual_target_budget') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">Compared against the summed utilization entries across all projects.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Notes</label>
                        <textarea rows="2" class="input" wire:model="form.notes" placeholder="Basis for the target — e.g. CESO memorandum, board approval…"></textarea>
                        @error('form.notes') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" wire:click="cancelForm" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save target</button>
                </div>
            </form>
        </section>
    @endif

    {{-- ===================== UNIVERSITY TARGET VS ACTUAL ===================== --}}
    <section class="mt-5">
        <h3 class="text-[14px] font-extrabold tracking-tight">University targets vs actuals</h3>
        <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Attainment is measured across the whole fiscal year</p>

        <div class="mt-3 grid lg:grid-cols-4 md:grid-cols-2 gap-4">
            {{-- 1. Training hours — the POOL, stated as a drawdown not a ratio --}}
            <div class="sc-card sc-card-hover p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Training hours rendered</p>
                    <span class="badge {{ $hoursPct === null ? 'badge-gray' : ($hoursPct >= 100 ? 'badge-green' : ($hoursPct >= 70 ? 'badge-blue' : 'badge-yellow')) }}">{{ $hoursPct === null ? 'no target' : $hoursPct.'%' }}</span>
                </div>
                <p class="mt-3 text-[24px] font-extrabold tracking-tight leading-none text-lnu-800">{{ number_format($actuals['training_hours']) }}</p>
                <p class="text-[11.5px] text-gray-400 font-medium mt-1">of {{ $targetHours === null ? '—' : number_format($targetHours) }} hrs target</p>
                <div class="progress mt-3"><span style="width:{{ (int) min($hoursPct ?? 0, 100) }}%" class="{{ $hoursPct === null ? 'bg-gray-300' : ($hoursPct >= 100 ? 'bg-emerald-500' : ($hoursPct >= 70 ? 'bg-lnu-600' : 'bg-gold-500')) }}"></span></div>
                <p class="text-[10.5px] text-gray-400 font-medium mt-2">
                    @if ($targetHours === null)
                        No annual target set for {{ $selectedLabel }}.
                    @elseif ($overDrawn)
                        Pool over-drawn by {{ number_format($actuals['training_hours'] - $targetHours) }} hrs across {{ $actuals['projects'] }} projects.
                    @else
                        {{ number_format($remaining) }} hrs of the annual pool still available · {{ number_format($drawnDown) }} drawn down by {{ $actuals['projects'] }} projects
                    @endif
                </p>
            </div>

            {{-- 2. Budget --}}
            <div class="sc-card sc-card-hover p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Budget utilized</p>
                    <span class="badge {{ $budgetPct === null ? 'badge-gray' : ($budgetPct > 100 ? 'badge-red' : ($budgetPct >= 70 ? 'badge-blue' : 'badge-yellow')) }}">{{ $budgetPct === null ? 'no target' : $budgetPct.'%' }}</span>
                </div>
                <p class="mt-3 text-[24px] font-extrabold tracking-tight leading-none {{ $budgetPct !== null && $budgetPct > 100 ? 'text-red-600' : '' }}">₱{{ number_format($actuals['budget']) }}</p>
                <p class="text-[11.5px] text-gray-400 font-medium mt-1">of ₱{{ $targetBudget === null ? '—' : number_format($targetBudget) }} target</p>
                <div class="progress mt-3"><span style="width:{{ (int) min($budgetPct ?? 0, 100) }}%" class="{{ $budgetPct !== null && $budgetPct > 100 ? 'bg-red-500' : 'bg-lnu-600' }}"></span></div>
                <p class="text-[10.5px] text-gray-400 font-medium mt-2">Approved utilisation entries rolled up across all projects</p>
            </div>

            {{-- 3. Trainees — informational, NO target exists at this level --}}
            <div class="sc-card sc-card-hover p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Trainees / beneficiaries</p>
                    <span class="badge badge-gray">no target</span>
                </div>
                <p class="mt-3 text-[24px] font-extrabold tracking-tight leading-none text-emerald-600">{{ number_format($actuals['trainees']) }}</p>
                <p class="text-[11.5px] text-gray-400 font-medium mt-1">distinct beneficiaries reached</p>
                <p class="text-[10.5px] text-gray-400 font-medium mt-2">Across {{ $actuals['projects'] }} projects — informational, no drawdown</p>
            </div>

            {{-- 4. Activities — informational, NO target exists at this level --}}
            <div class="sc-card sc-card-hover p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Activities delivered</p>
                    <span class="badge badge-gray">no target</span>
                </div>
                <p class="mt-3 text-[24px] font-extrabold tracking-tight leading-none">{{ number_format($actuals['activities']) }}</p>
                <p class="text-[11.5px] text-gray-400 font-medium mt-1">activities across all projects</p>
                <p class="text-[10.5px] text-gray-400 font-medium mt-2">Rolled up from {{ $actuals['projects'] }} projects · no program-level target exists</p>
            </div>
        </div>
    </section>

    {{-- ===================== PROJECT TARGETS TABLE ===================== --}}
    <section class="mt-5" x-data="{ college: '' }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-[14px] font-extrabold tracking-tight">Project targets</h3>
                <p class="text-[11.5px] text-gray-400 font-medium mt-0.5">Every project's annual hours target and allocation, and what has actually been rendered</p>
            </div>
            <div class="flex items-center gap-2">
                <select x-model="college" class="input !py-2 !text-[12px] w-auto">
                    <option value="">All colleges</option>
                    @foreach ($colleges as $c)
                        <option value="{{ $c->code }}">{{ $c->short_name ?? $c->code }}</option>
                    @endforeach
                </select>
                {{-- Server-side sort — see App\Livewire\Targets::$sort for why. --}}
                <select wire:model.live="sort" class="input !py-2 !text-[12px] w-auto">
                    <option value="hours">Sort · hours attainment ↑</option>
                    <option value="budget">Sort · budget utilization ↓</option>
                    <option value="code">Sort · code</option>
                </select>
            </div>
        </div>

        <div class="mt-3 sc-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="sc-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>College</th>
                            <th class="!text-right">Trainors</th>
                            <th class="!text-right">Trainees</th>
                            <th class="!text-right">Activities</th>
                            <th class="!text-right">Training hrs</th>
                            <th class="min-w-[168px]">Hours attainment</th>
                            <th class="!text-right">Allocated budget</th>
                            <th class="!text-right">Budget utilization</th>
                            <th class="!text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- The component has ALREADY ordered $rows (Targets::$sort), so the
                             blade must not re-sort: `->sortBy('code')` here was the bug — it
                             pinned the table to code order while the select above claimed
                             "hours attainment up", and the dropdown did nothing. --}}
                        @forelse ($rows as $r)
                            @php($p = $r->model)
                            @php($h = $r->hours)
                            @php($collegeCode = (string) ($p->college?->code ?? ''))
                            <tr wire:key="tg-{{ $p->id }}"
                                x-show="(college === '' || college === '{{ $collegeCode }}')"
                                data-code="{{ $p->code }}"
                                data-college="{{ $collegeCode }}">
                                <td>
                                    <a href="{{ route('projects.show', $p) }}" class="font-semibold text-charcoal hover:text-lnu-700">{{ str($p->title)->before(':') }}</a>
                                    <p class="text-[11px] text-gray-400 font-mono">{{ $p->code }}</p>
                                </td>
                                <td><x-sc.college-pill :code="$p->college?->code" :name="$p->college?->name" short /></td>
                                <td class="!text-right font-semibold">{{ $h['trainors'] }}</td>
                                <td class="!text-right font-semibold">{{ number_format($h['trainees']) }}</td>
                                <td class="!text-right text-gray-500">{{ $h['activity_count'] }}</td>
                                <td class="!text-right font-bold text-lnu-700">{{ number_format($h['actual_hours']) }}<span class="text-gray-300 font-medium"> / {{ $h['target_hours'] === null ? '—' : number_format($h['target_hours']) }}</span></td>
                                <td>
                                    @if ($h['hours_pct'] === null)
                                        <span class="text-[11.5px] text-gray-400 font-medium">no target set</span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            <div class="progress flex-1"><span style="width:{{ (int) min($h['hours_pct'], 100) }}%" class="{{ $h['hours_pct'] >= 100 ? 'bg-emerald-500' : ($h['hours_pct'] >= 70 ? 'bg-lnu-600' : 'bg-gold-500') }}"></span></div>
                                            <span class="text-[11.5px] font-bold text-gray-500 w-9 text-right">{{ $h['hours_pct'] }}%</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="!text-right font-semibold">₱{{ number_format($h['utilized_budget']) }}<span class="text-gray-300 font-medium"> / ₱{{ number_format($h['allocated_budget']) }}</span></td>
                                <td class="!text-right text-[12px] font-bold {{ $r->over ? 'text-red-600' : 'text-gray-500' }}">{{ $h['budget_pct'] === null ? '—' : $h['budget_pct'].'%' }}</td>
                                <td class="!text-right"><span class="badge badge-{{ config('smartcemes.status_colors')[$p->status] ?? 'gray' }}">{{ ucfirst($p->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-gray-400 py-8">No projects yet — create one under Extension Projects.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-[11.5px] text-gray-400 mt-2">Actual figures roll up from the activities recorded inside each project. Budget utilisation derives from the approved utilisation entries.</p>
    </section>
</div>
