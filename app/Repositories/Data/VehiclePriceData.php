<?php

namespace App\Repositories\Data;

use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;

final readonly class VehiclePriceData
{
    public function __construct(
        public int $modelId,
        public int $year,
        public int $price,
        public VehicleUsage $usage,
        public VehicleOrigin $origin,
        public string $brand,
        public string $type,
        public string $model,
    ) {}
}
