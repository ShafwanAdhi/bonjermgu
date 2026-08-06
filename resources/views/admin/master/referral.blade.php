<x-admin.master-shell title="Master Referral" :last-change="$this->lastChange">
    <div class="mb-md flex flex-wrap items-center gap-sm">
        <span class="text-body-md text-muted">Kategori memuat segment dan tier yang membentuk nama Product.</span>
        <x-ui.button type="button" size="md" class="ml-auto" wire:click="newCategory">Tambah Kategori</x-ui.button>
    </div>

    @if (session('admin_success'))
        <x-ui.callout class="mb-md">{{ session('admin_success') }}</x-ui.callout>
    @endif
    @error('master')
        <div class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror
    @error('configuration')
        <div class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror

    <div class="overflow-hidden rounded-lg border border-hairline">
        @forelse ($categories as $category)
            <div wire:key="category-{{ $category->id }}">
                <div class="flex flex-wrap items-center gap-sm border-b border-divider bg-surface-soft px-5 py-3.5">
                    <span class="text-[15px] font-medium text-ink">{{ $category->name }}</span>
                    <span class="rounded-xs bg-surface-strong px-2 py-0.5 text-[11px] text-muted">{{ $category->code }}</span>
                    <span class="rounded-xs bg-surface-strong px-2 py-0.5 text-[11px] text-muted">{{ $category->segment }}</span>
                    <span class="rounded-xs bg-signature-cream px-2 py-0.5 text-[11px] text-muted">{{ $category->tier }}</span>
                    <span class="rounded-xs bg-surface-strong px-2 py-0.5 text-[11px] text-muted">
                        {{ collect($category->allowedVehicleUsages())->map(fn ($usage) => $usage->value)->join(', ') }}
                    </span>
                    <span class="ml-auto text-helper text-muted">{{ $category->accounts_count }} akun</span>
                    <button type="button" wire:click="editCategory({{ $category->id }})" class="text-[13px] font-medium text-link">Ubah</button>
                    <button type="button" wire:click="newSubCategory({{ $category->id }})" class="text-[13px] font-medium text-link">+ Sub</button>
                </div>
                @foreach ($category->subCategories as $sub)
                    <div class="border-b border-divider py-2.5 pl-11 pr-5">
                        <div class="flex flex-wrap items-center gap-sm">
                            <span class="text-body-md text-body">{{ $sub->name }}</span>
                            <span class="ml-auto text-helper text-muted">{{ $sub->institutions->count() }} instansi</span>
                            <button type="button" wire:click="editSubCategory({{ $sub->id }})" class="text-[13px] text-link">Ubah</button>
                            <button type="button" wire:click="newInstitution({{ $sub->id }})" class="text-[13px] text-link">+ Instansi</button>
                        </div>
                        @if ($sub->institutions->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($sub->institutions as $institution)
                                    <button type="button" wire:click="editInstitution({{ $institution->id }})"
                                            class="rounded-xs border border-hairline bg-canvas px-2 py-1 text-[11px] text-muted">{{ $institution->name }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <p class="px-md py-lg text-body-md text-muted">Belum ada kategori Referral.</p>
        @endforelse
    </div>

    <div class="mt-lg grid grid-cols-1 items-start gap-lg xl:grid-cols-3">
        <form wire:submit="saveCategory">
            <x-ui.card :title="$categoryId ? 'Ubah Kategori' : 'Tambah Kategori'">
                <div class="flex flex-col gap-md">
                    <x-ui.field label="Nama" required :error="$errors->first('categoryForm.name')"><x-ui.input wire:model="categoryForm.name" /></x-ui.field>
                    <x-ui.field label="Kode" required :error="$errors->first('categoryForm.code')"><x-ui.input wire:model="categoryForm.code" /></x-ui.field>
                    <x-ui.field label="Segment" required><x-ui.select wire:model="categoryForm.segment"><option>Reguler</option><option>Captive</option></x-ui.select></x-ui.field>
                    <x-ui.field label="Tier" required :error="$errors->first('categoryForm.tier')"><x-ui.input wire:model="categoryForm.tier" /></x-ui.field>
                    <x-ui.field label="Penggunaan Kendaraan" required :error="$errors->first('categoryForm.allows_passenger')">
                        <div class="flex flex-wrap gap-md text-body-md">
                            <label class="flex gap-sm"><input wire:model="categoryForm.allows_passenger" type="checkbox"> Passenger</label>
                            <label class="flex gap-sm"><input wire:model="categoryForm.allows_commercial" type="checkbox"> Commercial</label>
                        </div>
                    </x-ui.field>
                    <label class="flex gap-sm text-body-md"><input wire:model="categoryForm.is_active" type="checkbox"> Aktif untuk registrasi</label>
                    <div class="flex gap-sm"><x-ui.button type="submit" size="md">Simpan</x-ui.button>
                        @if ($categoryId)<button type="button" wire:click="deleteCategory" wire:confirm="Hapus kategori dan seluruh turunannya?" class="text-[13px] text-signature-coral">Hapus</button>@endif</div>
                </div>
            </x-ui.card>
        </form>

        <form wire:submit="saveSubCategory">
            <x-ui.card :title="$subCategoryId ? 'Ubah Sub-kategori' : 'Tambah Sub-kategori'">
                <div class="flex flex-col gap-md">
                    <x-ui.field label="Kategori" required><x-ui.select wire:model="subCategoryForm.category_id"><option value="">Pilih</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</x-ui.select></x-ui.field>
                    <x-ui.field label="Nama" required :error="$errors->first('subCategoryForm.name')"><x-ui.input wire:model="subCategoryForm.name" /></x-ui.field>
                    <div class="flex gap-sm"><x-ui.button type="submit" size="md">Simpan</x-ui.button>
                        @if ($subCategoryId)<button type="button" wire:click="deleteSubCategory" wire:confirm="Hapus sub-kategori dan instansi yang tidak dipakai?" class="text-[13px] text-signature-coral">Hapus</button>@endif</div>
                </div>
            </x-ui.card>
        </form>

        <form wire:submit="saveInstitution">
            <x-ui.card :title="$institutionId ? 'Ubah Instansi' : 'Tambah Instansi'">
                <div class="flex flex-col gap-md">
                    <x-ui.field label="Sub-kategori" required><x-ui.select wire:model="institutionForm.sub_category_id"><option value="">Pilih</option>@foreach ($subCategories as $sub)<option value="{{ $sub->id }}">{{ $sub->category->name }} · {{ $sub->name }}</option>@endforeach</x-ui.select></x-ui.field>
                    <x-ui.field label="Nama" required :error="$errors->first('institutionForm.name')"><x-ui.input wire:model="institutionForm.name" /></x-ui.field>
                    <div class="flex gap-sm"><x-ui.button type="submit" size="md">Simpan</x-ui.button>
                        @if ($institutionId)<button type="button" wire:click="deleteInstitution" wire:confirm="Hapus instansi ini?" class="text-[13px] text-signature-coral">Hapus</button>@endif</div>
                </div>
            </x-ui.card>
        </form>
    </div>

    <p class="mt-sm text-helper text-muted">Kategori, sub-kategori, atau instansi yang masih dipakai akun Referral selalu ditolak saat dihapus.</p>
</x-admin.master-shell>
