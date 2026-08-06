<?php

use App\Models\AccountOfficer;
use App\Models\Admin;
use App\Models\Application;
use App\Models\Referral;
use App\Models\Scopes\ApplicationVisibilityScope;
use Database\Seeders\DocumentRequirementSeeder;
use Database\Seeders\TrackingStageSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

/*
 * The security gate — AD-09.
 *
 * Every test here is written from the side that should be REFUSED. A suite
 * that only proves the allowed path passes happily while the door stands open.
 *
 * Two layers, tested separately:
 *   the global scope decides which rows exist for a user
 *   the policy decides what they may do with one
 */

beforeEach(function () {
    $this->seed(DocumentRequirementSeeder::class);
    $this->seed(TrackingStageSeeder::class);

    $this->officerA = AccountOfficer::factory()->create();
    $this->officerB = AccountOfficer::factory()->create();
    $this->referralX = Referral::factory()->create();
    $this->referralY = Referral::factory()->create();

    // Written past the scope so the fixtures exist regardless of who is acting.
    $this->ownedByA = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
        ->create(applicationAttributes($this->officerA->id, $this->referralX->id));

    $this->ownedByB = Application::withoutGlobalScope(ApplicationVisibilityScope::class)
        ->create(applicationAttributes($this->officerB->id, $this->referralY->id));
});

function applicationAttributes(int $officerId, int $referralId): array
{
    return [
        'account_officer_id' => $officerId,
        'referral_id' => $referralId,
        'financing_product' => 'DTN',
        'debtor_name' => fake()->name(),
        'debtor_nik' => fake()->unique()->numerify('317305##########'),
        'debtor_birth_date' => '1988-05-05',
        'debtor_type' => 'Perorangan Non Wiraswasta',
        'spouse_income_type' => 'Tidak Ada',
        'unit_count' => 1,
    ];
}

/* ------------------------------------------------------------ Global scope */

it('hides another officer application from an officer', function () {
    $this->actingAs($this->officerA->user);

    expect(Application::count())->toBe(1)
        ->and(Application::first()->is($this->ownedByA))->toBeTrue()
        ->and(Application::where('code', $this->ownedByB->code)->exists())->toBeFalse();
});

it('hides applications a referral did not bring in', function () {
    $this->actingAs($this->referralX->user);

    expect(Application::count())->toBe(1)
        ->and(Application::first()->is($this->ownedByA))->toBeTrue();
});

/*
 * The rule people get wrong most often. Admin does not get everything — Admin
 * gets nothing. The only exception is the Lending aggregate.
 */
it('gives admin an empty set, not every application', function () {
    $this->actingAs(Admin::factory()->create()->user);

    expect(Application::count())->toBe(0)
        ->and(Application::get())->toBeEmpty();
});

it('returns nothing at all when no one is signed in', function () {
    expect(Application::count())->toBe(0);
});

/*
 * A query that forgets to filter still must not leak. This is the whole point
 * of enforcing on the query rather than in the controller.
 */
it('protects an unfiltered query', function () {
    $this->actingAs($this->officerA->user);

    $everything = Application::query()->get();

    expect($everything)->toHaveCount(1)
        ->and($everything->first()->is($this->ownedByA))->toBeTrue();
});

it('raises not found rather than returning another officer application', function () {
    $this->actingAs($this->officerA->user);

    Application::where('code', $this->ownedByB->code)->firstOrFail();
})->throws(ModelNotFoundException::class);

/* ------------------------------------------------------------------ Policy */

it('refuses update of another officer application', function () {
    $this->actingAs($this->officerA->user);

    expect(Auth::user()->can('update', $this->ownedByB))->toBeFalse()
        ->and(Auth::user()->can('view', $this->ownedByB))->toBeFalse();
});

it('allows the owning officer to update', function () {
    $this->actingAs($this->officerA->user);

    expect(Auth::user()->can('view', $this->ownedByA))->toBeTrue()
        ->and(Auth::user()->can('update', $this->ownedByA))->toBeTrue();
});

/*
 * Referral passes the scope and is refused the write. Seeing is allowed,
 * changing is not — docs/actors.md section 2.
 */
it('lets a referral view but never update its own application', function () {
    $this->actingAs($this->referralX->user);

    expect(Auth::user()->can('view', $this->ownedByA))->toBeTrue()
        ->and(Auth::user()->can('update', $this->ownedByA))->toBeFalse()
        ->and(Auth::user()->can('updateDocuments', $this->ownedByA))->toBeFalse()
        ->and(Auth::user()->can('updateTracking', $this->ownedByA))->toBeFalse();
});

it('refuses a referral any access to an application it did not bring in', function () {
    $this->actingAs($this->referralX->user);

    expect(Auth::user()->can('view', $this->ownedByB))->toBeFalse()
        ->and(Auth::user()->can('update', $this->ownedByB))->toBeFalse();
});

it('refuses admin every application ability', function () {
    $this->actingAs(Admin::factory()->create()->user);

    expect(Auth::user()->can('viewAny', Application::class))->toBeFalse()
        ->and(Auth::user()->can('view', $this->ownedByA))->toBeFalse()
        ->and(Auth::user()->can('update', $this->ownedByA))->toBeFalse()
        ->and(Auth::user()->can('create', Application::class))->toBeFalse();
});

it('refuses creation to everyone except an officer', function () {
    $this->actingAs($this->referralX->user);
    expect(Auth::user()->can('create', Application::class))->toBeFalse();

    $this->actingAs($this->officerA->user);
    expect(Auth::user()->can('create', Application::class))->toBeTrue();
});

it('refuses deletion to everyone', function () {
    foreach ([$this->officerA->user, $this->referralX->user] as $user) {
        $this->actingAs($user);
        expect(Auth::user()->can('delete', $this->ownedByA))->toBeFalse();
    }
});

/* ------------------------------------------------------------------ Lending */

/*
 * The documented exception. Lending must see everything, and it is the only
 * place withoutGlobalScope is allowed (AD-11).
 */
it('lets the lending aggregate opt out of the scope explicitly', function () {
    $this->actingAs(Admin::factory()->create()->user);

    $all = Application::withoutGlobalScope(ApplicationVisibilityScope::class)->get();

    expect($all)->toHaveCount(2);
});

it('confines withoutGlobalScope to the lending and code-uniqueness paths', function () {
    $offenders = collect(
        array_merge(
            glob(app_path('Http/**/*.php')) ?: [],
            glob(app_path('Livewire/**/*.php')) ?: [],
            glob(app_path('Livewire/**/**/*.php')) ?: [],
        )
    )->filter(fn ($file) => str_contains(file_get_contents($file), 'withoutGlobalScope'));

    expect($offenders)->toBeEmpty(
        'withoutGlobalScope belongs in the Lending aggregate and the code-uniqueness check only.'
    );
});
