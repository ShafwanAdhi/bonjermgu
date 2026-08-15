<?php

use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationEngine;
use App\Domain\Simulation\SimulationMode;
use Tests\Unit\Simulation\SimulationTestFactory;

function assertMoneySeries(array $expected, callable $actual): void
{
    foreach ($expected as $tenor => $value) {
        expect($actual($tenor))->toEqual($value);
    }
}

test('S1 Dana Tunai ADDB matches the reference vectors', function () {
    $engine = new SimulationEngine;
    $modeA = $engine->simulate(SimulationTestFactory::dtnInput(), SimulationTestFactory::dtnConfig(), 2026);
    $modeB = $engine->simulate(SimulationTestFactory::dtnInput(SimulationMode::B), SimulationTestFactory::dtnConfig(), 2026);

    $flatRates = [12 => 0.10284584815780895, 24 => 0.09521169008433106, 36 => 0.09845143022123226, 48 => 0.10281359899688652, 60 => 0.11300587506610578];
    foreach ($flatRates as $tenor => $flatRate) {
        expect($modeA->forTenor($tenor)->flatRate)->toEqualWithDelta($flatRate, 1.0E-14);
    }

    $sellingInterestRates = [12 => 0.10284584815780895, 24 => 0.19042338016866212, 36 => 0.29535429066369680, 48 => 0.41125439598754610, 60 => 0.56502937533052890];
    foreach ($sellingInterestRates as $tenor => $sellingInterestRate) {
        expect($modeA->forTenor($tenor)->sellingInterestRate)->toEqualWithDelta($sellingInterestRate, 1.0E-14);
    }

    expect($modeA->forTenor(12)->otrPrice)->toEqual(110_000_000)
        ->and($modeA->forTenor(12)->netDpRate)->toEqual(0.05)
        ->and($modeA->forTenor(12)->netDpAmount)->toEqual(5_500_000)
        ->and($modeA->forTenor(12)->ltvRate)->toEqual(0.95)
        ->and($modeA->forTenor(12)->ltvAmount)->toEqual(104_500_000);

    assertMoneySeries([12 => 6_518_200, 24 => 7_876_700, 36 => 9_206_600, 48 => 10_722_400, 60 => 12_138_100], fn ($tenor) => $modeA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 17_868_200, 24 => 19_226_700, 36 => 20_556_600, 48 => 22_072_400, 60 => 23_488_100], fn ($tenor) => $modeA->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 92_131_800, 24 => 90_773_300, 36 => 89_443_400, 48 => 87_927_600, 60 => 86_511_900], fn ($tenor) => $modeA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 9_604_000, 24 => 5_184_000, 36 => 3_761_000, 48 => 3_073_000, 60 => 2_726_000], fn ($tenor) => $modeA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 12_368_200, 24 => 13_726_700, 36 => 15_056_600, 48 => 16_572_400, 60 => 17_988_100], fn ($tenor) => $modeB->forTenor($tenor)->totalDownPayment);
    assertMoneySeries([12 => 37_631_826, 24 => 36_273_326, 36 => 34_943_426, 48 => 33_427_626, 60 => 32_011_926], fn ($tenor) => $modeB->forTenor($tenor)->netDpAmount);
    assertMoneySeries([12 => 72_368_200, 24 => 73_726_700, 36 => 75_056_600, 48 => 76_572_400, 60 => 77_988_100], fn ($tenor) => $modeB->forTenor($tenor)->ltvAmount);
    assertMoneySeries([12 => 6_651_000, 24 => 3_657_000, 36 => 2_701_000, 48 => 2_252_000, 60 => 2_035_000], fn ($tenor) => $modeB->forTenor($tenor)->instalment);
});

