<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The 26-row document catalogue, transcribed from
 * docs/document-requirement.md section 6.
 *
 * Codes are stable identifiers: application_documents point at them. Changing
 * a code here orphans every status that references it, so treat this list as
 * append-only unless the document itself changes.
 *
 * Idempotent.
 */
class DocumentRequirementSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: string}> */
    private const PERORANGAN = [
        ['PMH-KTP', 'KTP Pemohon', 'Pemohon'],
        ['PSG-KTP', 'KTP Pasangan', 'Pasangan'],
        ['PMH-NPWP', 'NPWP Pemohon', 'Pemohon'],
        ['PMH-KK', 'Kartu Keluarga', 'Pemohon'],
        ['PMH-RUMAH', 'Bukti Kepemilikan Rumah (SPPT PBB / AJB / SHM)', 'Pemohon'],
        ['PMH-SLIP', 'Slip Gaji Carbonized', 'Pemohon'],
        ['PMH-RKR', 'Rek Koran 3 bulan terakhir', 'Pemohon'],
        ['PMH-USAHA', 'Legalitas Usaha (NIB / SKDU)', 'Pemohon'],
        ['PMH-FAKTUR', 'Bon / Faktur Penjualan', 'Pemohon'],
        ['PMH-PROFESI', 'Surat Ijin Profesi', 'Pemohon'],
    ];

    /** @var array<int, array{0: string, 1: string, 2: string}> */
    private const BADAN_HUKUM = [
        ['KOM-KTP', 'KTP Komisaris', 'Komisaris'],
        ['DIR-KTP', 'KTP Direksi', 'Direksi'],
        ['DIR-NPWP', 'NPWP Direksi', 'Direksi'],
        ['BDN-NPWP', 'NPWP Usaha', 'Badan Usaha'],
        ['BDN-AKTA-DIR', 'Akte Pendirian', 'Badan Usaha'],
        ['BDN-AKTA-UBH', 'Akte Perubahan', 'Badan Usaha'],
        ['BDN-SKKUM', 'SK Kemenkumham', 'Badan Usaha'],
        ['BDN-NIB', 'Legalitas Usaha (NIB & Ijin Usaha khusus)', 'Badan Usaha'],
        ['BDN-LAPKEU', 'Laporan Keuangan', 'Badan Usaha'],
        ['BDN-RKR', 'Rek Koran 3 bulan terakhir', 'Badan Usaha'],
        ['BDN-SPK', 'SPK / MOU', 'Badan Usaha'],
    ];

    /** @var array<int, array{0: string, 1: string, 2: string}> */
    private const PASANGAN = [
        ['PSG-RKR', 'Rek Koran 3 bulan terakhir', 'Pasangan'],
        ['PSG-SLIP', 'Slip Gaji Carbonized', 'Pasangan'],
        ['PSG-USAHA', 'Legalitas Usaha (NIB / SKDU)', 'Pasangan'],
        ['PSG-FAKTUR', 'Bon / Faktur Penjualan', 'Pasangan'],
        ['PSG-PROFESI', 'Surat Ijin Profesi', 'Pasangan'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $rows = [];

            foreach ([
                'Perorangan' => self::PERORANGAN,
                'Badan Hukum Usaha' => self::BADAN_HUKUM,
                'Pasangan' => self::PASANGAN,
            ] as $group => $requirements) {
                foreach ($requirements as $index => [$code, $name, $subject]) {
                    $rows[] = [
                        'code' => $code,
                        'name' => $name,
                        'subject' => $subject,
                        'group_name' => $group,
                        'sort_order' => $index + 1,
                    ];
                }
            }

            DB::table('document_requirements')->upsert(
                $rows,
                ['code'],
                ['name', 'subject', 'group_name', 'sort_order'],
            );

            $this->command?->info(
                sprintf('Document requirements: %d requirement.', count($rows))
            );
        });
    }
}
