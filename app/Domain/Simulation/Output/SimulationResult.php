<?php

namespace App\Domain\Simulation\Output;

use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\SimulationMode;
use InvalidArgumentException;

final readonly class SimulationResult
{
    /**
     * @param  array<int, TenorResult>  $tenors
     */
    public function __construct(
        public FinancingType $financingType,
        public SimulationMode $mode,
        public array $tenors,
    ) {}

    public function forTenor(int $tenorMonths): TenorResult
    {
        return $this->tenors[$tenorMonths]
            ?? throw new InvalidArgumentException("Hasil tenor {$tenorMonths} tidak tersedia.");
    }
}
