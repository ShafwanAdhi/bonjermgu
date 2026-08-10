<?php

use App\Domain\Application\ApplicationCreator;
use App\Models\AccountOfficer;
use App\Models\Admin;
use App\Models\Referral;
use App\Models\User;
use Database\Seeders\DocumentRequirementSeeder;
use Database\Seeders\TrackingStageSeeder;

/*
 * Proves every screen from the UI mockups is reachable and renders.
 *
 * The route map is docs/pages.md section 2. The screen inventory is the set of
 * data-screen-label markers across Publik / Referral / AO / Admin / AdminMaster
 * in the Claude Design project.
 *
 * These are render tests, not behaviour tests. Database-backed behaviour for
 * Credit Simulation is covered by its dedicated feature suite.
 */

/** @return array<string, array{0: string, 1: string}> route => [role, expected text] */
function screenMap(): array
{
    return [
        // Publik — no authentication
        '/' => ['guest', 'Bawa Debiturnya'],
        '/register' => ['guest', 'Registrasi Referral'],
        '/login' => ['guest', 'Gunakan nama user dan kata sandi'],

        // Referral
        '/dashboard' => ['referral', 'Aplikasi terbaru'],
        '/simulation' => ['referral', 'Hasil Lima Tenor'],
        '/simulation/print' => ['referral', 'Hasil Simulasi Kredit'],
        '/applications' => ['referral', 'Nama AO'],
        // Detail needs a real record, so it is covered separately below.
        '/profile' => ['referral', 'Kategori Referral'],

        // Account Officer
        '/applications/create' => ['ao', 'Buat Credit Application'],

        // Admin
        '/configuration/products' => ['admin', 'Product dan Upping'],
        '/configuration/insurance' => ['admin', 'Casco dan TLO'],
        '/configuration/fees' => ['admin', 'Fiducia Fee'],
        '/configuration/defaults' => ['admin', 'Nilai Default Simulasi'],
        '/master/vehicles' => ['admin', 'Harga per Tahun'],
        '/master/referral' => ['admin', 'Tambah Kategori'],
        '/master/lookups' => ['admin', 'Kelompok Usia'],
        '/accounts/referrals' => ['admin', 'registrasi mandiri'],
        '/accounts/officers' => ['admin', 'Buat Akun AO'],
        '/lending' => ['admin', 'Pipe Line A/F'],
    ];
}

/**
 * Real profile rows. The profile and account screens read the database now, so
 * a bare user no longer renders them.
 */
function actingAsRole(string $role): User
{
    return match ($role) {
        'referral' => Referral::factory()->create()->user,
        'ao' => AccountOfficer::factory()->create()->user,
        'admin' => Admin::factory()->create()->user,
    };
}

it('renders every screen in the mockups', function (string $path, string $role, string $expected) {
    $user = null;

    if ($role !== 'guest') {
        $user = actingAsRole($role);
        test()->actingAs($user);
    }

    if ($path === '/simulation/print') {
        test()->withSession([
            'simulation.active' => [
                'owner_user_id' => $user->id,
                'subject' => [
                    'debtor_name' => 'Calon Debitur',
                    'debtor_nik' => '3173000000000001',
                    'debtor_birth_date' => '01 Januari 1990',
                    'referral_code' => 'REF-0001',
                    'referral_name' => 'Referral',
                    'product' => 'Dana Tunai',
                    'mode' => 'Berdasarkan Nilai Kendaraan',
                    'vehicle' => 'HONDA BRIO',
                    'vehicle_year' => 2024,
                    'usage' => 'Passenger',
                    'instalment_type' => 'ADDB',
                    'insurance' => 'TLO All Tenor',
                    'domicile' => 'Jakarta Barat',
                    'debtor_type' => 'Perorangan Non Wiraswasta',
                    'age_group' => '36-45 tahun',
                    'funding_purpose' => 'Pendidikan',
                ],
                'results' => collect([12, 24, 36, 48, 60])->map(fn ($tenor) => [
                    'tenor' => "{$tenor} Bulan",
                    'disbursement' => 'Rp 1.000.000',
                    'instalment' => 'Rp 100.000',
                    'zero' => false,
                ])->all(),
                'disbursement_heading' => 'Pencairan Maksimal',
            ],
        ]);
    }

    test()->get($path)
        ->assertOk()
        ->assertSee($expected, escape: false);
})->with(collect(screenMap())->map(
    fn ($spec, $path) => [$path, $spec[0], $spec[1]],
)->values()->all());

/*
 * The AO and Referral variants of the shared routes are different screens in
 * the mockups, so each one is checked separately.
 */
