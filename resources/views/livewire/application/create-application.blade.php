@use('App\Domain\Application\DebtorType')
@use('App\Domain\Application\FinancingProduct')
@use('App\Domain\Application\SpouseIncomeType')

<div class="bg-surface-soft">
    <div class="mx-auto max-w-[960px] px-lg py-xl md:px-xxl md:py-xxl">

        <x-ui.back-link :href="route('applications.index')" wire:navigate class="mb-md" />

        <h1 class="mb-2 font-display text-display-md text-ink">Buat Credit Application</h1>
        <p class="mb-lg max-w-[680px] text-[14px] leading-[1.6] text-muted">
            Input manual berdasarkan hasil simulasi yang diserahkan Referral. Amount Finance
            diinput ulang - nilai final dapat berbeda dari estimasi.
        </p>

        <form wire:submit="save" class="rounded-lg border border-hairline bg-canvas p-lg md:p-xl">
            <div class="flex flex-col gap-8">
                <section class="flex flex-col gap-md">
                    <p class="text-eyebrow uppercase text-muted">Produk</p>

                    <x-ui.field label="Produk Pembiayaan" required :error="$errors->first('financing_product')">
                        <div class="grid grid-cols-1 gap-sm sm:grid-cols-2">
                            @foreach (FinancingProduct::cases() as $product)
                                <button type="button" wire:click="$set('financing_product', '{{ $product->value }}')"
                                        data-motion-action
                                        @class([
                                            'flex min-h-[54px] items-center gap-sm rounded-lg border px-[18px] py-3.5 text-left transition-colors',
                                            'border-primary shadow-[0_0_0_1px_#181d26_inset]' => $financing_product === $product->value,
                                            'border-hairline hover:border-border-strong' => $financing_product !== $product->value,
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
                </section>

                {{-- Name plus identity fields that depend on debtor type. --}}
                <section class="flex flex-col gap-md border-t border-hairline pt-lg">
                    <p class="text-eyebrow uppercase text-muted">Data Debitur</p>

                    <div class="grid grid-cols-1 gap-md sm:grid-cols-2 lg:grid-cols-3">
                        <x-ui.field label="Nama Debitur" required class="sm:col-span-2 lg:col-span-3"
                                    :error="$errors->first('debtor_name')">
                            <x-ui.input wire:model="debtor_name" :invalid="$errors->has('debtor_name')" />
                        </x-ui.field>

                        <x-ui.field label="Type Debitur" required :error="$errors->first('debtor_type')">
                            <x-ui.select wire:model.live="debtor_type" :invalid="$errors->has('debtor_type')">
                                @foreach (DebtorType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        @if ($this->isIndividual)
                            <x-ui.field label="NIK Debitur" required :error="$errors->first('debtor_nik')">
                                <x-ui.input wire:model="debtor_nik" inputmode="numeric" maxlength="16"
                                            :invalid="$errors->has('debtor_nik')" />
                            </x-ui.field>

                            <x-ui.field label="Tanggal Lahir Debitur" required :error="$errors->first('debtor_birth_date')">
                                <x-ui.input wire:model="debtor_birth_date" type="date"
                                            :invalid="$errors->has('debtor_birth_date')" />
                            </x-ui.field>
                        @endif
                    </div>
                </section>

                <section class="flex flex-col gap-md border-t border-hairline pt-lg">
                    <p class="text-eyebrow uppercase text-muted">Referral dan Pembiayaan</p>

                    {{-- Search, not a full dropdown - the account count is unbounded. --}}
                    <x-ui.field label="Referral" required :error="$errors->first('referral_id')">
                        @if ($this->selectedReferral)
                            <div class="flex min-h-[58px] items-center gap-2.5 rounded-lg border border-success-border bg-canvas px-md py-3">
                                <div>
                                    <p class="text-[14px] font-medium leading-[1.4] text-ink">
                                        {{ $this->selectedReferral->full_name }}
                                    </p>
                                    <p class="text-helper text-muted">
                                        {{ $this->selectedReferral->category?->name }}
                                        @if ($this->selectedReferral->institution)
                                            &middot; {{ $this->selectedReferral->institution->name }}
                                        @endif
                                    </p>
                                </div>
                                <button type="button" wire:click="clearReferral"
                                        data-motion-action
                                        class="ml-auto inline-flex min-h-10 items-center rounded-sm px-3 text-[13px] font-medium text-link">
                                    Ganti
                                </button>
                            </div>
                        @else
                            <div class="relative">
                                <x-ui.input wire:model.live.debounce.400ms="referralSearch" type="search"
                                            placeholder="Ketik nama Referral..."
                                            :invalid="$errors->has('referral_id')" />

                                @if (mb_strlen(trim($referralSearch)) >= 2)
                                    <div class="absolute left-0 right-0 top-full z-30 mt-2 overflow-hidden rounded-lg border border-hairline bg-canvas shadow-[0_18px_42px_rgba(13,18,24,0.12)]"
                                         role="listbox">
                                        <p class="border-b border-divider px-md py-2 text-helper font-medium uppercase tracking-[0.08em] text-muted">
                                            Hasil pencarian
                                        </p>
                                        @forelse ($this->referralResults as $result)
                                            <button type="button" wire:click="selectReferral({{ $result->id }})"
                                                    data-motion-action
                                                    class="group flex min-h-[56px] w-full cursor-pointer items-center gap-3 border-b border-divider px-md py-3 text-left transition-colors hover:bg-surface-soft focus:bg-surface-soft last:border-b-0"
                                                    role="option">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-pill bg-signature-mint text-[12px] font-medium text-ink">
                                                    {{ Str::of($result->full_name)->explode(' ')->take(2)->map(fn ($word) => Str::substr($word, 0, 1))->implode('') }}
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-[14px] font-medium leading-[1.4] text-ink">
                                                        {{ $result->full_name }}
                                                    </span>
                                                    <span class="block truncate text-helper text-muted">
                                                        {{ $result->category?->name ?? 'Tanpa kategori' }}
                                                        @if ($result->institution)
                                                            &middot; {{ $result->institution->name }}
                                                        @endif
                                                    </span>
                                                </span>
                                                <span class="ml-auto text-[13px] font-medium text-link opacity-0 transition-opacity group-hover:opacity-100 group-focus:opacity-100">
                                                    Pilih
                                                </span>
                                            </button>
                                        @empty
                                            <p class="px-md py-3 text-body-md text-muted">
                                                Tidak ada Referral yang cocok dengan &ldquo;{{ $referralSearch }}&rdquo;.
                                            </p>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endif
                    </x-ui.field>

                    <div class="grid grid-cols-1 gap-md lg:grid-cols-3">
                        @if ($this->isIndividual)
                            <x-ui.field label="Konfirmasi Sumber Penghasilan Lainnya" required
                                        helper="Menentukan dokumen pasangan yang berlaku."
                                        :error="$errors->first('spouse_income_type')"
                                        class="application-finance-field">
                                <x-ui.select wire:model.live="spouse_income_type"
                                             :invalid="$errors->has('spouse_income_type')">
                                    @foreach (SpouseIncomeType::cases() as $spouse)
                                        <option value="{{ $spouse->value }}">{{ $spouse->label() }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.field>
                        @else
                            <x-ui.field label="Konfirmasi Sumber Penghasilan Lainnya"
                                        helper="Tidak berlaku untuk Badan Hukum Usaha."
                                        class="application-finance-field">
                                <x-ui.input value="-" disabled class="bg-surface-soft text-muted" />
                            </x-ui.field>
                        @endif

                        <x-ui.field label="Amount Finance" helper="Opsional saat pembuatan. Gunakan nominal rupiah penuh."
                                    :error="$errors->first('amount_finance')"
                                    class="application-finance-field">
                            <x-ui.money-input wire:model="amount_finance" placeholder="Rp 50.000.000"
                                              :invalid="$errors->has('amount_finance')" />
                        </x-ui.field>

                        {{-- Satu application selalu satu unit - client-decisions.md butir 15. --}}
                        <x-ui.field label="Jumlah Unit" helper="Satu application selalu mencakup satu unit."
                                    class="application-finance-field">
                            <x-ui.input value="1" disabled class="bg-surface-soft text-muted" />
                        </x-ui.field>
                    </div>
                </section>

                <div class="flex flex-wrap gap-sm border-t border-hairline pt-lg">
                    <x-ui.button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Simpan Aplikasi</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('applications.index')" wire:navigate>
                        Batal
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>
</div>
