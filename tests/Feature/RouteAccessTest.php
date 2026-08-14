<?php

use App\Models\User;

/*
 * Authorization is tested from the side that should be refused. A test that
 * only proves the allowed path passes happily while the door stands open.
 *
 * What is covered here is route-level role gating only. Record-level ownership
 * — a Referral seeing another Referral's application — is enforced on the query
 * by a global scope (AD-09) and cannot be tested until the application tables
 * exist. Do not read a green suite here as ownership being enforced.
 */

it('refuses the dashboard to guests', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('keeps authenticated users away from the guest pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/login')->assertRedirect();
    $this->actingAs($user)->get('/register')->assertRedirect();
});

it('refuses logout to guests', function () {
    $this->post('/logout')->assertRedirect(route('login'));
});

it('lets each role reach the shared dashboard route', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
})->with(['admin', 'referral', 'accountOfficer']);

/*
 * Admin has no route into application data at all. The global scope returns an
 * empty set rather than everything — the single exception being the Lending
 * aggregate. This is the rule people get wrong most often.
 */
it('refuses application screens to admin', function (string $path) {
    $this->actingAs(User::factory()->admin()->create());

    $this->get($path)->assertForbidden();
})->with([
    '/applications',
    '/applications/create',
    '/profile',
]);

/*
 * The detail route answers 404 rather than 403. Route model binding resolves
 * before the role middleware, and the visibility scope has already emptied
 * Admin's result set, so the record is simply not found.
 *
 * That is the better answer anyway: it does not confirm the application
 * exists (pages.md §18).
 */
it('answers not found when admin reaches an application detail url', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/applications/abc123')->assertNotFound();
});

it('refuses admin screens to referral and officer', function (string $path, string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get($path)->assertForbidden();
})->with([
    '/configuration',
    '/configuration/products',
    '/configuration/insurance',
    '/configuration/fees',
    '/configuration/defaults',
    '/configuration/audit',
    '/master',
    '/master/vehicles',
    '/master/referral',
    '/master/lookups',
    '/accounts',
    '/accounts/profile',
    '/accounts/referrals',
    '/accounts/officers',
    '/lending',
    '/lending/ao',
    '/lending/referrals',
])->with(['referral', 'accountOfficer']);

/*
 * The Referral calculator belongs to Referral alone. AO has its own screen at
 * /simulation/officer and Admin has Uji Konfigurasi; neither may reach this one,
 * because this is the route that can print a debtor's identity.
 */
it('refuses simulation to everyone except referral', function (string $path, string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get($path)->assertForbidden();
})->with(['/simulation', '/simulation/print', '/simulation/print/download'])->with(['admin', 'accountOfficer']);

/* Only AO creates applications. Referral passes the read gate and is stopped here. */
it('refuses application creation to referral', function () {
    $this->actingAs(User::factory()->referral()->create());

    $this->get('/applications/create')->assertForbidden();
});

it('returns not found for an unknown application code', function () {
    $this->actingAs(User::factory()->accountOfficer()->create());

    $this->get('/applications/APL-0000-0000')->assertNotFound();
});
