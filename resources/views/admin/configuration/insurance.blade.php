<x-admin.configuration-shell title="Konfigurasi Asuransi" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif
        @error('configuration')
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
        @enderror

        <x-ui.card title="Casco dan TLO" note="CRUD dilakukan per matriks wilayah dan varian; empat coverage disimpan atomik.">
            <div class="mb-md grid grid-cols-1 gap-md md:grid-cols-[1fr_220px_auto]">
                <x-ui.field label="Wilayah">
                    <x-ui.select wire:model.live="zone">
                        @foreach ($zones as $availableZone)
                            <option value="{{ $availableZone }}">{{ $availableZone }}</option>
                        @endforeach
                        @if ($zone !== '' && ! $zones->contains($zone))
                            <option value="{{ $zone }}">{{ $zone }} (baru)</option>
                        @endif
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Varian">
                    <x-ui.select wire:model.live="variant">
                        <option>Batas Bawah</option>
                        <option>Batas Atas</option>
                    </x-ui.select>
                </x-ui.field>
                <div class="flex items-end">
                    <x-ui.button type="button" variant="secondary" size="md" wire:click="deleteCascoMatrix"
                                 wire:confirm="Hapus seluruh matriks wilayah dan varian ini? Matriks aktif akan ditolak validator.">Hapus Matriks</x-ui.button>
                </div>
            </div>

            <div class="mb-md flex gap-sm">
                <x-ui.input wire:model="newZone" placeholder="Nama wilayah baru" class="max-w-[280px]" />
                <x-ui.button type="button" variant="secondary" size="md" wire:click="selectNewZone">Tambah Wilayah</x-ui.button>
            </div>

            <x-ui.table min-width="1050px">
                <x-slot:head>
                    <x-ui.th>Band Min</x-ui.th><x-ui.th>Band Max</x-ui.th>
                    <x-ui.th>Casco Passenger (%)</x-ui.th><x-ui.th>Casco Commercial (%)</x-ui.th>
                    <x-ui.th>TLO Passenger (%)</x-ui.th><x-ui.th>TLO Commercial (%)</x-ui.th><x-ui.th></x-ui.th>
                </x-slot:head>
                @foreach ($cascoBands as $index => $band)
                    <tr wire:key="casco-{{ $index }}">
                        @foreach (['band_min', 'band_max', 'passenger_comprehensive', 'commercial_comprehensive', 'passenger_tlo', 'commercial_tlo'] as $field)
                            <x-ui.td>
                                @if (str_contains($field, 'band_'))
                                    <x-ui.money-input wire:model="cascoBands.{{ $index }}.{{ $field }}"
                                                      placeholder="{{ $field === 'band_max' ? 'Tanpa batas' : 'Rp 0' }}" />
                                @else
                                    <x-ui.input wire:model="cascoBands.{{ $index }}.{{ $field }}" type="number"
                                                step="0.000001" />
                                @endif
                            </x-ui.td>
                        @endforeach
                        <x-ui.td><button type="button" wire:click="removeCascoBand({{ $index }})" class="text-[13px] font-medium text-signature-coral">Hapus</button></x-ui.td>
                    </tr>
                @endforeach
            </x-ui.table>
            <button type="button" wire:click="addCascoBand" class="mt-sm text-[13px] font-medium text-link">+ Tambah band harga</button>
        </x-ui.card>

        <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-2">
            <x-ui.card title="Loading Usia Kendaraan" note="Usia 0–14 wajib tetap lengkap.">
                @foreach ($loadingRates as $index => $row)
                    <div class="grid grid-cols-[90px_1fr_auto] gap-sm border-b border-divider py-2">
                        <x-ui.input wire:model="loadingRates.{{ $index }}.vehicle_age" type="number" min="0" />
                        <x-ui.input wire:model="loadingRates.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100" />
                        <button type="button" wire:click="removeLoadingRate({{ $index }})" class="text-signature-coral">Hapus</button>
                    </div>
                @endforeach
                <button type="button" wire:click="addLoadingRate" class="mt-sm text-[13px] font-medium text-link">+ Tambah usia</button>
            </x-ui.card>

            <x-ui.card title="Perluasan" note="Enam kode perluasan wajib tersedia.">
                @foreach ($extensionRates as $index => $row)
                    <x-ui.field :label="$extensionLabels[$row['code']] ?? $row['code']" class="mb-sm">
                        <x-ui.input wire:model="extensionRates.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100" />
                    </x-ui.field>
                @endforeach
            </x-ui.card>

            <x-ui.card title="ACP" note="Rate dasar lima tenor dan upping setiap kelompok usia.">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <div>
                        @foreach ($acpBaseRates as $index => $row)
                            <x-ui.field :label="'Tahun '.$row['tenor_years'].' (%)'" class="mb-sm">
                                <x-ui.input wire:model="acpBaseRates.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100" />
                            </x-ui.field>
                        @endforeach
                    </div>
                    <div>
                        @foreach ($acpUppings as $index => $row)
                            <x-ui.field :label="$row['label'].' (%)'" class="mb-sm">
                                <x-ui.input wire:model="acpUppings.{{ $index }}.upping" type="number" step="0.000001" min="0" max="100" />
                            </x-ui.field>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="TJH" note="Hanya lapisan terakhir yang boleh tanpa batas.">
                @foreach ($tjhTiers as $index => $row)
                    <div class="grid grid-cols-[70px_1fr_1fr_auto] gap-sm border-b border-divider py-2">
                        <x-ui.input wire:model="tjhTiers.{{ $index }}.sequence" type="number" min="1" />
                        <x-ui.money-input wire:model="tjhTiers.{{ $index }}.limit_amount" placeholder="Tanpa batas" />
                        <x-ui.input wire:model="tjhTiers.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100" />
                        <button type="button" wire:click="removeTjhTier({{ $index }})" class="text-signature-coral">Hapus</button>
                    </div>
                @endforeach
                <button type="button" wire:click="addTjhTier" class="mt-sm text-[13px] font-medium text-link">+ Tambah lapisan</button>
            </x-ui.card>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                Periksa kembali seluruh field. Konfigurasi belum disimpan.
            </div>
        @endif
        <div class="flex items-center gap-sm">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Insurance</x-ui.button>
            <span wire:loading class="text-helper text-muted">Memeriksa kelengkapan seluruh konfigurasi…</span>
        </div>
    </form>
</x-admin.configuration-shell>
