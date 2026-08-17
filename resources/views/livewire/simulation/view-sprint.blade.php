@use('App\Support\Format')

<div class="band py-xl md:py-xxl">
    <x-ui.back-link :href="route('simulation.officer')" wire:navigate class="mb-md" />
    <x-ui.page-header title="View Sprint"
                      meta="Lembar entri untuk SPRINT. Seluruh angka dibaca dari simulasi yang baru Anda jalankan." />

    @if (! $this->available)
        @php($reachable = $this->financedTenors)
        <x-ui.card>
            <div class="py-xl text-center">
                <p class="text-body-md text-ink">
                    {{ $reachable === [] ? 'Belum ada simulasi.' : 'Tenor ini tidak tersedia.' }}
                </p>
                <p class="mt-1 text-helper text-muted">{{ $unavailableReason }}</p>

                {{-- Tenor lain masih terjangkau; tidak perlu kembali ke layar simulasi. --}}
                @if ($reachable !== [])
                    <div class="mt-lg flex flex-wrap items-center justify-center gap-2">
                        <span class="text-helper text-muted">Tenor yang tersedia:</span>
                        @foreach ($reachable as $tenorOption)
                            <a href="{{ route('simulation.officer.sprint', $tenorOption) }}" wire:navigate
                               class="rounded-sm border border-hairline bg-canvas px-2.5 py-1 text-[12px] font-medium text-link">
                                {{ $tenorOption }} bulan
                            </a>
                        @endforeach
                    </div>
                @endif

                <x-ui.button :href="route('simulation.officer')" wire:navigate
                             :variant="$reachable === [] ? 'primary' : 'secondary'" size="md" class="mt-lg">
                    Ke Simulasi Kredit
                </x-ui.button>
            </div>
        </x-ui.card>
    @else
        @php($s = $this->sheet())

        <div class="flex flex-col gap-xl">

            {{-- ------------------------------------------------- Isian AO --}}
            <div class="grid grid-cols-1 gap-lg xl:grid-cols-3 xl:items-start">
                {{--
                    Kedua kode dirangkai dari satu token per dimensi, seperti
                    Master!C5 dan Master!C6 merangkainya dari dropdown. Yang sudah
                    dijawab simulasi dipilihkan di muka; sisanya diisi AO.
                --}}
                @php($lookupAvailable = $this->hasOfferingLookup)
                @php($selectorOptions = $this->selectorOptions)
                @php($productIdOptions = $this->productIdOptions)
                @php($productOfferingOptions = $this->productOfferingOptions)
                @php($selectorFields = [
                    ['product', 'Product', 'Product SPRINT', false],
                    ['channel', 'Kanal', 'Kanal pengajuan', false],
                    ['unit', 'Jenis Kendaraan', 'Jenis kendaraan', false],
                    ['brand', 'Brand', 'Brand kendaraan', false],
                    ['profile', 'Profil Debitur', 'Profil debitur', true],
                    ['debtor_type', 'Type Debitur', 'Type debitur', false],
                    ['dp', 'Golongan DP', 'Golongan DP', false],
                    ['region', 'Wilayah', 'Wilayah', false],
                ])

                <x-ui.card title="Pilihan SPRINT">
                    <div class="flex flex-col gap-lg">
                        <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                            @foreach ($selectorFields as [$group, $label, $placeholder, $wide])
                                <x-ui.field :label="$label" class="{{ $wide ? 'sm:col-span-2' : '' }}"
                                            :helper="$sprint_unit === '' && $group === 'unit' ? 'Unit Commercial bercabang jadi Pick Up atau Truck; simulasi tidak menentukannya.' : null">
                                    <x-ui.select wire:model.live="sprint_{{ $group }}">
                                        <option value="">{{ $placeholder }}</option>
                                        @foreach ($selectorOptions[$group] ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                            @endforeach

                            {{-- Ditentukan baris tenor yang dipilih AO, bukan dipilih di sini. --}}
                            <x-ui.field label="Tenor">
                                <x-ui.input type="text" value="{{ intdiv($tenor, 12) }}TH" disabled />
                            </x-ui.field>

                            <x-ui.field label="Jenis Angsuran">
                                <x-ui.input type="text" value="{{ $sprint_instalment !== '' ? $sprint_instalment : '—' }}" disabled />
                            </x-ui.field>

                        </div>

                        <div class="rounded-sm border border-hairline bg-surface-soft p-md">
                            <div class="grid grid-cols-1 gap-md">
                                {{-- Jalan buntu tanpa diagnosa memaksa AO menebak
                                     di antara delapan dropdown. --}}
                                {{-- Katalog kosong bukan salah pilihan AO; tanpa
                                     pesan ini layarnya hanya berkata "belum tersedia". --}}
                                @if (! $lookupAvailable)
                                    <p role="status" class="text-helper text-muted">
                                        Katalog offering SPRINT belum diimpor, jadi kode disusun dari token.
                                        Minta Admin menjalankan <span class="font-mono">sprint:import-offerings</span>.
                                    </p>
                                @elseif ($this->lookupDeadEnd)
                                    @php($blocking = $this->blockingFilters)
                                    <p role="status" class="text-helper text-signature-coral">
                                        Tidak ada offering yang cocok dengan kombinasi ini.
                                        {{-- Kalau tak satu pun filter membukanya sendirian,
                                             mengatakannya lebih berguna daripada diam. --}}
                                        @if ($blocking !== [])
                                            Coba longgarkan {{ implode(' atau ', $blocking) }}.
                                        @else
                                            Lebih dari satu pilihan menyempitkannya sekaligus.
                                        @endif
                                    </p>
                                @endif

                                <x-ui.field label="Product ID" required>
                                    @if ($lookupAvailable)
                                        <x-ui.select wire:model.live="product_id" :disabled="$productIdOptions === []">
                                            <option value="">{{ $productIdOptions === [] ? 'Product ID belum tersedia' : 'Pilih Product ID' }}</option>
                                            @foreach ($productIdOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </x-ui.select>
                                    @else
                                        <x-ui.input type="text" value="{{ $product_id }}" disabled
                                                    placeholder="Product ID belum tersedia" />
                                    @endif
                                </x-ui.field>

                                <x-ui.field label="Product Offering" required
                                            :helper="$lookupAvailable && $product_id !== '' && $productOfferingOptions === []
                                                ? 'Tidak ada offering yang cocok dengan filter yang dipilih.'
                                                : null">
                                    @if ($lookupAvailable)
                                        <x-ui.select wire:model.live="product_offering" :disabled="$product_id === '' || $productOfferingOptions === []">
                                            <option value="">
                                                @if ($product_id === '')
                                                    Pilih Product ID terlebih dahulu
                                                @elseif ($productOfferingOptions === [])
                                                    Product Offering belum tersedia
                                                @else
                                                    Pilih Product Offering
                                                @endif
                                            </option>
                                            @foreach ($productOfferingOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </x-ui.select>
                                    @else
                                        <x-ui.input type="text" value="{{ $product_offering }}" disabled
                                                    placeholder="Product Offering belum tersedia" />
                                    @endif
                                </x-ui.field>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                {{-- Kosakata tiap field berbeda dan itu disengaja; lihat ViewSprint::MANUAL_OPTIONS. --}}
                @php($manualOptions = \App\Livewire\Simulation\ViewSprint::MANUAL_OPTIONS)

                <x-ui.card title="Diisi Account Officer">
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        <x-ui.field label="Nama Customer" required class="sm:col-span-2">
                            <x-ui.input wire:model.live.debounce.400ms="nama_customer" type="text"
                                        placeholder="Nama customer sesuai pengajuan" />
                        </x-ui.field>

                        <x-ui.field label="Cara Pembayaran" class="sm:col-span-2">
                            <x-ui.select wire:model.live="cara_pembayaran">
                                @if ($cara_pembayaran !== '' && ! in_array($cara_pembayaran, $manualOptions['cara_pembayaran'], true))
                                    <option value="{{ $cara_pembayaran }}">{{ $cara_pembayaran }}</option>
                                @endif
                                @foreach ($manualOptions['cara_pembayaran'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Mandiri KPM (KKB)">
                            <x-ui.select wire:model.live="mandiri_kpm">
                                @if ($mandiri_kpm !== '' && ! in_array($mandiri_kpm, $manualOptions['mandiri_kpm'], true))
                                    <option value="{{ $mandiri_kpm }}">{{ $mandiri_kpm }}</option>
                                @endif
                                @foreach ($manualOptions['mandiri_kpm'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Kondisi Kendaraan" class="sm:col-span-2">
                            <x-ui.select wire:model.live="kondisi_kendaraan">
                                @if ($kondisi_kendaraan !== '' && ! in_array($kondisi_kendaraan, $manualOptions['kondisi_kendaraan'], true))
                                    <option value="{{ $kondisi_kendaraan }}">{{ $kondisi_kendaraan }}</option>
                                @endif
                                @foreach ($manualOptions['kondisi_kendaraan'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Spesifik Product" class="sm:col-span-2">
                            <x-ui.input wire:model.live.debounce.400ms="spesifik_product" type="text"
                                        placeholder="Contoh: Fasilitas Dana" />
                        </x-ui.field>

                        <x-ui.field label="Wira No">
                            <x-ui.input wire:model.live.debounce.400ms="wira_no" type="text"
                                        placeholder="Isi 0 bila tidak ada" />
                        </x-ui.field>

                        <x-ui.field label="Is BELIV?">
                            <x-ui.select wire:model.live="is_beliv">
                                @foreach ($manualOptions['is_beliv'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Sisa Kewajiban" class="sm:[&_[data-ui-field-label]]:flex sm:[&_[data-ui-field-label]]:min-h-[38px] sm:[&_[data-ui-field-label]]:items-end">
                            <x-ui.money-input wire:model.live.debounce.400ms="sisa_kewajiban" placeholder="Rp 0" />
                        </x-ui.field>

                        <x-ui.field label="Sisa OS LK Sebelumnya" class="sm:[&_[data-ui-field-label]]:flex sm:[&_[data-ui-field-label]]:min-h-[38px] sm:[&_[data-ui-field-label]]:items-end">
                            <x-ui.money-input wire:model.live.debounce.400ms="sisa_os_lk" placeholder="Rp 0" />
                        </x-ui.field>

                        @foreach ([['acp_axp', 'ACP & AXP'], ['gap', 'GAP'], ['hic', 'HIC'], ['water_hammer', 'Water Hammer & Theft by Driver']] as [$field, $label])
                            @php($fieldValue = ${$field})
                            <x-ui.field :label="$label" wire:key="manual-{{ $field }}"
                                        class="{{ in_array($field, ['hic', 'water_hammer'], true) ? 'sm:[&_[data-ui-field-label]]:flex sm:[&_[data-ui-field-label]]:min-h-[38px] sm:[&_[data-ui-field-label]]:items-end' : '' }}">
                                <x-ui.select wire:model.live="{{ $field }}">
                                    @if ($fieldValue !== '' && ! in_array($fieldValue, $manualOptions[$field], true))
                                        <option value="{{ $fieldValue }}">{{ $fieldValue }}</option>
                                    @endif
                                    @foreach ($manualOptions[$field] as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card title="Insurance Paid Entry">
                    <div class="flex flex-col gap-sm">
                        @foreach (range(1, 5) as $year)
                            <div wire:key="paid-{{ $year }}"
                                 class="rounded-sm border border-hairline bg-canvas p-sm">
                                <div class="mb-sm text-caption text-ink">
                                    Tahun {{ $year }}
                                </div>
                                <div class="grid grid-cols-1 gap-sm sm:grid-cols-3 sm:items-end">
                                    <x-ui.field label="Status">
                                        <x-ui.select wire:model.live="paid_status.{{ $year }}">
                                            <option value="CASH">CASH</option>
                                            <option value="ON LOAN">ON LOAN</option>
                                        </x-ui.select>
                                    </x-ui.field>
                                    <x-ui.field label="Diskon">
                                        <x-ui.money-input wire:model.live.debounce.400ms="paid_discount.{{ $year }}" placeholder="Rp 0" />
                                    </x-ui.field>
                                    <x-ui.field label="Paid">
                                        <x-ui.money-input wire:model.live.debounce.400ms="paid_amount.{{ $year }}" placeholder="Rp 0" />
                                    </x-ui.field>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            {{-- ------------------------------------------------- Lembarnya --}}
            <div class="flex min-w-0 flex-col gap-md"
                 x-data="viewSprintExport(@js($this->exportFileName))">
                <div class="flex flex-wrap items-center gap-sm border-t border-hairline pt-lg">
                    <span class="text-helper text-muted">Tenor {{ $s['tenor'] }} bulan</span>
                    <div class="ml-auto flex gap-1">
                        @php($reachable = $this->financedTenors)
                        @foreach (\App\Livewire\Simulation\ViewSprint::TENORS as $tenorOption)
                            @php($usable = in_array($tenorOption, $reachable, true))
                            {{-- Tenor tanpa pembiayaan tidak ditawarkan: mengkliknya
                                 hanya berujung pada layar "tidak tersedia". --}}
                            <a @if ($usable) href="{{ route('simulation.officer.sprint', $tenorOption) }}" wire:navigate @endif
                               aria-label="{{ $usable ? 'Lihat tenor '.$tenorOption.' bulan' : 'Tenor '.$tenorOption.' bulan tidak menghasilkan pembiayaan' }}"
                               @if (! $usable) aria-disabled="true" title="Tidak menghasilkan pembiayaan" @endif
                               @if ($tenorOption === $s['tenor']) aria-current="page" @endif
                               @class([
                                   'rounded-sm border px-2.5 py-1 text-[12px] font-medium',
                                   'border-primary bg-primary text-on-primary' => $tenorOption === $s['tenor'],
                                   'border-hairline bg-canvas text-muted' => $tenorOption !== $s['tenor'] && $usable,
                                   'cursor-not-allowed border-divider bg-surface-soft text-border-strong' => ! $usable,
                               ])>{{ $tenorOption }}</a>
                        @endforeach
                    </div>
                    @php($missing = $this->missingForExport)
                    <x-ui.button type="button" size="sm" x-on:click="save()"
                                 data-export-button
                                 :disabled="$missing !== []"
                                 x-bind:aria-busy="busy"
                                 x-bind:class="busy && 'pointer-events-none opacity-70'"
                                 :title="$missing === [] ? null : 'Lengkapi '.implode(', ', $missing)">
                        <span x-show="! busy">Unduh PNG</span>
                        <span x-show="busy" x-cloak>Menyiapkan…</span>
                    </x-ui.button>
                </div>

                {{-- Lembar setengah jadi terbaca seperti lembar sah begitu jadi
                     gambar, dan gambar itu yang dikirim ke pusat. --}}
                @if ($missing !== [])
                    <p role="status" class="text-helper text-signature-coral">
                        Belum bisa diunduh. Lengkapi dulu: {{ implode(', ', $missing) }}.
                    </p>
                @endif

                <p x-show="failed" x-cloak role="alert" class="text-helper text-signature-coral">
                    Gambar gagal dibuat. Gunakan tangkapan layar biasa, atau muat ulang halaman lalu coba lagi.
                </p>

                {{-- Yang dipotret. Sengaja berlatar putih dan berukuran tetap
                     supaya hasilnya sama di layar mana pun. --}}
                <div x-ref="sheet" class="w-full overflow-x-auto">
                    <div class="min-w-[900px] bg-white p-6 text-[12px] leading-[1.45] text-black">

                        <div class="mb-4 flex items-start justify-between border-b-2 border-black pb-2">
                            <div>
                                <p class="text-[15px] font-bold">SIMULASI KREDIT</p>
                            </div>
                            <div class="text-right text-[11px]">
                                <p><span class="font-semibold">Product ID</span> : {{ $product_id !== '' ? $product_id : '—' }}</p>
                                <p><span class="font-semibold">Product Offering</span> : {{ $product_offering !== '' ? $product_offering : '—' }}</p>
                                <p class="mt-1">{{ now()->translatedFormat('d F Y') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            {{-- kolom kiri --}}
                            <div>
                                <table class="w-full">
                                    @foreach ([
                                        ['NAMA CUSTOMER', $nama_customer !== '' ? $nama_customer : '—'],
                                        ['TYPE UNIT / TAHUN', $s['type_unit'].' / '.$s['tahun_unit']],
                                        ['HARGA KENDARAAN', Format::rupiah($s['harga_kendaraan'])],
                                        ['UANG MUKA (DP)', Format::rupiah($s['uang_muka'])],
                                        ['LEASING CASH DEPOSIT', Format::rupiah($s['leasing_cash_deposit'])],
                                        ['ADMINISTRASI KREDIT', Format::rupiah($s['administrasi_kredit'])],
                                        ['FIDUSIA', Format::rupiah($s['fidusia'])],
                                        ['PROVISI', Format::rupiah($s['provisi'])],
                                        ['ASURANSI', Format::rupiah($s['asuransi'])],
                                        ['ANGSURAN', Format::rupiah($s['angsuran'])],
                                        ['TENOR', $s['tenor'].' Bulan'],
                                        ['RATE BUNGA', number_format($s['rate_bunga'] * 100, 4, ',', '.').'%'],
                                        ['TOTAL BAYAR PERTAMA', Format::rupiah($s['total_bayar_pertama'])],
                                        ['POKOK HUTANG', Format::rupiah($s['pokok_hutang'])],
                                    ] as [$label, $value])
                                        <tr class="border-b border-neutral-300">
                                            <td class="w-[45%] py-[3px] pr-2 align-top font-medium">{{ $label }}</td>
                                            <td class="py-[3px] text-right tabular-nums">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>

                            {{-- kolom kanan --}}
                            <div class="flex flex-col gap-3">
                                <div>
                                    <p class="mb-1 bg-neutral-200 px-2 py-[3px] text-[11px] font-bold">APPLICATION DATA ENTRY</p>
                                    <table class="w-full">
                                        @foreach ([
                                            ['CARA PEMBAYARAN', $cara_pembayaran],
                                            ['MANDIRI KPM (KKB)', $mandiri_kpm],
                                            ['ANGSURAN PERTAMA', $s['angsuran_pertama']],
                                            ['KONDISI KENDARAAN', $kondisi_kendaraan],
                                            ['SPESIFIK PRODUCT', $spesifik_product],
                                            ['JUMLAH UNIT', $s['jumlah_unit']],
                                            ['BBN', Format::rupiah($s['bbn'])],
                                            ['SISA KEWAJIBAN', Format::rupiah((int) $sisa_kewajiban)],
                                            ['SISA OS LK SEBELUMNYA', Format::rupiah((int) $sisa_os_lk)],
                                            ['BIAYA PROSES FAKTUR', Format::rupiah($s['biaya_proses_faktur'])],
                                            ['WIRA NO', $wira_no],
                                            ['REFUND ADMINISTRATION', Format::rupiah($s['refund_administration'])],
                                            ['REFUND PROVISION', Format::rupiah($s['refund_provision'])],
                                            ['TYPE CUSTOMER', $s['type_customer']],
                                            ['IS BELIV?', $is_beliv],
                                        ] as [$label, $value])
                                            <tr class="border-b border-neutral-300">
                                                <td class="w-[58%] py-[3px] pr-2 align-top font-medium">{{ $label }}</td>
                                                <td class="py-[3px] text-right tabular-nums">{{ $value }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="mb-1 bg-neutral-200 px-2 py-[3px] text-[11px] font-bold">INSURANCE ENTRY</p>
                                        <table class="w-full">
                                            @foreach ([
                                                ['USAGE', $s['usage']],
                                                ['RATE JUAL', $s['rate_jual']],
                                                ['WILAYAH ASURANSI', $s['wilayah_asuransi']],
                                                ['ASURANSI CLP', number_format($s['asuransi_clp'] * 100, 2, ',', '.').'%'],
                                                ['ACP & AXP', $acp_axp],
                                                ['GAP', $gap],
                                                ['HIC', $hic],
                                                ['GARANSI MESIN', $s['garansi_mesin']],
                                            ] as [$label, $value])
                                                <tr class="border-b border-neutral-300">
                                                    <td class="py-[3px] pr-2 align-top font-medium">{{ $label }}</td>
                                                    <td class="py-[3px] text-right tabular-nums">{{ $value }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                    <div>
                                        <p class="mb-1 bg-neutral-200 px-2 py-[3px] text-[11px] font-bold">FINANCIAL DATA ENTRY</p>
                                        <table class="w-full">
                                            @foreach ([
                                                ['REFUND BUNGA', Format::rupiah($s['refund_bunga'])],
                                                ['DEPOSIT ANGSURAN ('.$s['deposit_angsuran'].'×)', Format::rupiah($s['deposit_angsuran_rp'])],
                                                ['REFUND PREMI INSURANCE', Format::rupiah($s['refund_premi_insurance'])],
                                            ] as [$label, $value])
                                                <tr class="border-b border-neutral-300">
                                                    <td class="py-[3px] pr-2 align-top font-medium">{{ $label }}</td>
                                                    <td class="py-[3px] text-right tabular-nums">{{ $value }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Detail asuransi per tahun --}}
                        <p class="mt-4 mb-1 bg-neutral-200 px-2 py-[3px] text-[11px] font-bold">DETAIL ASURANSI</p>
                        <table class="w-full border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-neutral-100">
                                    @foreach (['Tahun', 'Asuransi', 'TJH', 'Huru-hara', 'Banjir', 'Water Hammer & Theft by Driver', 'Gempa', 'Teroris', 'PA Pengemudi', 'PA Penumpang'] as $head)
                                        <th class="border border-neutral-400 px-1 py-[3px] text-left font-semibold">{{ $head }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($s['detail_asuransi'] as $year => $line)
                                    <tr>
                                        <td class="border border-neutral-400 px-1 py-[3px]">{{ $year }}</td>
                                        @if ($line['aktif'])
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['asuransi'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px] text-right tabular-nums">{{ Format::rupiah($line['tjh']) }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['huru_hara'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['banjir'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['water_hammer'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['gempa'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['teroris'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px]">{{ $line['pa_pengemudi'] }}</td>
                                            <td class="border border-neutral-400 px-1 py-[3px] text-right tabular-nums">{{ Format::rupiah($line['pa_penumpang']) }}</td>
                                        @else
                                            <td class="border border-neutral-400 px-1 py-[3px] text-neutral-400" colspan="9">—</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Insurance paid entry --}}
                        <p class="mt-4 mb-1 bg-neutral-200 px-2 py-[3px] text-[11px] font-bold">INSURANCE PAID ENTRY</p>
                        <table class="w-full border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-neutral-100">
                                    @foreach (['Tahun', 'Cash / On Loan', 'Diskon', 'Paid'] as $head)
                                        <th class="border border-neutral-400 px-1 py-[3px] text-left font-semibold">{{ $head }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (range(1, 5) as $year)
                                    <tr>
                                        <td class="border border-neutral-400 px-1 py-[3px]">{{ $year }}</td>
                                        <td class="border border-neutral-400 px-1 py-[3px]">{{ $paid_status[$year] ?? 'CASH' }}</td>
                                        <td class="border border-neutral-400 px-1 py-[3px] text-right tabular-nums">{{ Format::rupiah((int) ($paid_discount[$year] ?? 0)) }}</td>
                                        <td class="border border-neutral-400 px-1 py-[3px] text-right tabular-nums">{{ Format::rupiah((int) ($paid_amount[$year] ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <p class="mt-3 text-[10px] text-neutral-500">
                            Nominal bersifat estimasi, dihitung sistem atas parameter yang berlaku saat lembar ini dibuat.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
