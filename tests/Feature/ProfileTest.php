<?php

use App\Livewire\Profile\OfficerProfile;
use App\Livewire\Profile\ReferralProfile;
use App\Models\AccountOfficer;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\User;
use Livewire\Livewire;

it('shows the referral profile from the database, not placeholder data', function () {
    $referral = Referral::factory()->create(['full_name' => 'Budi Santoso']);

    $this->actingAs($referral->user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('NIK')
        ->assertSee($referral->user->username);
});

it('saves referral profile edits to the database', function () {
    $referral = Referral::factory()->create();

    Livewire::actingAs($referral->user)
        ->test(ReferralProfile::class)
        ->call('edit')
        ->set('full_name', 'Budi Santoso Baru')
        ->set('email', 'baru@example.test')
        ->set('phone', '081200000000')
        ->set('branch_name', 'KCP Kebon Jeruk')
        ->call('save')
        ->assertHasNoErrors();

    expect($referral->fresh())
        ->full_name->toBe('Budi Santoso Baru')
        ->email->toBe('baru@example.test')
        ->phone->toBe('081200000000')
        ->branch_name->toBe('KCP Kebon Jeruk');
});

it('does not expose account NIK on the referral profile form', function () {
    $referral = Referral::factory()->create();

    Livewire::actingAs($referral->user)
        ->test(ReferralProfile::class)
        ->call('edit')
        ->assertDontSee('NIK')
        ->set('full_name', 'Nama Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($referral->fresh())
        ->full_name->toBe('Nama Baru')
        ->nik->toBeNull();
});

it('rejects a sub-category from another category on the profile form', function () {
    $referral = Referral::factory()->create();

    $otherCategory = ReferralCategory::create([
        'name' => 'Kategori Lain', 'code' => 'LAI', 'segment' => 'Reguler',
        'tier' => 'Referral', 'is_active' => true,
    ]);

    $otherSub = ReferralSubCategory::create([
        'category_id' => $otherCategory->id,
        'name' => 'Sub Lain',
    ]);

    Livewire::actingAs($referral->user)
        ->test(ReferralProfile::class)
        ->call('edit')
        ->set('sub_category_id', (string) $otherSub->id)
        ->call('save')
        ->assertHasErrors('sub_category_id');

    expect($referral->fresh()->sub_category_id)->not->toBe($otherSub->id);
});

it('requires an institution when the chosen sub-category has one', function () {
    $referral = Referral::factory()->create();

    Institution::create([
        'sub_category_id' => $referral->sub_category_id,
        'name' => 'PT Contoh Sejahtera',
    ]);

    Livewire::actingAs($referral->user)
        ->test(ReferralProfile::class)
        ->call('edit')
        ->set('institution_id', null)
        ->call('save')
        ->assertHasErrors(['institution_id' => 'required']);
});

it('shows and saves the officer profile', function () {
    $officer = AccountOfficer::factory()->create(['full_name' => 'Andi Prasetyo']);

    $this->actingAs($officer->user)->get('/profile')->assertOk()->assertSee('Andi Prasetyo');

    Livewire::actingAs($officer->user)
        ->test(OfficerProfile::class)
        ->call('edit')
        ->set('full_name', 'Andi Prasetyo Baru')
        ->set('email', 'andi.baru@mtf.co.id')
        ->call('save')
        ->assertHasNoErrors();

    expect($officer->fresh())
        ->full_name->toBe('Andi Prasetyo Baru')
        ->email->toBe('andi.baru@mtf.co.id');
});

it('refuses the profile page to admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/profile')
        ->assertForbidden();
});

it('rejects an invalid email on either profile', function () {
    $referral = Referral::factory()->create();

    Livewire::actingAs($referral->user)
        ->test(ReferralProfile::class)
        ->call('edit')
        ->set('email', 'bukan-email')
        ->call('save')
        ->assertHasErrors(['email' => 'email']);
});
