<?php

namespace App\Domain\Simulation\Input;

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

    public function disbursementDeductions(): float
    {
        return $this->bbnkbAmount
            + $this->pkbAmount
            + $this->invoiceAmount
            + $this->depositInstalmentAmount;
    }
}
