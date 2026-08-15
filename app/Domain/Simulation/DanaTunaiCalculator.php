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

final class DanaTunaiCalculator
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

        // The Account Officer appraises the unit; Referral is always priced
        // straight off the PHPM master.
        $officer = $config->profile === SimulationProfile::OFFICER;

        // Same combined guard as before, split so each exit carries its own
        // reason. The condition it replaces was:
        // ! $eligible || ! $rateAvailable || $input->phpmPrice <= 0 || ($officer && $input->marketPrice <= 0)
        if (! $eligible) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::NotEligible);
        }

        if (! $rateAvailable) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::RateUnavailable);
        }

        if ($input->phpmPrice <= 0 || ($officer && $input->marketPrice <= 0)) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::PriceUnavailable);
        }

        $otrPrice = $officer
            ? $input->marketPrice
            : Rounding::down($input->phpmPrice, 100);
        $deviationAmount = $officer ? max($otrPrice - $input->phpmPrice, 0) : 0;
        $deviationRate = $officer ? $deviationAmount / $input->phpmPrice : 0.0;
        $minimumNetDpRate = $this->minimumNetDpRate($input, $config) + $deviationRate;
        $modeANetDpAmount = $officer
            ? Rounding::up($otrPrice * $minimumNetDpRate, 1000)
            : $otrPrice * $minimumNetDpRate;
        $modeALtvAmount = $otrPrice - $modeANetDpAmount;

        // An appraised value far above PHPM can drive Net DP past the whole
        // price. Nothing is financed, so the tenor normalises to zero instead
        // of reporting a negative disbursement.
        if ($modeALtvAmount <= 0) {
            return TenorResult::zero($tenorMonths, $score, $eligible, $rateAvailable, ZeroReason::DownPaymentExceedsPrice);
        }

        $flatRate = $this->flatRateConverter->convert($effectiveRate, $tenorMonths, $input->instalmentType);
        $flatRateFinal = $flatRate + $config->product->upRate;
        $sellingInterestRate = $flatRateFinal * ($tenorMonths / 12);
        $modeATotalAr = $modeALtvAmount * (1 + $sellingInterestRate);
        $insurance = $this->insuranceCalculator->calculate($input, $config, $otrPrice, $tenorMonths, $currentYear, $modeATotalAr);
        $fees = $this->feeCalculator->calculate(FinancingType::DTN, $config, $modeALtvAmount, $otrPrice);
        $modeAInstalment = Rounding::up($modeATotalAr / $tenorMonths, 1000);

        if ($input->mode === SimulationMode::A) {
            $netDpRate = $minimumNetDpRate;
            $netDpAmount = $modeANetDpAmount;
            $ltvAmount = $modeALtvAmount;
            $ltvRate = 1 - $netDpRate;
            $totalDownPayment = $netDpAmount + $insurance->total + $fees->total();
            $firstPayment = $totalDownPayment;
            $grossDisbursement = $otrPrice - $firstPayment;
            $depositAmount = $config->depositFor($modeAInstalment);
            $netDisbursement = $grossDisbursement - $config->disbursementDeductions() - $depositAmount;
            $refund = $this->refundCalculator->calculate(
                FinancingType::DTN,
                $insurance,
                $config->product,
                $config->refund,
                $modeALtvAmount,
                $tenorMonths,
                // Rate bottom saja — Up Rate tidak mendiskonto refundnya sendiri.
                $flatRate * ($tenorMonths / 12),
                $fees->provision,
            );
            // Refund is paid out separately; it never tops up the disbursement.
            $outputAmount = $netDisbursement;
            $desiredAmount = 0;
            $zeroReason = null;
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
            // Mode B quotes an instalment against a chosen amount; there is no
            // disbursement, and no refund on either product.
            $refund = RefundBreakdown::zero();
            $grossDisbursement = 0;
            $depositAmount = 0;
            $netDisbursement = 0;
            $outputAmount = $input->desiredAmount;
            $desiredAmount = $input->desiredAmount;
            $zeroReason = $meetsMinimum ? null : ZeroReason::DownPaymentBelowMinimum;
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
            firstInstalment: 0,
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
        $highRisk = $input->stnkOwnership === StnkOwnership::OTHER
            || $input->vehicleUsage === VehicleUsage::COMMERCIAL
            || $input->vehicleOrigin === VehicleOrigin::NON_JAPAN;

        return $highRisk
            ? $config->downPayment->dtnHighRiskRate
            : $config->downPayment->dtnStandardRate;
    }
}
