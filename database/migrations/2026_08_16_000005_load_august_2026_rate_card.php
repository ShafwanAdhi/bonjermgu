<?php

use Database\Seeders\SimulationConfigurationSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Loads the August 2026 rate card into a database that is already seeded.
 *
 * The figures live in database/seeders/data/august_2026_rate_card.json and the
 * seeder owns them, because a fresh install runs the seeder after every
 * migration: anything written here would be overwritten moments later and the
 * card would silently never arrive. This migration serves the other path, an
 * existing database that must pick the card up without a destructive re-seed,
 * and it calls the seeder rather than restating 195 figures that would then be
 * free to drift apart.
 *
 * Products were matched to card rows by their whole five-tenor effective rate
 * rather than by name, so the identification does not rest on wording. The two
 * Sales Dealer products take the WIRA AGENT rows, which publish flat figures
 * but no effective rate; theirs is left as already loaded.
 *
 * Four Used Car products are deliberately absent: no card row carries their
 * rates, and the rows sharing their names carry visibly different ones (High
 * Benefit is 17,97% here against 14,66% on the card). Assigning those would be
 * a repricing rather than a data refresh, so they keep converting from the
 * effective rate until head office confirms which row is theirs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Nothing to overlay before the products exist; a fresh install reaches
        // the card through the seeder instead.
        if (! DB::table('products')->exists()) {
            return;
        }

        SimulationConfigurationSeeder::applyAugustRateCard();
    }

    /** Only the card columns are dropped; the effective rate is left as loaded. */
    public function down(): void
    {
        DB::table('product_rates')->update(['flat_rate_addb' => null, 'flat_rate_addm' => null]);
    }
};
