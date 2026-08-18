<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames from head office, and the last dimensions View Sprint had to ask for.
 *
 * Kanal follows the referral category's tier, with the sub-category overriding
 * it where a category serves more than one: Karyawan Internal is Referral
 * except for Graha Sultan, which is Telemarketing. Captive Internal is
 * deliberately unmapped — head office does not use those channels
 * (client, 18 August 2026).
 *
 * GAP, HIC and Water Hammer become real insurance extensions rather than
 * yes/no notes on the sheet. Their rates arrive at nought because head office
 * has not issued them yet; the Admin insurance screen is where they land, and
 * nothing changes in any premium until they do.
 */
return new class extends Migration
{
    private const TIER_CHANNELS = [
        'Semangat' => 'Captive NJF Semangat',
        'Tengah' => 'Captive NJF Tengah',
        'Cuan' => 'Captive NJF Cuan',
        'Sales Dealer' => 'Wira Agent',
        'Referral' => 'Referral',
    ];

    private const NEW_EXTENSIONS = ['gap', 'hic', 'water_hammer'];

    public function up(): void
    {
        DB::table('sprint_tokens')
            ->where('group_key', 'channel')
            ->where('source', 'Captive NJF Tangguh')
            ->update(['source' => 'Captive NJF Tengah', 'offering_token' => 'CAPTIVE NJF TNGH']);

        DB::table('sprint_tokens')
            ->where('group_key', 'channel_source')
            ->where('offering_token', 'Captive NJF Tangguh')
            ->update(['offering_token' => 'Captive NJF Tengah']);

        DB::table('referral_categories')
            ->where('name', 'Karyawan Internal & Captive')
            ->update(['name' => 'Karyawan Internal']);

        // EV never appears in the offering catalogue, and Brand is no longer
        // asked for at all: it follows the unit the simulation already knows.
        DB::table('sprint_tokens')->where('group_key', 'brand')->where('source', 'EV')->delete();

        $now = now();
        $position = 0;

        foreach (self::TIER_CHANNELS as $tier => $channel) {
            DB::table('sprint_tokens')->updateOrInsert(
                ['group_key' => 'channel_tier', 'source' => $tier],
                [
                    'product_token' => null,
                    'offering_token' => $channel,
                    'position' => $position++,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        // The code list is pinned by a CHECK constraint, so it has to widen
        // before the three new rows will go in.
        $this->constrainExtensionCodes([
            'banjir', 'gempa', 'huru_hara', 'teroris', 'pengemudi', 'penumpang',
            ...self::NEW_EXTENSIONS,
        ]);

        foreach (self::NEW_EXTENSIONS as $code) {
            DB::table('insurance_extension_rates')->updateOrInsert(['code' => $code], ['rate' => 0]);
        }
    }

    public function down(): void
    {
        DB::table('sprint_tokens')
            ->where('group_key', 'channel')
            ->where('source', 'Captive NJF Tengah')
            ->update(['source' => 'Captive NJF Tangguh', 'offering_token' => 'CAPTIVE NJF TNGH']);

        DB::table('sprint_tokens')
            ->where('group_key', 'channel_source')
            ->where('offering_token', 'Captive NJF Tengah')
            ->update(['offering_token' => 'Captive NJF Tangguh']);

        DB::table('referral_categories')
            ->where('name', 'Karyawan Internal')
            ->update(['name' => 'Karyawan Internal & Captive']);

        DB::table('sprint_tokens')->where('group_key', 'channel_tier')->delete();
        DB::table('insurance_extension_rates')->whereIn('code', self::NEW_EXTENSIONS)->delete();

        $this->constrainExtensionCodes(['banjir', 'gempa', 'huru_hara', 'teroris', 'pengemudi', 'penumpang']);
    }

    /** @param  array<int, string>  $codes */
    private function constrainExtensionCodes(array $codes): void
    {
        $list = implode(', ', array_map(fn (string $code): string => "'{$code}'", $codes));

        DB::statement('ALTER TABLE insurance_extension_rates DROP CONSTRAINT IF EXISTS insurance_extension_rates_code_check');
        DB::statement("ALTER TABLE insurance_extension_rates ADD CONSTRAINT insurance_extension_rates_code_check CHECK (code IN ({$list}))");
    }
};
