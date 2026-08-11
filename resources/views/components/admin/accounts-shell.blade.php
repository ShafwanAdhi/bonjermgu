{{--
    Header for Akun subpages. The page shell itself comes from the Livewire
    #[Layout] attribute, so this component must not wrap x-layouts.app.
--}}
@props([
    'title' => 'Akun',
    'backHref' => null,
])

<div class="band py-xl md:py-xxl">
    <x-ui.back-link :href="$backHref ?? route('accounts.index')" class="mb-md" />

    <div class="mb-5">
        <p class="mb-1 text-eyebrow uppercase text-muted">Akun</p>
        <h1 class="m-0 font-display text-display-md text-ink">{{ $title }}</h1>
    </div>

    {{ $slot }}
</div>
