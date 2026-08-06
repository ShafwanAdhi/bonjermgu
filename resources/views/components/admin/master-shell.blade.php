{{-- Shared shell for the three Master Data tabs — docs/pages.md section 13. --}}
@props(['title' => 'Master Data', 'lastChange' => null])

<div class="band py-xl md:py-xxl">
        <div class="mb-5 flex flex-wrap items-baseline gap-md">
            <h1 class="m-0 font-display text-display-md text-ink">Master Data</h1>
            <span class="text-[13px] leading-[1.4] text-muted">
                @if ($lastChange)
                    Terakhir diubah {{ $lastChange->created_at->locale('id')->translatedFormat('d F Y, H.i') }}
                    · oleh {{ $lastChange->actor_name }}
                @else
                    Belum ada perubahan Admin yang tercatat
                @endif
            </span>
        </div>

        <x-ui.tabs :items="[
            ['label' => 'Master Kendaraan', 'url' => route('master.vehicles'), 'active' => request()->routeIs('master.vehicles')],
            ['label' => 'Master Referral', 'url' => route('master.referral'), 'active' => request()->routeIs('master.referral')],
            ['label' => 'Domisili dan Kelompok Usia', 'url' => route('master.lookups'), 'active' => request()->routeIs('master.lookups')],
        ]" />

        {{ $slot }}
</div>
