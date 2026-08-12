<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Up ACP was applied twice: once through the age-group upping table and again
 * through this per-product column, inflating every ACP premium the moment an
 * admin set it. The age-group table is the single source of upping.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_up_acp_check');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('up_acp');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('up_acp', 6, 4)->default(0);
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_up_acp_check CHECK (up_acp BETWEEN 0 AND 1)');
    }
};
