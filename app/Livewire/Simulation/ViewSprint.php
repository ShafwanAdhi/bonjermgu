<?php

namespace App\Livewire\Simulation;

use App\Application\Simulation\ConfigurationSimulationOutcome;
use App\Application\Simulation\OfficerSimulationRequest;
use App\Application\Simulation\OfficerSimulator;
use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\InsuranceCoverage;
use App\Domain\Simulation\Output\TenorResult;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;
use App\Models\AgeGroup;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\SimulationSetting;
use App\Models\SprintOffering;
use App\Models\SprintToken;
use App\Support\SimulationSettingDefaults;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * View Sprint — lembar entri yang di-screenshot inputter untuk dilaporkan ke
 * pusat.
 *
 * Kelanjutan dari layar Simulasi Kredit AO, bukan layar berdiri sendiri:
 *
 *   1. Tidak menghitung apa pun. Seluruh angka dibaca dari hasil engine atas
 *      simulasi yang baru saja dijalankan AO (klien, 15 Agustus 2026).
 *   2. Satu tenor per lembar. Draft memakai satu kolom tenor untuk seluruh
 *      HLOOKUP-nya, jadi AO memilih satu baris dari lima.
 *   3. Field yang tidak mungkin diketahui engine diisi AO, berangkat dari nilai
 *      default yang diatur Admin.
 *
 * Simulasinya dijalankan ulang dari state form yang sudah tersimpan di sesi
 * oleh layar AO, bukan dioper lewat URL: satu sumber kebenaran, dan angka pada
 * lembar ini tidak mungkin berbeda dari yang barusan dilihat AO.
 */
final class ViewSprint extends Component
{
    private const OFFICER_FORM_SESSION_KEY = 'simulation.officer.form';

    /**
     * Isian AO bertahan lintas tenor.
     *
     * Tombol tenor menavigasi ke rute lain, jadi komponennya mount ulang. Tanpa
     * ini, seorang AO yang sudah mengetik nama customer dan mengisi Insurance
     * Paid Entry kehilangan semuanya begitu ia membandingkan tenor lain —
     * padahal isian itu menggambarkan debitur yang sama, bukan tenornya.
     *
     * Nilai turunan sengaja tidak ikut disimpan: ia dihitung ulang dari
     * simulasi tiap kali, dan menyimpannya berarti membekukan jawaban tenor
     * lama pada lembar tenor baru.
     */
    private const SHEET_SESSION_KEY = 'simulation.officer.sprint';

    /** @var array<int, int> */
    public const TENORS = [12, 24, 36, 48, 60];

    /** Berlaku pada dokumen ini saja; tidak pernah masuk perhitungan. */
    private const MANUAL_DEFAULTS = [
        'cara_pembayaran' => 'view_sprint_cara_pembayaran',
        'is_beliv' => 'view_sprint_is_beliv',
    ];

    /**
     * Pilihan tiap field manual, mengikuti data validation workbook.
     *
     * Kosakatanya memang tidak seragam dan itu bukan kelalaian: SPRINT memakai
     * ADA/TIDAK untuk ACP & AXP, YA/TIDAK untuk BELIV, dan YES/NO untuk GAP,
     * HIC, serta Water Hammer. Lembar ini di-screenshot dan dikirim ke pusat,
     * jadi menyeragamkannya justru membuat gambarnya berbeda dari yang mereka
     * harapkan.
     *
     * Daftarnya di sini, bukan di blade, supaya nilai bawaan Admin dan pilihan
     * yang ditawarkan tidak bisa berselisih tanpa ada yang tahu.
     */
    public const MANUAL_OPTIONS = [
        'cara_pembayaran' => ['AUTO COLLECTION', 'PDC/GIRO'],
        'is_beliv' => ['TIDAK', 'YA'],
    ];

    public int $tenor = 12;

    /* ------------------------------------------------- Diisi Account Officer */

    public string $nama_customer = '';

    public string $product_id = '';

    public string $product_offering = '';

    /* ------------------------------------------- Penyusun Product ID & Offering */

    public string $sprint_product = '';

    public string $sprint_region = 'Jawa';

    public string $sprint_channel = '';

    public string $sprint_unit = '';

    public string $sprint_brand = '';

    public string $sprint_profile = '';

    public string $sprint_debtor_type = 'New Customer';

