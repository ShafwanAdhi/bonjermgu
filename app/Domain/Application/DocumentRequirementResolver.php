<?php

namespace App\Domain\Application;

/**
 * Decides which document requirement codes apply to a debtor profile.
 *
 * A pure function: the same two inputs always produce the same list, in the
 * same order. No Eloquent, no facades, no clock — the catalogue is data in the
 * database, the applicability is a branch in code (AD-09).
 *
 * The lists below are transcribed from docs/document-requirement.md section 7.
 * Changing one means changing that document first.
 */
class DocumentRequirementResolver
{
    /** Perorangan Non Wiraswasta — 7 requirements. */
    private const NON_WIRASWASTA = [
        'PMH-KTP',
        'PSG-KTP',
        'PMH-NPWP',
        'PMH-KK',
        'PMH-RUMAH',
        'PMH-SLIP',
        'PMH-RKR',
    ];

    /** Perorangan Wiraswasta — 9 requirements. Slip Gaji does not apply. */
    private const WIRASWASTA = [
        'PMH-KTP',
        'PSG-KTP',
        'PMH-NPWP',
        'PMH-KK',
        'PMH-RUMAH',
        'PMH-RKR',
        'PMH-USAHA',
        'PMH-FAKTUR',
        'PMH-PROFESI',
    ];

    /** Badan Hukum Usaha — 11 requirements. */
    private const BADAN_HUKUM = [
        'KOM-KTP',
        'DIR-KTP',
        'DIR-NPWP',
        'BDN-NPWP',
        'BDN-AKTA-DIR',
        'BDN-AKTA-UBH',
        'BDN-SKKUM',
        'BDN-NIB',
        'BDN-LAPKEU',
        'BDN-RKR',
        'BDN-SPK',
    ];

    /** Spouse documents, keyed by the income confirmation. */
    private const SPOUSE = [
        SpouseIncomeType::Bekerja->value => ['PSG-RKR', 'PSG-SLIP'],
        SpouseIncomeType::Usaha->value => ['PSG-RKR', 'PSG-USAHA', 'PSG-FAKTUR'],
        SpouseIncomeType::Profesional->value => ['PSG-RKR', 'PSG-PROFESI'],
        SpouseIncomeType::TidakAda->value => [],
    ];

    /**
     * @return array<int, string> requirement codes that apply
     */
    public static function resolve(DebtorType $type, ?SpouseIncomeType $spouse): array
    {
        $base = match ($type) {
            DebtorType::PeroranganNonWiraswasta => self::NON_WIRASWASTA,
            DebtorType::PeroranganWiraswasta => self::WIRASWASTA,
            DebtorType::BadanHukumUsaha => self::BADAN_HUKUM,
        };

        // A legal entity has no spouse documents whatever the second field says.
        if (! $type->isIndividual() || $spouse === null) {
            return $base;
        }

        return array_merge($base, self::SPOUSE[$spouse->value]);
    }
}
