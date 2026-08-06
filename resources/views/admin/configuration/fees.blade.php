<x-admin.configuration-shell title="Biaya dan Down Payment" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif
        @error('configuration')
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-2">
            <x-ui.card title="Fiducia Fee" note="Band harus dimulai dari Rp 0, tanpa celah, dan band terakhir tanpa batas.">
                @foreach ($fiduciaTiers as $index => $row)
                    <div class="grid grid-cols-[1fr_1fr_1fr_auto] gap-sm border-b border-divider py-2">
                        <x-ui.input wire:model="fiduciaTiers.{{ $index }}.min_amount" type="number" min="0" placeholder="Min" />
                        <x-ui.input wire:model="fiduciaTiers.{{ $index }}.max_amount" type="number" min="0" placeholder="Tanpa batas" />
                        <x-ui.input wire:model="fiduciaTiers.{{ $index }}.fee" type="number" min="0" placeholder="Biaya" />
                        <button type="button" wire:click="removeFiduciaTier({{ $index }})" class="text-signature-coral">Hapus</button>
                    </div>
                @endforeach
                <button type="button" wire:click="addFiduciaTier" class="mt-sm text-[13px] font-medium text-link">+ Tambah band Fiducia</button>
            </x-ui.card>

            <x-ui.card title="Sum Insured per Tahun" note="Lima tahun wajib lengkap.">
                @foreach ($sumInsured as $index => $row)
                    <div class="grid grid-cols-[100px_1fr_auto] gap-sm border-b border-divider py-2">
                        <x-ui.input wire:model="sumInsured.{{ $index }}.year_index" type="number" min="1" max="5" />
                        <x-ui.input wire:model="sumInsured.{{ $index }}.percentage" type="number" step="0.0001" min="0" max="100" />
                        <button type="button" wire:click="removeSumInsuredYear({{ $index }})" class="text-signature-coral">Hapus</button>
                    </div>
                @endforeach
                <button type="button" wire:click="addSumInsuredYear" class="mt-sm text-[13px] font-medium text-link">+ Tambah tahun</button>
            </x-ui.card>

            <x-ui.card title="Ketentuan Net DP per Produk" note="Nilai ditampilkan sebagai persen.">
                @foreach (array_slice($settingLabels, 0, 4, true) as $key => $label)
                    <x-ui.field :label="$label.' (%)'" class="mb-sm" :error="$errors->first('settings.'.$key)">
                        <x-ui.input wire:model="settings.{{ $key }}" type="number" step="0.0001" min="0" max="100"
                                    :invalid="$errors->has('settings.'.$key)" />
                    </x-ui.field>
                @endforeach
            </x-ui.card>

            <x-ui.card title="Persentase Refund" note="Seluruh komponen refund UCF harus lengkap.">
                @foreach (array_slice($settingLabels, 4, null, true) as $key => $label)
                    <x-ui.field :label="$label.' (%)'" class="mb-sm" :error="$errors->first('settings.'.$key)">
                        <x-ui.input wire:model="settings.{{ $key }}" type="number" step="0.0001" min="0" max="100"
                                    :invalid="$errors->has('settings.'.$key)" />
                    </x-ui.field>
                @endforeach
            </x-ui.card>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                Konfigurasi belum disimpan. Perbaiki field dan pastikan band tidak berlubang atau tumpang tindih.
            </div>
        @endif
        <div class="flex items-center gap-sm">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Fee, DP, dan Refund</x-ui.button>
            <span wire:loading class="text-helper text-muted">Memvalidasi konfigurasi…</span>
        </div>
    </form>
</x-admin.configuration-shell>
