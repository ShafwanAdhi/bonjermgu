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

/*
 * The only two fields Amount Finance handoff can carry: financing product and
 * debtor type. Referral (a specific account) and Amount Finance are both
 * absent from this screen, so a create-application link can only prefill this
 * much — see CreateApplication's docblock.
 */
it('offers a create-application link carrying financing product and debtor type once calculated', function () {
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $component = Livewire::actingAs(User::factory()->accountOfficer()->create())
        ->test(OfficerSimulation::class)
        ->set('financing_type', 'UCF')
        ->set(officerState($category, $model, $price->year))
        ->set('debtor_type', 'entrepreneur')
        ->set('unit_price', (string) $price->price)
        ->call('calculate')
        ->assertHasNoErrors();

    $url = $component->instance()->createApplicationUrl();

    expect($url)->not->toBeNull()
        ->and($url)->toContain(route('applications.create', absolute: false))
        ->and($url)->toContain('financing_product=UCF')
        ->and($url)->toContain('debtor_type=entrepreneur');

    Carbon::setTestNow();
});

it('has no create-application link before a simulation is calculated', function () {
    [$category] = officerMaster();

    $component = Livewire::actingAs(User::factory()->accountOfficer()->create())
        ->test(OfficerSimulation::class)
        ->set('referral_category_id', (string) $category->id);

    expect($component->instance()->createApplicationUrl())->toBeNull();
});

it('keeps officer simulation form data until the officer clears it', function () {
    [$category, $model, $price] = officerMaster();
    $officer = User::factory()->accountOfficer()->create();

    Livewire::actingAs($officer)
        ->test(OfficerSimulation::class)
        ->set('financing_type', 'UCF')
        ->set(officerState($category, $model, $price->year))
        ->set('mode', 'B')
        ->set('unit_price', 'Rp '.number_format($price->price, 0, ',', '.'))
        ->set('desired_amount', 'Rp 25.000.000')
        ->set('up_admin', 'Rp 500.000')
        ->set('ext_flood', true);

    expect(session('simulation.officer.form.financing_type'))->toBe('UCF')
        ->and(session('simulation.officer.form.mode'))->toBe('B')
        ->and(session('simulation.officer.form.unit_price'))->toBe('Rp '.number_format($price->price, 0, ',', '.'))
        ->and(session('simulation.officer.form.desired_amount'))->toBe('Rp 25.000.000')
        ->and(session('simulation.officer.form.up_admin'))->toBe('Rp 500.000')
        ->and(session('simulation.officer.form.ext_flood'))->toBeTrue();

    Livewire::actingAs($officer)
        ->test(OfficerSimulation::class)
        ->assertSet('financing_type', 'UCF')
        ->assertSet('mode', 'B')
        ->assertSet('unit_price', 'Rp '.number_format($price->price, 0, ',', '.'))
        ->assertSet('desired_amount', 'Rp 25.000.000')
        ->assertSet('up_admin', 'Rp 500.000')
        ->assertSet('ext_flood', true)
        ->call('clearFormData')
        ->assertSet('financing_type', 'DTN')
        ->assertSet('mode', 'A')
        ->assertSet('unit_price', '')
        ->assertSet('desired_amount', '')
        ->assertSet('up_admin', '0')
        ->assertSet('ext_flood', false)
        ->assertSet('hasCalculated', false);

    expect(session()->has('simulation.officer.form'))->toBeFalse();
});
