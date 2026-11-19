<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Evaluation card - {{ $activity->title }}</title>

    @vite(['resources/css/app.css', 'resources/js/share.js'])

    <style>
        @page { size: A4; margin: 12mm; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .sheet {
                margin: 0 !important;
                max-width: none !important;
                box-shadow: none !important;
                border: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[#eef2f8] font-sans text-charcoal antialiased">

    <div class="no-print mx-auto flex w-full max-w-[210mm] items-center justify-between gap-3 px-4 py-4">
        <a href="{{ route('activities.evaluation', $activity) }}"
           class="text-[12px] font-bold text-lnu-700 underline underline-offset-2">
            &larr; Back to evaluation links
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary !py-2 !px-4 text-[12px]">
            Print this card
        </button>
    </div>

    <main class="sheet mx-auto mb-8 w-full max-w-[210mm] bg-white p-8 shadow-xl ring-1 ring-black/5 sm:p-12">

        <header class="flex items-center gap-3 border-b border-gray-200 pb-4">
            <img src="{{ asset('ceso_logobig.png') }}" alt="" class="h-12 w-12 shrink-0 rounded-full ring-1 ring-gray-200">
            <div class="min-w-0">
                <p class="text-[15px] font-extrabold leading-none tracking-tight">
                    Smart<span class="text-blue-700">CEMES</span>
                </p>
                <p class="mt-1 text-[10.5px] font-semibold leading-tight text-gray-500">
                    Community Extension Services Office &middot; Leyte Normal University
                </p>
            </div>
            <p class="ml-auto text-right text-[9.5px] font-bold uppercase tracking-[0.14em] text-gray-400">
                Activity<br>evaluation
            </p>
        </header>

        @if ($link->isRevoked())
            <p class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11.5px] font-semibold text-red-700">
                This link was revoked. The QR code below no longer works &mdash; generate a new link before printing.
            </p>
        @elseif ($link->isExpired())
            <p class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11.5px] font-semibold text-amber-800">
                This link has expired. Reopen it from the activity evaluation screen, then print again.
            </p>
        @endif

        <section class="mt-6 text-center">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-lnu-700">
                {{ $project->code }}
            </p>
            <h1 class="mt-2 text-[24px] font-extrabold leading-tight tracking-tight">
                {{ $activity->title }}
            </h1>
            <p class="mt-1 text-[12.5px] font-medium text-gray-500">
                {{ $activity->planned_start_date->format('F j, Y') }}
                @if ($activity->venue)
                    &middot; {{ $activity->venue }}
                @endif
            </p>
            <p class="mx-auto mt-4 max-w-md text-[12px] leading-relaxed text-gray-600">
                Thank you for joining us. Please scan the code and rate this activity &mdash;
                it takes about a minute and your answer is anonymous.
            </p>
        </section>

        <section class="mt-6 flex flex-col items-center">
            <div class="rounded-2xl border border-gray-200 bg-white p-4">
                <canvas width="440" height="440"
                        data-qr="{{ $url }}"
                        data-qr-size="280"
                        style="width:280px;height:280px"
                        role="img"
                        aria-label="QR code for the {{ $activity->title }} evaluation form"></canvas>
            </div>
            <p class="mt-3 text-[10.5px] font-bold uppercase tracking-[0.16em] text-gray-400">
                Scan to evaluate
            </p>
            <p class="mt-2 max-w-full break-all text-center font-mono text-[9.5px] leading-relaxed text-gray-400">
                {{ $url }}
            </p>
        </section>

        <section class="mt-7 grid gap-4 border-t border-gray-200 pt-5 sm:grid-cols-2">
            <div>
                <h2 class="text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-gray-400">How to answer</h2>
                <ol class="mt-2 space-y-1.5 text-[11.5px] leading-relaxed text-gray-600">
                    <li><span class="font-bold text-charcoal">1.</span> Open your phone camera.</li>
                    <li><span class="font-bold text-charcoal">2.</span> Point it at the QR code.</li>
                    <li><span class="font-bold text-charcoal">3.</span> Tap the link that appears.</li>
                    <li><span class="font-bold text-charcoal">4.</span> Rate the activity from 1 to 5.</li>
                    <li><span class="font-bold text-charcoal">5.</span> Add a comment if you wish, then send.</li>
                </ol>
            </div>

            <div class="sm:border-l sm:border-gray-200 sm:pl-5">
                <h2 class="text-[10.5px] font-extrabold uppercase tracking-[0.14em] text-gray-400">Good to know</h2>
                <ul class="mt-2 space-y-1.5 text-[11.5px] leading-relaxed text-gray-600">
                    <li>&bull; No account or app is needed.</li>
                    <li>&bull; Your answer is anonymous.</li>
                    <li>&bull; One response is enough.</li>
                    <li>&bull; The link closes on
                        <span class="font-semibold text-charcoal">
                            {{ $link->expires_at?->format('F j, Y') ?? 'an unrecorded date' }}
                        </span>.
                    </li>
                </ul>
            </div>
        </section>

        <footer class="mt-8 flex items-end justify-between gap-4 border-t border-gray-200 pt-3 text-[9.5px] text-gray-400">
            <p>
                Prepared by the CESO field team
                @if ($link->creator)
                    &middot; {{ $link->creator->name }}
                @endif
            </p>
            <p class="text-right">
                {{ $activity->planned_start_date->format('M j, Y') }}<br>
                SmartCEMES &middot; Leyte Normal University
            </p>
        </footer>
    </main>

    <script>
        // The QR module is a deferred ES module, so it is defined by the time
        // `load` fires. Drawing on load also means the canvas exists.
        window.addEventListener('load', function () {
            document.querySelectorAll('canvas[data-qr]').forEach(function (canvas) {
                window.scDrawQr?.(canvas, canvas.dataset.qr);
            });
        });
    </script>
</body>
</html>
