<?php

namespace App\Domain\Simulation;

use App\Domain\Simulation\Fee\FeeCalculator;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\Insurance\InsuranceCalculator;
use App\Domain\Simulation\Output\SimulationResult;
use App\Domain\Simulation\Output\TenorResult;
use App\Domain\Simulation\Rate\FlatRateConverter;
use App\Domain\Simulation\Refund\RefundBreakdown;
use InvalidArgumentException;

final class DanaTunaiCalculator
{
    public function __construct(
        private readonly FlatRateConverter $flatRateConverter = new FlatRateConverter,
        private readonly InsuranceCalculator $insuranceCalculator = new InsuranceCalculator,
        private readonly FeeCalculator $feeCalculator = new FeeCalculator,
        private readonly VehicleEligibility $vehicleEligibility = new VehicleEligibility,
    ) {}

    public function calculate(SimulationInput $input, SimulationConfig $config, int $currentYear): SimulationResult
    {
        if ($input->financingType !== FinancingType::DTN) {
            throw new InvalidArgumentException('DanaTunaiCalculator hanya menerima pembiayaan DTN.');
        }

        $results = [];

        foreach ([12, 24, 36, 48, 60] as $tenorMonths) {
            $results[$tenorMonths] = $this->calculateTenor($input, $config, $currentYear, $tenorMonths);
        }

        return new SimulationResult(FinancingType::DTN, $input->mode, $results);
    }

    private function calculateTenor(
        SimulationInput $input,
        SimulationConfig $config,
        int $currentYear,
        int $tenorMonths,
    ): TenorResult {
        $score = $this->vehicleEligibility->score(
            $config->maxVehicleAge,
            $tenorMonths,
            $currentYear,
            $input->vehicleYear,
        );
        $eligible = $score >= 1;
        $effectiveRate = $config->product->effectiveRateFor($tenorMonths);
        $rateAvailable = $effectiveRate !== null;

        if (! $eligible || ! $rateAvailable || $input->phpmPrice <= 0) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable);
        }

        $otrPrice = Rounding::down($input->phpmPrice, 100);
        $minimumNetDpRate = $this->minimumNetDpRate($input, $config);
        $modeANetDpAmount = $otrPrice * $minimumNetDpRate;
        $modeALtvAmount = $otrPrice - $modeANetDpAmount;
        $flatRate = $this->flatRateConverter->convert($effectiveRate, $tenorMonths, $input->instalmentType);
        $flatRateFinal = $flatRate + $config->product->upRate;
        $sellingInterestRate = $flatRateFinal * ($tenorMonths / 12);
        $insurance = $this->insuranceCalculator->calculate($input, $config, $otrPrice, $tenorMonths, $currentYear);
        $fees = $this->feeCalculator->calculate(FinancingType::DTN, $config, $modeALtvAmount, $otrPrice);

        if ($input->mode === SimulationMode::A) {
            $netDpRate = $minimumNetDpRate;
            $netDpAmount = $modeANetDpAmount;
            $ltvAmount = $modeALtvAmount;
            $ltvRate = 1 - $netDpRate;
            $totalDownPayment = $netDpAmount + $insurance->total + $fees->total();
            $firstPayment = $totalDownPayment;
            $grossDisbursement = $otrPrice - $firstPayment;
            $netDisbursement = $grossDisbursement - $config->disbursementDeductions();
            $outputAmount = $netDisbursement;
            $desiredAmount = 0;
        } else {
            $totalDownPayment = $insurance->total + $fees->total();
            $firstPayment = $totalDownPayment;
            $netDpAmount = $input->phpmPrice - ($input->desiredAmount + $totalDownPayment);
            $netDpRate = $netDpAmount / $input->phpmPrice;
            $meetsMinimum = $netDpRate >= $minimumNetDpRate;
            $ltvRate = $meetsMinimum ? 1 - $netDpRate : 0.0;
            // Algebraically identical to PHPM × LTV rate, while preserving an
            // exact rupiah value instead of exposing a binary-float artefact.
            $ltvAmount = $meetsMinimum ? $input->phpmPrice - $netDpAmount : 0;
            $grossDisbursement = 0;
            $netDisbursement = 0;
            $outputAmount = $input->desiredAmount;
            $desiredAmount = $input->desiredAmount;
        }

        $interestAmount = $ltvAmount * $sellingInterestRate;
        $totalAccountsReceivable = $ltvAmount + $interestAmount;
        $instalment = $ltvAmount > 0
            ? Rounding::up($totalAccountsReceivable / $tenorMonths, 1000)
            : 0;

        return new TenorResult(
            tenorMonths: $tenorMonths,
            eligible: true,
            rateAvailable: true,
            eligibilityScore: $score,
            phpmPrice: $input->phpmPrice,
            otrPrice: $otrPrice,
            deviationAmount: 0,
            deviationRate: 0,
            minimumNetDpRate: $minimumNetDpRate,
            effectiveRate: $effectiveRate,
            flatRate: $flatRate,
            flatRateFinal: $flatRateFinal,
            sellingInterestRate: $sellingInterestRate,
            netDpRate: $netDpRate,
            netDpAmount: $netDpAmount,
            ltvRate: $ltvRate,
            ltvAmount: $ltvAmount,
            interestAmount: $interestAmount,
            totalAccountsReceivable: $totalAccountsReceivable,
            instalment: $instalment,
            insurance: $insurance,
            fees: $fees,
            firstInstalment: 0,
            firstPayment: $firstPayment,
            totalDownPayment: $totalDownPayment,
            desiredAmount: $desiredAmount,
            grossDisbursement: $grossDisbursement,
            netDisbursement: $netDisbursement,
            refund: RefundBreakdown::zero(),
            allInDisbursement: $netDisbursement,
            outputAmount: $outputAmount,
        );
    }

    private function minimumNetDpRate(SimulationInput $input, SimulationConfig $config): float
    {
        $highRisk = $input->stnkOwnership === StnkOwnership::OTHER
            || $input->vehicleUsage === VehicleUsage::COMMERCIAL
            || $input->vehicleOrigin === VehicleOrigin::NON_JAPAN;

        return $highRisk
            ? $config->downPayment->dtnHighRiskRate
            : $config->downPayment->dtnStandardRate;
    }
}
