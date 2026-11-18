<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Community Needs Assessment · SmartCEMES')</title>

    @vite(['resources/css/app.css', 'resources/js/public.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-[#f5f7fb] font-sans text-charcoal antialiased">
    <header class="bg-gradient-to-br from-lnu-800 to-lnu-700 text-white">
        <div class="mx-auto flex w-full max-w-2xl items-center gap-3 px-4 py-4">
            <img src="{{ asset('ceso_logobig.png') }}" alt=""
                 class="h-10 w-10 shrink-0 rounded-full ring-2 ring-white/30">
            <div class="min-w-0">
                <p class="text-[15px] font-extrabold leading-none tracking-tight">
                    Smart<span class="text-gold-400">CEMES</span>
                </p>
                <p class="mt-1 text-[10.5px] font-semibold leading-tight text-white/70">
                    Community Extension Services Office · Leyte Normal University
                </p>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-2xl px-4 py-6 sm:py-9">
        @yield('content')
    </main>

    <footer class="mx-auto w-full max-w-2xl px-4 pb-10 text-center text-[10.5px] font-medium text-gray-400">
        Leyte Normal University · Community Extension Services Office
    </footer>

    @stack('scripts')
</body>
</html>
