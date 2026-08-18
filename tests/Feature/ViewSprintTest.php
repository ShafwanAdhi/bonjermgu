<?php

use App\Livewire\Simulation\OfficerSimulation;
use App\Livewire\Simulation\ViewSprint;
use App\Models\ReferralCategory;
use App\Models\ReferralSubCategory;
use App\Models\SimulationSetting;
use App\Models\SprintOffering;
use App\Models\User;
use App\Support\SimulationSettingDefaults;
use Carbon\Carbon;
use Livewire\Livewire;

/*
 * View Sprint is the sheet an inputter screenshots and sends to head office.
 * It calculates nothing: every figure is read from the Officer simulation the
 * AO just ran, and the fields the engine cannot know are typed in by the AO
 * (client, 15 August 2026).
 */

function runOfficerSimulation(): array
{
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));

    $user = User::factory()->accountOfficer()->create();

    Livewire::actingAs($user)
        ->test(OfficerSimulation::class)
        ->set(officerState($category, $model, $price->year))
        ->set('unit_price', (string) $price->price)
        ->call('calculate')
        ->assertHasNoErrors();

    return [$user, $price];
}

it('refuses View Sprint to everyone except AO', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/simulation/officer/view-sprint/12')->assertForbidden();
})->with(['admin', 'referral']);

it('reads every figure from the simulation the officer already ran', function () {
    [$user] = runOfficerSimulation();

    $sprint = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 24]);
    $sheet = $sprint->instance()->sheet();

    expect($sheet['tenor'])->toEqual(24)
        ->and($sheet['harga_kendaraan'])->toBeGreaterThan(0)
        ->and($sheet['angsuran'])->toBeGreaterThan(0)
        ->and($sheet['pokok_hutang'])->toBeGreaterThan(0)
        ->and($sheet['total_bayar_pertama'])->toBeGreaterThan(0);

    Carbon::setTestNow();
});

it('shows the figures of the tenor that was asked for', function () {
    [$user] = runOfficerSimulation();

    $twelve = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 12])->instance()->sheet();
    $sixty = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 60])->instance()->sheet();

    expect($twelve['tenor'])->toEqual(12)
        ->and($sixty['tenor'])->toEqual(60)
        ->and($twelve['angsuran'])->toBeGreaterThan($sixty['angsuran']);

    Carbon::setTestNow();
});

/*
 * The engine never learns these; SPRINT still needs them. They start from the
 * Admin defaults so the AO edits rather than types from nothing.
 */
it('starts the fields the engine cannot know from the Admin defaults', function () {
    [$user] = runOfficerSimulation();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('cara_pembayaran', 'AUTO COLLECTION')
        ->assertSet('kondisi_kendaraan', 'USED CAR')
        ->assertSet('mandiri_kpm', 'NO')
        ->assertSet('is_beliv', 'TIDAK')
        ->assertSet('acp_axp', 'ADA')
        ->assertSet('gap', 'NO')
        ->assertSet('hic', 'NO')
        ->assertSet('water_hammer', 'NO');

    Carbon::setTestNow();
});

it('offers the Master dropdown choices needed by View Sprint', function () {
    [$user] = runOfficerSimulation();

    $component = Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSee('PDC/GIRO')
        ->instance();

    expect(collect($component->tokens()['dp'])->pluck('source')->all())
        ->toEqual(['DP5', 'DP10', 'DP15', 'DP20', 'DP25', 'DP30', 'DP40', 'DP50']);

    Carbon::setTestNow();
});

it('says so plainly when no simulation has been run yet', function () {
    $this->actingAs(User::factory()->accountOfficer()->create());

    $this->get('/simulation/officer/view-sprint/12')
        ->assertOk()
        ->assertSee('Belum ada simulasi');
});

it('refuses a tenor the simulation does not produce', function () {
    [$user] = runOfficerSimulation();

    $this->actingAs($user)->get('/simulation/officer/view-sprint/18')->assertNotFound();

    Carbon::setTestNow();
});

/*
 * Product ID dan Product Offering dieja dari satu token per dimensi. Nilai
 * acuan di bawah adalah Master!C5 dan Master!C6 pada workbook offering apa
 * adanya, jadi kegagalan di sini berarti rangkaiannya menyimpang dari sana.
 */
