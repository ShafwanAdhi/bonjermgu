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

    @if (session('password_success'))
        <x-ui.callout class="mb-md">{{ session('password_success') }}</x-ui.callout>
    @endif

    @if (session('password_reset_success'))
        <x-ui.callout class="mb-md">{{ session('password_reset_success') }}</x-ui.callout>
    @endif

    @if (session('password_reset_warning'))
        <x-ui.callout tone="warning" class="mb-md">{{ session('password_reset_warning') }}</x-ui.callout>
    @endif

    <x-ui.card>
        <div class="mb-7 flex items-center gap-md">
            <span class="flex h-14 w-14 items-center justify-center rounded-pill bg-signature-peach text-[20px] font-medium text-ink">
                {{ Str::of($this->profile->full_name)->explode(' ')->take(2)->map(fn ($w) => Str::substr($w, 0, 1))->implode('') }}
            </span>
            <div>
                <p class="text-title-sm text-ink">{{ $this->profile->full_name }}</p>
                <p class="text-[13px] leading-[1.5] text-muted">Account Officer</p>
            </div>
        </div>

        @if ($editing)
            <form wire:submit="save" class="flex flex-col gap-lg">
                <p id="profile-data-diri" class="scroll-mt-50 border-t border-hairline pt-7 text-eyebrow uppercase text-muted">Data diri</p>

                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Nama Lengkap" required class="sm:col-span-2"
                                :error="$errors->first('full_name')">
                        <x-ui.input wire:model="full_name"
                                    placeholder="Masukkan nama lengkap"
                                    :invalid="$errors->has('full_name')" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir" required :error="$errors->first('birth_date')">
                        <x-ui.input wire:model="birth_date" type="date" :invalid="$errors->has('birth_date')" />
                    </x-ui.field>

                    <x-ui.field label="Alamat Email" :error="$errors->first('email')">
                        <x-ui.input wire:model="email"
                                    type="email"
                                    placeholder="contoh@email.com"
                                    :invalid="$errors->has('email')" />
                    </x-ui.field>

                    <x-ui.field label="No. Handphone" :error="$errors->first('phone')">
                        <x-ui.input wire:model="phone"
                                    type="tel"
                                    placeholder="Contoh: 081234567890"
                                    :invalid="$errors->has('phone')" />
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
            <div class="grid grid-cols-1 gap-lg sm:grid-cols-2">
                <x-ui.key-value label="Nama User">{{ $this->profile->user->username }}</x-ui.key-value>
                <x-ui.key-value label="Akun dibuat">
                    oleh Admin · {{ $this->profile->created_at?->translatedFormat('d F Y') ?? '—' }}
                </x-ui.key-value>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Keamanan Akun" class="mt-lg">
        <div class="mb-lg flex flex-col gap-md border-b border-hairline pb-lg sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-body-md text-ink">Reset kata sandi lewat email</p>
                <p class="mt-1 text-[13px] leading-[1.6] text-muted">
                    @if ($this->profile->email)
                        Link reset akan dikirim ke {{ $this->profile->email }}.
                    @else
                        Pasang email terlebih dahulu untuk menerima link reset.
                    @endif
                </p>
            </div>

            <x-ui.button type="button"
                         variant="secondary"
                         size="md"
                         class="w-full whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:shrink-0"
                         wire:click="sendPasswordResetLink"
                         wire:loading.attr="disabled"
                         :disabled="! $this->profile->email"
                         wire:target="sendPasswordResetLink">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Kirim Link Reset</span>
                <span wire:loading wire:target="sendPasswordResetLink">Mengirim...</span>
            </x-ui.button>
        </div>

        <form wire:submit="changePassword" class="flex flex-col gap-md">
            <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                <x-ui.field label="Kata Sandi Saat Ini" required class="sm:col-span-2"
                            :error="$errors->first('current_password')">
                    <x-ui.input wire:model="current_password" type="password"
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi saat ini"
                                :invalid="$errors->has('current_password')" />
                </x-ui.field>

                <x-ui.field label="Kata Sandi Baru" required :error="$errors->first('password')">
                    <x-ui.input wire:model="password" type="password"
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                :invalid="$errors->has('password')" />
                </x-ui.field>

                <x-ui.field label="Konfirmasi Kata Sandi Baru" required
                            :error="$errors->first('password_confirmation')">
                    <x-ui.input wire:model="password_confirmation" type="password"
                                autocomplete="new-password"
                                placeholder="Ulangi kata sandi baru"
                                :invalid="$errors->has('password_confirmation')" />
                </x-ui.field>
            </div>

            <div class="flex flex-wrap gap-sm border-t border-hairline pt-lg">
                <x-ui.button type="submit" size="md" wire:loading.attr="disabled" wire:target="changePassword">
                    Simpan Kata Sandi
                </x-ui.button>
                <span wire:loading wire:target="changePassword" class="self-center text-helper text-muted">Menyimpan...</span>
            </div>
        </form>
    </x-ui.card>
</div>
