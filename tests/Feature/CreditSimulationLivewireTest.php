<?php

use App\Domain\Simulation\VehicleUsage as DomainVehicleUsage;
use App\Livewire\Simulation\CreditSimulation;
use App\Models\AgeGroup;
use App\Models\Domicile;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\SimulationSetting;
use App\Models\VehicleModel;
use App\Models\VehicleUsage;
use App\Repositories\ProductResolver;
use Carbon\Carbon;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Support\TestVehicleMaster;

function validSimulationState(VehicleModel $model, int $year): array
{
    return [
        'domicile_id' => (string) Domicile::query()->orderBy('sort_order')->value('id'),
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
        'funding_purpose' => 'education',
    ];
}

test('Referral calculates both products and modes on the server then prints the active result', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        // Tanpa ORDER BY, PostgreSQL bebas memilih baris mana pun. Tes yang
        // memilih baris berbeda tiap jalan gagal sesekali tanpa sebab terlihat.
        ->orderBy('id')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['user', 'category']);

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 2)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $component = Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->assertCount('results', 5)
        ->assertSeeHtml('wire:loading.delay')
        ->assertDontSee('Data calon debitur untuk dokumen simulasi')
        ->set(validSimulationState($model, $price->year))
        ->assertSet('hasCalculated', false)
        ->assertDontSee('12 Bulan');

    expect(session()->has('simulation.active'))->toBeFalse();

    $component
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertDispatched('simulation-calculated')
        ->assertSet('hasCalculated', true)
        ->assertSee('12 Bulan')
        ->assertCount('results', 5);

    $dtnModeA = $component->get('results')[0]['disbursement'];
    expect($dtnModeA)->not->toBe('Rp 0')
        ->and(session()->has('simulation.active'))->toBeTrue();

    $component
        ->set('mode', 'B')
        ->set('desired_amount', 'Rp '.number_format(intdiv($price->price, 2), 0, ',', '.'))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true);

    $dtnModeB = $component->get('results')[0]['disbursement'];
    expect($component->instance()->disbursementHeading())->toBe('Pencairan')
        ->and($dtnModeB)->not->toBe($dtnModeA);

    $component
        ->set('financing_type', 'UCF')
        ->set('mode', 'A')
        ->set('market_price', 'Rp '.number_format($price->price, 0, ',', '.'))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertCount('results', 5);

    $ucfModeA = $component->get('results')[0]['disbursement'];
    expect($component->instance()->disbursementHeading())->toBe('Pencairan Neto')
        ->and($ucfModeA)->not->toBe($dtnModeA);

    $component
        ->set('mode', 'B')
        ->set('desired_amount', 'Rp '.number_format((int) round($price->price * 0.6), 0, ',', '.'))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertCount('results', 5);

    $ucfModeB = $component->get('results')[0]['disbursement'];
    expect($component->instance()->disbursementHeading())->toBe('Total DP')
        ->and($ucfModeB)->not->toBe($ucfModeA)
        ->and($ucfModeB)->not->toBe('Rp 0');

    $component
        ->call('openPrintForm')
        ->assertSet('showPrintForm', true)
        ->assertSee('Data calon debitur untuk dokumen simulasi')
        ->set('debtor_nik', '123')
        ->call('preparePrint')
        ->assertHasErrors([
            'debtor_name' => 'required',
            'debtor_nik' => 'digits',
            'debtor_birth_date' => 'required',
        ])
        ->assertSet('hasCalculated', true)
        ->set('debtor_name', 'Rina Calon Debitur')
        ->set('debtor_nik', '3173054509900001')
        ->set('debtor_birth_date', '1990-09-05')
        ->call('preparePrint')
        ->assertHasNoErrors()
        ->assertRedirect(route('simulation.print'));

    $snapshot = session()->get('simulation.active');
    expect($snapshot['subject']['debtor_name'])->toBe('Rina Calon Debitur')
        ->and($snapshot['subject']['product'])->toBe('Pembiayaan Mobil Bekas')
        ->and($snapshot['subject']['mode'])->toBe('Berdasarkan Total DP')
        ->and($snapshot['results'])->toBe($component->get('results'));

    $this->actingAs($referral->user)
        ->withSession(['simulation.active' => $snapshot])
        ->get(route('simulation.print'))
        ->assertOk()
        ->assertSee('Rina Calon Debitur')
        ->assertSee('Pembiayaan Mobil Bekas')
        ->assertSee('Berdasarkan Total DP')
        ->assertSee('Download Simulasi Kredit')
        ->assertDontSee('Cetak Halaman')
        ->assertSee($ucfModeB);

    $download = $this->actingAs($referral->user)
        ->withSession(['simulation.active' => $snapshot])
        ->get(route('simulation.print.download'));

    $download->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($download->headers->get('Content-Disposition'))->toContain('attachment; filename="simulasi-kredit-rina-calon-debitur-')
        ->and($download->getContent())->toStartWith('%PDF-1.4')
        ->and($download->getContent())->toContain('Rina Calon Debitur')
        ->and($download->getContent())->toContain('Berdasarkan Total DP')
        ->and($download->getContent())->toContain($ucfModeB)
        ->and($download->getContent())->toContain('bonjemgu.com');

    Carbon::setTestNow();
});

