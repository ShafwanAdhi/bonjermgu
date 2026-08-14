<x-admin.master-shell title="Master Referral" :last-change="$this->lastChange">
    @php
        $totalSubCategories = $categories->sum(fn ($category) => $category->subCategories->count());
        $totalInstitutions = $categories->sum(fn ($category) => $category->subCategories->sum(fn ($sub) => $sub->institutions->count()));
    @endphp

    @if (session('admin_success'))
        <x-ui.callout class="mb-md">{{ session('admin_success') }}</x-ui.callout>
    @endif
    @error('master')
        <div role="alert" class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror
    @error('configuration')
        <div role="alert" class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror

    <section class="mb-lg rounded-lg border border-hairline bg-surface-soft p-lg md:p-xl">
        <div class="grid gap-lg lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <p class="mb-1.5 text-eyebrow uppercase text-muted">Struktur Referral</p>
                <p class="max-w-[620px] text-title-sm text-ink">
                    Kelola kategori, sub-kategori, dan instansi dalam satu alur yang mudah dipindai.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-sm text-center lg:col-span-2">
                <div class="rounded-sm border border-hairline bg-canvas px-3 py-2.5">
                    <p class="text-label-md text-ink">{{ $categories->count() }}</p>
                    <p class="mt-1 text-helper text-muted">Kategori</p>
                </div>
                <div class="rounded-sm border border-hairline bg-canvas px-3 py-2.5">
                    <p class="text-label-md text-ink">{{ $totalSubCategories }}</p>
                    <p class="mt-1 text-helper text-muted">Sub</p>
                </div>
                <div class="rounded-sm border border-hairline bg-canvas px-3 py-2.5">
                    <p class="text-label-md text-ink">{{ $totalInstitutions }}</p>
                    <p class="mt-1 text-helper text-muted">Instansi</p>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-5" data-master-page-grid>
        <section class="rounded-lg border border-hairline bg-canvas p-lg md:p-xl lg:col-span-3">
            <div class="mb-lg flex flex-col gap-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-title-sm text-ink">Peta Master Referral</p>
                    <p class="mt-1 text-[13px] leading-[1.5] text-muted">Klik kategori, sub-kategori, atau instansi untuk membuka editor yang sesuai.</p>
                </div>
                <x-ui.button type="button" size="md" wire:click="newCategory" class="w-full sm:w-auto">Tambah Kategori</x-ui.button>
            </div>

            <div class="space-y-md">
                @forelse ($categories as $category)
                    <article @class([
                                'overflow-hidden rounded-md border bg-canvas transition-colors duration-200',
                                'border-ink' => $activeEditor === 'category' && (int) $categoryId === (int) $category->id,
                                'border-hairline' => ! ($activeEditor === 'category' && (int) $categoryId === (int) $category->id),
                            ])
                            wire:key="category-{{ $category->id }}"
                            x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                        <button type="button"
                                class="flex w-full items-start gap-md bg-surface-soft px-md py-3 text-left"
                                data-master-row
                                x-on:click="open = ! open">
                            <span class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-canvas text-[12px] font-medium text-ink">
                                {{ Str::substr($category->code, 0, 2) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-[15px] font-medium text-ink">{{ $category->name }}</span>
                                    <span class="rounded-xs bg-canvas px-2 py-0.5 text-[11px] text-muted">{{ $category->segment }}</span>
                                    <span class="rounded-xs bg-signature-cream px-2 py-0.5 text-[11px] text-muted">{{ $category->tier }}</span>
                                    @unless ($category->is_active)
                                        <span class="rounded-xs bg-danger-bg px-2 py-0.5 text-[11px] text-signature-coral">Nonaktif</span>
                                    @endunless
                                </span>
                                <span class="mt-1 block text-helper text-muted">
                                    {{ $category->subCategories->count() }} sub-kategori · {{ $category->accounts_count }} akun · {{ collect($category->allowedVehicleUsages())->map(fn ($usage) => $usage->value)->join(', ') }}
                                </span>
                            </span>
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-hairline bg-canvas text-[16px] leading-none text-muted transition-transform duration-200"
                                  x-bind:class="open ? 'rotate-180' : ''"
                                  x-text="open ? '-' : '+'"></span>
                        </button>

                        <div data-master-collapse
                             x-bind:class="open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                            <div class="overflow-hidden">
                                <div class="flex flex-wrap gap-sm border-b border-divider px-md py-3">
                                    <button type="button" wire:click="editCategory({{ $category->id }})" class="text-[13px] font-medium text-link">Ubah Kategori</button>
                                    <button type="button" wire:click="newSubCategory({{ $category->id }})" class="text-[13px] font-medium text-link">+ Sub-kategori</button>
                                </div>

                                <div class="divide-y divide-divider">
                                    @forelse ($category->subCategories as $sub)
                                        <div class="px-md py-3">
                                            <div class="flex flex-col gap-sm sm:flex-row sm:items-center">
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-body-md font-medium text-body">{{ $sub->name }}</p>
                                                    <p class="mt-1 text-helper text-muted">{{ $sub->institutions->count() }} instansi</p>
                                                </div>
                                                <div class="flex flex-wrap gap-sm">
                                                    <button type="button" wire:click="editSubCategory({{ $sub->id }})" class="text-[13px] font-medium text-link">Ubah Sub</button>
                                                    <button type="button" wire:click="newInstitution({{ $sub->id }})" class="text-[13px] font-medium text-link">+ Instansi</button>
                                                </div>
                                            </div>

                                            @if ($sub->institutions->isNotEmpty())
                                                <div class="mt-3 flex max-h-36 flex-wrap gap-2 overflow-y-auto pr-1">
                                                    @foreach ($sub->institutions as $institution)
                                                        <button type="button" wire:click="editInstitution({{ $institution->id }})"
                                                                data-master-row
                                                                class="rounded-xs border border-hairline bg-surface-soft px-2.5 py-1.5 text-[11px] text-muted">
                                                            {{ $institution->name }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="px-md py-5 text-body-md text-muted">Belum ada sub-kategori pada kategori ini.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-md border border-dashed border-hairline px-md py-lg text-body-md text-muted">Belum ada kategori Referral.</p>
                @endforelse
            </div>
        </section>

        <aside class="lg:col-span-2 lg:sticky lg:top-6">
            <div data-master-active-panel wire:key="referral-editor-{{ $activeEditor }}-{{ $categoryId }}-{{ $subCategoryId }}-{{ $institutionId }}">
                @if ($activeEditor === 'category')
                    <form wire:submit="saveCategory">
                        <x-ui.card :title="$categoryId ? 'Ubah Kategori' : 'Tambah Kategori'" meta="Level utama">
                            <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                                <x-ui.field label="Nama" required class="sm:col-span-2" :error="$errors->first('categoryForm.name')">
                                    <x-ui.input wire:model="categoryForm.name" placeholder="Contoh: Sales Authorized Dealer" />
                                </x-ui.field>
                                <x-ui.field label="Kode" required :error="$errors->first('categoryForm.code')">
                                    <x-ui.input wire:model="categoryForm.code" placeholder="Contoh: SAD" />
                                </x-ui.field>
                                <x-ui.field label="Segment" required>
                                    <x-ui.select wire:model="categoryForm.segment">
                                        <option>Reguler</option>
                                        <option>Captive</option>
                                    </x-ui.select>
                                </x-ui.field>
                                <x-ui.field label="Tier" required class="sm:col-span-2" :error="$errors->first('categoryForm.tier')">
                                    <x-ui.input wire:model="categoryForm.tier" placeholder="Contoh: Referral" />
                                </x-ui.field>
                                <x-ui.field label="Penggunaan Kendaraan" required class="sm:col-span-2" :error="$errors->first('categoryForm.allows_passenger')">
                                    <div class="grid grid-cols-1 gap-sm sm:grid-cols-2">
                                        <label class="flex min-h-11 items-center gap-sm rounded-sm border border-hairline px-3 text-body-md">
                                            <input wire:model="categoryForm.allows_passenger" type="checkbox" class="rounded-xs border-hairline">
                                            Passenger
                                        </label>
                                        <label class="flex min-h-11 items-center gap-sm rounded-sm border border-hairline px-3 text-body-md">
                                            <input wire:model="categoryForm.allows_commercial" type="checkbox" class="rounded-xs border-hairline">
                                            Commercial
                                        </label>
                                    </div>
                                </x-ui.field>
                                <label class="flex min-h-11 items-center gap-sm rounded-sm border border-hairline px-3 text-body-md sm:col-span-2">
                                    <input wire:model="categoryForm.is_active" type="checkbox" class="rounded-xs border-hairline">
                                    Aktif untuk registrasi
                                </label>
                            </div>

                            <div class="mt-lg flex flex-col gap-sm sm:flex-row sm:items-center">
                                <x-ui.button type="submit" size="md" class="w-full sm:w-auto">Simpan Kategori</x-ui.button>
                                @if ($categoryId)
                                    <button type="button" wire:click="deleteCategory" wire:confirm="Hapus kategori dan seluruh turunannya?" class="min-h-11 rounded-sm px-3 text-[13px] font-medium text-signature-coral">Hapus</button>
                                @endif
                            </div>
                        </x-ui.card>
                    </form>
                @elseif ($activeEditor === 'sub-category')
                    <form wire:submit="saveSubCategory">
                        <x-ui.card :title="$subCategoryId ? 'Ubah Sub-kategori' : 'Tambah Sub-kategori'" meta="Level kedua">
                            <div class="flex flex-col gap-md">
                                <x-ui.field label="Kategori" required :error="$errors->first('subCategoryForm.category_id')">
                                    <x-ui.select wire:model="subCategoryForm.category_id">
                                        <option value="">Pilih kategori</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                                <x-ui.field label="Nama" required :error="$errors->first('subCategoryForm.name')">
                                    <x-ui.input wire:model="subCategoryForm.name" placeholder="Contoh: Dealer Honda" />
                                </x-ui.field>
                            </div>

                            <div class="mt-lg flex flex-col gap-sm sm:flex-row sm:items-center">
                                <x-ui.button type="submit" size="md" class="w-full sm:w-auto">Simpan Sub-kategori</x-ui.button>
                                @if ($subCategoryId)
                                    <button type="button" wire:click="deleteSubCategory" wire:confirm="Hapus sub-kategori dan instansi yang tidak dipakai?" class="min-h-11 rounded-sm px-3 text-[13px] font-medium text-signature-coral">Hapus</button>
                                @endif
                            </div>
                        </x-ui.card>
                    </form>
                @else
                    <form wire:submit="saveInstitution">
                        <x-ui.card :title="$institutionId ? 'Ubah Instansi' : 'Tambah Instansi'" meta="Level ketiga">
                            <div class="flex flex-col gap-md">
                                <x-ui.field label="Sub-kategori" required :error="$errors->first('institutionForm.sub_category_id')">
                                    <x-ui.select wire:model="institutionForm.sub_category_id">
                                        <option value="">Pilih sub-kategori</option>
                                        @foreach ($subCategories as $sub)
                                            <option value="{{ $sub->id }}">{{ $sub->category->name }} · {{ $sub->name }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>
                                <x-ui.field label="Nama" required :error="$errors->first('institutionForm.name')">
                                    <x-ui.input wire:model="institutionForm.name" placeholder="Contoh: Honda Jakarta Center" />
                                </x-ui.field>
                            </div>

                            <div class="mt-lg flex flex-col gap-sm sm:flex-row sm:items-center">
                                <x-ui.button type="submit" size="md" class="w-full sm:w-auto">Simpan Instansi</x-ui.button>
                                @if ($institutionId)
                                    <button type="button" wire:click="deleteInstitution" wire:confirm="Hapus instansi ini?" class="min-h-11 rounded-sm px-3 text-[13px] font-medium text-signature-coral">Hapus</button>
                                @endif
                            </div>
                        </x-ui.card>
                    </form>
                @endif
            </div>

            <p class="mt-md text-helper text-muted">
                Kategori, sub-kategori, atau instansi yang masih dipakai akun Referral selalu ditolak saat dihapus.
            </p>
        </aside>
    </div>
</x-admin.master-shell>
