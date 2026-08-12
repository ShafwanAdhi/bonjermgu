<?php

use App\Livewire\Simulation\OfficerSimulation;
use App\Models\AgeGroup;
use App\Models\ReferralCategory;
use App\Models\User;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Livewire\Livewire;
use Tests\Support\TestVehicleMaster;

function officerMaster(): array
{
    test()->seed(ReferralMasterSeeder::class);
    test()->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('is_active', true)
        ->where('allows_passenger', true)
        ->orderBy('id')
        ->firstOrFail();

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0))
        ->with(['type.brand', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->firstOrFail();

    return [$category, $model, $model->prices->first()];
}

function officerState(ReferralCategory $category, VehicleModel $model, int $year): array
{
    return [
        'referral_category_id' => (string) $category->id,
        'debtor_type' => 'non_entrepreneur',
        'age_group_id' => (string) AgeGroup::query()->where('label', '36-45 tahun')->value('id'),
        'usage_id' => (string) $model->type->brand->usage_id,
        'brand_id' => (string) $model->type->brand_id,
        'type_id' => (string) $model->type_id,
        'model_id' => (string) $model->id,
        'vehicle_year' => (string) $year,
        'instalment_type' => 'ADDB',
        'coverage_type' => 'tlo_all',
        'stnk_ownership' => 'own',
    ];
}

it('refuses the officer simulation to everyone except AO', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/simulation/officer')->assertForbidden();
})->with(['admin', 'referral']);

it('lets an AO reach the officer simulation', function () {
    $this->actingAs(User::factory()->accountOfficer()->create());

    $this->get('/simulation/officer')->assertOk();
});

it('calculates five tenors and a derivation for the Officer profile', function () {
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $component = Livewire::actingAs(User::factory()->accountOfficer()->create())
        ->test(OfficerSimulation::class)
        ->set(officerState($category, $model, $price->year))
        ->set('unit_price', (string) $price->price)
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertDispatched('simulation-calculated');

    expect($component->get('hasCalculated'))->toBeTrue()
        ->and($component->get('rows'))->toHaveCount(5)
        ->and($component->get('traces'))->toHaveCount(5)
        ->and($component->get('summary')['product'])->toBeString()
        ->and($component->instance()->trace())->not->toBeEmpty();

    Carbon::setTestNow();
});

it('switches the calculation trace tenor without recalculating the simulation', function () {
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $component = Livewire::actingAs(User::factory()->accountOfficer()->create())
        ->test(OfficerSimulation::class)
        ->set(officerState($category, $model, $price->year))
        ->set('unit_price', (string) $price->price)
        ->call('calculate')
        ->assertHasNoErrors();

    $rows = $component->get('rows');
    $trace24 = $component->get('traces')[24];

    $component
        ->call('traceTenor', 24)
        ->assertSet('traced_tenor', 24);

    expect($component->get('rows'))->toBe($rows)
        ->and($component->instance()->trace())->toBe($trace24);

    Carbon::setTestNow();
});

/*
 * The Officer profile prices Dana Tunai from the appraised value the AO typed,
 * not from PHPM. An appraisal above PHPM must therefore raise Net DP, which is
 * exactly what the Referral screen never does.
 */
it('prices Dana Tunai from the appraised value and charges deviation on the excess', function () {
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $run = function (int $unitPrice) use ($category, $model, $price) {
        return Livewire::actingAs(User::factory()->accountOfficer()->create())
            ->test(OfficerSimulation::class)
            ->set(officerState($category, $model, $price->year))
            ->set('unit_price', (string) $unitPrice)
            ->call('calculate')
            ->assertHasNoErrors()
            ->get('rows')[0]['instalment'];
    };

    $atPhpm = $run((int) $price->price);
    $abovePhpm = $run((int) $price->price * 2);

    // Deviation is added to Net DP, so doubling the appraisal does not double
    // the financed amount — the instalment must not simply double.
    expect($atPhpm)->not->toEqual($abovePhpm);

    Carbon::setTestNow();
});

it('does not offer Commercial units on Pembiayaan Mobil Bekas', function () {
    [$category] = officerMaster();

    $component = Livewire::actingAs(User::factory()->accountOfficer()->create())
        ->test(OfficerSimulation::class)
        ->set('referral_category_id', (string) $category->id)
        ->set('financing_type', 'UCF');

    expect(collect($component->instance()->usages())->pluck('name'))->not->toContain('Commercial');
});

/*
 * Upping the AO sets applies to this run only. If it did not reach the engine
 * the two instalments would be identical and the field would be decoration.
 */
it('applies the upping an officer enters without touching the Product', function () {
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $run = function (string $upRate) use ($category, $model, $price) {
        return Livewire::actingAs(User::factory()->accountOfficer()->create())
            ->test(OfficerSimulation::class)
            ->set(officerState($category, $model, $price->year))
            ->set('unit_price', (string) $price->price)
            ->set('up_rate', $upRate)
            ->call('calculate')
            ->assertHasNoErrors()
            ->get('rows')[0]['instalment'];
    };

    expect($run('0'))->not->toEqual($run('2'));

    Carbon::setTestNow();
});
