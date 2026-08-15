{{--
    Shared body for the error pages in resources/views/errors/. Each page
    supplies its own code, title, and message; the layout and CTA are the
    same everywhere so the four pages read as one family, not four accidents.
--}}
@props([
    'code' => null,
    'title',
    'message',
    'ctaLabel' => null,
    'ctaHref' => null,
])

@php
    // No auth()->user() dependency — a 404 can be hit while logged out.
    $ctaHref ??= auth()->check() ? route('dashboard') : route('landing');
    $ctaLabel ??= auth()->check() ? 'Kembali ke Dashboard' : 'Kembali ke Beranda';
@endphp

<div class="band flex min-h-[60vh] flex-col items-center justify-center py-xxl text-center">
    @if ($code)
        <p class="mb-sm text-eyebrow uppercase text-muted">Error {{ $code }}</p>
    @endif

    <h1 class="mb-sm font-display text-display-md text-ink">{{ $title }}</h1>

    <p class="max-w-[440px] text-body-md text-body">{{ $message }}</p>

    <div class="mt-xl">
        <x-ui.button size="md" :href="$ctaHref">{{ $ctaLabel }}</x-ui.button>
    </div>
</div>
