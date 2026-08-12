@use('App\Support\Format')

<div x-data
     x-on:simulation-calculated.window="$nextTick(() => $el.querySelector('#configuration-results-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
    <x-admin.configuration-shell title="Uji Konfigurasi">
        <div class="grid grid-cols-1 gap-lg xl:grid-cols-[1fr_520px] xl:items-start">

            {{-- ------------------------------------------------------- Input --}}
            <form wire:submit="calculate" class="flex flex-col gap-lg">
                <x-ui.card title="1 · Product">
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        <x-ui.field label="Product" required :error="$errors->first('product_id')">
                            <x-ui.select wire:model.live="product_id" :invalid="$errors->has('product_id')">
                                @foreach ($this->products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Profil Simulasi" required :error="$errors->first('simulation_profile')">
                            <x-ui.select wire:model.live="simulation_profile"
                                         :invalid="$errors->has('simulation_profile')">
                                <option value="referral">Referral</option>
                                <option value="officer">Account Officer</option>
                            </x-ui.select>
                        </x-ui.field>
                    </div>

                    @if ($this->selectedProduct)
                        <div class="mt-md border-t border-hairline pt-md">
                            <p class="mb-sm text-caption text-body">Rate Product Terpilih</p>
                            <x-ui.table min-width="260px" class="[&_td]:px-3 [&_th]:px-3">
                                <x-slot:head>
                                    <x-ui.th>Tenor</x-ui.th>
                                    <x-ui.th align="right">Effective Rate</x-ui.th>
                                </x-slot:head>

                                @foreach ($this->productRates as $tenor => $rate)
                                    <tr wire:key="selected-product-rate-{{ $tenor }}">
                                        <x-ui.td class="text-ink">{{ $tenor }} bulan</x-ui.td>
                                        <x-ui.td align="right" numeric
                                                 class="{{ $rate === null ? 'text-border-strong' : 'font-medium text-ink' }}">
                                            {{ $rate === null ? '-' : number_format((float) $rate * 100, 4, ',', '.').'%' }}
                                        </x-ui.td>
                                    </tr>
                                @endforeach
                            </x-ui.table>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card title="2 · Produk Pembiayaan">
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
                </x-ui.card>

                <x-ui.card title="3 · Profil Debitur">
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        <x-ui.field label="Type Debitur" required>
                            <x-ui.select wire:model.live="debtor_type">
                                <option value="non_entrepreneur">Perorangan Non Wiraswasta</option>
                                <option value="entrepreneur">Perorangan Wiraswasta</option>
                                <option value="legal_entity">Badan Hukum Usaha</option>
                            </x-ui.select>
                        </x-ui.field>

                        @if ($debtor_type !== 'legal_entity')
                            <x-ui.field label="Kelompok Usia" :error="$errors->first('age_group_id')">
                                <x-ui.select wire:model="age_group_id">
                                    <option value="">Tidak dipilih</option>
                                    @foreach ($this->ageGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->label }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card title="4 · Data Kendaraan">
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        <x-ui.field label="Penggunaan Unit" required>
                            <x-ui.select wire:model.live="usage_id">
                                <option value="">Pilih penggunaan</option>
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

                        <x-ui.field label="Type Kendaraan" required>
                            <x-ui.select wire:model.live="type_id" :disabled="$this->vehicleTypes->isEmpty()">
                                <option value="">Pilih type</option>
                                @foreach ($this->vehicleTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Model Kendaraan" required :error="$errors->first('model_id')">
                            <x-ui.select wire:model.live="model_id" :disabled="$this->vehicleModels->isEmpty()"
                                         :invalid="$errors->has('model_id')">
                                <option value="">Pilih model</option>
                                @foreach ($this->vehicleModels as $model)
                                    <option value="{{ $model->id }}">{{ $model->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Tahun Kendaraan" required :error="$errors->first('vehicle_year')">
                            <x-ui.select wire:model.live="vehicle_year" :disabled="$this->vehicleYears->isEmpty()"
                                         :invalid="$errors->has('vehicle_year')">
                                <option value="">Pilih tahun</option>
                                @foreach ($this->vehicleYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Type Angsuran" required>
                            <x-ui.select wire:model.live="instalment_type">
                                <option value="ADDB">ADDB - angsuran di belakang</option>
                                <option value="ADDM">ADDM - angsuran di muka</option>
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="STNK atas nama" required>
                            <x-ui.select wire:model.live="stnk_ownership">
                                <option value="own">Pribadi (milik sendiri)</option>
                                <option value="other">Orang lain</option>
                            </x-ui.select>
                        </x-ui.field>

                        @if ($this->needsUnitPrice)
                            <x-ui.field :label="$this->unitPriceLabel()" required
                                        :error="$errors->first('market_price')">
                                <x-ui.money-input wire:model.live.debounce.500ms="market_price"
                                                  placeholder="Rp 50.000.000"
                                                  :invalid="$errors->has('market_price')" />
                            </x-ui.field>
                        @endif
                    </div>
                </x-ui.card>

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
                                    <x-ui.field label="Coverage" required>
                                        <x-ui.select wire:model.live="coverage_type">
                                            <option value="comprehensive_all">Comprehensive All Tenor</option>
                                            <option value="comprehensive_then_tlo">Comprehensive 1 Tahun</option>
                                            <option value="tlo_all">TLO All Tenor</option>
                                        </x-ui.select>
                                    </x-ui.field>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div x-data="{ expanded: false }">
                    <x-ui.card>
                        <button type="button" class="mb-5 flex min-h-11 w-full items-center gap-sm"
                                x-on:click="expanded = !expanded">
                            <span class="text-title-sm text-ink">6 · Upping</span>
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

                                    <x-ui.field label="Up ACP (%)" :error="$errors->first('up_acp')">
                                        <x-ui.input wire:model="up_acp" type="number" step="0.0001" min="0" max="100"
                                                    placeholder="Default"
                                                    :invalid="$errors->has('up_acp')" />
                                    </x-ui.field>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

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
                            <span wire:loading.remove wire:target="calculate">Hitung Simulasi</span>
                            <span wire:loading wire:target="calculate">Menghitung...</span>
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </form>

            {{-- ------------------------------------------------------ Hasil --}}
            <div id="configuration-results" class="flex flex-col gap-lg scroll-mt-lg">
                <x-ui.card id="configuration-results-card" title="Hasil Lima Tenor" :meta="$this->selectedProduct?->name">
                    <div class="mb-md rounded-md border border-info-border bg-info-bg px-md py-3 text-[13px] text-body"
                         wire:loading.delay wire:target="calculate">
                        Menghitung lima tenor di server...
                    </div>

                    @if ($calculationError)
                        <div class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] leading-[1.5] text-signature-coral">
                            {{ $calculationError }}
                        </div>
                    @elseif (! $hasCalculated)
                        <div class="mb-md rounded-md border border-hairline bg-surface-soft px-md py-3 text-[13px] leading-[1.5] text-muted"
                             wire:loading.remove wire:target="calculate">
                            Lengkapi parameter, lalu klik <span class="font-medium text-ink">Hitung Simulasi</span>.
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
                                    <tr wire:key="configuration-result-{{ $row['tenor'] }}"
                                        class="{{ $row['tenor'] === $traced_tenor ? 'bg-surface-soft' : '' }}">
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
    </x-admin.configuration-shell>
</div>
