<x-layouts.app title="Konfigurasi - Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">
        <x-ui.page-header title="Konfigurasi"
                          meta="Pilih modul konfigurasi yang ingin dikelola." />

        <x-admin.module-navigation columns="five" :items="[
            ['label' => 'Product dan Upping', 'description' => 'Rate, tenor, dan upping produk.', 'route' => 'configuration.products', 'url' => route('configuration.products')],
            ['label' => 'Asuransi', 'description' => 'Casco, TLO, dan wilayah asuransi.', 'route' => 'configuration.insurance', 'url' => route('configuration.insurance')],
            ['label' => 'Biaya dan Down Payment', 'description' => 'Fiducia, admin, provisi, dan DP.', 'route' => 'configuration.fees', 'url' => route('configuration.fees')],
            ['label' => 'Nilai Default Simulasi', 'description' => 'Default input dan parameter simulasi.', 'route' => 'configuration.defaults', 'url' => route('configuration.defaults')],
            ['label' => 'Uji Konfigurasi', 'description' => 'Cek hasil engine sebelum dipakai.', 'route' => 'configuration.simulation', 'url' => route('configuration.simulation')],
        ]" />
    </div>
</x-layouts.app>
