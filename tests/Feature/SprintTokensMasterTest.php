<?php

use App\Livewire\Admin\Master\SprintTokens;
use App\Models\SprintToken;
use App\Models\User;
use Livewire\Livewire;

/*
 * Token SPRINT adalah ejaan Product ID dan Product Offering. Suntingan di sini
 * tidak pernah menyentuh perhitungan, tapi bisa membuat View Sprint diam-diam
 * berhenti menyusun kode — itu yang dijaga tes di bawah.
 */

it('refuses the SPRINT token master to everyone except Admin', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/master/sprint-tokens')->assertForbidden();
})->with(['referral', 'accountOfficer']);

it('lets Admin rename a token', function () {
    $this->actingAs(User::factory()->admin()->create());

    $index = collect(SprintToken::grouped()['product'])->search(fn ($row) => $row->source === 'Sale & Leaseback');

    Livewire::test(SprintTokens::class)
        ->set("groups.product.{$index}.offering_token", 'KMK SLB')
        ->call('save')
        ->assertHasNoErrors();

    expect(SprintToken::query()->where('source', 'Sale & Leaseback')->value('offering_token'))->toBe('KMK SLB');
});

/*
 * Kosongkan token yang dipakai Product ID dan kode itu tidak akan pernah
 * tersusun lagi, tanpa satu pun pesan di layar AO.
 */
it('refuses to blank a token that a code segment depends on', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SprintTokens::class)
        ->set('groups.product.0.product_token', '')
        ->call('save')
        ->assertHasErrors('groups.product.0.product_token');
});

it('accepts a blank token where the code has no segment for it', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SprintTokens::class)
        ->set('groups.dp.0.product_token', '')
        ->call('save')
        ->assertHasNoErrors();
});

/*
 * Sub kategori yang menunjuk kanal tidak ada membuat dropdown Kanal berhenti
 * terisi di muka, dan AO tidak diberi tahu apa pun.
 */
it('refuses a sub-category pointing at a channel that does not exist', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SprintTokens::class)
        ->set('groups.channel_source.0.offering_token', 'Kanal Karangan')
        ->call('save')
        ->assertHasErrors('groups.channel_source.0.offering_token');
});

it('refuses two identical choices inside one group', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SprintTokens::class)
        ->set('groups.region.0.source', 'Jawa')
        ->set('groups.region.1.source', 'Jawa')
        ->call('save')
        ->assertHasErrors('groups.region.0.source');
});

it('keeps a token added here reachable from View Sprint', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SprintTokens::class)
        ->call('addToken', 'region')
        ->set('groups.region.2.source', 'Bali')
        ->set('groups.region.2.offering_token', 'BALI')
        ->call('save')
        ->assertHasNoErrors();

    expect(collect(SprintToken::grouped()['region'])->pluck('source')->all())
        ->toContain('Bali');
});
