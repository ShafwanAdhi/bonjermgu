<x-layouts.app title="Master Data - Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">
        <x-ui.page-header title="Master Data"
                          meta="Pilih modul master data yang ingin diperbarui." />

        <x-admin.module-navigation :items="[
            ['label' => 'Master Kendaraan', 'description' => 'Brand, type, model, dan harga tahun.', 'route' => 'master.vehicles', 'url' => route('master.vehicles')],
            ['label' => 'Master Referral', 'description' => 'Kategori, sub-kategori, dan instansi.', 'route' => 'master.referral', 'url' => route('master.referral')],
            ['label' => 'Domisili dan Kelompok Usia', 'description' => 'Wilayah, usia, tujuan, dan aturan dasar.', 'route' => 'master.lookups', 'url' => route('master.lookups')],
            ['label' => 'Token SPRINT', 'description' => 'Ejaan Product ID dan Product Offering pada View Sprint.', 'route' => 'master.sprint-tokens', 'url' => route('master.sprint-tokens')],
        ]" />
    </div>
</x-layouts.app>