    public string $sprint_dp = '';

    public string $sprint_instalment = '';

    public string $cara_pembayaran = '';

    /**
     * Ditentukan kategori referral simulasinya: Captive Internal dan Captive
     * External memakai KKB, sisanya tidak (klien, 18 Agustus 2026).
     */
    public string $mandiri_kpm = 'NO';

    /** Pembiayaan ini selalu unit bekas; nilainya tetap dapat diubah Admin. */
    public string $kondisi_kendaraan = '';

    public string $spesifik_product = '';

    public string $wira_no = '0';

    public string $is_beliv = '';

    public string $sisa_kewajiban = '0';

    public string $sisa_os_lk = '0';

    /**
     * Keempatnya kini jawaban simulasi, bukan pertanyaan.
     *
     * ACP mengikuti apakah engine benar-benar membebankannya; GAP, HIC, dan
     * Water Hammer sudah menjadi perluasan asuransi yang dicentang AO di layar
     * Simulasi Kredit (klien, 18 Agustus 2026).
     */
    public string $acp_axp = 'TIDAK';

    public string $gap = 'NO';

    public string $hic = 'NO';

    public string $water_hammer = 'NO';

    public ?string $unavailableReason = null;

    private ?ConfigurationSimulationOutcome $outcome = null;

    public function mount(int $tenor): void
    {
        abort_unless(in_array($tenor, self::TENORS, true), 404);
        $this->tenor = $tenor;

        foreach (self::MANUAL_DEFAULTS as $property => $key) {
            $this->{$property} = $this->setting($key);
        }

        // Tidak ditanyakan ke AO: keduanya sudah ditentukan hal lain.
        $this->kondisi_kendaraan = $this->setting('view_sprint_kondisi_kendaraan');
        $this->sprint_region = $this->setting('view_sprint_region');
        $this->mandiri_kpm = $this->mandiriKpmForCategory();

        $this->resolveOutcome();
        $this->rejectTenorWithoutFinancing();

        if ($this->outcome !== null && $this->spesifik_product === '') {
            $this->spesifik_product = $this->outcome->config->product->name;
        }

        $this->prefillSelectors();
        $this->prefillInsuranceAnswers();
        $this->restoreSheetState();
        $this->ensureSprintSelection();
    }

    /**
     * Tenor yang tidak menghasilkan pembiayaan tidak boleh menjadi dokumen.
     *
     * Layar Simulasi Kredit menandainya dengan "—" alih-alih tombol, tapi
     * alamatnya bisa diketik langsung. Tanpa penjagaan ini lembarnya terbuka
     * berisi nol di setiap baris, dan nol terbaca seperti angka sah begitu jadi
     * gambar yang dikirim ke pusat.
     */
    private function rejectTenorWithoutFinancing(): void
    {
        if ($this->outcome === null || in_array($this->tenor, $this->financedTenors(), true)) {
            return;
        }

        // Outcome sengaja dipertahankan: pemilih tenor tetap perlu tahu tenor
        // mana yang bisa dituju, supaya AO tidak harus kembali ke layar
        // simulasi hanya untuk berpindah baris.
        $this->unavailableReason = sprintf(
            'Tenor %d bulan tidak menghasilkan pembiayaan pada simulasi ini, jadi tidak ada yang bisa dilaporkan.',
            $this->tenor,
        );
    }

    /**
     * Tenor yang benar-benar menghasilkan pembiayaan pada simulasi ini.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function financedTenors(): array
    {
        if ($this->outcome === null) {
            return [];
        }

        return array_values(array_filter(
            self::TENORS,
            fn (int $tenor): bool => $this->outcome->result->forTenor($tenor)->instalment > 0,
        ));
    }

    /**
     * Yang wajib terisi sebelum lembar ini pantas dikirim ke pusat.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function missingForExport(): array
    {
        return collect([
            'Nama Customer' => $this->nama_customer,
            'Product ID' => $this->product_id,
            'Product Offering' => $this->product_offering,
        ])->filter(fn (string $value): bool => trim($value) === '')->keys()->all();
    }

    /**
     * Nama berkas unduhan. AO yang mengunduh beberapa tenor untuk debitur yang
     * sama akan menerima berkas yang bisa dibedakan, bukan deretan stempel
     * waktu yang harus dibuka satu per satu.
     */
    #[Computed]
    public function exportFileName(): string
    {
        return sprintf(
            'view-sprint-%s-%d-bulan.png',
            Str::slug($this->nama_customer) ?: 'tanpa-nama',
            $this->tenor,
        );
    }

