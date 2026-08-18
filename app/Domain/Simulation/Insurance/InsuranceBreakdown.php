<?php

namespace App\Domain\Simulation\Insurance;

final readonly class InsuranceBreakdown
{
    public function __construct(
        public float $casco,
        public float $loading,
        public float $extensions,
        public float $tjh,
        public float $driver,
        public float $passenger,
        public float $acp,
        public float $engineWarranty,
        public int $total,
        /**
         * Premi per tahun pertanggungan, untuk blok Insurance Paid Entry pada
         * lembar SPRINT. Totalnya sudah ada di atas; ini rinciannya, supaya
         * lembar itu tidak perlu menghitung apa pun sendiri.
         *
         * @var array<int, array{paid: float, discount: float}>
         */
        public array $yearly = [],
    ) {}

    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0, 0);
    }

    public function refundablePremium(): float
    {
        return $this->casco + $this->loading + $this->extensions;
    }
}
