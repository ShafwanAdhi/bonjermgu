<?php

namespace App\Domain\Simulation\Input;

use App\Domain\Simulation\SimulationProfile;

final readonly class SimulationConfig
{
    /**
     * @param  array<string, bool>  $defaultExtensions
     */
    public function __construct(
        public ProductConfig $product,
        public InsuranceConfig $insurance,
        public FeeConfig $fees,
        public DownPaymentConfig $downPayment,
        public RefundConfig $refund,
        public int $maxVehicleAge,
        public SimulationProfile $profile = SimulationProfile::REFERRAL,
        public float $bbnkbAmount = 0,
        public float $pkbAmount = 0,
        public float $invoiceAmount = 0,
        public float $depositInstalmentAmount = 0,
        public array $defaultExtensions = [],
        public float $defaultTjhAmount = 0,
        public float $defaultDriverCoverageAmount = 0,
        public float $defaultPassengerCoverageAmount = 0,
        public int $defaultPassengerCount = 0,
        public bool $defaultEngineWarrantyEnabled = true,
    ) {}

    /**
     * A copy with selected pieces replaced; null keeps what is already there.
     *
     * Both verification screens need this: the Account Officer sets upping and
     * deductions for one simulation, and Admin switches profile to check what
     * the other screen would produce. Neither writes back to the Product or to
     * the settings table.
     */
    public function with(
        ?ProductConfig $product = null,
        ?InsuranceConfig $insurance = null,
        ?SimulationProfile $profile = null,
        ?float $bbnkbAmount = null,
        ?float $pkbAmount = null,
        ?float $invoiceAmount = null,
        ?float $depositInstalmentAmount = null,
    ): self {
        return new self(
            product: $product ?? $this->product,
            insurance: $insurance ?? $this->insurance,
            fees: $this->fees,
            downPayment: $this->downPayment,
            refund: $this->refund,
            maxVehicleAge: $this->maxVehicleAge,
            profile: $profile ?? $this->profile,
            bbnkbAmount: $bbnkbAmount ?? $this->bbnkbAmount,
            pkbAmount: $pkbAmount ?? $this->pkbAmount,
            invoiceAmount: $invoiceAmount ?? $this->invoiceAmount,
            depositInstalmentAmount: $depositInstalmentAmount ?? $this->depositInstalmentAmount,
            defaultExtensions: $this->defaultExtensions,
            defaultTjhAmount: $this->defaultTjhAmount,
            defaultDriverCoverageAmount: $this->defaultDriverCoverageAmount,
            defaultPassengerCoverageAmount: $this->defaultPassengerCoverageAmount,
            defaultPassengerCount: $this->defaultPassengerCount,
            defaultEngineWarrantyEnabled: $this->defaultEngineWarrantyEnabled,
        );
    }

    public function disbursementDeductions(): float
    {
        return $this->bbnkbAmount
            + $this->pkbAmount
            + $this->invoiceAmount
            + $this->depositInstalmentAmount;
    }
}
