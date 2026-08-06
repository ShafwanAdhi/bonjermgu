<?php

namespace App\Domain\Simulation\Input;

use InvalidArgumentException;

final readonly class ProductConfig
{
    /**
     * @param  array<int, float|null>  $effectiveRates
     */
    public function __construct(
        public string $name,
        public array $effectiveRates,
        public float $adminMax,
        public float $provisionRate = 0,
        public float $upRate = 0,
        public float $upAdmin = 0,
        public float $upProvision = 0,
        public float $upAcp = 0,
    ) {
        foreach ([12, 24, 36, 48, 60] as $tenor) {
            if (! array_key_exists($tenor, $this->effectiveRates)) {
                throw new InvalidArgumentException("Effective rate tenor {$tenor} harus disediakan, termasuk sebagai null.");
            }
        }
    }

    public function effectiveRateFor(int $tenorMonths): ?float
    {
        return $this->effectiveRates[$tenorMonths] ?? null;
    }
}
