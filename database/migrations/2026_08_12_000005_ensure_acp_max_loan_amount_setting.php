<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('simulation_settings')->where('key', 'acp_max_loan_amount')->exists()) {
            DB::table('simulation_settings')->insert([
                'key' => 'acp_max_loan_amount',
                'value' => '1000000000',
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