it('spells both SPRINT codes exactly as the offering workbook does', function () {
    [$user] = runOfficerSimulation();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 36])
        ->set('sprint_product', 'C2C Investasi PPSA')
        ->set('sprint_channel', 'Referral')
        ->set('sprint_unit', 'Passenger')
        ->set('sprint_brand', 'Japan')
        ->set('sprint_profile', 'Perorangan Non Wiraswasta')
        ->set('sprint_debtor_type', 'New Customer')
        ->set('sprint_dp', 'DP5')
        ->set('sprint_region', 'Jawa')
        ->assertSet('product_id', 'INVESTASI PPSA C2C REGULER PASSENGER PERORANGAN NEW CUST & ROMI - ADDB')
        ->assertSet('product_offering', 'INV PPSA C2C JAWA REFERRAL PASS JPN PERORANGAN NEW&ROMI DP5 3TH - ADDB');

    Carbon::setTestNow();
});

it('uses valid imported SPRINT offerings before falling back to token composition', function () {
    [$user] = runOfficerSimulation();

    SprintOffering::query()->create([
        'fingerprint' => hash('sha256', 'valid-dp5'),
        'source_workbook' => 'workbook.xlsx',
        'source_sheet' => 'new REFERRAL',
        'source_row' => 5,
        'source_channel' => 'Referral',
        'product_id' => 'VALID PRODUCT ID',
        'product_offering' => 'VALID OFFERING DP5 1TH - ADDB',
        'product_category' => 'C2C Investasi PPSA',
        'channel' => 'Referral',
        'region' => 'Jawa',
        'unit' => 'Passenger',
        'brand' => 'Japan',
        'profile' => 'Perorangan',
        'debtor_type' => 'New Customer / Repeat Order',
        'dp' => 'DP5',
        'tenor' => '1TH',
        'instalment' => 'ADDB',
    ]);

    SprintOffering::query()->create([
        'fingerprint' => hash('sha256', 'valid-dp10'),
        'source_workbook' => 'workbook.xlsx',
        'source_sheet' => 'new REFERRAL',
        'source_row' => 6,
        'source_channel' => 'Referral',
        'product_id' => 'VALID PRODUCT ID',
        'product_offering' => 'VALID OFFERING DP10 1TH - ADDB',
        'product_category' => 'C2C Investasi PPSA',
        'channel' => 'Referral',
        'region' => 'Jawa',
        'unit' => 'Passenger',
        'brand' => 'Japan',
        'profile' => 'Perorangan',
        'debtor_type' => 'New Customer / Repeat Order',
        'dp' => 'DP10',
        'tenor' => '1TH',
        'instalment' => 'ADDB',
    ]);

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->set('sprint_product', 'C2C Investasi PPSA')
        ->set('sprint_channel', 'Referral')
        ->set('sprint_unit', 'Passenger')
        ->set('sprint_brand', 'Japan')
        ->set('sprint_profile', 'Perorangan Non Wiraswasta')
        ->set('sprint_debtor_type', 'New Customer')
        ->set('sprint_dp', 'DP5')
        ->set('sprint_region', 'Jawa')
        ->assertSet('product_id', 'VALID PRODUCT ID')
        ->assertSet('product_offering', 'VALID OFFERING DP5 1TH - ADDB');

    Carbon::setTestNow();
});

it('does not show a manual Product ID and Product Offering mode', function () {
    [$user] = runOfficerSimulation();

    SprintOffering::query()->create([
        'fingerprint' => hash('sha256', 'dropdown-only'),
        'source_workbook' => 'workbook.xlsx',
        'source_sheet' => 'new REFERRAL',
        'source_row' => 5,
        'source_channel' => 'Referral',
        'product_id' => 'VALID PRODUCT ID',
        'product_offering' => 'VALID OFFERING DP5 1TH - ADDB',
        'product_category' => 'C2C Investasi PPSA',
        'channel' => 'Referral',
        'region' => 'Jawa',
        'unit' => 'Passenger',
        'brand' => 'Japan',
        'profile' => 'Perorangan',
        'debtor_type' => 'New Customer / Repeat Order',
        'dp' => 'DP5',
        'tenor' => '1TH',
        'instalment' => 'ADDB',
    ]);

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertDontSee('Manual')
        ->assertDontSee('Isi Product ID sesuai arahan pusat')
        ->assertDontSee('Isi Product Offering sesuai arahan pusat');

    Carbon::setTestNow();
});

