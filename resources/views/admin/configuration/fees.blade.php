<x-admin.configuration-shell title="Biaya dan Down Payment" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif
        @error('configuration')
            <div role="alert" class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-2">
            <div class="flex flex-col gap-lg">
                <x-ui.card title="Fiducia Fee">
                    <div class="flex flex-col gap-sm">
                        @foreach ($fiduciaTiers as $index => $row)
                            <div class="rounded-md border border-hairline bg-surface-soft p-md" wire:key="fiducia-{{ $index }}">
                                <div class="mb-md flex items-center justify-between gap-md">
                                    <p class="text-[13px] font-medium text-ink">Band #{{ $index + 1 }}</p>
                                    <button type="button" wire:click="removeFiduciaTier({{ $index }})"
                                            class="inline-flex min-h-10 items-center rounded-sm px-2 text-[13px] font-medium text-signature-coral">
                                        Hapus
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-sm sm:grid-cols-3">
                                    <x-ui.field label="Batas Bawah">
                                        <x-ui.money-input wire:model="fiduciaTiers.{{ $index }}.min_amount" placeholder="Rp 0" />
                                    </x-ui.field>

                                    <x-ui.field label="Batas Atas">
                                        <x-ui.money-input wire:model="fiduciaTiers.{{ $index }}.max_amount" placeholder="Tanpa batas" />
                                    </x-ui.field>

                                    <x-ui.field label="Fee">
                                        <x-ui.money-input wire:model="fiduciaTiers.{{ $index }}.fee" placeholder="Rp 500.000" />
                                    </x-ui.field>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" wire:click="addFiduciaTier"
                            class="mt-sm inline-flex min-h-11 items-center rounded-sm text-[13px] font-medium text-link">
                        + Tambah band Fiducia
                    </button>
                </x-ui.card>

                <x-ui.card title="Ketentuan Net DP per Produk">
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        @foreach ($netDpSettingLabels as $key => $label)
                            <x-ui.field :label="$label.' (%)'" :error="$errors->first('settings.'.$key)">
                                <x-ui.input wire:model="settings.{{ $key }}" type="number" step="0.0001" min="0" max="100"
                                            :invalid="$errors->has('settings.'.$key)" />
                            </x-ui.field>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div class="flex flex-col gap-lg">
                <x-ui.card title="Sum Insured per Tahun">
                    <div class="flex flex-col gap-sm">
                        @foreach ($sumInsured as $index => $row)
                            <div class="rounded-md border border-hairline bg-surface-soft p-md" wire:key="sum-insured-{{ $index }}">
                                <div class="mb-md flex items-center justify-between gap-md">
                                    <p class="text-[13px] font-medium text-ink">Tahun #{{ $index + 1 }}</p>
                                    <button type="button" wire:click="removeSumInsuredYear({{ $index }})"
                                            class="inline-flex min-h-10 items-center rounded-sm px-2 text-[13px] font-medium text-signature-coral">
                                        Hapus
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-sm sm:grid-cols-[120px_minmax(0,1fr)]">
                                    <x-ui.field label="Tahun">
                                        <x-ui.input wire:model="sumInsured.{{ $index }}.year_index" type="number" min="1" max="5" />
                                    </x-ui.field>

                                    <x-ui.field label="Persentase (%)">
                                        <x-ui.input wire:model="sumInsured.{{ $index }}.percentage" type="number" step="0.0001" min="0" max="100" />
                                    </x-ui.field>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" wire:click="addSumInsuredYear"
                            class="mt-sm inline-flex min-h-11 items-center rounded-sm text-[13px] font-medium text-link">
                        + Tambah tahun
                    </button>
                </x-ui.card>

                <x-ui.card title="Persentase Refund">
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        @foreach ($refundSettingLabels as $key => $label)
                            <x-ui.field :label="$label.' (%)'" :error="$errors->first('settings.'.$key)">
                                <x-ui.input wire:model="settings.{{ $key }}" type="number" step="0.0001" min="0" max="100"
                                            :invalid="$errors->has('settings.'.$key)" />
                            </x-ui.field>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                Konfigurasi belum disimpan. Perbaiki field dan pastikan band tidak berlubang atau tumpang tindih.
            </div>
        @endif

        <div class="flex flex-col gap-sm sm:flex-row sm:items-center">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled" class="w-full sm:w-auto">
                Simpan Fee, DP, dan Refund
            </x-ui.button>
            <span wire:loading class="text-helper text-muted">Memvalidasi konfigurasi...</span>
        </div>
    </form>
</x-admin.configuration-shell>
