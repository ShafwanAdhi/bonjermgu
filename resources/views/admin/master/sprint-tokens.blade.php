<x-admin.master-shell title="Token SPRINT" :last-change="$this->lastChange">
    <form wire:submit="save" class="flex flex-col gap-lg">
        @if (session('admin_success'))
            <x-ui.callout>{{ session('admin_success') }}</x-ui.callout>
        @endif

        <x-ui.callout>
            View Sprint mengeja Product ID dan Product Offering dari satu token per dimensi:<br>
            <span class="font-mono text-[12px]">Product ID = Product + Kanal + Jenis Kendaraan + Profil Debitur + Type Debitur — Jenis Angsuran</span><br>
            <span class="font-mono text-[12px]">Offering = Product + Wilayah + Kanal + Jenis Kendaraan + Brand + Profil Debitur + Type Debitur + DP + Tenor — Jenis Angsuran</span>
        </x-ui.callout>

        <div class="grid grid-cols-1 items-start gap-lg lg:grid-cols-2">
            @foreach (\App\Models\SprintToken::GROUPS as $group => $label)
                @php
                    $mapsChannel = $group === 'channel_source';
                    $usesProductToken = in_array($group, [...\App\Models\SprintToken::PRODUCT_ID_PARTS, 'instalment'], true);
                    // Kelas ditulis utuh: Tailwind memindai teks sumber, bukan hasil interpolasi.
                    $columns = $usesProductToken ? 'grid-cols-[28px_1fr_1fr_1fr_auto]' : 'grid-cols-[28px_1fr_1fr_auto]';
                @endphp

                {{-- Tiga kolom teks tidak muat separuh lebar; token terpanjang 33 karakter. --}}
                <x-ui.card @class(['lg:col-span-2' => $usesProductToken])
                           :title="$label"
                           :note="$mapsChannel
                               ? 'Menentukan Kanal mana yang terisi di muka saat AO memilih sub kategori itu. Sub kategori di luar daftar ini dibiarkan kosong untuk diisi AO.'
                               : 'Urutan menentukan tampilan dropdown View Sprint.'">
                    <div class="grid {{ $columns }} items-center gap-sm border-b border-divider pb-1 text-helper text-muted">
                        <span></span>
                        <span>{{ $mapsChannel ? 'Sub Kategori' : 'Pilihan' }}</span>
                        @if ($usesProductToken)
                            <span>Token Product ID</span>
                        @endif
                        <span>{{ $mapsChannel ? 'Kanal' : 'Token Offering' }}</span>
                        <span></span>
                    </div>

                    @foreach ($groups[$group] ?? [] as $index => $row)
                        <div class="grid {{ $columns }} items-center gap-sm border-b border-divider py-2"
                             wire:key="token-{{ $group }}-{{ $row['id'] ?? 'new-'.$index }}">
                            <span class="text-helper tabular-nums text-muted">{{ $index + 1 }}</span>
                            <x-ui.input wire:model="groups.{{ $group }}.{{ $index }}.source"
                                        :invalid="$errors->has('groups.'.$group.'.'.$index.'.source')" />
                            @if ($usesProductToken)
                                <x-ui.input wire:model="groups.{{ $group }}.{{ $index }}.product_token"
                                            :invalid="$errors->has('groups.'.$group.'.'.$index.'.product_token')" />
                            @endif
                            <x-ui.input wire:model="groups.{{ $group }}.{{ $index }}.offering_token"
                                        :invalid="$errors->has('groups.'.$group.'.'.$index.'.offering_token')" />
                            <div class="flex gap-1 text-[12px]">
                                <button type="button" wire:click="moveToken('{{ $group }}', {{ $index }}, -1)" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-link" aria-label="Naikkan {{ $label }} {{ $index + 1 }}">↑</button>
                                <button type="button" wire:click="moveToken('{{ $group }}', {{ $index }}, 1)" class="inline-flex h-8 w-8 items-center justify-center rounded-sm text-link" aria-label="Turunkan {{ $label }} {{ $index + 1 }}">↓</button>
                                <button type="button" wire:click="removeToken('{{ $group }}', {{ $index }})" class="text-signature-coral">Hapus</button>
                            </div>
                        </div>
                    @endforeach

                    <button type="button" wire:click="addToken('{{ $group }}')" class="mt-sm text-[13px] font-medium text-link">+ Tambah {{ $label }}</button>
                </x-ui.card>
            @endforeach
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">
                Data belum disimpan.
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center gap-sm">
            <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Token SPRINT</x-ui.button>
            <span wire:loading class="text-helper text-muted">Menyimpan…</span>
        </div>
    </form>
</x-admin.master-shell>
