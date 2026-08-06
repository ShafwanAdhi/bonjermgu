<?php

namespace App\Domain\Simulation;

use InvalidArgumentException;

final class Rounding
{
    public static function down(float $value, int $unit): int
    {
        self::guardUnit($unit);

        $scaled = $value / $unit;
        $epsilon = PHP_FLOAT_EPSILON * max(1.0, abs($scaled)) * 8;

        return (int) (floor($scaled + $epsilon) * $unit);
    }

    public static function up(float $value, int $unit): int
    {
        self::guardUnit($unit);

        if ($value === 0.0) {
            return 0;
        }

        $scaled = $value / $unit;
        $epsilon = PHP_FLOAT_EPSILON * max(1.0, abs($scaled)) * 8;

        return (int) (ceil($scaled - $epsilon) * $unit);
    }

    private static function guardUnit(int $unit): void
    {
        if ($unit <= 0) {
            throw new InvalidArgumentException('Unit pembulatan harus lebih besar dari nol.');
        }
    }
}
