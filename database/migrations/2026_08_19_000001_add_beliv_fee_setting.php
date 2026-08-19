<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('simulation_settings')->insertOrIgnore([
            ['key' => 'beliv_fee_amount', 'value' => '0'],
        ]);
    }

    public function down(): void
    {
        DB::table('simulation_settings')->where('key', 'beliv_fee_amount')->delete();
    }
};
