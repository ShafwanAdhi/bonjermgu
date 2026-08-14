<x-admin.master-shell title="Domisili dan Kelompok Usia" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif
        @error('configuration')
            <div role="alert" class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
        @enderror

        <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-2">
            <x-ui.card title="Domisili" note="Urutan menentukan tampilan dropdown simulasi.">
                @foreach ($domiciles as $index => $row)
                    <div class="grid grid-cols-[28px_1fr_auto] items-center gap-sm border-b border-divider py-2" wire:key="domicile-{{ $row['id'] ?? 'new-'.$index }}">
                        <span class="text-helper tabular-nums text-muted">{{ $index + 1 }}</span>
                        <x-ui.input wire:model="domiciles.{{ $index }}.name" :invalid="$errors->has('domiciles.'.$index.'.name')" />
                        <div class="flex gap-1 text-[12px]">
                            <button type="button" wire:click="moveDomicile({{ $index }}, -1)" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-link" aria-label="Naikkan domisili {{ $index + 1 }}">↑</button>
                            <button type="button" wire:click="moveDomicile({{ $index }}, 1)" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-link" aria-label="Turunkan domisili {{ $index + 1 }}">↓</button>
                            <button type="button" wire:click="removeDomicile({{ $index }})" class="text-signature-coral">Hapus</button>
                        </div>
                    </div>
                @endforeach
                <button type="button" wire:click="addDomicile" class="mt-sm text-[13px] font-medium text-link">+ Tambah Domisili</button>
            </x-ui.card>

            <x-ui.card title="Kelompok Usia" note="Setiap kelompok wajib memiliki upping ACP agar engine tetap lengkap.">
                @foreach ($ageGroups as $index => $row)
                    <div class="grid grid-cols-[28px_1fr_120px_auto] items-center gap-sm border-b border-divider py-2" wire:key="age-{{ $row['id'] ?? 'new-'.$index }}">
                        <span class="text-helper tabular-nums text-muted">{{ $index + 1 }}</span>
                        <x-ui.input wire:model="ageGroups.{{ $index }}.label" placeholder="Label usia" />
                        <x-ui.input wire:model="ageGroups.{{ $index }}.upping" type="number" step="0.000001" min="0" max="100" placeholder="Upping %" />
                        <div class="flex gap-1 text-[12px]">
                            <button type="button" wire:click="moveAgeGroup({{ $index }}, -1)" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-link" aria-label="Naikkan kelompok usia {{ $index + 1 }}">↑</button>
                            <button type="button" wire:click="moveAgeGroup({{ $index }}, 1)" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-link" aria-label="Turunkan kelompok usia {{ $index + 1 }}">↓</button>
                            <button type="button" wire:click="removeAgeGroup({{ $index }})" class="text-signature-coral">Hapus</button>
                        </div>
                    </div>
                @endforeach
                <button type="button" wire:click="addAgeGroup" class="mt-sm text-[13px] font-medium text-link">+ Tambah Kelompok Usia</button>
            </x-ui.card>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                Data belum disimpan. Nama harus unik dan setiap kelompok usia wajib memiliki upping ACP.
            </div>
        @endif
        <div class="flex items-center gap-sm">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Master Lookup</x-ui.button>
            <span wire:loading class="text-helper text-muted">Memvalidasi kelengkapan ACP…</span>
        </div>
    </form>
</x-admin.master-shell>
