@props([
    'options' => [],
    'selected' => [],
    'model' => null,
    'method' => null,
    'key' => null,
    'placeholder' => 'Select…',
    'id' => null,
    'invalid' => false,
])

@php
    $selected = is_array($selected) ? $selected : [];
    $labels = collect($options)->mapWithKeys(fn ($o) => [(string) $o['id'] => $o['label']])->all();
    $selectedState = $model
        ? '$wire.entangle('.json_encode($model).').live'
        : json_encode(array_map('strval', $selected));
    $componentKey = $model ?: ($id ?: ($key ?: 'default'));
@endphp

<div wire:key="sc-multi-select-{{ $componentKey }}" class="relative"
     x-data="{
        open: false,
        q: '',
        model: @js($model),
        selected: {{ $selectedState }},
        labels: {{ json_encode($labels) }},
        selectedValues() {
            return Array.isArray(this.selected) ? this.selected.map(value => String(value)) : [];
        },
        isSelected(id) {
            return this.selectedValues().includes(String(id));
        },
        toggle(id) {
            const current = Array.isArray(this.selected) ? [...this.selected] : [];
            const index = current.findIndex(value => String(value) === String(id));
            this.selected = index === -1
                ? [...current, id]
                : current.filter((_, itemIndex) => itemIndex !== index);

            // Legacy callers without a model still use the old toggle method.
            // All current multi-selects are model-bound and update the complete
            // array above, avoiding stale per-item toggle requests.
            if (! this.model && '{{ $method }}') {
                $wire.call('{{ $method }}', '{{ $key }}', id);
            }
        },
        sync(ids) {
            this.selected = Array.isArray(ids) ? ids : [];
        },
        get summary() {
            const picked = this.selectedValues().map(id => this.labels[id]).filter(Boolean);
            let s = picked.slice(0, 2).join(', ');
            if (picked.length > 2) s += ' +' + (picked.length - 2) + ' more';
            return s;
        }
     }"
     @click.outside="open = false"
     x-on:ms-sync-{{ $key }}.window="sync($event.detail.ids)">

    <button type="button" id="{{ $id }}" class="input w-full flex items-center justify-between gap-2 text-left !py-2.5 {{ $invalid ? '!border-red-400 !ring-2 !ring-red-100' : '' }}"
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

    <div x-cloak x-show="open" x-transition.opacity.duration.100ms role="listbox" aria-multiselectable="true"
         class="absolute left-0 right-0 top-full mt-1.5 z-30 sc-card p-0 shadow-pop overflow-hidden">
        <div class="relative p-2 border-b border-gray-100 bg-white">
            <input type="text" x-model="q" placeholder="Search…" class="input !py-2 !text-[12.5px] !pl-8" autocomplete="off">
            <x-sc.icon name="search" class="absolute left-[18px] top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
        </div>
        <div class="max-h-60 overflow-y-auto">
            @forelse ($options as $option)
                <button type="button" data-label="{{ $option['label'] }}" role="option"
                        x-show="! q || $el.dataset.label.toLowerCase().includes(q.toLowerCase())"
                        @click="toggle({{ json_encode($option['id']) }})"
                        :aria-selected="isSelected({{ json_encode($option['id']) }}) ? 'true' : 'false'"
                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-[12.5px] hover:bg-lnu-50/60 transition"
                        x-bind:class="isSelected({{ json_encode($option['id']) }}) ? 'bg-lnu-50/40' : ''">
                    <span class="w-[18px] h-[18px] rounded-md border flex items-center justify-center shrink-0 transition"
                          x-bind:class="isSelected({{ json_encode($option['id']) }}) ? 'bg-lnu-800 border-lnu-800 text-white' : 'border-gray-300 bg-white'">
                        <x-sc.icon name="check" class="w-3 h-3" x-show="isSelected({{ json_encode($option['id']) }})" />
                    </span>
                    <span class="truncate"
                          x-bind:class="isSelected({{ json_encode($option['id']) }}) ? 'font-bold text-charcoal' : 'text-gray-600 font-medium'">{{ $option['label'] }}</span>
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
