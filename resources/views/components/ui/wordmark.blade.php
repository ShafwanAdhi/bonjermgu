@props(['size' => 'md'])

@php
    $mark = $size === 'sm'
        ? 'h-6 w-6'
        : 'h-7 w-7 sm:h-8 sm:w-8';
    $label = $size === 'sm'
        ? 'text-body-md text-muted'
        : 'whitespace-nowrap text-[11px] font-medium leading-none text-ink sm:text-label-md sm:font-normal';
@endphp

<span {{ $attributes->class('flex min-w-0 items-center gap-2 sm:gap-2.5') }}>
    <img
        src="{{ asset('images/brand/bonjemgu-logo.svg') }}"
        alt=""
        width="{{ $size === 'sm' ? 24 : 32 }}"
        height="{{ $size === 'sm' ? 24 : 32 }}"
        class="{{ $mark }} aspect-square shrink-0 object-contain"
        aria-hidden="true"
    >
    <span class="{{ $label }} min-w-0 truncate">{{ $slot->isEmpty() ? 'Kebon Jeruk Multiguna' : $slot }}</span>
</span>
