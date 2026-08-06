<?php

namespace App\Domain\Application;

/**
 * Konfirmasi Sumber Penghasilan Lainnya. Applies only when the debtor is an
 * individual; a legal entity stores NULL (docs/document-requirement.md §5).
 */
enum SpouseIncomeType: string
{
    case Bekerja = 'Pasangan Bekerja dan memiliki penghasilan';
    case Usaha = 'Pasangan memiliki usaha lainnya dan memiliki penghasilan';
    case Profesional = 'Pasangan adalah profesional dan memiliki penghasilan';
    case TidakAda = 'Tidak Ada';

    public function label(): string
    {
        return $this->value;
    }
}
