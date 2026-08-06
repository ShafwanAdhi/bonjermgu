<?php

namespace App\Domain\Simulation;

final class VehicleEligibility
{
    public function score(int $maxVehicleAge, int $tenorMonths, int $currentYear, int $vehicleYear): int
    {
        return ($maxVehicleAge - intdiv($tenorMonths, 12)) - ($currentYear - $vehicleYear);
    }

    public function isEligible(int $maxVehicleAge, int $tenorMonths, int $currentYear, int $vehicleYear): bool
    {
        return $this->score($maxVehicleAge, $tenorMonths, $currentYear, $vehicleYear) >= 1;
    }
}