/*
 * pages.md §18: a failed calculation must name the cause, not show a bare
 * zero. Pushing the clock far past the vehicle's year makes every tenor fail
 * eligibility, which is the cheapest reliable way to force a zero row without
 * touching product/rate configuration.
 */
test('a tenor that fails eligibility explains why instead of showing a bare zero', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        // Tanpa ORDER BY, PostgreSQL bebas memilih baris mana pun. Tes yang
        // memilih baris berbeda tiap jalan gagal sesekali tanpa sebab terlihat.
        ->orderBy('id')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['user', 'category']);

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0))
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    // Thirty years out, even the 12-month tenor fails eligibility.
    Carbon::setTestNow(Carbon::create($price->year + 30, 8, 4));

    Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->set(validSimulationState($model, $price->year))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertSee('Usia kendaraan melebihi batas kelayakan untuk tenor ini.');

    Carbon::setTestNow();
});

test('simulation keeps calculating when legacy databases are missing newer default settings', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    SimulationSetting::query()
        ->whereIn('key', ['ucf_non_japan_net_dp_rate', 'acp_max_loan_amount'])
        ->delete();
    Cache::flush();

    $category = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        // Tanpa ORDER BY, PostgreSQL bebas memilih baris mana pun. Tes yang
        // memilih baris berbeda tiap jalan gagal sesekali tanpa sebab terlihat.
        ->orderBy('id')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['user', 'category']);

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 2)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->set(validSimulationState($model, $price->year))
        ->set('financing_type', 'UCF')
        ->set('mode', 'B')
        ->set('market_price', 'Rp '.number_format($price->price, 0, ',', '.'))
        ->set('desired_amount', 'Rp '.number_format((int) round($price->price * 0.6), 0, ',', '.'))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertDontSee('Simulation setting')
        ->assertCount('results', 5);
});

