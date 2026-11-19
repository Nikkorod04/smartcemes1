<div class="activity-evaluation-page">
    @push('scripts')
        @vite('resources/js/share.js')
    @endpush

    <section class="pt-6">
        <a href="{{ route('projects.show', $project) }}?tab=activities"
           class="inline-flex items-center gap-1 text-[11.5px] font-bold text-lnu-700 transition hover:text-lnu-900">
            ← Back to {{ $project->title }}
        </a>

        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">
                    {{ $project->code }} · Evaluation
                </p>
                <h1 class="secretary-form-title">Evaluation QR</h1>
                <p class="secretary-form-subtitle">
                    {{ $activity->title }} ·
                    {{ $activity->planned_start_date->format('M j, Y') }} ·
                    {{ $activity->venue ?: 'Venue not set' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="badge badge-blue">{{ $activeCount }} open</span>
                <span class="badge badge-gray">{{ $links->count() - $activeCount }} ended</span>
                <span class="badge badge-green">{{ $total }} response{{ $total === 1 ? '' : 's' }}</span>
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
                    <h2 class="text-[15px] font-extrabold tracking-tight">New session link</h2>
                    <p class="mt-0.5 text-[11.5px] leading-snug text-gray-500">
                        Respondents answer anonymously on their own phone. No account, no name.
                    </p>
                </div>
            </div>

            <form wire:submit="generate" class="mt-4 space-y-3.5">
                <div>
                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Open for</span>
                    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="How many days the link stays open">
                        @foreach (range(\App\Models\ActivityEvaluationLink::MIN_DAYS, \App\Models\ActivityEvaluationLink::MAX_DAYS) as $day)
                            <label @class([
                                'min-w-[2.2rem] flex-1 cursor-pointer rounded-lg border px-0 py-2 text-center text-[12.5px] font-bold transition',
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
                        closes <span class="font-semibold text-gray-600">{{ \App\Models\ActivityEvaluationLink::expiryFor($days, $activity->planned_start_date)->format('D, M j, Y g:i A') }}</span>
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
                <li>• The link closes at 11:59 PM on its last day.</li>
                <li>• Extending keeps the same QR — no need to reprint.</li>
                <li>• Revoking closes the form; answers already received stay.</li>
                <li>• Responses are anonymous and separate from the XLSX evaluation import.</li>
            </ul>
        </section>

        {{-- Selected link -------------------------------------------------- --}}
        <section class="xl:col-span-7">
            @if ($selected)
                <div class="sc-card p-5" wire:key="evaluation-card-{{ $selected->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-[15px] font-extrabold tracking-tight">{{ $activity->title }}</h2>
                            <p class="mt-0.5 text-[11.5px] text-gray-500">
                                {{ $activity->planned_start_date->format('M j, Y') }} · {{ $activity->venue ?: 'Venue not set' }}
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
                                aria-label="QR code for this evaluation link"
                                role="img"></canvas>
                            <p class="mt-2 text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Scan to evaluate</p>
                        </div>

                        <div class="min-w-0 flex-1">
                            <label for="evaluation-url" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Link</label>
                            <div class="flex items-center gap-2">
                                <input id="evaluation-url" type="text" readonly value="{{ $selectedUrl }}"
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
                            </dl>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ $selectedUrl }}" target="_blank" rel="noopener" class="btn btn-primary !py-2 !px-3 text-[12px]">
                                    <x-sc.icon name="eye" class="h-4 w-4" /> Open form
                                </a>
                                <a href="{{ route('activities.evaluation.sheet', [$activity, $selected]) }}" target="_blank" rel="noopener" class="btn btn-outline !py-2 !px-3 text-[12px]">
                                    <x-sc.icon name="doc" class="h-4 w-4" /> Print card
                                </a>
                                @if (! $selected->isRevoked())
                                    <button type="button" wire:click="openExtend({{ $selected->id }})" class="btn btn-outline !py-2 !px-3 text-[12px]">
                                        <x-sc.icon name="clock" class="h-4 w-4" /> {{ $selected->isExpired() ? 'Reopen' : 'Extend' }}
                                    </button>
                                    <button type="button" wire:click="openRevoke({{ $selected->id }})" class="btn btn-ghost !py-2 !px-3 text-[12px] text-red-600 hover:bg-red-50">
                                        <x-sc.icon name="trash" class="h-4 w-4" /> Revoke
                                    </button>
                                @endif
                            </div>

                            {{-- Live arrivals. A child component so the 10s poll
                                 cannot redraw the QR above. --}}
                            <div class="mt-3">
                                <livewire:activities.evaluation-arrivals :link-id="$selected->id" wire:key="evaluation-arrivals-{{ $selected->id }}" />
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="sc-card flex h-full min-h-[280px] flex-col items-center justify-center p-8 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-300 ring-1 ring-gray-100">
                        <x-sc.icon name="copy" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-[13px] font-semibold text-gray-400">No link selected</p>
                    <p class="mt-1 max-w-xs text-[11.5px] leading-relaxed text-gray-400">
                        Generate a session link on the left and its QR code will appear here, ready to show or print.
                    </p>
                </div>
            @endif
        </section>
    </div>

    {{-- Public feedback ---------------------------------------------------- --}}
    <section class="mt-5 sc-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-[15px] font-extrabold tracking-tight">Public feedback</h2>
                <p class="mt-0.5 text-[11.5px] text-gray-500">
                    Anonymous responses from every session link on this activity. The XLSX evaluation import stays separate.
                </p>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-[26px] font-extrabold leading-none tracking-tight text-lnu-800">{{ $average ?? '—' }}</span>
                <span class="text-[11px] font-semibold text-gray-400">average of {{ $total }}</span>
            </div>
        </div>

        @if ($total === 0)
            <p class="mt-4 rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-[12px] font-medium text-gray-400">
                No responses yet. Show the QR at the end of the session and the count will appear here.
            </p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    @foreach (array_reverse($distribution, true) as $score => $count)
                        @php($pct = $total > 0 ? round($count / $total * 100) : 0)
                        <div class="flex items-center gap-2">
                            <span class="w-3 text-right text-[11px] font-bold text-gray-500">{{ $score }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-lnu-600" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="w-8 text-right text-[11px] font-semibold text-gray-400">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2">
                    <p class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Latest comments</p>
                    @forelse ($recent as $response)
                        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="badge badge-green !text-[10px]">{{ $response->satisfaction }}/5</span>
                                <span class="text-[10px] font-medium text-gray-400">{{ $response->created_at->format('M j, g:i A') }}</span>
                            </div>
                            <p class="mt-1 line-clamp-3 text-[11.5px] leading-relaxed text-gray-600">{{ $response->comments }}</p>
                        </div>
                    @empty
                        <p class="text-[11.5px] text-gray-400">No written comments yet.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </section>

    {{-- Link history ------------------------------------------------------- --}}
    <section class="mt-5">
        <div class="sc-card p-0 overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
                <h2 class="text-[13px] font-extrabold tracking-tight">Session links</h2>
                <span class="text-[11px] font-medium text-gray-400">{{ $links->count() }} total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="sc-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Created</th>
                            <th>Closes</th>
                            <th>Status</th>
                            <th class="!text-right">Responses</th>
                            <th class="!text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($links as $link)
                            <tr wire:key="evaluation-link-{{ $link->id }}">
                                <td class="whitespace-nowrap text-[12px] text-gray-500">{{ $link->created_at->format('M j, Y g:i A') }}</td>
                                <td class="whitespace-nowrap text-[12px] text-gray-500">{{ $link->expires_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                <td>
                                    @if ($link->isRevoked())
                                        <span class="badge badge-red">Revoked</span>
                                    @elseif ($link->isExpired())
                                        <span class="badge badge-gray">Expired</span>
                                    @else
                                        <span class="badge badge-green">Open</span>
                                    @endif
                                </td>
                                <td class="!text-right font-semibold">{{ $link->evaluations_count }}</td>
                                <td class="!text-right row-actions whitespace-nowrap">
                                    <button type="button" wire:click="select({{ $link->id }})" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Show QR</button>
                                    <a href="{{ route('activities.evaluation.sheet', [$activity, $link]) }}" target="_blank" rel="noopener" class="btn btn-ghost !px-2 !py-1 !text-[11px]">Print</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="!py-10 text-center text-[12.5px] font-medium text-gray-400">
                                    No session links yet. Generate one above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Extend dialog ------------------------------------------------------- --}}
    @if ($showExtend)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal/40 px-4" wire:key="extend-evaluation">
            <div class="sc-card w-full max-w-md p-5" role="dialog" aria-modal="true" aria-labelledby="extend-evaluation-title">
                <h3 id="extend-evaluation-title" class="text-[15px] font-extrabold tracking-tight">
                    {{ $extending?->isExpired() ? 'Reopen this link' : 'Extend this link' }}
                </h3>
                <p class="mt-2 text-[12px] leading-relaxed text-gray-500">
                    The URL and QR stay the same, so a card already shown or printed keeps working.
                </p>

                <div class="mt-4">
                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-500">Keep open for</span>
                    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="How many days to keep the link open">
                        @foreach (range(\App\Models\ActivityEvaluationLink::MIN_DAYS, \App\Models\ActivityEvaluationLink::MAX_DAYS) as $day)
                            <label @class([
                                'min-w-[2.2rem] flex-1 cursor-pointer rounded-lg border px-0 py-2 text-center text-[12.5px] font-bold transition',
                                'border-lnu-800 bg-lnu-800 text-white shadow-sm' => $extendDays === $day,
                                'border-gray-200 bg-white text-gray-600 hover:border-lnu-300' => $extendDays !== $day,
                            ])>
                                <input type="radio" wire:model.live="extendDays" value="{{ $day }}" class="sr-only">
                                {{ $day }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-[11px] text-gray-400">
                        day{{ $extendDays === 1 ? '' : 's' }} &middot;
                        closes <span class="font-semibold text-gray-600">{{ \App\Models\ActivityEvaluationLink::expiryFor($extendDays, $activity->planned_start_date)->format('D, M j, Y g:i A') }}</span>
                    </p>
                    @error('extendDays') <p class="mt-1 text-[11.5px] font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="closeExtend" class="btn btn-ghost !py-2 !px-3 text-[12px]">Cancel</button>
                    <button type="button" wire:click="extend" class="btn btn-primary !py-2 !px-3 text-[12px]">
                        {{ $extending?->isExpired() ? 'Reopen link' : 'Extend link' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Revoke dialog ------------------------------------------------------- --}}
    @if ($showRevoke)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-charcoal/40 px-4" wire:key="revoke-evaluation">
            <div class="sc-card w-full max-w-md p-5" role="dialog" aria-modal="true" aria-labelledby="revoke-evaluation-title">
                <h3 id="revoke-evaluation-title" class="text-[15px] font-extrabold tracking-tight">Revoke this link?</h3>
                <p class="mt-2 text-[12px] leading-relaxed text-gray-500">
                    The QR stops working immediately. Responses already received stay on the activity.
                    A revoked link cannot be reopened — generate a new one if the session continues.
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="closeRevoke" class="btn btn-ghost !py-2 !px-3 text-[12px]">Cancel</button>
                    <button type="button" wire:click="revoke" class="btn !bg-red-600 !text-white !py-2 !px-3 text-[12px] hover:!bg-red-700">Revoke link</button>
                </div>
            </div>
        </div>
    @endif
</div>
