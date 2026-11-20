<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle ?? $title ?? 'Report' }} &middot; SmartCEMES</title>
    @vite(['resources/css/app.css'])

    <style>
        /* The paper is A4 LANDSCAPE on screen and on paper, so the workspace
           preview and the printed sheet are the same shape. */
        @media print {
            @page { size: A4 landscape; margin: 12mm; }

            body { background: #fff !important; }
            .no-print { display: none !important; }

            .report-paper {
                width: auto !important;
                max-width: none !important;
                min-height: 0 !important;
                margin: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            #print-area { padding: 0 !important; }

            /* A table may cross a page, but a row may not. Headers repeat. */
            table { page-break-inside: auto; }
            tr, .report-section { page-break-inside: avoid; }
            thead { display: table-header-group; }
            h1, h2, h3 { page-break-after: avoid; }
        }
    </style>
</head>
<body class="{{ request()->boolean('preview') ? 'bg-white' : 'bg-[#eef2f8]' }} font-sans text-charcoal antialiased">

    @unless (request()->boolean('preview'))
        <div class="no-print mx-auto flex w-full max-w-[297mm] flex-wrap items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('reports.index') }}" class="btn btn-ghost !px-3 !py-2 !text-[11.5px]">
                <x-sc.icon name="chevron-left" class="w-3.5 h-3.5" />Back to reports
            </a>
            <div class="flex items-center gap-2">
                <span class="hidden text-[11px] text-gray-400 sm:inline">A4 landscape &middot; use Save as PDF</span>
                <button type="button" onclick="window.print()" class="btn btn-primary !px-3 !py-2 !text-[11.5px]">
                    <x-sc.icon name="doc" class="w-3.5 h-3.5" />Print / Save as PDF
                </button>
            </div>
        </div>
    @endunless

    <div class="overflow-x-auto">
        <div class="report-paper mx-auto w-[297mm] min-h-[210mm] bg-white {{ request()->boolean('preview') ? '' : 'my-2 shadow-xl ring-1 ring-black/5' }}">
            <div id="print-area" class="p-[12mm]">

                {{-- University letterhead --}}
                <header class="border-b-4 border-lnu-800 pb-4">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('ceso_logobig.png') }}" alt="Leyte Normal University seal"
                             class="h-14 w-14 shrink-0 rounded-full ring-1 ring-gray-200">
                        <div class="min-w-0">
                            <p class="text-[18px] font-black uppercase leading-none tracking-tight text-lnu-800">Leyte Normal University</p>
                            <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500">Community Extension Services Office</p>
                            <p class="text-[10.5px] text-gray-400">Tacloban City, Leyte &middot; Republic of the Philippines</p>
                        </div>
                        <div class="ml-auto shrink-0 text-right">
                            <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-gray-400">SmartCEMES</p>
                            <p class="mt-1 text-[9.5px] font-medium text-gray-400">Generated {{ now()->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h1 class="text-[21px] font-extrabold leading-tight tracking-tight text-charcoal">{{ $reportTitle }}</h1>
                        @isset($subtitle)
                            <p class="mt-1 text-[12.5px] font-medium text-gray-500">{{ $subtitle }}</p>
                        @endisset
                        @isset($scopeLabel)
                            <p class="mt-0.5 text-[11px] font-medium text-gray-400">Scope: {{ $scopeLabel }}</p>
                        @endisset
                    </div>
                </header>

                <div class="pt-6">
                    @yield('content')
                </div>

                {{-- Signature blocks. Names stay blank: these are signed on paper. --}}
                <div class="mt-14 grid grid-cols-3 gap-8" id="print-signatories">
                    <div class="text-center">
                        <p class="text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Prepared by</p>
                        <div class="mx-auto mt-10 w-52 border-t border-gray-800"></div>
                        <p class="mt-1 text-[11px] font-medium text-gray-500">Name and Signature</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Reviewed by</p>
                        <div class="mx-auto mt-10 w-52 border-t border-gray-800"></div>
                        <p class="mt-1 text-[11px] font-medium text-gray-500">Name and Signature</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[10.5px] font-extrabold uppercase tracking-[0.12em] text-gray-500">Approved by</p>
                        <div class="mx-auto mt-10 w-52 border-t border-gray-800"></div>
                        <p class="mt-1 text-[11px] font-medium text-gray-500">Name and Signature</p>
                    </div>
                </div>

                <footer class="mt-8 border-t border-gray-200 pt-2 text-center text-[9px] text-gray-400">
                    SmartCEMES &middot; Community Extension Services Office &middot; Leyte Normal University
                </footer>
            </div>
        </div>
    </div>
</body>
</html>
