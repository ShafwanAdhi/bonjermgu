<div class="mx-auto max-w-[720px] px-lg py-xl md:px-xxl md:py-xxl">

    <x-ui.page-header title="Profil">
        @unless ($editing)
            <x-slot:actions>
                <x-ui.button variant="secondary" size="md" wire:click="edit">Ubah Profil</x-ui.button>
            </x-slot:actions>
        @endunless
    </x-ui.page-header>

    @if (session('profile_success'))
        <x-ui.callout class="mb-md">{{ session('profile_success') }}</x-ui.callout>
    @endif

    <x-ui.card>
        <div class="mb-7 flex items-center gap-md">
            <span class="flex h-14 w-14 items-center justify-center rounded-pill bg-signature-mint text-[20px] font-medium text-ink">
                {{ Str::of($this->profile->full_name)->explode(' ')->take(2)->map(fn ($w) => Str::substr($w, 0, 1))->implode('') }}
            </span>
            <div>
                <p class="text-title-sm text-ink">{{ $this->profile->full_name }}</p>
                <p class="text-[13px] leading-[1.5] text-muted">
                    Referral · {{ $this->profile->category?->name ?? 'Tanpa kategori' }}
                </p>
            </div>
        </div>

        @if ($editing)
            <form wire:submit="save" class="flex flex-col gap-lg">

                <p class="border-t border-hairline pt-7 text-eyebrow uppercase text-muted">Data diri</p>
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Nama Lengkap" required class="sm:col-span-2"
                                :error="$errors->first('full_name')">
                        <x-ui.input wire:model="full_name" :invalid="$errors->has('full_name')" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir" helper="Hanya Admin yang dapat mengubahnya.">
                        <x-ui.input value="{{ $this->profile->birth_date?->translatedFormat('d F Y') }}" disabled
                                    class="bg-surface-soft text-muted" />
                    </x-ui.field>

                    <x-ui.field label="Alamat Email" :error="$errors->first('email')">
                        <x-ui.input wire:model="email" type="email" :invalid="$errors->has('email')" />
                    </x-ui.field>

                    <x-ui.field label="No. Handphone" :error="$errors->first('phone')">
                        <x-ui.input wire:model="phone" type="tel" :invalid="$errors->has('phone')" />
                    </x-ui.field>
                </div>

                <p class="border-t border-hairline pt-7 text-eyebrow uppercase text-muted">Kategori Referral</p>
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Kategori" required :error="$errors->first('category_id')">
                        <x-ui.select wire:model.live="category_id" :invalid="$errors->has('category_id')">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Sub-kategori" required :error="$errors->first('sub_category_id')">
                        <x-ui.select wire:model.live="sub_category_id"
                                     :invalid="$errors->has('sub_category_id')"
                                     :disabled="! $category_id">
                            <option value="">{{ $category_id ? 'Pilih sub-kategori' : 'Pilih kategori dahulu' }}</option>
                            @foreach ($this->subCategories as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Instansi" :error="$errors->first('institution_id')">
                        <x-ui.select wire:model="institution_id"
                                     :invalid="$errors->has('institution_id')"
                                     :disabled="$this->institutions->isEmpty()">
                            <option value="">
                                @if (! $sub_category_id)
                                    Pilih sub-kategori dahulu
                                @elseif ($this->institutions->isEmpty())
                                    Tidak tersedia untuk sub-kategori ini
                                @else
                                    Pilih instansi
                                @endif
                            </option>
                            @foreach ($this->institutions as $institution)
                                <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Nama Cabang" :error="$errors->first('branch_name')">
                        <x-ui.input wire:model="branch_name" :invalid="$errors->has('branch_name')" />
                    </x-ui.field>
                </div>

                <div class="flex flex-wrap gap-sm border-t border-hairline pt-lg">
                    <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Perubahan</x-ui.button>
                    <x-ui.button variant="secondary" size="md" type="button" wire:click="cancel">Batal</x-ui.button>
                    <span wire:loading wire:target="save" class="self-center text-helper text-muted">Menyimpan…</span>
                </div>
            </form>
        @else
            <p class="mb-md border-t border-hairline pt-7 text-eyebrow uppercase text-muted">Data diri</p>
            <div class="mb-7 grid grid-cols-1 gap-lg sm:grid-cols-2">
                <x-ui.key-value label="Tanggal Lahir">
                    {{ $this->profile->birth_date?->translatedFormat('d F Y') ?? '—' }}
                </x-ui.key-value>
                <x-ui.key-value label="Alamat Email">{{ $this->profile->email ?? '—' }}</x-ui.key-value>
                <x-ui.key-value label="No. Handphone">{{ $this->profile->phone ?? '—' }}</x-ui.key-value>
            </div>

            <p class="mb-md border-t border-hairline pt-7 text-eyebrow uppercase text-muted">Akun</p>
            <div class="mb-7 grid grid-cols-1 gap-lg sm:grid-cols-2">
                <x-ui.key-value label="Nama User">{{ $this->profile->user->username }}</x-ui.key-value>
                <x-ui.key-value label="Terdaftar">
                    {{ $this->profile->created_at?->translatedFormat('d F Y') ?? '—' }}
                </x-ui.key-value>
            </div>

            <p class="mb-md border-t border-hairline pt-7 text-eyebrow uppercase text-muted">Kategori Referral</p>
            <div class="grid grid-cols-1 gap-lg sm:grid-cols-2">
                <x-ui.key-value label="Kategori">{{ $this->profile->category?->name ?? '—' }}</x-ui.key-value>
                <x-ui.key-value label="Sub-kategori">{{ $this->profile->subCategory?->name ?? '—' }}</x-ui.key-value>
                <x-ui.key-value label="Instansi">{{ $this->profile->institution?->name ?? '—' }}</x-ui.key-value>
                <x-ui.key-value label="Nama Cabang">{{ $this->profile->branch_name ?? '—' }}</x-ui.key-value>
            </div>
        @endif
    </x-ui.card>
</div>
