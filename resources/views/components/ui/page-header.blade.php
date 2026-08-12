@props([
    'title' => '',
    'meta' => null,
])

<div {{ $attributes->class('mb-xl flex flex-col items-start gap-md sm:flex-row sm:items-start sm:justify-between') }}>
    <div class="min-w-0">
        <h1 class="m-0 font-display text-display-md text-ink">{{ $title }}</h1>

        @if ($meta)
            <span class="mt-1.5 block text-body-md text-muted">{{ $meta }}</span>
        @endif
    </div>

    @isset($actions)
        <div class="flex w-full flex-wrap items-center gap-sm sm:w-auto sm:justify-end">{{ $actions }}</div>
    @endisset
</div>
