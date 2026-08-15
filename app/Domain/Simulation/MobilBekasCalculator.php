<?php

namespace App\Domain\Simulation;

use App\Domain\Simulation\Fee\FeeCalculator;
use App\Domain\Simulation\Input\SimulationConfig;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\Insurance\InsuranceCalculator;
use App\Domain\Simulation\Output\SimulationResult;
use App\Domain\Simulation\Output\TenorResult;
use App\Domain\Simulation\Output\ZeroReason;
use App\Domain\Simulation\Rate\FlatRateConverter;
use App\Domain\Simulation\Refund\RefundBreakdown;
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

        if ($input->vehicleUsage !== VehicleUsage::PASSENGER) {
            throw new InvalidArgumentException('Pembiayaan Mobil Bekas hanya tersedia untuk unit Passenger.');
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

        // Same combined guard as before, split so each exit carries its own
        // reason. The condition it replaces was:
        // ! $eligible || ! $rateAvailable || $input->phpmPrice <= 0 || $input->marketPrice <= 0
        if (! $eligible) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::NotEligible);
        }

        if (! $rateAvailable) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::RateUnavailable);
        }

        if ($input->phpmPrice <= 0 || $input->marketPrice <= 0) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::PriceUnavailable);
        }

        $otrPrice = $input->marketPrice;
        $deviationAmount = max($otrPrice - $input->phpmPrice, 0);
        // Measured against the master price, so the surcharge tracks how far
        // the asking price runs ahead of PHPM rather than diluting into it.
        $deviationRate = $deviationAmount / $input->phpmPrice;
        $minimumNetDpRate = $this->minimumNetDpRate($input, $config) + $deviationRate;
        $modeANetDpAmount = $otrPrice * $minimumNetDpRate;
        $modeALtvAmount = $otrPrice - $modeANetDpAmount;

        // A deviation large enough to push Net DP past the whole price leaves
        // nothing to finance, so the tenor normalises to zero rather than
        // reporting a negative instalment and disbursement.
        if ($modeALtvAmount <= 0) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::DownPaymentExceedsPrice);
        }

        $flatRate = $this->flatRateConverter->convert($effectiveRate, $tenorMonths, $input->instalmentType);
        $flatRateFinal = $flatRate + $config->product->upRate;
        $sellingInterestRate = $flatRateFinal * ($tenorMonths / 12);
        $modeATotalAr = $modeALtvAmount * (1 + $sellingInterestRate);
        $insurance = $this->insuranceCalculator->calculate($input, $config, $otrPrice, $tenorMonths, $currentYear, $modeATotalAr);
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
            $depositAmount = $config->depositFor($instalment);
            $netDisbursement = $grossDisbursement - $config->disbursementDeductions() - $depositAmount;
            $refund = $this->refundCalculator->calculate(
                FinancingType::UCF,
                $insurance,
                $config->product,
                $config->refund,
                $ltvAmount,
                $tenorMonths,
                // Rate bottom saja — Up Rate tidak mendiskonto refundnya sendiri.
                $flatRate * ($tenorMonths / 12),
                $fees->provision,
            );
            $desiredAmount = 0;
            // Refund is paid out separately; it never tops up the disbursement.
            $outputAmount = $netDisbursement;
            $zeroReason = null;
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
            // Mode B quotes an instalment against a chosen Total DP; there is
            // no disbursement for a refund to be added back to.
            $refund = RefundBreakdown::zero();
            $totalDownPayment = $input->desiredAmount;
            $desiredAmount = $input->desiredAmount;
            $grossDisbursement = 0;
            $depositAmount = 0;
            $netDisbursement = 0;
            $outputAmount = $input->desiredAmount;
            $zeroReason = $meetsMinimum ? null : ZeroReason::DownPaymentBelowMinimum;
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
            depositInstalmentAmount: $depositAmount,
            netDisbursement: $netDisbursement,
            refund: $refund,
            outputAmount: $outputAmount,
            zeroReason: $zeroReason,
        );
    }

    private function minimumNetDpRate(SimulationInput $input, SimulationConfig $config): float
    {
        if ($input->debtorType === DebtorType::ENTREPRENEUR) {
            return $config->downPayment->ucfEntrepreneurRate;
        }

        return $input->vehicleOrigin === VehicleOrigin::NON_JAPAN
            ? $config->downPayment->ucfNonJapanStandardRate
            : $config->downPayment->ucfStandardRate;
    }
}
