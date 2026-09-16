<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Report' }} · SmartCEMES</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-charcoal font-sans antialiased">
<div class="max-w-[860px] mx-auto px-6 py-8">
    {{-- University letterhead --}}
    <header class="text-center border-b-4 border-lnu-800 pb-4 mb-6" id="print-letterhead">
        <div class="flex items-center justify-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-lnu-800 flex items-center justify-center">
                <span class="text-white font-black text-lg tracking-tight">SC</span>
            </div>
            <div class="text-center">
                <p class="font-black text-[18px] tracking-tight text-lnu-800 uppercase">Leyte Normal University</p>
                <p class="text-[11.5px] text-gray-500 font-semibold uppercase tracking-wide">Community Extension Services Office</p>
                <p class="text-[11px] text-gray-400">Tacloban City, Leyte · Republic of the Philippines</p>
            </div>
        </div>
        <h1 class="mt-4 font-extrabold text-[20px] tracking-tight text-charcoal">{{ $reportTitle }}</h1>
        @isset($subtitle)
            <p class="text-[12.5px] text-gray-500 font-medium mt-1">{{ $subtitle }}</p>
        @endisset
    </header>

    <div id="print-area">
        @yield('content')
    </div>

    {{-- Signatory blocks --}}
    <div class="mt-14 grid grid-cols-3 gap-8" id="print-signatories">
        <div class="text-center">
            <div class="border-t border-gray-800 w-48 mx-auto"></div>
            <p class="text-[12px] font-bold mt-2">Prepared by</p>
            <p class="text-[11.5px] text-gray-500">CESO Faculty / Program Lead</p>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-800 w-48 mx-auto"></div>
            <p class="text-[12px] font-bold mt-2">Reviewed by</p>
            <p class="text-[11px] text-gray-500">CESO Secretary</p>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-800 w-48 mx-auto"></div>
            <p class="text-[12px] font-bold mt-2">Approved by</p>
            <p class="text-[11px] text-gray-500">Director, Community Extension Services Office</p>
        </div>
    </div>

    <footer class="mt-10 text-center text-[10px] text-gray-300 font-medium no-print">
        SmartCEMES · Community Extension Services Office · Leyte Normal University
    </footer>
</div>
</body>
</html>