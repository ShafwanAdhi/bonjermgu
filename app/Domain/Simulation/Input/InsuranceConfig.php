<?php

namespace App\Domain\Simulation\Input;

use App\Domain\Simulation\InsuranceCoverage;
use App\Domain\Simulation\VehicleUsage;
use RuntimeException;

final readonly class InsuranceConfig
{
    /**
     * @param  array<int, array{zone: string, usage: string, variant: string, coverage: string, band_min: int|float, band_max: int|float|null, rate: float}>  $cascoRates
     * @param  array<int, float>  $sumInsuredSchedule
     * @param  array<int, float>  $loadingRates
     * @param  array<string, float>  $extensionRates
     * @param  array<int, float>  $acpBaseRates
     * @param  array<string, float>  $acpUppings
     * @param  array<int, array{limit: int|float|null, rate: float}>  $tjhTiers
     */
    public function __construct(
        public string $activeZone,
        public string $activeVariant,
        public array $cascoRates,
        public array $sumInsuredSchedule,
        public array $loadingRates,
        public array $extensionRates,
        public array $acpBaseRates,
        public array $acpUppings,
        public array $tjhTiers,
        public float $engineWarrantyFee,
        public float $acpMaxLoanAmount = 0,
        public bool $dtnAcpEnabled = true,
        public bool $ucfAcpEnabled = true,
    ) {}

    public function cascoRateFor(
        VehicleUsage $usage,
        InsuranceCoverage $coverage,
        float $sumInsured,
    ): float {
        foreach ($this->cascoRates as $row) {
            $withinBand = $sumInsured >= $row['band_min']
                && ($row['band_max'] === null || $sumInsured <= $row['band_max']);

            if ($row['zone'] === $this->activeZone
                && $row['variant'] === $this->activeVariant
                && $row['usage'] === $usage->value
                && $row['coverage'] === $coverage->value
                && $withinBand) {
                return $row['rate'];
            }
        }

        throw new RuntimeException('Rate Casco tidak ditemukan untuk kombinasi simulasi.');
    }

    public function sumInsuredPercentage(int $year): float
    {
        return $this->sumInsuredSchedule[$year]
            ?? throw new RuntimeException("Jadwal Sum Insured tahun {$year} tidak ditemukan.");
    }

    public function loadingRate(int $vehicleAge): float
    {
        return $this->loadingRates[$vehicleAge] ?? 0.0;
    }

    public function extensionRate(string $code): float
    {
        return $this->extensionRates[$code] ?? 0.0;
    }

    public function acpBaseRate(int $tenorYears): float
    {
        return $this->acpBaseRates[$tenorYears] ?? 0.0;
    }

    public function acpUpping(?string $ageGroup): float
    {
        return $ageGroup === null ? 0.0 : ($this->acpUppings[$ageGroup] ?? 0.0);
    }

    /**
     * Upping an Account Officer set for one simulation, over the age-group
     * default. Only the group being simulated moves; the rest of the table
     * stays as Admin configured it.
     */
    public function withAcpUpping(?string $ageGroup, float $upping): self
    {
        if ($ageGroup === null) {
            return $this;
        }

        return new self(
            activeZone: $this->activeZone,
            activeVariant: $this->activeVariant,
            cascoRates: $this->cascoRates,
            sumInsuredSchedule: $this->sumInsuredSchedule,
            loadingRates: $this->loadingRates,
            extensionRates: $this->extensionRates,
            acpBaseRates: $this->acpBaseRates,
            acpUppings: [...$this->acpUppings, $ageGroup => $upping],
            tjhTiers: $this->tjhTiers,
            engineWarrantyFee: $this->engineWarrantyFee,
            acpMaxLoanAmount: $this->acpMaxLoanAmount,
            dtnAcpEnabled: $this->dtnAcpEnabled,
            ucfAcpEnabled: $this->ucfAcpEnabled,
        );
    }
}
