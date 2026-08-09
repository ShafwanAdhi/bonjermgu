<x-admin.configuration-shell title="Nilai Default Simulasi" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif
        @error('configuration')
            <div class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
        @enderror

        <x-ui.card title="Nilai Default Simulasi" note="Seluruh key wajib lengkap; wilayah aktif hanya dapat dipilih setelah matriks Insurance lengkap.">
            <div class="grid grid-cols-1 gap-md md:grid-cols-2">
                @foreach ($definitions as $key => $definition)
                    <x-ui.field :label="$definition['label']" required :error="$errors->first('settings.'.$key)">
                        @if ($definition['type'] === 'boolean')
                            <x-ui.select wire:model="settings.{{ $key }}" :invalid="$errors->has('settings.'.$key)">
                                <option value="true">Ya</option>
                                <option value="false">Tidak</option>
                            </x-ui.select>
                        @elseif ($definition['type'] === 'variant')
                            <x-ui.select wire:model="settings.{{ $key }}" :invalid="$errors->has('settings.'.$key)">
                                <option>Batas Bawah</option>
                                <option>Batas Atas</option>
                            </x-ui.select>
                        @elseif ($definition['type'] === 'zone')
                            <x-ui.select wire:model="settings.{{ $key }}" :invalid="$errors->has('settings.'.$key)">
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone }}">{{ $zone }}</option>
                                @endforeach
                            </x-ui.select>
                        @elseif ($definition['type'] === 'money')
                            <x-ui.money-input wire:model="settings.{{ $key }}" placeholder="Rp 0"
                                              :invalid="$errors->has('settings.'.$key)" />
                        @else
                            <x-ui.input wire:model="settings.{{ $key }}" type="number"
                                        min="{{ $definition['type'] === 'positive_integer' ? 1 : 0 }}"
                                        :invalid="$errors->has('settings.'.$key)" />
                        @endif
                    </x-ui.field>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.callout>
            Penyimpanan ditolak bila key hilang, tipe nilainya salah, TJH tidak mengikuti kelipatan,
            atau wilayah dan varian Insurance yang dipilih belum mempunyai seluruh band.
        </x-ui.callout>

        <div class="flex items-center gap-sm">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Nilai Default</x-ui.button>
            <span wire:loading class="text-helper text-muted">Memvalidasi dampak pada simulasi berikutnya…</span>
        </div>
    </form>
</x-admin.configuration-shell>
