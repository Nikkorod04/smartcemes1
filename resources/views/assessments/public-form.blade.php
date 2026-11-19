@extends('layouts.public')

@section('title', $link->community->name.' · Community Needs Assessment')

@section('content')
@php
    $stepOf = [];
    foreach ($sections as $section) {
        $stepOf[$section['key']] = \App\Services\AssessmentFormSchema::stepForSection($section['key']);
    }
@endphp

<div x-data="assessmentForm(@js($config))" x-cloak class="space-y-4">

    {{-- Context: locked by the link, so the respondent cannot change it. --}}
    <div class="sc-card p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-lnu-700">Community Needs Assessment</p>
                <h1 class="mt-1.5 text-[19px] font-extrabold leading-tight tracking-tight text-charcoal">{{ $link->community->name }}</h1>
                <p class="mt-1 text-[11.5px] font-medium text-gray-500">{{ $link->community->municipality }}, {{ $link->community->province }} · {{ $link->periodLabel() }}</p>
            </div>
            <span class="badge badge-blue shrink-0">~10 min</span>
        </div>
    </div>

    {{-- Answers found on this device. --}}
    <div x-show="restoredAt" class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
        <x-sc.icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
        <div class="min-w-0 flex-1">
            <p class="text-[11.5px] font-extrabold text-blue-900">Your earlier answers were restored</p>
            <p class="mt-0.5 text-[11px] text-blue-700" x-text="restoredLabel"></p>
        </div>
        <button type="button" class="shrink-0 text-[11px] font-bold text-blue-700 underline" x-on:click="startOver()">Start over</button>
    </div>

    {{-- Progress --}}
    <div class="sc-card px-4 py-3" x-show="!done && !closed">
        <div class="flex items-center justify-between gap-3">
            <span class="text-[10.5px] font-extrabold uppercase tracking-wide text-gray-400">
                Step <span x-text="step"></span> of 5
            </span>
            <span class="text-[11px] font-bold text-lnu-800" x-text="steps[step - 1].title"></span>
        </div>
        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-lnu-700 transition-all duration-300"
                 x-bind:style="'width:' + (step / 5 * 100) + '%'"></div>
        </div>
    </div>

    <form x-on:submit.prevent="submit()" novalidate class="space-y-4" x-show="!closed">
        {{-- Bot trap: no person can see or reach this. Answered 201 without a
             row, so a script has no way to tell it was ignored. --}}
        <div class="hidden" aria-hidden="true">
            <label>Website <input type="text" tabindex="-1" autocomplete="off" x-model="honeypot"></label>
        </div>
        @foreach ($sections as $section)
            <div x-show="step === {{ $stepOf[$section['key']] }}" class="sc-card p-5 sm:p-6">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Section {{ $section['key'] }}</p>
                <h2 class="mt-1 text-[15px] font-extrabold tracking-tight text-charcoal">{{ $section['title'] }}</h2>

                {{-- Identity block: these four fields are not part of the
                     section map (the wizard renders them the same way). --}}
                @if ($section['key'] === 'I')
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label">First name <span class="text-red-500">*</span></label>
                            <input type="text" class="input" autocomplete="given-name" inputmode="text"
                                   x-bind:value="form.respondent_first_name"
                                   x-on:input="onNameInput('respondent_first_name', $el)">
                            <p class="mt-1 text-[11px] font-medium text-red-600" x-show="errors.respondent_first_name" x-text="errors.respondent_first_name"></p>
                        </div>
                        <div>
                            <label class="label">Middle name</label>
                            <input type="text" class="input" autocomplete="additional-name" inputmode="text"
                                   x-bind:value="form.respondent_middle_name"
                                   x-on:input="onNameInput('respondent_middle_name', $el)">
                        </div>
                        <div>
                            <label class="label">Last name <span class="text-red-500">*</span></label>
                            <input type="text" class="input" autocomplete="family-name" inputmode="text"
                                   x-bind:value="form.respondent_last_name"
                                   x-on:input="onNameInput('respondent_last_name', $el)">
                            <p class="mt-1 text-[11px] font-medium text-red-600" x-show="errors.respondent_last_name" x-text="errors.respondent_last_name"></p>
                        </div>
                        <div>
                            <label class="label">Age</label>
                            <input type="number" min="15" max="120" inputmode="numeric" class="input"
                                   x-model="form.respondent_age" x-on:input="persist()">
                            <p class="mt-1 text-[11px] font-medium text-red-600" x-show="errors.respondent_age" x-text="errors.respondent_age"></p>
                        </div>
                    </div>
                @endif

                @foreach ($section['fields'] as $field => $type)
                    @include('assessments.partials.public-field', [
                        'field' => $field,
                        'type' => $type,
                        'label' => \App\Services\AssessmentFormSchema::labelFor($field),
                        'hint' => \App\Services\AssessmentFormSchema::hintFor($field),
                        'visibility' => $visibility[$field] ?? null,
                    ])
                @endforeach

                @if ($section['key'] === 'IX')
                    <div class="mt-5">
                        <label class="text-[12px] font-semibold text-charcoal">Anything else the barangay should know?</label>
                        <p class="text-[11px] font-normal text-gray-400">Optional</p>
                        <textarea rows="3" class="input mt-2" placeholder="Write your answer here"
                                  x-model="form.general_feedback" x-on:input="persist()"></textarea>
                    </div>

                    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-lnu-700"
                                   x-model="consent" x-on:change="persist()">
                            <span class="text-[11.5px] leading-relaxed text-gray-600">
                                I agree that these answers may be recorded and used by the Leyte Normal University
                                CESO office for community extension planning. I understand that an office staff member
                                will review this response before it is used.
                            </span>
                        </label>
                        <p class="mt-2 text-[11px] font-medium text-red-600" x-show="errors.consent" x-text="errors.consent"></p>
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Submitted --}}
        <div x-show="done" x-cloak class="sc-card p-7 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <x-sc.icon name="check" class="h-7 w-7" />
            </span>
            <h2 class="mt-4 text-[19px] font-extrabold tracking-tight text-charcoal">Answers sent</h2>
            <p class="mx-auto mt-2 max-w-md text-[12.5px] leading-relaxed text-gray-500">
                Thank you. A staff member of the CESO office will review your answers before they are used
                for community planning.
            </p>
            <p class="mt-3 text-[11px] font-semibold text-gray-400" x-show="reference">
                Reference: <span x-text="reference"></span>
            </p>
            <p class="mx-auto mt-3 max-w-sm text-[11px] leading-relaxed text-gray-400">
                You can close this page now. Your answers are no longer stored on this phone.
            </p>
        </div>

        {{-- The link was closed or expired while the form was being filled. --}}
        <div x-show="closed" x-cloak class="sc-card p-7 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <x-sc.icon name="clock" class="h-7 w-7" />
            </span>
            <h2 class="mt-4 text-[19px] font-extrabold tracking-tight text-charcoal">This link is no longer open</h2>
            <p class="mx-auto mt-2 max-w-md text-[12.5px] leading-relaxed text-gray-500" x-text="submitError"></p>
            <p class="mx-auto mt-3 max-w-md text-[11.5px] leading-relaxed text-gray-400">
                Your answers were not sent. Please ask the CESO office for a new link if you would still like to take part.
            </p>
        </div>
    </form>

    {{-- Failed send: answers are safe on the device, so offer a retry. --}}
    <div x-show="submitError && !closed && !done" x-cloak
         class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
        <x-sc.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-red-600" />
        <div class="min-w-0 flex-1">
            <p class="text-[11.5px] font-semibold leading-relaxed text-red-800" x-text="submitError"></p>
            <button type="button" class="mt-1.5 text-[11px] font-bold text-red-700 underline"
                    x-on:click="submit()" x-bind:disabled="submitting">Try again</button>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="sticky bottom-3 z-10" x-show="!done && !closed">
        <div class="sc-card flex items-center justify-between gap-3 px-4 py-3">
            <button type="button" class="btn btn-outline"
                    x-bind:class="step === 1 ? 'opacity-40 pointer-events-none' : ''"
                    x-on:click="prev()">← Back</button>

            <p class="text-[10.5px] font-semibold text-gray-400" x-text="steps[step - 1].blurb"></p>

            <button type="button" class="btn btn-primary" x-show="step < 5" x-on:click="next()">Next →</button>
            {{-- `type="button"` on purpose: this nav bar sits outside the <form>,
                 so a submit button here would have no form to submit. Enter in a
                 text field still submits through the form's own handler. --}}
            <button type="button" class="btn btn-primary" x-show="step === 5"
                    x-on:click="submit()" x-bind:disabled="submitting">
                <span x-show="!submitting">Send answers</span>
                <span x-show="submitting" x-cloak>Sending…</span>
            </button>
        </div>
    </div>

    <p class="pb-2 text-center text-[10.5px] font-medium leading-relaxed text-gray-400">
        Your answers are stored on this device as you type.<br>
        Leyte Normal University · Community Extension Services Office
    </p>
</div>
@endsection