/*
 * Kode setengah jadi terbaca seperti kode sah begitu lembarnya jadi gambar,
 * dan gambar itu dikirim ke pusat. Lebih baik kosong.
 */
it('leaves both codes empty while any segment is still unanswered', function () {
    [$user] = runOfficerSimulation();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->set('sprint_dp', 'DP5')
        ->set('sprint_product', '')
        ->assertSet('product_id', '')
        ->assertSet('product_offering', '');

    Carbon::setTestNow();
});

it('answers the dimensions the simulation already knows', function () {
    [$user] = runOfficerSimulation();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('sprint_unit', 'Passenger')
        ->assertSet('sprint_brand', 'Japan')
        ->assertSet('sprint_profile', 'Perorangan Non Wiraswasta')
        ->assertSet('sprint_instalment', 'ADDB')
        // Passenger Jepang; sisanya DP15 (klien, 18 Agustus 2026).
        ->assertSet('sprint_dp', 'DP5');

    Carbon::setTestNow();
});

/*
 * Lembar ini berakhir sebagai gambar yang dikirim ke pusat. Kode yang dipilihkan
 * sistem dari sekian kemungkinan terbaca persis seperti kode yang dipilih AO,
 * dan tidak ada yang bisa membedakannya setelah jadi PNG.
 */
it('refuses to pick a Product ID when the filters leave more than one', function () {
    [$user] = runOfficerSimulation();

    foreach ([['A', 'PRODUCT SATU'], ['B', 'PRODUCT DUA']] as [$suffix, $productId]) {
        SprintOffering::query()->create([
            'fingerprint' => hash('sha256', 'ambigu-'.$suffix),
            'source_workbook' => 'workbook.xlsx',
            'source_sheet' => 'new REFERRAL',
            'source_row' => 5,
            'source_channel' => 'Referral',
            'product_id' => $productId,
            'product_offering' => 'OFFERING '.$suffix.' DP5 1TH - ADDB',
            'product_category' => 'C2C Investasi PPSA',
            'channel' => 'Referral',
            'region' => 'Jawa',
            'unit' => 'Passenger',
            'brand' => 'Japan',
            'profile' => 'Perorangan',
            'debtor_type' => 'New Customer / Repeat Order',
            'dp' => 'DP5',
            'tenor' => '1TH',
            'instalment' => 'ADDB',
        ]);
    }

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->set('sprint_product', 'C2C Investasi PPSA')
        ->set('sprint_channel', 'Referral')
        ->set('sprint_dp', 'DP5')
        ->assertSet('product_id', '')
        ->assertSet('product_offering', '');

    Carbon::setTestNow();
});

/* Menyempitkan sampai tersisa satu bukan tebakan, jadi yang satu itu dipilihkan. */
it('settles on the only Product ID the filters leave standing', function () {
    [$user] = runOfficerSimulation();

    SprintOffering::query()->create([
        'fingerprint' => hash('sha256', 'tunggal'),
        'source_workbook' => 'workbook.xlsx',
        'source_sheet' => 'new REFERRAL',
        'source_row' => 5,
        'source_channel' => 'Referral',
        'product_id' => 'SATU-SATUNYA PRODUCT ID',
        'product_offering' => 'SATU-SATUNYA OFFERING DP5 1TH - ADDB',
        'product_category' => 'C2C Investasi PPSA',
        'channel' => 'Referral',
        'region' => 'Jawa',
        'unit' => 'Passenger',
        'brand' => 'Japan',
        'profile' => 'Perorangan',
        'debtor_type' => 'New Customer / Repeat Order',
        'dp' => 'DP5',
        'tenor' => '1TH',
        'instalment' => 'ADDB',
    ]);

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->set('sprint_product', 'C2C Investasi PPSA')
        ->set('sprint_dp', 'DP5')
        ->assertSet('product_id', 'SATU-SATUNYA PRODUCT ID')
        ->assertSet('product_offering', 'SATU-SATUNYA OFFERING DP5 1TH - ADDB');

    Carbon::setTestNow();
});

/*
 * Nilai bawaan Admin harus salah satu dari pilihan yang ditawarkan field itu.
 *
 * Ketika keduanya berselisih, blade menyisipkan nilai simpanan sebagai opsi
 * tambahan supaya tidak hilang diam-diam — dan AO melihat dropdown ganjil
 * berisi "NO", "ADA", "TIDAK" sekaligus. Itu yang terjadi pada GAP, HIC, dan
 * Water Hammer: bawaannya NO, tapi pilihannya ADA/TIDAK.
 *
 * Kosakatanya memang tidak seragam antar field, mengikuti data validation
 * workbook: ADA/TIDAK untuk ACP & AXP, TIDAK/YA untuk BELIV, NO/YES untuk
 * GAP, HIC, dan Water Hammer.
 */
