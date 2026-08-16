<?php

use App\Services\ConfigurationIntegrityValidator;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Support\Facades\DB;

it('seeds the complete simulation configuration and vehicle master idempotently', function () {
    $seed = function (): void {
        test()->seed(ReferralMasterSeeder::class);
        test()->seed(SimulationConfigurationSeeder::class);
        test()->seed(VehicleSeeder::class);
    };

    $seed();

    $expectedCounts = [
        'products' => 17,
        'product_rates' => 85,
        'vehicle_usages' => 2,
        'vehicle_brands' => 27,
        // The moved price asset contains 268 types and 4,678 models with at
        // least one price. The other 202 catalogue-only models documented in
        // master-data-extraction.md are not present in that asset.
        'vehicle_types' => 268,
        'vehicle_models' => 4_678,
        'vehicle_prices' => 26_791,
        'insurance_casco_rates' => 40,
        'insurance_loading_rates' => 15,
        'insurance_extension_rates' => 6,
        'acp_base_rates' => 5,
        'acp_uppings' => 4,
        'tjh_tiers' => 4,
        'fiducia_tiers' => 7,
        'sum_insured_schedules' => 5,
        'simulation_settings' => 41,
    ];

    foreach ($expectedCounts as $table => $count) {
        $this->assertDatabaseCount($table, $count);
    }

    expect(DB::table('product_rates')->whereNull('effective_rate')->count())->toBe(5)
        ->and(DB::table('referral_categories')->where('code', 'SRB')->value('tier'))->toBe('Referral')
        ->and(DB::table('referral_categories')->where('code', 'CIN')->value('allows_commercial'))->toBeFalse()
        ->and(DB::table('simulation_settings')->where('key', 'max_vehicle_age')->value('value'))->toBe('16')
        ->and(DB::table('simulation_settings')->where('key', 'active_rate_variant')->value('value'))->toBe('Batas Bawah')
        ->and(DB::table('sum_insured_schedules')->where('year_index', 5)->value('percentage'))->toBe('0.7000');

    app(ConfigurationIntegrityValidator::class)->assertProducts();

    $referencePrice = DB::table('vehicle_prices as prices')
        ->join('vehicle_models as models', 'models.id', '=', 'prices.model_id')
        ->join('vehicle_types as types', 'types.id', '=', 'models.type_id')
        ->join('vehicle_brands as brands', 'brands.id', '=', 'types.brand_id')
        ->join('vehicle_usages as usages', 'usages.id', '=', 'brands.usage_id')
        ->where('usages.name', 'Passenger')
        ->where('brands.name', 'HONDA')
        ->where('types.name', 'BRIO')
        ->where('models.name', 'ALL NEW BRIO RS CVT')
        ->where('prices.year', 2017)
        ->value('prices.price');

    expect($referencePrice)->toBe(110_000_026);

    $seed();

    foreach ($expectedCounts as $table => $count) {
        $this->assertDatabaseCount($table, $count);
    }

    $duplicatePrices = DB::table('vehicle_prices')
        ->select(['model_id', 'year'])
        ->groupBy('model_id', 'year')
        ->havingRaw('COUNT(*) > 1')
        ->exists();

    expect($duplicatePrices)->toBeFalse();
});
