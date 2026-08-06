<?php

namespace App\Domain\Application;

/**
 * Type Debitur on a Credit Application — one of the two fields that decide
 * which documents apply (docs/document-requirement.md section 5).
 *
 * Distinct from App\Domain\Simulation\DebtorType: that one drives the rate
 * lookup, this one drives the document catalogue, and their stored values
 * differ. Do not merge them.
 */
enum DebtorType: string
{
    case PeroranganNonWiraswasta = 'Perorangan Non Wiraswasta';
    case PeroranganWiraswasta = 'Perorangan Wiraswasta';
    case BadanHukumUsaha = 'Badan Hukum Usaha';

    /** Spouse documents apply to individuals only. */
    public function isIndividual(): bool
    {
        return $this !== self::BadanHukumUsaha;
    }

    public function label(): string
    {
        return $this->value;
    }
}
