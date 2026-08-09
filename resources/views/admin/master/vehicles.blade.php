<x-admin.master-shell title="Master Kendaraan" :last-change="$this->lastChange">
    @if (session('admin_success'))
        <x-ui.callout class="mb-md">{{ session('admin_success') }}</x-ui.callout>
    @endif
    @error('master')
        <div class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror

    <div class="mb-lg">
        <x-ui.input wire:model.live.debounce.400ms="search" type="search"
                    placeholder="Cari merk, type, atau model kendaraan…" />
        @if ($searchResults)
            <div class="mt-sm overflow-hidden rounded-md border border-hairline bg-canvas">
                @forelse ($searchResults as $result)
                    <button type="button" wire:click="selectSearchResult({{ $result->id }})"
                            class="flex w-full items-center gap-sm border-b border-divider px-md py-2 text-left text-[13px] hover:bg-surface-soft">
                        <span class="font-medium text-ink">{{ $result->type->brand->name }} {{ $result->type->name }} {{ $result->name }}</span>
                        <span class="ml-auto text-muted">{{ $result->type->brand->usage->name }}</span>
                    </button>
                @empty
                    <p class="px-md py-3 text-body-md text-muted">Tidak ada model yang cocok.</p>
                @endforelse
                <div class="p-sm">{{ $searchResults->links() }}</div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 items-start gap-md xl:grid-cols-4">
        <x-ui.card title="Penggunaan Unit" note="Nilai domain tetap Passenger dan Commercial.">
            @foreach ($this->usages as $usage)
                <button type="button" wire:click="selectUsage({{ $usage->id }})"
                        class="block w-full border-b border-divider px-2 py-2 text-left text-[13px] {{ $usageId === $usage->id ? 'bg-surface-soft font-medium text-ink' : 'text-body' }}">
                    {{ $usage->name }}
                </button>
            @endforeach
        </x-ui.card>

        <x-ui.card title="Merk" note="Klasifikasi asal disimpan pada level ini.">
            <div class="max-h-72 overflow-y-auto">
                @foreach ($this->brands as $brand)
                    <button type="button" wire:click="selectBrand({{ $brand->id }})"
                            class="block w-full border-b border-divider px-2 py-2 text-left text-[13px] {{ $brandId === $brand->id ? 'bg-surface-soft font-medium' : '' }}">
                        {{ $brand->name }} · {{ $brand->origin }}
                    </button>
                @endforeach
            </div>
            <button type="button" wire:click="newBrand" class="mt-sm text-[13px] font-medium text-link">+ Merk</button>
            <div class="mt-md flex flex-col gap-sm">
                <x-ui.input wire:model="brandForm.name" placeholder="Nama merk" />
                <x-ui.select wire:model="brandForm.origin"><option>Japan</option><option>Non Japan</option></x-ui.select>
                <div class="flex gap-sm"><x-ui.button type="button" size="md" wire:click="saveBrand">Simpan</x-ui.button>
                    @if ($brandId)<button type="button" wire:click="deleteBrand" class="text-[13px] text-signature-coral">Hapus</button>@endif</div>
            </div>
        </x-ui.card>

        <x-ui.card title="Type Kendaraan">
            <div class="max-h-72 overflow-y-auto">
                @foreach ($this->types as $type)
                    <button type="button" wire:click="selectType({{ $type->id }})"
                            class="block w-full border-b border-divider px-2 py-2 text-left text-[13px] {{ $typeId === $type->id ? 'bg-surface-soft font-medium' : '' }}">{{ $type->name }}</button>
                @endforeach
            </div>
            @if ($brandId)
                <button type="button" wire:click="newType" class="mt-sm text-[13px] font-medium text-link">+ Type</button>
                <div class="mt-md flex flex-col gap-sm">
                    <x-ui.input wire:model="typeForm.name" placeholder="Nama type" />
                    <div class="flex gap-sm"><x-ui.button type="button" size="md" wire:click="saveType">Simpan</x-ui.button>
                        @if ($typeId)<button type="button" wire:click="deleteType" class="text-[13px] text-signature-coral">Hapus</button>@endif</div>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Model Kendaraan" note="Hanya model dari Type terpilih yang dimuat.">
            <div class="max-h-72 overflow-y-auto">
                @foreach ($this->models as $model)
                    <button type="button" wire:click="selectModel({{ $model->id }})"
                            class="block w-full border-b border-divider px-2 py-2 text-left text-[13px] {{ $modelId === $model->id ? 'bg-surface-soft font-medium' : '' }}">{{ $model->name }}</button>
                @endforeach
            </div>
            @if ($typeId)
                <button type="button" wire:click="newModel" class="mt-sm text-[13px] font-medium text-link">+ Model</button>
                <div class="mt-md flex flex-col gap-sm">
                    <x-ui.input wire:model="modelForm.name" placeholder="Nama model" />
                    <div class="flex gap-sm"><x-ui.button type="button" size="md" wire:click="saveModel">Simpan</x-ui.button>
                        @if ($modelId)<button type="button" wire:click="deleteModel" class="text-[13px] text-signature-coral">Hapus</button>@endif</div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Harga per Tahun — PHPM" class="mt-lg max-w-[820px]"
               note="Seluruh tahun untuk satu model ditampilkan; tahun lain dan model lain tidak dimuat.">
        @if ($modelId)
            @foreach ($prices as $index => $row)
                <div class="grid grid-cols-[120px_1fr_auto] gap-sm border-b border-divider py-2" wire:key="price-{{ $row['id'] ?? 'new-'.$index }}">
                    <x-ui.input wire:model="prices.{{ $index }}.year" type="number" min="1" max="32767" placeholder="Tahun" />
                    <x-ui.money-input wire:model="prices.{{ $index }}.price" placeholder="Rp 250.000.000" />
                    <button type="button" wire:click="removePrice({{ $index }})" class="text-[13px] text-signature-coral">Hapus</button>
                </div>
            @endforeach
            <div class="mt-md flex gap-sm">
                <x-ui.button type="button" variant="secondary" size="md" wire:click="addPrice">Tambah Tahun</x-ui.button>
                <x-ui.button type="button" size="md" wire:click="savePrices">Simpan Harga</x-ui.button>
            </div>
        @else
            <p class="text-body-md text-muted">Pilih satu model untuk mengelola harga PHPM.</p>
        @endif
    </x-ui.card>

    <x-ui.callout class="mt-lg inline-flex">
        Harga PHPM disimpan mentah per tahun. Harga OTR tetap dihitung oleh engine dan tidak disimpan sebagai master.
    </x-ui.callout>
</x-admin.master-shell>
