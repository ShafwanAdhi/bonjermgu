<?php

namespace Tests\Unit\Simulation;

use App\Domain\Simulation\DanaTunaiCalculator;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\MobilBekasCalculator;
use App\Domain\Simulation\Output\ZeroReason;
use App\Domain\Simulation\SimulationMode;

/*
 * Presentation-only addition: TenorResult now carries *why* a tenor is zero,
 * read from decisions the calculators already made. Nothing here is a
 * calculation change — the reason is attached at the same branches that
 * already returned a zero row, so credit-simulation-test-vectors.md's 250
 * points staying green (proven by the Simulation gate) is what guarantees no
 * arithmetic moved. This file only proves the label attached is the right one.
 */

it('tags a vehicle past its eligibility age', function () {
    $result = (new DanaTunaiCalculator)->calculate(
        SimulationTestFactory::dtnInput(vehicleYear: 2005),
        SimulationTestFactory::dtnConfig(),
        2026,
    );

    expect($result->forTenor(60)->zeroReason)->toBe(ZeroReason::NotEligible);
});

it('tags a tenor with no configured rate', function () {
    $product = new ProductConfig(
        name: 'Reguler Commercial Sales Dealer',
        effectiveRates: [12 => 0.1983, 24 => 0.1907, 36 => 0.1931, 48 => 0.1921, 60 => null],
        adminMax: 4_400_000,
    );

    $result = (new DanaTunaiCalculator)->calculate(
        SimulationTestFactory::dtnInput(),
        SimulationTestFactory::dtnConfig($product),
        2026,
    );

    expect($result->forTenor(60)->zeroReason)->toBe(ZeroReason::RateUnavailable)
        ->and($result->forTenor(48)->zeroReason)->toBeNull();
});

it('tags a vehicle model with no price on the selected year', function () {
    $result = (new DanaTunaiCalculator)->calculate(
        SimulationTestFactory::dtnInput(phpmPrice: 0),
        SimulationTestFactory::dtnConfig(),
        2026,
    );

    expect($result->forTenor(12)->zeroReason)->toBe(ZeroReason::PriceUnavailable);
});

it('tags a deviation large enough to push Net DP to the unit price', function () {
    $result = (new MobilBekasCalculator)->calculate(
        SimulationTestFactory::ucfInput(marketPrice: 20_000_000_000, phpmPrice: 110_000_026),
        SimulationTestFactory::ucfConfig(),
        2026,
    );

    expect($result->forTenor(12)->zeroReason)->toBe(ZeroReason::DownPaymentExceedsPrice);
});

it('tags a Mode B amount that does not clear the minimum down payment', function () {
    // Mirrors S5 in the test vectors: a deviation that raises minimum Net DP
    // well past a modest desired amount.
    $result = (new MobilBekasCalculator)->calculate(
        SimulationTestFactory::ucfInput(
            mode: SimulationMode::B,
            debtorType: DebtorType::ENTREPRENEUR,
            marketPrice: 145_000_000,
            phpmPrice: 110_000_026,
        ),
        SimulationTestFactory::ucfConfig(),
        2026,
    );

    expect($result->forTenor(12)->zeroReason)->toBe(ZeroReason::DownPaymentBelowMinimum);
});

it('leaves a normal tenor without a reason', function () {
    $result = (new DanaTunaiCalculator)->calculate(
        SimulationTestFactory::dtnInput(),
        SimulationTestFactory::dtnConfig(),
        2026,
    );

    expect($result->forTenor(12)->zeroReason)->toBeNull();
});
