<?php

use App\Livewire\Admin\Configuration\AuditLog;
use App\Livewire\Admin\Configuration\Products;
use App\Models\AdminChangeLog;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;
use Livewire\Livewire;

/*
 * Riwayat Perubahan reads admin_change_logs, written by AuditsAdminChanges
 * (AD-17) on every config/master data change. This suite proves the reader,
 * not the writer — the writer already has coverage in
 * AdminConfigurationCrudTest.
 */

it('lists a real config change with its before and after values', function () {
    test()->seed(ReferralMasterSeeder::class);
    test()->seed(SimulationConfigurationSeeder::class);
    $admin = User::factory()->admin()->create(['username' => 'admin.audit']);
    $product = Product::query()->where('is_active', true)->firstOrFail();
    $newAdminMax = $product->admin_max + 1_000;

    Livewire::actingAs($admin)
        ->test(Products::class)
        ->call('edit', $product->id)
        ->set('form.admin_max', 'Rp '.number_format($newAdminMax, 0, ',', '.'))
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)
        ->test(AuditLog::class)
        ->assertSee('Product dan Upping')
        ->assertSee('Diubah')
        ->assertSee('admin.audit')
        ->assertSee('admin_max')
        ->assertSee((string) $newAdminMax);
});

it('filters entries by module', function () {
    $admin = User::factory()->admin()->create();

    AdminChangeLog::query()->create([
        'actor_id' => $admin->id,
        'actor_name' => $admin->username,
        'subject_type' => 'App\\Models\\Product',
        'subject_table' => 'products',
        'audit_module' => 'configuration.products',
        'subject_id' => 1,
        'action' => 'updated',
        'before_values' => ['admin_max' => 100],
        'after_values' => ['admin_max' => 200],
        'created_at' => now(),
    ]);

    AdminChangeLog::query()->create([
        'actor_id' => $admin->id,
        'actor_name' => $admin->username,
        'subject_type' => 'App\\Models\\Domicile',
        'subject_table' => 'domiciles',
        'audit_module' => 'master.lookups',
        'subject_id' => 1,
        'action' => 'created',
        'before_values' => null,
        'after_values' => ['name' => 'Jakarta Barat'],
        'created_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(AuditLog::class)
        ->set('module', 'configuration.products')
        ->assertSee('products #1')
        ->assertDontSee('domiciles #1');
});

it('filters entries by action', function () {
    $admin = User::factory()->admin()->create();

    AdminChangeLog::query()->create([
        'actor_id' => $admin->id,
        'actor_name' => $admin->username,
        'subject_type' => 'App\\Models\\Product',
        'subject_table' => 'products',
        'audit_module' => 'configuration.products',
        'subject_id' => 1,
        'action' => 'deleted',
        'before_values' => ['name' => 'Product Lama'],
        'after_values' => null,
        'created_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(AuditLog::class)
        ->assertSee('Dihapus')
        ->set('action', 'created')
        ->assertDontSee('Product Lama');
});

it('shows an empty state when no changes have been recorded', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(AuditLog::class)
        ->assertSee('Belum ada perubahan konfigurasi yang tercatat.');
});

it('refuses the audit log to referral and officer', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/configuration/audit')->assertForbidden();
})->with(['referral', 'accountOfficer']);
