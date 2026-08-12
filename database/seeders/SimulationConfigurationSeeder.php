<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SimulationConfigurationSeeder extends Seeder
{
    private const TENORS = [12, 24, 36, 48, 60];

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedProducts();
            $this->seedCascoRates();
            $this->seedLoadingRates();
            $this->seedExtensionRates();
            $this->seedAcp();
            $this->seedTjhTiers();
            $this->seedFiduciaTiers();
            $this->seedSumInsuredSchedule();
            $this->seedSettings();
        });
    }

    private function seedProducts(): void
    {
        $products = $this->products();

        DB::table('products')->upsert(
            array_map(fn (array $product) => [
                'name' => $product['name'],
                'dp_rate' => $product['dp_rate'],
                'admin_min' => $product['admin_min'],
                'admin_max' => $product['admin_max'],
                'provisi_rate' => $product['provisi_rate'],
                'up_rate' => 0,
                'up_admin' => 0,
                'up_provisi' => 0,
                'is_active' => true,
            ], $products),
            ['name'],
            [
                'dp_rate', 'admin_min', 'admin_max', 'provisi_rate',
                'up_rate', 'up_admin', 'up_provisi', 'is_active',
            ],
        );

        $productIds = DB::table('products')->pluck('id', 'name');
        $rates = [];

        foreach ($products as $product) {
            foreach (self::TENORS as $index => $tenor) {
                $rates[] = [
                    'product_id' => $productIds[$product['name']],
                    'tenor_months' => $tenor,
                    // NULL means unavailable, never zero percent.
                    'effective_rate' => $product['rates'][$index],
                ];
            }
        }

        DB::table('product_rates')->upsert(
            $rates,
            ['product_id', 'tenor_months'],
            ['effective_rate'],
        );
    }

    private function seedCascoRates(): void
    {
        $bands = [
            [0, 125_000_000],
            [125_000_001, 200_000_000],
            [200_000_001, 400_000_000],
            [400_000_001, 800_000_000],
            [800_000_001, null],
        ];

        $variants = [
            'Batas Atas' => [
                ['Passenger' => ['Comprehensive' => 0.0359, 'TLO' => 0.0078], 'Commercial' => ['Comprehensive' => 0.0369, 'TLO' => 0.0085]],
                ['Passenger' => ['Comprehensive' => 0.0272, 'TLO' => 0.0053], 'Commercial' => ['Comprehensive' => 0.0282, 'TLO' => 0.0057]],
                ['Passenger' => ['Comprehensive' => 0.0229, 'TLO' => 0.0042], 'Commercial' => ['Comprehensive' => 0.0239, 'TLO' => 0.0045]],
                ['Passenger' => ['Comprehensive' => 0.0132, 'TLO' => 0.0030], 'Commercial' => ['Comprehensive' => 0.0142, 'TLO' => 0.0033]],
                ['Passenger' => ['Comprehensive' => 0.0116, 'TLO' => 0.0024], 'Commercial' => ['Comprehensive' => 0.0126, 'TLO' => 0.0026]],
            ],
            'Batas Bawah' => [
                ['Passenger' => ['Comprehensive' => 0.0326, 'TLO' => 0.0065], 'Commercial' => ['Comprehensive' => 0.0336, 'TLO' => 0.0072]],
                ['Passenger' => ['Comprehensive' => 0.0247, 'TLO' => 0.0044], 'Commercial' => ['Comprehensive' => 0.0257, 'TLO' => 0.0048]],
                ['Passenger' => ['Comprehensive' => 0.0208, 'TLO' => 0.0038], 'Commercial' => ['Comprehensive' => 0.0218, 'TLO' => 0.0041]],
                ['Passenger' => ['Comprehensive' => 0.0120, 'TLO' => 0.0025], 'Commercial' => ['Comprehensive' => 0.0130, 'TLO' => 0.0028]],
                ['Passenger' => ['Comprehensive' => 0.0105, 'TLO' => 0.0020], 'Commercial' => ['Comprehensive' => 0.0115, 'TLO' => 0.0022]],
            ],
        ];

        $rows = [];

        foreach ($variants as $variant => $bandRates) {
            foreach ($bandRates as $bandIndex => $usageRates) {
                foreach ($usageRates as $usage => $coverageRates) {
                    foreach ($coverageRates as $coverage => $rate) {
                        $rows[] = [
                            'zone' => 'Wilayah 2',
                            'usage' => $usage,
                            'variant' => $variant,
                            'coverage' => $coverage,
                            'band_min' => $bands[$bandIndex][0],
                            'band_max' => $bands[$bandIndex][1],
                            'rate' => $rate,
                        ];
                    }
                }
            }
        }

        DB::table('insurance_casco_rates')->upsert(
            $rows,
            ['zone', 'usage', 'variant', 'coverage', 'band_min'],
            ['band_max', 'rate'],
        );
    }

    private function seedLoadingRates(): void
    {
        $rows = [];

        for ($age = 0; $age <= 14; $age++) {
            $rows[] = [
                'vehicle_age' => $age,
                'rate' => match ($age) {
                    6 => 0.05,
                    7 => 0.10,
                    8 => 0.15,
                    9 => 0.20,
                    10 => 0.25,
                    default => 0,
                },
            ];
        }

        DB::table('insurance_loading_rates')->upsert($rows, ['vehicle_age'], ['rate']);
    }

    private function seedExtensionRates(): void
    {
        DB::table('insurance_extension_rates')->upsert([
            ['code' => 'banjir', 'rate' => 0.0010],
            ['code' => 'gempa', 'rate' => 0.0010],
            ['code' => 'huru_hara', 'rate' => 0.0005],
            ['code' => 'teroris', 'rate' => 0.0005],
            ['code' => 'pengemudi', 'rate' => 0.0050],
            ['code' => 'penumpang', 'rate' => 0.0010],
        ], ['code'], ['rate']);
    }

    private function seedAcp(): void
    {
        DB::table('acp_base_rates')->upsert([
            ['tenor_years' => 1, 'rate' => 0.0050],
            ['tenor_years' => 2, 'rate' => 0.0100],
            ['tenor_years' => 3, 'rate' => 0.0153],
            ['tenor_years' => 4, 'rate' => 0.0224],
            ['tenor_years' => 5, 'rate' => 0.0288],
        ], ['tenor_years'], ['rate']);

        $ageGroupIds = DB::table('age_groups')->pluck('id', 'label');
        $uppings = [
            '18-35 tahun' => 0.3,
            '36-45 tahun' => 0.3,
            '46-50 tahun' => 0.3,
            '51-60 tahun' => 0.8,
        ];

        $rows = [];

        foreach ($uppings as $label => $upping) {
            if (! isset($ageGroupIds[$label])) {
                throw new RuntimeException("Kelompok usia '{$label}' belum tersedia.");
            }

            $rows[] = ['age_group_id' => $ageGroupIds[$label], 'upping' => $upping];
        }

        DB::table('acp_uppings')->upsert($rows, ['age_group_id'], ['upping']);
    }

    private function seedTjhTiers(): void
    {
        DB::table('tjh_tiers')->upsert([
            ['sequence' => 1, 'limit_amount' => 25_000_000, 'rate' => 0.0100],
            ['sequence' => 2, 'limit_amount' => 25_000_000, 'rate' => 0.0050],
            ['sequence' => 3, 'limit_amount' => 25_000_000, 'rate' => 0.0025],
            ['sequence' => 4, 'limit_amount' => null, 'rate' => 0.0015],
        ], ['sequence'], ['limit_amount', 'rate']);
    }

    private function seedFiduciaTiers(): void
    {
        DB::table('fiducia_tiers')->upsert([
            ['min_amount' => 0, 'max_amount' => 25_000_000, 'fee' => 350_000],
            ['min_amount' => 25_000_001, 'max_amount' => 50_000_000, 'fee' => 375_000],
            ['min_amount' => 50_000_001, 'max_amount' => 100_000_000, 'fee' => 400_000],
            ['min_amount' => 100_000_001, 'max_amount' => 250_000_000, 'fee' => 500_000],
            ['min_amount' => 250_000_001, 'max_amount' => 500_000_000, 'fee' => 750_000],
            ['min_amount' => 500_000_001, 'max_amount' => 1_000_000_000, 'fee' => 1_150_000],
            ['min_amount' => 1_000_000_001, 'max_amount' => null, 'fee' => 2_250_000],
        ], ['min_amount'], ['max_amount', 'fee']);
    }

    private function seedSumInsuredSchedule(): void
    {
        DB::table('sum_insured_schedules')->upsert([
            ['year_index' => 1, 'percentage' => 1.00],
            ['year_index' => 2, 'percentage' => 0.90],
            ['year_index' => 3, 'percentage' => 0.80],
            ['year_index' => 4, 'percentage' => 0.70],
            ['year_index' => 5, 'percentage' => 0.70],
        ], ['year_index'], ['percentage']);
    }

    private function seedSettings(): void
    {
        $settings = [
            'max_vehicle_age' => '16',
            'engine_warranty_fee' => '1500000',
            'active_insurance_zone' => 'Wilayah 2',
            'active_rate_variant' => 'Batas Bawah',
            'default_deposit_instalment_amount' => '0',
            'default_bbnkb_amount' => '0',
            'default_pkb_amount' => '0',
            'default_invoice_amount' => '0',
            'default_flood_enabled' => 'false',
            'default_earthquake_enabled' => 'false',
            'default_riot_enabled' => 'false',
            'default_terrorism_enabled' => 'false',
            'default_tjh_amount' => '0',
            'default_driver_coverage_amount' => '0',
            'default_passenger_coverage_amount' => '0',
            'default_passenger_count' => '0',
            'default_engine_warranty_enabled' => 'true',
            'tjh_max_amount' => '50000000',
            'tjh_step_amount' => '5000000',
            'dtn_standard_net_dp_rate' => '0.0500',
            'dtn_high_risk_net_dp_rate' => '0.1500',
            'ucf_standard_net_dp_rate' => '0.1000',
            'ucf_non_japan_net_dp_rate' => '0.1500',
            'ucf_entrepreneur_net_dp_rate' => '0.3000',
            'dtn_acp_enabled' => 'true',
            'ucf_acp_enabled' => 'true',
            'acp_max_loan_amount' => '1000000000',
            'ucf_insurance_refund_base_rate' => '0.1000',
            'ucf_insurance_refund_rate' => '1.0000',
            'ucf_interest_refund_rate' => '0.8000',
            'ucf_provision_refund_rate' => '0.8000',
            'ucf_admin_refund_rate' => '0.8000',
        ];

        DB::table('simulation_settings')->upsert(
            array_map(
                fn (string $key, string $value) => compact('key', 'value'),
                array_keys($settings),
                array_values($settings),
            ),
            ['key'],
            ['value'],
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function products(): array
    {
        return [
            ['name' => 'Captive Passenger Khusus Karyawan Bank Mandiri', 'rates' => [0.1026, 0.0966, 0.0939, 0.0966, 0.1037], 'dp_rate' => 0.05, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Khusus SME & Private/Prioritas', 'rates' => [0.1374, 0.1345, 0.1350, 0.1497, 0.1589], 'dp_rate' => 0.05, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Semangat', 'rates' => [0.1688, 0.1636, 0.1629, 0.1762, 0.1804], 'dp_rate' => 0.05, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Tengah', 'rates' => [0.1769, 0.1685, 0.1697, 0.1784, 0.1865], 'dp_rate' => 0.05, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Cuan', 'rates' => [0.1847, 0.1733, 0.1766, 0.1805, 0.1926], 'dp_rate' => 0.05, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Commercial Semangat', 'rates' => [0.1983, 0.1907, 0.1931, 0.1921, null], 'dp_rate' => 0.15, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Commercial Tengah', 'rates' => [0.2035, 0.1979, 0.1999, 0.1981, null], 'dp_rate' => 0.15, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Captive Commercial Cuan', 'rates' => [0.2088, 0.2050, 0.2065, 0.2039, null], 'dp_rate' => 0.15, 'admin_min' => 3_500_000, 'admin_max' => 4_400_000, 'provisi_rate' => 0],
            ['name' => 'Reguler Passenger Referral', 'rates' => [0.17253, 0.1750, 0.1800, 0.1869, 0.1907], 'dp_rate' => 0.05, 'admin_min' => 3_750_000, 'admin_max' => 5_350_000, 'provisi_rate' => 0],
            ['name' => 'Reguler Passenger TOMI Spesial Referral', 'rates' => [0.1000, 0.1025, 0.1050, 0.1075, 0.1100], 'dp_rate' => 0.05, 'admin_min' => 3_750_000, 'admin_max' => 5_350_000, 'provisi_rate' => 0],
            ['name' => 'Reguler Commercial Referral', 'rates' => [0.2035, 0.1955, 0.1976, 0.1963, null], 'dp_rate' => 0.15, 'admin_min' => 3_750_000, 'admin_max' => 5_350_000, 'provisi_rate' => 0],
            ['name' => 'Reguler Passenger Sales Dealer', 'rates' => [0.1847, 0.1733, 0.1766, 0.1805, 0.1926], 'dp_rate' => 0.15, 'admin_min' => 3_500_000, 'admin_max' => 5_350_000, 'provisi_rate' => 0],
            ['name' => 'Reguler Commercial Sales Dealer', 'rates' => [0.2088, 0.2050, 0.2065, 0.2039, null], 'dp_rate' => 0.15, 'admin_min' => 3_500_000, 'admin_max' => 5_350_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Authorized Showroom', 'rates' => [0.1400, 0.1400, 0.1400, 0.1600, 0.1600], 'dp_rate' => 0.20, 'admin_min' => 4_700_000, 'admin_max' => 4_700_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Low Rate', 'rates' => [0.144699, 0.1468, 0.1455, 0.1623, 0.1650], 'dp_rate' => 0.10, 'admin_min' => 4_700_000, 'admin_max' => 4_700_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger Authorized Reguler', 'rates' => [0.1622, 0.1643, 0.1624, 0.1751, 0.1766], 'dp_rate' => 0.10, 'admin_min' => 4_700_000, 'admin_max' => 4_700_000, 'provisi_rate' => 0],
            ['name' => 'Captive Passenger High Benefit', 'rates' => [0.1797, 0.1816, 0.1792, 0.1943, 0.1918], 'dp_rate' => 0.10, 'admin_min' => 4_700_000, 'admin_max' => 4_700_000, 'provisi_rate' => 0],
        ];
    }
}
