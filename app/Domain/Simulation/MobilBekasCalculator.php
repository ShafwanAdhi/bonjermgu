<?php

namespace App\Domain\Simulation;

use App\Domain\Simulation\Fee\FeeCalculator;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\Insurance\InsuranceCalculator;
use App\Domain\Simulation\Output\SimulationResult;
use App\Domain\Simulation\Output\TenorResult;
use App\Domain\Simulation\Rate\FlatRateConverter;
use App\Domain\Simulation\Refund\RefundCalculator;
use InvalidArgumentException;

final class MobilBekasCalculator
{
    public function __construct(
        private readonly FlatRateConverter $flatRateConverter = new FlatRateConverter,
        private readonly InsuranceCalculator $insuranceCalculator = new InsuranceCalculator,
        private readonly FeeCalculator $feeCalculator = new FeeCalculator,
        private readonly RefundCalculator $refundCalculator = new RefundCalculator,
        private readonly VehicleEligibility $vehicleEligibility = new VehicleEligibility,
    ) {}

    public function calculate(SimulationInput $input, SimulationConfig $config, int $currentYear): SimulationResult
    {
        if ($input->financingType !== FinancingType::UCF) {
            throw new InvalidArgumentException('MobilBekasCalculator hanya menerima pembiayaan UCF.');
        }

        $results = [];

        foreach ([12, 24, 36, 48, 60] as $tenorMonths) {
            $results[$tenorMonths] = $this->calculateTenor($input, $config, $currentYear, $tenorMonths);
        }

        return new SimulationResult(FinancingType::UCF, $input->mode, $results);
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

        if (! $eligible || ! $rateAvailable || $input->phpmPrice <= 0 || $input->marketPrice <= 0) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable);
        }

        $otrPrice = $input->marketPrice;
        $deviationAmount = max($otrPrice - $input->phpmPrice, 0);
        $deviationRate = $deviationAmount / $otrPrice;
        $minimumNetDpRate = $this->minimumNetDpRate($input, $config) + $deviationRate;
        $modeANetDpAmount = $otrPrice * $minimumNetDpRate;
        $modeALtvAmount = $otrPrice - $modeANetDpAmount;
        $flatRate = $this->flatRateConverter->convert($effectiveRate, $tenorMonths, $input->instalmentType);
        $flatRateFinal = $flatRate + $config->product->upRate;
        $sellingInterestRate = $flatRateFinal * ($tenorMonths / 12);
        $insurance = $this->insuranceCalculator->calculate($input, $config, $otrPrice, $tenorMonths, $currentYear);
        $fees = $this->feeCalculator->calculate(FinancingType::UCF, $config, $modeALtvAmount, $otrPrice);

        if ($input->mode === SimulationMode::A) {
            $netDpRate = $minimumNetDpRate;
            $netDpAmount = $modeANetDpAmount;
            $ltvRate = 1 - $netDpRate;
            $ltvAmount = $modeALtvAmount;
            $interestAmount = $ltvAmount * $sellingInterestRate;
            $totalAccountsReceivable = $ltvAmount + $interestAmount;
            $instalment = Rounding::up($totalAccountsReceivable / $tenorMonths, 1000);
            $firstInstalment = $input->instalmentType === InstalmentType::ADDM ? $instalment : 0;
            $firstPayment = $netDpAmount + $insurance->total + $fees->total() + $firstInstalment;
            $totalDownPayment = $netDpAmount;
            $grossDisbursement = $otrPrice - $firstPayment;
            $netDisbursement = $grossDisbursement - $config->disbursementDeductions();
            $refund = $this->refundCalculator->calculate(
                $insurance,
                $config->product,
                $config->refund,
                $ltvAmount,
                $tenorMonths,
                $sellingInterestRate,
                $fees->provision,
            );
            $allInDisbursement = $netDisbursement + $refund->total;
            $desiredAmount = 0;
            $outputAmount = $allInDisbursement;
        } else {
            $firstPayment = $insurance->total + $fees->total();
            $basis = $otrPrice - ($input->desiredAmount - $firstPayment);
            $instalmentBasis = $basis * (1 + $sellingInterestRate);
            $divisor = $input->instalmentType === InstalmentType::ADDM
                ? $tenorMonths - (1 + $sellingInterestRate)
                : $tenorMonths;
            $calculatedInstalment = Rounding::up($instalmentBasis / $divisor, 1000);
            $firstInstalment = $input->instalmentType === InstalmentType::ADDM
                ? $calculatedInstalment
                : 0;
            $netDpAmount = $input->desiredAmount - ($firstPayment + $firstInstalment);
            $netDpRate = $netDpAmount / $otrPrice;
            $meetsMinimum = $netDpRate >= $minimumNetDpRate;
            $ltvRate = $meetsMinimum ? 1 - $netDpRate : 0.0;
            $ltvAmount = $meetsMinimum ? $otrPrice - $netDpAmount : 0;
            $instalment = $meetsMinimum ? $calculatedInstalment : 0;
            $firstInstalment = $meetsMinimum ? $firstInstalment : 0;
            $interestAmount = $ltvAmount * $sellingInterestRate;
            $totalAccountsReceivable = $ltvAmount + $interestAmount;
            $refund = $this->refundCalculator->calculate(
                $insurance,
                $config->product,
                $config->refund,
                $ltvAmount,
                $tenorMonths,
                $sellingInterestRate,
                $fees->provision,
            );
            $totalDownPayment = $input->desiredAmount;
            $desiredAmount = $input->desiredAmount;
            $grossDisbursement = 0;
            $netDisbursement = 0;
            $allInDisbursement = 0;
            $outputAmount = $input->desiredAmount;
        }

        return new TenorResult(
            tenorMonths: $tenorMonths,
            eligible: true,
            rateAvailable: true,
            eligibilityScore: $score,
            phpmPrice: $input->phpmPrice,
            otrPrice: $otrPrice,
            deviationAmount: $deviationAmount,
            deviationRate: $deviationRate,
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
            firstInstalment: $firstInstalment,
            firstPayment: $firstPayment,
            totalDownPayment: $totalDownPayment,
            desiredAmount: $desiredAmount,
            grossDisbursement: $grossDisbursement,
            netDisbursement: $netDisbursement,
            refund: $refund,
            allInDisbursement: $allInDisbursement,
            outputAmount: $outputAmount,
        );
    }

    private function minimumNetDpRate(SimulationInput $input, SimulationConfig $config): float
    {
        return $input->debtorType === DebtorType::ENTREPRENEUR
            ? $config->downPayment->ucfEntrepreneurRate
            : $config->downPayment->ucfStandardRate;
    }
}
