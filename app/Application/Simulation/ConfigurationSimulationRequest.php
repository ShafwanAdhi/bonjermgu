<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\SimulationProfile;
use App\Domain\Simulation\StnkOwnership;
use App\Models\Product;

/**
 * Input for {@see ConfigurationSimulator}.
 *
 * Carries the Product itself rather than a Referral: the Product is the thing
 * being verified. Carries no debtor field of any kind.
 *
 * Profile and upping are part of the test: the same configuration produces
 * different figures on the Referral and Account Officer screens, so Admin has
 * to be able to reach both. Neither is persisted.
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
        public SimulationProfile $profile = SimulationProfile::REFERRAL,
        public float $upRate = 0,
        public float $upAdmin = 0,
        public float $upProvision = 0,
        public ?float $acpUpping = null,
    ) {}
}
