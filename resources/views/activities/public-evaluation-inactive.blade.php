@extends('layouts.public')

@section('title', 'Evaluation link unavailable - SmartCEMES')

@section('content')
    <div class="sc-card p-7 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
            <x-sc.icon name="clock" class="h-7 w-7" />
        </span>

        <h1 class="mt-4 text-[20px] font-extrabold leading-tight tracking-tight text-charcoal">
            This link is no longer active
        </h1>

        <p class="mt-2 text-[13px] leading-relaxed text-gray-500">
            @if ($reason === 'expired')
                This evaluation link has expired.
            @elseif ($reason === 'revoked')
                This evaluation link has been closed.
            @else
                We could not find this evaluation link.
            @endif
            Please ask the CESO staff for a new link.
        </p>
    </div>
@endsection
