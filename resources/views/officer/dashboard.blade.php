@use('App\Support\Format')

<x-layouts.app title="Dashboard — Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">

        <div class="mb-9 flex flex-wrap items-baseline gap-md">
            <h1 class="font-display text-display-md text-ink">
                Selamat datang, {{ Str::before(auth()->user()->displayName(), ' ') }}
            </h1>
            <span class="text-body-md text-muted">{{ Format::date(now()) }}</span>
        </div>

        <div class="mb-xxl grid grid-cols-1 gap-lg sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.4fr]">
            <x-ui.stat tone="cream" label="Aplikasi ditangani" :value="$handled"
                       :note="'Dari '.$referralCount.' mitra Referral'" />
            <x-ui.stat tone="peach" label="Belum Go Live" :value="$pipeline" note="Pipe Line berjalan" />

            <div class="flex flex-col items-start gap-2.5 rounded-lg bg-surface-dark p-xl text-on-dark">
                <p class="text-title-md">Buat Credit Application</p>
                <p class="text-[14px] leading-[1.6] text-white/80">
                    Input manual dari hasil simulasi yang diserahkan Referral.
                </p>
                <x-ui.button variant="secondary-on-dark" size="md" :href="route('applications.create')" class="mt-auto">
                    Buat Aplikasi
                </x-ui.button>
            </div>
        </div>

        <h2 class="mb-md text-title-lg text-ink">Tahapan tertunda</h2>

        <x-ui.table min-width="760px">
            <x-slot:head>
                <x-ui.th>Kode Aplikasi</x-ui.th>
                <x-ui.th>Nama Debitur</x-ui.th>
                <x-ui.th>Nama Referral</x-ui.th>
                <x-ui.th>Dokumen</x-ui.th>
                <x-ui.th>Tahapan</x-ui.th>
            </x-slot:head>

            @forelse ($pending as $application)
                <tr>
                    <x-ui.td mono>
                        <a href="{{ route('applications.show', $application) }}" class="text-ink">
                            {{ $application->code }}
                        </a>
                    </x-ui.td>
                    <x-ui.td>{{ $application->debtor_name }}</x-ui.td>
                    <x-ui.td>{{ $application->referral?->full_name ?? '—' }}</x-ui.td>
                    <x-ui.td numeric>
                        {{ Format::ratio($application->documents_complete_count, $application->documents_count) }}
                    </x-ui.td>
                    <x-ui.td numeric>{{ Format::ratio($application->trackings_done_count, 11) }}</x-ui.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-xl text-center">
                        @if ($handled > 0)
                            <p class="text-body-md text-ink">Tidak ada aplikasi yang tertunda.</p>
                            <p class="mt-1 text-helper text-muted">Seluruh aplikasi Anda telah Go Live.</p>
                        @else
                            <p class="text-body-md text-ink">Anda belum menangani aplikasi apa pun.</p>
                            <p class="mt-1 text-helper text-muted">
                                Buat Credit Application dari hasil simulasi yang diserahkan Referral.
                            </p>
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
