{{--
    Header and tab rail for the two Akun tabs — docs/pages.md section 14.

    The page shell itself comes from the Livewire #[Layout] attribute, so this
    component must not wrap x-layouts.app or the layout renders twice.
--}}
@props(['title' => 'Akun'])

<div class="band py-xl md:py-xxl">
    <x-ui.page-header title="Akun" class="mb-5" />

    <x-ui.tabs :items="[
        ['label' => 'Akun Referral', 'url' => route('accounts.referrals'), 'active' => request()->routeIs('accounts.referrals')],
        ['label' => 'Akun AO', 'url' => route('accounts.officers'), 'active' => request()->routeIs('accounts.officers')],
    ]" />

    {{ $slot }}
</div>
