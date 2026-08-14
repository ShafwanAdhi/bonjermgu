@props([
    'isActive' => false,
    'inactiveLabel' => 'Belum',
    'activeLabel' => 'Selesai',
    'label' => 'Pilih status',
])

<span
    role="group"
    aria-label="{{ $label }}"
    {{ $attributes->class('status-toggle inline-flex shrink-0 rounded-lg border border-hairline bg-surface-soft p-0.5') }}
>
    <button type="button"
            aria-pressed="{{ $isActive ? 'false' : 'true' }}"
            {{ $inactive->attributes->class([
                'min-h-9 rounded-md px-3 text-[12px] font-medium leading-none transition-colors',
                'bg-canvas text-muted hover:text-ink' => $isActive,
                'bg-primary text-on-primary shadow-[0_1px_2px_rgba(13,18,24,0.12)]' => ! $isActive,
            ]) }}>
        {{ $inactiveLabel }}
    </button>
    <button type="button"
            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
            {{ $active->attributes->class([
                'min-h-9 rounded-md px-3 text-[12px] font-medium leading-none transition-colors',
                'bg-primary text-on-primary shadow-[0_1px_2px_rgba(13,18,24,0.12)]' => $isActive,
                'bg-canvas text-muted hover:text-ink' => ! $isActive,
            ]) }}>
        {{ $activeLabel }}
    </button>
</span>
