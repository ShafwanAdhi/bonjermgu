<?php

use App\Domain\Simulation\DanaTunaiCalculator;
use App\Domain\Simulation\Input\ProductConfig;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\Rate\FlatRateConverter;
use Tests\Unit\Simulation\SimulationTestFactory;

/*
 * Rate card MTF mencetak tiga angka per tenor: Eff, flat ADDB, dan flat ADDM.
 * Cabang menghitung angsuran dari yang flat. Kita dulu hanya menyimpan Eff lalu
 * menghitung flat-nya sendiri, dan hasilnya tidak bisa mendarat di angka cetak:
 * kartu membulatkan ke 4 desimal, jadi 19,06% terbit menjadi 11,17% tercetak
 * sementara konversi kita memberi 11,1749%. Selisih desimal kelima itu berubah
 * jadi seribu rupiah penuh begitu angsuran dibulatkan naik ke ribuan.
 */

function cardProduct(?float $addb = null, ?float $addm = null): ProductConfig
{
    $blank = array_fill_keys([12, 24, 36, 48, 60], null);

    return new ProductConfig(
        name: 'Reguler Passenger Referral',
        effectiveRates: [12 => 0.17253, 24 => 0.1750, 36 => 0.1800, 48 => 0.1869, 60 => 0.1907],
        adminMax: 5350000,
        flatRatesAddb: $addb === null ? $blank : [60 => $addb] + $blank,
        flatRatesAddm: $addm === null ? $blank : [60 => $addm] + $blank,
    );
}

it('reports no flat rate until the card has been entered', function () {
    $product = cardProduct();

    expect($product->flatRateFor(60, InstalmentType::ADDB))->toBeNull()
        ->and($product->flatRateFor(60, InstalmentType::ADDM))->toBeNull();
});

it('keeps ADDB and ADDM apart', function () {
    $product = cardProduct(addb: 0.1117, addm: 0.1069);

    expect($product->flatRateFor(60, InstalmentType::ADDB))->toBe(0.1117)
        ->and($product->flatRateFor(60, InstalmentType::ADDM))->toBe(0.1069)
        // Hanya tenor 60 yang punya angka kartu pada contoh ini.
        ->and($product->flatRateFor(48, InstalmentType::ADDB))->toBeNull();
});

it('carries the card through an Officer upping', function () {
    $product = cardProduct(addb: 0.1117, addm: 0.1069)->withUpping(0.03, 500000, 0.01);

    expect($product->flatRateFor(60, InstalmentType::ADDB))->toBe(0.1117);
});

/*
 * Angka inilah yang membuat kolomnya perlu ada: kartu mencetak 0,1117 dan
 * konversi memberi 0,11174900. Konverter tetap dipakai sebagai pemeriksa, jadi
 * jaraknya harus tetap kecil — lebih dari satu basis poin berarti salah ketik.
 */
it('stays within one basis point of the card it replaces', function () {
    $converted = (new FlatRateConverter)->convert(0.1907, 60, InstalmentType::ADDB);

    expect(round($converted, 4))->toBe(0.1117)
        ->and(abs($converted - 0.1117))->toBeLessThan(0.0001)
        ->and($converted)->not->toBe(0.1117);
});

/*
 * Bukti bahwa kalkulator membaca angka kartu, bukan sekadar menyimpannya.
 *
 * Angka kartu di sini sengaja dibuat jauh dari hasil konversi. Memakai selisih
 * sebenarnya — 0,1117 lawan 0,11174900 — akan membuat tes ini bergantung pada
 * apakah pembulatan naik kebetulan melompat pada harga yang dipilih, dan itu
 * menguji keberuntungan, bukan jalur kodenya. Besarnya selisih yang nyata sudah
 * dijaga tes "stays within one basis point" di atas.
 */
it('prices a tenor from the card instead of converting when the card is loaded', function () {
    $kosong = [12 => null, 24 => null, 36 => null, 48 => null];
    $effective = [60 => 0.1906] + $kosong;

    $hitung = function (ProductConfig $product): int {
        $result = (new DanaTunaiCalculator)->calculate(
            SimulationTestFactory::dtnInput(phpmPrice: 100_000_000),
            SimulationTestFactory::dtnConfig($product),
            2018,
        );

        return $result->forTenor(60)->instalment;
    };

    $tanpaKartu = $hitung(new ProductConfig(
        name: 'Reguler Passenger Referral',
        effectiveRates: $effective,
        adminMax: 5_350_000,
    ));
    $denganKartu = $hitung(new ProductConfig(
        name: 'Reguler Passenger Referral',
        effectiveRates: $effective,
        adminMax: 5_350_000,
        flatRatesAddb: [60 => 0.05] + $kosong,
        flatRatesAddm: [60 => 0.04] + $kosong,
    ));

    expect($denganKartu)->toBeLessThan($tanpaKartu);
});

/* ADDM dan ADDB tidak boleh tertukar: kartu menerbitkan keduanya terpisah. */
it('picks the ADDM column when the instalment is paid up front', function () {
    $kosong = [12 => null, 24 => null, 36 => null, 48 => null];
    $product = new ProductConfig(
        name: 'Reguler Passenger Referral',
        effectiveRates: [60 => 0.1906] + $kosong,
        adminMax: 5_350_000,
        flatRatesAddb: [60 => 0.1117] + $kosong,
        flatRatesAddm: [60 => 0.05] + $kosong,
    );

    $addb = (new DanaTunaiCalculator)->calculate(
        SimulationTestFactory::dtnInput(phpmPrice: 100_000_000, instalmentType: InstalmentType::ADDB),
        SimulationTestFactory::dtnConfig($product),
        2018,
    )->forTenor(60)->instalment;

    $addm = (new DanaTunaiCalculator)->calculate(
        SimulationTestFactory::dtnInput(phpmPrice: 100_000_000, instalmentType: InstalmentType::ADDM),
        SimulationTestFactory::dtnConfig($product),
        2018,
    )->forTenor(60)->instalment;

    expect($addm)->toBeLessThan($addb);
});
