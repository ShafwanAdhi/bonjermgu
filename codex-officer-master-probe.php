<?php

use App\Models\AgeGroup;
use App\Models\ReferralCategory;
use App\Models\VehicleModel;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$category = ReferralCategory::query()
    ->where('is_active', true)
    ->where('allows_passenger', true)
    ->orderBy('id')
    ->first();

$model = VehicleModel::query()
    ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
    ->whereHas('prices', fn ($query) => $query->where('price', '>', 0))
    ->with([
        'type.brand.usage',
        'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year'),
    ])
    ->first();

$age = AgeGroup::query()->orderBy('sort_order')->first();

echo json_encode([
    'category_id' => $category?->id,
    'age_group_id' => $age?->id,
    'usage_id' => $model?->type?->brand?->usage_id,
    'brand_id' => $model?->type?->brand_id,
    'type_id' => $model?->type_id,
    'model_id' => $model?->id,
    'vehicle_year' => $model?->prices?->first()?->year,
    'price' => $model?->prices?->first()?->price,
], JSON_PRETTY_PRINT);
