<div>
<x-admin.accounts-shell
    :title="$pageMode === 'edit' ? 'Ubah Profil Referral' : 'Akun Referral'"
    :back-href="$pageMode === 'edit' ? route('accounts.referrals') : route('accounts.index')">

    @if (session('account_success'))
        <x-ui.callout class="mb-md">{{ session('account_success') }}</x-ui.callout>
    @endif

    @if ($pageMode === 'edit')
        <form wire:submit="save">
            <x-ui.card title="Data Referral">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Nama Lengkap" required class="sm:col-span-2" :error="$errors->first('full_name')">
                        <x-ui.input wire:model="full_name"
                                    placeholder="Masukkan nama lengkap referral"
                                    :invalid="$errors->has('full_name')" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir" required :error="$errors->first('birth_date')">
                        <x-ui.input wire:model="birth_date" type="date"
                                    placeholder="Pilih tanggal lahir"
                                    :invalid="$errors->has('birth_date')" />
                    </x-ui.field>

                    <x-ui.field label="Alamat Email" :error="$errors->first('email')">
                        <x-ui.input wire:model="email" type="email"
                                    placeholder="Contoh: nama@email.com"
                                    :invalid="$errors->has('email')" />
                    </x-ui.field>

                    <x-ui.field label="No. Handphone" :error="$errors->first('phone')">
                        <x-ui.input wire:model="phone" type="tel"
                                    placeholder="Contoh: 081234567890"
                                    :invalid="$errors->has('phone')" />
                    </x-ui.field>

                    <x-ui.field label="Kategori" required :error="$errors->first('category_id')">
                        <x-ui.select wire:model.live="category_id" :invalid="$errors->has('category_id')">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Sub-kategori" required :error="$errors->first('sub_category_id')">
                        <x-ui.select wire:model.live="sub_category_id" :disabled="! $category_id"
                                     :invalid="$errors->has('sub_category_id')">
                            <option value="">{{ $category_id ? 'Pilih sub-kategori' : 'Pilih kategori dahulu' }}</option>
                            @foreach ($this->subCategories as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Instansi" :error="$errors->first('institution_id')">
                        <x-ui.select wire:model="institution_id" :disabled="$this->institutions->isEmpty()"
                                     :invalid="$errors->has('institution_id')">
                            <option value="">
                                {{ $this->institutions->isEmpty() ? 'Tidak tersedia untuk sub-kategori ini' : 'Pilih instansi' }}
                            </option>
                            @foreach ($this->institutions as $institution)
                                <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Nama Cabang" :error="$errors->first('branch_name')">
                        <x-ui.input wire:model="branch_name"
                                    placeholder="Contoh: Cabang BSD"
                                    :invalid="$errors->has('branch_name')" />
                    </x-ui.field>
                </div>

                <label class="mt-md flex items-center gap-sm text-body-md text-body">
                    <input type="checkbox" wire:model="is_active" class="rounded-xs border-hairline">
                    Akun aktif - menonaktifkan menutup akses masuk tanpa menghapus data
                </label>

                <div class="mt-lg flex flex-wrap gap-sm border-t border-hairline pt-lg">
                    <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Perubahan</x-ui.button>
                    <x-ui.button variant="secondary" size="md" type="button" wire:click="cancel">Batal</x-ui.button>
                    <span wire:loading wire:target="save" class="self-center text-helper text-muted">Menyimpan...</span>
                </div>
            </x-ui.card>
        </form>
    @else
        <div class="mb-5 flex flex-wrap items-center gap-sm">
            <div class="min-w-[260px] flex-1">
                <x-ui.input wire:model.live.debounce.400ms="search" type="search"
                            placeholder="Cari nama atau nama user Referral..." />
            </div>
            <span class="text-[13px] leading-[1.4] text-muted">
                {{ $this->accounts->total() }} akun
            </span>
        </div>

        <x-ui.table min-width="920px" label="Daftar akun Referral">
            <x-slot:head>
                <x-ui.th>Nama</x-ui.th>
                <x-ui.th>Nama User</x-ui.th>
                <x-ui.th>Kategori</x-ui.th>
                <x-ui.th>Instansi</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th align="right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($this->accounts as $account)
                <tr wire:key="referral-{{ $account->id }}">
                    <x-ui.td class="font-medium text-ink">{{ $account->full_name }}</x-ui.td>
                    <x-ui.td>{{ $account->user->username }}</x-ui.td>
                    <x-ui.td>
                        {{ $account->category?->name ?? '-' }}
                        <span class="block text-helper text-muted">{{ $account->subCategory?->name }}</span>
                    </x-ui.td>
                    <x-ui.td>{{ $account->institution?->name ?? '-' }}</x-ui.td>
                    <x-ui.td>
                        <x-ui.chip :tone="$account->user->is_active ? 'success' : 'neutral'">
                            {{ $account->user->is_active ? 'Aktif' : 'Nonaktif' }}
                        </x-ui.chip>
                    </x-ui.td>
                    <x-ui.td align="right">
                        <a href="{{ route('accounts.referrals.edit', $account) }}" wire:navigate
                           class="text-[13px] font-medium text-link">Ubah profil</a>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-lg text-center text-body-md text-muted">
                        @if ($search !== '')
                            Tidak ada Referral yang cocok dengan "{{ $search }}".
                        @else
                            Belum ada akun Referral. Akun dibuat sendiri melalui registrasi mandiri.
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div class="mt-md">{{ $this->accounts->links() }}</div>
    @endif

</x-admin.accounts-shell>
</div>