it('offers each manual field a list its own Admin default belongs to', function () {
    $sprint = new ViewSprint;
    $defaults = (new ReflectionClass($sprint))->getConstant('MANUAL_DEFAULTS');

    $mismatched = collect(ViewSprint::MANUAL_OPTIONS)
        ->reject(function (array $options, string $field) use ($defaults): bool {
            $value = SimulationSetting::query()->where('key', $defaults[$field])->value('value')
                ?? SimulationSettingDefaults::values()[$defaults[$field]];

            return in_array($value, $options, true);
        })
        ->keys()
        ->all();

    expect($mismatched)->toBe([]);
});

/* Yang tersisa sebagai pilihan manual tinggal dua, dan keduanya berbeda kosakata. */
it('keeps the manual vocabulary the workbook uses for what is still asked', function () {
    expect(ViewSprint::MANUAL_OPTIONS)->toBe([
        'cara_pembayaran' => ['AUTO COLLECTION', 'PDC/GIRO'],
        'is_beliv' => ['TIDAK', 'YA'],
    ]);
});

/*
 * Layar Simulasi Kredit menandai tenor tanpa pembiayaan dengan "—" alih-alih
 * tombol, tapi alamatnya bisa diketik langsung. Lembar berisi nol di setiap
 * baris terbaca seperti lembar sah begitu jadi gambar.
 */
it('refuses a tenor that produces no financing at all', function () {
    [$user, $price] = runOfficerSimulation();

    // Harga sangat rendah membuat Net DP melampaui harga, jadi tidak ada yang dibiayai.
    Livewire::actingAs($user)
        ->test(OfficerSimulation::class)
        ->set('unit_price', '1')
        ->call('calculate');

    $sprint = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 60]);

    expect($sprint->instance()->available())->toBeFalse();
    $sprint->assertSee('tidak menghasilkan pembiayaan');

    Carbon::setTestNow();
});

/*
 * Lembar ini hanya ada untuk jadi gambar yang dikirim ke pusat. Mengunduhnya
 * setengah jadi menghasilkan dokumen yang terbaca sah padahal identitasnya
 * kosong.
 */
it('names what is still missing before the sheet may be downloaded', function () {
    [$user] = runOfficerSimulation();

    $sprint = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 12]);

    // Kanal, Brand, dan Golongan DP yang diturunkan menyempitkan katalog sampai
    // tersisa satu, jadi kedua kode terisi sendiri dan tinggal namanya.
    expect($sprint->instance()->missingForExport())->toBe(['Nama Customer']);

    $sprint->assertSee('Belum bisa diunduh');

    Carbon::setTestNow();
});

/*
 * Tombol tenor menavigasi ke rute lain, jadi komponennya mount ulang. Isian AO
 * menggambarkan debiturnya, bukan tenornya, dan harus ikut pindah.
 */
it('keeps what the officer typed when the tenor changes', function () {
    [$user] = runOfficerSimulation();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->set('nama_customer', 'PT Sinar Rejeki')
        ->set('spesifik_product', 'Paket Khusus')
        ->set('wira_no', '77');

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 48])
        ->assertSet('nama_customer', 'PT Sinar Rejeki')
        ->assertSet('spesifik_product', 'Paket Khusus')
        ->assertSet('wira_no', '77');

    Carbon::setTestNow();
});

it('names the download after the customer and the tenor', function () {
    [$user] = runOfficerSimulation();

    $sprint = Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 36])
        ->set('nama_customer', 'PT Sinar Rejeki');

    expect($sprint->instance()->exportFileName())->toBe('view-sprint-pt-sinar-rejeki-36-bulan.png');

    Carbon::setTestNow();
});

/*
 * Delapan dropdown, dan satu kombinasi bisa menyaring habis katalog. Tanpa
 * diagnosa, layar hanya berkata "belum tersedia" dan AO harus menebak pilihan
 * mana yang harus diubah.
 */
