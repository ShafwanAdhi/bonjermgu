@props([
    'variant' => 'primary',
    'size' => 'lg',
    'href' => null,
])

@php
    // Secondary carries a 1px border, so its padding is 1px lighter than
    // primary's. That keeps the two the same height when sat side by side.
    $sizes = [
        'sm' => ['primary' => 'px-3.5 py-2', 'secondary' => 'px-3.5 py-[7px]'],
        'md' => ['primary' => 'px-5 py-3', 'secondary' => 'px-5 py-[11px]'],
        'lg' => ['primary' => 'px-7 py-[15px]', 'secondary' => 'px-7 py-[14px]'],
    ];

    $variants = [
        'primary' => 'bg-primary text-on-primary active:bg-primary-active',
        'secondary' => 'border border-hairline bg-canvas text-ink',
        // Stays white over coral / forest / dark surfaces — the system never
        // inverts to a translucent on-dark style.
        'secondary-on-dark' => 'bg-canvas text-ink',
    ];

    $padKey = $variant === 'primary' ? 'primary' : 'secondary';
    $classes = 'inline-flex items-center justify-center rounded-lg text-button transition-colors '
        .$sizes[$size][$padKey].' '.$variants[$variant];
@endphp

@if ($href)
    <a href="{{ $href }}" data-motion-action {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button data-motion-action {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
