<?php

use App\Application\Simulation\CalculationTrace;
use App\Application\Simulation\ConfigurationSimulationRequest;
use App\Application\Simulation\ConfigurationSimulator;
use App\Application\Simulation\DatabaseSimulationInput;
use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleUsage;
use App\Livewire\Admin\Simulation\ConfigurationSimulation;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Referral;
use App\Models\User;
use App\Models\VehicleModel;
use App\Repositories\ProductResolver;
use App\Repositories\SimulationConfigurationRepository;
use App\Services\SimulationService;
use App\Support\Format;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Livewire\Livewire;
use Tests\Support\TestVehicleMaster;

beforeEach(function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $this->admin = Admin::factory()->create()->user;

    $this->model = VehicleModel::query()
        ->whereHas('prices', fn ($q) => $q->where('price', '>', 0))
        ->with('type.brand.usage')
        ->first();

    $this->year = $this->model->prices()->where('price', '>', 0)->orderByDesc('year')->value('year');
});

function simulationRequest(Product $product, array $overrides = []): ConfigurationSimulationRequest
{
    return new ConfigurationSimulationRequest(
        product: $product,
        vehicleModelId: $overrides['model'] ?? test()->model->id,
        vehicleYear: $overrides['year'] ?? test()->year,
        financingType: $overrides['financing'] ?? FinancingType::UCF,
        mode: SimulationMode::A,
        debtorType: DebtorType::ENTREPRENEUR,
        ageGroup: null,
        stnkOwnership: StnkOwnership::OWN,
        instalmentType: InstalmentType::ADDB,
        coverageType: CoverageType::TLO_ALL,
        marketPrice: $overrides['market'] ?? 110_000_000,
    );
}

/* ------------------------------------------------------------- Akses */

it('shows the configuration simulation to admin', function () {
    $this->actingAs($this->admin)
        ->get('/configuration/simulation')
        ->assertOk()
        ->assertSee('Uji Konfigurasi')
        ->assertSee('Parameter Uji');
});

it('refuses it to referral and officer', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/configuration/simulation')->assertForbidden();
})->with(['referral', 'accountOfficer']);

/*
 * Admin has no business touching debtor data, so the screen carries no field
 * for it (business.md section 5, CLAUDE.md rule 9).
 */
it('exposes no debtor field at all', function () {
    $response = $this->actingAs($this->admin)->get('/configuration/simulation');

    $response->assertDontSee('Nama Debitur')
        ->assertDontSee('NIK Debitur')
        ->assertDontSee('Tanggal Lahir');

    $properties = array_keys(get_class_vars(ConfigurationSimulation::class));

    foreach ($properties as $property) {
        expect($property)->not->toContain('debtor_name')
            ->and($property)->not->toContain('debtor_nik')
            ->and($property)->not->toContain('birth');
    }
});

/* -------------------------------------------------- Kesetaraan engine */

/**
 * The point of the whole screen: it must agree with what a Referral would see.
 * Same Product, same inputs, same engine — so the figures have to match to the
 * rupiah, otherwise Admin is verifying a fiction.
 */
it('produces the same figures as the referral path for the same product', function () {
    $referral = Referral::factory()->create();
    $usage = $this->model->type->brand->usage->name === 'Passenger'
        ? VehicleUsage::PASSENGER
        : VehicleUsage::COMMERCIAL;

    // Whatever Product the referral's category resolves to — feed that same
    // Product into the admin path.
    $product = app(ProductResolver::class)->resolve($referral, $usage);

    $referralResult = app(SimulationService::class)->simulate(
        $referral,
        new DatabaseSimulationInput(
            vehicleModelId: $this->model->id,
            vehicleYear: $this->year,
            financingType: FinancingType::UCF,
            mode: SimulationMode::A,
            debtorType: DebtorType::ENTREPRENEUR,
            ageGroup: null,
            stnkOwnership: StnkOwnership::OWN,
            instalmentType: InstalmentType::ADDB,
            coverageType: CoverageType::TLO_ALL,
            marketPrice: 110_000_000,
        ),
        2026,
    );

    $adminOutcome = app(ConfigurationSimulator::class)->run(simulationRequest($product), 2026);

    foreach ([12, 24, 36, 48, 60] as $tenor) {
        $fromReferral = $referralResult->forTenor($tenor);
        $fromAdmin = $adminOutcome->result->forTenor($tenor);

        expect($fromAdmin->instalment)->toBe(
            $fromReferral->instalment,
            "Angsuran tenor {$tenor} berbeda antara jalur Admin dan Referral."
        );
        expect(round($fromAdmin->outputAmount))->toBe(
            round($fromReferral->outputAmount),
            "Pencairan tenor {$tenor} berbeda antara jalur Admin dan Referral."
        );
    }
});

