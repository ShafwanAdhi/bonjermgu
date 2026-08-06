<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the vehicle master: usages, brands, types, models, and PHPM prices.
 *
 * Source: draft_Web_bonjermgu_agt_2026_2.xlsx, sheet "PHPM 1 DT Ext".
 * See docs/master-data-extraction.md for the extraction rules.
 *
 * Idempotent: safe to run repeatedly. Existing rows are updated, not duplicated.
 */
class VehicleSeeder extends Seeder
{
    private const CHUNK = 1000;

    public function run(): void
    {
        $dataPath = database_path('seeders/data');

        $origins = json_decode(file_get_contents($dataPath.'/vehicle_origins.json'), true);
        $handle = fopen($dataPath.'/vehicle_prices.csv', 'r');

        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membuka data harga kendaraan.');
        }

        fgetcsv($handle); // header

        $usageIds = $this->seedUsages();

        $brandIds = [];
        $typeIds = [];
        $modelIds = [];
        $priceBuffer = [];
        $priceCount = 0;

        DB::transaction(function () use (
            $handle, $origins, $usageIds,
            &$brandIds, &$typeIds, &$modelIds, &$priceBuffer, &$priceCount
        ) {
            while (($row = fgetcsv($handle)) !== false) {
                [$usage, $brand, $type, $model, $year, $price] = $row;

                $brandKey = $usage.'|'.$brand;
                $brandIds[$brandKey] ??= $this->upsertBrand(
                    $usageIds[$usage],
                    $brand,
                    $origins[strtoupper($brand)] ?? 'Non Japan',
                );

                $typeKey = $brandKey.'|'.$type;
                $typeIds[$typeKey] ??= $this->upsertType($brandIds[$brandKey], $type);

                $modelKey = $typeKey.'|'.$model;
                $modelIds[$modelKey] ??= $this->upsertModel($typeIds[$typeKey], $model);

                $priceBuffer[] = [
                    'model_id' => $modelIds[$modelKey],
                    'year' => (int) $year,
                    'price' => (int) $price,
                ];
                $priceCount++;

                if (count($priceBuffer) >= self::CHUNK) {
                    $this->flushPrices($priceBuffer);
                    $priceBuffer = [];
                }
            }

            if ($priceBuffer !== []) {
                $this->flushPrices($priceBuffer);
            }
        });

        fclose($handle);

        $this->command?->info(sprintf(
            'Vehicle master: %d brands, %d types, %d models, %d prices.',
            count($brandIds), count($typeIds), count($modelIds), $priceCount,
        ));
    }

    /** @return array<string,int> */
    private function seedUsages(): array
    {
        $ids = [];

        foreach (['Passenger', 'Commercial'] as $name) {
            DB::table('vehicle_usages')->insertOrIgnore(['name' => $name]);
            $ids[$name] = DB::table('vehicle_usages')->where('name', $name)->value('id');
        }

        return $ids;
    }

    private function upsertBrand(int $usageId, string $name, string $origin): int
    {
        DB::table('vehicle_brands')->upsert(
            [['usage_id' => $usageId, 'name' => $name, 'origin' => $origin]],
            ['usage_id', 'name'],
            ['origin'],
        );

        return DB::table('vehicle_brands')
            ->where('usage_id', $usageId)->where('name', $name)->value('id');
    }

    private function upsertType(int $brandId, string $name): int
    {
        DB::table('vehicle_types')->insertOrIgnore([
            'brand_id' => $brandId,
            'name' => $name,
        ]);

        return DB::table('vehicle_types')
            ->where('brand_id', $brandId)->where('name', $name)->value('id');
    }

    private function upsertModel(int $typeId, string $name): int
    {
        DB::table('vehicle_models')->insertOrIgnore([
            'type_id' => $typeId,
            'name' => $name,
        ]);

        return DB::table('vehicle_models')
            ->where('type_id', $typeId)->where('name', $name)->value('id');
    }

    /** @param array<int,array{model_id:int,year:int,price:int}> $rows */
    private function flushPrices(array $rows): void
    {
        DB::table('vehicle_prices')->upsert($rows, ['model_id', 'year'], ['price']);
    }
}
