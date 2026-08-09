<?php

use App\Enums\Role;
use App\Livewire\Auth\Register;
use App\Models\Institution;
use App\Models\Referral;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->category = ReferralCategory::create([
        'name' => 'Karyawan Internal & Captive',
        'code' => 'KIN',
        'segment' => 'Reguler',
        'tier' => 'Referral',
        'is_active' => true,
    ]);

    $this->subCategory = ReferralSubCategory::create([
        'category_id' => $this->category->id,
        'name' => 'BUMN',
    ]);

    $this->institution = Institution::create([
        'sub_category_id' => $this->subCategory->id,
        'name' => 'PT Bank Mandiri (Persero) Tbk',
    ]);

    // A second branch of the tree, used to prove the cascade is validated
    // server-side rather than trusted from the client.
    $this->otherCategory = ReferralCategory::create([
        'name' => 'Komunitas Otomotif',
        'code' => 'KMO',
        'segment' => 'Reguler',
        'tier' => 'Referral',
        'is_active' => true,
    ]);

    $this->otherSubCategory = ReferralSubCategory::create([
        'category_id' => $this->otherCategory->id,
        'name' => 'Klub Mobil',
    ]);
});

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Budi Santoso',
        'birth_date' => '1990-08-12',
        'email' => 'budi.santoso@gmail.com',
        'phone' => '081290347712',
        'username' => 'budisantoso',
        'password' => 'rahasia-pilihan-referral',
        'password_confirmation' => 'rahasia-pilihan-referral',
        'branch_name' => 'KCP Jakarta Kebon Jeruk',
    ], $overrides);
}

it('renders the registration page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Registrasi Referral')
        ->assertSee('Kembali')
        ->assertSee('Contoh: Budi Santoso')
        ->assertSee('Contoh: 081234567890')
        ->assertSee('Contoh: budi_santoso')
        ->assertSee('Nama user ini akan digunakan untuk proses login nanti.')
        ->assertSee('Minimal 8 karakter')
        ->assertSee('Tampilkan kata sandi')
        ->assertSee('Karyawan Internal')
        ->assertDontSee('Karyawan Internal & Captive');
});

it('creates an active referral account with the password chosen by the referral', function () {
    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->set('institution_id', $this->institution->id)
        ->call('register')
        ->assertHasNoErrors()
        ->assertSet('registered', true)
        ->assertSet('password', '')
        ->assertSet('password_confirmation', '')
        ->assertSee('Gunakan nama user dan kata sandi')
        ->assertDontSee('Kata sandi awal');

    $user = User::where('username', 'budisantoso')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Referral)
        ->and($user->is_active)->toBeTrue();

    $referral = Referral::where('user_id', $user->id)->first();

    expect($referral->full_name)->toBe('Budi Santoso')
        ->and($referral->nik)->toBeNull()
        ->and($referral->category_id)->toBe($this->category->id)
        ->and($referral->sub_category_id)->toBe($this->subCategory->id)
        ->and($referral->institution_id)->toBe($this->institution->id)
        ->and($referral->branch_name)->toBe('KCP Jakarta Kebon Jeruk');

    expect(Hash::check('rahasia-pilihan-referral', $user->password))->toBeTrue();
});

it('stores the password as a hash, never as readable text', function () {
    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->set('institution_id', $this->institution->id)
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('username', 'budisantoso')->first();
    $expected = 'rahasia-pilihan-referral';

    expect($user->password)->not->toBe($expected)
        ->and($user->password)->toStartWith('$2y$')
        ->and(Hash::check($expected, $user->password))->toBeTrue();
});

it('rejects a duplicate username', function () {
    User::factory()->create(['username' => 'budisantoso']);

    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->set('institution_id', $this->institution->id)
        ->call('register')
        ->assertHasErrors(['username' => 'unique']);
});

it('requires the mandatory fields', function () {
    Livewire::test(Register::class)
        ->call('register')
        ->assertHasErrors([
            'full_name' => 'required',
            'birth_date' => 'required',
            'phone' => 'required',
            'username' => 'required',
            'password' => 'required',
            'category_id' => 'required',
            'sub_category_id' => 'required',
        ]);

    expect(User::count())->toBe(0);
});

