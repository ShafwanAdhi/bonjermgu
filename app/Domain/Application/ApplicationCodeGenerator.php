<?php

namespace App\Domain\Application;

use RuntimeException;

/**
 * Six random base62 characters — docs/application-tracking.md section 3.
 *
 * Uses random_bytes, not rand or uniqid: the code identifies an application in
 * URLs and a predictable sequence would let anyone enumerate them. It is still
 * only an identifier and never a credential — every request goes through
 * authentication and the ownership scope regardless (AD-08).
 *
 * Collisions are handled by retrying, with the unique index as the real
 * guarantee.
 */
class ApplicationCodeGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const LENGTH = 6;

    private const MAX_ATTEMPTS = 20;

    /**
     * @param  callable(string): bool  $exists  returns true when the code is taken
     */
    public static function generate(callable $exists): string
    {
        foreach (range(1, self::MAX_ATTEMPTS) as $ignored) {
            $code = self::random();

            if (! $exists($code)) {
                return $code;
            }
        }

        throw new RuntimeException(
            'Gagal membangkitkan Kode Aplikasi yang unik setelah '.self::MAX_ATTEMPTS.' percobaan.'
        );
    }

    public static function random(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            // random_int draws from the same CSPRNG as random_bytes and avoids
            // the modulo bias a raw byte would introduce over a 62-char set.
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }
}
