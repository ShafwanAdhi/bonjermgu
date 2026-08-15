<?php

use App\Models\User;
use Illuminate\Support\Facades\View;

/*
 * Custom copy for the error states listed in pages.md §18. Laravel renders
 * these automatically for any 403/404/419/500 response — nothing in the app
 * has to opt in.
 *
 * 419 and 500 are not naturally reachable in tests (CSRF verification is
 * skipped while running unit tests, and nothing here deliberately throws),
 * so those two render the view directly instead of going through a request.
 */

it('shows Indonesian copy on a 403 response', function () {
    $this->actingAs(User::factory()->referral()->create());

    $this->get('/configuration')
        ->assertForbidden()
        ->assertSee('Tidak Berwenang')
        ->assertSee('hubungi Admin');
});

it('shows Indonesian copy on a 404 response', function () {
    $this->actingAs(User::factory()->accountOfficer()->create());

    $this->get('/applications/zzzzzz')
        ->assertNotFound()
        ->assertSee('Halaman Tidak Ditemukan');
});

it('renders the session expired page with a way back in', function () {
    $html = View::make('errors.419')->render();

    expect($html)->toContain('Sesi Anda Telah Berakhir')
        ->toContain('Masuk Kembali');
});

it('renders the generic error page without leaking internals', function () {
    $html = View::make('errors.500')->render();

    expect($html)->toContain('Terjadi Kesalahan')
        ->not->toContain('Exception')
        ->not->toContain('Stack trace');
});
