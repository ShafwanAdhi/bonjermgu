<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_categories', function (Blueprint $table) {
            $table->boolean('allows_passenger')->default(true);
            $table->boolean('allows_commercial')->default(true);
        });

        DB::table('referral_categories')
            ->where('code', 'SRB')
            ->where('tier', 'Referral C2C')
            ->update(['tier' => 'Referral']);

        DB::table('referral_categories')
            ->where('code', 'CIN')
            ->update(['allows_commercial' => false]);

        DB::statement(
            'ALTER TABLE referral_categories ADD CONSTRAINT referral_categories_usage_check
             CHECK (allows_passenger OR allows_commercial)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE referral_categories DROP CONSTRAINT IF EXISTS referral_categories_usage_check'
        );

        DB::table('referral_categories')
            ->where('code', 'SRB')
            ->where('tier', 'Referral')
            ->update(['tier' => 'Referral C2C']);

        Schema::table('referral_categories', function (Blueprint $table) {
            $table->dropColumn(['allows_passenger', 'allows_commercial']);
        });
    }
};
