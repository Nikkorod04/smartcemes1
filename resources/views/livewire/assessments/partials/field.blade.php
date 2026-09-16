@php($value = $form[$field] ?? null)

<div @class([$class ?? ''])>
    <p class="text-[12px] font-semibold mb-2">
        {{ $label }}
        @if ($type === 'multi')
            <span class="text-[11px] text-gray-400 font-normal">· {{ $hint ?? 'select all that apply' }}</span>
        @endif
    </p>
    <div class="chip-group flex flex-wrap gap-2">
        @foreach ($options as $option)
            @if ($type === 'multi')
                <button type="button" wire:click="toggleOption('{{ $field }}', '{{ $option }}')"
                        @class(['chip', 'on' => in_array($option, is_array($value) ? $value : [], true)])>{{ $option }}</button>
            @else
                <button type="button" wire:click="$set('form.{{ $field }}', '{{ $option }}')"
                        @class(['chip', 'on' => ($value ?? '') === $option])>{{ $option }}</button>
            @endif
        @endforeach
    </div>
    @error('form.'.$field) <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
    @if ($type !== 'yesno' && in_array('Other', $options, true))
        @php($otherSelected = $type === 'multi'
            ? in_array('Other', is_array($value) ? $value : [], true)
            : ($value ?? '') === 'Other')
        @if ($otherSelected)
            <div class="mt-3 pl-3 border-l-2 border-gold-400">
                <label class="label !text-[11px] !mb-1">Other — please specify</label>
                <input class="input !py-1.5 !text-[12px]" wire:model="form.{{ $field }}_other" placeholder="Specify the “Other” answer">
            </div>
        @endif
    @endif
</div>
