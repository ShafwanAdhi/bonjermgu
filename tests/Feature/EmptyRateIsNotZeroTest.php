<?php

use App\Livewire\Admin\Configuration\Products;
use App\Models\Product;
use App\Models\User;
use App\Repositories\SimulationConfigurationRepository;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Livewire\Livewire;

/*
 * CLAUDE.md rule 3 and larangan 3 in architecture.md section 21.
 *
 * An empty tenor rate means the tenor IS NOT AVAILABLE. Zero percent means the
 * tenor IS available at a zero rate. They are different facts and the system
 * must never collapse one into the other.
 *
 * The failure this guards against is silent: treating NULL as 0 produces a
 * plausible-looking instalment for a tenor that should have produced nothing
 * at all. Nobody notices by reading the screen.
 */

beforeEach(function () {
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);

    $this->admin = User::factory()->admin()->create();
    $this->product = Product::query()->where('is_active', true)->with('rates')->firstOrFail();
});

it('stores an emptied tenor rate as NULL, not as zero', function () {
    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id)
        ->set('form.rates.60', '')
        ->call('save')
        ->assertHasNoErrors();

    $rate = $this->product->rates()->where('tenor_months', 60)->first();

    expect($rate->effective_rate)->toBeNull()
        ->and($rate->effective_rate)->not->toBe(0.0);
});

it('stores an explicit zero as zero, not as NULL', function () {
    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id)
        ->set('form.rates.60', '0')
        ->call('save')
        ->assertHasNoErrors();

    $rate = $this->product->rates()->where('tenor_months', 60)->first();

    expect($rate->effective_rate)->not->toBeNull()
        ->and((float) $rate->effective_rate)->toBe(0.0);
});

/* The round trip is where a naive `?: null` would quietly destroy the zero. */
it('keeps empty and zero distinguishable through a save and reload', function () {
    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id)
        ->set('form.rates.48', '')
        ->set('form.rates.60', '0')
        ->call('save')
        ->assertHasNoErrors();

    $reloaded = Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id);

    // Empty comes back empty; zero comes back as a zero the user can see.
    expect($reloaded->get('form.rates')[48])->toBe('');
    expect((float) $reloaded->get('form.rates')[60])->toBe(0.0);

    $rates = $this->product->rates()->pluck('effective_rate', 'tenor_months');

    expect($rates[48])->toBeNull()
        ->and($rates[60])->not->toBeNull();
});

/*
 * The consequence that matters. An unavailable tenor must produce nothing on
 * every component — not a zero-interest instalment (CLAUDE.md rule 4).
 */
it('produces no financing for a tenor whose rate is empty', function () {
    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id)
        ->set('form.rates.60', '')
        ->call('save')
        ->assertHasNoErrors();

    $config = app(SimulationConfigurationRepository::class)->forProduct($this->product->refresh());

    expect($config->product->effectiveRateFor(60))->toBeNull()
        ->and($config->product->effectiveRateFor(12))->not->toBeNull();
});

/*
 * pages.md §12: the interface has to show the difference, not merely store it.
 * A correct database with an ambiguous screen still misleads the Admin.
 */
it('shows empty and zero differently on the product list', function () {
    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id)
        ->set('form.rates.48', '')
        ->set('form.rates.60', '0')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->assertSee('48: kosong')
        ->assertSee('60: 0,0%');
});

it('refuses to leave a product with no available tenor at all', function () {
    Livewire::actingAs($this->admin)
        ->test(Products::class)
        ->call('edit', $this->product->id)
        ->set('form.rates', [12 => '', 24 => '', 36 => '', 48 => '', 60 => ''])
        ->call('save')
        ->assertHasErrors(['form.rates.12']);

    // Nothing was wiped by the rejected save.
    expect($this->product->rates()->whereNotNull('effective_rate')->exists())->toBeTrue();
});