    /* ------------------------------------------------------- Ingatan lembar */

    /**
     * Properti yang diketik atau dipilih AO sendiri.
     *
     * Dropdown penyusun ditemukan dari properti komponen, bukan dari daftar
     * terpisah: menambah satu dimensi baru lalu lupa mencatatnya di sini akan
     * membuat dimensi itu diam-diam tidak ikut tersimpan.
     *
     * @return array<int, string>
     */
    private function rememberedProperties(): array
    {
        // Yang datang dari layar simulasi atau dari konfigurasi tidak diingat di
        // sini: menyimpannya berarti membekukan jawaban lama pada simulasi baru.
        $derived = [
            'sprint_instalment', 'sprint_debtor_type', 'sprint_region',
            'sprint_channel', 'sprint_brand', 'sprint_dp',
        ];

        $selectors = array_values(array_filter(
            array_keys(get_object_vars($this)),
            fn (string $property): bool => str_starts_with($property, 'sprint_')
                && ! in_array($property, $derived, true),
        ));

        return [
            ...array_keys(self::MANUAL_DEFAULTS),
            ...$selectors,
            'nama_customer', 'spesifik_product', 'wira_no', 'sisa_kewajiban', 'sisa_os_lk',
        ];
    }

    /**
     * Nilai tersimpan dipasang di atas hasil prefill, bukan sebaliknya: kalau AO
     * sudah menimpa sebuah pilihan dengan sadar, pilihannya yang menang.
     * Pilihan yang tidak lagi ada di daftar dibiarkan gugur ke hasil prefill.
     */
    private function restoreSheetState(): void
    {
        $stored = session()->get(self::SHEET_SESSION_KEY);

        if (! is_array($stored)) {
            return;
        }

        $options = $this->selectorOptions();

        foreach ($this->rememberedProperties() as $property) {
            if (! array_key_exists($property, $stored)) {
                continue;
            }

            $value = $stored[$property];
            $group = str_starts_with($property, 'sprint_') ? substr($property, 7) : null;

            if ($group !== null && $value !== '' && ! in_array($value, $options[$group] ?? [], true)) {
                continue;
            }

            $this->{$property} = $value;
        }
    }

    private function rememberSheetState(): void
    {
        session()->put(self::SHEET_SESSION_KEY, collect($this->rememberedProperties())
            ->mapWithKeys(fn (string $property): array => [$property => $this->{$property}])
            ->all());
    }

    /* ---------------------------------------- Product ID & Product Offering */

    /**
     * Kode SPRINT dieja dari satu token per dimensi, persis seperti Master!C5
     * dan Master!C6 merangkainya dari dropdown yang diisi AO.
     *
     * Dimensi yang sudah dijawab simulasi dipilihkan di muka; sisanya dibiarkan
     * kosong supaya AO memilih sendiri, bukan ditebak. Nilai hasil rangkaian
     * tetap boleh disunting: dua sel bantu di Master mengeja token yang tidak
     * cocok dengan satu pun baris offering, jadi rangkaian ini tidak bisa
     * dianggap mutlak benar sampai pusat mengonfirmasinya.
     */
    private function prefillSelectors(): void
    {
        if ($this->outcome === null) {
            return;
        }

        $input = $this->outcome->input;

        $this->sprint_product = $input->financingType === FinancingType::UCF
            ? 'C2C Multiguna PPSA'
            : ($this->outcome->result->forTenor($this->tenor)->ltvAmount >= (float) $this->setting('view_sprint_modal_usaha_threshold')
                ? 'Fasilitas Modal Usaha'
                : 'Fasilitas Dana');

        // Commercial masih bercabang jadi Pick Up atau Truck; engine tidak tahu
        // yang mana, jadi hanya Passenger yang bisa dipilihkan.
        $this->sprint_unit = $input->vehicleUsage === VehicleUsage::PASSENGER ? 'Passenger' : '';
        $this->sprint_brand = $input->vehicleOrigin->value;
        $this->sprint_profile = match ($input->debtorType) {
            DebtorType::ENTREPRENEUR => 'Perorangan Wiraswasta',
            DebtorType::LEGAL_ENTITY => 'Badan Hukum Usaha',
            DebtorType::NON_ENTREPRENEUR => 'Perorangan Non Wiraswasta',
        };

        $this->sprint_instalment = $input->instalmentType->value;
        $this->sprint_channel = $this->channelForReferral();
        $this->sprint_dp = $this->downPaymentBand();
        $this->sprint_debtor_type = (string) (session()->get(self::OFFICER_FORM_SESSION_KEY)['customer_type'] ?? 'New Customer');
    }

