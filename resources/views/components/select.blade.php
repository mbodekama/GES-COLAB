@props([
    'name',
    'label'       => null,
    'options'     => [],
    'value'       => null,
    'optionValue' => null,  // champ "valeur" sur les items de collection
    'optionLabel' => null,  // champ "libellé" sur les items de collection
    'placeholder' => null,  // option vide en tête
    'required'    => false,
])

@php
    $selected = old($name, $value);
    $hasError = $errors->has($name);
    $inputId  = $attributes->get('id', $name);
@endphp

<div>
    @if($label)
    <label for="{{ $inputId }}" class="form-label small fw-medium">
        {{ $label }}@if($required)<span class="text-danger ms-1">*</span>@endif
    </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $inputId }}"
        {{ $attributes->except('id')->merge(['class' => 'form-select' . ($hasError ? ' is-invalid' : '')]) }}
        @if($required) required @endif
    >
        @if($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $optKey => $optItem)
            @php
                if ($optionValue !== null && $optionLabel !== null) {
                    // Collection d'objets ou tableau d'arrays
                    $val = is_object($optItem) ? $optItem->{$optionValue} : ($optItem[$optionValue] ?? $optKey);
                    $lbl = is_object($optItem) ? $optItem->{$optionLabel} : ($optItem[$optionLabel] ?? $optItem);
                } else {
                    // Tableau associatif simple [valeur => libellé]
                    $val = $optKey;
                    $lbl = $optItem;
                }
            @endphp
            <option value="{{ $val }}" @selected((string) $selected === (string) $val)>
                {{ $lbl }}
            </option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
