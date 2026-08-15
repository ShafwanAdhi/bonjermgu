<?php

use App\Domain\Simulation\SimulationEngine;
use App\Domain\Simulation\SimulationMode;
use Tests\Unit\Simulation\SimulationTestFactory;

/*
 * Deposit Angsuran is a count of instalments held back, not a rupiah figure.
 * Agreed with the client on 13 August 2026 after the stakeholder pointed out
 * the screen was asking for the wrong kind of number.
 *
 * Because the instalment differs per tenor, so does the deposit — it cannot be
 * one flat deduction shared by all five columns.
 */

it('withholds count times the instalment of that tenor', function () {
    $engine = new SimulationEngine;
    $config = SimulationTestFactory::dtnConfig(depositInstalmentCount: 2);
    $none = SimulationTestFactory::dtnConfig();

    $withDeposit = $engine->simulate(SimulationTestFactory::dtnInput(), $config, 2026);
    $without = $engine->simulate(SimulationTestFactory::dtnInput(), $none, 2026);

    foreach ([12, 24, 36, 48, 60] as $tenor) {
        $held = $withDeposit->forTenor($tenor);
        $free = $without->forTenor($tenor);

        expect($held->depositInstalmentAmount)->toEqual(2 * $free->instalment)
            ->and($held->netDisbursement)->toEqual($free->netDisbursement - 2 * $free->instalment);
    }
});

it('withholds nothing when the count is zero', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(),
        SimulationTestFactory::dtnConfig(depositInstalmentCount: 0),
        2026,
    );

    expect($result->forTenor(12)->depositInstalmentAmount)->toEqual(0);
});

/*
 * The draft multiplied Dana Tunai by Angsuran Pertama, which is zero on ADDB,
 * so the deposit silently never applied. Both products now use the ordinary
 * instalment (XLBUG-07).
 */
it('applies on ADDB, where the draft silently withheld nothing', function () {
    $engine = new SimulationEngine;
    $config = SimulationTestFactory::dtnConfig(depositInstalmentCount: 3);

    $tenor = $engine->simulate(SimulationTestFactory::dtnInput(), $config, 2026)->forTenor(12);

    expect($tenor->firstInstalment)->toEqual(0)
        ->and($tenor->depositInstalmentAmount)->toEqual(3 * $tenor->instalment)
        ->and($tenor->depositInstalmentAmount)->toBeGreaterThan(0);
});

it('withholds from Mobil Bekas disbursement without touching Total Bayar Pertama', function () {
    $engine = new SimulationEngine;
    $held = $engine->simulate(
        SimulationTestFactory::ucfInput(),
        SimulationTestFactory::ucfConfig(depositInstalmentCount: 2),
        2026,
    )->forTenor(24);
    $free = $engine->simulate(
        SimulationTestFactory::ucfInput(),
        SimulationTestFactory::ucfConfig(),
        2026,
    )->forTenor(24);

    expect($held->depositInstalmentAmount)->toEqual(2 * $free->instalment)
        ->and($held->firstPayment)->toEqual($free->firstPayment)
        ->and($held->outputAmount)->toEqual($free->outputAmount - 2 * $free->instalment);
});

it('withholds nothing in Mode B, which produces no disbursement', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(SimulationMode::B),
        SimulationTestFactory::dtnConfig(depositInstalmentCount: 5),
        2026,
    );

    expect($result->forTenor(12)->depositInstalmentAmount)->toEqual(0);
});
