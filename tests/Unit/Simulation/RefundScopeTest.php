<?php

use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\Input\RefundConfig;
use App\Domain\Simulation\Insurance\InsuranceBreakdown;
use App\Domain\Simulation\Refund\RefundCalculator;
use App\Domain\Simulation\SimulationEngine;
use App\Domain\Simulation\SimulationMode;
use Tests\Unit\Simulation\SimulationTestFactory;

/*
 * Refund is a payout in its own right, not a top-up to the disbursement.
 * Agreed with the client on 15 August 2026, after the stakeholder reported
 * that upping "malah ngurangin pencairan".
 *
 * Two rules come out of that:
 *
 *   1. Refund never enters Pencairan. Interest upping is not charged up front,
 *      so folding its refund into the disbursement made a higher rate pay the
 *      recipient MORE — the draft's behaviour, and the thing being corrected.
 *   2. Dana Tunai earns Refund Bunga and Refund Provisi. Refund Asuransi and
 *      Refund Admin stay with Pembiayaan Mobil Bekas.
 */

function upcountProduct(float $upRate = 0.03, float $upProvision = 0.03): ProductConfig
{
    return new ProductConfig(
        name: 'Reguler Passenger Sales Dealer',
        effectiveRates: [12 => 0.1847, 24 => 0.1733, 36 => 0.1766, 48 => 0.1805, 60 => 0.1926],
        adminMax: 5_350_000,
        provisionRate: 0.01,
        upRate: $upRate,
        upAdmin: 2_000_000,
        upProvision: $upProvision,
    );
}

it('gives Dana Tunai a refund on interest and provision only', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::dtnInput(),
        SimulationTestFactory::dtnConfig(upcountProduct()),
        2026,
    );
    $refund = $result->forTenor(24)->refund;

    expect($refund->interest)->toBeGreaterThan(0.0)
        ->and($refund->provision)->toBeGreaterThan(0.0)
        ->and($refund->insurance)->toEqual(0.0)
        ->and($refund->administration)->toEqual(0.0)
        ->and($refund->total)->toBeGreaterThan(0);
});

it('keeps Refund Asuransi and Refund Admin on Pembiayaan Mobil Bekas', function () {
    $result = (new SimulationEngine)->simulate(
        SimulationTestFactory::ucfInput(),
        SimulationTestFactory::ucfConfig(upcountProduct()),
        2026,
    );
    $refund = $result->forTenor(24)->refund;

    expect($refund->insurance)->toBeGreaterThan(0.0)
        ->and($refund->administration)->toBeGreaterThan(0.0);
});

it('never adds refund to the disbursement', function (string $product) {
    $input = $product === 'DTN'
        ? SimulationTestFactory::dtnInput()
        : SimulationTestFactory::ucfInput();
    $config = $product === 'DTN'
        ? SimulationTestFactory::dtnConfig(upcountProduct())
        : SimulationTestFactory::ucfConfig(upcountProduct());

    $result = (new SimulationEngine)->simulate($input, $config, 2026);

    foreach ([12, 24, 36, 48, 60] as $tenor) {
        $row = $result->forTenor($tenor);

        expect($row->refund->total)->toBeGreaterThan(0)
            ->and($row->outputAmount)->toEqual($row->netDisbursement);
    }
})->with(['DTN', 'UCF']);

/*
 * The correction the stakeholder was really after: interest is not charged up
 * front, so raising it must not move the disbursement at all.
 */
it('leaves the disbursement untouched when only the interest upping changes', function (string $product) {
    $engine = new SimulationEngine;
    $input = $product === 'DTN'
        ? SimulationTestFactory::dtnInput()
        : SimulationTestFactory::ucfInput();
    $configFor = fn (float $upRate) => $product === 'DTN'
        ? SimulationTestFactory::dtnConfig(upcountProduct(upRate: $upRate, upProvision: 0.0))
        : SimulationTestFactory::ucfConfig(upcountProduct(upRate: $upRate, upProvision: 0.0));

    $flat = $engine->simulate($input, $configFor(0.0), 2026)->forTenor(24);
    $upped = $engine->simulate($input, $configFor(0.03), 2026)->forTenor(24);

    expect($upped->outputAmount)->toEqual($flat->outputAmount)
        ->and($upped->instalment)->toBeGreaterThan($flat->instalment);
})->with(['DTN', 'UCF']);

it('still produces no refund in Mode B', function (string $product) {
    $input = $product === 'DTN'
        ? SimulationTestFactory::dtnInput(SimulationMode::B)
        : SimulationTestFactory::ucfInput(SimulationMode::B);
    $config = $product === 'DTN'
        ? SimulationTestFactory::dtnConfig(upcountProduct())
        : SimulationTestFactory::ucfConfig(upcountProduct());

    $result = (new SimulationEngine)->simulate($input, $config, 2026);

    foreach ([12, 24, 36, 48, 60] as $tenor) {
        expect($result->forTenor($tenor)->refund->total)->toEqual(0);
    }
})->with(['DTN', 'UCF']);

/*
 * The client's worked example, 15 August 2026. Net Present Value discounts on
 * the rate BEFORE upping — the upping itself is the thing being refunded, so
 * it does not belong in the discount factor.
 *
 *   Pokok Hutang  100.000.000
 *   Upping bunga  3%
 *   Tenor         60 bulan (5 tahun)
 *   Rate bottom   10% per tahun  ->  NPV = 1 + (10% x 5) = 1,50
 *   Refund        80%
 *
 *   100.000.000 x (3% x 5) = 15.000.000
 *   15.000.000 / 1,50 x 80% = 8.000.000
 */
it('discounts the interest refund on the rate before upping', function () {
    $refund = (new RefundCalculator)->calculate(
        FinancingType::DTN,
        InsuranceBreakdown::zero(),
        new ProductConfig(
            name: 'Contoh',
            effectiveRates: [12 => 0.10, 24 => 0.10, 36 => 0.10, 48 => 0.10, 60 => 0.10],
            adminMax: 0,
            upRate: 0.03,
        ),
        new RefundConfig(
            insuranceBaseRate: 0.10,
            insuranceRefundRate: 1.00,
            interestRefundRate: 0.80,
            provisionRefundRate: 0.80,
            adminRefundRate: 0.80,
        ),
        ltvAmount: 100_000_000,
        tenorMonths: 60,
        // Bunga Jual atas rate bottom saja: 10% x 5 tahun.
        baseSellingInterestRate: 0.50,
        provision: 0,
    );

    expect($refund->interest)->toEqualWithDelta(8_000_000.0, 1.0E-6);
});
