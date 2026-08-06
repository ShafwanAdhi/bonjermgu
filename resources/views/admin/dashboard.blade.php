@use('App\Support\Format')

<x-layouts.app title="Ringkasan — Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">

        <div class="mb-9 flex flex-wrap items-baseline gap-md">
            <h1 class="font-display text-display-md text-ink">Ringkasan</h1>
            <span class="text-body-md text-muted">{{ Format::date(now()) }} · seluruh cabang</span>
        </div>

        <div class="grid grid-cols-1 gap-lg lg:grid-cols-[1.2fr_1.2fr_1fr]">
            <div class="rounded-lg bg-signature-forest p-xl text-on-dark">
                <p class="mb-md text-caption text-white/75">Actual Lending</p>
                <p class="font-display text-[44px] leading-[1.1]">{{ $totals->actualUnits }} unit</p>
                <p class="mt-2.5 text-[16px] leading-[1.5] tabular-nums">
                    {{ Format::rupiah($totals->actualAmount) }}
                </p>
                <p class="mt-1.5 text-helper text-white/65">Amount Finance telah Go Live · seluruh periode</p>
            </div>

            <div class="rounded-lg bg-signature-cream p-xl text-ink">
                <p class="mb-md text-caption text-muted">Pipe Line</p>
                <p class="font-display text-[44px] leading-[1.1]">{{ $totals->pipelineUnits }} unit</p>
                <p class="mt-2.5 text-[16px] leading-[1.5] tabular-nums">
                    {{ Format::rupiah($totals->pipelineAmount) }}
                </p>
                <p class="mt-1.5 text-helper text-muted">Belum Go Live · posisi saat ini</p>
            </div>

            <div class="flex flex-col gap-lg">
                <div class="flex-1 rounded-md border border-hairline bg-surface-soft px-lg py-5">
                    <p class="mb-2 text-[13px] font-medium leading-[1.35] text-muted">Akun Referral</p>
                    <p class="font-display text-[32px] leading-[1.1] text-ink">{{ $referralAccounts }}</p>
                </div>
                <div class="flex-1 rounded-md border border-hairline bg-surface-soft px-lg py-5">
                    <p class="mb-2 text-[13px] font-medium leading-[1.35] text-muted">Akun Account Officer</p>
                    <p class="font-display text-[32px] leading-[1.1] text-ink">{{ $officerAccounts }}</p>
                    @if ($inactiveAccounts > 0)
                        <p class="mt-1 text-helper text-muted">{{ $inactiveAccounts }} akun nonaktif</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-9 flex flex-wrap gap-sm">
            <x-ui.button size="md" :href="route('configuration.products')">Buka Konfigurasi</x-ui.button>
            <x-ui.button variant="secondary" size="md" :href="route('lending')">Buka Lending</x-ui.button>
        </div>

        {{-- Admin has no route into application data. The absence is the design. --}}
        <x-ui.callout class="mt-lg inline-flex">
            Angka di atas berasal dari query agregasi Lending — satu-satunya pengecualian akses
            Admin terhadap data application.
        </x-ui.callout>
    </div>
</x-layouts.app>
