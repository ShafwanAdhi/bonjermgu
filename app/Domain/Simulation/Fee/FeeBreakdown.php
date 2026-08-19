<?php

namespace App\Domain\Simulation\Fee;

final readonly class FeeBreakdown
{
    public function __construct(
        public int|float $provision,
        public float $administration,
        public float $fiducia,
        public float $beliv = 0,
    ) {}

    public function total(): float
    {
        return $this->provision + $this->administration + $this->fiducia + $this->beliv;
    }

    public static function zero(): self
    {
        return new self(0, 0, 0);
    }
}