/** Admin can reach a Product no referral category maps to — that is the point. */
it('runs a product that no referral category can reach', function () {
    $product = Product::where('name', 'Captive Passenger Low Rate')->first();

    expect($product)->not->toBeNull();

    $outcome = app(ConfigurationSimulator::class)->run(simulationRequest($product), 2026);

    expect($outcome->result->forTenor(12)->instalment)->toBeGreaterThan(0);
});

it('reads the configuration of the product it was given, not of a referral', function () {
    $product = Product::where('name', 'Captive Passenger Low Rate')->firstOrFail();
    $expected = app(SimulationConfigurationRepository::class)->forProduct($product);

    $outcome = app(ConfigurationSimulator::class)->run(simulationRequest($product), 2026);

    expect($outcome->config->product->name)->toBe($expected->product->name)
        ->and($outcome->result->forTenor(12)->effectiveRate)
        ->toBe($expected->product->effectiveRateFor(12));
});

/* ------------------------------------------------------------- Jejak */

it('renders the calculation trace for the traced tenor', function () {
    $product = Product::where('is_active', true)->orderBy('name')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigurationSimulation::class)
        ->set('product_id', (string) $product->id)
        ->set('financing_type', 'UCF')
        ->set('usage_id', (string) $this->model->type->brand->usage_id)
        ->set('brand_id', (string) $this->model->type->brand_id)
        ->set('type_id', (string) $this->model->type_id)
        ->set('model_id', (string) $this->model->id)
        ->set('vehicle_year', (string) $this->year)
        ->set('market_price', 'Rp 110.000.000')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertSee('Rincian Perhitungan')
        ->assertSee('Net DP dan LTV')
        ->assertSee('Bunga jual')
        ->assertSee('Total asuransi');
});

it('switches the trace to another tenor', function () {
    $product = Product::where('is_active', true)->orderBy('name')->firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(ConfigurationSimulation::class)
        ->set('product_id', (string) $product->id)
        ->set('usage_id', (string) $this->model->type->brand->usage_id)
        ->set('brand_id', (string) $this->model->type->brand_id)
        ->set('type_id', (string) $this->model->type_id)
        ->set('model_id', (string) $this->model->id)
        ->set('vehicle_year', (string) $this->year)
        ->set('market_price', 'Rp 110.000.000')
        ->call('calculate')
        ->assertSet('traced_tenor', 12)
        ->call('traceTenor', 60)
        ->assertSet('traced_tenor', 60)
        ->assertSee('tenor 60 bulan');
});

/**
 * The trace must report what the engine produced, never its own arithmetic.
 * This checks the headline figures in the trace against the engine directly.
 */
it('shows figures that match the engine rather than recomputing them', function () {
    $product = Product::where('is_active', true)->orderBy('name')->firstOrFail();
    $outcome = app(ConfigurationSimulator::class)->run(simulationRequest($product), (int) today()->format('Y'));
    $tenor = $outcome->result->forTenor(12);

    $trace = CalculationTrace::build($tenor, $outcome);

    $values = collect($trace)->flatMap(fn ($section) => collect($section['steps'])->pluck('value'))->all();

    expect($values)->toContain(Format::rupiah($tenor->instalment))
        ->and($values)->toContain(Format::rupiah($tenor->insurance->total));
});

it('explains a tenor that produces nothing instead of showing bare zeros', function () {
    // A rate table with an empty 60-month slot: Commercial products have none.
    $product = Product::where('name', 'like', '%Commercial%')->firstOrFail();
    $outcome = app(ConfigurationSimulator::class)->run(simulationRequest($product), 2026);

    $tenor = $outcome->result->forTenor(60);

    expect($tenor->rateAvailable)->toBeFalse();

    $trace = CalculationTrace::build($tenor, $outcome);

    expect($trace[0]['title'])->toBe('Tenor tidak menghasilkan pembiayaan')
        ->and($trace[0]['note'])->toContain('Kosong berarti tenor tidak tersedia');
});
