<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Starting values for the View Sprint fields the engine cannot know.
 *
 * View Sprint calculates nothing — it re-presents an Officer simulation in the
 * layout the inputter screenshots for head office. These eight are the entries
 * SPRINT still asks for; the AO may change any of them before exporting.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'view_sprint_cara_pembayaran' => 'AUTO COLLECTION',
        'view_sprint_mandiri_kpm' => 'NO',
        'view_sprint_kondisi_kendaraan' => 'USED CAR',
        'view_sprint_is_beliv' => 'TIDAK',
        'view_sprint_acp_axp' => 'ADA',
        'view_sprint_gap' => 'NO',
        'view_sprint_hic' => 'NO',
        'view_sprint_water_hammer' => 'NO',
    ];

    public function up(): void
    {
        DB::table('simulation_settings')->insertOrIgnore(
            array_map(
                fn (string $key, string $value): array => compact('key', 'value'),
                array_keys(self::DEFAULTS),
                array_values(self::DEFAULTS),
            ),
        );
    }

    public function down(): void
    {
        DB::table('simulation_settings')->whereIn('key', array_keys(self::DEFAULTS))->delete();
    }
};
