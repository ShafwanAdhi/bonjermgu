<?php

use App\Enums\Role;
use App\Models\AccountOfficer;
use App\Models\Admin;
use App\Models\Referral;
use App\Models\User;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(ReferralMasterSeeder::class);
});

it('creates one account per role', function () {
    $this->seed(UserSeeder::class);

    expect(User::where('role', Role::Admin)->count())->toBe(1)
        ->and(User::where('role', Role::AccountOfficer)->count())->toBe(2)
        ->and(User::where('role', Role::Referral)->count())->toBe(3);
});

/* data-model.md section 7, rule 4: exactly one profile row per user. */
it('gives every user exactly one profile matching its role', function () {
    $this->seed(UserSeeder::class);

    foreach (User::all() as $user) {
        expect($user->profile()->count())->toBe(1);
    }

    expect(Admin::count())->toBe(1)
        ->and(AccountOfficer::count())->toBe(2)
        ->and(Referral::count())->toBe(3);
});

it('uses explicit development passwords without NIK', function () {
    $this->seed(UserSeeder::class);

    $budi = User::where('username', 'budisantoso')->firstOrFail();

    expect(Hash::check('ref-budisantoso', $budi->password))->toBeTrue()
        ->and($budi->referral->nik)->toBeNull();

    $ao = User::where('username', 'aorahmawati')->firstOrFail();

    expect(Hash::check('ao-rahmawati', $ao->password))->toBeTrue()
        ->and($ao->accountOfficer->nik)->toBeNull();
});

it('stores every password hashed, never readable', function () {
    $this->seed(UserSeeder::class);

    foreach (User::all() as $user) {
        expect($user->password)->toStartWith('$2y$');
    }
});

it('runs twice without duplicating anything', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::count())->toBe(6)
        ->and(Referral::count())->toBe(3)
        ->and(AccountOfficer::count())->toBe(2)
        ->and(Admin::count())->toBe(1);
});

it('points every referral at a cascade that actually exists', function () {
    $this->seed(UserSeeder::class);

    foreach (Referral::with(['category', 'subCategory', 'institution'])->get() as $referral) {
        expect($referral->category)->not->toBeNull()
            ->and($referral->subCategory)->not->toBeNull()
            ->and($referral->subCategory->category_id)->toBe($referral->category_id);

        if ($referral->institution_id !== null) {
            expect($referral->institution->sub_category_id)->toBe($referral->sub_category_id);
        }
    }
});

it('lets a seeded referral log in with the development password', function () {
    $this->seed(UserSeeder::class);

    expect(Auth::attempt(['username' => 'budisantoso', 'password' => 'ref-budisantoso']))->toBeTrue();
});

/*
 * Laravel already prompts before seeding in production, so this calls the
 * seeder directly — the point is that the guard inside it holds on its own,
 * without relying on someone answering a prompt correctly.
 */
it('creates nothing in production', function () {
    app()->detectEnvironment(fn () => 'production');

    (new UserSeeder)->run();

    expect(User::count())->toBe(0);
});
