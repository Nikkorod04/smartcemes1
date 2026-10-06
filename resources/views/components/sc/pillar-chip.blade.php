{{--
    Pillar chip (prototype `SC.pillarChip`).

    The three CESO pillars — Social / Economic / Environmental — rendered as the
    app's `.pillar` chip. The DB stores the pillar lower-case (`social`), the CSS
    classes are capitalised (`.pillar-Social`), so the label is normalised here
    rather than at every call site.

    An unknown/absent pillar renders the neutral dash, never a wrong colour.
--}}
@props(['pillar' => null])

@php
    $label = $pillar ? ucfirst((string) $pillar) : null;
    $known = in_array($label, ['Social', 'Economic', 'Environmental'], true);
@endphp

@if ($known)
    <span class="pillar pillar-{{ $label }}">{{ $label }}</span>
@else
    <span class="badge badge-gray">—</span>
@endif
