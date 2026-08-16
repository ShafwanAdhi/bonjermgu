<?php

use App\Models\SprintOffering;
use App\Support\SprintOfferingWorkbookImporter;

/*
 * Mengunci jumlah baris yang terbaca dari tiap sheet workbook SPRINT.
 *
 * Impor pertama kehilangan 1.756 dari 1.800 baris "new CAPTIVE" tanpa satu pun
 * pesan: sheet itu memuat dua kolom produk, dan importer membaca picker list
 * 44 baris alih-alih katalognya. Suite tetap hijau seluruhnya waktu itu, karena
 * tidak ada yang memeriksa berapa yang seharusnya masuk.
 */

const SPRINT_WORKBOOK = 'NEW SIMULASI MULTIGUNA AGUSTUS 2026 NEW - SHARE CABANG.xlsx';

/** @return array<string, int> */
function expectedSprintRows(): array
{
    return [
        'new REFERRAL' => 600,
        'new CAPTIVE' => 1800,
        'new WIRA BISNIS' => 552,
        'new TELEMARKETING' => 356,
        'new SHOWROOM' => 40,
    ];
}

beforeEach(function () {
    if (! is_file(base_path(SPRINT_WORKBOOK))) {
        $this->markTestSkipped('Workbook SPRINT tidak ada di root repo.');
    }
});

it('reads every catalogue row of every offering sheet', function () {
    $rows = collect(app(SprintOfferingWorkbookImporter::class)->extract(base_path(SPRINT_WORKBOOK)));

    expect($rows->groupBy('source_sheet')->map->count()->all())
        ->toEqual(expectedSprintRows());
});

/*
 * Kolom "Product ID" 44 baris di new CAPTIVE adalah picker list, bukan data
 * baris. Membacanya menghasilkan ejaan pendek yang berbeda dari empat sheet
 * lain, dan menyembunyikan seluruh katalognya.
 */
it('takes the catalogue column of new CAPTIVE, not its picker list', function () {
    $captive = collect(app(SprintOfferingWorkbookImporter::class)->extract(base_path(SPRINT_WORKBOOK)))
        ->where('source_sheet', 'new CAPTIVE')
        ->firstWhere('source_row', 5);

    expect($captive['product_id'])->toStartWith('MULTIGUNA FASILITAS DANA CAPTIVE NJF REWARD')
        ->and($captive['product_id'])->not->toBe('FASILITAS DANA PASSENGER REWARD CAPTIVE NJF NEW CUSTOMER - ADDB');
});

it('stores what it read', function () {
    $result = app(SprintOfferingWorkbookImporter::class)->import(base_path(SPRINT_WORKBOOK));

    expect($result['imported'])->toBe(array_sum(expectedSprintRows()))
        ->and(SprintOffering::count())->toBe(array_sum(expectedSprintRows()));
});
