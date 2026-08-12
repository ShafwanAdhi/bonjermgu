<x-layouts.app title="Lending - Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">
        <x-ui.page-header title="Lending"
                          meta="Pilih sudut pandang laporan lending." />

        <x-admin.module-navigation columns="two" :items="[
            ['label' => 'Per AO', 'description' => 'Lihat lending berdasarkan Account Officer.', 'route' => 'lending.ao', 'url' => route('lending.ao')],
            ['label' => 'Per Referral', 'description' => 'Lihat lending berdasarkan Referral.', 'route' => 'lending.referrals', 'url' => route('lending.referrals')],
        ]" />
    </div>
</x-layouts.app>
