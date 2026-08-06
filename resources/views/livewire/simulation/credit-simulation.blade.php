<div class="band py-xl md:py-xxl"
     x-data
     x-on:simulation-calculated.window="$nextTick(() => $el.querySelector('#simulation-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
    <x-ui.page-header title="Simulasi Kredit"
                      meta="Lengkapi parameter pembiayaan, lalu klik Hitung Simulasi untuk melihat estimasi lima tenor." />

    <div class="grid grid-cols-1 gap-lg lg:grid-cols-[1fr_420px] lg:items-start">
        <form wire:submit="calculate" class="flex flex-col gap-lg">
            <x-ui.card title="1 · Produk Pembiayaan">
                <div class="flex flex-col gap-sm sm:flex-row">
                    @foreach ([['DTN', 'Dana Tunai'], ['UCF', 'Pembiayaan Mobil Bekas']] as [$key, $label])
                        <button type="button" wire:click="$set('financing_type', '{{ $key }}')"
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

            <div x-data="{ expanded: true }">
                <x-ui.card>
                    <button type="button" class="mb-5 flex w-full items-center gap-sm md:cursor-default"
                            x-on:click="expanded = !expanded">
                        <span class="text-title-sm text-ink">2 · Profil Perhitungan</span>
                        <span class="ml-auto text-helper text-muted md:hidden" x-text="expanded ? 'Tutup' : 'Buka'"></span>
                    </button>

                    <div :class="expanded ? 'block' : 'hidden md:block'">
                        <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                            <x-ui.field label="Domisili Debitur" required :error="$errors->first('domicile_id')">
                                <x-ui.select wire:model.live="domicile_id" :invalid="$errors->has('domicile_id')">
                                    <option value="">Pilih domisili</option>
                                    @foreach ($this->domiciles as $domicile)
                                        <option value="{{ $domicile->id }}">{{ $domicile->name }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Type Debitur" required :error="$errors->first('debtor_type')">
                                <x-ui.select wire:model.live="debtor_type" :invalid="$errors->has('debtor_type')">
                                    <option value="non_entrepreneur">Perorangan Non Wiraswasta</option>
                                    <option value="entrepreneur">Perorangan Wiraswasta</option>
                                    <option value="legal_entity">Badan Hukum Usaha</option>
                                </x-ui.select>
                            </x-ui.field>

                            @if ($debtor_type !== 'legal_entity')
                                <x-ui.field label="Usia Debitur" required class="sm:col-span-2"
                                            :error="$errors->first('age_group_id')">
                                    <x-ui.select wire:model.live="age_group_id"
                                                 :invalid="$errors->has('age_group_id')">
                                        <option value="">Pilih kelompok usia</option>
                                        @foreach ($this->ageGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->label }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div x-data="{ expanded: true }">
                <x-ui.card>
                    <button type="button" class="mb-5 flex w-full items-center gap-sm md:cursor-default"
                            x-on:click="expanded = !expanded">
                        <span class="text-title-sm text-ink">3 · Data Kendaraan</span>
                        <span class="ml-auto text-helper text-muted md:hidden" x-text="expanded ? 'Tutup' : 'Buka'"></span>
                    </button>

                    <div :class="expanded ? 'block' : 'hidden md:block'">
                        <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                            <x-ui.field label="Penggunaan Unit" required :error="$errors->first('usage_id')">
                                <x-ui.select wire:model.live="usage_id" :invalid="$errors->has('usage_id')">
                                    <option value="">Pilih penggunaan</option>
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
                                <x-ui.select wire:model.live="vehicle_year"
                                             :invalid="$errors->has('vehicle_year')"
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

                            <x-ui.field label="Type Angsuran" required :error="$errors->first('instalment_type')">
                                <x-ui.select wire:model.live="instalment_type"
                                             :invalid="$errors->has('instalment_type')">
                                    <option value="ADDB">ADDB — angsuran di belakang</option>
                                    <option value="ADDM">ADDM — angsuran di muka</option>
                                </x-ui.select>
                            </x-ui.field>

                            <x-ui.field label="Asuransi" required class="sm:col-span-2"
                                        :error="$errors->first('coverage_type')">
                                <x-ui.select wire:model.live="coverage_type"
                                             :invalid="$errors->has('coverage_type')">
                                    <option value="comprehensive_all">Comprehensive All Tenor</option>
                                    <option value="comprehensive_then_tlo">Comprehensive 1 tahun, sisanya TLO</option>
                                    <option value="tlo_all">TLO All Tenor</option>
                                </x-ui.select>
                            </x-ui.field>

                            @if ($financing_type === 'DTN')
                                <x-ui.field label="Kebutuhan Dana" required :error="$errors->first('funding_purpose')">
                                    <x-ui.select wire:model.live="funding_purpose"
                                                 :invalid="$errors->has('funding_purpose')">
                                        <option value="">Pilih kebutuhan</option>
                                        @foreach ($this->fundingPurposes() as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                            @else
                                <x-ui.field label="Harga Pasar" required :error="$errors->first('market_price')">
                                    <x-ui.input wire:model.live.debounce.500ms="market_price" type="number" min="1"
                                                inputmode="numeric" :invalid="$errors->has('market_price')" />
                                </x-ui.field>
                            @endif

                            <x-ui.field label="STNK atas nama" required :error="$errors->first('stnk_ownership')">
                                <x-ui.select wire:model.live="stnk_ownership"
                                             :invalid="$errors->has('stnk_ownership')">
                                    <option value="own">Pribadi (milik sendiri)</option>
                                    <option value="other">Orang lain</option>
                                </x-ui.select>
                            </x-ui.field>
                        </div>

                        <p class="mt-md text-helper text-border-strong">
                            Pilihan kendaraan dimuat bertingkat dari server. Sistem hanya mengambil model milik
                            type yang dipilih, bukan seluruh master kendaraan.
                        </p>
                    </div>
                </x-ui.card>
            </div>

            <x-ui.card title="4 · Dasar Simulasi">
                <div class="grid grid-cols-1 gap-sm sm:grid-cols-2">
                    @foreach ($this->modeOptions() as $key => $option)
                        <button type="button" wire:click="$set('mode', '{{ $key }}')"
                                @class([
                                    'rounded-lg border p-md text-left',
                                    'border-primary shadow-[0_0_0_1px_#181d26_inset]' => $mode === $key,
                                    'border-hairline' => $mode !== $key,
                            ])>
                            <p class="text-[14px] font-medium leading-[1.3] {{ $mode === $key ? 'text-ink' : 'text-muted' }}">
                                {{ $option['label'] }}
                            </p>
                            <p class="mt-1 text-helper text-muted">{{ $option['description'] }}</p>
                        </button>
                    @endforeach
                </div>

                @if ($mode === 'B')
                    <div class="mt-md">
                        <x-ui.field :label="$financing_type === 'DTN' ? 'Dana yang dibutuhkan' : 'Total DP dikehendaki'"
                                    required :error="$errors->first('desired_amount')">
                            <x-ui.input wire:model.live.debounce.500ms="desired_amount" type="number" min="1"
                                        inputmode="numeric" :invalid="$errors->has('desired_amount')" />
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

        <div id="simulation-results" class="flex flex-col gap-lg scroll-mt-lg lg:sticky lg:top-lg">
            <x-ui.card title="5 · Hasil Lima Tenor">
                <div class="mb-md flex flex-wrap gap-2">
                    <span class="rounded-sm bg-signature-cream px-2.5 py-1 text-[12px] font-medium text-ink">
                        {{ $this->productLabel() }}
                    </span>
                    <span class="rounded-sm border border-hairline bg-surface-soft px-2.5 py-1 text-[12px] font-medium text-muted">
                        {{ $this->modeLabel() }}
                    </span>
                    @if ($vehicleSummary)
                        <span class="rounded-sm border border-hairline bg-surface-soft px-2.5 py-1 text-[12px] font-medium text-muted">
                            {{ $vehicleSummary }} {{ $vehicle_year }}
                        </span>
                    @endif
                </div>

                <div class="mb-md rounded-md border border-info-border bg-info-bg px-md py-3 text-[13px] text-body"
                     wire:loading.delay wire:target="calculate">
                    Menghitung lima tenor di server…
                </div>

                @if ($calculationError)
                    <div @class([
                        'mb-md rounded-md border px-md py-3 text-[13px] leading-[1.5]',
                        'border-signature-coral bg-danger-bg text-signature-coral' => ! $priceUnavailable,
                        'border-warning-border bg-warning-bg text-body' => $priceUnavailable,
                    ])>
                        <span class="font-medium">Simulasi belum dapat dihitung.</span><br>
                        {{ $calculationError }}
                    </div>
                @elseif (! $hasCalculated)
                    <div class="mb-md rounded-md border border-hairline bg-surface-soft px-md py-3 text-[13px] leading-[1.5] text-muted"
                         wire:loading.remove wire:target="calculate">
                        Lengkapi parameter pembiayaan dan kendaraan, kemudian klik <span class="font-medium text-ink">Hitung Simulasi</span>.
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

                            @foreach ($results as $row)
                                <tr wire:key="result-{{ $row['tenor'] }}">
                                    <x-ui.td :class="$row['zero'] ? 'text-border-strong' : 'text-ink'">
                                        {{ $row['tenor'] }}
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

                    <p class="mt-md text-[12px] leading-[1.6] text-muted">
                        Nominal pembiayaan bersifat estimasi.<br>
                        Besarnya pembiayaan berdasarkan hasil verifikasi profil debitur dan kondisi kendaraan.
                    </p>

                    @if (! $showPrintForm)
                        <x-ui.button type="button" size="md" class="mt-lg w-full" wire:click="openPrintForm">
                            Download Hasil Simulasi
                        </x-ui.button>
                    @else
                        <form wire:submit="preparePrint" class="mt-lg rounded-md border border-hairline bg-surface-soft p-md">
                            <p class="text-[14px] font-medium text-ink">Data calon debitur untuk dokumen simulasi</p>
                            <p class="mt-1 text-helper text-muted">Data ini hanya dicantumkan pada PDF dan tidak mengubah perhitungan.</p>

                            <div class="mt-md flex flex-col gap-md">
                                <x-ui.field label="Nama" required :error="$errors->first('debtor_name')">
                                    <x-ui.input wire:model="debtor_name" type="text" autocomplete="name"
                                                :invalid="$errors->has('debtor_name')" />
                                </x-ui.field>

                                <x-ui.field label="NIK" required :error="$errors->first('debtor_nik')">
                                    <x-ui.input wire:model="debtor_nik" type="text" inputmode="numeric" maxlength="16"
                                                :invalid="$errors->has('debtor_nik')" />
                                </x-ui.field>

                                <x-ui.field label="Tanggal Lahir" required :error="$errors->first('debtor_birth_date')">
                                    <x-ui.input wire:model="debtor_birth_date" type="date"
                                                :invalid="$errors->has('debtor_birth_date')" />
                                </x-ui.field>
                            </div>

                            <div class="mt-md flex flex-wrap gap-sm">
                                <x-ui.button type="submit" size="md" wire:loading.attr="disabled" wire:target="preparePrint">
                                    Lanjut ke Halaman Download
                                </x-ui.button>
                                <x-ui.button type="button" variant="secondary" size="md" wire:click="closePrintForm">
                                    Batal
                                </x-ui.button>
                            </div>
                        </form>
                    @endif
                @endif
            </x-ui.card>

            <p class="text-helper text-border-strong">
                Rate, LTV, premi asuransi, dan biaya tidak ditampilkan pada layar Referral.
            </p>
        </div>
    </div>
</div>
