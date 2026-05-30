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
        {{ $attributes->except('id')->merge(['class' => 'form-control fp-date' . ($hasError ? ' is-invalid' : '')]) }}
        @if($required) required @endif
    >

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@once
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* ── Flatpickr — thème GES-COLAB ──────────────────────── */
    .flatpickr-calendar {
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        border: 1px solid #e8ecf0;
        font-family: 'Segoe UI', system-ui, sans-serif;
        font-size: 14px;
    }
    .flatpickr-months .flatpickr-month {
        background: #185FA5;
        border-radius: 11px 11px 0 0;
    }
    .flatpickr-months .flatpickr-prev-month svg,
    .flatpickr-months .flatpickr-next-month svg { fill: #fff; }
    .flatpickr-months .flatpickr-prev-month:hover svg,
    .flatpickr-months .flatpickr-next-month:hover svg { fill: #E6F1FB; }
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year { color: #fff; }
    .numInputWrapper span.arrowUp:after  { border-bottom-color: #fff; }
    .numInputWrapper span.arrowDown:after { border-top-color: #fff; }
    .flatpickr-weekday { color: #6c757d; font-weight: 600; font-size: 12px; }
    .flatpickr-day:hover  { background: #E6F1FB; border-color: #E6F1FB; color: #185FA5; }
    .flatpickr-day.today  { border-color: #185FA5; color: #185FA5; font-weight: 600; }
    .flatpickr-day.today:hover { background: #E6F1FB; }
    .flatpickr-day.selected,
    .flatpickr-day.selected:hover {
        background: #185FA5;
        border-color: #185FA5;
        color: #fff;
    }
    /* Alt input visible — apparence form-control normale */
    input.flatpickr-input.form-control[readonly] {
        background-color: #fff;
        cursor: pointer;
    }
    input.flatpickr-input.form-control[readonly]:focus {
        border-color: #185FA5;
        box-shadow: 0 0 0 .2rem rgba(24,95,165,.2);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input.fp-date').forEach(function (el) {
        var opts = {
            locale: 'fr',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j F Y',
            allowInput: true,
            disableMobile: true,
        };
        if (el.getAttribute('min')) opts.minDate = el.getAttribute('min');
        if (el.getAttribute('max')) opts.maxDate = el.getAttribute('max');

        var fp = flatpickr(el, opts);

        // Relier le label au champ visible (altInput)
        if (fp.altInput) {
            fp.altInput.id = el.id;
            el.removeAttribute('id');
        }
    });
});
</script>
@endpush
@endonce
