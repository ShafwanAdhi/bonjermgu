@use('App\Support\Format')

<div class="band py-xl md:py-xxl">

    <x-ui.page-header title="Aplikasi"
                      :meta="$this->isOfficer ? null : $this->applications->total().' aplikasi yang Anda bawa'">
        @if ($this->isOfficer)
            <x-slot:actions>
                <x-ui.button size="md" :href="route('applications.create')" wire:navigate>Buat Aplikasi</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    @if (session('application_success'))
        <x-ui.callout class="mb-md">{{ session('application_success') }}</x-ui.callout>
    @endif

    <div class="mb-lg grid gap-sm md:grid-cols-[minmax(260px,1fr)_210px_180px] md:items-end">
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

        <x-ui.field label="Status Go Live">
            <x-ui.select wire:model.live="goLive">
                <option value="">Semua</option>
                <option value="live">Go Live</option>
                <option value="pipeline">Pipe Line</option>
            </x-ui.select>
        </x-ui.field>
    </div>

    <div wire:loading.class="opacity-60" wire:target="search,product,goLive,gotoPage,nextPage,previousPage">
        <x-ui.table min-width="880px">
            <x-slot:head>
                <x-ui.th>Kode Aplikasi</x-ui.th>
                <x-ui.th>Nama Debitur</x-ui.th>
                <x-ui.th>Produk</x-ui.th>
                <x-ui.th>{{ $this->isOfficer ? 'Nama Referral' : 'Nama AO' }}</x-ui.th>
                <x-ui.th>Dokumen</x-ui.th>
                <x-ui.th>Tahapan</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th align="right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($this->applications as $application)
                <tr wire:key="application-{{ $application->id }}">
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
                        <x-ui.chip :tone="$application->go_live_date ? 'success' : 'neutral'">
                            {{ $application->go_live_date ? 'Go Live' : 'Pipe Line' }}
                        </x-ui.chip>
                    </x-ui.td>
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
                    <td colspan="8" class="px-5 py-xl text-center">
                        @if ($search !== '' || $product !== '' || $goLive !== '')
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
