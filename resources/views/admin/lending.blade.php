@use('App\Domain\Application\FinancingProduct')
@use('App\Support\Format')

<x-layouts.app title="Lending — Kebon Jeruk Multiguna">
    <div class="band py-xl md:py-xxl">

        <x-ui.page-header title="Lending"
                          meta="Dihitung dari seluruh application — tanpa input operasional" />

        {{-- Filters submit as a plain GET so a report is a shareable URL. --}}
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-sm">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <x-ui.field label="Periode Go Live dari" class="w-auto">
                <x-ui.input type="date" name="from" value="{{ $filters->from }}" class="w-auto" />
            </x-ui.field>

            <x-ui.field label="sampai" class="w-auto">
                <x-ui.input type="date" name="to" value="{{ $filters->to }}" class="w-auto" />
            </x-ui.field>

            <x-ui.field label="Produk" class="w-auto">
                <x-ui.select name="product" class="w-auto">
                    <option value="">Semua</option>
                    @foreach (FinancingProduct::cases() as $product)
                        <option value="{{ $product->value }}" @selected($filters->product === $product->value)>
                            {{ $product->label() }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field label="Kategori Referral" class="w-auto">
                <x-ui.select name="category_id" class="w-auto">
                    <option value="">Semua</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($filters->categoryId === $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.button type="submit" size="md">Terapkan</x-ui.button>

            @if ($filters->hasAny())
                <x-ui.button variant="secondary" size="md" :href="route('lending', ['tab' => $tab])">
                    Bersihkan
                </x-ui.button>
            @endif
        </form>

        <x-ui.tabs :items="[
            ['label' => 'Per AO', 'url' => route('lending', ['tab' => 'ao']), 'active' => $tab === 'ao'],
            ['label' => 'Per Referral', 'url' => route('lending', ['tab' => 'referral']), 'active' => $tab === 'referral'],
        ]" />

        <x-ui.table min-width="820px">
            <x-slot:head>
                <x-ui.th>{{ $nameHeading }}</x-ui.th>
                <x-ui.th align="right">Actual Unit</x-ui.th>
                <x-ui.th align="right">Actual A/F</x-ui.th>
                <x-ui.th align="right">Pipe Line Unit</x-ui.th>
                <x-ui.th align="right">Pipe Line A/F</x-ui.th>
            </x-slot:head>

            {{-- A row appears only for a party with at least one application. --}}
            @forelse ($rows as $row)
                <tr>
                    <x-ui.td class="font-medium text-ink">{{ $row->name }}</x-ui.td>
                    <x-ui.td align="right" numeric class="text-ink">{{ $row->actualUnits }}</x-ui.td>
                    <x-ui.td align="right" numeric class="text-ink">{{ Format::rupiah($row->actualAmount) }}</x-ui.td>
                    <x-ui.td align="right" numeric>{{ $row->pipelineUnits }}</x-ui.td>
                    <x-ui.td align="right" numeric>{{ Format::rupiah($row->pipelineAmount) }}</x-ui.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-xl text-center">
                        @if ($filters->hasAny())
                            <p class="text-body-md text-ink">Tidak ada application yang cocok dengan penyaring ini.</p>
                            <p class="mt-1 text-helper text-muted">Longgarkan periode atau produk yang dipilih.</p>
                        @else
                            <p class="text-body-md text-ink">Belum ada application dalam sistem.</p>
                            <p class="mt-1 text-helper text-muted">
                                Angka muncul setelah Account Officer membuat Credit Application.
                            </p>
                        @endif
                    </td>
                </tr>
            @endforelse

            {{-- TOTAL is mandatory and follows the active filters — lending.md §6. --}}
            <tr class="bg-surface-soft">
                <td class="border-t border-border-strong px-5 py-3.5">
                    <span class="text-[14px] font-medium leading-[1.4] text-ink">TOTAL</span>
                    <span class="block text-helper text-muted">Mengikuti penyaring aktif</span>
                </td>
                <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                    {{ $totals->actualUnits }}
                </td>
                <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                    {{ Format::rupiah($totals->actualAmount) }}
                </td>
                <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                    {{ $totals->pipelineUnits }}
                </td>
                <td class="border-t border-border-strong px-5 py-3.5 text-right text-[14px] font-medium tabular-nums text-ink">
                    {{ Format::rupiah($totals->pipelineAmount) }}
                </td>
            </tr>
        </x-ui.table>

        {{-- Mandatory wording — lending.md section 7. --}}
        <x-ui.callout class="mt-md inline-flex">
            Periode hanya berlaku pada kolom Actual.
            Pipe Line menggambarkan posisi saat ini dan tidak dipotong periode.
        </x-ui.callout>
    </div>
</x-layouts.app>
