<div class="assessments-share-page">
    @push('scripts')
        @vite('resources/js/share.js')
    @endpush
    @php
        $years = range(now()->year - 1, now()->year + 1);
        $selectedUrl = $selected ? route('assessment.public', ['token' => $selected->token]) : null;
        $revoking = $revokingId ? $links->firstWhere('id', $revokingId) : null;
        $extending = $extendingId ? $links->firstWhere('id', $extendingId) : null;
    @endphp

    <section class="pt-6">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
            <div>
                <h1 class="secretary-form-title">Share the assessment form</h1>
                <p class="secretary-form-subtitle">
                    One link per barangay and period. Print or send the QR — respondents answer on their own phone, with no account.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="badge badge-blue">{{ $activeCount }} open</span>
                <span class="badge badge-gray">{{ $links->count() - $activeCount }} ended</span>
            </div>
        </div>
    </section>

    <div class="mt-4 grid gap-5 xl:grid-cols-12">
        {{-- Generate ------------------------------------------------------ --}}
        <section class="sc-card p-5 xl:col-span-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-lnu-50 text-lnu-700 ring-1 ring-lnu-100">
                    <x-sc.icon name="link" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-[15px] font-extrabold tracking-tight">New link</h2>
                    <p class="mt-0.5 text-[11.5px] leading-snug text-gray-500">
                        The link locks the barangay and period. Respondents cannot change either.
                    </p>
                </div>
            </div>

            <form wire:submit="generate" class="mt-4 space-y-3.5">
                <div>
                    <label for="share-community" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Community</label>
                    <select id="share-community" wire:model="communityId" @class(['input', 'border-red-300' => $errors->has('communityId')])>
                        <option value="">Choose a barangay…</option>
                        @foreach ($communities as $community)
                            <option value="{{ $community->id }}">{{ $community->name }} · {{ $community->municipality }}</option>
                        @endforeach
                    </select>
                    @error('communityId') <p class="mt-1 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="share-quarter" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Quarter</label>
                        <select id="share-quarter" wire:model="quarter" class="input">
                            @foreach ([1 => 'Q1 · Jan–Mar', 2 => 'Q2 · Apr–Jun', 3 => 'Q3 · Jul–Sep', 4 => 'Q4 · Oct–Dec'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('quarter') <p class="mt-1 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="share-year" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Year</label>
                        <select id="share-year" wire:model="year" class="input">
                            @foreach ($years as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        </select>
                        @error('year') <p class="mt-1 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Open for</span>
                    <div class="flex gap-1.5" role="radiogroup" aria-label="How many days the link stays open">
                        @foreach (range(\App\Models\AssessmentLink::MIN_DAYS, \App\Models\AssessmentLink::MAX_DAYS) as $day)
                            <label @class([
                                'flex-1 cursor-pointer rounded-lg border px-0 py-2 text-center text-[12.5px] font-bold transition',
                                'border-lnu-800 bg-lnu-800 text-white shadow-sm' => $days === $day,
                                'border-gray-200 bg-white text-gray-600 hover:border-lnu-300' => $days !== $day,
                            ])>
                                <input type="radio" wire:model.live="days" value="{{ $day }}" class="sr-only">
                                {{ $day }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-[11px] text-gray-400">
                        day{{ $days === 1 ? '' : 's' }} ·
                        closes <span class="font-semibold text-gray-600">{{ \App\Models\AssessmentLink::expiryFor($days)->format('D, M j, Y g:i A') }}</span>
                    </p>
                    @error('days') <p class="mt-1 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="generate">
                    <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-1.5">
                        <x-sc.icon name="plus" class="h-4 w-4" /> Generate link
                    </span>
                    <span wire:loading wire:target="generate">Creating…</span>
                </button>
            </form>

            <ul class="mt-4 space-y-1.5 border-t border-gray-100 pt-3 text-[11px] leading-snug text-gray-500">
                <li>· A link closes at 11:59 PM on its last day.</li>
                <li>· Extending keeps the same QR — no need to reprint.</li>
                <li>· Revoking closes the form; answers already received stay in the queue.</li>
            </ul>
        </section>

        {{-- Selected link -------------------------------------------------- --}}
        <section class="xl:col-span-7">
            @if ($selected)
                <div class="sc-card p-5" wire:key="share-card-{{ $selected->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-[15px] font-extrabold tracking-tight">{{ $selected->community->name }}</h2>
                            <p class="mt-0.5 text-[11.5px] text-gray-500">
                                {{ $selected->community->municipality }}, {{ $selected->community->province }} · {{ $selected->periodLabel() }}
                            </p>
                        </div>
                        @if ($selected->isRevoked())
                            <span class="badge badge-red">Revoked</span>
                        @elseif ($selected->isExpired())
                            <span class="badge badge-gray">Expired</span>
                        @else
                            <span class="badge badge-green">Open</span>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-col gap-5 sm:flex-row">
                        <div class="shrink-0 text-center">
                            <canvas
                                width="440" height="440"
                                class="mx-auto rounded-xl border border-gray-100 bg-white p-1.5"
                                style="width:200px;height:200px"
                                data-qr="{{ $selectedUrl }}"
                                x-init="window.scDrawQr?.($el, $el.dataset.qr)"
                                aria-label="QR code for this assessment link"
                                role="img"></canvas>
                            <p class="mt-2 text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Scan to answer</p>
                        </div>

                        <div class="min-w-0 flex-1">
                            <label for="share-url" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Link</label>
                            <div class="flex items-center gap-2">
                                <input id="share-url" type="text" readonly value="{{ $selectedUrl }}"
                                       class="input min-w-0 flex-1 font-mono !text-[11px] text-gray-600"
                                       x-on:focus="$el.select()">
                                <button type="button" class="btn btn-outline shrink-0 !px-3"
                                        data-copy="{{ $selectedUrl }}"
                                        x-on:click="window.scCopyLink?.($el.dataset.copy)"
                                        title="Copy the link">
                                    <x-sc.icon name="copy" class="h-4 w-4" />
                                </button>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[12px]">
                                <div>
                                    <dt class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Closes</dt>
                                    <dd class="mt-0.5 font-semibold text-charcoal">{{ $selected->expires_at?->format('M j, Y g:i A') ?? 'No expiry recorded' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Created by</dt>
                                    <dd class="mt-0.5 font-semibold text-charcoal">{{ $selected->creator?->name ?? 'Console command' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Period</dt>
                                    <dd class="mt-0.5 font-semibold text-charcoal">{{ $selected->periodLabel() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Created</dt>
                                    <dd class="mt-0.5 font-semibold text-charcoal">{{ $selected->created_at->format('M j, Y g:i A') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ $selectedUrl }}" target="_blank" rel="noopener" class="btn btn-primary !py-2 !px-3 text-[12px]">
                                    <x-sc.icon name="eye" class="h-4 w-4" /> Open form
                                </a>
                                @if ($selected->isActive())
                                    <button type="button" wire:click="openExtend({{ $selected->id }})" class="btn btn-outline !py-2 !px-3 text-[12px]">
                                        <x-sc.icon name="clock" class="h-4 w-4" /> Extend
                                    </button>
                                    <button type="button" wire:click="openRevoke({{ $selected->id }})" class="btn btn-ghost !py-2 !px-3 text-[12px] text-red-600 hover:bg-red-50">
                                        <x-sc.icon name="trash" class="h-4 w-4" /> Revoke
                                    </button>
                                @elseif (! $selected->isRevoked() && $selected->isExpired())
                                    <button type="button" wire:click="openExtend({{ $selected->id }})" class="btn btn-outline !py-2 !px-3 text-[12px]">
                                        <x-sc.icon name="clock" class="h-4 w-4" /> Reopen
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="sc-card flex h-full min-h-[280px] flex-col items-center justify-center p-8 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-300 ring-1 ring-gray-100">
                        <x-sc.icon name="copy" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-[13px] font-bold text-gray-600">No link selected</p>
                    <p class="mt-1 max-w-sm text-[11.5px] leading-relaxed text-gray-400">
                        Generate a link on the left and its QR code will appear here, ready to show or print.
                    </p>
                </div>
            @endif
        </section>
    </div>

    {{-- All links -------------------------------------------------------- --}}
    <section class="mt-5">
        <div class="sc-card p-0 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-5 py-4">
                <h2 class="text-[13.5px] font-extrabold tracking-tight">All links</h2>
                <p class="text-[11.5px] text-gray-400">Anyone on the team can extend or revoke a link. Every action is logged.</p>
                <span class="ml-auto text-[11.5px] font-medium text-gray-400">{{ $activeCount }} open · {{ $links->count() - $activeCount }} ended</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table min-w-[560px]">
                    <thead>
                        <tr>
                            <th>Community</th>
                            <th class="hidden xl:table-cell">Created by</th>
                            <th>Closes</th>
                            <th>Status</th>
                            <th class="!text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($links as $link)
                            @php($linkUrl = route('assessment.public', ['token' => $link->token]))
                            <tr wire:key="share-row-{{ $link->id }}"
                                wire:click="select({{ $link->id }})"
                                @class(['cursor-pointer', 'bg-lnu-50/40' => $selected && $selected->id === $link->id])>
                                <td>
                                    <p class="text-[12.5px] font-bold text-charcoal">{{ $link->community->name }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-400">{{ $link->community->municipality }} · {{ $link->periodLabel() }}</p>
                                    {{-- Below xl the Created by column is hidden, but knowing whose sheet this is
                                         matters more on a phone than on a desktop. --}}
                                    <p class="mt-0.5 text-[11px] text-gray-400 xl:hidden">By {{ $link->creator?->name ?? 'Console command' }}</p>
                                </td>
                                <td class="hidden xl:table-cell">
                                    <p class="text-[12px] font-semibold text-charcoal">{{ $link->creator?->name ?? 'Console command' }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $link->creator ? ucfirst($link->creator->role) : '—' }}</p>
                                </td>
                                <td class="whitespace-nowrap text-[12px] text-gray-500">
                                    {{ $link->expires_at?->format('M j, Y') ?? '—' }}
                                    <span class="block text-[10.5px] text-gray-400">{{ $link->expires_at?->format('g:i A') }}</span>
                                </td>
                                <td>
                                    @if ($link->isRevoked())
                                        <span class="badge badge-red">Revoked</span>
                                    @elseif ($link->isExpired())
                                        <span class="badge badge-gray">Expired</span>
                                    @else
                                        <span class="badge badge-green">Open</span>
                                    @endif
                                </td>
                                <td class="!text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" class="btn btn-ghost !px-2 !py-1.5 text-[11.5px]"
                                                data-copy="{{ $linkUrl }}"
                                                x-on:click.stop="window.scCopyLink?.($el.dataset.copy)"
                                                title="Copy link">
                                            Copy
                                        </button>
                                        @if ($link->isActive())
                                            <button type="button" class="btn btn-ghost !px-2 !py-1.5 text-[11.5px]"
                                                    wire:click.stop="openExtend({{ $link->id }})">
                                                Extend
                                            </button>
                                            <button type="button" class="btn btn-ghost !px-2 !py-1.5 text-[11.5px] text-red-600 hover:bg-red-50"
                                                    wire:click.stop="openRevoke({{ $link->id }})">
                                                Revoke
                                            </button>
                                        @elseif (! $link->isRevoked() && $link->isExpired())
                                            <button type="button" class="btn btn-ghost !px-2 !py-1.5 text-[11.5px]"
                                                    wire:click.stop="openExtend({{ $link->id }})">
                                                Reopen
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-[12px] text-gray-400">
                                    No links yet. Generate one above to get a QR code.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Extend dialog ---------------------------------------------------- --}}
    @if ($showExtend)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="extend-link-title">
            <div class="absolute inset-0 bg-charcoal/40 backdrop-blur-sm" wire:click="closeExtend"></div>
            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white p-5 shadow-pop">
                <h3 id="extend-link-title" class="text-[15px] font-extrabold tracking-tight">
                    {{ $extending?->isExpired() ? 'Reopen this link' : 'Extend this link' }}
                </h3>
                <p class="mt-1 text-[11.5px] leading-relaxed text-gray-500">
                    {{ $extending?->community->name }} · {{ $extending?->periodLabel() }}.
                    The URL and QR stay the same — a sheet printed earlier keeps working.
                </p>

                <div class="mt-4">
                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Open for</span>
                    <div class="flex gap-1.5" role="radiogroup" aria-label="New validity in days">
                        @foreach (range(\App\Models\AssessmentLink::MIN_DAYS, \App\Models\AssessmentLink::MAX_DAYS) as $day)
                            <label @class([
                                'flex-1 cursor-pointer rounded-lg border px-0 py-2 text-center text-[12.5px] font-bold transition',
                                'border-lnu-800 bg-lnu-800 text-white shadow-sm' => $extendDays === $day,
                                'border-gray-200 bg-white text-gray-600 hover:border-lnu-300' => $extendDays !== $day,
                            ])>
                                <input type="radio" wire:model.live="extendDays" value="{{ $day }}" class="sr-only">
                                {{ $day }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-[11px] text-gray-400">
                        Counted from today · closes
                        <span class="font-semibold text-gray-600">{{ \App\Models\AssessmentLink::expiryFor($extendDays)->format('D, M j, Y g:i A') }}</span>
                    </p>
                    @error('extendDays') <p class="mt-1 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" wire:click="closeExtend">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="extend" wire:loading.attr="disabled" wire:target="extend">
                        <span wire:loading.remove wire:target="extend">{{ $extending?->isExpired() ? 'Reopen' : 'Extend' }}</span>
                        <span wire:loading wire:target="extend">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Revoke dialog ---------------------------------------------------- --}}
    @if ($showRevoke)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="revoke-link-title">
            <div class="absolute inset-0 bg-charcoal/40 backdrop-blur-sm" wire:click="closeRevoke"></div>
            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white p-5 shadow-pop">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100">
                        <x-sc.icon name="alert" class="h-5 w-5" />
                    </span>
                    <div>
                        <h3 id="revoke-link-title" class="text-[15px] font-extrabold tracking-tight">Close this link now?</h3>
                        <p class="mt-1 text-[11.5px] leading-relaxed text-gray-500">
                            {{ $revoking?->community->name }} · {{ $revoking?->periodLabel() }} will stop accepting answers immediately.
                            This cannot be undone — to collect more later, generate a new link.
                        </p>
                    </div>
                </div>
                <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-[11.5px] leading-relaxed text-gray-500">
                    Answers already received are not deleted. They stay in the review queue as usual.
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" wire:click="closeRevoke">Keep it open</button>
                    <button type="button" class="btn btn-primary !bg-red-600 hover:!bg-red-700" wire:click="revoke" wire:loading.attr="disabled" wire:target="revoke">
                        <span wire:loading.remove wire:target="revoke">Revoke link</span>
                        <span wire:loading wire:target="revoke">Closing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