it('requires the password confirmation to match', function () {
    Livewire::test(Register::class)
        ->fill(validRegistration([
            'password_confirmation' => 'password-yang-berbeda',
        ]))
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->set('institution_id', $this->institution->id)
        ->call('register')
        ->assertHasErrors(['password' => 'confirmed']);

    expect(User::count())->toBe(0);
});

/*
 * The cascade is a server-side constraint, not a UI convenience. A client that
 * posts a sub-category from a different category must be refused.
 */
it('rejects a sub-category belonging to another category', function () {
    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->otherSubCategory->id)
        ->call('register')
        ->assertHasErrors(['sub_category_id']);

    expect(User::count())->toBe(0);
});

it('rejects an institution belonging to another sub-category', function () {
    $foreign = Institution::create([
        'sub_category_id' => $this->otherSubCategory->id,
        'name' => 'Klub Sedan Klasik',
    ]);

    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->set('institution_id', $foreign->id)
        ->call('register')
        ->assertHasErrors(['institution_id']);

    expect(User::count())->toBe(0);
});

it('accepts an empty institution when the sub-category has none', function () {
    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->otherCategory->id)
        ->set('sub_category_id', $this->otherSubCategory->id)
        ->call('register')
        ->assertHasNoErrors();

    expect(Referral::first()->institution_id)->toBeNull();
});

it('requires an institution when the sub-category has one', function () {
    Livewire::test(Register::class)
        ->fill(validRegistration())
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->call('register')
        ->assertHasErrors(['institution_id' => 'required']);
});

it('resets the dependent selects when the category changes', function () {
    Livewire::test(Register::class)
        ->set('category_id', $this->category->id)
        ->set('sub_category_id', $this->subCategory->id)
        ->set('institution_id', $this->institution->id)
        ->set('category_id', $this->otherCategory->id)
        ->assertSet('sub_category_id', null)
        ->assertSet('institution_id', null);
});

it('only offers sub-categories of the selected category', function () {
    Livewire::test(Register::class)
        ->set('category_id', $this->category->id)
        ->assertSee('BUMN')
        ->assertDontSee('Klub Mobil');
});

it('does not ship the institution list before a sub-category is chosen', function () {
    Livewire::test(Register::class)
        ->set('category_id', $this->category->id)
        ->assertDontSee('PT Bank Mandiri (Persero) Tbk');
});

it('keeps Others at the bottom of the registration dropdown cascade', function () {
    $zebraCategory = ReferralCategory::create([
        'name' => 'Zebra Auto',
        'code' => 'ZBR',
        'segment' => 'Reguler',
        'tier' => 'Referral',
        'is_active' => true,
    ]);

    $othersCategory = ReferralCategory::create([
        'name' => 'Others',
        'code' => 'OTH',
        'segment' => 'Reguler',
        'tier' => 'Referral',
        'is_active' => true,
    ]);

    $alphaSubCategory = ReferralSubCategory::create([
        'category_id' => $zebraCategory->id,
        'name' => 'Alpha Dealer',
    ]);

    $zuluSubCategory = ReferralSubCategory::create([
        'category_id' => $zebraCategory->id,
        'name' => 'Zulu Dealer',
    ]);

    $othersSubCategory = ReferralSubCategory::create([
        'category_id' => $zebraCategory->id,
        'name' => 'Others',
    ]);

    Institution::create([
        'sub_category_id' => $alphaSubCategory->id,
        'name' => 'Mandiri Alpha',
    ]);

    Institution::create([
        'sub_category_id' => $alphaSubCategory->id,
        'name' => 'Zeta Finance',
    ]);

    Institution::create([
        'sub_category_id' => $alphaSubCategory->id,
        'name' => 'Others',
    ]);

    $component = Livewire::test(Register::class);

    expect($component->instance()->categories()->pluck('name')->all())
        ->toBe([
            'Karyawan Internal & Captive',
            'Komunitas Otomotif',
            'Zebra Auto',
            'Others',
        ]);

    $component->set('category_id', $zebraCategory->id);

    expect($component->instance()->subCategories()->pluck('name')->all())
        ->toBe([
            'Alpha Dealer',
            'Zulu Dealer',
            'Others',
        ]);

    $component->set('sub_category_id', $alphaSubCategory->id);

    expect($component->instance()->institutions()->pluck('name')->all())
        ->toBe([
            'Mandiri Alpha',
            'Zeta Finance',
            'Others',
        ]);

    expect($othersCategory->name)->toBe('Others');
});
