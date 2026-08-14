@use('App\Support\Format')

<div class="band py-xl md:py-xxl">
    <x-ui.back-link :href="route('lending.index')" class="mb-md" />

    <x-ui.page-header :title="$reportTitle"
                      meta="Dihitung dari seluruh application, tanpa input operasional" />

    <div class="mb-5 grid grid-cols-1 items-end gap-sm sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
        <x-ui.field label="Bulan Go Live" class="w-full">
            <x-ui.input type="month" wire:model.live="month" class="w-full" />
        </x-ui.field>

        <x-ui.field label="Produk" class="w-full">
            <x-ui.select wire:model.live="product" class="w-full">
                <option value="">Semua</option>
                @foreach ($this->products() as $productOption)
                    <option value="{{ $productOption->value }}">{{ $productOption->label() }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        <x-ui.field label="Kategori Referral" class="w-full">
            <x-ui.select wire:model.live="category_id" class="w-full">
                <option value="">Semua</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>

        @if ($this->filters->hasAny())
            <x-ui.button variant="secondary" size="md" type="button" wire:click="clearFilters" class="w-full xl:w-auto">
                Bersihkan
            </x-ui.button>
        @endif
    </div>

    <div class="relative">
        <div wire:loading.delay.class="opacity-45" wire:target="month,product,category_id,clearFilters"
             class="transition-opacity duration-150">
            <x-ui.table min-width="820px" label="Laporan lending">
                <x-slot:head>
                    <x-ui.th>{{ $nameHeading }}</x-ui.th>
                    <x-ui.th align="right">Actual Unit</x-ui.th>
                    <x-ui.th align="right">Actual A/F</x-ui.th>
                    <x-ui.th align="right">Pipe Line Unit</x-ui.th>
                    <x-ui.th align="right">Pipe Line A/F</x-ui.th>
                </x-slot:head>

                @forelse ($this->rows as $row)
                    <tr wire:key="lending-row-{{ md5($row->name) }}">
                        <x-ui.td class="font-medium text-ink">{{ $row->name }}</x-ui.td>
                        <x-ui.td align="right" numeric class="text-ink">{{ $row->actualUnits }}</x-ui.td>
                        <x-ui.td align="right" numeric class="text-ink">{{ Format::rupiah($row->actualAmount) }}</x-ui.td>
                        <x-ui.td align="right" numeric>{{ $row->pipelineUnits }}</x-ui.td>
                        <x-ui.td align="right" numeric>{{ Format::rupiah($row->pipelineAmount) }}</x-ui.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-xl text-center">
                            @if ($this->filters->hasAny())
                                <p class="text-body-md text-ink">Tidak ada application yang cocok dengan penyaring ini.</p>
                                <p class="mt-1 text-helper text-muted">Longgarkan bulan, produk, atau kategori yang dipilih.</p>
                            @else
                                <p class="text-body-md text-ink">Belum ada application dalam sistem.</p>
                                <p class="mt-1 text-helper text-muted">
                                    Angka muncul setelah Account Officer membuat Credit Application.
                                </p>
                            @endif
                        </td>
                    </tr>
                @endforelse

                <tr class="bg-surface-soft">
                    <td class="border-t border-border-strong px-5 py-3.5">
                        <span class="text-[14px] font-medium leading-[1.4] text-ink">TOTAL</span>
                        <span class="block text-helper text-muted">Mengikuti penyaring aktif</span>
                    </td>
                    <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                        {{ $this->totals->actualUnits }}
                    </td>
                    <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                        {{ Format::rupiah($this->totals->actualAmount) }}
                    </td>
                    <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                        {{ $this->totals->pipelineUnits }}
                    </td>
                    <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                        {{ Format::rupiah($this->totals->pipelineAmount) }}
                    </td>
                </tr>
            </x-ui.table>
        </div>

        <div wire:loading.delay.flex wire:target="month,product,category_id,clearFilters"
             class="pointer-events-none absolute inset-x-0 top-0 hidden justify-center pt-md">
            <span class="rounded-full border border-hairline bg-canvas px-4 py-2 text-helper text-muted shadow-[0_10px_24px_rgba(24,29,38,0.08)]">
                Memperbarui tabel...
            </span>
        </div>
    </div>
</div>
