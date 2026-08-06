<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial Password
    |--------------------------------------------------------------------------
    |
    | AO accounts are created by Admin, so the system generates a random
    | initial password and shows it once, without using NIK or any other
    | personal identity number. Referral accounts choose their own password
    | during self-service registration.
    |
    */

    'initial_password' => [
        'length' => (int) env('INITIAL_PASSWORD_LENGTH', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Throttling
    |--------------------------------------------------------------------------
    |
    | Five attempts per minute per username + IP combination, per AD-15.
    |
    */

    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 60),
    ],

];
