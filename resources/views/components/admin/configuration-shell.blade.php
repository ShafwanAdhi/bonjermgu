{{-- Shared shell for the four configuration tabs — docs/pages.md section 12. --}}
@props(['title' => 'Konfigurasi', 'lastChange' => null])

<div class="band py-xl md:py-xxl">
        <div class="mb-1.5 flex flex-wrap items-center gap-md">
            <h1 class="m-0 font-display text-display-md text-ink">Konfigurasi</h1>
            <span class="text-[13px] leading-[1.4] text-muted">
                @if ($lastChange)
                    Terakhir diubah {{ $lastChange->created_at->locale('id')->translatedFormat('d F Y, H.i') }}
                    · oleh {{ $lastChange->actor_name }}
                @else
                    Belum ada perubahan Admin yang tercatat
                @endif
            </span>
        </div>

        <p class="mb-5 text-[13px] leading-[1.5] text-border-strong">
            Perubahan berlaku pada simulasi berikutnya — tidak mengubah hasil simulasi yang telah
            dicetak. Nilai persentase ditampilkan sebagai persen dan disimpan sebagai pecahan.
        </p>

        <x-ui.tabs :items="[
            ['label' => 'Product dan Upping', 'url' => route('configuration.products'), 'active' => request()->routeIs('configuration.products')],
            ['label' => 'Asuransi', 'url' => route('configuration.insurance'), 'active' => request()->routeIs('configuration.insurance')],
            ['label' => 'Biaya dan Down Payment', 'url' => route('configuration.fees'), 'active' => request()->routeIs('configuration.fees')],
            ['label' => 'Nilai Default Simulasi', 'url' => route('configuration.defaults'), 'active' => request()->routeIs('configuration.defaults')],
            ['label' => 'Uji Konfigurasi', 'url' => route('configuration.simulation'), 'active' => request()->routeIs('configuration.simulation')],
        ]" />

        {{ $slot }}
</div>