it('names the filter to relax when one of them empties the catalogue', function () {
    [$user] = runOfficerSimulation();

    SprintOffering::query()->create([
        'fingerprint' => hash('sha256', 'diagnosa'),
        'source_workbook' => 'workbook.xlsx',
        'source_sheet' => 'new REFERRAL',
        'source_row' => 5,
        'source_channel' => 'Referral',
        'product_id' => 'PRODUCT ADA',
        'product_offering' => 'OFFERING ADA DP5 1TH - ADDB',
        'product_category' => 'C2C Investasi PPSA',
        'channel' => 'Referral',
        'region' => 'Jawa',
        'unit' => 'Passenger',
        'brand' => 'Japan',
        'profile' => 'Perorangan',
        'debtor_type' => 'New Customer / Repeat Order',
        'dp' => 'DP5',
        'tenor' => '1TH',
        'instalment' => 'ADDB',
    ]);

    $sprint = Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->set('sprint_product', 'C2C Investasi PPSA')
        ->set('sprint_dp', 'DP5')
        // Hanya Golongan DP yang meleset; melonggarkannya sendirian cukup.
        ->set('sprint_dp', 'DP50');

    expect($sprint->instance()->lookupDeadEnd())->toBeTrue()
        ->and($sprint->instance()->blockingFilters())->toBe(['Golongan DP']);

    $sprint->assertSee('Coba longgarkan Golongan DP');

    Carbon::setTestNow();
});

/* Tenor tanpa pembiayaan tidak boleh ditawarkan sebagai tautan. */
it('offers only the tenors that actually produce financing', function () {
    [$user] = runOfficerSimulation();

    $sprint = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 12]);

    expect($sprint->instance()->financedTenors())->not->toBeEmpty()
        ->and($sprint->instance()->financedTenors())->each->toBeIn([12, 24, 36, 48, 60]);

    Carbon::setTestNow();
});

/*
 * Tombol unduh sempat tetap bisa diklik meski pesannya sudah melarang:
 * x-bind:disabled="busy" menghapus atribut disabled dari server setiap kali
 * busy bernilai false. Sisi server yang memegang disabled sekarang, jadi tes
 * ini memeriksa atributnya benar-benar terpasang, bukan sekadar pesannya ada.
 */
it('really disables the download button while the sheet is incomplete', function () {
    [$user] = runOfficerSimulation();

    $html = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 12])->html();

    expect($html)->toMatch('/<button[^>]*data-export-button[^>]*\bdisabled\b/');

    Carbon::setTestNow();
});

it('marks every field that blocks the download as required', function () {
    [$user] = runOfficerSimulation();

    $html = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 12])->html();

    // Label wajib membawa tanda bintang; jumlahnya harus sama dengan jumlah
    // yang menahan unduhan, tidak lebih dan tidak kurang.
    expect(substr_count($html, '<span class="text-signature-coral"> *</span>'))->toBe(3);

    Carbon::setTestNow();
});

/*
 * Lima dimensi berhenti ditanyakan ke AO karena jawabannya sudah ditentukan
 * hal lain (klien, 18 Agustus 2026). Nilainya tetap muncul di lembar; yang
 * hilang hanya kolom isiannya.
 */
it('stops asking for what the simulation and the configuration already decided', function () {
    [$user] = runOfficerSimulation();

    $html = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 12])->html();

    foreach (['sprint_region', 'sprint_debtor_type'] as $gone) {
        expect($html)->not->toContain('wire:model.live="'.$gone.'"');
    }
    foreach (['mandiri_kpm', 'kondisi_kendaraan', 'acp_axp', 'gap', 'hic', 'water_hammer',
        'sprint_channel', 'sprint_brand', 'sprint_dp', 'paid_status.1'] as $gone) {
        expect($html)->not->toContain('wire:model.live="'.$gone.'"');
    }

    // Tenor dan Jenis Angsuran tidak lagi tampil sebagai kolom mati.
    expect($html)->not->toContain('label="Jenis Angsuran"');

    Carbon::setTestNow();
});

it('reads Type Customer from the simulation instead of asking again', function () {
    [$category, $model, $price] = officerMaster();
    Carbon::setTestNow(Carbon::create($price->year + 1, 8, 4));
    $user = User::factory()->accountOfficer()->create();

    Livewire::actingAs($user)
        ->test(OfficerSimulation::class)
        ->set(officerState($category, $model, $price->year))
        ->set('unit_price', (string) $price->price)
        ->set('customer_type', 'Additional Order')
        ->call('calculate')
        ->assertHasNoErrors();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('sprint_debtor_type', 'Additional Order');

    Carbon::setTestNow();
});

