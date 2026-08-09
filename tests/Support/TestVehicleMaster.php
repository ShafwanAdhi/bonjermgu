<?php

namespace Tests\Support;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehiclePrice;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use App\Repositories\VehicleCascadeRepository;
use Illuminate\Support\Facades\Cache;

final class TestVehicleMaster
{
    /** @return array{passenger: VehicleModel, commercial: VehicleModel} */
    public static function seed(): array
    {
        $passenger = self::createVehicle(
            usage: 'Passenger',
            brand: 'HONDA',
            origin: 'Japan',
            type: 'BRIO',
            model: 'ALL NEW BRIO RS CVT',
            prices: [
                2025 => 110_000_026,
                2024 => 100_000_000,
            ],
        );

        $commercial = self::createVehicle(
            usage: 'Commercial',
            brand: 'MITSUBISHI',
            origin: 'Japan',
            type: 'L300',
            model: 'L300 PICK UP',
            prices: [
                2025 => 180_000_000,
                2024 => 165_000_000,
            ],
        );

        Cache::forever(VehicleCascadeRepository::CACHE_VERSION_KEY, (string) hrtime(true));

        return [
            'passenger' => $passenger->load('type.brand.usage', 'prices'),
            'commercial' => $commercial->load('type.brand.usage', 'prices'),
        ];
    }

    /** @param array<int, int> $prices */
    private static function createVehicle(
        string $usage,
        string $brand,
        string $origin,
        string $type,
        string $model,
        array $prices,
    ): VehicleModel {
        $usageModel = VehicleUsage::query()->firstOrCreate(['name' => $usage]);

        $brandModel = VehicleBrand::query()->firstOrCreate(
            ['usage_id' => $usageModel->id, 'name' => $brand],
            ['origin' => $origin],
        );

        $typeModel = VehicleType::query()->firstOrCreate([
            'brand_id' => $brandModel->id,
            'name' => $type,
        ]);

        $vehicleModel = VehicleModel::query()->firstOrCreate([
            'type_id' => $typeModel->id,
            'name' => $model,
        ]);

        foreach ($prices as $year => $price) {
            VehiclePrice::query()->updateOrCreate(
                ['model_id' => $vehicleModel->id, 'year' => $year],
                ['price' => $price],
            );
        }

        return $vehicleModel;
    }
}
