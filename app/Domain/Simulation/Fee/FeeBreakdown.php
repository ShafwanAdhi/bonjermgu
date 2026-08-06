<?php

namespace App\Domain\Simulation\Fee;

final readonly class FeeBreakdown
{
    public function __construct(
        public int|float $provision,
        public float $administration,
        public float $fiducia,
    ) {}

    public function total(): float
    {
        return $this->provision + $this->administration + $this->fiducia;
    }

    public static function zero(): self
    {
        return new self(0, 0, 0);
    }
}
