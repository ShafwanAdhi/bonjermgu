<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

/**
 * Generates an initial account password without using personal identity data.
 *
 * The plaintext returned here is shown to the account holder exactly once,
 * at registration. It is never stored, logged, or mailed.
 */
class InitialPassword
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

    public static function generate(): string
    {
        $length = (int) config('account.initial_password.length');

        if ($length < 8) {
            throw new InvalidArgumentException(
                'Initial password length must be at least 8 characters.'
            );
        }

        $password = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            try {
                $password .= self::ALPHABET[random_int(0, $max)];
            } catch (\Exception $exception) {
                throw new RuntimeException('Unable to generate an initial password.', previous: $exception);
            }
        }

        return $password;
    }
}
