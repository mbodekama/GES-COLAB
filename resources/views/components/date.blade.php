@props([
    'name',
    'label'    => null,
    'value'    => null,
    'required' => false,
])

@php
    $val      = old($name, $value);
    $hasError = $errors->has($name);
    $inputId  = $attributes->get('id', $name);
@endphp

<div>
    @if($label)
    <label for="{{ $inputId }}" class="form-label small fw-medium">
        {{ $label }}@if($required)<span class="text-danger ms-1">*</span>@endif
    </label>
    @endif

    <input
        type="date"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $val }}"
        {{ $attributes->except('id')->merge(['class' => 'form-control' . ($hasError ? ' is-invalid' : '')]) }}
        @if($required) required @endif
    >

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
