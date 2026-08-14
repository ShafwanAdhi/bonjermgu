<?php

it('shows the landing page to guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Kebon Jeruk Multiguna')
        ->assertSee('images/brand/bonjemgu-logo.svg', escape: false)
        ->assertSee('Bawa Debiturnya biarkan kami yg menghitung')
        ->assertSee('Program Mitra Referral')
        ->assertSee('Registrasi')
        ->assertSee('Masuk');
});

it('lists the referral types and the three steps', function () {
    $response = $this->get('/');

    $response->assertSee('Sales Authorized Dealer');
    $response->assertSee('Karyawan asuransi Rekanan');
    $response->assertSee('Karyawan Internal &amp; Captive', escape: false);
    $response->assertSee('Komunitas otomotif');
    $response->assertSee('Perorangan');

    $response->assertSee('Isi Form Online');
    $response->assertSee('Ajukan');
});

/*
 * The draft carried an application-lookup panel on the front page. It was
 * removed on purpose — a Kode Aplikasi is an identifier, not a credential
 * (docs/business.md section 6, AD-08). This test exists to keep it removed.
 */
it('carries no application lookup and no calculator', function () {
    $response = $this->get('/');

    $response->assertDontSee('Kode Aplikasi');
    $response->assertDontSee('Cari Aplikasi');
    $response->assertDontSee('Lacak');
    $response->assertDontSee('Simulasi Cepat');
    $response->assertDontSee('Hitung');
});
