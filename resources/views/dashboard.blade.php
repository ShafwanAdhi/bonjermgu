{{--
    Placeholder. The per-role dashboards are specified in docs/pages.md
    section 6 and mocked in Referral.dc.html, AO.dc.html, and Admin.dc.html.
    Only the public pages have been built so far.
--}}
<x-layouts.app title="Dashboard — Kebon Jeruk Multiguna">
    <div class="band py-xxl">
        <h1 class="mb-2 font-display text-display-md text-ink">Dashboard</h1>
        <p class="mb-xl text-[14px] leading-[1.6] text-muted">
            Masuk sebagai {{ auth()->user()->role->label() }}.
        </p>

        <div class="rounded-md border border-hairline bg-canvas p-lg">
            <p class="text-[14px] leading-[1.6] text-body">
                Halaman ini belum dibangun. Isinya mengikuti
                <span class="font-medium">docs/pages.md</span> bagian 6.
            </p>
        </div>
    </div>
</x-layouts.app>
