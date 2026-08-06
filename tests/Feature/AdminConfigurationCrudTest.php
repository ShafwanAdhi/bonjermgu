<?php

use App\Livewire\Admin\Configuration\Defaults;
use App\Livewire\Admin\Configuration\Fees;
use App\Livewire\Admin\Configuration\Insurance;
use App\Livewire\Admin\Configuration\Products;
use App\Livewire\Admin\Master\Lookups;
use App\Livewire\Admin\Master\ReferralMaster;
use App\Livewire\Admin\Master\Vehicles;
use App\Models\AcpUpping;
use App\Models\AdminChangeLog;
use App\Models\AgeGroup;
use App\Models\Domicile;
use App\Models\FiduciaTier;
use App\Models\InsuranceCascoRate;
use App\Models\InsuranceExtensionRate;
use App\Models\Product;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\SimulationSetting;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Repositories\SimulationConfigurationRepository;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Database\Seeders\VehicleSeeder;
use Livewire\Livewire;

function seedConfigurationWithoutVehicles(): void
{
    test()->seed(ReferralMasterSeeder::class);
    test()->seed(SimulationConfigurationSeeder::class);
}

test('Admin manages complete Products and every change records actor and time', function () {
    seedConfigurationWithoutVehicles();
    $admin = User::factory()->admin()->create(['username' => 'admin.audit']);
    $product = Product::query()->where('is_active', true)->with('rates')->firstOrFail();
    $newAdminMax = $product->admin_max + 1_000;

    $component = Livewire::actingAs($admin)
        ->test(Products::class)
        ->call('edit', $product->id)
        ->set('form.admin_max', (string) $newAdminMax)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh()->admin_max)->toBe($newAdminMax)
        ->and(app(SimulationConfigurationRepository::class)->forProduct($product)->product->adminMax)
        ->toBe((float) $newAdminMax);

    $audit = AdminChangeLog::query()->where('subject_table', 'products')->latest('id')->firstOrFail();
    expect($audit->actor_id)->toBe($admin->id)
        ->and($audit->actor_name)->toBe('admin.audit')
        ->and($audit->action)->toBe('updated')
        ->and($audit->created_at)->not->toBeNull();

    $component
        ->set('form.rates', [12 => '', 24 => '', 36 => '', 48 => '', 60 => ''])
        ->call('save')
        ->assertHasErrors(['form.rates.12']);

    expect($product->rates()->whereNotNull('effective_rate')->exists())->toBeTrue();

    $component
        ->call('createProduct')
        ->set('form.name', 'Product CRUD Pengujian')
        ->set('form.rates.12', '10')
        ->set('form.dp_rate', '5')
        ->set('form.admin_min', '1000000')
        ->set('form.admin_max', '2000000')
        ->call('save')
        ->assertHasNoErrors()
        ->set('form.is_active', false)
        ->call('save')
        ->assertHasNoErrors()
        ->call('deleteProduct')
        ->assertHasNoErrors();

    expect(Product::query()->where('name', 'Product CRUD Pengujian')->exists())->toBeFalse()
        ->and(AdminChangeLog::query()->where('subject_table', 'products')->where('action', 'deleted')->exists())->toBeTrue();

    $requiredProduct = Product::query()->where('name', 'Reguler Commercial Referral')->firstOrFail();
    Livewire::actingAs($admin)
        ->test(Products::class)
        ->call('edit', $requiredProduct->id)
        ->set('form.is_active', false)
        ->call('save')
        ->assertHasErrors(['configuration']);

    expect($requiredProduct->fresh()->is_active)->toBeTrue();
});

test('Insurance Fee and Defaults reject incomplete data and roll it back', function () {
    seedConfigurationWithoutVehicles();
    $admin = User::factory()->admin()->create();
    $cascoCount = InsuranceCascoRate::query()->count();
    $originalExtension = InsuranceExtensionRate::query()->orderBy('code')->firstOrFail();

    $insurance = Livewire::actingAs($admin)->test(Insurance::class);
    $insurance
        ->set('extensionRates.0.rate', (string) (($originalExtension->rate * 100) + 0.01))
        ->call('save')
        ->assertHasNoErrors();

    expect($originalExtension->refresh()->rate)->not->toBe(0.0)
        ->and(AdminChangeLog::query()->where('subject_table', 'insurance_extension_rates')->exists())->toBeTrue();

    $insurance
        ->call('removeCascoBand', 1)
        ->call('save')
        ->assertHasErrors(['configuration']);

    expect(InsuranceCascoRate::query()->count())->toBe($cascoCount);

    $secondTier = FiduciaTier::query()->orderBy('min_amount')->skip(1)->firstOrFail();
    $originalMinimum = $secondTier->min_amount;
    Livewire::actingAs($admin)
        ->test(Fees::class)
        ->set('fiduciaTiers.1.min_amount', (string) ($originalMinimum + 1))
        ->call('save')
        ->assertHasErrors(['configuration']);

    expect($secondTier->refresh()->min_amount)->toBe($originalMinimum);

    $warranty = (int) SimulationSetting::query()->where('key', 'engine_warranty_fee')->value('value');
    $defaults = Livewire::actingAs($admin)
        ->test(Defaults::class)
        ->set('settings.engine_warranty_fee', (string) ($warranty + 1_000))
        ->call('save')
        ->assertHasNoErrors();

    expect((int) SimulationSetting::query()->where('key', 'engine_warranty_fee')->value('value'))
        ->toBe($warranty + 1_000);

    $defaults
        ->set('settings.tjh_step_amount', '0')
        ->call('save')
        ->assertHasErrors(['settings.tjh_step_amount']);
});

