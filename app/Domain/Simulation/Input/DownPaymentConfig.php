<?php

namespace App\Domain\Simulation\Input;

final readonly class DownPaymentConfig
{
    public function __construct(
        public float $dtnStandardRate,
        public float $dtnHighRiskRate,
        public float $ucfStandardRate,
        public float $ucfNonJapanStandardRate,
        public float $ucfEntrepreneurRate,
    ) {}
}
