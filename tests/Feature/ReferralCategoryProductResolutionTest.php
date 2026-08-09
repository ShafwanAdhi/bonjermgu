<?php

use App\Domain\Simulation\VehicleUsage;
use App\Livewire\Admin\Master\ReferralMaster;
use App\Models\Product;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\User;
use App\Repositories\ProductResolver;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
 * Guards the category -> Product mapping.
 *
 * The Product name is built from `segment + penggunaan + tier`. If a category
 * carries a tier no Product is named after, ProductResolver throws and that
 * Referral cannot run a simulation at all — not a wrong number, no simulation.
 * These tests exist so that failure surfaces here rather than in front of a
 * Referral with a customer waiting.
 *
 * `Referral C2C` is retired by client decision: it is the same category as
 * `Referral`, with the same pricing and the same Product. See
 * docs/master-data-extraction.md.
 */

beforeEach(function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
});

function subCategoryIdFor(ReferralCategory $category): int
{
    return $category->subCategories()->value('id')
        ?? $category->subCategories()->create(['name' => "Sub Uji {$category->code}"])->id;
}

/* --------------------------------------------------- Invariant utama */

/**
 * The invariant that matters: every active category, for every vehicle usage
 * it allows, must resolve to an existing active Product.
 */
it('resolves an active product for every active category and allowed usage', function () {
    $resolver = app(ProductResolver::class);
    $categories = ReferralCategory::where('is_active', true)->get();

    expect($categories)->not->toBeEmpty();

    foreach ($categories as $category) {
        $usages = array_filter([
            $category->allows_passenger ? VehicleUsage::PASSENGER : null,
            $category->allows_commercial ? VehicleUsage::COMMERCIAL : null,
        ]);

        expect($usages)->not->toBeEmpty("Kategori {$category->name} tidak mengizinkan penggunaan apa pun.");

        foreach ($usages as $usage) {
            $referral = Referral::factory()->create([
                'category_id' => $category->id,
                'sub_category_id' => subCategoryIdFor($category),
            ]);

            $expectedName = $resolver->nameFor($category, $usage);

            $product = $resolver->resolve($referral, $usage);

            expect($product->name)->toBe(
                $expectedName,
                "Kategori {$category->name} + {$usage->value} seharusnya memakai Product {$expectedName}."
            );
            expect($product->is_active)->toBeTrue();
        }
    }
});

/* ------------------------------------------- Referral C2C sudah pensiun */

it('carries no category on the retired Referral C2C tier', function () {
    expect(ReferralCategory::where('tier', 'Referral C2C')->exists())->toBeFalse();
});

it('carries no product named after the retired tier', function () {
    expect(Product::where('name', 'like', '%Referral C2C%')->exists())->toBeFalse();
});

/**
 * The canonical tier resolves to the plain `Referral` products for both
 * vehicle usages — no C2C-specific product and no rate copied between them.
 */
it('maps the canonical Referral tier to the plain Referral products', function () {
    $category = ReferralCategory::where('code', 'SRB')->firstOrFail();

    expect($category->tier)->toBe('Referral');

    $resolver = app(ProductResolver::class);

    expect($resolver->nameFor($category, VehicleUsage::PASSENGER))->toBe('Reguler Passenger Referral')
        ->and($resolver->nameFor($category, VehicleUsage::COMMERCIAL))->toBe('Reguler Commercial Referral');

    foreach (['Reguler Passenger Referral', 'Reguler Commercial Referral'] as $name) {
        expect(Product::where('name', $name)->where('is_active', true)->exists())->toBeTrue();
    }
});

/* The database refuses the retired value outright. */
it('rejects the retired tier at the database level', function () {
    DB::table('referral_categories')->insert([
        'name' => 'Kategori Uji C2C',
        'code' => 'UJC',
        'segment' => 'Reguler',
        'tier' => 'Referral C2C',
        'allows_passenger' => true,
        'allows_commercial' => true,
        'is_active' => true,
    ]);
})->throws(QueryException::class);

it('rejects the retired tier on the master referral screen with a readable message', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ReferralMaster::class)
        ->call('newCategory')
        ->set('categoryForm.name', 'Kategori Uji')
        ->set('categoryForm.code', 'UJI')
        ->set('categoryForm.segment', 'Reguler')
        ->set('categoryForm.tier', 'Referral C2C')
        ->set('categoryForm.allows_passenger', true)
        ->set('categoryForm.allows_commercial', true)
        ->set('categoryForm.is_active', true)
        ->call('saveCategory')
        ->assertHasErrors('categoryForm.tier')
        ->assertSee('sudah tidak digunakan');

    expect(ReferralCategory::where('code', 'UJI')->exists())->toBeFalse();
});

/* ------------------------------------------------- Kegagalan yang jelas */

/**
 * A tier no Product is named after must fail loudly at resolve time. Silently
 * falling back to another Product would price a contract from the wrong rate
 * table, and nobody would notice.
 */
it('refuses to guess when a tier has no matching product', function () {
    $category = ReferralCategory::create([
        'name' => 'Kategori Tanpa Product',
        'code' => 'XXX',
        'segment' => 'Reguler',
        'tier' => 'Tier Yang Tidak Ada',
        'allows_passenger' => true,
        'allows_commercial' => true,
        'is_active' => true,
    ]);

    $sub = $category->subCategories()->create(['name' => 'Sub Uji']);

    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => $sub->id,
    ]);

    app(ProductResolver::class)->resolve($referral, VehicleUsage::PASSENGER);
})->throws(RuntimeException::class);

it('refuses a usage the category does not allow', function () {
    $category = ReferralCategory::where('code', 'CIN')->firstOrFail();

    expect($category->allows_commercial)->toBeFalse();

    $referral = Referral::factory()->create([
        'category_id' => $category->id,
        'sub_category_id' => subCategoryIdFor($category),
    ]);

    app(ProductResolver::class)->resolve($referral, VehicleUsage::COMMERCIAL);
})->throws(RuntimeException::class);
