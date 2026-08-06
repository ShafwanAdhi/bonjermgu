@use('App\Support\Format')

<div>
<x-admin.configuration-shell title="Uji Konfigurasi">

    <p class="mb-lg text-[13px] leading-[1.6] text-muted">
        Menjalankan engine yang sama dengan simulasi Referral, tetapi Product dipilih langsung
        agar konfigurasi yang baru Anda ubah bisa diperiksa hasilnya. Tidak ada data debitur di
        halaman ini, dan tidak ada hasil yang disimpan atau dicetak.
    </p>

    <div class="grid grid-cols-1 gap-lg xl:grid-cols-[380px_1fr] xl:items-start">

        {{-- ------------------------------------------------------- Input --}}
        <form wire:submit="calculate">
            <x-ui.card title="Parameter Uji">
                <div class="flex flex-col gap-md">

                    <x-ui.field label="Product" required :error="$errors->first('product_id')"
                                helper="Product yang ingin diperiksa konfigurasinya.">
                        <x-ui.select wire:model.live="product_id" :invalid="$errors->has('product_id')">
                            @foreach ($this->products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <div class="grid grid-cols-2 gap-md">
                        <x-ui.field label="Produk Pembiayaan" required>
                            <x-ui.select wire:model.live="financing_type">
                                <option value="DTN">Dana Tunai</option>
                                <option value="UCF">Pembiayaan Mobil Bekas</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Mode" required>
                            <x-ui.select wire:model.live="mode">
                                <option value="A">Mode A</option>
                                <option value="B">Mode B</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Type Debitur" required>
                            <x-ui.select wire:model="debtor_type">
                                <option value="non_entrepreneur">Perorangan Non Wiraswasta</option>
                                <option value="entrepreneur">Perorangan Wiraswasta</option>
                                <option value="legal_entity">Badan Hukum Usaha</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Kelompok Usia" :error="$errors->first('age_group_id')">
                            <x-ui.select wire:model="age_group_id">
                                <option value="">Tidak dipilih</option>
                                @foreach ($this->ageGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    </div>

                    <p class="border-t border-hairline pt-md text-eyebrow uppercase text-muted">Kendaraan</p>

                    <div class="grid grid-cols-2 gap-md">
                        <x-ui.field label="Penggunaan Unit" required>
                            <x-ui.select wire:model.live="usage_id">
                                @foreach ($this->usages as $usage)
                                    <option value="{{ $usage->id }}">{{ $usage->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Merk" required>
                            <x-ui.select wire:model.live="brand_id" :disabled="$this->brands->isEmpty()">
                                <option value="">Pilih merk</option>
                                @foreach ($this->brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Type" required>
                            <x-ui.select wire:model.live="type_id" :disabled="$this->vehicleTypes->isEmpty()">
                                <option value="">Pilih type</option>
                                @foreach ($this->vehicleTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Model" required :error="$errors->first('model_id')">
                            <x-ui.select wire:model.live="model_id" :disabled="$this->vehicleModels->isEmpty()"
                                         :invalid="$errors->has('model_id')">
                                <option value="">Pilih model</option>
                                @foreach ($this->vehicleModels as $model)
                                    <option value="{{ $model->id }}">{{ $model->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Tahun" required :error="$errors->first('vehicle_year')"
                                    helper="Hanya tahun yang punya harga.">
                            <x-ui.select wire:model="vehicle_year" :disabled="$this->vehicleYears->isEmpty()"
                                         :invalid="$errors->has('vehicle_year')">
                                <option value="">Pilih tahun</option>
                                @foreach ($this->vehicleYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Type Angsuran" required>
                            <x-ui.select wire:model="instalment_type">
                                <option value="ADDB">ADDB</option>
                                <option value="ADDM">ADDM</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Asuransi" required class="col-span-2">
                            <x-ui.select wire:model="coverage_type">
                                <option value="comprehensive_all">Comprehensive All Tenor</option>
                                <option value="comprehensive_then_tlo">Comprehensive 1 Tahun</option>
                                <option value="tlo_all">TLO All Tenor</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="STNK atas nama" required class="col-span-2">
                            <x-ui.select wire:model="stnk_ownership">
                                <option value="own">Pribadi (milik sendiri)</option>
                                <option value="other">Orang lain</option>
                            </x-ui.select>
                        </x-ui.field>

                        @if ($this->isUcf)
                            <x-ui.field label="Harga Pasar" required class="col-span-2"
                                        :error="$errors->first('market_price')">
                                <x-ui.input wire:model="market_price" type="number" min="0" step="1"
                                            :invalid="$errors->has('market_price')" />
                            </x-ui.field>
                        @endif

                        @if ($this->isModeB)
                            <x-ui.field label="Nominal Dikehendaki" required class="col-span-2"
                                        :error="$errors->first('desired_amount')">
                                <x-ui.input wire:model="desired_amount" type="number" min="0" step="1"
                                            :invalid="$errors->has('desired_amount')" />
                            </x-ui.field>
                        @endif
                    </div>

                    <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="calculate">Hitung</span>
                        <span wire:loading wire:target="calculate">Menghitung…</span>
                    </x-ui.button>
                </div>
            </x-ui.card>

            {{-- Rate table of the selected Product, so an empty tenor is visible
                 before it silently produces a zero row. --}}
            @if ($this->selectedProduct)
                <x-ui.card title="Rate Product Terpilih" class="mt-lg"
                           note="Sel kosong berarti tenor tidak tersedia — bukan rate 0%.">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->productRates as $tenor => $rate)
                            <div class="flex-1 rounded-sm border border-hairline px-2 py-1.5 text-center">
                                <p class="text-helper text-muted">{{ $tenor }} bln</p>
                                <p class="text-[13px] tabular-nums {{ $rate === null ? 'text-border-strong' : 'text-ink' }}">
                                    {{ $rate === null ? 'kosong' : number_format((float) $rate * 100, 4, ',', '.').'%' }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reachability: a Product no category maps to can never
                         produce these figures for a real Referral. --}}
                    <div class="mt-md border-t border-hairline pt-md">
                        <p class="mb-1.5 text-eyebrow uppercase text-muted">Dijangkau kategori</p>
                        @if ($this->reachingCategories->isEmpty())
                            <p class="text-[13px] leading-[1.6] text-signature-coral">
                                Tidak ada kategori Referral yang memetakan ke Product ini. Angka di
                                bawah tidak akan pernah muncul pada simulasi Referral mana pun.
                            </p>
                        @else
                            <ul class="flex flex-col gap-1">
                                @foreach ($this->reachingCategories as $reach)
                                    <li class="text-[13px] leading-[1.5] text-body">{{ $reach }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-ui.card>
            @endif
        </form>

        {{-- ------------------------------------------------------ Hasil --}}
        <div class="flex flex-col gap-lg">

            @if ($calculationError)
                <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                    {{ $calculationError }}
                </div>
            @endif

            @if (! $hasCalculated)
                <x-ui.card>
                    <div class="py-xl text-center">
                        <p class="text-body-md text-ink">Belum ada hasil.</p>
                        <p class="mt-1 text-helper text-muted">
                            Pilih Product dan kendaraan, lalu tekan Hitung untuk melihat hasil beserta
                            seluruh langkah perhitungannya.
                        </p>
                    </div>
                </x-ui.card>
            @else
                <x-ui.card title="Hasil Lima Tenor" :meta="$this->selectedProduct?->name">
                    <x-ui.table>
                        <x-slot:head>
                            <x-ui.th>Tenor</x-ui.th>
                            <x-ui.th align="right">{{ $this->disbursementHeading() }}</x-ui.th>
                            <x-ui.th align="right">Angsuran</x-ui.th>
                            <x-ui.th align="right">Jejak</x-ui.th>
                        </x-slot:head>

                        @foreach ($rows as $row)
                            <tr class="{{ $row['tenor'] === $traced_tenor ? 'bg-surface-soft' : '' }}">
                                <x-ui.td class="{{ $row['zero'] ? 'text-border-strong' : 'text-ink' }}">
                                    {{ $row['label'] }}
                                    @if ($row['reason'])
                                        <span class="block text-helper text-signature-coral">{{ $row['reason'] }}</span>
                                    @endif
                                </x-ui.td>
                                <x-ui.td align="right" numeric
                                         class="{{ $row['zero'] ? 'text-border-strong' : 'font-medium text-ink' }}">
                                    {{ $row['disbursement'] }}
                                </x-ui.td>
                                <x-ui.td align="right" numeric
                                         class="{{ $row['zero'] ? 'text-border-strong' : 'font-medium text-ink' }}">
                                    {{ $row['instalment'] }}
                                </x-ui.td>
                                <x-ui.td align="right">
                                    <button type="button" wire:click="traceTenor({{ $row['tenor'] }})"
                                            @class([
                                                'text-[13px] font-medium',
                                                'text-ink underline' => $row['tenor'] === $traced_tenor,
                                                'text-link' => $row['tenor'] !== $traced_tenor,
                                            ])>Lihat</button>
                                </x-ui.td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <p class="mt-sm text-helper text-muted">
                        Baris bernilai nol tetap ditampilkan. Menyembunyikannya membuat pengguna
                        mengira sistem gagal.
                    </p>
                </x-ui.card>

                {{-- ------------------------------------------- Jejak hitung --}}
                <div>
                    <div class="mb-md flex flex-wrap items-center gap-sm">
                        <h2 class="text-title-lg text-ink">Rincian Perhitungan</h2>
                        <span class="text-body-md text-muted">tenor {{ $traced_tenor }} bulan</span>

                        <div class="ml-auto flex gap-1">
                            @foreach ([12, 24, 36, 48, 60] as $tenor)
                                <button type="button" wire:click="traceTenor({{ $tenor }})"
                                        @class([
                                            'rounded-sm border px-2.5 py-1 text-[12px] font-medium',
                                            'border-primary bg-primary text-on-primary' => $tenor === $traced_tenor,
                                            'border-hairline bg-canvas text-muted' => $tenor !== $traced_tenor,
                                        ])>{{ $tenor }}</button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-md">
                        @foreach ($this->trace as $section)
                            <x-ui.card :title="$section['title']" :note="$section['note']">
                                <div class="flex flex-col">
                                    @foreach ($section['steps'] as $step)
                                        <div @class([
                                            'grid grid-cols-1 gap-1 border-b border-divider py-2.5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)_auto] sm:items-baseline sm:gap-md',
                                            'bg-surface-soft' => $step['emphasis'] ?? false,
                                        ])>
                                            <span @class([
                                                'text-body-md',
                                                'font-medium text-ink' => $step['emphasis'] ?? false,
                                                'text-body' => ! ($step['emphasis'] ?? false),
                                            ])>{{ $step['label'] }}</span>

                                            <span class="font-mono text-[12px] leading-[1.5] text-border-strong">
                                                {{ $step['formula'] }}
                                            </span>

                                            <span @class([
                                                'tabular-nums sm:text-right',
                                                'text-[15px] font-medium text-ink' => $step['emphasis'] ?? false,
                                                'text-body-md text-body' => ! ($step['emphasis'] ?? false),
                                            ])>{{ $step['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </x-ui.card>
                        @endforeach
                    </div>

                    <x-ui.callout class="mt-md">
                        Angka pada rincian ini dibaca langsung dari hasil engine, bukan dihitung ulang
                        untuk ditampilkan. Kalau ada yang tidak cocok dengan
                        <span class="font-medium">docs/credit-simulation.md</span>, yang keliru adalah
                        engine atau konfigurasinya — bukan tampilan ini.
                    </x-ui.callout>
                </div>
            @endif
        </div>
    </div>

</x-admin.configuration-shell>
</div>
