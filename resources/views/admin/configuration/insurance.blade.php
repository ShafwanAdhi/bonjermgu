<x-admin.configuration-shell title="Konfigurasi Asuransi" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif
        @error('configuration')
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
        @enderror

        <x-ui.card title="Casco dan TLO">
            <div class="mb-lg rounded-md bg-surface-soft p-md md:p-lg">
                <div class="grid grid-cols-1 gap-md lg:grid-cols-[minmax(220px,1fr)_170px_max-content_minmax(220px,0.85fr)_max-content] lg:items-end">
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

                    <button type="button" wire:click="deleteCascoMatrix"
                            wire:confirm="Hapus seluruh matriks wilayah dan varian ini? Matriks aktif akan ditolak validator."
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-hairline bg-canvas px-3.5 text-[12px] font-medium leading-none text-signature-coral transition-colors hover:border-signature-coral">
                        Hapus Matriks
                    </button>

                    <x-ui.field label="Wilayah baru">
                        <x-ui.input wire:model="newZone" placeholder="Contoh: Wilayah 4" />
                    </x-ui.field>

                    <button type="button"
                            wire:click="selectNewZone"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-hairline bg-canvas px-3.5 text-[12px] font-medium leading-none text-ink transition-colors hover:border-border-strong">
                        Tambah Wilayah
                    </button>
                </div>
            </div>

            <div class="divide-y divide-divider lg:hidden">
                @foreach ($cascoBands as $index => $band)
                    <div class="py-lg first:pt-0 last:pb-0" wire:key="casco-mobile-{{ $index }}">
                        <div class="mb-md flex items-center justify-between gap-md">
                            <p class="text-[13px] font-medium text-ink">Band #{{ $index + 1 }}</p>
                            <button type="button" wire:click="removeCascoBand({{ $index }})"
                                    class="inline-flex min-h-10 items-center rounded-sm px-2 text-[13px] font-medium text-signature-coral">
                                Hapus
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-sm">
                            <x-ui.field label="Band min">
                                <x-ui.money-input wire:model="cascoBands.{{ $index }}.band_min" placeholder="Rp 0" />
                            </x-ui.field>

                            <x-ui.field label="Band max">
                                <x-ui.money-input wire:model="cascoBands.{{ $index }}.band_max" placeholder="Tanpa batas" />
                            </x-ui.field>
                        </div>

                        <div class="mt-md border-t border-divider pt-md">
                            <p class="mb-sm text-[13px] font-medium text-ink">Casco (%)</p>
                            <div class="grid grid-cols-2 gap-sm">
                            <x-ui.field label="Passenger">
                                <x-ui.input wire:model="cascoBands.{{ $index }}.passenger_comprehensive" type="number" step="0.000001" />
                            </x-ui.field>

                            <x-ui.field label="Commercial">
                                <x-ui.input wire:model="cascoBands.{{ $index }}.commercial_comprehensive" type="number" step="0.000001" />
                            </x-ui.field>
                            </div>
                        </div>

                        <div class="mt-md border-t border-divider pt-md">
                            <p class="mb-sm text-[13px] font-medium text-ink">TLO (%)</p>
                            <div class="grid grid-cols-2 gap-sm">
                            <x-ui.field label="Passenger">
                                <x-ui.input wire:model="cascoBands.{{ $index }}.passenger_tlo" type="number" step="0.000001" />
                            </x-ui.field>

                            <x-ui.field label="Commercial">
                                <x-ui.input wire:model="cascoBands.{{ $index }}.commercial_tlo" type="number" step="0.000001" />
                            </x-ui.field>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-hidden rounded-lg border border-hairline lg:block">
                <div class="grid grid-cols-[minmax(0,1.1fr)_minmax(0,1.1fr)_repeat(4,minmax(0,1fr))_52px] bg-surface-soft px-md pt-3 text-center text-[11px] font-medium uppercase leading-[1.35] tracking-[0.08em] text-muted">
                    <span class="col-span-2 border-b border-divider pb-2 text-left">Rentang harga</span>
                    <span class="col-span-2 border-b border-divider pb-2">Casco (%)</span>
                    <span class="col-span-2 border-b border-divider pb-2">TLO (%)</span>
                    <span></span>
                </div>
                <div class="grid grid-cols-[minmax(0,1.1fr)_minmax(0,1.1fr)_repeat(4,minmax(0,1fr))_52px] gap-sm border-b border-hairline bg-surface-soft px-md py-2 text-[11px] font-medium uppercase leading-[1.35] tracking-[0.08em] text-muted">
                    <span>Minimum</span>
                    <span>Maksimum</span>
                    <span class="text-center">Passenger</span>
                    <span class="text-center">Commercial</span>
                    <span class="text-center">Passenger</span>
                    <span class="text-center">Commercial</span>
                    <span></span>
                </div>
                @foreach ($cascoBands as $index => $band)
                    <div class="grid grid-cols-[minmax(0,1.1fr)_minmax(0,1.1fr)_repeat(4,minmax(0,1fr))_52px] items-center gap-sm border-b border-divider px-md py-3 last:border-b-0" wire:key="casco-{{ $index }}">
                        @foreach (['band_min', 'band_max', 'passenger_comprehensive', 'commercial_comprehensive', 'passenger_tlo', 'commercial_tlo'] as $field)
                            @if (str_contains($field, 'band_'))
                                <x-ui.money-input wire:model="cascoBands.{{ $index }}.{{ $field }}"
                                                  placeholder="{{ $field === 'band_max' ? 'Tanpa batas' : 'Rp 0' }}"
                                                  class="min-w-0 px-3 text-[13px]" />
                            @else
                                <x-ui.input wire:model="cascoBands.{{ $index }}.{{ $field }}" type="number"
                                            step="0.000001" class="min-w-0 px-3 text-[13px]" />
                            @endif
                        @endforeach
                        <button type="button" wire:click="removeCascoBand({{ $index }})"
                                class="inline-flex min-h-11 items-center justify-center text-[12px] font-medium text-signature-coral">
                            Hapus
                        </button>
                    </div>
                @endforeach
            </div>

            <button type="button" wire:click="addCascoBand"
                    class="mt-sm inline-flex min-h-11 items-center rounded-sm text-[13px] font-medium text-link">
                + Tambah band harga
            </button>
        </x-ui.card>

        <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <x-ui.card title="Loading Usia Kendaraan">
                <div class="grid grid-cols-[76px_minmax(0,1fr)_48px] gap-sm px-1 pb-1 text-caption text-muted">
                    <span>Usia</span>
                    <span>Rate (%)</span>
                    <span></span>
                </div>

                @foreach ($loadingRates as $index => $row)
                    <div class="grid grid-cols-[76px_minmax(0,1fr)_48px] items-center gap-sm border-b border-divider py-2">
                        <x-ui.input wire:model="loadingRates.{{ $index }}.vehicle_age" type="number" min="0"
                                    aria-label="Usia kendaraan {{ $index + 1 }}" class="px-3 text-[13px]" />

                        <x-ui.input wire:model="loadingRates.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100"
                                    aria-label="Rate loading usia kendaraan {{ $index + 1 }}" class="px-3 text-[13px]" />

                        <button type="button" wire:click="removeLoadingRate({{ $index }})"
                                class="inline-flex min-h-11 items-center justify-center rounded-sm text-[12px] font-medium text-muted transition-colors hover:text-signature-coral focus:text-signature-coral"
                                aria-label="Hapus loading usia kendaraan {{ $index + 1 }}">
                            Hapus
                        </button>
                    </div>
                @endforeach

                <button type="button" wire:click="addLoadingRate"
                        class="mt-sm inline-flex min-h-11 items-center rounded-sm text-[13px] font-medium text-link">
                    + Tambah usia
                </button>
            </x-ui.card>

            <div class="flex flex-col gap-lg">
                <x-ui.card title="Perluasan">
                    <div class="grid grid-cols-2 gap-sm md:gap-md">
                        @foreach ($extensionRates as $index => $row)
                            <x-ui.field :label="$extensionLabels[$row['code']] ?? $row['code']">
                                <x-ui.input wire:model="extensionRates.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100" />
                            </x-ui.field>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card title="ACP">
                    <div class="grid grid-cols-1 gap-lg sm:grid-cols-2">
                        <div>
                            <p class="mb-sm text-[13px] font-medium text-ink">Rate dasar</p>
                            <div class="grid grid-cols-2 gap-sm sm:grid-cols-1">
                                @foreach ($acpBaseRates as $index => $row)
                                    <x-ui.field :label="'Tahun '.$row['tenor_years'].' (%)'">
                                        <x-ui.input wire:model="acpBaseRates.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100" />
                                    </x-ui.field>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p class="mb-sm text-[13px] font-medium text-ink">Upping usia</p>
                            <div class="grid grid-cols-2 gap-sm sm:grid-cols-1">
                                @foreach ($acpUppings as $index => $row)
                                    <x-ui.field :label="$row['label'].' (%)'">
                                        <x-ui.input wire:model="acpUppings.{{ $index }}.upping" type="number" step="0.000001" min="0" max="100" />
                                    </x-ui.field>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="TJH" id="insurance-tjh-card">
                    <div class="divide-y divide-divider sm:hidden">
                        @foreach ($tjhTiers as $index => $row)
                            <div class="py-3 first:pt-0 last:pb-0">
                                <div class="mb-sm flex items-center justify-between gap-md">
                                    <p class="text-[13px] font-medium text-ink">Lapisan {{ $index + 1 }}</p>
                                    <button type="button" wire:click="removeTjhTier({{ $index }})"
                                            class="inline-flex min-h-9 items-center rounded-sm px-2 text-[12px] font-medium text-muted transition-colors hover:text-signature-coral focus:text-signature-coral"
                                            aria-label="Hapus TJH {{ $index + 1 }}">
                                        Hapus
                                    </button>
                                </div>

                                <div class="grid grid-cols-[72px_minmax(0,1fr)] gap-sm">
                                    <x-ui.field label="Urutan">
                                        <x-ui.input wire:model="tjhTiers.{{ $index }}.sequence" type="number" min="1"
                                                    aria-label="Lapisan TJH {{ $index + 1 }}" class="px-3 text-[13px]" />
                                    </x-ui.field>

                                    <x-ui.field label="Limit">
                                        <x-ui.money-input wire:model="tjhTiers.{{ $index }}.limit_amount" placeholder="Tanpa batas"
                                                          aria-label="Limit TJH {{ $index + 1 }}" class="px-3 text-[13px]" />
                                    </x-ui.field>

                                    <x-ui.field label="Rate (%)" class="col-span-2">
                                        <x-ui.input wire:model="tjhTiers.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100"
                                                    aria-label="Rate TJH {{ $index + 1 }}" class="px-3 text-[13px]" />
                                    </x-ui.field>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hidden sm:block">
                        <div class="grid grid-cols-[72px_minmax(0,1fr)_112px_44px] gap-sm px-1 pb-1 text-caption text-muted">
                            <span>Lapisan</span>
                            <span>Limit</span>
                            <span>Rate (%)</span>
                            <span></span>
                        </div>

                        <div class="divide-y divide-divider">
                            @foreach ($tjhTiers as $index => $row)
                                <div class="grid grid-cols-[72px_minmax(0,1fr)_112px_44px] items-center gap-sm py-3 first:pt-0 last:pb-0">
                                    <x-ui.input wire:model="tjhTiers.{{ $index }}.sequence" type="number" min="1"
                                                aria-label="Lapisan TJH {{ $index + 1 }}" class="px-3 text-[13px]" />

                                    <x-ui.money-input wire:model="tjhTiers.{{ $index }}.limit_amount" placeholder="Tanpa batas"
                                                      aria-label="Limit TJH {{ $index + 1 }}" class="px-3 text-[13px]" />

                                    <x-ui.input wire:model="tjhTiers.{{ $index }}.rate" type="number" step="0.000001" min="0" max="100"
                                                aria-label="Rate TJH {{ $index + 1 }}" class="px-3 text-[13px]" />

                                    <button type="button" wire:click="removeTjhTier({{ $index }})"
                                            class="inline-flex min-h-11 items-center justify-center rounded-sm text-[12px] font-medium text-muted transition-colors hover:text-signature-coral focus:text-signature-coral"
                                            aria-label="Hapus TJH {{ $index + 1 }}">
                                        Hapus
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="button" wire:click="addTjhTier"
                            class="mt-sm inline-flex min-h-11 items-center rounded-sm text-[13px] font-medium text-link">
                        + Tambah lapisan
                    </button>
                </x-ui.card>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                Periksa kembali seluruh field. Konfigurasi belum disimpan.
            </div>
        @endif

        <div class="flex flex-col gap-sm sm:flex-row sm:items-center sm:justify-end">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled" class="w-full sm:w-auto">Simpan Insurance</x-ui.button>
            <span wire:loading class="text-helper text-muted">Memeriksa kelengkapan seluruh konfigurasi...</span>
        </div>
    </form>
</x-admin.configuration-shell>
