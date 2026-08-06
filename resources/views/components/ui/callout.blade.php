{{-- Cream callout band used for rules the operator has to read before acting. --}}
@props(['tone' => 'cream'])

@php
    $tones = [
        'cream' => 'bg-signature-cream text-body',
        'warning' => 'bg-signature-cream text-signature-coral',
        'soft' => 'bg-surface-soft border border-hairline text-body',
    ];
@endphp

<div {{ $attributes->class(['rounded-md px-5 py-3.5 text-[13px] leading-[1.6]', $tones[$tone]]) }}>
    {{ $slot }}
</div>