<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The August 2026 rate card, taken as one piece per product.
 *
 * Each entry is [effective, flat ADDB, flat ADDM] exactly as MTF printed it in
 * "NEW SIMULASI MULTIGUNA AGUSTUS 2026 NEW - SHARE CABANG.xlsx", sheet SHOW
 * RAHASIA PRODUK. The three belong together: taking the effective rate from one
 * edition and the flat rates from another leaves a table whose own figures
 * disagree, and nothing on screen would show it.
 *
 * Products were matched to card rows by their whole five-tenor effective rate
 * rather than by name, so the identification does not rest on wording. Where
 * several rows carried those rates their flat figures agreed, so the choice of
 * row changes nothing; the row named in each comment is where the values came
 * from. The two Sales Dealer products take the WIRA AGENT rows, which publish
 * flat figures but no effective rate — theirs is left as already loaded.
 *
 * Four Used Car products are deliberately absent: no card row carries their
 * rates, and the rows sharing their names carry visibly different ones (High
 * Benefit is 17,97% here against 14,66% on the card). Assigning those would be
 * a repricing rather than a data refresh, so they keep converting from the
 * effective rate until head office confirms which row is theirs.
 */
return new class extends Migration
{
    /** @var array<string, array<int, array{0: float|null, 1: float|null, 2: float|null}>> */
    private const CARD = [
        // MULTIGUNA CAPTIVE PASSENGER KHUSUS KARYAWAN BANK MANDIRI NASIONAL
        'Captive Passenger Khusus Karyawan Bank Mandiri' => [
            12 => [0.1026, 0.0565, 0.0475],
            24 => [0.0966, 0.0519, 0.0475],
            36 => [0.0939, 0.0505, 0.0475],
            48 => [0.0966, 0.0524, 0.05],
            60 => [0.1037, 0.0572, 0.055],
        ],
        // MULTIGUNA CAPTIVE PASSENGER KHUSUS SME & PRIVATE/PRIORITAS NASIONAL
        'Captive Passenger Khusus SME & Private/Prioritas' => [
            12 => [0.1374, 0.076, 0.0639],
            24 => [0.1345, 0.0731, 0.0668],
            36 => [0.135, 0.074, 0.0695],
            48 => [0.1497, 0.0839, 0.0798],
            60 => [0.1589, 0.0912, 0.0874],
        ],
        // MULTIGUNA CAPTIVE PASSENGER SEMANGAT NASIONAL
        'Captive Passenger Semangat' => [
            12 => [0.1688, 0.0938, 0.0787],
            24 => [0.1636, 0.0897, 0.0817],
            36 => [0.1629, 0.0903, 0.0846],
            48 => [0.1762, 0.1002, 0.0951],
            60 => [0.1804, 0.105, 0.1005],
        ],
        // MULTIGUNA CAPTIVE PASSENGER TENGAH NASIONAL
        'Captive Passenger Tengah' => [
            12 => [0.1769, 0.0984, 0.0825],
            24 => [0.1685, 0.0925, 0.0843],
            36 => [0.1697, 0.0944, 0.0884],
            48 => [0.1784, 0.1015, 0.0964],
            60 => [0.1865, 0.109, 0.1043],
        ],
        // MULTIGUNA CAPTIVE PASSENGER CUAN NASIONAL
        'Captive Passenger Cuan' => [
            12 => [0.1847, 0.1029, 0.0862],
            24 => [0.1733, 0.0953, 0.0868],
            36 => [0.1766, 0.0985, 0.0922],
            48 => [0.1805, 0.1029, 0.0976],
            60 => [0.1926, 0.1131, 0.1081],
        ],
        // MULTIGUNA CAPTIVE COMMERCIAL SEMANGAT NASIONAL
        'Captive Commercial Semangat' => [
            12 => [0.1983, 0.1107, 0.0926],
            24 => [0.1907, 0.1054, 0.0959],
            36 => [0.1931, 0.1085, 0.1015],
            48 => [0.1921, 0.1102, 0.1045],
            60 => [null, null, null],
        ],
        // MULTIGUNA CAPTIVE COMMERCIAL TENGAH NASIONAL
        'Captive Commercial Tengah' => [
            12 => [0.2035, 0.1137, 0.0951],
            24 => [0.1979, 0.1096, 0.0997],
            36 => [0.1999, 0.1126, 0.1053],
            48 => [0.1981, 0.114, 0.1081],
            60 => [null, null, null],
        ],
        // MULTIGUNA CAPTIVE COMMERCIAL CUAN NASIONAL
        'Captive Commercial Cuan' => [
            12 => [0.2088, 0.1167, 0.0976],
            24 => [0.205, 0.1137, 0.1034],
            36 => [0.2065, 0.1167, 0.1091],
            48 => [0.2039, 0.1177, 0.1116],
            60 => [null, null, null],
        ],
        // MULTIGUNA REGULER PASSENGER REFERRAL JAWA
        'Reguler Passenger Referral' => [
            12 => [0.1725, 0.0959, 0.0804],
            24 => [0.175, 0.0962, 0.0877],
            36 => [0.18, 0.1005, 0.0941],
            48 => [0.1867, 0.1068, 0.1013],
            60 => [0.1906, 0.1117, 0.1069],
        ],
        // MULTIGUNA REGULER Passenger KTA REFERRAL Nasional
        'Reguler Passenger TOMI Spesial Referral' => [
            12 => [0.1, 0.0551, 0.0464],
            24 => [0.1025, 0.0552, 0.0505],
            36 => [0.105, 0.0568, 0.0534],
            48 => [0.1075, 0.0588, 0.0561],
            60 => [0.11, 0.061, 0.0574],
        ],
        // MULTIGUNA REGULER COMMERCIAL REFERRAL NASIONAL
        'Reguler Commercial Referral' => [
            12 => [0.2035, 0.1137, 0.0951],
            24 => [0.1955, 0.1082, 0.0984],
            36 => [0.1976, 0.1112, 0.104],
            48 => [0.1963, 0.1129, 0.107],
            60 => [null, null, null],
        ],
        // MULTIGUNA REGULER Passenger WIRA AGENT Nasional
        'Reguler Passenger Sales Dealer' => [
            12 => [null, 0.1029, 0.0862],
            24 => [null, 0.0953, 0.0868],
            36 => [null, 0.0985, 0.0922],
            48 => [null, 0.1029, 0.0976],
            60 => [null, 0.1131, 0.1081],
        ],
        // MULTIGUNA REGULER Commercial WIRA AGENT Nasional
        'Reguler Commercial Sales Dealer' => [
            12 => [null, 0.1167, 0.0976],
            24 => [null, 0.1137, 0.1034],
            36 => [null, 0.1167, 0.1091],
            48 => [null, 0.1177, 0.1116],
            60 => [null, null, null],
        ],
    ];

    public function up(): void
    {
        $this->apply(function (array $rate): array {
            $columns = ['flat_rate_addb' => $rate[1], 'flat_rate_addm' => $rate[2]];

            // A card row without an effective rate must not blank the one we
            // already hold; the WIRA AGENT rows publish only the flat figures.
            if ($rate[0] !== null) {
                $columns['effective_rate'] = $rate[0];
            }

            return $columns;
        });
    }

    /** Only the card columns are dropped; the effective rate is left as loaded. */
    public function down(): void
    {
        $this->apply(fn (): array => ['flat_rate_addb' => null, 'flat_rate_addm' => null]);
    }

    private function apply(callable $columns): void
    {
        foreach (self::CARD as $product => $rates) {
            $productId = DB::table('products')->where('name', $product)->value('id');

            if ($productId === null) {
                continue;
            }

            foreach ($rates as $tenor => $rate) {
                DB::table('product_rates')
                    ->where('product_id', $productId)
                    ->where('tenor_months', $tenor)
                    ->update($columns($rate));
            }
        }
    }
};
