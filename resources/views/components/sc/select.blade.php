@props([
    'model',                {{-- Livewire property name (dots allowed, e.g. form.respondent_civil_status) --}}
    'value' => null,        {{-- current server-side value --}}
    'options' => [],        {{-- assoc array: value => label (a plain 0-based list of strings maps value = label) --}}
    'placeholder' => 'Select…',
    'search' => null,       {{-- null = auto (more than 12 options), true/false to force --}}
])

@php
    // Normalize to assoc value => label.
    if (array_is_list($options)) {
        $options = array_combine($options, $options);
    }

    $labels = [];
    $rawValues = [];
    foreach ($options as $optionValue => $label) {
        $labels[(string) $optionValue] = $label;
        $rawValues[(string) $optionValue] = $optionValue;
    }

    $showSearch = $search ?? (count($options) > 12);
    $initialLabel = $labels[(string) $value] ?? $placeholder;
@endphp

<div class="relative"
     x-data="{
        open: false,
        q: '',
        selected: @js((string) $value),
        labels: {{ json_encode($labels) }},
        values: {{ json_encode($rawValues) }},
        placeholder: @js($placeholder),
        pick(v) {
            this.selected = String(v);
            this.q = '';
            this.open = false;
            $wire.set(@js($model), this.values[String(v)] ?? v);
        },
        init() {
            $wire.$watch(@js($model), v => {
                this.selected = v === null || v === undefined ? '' : String(v);
            });
        }
     }"
     @click.outside="open = false"
     @keydown.escape.window="open = false">

    <button type="button" class="input w-full flex items-center justify-between gap-2 text-left !py-2.5"
            @click="open = ! open">
        <span class="truncate"
              x-text="labels[selected] || placeholder"
              x-bind:class="selected && labels[selected] ? 'text-charcoal font-semibold' : 'text-gray-400 font-medium'">{{ $initialLabel }}</span>
        <x-sc.icon name="chevron" class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         class="absolute left-0 right-0 top-full mt-1.5 z-30 sc-card p-0 shadow-pop overflow-hidden">
        @if ($showSearch)
            <div class="relative p-2 border-b border-gray-100 bg-white">
                <input type="text" x-model="q" @keydown.enter.prevent placeholder="Search…" class="input !py-2 !text-[12.5px] !pl-8" autocomplete="off">
                <x-sc.icon name="search" class="absolute left-[18px] top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
            </div>
        @endif
        <div class="max-h-60 overflow-y-auto">
            @forelse ($options as $optionValue => $label)
                <button type="button" data-label="{{ $label }}"
                        x-show="! q || $el.dataset.label.toLowerCase().includes(q.toLowerCase())"
                        @click="pick(@js((string) $optionValue))"
                        class="w-full flex items-center justify-between gap-2.5 px-3 py-2 text-left text-[12.5px] hover:bg-lnu-50/60 transition"
                        x-bind:class="selected === @js((string) $optionValue) ? 'bg-lnu-50/50' : ''">
                    <span class="truncate"
                          x-bind:class="selected === @js((string) $optionValue) ? 'font-bold text-charcoal' : 'text-gray-600 font-medium'">{{ $label }}</span>
                    <x-sc.icon name="check" class="w-3.5 h-3.5 text-lnu-700 shrink-0" x-show="selected === @js((string) $optionValue)" />
                </button>
            @empty
                <p class="px-3 py-4 text-[12px] text-gray-400 text-center">No options available.</p>
            @endforelse
        </div>
        <div class="px-3 py-1.5 border-t border-gray-100 bg-gray-50/70 text-[10.5px] text-gray-400 font-semibold">
            {{ count($options) }} option{{ count($options) === 1 ? '' : 's' }}
        </div>
    </div>
</div>
