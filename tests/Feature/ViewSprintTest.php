<?php

use App\Livewire\Simulation\OfficerSimulation;
use App\Livewire\Simulation\ViewSprint;
use App\Models\SprintOffering;
use App\Models\User;
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
        // Ambigu bagi engine; ditinggalkan untuk AO.
        ->assertSet('sprint_dp', '');

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
