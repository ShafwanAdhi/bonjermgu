<?php

use App\Support\InitialPassword;

it('generates a readable random password with the configured length', function () {
    config()->set('account.initial_password.length', 12);

    $password = InitialPassword::generate();

    expect($password)->toHaveLength(12)
        ->and($password)->toMatch('/^[ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789]+$/');
});

it('honours a changed password length without a code change', function () {
    config()->set('account.initial_password.length', 16);

    expect(InitialPassword::generate())->toHaveLength(16);
});

it('does not repeat itself over a practical sample', function () {
    config()->set('account.initial_password.length', 12);

    $passwords = collect(range(1, 100))->map(fn () => InitialPassword::generate());

    expect($passwords->unique())->toHaveCount(100);
});

it('refuses an unsafe password length', function () {
    config()->set('account.initial_password.length', 7);

    InitialPassword::generate();
})->throws(InvalidArgumentException::class);