test('legal entity simulation print does not require personal identity fields', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        // Tanpa ORDER BY, PostgreSQL bebas memilih baris mana pun. Tes yang
        // memilih baris berbeda tiap jalan gagal sesekali tanpa sebab terlihat.
        ->orderBy('id')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['user', 'category']);

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 1)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $state = validSimulationState($model, $price->year);
    $state['debtor_type'] = 'legal_entity';
    $state['age_group_id'] = null;

    $component = Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->set($state)
        ->call('calculate')
        ->assertHasNoErrors()
        ->call('openPrintForm')
        ->assertSee('Data calon debitur untuk dokumen simulasi')
        ->assertDontSee('NIK')
        ->assertDontSee('Tanggal Lahir')
        ->set('debtor_name', 'PT Calon Debitur')
        ->set('debtor_nik', '123')
        ->call('preparePrint')
        ->assertHasNoErrors(['debtor_nik', 'debtor_birth_date'])
        ->assertRedirect(route('simulation.print'));

    $snapshot = session()->get('simulation.active');
    expect($snapshot['subject']['debtor_name'])->toBe('PT Calon Debitur')
        ->and($snapshot['subject']['debtor_nik'])->toBeNull()
        ->and($snapshot['subject']['debtor_birth_date'])->toBeNull()
        ->and($component->get('debtor_nik'))->toBe('');

    $this->actingAs($referral->user)
        ->withSession(['simulation.active' => $snapshot])
        ->get(route('simulation.print'))
        ->assertOk()
        ->assertSee('PT Calon Debitur')
        ->assertSee('Badan Hukum Usaha')
        ->assertDontSee('NIK')
        ->assertDontSee('Tanggal Lahir');

    $download = $this->actingAs($referral->user)
        ->withSession(['simulation.active' => $snapshot])
        ->get(route('simulation.print.download'));

    $download->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($download->getContent())->toStartWith('%PDF-1.4')
        ->and($download->getContent())->toContain('PT Calon Debitur')
        ->and($download->getContent())->toContain('Badan Hukum Usaha')
        ->and($download->getContent())->toContain('bonjemgu.com')
        ->and($download->getContent())->not->toContain('Tanggal Lahir');

    Carbon::setTestNow();
});

test('simulation form state is kept temporarily and can be cleared', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        // Tanpa ORDER BY, PostgreSQL bebas memilih baris mana pun. Tes yang
        // memilih baris berbeda tiap jalan gagal sesekali tanpa sebab terlihat.
        ->orderBy('id')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['user', 'category']);

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 1)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    $state = validSimulationState($model, $price->year);

    Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->set($state)
        ->set('financing_type', 'UCF')
        ->set('mode', 'B')
        ->set('market_price', 'Rp '.number_format($price->price, 0, ',', '.'))
        ->set('desired_amount', 'Rp 25.000.000');

    expect(session('simulation.credit.form.financing_type'))->toBe('UCF')
        ->and(session('simulation.credit.form.mode'))->toBe('B')
        ->and(session('simulation.credit.form.market_price'))->toBe('Rp '.number_format($price->price, 0, ',', '.'))
        ->and(session('simulation.credit.form.desired_amount'))->toBe('Rp 25.000.000');

    Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->assertSet('financing_type', 'UCF')
        ->assertSet('mode', 'B')
        ->assertSet('market_price', 'Rp '.number_format($price->price, 0, ',', '.'))
        ->assertSet('desired_amount', 'Rp 25.000.000')
        ->call('clearFormData')
        ->assertSet('financing_type', 'DTN')
        ->assertSet('mode', 'A')
        ->assertSet('market_price', '')
        ->assertSet('desired_amount', '')
        ->assertSet('hasCalculated', false);

    expect(session()->has('simulation.credit.form'))->toBeFalse();
});

