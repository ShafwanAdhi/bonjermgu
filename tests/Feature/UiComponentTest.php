<?php

use Illuminate\Support\Facades\Blade;

it('shows a lock icon on disabled inputs and selects', function () {
    $input = Blade::render('<x-ui.input disabled value="Terkunci" />');
    $select = Blade::render('<x-ui.select disabled><option>Terkunci</option></x-ui.select>');

    expect($input)
        ->toContain('aria-hidden="true"')
        ->toContain('disabled:bg-surface-soft')
        ->and($select)
        ->toContain('aria-hidden="true"')
        ->toContain('appearance-none');
});
