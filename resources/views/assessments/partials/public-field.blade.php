@php
    $col = \App\Services\AssessmentTemplate::COLUMNS[$field] ?? ['type' => $type, 'label' => $field];

    $options = match ($type) {
        'yesno' => ['Yes', 'No'],
        'rating' => config('smartcemes.vocab.barangay_service_ratings'),
        default => config('smartcemes.vocab.'.$col['vocab']) ?? [],
    };

    $hasOther = ! in_array($type, ['yesno', 'rating'], true) && in_array('Other', $options, true);
    $max = $col['max'] ?? null;
@endphp

<div class="mt-5 first:mt-0" @if (! empty($visibility)) x-show="{{ $visibility }}" @endif>
    <div class="flex items-baseline gap-1.5">
        <p class="text-[12px] font-semibold text-charcoal">{{ $label }}</p>
        @if ($hint)
            <span class="text-[11px] font-normal text-gray-400">· {{ $hint }}</span>
        @endif
        @if ($max)
            <span class="ml-auto text-[11px] font-bold"
                  x-bind:class="atCap('{{ $field }}') ? 'text-amber-600' : 'text-gray-400'"
                  x-text="countOf('{{ $field }}') + '/{{ $max }}'"></span>
        @endif
    </div>

    <div class="chip-group mt-2 flex flex-wrap gap-2">
        @foreach ($options as $option)
            <button type="button"
                    class="chip"
                    x-on:click="pick(@js($field), @js($option))"
                    x-bind:class="{ on: isOn(@js($field), @js($option)) }">{{ $option }}</button>
        @endforeach
    </div>

    @if ($max)
        <p class="mt-1.5 text-[11px] font-medium text-amber-600" x-show="atCap(@js($field))">
            Up to {{ $max }} — tap a chosen answer to remove it.
        </p>
    @endif

    @if ($hasOther)
        <div class="mt-3 border-l-2 border-gold-400 pl-3" x-show="isOn(@js($field), 'Other')">
            <label class="label !mb-1 !text-[11px]">Other — please specify</label>
            <input type="text"
                   class="input !py-2 !text-[13px]"
                   x-bind:value="form['{{ $field }}_other'] || ''"
                   x-on:input="form['{{ $field }}_other'] = $el.value; persist()"
                   placeholder="Type your answer">
        </div>
    @endif

    <p class="mt-1.5 text-[11px] font-medium text-red-600" x-show="errors[@js($field)]" x-text="errors[@js($field)]"></p>
</div>
