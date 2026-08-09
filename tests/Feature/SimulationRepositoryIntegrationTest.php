<?php

use App\Application\Simulation\DatabaseSimulationInput;
use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleUsage;
use App\Models\Admin;
use App\Models\InsuranceCascoRate;
use App\Models\InsuranceExtensionRate;
use App\Models\ProductRate;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\SimulationSetting;
use App\Models\VehicleModel;
use App\Models\VehiclePrice;
use App\Repositories\ProductResolver;
use App\Repositories\SimulationConfigurationRepository;
use App\Repositories\VehicleCascadeRepository;
use App\Services\SimulationService;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Tests\Support\TestVehicleMaster;

test('seeded repositories resolve the cascade configuration and run the simulation engine', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();
    Admin::factory()->create();

    $vehicles = app(VehicleCascadeRepository::class);
    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->has('prices', '>=', 2)
        ->with('type.brand.usage')
        ->firstOrFail();
    $usage = $model->type->brand->usage;
    $brand = $model->type->brand;
    $type = $model->type;

    expect($vehicles->usages()->pluck('id'))->toContain($usage->id)
        ->and($vehicles->brandsForUsage($usage->id)->pluck('id'))->toContain($brand->id)
        ->and($vehicles->typesForBrand($brand->id)->pluck('id'))->toContain($type->id)
        ->and($vehicles->modelsForType($type->id)->pluck('id'))->toContain($model->id)
        ->and($vehicles->modelsForType($type->id)->count())->toBeLessThan(VehicleModel::query()->count());

    $zeroPrice = $model->prices()->orderBy('year')->firstOrFail();
    $zeroPrice->update(['price' => 0]);
    $years = $vehicles->yearsForModel($model->id);

    expect($years->pluck('year'))->not->toContain($zeroPrice->year)
        ->and($years)->not->toBeEmpty();

    foreach ($years as $year) {
        expect(VehiclePrice::query()
            ->where('model_id', $model->id)
            ->where('year', $year['year'])
            ->where('price', '>', 0)
            ->exists())->toBeTrue();
    }

    $referralCategory = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $referralCategory->id,
        'sub_category_id' => $referralCategory->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['category', 'user']);
    $domainUsage = VehicleUsage::from($usage->name);
    $product = app(ProductResolver::class)->resolve($referral, $domainUsage);
    $config = app(SimulationConfigurationRepository::class)->forReferral($referral, $domainUsage);
    $databaseRate = ProductRate::query()
        ->where('product_id', $product->id)
        ->where('tenor_months', 12)
        ->value('effective_rate');

    expect($product->name)->toBe("{$referral->category->segment} {$usage->name} {$referral->category->tier}")
        ->and($config->product->name)->toBe($product->name)
        ->and($config->product->effectiveRateFor(12))->toBe((float) $databaseRate)
        ->and($config->maxVehicleAge)->toBe((int) SimulationSetting::query()->where('key', 'max_vehicle_age')->value('value'))
        ->and($config->insurance->cascoRates)->toHaveCount(
            InsuranceCascoRate::query()
                ->where('zone', $config->insurance->activeZone)
                ->where('variant', $config->insurance->activeVariant)
                ->count(),
        )
        ->and($config->insurance->extensionRate('flood'))->toBe(
            (float) InsuranceExtensionRate::query()->where('code', 'banjir')->value('rate'),
        );

    $showroomCategory = ReferralCategory::query()
        ->where('code', 'SRB')
        ->with('subCategories')
        ->firstOrFail();
    $showroomReferral = Referral::factory()->create([
        'category_id' => $showroomCategory->id,
        'sub_category_id' => $showroomCategory->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load('category');

    expect(app(ProductResolver::class)->resolve($showroomReferral, VehicleUsage::PASSENGER)->name)
        ->toBe('Reguler Passenger Referral')
        ->and(app(ProductResolver::class)->resolve($showroomReferral, VehicleUsage::COMMERCIAL)->name)
        ->toBe('Reguler Commercial Referral');

    $pricedYear = $years->first()['year'];
    $pricedVehicle = $vehicles->pricedVehicle($model->id, $pricedYear);
    $result = app(SimulationService::class)->simulate(
        $referral,
        new DatabaseSimulationInput(
            vehicleModelId: $model->id,
            vehicleYear: $pricedYear,
            financingType: FinancingType::DTN,
            mode: SimulationMode::A,
            debtorType: DebtorType::NON_ENTREPRENEUR,
            ageGroup: '36-45 tahun',
            stnkOwnership: StnkOwnership::OWN,
            instalmentType: InstalmentType::ADDB,
            coverageType: CoverageType::TLO_ALL,
        ),
        currentYear: $pricedYear + 1,
    );

    expect($result->forTenor(12)->phpmPrice)->toBe((float) $pricedVehicle->price)
        ->and($result->forTenor(12)->effectiveRate)->toBe((float) $databaseRate)
        ->and($result->forTenor(12)->instalment)->toBeGreaterThan(0)
        ->and($result->forTenor(12)->insurance->total)->toBeGreaterThan(0)
        ->and($result->forTenor(12)->outputAmount)->toBeGreaterThan(0);

    $this->actingAs($referral->user)
        ->getJson(route('simulation.vehicles.models', ['type' => $type->id]))
        ->assertOk()
        ->assertJsonCount($vehicles->modelsForType($type->id)->count(), 'data');

    $admin = Admin::query()->with('user')->firstOrFail();
    $this->actingAs($admin->user)
        ->getJson(route('simulation.vehicles.usages'))
        ->assertForbidden();
});