test('S2 Mobil Bekas ADDB matches the reference vectors', function () {
    $engine = new SimulationEngine;
    $modeA = $engine->simulate(SimulationTestFactory::ucfInput(), SimulationTestFactory::ucfConfig(), 2026);
    $modeB = $engine->simulate(SimulationTestFactory::ucfInput(SimulationMode::B), SimulationTestFactory::ucfConfig(), 2026);

    $flatRateFinals = [12 => 0.08510036380248776, 24 => 0.08501699398093465, 36 => 0.08501107580728577, 48 => 0.09649856929686740, 60 => 0.10001425315586772];
    $sellingInterestRates = [12 => 0.08510036380248776, 24 => 0.17003398796186930, 36 => 0.25503322742185730, 48 => 0.38599427718746960, 60 => 0.50007126577933860];
    foreach ($flatRateFinals as $tenor => $flatRateFinal) {
        expect($modeA->forTenor($tenor)->flatRateFinal)->toEqualWithDelta($flatRateFinal, 1.0E-14)
            ->and($modeA->forTenor($tenor)->sellingInterestRate)->toEqualWithDelta($sellingInterestRates[$tenor], 1.0E-14);
    }

    assertMoneySeries([12 => 2_930_000, 24 => 4_288_500, 36 => 5_618_400, 48 => 7_134_200, 60 => 8_549_900], fn ($tenor) => $modeA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 19_130_000, 24 => 20_488_500, 36 => 21_818_400, 48 => 23_334_200, 60 => 24_749_900], fn ($tenor) => $modeA->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 90_870_000, 24 => 89_511_500, 36 => 88_181_600, 48 => 86_665_800, 60 => 85_250_100], fn ($tenor) => $modeA->forTenor($tenor)->netDisbursement);
    assertMoneySeries([12 => 438_000, 24 => 818_000, 36 => 1_151_000, 48 => 1_402_000, 60 => 1_635_000], fn ($tenor) => $modeA->forTenor($tenor)->refund->total);
    assertMoneySeries([12 => 90_870_000, 24 => 89_511_500, 36 => 88_181_600, 48 => 86_665_800, 60 => 85_250_100], fn ($tenor) => $modeA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 8_953_000, 24 => 4_827_000, 36 => 3_452_000, 48 => 2_859_000, 60 => 2_476_000], fn ($tenor) => $modeA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 8_130_000, 24 => 9_488_500, 36 => 10_818_400, 48 => 12_334_200, 60 => 13_749_900], fn ($tenor) => $modeB->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 51_870_000, 24 => 50_511_500, 36 => 49_181_600, 48 => 47_665_800, 60 => 46_250_100], fn ($tenor) => $modeB->forTenor($tenor)->netDpAmount);
    assertMoneySeries([12 => 58_130_000, 24 => 59_488_500, 36 => 60_818_400, 48 => 62_334_200, 60 => 63_749_900], fn ($tenor) => $modeB->forTenor($tenor)->ltvAmount);
    assertMoneySeries([12 => 5_257_000, 24 => 2_901_000, 36 => 2_121_000, 48 => 1_800_000, 60 => 1_594_000], fn ($tenor) => $modeB->forTenor($tenor)->instalment);
});

test('S3 and S4 ADDM match the reference vectors', function () {
    $engine = new SimulationEngine;
    $dtnA = $engine->simulate(SimulationTestFactory::dtnInput(instalmentType: InstalmentType::ADDM), SimulationTestFactory::dtnConfig(), 2026);
    $dtnB = $engine->simulate(SimulationTestFactory::dtnInput(SimulationMode::B, InstalmentType::ADDM), SimulationTestFactory::dtnConfig(), 2026);
    $ucfA = $engine->simulate(SimulationTestFactory::ucfInput(instalmentType: InstalmentType::ADDM), SimulationTestFactory::ucfConfig(), 2026);
    $ucfB = $engine->simulate(SimulationTestFactory::ucfInput(SimulationMode::B, InstalmentType::ADDM), SimulationTestFactory::ucfConfig(), 2026);

    assertMoneySeries([12 => 9_459_000, 24 => 5_110_000, 36 => 3_706_000, 48 => 3_027_000, 60 => 2_683_000], fn ($tenor) => $dtnA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 6_551_000, 24 => 3_605_000, 36 => 2_662_000, 48 => 2_218_000, 60 => 2_003_000], fn ($tenor) => $dtnB->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 92_131_800, 24 => 90_773_300, 36 => 89_443_400, 48 => 87_927_600, 60 => 86_511_900], fn ($tenor) => $dtnA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 6_518_200, 24 => 7_876_700, 36 => 9_206_600, 48 => 10_722_400, 60 => 12_138_100], fn ($tenor) => $dtnA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 17_868_200, 24 => 19_226_700, 36 => 20_556_600, 48 => 22_072_400, 60 => 23_488_100], fn ($tenor) => $dtnA->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 82_024_000, 24 => 84_742_500, 36 => 84_770_600, 48 => 83_843_800, 60 => 82_807_100], fn ($tenor) => $ucfA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 8_846_000, 24 => 4_769_000, 36 => 3_411_000, 48 => 2_822_000, 60 => 2_443_000], fn ($tenor) => $ucfA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 5_704_000, 24 => 3_011_000, 36 => 2_170_000, 48 => 1_829_000, 60 => 1_613_000], fn ($tenor) => $ucfB->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 2_930_000, 24 => 4_288_500, 36 => 5_618_400, 48 => 7_134_200, 60 => 8_549_900], fn ($tenor) => $ucfA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 442_000, 24 => 826_000, 36 => 1_162_000, 48 => 1_418_000, 60 => 1_653_000], fn ($tenor) => $ucfA->forTenor($tenor)->refund->total);
});

