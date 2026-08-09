<?php

namespace App\Support;

final class RupiahInput
{
    public static function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function nullableInteger(mixed $value): ?int
    {
        $digits = self::normalize($value);

        return $digits === '' ? null : (int) $digits;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    public static function normalizeRows(array $rows, array $keys): array
    {
        foreach ($rows as $index => $row) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $row)) {
                    $rows[$index][$key] = self::normalize($row[$key]);
                }
            }
        }

        return $rows;
    }
}
