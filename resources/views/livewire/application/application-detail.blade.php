@use('App\Domain\Application\DebtorType')
@use('App\Domain\Application\DocumentStatus')
@use('App\Domain\Application\FinancingProduct')
@use('App\Domain\Application\SpouseIncomeType')
@use('App\Domain\Application\TrackingStatus')
@use('App\Support\Format')

<div class="mx-auto max-w-[1080px] px-lg py-xl md:px-xxl md:py-xxl">

    <x-ui.back-link :href="route('applications.index')" wire:navigate class="mb-md" />

    <div class="mb-lg flex flex-col items-start gap-2">
        <h1 class="m-0 font-display text-display-md text-ink">{{ $application->code }}</h1>
        <x-ui.chip :tone="$application->statusTone()" class="px-3 py-1.5 text-[13px]">
            {{ $application->statusLabel() }}
        </x-ui.chip>
    </div>

    @if (session('application_success'))
        <x-ui.callout class="mb-lg">{{ session('application_success') }}</x-ui.callout>
    @endif

    {{-- ---------------------------------------------------------------- Data --}}
    <x-ui.card title="Data" class="mb-lg">
        @if ($this->canEdit && ! $editing)
            <x-slot:actions>
                <div class="grid w-full grid-cols-2 gap-sm sm:flex sm:w-auto sm:flex-wrap sm:justify-end">
                    @if (! $application->isCanceled())
                        <x-ui.button variant="secondary" size="md" wire:click="edit"
                                     class="min-h-10 w-full whitespace-nowrap !px-2.5 !py-2 text-[12px] leading-none sm:w-auto sm:!px-5 sm:!py-[11px] sm:text-button">
                            Ubah Data
                        </x-ui.button>
                    @endif

                    @if (! $application->isGoLive() && ! $application->isCanceled())
                        <x-ui.button variant="secondary" size="md" wire:click="askCancelApplication"
                                     class="min-h-10 w-full whitespace-nowrap !px-2.5 !py-2 text-[12px] leading-none text-red-700 sm:w-auto sm:!px-5 sm:!py-[11px] sm:text-button">
                            Batalkan Aplikasi
                        </x-ui.button>
                    @elseif ($application->isCanceled())
                        <x-ui.button variant="secondary" size="md" wire:click="restoreApplication"
                                     class="col-span-2 min-h-10 w-full !px-2.5 !py-2 text-[12px] leading-none sm:w-auto sm:!px-5 sm:!py-[11px] sm:text-button">
                            Aktifkan Kembali
                        </x-ui.button>
                    @endif
                </div>
            </x-slot:actions>
        @endif

        @if ($editing)
            <form wire:submit="save" class="flex flex-col gap-md">
                <div class="grid grid-cols-1 gap-md sm:grid-cols-2 lg:grid-cols-3">
                    <x-ui.field label="Produk Pembiayaan" required
                                :error="$errors->first('financing_product')"
                                :helper="$application->go_live_date ? 'Terkunci setelah Go Live.' : null">
                        <x-ui.select wire:model="financing_product" :disabled="(bool) $application->go_live_date"
                                     :invalid="$errors->has('financing_product')">
                            @foreach (FinancingProduct::cases() as $product)
                                <option value="{{ $product->value }}">{{ $product->label() }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Nama Debitur" required :error="$errors->first('debtor_name')">
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

                    @if ($this->isIndividual)
                        <x-ui.field label="Konfirmasi Sumber Penghasilan Lainnya" required
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

                    <x-ui.field label="Amount Finance" helper="Nominal rupiah penuh."
                                :error="$errors->first('amount_finance')">
                        <x-ui.money-input wire:model="amount_finance" placeholder="Rp 50.000.000"
                                          :invalid="$errors->has('amount_finance')" />
                    </x-ui.field>

                    {{-- Satu application selalu satu unit — client-decisions.md butir 15. --}}
                    <x-ui.field label="Jumlah Unit" helper="Satu application selalu mencakup satu unit.">
                        <x-ui.input value="1" disabled class="bg-surface-soft text-muted" />
                    </x-ui.field>
                </div>

                {{-- Warned before the change is saved, not after — pages.md §11. --}}
                @if ($this->determinantsChanged)
                    <x-ui.callout tone="warning">
                        Mengubah Type Debitur akan menyusun ulang daftar dokumen.
                        Dokumen yang tidak lagi berlaku akan dihapus statusnya.
                    </x-ui.callout>
                @endif

                <div class="flex flex-wrap gap-sm border-t border-hairline pt-lg">
                    <x-ui.button type="submit" size="md" wire:loading.attr="disabled">Simpan Perubahan</x-ui.button>
                    <x-ui.button variant="secondary" size="md" type="button" wire:click="cancelEdit">Batal</x-ui.button>
                    <span wire:loading wire:target="save" class="self-center text-helper text-muted">Menyimpan…</span>
                </div>
            </form>
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.key-value label="Produk Pembiayaan"
                                :note="$application->go_live_date ? 'Terkunci setelah Go Live' : null">
                    {{ $application->financing_product->label() }}
                </x-ui.key-value>
                <x-ui.key-value label="Nama Debitur">{{ $application->debtor_name }}</x-ui.key-value>
                @if ($application->debtor_type->isIndividual())
                    <x-ui.key-value label="NIK">{{ $application->debtor_nik }}</x-ui.key-value>
                    <x-ui.key-value label="Tanggal Lahir">
                        {{ Format::date($application->debtor_birth_date) }}
                    </x-ui.key-value>
                @endif
                <x-ui.key-value label="Referral">
                    {{ $application->referral?->full_name ?? '—' }}
                    <span class="block text-helper text-muted">
                        {{ $application->referral?->category?->name }}
                        @if ($application->referral?->institution)
                            · {{ $application->referral->institution->name }}
                        @endif
                    </span>
                </x-ui.key-value>
                <x-ui.key-value label="Nama AO">{{ $application->accountOfficer?->full_name ?? '—' }}</x-ui.key-value>
                <x-ui.key-value label="Type Debitur">{{ $application->debtor_type->label() }}</x-ui.key-value>
                <x-ui.key-value label="Konfirmasi Sumber Penghasilan Lainnya">
                    {{ $application->spouse_income_type?->label() ?? '—' }}
                </x-ui.key-value>
                <x-ui.key-value label="Amount Finance">
                    {{ Format::rupiah($application->amount_finance, 'Belum diisi') }}
                </x-ui.key-value>
                <x-ui.key-value label="Jumlah Unit">{{ $application->unit_count }}</x-ui.key-value>

                @if ($application->go_live_date)
                    <x-ui.key-value label="Tanggal Go Live" tone="success">
                        {{ Format::date($application->go_live_date) }}
                    </x-ui.key-value>
                @endif
            </div>
        @endif
    </x-ui.card>

    {{-- ------------------------------------------------------------- Dokumen --}}
    <x-ui.card title="Dokumen" :meta="$this->documentSummary.' lengkap'" class="mb-lg">

        <div class="flex flex-col gap-sm md:hidden">
            @foreach ($this->documents as $document)
                <div class="rounded-lg border border-hairline bg-canvas px-md py-3"
                     wire:key="document-mobile-{{ $document->id }}">
                    <div class="flex items-start justify-between gap-md">
                        <div>
                            <p class="text-body-md text-ink">{{ $document->requirement->name }}</p>
                            <p class="mt-1 text-[13px] text-muted">{{ $document->requirement->subject }}</p>
                        </div>

                        @if ($this->canEdit && ! $application->isCanceled())
                            <x-ui.status-toggle :is-active="$document->status->isComplete()"
                                                :label="'Status dokumen '.$document->requirement->name"
                                                active-label="Lengkap">
                                <x-slot:inactive wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Belum->value }}')"></x-slot:inactive>
                                <x-slot:active wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Lengkap->value }}')"></x-slot:active>
                            </x-ui.status-toggle>
                        @else
                            <x-ui.chip :tone="$document->status->isComplete() ? 'success' : 'neutral'" class="shrink-0">
                                {{ $document->status->isComplete() ? '✓ Lengkap' : '✕ Belum' }}
                            </x-ui.chip>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <x-ui.table min-width="560px" label="Daftar dokumen aplikasi" class="hidden md:block">
            <x-slot:head>
                <x-ui.th>Dokumen</x-ui.th>
                <x-ui.th>Subjek</x-ui.th>
                <x-ui.th align="right">Status</x-ui.th>
            </x-slot:head>

            {{-- Requirements that do not apply have no row at all. There is no
                 status meaning "tidak berlaku" — document-requirement.md §4. --}}
            @foreach ($this->documents as $document)
                <tr wire:key="document-{{ $document->id }}">
                    <x-ui.td>{{ $document->requirement->name }}</x-ui.td>
                    <x-ui.td class="text-[13px] text-muted">{{ $document->requirement->subject }}</x-ui.td>
                    <x-ui.td align="right">
                        @if ($this->canEdit && ! $application->isCanceled())
                            <x-ui.status-toggle :is-active="$document->status->isComplete()"
                                                :label="'Status dokumen '.$document->requirement->name"
                                                active-label="Lengkap">
                                <x-slot:inactive wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Belum->value }}')"></x-slot:inactive>
                                <x-slot:active wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Lengkap->value }}')"></x-slot:active>
                            </x-ui.status-toggle>
                        @else
                            <x-ui.chip :tone="$document->status->isComplete() ? 'success' : 'neutral'">
                                {{ $document->status->isComplete() ? '✓ Lengkap' : '✕ Belum' }}
                            </x-ui.chip>
                        @endif
                    </x-ui.td>
                </tr>
            @endforeach
        </x-ui.table>
    </x-ui.card>

    {{-- ------------------------------------------------------------ Tracking --}}
    <x-ui.card title="Tracking" :meta="$this->trackingSummary.' selesai'">
        <div class="flex flex-col">
            @foreach ($this->trackings as $tracking)
                <div class="flex items-center gap-md border-b border-divider px-2 py-3"
                     wire:key="tracking-{{ $tracking->id }}">
                    <span class="w-6 shrink-0 text-[13px] font-medium tabular-nums text-muted">
                        {{ $tracking->stage_no }}
                    </span>
                    <span class="flex-1 text-body-md text-body">{{ $tracking->stage->name }}</span>

                    @if ($this->canEdit && ! $application->isCanceled())
                        {{-- Never disabled because an earlier stage is unfinished. --}}
                        <x-ui.status-toggle :is-active="$tracking->status->isDone()"
                                            :label="'Status tracking '.$tracking->stage->name"
                                            active-label="Selesai">
                            <x-slot:inactive wire:click="toggleStage({{ $tracking->stage_no }})"></x-slot:inactive>
                                {{ $tracking->status->isDone() ? '✓ Selesai' : '✕ Belum' }}
                            <x-slot:active wire:click="toggleStage({{ $tracking->stage_no }})"></x-slot:active>
                        </x-ui.status-toggle>
                    @else
                        <x-ui.chip :tone="$tracking->status->isDone() ? 'success' : 'neutral'">
                            {{ $tracking->status->isDone() ? '✓ Selesai' : '✕ Belum' }}
                        </x-ui.chip>
                    @endif
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Go Live confirmation — it changes the Lending classification. --}}
    @if ($confirmingGoLive)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-primary/45 p-lg"
             role="dialog"
             aria-modal="true"
             aria-labelledby="confirm-go-live-title"
             aria-describedby="confirm-go-live-copy"
             tabindex="-1"
             x-data
             x-init="$nextTick(() => $el.focus())"
             x-on:keydown.escape.window="$wire.cancelGoLive()"
             wire:click="cancelGoLive">
            <div class="max-w-[460px] rounded-lg bg-canvas p-xl shadow-[0_24px_64px_rgba(13,18,24,0.25)]"
                 wire:click.stop>
                <p id="confirm-go-live-title" class="mb-sm text-title-sm text-ink">Tandai Golive &amp; Payment sebagai Selesai?</p>
                <p id="confirm-go-live-copy" class="mb-lg text-[14px] leading-[1.7] text-body">
                    Menandai Golive &amp; Payment akan mencatat Tanggal Go Live dan memindahkan
                    aplikasi ini ke Actual Lending.
                </p>

                {{-- Asked here so an Actual Lending row is never counted against Rp 0. --}}
                <x-ui.field label="Amount Finance" required
                            helper="Nilai yang masuk laporan Actual Lending."
                            :error="$errors->first('goLiveAmountFinance')"
                            class="mb-lg">
                    <x-ui.money-input wire:model="goLiveAmountFinance" placeholder="Rp 50.000.000"
                                      :invalid="$errors->has('goLiveAmountFinance')" />
                </x-ui.field>

                <div class="flex justify-end gap-sm">
                    <x-ui.button variant="secondary" size="md" wire:click="cancelGoLive">Batal</x-ui.button>
                    <x-ui.button size="md" wire:click="confirmGoLive">Tandai Selesai</x-ui.button>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmingCancel)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-primary/45 p-lg"
             role="dialog"
             aria-modal="true"
             aria-labelledby="confirm-cancel-title"
             aria-describedby="confirm-cancel-copy"
             tabindex="-1"
             x-data
             x-init="$nextTick(() => $el.focus())"
             x-on:keydown.escape.window="$wire.cancelCancelApplication()"
             wire:click="cancelCancelApplication">
            <div class="max-w-[460px] rounded-lg bg-canvas p-xl shadow-[0_24px_64px_rgba(13,18,24,0.25)]"
                 wire:click.stop>
                <p id="confirm-cancel-title" class="mb-sm text-title-sm text-ink">Batalkan aplikasi ini?</p>
                <p id="confirm-cancel-copy" class="mb-lg text-[14px] leading-[1.7] text-body">
                    Aplikasi akan keluar dari status Pipe Line dan tampil sebagai Canceled pada daftar aplikasi.
                </p>
                <div class="flex justify-end gap-sm">
                    <x-ui.button variant="secondary" size="md" wire:click="cancelCancelApplication">Batal</x-ui.button>
                    <x-ui.button size="md" wire:click="confirmCancelApplication">Batalkan Aplikasi</x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
