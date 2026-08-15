@use('App\Support\Format')

<x-layouts.app title="Dashboard — Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl" data-motion-page>
        @if ($isBirthday)
            <x-ui.birthday-fireworks />
        @endif

        <div class="mb-9 py-1" data-reveal="hero">
            <h1 class="font-display text-display-md text-ink">
                <span class="block">Selamat datang, {{ Str::before(auth()->user()->displayName(), ' ') }}.</span>
                @if ($isBirthday)
                    <span class="birthday-message">Selamat ulang tahun, semoga selalu diberikan kesehatan.</span>
                @endif
            </h1>
            <p class="mt-0.5 text-[13px] leading-[1.5] text-muted">
                Referral · {{ Format::date(now()) }}
            </p>
        </div>

        <div class="mb-xxl grid grid-cols-1 gap-lg sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.4fr]"
             data-motion-stagger>
            <x-ui.stat tone="cream" label="Pooling Aplikasi" :value="$carried" note="Sejak akun aktif" />
            <x-ui.stat tone="mint" label="Go Live" :value="$goLive" note="Actual Lending" />

            <div class="flex flex-col items-start gap-2.5 rounded-lg bg-signature-coral p-xl text-on-dark"
                 data-motion-card>
                <p class="text-title-md">Simulasi Kredit</p>
                <p class="text-[14px] leading-[1.6] text-white/80">
                    Hitung pencairan dan angsuran lima tenor untuk calon debitur.
                </p>
                <x-ui.button variant="secondary-on-dark" size="md" :href="route('simulation')" class="mt-auto">
                    Buka Simulasi
                </x-ui.button>
            </div>
        </div>

        <h2 class="mb-md text-title-lg text-ink" data-reveal>Aplikasi terbaru</h2>

        <div class="flex flex-col gap-sm md:hidden" data-motion-stagger data-motion-step="45">
            @forelse ($recent as $application)
                <a href="{{ route('applications.show', $application) }}"
                   class="block rounded-lg border border-hairline bg-canvas p-md transition-colors active:bg-surface-soft"
                   data-motion-card>
                    <div class="flex items-start justify-between gap-md">
                        <div>
                            <p class="font-mono text-[13px] leading-[1.4] text-ink">{{ $application->code }}</p>
                            <p class="mt-1 text-body-md font-medium text-ink">{{ $application->debtor_name }}</p>
                        </div>
                        <x-ui.chip :tone="$application->statusTone()">
                            {{ $application->statusLabel() }}
                        </x-ui.chip>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3 text-[13px] leading-[1.4]">
                        <div>
                            <p class="text-helper text-muted">Produk</p>
                            <p class="text-body">{{ $application->financing_product->label() }}</p>
                        </div>
                        <div>
                            <p class="text-helper text-muted">AO</p>
                            <p class="text-body">{{ $application->accountOfficer?->full_name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-helper text-muted">Dokumen</p>
                            <p class="font-medium text-ink">
                                {{ Format::ratio($application->documents_complete_count, $application->documents_count) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-helper text-muted">Tahapan</p>
                            <p class="font-medium text-ink">{{ Format::ratio($application->trackings_done_count, 11) }}</p>
                        </div>
                    </div>

                    <p class="mt-3 text-[13px] font-medium text-link">Lihat detail</p>
                </a>
            @empty
                <div class="rounded-lg border border-hairline bg-canvas px-5 py-xl text-center">
                    <p class="text-body-md text-ink">Belum ada aplikasi yang membawa nama Anda.</p>
                    <p class="mt-1 text-helper text-muted">
                        Mulai dari Simulasi Kredit, lalu serahkan hasilnya kepada Account Officer.
                    </p>
                </div>
            @endforelse
        </div>

        <x-ui.table min-width="760px" label="Aplikasi terbaru" class="hidden md:block" data-reveal="table">
            <x-slot:head>
                <x-ui.th>Kode Aplikasi</x-ui.th>
                <x-ui.th>Nama Debitur</x-ui.th>
                <x-ui.th>Produk</x-ui.th>
                <x-ui.th>Nama AO</x-ui.th>
                <x-ui.th>Dokumen</x-ui.th>
                <x-ui.th>Tahapan</x-ui.th>
                <x-ui.th>Status</x-ui.th>
            </x-slot:head>

            @forelse ($recent as $application)
                <tr data-motion-row>
                    <x-ui.td mono>
                        <a href="{{ route('applications.show', $application) }}" class="text-ink">
                            {{ $application->code }}
                        </a>
                    </x-ui.td>
                    <x-ui.td>{{ $application->debtor_name }}</x-ui.td>
                    <x-ui.td>{{ $application->financing_product->label() }}</x-ui.td>
                    <x-ui.td>{{ $application->accountOfficer?->full_name ?? '—' }}</x-ui.td>
                    <x-ui.td numeric>
                        {{ Format::ratio($application->documents_complete_count, $application->documents_count) }}
                    </x-ui.td>
                    <x-ui.td numeric>{{ Format::ratio($application->trackings_done_count, 11) }}</x-ui.td>
                    <x-ui.td>
                        <x-ui.chip :tone="$application->statusTone()">
                            {{ $application->statusLabel() }}
                        </x-ui.chip>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-xl text-center">
                        <p class="text-body-md text-ink">Belum ada aplikasi yang membawa nama Anda.</p>
                        <p class="mt-1 text-helper text-muted">
                            Mulai dari Simulasi Kredit, lalu serahkan hasilnya kepada Account Officer.
                        </p>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
