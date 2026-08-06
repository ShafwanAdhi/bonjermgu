@props([
    'label' => '',
    'value' => '',
    'note' => null,
    'tone' => 'cream',
])

@php
    $tones = [
        'cream' => 'bg-signature-cream text-ink',
        'peach' => 'bg-signature-peach text-ink',
        'mint' => 'bg-signature-mint text-ink',
        'forest' => 'bg-signature-forest text-on-dark',
        'dark' => 'bg-surface-dark text-on-dark',
        'soft' => 'bg-surface-soft border border-hairline text-ink',
    ];

    $onDark = in_array($tone, ['forest', 'dark'], true);
@endphp

<div {{ $attributes->class(['rounded-md p-lg', $tones[$tone]]) }}>
    <p class="mb-md text-caption {{ $onDark ? 'text-white/75' : 'text-muted' }}">{{ $label }}</p>
    <p class="font-display text-[48px] leading-[1.1]">{{ $value }}</p>
    @if ($note)
        <p class="mt-2.5 text-[13px] leading-[1.5] {{ $onDark ? 'text-white/65' : 'text-muted' }}">{{ $note }}</p>
    @endif
</div>