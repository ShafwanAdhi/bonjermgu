<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sale & Leaseback stops being offered (client, 18 August 2026).
 *
 * Its 1.544 rows stay in the offering catalogue: they are what head office
 * published, and the importer would bring them back on the next run anyway.
 * What changes is that the Product choice is now driven by these tokens alone
 * rather than by whatever categories the catalogue happens to contain, so a
 * product Admin has not listed cannot be picked.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sprint_tokens')
            ->where('group_key', 'product')
            ->where('source', 'Sale & Leaseback')
            ->delete();
    }

    public function down(): void
    {
        DB::table('sprint_tokens')->insertOrIgnore([[
            'group_key' => 'product',
            'source' => 'Sale & Leaseback',
            'product_token' => 'MODAL KERJA SALES & LEASEBACK',
            'offering_token' => 'KMK S&L',
            'position' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]]);
    }
};
