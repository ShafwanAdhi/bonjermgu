@use('App\Support\Format')

<div class="band py-xl md:py-xxl" data-motion-page>

    <x-ui.page-header title="Aplikasi"
                      :meta="$this->isOfficer ? null : $this->applications->total().' aplikasi yang Anda bawa'"
                      data-reveal="hero">
        @if ($this->isOfficer)
            <x-slot:actions>
                <x-ui.button size="md" :href="route('applications.create')" wire:navigate>Buat Aplikasi</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if (session('application_success'))
        <x-ui.callout class="mb-md" data-reveal>{{ session('application_success') }}</x-ui.callout>
    @endif

    <div class="mb-lg grid gap-sm md:grid-cols-[minmax(220px,1fr)_190px_170px_170px] md:items-end" data-reveal>
        <div>
            <x-ui.input wire:model.live.debounce.400ms="search" type="search"
                        placeholder="Cari Kode Aplikasi atau Nama Debitur…" />
        </div>

        <x-ui.field label="Produk">
            <x-ui.select wire:model.live="product">
                <option value="">Semua</option>
                @foreach ($this->products as $product)
                    <option value="{{ $product->value }}">{{ $product->label() }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Status">
            <x-ui.select wire:model.live="goLive">
                <option value="">Semua</option>
                <option value="live">Go Live</option>
                <option value="pipeline">Pipe Line</option>
                <option value="canceled">Canceled</option>
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Bulan">
            <x-ui.input wire:model.live="month" type="month" />
        </x-ui.field>
    </div>

    <div class="md:hidden" wire:loading.class="opacity-60" wire:target="search,product,goLive,month,gotoPage,nextPage,previousPage">
        <div class="flex flex-col gap-sm" data-motion-stagger>
            @forelse ($this->applications as $application)
                <a href="{{ route('applications.show', $application) }}" wire:navigate
                   class="block rounded-lg border border-hairline bg-canvas p-md transition-colors active:bg-surface-soft">
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
                            <p class="text-helper text-muted">{{ $this->isOfficer ? 'Referral' : 'AO' }}</p>
                            <p class="text-body">
                                {{ $this->isOfficer
                                    ? ($application->referral?->full_name ?? '—')
                                    : ($application->accountOfficer?->full_name ?? '—') }}
                            </p>
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
                        <div>
                            <p class="text-helper text-muted">Dibuat</p>
                            <p class="text-body">{{ Format::date($application->created_at) }}</p>
                        </div>
                    </div>

                    <p class="mt-3 text-[13px] font-medium text-link">Lihat detail</p>
                </a>
            @empty
                <div class="rounded-lg border border-hairline bg-canvas px-5 py-xl text-center" data-reveal>
                    @if ($search !== '' || $product !== '' || $goLive !== '' || $month !== '')
                        <p class="text-body-md text-ink">Tidak ada aplikasi yang cocok dengan penyaring ini.</p>
                        <button type="button" wire:click="resetFilters"
                                class="mt-3 inline-flex min-h-11 items-center rounded-sm px-3 text-[13px] font-medium text-link">
                            Bersihkan penyaring
                        </button>
                    @elseif ($this->isOfficer)
                        <p class="text-body-md text-ink">Anda belum menangani aplikasi apa pun.</p>
                        <p class="mt-1 text-helper text-muted">
                            Buat Credit Application dari hasil simulasi yang diserahkan Referral.
                        </p>
                    @else
                        <p class="text-body-md text-ink">Belum ada aplikasi yang membawa nama Anda.</p>
                        <p class="mt-1 text-helper text-muted">
                            Aplikasi muncul di sini setelah Account Officer membuatnya dari simulasi Anda.
                        </p>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <div class="hidden md:block" wire:loading.class="opacity-60" wire:target="search,product,goLive,month,gotoPage,nextPage,previousPage" data-reveal>
        <x-ui.table min-width="980px" label="Daftar aplikasi">
            <x-slot:head>
                <x-ui.th>Kode Aplikasi</x-ui.th>
                <x-ui.th>Nama Debitur</x-ui.th>
                <x-ui.th>Produk</x-ui.th>
                <x-ui.th>{{ $this->isOfficer ? 'Nama Referral' : 'Nama AO' }}</x-ui.th>
                <x-ui.th>Dokumen</x-ui.th>
                <x-ui.th>Tahapan</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th>Dibuat</x-ui.th>
                <x-ui.th align="right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($this->applications as $application)
                <tr wire:key="application-{{ $application->id }}" data-motion-row>
                    <x-ui.td mono>
                        <a href="{{ route('applications.show', $application) }}" wire:navigate
                           class="text-ink">{{ $application->code }}</a>
                    </x-ui.td>
                    <x-ui.td>{{ $application->debtor_name }}</x-ui.td>
                    <x-ui.td>{{ $application->financing_product->label() }}</x-ui.td>
                    <x-ui.td>
                        {{ $this->isOfficer
                            ? ($application->referral?->full_name ?? '—')
                            : ($application->accountOfficer?->full_name ?? '—') }}
                    </x-ui.td>
                    <x-ui.td numeric>
                        {{ Format::ratio($application->documents_complete_count, $application->documents_count) }}
                    </x-ui.td>
                    <x-ui.td numeric>{{ Format::ratio($application->trackings_done_count, 11) }}</x-ui.td>
                    <x-ui.td>
                        <x-ui.chip :tone="$application->statusTone()">
                            {{ $application->statusLabel() }}
                        </x-ui.chip>
                    </x-ui.td>
                    <x-ui.td class="text-muted">{{ Format::date($application->created_at) }}</x-ui.td>
                    <x-ui.td align="right">
                        <a href="{{ route('applications.show', $application) }}" wire:navigate
                           class="text-[13px] font-medium text-link">
                            Detail
                        </a>
                    </x-ui.td>
                </tr>
            @empty
                {{-- The empty state says why and what to do next, not just
                     "tidak ada data" — pages.md §18. --}}
                <tr>
                    <td colspan="9" class="px-5 py-xl text-center">
                        @if ($search !== '' || $product !== '' || $goLive !== '' || $month !== '')
                            <p class="text-body-md text-ink">Tidak ada aplikasi yang cocok dengan penyaring ini.</p>
                            <button type="button" wire:click="resetFilters"
                                    class="mt-2 text-[13px] font-medium text-link">Bersihkan penyaring</button>
                        @elseif ($this->isOfficer)
                            <p class="text-body-md text-ink">Anda belum menangani aplikasi apa pun.</p>
                            <p class="mt-1 text-helper text-muted">
                                Buat Credit Application dari hasil simulasi yang diserahkan Referral.
                            </p>
                        @else
                            <p class="text-body-md text-ink">Belum ada aplikasi yang membawa nama Anda.</p>
                            <p class="mt-1 text-helper text-muted">
                                Aplikasi muncul di sini setelah Account Officer membuatnya dari simulasi Anda.
                            </p>
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <div class="mt-md">{{ $this->applications->links() }}</div>

    
</div>
