<?php

namespace App\Domain\Simulation\Refund;

use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\Input\RefundConfig;
use App\Domain\Simulation\Insurance\InsuranceBreakdown;
use App\Domain\Simulation\Rounding;

final class RefundCalculator
{
    public function calculate(
        InsuranceBreakdown $insurance,
        ProductConfig $product,
        RefundConfig $config,
        float $ltvAmount,
        int $tenorMonths,
        float $sellingInterestRate,
        float $provision,
    ): RefundBreakdown {
        $tenorYears = $tenorMonths / 12;
        $insuranceRefund = $insurance->refundablePremium()
            * $config->insuranceBaseRate
            * $config->insuranceRefundRate;
        $interestRefund = ($ltvAmount * ($product->upRate * $tenorYears))
            / (1 + $sellingInterestRate)
            * $config->interestRefundRate;
        $provisionRefund = $provision * $config->provisionRefundRate;
        $administrationRefund = $product->upAdmin * $config->adminRefundRate;
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
