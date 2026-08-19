<?php

namespace App\Domain\Simulation\Fee;

use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Rounding;

final class FeeCalculator
{
    public function calculate(
        FinancingType $financingType,
        SimulationConfig $config,
        float $ltvAmount,
        float $otrPrice,
        bool $belivEnabled = false,
    ): FeeBreakdown {
        $provision = ($config->product->provisionRate + $config->product->upProvision) * $ltvAmount;

        if ($financingType === FinancingType::UCF) {
            $provision = Rounding::up($provision, 100);
        }

        return new FeeBreakdown(
            $provision,
            $config->product->adminMax + $config->product->upAdmin,
            $config->fees->fiduciaFor($otrPrice),
            $belivEnabled ? $config->currentBelivFeeAmount() : 0,
        );
    }
}
