@props(['size' => 'md'])

@php
    $box = $size === 'sm' ? 'h-[22px] w-[22px] rounded-[5px] text-[11px]' : 'h-7 w-7 rounded-sm text-[13px]';
    $label = $size === 'sm' ? 'text-body-md text-muted' : 'text-label-md font-normal text-ink';
@endphp

<span {{ $attributes->class('flex items-center gap-2.5') }}>
    <span class="{{ $box }} flex items-center justify-center bg-primary font-semibold text-on-primary">M</span>
    <span class="{{ $label }}">{{ $slot->isEmpty() ? 'Kebon Jeruk Multiguna' : $slot }}</span>
</span>