/*
 * KKB mengikuti segmen kategori referral, bukan pilihan AO: Captive Internal
 * dan Captive External memakainya, sisanya tidak.
 */
it('turns KKB on only for the Captive segment', function (string $segment, string $expected) {
    [$user] = runOfficerSimulation();

    $categoryId = session()->get('simulation.officer.form')['referral_category_id'];
    ReferralCategory::query()->whereKey($categoryId)->update(['segment' => $segment]);

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('mandiri_kpm', $expected);

    Carbon::setTestNow();
})->with([['Captive', 'YES'], ['Reguler', 'NO']]);

it('fixes the vehicle condition and the region from Admin configuration', function () {
    [$user] = runOfficerSimulation();

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('kondisi_kendaraan', 'USED CAR')
        ->assertSet('sprint_region', 'Jawa');

    Carbon::setTestNow();
});

/*
 * Kanal mengikuti tier kategori referral, ditimpa sub kategori bila satu
 * kategori melayani lebih dari satu (klien, 18 Agustus 2026).
 */
it('reads the channel from the referral tier', function (string $segment, string $tier, string $expected) {
    [$user] = runOfficerSimulation();

    $state = session()->get('simulation.officer.form');
    // Segmen ikut disetel karena Product diresolusi dari segmen + unit + tier;
    // tier sendirian bisa menunjuk produk yang tidak ada.
    ReferralCategory::query()->whereKey($state['referral_category_id'])
        ->update(['segment' => $segment, 'tier' => $tier]);

    // Fixture layar AO tidak memilih sub kategori, jadi tidak ada yang menimpa
    // tier — persis keadaan yang ingin diuji di sini.
    $state['referral_sub_category_id'] = null;
    session()->put('simulation.officer.form', $state);

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('sprint_channel', $expected);

    Carbon::setTestNow();
})->with([
    ['Captive', 'Semangat', 'Captive NJF Semangat'],
    ['Captive', 'Tengah', 'Captive NJF Tengah'],
    ['Captive', 'Cuan', 'Captive NJF Cuan'],
    ['Reguler', 'Sales Dealer', 'Wira Agent'],
    // Tier yang tidak dipakai pusat tidak ditebak.
    ['Captive', 'Khusus Karyawan Bank Mandiri', ''],
]);

it('lets a sub-category override the tier where a category serves two channels', function () {
    [$user] = runOfficerSimulation();

    $state = session()->get('simulation.officer.form');
    ReferralCategory::query()->whereKey($state['referral_category_id'])->update(['tier' => 'Referral']);

    $telemarketing = ReferralSubCategory::query()->create([
        'category_id' => $state['referral_category_id'],
        'name' => 'Graha Sultan',
    ]);
    $state['referral_sub_category_id'] = $telemarketing->id;
    session()->put('simulation.officer.form', $state);

    Livewire::actingAs($user)
        ->test(ViewSprint::class, ['tenor' => 12])
        ->assertSet('sprint_channel', 'Telemarketing');

    Carbon::setTestNow();
});

/* Insurance Paid Entry hanya sepanjang tenor, dan seluruhnya dari engine. */
it('lists Insurance Paid Entry for the tenor years only', function (int $tenor, int $years) {
    [$user] = runOfficerSimulation();

    $sheet = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => $tenor])->instance()->sheet();

    expect($sheet['paid_entry'])->toHaveCount($years)
        ->and(collect($sheet['paid_entry'])->pluck('status')->unique()->all())->toBe(['CASH'])
        ->and(collect($sheet['paid_entry'])->sum('paid'))->toBeGreaterThan(0);

    Carbon::setTestNow();
})->with([[12, 1], [36, 3], [60, 5]]);

/* Rincian per tahun harus berjumlah sama dengan total asuransi yang dilaporkan. */
it('splits the premium across years without losing any of it', function () {
    [$user] = runOfficerSimulation();

    $sprint = Livewire::actingAs($user)->test(ViewSprint::class, ['tenor' => 60])->instance();
    $sheet = $sprint->sheet();

    expect(collect($sheet['paid_entry'])->sum('paid'))->toEqualWithDelta($sheet['asuransi'], 100);

    Carbon::setTestNow();
});
