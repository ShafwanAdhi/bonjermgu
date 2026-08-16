<?php

namespace Database\Seeders;

use App\Models\SprintOffering;
use App\Support\SprintOfferingWorkbookImporter;
use Illuminate\Database\Seeder;

/**
 * The SPRINT offering catalogue, so a fresh install has one.
 *
 * It used to arrive only through `php artisan sprint:import-offerings`, which
 * meant `migrate:fresh --seed` emptied the table and View Sprint quietly fell
 * back to composing codes from tokens — showing "Product ID belum tersedia"
 * with nothing on screen to say the catalogue itself was gone.
 *
 * Re-importing is safe: the importer replaces the table wholesale, and rows
 * carry a fingerprint of their source sheet and row.
 */
class SprintOfferingSeeder extends Seeder
{
    public const WORKBOOK = 'NEW SIMULASI MULTIGUNA AGUSTUS 2026 NEW - SHARE CABANG.xlsx';

    public function run(): void
    {
        $path = base_path(self::WORKBOOK);

        if (! is_file($path)) {
            $this->command?->warn('Workbook SPRINT tidak ada; katalog offering dilewati.');

            return;
        }

        $result = app(SprintOfferingWorkbookImporter::class)->import($path);

        $this->command?->info(sprintf('%s baris offering SPRINT diimpor.', number_format($result['imported'])));
    }

    /** Whether the catalogue is already loaded, for callers that only want it present. */
    public static function alreadyLoaded(): bool
    {
        return SprintOffering::query()->exists();
    }
}