    /**
     * KKB mengikuti segmen kategori referral, bukan pilihan AO.
     *
     * Segmen dipakai alih-alih mencocokkan nama supaya kategori Captive yang
     * ditambahkan Admin kelak ikut terbaca tanpa menyentuh kode ini.
     */
    private function mandiriKpmForCategory(): string
    {
        $id = session()->get(self::OFFICER_FORM_SESSION_KEY)['referral_category_id'] ?? null;

        $segment = $id ? ReferralCategory::query()->whereKey($id)->value('segment') : null;

        return $segment === 'Captive' ? 'YES' : 'NO';
    }

    /**
     * Kanal mengikuti tier kategori referral, bukan pilihan AO.
     *
     * Sub kategori menimpanya kalau satu kategori melayani lebih dari satu
     * kanal: Karyawan Internal adalah Referral kecuali Graha Sultan, yang
     * Telemarketing. Tier yang tidak dipetakan berarti kanalnya memang tidak
     * dipakai pusat, dan hasilnya kosong — bukan tebakan.
     */
    private function channelForReferral(): string
    {
        $state = session()->get(self::OFFICER_FORM_SESSION_KEY) ?? [];
        $tokens = $this->tokens();

        $subCategory = ($state['referral_sub_category_id'] ?? null)
            ? ReferralSubCategory::query()->whereKey($state['referral_sub_category_id'])->value('name')
            : null;

        $override = $subCategory === null
            ? null
            : ($tokens['channel_source'] ?? collect())->firstWhere('source', $subCategory)?->offering_token;

        if ($override) {
            return (string) $override;
        }

        $tier = ($state['referral_category_id'] ?? null)
            ? ReferralCategory::query()->whereKey($state['referral_category_id'])->value('tier')
            : null;

        return (string) (($tokens['channel_tier'] ?? collect())->firstWhere('source', $tier)?->offering_token ?? '');
    }

    /** Jawaban asuransi yang sudah diberikan simulasi, bukan ditanyakan lagi. */
    private function prefillInsuranceAnswers(): void
    {
        if ($this->outcome === null) {
            return;
        }

        $input = $this->outcome->input;
        $yes = fn (string $code): string => $input->extensionEnabled($code) ? 'YES' : 'NO';

        $this->acp_axp = $this->outcome->result->forTenor($this->tenor)->insurance->acp > 0 ? 'ADA' : 'TIDAK';
        $this->gap = $yes('gap');
        $this->hic = $yes('hic');
        $this->water_hammer = $yes('water_hammer');
    }

    /**
     * Golongan DP mengikuti unit dan asal merk (klien, 18 Agustus 2026):
     * Passenger Jepang DP5, sisanya DP15.
     */
    private function downPaymentBand(): string
    {
        $input = $this->outcome?->input;

        if ($input === null) {
            return '';
        }

        return $input->vehicleUsage === VehicleUsage::PASSENGER && $input->vehicleOrigin === VehicleOrigin::JAPAN
            ? 'DP5'
            : 'DP15';
    }

    /** @return array<string, Collection<int, SprintToken>> */
    #[Computed]
    public function tokens(): array
    {
        return SprintToken::grouped();
    }

    /** @return array<string, array<int, string>> */
    #[Computed]
    public function selectorOptions(): array
    {
        $options = collect(SprintToken::GROUPS)
            ->map(fn (string $label, string $group): array => collect($this->tokens()[$group] ?? [])
                ->pluck('source')
                ->filter()
                ->values()
                ->all())
            ->all();

        if ($this->hasOfferingLookup()) {
            foreach ([
                'product' => 'product_category',
                'channel' => 'channel',
                'unit' => 'unit',
                'brand' => 'brand',
                'region' => 'region',
                'dp' => 'dp',
            ] as $group => $column) {
                $fromOfferings = SprintOffering::query()
                    ->select($column)
                    ->distinct()
                    ->whereNotNull($column)
                    ->orderBy($column)
                    ->pluck($column)
                    ->all();

                $options[$group] = array_values(array_unique([
                    ...($options[$group] ?? []),
                    ...$fromOfferings,
                ]));
            }
        }

        return $options;
    }

