<x-layouts.app title="Akun - Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">
        <x-ui.page-header title="Akun"
                          meta="Pilih kelompok akun yang ingin dikelola." />

        <x-admin.module-navigation :items="[
            ['label' => 'Akun Anda', 'description' => 'Ubah profil dan kata sandi admin.', 'route' => 'accounts.profile', 'url' => route('accounts.profile')],
            ['label' => 'Akun Referral', 'description' => 'Kelola akun mitra referral.', 'route' => 'accounts.referrals', 'url' => route('accounts.referrals')],
            ['label' => 'Akun AO', 'description' => 'Buat dan ubah akun account officer.', 'route' => 'accounts.officers', 'url' => route('accounts.officers')],
        ]" />
    </div>
</x-layouts.app>
