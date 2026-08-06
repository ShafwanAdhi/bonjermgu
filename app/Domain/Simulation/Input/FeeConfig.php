<?php

namespace App\Domain\Simulation\Input;

use RuntimeException;

final readonly class FeeConfig
{
    /**
     * @param  array<int, array{min: int|float, max: int|float|null, fee: int|float}>  $fiduciaTiers
     */
    public function __construct(public array $fiduciaTiers) {}

    public function fiduciaFor(float $otrPrice): float
    {
        foreach ($this->fiduciaTiers as $tier) {
            if ($otrPrice >= $tier['min'] && ($tier['max'] === null || $otrPrice <= $tier['max'])) {
                return (float) $tier['fee'];
            }
        }

        throw new RuntimeException('Tier Fiducia tidak ditemukan untuk harga kendaraan.');
    }
}