    /**
     * Filter yang, kalau dilonggarkan sendirian, memunculkan offering lagi.
     *
     * Tanpa ini layar hanya berkata "Product ID belum tersedia" dan AO tidak
     * punya cara tahu pilihan mana yang harus diubah — delapan dropdown, dan
     * satu di antaranya menyaring habis semuanya.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function blockingFilters(): array
    {
        if (! $this->lookupDeadEnd()) {
            return [];
        }

        return collect(SprintToken::GROUPS)
            ->only(['product', 'channel', 'unit', 'brand', 'profile', 'debtor_type', 'dp', 'region'])
            ->filter(fn (string $label, string $group): bool => $this->{'sprint_'.$group} !== ''
                && $this->offeringQuery(includeProductId: false, ignore: $group)->exists())
            ->values()
            ->all();
    }

    /** Katalog offering ada, tapi kombinasi pilihan sekarang tidak menyisakan apa pun. */
    #[Computed]
    public function lookupDeadEnd(): bool
    {
        return $this->hasOfferingLookup() && $this->productIdOptions() === [];
    }

    #[Computed]
    public function hasOfferingLookup(): bool
    {
        return SprintOffering::query()->exists();
    }

    /** @return array<int, string> */
    #[Computed]
    public function productIdOptions(): array
    {
        return $this->offeringQuery(includeProductId: false)
            ->select('product_id')
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id')
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function productOfferingOptions(): array
    {
        if ($this->product_id === '') {
            return [];
        }

        return $this->offeringQuery(includeProductId: true)
            ->select('product_offering')
            ->distinct()
            ->orderBy('product_offering')
            ->pluck('product_offering')
            ->values()
            ->all();
    }

    /**
     * Menjaga kedua kode tetap sejalan dengan filter di atasnya.
     *
     * Pilihan yang tidak lagi cocok dikosongkan, bukan diganti. Mengambil baris
     * pertama dari sekian adalah tebakan, dan lembar ini berakhir sebagai PNG
     * yang dikirim ke pusat — di sana kode tebakan terbaca persis seperti kode
     * yang dipilih. Kalau filternya menyisakan tepat satu baris tidak ada yang
     * ditebak, jadi baris itu dipilihkan.
     */
    private function ensureSprintSelection(bool $allowProductChange = true): void
    {
        if (! $this->hasOfferingLookup()) {
            $this->compose();

            return;
        }

        if ($allowProductChange) {
            $this->product_id = $this->settledOption($this->productIdOptions(), $this->product_id);
        }

        $this->product_offering = $this->settledOption($this->productOfferingOptions(), $this->product_offering);
    }

    /** @param  array<int, string>  $options */
    private function settledOption(array $options, string $current): string
    {
        if (in_array($current, $options, true)) {
            return $current;
        }

        return count($options) === 1 ? $options[0] : '';
    }

    /**
     * @param  string|null  $ignore  Dimensi yang dilewati, untuk menguji apakah
     *                               ia yang menyaring habis semuanya.
     * @return Builder<SprintOffering>
     */
    private function offeringQuery(bool $includeProductId, ?string $ignore = null): Builder
    {
        $query = SprintOffering::query();
        $on = fn (string $group): bool => $group !== $ignore;

        if ($on('product')) {
            $this->whereNullable($query, 'product_category', $this->sprint_product);
        }
        if ($on('channel')) {
            $this->whereNullable($query, 'channel', $this->sprint_channel);
        }
        if ($on('unit')) {
            $this->whereNullable($query, 'unit', $this->sprint_unit);
        }
        if ($on('brand')) {
            $this->whereNullable($query, 'brand', $this->sprint_brand);
        }
        if ($on('dp')) {
            $this->whereNullable($query, 'dp', $this->sprint_dp);
        }
        if ($on('profile')) {
            $this->whereProfile($query);
        }
        if ($on('debtor_type')) {
            $this->whereDebtorType($query);
        }
        if ($on('region')) {
            $this->whereRegion($query);
        }

        // Tenor dan jenis angsuran tidak dipilih AO di layar ini, jadi
        // melonggarkannya tidak pernah bisa jadi saran yang berguna.
        $this->whereNullable($query, 'tenor', intdiv($this->tenor, 12).'TH');
        $this->whereNullable($query, 'instalment', $this->sprint_instalment);

        if ($includeProductId) {
            $this->whereNullable($query, 'product_id', $this->product_id);
        }

        return $query;
    }

    /** @param  Builder<SprintOffering>  $query */
    private function whereNullable(Builder $query, string $column, string $value): void
    {
        if ($value === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereNull($column)
            ->orWhere($column, $value));
    }

    /** @param  Builder<SprintOffering>  $query */
    private function whereProfile(Builder $query): void
    {
        if ($this->sprint_profile === '') {
            return;
        }

        $values = $this->sprint_profile === 'Badan Hukum Usaha'
            ? ['Badan Hukum Usaha']
            : ['Perorangan', 'Perorangan Non Wiraswasta', 'Perorangan Wiraswasta'];

        $query->where(fn (Builder $query) => $query
            ->whereNull('profile')
            ->orWhereIn('profile', $values));
    }

    /** @param  Builder<SprintOffering>  $query */
    private function whereDebtorType(Builder $query): void
    {
        if ($this->sprint_debtor_type === '') {
            return;
        }

        $values = $this->sprint_debtor_type === 'Additional Order'
            ? ['Additional Order']
            : ['New Customer / Repeat Order', 'New Customer', 'Repeat Order'];

        $query->where(fn (Builder $query) => $query
            ->whereNull('debtor_type')
            ->orWhereIn('debtor_type', $values));
    }

    /** @param  Builder<SprintOffering>  $query */
    private function whereRegion(Builder $query): void
    {
        if ($this->sprint_region === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereNull('region')
            ->orWhere('region', 'Nasional')
            ->orWhere('region', $this->sprint_region));
    }

    /** @return array<string, string> */
    private function selections(): array
    {
        return [
            'product' => $this->sprint_product,
            'region' => $this->sprint_region,
            'channel' => $this->sprint_channel,
            'unit' => $this->sprint_unit,
            'brand' => $this->sprint_brand,
            'profile' => $this->sprint_profile,
            'debtor_type' => $this->sprint_debtor_type,
            'dp' => $this->sprint_dp,
            'tenor' => intdiv($this->tenor, 12).'TH',
            'instalment' => $this->sprint_instalment,
        ];
    }

    /**
     * Menyusun ulang kedua kode. Satu segmen saja yang belum terjawab membuat
     * kode dikosongkan, bukan dirangkai bolong — kode setengah jadi terbaca
     * seperti kode sah saat sudah jadi gambar.
     */
    private function compose(): void
    {
        $this->product_id = $this->join(SprintToken::PRODUCT_ID_PARTS, 'product_token');
        $this->product_offering = $this->join(SprintToken::OFFERING_PARTS, 'offering_token');
    }

    /** @param  array<int, string>  $groups */
    private function join(array $groups, string $column): string
    {
        $selected = $this->selections();
        $tokens = $this->tokens();
        $parts = [];

        foreach ([...$groups, 'instalment'] as $group) {
            $source = $selected[$group] ?? '';
            $token = $source === ''
                ? null
                : ($tokens[$group] ?? collect())->firstWhere('source', $source)?->{$column};

            if ($token === null || $token === '') {
                return '';
            }

            $parts[] = $token;
        }

        $instalment = array_pop($parts);

        return implode(' ', $parts).' - '.$instalment;
    }

    public function updated(string $property): void
    {
        $this->rememberSheetState();

        if ($property === 'product_id' && $this->hasOfferingLookup()) {
            $this->ensureSprintSelection(false);

            return;
        }

        if (in_array($property, ['product_id', 'product_offering'], true)) {
            return;
        }

        if (str_starts_with($property, 'sprint_')) {
            $this->ensureSprintSelection();
        } elseif (! $this->hasOfferingLookup()) {
            $this->compose();
        }
    }

    /* ------------------------------------------------------------- Simulasi */

    /**
     * Menjalankan ulang simulasi AO dari state sesi. Gagal di sini bukan error
     * sistem — biasanya AO membuka halaman ini tanpa menghitung dulu.
     */
    private function resolveOutcome(): void
    {
        $state = session()->get(self::OFFICER_FORM_SESSION_KEY);

        if (! is_array($state) || ($state['model_id'] ?? '') === '' || ($state['unit_price'] ?? '') === '') {
            $this->unavailableReason = 'Belum ada simulasi yang dijalankan pada sesi ini.';

            return;
        }

        try {
            $this->outcome = app(OfficerSimulator::class)->run(
                $this->requestFrom($state),
                (int) today()->format('Y'),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->unavailableReason = 'Simulasi tidak dapat dimuat ulang. Jalankan ulang dari layar Simulasi Kredit.';
        }
    }

    /** @param  array<string, mixed>  $s */
    private function requestFrom(array $s): OfficerSimulationRequest
    {
        $get = fn (string $key, $fallback = '') => $s[$key] ?? $fallback;
        $money = fn (string $key) => (float) ($s[$key] ?? 0);
        $percent = fn (string $key) => ((float) ($s[$key] ?? 0)) / 100;
        $ageGroup = $get('age_group_id')
            ? AgeGroup::query()->find($get('age_group_id'))?->label
            : null;

        return new OfficerSimulationRequest(
            referralCategoryId: (int) $get('referral_category_id'),
            vehicleModelId: (int) $get('model_id'),
            vehicleYear: (int) $get('vehicle_year'),
            financingType: FinancingType::from($get('financing_type', 'DTN')),
            mode: SimulationMode::from($get('mode', 'A')),
            debtorType: DebtorType::from($get('debtor_type', 'non_entrepreneur')),
            ageGroup: $ageGroup,
            stnkOwnership: StnkOwnership::from($get('stnk_ownership', 'own')),
            instalmentType: InstalmentType::from($get('instalment_type', 'ADDB')),
            coverageType: CoverageType::from($get('coverage_type', 'comprehensive_then_tlo')),
            marketPrice: $money('unit_price'),
            desiredAmount: $money('desired_amount'),
            rateVariant: $get('rate_variant', 'Batas Bawah'),
            upRate: $percent('up_rate'),
            upAdmin: $money('up_admin'),
            upProvision: $percent('up_provisi'),
            acpUpping: ($s['up_acp'] ?? '') === '' ? null : $percent('up_acp'),
            extensions: [
                'flood' => (bool) $get('ext_flood', false),
                'earthquake' => (bool) $get('ext_earthquake', false),
                'riot' => (bool) $get('ext_riot', false),
                'terrorism' => (bool) $get('ext_terrorism', false),
            ],
            tjhAmount: $money('tjh_amount'),
            driverCoverageAmount: $money('driver_amount'),
            passengerCoverageAmount: $money('passenger_amount'),
            passengerCount: (int) $get('passenger_count', 0),
            engineWarrantyEnabled: (bool) $get('engine_warranty', true),
            depositInstalmentCount: (int) $get('deposit_instalment', 0),
            bbnkbAmount: $money('bbnkb_amount'),
            pkbAmount: $money('pkb_amount'),
            invoiceAmount: $money('invoice_amount'),
        );
    }

    /* ---------------------------------------------------------- Isi lembar */

    /**
     * Setiap angka di sini dibaca apa adanya dari hasil engine. Tidak ada satu
     * pun yang dihitung ulang — lembar yang berhitung sendiri bisa cocok dengan
     * dirinya sendiri sambil berbeda dari engine.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function sheet(): array
    {
        if ($this->outcome === null) {
            return [];
        }

        $row = $this->outcome->result->forTenor($this->tenor);
        $input = $this->outcome->input;
        $config = $this->outcome->config;

        return [
            'tenor' => $row->tenorMonths,
            'type_unit' => $this->outcome->vehicleLabel,
            'tahun_unit' => $input->vehicleYear,
            'harga_kendaraan' => (int) round($row->otrPrice),
            'uang_muka' => (int) round($row->netDpAmount),
            'leasing_cash_deposit' => (int) round($row->depositInstalmentAmount),
            'administrasi_kredit' => (int) round($row->fees->administration),
            'fidusia' => (int) round($row->fees->fiducia),
            'provisi' => (int) round($row->fees->provision),
            'asuransi' => $row->insurance->total,
            'angsuran' => $row->instalment,
            'rate_bunga' => $row->sellingInterestRate,
            'total_bayar_pertama' => (int) round($row->firstPayment),
            'pokok_hutang' => (int) round($row->ltvAmount),

            'angsuran_pertama' => $input->instalmentType === InstalmentType::ADDM ? 'ADVANCE' : 'ARREAR',
            'jumlah_unit' => 1,
            'bbn' => (int) round($config->bbnkbAmount),
            'biaya_proses_faktur' => (int) round($config->invoiceAmount),
            'type_customer' => $input->debtorType === DebtorType::LEGAL_ENTITY ? 'BADAN USAHA' : 'PERORANGAN',
            'refund_administration' => (int) round($row->refund->administration),
            'refund_provision' => (int) round($row->refund->provision),

            'usage' => $input->vehicleUsage === VehicleUsage::PASSENGER ? 'Non Commercial' : 'Commercial',
            'rate_jual' => mb_strtoupper(str_replace('Batas ', '', $config->insurance->activeVariant)),
            'wilayah_asuransi' => mb_strtoupper($config->insurance->activeZone),
            'asuransi_clp' => $config->insurance->acpUpping($input->ageGroup),
            'garansi_mesin' => $input->engineWarrantyEnabled ? 'YA' : 'TIDAK',

            'refund_bunga' => (int) round($row->refund->interest),
            'refund_premi_insurance' => (int) round($row->refund->insurance),
            'deposit_angsuran' => $config->depositInstalmentCount,
            'deposit_angsuran_rp' => (int) round($row->depositInstalmentAmount),

            'detail_asuransi' => $this->insuranceYears($row),

            // Status selalu CASH, dan barisnya hanya sepanjang tenor: lembar
            // tenor satu tahun tidak lagi menampilkan lima baris kosong
            // (klien, 18 Agustus 2026).
            'paid_entry' => collect($row->insurance->yearly)
                ->map(fn (array $year): array => [
                    'status' => 'CASH',
                    'discount' => (int) round($year['discount']),
                    'paid' => (int) round($year['paid']),
                ])
                ->all(),
        ];
    }

    /**
     * Baris per tahun pada blok Detail Asuransi. Draft menyediakan tujuh baris;
     * yang di luar tenor dibiarkan kosong, bukan diisi nol, supaya tidak terbaca
     * sebagai pertanggungan bernilai nol.
     *
     * @return array<int, array<string, string>>
     */
    private function insuranceYears(TenorResult $row): array
    {
        $input = $this->outcome->input;
        $years = intdiv($row->tenorMonths, 12);
        $rows = [];

        foreach (range(1, 7) as $year) {
            if ($year > $years) {
                $rows[$year] = ['aktif' => false];

                continue;
            }

            $comprehensive = $input->coverageType->coverageForYear($year) === InsuranceCoverage::COMPREHENSIVE;
            $yes = fn (bool $on) => $on && $comprehensive ? 'YES' : 'NO';

            $rows[$year] = [
                'aktif' => true,
                'asuransi' => $comprehensive ? 'All Risk' : 'TLO',
                'tjh' => (int) round($input->tjhAmount),
                'huru_hara' => $yes($input->extensionEnabled('riot')),
                'banjir' => $yes($input->extensionEnabled('flood')),
                'water_hammer' => $this->water_hammer,
                'gempa' => $yes($input->extensionEnabled('earthquake')),
                'teroris' => $yes($input->extensionEnabled('terrorism')),
                'pa_pengemudi' => $yes($input->driverCoverageAmount > 0),
                'pa_penumpang' => (int) round($input->passengerCoverageAmount),
            ];
        }

        return $rows;
    }

    #[Computed]
    public function available(): bool
    {
        return $this->outcome !== null && $this->unavailableReason === null;
    }

    private function setting(string $key): string
    {
        return (string) (SimulationSetting::query()->where('key', $key)->value('value')
            ?? SimulationSettingDefaults::values()[$key]
            ?? '');
    }

    public function render(): View
    {
        // Outcome tidak bertahan antar request Livewire: ia memuat value object
        // yang tidak layak masuk payload, jadi dimuat ulang dari sesi.
        if ($this->outcome === null && $this->unavailableReason === null) {
            $this->resolveOutcome();
        }

        return view('livewire.simulation.view-sprint');
    }
}
