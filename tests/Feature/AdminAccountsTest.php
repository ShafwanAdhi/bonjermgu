<?php

use App\Enums\Role;
use App\Livewire\Admin\Accounts\OfficerAccounts;
use App\Livewire\Admin\Accounts\ReferralAccounts;
use App\Models\AccountOfficer;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/* ------------------------------------------------------------ Akun Referral */

it('lists referral accounts from the database', function () {
    Referral::factory()->create(['full_name' => 'Budi Santoso']);
    Referral::factory()->create(['full_name' => 'Siti Nurhaliza']);

    $this->actingAs($this->admin)
        ->get('/accounts/referrals')
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('Siti Nurhaliza');
});

it('searches referral accounts on the server', function () {
    Referral::factory()->create(['full_name' => 'Budi Santoso']);
    Referral::factory()->create(['full_name' => 'Siti Nurhaliza']);

    Livewire::actingAs($this->admin)
        ->test(ReferralAccounts::class)
        ->set('search', 'Siti')
        ->assertSee('Siti Nurhaliza')
        ->assertDontSee('Budi Santoso');
});

it('lets admin edit a referral profile without requiring a NIK', function () {
    $referral = Referral::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ReferralAccounts::class)
        ->call('edit', $referral->id)
        ->set('full_name', 'Budi Diperbarui')
        ->call('save')
        ->assertHasNoErrors();

    expect($referral->fresh())
        ->full_name->toBe('Budi Diperbarui')
        ->nik->toBeNull();
});

it('can deactivate a referral account without touching its password', function () {
    $referral = Referral::factory()->create();
    $hashBefore = $referral->user->password;

    Livewire::actingAs($this->admin)
        ->test(ReferralAccounts::class)
        ->call('edit', $referral->id)
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($referral->user->fresh())
        ->is_active->toBeFalse()
        ->password->toBe($hashBefore);
});

/* ------------------------------------------------------------------ Akun AO */

it('creates an AO account and shows the generated password once', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('create')
        ->set('full_name', 'Dian Permata')
        ->set('birth_date', '1991-09-04')
        ->set('username', 'dian.permata')
        ->set('email', 'dian.permata@mtf.co.id')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('hanya ditampilkan sekali');

    $password = $component->get('initialPassword');

    expect($password)->toBeString()
        ->toHaveLength(config('account.initial_password.length'));

    $component->assertSee($password);

    $user = User::where('username', 'dian.permata')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::AccountOfficer)
        ->and(AccountOfficer::where('user_id', $user->id)->whereNull('nik')->exists())->toBeTrue();
});

it('stores the AO password as a hash, never readable', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('create')
        ->set('full_name', 'Dian Permata')
        ->set('birth_date', '1991-09-04')
        ->set('username', 'dian.permata')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('username', 'dian.permata')->first();
    $expected = $component->get('initialPassword');

    expect($user->password)->not->toBe($expected)
        ->and($user->password)->toStartWith('$2y$')
        ->and(Hash::check($expected, $user->password))->toBeTrue();
});

/*
 * Shown once means once. The next interaction with the component must not be
 * able to render it again — there is no recovery path (pages.md §19 item 2).
 */
it('clears the shown password on the next interaction', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('create')
        ->set('full_name', 'Dian Permata')
        ->set('birth_date', '1991-09-04')
        ->set('username', 'dian.permata')
        ->call('save');

    $password = $component->get('initialPassword');
    expect($password)->toBeString()->not->toBe('');

    $component->call('cancel')
        ->assertSet('initialPassword', null)
        ->assertDontSee($password);
});

it('lets a newly created AO log in with the shown password', function () {
    $component = Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('create')
        ->set('full_name', 'Dian Permata')
        ->set('birth_date', '1991-09-04')
        ->set('username', 'dian.permata')
        ->call('save')
        ->assertHasNoErrors();

    expect(Auth::attempt(['username' => 'dian.permata', 'password' => $component->get('initialPassword')]))->toBeTrue();
});

it('refuses a duplicate username when creating an AO', function () {
    User::factory()->create(['username' => 'dian.permata']);

    Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('create')
        ->set('full_name', 'Dian Permata')
        ->set('birth_date', '1991-09-04')
        ->set('username', 'dian.permata')
        ->call('save')
        ->assertHasErrors(['username' => 'unique']);

    expect(AccountOfficer::count())->toBe(0);
});

it('requires the mandatory AO fields', function () {
    Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors([
            'full_name' => 'required',
            'birth_date' => 'required',
            'username' => 'required',
        ]);

    expect(User::where('role', Role::AccountOfficer)->count())->toBe(0);
});

it('edits an AO without reissuing the password', function () {
    $officer = AccountOfficer::factory()->create();
    $hashBefore = $officer->user->password;

    Livewire::actingAs($this->admin)
        ->test(OfficerAccounts::class)
        ->call('edit', $officer->id)
        ->set('full_name', 'Andi Diperbarui')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('initialPassword', null);

    expect($officer->fresh())
        ->full_name->toBe('Andi Diperbarui')
        ->and($officer->user->fresh()->password)->toBe($hashBefore);
});

/* ---------------------------------------------------------------- Otorisasi */

it('refuses the account screens to referral and officer', function (string $path, string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get($path)->assertForbidden();
})->with(['/accounts/referrals', '/accounts/officers'])->with(['referral', 'accountOfficer']);
