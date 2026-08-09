<x-admin.configuration-shell title="Product dan Upping" :last-change="$this->lastChange">
    <div class="mb-md flex flex-wrap items-center gap-sm">
        <div>
            <h2 class="m-0 text-title-md text-ink">Product dan Upping</h2>
            <p class="mt-1 text-helper text-muted">Persentase diisi sebagai persen; database menyimpan pecahan.</p>
        </div>
        <x-ui.button type="button" size="md" class="ml-auto" wire:click="createProduct">Tambah Product</x-ui.button>
    </div>

    @if (session('admin_success'))
        <x-ui.callout class="mb-md">{{ session('admin_success') }}</x-ui.callout>
    @endif
    @error('configuration')
        <div class="mb-md rounded-md border border-signature-coral bg-danger-bg px-md py-3 text-[13px] text-signature-coral">{{ $message }}</div>
    @enderror

    <x-ui.callout class="mb-md">
        <span class="font-medium">Kosong ≠ nol.</span>
        Rate kosong berarti tenor tidak tersedia; angka 0 berarti tenor tersedia dengan rate 0%.
    </x-ui.callout>

    <div class="grid grid-cols-1 items-start gap-lg xl:grid-cols-[320px_1fr]">
        <x-ui.card title="Daftar Product" note="Pilih satu Product untuk disunting.">
            <div class="max-h-[680px] overflow-y-auto">
                @forelse ($products as $product)
                    @php
                        $rateByTenor = $product->rates->pluck('effective_rate', 'tenor_months');
                    @endphp

                    <button type="button" wire:click="edit({{ $product->id }})"
                            @class([
                                'block w-full border-b border-divider px-2 py-2.5 text-left',
                                'bg-surface-soft' => $productId === $product->id,
                            ])>
                        <span class="flex items-center gap-sm">
                            <span class="text-[13px] font-medium text-ink">{{ $product->name }}</span>
                            <span @class([
                                'ml-auto rounded-xs px-2 py-0.5 text-[11px]',
                                'bg-signature-mint text-ink' => $product->is_active,
                                'bg-surface-strong text-muted' => ! $product->is_active,
                            ])>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </span>

                        {{-- Availability per tenor, visible without opening the
                             product. A dashed outline reading "kosong" means the
                             tenor is unavailable; 0,0% means available at zero
                             rate. The two must never look alike (rule 3). --}}
                        <span class="mt-1.5 flex flex-wrap gap-1">
                            @foreach ($tenors as $tenor)
                                @php $rate = $rateByTenor[$tenor] ?? null; @endphp
                                <span @class([
                                    'rounded-xs border px-1.5 py-0.5 text-[10px] tabular-nums',
                                    'border-dashed border-border-strong text-border-strong' => $rate === null,
                                    'border-hairline text-ink' => $rate !== null,
                                ])
                                title="{{ $tenor }} bulan — {{ $rate === null ? 'tenor tidak tersedia' : 'tersedia' }}">
                                    {{ $tenor }}: {{ $rate === null ? 'kosong' : \App\Support\Format::percent((float) $rate) }}
                                </span>
                            @endforeach
                        </span>
                    </button>
                @empty
                    <p class="text-body-md text-muted">Belum ada Product. Tambahkan Product pertama.</p>
                @endforelse
            </div>
        </x-ui.card>

        <form wire:submit="save" class="flex flex-col gap-lg">
            <x-ui.card :title="$productId ? 'Ubah Product' : 'Tambah Product'">
                <div class="grid grid-cols-1 gap-md md:grid-cols-2">
                    <x-ui.field label="Nama Product" required class="md:col-span-2" :error="$errors->first('form.name')">
                        <x-ui.input wire:model="form.name" :invalid="$errors->has('form.name')" />
                    </x-ui.field>

                    @foreach ($tenors as $tenor)
                        <x-ui.field :label="$tenor.' Bulan (%)'" :error="$errors->first('form.rates.'.$tenor)">
                            <x-ui.input wire:model="form.rates.{{ $tenor }}" type="number" step="0.000001" min="0" max="100"
                                        placeholder="Kosong = tidak tersedia" :invalid="$errors->has('form.rates.'.$tenor)" />
                        </x-ui.field>
                    @endforeach

                    <x-ui.field label="DP (%)" required :error="$errors->first('form.dp_rate')">
                        <x-ui.input wire:model="form.dp_rate" type="number" step="0.0001" min="0" max="100" :invalid="$errors->has('form.dp_rate')" />
                    </x-ui.field>
                    <x-ui.field label="Admin Minimal (Rp)" required :error="$errors->first('form.admin_min')">
                        <x-ui.money-input wire:model="form.admin_min" placeholder="Rp 500.000" :invalid="$errors->has('form.admin_min')" />
                    </x-ui.field>
                    <x-ui.field label="Admin Maksimal (Rp)" required :error="$errors->first('form.admin_max')">
                        <x-ui.money-input wire:model="form.admin_max" placeholder="Rp 5.000.000" :invalid="$errors->has('form.admin_max')" />
                    </x-ui.field>
                    <x-ui.field label="Provisi (%)" required :error="$errors->first('form.provisi_rate')">
                        <x-ui.input wire:model="form.provisi_rate" type="number" step="0.0001" min="0" max="100" :invalid="$errors->has('form.provisi_rate')" />
                    </x-ui.field>
                    <x-ui.field label="Up ACP (%)" required :error="$errors->first('form.up_acp')">
                        <x-ui.input wire:model="form.up_acp" type="number" step="0.0001" min="0" max="100" :invalid="$errors->has('form.up_acp')" />
                    </x-ui.field>
                    <x-ui.field label="Up Rate (%)" required :error="$errors->first('form.up_rate')">
                        <x-ui.input wire:model="form.up_rate" type="number" step="0.0001" min="0" max="100" :invalid="$errors->has('form.up_rate')" />
                    </x-ui.field>
                    <x-ui.field label="Up Admin (Rp)" required :error="$errors->first('form.up_admin')">
                        <x-ui.money-input wire:model="form.up_admin" placeholder="Rp 0" :invalid="$errors->has('form.up_admin')" />
                    </x-ui.field>
                    <x-ui.field label="Up Provisi (%)" required :error="$errors->first('form.up_provisi')">
                        <x-ui.input wire:model="form.up_provisi" type="number" step="0.0001" min="0" max="100" :invalid="$errors->has('form.up_provisi')" />
                    </x-ui.field>
                    <label class="flex items-center gap-sm text-body-md text-body md:col-span-2">
                        <input wire:model="form.is_active" type="checkbox" class="rounded border-hairline">
                        Product aktif dan dapat diresolusi oleh simulasi
                    </label>
                </div>
            </x-ui.card>

            <div class="flex flex-wrap gap-sm">
                <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Product</x-ui.button>
                @if ($productId)
                    <x-ui.button type="button" variant="secondary" size="md" wire:click="deleteProduct"
                                 wire:confirm="Hapus Product nonaktif ini beserta seluruh rate tenor?">Hapus</x-ui.button>
                @endif
                <span class="self-center text-helper text-muted" wire:loading>Memvalidasi konfigurasi…</span>
            </div>
        </form>
    </div>
</x-admin.configuration-shell>
