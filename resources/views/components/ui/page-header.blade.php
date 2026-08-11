@props([
    'title' => '',
    'meta' => null,
])

<div {{ $attributes->class('mb-xl flex flex-col items-start gap-1.5 sm:flex-row sm:items-center sm:gap-md') }}>
    <h1 class="m-0 font-display text-display-md text-ink">{{ $title }}</h1>

    @if ($meta)
        <span class="text-body-md text-muted">{{ $meta }}</span>
    @endif

    @isset($actions)
        <div class="flex w-full flex-wrap items-center gap-sm sm:ml-auto sm:w-auto">{{ $actions }}</div>
    @endisset
</div>
