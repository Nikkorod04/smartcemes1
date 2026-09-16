<div>
<section class="pt-6">
    <div class="flex items-end justify-between mb-3">
        <p class="text-[13px] text-gray-400 font-medium">Needs-assessment validation queue · pending → validated | returned (with remarks)</p>
        <p class="text-[12px] text-gray-400 font-medium">Validations stamp your name &amp; timestamp to the audit trail</p>
    </div>
    <div class="reveal-item grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl bg-gold-50 text-gold-700 flex items-center justify-center"><x-sc.icon name="clipboard" /></span>
                <span class="badge badge-yellow"><span class="w-1.5 h-1.5 rounded-full bg-gold-500 pulse-dot"></span>needs review</span>
            </div>
            <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none text-gold-700">{{ (int) $stats->pending }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Pending Review</p>
        </div>

        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><x-sc.icon name="check" /></span>
                <span class="badge badge-green">cleared</span>
            </div>
            <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none">{{ (int) $stats->validated }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Validated</p>
        </div>

        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center"><x-sc.icon name="doc" /></span>
                <span class="badge badge-red">re-encode</span>
            </div>
            <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none">{{ (int) $stats->returned }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Returned</p>
        </div>

        <div class="sc-card sc-card-hover p-5">
            <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl bg-lnu-50 text-lnu-800 flex items-center justify-center"><x-sc.icon name="people" /></span>
                <span class="badge badge-blue">all quarters</span>
            </div>
            <p class="mt-4 text-[28px] font-extrabold tracking-tight leading-none text-lnu-800">{{ (int) $stats->total }}</p>
            <p class="text-[12.5px] text-gray-500 font-medium mt-1.5">Total Encoded</p>
        </div>
    </div>
</section>

