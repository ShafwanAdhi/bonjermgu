<?php

namespace App\Repositories;

use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage as DomainVehicleUsage;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehiclePrice;
use App\Models\VehicleType;
use App\Models\VehicleUsage;
use App\Repositories\Data\VehiclePriceData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class VehicleCascadeRepository
{
    public const CACHE_VERSION_KEY = 'vehicle_master_version';

    /** @return Collection<int, array{id: int, name: string}> */
    public function usages(): Collection
    {
        return $this->remember('usages', fn () => VehicleUsage::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (VehicleUsage $usage) => [
                'id' => $usage->id,
                'name' => $usage->name,
            ]));
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function brandsForUsage(int $usageId): Collection
    {
        return $this->remember("usage:{$usageId}:brands", fn () => VehicleBrand::query()
            ->where('usage_id', $usageId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (VehicleBrand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ]));
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function typesForBrand(int $brandId): Collection
    {
        return $this->remember("brand:{$brandId}:types", fn () => VehicleType::query()
            ->where('brand_id', $brandId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (VehicleType $type) => [
                'id' => $type->id,
                'name' => $type->name,
            ]));
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function modelsForType(int $typeId): Collection
    {
        return $this->remember("type:{$typeId}:models", fn () => VehicleModel::query()
            ->where('type_id', $typeId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (VehicleModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
            ]));
    }

    /** @return Collection<int, array{year: int}> */
    public function yearsForModel(int $modelId): Collection
    {
        return $this->remember("model:{$modelId}:years", fn () => VehiclePrice::query()
            ->where('model_id', $modelId)
            ->where('price', '>', 0)
            ->orderByDesc('year')
            ->get(['year'])
            ->map(fn (VehiclePrice $price) => ['year' => $price->year]));
    }

    public function pricedVehicle(int $modelId, int $year): VehiclePriceData
    {
        $row = VehiclePrice::query()
            ->join('vehicle_models', 'vehicle_models.id', '=', 'vehicle_prices.model_id')
            ->join('vehicle_types', 'vehicle_types.id', '=', 'vehicle_models.type_id')
            ->join('vehicle_brands', 'vehicle_brands.id', '=', 'vehicle_types.brand_id')
            ->join('vehicle_usages', 'vehicle_usages.id', '=', 'vehicle_brands.usage_id')
            ->where('vehicle_prices.model_id', $modelId)
            ->where('vehicle_prices.year', $year)
            ->where('vehicle_prices.price', '>', 0)
            ->first([
                'vehicle_prices.model_id',
                'vehicle_prices.year',
                'vehicle_prices.price',
                'vehicle_usages.name as usage_name',
                'vehicle_brands.origin',
                'vehicle_brands.name as brand_name',
                'vehicle_types.name as type_name',
                'vehicle_models.name as model_name',
            ]);

        if ($row === null) {
            throw new RuntimeException('Harga kendaraan untuk model dan tahun tersebut tidak ditemukan.');
        }

        return new VehiclePriceData(
            modelId: (int) $row->model_id,
            year: (int) $row->year,
            price: (int) $row->price,
            usage: DomainVehicleUsage::from($row->usage_name),
            origin: VehicleOrigin::from($row->origin),
            brand: $row->brand_name,
            type: $row->type_name,
            model: $row->model_name,
        );
    }

    /** @param callable(): Collection<int, array<string, mixed>> $callback */
    private function remember(string $key, callable $callback): Collection
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, '0');

        return Cache::remember(
            "vehicle_master:{$version}:{$key}",
            now()->addHours(12),
            $callback,
        );
    }
}
