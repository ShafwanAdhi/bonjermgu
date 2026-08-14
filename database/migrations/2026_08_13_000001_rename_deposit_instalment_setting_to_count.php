<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deposit Angsuran is a count of instalments withheld, not a rupiah figure.
 * The stakeholder pointed out the screen was asking for the wrong kind of
 * number on 13 August 2026.
 *
 * Any stored rupiah value is meaningless as a count, so it resets to 0 rather
 * than being carried across — a leftover 5000000 would otherwise be read as
 * five million instalments.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('simulation_settings')
            ->where('key', 'default_deposit_instalment_amount')
            ->delete();

        DB::table('simulation_settings')->insertOrIgnore([
            ['key' => 'default_deposit_instalment_count', 'value' => '0'],
        ]);
    }

    public function down(): void
    {
        DB::table('simulation_settings')
            ->where('key', 'default_deposit_instalment_count')
            ->delete();

        DB::table('simulation_settings')->insertOrIgnore([
            ['key' => 'default_deposit_instalment_amount', 'value' => '0'],
        ]);
    }
};