<section class="mt-5 reveal-item">
    <div class="sc-card p-0 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <label class="relative flex-1 min-w-[220px] max-w-sm">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 inline-flex pointer-events-none"><x-sc.icon name="search" class="w-4 h-4" /></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="input !pl-9 !py-2 bg-gray-50 border-transparent focus:bg-white" placeholder="Search community, encoder, or respondent…">
            </label>
            <select wire:model.live="quarter" class="input !w-40 !py-2">
                <option value="all">All quarters</option>
                <option value="1">Q1 · Jan–Mar</option>
                <option value="2">Q2 · Apr–Jun</option>
                <option value="3">Q3 · Jul–Sep</option>
                <option value="4">Q4 · Oct–Dec</option>
            </select>
            <span class="ml-auto text-[11.5px] text-gray-400 font-medium whitespace-nowrap">{{ $queue->total() }} submission{{ $queue->total() === 1 ? '' : 's' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="sc-table">
                <thead><tr>
                    <th>Community</th>
                    <th>
                        <button type="button" wire:click="toggleSortPeriod" class="inline-flex items-center gap-1 text-inherit hover:text-charcoal transition group" title="Sort by period (quarter + year)">
                            Period
                            <x-sc.icon name="chevron" @class([
                                'w-3 h-3 transition',
                                'text-lnu-800 rotate-180' => $sortPeriod === 'asc',
                                'text-lnu-800' => $sortPeriod === 'desc',
                                'text-gray-300 group-hover:text-gray-400' => $sortPeriod === 'default',
                            ]) />
                        </button>
                    </th><th>Respondent</th><th>Submitted By</th><th>Submitted</th><th>Review Status</th><th class="!text-right">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($queue as $a)
                        @php($initials = strtoupper(collect(explode(' ', $a->uploader?->name ?? '?'))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')))
                        <tr wire:key="rv-{{ $a->id }}" class="cursor-pointer" wire:click="openDrawer({{ $a->id }})">
                            <td>
                                <p class="font-bold text-[13px] text-charcoal">{{ $a->community->name }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $a->community->municipality }}, {{ $a->community->province }}</p>
                            </td>
                            <td><span class="inline-flex items-center text-[11.5px] font-semibold text-gray-500 bg-gray-50 border border-gray-100 rounded-md px-2 py-0.5 whitespace-nowrap">Q{{ $a->quarter }} · {{ $a->year }}</span></td>
                            <td>
                                <p class="text-[12.5px] font-semibold text-charcoal whitespace-nowrap">{{ $a->respondent_last_name }}, {{ $a->respondent_first_name }}</p>
                                <p class="mt-0.5">
                                    @if ($a->file_path)
                                        <span class="badge badge-gold !px-1.5 !py-0 !text-[10px]">XLSX</span>
                                    @else
                                        <span class="badge badge-gray !px-1.5 !py-0 !text-[10px]">Manual</span>
                                    @endif
                                </p>
                            </td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="avatar w-8 h-8 text-[10px]">{{ $initials }}</span>
                                    <div class="min-w-0">
                                        <p class="text-[12.5px] font-semibold truncate">{{ $a->uploader?->name ?? '—' }}</p>
                                        <p class="text-[11px] text-gray-400 truncate">{{ $a->uploader ? ucfirst($a->uploader->role) : '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-[12.5px] text-gray-500 whitespace-nowrap">{{ $a->created_at->format('M j, Y') }}</td>
                            <td>
                                @if ($a->review_status === 'pending')
                                    <span class="badge badge-yellow"><span class="w-1.5 h-1.5 rounded-full bg-gold-500 pulse-dot"></span>Pending</span>
                                @elseif ($a->review_status === 'validated')
                                    <span class="badge badge-green">Validated</span>
                                @else
                                    <span class="badge badge-red">Returned</span>
                                @endif
                                @if ($a->reviewed_at)
                                    <p class="text-[10.5px] text-gray-400 mt-1 leading-snug">by {{ $a->reviewer?->name }} · {{ $a->reviewed_at->format('M j, Y') }}</p>
                                @endif
                                @if ($a->review_status === 'returned' && $a->review_remarks)
                                    <p class="italic text-[11px] text-gray-400 mt-1 leading-snug max-w-[230px]">“{{ Illuminate\Support\Str::limit($a->review_remarks, 90) }}”</p>
                                @endif
                            </td>
                            <td class="!p-2 text-right whitespace-nowrap">
                                <div class="row-actions inline-flex items-center gap-1.5">
                                    @if ($a->review_status === 'pending')
                                        <button wire:click.stop="openValidate({{ $a->id }})" class="btn btn-success-soft !px-2.5 !py-1.5 !text-[11px]">Validate</button>
                                        <button wire:click.stop="openReturn({{ $a->id }})" class="btn btn-danger-soft !px-2.5 !py-1.5 !text-[11px]">Return</button>
                                    @endif
                                    <button wire:click.stop="openDrawer({{ $a->id }})" class="btn btn-ghost !px-2.5 !py-1.5 !text-[11px]">Review</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="!py-12 text-center">
                                @if ((int) $stats->total === 0)
                                    <p class="text-[13px] font-semibold text-gray-400">No submissions yet.</p>
                                    <p class="text-[11.5px] text-gray-300 mt-1">Encoded assessments appear here for validation.</p>
                                @else
                                    <p class="text-[13px] font-semibold text-gray-400">No submissions match your filters.</p>
                                    <p class="text-[11.5px] text-gray-300 mt-1">Adjust the search term or quarter selection.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3.5 border-t border-gray-100">
            @include('livewire.partials.pagination', ['paginator' => $queue])
        </div>
    </div>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>

{{-- Encoded Response Dossier drawer (server-side @if — §14: morph-safe, no Alpine
     state bridging; the .sc-drawer CSS animation plays on mount) --}}
@if ($showDrawer && $selected)
    <div wire:key="review-drawer" x-data @keydown.escape.window="$wire.closeDrawer()"
         class="fixed inset-0 z-50 overflow-hidden no-print">
        <div class="absolute inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.closeDrawer()"></div>

        <aside class="sc-drawer absolute inset-y-0 right-0 w-full max-w-[480px] bg-white shadow-pop flex flex-col">
            <header class="p-5 border-b border-gray-100 flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400">Encoded Response Dossier</p>
                    <h2 class="mt-0.5 text-[15px] font-extrabold tracking-tight truncate">{{ $selected->community->name }}</h2>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        @if ($selected->review_status === 'pending')
                            <span class="badge badge-yellow"><span class="w-1.5 h-1.5 rounded-full bg-gold-500 pulse-dot"></span>Pending</span>
                        @elseif ($selected->review_status === 'validated')
                            <span class="badge badge-green">Validated</span>
                        @else
                            <span class="badge badge-red">Returned</span>
                        @endif
                        <span class="badge badge-blue">Q{{ $selected->quarter }} · {{ $selected->year }}</span>
                        @if ($selected->file_path)
                            <span class="badge badge-gold">XLSX import</span>
                        @endif
                    </div>
                    <p class="mt-2 text-[11.5px] text-gray-400 leading-snug">
                        Respondent: <span class="font-semibold text-gray-500">{{ $this->answer($selected, 'respondent_full_name') ?? '—' }}</span>
                        · Encoded by {{ $selected->uploader?->name ?? '—' }} · {{ $selected->created_at->format('M j, Y') }}
                        @if ($selected->reviewed_at)
                            · Reviewed by {{ $selected->reviewer?->name }} on {{ $selected->reviewed_at->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                <button wire:click="closeDrawer" class="p-2 rounded-lg text-gray-400 hover:text-charcoal hover:bg-gray-100 transition text-lg leading-none shrink-0">✕</button>
            </header>

            <div class="flex-1 overflow-y-auto bg-gray-50/60 p-4">
                @foreach ($sections as $s)
                    <details class="sc-acc" {{ $loop->first ? 'open' : '' }}>
                        <summary>
                            <span class="acc-num">{{ $s['num'] }}</span>{{ $s['title'] }}
                            <svg class="acc-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="px-4 pb-4 pt-1">
                            @foreach ($s['rows'] as [$field, $label])
                                @php($answer = $this->answer($selected, $field))
                                <div class="kv">
                                    <span class="k">{{ $label }}</span>
                                    @if ($answer === null)
                                        <span class="v !text-gray-300">—</span>
                                    @elseif (in_array($field, \App\Models\NeedsAssessment::YES_NO_FIELDS, true))
                                        <span class="v"><span class="badge {{ $answer === 'Yes' ? 'badge-green' : 'badge-gray' }}">{{ $answer }}</span></span>
                                    @else
                                        <span class="v">{{ $answer }}</span>
                                    @endif
                                </div>
                            @endforeach
                            @foreach ($s['chips'] as [$chipField, $chipLabel])
                                @php($chipValues = $this->chipValues($selected, $chipField))
                                <p class="label !mb-2 {{ !$loop->first || $s['rows'] ? 'mt-3' : '' }}">{{ $chipLabel }}</p>
                                <div class="chip-group">
                                    @forelse ($chipValues as $value)
                                        <span class="chip on !cursor-default !pointer-events-none">{{ $value }}</span>
                                    @empty
                                        <span class="text-[12px] text-gray-300 italic">None selected</span>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach

                @if ($selected->other_text)
                    <div class="mt-3 rounded-xl border border-gold-200 bg-gold-50/60 p-3">
                        <p class="text-[10.5px] font-bold uppercase tracking-wide text-gold-700 mb-1">"Other" raw text preserved (D9)</p>
                        @foreach ($selected->other_text as $otherField => $texts)
                            @foreach ((array) $texts as $text)
                                <div class="kv"><span class="k">{{ ucwords(str_replace('_', ' ', $otherField)) }}</span><span class="v">“{{ $text }}”</span></div>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>

            <footer class="p-4 border-t border-gray-100 bg-white">
                @php($pct = $this->completeness($selected))
                <div class="flex items-center justify-between text-[11.5px]">
                    <p class="font-bold text-charcoal">Record completeness</p>
                    <p class="font-semibold {{ $pct < 80 ? 'text-red-500' : 'text-lnu-800' }}">{{ $pct }}% fields answered</p>
                </div>
                <div class="progress mt-1.5"><span style="width: {{ $pct }}%" class="{{ $pct < 80 ? 'bg-red-400' : 'bg-lnu' }}"></span></div>
                @if ($selected->review_status === 'pending')
                    <div class="mt-3 flex items-center gap-2">
                        <button wire:click="openValidate({{ $selected->id }})" class="btn btn-primary flex-1 !py-2.5">Validate Assessment</button>
                        <button wire:click="openReturn({{ $selected->id }})" class="btn btn-danger-soft flex-1 !py-2.5">Return with Remarks</button>
                    </div>
                @endif
            </footer>
        </aside>
    </div>
@endif

{{-- Validate confirmation modal (stacks above the drawer, §14 z-rule) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showValidate', v => open = v)" @keydown.escape.window="$wire.set('showValidate', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-[60] p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showValidate', false)"></div>
    <div class="sc-modal relative max-w-lg mx-auto mt-32 sc-card p-6 shadow-pop">
        <div class="flex items-start gap-3">
            <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><x-sc.icon name="check" /></span>
            <div>
                <h3 class="font-extrabold text-[15px] tracking-tight">Validate Submission</h3>
                <p class="text-[12px] text-gray-500 mt-0.5 leading-relaxed">The submission for <b class="text-charcoal">{{ $validating?->community?->name ?? 'this community' }}</b>@if ($validating) (Q{{ $validating->quarter }} · {{ $validating->year }})@endif will be marked as validated.</p>
            </div>
        </div>
        <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50/70 p-3.5">
            <p class="text-[12px] font-bold text-charcoal mb-1.5">On confirm:</p>
            <ul class="space-y-1.5 text-[12px] text-gray-500 leading-relaxed">
                <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✓</span>The community summary recomputes automatically</li>
                <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✓</span>The encoder is notified of the validation</li>
                <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✓</span>Your name &amp; timestamp are stamped to the audit trail</li>
            </ul>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="$set('showValidate', false)">Cancel</button>
            <button type="button" class="btn btn-primary" wire:click="confirmValidate" wire:loading.attr="disabled" wire:target="confirmValidate">Validate Assessment</button>
        </div>
    </div>
</div>

{{-- Return remarks modal (stacks above the drawer, §14 z-rule) --}}
<div x-data="{ open: false }" x-init="$wire.$watch('showReturn', v => open = v)" @keydown.escape.window="$wire.set('showReturn', false)"
     x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-[60] p-6 overflow-auto no-print">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="$wire.set('showReturn', false)"></div>
    <form wire:submit="confirmReturn" class="sc-modal relative max-w-lg mx-auto mt-32 sc-card p-6 shadow-pop">
        <div class="flex items-start gap-3">
            <span class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center shrink-0"><x-sc.icon name="doc" /></span>
            <div>
                <h3 class="font-extrabold text-[15px] tracking-tight">Return to Encoder</h3>
                <p class="text-[12px] text-gray-500 mt-0.5 leading-relaxed">The submission for <b class="text-charcoal">{{ $returning?->community?->name ?? 'this community' }}</b> goes back to the encoder with your remarks.</p>
            </div>
        </div>
        <div class="mt-4">
            <label class="label">Remarks <span class="text-red-500">*</span></label>
            <textarea rows="4" class="input resize-none" wire:model="returnRemarks" placeholder="Explain what must be corrected before resubmission…"></textarea>
            <p class="text-[11px] text-gray-400 mt-1.5">Remarks are logged in the audit trail and shown to the encoder.</p>
            @error('returnRemarks') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" wire:click="$set('showReturn', false)">Cancel</button>
            <button type="submit" class="btn btn-danger-soft">Return Assessment</button>
        </div>
    </form>
</div>
</div>
