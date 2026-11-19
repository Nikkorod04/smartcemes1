@extends('layouts.public')

@section('title', 'Activity Evaluation - SmartCEMES')

@section('content')
    <div x-data="activityEvaluationForm(@js($config))" x-cloak class="space-y-4">

        {{-- Context: locked by the link, so the respondent cannot change it. --}}
        <div class="sc-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-lnu-700">Activity Evaluation</p>
                    <h1 class="mt-1.5 text-[19px] font-extrabold leading-tight tracking-tight text-charcoal">{{ $activity->title }}</h1>
                    <p class="mt-1 text-[11.5px] font-medium text-gray-500">
                        {{ $activity->planned_start_date->format('F j, Y') }}@if ($activity->venue) · {{ $activity->venue }}@endif
                    </p>
                    <p class="mt-0.5 text-[10.5px] font-medium text-gray-400">{{ $project->code }} · {{ $project->title }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                    <span class="badge badge-blue">~1 min</span>
                    <span class="badge badge-gray">Anonymous</span>
                </div>
            </div>
        </div>

        {{-- Closed between opening the page and pressing send. --}}
        <div x-show="closed" class="sc-card p-7 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <x-sc.icon name="clock" class="h-7 w-7" />
            </span>
            <h2 class="mt-4 text-[19px] font-extrabold leading-tight tracking-tight">This link is no longer open</h2>
            <p class="mt-2 text-[13px] leading-relaxed text-gray-500" x-text="error || 'Please ask the CESO staff for a new link.'"></p>
        </div>

        {{-- Thank you. --}}
        <div x-show="submitted" class="sc-card p-7 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <x-sc.icon name="check" class="h-7 w-7" />
            </span>
            <h2 class="mt-4 text-[19px] font-extrabold leading-tight tracking-tight">Thank you</h2>
            <p class="mt-2 text-[13px] leading-relaxed text-gray-500">
                Your answer was recorded anonymously. You can close this page.
            </p>
            <p x-show="reference" class="mt-3 text-[10.5px] font-semibold uppercase tracking-wide text-gray-300">
                Reference <span x-text="reference"></span>
            </p>
        </div>

        {{-- The form. --}}
        <form x-show="!submitted && !closed" x-on:submit.prevent="submit()" novalidate class="space-y-4">
            {{-- Bot trap: no person can see or reach this. --}}
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" tabindex="-1" autocomplete="off" x-model="honeypot"></label>
            </div>

            <div class="sc-card p-5 sm:p-6">
                <h2 class="text-[15px] font-extrabold tracking-tight text-charcoal">
                    How satisfied were you with this activity?
                </h2>
                <p class="mt-1 text-[11.5px] leading-relaxed text-gray-500">
                    Choose one. There is no right answer - your honest feedback helps the next session.
                </p>

                <div class="mt-4 grid grid-cols-5 gap-1.5 sm:gap-2" role="group" aria-label="Satisfaction from 1 to 5">
                    <template x-for="score in 5" x-bind:key="score">
                        <button type="button"
                                x-on:click="choose(score)"
                                x-bind:aria-pressed="satisfaction === score ? 'true' : 'false'"
                                x-bind:aria-label="labels[score]"
                                x-bind:class="satisfaction === score
                                    ? 'border-lnu-700 bg-lnu-700 text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-charcoal hover:border-lnu-300 hover:bg-lnu-50'"
                                class="flex flex-col items-center gap-0.5 rounded-xl border py-3 font-extrabold transition">
                            <span class="text-[18px] leading-none" x-text="score"></span>
                            <span class="text-[9.5px] font-bold uppercase tracking-wide"
                                  x-bind:class="satisfaction === score ? 'text-white/70' : 'text-gray-400'"
                                  x-text="score === 1 ? 'Low' : (score === 5 ? 'High' : '')"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-2 flex items-center justify-between text-[10.5px] font-semibold text-gray-400">
                    <span>Very dissatisfied</span>
                    <span>Very satisfied</span>
                </div>

                <p class="mt-3 text-center text-[12.5px] font-bold text-lnu-800" aria-live="polite">
                    <span x-show="satisfaction" x-text="labels[satisfaction]"></span>
                    <span x-show="!satisfaction" class="font-medium text-gray-300">Tap a number</span>
                </p>
            </div>

            <div class="sc-card p-5 sm:p-6">
                <label for="evaluation-comments" class="text-[15px] font-extrabold tracking-tight text-charcoal">
                    Anything you want to add? <span class="text-[11px] font-semibold text-gray-400">(optional)</span>
                </label>
                <p class="mt-1 text-[11.5px] leading-relaxed text-gray-500">
                    What worked well, what could be better, or what you would like next.
                </p>
                <textarea id="evaluation-comments" rows="4" x-model="comments"
                          x-bind:maxlength="maxComments"
                          class="input mt-3 resize-none"
                          placeholder="Your comment (optional)"></textarea>
                <p class="mt-1 text-right text-[10.5px] font-medium text-gray-400">
                    <span x-text="remaining"></span> characters left
                </p>
            </div>

            <div x-show="error" x-text="error"
                 class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[12px] font-semibold text-red-700"></div>

            <button type="submit" x-bind:disabled="submitting"
                    class="btn btn-primary w-full !py-3 text-[13px] disabled:opacity-60">
                <span x-show="!submitting" class="inline-flex items-center gap-1.5">
                    <x-sc.icon name="check" class="h-4 w-4" /> Send evaluation
                </span>
                <span x-show="submitting">Sending…</span>
            </button>

            <p class="pb-2 text-center text-[10.5px] leading-relaxed text-gray-400">
                Your answer is anonymous. It is not linked to your name or household.
            </p>
        </form>
    </div>
@endsection
