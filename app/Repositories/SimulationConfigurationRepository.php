<?php

namespace App\Repositories;

use App\Domain\Simulation\Input\DownPaymentConfig;
use App\Domain\Simulation\Input\FeeConfig;
use App\Domain\Simulation\Input\InsuranceConfig;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\Input\RefundConfig;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\VehicleUsage;
use App\Models\AcpBaseRate;
use App\Models\AcpUpping;
use App\Models\FiduciaTier;
use App\Models\InsuranceCascoRate;
use App\Models\InsuranceExtensionRate;
use App\Models\InsuranceLoadingRate;
use App\Models\Product;
use App\Models\Referral;
use App\Models\SimulationSetting;
use App\Models\SumInsuredSchedule;
use App\Models\TjhTier;
use App\Support\SimulationSettingDefaults;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class SimulationConfigurationRepository
{
    public const CACHE_VERSION_KEY = 'simulation_config_version';

    private const EXTENSION_CODES = [
        'banjir' => 'flood',
        'gempa' => 'earthquake',
        'huru_hara' => 'riot',
        'teroris' => 'terrorism',
        'pengemudi' => 'driver',
        'penumpang' => 'passenger',
    ];

    public function __construct(private readonly ProductResolver $productResolver) {}

    public function forReferral(Referral $referral, VehicleUsage $usage): SimulationConfig
    {
        return $this->forProduct($this->productResolver->resolve($referral, $usage));
    }

    /**
     * @param  string|null  $rateVariant  Overrides the Admin default. The Account
     *                                    Officer screen exposes the insurance
     *                                    rate variant per simulation, and the
     *                                    Casco rows must be re-read for it.
     */
    public function forProduct(Product $product, ?string $rateVariant = null): SimulationConfig
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, '0');
        $variantKey = $rateVariant === null ? '' : ':variant:'.md5($rateVariant);

        return Cache::remember(
            "simulation_config:{$version}:product:{$product->id}{$variantKey}",
            now()->addHours(12),
            fn () => $this->buildForProduct($product, $rateVariant),
        );
    }

    private function buildForProduct(Product $product, ?string $rateVariant = null): SimulationConfig
    {
        $settings = array_replace(
            SimulationSettingDefaults::values(),
            SimulationSetting::query()->pluck('value', 'key')->all(),
        );
        $zone = $this->stringSetting($settings, 'active_insurance_zone');
        $variant = $rateVariant ?? $this->stringSetting($settings, 'active_rate_variant');

        return new SimulationConfig(
            product: $this->productConfig($product),
            insurance: new InsuranceConfig(
                activeZone: $zone,
                activeVariant: $variant,
                cascoRates: InsuranceCascoRate::query()
                    ->where('zone', $zone)
                    ->where('variant', $variant)
                    ->orderBy('usage')
                    ->orderBy('coverage')
                    ->orderBy('band_min')
                    ->get()
                    ->map(fn (InsuranceCascoRate $rate) => [
                        'zone' => $rate->zone,
                        'usage' => $rate->usage,
                        'variant' => $rate->variant,
                        'coverage' => $rate->coverage,
                        'band_min' => $rate->band_min,
                        'band_max' => $rate->band_max,
                        'rate' => $rate->rate,
                    ])->all(),
                sumInsuredSchedule: SumInsuredSchedule::query()
                    ->orderBy('year_index')
                    ->pluck('percentage', 'year_index')
                    ->map(fn ($percentage) => (float) $percentage)
                    ->all(),
                loadingRates: InsuranceLoadingRate::query()
                    ->orderBy('vehicle_age')
                    ->pluck('rate', 'vehicle_age')
                    ->map(fn ($rate) => (float) $rate)
                    ->all(),
                extensionRates: $this->extensionRates(),
                acpBaseRates: AcpBaseRate::query()
                    ->orderBy('tenor_years')
                    ->pluck('rate', 'tenor_years')
                    ->map(fn ($rate) => (float) $rate)
                    ->all(),
                acpUppings: AcpUpping::query()
                    ->join('age_groups', 'age_groups.id', '=', 'acp_uppings.age_group_id')
                    ->orderBy('age_groups.sort_order')
                    ->pluck('acp_uppings.upping', 'age_groups.label')
                    ->map(fn ($upping) => (float) $upping)
                    ->all(),
                tjhTiers: TjhTier::query()
                    ->orderBy('sequence')
                    ->get()
                    ->map(fn (TjhTier $tier) => [
                        'limit' => $tier->limit_amount,
                        'rate' => $tier->rate,
                    ])->all(),
                engineWarrantyFee: $this->floatSetting($settings, 'engine_warranty_fee'),
                acpMaxLoanAmount: $this->floatSetting($settings, 'acp_max_loan_amount'),
                dtnAcpEnabled: $this->boolSetting($settings, 'dtn_acp_enabled'),
                ucfAcpEnabled: $this->boolSetting($settings, 'ucf_acp_enabled'),
            ),
            fees: new FeeConfig(
                FiduciaTier::query()
                    ->orderBy('min_amount')
                    ->get()
                    ->map(fn (FiduciaTier $tier) => [
                        'min' => $tier->min_amount,
                        'max' => $tier->max_amount,
                        'fee' => $tier->fee,
                    ])->all(),
            ),
            downPayment: new DownPaymentConfig(
                dtnStandardRate: $this->floatSetting($settings, 'dtn_standard_net_dp_rate'),
                dtnHighRiskRate: $this->floatSetting($settings, 'dtn_high_risk_net_dp_rate'),
                ucfStandardRate: $this->floatSetting($settings, 'ucf_standard_net_dp_rate'),
                ucfNonJapanStandardRate: $this->floatSetting($settings, 'ucf_non_japan_net_dp_rate'),
                ucfEntrepreneurRate: $this->floatSetting($settings, 'ucf_entrepreneur_net_dp_rate'),
            ),
            refund: new RefundConfig(
                insuranceBaseRate: $this->floatSetting($settings, 'ucf_insurance_refund_base_rate'),
                insuranceRefundRate: $this->floatSetting($settings, 'ucf_insurance_refund_rate'),
                interestRefundRate: $this->floatSetting($settings, 'ucf_interest_refund_rate'),
                provisionRefundRate: $this->floatSetting($settings, 'ucf_provision_refund_rate'),
                adminRefundRate: $this->floatSetting($settings, 'ucf_admin_refund_rate'),
            ),
            maxVehicleAge: $this->intSetting($settings, 'max_vehicle_age'),
            bbnkbAmount: $this->floatSetting($settings, 'default_bbnkb_amount'),
            pkbAmount: $this->floatSetting($settings, 'default_pkb_amount'),
            invoiceAmount: $this->floatSetting($settings, 'default_invoice_amount'),
            depositInstalmentCount: $this->intSetting($settings, 'default_deposit_instalment_count'),
            defaultExtensions: [
                'flood' => $this->boolSetting($settings, 'default_flood_enabled'),
                'earthquake' => $this->boolSetting($settings, 'default_earthquake_enabled'),
                'riot' => $this->boolSetting($settings, 'default_riot_enabled'),
                'terrorism' => $this->boolSetting($settings, 'default_terrorism_enabled'),
            ],
            defaultTjhAmount: $this->floatSetting($settings, 'default_tjh_amount'),
            defaultDriverCoverageAmount: $this->floatSetting($settings, 'default_driver_coverage_amount'),
            defaultPassengerCoverageAmount: $this->floatSetting($settings, 'default_passenger_coverage_amount'),
            defaultPassengerCount: $this->intSetting($settings, 'default_passenger_count'),
            defaultEngineWarrantyEnabled: $this->boolSetting($settings, 'default_engine_warranty_enabled'),
        );
    }

    private function productConfig(Product $product): ProductConfig
    {
        if (! $product->relationLoaded('rates')) {
            $product->load(['rates' => fn ($query) => $query->orderBy('tenor_months')]);
        }

        $rates = array_fill_keys([12, 24, 36, 48, 60], null);

        foreach ($product->rates as $rate) {
            $rates[$rate->tenor_months] = $rate->effective_rate;
        }

        return new ProductConfig(
            name: $product->name,
            effectiveRates: $rates,
            adminMax: (float) $product->admin_max,
            provisionRate: $product->provisi_rate,
            upRate: $product->up_rate,
            upAdmin: (float) $product->up_admin,
            upProvision: $product->up_provisi,
        );
    }

    /** @return array<string, float> */
    private function extensionRates(): array
    {
        $databaseRates = InsuranceExtensionRate::query()->pluck('rate', 'code');
        $rates = [];

        foreach (self::EXTENSION_CODES as $databaseCode => $domainCode) {
            if (! $databaseRates->has($databaseCode)) {
                throw new RuntimeException("Rate perluasan '{$databaseCode}' tidak ditemukan.");
            }

            $rates[$domainCode] = (float) $databaseRates->get($databaseCode);
        }

        return $rates;
    }

    /** @param array<string, string> $settings */
    private function stringSetting(array $settings, string $key): string
    {
        if (! array_key_exists($key, $settings)) {
            throw new RuntimeException("Simulation setting '{$key}' tidak ditemukan.");
        }

        return $settings[$key];
    }

    /** @param array<string, string> $settings */
    private function floatSetting(array $settings, string $key): float
    {
        $value = $this->stringSetting($settings, $key);

        if (! is_numeric($value)) {
            throw new RuntimeException("Simulation setting '{$key}' harus numerik.");
        }

        return (float) $value;
    }

    /** @param array<string, string> $settings */
    private function intSetting(array $settings, string $key): int
    {
        $value = $this->stringSetting($settings, $key);

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException("Simulation setting '{$key}' harus berupa integer.");
        }

        return (int) $value;
    }

    /** @param array<string, string> $settings */
    private function boolSetting(array $settings, string $key): bool
    {
        $value = $this->stringSetting($settings, $key);
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new RuntimeException("Simulation setting '{$key}' harus berupa boolean.");
        }

        return $parsed;
    }
}