test('simulation exposes validation and unavailable-price states while SRB calculates successfully', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $validCategory = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Referral')
        ->with('subCategories')
        // Tanpa ORDER BY, PostgreSQL bebas memilih baris mana pun. Tes yang
        // memilih baris berbeda tiap jalan gagal sesekali tanpa sebab terlihat.
        ->orderBy('id')
        ->firstOrFail();
    $validReferral = Referral::factory()->create([
        'category_id' => $validCategory->id,
        'sub_category_id' => $validCategory->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load('user');

    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 2)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    Livewire::actingAs($validReferral->user)
        ->test(CreditSimulation::class)
        ->call('calculate')
        ->assertHasErrors([
            'domicile_id' => 'required',
            'usage_id' => 'required',
            'model_id' => 'required',
            'vehicle_year' => 'required',
        ])
        ->assertHasNoErrors(['debtor_name', 'debtor_nik', 'debtor_birth_date'])
        ->assertCount('results', 5);

    $component = Livewire::actingAs($validReferral->user)
        ->test(CreditSimulation::class)
        ->set(validSimulationState($model, $price->year))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true);

    $price->update(['price' => 0]);

    $component
        ->call('calculate')
        ->assertSet('priceUnavailable', true)
        ->assertSet('hasCalculated', false)
        ->assertSee('Harga kendaraan tidak tersedia');

    $showroomCategory = ReferralCategory::query()
        ->where('code', 'SRB')
        ->with('subCategories')
        ->firstOrFail();
    $showroomReferral = Referral::factory()->create([
        'category_id' => $showroomCategory->id,
        'sub_category_id' => $showroomCategory->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load('user');
    $availablePrice = $model->prices()->where('price', '>', 0)->orderByDesc('year')->firstOrFail();

    Livewire::actingAs($showroomReferral->user)
        ->test(CreditSimulation::class)
        ->set(validSimulationState($model, $availablePrice->year))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertSet('calculationError', null)
        ->assertCount('results', 5);

    $this->actingAs($showroomReferral->user)
        ->get(route('simulation.print'))
        ->assertRedirect(route('simulation'))
        ->assertSessionHas('simulation_error', 'Lengkapi data calon debitur melalui tombol download pada hasil simulasi.');

    $this->actingAs($showroomReferral->user)
        ->get(route('simulation.print.download'))
        ->assertRedirect(route('simulation'))
        ->assertSessionHas('simulation_error', 'Lengkapi data calon debitur melalui tombol download pada hasil simulasi.');

    session()->forget('simulation.active');

    $this->actingAs($validReferral->user)
        ->get(route('simulation.print'))
        ->assertRedirect(route('simulation'));

    Carbon::setTestNow();
});

test('Captive Internal only exposes Passenger and rejects Commercial server-side', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('code', 'CIN')
        ->with('subCategories')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ]);
    $commercialUsage = VehicleUsage::query()->where('name', 'Commercial')->firstOrFail();
    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Commercial'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0))
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $model->prices->first();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $component = Livewire::actingAs($referral->user)->test(CreditSimulation::class);

    $component
        ->assertSee('Passenger')
        ->assertDontSee('Commercial');

    expect($component->instance()->usages()->pluck('name')->all())->toBe(['Passenger'])
        ->and(fn () => app(ProductResolver::class)->resolve($referral, DomainVehicleUsage::COMMERCIAL))
        ->toThrow(RuntimeException::class, "Penggunaan kendaraan Commercial tidak tersedia untuk kategori Referral 'Captive Internal'.");

    $component
        ->set(validSimulationState($model, $price->year))
        ->set('usage_id', (string) $commercialUsage->id)
        ->call('calculate')
        ->assertHasErrors(['usage_id' => 'in'])
        ->assertSet('hasCalculated', false);

    Carbon::setTestNow();
});

test('Mobil Bekas does not offer Commercial units and rejects them server-side', function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
    TestVehicleMaster::seed();

    $category = ReferralCategory::query()
        ->where('segment', 'Reguler')
        ->where('tier', 'Sales Dealer')
        ->with('subCategories')
        ->firstOrFail();
    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $category->subCategories->firstOrFail()->id,
        'institution_id' => null,
    ])->load(['user', 'category']);

    expect($category->allowedVehicleUsages())->toContain(DomainVehicleUsage::COMMERCIAL);

    $commercial = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Commercial'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0))
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
        ->orderBy('vehicle_models.id')
        ->firstOrFail();
    $price = $commercial->prices->first();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $component = Livewire::actingAs($referral->user)
        ->test(CreditSimulation::class)
        ->set('financing_type', 'UCF')
        ->set('mode', 'A');

    $offered = collect($component->instance()->usages())->pluck('name');
    expect($offered)->not->toContain('Commercial');

    $component
        ->set(validSimulationState($commercial, $price->year))
        ->set('market_price', '150000000')
        ->call('calculate')
        ->assertHasErrors('usage_id');

    Carbon::setTestNow();
});
