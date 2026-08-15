<?php

namespace App\Domain\Simulation\Output;

use App\Domain\Simulation\Fee\FeeBreakdown;
use App\Domain\Simulation\Insurance\InsuranceBreakdown;
use App\Domain\Simulation\Refund\RefundBreakdown;

final readonly class TenorResult
{
    public function __construct(
        public int $tenorMonths,
        public bool $eligible,
        public bool $rateAvailable,
        public int $eligibilityScore,
        public float $phpmPrice,
        public float $otrPrice,
        public float $deviationAmount,
        public float $deviationRate,
        public float $minimumNetDpRate,
        public float $effectiveRate,
        public float $flatRate,
        public float $flatRateFinal,
        public float $sellingInterestRate,
        public float $netDpRate,
        public int|float $netDpAmount,
        public float $ltvRate,
        public int|float $ltvAmount,
        public int|float $interestAmount,
        public int|float $totalAccountsReceivable,
        public int $instalment,
        public InsuranceBreakdown $insurance,
        public FeeBreakdown $fees,
        public int|float $firstInstalment,
        public int|float $firstPayment,
        public int|float $totalDownPayment,
        public int|float $desiredAmount,
        public int|float $grossDisbursement,
        /** Rupiah withheld as Deposit Angsuran: count x this tenor's instalment. */
        public int|float $depositInstalmentAmount,
        public int|float $netDisbursement,
        public RefundBreakdown $refund,
        public int|float $outputAmount,
    ) {}

    public static function zero(
        int $tenorMonths,
        int $eligibilityScore = 0,
        bool $eligible = false,
        bool $rateAvailable = false,
    ): self {
        return new self(
            tenorMonths: $tenorMonths,
            eligible: $eligible,
            rateAvailable: $rateAvailable,
            eligibilityScore: $eligibilityScore,
            phpmPrice: 0,
            otrPrice: 0,
            deviationAmount: 0,
            deviationRate: 0,
            minimumNetDpRate: 0,
            effectiveRate: 0,
            flatRate: 0,
            flatRateFinal: 0,
            sellingInterestRate: 0,
            netDpRate: 0,
            netDpAmount: 0,
            ltvRate: 0,
            ltvAmount: 0,
            interestAmount: 0,
            totalAccountsReceivable: 0,
            instalment: 0,
            insurance: InsuranceBreakdown::zero(),
            fees: FeeBreakdown::zero(),
            firstInstalment: 0,
            firstPayment: 0,
            totalDownPayment: 0,
            desiredAmount: 0,
            grossDisbursement: 0,
            depositInstalmentAmount: 0,
            netDisbursement: 0,
            refund: RefundBreakdown::zero(),
            outputAmount: 0,
        );
    }

    public function totalInsurance(): int
    {
        return $this->insurance->total;
    }

    public function totalRefund(): int
    {
        return $this->refund->total;
    }
}
