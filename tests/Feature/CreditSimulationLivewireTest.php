<?php

use App\Domain\Simulation\VehicleUsage as DomainVehicleUsage;
use App\Livewire\Simulation\CreditSimulation;
use App\Models\AgeGroup;
use App\Models\Domicile;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\VehicleModel;
use App\Models\VehicleUsage;
use App\Repositories\ProductResolver;
use Carbon\Carbon;
use Livewire\Livewire;

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
    $this->seed();

    $referral = Referral::query()
        ->whereHas('category', fn ($query) => $query
            ->where('segment', 'Reguler')
            ->where('tier', 'Referral'))
        ->with(['user', 'category'])
        ->firstOrFail();
    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 2)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
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
        ->set('desired_amount', (string) intdiv($price->price, 2))
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true);

    $dtnModeB = $component->get('results')[0]['disbursement'];
    expect($component->instance()->disbursementHeading())->toBe('Pencairan')
        ->and($dtnModeB)->not->toBe($dtnModeA);

    $component
        ->set('financing_type', 'UCF')
        ->set('mode', 'A')
        ->set('market_price', (string) $price->price)
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('hasCalculated', true)
        ->assertCount('results', 5);

    $ucfModeA = $component->get('results')[0]['disbursement'];
    expect($component->instance()->disbursementHeading())->toBe('Pencairan All In')
        ->and($ucfModeA)->not->toBe($dtnModeA);

    $component
        ->set('mode', 'B')
        ->set('desired_amount', (string) round($price->price * 0.6))
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
        ->and($download->getContent())->toContain($ucfModeB);

    Carbon::setTestNow();
});

test('simulation exposes validation and unavailable-price states while SRB calculates successfully', function () {
    $this->seed();

    $validReferral = Referral::query()
        ->whereHas('category', fn ($query) => $query
            ->where('segment', 'Reguler')
            ->where('tier', 'Referral'))
        ->with('user')
        ->firstOrFail();
    $model = VehicleModel::query()
        ->whereHas('type.brand.usage', fn ($query) => $query->where('name', 'Passenger'))
        ->whereHas('prices', fn ($query) => $query->where('price', '>', 0), '>=', 2)
        ->with(['type.brand.usage', 'prices' => fn ($query) => $query->where('price', '>', 0)->orderByDesc('year')])
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

    $showroomReferral = Referral::query()
        ->whereHas('category', fn ($query) => $query->where('code', 'SRB'))
        ->with('user')
        ->firstOrFail();
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
    $this->seed();

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
