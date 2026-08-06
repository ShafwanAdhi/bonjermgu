<div>
<x-admin.accounts-shell title="Akun AO">

    @if (session('account_success'))
        <x-ui.callout class="mb-md">{{ session('account_success') }}</x-ui.callout>
    @endif

    {{-- The one showing of the initial password. Rendered from a public
         property that is not persisted anywhere; the next Livewire round trip
         clears it and no screen can bring it back. --}}
    @if ($initialPassword)
        <div class="mb-5 flex flex-wrap items-center gap-5 rounded-lg border border-success-border bg-canvas px-lg py-5">
            <span class="flex h-10 w-10 items-center justify-center rounded-pill border-[1.5px] border-success-border text-[18px] text-success">
                &check;
            </span>
            <div>
                <p class="text-[15px] font-medium leading-[1.4] text-ink">Akun AO dibuat — {{ $createdName }}</p>
                <p class="text-[13px] leading-[1.5] text-muted">
                    Kata sandi awal dibuat otomatis dan
                    <span class="font-medium text-signature-coral">hanya ditampilkan sekali</span>.
                    Catat sekarang sebelum meninggalkan halaman ini.
                </p>
            </div>
            <div class="ml-auto rounded-md border border-dashed border-border-strong px-5 py-3">
                <p class="mb-1 text-[11px] font-medium uppercase leading-[1.35] tracking-[0.12em] text-muted">
                    Kata sandi awal
                </p>
                <p class="font-mono text-[18px] font-medium leading-[1.2] tracking-[0.1em] text-ink">
                    {{ $initialPassword }}
                </p>
            </div>
        </div>
    @endif

    <div class="mb-5 flex flex-wrap items-center gap-sm">
        <div class="min-w-[260px] flex-1">
            <x-ui.input wire:model.live.debounce.400ms="search" type="search"
                        placeholder="Cari nama atau nama user AO…" />
        </div>
        <span class="text-[13px] leading-[1.4] text-muted">
            {{ $this->accounts->total() }} akun · dibuat Admin, tidak dapat mendaftar sendiri
        </span>
        <x-ui.button size="md" wire:click="create">Buat Akun AO</x-ui.button>
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
                <x-ui.td class="text-muted">{{ $account->created_at?->translatedFormat('d F Y') ?? '—' }}</x-ui.td>
                <x-ui.td>
                    <x-ui.chip :tone="$account->user->is_active ? 'success' : 'neutral'">
                        {{ $account->user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </x-ui.chip>
                </x-ui.td>
                <x-ui.td align="right">
                    <button type="button" wire:click="edit({{ $account->id }})"
                            class="text-[13px] font-medium text-link">Ubah</button>
                </x-ui.td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-5 py-lg text-center text-body-md text-muted">
                    @if ($search !== '')
                        Tidak ada AO yang cocok dengan &ldquo;{{ $search }}&rdquo;.
                    @else
                        Belum ada akun AO. Gunakan tombol Buat Akun AO untuk membuat yang pertama.
                    @endif
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-md">{{ $this->accounts->links() }}</div>

    @if ($creating || $editingId)
        <form wire:submit="save" class="mt-lg">
            <x-ui.card :title="$editingId ? 'Ubah Akun AO' : 'Buat Akun AO'">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                    <x-ui.field label="Nama Lengkap" required class="sm:col-span-2" :error="$errors->first('full_name')">
                        <x-ui.input wire:model="full_name" :invalid="$errors->has('full_name')" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir" required :error="$errors->first('birth_date')">
                        <x-ui.input wire:model="birth_date" type="date" :invalid="$errors->has('birth_date')" />
                    </x-ui.field>

                    <x-ui.field label="Nama User" required :error="$errors->first('username')"
                                helper="Harus unik. Digunakan untuk masuk.">
                        <x-ui.input wire:model="username" :invalid="$errors->has('username')" />
                    </x-ui.field>

                    <x-ui.field label="Alamat Email" :error="$errors->first('email')">
                        <x-ui.input wire:model="email" type="email" :invalid="$errors->has('email')" />
                    </x-ui.field>

                    <x-ui.field label="No. Handphone" :error="$errors->first('phone')" class="sm:col-span-2">
                        <x-ui.input wire:model="phone" type="tel" :invalid="$errors->has('phone')" />
                    </x-ui.field>
                </div>

                <label class="mt-md flex items-center gap-sm text-body-md text-body">
                    <input type="checkbox" wire:model="is_active" class="rounded-xs border-hairline">
                    Akun aktif
                </label>

                @unless ($editingId)
                    <x-ui.callout class="mt-md">
                        Kata sandi awal dibuat otomatis dan ditampilkan
                        <span class="font-medium">satu kali</span> setelah akun tersimpan.
                        Tidak ada cara menampilkannya kembali.
                    </x-ui.callout>
                @endunless

                <div class="mt-lg flex flex-wrap gap-sm border-t border-hairline pt-lg">
                    <x-ui.button type="submit" size="md" wire:loading.attr="disabled">
                        {{ $editingId ? 'Simpan Perubahan' : 'Buat Akun' }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" size="md" type="button" wire:click="cancel">Batal</x-ui.button>
                    <span wire:loading wire:target="save" class="self-center text-helper text-muted">Menyimpan…</span>
                </div>
            </x-ui.card>
        </form>
    @endif

</x-admin.accounts-shell>
</div>