test('S5 deviation and minimum DP rejection match the reference vectors', function () {
    $engine = new SimulationEngine;
    $inputA = SimulationTestFactory::ucfInput(debtorType: DebtorType::ENTREPRENEUR, marketPrice: 145_000_000);
    $inputB = SimulationTestFactory::ucfInput(SimulationMode::B, debtorType: DebtorType::ENTREPRENEUR, marketPrice: 145_000_000);
    $modeA = $engine->simulate($inputA, SimulationTestFactory::ucfConfig(), 2026);
    $modeB = $engine->simulate($inputB, SimulationTestFactory::ucfConfig(), 2026);

    expect($modeA->forTenor(12)->deviationAmount)->toEqual(34_999_974)
        ->and($modeA->forTenor(12)->deviationRate)->toEqualWithDelta(0.3181815066116439, 1.0E-15)
        ->and($modeA->forTenor(12)->netDpAmount)->toEqualWithDelta(89_636_318.45868836, 1.0E-6);
    assertMoneySeries([12 => 3_080_500, 24 => 4_597_200, 36 => 6_350_300, 48 => 8_348_400, 60 => 10_214_500], fn ($tenor) => $modeA->forTenor($tenor)->insurance->total);
    foreach ([12 => 97_916_818.45868836, 24 => 99_433_518.45868836, 36 => 101_186_618.45868836, 48 => 103_184_718.45868836, 60 => 105_050_818.45868836] as $tenor => $firstPayment) {
        expect($modeA->forTenor($tenor)->firstPayment)->toEqualWithDelta($firstPayment, 1.0E-6);
    }
    foreach ([12 => 47_083_181.54131164, 24 => 45_566_481.54131164, 36 => 43_813_381.54131164, 48 => 41_815_281.54131164, 60 => 39_949_181.54131164] as $tenor => $disbursement) {
        expect($modeA->forTenor($tenor)->outputAmount)->toEqualWithDelta($disbursement, 1.0E-6);
    }
    assertMoneySeries([12 => 5_007_000, 24 => 2_700_000, 36 => 1_931_000, 48 => 1_599_000, 60 => 1_385_000], fn ($tenor) => $modeA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 0, 24 => 0, 36 => 0, 48 => 0, 60 => 0], fn ($tenor) => $modeB->forTenor($tenor)->ltvAmount);
    assertMoneySeries([12 => 0, 24 => 0, 36 => 0, 48 => 0, 60 => 0], fn ($tenor) => $modeB->forTenor($tenor)->instalment);
});

test('S6 zero normalization and null rate rules are enforced', function () {
    $engine = new SimulationEngine;
    $dtn = $engine->simulate(SimulationTestFactory::dtnInput(vehicleYear: 2013, phpmPrice: 0), SimulationTestFactory::dtnConfig(), 2026);
    $ucf = $engine->simulate(SimulationTestFactory::ucfInput(vehicleYear: 2013, phpmPrice: 0), SimulationTestFactory::ucfConfig(), 2026);

    foreach ([12, 24, 36, 48, 60] as $tenor) {
        expect($dtn->forTenor($tenor)->outputAmount)->toBe(0)
            ->and($dtn->forTenor($tenor)->instalment)->toBe(0)
            ->and($ucf->forTenor($tenor)->outputAmount)->toBe(0)
            ->and($ucf->forTenor($tenor)->instalment)->toBe(0)
            ->and($ucf->forTenor($tenor)->refund->total)->toBe(0);
    }

    foreach ([12 => 2, 24 => 1, 36 => 0, 48 => -1, 60 => -2] as $tenor => $score) {
        expect($dtn->forTenor($tenor)->eligibilityScore)->toBe($score);
    }

    $product = new ProductConfig(
        'Reguler Commercial Sales Dealer',
        [12 => 0.2088, 24 => 0.2050, 36 => 0.2065, 48 => 0.2039, 60 => null],
        5_350_000,
    );
    $available = $engine->simulate(SimulationTestFactory::dtnInput(), SimulationTestFactory::dtnConfig($product), 2026);
    expect($available->forTenor(60)->instalment)->toBe(0)
        ->and($available->forTenor(60)->insurance->total)->toBe(0)
        ->and($available->forTenor(60)->refund->total)->toBe(0);
});

test('S7 debtor age ACP variants match the reference vectors', function () {
    $engine = new SimulationEngine;
    $young = $engine->simulate(SimulationTestFactory::dtnInput(ageGroup: '18-35 tahun'), SimulationTestFactory::dtnConfig(), 2026);
    $older = $engine->simulate(SimulationTestFactory::dtnInput(ageGroup: '51-60 tahun'), SimulationTestFactory::dtnConfig(), 2026);

    assertMoneySeries([12 => 6_518_200, 24 => 7_876_700, 36 => 9_206_600, 48 => 10_722_400, 60 => 12_138_100], fn ($tenor) => $young->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 6_793_200, 24 => 8_426_700, 36 => 10_048_100, 48 => 11_954_400, 60 => 13_722_100], fn ($tenor) => $older->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 92_131_800, 24 => 90_773_300, 36 => 89_443_400, 48 => 87_927_600, 60 => 86_511_900], fn ($tenor) => $young->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 91_856_800, 24 => 90_223_300, 36 => 88_601_900, 48 => 86_695_600, 60 => 84_927_900], fn ($tenor) => $older->forTenor($tenor)->outputAmount);
});
