<?php

namespace App\Domain\Simulation\Input;

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;

final readonly class SimulationInput
{
    /**
     * @param  array<string, bool>  $extensions
     */
    public function __construct(
        public FinancingType $financingType,
        public SimulationMode $mode,
        public DebtorType $debtorType,
        public ?string $ageGroup,
        public VehicleUsage $vehicleUsage,
        public VehicleOrigin $vehicleOrigin,
        public StnkOwnership $stnkOwnership,
        public int $vehicleYear,
        public float $phpmPrice,
        public InstalmentType $instalmentType,
        public CoverageType $coverageType,
        public float $marketPrice = 0,
        public float $desiredAmount = 0,
        public array $extensions = [],
        public float $tjhAmount = 0,
        public float $driverCoverageAmount = 0,
        public float $passengerCoverageAmount = 0,
        public int $passengerCount = 0,
        public bool $engineWarrantyEnabled = true,
        public bool $belivEnabled = false,
    ) {}

    public function extensionEnabled(string $code): bool
    {
        return $this->extensions[$code] ?? false;
    }
}
