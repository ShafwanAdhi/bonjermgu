<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;

final readonly class DatabaseSimulationInput
{
    public function __construct(
        public int $vehicleModelId,
        public int $vehicleYear,
        public FinancingType $financingType,
        public SimulationMode $mode,
        public DebtorType $debtorType,
        public ?string $ageGroup,
        public StnkOwnership $stnkOwnership,
        public InstalmentType $instalmentType,
        public CoverageType $coverageType,
        public float $marketPrice = 0,
        public float $desiredAmount = 0,
    ) {}
}
