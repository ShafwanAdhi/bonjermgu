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

    assertMoneySeries([12 => 2_215_000, 24 => 2_858_500, 36 => 3_430_500, 48 => 3_931_000, 60 => 4_431_500], fn ($tenor) => $modeA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 18_415_000, 24 => 19_058_500, 36 => 19_630_500, 48 => 20_131_000, 60 => 20_631_500], fn ($tenor) => $modeA->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 91_585_000, 24 => 90_941_500, 36 => 90_369_500, 48 => 89_869_000, 60 => 89_368_500], fn ($tenor) => $modeA->forTenor($tenor)->netDisbursement);
    assertMoneySeries([12 => 436_000, 24 => 812_000, 36 => 1_139_000, 48 => 1_385_000, 60 => 1_613_000], fn ($tenor) => $modeA->forTenor($tenor)->refund->total);
    assertMoneySeries([12 => 92_021_000, 24 => 91_753_500, 36 => 91_508_500, 48 => 91_254_000, 60 => 90_981_500], fn ($tenor) => $modeA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 8_953_000, 24 => 4_827_000, 36 => 3_452_000, 48 => 2_859_000, 60 => 2_476_000], fn ($tenor) => $modeA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 7_415_000, 24 => 8_058_500, 36 => 8_630_500, 48 => 9_131_000, 60 => 9_631_500], fn ($tenor) => $modeB->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 52_585_000, 24 => 51_941_500, 36 => 51_369_500, 48 => 50_869_000, 60 => 50_368_500], fn ($tenor) => $modeB->forTenor($tenor)->netDpAmount);
    assertMoneySeries([12 => 57_415_000, 24 => 58_058_500, 36 => 58_630_500, 48 => 59_131_000, 60 => 59_631_500], fn ($tenor) => $modeB->forTenor($tenor)->ltvAmount);
    assertMoneySeries([12 => 5_192_000, 24 => 2_831_000, 36 => 2_044_000, 48 => 1_708_000, 60 => 1_491_000], fn ($tenor) => $modeB->forTenor($tenor)->instalment);
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
    assertMoneySeries([12 => 83_179_000, 24 => 86_992_500, 36 => 88_108_500, 48 => 88_448_000, 60 => 88_555_500], fn ($tenor) => $ucfA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 8_846_000, 24 => 4_769_000, 36 => 3_411_000, 48 => 2_822_000, 60 => 2_443_000], fn ($tenor) => $ucfA->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 5_634_000, 24 => 2_939_000, 36 => 2_092_000, 48 => 1_735_000, 60 => 1_509_000], fn ($tenor) => $ucfB->forTenor($tenor)->instalment);
    assertMoneySeries([12 => 2_215_000, 24 => 2_858_500, 36 => 3_430_500, 48 => 3_931_000, 60 => 4_431_500], fn ($tenor) => $ucfA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 440_000, 24 => 820_000, 36 => 1_150_000, 48 => 1_401_000, 60 => 1_630_000], fn ($tenor) => $ucfA->forTenor($tenor)->refund->total);
});

test('S5 deviation and minimum DP rejection match the reference vectors', function () {
    $engine = new SimulationEngine;
    $inputA = SimulationTestFactory::ucfInput(debtorType: DebtorType::ENTREPRENEUR, marketPrice: 145_000_000);
    $inputB = SimulationTestFactory::ucfInput(SimulationMode::B, debtorType: DebtorType::ENTREPRENEUR, marketPrice: 145_000_000);
    $modeA = $engine->simulate($inputA, SimulationTestFactory::ucfConfig(), 2026);
    $modeB = $engine->simulate($inputB, SimulationTestFactory::ucfConfig(), 2026);

    expect($modeA->forTenor(12)->deviationAmount)->toEqual(34_999_974)
        ->and($modeA->forTenor(12)->netDpAmount)->toEqual(78_499_974);
    assertMoneySeries([12 => 2_138_000, 24 => 2_712_200, 36 => 3_466_200, 48 => 4_126_000, 60 => 4_785_700], fn ($tenor) => $modeA->forTenor($tenor)->insurance->total);
    assertMoneySeries([12 => 85_837_974, 24 => 86_412_174, 36 => 87_166_174, 48 => 87_825_974, 60 => 88_485_674], fn ($tenor) => $modeA->forTenor($tenor)->firstPayment);
    assertMoneySeries([12 => 59_470_026, 24 => 59_162_826, 36 => 58_665_826, 48 => 58_204_026, 60 => 57_729_326], fn ($tenor) => $modeA->forTenor($tenor)->outputAmount);
    assertMoneySeries([12 => 6_014_000, 24 => 3_242_000, 36 => 2_319_000, 48 => 1_921_000, 60 => 1_663_000], fn ($tenor) => $modeA->forTenor($tenor)->instalment);
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
