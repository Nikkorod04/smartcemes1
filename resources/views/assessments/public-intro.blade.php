@extends('layouts.public')

@section('title', $link->community->name.' · Community Needs Assessment')

@section('content')
    <div class="space-y-4">
        <div class="sc-card p-6">
            <p class="text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-lnu-700">
                Community Needs Assessment
            </p>

            <h1 class="mt-2 text-[22px] font-extrabold leading-tight tracking-tight text-charcoal">
                {{ $link->community->name }}
            </h1>

            <p class="mt-1 text-[12.5px] font-medium text-gray-500">
                {{ $link->community->municipality }}, {{ $link->community->province }} · {{ $link->periodLabel() }}
            </p>

            <p class="mt-4 text-[13px] leading-relaxed text-gray-600">
                Your answers help the CESO office plan the trainings and programs that your community needs.
                The form has 9 short sections and takes about 10 minutes. No account is needed.
            </p>

            <dl class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-gray-50 px-3.5 py-3">
                    <dt class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Sections</dt>
                    <dd class="mt-0.5 text-[15px] font-extrabold text-charcoal">9</dd>
                </div>
                <div class="rounded-xl bg-gray-50 px-3.5 py-3">
                    <dt class="text-[10.5px] font-bold uppercase tracking-wide text-gray-400">Estimated time</dt>
                    <dd class="mt-0.5 text-[15px] font-extrabold text-charcoal">~10 minutes</dd>
                </div>
            </dl>

            {{-- STEP 1 PLACEHOLDER — replaced by the questionnaire in Step 2. --}}
            <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-amber-100 bg-amber-50 px-3.5 py-3 text-[11.5px] leading-relaxed text-amber-900">
                <x-sc.icon name="clock" class="mt-0.5 h-4 w-4 shrink-0" />
                <p>
                    <span class="font-extrabold">This form is not yet open.</span>
                    The questions will appear here once the CESO office activates the link.
                </p>
            </div>
        </div>

        <div class="sc-card p-5">
            <p class="text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Your data</p>
            <p class="mt-2 text-[11.5px] leading-relaxed text-gray-500">
                Answers are submitted to the Leyte Normal University CESO office and are used only for
                community extension planning. Your name is recorded so the office can validate the response.
            </p>
        </div>
    </div>
@endsection
