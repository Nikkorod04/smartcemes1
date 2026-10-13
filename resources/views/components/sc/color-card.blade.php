{{--
    Global colorful card container.

    Usage:
        <x-sc.color-card tone="blue" class="p-5">
            Card content
        </x-sc.color-card>

    Available tones: blue, teal, gold, indigo.
    The passed class attribute is preserved for layout and spacing utilities.
--}}
@props(['tone' => 'blue'])

@php
    $tone = in_array($tone, ['blue', 'teal', 'gold', 'indigo'], true) ? $tone : 'blue';
@endphp

<div {{ $attributes->merge(['class' => 'sc-card-colorful sc-card-colorful--'.$tone]) }}>
    {{ $slot }}
</div>
