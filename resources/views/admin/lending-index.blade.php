<x-layouts.app title="Lending - Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">
        <x-ui.page-header title="Lending"
                          meta="Pilih sudut pandang laporan lending." />

        <x-admin.module-navigation columns="two" :items="[
            ['label' => 'Per AO', 'description' => 'Lihat kontribusi referral per officer.', 'route' => 'lending.ao', 'url' => route('lending.ao')],
            ['label' => 'Per Referral', 'description' => 'Lihat officer yang menangani tiap referral.', 'route' => 'lending.referrals', 'url' => route('lending.referrals')],
        ]" />
    </div>
</x-layouts.app>
