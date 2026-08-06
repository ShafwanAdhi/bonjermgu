@props([
    'title' => '',
    'meta' => null,
])

<div {{ $attributes->class('mb-xl flex flex-wrap items-center gap-md') }}>
    <h1 class="m-0 font-display text-display-md text-ink">{{ $title }}</h1>

    @if ($meta)
        <span class="text-body-md text-muted">{{ $meta }}</span>
    @endif

    @isset($actions)
        <div class="ml-auto flex flex-wrap items-center gap-sm">{{ $actions }}</div>
    @endisset
</div>