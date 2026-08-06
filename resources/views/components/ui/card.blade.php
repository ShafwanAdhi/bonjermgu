@props([
    'title' => null,
    'note' => null,
    'meta' => null,
])

<div {{ $attributes->class('rounded-lg border border-hairline bg-canvas p-lg md:p-xl') }}>
    @if ($title)
        <div class="mb-5 flex flex-wrap items-baseline gap-sm">
            <p class="text-title-sm text-ink">{{ $title }}</p>
            @if ($meta)
                <span class="text-[13px] leading-[1.4] text-muted">{{ $meta }}</span>
            @endif
            @isset($actions)
                <div class="ml-auto flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}

    @if ($note)
        <p class="mt-sm text-helper text-border-strong">{{ $note }}</p>
    @endif
</div>