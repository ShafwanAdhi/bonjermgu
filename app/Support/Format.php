<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Display formatting — docs/pages.md section 17.
 *
 * Indonesian conventions: dot for thousands, comma for decimals.
 *
 * These functions format an already-final number. They never round. Rounding
 * happens at the points credit-simulation.md specifies, inside the engine —
 * the interface must not quietly change a figure on its way to the screen
 * (CLAUDE.md rule 6).
 */
class Format
{
    /** `Rp 92.131.800`. A null amount is a dash; a real zero is `Rp 0`. */
    public static function rupiah(?int $amount, string $whenNull = '—'): string
    {
        if ($amount === null) {
            return $whenNull;
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /** `18,5%` — one decimal place. */
    public static function percent(?float $fraction, string $whenNull = '—'): string
    {
        if ($fraction === null) {
            return $whenNull;
        }

        return number_format($fraction * 100, 1, ',', '.').'%';
    }

    /** `03 Agustus 2026`. */
    public static function date(Carbon|string|null $date, string $whenNull = '—'): string
    {
        if ($date === null || $date === '') {
            return $whenNull;
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbon->translatedFormat('d F Y');
    }

    /** `12 Bulan`. */
    public static function tenor(int $months): string
    {
        return $months.' Bulan';
    }

    /** `5 / 7` — completeness shown as a comparison, per pages.md section 9. */
    public static function ratio(int $done, int $total): string
    {
        return $done.' / '.$total;
    }
}
