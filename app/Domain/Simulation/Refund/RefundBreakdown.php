<?php

namespace App\Domain\Simulation\Refund;

final readonly class RefundBreakdown
{
    public function __construct(
        public float $insurance,
        public float $interest,
        public float $provision,
        public float $administration,
        public int $total,
    ) {}

    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0);
    }
}
