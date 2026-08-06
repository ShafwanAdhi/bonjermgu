<?php

namespace App\Domain\Simulation\Rate;

use App\Domain\Simulation\InstalmentType;
use InvalidArgumentException;

final class FlatRateConverter
{
    public function convert(float $effectiveRate, int $tenorMonths, InstalmentType $instalmentType): float
    {
        if ($effectiveRate < 0) {
            throw new InvalidArgumentException('Effective rate tidak boleh negatif.');
        }

        if ($tenorMonths <= 0 || $tenorMonths % 12 !== 0) {
            throw new InvalidArgumentException('Tenor harus berupa kelipatan 12 bulan.');
        }

        if ($effectiveRate === 0.0) {
            return 0.0;
        }

        $monthlyRate = $effectiveRate / 12;
        $discountFactor = pow(1 / (1 + $monthlyRate), $tenorMonths);
        $paymentFactor = $monthlyRate / (1 - $discountFactor);

        if ($instalmentType === InstalmentType::ADDM) {
            $paymentFactor /= 1 + $monthlyRate;
        }

        return (($paymentFactor * $tenorMonths) - 1) / ($tenorMonths / 12);
    }
}
