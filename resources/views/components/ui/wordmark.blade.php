@props(['size' => 'md'])

@php
    $box = $size === 'sm'
        ? 'h-[22px] w-[22px] rounded-[5px] text-[11px]'
        : 'h-6 w-6 rounded-full text-[11px] sm:h-7 sm:w-7 sm:text-[13px]';
    $label = $size === 'sm'
        ? 'text-body-md text-muted'
        : 'whitespace-nowrap text-[11px] font-medium leading-none text-ink sm:text-label-md sm:font-normal';
@endphp

<span {{ $attributes->class('flex min-w-0 items-center gap-2 sm:gap-2.5') }}>
    <span class="{{ $box }} flex aspect-square shrink-0 items-center justify-center bg-primary font-semibold leading-none text-on-primary">M</span>
    <span class="{{ $label }} min-w-0 truncate">{{ $slot->isEmpty() ? 'Kebon Jeruk Multiguna' : $slot }}</span>
</span>
