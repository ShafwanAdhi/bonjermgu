<?php

namespace App\Application\Simulation;

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;

/**
 * Input for {@see OfficerSimulator}.
 *
 * Carries the Referral category rather than a Referral or a Product. An Account
 * Officer has no category of its own, so it names the one the application came
 * through and the Product is resolved from that — the same mapping a Referral
 * would hit.
 *
 * Everything below `desiredAmount` is an override the Officer sets for this one
 * simulation. They exist because the figures an Officer works from are the ones
 * on the deal in front of them, not the defaults. They never write back to the
 * Product or to the settings table.
 *
 * No debtor field of any kind: this screen has no download, so it has no reason
 * to hold identity (CLAUDE.md rule 9).
 */
final readonly class OfficerSimulationRequest
{
    /** @param  array<string, bool>  $extensions */
    public function __construct(
        public int $referralCategoryId,
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
        public ?string $rateVariant = null,
        public float $upRate = 0,
        public float $upAdmin = 0,
        public float $upProvision = 0,
        public ?float $acpUpping = null,
        public array $extensions = [],
        public float $tjhAmount = 0,
        public float $driverCoverageAmount = 0,
        public float $passengerCoverageAmount = 0,
        public int $passengerCount = 0,
        public bool $engineWarrantyEnabled = true,
        /** How many instalments are withheld, 0 to 10 — not a rupiah figure. */
        public int $depositInstalmentCount = 0,
        public float $bbnkbAmount = 0,
        public float $pkbAmount = 0,
        public float $invoiceAmount = 0,
    ) {}
}
