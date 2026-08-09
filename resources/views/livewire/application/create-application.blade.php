@use('App\Domain\Application\DebtorType')
@use('App\Domain\Application\FinancingProduct')
@use('App\Domain\Application\SpouseIncomeType')

<div class="bg-surface-soft">
    <div class="mx-auto max-w-[640px] px-lg py-xl md:px-xxl md:py-xxl">

        <p class="mb-md text-[13px] leading-[1.4] text-muted">
            <a href="{{ route('applications.index') }}" wire:navigate class="text-link">Aplikasi</a> / Buat
        </p>

        <h1 class="mb-2 font-display text-display-md text-ink">Buat Credit Application</h1>
        <p class="mb-lg text-[14px] leading-[1.6] text-muted">
            Input manual berdasarkan hasil simulasi yang diserahkan Referral. Amount Finance
            diinput ulang — nilai final dapat berbeda dari estimasi.
        </p>

        <form wire:submit="save">
            <x-ui.card>
                <div class="flex flex-col gap-5">

                    <x-ui.field label="Produk Pembiayaan" required :error="$errors->first('financing_product')">
                        <div class="flex flex-col gap-sm sm:flex-row">
                            @foreach (FinancingProduct::cases() as $product)
                                <button type="button" wire:click="$set('financing_product', '{{ $product->value }}')"
                                        @class([
                                            'flex flex-1 items-center gap-sm rounded-lg border px-[18px] py-3.5 text-left',
                                            'border-primary shadow-[0_0_0_1px_#181d26_inset]' => $financing_product === $product->value,
                                            'border-hairline' => $financing_product !== $product->value,
                                        ])>
                                    <span @class([
                                        'flex h-[18px] w-[18px] items-center justify-center rounded-pill border-[1.5px]',
                                        'border-primary' => $financing_product === $product->value,
                                        'border-border-strong' => $financing_product !== $product->value,
                                    ])>
                                        @if ($financing_product === $product->value)
                                            <span class="h-2 w-2 rounded-pill bg-primary"></span>
                                        @endif
                                    </span>
                                    <span @class([
                                        'text-[14px] font-medium leading-[1.4]',
                                        'text-ink' => $financing_product === $product->value,
                                        'text-muted' => $financing_product !== $product->value,
                                    ])>{{ $product->label() }}</span>
                                </button>
                            @endforeach
                        </div>
                    </x-ui.field>

                    {{-- Name, NIK, birth date. No other debtor field belongs here. --}}
                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        <x-ui.field label="Nama Debitur" required class="sm:col-span-2"
                                    :error="$errors->first('debtor_name')">
                            <x-ui.input wire:model="debtor_name" :invalid="$errors->has('debtor_name')" />
                        </x-ui.field>

                        <x-ui.field label="NIK Debitur" required :error="$errors->first('debtor_nik')">
                            <x-ui.input wire:model="debtor_nik" inputmode="numeric" maxlength="16"
                                        :invalid="$errors->has('debtor_nik')" />
                        </x-ui.field>

                        <x-ui.field label="Tanggal Lahir Debitur" required :error="$errors->first('debtor_birth_date')">
                            <x-ui.input wire:model="debtor_birth_date" type="date"
                                        :invalid="$errors->has('debtor_birth_date')" />
                        </x-ui.field>
                    </div>

                    {{-- Search, not a full dropdown — the account count is unbounded. --}}
                    <x-ui.field label="Referral" required :error="$errors->first('referral_id')"
                                helper="Pencarian akun — daftar lengkap Referral tidak dimuat ke klien.">
                        @if ($this->selectedReferral)
                            <div class="flex items-center gap-2.5 rounded-sm border border-success-border bg-canvas px-md py-3">
                                <div>
                                    <p class="text-[14px] font-medium leading-[1.4] text-ink">
                                        {{ $this->selectedReferral->full_name }}
                                    </p>
                                    <p class="text-helper text-muted">
                                        {{ $this->selectedReferral->category?->name }}
                                        @if ($this->selectedReferral->institution)
                                            · {{ $this->selectedReferral->institution->name }}
                                        @endif
                                    </p>
                                </div>
                                <button type="button" wire:click="clearReferral"
                                        class="ml-auto text-[13px] font-medium text-link">Ganti</button>
                            </div>
                        @else
                            <x-ui.input wire:model.live.debounce.400ms="referralSearch" type="search"
                                        placeholder="Ketik nama Referral…"
                                        :invalid="$errors->has('referral_id')" />

                            @if (mb_strlen(trim($referralSearch)) >= 2)
                                <div class="mt-2 overflow-hidden rounded-sm border border-hairline">
                                    @forelse ($this->referralResults as $result)
                                        <button type="button" wire:click="selectReferral({{ $result->id }})"
                                                class="flex w-full items-center gap-2.5 border-b border-divider px-md py-3 text-left last:border-b-0">
                                            <div>
                                                <p class="text-[14px] font-medium leading-[1.4] text-ink">
                                                    {{ $result->full_name }}
                                                </p>
                                                <p class="text-helper text-muted">
                                                    {{ $result->category?->name ?? 'Tanpa kategori' }}
                                                </p>
                                            </div>
                                        </button>
                                    @empty
                                        <p class="px-md py-3 text-body-md text-muted">
                                            Tidak ada Referral yang cocok dengan &ldquo;{{ $referralSearch }}&rdquo;.
                                        </p>
                                    @endforelse
                                </div>
                            @endif
                        @endif
                    </x-ui.field>

                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
                        <x-ui.field label="Type Debitur" required :error="$errors->first('debtor_type')">
                            <x-ui.select wire:model.live="debtor_type" :invalid="$errors->has('debtor_type')">
                                @foreach (DebtorType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        {{-- Not applicable to a legal entity; the database enforces NULL there. --}}
                        @if ($this->isIndividual)
                            <x-ui.field label="Konfirmasi Sumber Penghasilan Lainnya" required
                                        helper="Menentukan dokumen pasangan yang berlaku."
                                        :error="$errors->first('spouse_income_type')">
                                <x-ui.select wire:model.live="spouse_income_type"
                                             :invalid="$errors->has('spouse_income_type')">
                                    @foreach (SpouseIncomeType::cases() as $spouse)
                                        <option value="{{ $spouse->value }}">{{ $spouse->label() }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>
                        @else
                            <x-ui.field label="Konfirmasi Sumber Penghasilan Lainnya"
                                        helper="Tidak berlaku untuk Badan Hukum Usaha.">
                                <x-ui.input value="—" disabled class="bg-surface-soft text-muted" />
                            </x-ui.field>
                        @endif

                        <x-ui.field label="Amount Finance" helper="Opsional saat pembuatan. Gunakan nominal rupiah penuh."
                                    :error="$errors->first('amount_finance')">
                            <x-ui.money-input wire:model="amount_finance" placeholder="Rp 50.000.000"
                                              :invalid="$errors->has('amount_finance')" />
                        </x-ui.field>

                        {{-- Satu application selalu satu unit — client-decisions.md butir 15. --}}
                        <x-ui.field label="Jumlah Unit" helper="Satu application selalu mencakup satu unit.">
                            <x-ui.input value="1" disabled class="bg-surface-soft text-muted" />
                        </x-ui.field>
                    </div>

                    <x-ui.callout>
                        <span class="font-medium">Setelah tersimpan, sistem otomatis:</span>
                        membangkitkan Kode Aplikasi, menyusun Document Requirement sesuai Type Debitur
                        dan konfirmasi penghasilan, dan membuat sebelas tahapan berstatus Belum.
                    </x-ui.callout>

                    <div class="flex flex-wrap gap-sm">
                        <x-ui.button type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Simpan Aplikasi</span>
                            <span wire:loading wire:target="save">Menyimpan…</span>
                        </x-ui.button>
                        <x-ui.button variant="secondary" :href="route('applications.index')" wire:navigate>
                            Batal
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>
        </form>
    </div>
</div>
