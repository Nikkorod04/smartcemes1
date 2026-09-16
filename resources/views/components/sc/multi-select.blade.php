@props([
    'options' => [],
    'selected' => [],
    'method' => null,
    'key' => null,
    'placeholder' => 'Select…',
])

@php
    $selected = is_array($selected) ? $selected : [];
    $labels = collect($options)->mapWithKeys(fn ($o) => [(string) $o['id'] => $o['label']])->all();
@endphp

<div class="relative"
     x-data="{
        open: false,
        q: '',
        selected: {{ json_encode(array_map('strval', $selected)) }},
        labels: {{ json_encode($labels) }},
        toggle(id) {
            const sid = String(id);
            this.selected = this.selected.includes(sid)
                ? this.selected.filter(v => v !== sid)
                : [...this.selected, sid];
            $wire.call('{{ $method }}', '{{ $key }}', id);
        },
        get summary() {
            const picked = this.selected.map(id => this.labels[id]).filter(Boolean);
            let s = picked.slice(0, 2).join(', ');
            if (picked.length > 2) s += ' +' + (picked.length - 2) + ' more';
            return s;
        }
     }"
     @click.outside="open = false"
     x-on:ms-sync-{{ $key }}.window="selected = $event.detail.ids.map(String)">

    <button type="button" class="input w-full flex items-center justify-between gap-2 text-left !py-2.5"
            @click="open = ! open">
        <span class="truncate"
              x-text="summary || {{ json_encode($placeholder) }}"
              x-bind:class="selected.length ? 'text-charcoal font-semibold' : 'text-gray-400 font-medium'"></span>
        <span class="flex items-center gap-2 shrink-0">
            <span class="text-[10.5px] font-extrabold px-2 py-0.5 rounded-md bg-lnu-50 text-lnu-800 border border-lnu-100"
                  x-show="selected.length" x-cloak x-text="selected.length"></span>
            <x-sc.icon name="chevron" class="w-4 h-4 text-gray-400 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" />
        </span>
    </button>

    <div x-cloak x-show="open" x-transition.opacity.duration.100ms
         class="absolute left-0 right-0 top-full mt-1.5 z-30 sc-card p-0 shadow-pop overflow-hidden">
        <div class="relative p-2 border-b border-gray-100 bg-white">
            <input type="text" x-model="q" placeholder="Search…" class="input !py-2 !text-[12.5px] !pl-8" autocomplete="off">
            <x-sc.icon name="search" class="absolute left-[18px] top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
        </div>
        <div class="max-h-60 overflow-y-auto">
            @forelse ($options as $option)
                <button type="button" data-label="{{ $option['label'] }}"
                        x-show="! q || $el.dataset.label.toLowerCase().includes(q.toLowerCase())"
                        @click="toggle({{ json_encode($option['id']) }})"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-[12.5px] hover:bg-lnu-50/60 transition"
                        x-bind:class="selected.includes({{ json_encode((string) $option['id']) }}) ? 'bg-lnu-50/40' : ''">
                    <span class="w-[18px] h-[18px] rounded-md border flex items-center justify-center shrink-0 transition"
                          x-bind:class="selected.includes({{ json_encode((string) $option['id']) }}) ? 'bg-lnu-800 border-lnu-800 text-white' : 'border-gray-300 bg-white'">
                        <x-sc.icon name="check" class="w-3 h-3" x-show="selected.includes({{ json_encode((string) $option['id']) }})" />
                    </span>
                    <span class="truncate"
                          x-bind:class="selected.includes({{ json_encode((string) $option['id']) }}) ? 'font-bold text-charcoal' : 'text-gray-600 font-medium'">{{ $option['label'] }}</span>
                </button>
            @empty
                <p class="px-3 py-4 text-[12px] text-gray-400 text-center">No options available.</p>
            @endforelse
        </div>
        <div class="px-3 py-2 border-t border-gray-100 bg-gray-50/70 text-[11px] text-gray-400 font-semibold">
            <span x-text="selected.length"></span> selected — click a row to toggle
        </div>
    </div>
</div>
