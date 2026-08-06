<?php

namespace Tests\Unit\Simulation;

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\DownPaymentConfig;
use App\Domain\Simulation\Input\FeeConfig;
use App\Domain\Simulation\Input\InsuranceConfig;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\Input\RefundConfig;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;

final class SimulationTestFactory
{
    public static function dtnConfig(?ProductConfig $product = null): SimulationConfig
    {
        return self::config($product ?? new ProductConfig(
            name: 'Reguler Passenger Sales Dealer',
            effectiveRates: [
                12 => 0.1847,
                24 => 0.1733,
                36 => 0.1766,
                48 => 0.1805,
                60 => 0.1926,
            ],
            adminMax: 5_350_000,
        ));
    }

    public static function ucfConfig(?ProductConfig $product = null): SimulationConfig
    {
        return self::config($product ?? new ProductConfig(
            name: 'Captive Passenger Low Rate',
            effectiveRates: [
                12 => 0.144699,
                24 => 0.1468,
                36 => 0.1455,
                48 => 0.1623,
                60 => 0.1650,
            ],
            adminMax: 4_700_000,
            upRate: 0.005,
        ));
    }

    public static function dtnInput(
        SimulationMode $mode = SimulationMode::A,
        InstalmentType $instalmentType = InstalmentType::ADDB,
        string $ageGroup = '36-45 tahun',
        int $vehicleYear = 2017,
        float $phpmPrice = 110_000_026,
    ): SimulationInput {
        return new SimulationInput(
            financingType: FinancingType::DTN,
            mode: $mode,
            debtorType: DebtorType::ENTREPRENEUR,
            ageGroup: $ageGroup,
            vehicleUsage: VehicleUsage::PASSENGER,
            vehicleOrigin: VehicleOrigin::JAPAN,
            stnkOwnership: StnkOwnership::OWN,
            vehicleYear: $vehicleYear,
            phpmPrice: $phpmPrice,
            instalmentType: $instalmentType,
            coverageType: CoverageType::COMPREHENSIVE_THEN_TLO,
            desiredAmount: 60_000_000,
        );
    }

    public static function ucfInput(
        SimulationMode $mode = SimulationMode::A,
        InstalmentType $instalmentType = InstalmentType::ADDB,
        DebtorType $debtorType = DebtorType::NON_ENTREPRENEUR,
        float $marketPrice = 110_000_000,
        int $vehicleYear = 2017,
        float $phpmPrice = 110_000_026,
    ): SimulationInput {
        return new SimulationInput(
            financingType: FinancingType::UCF,
            mode: $mode,
            debtorType: $debtorType,
            ageGroup: $debtorType === DebtorType::LEGAL_ENTITY ? null : '36-45 tahun',
            vehicleUsage: VehicleUsage::PASSENGER,
            vehicleOrigin: VehicleOrigin::JAPAN,
            stnkOwnership: StnkOwnership::OWN,
            vehicleYear: $vehicleYear,
            phpmPrice: $phpmPrice,
            instalmentType: $instalmentType,
            coverageType: CoverageType::TLO_ALL,
            marketPrice: $marketPrice,
            desiredAmount: 60_000_000,
        );
    }

    private static function config(ProductConfig $product): SimulationConfig
    {
        return new SimulationConfig(
            product: $product,
            insurance: new InsuranceConfig(
                activeZone: 'Wilayah 2',
                activeVariant: 'Batas Bawah',
                cascoRates: self::cascoRates(),
                sumInsuredSchedule: [1 => 1.00, 2 => 0.90, 3 => 0.80, 4 => 0.70, 5 => 0.70],
                loadingRates: [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0.05, 7 => 0.10, 8 => 0.15, 9 => 0.20, 10 => 0.25, 11 => 0, 12 => 0, 13 => 0, 14 => 0],
                extensionRates: [
                    'flood' => 0.001,
                    'earthquake' => 0.001,
                    'riot' => 0.0005,
                    'terrorism' => 0.0005,
                    'driver' => 0.005,
                    'passenger' => 0.001,
                ],
                acpBaseRates: [1 => 0.005, 2 => 0.010, 3 => 0.0153, 4 => 0.0224, 5 => 0.0288],
                acpUppings: [
                    '18-35 tahun' => 0.3,
                    '36-45 tahun' => 0.3,
                    '46-50 tahun' => 0.3,
                    '51-60 tahun' => 0.8,
                ],
                tjhTiers: [
                    ['limit' => 25_000_000, 'rate' => 0.01],
                    ['limit' => 25_000_000, 'rate' => 0.005],
                    ['limit' => 25_000_000, 'rate' => 0.0025],
                    ['limit' => null, 'rate' => 0.0015],
                ],
                engineWarrantyFee: 1_500_000,
            ),
            fees: new FeeConfig([
                ['min' => 0, 'max' => 25_000_000, 'fee' => 350_000],
                ['min' => 25_000_001, 'max' => 50_000_000, 'fee' => 375_000],
                ['min' => 50_000_001, 'max' => 100_000_000, 'fee' => 400_000],
                ['min' => 100_000_001, 'max' => 250_000_000, 'fee' => 500_000],
                ['min' => 250_000_001, 'max' => 500_000_000, 'fee' => 750_000],
                ['min' => 500_000_001, 'max' => 1_000_000_000, 'fee' => 1_150_000],
                ['min' => 1_000_000_001, 'max' => null, 'fee' => 2_250_000],
            ]),
            downPayment: new DownPaymentConfig(0.05, 0.15, 0.10, 0.30),
            refund: new RefundConfig(0.10, 1.00, 0.80, 0.80, 0.80),
            maxVehicleAge: 16,
        );
    }

    /**
     * @return array<int, array{zone: string, usage: string, variant: string, coverage: string, band_min: int, band_max: int|null, rate: float}>
     */
    private static function cascoRates(): array
    {
        $bands = [
            [0, 125_000_000, 0.0326, 0.0065],
            [125_000_001, 200_000_000, 0.0247, 0.0044],
            [200_000_001, 400_000_000, 0.0208, 0.0038],
            [400_000_001, 800_000_000, 0.0120, 0.0025],
            [800_000_001, null, 0.0105, 0.0020],
        ];
        $rows = [];

        foreach ($bands as [$min, $max, $comprehensive, $tlo]) {
            $rows[] = self::cascoRow($min, $max, 'Comprehensive', $comprehensive);
            $rows[] = self::cascoRow($min, $max, 'TLO', $tlo);
        }

        return $rows;
    }

    /**
     * @return array{zone: string, usage: string, variant: string, coverage: string, band_min: int, band_max: int|null, rate: float}
     */
    private static function cascoRow(int $min, ?int $max, string $coverage, float $rate): array
    {
        return [
            'zone' => 'Wilayah 2',
            'usage' => 'Passenger',
            'variant' => 'Batas Bawah',
            'coverage' => $coverage,
            'band_min' => $min,
            'band_max' => $max,
            'rate' => $rate,
        ];
    }
}
