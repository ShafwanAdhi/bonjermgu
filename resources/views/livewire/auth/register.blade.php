<div>
@if ($registered)

    {{-- Registrasi Berhasil. Password dipilih Referral saat pendaftaran dan
         tidak ditampilkan ulang setelah akun dibuat. --}}
    <div class="min-h-[calc(100vh-100px)] bg-surface-soft px-lg py-section md:px-xxl">
        <div class="mx-auto max-w-[520px] rounded-lg border border-hairline bg-canvas p-xxl text-center">
            <div class="mx-auto mb-lg flex h-14 w-14 items-center justify-center rounded-pill border-[1.5px] border-success-border text-[26px] text-success">
                &check;
            </div>

            <h1 class="mb-2 text-title-lg text-ink">Akun Anda aktif</h1>
            <p class="mb-xl text-[14px] leading-[1.6] text-body">
                Selamat bergabung, {{ $full_name }}. Gunakan nama user dan kata sandi
                yang Anda buat untuk masuk ke sistem.
            </p>

            <p class="mb-xl rounded-md bg-signature-cream px-[18px] py-3.5 text-[13px] leading-[1.6] text-signature-coral">
                Demi keamanan, kata sandi tidak ditampilkan ulang setelah pendaftaran berhasil.
            </p>

            <div class="flex flex-wrap justify-center gap-sm">
                <x-ui.button :href="route('login')">Masuk sekarang</x-ui.button>
            </div>
        </div>
    </div>

@else

    {{-- Registrasi Referral --}}
    <div class="min-h-[calc(100vh-100px)] bg-surface-soft px-lg pb-section pt-16 md:px-xxl">
        <div class="mx-auto max-w-[640px]">
            <h1 class="mb-2 font-display text-display-md text-ink">Registrasi Referral</h1>
            <p class="mb-xl text-[14px] leading-[1.6] text-muted">
                Isi data di bawah ini. Akun Anda langsung aktif setelah pendaftaran.
            </p>

            <form wire:submit="register"
                  x-data="{ showPassword: false }"
                  class="flex flex-col gap-lg rounded-lg border border-hairline bg-canvas p-xl">

                <p class="text-eyebrow uppercase text-muted">Data diri</p>

                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Nama Lengkap" required class="sm:col-span-2"
                                :error="$errors->first('full_name')">
                        <x-ui.input wire:model="full_name" type="text"
                                    :invalid="$errors->has('full_name')" autocomplete="name" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir" required :error="$errors->first('birth_date')">
                        <x-ui.input wire:model="birth_date" type="date"
                                    :invalid="$errors->has('birth_date')" />
                    </x-ui.field>

                    <x-ui.field label="Alamat Email" :error="$errors->first('email')">
                        <x-ui.input wire:model="email" type="email"
                                    :invalid="$errors->has('email')" autocomplete="email" />
                    </x-ui.field>

                    <x-ui.field label="No. Handphone" :error="$errors->first('phone')">
                        <x-ui.input wire:model="phone" type="tel"
                                    :invalid="$errors->has('phone')" autocomplete="tel" />
                    </x-ui.field>
                </div>

                <p class="border-t border-hairline pt-lg text-eyebrow uppercase text-muted">Akun</p>

                <x-ui.field label="Nama User" required
                            :error="$errors->first('username')">
                    <x-ui.input wire:model="username" type="text"
                                :invalid="$errors->has('username')" autocomplete="username"
                                placeholder="Harus unik, digunakan untuk masuk" />
                </x-ui.field>

                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Kata Sandi" required :error="$errors->first('password')">
                        <x-ui.input wire:model="password" type="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    :invalid="$errors->has('password')" autocomplete="new-password"
                                    placeholder="Minimal 8 karakter" />
                    </x-ui.field>

                    <x-ui.field label="Konfirmasi Kata Sandi" required
                                :error="$errors->first('password_confirmation')">
                        <x-ui.input wire:model="password_confirmation" type="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    :invalid="$errors->has('password_confirmation')" autocomplete="new-password"
                                    placeholder="Ulangi kata sandi" />
                    </x-ui.field>
                </div>

                <label class="-mt-sm flex items-center gap-sm text-body-md text-body">
                    <input type="checkbox" x-model="showPassword" class="rounded-xs border-hairline">
                    Tampilkan kata sandi
                </label>

                <p class="border-t border-hairline pt-lg text-eyebrow uppercase text-muted">
                    Kategori Referral
                </p>

                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Kategori" required :error="$errors->first('category_id')">
                        <x-ui.select wire:model.live="category_id" :invalid="$errors->has('category_id')">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $this->categoryOptionLabel($category->name) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Sub-kategori" required :error="$errors->first('sub_category_id')">
                        <x-ui.select wire:model.live="sub_category_id"
                                     :invalid="$errors->has('sub_category_id')"
                                     :disabled="! $category_id">
                            <option value="">
                                {{ $category_id ? 'Pilih sub-kategori' : 'Pilih kategori dahulu' }}
                            </option>
                            @foreach ($this->subCategories as $subCategory)
                                <option value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
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
                        <x-ui.input wire:model="branch_name" type="text"
                                    :invalid="$errors->has('branch_name')" />
                    </x-ui.field>
                </div>

                <p class="-mt-sm text-helper text-muted">
                    Pilihan sub-kategori dan instansi dimuat dari server mengikuti kategori yang dipilih.
                </p>

                <x-ui.button type="submit" wire:loading.attr="disabled" class="w-full">
                    <span wire:loading.remove wire:target="register">Daftar &amp; aktifkan akun</span>
                    <span wire:loading wire:target="register">Memproses…</span>
                </x-ui.button>
            </form>
        </div>
    </div>

@endif
</div>
