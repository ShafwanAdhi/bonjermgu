<?php

use App\Domain\Simulation\CoverageType;
use App\Domain\Simulation\DanaTunaiCalculator;
use App\Domain\Simulation\DebtorType;
use App\Domain\Simulation\FinancingType;
use App\Domain\Simulation\Input\SimulationInput;
use App\Domain\Simulation\InstalmentType;
use App\Domain\Simulation\Output\SimulationResult;
use App\Domain\Simulation\SimulationMode;
use App\Domain\Simulation\StnkOwnership;
use App\Domain\Simulation\VehicleOrigin;
use App\Domain\Simulation\VehicleUsage;
use App\Models\Product;
use App\Repositories\SimulationConfigurationRepository;
use Database\Seeders\ReferralMasterSeeder;
use Database\Seeders\SimulationConfigurationSeeder;

/*
 * Nilai acuan dari workbook yang dipakai cabang hari ini,
 * "NEW SIMULASI MULTIGUNA AGUSTUS 2026 NEW - SHARE CABANG.xlsx", sheet
 * EDIT SIMULASI baris 35-63, kolom E sampai I.
 *
 * Berbeda dari 250 vektor di tests/Unit/Simulation: yang itu membangun
 * konfigurasinya sendiri di dalam berkas tes, jadi ia menguji aritmetika domain
 * dan tidak pernah menyentuh basis data. Yang ini berangkat dari konfigurasi
 * yang benar-benar dimuat aplikasi, sehingga rate, tarif casco, jadwal
 * depresiasi, dan tabel biaya ikut teruji — salah ketik pada layar Admin akan
 * tertangkap di sini, bukan di sana.
 *
 * Skenario workbook: HONDA BRIO ALL NEW BRIO RS CVT 2017, Taksasi OTR
 * 110.000.026, OTR Pengajuan 110.000.000, Reguler Passenger Referral, Passenger
 * Jepang, perorangan non wiraswasta, kelompok usia 36-45 (upping ACP 30%),
 * ADDB, Comprehensive tahun pertama lalu TLO, garansi mesin YA, tanpa perluasan
 * dan tanpa TJH, Wilayah 2, Batas Bawah.
 */

beforeEach(function () {
    // Kelompok usia lahir dari master referral, dan upping ACP menggantung padanya.
    $this->seed(ReferralMasterSeeder::class);
    $this->seed(SimulationConfigurationSeeder::class);
});

function augustWorkbookResult(): SimulationResult
{
    $config = app(SimulationConfigurationRepository::class)->forProduct(
        Product::query()->where('name', 'Reguler Passenger Referral')->firstOrFail(),
        'Batas Bawah',
    );

    $input = new SimulationInput(
        financingType: FinancingType::DTN,
        mode: SimulationMode::A,
        debtorType: DebtorType::NON_ENTREPRENEUR,
        ageGroup: '36-45 tahun',
        vehicleUsage: VehicleUsage::PASSENGER,
        vehicleOrigin: VehicleOrigin::JAPAN,
        stnkOwnership: StnkOwnership::OWN,
        vehicleYear: 2017,
        phpmPrice: 110_000_026,
        instalmentType: InstalmentType::ADDB,
        coverageType: CoverageType::COMPREHENSIVE_THEN_TLO,
        engineWarrantyEnabled: true,
    );

    // Tahun berjalan dipaku: kelayakan unit dan loading bergantung pada umur
    // kendaraan, dan tes yang memakai tahun sekarang akan merah saat pergantian
    // tahun tanpa ada yang berubah.
    return (new DanaTunaiCalculator)->calculate($input, $config, 2026);
}

it('matches every figure the branch workbook shows for its own scenario', function (string $label, array $expected) {
    $result = augustWorkbookResult();

    $actual = collect($expected)->map(function (int $_, int $tenor) use ($label, $result): int {
        $row = $result->forTenor($tenor);

        return match ($label) {
            'Net DP' => (int) round($row->netDpAmount),
            'Pokok Hutang' => (int) round($row->ltvAmount),
            'Angsuran' => $row->instalment,
            'Asuransi' => $row->insurance->total,
            'Administrasi' => (int) round($row->fees->administration),
            'Fiducia' => (int) round($row->fees->fiducia),
            'Provisi' => (int) round($row->fees->provision),
            'Total Bayar Pertama' => (int) round($row->firstPayment),
            'Pencairan' => (int) round($row->netDisbursement),
        };
    })->all();

    expect($actual)->toEqual($expected);
})->with([
    ['Net DP', [12 => 5_500_000, 24 => 5_500_000, 36 => 5_500_000, 48 => 5_500_000, 60 => 5_500_000]],
    ['Pokok Hutang', [12 => 104_500_000, 24 => 104_500_000, 36 => 104_500_000, 48 => 104_500_000, 60 => 104_500_000]],
    ['Angsuran', [12 => 9_544_000, 24 => 5_192_000, 36 => 3_778_000, 48 => 3_108_000, 60 => 2_715_000]],
    ['Asuransi', [12 => 6_518_200, 24 => 7_876_700, 36 => 9_206_600, 48 => 10_722_400, 60 => 12_138_100]],
    ['Administrasi', [12 => 5_350_000, 24 => 5_350_000, 36 => 5_350_000, 48 => 5_350_000, 60 => 5_350_000]],
    ['Fiducia', [12 => 500_000, 24 => 500_000, 36 => 500_000, 48 => 500_000, 60 => 500_000]],
    ['Provisi', [12 => 0, 24 => 0, 36 => 0, 48 => 0, 60 => 0]],
    ['Total Bayar Pertama', [12 => 17_868_200, 24 => 19_226_700, 36 => 20_556_600, 48 => 22_072_400, 60 => 23_488_100]],
    ['Pencairan', [12 => 92_131_800, 24 => 90_773_300, 36 => 89_443_400, 48 => 87_927_600, 60 => 86_511_900]],
]);

/*
 * Kartu rate harus sampai ke konfigurasi yang dipakai aplikasi, bukan berhenti
 * di tabel. Sebelum ini ia dimuat lewat migrasi yang berjalan sebelum seeder,
 * jadi migrate:fresh --seed menghapusnya tanpa jejak.
 */
it('carries the published card into the configuration the app loads', function () {
    $config = app(SimulationConfigurationRepository::class)->forProduct(
        Product::query()->where('name', 'Reguler Passenger Referral')->firstOrFail(),
        'Batas Bawah',
    );

    expect($config->product->flatRateFor(60, InstalmentType::ADDB))->toBe(0.1117)
        ->and($config->product->flatRateFor(60, InstalmentType::ADDM))->toBe(0.1069)
        ->and($config->product->effectiveRateFor(60))->toBe(0.1906);
});
