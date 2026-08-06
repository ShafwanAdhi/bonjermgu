<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Models\Product;

/**
 * Input for {@see ConfigurationSimulator}.
 *
 * Carries the Product itself rather than a Referral: the Product is the thing
 * being verified. Carries no debtor field of any kind.
 */
final readonly class ConfigurationSimulationRequest
{
    public function __construct(
        public Product $product,
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
