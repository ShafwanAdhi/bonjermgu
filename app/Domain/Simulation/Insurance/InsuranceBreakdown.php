<?php

namespace App\Domain\Simulation\Insurance;

final readonly class InsuranceBreakdown
{
    public function __construct(
        public float $casco,
        public float $loading,
        public float $extensions,
        public float $tjh,
        public float $driver,
        public float $passenger,
        public float $acp,
        public float $engineWarranty,
        public int $total,
    ) {}

    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, 0);
    }

    public function refundablePremium(): float
    {
        return $this->casco + $this->extensions + $this->driver + $this->passenger;
    }
}