test('Admin manages domicile and age group together with mandatory ACP upping', function () {
    seedConfigurationWithoutVehicles();
    $admin = User::factory()->admin()->create();

    $component = Livewire::actingAs($admin)->test(Lookups::class);
    $domicileIndex = count($component->get('domiciles'));
    $ageIndex = count($component->get('ageGroups'));

    $component
        ->call('addDomicile')
        ->set("domiciles.{$domicileIndex}.name", 'Bogor Pengujian')
        ->call('addAgeGroup')
        ->set("ageGroups.{$ageIndex}.label", '61-65 tahun')
        ->set("ageGroups.{$ageIndex}.upping", '90')
        ->call('save')
        ->assertHasNoErrors();

    $group = AgeGroup::query()->where('label', '61-65 tahun')->firstOrFail();
    expect(Domicile::query()->where('name', 'Bogor Pengujian')->exists())->toBeTrue()
        ->and(AcpUpping::query()->where('age_group_id', $group->id)->value('upping'))->toBe(0.9)
        ->and(AdminChangeLog::query()->where('subject_table', 'age_groups')->where('actor_id', $admin->id)->exists())->toBeTrue();
});

test('Vehicle master edits only the selected cascade and audits PHPM changes', function () {
    $this->seed(VehicleSeeder::class);
    $admin = User::factory()->admin()->create();
    $model = VehicleModel::query()->whereHas('prices')->with(['type.brand.usage', 'prices'])->firstOrFail();
    $price = $model->prices->first();
    $newPrice = $price->price + 123;

    $component = Livewire::actingAs($admin)
        ->test(Vehicles::class)
        ->call('selectSearchResult', $model->id);

    expect($component->instance()->models()->count())->toBeLessThan(VehicleModel::query()->count());

    $priceIndex = collect($component->get('prices'))->search(fn ($row) => $row['id'] === $price->id);
    $component
        ->set("prices.{$priceIndex}.price", (string) $newPrice)
        ->call('savePrices')
        ->assertHasNoErrors();

    expect($price->refresh()->price)->toBe($newPrice)
        ->and(AdminChangeLog::query()->where('subject_table', 'vehicle_prices')->where('actor_id', $admin->id)->exists())->toBeTrue();

    $component
        ->call('newBrand')
        ->set('brandForm.name', 'MERK PENGUJIAN')
        ->set('brandForm.origin', 'Non Japan')
        ->call('saveBrand')
        ->assertHasNoErrors();

    expect(VehicleBrand::query()
        ->where('usage_id', $component->get('usageId'))
        ->where('name', 'MERK PENGUJIAN')
        ->exists())->toBeTrue();
});

test('Master Referral supports its hierarchy and refuses deleting a category in use', function () {
    seedConfigurationWithoutVehicles();
    $admin = User::factory()->admin()->create();
    $usedCategory = ReferralCategory::query()->whereHas('subCategories')->with('subCategories')->firstOrFail();
    $sub = $usedCategory->subCategories->first();
    $referralUser = User::factory()->referral()->create();
    Referral::query()->create([
        'user_id' => $referralUser->id,
        'full_name' => 'Referral Pengujian',
        'birth_date' => '1990-01-01',
        'nik' => '3173000000000999',
        'category_id' => $usedCategory->id,
        'sub_category_id' => $sub->id,
    ]);

    $component = Livewire::actingAs($admin)
        ->test(ReferralMaster::class)
        ->call('editCategory', $usedCategory->id)
        ->call('deleteCategory')
        ->assertHasErrors(['master']);

    expect($usedCategory->fresh())->not->toBeNull();

    $component
        ->call('newCategory')
        ->set('categoryForm.name', 'Kategori Pengujian')
        ->set('categoryForm.code', 'TST')
        ->set('categoryForm.segment', 'Reguler')
        ->set('categoryForm.tier', 'Referral')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $captiveInternal = ReferralCategory::query()->where('code', 'CIN')->firstOrFail();
    expect($captiveInternal->allows_passenger)->toBeTrue()
        ->and($captiveInternal->allows_commercial)->toBeFalse();

    $newCategory = ReferralCategory::query()->where('code', 'TST')->firstOrFail();
    $component
        ->call('newSubCategory', $newCategory->id)
        ->set('subCategoryForm.name', 'Sub Pengujian')
        ->call('saveSubCategory')
        ->assertHasNoErrors();

    $newSub = ReferralSubCategory::query()->where('category_id', $newCategory->id)->firstOrFail();
    $component
        ->call('newInstitution', $newSub->id)
        ->set('institutionForm.name', 'Instansi Pengujian')
        ->call('saveInstitution')
        ->assertHasNoErrors()
        ->call('deleteInstitution')
        ->assertHasNoErrors()
        ->call('editSubCategory', $newSub->id)
        ->call('deleteSubCategory')
        ->assertHasNoErrors()
        ->call('editCategory', $newCategory->id)
        ->set('categoryForm.is_active', false)
        ->call('saveCategory')
        ->assertHasNoErrors()
        ->call('deleteCategory')
        ->assertHasNoErrors();

    expect(ReferralCategory::query()->where('code', 'TST')->exists())->toBeFalse()
        ->and(AdminChangeLog::query()->where('subject_table', 'referral_categories')->where('action', 'deleted')->exists())->toBeTrue();
});
