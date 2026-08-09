@use('App\Domain\Application\DebtorType')
@use('App\Domain\Application\DocumentStatus')
@use('App\Domain\Application\FinancingProduct')
@use('App\Domain\Application\SpouseIncomeType')
@use('App\Domain\Application\TrackingStatus')
@use('App\Support\Format')

<div class="mx-auto max-w-[1080px] px-lg py-xl md:px-xxl md:py-xxl">

    <x-ui.back-link :href="route('applications.index')" wire:navigate class="mb-md" />

    <div class="mb-lg flex flex-wrap items-center gap-md">
        <h1 class="m-0 font-display text-display-md text-ink">{{ $application->code }}</h1>
        <x-ui.chip :tone="$application->go_live_date ? 'success' : 'neutral'" class="px-3 py-1.5 text-[13px]">
            {{ $application->go_live_date ? 'Go Live' : 'Pipe Line' }}
        </x-ui.chip>
    </div>

    @if (session('application_success'))
        <x-ui.callout class="mb-lg">{{ session('application_success') }}</x-ui.callout>
    @endif

    {{-- ---------------------------------------------------------------- Data --}}
    <x-ui.card title="Data" class="mb-lg">
        @if ($this->canEdit && ! $editing)
            <x-slot:actions>
                <x-ui.button variant="secondary" size="md" wire:click="edit">Ubah Data</x-ui.button>
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

                    <x-ui.field label="NIK Debitur" required :error="$errors->first('debtor_nik')">
                        <x-ui.input wire:model="debtor_nik" inputmode="numeric" maxlength="16"
                                    :invalid="$errors->has('debtor_nik')" />
                    </x-ui.field>

                    <x-ui.field label="Tanggal Lahir Debitur" required :error="$errors->first('debtor_birth_date')">
                        <x-ui.input wire:model="debtor_birth_date" type="date"
                                    :invalid="$errors->has('debtor_birth_date')" />
                    </x-ui.field>

                    <x-ui.field label="Type Debitur" required :error="$errors->first('debtor_type')">
                        <x-ui.select wire:model.live="debtor_type" :invalid="$errors->has('debtor_type')">
                            @foreach (DebtorType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

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
                <x-ui.key-value label="NIK">{{ $application->debtor_nik }}</x-ui.key-value>
                <x-ui.key-value label="Tanggal Lahir">
                    {{ Format::date($application->debtor_birth_date) }}
                </x-ui.key-value>
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
        @if ($this->canEdit)
            <x-slot:actions>
                <span class="text-helper text-muted">Klik untuk menandai status</span>
            </x-slot:actions>
        @endif

        <div class="flex flex-col gap-sm md:hidden">
            @foreach ($this->documents as $document)
                <div class="rounded-lg border border-hairline bg-canvas px-md py-3"
                     wire:key="document-mobile-{{ $document->id }}">
                    <div class="flex items-start justify-between gap-md">
                        <div>
                            <p class="text-body-md text-ink">{{ $document->requirement->name }}</p>
                            <p class="mt-1 text-[13px] text-muted">{{ $document->requirement->subject }}</p>
                        </div>

                        @if ($this->canEdit)
                            <span class="inline-flex shrink-0 overflow-hidden rounded-sm border border-hairline">
                                <button type="button"
                                        wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Belum->value }}')"
                                        @class([
                                            'px-3.5 py-1.5 text-[12px] font-medium leading-[1.2]',
                                            'bg-muted text-canvas' => ! $document->status->isComplete(),
                                            'bg-canvas text-muted' => $document->status->isComplete(),
                                        ])>&#10007; Belum</button>
                                <button type="button"
                                        wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Lengkap->value }}')"
                                        @class([
                                            'border-l border-hairline px-3.5 py-1.5 text-[12px] font-medium leading-[1.2]',
                                            'bg-primary text-on-primary' => $document->status->isComplete(),
                                            'bg-canvas text-muted' => ! $document->status->isComplete(),
                                        ])>&#10003; Lengkap</button>
                            </span>
                        @else
                            <x-ui.chip :tone="$document->status->isComplete() ? 'success' : 'neutral'" class="shrink-0">
                                {{ $document->status->isComplete() ? '✓ Lengkap' : '✕ Belum' }}
                            </x-ui.chip>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <x-ui.table min-width="560px" class="hidden md:block">
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
                        @if ($this->canEdit)
                            <span class="inline-flex overflow-hidden rounded-sm border border-hairline">
                                <button type="button"
                                        wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Belum->value }}')"
                                        @class([
                                            'px-3.5 py-1.5 text-[12px] font-medium leading-[1.2]',
                                            'bg-muted text-canvas' => ! $document->status->isComplete(),
                                            'bg-canvas text-muted' => $document->status->isComplete(),
                                        ])>&#10007; Belum</button>
                                <button type="button"
                                        wire:click="setDocumentStatus({{ $document->id }}, '{{ DocumentStatus::Lengkap->value }}')"
                                        @class([
                                            'border-l border-hairline px-3.5 py-1.5 text-[12px] font-medium leading-[1.2]',
                                            'bg-primary text-on-primary' => $document->status->isComplete(),
                                            'bg-canvas text-muted' => ! $document->status->isComplete(),
                                        ])>&#10003; Lengkap</button>
                            </span>
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
        @if ($this->canEdit)
            <x-slot:actions>
                <span class="text-helper text-muted">Dapat ditandai tanpa urutan</span>
            </x-slot:actions>
        @endif

        <div class="flex flex-col">
            @foreach ($this->trackings as $tracking)
                <div class="flex items-center gap-md border-b border-divider px-2 py-3"
                     wire:key="tracking-{{ $tracking->id }}">
                    <span class="w-6 shrink-0 text-[13px] font-medium tabular-nums text-muted">
                        {{ $tracking->stage_no }}
                    </span>
                    <span class="flex-1 text-body-md text-body">{{ $tracking->stage->name }}</span>

                    @if ($this->canEdit)
                        {{-- Never disabled because an earlier stage is unfinished. --}}
                        <button type="button" wire:click="toggleStage({{ $tracking->stage_no }})">
                            <x-ui.chip :tone="$tracking->status->isDone() ? 'success' : 'neutral'">
                                {{ $tracking->status->isDone() ? '✓ Selesai' : '✕ Belum' }}
                            </x-ui.chip>
                        </button>
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
             wire:click="cancelGoLive">
            <div class="max-w-[460px] rounded-lg bg-canvas p-xl shadow-[0_24px_64px_rgba(13,18,24,0.25)]"
                 wire:click.stop>
                <p class="mb-sm text-title-sm text-ink">Tandai Golive &amp; Payment sebagai Selesai?</p>
                <p class="mb-lg text-[14px] leading-[1.7] text-body">
                    Menandai Golive &amp; Payment akan mencatat Tanggal Go Live dan memindahkan
                    aplikasi ini ke Actual Lending.
                </p>
                <div class="flex justify-end gap-sm">
                    <x-ui.button variant="secondary" size="md" wire:click="cancelGoLive">Batal</x-ui.button>
                    <x-ui.button size="md" wire:click="confirmGoLive">Tandai Selesai</x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
