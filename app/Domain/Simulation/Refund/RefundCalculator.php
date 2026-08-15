<?php

namespace App\Domain\Simulation\Refund;

use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\Input\RefundConfig;
use App\Domain\Simulation\Insurance\InsuranceBreakdown;
use App\Domain\Simulation\Rounding;

final class RefundCalculator
{
    /**
     * Refund is a payout in its own right and never enters the disbursement.
     *
     * Dana Tunai earns the interest and provision components only. Refund
     * Asuransi and Refund Admin belong to Pembiayaan Mobil Bekas
     * (docs/credit-simulation.md section 10).
     */
    public function calculate(
        FinancingType $financingType,
        InsuranceBreakdown $insurance,
        ProductConfig $product,
        RefundConfig $config,
        float $ltvAmount,
        int $tenorMonths,
        /**
         * Bunga Jual dihitung dari rate bottom saja: Flat Rate x Tenor Tahun,
         * tanpa Up Rate. Upping adalah hal yang direfund, jadi ia tidak ikut
         * mendiskonto refundnya sendiri (ditetapkan klien 15 Agustus 2026).
         */
        float $baseSellingInterestRate,
        float $provision,
    ): RefundBreakdown {
        $tenorYears = $tenorMonths / 12;
        $ucf = $financingType === FinancingType::UCF;
        $insuranceRefund = $ucf
            ? $insurance->refundablePremium()
                * $config->insuranceBaseRate
                * $config->insuranceRefundRate
            : 0.0;
        $interestRefund = ($ltvAmount * ($product->upRate * $tenorYears))
            / (1 + $baseSellingInterestRate)
            * $config->interestRefundRate;
        $provisionRefund = $provision * $config->provisionRefundRate;
        $administrationRefund = $ucf ? $product->upAdmin * $config->adminRefundRate : 0.0;
        $total = Rounding::down(
            $insuranceRefund + $interestRefund + $provisionRefund + $administrationRefund,
            1000,
        );

        return new RefundBreakdown(
            $insuranceRefund,
            $interestRefund,
            $provisionRefund,
            $administrationRefund,
            $total,
        );
    }
}
