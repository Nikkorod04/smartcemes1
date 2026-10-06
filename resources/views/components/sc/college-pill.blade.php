{{--
    College pill (revision §5 R3 / prototype SC.collegePill).

    A small coloured chip identifying a college. The colour is the same
    CAS/COE/CME/GRAD palette the engagement board's chart and split bar use, so a
    colour means the same thing everywhere on the page.

    Renders a neutral dash when there is no college — a faculty member whose
    department is unrecognised legitimately has none, and a wrong colour would
    be worse than none.
--}}
@props(['code' => null, 'name' => null, 'short' => false])

@php
    $palette = [
        'CAS' => ['bg' => 'bg-lnu-50', 'text' => 'text-lnu-700', 'dot' => '#003599'],
        'COE' => ['bg' => 'bg-gold-50', 'text' => 'text-gold-700', 'dot' => '#F6B800'],
        'CME' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'dot' => '#10b981'],
    ];
    $tone = $code ? ($palette[strtoupper($code)] ?? null) : null;
@endphp

@if ($code && $tone)
    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10.5px] font-bold {{ $tone['bg'] }} {{ $tone['text'] }} shrink-0"
          @if ($name) title="{{ $name }}" @endif>
        <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $tone['dot'] }}"></span>
        {{ strtoupper($code) }}@if (! $short && $name)<span class="font-medium opacity-70">— {{ $name }}</span>@endif
    </span>
@else
    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-gray-100 text-gray-400 shrink-0">
        —
    </span>
@endif
