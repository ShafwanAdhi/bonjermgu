<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retires the `Referral C2C` tier value.
 *
 * BUSINESS RULE, confirmed by the client: Referral C2C is not a distinct
 * category. It carries no pricing, product, or process difference from
 * Referral, so `Referral` is the single canonical tier value.
 *
 * This is not a technical workaround for a lookup that failed. The two were
 * never two things.
 *
 * Migration 2026_08_04_000007 already normalised the one seeded row (SRB).
 * This one is unscoped on purpose: an Admin could have typed the old value
 * into any category through the Master Referral screen, and other
 * environments may carry rows the earlier migration never saw.
 *
 * The CHECK constraint stops the value coming back. The tier feeds the
 * Product name (`segment + usage + tier`), so a stray `Referral C2C` would
 * make the resolver look for a Product that does not and will not exist,
 * and every simulation for that category would fail.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('referral_categories')
            ->where('tier', 'Referral C2C')
            ->update(['tier' => 'Referral']);

        DB::statement(
            "ALTER TABLE referral_categories ADD CONSTRAINT referral_categories_tier_retired_check
             CHECK (tier <> 'Referral C2C')"
        );
    }

    /**
     * Drops the constraint only. The data is deliberately NOT reverted:
     * restoring a retired value would leave categories pointing at a Product
     * that does not exist. Rolling this back returns the schema, not the
     * discarded business distinction.
     */
    public function down(): void
    {
        DB::statement(
            'ALTER TABLE referral_categories DROP CONSTRAINT IF EXISTS referral_categories_tier_retired_check'
        );
    }
};
