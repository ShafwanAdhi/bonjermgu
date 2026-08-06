@props([
    'active' => false,
    'leftLabel' => 'Belum',
    'rightLabel' => 'Lengkap',
    'leftAction' => null,
    'rightAction' => null,
    'disabled' => false,
])

@php
    $active = (bool) $active;
    $canUseLeft = $leftAction && ! $disabled;
    $canUseRight = $rightAction && ! $disabled;
@endphp

<span {{ $attributes->class('relative inline-grid grid-cols-2 overflow-hidden rounded-md border border-hairline bg-surface-soft p-0.5 text-[12px] font-medium leading-[1.2] shadow-[0_1px_0_rgba(13,18,24,0.03)]') }}>
    <span
        aria-hidden="true"
        @class([
            'absolute inset-y-0.5 left-0.5 w-[calc(50%-2px)] rounded-[5px] shadow-sm transition-all duration-200 ease-out',
            'translate-x-[calc(100%+2px)] bg-primary' => $active,
            'translate-x-0 bg-border-strong' => ! $active,
        ])
    ></span>

    <button type="button"
            @if ($canUseLeft) wire:click="{{ $leftAction }}" @endif
            @disabled(! $canUseLeft)
            @class([
                'relative z-10 min-w-[76px] rounded-[5px] px-3 py-1.5 text-center transition-colors duration-200',
                'text-canvas' => ! $active,
                'text-muted hover:text-ink' => $active && $canUseLeft,
                'text-muted' => $active && ! $canUseLeft,
            ])>
        {{ $leftLabel }}
    </button>

    <button type="button"
            @if ($canUseRight) wire:click="{{ $rightAction }}" @endif
            @disabled(! $canUseRight)
            @class([
                'relative z-10 min-w-[76px] rounded-[5px] px-3 py-1.5 text-center transition-colors duration-200',
                'text-on-primary' => $active,
                'text-muted hover:text-ink' => ! $active && $canUseRight,
                'text-muted' => ! $active && ! $canUseRight,
            ])>
        {{ $rightLabel }}
    </button>
</span>
