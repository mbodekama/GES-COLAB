@props([
    'initials' => '?',
    'size'     => 'md',   // xs | sm | md | lg | xl | 2xl
])

@php
$sizeClass = match($size) {
    'xs'  => 'avatar-xs',
    'sm'  => 'avatar-sm',
    'lg'  => 'avatar-lg',
    'xl'  => 'avatar-xl',
    '2xl' => 'avatar-2xl',
    default => 'avatar-md',
};
@endphp

<div {{ $attributes->merge(['class' => "avatar-initials avatar-blue {$sizeClass}"]) }}
     aria-hidden="true">
    {{ $initials }}
</div>
