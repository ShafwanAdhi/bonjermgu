<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wilayah stops being a question and becomes a setting.
 *
 * Every application these branches write is Jawa, so asking the AO on every
 * sheet was a dropdown with one real answer (client, 18 August 2026). It stays
 * a setting rather than a constant because the value is a fact about where the
 * branches operate today, not about the product — and the offering catalogue
 * already carries Non Jawa rows for the day that changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('simulation_settings')->insertOrIgnore([
            ['key' => 'view_sprint_region', 'value' => 'Jawa'],
        ]);
    }

    public function down(): void
    {
        DB::table('simulation_settings')->where('key', 'view_sprint_region')->delete();
    }
};
