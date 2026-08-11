{{-- Shared shell for Master Data subpages. --}}
@props(['title' => 'Master Data', 'lastChange' => null])

<div class="band py-xl md:py-xxl">
    <x-ui.back-link :href="route('master.index')" class="mb-md" />

    <div class="mb-5 flex flex-wrap items-end gap-md">
        <div>
            <p class="mb-1 text-eyebrow uppercase text-muted">Master Data</p>
            <h1 class="m-0 font-display text-display-md text-ink">{{ $title }}</h1>
        </div>
        <span class="text-[13px] leading-[1.4] text-muted">
            @if ($lastChange)
                Terakhir diubah {{ $lastChange->created_at->locale('id')->translatedFormat('d F Y, H.i') }}
                - oleh {{ $lastChange->actor_name }}
            @else
                Belum ada perubahan Admin yang tercatat
            @endif
        </span>
    </div>

    {{ $slot }}
</div>
