<?php

namespace App\Domain\Application;

/**
 * Products in scope. LMF and NCF are out of scope and must not appear here,
 * not even as inactive entries (docs/pages.md section 7).
 */
enum FinancingProduct: string
{
    case DanaTunai = 'DTN';
    case MobilBekas = 'UCF';

    public function label(): string
    {
        return match ($this) {
            self::DanaTunai => 'Dana Tunai',
            self::MobilBekas => 'Pembiayaan Mobil Bekas',
        };
    }
}
