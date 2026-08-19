<?php

namespace App\Services;

use App\Models\AcpBaseRate;
use App\Models\AcpUpping;
use App\Models\AgeGroup;
use App\Models\FiduciaTier;
use App\Models\InsuranceCascoRate;
use App\Models\InsuranceExtensionRate;
use App\Models\InsuranceLoadingRate;
use App\Models\Product;
use App\Models\ReferralCategory;
use App\Models\SimulationSetting;
use App\Models\SumInsuredSchedule;
use App\Models\TjhTier;
use App\Repositories\ProductResolver;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ConfigurationIntegrityValidator
{
    private const TENORS = [12, 24, 36, 48, 60];

    private const EXTENSION_CODES = [
        'banjir',
        'gempa',
        'huru_hara',
        'teroris',
        'pengemudi',
        'penumpang',
        'gap',
        'hic',
        'water_hammer',
    ];

    private const RATE_SETTING_KEYS = [
        'dtn_standard_net_dp_rate',
        'dtn_high_risk_net_dp_rate',
        'ucf_standard_net_dp_rate',
        'ucf_entrepreneur_net_dp_rate',
        'ucf_insurance_refund_base_rate',
        'ucf_insurance_refund_rate',
        'ucf_interest_refund_rate',
        'ucf_provision_refund_rate',
        'ucf_admin_refund_rate',
    ];

    private const MONEY_SETTING_KEYS = [
        'engine_warranty_fee',
        'beliv_fee_amount',
        'default_deposit_instalment_count',
        'default_bbnkb_amount',
        'default_pkb_amount',
        'default_invoice_amount',
        'default_tjh_amount',
        'default_driver_coverage_amount',
        'default_passenger_coverage_amount',
        'tjh_max_amount',
        'tjh_step_amount',
    ];

    private const BOOLEAN_SETTING_KEYS = [
        'default_flood_enabled',
        'default_earthquake_enabled',
        'default_riot_enabled',
        'default_terrorism_enabled',
        'default_engine_warranty_enabled',
        'dtn_acp_enabled',
        'ucf_acp_enabled',
    ];

    public function __construct(private readonly ProductResolver $productResolver) {}

    public function assertAll(): void
    {
        $this->assertProducts();
        $this->assertInsurance();
        $this->assertFees();
        $this->assertDefaults();
    }

    public function assertProducts(): void
    {
        Product::query()
            ->where('is_active', true)
            ->with('rates')
            ->each(function (Product $product): void {
                $tenors = $product->rates->pluck('tenor_months')->sort()->values()->all();

                $this->ensure(
                    $tenors === self::TENORS,
                    "Product '{$product->name}' wajib mempunyai tepat lima baris tenor 12, 24, 36, 48, dan 60 bulan.",
                );
                $this->ensure(
                    $product->rates->contains(fn ($rate) => $rate->effective_rate !== null),
                    "Product aktif '{$product->name}' harus mempunyai sedikitnya satu tenor yang tersedia.",
                );
            });

        $this->assertProductCoverage();
    }

    public function assertProductCoverage(): void
    {
        $activeProductNames = Product::query()
            ->where('is_active', true)
            ->pluck('name')
            ->flip();

        ReferralCategory::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->each(function (ReferralCategory $category) use ($activeProductNames): void {
                $usages = $category->allowedVehicleUsages();

                $this->ensure(
                    $usages !== [],
                    "Kategori Referral aktif '{$category->name}' harus mengizinkan sedikitnya satu penggunaan kendaraan.",
                );

                foreach ($usages as $usage) {
                    $productName = $this->productResolver->nameFor($category, $usage);
                    $this->ensure(
                        $activeProductNames->has($productName),
                        "Kategori Referral aktif '{$category->name}' untuk {$usage->value} membutuhkan Product aktif '{$productName}'.",
                    );
                }
            });
    }

    public function assertInsurance(): void
    {
        $settings = SimulationSetting::query()->pluck('value', 'key');
        $zone = $this->requiredSetting($settings, 'active_insurance_zone');
        $variant = $this->requiredSetting($settings, 'active_rate_variant');

        $this->ensure(
            in_array($variant, ['Batas Atas', 'Batas Bawah'], true),
            'Varian rate asuransi aktif harus Batas Atas atau Batas Bawah.',
        );

        foreach (['Passenger', 'Commercial'] as $usage) {
            foreach (['Comprehensive', 'TLO'] as $coverage) {
                $bands = InsuranceCascoRate::query()
                    ->where('zone', $zone)
                    ->where('variant', $variant)
                    ->where('usage', $usage)
                    ->where('coverage', $coverage)
                    ->orderBy('band_min')
                    ->get(['band_min', 'band_max']);

                $this->assertContinuousBands(
                    $bands,
                    "Casco {$zone} / {$variant} / {$usage} / {$coverage}",
                );
            }
        }

        $loadingAges = InsuranceLoadingRate::query()
            ->whereBetween('vehicle_age', [0, 14])
            ->orderBy('vehicle_age')
            ->pluck('vehicle_age')
            ->all();
        $this->ensure(
            $loadingAges === range(0, 14),
            'Loading asuransi wajib lengkap untuk setiap usia kendaraan 0 sampai 14 tahun.',
        );

        // Keduanya diurutkan sebelum dibandingkan: urutan penulisan konstanta di
        // atas tidak boleh ikut menentukan lolos atau tidaknya konfigurasi.
        $extensionCodes = InsuranceExtensionRate::query()->orderBy('code')->pluck('code')->all();
        $expectedCodes = self::EXTENSION_CODES;
        sort($extensionCodes);
        sort($expectedCodes);
        sort($expectedCodes);
        $this->ensure(
            $extensionCodes === $expectedCodes,
            'Rate perluasan wajib lengkap untuk banjir, gempa, huru-hara, teroris, pengemudi, dan penumpang.',
        );

        $acpYears = AcpBaseRate::query()->orderBy('tenor_years')->pluck('tenor_years')->all();
        $this->ensure($acpYears === [1, 2, 3, 4, 5], 'Rate dasar ACP wajib lengkap untuk tenor tahun 1 sampai 5.');

        $ageGroupIds = AgeGroup::query()->orderBy('id')->pluck('id')->all();
        $uppingAgeGroupIds = AcpUpping::query()->orderBy('age_group_id')->pluck('age_group_id')->all();
        $this->ensure(
            $ageGroupIds === $uppingAgeGroupIds,
            'Setiap kelompok usia wajib mempunyai tepat satu nilai upping ACP.',
        );

        $tjh = TjhTier::query()->orderBy('sequence')->get();
        $this->ensure($tjh->isNotEmpty(), 'TJH wajib mempunyai sedikitnya satu lapisan.');
        $this->ensure(
            $tjh->pluck('sequence')->all() === range(1, $tjh->count()),
            'Urutan lapisan TJH harus berurutan mulai dari 1.',
        );

        foreach ($tjh as $index => $tier) {
            $isLast = $index === $tjh->count() - 1;
            $this->ensure(
                $isLast ? $tier->limit_amount === null : $tier->limit_amount !== null,
                'Hanya lapisan TJH terakhir yang boleh mempunyai batas tanpa maksimum.',
            );
        }
    }

    public function assertFees(): void
    {
        $this->assertContinuousBands(
            FiduciaTier::query()->orderBy('min_amount')->get([
                'min_amount as band_min',
                'max_amount as band_max',
            ]),
            'Fiducia',
        );

        $sumInsuredYears = SumInsuredSchedule::query()
            ->orderBy('year_index')
            ->pluck('year_index')
            ->all();
        $this->ensure(
            $sumInsuredYears === [1, 2, 3, 4, 5],
            'Sum Insured wajib lengkap untuk tahun pembiayaan 1 sampai 5.',
        );

        $settings = SimulationSetting::query()->pluck('value', 'key');

        foreach (self::RATE_SETTING_KEYS as $key) {
            $value = $this->requiredNumericSetting($settings, $key);
            $this->ensure($value >= 0 && $value <= 1, "Nilai setting '{$key}' harus berada antara 0% dan 100%.");
        }
    }

    public function assertDefaults(): void
    {
        $settings = SimulationSetting::query()->pluck('value', 'key');

        $maxVehicleAge = $this->requiredIntegerSetting($settings, 'max_vehicle_age');
        $this->ensure($maxVehicleAge >= 1, 'Batas usia maksimal kendaraan harus sedikitnya 1 tahun.');

        $passengerCount = $this->requiredIntegerSetting($settings, 'default_passenger_count');
        $this->ensure($passengerCount >= 0, 'Jumlah penumpang default tidak boleh negatif.');

        foreach (self::MONEY_SETTING_KEYS as $key) {
            $value = $this->requiredIntegerSetting($settings, $key);
            $this->ensure($value >= 0, "Nilai setting '{$key}' tidak boleh negatif.");
        }

        foreach (self::BOOLEAN_SETTING_KEYS as $key) {
            $value = $this->requiredSetting($settings, $key);
            $this->ensure(
                filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null,
                "Nilai setting '{$key}' harus berupa boolean.",
            );
        }

        $step = $this->requiredIntegerSetting($settings, 'tjh_step_amount');
        $maximum = $this->requiredIntegerSetting($settings, 'tjh_max_amount');
        $default = $this->requiredIntegerSetting($settings, 'default_tjh_amount');
        $this->ensure($step > 0, 'Kelipatan TJH harus lebih besar dari nol.');
        $this->ensure($default <= $maximum, 'TJH default tidak boleh melebihi batas maksimal TJH.');
        $this->ensure($maximum % $step === 0 && $default % $step === 0, 'Batas dan default TJH harus mengikuti kelipatan TJH.');

        $this->requiredSetting($settings, 'active_insurance_zone');
        $this->requiredSetting($settings, 'active_rate_variant');
    }

    /** @param Collection<int, object> $bands */
    private function assertContinuousBands(Collection $bands, string $label): void
    {
        $this->ensure($bands->isNotEmpty(), "Konfigurasi {$label} tidak boleh kosong.");
        $this->ensure((int) $bands->first()->band_min === 0, "Band {$label} harus dimulai dari Rp 0.");

        foreach ($bands as $index => $band) {
            $isLast = $index === $bands->count() - 1;

            if ($isLast) {
                $this->ensure($band->band_max === null, "Band terakhir {$label} wajib tanpa batas maksimum.");

                continue;
            }

            $this->ensure($band->band_max !== null, "Hanya band terakhir {$label} yang boleh tanpa batas maksimum.");
            $next = $bands[$index + 1];
            $this->ensure(
                (int) $next->band_min === (int) $band->band_max + 1,
                "Band {$label} harus berurutan tanpa celah atau tumpang tindih.",
            );
        }
    }

    /** @param Collection<string, string> $settings */
    private function requiredSetting(Collection $settings, string $key): string
    {
        $this->ensure($settings->has($key), "Simulation setting '{$key}' wajib tersedia.");
        $value = trim((string) $settings->get($key));
        $this->ensure($value !== '', "Simulation setting '{$key}' tidak boleh kosong.");

        return $value;
    }

    /** @param Collection<string, string> $settings */
    private function requiredNumericSetting(Collection $settings, string $key): float
    {
        $value = $this->requiredSetting($settings, $key);
        $this->ensure(is_numeric($value), "Simulation setting '{$key}' harus numerik.");

        return (float) $value;
    }

    /** @param Collection<string, string> $settings */
    private function requiredIntegerSetting(Collection $settings, string $key): int
    {
        $value = $this->requiredSetting($settings, $key);
        $this->ensure(filter_var($value, FILTER_VALIDATE_INT) !== false, "Simulation setting '{$key}' harus berupa integer.");

        return (int) $value;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['configuration' => $message]);
        }
    }
}
