<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'SmartCEMES') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('ceso_logobig.png') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-gray-50 text-charcoal font-sans antialiased">
        @include('layouts.partials.sidebar')
        @include('layouts.partials.topbar')

        <main class="pl-72 pr-6 pb-12">
            {{ $slot }}
        </main>

        <div id="sc-toasts" class="fixed bottom-5 right-5 z-[90] space-y-2 no-print"></div>

        @livewireScripts
    </body>
</html>