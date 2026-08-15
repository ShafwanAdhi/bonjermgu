<x-layouts.public title="Sesi Berakhir - Kebon Jeruk Multiguna">
    <x-ui.error-state code="419"
                       title="Sesi Anda Telah Berakhir"
                       message="Sesi berakhir karena tidak ada aktivitas. Masuk kembali untuk melanjutkan — isian formulir yang belum disimpan mungkin perlu diulang."
                       cta-label="Masuk Kembali"
                       :cta-href="route('login')" />
</x-layouts.public>
