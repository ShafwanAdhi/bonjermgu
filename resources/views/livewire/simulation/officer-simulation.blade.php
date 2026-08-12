<div class="band py-xl md:py-xxl"
     data-motion-click-only
     x-data
     x-on:simulation-calculated.window="$nextTick(() => $el.querySelector('#simulation-results-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
    <x-ui.page-header title="Simulasi Kredit" />

    <div class="grid grid-cols-1 gap-lg xl:grid-cols-[1fr_520px] xl:items-start">
        <form wire:submit="calculate" class="flex flex-col gap-lg">

            {{-- ------------------------------------------------------ Produk --}}
            <x-ui.card title="1 · Produk Pembiayaan">
                <div class="flex flex-col gap-sm sm:flex-row">
                    @foreach ([['DTN', 'Dana Tunai'], ['UCF', 'Pembiayaan Mobil Bekas']] as [$key, $label])
                        <button type="button" wire:click="$set('financing_type', '{{ $key }}')"
                                data-motion-action
                                @class([
                                    'flex flex-1 items-center gap-sm rounded-lg border px-[18px] py-3.5 text-left',
                                    'border-primary shadow-[0_0_0_1px_#181d26_inset]' => $financing_type === $key,
                                    'border-hairline' => $financing_type !== $key,
                                ])>
                            <span @class([
                                'flex h-[18px] w-[18px] items-center justify-center rounded-pill border-[1.5px]',
                                'border-primary' => $financing_type === $key,
                                'border-border-strong' => $financing_type !== $key,
                            ])>
                                @if ($financing_type === $key)
                                    <span class="h-2 w-2 rounded-pill bg-primary"></span>
                                @endif
                            </span>
                            <span @class([
                                'text-[14px] font-medium leading-[1.4]',
                                'text-ink' => $financing_type === $key,
                                'text-muted' => $financing_type !== $key,
                            ])>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
                @error('financing_type')
                    <p class="mt-sm text-helper text-signature-coral">{{ $message }}</p>
                @enderror
            </x-ui.card>

            {{-- ------------------------------------------------ Asal pengajuan --}}
            <x-ui.card title="2 · Asal Pengajuan">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Kategori Referral" required
                                helper="Menentukan Product, jadi mengubahnya mengubah rate dan biaya."
                                :error="$errors->first('referral_category_id')">
                        <x-ui.select wire:model.live="referral_category_id"
                                     :invalid="$errors->has('referral_category_id')">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Sub Kategori Referral" :error="$errors->first('referral_sub_category_id')">
                        <x-ui.select wire:model.live="referral_sub_category_id"
                                     :invalid="$errors->has('referral_sub_category_id')"
                                     :disabled="! $referral_category_id">
                            <option value="">{{ $referral_category_id ? 'Pilih sub kategori' : 'Pilih kategori dahulu' }}</option>
                            @foreach ($this->subCategories as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                </div>
            </x-ui.card>

            {{-- -------------------------------------------------- Profil debitur --}}
            <x-ui.card title="3 · Profil Debitur">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Type Debitur" required :error="$errors->first('debtor_type')">
                        <x-ui.select wire:model.live="debtor_type" :invalid="$errors->has('debtor_type')">
                            <option value="non_entrepreneur">Perorangan Non Wiraswasta</option>
                            <option value="entrepreneur">Perorangan Wiraswasta</option>
                            <option value="legal_entity">Badan Hukum Usaha</option>
                        </x-ui.select>
                    </x-ui.field>

                    @if ($debtor_type !== 'legal_entity')
                        <x-ui.field label="Usia Debitur" required :error="$errors->first('age_group_id')">
                            <x-ui.select wire:model.live="age_group_id" :invalid="$errors->has('age_group_id')">
                                <option value="">Pilih kelompok usia</option>
                                @foreach ($this->ageGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    @endif
                </div>
            </x-ui.card>

            {{-- -------------------------------------------------- Data kendaraan --}}
            <div x-data="{ expanded: true }">
                <x-ui.card>
                    <button type="button" class="mb-5 flex min-h-11 w-full items-center gap-sm md:cursor-default"
                            x-on:click="expanded = !expanded">
                        <span class="text-title-sm text-ink">4 · Data Kendaraan</span>
                        <span class="ml-auto text-helper text-muted md:hidden" x-text="expanded ? 'Tutup' : 'Buka'"></span>
                    </button>

                    <div class="grid transition-[grid-template-rows,opacity] duration-300 ease-out md:grid-rows-[1fr] md:opacity-100"
                         :class="expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                        <div class="overflow-hidden">
                        <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                            <x-ui.field label="Penggunaan Unit" required :error="$errors->first('usage_id')">
                                <x-ui.select wire:model.live="usage_id" :invalid="$errors->has('usage_id')"
                                             :disabled="! $referral_category_id">
                                    <option value="">{{ $referral_category_id ? 'Pilih penggunaan' : 'Pilih kategori dahulu' }}</option>
                                    @foreach ($this->usages as $usage)
                                        <option value="{{ $usage['id'] }}">{{ $usage['name'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Merk" required :error="$errors->first('brand_id')">
                                <x-ui.select wire:model.live="brand_id" :invalid="$errors->has('brand_id')"
                                             :disabled="! $usage_id">
                                    <option value="">{{ $usage_id ? 'Pilih merk' : 'Pilih penggunaan dahulu' }}</option>
                                    @foreach ($this->brands as $brand)
                                        <option value="{{ $brand['id'] }}">{{ $brand['name'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Type Kendaraan" required :error="$errors->first('type_id')">
                                <x-ui.select wire:model.live="type_id" :invalid="$errors->has('type_id')"
                                             :disabled="! $brand_id">
                                    <option value="">{{ $brand_id ? 'Pilih type' : 'Pilih merk dahulu' }}</option>
                                    @foreach ($this->vehicleTypes as $type)
                                        <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Model Kendaraan" required :error="$errors->first('model_id')">
                                <x-ui.select wire:model.live="model_id" :invalid="$errors->has('model_id')"
                                             :disabled="! $type_id">
                                    <option value="">{{ $type_id ? 'Pilih model' : 'Pilih type dahulu' }}</option>
                                    @foreach ($this->vehicleModels as $model)
                                        <option value="{{ $model['id'] }}">{{ $model['name'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Tahun Kendaraan" required
                                        helper="Hanya tahun yang memiliki harga yang ditampilkan."
                                        :error="$errors->first('vehicle_year')">
                                <x-ui.select wire:model.live="vehicle_year" :invalid="$errors->has('vehicle_year')"
                                             :disabled="! $model_id || $this->vehicleYears->isEmpty()">
                                    <option value="">
                                        @if (! $model_id)
                                            Pilih model dahulu
                                        @elseif ($this->vehicleYears->isEmpty())
                                            Harga tidak tersedia
                                        @else
                                            Pilih tahun
                                        @endif
                                    </option>
                                    @foreach ($this->vehicleYears as $year)
                                        <option value="{{ $year['year'] }}">{{ $year['year'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field :label="$this->isUcf ? 'Harga Pasar' : 'Harga Taksasi'" required
                                        :helper="$this->isUcf
                                            ? 'Harga transaksi unit.'
                                            : 'Nilai taksasi Anda. Selisih di atas Harga PHPM menaikkan Net DP.'"
                                        :error="$errors->first('unit_price')">
                                <x-ui.money-input wire:model.live.debounce.500ms="unit_price"
                                                  placeholder="Rp 150.000.000"
                                                  :invalid="$errors->has('unit_price')" />
                            </x-ui.field>

                            <x-ui.field label="Type Angsuran" required :error="$errors->first('instalment_type')">
                                <x-ui.select wire:model.live="instalment_type"
                                             :invalid="$errors->has('instalment_type')">
                                    <option value="ADDB">ADDB — angsuran di belakang</option>
                                    <option value="ADDM">ADDM — angsuran di muka</option>
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="STNK atas nama" required :error="$errors->first('stnk_ownership')">
                                <x-ui.select wire:model.live="stnk_ownership"
                                             :invalid="$errors->has('stnk_ownership')">
                                    <option value="own">Pribadi (milik sendiri)</option>
                                    <option value="other">Orang lain</option>
                                </x-ui.select>
                            </x-ui.field>
                        </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- ------------------------------------------------------ Asuransi --}}
            <div x-data="{ expanded: false }">
                <x-ui.card>
                    <button type="button" class="mb-5 flex min-h-11 w-full items-center gap-sm"
                            x-on:click="expanded = !expanded">
                        <span class="text-title-sm text-ink">5 · Asuransi</span>
                        <span class="ml-auto text-helper text-muted" x-text="expanded ? 'Tutup' : 'Buka'"></span>
                    </button>

                    <div class="grid transition-[grid-template-rows,opacity] duration-300 ease-out"
                         :class="expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                        <div class="overflow-hidden">
                        <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                            <x-ui.field label="Coverage" required :error="$errors->first('coverage_type')">
                                <x-ui.select wire:model.live="coverage_type" :invalid="$errors->has('coverage_type')">
                                    <option value="comprehensive_all">Comprehensive All Tenor</option>
                                    <option value="comprehensive_then_tlo">Comprehensive 1 tahun, sisanya TLO</option>
                                    <option value="tlo_all">TLO All Tenor</option>
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Varian Rate" required :error="$errors->first('rate_variant')">
                                <x-ui.select wire:model.live="rate_variant" :invalid="$errors->has('rate_variant')">
                                    <option value="Batas Bawah">Batas Bawah</option>
                                    <option value="Batas Atas">Batas Atas</option>
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Nilai TJH" :error="$errors->first('tjh_amount')">
                                <x-ui.money-input wire:model="tjh_amount" placeholder="Rp 0"
                                                  :invalid="$errors->has('tjh_amount')" />
                            </x-ui.field>

                            <x-ui.field label="Pertanggungan Pengemudi" :error="$errors->first('driver_amount')">
                                <x-ui.money-input wire:model="driver_amount" placeholder="Rp 0"
                                                  :invalid="$errors->has('driver_amount')" />
                            </x-ui.field>

                            <x-ui.field label="Pertanggungan Penumpang" :error="$errors->first('passenger_amount')">
                                <x-ui.money-input wire:model="passenger_amount" placeholder="Rp 0"
                                                  :invalid="$errors->has('passenger_amount')" />
                            </x-ui.field>

                            <x-ui.field label="Jumlah Penumpang" :error="$errors->first('passenger_count')">
                                <x-ui.input wire:model="passenger_count" type="number" min="0" max="20"
                                            :invalid="$errors->has('passenger_count')" />
                            </x-ui.field>
                        </div>

                        <p class="mt-lg mb-sm text-caption text-body">Perluasan</p>
                        <div class="grid grid-cols-1 gap-sm sm:grid-cols-2">
                            @foreach ([
                                ['ext_flood', 'Banjir'],
                                ['ext_earthquake', 'Gempa'],
                                ['ext_riot', 'Huru-hara'],
                                ['ext_terrorism', 'Teroris'],
                            ] as [$field, $label])
                                <label class="flex items-center gap-sm text-body-md text-body">
                                    <input wire:model="{{ $field }}" type="checkbox" class="rounded-xs border-hairline">
                                    {{ $label }}
                                </label>
                            @endforeach
                            <label class="flex items-center gap-sm text-body-md text-body sm:col-span-2">
                                <input wire:model="engine_warranty" type="checkbox" class="rounded-xs border-hairline">
                                Garansi Mesin
                            </label>
                        </div>

                        <p class="mt-md text-helper text-border-strong">
                            Perluasan, TJH, Pengemudi, dan Penumpang hanya ditagih pada tahun dengan
                            coverage Comprehensive.
                        </p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- -------------------------------------------------- Upping & biaya --}}
            <div x-data="{ expanded: false }">
                <x-ui.card>
                    <button type="button" class="mb-5 flex min-h-11 w-full items-center gap-sm text-left"
                            x-on:click="expanded = !expanded">
                        <span class="flex-1 text-left text-title-sm text-ink">6 · Upping dan Pengurang Pencairan</span>
                        <span class="ml-auto text-helper text-muted" x-text="expanded ? 'Tutup' : 'Buka'"></span>
                    </button>

                    <div class="grid transition-[grid-template-rows,opacity] duration-300 ease-out"
                         :class="expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                        <div class="overflow-hidden">
                        <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                            <x-ui.field label="Up Rate (%)" :error="$errors->first('up_rate')">
                                <x-ui.input wire:model="up_rate" type="number" step="0.0001" min="0" max="100"
                                            :invalid="$errors->has('up_rate')" />
                            </x-ui.field>

                            <x-ui.field label="Up Provisi (%)" :error="$errors->first('up_provisi')">
                                <x-ui.input wire:model="up_provisi" type="number" step="0.0001" min="0" max="100"
                                            :invalid="$errors->has('up_provisi')" />
                            </x-ui.field>

                            <x-ui.field label="Up Admin (Rp)" :error="$errors->first('up_admin')">
                                <x-ui.money-input wire:model="up_admin" placeholder="Rp 0"
                                                  :invalid="$errors->has('up_admin')" />
                            </x-ui.field>

                            <x-ui.field label="Up ACP (%)"
                                        helper="Kosongkan untuk memakai upping kelompok usia dari Admin."
                                        :error="$errors->first('up_acp')">
                                <x-ui.input wire:model="up_acp" type="number" step="0.0001" min="0" max="100"
                                            placeholder="Default Admin"
                                            :invalid="$errors->has('up_acp')" />
                            </x-ui.field>

                            <x-ui.field label="Deposit Angsuran (Rp)" :error="$errors->first('deposit_instalment')">
                                <x-ui.money-input wire:model="deposit_instalment" placeholder="Rp 0"
                                                  :invalid="$errors->has('deposit_instalment')" />
                            </x-ui.field>

                            <x-ui.field label="BBNKB (Rp)" :error="$errors->first('bbnkb_amount')">
                                <x-ui.money-input wire:model="bbnkb_amount" placeholder="Rp 0"
                                                  :invalid="$errors->has('bbnkb_amount')" />
                            </x-ui.field>

                            <x-ui.field label="PKB (Rp)" :error="$errors->first('pkb_amount')">
                                <x-ui.money-input wire:model="pkb_amount" placeholder="Rp 0"
                                                  :invalid="$errors->has('pkb_amount')" />
                            </x-ui.field>

                            <x-ui.field label="Faktur (Rp)" :error="$errors->first('invoice_amount')">
                                <x-ui.money-input wire:model="invoice_amount" placeholder="Rp 0"
                                                  :invalid="$errors->has('invoice_amount')" />
                            </x-ui.field>
                        </div>

                        <p class="mt-md text-helper text-border-strong">
                            Nilai di sini berlaku untuk satu simulasi ini saja dan tidak mengubah
                            konfigurasi Product maupun parameter Admin.
                        </p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            {{-- ------------------------------------------------ Dasar simulasi --}}
            <x-ui.card title="7 · Dasar Simulasi">
                <div class="grid grid-cols-1 gap-sm sm:grid-cols-2">
                    @foreach ([
                        'A' => ['Berdasarkan Nilai Kendaraan', 'Sistem menghitung pencairan maksimal dan angsuran.'],
                        'B' => [
                            $this->isUcf ? 'Berdasarkan Total DP' : 'Berdasarkan Kebutuhan Dana',
                            $this->isUcf ? 'Angsuran dihitung dari Total DP yang dikehendaki.' : 'Angsuran dihitung dari dana yang dibutuhkan.',
                        ],
                    ] as $key => [$label, $description])
                        <button type="button" wire:click="$set('mode', '{{ $key }}')"
                                data-motion-action
                                @class([
                                    'rounded-lg border p-md text-left',
                                    'border-primary shadow-[0_0_0_1px_#181d26_inset]' => $mode === $key,
                                    'border-hairline' => $mode !== $key,
                                ])>
                            <p class="text-[14px] font-medium leading-[1.3] {{ $mode === $key ? 'text-ink' : 'text-muted' }}">
                                {{ $label }}
                            </p>
                            <p class="mt-1 text-helper text-muted">{{ $description }}</p>
                        </button>
                    @endforeach
                </div>

                @if ($this->isModeB)
                    <div class="mt-md" data-simulation-field-enter>
                        <x-ui.field :label="$this->isUcf ? 'Total DP dikehendaki' : 'Dana yang dibutuhkan'"
                                    required :error="$errors->first('desired_amount')">
                            <x-ui.money-input wire:model.live.debounce.500ms="desired_amount"
                                              placeholder="Rp 50.000.000"
                                              :invalid="$errors->has('desired_amount')" />
                        </x-ui.field>
                    </div>
                @endif

                <div class="mt-lg">
                    <x-ui.button type="submit" size="md" wire:loading.attr="disabled" wire:target="calculate">
                        Hitung Simulasi
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>

        {{-- ---------------------------------------------------------- Hasil --}}
        <div id="simulation-results" class="flex flex-col gap-lg scroll-mt-lg">
            <x-ui.card id="simulation-results-card" title="Hasil Lima Tenor" :meta="$summary['product'] ?? null">
                @if ($summary)
                    <div class="mb-md flex flex-wrap gap-2">
                        <span class="rounded-sm bg-signature-cream px-2.5 py-1 text-[12px] font-medium text-ink">
                            {{ $summary['vehicle'] }}
                        </span>
                        <span class="rounded-sm border border-hairline bg-surface-soft px-2.5 py-1 text-[12px] font-medium text-muted">
                            Harga PHPM {{ $summary['phpm'] }}
                        </span>
                    </div>
                @endif

                <div class="mb-md rounded-md border border-info-border bg-info-bg px-md py-3 text-[13px] text-body"
                     wire:loading.delay wire:target="calculate">
                    Menghitung lima tenor di server…
                </div>

                @if ($calculationError)
                    <div class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] leading-[1.5] text-signature-coral">
                        <span class="font-medium">Simulasi belum dapat dihitung.</span><br>
                        {{ $calculationError }}
                    </div>
                @elseif (! $hasCalculated)
                    <div class="mb-md rounded-md border border-hairline bg-surface-soft px-md py-3 text-[13px] leading-[1.5] text-muted"
                         wire:loading.remove wire:target="calculate">
                        Lengkapi asal pengajuan, kendaraan, dan harga, kemudian klik
                        <span class="font-medium text-ink">Hitung Simulasi</span>.
                    </div>
                @endif

                @if ($hasCalculated)
                    <div wire:loading.class="opacity-60" wire:target="calculate">
                        <x-ui.table>
                            <x-slot:head>
                                <x-ui.th>Tenor</x-ui.th>
                                <x-ui.th align="right">{{ $this->disbursementHeading() }}</x-ui.th>
                                <x-ui.th align="right">Angsuran</x-ui.th>
                            </x-slot:head>

                            @foreach ($rows as $row)
                                <tr wire:key="result-{{ $row['tenor'] }}"
                                    class="{{ $row['tenor'] === $traced_tenor ? 'bg-surface-soft' : '' }}">
                                    <x-ui.td :class="$row['zero'] ? 'text-border-strong' : 'text-ink'">
                                        {{ $row['label'] }}
                                        @if ($row['reason'])
                                            <span class="block text-helper text-signature-coral">{{ $row['reason'] }}</span>
                                        @endif
                                    </x-ui.td>
                                    <x-ui.td align="right" numeric
                                             :class="$row['zero'] ? 'text-border-strong' : 'font-medium text-ink'">
                                        {{ $row['disbursement'] }}
                                    </x-ui.td>
                                    <x-ui.td align="right" numeric
                                             :class="$row['zero'] ? 'text-border-strong' : 'font-medium text-ink'">
                                        {{ $row['instalment'] }}
                                    </x-ui.td>
                                </tr>
                            @endforeach
                        </x-ui.table>
                    </div>

                @endif
            </x-ui.card>

            @if ($hasCalculated)
                <div>
                    <div class="mb-md flex flex-col items-center gap-sm text-center">
                        <div>
                            <h2 class="text-title-lg text-ink">Rincian Perhitungan</h2>
                            <span class="mt-1 block text-body-md text-muted">Tenor {{ $traced_tenor }} bulan</span>
                        </div>

                        <div class="inline-flex max-w-full items-center justify-center gap-1 overflow-x-auto rounded-lg border border-hairline bg-surface-soft p-1">
                            @foreach ([12, 24, 36, 48, 60] as $tenor)
                                <button type="button" wire:click="traceTenor({{ $tenor }})"
                                        data-motion-action
                                        @class([
                                            'min-h-9 min-w-14 rounded-md px-3 text-[13px] font-medium transition-colors',
                                            'bg-primary text-on-primary shadow-[0_8px_18px_rgba(24,29,38,0.12)]' => $tenor === $traced_tenor,
                                            'text-muted hover:bg-canvas hover:text-ink' => $tenor !== $traced_tenor,
                                        ])>
                                    {{ $tenor }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-md">
                        @foreach ($this->trace as $section)
                            <x-ui.card :title="$section['title']">
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
                </div>
            @endif
        </div>
    </div>
</div>
