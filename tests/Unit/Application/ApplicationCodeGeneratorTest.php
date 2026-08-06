<?php

use App\Domain\Application\ApplicationCodeGenerator;

it('generates a six character base62 code', function () {
    foreach (range(1, 200) as $ignored) {
        expect(ApplicationCodeGenerator::random())->toMatch('/^[0-9a-zA-Z]{6}$/');
    }
});

it('retries until it finds a free code', function () {
    $taken = ['aaaaaa', 'bbbbbb'];
    $attempts = 0;

    $code = ApplicationCodeGenerator::generate(function (string $candidate) use (&$attempts, $taken) {
        $attempts++;

        // Refuse the first two candidates whatever they are.
        return $attempts <= 2 || in_array($candidate, $taken, true);
    });

    expect($attempts)->toBe(3)
        ->and($code)->toMatch('/^[0-9a-zA-Z]{6}$/');
});

it('gives up rather than looping forever when every code collides', function () {
    ApplicationCodeGenerator::generate(fn () => true);
})->throws(RuntimeException::class);

/*
 * Not a randomness proof — just a smoke test that the generator is not
 * returning a constant or a short cycle, which would make codes guessable.
 */
it('does not repeat itself over a large sample', function () {
    $codes = collect(range(1, 2000))->map(fn () => ApplicationCodeGenerator::random());

    expect($codes->unique())->toHaveCount(2000);
});
