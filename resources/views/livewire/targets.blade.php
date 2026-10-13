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
        <div class="targets-toolbar">
            <div class="targets-year-control">
                <x-sc.icon name="calendar" class="h-4 w-4" />
                <label for="targets-year" class="targets-toolbar-label">Academic year</label>
                <span class="targets-select-wrap">
                    <select id="targets-year" wire:model.live="year" class="targets-year-select">
                    @foreach ($yearOptions as $y)
                        <option value="{{ $y }}">{{ $yearLabels[(int) $y] ?? 'AY '.$y.'-'.($y + 1) }}</option>
                    @endforeach
                    </select>
                </span>
            </div>
            @if (! $showForm)
                <span class="targets-toolbar-divider" aria-hidden="true"></span>
                <button wire:click="openForm" class="btn btn-primary targets-action"><x-sc.icon name="edit" class="h-4 w-4" />{{ $target ? 'Edit targets' : 'Set targets' }}</button>
            @endif
        </div>
    </section>

    {{-- ===================== EDIT FORM ===================== --}}
    @if ($showForm)
        <div x-data @keydown.escape.window="$wire.cancelForm()"
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 no-print"
             role="dialog" aria-modal="true" aria-labelledby="target-form-title">
            <div class="fixed inset-0 bg-charcoal/50 backdrop-blur-[3px]" wire:click="cancelForm"></div>
            <form wire:submit="save" class="sc-modal relative z-10 flex w-full max-w-2xl max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-pop" aria-labelledby="target-form-title">
                <header class="shrink-0 border-b border-gray-100 bg-gradient-to-br from-lnu-800 to-lnu-700 px-5 py-5 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15">
                                <x-sc.icon name="chart" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/65">University target</p>
                                <h3 id="target-form-title" class="mt-1 text-[18px] font-extrabold tracking-tight">{{ $target ? 'Edit annual targets' : 'Set annual targets' }}</h3>
                                <p class="mt-1 max-w-lg text-[12px] font-medium leading-relaxed text-white/72">Set the institutional training-hours pool and budget commitment for {{ $selectedLabel }}.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="cancelForm" aria-label="Close target form"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/12 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"><x-sc.icon name="x" class="h-4 w-4" /></button>
                    </div>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-lnu-100 bg-lnu-50/70 px-3.5 py-3 text-[11px] leading-relaxed text-lnu-900">
                        <x-sc.icon name="shield" class="mt-0.5 h-4 w-4 shrink-0 text-lnu-700" />
                        <p class="min-w-0 flex-1"><span class="font-extrabold">Director-only target.</span> Changes are recorded in the activity log. Leave a field blank when the institution has no target for that measure.</p>
                        <span class="badge badge-blue shrink-0 !bg-white !text-lnu-800">{{ $selectedLabel }}</span>
                    </div>

                    <div class="space-y-6">
                        <section>
                            <div class="mb-3 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-[11px] font-extrabold text-gray-500">1</span>
                                <div>
                                    <h4 class="text-[12px] font-extrabold uppercase tracking-[0.12em] text-charcoal">Institutional commitments</h4>
                                    <p class="text-[11px] font-medium text-gray-400">These figures are used to measure university-wide attainment.</p>
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label" for="target-annual-hours">Annual training hours <span class="font-medium text-gray-400">(optional)</span></label>
                                    <input id="target-annual-hours" type="number" min="0" step="0.5" inputmode="decimal" class="input" wire:model="form.annual_target_hours" aria-invalid="{{ $errors->has('form.annual_target_hours') ? 'true' : 'false' }}" placeholder="e.g. 2500">
                                    <p class="field-hint">The annual pool drawn down by actual project hours.</p>
                                    @error('form.annual_target_hours') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="target-annual-budget">Annual budget target <span class="font-medium text-gray-400">(optional)</span></label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[13px] font-bold text-gray-400">₱</span>
                                        <input id="target-annual-budget" type="number" min="0" step="0.01" inputmode="decimal" class="input !pl-8" wire:model="form.annual_target_budget" aria-invalid="{{ $errors->has('form.annual_target_budget') ? 'true' : 'false' }}" placeholder="e.g. 668000">
                                    </div>
                                    <p class="field-hint">Compared against approved utilization entries across projects.</p>
                                    @error('form.annual_target_budget') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl border border-gray-200 bg-gray-50/70 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-lnu-700 ring-1 ring-gray-200"><x-sc.icon name="doc" class="h-4 w-4" /></span>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-[12px] font-extrabold text-charcoal">Target notes <span class="font-semibold text-gray-400">(optional)</span></h4>
                                    <p class="mt-1 text-[11px] font-medium leading-relaxed text-gray-500">Record the memorandum, board approval, or planning basis for this commitment.</p>
                                </div>
                            </div>
                            <label class="label mt-4" for="target-notes">Notes</label>
                            <textarea id="target-notes" rows="4" maxlength="1000" class="input resize-y bg-white" wire:model="form.notes" aria-invalid="{{ $errors->has('form.notes') ? 'true' : 'false' }}" placeholder="Basis for the target — e.g. CESO memorandum, board approval…"></textarea>
                            @error('form.notes') <p class="sc-field-error"><x-sc.icon name="alert" class="w-3.5 h-3.5 shrink-0" />{{ $message }}</p> @enderror
                        </section>
                    </div>
                </div>

                <footer class="shrink-0 border-t border-gray-100 bg-gray-50/80 px-5 py-3.5 sm:px-6">
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[10.5px] font-medium text-gray-400">Optional targets may be left blank · Changes are logged</p>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="cancelForm" class="btn btn-ghost">Cancel</button>
                            <button type="submit" class="btn btn-primary min-w-[132px]" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Save target</span>
                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2"><span class="rh-spinner"></span>Saving…</span>
                            </button>
                        </div>
                    </div>
                </footer>
            </form>
        </div>
    @endif

    {{-- ===================== UNIVERSITY TARGET VS ACTUAL ===================== --}}
    <section class="mt-5">
        <h3 class="targets-section-title">Targets vs Actuals</h3>

        <div class="mt-3 grid lg:grid-cols-4 md:grid-cols-2 gap-4">
            {{-- 1. Training hours — the POOL, stated as a drawdown not a ratio --}}
            <x-sc.color-card tone="blue" class="targets-kpi-card flex h-full flex-col p-5">
                <div class="targets-kpi-head">
                    <p class="targets-kpi-label"><span class="targets-kpi-icon"><x-sc.icon name="chart" class="h-4 w-4" /></span>Training hours rendered</p>
                    <span class="targets-kpi-chip {{ $hoursPct === null ? 'targets-kpi-chip--muted' : ($hoursPct >= 100 ? 'targets-kpi-chip--green' : ($hoursPct >= 70 ? 'targets-kpi-chip--blue' : 'targets-kpi-chip--gold')) }}">{{ $hoursPct === null ? 'no target' : $hoursPct.'%' }}</span>
                </div>
                <p class="targets-kpi-value mt-4">{{ number_format($actuals['training_hours']) }}</p>
                <p class="targets-kpi-subvalue">of {{ $targetHours === null ? '—' : number_format($targetHours) }} hrs target</p>
                <div class="targets-kpi-progress mt-4"><span style="width:{{ (int) min($hoursPct ?? 0, 100) }}%" class="{{ $hoursPct === null ? 'targets-kpi-progress-fill--muted' : ($hoursPct >= 100 ? 'targets-kpi-progress-fill--green' : ($hoursPct >= 70 ? 'targets-kpi-progress-fill--blue' : 'targets-kpi-progress-fill--gold')) }}"></span></div>
                <p class="targets-kpi-note mt-auto pt-3 {{ $overDrawn ? 'targets-kpi-note--alert' : '' }}">
                    @if ($targetHours === null)
                        No annual target set for {{ $selectedLabel }}.
                    @elseif ($overDrawn)
                        Pool over-drawn by {{ number_format($actuals['training_hours'] - $targetHours) }} hrs across {{ $actuals['projects'] }} projects.
                    @else
                        {{ number_format($remaining) }} hrs of the annual pool still available · {{ number_format($drawnDown) }} drawn down by {{ $actuals['projects'] }} projects
                    @endif
                </p>
            </x-sc.color-card>

            {{-- 2. Budget --}}
            <x-sc.color-card tone="gold" class="targets-kpi-card flex h-full flex-col p-5">
                <div class="targets-kpi-head">
                    <p class="targets-kpi-label"><span class="targets-kpi-icon"><x-sc.icon name="wallet" class="h-4 w-4" /></span>Budget utilized</p>
                    <span class="targets-kpi-chip {{ $budgetPct === null ? 'targets-kpi-chip--muted' : ($budgetPct > 100 ? 'targets-kpi-chip--red' : ($budgetPct >= 70 ? 'targets-kpi-chip--blue' : 'targets-kpi-chip--gold')) }}">{{ $budgetPct === null ? 'no target' : $budgetPct.'%' }}</span>
                </div>
                <p class="targets-kpi-value targets-kpi-value--money mt-4">₱{{ number_format($actuals['budget']) }}</p>
                <p class="targets-kpi-subvalue">of ₱{{ $targetBudget === null ? '—' : number_format($targetBudget) }} target</p>
                <div class="targets-kpi-progress mt-4"><span style="width:{{ (int) min($budgetPct ?? 0, 100) }}%" class="{{ $budgetPct !== null && $budgetPct > 100 ? 'targets-kpi-progress-fill--red' : 'targets-kpi-progress-fill--navy' }}"></span></div>
                <p class="targets-kpi-note mt-auto pt-3">Approved utilisation entries rolled up across all projects</p>
            </x-sc.color-card>

            {{-- 3. Trainees — informational, NO target exists at this level --}}
            <x-sc.color-card tone="teal" class="targets-kpi-card flex h-full flex-col p-5">
                <div class="targets-kpi-head">
                    <p class="targets-kpi-label"><span class="targets-kpi-icon"><x-sc.icon name="people" class="h-4 w-4" /></span>Trainees / beneficiaries</p>
                    <span class="targets-kpi-chip targets-kpi-chip--muted">no target</span>
                </div>
                <p class="targets-kpi-value mt-4">{{ number_format($actuals['trainees']) }}</p>
                <p class="targets-kpi-subvalue">distinct beneficiaries reached</p>
                <p class="targets-kpi-note mt-auto pt-3">Across {{ $actuals['projects'] }} projects — informational, no drawdown</p>
            </x-sc.color-card>

            {{-- 4. Activities — informational, NO target exists at this level --}}
            <x-sc.color-card tone="indigo" class="targets-kpi-card flex h-full flex-col p-5">
                <div class="targets-kpi-head">
                    <p class="targets-kpi-label"><span class="targets-kpi-icon"><x-sc.icon name="activity" class="h-4 w-4" /></span>Activities delivered</p>
                    <span class="targets-kpi-chip targets-kpi-chip--muted">no target</span>
                </div>
                <p class="targets-kpi-value mt-4">{{ number_format($actuals['activities']) }}</p>
                <p class="targets-kpi-subvalue">activities across all projects</p>
                <p class="targets-kpi-note mt-auto pt-3">Rolled up from {{ $actuals['projects'] }} projects · no program-level target exists</p>
            </x-sc.color-card>
        </div>
    </section>

    {{-- ===================== PROJECT TARGETS TABLE ===================== --}}
    <section class="mt-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="targets-section-title">Project targets</h3>
            </div>
            <div class="flex items-center gap-2">
                <select wire:model.live="college" class="input !py-2 !text-[12px] w-auto">
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
                            <tr wire:key="tg-{{ $p->id }}">
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
            <div class="border-t border-gray-100 bg-gray-50/50 px-5 py-3.5">
                @include('livewire.partials.pagination', ['paginator' => $rows])
            </div>
        </div>
    </section>
</div>