it('renders the officer variant of each shared screen', function (string $path, string $expected) {
    test()->actingAs(actingAsRole('ao'));

    test()->get($path)->assertOk()->assertSee($expected, escape: false);
})->with([
    ['/dashboard', 'Tahapan tertunda'],
    ['/applications', 'Nama Referral'],
    // Detail is covered by the dedicated test below.
    ['/profile', 'Account Officer'],
]);

it('renders the admin dashboard variant', function () {
    $this->actingAs(actingAsRole('admin'));

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Actual Lending')
        ->assertSee('Pipe Line')
        ->assertSee('1 bulan')
        ->assertSee('3 bulan')
        ->assertSee('12 bulan')
        ->assertSee('Semua')
        ->assertSee('Komposisi Lending')
        ->assertSee('Performa Produk')
        ->assertDontSee('6 bulan')
        ->assertDontSee('Visualisasi Data')
        ->assertDontSee('Insight Lending')
        ->assertDontSee('Ringkasan visual untuk membaca proporsi portofolio')
        ->assertDontSee('Buka Konfigurasi')
        ->assertDontSee('Buka Lending')
        ->assertDontSee('satu-satunya pengecualian akses Admin terhadap data application');
});

it('renders testimonial content on the landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Testimoni')
        ->assertSee('Tri')
        ->assertSee('SM-Honda Jakarta Center')
        ->assertSee('Simulasinya sangat mudah digunakan')
        ->assertSee('Gito Purnomo')
        ->assertSee('BM DSO BSD')
        ->assertSee('Website sangat membantu, top &amp; keren', escape: false);
});

it('renders a birthday greeting on the referral dashboard', function () {
    $referral = Referral::factory()->create([
        'full_name' => 'Budi Santoso',
        'birth_date' => '1990-'.now()->format('m-d'),
    ]);

    $this->actingAs($referral->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeText('Selamat datang, Budi.')
        ->assertSeeText('Selamat ulang tahun, semoga selalu diberikan kesehatan.')
        ->assertSee('birthday-fireworks', escape: false);
});

it('renders a birthday greeting on the officer dashboard', function () {
    $officer = AccountOfficer::factory()->create([
        'full_name' => 'Siti Rahayu',
        'birth_date' => '1990-'.now()->format('m-d'),
    ]);

    $this->actingAs($officer->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeText('Selamat datang, Siti.')
        ->assertSeeText('Selamat ulang tahun, semoga selalu diberikan kesehatan.')
        ->assertSee('birthday-fireworks', escape: false);
});

it('keeps the normal dashboard greeting when today is not the user birthday', function () {
    $referral = Referral::factory()->create([
        'full_name' => 'Andi Wijaya',
        'birth_date' => now()->subDay()->subYears(30)->format('Y-m-d'),
    ]);

    $this->actingAs($referral->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Selamat datang, Andi.', escape: false)
        ->assertDontSee('Selamat ulang tahun, semoga selalu diberikan kesehatan.', escape: false)
        ->assertDontSee('birthday-fireworks', escape: false);
});

it('renders both lending tabs', function (string $tab, string $expected) {
    $this->actingAs(actingAsRole('admin'));

    $this->get('/lending?tab='.$tab)->assertOk()->assertSee($expected);
})->with([
    ['ao', 'Account Officer'],
    ['referral', 'Referral'],
]);

/*
 * Detail Aplikasi needs a real record, so it builds one instead of using a
 * fixed path in the map above. Both role variants of the same screen.
 */
it('renders the application detail screen for both roles', function (string $role, string $expected) {
    $this->seed(DocumentRequirementSeeder::class);
    $this->seed(TrackingStageSeeder::class);

    $officer = AccountOfficer::factory()->create();
    $referral = Referral::factory()->create();

    $this->actingAs($officer->user);

    $application = ApplicationCreator::create([
        'account_officer_id' => $officer->id,
        'referral_id' => $referral->id,
        'financing_product' => 'DTN',
        'debtor_name' => 'Siti Rahayu',
        'debtor_nik' => '3173054505880002',
        'debtor_birth_date' => '1988-05-05',
        'debtor_type' => 'Perorangan Non Wiraswasta',
        'spouse_income_type' => 'Tidak Ada',
        'amount_finance' => 120_000_000,
        'unit_count' => 1,
    ]);

    $this->actingAs($role === 'ao' ? $officer->user : $referral->user)
        ->get('/applications/'.$application->code)
        ->assertOk()
        ->assertSee($expected);
})->with([
    // AO gets the controls, Referral gets the same content read-only.
    ['ao', 'Ubah Data'],
    ['referral', 'Tracking'],
]);
