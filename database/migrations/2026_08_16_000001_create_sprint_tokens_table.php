<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vocabulary SPRINT uses to spell a Product ID and a Product Offering.
 *
 * The offering workbook builds both codes by gluing one token per dimension:
 *
 *   Product ID = product + channel + unit + profile + debtor type + " - " + instalment
 *   Offering   = product + region + channel + unit + brand + profile
 *                + debtor type + DP + tenor + " - " + instalment
 *
 * Each dimension therefore carries two spellings, and they are rarely the same
 * word: "C2C Investasi PPSA" is INVESTASI PPSA C2C on the Product ID side and
 * INV PPSA C2C on the Offering side. Both live here so a new product or channel
 * is a data change rather than a deploy.
 *
 * Tokens come from the offering data itself, not from the Master sheet's
 * helper cells. Two of those cells disagree with every row they claim to
 * describe: G13 is the literal "REGULER" although the descriptions carry
 * WIRA and CAPTIVE NJF REWARD too, and I10 spells Fasilitas Modal Usaha
 * "KMK MDL USAHA" against "KMK FLT MDL USAHA" in all 966 of its rows.
 */
return new class extends Migration
{
    /** @var array<string, array<int, array{0: string, 1: ?string, 2: ?string}>> */
    private const TOKENS = [
        // source, Product ID token, Offering token
        'product' => [
            ['Fasilitas Dana', 'MULTIGUNA FASILITAS DANA', 'MGU FLT DN'],
            ['Fasilitas Modal Usaha', 'MODAL KERJA FASILITAS MODAL USAHA', 'KMK FLT MDL USAHA'],
            ['Sale & Leaseback', 'MODAL KERJA SALES & LEASEBACK', 'KMK S&L'],
            ['C2C Multiguna PPSA', 'MULTIGUNA PPSA C2C', 'MGU PPSA C2C'],
            ['C2C Investasi PPSA', 'INVESTASI PPSA C2C', 'INV PPSA C2C'],
        ],
        'region' => [
            ['Jawa', null, 'JAWA'],
            ['Non Jawa', null, 'NON-JAWA'],
        ],
        'channel' => [
            ['Referral', 'REGULER', 'REFERRAL'],
            ['Reguler', 'REGULER', 'REGULER'],
            ['Telemarketing', 'REGULER', 'TELEMARKETING'],
            ['Wira Agent', 'WIRA', 'WIRA AGENT'],
            ['Captive NJF Semangat', 'CAPTIVE NJF REWARD', 'CAPTIVE NJF SMGT'],
            ['Captive NJF Tangguh', 'CAPTIVE NJF REWARD', 'CAPTIVE NJF TNGH'],
            ['Captive NJF Cuan', 'CAPTIVE NJF REWARD', 'CAPTIVE NJF CUAN'],
        ],
        // Sub-category the AO already picked in the simulation, mapped to the
        // channel above so the dropdown starts on the right entry. Only the
        // sub-categories the workbook names are here; anything else asks the AO.
        'channel_source' => [
            ['Pemegang WMK', null, 'Referral'],
            ['Team AR', null, 'Referral'],
            ['Team Credit Risk', null, 'Referral'],
            ['Team Operation', null, 'Referral'],
            ['Graha Sultan', null, 'Telemarketing'],
            ['Captive S', null, 'Captive NJF Semangat'],
            ['Captive M', null, 'Captive NJF Tangguh'],
            ['Captive L', null, 'Captive NJF Cuan'],
            ['Wira Sales Dealer', null, 'Wira Agent'],
        ],
        'unit' => [
            ['Passenger', 'PASSENGER', 'PASS'],
            ['Pick Up', 'PICK UP', 'PICK UP'],
            ['Truck', 'TRUCK', 'TRUCK'],
        ],
        // Brand never reaches the Product ID; only the Offering carries it.
        'brand' => [
            ['Japan', null, 'JPN'],
            ['Non Japan', null, 'NON-JPN'],
            ['EV', null, 'EV'],
        ],
        'profile' => [
            ['Perorangan Non Wiraswasta', 'PERORANGAN', 'PERORANGAN'],
            ['Perorangan Wiraswasta', 'PERORANGAN', 'PERORANGAN'],
            ['Badan Hukum Usaha', 'BADAN USAHA', 'B.USAHA'],
        ],
        'debtor_type' => [
            ['New Customer', 'NEW CUST & ROMI', 'NEW&ROMI'],
            ['Repeat Order', 'NEW CUST & ROMI', 'NEW&ROMI'],
            ['Additional Order', 'TOMI', 'TOMI'],
        ],
        // Bands exposed by Master!C34 plus bands found in the offering sheets.
        // DP25 appears in the Master dropdown even though the current offering
        // rows do not use it; keeping it here preserves the workbook UI.
        'dp' => [
            ['DP5', null, 'DP5'],
            ['DP10', null, 'DP10'],
            ['DP15', null, 'DP15'],
            ['DP20', null, 'DP20'],
            ['DP25', null, 'DP25'],
            ['DP30', null, 'DP30'],
            ['DP40', null, 'DP40'],
            ['DP50', null, 'DP50'],
        ],
        'tenor' => [
            ['1TH', null, '1TH'],
            ['2TH', null, '2TH'],
            ['3TH', null, '3TH'],
            ['4TH', null, '4TH'],
            ['5TH', null, '5TH'],
        ],
        'instalment' => [
            ['ADDB', 'ADDB', 'ADDB'],
            ['ADDM', 'ADDM', 'ADDM'],
        ],
    ];

    public function up(): void
    {
        Schema::create('sprint_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('group_key', 24);
            $table->string('source', 64);
            $table->string('product_token', 64)->nullable();
            $table->string('offering_token', 64)->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['group_key', 'source']);
            $table->index(['group_key', 'position']);
        });

        $now = now();
        $rows = [];

        foreach (self::TOKENS as $group => $tokens) {
            foreach ($tokens as $position => [$source, $product, $offering]) {
                $rows[] = [
                    'group_key' => $group,
                    'source' => $source,
                    'product_token' => $product,
                    'offering_token' => $offering,
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('sprint_tokens')->insert($rows);

        // Splits Dana Tunai between Fasilitas Dana and Fasilitas Modal Usaha
        // (client, 15 August 2026). A threshold, not a calculation input, but it
        // belongs with the other tunable figures rather than in code.
        DB::table('simulation_settings')->insertOrIgnore([
            ['key' => 'view_sprint_modal_usaha_threshold', 'value' => '500000000'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_tokens');

        DB::table('simulation_settings')->where('key', 'view_sprint_modal_usaha_threshold')->delete();
    }
};
