<div>
<x-admin.accounts-shell
    :title="$pageMode === 'create' ? 'Buat Akun AO' : ($pageMode === 'edit' ? 'Ubah Akun AO' : 'Akun AO')"
    :back-href="$pageMode === 'list' ? route('accounts.index') : route('accounts.officers')">

    @if (session('account_success'))
        <x-ui.callout class="mb-md">{{ session('account_success') }}</x-ui.callout>
    @endif

    @if ($initialPassword)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink/35 px-lg py-xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="initial-password-title"
            x-data="{
                copied: false,
                password: @js($initialPassword),
                async copyPassword() {
                    try {
                        await navigator.clipboard.writeText(this.password);
                    } catch (error) {
                        const input = this.$refs.passwordText;
                        input.select();
                        document.execCommand('copy');
                        input.setSelectionRange(input.value.length, input.value.length);
                    }

                    this.copied = true;
                    window.setTimeout(() => this.copied = false, 1800);
                },
            }"
            x-on:keydown.escape.window="$wire.dismissInitialPassword()"
        >
            <div class="w-full max-w-[440px] rounded-lg border border-hairline bg-canvas p-lg shadow-[0_24px_70px_rgba(13,18,24,0.22)] md:p-xl">
                <div class="flex items-start justify-between gap-md">
                    <div>
                        <p id="initial-password-title" class="text-title-sm text-ink">Akun AO dibuat</p>
                        <p class="mt-1 text-body-md text-muted">{{ $createdName }}</p>
                    </div>
                    <button type="button"
                            wire:click="dismissInitialPassword"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-hairline text-muted transition-colors hover:text-ink"
                            aria-label="Tutup popup kata sandi">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             class="h-4 w-4" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <p class="mt-md text-[13px] leading-[1.55] text-muted">
                    Kata sandi awal hanya ditampilkan sekali. Salin sekarang sebelum menutup popup ini.
                </p>

                <div class="mt-lg flex items-center gap-sm rounded-md border border-dashed border-border-strong bg-surface-soft p-3">
                    <input x-ref="passwordText"
                           type="text"
                           readonly
                           value="{{ $initialPassword }}"
                           class="min-w-0 flex-1 border-0 bg-transparent p-0 font-mono text-[18px] font-medium tracking-[0.08em] text-ink focus:ring-0">
                    <button type="button"
                            x-on:click="copyPassword"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary text-on-primary transition-colors active:bg-primary-active"
                            x-bind:aria-label="copied ? 'Kata sandi tersalin' : 'Salin kata sandi'">
                        <svg x-show="! copied" xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="h-4 w-4" aria-hidden="true">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                        </svg>
                        <svg x-show="copied" x-cloak xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="h-4 w-4" aria-hidden="true">
                            <path d="M20 6 9 17l-5-5" />
                        </svg>
                    </button>
                </div>

                <div class="mt-lg flex justify-end">
                    <x-ui.button variant="secondary" size="md" type="button" wire:click="dismissInitialPassword">
                        Selesai
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    @if ($pageMode === 'list')
        <div class="mb-5 flex flex-wrap items-center gap-sm">
            <div class="min-w-[260px] flex-1">
                <x-ui.input wire:model.live.debounce.400ms="search" type="search"
                            placeholder="Cari nama atau nama user AO..." />
            </div>
        </div>

        <x-ui.table min-width="820px">
            <x-slot:head>
                <x-ui.th>Nama</x-ui.th>
                <x-ui.th>Nama User</x-ui.th>
                <x-ui.th>Dibuat</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th align="right">Aksi</x-ui.th>
            </x-slot:head>

            @forelse ($this->accounts as $account)
                <tr wire:key="officer-{{ $account->id }}">
                    <x-ui.td class="font-medium text-ink">{{ $account->full_name }}</x-ui.td>
                    <x-ui.td>{{ $account->user->username }}</x-ui.td>
                    <x-ui.td class="text-muted">{{ $account->created_at?->translatedFormat('d F Y') ?? '-' }}</x-ui.td>
                    <x-ui.td>
                        <x-ui.chip :tone="$account->user->is_active ? 'success' : 'neutral'">
                            {{ $account->user->is_active ? 'Aktif' : 'Nonaktif' }}
                        </x-ui.chip>
                    </x-ui.td>
                    <x-ui.td align="right">
                        <a href="{{ route('accounts.officers.edit', $account) }}" wire:navigate
                           class="text-[13px] font-medium text-link">Ubah</a>
                    </x-ui.td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-lg text-center text-body-md text-muted">
                        @if ($search !== '')
                            Tidak ada AO yang cocok dengan "{{ $search }}".
                        @else
                            Belum ada akun AO. Gunakan tombol Buat Akun AO untuk membuat yang pertama.
                        @endif
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div class="mt-md">{{ $this->accounts->links() }}</div>

        <div class="mt-lg flex justify-end">
            <x-ui.button size="md" href="{{ route('accounts.officers.create') }}" wire:navigate>Buat Akun AO</x-ui.button>
        </div>
    @else
        <form wire:submit="save">
            <x-ui.card :title="$pageMode === 'edit' ? 'Data AO' : 'Data Akun Baru'">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Nama Lengkap" required class="sm:col-span-2" :error="$errors->first('full_name')">
                        <x-ui.input wire:model="full_name"
                                    placeholder="Masukkan nama lengkap account officer"
                                    :invalid="$errors->has('full_name')" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir" required :error="$errors->first('birth_date')">
                        <x-ui.input wire:model="birth_date" type="date"
                                    placeholder="Pilih tanggal lahir"
                                    :invalid="$errors->has('birth_date')" />
                    </x-ui.field>

                    <x-ui.field label="Nama User" required :error="$errors->first('username')"
                                helper="Harus unik. Digunakan untuk masuk.">
                        <x-ui.input wire:model="username"
                                    placeholder="Contoh: rahmawati.putri"
                                    :invalid="$errors->has('username')" />
                    </x-ui.field>

                    <x-ui.field label="Alamat Email" :error="$errors->first('email')">
                        <x-ui.input wire:model="email" type="email"
                                    placeholder="Contoh: nama@perusahaan.co.id"
                                    :invalid="$errors->has('email')" />
                    </x-ui.field>

                    <x-ui.field label="No. Handphone" :error="$errors->first('phone')" class="sm:col-span-2">
                        <x-ui.input wire:model="phone" type="tel"
                                    placeholder="Contoh: 081234567890"
                                    :invalid="$errors->has('phone')" />
                    </x-ui.field>
                </div>

                <label class="mt-md flex items-center gap-sm text-body-md text-body">
                    <input type="checkbox" wire:model="is_active" class="rounded-xs border-hairline">
                    Akun aktif
                </label>

                @if ($pageMode === 'create' && ! $initialPassword)
                    <x-ui.callout class="mt-md">
                        Kata sandi awal dibuat otomatis dan ditampilkan
                        <span class="font-medium">satu kali</span> setelah akun tersimpan.
                        Tidak ada cara menampilkannya kembali.
                    </x-ui.callout>
                @endif

                <div class="mt-lg flex flex-wrap gap-sm border-t border-hairline pt-lg">
                    <x-ui.button type="submit" size="md" wire:loading.attr="disabled">
                        {{ $pageMode === 'edit' ? 'Simpan Perubahan' : 'Buat Akun' }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" size="md" type="button" wire:click="cancel">Batal</x-ui.button>
                    <span wire:loading wire:target="save" class="self-center text-helper text-muted">Menyimpan...</span>
                </div>
            </x-ui.card>
        </form>
    @endif

</x-admin.accounts-shell>
</div>
