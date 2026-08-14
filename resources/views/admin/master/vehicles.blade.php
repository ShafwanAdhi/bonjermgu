<x-admin.master-shell title="Master Kendaraan" :last-change="$this->lastChange">
    @php
        $selectedUsage = $this->usages->firstWhere('id', $usageId);
        $selectedBrand = $this->brands->firstWhere('id', $brandId);
        $selectedType = $this->types->firstWhere('id', $typeId);
        $selectedModel = $this->models->firstWhere('id', $modelId);
        $editorTabs = [
            'brand' => 'Merk',
            'type' => 'Type',
            'model' => 'Model',
            'prices' => 'Harga per Tahun',
        ];
    @endphp

    @if (session('admin_success'))
        <x-ui.callout class="mb-md">{{ session('admin_success') }}</x-ui.callout>
    @endif
    @error('master')
        <div role="alert" class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror

    <section class="mb-lg rounded-lg border border-hairline bg-surface-soft p-lg md:p-xl">
        <div class="grid gap-lg lg:grid-cols-[minmax(0,0.9fr)_minmax(320px,0.5fr)] lg:items-end">
            <div>
                <p class="mb-1.5 text-eyebrow uppercase text-muted">Pencarian PHPM</p>
                <x-ui.input wire:model.live.debounce.400ms="search" type="search"
                            placeholder="Cari merk, type, atau model kendaraan..." />
            </div>

            <div class="flex flex-wrap gap-2 text-[12px] leading-none">
                <span class="rounded-xs border border-hairline bg-canvas px-2.5 py-2 text-muted">{{ $selectedUsage?->name ?? 'Pilih penggunaan' }}</span>
                <span class="rounded-xs border border-hairline bg-canvas px-2.5 py-2 text-muted">{{ $selectedBrand?->name ?? 'Pilih merk' }}</span>
                <span class="rounded-xs border border-hairline bg-canvas px-2.5 py-2 text-muted">{{ $selectedType?->name ?? 'Pilih type' }}</span>
                <span class="rounded-xs border border-hairline bg-canvas px-2.5 py-2 text-muted">{{ $selectedModel?->name ?? 'Pilih model' }}</span>
            </div>
        </div>

        @if ($searchResults)
            <div class="mt-md overflow-hidden rounded-md border border-hairline bg-canvas">
                @forelse ($searchResults as $result)
                    <button type="button" wire:click="selectSearchResult({{ $result->id }})"
                            data-master-row
                            class="flex w-full items-center gap-sm border-b border-divider px-md py-3 text-left text-[13px]">
                        <span class="min-w-0 font-medium text-ink">{{ $result->type->brand->name }} {{ $result->type->name }} {{ $result->name }}</span>
                        <span class="ml-auto shrink-0 text-muted">{{ $result->type->brand->usage->name }}</span>
                    </button>
                @empty
                    <p class="px-md py-3 text-body-md text-muted">Tidak ada model yang cocok.</p>
                @endforelse
                <div class="p-sm">{{ $searchResults->links() }}</div>
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 items-start gap-lg xl:grid-cols-[minmax(0,1.12fr)_minmax(360px,0.88fr)]" data-master-page-grid>
        <section class="rounded-lg border border-hairline bg-canvas p-lg md:p-xl">
            <div class="mb-lg flex flex-col gap-1.5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-title-sm text-ink">Alur Master PHPM</p>
                    <p class="mt-1 text-[13px] leading-[1.5] text-muted">Pilih berurutan dari penggunaan unit sampai model kendaraan.</p>
                </div>
                <span class="text-helper text-muted">{{ $this->models->count() }} model pada type aktif</span>
            </div>

            <div class="grid gap-md lg:grid-cols-2">
                <div class="rounded-md border border-hairline bg-surface-soft p-md">
                    <div class="mb-sm flex items-center justify-between gap-sm">
                        <p class="text-label-md text-ink">Penggunaan Unit</p>
                        <span class="text-helper text-muted">{{ $this->usages->count() }} opsi</span>
                    </div>
                    <div class="grid gap-2">
                        @foreach ($this->usages as $usage)
                            <button type="button" wire:click="selectUsage({{ $usage->id }})"
                                    data-master-row
                                    @class([
                                        'rounded-sm border px-3 py-2.5 text-left text-[13px]',
                                        'border-primary bg-canvas font-medium text-ink' => $usageId === $usage->id,
                                        'border-transparent bg-canvas/70 text-body' => $usageId !== $usage->id,
                                    ])>
                                {{ $usage->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-md border border-hairline bg-canvas p-md">
                    <div class="mb-sm flex items-center justify-between gap-sm">
                        <p class="text-label-md text-ink">Merk</p>
                        <button type="button" wire:click="newBrand" class="text-[13px] font-medium text-link">+ Merk</button>
                    </div>
                    <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                        @forelse ($this->brands as $brand)
                            <button type="button" wire:click="selectBrand({{ $brand->id }})"
                                    data-master-row
                                    @class([
                                        'flex w-full items-center gap-sm rounded-sm border px-3 py-2.5 text-left text-[13px]',
                                        'border-primary bg-surface-soft font-medium text-ink' => $brandId === $brand->id,
                                        'border-hairline bg-canvas text-body' => $brandId !== $brand->id,
                                    ])>
                                <span class="min-w-0 flex-1">{{ $brand->name }}</span>
                                <span class="shrink-0 text-helper text-muted">{{ $brand->origin }}</span>
                            </button>
                        @empty
                            <p class="rounded-sm border border-dashed border-hairline px-3 py-6 text-center text-body-md text-muted">Belum ada merk pada penggunaan ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-md border border-hairline bg-canvas p-md" data-master-active-step="{{ $brandId ? 'open' : 'closed' }}">
                    <div class="mb-sm flex items-center justify-between gap-sm">
                        <p class="text-label-md text-ink">Type Kendaraan</p>
                        @if ($brandId)
                            <button type="button" wire:click="newType" class="text-[13px] font-medium text-link">+ Type</button>
                        @endif
                    </div>
                    @if ($brandId)
                        <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                            @forelse ($this->types as $type)
                                <button type="button" wire:click="selectType({{ $type->id }})"
                                        data-master-row
                                        @class([
                                            'block w-full rounded-sm border px-3 py-2.5 text-left text-[13px]',
                                            'border-primary bg-surface-soft font-medium text-ink' => $typeId === $type->id,
                                            'border-hairline bg-canvas text-body' => $typeId !== $type->id,
                                        ])>
                                    {{ $type->name }}
                                </button>
                            @empty
                                <p class="rounded-sm border border-dashed border-hairline px-3 py-6 text-center text-body-md text-muted">Belum ada type untuk merk ini.</p>
                            @endforelse
                        </div>
                    @else
                        <p class="rounded-sm border border-dashed border-hairline px-3 py-6 text-center text-body-md text-muted">Pilih merk untuk membuka type kendaraan.</p>
                    @endif
                </div>

                <div data-master-step="model"
                     data-master-attention="{{ $attentionTarget === 'model' ? 'true' : 'false' }}"
                     @class([
                         'rounded-md border bg-canvas p-md transition-colors duration-200',
                         'border-signature-yellow bg-signature-cream/45' => $attentionTarget === 'model',
                         'border-hairline' => $attentionTarget !== 'model',
                     ])>
                    <div class="mb-sm flex items-center justify-between gap-sm">
                        <p class="text-label-md text-ink">Model Kendaraan</p>
                        @if ($typeId)
                            <button type="button" wire:click="newModel" class="text-[13px] font-medium text-link">+ Model</button>
                        @endif
                    </div>
                    @if ($typeId)
                        <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                            @forelse ($this->models as $model)
                                <button type="button" wire:click="selectModel({{ $model->id }})"
                                        data-master-row
                                        @class([
                                            'block w-full rounded-sm border px-3 py-2.5 text-left text-[13px]',
                                            'border-primary bg-surface-soft font-medium text-ink' => $modelId === $model->id,
                                            'border-hairline bg-canvas text-body' => $modelId !== $model->id,
                                        ])>
                                    {{ $model->name }}
                                </button>
                            @empty
                                <p class="rounded-sm border border-dashed border-hairline px-3 py-6 text-center text-body-md text-muted">Belum ada model untuk type ini.</p>
                            @endforelse
                        </div>
                    @else
                        <p class="rounded-sm border border-dashed border-hairline px-3 py-6 text-center text-body-md text-muted">Pilih type untuk membuka model kendaraan.</p>
                    @endif
                </div>
            </div>
        </section>

        <aside class="xl:sticky xl:top-6">
            <div class="mb-sm flex gap-2 overflow-x-auto pb-1">
                @foreach ($editorTabs as $key => $label)
                    <button type="button" wire:click="showEditor('{{ $key }}')"
                            @class([
                                'shrink-0 rounded-sm border px-3 py-2 text-[13px] font-medium transition-colors',
                                'border-primary bg-primary text-on-primary' => $activeEditor === $key,
                                'border-hairline bg-canvas text-muted' => $activeEditor !== $key,
                            ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div data-master-active-panel wire:key="vehicle-editor-{{ $activeEditor }}-{{ $brandId }}-{{ $typeId }}-{{ $modelId }}">
                @if ($activeEditor === 'brand')
                    <form wire:submit="saveBrand">
                        <x-ui.card :title="$brandId ? 'Ubah Merk' : 'Tambah Merk'" meta="Level 2">
                            <div class="flex flex-col gap-md">
                                <x-ui.field label="Nama Merk" required :error="$errors->first('brandForm.name')">
                                    <x-ui.input wire:model="brandForm.name" placeholder="Contoh: HONDA" />
                                </x-ui.field>
                                <x-ui.field label="Klasifikasi Asal" required :error="$errors->first('brandForm.origin')">
                                    <x-ui.select wire:model="brandForm.origin">
                                        <option>Japan</option>
                                        <option>Non Japan</option>
                                    </x-ui.select>
                                </x-ui.field>
                                <div class="flex flex-col gap-sm sm:flex-row sm:items-center">
                                    <x-ui.button type="submit" size="md" class="w-full sm:w-auto">Simpan Merk</x-ui.button>
                                    @if ($brandId)
                                        <button type="button" wire:click="deleteBrand" class="min-h-11 rounded-sm px-3 text-[13px] font-medium text-signature-coral">Hapus</button>
                                    @endif
                                </div>
                            </div>
                        </x-ui.card>
                    </form>
                @elseif ($activeEditor === 'type')
                    <form wire:submit="saveType">
                        <x-ui.card :title="$typeId ? 'Ubah Type Kendaraan' : 'Tambah Type Kendaraan'" meta="Level 3">
                            @if ($brandId)
                                <div class="flex flex-col gap-md">
                                    <x-ui.field label="Merk Aktif">
                                        <x-ui.input value="{{ $selectedBrand?->name }}" disabled />
                                    </x-ui.field>
                                    <x-ui.field label="Nama Type" required :error="$errors->first('typeForm.name')">
                                        <x-ui.input wire:model="typeForm.name" placeholder="Contoh: BRIO" />
                                    </x-ui.field>
                                    <div class="flex flex-col gap-sm sm:flex-row sm:items-center">
                                        <x-ui.button type="submit" size="md" class="w-full sm:w-auto">Simpan Type</x-ui.button>
                                        @if ($typeId)
                                            <button type="button" wire:click="deleteType" class="min-h-11 rounded-sm px-3 text-[13px] font-medium text-signature-coral">Hapus</button>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-body-md text-muted">Pilih merk terlebih dahulu.</p>
                            @endif
                        </x-ui.card>
                    </form>
                @elseif ($activeEditor === 'model')
                    <form wire:submit="saveModel">
                        <x-ui.card :title="$modelId ? 'Ubah Model Kendaraan' : 'Tambah Model Kendaraan'" meta="Level 4">
                            @if ($typeId)
                                <div class="flex flex-col gap-md">
                                    <x-ui.field label="Type Aktif">
                                        <x-ui.input value="{{ $selectedType?->name }}" disabled />
                                    </x-ui.field>
                                    <x-ui.field label="Nama Model" required :error="$errors->first('modelForm.name')">
                                        <x-ui.input wire:model="modelForm.name" placeholder="Contoh: ALL NEW BRIO RS CVT" />
                                    </x-ui.field>
                                    <div class="flex flex-col gap-sm sm:flex-row sm:items-center">
                                        <x-ui.button type="submit" size="md" class="w-full sm:w-auto">Simpan Model</x-ui.button>
                                        @if ($modelId)
                                            <button type="button" wire:click="deleteModel" class="min-h-11 rounded-sm px-3 text-[13px] font-medium text-signature-coral">Hapus</button>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-body-md text-muted">Pilih type terlebih dahulu.</p>
                            @endif
                        </x-ui.card>
                    </form>
                @else
                    <x-ui.card title="Harga per Tahun — PHPM" meta="{{ $selectedModel?->name ?? 'Pilih model' }}">
                        @if ($modelId)
                            <div class="flex flex-col gap-sm">
                                @foreach ($prices as $index => $row)
                                    <div class="grid grid-cols-1 gap-sm rounded-md border border-hairline bg-surface-soft p-sm sm:grid-cols-[92px_minmax(0,1fr)_auto]" wire:key="price-{{ $row['id'] ?? 'new-'.$index }}">
                                        <x-ui.input wire:model="prices.{{ $index }}.year" type="number" min="1" max="32767" placeholder="Tahun" />
                                        <x-ui.money-input wire:model="prices.{{ $index }}.price" placeholder="Rp 250.000.000" />
                                        <button type="button" wire:click="removePrice({{ $index }})" class="min-h-11 rounded-sm px-2 text-[13px] font-medium text-signature-coral">Hapus</button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-md flex flex-col gap-sm sm:flex-row sm:items-center">
                                <x-ui.button type="button" variant="secondary" size="md" wire:click="addPrice" class="w-full sm:w-auto">Tambah Tahun</x-ui.button>
                                <x-ui.button type="button" size="md" wire:click="savePrices" class="w-full sm:w-auto">Simpan Harga</x-ui.button>
                            </div>
                        @else
                            <p class="text-body-md text-muted">Pilih satu model untuk mengelola harga PHPM.</p>
                        @endif
                    </x-ui.card>
                @endif
            </div>

            <x-ui.callout class="mt-md">
                Harga PHPM disimpan mentah per tahun. Harga OTR tetap dihitung oleh engine dan tidak disimpan sebagai master.
            </x-ui.callout>
        </aside>
    </div>
</x-admin.master-shell>
