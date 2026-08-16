<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The flat rates MTF prints on its rate card, stored rather than re-derived.
 *
 * Each product on the card carries three figures per tenor: the effective rate
 * and the two flat rates, ADDB and ADDM. Branches quote instalments from the
 * flat figures. We held only the effective one and computed the flat at run
 * time, which cannot land back on the printed value: the card rounds to four
 * decimals, so 19,06% published becomes 11,17% printed while our conversion
 * gives 11,1749%. Rounding the instalment up to the nearest thousand then turns
 * that fifth decimal into a full 1.000 rupiah, on most prices at 48 and 60
 * months (client, 16 August 2026).
 *
 * Both columns stay nullable. A product without card figures keeps converting
 * from the effective rate, so nothing moves until the card is entered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_rates', function (Blueprint $table): void {
            $table->decimal('flat_rate_addb', 12, 10)->nullable()->after('effective_rate');
            $table->decimal('flat_rate_addm', 12, 10)->nullable()->after('flat_rate_addb');
        });

        foreach (['addb', 'addm'] as $type) {
            DB::statement(
                "ALTER TABLE product_rates ADD CONSTRAINT product_rates_flat_rate_{$type}_check "
                ."CHECK (flat_rate_{$type} IS NULL OR flat_rate_{$type} BETWEEN 0 AND 1)"
            );
        }
    }

    public function down(): void
    {
        foreach (['addb', 'addm'] as $type) {
            DB::statement("ALTER TABLE product_rates DROP CONSTRAINT IF EXISTS product_rates_flat_rate_{$type}_check");
        }

        Schema::table('product_rates', function (Blueprint $table): void {
            $table->dropColumn(['flat_rate_addb', 'flat_rate_addm']);
        });
    }
};
