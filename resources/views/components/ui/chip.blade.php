{{-- Status pill. Green outline for a reached state, hairline for a pending one. --}}
@props(['tone' => 'neutral'])

@php
    $tones = [
        'success' => 'border-success-border text-success',
        'neutral' => 'border-hairline text-muted',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex whitespace-nowrap rounded-sm border px-2.5 py-1 text-[12px] font-medium leading-[1.2]',
    $tones[$tone],
]) }}>{{ $slot }}</span>