{{--
    Download Hasil Simulasi — docs/credit-simulation.md section 14.
    No intermediate values and no configuration parameters appear here.
--}}
<x-layouts.print title="Download Simulasi — {{ $subject['debtor_name'] }}">
    <div class="mx-auto max-w-[820px] p-lg md:p-xxl">

        <div class="no-print mb-lg flex flex-wrap items-center gap-sm">
            <x-ui.back-link :href="route('simulation')" />
            <x-ui.button size="md" :href="route('simulation.print.download')">Download Simulasi Kredit</x-ui.button>
        </div>

        <div class="rounded-lg border border-hairline bg-canvas p-lg md:p-xxl print:rounded-none print:border-0 print:p-0">

            <div class="mb-xl flex items-start gap-md border-b border-hairline pb-lg">
                <x-ui.wordmark />
                <div class="ml-auto text-right">
                    <p class="text-eyebrow uppercase text-muted">Hasil Simulasi Kredit</p>
                    <p class="text-[13px] leading-[1.5] text-muted">{{ $subject['printed_at'] }}</p>
                </div>
            </div>

            <div class="mb-xl grid grid-cols-1 gap-lg sm:grid-cols-3">
                <x-ui.key-value label="Nama Calon Debitur">{{ $subject['debtor_name'] }}</x-ui.key-value>
                @if ($subject['debtor_nik'] ?? null)
                    <x-ui.key-value label="NIK">{{ $subject['debtor_nik'] }}</x-ui.key-value>
                @endif
                @if ($subject['debtor_birth_date'] ?? null)
                    <x-ui.key-value label="Tanggal Lahir">{{ $subject['debtor_birth_date'] }}</x-ui.key-value>
                @endif
                <x-ui.key-value label="Kode Referral">{{ $subject['referral_code'] }}</x-ui.key-value>
                <x-ui.key-value label="Nama Referral">{{ $subject['referral_name'] }}</x-ui.key-value>
                <x-ui.key-value label="Jenis Pembiayaan">{{ $subject['product'] }}</x-ui.key-value>
                <x-ui.key-value label="Dasar Simulasi">{{ $subject['mode'] }}</x-ui.key-value>
            </div>

            <p class="mb-md text-eyebrow uppercase text-muted">Data Kendaraan</p>
            <div class="mb-xl grid grid-cols-1 gap-lg sm:grid-cols-3">
                <x-ui.key-value label="Kendaraan">{{ $subject['vehicle'] }}</x-ui.key-value>
                <x-ui.key-value label="Tahun">{{ $subject['vehicle_year'] }}</x-ui.key-value>
                <x-ui.key-value label="Penggunaan Unit">{{ $subject['usage'] }}</x-ui.key-value>
                <x-ui.key-value label="Type Angsuran">{{ $subject['instalment_type'] }}</x-ui.key-value>
                <x-ui.key-value label="Asuransi">{{ $subject['insurance'] }}</x-ui.key-value>
                <x-ui.key-value label="Domisili">{{ $subject['domicile'] }}</x-ui.key-value>
                <x-ui.key-value label="Type Debitur">{{ $subject['debtor_type'] }}</x-ui.key-value>
                @if ($subject['age_group'])
                    <x-ui.key-value label="Usia Debitur">{{ $subject['age_group'] }}</x-ui.key-value>
                @endif
                @if ($subject['funding_purpose'])
                    <x-ui.key-value label="Kebutuhan Dana">{{ $subject['funding_purpose'] }}</x-ui.key-value>
                @endif
            </div>

            <p class="mb-md text-eyebrow uppercase text-muted">Hasil Lima Tenor</p>
            <div class="mb-lg sm:hidden">
                <div class="overflow-hidden rounded-lg border border-hairline">
                    @foreach ($results as $row)
                        <div class="border-b border-divider px-md py-3 last:border-b-0">
                            <p class="{{ $row['zero'] ? 'text-border-strong' : 'text-ink' }} text-[13px] font-medium leading-[1.4]">
                                {{ $row['tenor'] }}
                            </p>
                            <div class="mt-2 grid grid-cols-2 gap-sm">
                                <div>
                                    <p class="text-[11px] uppercase leading-[1.3] text-muted">
                                        {{ $disbursementHeading }}
                                    </p>
                                    <p class="{{ $row['zero'] ? 'text-border-strong' : 'font-medium text-ink' }} mt-1 text-[14px] leading-[1.4]">
                                        {{ $row['disbursement'] }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] uppercase leading-[1.3] text-muted">Angsuran</p>
                                    <p class="{{ $row['zero'] ? 'text-border-strong' : 'font-medium text-ink' }} mt-1 text-[14px] leading-[1.4]">
                                        {{ $row['instalment'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-ui.table class="mb-lg hidden sm:block">
                <x-slot:head>
                    <x-ui.th>Tenor</x-ui.th>
                    <x-ui.th align="right">{{ $disbursementHeading }}</x-ui.th>
                    <x-ui.th align="right">Angsuran</x-ui.th>
                </x-slot:head>

                @foreach ($results as $row)
                    <tr>
                        <x-ui.td class="{{ $row['zero'] ? 'text-border-strong' : 'text-ink' }}">{{ $row['tenor'] }}</x-ui.td>
                        <x-ui.td align="right" numeric class="{{ $row['zero'] ? 'text-border-strong' : 'font-medium text-ink' }}">
                            {{ $row['disbursement'] }}
                        </x-ui.td>
                        <x-ui.td align="right" numeric class="{{ $row['zero'] ? 'text-border-strong' : 'font-medium text-ink' }}">
                            {{ $row['instalment'] }}
                        </x-ui.td>
                    </tr>
                @endforeach
            </x-ui.table>

            <p class="text-[13px] leading-[1.6] text-body">
                Nominal pembiayaan bersifat estimasi.<br>
                Besarnya pembiayaan berdasarkan hasil verifikasi profil debitur dan kondisi kendaraan.
            </p>

            <p class="mt-xl border-t border-hairline pt-md text-helper text-border-strong">
                Dibuat {{ $subject['printed_at'] }} · Kebon Jeruk Multiguna
            </p>
        </div>
    </div>
</x-layouts.print>
