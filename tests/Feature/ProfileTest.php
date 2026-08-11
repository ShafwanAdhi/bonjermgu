<?php

use App\Livewire\Profile\OfficerProfile;
use App\Livewire\Profile\AdminProfile;
use App\Livewire\Profile\ReferralProfile;
use App\Models\AccountOfficer;
use App\Models\Admin;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        ->set('birth_date', '1991-02-03')
        ->set('email', 'baru@example.test')
        ->set('phone', '081200000000')
        ->set('branch_name', 'KCP Kebon Jeruk')
        ->call('save')
        ->assertHasNoErrors();

    $updatedReferral = $referral->fresh();
    expect($updatedReferral)
        ->full_name->toBe('Budi Santoso Baru')
        ->email->toBe('baru@example.test')
        ->phone->toBe('081200000000')
        ->branch_name->toBe('KCP Kebon Jeruk');
    expect($updatedReferral->birth_date?->format('Y-m-d'))->toBe('1991-02-03');
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
        ->set('birth_date', '1992-04-05')
        ->set('email', 'andi.baru@mtf.co.id')
        ->call('save')
        ->assertHasNoErrors();

    $updatedOfficer = $officer->fresh();
    expect($updatedOfficer)
        ->full_name->toBe('Andi Prasetyo Baru')
        ->email->toBe('andi.baru@mtf.co.id');
    expect($updatedOfficer->birth_date?->format('Y-m-d'))->toBe('1992-04-05');
});

it('lets an officer change their own password from profile', function () {
    $officer = AccountOfficer::factory()->create();

    Livewire::actingAs($officer->user)
        ->test(OfficerProfile::class)
        ->set('current_password', 'password')
        ->set('password', 'password-baru-aman')
        ->set('password_confirmation', 'password-baru-aman')
        ->call('changePassword')
        ->assertHasNoErrors()
        ->assertSet('current_password', '')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '');

    expect(Hash::check('password-baru-aman', $officer->user->fresh()->password))->toBeTrue();
});

it('rejects an officer password change when the current password is wrong', function () {
    $officer = AccountOfficer::factory()->create();
    $hashBefore = $officer->user->password;

    Livewire::actingAs($officer->user)
        ->test(OfficerProfile::class)
        ->set('current_password', 'salah')
        ->set('password', 'password-baru-aman')
        ->set('password_confirmation', 'password-baru-aman')
        ->call('changePassword')
        ->assertHasErrors('current_password');

    expect($officer->user->fresh()->password)->toBe($hashBefore);
});

it('refuses the profile page to admin', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/profile')
        ->assertForbidden();
});

it('shows and saves the admin account profile from the accounts module', function () {
    $admin = Admin::factory()->create(['full_name' => 'Administrator Lama']);

    $this->actingAs($admin->user)
        ->get('/accounts/profile')
        ->assertOk()
        ->assertSee('Administrator Lama')
        ->assertSee($admin->user->username);

    Livewire::actingAs($admin->user)
        ->test(AdminProfile::class)
        ->call('edit')
        ->set('full_name', 'Administrator Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->fresh()->full_name)->toBe('Administrator Baru');
});

it('lets an admin change their own password from the accounts module', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin->user)
        ->test(AdminProfile::class)
        ->set('current_password', 'password')
        ->set('password', 'password-admin-baru')
        ->set('password_confirmation', 'password-admin-baru')
        ->call('changePassword')
        ->assertHasNoErrors()
        ->assertSet('current_password', '')
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '');

    expect(Hash::check('password-admin-baru', $admin->user->fresh()->password))->toBeTrue();
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
