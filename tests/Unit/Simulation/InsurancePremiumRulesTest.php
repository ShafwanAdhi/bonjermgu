<?php

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\Insurance\InsuranceBreakdown;
use App\Domain\Simulation\Insurance\InsuranceCalculator;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;
use Tests\Unit\Simulation\SimulationTestFactory;

function tjhInput(CoverageType $coverageType): SimulationInput
{
    return new SimulationInput(
        financingType: FinancingType::DTN,
        mode: SimulationMode::A,
        debtorType: DebtorType::ENTREPRENEUR,
        ageGroup: '36-45 tahun',
        vehicleUsage: VehicleUsage::PASSENGER,
        vehicleOrigin: VehicleOrigin::JAPAN,
        stnkOwnership: StnkOwnership::OWN,
        vehicleYear: 2017,
        phpmPrice: 110_000_026,
        instalmentType: InstalmentType::ADDB,
        coverageType: $coverageType,
        tjhAmount: 20_000_000,
    );
}

test('TJH is charged only for years covered by Comprehensive', function () {
    $calculator = new InsuranceCalculator;
    $config = SimulationTestFactory::dtnConfig();

    $tlo = $calculator->calculate(tjhInput(CoverageType::TLO_ALL), $config, 110_000_000, 60, 2026);
    $firstYearOnly = $calculator->calculate(tjhInput(CoverageType::COMPREHENSIVE_THEN_TLO), $config, 110_000_000, 60, 2026);
    $everyYear = $calculator->calculate(tjhInput(CoverageType::COMPREHENSIVE_ALL), $config, 110_000_000, 60, 2026);

    expect($tlo->tjh)->toEqual(0.0)
        ->and($firstYearOnly->tjh)->toEqual(200_000.0)
        ->and($everyYear->tjh)->toEqual(1_000_000.0);
});

test('refundable premium covers Casco, Loading and Perluasan only', function () {
    $breakdown = new InsuranceBreakdown(
        casco: 4_000_000,
        loading: 800_000,
        extensions: 200_000,
        tjh: 150_000,
        driver: 50_000,
        passenger: 40_000,
        acp: 900_000,
        engineWarranty: 1_500_000,
        total: 7_640_000,
    );

    expect($breakdown->refundablePremium())->toEqual(5_000_000.0);
});
