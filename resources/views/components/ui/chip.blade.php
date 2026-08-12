{{-- Status pill. Green outline for a reached state, hairline for a pending one. --}}
@props(['tone' => 'neutral'])

@php
    $tones = [
        'success' => 'border-success-border text-success',
        'neutral' => 'border-hairline text-muted',
        'danger' => 'border-red-200 text-red-700',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex whitespace-nowrap rounded-sm border px-2.5 py-1 text-[12px] font-medium leading-[1.2]',
    $tones[$tone],
]) }}>{{ $slot }}</span>
