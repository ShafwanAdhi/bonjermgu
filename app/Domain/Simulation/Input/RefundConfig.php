<?php

namespace App\Domain\Simulation\Input;

final readonly class RefundConfig
{
    public function __construct(
        public float $insuranceBaseRate,
        public float $insuranceRefundRate,
        public float $interestRefundRate,
        public float $provisionRefundRate,
        public float $adminRefundRate,
    ) {}
}
