<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The eleven fixed tracking stages — docs/application-tracking.md section 4.
 *
 * Count and names are fixed and not configurable. Stage 11 is special: marking
 * it Selesai is what records the Go Live date and moves the application into
 * Actual Lending.
 *
 * Idempotent.
 */
class TrackingStageSeeder extends Seeder
{
    private const STAGES = [
        'Verifikasi, Validasi dan kelengkapan data permohonan',
        'Survey Domisili & Tempat usaha/Kantor',
        'Cek fisik Kendaraan',
        'Laporan Hasil Survey',
        'Proses Aplikasi',
        'Konfirmasi Debitur by phone & Scoring Credit',
        'Permohonan Persetujuan Pembiayaan',
        'Persetujuan Pembiayaan (PO)',
        'Verifikasi, Validasi dan kelengkapan Jaminan (BPKB)',
        'Konfirmasi Debitur by phone',
        'Golive & Payment',
    ];

    public function run(): void
    {
        $rows = [];

        foreach (self::STAGES as $index => $name) {
            $rows[] = ['stage_no' => $index + 1, 'name' => $name];
        }

        DB::table('tracking_stages')->upsert($rows, ['stage_no'], ['name']);

        $this->command?->info(sprintf('Tracking stages: %d tahapan.', count($rows)));
    }
}
