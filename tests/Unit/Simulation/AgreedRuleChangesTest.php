<?php

use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\SimulationEngine;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\SimulationProfile;
use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;
use Tests\Unit\Simulation\SimulationTestFactory;

test('UCF deviation percentage is measured against Harga PHPM', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(marketPrice: 145_000_000),
        SimulationTestFactory::ucfConfig(),
        2026,
    );

    // 34.999.974 / 110.000.026, not / 145.000.000
    expect($result->forTenor(12)->deviationAmount)->toEqual(34_999_974)
        ->and($result->forTenor(12)->deviationRate)->toEqualWithDelta(0.3181815066116439, 1.0E-15);
});

test('UCF minimum Net DP follows debtor type and vehicle origin', function () {
    $rateFor = function (DebtorType $debtorType, VehicleOrigin $origin) {
        $input = SimulationTestFactory::ucfInput(debtorType: $debtorType, vehicleOrigin: $origin);

        return (new SimulationEngine)
            ->simulate($input, SimulationTestFactory::ucfConfig(), 2026)
            ->forTenor(12)->minimumNetDpRate;
    };

    expect($rateFor(DebtorType::ENTREPRENEUR, VehicleOrigin::JAPAN))->toEqual(0.30)
        ->and($rateFor(DebtorType::ENTREPRENEUR, VehicleOrigin::NON_JAPAN))->toEqual(0.30)
        ->and($rateFor(DebtorType::NON_ENTREPRENEUR, VehicleOrigin::JAPAN))->toEqual(0.10)
        ->and($rateFor(DebtorType::NON_ENTREPRENEUR, VehicleOrigin::NON_JAPAN))->toEqual(0.15)
        ->and($rateFor(DebtorType::LEGAL_ENTITY, VehicleOrigin::JAPAN))->toEqual(0.10)
        ->and($rateFor(DebtorType::LEGAL_ENTITY, VehicleOrigin::NON_JAPAN))->toEqual(0.15);
});

test('UCF rejects Commercial units', function () {
    (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(vehicleUsage: VehicleUsage::COMMERCIAL),
        SimulationTestFactory::ucfConfig(),
        2026,
    );
})->throws(InvalidArgumentException::class, 'Pembiayaan Mobil Bekas hanya tersedia untuk unit Passenger.');

test('ACP applies to UCF and is charged on Harga Pasar', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(),
        SimulationTestFactory::ucfConfig(),
        2026,
    );

    // Rate dasar tenor 1 tahun 0,005 x (1 + upping 36-45 0,3) = 0,0065, atas Harga Pasar.
    expect($result->forTenor(12)->insurance->acp)->toEqualWithDelta(715_000.0, 1.0E-6);
});

test('ACP is dropped once Total A/R passes the configured ceiling', function () {
    $under = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(marketPrice: 900_000_000, phpmPrice: 2_000_000_000),
        SimulationTestFactory::ucfConfig(),
        2026,
    );
    $over = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(marketPrice: 1_400_000_000, phpmPrice: 2_000_000_000),
        SimulationTestFactory::ucfConfig(),
        2026,
    );

    expect($under->forTenor(12)->totalAccountsReceivable)->toBeLessThanOrEqual(1_000_000_000)
        ->and($under->forTenor(12)->insurance->acp)->toBeGreaterThan(0.0)
        ->and($over->forTenor(12)->totalAccountsReceivable)->toBeGreaterThan(1_000_000_000)
        ->and($over->forTenor(12)->insurance->acp)->toEqual(0.0);
});

test('Officer profile prices Dana Tunai from the appraised value and rounds Net DP up', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(phpmPrice: 275_000_026, marketPrice: 210_000_000),
        SimulationTestFactory::dtnConfig(profile: SimulationProfile::OFFICER),
        2026,
    );
    $tenor = $result->forTenor(12);

    // Harga Taksasi 210.000.000 di bawah PHPM 275.000.026, jadi tidak ada Deviasi.
    expect($tenor->otrPrice)->toEqual(210_000_000)
        ->and($tenor->deviationAmount)->toEqual(0)
        ->and($tenor->netDpAmount)->toEqual(10_500_000);
});

test('Officer profile adds deviation to Dana Tunai Net DP and rounds the rupiah up', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(marketPrice: 145_000_000),
        SimulationTestFactory::dtnConfig(profile: SimulationProfile::OFFICER),
        2026,
    );
    $tenor = $result->forTenor(12);

    // Deviasi 34.999.974 / PHPM 110.000.026 = 0,31818151...; Net DP 5% + itu.
    expect($tenor->deviationAmount)->toEqual(34_999_974)
        ->and($tenor->minimumNetDpRate)->toEqualWithDelta(0.3681815066116439, 1.0E-15)
        ->and($tenor->netDpAmount)->toEqual(53_387_000);
});

test('Referral profile keeps Dana Tunai priced from PHPM with no deviation', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(phpmPrice: 275_000_026, marketPrice: 210_000_000),
        SimulationTestFactory::dtnConfig(),
        2026,
    );
    $tenor = $result->forTenor(12);

    expect($tenor->otrPrice)->toEqual(275_000_000)
        ->and($tenor->deviationAmount)->toEqual(0)
        ->and($tenor->netDpAmount)->toEqual(13_750_000);
});

test('UCF zeroes the tenor when deviation pushes Net DP past the whole price', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(marketPrice: 260_000_000),
        SimulationTestFactory::ucfConfig(),
        2026,
    );
    $tenor = $result->forTenor(12);

    // Deviasi 149.999.974 / 110.000.026 = 136%, jadi Net DP melampaui harga unit.
    expect($tenor->ltvAmount)->toEqual(0)
        ->and($tenor->instalment)->toEqual(0)
        ->and($tenor->outputAmount)->toEqual(0)
        ->and($tenor->refund->total)->toEqual(0)
        ->and($tenor->insurance->total)->toEqual(0);
});

test('UCF Mode B carries no refund', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(SimulationMode::B),
        SimulationTestFactory::ucfConfig(),
        2026,
    );

    foreach ([12, 24, 36, 48, 60] as $tenor) {
        expect($result->forTenor($tenor)->refund->total)->toEqual(0);
    }
});

test('Officer Dana Tunai zeroes the tenor when Net DP exceeds the appraised value', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(marketPrice: 260_000_000),
        SimulationTestFactory::dtnConfig(profile: SimulationProfile::OFFICER),
        2026,
    );
    $tenor = $result->forTenor(12);

    expect($tenor->ltvAmount)->toEqual(0)
        ->and($tenor->instalment)->toEqual(0)
        ->and($tenor->outputAmount)->toEqual(0)
        ->and($tenor->insurance->total)->toEqual(0);
});
