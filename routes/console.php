<?php

use App\Support\SprintOfferingWorkbookImporter;
use Illuminate\Support\Facades\Artisan;

// Production scheduler tasks can be registered here when needed.

Artisan::command('sprint:import-offerings {--path= : Path workbook SPRINT utama}', function (): int {
    $path = $this->option('path') ?: base_path('NEW SIMULASI MULTIGUNA AGUSTUS 2026 NEW - SHARE CABANG.xlsx');

    if (! is_string($path) || $path === '') {
        $this->error('Path workbook SPRINT wajib diisi.');

        return 1;
    }

    $result = app(SprintOfferingWorkbookImporter::class)->import($path);

    $this->info("Imported {$result['imported']} SPRINT offering rows.");

    return 0;
})->purpose('Import Product ID dan Product Offering SPRINT dari workbook utama.');
