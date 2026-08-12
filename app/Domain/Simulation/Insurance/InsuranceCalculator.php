<?php

namespace App\Domain\Simulation\Insurance;

use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\InsuranceCoverage;
use App\Domain\Simulation\Rounding;

final class InsuranceCalculator
{
    public function calculate(
        SimulationInput $input,
        SimulationConfig $config,
        float $otrPrice,
        int $tenorMonths,
        int $currentYear,
        float $totalAccountsReceivable = 0,
    ): InsuranceBreakdown {
        $tenorYears = intdiv($tenorMonths, 12);
        $casco = 0.0;
        $loading = 0.0;
        $extensions = 0.0;
        $tjh = 0.0;
        $driver = 0.0;
        $passenger = 0.0;

        for ($year = 1; $year <= $tenorYears; $year++) {
            $coverage = $input->coverageType->coverageForYear($year);
            $sumInsured = $otrPrice * $config->insurance->sumInsuredPercentage($year);
            $yearlyCasco = $config->insurance->cascoRateFor(
                $input->vehicleUsage,
                $coverage,
                $sumInsured,
            ) * $sumInsured;

            $casco += $yearlyCasco;

            if ($coverage === InsuranceCoverage::COMPREHENSIVE) {
                $vehicleAge = ($currentYear - $input->vehicleYear) + ($year - 1);
                $loading += $yearlyCasco * $config->insurance->loadingRate($vehicleAge);

                foreach (['flood', 'earthquake', 'riot', 'terrorism'] as $code) {
                    if ($input->extensionEnabled($code)) {
                        $extensions += $config->insurance->extensionRate($code) * $sumInsured;
                    }
                }

                $driver += $config->insurance->extensionRate('driver') * $input->driverCoverageAmount;
                $passenger += $config->insurance->extensionRate('passenger')
                    * $input->passengerCount
                    * $input->passengerCoverageAmount;

                $tjh += $this->tieredPremium($input->tjhAmount, $config->insurance->tjhTiers);
            }
        }

        $acpEnabled = match ($input->financingType) {
            FinancingType::DTN => $config->insurance->dtnAcpEnabled,
            FinancingType::UCF => $config->insurance->ucfAcpEnabled,
        };

        // Dana Tunai is underwritten on the master price; Mobil Bekas on the
        // price the unit actually changes hands at.
        $acpBasis = $input->financingType === FinancingType::DTN
            ? $input->phpmPrice
            : $otrPrice;
        $withinLoanCeiling = $config->insurance->acpMaxLoanAmount <= 0
            || $totalAccountsReceivable <= $config->insurance->acpMaxLoanAmount;

        $acp = 0.0;
        if ($acpEnabled && $withinLoanCeiling && $input->debtorType !== DebtorType::LEGAL_ENTITY) {
            $acpRate = $config->insurance->acpBaseRate($tenorYears)
                * (1 + $config->insurance->acpUpping($input->ageGroup));
            $acp = $acpRate * $acpBasis;
        }

        $engineWarranty = $input->engineWarrantyEnabled ? $config->insurance->engineWarrantyFee : 0.0;
        $rawTotal = $casco + $loading + $extensions + $tjh + $driver + $passenger + $acp + $engineWarranty;
        $total = $input->financingType === FinancingType::DTN
            ? Rounding::down($rawTotal, 100)
            : Rounding::up($rawTotal, 100);

        return new InsuranceBreakdown(
            $casco,
            $loading,
            $extensions,
            $tjh,
            $driver,
            $passenger,
            $acp,
            $engineWarranty,
            $total,
        );
    }

    /**
     * @param  array<int, array{limit: int|float|null, rate: float}>  $tiers
     */
    private function tieredPremium(float $insuredAmount, array $tiers): float
    {
        $remaining = max($insuredAmount, 0);
        $premium = 0.0;

        foreach ($tiers as $tier) {
            if ($remaining <= 0) {
                break;
            }

            $portion = $tier['limit'] === null
                ? $remaining
                : min($remaining, (float) $tier['limit']);
            $premium += $portion * $tier['rate'];
            $remaining -= $portion;
        }

        return $premium;
    }
}
