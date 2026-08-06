<?php

use App\Domain\Application\DebtorType;
use App\Domain\Application\DocumentRequirementResolver;
use App\Domain\Application\SpouseIncomeType;

/*
 * Reference lists from docs/document-requirement.md section 7. These counts
 * are stated in the document and are the cheapest way to catch a typo in the
 * resolver.
 */

it('resolves seven requirements for Perorangan Non Wiraswasta with no spouse income', function () {
    $codes = DocumentRequirementResolver::resolve(
        DebtorType::PeroranganNonWiraswasta,
        SpouseIncomeType::TidakAda,
    );

    expect($codes)->toBe([
        'PMH-KTP', 'PSG-KTP', 'PMH-NPWP', 'PMH-KK', 'PMH-RUMAH', 'PMH-SLIP', 'PMH-RKR',
    ]);
});

it('resolves nine requirements for Perorangan Wiraswasta with no spouse income', function () {
    $codes = DocumentRequirementResolver::resolve(
        DebtorType::PeroranganWiraswasta,
        SpouseIncomeType::TidakAda,
    );

    expect($codes)->toHaveCount(9)
        ->and($codes)->toContain('PMH-USAHA', 'PMH-FAKTUR', 'PMH-PROFESI');
});

/* Slip Gaji does not apply to a self-employed debtor. */
it('drops Slip Gaji for Wiraswasta', function () {
    $codes = DocumentRequirementResolver::resolve(
        DebtorType::PeroranganWiraswasta,
        SpouseIncomeType::TidakAda,
    );

    expect($codes)->not->toContain('PMH-SLIP');
});

it('resolves eleven requirements for Badan Hukum Usaha', function () {
    $codes = DocumentRequirementResolver::resolve(DebtorType::BadanHukumUsaha, null);

    expect($codes)->toHaveCount(11)
        ->and($codes)->toBe([
            'KOM-KTP', 'DIR-KTP', 'DIR-NPWP', 'BDN-NPWP', 'BDN-AKTA-DIR', 'BDN-AKTA-UBH',
            'BDN-SKKUM', 'BDN-NIB', 'BDN-LAPKEU', 'BDN-RKR', 'BDN-SPK',
        ]);
});

it('adds the right spouse documents per income confirmation', function (
    SpouseIncomeType $spouse,
    array $expected,
) {
    $codes = DocumentRequirementResolver::resolve(DebtorType::PeroranganNonWiraswasta, $spouse);

    $spouseCodes = array_values(array_filter($codes, fn ($c) => str_starts_with($c, 'PSG-') && $c !== 'PSG-KTP'));

    expect($spouseCodes)->toBe($expected);
})->with([
    [SpouseIncomeType::Bekerja, ['PSG-RKR', 'PSG-SLIP']],
    [SpouseIncomeType::Usaha, ['PSG-RKR', 'PSG-USAHA', 'PSG-FAKTUR']],
    [SpouseIncomeType::Profesional, ['PSG-RKR', 'PSG-PROFESI']],
    [SpouseIncomeType::TidakAda, []],
]);

/*
 * A legal entity has no spouse documents whatever the second field says. The
 * database forbids the combination too, but the resolver must not depend on
 * that to behave.
 */
it('never adds spouse documents to a legal entity', function () {
    foreach (SpouseIncomeType::cases() as $spouse) {
        $codes = DocumentRequirementResolver::resolve(DebtorType::BadanHukumUsaha, $spouse);

        expect($codes)->toHaveCount(11)
            ->and(array_filter($codes, fn ($c) => str_starts_with($c, 'PSG-')))->toBeEmpty();
    }
});

/* Pure function: same input, same output, every time. */
it('is deterministic', function () {
    $first = DocumentRequirementResolver::resolve(DebtorType::PeroranganWiraswasta, SpouseIncomeType::Usaha);
    $second = DocumentRequirementResolver::resolve(DebtorType::PeroranganWiraswasta, SpouseIncomeType::Usaha);

    expect($first)->toBe($second);
});

it('never returns a duplicate code', function (DebtorType $type, ?SpouseIncomeType $spouse) {
    $codes = DocumentRequirementResolver::resolve($type, $spouse);

    expect($codes)->toBe(array_values(array_unique($codes)));
})->with([
    [DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Bekerja],
    [DebtorType::PeroranganWiraswasta, SpouseIncomeType::Usaha],
    [DebtorType::PeroranganNonWiraswasta, SpouseIncomeType::Profesional],
    [DebtorType::BadanHukumUsaha, null],
]);
