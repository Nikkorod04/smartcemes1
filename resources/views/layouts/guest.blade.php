<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SmartCEMES') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans text-charcoal antialiased bg-white">
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10 overflow-hidden">
            {{-- soft LNU-tinted accents on white --}}
            <div class="pointer-events-none absolute -top-28 -left-28 w-[26rem] h-[26rem] rounded-full bg-lnu-50 blur-3xl opacity-80"></div>
            <div class="pointer-events-none absolute -bottom-36 -right-28 w-[30rem] h-[30rem] rounded-full bg-gold-50 blur-3xl opacity-90"></div>
            <div class="pointer-events-none absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-lnu-800 via-gold-500 to-lnu-800"></div>

            {{-- brand header --}}
            <div class="relative flex flex-col items-center text-center">
                <img src="{{ asset('ceso_logobig.png') }}" alt="Leyte Normal University — Community Extension Services Office" class="w-24 h-24 rounded-full ring-4 ring-white shadow-card">
                <h1 class="mt-3.5 text-[22px] font-extrabold tracking-tight leading-none">Smart<span class="text-gold-600">CEMES</span></h1>
                <p class="mt-1.5 text-[11.5px] text-gray-400 font-semibold">Community Extension Services Office · Leyte Normal University</p>
            </div>

            {{-- card --}}
            <div class="relative w-full sm:max-w-[400px] mt-8 sc-card p-7 shadow-pop">
                {{ $slot }}
            </div>

            <p class="relative mt-6 text-[11px] text-gray-300 font-medium">Monitoring &amp; evaluation platform for community extension programs</p>
        </div>

        @livewireScripts
    </body>
</html>
